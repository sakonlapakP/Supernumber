<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>ผังที่นั่ง — วงสุนทราภรณ์</title>
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

    /* ─── Zone colors (dynamic) ─── */
    @foreach($zones as $zone)
    .z-{{ $zone->slug }} { background: {{ $zone->color }}; color: {{ $zone->text_color }}; border-color: {{ $zone->border_color }}; }
    @endforeach
    .z-wc      { background: #9E9E9E; color: #fff; border-color: #757575; cursor: default !important; opacity: 0.75; }
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
    /* ─── ที่นั่ง Sponsor (กันไว้ ฿0) — แอดมินเห็นเป็นสีทอง, ลูกค้าเห็นเป็น "ขายแล้ว" ─── */
    .seat.is-sponsor {
      background: #C9A227 !important;
      border-color: #8a6d1a !important;
      color: #fff !important;
      cursor: pointer;
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
    /* ─── กำลังถูกเลือกโดยผู้อื่น ─── */
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
    .seat-gap  { width: 22px; flex-shrink: 0; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:800; color:#555; }
    .seats-line { display: flex; align-items: center; gap: 1.5px; }
    .seat-gap-center {
      width: 28px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 800; color: #222;
      height: 28px; margin: 0;
    }

    /* ─── Row ─── */
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
      width: 60px;
      height: 28px;
      display: flex;
      align-items: center;
      position: relative;
      box-sizing: border-box;
      flex-shrink: 0;
    }
    @media (max-width: 640px) { .box-cell { width: 28px; padding: 0 2px; } }
    .left-row-half .box-cell {
      margin-right: auto;
      justify-content: flex-end;
      padding-right: 4px;
    }
    .right-row-half .box-cell {
      margin-left: auto;
      justify-content: flex-start;
      padding-left: 4px;
    }

    /* ─── Box Borders ─── */
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

    /* Box Labels */
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
    .left-row-half .box-label-text {
      right: 6px;
    }
    .right-row-half .box-label-text {
      left: 6px;
    }

    /* ♿ Wheelchair Badge */
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
    .left-row-half .wc-badge {
      margin-right: 6px;
    }
    .right-row-half .wc-badge {
      margin-left: 6px;
    }

    /* ─── Chart container ─── */
    .chart-scroll {
      overflow-x: auto;
      padding-bottom: 8px;
    }
    .chart {
      min-width: 1140px;
      margin: 0 auto;
    }

    /* ─── VIP + VVIP top section ─── */
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

    /* ─── Stage ─── */
    .stage-area {
      display: flex;
      justify-content: center;
      margin-top: 10px;
    }
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

    /* ─── Legend ─── */
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

    /* ─── Header ─── */
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 12px;
    }
    .page-title { font-size: 22px; font-weight: 800; color: #1a1a1a; }

    /* ─── Buttons ─── */
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

    /* ─── Modal backdrop ─── */
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

    /* Price modal rows */
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

    /* Booking form */
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

    /* ─── Summary ─── */
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

    /* ─── Stats bar ─── */
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

    /* Booking detail popup */
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
    .seats-group {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
    }
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

    /* Floating book button */
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

{{-- ─── Zone Management Modal ─────────────────────────────── --}}
<div class="modal-backdrop" id="priceModal">
  <div class="modal" style="width:560px;max-width:98vw;">
    <h2>🎨 จัดการ Zone</h2>

    {{-- Section A: Zone list --}}
    <div style="margin-bottom:18px;">
      <div style="font-size:14px;font-weight:700;margin-bottom:8px;color:#444;">Zone ทั้งหมด</div>
      <table style="width:100%;border-collapse:collapse;font-size:13px;" id="zone-table">
        <thead>
          <tr style="background:#f5f5f5;">
            <th style="padding:6px 8px;text-align:left;font-weight:600;color:#555;">สี</th>
            <th style="padding:6px 8px;text-align:left;font-weight:600;color:#555;">ชื่อ Zone</th>
            <th style="padding:6px 8px;text-align:right;font-weight:600;color:#555;">ราคา (บาท)</th>
            <th style="padding:6px 8px;text-align:center;font-weight:600;color:#555;">จัดการ</th>
          </tr>
        </thead>
        <tbody id="zone-table-body">
          @foreach($zones as $zone)
          <tr data-zone-id="{{ $zone->id }}" data-zone-slug="{{ $zone->slug }}">
            <td style="padding:5px 8px;">
              <input type="color" value="{{ $zone->color }}" class="zone-color-input" data-zone-id="{{ $zone->id }}" style="width:36px;height:26px;padding:1px;border:1px solid #ddd;border-radius:4px;cursor:pointer;">
            </td>
            <td style="padding:5px 8px;">
              <input type="text" value="{{ $zone->label }}" class="zone-label-input" data-zone-id="{{ $zone->id }}" style="border:1px solid #ddd;border-radius:4px;padding:4px 7px;font-size:13px;width:100%;">
            </td>
            <td style="padding:5px 8px;">
              <input type="number" value="{{ $zone->price }}" class="zone-price-input" data-zone-id="{{ $zone->id }}" min="0" step="100" style="border:1px solid #ddd;border-radius:4px;padding:4px 7px;font-size:13px;width:90px;text-align:right;">
            </td>
            <td style="padding:5px 8px;text-align:center;white-space:nowrap;">
              <button onclick="saveZone({{ $zone->id }})" style="background:#1a1a2e;color:#fff;border:none;border-radius:5px;padding:4px 10px;font-size:12px;cursor:pointer;margin-right:4px;">บันทึก</button>
              <button onclick="deleteZone({{ $zone->id }},'{{ $zone->label }}')" style="background:#e53935;color:#fff;border:none;border-radius:5px;padding:4px 10px;font-size:12px;cursor:pointer;">ลบ</button>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
      <button onclick="addNewZoneRow()" style="margin-top:8px;background:#2e7d32;color:#fff;border:none;border-radius:6px;padding:6px 14px;font-size:13px;cursor:pointer;font-family:inherit;">+ เพิ่ม Zone ใหม่</button>
    </div>

    {{-- Section B: Row assignment --}}
    <div>
      <div style="font-size:14px;font-weight:700;margin-bottom:8px;color:#444;">กำหนด Zone ให้แต่ละแถว</div>
      <div style="max-height:280px;overflow-y:auto;border:1px solid #eee;border-radius:6px;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
          <thead>
            <tr style="background:#f5f5f5;position:sticky;top:0;">
              <th style="padding:6px 10px;text-align:left;font-weight:600;color:#555;">แถว</th>
              <th style="padding:6px 10px;text-align:left;font-weight:600;color:#555;">Zone</th>
            </tr>
          </thead>
          <tbody>
            @php
              $allRowKeys = ['V','W','U','T','S','R','Q','P','N','M','L','K','J','H','G','F','E','D','C','B','A','BOXA','BOXB','BOXC','BOXD','BOXE','BOXF'];
            @endphp
            @foreach($allRowKeys as $rowKey)
            <tr style="border-bottom:1px solid #f0f0f0;">
              <td style="padding:5px 10px;font-weight:600;color:#333;">{{ $rowKey }}</td>
              <td style="padding:5px 10px;">
                <select class="row-zone-select" data-row-key="{{ $rowKey }}" style="border:1px solid #ddd;border-radius:4px;padding:4px 8px;font-size:13px;font-family:inherit;width:100%;">
                  @foreach($zones as $z)
                    <option value="{{ $z->id }}" {{ ($rowZones[$rowKey] ?? '') === $z->slug ? 'selected' : '' }}>{{ $z->label }} — ฿{{ number_format($z->price) }}</option>
                  @endforeach
                </select>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <button onclick="saveRowZones()" style="margin-top:8px;background:#1a1a2e;color:#fff;border:none;border-radius:6px;padding:8px 18px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;">บันทึกการกำหนดแถว</button>
    </div>

    <div class="modal-footer" style="margin-top:12px;">
      <button class="btn btn-outline" onclick="closePriceModal()">ปิด</button>
    </div>
  </div>
</div>

{{-- ─── Booking Modal ──────────────────────────────────────── --}}
<div class="modal-backdrop" id="bookingModal">
  <div class="modal">
    <h2 id="booking-modal-title">🎟️ จองที่นั่ง</h2>

    <div style="display:flex;align-items:center;gap:8px;background:#f0f4ff;border:1.5px solid #c5d3f7;border-radius:8px;padding:8px 14px;margin-bottom:14px;font-size:14px;">
      <span style="font-size:18px;">📅</span>
      <span style="color:#555;">รอบการแสดง:</span>
      <strong style="color:#1a1a2e;">{{ $showDates[$showDate] }}</strong>
    </div>

    {{-- ประเภทการจอง: ขายปกติ / Sponsor --}}
    <div class="form-group">
      <label>ประเภทการจอง</label>
      <select id="booking-type" onchange="onBookingTypeChange()" style="width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;font-family:inherit;background:#fff;">
        <option value="normal">🎟️ ขายปกติ</option>
        <option value="sponsor">🎁 Sponsor (฿0 — ไม่คิดเงิน)</option>
      </select>
    </div>

    <div id="sponsor-note-box" style="display:none;align-items:flex-start;gap:8px;background:#fdf6e3;border:1.5px solid #e6cf7a;border-radius:8px;padding:8px 14px;margin-bottom:14px;font-size:13px;color:#7a5d00;">
      <span style="font-size:18px;">ℹ️</span>
      <span>ที่นั่งนี้จะถูกกันไว้ให้ Sponsor (<strong>ไม่คิดเงิน ฿0</strong>) — ฝั่งลูกค้าจะเห็นเป็น <strong>"ขายแล้ว"</strong></span>
    </div>

    <div class="selected-seats-list">
      <strong>ที่นั่งที่เลือก</strong>
      <div id="selected-chips"></div>
      <div class="booking-total" id="booking-total-wrap">ยอดรวม: ฿<span id="booking-total-price">0</span></div>
    </div>

    <form id="bookingForm" enctype="multipart/form-data">
      @csrf
      <div id="customer-fields">
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
          <input type="tel" name="phone" id="inp-phone" placeholder="0812345678" required
                 inputmode="numeric" pattern="[0-9]{10}" maxlength="10" minlength="10">
        </div>
        <div class="form-group">
          <label>อัพโหลด Slip โอนเงิน</label>
          <input type="file" name="slip" id="inp-slip" accept="image/jpeg,image/png,application/pdf">
          <div style="font-size:12px;color:#999;margin-top:4px;">JPG / PNG / PDF ขนาดไม่เกิน 5 MB</div>
        </div>
      </div>
      <div class="form-group" id="sponsor-field" style="display:none;">
        <label>ชื่อ / โน้ต Sponsor <span style="color:#999;font-weight:400;">(ไม่บังคับ — กรอกภายหลังได้)</span></label>
        <input type="text" id="inp-sponsor-name" maxlength="100" placeholder="เช่น King Power (เว้นว่างได้)">
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
      <div id="det-sponsor-badge" style="display:none;background:#fdf6e3;border:1.5px solid #e6cf7a;color:#7a5d00;border-radius:8px;padding:8px 12px;margin-bottom:12px;font-weight:600;font-size:14px;">🎁 ที่นั่ง Sponsor (กันไว้ — ฿0)</div>
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
      <div id="det-sponsor-wrap" style="display:none;">
        <button class="btn-cancel-booking" id="det-sponsor-btn" onclick="removeSponsor()" style="background:#C9A227;border-color:#8a6d1a;">🎁 ยกเลิกที่นั่ง Sponsor</button>
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
    <div class="page-title">🎵 ผังที่นั่ง วงสุนทราภรณ์</div>
    <div style="font-size:13px;color:#777;margin-top:2px;">สวัสดี, {{ $user->name }}</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <a href="{{ route('suntaraporn.bookings', ['date' => $showDate]) }}" class="btn btn-outline">📋 รายการจอง</a>
    @if ($user->role === 'manager')
    <button class="btn btn-outline" onclick="openResetModal()">🔄 รีเซ็ต</button>
    <button class="btn btn-primary" onclick="openPriceModal()">✏️ แก้ไขราคา</button>
    @endif
    <form method="POST" action="{{ route('suntaraporn.logout') }}" style="margin:0;">
      @csrf
      <button type="submit" class="btn btn-outline">ออกจากระบบ</button>
    </form>
  </div>
</div>

{{-- Show date selector --}}
<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 12px;padding:10px 14px;background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.08);">
  <span style="font-size:13px;font-weight:700;color:#555;">🗓️ รอบการแสดง:</span>
  @foreach ($showDates as $date => $label)
  <a href="{{ route('suntaraporn.index', ['date' => $date]) }}"
     style="padding:7px 16px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;border:1.5px solid {{ $showDate === $date ? '#1a1a2e' : '#ddd' }};background:{{ $showDate === $date ? '#1a1a2e' : '#fff' }};color:{{ $showDate === $date ? '#fff' : '#555' }};">
    {{ $label }}
  </a>
  @endforeach
</div>

{{-- Stats bar --}}
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
    <span class="stat-num" id="stat-sponsor" style="color:#C9A227">0</span>
    <span class="stat-lbl">🎁 Sponsor</span>
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
  <div class="stat-item" style="border-left:2px solid #eee;padding-left:16px;margin-left:4px;">
    <span class="stat-num" id="stat-sold-revenue" style="color:#2e7d32;font-size:16px;">฿0</span>
    <span class="stat-lbl">💰 ขายแล้ว</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" id="stat-sellable-revenue" style="color:#1565c0;font-size:16px;">฿0</span>
    <span class="stat-lbl">🎯 ขายได้สูงสุด (หลังหัก Sponsor)</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" id="stat-full-revenue" style="color:#aaa;font-size:16px;">฿0</span>
    <span class="stat-lbl">🏷️ ถ้าขายหมด</span>
  </div>
</div>

{{-- Legend --}}
@php
  // แสดงเฉพาะโซนที่มีราคา (ตัด Control/VIP/BOX A-F ราคา ฿0 ออกจากแถบ Legend)
  $legend = $zones
    ->filter(fn($z) => ($prices[$z->slug] ?? 0) > 0)
    ->map(fn($z) => [
      'zone'   => $z->slug,
      'label'  => $z->label,
      'bg'     => $z->color,
      'border' => $z->border_color,
    ])->values()->all();
@endphp
<div class="legend" id="legend-bar">
  @foreach ($legend as $item)
  <div class="legend-item" id="legend-item-{{ $item['zone'] }}">
    <div class="legend-swatch" style="background:{{ $item['bg'] }};border-color:{{ $item['border'] }}"></div>
    <span>
      {{ $item['label'] }}
      — <strong id="legend_{{ $item['zone'] }}">฿{{ number_format($prices[$item['zone']] ?? 0) }}</strong>
    </span>
  </div>
  @endforeach
  <div class="legend-item">
    <div class="legend-swatch" style="background:#555;border-color:#333;"></div>
    <span>จองแล้ว</span>
  </div>
  <div class="legend-item">
    <div class="legend-swatch" style="background:#C9A227;border-color:#8a6d1a;"></div>
    <span>🎁 Sponsor (฿0)</span>
  </div>
  <div class="legend-item">
    <div class="legend-swatch" style="background:#1a1a2e;border-color:#000;"></div>
    <span>เลือกอยู่ (ฉัน)</span>
  </div>
  <div class="legend-item">
    <div class="legend-swatch" style="background:#FF8F00;border-color:#E65100;display:flex;align-items:center;justify-content:center;font-size:11px;">🔒</div>
    <span>ผู้อื่นกำลังเลือก</span>
  </div>
</div>

{{-- ─── Seating Chart ──────────────────────────────────────── --}}
<div style="background:#fff;border-radius:12px;padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.1);">
  <div class="chart-scroll">
    <div class="chart">

      {{-- Control + VIP V/W --}}
      <div style="display:flex;align-items:stretch;justify-content:center;gap:8px;margin-bottom:8px;padding:8px 0;">

        {{-- Row W/V Left --}}
        <div style="display:flex;flex-direction:column;gap:2px;align-items:flex-end;">
          <div style="display:flex;align-items:center;gap:4px;">
            <span class="row-label">W</span>
            <div class="seats-line">
              @foreach (range(1,9) as $n)
                <div class="seat z-{{ $rowZones['W'] ?? 'vip' }}" data-key="W_{{ $n }}" data-zone="{{ $rowZones['W'] ?? 'vip' }}" onclick="toggleSeat(this)">{{ $n }}</div>
              @endforeach
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:4px;">
            <span class="row-label">V</span>
            <div class="seats-line">
              @foreach (range(1,8) as $n)
                <div class="seat z-{{ $rowZones['V'] ?? 'vip' }}" data-key="V_{{ $n }}" data-zone="{{ $rowZones['V'] ?? 'vip' }}" onclick="toggleSeat(this)">{{ $n }}</div>
              @endforeach
            </div>
          </div>
        </div>

        {{-- Control --}}
        <div class="vvip-box" style="height:auto;align-self:stretch;">VVIP</div>

        {{-- Row W/V Right --}}
        <div style="display:flex;flex-direction:column;gap:2px;align-items:flex-start;">
          <div style="display:flex;align-items:center;gap:4px;">
            <div class="seats-line">
              @foreach (range(10,18) as $n)
                <div class="seat z-{{ $rowZones['W'] ?? 'vip' }}" data-key="W_{{ $n }}" data-zone="{{ $rowZones['W'] ?? 'vip' }}" onclick="toggleSeat(this)">{{ $n }}</div>
              @endforeach
            </div>
            <span class="row-label">W</span>
          </div>
          <div style="display:flex;align-items:center;gap:4px;">
            <div class="seats-line">
              @foreach (range(9,16) as $n)
                <div class="seat z-{{ $rowZones['V'] ?? 'vip' }}" data-key="V_{{ $n }}" data-zone="{{ $rowZones['V'] ?? 'vip' }}" onclick="toggleSeat(this)">{{ $n }}</div>
              @endforeach
            </div>
            <span class="row-label">V</span>
          </div>
        </div>

      </div>

      {{-- ─── Main Rows ─── --}}
      @php
        $r = fn($s,$e) => range($s,$e);
        $rz = fn(string $key) => $rowZones[$key] ?? 'yellow';
        $bz = fn(string $box) => $rowZones[$box] ?? 'box';
        $rows = [
          ['U', $rz('U'), [],      $r(1,6),   $r(7,13),  [],        null,                              null],
          ['T', $rz('T'), [],      $r(1,11),  $r(12,23), [],        null,                              null],
          ['S', $rz('S'), [],      $r(1,10),  $r(11,21), [],        ['k'=>'BOXC_14','n'=>14,'z'=>$bz('BOXC')], ['k'=>'BOXF_15','n'=>15,'z'=>$bz('BOXF')]],
          ['R', $rz('R'), [],      $r(1,10),  $r(11,21), [],        ['k'=>'BOXC_13','n'=>13,'z'=>$bz('BOXC')], ['k'=>'BOXF_16','n'=>16,'z'=>$bz('BOXF')]],
          ['Q', $rz('Q'), $r(1,4), $r(5,14),  $r(15,24), $r(25,28), ['k'=>'BOXC_12','n'=>12,'z'=>$bz('BOXC')], ['k'=>'BOXF_17','n'=>17,'z'=>$bz('BOXF')]],
          ['P', $rz('P'), $r(1,5), $r(6,15),  $r(16,24), $r(25,29), ['k'=>'BOXC_11','n'=>11,'z'=>$bz('BOXC')], ['k'=>'BOXF_18','n'=>18,'z'=>$bz('BOXF')]],
          ['N', $rz('N'), $r(1,6), $r(7,15),  $r(16,24), $r(25,30), ['k'=>'BOXC_10','n'=>10,'z'=>$bz('BOXC')], ['k'=>'BOXF_19','n'=>19,'z'=>$bz('BOXF')]],
          ['M', $rz('M'), $r(1,6), $r(7,14),  $r(15,23), $r(24,29), ['k'=>'BOXB_9', 'n'=>9, 'z'=>$bz('BOXB')], ['k'=>'BOXE_20','n'=>20,'z'=>$bz('BOXE')]],
          ['L', $rz('L'), $r(1,7), $r(8,16),  $r(17,24), $r(25,31), ['k'=>'BOXB_8', 'n'=>8, 'z'=>$bz('BOXB')], ['k'=>'BOXE_21','n'=>21,'z'=>$bz('BOXE')]],
          ['K', $rz('K'), $r(1,8), $r(9,16),  $r(17,24), $r(25,32), ['k'=>'BOXB_7', 'n'=>7, 'z'=>$bz('BOXB')], ['k'=>'BOXE_22','n'=>22,'z'=>$bz('BOXE')]],
          ['J', $rz('J'), $r(1,8), $r(9,16),  $r(17,24), $r(25,32), ['k'=>'BOXB_6', 'n'=>6, 'z'=>$bz('BOXB')], ['k'=>'BOXE_23','n'=>23,'z'=>$bz('BOXE')]],
          ['H', $rz('H'), $r(1,8), $r(9,15),  $r(16,23), $r(24,31), null,                               null],
          ['G', $rz('G'), $r(1,8), $r(9,15),  $r(16,23), $r(24,31), ['k'=>'BOXA_5','n'=>5,'z'=>$bz('BOXA')], ['k'=>'BOXD_24','n'=>24,'z'=>$bz('BOXD')]],
          ['F', $rz('F'), $r(1,8), $r(9,15),  $r(16,23), $r(24,31), ['k'=>'BOXA_4','n'=>4,'z'=>$bz('BOXA')], ['k'=>'BOXD_25','n'=>25,'z'=>$bz('BOXD')]],
          ['E', $rz('E'), $r(1,8), $r(9,15),  $r(16,23), $r(24,31), ['k'=>'BOXA_3','n'=>3,'z'=>$bz('BOXA')], ['k'=>'BOXD_26','n'=>26,'z'=>$bz('BOXD')]],
          ['D', $rz('D'), $r(1,7), $r(8,14),  $r(15,22), $r(23,29), ['k'=>'BOXA_2','n'=>2,'z'=>$bz('BOXA')], ['k'=>'BOXD_27','n'=>27,'z'=>$bz('BOXD')]],
          ['C', $rz('C'), $r(1,7), $r(8,14),  $r(15,22), $r(23,29), ['k'=>'BOXA_1','n'=>1,'z'=>$bz('BOXA')], ['k'=>'BOXD_28','n'=>28,'z'=>$bz('BOXD')]],
          ['B', $rz('B'), $r(1,6), $r(7,13),  $r(14,21), $r(22,27), null,                              null],
          ['A', $rz('A'), $r(1,5), $r(6,12),  $r(13,20), $r(21,25), null,                              null],
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

        {{-- ─── Left Side (50%) ─── --}}
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
              @if ($boxL['k'] === 'BOXC_14')
                <div class="wc-badge" title="ที่นั่งผู้พิการ">♿</div>
              @endif
              @if ($showLabelL)
                <span class="box-label-text">{{ $showLabelL }}</span>
              @endif
              {!! $seatEl($boxL['k'], $boxL['z'], $boxL['n'], $boxL['z']==='wc') !!}
            @endif
          </div>

          <span class="row-label">{{ $rid }}</span>

          <div class="seats-half-left">
            @foreach ($left as $n)
              {!! $seatEl($rid.'_'.$n, $zone, $n) !!}
            @endforeach
            @if (count($left) > 0)<div class="seat-gap">{{ $rid }}</div>@endif
            @foreach ($cl as $n)
              {!! $seatEl($rid.'_'.$n, $zone, $n) !!}
            @endforeach
          </div>
        </div>

        {{-- Center Divider --}}
        <div class="seat-gap-center">{{ $rid }}</div>

        {{-- ─── Right Side (50%) ─── --}}
        <div class="right-row-half">
          <div class="seats-half-right">
            @foreach ($cr as $n)
              {!! $seatEl($rid.'_'.$n, $zone, $n) !!}
            @endforeach
            @if (count($right) > 0)<div class="seat-gap">{{ $rid }}</div>@endif
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
              @if ($showLabelR)
                <span class="box-label-text">{{ $showLabelR }}</span>
              @endif
              @if ($boxR['k'] === 'BOXF_15')
                <div class="wc-badge" title="ที่นั่งผู้พิการ">♿</div>
              @endif
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
</div>

{{-- Summary --}}
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

{{-- Floating book button --}}
<button class="btn btn-success float-book-btn" id="floatBookBtn" onclick="openBookingModal()">
  🎟️ จองที่นั่งที่เลือก (<span id="float-count">0</span>)
</button>

<script>
const CSRF             = document.querySelector('meta[name="csrf-token"]').content;
const SHOW_DATE        = @json($showDate);
const BOOKED           = new Set(@json($bookedSeats));
const SPONSOR          = new Map(Object.entries(@json((object) $sponsorSeats))); // key → ชื่อ/โน้ต (ที่นั่งกันให้ Sponsor)
const PRICES           = @json($prices);
const ZONE_COLORS      = @json($zones->mapWithKeys(fn($z) => [$z->slug => ['bg' => $z->color, 'text' => $z->text_color, 'border' => $z->border_color]]));
const SELECTED         = new Map(); // key → zone (ที่นั่งที่ฉันเลือก)
const SELECTING_OTHERS = new Set(); // key (ที่นั่งที่ผู้อื่นกำลังเลือก)

// สร้าง chip ด้วยสีตาม zone (ดึง zone จาก SELECTED หรือ DOM)
function chipHtml(key) {
  const zone  = SELECTED.get(key) ?? document.querySelector(`[data-key="${CSS.escape(key)}"]`)?.dataset.zone ?? '';
  const c     = ZONE_COLORS[zone];
  const style = c ? `background:${c.bg};color:${c.text};border:1.5px solid ${c.border};` : '';
  return `<span class="seat-chip" style="${style}">${key}</span>`;
}

// ── Initialize ──────────────────────────────────────────────────
function init() {
  document.querySelectorAll('[data-key]').forEach(el => {
    if (BOOKED.has(el.dataset.key)) el.classList.add('is-booked');
  });
  // ที่นั่ง Sponsor — แอดมินเห็นเป็นสีทอง + โน้ตชื่อ (อยู่ใน BOOKED ด้วยจึงกันการจองทับ)
  SPONSOR.forEach((note, key) => {
    document.querySelectorAll(`[data-key="${CSS.escape(key)}"]`).forEach(el => {
      el.classList.add('is-sponsor');
      el.setAttribute('title', '🎁 Sponsor: ' + note);
    });
  });
  updateStats();
  updateSummary();
}

// ── Toggle Seat Selection ────────────────────────────────────────
function toggleSeat(el) {
  if (el.classList.contains('is-reserved')) return;
  if (el.classList.contains('is-selecting')) return; // ถูกถือโดยผู้อื่น

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

// ── Broadcast select / deselect ──────────────────────────────────
async function broadcastSelect(keys) {
  try {
    await fetch('/SuntarapornBand/select', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ seat_keys: keys, date: SHOW_DATE })
    });
  } catch {}
}

