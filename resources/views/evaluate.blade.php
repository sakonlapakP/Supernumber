@extends('layouts.app')

@section('title', 'Supernumber | ผลการวิเคราะห์เบอร์')
@section('meta_description', 'ผลการวิเคราะห์เบอร์มงคลแบบเข้าใจง่าย พร้อมแนวโน้มและเลขคู่ที่ส่งเสริมหรือควรระวัง')
@section('og_title', 'Supernumber | ผลการวิเคราะห์เบอร์')
@section('og_description', 'ผลการวิเคราะห์เบอร์มงคลแบบเข้าใจง่าย พร้อมแนวโน้มและเลขคู่ที่ส่งเสริมหรือควรระวัง')
@section('canonical', url('/evaluate'))
@section('og_url', url('/evaluate'))
@section('og_image', asset('images/evaluate_banner.jpg'))
@section('preload_image', asset('images/evaluate_banner.jpg'))

@section('content')
  @php
    $rawPhone = request('phone');
    $phone = preg_replace('/[^0-9]/', '', $rawPhone ?? '');
    if ($phone === '') {
        $phone = '0641234567';
    }
    if (strlen($phone) < 7) {
        $phone = '0641234567';
    }
    $lastSeven = substr($phone, -7);
    $endingPair = substr($lastSeven, -2);
    $endingDigit = substr($lastSeven, -1);
    $endingWarning = false;
    $endingRuleLabel = null;
    $endingReason = null;
    if (in_array($endingPair, ['69', '96'], true)) {
        $endingWarning = true;
        $endingRuleLabel = '69 96';
        $endingReason = 'ทำให้เก็บเงินไม่อยู่ ใช้เงินหมดง่าย และสิ่งที่คิดว่าจะได้มักหลุดมือ';
    } elseif ($endingDigit === '2') {
        $endingWarning = true;
        $endingRuleLabel = '2';
        $endingReason = 'อารมณ์อ่อนไหวง่าย ไม่หนักแน่น ส่งผลให้การตัดสินใจคลาดเคลื่อนได้บ่อย';
    } elseif ($endingDigit === '8') {
        $endingWarning = true;
        $endingRuleLabel = '8';
        $endingReason = 'อารมณ์ขึ้นลงไว ตัดสินใจเร็ว ขาดความยับยั้งชั่งใจ เสี่ยงต่อการตัดสินใจพลาด';
    }
    $pairs = [];
    for ($i = 0; $i < 6; $i++) {
        $pair = substr($lastSeven, $i, 2);
        $pairs[] = [
            'pair' => $pair,
            'score' => $i < 5 ? 10 : 30,
            'position' => $i + 1,
        ];
    }
    $groupScores = [];
    foreach ($pairs as $index => $data) {
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
    $topMeaningRecord = \App\Models\PairMeaning::where('pair', $topGroupKey)->first();
    $topMeaning = [
        'title' => $topMeaningRecord ? ($statusLabelMap[$topMeaningRecord->status] ?? 'เลขทั่วไป') : 'เลขทั่วไป',
        'desc' => $topMeaningRecord
            ? ($topMeaningRecord->long_meaning ?: $topMeaningRecord->short_meaning)
            : 'คู่นี้มีคะแนนสูงสุดตามตำแหน่ง แนะนำประเมินร่วมกับคู่เลขอื่นเพื่อความแม่นยำ',
    ];
    $statusClassMap = [
        'good' => 'is-good',
        'bad' => 'is-alert',
        'conditional' => 'is-neutral',
    ];
    $pairMeaningMap = \App\Models\PairMeaning::whereIn('pair', collect($pairs)->pluck('pair'))
        ->get()
        ->keyBy('pair');
    $groupedPairs = [];
    foreach ($pairs as $index => $data) {
        $chars = str_split($data['pair']);
        sort($chars);
        $key = implode('', $chars);
        if (!isset($groupedPairs[$key])) {
            $groupedPairs[$key] = [
                'key' => $key,
                'primary_pair' => $data['pair'],
                'first_index' => $index,
            ];
        }
    }
    usort($groupedPairs, fn ($a, $b) => $a['first_index'] <=> $b['first_index']);
    $pairCards = [];
    $hasBadPair = $endingWarning;
    $pairCardsData = [];
    foreach ($groupedPairs as $group) {
        $label = $group['key'];
        if (strlen($label) === 2 && $label[0] !== $label[1]) {
            $label = $label . ' ' . strrev($label);
        }
        $meaning = $pairMeaningMap->get($group['primary_pair'])
            ?? $pairMeaningMap->get($group['key'])
            ?? $pairMeaningMap->get(strrev($group['key']));
        $status = $meaning?->status ?? 'neutral';
        if ($status === 'bad') {
            $hasBadPair = true;
        }
        $desc = $meaning?->short_meaning ?? 'ยังไม่มีข้อมูลความหมายแบบสั้นสำหรับคู่นี้';
        $pairCardsData[] = [
            'pair' => $label,
            'key' => $group['key'],
            'status' => $status,
            'label' => $statusLabelMap[$status] ?? 'เลขทั่วไป',
            'desc' => $desc,
        ];
    }
    $badCardClass = $hasBadPair ? 'is-danger' : 'is-alert';
    foreach ($pairCardsData as $card) {
        $cardClass = $card['status'] === 'bad'
            ? $badCardClass
            : ($statusClassMap[$card['status']] ?? 'is-neutral');
        $pairCards[] = $card + ['class' => $cardClass];
    }
  @endphp
  <section class="evaluate-hero{{ $hasBadPair ? ' evaluate-hero--danger' : '' }}" aria-labelledby="evaluate-title">
    <div class="evaluate-overlay"></div>
    <div class="container evaluate-hero__content">
      <div class="evaluate-hero__text">
        <p class="evaluate-kicker">ผลการวิเคราะห์</p>
        <h1 id="evaluate-title">ผลการวิเคราะห์เบอร์มงคลของคุณ</h1>
        <p>
          วิเคราะห์เสริมพลังให้ชัดเจน เข้าใจง่าย พร้อมคำแนะนำที่เหมาะกับคุณโดยทีม Supernumber
        </p>
      </div>
    </div>
  </section>

  <section class="evaluate-results{{ $hasBadPair ? ' evaluate-results--danger' : '' }}">
    <div class="container">
      <div class="evaluate-summary">
        <div class="summary-number{{ $hasBadPair ? ' summary-number--danger' : '' }}">
          <span>หมายเลขมือถือ</span>
          <strong>{{ $phone }}</strong>
          <small>ผลวิเคราะห์โดย Supernumber</small>
        </div>
        <div class="summary-body">
          <h2>คู่เลขเด่นคะแนนสูงสุด: {{ $topGroupLabel }} ({{ $topGroupScore }} คะแนน)</h2>
          <p>
            {{ $topMeaning['title'] }} — {{ $topMeaning['desc'] }}
          </p>
          <div class="summary-tags">
            <span class="badge badge-good">เลขส่งเสริม</span>
            <span class="badge badge-alert">เลขควรระวัง</span>
            <span class="badge badge-neutral">แนะนำปรับสมดุล</span>
          </div>
        </div>
      </div>

      @if ($hasBadPair)
        <div class="danger-banner">
          <div class="danger-icon">!</div>
          <div>
            <h3>คำเตือนระดับสูง</h3>
            <p>
              @if ($endingWarning)
                เลข {{ $endingRuleLabel }} ไม่ควรอยู่ท้ายสุดของเบอร์ — {{ $endingReason }}
                ควรพิจารณาปรับสมดุลโดยผู้เชี่ยวชาญ
              @else
                ระบบพบอย่างน้อย 1 คู่เลขที่จัดอยู่ในกลุ่มควรระวังสูง แนะนำให้พิจารณาใช้งานอย่างระมัดระวังและปรับสมดุลโดยผู้เชี่ยวชาญ
              @endif
            </p>
          </div>
        </div>
      @endif

      <div class="evaluate-highlight">
        <div class="highlight-card">
          <h3>ภาพรวมการงาน</h3>
          <p>การเจรจาโดดเด่น มีโอกาสปิดงานเร็ว เหมาะกับงานขายและงานบริการ</p>
        </div>
        <div class="highlight-card">
          <h3>ภาพรวมการเงิน</h3>
          <p>รายรับเข้ามาเป็นระยะ แต่ควรควบคุมรายจ่ายกะทันหันและเลี่ยงการลงทุนเสี่ยง</p>
        </div>
        <div class="highlight-card">
          <h3>ภาพรวมความรัก</h3>
          <p>เสน่ห์ดี มีคนเข้ามา แต่ควรสื่อสารให้ชัดเจน ลดการตีความผิดพลาด</p>
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
        <h2 id="recommend-title">เบอร์แนะนำสำหรับคุณ</h2>
        <div class="recommend-grid">
          <article class="number-card">
            <div class="card-top">0646495945</div>
            <div class="card-body">
              <div class="card-meta-stack">
                <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">รายเดือน</span></span>
                <span class="card-meta-plan">โปรโมชั่น 4G+ Super Smart</span>
                <span>1499 ขึ้นไป</span>
              </div>
            </div>
            <a class="card-btn" href="{{ route('good-number', ['number' => '0646495945']) }}">ดูความหมาย</a>
            <a class="card-btn card-btn--buy" href="{{ route('book', ['number' => '0646495945', 'package' => 1499]) }}">สั่งซื้อ</a>
          </article>
          <article class="number-card">
            <div class="card-top">0645164549</div>
            <div class="card-body">
              <div class="card-meta-stack">
                <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">รายเดือน</span></span>
                <span class="card-meta-plan">โปรโมชั่น 4G+ Super Smart</span>
                <span>699 ขึ้นไป</span>
              </div>
            </div>
            <a class="card-btn" href="{{ route('good-number', ['number' => '0645164549']) }}">ดูความหมาย</a>
            <a class="card-btn card-btn--buy" href="{{ route('book', ['number' => '0645164549', 'package' => 699]) }}">สั่งซื้อ</a>
          </article>
          <article class="number-card">
            <div class="card-top">0645953639</div>
            <div class="card-body">
              <div class="card-meta-stack">
                <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">รายเดือน</span></span>
                <span class="card-meta-plan">โปรโมชั่น 4G+ Super Smart</span>
                <span>1499 ขึ้นไป</span>
              </div>
            </div>
            <a class="card-btn" href="{{ route('good-number', ['number' => '0645953639']) }}">ดูความหมาย</a>
            <a class="card-btn card-btn--buy" href="{{ route('book', ['number' => '0645953639', 'package' => 1499]) }}">สั่งซื้อ</a>
          </article>
          <article class="number-card">
            <div class="card-top">0645636463</div>
            <div class="card-body">
              <div class="card-meta-stack">
                <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">รายเดือน</span></span>
                <span class="card-meta-plan">โปรโมชั่น 4G+ Super Smart</span>
                <span>1099 ขึ้นไป</span>
              </div>
            </div>
            <a class="card-btn" href="{{ route('good-number', ['number' => '0645636463']) }}">ดูความหมาย</a>
            <a class="card-btn card-btn--buy" href="{{ route('book', ['number' => '0645636463', 'package' => 1099]) }}">สั่งซื้อ</a>
          </article>
          <article class="number-card">
            <div class="card-top">0644697923</div>
            <div class="card-body">
              <div class="card-meta-stack">
                <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">รายเดือน</span></span>
                <span class="card-meta-plan">โปรโมชั่น 4G+ Super Smart</span>
                <span>699 ขึ้นไป</span>
              </div>
            </div>
            <a class="card-btn" href="{{ route('good-number', ['number' => '0644697923']) }}">ดูความหมาย</a>
            <a class="card-btn card-btn--buy" href="{{ route('book', ['number' => '0644697923', 'package' => 699]) }}">สั่งซื้อ</a>
          </article>
          <article class="number-card">
            <div class="card-top">0646396926</div>
            <div class="card-body">
              <div class="card-meta-stack">
                <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">รายเดือน</span></span>
                <span class="card-meta-plan">โปรโมชั่น 4G+ Super Smart</span>
                <span>1099 ขึ้นไป</span>
              </div>
            </div>
            <a class="card-btn" href="{{ route('good-number', ['number' => '0646396926']) }}">ดูความหมาย</a>
            <a class="card-btn card-btn--buy" href="{{ route('book', ['number' => '0646396926', 'package' => 1099]) }}">สั่งซื้อ</a>
          </article>
          <article class="number-card">
            <div class="card-top">0645635964</div>
            <div class="card-body">
              <div class="card-meta-stack">
                <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">รายเดือน</span></span>
                <span class="card-meta-plan">โปรโมชั่น 4G+ Super Smart</span>
                <span>1099 ขึ้นไป</span>
              </div>
            </div>
            <a class="card-btn" href="{{ route('good-number', ['number' => '0645635964']) }}">ดูความหมาย</a>
            <a class="card-btn card-btn--buy" href="{{ route('book', ['number' => '0645635964', 'package' => 1099]) }}">สั่งซื้อ</a>
          </article>
          <article class="number-card">
            <div class="card-top">0645632656</div>
            <div class="card-body">
              <div class="card-meta-stack">
                <span class="card-tier card-tier--network"><span class="card-network-main">TRUE-DTAC</span><span class="card-network-suffix">รายเดือน</span></span>
                <span class="card-meta-plan">โปรโมชั่น 4G+ Super Smart</span>
                <span>1099 ขึ้นไป</span>
              </div>
            </div>
            <a class="card-btn" href="{{ route('good-number', ['number' => '0645632656']) }}">ดูความหมาย</a>
            <a class="card-btn card-btn--buy" href="{{ route('book', ['number' => '0645632656', 'package' => 1099]) }}">สั่งซื้อ</a>
          </article>
        </div>
      </section>

      <div class="evaluate-cta{{ $hasBadPair ? ' evaluate-cta--danger' : '' }}">
        <div>
          <h3>อยากได้เบอร์ที่เหมาะกับคุณมากขึ้น?</h3>
          <p>ให้ทีม Supernumber ช่วยคัดเลขมงคลที่ส่งเสริมชีวิตและงานของคุณได้ทันที</p>
        </div>
        <a class="cta-btn" href="{{ route('home') }}">กลับไปเลือกเบอร์</a>
      </div>
    </div>
  </section>
@endsection
