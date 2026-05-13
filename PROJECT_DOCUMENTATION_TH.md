# เอกสารโปรแกรมเมอร์ Supernumber

สร้างจากการตรวจ repository วันที่ 2026-05-11 เอกสารนี้สำหรับโปรแกรมเมอร์ที่ดูแล Laravel website/API และ Flutter admin app

## 1. เทคโนโลยีและ Runtime
- Backend: PHP ^8.2, Laravel ^12.0, Laravel Sanctum ^4.0, Laravel Tinker.
- Frontend assets: Vite ^7, Tailwind CSS ^4, Axios, Laravel Vite plugin.
- Admin app: Flutter SDK ^3.11.3, Dio, Provider, Shared Preferences, Intl, Google Fonts, HTML editor, Image Picker, URL Launcher.
- Testing: PHPUnit ^11.5 สำหรับ Laravel; Flutter test/lints สำหรับ admin app.
- Storage/ops: Laravel public storage disk, queues/jobs tables, scheduler/worker scripts และ generated image/PDF/article assets.

## 2. โครงสร้างแอป
- `app/Http/Controllers`: Laravel controllers สำหรับหน้าเว็บสาธารณะ API และ admin controller resources.
- `app/Models`: Eloquent models, computed attributes, scopes และ relationships.
- `app/Services`: business services สำหรับ recommendation, notification, analytics, sanitizing, posting, PDF และ external integrations.
- `app/Console/Commands`: CLI imports, lottery fetch, timestamp conversion, scheduled publishing และสร้าง admin user.
- `routes/web.php`: public routes, admin web routes, order/LINE flows, cron/maintenance routes และ closure handlers จำนวนมาก.
- `routes/api.php`: API routes แบบ token auth สำหรับ Flutter admin และ tarot endpoint ที่ throttle.
- `resources/views`: Blade templates สำหรับ public pages, admin pages, partials, layouts และ error pages.
- `database/migrations`: ประวัติ schema สำหรับ users, inventory, orders, articles, lottery, LINE logs, customers, submissions และ sales documents.
- `tests`: Feature/unit tests ครอบคลุม public flows, admin flows, imports, API auth, lottery, LINE, SEO และ recommendations.
- `admin_flutter/lib`: source ของ Flutter admin แบ่งเป็น services, providers, models, screens และ utils.
- `scripts`: helper scripts สำหรับ deploy, scheduler, worker, Telegram และ lottery cover.

## 3. รายการ Feature
### Frontend เว็บไซต์สาธารณะ
หน้าแรกแสดงเบอร์เติมเงิน/รายเดือนแบบสุ่มและฟอร์มค้นหาเต็มรูปแบบ; หน้า catalog ค้นหาด้วยข้อความ ตำแหน่งตัวเลข service type package ช่วงราคาเติมเงิน และ layout เริ่มต้นแบบแบ่งเติมเงิน/รายเดือน; บทความรองรับ comment และ embed ผลหวย; มีหน้าวิเคราะห์เบอร์ วิเคราะห์เบอร์เสีย ประเมินแนะนำ ติดต่อ tiers เอกสารขาย privacy และ sitemap.

### Frontend แอดมินเว็บ
admin panel แบบ session ครอบคลุมคลังเบอร์/status log, orders และ slip, customers, sales documents/PDF, analytics settings/dashboard, LINE settings/webhook events, log viewer, test runner, article CRUD/import/planning/covers/social sharing, comments, contact messages, customer submissions, estimate leads, hold numbers, user approval และ activity logs.

### Backend/API
Laravel routes/services จัดการ catalog filtering, order processing, payment slip storage, customer/submission recording, article lifecycle, API token auth, role enforcement, notifications, GA4 reporting, Turnstile, lottery sync, Facebook posting, tarot AI และ cron/maintenance endpoint.

### แอป Flutter admin
Flutter app login ผ่าน API เก็บ token แสดง/แก้/นำเข้า/แชร์บทความ ขอ preview URL จัดการ users สำหรับ manager แสดง content-plan table และใช้ Dio/Provider สำหรับ API/state.

### External integrations
LINE Messaging API push/broadcast/webhook, Facebook Page posting, Google Analytics Data API, Cloudflare Turnstile, GLO lottery API, Telegram bot script และ shell deploy/worker/scheduler scripts.

## 4. Routes และ API Map
- **หน้าสาธารณะ**: /, /numbers, /articles, comment บทความ, /evaluate, /evaluateBadNumber, /tiers, /estimate, /sales-documents, /contact-us, /privacy-policy, sitemap.xml.
- **flow จอง/สั่งซื้อ**: /book GET/POST และ /book/save-step2 จัดการ checkout ตาม service type, ข้อมูลลูกค้า, slip โอนเงิน และสถานะ hold/sold ของเบอร์.
- **endpoint สำหรับ LINE**: /line/webhook เก็บ webhook events; signed /line/payment-slips/{order} และ /line/lottery-results/{lotteryResult}/image เปิด asset ให้ LINE เข้าถึงได้.
- **แผง admin web**: /admin login/logout/register และหน้า numbers, orders, customers, sales documents, analytics, LINE settings, logs, tests, articles, comments, submissions, leads, hold numbers, users, activity logs.
- **route operation/article bypass**: direct article save/create และ cron/maintenance/emergency endpoint ใช้กู้ระบบและ automation publish.
- **API**: /api/login, /api/me, /api/logout, article CRUD/import/share/preview, users เฉพาะ manager และ tarot reading ที่ throttle.

helper สำคัญใน `routes/web.php`:
- `currentAdmin()`: คืนค่า admin user ปัจจุบันจาก session ถ้าผู้ใช้ยังมีสิทธิ์เข้า panel.
- `ensureAdmin(?string $requiredRole = null)`: guard admin route และตรวจ role admin/manager ตามที่ route ต้องการ.
- `sanitizeArticleContent(string $content)`: ส่ง HTML บทความให้ ArticleContentSanitizer ล้างก่อนบันทึก.
- `articleColumnExists(string $column)`: cache การตรวจ column ในตาราง articles เพื่อให้ code ทำงานได้แม้บาง environment migrate ไม่เท่ากัน.
- `ensurePublicStorageLink()`: สร้าง public storage symlink เมื่อ route media/article ต้องใช้และ symlink ยังไม่มี.
- `decodeBase64Image(?string $base64String)`: แปลง base64 data URI เป็น UploadedFile ชั่วคราวสำหรับจัดการรูปบทความ.
- `moveTmpImagesToPermanent(string $content, string $articleSlug, int $year)`: ย้ายรูปชั่วคราวจาก editor ไป folder ถาวรของบทความและแก้ path ใน content.
- `rejectAdminLogin(Request $request)`: ส่งกลับหน้า login admin พร้อม error ภาษาไทยและเก็บ input ที่ไม่ใช่ password.
- `safelyRunLineNotification(callable $callback, string $message, array $context = [])`: รัน LINE notification โดยไม่ทำให้ user flow ล้มถ้าส่งแจ้งเตือนไม่สำเร็จ.
- `storeOrderPaymentSlip(CustomerOrder $order, UploadedFile $file)`: บันทึกและ normalize payment slip ของ order รวมถึง image/PDF เพื่อให้ admin และ LINE เข้าถึงภายหลัง.
- `guessFileMimeType(string $path)`: ตรวจ MIME type ของไฟล์ที่เก็บไว้เพื่อใช้ preview/download slip.
- `resolveOrderPaymentSlip(CustomerOrder $order)`: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
- `resolveEnvironmentEditor()`: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
- `resolveAdminLogViewer()`: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
- `storeLineWebhookEvent(array $attributes)`: บันทึก metadata และ payload ของ LINE webhook ที่เข้ามาเพื่อดูใน admin.
- `latestLineWebhookEvents(int $limit = 20)`: ดึง LINE webhook ล่าสุดสำหรับหน้า LINE settings ใน admin.
- `buildArticleSlug(string $title, ?int $ignoreId = null)`: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
- `resolvePlannedArticlePublishedAt(?string $slug, ?string $title)`: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
- `resolveArticleImageMeta(string $slug, ?Carbon $date = null)`: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
- `resolveLotteryResultForArticle(Article $article)`: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
- `resolveAnalysisPhone(Request $request)`: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
- `normalizeServiceType(?string $serviceType)`: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
- `defaultOrderStatus(string $serviceType)`: เลือกสถานะเริ่มต้นของ order ตาม flow เติมเงิน/รายเดือน.
- `logPhoneNumberStatusChange(PhoneNumber $phoneNumber, ?string $fromStatus, ?int $userId = null)`: บันทึก audit เมื่อสถานะเบอร์เปลี่ยน.
- `syncPhoneNumberStatusFromOrder(CustomerOrder $order, ?int $userId = null)`: sync สถานะเบอร์ที่ผูกอยู่กับการเปลี่ยนสถานะ order.
- `resolveTestDiscovery()`: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.