async function broadcastDeselect(keys) {
  try {
    await fetch('/SuntarapornBand/deselect', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ seat_keys: keys, date: SHOW_DATE })
    });
  } catch {}
}

// ── ปิด tab → deselect ที่นั่งทั้งหมดที่ถือไว้ ───────────────────
window.addEventListener('beforeunload', function () {
  if (SELECTED.size === 0) return;
  const fd = new FormData();
  fd.append('_token', CSRF);
  fd.append('date', SHOW_DATE);
  [...SELECTED.keys()].forEach(k => fd.append('seat_keys[]', k));
  navigator.sendBeacon('/SuntarapornBand/deselect', fd);
});

// ── Booking Modal ────────────────────────────────────────────────
function openBookingModal() {
  if (SELECTED.size === 0) return;

  // Update chips and total in modal
  const chips = document.getElementById('selected-chips');
  chips.innerHTML = [...SELECTED.keys()].map(k => chipHtml(k)).join('');

  let total = 0;
  SELECTED.forEach(zone => { total += PRICES[zone] || 0; });
  document.getElementById('booking-total-price').textContent = total.toLocaleString('th-TH');

  document.getElementById('bookingForm').reset();
  document.getElementById('booking-type').value = 'normal'; // เริ่มที่ "ขายปกติ" เสมอ
  onBookingTypeChange();
  document.getElementById('bookingModal').classList.add('open');
}

