# Lottery Images (SVG)

This project generates 2 SVG cover images for each Thai Government Lottery draw:

- `square` (1200x1200): includes prize numbers (for sharing / result preview).
- `landscape` (1200x630): cover-style image that only shows the draw date (no prize numbers).

The SVGs are embedded-font SVG (Kanit) and are stored on the `public` disk under the article directory.

## Where The SVGs Are Generated

- Generator: `app/Services/LineLotteryImageService.php`
  - `generateSquareSvg(LotteryResult $result)`
  - `generateLandscapeSvg(LotteryResult $result)`
- Sync flow (creates/updates the deterministic lottery article and writes SVG files):
  - `app/Console/Commands/FetchLatestLotteryCommand.php` (`syncLotteryArticleCover()`)

## Output Files / Paths

During `lottery:fetch-latest`, the command writes SVGs to a folder like:

- `storage/app/public/articles/{YYYY}/{slug}/`

Filenames:

- `{slug}_square.svg`
- `{slug}_landscape.svg`

The article stores the paths in:

- `articles.cover_image_square_path` (usually points to the square SVG unless overridden by a PNG upload)
- `articles.cover_image_landscape_path` (usually points to the landscape SVG unless overridden by a PNG upload)

## Square (Result) SVG

Intent:

- Show: `ผลสลากกินแบ่งรัฐบาล` + `งวดประจำวันที่ {thaiDate}`
- Show prize numbers:
  - `รางวัลที่ 1`
  - `เลขหน้า 3 ตัว` (2 numbers)
  - `เลขท้าย 3 ตัว` (2 numbers)
  - `เลขท้าย 2 ตัว`

Code reference:

- `app/Services/LineLotteryImageService.php:486` (`generateSquareSvg`)

Minimal template example (structure only, numbers omitted for brevity):

```xml
<svg width="1200" height="1200" viewBox="0 0 1200 1200" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <style>
      @font-face { font-family: 'Kanit'; font-weight: 700; src: url(data:font/ttf;base64,${KANIT_700}); }
      @font-face { font-family: 'Kanit'; font-weight: 500; src: url(data:font/ttf;base64,${KANIT_500}); }
    </style>
    <radialGradient id="bgGrad" cx="50%" cy="15%" r="85%">
      <stop offset="0%" stop-color="#5a4227"/>
      <stop offset="40%" stop-color="#261a11"/>
      <stop offset="100%" stop-color="#100a06"/>
    </radialGradient>
  </defs>

  <rect width="1200" height="1200" fill="url(#bgGrad)"/>
  <!-- border / brand -->
  <text x="600" y="280" font-family="Kanit" font-size="64" font-weight="700" fill="#fff" text-anchor="middle">ผลสลากกินแบ่งรัฐบาล</text>
  <text x="600" y="335" font-family="Kanit" font-size="34" font-weight="500" fill="#f7d58f" text-anchor="middle">งวดประจำวันที่ ${THAI_DATE}</text>

  <!-- prize panels -->
  <text x="600" y="445" font-family="Kanit" font-size="55" font-weight="700" fill="#2a1a10" text-anchor="middle">รางวัลที่ 1</text>
  <text x="600" y="590" font-family="Kanit" font-size="180" font-weight="700" fill="#2a1a10" text-anchor="middle">${FIRST_PRIZE}</text>
  <!-- ... front/back 3 digits + last 2 digits panels ... -->
</svg>
```

## Landscape (Cover) SVG

Intent:

- Show: `สลากกินแบ่งรัฐบาลล่าสุด`
- Show: `งวดประจำวันที่ {thaiDate}`
- Do NOT show any prize numbers.

Code reference:

- `app/Services/LineLotteryImageService.php:558` (`generateLandscapeSvg`)

Minimal template example (structure only):

```xml
<svg width="1200" height="630" viewBox="0 0 1200 630" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <style>
      @font-face { font-family: 'Kanit'; font-weight: 700; src: url(data:font/ttf;base64,${KANIT_700}); }
    </style>
    <radialGradient id="bgGrad" cx="50%" cy="15%" r="85%">
      <stop offset="0%" stop-color="#5a4227"/>
      <stop offset="40%" stop-color="#261a11"/>
      <stop offset="100%" stop-color="#100a06"/>
    </radialGradient>
    <!-- diagonal line: only top-half, fades out -->
    <linearGradient id="diagonalFade" x1="965" y1="0" x2="910" y2="315" gradientUnits="userSpaceOnUse">
      <stop offset="0%" stop-color="#f5c76d" stop-opacity="0.32"/>
      <stop offset="65%" stop-color="#f5c76d" stop-opacity="0.14"/>
      <stop offset="100%" stop-color="#f5c76d" stop-opacity="0"/>
    </linearGradient>
  </defs>

  <rect width="1200" height="630" fill="url(#bgGrad)"/>
  <line x1="965" y1="0" x2="910" y2="315" stroke="url(#diagonalFade)" stroke-width="2"/>

  <text x="600" y="360" font-family="Kanit" font-size="52" font-weight="700" fill="#fff" text-anchor="middle">สลากกินแบ่งรัฐบาลล่าสุด</text>
  <text x="600" y="470" font-family="Kanit" font-size="68" font-weight="700" fill="#f7d58f" text-anchor="middle">งวดประจำวันที่ ${THAI_DATE}</text>
  <text x="600" y="592" font-family="Kanit" font-size="44" font-weight="700" fill="#fff" text-anchor="middle">supernumber.co.th</text>
</svg>
```

## Example PHP Usage

```php
use App\Services\LineLotteryImageService;

$service = app(LineLotteryImageService::class);

$squareSvg = $service->generateSquareSvg($lotteryResult);
$landscapeSvg = $service->generateLandscapeSvg($lotteryResult);
```

