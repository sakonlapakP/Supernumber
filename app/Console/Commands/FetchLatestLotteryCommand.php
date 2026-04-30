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
            
            $article = $this->syncLotteryArticleCover($result, $now);
            
            DB::commit();

            $this->notifyLineWhenCompleted($result, $wasAlreadyComplete);
            $this->notifyFacebookWhenCompleted($article, $result, $wasAlreadyComplete);

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

    private function notifyLineWhenCompleted(LotteryResult $result, bool $wasAlreadyComplete): void
    {
        if ($wasAlreadyComplete || !$result->is_complete) return;
        try {
            app(LineLotteryNotifier::class)->sendCompleted($result);
        } catch (\Throwable $exception) {
            Log::warning('Lottery LINE notification failed: '.$exception->getMessage());
        }
    }

    private function notifyFacebookWhenCompleted(?Article $article, LotteryResult $result, bool $wasAlreadyComplete): void
    {
        if ($article === null || $wasAlreadyComplete || !$result->is_complete) return;
        try {
            app(FacebookPagePoster::class)->postArticle($article);
        } catch (\Throwable $exception) {
            Log::warning('Lottery Facebook notification failed: '.$exception->getMessage());
        }
    }

    private function syncLotteryArticleCover(LotteryResult $result, Carbon $now): ?Article
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

        if (!$article->exists) {
            $article->is_published = true;
            $article->published_at = now();
        }

        $article->title = sprintf('ตรวจหวยรัฐบาล งวดประจำวันที่ %s ผลสลากกินแบ่งรัฐบาล', $thaiDateLabel);
        $article->excerpt = sprintf('สรุปผลสลากกินแบ่งรัฐบาล งวดประจำวันที่ %s', $thaiDateLabel);
        $article->content = $this->buildLotteryArticleContent($result, $drawDate->copy(), $now->copy());
        
        $squareSvgContents = $this->buildLotteryCoverSvg($result);
        Storage::disk('public')->put($squareSvgFilename, $squareSvgContents);

        $landscapeSvgContents = $this->buildLotteryLandscapeSvg($result);
        Storage::disk('public')->put($landscapeSvgFilename, $landscapeSvgContents);

        $article->cover_image_square_path = $squareSvgFilename;
        $article->cover_image_path = $squareSvgFilename;
        $article->cover_image_landscape_path = $landscapeSvgFilename;

        $article->save();
        $this->info("Synced lottery article: {$articleName}");

        try {
            app(LineLotteryNotifier::class)->notifyAdminArticleReady($article);
        } catch (\Throwable $e) {
            Log::error("Failed to send admin article notification: " . $e->getMessage());
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
        $prizes = $result->prizes;

        $p1 = $this->pickFirstPrizeNumber($prizes, 'รางวัลที่ 1', '......');
        $l2 = $this->pickFirstPrizeNumber($prizes, 'เลขท้าย 2 ตัว', '..');
        $f3_arr = $this->pickPrizeNumbers($prizes, 'เลขหน้า 3 ตัว', 2);
        $l3_arr = $this->pickPrizeNumbers($prizes, 'เลขท้าย 3 ตัว', 2);
        
        $f3 = count($f3_arr) > 0 ? implode(' ', $f3_arr) : '... ...';
        $l3 = count($l3_arr) > 0 ? implode(' ', $l3_arr) : '... ...';

        $fontPath = public_path('fonts/Kanit-500.ttf');
        $fontBase64 = is_file($fontPath) ? base64_encode((string)file_get_contents($fontPath)) : '';

        return <<<SVG
<svg width="1200" height="630" viewBox="0 0 1200 630" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <style>
            @font-face { font-family: 'Kanit'; src: url(data:font/ttf;base64,{$fontBase64}); }
        </style>
        <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#1e140d;stop-opacity:1" />
            <stop offset="50%" style="stop-color:#120907;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#1e140d;stop-opacity:1" />
        </linearGradient>
    </defs>
    <rect width="1200" height="630" fill="url(#bgGrad)" />
    <rect x="20" y="20" width="1160" height="590" fill="none" stroke="#635342" stroke-width="4" />
    <rect x="40" y="40" width="1120" height="550" fill="none" stroke="#ba8e4d" stroke-width="2" opacity="0.5" />
    <rect x="565" y="70" width="70" height="70" fill="#2a1a10" stroke="#d7a64e" stroke-width="3" />
    <text x="600" y="127" font-family="'Times New Roman', serif" font-size="62" font-weight="900" fill="#f5c76d" text-anchor="middle">S</text>
    <text x="600" y="175" font-family="Kanit" font-size="24" font-weight="500" fill="#f7d58f" text-anchor="middle" letter-spacing="8">SUPERNUMBER</text>
    <text x="600" y="235" font-family="Kanit" font-size="32" font-weight="500" fill="#ffffff" text-anchor="middle">ผลสลากกินแบ่งรัฐบาล งวดวันที่ $thaiDate</text>
    <rect x="250" y="260" width="700" height="160" rx="10" fill="#fffaf0" />
    <text x="600" y="315" font-family="Kanit" font-size="38" font-weight="500" fill="#2a1a10" text-anchor="middle" opacity="0.8">รางวัลที่ 1</text>
    <text x="600" y="395" font-family="Kanit" font-size="92" font-weight="500" fill="#2a1a10" text-anchor="middle" letter-spacing="10">$p1</text>
    <rect x="150" y="440" width="280" height="110" rx="8" fill="#fffaf0" opacity="0.95" />
    <text x="290" y="475" font-family="Kanit" font-size="24" font-weight="400" fill="#2a1a10" text-anchor="middle">เลขหน้า 3 ตัว</text>
    <text x="290" y="530" font-family="Kanit" font-size="52" font-weight="500" fill="#2a1a10" text-anchor="middle" letter-spacing="4">$f3</text>
    <rect x="460" y="440" width="280" height="110" rx="8" fill="#fffaf0" opacity="0.95" />
    <text x="600" y="475" font-family="Kanit" font-size="24" font-weight="400" fill="#2a1a10" text-anchor="middle">เลขท้าย 3 ตัว</text>
    <text x="600" y="530" font-family="Kanit" font-size="52" font-weight="500" fill="#2a1a10" text-anchor="middle" letter-spacing="4">$l3</text>
    <rect x="770" y="440" width="280" height="110" rx="8" fill="#fffaf0" opacity="0.95" />
    <text x="910" y="475" font-family="Kanit" font-size="24" font-weight="400" fill="#2a1a10" text-anchor="middle">เลขท้าย 2 ตัว</text>
    <text x="910" y="530" font-family="Kanit" font-size="64" font-weight="500" fill="#2a1a10" text-anchor="middle" letter-spacing="4">$l2</text>
    <text x="600" y="585" font-family="Kanit" font-size="20" font-weight="400" fill="#f7d58f" text-anchor="middle" opacity="0.6">supernumber.co.th</text>
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

        $fontPath = public_path('fonts/Kanit-500.ttf');
        $fontBase64 = is_file($fontPath) ? base64_encode((string)file_get_contents($fontPath)) : '';

        return <<<SVG
<svg width="1200" height="1200" viewBox="0 0 1200 1200" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <style>
            @font-face { font-family: 'Kanit'; src: url(data:font/ttf;base64,{$fontBase64}); }
        </style>
        <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#1e140d;stop-opacity:1" />
            <stop offset="50%" style="stop-color:#120907;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#1e140d;stop-opacity:1" />
        </linearGradient>
    </defs>
    <rect width="1200" height="1200" fill="url(#bgGrad)" />
    <rect x="15" y="15" width="1170" height="1170" fill="none" stroke="#c59d62" stroke-width="3" />
    <rect x="50" y="50" width="1100" height="1100" fill="none" stroke="#ba8e4d" stroke-width="2" opacity="0.4" />
    <rect x="554" y="78" width="92" height="92" fill="#2a1a10" stroke="#d7a64e" stroke-width="3" />
    <text x="600" y="152" font-family="'Times New Roman', serif" font-size="82" font-weight="900" fill="#f5c76d" text-anchor="middle">S</text>
    <text x="600" y="215" font-family="Kanit" font-size="28" font-weight="500" fill="#f7d58f" text-anchor="middle" letter-spacing="6">SUPERNUMBER</text>
    <text x="600" y="280" font-family="Kanit" font-size="60" font-weight="500" fill="#ffffff" text-anchor="middle">ผลสลากกินแบ่งรัฐบาล</text>
    <text x="600" y="340" font-family="Kanit" font-size="32" font-weight="400" fill="#f7d58f" text-anchor="middle">งวดประจำวันที่ $thaiDate</text>
    <rect x="100" y="380" width="1000" height="340" rx="15" fill="#fffaf0" />
    <text x="600" y="460" font-family="Kanit" font-size="60" font-weight="500" fill="#2a1a10" text-anchor="middle">รางวัลที่ 1</text>
    <text x="600" y="630" font-family="Kanit" font-size="187" font-weight="500" fill="#2a1a10" text-anchor="middle" letter-spacing="15">$p1</text>
    <text x="250" y="795" font-family="Kanit" font-size="30" font-weight="400" fill="#ffffff" text-anchor="middle">เลขหน้า 3 ตัว</text>
    <text x="600" y="795" font-family="Kanit" font-size="30" font-weight="400" fill="#ffffff" text-anchor="middle">เลขท้าย 3 ตัว</text>
    <text x="950" y="795" font-family="Kanit" font-size="30" font-weight="400" fill="#ffffff" text-anchor="middle">เลขท้าย 2 ตัว</text>
    <rect x="80" y="805" width="337" height="120" rx="10" fill="#fffaf0" />
    <rect x="80" y="940" width="337" height="120" rx="10" fill="#fffaf0" />
    <text x="250" y="890" font-family="Kanit" font-size="77" font-weight="500" fill="#2a1a10" text-anchor="middle">$f3_1</text>
    <text x="250" y="1025" font-family="Kanit" font-size="77" font-weight="500" fill="#2a1a10" text-anchor="middle">$f3_2</text>
    <rect x="431" y="805" width="337" height="120" rx="10" fill="#fffaf0" />
    <rect x="431" y="940" width="337" height="120" rx="10" fill="#fffaf0" />
    <text x="600" y="890" font-family="Kanit" font-size="77" font-weight="500" fill="#2a1a10" text-anchor="middle">$l3_1</text>
    <text x="600" y="1025" font-family="Kanit" font-size="77" font-weight="500" fill="#2a1a10" text-anchor="middle">$l3_2</text>
    <rect x="783" y="805" width="337" height="255" rx="10" fill="#fffaf0" />
    <text x="950" y="965" font-family="Kanit" font-size="128" font-weight="500" fill="#2a1a10" text-anchor="middle">$l2</text>
    <rect x="450" y="1090" width="300" height="42" fill="#1a0f08" />
    <text x="600" y="1121" font-family="Kanit" font-size="32" font-weight="500" fill="#fff6e4" text-anchor="middle">SUPERNUMBER.CO.TH</text>
    <text x="600" y="1165" font-family="Kanit" font-size="22" font-weight="400" fill="#f5e4c4" text-anchor="middle">Web : www.supernumber.co.th Tel : 0963232656, 0963232665 Line : @supernumber</text>
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
