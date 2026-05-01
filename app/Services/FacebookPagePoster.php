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

        // กรณีที่ 1: มีรูปพรีเมียมจากเบราว์เซอร์ (Manual)
        if (!empty($manualImageUrl)) {
            // ดึงชื่อไฟล์จาก URL (เช่น temp_render_123.png)
            $tempFilename = basename($manualImageUrl);
            $localTempPath = \Illuminate\Support\Facades\Storage::disk('public')->path('temp_lottery/' . $tempFilename);
            
            if (file_exists($localTempPath) && is_readable($localTempPath)) {
                $imagePath = $localTempPath;
            }
        }

        // กรณีที่ 2: ใช้รูปปกติจากระบบ (ถ้าไม่มีรูปพรีเมียมหรือรูปพรีเมียมหาไฟล์ไม่เจอ)
        if (!$imagePath && !empty($article->cover_image_landscape_path)) {
            $relPath = $article->cover_image_landscape_path;
            
            // 1. ตรวจสอบไฟล์: ถ้าเป็นไฟล์ SVG ให้พยายามหาไฟล์ PNG ชื่อเดียวกันก่อน
            // เพราะ Facebook API ไม่รองรับการโพสต์รูปภาพประเภท SVG
            if (str_ends_with(strtolower($relPath), '.svg')) {
                $pngRelPath = str_replace('.svg', '.png', $relPath);
                $pngPath = \Illuminate\Support\Facades\Storage::disk('public')->path($pngRelPath);
                if (file_exists($pngPath) && is_readable($pngPath)) {
                    $relPath = $pngRelPath;
                }
            }

            // 2. ค้นหาที่อยู่ไฟล์จริงบนเซิร์ฟเวอร์ (Absolute Path)
            $path1 = \Illuminate\Support\Facades\Storage::disk('public')->path($relPath);
            if (file_exists($path1) && is_readable($path1)) {
                $imagePath = $path1;
            } 
            else {
                // ถ้าหาที่จุดแรกไม่เจอ ให้ลองหาที่โฟลเดอร์ public/storage
                $path2 = public_path('storage/' . $relPath);
                if (file_exists($path2) && is_readable($path2)) {
                    $imagePath = $path2;
                }
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
