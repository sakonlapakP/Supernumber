# Supernumber Programmer Documentation

Generated from repository inspection on 2026-05-11. This document is for developers maintaining the Laravel website/API and Flutter admin app.

## 1. Tech Stack and Runtime
- Backend: PHP ^8.2, Laravel ^12.0, Laravel Sanctum ^4.0, Laravel Tinker.
- Frontend assets: Vite ^7, Tailwind CSS ^4, Axios, Laravel Vite plugin.
- Admin app: Flutter SDK ^3.11.3, Dio, Provider, Shared Preferences, Intl, Google Fonts, HTML editor, Image Picker, URL Launcher.
- Testing: PHPUnit ^11.5 for Laravel; Flutter test/lints for admin app.
- Storage/ops: Laravel public storage disk, queues/jobs tables, scheduler/worker scripts, generated image/PDF/article assets.

## 2. App Structure
- `app/Http/Controllers`: Laravel controllers for public web, API, and admin controller resources.
- `app/Models`: Eloquent models, computed attributes, scopes, and relationships.
- `app/Services`: Business services for recommendations, notifications, analytics, content sanitizing, posting, PDFs, and external integrations.
- `app/Console/Commands`: CLI imports, lottery fetch, timestamp conversion, scheduled publishing, and admin-user creation.
- `routes/web.php`: Public routes, admin web routes, public order/LINE flows, cron/maintenance routes, and many closure handlers.
- `routes/api.php`: Token-authenticated API routes used by Flutter admin plus throttled tarot endpoint.
- `resources/views`: Blade templates for public pages, admin pages, partials, layouts, and error pages.
- `database/migrations`: Schema history for users, inventory, orders, articles, lottery, LINE logs, customers, submissions, and sales documents.
- `tests`: Feature/unit tests covering public flows, admin flows, imports, API auth, lottery, LINE, SEO, and recommendations.
- `admin_flutter/lib`: Flutter admin app source organized into services, providers, models, screens, and utils.
- `scripts`: Deployment, scheduler, worker, Telegram, and lottery cover helper scripts.

## 3. Feature Inventory
### Public website frontend
Home page showcases random available prepaid/postpaid numbers and a full filter form; catalog supports text search, digit-position masks, service type, package, prepaid price ranges, and default balanced prepaid/postpaid layout; article pages include comments and lottery-result embedding; evaluation, bad-number evaluation, estimate, contact, tiers, sales documents, privacy, and sitemap pages are available. The estimate flow is documented in [estimate.md](/Users/efaum/Sites/localhost/supernumber/estimate.md).

### Admin web frontend
Session-based admin panel covers number inventory/status logs, orders and slips, customers, sales documents/PDFs, analytics settings and dashboard, LINE settings and webhook events, log viewer, test runner, article CRUD/import/planning/covers/social sharing, comments, contact messages, customer submissions, estimate leads, hold numbers, user approval, and activity logs.

### Backend/API
Laravel routes and services implement catalog filtering, order processing, payment slip storage, customer/submission recording, article lifecycle, API auth tokens, role enforcement, notifications, GA4 reporting, Turnstile checks, lottery sync, Facebook posting, tarot AI, and operational cron/maintenance endpoints.

### Flutter admin app
Flutter app authenticates against the API, persists token state, lists/edits/imports/shares articles, requests preview URLs, manages users for managers, renders content-plan tables, and uses Dio/Provider for API/state flow.

### External integrations
LINE Messaging API push/broadcast/webhooks, Facebook Page posting, Google Analytics Data API, Cloudflare Turnstile, GLO lottery API, Telegram bot script, shell deploy/worker/scheduler scripts.

## 4. Routes and API Map
- **Public pages**: /, /numbers, /articles, article comments, /evaluate, /evaluateBadNumber, /tiers, /estimate, /sales-documents, /contact-us, /privacy-policy, sitemap.xml.
- **Booking/order flow**: /book GET/POST plus /book/save-step2 handle service-type-specific checkout, customer details, payment slips, and phone-number hold/sold status.
- **LINE public endpoints**: /line/webhook stores webhook events; signed /line/payment-slips/{order} and /line/lottery-results/{lotteryResult}/image expose assets to LINE.
- **Admin web panel**: /admin login/logout/register plus numbers, orders, customers, sales documents, analytics, LINE settings, logs, tests, articles, comments, submissions, leads, hold numbers, users, and activity logs.
- **Article bypass/ops routes**: direct article save/create routes and cron/maintenance/emergency endpoints exist for operational recovery and publishing automation.
- **API**: /api/login, /api/me, /api/logout, article CRUD/import/share/preview, manager-only users, and throttled tarot reading.

Important route-handler helpers in `routes/web.php`:
- `currentAdmin()`: Returns the currently authenticated admin user from session when the user is still allowed to access the panel.
- `ensureAdmin(?string $requiredRole = null)`: Guards admin routes and optionally enforces an admin/manager role before the closure continues.
- `sanitizeArticleContent(string $content)`: Delegates article HTML cleanup to ArticleContentSanitizer before storing content.
- `articleColumnExists(string $column)`: Caches article table column checks so code can run across partially migrated environments.
- `ensurePublicStorageLink()`: Creates the public storage symlink when article/media routes need it and it is missing.
- `decodeBase64Image(?string $base64String)`: Converts a base64 data URI into a temporary UploadedFile for article image handling.
- `moveTmpImagesToPermanent(string $content, string $articleSlug, int $year)`: Moves article editor temporary images into the final article/year storage folder and rewrites content paths.
- `rejectAdminLogin(Request $request)`: Returns the admin login form with a Thai validation error while preserving non-password input.
- `safelyRunLineNotification(callable $callback, string $message, array $context = [])`: Runs LINE notification callbacks without breaking the user flow when notification delivery fails.
- `storeOrderPaymentSlip(CustomerOrder $order, UploadedFile $file)`: Stores and normalizes uploaded order payment slips, including image/PDF handling for later admin and LINE access.
- `guessFileMimeType(string $path)`: Detects a stored file MIME type for payment-slip preview/download behavior.
- `resolveOrderPaymentSlip(CustomerOrder $order)`: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
- `resolveEnvironmentEditor()`: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
- `resolveAdminLogViewer()`: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
- `storeLineWebhookEvent(array $attributes)`: Persists incoming LINE webhook event metadata and payload for admin review.
- `latestLineWebhookEvents(int $limit = 20)`: Returns recent LINE webhook events for the admin LINE settings screen.
- `buildArticleSlug(string $title, ?int $ignoreId = null)`: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
- `resolvePlannedArticlePublishedAt(?string $slug, ?string $title)`: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
- `resolveArticleImageMeta(string $slug, ?Carbon $date = null)`: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
- `resolveLotteryResultForArticle(Article $article)`: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
- `resolveAnalysisPhone(Request $request)`: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
- `normalizeServiceType(?string $serviceType)`: Normalizes raw input into the canonical internal format.
- `defaultOrderStatus(string $serviceType)`: Chooses the initial order status according to prepaid/postpaid service flow.
- `logPhoneNumberStatusChange(PhoneNumber $phoneNumber, ?string $fromStatus, ?int $userId = null)`: Writes an audit record whenever a phone number status changes.
- `syncPhoneNumberStatusFromOrder(CustomerOrder $order, ?int $userId = null)`: Keeps the linked phone-number status aligned with order status transitions.
- `resolveTestDiscovery()`: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.

