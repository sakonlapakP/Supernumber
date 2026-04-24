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

@section('seo_schema')
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Organization",
  "name": "Supernumber",
  "url": "{{ url('/') }}",
  "logo": "{{ asset('images/logo.png') }}",
  "contactPoint": {
    "@@type": "ContactPoint",
    "telephone": "+66-96-323-2656",
    "contactType": "customer service"
  }
}
</script>
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "WebSite",
  "url": "{{ url('/') }}",
  "potentialAction": {
    "@@type": "SearchAction",
    "target": "{{ url('/numbers') }}?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>
@endsection

@section('content')
  <style>
    body.home-scale-soft .numbers {
      padding-top: 20px !important;
    }

    @media (min-width: 992px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        grid-template-columns: repeat(4, 240px) !important;
        grid-auto-rows: auto !important;
        justify-content: center !important;
        align-items: stretch !important;
        gap: 16px !important;
      }
    }

    /* Base Card Appearance (Defaults to Grid/Balanced Look) */
    .number-card--home {
      min-height: 290px !important;
      height: 100% !important;
      padding: 20px 15px !important;
      gap: 12px !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: stretch !important;
    }

    .number-card--home .card-top {
      padding: 12px 14px !important;
      border-radius: 16px !important;
      font-size: 20px !important;
      font-weight: 700 !important;
      letter-spacing: 0.02em !important;
      line-height: 1.2 !important;
    }

    .number-card--home .card-topic-icon {
      background: rgba(232, 243, 235, 0.95) !important;
      border-radius: 999px !important;
      width: 26px !important;
      height: 26px !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      font-size: 14px !important;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.92), 0 2px 6px rgba(34, 94, 67, 0.08) !important;
    }

    .number-card--home .card-body {
      flex-grow: 1 !important;
      display: flex !important;
      flex-direction: column !important;
      justify-content: center !important;
      align-items: center !important;
      padding: 0 !important;
      text-align: center !important;
    }

    .number-card--home .card-meta-stack {
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      gap: 2px !important;
      width: 100% !important;
    }

    .number-card--home .card-network-main {
      font-weight: 800 !important;
      font-size: 12px !important;
    }

    .number-card--home .card-meta-price {
      font-size: 17px !important;
      margin-top: 0 !important;
      line-height: 1.2 !important;
    }

    .number-card--home .card-btn {
      margin-top: auto !important;
      min-height: 42px !important;
      font-size: 15px !important;
      font-weight: 700 !important;
      border-radius: 12px !important;
    }

    /* List View Override (Shrinks the cards back down) */
    .home-card-grid[data-view="list"] {
      grid-template-columns: 1fr !important;
      gap: 12px !important;
    }

    .home-card-grid[data-view="list"] .number-card--home {
      display: grid !important;
      grid-template-columns: 160px 1fr 100px !important;
      grid-template-areas: "top body btn" "icons body btn" !important;
      align-items: center !important;
      gap: 0 12px !important;
      padding: 14px 20px !important;
      border-radius: 18px !important;
      min-height: 84px !important;
      height: auto !important;
      background: #ffffff !important;
      box-shadow: 0 2px 10px rgba(0,0,0,0.02), 0 10px 20px rgba(45, 33, 24, 0.04) !important;
    }

    .home-card-grid[data-view="list"] .number-card--home .card-top {
      grid-area: top !important;
      font-size: 20px !important;
      font-weight: 800 !important;
      line-height: 1 !important;
      margin: 0 !important;
      padding: 0 !important;
      background: none !important;
    }

    .home-card-grid[data-view="list"] .number-card--home .card-topic-icons {
      grid-area: icons !important;
      margin-top: 6px !important;
      gap: 6px !important;
      display: flex !important;
    }

    .home-card-grid[data-view="list"] .number-card--home .card-topic-icon {
      width: 22px !important;
      height: 22px !important;
      font-size: 11px !important;
      border-radius: 6px !important;
    }

    .home-card-grid[data-view="list"] .number-card--home .card-body {
      grid-area: body !important;
      display: flex !important;
      justify-content: center !important;
      align-items: center !important;
      padding: 0 10px !important;
    }

    .home-card-grid[data-view="list"] .number-card--home .card-meta-stack {
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      gap: 2px !important;
    }

    .home-card-grid[data-view="list"] .number-card--home .card-meta-price {
      font-size: 16px !important;
      font-weight: 700 !important;
    }

    .home-card-grid[data-view="list"] .number-card--home .card-btn {
      grid-area: btn !important;
      width: 100% !important;
      min-height: 42px !important;
      font-size: 14px !important;
      font-weight: 700 !important;
      border-radius: 12px !important;
      margin: 0 !important;
    }

    /* Redesigned Home Search Section - In-lined for Instant Load */
    .home-search {
      margin-top: -40px; 
      position: relative;
      z-index: 10;
      padding-bottom: 10px;
    }

    .home-filter {
      background: rgba(255, 255, 255, 0.75);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 40px;
      padding: 40px;
      border: 1px solid rgba(255, 255, 255, 0.8);
      box-shadow: 0 10px 40px rgba(45, 33, 24, 0.1), 0 2px 4px rgba(216, 163, 74, 0.1);
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
      margin-top: 20px;
    }

    .home-filter__submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(32, 24, 18, 0.35);
      background: linear-gradient(135deg, #4d3e34, #2a2019);
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
      .home-search {
        margin-top: -20px;
        padding-bottom: 30px;
      }
      .home-filter {
        padding: 20px 15px;
        border-radius: 25px;
      }
      .home-filter__header h2 {
        font-size: 24px;
      }
      .home-filter__header p {
        font-size: 14px;
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
      
      /* Optimize Numbers Grid for Mobile */
      .home-card-grid {
        gap: 10px !important;
        padding: 0 5px !important;
      }
      .home-card-item {
        padding: 12px !important;
      }
      .home-number-display {
        font-size: 18px !important;
      }
      .home-card-price {
        font-size: 13px !important;
      }
    }

    @media (min-width: 986px) and (max-width: 1199px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        grid-template-columns: repeat(3, 240px) !important;
        grid-auto-rows: auto !important;
        justify-content: center !important;
        align-items: stretch !important;
        gap: 16px !important;
      }
    }

    @media (min-width: 681px) and (max-width: 985px) {
      body.home-scale-soft .home-card-grid[data-view="grid"],
      body.home-scale-soft #home-prepaid-grid[data-view="grid"],
      body.home-scale-soft #home-postpaid-grid[data-view="grid"] {
        grid-template-columns: repeat(2, 240px) !important;
        grid-auto-rows: auto !important;
        justify-content: center !important;
        align-items: stretch !important;
        gap: 16px !important;
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

  <!-- Hero Section -->
  <section class="hero" aria-labelledby="hero-title">
    <div class="hero-media" aria-hidden="true">
      <img
        class="hero-media__image"
        src="{{ $homeBannerUrl }}"
        alt="เบอร์มงคล Supernumber - เปลี่ยนเบอร์เปลี่ยนชีวิต"
        fetchpriority="high"
        decoding="async"
      />
    </div>
    <div class="hero-overlay"></div>
    <div class="container hero-content">
      <div class="hero-left">
        <p class="hero-kicker">แค่เปลี่ยนเบอร์ชีวิตคุณก็เปลี่ยน</p>
        <h1 class="hero-title" id="hero-title">
          เบอร์มงคล วิเคราะห์เบอร์เสริมพลังชีวิต และ คัดสรรเบอร์พิเศษสุดเพื่อคุณ | Supernumber
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

  <!-- Search Section -->
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

            <div class="home-filter__group">
              <label class="home-filter__label" for="home-pos-prefix">ค้นหาตามตำแหน่ง</label>
              <div class="home-filter__position-row">
                <input
                  id="home-pos-prefix"
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
                          <span class="card-meta-plan"><strong>ราคา {{ $number['payment_label'] }}</strong></span>
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
                          <span class="card-meta-plan"><strong>ราคา {{ $number['payment_label'] }}</strong></span>
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
                ${!number.is_postpaid ? `<span class="card-meta-plan"><strong>ราคา ${escapeHtml(number.payment_label)}</strong></span>` : ""}
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
