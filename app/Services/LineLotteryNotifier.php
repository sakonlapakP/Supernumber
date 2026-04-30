<?php

namespace App\Services;

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

        $imageUrl = $manualImageUrl;
        
        if ($imageUrl === null) {
            $article = \App\Models\Article::where('draw_date', $result->draw_date)->first();
            if ($article && !empty($article->cover_image_square_path)) {
                $imageUrl = asset('storage/' . $article->cover_image_square_path);
                // Ensure we use PNG version
                $imageUrl = str_replace('.svg', '.png', $imageUrl);
            } else {
                $imageUrl = $this->lineLotteryImageService->buildLineImageUrl($result);
            }
        }

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
