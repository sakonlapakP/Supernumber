<!doctype html>
<html lang="th">
  <head>
    @php
      $staticPath = static fn (string $path): string => '/' . ltrim($path, '/');
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
      href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Kanit:wght@300;400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <style>
      :root {
        --admin-bg: #eef3f7;
        --admin-panel: #ffffff;
        --admin-panel-soft: #f8fbff;
        --admin-border: #d6dfeb;
        --admin-border-strong: #bcc9db;
        --admin-text: #162033;
        --admin-muted: #627495;
        --admin-primary: #1d3557;
        --admin-primary-soft: #e9f1fb;
        --admin-green: #1f8f78;
        --admin-green-soft: #e8f7f3;
        --admin-gold: #d8a34a;
        --admin-shadow: 0 16px 36px rgba(19, 34, 58, 0.08);
      }

      * {
        box-sizing: border-box;
      }

      html {
        background: linear-gradient(180deg, #f8fafc 0%, var(--admin-bg) 100%);
      }

      body {
        margin: 0;
        font-family: "Kanit", sans-serif;
        color: var(--admin-text);
        background: transparent;
      }

      a {
        color: inherit;
      }

      .admin-shell {
        width: min(1680px, calc(100% - 64px));
        margin: 0 auto;
      }

      .admin-topbar {
        background: rgba(255, 255, 255, 0.88);
        border-bottom: 1px solid rgba(188, 201, 219, 0.7);
        backdrop-filter: blur(14px);
      }

      .admin-topbar__inner {
        min-height: 92px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
      }

      .admin-brand {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: #d8a34a;
      }

      .admin-brand__mark {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        border: 1px solid rgba(216, 163, 74, 0.6);
        display: grid;
        place-items: center;
        font-family: "Cinzel", serif;
        font-size: 30px;
        font-weight: 700;
        color: #d8a34a;
        background: linear-gradient(120deg, #1d1816, #46372b);
        box-shadow: inset 0 0 12px rgba(216, 163, 74, 0.3);
      }

      .admin-brand__text {
        display: grid;
        line-height: 1;
      }

      .admin-brand__title {
        font-family: "Cinzel", serif;
        font-size: 18px;
        letter-spacing: 0.2em;
        color: #d8a34a;
      }

      .admin-brand__sub {
        margin-top: 2px;
        font-size: 11px;
        letter-spacing: 0.3em;
        color: rgba(216, 163, 74, 0.75);
      }

      .admin-topbar__actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: flex-end;
      }

      .admin-menu-toggle {
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 4px;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        border: 1px solid var(--admin-border);
        background: #ffffff;
        cursor: pointer;
      }

      .admin-menu-toggle span {
        width: 18px;
        height: 2px;
        border-radius: 999px;
        background: var(--admin-text);
        display: block;
      }

      .admin-link {
        color: var(--admin-muted);
        text-decoration: none;
        font-size: 15px;
        font-weight: 500;
      }

      .admin-role-pill,
      .admin-logout {
        min-height: 44px;
        padding: 10px 16px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: #ffffff;
        color: var(--admin-text);
        font-family: inherit;
        font-size: 15px;
        font-weight: 600;
        border: 1px solid var(--admin-border);
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
      }

      .admin-role-pill {
        min-height: 38px;
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 14px;
        font-weight: 700;
      }

      .admin-logout {
        cursor: pointer;
      }

      .admin-page {
        padding: 26px 0 56px;
      }

      .admin-layout {
        display: grid;
        grid-template-columns: 240px minmax(0, 1fr);
        gap: 24px;
        align-items: start;
      }

      .admin-sidebar {
        position: sticky;
        top: 18px;
      }

      .admin-sidebar-stack {
        display: grid;
        gap: 14px;
      }

      .admin-nav {
        margin: 0;
        padding: 16px 14px;
        border-radius: 22px;
        border: 1px solid rgba(188, 201, 219, 0.9);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 251, 255, 0.95) 100%);
        box-shadow: var(--admin-shadow);
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        gap: 10px;
      }

      .admin-user-panel {
        padding: 16px 16px 14px;
        border-radius: 22px;
        border: 1px solid rgba(195, 207, 225, 0.9);
        background:
          radial-gradient(circle at top right, rgba(216, 163, 74, 0.14), transparent 28%),
          linear-gradient(180deg, #ffffff 0%, #f3f8ff 100%);
        box-shadow: var(--admin-shadow);
      }

      .admin-user-panel__label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #7c8dab;
      }

      .admin-user-panel__name {
        margin-top: 6px;
        font-size: 20px;
        font-weight: 800;
        line-height: 1.2;
        color: var(--admin-text);
        word-break: break-word;
      }

      .admin-user-panel__meta {
        margin-top: 8px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
      }

      .admin-user-panel__pill {
        min-height: 28px;
        padding: 5px 10px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid #d6dfeb;
        color: #4a5d7c;
        font-size: 12px;
        font-weight: 700;
      }

      .admin-user-panel__pill--role {
        background: #edf3ff;
        border-color: #c9d7f3;
        color: #26436f;
      }

      .admin-nav__link {
        min-height: 44px;
        padding: 11px 14px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        text-decoration: none;
        font-size: 15px;
        font-weight: 600;
        color: #64789c;
        background: transparent;
        border: 1px solid transparent;
        transition: all 0.16s ease;
      }

      .admin-nav__link:hover {
        color: var(--admin-text);
        background: #f6f9fd;
        border-color: #d7e1ef;
      }

      .admin-nav__link.is-active {
        color: var(--admin-text);
        font-weight: 800;
        background: linear-gradient(180deg, #ffffff 0%, #f7faff 100%);
        border-color: #ccd8ea;
        box-shadow: 0 6px 16px rgba(28, 50, 84, 0.08);
      }

      .admin-nav__link--danger {
        color: #c4322b;
        background: #fff8f8;
        border-color: #f2d2cf;
        font-weight: 700;
      }

      .admin-nav__link--danger:hover {
        color: #a11d17;
        background: #ffecec;
        border-color: #eeb8b2;
      }

      .admin-nav__logout {
        margin: 0;
        margin-top: 6px;
        padding-top: 8px;
        border-top: 1px solid var(--admin-border);
      }

      .admin-nav__logout .admin-nav__link {
        width: 100%;
        cursor: pointer;
        font-family: inherit;
      }

      .admin-content {
        min-width: 0;
      }

      .admin-mobile-nav {
        display: none;
        margin-bottom: 16px;
        padding: 10px;
        border: 1px solid var(--admin-border);
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.94);
        gap: 8px;
      }

      .admin-mobile-nav__logout {
        margin: 0;
        margin-top: 4px;
        padding-top: 8px;
        border-top: 1px solid var(--admin-border);
      }

      .admin-mobile-nav__logout .admin-nav__link {
        width: 100%;
        cursor: pointer;
        font-family: inherit;
      }

      .admin-auth {
        min-height: calc(100vh - 120px);
        display: grid;
        place-items: center;
      }

      .admin-card {
        background: var(--admin-panel);
        border: 1px solid rgba(188, 201, 219, 0.9);
        border-radius: 24px;
        box-shadow: var(--admin-shadow);
      }

      .admin-login-card {
        width: min(520px, 100%);
        padding: 30px;
      }

      .admin-login-card h1,
      .admin-page-head h1 {
        margin: 0;
        font-size: 28px;
        font-weight: 800;
        line-height: 1.15;
      }

      .admin-subtitle {
        margin-top: 8px;
        color: var(--admin-muted);
        line-height: 1.6;
        font-size: 15px;
        font-weight: 500;
      }

      .admin-form {
        margin-top: 18px;
        display: grid;
        gap: 16px;
      }

      .admin-field {
        display: grid;
        gap: 7px;
      }

      .admin-field label {
        font-size: 14px;
        font-weight: 600;
        color: #31405f;
      }

      .admin-input,
      .admin-select {
        width: 100%;
        min-height: 50px;
        border: 1px solid var(--admin-border-strong);
        border-radius: 16px;
        padding: 12px 16px;
        font-family: inherit;
        font-size: 15px;
        color: var(--admin-text);
        background: #fbfdff;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
      }

      .admin-input:focus,
      .admin-select:focus {
        border-color: #9eb1cf;
        box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.08);
      }

      .admin-rte {
        border: 1px solid var(--admin-border-strong);
        border-radius: 14px;
        overflow: hidden;
        background: #ffffff;
      }

      .admin-rte__toolbar {
        padding: 10px;
        border-bottom: 1px solid var(--admin-border);
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        background: #f8fbff;
      }

      .admin-rte__btn {
        min-height: 32px;
        min-width: 32px;
        border-radius: 8px;
        border: 1px solid #d6dfed;
        background: #ffffff;
        color: #273755;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        padding: 6px 10px;
      }

      .admin-rte__btn:hover {
        background: #f1f5fb;
      }

      .admin-rte__editor {
        min-height: 260px;
        padding: 14px 16px;
        line-height: 1.7;
        outline: none;
      }

      .admin-rte__editor:empty:before {
        content: attr(data-placeholder);
        color: #9aa7bd;
      }

      .admin-button {
        min-height: 48px;
        border: 1px solid transparent;
        border-radius: 16px;
        padding: 12px 18px;
        background: linear-gradient(135deg, #27436f 0%, #162c4d 100%);
        color: #ffffff;
        font-family: inherit;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 12px 24px rgba(24, 42, 72, 0.18);
      }

      .admin-button--secondary {
        background: linear-gradient(135deg, #2d9d85 0%, #1f7f6c 100%);
        box-shadow: 0 10px 22px rgba(31, 127, 108, 0.16);
      }

      .admin-button--muted {
        background: #e5e7eb;
        border-color: #d1d5db;
        color: #374151;
        box-shadow: none;
      }

      .admin-button--muted:hover {
        background: #dfe3e8;
      }

      .admin-button--compact {
        min-height: 36px;
        padding: 7px 14px;
        border-radius: 11px;
        font-size: 13px;
        box-shadow: none;
      }

      .admin-alert {
        margin-top: 16px;
        padding: 12px 14px;
        border-radius: 14px;
        font-size: 14px;
        line-height: 1.5;
      }

      .admin-alert--error {
        color: #b42318;
        background: #fef3f2;
        border: 1px solid #fecdca;
      }

      .admin-alert--success {
        color: #067647;
        background: #ecfdf3;
        border: 1px solid #abefc6;
      }

      .admin-page-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
      }

      .admin-toolbar {
        margin-bottom: 18px;
        padding: 18px;
        display: flex;
        align-items: flex-end;
        gap: 12px;
      }

      .admin-toolbar .admin-field {
        flex: 1 1 auto;
      }

      .admin-summary {
        color: #7081a0;
        font-size: 14px;
        font-weight: 600;
      }

      .admin-page-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
      }

      .admin-kpi {
        min-width: 190px;
        padding: 14px 16px;
        border-radius: 18px;
        background: #ffffff;
        border: 1px solid var(--admin-border);
      }

      .admin-kpi__label {
        font-size: 12px;
        font-weight: 600;
        color: var(--admin-muted);
      }

      .admin-kpi__value {
        margin-top: 4px;
        font-size: 24px;
        font-weight: 700;
        line-height: 1;
      }

      .admin-panel-stack {
        display: grid;
        gap: 16px;
        margin-bottom: 18px;
      }

      .admin-feature-card {
        padding: 20px;
      }

      .admin-feature-card__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 14px;
      }

      .admin-feature-card__actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
      }

      .admin-feature-card__title {
        margin: 0;
        font-size: 17px;
        font-weight: 700;
      }

      .admin-feature-card__hint {
        margin: 6px 0 0;
        font-size: 13px;
        line-height: 1.5;
        color: var(--admin-muted);
      }

      .admin-form--inline {
        margin-top: 0;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: end;
      }

      .admin-search-shell {
        display: grid;
        gap: 8px;
      }

      .admin-search-shell .admin-input {
        background: #fbfcfe;
      }

      .admin-feature-card--compact {
        padding: 14px 16px;
      }

      .admin-feature-card--compact .admin-feature-card__head {
        margin-bottom: 8px;
        align-items: center;
      }

      .admin-feature-card--compact .admin-feature-card__hint {
        margin-top: 2px;
        font-size: 12px;
      }

      .admin-feature-card--compact .admin-input {
        min-height: 44px;
        padding: 10px 14px;
      }

      .admin-status-pill {
        min-width: 64px;
        min-height: 28px;
        padding: 5px 10px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0;
        border: 1px solid transparent;
      }

      .admin-status-pill--hold {
        color: #b54708;
        background: #fffaeb;
        border-color: #fedf89;
      }

      .admin-status-pill--active {
        color: #067647;
        background: #ecfdf3;
        border-color: #abefc6;
      }

      .admin-action-cell {
        text-align: right;
      }

      .admin-action-group {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
      }

      .admin-table-card {
        overflow: hidden;
        background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
      }

      .admin-table-wrap {
        overflow-x: auto;
      }

      .admin-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 880px;
      }

      .admin-table th,
      .admin-table td {
        padding: 16px 18px;
        border-bottom: 1px solid #e3eaf4;
        text-align: left;
        vertical-align: middle;
        font-size: 14px;
      }

      .admin-table th {
        font-size: 13px;
        font-weight: 700;
        color: #6f82a3;
        background: #f6f9fd;
        text-transform: none;
        letter-spacing: 0;
      }

      .admin-table tbody tr:nth-child(even) td {
        background: #fbfcfe;
      }

      .admin-table tbody tr:hover td {
        background: #f2f7fd;
      }

      .admin-number {
        font-size: 17px;
        font-weight: 800;
      }

      .admin-muted {
        color: var(--admin-muted);
      }

      .admin-article-title {
        display: inline-block;
        width: 100%;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
      }

      .admin-status-form {
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .admin-status-form .admin-select {
        min-width: 150px;
        min-height: 40px;
        border-radius: 10px;
        padding: 8px 12px;
      }

      .admin-status-form .admin-button {
        min-height: 40px;
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 14px;
      }

      .admin-pagination {
        padding: 18px 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
        background: #fbfcfe;
      }

      .admin-pagination a,
      .admin-pagination span {
        min-width: 38px;
        min-height: 38px;
        padding: 8px 12px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
      }

      .admin-pagination a {
        color: var(--admin-text);
        background: #fff;
        border: 1px solid var(--admin-border);
      }

      .admin-pagination span {
        color: #97a6c1;
        background: #ffffff;
        border: 1px solid var(--admin-border);
      }

      .admin-pagination .is-active {
        color: #ffffff;
        background: var(--admin-primary);
        border-color: var(--admin-primary);
      }

      .admin-footer {
        margin-top: 42px;
        padding-top: 18px;
        border-top: 1px solid #d8e1ed;
      }

      .admin-footer__inner {
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        color: #90a0bb;
        font-size: 14px;
      }

      .admin-footer__meta {
        display: inline-flex;
        align-items: center;
        gap: 12px;
      }

      .admin-footer__dot {
        width: 11px;
        height: 11px;
        border-radius: 999px;
        display: block;
        background: #1da1f2;
        box-shadow: 0 0 0 4px rgba(29, 161, 242, 0.08);
      }

      @media (max-width: 980px) {
        .admin-shell {
          width: min(100%, calc(100% - 32px));
        }

        .admin-layout {
          grid-template-columns: 1fr;
          gap: 16px;
        }

        .admin-sidebar {
          position: static;
          display: none;
        }

        .admin-nav {
          flex-direction: row;
          flex-wrap: wrap;
          padding: 10px;
          gap: 10px;
        }

        .admin-menu-toggle {
          display: inline-flex;
        }

        .admin-mobile-nav.is-open {
          display: flex;
          flex-direction: column;
        }

        .admin-page-head {
          flex-direction: column;
          align-items: flex-start;
        }

        .admin-page-actions {
          width: 100%;
          justify-content: flex-start;
          flex-wrap: nowrap;
        }

        .admin-kpi {
          min-width: 0;
        }

        .admin-topbar__inner {
          min-height: 78px;
        }
      }

      @media (max-width: 760px) {
        .admin-topbar__inner {
          align-items: flex-start;
          flex-direction: column;
          justify-content: center;
          padding: 14px 0;
        }

        .admin-brand__mark {
          width: 40px;
          height: 40px;
          font-size: 25px;
        }

        .admin-brand__title {
          font-size: 15px;
        }

        .admin-brand__sub {
          font-size: 9px;
          letter-spacing: 0.24em;
        }

        .admin-topbar__actions {
          width: 100%;
          justify-content: space-between;
        }

        .admin-page-actions {
          width: 100%;
          display: grid;
          grid-template-columns: minmax(0, 1fr) auto;
          align-items: center;
          gap: 10px;
          flex-wrap: nowrap;
        }

        .admin-kpi {
          min-width: 0;
        }

        .admin-nav {
          flex-direction: column;
          flex-wrap: wrap;
          gap: 10px;
        }

        .admin-user-panel {
          width: 100%;
        }

        .admin-feature-card--compact .admin-feature-card__head {
          align-items: flex-start;
        }

        .admin-form--inline {
          grid-template-columns: 1fr;
        }

        .admin-table--articles {
          min-width: 0;
          table-layout: fixed;
          width: 100%;
        }

        .admin-table--articles th,
        .admin-table--articles td {
          padding: 10px 8px;
          font-size: 12px;
        }

        .admin-table-wrap--articles {
          overflow-x: hidden;
        }

        .admin-table--articles th:nth-child(1),
        .admin-table--articles td:nth-child(1) {
          width: 44%;
        }

        .admin-table--articles th:nth-child(2),
        .admin-table--articles td:nth-child(2) {
          width: 18%;
        }

        .admin-table--articles th:nth-child(3),
        .admin-table--articles td:nth-child(3) {
          width: 20%;
        }

        .admin-table--articles th:nth-child(4),
        .admin-table--articles td:nth-child(4) {
          width: 18%;
        }

        .admin-table--articles .admin-action-cell {
          min-width: 0;
        }

        .admin-table--articles td:first-child {
          max-width: none;
        }

        .admin-table--articles .admin-status-pill {
          min-width: 0;
          padding: 4px 8px;
          font-size: 11px;
        }

        .admin-table--articles td:nth-child(3) {
          white-space: normal;
          word-break: break-word;
        }

        .admin-action-group {
          display: grid;
          justify-items: stretch;
          gap: 6px;
        }

        .admin-action-group .admin-button {
          width: 100%;
          justify-content: center;
          padding: 5px 8px;
          min-height: 30px;
          font-size: 11px;
          border-radius: 8px;
        }

        .admin-login-card {
          padding: 24px;
        }

        .admin-page {
          padding-top: 20px;
        }

        .admin-footer__inner {
          flex-direction: column;
          align-items: flex-start;
        }
      }
    </style>
  </head>
  <body>
    <header class="admin-topbar">
      <div class="admin-shell admin-topbar__inner">
        <a href="{{ route('admin.numbers') }}" class="admin-brand" aria-label="Supernumber Admin">
          <span class="admin-brand__mark" aria-hidden="true">S</span>
          <span class="admin-brand__text">
            <span class="admin-brand__title">NUMBER</span>
            <span class="admin-brand__sub">SUPERNUMBER</span>
          </span>
        </a>
        <div class="admin-topbar__actions">
          @if (session('admin_authenticated') && trim($__env->yieldContent('hide_admin_sidebar')) !== '1')
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
        @php
          $showAdminSidebar = session('admin_authenticated') && trim($__env->yieldContent('hide_admin_sidebar')) !== '1';
          $adminDisplayName = trim((string) session('admin_user_name', ''));
          $adminDisplayRole = trim((string) session('admin_user_role', ''));
          $adminDisplayId = session('admin_user_id');
          $adminRoleLabel = $adminDisplayRole !== '' ? ucfirst($adminDisplayRole) : 'User';
        @endphp

        @if ($showAdminSidebar)
          <nav id="admin-mobile-nav" class="admin-mobile-nav" aria-label="เมนูผู้ดูแลระบบสำหรับมือถือ">
            <div class="admin-user-panel">
              <div class="admin-user-panel__label">Signed In</div>
              <div class="admin-user-panel__name">{{ $adminDisplayName !== '' ? $adminDisplayName : 'Administrator' }}</div>
              <div class="admin-user-panel__meta">
                <span class="admin-user-panel__pill admin-user-panel__pill--role">{{ $adminRoleLabel }}</span>
                @if ($adminDisplayId !== null)
                  <span class="admin-user-panel__pill">ID #{{ $adminDisplayId }}</span>
                @endif
              </div>
            </div>
            <a href="{{ route('admin.numbers') }}" class="admin-nav__link @if (request()->routeIs('admin.numbers')) is-active @endif">All Numbers</a>
            <a href="{{ route('admin.hold-numbers') }}" class="admin-nav__link @if (request()->routeIs('admin.hold-numbers')) is-active @endif">Hold Numbers</a>
            <a href="{{ route('admin.orders') }}" class="admin-nav__link @if (request()->routeIs('admin.orders')) is-active @endif">Orders</a>
            <a href="{{ route('admin.articles') }}" class="admin-nav__link @if (request()->routeIs('admin.articles*')) is-active @endif">Articles</a>
            <a href="{{ route('admin.comments') }}" class="admin-nav__link @if (request()->routeIs('admin.comments*')) is-active @endif">Comments</a>
            @if (session('admin_user_role') === 'manager')
              <a href="{{ route('admin.line-settings') }}" class="admin-nav__link @if (request()->routeIs('admin.line-settings*')) is-active @endif">LINE Settings</a>
              <a href="{{ route('admin.logs') }}" class="admin-nav__link @if (request()->routeIs('admin.logs')) is-active @endif">Application Logs</a>
              <a href="{{ route('admin.users') }}" class="admin-nav__link @if (request()->routeIs('admin.users')) is-active @endif">Users</a>
              <a href="{{ route('admin.activity-logs') }}" class="admin-nav__link @if (request()->routeIs('admin.activity-logs')) is-active @endif">Activity Logs</a>
            @endif
            <form action="{{ route('admin.logout') }}" method="post" class="admin-mobile-nav__logout admin-logout-confirm">
              @csrf
              <button type="submit" class="admin-nav__link admin-nav__link--danger">ออกจากระบบ</button>
            </form>
          </nav>
        @endif

        <div class="@if ($showAdminSidebar) admin-layout @endif">
          @if ($showAdminSidebar)
            <aside class="admin-sidebar">
              <div class="admin-sidebar-stack">
                <div class="admin-user-panel">
                  <div class="admin-user-panel__label">Signed In</div>
                  <div class="admin-user-panel__name">{{ $adminDisplayName !== '' ? $adminDisplayName : 'Administrator' }}</div>
                  <div class="admin-user-panel__meta">
                    <span class="admin-user-panel__pill admin-user-panel__pill--role">{{ $adminRoleLabel }}</span>
                    @if ($adminDisplayId !== null)
                      <span class="admin-user-panel__pill">ID #{{ $adminDisplayId }}</span>
                    @endif
                  </div>
                </div>

                <nav class="admin-nav" aria-label="เมนูผู้ดูแลระบบ">
                <a href="{{ route('admin.numbers') }}" class="admin-nav__link @if (request()->routeIs('admin.numbers')) is-active @endif">All Numbers</a>
                <a href="{{ route('admin.hold-numbers') }}" class="admin-nav__link @if (request()->routeIs('admin.hold-numbers')) is-active @endif">Hold Numbers</a>
                <a href="{{ route('admin.orders') }}" class="admin-nav__link @if (request()->routeIs('admin.orders')) is-active @endif">Orders</a>
                <a href="{{ route('admin.articles') }}" class="admin-nav__link @if (request()->routeIs('admin.articles*')) is-active @endif">Articles</a>
                <a href="{{ route('admin.comments') }}" class="admin-nav__link @if (request()->routeIs('admin.comments*')) is-active @endif">Comments</a>
              @if (session('admin_user_role') === 'manager')
                <a href="{{ route('admin.line-settings') }}" class="admin-nav__link @if (request()->routeIs('admin.line-settings*')) is-active @endif">LINE Settings</a>
                <a href="{{ route('admin.logs') }}" class="admin-nav__link @if (request()->routeIs('admin.logs')) is-active @endif">Application Logs</a>
                <a href="{{ route('admin.users') }}" class="admin-nav__link @if (request()->routeIs('admin.users')) is-active @endif">Users</a>
                <a href="{{ route('admin.activity-logs') }}" class="admin-nav__link @if (request()->routeIs('admin.activity-logs')) is-active @endif">Activity Logs</a>
              @endif
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
        const logoutForms = document.querySelectorAll(".admin-logout-confirm");

        logoutForms.forEach((form) => {
          form.addEventListener("submit", (event) => {
            const ok = window.confirm("ยืนยันออกจากระบบ?");
            if (!ok) {
              event.preventDefault();
            }
          });
        });

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