## 5. โครงสร้าง Database
- `users`: บัญชี admin/staff พร้อม role, status, password และเจ้าของ token.
- `phone_numbers`: คลังเบอร์เติมเงิน/รายเดือน ราคา package network status และผลรวมเลข.
- `phone_number_status_logs`: ประวัติการเปลี่ยนสถานะเบอร์.
- `customer_orders`: คำสั่งซื้อ/จอง พร้อมข้อมูลลูกค้า slip นัดรับซิม service type และ status.
- `articles`: บทความ SEO/content พร้อมสถานะ publish รูปปก metadata scheduling และ flag social.
- `article_comments`: comment บทความที่ต้องผ่านการอนุมัติ.
- `estimate_leads`: lead จากฟอร์มประเมิน/แนะนำเบอร์.
- `lottery_results`: ข้อมูลรอบหวย payload จากแหล่งข้อมูล สถานะ fetch และความครบถ้วน.
- `lottery_result_prizes`: รายการรางวัลที่ผูกกับรอบหวย.
- `line_notification_logs`: log การส่ง LINE push/broadcast พร้อม payload status attempts response และ error.
- `line_webhook_events`: audit log ของ LINE webhook ที่เข้ามา.
- `contact_messages`: ข้อความจากฟอร์มติดต่อ.
- `customers`: ข้อมูลลูกค้าที่ใช้ซ้ำ.
- `sales_documents`: เอกสารขายที่บันทึกและ metadata PDF.
- `customer_submissions`: audit trail กลางของ form submission หน้าเว็บ.
- `article_plans`: แผนหัวข้อบทความและวัน publish.
- `pair_meanings`: ข้อมูลอ้างอิงความหมายคู่เลข.
- `personal_access_tokens`: API token แบบ Sanctum สำหรับ Flutter/admin API.
- `cache/cache_locks/jobs/job_batches/failed_jobs/sessions/password_reset_tokens`: ตาราง infrastructure ของ Laravel.

จุดที่ migration สร้างหรือแก้ schema:
- `database/migrations/0001_01_01_000000_create_users_table.php`: `create` `users`
- `database/migrations/0001_01_01_000000_create_users_table.php`: `create` `password_reset_tokens`
- `database/migrations/0001_01_01_000000_create_users_table.php`: `create` `sessions`
- `database/migrations/0001_01_01_000001_create_cache_table.php`: `create` `cache`
- `database/migrations/0001_01_01_000001_create_cache_table.php`: `create` `cache_locks`
- `database/migrations/0001_01_01_000002_create_jobs_table.php`: `create` `jobs`
- `database/migrations/0001_01_01_000002_create_jobs_table.php`: `create` `job_batches`
- `database/migrations/0001_01_01_000002_create_jobs_table.php`: `create` `failed_jobs`
- `database/migrations/2026_01_31_120000_create_pair_meanings_table.php`: `create` `pair_meanings`
- `database/migrations/2026_03_01_040235_create_phone_numbers_table.php`: `create` `phone_numbers`
- `database/migrations/2026_03_04_120000_add_admin_fields_to_users_table.php`: `table` `users`
- `database/migrations/2026_03_04_120000_add_admin_fields_to_users_table.php`: `table` `users`
- `database/migrations/2026_03_04_120100_create_phone_number_status_logs_table.php`: `create` `phone_number_status_logs`
- `database/migrations/2026_03_07_201000_create_customer_orders_table.php`: `create` `customer_orders`
- `database/migrations/2026_03_08_120000_create_articles_table.php`: `create` `articles`
- `database/migrations/2026_03_08_130000_create_article_comments_table.php`: `create` `article_comments`
- `database/migrations/2026_03_09_120000_remove_unused_fields_from_article_comments_table.php`: `table` `article_comments`
- `database/migrations/2026_03_09_120000_remove_unused_fields_from_article_comments_table.php`: `table` `article_comments`
- `database/migrations/2026_03_15_210000_create_estimate_leads_table.php`: `create` `estimate_leads`
- `database/migrations/2026_03_16_120000_create_lottery_results_tables.php`: `create` `lottery_results`
- `database/migrations/2026_03_16_120000_create_lottery_results_tables.php`: `create` `lottery_result_prizes`
- `database/migrations/2026_03_18_120100_add_dual_cover_images_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_03_18_120100_add_dual_cover_images_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_03_26_120000_create_line_notification_logs_table.php`: `create` `line_notification_logs`
- `database/migrations/2026_03_26_130000_create_line_webhook_events_table.php`: `create` `line_webhook_events`
- `database/migrations/2026_03_27_140000_add_service_type_to_phone_numbers_and_customer_orders.php`: `table` `phone_numbers`
- `database/migrations/2026_03_27_140000_add_service_type_to_phone_numbers_and_customer_orders.php`: `table` `customer_orders`
- `database/migrations/2026_03_27_140000_add_service_type_to_phone_numbers_and_customer_orders.php`: `table` `customer_orders`
- `database/migrations/2026_03_27_140000_add_service_type_to_phone_numbers_and_customer_orders.php`: `table` `phone_numbers`
- `database/migrations/2026_03_27_220000_add_number_sum_to_phone_numbers_table.php`: `table` `phone_numbers`
- `database/migrations/2026_03_27_220000_add_number_sum_to_phone_numbers_table.php`: `table` `phone_numbers`
- `database/migrations/2026_03_30_120000_create_contact_messages_table.php`: `create` `contact_messages`
- `database/migrations/2026_04_02_120000_harden_customer_orders_and_defaults.php`: `table` `customer_orders`
- `database/migrations/2026_04_02_120000_harden_customer_orders_and_defaults.php`: `table` `customer_orders`
- `database/migrations/2026_04_02_120000_harden_customer_orders_and_defaults.php`: `table` `phone_number_status_logs`
- `database/migrations/2026_04_02_120000_harden_customer_orders_and_defaults.php`: `table` `phone_number_status_logs`
- `database/migrations/2026_04_02_120000_harden_customer_orders_and_defaults.php`: `table` `customer_orders`
- `database/migrations/2026_04_02_130000_create_customers_table.php`: `create` `customers`
- `database/migrations/2026_04_03_190000_create_sales_documents_table.php`: `create` `sales_documents`
- `database/migrations/2026_04_22_120000_add_seo_keywords_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_22_120000_add_seo_keywords_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_23_084500_add_media_paths_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_23_084500_add_media_paths_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_23_085600_remove_landscape_path_from_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_23_085600_remove_landscape_path_from_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_23_090000_restore_cover_image_landscape_path_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_23_090000_restore_cover_image_landscape_path_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_24_034221_add_view_count_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_24_034221_add_view_count_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_24_043551_alter_meta_description_to_text_in_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_24_043551_alter_meta_description_to_text_in_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_24_050117_add_notified_at_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_24_050117_add_notified_at_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_30_101951_add_is_line_broadcasted_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_04_30_101951_add_is_line_broadcasted_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_05_01_181017_add_is_auto_post_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_05_01_181017_add_is_auto_post_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_05_03_162524_add_is_active_to_sales_documents_table.php`: `table` `sales_documents`
- `database/migrations/2026_05_03_162524_add_is_active_to_sales_documents_table.php`: `table` `sales_documents`
- `database/migrations/2026_05_06_045429_create_personal_access_tokens_table.php`: `create` `personal_access_tokens`
- `database/migrations/2026_05_06_102000_add_image_guidelines_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_05_06_102000_add_image_guidelines_to_articles_table.php`: `table` `articles`
- `database/migrations/2026_05_08_070715_convert_dates_to_unix_timestamps_in_articles_and_logs.php`: `table` `articles`
- `database/migrations/2026_05_08_070715_convert_dates_to_unix_timestamps_in_articles_and_logs.php`: `table` `articles`
- `database/migrations/2026_05_08_070715_convert_dates_to_unix_timestamps_in_articles_and_logs.php`: `table` `articles`
- `database/migrations/2026_05_08_070715_convert_dates_to_unix_timestamps_in_articles_and_logs.php`: `table` `articles`
- `database/migrations/2026_05_08_070715_convert_dates_to_unix_timestamps_in_articles_and_logs.php`: `table` `line_notification_logs`
- `database/migrations/2026_05_08_070715_convert_dates_to_unix_timestamps_in_articles_and_logs.php`: `table` `line_notification_logs`
- `database/migrations/2026_05_08_070715_convert_dates_to_unix_timestamps_in_articles_and_logs.php`: `table` `line_notification_logs`
- `database/migrations/2026_05_08_070715_convert_dates_to_unix_timestamps_in_articles_and_logs.php`: `table` `line_notification_logs`
- `database/migrations/2026_05_09_120000_create_customer_submissions_table.php`: `create` `customer_submissions`
- `database/migrations/2026_05_09_162658_remove_redundant_columns_from_phone_numbers.php`: `table` `phone_numbers`
- `database/migrations/2026_05_09_162658_remove_redundant_columns_from_phone_numbers.php`: `table` `phone_numbers`
- `database/migrations/2026_05_11_090708_create_article_plans_table.php`: `create` `article_plans`