function closeBookingModal() {
  document.getElementById('bookingModal').classList.remove('open');
}

document.getElementById('inp-phone').addEventListener('input', function () {
  this.value = this.value.replace(/\D/g, '').slice(0, 10);
});

async function confirmBooking() {
  // ถ้าเลือกประเภท Sponsor → ไปเส้นทางกันที่นั่ง (฿0)
  if (document.getElementById('booking-type').value === 'sponsor') {
    return confirmSponsorBooking();
  }

  const form    = document.getElementById('bookingForm');
  const inputs  = form.querySelectorAll('[required]');
  for (const inp of inputs) {
    if (!inp.value.trim()) { inp.focus(); alert('กรุณากรอกข้อมูลให้ครบถ้วน'); return; }
  }

  const phone = document.getElementById('inp-phone').value.trim();
  if (!/^[0-9]{10}$/.test(phone)) {
    document.getElementById('inp-phone').focus();
    alert('กรุณากรอกเบอร์โทรศัพท์ให้ครบ 10 หลัก (ตัวเลขเท่านั้น)');
    return;
  }

  const btn = document.getElementById('confirmBookBtn');
  btn.disabled = true;
  btn.textContent = 'กำลังบันทึก...';

  const fd = new FormData();
  fd.append('_token', CSRF);
  fd.append('date', SHOW_DATE);
  [...SELECTED.entries()].forEach(([k, z], i) => {
    fd.append('seat_keys[]', k);
    fd.append('zones[]', z);
  });
  fd.append('first_name', document.getElementById('inp-first-name').value.trim());
  fd.append('last_name',  document.getElementById('inp-last-name').value.trim());
  fd.append('phone',      document.getElementById('inp-phone').value.trim());
  const slipFile = document.getElementById('inp-slip').files[0];
  if (slipFile) fd.append('slip', slipFile);

  try {
    const res  = await fetch('/SuntarapornBand/book', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      // Mark seats as booked in UI
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

// ── ประเภทการจอง: ขายปกติ / Sponsor (รวมใน modal จองที่นั่ง) ──────
function onBookingTypeChange() {
  const isSponsor = document.getElementById('booking-type').value === 'sponsor';
  document.getElementById('customer-fields').style.display    = isSponsor ? 'none' : 'block';
  document.getElementById('sponsor-field').style.display      = isSponsor ? 'block' : 'none';
  document.getElementById('sponsor-note-box').style.display   = isSponsor ? 'flex' : 'none';
  document.getElementById('booking-total-wrap').style.display = isSponsor ? 'none' : 'block';
  document.getElementById('booking-modal-title').textContent  = isSponsor ? '🎁 กันที่นั่งให้ Sponsor' : '🎟️ จองที่นั่ง';
  document.getElementById('confirmBookBtn').textContent       = isSponsor ? 'ยืนยันกัน Sponsor' : 'ยืนยันการจอง';
  // ปิด required ของช่องลูกค้าตอนเป็น Sponsor (ช่องถูกซ่อน)
  ['inp-first-name', 'inp-last-name', 'inp-phone'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.required = !isSponsor;
  });
}

async function confirmSponsorBooking() {
  if (SELECTED.size === 0) return;
  const name = document.getElementById('inp-sponsor-name').value.trim();
  const btn  = document.getElementById('confirmBookBtn');
  btn.disabled = true;
  btn.textContent = 'กำลังบันทึก...';

  try {
    const res  = await fetch('/SuntarapornBand/mark-sponsor', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ seat_keys: [...SELECTED.keys()], sponsor_name: name, date: SHOW_DATE })
    });
    const data = await res.json();
    if (data.success) {
      const label = name || 'Sponsor';
      SELECTED.forEach((zone, key) => {
        BOOKED.add(key);
        SPONSOR.set(key, label);
        document.querySelectorAll(`[data-key="${CSS.escape(key)}"]`).forEach(el => {
          el.classList.remove('is-selected');
          el.classList.add('is-booked', 'is-sponsor');
          el.setAttribute('title', '🎁 Sponsor: ' + label);
        });
      });
      SELECTED.clear();
      updateStats();
      updateSummary();
      updateFloatBtn();
      closeBookingModal();
      alert('กันที่นั่งให้ Sponsor เรียบร้อย');
    } else {
      alert(data.error || 'เกิดข้อผิดพลาด');
    }
  } catch {
    alert('เกิดข้อผิดพลาด');
  } finally {
    btn.disabled = false;
    btn.textContent = 'ยืนยันกัน Sponsor';
  }
}

