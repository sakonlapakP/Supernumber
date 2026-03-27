<!doctype html>
<html lang="th">
  <head>
    @php
      $staticPath = static fn (string $path): string => '/' . ltrim($path, '/');
    @endphp
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Supernumber')</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ $staticPath('favicon-v2.ico') }}" />
    <link rel="icon" type="image/svg+xml" sizes="any" href="{{ $staticPath('favicon.svg') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $staticPath('favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $staticPath('favicon-16x16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $staticPath('apple-touch-icon.png') }}" />
    <style>
      :root {
        color-scheme: light;
      }

      * {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 24px;
        font-family: "Kanit", sans-serif;
        color: #2c2521;
        background:
          radial-gradient(circle at top left, #f6eddc 0%, rgba(246, 237, 220, 0) 55%),
          linear-gradient(180deg, #f8f3ea 0%, #efe6d6 55%, #f7f5f1 100%);
      }

      .error-shell {
        width: min(100%, 680px);
        padding: 28px 32px;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 24px 48px rgba(42, 35, 33, 0.12);
        text-align: center;
      }

      .error-code {
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 0.08em;
        color: #d8a34a;
        text-transform: uppercase;
      }

      .error-message {
        margin-top: 10px;
        font-size: 32px;
        font-weight: 700;
        line-height: 1.2;
        color: #2a2321;
      }
    </style>
  </head>
  <body>
    <main class="error-shell" role="main">
      <div class="error-code">@yield('code')</div>
      <div class="error-message">@yield('message')</div>
    </main>
  </body>
</html>