## 6. Laravel Classes และ Functions
### API controllers

- `ArticleController` ใน `app/Http/Controllers/Api/ArticleController.php`: API สำหรับแอป Flutter admin ใช้จัดการบทความ นำเข้า JSON สร้างลิงก์ preview และแชร์ไป Facebook/LINE.
  - `index(Request $request)` [public]: แสดงรายการข้อมูลหรือหน้ารายการหลักของ resource.
  - `importJson(Request $request)` [public]: นำเข้าข้อมูลภายนอกเป็น record ของระบบ.
  - `store(Request $request)` [public]: ตรวจข้อมูลและสร้าง record ใหม่.
  - `show(Article $article)` [public]: ส่งกลับหรือแสดง record เดียว.
  - `previewUrl(Article $article)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `share(Request $request, Article $article)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `update(Request $request, Article $article)` [public]: ตรวจข้อมูลและแก้ไข record เดิม.
  - `destroy(Article $article)` [public]: ลบหรือ archive record.
  - `stringValue(mixed $value)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `booleanValue(mixed $value, bool $default)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `imageGuidelinesValue(mixed $value)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `removeMissingArticleColumns(array &$data)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `articleColumnExists(string $column)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `articleSquareImageUrl(Article $article)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `uniqueArticleSlug(string $value)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `AuthController` ใน `app/Http/Controllers/Api/AuthController.php`: API login/logout ออกและยกเลิก personal access token และส่งข้อมูลผู้ใช้ปัจจุบัน.
  - `login(Request $request)` [public]: ตรวจ credential และสร้างสถานะ login/token.
  - `logout(Request $request)` [public]: ยกเลิกสถานะ login/token ปัจจุบัน.
  - `me(Request $request)` [public]: ส่งข้อมูลผู้ใช้ที่ login อยู่.

- `TarotReadingController` ใน `app/Http/Controllers/Api/TarotReadingController.php`: ตรวจ request ดูดวงไพ่ tarot และส่งต่อให้ service AI สร้างคำทำนาย.
  - `__invoke(Request $request, TarotAiService $tarotAiService)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `UserController` ใน `app/Http/Controllers/Api/UserController.php`: API สำหรับ manager จัดการผู้ใช้ในแอป admin.
  - `index()` [public]: แสดงรายการข้อมูลหรือหน้ารายการหลักของ resource.
  - `store(Request $request)` [public]: ตรวจข้อมูลและสร้าง record ใหม่.
  - `show(User $user)` [public]: ส่งกลับหรือแสดง record เดียว.
  - `update(Request $request, User $user)` [public]: ตรวจข้อมูลและแก้ไข record เดิม.
  - `destroy(User $user)` [public]: ลบหรือ archive record.

### Admin controllers

- `ArticlePlanController` ใน `app/Http/Controllers/Admin/ArticlePlanController.php`: สร้าง แก้ไข และลบแผนบทความใน admin.
  - `store(Request $request)` [public]: ตรวจข้อมูลและสร้าง record ใหม่.
  - `update(Request $request, ArticlePlan $articlePlan)` [public]: ตรวจข้อมูลและแก้ไข record เดิม.
  - `destroy(ArticlePlan $articlePlan)` [public]: ลบหรือ archive record.

- `RegisterController` ใน `app/Http/Controllers/Admin/RegisterController.php`: จัดการการสมัคร admin ที่รออนุมัติจาก web admin panel.
  - `showRegistrationForm()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `register(Request $request)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

### Console commands

- `ConvertToTimestamps` ใน `app/Console/Commands/ConvertToTimestamps.php`: command maintenance สำหรับแปลง timestamp.
  - `handle()` [public]: เป็น entrypoint สำหรับ command, middleware หรือ queued job.
  - `convertTable(string $table, array $columns)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `success(string $message)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `CreateAdminUserCommand` ใน `app/Console/Commands/CreateAdminUserCommand.php`: CLI command สำหรับสร้าง admin user.
  - `handle()` [public]: เป็น entrypoint สำหรับ command, middleware หรือ queued job.

- `FetchLatestLotteryCommand` ใน `app/Console/Commands/FetchLatestLotteryCommand.php`: ดึงผลสลากจาก GLO บันทึกรางวัล สร้างบทความ/รูปปก และแจ้งเตือน LINE.
  - `handle()` [public]: เป็น entrypoint สำหรับ command, middleware หรือ queued job.
  - `resolveTargetDate(Carbon $now)` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `isInScheduleWindow(Carbon $now)` [private]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `isEligibleScheduleDate(Carbon $now)` [private]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `isRetryDay(Carbon $now)` [private]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `handleRetryDayEnd(LotteryResult $result, Carbon $targetDate, Carbon $now)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `syncLotteryArticleCover(LotteryResult $result, Carbon $now, bool $wasAlreadyComplete = false)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `extractDrawDate(array $payload)` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `extractPrizes(array $payload)` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `isCompletePayload(array $prizes, ?Carbon $source, Carbon $storage)` [private]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.

- `ImportPostpaidSnapshotCommand` ใน `app/Console/Commands/ImportPostpaidSnapshotCommand.php`: นำเข้าคลังเบอร์รายเดือนจากไฟล์ SQL snapshot.
  - `handle()` [public]: เป็น entrypoint สำหรับ command, middleware หรือ queued job.
  - `parseRecords(string $contents)` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `normalizePhoneNumber(mixed $value)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `resolveImportedStatus(?PhoneNumber $existing)` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.

- `ImportTruePrepaidCsvCommand` ใน `app/Console/Commands/ImportTruePrepaidCsvCommand.php`: นำเข้าคลังเบอร์เติมเงินจากไฟล์ CSV.
  - `handle()` [public]: เป็น entrypoint สำหรับ command, middleware หรือ queued job.
  - `matchHeaderColumns(array $header)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `normalizePhoneNumber(mixed $value)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `normalizeInteger(mixed $value)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `shouldSkipStatus(string $status)` [private]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.

