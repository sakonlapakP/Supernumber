@extends('layouts.app')

@section('title', 'Supernumber | เบอร์ทั้งหมด')
@section('meta_description', 'รวมเบอร์พร้อมขายทั้งหมด ค้นหาเบอร์ตามตำแหน่งที่ต้องการและเลือกโปรโมชั่นที่เหมาะกับคุณ')
@section('og_title', 'Supernumber | เบอร์ทั้งหมด')
@section('og_description', 'รวมเบอร์พร้อมขายทั้งหมด ค้นหาเบอร์ตามตำแหน่งที่ต้องการและเลือกโปรโมชั่นที่เหมาะกับคุณ')
@section('canonical', url('/numbers'))
@section('og_url', url('/numbers'))
@section('og_image', asset('images/home_banner.jpg'))
@section('preload_image', asset('images/home_banner.jpg'))
@section('body_class', 'numbers-scale-soft')

@section('content')
  <section class="numbers-hero" aria-labelledby="numbers-hero-title">
    <div class="numbers-hero-overlay"></div>
    <div class="container numbers-hero__content">
      <div class="numbers-hero__text">
        <p class="hero-kicker">ค้นหาเบอร์มงคล</p>
        <h1 id="numbers-hero-title">เบอร์ทั้งหมดที่พร้อมขาย</h1>
        <p>เลือกเบอร์ที่ตรงใจจากคลังเบอร์คุณภาพ พร้อมตัวกรองที่ช่วยค้นหาได้ไวขึ้น</p>
      </div>
    </div>
  </section>

  <section class="numbers-catalog-page" aria-labelledby="numbers-catalog-title">
    <div class="container numbers-catalog-shell">
      <div class="numbers-catalog-toolbar">
        <form class="numbers-filter-form" action="{{ route('numbers.index') }}" method="get">
          <div class="numbers-filter-panel">
            <div class="numbers-filter-modes">
              <div class="numbers-filter-card numbers-filter-card--sequence">
                <label class="numbers-filter-label" for="numbers-search-sequence">ค้นหาจากชุดตัวเลข</label>
                <input
                  id="numbers-search-sequence"
                  class="numbers-filter-input"
                  type="text"
                  name="q"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  value="{{ $search }}"
                  placeholder="เช่น 629"
                />
              </div>

              <div class="numbers-filter-divider" aria-hidden="true">หรือ</div>

              <div class="numbers-filter-card">
                <label class="numbers-filter-label" for="numbers-prefix">ค้นหาตามตำแหน่ง</label>
                <div class="numbers-position-row" aria-label="ค้นหาตามตำแหน่งตัวเลข">
                  <input
                    id="numbers-prefix"
                    class="numbers-position-prefix"
                    type="text"
                    name="prefix"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="3"
                    value="{{ request('prefix') }}"
                    placeholder="0XX"
                  />
                  <span class="numbers-position-separator">-</span>
                  @foreach (range(4, 6) as $position)
                    <input
                      class="numbers-position-digit"
                      type="text"
                      name="p{{ $position }}"
                      inputmode="numeric"
                      pattern="[0-9]*"
                      maxlength="1"
                      value="{{ request("p{$position}") }}"
                      aria-label="ตำแหน่ง {{ $position }}"
                    />
                  @endforeach
                  <span class="numbers-position-separator">-</span>
                  @foreach (range(7, 10) as $position)
                    <input
                      class="numbers-position-digit"
                      type="text"
                      name="p{{ $position }}"
                      inputmode="numeric"
                      pattern="[0-9]*"
                      maxlength="1"
                      value="{{ request("p{$position}") }}"
                      aria-label="ตำแหน่ง {{ $position }}"
                    />
                  @endforeach
                </div>
              </div>
            </div>

            <div class="numbers-filter-actions">
              <label class="numbers-filter-label" for="numbers-plan">โปรโมชั่น</label>
              <div class="numbers-filter-actions__controls">
                <select id="numbers-plan" class="numbers-filter-select" name="plan">
                  <option value="">เลือกโปรโมชั่น</option>
                  @foreach ($plans as $plan)
                    <option value="{{ $plan }}" @selected($selectedPlan === $plan)>{{ $plan }}</option>
                  @endforeach
                </select>
                <button class="numbers-filter-submit" type="submit">ค้นหา</button>
              </div>
              <p class="numbers-filter-actions__note">ใช้โปรโมชั่นเดียวกันได้ทั้งการค้นหาตามตำแหน่งและค้นหาจากชุดตัวเลข</p>
            </div>
          </div>
        </form>
      </div>

      @if ($positionPattern)
        <p class="numbers-filter-hint">รูปแบบที่ค้นหา: {{ $positionPattern }}</p>
      @endif

      <div class="section-title numbers-catalog-title">
        <div class="numbers-catalog-title__content">
          <h2 id="numbers-catalog-title">เบอร์ทั้งหมด</h2>
          <p>
            แสดง
            {{ $numbers->count() ? $numbers->firstItem() . '-' . $numbers->lastItem() : '0' }}
            จาก {{ number_format($numbers->total()) }} เบอร์
          </p>
        </div>
        <div class="numbers-view-toggle" id="numbers-view-toggle" role="group" aria-label="เลือกรูปแบบการแสดงผล">
          <button class="numbers-view-toggle__button" type="button" data-view="list" aria-pressed="false">List</button>
          <button class="numbers-view-toggle__button" type="button" data-view="grid" aria-pressed="false">Grid</button>
        </div>
      </div>

      <div class="numbers-catalog-grid" id="numbers-catalog-grid">
        @forelse ($numbers as $number)
          <article class="number-card number-card--catalog">
            <div class="card-top">{{ $number->display_number ?: $number->phone_number }}</div>
            <div class="card-body">
              <div class="card-meta-stack">
                <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">รายเดือน</span></span>
                <span class="card-meta-plan">{{ $number->package_label }}</span>
              </div>
            </div>
            <a class="card-btn card-btn--buy" href="{{ route('evaluate', ['phone' => $number->phone_number]) }}">สั่งซื้อ</a>
          </article>
        @empty
          <p class="numbers-empty">ไม่พบบัญชีเบอร์ตามเงื่อนไขที่ค้นหา</p>
        @endforelse
      </div>

      @if ($numbers->hasPages())
        @php
          $startPage = max(1, $numbers->currentPage() - 2);
          $endPage = min($numbers->lastPage(), $numbers->currentPage() + 2);
        @endphp
        <nav class="numbers-pagination" aria-label="เปลี่ยนหน้ารายการเบอร์">
          @if ($numbers->onFirstPage())
            <span class="numbers-pagination__link is-disabled">ก่อนหน้า</span>
          @else
            <a class="numbers-pagination__link" href="{{ $numbers->previousPageUrl() }}">ก่อนหน้า</a>
          @endif

          @for ($page = $startPage; $page <= $endPage; $page++)
            @if ($page === $numbers->currentPage())
              <span class="numbers-pagination__link is-active">{{ $page }}</span>
            @else
              <a class="numbers-pagination__link" href="{{ $numbers->url($page) }}">{{ $page }}</a>
            @endif
          @endfor

          @if ($numbers->hasMorePages())
            <a class="numbers-pagination__link" href="{{ $numbers->nextPageUrl() }}">ถัดไป</a>
          @else
            <span class="numbers-pagination__link is-disabled">ถัดไป</span>
          @endif
        </nav>
      @endif
    </div>
  </section>

  <script>
    (() => {
      const prefixField = document.querySelector(".numbers-position-prefix");
      const digitFields = Array.from(document.querySelectorAll(".numbers-position-digit"));

      if (!prefixField || digitFields.length === 0) return;

      const fields = [prefixField, ...digitFields];

      const fieldLimit = (field) => Number.parseInt(field.getAttribute("maxlength") || "1", 10);

      const focusField = (index) => {
        if (index < 0 || index >= fields.length) return;
        fields[index].focus();
        fields[index].select();
      };

      const fillFromIndex = (startIndex, digits) => {
        let offset = 0;

        for (let index = startIndex; index < fields.length; index += 1) {
          const field = fields[index];
          const limit = fieldLimit(field);
          field.value = digits.slice(offset, offset + limit);
          offset += limit;

          if (offset >= digits.length) {
            if (field.value.length >= limit) {
              focusField(index + 1);
            }
            return;
          }
        }

        fields.at(-1)?.focus();
      };

      fields.forEach((field, index) => {
        field.addEventListener("input", () => {
          const digits = field.value.replace(/\D/g, "");
          const limit = fieldLimit(field);

          if (digits.length <= limit) {
            field.value = digits.slice(0, limit);

            if (field.value.length === limit) {
              focusField(index + 1);
            }
            return;
          }

          fillFromIndex(index, digits);
        });

        field.addEventListener("keydown", (event) => {
          const atStart = field.selectionStart === 0 && field.selectionEnd === 0;
          const atEnd = field.selectionStart === field.value.length && field.selectionEnd === field.value.length;

          if (event.key === "Backspace" && field.value === "" && atStart && index > 0) {
            event.preventDefault();
            const previousField = fields[index - 1];
            previousField.value = "";
            previousField.focus();
          }

          if (event.key === "ArrowLeft" && atStart && index > 0) {
            event.preventDefault();
            focusField(index - 1);
          }

          if (event.key === "ArrowRight" && atEnd && index < fields.length - 1) {
            event.preventDefault();
            focusField(index + 1);
          }
        });

        field.addEventListener("paste", (event) => {
          event.preventDefault();
          const pasted = (event.clipboardData || window.clipboardData)
            .getData("text")
            .replace(/\D/g, "");

          if (pasted === "") return;

          fillFromIndex(index, pasted);
        });
      });
    })();

    (() => {
      const storageKey = "numbers-catalog-view";
      const catalogGrid = document.getElementById("numbers-catalog-grid");
      const toggle = document.getElementById("numbers-view-toggle");

      if (!catalogGrid || !toggle) return;

      const buttons = Array.from(toggle.querySelectorAll("[data-view]"));
      const mediaQuery = window.matchMedia("(max-width: 680px)");
      let hasManualPreference = false;

      const defaultView = () => (mediaQuery.matches ? "list" : "grid");

      const applyView = (view) => {
        const normalizedView = view === "list" ? "list" : "grid";
        catalogGrid.dataset.view = normalizedView;

        buttons.forEach((button) => {
          const isActive = button.dataset.view === normalizedView;
          button.classList.toggle("is-active", isActive);
          button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
      };

      try {
        const savedView = localStorage.getItem(storageKey);
        if (savedView === "list" || savedView === "grid") {
          hasManualPreference = true;
          applyView(savedView);
        } else {
          applyView(defaultView());
        }
      } catch (error) {
        applyView(defaultView());
      }

      toggle.addEventListener("click", (event) => {
        const target = event.target.closest("[data-view]");
        if (!target) return;

        hasManualPreference = true;
        applyView(target.dataset.view);

        try {
          localStorage.setItem(storageKey, target.dataset.view);
        } catch (error) {
          // Ignore storage errors and keep the in-memory state.
        }
      });

      const handleViewportChange = () => {
        if (!hasManualPreference) {
          applyView(defaultView());
        }
      };

      if (typeof mediaQuery.addEventListener === "function") {
        mediaQuery.addEventListener("change", handleViewportChange);
      } else if (typeof mediaQuery.addListener === "function") {
        mediaQuery.addListener(handleViewportChange);
      }
    })();
  </script>
@endsection
