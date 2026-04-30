<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\LineNotificationLog;
use App\Models\LotteryResult;
use App\Services\LineLotteryNotifier;
use App\Services\FacebookPagePoster;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchLatestLotteryCommand extends Command
{
    protected $signature = 'lottery:fetch-latest
        {--force : Ignore schedule window restrictions}
        {--draw-date= : Target draw date in Y-m-d (defaults to today in Thailand)}';

    protected $description = 'Fetch latest GLO lottery result and persist it to database';

    private const API_URL = 'https://www.glo.or.th/api/lottery/getLatestLottery';
    private const TZ = 'Asia/Bangkok';

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
            $response = Http::timeout(12)->post(self::API_URL);
            if (!$response->successful()) {
                throw new \Exception("API Error: " . $response->status());
            }

            $payload = $response->json();
            [$sourceDateText, $sourceDate] = $this->extractDrawDate($payload);
            
            if ($sourceDate === null || $sourceDate->toDateString() !== $targetDate->toDateString()) {
                if (!$this->option('force')) {
                    $this->line('Skipped: API returned different date: ' . ($sourceDateText ?? 'null'));
                    return self::SUCCESS;
                }
            }

            $prizeRows = $this->extractPrizes($payload);
            $wasAlreadyComplete = (bool) ($existing?->is_complete);

            DB::beginTransaction();

            $result = $this->touchResultWithoutPrizes($targetDate, $existing, $now);
            $result->source_draw_date = $sourceDate;
            $result->source_draw_date_text = $sourceDateText;
            $result->source_payload = $payload;

            if ($this->shouldReplacePrizes($prizeRows, $existing?->prizes)) {
                $result->prizes()->delete();
                foreach ($prizeRows as $row) {
                    $result->prizes()->create([
                        'prize_name' => $row['name'],
                        'prize_number' => $row['number'],
                    ]);
                }
                $result->is_complete = $this->isCompletePayload($prizeRows, $sourceDate, $targetDate);
            }

            $result->save();
            
            $article = $this->syncLotteryArticleCover($result, $now, $wasAlreadyComplete);
            
            DB::commit();


            $this->info("Successfully processed lottery for " . $targetDate->toDateString());
            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("FetchLatestLotteryCommand Error: " . $e->getMessage());
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function resolveTargetDate(Carbon $now): ?Carbon
    {
        $drawDateOption = (string) $this->option('draw-date');
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
                'fetched_at' => $now->copy()->utc()->toDateTimeString(),
            ]
        );
    }



    private function syncLotteryArticleCover(LotteryResult $result, Carbon $now, bool $wasAlreadyComplete = false): ?Article
    {
        if (!$result->relationLoaded('prizes')) $result->load('prizes');

        $drawDate = $result->source_draw_date ?? $result->draw_date ?? $now;
        $year = $drawDate->format('Y');
        $month = $drawDate->format('m');
        $round = (int) $drawDate->format('j') <= 15 ? 'first' : 'second';
        $articleName = sprintf('thai-government-lottery-%s%s%s', $year, $month, $round);
        $articleDir = sprintf('articles/%s/%s', $year, $articleName);
        
        $ts = time();
        $squareFilename = sprintf('%s/%s_%s.png', $articleDir, $articleName, $ts);
        $landscapeFilename = sprintf('%s/%s_cover_%s.png', $articleDir, $articleName, $ts);
        $squareSvgFilename = sprintf('%s/%s_%s.svg', $articleDir, $articleName, $ts);
        $landscapeSvgFilename = sprintf('%s/%s_cover_%s.svg', $articleDir, $articleName, $ts);

        if (!Storage::disk('public')->exists($articleDir)) {
            Storage::disk('public')->makeDirectory($articleDir);
        }

        $thaiDateLabel = $this->toThaiDateLabel($drawDate->copy());
        $article = Article::query()->firstOrNew(['slug' => $articleName]);

        $article->title = sprintf('ตรวจหวยรัฐบาล งวดประจำวันที่ %s ผลสลากกินแบ่งรัฐบาล', $thaiDateLabel);
        $article->excerpt = sprintf('สรุปผลสลากกินแบ่งรัฐบาล งวดประจำวันที่ %s', $thaiDateLabel);
        $article->content = $this->buildLotteryArticleContent($result, $drawDate->copy(), $now->copy());

        // Only generate images and publish when complete
        if ($result->is_complete) {
            if (!$article->is_published) {
                $article->is_published = true;
                $article->published_at = now();
            }

            $squareSvgContents = $this->buildLotteryCoverSvg($result);
            Storage::disk('public')->put($squareSvgFilename, $squareSvgContents);
            $article->cover_image_square_path = $squareSvgFilename;
            $article->cover_image_path = $squareSvgFilename;

            $landscapeSvgContents = $this->buildLotteryLandscapeSvg($result);
            Storage::disk('public')->put($landscapeSvgFilename, $landscapeSvgContents);
            $article->cover_image_landscape_path = $landscapeSvgFilename;
        } else {
            // Keep as draft if not complete yet
            if (!$article->exists) {
                $article->is_published = false;
            }
        }

        $article->save();
        $this->info("Synced lottery article: {$articleName}");

        if ($result->is_complete && !$wasAlreadyComplete) {
            try {
                app(LineLotteryNotifier::class)->notifyAdminArticleReady($article, $drawDate);
            } catch (\Throwable $e) {
                Log::error("Failed to send admin article notification: " . $e->getMessage());
            }
        }
        
        return $article;
    }

    private function convertSvgToPng(string $svgPath, string $pngPath): bool
    {
        return false; // Server-side conversion is disabled to favor browser-side premium rendering
    }

    private function buildLotteryLandscapeSvg(LotteryResult $result): string
    {
        $drawDate = $result->source_draw_date ?? $result->draw_date;
        $thaiDate = $drawDate ? $this->toThaiDateLabel($drawDate->copy()) : '-';

        $fontPath500 = public_path('fonts/Kanit-500.ttf');
        $fontBase64_500 = is_file($fontPath500) ? base64_encode((string)file_get_contents($fontPath500)) : '';
        $fontPath700 = public_path('fonts/Kanit-700.ttf');
        $fontBase64_700 = is_file($fontPath700) ? base64_encode((string)file_get_contents($fontPath700)) : '';

        return <<<SVG
<svg width="1200" height="630" viewBox="0 0 1200 630" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <style>
            @font-face { font-family: 'Kanit'; font-weight: 500; src: url(data:font/ttf;base64,{$fontBase64_500}); }
            @font-face { font-family: 'Kanit'; font-weight: 700; src: url(data:font/ttf;base64,{$fontBase64_700}); }
        </style>
        <radialGradient id="bgGrad" cx="50%" cy="15%" r="85%" fx="50%" fy="15%">
            <stop offset="0%" stop-color="#5a4227" />
            <stop offset="40%" stop-color="#261a11" />
            <stop offset="100%" stop-color="#100a06" />
        </radialGradient>
    </defs>
    <rect width="1200" height="630" fill="url(#bgGrad)" />
    <line x1="965" y1="0" x2="874" y2="450" stroke="#f5c76d" stroke-width="2" opacity="0.3" />
    <rect x="6" y="6" width="1188" height="618" fill="none" stroke="#c59d62" stroke-width="3" />
    <rect x="38" y="38" width="1124" height="554" fill="none" stroke="#ba8e4d" stroke-width="2" opacity="0.4" />
    
    <line x1="120" y1="124" x2="520" y2="124" stroke="#c59d62" stroke-width="2" opacity="0.8" />
    <line x1="680" y1="124" x2="1080" y2="124" stroke="#c59d62" stroke-width="2" opacity="0.8" />

    <rect x="548" y="72" width="104" height="104" fill="#2a1a10" stroke="#d7a64e" stroke-width="3" />
    <text x="600" y="154" font-family="'Times New Roman', serif" font-size="88" font-weight="900" fill="#f5c76d" text-anchor="middle">S</text>
    <text x="600" y="235" font-family="Kanit" font-size="36" font-weight="700" fill="#f7d58f" text-anchor="middle" letter-spacing="8">SUPERNUMBER</text>
    
    <text x="600" y="380" font-family="Kanit" font-size="95" font-weight="700" fill="#120907" stroke="#120907" stroke-width="12" stroke-linejoin="round" text-anchor="middle">สลากกินแบ่งรัฐบาลล่าสุด</text>
    <text x="600" y="380" font-family="Kanit" font-size="95" font-weight="700" fill="#ffffff" text-anchor="middle">สลากกินแบ่งรัฐบาลล่าสุด</text>
    
    <text x="600" y="470" font-family="Kanit" font-size="52" font-weight="700" fill="#120907" stroke="#120907" stroke-width="8" stroke-linejoin="round" text-anchor="middle">งวดประจำวันที่ $thaiDate</text>
    <text x="600" y="470" font-family="Kanit" font-size="52" font-weight="700" fill="#f7d58f" text-anchor="middle">งวดประจำวันที่ $thaiDate</text>
    
    <rect x="360" y="542" width="480" height="70" fill="#160c08" />
    <text x="600" y="592" font-family="Kanit" font-size="44" font-weight="700" fill="#ffffff" text-anchor="middle">supernumber.co.th</text>
</svg>
SVG;
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

        $f3_1 = $f3_arr[0] ?? '...';
        $f3_2 = $f3_arr[1] ?? '...';
        $l3_1 = $l3_arr[0] ?? '...';
        $l3_2 = $l3_arr[1] ?? '...';

        $fontPath500 = public_path('fonts/Kanit-500.ttf');
        $fontBase64_500 = is_file($fontPath500) ? base64_encode((string)file_get_contents($fontPath500)) : '';
        
        $fontPath700 = public_path('fonts/Kanit-700.ttf');
        $fontBase64_700 = is_file($fontPath700) ? base64_encode((string)file_get_contents($fontPath700)) : '';

        return <<<SVG
<svg width="1200" height="1200" viewBox="0 0 1200 1200" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <style>
            @font-face { font-family: 'Kanit'; font-weight: 500; src: url(data:font/ttf;base64,{$fontBase64_500}); }
            @font-face { font-family: 'Kanit'; font-weight: 700; src: url(data:font/ttf;base64,{$fontBase64_700}); }
        </style>
        <radialGradient id="bgGrad" cx="50%" cy="15%" r="85%" fx="50%" fy="15%">
            <stop offset="0%" stop-color="#5a4227" />
            <stop offset="40%" stop-color="#261a11" />
            <stop offset="100%" stop-color="#100a06" />
        </radialGradient>
    </defs>
    <rect width="1200" height="1200" fill="url(#bgGrad)" />
    <line x1="965" y1="0" x2="850" y2="380" stroke="#f5c76d" stroke-width="2" opacity="0.3" />
    <rect x="6" y="6" width="1188" height="1188" fill="none" stroke="#c59d62" stroke-width="3" />
    <rect x="38" y="38" width="1124" height="1124" fill="none" stroke="#ba8e4d" stroke-width="2" opacity="0.4" />
    
    <line x1="150" y1="124" x2="520" y2="124" stroke="#c59d62" stroke-width="2" opacity="0.8" />
    <line x1="680" y1="124" x2="1050" y2="124" stroke="#c59d62" stroke-width="2" opacity="0.8" />

    <rect x="554" y="78" width="92" height="92" fill="#2a1a10" stroke="#d7a64e" stroke-width="3" />
    <text x="600" y="152" font-family="'Times New Roman', serif" font-size="82" font-weight="900" fill="#f5c76d" text-anchor="middle">S</text>
    <text x="600" y="215" font-family="Kanit" font-size="28" font-weight="700" fill="#f7d58f" text-anchor="middle" letter-spacing="6">SUPERNUMBER</text>
    <text x="600" y="280" font-family="Kanit" font-size="64" font-weight="700" fill="#ffffff" text-anchor="middle">ผลสลากกินแบ่งรัฐบาล</text>
    <text x="600" y="335" font-family="Kanit" font-size="34" font-weight="500" fill="#f7d58f" text-anchor="middle">งวดประจำวันที่ $thaiDate</text>
    
    <rect x="75" y="370" width="1050" height="260" rx="12" fill="#fffaf0" />
    <text x="600" y="445" font-family="Kanit" font-size="55" font-weight="700" fill="#2a1a10" text-anchor="middle">รางวัลที่ 1</text>
    <text x="600" y="590" font-family="Kanit" font-size="180" font-weight="700" fill="#2a1a10" text-anchor="middle" letter-spacing="15">$p1</text>

    <text x="243" y="740" font-family="Kanit" font-size="38" font-weight="700" fill="#fffaf0" text-anchor="middle">เลขหน้า 3 ตัว</text>
    <text x="600" y="740" font-family="Kanit" font-size="38" font-weight="700" fill="#fffaf0" text-anchor="middle">เลขท้าย 3 ตัว</text>
    <text x="957" y="740" font-family="Kanit" font-size="38" font-weight="700" fill="#fffaf0" text-anchor="middle">เลขท้าย 2 ตัว</text>
    
    <rect x="75" y="760" width="336" height="130" rx="10" fill="#fffaf0" />
    <text x="243" y="860" font-family="Kanit" font-size="85" font-weight="700" fill="#2a1a10" text-anchor="middle">$f3_1</text>
    <rect x="75" y="910" width="336" height="130" rx="10" fill="#fffaf0" />
    <text x="243" y="1010" font-family="Kanit" font-size="85" font-weight="700" fill="#2a1a10" text-anchor="middle">$f3_2</text>
    
    <rect x="432" y="760" width="336" height="130" rx="10" fill="#fffaf0" />
    <text x="600" y="860" font-family="Kanit" font-size="85" font-weight="700" fill="#2a1a10" text-anchor="middle">$l3_1</text>
    <rect x="432" y="910" width="336" height="130" rx="10" fill="#fffaf0" />
    <text x="600" y="1010" font-family="Kanit" font-size="85" font-weight="700" fill="#2a1a10" text-anchor="middle">$l3_2</text>
    
    <rect x="789" y="760" width="336" height="280" rx="10" fill="#fffaf0" />
    <text x="957" y="960" font-family="Kanit" font-size="150" font-weight="700" fill="#2a1a10" text-anchor="middle">$l2</text>
    
    <text x="600" y="1115" font-family="Kanit" font-size="28" font-weight="700" fill="#fff6e4" text-anchor="middle">SUPERNUMBER.CO.TH</text>
    <text x="600" y="1155" font-family="Kanit" font-size="20" font-weight="500" fill="#f5e4c4" text-anchor="middle">Web : www.supernumber.co.th Tel : 0963232656, 0963232665 Line : @supernumber</text>
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
        $nearFirst = implode(' ', $this->pickPrizeNumbers($prizes, 'ข้างเคียง', 2));
        $statusLabel = $result->is_complete ? 'ผลรางวัลออกครบแล้ว' : 'ผลรางวัลยังอยู่ระหว่างการอัปเดต';

        return "
<p>ตรวจสอบผลสลากกินแบ่งรัฐบาล งวดประจำวันที่ {$thaiDate} {$statusLabel}</p>
<h2>สรุปผลรางวัล</h2>
<ul>
  <li>รางวัลที่ 1: <strong>{$firstPrize}</strong></li>
  <li>เลขหน้า 3 ตัว: <strong>{$frontThree}</strong></li>
  <li>เลขท้าย 3 ตัว: <strong>{$backThree}</strong></li>
  <li>เลขท้าย 2 ตัว: <strong>{$lastTwo}</strong></li>
  <li>ข้างเคียงรางวัลที่ 1: <strong>{$nearFirst}</strong></li>
</ul>
<p>อัปเดตล่าสุด {$updatedAt} น. ข้อความนี้จัดทำขึ้นโดยอัตโนมัติหากข้อมูลผลรางวัลมีการระบุผิดพลาดขอน้อมรับและขออภัยในความไม่สะดวก</p>";
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
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $candidate, $m)) {
                return [$candidate, Carbon::create((int)$m[3]-543, (int)$m[2], (int)$m[1], 0, 0, 0, self::TZ)];
            }
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $candidate, $m)) {
                return [$candidate, Carbon::create((int)$m[1], (int)$m[2], (int)$m[3], 0, 0, 0, self::TZ)];
            }
        }
        return [null, null];
    }

    private function extractPrizes(array $payload): array
    {
        $rows = [];
        $data = data_get($payload, 'response.data', []);
        $map = [
            'first' => 'รางวัลที่ 1', 
            'last3f' => 'เลขหน้า 3 ตัว', 
            'last3b' => 'เลขท้าย 3 ตัว', 
            'last2' => 'เลขท้าย 2 ตัว'
        ];

        foreach ($map as $key => $name) {
            $numbers = data_get($data, "{$key}.number", []);
            if (is_scalar($numbers)) $numbers = [$numbers];
            if (is_array($numbers)) {
                foreach ($numbers as $num) {
                    if (is_array($num)) $num = data_get($num, 'value') ?? reset($num);
                    if (is_scalar($num)) {
                        $rows[] = ['name' => $name, 'number' => (string) $num];
                    }
                }
            }
        }
        return $rows;
    }

    private function shouldReplacePrizes(array $incoming, ?Collection $existing): bool
    {
        return count($incoming) >= ($existing?->count() ?? 0);
    }

    private function isCompletePayload(array $prizes, ?Carbon $source, Carbon $storage): bool
    {
        return count($prizes) >= 6;
    }
}