- `ImportTruePrepaidNumbersCommand` ใน `app/Console/Commands/ImportTruePrepaidNumbersCommand.php`: นำเข้าคลังเบอร์เติมเงินจาก workbook XLSX.
  - `handle()` [public]: เป็น entrypoint สำหรับ command, middleware หรือ queued job.
  - `parseWorkbook(string $path)` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `parseWorkbookSheets(string $workbookXml, string $workbookRelsXml)` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `parseWorksheet(string $sheetXml, array $sharedStrings, string $sheetName)` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `parseSharedStrings(?string $xml)` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `loadXml(string $xml)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `loadRelationshipsXml(string $xml)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `extractCellValue(SimpleXMLElement $cell, array $sharedStrings)` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `extractColumnName(string $reference)` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `normalizeInteger(mixed $value)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `matchHeaderColumns(array $cells)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `normalizePhoneNumber(mixed $value)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `shouldSkipStatus(string $status)` [private]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `resolveImportedStatus(?PhoneNumber $phoneNumber)` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `resolveImportPath(string $path)` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.

- `PublishScheduledArticles` ใน `app/Console/Commands/PublishScheduledArticles.php`: publish บทความที่ถึงกำหนด.
  - `handle()` [public]: เป็น entrypoint สำหรับ command, middleware หรือ queued job.

### Factories

- `UserFactory` ใน `database/factories/UserFactory.php`: class ของ Laravel ที่ใช้ใน flow ของระบบ.
  - `definition()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `unverified()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

### Jobs

- `SendLinePushJob` ใน `app/Jobs/SendLinePushJob.php`: queued job สำหรับ retry การส่ง LINE notification log ที่บันทึกไว้.
  - `__construct(public readonly int $notificationLogId,)` [public]: กำหนด dependency หรือค่าเริ่มต้นของ class.
  - `handle(LineNotifier $lineNotifier)` [public]: เป็น entrypoint สำหรับ command, middleware หรือ queued job.
  - `failed(Throwable $exception)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

### Middleware

- `ApiTokenAuth` ใน `app/Http/Middleware/ApiTokenAuth.php`: ตรวจ API request จาก bearer token แบบ Sanctum และปฏิเสธ user ที่ inactive หรือไม่มีสิทธิ์ admin.
  - `handle(Request $request, Closure $next)` [public]: เป็น entrypoint สำหรับ command, middleware หรือ queued job.
  - `resolveUserFromBearerToken(?string $bearerToken)` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.

- `CheckRole` ใน `app/Http/Middleware/CheckRole.php`: บังคับสิทธิ์ตาม role สำหรับ API route ของ admin/manager.
  - `handle(Request $request, Closure $next, ...$roles)` [public]: เป็น entrypoint สำหรับ command, middleware หรือ queued job.

### Models

- `Article` ใน `app/Models/Article.php`: model Article ของ Flutter สำหรับ serialize/deserialize JSON.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `scopePublished(Builder $query)` [public]: scope ของ Eloquent สำหรับกรอง query ที่ใช้ซ้ำ.
  - `author()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `comments()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `approvedComments()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `sanitizedContent()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `ArticleComment` ใน `app/Models/ArticleComment.php`: เก็บ comment บทความและสถานะ approval.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `scopeApproved(Builder $query)` [public]: scope ของ Eloquent สำหรับกรอง query ที่ใช้ซ้ำ.
  - `article()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `approver()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `ArticlePlan` ใน `app/Models/ArticlePlan.php`: เก็บหัวข้อบทความที่วางแผนและวัน publish.
  - `isPast()` [public]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.

- `ContactMessage` ใน `app/Models/ContactMessage.php`: เก็บข้อความจากฟอร์มติดต่อ.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `Customer` ใน `app/Models/Customer.php`: เก็บข้อมูลตัวตน/ช่องทางติดต่อของลูกค้าที่ใช้ซ้ำ.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `getDisplayNameAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getContactNameAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.

- `CustomerOrder` ใน `app/Models/CustomerOrder.php`: เก็บข้อมูล booking/order จากหน้าเว็บ metadata slip service type status และ label ที่แสดงให้ลูกค้า.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `booted()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `statusOptions()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `defaultStatusForServiceType(?string $serviceType)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `resolvePhoneNumberStatus(?string $orderStatus)` [public]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `getIsPostpaidAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getIsPrepaidAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getServiceTypeLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getPaymentLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getFullNameAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getShippingAddressAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `phoneNumber()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `lineNotificationLogs()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `normalizeServiceType(?string $serviceType)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `normalizeStatus(?string $status)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.

- `CustomerSubmission` ใน `app/Models/CustomerSubmission.php`: โมเดล audit ของ form submission หน้าเว็บที่ผูกกับลูกค้า.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `customer()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `formTypeLabels()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `getFormTypeLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.

- `EstimateLead` ใน `app/Models/EstimateLead.php`: เก็บ lead ประเมิน/แนะนำเบอร์ และ label ของ gender, work type, goal.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `lineNotificationLogs()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `genderLabels()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `workTypeLabels()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `goalLabels()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `getFullNameAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getGenderLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getWorkTypeLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getGoalLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.

- `LineNotificationLog` ใน `app/Models/LineNotificationLog.php`: log สำหรับ audit ข้อความ LINE ที่ queued/sent/failed.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `notifiable()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `LineWebhookEvent` ใน `app/Models/LineWebhookEvent.php`: เก็บ payload LINE webhook ที่เข้ามาเพื่อ review/setup.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `LotteryResult` ใน `app/Models/LotteryResult.php`: เก็บข้อมูลหวยหนึ่งงวดและ normalize ช่องวันที่งวด.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `drawDate()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `sourceDrawDate()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `prizes()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `asDateOnlyCarbon(?string $value)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `normalizeDateOnly(mixed $value)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.

- `LotteryResultPrize` ใน `app/Models/LotteryResultPrize.php`: เก็บรายการรางวัลย่อยของแต่ละงวดหวย.
  - `result()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `PairMeaning` ใน `app/Models/PairMeaning.php`: เก็บข้อมูลอ้างอิงความหมายคู่เลข.

- `PhoneNumber` ใน `app/Models/PhoneNumber.php`: โมเดลหลักของคลังเบอร์ เติมเงิน/รายเดือน การค้นหา ราคา สถานะ และหัวข้อเลขศาสตร์.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `booted()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `scopeAvailable(Builder $query)` [public]: scope ของ Eloquent สำหรับกรอง query ที่ใช้ซ้ำ.
  - `scopeMatchingPattern(Builder $query, ?string $pattern)` [public]: scope ของ Eloquent สำหรับกรอง query ที่ใช้ซ้ำ.
  - `scopeOfServiceType(Builder $query, ?string $serviceType)` [public]: scope ของ Eloquent สำหรับกรอง query ที่ใช้ซ้ำ.
  - `scopePostpaid(Builder $query)` [public]: scope ของ Eloquent สำหรับกรอง query ที่ใช้ซ้ำ.
  - `scopePrepaid(Builder $query)` [public]: scope ของ Eloquent สำหรับกรอง query ที่ใช้ซ้ำ.
  - `scopeSupportedNetwork(Builder $query)` [public]: scope ของ Eloquent สำหรับกรอง query ที่ใช้ซ้ำ.
  - `buildSearchPattern(array $filters)` [public]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `getFormattedNumberAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `format(?string $phoneNumber)` [public]: จัดรูปแบบข้อมูลสำหรับแสดงผล.
  - `getPackageLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getOfferLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getPaymentLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getInitialPaymentLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getInitialPaymentHtmlAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getNormalizedPackagePriceAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getIsPostpaidAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getIsPrepaidAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getServiceTypeLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getNetworkLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getTierLabelAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `getTierClassAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `buildPackageLabel(?string $planName, mixed $salePrice)` [public]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `parsePackageLabel(?string $label)` [public]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `packageLabelsForQuery(Builder $query)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `supportedNetworkCodes()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `networkLabel(?string $networkCode)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `getSupportedTopicIconsAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.
  - `buildSupportedTopicIcons(?string $phoneNumber)` [public]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `buildPairVariants(string $pair)` [protected]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `topicPairMap()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `prepaidPriceRanges()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `prepaidPriceRangeOptions()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `resolvePrepaidPriceRange(?string $value)` [public]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `serviceTypeOptions()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `calculateNumberSum(mixed $phoneNumber)` [public]: คำนวณค่าที่ได้จากข้อมูลดิบหรือข้อมูลที่เก็บไว้.
  - `adminStatusOptions()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `statusLogs()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `orders()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `firstDigit(mixed $value)` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `digitsOnly(mixed $value)` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `normalizePackagePrice(mixed $salePrice)` [protected]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `packagePrice(mixed $salePrice)` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `normalizeServiceType(?string $serviceType)` [protected]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `normalizeNetworkCode(?string $networkCode)` [protected]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.

- `PhoneNumberStatusLog` ใน `app/Models/PhoneNumberStatusLog.php`: ติดตามการเปลี่ยนสถานะเบอร์โดย admin/user.
  - `phoneNumber()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `user()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `SalesDocument` ใน `app/Models/SalesDocument.php`: เก็บ metadata เอกสารขายและ path PDF ที่สร้างแล้ว.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `customer()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `getFileExistsAttribute()` [public]: computed attribute ของ Eloquent สำหรับแสดงผลหรือข้อมูลที่คำนวณ.

