# คู่มือเอกสาร Supernumber (TH)

> [!NOTE]
> เอกสารนี้เป็นคู่มือฉบับสมบูรณ์สำหรับทั้ง AI และนักพัฒนา โดยรวมบริบททางธุรกิจระดับสูงเข้ากับรายละเอียดทางเทคนิคเชิงลึก เพื่อให้แน่ใจว่าการพัฒนาและการดูแลโครงการ Supernumber เป็นไปในทิศทางเดียวกัน

---

## 1. ภาพรวมโปรเจกต์ (Project Overview)
- **ชื่อโปรเจกต์:** Supernumber
- **ธุรกิจหลัก:** ตลาดซื้อขายเบอร์โทรศัพท์มือถือระดับพรีเมียม/VIP โดยเน้นเรื่องเลขศาสตร์ (Numerology) และเนื้อหาที่ขับเคลื่อนด้วย SEO
- **กลุ่มเป้าหมายหลัก:** ลูกค้าชาวไทยที่มองหาเบอร์มงคล
- **ระบบจัดการ:** ประกอบด้วย Dashboard บนเว็บ และแอปพลิเคชันมือถือ Flutter สำหรับผู้จัดการ

### สรุปฟีเจอร์หลัก
- **เว็บไซต์สาธารณะ:** หน้าแรกแสดงเบอร์สุ่ม, แคตตาล็อกพร้อมตัวกรองขั้นสูง (ข้อความ, หน้ากากตัวเลข, ราคา, ประเภทบริการ), หน้าบทความพร้อมระบบคอมเมนต์, การฝังผลสลากกินแบ่งรัฐบาล และเครื่องมือวิเคราะห์เลขศาสตร์
- **แผงควบคุมแอดมิน:** ระบบจัดการบนเว็บสำหรับคลังสินค้า, คำสั่งซื้อ, ลูกค้า, เอกสารการขาย, สถิติ และการตั้งค่า LINE
- **แอปแอดมิน Flutter:** แอปมือถือที่ใช้ Token ในการยืนยันตัวตน สำหรับจัดการบทความ, วางแผนเนื้อหา และจัดการผู้ใช้
- **การเชื่อมต่อภายนอก:** LINE Messaging API (แจ้งเตือน/บรอดแคสต์), การโพสต์ลง Facebook Page, Google Analytics 4, Cloudflare Turnstile และ GLO Lottery API

---

## 2. ชุดเครื่องมือทางเทคนิค (Tech Stack)
- **Backend:** PHP 8.2+ / Laravel 12.0
- **Frontend (Web):** Blade Templates + Tailwind CSS v4 + Vite
- **Admin Mobile:** Flutter SDK ^3.11.3 (อยู่ที่โฟลเดอร์ `/admin_flutter`)
- **Database:** MySQL (จัดการผ่าน Migrations อย่างเคร่งครัด)
- **แพ็กเกจสำคัญ:** Laravel Sanctum (API Auth), Dio (Flutter HTTP), Provider (Flutter State), Axios, PHPUnit
- **การดำเนินงาน:** Laravel public storage, queues/jobs สำหรับแจ้งเตือน, scheduler สำหรับการเผยแพร่บทความและซิงค์หวย, GitHub Actions สำหรับ CI/CD

---

## 3. โครงสร้างแอปและโฟลเดอร์สำคัญ (App Structure)

### โฟลเดอร์หลัก
- `app/Models/`: เอนทิตีทางธุรกิจและตรรกะ (Eloquent models)
- `app/Services/`: การคำนวณที่ซับซ้อน, API wrappers ภายนอก และบริการทางธุรกิจ
- `app/Http/Controllers/`: ตัวควบคุม Laravel (สาธารณะ, API และแอดมิน)
- `app/Console/Commands/`: เครื่องมือ CLI สำหรับนำเข้าข้อมูล, ซิงค์หวย และการบำรุงรักษา
- `resources/views/`: Blade templates สำหรับหน้าเว็บ
- `admin_flutter/`: ซอร์สโค้ดสำหรับแอปแอดมิน Flutter
- `routes/`: การกำหนดเส้นทาง (`web.php`, `api.php`)
- `database/migrations/`: ประวัติโครงสร้างฐานข้อมูล
- `tests/`: การทดสอบ Feature และ Unit
- `scripts/`: สคริปต์สำหรับการ Deploy, Scheduler และ Automation

