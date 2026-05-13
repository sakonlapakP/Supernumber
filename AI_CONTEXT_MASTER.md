# AI Project Context: Supernumber (Master Guide)

> [!NOTE]
> This document is designed for AI agents (like Antigravity, Cursor, or Copilot) to understand the project's architecture, business logic, and coding standards instantly.

---

## 1. Project Overview | ภาพรวมโปรเจกต์
- **Name:** Supernumber
- **Core Business:** Premium/VIP Mobile Number marketplace with a focus on Numerology (ศาสตร์ตัวเลข) and SEO-driven content.
- **Primary Audience:** Thai customers looking for lucky mobile numbers.
- **Admin System:** Includes a web-based dashboard and a Flutter-based mobile app for managers.

---

## 2. Technical Stack | ชุดเครื่องมือทางเทคนิค
- **Backend:** PHP 8.2+ / Laravel 10
- **Frontend (Web):** Blade Templates + Tailwind CSS v4.
    - *Note:* The user prefers "Premium Aesthetics" and "Rich Designs." Avoid generic layouts.
- **Admin Mobile:** Flutter (located in `/admin_flutter`).
- **Database:** MySQL (strictly managed via Migrations).
- **Integrations:** 
    - **LINE Messaging API:** For order alerts and article broadcasts.
    - **Cloudflare Turnstile:** Anti-spam for forms.
    - **Google Analytics 4 (GA4):** Backend dashboard for traffic stats.
    - **GitHub Actions:** CI/CD for deployments with Telegram notifications.

---

## 3. Business Logic: Numerology & Status | ตรรกะทางธุรกิจ
- **PhoneNumber Model (`app/Models/PhoneNumber.php`):**
    - **Number Sum:** Automatically calculated on save.
    - **Topic & Icons:** Complex logic based on "Pair Variants" (คู่เลข). Icons are mapped in `TOPIC_ICON_MAP`.
    - **Service Types:** `prepaid` (เติมเงิน) and `postpaid` (รายเดือน).
    - **Status Workflow:** `active` (Available), `hold` (Reserved), `sold` (Purchased).
- **Article System:**
    - SEO-centric with `lsi_keywords` and auto-generated slugs.
    - Uses Gemini AI for content generation.
    - Includes an `ArticleContentSanitizer` to maintain HTML quality.

---

## 4. Authentication & RBAC | ระบบสิทธิ์การใช้งาน
Managed in `app/Models/User.php`:
- **Roles:**
    - `manager`: Full authority, can manage other users.
    - `admin`: General management access.
    - `staff`: Basic access, often read-only for sensitive data.
- **Hierarchy:** `isAtLeastAdmin()` checks if a user is either `admin` or `manager`.

---

## 5. Coding Standards | มาตรฐานการเขียนโค้ด
1. **Language:** UI, Error messages, and Content must be in **Thai**.
2. **Logic Placement:** Move complex business logic to **Services** (e.g., in `app/Services`) or **Traits** if shared.
3. **Design:** Follow the "Premium Design" guidelines:
    - Use vibrant colors (HSL), dark modes, and glassmorphism where appropriate.
    - Use modern typography (Google Fonts like 'Instrument Sans' or 'Inter').
    - Add micro-animations and smooth transitions.
4. **Database:** NEVER edit the database directly. Always use `php artisan make:migration`.
5. **Testing:** 
    - Maintain unit/feature tests in `/tests`.
    - Run `php artisan test` before confirming any major changes.

---

## 6. Development Workflow | ขั้นตอนการทำงาน
1. **Plan & Confirm:** Always provide a step-by-step plan in Thai before execution.
2. **Modular Edits:** Break down large tasks into smaller, manageable chunks.
3. **Notification:** If GitHub Actions are modified, ensure the Telegram notification payload includes relevant commit info.

---

## 7. Key Directories | โฟลเดอร์สำคัญ
- `app/Models/`: Business entities and logic.
- `app/Services/`: Complex calculations and external API wrappers.
- `resources/views/`: Blade templates for the web frontend.
- `admin_flutter/`: Source code for the Flutter Admin App.
- `routes/web.php` & `routes/api.php`: Routing definitions.

---

## 8. Detailed Documentation | เอกสารรายละเอียดเพิ่มเติม
- **Phone Number Status:** [number.md](file:///Users/efaum/Sites/localhost/Supernumber/number.md)
- **Customer Order Status:** [order.md](file:///Users/efaum/Sites/localhost/Supernumber/order.md)

*Last Updated: 2026-05-13*
