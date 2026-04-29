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

        if (! $this->option('force') && ! $this->isInScheduleWindow($now)) {
            $this->line(sprintf('Skipped: outside schedule window (%s).', $now->format('Y-m-d H:i:s T')));

            return self::SUCCESS;
        }

        $existing = LotteryResult::query()
            ->with('prizes')
            ->whereDate('draw_date', $targetDate->toDateString())
            ->first();

        if (! $this->option('force') && $existing?->is_complete) {
            $this->line(sprintf('Skipped: draw %s is already complete.', $targetDate->toDateString()));

            return self::SUCCESS;
        }

        try {
            $response = Http::timeout(12)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post(self::API_URL, []);
        } catch (\Throwable $exception) {
            $this->warn('GLO API request failed: '.$exception->getMessage());

            $result = $this->touchResultWithoutPrizes($targetDate, $existing, $now);
            $this->syncLotteryArticleCover($result, $now);
            $this->notifyLineWhenRetryWindowClosesWithoutData($result, $targetDate, $now);

            return self::SUCCESS;
        }

        if (! $response->ok()) {
            $this->warn('GLO API returned HTTP '.$response->status());

            $result = $this->touchResultWithoutPrizes($targetDate, $existing, $now);
            $this->syncLotteryArticleCover($result, $now);
            $this->notifyLineWhenRetryWindowClosesWithoutData($result, $targetDate, $now);

            return self::SUCCESS;
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            $this->warn('GLO API payload is not valid JSON object/array.');

            $result = $this->touchResultWithoutPrizes($targetDate, $existing, $now);
            $this->syncLotteryArticleCover($result, $now);
            $this->notifyLineWhenRetryWindowClosesWithoutData($result, $targetDate, $now);

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
        $effectivePrizes = $shouldReplacePrizes
            ? $prizes
            : $this->serializePrizeRows($storedResult?->prizes);
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

            if ($shouldReplacePrizes && ! empty($prizes)) {
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
            $this->notifyLineWhenRetryWindowClosesWithoutData($savedResult, $targetDate, $now);
        }

        $this->info(sprintf(
            'Saved draw %s (%d prizes, complete=%s).',
            $storageDate->toDateString(),
            count($prizes),
            $isComplete ? 'yes' : 'no'
        ));

        return self::SUCCESS;
    }

    private function resolveTargetDate(Carbon $now): ?Carbon
    {
        $drawDateOption = trim((string) $this->option('draw-date'));

        if ($drawDateOption === '') {
            return $this->resolveScheduledTargetDate($now);
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $drawDateOption, self::TZ)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveScheduledTargetDate(Carbon $now): Carbon
    {
        $today = $now->copy()->startOfDay();

        if (in_array($now->day, [2, 17], true)) {
            $previousDrawDate = $today->copy()->subDay();

            if ($this->shouldRetryOnNextDay($previousDrawDate)) {
                return $previousDrawDate;
            }
        }

        return $today;
    }

    private function isInScheduleWindow(Carbon $now): bool
    {
        if (! $this->isEligibleScheduleDate($now)) {
            return false;
        }

        $windowStart = $now->copy()->setTime(15, 45);
        $windowEnd = $now->copy()->setTime(16, 20);

        return $now->greaterThanOrEqualTo($windowStart)
            && $now->lessThanOrEqualTo($windowEnd);
    }

    private function isEligibleScheduleDate(Carbon $now): bool
    {
        if (in_array($now->day, [1, 16], true)) {
            return true;
        }

        if (! in_array($now->day, [2, 17], true)) {
            return false;
        }

        return $this->shouldRetryOnNextDay($now->copy()->subDay()->startOfDay());
    }

    private function shouldRetryOnNextDay(Carbon $previousDrawDate): bool
    {
        $previousResult = LotteryResult::query()
            ->withCount('prizes')
            ->whereDate('draw_date', $previousDrawDate->toDateString())
            ->first();

        if ($previousResult === null) {
            return true;
        }

        if ($previousResult->is_complete) {
            return false;
        }

        return (int) ($previousResult->prizes_count ?? 0) === 0;
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

    private function notifyLineWhenRetryWindowClosesWithoutData(LotteryResult $result, Carbon $targetDate, Carbon $now): void
    {
        if ($this->option('force') || ! $this->isRetryWindowClosing($targetDate, $now)) {
            return;
        }

        if (! $result->relationLoaded('prizes')) {
            $result->load('prizes');
        }

        if ($result->prizes->isNotEmpty() || $this->hasSentUnavailableAfterRetryNotification($result)) {
            return;
        }

        try {
            app(LineLotteryNotifier::class)->sendUnavailableAfterRetryWindow($result, $targetDate, $now);
        } catch (\Throwable $exception) {
            Log::warning('Lottery unavailable-after-retry LINE notification failed.', [
                'lottery_result_id' => $result->id,
                'target_draw_date' => $targetDate->toDateString(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function isRetryWindowClosing(Carbon $targetDate, Carbon $now): bool
    {
        return $now->format('H:i') === '16:20'
            && $targetDate->toDateString() === $now->copy()->subDay()->toDateString();
    }

    private function hasSentUnavailableAfterRetryNotification(LotteryResult $result): bool
    {
        return LineNotificationLog::query()
            ->where('event_type', 'lottery_unavailable_after_retry')
            ->where('status', LineNotificationLog::STATUS_SENT)
            ->where('notifiable_type', $result->getMorphClass())
            ->where('notifiable_id', $result->getKey())
            ->exists();
    }

    private function notifyLineWhenCompleted(LotteryResult $result, bool $wasAlreadyComplete): void
    {
        if ($wasAlreadyComplete || ! $result->is_complete) {
            return;
        }

        try {
            app(LineLotteryNotifier::class)->sendCompleted($result);
        } catch (\Throwable $exception) {
            Log::warning('Lottery LINE notification failed.', [
                'lottery_result_id' => $result->id,
                'draw_date' => $result->draw_date?->toDateString(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function syncLotteryArticleCover(LotteryResult $result, Carbon $now): void
    {
        if (! $result->relationLoaded('prizes')) {
            $result->load('prizes');
        }

        $drawDate = $result->source_draw_date ?? $result->draw_date ?? $now;
        $year = $drawDate->format('Y');
        $month = $drawDate->format('m');
        $round = $this->resolveDrawRound($drawDate);
        $articleName = sprintf('thai-goverment-lottery-%s%s%s', $year, $month, $round);
        $articleDir = sprintf('article/%s/%s', $year, $articleName);
        $squareFilename = sprintf('%s/%s.png', $articleDir, $articleName);
        $landscapeFilename = sprintf('%s/%s_cover.png', $articleDir, $articleName);
        $squareSvgFilename = sprintf('%s/%s.svg', $articleDir, $articleName);
        $landscapeSvgFilename = sprintf('%s/%s_cover.svg', $articleDir, $articleName);
        $thaiDateLabel = $this->toThaiDateLabel($drawDate->copy());
        $articleTitle = 'สลากกินแบ่งรัฐบาล ประจำวันที่ '.$thaiDateLabel;
        $article = Article::query()->firstOrNew([
            'slug' => $articleName,
        ]);
        $articleExcerpt = $this->buildLotteryArticleExcerpt($result, $drawDate->copy());
        $articleContent = $this->buildLotteryArticleContent($result, $drawDate->copy(), $now->copy());
        $articleMetaDescription = $this->buildLotteryArticleMetaDescription($result, $drawDate->copy());
        $publishedAt = $result->is_complete
            ? ($article->published_at?->copy() ?? $result->fetched_at?->copy() ?? $now->copy())
            : null;

        $previousLegacyPath = (string) ($article->cover_image_path ?? '');
        $previousSquarePath = (string) ($article->cover_image_square_path ?? '');
        $previousLandscapePath = (string) ($article->cover_image_landscape_path ?? '');
        $coverSyncSucceeded = false;
        $coverSyncMode = 'skipped';

        try {
            $renderedSquare = $this->renderLotteryCoverPng($result, self::SQUARE_RENDER_SELECTOR, 'square');
            $renderedLandscape = $this->renderLotteryCoverPng($result, self::LANDSCAPE_RENDER_SELECTOR, 'landscape');

            if ($renderedSquare === null) {
                $this->warn('Falling back to SVG cover: square cover render failed.');

                if ($renderedLandscape !== null) {
                    @unlink($renderedLandscape);
                }

                $svgContents = $this->buildLotteryCoverSvg($result);
                Storage::disk('public')->put($squareSvgFilename, $svgContents);
                Storage::disk('public')->put($landscapeSvgFilename, $svgContents);

                $article->cover_image_square_path = $squareSvgFilename;
                $article->cover_image_landscape_path = $landscapeSvgFilename;
                $article->cover_image_path = $squareSvgFilename;
                $coverSyncSucceeded = true;
                $coverSyncMode = 'svg-fallback';
            } else {
                $squareContents = file_get_contents($renderedSquare) ?: '';
                Storage::disk('public')->put($squareFilename, $squareContents);
                @unlink($renderedSquare);
                $coverSyncSucceeded = true;
                $coverSyncMode = 'png';

                if ($renderedLandscape === null) {
                    Storage::disk('public')->put($landscapeFilename, $squareContents);
                } else {
                    Storage::disk('public')->put($landscapeFilename, file_get_contents($renderedLandscape) ?: '');
                    @unlink($renderedLandscape);
                }
            }
        } catch (\Throwable $exception) {
            $this->warn('Failed to write lottery cover image: '.$exception->getMessage());
        }

        if ($coverSyncSucceeded && $coverSyncMode === 'png') {
            $article->cover_image_square_path = $squareFilename;
            $article->cover_image_landscape_path = $landscapeFilename;
            $article->cover_image_path = $squareFilename;
        }

        $article->title = $articleTitle;
        $article->excerpt = $articleExcerpt;
        $article->content = $articleContent;
        $article->meta_description = $articleMetaDescription;
        $article->is_published = $result->is_complete;
        $article->published_at = $publishedAt;
        $article->save();

        if ($coverSyncSucceeded) {
            $activePaths = array_values(array_unique(array_filter([
                (string) $article->cover_image_path,
                (string) $article->cover_image_square_path,
                (string) $article->cover_image_landscape_path,
            ])));
            $oldPosterPaths = array_values(array_unique(array_filter([
                $previousLegacyPath,
                $previousSquarePath,
                $previousLandscapePath,
            ])));

            foreach ($oldPosterPaths as $path) {
                if (in_array($path, $activePaths, true)) {
                    continue;
                }

                Storage::disk('public')->delete($path);
            }
        }

        $this->line(sprintf(
            'Synced lottery article %s (published=%s, cover=%s).',
            $article->slug,
            $article->is_published ? 'yes' : 'no',
            $coverSyncSucceeded ? $coverSyncMode : 'skipped'
        ));
    }

    private function buildLotteryArticleExcerpt(LotteryResult $result, Carbon $drawDate): string
    {
        /** @var Collection<int, mixed> $prizes */
        $prizes = $result->prizes;
        $thaiDate = $this->toThaiDateLabel($drawDate->copy());
        $firstPrize = $this->pickFirstPrizeNumber($prizes, 'รางวัลที่ 1', '-');
        $lastTwo = $this->pickFirstPrizeNumber($prizes, 'เลขท้าย 2 ตัว', '-');

        return sprintf(
            'สรุปผลสลากกินแบ่งรัฐบาล งวดประจำวันที่ %s รางวัลที่ 1 %s และเลขท้าย 2 ตัว %s',
            $thaiDate,
            $firstPrize,
            $lastTwo
        );
    }

    private function buildLotteryArticleMetaDescription(LotteryResult $result, Carbon $drawDate): string
    {
        return $this->buildLotteryArticleExcerpt($result, $drawDate);
    }

    private function buildLotteryArticleContent(LotteryResult $result, Carbon $drawDate, Carbon $now): string
    {
        /** @var Collection<int, mixed> $prizes */
        $prizes = $result->prizes;
        $thaiDate = $this->toThaiDateLabel($drawDate->copy());
        $updatedAt = ($result->fetched_at?->copy() ?? $now->copy())
            ->timezone(self::TZ)
            ->format('d/m/Y H:i');
        $firstPrize = $this->pickFirstPrizeNumber($prizes, 'รางวัลที่ 1', '-');
        $frontThree = $this->formatPrizeNumbers($this->pickPrizeNumbers($prizes, 'เลขหน้า 3 ตัว', 2));
        $backThree = $this->formatPrizeNumbers($this->pickPrizeNumbers($prizes, 'เลขท้าย 3 ตัว', 2));
        $lastTwo = $this->pickFirstPrizeNumber($prizes, 'เลขท้าย 2 ตัว', '-');
        $nearFirst = $this->formatPrizeNumbers($this->pickPrizeNumbers($prizes, 'ข้างเคียง', 2));
        $statusLabel = $result->is_complete ? 'ผลรางวัลออกครบแล้ว' : 'ผลรางวัลยังอยู่ระหว่างอัปเดต';

        return <<<HTML
<p>ตรวจผลสลากกินแบ่งรัฐบาล งวดประจำวันที่ {$this->escapeHtml($thaiDate)} {$this->escapeHtml($statusLabel)}</p>
<h2>สรุปผลรางวัล</h2>
<ul>
  <li>รางวัลที่ 1: <strong>{$this->escapeHtml($firstPrize)}</strong></li>
  <li>เลขหน้า 3 ตัว: <strong>{$this->escapeHtml($frontThree)}</strong></li>
  <li>เลขท้าย 3 ตัว: <strong>{$this->escapeHtml($backThree)}</strong></li>
  <li>เลขท้าย 2 ตัว: <strong>{$this->escapeHtml($lastTwo)}</strong></li>
  <li>ข้างเคียงรางวัลที่ 1: <strong>{$this->escapeHtml($nearFirst)}</strong></li>
</ul>
<p>อัปเดตล่าสุด {$this->escapeHtml($updatedAt)} น. บทความนี้จัดทำอัตโนมัติจากข้อมูลผลรางวัลที่ระบบดึงล่าสุด เพื่อให้อ่านและแชร์ต่อได้สะดวก</p>
HTML;
    }

    /**
     * @param  array<int, string>  $numbers
     */
    private function formatPrizeNumbers(array $numbers, string $fallback = '-'): string
    {
        $numbers = array_values(array_filter(array_map(
            static fn ($number) => trim((string) $number),
            $numbers
        )));

        return $numbers !== [] ? implode(' ', $numbers) : $fallback;
    }

    private function resolveDrawRound(Carbon $drawDate): string
    {
        return (int) $drawDate->format('j') <= 15 ? 'first' : 'second';
    }

    private function renderLotteryCoverPng(
        LotteryResult $result,
        string $selector,
        string $variantLabel
    ): ?string
    {
        $scriptPath = base_path(self::PLAYWRIGHT_RENDER_SCRIPT);
        if (! is_file($scriptPath)) {
            $this->warn('Cover renderer script not found: '.$scriptPath);

            return null;
        }

        $tmpHtml = tempnam(sys_get_temp_dir(), 'lottery-cover-html-'.$variantLabel.'-');
        $tmpPng = tempnam(sys_get_temp_dir(), 'lottery-cover-png-'.$variantLabel.'-');

        if ($tmpHtml === false || $tmpPng === false) {
            return null;
        }

        $htmlPath = $tmpHtml.'.html';
        $pngPath = $tmpPng.'.png';
        @rename($tmpHtml, $htmlPath);
        @rename($tmpPng, $pngPath);

        try {
            file_put_contents($htmlPath, $this->buildLotteryCoverHtml($result, $variantLabel));

            $process = new Process(
                ['node', $scriptPath, $htmlPath, $pngPath, $selector],
                base_path(),
            );
            $process->setTimeout(90);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($pngPath) || filesize($pngPath) < 1024) {
                $this->warn(sprintf(
                    'Playwright cover render failed (%s): %s',
                    $variantLabel,
                    trim($process->getErrorOutput().' '.$process->getOutput())
                ));

                return null;
            }

            return $pngPath;
        } catch (\Throwable $exception) {
            $this->warn(sprintf(
                'Playwright cover render exception (%s): %s',
                $variantLabel,
                $exception->getMessage()
            ));

            return null;
        } finally {
            if (is_file($htmlPath)) {
                @unlink($htmlPath);
            }
        }
    }

    private function buildLotteryCoverHtml(LotteryResult $result, string $variant = 'square'): string
    {
        $drawDate = $result->source_draw_date ?? $result->draw_date;
        $thaiDate = $drawDate ? $this->toThaiDateLabel($drawDate->copy()) : '-';

        /** @var Collection<int, mixed> $prizes */
        $prizes = $result->prizes;

        $firstPrize = $this->pickFirstPrizeNumber($prizes, 'รางวัลที่ 1', '-');
        $frontThree = $this->pickPrizeNumbers($prizes, 'เลขหน้า 3 ตัว', 2);
        $backThree = $this->pickPrizeNumbers($prizes, 'เลขท้าย 3 ตัว', 2);
        $lastTwo = $this->pickFirstPrizeNumber($prizes, 'เลขท้าย 2 ตัว', '-');
        $nearFirst = $this->pickPrizeNumbers($prizes, 'ข้างเคียง', 2);

        while (count($frontThree) < 2) {
            $frontThree[] = '-';
        }

        while (count($backThree) < 2) {
            $backThree[] = '-';
        }

        $nearFirstText = empty($nearFirst) ? '-' : implode(', ', $nearFirst);

        $coverCssPath = public_path('css/randerLottteryCover.css');
        $coverCss = is_file($coverCssPath) ? (string) file_get_contents($coverCssPath) : '';

        $lotteryWebsite = 'www.supernumber.co.th';
        $lotteryWebsiteDisplay = preg_replace('/^www\./iu', '', $lotteryWebsite) ?: $lotteryWebsite;
        $lotteryTel = '0963232656, 0963232665';
        $lotteryLine = '@supernumber';

        if ($variant === 'landscape') {
            return '<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Kanit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>'.$coverCss.'</style>
</head>
<body class="landscape">
  <section class="landscape-poster">
    <div class="inner">
      <div class="header-block">
        <div class="top-line">
          <span class="line"></span>
          <span class="logo-mark">S</span>
          <span class="line"></span>
        </div>
        <p class="brand">SUPERNUMBER</p>
      </div>
      <div class="center-block">
        <h2 class="title">สลากกินแบ่งรัฐบาลล่าสุด</h2>
        <p class="subtitle">งวดประจำวันที่ '.$this->escapeHtml($thaiDate).'</p>
      </div>

      <footer class="footer">
        <p class="website">'.$this->escapeHtml($lotteryWebsiteDisplay).'</p>
      </footer>
    </div>
  </section>
</body>
</html>';
        }

        $squareCssPath = public_path('css/randerLottterySquare.css');
        $squareCss = is_file($squareCssPath) ? (string) file_get_contents($squareCssPath) : '';

        return '<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>'.$squareCss.'</style>
</head>
<body class="square">
  <div class="lottery-cover-render-wrap lottery-cover-render-wrap--square">
    '.$this->buildLotteryCoverSvg($result).'
  </div>
</body>
</html>';
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function buildLotteryCoverSvg(LotteryResult $result): string
    {
        $drawDate = $result->source_draw_date ?? $result->draw_date;
        $thaiDate = $drawDate ? $this->toThaiDateLabel($drawDate->copy()) : '-';
        $updatedAt = optional($result->fetched_at)->format('d/m/Y H:i') ?: Carbon::now(self::TZ)->format('d/m/Y H:i');

        /** @var Collection<int, mixed> $prizes */
        $prizes = $result->prizes;

        $firstPrize = $this->pickFirstPrizeNumber($prizes, 'รางวัลที่ 1', '-');
        $frontThree = $this->pickPrizeNumbers($prizes, 'เลขหน้า 3 ตัว', 2);
        $backThree = $this->pickPrizeNumbers($prizes, 'เลขท้าย 3 ตัว', 2);
        $lastTwo = $this->pickFirstPrizeNumber($prizes, 'เลขท้าย 2 ตัว', '-');
        $nearFirst = $this->pickPrizeNumbers($prizes, 'ข้างเคียง', 2);
        $nearFirstText = empty($nearFirst) ? '-' : implode(', ', $nearFirst);
        $status = $result->is_complete ? 'ข้อมูลครบแล้ว' : 'กำลังอัปเดต';

        while (count($frontThree) < 2) {
            $frontThree[] = '-';
        }

        while (count($backThree) < 2) {
            $backThree[] = '-';
        }

        $vars = [
            '{{DRAW_DATE}}' => $this->xmlSafe($thaiDate),
            '{{FIRST}}' => $this->xmlSafe($firstPrize),
            '{{FRONT_1}}' => $this->xmlSafe($frontThree[0]),
            '{{FRONT_2}}' => $this->xmlSafe($frontThree[1]),
            '{{BACK_1}}' => $this->xmlSafe($backThree[0]),
            '{{BACK_2}}' => $this->xmlSafe($backThree[1]),
            '{{LAST_2}}' => $this->xmlSafe($lastTwo),
            '{{NEAR_FIRST}}' => $this->xmlSafe($nearFirstText),
            '{{UPDATED_AT}}' => $this->xmlSafe($updatedAt),
            '{{STATUS}}' => $this->xmlSafe($status),
        ];

        $squareCssPath = public_path('css/randerLottterySquare.css');
        $squareCss = is_file($squareCssPath) ? (string) file_get_contents($squareCssPath) : '';

        $template = <<<'SVG'
<svg class="article-lottery-poster__frame" xmlns="http://www.w3.org/2000/svg" width="1200" height="1200" viewBox="0 0 1200 1200">
  <defs>
    <style><![CDATA[{{SQUARE_CSS}}]]></style>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#1e140d"/>
      <stop offset="15%" stop-color="#120907"/>
      <stop offset="85%" stop-color="#120907"/>
      <stop offset="100%" stop-color="#1e140d"/>
    </linearGradient>
    <radialGradient id="glowLeft" cx="10%" cy="10%" r="40%">
      <stop offset="0%" stop-color="#ba8d49" stop-opacity="0.25"/>
      <stop offset="100%" stop-color="#ba8d49" stop-opacity="0"/>
    </radialGradient>
    <radialGradient id="glowRight" cx="90%" cy="10%" r="40%">
      <stop offset="0%" stop-color="#ba8d49" stop-opacity="0.15"/>
      <stop offset="100%" stop-color="#ba8d49" stop-opacity="0"/>
    </radialGradient>
  </defs>

  <rect width="1200" height="1200" fill="url(#bg)"/>
  <rect width="1200" height="1200" fill="url(#glowLeft)"/>
  <rect width="1200" height="1200" fill="url(#glowRight)"/>
  <rect class="sq-outer-border" x="12" y="12" width="1176" height="1176"/>
  <rect class="sq-inner-border" x="48" y="48" width="1104" height="1104"/>
  <line class="sq-diagonal" x1="940" y1="0" x2="840" y2="500"/>

  <line class="sq-header-line" x1="160" y1="125" x2="520" y2="125"/>
  <line class="sq-header-line" x1="680" y1="125" x2="1040" y2="125"/>

  <rect class="sq-logo-box" x="554" y="78" width="92" height="92"/>
  <text class="sq-logo-text" x="600" y="146" text-anchor="middle">S</text>
  <text class="sq-brand" x="600" y="215" text-anchor="middle">SUPERNUMBER</text>

  <text class="sq-title" x="600" y="270" text-anchor="middle">ผลสลากกินแบ่งรัฐบาล</text>
  <text class="sq-date" x="600" y="325" text-anchor="middle">งวดประจำวันที่ {{DRAW_DATE}}</text>

  <rect class="sq-main-panel" x="80" y="360" width="1040" height="260"/>
  <text class="sq-first-label" x="600" y="445" text-anchor="middle">รางวัลที่ 1</text>
  <text class="sq-first-number" x="600" y="580" text-anchor="middle">{{FIRST}}</text>
  <text class="sq-near-first" x="600" y="670" text-anchor="middle">ข้างเคียงรางวัลที่ 1 : {{NEAR_FIRST}}</text>

  <text class="sq-small-heading" x="250" y="745" text-anchor="middle">เลขหน้า 3 ตัว</text>
  <text class="sq-small-heading" x="600" y="745" text-anchor="middle">เลขท้าย 3 ตัว</text>
  <text class="sq-small-heading" x="950" y="745" text-anchor="middle">เลขท้าย 2 ตัว</text>

  <rect class="sq-small-panel" x="80" y="775" width="337" height="120"/>
  <rect class="sq-small-panel" x="80" y="910" width="337" height="120"/>
  <rect class="sq-small-panel" x="431" y="775" width="337" height="120"/>
  <rect class="sq-small-panel" x="431" y="910" width="337" height="120"/>
  <rect class="sq-small-panel" x="783" y="775" width="337" height="255"/>

  <text class="sq-small-number" x="250" y="865" text-anchor="middle">{{FRONT_1}}</text>
  <text class="sq-small-number" x="250" y="1000" text-anchor="middle">{{FRONT_2}}</text>
  <text class="sq-small-number" x="600" y="865" text-anchor="middle">{{BACK_1}}</text>
  <text class="sq-small-number" x="600" y="1000" text-anchor="middle">{{BACK_2}}</text>
  <text class="sq-last-two-number" x="950" y="945" text-anchor="middle">{{LAST_2}}</text>

  <rect class="sq-footer-tag" x="460" y="1070" width="280" height="42"/>
  <text class="sq-footer-site" x="600" y="1101" text-anchor="middle">SUPERNUMBER.CO.TH</text>
  <text class="sq-footer-meta" x="600" y="1135" text-anchor="middle">Web : www.supernumber.co.th Tel : 0963232656, 0963232665 Line : @supernumber</text>
</svg>






SVG;

        $vars['{{SQUARE_CSS}}'] = str_replace(']]>', ']]]]><![CDATA[>', $squareCss);

        return strtr($template, $vars);
    }

    private function pickFirstPrizeNumber(Collection $prizes, string $nameNeedle, string $fallback): string
    {
        $prize = $prizes
            ->first(fn ($item) => str_contains((string) data_get($item, 'prize_name', ''), $nameNeedle));

        $number = trim((string) data_get($prize, 'prize_number', ''));

        return $number !== '' ? $number : $fallback;
    }

    private function pickPrizeNumbers(Collection $prizes, string $nameNeedle, int $limit): array
    {
        return $prizes
            ->filter(fn ($item) => str_contains((string) data_get($item, 'prize_name', ''), $nameNeedle))
            ->pluck('prize_number')
            ->map(fn ($number) => trim((string) $number))
            ->filter()
            ->take($limit)
            ->values()
            ->all();
    }

    private function toThaiDateLabel(Carbon $drawDate): string
    {
        $thaiMonths = [
            1 => 'มกราคม',
            2 => 'กุมภาพันธ์',
            3 => 'มีนาคม',
            4 => 'เมษายน',
            5 => 'พฤษภาคม',
            6 => 'มิถุนายน',
            7 => 'กรกฎาคม',
            8 => 'สิงหาคม',
            9 => 'กันยายน',
            10 => 'ตุลาคม',
            11 => 'พฤศจิกายน',
            12 => 'ธันวาคม',
        ];

        $month = $thaiMonths[(int) $drawDate->format('n')] ?? $drawDate->format('m');
        $year = (int) $drawDate->format('Y') + 543;

        return $drawDate->format('j').' '.$month.' '.$year;
    }

    private function xmlSafe(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function extractDrawDate(array $payload): array
    {
        $dateCandidates = [];

        $walk = function ($node) use (&$walk, &$dateCandidates): void {
            if (! is_array($node)) {
                return;
            }

            foreach ($node as $key => $value) {
                if (is_scalar($value) && is_string($key)) {
                    $normalizedKey = Str::lower((string) $key);
                    if (in_array($normalizedKey, ['date', 'draw_date', 'drawdate', 'lottery_date'], true)) {
                        $dateCandidates[] = trim((string) $value);
                    }
                }

                if (is_array($value)) {
                    $walk($value);
                }
            }
        };

        $walk($payload);

        foreach ($dateCandidates as $candidate) {
            $parsed = $this->parseDateFromString($candidate);
            if ($parsed !== null) {
                return [$candidate, $parsed];
            }
        }

        $first = $dateCandidates[0] ?? null;

        return [$first, null];
    }

    private function parseDateFromString(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $value, $matches) === 1) {
            return Carbon::createFromFormat('Y-m-d', $matches[0], self::TZ)->startOfDay();
        }

        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{2,4})/', $value, $matches) === 1) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];

            if ($year >= 2400) {
                $year -= 543;
            }

            return Carbon::create($year, $month, $day, 0, 0, 0, self::TZ);
        }

        return null;
    }

    private function extractPrizes(array $payload): array
    {
        $rows = [];

        $appendRow = function (string $name, string $number) use (&$rows): void {
            $number = preg_replace('/\D+/', '', $number) ?? '';
            $name = trim($name);

            if ($number === '' || strlen($number) < 2 || strlen($number) > 6) {
                return;
            }

            if ($name === '') {
                $name = 'รางวัล';
            }

            $rows[] = [
                'name' => $name,
                'number' => $number,
            ];
        };

        $walk = function ($node) use (&$walk, $appendRow): void {
            if (! is_array($node)) {
                return;
            }

            $name = '';
            foreach (['name', 'reward', 'prize', 'title', 'label'] as $nameKey) {
                if (isset($node[$nameKey]) && is_scalar($node[$nameKey])) {
                    $name = trim((string) $node[$nameKey]);
                    break;
                }
            }

            foreach (['number', 'numbers'] as $numberKey) {
                if (! array_key_exists($numberKey, $node)) {
                    continue;
                }

                $value = $node[$numberKey];

                if (is_scalar($value)) {
                    $appendRow($name, (string) $value);
                    continue;
                }

                if (is_array($value)) {
                    foreach ($value as $item) {
                        if (is_scalar($item)) {
                            $appendRow($name, (string) $item);
                        }
                    }
                }
            }

            foreach ($node as $key => $value) {
                if (is_scalar($value) && is_string($key)) {
                    $normalizedKey = Str::lower($key);

                    if (in_array($normalizedKey, ['date', 'draw_date', 'drawdate', 'year', 'month', 'day', 'name', 'reward', 'prize', 'title', 'label', 'number', 'numbers', 'round', 'statuscode', 'status', 'price', 'period', 'remark', 'sheetid'], true)) {
                        continue;
                    }

                    if (preg_match('/^\d{2,6}$/', trim((string) $value)) === 1) {
                        $appendRow($this->normalizePrizeName($key), (string) $value);
                    }
                }

                if (is_array($value)) {
                    $walk($value);
                }
            }
        };

        $structuredData = data_get($payload, 'response.data');
        if (is_array($structuredData)) {
            $nameByGroup = [
                'first' => 'รางวัลที่ 1',
                'first3' => 'เลขหน้า 3 ตัว',
                'last3' => 'เลขท้าย 3 ตัว',
                'last3f' => 'เลขหน้า 3 ตัว',
                'last3b' => 'เลขท้าย 3 ตัว',
                'last2' => 'เลขท้าย 2 ตัว',
                'near1' => 'รางวัลข้างเคียงรางวัลที่ 1',
            ];

            foreach ($structuredData as $groupKey => $groupPayload) {
                if (! is_array($groupPayload)) {
                    continue;
                }

                $normalizedGroupKey = Str::lower((string) $groupKey);
                if (! array_key_exists($normalizedGroupKey, $nameByGroup)) {
                    continue;
                }

                $groupName = $nameByGroup[$normalizedGroupKey];
                $numbers = $groupPayload['number'] ?? $groupPayload['numbers'] ?? null;

                if (is_scalar($numbers)) {
                    $appendRow($groupName, (string) $numbers);
                    continue;
                }

                if (! is_array($numbers)) {
                    continue;
                }

                foreach ($numbers as $entry) {
                    if (is_scalar($entry)) {
                        $appendRow($groupName, (string) $entry);
                        continue;
                    }

                    if (is_array($entry)) {
                        $candidate = $entry['value'] ?? $entry['number'] ?? null;
                        if (is_scalar($candidate)) {
                            $appendRow($groupName, (string) $candidate);
                        }
                    }
                }
            }
        }

        if (empty($rows)) {
            $walk($payload);
        }

        return collect($rows)
            ->unique(fn (array $row) => $row['name'].'|'.$row['number'])
            ->values()
            ->take(30)
            ->all();
    }

    private function normalizePrizeName(string $key): string
    {
        $name = trim(str_replace(['_', '-'], ' ', $key));

        return $name !== '' ? $name : 'รางวัล';
    }

    private function shouldReplacePrizes(array $incomingPrizes, ?Collection $existingPrizes): bool
    {
        if (empty($incomingPrizes)) {
            return false;
        }

        $incomingShape = $this->describePrizeShape($incomingPrizes);
        $existingShape = $this->describePrizeShape($this->serializePrizeRows($existingPrizes));

        if ($incomingShape['score'] > $existingShape['score']) {
            return true;
        }

        if ($incomingShape['score'] < $existingShape['score']) {
            return false;
        }

        if ($incomingShape['total'] > $existingShape['total']) {
            return true;
        }

        if ($incomingShape['total'] < $existingShape['total']) {
            return false;
        }

        return true;
    }

    private function serializePrizeRows(?Collection $prizes): array
    {
        if ($prizes === null) {
            return [];
        }

        return $prizes
            ->map(fn ($prize) => [
                'name' => trim((string) data_get($prize, 'prize_name', data_get($prize, 'name', ''))),
                'number' => trim((string) data_get($prize, 'prize_number', data_get($prize, 'number', ''))),
            ])
            ->filter(fn (array $prize) => $prize['name'] !== '' && $prize['number'] !== '')
            ->values()
            ->all();
    }

    private function describePrizeShape(array $prizes): array
    {
        $firstPrize = $this->countPrizeMatches($prizes, 'รางวัลที่ 1', 6);
        $frontThree = $this->countPrizeMatches($prizes, 'เลขหน้า 3 ตัว', 3);
        $backThree = $this->countPrizeMatches($prizes, 'เลขท้าย 3 ตัว', 3);
        $lastTwo = $this->countPrizeMatches($prizes, 'เลขท้าย 2 ตัว', 2);
        $nearFirst = $this->countPrizeMatches($prizes, 'ข้างเคียง', 6);

        return [
            'total' => count($prizes),
            'first_prize' => $firstPrize,
            'front_three' => $frontThree,
            'back_three' => $backThree,
            'last_two' => $lastTwo,
            'near_first' => $nearFirst,
            'score' => min($firstPrize, 1)
                + min($frontThree, 2)
                + min($backThree, 2)
                + min($lastTwo, 1)
                + min($nearFirst, 2),
        ];
    }

    private function countPrizeMatches(array $prizes, string $nameNeedle, int $digits): int
    {
        return collect($prizes)
            ->filter(function (array $prize) use ($nameNeedle, $digits): bool {
                $name = (string) ($prize['name'] ?? '');
                $number = preg_replace('/\D+/', '', (string) ($prize['number'] ?? '')) ?? '';

                return str_contains($name, $nameNeedle) && strlen($number) === $digits;
            })
            ->count();
    }

    private function isCompletePayload(array $prizes, ?Carbon $sourceDrawDate, Carbon $storageDate): bool
    {
        $shape = $this->describePrizeShape($prizes);

        if (
            $shape['first_prize'] !== 1
            || $shape['front_three'] !== 2
            || $shape['back_three'] !== 2
            || $shape['last_two'] !== 1
        ) {
            return false;
        }

        if ($sourceDrawDate !== null && ! $sourceDrawDate->isSameDay($storageDate)) {
            return false;
        }

        return true;
    }
}