async function removeSponsor() {
  if (!currentSponsorSeats || !currentSponsorSeats.length) return;
  if (!confirm('ยกเลิกการกัน Sponsor นี้? ที่นั่งทั้งหมดจะกลับมาว่าง')) return;

  const btn = document.getElementById('det-sponsor-btn');
  btn.disabled = true;
  btn.textContent = 'กำลังยกเลิก...';

  try {
    const res  = await fetch('/SuntarapornBand/unmark-sponsor', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ seat_keys: currentSponsorSeats, date: SHOW_DATE })
    });
    const data = await res.json();
    if (data.success) {
      closeDetailModal();
      alert('ยกเลิกที่นั่ง Sponsor เรียบร้อย');
      location.reload();
    } else {
      alert(data.error || 'เกิดข้อผิดพลาด');
    }
  } catch {
    alert('เกิดข้อผิดพลาด');
  } finally {
    btn.disabled = false;
    btn.textContent = '🎁 ยกเลิกที่นั่ง Sponsor';
  }
}

// ── Booking Detail Modal ─────────────────────────────────────────
const IS_MANAGER = {{ $user->role === 'manager' ? 'true' : 'false' }};
let currentBookingId = null;
let currentSponsorSeats = null;

async function openDetailModal(seatKey) {
  currentBookingId = null;
  document.getElementById('detail-loading').style.display = 'block';
  document.getElementById('detail-body').style.display    = 'none';
  document.getElementById('detailModal').classList.add('open');

  try {
    const res  = await fetch(`/SuntarapornBand/booking-info/${encodeURIComponent(seatKey)}?date=${encodeURIComponent(SHOW_DATE)}`);
    const data = await res.json();
    if (!data.success) { alert(data.error || 'ไม่พบข้อมูล'); closeDetailModal(); return; }

    currentBookingId = data.booking_id;

    // Seats chips (ใช้ chipHtml เพื่อแสดงสีตาม zone ของเก้าอี้)
    document.getElementById('det-seats').innerHTML = data.all_seats
      .map(k => chipHtml(k)).join('');

    document.getElementById('det-name').textContent   = `${data.first_name} ${data.last_name}`;
    document.getElementById('det-phone').textContent  = data.phone;
    document.getElementById('det-booker').textContent = data.booker_name;
    document.getElementById('det-total').textContent  = '฿' + Number(data.total_price).toLocaleString('th-TH');
    document.getElementById('det-date').textContent   = data.booked_at || '-';

    // Slip
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

    // Sponsor vs การจองปกติ — สลับ badge + ปุ่มท้าย modal
    currentSponsorSeats = null;
    const sponsorBadge = document.getElementById('det-sponsor-badge');
    const sponsorWrap  = document.getElementById('det-sponsor-wrap');
    const cancelWrap   = document.getElementById('det-cancel-wrap');
    if (data.is_sponsor) {
      currentSponsorSeats = data.all_seats;
      document.getElementById('det-name').textContent = '🎁 ' + (data.first_name || 'Sponsor');
      sponsorBadge.style.display = 'block';
      sponsorWrap.style.display  = 'block';   // ยกเลิก Sponsor ได้ทุก admin
      cancelWrap.style.display   = 'none';
    } else {
      sponsorBadge.style.display = 'none';
      sponsorWrap.style.display  = 'none';
      cancelWrap.style.display   = IS_MANAGER ? 'block' : 'none'; // ยกเลิกจอง = manager เท่านั้น
    }

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
    const res  = await fetch(`/SuntarapornBand/booking/${currentBookingId}`, {
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

// ── Price Modal ─────────────────────────────────────────────────
function openPriceModal()  { document.getElementById('priceModal').classList.add('open'); }
function closePriceModal() { document.getElementById('priceModal').classList.remove('open'); }

async function saveZone(zoneId) {
  const row = document.querySelector(`tr[data-zone-id="${zoneId}"]`);
  if (!row) return;
  const label = row.querySelector('.zone-label-input').value.trim();
  const color = row.querySelector('.zone-color-input').value;
  const price = parseInt(row.querySelector('.zone-price-input').value) || 0;
  try {
    const res = await fetch(`/SuntarapornBand/zones/${zoneId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ label, color, price })
    });
    const data = await res.json();
    if (data.success) {
      alert('บันทึกสำเร็จ');
    } else {
      alert(data.error || 'เกิดข้อผิดพลาด');
    }
  } catch { alert('บันทึกไม่สำเร็จ'); }
}

async function deleteZone(zoneId, label) {
  if (!confirm(`ลบ Zone "${label}"?`)) return;
  try {
    const res = await fetch(`/SuntarapornBand/zones/${zoneId}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': CSRF }
    });
    const data = await res.json();
    if (data.success) {
      location.reload();
    } else {
      alert(data.error || 'เกิดข้อผิดพลาด');
    }
  } catch { alert('ลบไม่สำเร็จ'); }
}

function addNewZoneRow() {
  const tbody = document.getElementById('zone-table-body');
  const row = document.createElement('tr');
  row.setAttribute('data-zone-id', 'new');
  row.innerHTML = `
    <td style="padding:5px 8px;"><input type="color" value="#4CAF50" class="zone-color-input" data-zone-id="new" style="width:36px;height:26px;padding:1px;border:1px solid #ddd;border-radius:4px;cursor:pointer;"></td>
    <td style="padding:5px 8px;"><input type="text" placeholder="ชื่อ Zone" class="zone-label-input" data-zone-id="new" style="border:1px solid #ddd;border-radius:4px;padding:4px 7px;font-size:13px;width:100%;"></td>
    <td style="padding:5px 8px;"><input type="number" value="0" class="zone-price-input" data-zone-id="new" min="0" step="100" style="border:1px solid #ddd;border-radius:4px;padding:4px 7px;font-size:13px;width:90px;text-align:right;"></td>
    <td style="padding:5px 8px;text-align:center;"><input type="text" placeholder="slug (e.g. vip)" class="zone-slug-input" style="border:1px solid #ddd;border-radius:4px;padding:4px 7px;font-size:12px;width:80px;margin-right:4px;"><button onclick="createZone(this.closest('tr'))" style="background:#2e7d32;color:#fff;border:none;border-radius:5px;padding:4px 10px;font-size:12px;cursor:pointer;">สร้าง</button></td>
  `;
  tbody.appendChild(row);
}

async function createZone(row) {
  const label = row.querySelector('.zone-label-input').value.trim();
  const color = row.querySelector('.zone-color-input').value;
  const price = parseInt(row.querySelector('.zone-price-input').value) || 0;
  const slug  = row.querySelector('.zone-slug-input').value.trim().toLowerCase();
  if (!slug || !label) { alert('กรุณากรอกชื่อและ slug'); return; }
  try {
    const res = await fetch('/SuntarapornBand/zones', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ slug, label, color, price })
    });
    const data = await res.json();
    if (data.success) {
      location.reload();
    } else {
      alert(data.error || 'เกิดข้อผิดพลาด');
    }
  } catch { alert('สร้างไม่สำเร็จ'); }
}