### โครงสร้าง Flutter Admin (`admin_flutter/lib`)
- `services/`: API client (Dio)
- `providers/`: การจัดการสถานะ (Auth, Article, User)
- `models/`: โมเดลข้อมูลสำหรับการ Serialize
- `screens/`: หน้าจอ UI (Login, Article List, Article Edit, JSON Import)
- `utils/`: ตัวช่วยต่างๆ เช่น การจัดรูปแบบวันที่

---

## 4. ตรรกะทางธุรกิจและกฎ (Business Logic)

### การจัดการเบอร์โทรศัพท์
- **โมเดล:** `app/Models/PhoneNumber.php`
- **ขั้นตอนสถานะ (Status Workflow):** `active` (ว่าง), `hold` (จอง), `sold` (ขายแล้ว)
- **ประเภทบริการ:** `prepaid` (เติมเงิน) และ `postpaid` (รายเดือน)
- **ผลรวมเลข:** คำนวณโดยอัตโนมัติเมื่อบันทึก
- **เลขศาสตร์ (Topics):** ตรรกะตาม "คู่เลข" (Pair Variants) โดยมีการจับคู่ไอคอนใน `TOPIC_ICON_MAP` และให้ความสำคัญกับคู่สุดท้ายมากกว่า

### การประมวลผลคำสั่งซื้อ
- **โมเดล:** `app/Models/CustomerOrder.php`
- **Flow:** การจองสร้างคำสั่งซื้อ -> ซิงค์สถานะไปยังเบอร์โทรศัพท์ -> เก็บสลิปการชำระเงิน -> อัปเดตสถานะ
- **การแจ้งเตือน:** ส่งการแจ้งเตือนผ่าน LINE สำหรับคำสั่งซื้อใหม่และการอัปเดตสถานะ

### ระบบบทความและเนื้อหา
- **เน้น SEO:** รวม `lsi_keywords`, การสร้าง Slug อัตโนมัติ และ Metadata
- **การทำความสะอาดเนื้อหา:** ใช้ `ArticleContentSanitizer` เพื่อรักษาคุณภาพ HTML
- **การเผยแพร่:** รองรับการตั้งเวลาเผยแพร่และการโพสต์อัตโนมัติไปยัง Facebook/LINE
- **การรวมข้อมูลหวย:** ดึงข้อมูลหวย GLO อัตโนมัติ, เก็บข้อมูลรางวัล และซิงค์บทความ

---

## 5. แผนผังเส้นทางและ API (Routes Map)

### เส้นทางสาธารณะ (Public Routes)
- `/`: หน้าแรก
- `/numbers`: การค้นหาและกรองแคตตาล็อก
- `/articles`: รายการและการอ่านบทความ
- `/evaluate` & `/evaluateBadNumber`: เครื่องมือเลขศาสตร์
- `/book`: การชำระเงินและส่งคำสั่งซื้อ
- `/line/webhook`: จุดรับข้อมูล LINE Messaging API

### แผงควบคุมแอดมินเว็บ (`/admin`)
- คลังสินค้า (`numbers`), คำสั่งซื้อ (`orders`), ลูกค้า (`customers`)
- เอกสารการขายและ PDF, Dashboard สถิติ
- การตั้งค่า LINE, ตัวดู Log, ตัวรันการทดสอบ (Test Runner)
- จัดการบทความ (CRUD), การวางแผนเนื้อหา, การตรวจสอบคอมเมนต์

### เส้นทาง API (`/api`)
- `POST /api/login`: ยืนยันตัวตนและรับ Token
- `GET /api/me`: ข้อมูลผู้ใช้ปัจจุบัน
- จัดการบทความ (CRUD), นำเข้า JSON, เครื่องมือ Preview/Share
- การจัดการผู้ใช้ (เฉพาะ Manager)
- `GET /api/tarot`: การดูดวงไพ่ยิปซีด้วย AI (จำกัดความถี่)

---

