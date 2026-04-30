<?php

namespace App\Services;

use App\Models\Article;
use App\Models\LineNotificationLog;
use App\Models\LotteryResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LineLotteryNotifier
{
    public function __construct(
        private readonly LineNotifier $lineNotifier,
        private readonly LineLotteryImageService $lineLotteryImageService,
    ) {
    }

    /**
     * ส่งข้อความแจ้งเตือนหวยออกเข้ากลุ่ม LINE
     * @param LotteryResult $result ข้อมูลผลหวย
     * @param string|null $manualImageUrl URL รูปภาพที่วาดเสร็จจากเบราว์เซอร์ (ถ้ามี) 
     *                                   หากส่งค่านี้มา ระบบจะใช้รูปนี้แทนรูปออโต้ทันที
     */
    public function sendCompleted(LotteryResult $result, ?string $manualImageUrl = null): ?LineNotificationLog
    {
        if (! $result->relationLoaded('prizes')) {
            $result->load('prizes');
        }

        return $this->lineNotifier->queueMessages(
            eventType: 'lottery_completed',
            messages: $this->buildMessages($result, $manualImageUrl),
            notifiable: $result,
            destinationKey: 'lottery',
        );
    }

    /**
     * แจ้งเตือนแอดมินพร้อมลิงก์ทางลัด (One-Click Shortcut)
     */
    public function notifyAdminArticleReady(Article $article): ?LineNotificationLog
    {
        $drawDate = $article->lottery_draw_date ? $article->lottery_draw_date->format('d/m/Y') : '-';
        
        // สร้างลิงก์พิเศษพ่วงคำสั่ง Auto-Trigger
        $adminBaseUrl = route('admin.articles');
        $fbUrl = "{$adminBaseUrl}?auto_share=fb&article_id={$article->id}";
        $lineUrl = "{$adminBaseUrl}?auto_share=line&article_id={$article->id}";
        $viewUrl = route('articles.show', ['slug' => $article->slug]);

        $msg = "📝 [ระบบบทความหวยอัตโนมัติ]\n";
        $msg .= "บทความงวดวันที่ {$drawDate} เผยแพร่แล้ว!\n\n";
        $msg .= "เชิญแอดมินเลือกดำเนินการ:\n";
        $msg .= "🔵 แชร์ FB พรีเมียม: {$fbUrl}\n";
        $msg .= "🟢 บรอดแคสต์ LINE: {$lineUrl}\n\n";
        $msg .= "🌐 ดูหน้าเว็บ: {$viewUrl}";

        return $this->lineNotifier->queueText(
            eventType: 'admin_article_ready',
            message: $msg,
            notifiable: $article,
            destinationKey: 'admin',
        );
    }

    public function sendUnavailableAfterRetryWindow(LotteryResult $result, Carbon $scheduledDrawDate, Carbon $checkedAt): ?LineNotificationLog
    {
        return $this->lineNotifier->queueText(
            eventType: 'lottery_unavailable_after_retry',
            message: $this->buildUnavailableAfterRetryMessage($scheduledDrawDate, $checkedAt),
            notifiable: $result,
            destinationKey: 'lottery',
        );
    }

    private function buildMessages(LotteryResult $result, ?string $manualImageUrl = null): array
    {
        $messages = [
            [
                'type' => 'text',
                'text' => $this->buildTextMessage($result),
            ],
        ];

        // ตรรกะสำคัญ: ถ้ามีการส่ง manualImageUrl (รูปพรีเมียมจากเบราว์เซอร์) มา ให้ใช้ค่านั้นเลย
        // ถ้าไม่มี ถึงจะไปเรียกใช้ระบบ buildLineImageUrl ตามปกติครับ
        $imageUrl = $manualImageUrl ?? $this->lineLotteryImageService->buildLineImageUrl($result);

        if ($imageUrl !== null) {
            $messages[] = [
                'type' => 'image',
                'originalContentUrl' => $imageUrl,
                'previewImageUrl' => $imageUrl,
            ];
        }

        return $messages;
    }

    private function buildTextMessage(LotteryResult $result): string
    {
        /** @var Collection<int, mixed> $prizes */
        $prizes = $result->prizes;
        $drawDate = $result->source_draw_date ?? $result->draw_date;
        $drawDateText = $drawDate instanceof Carbon
            ? $drawDate->copy()->timezone('Asia/Bangkok')->format('d/m/Y')
            : ($result->source_draw_date_text ?: '-');
        $firstPrize = $this->pickFirstPrizeNumber($prizes, 'รางวัลที่ 1', '-');
        $frontThree = implode(' ', $this->pickPrizeNumbers($prizes, 'เลขหน้า 3 ตัว', 2));
        $backThree = implode(' ', $this->pickPrizeNumbers($prizes, 'เลขท้าย 3 ตัว', 2));
        $lastTwo = $this->pickFirstPrizeNumber($prizes, 'เลขท้าย 2 ตัว', '-');
        $nearFirst = implode(' ', $this->pickPrizeNumbers($prizes, 'ข้างเคียง', 2));

        return implode("\n", array_filter([
            'ผลหวยออกแล้ว',
            'งวดวันที่: ' . $drawDateText,
            'รางวัลที่ 1: ' . $firstPrize,
            'เลขหน้า 3 ตัว: ' . ($frontThree !== '' ? $frontThree : '-'),
            'เลขท้าย 3 ตัว: ' . ($backThree !== '' ? $backThree : '-'),
            'เลขท้าย 2 ตัว: ' . $lastTwo,
            $nearFirst !== '' ? 'ข้างเคียงรางวัลที่ 1: ' . $nearFirst : null,
        ]));
    }

    private function buildUnavailableAfterRetryMessage(Carbon $scheduledDrawDate, Carbon $checkedAt): string
    {
        return implode("\n", [
            'ยังไม่พบข้อมูลผลสลากกินแบ่งรัฐบาล',
            'งวดวันที่: ' . $scheduledDrawDate->copy()->timezone('Asia/Bangkok')->format('d/m/Y'),
            'ตรวจสอบล่าสุด: ' . $checkedAt->copy()->timezone('Asia/Bangkok')->format('d/m/Y H:i') . ' น.',
            'ระบบติดตามถึงสิ้นสุดรอบวันถัดไปแล้ว แต่ยังไม่มีข้อมูลผลรางวัลจากต้นทาง',
        ]);
    }

    private function pickFirstPrizeNumber(Collection $prizes, string $nameNeedle, string $fallback): string
    {
        $prize = $prizes
            ->first(fn ($item) => str_contains((string) data_get($item, 'prize_name', ''), $nameNeedle));

        $number = trim((string) data_get($prize, 'prize_number', ''));

        return $number !== '' ? $number : $fallback;
    }

    /**
     * @return array<int, string>
     */
    private function pickPrizeNumbers(Collection $prizes, string $nameNeedle, int $limit): array
    {
        return $prizes
            ->filter(fn ($item) => str_contains((string) data_get($item, 'prize_name', ''), $nameNeedle))
            ->pluck('prize_number')
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->take($limit)
            ->map(fn ($value) => trim((string) $value))
            ->values()
            ->all();
    }
}