async function saveRowZones() {
  const selects = document.querySelectorAll('.row-zone-select');
  const assignments = {};
  selects.forEach(sel => {
    assignments[sel.dataset.rowKey] = parseInt(sel.value);
  });
  try {
    const res = await fetch('/SuntarapornBand/row-zones', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify({ assignments })
    });
    const data = await res.json();
    if (data.success) {
      alert('บันทึกการกำหนดแถวสำเร็จ — รีโหลดหน้าเพื่อดูการเปลี่ยนแปลง');
      location.reload();
    } else {
      alert(data.error || 'เกิดข้อผิดพลาด');
    }
  } catch { alert('บันทึกไม่สำเร็จ'); }
}

function applyZoneConfig(zonesArr, rowZonesMap) {
  var css = '';
  zonesArr.forEach(function (z) {
    css += '.z-' + z.slug + ' { background: ' + z.color + '; color: ' + z.text_color + '; border-color: ' + z.border_color + '; }\n';
    PRICES[z.slug] = z.price;
    var legendEl = document.getElementById('legend_' + z.slug);
    if (legendEl) legendEl.textContent = '฿' + Number(z.price).toLocaleString('th-TH');
  });
  var styleEl = document.getElementById('zone-dynamic-style');
  if (!styleEl) {
    styleEl = document.createElement('style');
    styleEl.id = 'zone-dynamic-style';
    document.head.appendChild(styleEl);
  }
  styleEl.textContent = css;
  updateSummary();
}