## 5. Database Structure
- `users`: Admin/staff accounts with role, status, password, and token ownership.
- `phone_numbers`: Inventory of prepaid/postpaid phone numbers, price/package, network, status, and number sum.
- `phone_number_status_logs`: Audit history for phone number status changes.
- `customer_orders`: Booking/order records with customer details, payment slip, appointment, service type, and status.
- `articles`: SEO/content articles with publish state, covers, metadata, scheduling, and social flags.
- `article_comments`: Moderated comments submitted on public articles.
- `estimate_leads`: Lead data from the recommendation/estimate form documented in [estimate.md](/Users/efaum/Sites/localhost/supernumber/estimate.md).
- `lottery_results`: Lottery draw metadata, source payload, fetch status, and completion state.
- `lottery_result_prizes`: Prize rows belonging to each lottery draw.
- `line_notification_logs`: Outgoing LINE push/broadcast log with payload, status, attempts, response, and errors.
- `line_webhook_events`: Incoming LINE webhook payload audit log.
- `contact_messages`: Public contact form messages.
- `customers`: Reusable customer contact records.
- `sales_documents`: Saved sales documents and generated PDF metadata.
- `customer_submissions`: Normalized audit trail for public form submissions.
- `article_plans`: Content planning rows for article topics and planned publishing.
- `pair_meanings`: Numerology pair meaning reference data.
- `personal_access_tokens`: Sanctum-compatible API tokens for Flutter/admin API access.
- `cache/cache_locks/jobs/job_batches/failed_jobs/sessions/password_reset_tokens`: Laravel framework infrastructure tables.

Migration touchpoints:
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

## 6. Laravel Classes and Functions
### API controllers

- `ArticleController` in `app/Http/Controllers/Api/ArticleController.php`: Provides token-authenticated article CRUD, JSON import, preview URL generation, and social sharing APIs for the Flutter admin app.
  - `index(Request $request)` [public]: Lists records or renders the main listing screen for this resource.
  - `importJson(Request $request)` [public]: Imports external data into application records.
  - `store(Request $request)` [public]: Validates input and creates a new record.
  - `show(Article $article)` [public]: Returns or renders one record.
  - `previewUrl(Article $article)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `share(Request $request, Article $article)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `update(Request $request, Article $article)` [public]: Validates input and updates an existing record.
  - `destroy(Article $article)` [public]: Deletes or archives an existing record.
  - `stringValue(mixed $value)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `booleanValue(mixed $value, bool $default)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `imageGuidelinesValue(mixed $value)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `removeMissingArticleColumns(array &$data)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `articleColumnExists(string $column)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `articleSquareImageUrl(Article $article)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `uniqueArticleSlug(string $value)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `AuthController` in `app/Http/Controllers/Api/AuthController.php`: Authenticates API users, issues/revokes personal access tokens, and returns current user metadata.
  - `login(Request $request)` [public]: Validates credentials and creates an authenticated API/session state.
  - `logout(Request $request)` [public]: Revokes the current authentication state.
  - `me(Request $request)` [public]: Returns the current authenticated user payload.

- `TarotReadingController` in `app/Http/Controllers/Api/TarotReadingController.php`: Validates tarot-reading requests and delegates AI reading generation to the tarot service.
  - `__invoke(Request $request, TarotAiService $tarotAiService)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `UserController` in `app/Http/Controllers/Api/UserController.php`: Provides manager-only API user management for the Flutter admin app.
  - `index()` [public]: Lists records or renders the main listing screen for this resource.
  - `store(Request $request)` [public]: Validates input and creates a new record.
  - `show(User $user)` [public]: Returns or renders one record.
  - `update(Request $request, User $user)` [public]: Validates input and updates an existing record.
  - `destroy(User $user)` [public]: Deletes or archives an existing record.

### Admin controllers

- `ArticlePlanController` in `app/Http/Controllers/Admin/ArticlePlanController.php`: Stores, updates, and deletes admin article planning rows.
  - `store(Request $request)` [public]: Validates input and creates a new record.
  - `update(Request $request, ArticlePlan $articlePlan)` [public]: Validates input and updates an existing record.
  - `destroy(ArticlePlan $articlePlan)` [public]: Deletes or archives an existing record.

- `RegisterController` in `app/Http/Controllers/Admin/RegisterController.php`: Handles pending admin registration from the web admin panel.
  - `showRegistrationForm()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `register(Request $request)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

### Console commands

- `ConvertToTimestamps` in `app/Console/Commands/ConvertToTimestamps.php`: Maintenance command for timestamp conversion.
  - `handle()` [public]: Runs the command, middleware, or queued job entrypoint.
  - `convertTable(string $table, array $columns)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `success(string $message)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `CreateAdminUserCommand` in `app/Console/Commands/CreateAdminUserCommand.php`: CLI command to create an admin user.
  - `handle()` [public]: Runs the command, middleware, or queued job entrypoint.

- `FetchLatestLotteryCommand` in `app/Console/Commands/FetchLatestLotteryCommand.php`: Fetches latest GLO lottery results, stores prizes, syncs lottery articles/covers, and sends LINE notifications.
  - `handle()` [public]: Runs the command, middleware, or queued job entrypoint.
  - `resolveTargetDate(Carbon $now)` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `isInScheduleWindow(Carbon $now)` [private]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `isEligibleScheduleDate(Carbon $now)` [private]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `isRetryDay(Carbon $now)` [private]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `handleRetryDayEnd(LotteryResult $result, Carbon $targetDate, Carbon $now)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `syncLotteryArticleCover(LotteryResult $result, Carbon $now, bool $wasAlreadyComplete = false)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `extractDrawDate(array $payload)` [private]: Parses input data and extracts normalized values for storage or processing.
  - `extractPrizes(array $payload)` [private]: Parses input data and extracts normalized values for storage or processing.
  - `isCompletePayload(array $prizes, ?Carbon $source, Carbon $storage)` [private]: Boolean guard/helper for permissions, configuration, status, or rule matching.

