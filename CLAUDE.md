# Supernumber Project — Claude Code Instructions

## Project Identity

- **Name:** Supernumber
- **Type:** Laravel web application + Flutter admin app
- **Port:** 8000 (http://127.0.0.1:8000)
- **Language Support:** Thai & English
- **Email:** sakonlapak.p@gmail.com

## Core Features

1. **Product Management** — เบอร์โทรศัพท์มงคล (phone numbers) and booking status
2. **Article System** — บทความดูดวงและตัวเลข (numerology/tarot articles) for SEO
3. **Lottery Results** — ผลสลากกินแบ่ง (lottery results) automatic fetching
4. **Admin Dashboard** — หน้าจัดการ for administrators
5. **API Backend** — Serves mobile app (Flutter admin in `admin_flutter/`)

## Tech Stack

- **Backend:** PHP / Laravel
- **Frontend:** Blade templates + Tailwind CSS
- **Mobile Admin:** Flutter (`admin_flutter/` directory)
- **Database:** Managed via Laravel migrations only (never direct SQL)
- **Notifications:** LINE Messaging API integration

## Important Files/Docs

- [llms.txt](llms.txt) — Full technical context
- [ai-context.md](ai-context.md) — AI-specific guidance
- [PROJECT_RULES.md](PROJECT_RULES.md) — Standards and requirements
- [number.md](number.md) — Phone number status
- [order.md](order.md) — Order status
- [lottery.md](lottery.md) — Lottery system details

---

## 🇹🇭 Communication & Approval Protocol (บังคับใช้ทุกครั้ง)

**Always follow this sequence for every task:**

### 1. Acknowledge & Plan

- Summarize understanding in **Thai** (สรุปความเข้าใจ)
- Break down the plan into numbered steps (1, 2, 3...)

### 2. Wait for Approval

- **Do NOT execute** until user explicitly approves
- User must respond with: "ตกลง" | "เริ่มได้" | "Confirm" | "ได้จ้า" or similar

### 3. Brief Output

- Use **bullet points** (short & scannable)
- Optimize for mobile reading

**Why:** Ensures user awareness before execution, especially for risky/complex operations.

---

## Testing & Quality Standards

### Pre-Completion Checklist

- ✅ Create or update Test Cases when adding/modifying logic
- ✅ Run `php artisan test` before closing task
- ✅ Report results: "Tests passed: X, Failed: Y"

**Never mark a task complete without passing tests.**

---

## Technical Standards

### Backend Code

- **Language:** PHP / Laravel
- **ORM:** Eloquent (use migrations for all DB changes)
- **Error Handling:** Try-catch for external APIs (LINE, Facebook)

### Frontend Code

- **Template Engine:** Blade (.blade.php)
- **Styling:** Tailwind CSS
- **Location:** `resources/views/admin/`

### Database Rules

- ❌ Never modify database directly
- ✅ Always use Laravel Migrations (`database/migrations/`)

### Key Directories

```
app/                     — PHP models, controllers
resources/views/admin/   — Blade templates
database/migrations/     — Database changes
admin_flutter/          — Flutter admin mobile app
```

---

## Common Commands

```bash
# Laravel
php artisan serve          # Run dev server (port 8000)
php artisan migrate        # Apply migrations
php artisan cache:clear    # Clear all caches
php artisan test          # Run tests

# Flutter (admin_flutter/)
flutter pub get           # Install dependencies
flutter run              # Run debug version
flutter build apk --release  # Build for production
```

## Production Constraints

⚠️ **No direct server access** — Cannot SSH or run terminal commands on production
- When debugging production errors: analyze logs, suggest code fixes, or create artisan commands
- Never suggest SSH commands or direct terminal operations
- Always provide solutions that can be deployed via code changes

---

## Developer Profile

- **Role:** Full-Stack Developer / Mobile Developer
- **Expertise:** PHP/Laravel (backend), Flutter/Dart (mobile), Blade/Tailwind (frontend)
- **Environment:** Apple Ecosystem (iMac, MacBook Pro, iOS development)
- **Working Style:** Prefers efficient, production-ready solutions

---

## Environment Variables

Key `.env` variables:
- `LINE_CHANNEL_ACCESS_TOKEN`, `LINE_GROUP_ID`
- `LINE_ORDER_STATUS_EVENTS` (e.g., "submitted,paid,completed")
- Queue driver for background jobs (notifications queue)

**Alert user immediately if a new `.env` variable is needed.**
