<!doctype html>
<html lang="th">
  <head>
    @php
      $staticPath = static fn (string $path): string => '/' . ltrim($path, '/');
      $adminNavServiceType = request()->query('service_type');
      $showAllNumbersActive = request()->routeIs('admin.numbers*')
        && ! in_array($adminNavServiceType, \App\Models\PhoneNumber::serviceTypeOptions(), true);
      $showPostpaidNumbersActive = request()->routeIs('admin.numbers')
        && $adminNavServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID;
      $showPrepaidNumbersActive = request()->routeIs('admin.numbers')
        && $adminNavServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID;
    @endphp
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Supernumber Admin')</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ $staticPath('favicon-v2.ico') }}" />
    <link rel="icon" type="image/svg+xml" sizes="any" href="{{ $staticPath('favicon.svg') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $staticPath('favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $staticPath('favicon-16x16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $staticPath('apple-touch-icon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&display=swap"
      rel="stylesheet"
    />
    <style>
      :root {
        --admin-bg: #eef4fb;
        --admin-surface: #ffffff;
        --admin-surface-soft: #f7faff;
        --admin-border: #d7e1f0;
        --admin-border-strong: #c9d7ea;
        --admin-text: #1e2d45;
        --admin-muted: #7488a8;
        --admin-primary: #223a63;
        --admin-primary-soft: #edf3ff;
        --admin-danger: #c54b3d;
        --admin-success: #1b8b6f;
        --admin-shadow: 0 14px 36px rgba(30, 45, 69, 0.08);
        --admin-radius-lg: 24px;
        --admin-radius-md: 18px;
        --admin-radius-sm: 14px;
      }

      * {
        box-sizing: border-box;
      }

      html {
        background: linear-gradient(180deg, #f7faff 0%, var(--admin-bg) 100%);
      }

      body {
        margin: 0;
        min-height: 100vh;
        font-family: "Kanit", sans-serif;
        color: var(--admin-text);
        background: transparent;
      }

      a {
        color: inherit;
      }

      button,
      input,
      select,
      textarea {
        font: inherit;
      }

      .admin-shell {
        width: min(1600px, 100%);
        margin: 0 auto;
        padding-left: 24px;
        padding-right: 24px;
      }

      .admin-topbar {
        background: rgba(255, 255, 255, 0.92);
        border-bottom: 1px solid var(--admin-border);
      }

      .admin-topbar__inner {
        min-height: 88px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
      }

      .admin-brand {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
      }

      .admin-brand__mark {
        width: 28px;
        height: 28px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #1d1816 0%, #46372b 100%);
        border: 1px solid rgba(216, 163, 74, 0.5);
        color: #d8a34a;
        font-family: "Cinzel", serif;
        font-size: 18px;
        font-weight: 700;
        line-height: 1;
        flex: 0 0 auto;
      }

      .admin-brand__text {
        display: grid;
        gap: 1px;
        line-height: 1;
      }

      .admin-brand__title,
      .admin-brand__subtitle {
        font-family: "Cinzel", serif;
        color: #d8a34a;
        white-space: nowrap;
      }

      .admin-brand__title {
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 0.26em;
      }

      .admin-brand__subtitle {
        font-size: 8px;
        font-weight: 600;
        letter-spacing: 0.22em;
        opacity: 0.82;
      }

      .admin-topbar__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
      }

      .admin-menu-toggle {
        display: none;
        width: 40px;
        height: 40px;
        padding: 0;
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        background: var(--admin-surface);
        color: var(--admin-text);
        cursor: pointer;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 4px;
      }

      .admin-menu-toggle span {
        display: block;
        width: 18px;
        height: 2px;
        border-radius: 999px;
        background: currentColor;
      }

      .admin-page {
        padding: 0 0 32px;
      }

      .admin-page > .admin-shell {
        padding-left: 0;
      }

      .admin-layout {
        display: grid;
        grid-template-columns: 240px minmax(0, 1fr);
        gap: 14px;
        align-items: stretch;
      }

      .admin-sidebar {
        position: sticky;
        top: 0;
        align-self: stretch;
      }

      .admin-sidebar-stack {
        min-height: calc(100vh - 88px);
        display: flex;
        flex-direction: column;
      }

      .admin-user-panel,
      .admin-nav {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid var(--admin-border);
        box-shadow: var(--admin-shadow);
      }

      .admin-user-panel {
        padding: 14px 14px 16px;
        border-radius: 0;
        border-bottom: 0;
      }

      .admin-user-panel__label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: var(--admin-muted);
      }

      .admin-user-panel__name {
        margin-top: 4px;
        font-size: 17px;
        line-height: 1.15;
        font-weight: 800;
      }

      .admin-user-panel__meta {
        margin-top: 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
      }

      .admin-user-panel__pill {
        min-height: 28px;
        padding: 4px 10px;
        border: 1px solid var(--admin-border-strong);
        border-radius: 999px;
        background: var(--admin-surface);
        color: #5f7494;
        font-size: 11px;
        font-weight: 700;
      }

      .admin-user-panel__pill--role {
        background: var(--admin-primary-soft);
        color: var(--admin-primary);
      }

      .admin-nav {
        border-radius: 0;
        padding: 12px 10px 0;
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
      }

      .admin-nav__sections {
        display: grid;
        gap: 14px;
        flex: 1 1 auto;
        align-content: start;
      }

      .admin-nav__section {
        display: grid;
        gap: 10px;
      }

      .admin-nav__section + .admin-nav__section {
        padding-top: 14px;
        border-top: 1px solid #e3eaf5;
      }

      .admin-nav__section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 0 4px;
      }

      .admin-nav__section-label {
        font-size: 14px;
        font-weight: 800;
        line-height: 1.2;
      }

      .admin-nav__section-count {
        min-width: 22px;
        height: 22px;
        padding: 0 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid var(--admin-border-strong);
        background: var(--admin-primary-soft);
        color: #657aa0;
        font-size: 10px;
        font-weight: 700;
      }

      .admin-nav__section-links {
        display: grid;
        gap: 6px;
      }

      .admin-nav__link {
        min-height: 34px;
        padding: 8px 12px;
        border: 1px solid transparent;
        border-radius: 0;
        display: inline-flex;
        align-items: center;
        text-decoration: none;
        color: #7488a8;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
        transition: border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
      }

      .admin-nav__link:hover {
        color: var(--admin-text);
        background: var(--admin-surface-soft);
        border-color: var(--admin-border);
      }

      .admin-nav__link.is-active {
        color: var(--admin-text);
        background: var(--admin-surface-soft);
        border-color: var(--admin-border);
        font-weight: 800;
      }

      .admin-nav__logout {
        margin-top: auto;
        padding: 8px 0 12px;
        border-top: 1px solid #e3eaf5;
      }

      .admin-nav__logout .admin-nav__link {
        width: 100%;
        color: var(--admin-danger);
        background: #fff7f5;
        border-color: #f1d6d1;
        font-weight: 700;
        cursor: pointer;
      }

      .admin-content {
        min-width: 0;
        padding-top: 10px;
        padding-left: 14px;
      }

      .admin-card {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid var(--admin-border);
        border-radius: var(--admin-radius-lg);
        box-shadow: var(--admin-shadow);
      }

      .admin-page-head {
        margin-bottom: 12px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 16px;
        align-items: start;
      }

      .admin-page-head h1,
      .admin-login-card h1 {
        margin: 0;
        font-size: clamp(26px, 3vw, 36px);
        line-height: 1.1;
        font-weight: 800;
        letter-spacing: -0.03em;
      }

      .admin-subtitle {
        margin-top: 8px;
        color: var(--admin-muted);
        font-size: 14px;
        line-height: 1.6;
        font-weight: 600;
      }

      .admin-page-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        flex-wrap: wrap;
      }

      .admin-summary {
        color: var(--admin-muted);
        font-size: 13px;
        line-height: 1.5;
        font-weight: 700;
        text-align: right;
      }

      .admin-panel-stack {
        display: grid;
        gap: 18px;
        margin-bottom: 18px;
      }

      .admin-search-shell {
        display: grid;
        gap: 12px;
      }

      .admin-feature-card__actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
      }

      .admin-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
      }

      .admin-kpi {
        min-width: 180px;
        padding: 14px 16px;
        border-radius: 18px;
        border: 1px solid var(--admin-border);
        background: rgba(255, 255, 255, 0.96);
        box-shadow: var(--admin-shadow);
      }

      .admin-kpi__label {
        color: var(--admin-muted);
        font-size: 12px;
        font-weight: 700;
        line-height: 1.4;
      }

      .admin-kpi__value {
        margin-top: 8px;
        font-size: clamp(26px, 3vw, 34px);
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.03em;
      }

      .admin-alert {
        margin-bottom: 12px;
        padding: 12px 14px;
        border-radius: var(--admin-radius-sm);
        border: 1px solid transparent;
        font-size: 14px;
        line-height: 1.5;
      }

      .admin-alert--success {
        background: #edf9f5;
        border-color: #cbe9de;
        color: var(--admin-success);
      }

      .admin-alert--error {
        background: #fff5f3;
        border-color: #f3d3cd;
        color: #b64637;
      }

      .admin-feature-card {
        padding: 16px 18px;
        margin-bottom: 18px;
      }

      .admin-feature-card__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 12px;
      }

      .admin-feature-card__title {
        margin: 0;
        font-size: 17px;
        font-weight: 800;
      }

      .admin-feature-card__hint {
        margin: 4px 0 0;
        color: var(--admin-muted);
        font-size: 13px;
        line-height: 1.55;
      }

      .admin-form {
        margin: 0;
        display: grid;
        gap: 12px;
      }

      .admin-form--inline {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: end;
      }

      .admin-form--numbers-search {
        grid-template-columns: minmax(0, 1fr) auto;
      }

      .admin-field {
        display: grid;
        gap: 6px;
      }

      .admin-field label {
        color: #516681;
        font-size: 13px;
        font-weight: 700;
      }

      .admin-input,
      .admin-select {
        width: 100%;
        min-height: 42px;
        padding: 10px 14px;
        border: 1px solid var(--admin-border-strong);
        border-radius: 16px;
        background: #ffffff;
        color: var(--admin-text);
        outline: none;
      }

      textarea.admin-input {
        min-height: 120px;
        resize: vertical;
      }

      .admin-input:focus,
      .admin-select:focus {
        border-color: #b7c8e3;
        box-shadow: 0 0 0 4px rgba(34, 58, 99, 0.06);
      }

      .admin-button {
        min-height: 42px;
        padding: 10px 16px;
        border: 1px solid transparent;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        background: var(--admin-primary);
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
      }

      .admin-button--secondary {
        background: #257f70;
      }

      .admin-button--muted {
        background: #eff3f8;
        border-color: var(--admin-border);
        color: var(--admin-text);
      }

      .admin-button--compact {
        min-height: 38px;
        padding: 8px 14px;
        border-radius: 12px;
        font-size: 13px;
      }

      .admin-table-card {
        overflow: hidden;
      }

      .admin-table-wrap {
        overflow-x: auto;
      }

      .admin-table {
        width: 100%;
        min-width: 860px;
        border-collapse: collapse;
      }

      .admin-table th,
      .admin-table td {
        padding: 16px 10px;
        border-bottom: 1px solid #e6edf8;
        text-align: left;
        font-size: 14px;
      }

      .admin-table th {
        background: var(--admin-surface-soft);
        color: #7184a3;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.01em;
      }

      .admin-number {
        font-size: 17px;
        font-weight: 800;
        letter-spacing: -0.02em;
      }

      .admin-muted {
        color: var(--admin-muted);
      }

      .admin-action-cell {
        text-align: right;
      }

      .admin-action-group {
        display: inline-flex;
        gap: 8px;
        justify-content: flex-end;
        flex-wrap: wrap;
      }

      .admin-pagination {
        padding: 16px;
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
      }

      .admin-pagination a,
      .admin-pagination span {
        min-width: 38px;
        min-height: 38px;
        padding: 8px 12px;
        border-radius: 999px;
        border: 1px solid var(--admin-border);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        background: #ffffff;
      }

      .admin-pagination .is-active {
        background: var(--admin-primary);
        border-color: var(--admin-primary);
        color: #ffffff;
      }

      .admin-status-pill {
        min-height: 26px;
        padding: 4px 10px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid transparent;
      }

      .admin-status-pill--active {
        color: #1b8b6f;
        background: #edf9f5;
        border-color: #cbe9de;
      }

      .admin-status-pill--hold {
        color: #a66a14;
        background: #fff8e8;
        border-color: #f0dbad;
      }

      .admin-auth {
        min-height: calc(100vh - 140px);
        display: grid;
        place-items: center;
      }

      .admin-login-card {
        width: min(560px, 100%);
        padding: 22px;
      }

      .admin-footer {
        margin-top: 24px;
        padding-top: 14px;
        border-top: 1px solid var(--admin-border);
      }

      .admin-footer__inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        color: #8b9cb7;
        font-size: 12px;
        font-weight: 600;
      }

      .admin-footer__meta {
        display: inline-flex;
        align-items: center;
        gap: 10px;
      }

      .admin-footer__dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: #4fa8ff;
      }

      .admin-mobile-nav {
        display: none;
      }

      .admin-rte {
        border: 1px solid var(--admin-border-strong);
        border-radius: var(--admin-radius-sm);
        overflow: hidden;
        background: #fff;
      }

      .admin-rte__toolbar {
        padding: 10px;
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        background: var(--admin-surface-soft);
        border-bottom: 1px solid var(--admin-border);
      }

      .admin-rte__btn {
        min-height: 34px;
        min-width: 34px;
        padding: 6px 10px;
        border: 1px solid var(--admin-border);
        border-radius: 10px;
        background: #fff;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
      }

      .admin-rte__editor {
        min-height: 260px;
        padding: 14px;
        line-height: 1.7;
        outline: none;
      }

      .admin-rte__editor:empty::before {
        content: attr(data-placeholder);
        color: #9aadca;
      }

      @media (max-width: 980px) {
        .admin-shell {
          padding-left: 16px;
          padding-right: 16px;
        }

        .admin-page > .admin-shell {
          padding-left: 16px;
          padding-right: 16px;
        }

        .admin-menu-toggle {
          display: inline-flex;
        }

        .admin-layout {
          grid-template-columns: 1fr;
        }

        .admin-sidebar {
          display: none;
        }

        .admin-content {
          padding-top: 16px;
          padding-left: 0;
        }

        .admin-mobile-nav {
          display: none;
          margin: 12px 0 0;
          padding: 12px;
          border: 1px solid var(--admin-border);
          background: rgba(255, 255, 255, 0.96);
          box-shadow: var(--admin-shadow);
        }

        .admin-mobile-nav.is-open {
          display: block;
        }

        .admin-page-head,
        .admin-form--inline {
          grid-template-columns: 1fr;
        }

        .admin-kpi-grid {
          grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .admin-form--numbers-search {
          grid-template-columns: minmax(0, 1fr) auto;
          align-items: end;
        }

        .admin-summary,
        .admin-page-actions {
          text-align: left;
          justify-content: flex-start;
        }
      }

      @media (max-width: 760px) {
        .admin-topbar__inner {
          min-height: 74px;
        }

        .admin-brand {
          gap: 10px;
        }

        .admin-brand__mark {
          width: 26px;
          height: 26px;
          font-size: 16px;
        }

        .admin-brand__title {
          font-size: 13px;
        }

        .admin-brand__subtitle {
          font-size: 7px;
        }

        .admin-page {
          padding-bottom: 24px;
        }

        .admin-feature-card,
        .admin-login-card {
          padding: 16px;
        }

        .admin-table {
          min-width: 720px;
        }

        .admin-kpi-grid {
          grid-template-columns: 1fr;
        }

        .admin-footer__inner {
          flex-direction: column;
          align-items: flex-start;
        }
      }
    </style>
  </head>
  <body>
    @php
      $showAdminSidebar = session('admin_authenticated') && trim($__env->yieldContent('hide_admin_sidebar')) !== '1';
      $adminDisplayName = trim((string) session('admin_user_name', ''));
      $adminDisplayRole = trim((string) session('admin_user_role', ''));
      $adminDisplayId = session('admin_user_id');
      $adminRoleLabel = match ($adminDisplayRole) {
        'manager' => 'ผู้จัดการ',
        'admin' => 'แอดมิน',
        default => 'ผู้ใช้',
      };
      $adminNavGroups = [
        [
          'label' => 'เบอร์',
          'items' => [
            [
              'label' => 'เบอร์ทั้งหมด',
              'url' => route('admin.numbers'),
              'active' => $showAllNumbersActive,
            ],
            [
              'label' => 'เบอร์รายเดือน',
              'url' => route('admin.numbers', ['service_type' => \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID]),
              'active' => $showPostpaidNumbersActive,
            ],
            [
              'label' => 'เบอร์เติมเงิน',
              'url' => route('admin.numbers', ['service_type' => \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID]),
              'active' => $showPrepaidNumbersActive,
            ],
            [
              'label' => 'เบอร์ที่พักไว้',
              'url' => route('admin.hold-numbers'),
              'active' => request()->routeIs('admin.hold-numbers'),
            ],
            [
              'label' => 'คำสั่งซื้อ',
              'url' => route('admin.orders'),
              'active' => request()->routeIs('admin.orders*'),
            ],
          ],
        ],
        [
          'label' => 'ใบเสนอราคา / ใบแจ้งหนี้',
          'items' => [
            [
              'label' => 'ลูกค้า',
              'url' => route('admin.customers'),
              'active' => request()->routeIs('admin.customers*'),
            ],
            [
              'label' => 'รายการเอกสารทั้งหมด',
              'url' => route('admin.saved-sales-documents.index'),
              'active' => request()->routeIs('admin.saved-sales-documents.*') || request()->routeIs('admin.sales-documents'),
            ],
          ],
        ],
        [
          'label' => 'เนื้อหา',
          'items' => [
            [
              'label' => 'บทความ',
              'url' => route('admin.articles'),
              'active' => request()->routeIs('admin.articles*'),
            ],
            [
              'label' => 'คอมเมนต์',
              'url' => route('admin.comments'),
              'active' => request()->routeIs('admin.comments*'),
            ],
            [
              'label' => 'ข้อความติดต่อ',
              'url' => route('admin.contact-messages'),
              'active' => request()->routeIs('admin.contact-messages*'),
            ],
            [
              'label' => 'Lead เลือกเบอร์',
              'url' => route('admin.estimate-leads'),
              'active' => request()->routeIs('admin.estimate-leads*'),
            ],
          ],
        ],
      ];

      if (session('admin_user_role') === 'manager') {
        $adminNavGroups[] = [
          'label' => 'ระบบ',
          'items' => [
            [
              'label' => 'Analytics GA4',
              'url' => route('admin.analytics'),
              'active' => request()->routeIs('admin.analytics*'),
            ],
            [
              'label' => 'ตั้งค่าระบบ',
              'url' => route('admin.line-settings'),
              'active' => request()->routeIs('admin.line-settings*'),
            ],
            [
              'label' => 'ผู้ใช้งาน',
              'url' => route('admin.users'),
              'active' => request()->routeIs('admin.users'),
            ],
            [
              'label' => 'บันทึกระบบ',
              'url' => route('admin.logs'),
              'active' => request()->routeIs('admin.logs'),
            ],
            [
              'label' => 'บันทึกกิจกรรม',
              'url' => route('admin.activity-logs'),
              'active' => request()->routeIs('admin.activity-logs'),
            ],
          ],
        ];
      }
    @endphp

    <header class="admin-topbar">
      <div class="admin-shell admin-topbar__inner">
        <a href="{{ route('admin.numbers') }}" class="admin-brand" aria-label="Supernumber Admin">
          <span class="admin-brand__mark" aria-hidden="true">S</span>
          <span class="admin-brand__text">
            <span class="admin-brand__title">NUMBER</span>
            <span class="admin-brand__subtitle">SUPERNUMBER</span>
          </span>
        </a>
        <div class="admin-topbar__actions">
          @if ($showAdminSidebar)
            <button type="button" class="admin-menu-toggle" id="admin-menu-toggle" aria-expanded="false" aria-controls="admin-mobile-nav">
              <span></span>
              <span></span>
              <span></span>
            </button>
          @endif
        </div>
      </div>
    </header>

    <main class="admin-page">
      <div class="admin-shell">
        @if ($showAdminSidebar)
          <nav id="admin-mobile-nav" class="admin-mobile-nav" aria-label="เมนูผู้ดูแลระบบสำหรับมือถือ">
            <div class="admin-user-panel">
              <div class="admin-user-panel__label">ผู้ใช้งานปัจจุบัน</div>
              <div class="admin-user-panel__name">{{ $adminDisplayName !== '' ? $adminDisplayName : 'ผู้ดูแลระบบ' }}</div>
              <div class="admin-user-panel__meta">
                <span class="admin-user-panel__pill admin-user-panel__pill--role">{{ $adminRoleLabel }}</span>
                @if ($adminDisplayId !== null)
                  <span class="admin-user-panel__pill">ID #{{ $adminDisplayId }}</span>
                @endif
              </div>
            </div>
            <div class="admin-nav">
              <div class="admin-nav__sections">
                @foreach ($adminNavGroups as $group)
                  <div class="admin-nav__section">
                    <div class="admin-nav__section-head">
                      <div class="admin-nav__section-label">{{ $group['label'] }}</div>
                      <div class="admin-nav__section-count">{{ count($group['items']) }}</div>
                    </div>
                    <div class="admin-nav__section-links">
                      @foreach ($group['items'] as $item)
                        <a href="{{ $item['url'] }}" class="admin-nav__link @if ($item['active']) is-active @endif">{{ $item['label'] }}</a>
                      @endforeach
                    </div>
                  </div>
                @endforeach
              </div>
              <form action="{{ route('admin.logout') }}" method="post" class="admin-nav__logout admin-logout-confirm">
                @csrf
                <button type="submit" class="admin-nav__link admin-nav__link--danger">ออกจากระบบ</button>
              </form>
            </div>
          </nav>
        @endif

        <div class="@if ($showAdminSidebar) admin-layout @endif">
          @if ($showAdminSidebar)
            <aside class="admin-sidebar">
              <div class="admin-sidebar-stack">
                <div class="admin-user-panel">
                  <div class="admin-user-panel__label">ผู้ใช้งานปัจจุบัน</div>
                  <div class="admin-user-panel__name">{{ $adminDisplayName !== '' ? $adminDisplayName : 'ผู้ดูแลระบบ' }}</div>
                  <div class="admin-user-panel__meta">
                    <span class="admin-user-panel__pill admin-user-panel__pill--role">{{ $adminRoleLabel }}</span>
                    @if ($adminDisplayId !== null)
                      <span class="admin-user-panel__pill">ID #{{ $adminDisplayId }}</span>
                    @endif
                  </div>
                </div>

                <nav class="admin-nav" aria-label="เมนูผู้ดูแลระบบ">
                  <div class="admin-nav__sections">
                    @foreach ($adminNavGroups as $group)
                      <div class="admin-nav__section">
                        <div class="admin-nav__section-head">
                          <div class="admin-nav__section-label">{{ $group['label'] }}</div>
                          <div class="admin-nav__section-count">{{ count($group['items']) }}</div>
                        </div>
                        <div class="admin-nav__section-links">
                          @foreach ($group['items'] as $item)
                            <a href="{{ $item['url'] }}" class="admin-nav__link @if ($item['active']) is-active @endif">{{ $item['label'] }}</a>
                          @endforeach
                        </div>
                      </div>
                    @endforeach
                  </div>

                  <form action="{{ route('admin.logout') }}" method="post" class="admin-nav__logout admin-logout-confirm">
                    @csrf
                    <button type="submit" class="admin-nav__link admin-nav__link--danger">ออกจากระบบ</button>
                  </form>
                </nav>
              </div>
            </aside>
          @endif

          <section class="admin-content">
            @yield('content')

            @if (session('admin_authenticated'))
              <footer class="admin-footer">
                <div class="admin-footer__inner">
                  <span class="admin-footer__meta">
                    <span class="admin-footer__dot" aria-hidden="true"></span>
                    <span>&copy; {{ now()->year }}</span>
                  </span>
                  <span>Supernumber admin console.</span>
                </div>
              </footer>
            @endif
          </section>
        </div>
      </div>
    </main>

    <script>
      (() => {
        const toggle = document.getElementById("admin-menu-toggle");
        const menu = document.getElementById("admin-mobile-nav");

        if (!toggle || !menu) return;

        toggle.addEventListener("click", () => {
          const isOpen = menu.classList.toggle("is-open");
          toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });
      })();
    </script>
    @stack('scripts')
  </body>
</html>
