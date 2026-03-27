@extends('layouts.app')

@section('title', 'Supernumber | เบอร์มงคลที่ใช่สำหรับคุณ')
@section('meta_description', 'ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง ดึงดูดโอกาส และปลดล็อกเส้นทางสำเร็จด้วยเบอร์มงคลที่ใช่สำหรับคุณ')
@section('og_title', 'Supernumber | เบอร์มงคลที่ใช่สำหรับคุณ')
@section('og_description', 'ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง ดึงดูดโอกาส และปลดล็อกเส้นทางสำเร็จด้วยเบอร์มงคลที่ใช่สำหรับคุณ')
@section('canonical', url('/'))
@section('og_url', url('/'))
@section('og_image', asset('images/home_banner.jpg'))
@section('preload_image', asset('images/home_banner.jpg'))
@section('body_class', 'home-scale-soft')

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
            <input
              id="phone"
              name="phone"
              type="tel"
              inputmode="numeric"
              autocomplete="tel-national"
              placeholder="0xx123456"
              pattern="[0-9]{10}"
              minlength="10"
              maxlength="10"
              value="{{ old('phone') }}"
              aria-describedby="phone-help{{ $errors->has('phone') ? ' phone-error' : '' }}"
              @error('phone') aria-invalid="true" @enderror
              required
            />
            <button type="submit">วิเคราะห์</button>
          </div>
          <p class="hero-help" id="phone-help">กรอกได้เฉพาะตัวเลข 10 หลัก</p>
          @error('phone')
            <p class="hero-error" id="phone-error">{{ $message }}</p>
          @enderror
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
              'good_number_url' => route('evaluate', ['phone' => $number->phone_number]),
          ];
      })->values();
      $initialNumbers = $numbersPayload->take($pageSize);
      $totalPages = max(1, (int) ceil($numbersPayload->count() / $pageSize));
      $startPage = 1;
      $endPage = min($totalPages, 3);
    @endphp
    <div class="container">
      <div class="section-title numbers-catalog-title">
        <div class="numbers-catalog-title__content">
          <h2 id="numbers-title">เบอร์มงคลชีวิต</h2>
          <p>คัดสรรเบอร์เด่นพร้อมพลังงานเหมาะกับคุณ</p>
        </div>
        @if ($numbersPayload->isNotEmpty())
          <div class="numbers-view-toggle" id="home-view-toggle" role="group" aria-label="เลือกรูปแบบการแสดงผลหน้าแรก">
            <button class="numbers-view-toggle__button" type="button" data-view="list" aria-pressed="false">List</button>
            <button class="numbers-view-toggle__button" type="button" data-view="grid" aria-pressed="false">Grid</button>
          </div>
        @endif
      </div>

      @if ($numbersPayload->isNotEmpty())
        <div class="card-grid home-card-grid" id="home-card-grid" data-view="grid">
          @foreach ($initialNumbers as $number)
            <article class="number-card number-card--home">
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
          <span class="numbers-pagination__link is-disabled">ก่อนหน้า</span>
          @for ($page = $startPage; $page <= $endPage; $page++)
            @if ($page === 1)
              <span class="numbers-pagination__link is-active" aria-current="page">{{ $page }}</span>
            @else
              <button class="numbers-pagination__link" type="button" data-page="{{ $page }}">{{ $page }}</button>
            @endif
          @endfor

          @if ($totalPages > 1)
            <button class="numbers-pagination__link" type="button" data-action="next">ถัดไป</button>
          @else
            <span class="numbers-pagination__link is-disabled">ถัดไป</span>
          @endif
        </nav>
      @else
        <p class="numbers-empty">ยังไม่มีเบอร์พร้อมขายในระบบตอนนี้</p>
      @endif
    </div>
  </section>

  @if ($numbersPayload->isNotEmpty())
    <script>
      (() => {
        const numbers = @json($numbersPayload);
        const pageSize = {{ $pageSize }};
        const totalPages = Math.max(1, Math.ceil(numbers.length / pageSize));

        const grid = document.getElementById("home-card-grid");
        const pager = document.getElementById("home-pagination");
        const toggle = document.getElementById("home-view-toggle");
        const storageKey = "home-numbers-view";

        if (!grid || !toggle) return;

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
          <article class="number-card number-card--home">
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

        const renderPager = () => {
          if (!pager) return;

          const startPage = Math.max(1, currentPage - 2);
          const endPage = Math.min(totalPages, currentPage + 2);
          const controls = [];

          if (currentPage <= 1) {
            controls.push('<span class="numbers-pagination__link is-disabled">ก่อนหน้า</span>');
          } else {
            controls.push('<button class="numbers-pagination__link" type="button" data-action="prev">ก่อนหน้า</button>');
          }

          for (let page = startPage; page <= endPage; page += 1) {
            if (page === currentPage) {
              controls.push(`<span class="numbers-pagination__link is-active" aria-current="page">${page}</span>`);
            } else {
              controls.push(`<button class="numbers-pagination__link" type="button" data-page="${page}">${page}</button>`);
            }
          }

          if (currentPage >= totalPages) {
            controls.push('<span class="numbers-pagination__link is-disabled">ถัดไป</span>');
          } else {
            controls.push('<button class="numbers-pagination__link" type="button" data-action="next">ถัดไป</button>');
          }

          pager.innerHTML = controls.join("");
        };

        const renderPage = (page) => {
          currentPage = Math.min(totalPages, Math.max(1, page));
          const start = (currentPage - 1) * pageSize;
          const pageItems = numbers.slice(start, start + pageSize);
          grid.innerHTML = pageItems.map(renderCard).join("");
          renderPager();
        };

        const buttons = Array.from(toggle.querySelectorAll("[data-view]"));

        const applyView = (view) => {
          const normalizedView = view === "list" ? "list" : "grid";
          grid.dataset.view = normalizedView;

          buttons.forEach((button) => {
            const isActive = button.dataset.view === normalizedView;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
          });
        };

        try {
          const savedView = localStorage.getItem(storageKey);
          applyView(savedView === "list" ? "list" : "grid");
        } catch (error) {
          applyView("grid");
        }

        toggle.addEventListener("click", (event) => {
          const target = event.target.closest("[data-view]");
          if (!target) return;

          applyView(target.dataset.view);

          try {
            localStorage.setItem(storageKey, target.dataset.view);
          } catch (error) {
            // Ignore storage errors and keep the in-memory state.
          }
        });

        if (pager) {
          pager.addEventListener("click", (event) => {
            const control = event.target.closest("[data-page], [data-action]");
            if (!control) return;

            const targetPage = Number.parseInt(control.dataset.page || "", 10);
            if (Number.isFinite(targetPage)) {
              renderPage(targetPage);
              return;
            }

            if (control.dataset.action === "prev" && currentPage > 1) {
              renderPage(currentPage - 1);
              return;
            }

            if (control.dataset.action === "next" && currentPage < totalPages) {
              renderPage(currentPage + 1);
            }
          });
        }

        renderPage(1);
      })();
    </script>
  @endif

  <script>
    (() => {
      const phoneInput = document.getElementById("phone");

      if (!phoneInput) return;

      const syncPhoneValue = () => {
        const digits = (phoneInput.value || "").replace(/\D+/g, "").slice(0, 10);
        phoneInput.value = digits;
        phoneInput.setCustomValidity(
          digits.length === 10 ? "" : "กรุณากรอกเบอร์มือถือให้ครบ 10 หลัก"
        );
      };

      phoneInput.addEventListener("input", syncPhoneValue);
      phoneInput.addEventListener("blur", syncPhoneValue);
      phoneInput.addEventListener("invalid", syncPhoneValue);

      syncPhoneValue();
    })();
  </script>
@endsection
