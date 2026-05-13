# Supernumber Master Documentation (EN)

> [!NOTE]
> This document is a comprehensive guide for both AI agents and developers. it combines high-level business context with deep technical details to ensure consistent development and maintenance of the Supernumber project.

---

## 1. Project Overview
- **Name:** Supernumber
- **Core Business:** Premium/VIP Mobile Number marketplace with a focus on Numerology (ศาสตร์ตัวเลข) and SEO-driven content.
- **Primary Audience:** Thai customers looking for lucky mobile numbers.
- **Admin System:** Includes a web-based dashboard and a Flutter-based mobile app for managers.

### Feature Summary
- **Public Website:** Home page with random available numbers, catalog with advanced filters (text, digit-mask, price, service type), article pages with comments, lottery results embedding, and numerology evaluation tools.
- **Admin Panel:** Session-based web dashboard for inventory, orders, customers, sales documents, analytics, and LINE settings.
- **Flutter Admin App:** Token-authenticated mobile app for article management, content planning, and user management.
- **Integrations:** LINE Messaging API (alerts/broadcasts), Facebook Page posting, Google Analytics 4, Cloudflare Turnstile, and GLO Lottery API.

---

## 2. Tech Stack and Runtime
- **Backend:** PHP 8.2+ / Laravel 12.0
- **Frontend (Web):** Blade Templates + Tailwind CSS v4 + Vite.
- **Admin Mobile:** Flutter SDK ^3.11.3 (located in `/admin_flutter`).
- **Database:** MySQL (strictly managed via Migrations).
- **Key Packages:** Laravel Sanctum (API Auth), Dio (Flutter HTTP), Provider (Flutter State), Axios, PHPUnit.
- **Operations:** Laravel public storage, queues/jobs for notifications, scheduler for publishing and lottery sync, GitHub Actions for CI/CD.

---

## 3. App Structure & Key Directories

### Core Directories
- `app/Models/`: Business entities and logic (Eloquent models).
- `app/Services/`: Complex calculations, external API wrappers, and business services.
- `app/Http/Controllers/`: Laravel controllers (Public, API, and Admin).
- `app/Console/Commands/`: CLI tools for imports, lottery sync, and maintenance.
- `resources/views/`: Blade templates for the web frontend.
- `admin_flutter/`: Source code for the Flutter Admin App.
- `routes/`: Routing definitions (`web.php`, `api.php`).
- `database/migrations/`: Schema history and database structure.
- `tests/`: Feature and Unit tests.
- `scripts/`: Deployment, scheduler, and automation scripts.

### Flutter Admin Structure (`admin_flutter/lib`)
- `services/`: API client (Dio).
- `providers/`: State management (Auth, Article, User).
- `models/`: Data models for serialization.
- `screens/`: UI screens (Login, Article List, Article Edit, JSON Import).
- `utils/`: Helpers like date formatting.

---

## 4. Business Logic & Rules

### Phone Number Management
- **Model:** `app/Models/PhoneNumber.php`.
- **Status Workflow:** `active` (Available), `hold` (Reserved), `sold` (Purchased).
- **Service Types:** `prepaid` (เติมเงิน) and `postpaid` (รายเดือน).
- **Number Sum:** Automatically calculated on save.
- **Numerology (Topics):** Logic based on "Pair Variants" (คู่เลข). Icons are mapped in `TOPIC_ICON_MAP`. Last pair is weighted higher.

### Order Processing
- **Model:** `app/Models/CustomerOrder.php`.
- **Flow:** Booking creates an order -> status syncs to phone number -> payment slip stored -> status updated.
- **Notifications:** LINE alerts are sent for new orders and status updates.

### Article & Content System
- **SEO-centric:** Includes `lsi_keywords`, auto-generated slugs, and metadata.
- **Sanitization:** Uses `ArticleContentSanitizer` to maintain HTML quality.
- **Publishing:** Supports scheduled publishing and auto-posting to Facebook/LINE.
- **Lottery Integration:** Automatic GLO lottery fetch, prize storage, and article syncing.

---

## 5. Routes and API Map

### Public Routes
- `/`: Home page.
- `/numbers`: Catalog search and filtering.
- `/articles`: Article listing and reading.
- `/evaluate` & `/evaluateBadNumber`: Numerology tools.
- `/book`: Checkout and order submission.
- `/line/webhook`: LINE Messaging API entry point.

### Admin Web Panel (`/admin`)
- Inventory (`numbers`), Orders (`orders`), Customers (`customers`).
- Sales Documents & PDFs, Analytics Dashboard.
- LINE Settings, Log Viewer, Test Runner.
- Article CRUD, Content Planning, Comments moderation.

### API Routes (`/api`)
- `POST /api/login`: Authenticate and get token.
- `GET /api/me`: Current user info.
- Article CRUD, JSON Import, Preview/Share tools.
- Manager-only User management.
- `GET /api/tarot`: Throttled AI tarot reading.

---

## 6. Database Schema (Key Tables)
- `users`: Admin/staff accounts with roles (`manager`, `admin`, `staff`).
- `phone_numbers`: Inventory, prices, status, and numerology data.
- `customer_orders`: Booking details, payment slips, and status.
- `articles`: Content, metadata, and scheduling.
- `lottery_results` & `lottery_result_prizes`: GLO results.
- `line_notification_logs` & `line_webhook_events`: LINE integration audit.
- `customers`: Reusable contact records.
- `sales_documents`: PDF metadata and document tracking.

---

## 7. Coding Standards & Workflow

### Development Guidelines
1. **Language:** UI, Error messages, and Content must be in **Thai**.
2. **Logic Placement:** Move complex business logic to **Services** (`app/Services`) or **Traits**.
3. **Design:** Follow "Premium Design" (HSL colors, glassmorphism, modern typography like 'Instrument Sans').
4. **Database:** NEVER edit the database directly. Always use Migrations.
5. **Testing:** Run `php artisan test` before confirming major changes.

### Workflow
1. **Plan:** Provide a step-by-step plan in Thai.
2. **Execute:** Break tasks into modular edits.
3. **Notify:** Ensure Telegram/LINE notifications are triggered where applicable.

---

## 8. Technical Reference (Key Classes)

### Backend Services
- `Ga4AnalyticsService`: Fetches GA4 reporting data.
- `LineNotifier`: Core service for LINE push/broadcast.
- `ArticleContentSanitizer`: HTML cleanup for articles.
- `EstimateRecommendationService`: Logic for phone number matching.
- `SalesDocumentPdfService`: Renders sales PDFs.
- `TarotAiService`: AI prompt builder for tarot readings.

### Flutter Providers
- `AuthProvider`: Manages login/token persistence.
- `ArticleProvider`: Article CRUD and content plan state.
- `UserProvider`: Manager-only user administration.

---

## 9. Operations & Testing
- **Backend Tests:** Located in `tests/Feature`. Covers analytics, orders, articles, lottery, and SEO.
- **Install Commands:** `composer install`, `npm install`, `php artisan migrate --seed`.
- **Deployment:** Managed via `scripts/deploy-production.sh` and GitHub Actions.
- **Background Tasks:** `scripts/run-notification-worker.sh` and `scripts/install-scheduler-cron.sh`.

---

## 10. Risks and TODO
- `routes/web.php` is large and contains many closures; future refactoring into controllers is recommended.
- External services (LINE, Facebook, GA4) require valid `.env` secrets for full functionality.
- Lottery image workflow relies on server-side image tools (SVG/PNG).

*Last Updated: 2026-05-13*
