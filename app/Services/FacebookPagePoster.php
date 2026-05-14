<?php

namespace App\Services;

use App\Models\Article;
use App\Models\LotteryResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FacebookPagePoster
{
    private const LOTTERY_SLUG_PATTERN = '/thai-goverment-lottery-\d{6}(first|second)/';

    private const THAI_MONTHS = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
    ];

    /**
     * โพสต์บทความลง Facebook Page
     * บทความหวย: ใช้ข้อความ lottery template + รูป square
     * บทความทั่วไป: ใช้ชื่อบทความ + URL + รูป landscape
     */
    public function postArticle(Article $article, ?string $manualImageUrl = null): array
    {
        $pageId = config('services.facebook.page_id');
        $accessToken = config('services.facebook.page_access_token');

        // ป้องกันการโพสต์ Facebook จากเครื่อง Local
        if (app()->isLocal() && !app()->runningUnitTests()) {
            Log::info("FB Post: Bypassing real post because environment is local development.");
            return ['success' => true, 'id' => 'local-test-id', 'testing' => true];
        }

        if (empty($pageId) || empty($accessToken)) {
            Log::error("FB Post: Missing config - ID: " . ($pageId ? 'OK' : 'MISSING') . ", Token: " . ($accessToken ? 'OK' : 'MISSING'));
            return ['success' => false, 'error' => 'Missing Facebook Page ID or Access Token in configuration.'];
        }

        $articleUrl = route('articles.show', ['slug' => $article->slug]);
        $isLottery = $this->isLotteryArticle($article);

        $message = $isLottery
            ? $this->buildLotteryMessage($article, $articleUrl)
            : $this->buildRegularMessage($article, $articleUrl);

        $imagePath = $this->resolveImagePath($article, $manualImageUrl, $isLottery);

        Log::info("FB Post Debug: Article {$article->id} - Type: " . ($isLottery ? 'lottery' : 'regular') . " - Image: " . ($imagePath ?: 'NOT_FOUND'));

        if (!$imagePath || str_ends_with(strtolower($imagePath), '.svg')) {
            Log::error("FB Post: Aborting - Image file not found or is still SVG for article [{$article->id}].");
            return [
                'success' => false,
                'error' => 'ไม่พบไฟล์รูปภาพที่ถูกต้องบนเซิร์ฟเวอร์ (ต้องการ .png หรือ .jpg) ระบบได้ระงับการโพสต์เพื่อป้องกันความผิดพลาดครับ',
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

    private function isLotteryArticle(Article $article): bool
    {
        return (bool) preg_match(self::LOTTERY_SLUG_PATTERN, $article->slug);
    }

    private function buildLotteryMessage(Article $article, string $articleUrl): string
    {
        $thaiDate = $this->resolveLotteryThaiDate($article);
        $template = config('services.lottery.fb_template_lottery');

        return str_replace(
            ['{thai_draw_date}', '{article_url}'],
            [$thaiDate, $articleUrl],
            $template
        );
    }

    private function buildRegularMessage(Article $article, string $articleUrl): string
    {
        $template = config('services.lottery.fb_template_regular');

        return str_replace(
            ['{title}', '{article_url}'],
            [$article->title, $articleUrl],
            $template
        );
    }

    private function resolveImagePath(Article $article, ?string $manualImageUrl, bool $isLottery): ?string
    {
        $disk = Storage::disk('public');

        // manual_image_url จาก browser render มาก่อนเสมอ
        if ($manualImageUrl) {
            $relPath = $manualImageUrl;
            if ($disk->exists($relPath)) {
                return $disk->path($relPath);
            }
        }

        // บทความหวย → ใช้ square image, บทความทั่วไป → ใช้ landscape image
        $relPath = $isLottery
            ? ($article->cover_image_square_path ?: $article->cover_image_path)
            : ($article->cover_image_landscape_path ?: $article->cover_image_path);

        if ($relPath && $disk->exists($relPath)) {
            return $disk->path($relPath);
        }

        return null;
    }

    private function resolveLotteryThaiDate(Article $article): string
    {
        // ลองดึง draw_date จาก LotteryResult
        $date = null;

        if ($article->published_at) {
            $result = LotteryResult::where('draw_date', $article->published_at->toDateString())->first();
            if ($result) {
                $date = $result->draw_date instanceof Carbon ? $result->draw_date : Carbon::parse($result->draw_date);
            }
        }

        if (!$date && preg_match('/(\d{4})(\d{2})/', $article->slug, $matches)) {
            $result = LotteryResult::where('draw_date', 'like', $matches[1] . '-' . $matches[2] . '%')
                ->orderBy('draw_date', 'desc')
                ->first();
            if ($result) {
                $date = $result->draw_date instanceof Carbon ? $result->draw_date : Carbon::parse($result->draw_date);
            }
        }

        if (!$date && $article->published_at) {
            $date = $article->published_at;
        }

        if (!$date) {
            return '-';
        }

        $day = $date->format('j');
        $month = self::THAI_MONTHS[(int) $date->format('n')] ?? '';
        $year = (int) $date->format('Y') + 543;

        return "{$day} {$month} {$year}";
    }
}