- `User` ใน `app/Models/User.php`: โมเดลผู้ใช้ admin พร้อม helper role/status และ token.
  - `casts()` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `statusLogs()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `roleOptions()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `canAccessAdminPanel()` [public]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `isManager()` [public]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `isAdmin()` [public]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `isStaff()` [public]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `isAtLeastAdmin()` [public]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.

### Providers

- `AppServiceProvider` ใน `app/Providers/AppServiceProvider.php`: class ของ Laravel ที่ใช้ใน flow ของระบบ.
  - `register()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `boot()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `guardDestructiveDatabaseCommands()` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

### Public/web controllers

- `Controller` ใน `app/Http/Controllers/Controller.php`: class ของ Laravel ที่ใช้ใน flow ของระบบ.

- `PublicController` ใน `app/Http/Controllers/PublicController.php`: ควบคุมหน้าเว็บไซต์สาธารณะ เช่น หน้าแรก ค้นหาเบอร์ บทความ วิเคราะห์เบอร์ ติดต่อ นโยบาย และ sitemap.
  - `index()` [public]: แสดงรายการข้อมูลหรือหน้ารายการหลักของ resource.
  - `numbers(Request $request)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `articles()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `showArticle(string $slug)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `storeArticleComment(Request $request, string $slug)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `evaluate(Request $request, CustomerSubmissionRecorder $submissionRecorder)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `evaluateBad(Request $request)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `tiers()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `contact()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `storeContact(Request $request, TurnstileVerifier $turnstileVerifier, ContactSpamFilter $spamFilter, CustomerSubmissionRecorder $submissionRecorder)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `privacy()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `resolveLotteryResultForArticle(Article $article)` [protected]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `sitemap()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `resolveAnalysisPhone(Request $request)` [protected]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.

### Seeders

- `CloudRunDemoSeeder` ใน `database/seeders/CloudRunDemoSeeder.php`: class ของ Laravel ที่ใช้ใน flow ของระบบ.
  - `run()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `seedManagerAccount()` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `seedPhoneNumbers()` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `DatabaseSeeder` ใน `database/seeders/DatabaseSeeder.php`: class ของ Laravel ที่ใช้ใน flow ของระบบ.
  - `run()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `PairMeaningLongSeeder` ใน `database/seeders/PairMeaningLongSeeder.php`: class ของ Laravel ที่ใช้ใน flow ของระบบ.
  - `run()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `PairMeaningSeeder` ใน `database/seeders/PairMeaningSeeder.php`: class ของ Laravel ที่ใช้ใน flow ของระบบ.
  - `run()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

### Services

- `AdminLogViewer` ใน `app/Services/AdminLogViewer.php`: แสดง กรอง อ่าน และล้าง Laravel log file สำหรับ manager.
  - `availableFiles()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `resolveFile(?string $selectedFile)` [public]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `readTail(string $path, int $maxBytes = self::MAX_READ_BYTES)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `parseEntries(string $content)` [public]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `filterEntries(array $entries, ?string $level, ?string $date, ?string $search)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `availableLevels(array $entries)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `availableDates(array $entries)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `clearFile(string $path)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `normalizeTimestamp(string $timestamp)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.

- `ArticleContentSanitizer` ใน `app/Services/ArticleContentSanitizer.php`: sanitize HTML บทความโดยเก็บ link, image และ formatting ที่อนุญาตไว้.
  - `sanitize(string $html)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `prepareForDom(string $html)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `sanitizeChildren(DOMNode $node)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `sanitizeAttributes(DOMElement $element, string $tag)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `sanitizeLink(DOMElement $element)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `sanitizeImage(DOMElement $element)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `isSafeUrl(string $value, array $allowedSchemes)` [private]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `unwrapElement(DOMElement $element)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `ContactSpamFilter` ใน `app/Services/ContactSpamFilter.php`: ให้คะแนนข้อความติดต่อเพื่อจับ spam และสัญญาณ honeypot.
  - `inspect(string $name, string $phone, string $message)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `normalize(string $value)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `countKeywordHits(string $message, array $keywords)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `countMatches(string $pattern, string $message)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `looksGeneratedName(string $name)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `hasSuspiciousPhone(string $phone)` [private]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.

