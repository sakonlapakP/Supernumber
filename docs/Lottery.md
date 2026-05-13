# Lottery System Documentation

ระบบการจัดการผลสลากกินแบ่งรัฐบาล (Government Lottery Results) ของ Supernumber

## ภาพรวม

ระบบ Lottery เป็นชุดอัตโนมัติเพื่อดึงผลหวยจากเว็บไซต์ Government Lottery Organization (GLO) แล้วสร้างบทความและรูปภาพครอบปัญหา จากนั้นประกาศผ่าน LINE ให้ผู้ดูแลและกลุ่มสมาชิก

**หลักการสำคัญ:**
- ทำงานอัตโนมัติ 2 ครั้งต่อเดือน (วันที่ 1–2 และ 16–17)
- ตรงเวลา 15:45–16:20 น. เวลากรุงเทพ
- สร้างรูปภาพ SVG + PNG พร้อมตัวเลขรางวัล
- ส่งแจ้งเตือน LINE อัตโนมัติ

---

## โครงสร้างข้อมูล

### ตาราหลัก: `lottery_results`

เก็บผลลัพธ์หวยแต่ละครั้ง:

```
id                   | bigint PK
draw_date            | DATE UNIQUE           -- วันรางวัลตามระบบเรา (YYYY-MM-DD)
source_draw_date     | DATE NULLABLE         -- วันรางวัลจาก API (GLO)
source_draw_date_text| VARCHAR NULLABLE      -- วันรางวัลข้อความดั้งเดิม
is_complete          | BOOLEAN (default 0)  -- ข้อมูลครบครันหรือยัง
fetched_at           | TIMESTAMP NULLABLE    -- เวลาที่ดึงข้อมูลล่าสุด
source_payload       | JSON NULLABLE         -- Response จาก GLO API (audit trail)
created_at, updated_at| TIMESTAMP
```

**Indexes:**
- `(is_complete, draw_date)` — ค้นหาผลลัพธ์ที่ยังไม่สมบูรณ์

### ตารางรอง: `lottery_result_prizes`

เก็บรายการรางวัลแต่ละรายการภายใต้ผลลัพธ์หนึ่ง:

```
id                   | bigint PK
lottery_result_id    | bigint FK (cascade delete)
position             | unsigned int (default 0)    -- ลำดับแสดง
prize_name           | VARCHAR                      -- "รางวัลที่ 1", "เลขท้าย 2 ตัว" etc.
prize_number         | VARCHAR (max 20)             -- หมายเลขรางวัล
created_at, updated_at| TIMESTAMP
```

**Unique constraint:** `(lottery_result_id, prize_name, prize_number)` — ไม่ซ้ำกัน

---

## วิธีการทำงาน

### 1. Scheduled Fetch Loop

**ตำแหน่ง:** `routes/console.php`

```
Every 5 minutes
├─ Between 15:45–16:20 (Asia/Bangkok)
├─ On days: 1, 2, 16, 17 of month
└─ Without overlapping (locked)
```

**คำสั่ง:** `php artisan lottery:fetch-latest`

### 2. Fetch and Validation Flow

```
┌─ lottery:fetch-latest command ─────────────────┐
│                                                 │
│ 1. Check schedule window (15:45–16:20 BKT)    │
│    ├─ If not in window & !--force → SKIP      │
│    └─ If in window → Continue                 │
│                                                 │
│ 2. POST to GLO API (with 3 retries)            │
│    └─ https://www.glo.or.th/api/lottery/...   │
│                                                 │
│ 3. Extract draw date from response             │
│    ├─ If API date ≠ our target date            │
│    │  and difference ≤ 5 days (holiday shift)  │
│    │  → Use API date instead                   │
│    └─ Else SKIP                                │
│                                                 │
│ 4. Extract 6 prizes:                           │
│    ├─ รางวัลที่ 1 (1 number)                   │
│    ├─ เลขหน้า 3 ตัว (2 numbers)                │
│    ├─ เลขท้าย 3 ตัว (2 numbers)                │
│    └─ เลขท้าย 2 ตัว (1 number)                 │
│                                                 │
│ 5. Validate completeness                       │
│    ├─ Must have all 6 prize categories         │
│    └─ If partial & already complete → SKIP     │
│                                                 │
│ 6. Save to DB:                                 │
│    ├─ lottery_results record                   │
│    └─ lottery_result_prizes children           │
│                                                 │
│ 7. Generate article & cover images:            │
│    ├─ Generate SVG (1200x1200px square)        │
│    ├─ Generate SVG (1200x630px landscape)      │
│    ├─ Generate PNG fallback (if GD available)  │
│    └─ Save to storage/app/public/articles/...  │
│                                                 │
│ 8. Send LINE notifications (if new complete):  │
│    ├─ Admin: "article ready, check admin page" │
│    └─ Lottery Group: Results with image        │
│                                                 │
│ 9. Retry Day End Logic (2nd/17th at 16:20+):   │
│    └─ If still no data → send "unavailable"    │
│                                                 │
└─────────────────────────────────────────────────┘
```