- `ImportPostpaidSnapshotCommand` in `app/Console/Commands/ImportPostpaidSnapshotCommand.php`: Imports postpaid phone inventory from SQL snapshot files.
  - `handle()` [public]: Runs the command, middleware, or queued job entrypoint.
  - `parseRecords(string $contents)` [private]: Parses input data and extracts normalized values for storage or processing.
  - `normalizePhoneNumber(mixed $value)` [private]: Normalizes raw input into the canonical internal format.
  - `resolveImportedStatus(?PhoneNumber $existing)` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.

- `ImportTruePrepaidCsvCommand` in `app/Console/Commands/ImportTruePrepaidCsvCommand.php`: Imports prepaid phone inventory from CSV files.
  - `handle()` [public]: Runs the command, middleware, or queued job entrypoint.
  - `matchHeaderColumns(array $header)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `normalizePhoneNumber(mixed $value)` [private]: Normalizes raw input into the canonical internal format.
  - `normalizeInteger(mixed $value)` [private]: Normalizes raw input into the canonical internal format.
  - `shouldSkipStatus(string $status)` [private]: Boolean guard/helper for permissions, configuration, status, or rule matching.

- `ImportTruePrepaidNumbersCommand` in `app/Console/Commands/ImportTruePrepaidNumbersCommand.php`: Imports prepaid phone inventory from XLSX workbooks.
  - `handle()` [public]: Runs the command, middleware, or queued job entrypoint.
  - `parseWorkbook(string $path)` [private]: Parses input data and extracts normalized values for storage or processing.
  - `parseWorkbookSheets(string $workbookXml, string $workbookRelsXml)` [private]: Parses input data and extracts normalized values for storage or processing.
  - `parseWorksheet(string $sheetXml, array $sharedStrings, string $sheetName)` [private]: Parses input data and extracts normalized values for storage or processing.
  - `parseSharedStrings(?string $xml)` [private]: Parses input data and extracts normalized values for storage or processing.
  - `loadXml(string $xml)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `loadRelationshipsXml(string $xml)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `extractCellValue(SimpleXMLElement $cell, array $sharedStrings)` [private]: Parses input data and extracts normalized values for storage or processing.
  - `extractColumnName(string $reference)` [private]: Parses input data and extracts normalized values for storage or processing.
  - `normalizeInteger(mixed $value)` [private]: Normalizes raw input into the canonical internal format.
  - `matchHeaderColumns(array $cells)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `normalizePhoneNumber(mixed $value)` [private]: Normalizes raw input into the canonical internal format.
  - `shouldSkipStatus(string $status)` [private]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `resolveImportedStatus(?PhoneNumber $phoneNumber)` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `resolveImportPath(string $path)` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.

- `PublishScheduledArticles` in `app/Console/Commands/PublishScheduledArticles.php`: Publishes due scheduled articles.
  - `handle()` [public]: Runs the command, middleware, or queued job entrypoint.

### Factories

- `UserFactory` in `database/factories/UserFactory.php`: Laravel class used by the application flow.
  - `definition()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `unverified()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

### Jobs

- `SendLinePushJob` in `app/Jobs/SendLinePushJob.php`: Queue job wrapper for retrying persisted LINE notification logs.
  - `__construct(public readonly int $notificationLogId,)` [public]: Initializes dependencies or value fields for this class.
  - `handle(LineNotifier $lineNotifier)` [public]: Runs the command, middleware, or queued job entrypoint.
  - `failed(Throwable $exception)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

### Middleware

- `ApiTokenAuth` in `app/Http/Middleware/ApiTokenAuth.php`: Authenticates API requests from Sanctum-style bearer tokens and rejects inactive/non-admin users.
  - `handle(Request $request, Closure $next)` [public]: Runs the command, middleware, or queued job entrypoint.
  - `resolveUserFromBearerToken(?string $bearerToken)` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.

- `CheckRole` in `app/Http/Middleware/CheckRole.php`: Enforces role-based API access for admin and manager routes.
  - `handle(Request $request, Closure $next, ...$roles)` [public]: Runs the command, middleware, or queued job entrypoint.

### Models

- `Article` in `app/Models/Article.php`: Flutter article data model for JSON serialization/deserialization.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `scopePublished(Builder $query)` [public]: Eloquent query scope used to constrain reusable database queries.
  - `author()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `comments()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `approvedComments()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `sanitizedContent()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `ArticleComment` in `app/Models/ArticleComment.php`: Stores article comments and approval state.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `scopeApproved(Builder $query)` [public]: Eloquent query scope used to constrain reusable database queries.
  - `article()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `approver()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `ArticlePlan` in `app/Models/ArticlePlan.php`: Stores planned article topics and publish dates.
  - `isPast()` [public]: Boolean guard/helper for permissions, configuration, status, or rule matching.

- `ContactMessage` in `app/Models/ContactMessage.php`: Stores contact form messages.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `Customer` in `app/Models/Customer.php`: Stores reusable customer identity/contact records.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `getDisplayNameAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getContactNameAttribute()` [public]: Eloquent computed attribute for display or derived business data.

- `CustomerOrder` in `app/Models/CustomerOrder.php`: Stores public booking/order details, payment slip metadata, service type, status, and customer-facing computed labels.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `booted()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `statusOptions()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `defaultStatusForServiceType(?string $serviceType)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `resolvePhoneNumberStatus(?string $orderStatus)` [public]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `getIsPostpaidAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getIsPrepaidAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getServiceTypeLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getPaymentLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getFullNameAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getShippingAddressAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `phoneNumber()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `lineNotificationLogs()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `normalizeServiceType(?string $serviceType)` [private]: Normalizes raw input into the canonical internal format.
  - `normalizeStatus(?string $status)` [private]: Normalizes raw input into the canonical internal format.

- `CustomerSubmission` in `app/Models/CustomerSubmission.php`: Audit model for public form submissions linked to customers.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `customer()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `formTypeLabels()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `getFormTypeLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.

