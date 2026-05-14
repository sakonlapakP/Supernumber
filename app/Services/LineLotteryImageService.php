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

        // LINE Messaging API only supports JPEG/PNG.
        // We force looking for a PNG version even if the primary cover is SVG.
        $url = $this->absoluteUrl(URL::signedRoute('line.lottery-result-image', [
            'lotteryResult' => $result,
            'format' => 'png',
        ], absolute: false));

        return $this->isPublicHttpsUrl($url) ? $url : null;
    }

    public function canServeImage(LotteryResult $result): bool
    {
        return $this->resolveStoredImage($result) !== null || $this->canRenderFallbackPng();
    }

    public function toResponse(LotteryResult $result): Response
    {
        $format = request()->query('format');
        $storedImage = $this->resolveStoredImage($result, $format);
        
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

    private function resolveStoredImage(LotteryResult $result, ?string $preferredExtension = null): ?array
    {
        $article = Article::query()
            ->where('slug', $this->resolveArticleSlug($result))
            ->first();

        $path = trim((string) ($article?->cover_image_square_path ?: $article?->cover_image_path ?: ''));

        if ($path === '') {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // If a preferred extension is requested (e.g. 'png' for LINE),
        // try to find a file with that extension in the same directory.
        if ($preferredExtension !== null && $extension !== $preferredExtension) {
            $pathWithoutExt = substr($path, 0, strrpos($path, '.'));
            
            // Try [slug].[ext] first (standard)
            $newPath = $pathWithoutExt . '.' . $preferredExtension;
            if (!Storage::disk('public')->exists($newPath)) {
                // Try removing _square if it exists
                if (str_ends_with($pathWithoutExt, '_square')) {
                    $newPath = substr($pathWithoutExt, 0, -7) . '.' . $preferredExtension;
                }
            }
            
            if (Storage::disk('public')->exists($newPath)) {
                $path = $newPath;
                $extension = $preferredExtension;
            }
        }

        if (! in_array($extension, ['png', 'jpg', 'jpeg', 'svg'], true)) {
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

        if ($extension === 'svg') {
            return [
                'absolute_path' => $absolutePath,
                'mime_type' => 'image/svg+xml',
            ];
        }

        $imageInfo = @getimagesize($absolutePath);

        if ($imageInfo === false) {
            return null;
        }

        $mimeType = strtolower((string) ($imageInfo['mime'] ?? ''));

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

    public function renderFallbackPng(LotteryResult $result): ?string
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
            $gold = imagecolorallocate($image, 245, 199, 109);
            $goldDark = imagecolorallocate($image, 186, 142, 77);
            $panel = imagecolorallocate($image, 255, 250, 240);
            $panelDark = imagecolorallocate($image, 42, 26, 16);
            $muted = imagecolorallocate($image, 247, 213, 143);

            imagesetthickness($image, 3);
            imagerectangle($image, 6, 6, $width - 7, $height - 7, $goldDark);
            imagesetthickness($image, 2);
            imagerectangle($image, 38, 38, $width - 39, $height - 39, imagecolorallocatealpha($image, 186, 142, 77, 52));
            imagesetthickness($image, 1);
            imageline($image, 965, 0, 850, 380, imagecolorallocatealpha($image, 245, 199, 109, 108));

            $this->drawTopBrand($image, $gold, $goldDark);

            $drawDate = $result->source_draw_date ?? $result->draw_date ?? now('Asia/Bangkok');
            $prizes = $result->prizes;

            $firstPrize = $this->pickFirstPrizeNumber($prizes, 'รางวัลที่ 1', '-');
            $frontThree = $this->pickPrizeNumbers($prizes, 'เลขหน้า 3 ตัว', 2);
            $backThree = $this->pickPrizeNumbers($prizes, 'เลขท้าย 3 ตัว', 2);
            $lastTwo = $this->pickFirstPrizeNumber($prizes, 'เลขท้าย 2 ตัว', '-');
            $thaiDate = $this->toThaiDateLabel($drawDate->copy());

            while (count($frontThree) < 2) {
                $frontThree[] = '-';
            }

            while (count($backThree) < 2) {
                $backThree[] = '-';
            }

            $this->drawCenteredTextWithOutline($image, 'ผลสลากกินแบ่งรัฐบาล', $this->fontPath('Kanit-700.ttf'), 46, 600, 272, $white, $outline, 4);
            $this->drawCenteredThaiTextWithManualMaiEk($image, 'งวดประจำวันที่ '.$thaiDate, $this->fontPath('Kanit-600.ttf'), 26, 600, 322, $muted, $outline, 2);

            $this->drawRoundedRectangle($image, 75, 370, 1125, 630, 12, $panel, true);
            $this->drawCenteredThaiTextWithManualMaiEk($image, 'รางวัลที่ 1', $this->fontPath('Kanit-600.ttf'), 42, 600, 445, $panelDark);
            $this->drawCenteredLetterSpacedText($image, $firstPrize, $this->fontPath('Kanit-700.ttf'), 120, 600, 585, $panelDark, 8);

            $this->drawCenteredText($image, 'เลขหน้า 3 ตัว', $this->fontPath('Kanit-600.ttf'), 34, 243, 740, $white);
            $this->drawCenteredText($image, 'เลขท้าย 3 ตัว', $this->fontPath('Kanit-600.ttf'), 34, 600, 740, $white);
            $this->drawCenteredText($image, 'เลขท้าย 2 ตัว', $this->fontPath('Kanit-600.ttf'), 34, 957, 740, $white);

            $this->drawDoubleNumberPanel($image, 75, 760, 336, 130, $frontThree[0], $frontThree[1], $panel, $panelDark);
            $this->drawDoubleNumberPanel($image, 432, 760, 336, 130, $backThree[0], $backThree[1], $panel, $panelDark);
            $this->drawSingleNumberPanel($image, 789, 760, 336, 280, $lastTwo, $panel, $panelDark);

            $this->drawCenteredText($image, 'SUPERNUMBER.CO.TH', $this->fontPath('Kanit-700.ttf'), 24, 600, 1115, $white);
            $this->drawCenteredText($image, 'Web : www.supernumber.co.th Tel : 0963232656, 0963232665 Line : @supernumber', $this->fontPath('Kanit-600.ttf'), 17, 600, 1155, $muted);

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
        // Match SVG radialGradient cx=50% cy=15% r=85% with stops:
        //   0%   #5a4227
        //   40%  #261a11
        //   100% #100a06
        // SVG percentage on r (objectBoundingBox) is relative to sqrt(w² + h²)/sqrt(2),
        // which equals the side length for a square canvas — so r=85% of 1200 = 1020.
        $cx = (int) round($width * 0.50);
        $cy = (int) round($height * 0.15);
        $maxR = (int) round($width * 0.85);

        $stops = [
            [0.00, 0x5a, 0x42, 0x27],
            [0.40, 0x26, 0x1a, 0x11],
            [1.00, 0x10, 0x0a, 0x06],
        ];

        // Precompute a 1-D lookup table of packed RGB values keyed by integer distance.
        $gradient = [];
        for ($d = 0; $d <= $maxR; $d++) {
            [$r, $g, $b] = $this->resolveGradientStop($stops, $d / $maxR);
            $gradient[$d] = ($r << 16) | ($g << 8) | $b;
        }
        $outerColor = $gradient[$maxR];

        for ($y = 0; $y < $height; $y++) {
            $dy = $y - $cy;
            $dy2 = $dy * $dy;
            for ($x = 0; $x < $width; $x++) {
                $dx = $x - $cx;
                $d = (int) sqrt($dx * $dx + $dy2);
                imagesetpixel($image, $x, $y, $d >= $maxR ? $outerColor : $gradient[$d]);
            }
        }
    }

    /**
     * @param array<int, array{0: float, 1: int, 2: int, 3: int}> $stops
     * @return array{0: int, 1: int, 2: int}
     */
    private function resolveGradientStop(array $stops, float $t): array
    {
        if ($t <= $stops[0][0]) {
            return [$stops[0][1], $stops[0][2], $stops[0][3]];
        }

        $count = count($stops);
        for ($i = 1; $i < $count; $i++) {
            if ($t <= $stops[$i][0]) {
                $prev = $stops[$i - 1];
                $cur = $stops[$i];
                $range = $cur[0] - $prev[0];
                $local = $range > 0 ? ($t - $prev[0]) / $range : 0.0;

                return [
                    (int) round($prev[1] + ($cur[1] - $prev[1]) * $local),
                    (int) round($prev[2] + ($cur[2] - $prev[2]) * $local),
                    (int) round($prev[3] + ($cur[3] - $prev[3]) * $local),
                ];
            }
        }

        $last = $stops[$count - 1];

        return [$last[1], $last[2], $last[3]];
    }

    private function drawTopBrand($image, int $gold, int $goldDark): void
    {
        imagesetthickness($image, 2);
        imageline($image, 150, 124, 520, 124, $goldDark);
        imageline($image, 680, 124, 1050, 124, $goldDark);
        imagesetthickness($image, 3);
        imagerectangle($image, 554, 78, 646, 170, $gold);
        imagesetthickness($image, 1);

        $brandBg = imagecolorallocate($image, 42, 26, 16);
        imagefilledrectangle($image, 557, 81, 643, 167, $brandBg);

        $this->drawCenteredText($image, 'S', $this->fontPath('Cinzel-700.ttf'), 62, 600, 151, $gold);
        $this->drawCenteredLetterSpacedText($image, 'SUPERNUMBER', $this->fontPath('Kanit-700.ttf'), 24, 600, 215, $gold, 6);
    }

    private function drawDoubleNumberPanel($image, int $x, int $y, int $width, int $boxHeight, string $top, string $bottom, int $panelColor, int $textColor): void
    {
        $gap = 20;
        $this->drawRoundedRectangle($image, $x, $y, $x + $width, $y + $boxHeight, 10, $panelColor, true);
        $this->drawRoundedRectangle($image, $x, $y + $boxHeight + $gap, $x + $width, $y + ($boxHeight * 2) + $gap, 10, $panelColor, true);

        $centerX = $x + (int) floor($width / 2);
        $this->drawCenteredText($image, $top, $this->fontPath('Kanit-700.ttf'), 72, $centerX, $y + 94, $textColor);
        $this->drawCenteredText($image, $bottom, $this->fontPath('Kanit-700.ttf'), 72, $centerX, $y + $boxHeight + $gap + 94, $textColor);
    }

    private function drawSingleNumberPanel($image, int $x, int $y, int $width, int $height, string $number, int $panelColor, int $textColor): void
    {
        $this->drawRoundedRectangle($image, $x, $y, $x + $width, $y + $height, 10, $panelColor, true);
        $centerX = $x + (int) floor($width / 2);
        $this->drawCenteredText($image, $number, $this->fontPath('Kanit-700.ttf'), 122, $centerX, $y + 190, $textColor);
    }

    private function drawRoundedRectangle($image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color, bool $filled): void
    {
        if (! $filled) {
            imagerectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
            imagerectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
            imagearc($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color);
            imagearc($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color);
            imagearc($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $color);
            imagearc($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color);

            return;
        }

        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
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

    private function drawCenteredLetterSpacedText(
        $image,
        string $text,
        string $fontPath,
        int $size,
        int $centerX,
        int $baselineY,
        int $color,
        int $letterSpacing
    ): void {
        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if ($characters === false || $characters === []) {
            return;
        }

        $characterWidths = [];
        $totalWidth = 0;

        foreach ($characters as $character) {
            $box = imagettfbbox($size, 0, $fontPath, $character);

            if ($box === false) {
                return;
            }

            $width = (int) abs($box[4] - $box[0]);
            $characterWidths[] = $width;
            $totalWidth += $width;
        }

        $totalWidth += $letterSpacing * (count($characters) - 1);
        $x = $centerX - (int) floor($totalWidth / 2);

        foreach ($characters as $index => $character) {
            imagettftext($image, $size, 0, $x, $baselineY, $color, $fontPath, $character);
            $x += $characterWidths[$index] + $letterSpacing;
        }
    }

    private function drawCenteredThaiTextWithManualMaiEk(
        $image,
        string $text,
        string $fontPath,
        int $size,
        int $centerX,
        int $baselineY,
        int $fillColor,
        ?int $outlineColor = null,
        int $outlineWidth = 0
    ): void {
        $displayText = str_replace('ที่', 'ที', $text);
        $box = imagettfbbox($size, 0, $fontPath, $displayText);

        if ($box === false) {
            return;
        }

        $textWidth = (int) abs($box[4] - $box[0]);
        $x = $centerX - (int) floor($textWidth / 2);

        if ($outlineColor !== null && $outlineWidth > 0) {
            for ($offsetX = -$outlineWidth; $offsetX <= $outlineWidth; $offsetX++) {
                for ($offsetY = -$outlineWidth; $offsetY <= $outlineWidth; $offsetY++) {
                    if ($offsetX === 0 && $offsetY === 0) {
                        continue;
                    }

                    imagettftext($image, $size, 0, $x + $offsetX, $baselineY + $offsetY, $outlineColor, $fontPath, $displayText);
                }
            }
        }

        imagettftext($image, $size, 0, $x, $baselineY, $fillColor, $fontPath, $displayText);
        $this->drawManualMaiEkMarks($image, $text, $fontPath, $size, $x, $baselineY, $fillColor, $outlineColor, $outlineWidth);
    }

    private function drawManualMaiEkMarks(
        $image,
        string $originalText,
        string $fontPath,
        int $size,
        int $startX,
        int $baselineY,
        int $fillColor,
        ?int $outlineColor,
        int $outlineWidth
    ): void {
        $markSize = max(12, (int) round($size * 0.85));

        $boxTha  = imagettfbbox($size, 0, $fontPath, 'ท');
        $boxThi  = imagettfbbox($size, 0, $fontPath, 'ที');
        $boxMark = imagettfbbox($markSize, 0, $fontPath, '่');

        if ($boxTha === false || $boxThi === false) {
            return;
        }

        // Visual-centre offsets relative to each glyph's drawing position.
        // For a glyph drawn at cursor X, its visual centre = X + (box[0] + box[4]) / 2,
        // accounting for left/right side bearings (important for combining marks like ่
        // which often have negative box[0]).
        $thaCentre  = ($boxTha[0] + $boxTha[4]) / 2;
        $markCentre = ($boxMark !== false) ? ($boxMark[0] + $boxMark[4]) / 2 : 0.0;

        // imagettfbbox y-values: negative = above baseline.
        // [5]=upper-right y, [7]=upper-left y — the minimum (most negative) is the topmost pixel.
        $thiTop = min($boxThi[5], $boxThi[7]);
        $thaTop = min($boxTha[5], $boxTha[7]);

        // When FreeType correctly positions sara-ii (ี) above ท via mark-to-base GPOS,
        // the measured top of "ที" is noticeably higher than just "ท".
        // Place mai-ek baseline just above that measured sara-ii top.
        // If the difference is negligible FreeType didn't expose it — use a Kanit-tuned ratio.
        if ($thiTop < $thaTop - 2) {
            $markY = $baselineY + $thiTop - (int) round($size * 0.06);
        } else {
            // Empirical fallback: mai-ek sits ~1.05× font-size above the text baseline for Kanit.
            $markY = $baselineY - (int) round($size * 1.05);
        }

        $offset = 0;
        while (($pos = mb_strpos($originalText, 'ที่', $offset, 'UTF-8')) !== false) {
            // The display string has every "ที่" replaced with "ที", so measure the prefix the same way.
            $prefix = str_replace('ที่', 'ที', mb_substr($originalText, 0, $pos, 'UTF-8'));

            $prefixWidth = 0;
            if ($prefix !== '') {
                $prefixBox = imagettfbbox($size, 0, $fontPath, $prefix);
                if ($prefixBox === false) {
                    return;
                }
                $prefixWidth = (int) abs($prefixBox[4] - $prefixBox[0]);
            }

            // Align the mai-ek's visual centre with ท's visual centre.
            $markX = $startX + $prefixWidth + (int) round($thaCentre - $markCentre);

            if ($outlineColor !== null && $outlineWidth > 0) {
                for ($dx = -$outlineWidth; $dx <= $outlineWidth; $dx++) {
                    for ($dy = -$outlineWidth; $dy <= $outlineWidth; $dy++) {
                        if ($dx === 0 && $dy === 0) {
                            continue;
                        }
                        imagettftext($image, $markSize, 0, $markX + $dx, $markY + $dy, $outlineColor, $fontPath, '่');
                    }
                }
            }

            imagettftext($image, $markSize, 0, $markX, $markY, $fillColor, $fontPath, '่');
            $offset = $pos + mb_strlen('ที่', 'UTF-8');
        }
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

        if (config('app.env') === 'testing') {
            return true;
        }

        return ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            && ! str_ends_with($host, '.local');
    }

    public function generateSquareSvg(LotteryResult $result): string
    {
        $drawDate = $result->source_draw_date ?? $result->draw_date ?? now('Asia/Bangkok');
        $thaiDate = $this->toThaiDateLabel($drawDate->copy());
        
        $prizes = $result->prizes;
        $firstPrize = $this->pickFirstPrizeNumber($prizes, 'รางวัลที่ 1', '-');
        $frontThree = $this->pickPrizeNumbers($prizes, 'เลขหน้า 3 ตัว', 2);
        $backThree = $this->pickPrizeNumbers($prizes, 'เลขท้าย 3 ตัว', 2);
        $lastTwo = $this->pickFirstPrizeNumber($prizes, 'เลขท้าย 2 ตัว', '-');
        
        while (count($frontThree) < 2) $frontThree[] = '-';
        while (count($backThree) < 2) $backThree[] = '-';

        $fontData = $this->getFontBase64('Kanit-700.ttf');
        $fontMediumData = $this->getFontBase64('Kanit-500.ttf');

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="1200" height="1200" viewBox="0 0 1200 1200" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <style>
            @font-face { font-family: 'Kanit'; font-weight: 700; src: url(data:font/ttf;base64,{$fontData}); }
            @font-face { font-family: 'Kanit'; font-weight: 500; src: url(data:font/ttf;base64,{$fontMediumData}); }
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
    <text x="600" y="335" font-family="Kanit" font-size="34" font-weight="500" fill="#f7d58f" text-anchor="middle">งวดประจำวันที่ {$thaiDate}</text>
    
    <rect x="75" y="370" width="1050" height="260" rx="12" fill="#fffaf0" />
    <text x="600" y="445" font-family="Kanit" font-size="55" font-weight="700" fill="#2a1a10" text-anchor="middle">รางวัลที่ 1</text>
    <text x="600" y="590" font-family="Kanit" font-size="180" font-weight="700" fill="#2a1a10" text-anchor="middle" letter-spacing="15">{$firstPrize}</text>

    <text x="243" y="740" font-family="Kanit" font-size="38" font-weight="700" fill="#fffaf0" text-anchor="middle">เลขหน้า 3 ตัว</text>
    <text x="600" y="740" font-family="Kanit" font-size="38" font-weight="700" fill="#fffaf0" text-anchor="middle">เลขท้าย 3 ตัว</text>
    <text x="957" y="740" font-family="Kanit" font-size="38" font-weight="700" fill="#fffaf0" text-anchor="middle">เลขท้าย 2 ตัว</text>
    
    <rect x="75" y="760" width="336" height="130" rx="10" fill="#fffaf0" />
    <text x="243" y="860" font-family="Kanit" font-size="85" font-weight="700" fill="#2a1a10" text-anchor="middle">{$frontThree[0]}</text>
    <rect x="75" y="910" width="336" height="130" rx="10" fill="#fffaf0" />
    <text x="243" y="1010" font-family="Kanit" font-size="85" font-weight="700" fill="#2a1a10" text-anchor="middle">{$frontThree[1]}</text>
    
    <rect x="432" y="760" width="336" height="130" rx="10" fill="#fffaf0" />
    <text x="600" y="860" font-family="Kanit" font-size="85" font-weight="700" fill="#2a1a10" text-anchor="middle">{$backThree[0]}</text>
    <rect x="432" y="910" width="336" height="130" rx="10" fill="#fffaf0" />
    <text x="600" y="1010" font-family="Kanit" font-size="85" font-weight="700" fill="#2a1a10" text-anchor="middle">{$backThree[1]}</text>
    
    <rect x="789" y="760" width="336" height="280" rx="10" fill="#fffaf0" />
    <text x="957" y="960" font-family="Kanit" font-size="150" font-weight="700" fill="#2a1a10" text-anchor="middle">{$lastTwo}</text>
    
    <text x="600" y="1115" font-family="Kanit" font-size="28" font-weight="700" fill="#fff6e4" text-anchor="middle">SUPERNUMBER.CO.TH</text>
    <text x="600" y="1155" font-family="Kanit" font-size="20" font-weight="500" fill="#f5e4c4" text-anchor="middle">Web : www.supernumber.co.th Tel : 0963232656, 0963232665 Line : @supernumber</text>
</svg>
SVG;
    }

    public function generateLandscapeSvg(LotteryResult $result): string
    {
        $drawDate = $result->source_draw_date ?? $result->draw_date ?? now('Asia/Bangkok');
        $thaiDate = $this->toThaiDateLabel($drawDate->copy());

        $fontData = $this->getFontBase64('Kanit-700.ttf');

        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="1200" height="630" viewBox="0 0 1200 630" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <style>
            @font-face { font-family: 'Kanit'; font-weight: 700; src: url(data:font/ttf;base64,{$fontData}); }
        </style>
        <radialGradient id="bgGrad" cx="50%" cy="15%" r="85%" fx="50%" fy="15%">
            <stop offset="0%" stop-color="#5a4227" />
            <stop offset="40%" stop-color="#261a11" />
            <stop offset="100%" stop-color="#100a06" />
        </radialGradient>
        <linearGradient id="diagonalFade" x1="965" y1="0" x2="910" y2="315" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#f5c76d" stop-opacity="0.32" />
            <stop offset="65%" stop-color="#f5c76d" stop-opacity="0.14" />
            <stop offset="100%" stop-color="#f5c76d" stop-opacity="0" />
        </linearGradient>
    </defs>
    <rect width="1200" height="630" fill="url(#bgGrad)" />
    <line x1="965" y1="0" x2="910" y2="315" stroke="url(#diagonalFade)" stroke-width="2" />
    <rect x="6" y="6" width="1188" height="618" fill="none" stroke="#c59d62" stroke-width="3" />
    <rect x="38" y="38" width="1124" height="554" fill="none" stroke="#ba8e4d" stroke-width="2" opacity="0.4" />

    <line x1="120" y1="124" x2="520" y2="124" stroke="#c59d62" stroke-width="2" opacity="0.8" />
    <line x1="680" y1="124" x2="1080" y2="124" stroke="#c59d62" stroke-width="2" opacity="0.8" />

    <rect x="548" y="72" width="104" height="104" fill="#2a1a10" stroke="#d7a64e" stroke-width="3" />
    <text x="600" y="154" font-family="'Times New Roman', serif" font-size="88" font-weight="900" fill="#f5c76d" text-anchor="middle">S</text>
    <text x="600" y="235" font-family="Kanit" font-size="36" font-weight="700" fill="#f7d58f" text-anchor="middle" letter-spacing="8">SUPERNUMBER</text>

    <text x="600" y="360" font-family="Kanit" font-size="52" font-weight="700" fill="#120907" stroke="#120907" stroke-width="8" stroke-linejoin="round" text-anchor="middle">สลากกินแบ่งรัฐบาลล่าสุด</text>
    <text x="600" y="360" font-family="Kanit" font-size="52" font-weight="700" fill="#ffffff" text-anchor="middle">สลากกินแบ่งรัฐบาลล่าสุด</text>

    <text x="600" y="470" font-family="Kanit" font-size="68" font-weight="700" fill="#120907" stroke="#120907" stroke-width="9" stroke-linejoin="round" text-anchor="middle">งวดประจำวันที่ {$thaiDate}</text>
    <text x="600" y="470" font-family="Kanit" font-size="68" font-weight="700" fill="#f7d58f" text-anchor="middle">งวดประจำวันที่ {$thaiDate}</text>

    <rect x="360" y="542" width="480" height="70" fill="#160c08" />
    <text x="600" y="592" font-family="Kanit" font-size="44" font-weight="700" fill="#ffffff" text-anchor="middle">supernumber.co.th</text>
</svg>
SVG;
    }

    private function getFontBase64(string $filename): string
    {
        $path = $this->fontPath($filename);
        if (is_file($path)) {
            return base64_encode(file_get_contents($path));
        }
        return '';
    }
}