- `CustomerSubmissionRecorder` ใน `app/Services/CustomerSubmissionRecorder.php`: บันทึก submission จากฟอร์มหน้าเว็บและผูก/สร้างข้อมูลลูกค้า.
  - `record(Request $request, string $formType, array $payload)` [public]: บันทึก submission/event จาก request เพื่อ audit.
  - `resolveCustomer(?string $name, ?string $phone, ?string $email)` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `cleanString(mixed $value)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `normalizePhone(mixed $value)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `booleanConsent(mixed $value)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `EnvironmentEditor` ใน `app/Services/EnvironmentEditor.php`: อ่านและอัปเดต key บางตัวใน .env สำหรับหน้า settings ของ admin.
  - `__construct(private readonly ?string $path = null,)` [public]: กำหนด dependency หรือค่าเริ่มต้นของ class.
  - `getMany(array $keys)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `setMany(array $values)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `parseFile()` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `parseValue(string $value)` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `formatValue(string $value)` [private]: จัดรูปแบบข้อมูลสำหรับแสดงผล.
  - `resolvePath()` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.

- `EstimateRecommendationService` ใน `app/Services/EstimateRecommendationService.php`: สร้างชุดเบอร์แนะนำจากอาชีพ เป้าหมาย และหัวข้อเลขศาสตร์ที่เบอร์รองรับ.
  - `buildResult(EstimateLead $lead, int $limit = 12)` [public]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `recommendedNumbers(array $workTopics, array $goalRule, int $limit, ?string $serviceType = null, array $excludeIds = [])` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `goalRule(?string $goal, ?string $workType)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `applyGoalQuery(Builder $query, array $goalRule)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `goalMatches(?string $phoneNumber, array $goalRule)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `goalScore(?string $phoneNumber, array $goalRule)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `topicCards(array $topics)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `workRuleText(EstimateLead $lead, array $workTopics)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `digitsOnly(?string $value)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `FacebookPagePoster` ใน `app/Services/FacebookPagePoster.php`: โพสต์บทความที่ publish แล้วไปยัง Facebook Page ที่ตั้งค่าไว้.
  - `postArticle(Article $article, ?string $manualImageUrl = null)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `Ga4AnalyticsService` ใน `app/Services/Ga4AnalyticsService.php`: อ่าน/เขียน config GA4 และดึงข้อมูล dashboard ผ่าน Google APIs.
  - `measurementId()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `propertyId()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `dashboardCacheSeconds()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `isClientTrackingConfigured()` [public]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `isReportingConfigured()` [public]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `editableServiceAccountJson()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `serviceAccountEmail()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `normalizeServiceAccountJson(?string $json)` [public]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `fetchDashboard(int $days = 30)` [public]: ดึงข้อมูลจากภายนอกหรือข้อมูลที่คำนวณสำหรับ service นี้.
  - `buildDashboard(int $days)` [private]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `runReport(array $payload)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `accessToken()` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `buildSignedJwt(array $credentials)` [private]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `decodeStoredCredentials()` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `rawServiceAccountJsonBase64()` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `base64UrlEncode(string $value)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `firstRow(array $response)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `rows(array $response)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `castMetricValue(string $value)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `resolveGoogleErrorMessage(Response $response)` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.

- `LineEstimateLeadNotifier` ใน `app/Services/LineEstimateLeadNotifier.php`: สร้างข้อความ LINE สำหรับ lead ประเมินที่ถูกส่งเข้ามา.
  - `__construct(private readonly LineNotifier $lineNotifier,)` [public]: กำหนด dependency หรือค่าเริ่มต้นของ class.
  - `sendSubmitted(EstimateLead $lead)` [public]: สร้างและส่งข้อความแจ้งเตือนที่เกี่ยวข้อง.
  - `buildMessage(EstimateLead $lead)` [private]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.

- `LineLotteryImageService` ใน `app/Services/LineLotteryImageService.php`: สร้าง asset SVG/PNG ของผลหวยสำหรับบทความและ LINE preview.
  - `buildLineImageUrl(LotteryResult $result)` [public]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `canServeImage(LotteryResult $result)` [public]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `toResponse(LotteryResult $result)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `resolveStoredImage(LotteryResult $result, ?string $preferredExtension = null)` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `resolveArticleSlug(LotteryResult $result)` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `canRenderFallbackPng()` [private]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `renderFallbackPng(LotteryResult $result)` [public]: render HTML/image/PDF สำหรับ flow ต่อเนื่อง.
  - `paintBackground($image, int $width, int $height)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `drawTopBrand($image, int $gold, int $goldDark)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `drawDoubleNumberPanel($image, int $x, int $y, int $width, int $boxHeight, string $top, string $bottom, int $panelColor, int $borderColor, int $textColor)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `drawSingleNumberPanel($image, int $x, int $y, int $width, int $height, string $number, int $panelColor, int $borderColor, int $textColor)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `drawGroupPanel($image, int $x, int $y, int $width, int $height, string $title, array $numbers, int $numberSize, int $panelColor, int $borderColor, int $textColor)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `drawCenteredText($image, string $text, string $fontPath, int $size, int $centerX, int $baselineY, int $color)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `drawCenteredTextWithOutline($image, string $text, string $fontPath, int $size, int $centerX, int $baselineY, int $fillColor, int $outlineColor, int $outlineWidth)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `fontPath(string $filename)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `pickFirstPrizeNumber(Collection $prizes, string $nameNeedle, string $fallback)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `pickPrizeNumbers(Collection $prizes, string $nameNeedle, int $limit)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `toThaiDateLabel(Carbon $drawDate)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `absoluteUrl(string $pathOrUrl)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `isPublicHttpsUrl(string $url)` [private]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `generateSquareSvg(LotteryResult $result)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `generateLandscapeSvg(LotteryResult $result)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `getFontBase64(string $filename)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `LineLotteryNotifier` ใน `app/Services/LineLotteryNotifier.php`: สร้างและส่ง LINE message เมื่อผลหวยครบหรือหมดช่วง retry แล้วยังไม่มีผล.
  - `__construct(private readonly LineNotifier $lineNotifier, private readonly LineLotteryImageService $lineLotteryImageService,)` [public]: กำหนด dependency หรือค่าเริ่มต้นของ class.
  - `sendCompleted(LotteryResult $result, ?string $manualImageUrl = null)` [public]: สร้างและส่งข้อความแจ้งเตือนที่เกี่ยวข้อง.
  - `notifyAdminArticleReady(Article $article, ?Carbon $drawDate = null)` [public]: สร้างและส่งข้อความแจ้งเตือนที่เกี่ยวข้อง.
  - `sendUnavailableAfterRetryWindow(LotteryResult $result, Carbon $scheduledDrawDate, Carbon $checkedAt)` [public]: สร้างและส่งข้อความแจ้งเตือนที่เกี่ยวข้อง.
  - `buildMessages(LotteryResult $result, ?string $manualImageUrl = null)` [private]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `buildTextMessage(LotteryResult $result)` [private]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `buildUnavailableAfterRetryMessage(Carbon $scheduledDrawDate, Carbon $checkedAt)` [private]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `pickFirstPrizeNumber(Collection $prizes, string $nameNeedle, string $fallback)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `pickPrizeNumbers(Collection $prizes, string $nameNeedle, int $limit)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `LineNotifier` ใน `app/Services/LineNotifier.php`: บันทึก payload แจ้งเตือน LINE แก้ปลายทาง และส่ง push/broadcast พร้อม log.
  - `isConfigured(?string $destinationKey = null)` [public]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `queueText(string $eventType, string $message, ?Model $notifiable = null, ?string $destinationKey = null,)` [public]: บันทึก payload แจ้งเตือนและเริ่มส่ง.
  - `queueMessages(string $eventType, array $messages, ?Model $notifiable = null, ?string $destinationKey = null,)` [public]: บันทึก payload แจ้งเตือนและเริ่มส่ง.
  - `queueBroadcastMessages(string $eventType, array $messages, ?Model $notifiable = null)` [public]: บันทึก payload แจ้งเตือนและเริ่มส่ง.
  - `deliverLog(int $logId, int $attempt)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `resolveToken()` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `resolveDestinationId(?string $destinationKey = null)` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `markPermanentFailure(LineNotificationLog $log, int $attempt, string $message)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `buildMessagePreview(array $messages)` [private]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `normalizeResponsePayload(Response $response)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `deliverImmediately(LineNotificationLog $log)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `LineOrderNotifier` ใน `app/Services/LineOrderNotifier.php`: Builds LINE messages for orders, payment slips, order status changes, admin tests, and contact messages.
  - `__construct(private readonly LineNotifier $lineNotifier,)` [public]: กำหนด dependency หรือค่าเริ่มต้นของ class.
  - `sendOrderSubmitted(CustomerOrder $order)` [public]: สร้างและส่งข้อความแจ้งเตือนที่เกี่ยวข้อง.
  - `sendStatusUpdated(CustomerOrder $order, ?string $previousStatus = null)` [public]: สร้างและส่งข้อความแจ้งเตือนที่เกี่ยวข้อง.
  - `sendAdminTest(CustomerOrder $order)` [public]: สร้างและส่งข้อความแจ้งเตือนที่เกี่ยวข้อง.
  - `notifyContactMessage(string $name, string $phone, string $message)` [public]: สร้างและส่งข้อความแจ้งเตือนที่เกี่ยวข้อง.
  - `shouldNotifyStatus(?string $status)` [public]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `buildOrderMessages(CustomerOrder $order, string $headline, array $extraLines = [])` [private]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `resolveSlipUrl(CustomerOrder $order)` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `resolveLineImageUrl(CustomerOrder $order)` [private]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `isPublicHttpsUrl(string $url)` [private]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `normalizeStatus(?string $status)` [private]: แปลง input ดิบให้อยู่ในรูปแบบมาตรฐานของระบบ.
  - `displayStatus(?string $status)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `absoluteUrl(string $pathOrUrl)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `SalesDocumentPdfService` ใน `app/Services/SalesDocumentPdfService.php`: render HTML เอกสารขายและบันทึก record/path PDF.
  - `renderDocumentHtml(SalesDocument $document, array $options = [])` [public]: render HTML/image/PDF สำหรับ flow ต่อเนื่อง.
  - `saveDocument(array $data, ?int $savedByUserId = null)` [public]: บันทึกข้อมูลที่สร้างหรือส่งเข้ามา.
  - `buildFileName(string $documentType, string $documentNumber)` [protected]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `buildRelativePdfPath(string $documentType, string $year, string $fileName)` [protected]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `buildViewData(SalesDocument $document, array $options = [])` [protected]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `resolveDateValue(mixed $value)` [protected]: หาและ normalize ค่า ปลายทาง record path หรือ fallback จาก input/config.
  - `trimNullable(mixed $value)` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `TarotAiService` ใน `app/Services/TarotAiService.php`: สร้าง prompt และเรียก AI provider เพื่อสร้างคำทำนาย tarot.
  - `generateReading(array $cards, ?string $question = null, string $languageCode = 'th', ?string $type = null,)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `buildPrompt(array $cards, string $question, string $languageCode, string $type)` [private]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `buildCardDescription(array $cards, string $languageCode)` [private]: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `sanitizeStringList(mixed $value)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `languageMessage(string $languageCode, string $english, string $thai)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `TestDiscoveryService` ใน `app/Services/TestDiscoveryService.php`: ค้นหา PHPUnit tests และรัน test filter ที่เลือกจากหน้า admin tests.
  - `discoverTests()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `runTest(string $filter)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `getClassNameFromFile(string $path)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `hasTestAnnotation(ReflectionMethod $method)` [private]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `extractThaiTitle(?string $docComment, string $methodName)` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.
  - `translateToThai(string $text)` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `extractCategory(string $className)` [private]: อ่าน input และดึงค่าที่ normalize แล้วเพื่อเก็บหรือประมวลผล.

- `TurnstileVerifier` ใน `app/Services/TurnstileVerifier.php`: ตรวจ token Cloudflare Turnstile สำหรับฟอร์มติดต่อหน้าเว็บ.
  - `isEnabled()` [public]: helper แบบ true/false สำหรับ permission, config, status หรือ rule.
  - `siteKey()` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `verify(string $token, ?string $ipAddress = null)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `secretKey()` [private]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