- `EstimateLead` in `app/Models/EstimateLead.php`: Stores estimate/recommendation leads and labels gender, work type, and goal values. See [estimate.md](/Users/efaum/Sites/localhost/supernumber/estimate.md) for the current form semantics and option list.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `lineNotificationLogs()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `genderLabels()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `workTypeLabels()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `goalLabels()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `getFullNameAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getGenderLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getWorkTypeLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getGoalLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.

- `LineNotificationLog` in `app/Models/LineNotificationLog.php`: Auditable log of queued/sent/failed LINE messages.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `notifiable()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `LineWebhookEvent` in `app/Models/LineWebhookEvent.php`: Stores incoming LINE webhook payloads for review and setup.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `LotteryResult` in `app/Models/LotteryResult.php`: Stores one lottery draw and normalizes draw-date fields.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `drawDate()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `sourceDrawDate()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `prizes()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `asDateOnlyCarbon(?string $value)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `normalizeDateOnly(mixed $value)` [private]: Normalizes raw input into the canonical internal format.

- `LotteryResultPrize` in `app/Models/LotteryResultPrize.php`: Stores individual prize rows for a lottery draw.
  - `result()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `PairMeaning` in `app/Models/PairMeaning.php`: Stores numerology pair meaning reference content.

- `PhoneNumber` in `app/Models/PhoneNumber.php`: Core model for prepaid/postpaid number inventory, catalog filters, formatting, price labels, status, and numerology topic support.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `booted()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `scopeAvailable(Builder $query)` [public]: Eloquent query scope used to constrain reusable database queries.
  - `scopeMatchingPattern(Builder $query, ?string $pattern)` [public]: Eloquent query scope used to constrain reusable database queries.
  - `scopeOfServiceType(Builder $query, ?string $serviceType)` [public]: Eloquent query scope used to constrain reusable database queries.
  - `scopePostpaid(Builder $query)` [public]: Eloquent query scope used to constrain reusable database queries.
  - `scopePrepaid(Builder $query)` [public]: Eloquent query scope used to constrain reusable database queries.
  - `scopeSupportedNetwork(Builder $query)` [public]: Eloquent query scope used to constrain reusable database queries.
  - `buildSearchPattern(array $filters)` [public]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `getFormattedNumberAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `format(?string $phoneNumber)` [public]: Formats data for display.
  - `getPackageLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getOfferLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getPaymentLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getInitialPaymentLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getInitialPaymentHtmlAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getNormalizedPackagePriceAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getIsPostpaidAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getIsPrepaidAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getServiceTypeLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getNetworkLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getTierLabelAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `getTierClassAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `buildPackageLabel(?string $planName, mixed $salePrice)` [public]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `parsePackageLabel(?string $label)` [public]: Parses input data and extracts normalized values for storage or processing.
  - `packageLabelsForQuery(Builder $query)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `supportedNetworkCodes()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `networkLabel(?string $networkCode)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `getSupportedTopicIconsAttribute()` [public]: Eloquent computed attribute for display or derived business data.
  - `buildSupportedTopicIcons(?string $phoneNumber)` [public]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `buildPairVariants(string $pair)` [protected]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `topicPairMap()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `prepaidPriceRanges()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `prepaidPriceRangeOptions()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `resolvePrepaidPriceRange(?string $value)` [public]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `serviceTypeOptions()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `calculateNumberSum(mixed $phoneNumber)` [public]: Calculates a derived value from stored/raw data.
  - `adminStatusOptions()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `statusLogs()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `orders()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `firstDigit(mixed $value)` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `digitsOnly(mixed $value)` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `normalizePackagePrice(mixed $salePrice)` [protected]: Normalizes raw input into the canonical internal format.
  - `packagePrice(mixed $salePrice)` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `normalizeServiceType(?string $serviceType)` [protected]: Normalizes raw input into the canonical internal format.
  - `normalizeNetworkCode(?string $networkCode)` [protected]: Normalizes raw input into the canonical internal format.

- `PhoneNumberStatusLog` in `app/Models/PhoneNumberStatusLog.php`: Tracks admin/user status changes on phone numbers.
  - `phoneNumber()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `user()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `SalesDocument` in `app/Models/SalesDocument.php`: Stores saved sales document metadata and generated PDF path.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `customer()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `getFileExistsAttribute()` [public]: Eloquent computed attribute for display or derived business data.

- `User` in `app/Models/User.php`: Admin user model with role/status helpers and token support.
  - `casts()` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `statusLogs()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `roleOptions()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `canAccessAdminPanel()` [public]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `isManager()` [public]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `isAdmin()` [public]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `isStaff()` [public]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `isAtLeastAdmin()` [public]: Boolean guard/helper for permissions, configuration, status, or rule matching.

### Providers

- `AppServiceProvider` in `app/Providers/AppServiceProvider.php`: Laravel class used by the application flow.
  - `register()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `boot()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `guardDestructiveDatabaseCommands()` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

### Public/web controllers

- `Controller` in `app/Http/Controllers/Controller.php`: Laravel class used by the application flow.

