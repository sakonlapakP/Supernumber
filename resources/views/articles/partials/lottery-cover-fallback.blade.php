@php
  $wrapFigure = $wrapFigure ?? true;
  $drawDate = $lotteryResult->source_draw_date ?? $lotteryResult->draw_date;
  $thaiMonths = [
      1 => 'มกราคม',
      2 => 'กุมภาพันธ์',
      3 => 'มีนาคม',
      4 => 'เมษายน',
      5 => 'พฤษภาคม',
      6 => 'มิถุนายน',
      7 => 'กรกฎาคม',
      8 => 'สิงหาคม',
      9 => 'กันยายน',
      10 => 'ตุลาคม',
      11 => 'พฤศจิกายน',
      12 => 'ธันวาคม',
  ];
  $thaiDateLabel = $drawDate
      ? $drawDate->format('j') . ' ' . ($thaiMonths[(int) $drawDate->format('n')] ?? $drawDate->format('m')) . ' ' . ((int) $drawDate->format('Y') + 543)
      : '-';
  $updatedAtLabel = optional($lotteryResult->fetched_at)->timezone('Asia/Bangkok')->format('d/m/Y H:i') ?: now('Asia/Bangkok')->format('d/m/Y H:i');
  $prizes = $lotteryResult->relationLoaded('prizes') ? $lotteryResult->prizes : $lotteryResult->prizes()->get();
  $pickFirstPrizeNumber = static function (string $nameNeedle, string $fallback = '-') use ($prizes): string {
      $prize = $prizes->first(fn ($item) => str_contains((string) data_get($item, 'prize_name', ''), $nameNeedle));
      $number = trim((string) data_get($prize, 'prize_number', ''));

      return $number !== '' ? $number : $fallback;
  };
  $pickPrizeNumbers = static function (string $nameNeedle, int $limit) use ($prizes): array {
      return $prizes
          ->filter(fn ($item) => str_contains((string) data_get($item, 'prize_name', ''), $nameNeedle))
          ->pluck('prize_number')
          ->map(fn ($number) => trim((string) $number))
          ->filter()
          ->take($limit)
          ->values()
          ->all();
  };
  $firstPrize = $pickFirstPrizeNumber('รางวัลที่ 1');
  $frontThree = $pickPrizeNumbers('เลขหน้า 3 ตัว', 2);
  $backThree = $pickPrizeNumbers('เลขท้าย 3 ตัว', 2);
  $lastTwo = $pickFirstPrizeNumber('เลขท้าย 2 ตัว');
  $nearFirst = $pickPrizeNumbers('ข้างเคียง', 2);
  while (count($frontThree) < 2) {
      $frontThree[] = '-';
  }
  while (count($backThree) < 2) {
      $backThree[] = '-';
  }
  $nearFirstLabel = $nearFirst !== [] ? implode(' ', $nearFirst) : '-';
  $statusLabel = $lotteryResult->is_complete ? 'ข้อมูลครบแล้ว' : 'ผลรางวัลยังอัปเดตอยู่';
@endphp

@if ($wrapFigure)
  <figure class="article-detail__cover-wrap">
@endif
<section class="article-detail__lottery-fallback" aria-label="ภาพสรุปผลสลากกินแบ่งรัฐบาล {{ $thaiDateLabel }}">
  <div class="article-detail__lottery-fallback-shell">
    <div class="lottery-header">
      <div class="lottery-logo-box">S</div>
      <p class="lottery-brand-text">SUPERNUMBER</p>
    </div>
    
    <h2 class="lottery-main-title">ผลสลากกินแบ่งรัฐบาล</h2>
    <p class="lottery-date-label">งวดประจำวันที่ {{ $thaiDateLabel }}</p>

    <div class="lottery-prize-1-box">
      <span class="prize-1-title">รางวัลที่ 1</span>
      <strong class="prize-1-number">{{ $firstPrize }}</strong>
    </div>

    <p class="lottery-near-label">ข้างเคียงรางวัลที่ 1 : {{ $nearFirstLabel }}</p>

    <div class="lottery-prizes-grid">
      <div class="lottery-column">
        <h3 class="prize-sub-title">เลขหน้า 3 ตัว</h3>
        <div class="prize-sub-box-pair">
          <div class="prize-sub-box">{{ $frontThree[0] }}</div>
          <div class="prize-sub-box">{{ $frontThree[1] }}</div>
        </div>
      </div>

      <div class="lottery-column">
        <h3 class="prize-sub-title">เลขท้าย 3 ตัว</h3>
        <div class="prize-sub-box-pair">
          <div class="prize-sub-box">{{ $backThree[0] }}</div>
          <div class="prize-sub-box">{{ $backThree[1] }}</div>
        </div>
      </div>

      <div class="lottery-column lottery-column--last-two">
        <h3 class="prize-sub-title">เลขท้าย 2 ตัว</h3>
        <div class="prize-sub-box prize-sub-box--last-two">{{ $lastTwo }}</div>
      </div>
    </div>

    <div class="lottery-footer">
      <p class="lottery-footer-url">SUPERNUMBER.CO.TH</p>
      <p class="lottery-footer-info">Web : www.supernumber.co.th Tel : 0963232656, 0963232665 Line : @supernumber</p>
    </div>
  </div>
</section>
@if ($wrapFigure)
  </figure>
@endif