### Traits

- `UnixTimestampSerializable` ใน `app/Traits/UnixTimestampSerializable.php`: trait สำหรับปรับการ serialize/deserialize วันที่ของ model ให้เข้ากับ timestamp.
  - `serializeDate(\DateTimeInterface $date)` [protected]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `fromDateTime($value)` [public]: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

## 7. Flutter Admin Classes และ Functions
- `SupernumberAdminApp` ใน `admin_flutter/lib/main.dart`: widget หลักของ Flutter admin ที่ผูก providers และสลับหน้า login/article list.
  - `main()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `build(BuildContext context)`: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.

- `Article` ใน `admin_flutter/lib/models/article.model.dart`: model Article ของ Flutter สำหรับ serialize/deserialize JSON.
  - `toJson()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `ArticleProvider` ใน `admin_flutter/lib/providers/article_provider.dart`: state provider ของ Flutter สำหรับ list/CRUD/import/preview/share บทความและ content plan.
  - `fetchArticles({String? monthPlan})`: ดึงข้อมูลจากภายนอกหรือข้อมูลที่คำนวณสำหรับ service นี้.
  - `fetchArticleDetail(int id)`: ดึงข้อมูลจากภายนอกหรือข้อมูลที่คำนวณสำหรับ service นี้.
  - `fetchPreviewUrl(Article article)`: ดึงข้อมูลจากภายนอกหรือข้อมูลที่คำนวณสำหรับ service นี้.
  - `shareArticle(Article article, String platform)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `saveArticle(Article article, { String? landscapePath, String? squarePath, })`: บันทึกข้อมูลที่สร้างหรือส่งเข้ามา.
  - `deleteArticle(Article article)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `importArticlesFromJson(String jsonData)`: นำเข้าข้อมูลภายนอกเป็น record ของระบบ.
  - `_extractErrorMessage(DioException error)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `AuthProvider` ใน `admin_flutter/lib/providers/auth_provider.dart`: state provider ของ Flutter สำหรับ login/logout และเก็บ session.
  - `_loadAuthStatus()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `login(String login, String password)`: ตรวจ credential และสร้างสถานะ login/token.
  - `_extractErrorMessage(DioException error)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `logout()`: ยกเลิกสถานะ login/token ปัจจุบัน.

- `UserProvider` ใน `admin_flutter/lib/providers/user_provider.dart`: state provider ของ Flutter สำหรับจัดการ user เฉพาะ manager.
  - `fetchUsers()`: ดึงข้อมูลจากภายนอกหรือข้อมูลที่คำนวณสำหรับ service นี้.
  - `createUser(Map<String, dynamic> userData)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_extractErrorMessage(DioException error)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `AdminRegisterScreen, _AdminRegisterScreenState` ใน `admin_flutter/lib/screens/admin_register_screen.dart`: หน้า registration ของ Flutter/admin.
  - `_handleSubmit()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `build(BuildContext context)`: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.

- `ArticleEditScreen, _ArticleEditScreenState` ใน `admin_flutter/lib/screens/article_edit_screen.dart`: ฟอร์มสร้าง/แก้บทความพร้อมรูปปกและตัวควบคุม publish ใน Flutter.
  - `initState()`: เตรียม state ของหน้า Flutter และเริ่มโหลดข้อมูล.
  - `_loadFullDetail()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_pickImage(bool isLandscape)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_selectDateTime()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_copyToClipboard(String text, String label)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_save()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `build(BuildContext context)`: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `_buildImagePicker({ required String label, String? imagePath, String? serverPath, required bool isLandscape, })`: สร้างส่วนย่อยของ Flutter widget สำหรับหน้านี้.
  - `_buildPlaceholder()`: สร้างส่วนย่อยของ Flutter widget สำหรับหน้านี้.
  - `_buildSectionTitle(String title)`: สร้างส่วนย่อยของ Flutter widget สำหรับหน้านี้.
  - `_buildTextField(String label, TextEditingController controller, { bool isBold = false, int maxLines = 1, })`: สร้างส่วนย่อยของ Flutter widget สำหรับหน้านี้.
  - `_buildPublishToggle()`: สร้างส่วนย่อยของ Flutter widget สำหรับหน้านี้.
  - `_buildAutoPostToggle()`: สร้างส่วนย่อยของ Flutter widget สำหรับหน้านี้.
  - `_buildDateTimePicker()`: สร้างส่วนย่อยของ Flutter widget สำหรับหน้านี้.

- `ArticleJsonImportScreen, _ArticleJsonImportScreenState, _ContentPlanTable, _TypePill` ใน `admin_flutter/lib/screens/article_json_import_screen.dart`: หน้า import JSON และ preview content plan ใน Flutter.
  - `dispose()`: คืนทรัพยากร controller/listener ของ Flutter.
  - `_pasteFromClipboard()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_insertSample()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_formatJson()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_importJson()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_showSnack(String message, {bool isError = false})`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `build(BuildContext context)`: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.