- `PublicController` in `app/Http/Controllers/PublicController.php`: Serves public website pages including home, catalog search, articles, evaluation, contact, privacy, and sitemap.
  - `index()` [public]: Lists records or renders the main listing screen for this resource.
  - `numbers(Request $request)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `articles()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `showArticle(string $slug)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `storeArticleComment(Request $request, string $slug)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `evaluate(Request $request, CustomerSubmissionRecorder $submissionRecorder)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `evaluateBad(Request $request)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `tiers()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `contact()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `storeContact(Request $request, TurnstileVerifier $turnstileVerifier, ContactSpamFilter $spamFilter, CustomerSubmissionRecorder $submissionRecorder)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `privacy()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `resolveLotteryResultForArticle(Article $article)` [protected]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `sitemap()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `resolveAnalysisPhone(Request $request)` [protected]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.

### Seeders

- `CloudRunDemoSeeder` in `database/seeders/CloudRunDemoSeeder.php`: Laravel class used by the application flow.
  - `run()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `seedManagerAccount()` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `seedPhoneNumbers()` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `DatabaseSeeder` in `database/seeders/DatabaseSeeder.php`: Laravel class used by the application flow.
  - `run()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `PairMeaningLongSeeder` in `database/seeders/PairMeaningLongSeeder.php`: Laravel class used by the application flow.
  - `run()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `PairMeaningSeeder` in `database/seeders/PairMeaningSeeder.php`: Laravel class used by the application flow.
  - `run()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

### Services

- `AdminLogViewer` in `app/Services/AdminLogViewer.php`: Lists, filters, reads, and clears Laravel log files for managers.
  - `availableFiles()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `resolveFile(?string $selectedFile)` [public]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `readTail(string $path, int $maxBytes = self::MAX_READ_BYTES)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `parseEntries(string $content)` [public]: Parses input data and extracts normalized values for storage or processing.
  - `filterEntries(array $entries, ?string $level, ?string $date, ?string $search)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `availableLevels(array $entries)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `availableDates(array $entries)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `clearFile(string $path)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `normalizeTimestamp(string $timestamp)` [private]: Normalizes raw input into the canonical internal format.

- `ArticleContentSanitizer` in `app/Services/ArticleContentSanitizer.php`: Sanitizes article HTML while preserving allowed links, images, and formatting.
  - `sanitize(string $html)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `prepareForDom(string $html)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `sanitizeChildren(DOMNode $node)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `sanitizeAttributes(DOMElement $element, string $tag)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `sanitizeLink(DOMElement $element)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `sanitizeImage(DOMElement $element)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `isSafeUrl(string $value, array $allowedSchemes)` [private]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `unwrapElement(DOMElement $element)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `ContactSpamFilter` in `app/Services/ContactSpamFilter.php`: Scores contact messages for spam/honeypot-like signals.
  - `inspect(string $name, string $phone, string $message)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `normalize(string $value)` [private]: Normalizes raw input into the canonical internal format.
  - `countKeywordHits(string $message, array $keywords)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `countMatches(string $pattern, string $message)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `looksGeneratedName(string $name)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `hasSuspiciousPhone(string $phone)` [private]: Boolean guard/helper for permissions, configuration, status, or rule matching.

- `CustomerSubmissionRecorder` in `app/Services/CustomerSubmissionRecorder.php`: Creates customer submission audit rows and reuses/creates customer records from form payloads.
  - `record(Request $request, string $formType, array $payload)` [public]: Records an auditable submission/event from request data.
  - `resolveCustomer(?string $name, ?string $phone, ?string $email)` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `cleanString(mixed $value)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `normalizePhone(mixed $value)` [private]: Normalizes raw input into the canonical internal format.
  - `booleanConsent(mixed $value)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `EnvironmentEditor` in `app/Services/EnvironmentEditor.php`: Reads and updates selected .env keys for admin settings screens.
  - `__construct(private readonly ?string $path = null,)` [public]: Initializes dependencies or value fields for this class.
  - `getMany(array $keys)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `setMany(array $values)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `parseFile()` [private]: Parses input data and extracts normalized values for storage or processing.
  - `parseValue(string $value)` [private]: Parses input data and extracts normalized values for storage or processing.
  - `formatValue(string $value)` [private]: Formats data for display.
  - `resolvePath()` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.

- `EstimateRecommendationService` in `app/Services/EstimateRecommendationService.php`: Builds recommended phone-number sets from work-type topics and selected customer goal rules. The matching flow is described in [estimate.md](/Users/efaum/Sites/localhost/supernumber/estimate.md).
  - `buildResult(EstimateLead $lead, int $limit = 12)` [public]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `recommendedNumbers(array $workTopics, array $goalRule, int $limit, ?string $serviceType = null, array $excludeIds = [])` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `goalRule(?string $goal, ?string $workType)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `applyGoalQuery(Builder $query, array $goalRule)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `goalMatches(?string $phoneNumber, array $goalRule)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `goalScore(?string $phoneNumber, array $goalRule)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `topicCards(array $topics)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `workRuleText(EstimateLead $lead, array $workTopics)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `digitsOnly(?string $value)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `FacebookPagePoster` in `app/Services/FacebookPagePoster.php`: Posts published articles to a configured Facebook Page.
  - `postArticle(Article $article, ?string $manualImageUrl = null)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `Ga4AnalyticsService` in `app/Services/Ga4AnalyticsService.php`: Reads/writes GA4 configuration and fetches dashboard reporting data through Google APIs.
  - `measurementId()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `propertyId()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `dashboardCacheSeconds()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `isClientTrackingConfigured()` [public]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `isReportingConfigured()` [public]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `editableServiceAccountJson()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `serviceAccountEmail()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `normalizeServiceAccountJson(?string $json)` [public]: Normalizes raw input into the canonical internal format.
  - `fetchDashboard(int $days = 30)` [public]: Fetches remote or computed data for this service.
  - `buildDashboard(int $days)` [private]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `runReport(array $payload)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `accessToken()` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `buildSignedJwt(array $credentials)` [private]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `decodeStoredCredentials()` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `rawServiceAccountJsonBase64()` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `base64UrlEncode(string $value)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `firstRow(array $response)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `rows(array $response)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `castMetricValue(string $value)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `resolveGoogleErrorMessage(Response $response)` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.

- `LineEstimateLeadNotifier` in `app/Services/LineEstimateLeadNotifier.php`: Builds LINE notification text for submitted estimate leads.
  - `__construct(private readonly LineNotifier $lineNotifier,)` [public]: Initializes dependencies or value fields for this class.
  - `sendSubmitted(EstimateLead $lead)` [public]: Creates and sends the corresponding notification message.
  - `buildMessage(EstimateLead $lead)` [private]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.