// ── Reset ───────────────────────────────────────────────────────
async function resetAllSeats() {
  try {
    const res  = await fetch(`/SuntarapornBand/reset?date=${encodeURIComponent(SHOW_DATE)}`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF }
    });
    const data = await res.json();
    if (!data.success) { alert(data.error || 'เกิดข้อผิดพลาด'); return; }
    BOOKED.clear();
    SELECTED.clear();
    SPONSOR.clear();
    document.querySelectorAll('.seat.is-booked').forEach(el => el.classList.remove('is-booked'));
    document.querySelectorAll('.seat.is-selected').forEach(el => el.classList.remove('is-selected'));
    document.querySelectorAll('.seat.is-sponsor').forEach(el => { el.classList.remove('is-sponsor'); el.removeAttribute('title'); });
    updateStats();
    updateSummary();
    updateFloatBtn();
  } catch {
    alert('เกิดข้อผิดพลาด');
  }
}

// ── Stats + Summary ─────────────────────────────────────────────
function updateStats() {
  const total     = @json($totalSeats);
  const booked    = BOOKED.size;        // รวมที่นั่ง Sponsor ด้วย (กันการจองทับ)
  const sponsor   = SPONSOR.size;
  const selected  = SELECTED.size;
  const selecting = SELECTING_OTHERS.size;
  document.getElementById('stat-booked').textContent    = Math.max(0, booked - sponsor); // จองจริง (ไม่รวม Sponsor)
  document.getElementById('stat-sponsor').textContent   = sponsor;
  document.getElementById('stat-avail').textContent     = Math.max(0, total - booked - selected - selecting);
  document.getElementById('stat-selected').textContent  = selected;
  document.getElementById('stat-selecting').textContent = selecting;

  updateRevenue();
}

