@extends('layouts.app')

@section('title', 'Supernumber | ความหมายเบอร์มงคล')
@section('meta_description', 'สรุปความหมายเบอร์มงคลแบบเข้าใจง่าย พร้อมจุดเด่นและคำแนะนำการใช้งาน')
@section('og_title', 'Supernumber | ความหมายเบอร์มงคล')
@section('og_description', 'สรุปความหมายเบอร์มงคลแบบเข้าใจง่าย พร้อมจุดเด่นและคำแนะนำการใช้งาน')
@section('canonical', url('/good-number'))
@section('og_url', url('/good-number'))
@section('og_image', asset('images/good_number_banner.jpg'))
@section('preload_image', asset('images/good_number_banner.jpg'))

@section('content')
  @php
    $rawNumber = request('number');
    $number = preg_replace('/[^0-9]/', '', $rawNumber ?? '');
    if ($number === '') {
        $number = '0645391429';
    }
    if (strlen($number) < 7) {
        $number = '0645391429';
    }
    $currentNumberModel = \App\Models\PhoneNumber::query()
        ->where('phone_number', $number)
        ->first();
    $currentPackage = $currentNumberModel?->normalized_package_price ?? 699;

    $recommendedNumbers = \App\Models\PhoneNumber::query()
        ->available()
        ->where('network_code', 'true_dtac')
        ->where('phone_number', '!=', $number)
        ->inRandomOrder()
        ->limit(8)
        ->get();

    if ($recommendedNumbers->count() < 8) {
        $excludedNumbers = $recommendedNumbers
            ->pluck('phone_number')
            ->push($number)
            ->all();
        $extraNumbers = \App\Models\PhoneNumber::query()
            ->available()
            ->where('network_code', 'true_dtac')
            ->whereNotIn('phone_number', $excludedNumbers)
            ->inRandomOrder()
            ->limit(8 - $recommendedNumbers->count())
            ->get();
        $recommendedNumbers = $recommendedNumbers->concat($extraNumbers);
    }

    $lastSeven = substr($number, -7);
    $pairs = [];
    for ($i = 0; $i < 6; $i++) {
        $pairs[] = substr($lastSeven, $i, 2);
    }
    $groupedPairs = [];
    foreach ($pairs as $index => $pair) {
        $chars = str_split($pair);
        sort($chars);
        $key = implode('', $chars);
        if (!isset($groupedPairs[$key])) {
            $groupedPairs[$key] = [
                'key' => $key,
                'primary_pair' => $pair,
                'first_index' => $index,
            ];
        }
    }
    usort($groupedPairs, fn ($a, $b) => $a['first_index'] <=> $b['first_index']);

    $pairsWithScore = [];
    for ($i = 0; $i < 6; $i++) {
        $pair = $pairs[$i];
        $pairsWithScore[] = [
            'pair' => $pair,
            'score' => $i < 5 ? 10 : 30,
            'position' => $i + 1,
        ];
    }
    $groupScores = [];
    foreach ($pairsWithScore as $index => $data) {
        $chars = str_split($data['pair']);
        sort($chars);
        $key = implode('', $chars);
        $mapKey = 'k' . $key;
        if (!isset($groupScores[$mapKey])) {
            $groupScores[$mapKey] = [
                'key' => $key,
                'score' => 0,
                'first_index' => $index,
            ];
        }
        $groupScores[$mapKey]['score'] += $data['score'];
    }
    $topGroup = null;
    foreach ($groupScores as $meta) {
        if ($topGroup === null) {
            $topGroup = $meta;
            continue;
        }
        if ($meta['score'] > $topGroup['score']) {
            $topGroup = $meta;
        } elseif ($meta['score'] === $topGroup['score'] && $meta['first_index'] < $topGroup['first_index']) {
            $topGroup = $meta;
        }
    }
    $topGroupKey = $topGroup['key'];
    $topGroupScore = $topGroup['score'];
    $topGroupLabel = $topGroupKey;
    if (strlen($topGroupKey) === 2 && $topGroupKey[0] !== $topGroupKey[1]) {
        $topGroupLabel = $topGroupKey . ' ' . strrev($topGroupKey);
    }

    $statusLabelMap = [
        'good' => 'เลขส่งเสริม',
        'bad' => 'เลขควรระวัง',
        'conditional' => 'ใช้ได้บางกรณี',
    ];
    $statusClassMap = [
        'good' => 'is-good',
        'bad' => 'is-alert',
        'conditional' => 'is-neutral',
        'neutral' => 'is-neutral',
    ];
    $pairMeaningMap = \App\Models\PairMeaning::whereIn('pair', $pairs)
        ->get()
        ->keyBy('pair');

    $topMeaningRecord = \App\Models\PairMeaning::where('pair', $topGroupKey)->first();
    $topMeaning = [
        'title' => $topMeaningRecord ? ($statusLabelMap[$topMeaningRecord->status] ?? 'เลขทั่วไป') : 'เลขทั่วไป',
        'desc' => $topMeaningRecord
            ? ($topMeaningRecord->long_meaning ?: $topMeaningRecord->short_meaning)
            : 'คู่นี้มีคะแนนสูงสุดตามตำแหน่ง แนะนำประเมินร่วมกับคู่เลขอื่นเพื่อความแม่นยำ',
    ];
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
            'desc' => $meaning?->short_meaning ?? 'ยังไม่มีข้อมูลความหมายแบบสั้นสำหรับคู่นี้',
            'class' => $statusClassMap[$status] ?? 'is-neutral',
        ];
    }
  @endphp

  <section class="good-number-hero" aria-labelledby="good-number-title">
    <div class="good-number-overlay"></div>
    <div class="container good-number-hero__content">
      <div class="good-number-hero__text">
        <p class="hero-kicker">ความหมายเบอร์มงคล</p>
        <h1 id="good-number-title">ความหมายของเบอร์ {{ $number }}</h1>
        <p>
          อ่านสรุปพลังงานของเบอร์ พร้อมคำแนะนำการใช้งานให้เหมาะกับจังหวะชีวิตของคุณ
        </p>
      </div>
    </div>
  </section>

  <section class="good-number-results">
    <div class="container">
      <div class="evaluate-summary">
        <div class="summary-number">
          <span>หมายเลขที่คุณเลือก</span>
          <strong>{{ $number }}</strong>
          <a class="summary-order-btn" href="{{ route('book', ['number' => $number, 'package' => $currentPackage]) }}">สั่งซื้อ</a>
        </div>
        <div class="summary-body">
          <h2>คู่เลขเด่นคะแนนสูงสุด: {{ $topGroupLabel }} ({{ $topGroupScore }} คะแนน)</h2>
          <p>{{ $topMeaning['title'] }} — {{ $topMeaning['desc'] }}</p>
          <div class="summary-tags">
            <span class="badge badge-good">สื่อสารโดดเด่น</span>
            <span class="badge badge-good">โอกาสใหม่</span>
            <span class="badge badge-neutral">เสริมความมั่นใจ</span>
          </div>
        </div>
      </div>

      <div class="evaluate-highlight">
        <div class="highlight-card">
          <h3>การงานและธุรกิจ</h3>
          <p>ช่วยให้ปิดดีลง่ายขึ้น เสริมพลังการตัดสินใจ และทำให้ทีมเชื่อมั่นในทิศทางของคุณ</p>
        </div>
        <div class="highlight-card">
          <h3>การเงินและโชคลาภ</h3>
          <p>รายรับเข้ามาเป็นจังหวะดี เหมาะกับการเริ่มโปรเจกต์ใหม่และขยายฐานลูกค้า</p>
        </div>
        <div class="highlight-card">
          <h3>ความรักและความสัมพันธ์</h3>
          <p>เสน่ห์นุ่มนวล สื่อสารตรงใจ ลดความเข้าใจผิด เหมาะกับการปรับความสัมพันธ์ให้มั่นคง</p>
        </div>
      </div>

      <div class="pair-grid">
        @foreach ($pairCards as $card)
          <article class="pair-card {{ $card['class'] }}">
            <div class="pair-score">{{ $card['pair'] }}</div>
            <h4>{{ $card['label'] }}</h4>
            <p>{{ $card['desc'] }}</p>
          </article>
        @endforeach
      </div>

      <section class="recommend-section" aria-labelledby="recommend-title">
        <h2 id="recommend-title">เลือกเบอร์อื่นเพิ่มเติม</h2>
        <div class="card-grid">
          @forelse ($recommendedNumbers as $recommended)
            <article class="number-card">
              <div class="card-top">{{ $recommended->display_number ?: $recommended->phone_number }}</div>
              <div class="card-body">
                <div class="card-meta-stack">
                  <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">รายเดือน</span></span>
                  <span class="card-meta-plan">{{ $recommended->package_label }}</span>
                </div>
              </div>
              <a class="card-btn card-btn--buy" href="{{ route('good-number', ['number' => $recommended->phone_number]) }}">สั่งซื้อ</a>
            </article>
          @empty
            <p class="numbers-empty">ยังไม่มีเบอร์แนะนำในระบบตอนนี้</p>
          @endforelse
        </div>
      </section>

      <div class="evaluate-cta">
        <div>
          <h3>อยากได้เบอร์ที่ตรงกับเป้าหมายมากขึ้น?</h3>
          <p>ให้ทีม Supernumber ช่วยคัดเบอร์มงคลที่ตอบโจทย์งาน เงิน และความรักของคุณ</p>
        </div>
        <a class="cta-btn" href="{{ route('home') }}">กลับไปเลือกเบอร์</a>
      </div>
    </div>
  </section>
@endsection
