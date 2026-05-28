<!doctype html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'Supernumber Sale Portal')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Kanit', sans-serif; background: #f4f6fb; color: #1a1a2e; min-height: 100vh; }

    .sale-nav {
      background: #1a1a2e;
      color: #fff;
      padding: 0 1.5rem;
      height: 56px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 8px rgba(0,0,0,.15);
    }
    .sale-nav__brand { font-weight: 700; font-size: 1.1rem; color: #f5c842; text-decoration: none; }
    .sale-nav__links { display: flex; gap: 1rem; align-items: center; }
    .sale-nav__links a { color: #ccc; text-decoration: none; font-size: .9rem; }
    .sale-nav__links a:hover { color: #fff; }

    .sale-container { max-width: 960px; margin: 0 auto; padding: 2rem 1rem; }

    .sale-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }
    .sale-card__title {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 1rem;
      color: #1a1a2e;
      border-bottom: 2px solid #f4f6fb;
      padding-bottom: .5rem;
    }

    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; font-size: .85rem; font-weight: 500; margin-bottom: .35rem; color: #444; }
    .form-control {
      width: 100%; padding: .6rem .9rem; border: 1.5px solid #dde;
      border-radius: 8px; font-family: inherit; font-size: .95rem;
      transition: border-color .2s;
    }
    .form-control:focus { outline: none; border-color: #f5c842; }
    .form-control.is-invalid { border-color: #e53e3e; }
    .invalid-feedback { color: #e53e3e; font-size: .8rem; margin-top: .25rem; }

    .btn {
      display: inline-flex; align-items: center; justify-content: center;
      padding: .65rem 1.4rem; border-radius: 8px; font-family: inherit;
      font-size: .95rem; font-weight: 600; cursor: pointer; border: none;
      transition: background .2s, transform .1s;
    }
    .btn:active { transform: scale(.98); }
    .btn-primary { background: #f5c842; color: #1a1a2e; }
    .btn-primary:hover { background: #e6b832; }
    .btn-success { background: #38a169; color: #fff; }
    .btn-danger  { background: #e53e3e; color: #fff; }
    .btn-sm { padding: .4rem .9rem; font-size: .85rem; }
    .btn-block { width: 100%; }

    .alert { padding: .8rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .9rem; }
    .alert-success { background: #f0fff4; color: #276749; border: 1px solid #9ae6b4; }
    .alert-error   { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }
    .alert-info    { background: #ebf8ff; color: #2c5282; border: 1px solid #90cdf4; }

    .badge {
      display: inline-block; padding: .2rem .55rem; border-radius: 999px;
      font-size: .75rem; font-weight: 600;
    }
    .badge-pending  { background: #fef3c7; color: #92400e; }
    .badge-approved { background: #d1fae5; color: #065f46; }
    .badge-rejected { background: #fee2e2; color: #991b1b; }

    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; }
    .stat-item { background: #f8f9ff; border-radius: 10px; padding: 1rem; text-align: center; }
    .stat-item__value { font-size: 1.5rem; font-weight: 700; color: #1a1a2e; }
    .stat-item__label { font-size: .8rem; color: #666; margin-top: .25rem; }

    .table { width: 100%; border-collapse: collapse; font-size: .9rem; }
    .table th { background: #f4f6fb; padding: .65rem .8rem; text-align: left; font-weight: 600; color: #555; }
    .table td { padding: .65rem .8rem; border-bottom: 1px solid #f0f0f0; }
    .table tr:last-child td { border-bottom: none; }

    .referral-box {
      background: linear-gradient(135deg, #1a1a2e, #16213e);
      color: #fff;
      border-radius: 12px;
      padding: 1.5rem;
      text-align: center;
    }
    .referral-box__label { font-size: .85rem; color: #aaa; margin-bottom: .5rem; }
    .referral-box__code  { font-size: 2rem; font-weight: 700; letter-spacing: .2em; color: #f5c842; }
    .referral-box__url   { font-size: .8rem; color: #888; margin-top: .5rem; word-break: break-all; }

    @media (max-width: 600px) {
      .sale-container { padding: 1rem .75rem; }
      .stat-item__value { font-size: 1.25rem; }
    }
  </style>
  @stack('styles')
</head>
<body>
  <nav class="sale-nav">
    <a href="{{ route('sale.dashboard') }}" class="sale-nav__brand">Supernumber Sale</a>
    @if(session('sale_authenticated'))
    <div class="sale-nav__links">
      <a href="{{ route('sale.dashboard') }}">Dashboard</a>
      <a href="{{ route('sale.logout') }}">ออกจากระบบ</a>
    </div>
    @endif
  </nav>

  <div class="sale-container">
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    @yield('content')
  </div>

  @stack('scripts')
</body>
</html>