### 3. Article Resolution

เมื่อผู้ใช้เข้าดูหน้า lottery article:

```
1. Match URL slug ของ article:
   └─ Pattern: thai-goverment-lottery-{YYYYMM}{first|second}

2. Query lottery_results โดย:
   ├─ Year = from slug
   ├─ Month = from slug
   ├─ draw_date day:
   │  ├─ "first" half (1–15)
   │  └─ "second" half (16–31)
   └─ Order by latest: source_draw_date DESC, fetched_at DESC, id DESC

3. Render article with lottery result:
   ├─ Text prizes in content
   ├─ Cover image (SVG or PNG)
   ├─ HTML fallback if image unavailable
   └─ (Admin simulation: ?simulate_lottery=1 if authenticated admin/manager)
```

---

## Models & Services

### Model: `LotteryResult`
- **Relation:** `hasMany(LotteryResultPrize)` via `prizes()`
- **Traits:** `UnixTimestampSerializable`
- **File:** `app/Models/LotteryResult.php`

### Model: `LotteryResultPrize`
- **Relation:** `belongsTo(LotteryResult)`
- **File:** `app/Models/LotteryResultPrize.php`

### Service: `LineLotteryImageService`
- **วิธีการ:**
  - `buildLineImageUrl(LotteryResult)` — สร้างลิงก์ลายเซ็นสำหรับ LINE
  - `generateSquareSvg(LotteryResult)` — SVG 1200×1200px
  - `generateLandscapeSvg(LotteryResult)` — SVG 1200×630px
  - `renderFallbackPng(LotteryResult)` — PNG fallback (ถ้ามี GD)
  - `canServeImage(LotteryResult)` — ตรวจสอบว่ามีรูปให้บริการ
  - `toResponse(LotteryResult)` — ส่งรูปกลับให้ browser

- **File:** `app/Services/LineLotteryImageService.php`

### Service: `LineLotteryNotifier`
- **วิธีการ:**
  - `sendCompleted(LotteryResult, ?manualImageUrl)` — ส่งหวยเสร็จสมบูรณ์ไป LINE
  - `notifyAdminArticleReady(Article, ?Carbon)` — แจ้งแอดมิน
  - `sendUnavailableAfterRetryWindow(LotteryResult, scheduledDate, checkedAt)` — แจ้งว่ายังไม่มีข้อมูล

- **File:** `app/Services/LineLotteryNotifier.php`

---

## Routes & API

### Public Routes

**GET** `/line/lottery-results/{lotteryResult}/image`
- Signed route (HMAC protection)
- Query params: `?format=png|svg`
- Returns: PNG/SVG, cached 5 minutes
- Name: `line.lottery-result-image`

**GET** `/articles/{slug}` (Public Controller)
- Renders article with lottery result
- Query: `?simulate_lottery=1` (admin/manager only)
- View: `articles.show` with `$lotteryResult`

### Admin Routes

**POST** `/admin/lottery/fetch-force`
- Manually trigger `lottery:fetch-latest --force`
- Bypasses schedule window checks
- Restricted: `ROLE_MANAGER` only
- Name: `lottery.fetch-force`

**POST** `/admin/articles/auto-gen-lottery`
- Auto-generate lottery article + images
- Restricted: `ROLE_MANAGER` only

**POST** `/admin/line-settings/group-id`
- Update LINE group ID for notifications

---

## Configuration

### Environment Variables

```php
// LINE API
LINE_CHANNEL_ACCESS_TOKEN=xxx
LINE_CHANNEL_SECRET=xxx
LINE_LOTTERY_GROUP_ID=C1234567...    // LINE Group ID for lottery notifications

// Lottery Message Templates
LOTTERY_MSG_LINE="ผลหวยออกแล้ว\n..."
LOTTERY_MSG_FB="📝 ผลสลากกินแบ่งรัฐบาล..."
LOTTERY_MSG_FOOTER="..."
```

