@extends('layouts.app')

@php
  $homeBannerVersion = @filemtime(public_path('images/home_banner.jpg')) ?: time();
  $homeBannerUrl = asset('images/home_banner.jpg') . '?v=' . $homeBannerVersion;
@endphp

@section('title', 'Supernumber | เบอร์มงคลที่ใช่สำหรับคุณ')
@section('meta_description', 'ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง ดึงดูดโอกาส และปลดล็อกเส้นทางสำเร็จด้วยเบอร์มงคลที่ใช่สำหรับคุณ')
@section('og_title', 'Supernumber | เบอร์มงคลที่ใช่สำหรับคุณ')
@section('og_description', 'ดูดวงเบอร์มือถือฟรี วิเคราะห์เสริมพลัง ดึงดูดโอกาส และปลดล็อกเส้นทางสำเร็จด้วยเบอร์มงคลที่ใช่สำหรับคุณ')
@section('canonical', url('/'))
@section('og_url', url('/'))
@section('og_image', $homeBannerUrl)
@section('preload_image', $homeBannerUrl)
@section('body_class', 'home-scale-soft')

@section('content')
  <style>
    @media (min-width: 1200px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        grid-template-columns: repeat(4, 240px) !important;
        justify-content: center !important;
        gap: 12px !important;
      }
    }

    @media (min-width: 986px) and (max-width: 1199px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        grid-template-columns: repeat(3, 240px) !important;
        justify-content: center !important;
        gap: 12px !important;
      }
    }

    @media (min-width: 681px) and (max-width: 985px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        grid-template-columns: repeat(2, 240px) !important;
        justify-content: center !important;
        gap: 12px !important;
      }
    }

    @media (max-width: 680px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 10px !important;
      }
    }

    @media (max-width: 420px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        gap: 8px !important;
      }
    }
  </style>

  <section class="hero" aria-labelledby="hero-title">
    <div class="hero-media" aria-hidden="true">
      <img
        class="hero-media__image"
        src="{{ $homeBannerUrl }}"
        alt=""
        fetchpriority="high"
        decoding="async"
      />
    </div>
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

          @error('phone')
            <p class="hero-error" id="phone-error">{{ $message }}</p>
          @enderror
        </form>
      </div>
    </div>
  </section>

  <section class="home-search" aria-labelledby="home-search-title">
    <div class="container container--narrow">
      <div class="home-filter">
        <div class="home-filter__header">
          <h2 id="home-search-title">ค้นหาเบอร์มงคล</h2>
          <p>ระบุตำแหน่งหรือชุดตัวเลขที่คุณต้องการ เพื่อความสำเร็จที่มากกว่า</p>
        </div>

        <form class="home-filter__form" action="{{ route('numbers.index') }}" method="get">
          <div class="home-filter__main">
            <!-- Sequence Search -->
            <div class="home-filter__group">
              <label class="home-filter__label" for="home-search-sequence">ค้นหาจากชุดตัวเลข</label>
              <div class="home-filter__input-wrapper">
                <i class="icon-search-small"></i>
                <input
                  id="home-search-sequence"
                  class="home-filter__input"
                  type="text"
                  name="q"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  value="{{ $search }}"
                  placeholder="เช่น 629"
                />
              </div>
            </div>

            <div class="home-filter__orb" aria-hidden="true"><span>หรือ</span></div>

            <!-- Position Search -->
            <div class="home-filter__group">
              <label class="home-filter__label">ค้นหาตามตำแหน่ง</label>
              <div class="home-filter__position-row">
                <input
                  class="home-filter__pos-prefix"
                  type="text"
                  name="prefix"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  maxlength="3"
                  value="{{ request('prefix') }}"
                  placeholder="0XX"
                />
                <span class="home-filter__pos-sep"></span>
                <div class="home-filter__pos-digits">
                  @foreach (range(4, 6) as $position)
                    <input
                      class="home-filter__pos-input"
                      type="text"
                      name="p{{ $position }}"
                      inputmode="numeric"
                      pattern="[0-9]*"
                      maxlength="1"
                      value="{{ request("p{$position}") }}"
                      aria-label="ตำแหน่ง {{ $position }}"
                    />
                  @endforeach
                  <span class="home-filter__pos-sep"></span>
                  @foreach (range(7, 10) as $position)
                    <input
                      class="home-filter__pos-input"
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
          </div>

          <div class="home-filter__footer">
            <div class="home-filter__footer-controls">
              <div class="home-filter__select-wrapper">
                <label>ประเภทเบอร์</label>
                <select id="home-service-type" name="service_type">
                  <option value="">ทั้งหมด</option>
                  <option value="{{ \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID }}" @selected($selectedServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID)>รายเดือน</option>
                  <option value="{{ \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID }}" @selected($selectedServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID)>เติมเงิน</option>
                </select>
              </div>
              <div class="home-filter__select-wrapper">
                <label>โปรโมชั่น / ราคาเบอร์</label>
                <select id="home-plan" name="plan">
                  <option value="">ทั้งหมด</option>
                  @foreach ($plans as $plan)
                    <option value="{{ $plan['value'] }}" @selected($selectedPlan === $plan['value'])>{{ $plan['label'] }}</option>
                  @endforeach
                </select>
              </div>
              <button class="home-filter__submit" type="submit">ค้นหาเบอร์</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </section>

  <section class="numbers" aria-labelledby="numbers-title">
    @php
      $pageSize = 8;
      $maxPages = 6;
      $maxItems = $pageSize * $maxPages;
      $buildHomePayload = function ($numbers) use ($maxItems) {
          return $numbers->take($maxItems)->map(function ($number) {
              return [
                  'phone_number' => $number->phone_number,
                  'display_number' => $number->display_number ?: $number->phone_number,
                  'service_type_label' => $number->service_type_label,
                  'payment_label' => $number->payment_label,
                  'initial_payment_label' => $number->initial_payment_label,
                  'initial_payment_html' => $number->initial_payment_html,
                  'is_postpaid' => $number->is_postpaid,
                  'supported_topic_icons' => $number->supported_topic_icons,
                  'good_number_url' => route('evaluate', ['phone' => $number->phone_number]),
              ];
          })->values();
      };
      $prepaidPayload = $buildHomePayload($prepaidNumbers);
      $postpaidPayload = $buildHomePayload($postpaidNumbers);
      $initialPrepaidNumbers = $prepaidPayload->take($pageSize);
      $initialPostpaidNumbers = $postpaidPayload->take($pageSize);
      $hasHomeNumbers = $prepaidPayload->isNotEmpty() || $postpaidPayload->isNotEmpty();
      $totalPages = max(
          1,
          (int) max(
              ceil($prepaidPayload->count() / $pageSize),
              ceil($postpaidPayload->count() / $pageSize)
          )
      );
      $startPage = 1;
      $endPage = min($totalPages, 3);
    @endphp
    <div class="container">
      <div class="section-title numbers-catalog-title">
        <div class="numbers-catalog-title__content">
          <!-- <h2 id="numbers-title">เบอร์มงคลชีวิต</h2>
          <p>เบอร์มงคลที่คัดสรรมาเพื่อคุณ</p> -->
        </div>
        @if ($hasHomeNumbers)
          <div class="numbers-view-toggle" id="home-view-toggle" role="group" aria-label="เลือกรูปแบบการแสดงผลหน้าแรก">
            <button class="numbers-view-toggle__button" type="button" data-view="list" aria-pressed="false">รายการ</button>
            <button class="numbers-view-toggle__button is-active" type="button" data-view="grid" aria-pressed="true">ตาราง</button>
          </div>
        @endif
      </div>

      @if ($hasHomeNumbers)
        <div class="home-number-groups" id="home-number-groups" data-view="grid">
          @if ($prepaidPayload->isNotEmpty())
            <section class="home-number-group home-number-group--prepaid" id="home-prepaid-section">
              <div class="home-number-group__head">
                <div class="home-number-group__copy">
                  <h3 class="home-number-group__title">เบอร์เติมเงินพร้อมใช้</h3>
                  <p class="home-number-group__hint">เบอร์เติมเงินสามารถย้ายค่ายได้</p>
                </div>
              </div>
              <div class="card-grid home-card-grid listing-card-grid" id="home-prepaid-grid" data-view="grid">
                @foreach ($initialPrepaidNumbers as $number)
                  <article class="number-card number-card--listing number-card--home">
                    <div class="card-top">{{ $number['display_number'] }}</div>
                    @if (! empty($number['supported_topic_icons']))
                      @php
                        $topicIcons = collect($number['supported_topic_icons']);
                        $visibleTopicIcons = $topicIcons->take(4);
                        $hasMoreTopicIcons = $topicIcons->count() > 4;
                      @endphp
                      <div class="card-topic-icons" aria-label="หมวดที่เบอร์นี้ช่วย">
                        @foreach ($visibleTopicIcons as $topic)
                          <span class="card-topic-icon" title="{{ $topic['topic'] }}" aria-label="{{ $topic['topic'] }}">{{ $topic['icon'] }}</span>
                        @endforeach
                        @if ($hasMoreTopicIcons)
                          <span class="card-topic-icon card-topic-icon--more" aria-label="มีหมวดที่ช่วยเพิ่มเติม">+</span>
                        @endif
                      </div>
                    @endif
                    <div class="card-body">
                      <div class="card-meta-stack">
                        <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">{{ $number['service_type_label'] }}</span></span>
                        @if (! $number['is_postpaid'])
                          <span class="card-meta-plan">{{ $number['payment_label'] }}</span>
                        @endif
                        @if ($number['is_postpaid'])
                          <span class="card-meta-price">{!! $number['initial_payment_html'] !!}</span>
                        @endif
                      </div>
                    </div>
                    <a class="card-btn card-btn--buy" href="{{ $number['good_number_url'] }}">สั่งซื้อ</a>
                  </article>
                @endforeach
              </div>
            </section>
          @endif

          @if ($postpaidPayload->isNotEmpty())
            <section class="home-number-group home-number-group--postpaid" id="home-postpaid-section">
              <div class="home-number-group__head">
                <div class="home-number-group__copy">
                  <h3 class="home-number-group__title">เบอร์รายเดือนแนะนำ</h3>
                  <p class="home-number-group__hint">รวมเบอร์รายเดือนที่พร้อมเลือกแพ็กเกจ</p>
                </div>
              </div>
              <div class="card-grid home-card-grid listing-card-grid" id="home-postpaid-grid" data-view="grid">
                @foreach ($initialPostpaidNumbers as $number)
                  <article class="number-card number-card--listing number-card--home">
                    <div class="card-top">{{ $number['display_number'] }}</div>
                    @if (! empty($number['supported_topic_icons']))
                      @php
                        $topicIcons = collect($number['supported_topic_icons']);
                        $visibleTopicIcons = $topicIcons->take(4);
                        $hasMoreTopicIcons = $topicIcons->count() > 4;
                      @endphp
                      <div class="card-topic-icons" aria-label="หมวดที่เบอร์นี้ช่วย">
                        @foreach ($visibleTopicIcons as $topic)
                          <span class="card-topic-icon" title="{{ $topic['topic'] }}" aria-label="{{ $topic['topic'] }}">{{ $topic['icon'] }}</span>
                        @endforeach
                        @if ($hasMoreTopicIcons)
                          <span class="card-topic-icon card-topic-icon--more" aria-label="มีหมวดที่ช่วยเพิ่มเติม">+</span>
                        @endif
                      </div>
                    @endif
                    <div class="card-body">
                      <div class="card-meta-stack">
                        <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">{{ $number['service_type_label'] }}</span></span>
                        @if (! $number['is_postpaid'])
                          <span class="card-meta-plan">{{ $number['payment_label'] }}</span>
                        @endif
                        @if ($number['is_postpaid'])
                          <span class="card-meta-price">{!! $number['initial_payment_html'] !!}</span>
                        @endif
                      </div>
                    </div>
                    <a class="card-btn card-btn--buy" href="{{ $number['good_number_url'] }}">สั่งซื้อ</a>
                  </article>
                @endforeach
              </div>
            </section>
          @endif
        </div>

        <nav class="numbers-pagination home-pagination" id="home-pagination" aria-label="เปลี่ยนหน้ารายการเบอร์" @if ($totalPages <= 1) hidden @endif>
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

  @if ($hasHomeNumbers)
    <script>
      (() => {
        const prepaidNumbers = @json($prepaidPayload);
        const postpaidNumbers = @json($postpaidPayload);
        const pageSize = {{ $pageSize }};
        const totalPages = Math.max(
          1,
          Math.max(
            Math.ceil(prepaidNumbers.length / pageSize),
            Math.ceil(postpaidNumbers.length / pageSize)
          )
        );

        const prepaidGrid = document.getElementById("home-prepaid-grid");
        const prepaidSection = document.getElementById("home-prepaid-section");
        const postpaidGrid = document.getElementById("home-postpaid-grid");
        const postpaidSection = document.getElementById("home-postpaid-section");
        const groups = document.getElementById("home-number-groups");
        const pager = document.getElementById("home-pagination");
        const toggle = document.getElementById("home-view-toggle");

        if ((!prepaidGrid && !postpaidGrid) || !toggle) return;

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
          <article class="number-card number-card--listing number-card--home">
            <div class="card-top">${escapeHtml(number.display_number)}</div>
            ${Array.isArray(number.supported_topic_icons) && number.supported_topic_icons.length
              ? `<div class="card-topic-icons" aria-label="หมวดที่เบอร์นี้ช่วย">${number.supported_topic_icons.slice(0, 4).map((topic) => `<span class="card-topic-icon" title="${escapeHtml(topic.topic)}" aria-label="${escapeHtml(topic.topic)}">${escapeHtml(topic.icon)}</span>`).join("")}${number.supported_topic_icons.length > 4 ? `<span class="card-topic-icon card-topic-icon--more" aria-label="มีหมวดที่ช่วยเพิ่มเติม">+</span>` : ""}</div>`
              : ""}
            <div class="card-body">
              <div class="card-meta-stack">
                <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">${escapeHtml(number.service_type_label)}</span></span>
                ${!number.is_postpaid ? `<span class="card-meta-plan">${escapeHtml(number.payment_label)}</span>` : ""}
                ${number.is_postpaid ? `<span class="card-meta-price">${number.initial_payment_html}</span>` : ""}
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
          const prepaidItems = prepaidNumbers.slice(start, start + pageSize);
          const postpaidItems = postpaidNumbers.slice(start, start + pageSize);

          if (prepaidGrid) {
            prepaidGrid.innerHTML = prepaidItems.map(renderCard).join("");
          }

          if (postpaidGrid) {
            postpaidGrid.innerHTML = postpaidItems.map(renderCard).join("");
          }

          if (prepaidSection) {
            prepaidSection.hidden = prepaidItems.length === 0;
          }

          if (postpaidSection) {
            postpaidSection.hidden = postpaidItems.length === 0;
          }

          renderPager();
        };

        const buttons = Array.from(toggle.querySelectorAll("[data-view]"));
        const grids = [prepaidGrid, postpaidGrid].filter(Boolean);

        const applyView = (view) => {
          const normalizedView = view === "list" ? "list" : "grid";
          if (groups) {
            groups.dataset.view = normalizedView;
          }

          grids.forEach((grid) => {
            grid.dataset.view = normalizedView;
          });

          buttons.forEach((button) => {
            const isActive = button.dataset.view === normalizedView;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
          });
        };

        applyView("grid");

        toggle.addEventListener("click", (event) => {
          const target = event.target.closest("[data-view]");
          if (!target) return;

          applyView(target.dataset.view);
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

  <script>
    (() => {
      const serviceTypeSelect = document.getElementById("home-service-type");
      const planSelect = document.getElementById("home-plan");
      const planOptionsByServiceType = @json($plansByServiceType);
      const serviceTypePostpaid = @json(\App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID);
      const serviceTypePrepaid = @json(\App\Models\PhoneNumber::SERVICE_TYPE_PREPAID);
      const labels = {
        [serviceTypePostpaid]: "โปรรายเดือน",
        [serviceTypePrepaid]: "ราคาเบอร์",
        all: "โปรโมชั่น / ราคาเบอร์",
      };

      if (!serviceTypeSelect || !planSelect) return;

      const escapeHtml = (value) =>
        String(value ?? "").replace(/[&<>"']/g, (char) => {
          switch (char) {
            case "&": return "&amp;";
            case "<": return "&lt;";
            case ">": return "&gt;";
            case '"': return "&quot;";
            case "'": return "&#39;";
            default: return char;
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
        const placeholderLabel = labels[optionKey] || labels.all;

        const renderedOptions = options
          .map((option) => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`)
          .join("");

        planSelect.innerHTML = `<option value="">${placeholderLabel}</option>${renderedOptions}`;

        const hasCurrentValue = options.some((option) => option.value === currentValue);
        planSelect.value = hasCurrentValue ? currentValue : "";
        
        // Disable if "all" is selected
        planSelect.disabled = (optionKey === "all");
        planSelect.style.opacity = (optionKey === "all") ? "0.6" : "1";
        planSelect.style.cursor = (optionKey === "all") ? "not-allowed" : "pointer";

        // Update label above the select if needed
        const labelEl = planSelect.previousElementSibling;
        if (labelEl && labelEl.tagName === 'LABEL') {
            labelEl.textContent = placeholderLabel;
        }
      };

      renderPlanOptions(serviceTypeSelect.value);

      serviceTypeSelect.addEventListener("change", () => {
        renderPlanOptions(serviceTypeSelect.value);
      });
    })();
  </script>
@endsection
