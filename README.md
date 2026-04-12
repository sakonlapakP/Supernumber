<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## LINE Notifications

This project can send LINE Messaging API notifications for:

- Estimate form submissions (`/estimate`)
- Lottery result completion (`lottery:fetch-latest`)
- New number orders (`/book`)
- Admin order status changes for configured statuses
- Manual test sends from the admin order detail page

Environment variables:

- `LINE_CHANNEL_ACCESS_TOKEN`
- `LINE_GROUP_ID` as the default fallback group
- `LINE_ESTIMATE_GROUP_ID`
- `LINE_LOTTERY_GROUP_ID`
- `LINE_ORDER_GROUP_ID`
- `LINE_ORDER_STATUS_GROUP_ID`
- `LINE_TEST_GROUP_ID`
- `LINE_ORDER_STATUS_EVENTS` such as `submitted,paid,completed`
- `LINE_RETRY_TIMES`
- `LINE_RETRY_SLEEP_MS`

Delivery logs are stored in the `line_notification_logs` table.

For background delivery and queue retries, use a queue driver such as `database` and run a worker for the `notifications` queue:

```bash
php artisan queue:work --queue=notifications
```

## Server Scripts

This repository includes shell helpers for production server setup:

- `scripts/deploy-production.sh` installs PHP and Node dependencies, builds assets, installs Playwright Chromium, runs migrations, refreshes Laravel caches, and ensures `public/storage` is linked.
- `scripts/run-notification-worker.sh` starts a queue worker for the `notifications` queue.
- `scripts/install-scheduler-cron.sh` installs the Laravel scheduler into the current user's crontab.

Typical server flow:

```bash
cd /var/www/supernumber
bash scripts/deploy-production.sh
bash scripts/install-scheduler-cron.sh
bash scripts/run-notification-worker.sh
```

Useful environment overrides:

- `APP_DIR=/var/www/supernumber`
- `BRANCH=main`
- `RUN_GIT_PULL=0`
- `INSTALL_PLAYWRIGHT=0`
- `PLAYWRIGHT_WITH_DEPS=1`
- `QUEUE_CONNECTION=database`

## Google Analytics 4

This project supports GA4 in two layers:

- Frontend tracking with `GA4_MEASUREMENT_ID`
- Manager dashboard reporting in `/admin/analytics` with the GA4 Data API

Environment variables:

- `GA4_MEASUREMENT_ID`
- `GA4_PROPERTY_ID`
- `GA4_SERVICE_ACCOUNT_JSON_BASE64`
- `GA4_DASHBOARD_CACHE_SECONDS`

Notes:

- Frontend tracking strips query strings before sending page URLs to GA4 so phone numbers from routes like `/evaluate?phone=...` are not sent to Google Analytics.
- To enable the manager dashboard, create a Google service account, then grant that service account access to the GA4 property as at least Viewer or Analyst.
- The admin page accepts the raw service account JSON and stores it in `GA4_SERVICE_ACCOUNT_JSON_BASE64` automatically.
