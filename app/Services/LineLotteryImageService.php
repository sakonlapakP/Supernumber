<?php

namespace App\Services;

use App\Models\Article;
use App\Models\LotteryResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LineLotteryImageService
{
    public function buildLineImageUrl(LotteryResult $result): ?string
    {
        if (! $this->canServeImage($result)) {
            return null;
        }

        $url = $this->absoluteUrl(URL::signedRoute('line.lottery-result-image', [
            'lotteryResult' => $result,
        ], absolute: false));

        return $this->isPublicHttpsUrl($url) ? $url : null;
    }

    public function canServeImage(LotteryResult $result): bool
    {
        return $this->resolveStoredRasterImage($result) !== null || $this->canRenderFallbackPng();
    }

    public function toResponse(LotteryResult $result): Response
    {
        $storedImage = $this->resolveStoredRasterImage($result);

        if ($storedImage !== null) {
            return response()->file($storedImage['absolute_path'], [
                'Content-Type' => $storedImage['mime_type'],
                'Cache-Control' => 'public, max-age=300',
            ]);
        }

        $binary = $this->renderFallbackPng($result);

        abort_if($binary === null, 404);

        return response($binary, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    private function resolveStoredRasterImage(LotteryResult $result): ?array
    {
        $article = Article::query()
            ->where('slug', $this->resolveArticleSlug($result))
            ->first();

        $path = trim((string) ($article?->cover_image_square_path ?: $article?->cover_image_path ?: ''));

        if ($path === '') {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $absolutePath = $disk->path($path);

        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return null;
        }

        $imageInfo = @getimagesize($absolutePath);

        if ($imageInfo === false) {
            return null;
        }

        $mimeType = strtolower((string) ($imageInfo['mime'] ?? ''));

        if (! in_array($mimeType, ['image/png', 'image/jpeg'], true)) {
            return null;
        }

        return [
            'absolute_path' => $absolutePath,
            'mime_type' => $mimeType,
        ];
    }

    private function resolveArticleSlug(LotteryResult $result): string
    {
        $drawDate = $result->source_draw_date ?? $result->draw_date ?? now('Asia/Bangkok');
        $round = (int) $drawDate->format('j') <= 15 ? 'first' : 'second';

        return sprintf(
            'thai-goverment-lottery-%s%s%s',
            $drawDate->format('Y'),
            $drawDate->format('m'),
            $round
        );
    }

    private function canRenderFallbackPng(): bool
    {
        return function_exists('imagecreatetruecolor')
            && function_exists('imagepng')
            && function_exists('imagettftext')
            && is_file($this->fontPath('Kanit-700.ttf'))
            && is_file($this->fontPath('Kanit-600.ttf'))
            && is_file($this->fontPath('Kanit-500.ttf'));
    }

    private function renderFallbackPng(LotteryResult $result): ?string
    {
        if (! $this->canRenderFallbackPng()) {
            return null;
        }

        if (! $result->relationLoaded('prizes')) {
            $result->load('prizes');
        }

        $width = 1200;
        $height = 1200;
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            return null;
        }

        try {
            if (function_exists('imageantialias')) {
                imageantialias($image, true);
            }
            imagealphablending($image, true);
            imagesavealpha($image, true);

            $this->paintBackground($image, $width, $height);

            $cream = imagecolorallocate($image, 255, 247, 232);
            $gold = imagecolorallocate($image, 239, 199, 120);
            $goldDark = imagecolorallocate($image, 163, 117, 53);
            $panel = imagecolorallocate($image, 249, 234, 197);
            $panelDark = imagecolorallocate($image, 63, 42, 19);
            $muted = imagecolorallocate($image, 230, 215, 184);

            imagerectangle($image, 34, 34, $width - 35, $height - 35, $gold);
            imagerectangle($image, 58, 58, $width - 59, $height - 59, $goldDark);

            $drawDate = $result->source_draw_date ?? $result->draw_date ?? now('Asia/Bangkok');
            $prizes = $result->prizes;

            $firstPrize = $this->pickFirstPrizeNumber($prizes, 'รางวัลที่ 1', '-');
            $frontThree = $this->pickPrizeNumbers($prizes, 'เลขหน้า 3 ตัว', 2);
            $backThree = $this->pickPrizeNumbers($prizes, 'เลขท้าย 3 ตัว', 2);
            $lastTwo = $this->pickFirstPrizeNumber($prizes, 'เลขท้าย 2 ตัว', '-');
            $nearFirst = $this->pickPrizeNumbers($prizes, 'ข้างเคียง', 2);
            $thaiDate = $this->toThaiDateLabel($drawDate->copy());
            $updatedAt = ($result->fetched_at?->copy() ?? now('Asia/Bangkok'))
                ->timezone('Asia/Bangkok')
                ->format('d/m/Y H:i');

            while (count($frontThree) < 2) {
                $frontThree[] = '-';
            }

            while (count($backThree) < 2) {
                $backThree[] = '-';
            }

            $this->drawCenteredText($image, 'SUPERNUMBER', $this->fontPath('Kanit-700.ttf'), 28, 600, 105, $gold);
            $this->drawCenteredText($image, 'ผลสลากกินแบ่งรัฐบาล', $this->fontPath('Kanit-700.ttf'), 54, 600, 172, $cream);
            $this->drawCenteredText($image, 'งวดประจำวันที่ '.$thaiDate, $this->fontPath('Kanit-500.ttf'), 30, 600, 222, $muted);

            imagefilledrectangle($image, 150, 276, 1050, 490, $panel);
            imagerectangle($image, 150, 276, 1050, 490, $gold);
            $this->drawCenteredText($image, 'รางวัลที่ 1', $this->fontPath('Kanit-700.ttf'), 40, 600, 345, $goldDark);
            $this->drawCenteredText($image, $firstPrize, $this->fontPath('Kanit-700.ttf'), 112, 600, 452, $panelDark);

            $nearFirstText = $nearFirst !== [] ? implode(' ', $nearFirst) : '-';
            $this->drawCenteredText($image, 'ข้างเคียงรางวัลที่ 1 : '.$nearFirstText, $this->fontPath('Kanit-500.ttf'), 28, 600, 548, $muted);

            $this->drawGroupPanel($image, 82, 620, 310, 360, 'เลขหน้า 3 ตัว', $frontThree, 78, $panel, $gold, $panelDark);
            $this->drawGroupPanel($image, 445, 620, 310, 360, 'เลขท้าย 3 ตัว', $backThree, 78, $panel, $gold, $panelDark);
            $this->drawGroupPanel($image, 808, 620, 310, 360, 'เลขท้าย 2 ตัว', [$lastTwo], 118, $panel, $gold, $panelDark);

            $status = $result->is_complete ? 'ข้อมูลครบแล้ว' : 'ผลรางวัลยังอัปเดตอยู่';
            $this->drawCenteredText($image, 'อัปเดตล่าสุด '.$updatedAt.' น. ('.$status.')', $this->fontPath('Kanit-500.ttf'), 24, 600, 1088, $muted);

            ob_start();
            imagepng($image);
            $binary = ob_get_clean();

            return is_string($binary) ? $binary : null;
        } finally {
            imagedestroy($image);
        }
    }

    private function paintBackground($image, int $width, int $height): void
    {
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / max($height - 1, 1);
            $red = (int) round(18 + (($ratio * 30)));
            $green = (int) round(12 + (($ratio * 20)));
            $blue = (int) round(12 + (($ratio * 10)));
            $lineColor = imagecolorallocate($image, $red, $green, $blue);
            imageline($image, 0, $y, $width, $y, $lineColor);
        }

        $glowLeft = imagecolorallocatealpha($image, 241, 208, 141, 92);
        imagefilledellipse($image, 90, 90, 340, 340, $glowLeft);

        $glowRight = imagecolorallocatealpha($image, 255, 204, 120, 102);
        imagefilledellipse($image, 1110, 120, 320, 320, $glowRight);
    }

    private function drawGroupPanel(
        $image,
        int $x,
        int $y,
        int $width,
        int $height,
        string $title,
        array $numbers,
        int $numberSize,
        int $panelColor,
        int $borderColor,
        int $textColor
    ): void {
        imagefilledrectangle($image, $x, $y, $x + $width, $y + $height, $panelColor);
        imagerectangle($image, $x, $y, $x + $width, $y + $height, $borderColor);

        $centerX = $x + (int) floor($width / 2);
        $this->drawCenteredText($image, $title, $this->fontPath('Kanit-700.ttf'), 30, $centerX, $y + 58, $textColor);

        if (count($numbers) === 1) {
            imagefilledrectangle($image, $x + 32, $y + 102, $x + $width - 32, $y + $height - 32, imagecolorallocate($image, 255, 248, 230));
            imagerectangle($image, $x + 32, $y + 102, $x + $width - 32, $y + $height - 32, $borderColor);
            $this->drawCenteredText($image, (string) $numbers[0], $this->fontPath('Kanit-700.ttf'), $numberSize, $centerX, $y + 250, $textColor);

            return;
        }

        $boxTop = $y + 102;
        $boxWidth = $width - 64;
        $gap = 20;
        $boxHeight = (int) floor(($height - 134 - $gap) / 2);

        foreach (array_slice(array_values($numbers), 0, 2) as $index => $number) {
            $boxY = $boxTop + (($boxHeight + $gap) * $index);
            imagefilledrectangle($image, $x + 32, $boxY, $x + 32 + $boxWidth, $boxY + $boxHeight, imagecolorallocate($image, 255, 248, 230));
            imagerectangle($image, $x + 32, $boxY, $x + 32 + $boxWidth, $boxY + $boxHeight, $borderColor);
            $this->drawCenteredText($image, (string) $number, $this->fontPath('Kanit-700.ttf'), $numberSize, $centerX, $boxY + (int) floor(($boxHeight / 2)) + 24, $textColor);
        }
    }

    private function drawCenteredText(
        $image,
        string $text,
        string $fontPath,
        int $size,
        int $centerX,
        int $baselineY,
        int $color
    ): void {
        $box = imagettfbbox($size, 0, $fontPath, $text);

        if ($box === false) {
            return;
        }

        $textWidth = (int) abs($box[4] - $box[0]);
        $x = $centerX - (int) floor($textWidth / 2);

        imagettftext($image, $size, 0, $x, $baselineY, $color, $fontPath, $text);
    }

    private function fontPath(string $filename): string
    {
        return public_path('fonts/'.$filename);
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
            ->map(fn ($value) => trim((string) $value))
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

        return $drawDate->format('j')
            .' '
            .($thaiMonths[(int) $drawDate->format('n')] ?? $drawDate->format('m'))
            .' '
            .((int) $drawDate->format('Y') + 543);
    }

    private function absoluteUrl(string $pathOrUrl): string
    {
        if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
            return $pathOrUrl;
        }

        $baseUrl = rtrim((string) config('app.url', ''), '/');

        if ($baseUrl === '') {
            return url($pathOrUrl);
        }

        return $baseUrl.'/'.ltrim($pathOrUrl, '/');
    }

    private function isPublicHttpsUrl(string $url): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        return ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            && ! str_ends_with($host, '.local');
    }
}
