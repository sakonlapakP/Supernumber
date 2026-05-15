<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\FacebookImportedPost;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SyncArticlesFromFacebookByTitleCommand extends Command
{
    protected $signature = 'articles:sync-facebook-by-title
        {--limit=50 : Max articles to update}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Update articles missing images by matching Facebook imported posts using the article title (content uses Facebook message 1:1, created_at = published_at).';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        $articles = Article::query()
            ->where(function ($query) {
                $query
                    ->whereNull('cover_image_landscape_path')
                    ->orWhere('cover_image_landscape_path', '')
                    ->orWhereNull('cover_image_square_path')
                    ->orWhere('cover_image_square_path', '');
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($articles->isEmpty()) {
            $this->info('No articles matched (missing cover images).');
            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($articles as $article) {
            $title = trim((string) $article->title);
            if ($title === '') {
                $skipped++;
                continue;
            }

            $searchTitle = $this->normalizeTitleForSearch($title);

            $post = FacebookImportedPost::query()
                ->whereNotNull('message')
                ->whereRaw('LOWER(COALESCE(message, "")) like ?', ['%' . mb_strtolower($searchTitle) . '%'])
                ->orderByDesc('facebook_created_time')
                ->orderByDesc('id')
                ->first();

            if (! $post instanceof FacebookImportedPost) {
                $this->line("SKIP article#{$article->id} (no fb match): {$title}");
                $skipped++;
                continue;
            }

            $storedPath = $this->storePostImage($post, (string) $article->slug, $article->published_at);
            if ($storedPath === null) {
                $this->line("SKIP article#{$article->id} (no image): {$title}");
                $skipped++;
                continue;
            }

            $this->line("UPDATE article#{$article->id} <= fb_import#{$post->id} ({$storedPath})");

            if (! $dryRun) {
                $article->cover_image_landscape_path = $storedPath;
                $article->cover_image_square_path = $storedPath;
                $article->cover_image_path = $storedPath;
                $article->content = (string) ($post->message ?? '');

                if ($article->published_at !== null) {
                    $article->created_at = $article->published_at;
                }

                $article->save();
            }

            $updated++;
        }

        $this->info("Done. updated={$updated}, skipped={$skipped}, dry_run=" . ($dryRun ? 'true' : 'false'));

        return self::SUCCESS;
    }

    private function storePostImage(FacebookImportedPost $post, string $slug, $publishedAt): ?string
    {
        $imageUrl = $this->resolveImageUrl($post);
        if ($imageUrl === null) {
            return null;
        }

        $date = $publishedAt instanceof Carbon
            ? $publishedAt->copy()->timezone('Asia/Bangkok')
            : now('Asia/Bangkok');

        $safeSlug = trim($slug) !== '' ? $slug : ('article-' . $post->id);

        try {
            $response = Http::timeout(20)->get($imageUrl);
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

            $year = $date->format('Y');
            $path = "articles/{$year}/{$safeSlug}/facebook-cover-{$post->id}.{$ext}";
            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveImageUrl(FacebookImportedPost $post): ?string
    {
        $imageUrl = trim((string) ($post->full_picture ?? ''));
        if ($imageUrl !== '') {
            return $imageUrl;
        }

        $attachments = is_array($post->attachments_json) ? $post->attachments_json : [];
        if (! isset($attachments['data']) || ! is_array($attachments['data'])) {
            return null;
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

        return null;
    }

    private function normalizeTitleForSearch(string $title): string
    {
        $title = trim($title);

        if (str_contains($title, ':')) {
            $title = trim(explode(':', $title, 2)[0]);
        }

        // Remove Thai year like 2566 / 2569 etc to increase match chance.
        $title = preg_replace('/\\b25\\d{2}\\b/u', '', $title) ?: $title;

        $title = trim(preg_replace('/\\s+/u', ' ', $title) ?: $title);

        return $title !== '' ? $title : trim($title);
    }
}
