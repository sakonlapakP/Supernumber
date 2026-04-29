<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\LineNotificationLog;
use App\Models\LotteryResult;
use App\Services\LineLotteryNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class FetchLatestLotteryCommand extends Command
{
    protected $signature = 'lottery:fetch-latest
        {--force : Ignore schedule window restrictions}
        {--draw-date= : Target draw date in Y-m-d (defaults to today in Thailand)}';

    protected $description = 'Fetch latest GLO lottery result and persist it to database';

    private const API_URL = 'https://www.glo.or.th/api/lottery/getLatestLottery';
    private const TZ = 'Asia/Bangkok';
    private const PLAYWRIGHT_RENDER_SCRIPT = 'scripts/render_lottery_cover.mjs';
    private const SQUARE_RENDER_SELECTOR = '.article-lottery-poster__frame';
    private const LANDSCAPE_RENDER_SELECTOR = '.landscape-poster';

    public function handle(): int
    {
        $now = Carbon::now(self::TZ);
        $targetDate = $this->resolveTargetDate($now);

        if ($targetDate === null) {
            $this->error('Invalid --draw-date format. Use Y-m-d.');
            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->isInScheduleWindow($now)) {
            $this->line(sprintf('Skipped: outside schedule window (%s).', $now->format('Y-m-d H:i:s T')));
            return self::SUCCESS;
        }

        $existing = LotteryResult::query()
            ->with('prizes')
            ->whereDate('draw_date', $targetDate->toDateString())
            ->first();

        if (!$this->option('force') && $existing?->is_complete) {
            $this->line(sprintf('Skipped: draw %s is already complete.', $targetDate->toDateString()));
            return self::SUCCESS;
        }

        try {
            $response = Http::timeout(12)
                ->acceptJson()
                ->asJson()
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::API_URL, []);
        } catch (\Throwable $exception) {
            $this->warn('GLO API request failed: '.$exception->getMessage());
            $result = $this->touchResultWithoutPrizes($targetDate, $existing, $now);
            $this->syncLotteryArticleCover($result, $now);
            return self::SUCCESS;
        }

        if (!$response->ok()) {
            $this->warn('GLO API returned HTTP '.$response->status());
            $result = $this->touchResultWithoutPrizes($targetDate, $existing, $now);
            $this->syncLotteryArticleCover($result, $now);
            return self::SUCCESS;
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            $this->warn('GLO API payload is not valid JSON object/array.');
            $result = $this->touchResultWithoutPrizes($targetDate, $existing, $now);
            $this->syncLotteryArticleCover($result, $now);
            return self::SUCCESS;
        }

        [$drawDateText, $sourceDrawDate] = $this->extractDrawDate($payload);
        $storageDate = ($sourceDrawDate ?? $targetDate)->copy()->startOfDay();
        $prizes = $this->extractPrizes($payload);
        $storedResult = LotteryResult::query()
            ->with('prizes')
            ->whereDate('draw_date', $storageDate->toDateString())
            ->first();
        
        $wasAlreadyComplete = $storedResult?->is_complete ?? false;
        $shouldReplacePrizes = $this->shouldReplacePrizes($prizes, $storedResult?->prizes);
        $effectivePrizes = $shouldReplacePrizes ? $prizes : $this->serializePrizeRows($storedResult?->prizes);
        $effectiveSourceDrawDate = $sourceDrawDate ?? $storedResult?->source_draw_date;
        $isComplete = $this->isCompletePayload($effectivePrizes, $effectiveSourceDrawDate, $storageDate);

        $savedResult = null;
        DB::transaction(function () use ($storageDate, $sourceDrawDate, $drawDateText, $isComplete, $now, $payload, $prizes, $storedResult, $shouldReplacePrizes, &$savedResult): void {
            $savedResult = LotteryResult::query()->updateOrCreate(
                ['draw_date' => $storageDate->toDateString()],
                [
                    'source_draw_date' => $sourceDrawDate?->toDateString() ?? $storedResult?->source_draw_date?->toDateString(),
                    'source_draw_date_text' => $drawDateText ?? $storedResult?->source_draw_date_text,
                    'is_complete' => $isComplete,
                    'fetched_at' => $now->copy()->utc()->toDateTimeString(),
                    'source_payload' => $payload !== [] ? $payload : $storedResult?->source_payload,
                ]
            );

            if ($shouldReplacePrizes && !empty($prizes)) {
                $savedResult->prizes()->delete();
                foreach ($prizes as $index => $prize) {
                    $savedResult->prizes()->create([
                        'position' => $index,
                        'prize_name' => $prize['name'],
                        'prize_number' => $prize['number'],
                    ]);
                }
            }
        });

        if ($savedResult instanceof LotteryResult) {
            $savedResult->load('prizes');
            $this->syncLotteryArticleCover($savedResult, $now);
            $this->notifyLineWhenCompleted($savedResult, $wasAlreadyComplete);
        }

        $this->info(sprintf('Saved draw %s (%d prizes, complete=%s).', $storageDate->toDateString(), count($prizes), $isComplete ? 'yes' : 'no'));
        return self::SUCCESS;
    }

    private function resolveTargetDate(Carbon $now): ?Carbon
    {
        $drawDateOption = trim((string) $this->option('draw-date'));
        if ($drawDateOption === '') return $this->resolveScheduledTargetDate($now);
        try {
            return Carbon::createFromFormat('Y-m-d', $drawDateOption, self::TZ)->startOfDay();
        } catch (\Throwable $e) { return null; }
    }

    private function resolveScheduledTargetDate(Carbon $now): Carbon
    {
        $today = $now->copy()->startOfDay();
        if (in_array($now->day, [2, 17], true)) {
            $previousDrawDate = $today->copy()->subDay();
            if ($this->shouldRetryOnNextDay($previousDrawDate)) return $previousDrawDate;
        }
        return $today;
    }

    private function isInScheduleWindow(Carbon $now): bool
    {
        if (!$this->isEligibleScheduleDate($now)) return false;
        $windowStart = $now->copy()->setTime(15, 45);
        $windowEnd = $now->copy()->setTime(16, 20);
        return $now->greaterThanOrEqualTo($windowStart) && $now->lessThanOrEqualTo($windowEnd);
    }

    private function isEligibleScheduleDate(Carbon $now): bool
    {
        if (in_array($now->day, [1, 16], true)) return true;
        if (!in_array($now->day, [2, 17], true)) return false;
        return $this->shouldRetryOnNextDay($now->copy()->subDay()->startOfDay());
    }

    private function shouldRetryOnNextDay(Carbon $previousDrawDate): bool
    {
        $previousResult = LotteryResult::query()->whereDate('draw_date', $previousDrawDate->toDateString())->first();
        if ($previousResult === null) return true;
        return !$previousResult->is_complete;
    }

    private function touchResultWithoutPrizes(Carbon $targetDate, ?LotteryResult $existing, Carbon $now): LotteryResult
    {
        return LotteryResult::query()->updateOrCreate(
            ['draw_date' => $targetDate->toDateString()],
            [
                'source_draw_date' => $existing?->source_draw_date?->toDateString(),
                'source_draw_date_text' => $existing?->source_draw_date_text,
                'is_complete' => $existing?->is_complete ?? false,
                'fetched_at' => $now->copy()->utc()->toDateTimeString(),
                'source_payload' => $existing?->source_payload,
            ]
        );
    }

    private function notifyLineWhenCompleted(LotteryResult $result, bool $wasAlreadyComplete): void
    {
        if ($wasAlreadyComplete || !$result->is_complete) return;
        try {
            app(LineLotteryNotifier::class)->sendCompleted($result);
        } catch (\Throwable $exception) {
            Log::warning('Lottery LINE notification failed: '.$exception->getMessage());
        }
    }

    private function syncLotteryArticleCover(LotteryResult $result, Carbon $now): void
    {
        if (!$result->relationLoaded('prizes')) $result->load('prizes');

        $drawDate = $result->source_draw_date ?? $result->draw_date ?? $now;
        $year = $drawDate->format('Y');
        $month = $drawDate->format('m');
        $round = (int) $drawDate->format('j') <= 15 ? 'first' : 'second';
        $articleName = sprintf('thai-government-lottery-%s%s%s', $year, $month, $round);
        $articleDir = sprintf('articles/%s/%s', $year, $articleName);
        
        $squareFilename = sprintf('%s/%s.png', $articleDir, $articleName);
        $landscapeFilename = sprintf('%s/%s_cover.png', $articleDir, $articleName);
        $squareSvgFilename = sprintf('%s/%s.svg', $articleDir, $articleName);

        if (!Storage::disk('public')->exists($articleDir)) {
            Storage::disk('public')->makeDirectory($articleDir);
        }
        
        @chmod(Storage::disk('public')->path('articles'), 0755);
        @chmod(Storage::disk('public')->path('articles/' . $year), 0755);
        @chmod(Storage::disk('public')->path($articleDir), 0755);

        $thaiDateLabel = $this->toThaiDateLabel($drawDate->copy());
        $article = Article::query()->firstOrNew(['slug' => $articleName]);

        if (!$article->exists) {
            $article->is_published = true;
            $article->published_at = now();
        }

        $article->title = sprintf('ตรวจหวยรัฐบาล งวดวันที่ %s ผลสลากกินแบ่งรัฐบาล', $thaiDateLabel);
        $article->excerpt = sprintf('สรุปผลสลากกินแบ่งรัฐบาล งวดประจำวันที่ %s', $thaiDateLabel);
        $article->content = $this->buildLotteryArticleContent($result, $drawDate->copy(), $now->copy());
        
        // Render Cover
        $svgContents = $this->buildLotteryCoverSvg($result);
        Storage::disk('public')->put($squareSvgFilename, $svgContents);
        @chmod(Storage::disk('public')->path($squareSvgFilename), 0644);

        // Convert to PNG for sharing
        if ($this->convertSvgToPng(Storage::disk('public')->path($squareSvgFilename), Storage::disk('public')->path($squareFilename))) {
            @chmod(Storage::disk('public')->path($squareFilename), 0644);
            Storage::disk('public')->copy($squareFilename, $landscapeFilename);
            @chmod(Storage::disk('public')->path($landscapeFilename), 0644);
            
            $article->cover_image_square_path = $squareFilename;
            $article->cover_image_landscape_path = $landscapeFilename;
            $article->cover_image_path = $squareFilename;
        } else {
            $article->cover_image_square_path = $squareSvgFilename;
            $article->cover_image_landscape_path = $squareSvgFilename;
            $article->cover_image_path = $squareSvgFilename;
        }

        $article->save();
        $this->info("Synced lottery article: {$articleName}");
    }

    private function convertSvgToPng(string $svgPath, string $pngPath): bool
    {
        if (class_exists(\Imagick::class)) {
            try {
                $imagick = new \Imagick();
                $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
                $imagick->readImageBlob(file_get_contents($svgPath));
                $imagick->setImageFormat("png32");
                $imagick->writeImage($pngPath);
                return true;
            } catch (\Throwable $e) {
                Log::warning('Imagick conversion failed: '.$e->getMessage());
            }
        }

        try {
            $process = new Process(['convert', $svgPath, $pngPath]);
            $process->run();
            return $process->isSuccessful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function buildLotteryCoverSvg(LotteryResult $result): string
    {
        $drawDate = $result->source_draw_date ?? $result->draw_date;
        $thaiDate = $drawDate ? $this->toThaiDateLabel($drawDate->copy()) : '-';
        $prizes = $result->prizes;

        $p1 = $this->pickFirstPrizeNumber($prizes, 'รางวัลที่ 1', '......');
        $l2 = $this->pickFirstPrizeNumber($prizes, 'เลขท้าย 2 ตัว', '..');
        $f3_arr = $this->pickPrizeNumbers($prizes, 'เลขหน้า 3 ตัว', 2);
        $l3_arr = $this->pickPrizeNumbers($prizes, 'เลขท้าย 3 ตัว', 2);
        
        $f3 = count($f3_arr) > 0 ? implode(' ', $f3_arr) : '... ...';
        $l3 = count($l3_arr) > 0 ? implode(' ', $l3_arr) : '... ...';

        return <<<SVG
<svg width="1000" height="1000" viewBox="0 0 1000 1000" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#D4AF37;stop-opacity:1" />
            <stop offset="50%" style="stop-color:#F9E27D;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#B8860B;stop-opacity:1" />
        </linearGradient>
    </defs>
    <rect width="1000" height="1000" fill="#120907" />
    <rect x="20" y="20" width="960" height="960" rx="40" fill="url(#goldGrad)" opacity="0.1" />
    <rect x="50" y="50" width="900" height="180" rx="30" fill="url(#goldGrad)" />
    <text x="500" y="115" font-family="sans-serif" font-size="42" font-weight="bold" fill="#1e293b" text-anchor="middle">ผลสลากกินแบ่งรัฐบาล</text>
    <text x="500" y="185" font-family="sans-serif" font-size="54" font-weight="bold" fill="#1e293b" text-anchor="middle">งวดวันที่ $thaiDate</text>
    <rect x="100" y="270" width="800" height="220" rx="25" fill="#ffffff" />
    <text x="500" y="330" font-family="sans-serif" font-size="36" font-weight="bold" fill="#64748b" text-anchor="middle">รางวัลที่ 1</text>
    <text x="500" y="440" font-family="sans-serif" font-size="120" font-weight="bold" fill="#b45309" text-anchor="middle" letter-spacing="10">$p1</text>
    <rect x="100" y="520" width="385" height="180" rx="20" fill="#ffffff" />
    <text x="292" y="570" font-family="sans-serif" font-size="30" font-weight="bold" fill="#64748b" text-anchor="middle">เลขหน้า 3 ตัว</text>
    <text x="292" y="655" font-family="sans-serif" font-size="65" font-weight="bold" fill="#1e293b" text-anchor="middle">$f3</text>
    <rect x="515" y="520" width="385" height="180" rx="20" fill="#ffffff" />
    <text x="707" y="570" font-family="sans-serif" font-size="30" font-weight="bold" fill="#64748b" text-anchor="middle">เลขท้าย 3 ตัว</text>
    <text x="707" y="655" font-family="sans-serif" font-size="65" font-weight="bold" fill="#1e293b" text-anchor="middle">$l3</text>
    <rect x="100" y="730" width="800" height="180" rx="25" fill="#1e293b" />
    <text x="300" y="835" font-family="sans-serif" font-size="40" font-weight="bold" fill="#fbbf24" text-anchor="middle">เลขท้าย 2 ตัว</text>
    <text x="650" y="855" font-family="sans-serif" font-size="140" font-weight="bold" fill="#ffffff" text-anchor="middle">$l2</text>
    <text x="500" y="965" font-family="sans-serif" font-size="24" fill="#94a3b8" text-anchor="middle">ตรวจผลหวยแม่นยำได้ที่ www.supernumber.co.th</text>
</svg>
SVG;
    }

    private function buildLotteryArticleContent(LotteryResult $result, Carbon $drawDate, Carbon $now): string
    {
        $prizes = $result->prizes;
        $thaiDate = $this->toThaiDateLabel($drawDate->copy());
        $updatedAt = $now->timezone(self::TZ)->format('d/m/Y H:i');
        $firstPrize = $this->pickFirstPrizeNumber($prizes, 'รางวัลที่ 1', '-');
        $frontThree = implode(' ', $this->pickPrizeNumbers($prizes, 'เลขหน้า 3 ตัว', 2));
        $backThree = implode(' ', $this->pickPrizeNumbers($prizes, 'เลขท้าย 3 ตัว', 2));
        $lastTwo = $this->pickFirstPrizeNumber($prizes, 'เลขท้าย 2 ตัว', '-');
        $statusLabel = $result->is_complete ? 'ผลรางวัลออกครบแล้ว' : 'ผลรางวัลยังอยู่ระหว่างอัปเดต';

        return "
<p>ตรวจผลสลากกินแบ่งรัฐบาล งวดประจำวันที่ {$thaiDate} {$statusLabel}</p>
<h2>สรุปผลรางวัล</h2>
<ul>
  <li>รางวัลที่ 1: <strong>{$firstPrize}</strong></li>
  <li>เลขหน้า 3 ตัว: <strong>{$frontThree}</strong></li>
  <li>เลขท้าย 3 ตัว: <strong>{$backThree}</strong></li>
  <li>เลขท้าย 2 ตัว: <strong>{$lastTwo}</strong></li>
</ul>
<p>อัปเดตล่าสุด {$updatedAt} น. บทความนี้จัดทำอัตโนมัติจากข้อมูลผลรางวัลที่ระบบดึงล่าสุด เพื่อให้อ่านและแชร์ต่อได้สะดวก</p>";
    }

    private function pickFirstPrizeNumber(Collection $prizes, string $nameNeedle, string $fallback): string
    {
        $prize = $prizes->first(fn ($item) => str_contains((string) data_get($item, 'prize_name', ''), $nameNeedle));
        $number = trim((string) data_get($prize, 'prize_number', ''));
        return $number !== '' ? $number : $fallback;
    }

    private function pickPrizeNumbers(Collection $prizes, string $nameNeedle, int $limit): array
    {
        return $prizes->filter(fn ($item) => str_contains((string) data_get($item, 'prize_name', ''), $nameNeedle))
            ->pluck('prize_number')->map(fn ($n) => trim((string) $n))->filter()->take($limit)->values()->all();
    }

    private function toThaiDateLabel(Carbon $drawDate): string
    {
        $thaiMonths = [1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'];
        $month = $thaiMonths[(int) $drawDate->format('n')] ?? $drawDate->format('m');
        $year = (int) $drawDate->format('Y') + 543;
        return $drawDate->format('j').' '.$month.' '.$year;
    }

    private function extractDrawDate(array $payload): array
    {
        $candidate = data_get($payload, 'response.date') ?? data_get($payload, 'date');
        if ($candidate) {
            if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $candidate, $m)) {
                return [$candidate, Carbon::create((int)$m[3]-543, (int)$m[2], (int)$m[1], 0, 0, 0, self::TZ)];
            }
        }
        return [null, null];
    }

    private function extractPrizes(array $payload): array
    {
        $rows = [];
        $data = data_get($payload, 'response.data', []);
        $map = ['first' => 'รางวัลที่ 1', 'last3f' => 'เลขหน้า 3 ตัว', 'last3b' => 'เลขท้าย 3 ตัว', 'last2' => 'เลขท้าย 2 ตัว'];
        foreach ($map as $key => $name) {
            $numbers = data_get($data, "{$key}.number", []);
            if (is_scalar($numbers)) $numbers = [$numbers];
            foreach ($numbers as $num) $rows[] = ['name' => $name, 'number' => $num];
        }
        return $rows;
    }

    private function shouldReplacePrizes(array $incoming, ?Collection $existing): bool
    {
        return count($incoming) >= ($existing?->count() ?? 0);
    }

    private function serializePrizeRows(?Collection $prizes): array
    {
        return $prizes ? $prizes->map(fn($p) => ['name' => $p->prize_name, 'number' => $p->prize_number])->all() : [];
    }

    private function isCompletePayload(array $prizes, ?Carbon $source, Carbon $storage): bool
    {
        return count($prizes) >= 6;
    }
}
