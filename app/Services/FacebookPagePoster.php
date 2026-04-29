<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPagePoster
{
    public function postArticle(Article $article): array
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

        // If we have a landscape cover, post it as a photo
        $hasCover = !empty($article->cover_image_landscape_path);
        $existsOnDisk = $hasCover && \Illuminate\Support\Facades\Storage::disk('public')->exists($article->cover_image_landscape_path);

        Log::info("FB Post Debug: Article {$article->id} - HasCover: " . ($hasCover ? 'Yes' : 'No') . ", Path: {$article->cover_image_landscape_path}, Exists: " . ($existsOnDisk ? 'Yes' : 'No'));

        if ($existsOnDisk) {
            $url = "https://graph.facebook.com/v19.0/{$pageId}/photos";
            $imagePath = \Illuminate\Support\Facades\Storage::disk('public')->path($article->cover_image_landscape_path);
            
            try {
                Log::info("FB Post: Attempting photo upload from: {$imagePath}");
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
            Log::warning("FB Post: Skipping photo upload - File not found or path empty.");
        }

        // Fallback to simple link post if photo fails or missing
        Log::info("FB Post: Falling back to link-only post.");
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