### Config File: `config/services.php`

```php
'lottery' => [
    'article_footer' => env('LOTTERY_MSG_FOOTER', '...'),
    'line_template' => env('LOTTERY_MSG_LINE', 'ผลหวยออกแล้ว\n...'),
    'fb_template' => env('LOTTERY_MSG_FB', '📝 ผลสลากกินแบ่งรัฐบาล...'),
],
'line' => [
    'groups' => [
        'lottery' => env('LINE_LOTTERY_GROUP_ID'),
        'admin' => env('LINE_ADMIN_GROUP_ID'),
    ]
]
```

---

## Image Generation

### SVG Output

- **Location:** `LineLotteryImageService::generateSquareSvg()` / `generateLandscapeSvg()`
- **Fonts:** Kanit (embedded base64 in SVG)
- **Size:** 1200×1200px (square) or 1200×630px (landscape)
- **Content:** Prize names + numbers, styled Thai layout
- **Storage:** `storage/app/public/articles/{year}/{slug}/` + `_square.svg` / `_landscape.svg`

### PNG Fallback

- **Requires:** PHP GD library (`extension_loaded('gd')`)
- **Method:** GD drawing API (text + rectangles)
- **Size:** 1200×1200px
- **Storage:** `storage/app/public/articles/{year}/{slug}/{slug}.png`
- **When:** Auto-generated during lottery fetch if GD available

### Protection

- **Manual uploads:** If admin uploads PNG manually, auto-generated SVG won't overwrite it
- **Check:** File suffix `.png` blocks SVG path updates
- **Use case:** Premium/custom cover images

---

## LINE Notifications

### Message Template Variables

Use `{placeholder}` in `LOTTERY_MSG_LINE`:

```
{draw_date}      -> "14/05/2026" (short date)
{thai_draw_date} -> "14 พฤษภาคม 2569" (Thai full date)
{first_prize}    -> "123456" (single number)
{front_three}    -> "123 456" (2 numbers separated)
{back_three}     -> "789 012" (2 numbers separated)
{last_two}       -> "34" (single number)
{near_first}     -> "123455 123457" (near numbers if available)
```

### Example Message

```
ผลสลากกินแบ่งรัฐบาล งวด 14 พฤษภาคม 2569

🎰 รางวัลที่ 1: 123456
🔢 เลขหน้า 3 ตัว: 123 456
🔢 เลขท้าย 3 ตัว: 789 012
🔢 เลขท้าย 2 ตัว: 34
```

---

## Common Operations

### Force Fetch (Manual)

```bash
# From CLI
php artisan lottery:fetch-latest --force

# From Admin Panel
POST /admin/lottery/fetch-force
```

**Use case:** Fetch before 15:45 or after 16:20, or override completeness check

### Check Latest Results

```bash
# CLI
php artisan tinker
>>> LotteryResult::with('prizes')->latest('draw_date')->first();

# HTTP simulation preview
GET /articles/thai-goverment-lottery-202605first?simulate_lottery=1
# (requires admin auth)
```

### Update LINE Group

**File:** `/app/Http/Controllers/Admin/LineSettingsController.php`

```bash
POST /admin/line-settings/group-id
Body: { "group_id": "C1234567..." }
```

### Check Image Availability

**Service:** `LineLotteryImageService::canServeImage(LotteryResult)`

```php
$service = app(LineLotteryImageService::class);
if ($service->canServeImage($result)) {
    $url = $service->buildLineImageUrl($result);
}
```

---

## Troubleshooting

### ❌ "Skipped: outside schedule window"

**ปัญหา:** ระบบไม่ดึงข้อมูล แม้ว่าเป็นวันหวย

**เหตุผล:** อยู่นอกช่วง 15:45–16:20 BKT

**วิธีแก้:** ใช้ `--force` flag
```bash
php artisan lottery:fetch-latest --force
```

### ❌ "Attempted to overwrite complete result with partial data"

**ปัญหา:** ผลลัพธ์ที่ได้มาจาก API ไม่สมบูรณ์ ระบบปฏิเสธการบันทึก