// รายได้ (คำนวณสดจากผังจริง):
//  • ขายแล้ว        = ที่นั่งจองจริง (ไม่รวม Sponsor)
//  • หลังหัก Sponsor = ราคาเต็มทั้งผัง − ที่นั่งที่กันให้ Sponsor (เพดานที่ยังขายได้)
//  • ถ้าขายหมด       = ราคาเต็มทั้งผัง
function updateRevenue() {
  let full = 0, sponsorRev = 0, sold = 0;
  document.querySelectorAll('.seat[data-zone]').forEach(el => {
    const price = PRICES[el.dataset.zone] || 0;
    if (price <= 0) return;
    const key = el.dataset.key;
    full += price;
    if (SPONSOR.has(key))      sponsorRev += price;
    else if (BOOKED.has(key))  sold       += price;
  });
  const fmt = n => '฿' + n.toLocaleString('th-TH');
  document.getElementById('stat-sold-revenue').textContent     = fmt(sold);
  document.getElementById('stat-sellable-revenue').textContent = fmt(full - sponsorRev);
  document.getElementById('stat-full-revenue').textContent     = fmt(full);
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

// ── Close modals on backdrop click ──────────────────────────────
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

{{-- ─── Pusher Real-time ──────────────────────────────────────── --}}
<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
(function () {
  const pusher  = new Pusher('{{ config("broadcasting.connections.pusher.key") }}', { cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}' });
  const channel = pusher.subscribe('suntaraporn-concert-' + SHOW_DATE);

  channel.bind('zone-config-updated', function (data) {
    if (data.zones && data.row_zones) {
      applyZoneConfig(data.zones, data.row_zones);
    }
  });

  channel.bind('seat-status-updated', function (data) {
    // ที่นั่งถูกจองแล้ว
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

    // ที่นั่ง Sponsor (กันให้ผู้สนับสนุน) — แอดมินเห็นเป็นสีทองทับ booked
    (data.sponsor_keys || []).forEach(function (key) {
      if (!SPONSOR.has(key)) SPONSOR.set(key, 'Sponsor');
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.add('is-sponsor');
        el.setAttribute('title', '🎁 Sponsor');
      });
    });

    // ที่นั่งถูกปล่อยว่าง (ยกเลิกจอง / ยกเลิก Sponsor)
    (data.freed_keys || []).forEach(function (key) {
      BOOKED.delete(key);
      SPONSOR.delete(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.remove('is-booked', 'is-sponsor');
        el.removeAttribute('title');
      });
    });

    // ที่นั่งกำลังถูกเลือกโดยผู้อื่น → สีส้ม 🔒
    (data.selecting_keys || []).forEach(function (key) {
      if (SELECTED.has(key) || BOOKED.has(key)) return; // ฉันเลือกอยู่ หรือจองแล้ว ไม่ต้องอัพเดท
      SELECTING_OTHERS.add(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.add('is-selecting');
        el.setAttribute('title', 'กำลังถูกเลือกอยู่');
      });
    });

    // ที่นั่งถูกปล่อยออก (deselect) → กลับว่าง
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

window.addEventListener('pageshow', e => { if (e.persisted) location.reload(); });
</script>

{{-- ─── Polling Fallback (ถ้า Pusher ไม่ deliver) ──────────────── --}}
<script>
(function () {
  var selectingTimestamp = SELECTED.size > 0 ? Date.now() : null;
  var expireNotified     = false;
  var SELECTING_TTL_MS   = 180000; // 3 minutes in ms

  // ── ติดตาม timestamp เมื่อเลือกที่นั่ง ──
  var origToggle = window.toggleSeat;
  window.toggleSeat = function (el) {
    origToggle(el);
    if (SELECTED.size > 0 && !selectingTimestamp) {
      selectingTimestamp = Date.now();
      expireNotified = false;
    } else if (SELECTED.size === 0) {
      selectingTimestamp = null;
      expireNotified = false;
      hideExpireNotice();
    }
  };

  function showExpireNotice() {
    if (document.getElementById('expire-notice')) return;
    var div = document.createElement('div');
    div.id = 'expire-notice';
    div.style.cssText = 'position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:200;background:#FF8F00;color:#fff;padding:12px 20px;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.3);font-size:14px;font-weight:600;display:flex;align-items:center;gap:10px;font-family:inherit;';
    div.innerHTML = '⚠️ ที่นั่งที่เลือกไว้อาจหลุดแล้ว — <button onclick="location.reload()" style="background:#fff;color:#FF8F00;border:none;border-radius:6px;padding:6px 14px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;">🔄 รีเฟรชหน้าเว็บ</button>';
    document.body.appendChild(div);
  }

  function hideExpireNotice() {
    var el = document.getElementById('expire-notice');
    if (el) el.remove();
  }

  function applyState(data) {
    var newBooked     = new Set(data.booked    || []);
    var newSelecting  = new Set(data.selecting || []);
    var newSponsor    = new Set(data.sponsor   || []);

    // sync BOOKED
    newBooked.forEach(function (key) {
      if (BOOKED.has(key)) return;
      SELECTED.delete(key);
      SELECTING_OTHERS.delete(key);
      BOOKED.add(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.remove('is-selected', 'is-selecting');
        el.classList.add('is-booked');
        el.removeAttribute('title');
      });
    });
    BOOKED.forEach(function (key) {
      if (newBooked.has(key)) return;
      BOOKED.delete(key);
      SPONSOR.delete(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.remove('is-booked', 'is-sponsor');
        el.removeAttribute('title');
      });
    });

    // sync SPONSOR (ทับสีทองบน booked)
    newSponsor.forEach(function (key) {
      if (!SPONSOR.has(key)) SPONSOR.set(key, 'Sponsor');
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.add('is-sponsor');
        el.setAttribute('title', '🎁 Sponsor');
      });
    });
    SPONSOR.forEach(function (note, key) {
      if (newSponsor.has(key)) return;
      SPONSOR.delete(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.remove('is-sponsor');
        if (!BOOKED.has(key)) el.removeAttribute('title');
      });
    });

    // sync SELECTING_OTHERS (ไม่แตะ SELECTED ของตัวเอง)
    newSelecting.forEach(function (key) {
      if (SELECTED.has(key) || BOOKED.has(key) || SELECTING_OTHERS.has(key)) return;
      SELECTING_OTHERS.add(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        el.classList.add('is-selecting');
        el.setAttribute('title', 'กำลังถูกเลือกอยู่');
      });
    });
    SELECTING_OTHERS.forEach(function (key) {
      if (newSelecting.has(key)) return;
      SELECTING_OTHERS.delete(key);
      document.querySelectorAll('[data-key="' + key + '"]').forEach(function (el) {
        if (!BOOKED.has(key) && !SELECTED.has(key)) {
          el.classList.remove('is-selecting');
          el.removeAttribute('title');
        }
      });
    });

    // ── ตรวจว่า selecting อาจ expire (3 นาที) → แจ้งให้ refresh ──
    if (SELECTED.size > 0 && selectingTimestamp && !expireNotified) {
      if (Date.now() - selectingTimestamp > 180000) {
        expireNotified = true;
        showExpireNotice();
      }
    }

    updateStats();
    updateSummary();
    updateFloatBtn();
  }

  function poll() {
    fetch('/SuntarapornBand/live-state?date=' + encodeURIComponent(SHOW_DATE))
      .then(function (r) { return r.json(); })
      .then(applyState)
      .catch(function () {});
  }

  setInterval(poll, 5000);
})();
</script>

