# AI Context: Supernumber Deep Dive

## 1. Business Logic: Lucky Numbers & Numerology
- **Analysis Logic:** ระบบคำนวณความหมายเบอร์มงคลโดยอ้างอิงจาก `PairMeaning` (คู่ลำดับเลข)
- **Status Workflow:** เบอร์จะมีสถานะ `active`, `sold`, `hold` ซึ่งจะสัมพันธ์กับ `CustomerOrder`
- **Importing:** มีระบบ Import เบอร์จาก CSV (เช่น `ImportTruePrepaidCsvCommand`) เพื่อความรวดเร็ว

## 2. Content & SEO System (ระบบบทความ - สำคัญมาก)
ระบบบทความของ Supernumber ถูกออกแบบมาเพื่อดึง Traffic จาก Google (SEO) เป็นหลัก:
- **Article Lifecycle:** มีระบบ AI Content Generation (ใช้ Gemini) เพื่อเขียนบทความอัตโนมัติ
- **SEO Elements:**
    - `lsi_keywords`: ใช้สำหรับใส่คีย์เวิร์ดรองเพื่อช่วยเรื่องอันดับ
    - `slug`: ต้อง Unique และถูกสร้างแบบ SEO-friendly (Slug-based routing)
    - `sanitizedContent`: มีการใช้ `ArticleContentSanitizer` เพื่อคุมคุณภาพ HTML
- **Media Management:** ระบบจัดเก็บรูปภาพบทความจะแยกตาม `/articles/{year}/{slug}` เพื่อความเป็นระเบียบ
- **Automation:** 
    - `is_auto_post`: ตั้งเวลาโพสต์อัตโนมัติ
    - `is_line_broadcasted`: ระบบจะยิงบทความใหม่เข้า LINE Group อัตโนมัติเมื่อโพสต์

## 3. Integrations & Notifications
- **LINE Messaging API:** แจ้งเตือนเมื่อมีออเดอร์ใหม่, ลูกค้าส่งแบบฟอร์มทำนายดวง (`EstimateLead`), และการบรอดแคสต์บทความ
- **GA4 Data API:** ระบบหลังบ้านมี Dashboard ดึงสถิติจาก Google Analytics 4 มาแสดงผลโดยใช้ Service Account
- **Turnstile:** ใช้ Cloudflare Turnstile เพื่อกัน Spam ในหน้า Contact และ Order

## 4. Coding Standards for AI
- **Models:** ห้ามลบ `casts()` หรือ Logic การคำนวณคะแนนเบอร์ใน `PhoneNumber`
- **Routes:** ศึกษารูปแบบใน `routes/web.php` ซึ่งมีการใช้ Callback และ Services แทน Controller ในบางจุดเพื่อความยืดหยุ่น
- **Translations:** เว็บรองรับภาษาไทยเป็นหลัก ดังนั้น Error Messages หรือ UI Content ต้องเป็นภาษาไทย
