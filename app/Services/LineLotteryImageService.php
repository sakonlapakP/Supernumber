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
            'thai-government-lottery-%s%s%s',
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

            $white = imagecolorallocate($image, 255, 255, 255);
            $outline = imagecolorallocate($image, 18, 9, 7);
            $gold = imagecolorallocate($image, 239, 199, 120);
            $goldDark = imagecolorallocate($image, 181, 141, 89);
            $panel = imagecolorallocate($image, 255, 250, 243);
            $panelDark = imagecolorallocate($image, 63, 42, 19);
            $muted = imagecolorallocate($image, 245, 228, 196);
            $footerBg = imagecolorallocate($image, 34, 20, 12);

            imagerectangle($image, 6, 6, $width - 7, $height - 7, $goldDark);
            imagerectangle($image, 38, 38, $width - 39, $height - 39, $goldDark);
            imageline($image, 965, 0, 860, 520, imagecolorallocatealpha($image, 140, 103, 55, 64));

            $this->drawTopBrand($image, $gold, $goldDark);

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

            $this->drawCenteredTextWithOutline($image, 'ผลสลากกินแบ่งรัฐบาล', $this->fontPath('Kanit-700.ttf'), 60, 600, 308, $white, $outline, 4);
            $this->drawCenteredTextWithOutline($image, 'งวดประจำวันที่ '.$thaiDate, $this->fontPath('Kanit-700.ttf'), 34, 600, 388, $muted, $outline, 3);

            imagefilledrectangle($image, 70, 400, 1130, 602, $panel);
            imagerectangle($image, 70, 400, 1130, 602, $goldDark);
            $this->drawCenteredText($image, 'รางวัลที่ 1', $this->fontPath('Kanit-700.ttf'), 46, 600, 458, $panelDark);
            $this->drawCenteredText($image, $firstPrize, $this->fontPath('Kanit-700.ttf'), 110, 600, 572, $panelDark);

            $nearFirstText = $nearFirst !== [] ? implode(', ', $nearFirst) : '-';
            $this->drawCenteredText($image, 'ข้างเคียงรางวัลที่ 1 : '.$nearFirstText, $this->fontPath('Kanit-600.ttf'), 20, 600, 666, $muted);

            $this->drawCenteredText($image, 'เลขหน้า 3 ตัว', $this->fontPath('Kanit-700.ttf'), 26, 220, 756, $white);
            $this->drawCenteredText($image, 'เลขท้าย 3 ตัว', $this->fontPath('Kanit-700.ttf'), 26, 600, 756, $white);
            $this->drawCenteredText($image, 'เลขท้าย 2 ตัว', $this->fontPath('Kanit-700.ttf'), 26, 980, 756, $white);

            $this->drawDoubleNumberPanel($image, 50, 770, 360, 92, $frontThree[0], $frontThree[1], $panel, $goldDark, $panelDark);
            $this->drawDoubleNumberPanel($image, 420, 770, 360, 92, $backThree[0], $backThree[1], $panel, $goldDark, $panelDark);
            $this->drawSingleNumberPanel($image, 790, 770, 360, 184, $lastTwo, $panel, $goldDark, $panelDark);

            $status = $result->is_complete ? 'ข้อมูลครบแล้ว' : 'ผลรางวัลยังอัปเดตอยู่';
            imageline($image, 86, 1112, 1114, 1112, $goldDark);
            imagefilledrectangle($image, 434, 1118, 766, 1154, $footerBg);
            $this->drawCenteredText($image, 'SUPERNUMBER.CO.TH', $this->fontPath('Kanit-700.ttf'), 22, 600, 1146, $white);
            $this->drawCenteredText($image, 'Web : www.supernumber.co.th Tel : 0963232656, 0963232665 Line : @supernumber', $this->fontPath('Kanit-600.ttf'), 16, 600, 1172, $muted);
            $this->drawCenteredText($image, 'อัปเดตล่าสุด '.$updatedAt.' น. ('.$status.')', $this->fontPath('Kanit-500.ttf'), 13, 600, 1188, $muted);

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
            $red = (int) round(16 + (($ratio * 42)));
            $green = (int) round(9 + (($ratio * 24)));
            $blue = (int) round(7 + (($ratio * 13)));
            $lineColor = imagecolorallocate($image, $red, $green, $blue);
            imageline($image, 0, $y, $width, $y, $lineColor);
        }

        $glowLeft = imagecolorallocatealpha($image, 197, 149, 78, 96);
        imagefilledellipse($image, 88, 96, 360, 360, $glowLeft);

        $glowRight = imagecolorallocatealpha($image, 197, 149, 78, 108);
        imagefilledellipse($image, 1108, 108, 360, 320, $glowRight);
    }

    private function drawTopBrand($image, int $gold, int $goldDark): void
    {
        imageline($image, 154, 134, 525, 134, $goldDark);
        imageline($image, 675, 134, 1046, 134, $goldDark);
        imagerectangle($image, 548, 78, 652, 182, $gold);
        imagerectangle($image, 549, 79, 651, 181, $goldDark);

        $brandBg = imagecolorallocate($image, 42, 26, 16);
        imagefilledrectangle($image, 550, 80, 650, 180, $brandBg);

        $this->drawCenteredText($image, 'S', $this->fontPath('Kanit-700.ttf'), 50, 600, 150, $gold);
        $this->drawCenteredText($image, 'SUPERNUMBER', $this->fontPath('Kanit-700.ttf'), 18, 600, 212, $gold);
    }

    private function drawDoubleNumberPanel($image, int $x, int $y, int $width, int $boxHeight, string $top, string $bottom, int $panelColor, int $borderColor, int $textColor): void
    {
        $gap = 14;
        imagefilledrectangle($image, $x, $y, $x + $width, $y + $boxHeight, $panelColor);
        imagerectangle($image, $x, $y, $x + $width, $y + $boxHeight, $borderColor);
        imagefilledrectangle($image, $x, $y + $boxHeight + $gap, $x + $width, $y + ($boxHeight * 2) + $gap, $panelColor);
        imagerectangle($image, $x, $y + $boxHeight + $gap, $x + $width, $y + ($boxHeight * 2) + $gap, $borderColor);

        $centerX = $x + (int) floor($width / 2);
        $this->drawCenteredText($image, $top, $this->fontPath('Kanit-700.ttf'), 64, $centerX, $y + 74, $textColor);
        $this->drawCenteredText($image, $bottom, $this->fontPath('Kanit-700.ttf'), 64, $centerX, $y + $boxHeight + $gap + 74, $textColor);
    }

    private function drawSingleNumberPanel($image, int $x, int $y, int $width, int $height, string $number, int $panelColor, int $borderColor, int $textColor): void
    {
        imagefilledrectangle($image, $x, $y, $x + $width, $y + $height, $panelColor);
        imagerectangle($image, $x, $y, $x + $width, $y + $height, $borderColor);
        $centerX = $x + (int) floor($width / 2);
        $this->drawCenteredText($image, $number, $this->fontPath('Kanit-700.ttf'), 112, $centerX, $y + 132, $textColor);
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

    private function drawCenteredTextWithOutline(
        $image,
        string $text,
        string $fontPath,
        int $size,
        int $centerX,
        int $baselineY,
        int $fillColor,
        int $outlineColor,
        int $outlineWidth
    ): void {
        for ($offsetX = -$outlineWidth; $offsetX <= $outlineWidth; $offsetX++) {
            for ($offsetY = -$outlineWidth; $offsetY <= $outlineWidth; $offsetY++) {
                if ($offsetX === 0 && $offsetY === 0) {
                    continue;
                }

                $box = imagettfbbox($size, 0, $fontPath, $text);

                if ($box === false) {
                    return;
                }

                $textWidth = (int) abs($box[4] - $box[0]);
                $x = $centerX - (int) floor($textWidth / 2) + $offsetX;

                imagettftext($image, $size, 0, $x, $baselineY + $offsetY, $outlineColor, $fontPath, $text);
            }
        }

        $this->drawCenteredText($image, $text, $fontPath, $size, $centerX, $baselineY, $fillColor);
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
