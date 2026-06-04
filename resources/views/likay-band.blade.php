<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>ผังที่นั่ง — ลิเกไลฟ์อินเดอะเธียเตอร์</title>
  <link rel="shortcut icon" type="image/x-icon" href="/favicon-v2.ico?v=supernumber-20260602">
  <link rel="icon" type="image/svg+xml" sizes="any" href="/favicon.svg?v=supernumber-20260602">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=supernumber-20260602">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=supernumber-20260602">
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=supernumber-20260602">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    .z-blue    { background: #4CAF50; color: #fff; border-color: #388E3C; }
    .z-pink    { background: #29B6F6; color: #1a1a1a; border-color: #039BE5; }
    .z-green   { background: #EF5350; color: #fff; border-color: #e53935; }
    .z-purple  { background: #AB47BC; color: #fff; border-color: #9C27B0; }
    .z-vip     { background: #FFEE32; color: #333; border-color: #e6d400; }
    .z-box_b   { background: #26C6DA; color: #1a1a1a; border-color: #00ACC1; }
    .z-box     { background: #4CAF50; color: #fff; border-color: #388E3C; }
    .z-wc      { background: #26C6DA; color: #1a1a1a; border-color: #00ACC1; cursor: default !important; opacity: 0.75; }
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
      cursor: pointer;
      user-select: none;
      flex-shrink: 0;
      transition: transform .12s, box-shadow .12s, opacity .12s;
      position: relative;
    }
    .seat:hover:not(.is-booked):not(.is-reserved):not(.is-selected):not(.is-selecting) {
      transform: scale(1.25);
      z-index: 10;
      box-shadow: 0 2px 8px rgba(0,0,0,.35);
    }
    .seat.is-booked {
      background: #555 !important;
      border-color: #333 !important;
      color: #888 !important;
      cursor: not-allowed;
    }
    .seat.is-selected {
      background: #1a1a2e !important;
      border-color: #000 !important;
      color: #fff !important;
      transform: scale(1.15);
      box-shadow: 0 2px 8px rgba(0,0,0,.4);
    }
    .seat.is-reserved {
      cursor: default;
    }
    .seat.is-selecting {
      background: #FF8F00 !important;
      border-color: #E65100 !important;
      color: #fff !important;
      cursor: not-allowed;
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
    .seat-gap  { width: 10px; flex-shrink: 0; }
    .seats-line { display: flex; align-items: center; gap: 1.5px; }
    .seat-gap-center {
      width: 14px;
      flex-shrink: 0;
      border-left: 3px solid #222;
      height: 28px;
      margin: 0;
    }

    .row-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      margin-bottom: 2px;
    }
    .left-row-half {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      flex: 1;
    }
    .right-row-half {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      flex: 1;
    }
    .seats-half-left {
      display: flex;
      align-items: center;
      gap: 1.5px;
      justify-content: flex-end;
    }
    .seats-half-right {
      display: flex;
      align-items: center;
      gap: 1.5px;
      justify-content: flex-start;
    }
    .row-label {
      width: 24px;
      font-size: 11px;
      font-weight: 700;
      color: #555;
      text-align: center;
      flex-shrink: 0;
    }
    .box-cell {
      width: 110px;
      height: 28px;
      display: flex;
      align-items: center;
      position: relative;
      box-sizing: border-box;
      flex-shrink: 0;
    }
    .left-row-half .box-cell {
      margin-right: auto;
      justify-content: flex-end;
      padding-right: 6px;
    }
    .right-row-half .box-cell {
      margin-left: auto;
      justify-content: flex-start;
      padding-left: 6px;
    }

    .box-border-top {
      border-top: 1.5px solid #222;
      border-left: 1.5px solid #222;
      border-right: 1.5px solid #222;
      border-top-left-radius: 6px;
      border-top-right-radius: 6px;
      height: 30px;
      margin-bottom: -2px;
      background: #fff;
      z-index: 2;
    }
    .box-border-middle {
      border-left: 1.5px solid #222;
      border-right: 1.5px solid #222;
      height: 30px;
      margin-bottom: -2px;
      background: #fff;
      z-index: 2;
    }
    .box-border-bottom {
      border-bottom: 1.5px solid #222;
      border-left: 1.5px solid #222;
      border-right: 1.5px solid #222;
      border-bottom-left-radius: 6px;
      border-bottom-right-radius: 6px;
      background: #fff;
      z-index: 2;
    }

    .box-label-text {
      position: absolute;
      top: -10px;
      font-size: 8px;
      font-weight: 700;
      color: #222;
      background: #fff;
      padding: 0 4px;
      z-index: 5;
    }
    .left-row-half .box-label-text { right: 6px; }
    .right-row-half .box-label-text { left: 6px; }

    .wc-badge {
      width: 22px;
      height: 22px;
      background: #1976D2;
      color: #fff;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: bold;
      box-shadow: 0 1px 3px rgba(0,0,0,0.2);
      flex-shrink: 0;
    }
    .left-row-half .wc-badge  { margin-right: 6px; }
    .right-row-half .wc-badge { margin-left: 6px; }

    .chart-scroll { overflow-x: auto; padding-bottom: 8px; }
    .chart { min-width: 1140px; margin: 0 auto; }

    .vip-area {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 24px;
      margin-bottom: 8px;
      padding: 8px 0;
    }
    .vip-block { display: flex; flex-direction: column; gap: 2px; align-items: center; }
    .vip-block .row-label-top { font-size: 10px; font-weight: 700; color: #555; margin-bottom: 2px; }
    .vvip-box {
      width: 240px;
      height: 80px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      font-weight: 800;
      letter-spacing: 2px;
      color: #222;
      border: 3px solid #222;
      background: #fff;
      flex-shrink: 0;
    }

    .stage-area { display: flex; justify-content: center; margin-top: 10px; }
    .stage-box {
      width: 640px;
      height: 56px;
      background: #e8e8e8;
      border: 2px solid #aaa;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      font-weight: 800;
      letter-spacing: 4px;
      color: #555;
    }

    .legend {
      display: flex;
      flex-wrap: wrap;
      gap: 10px 20px;
      background: #fff;
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 14px;
      box-shadow: 0 1px 4px rgba(0,0,0,.1);
    }
    .legend-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
    }
    .legend-swatch {
      width: 20px; height: 20px;
      border-radius: 3px;
      border: 1.5px solid rgba(0,0,0,.15);
      flex-shrink: 0;
    }

    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 12px;
    }
    .page-title { font-size: 22px; font-weight: 800; color: #1a1a1a; }

    .btn {
      padding: 8px 16px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      font-family: inherit;
      font-size: 14px;
      font-weight: 600;
      transition: opacity .15s;
    }
    .btn:hover { opacity: .85; }
    .btn-primary { background: #1a1a2e; color: #fff; }
    .btn-danger  { background: #e53935; color: #fff; }
    .btn-success { background: #2e7d32; color: #fff; }
    .btn-outline { background: transparent; border: 1.5px solid #ccc; color: #333; }

    .modal-backdrop {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,.55);
      z-index: 100;
      align-items: center;
      justify-content: center;
    }
    .modal-backdrop.open { display: flex; }
    .modal {
      background: #fff;
      border-radius: 12px;
      padding: 24px;
      width: 420px;
      max-width: 95vw;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 8px 32px rgba(0,0,0,.25);
    }
    .modal h2 { font-size: 18px; font-weight: 700; margin-bottom: 16px; }

    .price-row {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
      gap: 10px;
    }
    .price-row label {
      display: flex;
      align-items: center;
      gap: 6px;
      width: 160px;
      font-size: 14px;
      font-weight: 500;
      flex-shrink: 0;
    }
    .price-row input {
      flex: 1;
      border: 1.5px solid #ddd;
      border-radius: 6px;
      padding: 6px 10px;
      font-size: 14px;
      font-family: inherit;
      text-align: right;
    }

    .form-group { margin-bottom: 14px; }
    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #444;
      margin-bottom: 5px;
    }
    .form-group input[type="text"],
    .form-group input[type="tel"],
    .form-group input[type="file"] {
      width: 100%;
      border: 1.5px solid #ddd;
      border-radius: 7px;
      padding: 9px 12px;
      font-size: 14px;
      font-family: inherit;
      outline: none;
      transition: border-color .15s;
    }
    .form-group input:focus { border-color: #1a1a2e; }
    .form-group input[type="file"] { padding: 7px 10px; }

    .selected-seats-list {
      background: #f5f5f5;
      border-radius: 8px;
      padding: 10px 14px;
      margin-bottom: 16px;
      font-size: 13px;
    }
    .selected-seats-list strong { display: block; margin-bottom: 6px; font-size: 14px; }
    .seat-chip {
      display: inline-block;
      background: #1a1a2e;
      color: #fff;
      border-radius: 4px;
      padding: 2px 8px;
      font-size: 12px;
      font-weight: 600;
      margin: 2px;
    }
    .booking-total {
      font-size: 15px;
      font-weight: 700;
      color: #1a1a2e;
      margin-top: 8px;
    }

    .modal-footer { display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }

    .summary {
      background: #fff;
      border-radius: 10px;
      padding: 14px 16px;
      margin-top: 14px;
      box-shadow: 0 1px 4px rgba(0,0,0,.1);
    }
    .summary h3 { font-size: 15px; font-weight: 700; margin-bottom: 8px; }
    .summary-stats { display: flex; gap: 24px; flex-wrap: wrap; font-size: 14px; }
    .summary-total { font-size: 18px; font-weight: 800; color: #1a1a2e; }
    .sel-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .sel-tag {
      background: #f0f0f0;
      border-radius: 4px;
      padding: 2px 8px;
      font-size: 12px;
      font-weight: 600;
    }

    .stats-bar {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      background: #fff;
      border-radius: 10px;
      padding: 10px 16px;
      margin-bottom: 12px;
      box-shadow: 0 1px 4px rgba(0,0,0,.1);
      font-size: 13px;
    }
    .stat-item { display: flex; flex-direction: column; align-items: center; gap: 2px; }
    .stat-num  { font-size: 20px; font-weight: 800; }
    .stat-lbl  { color: #777; font-size: 11px; }

    .detail-row {
      display: flex;
      gap: 8px;
      margin-bottom: 10px;
      font-size: 14px;
    }
    .detail-label {
      width: 110px;
      font-weight: 600;
      color: #666;
      flex-shrink: 0;
    }
    .detail-val { color: #1a1a1a; word-break: break-all; }
    .slip-img {
      max-width: 100%;
      max-height: 260px;
      border-radius: 8px;
      border: 1px solid #ddd;
      margin-top: 6px;
      cursor: zoom-in;
    }
    .seats-group { display: flex; flex-wrap: wrap; gap: 4px; }
    .btn-cancel-booking {
      background: #e53935;
      color: #fff;
      border: none;
      border-radius: 7px;
      padding: 8px 18px;
      font-size: 14px;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
    }
    .btn-cancel-booking:hover { opacity: .85; }
    .detail-divider { border: none; border-top: 1px solid #eee; margin: 12px 0; }

    .float-book-btn {
      display: none;
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 50;
      padding: 14px 24px;
      font-size: 15px;
      border-radius: 50px;
      box-shadow: 0 4px 16px rgba(0,0,0,.25);
    }
    .float-book-btn.visible { display: flex; align-items: center; gap: 8px; }

    @media (max-width: 640px) {
      body { padding: 8px; }
      .page-title { font-size: 18px; }
      .vvip-box { width: 160px; height: 60px; font-size: 16px; }
    }
  </style>
</head>
<body>

{{-- ─── Price Edit Modal ─────────────────────────────────── --}}
<div class="modal-backdrop" id="priceModal">
  <div class="modal">
    <h2>✏️ แก้ไขราคาบัตร</h2>
    @php
      $zoneLabels = [
        'vvip'   => ['Control',                '#fff',    '#222',    '#222'],
        'purple' => ['ม่วง',                   '#AB47BC', '#fff',    '#9C27B0'],
        'green'  => ['แดง',                    '#EF5350', '#fff',    '#e53935'],
        'pink'   => ['ฟ้าอ่อน',                '#29B6F6', '#1a1a1a', '#039BE5'],
        'blue'   => ['เขียว',                  '#4CAF50', '#fff',    '#388E3C'],
        'box'    => ['BOX A-F',                '#4CAF50', '#fff',    '#388E3C'],
        'yellow' => ['เหลือง',                 '#FFEE32', '#333',    '#e6d400'],
      ];
    @endphp
    @foreach ($zoneLabels as $key => [$label, $bg, $fg, $border])
    <div class="price-row">
      <label>
        <span class="legend-swatch" style="background:{{ $bg }};border-color:{{ $border }}"></span>
        {{ $label }}
      </label>
      <input type="number" id="price_{{ $key }}" value="{{ $prices[$key] ?? 0 }}" min="0" step="100">
    </div>
    @endforeach
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closePriceModal()">ยกเลิก</button>
      <button class="btn btn-primary" onclick="savePrices()">บันทึก</button>
    </div>
  </div>
</div>

{{-- ─── Booking Modal ──────────────────────────────────────── --}}
<div class="modal-backdrop" id="bookingModal">
  <div class="modal">
    <h2>🎟️ จองที่นั่ง</h2>

    <div class="selected-seats-list">
      <strong>ที่นั่งที่เลือก</strong>
      <div id="selected-chips"></div>
      <div class="booking-total">ยอดรวม: ฿<span id="booking-total-price">0</span></div>
    </div>

    <form id="bookingForm" enctype="multipart/form-data">
      @csrf
      <div style="display:flex;gap:10px;">
        <div class="form-group" style="flex:1;">
          <label>ชื่อ <span style="color:#e53935">*</span></label>
          <input type="text" name="first_name" id="inp-first-name" placeholder="ชื่อ" required>
        </div>
        <div class="form-group" style="flex:1;">
          <label>นามสกุล <span style="color:#e53935">*</span></label>
          <input type="text" name="last_name" id="inp-last-name" placeholder="นามสกุล" required>
        </div>
      </div>
      <div class="form-group">
        <label>เบอร์ติดต่อ <span style="color:#e53935">*</span></label>
        <input type="tel" name="phone" id="inp-phone" placeholder="0812345678" required>
      </div>
      <div class="form-group">
        <label>อัพโหลด Slip โอนเงิน</label>
        <input type="file" name="slip" id="inp-slip" accept="image/jpeg,image/png,application/pdf">
        <div style="font-size:12px;color:#999;margin-top:4px;">JPG / PNG / PDF ขนาดไม่เกิน 5 MB</div>
      </div>
    </form>

    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeBookingModal()">ยกเลิก</button>
      <button class="btn btn-success" id="confirmBookBtn" onclick="confirmBooking()">ยืนยันการจอง</button>
    </div>
  </div>
</div>

{{-- ─── Booking Detail Modal ───────────────────────────────── --}}
<div class="modal-backdrop" id="detailModal">
  <div class="modal" style="width:460px;">
    <h2>📋 ข้อมูลการจอง</h2>
    <div id="detail-loading" style="text-align:center;padding:20px;color:#999;">กำลังโหลด...</div>
    <div id="detail-body" style="display:none;">
      <div class="detail-row">
        <span class="detail-label">ที่นั่งทั้งหมด</span>
        <span class="detail-val"><div class="seats-group" id="det-seats"></div></span>
      </div>
      <hr class="detail-divider">
      <div class="detail-row">
        <span class="detail-label">ชื่อ-นามสกุล</span>
        <span class="detail-val" id="det-name"></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">เบอร์ติดต่อ</span>
        <span class="detail-val" id="det-phone"></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">ผู้รับจอง</span>
        <span class="detail-val" id="det-booker"></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">ยอดรวม</span>
        <span class="detail-val" id="det-total"></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">วันที่จอง</span>
        <span class="detail-val" id="det-date"></span>
      </div>
      <div id="det-slip-wrap">
        <hr class="detail-divider">
        <div style="font-size:13px;font-weight:600;color:#666;margin-bottom:6px;">Slip โอนเงิน</div>
        <img id="det-slip-img" class="slip-img" src="" alt="slip" style="display:none;" onclick="window.open(this.src,'_blank')">
        <a id="det-slip-link" href="#" target="_blank" style="font-size:13px;display:none;">📄 ดูไฟล์ Slip (PDF)</a>
      </div>
    </div>
    <div class="modal-footer" style="justify-content:space-between;align-items:center;">
      <div id="det-cancel-wrap" style="display:none;">
        <button class="btn-cancel-booking" id="det-cancel-btn" onclick="cancelBooking()">🗑 ยกเลิกการจอง</button>
      </div>
      <div style="margin-left:auto;">
        <button class="btn btn-outline" onclick="closeDetailModal()">ปิด</button>
      </div>
    </div>
  </div>
</div>

{{-- ─── Main Page ─────────────────────────────────────────── --}}
<div class="page-header">
  <div>
    <div class="page-title">🎭 ผังที่นั่ง ลิเกไลฟ์อินเดอะเธียเตอร์</div>
    <div style="font-size:13px;color:#777;margin-top:2px;">สวัสดี, {{ $user->name }}</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <a href="{{ route('likay.bookings') }}" class="btn btn-outline">📋 รายการจอง</a>
    @if ($user->role === 'manager')
    <button class="btn btn-outline" onclick="if(confirm('รีเซ็ตที่นั่งทั้งหมด?')) resetAllSeats()">🔄 รีเซ็ต</button>
    <button class="btn btn-primary" onclick="openPriceModal()">✏️ แก้ไขราคา</button>
    @endif
    <form method="POST" action="{{ route('likay.logout') }}" style="margin:0;">
      @csrf
      <button type="submit" class="btn btn-outline">ออกจากระบบ</button>
    </form>
  </div>
</div>

<div class="stats-bar">
  <div class="stat-item">
    <span class="stat-num" id="stat-total">{{ $totalSeats }}</span>
    <span class="stat-lbl">ที่นั่งทั้งหมด</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" id="stat-booked" style="color:#e53935">0</span>
    <span class="stat-lbl">จองแล้ว</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" id="stat-avail" style="color:#2e7d32">{{ $totalSeats }}</span>
    <span class="stat-lbl">ว่าง</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" id="stat-selected" style="color:#1a1a2e">0</span>
    <span class="stat-lbl">เลือกแล้ว</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" id="stat-selecting" style="color:#FF8F00">0</span>
    <span class="stat-lbl">🔒 ถูกถือ</span>
  </div>
</div>

@php
  $legend = [
    ['zone'=>'vvip',   'label'=>'Control',            'bg'=>'#fff',    'border'=>'#222'],
    ['zone'=>'purple', 'label'=>'ม่วง',              'bg'=>'#AB47BC', 'border'=>'#9C27B0'],
    ['zone'=>'green',  'label'=>'แดง',               'bg'=>'#EF5350', 'border'=>'#e53935'],
    ['zone'=>'pink',   'label'=>'ฟ้าอ่อน',           'bg'=>'#29B6F6', 'border'=>'#039BE5'],
    ['zone'=>'blue',   'label'=>'เขียว',              'bg'=>'#4CAF50', 'border'=>'#388E3C'],
    ['zone'=>'box',    'label'=>'BOX A-F',            'bg'=>'#4CAF50', 'border'=>'#388E3C'],
    ['zone'=>'yellow', 'label'=>'เหลือง',             'bg'=>'#FFEE32', 'border'=>'#e6d400'],
  ];
@endphp
<div class="legend">
  @foreach ($legend as $item)
  <div class="legend-item">
    <div class="legend-swatch" style="background:{{ $item['bg'] }};border-color:{{ $item['border'] }}"></div>
    <span>
      {{ $item['label'] }}
      @if ($item['zone'] !== 'vvip')
        — <strong id="legend_{{ $item['zone'] }}">฿{{ number_format($prices[$item['zone']] ?? 0) }}</strong>
      @endif
    </span>
  </div>
  @endforeach
  <div class="legend-item">
    <div class="legend-swatch" style="background:#555;border-color:#333;"></div>
    <span>จองแล้ว</span>
  </div>
  <div class="legend-item">
    <div class="legend-swatch" style="background:#1a1a2e;border-color:#000;"></div>
    <span>เลือกอยู่ (ฉัน)</span>
  </div>
  <div class="legend-item">
    <div class="legend-swatch" style="background:#FF8F00;border-color:#E65100;display:flex;align-items:center;justify-content:center;font-size:11px;">🔒</div>
    <span>กำลังถูกเลือกอยู่ (ผู้อื่น)</span>
  </div>
</div>

<div style="background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.1);">
  <div class="chart-scroll">
    <div class="chart">

      <div style="display:flex;align-items:stretch;justify-content:center;gap:8px;margin-bottom:8px;padding:8px 0;">

        <div style="display:flex;flex-direction:column;gap:2px;align-items:flex-end;">
          <div style="display:flex;align-items:center;gap:4px;">
            <span class="row-label">W</span>
            <div class="seats-line">
              @foreach (range(1,9) as $n)
                <div class="seat z-yellow" data-key="W_{{ $n }}" data-zone="yellow" onclick="toggleSeat(this)">{{ $n }}</div>
              @endforeach
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:4px;">
            <span class="row-label">V</span>
            <div class="seats-line">
              @foreach (range(1,8) as $n)
                <div class="seat z-yellow" data-key="V_{{ $n }}" data-zone="yellow" onclick="toggleSeat(this)">{{ $n }}</div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="vvip-box" style="height:auto;align-self:stretch;">Control</div>

        <div style="display:flex;flex-direction:column;gap:2px;align-items:flex-start;">
          <div style="display:flex;align-items:center;gap:4px;">
            <div class="seats-line">
              @foreach (range(10,18) as $n)
                <div class="seat z-yellow" data-key="W_{{ $n }}" data-zone="yellow" onclick="toggleSeat(this)">{{ $n }}</div>
              @endforeach
            </div>
            <span class="row-label">W</span>
          </div>
          <div style="display:flex;align-items:center;gap:4px;">
            <div class="seats-line">
              @foreach (range(9,16) as $n)
                <div class="seat z-yellow" data-key="V_{{ $n }}" data-zone="yellow" onclick="toggleSeat(this)">{{ $n }}</div>
              @endforeach
            </div>
            <span class="row-label">V</span>
          </div>
        </div>

      </div>

      @php
        $r = fn($s,$e) => range($s,$e);
        $rows = [
          ['U','yellow', [],      $r(1,6),   $r(7,13),  [],        null,                              null],
          ['T','yellow', [],      $r(1,11),  $r(12,23), [],        null,                              null],
          ['S','blue',   [],      $r(1,10),  $r(11,21), [],        ['k'=>'BOXC_14','n'=>14,'z'=>'box'], ['k'=>'BOXF_15','n'=>15,'z'=>'box']],
          ['R','blue',   [],      $r(1,10),  $r(11,21), [],        ['k'=>'BOXC_13','n'=>13,'z'=>'box'], ['k'=>'BOXF_16','n'=>16,'z'=>'box']],
          ['Q','blue',   $r(1,4), $r(5,14),  $r(15,24), $r(25,28), ['k'=>'BOXC_12','n'=>12,'z'=>'box'], ['k'=>'BOXF_17','n'=>17,'z'=>'box']],
          ['P','blue',   $r(1,5), $r(6,15),  $r(16,24), $r(25,29), ['k'=>'BOXC_11','n'=>11,'z'=>'box'], ['k'=>'BOXF_18','n'=>18,'z'=>'box']],
          ['N','pink',   $r(1,6), $r(7,15),  $r(16,24), $r(25,30), ['k'=>'BOXC_10','n'=>10,'z'=>'box'], ['k'=>'BOXF_19','n'=>19,'z'=>'box']],
          ['M','pink',   $r(1,6), $r(7,14),  $r(15,23), $r(24,29), ['k'=>'BOXB_9', 'n'=>9, 'z'=>'box'], ['k'=>'BOXE_20','n'=>20,'z'=>'box']],
          ['L','pink',   $r(1,7), $r(8,16),  $r(17,24), $r(25,31), ['k'=>'BOXB_8', 'n'=>8, 'z'=>'box'], ['k'=>'BOXE_21','n'=>21,'z'=>'box']],
          ['K','pink',   $r(1,8), $r(9,16),  $r(17,24), $r(25,32), ['k'=>'BOXB_7', 'n'=>7, 'z'=>'box'], ['k'=>'BOXE_22','n'=>22,'z'=>'box']],
          ['J','pink',   $r(1,8), $r(9,16),  $r(17,24), $r(25,32), ['k'=>'BOXB_6', 'n'=>6, 'z'=>'box'], ['k'=>'BOXE_23','n'=>23,'z'=>'box']],
          ['H','green',  $r(1,8), $r(9,15),  $r(16,23), $r(24,31), null,                               null],
          ['G','green',  $r(1,8), $r(9,15),  $r(16,23), $r(24,31), ['k'=>'BOXA_5','n'=>5,'z'=>'box'],  ['k'=>'BOXD_24','n'=>24,'z'=>'box']],
          ['F','green',  $r(1,8), $r(9,15),  $r(16,23), $r(24,31), ['k'=>'BOXA_4','n'=>4,'z'=>'box'],  ['k'=>'BOXD_25','n'=>25,'z'=>'box']],
          ['E','green',  $r(1,8), $r(9,15),  $r(16,23), $r(24,31), ['k'=>'BOXA_3','n'=>3,'z'=>'box'],  ['k'=>'BOXD_26','n'=>26,'z'=>'box']],
          ['D','green',  $r(1,7), $r(8,14),  $r(15,22), $r(23,29), ['k'=>'BOXA_2','n'=>2,'z'=>'box'],  ['k'=>'BOXD_27','n'=>27,'z'=>'box']],
          ['C','purple', $r(1,7), $r(8,14),  $r(15,22), $r(23,29), ['k'=>'BOXA_1','n'=>1,'z'=>'box'],  ['k'=>'BOXD_28','n'=>28,'z'=>'box']],
          ['B','purple', $r(1,6), $r(7,13),  $r(14,21), $r(22,27), null,                              null],
          ['A','purple', $r(1,5), $r(6,12),  $r(13,20), $r(21,25), null,                              null],
        ];

        $bookedSet = array_flip($bookedSeats);
        $isBooked = fn($key) => isset($bookedSet[$key]) ? 'is-booked' : '';
        $seatEl = function($key, $zone, $num, $reserved=false) use ($isBooked) {
            $cls  = 'seat z-'.$zone;
            $cls .= $reserved ? ' is-reserved' : '';
            $cls .= ' '.$isBooked($key);
            $click = $reserved ? '' : ' onclick="toggleSeat(this)"';
            $title = $reserved ? ' title="ที่นั่งผู้พิการ (reserved)"' : '';
            return '<div class="'.$cls.'" data-key="'.$key.'" data-zone="'.$zone.'"'.$click.$title.'>'.$num.'</div>';
        };
      @endphp

      @foreach ($rows as [$rid, $zone, $left, $cl, $cr, $right, $boxL, $boxR])
      <div class="row-wrap">

        <div class="left-row-half">
          @php
            $boxLClass = '';
            $showLabelL = null;
            if ($boxL) {
                $k = $boxL['k'];
                if ($k === 'BOXC_14') { $boxLClass = 'box-border-top'; $showLabelL = 'BOX C'; }
                elseif (in_array($k, ['BOXC_13', 'BOXC_12', 'BOXC_11'])) { $boxLClass = 'box-border-middle'; }
                elseif ($k === 'BOXC_10') { $boxLClass = 'box-border-bottom'; }
                elseif ($k === 'BOXB_9') { $boxLClass = 'box-border-top'; $showLabelL = 'BOX B'; }
                elseif (in_array($k, ['BOXB_8', 'BOXB_7'])) { $boxLClass = 'box-border-middle'; }
                elseif ($k === 'BOXB_6') { $boxLClass = 'box-border-bottom'; }
                elseif ($k === 'BOXA_5') { $boxLClass = 'box-border-top'; $showLabelL = 'BOX A'; }
                elseif (in_array($k, ['BOXA_4', 'BOXA_3', 'BOXA_2'])) { $boxLClass = 'box-border-middle'; }
                elseif ($k === 'BOXA_1') { $boxLClass = 'box-border-bottom'; }
            }
          @endphp

          <div class="box-cell {{ $boxLClass }}">
            @if ($boxL)
              @if ($showLabelL)
                <span class="box-label-text">{{ $showLabelL }}</span>
              @endif
              @if ($boxL['k'] === 'BOXC_12')
                <div class="wc-badge" title="ที่นั่งผู้พิการ">♿</div>
              @endif
              {!! $seatEl($boxL['k'], $boxL['z'], $boxL['n'], $boxL['z']==='wc') !!}
            @endif
          </div>

          <span class="row-label">{{ $rid }}</span>

          <div class="seats-half-left">
            @foreach ($left as $n)
              {!! $seatEl($rid.'_'.$n, $zone, $n) !!}
            @endforeach
            @if (count($left) > 0)<div class="seat-gap"></div>@endif
            @foreach ($cl as $n)
              {!! $seatEl($rid.'_'.$n, $zone, $n) !!}
            @endforeach
          </div>
        </div>

        <div class="seat-gap-center"></div>

        <div class="right-row-half">
          <div class="seats-half-right">
            @foreach ($cr as $n)
              {!! $seatEl($rid.'_'.$n, $zone, $n) !!}
            @endforeach
            @if (count($right) > 0)<div class="seat-gap"></div>@endif
            @foreach ($right as $n)
              {!! $seatEl($rid.'_'.$n, $zone, $n) !!}
            @endforeach
          </div>

          <span class="row-label">{{ $rid }}</span>

          @php
            $boxRClass = '';
            $showLabelR = null;
            if ($boxR) {
                $k = $boxR['k'];
                if ($k === 'BOXF_15') { $boxRClass = 'box-border-top'; $showLabelR = 'BOX F'; }
                elseif (in_array($k, ['BOXF_16', 'BOXF_17', 'BOXF_18'])) { $boxRClass = 'box-border-middle'; }
                elseif ($k === 'BOXF_19') { $boxRClass = 'box-border-bottom'; }
                elseif ($k === 'BOXE_20') { $boxRClass = 'box-border-top'; $showLabelR = 'BOX E'; }
                elseif (in_array($k, ['BOXE_21', 'BOXE_22'])) { $boxRClass = 'box-border-middle'; }
                elseif ($k === 'BOXE_23') { $boxRClass = 'box-border-bottom'; }
                elseif ($k === 'BOXD_24') { $boxRClass = 'box-border-top'; $showLabelR = 'BOX D'; }
                elseif (in_array($k, ['BOXD_25', 'BOXD_26', 'BOXD_27'])) { $boxRClass = 'box-border-middle'; }
                elseif ($k === 'BOXD_28') { $boxRClass = 'box-border-bottom'; }
            }
          @endphp

          <div class="box-cell {{ $boxRClass }}">
            @if ($boxR)
              {!! $seatEl($boxR['k'], $boxR['z'], $boxR['n'], $boxR['z']==='wc') !!}
              @if ($boxR['k'] === 'BOXF_17')
                <div class="wc-badge" title="ที่นั่งผู้พิการ">♿</div>
              @endif
              @if ($showLabelR)
                <span class="box-label-text">{{ $showLabelR }}</span>
              @endif
            @endif
          </div>
        </div>

      </div>
      @endforeach

      <div class="stage-area">
        <div class="stage-box">STAGE</div>
      </div>

    </div>
  </div>
</div>

<div class="summary">
  <h3>ที่นั่งที่เลือก</h3>
  <div class="summary-stats">
    <div>เลือกแล้ว: <strong id="sum-selected">0</strong> ที่นั่ง</div>
    <div>ยอดรวม: <span class="summary-total">฿<span id="sum-total">0</span></span></div>
  </div>
  <div class="sel-tags" id="sel-tags">
    <span style="color:#999;font-size:13px;">คลิกที่นั่งที่ต้องการ แล้วกดปุ่มจอง</span>
  </div>
</div>

<button class="btn btn-success float-book-btn" id="floatBookBtn" onclick="openBookingModal()">
  🎟️ จองที่นั่งที่เลือก (<span id="float-count">0</span>)
</button>

<script>
const CSRF             = document.querySelector('meta[name="csrf-token"]').content;
const BOOKED           = new Set(@json($bookedSeats));
const PRICES           = @json($prices);
const SELECTED         = new Map();
const SELECTING_OTHERS = new Set();

function init() {
  document.querySelectorAll('[data-key]').forEach(el => {
    if (BOOKED.has(el.dataset.key)) el.classList.add('is-booked');
  });
  updateStats();
  updateSummary();
}

function toggleSeat(el) {
  if (el.classList.contains('is-reserved')) return;
  if (el.classList.contains('is-selecting')) return;

  if (el.classList.contains('is-booked')) {
    openDetailModal(el.dataset.key);
    return;
  }

  const key  = el.dataset.key;
  const zone = el.dataset.zone;

  if (SELECTED.has(key)) {
    SELECTED.delete(key);
    el.classList.remove('is-selected');
    broadcastDeselect([key]);
  } else {
    SELECTED.set(key, zone);
    el.classList.add('is-selected');
    broadcastSelect([key]);
  }

  updateStats();
  updateSummary();
  updateFloatBtn();
}

async function broadcastSelect(keys) {
  try {
    await fetch('/LikayLiveInTheater/select', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ seat_keys: keys })
    });
  } catch {}
}

async function broadcastDeselect(keys) {
  try {
    await fetch('/LikayLiveInTheater/deselect', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ seat_keys: keys })
    });
  } catch {}
}

window.addEventListener('beforeunload', function () {
  if (SELECTED.size === 0) return;
  const fd = new FormData();
  fd.append('_token', CSRF);
  [...SELECTED.keys()].forEach(k => fd.append('seat_keys[]', k));
  navigator.sendBeacon('/LikayLiveInTheater/deselect', fd);
});

function openBookingModal() {
  if (SELECTED.size === 0) return;

  const chips = document.getElementById('selected-chips');
  chips.innerHTML = [...SELECTED.keys()].map(k =>
    `<span class="seat-chip">${k}</span>`
  ).join('');

  let total = 0;
  SELECTED.forEach(zone => { total += PRICES[zone] || 0; });
  document.getElementById('booking-total-price').textContent = total.toLocaleString('th-TH');

  document.getElementById('bookingForm').reset();
  document.getElementById('bookingModal').classList.add('open');
}

function closeBookingModal() {
  document.getElementById('bookingModal').classList.remove('open');
}

async function confirmBooking() {
  const form    = document.getElementById('bookingForm');
  const inputs  = form.querySelectorAll('[required]');
  for (const inp of inputs) {
    if (!inp.value.trim()) { inp.focus(); alert('กรุณากรอกข้อมูลให้ครบถ้วน'); return; }
  }

  const btn = document.getElementById('confirmBookBtn');
  btn.disabled = true;
  btn.textContent = 'กำลังบันทึก...';

  const fd = new FormData();
  fd.append('_token', CSRF);
  [...SELECTED.entries()].forEach(([k]) => {
    fd.append('seat_keys[]', k);
  });
  fd.append('first_name', document.getElementById('inp-first-name').value.trim());
  fd.append('last_name',  document.getElementById('inp-last-name').value.trim());
  fd.append('phone',      document.getElementById('inp-phone').value.trim());
  const slipFile = document.getElementById('inp-slip').files[0];
  if (slipFile) fd.append('slip', slipFile);

  try {
    const res  = await fetch('/LikayLiveInTheater/book', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      SELECTED.forEach((zone, key) => {
        BOOKED.add(key);
        document.querySelectorAll(`[data-key="${key}"]`).forEach(el => {
          el.classList.remove('is-selected');
          el.classList.add('is-booked');
        });
      });
      SELECTED.clear();
      updateStats();
      updateSummary();
      updateFloatBtn();
      closeBookingModal();
      alert('จองสำเร็จ!');
    } else {
      alert(data.error || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
    }
  } catch {
    alert('เกิดข้อผิดพลาด กรุณาลองใหม่');
  } finally {
    btn.disabled = false;
    btn.textContent = 'ยืนยันการจอง';
  }
}

const IS_MANAGER = {{ $user->role === 'manager' ? 'true' : 'false' }};
let currentBookingId = null;

async function openDetailModal(seatKey) {
  currentBookingId = null;
  document.getElementById('detail-loading').style.display = 'block';
  document.getElementById('detail-body').style.display    = 'none';
  document.getElementById('detailModal').classList.add('open');

  try {
    const res  = await fetch(`/LikayLiveInTheater/booking-info/${encodeURIComponent(seatKey)}`);
    const data = await res.json();
    if (!data.success) { alert(data.error || 'ไม่พบข้อมูล'); closeDetailModal(); return; }

    currentBookingId = data.booking_id;

    document.getElementById('det-seats').innerHTML = data.all_seats
      .map(k => `<span class="seat-chip">${k}</span>`).join('');

    document.getElementById('det-name').textContent   = `${data.first_name} ${data.last_name}`;
    document.getElementById('det-phone').textContent  = data.phone;
    document.getElementById('det-booker').textContent = data.booker_name;
    document.getElementById('det-total').textContent  = '฿' + Number(data.total_price).toLocaleString('th-TH');
    document.getElementById('det-date').textContent   = data.booked_at || '-';

    const slipImg  = document.getElementById('det-slip-img');
    const slipLink = document.getElementById('det-slip-link');
    const slipWrap = document.getElementById('det-slip-wrap');
    slipImg.style.display  = 'none';
    slipLink.style.display = 'none';

    if (data.slip_url) {
      const isPdf = data.slip_url.toLowerCase().endsWith('.pdf');
      if (isPdf) {
        slipLink.href = data.slip_url;
        slipLink.style.display = 'inline';
      } else {
        slipImg.src = data.slip_url;
        slipImg.style.display = 'block';
      }
      slipWrap.style.display = 'block';
    } else {
      slipWrap.style.display = 'none';
    }

    document.getElementById('det-cancel-wrap').style.display = IS_MANAGER ? 'block' : 'none';

    document.getElementById('detail-loading').style.display = 'none';
    document.getElementById('detail-body').style.display    = 'block';
  } catch {
    alert('เกิดข้อผิดพลาด');
    closeDetailModal();
  }
}

function closeDetailModal() {
  document.getElementById('detailModal').classList.remove('open');
  currentBookingId = null;
}

async function cancelBooking() {
  if (!currentBookingId) return;
  if (!confirm('ยืนยันยกเลิกการจองนี้? ที่นั่งทั้งหมดใน booking จะถูกปล่อยว่าง')) return;

  const btn = document.getElementById('det-cancel-btn');
  btn.disabled = true;
  btn.textContent = 'กำลังยกเลิก...';

  try {
    const res  = await fetch(`/LikayLiveInTheater/booking/${currentBookingId}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': CSRF }
    });
    const data = await res.json();
    if (data.success) {
      closeDetailModal();
      alert('ยกเลิกการจองเรียบร้อย');
      location.reload();
    } else {
      alert(data.error || 'เกิดข้อผิดพลาด');
    }
  } catch {
    alert('เกิดข้อผิดพลาด');
  } finally {
    btn.disabled = false;
    btn.textContent = '🗑 ยกเลิกการจอง';
  }
}

function openPriceModal()  { document.getElementById('priceModal').classList.add('open'); }
function closePriceModal() { document.getElementById('priceModal').classList.remove('open'); }

async function savePrices() {
  const zones = ['vvip','box','yellow','blue','pink','green','purple'];
  const data  = {};
  zones.forEach(z => { data[z] = parseInt(document.getElementById('price_'+z).value) || 0; });

  try {
    const res  = await fetch('/LikayLiveInTheater/prices', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify(data)
    });
    const json = await res.json();
    if (json.success) {
      Object.assign(PRICES, data);
      zones.forEach(z => {
        const el = document.getElementById('legend_'+z);
        if (el) el.textContent = '฿'+data[z].toLocaleString('th-TH');
      });
      updateSummary();
      closePriceModal();
    }
  } catch {
    alert('บันทึกไม่สำเร็จ');
  }
}

async function resetAllSeats() {
  try {
    const res  = await fetch('/LikayLiveInTheater/reset', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF }
    });
    const data = await res.json();
    if (!data.success) { alert(data.error || 'เกิดข้อผิดพลาด'); return; }
    BOOKED.clear();
    SELECTED.clear();
    document.querySelectorAll('.seat.is-booked').forEach(el => el.classList.remove('is-booked'));
    document.querySelectorAll('.seat.is-selected').forEach(el => el.classList.remove('is-selected'));
    updateStats();
    updateSummary();
    updateFloatBtn();
  } catch {
    alert('เกิดข้อผิดพลาด');
  }
}

function updateStats() {
  const total     = @json($totalSeats);
  const booked    = BOOKED.size;
  const selected  = SELECTED.size;
  const selecting = SELECTING_OTHERS.size;
  document.getElementById('stat-booked').textContent    = booked;
  document.getElementById('stat-avail').textContent     = Math.max(0, total - booked - selected - selecting);
  document.getElementById('stat-selected').textContent  = selected;
  document.getElementById('stat-selecting').textContent = selecting;
}

function updateSummary() {
  let total = 0;
  SELECTED.forEach(zone => { total += PRICES[zone] || 0; });

  document.getElementById('sum-selected').textContent = SELECTED.size;
  document.getElementById('sum-total').textContent    = total.toLocaleString('th-TH');

  const tagsEl = document.getElementById('sel-tags');
  if (SELECTED.size === 0) {
    tagsEl.innerHTML = '<span style="color:#999;font-size:13px;">คลิกที่นั่งที่ต้องการ แล้วกดปุ่มจอง</span>';
  } else {
    tagsEl.innerHTML = [...SELECTED.entries()].map(([k, zone]) => {
      const price = PRICES[zone] || 0;
      return `<span class="sel-tag">${k} <span style="color:#777">(฿${price.toLocaleString('th-TH')})</span></span>`;
    }).join('');
  }
}

function updateFloatBtn() {
  const btn = document.getElementById('floatBookBtn');
  document.getElementById('float-count').textContent = SELECTED.size;
  btn.classList.toggle('visible', SELECTED.size > 0);
}

document.getElementById('priceModal').addEventListener('click', function(e) {
  if (e.target === this) closePriceModal();
});
document.getElementById('bookingModal').addEventListener('click', function(e) {
  if (e.target === this) closeBookingModal();
});
document.getElementById('detailModal').addEventListener('click', function(e) {
  if (e.target === this) closeDetailModal();
});

init();
</script>

<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
(function () {
  const pusher  = new Pusher('{{ config("broadcasting.connections.pusher.key") }}', { cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}' });
  const channel = pusher.subscribe('likay-concert');

  channel.bind('seat-status-updated', function (data) {
    (data.booked_keys || []).forEach(function (key) {
      SELECTED.delete(key);
      SELECTING_OTHERS.delete(key);
      BOOKED.add(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.remove('is-selected', 'is-selecting');
        el.classList.add('is-booked');
        el.removeAttribute('title');
      });
    });

    (data.freed_keys || []).forEach(function (key) {
      BOOKED.delete(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.remove('is-booked');
      });
    });

    (data.selecting_keys || []).forEach(function (key) {
      if (SELECTED.has(key) || BOOKED.has(key)) return;
      SELECTING_OTHERS.add(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.add('is-selecting');
        el.setAttribute('title', 'กำลังถูกเลือกอยู่');
      });
    });

    (data.deselecting_keys || []).forEach(function (key) {
      SELECTING_OTHERS.delete(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        if (!BOOKED.has(key)) {
          el.classList.remove('is-selecting');
          el.removeAttribute('title');
        }
      });
    });

    updateStats();
    updateSummary();
    updateFloatBtn();
  });
})();
</script>
</body>
</html>
