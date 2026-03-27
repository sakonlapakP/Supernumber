<!doctype html>
<html lang="th">
  <head>
    @php
      $staticPath = static fn (string $path): string => '/' . ltrim($path, '/');
      $cssVersion = @filemtime(public_path('css/supernumber.css')) ?: time();
    @endphp
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Supernumber')</title>
    <meta name="description" content="@yield('meta_description', 'ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง และคัดเบอร์มงคลที่เหมาะกับคุณ')" />
    <meta name="robots" content="@yield('robots', 'index, follow')" />
    <link rel="canonical" href="@yield('canonical', url()->current())" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="@yield('og_title', 'Supernumber')" />
    <meta property="og:description" content="@yield('og_description', 'ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง และคัดเบอร์มงคลที่เหมาะกับคุณ')" />
    <meta property="og:url" content="@yield('og_url', url()->current())" />
    <meta property="og:image" content="@yield('og_image', $staticPath('images/home_banner.jpg'))" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="theme-color" content="@yield('theme_color', '#2a2321')" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ $staticPath('favicon-v2.ico') }}" />
    <link rel="icon" type="image/svg+xml" sizes="any" href="{{ $staticPath('favicon.svg') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $staticPath('favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $staticPath('favicon-16x16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $staticPath('apple-touch-icon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Kanit:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />
    @hasSection('preload_image')
      <link rel="preload" as="image" href="@yield('preload_image')" />
    @endif
    <link rel="stylesheet" href="{{ $staticPath('css/supernumber.css') }}?v={{ $cssVersion }}" />
  </head>
  <body class="@yield('body_class')">
    @hasSection('hide_header')
    @else
      @include('partials.header')
    @endif

    <main>
      @yield('content')
    </main>

    @hasSection('hide_footer')
    @else
      @include('partials.footer')
    @endif

    <script>
      (() => {
        const toggle = document.querySelector(".nav-toggle");
        const menu = document.getElementById("mobile-menu");

        if (!toggle || !menu) return;

        const closeMenu = () => {
          menu.classList.remove("is-open");
          toggle.setAttribute("aria-expanded", "false");
        };

        toggle.addEventListener("click", () => {
          const isOpen = menu.classList.toggle("is-open");
          toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });

        menu.addEventListener("click", (event) => {
          if (event.target.matches("a")) {
            closeMenu();
          }
        });
      })();
    </script>
  </body>
</html>