- `LineLotteryImageService` in `app/Services/LineLotteryImageService.php`: Generates lottery result SVG/PNG assets for articles and LINE previews.
  - `buildLineImageUrl(LotteryResult $result)` [public]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `canServeImage(LotteryResult $result)` [public]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `toResponse(LotteryResult $result)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `resolveStoredImage(LotteryResult $result, ?string $preferredExtension = null)` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `resolveArticleSlug(LotteryResult $result)` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `canRenderFallbackPng()` [private]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `renderFallbackPng(LotteryResult $result)` [public]: Renders HTML/image/PDF output for a downstream workflow.
  - `paintBackground($image, int $width, int $height)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `drawTopBrand($image, int $gold, int $goldDark)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `drawDoubleNumberPanel($image, int $x, int $y, int $width, int $boxHeight, string $top, string $bottom, int $panelColor, int $borderColor, int $textColor)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `drawSingleNumberPanel($image, int $x, int $y, int $width, int $height, string $number, int $panelColor, int $borderColor, int $textColor)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `drawGroupPanel($image, int $x, int $y, int $width, int $height, string $title, array $numbers, int $numberSize, int $panelColor, int $borderColor, int $textColor)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `drawCenteredText($image, string $text, string $fontPath, int $size, int $centerX, int $baselineY, int $color)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `drawCenteredTextWithOutline($image, string $text, string $fontPath, int $size, int $centerX, int $baselineY, int $fillColor, int $outlineColor, int $outlineWidth)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `fontPath(string $filename)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `pickFirstPrizeNumber(Collection $prizes, string $nameNeedle, string $fallback)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `pickPrizeNumbers(Collection $prizes, string $nameNeedle, int $limit)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `toThaiDateLabel(Carbon $drawDate)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `absoluteUrl(string $pathOrUrl)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `isPublicHttpsUrl(string $url)` [private]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `generateSquareSvg(LotteryResult $result)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `generateLandscapeSvg(LotteryResult $result)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `getFontBase64(string $filename)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `LineLotteryNotifier` in `app/Services/LineLotteryNotifier.php`: Builds and sends LINE messages for completed lottery draws and retry-window failures.
  - `__construct(private readonly LineNotifier $lineNotifier, private readonly LineLotteryImageService $lineLotteryImageService,)` [public]: Initializes dependencies or value fields for this class.
  - `sendCompleted(LotteryResult $result, ?string $manualImageUrl = null)` [public]: Creates and sends the corresponding notification message.
  - `notifyAdminArticleReady(Article $article, ?Carbon $drawDate = null)` [public]: Creates and sends the corresponding notification message.
  - `sendUnavailableAfterRetryWindow(LotteryResult $result, Carbon $scheduledDrawDate, Carbon $checkedAt)` [public]: Creates and sends the corresponding notification message.
  - `buildMessages(LotteryResult $result, ?string $manualImageUrl = null)` [private]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `buildTextMessage(LotteryResult $result)` [private]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `buildUnavailableAfterRetryMessage(Carbon $scheduledDrawDate, Carbon $checkedAt)` [private]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `pickFirstPrizeNumber(Collection $prizes, string $nameNeedle, string $fallback)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `pickPrizeNumbers(Collection $prizes, string $nameNeedle, int $limit)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `LineNotifier` in `app/Services/LineNotifier.php`: Persists LINE notification payloads, resolves destinations, and delivers push/broadcast requests.
  - `isConfigured(?string $destinationKey = null)` [public]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `queueText(string $eventType, string $message, ?Model $notifiable = null, ?string $destinationKey = null,)` [public]: Persists a notification payload and starts delivery.
  - `queueMessages(string $eventType, array $messages, ?Model $notifiable = null, ?string $destinationKey = null,)` [public]: Persists a notification payload and starts delivery.
  - `queueBroadcastMessages(string $eventType, array $messages, ?Model $notifiable = null)` [public]: Persists a notification payload and starts delivery.
  - `deliverLog(int $logId, int $attempt)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `resolveToken()` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `resolveDestinationId(?string $destinationKey = null)` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `markPermanentFailure(LineNotificationLog $log, int $attempt, string $message)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `buildMessagePreview(array $messages)` [private]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `normalizeResponsePayload(Response $response)` [private]: Normalizes raw input into the canonical internal format.
  - `deliverImmediately(LineNotificationLog $log)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `LineOrderNotifier` in `app/Services/LineOrderNotifier.php`: Builds LINE messages for orders, payment slips, order status changes, admin tests, and contact messages.
  - `__construct(private readonly LineNotifier $lineNotifier,)` [public]: Initializes dependencies or value fields for this class.
  - `sendOrderSubmitted(CustomerOrder $order)` [public]: Creates and sends the corresponding notification message.
  - `sendStatusUpdated(CustomerOrder $order, ?string $previousStatus = null)` [public]: Creates and sends the corresponding notification message.
  - `sendAdminTest(CustomerOrder $order)` [public]: Creates and sends the corresponding notification message.
  - `notifyContactMessage(string $name, string $phone, string $message)` [public]: Creates and sends the corresponding notification message.
  - `shouldNotifyStatus(?string $status)` [public]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `buildOrderMessages(CustomerOrder $order, string $headline, array $extraLines = [])` [private]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `resolveSlipUrl(CustomerOrder $order)` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `resolveLineImageUrl(CustomerOrder $order)` [private]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `isPublicHttpsUrl(string $url)` [private]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `normalizeStatus(?string $status)` [private]: Normalizes raw input into the canonical internal format.
  - `displayStatus(?string $status)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `absoluteUrl(string $pathOrUrl)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `SalesDocumentPdfService` in `app/Services/SalesDocumentPdfService.php`: Renders sales document HTML and saves document records/PDF paths.
  - `renderDocumentHtml(SalesDocument $document, array $options = [])` [public]: Renders HTML/image/PDF output for a downstream workflow.
  - `saveDocument(array $data, ?int $savedByUserId = null)` [public]: Persists generated or submitted data.
  - `buildFileName(string $documentType, string $documentNumber)` [protected]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `buildRelativePdfPath(string $documentType, string $year, string $fileName)` [protected]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `buildViewData(SalesDocument $document, array $options = [])` [protected]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `resolveDateValue(mixed $value)` [protected]: Resolves a normalized value, destination, record, path, or fallback from available inputs/configuration.
  - `trimNullable(mixed $value)` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `TarotAiService` in `app/Services/TarotAiService.php`: Builds prompts and calls the AI provider for tarot reading responses.
  - `generateReading(array $cards, ?string $question = null, string $languageCode = 'th', ?string $type = null,)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `buildPrompt(array $cards, string $question, string $languageCode, string $type)` [private]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `buildCardDescription(array $cards, string $languageCode)` [private]: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `sanitizeStringList(mixed $value)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `languageMessage(string $languageCode, string $english, string $thai)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `TestDiscoveryService` in `app/Services/TestDiscoveryService.php`: Discovers PHPUnit tests and runs selected test filters from the admin tests page.
  - `discoverTests()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `runTest(string $filter)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `getClassNameFromFile(string $path)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `hasTestAnnotation(ReflectionMethod $method)` [private]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `extractThaiTitle(?string $docComment, string $methodName)` [private]: Parses input data and extracts normalized values for storage or processing.
  - `translateToThai(string $text)` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `extractCategory(string $className)` [private]: Parses input data and extracts normalized values for storage or processing.

- `TurnstileVerifier` in `app/Services/TurnstileVerifier.php`: Verifies Cloudflare Turnstile tokens for public contact forms.
  - `isEnabled()` [public]: Boolean guard/helper for permissions, configuration, status, or rule matching.
  - `siteKey()` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `verify(string $token, ?string $ipAddress = null)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `secretKey()` [private]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

### Traits

