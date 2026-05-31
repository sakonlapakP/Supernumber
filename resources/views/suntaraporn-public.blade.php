<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ผังที่นั่ง — วงสุนทราภรณ์</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Sarabun', sans-serif;
      background: #f0f0f0;
      min-height: 100vh;
      padding: 16px;
    }

    /* ─── Zone colors ─── */
    .z-yellow  { background: #FFEE32; color: #333; border-color: #e6d400; }
    .z-blue    { background: #7EC8E3; color: #1a1a1a; border-color: #5ab5d5; }
    .z-pink    { background: #F48FB1; color: #1a1a1a; border-color: #e06090; }
    .z-green   { background: #66BB6A; color: #fff; border-color: #4caa50; }
    .z-purple  { background: #CE93D8; color: #1a1a1a; border-color: #ba68c8; }
    .z-vip     { background: #9E9E9E; color: #fff; border-color: #757575; }
    .z-box_b   { background: #F48FB1; color: #1a1a1a; border-color: #e06090; }
    .z-wc      { background: #7EC8E3; color: #1a1a1a; border-color: #5ab5d5; opacity: 0.75; }
    .z-vvip-box { background: #fff; border: 2.5px solid #222; }

    /* ─── Seat ─── */
    .seat {
      width: 26px; height: 26px;
      border: 1.5px solid transparent;
      border-radius: 4px;
      font-size: 8px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: default;
      user-select: none;
      flex-shrink: 0;
      position: relative;
      transition: opacity .12s;
    }
    .seat.is-booked {
      background: #555 !important;
      border-color: #333 !important;
      color: #888 !important;
    }
    /* กำลังถูกถือโดย staff */
    .seat.is-selecting {
      background: #FF8F00 !important;
      border-color: #E65100 !important;
      color: #fff !important;
      animation: pulse-selecting 1.4s ease-in-out infinite;
    }
    .seat.is-selecting::after {
      content: '🔒';
      position: absolute;
      top: -6px;
      right: -6px;
      font-size: 10px;
      line-height: 1;
      pointer-events: none;
    }
    @keyframes pulse-selecting {
      0%, 100% { box-shadow: 0 0 0 0 rgba(255,143,0,.6); }
      50%       { box-shadow: 0 0 0 4px rgba(255,143,0,0); }
    }
    .seat.is-reserved { opacity: 0.75; }
    .seat-gap  { width: 10px; flex-shrink: 0; }
    .seat-gap-center {
      width: 14px; flex-shrink: 0;
      border-left: 3px solid #222;
      height: 28px; margin: 0;
    }

    /* ─── Row ─── */
    .row-wrap {
      display: flex; align-items: center;
      justify-content: center;
      width: 100%; margin-bottom: 2px;
    }
    .left-row-half {
      display: flex; align-items: center;
      justify-content: flex-end; flex: 1;
    }
    .right-row-half {
      display: flex; align-items: center;
      justify-content: flex-start; flex: 1;
    }
    .seats-half-left {
      display: flex; align-items: center;
      gap: 1.5px; justify-content: flex-end;
    }
    .seats-half-right {
      display: flex; align-items: center;
      gap: 1.5px; justify-content: flex-start;
    }
    .row-label {
      width: 24px; font-size: 11px; font-weight: 700;
      color: #555; text-align: center; flex-shrink: 0;
    }
    .box-cell {
      width: 110px; height: 28px;
      display: flex; align-items: center;
      position: relative; box-sizing: border-box; flex-shrink: 0;
    }
    .left-row-half .box-cell  { margin-right: auto; justify-content: flex-end; padding-right: 6px; }
    .right-row-half .box-cell { margin-left: auto; justify-content: flex-start; padding-left: 6px; }

    /* Box Borders */
    .box-border-top    { border-top: 1.5px solid #222; border-left: 1.5px solid #222; border-right: 1.5px solid #222; border-top-left-radius:6px; border-top-right-radius:6px; height:30px; margin-bottom:-2px; background:#fff; z-index:2; }
    .box-border-middle { border-left: 1.5px solid #222; border-right: 1.5px solid #222; height:30px; margin-bottom:-2px; background:#fff; z-index:2; }
    .box-border-bottom { border-bottom:1.5px solid #222; border-left:1.5px solid #222; border-right:1.5px solid #222; border-bottom-left-radius:6px; border-bottom-right-radius:6px; background:#fff; z-index:2; }
    .box-label-text    { position:absolute; top:-10px; font-size:8px; font-weight:700; color:#222; background:#fff; padding:0 4px; z-index:5; }
    .left-row-half .box-label-text  { right:6px; }
    .right-row-half .box-label-text { left:6px; }

    /* ♿ */
    .wc-badge { width:22px; height:22px; background:#1976D2; color:#fff; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:bold; box-shadow:0 1px 3px rgba(0,0,0,.2); flex-shrink:0; }
    .left-row-half .wc-badge  { margin-right:6px; }
    .right-row-half .wc-badge { margin-left:6px; }

    /* Chart */
    .chart-scroll { overflow-x: auto; padding-bottom: 8px; }
    .chart { min-width: 1140px; margin: 0 auto; }

    /* VIP + VVIP */
    .vip-area { display:flex; align-items:center; justify-content:center; gap:24px; margin-bottom:8px; padding:8px 0; }
    .vip-block { display:flex; flex-direction:column; gap:2px; align-items:center; }
    .vip-block .row-label-top { font-size:10px; font-weight:700; color:#555; margin-bottom:2px; }
    .vvip-box { width:240px; height:80px; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800; letter-spacing:2px; color:#222; border:3px solid #222; background:#fff; flex-shrink:0; }

    /* Stage */
    .stage-area { display:flex; justify-content:center; margin-top:10px; }
    .stage-box  { width:640px; height:56px; background:#e8e8e8; border:2px solid #aaa; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800; letter-spacing:4px; color:#555; }

    /* Header */
    .page-header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
    .page-title  { font-size:22px; font-weight:800; color:#1a1a1a; }
    .page-sub    { font-size:13px; color:#777; margin-top:3px; }

    /* Live badge */
    .live-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: #e53935; color: #fff;
      border-radius: 20px; padding: 4px 12px;
      font-size: 13px; font-weight: 700;
    }
    .live-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: #fff;
      animation: blink 1.2s ease-in-out infinite;
    }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

    /* Last updated */
    .last-updated { font-size:11px; color:#999; margin-top:4px; text-align:right; }

    /* Stats bar */
    .stats-bar {
      display:flex; gap:16px; flex-wrap:wrap;
      background:#fff; border-radius:10px;
      padding:10px 16px; margin-bottom:12px;
      box-shadow:0 1px 4px rgba(0,0,0,.1); font-size:13px;
    }
    .stat-item { display:flex; flex-direction:column; align-items:center; gap:2px; }
    .stat-num  { font-size:20px; font-weight:800; }
    .stat-lbl  { color:#777; font-size:11px; }

    /* Legend */
    .legend {
      display:flex; flex-wrap:wrap; gap:10px 20px;
      background:#fff; border-radius:10px;
      padding:12px 16px; margin-bottom:14px;
      box-shadow:0 1px 4px rgba(0,0,0,.1);
    }
    .legend-item  { display:flex; align-items:center; gap:6px; font-size:13px; }
    .legend-swatch { width:20px; height:20px; border-radius:3px; border:1.5px solid rgba(0,0,0,.15); flex-shrink:0; }

    /* Chart wrapper */
    .chart-card { background:#fff; border-radius:12px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,.1); }

    /* Progress bar */
    .progress-wrap { margin-bottom:12px; }
    .progress-label { display:flex; justify-content:space-between; font-size:12px; color:#777; margin-bottom:4px; }
    .progress-bar   { height:8px; border-radius:4px; background:#eee; overflow:hidden; }
    .progress-fill  { height:100%; border-radius:4px; background:#e53935; transition:width .5s ease; }

    @media (max-width: 640px) {
      body { padding: 8px; }
      .page-title { font-size: 18px; }
      .vvip-box { width:160px; height:60px; font-size:16px; }
    }
  </style>
</head>
<body>

{{-- ─── Header ───────────────────────────────────────────────── --}}
<div class="page-header">
  <div>
    <div class="page-title">🎵 ผังที่นั่ง วงสุนทราภรณ์</div>
    <div class="page-sub">อัพเดทแบบ real-time — สามารถดูสถานะที่นั่งได้ทันที</div>
  </div>
  <div style="text-align:right;">
    <div class="live-badge"><span class="live-dot"></span> LIVE</div>
    <div class="last-updated" id="last-updated">กำลังเชื่อมต่อ...</div>
  </div>
</div>

{{-- ─── Stats bar ─────────────────────────────────────────────── --}}
<div class="stats-bar">
  <div class="stat-item">
    <span class="stat-num" id="stat-total">542</span>
    <span class="stat-lbl">ที่นั่งทั้งหมด</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" id="stat-booked" style="color:#e53935">0</span>
    <span class="stat-lbl">จองแล้ว</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" id="stat-selecting" style="color:#FF8F00">0</span>
    <span class="stat-lbl">🔒 กำลังถูกถือ</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" id="stat-avail" style="color:#2e7d32">542</span>
    <span class="stat-lbl">ว่าง</span>
  </div>
</div>

{{-- Progress bar --}}
<div class="progress-wrap">
  <div class="progress-label">
    <span>ที่นั่งที่ถูกจองแล้ว</span>
    <span id="progress-pct">0%</span>
  </div>
  <div class="progress-bar"><div class="progress-fill" id="progress-fill" style="width:0%"></div></div>
</div>

{{-- ─── Legend ─────────────────────────────────────────────────── --}}
@php
  $legend = [
    ['zone'=>'vvip',   'label'=>'VVIP',       'bg'=>'#fff',    'border'=>'#222'],
    ['zone'=>'vip',    'label'=>'VIP',         'bg'=>'#9E9E9E', 'border'=>'#757575'],
    ['zone'=>'box_b',  'label'=>'BOX B',       'bg'=>'#F48FB1', 'border'=>'#e06090'],
    ['zone'=>'purple', 'label'=>'ม่วง',        'bg'=>'#CE93D8', 'border'=>'#ba68c8'],
    ['zone'=>'green',  'label'=>'เขียว',       'bg'=>'#66BB6A', 'border'=>'#4caa50'],
    ['zone'=>'pink',   'label'=>'ชมพู',        'bg'=>'#F48FB1', 'border'=>'#e06090'],
    ['zone'=>'blue',   'label'=>'ฟ้า',         'bg'=>'#7EC8E3', 'border'=>'#5ab5d5'],
    ['zone'=>'yellow', 'label'=>'เหลือง',      'bg'=>'#FFEE32', 'border'=>'#e6d400'],
    ['zone'=>'wc',     'label'=>'♿ ผู้พิการ', 'bg'=>'#7EC8E3', 'border'=>'#5ab5d5'],
  ];
@endphp
<div class="legend">
  @foreach ($legend as $item)
  <div class="legend-item">
    <div class="legend-swatch" style="background:{{ $item['bg'] }};border-color:{{ $item['border'] }}"></div>
    <span>
      {{ $item['label'] }}
      @if ($item['zone'] !== 'wc')
        — <strong>฿{{ number_format($prices[$item['zone']] ?? 0) }}</strong>
      @else
        (reserved)
      @endif
    </span>
  </div>
  @endforeach
  <div class="legend-item">
    <div class="legend-swatch" style="background:#555;border-color:#333;"></div>
    <span>จองแล้ว</span>
  </div>
  <div class="legend-item">
    <div class="legend-swatch" style="background:#FF8F00;border-color:#E65100;display:flex;align-items:center;justify-content:center;font-size:10px;">🔒</div>
    <span>กำลังถูกถือ</span>
  </div>
</div>

{{-- ─── Seating Chart ──────────────────────────────────────────── --}}
<div class="chart-card">
  <div class="chart-scroll">
    <div class="chart">

      {{-- VVIP + VIP --}}
      <div class="vip-area">
        {{-- VIP Left --}}
        <div class="vip-block">
          <div class="row-label-top">VIP</div>
          <div class="seats-line">
            @foreach (range(9,17) as $n)
              <div class="seat z-vip" data-key="VL_{{ $n }}">{{ $n }}</div>
            @endforeach
          </div>
          <div class="seats-line">
            @foreach (range(1,8) as $n)
              <div class="seat z-vip" data-key="VL_{{ $n }}">{{ $n }}</div>
            @endforeach
          </div>
        </div>

        {{-- VVIP --}}
        <div class="vvip-box">VVIP</div>

        {{-- VIP Right --}}
        <div class="vip-block">
          <div class="row-label-top">VIP</div>
          <div class="seats-line">
            @foreach (range(26,34) as $n)
              <div class="seat z-vip" data-key="VR_{{ $n }}">{{ $n }}</div>
            @endforeach
          </div>
          <div class="seats-line">
            @foreach (range(18,25) as $n)
              <div class="seat z-vip" data-key="VR_{{ $n }}">{{ $n }}</div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- ─── Main Rows ─── --}}
      @php
        $r = fn($s,$e) => range($s,$e);
        $rows = [
          ['U','yellow', [],      $r(1,6),   $r(7,13),  [],        null,                              null],
          ['T','yellow', [],      $r(1,11),  $r(12,23), [],        null,                              null],
          ['S','yellow', [],      $r(1,10),  $r(11,21), [],        ['k'=>'BOXC_14','n'=>14,'z'=>'wc'], ['k'=>'BOXF_15','n'=>15,'z'=>'wc']],
          ['R','blue',   [],      $r(1,10),  $r(11,21), [],        ['k'=>'BOXC_13','n'=>13,'z'=>'wc'], ['k'=>'BOXF_16','n'=>16,'z'=>'wc']],
          ['Q','blue',   $r(1,4), $r(5,14),  $r(15,24), $r(25,28), ['k'=>'BOXC_12','n'=>12,'z'=>'wc'], ['k'=>'BOXF_17','n'=>17,'z'=>'wc']],
          ['P','blue',   $r(1,5), $r(6,15),  $r(16,24), $r(25,29), ['k'=>'BOXC_11','n'=>11,'z'=>'wc'], ['k'=>'BOXF_18','n'=>18,'z'=>'wc']],
          ['N','pink',   $r(1,6), $r(7,15),  $r(16,24), $r(25,30), ['k'=>'BOXC_10','n'=>10,'z'=>'wc'], ['k'=>'BOXF_19','n'=>19,'z'=>'wc']],
          ['M','pink',   $r(1,6), $r(7,14),  $r(15,23), $r(24,29), ['k'=>'BOXB_9', 'n'=>9, 'z'=>'box_b'],['k'=>'BOXE_20','n'=>20,'z'=>'pink']],
          ['L','pink',   $r(1,7), $r(8,16),  $r(17,24), $r(25,31), ['k'=>'BOXB_8', 'n'=>8, 'z'=>'box_b'],['k'=>'BOXE_21','n'=>21,'z'=>'pink']],
          ['K','pink',   $r(1,8), $r(9,16),  $r(17,24), $r(25,32), ['k'=>'BOXB_7', 'n'=>7, 'z'=>'box_b'],['k'=>'BOXE_22','n'=>22,'z'=>'pink']],
          ['J','green',  $r(1,8), $r(9,16),  $r(17,24), $r(25,32), ['k'=>'BOXB_6', 'n'=>6, 'z'=>'box_b'],['k'=>'BOXE_23','n'=>23,'z'=>'pink']],
          ['H','green',  $r(1,8), $r(9,15),  $r(16,23), $r(24,31), null,                              null],
          ['G','green',  $r(1,8), $r(9,15),  $r(16,23), $r(24,31), ['k'=>'BOXA_5','n'=>5,'z'=>'green'],['k'=>'BOXD_24','n'=>24,'z'=>'green']],
          ['F','green',  $r(1,8), $r(9,15),  $r(16,23), $r(24,31), ['k'=>'BOXA_4','n'=>4,'z'=>'green'],['k'=>'BOXD_25','n'=>25,'z'=>'green']],
          ['E','purple', $r(1,8), $r(9,15),  $r(16,23), $r(24,31), ['k'=>'BOXA_3','n'=>3,'z'=>'green'],['k'=>'BOXD_26','n'=>26,'z'=>'green']],
          ['D','purple', $r(1,7), $r(8,14),  $r(15,22), $r(23,29), ['k'=>'BOXA_2','n'=>2,'z'=>'green'],['k'=>'BOXD_27','n'=>27,'z'=>'green']],
          ['C','purple', $r(1,7), $r(8,14),  $r(15,22), $r(23,29), ['k'=>'BOXA_1','n'=>1,'z'=>'green'],['k'=>'BOXD_28','n'=>28,'z'=>'green']],
          ['B','purple', $r(1,6), $r(7,13),  $r(14,21), $r(22,27), null,                              null],
          ['A','purple', $r(1,5), $r(6,12),  $r(13,20), $r(21,25), null,                              null],
        ];

        $bookedSet = array_flip($bookedSeats);
        $isBooked  = fn($key) => isset($bookedSet[$key]) ? 'is-booked' : '';
        $seatEl = function($key, $zone, $num, $reserved=false) use ($isBooked) {
            $cls   = 'seat z-'.$zone;
            $cls  .= $reserved ? ' is-reserved' : '';
            $cls  .= ' '.$isBooked($key);
            $title = $reserved ? ' title="ที่นั่งผู้พิการ (reserved)"' : '';
            return '<div class="'.$cls.'" data-key="'.$key.'"'.$title.'>'.$num.'</div>';
        };
      @endphp

      @foreach ($rows as [$rid, $zone, $left, $cl, $cr, $right, $boxL, $boxR])
      <div class="row-wrap">

        {{-- Left Side --}}
        <div class="left-row-half">
          @php
            $boxLClass = ''; $showLabelL = null;
            if ($boxL) {
              $k = $boxL['k'];
              if ($k==='BOXC_14') { $boxLClass='box-border-top'; $showLabelL='BOX C'; }
              elseif (in_array($k,['BOXC_13','BOXC_12','BOXC_11'])) { $boxLClass='box-border-middle'; }
              elseif ($k==='BOXC_10') { $boxLClass='box-border-bottom'; }
              elseif ($k==='BOXB_9')  { $boxLClass='box-border-top'; $showLabelL='BOX B'; }
              elseif (in_array($k,['BOXB_8','BOXB_7'])) { $boxLClass='box-border-middle'; }
              elseif ($k==='BOXB_6')  { $boxLClass='box-border-bottom'; }
              elseif ($k==='BOXA_5')  { $boxLClass='box-border-top'; $showLabelL='BOX A'; }
              elseif (in_array($k,['BOXA_4','BOXA_3','BOXA_2'])) { $boxLClass='box-border-middle'; }
              elseif ($k==='BOXA_1')  { $boxLClass='box-border-bottom'; }
            }
          @endphp
          <div class="box-cell {{ $boxLClass }}">
            @if ($boxL)
              @if ($showLabelL)<span class="box-label-text">{{ $showLabelL }}</span>@endif
              @if ($boxL['k']==='BOXC_12')<div class="wc-badge" title="ที่นั่งผู้พิการ">♿</div>@endif
              {!! $seatEl($boxL['k'], $boxL['z'], $boxL['n'], $boxL['z']==='wc') !!}
            @endif
          </div>
          <span class="row-label">{{ $rid }}</span>
          <div class="seats-half-left">
            @foreach ($left as $n){!! $seatEl($rid.'_'.$n, $zone, $n) !!}@endforeach
            @if (count($left)>0)<div class="seat-gap"></div>@endif
            @foreach ($cl as $n){!! $seatEl($rid.'_'.$n, $zone, $n) !!}@endforeach
          </div>
        </div>

        <div class="seat-gap-center"></div>

        {{-- Right Side --}}
        <div class="right-row-half">
          <div class="seats-half-right">
            @foreach ($cr as $n){!! $seatEl($rid.'_'.$n, $zone, $n) !!}@endforeach
            @if (count($right)>0)<div class="seat-gap"></div>@endif
            @foreach ($right as $n){!! $seatEl($rid.'_'.$n, $zone, $n) !!}@endforeach
          </div>
          <span class="row-label">{{ $rid }}</span>
          @php
            $boxRClass = ''; $showLabelR = null;
            if ($boxR) {
              $k = $boxR['k'];
              if ($k==='BOXF_15') { $boxRClass='box-border-top'; $showLabelR='BOX F'; }
              elseif (in_array($k,['BOXF_16','BOXF_17','BOXF_18'])) { $boxRClass='box-border-middle'; }
              elseif ($k==='BOXF_19') { $boxRClass='box-border-bottom'; }
              elseif ($k==='BOXE_20') { $boxRClass='box-border-top'; $showLabelR='BOX E'; }
              elseif (in_array($k,['BOXE_21','BOXE_22'])) { $boxRClass='box-border-middle'; }
              elseif ($k==='BOXE_23') { $boxRClass='box-border-bottom'; }
              elseif ($k==='BOXD_24') { $boxRClass='box-border-top'; $showLabelR='BOX D'; }
              elseif (in_array($k,['BOXD_25','BOXD_26','BOXD_27'])) { $boxRClass='box-border-middle'; }
              elseif ($k==='BOXD_28') { $boxRClass='box-border-bottom'; }
            }
          @endphp
          <div class="box-cell {{ $boxRClass }}">
            @if ($boxR)
              {!! $seatEl($boxR['k'], $boxR['z'], $boxR['n'], $boxR['z']==='wc') !!}
              @if ($boxR['k']==='BOXF_17')<div class="wc-badge" title="ที่นั่งผู้พิการ">♿</div>@endif
              @if ($showLabelR)<span class="box-label-text">{{ $showLabelR }}</span>@endif
            @endif
          </div>
        </div>

      </div>
      @endforeach

      {{-- Stage --}}
      <div class="stage-area">
        <div class="stage-box">STAGE</div>
      </div>

    </div>{{-- .chart --}}
  </div>{{-- .chart-scroll --}}
</div>{{-- .chart-card --}}

<div style="text-align:center;color:#bbb;font-size:11px;margin-top:12px;padding-bottom:20px;">
  ข้อมูลนี้อัพเดทแบบ real-time · ไม่สามารถจองผ่านหน้านี้ได้
</div>

<script>
const BOOKED    = new Set(@json($bookedSeats));
const SELECTING = new Set();
const TOTAL     = 542;

// ── Init ─────────────────────────────────────────────────────────
function init() {
  document.querySelectorAll('[data-key]').forEach(el => {
    if (BOOKED.has(el.dataset.key)) el.classList.add('is-booked');
  });
  updateStats();
}

// ── Stats ────────────────────────────────────────────────────────
function updateStats() {
  const booked    = BOOKED.size;
  const selecting = SELECTING.size;
  const avail     = TOTAL - booked - selecting;
  const pct       = Math.round((booked / TOTAL) * 100);

  document.getElementById('stat-booked').textContent    = booked;
  document.getElementById('stat-selecting').textContent = selecting;
  document.getElementById('stat-avail').textContent     = Math.max(0, avail);
  document.getElementById('progress-fill').style.width  = pct + '%';
  document.getElementById('progress-pct').textContent   = pct + '%';
}

function markLastUpdated() {
  const now = new Date();
  const hh  = String(now.getHours()).padStart(2,'0');
  const mm  = String(now.getMinutes()).padStart(2,'0');
  const ss  = String(now.getSeconds()).padStart(2,'0');
  document.getElementById('last-updated').textContent = 'อัพเดทล่าสุด ' + hh + ':' + mm + ':' + ss;
}

init();
</script>

{{-- ─── Pusher Real-time ───────────────────────────────────────── --}}
<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
(function () {
  const pusher  = new Pusher('{{ env("PUSHER_APP_KEY") }}', { cluster: '{{ env("PUSHER_APP_CLUSTER") }}' });
  const channel = pusher.subscribe('suntaraporn-concert');

  pusher.connection.bind('connected', function () {
    markLastUpdated();
  });

  channel.bind('seat-status-updated', function (data) {
    // จองแล้ว → เทา
    (data.booked_keys || []).forEach(function (key) {
      BOOKED.add(key);
      SELECTING.delete(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.remove('is-selecting');
        el.classList.add('is-booked');
        el.removeAttribute('title');
      });
    });

    // ยกเลิกจอง → ว่าง
    (data.freed_keys || []).forEach(function (key) {
      BOOKED.delete(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.remove('is-booked');
      });
    });

    // กำลังถูกถือ → ส้ม 🔒
    (data.selecting_keys || []).forEach(function (key) {
      if (BOOKED.has(key)) return;
      SELECTING.add(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.add('is-selecting');
        el.setAttribute('title', 'กำลังถูกเลือกอยู่');
      });
    });

    // ปล่อยออก → ว่าง
    (data.deselecting_keys || []).forEach(function (key) {
      SELECTING.delete(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        if (!BOOKED.has(key)) {
          el.classList.remove('is-selecting');
          el.removeAttribute('title');
        }
      });
    });

    updateStats();
    markLastUpdated();
  });
})();
</script>
</body>
</html>
