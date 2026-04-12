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
    <p class="article-detail__lottery-brand">SUPERNUMBER</p>
    <h2>ผลสลากกินแบ่งรัฐบาล</h2>
    <p class="article-detail__lottery-date">งวดประจำวันที่ {{ $thaiDateLabel }}</p>

    <div class="article-detail__lottery-first-prize">
      <span>รางวัลที่ 1</span>
      <strong>{{ $firstPrize }}</strong>
    </div>

    <p class="article-detail__lottery-near">ข้างเคียงรางวัลที่ 1 : {{ $nearFirstLabel }}</p>

    <div class="article-detail__lottery-grid">
      <section class="article-detail__lottery-group">
        <h3>เลขหน้า 3 ตัว</h3>
        <div class="article-detail__lottery-box-grid">
          <div class="article-detail__lottery-box">{{ $frontThree[0] }}</div>
          <div class="article-detail__lottery-box">{{ $frontThree[1] }}</div>
        </div>
      </section>

      <section class="article-detail__lottery-group">
        <h3>เลขท้าย 3 ตัว</h3>
        <div class="article-detail__lottery-box-grid">
          <div class="article-detail__lottery-box">{{ $backThree[0] }}</div>
          <div class="article-detail__lottery-box">{{ $backThree[1] }}</div>
        </div>
      </section>

      <section class="article-detail__lottery-group article-detail__lottery-group--last-two">
        <h3>เลขท้าย 2 ตัว</h3>
        <div class="article-detail__lottery-box article-detail__lottery-box--last-two">{{ $lastTwo }}</div>
      </section>
    </div>

    <p class="article-detail__lottery-updated">อัปเดตล่าสุด {{ $updatedAtLabel }} น. ({{ $statusLabel }})</p>
  </div>
</section>
@if ($wrapFigure)
  </figure>
@endif
