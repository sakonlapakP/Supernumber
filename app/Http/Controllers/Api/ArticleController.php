<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\FacebookPagePoster;
use App\Services\LineNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class ArticleController extends Controller
{
    public function index()
    {
        return Article::latest()->paginate(20);
    }

    public function importJson(Request $request): JsonResponse
    {
        $request->validate([
            'json_data' => ['required', 'string'],
        ]);

        $data = json_decode($request->input('json_data'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'json_data' => ['รูปแบบ JSON ไม่ถูกต้อง: ' . json_last_error_msg()],
            ]);
        }

        if (! is_array($data)) {
            throw ValidationException::withMessages([
                'json_data' => ['ข้อมูลใน JSON ต้องเป็น Object หรือ Array ของบทความ'],
            ]);
        }

        $articlesToImport = isset($data['title']) ? [$data] : $data;
        $imported = 0;
        $skipped = 0;

        foreach ($articlesToImport as $item) {
            if (! is_array($item) || empty($item['title'])) {
                $skipped++;
                continue;
            }

            $title = trim((string) $item['title']);
            $content = (string) ($item['content'] ?? '');
            $slugInput = $this->stringValue($item['slug'] ?? null);
            $slug = $this->uniqueArticleSlug($slugInput ? Str::slug($slugInput) : $title);

            $articleData = [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $this->stringValue($item['excerpt'] ?? null) ?? Str::limit(strip_tags($content), 160),
                'content' => $content,
                'is_published' => $this->booleanValue($item['is_published'] ?? null, false),
                'published_at' => isset($item['published_at'])
                    ? Carbon::parse($item['published_at'], 'Asia/Bangkok')->setTimezone(config('app.timezone'))
                    : null,
                'is_auto_post' => $this->booleanValue($item['is_auto_post'] ?? null, false),
                'author_user_id' => $request->user()->id,
                'meta_description' => $this->stringValue($item['meta_description'] ?? null),
                'keywords' => $this->stringValue($item['keywords'] ?? null),
                'lsi_keywords' => $this->stringValue($item['lsi_keywords'] ?? null),
            ];

            if ($this->articleColumnExists('image_guidelines')) {
                $articleData['image_guidelines'] = $this->imageGuidelinesValue($item['image_guidelines'] ?? null);
            }

            Article::create($articleData);

            $imported++;
        }

        return response()->json([
            'imported' => $imported,
            'skipped' => $skipped,
            'message' => "นำเข้าบทความสำเร็จ {$imported} รายการ",
        ], 201);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'meta_description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'lsi_keywords' => 'nullable|string',
            'is_published' => 'boolean',
            'is_auto_post' => 'boolean',
            'published_at' => 'nullable|date',
            'image_guidelines' => 'nullable|string', // รับเป็น JSON string จาก FormData
            'cover_landscape' => 'nullable|image|max:2048',
            'cover_square' => 'nullable|image|max:2048',
        ]);

        $data = $validated;
        unset($data['cover_landscape'], $data['cover_square']);
        
        if ($request->hasFile('cover_landscape')) {
            $data['cover_image_landscape_path'] = $request->file('cover_landscape')->store('articles', 'public');
            $data['cover_image_path'] = $data['cover_image_landscape_path']; // ใช้เป็นรูปหลักด้วย
        }

        if ($request->hasFile('cover_square')) {
            $data['cover_image_square_path'] = $request->file('cover_square')->store('articles', 'public');
        }
        
        if (isset($data['image_guidelines'])) {
            $data['image_guidelines'] = json_decode($data['image_guidelines'], true);
        }
        if (! empty($data['published_at'])) {
            $data['published_at'] = Carbon::parse($data['published_at'], 'Asia/Bangkok')->setTimezone(config('app.timezone'));
        }
        $this->removeMissingArticleColumns($data);

        $article = Article::create([
            ...$data,
            'slug' => Str::slug($data['title']) . '-' . time(),
            'author_user_id' => $request->user()->id,
            'published_at' => $data['published_at'] ?? ($request->boolean('is_published') ? now() : null),
        ]);

        return response()->json($article, 201);
    }

    public function show(Article $article)
    {
        return $article;
    }

    public function previewUrl(Article $article): JsonResponse
    {
        $isCurrentlyPublic = $article->is_published 
            && $article->slug 
            && ($article->published_at === null || $article->published_at->lte(now('Asia/Bangkok')));

        $url = $isCurrentlyPublic
            ? route('articles.show', $article->slug)
            : URL::temporarySignedRoute(
                'articles.signed-preview',
                now()->addHours(24),
                ['article' => $article]
            );

        return response()->json(['url' => $url]);
    }

    public function share(Request $request, Article $article): JsonResponse
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', 'in:facebook,line'],
        ]);

        if (! $article->is_published || ! $article->slug) {
            return response()->json([
                'message' => 'กรุณาเผยแพร่บทความก่อนแชร์',
            ], 422);
        }

        if ($validated['platform'] === 'facebook') {
            $result = app(FacebookPagePoster::class)->postArticle($article);

            if ($result['success'] ?? false) {
                return response()->json([
                    'message' => 'แชร์ไปที่ Facebook Page สำเร็จ',
                    'id' => $result['id'] ?? null,
                ]);
            }

            return response()->json([
                'message' => 'แชร์ไปที่ Facebook Page ไม่สำเร็จ',
                'error' => $result['error'] ?? 'Unknown Error',
            ], 422);
        }

        $messages = [];

        $imageUrl = $this->articleSquareImageUrl($article);
        if ($imageUrl !== null) {
            $messages[] = [
                'type' => 'image',
                'originalContentUrl' => $imageUrl,
                'previewImageUrl' => $imageUrl,
            ];
        }

        $messages[] = [
            'type' => 'text',
            'text' => "บทความใหม่\n{$article->title}\n\nอ่านเพิ่มเติม: " . route('articles.show', $article->slug),
        ];

        $log = app(LineNotifier::class)->queueBroadcastMessages(
            'article_broadcast',
            $messages,
            $article
        );

        if ($log === null) {
            return response()->json([
                'message' => 'แชร์ไปที่ LINE ไม่สำเร็จ',
                'error' => 'LINE ยังไม่ได้ตั้งค่า หรือไม่สามารถบันทึกคิวส่งข้อความได้',
            ], 422);
        }

        if (Schema::hasColumn('articles', 'is_line_broadcasted')) {
            $article->forceFill(['is_line_broadcasted' => true])->save();
        }

        return response()->json(['message' => 'แชร์ไปที่ LINE สำเร็จ']);
    }

    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'meta_description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'lsi_keywords' => 'nullable|string',
            'is_published' => 'boolean',
            'is_auto_post' => 'boolean',
            'published_at' => 'nullable|date',
            'image_guidelines' => 'nullable|string',
            'cover_landscape' => 'nullable|image|max:2048',
            'cover_square' => 'nullable|image|max:2048',
        ]);

        $data = $validated;
        unset($data['cover_landscape'], $data['cover_square']);

        if ($request->hasFile('cover_landscape')) {
            if ($article->cover_image_landscape_path) {
                Storage::disk('public')->delete($article->cover_image_landscape_path);
            }
            $data['cover_image_landscape_path'] = $request->file('cover_landscape')->store('articles', 'public');
            $data['cover_image_path'] = $data['cover_image_landscape_path'];
        }

        if ($request->hasFile('cover_square')) {
            if ($article->cover_image_square_path) {
                Storage::disk('public')->delete($article->cover_image_square_path);
            }
            $data['cover_image_square_path'] = $request->file('cover_square')->store('articles', 'public');
        }

        if (isset($data['image_guidelines'])) {
            $data['image_guidelines'] = json_decode($data['image_guidelines'], true);
        }
        if (! empty($data['published_at'])) {
            $data['published_at'] = Carbon::parse($data['published_at'], 'Asia/Bangkok')->setTimezone(config('app.timezone'));
        }
        $this->removeMissingArticleColumns($data);

        if ($request->boolean('is_published') && !$article->is_published && !$request->published_at) {
            $data['published_at'] = now();
        }

        $article->update($data);

        return response()->json($article);
    }

    public function destroy(Article $article)
    {
        if ($article->cover_image_landscape_path) {
            Storage::disk('public')->delete($article->cover_image_landscape_path);
        }
        if ($article->cover_image_square_path) {
            Storage::disk('public')->delete($article->cover_image_square_path);
        }
        $article->delete();
        return response()->json(['message' => 'Article deleted']);
    }

    private function stringValue(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = implode(', ', array_filter(array_map(
                static fn (mixed $item): string => trim((string) $item),
                $value
            )));
        }

        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : null;
    }

    private function booleanValue(mixed $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    private function imageGuidelinesValue(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $guidelines = [
            'landscape_prompt' => $this->stringValue($value['landscape_prompt'] ?? null),
            'square_prompt' => $this->stringValue($value['square_prompt'] ?? null),
        ];

        return array_filter($guidelines, static fn (?string $prompt): bool => $prompt !== null) ?: null;
    }

    private function removeMissingArticleColumns(array &$data): void
    {
        foreach (['image_guidelines'] as $column) {
            if (array_key_exists($column, $data) && ! $this->articleColumnExists($column)) {
                unset($data[$column]);
            }
        }
    }

    private function articleColumnExists(string $column): bool
    {
        static $columns = null;

        if ($columns === null) {
            $columns = array_flip(Schema::getColumnListing('articles'));
        }

        return isset($columns[$column]);
    }

    private function articleSquareImageUrl(Article $article): ?string
    {
        $path = $article->cover_image_square_path ?: $article->cover_image_landscape_path;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function uniqueArticleSlug(string $value): string
    {
        $base = Str::slug(trim($value));
        $base = $base !== '' ? $base : 'article';
        $slug = $base;
        $suffix = 2;

        while (Article::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