- `UnixTimestampSerializable` in `app/Traits/UnixTimestampSerializable.php`: Trait customizing model date serialization/deserialization for timestamp compatibility.
  - `serializeDate(\DateTimeInterface $date)` [protected]: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `fromDateTime($value)` [public]: Supports this class feature flow with the behavior indicated by its name and surrounding class.

## 7. Flutter Admin Classes and Functions
- `SupernumberAdminApp` in `admin_flutter/lib/main.dart`: Flutter root widget that wires providers and switches between login and article list screens.
  - `main()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `build(BuildContext context)`: Builds a structured value, payload, label, prompt, or query component used by the feature flow.

- `Article` in `admin_flutter/lib/models/article.model.dart`: Flutter article data model for JSON serialization/deserialization.
  - `toJson()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `ArticleProvider` in `admin_flutter/lib/providers/article_provider.dart`: Flutter state provider for article listing, CRUD, import, preview, sharing, and content plans.
  - `fetchArticles({String? monthPlan})`: Fetches remote or computed data for this service.
  - `fetchArticleDetail(int id)`: Fetches remote or computed data for this service.
  - `fetchPreviewUrl(Article article)`: Fetches remote or computed data for this service.
  - `shareArticle(Article article, String platform)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `saveArticle(Article article, { String? landscapePath, String? squarePath, })`: Persists generated or submitted data.
  - `deleteArticle(Article article)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `importArticlesFromJson(String jsonData)`: Imports external data into application records.
  - `_extractErrorMessage(DioException error)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `AuthProvider` in `admin_flutter/lib/providers/auth_provider.dart`: Flutter state provider for login/logout/session persistence.
  - `_loadAuthStatus()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `login(String login, String password)`: Validates credentials and creates an authenticated API/session state.
  - `_extractErrorMessage(DioException error)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `logout()`: Revokes the current authentication state.

- `UserProvider` in `admin_flutter/lib/providers/user_provider.dart`: Flutter state provider for manager-only user management.
  - `fetchUsers()`: Fetches remote or computed data for this service.
  - `createUser(Map<String, dynamic> userData)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_extractErrorMessage(DioException error)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `AdminRegisterScreen, _AdminRegisterScreenState` in `admin_flutter/lib/screens/admin_register_screen.dart`: Flutter/admin registration UI.
  - `_handleSubmit()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `build(BuildContext context)`: Builds a structured value, payload, label, prompt, or query component used by the feature flow.

- `ArticleEditScreen, _ArticleEditScreenState` in `admin_flutter/lib/screens/article_edit_screen.dart`: Flutter article create/edit form with cover images and publishing controls.
  - `initState()`: Initializes Flutter screen state and kicks off initial loading.
  - `_loadFullDetail()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_pickImage(bool isLandscape)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_selectDateTime()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_copyToClipboard(String text, String label)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_save()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `build(BuildContext context)`: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `_buildImagePicker({ required String label, String? imagePath, String? serverPath, required bool isLandscape, })`: Builds a Flutter widget subtree for this screen.
  - `_buildPlaceholder()`: Builds a Flutter widget subtree for this screen.
  - `_buildSectionTitle(String title)`: Builds a Flutter widget subtree for this screen.
  - `_buildTextField(String label, TextEditingController controller, { bool isBold = false, int maxLines = 1, })`: Builds a Flutter widget subtree for this screen.
  - `_buildPublishToggle()`: Builds a Flutter widget subtree for this screen.
  - `_buildAutoPostToggle()`: Builds a Flutter widget subtree for this screen.
  - `_buildDateTimePicker()`: Builds a Flutter widget subtree for this screen.

- `ArticleJsonImportScreen, _ArticleJsonImportScreenState, _ContentPlanTable, _TypePill` in `admin_flutter/lib/screens/article_json_import_screen.dart`: Flutter JSON importer and content-plan preview screen.
  - `dispose()`: Disposes Flutter controllers/listeners.
  - `_pasteFromClipboard()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_insertSample()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_formatJson()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_importJson()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_showSnack(String message, {bool isError = false})`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `build(BuildContext context)`: Builds a structured value, payload, label, prompt, or query component used by the feature flow.

