# Article System

ระบบจัดการบทความของ Supernumber รองรับทั้งบทความทั่วไปและบทความหวยอัตโนมัติ

---

## Article Model

ตาราง `articles` — fields หลักที่เกี่ยวข้อง:

| Field | ประเภท | คำอธิบาย |
|-------|--------|----------|
| `title` | string | หัวข้อบทความ |
| `slug` | string | URL slug |
| `is_published` | boolean | เผยแพร่แล้วหรือไม่ |
| `published_at` | timestamp | เวลาเผยแพร่ (null = ทันที) |
| `is_auto_post` | boolean | ให้ระบบ auto-post ไป Facebook/LINE หรือไม่ |
| `notified_at` | timestamp | เวลาที่ส่ง notification แล้ว (null = ยังไม่ส่ง) |
| `is_line_broadcasted` | boolean | broadcast ไป LINE followers แล้วหรือยัง |
| `cover_image_path` | string | path รูปปก (square SVG/PNG) |
| `cover_image_square_path` | string | path รูป square 1:1 |
| `cover_image_landscape_path` | string | path รูป landscape 16:9 |

---

## Auto-Publish และ LINE Notification

### Cron Trigger

Web cron เรียก endpoint ทุกนาที:

```
GET https://www.supernumber.co.th/cron/publish/{CRON_SECRET}
```

Route นี้ call: `php artisan articles:publish-scheduled`

### Command: `articles:publish-scheduled`

`app/Console/Commands/PublishScheduledArticles.php`

**ค้นหาบทความที่ต้องประมวลผล:**

```
is_auto_post = true
AND notified_at IS NULL
AND (
  (is_published = true AND published_at <= now())
  OR (is_published = false AND published_at <= now())  ← Draft ที่ถึงเวลาแล้ว
)
```

**สิ่งที่ทำ (ใน DB transaction):**

1. Set `notified_at = now()` (atomic — ป้องกัน duplicate จาก parallel run)
2. ถ้า Draft → flip `is_published = true`
3. Post ไป Facebook Page ผ่าน `FacebookPagePoster::postArticle()`
4. ส่ง LINE notification ผ่าน `LineNotifier::queueText('article_published', $message)`

**ข้อความ LINE ที่ส่ง:**

```
📢 เผยแพร่บทความใหม่แล้ว!

หัวข้อ: {title}
แชร์ไปที่ Facebook Page: สำเร็จ ✅ / ไม่สำเร็จ ❌

{article_url}
```

---

## Manual Share จาก Admin Panel (`/admin/articles`)

### ปุ่มที่มีในแต่ละ row (published article)

| ปุ่ม | สี | Action |
|------|-----|--------|
| แชร์ (Facebook) | น้ำเงิน `#1877F2` | แชร์ไป Facebook Page พร้อมรูป |
| LINE | เขียว `#06C755` | ส่งเข้า LINE Group |
| ลบ | แดง | ลบบทความ (manager/admin เท่านั้น) |

---

## Facebook Share Flow

### บทความหวย (slug ตรงกับ `thai-goverment-lottery-{YYYYMM}(first|second)`)

1. ตรวจสอบว่าหวยออกครบ 100% (`$lotteryIsComplete`) — ถ้าไม่ครบ จะ block
2. Browser render SVG → PNG ด้วย Canvg (CDN: `cdn.jsdelivr.net/npm/canvg@3.0.10`)
   - ดึง SVG ผ่าน proxy: `GET /admin/articles/get-svg-proxy?path={svgPath}`
   - inject font Kanit-700 จาก `/fonts/Kanit-700.ttf`
   - วาดลง `<canvas id="render-canvas">` ขนาด 1200×1200px
   - แปลง canvas → PNG base64
3. Upload PNG: `POST /admin/articles/{id}/upload-rendered-image`
   - server บันทึกไฟล์ลง `storage/app/public/`
   - อัปเดต `cover_image_square_path` ใน DB
4. Form submit → `POST /admin/articles/{article}/share-social`
5. `FacebookPagePoster::postArticle()` → Facebook Graph API v19.0 `/photos`

### บทความทั่วไป

- Form submit โดยตรง (ไม่ต้อง render)
- ใช้ `cover_image_landscape_path` หรือ `cover_image_path`
- ต้องเป็นไฟล์ `.png` หรือ `.jpg` — SVG จะถูก abort

### Error Handling

- Render fail → alert + `POST /admin/articles/{id}/report-render-error` → แจ้ง admin ผ่าน LINE
- Facebook API fail → แสดง error message บนหน้า

---

## LINE Group Share Flow

Route: `POST /admin/articles/{article}/share-line` (admin.articles.share-line)

### บทความหวย

ค้นหา `LotteryResult` จาก `published_at` date หรือ slug pattern แล้วส่งผ่าน:

```php
app(LineLotteryNotifier::class)->sendCompleted($lotteryResult, $manualImageUrl);
```

ส่งผลหวยครบ (รางวัลที่ 1, เลขหน้า/ท้าย 3 ตัว ฯลฯ) + รูป

### บทความทั่วไป

```php
app(LineNotifier::class)->queueMessages('article_shared', [
    ['type' => 'image', ...],   // ถ้ามีรูป
    ['type' => 'text', 'text' => "📝 บทความใหม่\n{title}\n\n{url}"],
]);
```

ส่งชื่อบทความ + ลิงก์ + รูปปก (ถ้ามี) เข้า default LINE Group

---

## LINE Broadcast (ส่งหาผู้ติดตาม OA ทุกคน)

Route: `POST /admin/articles/{article}/broadcast-line` (admin.articles.broadcast-line)

- ส่งผ่าน `LineNotifier::queueBroadcastMessages('article_broadcast', $messages)`
- ใช้ LINE Messaging API endpoint `/v2/bot/message/broadcast`
- บันทึก `is_line_broadcasted = true` หลังส่งสำเร็จ (ป้องกัน duplicate)

---

## DOM Elements ที่จำเป็นสำหรับ JS Render

ต้องมีใน HTML ของหน้า admin/articles (อยู่ก่อน `@endsection`):

```html
<div id="render-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); z-index:9999; ...">
  <p id="render-status" style="color:#fff; ..."></p>
</div>
<canvas id="render-canvas" style="display:none;"></canvas>
```

หากขาด elements เหล่านี้ → JS crash ที่ `canvas.getContext('2d')` → `renderAndShareSocial` ไม่ถูก define → ปุ่มแชร์ทั้งหมดใช้ไม่ได้

---

## ENV ที่เกี่ยวข้อง

```env
# Facebook
FACEBOOK_PAGE_ID=...
FACEBOOK_PAGE_ACCESS_TOKEN=...

# LINE
LINE_CHANNEL_ACCESS_TOKEN=...
LINE_GROUP_ID=C...
LINE_LOTTERY_GROUP_ID=C...      # ถ้าว่าง ใช้ LINE_GROUP_ID
LINE_NOTIFICATION_TEST_MODE=false
LINE_ADMIN_USER_ID=

# Cron
CRON_SECRET=supernumber_secret_789
```