**เหตุผล:** ถ้าได้บันทึกผลลัพธ์สมบูรณ์แล้ว จะไม่ยอมให้ partial data เขียนทับ (ป้องกันความเสี่ยง)

**วิธีแก้:** 
1. รอให้ GLO ปล่อยข้อมูลสมบูรณ์
2. หรือลบ `lottery_results` record และเรียก `--force` ใหม่

### ❌ "Failed to send lottery completion notifications"

**ปัญหา:** ส่งแจ้งเตือน LINE ล้มเหลว ข้อมูลบันทึกสำเร็จ แต่ไม่มีการแจ้งเตือน

**เหตุผล:** ปัญหากับ LINE API (token หรือ group ID ผิด)

**เช็ก:**
```bash
# ตรวจสอบ LINE config
env | grep LINE_

# ดูลอก
tail -f storage/logs/laravel.log | grep LINE
```

### ❌ "PNG fallback not available"

**ปัญหา:** ไม่มี PNG fallback image แม้ว่า SVG มีอยู่

**เหตุผล:** PHP GD library ไม่ติดตั้ง

**เช็ก:**
```bash
php -m | grep gd
# หรือ
php -r "echo extension_loaded('gd') ? 'GD enabled' : 'GD disabled';"
```

**แก้:** ติดตั้ง PHP GD extension
```bash
# macOS (brew)
brew install php-gd

# Ubuntu/Debian
sudo apt-get install php8.4-gd
```

### ❌ "LINE image returns 404"

**ปัญหา:** `/line/lottery-results/{id}/image` คืน 404

**เหตุผล:** รูปภาพไม่มีในที่เก็บ หรือไม่สามารถเรนเดอร์ได้

**วิธีแก้:**
1. ตรวจสอบที่เก็บ:
   ```bash
   ls -la storage/app/public/articles/
   ```

2. Re-generate images:
   ```bash
   php artisan lottery:fetch-latest --force
   ```

### ❌ "Lottery article slug not matching"

**ปัญหา:** ผลลัพธ์หวยไม่แสดงบนหน้า article

**เหตุผล:** Slug ไม่ตรง Pattern `thai-goverment-lottery-{YYYYMM}{first|second}`

**เช็ก:**
```bash
# Database
SELECT slug FROM articles WHERE slug LIKE '%lottery%';

# Expected format
thai-goverment-lottery-202605first   (days 1-15)
thai-goverment-lottery-202605second  (days 16-31)
```

---

## Performance Notes

- **Scheduled frequency:** Every 5 minutes (but only executes in 35-min window 15:45–16:20)
- **Database indexes:** `(is_complete, draw_date)` for quick completeness checks
- **Image caching:** 5-minute cache headers on public images
- **API retry:** 3 attempts with 2-second delay on transient failures
- **Overlap protection:** `withoutOverlapping()` prevents concurrent command runs

---

## Recent Changes

### May 2026

- ✅ Added HTTP retry logic (3 attempts) for GLO API failures
- ✅ Fixed retry-day notification timing (>= instead of exact minute)
- ✅ Improved PNG detection (GD library check instead of PATH env)
- ✅ Added auth guard to `simulate_lottery` parameter (admin/manager only)
- ✅ Removed test-specific comments from production code

---

## Related Files

| File | Purpose |
|------|---------|
| `app/Console/Commands/FetchLatestLotteryCommand.php` | Main fetch + sync command |
| `app/Services/LineLotteryImageService.php` | Image generation (SVG/PNG) |
| `app/Services/LineLotteryNotifier.php` | LINE notifications |
| `app/Http/Controllers/PublicController.php` | Article rendering |
| `app/Models/LotteryResult.php` | Model + relations |
| `routes/console.php` | Scheduler configuration |
| `config/services.php` | Lottery config + templates |
| `database/migrations/*lottery*.php` | Database schema |
| `resources/views/articles/show.blade.php` | Article detail view |
| `resources/views/articles/partials/lottery-cover-fallback.blade.php` | HTML fallback display |
| `tests/Feature/FetchLatestLotteryCommandTest.php` | Command tests |
| `tests/Feature/LotteryFlowRefinedTest.php` | Integration tests |

---

## Contact & Support

- **Questions?** Check logs: `storage/logs/laravel.log`
- **Issues?** Run diagnostic:
  ```bash
  php artisan lottery:fetch-latest --force
  ```
- **Configuration?** See `.env` file or `config/services.php`