- `ArticleListScreen, _ArticleListScreenState, _ArticleItem, _ArticleActionButton, _StatusPill, _ContentPlanSection, _MonthPlanCard, _PlanItemRow` in `admin_flutter/lib/screens/article_list_screen.dart`: Flutter article management list, filters, actions, and content planning UI.
  - `initState()`: Initializes Flutter screen state and kicks off initial loading.
  - `_openJsonImport()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_openArticleEditor([Article? article])`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_openArticlePreview(Article article)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_shareArticle(Article article)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_confirmDeleteArticle(Article article)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_showAiPromptDialog()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_copyPrompt(String subjectText, String type)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `build(BuildContext context)`: Builds a structured value, payload, label, prompt, or query component used by the feature flow.
  - `_formatArticleDate(Article article)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `_checkIfDone(Map<String, dynamic> item)`: Supports this class feature flow with the behavior indicated by its name and surrounding class.

- `LoginScreen, _LoginScreenState` in `admin_flutter/lib/screens/login_screen.dart`: Flutter login UI for admin users.
  - `_handleLogin()`: Supports this class feature flow with the behavior indicated by its name and surrounding class.
  - `build(BuildContext context)`: Builds a structured value, payload, label, prompt, or query component used by the feature flow.

- `ApiService` in `admin_flutter/lib/services/api_service.dart`: Flutter Dio API client configuration shared by providers.

- `DateFormatter` in `admin_flutter/lib/utils/date_formatter.dart`: Flutter helper for date formatting.

## 8. Business Rules
- Phone inventory: saving normalizes service type/network/status and recalculates number_sum when the phone number changes.
- Catalog search: free-text digits, digit-position masks, service type, package labels, and prepaid price ranges are combined into reusable query filters.
- Default catalog layout: when no filters are selected, prepaid and postpaid numbers are fetched separately and interleaved for balanced display.
- Numerology topics: supported topic icons are scored from the final seven digits, with the last pair weighted higher; topics survive only when good/conditional weight beats bad weight.
- Estimate recommendations: work type selects desired topics; goal rules add required/blocked digit patterns; strict topic matches are preferred but relaxed when inventory is low. The user-facing form, processing screen, and result flow are documented in [estimate.md](/Users/efaum/Sites/localhost/supernumber/estimate.md).
- Orders: public booking creates/updates orders, stores payment slips, and syncs phone status to hold/sold/active depending on order state.
- Submissions/customers: public form submissions are recorded in a common audit table and linked to existing customers by phone/email when possible.
- Articles: content is sanitized, slugs are made unique, covers can be landscape/square, publish state controls public visibility, and social sharing updates LINE flags where columns exist.
- Lottery: scheduled fetch only runs on configured draw/retry windows unless forced; complete results cannot be overwritten by partial payloads; one deterministic article is synced per draw.
- LINE: notifications are logged before delivery; test mode redirects to an admin user; image messages require public HTTPS URLs.

## 9. Testing and Operations
Existing Laravel tests:
- `tests/Feature/AdminAnalyticsTest.php`: covers admin Analytics.
- `tests/Feature/AdminArticleMediaTest.php`: covers admin ArticleMedia.
- `tests/Feature/AdminContactMessagesTest.php`: covers admin ContactMessages.
- `tests/Feature/AdminCustomerQuickStoreTest.php`: covers admin CustomerQuickStore.
- `tests/Feature/AdminCustomerSubmissionsTest.php`: covers admin CustomerSubmissions.
- `tests/Feature/AdminCustomersTest.php`: covers admin Customers.
- `tests/Feature/AdminEstimateLeadsTest.php`: covers admin EstimateLeads.
- `tests/Feature/AdminLineSettingsTest.php`: covers admin LineSettings.
- `tests/Feature/AdminLogsTest.php`: covers admin Logs.
- `tests/Feature/AdminLotteryFlowTest.php`: covers admin LotteryFlow.
- `tests/Feature/AdminOrderPaymentSlipTest.php`: covers admin OrderPaymentSlip.
- `tests/Feature/AdminPermissionTest.php`: covers admin Permission.
- `tests/Feature/AdminPhoneNumberEditTest.php`: covers admin PhoneNumberEdit.
- `tests/Feature/AdminSalesDocumentWorkspaceTest.php`: covers admin SalesDocumentWorkspace.
- `tests/Feature/AdminSavedSalesDocumentTest.php`: covers admin SavedSalesDocument.
- `tests/Feature/AdminUserBootstrapTest.php`: covers admin UserBootstrap.
- `tests/Feature/ApiArticleImportJsonTest.php`: covers API ArticleImportJson.
- `tests/Feature/ApiArticleStorageTest.php`: covers API ArticleStorage.
- `tests/Feature/ApiAuthTest.php`: covers API Auth.
- `tests/Feature/ArticleStorageTest.php`: covers ArticleStorage.
- `tests/Feature/ContactMessageStoreTest.php`: covers ContactMessageStore.
- `tests/Feature/DebugDatabaseTest.php`: covers DebugDatabase.
- `tests/Feature/EstimateLeadStoreTest.php`: covers EstimateLeadStore.
- `tests/Feature/EstimateRecommendationServiceTest.php`: covers EstimateRecommendationService.
- `tests/Feature/EvaluatePhoneValidationTest.php`: covers EvaluatePhoneValidation.
- `tests/Feature/ExampleTest.php`: covers Example.
- `tests/Feature/FacebookPostTest.php`: covers FacebookPost.
- `tests/Feature/FetchLatestLotteryCommandTest.php`: covers FetchLatestLotteryCommand.
- `tests/Feature/ImportPostpaidSnapshotCommandTest.php`: covers ImportPostpaidSnapshotCommand.
- `tests/Feature/ImportTruePrepaidCsvCommandTest.php`: covers ImportTruePrepaidCsvCommand.
- `tests/Feature/ImportTruePrepaidNumbersCommandTest.php`: covers ImportTruePrepaidNumbersCommand.
- `tests/Feature/LineNotificationTest.php`: covers LineNotification.
- `tests/Feature/LineTestModeTest.php`: covers LineMode.
- `tests/Feature/LineWebhookTest.php`: covers LineWebhook.
- `tests/Feature/LotteryFlowRefinedTest.php`: covers LotteryFlowRefined.
- `tests/Feature/NumberSearchTest.php`: covers NumberSearch.
- `tests/Feature/PhoneNumberNumberSumTest.php`: covers PhoneNumberNumberSum.
- `tests/Feature/PrepaidOrderFlowTest.php`: covers PrepaidOrderFlow.
- `tests/Feature/SEOValidationTest.php`: covers SEOValidation.
- `tests/Feature/SeoEstimateTest.php`: covers SeoEstimate.
- `tests/Feature/TarotReadingApiTest.php`: covers TarotReadingAPI.
- `tests/Unit/ExampleTest.php`: covers Example.
- `tests/Unit/PhoneNumberSupportedTopicIconsTest.php`: covers PhoneNumberSupportedTopicIcons.

Recommended commands:
- **Install backend**: composer install; copy .env.example to .env; configure database, APP_URL, LINE, GA4, Turnstile, Facebook as needed; php artisan key:generate; php artisan migrate --seed.
- **Run web assets**: npm install; npm run dev for Vite development; npm run build for production assets.
- **Run Laravel**: php artisan serve, or the configured production web server pointing at public/index.php.
- **Run queues/scheduler**: scripts/run-notification-worker.sh handles queue work; scripts/install-scheduler-cron.sh installs scheduled publishing/lottery style automation where used.
- **Run Flutter admin**: cd admin_flutter; flutter pub get; flutter run -d chrome or the desired desktop/mobile target.
- **Deploy/ops scripts**: scripts/deploy-production.sh and scripts/deploy-cloud-run-demo.sh are deployment helpers; scripts/render_lottery_cover.mjs renders lottery covers; scripts/telegram-codex-bot.mjs is a Telegram automation helper.

## 10. Risks and TODO
- routes/web.php is large and contains many closure-based admin flows; refactoring into controllers would improve maintainability but was intentionally not done here.
- There are existing untracked local data/scratch files and a pre-existing routes/web.php modification; these were preserved.
- External services require valid .env secrets and public HTTPS URLs for full LINE/Facebook/GA4 behavior.
- Lottery/article image workflows mix SVG generation with optional PNG rendering and should be tested after server image-tool changes.
- Admin docs should treat emergency/cron/direct article routes as operational tools, not public product features.

## 11. Three-Pass Coverage Check
- Pass 1 mapped file tree, route list, views, migrations, tests, scripts, and Flutter files.
- Pass 2 extracted Laravel and Flutter classes/methods and wrote the inventories above.
- Pass 3 cross-checked features against routes, tests, models/services/controllers, Blade views, migrations, and admin Flutter files.
