<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticlePlan;
use App\Models\FacebookImportedPost;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FacebookImportsRebuildService
{
    /**
     * @return array<string, mixed>
     */
    public function preview(int $maxPosts = 300): array
    {
        return $this->run(true, $maxPosts);
    }

    /**
     * @return array<string, mixed>
     */
    public function rebuild(int $maxPosts = 300, ?int $authorUserId = null): array
    {
        return $this->run(false, $maxPosts, $authorUserId);
    }

    /**
     * @return array<string, mixed>
     */
    private function run(bool $dryRun, int $maxPosts, ?int $authorUserId = null): array
    {
        $maxPosts = max(1, min(1000, $maxPosts));
        $fetched = $this->fetchFacebookPosts($maxPosts);

        $eligiblePosts = collect($fetched['posts'])
            ->filter(fn (array $post): bool => ! $this->isVideoPayload($post))
            ->filter(function (array $post): bool {
                return trim((string) ($post['message'] ?? '')) !== '';
            })
            ->values();

        $summary = [
            'dry_run' => $dryRun,
            'fetched_posts' => count($fetched['posts']),
            'eligible_posts' => $eligiblePosts->count(),
            'deleted_articles' => 0,
            'created_articles' => 0,
            'imported_posts' => 0,
            'deleted_article_images' => 0,
            'page_id' => $fetched['page_id'],
            'has_more_pages' => $fetched['has_more_pages'],
        ];

        if ($dryRun) {
            return $summary;
        }

        DB::transaction(function () use ($eligiblePosts, $authorUserId, &$summary): void {
            $summary['deleted_article_images'] = $this->deleteExistingArticleImages();
            $summary['deleted_articles'] = Article::query()->count();
            Article::query()->delete();

            ArticlePlan::query()->update([
                'article_id' => null,
                'refresh_status' => ArticlePlan::REFRESH_STATUS_DRAFT,
                'last_refreshed_at' => null,
            ]);

            FacebookImportedPost::query()->delete();

            foreach ($eligiblePosts as $payload) {
                $import = $this->createImportRecord($payload);
                $summary['imported_posts']++;

                $this->createArticleFromImport($import, $authorUserId);
                $summary['created_articles']++;
            }
        });

        return $summary;
    }

    /**
     * @return array{posts: array<int, array<string, mixed>>, page_id: string, has_more_pages: bool}
     */
    private function fetchFacebookPosts(int $maxPosts): array
    {
        $pageId = trim((string) config('services.facebook.page_id', ''));
        $token = trim((string) config('services.facebook.page_access_token', ''));

        if ($pageId === '' || $token === '') {
            throw new \RuntimeException('Missing FB_PAGE_ID or FB_PAGE_ACCESS_TOKEN.');
        }

        $url = "https://graph.facebook.com/v20.0/{$pageId}/posts";
        $posts = [];
        $hasMorePages = false;

        while (count($posts) < $maxPosts && $url !== null) {
            $response = Http::timeout(30)->get($url, [
                'access_token' => $token,
                'fields' => 'id,message,story,permalink_url,full_picture,attachments{media,url,type,target,title,description,subattachments},created_time',
                'limit' => min(100, $maxPosts - count($posts)),
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Facebook Graph API request failed: ' . $response->status());
            }

            $json = $response->json();
            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            foreach ($data as $row) {
                if (is_array($row)) {
                    $posts[] = $row;
                }
            }

            $next = data_get($json, 'paging.next');
            if (is_string($next) && $next !== '' && count($posts) < $maxPosts) {
                $url = $next;
                $hasMorePages = true;
                continue;
            }

            $url = null;
        }

        return [
            'posts' => $posts,
            'page_id' => $pageId,
            'has_more_pages' => $hasMorePages,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function isVideoPayload(array $payload): bool
    {
        return $this->containsVideoMarker($payload);
    }

    /**
     * @param mixed $value
     */
    private function containsVideoMarker(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $key => $item) {
            if (is_string($item) && mb_strtolower($item) === 'video') {
                return true;
            }

            if (is_string($key) && in_array(mb_strtolower($key), ['type', 'media_type'], true) && is_string($item) && mb_strtolower($item) === 'video') {
                return true;
            }

            if ($this->containsVideoMarker($item)) {
                return true;
            }
        }

        return false;
    }

    private function deleteExistingArticleImages(): int
    {
        $deleted = 0;
        $paths = Article::query()
            ->get(['cover_image_path', 'cover_image_square_path', 'cover_image_landscape_path'])
            ->flatMap(function (Article $article): array {
                return [
                    trim((string) $article->cover_image_path),
                    trim((string) $article->cover_image_square_path),
                    trim((string) $article->cover_image_landscape_path),
                ];
            })
            ->filter(fn (string $path): bool => $path !== '' && ! Str::startsWith($path, ['http://', 'https://']))
            ->unique()
            ->values();

        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createImportRecord(array $payload): FacebookImportedPost
    {
        $createdAt = $this->parseDate((string) ($payload['created_time'] ?? ''));

        return FacebookImportedPost::query()->create([
            'facebook_post_id' => (string) ($payload['id'] ?? ''),
            'source_node_id' => trim((string) config('services.facebook.page_id', '')),
            'source_node_type' => 'page',
            'message' => isset($payload['message']) ? (string) $payload['message'] : null,
            'story' => isset($payload['story']) ? (string) $payload['story'] : null,
            'permalink_url' => isset($payload['permalink_url']) ? (string) $payload['permalink_url'] : null,
            'full_picture' => isset($payload['full_picture']) ? (string) $payload['full_picture'] : null,
            'attachments_json' => is_array($payload['attachments'] ?? null) ? $payload['attachments'] : null,
            'raw_json' => $payload,
            'facebook_created_time' => $createdAt,
            'imported_at' => now(),
            'last_synced_at' => now(),
        ]);
    }

    private function createArticleFromImport(FacebookImportedPost $import, ?int $authorUserId): void
    {
        $publishedAt = $import->facebook_created_time ?? $import->imported_at ?? now();
        $title = $this->buildTitleFromMessage((string) ($import->message ?? ''), $import->id);
        $slug = $this->buildUniqueSlug($title, (string) $import->facebook_post_id);
        $imagePath = $this->storePostImage($import, $slug, $publishedAt instanceof Carbon ? $publishedAt : Carbon::parse($publishedAt));

        $article = new Article([
            'title' => $title,
            'slug' => $slug,
            'excerpt' => Str::limit(trim((string) $import->message), 180, ''),
            'content' => (string) $import->message,
            'meta_description' => Str::limit(trim((string) $import->message), 155, ''),
            'keywords' => null,
            'lsi_keywords' => null,
            'is_published' => true,
            'is_auto_post' => false,
            'published_at' => $publishedAt,
            'author_user_id' => $authorUserId,
        ]);

        if ($imagePath !== null) {
            $article->cover_image_path = $imagePath;
            $article->cover_image_square_path = $imagePath;
            $article->cover_image_landscape_path = $imagePath;
        }

        $article->setCreatedAt($publishedAt);
        $article->setUpdatedAt($publishedAt);
        $article->save();
    }

    private function buildTitleFromMessage(string $message, int $id): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $message) ?: '');
        if ($normalized === '') {
            return 'Facebook Post ' . $id;
        }

        $firstLine = trim((string) preg_split('/\R/u', $normalized, 2)[0]);
        return Str::limit($firstLine !== '' ? $firstLine : $normalized, 140, '');
    }

    private function buildUniqueSlug(string $title, string $facebookPostId): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'facebook-post';
        }

        $safePostId = Str::slug($facebookPostId);
        $suffix = $safePostId !== '' ? $safePostId : Str::random(8);

        return Str::limit($base, 150, '') . '-' . $suffix;
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function storePostImage(FacebookImportedPost $post, string $slug, Carbon $date): ?string
    {
        $sourceImageUrl = trim((string) ($post->full_picture ?? ''));
        if ($sourceImageUrl === '') {
            $sourceImageUrl = $this->resolveImageUrlFromAttachments($post->attachments_json);
        }
        if ($sourceImageUrl === '') {
            return null;
        }

        try {
            $response = Http::timeout(20)->get($sourceImageUrl);
            if (! $response->successful()) {
                return null;
            }

            $contentType = (string) $response->header('Content-Type', '');
            $ext = 'jpg';
            if (str_contains($contentType, 'png')) {
                $ext = 'png';
            } elseif (str_contains($contentType, 'webp')) {
                $ext = 'webp';
            }

            $year = $date->copy()->timezone('Asia/Bangkok')->format('Y');
            $path = "articles/{$year}/{$slug}/facebook-cover-{$post->id}.{$ext}";
            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param mixed $attachments
     */
    private function resolveImageUrlFromAttachments(mixed $attachments): string
    {
        if (! is_array($attachments) || ! isset($attachments['data']) || ! is_array($attachments['data'])) {
            return '';
        }

        foreach ($attachments['data'] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $candidate = trim((string) data_get($item, 'media.image.src', ''));
            if ($candidate === '') {
                $candidate = trim((string) data_get($item, 'media.source', ''));
            }
            if ($candidate === '') {
                $candidate = trim((string) data_get($item, 'url', ''));
            }

            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }
}

