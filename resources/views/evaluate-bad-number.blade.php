@extends('layouts.app')

@section('title', 'Supernumber | วิเคราะห์เบอร์ควรระวัง')
@section('meta_description', 'ผลการวิเคราะห์เบอร์ที่มีคู่เลขควรระวังระดับสูง พร้อมคำแนะนำเพื่อหลีกเลี่ยงความเสี่ยง')
@section('og_title', 'Supernumber | วิเคราะห์เบอร์ควรระวัง')
@section('og_description', 'ผลการวิเคราะห์เบอร์ที่มีคู่เลขควรระวังระดับสูง พร้อมคำแนะนำเพื่อหลีกเลี่ยงความเสี่ยง')
@section('canonical', url('/evaluateBadNumber'))
@section('og_url', url('/evaluateBadNumber'))
@section('og_image', asset('images/evaluate_banner.jpg'))
@section('preload_image', asset('images/evaluate_banner.jpg'))

@section('content')
  @php
    $lastSeven = substr($phone, -7);
    $pairs = [];
    for ($i = 0; $i < 6; $i++) {
        $pairs[] = substr($lastSeven, $i, 2);
    }

    $groupedPairs = [];
    foreach ($pairs as $index => $pair) {
        $chars = str_split($pair);
        sort($chars);
        $key = implode('', $chars);
        if (! isset($groupedPairs[$key])) {
            $groupedPairs[$key] = [
                'key' => $key,
                'primary_pair' => $pair,
                'first_index' => $index,
            ];
        }
    }
    usort($groupedPairs, fn ($a, $b) => $a['first_index'] <=> $b['first_index']);

    $statusLabelMap = [
        'good' => 'ตรง',
        'bad' => 'เลขควรระวัง',
        'conditional' => 'ใช้ได้บางกรณี',
    ];
    $statusClassMap = [
        'good' => 'is-good',
        'bad' => 'is-danger',
        'conditional' => 'is-neutral',
        'neutral' => 'is-neutral',
    ];
    $pairHeadingMap = [
        'good' => 'คู่เลขดี',
        'bad' => 'คู่เลขเสีย',
        'conditional' => 'คู่เลขดีกับคู่เลขเสีย',
        'neutral' => 'คู่เลขดีกับคู่เลขเสีย',
    ];
    $pairMeaningMap = \App\Models\PairMeaning::whereIn('pair', $pairs)
        ->get()
        ->keyBy('pair');

    $pairCards = [];
    foreach ($groupedPairs as $group) {
        $label = $group['key'];
        if (strlen($label) === 2 && $label[0] !== $label[1]) {
            $label = $label . ' ' . strrev($label);
        }

        $meaning = $pairMeaningMap->get($group['primary_pair'])
            ?? $pairMeaningMap->get($group['key'])
            ?? $pairMeaningMap->get(strrev($group['key']));
        $status = $meaning?->status ?? 'neutral';

        $pairCards[] = [
            'pair' => $label,
            'status' => $status,
            'label' => $statusLabelMap[$status] ?? 'เลขทั่วไป',
            'heading' => $pairHeadingMap[$status] ?? 'คู่เลขดีกับคู่เลขเสีย',
            'desc' => $meaning?->short_meaning ?? 'ยังไม่มีข้อมูลความหมายแบบสั้นสำหรับคู่นี้',
            'class' => $statusClassMap[$status] ?? 'is-neutral',
        ];
    }

    $recommendedNumbers = \App\Models\PhoneNumber::query()
        ->available()
        ->where('network_code', 'true_dtac')
        ->where('phone_number', '!=', $phone)
        ->inRandomOrder()
        ->limit(12)
        ->get();

    if ($recommendedNumbers->count() < 12) {
        $excludedNumbers = $recommendedNumbers
            ->pluck('phone_number')
            ->push($phone)
            ->all();
        $extraNumbers = \App\Models\PhoneNumber::query()
            ->available()
            ->where('network_code', 'true_dtac')
            ->whereNotIn('phone_number', $excludedNumbers)
            ->inRandomOrder()
            ->limit(12 - $recommendedNumbers->count())
            ->get();
        $recommendedNumbers = $recommendedNumbers->concat($extraNumbers);
    }
  @endphp
  <section class="evaluate-hero evaluate-hero--danger" aria-labelledby="evaluate-title">
    <div class="evaluate-overlay"></div>
    <div class="container evaluate-hero__content">
      <div class="evaluate-hero__text">
        <p class="evaluate-kicker">รายงานความเสี่ยง</p>
        <h1 id="evaluate-title">ผลการวิเคราะห์เบอร์ที่ต้องระวังเป็นพิเศษ</h1>
        <p>
          ระบบตรวจพบคู่เลขที่มีความเสี่ยงสูง แนะนำให้อ่านคำเตือนให้ครบและปรึกษาทีม Supernumber
        </p>
      </div>
    </div>
  </section>

  <section class="evaluate-results evaluate-results--danger">
    <div class="container">
      <div class="evaluate-summary">
        <div class="summary-number summary-number--danger">
          <span>หมายเลขมือถือ</span>
          <strong>{{ $phone }}</strong>
          <small>ระดับความเสี่ยง: สูง</small>
        </div>
        <div class="summary-body">
          <h2>สัญญาณเตือนสำคัญ</h2>
          <p>
            เบอร์นี้มีพลังงานด้านอารมณ์และความเครียดสูง อาจส่งผลต่อการตัดสินใจ การสื่อสาร และสุขภาพจิต
            เมื่ออยู่ในสถานการณ์กดดัน หากใช้งานต่อเนื่องควรมีการจัดสมดุลอย่างจริงจัง
          </p>
          <div class="summary-tags">
            <span class="badge badge-alert">ควรระวังสูง</span>
            <span class="badge badge-alert">หลีกเลี่ยงการใช้ยาวนาน</span>
            <span class="badge badge-neutral">แนะนำปรับสมดุล</span>
          </div>
        </div>
      </div>

      <div class="danger-banner">
        <div class="danger-icon">!</div>
        <div>
          <h3>คำเตือนระดับสูง</h3>
          <p>
            คู่เลขบางชุดมีแนวโน้มกระตุ้นความใจร้อน อารมณ์แปรปรวน และการตัดสินใจผิดพลาด
            ควรหลีกเลี่ยงการใช้ในช่วงเวลาที่ต้องการความนิ่งและความชัดเจน
          </p>
        </div>
      </div>

      <div class="pair-grid">
        @foreach ($pairCards as $card)
          <article class="pair-card {{ $card['class'] }}">
            <div class="pair-score">{{ $card['pair'] }}</div>
            <h4>{{ $card['heading'] }}</h4>
            <p>{{ $card['desc'] }}</p>
          </article>
        @endforeach
      </div>

      <section class="recommend-section" aria-labelledby="recommend-title">
        <h2 id="recommend-title">เบอร์แนะนำเพื่อปรับสมดุล</h2>
        <div class="card-grid">
          @forelse ($recommendedNumbers as $recommended)
            <article class="number-card">
              <div class="card-top">{{ $recommended->display_number ?: $recommended->phone_number }}</div>
              <div class="card-body">
                <div class="card-meta-stack">
                  <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">{{ $recommended->service_type_label }}</span></span>
                  <span class="card-meta-plan">{{ $recommended->payment_label }}</span>
                </div>
              </div>
              <a class="card-btn card-btn--buy" href="{{ route('evaluate', ['phone' => $recommended->phone_number]) }}">สั่งซื้อ</a>
            </article>
          @empty
            <p class="numbers-empty">ยังไม่มีเบอร์แนะนำในระบบตอนนี้</p>
          @endforelse
        </div>
      </section>

      <div class="evaluate-cta evaluate-cta--danger">
        <div>
          <h3>ต้องการให้ผู้เชี่ยวชาญช่วยวิเคราะห์แบบละเอียด?</h3>
          <p>ให้ทีม Supernumber คัดเบอร์ใหม่ที่เหมาะกับคุณ ลดความเสี่ยงและเพิ่มโอกาสในชีวิต</p>
        </div>
        <a class="cta-btn" href="{{ route('home') }}">กลับไปเลือกเบอร์</a>
      </div>
    </div>
  </section>
@endsection
