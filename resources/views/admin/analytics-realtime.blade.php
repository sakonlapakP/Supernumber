@extends('layouts.admin')

@section('title', 'Supernumber Admin | Realtime Analytics')

@section('content')
  @php
    $active = (int) ($realtimeData['activeUsers'] ?? 0);
    $pages = $realtimeData['pages'] ?? [];
    $devices = $realtimeData['devices'] ?? [];
    $countries = $realtimeData['countries'] ?? [];
  @endphp

  <style>
    .rt-grid {
      display: grid;
      gap: 18px;
    }

    .rt-two-up {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .rt-hero {
      display: flex;
      align-items: center;
      gap: 20px;
      padding: 24px 26px;
      background: #f0f8f4;
      border: 1px solid #c3e4d8;
      border-radius: 20px;
    }

    .rt-hero__pulse {
      flex-shrink: 0;
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background: #22b87d;
      box-shadow: 0 0 0 0 rgba(34, 184, 125, 0.4);
      animation: rt-pulse 2s infinite;
    }

    @keyframes rt-pulse {
      0%   { box-shadow: 0 0 0 0 rgba(34, 184, 125, 0.5); }
      70%  { box-shadow: 0 0 0 10px rgba(34, 184, 125, 0); }
      100% { box-shadow: 0 0 0 0 rgba(34, 184, 125, 0); }
    }

    .rt-hero__count {
      font-size: 48px;
      font-weight: 900;
      line-height: 1;
      color: #1a3a2e;
    }

    .rt-hero__label {
      font-size: 14px;
      font-weight: 700;
      color: #3a8265;
      margin-top: 4px;
    }

    .rt-hero__meta {
      margin-left: auto;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 6px;
    }

    .rt-refresh-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      border-radius: 999px;
      background: #e8f5f0;
      border: 1px solid #c3e4d8;
      font-size: 12px;
      font-weight: 700;
      color: #3a8265;
    }

    .rt-refresh-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #22b87d;
    }

    .rt-countdown {
      font-size: 12px;
      color: #6a9987;
      font-weight: 600;
    }

    .rt-bar-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 0;
      border-bottom: 1px solid #edf2f8;
    }

    .rt-bar-row:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .rt-bar-label {
      flex: 1;
      min-width: 0;
      font-size: 13px;
      color: #2a3f5c;
      font-weight: 600;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .rt-bar-wrap {
      width: 120px;
      height: 8px;
      background: #e8eef8;
      border-radius: 999px;
      overflow: hidden;
      flex-shrink: 0;
    }

    .rt-bar-fill {
      height: 100%;
      border-radius: 999px;
      background: #3a7bd5;
      transition: width 0.4s ease;
    }

    .rt-bar-count {
      flex-shrink: 0;
      width: 28px;
      text-align: right;
      font-size: 13px;
      font-weight: 800;
      color: #1c2f4c;
    }

    @media (max-width: 840px) {
      .rt-two-up {
        grid-template-columns: minmax(0, 1fr);
      }
    }
  </style>

  <div class="admin-page-head">
    <div>
      <h1>Realtime Analytics</h1>
      <p class="admin-subtitle">ดูว่ามีคนใช้งานเว็บอยู่กี่คนและดูหน้าอะไรอยู่ในขณะนี้ (อิงจาก GA4 ช่วง 30 นาทีล่าสุด)</p>
    </div>
    <a href="{{ route('admin.analytics') }}" class="admin-button admin-button--muted admin-button--compact">
      ดู Analytics ทั้งหมด
    </a>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  @if ($realtimeError)
    <div class="admin-alert admin-alert--error">GA4 Realtime เชื่อมต่อไม่สำเร็จ: {{ $realtimeError }}</div>
  @endif

  @if (! $ga4ConfiguredForReporting)
    <div class="admin-alert admin-alert--error">ยังไม่ได้ตั้งค่า GA4 Property ID และ Service Account — ไปที่ <a href="{{ route('admin.analytics') }}">Analytics GA4</a> เพื่อตั้งค่า</div>
  @endif

  <div class="rt-grid">
    {{-- Hero: active users count --}}
    <div class="rt-hero">
      <div class="rt-hero__pulse"></div>
      <div>
        <div class="rt-hero__count">{{ $active }}</div>
        <div class="rt-hero__label">ผู้ใช้งานอยู่ในขณะนี้</div>
      </div>
      <div class="rt-hero__meta">
        <div class="rt-refresh-badge">
          <span class="rt-refresh-dot"></span>
          อัพเดทอัตโนมัติทุก 30 วิ
        </div>
        <div class="rt-countdown" id="rt-countdown">รีเฟรชใน <span id="rt-seconds">30</span> วิ</div>
      </div>
    </div>

    {{-- Pages --}}
    <section class="admin-card admin-feature-card">
      <div class="admin-feature-card__head">
        <div>
          <h2 class="admin-feature-card__title">หน้าที่คนดูอยู่ตอนนี้</h2>
          <p class="admin-feature-card__hint">แสดงสูงสุด 15 หน้า เรียงจากผู้ใช้งานมากไปน้อย</p>
        </div>
      </div>

      @php
        $maxPageUsers = (int) ($pages[0]['activeUsers'] ?? 0);
      @endphp

      @if (count($pages) > 0)
        <div style="padding: 0 20px 20px;">
          @foreach ($pages as $row)
            @php
              $pageUsers = (int) ($row['activeUsers'] ?? 0);
              $barWidth = $maxPageUsers > 0 ? round(($pageUsers / $maxPageUsers) * 100) : 0;
            @endphp
            <div class="rt-bar-row">
              <div class="rt-bar-label" title="{{ $row['unifiedPageScreen'] ?? '/' }}">
                {{ $row['unifiedPageScreen'] ?? '/' }}
              </div>
              <div class="rt-bar-wrap">
                <div class="rt-bar-fill" style="width: {{ $barWidth }}%;"></div>
              </div>
              <div class="rt-bar-count">{{ $pageUsers }}</div>
            </div>
          @endforeach
        </div>
      @else
        <div style="padding: 0 20px 20px;" class="admin-muted">
          ยังไม่มีผู้ใช้งานอยู่ในขณะนี้
        </div>
      @endif
    </section>

    <div class="rt-two-up">
      {{-- Devices --}}
      <section class="admin-card admin-feature-card">
        <div class="admin-feature-card__head">
          <div>
            <h2 class="admin-feature-card__title">อุปกรณ์</h2>
            <p class="admin-feature-card__hint">ผู้ใช้งานแบ่งตามประเภทอุปกรณ์</p>
          </div>
        </div>
        @php $maxDeviceUsers = (int) ($devices[0]['activeUsers'] ?? 0); @endphp
        <div style="padding: 0 20px 20px;">
          @forelse ($devices as $row)
            @php
              $n = (int) ($row['activeUsers'] ?? 0);
              $bw = $maxDeviceUsers > 0 ? round(($n / $maxDeviceUsers) * 100) : 0;
            @endphp
            <div class="rt-bar-row">
              <div class="rt-bar-label">{{ ucfirst($row['deviceCategory'] ?? '-') }}</div>
              <div class="rt-bar-wrap">
                <div class="rt-bar-fill" style="width: {{ $bw }}%;"></div>
              </div>
              <div class="rt-bar-count">{{ $n }}</div>
            </div>
          @empty
            <p class="admin-muted">ยังไม่มีข้อมูลอุปกรณ์</p>
          @endforelse
        </div>
      </section>

      {{-- Countries --}}
      <section class="admin-card admin-feature-card">
        <div class="admin-feature-card__head">
          <div>
            <h2 class="admin-feature-card__title">ประเทศ</h2>
            <p class="admin-feature-card__hint">ผู้ใช้งานแบ่งตามประเทศ</p>
          </div>
        </div>
        @php $maxCountryUsers = (int) ($countries[0]['activeUsers'] ?? 0); @endphp
        <div style="padding: 0 20px 20px;">
          @forelse ($countries as $row)
            @php
              $n = (int) ($row['activeUsers'] ?? 0);
              $bw = $maxCountryUsers > 0 ? round(($n / $maxCountryUsers) * 100) : 0;
            @endphp
            <div class="rt-bar-row">
              <div class="rt-bar-label">{{ $row['country'] ?? '-' }}</div>
              <div class="rt-bar-wrap">
                <div class="rt-bar-fill" style="width: {{ $bw }}%;"></div>
              </div>
              <div class="rt-bar-count">{{ $n }}</div>
            </div>
          @empty
            <p class="admin-muted">ยังไม่มีข้อมูลประเทศ</p>
          @endforelse
        </div>
      </section>
    </div>
  </div>

  <script>
    (() => {
      let remaining = 30;
      const el = document.getElementById("rt-seconds");

      const tick = () => {
        remaining -= 1;
        if (el) el.textContent = remaining;
        if (remaining <= 0) {
          window.location.reload();
        }
      };

      setInterval(tick, 1000);
    })();
  </script>
@endsection