{{-- ─── Reset Confirm Modal ────────────────────────────────────── --}}
<style>
#resetModal { display:none; position:fixed; inset:0; z-index:9000; background:rgba(0,0,0,.55); align-items:center; justify-content:center; padding:16px; }
#resetModal.open { display:flex; }
#resetModal .modal-box { background:#fff; border-radius:14px; padding:28px 24px; max-width:380px; width:100%; box-shadow:0 8px 32px rgba(0,0,0,.25); }
#resetModal .modal-title { font-size:17px; font-weight:800; color:#c62828; margin-bottom:6px; }
#resetModal .modal-sub { font-size:13px; color:#555; margin-bottom:18px; line-height:1.5; }
#resetModal .modal-label { font-size:13px; font-weight:700; color:#333; margin-bottom:6px; }
#resetModal input { width:100%; border:1.5px solid #ddd; border-radius:8px; padding:10px 12px; font-size:14px; font-family:'Sarabun',sans-serif; outline:none; margin-bottom:16px; transition:border-color .15s; }
#resetModal input:focus { border-color:#e53935; }
#resetModal .modal-actions { display:flex; gap:10px; justify-content:flex-end; }
#resetModal .btn-cancel { background:#f5f5f5; color:#555; border:none; border-radius:8px; padding:9px 20px; font-size:13px; font-weight:700; cursor:pointer; font-family:'Sarabun',sans-serif; }
#resetModal .btn-confirm { background:#c62828; color:#fff; border:none; border-radius:8px; padding:9px 20px; font-size:13px; font-weight:700; cursor:pointer; font-family:'Sarabun',sans-serif; opacity:.4; transition:opacity .15s; }
#resetModal .btn-confirm.ready { opacity:1; cursor:pointer; }
</style>
<div id="resetModal">
  <div class="modal-box">
    <div class="modal-title">⚠️ รีเซ็ตที่นั่งทั้งหมด</div>
    <div class="modal-sub">การรีเซ็ตจะลบข้อมูลการจองและสลิปทั้งหมดของรอบนี้ถาวร ไม่สามารถย้อนกลับได้</div>
    <div class="modal-label">พิมพ์ <strong>confirm</strong> เพื่อยืนยัน</div>
    <input type="text" id="resetConfirmInput" placeholder="พิมพ์ confirm ที่นี่" autocomplete="off" oninput="onResetInput()">
    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeResetModal()">ยกเลิก</button>
      <button class="btn-confirm" id="resetConfirmBtn" disabled onclick="doReset()">รีเซ็ต</button>
    </div>
  </div>
</div>
<script>
function openResetModal() {
  document.getElementById('resetConfirmInput').value = '';
  document.getElementById('resetConfirmBtn').disabled = true;
  document.getElementById('resetConfirmBtn').classList.remove('ready');
  document.getElementById('resetModal').classList.add('open');
  setTimeout(() => document.getElementById('resetConfirmInput').focus(), 50);
}
function closeResetModal() {
  document.getElementById('resetModal').classList.remove('open');
}
function onResetInput() {
  var val = document.getElementById('resetConfirmInput').value.trim().toLowerCase();
  var btn = document.getElementById('resetConfirmBtn');
  btn.disabled = val !== 'confirm';
  btn.classList.toggle('ready', val === 'confirm');
}
function doReset() {
  closeResetModal();
  resetAllSeats();
}
document.getElementById('resetModal').addEventListener('click', function(e) {
  if (e.target === this) closeResetModal();
});
</script>
</body>
</html>
