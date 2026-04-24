<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPagePoster
{
    public function postArticle(Article $article): bool
    {
        $pageId = config('services.facebook.page_id');
        $accessToken = config('services.facebook.page_access_token');

        if (empty($pageId) || empty($accessToken)) {
            return false;
        }

        $url = "https://graph.facebook.com/v19.0/{$pageId}/feed";

        $articleUrl = route('articles.show', ['slug' => $article->slug]);
        
        $message = "📝 บทความใหม่: {$article->title}\n\n";
        if ($article->excerpt) {
            $message .= strip_tags($article->excerpt) . "\n\n";
        }
        $message .= "อ่านเพิ่มเติมได้ที่นี่เลยครับ 👇\n";
        // Do not add the URL directly to the message if we are passing it as a link parameter
        // The link parameter will create a nice preview card on Facebook

        try {
            $response = Http::post($url, [
                'message' => $message,
                'link' => $articleUrl,
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                Log::info("Successfully posted article [{$article->id}] to Facebook Page.", [
                    'fb_post_id' => $response->json('id')
                ]);
                return true;
            }

            Log::error("Failed to post article [{$article->id}] to Facebook Page.", [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error("Exception when posting article [{$article->id}] to Facebook Page.", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
