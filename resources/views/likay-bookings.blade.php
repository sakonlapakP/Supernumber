<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>รายการจอง — ลิเกไลฟ์อินเดอะเธียเตอร์</title>
  <link rel="shortcut icon" type="image/x-icon" href="/favicon-v2.ico?v=supernumber-20260602">
  <link rel="icon" type="image/svg+xml" sizes="any" href="/favicon.svg?v=supernumber-20260602">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=supernumber-20260602">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=supernumber-20260602">
  <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=supernumber-20260602">
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Sarabun', sans-serif;
      background: #f0f0f0;
      min-height: 100vh;
      padding: 16px;
    }

    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 16px;
    }
    .page-title { font-size: 22px; font-weight: 800; color: #1a1a1a; }
    .page-sub   { font-size: 13px; color: #777; margin-top: 2px; }

    .btn {
      padding: 8px 16px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      font-family: inherit;
      font-size: 14px;
      font-weight: 600;
      transition: opacity .15s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn:hover { opacity: .85; }
    .btn-primary { background: #1a1a2e; color: #fff; }
    .btn-outline { background: transparent; border: 1.5px solid #ccc; color: #333; }
    .btn-danger  { background: #e53935; color: #fff; }
    .btn-sm { padding: 5px 12px; font-size: 13px; }

    .stats-bar {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      background: #fff;
      border-radius: 10px;
      padding: 12px 18px;
      margin-bottom: 14px;
      box-shadow: 0 1px 4px rgba(0,0,0,.1);
    }
    .stat-item { display: flex; flex-direction: column; align-items: center; gap: 2px; }
    .stat-num  { font-size: 22px; font-weight: 800; }
    .stat-lbl  { color: #777; font-size: 11px; }

    .table-wrap {
      background: #fff;
      border-radius: 12px;
      padding: 16px;
      box-shadow: 0 1px 4px rgba(0,0,0,.1);
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }
    th {
      text-align: left;
      padding: 10px 12px;
      border-bottom: 2px solid #eee;
      font-size: 12px;
      font-weight: 700;
      color: #777;
      text-transform: uppercase;
      letter-spacing: .5px;
      white-space: nowrap;
    }
    td {
      padding: 10px 12px;
      border-bottom: 1px solid #f0f0f0;
      vertical-align: middle;
    }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #fafafa; }

    .seat-chip {
      display: inline-block;
      background: #1a1a2e;
      color: #fff;
      border-radius: 4px;
      padding: 2px 7px;
      font-size: 11px;
      font-weight: 600;
      margin: 1px;
    }
    .price-badge {
      font-weight: 700;
      color: #1a1a2e;
    }
    .slip-thumb {
      width: 48px;
      height: 48px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid #ddd;
      cursor: pointer;
      transition: transform .15s;
    }
    .slip-thumb:hover { transform: scale(1.08); }
    .no-slip { color: #bbb; font-size: 12px; }

    .empty-state {
      text-align: center;
      padding: 48px 16px;
      color: #aaa;
    }
    .empty-state .icon { font-size: 40px; margin-bottom: 12px; }
    .empty-state p { font-size: 15px; }

    .lightbox {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,.85);
      z-index: 200;
      align-items: center;
      justify-content: center;
    }
    .lightbox.open { display: flex; }
    .lightbox img {
      max-width: 90vw;
      max-height: 90vh;
      border-radius: 8px;
      box-shadow: 0 8px 32px rgba(0,0,0,.5);
    }
    .lightbox-close {
      position: fixed;
      top: 16px; right: 20px;
      color: #fff;
      font-size: 28px;
      cursor: pointer;
      font-weight: 700;
      line-height: 1;
    }

    .search-bar {
      display: flex;
      gap: 8px;
      margin-bottom: 14px;
      flex-wrap: wrap;
    }
    .search-input {
      flex: 1;
      min-width: 200px;
      padding: 9px 14px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-family: inherit;
      font-size: 14px;
      outline: none;
      transition: border-color .15s;
    }
    .search-input:focus { border-color: #1a1a2e; }

    @media (max-width: 640px) {
      body { padding: 8px; }
      .page-title { font-size: 18px; }
    }
  </style>
</head>
<body>

<div class="lightbox" id="lightbox" onclick="closeLightbox()">
  <span class="lightbox-close">✕</span>
  <img id="lightbox-img" src="" alt="slip">
</div>

<div class="page-header">
  <div>
    <div class="page-title">📋 รายการจองทั้งหมด</div>
    <div class="page-sub">สวัสดี, {{ $user->name }}</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <a href="{{ route('likay.export') }}" class="btn" style="background:#1b5e20;color:#fff;">📥 Export Excel</a>
    <a href="{{ route('likay.index') }}" class="btn btn-outline">← ผังที่นั่ง</a>
    <form method="POST" action="{{ route('likay.logout') }}" style="margin:0;">
      @csrf
      <button type="submit" class="btn btn-outline">ออกจากระบบ</button>
    </form>
  </div>
</div>

<form method="GET" action="{{ route('likay.bookings') }}" class="search-bar">
  <input
    type="text"
    name="search"
    class="search-input"
    placeholder="🔍 ค้นหาชื่อหรือเบอร์โทร..."
    value="{{ $search }}"
    autocomplete="off"
  >
  @if ($search)
    <a href="{{ route('likay.bookings') }}" class="btn btn-outline">✕ ล้าง</a>
  @endif
  <button type="submit" class="btn btn-primary">ค้นหา</button>
</form>

@php
  $totalSeats    = $bookings->sum(fn($b) => $b->seats->count());
  $totalRevenue  = $bookings->sum('total_price');
@endphp
<div class="stats-bar">
  <div class="stat-item">
    <span class="stat-num">{{ $bookings->count() }}</span>
    <span class="stat-lbl">รายการจอง</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" style="color:#1a1a2e">{{ $totalSeats }}</span>
    <span class="stat-lbl">ที่นั่งที่จอง</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" style="color:#2e7d32">฿{{ number_format($totalRevenue) }}</span>
    <span class="stat-lbl">ยอดรวมทั้งหมด</span>
  </div>
</div>

<div class="table-wrap">
  @if ($bookings->isEmpty())
    <div class="empty-state">
      <div class="icon">🎟️</div>
      @if ($search)
        <p>ไม่พบรายการที่ตรงกับ "<strong>{{ $search }}</strong>"</p>
      @else
        <p>ยังไม่มีรายการจอง</p>
      @endif
    </div>
  @else
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>ที่นั่ง</th>
          <th>ชื่อ-นามสกุล</th>
          <th>เบอร์ติดต่อ</th>
          <th>ผู้รับจอง</th>
          <th>ยอด</th>
          <th>Slip</th>
          <th>วันที่จอง</th>
          @if ($user->role === 'manager')
          <th></th>
          @endif
        </tr>
      </thead>
      <tbody>
        @foreach ($bookings as $booking)
        <tr id="row-{{ $booking->id }}">
          <td style="color:#999;font-size:12px;">{{ $booking->id }}</td>
          <td>
            <div style="display:flex;flex-wrap:wrap;gap:2px;max-width:200px;">
              @foreach ($booking->seats as $seat)
                <span class="seat-chip">{{ $seat->seat_key }}</span>
              @endforeach
            </div>
          </td>
          <td>
            <div style="font-weight:600;">{{ $booking->first_name }} {{ $booking->last_name }}</div>
          </td>
          <td>{{ $booking->phone }}</td>
          <td style="color:#555;">{{ $booking->booker_name }}</td>
          <td><span class="price-badge">฿{{ number_format($booking->total_price) }}</span></td>
          <td>
            @if ($booking->slip_path)
              @php $ext = strtolower(pathinfo($booking->slip_path, PATHINFO_EXTENSION)); @endphp
              @if (in_array($ext, ['jpg','jpeg','png']))
                <img
                  class="slip-thumb"
                  src="{{ asset('storage/' . $booking->slip_path) }}"
                  alt="slip"
                  onclick="openLightbox('{{ asset('storage/' . $booking->slip_path) }}')"
                >
              @else
                <a href="{{ asset('storage/' . $booking->slip_path) }}" target="_blank" class="btn btn-outline btn-sm">📄 PDF</a>
              @endif
            @else
              <span class="no-slip">ไม่มี slip</span>
            @endif
          </td>
          <td style="white-space:nowrap;color:#777;font-size:13px;">
            {{ $booking->created_at->format('d/m/Y H:i') }}
          </td>
          @if ($user->role === 'manager')
          <td>
            <button class="btn btn-danger btn-sm" onclick="cancelBooking({{ $booking->id }}, this)">ยกเลิก</button>
          </td>
          @endif
        </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

function openLightbox(url) {
  document.getElementById('lightbox-img').src = url;
  document.getElementById('lightbox').classList.add('open');
}
function closeLightbox() {
  document.getElementById('lightbox').classList.remove('open');
}

async function cancelBooking(id, btn) {
  if (!confirm('ยืนยันยกเลิกการจองนี้? ที่นั่งทั้งหมดใน booking จะถูกปล่อยว่าง')) return;

  btn.disabled = true;
  btn.textContent = 'กำลังยกเลิก...';

  try {
    const res  = await fetch(`/LikayLiveInTheater/booking/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': CSRF }
    });
    const data = await res.json();

    if (data.success) {
      const row = document.getElementById(`row-${id}`);
      row.style.transition = 'opacity .3s';
      row.style.opacity = '0';
      setTimeout(() => row.remove(), 300);
    } else {
      alert(data.error || 'เกิดข้อผิดพลาด');
      btn.disabled = false;
      btn.textContent = 'ยกเลิก';
    }
  } catch {
    alert('เกิดข้อผิดพลาด');
    btn.disabled = false;
    btn.textContent = 'ยกเลิก';
  }
}
</script>
</body>
</html>
