@extends('layouts.app')

@section('title', 'ผลแนะนำเบอร์มงคลที่เหมาะกับคุณ | Supernumber')
@section('meta_description', 'ผลแนะนำเบอร์มงคลจากข้อมูลอาชีพและเป้าหมายชีวิต พร้อมอธิบายเงื่อนไขที่ใช้คัดเบอร์')
@section('og_title', 'ผลแนะนำเบอร์มงคลที่เหมาะกับคุณ | Supernumber')
@section('og_description', 'ดูผลแนะนำเบอร์มงคลที่คัดจากอาชีพ เป้าหมาย และคู่เลขสำคัญ')
@section('canonical', url('/estimate'))
@section('og_url', url('/estimate'))
@section('og_image', asset('images/home_banner.jpg'))
@section('body_class', 'numbers-scale-soft')

@section('content')
  <section class="estimate-results-hero" aria-labelledby="estimate-results-title">
    <div class="container estimate-results-hero__content">
      <p class="hero-kicker">ผลวิเคราะห์เลือกเบอร์</p>
      <h1 id="estimate-results-title">สำหรับคุณ {{ $lead->full_name }} เบอร์ที่เหมาะกับคุณคือ</h1>
      <p>ระบบคัดจากอาชีพ เป้าหมาย และคู่เลขเฉพาะที่เหมาะกับสิ่งที่คุณต้องการเสริม</p>
    </div>
  </section>

  <section class="estimate-results-page">
    <div class="container estimate-results-shell">
      <div class="estimate-results-summary">
        <div class="estimate-results-summary__main">
          <p>ข้อมูลที่ใช้วิเคราะห์</p>
          <h2>{{ $lead->work_type_label }} · {{ $lead->goal_label }}</h2>
        </div>
        <a class="estimate-results-summary__link" href="{{ route('estimate') }}">กรอกข้อมูลใหม่</a>
      </div>

      <div class="estimate-rule-grid">
        <article class="estimate-rule-card">
          <span>อาชีพ</span>
          <h3>{{ $lead->work_type_label }}</h3>
          <p>{{ $work_rule_text }}</p>
          @if ($work_topic_cards !== [])
            <div class="estimate-topic-pills">
              @foreach ($work_topic_cards as $topic)
                <span title="{{ $topic['topic'] }}">{{ $topic['icon'] }} {{ $topic['topic'] }}</span>
              @endforeach
            </div>
          @endif
        </article>

        <article class="estimate-rule-card">
          <span>สิ่งที่อยากเสริม</span>
          <h3>{{ $lead->goal_label }}</h3>
          <p>{{ $goal_rule_text }}</p>
        </article>
      </div>

      <div class="estimate-results-head">
        <div class="estimate-results-head__copy">
          <h2>เบอร์แนะนำ</h2>
          <p>สุ่มจากเบอร์ที่ตรงกับเงื่อนไขมากที่สุด</p>
        </div>
        <div class="numbers-view-toggle" id="estimate-results-view-toggle" role="group" aria-label="เลือกรูปแบบการแสดงผลเบอร์แนะนำ">
          <button class="numbers-view-toggle__button" type="button" data-view="list" aria-pressed="false">รายการ</button>
          <button class="numbers-view-toggle__button is-active" type="button" data-view="grid" aria-pressed="true">ตาราง</button>
        </div>
      </div>

      @if ($numbers->isNotEmpty() || $prepaid_numbers->isNotEmpty())
        <div class="estimate-result-number-groups" id="estimate-result-number-groups" data-view="grid">
          @if ($numbers->isNotEmpty())
            <section class="estimate-result-number-section" aria-labelledby="estimate-recommended-title">
              <div class="estimate-result-number-section__head">
                <h3 id="estimate-recommended-title">สุ่มเบอร์ที่เหมาะกับคุณ</h3>
                <p>คัดจากอาชีพและสิ่งที่อยากเสริม</p>
              </div>
              <div class="card-grid numbers-catalog-grid listing-card-grid estimate-result-number-grid" data-view="grid">
                @foreach ($numbers as $number)
                  @include('partials.number-card', ['number' => $number])
                @endforeach
              </div>
            </section>
          @endif

          @if ($prepaid_numbers->isNotEmpty())
            <section class="estimate-result-number-section" aria-labelledby="estimate-prepaid-title">
              <div class="estimate-result-number-section__head">
                <h3 id="estimate-prepaid-title">สุ่มเบอร์เติมเงิน</h3>
                <p>เบอร์เติมเงินที่ยังผ่านเงื่อนไขเดียวกัน</p>
              </div>
              <div class="card-grid numbers-catalog-grid listing-card-grid estimate-result-number-grid" data-view="grid">
                @foreach ($prepaid_numbers as $number)
                  @include('partials.number-card', ['number' => $number])
                @endforeach
              </div>
            </section>
          @endif
        </div>
      @else
        <div class="estimate-results-empty">
          <h3>ยังไม่พบเบอร์ที่ตรงกับเงื่อนไขนี้</h3>
          <p>ลองเปลี่ยนอาชีพหรือเป้าหมาย แล้วค้นหาใหม่อีกครั้ง</p>
        </div>
      @endif
    </div>
  </section>
@endsection

@push('scripts')
  <script>
    (() => {
      const groupRoot = document.getElementById("estimate-result-number-groups");
      const toggle = document.getElementById("estimate-results-view-toggle");

      if (!groupRoot || !toggle) return;

      const buttons = Array.from(toggle.querySelectorAll("[data-view]"));
      const grids = Array.from(groupRoot.querySelectorAll(".estimate-result-number-grid"));

      const applyView = (view) => {
        const normalizedView = view === "list" ? "list" : "grid";
        groupRoot.dataset.view = normalizedView;
        grids.forEach((grid) => {
          grid.dataset.view = normalizedView;
        });

        buttons.forEach((button) => {
          const isActive = button.dataset.view === normalizedView;
          button.classList.toggle("is-active", isActive);
          button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
      };

      toggle.addEventListener("click", (event) => {
        const target = event.target.closest("[data-view]");
        if (!target) return;

        applyView(target.dataset.view);
      });
    })();
  </script>
@endpush
