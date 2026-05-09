<article class="number-card number-card--listing number-card--catalog">
  <div class="card-left-group">
    <div class="card-top">{{ $number->formatted_number }}</div>

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