## 6. โครงสร้างฐานข้อมูล (ตารางสำคัญ)
- `users`: บัญชีแอดมิน/พนักงาน พร้อมบทบาท (`manager`, `admin`, `staff`)
- `phone_numbers`: คลังสินค้า, ราคา, สถานะ และข้อมูลเลขศาสตร์
- `customer_orders`: รายละเอียดการจอง, สลิป และสถานะ
- `articles`: เนื้อหา, Metadata และการตั้งเวลา
- `lottery_results` & `lottery_result_prizes`: ผลรางวัลหวย GLO
- `line_notification_logs` & `line_webhook_events`: การตรวจสอบการเชื่อมต่อ LINE
- `customers`: ข้อมูลติดต่อลูกค้าที่ใช้ซ้ำได้
- `sales_documents`: Metadata ของ PDF และการติดตามเอกสาร

---

## 7. มาตรฐานการเขียนโค้ดและขั้นตอนการทำงาน

### แนวทางการพัฒนา
1. **ภาษา:** UI, ข้อความแจ้งเตือน และเนื้อหาต้องเป็น **ภาษาไทย**
2. **การวางตรรกะ:** ย้ายตรรกะทางธุรกิจที่ซับซ้อนไปยัง **Services** (`app/Services`) หรือ **Traits**
3. **การออกแบบ:** ทำตามหลัก "Premium Design" (ใช้สี HSL, glassmorphism, ฟอนต์สมัยใหม่ เช่น 'Instrument Sans')
4. **ฐานข้อมูล:** ห้ามแก้ไขฐานข้อมูลโดยตรง ให้ใช้ Migrations เสมอ
5. **การทดสอบ:** รัน `php artisan test` ก่อนยืนยันการเปลี่ยนแปลงครั้งใหญ่

### ขั้นตอนการทำงาน
1. **วางแผน:** จัดทำแผนทีละขั้นตอนเป็นภาษาไทย
2. **ดำเนินการ:** แบ่งงานออกเป็นส่วนย่อยๆ
3. **แจ้งเตือน:** ตรวจสอบให้แน่ใจว่ามีการแจ้งเตือนผ่าน Telegram/LINE ในจุดที่เหมาะสม

---

## 8. ข้อมูลอ้างอิงทางเทคนิค (คลาสที่สำคัญ)

### บริการหลังบ้าน (Backend Services)
- `Ga4AnalyticsService`: ดึงข้อมูลสถิติจาก GA4
- `LineNotifier`: บริการหลักสำหรับส่ง LINE push/broadcast
- `ArticleContentSanitizer`: ทำความสะอาด HTML สำหรับบทความ
- `EstimateRecommendationService`: ตรรกะการจับคู่เบอร์โทรศัพท์
- `SalesDocumentPdfService`: สร้างไฟล์ PDF เอกสารการขาย
- `TarotAiService`: ตัวสร้าง Prompt สำหรับการดูดวงด้วย AI

### Flutter Providers
- `AuthProvider`: จัดการการล็อกอินและการคงอยู่ของ Token
- `ArticleProvider`: สถานะการจัดการบทความและแผนเนื้อหา
- `UserProvider`: การจัดการผู้ใช้สำหรับ Manager

---

## 9. การดำเนินงานและการทดสอบ (Operations)
- **การทดสอบหลังบ้าน:** อยู่ใน `tests/Feature` ครอบคลุมสถิติ, คำสั่งซื้อ, บทความ, หวย และ SEO
- **คำสั่งติดตั้ง:** `composer install`, `npm install`, `php artisan migrate --seed`
- **การ Deploy:** จัดการผ่าน `scripts/deploy-production.sh` และ GitHub Actions
- **งานเบื้องหลัง:** `scripts/run-notification-worker.sh` และ `scripts/install-scheduler-cron.sh`

---

## 10. ความเสี่ยงและสิ่งที่ต้องทำ (Risks & TODO)
- `routes/web.php` มีขนาดใหญ่และมีการใช้ closures จำนวนมาก แนะนำให้แยกเป็น Controllers ในอนาคต
- บริการภายนอก (LINE, Facebook, GA4) จำเป็นต้องมีข้อมูลลับใน `.env` ที่ถูกต้องเพื่อให้ทำงานได้สมบูรณ์
- ขั้นตอนการสร้างรูปภาพหวยขึ้นอยู่กับเครื่องมือจัดการรูปภาพบนเซิร์ฟเวอร์ (SVG/PNG)

*อัปเดตล่าสุด: 2026-05-13*
