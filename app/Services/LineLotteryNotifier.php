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
    ) {
    }

    public function sendCompleted(LotteryResult $result): ?LineNotificationLog
    {
        if (! $result->relationLoaded('prizes')) {
            $result->load('prizes');
        }

        return $this->lineNotifier->queueText(
            eventType: 'lottery_completed',
            message: $this->buildMessage($result),
            notifiable: $result,
            destinationKey: 'lottery',
        );
    }

    private function buildMessage(LotteryResult $result): string
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
