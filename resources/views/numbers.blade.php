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
  @php
    $selectedView = request('view') === 'list' ? 'list' : 'grid';
  @endphp
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
          <input id="numbers-view-input" type="hidden" name="view" value="{{ $selectedView }}">
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
              <label class="numbers-filter-label" for="numbers-service-type">ประเภทเบอร์</label>
              <div class="numbers-filter-actions__controls">
                <select id="numbers-service-type" class="numbers-filter-select" name="service_type">
                  <option value="">ทั้งหมด</option>
                  <option value="{{ \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID }}" @selected($selectedServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID)>รายเดือน</option>
                  <option value="{{ \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID }}" @selected($selectedServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID)>เติมเงิน</option>
                </select>
                <select id="numbers-plan" class="numbers-filter-select" name="plan">
                  <option value="">ราคา / โปรโมชั่น</option>
                  @foreach ($plans as $plan)
                    <option value="{{ $plan['value'] }}" @selected($selectedPlan === $plan['value'])>{{ $plan['label'] }}</option>
                  @endforeach
                </select>
                <button class="numbers-filter-submit" type="submit">ค้นหา</button>
              </div>
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
          <button class="numbers-view-toggle__button {{ $selectedView === 'list' ? 'is-active' : '' }}" type="button" data-view="list" aria-pressed="{{ $selectedView === 'list' ? 'true' : 'false' }}">รายการ</button>
          <button class="numbers-view-toggle__button {{ $selectedView === 'grid' ? 'is-active' : '' }}" type="button" data-view="grid" aria-pressed="{{ $selectedView === 'grid' ? 'true' : 'false' }}">ตาราง</button>
        </div>
      </div>

      @if ($isDefaultSplitLayout)
        <div class="numbers-default-columns" id="numbers-catalog-grid" data-view="{{ $selectedView }}">
          <section class="numbers-default-column numbers-default-column--prepaid">
            <div class="numbers-default-column__head">
              <h3>เบอร์เติมเงิน</h3>
              <p>แสดงก่อนเมื่อยังไม่ได้ค้นหา</p>
            </div>
            <div class="numbers-catalog-grid listing-card-grid is-default-split" data-view="{{ $selectedView }}">
              @foreach ($defaultPrepaidNumbers as $number)
                <article class="number-card number-card--listing number-card--catalog">
                  <div class="card-top">{{ $number->display_number ?: $number->phone_number }}</div>
                  <div class="card-body">
                    <div class="card-meta-stack">
                      <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">{{ $number->service_type_label }}</span></span>
                      <span class="card-meta-plan">{{ $number->payment_label }}</span>
                    </div>
                  </div>
                  <a class="card-btn card-btn--buy" href="{{ route('evaluate', ['phone' => $number->phone_number]) }}">สั่งซื้อ</a>
                </article>
              @endforeach
            </div>
          </section>

          <section class="numbers-default-column numbers-default-column--postpaid">
            <div class="numbers-default-column__head">
              <h3>เบอร์รายเดือน</h3>
              <p>แสดงถัดลงมาเมื่อยังไม่ได้ค้นหา</p>
            </div>
            <div class="numbers-catalog-grid listing-card-grid is-default-split" data-view="{{ $selectedView }}">
              @foreach ($defaultPostpaidNumbers as $number)
                <article class="number-card number-card--listing number-card--catalog">
                  <div class="card-top">{{ $number->display_number ?: $number->phone_number }}</div>
                  <div class="card-body">
                    <div class="card-meta-stack">
                      <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">{{ $number->service_type_label }}</span></span>
                      <span class="card-meta-plan">{{ $number->payment_label }}</span>
                    </div>
                  </div>
                  <a class="card-btn card-btn--buy" href="{{ route('evaluate', ['phone' => $number->phone_number]) }}">สั่งซื้อ</a>
                </article>
              @endforeach
            </div>
          </section>
        </div>
      @else
        <div class="numbers-catalog-grid listing-card-grid" id="numbers-catalog-grid" data-view="{{ $selectedView }}">
          @forelse ($numbers as $number)
            <article class="number-card number-card--listing number-card--catalog">
              <div class="card-top">{{ $number->display_number ?: $number->phone_number }}</div>
              <div class="card-body">
                <div class="card-meta-stack">
                  <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">{{ $number->service_type_label }}</span></span>
                  <span class="card-meta-plan">{{ $number->payment_label }}</span>
                </div>
              </div>
              <a class="card-btn card-btn--buy" href="{{ route('evaluate', ['phone' => $number->phone_number]) }}">สั่งซื้อ</a>
            </article>
          @empty
            <p class="numbers-empty">ไม่พบบัญชีเบอร์ตามเงื่อนไขที่ค้นหา</p>
          @endforelse
        </div>
      @endif

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
      const serviceTypeSelect = document.getElementById("numbers-service-type");
      const planSelect = document.getElementById("numbers-plan");
      const planOptionsByServiceType = @json($planOptionsByServiceType);
      const serviceTypePostpaid = @json(\App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID);
      const serviceTypePrepaid = @json(\App\Models\PhoneNumber::SERVICE_TYPE_PREPAID);
      const placeholderLabel = "ราคา / โปรโมชั่น";

      if (!serviceTypeSelect || !planSelect) return;

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

      const resolveOptionKey = (value) => {
        if (value === serviceTypePostpaid || value === serviceTypePrepaid) {
          return value;
        }

        return "all";
      };

      const renderPlanOptions = (serviceType) => {
        const optionKey = resolveOptionKey(serviceType);
        const options = planOptionsByServiceType[optionKey] ?? [];
        const currentValue = planSelect.value;
        const renderedOptions = options
          .map((option) => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`)
          .join("");

        planSelect.innerHTML = `<option value="">${placeholderLabel}</option>${renderedOptions}`;

        const hasCurrentValue = options.some((option) => option.value === currentValue);
        planSelect.value = hasCurrentValue ? currentValue : "";
      };

      renderPlanOptions(serviceTypeSelect.value);

      serviceTypeSelect.addEventListener("change", () => {
        renderPlanOptions(serviceTypeSelect.value);
      });
    })();

    (() => {
      const catalogRoot = document.getElementById("numbers-catalog-grid");
      const toggle = document.getElementById("numbers-view-toggle");
      const viewInput = document.getElementById("numbers-view-input");

      if (!catalogRoot || !toggle) return;

      const buttons = Array.from(toggle.querySelectorAll("[data-view]"));
      const catalogGrids = catalogRoot.classList.contains("numbers-catalog-grid")
        ? [catalogRoot]
        : Array.from(catalogRoot.querySelectorAll(".numbers-catalog-grid"));
      const paginationLinks = Array.from(document.querySelectorAll(".numbers-pagination a.numbers-pagination__link"));

      const updateUrlState = (view) => {
        const url = new URL(window.location.href);
        url.searchParams.set("view", view);
        window.history.replaceState({}, "", url);
      };

      const updatePaginationLinks = (view) => {
        paginationLinks.forEach((link) => {
          const url = new URL(link.href, window.location.origin);
          url.searchParams.set("view", view);
          link.href = url.toString();
        });
      };

      const applyView = (view) => {
        const normalizedView = view === "list" ? "list" : "grid";
        catalogRoot.dataset.view = normalizedView;
        catalogGrids.forEach((grid) => {
          grid.dataset.view = normalizedView;
        });
        if (viewInput) {
          viewInput.value = normalizedView;
        }
        updatePaginationLinks(normalizedView);
        updateUrlState(normalizedView);

        buttons.forEach((button) => {
          const isActive = button.dataset.view === normalizedView;
          button.classList.toggle("is-active", isActive);
          button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
      };

      applyView(@json($selectedView));

      toggle.addEventListener("click", (event) => {
        const target = event.target.closest("[data-view]");
        if (!target) return;

        applyView(target.dataset.view);
      });
    })();
  </script>
@endsection

@push('scripts')
  @if ($search !== '' || $selectedPlan !== '' || $selectedServiceType !== '' || $positionPattern !== null)
    <script>
      (() => {
        if (!window.SupernumberAnalytics) return;

        window.SupernumberAnalytics.track("search", {
          search_context: "numbers_catalog",
          has_sequence_query: @json($search !== ''),
          has_position_pattern: @json($positionPattern !== null),
          has_plan_filter: @json($selectedPlan !== ''),
          service_type: @json($selectedServiceType !== '' ? $selectedServiceType : 'all'),
          results_count: {{ (int) $numbers->total() }},
        });
      })();
    </script>
  @endif
@endpush
