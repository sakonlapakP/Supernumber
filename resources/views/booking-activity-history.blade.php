<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @php
    $titleMap = [
      'likay'       => 'ลิเกไลฟ์อินเดอะเธียเตอร์',
      'suntaraporn' => 'สุนทราภรณ์',
    ];
    $badgeMap = [
      'book'   => ['label' => 'จอง/ขาย',       'icon' => '🟢', 'bg' => '#e8f5e9', 'fg' => '#2e7d32'],
      'cancel' => ['label' => 'ยกเลิก',         'icon' => '🔴', 'bg' => '#ffebee', 'fg' => '#c62828'],
      'reset'  => ['label' => 'รีเซ็ตทั้งหมด',   'icon' => '🟠', 'bg' => '#fff3e0', 'fg' => '#e65100'],
      'search' => ['label' => 'ค้นหา',          'icon' => '🔵', 'bg' => '#e3f2fd', 'fg' => '#1565c0'],
    ];
    $sysTitle = $titleMap[$system] ?? $system;
    // $counts ส่งมาจาก controller (สรุปทั้งช่วง ไม่ใช่แค่หน้าปัจจุบัน)
  @endphp
  <title>ประวัติการทำรายการ — {{ $sysTitle }}</title>
  <link rel="shortcut icon" type="image/x-icon" href="/favicon-v2.ico?v=supernumber-20260602">
  <link rel="icon" type="image/svg+xml" sizes="any" href="/favicon.svg?v=supernumber-20260602">
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

    .filter-bar {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: flex-end;
      background: #fff;
      border-radius: 10px;
      padding: 14px 16px;
      margin-bottom: 14px;
      box-shadow: 0 1px 4px rgba(0,0,0,.1);
    }
    .filter-field { display: flex; flex-direction: column; gap: 4px; }
    .filter-field label { font-size: 11px; color: #777; font-weight: 600; }
    .filter-field select,
    .filter-field input {
      padding: 8px 12px;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      font-family: inherit;
      font-size: 14px;
      outline: none;
      transition: border-color .15s;
    }
    .filter-field select:focus,
    .filter-field input:focus { border-color: #1a1a2e; }

    .date-selector { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
    .date-chip {
      padding: 7px 16px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
    }

    .table-wrap {
      background: #fff;
      border-radius: 12px;
      padding: 16px;
      box-shadow: 0 1px 4px rgba(0,0,0,.1);
      overflow-x: auto;
    }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
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

    .action-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      border-radius: 6px;
      padding: 3px 10px;
      font-size: 12px;
      font-weight: 700;
      white-space: nowrap;
    }
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
    .price-badge { font-weight: 700; color: #1a1a2e; }
    .muted { color: #999; font-size: 12px; }

    .empty-state { text-align: center; padding: 48px 16px; color: #aaa; }
    .empty-state .icon { font-size: 40px; margin-bottom: 12px; }
    .empty-state p { font-size: 15px; }

    @media (max-width: 640px) {
      body { padding: 8px; }
      .page-title { font-size: 18px; }
    }
  </style>
</head>
<body>

<div class="page-header">
  <div>
    <div class="page-title">🕓 ประวัติการทำรายการ</div>
    <div class="page-sub">{{ $sysTitle }} · สวัสดี, {{ $user->name }}</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <a href="{{ route($system . '.bookings', $system === 'suntaraporn' ? ['date' => $showDate] : []) }}" class="btn btn-outline">📋 รายการจอง</a>
    <a href="{{ route($system . '.index', $system === 'suntaraporn' ? ['date' => $showDate] : []) }}" class="btn btn-outline">← ผังที่นั่ง</a>
    <form method="POST" action="{{ route($system . '.logout') }}" style="margin:0;">
      @csrf
      <button type="submit" class="btn btn-outline">ออกจากระบบ</button>
    </form>
  </div>
</div>

@if ($system === 'suntaraporn')
<div class="date-selector">
  @foreach ($showDates as $d => $label)
    <a href="{{ route('suntaraporn.history', array_filter(['date' => $d, 'action' => $action, 'from' => $from, 'to' => $to])) }}"
       class="date-chip"
       style="border:1.5px solid {{ $showDate === $d ? '#1a1a2e' : '#ddd' }};background:{{ $showDate === $d ? '#1a1a2e' : '#fff' }};color:{{ $showDate === $d ? '#fff' : '#555' }};">
      📅 {{ $label }}
    </a>
  @endforeach
</div>
@endif

<form method="GET" action="{{ route($system . '.history') }}" class="filter-bar">
  @if ($system === 'suntaraporn')
    <input type="hidden" name="date" value="{{ $showDate }}">
  @endif
  <div class="filter-field">
    <label>ประเภท</label>
    <select name="action">
      <option value="">ทั้งหมด</option>
      @foreach ($badgeMap as $key => $b)
        <option value="{{ $key }}" {{ $action === $key ? 'selected' : '' }}>{{ $b['icon'] }} {{ $b['label'] }}</option>
      @endforeach
    </select>
  </div>
  <div class="filter-field">
    <label>ตั้งแต่วันที่</label>
    <input type="date" name="from" value="{{ $from }}">
  </div>
  <div class="filter-field">
    <label>ถึงวันที่</label>
    <input type="date" name="to" value="{{ $to }}">
  </div>
  <button type="submit" class="btn btn-primary">กรอง</button>
  @if ($action || $from || $to)
    <a href="{{ route($system . '.history', $system === 'suntaraporn' ? ['date' => $showDate] : []) }}" class="btn btn-outline">✕ ล้าง</a>
  @endif
</form>

<div class="stats-bar">
  <div class="stat-item">
    <span class="stat-num">{{ $counts->sum() }}</span>
    <span class="stat-lbl">รายการทั้งหมด</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" style="color:#2e7d32">{{ $counts['book'] ?? 0 }}</span>
    <span class="stat-lbl">จอง/ขาย</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" style="color:#c62828">{{ $counts['cancel'] ?? 0 }}</span>
    <span class="stat-lbl">ยกเลิก</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" style="color:#e65100">{{ $counts['reset'] ?? 0 }}</span>
    <span class="stat-lbl">รีเซ็ต</span>
  </div>
  <div class="stat-item">
    <span class="stat-num" style="color:#1565c0">{{ $counts['search'] ?? 0 }}</span>
    <span class="stat-lbl">ค้นหา</span>
  </div>
</div>

<div class="table-wrap">
  @if ($logs->isEmpty())
    <div class="empty-state">
      <div class="icon">🕓</div>
      <p>ยังไม่มีประวัติการทำรายการ</p>
    </div>
  @else
    <table>
      <thead>
        <tr>
          <th>เวลา</th>
          <th>ประเภท</th>
          <th>ผู้ทำรายการ</th>
          <th>รายละเอียด</th>
          <th>ยอด</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($logs as $log)
        @php $b = $badgeMap[$log->action] ?? ['label' => $log->action, 'icon' => '•', 'bg' => '#eee', 'fg' => '#555']; @endphp
        <tr>
          <td style="white-space:nowrap;color:#777;font-size:13px;">
            {{ $log->created_at?->format('d/m/Y H:i') ?? '-' }}
          </td>
          <td>
            <span class="action-badge" style="background:{{ $b['bg'] }};color:{{ $b['fg'] }};">{{ $b['icon'] }} {{ $b['label'] }}</span>
          </td>
          <td style="color:#555;white-space:nowrap;">{{ $log->actor_name }}</td>
          <td>
            @if ($log->action === 'search')
              <span class="muted">คำค้นหา:</span> <strong>{{ $log->search_query }}</strong>
            @elseif ($log->action === 'reset')
              <span class="muted">รีเซ็ตที่นั่งทั้งหมด {{ count($log->seat_keys ?? []) }} ที่นั่ง</span>
            @else
              @if ($log->customer_name)
                <div style="font-weight:600;">{{ $log->customer_name }}
                  @if ($log->phone)<span class="muted">· {{ $log->phone }}</span>@endif
                </div>
              @endif
              @if (!empty($log->seat_keys))
                <div style="display:flex;flex-wrap:wrap;gap:2px;max-width:260px;margin-top:3px;">
                  @foreach ($log->seat_keys as $key)
                    <span class="seat-chip">{{ $key }}</span>
                  @endforeach
                </div>
              @endif
            @endif
          </td>
          <td>
            @if ($log->total_price !== null)
              <span class="price-badge">฿{{ number_format($log->total_price) }}</span>
            @else
              <span class="muted">-</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @if ($logs->hasPages())
      <div style="display:flex;justify-content:center;align-items:center;gap:10px;margin-top:16px;flex-wrap:wrap;">
        @if ($logs->onFirstPage())
          <span class="btn btn-outline btn-sm" style="opacity:.4;cursor:default;">← ก่อนหน้า</span>
        @else
          <a href="{{ $logs->previousPageUrl() }}" class="btn btn-outline btn-sm">← ก่อนหน้า</a>
        @endif
        <span class="muted">หน้า {{ $logs->currentPage() }} / {{ $logs->lastPage() }} ({{ number_format($logs->total()) }} รายการ)</span>
        @if ($logs->hasMorePages())
          <a href="{{ $logs->nextPageUrl() }}" class="btn btn-outline btn-sm">ถัดไป →</a>
        @else
          <span class="btn btn-outline btn-sm" style="opacity:.4;cursor:default;">ถัดไป →</span>
        @endif
      </div>
    @endif
  @endif
</div>

</body>
</html>
