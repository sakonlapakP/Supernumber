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

---

## LINE Notification Flow

เมื่อผลหวยออก ระบบจะส่งข้อความเข้า LINE group อัตโนมัติผ่าน `LineLotteryNotifier`

### Trigger

`FetchLatestLotteryCommand` (`lottery:fetch-latest`) รันทุก 5 นาที ช่วง 15:45–16:20 น. (Asia/Bangkok)  
หากผลหวยครบแล้ว จะ trigger:

```php
app(LineLotteryNotifier::class)->notifyAdminArticleReady($article, $targetDate);
app(LineLotteryNotifier::class)->sendCompleted($result);
```

### Services

| Service | Method | ส่งไปที่ | destinationKey |
|---------|--------|----------|----------------|
| `LineLotteryNotifier` | `sendCompleted()` | LINE Lottery Group | `lottery` |
| `LineLotteryNotifier` | `notifyAdminArticleReady()` | LINE Admin Group | `admin` |
| `LineLotteryNotifier` | `sendUnavailableAfterRetryWindow()` | LINE Lottery Group | `lottery` |

- `destinationKey` resolve จาก `config('services.line.groups.{key}')` → fallback เป็น `LINE_GROUP_ID` ถ้าไม่มี
- ส่งผ่าน `LineNotifier::queueMessages()` → บันทึกลง `line_notification_logs` → ส่งทันทีผ่าน LINE Messaging API (push)

### ข้อความที่ส่ง (`sendCompleted`)

- Text: ผลรางวัลครบ (รางวัลที่ 1, เลขหน้า/ท้าย 3 ตัว, เลขท้าย 2 ตัว, ข้างเคียง) — template จาก `config('services.lottery.line_template')`
- Image: รูป square PNG/SVG ที่ upload แล้ว (ถ้ามี `manualImageUrl`) หรือ fallback จาก `LineLotteryImageService::buildLineImageUrl()`

### Manual Share จาก Admin Panel

ใน `/admin/articles` มีปุ่ม **LINE** สีเขียว (สำหรับบทความที่ published แล้ว):

- **บทความหวย** (slug ตรงกับ `thai-goverment-lottery-{YYYYMM}(first|second)`): ส่งผลหวยครบผ่าน `LineLotteryNotifier::sendCompleted()`
- **บทความทั่วไป**: ส่งชื่อบทความ + URL ผ่าน `LineNotifier::queueMessages('article_shared', ...)`

Route: `POST /admin/articles/{article}/share-line` → `admin.articles.share-line`

### ENV ที่เกี่ยวข้อง

```env
LINE_CHANNEL_ACCESS_TOKEN=...
LINE_GROUP_ID=C...              # กลุ่มหลัก (fallback)
LINE_LOTTERY_GROUP_ID=C...      # กลุ่มประกาศหวย (ถ้าว่าง ใช้ LINE_GROUP_ID)
LINE_NOTIFICATION_TEST_MODE=false
LINE_ADMIN_USER_ID=             # ถ้า test mode เปิด จะส่งหาคนนี้แทน
```

---

## Facebook Share Flow (Admin Manual)

ปุ่ม **แชร์** (สีน้ำเงิน) ใน `/admin/articles` สำหรับบทความหวยที่ผลครบแล้ว

### ขั้นตอน

1. Admin กดปุ่มแชร์ → `renderAndShareSocial()` ตรวจว่าหวยออกครบ 100% (`$lotteryIsComplete`)
2. ถ้า cover image เป็น SVG → browser วาดรูปด้วย Canvg library (CDN)
   - ดึง SVG ผ่าน `/admin/articles/get-svg-proxy?path=...`
   - inject Kanit-700 font → render บน `<canvas>` 1200×1200
   - แปลงเป็น PNG base64 → POST ไป `/admin/articles/{id}/upload-rendered-image`
   - server บันทึก PNG ทับ SVG path → อัปเดต `cover_image_square_path` ใน DB
