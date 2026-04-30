<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPagePoster
{
    public function postArticle(Article $article, ?string $manualImagePath = null): array
    {
        $pageId = config('services.facebook.page_id');
        $accessToken = config('services.facebook.page_access_token');

        if (empty($pageId) || empty($accessToken)) {
            Log::error("FB Post: Missing config - ID: " . ($pageId ? 'OK' : 'MISSING') . ", Token: " . ($accessToken ? 'OK' : 'MISSING'));
            return ['success' => false, 'error' => 'Missing Facebook Page ID or Access Token in configuration.'];
        }

        $articleUrl = route('articles.show', ['slug' => $article->slug]);
        $message = "📝 ผลสลากกินแบ่งรัฐบาล: {$article->title}\n\n";
        if ($article->excerpt) {
            $message .= strip_tags($article->excerpt) . "\n\n";
        }
        $message .= "อ่านผลรางวัลฉบับเต็มและตรวจเลขอื่นๆ ได้ที่นี่ครับ 👇\n{$articleUrl}";

        // Robust Image Path Resolution (Prefer PNG over SVG)
        $imagePath = null;
        
        // Use manual image path if provided
        if ($manualImagePath) {
            $path = \Illuminate\Support\Facades\Storage::disk('public')->path($manualImagePath);
            if (file_exists($path) && is_readable($path)) {
                $imagePath = $path;
                Log::info("FB Post: Using manual image override: {$imagePath}");
            }
        }

        if (!$imagePath && !empty($article->cover_image_landscape_path)) {
            $relPath = $article->cover_image_landscape_path;
            
            // If the path is SVG, try to find a PNG version first
            if (str_ends_with(strtolower($relPath), '.svg')) {
                $pngRelPath = str_replace('.svg', '.png', $relPath);
                $pngPath = \Illuminate\Support\Facades\Storage::disk('public')->path($pngRelPath);
                if (file_exists($pngPath) && is_readable($pngPath)) {
                    $relPath = $pngRelPath;
                }
            }

            // Try 1: Storage Disk Public Path
            $path1 = \Illuminate\Support\Facades\Storage::disk('public')->path($relPath);
            if (file_exists($path1) && is_readable($path1)) {
                $imagePath = $path1;
            } 
            // Try 2: Direct public_path if stored there
            else {
                $path2 = public_path('storage/' . $relPath);
                if (file_exists($path2) && is_readable($path2)) {
                    $imagePath = $path2;
                }
            }
        }

        Log::info("FB Post Debug: Article {$article->id} - Final Image Path: " . ($imagePath ?: 'NOT_FOUND'));

        if ($imagePath && !str_ends_with(strtolower($imagePath), '.svg')) {
            $url = "https://graph.facebook.com/v19.0/{$pageId}/photos";
            
            try {
                Log::info("FB Post: Uploading photo from: {$imagePath}");
                $response = Http::attach('source', file_get_contents($imagePath), basename($imagePath))
                    ->post($url, [
                        'message' => $message,
                        'access_token' => $accessToken,
                    ]);

                if ($response->successful()) {
                    Log::info("Successfully posted photo to Facebook Page for article [{$article->id}]. FB ID: " . $response->json('id'));
                    return ['success' => true, 'id' => $response->json('id')];
                }

                Log::error("FB Photo Upload API error: " . json_encode($response->json()));
            } catch (\Throwable $e) {
                Log::error("FB Photo Upload exception: " . $e->getMessage());
            }
        } else {
            Log::warning("FB Post: Skipping photo upload - File not found, path empty, or still SVG.");
        }

        // Fallback to simple link post
        Log::info("FB Post: Falling back to link post.");
        $url = "https://graph.facebook.com/v19.0/{$pageId}/feed";
        try {
            $response = Http::post($url, [
                'message' => $message,
                'link' => $articleUrl,
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                Log::info("Successfully posted link to Facebook Page for article [{$article->id}].");
                return ['success' => true, 'id' => $response->json('id')];
            }

            $error = $response->json('error')['message'] ?? 'FB API Error';
            Log::error("FB Link Post API error: " . $error);
            return ['success' => false, 'error' => $error];
        } catch (\Throwable $e) {
            Log::error("FB Link Post exception: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
