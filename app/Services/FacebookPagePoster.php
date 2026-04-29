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
            return ['success' => false, 'error' => 'Missing Facebook Page ID or Access Token in configuration.'];
        }

        $articleUrl = route('articles.show', ['slug' => $article->slug]);
        $message = "📝 ผลสลากกินแบ่งรัฐบาล: {$article->title}\n\n";
        if ($article->excerpt) {
            $message .= strip_tags($article->excerpt) . "\n\n";
        }
        $message .= "อ่านผลรางวัลฉบับเต็มและตรวจเลขอื่นๆ ได้ที่นี่ครับ 👇\n{$articleUrl}";

        // If we have a landscape cover, post it as a photo
        if ($article->cover_image_landscape_path && \Storage::disk('public')->exists($article->cover_image_landscape_path)) {
            $url = "https://graph.facebook.com/v19.0/{$pageId}/photos";
            $imagePath = \Storage::disk('public')->path($article->cover_image_landscape_path);
            
            try {
                $response = Http::attach('source', file_get_contents($imagePath), basename($imagePath))
                    ->post($url, [
                        'message' => $message,
                        'access_token' => $accessToken,
                    ]);

                if ($response->successful()) {
                    Log::info("Successfully posted photo to Facebook Page for article [{$article->id}].");
                    return ['success' => true, 'id' => $response->json('id')];
                }
            } catch (\Throwable $e) {
                Log::error("FB Photo Upload failed: " . $e->getMessage());
            }
        }

        // Fallback to simple link post if photo fails or missing
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

            return ['success' => false, 'error' => $response->json('error')['message'] ?? 'FB API Error'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
