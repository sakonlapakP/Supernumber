@extends('layouts.app')

@section('title', 'Supernumber | เบอร์มงคลที่ใช่สำหรับคุณ')
@section('meta_description', 'ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง ดึงดูดโอกาส และปลดล็อกเส้นทางสำเร็จด้วยเบอร์มงคลที่ใช่สำหรับคุณ')
@section('og_title', 'Supernumber | เบอร์มงคลที่ใช่สำหรับคุณ')
@section('og_description', 'ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง ดึงดูดโอกาส และปลดล็อกเส้นทางสำเร็จด้วยเบอร์มงคลที่ใช่สำหรับคุณ')
@section('canonical', url('/'))
@section('og_url', url('/'))
@section('og_image', asset('images/home_banner.jpg'))
@section('preload_image', asset('images/home_banner.jpg'))

@section('content')
  <section class="hero" aria-labelledby="hero-title">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
      <div class="hero-left">
        <p class="hero-kicker">แค่เปลี่ยนเบอร์ชีวิตคุณก็เปลี่ยน</p>
        <h1 class="hero-title" id="hero-title">
          เบอร์มงคลไม่ต้องซื้อ ใคร ๆ ก็เป็นเจ้าของได้ที่นี่...ที่เดียว
        </h1>
        <p class="hero-subtitle">
          ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง ดึงดูดโอกาส และปลดล็อกเส้นทางสำเร็จ
        </p>
        <form class="hero-form" action="{{ route('evaluate') }}" method="get">
          <label class="hero-label" for="phone">กรอกเบอร์มือถือ</label>
          <div class="hero-input">
            <input id="phone" name="phone" type="tel" placeholder="0XX-XXX-XXXX" />
            <button type="submit">วิเคราะห์</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <section class="numbers" aria-labelledby="numbers-title">
    @php
      $pageSize = 16;
      $maxPages = 6;
      $maxItems = $pageSize * $maxPages;
      $numbersPayload = $numbers->take($maxItems)->map(function ($number) {
          return [
              'phone_number' => $number->phone_number,
              'display_number' => $number->display_number ?: $number->phone_number,
              'package_label' => $number->package_label,
              'good_number_url' => route('good-number', ['number' => $number->phone_number]),
          ];
      })->values();
      $initialNumbers = $numbersPayload->take($pageSize);
      $totalPages = max(1, (int) ceil($numbersPayload->count() / $pageSize));
      $numbersAllUrl = route('numbers.index');
    @endphp
    <div class="container">
      <div class="section-title">
        <h2 id="numbers-title">เบอร์มงคลชีวิต</h2>
        <p>คัดสรรเบอร์เด่นพร้อมพลังงานเหมาะกับคุณ</p>
      </div>

      @if ($numbersPayload->isNotEmpty())
        <div class="card-grid" id="home-card-grid">
          @foreach ($initialNumbers as $number)
            <article class="number-card">
              <div class="card-top">{{ $number['display_number'] }}</div>
              <div class="card-body">
                <div class="card-meta-stack">
                  <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">รายเดือน</span></span>
                  <span class="card-meta-plan">{{ $number['package_label'] }}</span>
                </div>
              </div>
              <a class="card-btn card-btn--buy" href="{{ $number['good_number_url'] }}">สั่งซื้อ</a>
            </article>
          @endforeach
        </div>

        <nav class="numbers-pagination home-pagination" id="home-pagination" aria-label="เปลี่ยนหน้ารายการเบอร์" @if ($numbersPayload->count() <= 16) hidden @endif>
          <button class="numbers-pagination__link" id="home-prev" type="button">ก่อนหน้า</button>
          <span class="numbers-pagination__link is-active" id="home-page-info">1 / {{ $totalPages }}</span>
          <button class="numbers-pagination__link" id="home-next" type="button">ดูต่อไป</button>
        </nav>
      @else
        <p class="numbers-empty">ยังไม่มีเบอร์พร้อมขายในระบบตอนนี้</p>
      @endif
    </div>
  </section>

  @if ($numbersPayload->count() > 16)
    <script>
      (() => {
        const numbers = @json($numbersPayload);
        const pageSize = {{ $pageSize }};
        const totalPages = Math.max(1, Math.ceil(numbers.length / pageSize));
        const allNumbersUrl = @json($numbersAllUrl);

        const grid = document.getElementById("home-card-grid");
        const prevBtn = document.getElementById("home-prev");
        const nextBtn = document.getElementById("home-next");
        const pageInfo = document.getElementById("home-page-info");

        if (!grid || !prevBtn || !nextBtn || !pageInfo) return;

        let currentPage = 1;

        const escapeHtml = (value) =>
          String(value ?? "").replace(/[&<>"']/g, (char) => {
            switch (char) {
              case "&":
                return "&amp;";
              case "<":
                return "&lt;";
              case ">":
                return "&gt;";
              case '"':
                return "&quot;";
              case "'":
                return "&#39;";
              default:
                return char;
            }
          });

        const renderCard = (number) => `
          <article class="number-card">
            <div class="card-top">${escapeHtml(number.display_number)}</div>
            <div class="card-body">
              <div class="card-meta-stack">
                <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">รายเดือน</span></span>
                <span class="card-meta-plan">${escapeHtml(number.package_label)}</span>
              </div>
            </div>
            <a class="card-btn card-btn--buy" href="${escapeHtml(number.good_number_url)}">สั่งซื้อ</a>
          </article>
        `;

        const updatePagerState = () => {
          const isFirst = currentPage <= 1;
          const isLast = currentPage >= totalPages;

          prevBtn.disabled = isFirst;
          nextBtn.disabled = false;

          prevBtn.classList.toggle("is-disabled", isFirst);
          nextBtn.classList.toggle("is-disabled", false);

          nextBtn.textContent = isLast ? "ดูเบอร์ทั้งหมด" : "ดูต่อไป";

          pageInfo.textContent = `${currentPage} / ${totalPages}`;
        };

        const renderPage = (page) => {
          currentPage = Math.min(totalPages, Math.max(1, page));
          const start = (currentPage - 1) * pageSize;
          const pageItems = numbers.slice(start, start + pageSize);
          grid.innerHTML = pageItems.map(renderCard).join("");
          updatePagerState();
        };

        prevBtn.addEventListener("click", () => {
          if (currentPage > 1) renderPage(currentPage - 1);
        });

        nextBtn.addEventListener("click", () => {
          if (currentPage < totalPages) {
            renderPage(currentPage + 1);
            return;
          }

          window.location.href = allNumbersUrl;
        });

        renderPage(1);
      })();
    </script>
  @endif
@endsection
