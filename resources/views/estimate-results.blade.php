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
          <p>เรียงจากเบอร์ที่ตรงกับเงื่อนไขมากที่สุด</p>
        </div>
        <div class="numbers-view-toggle" id="estimate-results-view-toggle" role="group" aria-label="เลือกรูปแบบการแสดงผลเบอร์แนะนำ">
          <button class="numbers-view-toggle__button" type="button" data-view="list" aria-pressed="false">รายการ</button>
          <button class="numbers-view-toggle__button is-active" type="button" data-view="grid" aria-pressed="true">ตาราง</button>
        </div>
      </div>

      @if ($numbers->isNotEmpty())
        <div class="card-grid listing-card-grid estimate-result-number-grid" id="estimate-result-number-grid" data-view="grid">
          @foreach ($numbers as $number)
            <article class="number-card number-card--listing number-card--catalog">
              <div class="card-left-group">
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
              </div>
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
      const grid = document.getElementById("estimate-result-number-grid");
      const toggle = document.getElementById("estimate-results-view-toggle");

      if (!grid || !toggle) return;

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

      toggle.addEventListener("click", (event) => {
        const target = event.target.closest("[data-view]");
        if (!target) return;

        applyView(target.dataset.view);
      });
    })();
  </script>
@endpush