3. Form submit ไป `/admin/articles/{article}/share-social`
4. `FacebookPagePoster::postArticle()` detect บทความหวย → อ่านไฟล์ PNG จาก storage → upload ไป Facebook Graph API v19.0 `/photos`

### ข้อความ Facebook (บทความหวย)

```
ตรวจสลากกินแบ่งรัฐบาล
งวดประจำวันที่ {thai_draw_date}

ใครถูกรางวัลกันบ้าง โชคดี ร่ำรวย รับทรัพย์ กันทุกคน ครับ 🙏🙏🙏

สนใจเปลี่ยนเบอร์เสริมด้านการเงิน
Tel : 0963232656, 0963232665
Line : @supernumber

{article_url}
```

พร้อมรูป square PNG (1200×1200) ที่ render จาก SVG

config key: `services.lottery.fb_template_lottery` (env: `LOTTERY_MSG_FB_LOTTERY`)

### หลังจาก Facebook share เสร็จ

PNG file ที่ render ออกมาจาก browser ไม่ได้ save permanent ให้ FB API ใช้ได้ดี แต่ไฟล์ PNG จะอัปเดต DB:
- `cover_image_square_path` → PNG path ✓
- `cover_image_path` → PNG path ✓

หน้าบทความเดี๋ยวนี้จะแสดง PNG แทน SVG:
```php
$detailCoverCandidate = $article->cover_image_square_path ?: $article->cover_image_path;
// → ชี้ไปที่ PNG หลัง Facebook share เสร็จ
```

**LINE URL preview จะแสดงรูป PNG** โดยอัตโนมัติ (ผ่าน OG meta tag) ดังนั้นไม่ต้องแนบรูป — URL เพียงอย่างเดียวก็พอ

### ข้อกำหนด

- Image ต้องเป็น `.png` หรือ `.jpg` (ไม่ใช่ SVG) — หาก SVG ยังอยู่จะ abort
- ปุ่มแชร์จะ disabled ถ้าหวยยังออกไม่ครบ
- หาก render fail → แจ้ง admin ผ่าน LINE (`report-render-error`)

### ENV ที่เกี่ยวข้อง

```env
FACEBOOK_PAGE_ID=...
FACEBOOK_PAGE_ACCESS_TOKEN=...
```

Permissions ที่ต้องการ: `pages_manage_posts`, `pages_read_engagement`

---

## LINE Share Flow (Admin Manual)

ปุ่ม **LINE** (สีเขียว) ใน `/admin/articles` → Route: `POST /admin/articles/{article}/share-line`

### บทความหวย

ข้อความเดียวกับ Facebook lottery — ค้นหา Thai date จาก `LotteryResult` แล้วใส่ template:

```
ตรวจสลากกินแบ่งรัฐบาล
งวดประจำวันที่ {thai_draw_date}

ใครถูกรางวัลกันบ้าง โชคดี ร่ำรวย รับทรัพย์ กันทุกคน ครับ 🙏🙏🙏

สนใจเปลี่ยนเบอร์เสริมด้านการเงิน
Tel : 0963232656, 0963232665
Line : @supernumber

{article_url}
```

**ไม่มีรูปแนบ** — URL ใน LINE จะ preview รูป PNG โดยอัตโนมัติ (ผ่าน OG meta tag)
- หลังจาก Facebook share: `cover_image_square_path` → PNG
- OG image ก็เปลี่ยนจาก SVG → PNG
- LINE app แสดง PNG ให้เห็นเองโดยอัตโนมัติ

config key: `services.lottery.line_template_lottery_manual` (env: `LOTTERY_MSG_LINE_LOTTERY_MANUAL`)

### บทความทั่วไป

```
📝 บทความใหม่
{title}

{article_url}
```

**ไม่มีรูปแนบ** — ใช้ URL preview แทน

config key: `services.lottery.line_template_regular_manual` (env: `LOTTERY_MSG_LINE_REGULAR_MANUAL`)

