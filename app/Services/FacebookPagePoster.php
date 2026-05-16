<?php

namespace App\Services;

use App\Models\Article;
use App\Models\LotteryResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPagePoster
{
    private const LOTTERY_SLUG_PATTERN = '/thai-goverment-lottery-\d{6}(first|second)/';

    private const THAI_MONTHS = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
    ];

    /**
     * โพสต์บทความลง Facebook Page เป็น link post เพื่อให้ขึ้นบน feed ของเพจ
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
        Log::info("FB Post Debug: Article {$article->id} - Type: " . ($isLottery ? 'lottery' : 'regular') . " - URL: {$articleUrl}");

        $feedUrl = "https://graph.facebook.com/v19.0/{$pageId}/feed";

        try {
            $this->refreshLinkPreview($articleUrl, $accessToken);

            $feedResponse = Http::asForm()->post($feedUrl, [
                'message' => $this->normalizeMessageForLinkPost($message, $articleUrl),
                'link' => $articleUrl,
                'access_token' => $accessToken,
            ]);

            if ($feedResponse->successful()) {
                Log::info("Successfully posted link story to Facebook Page for article [{$article->id}]. FB Post ID: " . $feedResponse->json('id'));
                return ['success' => true, 'id' => $feedResponse->json('id')];
            }

            $fbError = $feedResponse->json('error')['message'] ?? 'Unknown FB API Error';
            Log::error("FB Feed Publish API error: " . $fbError);
            return ['success' => false, 'error' => 'Facebook API Error: ' . $fbError];
        } catch (\Throwable $e) {
            Log::error("FB Post exception: " . $e->getMessage());
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
        $excerpt = $article->excerpt ? strip_tags($article->excerpt) : '';

        return str_replace(
            ['{title}', '{excerpt}', '{article_url}'],
            [$article->title, $excerpt, $articleUrl],
            $template
        );
    }

    private function refreshLinkPreview(string $articleUrl, string $accessToken): void
    {
        $response = Http::asForm()->post('https://graph.facebook.com', [
            'id' => $articleUrl,
            'scrape' => 'true',
            'access_token' => $accessToken,
        ]);

        if (! $response->successful()) {
            $fbError = $response->json('error')['message'] ?? 'Unknown FB scrape error';
            Log::warning("FB Scrape warning for [{$articleUrl}]: " . $fbError);
        }
    }

    private function normalizeMessageForLinkPost(string $message, string $articleUrl): string
    {
        $normalized = trim(str_replace($articleUrl, '', $message));
        $normalized = preg_replace("/\n{3,}/", "\n\n", $normalized);

        return trim((string) $normalized);
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
