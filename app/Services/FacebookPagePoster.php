<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPagePoster
{
    /**
     * โพสต์บทความหวยลง Facebook Page
     * @param Article $article ข้อมูลบทความ
     * @param string|null $manualImageUrl URL รูปภาพพรีเมียมที่วาดจากเบราว์เซอร์ (ถ้ามี)
     */
    public function postArticle(Article $article, ?string $manualImageUrl = null): array
    {
        $pageId = config('services.facebook.page_id');
        $accessToken = config('services.facebook.page_access_token');

        if (empty($pageId) || empty($accessToken)) {
            Log::error("FB Post: Missing config - ID: " . ($pageId ? 'OK' : 'MISSING') . ", Token: " . ($accessToken ? 'OK' : 'MISSING'));
            return ['success' => false, 'error' => 'Missing Facebook Page ID or Access Token in configuration.'];
        }

        $articleUrl = route('articles.show', ['slug' => $article->slug]);
        
        $template = config('services.lottery.fb_template');
        
        $placeholders = [
            '{title}' => $article->title,
            '{excerpt}' => $article->excerpt ? strip_tags($article->excerpt) : '',
            '{article_url}' => $articleUrl,
        ];

        // If we want to support prize placeholders in FB too, we'd need to load the lottery result.
        // For now, let's stick to article-based placeholders as defined in the plan.
        
        $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);

        // --- ส่วนการจัดการรูปภาพสำหรับโพสต์ Facebook ---
        $imagePath = null;
        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        // Check provided manual image URL first, then fall back to landscape path
        $relPath = $manualImageUrl ?: $article->cover_image_landscape_path;

        if ($relPath) {
            // ระบบใหม่ใช้ Storage เป็นหลัก ดังนั้นเราตรวจสอบความมีอยู่ผ่าน Disk โดยตรง
            if ($disk->exists($relPath)) {
                $imagePath = $disk->path($relPath);
            }
        }

        Log::info("FB Post Debug: Article {$article->id} - Final Image Path: " . ($imagePath ?: 'NOT_FOUND'));

        if (!$imagePath || str_ends_with(strtolower($imagePath), '.svg')) {
            Log::error("FB Post: Aborting - Image file not found or is still SVG for article [{$article->id}].");
            return [
                'success' => false, 
                'error' => 'ไม่พบไฟล์รูปภาพที่ถูกต้องบนเซิร์ฟเวอร์ (ต้องการ .png หรือ .jpg) ระบบได้ระงับการโพสต์เพื่อป้องกันความผิดพลาดครับ'
            ];
        }

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

            $fbError = $response->json('error')['message'] ?? 'Unknown FB API Error';
            Log::error("FB Photo Upload API error: " . $fbError);
            return ['success' => false, 'error' => 'Facebook API Error: ' . $fbError];
        } catch (\Throwable $e) {
            Log::error("FB Photo Upload exception: " . $e->getMessage());
            return ['success' => false, 'error' => 'System Exception: ' . $e->getMessage()];
        }
    }
}
