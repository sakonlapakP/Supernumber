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

@section('seo_schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "ItemList",
  "itemListElement": [
    @if(isset($numbers))
    @foreach($numbers->take(10) as $index => $number)
    {
      "@@type": "ListItem",
      "position": {{ $index + 1 }},
      "url": "{{ url('/numbers') }}",
      "name": "เบอร์มงคล {{ $number->phone }}"
    }{{ !$loop->last ? ',' : '' }}
    @endforeach
    @endif
  ]
}
</script>
@endsection

@section('content')
  <style>
    /* Redesigned Search Section Styling */
    .home-filter {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 40px;
      padding: 40px;
      border: 1px solid rgba(255, 255, 255, 0.8);
      box-shadow: 0 10px 40px rgba(45, 33, 24, 0.1), 0 2px 4px rgba(216, 163, 74, 0.1);
      margin-bottom: 40px;
    }

    .home-filter__header {
      text-align: center;
      margin-bottom: 40px;
    }

    .home-filter__header h2 {
      font-size: 34px;
      color: #3b2f27;
      font-weight: 800;
      margin-bottom: 8px;
      letter-spacing: -0.02em;
    }

    .home-filter__header p {
      color: #7a6c62;
      font-size: 17px;
    }

    .home-filter__main {
      display: grid;
      grid-template-columns: 1fr auto 1.2fr;
      gap: 30px;
      align-items: flex-end;
      margin-bottom: 32px;
    }

    .home-filter__group {
      display: grid;
      gap: 12px;
    }

    .home-filter__label {
      font-size: 14px;
      font-weight: 700;
      color: #4a3e35;
      padding-left: 4px;
    }

    .home-filter__input-wrapper {
      position: relative;
    }

    .home-filter__input {
      width: 100%;
      height: 56px;
      background: #fff;
      border: 1.5px solid rgba(73, 61, 52, 0.1);
      border-radius: 16px;
      padding: 0 20px;
      font-size: 16px;
      color: #3b2f27;
      transition: all 0.3s ease;
    }

    .home-filter__input:focus {
      outline: none;
      border-color: #d8a34a;
      box-shadow: 0 0 0 4px rgba(216, 163, 74, 0.1);
    }

    .home-filter__orb {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #f9f6f1, #f0e8db);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 800;
      color: #8a7a6c;
      border: 1px solid rgba(216, 163, 74, 0.2);
      margin-bottom: 4px;
    }

    .home-filter__position-row {
      display: flex;
      align-items: center;
      gap: 10px;
      height: 56px;
    }

    .home-filter__pos-prefix {
      width: 70px;
      height: 56px;
      background: #fff;
      border: 1.5px solid rgba(73, 61, 52, 0.12);
      border-radius: 14px;
      text-align: center;
      font-size: 16px;
      font-weight: 600;
      color: #3b2f27;
    }

    .home-filter__pos-digits {
      display: flex;
      align-items: center;
      gap: 5px;
      flex: 1;
    }

    .home-filter__pos-input {
      width: 100%;
      height: 56px;
      background: #fff;
      border: 1.5px solid rgba(73, 61, 52, 0.12);
      border-radius: 14px;
      text-align: center;
      font-size: 18px;
      font-weight: 600;
      color: #3b2f27;
      transition: all 0.3s ease;
    }

    .home-filter__pos-input:focus {
      outline: none;
      border-color: #d8a34a;
      background: #fffefb;
    }

    .home-filter__pos-sep {
      width: 6px;
      height: 2px;
      background: #d8a34a;
      opacity: 0.3;
      margin: 0 2px;
    }

    .home-filter__footer {
      padding-top: 24px;
      border-top: 1px solid rgba(74, 62, 53, 0.08);
    }

    .home-filter__footer-controls {
      display: grid;
      grid-template-columns: 1fr 1.5fr auto;
      gap: 20px;
      align-items: center;
    }

    .home-filter__select-wrapper {
      display: grid;
      gap: 6px;
    }

    .home-filter__select-wrapper label {
      font-size: 12px;
      font-weight: 700;
      color: #8a7a6c;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding-left: 2px;
    }

    .home-filter__select-wrapper select {
      width: 100%;
      height: 48px;
      background: #fff;
      border: 1px solid rgba(73, 61, 52, 0.1);
      border-radius: 12px;
      padding: 0 16px;
      font-size: 15px;
      color: #3b2f27;
    }

    .home-filter__submit {
      height: 56px;
      padding: 0 48px;
      background: linear-gradient(135deg, #3b2f27, #201812);
      color: #fff;
      border: none;
      border-radius: 16px;
      font-size: 17px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 10px 25px rgba(32, 24, 18, 0.25);
    }

    .home-filter__submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(32, 24, 18, 0.35);
      background: linear-gradient(135deg, #4d3e34, #2a2019);
    }

    @media (min-width: 992px) {
      body.numbers-scale-soft .numbers-results-grid[data-view="grid"] {
        grid-template-columns: repeat(4, 240px) !important;
        justify-content: center !important;
        gap: 12px !important;
      }
    }

    @media (max-width: 1024px) {
      .home-filter__main {
        grid-template-columns: 1fr;
        gap: 20px;
      }
      .home-filter__orb {
        justify-self: center;
        margin: 0;
      }
    }

    @media (max-width: 768px) {
      .home-filter {
        padding: 20px 15px;
        border-radius: 25px;
        margin-bottom: 30px;
      }
      .home-filter__footer-controls {
        grid-template-columns: 1fr;
        gap: 12px;
      }
      .home-filter__submit {
        margin-top: 10px;
        width: 100%;
        height: 50px;
      }
      .home-filter__position-row {
        gap: 6px;
      }
      .home-filter__pos-prefix {
        width: 60px;
        font-size: 14px;
      }
      .home-filter__pos-input {
        height: 50px;
        font-size: 16px;
      }
    }
  </style>
  @php
    $selectedView = request('view') === 'list' ? 'list' : 'grid';
  @endphp
  <section class="numbers-hero" aria-labelledby="numbers-hero-title">
    <div class="numbers-hero-overlay"></div>
    <div class="container numbers-hero__content">
      <div class="numbers-hero__text">
        <p class="hero-kicker">ค้นหาเบอร์มงคล</p>
        <h1 id="numbers-hero-title">รวมเบอร์มงคลทั้งหมดที่พร้อมขาย</h1>
        <p>เลือกเบอร์ที่ตรงใจจากคลังเบอร์คุณภาพ พร้อมตัวกรองที่ช่วยค้นหาได้ไวขึ้น</p>
      </div>
    </div>
  </section>

  <section class="numbers-catalog-page" aria-labelledby="numbers-catalog-title">
    <div class="container numbers-catalog-shell">
      <div class="numbers-catalog-toolbar">
        <form class="numbers-filter-form" action="{{ route('numbers.index') }}" method="get">
          <input id="numbers-view-input" type="hidden" name="view" value="{{ $selectedView }}">
          
          <div class="home-filter">
            <div class="home-filter__main">
              <!-- Sequence Search -->
              <div class="home-filter__group">
                <label class="home-filter__label" for="numbers-search-sequence">ค้นหาจากชุดตัวเลข</label>
                <div class="home-filter__input-wrapper">
                  <i class="icon-search-small"></i>
                  <input
                    id="numbers-search-sequence"
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
                <label class="home-filter__label" for="numbers-prefix">ค้นหาตามตำแหน่ง</label>
                <div class="home-filter__position-row">
                  <input
                    id="numbers-prefix"
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
                  <select id="numbers-service-type" name="service_type">
                    <option value="">ทั้งหมด</option>
                    <option value="{{ \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID }}" @selected($selectedServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID)>รายเดือน</option>
                    <option value="{{ \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID }}" @selected($selectedServiceType === \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID)>เติมเงิน</option>
                  </select>
                </div>
                <div class="home-filter__select-wrapper">
                  <label>โปรโมชั่น / ราคาเบอร์</label>
                  <select id="numbers-plan" name="plan">
                    <option value="">ทั้งหมด</option>
                    @foreach ($plans as $plan)
                      <option value="{{ $plan['value'] }}" @selected($selectedPlan === $plan['value'])>{{ $plan['label'] }}</option>
                    @endforeach
                  </select>
                </div>
                <button class="home-filter__submit" type="submit">ค้นหาเบอร์</button>
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
                  @if ($number->supported_topic_icons !== [])
                    @php
                      $topicIcons = collect($number->supported_topic_icons);
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
                      <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">{{ $number->service_type_label }}</span></span>
                      @if ($number->is_prepaid)
                        <span class="card-meta-plan">{{ $number->payment_label }}</span>
                      @endif
                      @if ($number->is_postpaid)
                        <span class="card-meta-price">{!! $number->initial_payment_html !!}</span>
                      @endif
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
                  @if ($number->supported_topic_icons !== [])
                    @php
                      $topicIcons = collect($number->supported_topic_icons);
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
                      <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">{{ $number->service_type_label }}</span></span>
                      @if ($number->is_prepaid)
                        <span class="card-meta-plan">{{ $number->payment_label }}</span>
                      @endif
                      @if ($number->is_postpaid)
                        <span class="card-meta-price">{!! $number->initial_payment_html !!}</span>
                      @endif
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
              @if ($number->supported_topic_icons !== [])
                @php
                  $topicIcons = collect($number->supported_topic_icons);
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
                  <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">{{ $number->service_type_label }}</span></span>
                  @if ($number->is_prepaid)
                    <span class="card-meta-plan">{{ $number->payment_label }}</span>
                  @endif
                  @if ($number->is_postpaid)
                    <span class="card-meta-price">{!! $number->initial_payment_html !!}</span>
                  @endif
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

      const labels = {
        [serviceTypePostpaid]: "โปรรายเดือน",
        [serviceTypePrepaid]: "ราคาเบอร์",
        all: "โปรโมชั่น / ราคาเบอร์",
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