- `ArticleListScreen, _ArticleListScreenState, _ArticleItem, _ArticleActionButton, _StatusPill, _ContentPlanSection, _MonthPlanCard, _PlanItemRow` ใน `admin_flutter/lib/screens/article_list_screen.dart`: หน้า list จัดการบทความ filter actions และ content planning ของ Flutter.
  - `initState()`: เตรียม state ของหน้า Flutter และเริ่มโหลดข้อมูล.
  - `_openJsonImport()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_openArticleEditor([Article? article])`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_openArticlePreview(Article article)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_shareArticle(Article article)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_confirmDeleteArticle(Article article)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_showAiPromptDialog()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_copyPrompt(String subjectText, String type)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `build(BuildContext context)`: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.
  - `_formatArticleDate(Article article)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `_checkIfDone(Map<String, dynamic> item)`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.

- `LoginScreen, _LoginScreenState` ใน `admin_flutter/lib/screens/login_screen.dart`: หน้า login Flutter สำหรับ admin users.
  - `_handleLogin()`: ช่วยรองรับ flow ของ class นี้ตามหน้าที่ที่ชื่อ method ระบุและบริบทโค้ดรอบข้าง.
  - `build(BuildContext context)`: สร้างค่า/payload/label/prompt/query ที่ flow ต้องใช้.

- `ApiService` ใน `admin_flutter/lib/services/api_service.dart`: config Dio API client ของ Flutter ที่ providers ใช้ร่วมกัน.

- `DateFormatter` ใน `admin_flutter/lib/utils/date_formatter.dart`: helper Flutter สำหรับ format วันที่.

## 8. Business Rules
- คลังเบอร์: ตอน save จะ normalize service type/network/status และคำนวณ number_sum ใหม่เมื่อ phone_number เปลี่ยน.
- ค้นหา catalog: รวม free-text digits, digit-position masks, service type, package labels และ prepaid price ranges เป็น query filter เดียว.
- layout catalog เริ่มต้น: ถ้าไม่มี filter จะดึง prepaid/postpaid แยกกันแล้ว interleave เพื่อแสดงสมดุล.
- หัวข้อเลขศาสตร์: คำนวณจาก 7 หลักท้าย โดยคู่สุดท้ายมีน้ำหนักมากกว่า; topic จะแสดงเมื่อคะแนนดี/กลางมากกว่าคะแนนเสีย.
- recommendation: work type เลือกหัวข้อเป้าหมาย; goal rule เพิ่มเลขที่ต้องมี/ห้ามมี; เริ่มจาก strict topic match แล้วผ่อนเป็น partial ถ้า inventory ไม่พอ.
- orders: public booking สร้าง/แก้ order, เก็บ payment slip และ sync สถานะเบอร์เป็น hold/sold/active ตามสถานะ order.
- submissions/customers: form หน้าเว็บถูกบันทึกใน audit table กลางและผูกลูกค้าเดิมด้วย phone/email ถ้ามี.
- articles: sanitize content, slug ต้อง unique, รองรับ cover landscape/square, publish state คุมการแสดง public และการ share LINE update flag ตาม column ที่มี.
- lottery: scheduled fetch ทำงานเฉพาะวัน/ช่วงเวลาที่กำหนดถ้าไม่ force; ห้าม overwrite ผลครบด้วย partial payload; sync บทความหนึ่งรายการต่อหนึ่งงวดแบบ deterministic.
- LINE: log notification ก่อนส่งจริง; test mode redirect ไป admin user; image message ต้องเป็น public HTTPS URL.

## 9. Testing และ Operations
Laravel tests ที่มีอยู่:
- `tests/Feature/AdminAnalyticsTest.php`: ทดสอบกลุ่ม AdminAnalytics.
- `tests/Feature/AdminArticleMediaTest.php`: ทดสอบกลุ่ม AdminArticleMedia.
- `tests/Feature/AdminContactMessagesTest.php`: ทดสอบกลุ่ม AdminContactMessages.
- `tests/Feature/AdminCustomerQuickStoreTest.php`: ทดสอบกลุ่ม AdminCustomerQuickStore.
- `tests/Feature/AdminCustomerSubmissionsTest.php`: ทดสอบกลุ่ม AdminCustomerSubmissions.
- `tests/Feature/AdminCustomersTest.php`: ทดสอบกลุ่ม AdminCustomers.
- `tests/Feature/AdminEstimateLeadsTest.php`: ทดสอบกลุ่ม AdminEstimateLeads.
- `tests/Feature/AdminLineSettingsTest.php`: ทดสอบกลุ่ม AdminLineSettings.
- `tests/Feature/AdminLogsTest.php`: ทดสอบกลุ่ม AdminLogs.
- `tests/Feature/AdminLotteryFlowTest.php`: ทดสอบกลุ่ม AdminLotteryFlow.
- `tests/Feature/AdminOrderPaymentSlipTest.php`: ทดสอบกลุ่ม AdminOrderPaymentSlip.
- `tests/Feature/AdminPermissionTest.php`: ทดสอบกลุ่ม AdminPermission.
- `tests/Feature/AdminPhoneNumberEditTest.php`: ทดสอบกลุ่ม AdminPhoneNumberEdit.
- `tests/Feature/AdminSalesDocumentWorkspaceTest.php`: ทดสอบกลุ่ม AdminSalesDocumentWorkspace.
- `tests/Feature/AdminSavedSalesDocumentTest.php`: ทดสอบกลุ่ม AdminSavedSalesDocument.
- `tests/Feature/AdminUserBootstrapTest.php`: ทดสอบกลุ่ม AdminUserBootstrap.
- `tests/Feature/ApiArticleImportJsonTest.php`: ทดสอบกลุ่ม ApiArticleImportJson.
- `tests/Feature/ApiArticleStorageTest.php`: ทดสอบกลุ่ม ApiArticleStorage.
- `tests/Feature/ApiAuthTest.php`: ทดสอบกลุ่ม ApiAuth.
- `tests/Feature/ArticleStorageTest.php`: ทดสอบกลุ่ม ArticleStorage.
- `tests/Feature/ContactMessageStoreTest.php`: ทดสอบกลุ่ม ContactMessageStore.
- `tests/Feature/DebugDatabaseTest.php`: ทดสอบกลุ่ม DebugDatabase.
- `tests/Feature/EstimateLeadStoreTest.php`: ทดสอบกลุ่ม EstimateLeadStore.
- `tests/Feature/EstimateRecommendationServiceTest.php`: ทดสอบกลุ่ม EstimateRecommendationService.
- `tests/Feature/EvaluatePhoneValidationTest.php`: ทดสอบกลุ่ม EvaluatePhoneValidation.
- `tests/Feature/ExampleTest.php`: ทดสอบกลุ่ม Example.
- `tests/Feature/FacebookPostTest.php`: ทดสอบกลุ่ม FacebookPost.
- `tests/Feature/FetchLatestLotteryCommandTest.php`: ทดสอบกลุ่ม FetchLatestLotteryCommand.
- `tests/Feature/ImportPostpaidSnapshotCommandTest.php`: ทดสอบกลุ่ม ImportPostpaidSnapshotCommand.
- `tests/Feature/ImportTruePrepaidCsvCommandTest.php`: ทดสอบกลุ่ม ImportTruePrepaidCsvCommand.
- `tests/Feature/ImportTruePrepaidNumbersCommandTest.php`: ทดสอบกลุ่ม ImportTruePrepaidNumbersCommand.
- `tests/Feature/LineNotificationTest.php`: ทดสอบกลุ่ม LineNotification.
- `tests/Feature/LineTestModeTest.php`: ทดสอบกลุ่ม LineMode.
- `tests/Feature/LineWebhookTest.php`: ทดสอบกลุ่ม LineWebhook.
- `tests/Feature/LotteryFlowRefinedTest.php`: ทดสอบกลุ่ม LotteryFlowRefined.
- `tests/Feature/NumberSearchTest.php`: ทดสอบกลุ่ม NumberSearch.
- `tests/Feature/PhoneNumberNumberSumTest.php`: ทดสอบกลุ่ม PhoneNumberNumberSum.
- `tests/Feature/PrepaidOrderFlowTest.php`: ทดสอบกลุ่ม PrepaidOrderFlow.
- `tests/Feature/SEOValidationTest.php`: ทดสอบกลุ่ม SEOValidation.
- `tests/Feature/SeoEstimateTest.php`: ทดสอบกลุ่ม SeoEstimate.
- `tests/Feature/TarotReadingApiTest.php`: ทดสอบกลุ่ม TarotReadingApi.
- `tests/Unit/ExampleTest.php`: ทดสอบกลุ่ม Example.
- `tests/Unit/PhoneNumberSupportedTopicIconsTest.php`: ทดสอบกลุ่ม PhoneNumberSupportedTopicIcons.

คำสั่งแนะนำ:
- **ติดตั้ง backend**: composer install; copy .env.example เป็น .env; ตั้งค่า database, APP_URL, LINE, GA4, Turnstile, Facebook ตามที่ใช้; php artisan key:generate; php artisan migrate --seed.
- **รัน web assets**: npm install; npm run dev สำหรับ Vite development; npm run build สำหรับ production assets.
- **รัน Laravel**: php artisan serve หรือ production web server ที่ชี้ไป public/index.php.
- **รัน queue/scheduler**: scripts/run-notification-worker.sh ใช้กับ queue worker; scripts/install-scheduler-cron.sh ใช้ติดตั้ง cron/scheduler สำหรับ automation.
- **รัน Flutter admin**: cd admin_flutter; flutter pub get; flutter run -d chrome หรือ target desktop/mobile ที่ต้องการ.
- **Deploy/ops scripts**: scripts/deploy-production.sh และ scripts/deploy-cloud-run-demo.sh เป็น helper deploy; scripts/render_lottery_cover.mjs ใช้ render lottery covers; scripts/telegram-codex-bot.mjs เป็น Telegram automation helper.

## 10. ความเสี่ยงและ TODO
- routes/web.php มี closure admin จำนวนมากและไฟล์ใหญ่; การแยกเป็น controller จะดูแลง่ายขึ้น แต่ไม่ได้ refactor ในงานนี้.
- มีไฟล์ local/untracked และการแก้ routes/web.php ที่มีอยู่ก่อนงานนี้; งานนี้เก็บรักษาไว้ไม่ลบทับ.
- external service ต้องมี secret ใน .env และ URL แบบ public HTTPS จึงจะทดสอบ LINE/Facebook/GA4 ได้ครบ.
- flow รูปหวย/บทความใช้ SVG และ optional PNG rendering ควรทดสอบหลังเปลี่ยน image tooling/server.
- route emergency/cron/direct article ควรมองเป็นเครื่องมือ operation ไม่ใช่ feature สาธารณะ.

## 11. การตรวจซ้ำ 3 รอบ
- รอบที่ 1 map file tree, route list, views, migrations, tests, scripts และ Flutter files.
- รอบที่ 2 extract Laravel/Flutter classes/methods และเขียน inventory ด้านบน.
- รอบที่ 3 cross-check features กับ routes, tests, models/services/controllers, Blade views, migrations และ admin Flutter files.
