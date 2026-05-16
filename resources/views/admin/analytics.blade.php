@extends('layouts.admin')

@section('title', 'Supernumber Admin | Analytics GA4')

@section('content')
  @php
    $gaSummary = $ga4Dashboard['summary'] ?? [];
    $gaDaily = $ga4Dashboard['daily'] ?? [];
    $gaSources = $ga4Dashboard['sources'] ?? [];
    $gaPages = $ga4Dashboard['pages'] ?? [];
    $gaEvents = $ga4Dashboard['events'] ?? [];
    $gaDevices = $ga4Dashboard['devices'] ?? [];
    $gaCountries = $ga4Dashboard['countries'] ?? [];
    $formatNumber = static fn ($value): string => number_format((float) $value, (is_float($value) || (is_numeric($value) && floor((float) $value) !== (float) $value)) ? 2 : 0);
    $formatPercent = static fn ($value): string => number_format(((float) $value) * 100, 1) . '%';
    $formatDuration = static function ($seconds): string {
        $seconds = max(0, (int) round((float) $seconds));
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    };
    $formatGaDate = static function (?string $date): string {
        $date = trim((string) $date);
        if ($date === '' || strlen($date) !== 8) return '-';
        try {
            return \Illuminate\Support\Carbon::createFromFormat('Ymd', $date, 'Asia/Bangkok')
                ->locale('th')->translatedFormat('j M Y');
        } catch (\Throwable) { return $date; }
    };
  @endphp

  <style>
    .analytics-card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 14px;
    }

    .analytics-stat-card {
      padding: 16px 18px;
      border: 1px solid #dbe6f4;
      border-radius: 18px;
      background: #f8fbff;
    }

    .analytics-stat-card__label {
      color: #6a7f9e;
      font-size: 12px;
      font-weight: 700;
      line-height: 1.4;
    }

    .analytics-stat-card__value {
      margin-top: 8px;
      font-size: 28px;
      line-height: 1.05;
      font-weight: 800;
      color: #1c2f4c;
    }

    .analytics-stat-card__hint {
      margin-top: 6px;
      color: #7c8ea9;
      font-size: 12px;
      line-height: 1.5;
    }

    .analytics-panels {
      display: grid;
      gap: 18px;
    }

    .analytics-two-up {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    .analytics-table-note {
      padding: 0 20px 18px;
      color: #7084a3;
      font-size: 13px;
      line-height: 1.6;
    }

    /* Status bar */
    .analytics-statusbar {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      padding: 12px 18px;
      background: #f4f7fc;
      border: 1px solid #dce6f5;
      border-radius: 16px;
    }

    .analytics-statusbar__chips {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      flex: 1;
    }

    .analytics-chip {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      line-height: 1;
    }

    .analytics-chip--ok {
      background: #edf9f5;
      color: #157357;
      border: 1px solid #c8eadf;
    }

    .analytics-chip--warn {
      background: #fff8e7;
      color: #9a6700;
      border: 1px solid #f0ddb0;
    }

    .analytics-chip--neutral {
      background: #eef2f8;
      color: #4a6080;
      border: 1px solid #d4dff0;
      font-weight: 600;
    }

    .analytics-chip__dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .analytics-chip--ok .analytics-chip__dot   { background: #22b87d; }
    .analytics-chip--warn .analytics-chip__dot { background: #e6a700; }

    /* Range + actions row */
    .analytics-toolbar {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .analytics-toolbar__range {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .analytics-toolbar__range .admin-button {
      min-width: 110px;
    }

    .analytics-month-tabs {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      align-items: center;
    }

    .analytics-month-tab {
      padding: 5px 12px;
      border-radius: 999px;
      border: 1px solid #d4dff0;
      background: #f4f7fc;
      font-size: 12px;
      font-weight: 700;
      color: #4a6080;
      cursor: pointer;
      line-height: 1;
      transition: background 0.15s, color 0.15s, border-color 0.15s;
    }

    .analytics-month-tab:hover {
      background: #e8eef8;
      border-color: #b8c8e8;
    }

    .analytics-month-tab--active {
      background: #223a63;
      border-color: #223a63;
      color: #fff;
    }

    @media (max-width: 980px) {
      .analytics-two-up {
        grid-template-columns: minmax(0, 1fr);
      }
    }
  </style>

  <div class="admin-page-head">
    <div>
      <h1>Analytics GA4</h1>
      <p class="admin-subtitle">traffic, lead และ order ย้อนหลังของ Supernumber</p>
    </div>
    <div class="analytics-toolbar">
      <div class="analytics-toolbar__range">
        @foreach ($rangeOptions as $rangeValue => $rangeLabel)
          <a
            href="{{ route('admin.analytics', ['range' => $rangeValue]) }}"
            class="admin-button @if ($days === $rangeValue) admin-button--secondary @else admin-button--muted @endif admin-button--compact"
          >{{ $rangeLabel }}</a>
        @endforeach
      </div>
      <a href="{{ route('admin.analytics.realtime') }}" class="admin-button admin-button--muted admin-button--compact">Realtime</a>
      <a href="{{ route('admin.analytics.settings') }}" class="admin-button admin-button--muted admin-button--compact">ตั้งค่า GA4</a>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  @if ($ga4Error)
    <div class="admin-alert admin-alert--error">GA4 เชื่อมต่อไม่สำเร็จ: {{ $ga4Error }}</div>
  @endif

  <div class="analytics-panels">

    {{-- Compact status bar --}}
    <div class="analytics-statusbar">
      <div class="analytics-statusbar__chips">
        <span class="analytics-chip {{ $ga4ConfiguredForTracking ? 'analytics-chip--ok' : 'analytics-chip--warn' }}">
          <span class="analytics-chip__dot"></span>
          Tracking: {{ $ga4ConfiguredForTracking ? 'พร้อม' : 'รอตั้งค่า' }}
        </span>
        <span class="analytics-chip {{ $ga4ConfiguredForReporting ? 'analytics-chip--ok' : 'analytics-chip--warn' }}">
          <span class="analytics-chip__dot"></span>
          Reporting: {{ $ga4ConfiguredForReporting ? 'พร้อม' : 'ยังไม่ครบ' }}
        </span>
        @if ($ga4ServiceAccountEmail !== '')
          <span class="analytics-chip analytics-chip--neutral">{{ $ga4ServiceAccountEmail }}</span>
        @endif
      </div>
    </div>

    {{-- Internal summary --}}
    <section class="admin-card admin-feature-card">
      <div class="admin-feature-card__head">
        <div>
          <h2 class="admin-feature-card__title">ภาพรวมธุรกิจในระบบ</h2>
          <p class="admin-feature-card__hint">สรุปข้อมูลจากฐานข้อมูลจริงของเว็บไซต์ในช่วง {{ $rangeOptions[$days] ?? $days . ' วันล่าสุด' }}</p>
        </div>
      </div>

      <div class="analytics-card-grid">
        <article class="analytics-stat-card">
          <div class="analytics-stat-card__label">ข้อความติดต่อ</div>
          <div class="analytics-stat-card__value">{{ $formatNumber($internalSummary['contact_messages'] ?? 0) }}</div>
          <div class="analytics-stat-card__hint">รายการจากหน้า Contact Us</div>
        </article>
        <article class="analytics-stat-card">
          <div class="analytics-stat-card__label">คำขอให้ช่วยเลือกเบอร์</div>
          <div class="analytics-stat-card__value">{{ $formatNumber($internalSummary['estimate_leads'] ?? 0) }}</div>
          <div class="analytics-stat-card__hint">รายการจากหน้า Estimate</div>
        </article>
        <article class="analytics-stat-card">
          <div class="analytics-stat-card__label">คำสั่งซื้อที่สร้าง</div>
          <div class="analytics-stat-card__value">{{ $formatNumber($internalSummary['orders_created'] ?? 0) }}</div>
          <div class="analytics-stat-card__hint">อิงจาก order ที่ถูกบันทึกในระบบ</div>
        </article>
        <article class="analytics-stat-card">
          <div class="analytics-stat-card__label">Order ปิดการขายแล้ว</div>
          <div class="analytics-stat-card__value">{{ $formatNumber($internalSummary['closed_orders'] ?? 0) }}</div>
          <div class="analytics-stat-card__hint">สถานะ `สำเร็จ`</div>
        </article>
      </div>
    </section>

    {{-- GA4 data --}}
    <section class="admin-card admin-feature-card">
      <div class="admin-feature-card__head">
        <div>
          <h2 class="admin-feature-card__title">ภาพรวมจาก GA4</h2>
          <p class="admin-feature-card__hint">ข้อมูลย้อนหลัง {{ $rangeOptions[$days] ?? $days . ' วันล่าสุด' }} จาก Google Analytics Data API</p>
        </div>
      </div>

      @if (! $ga4ConfiguredForReporting)
        <div class="admin-muted" style="padding: 0 20px 20px;">
          ยังไม่พร้อมดึงรายงาน —
          <a href="{{ route('admin.analytics.settings') }}" style="color: var(--admin-primary); font-weight: 700;">ตั้งค่า GA4</a>
          เพื่อเปิดใช้งาน
        </div>
      @elseif (! $ga4Dashboard)
        <div class="admin-muted" style="padding: 0 20px 20px;">ยังไม่มีข้อมูลจาก GA4 ในตอนนี้</div>
      @else
        <div class="analytics-card-grid">
          <article class="analytics-stat-card">
            <div class="analytics-stat-card__label">ผู้ใช้งานทั้งหมด</div>
            <div class="analytics-stat-card__value">{{ $formatNumber($gaSummary['activeUsers'] ?? 0) }}</div>
          </article>
          <article class="analytics-stat-card">
            <div class="analytics-stat-card__label">ผู้ใช้งานใหม่</div>
            <div class="analytics-stat-card__value">{{ $formatNumber($gaSummary['newUsers'] ?? 0) }}</div>
          </article>
          <article class="analytics-stat-card">
            <div class="analytics-stat-card__label">Sessions</div>
            <div class="analytics-stat-card__value">{{ $formatNumber($gaSummary['sessions'] ?? 0) }}</div>
          </article>
          <article class="analytics-stat-card">
            <div class="analytics-stat-card__label">Page views</div>
            <div class="analytics-stat-card__value">{{ $formatNumber($gaSummary['screenPageViews'] ?? 0) }}</div>
          </article>
          <article class="analytics-stat-card">
            <div class="analytics-stat-card__label">Engagement rate</div>
            <div class="analytics-stat-card__value">{{ $formatPercent($gaSummary['engagementRate'] ?? 0) }}</div>
          </article>
          <article class="analytics-stat-card">
            <div class="analytics-stat-card__label">Avg session duration</div>
            <div class="analytics-stat-card__value">{{ $formatDuration($gaSummary['averageSessionDuration'] ?? 0) }}</div>
          </article>
        </div>
      @endif
    </section>

    @if ($ga4Dashboard)
      <section class="admin-card admin-table-card">
        <div style="padding: 18px 20px 0;">
          <h2 style="margin: 0; font-size: 1.1rem;">ทราฟฟิกตามวัน</h2>
          <p class="admin-subtitle" style="margin-top: 6px;">ดูความเคลื่อนไหวของ user, session, page view และ event ในแต่ละวัน</p>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width: 180px;">วันที่</th>
                <th style="width: 130px;">Users</th>
                <th style="width: 130px;">Sessions</th>
                <th style="width: 130px;">Page views</th>
                <th>Events</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($gaDaily as $row)
                <tr>
                  <td>{{ $formatGaDate($row['date'] ?? '') }}</td>
                  <td>{{ $formatNumber($row['activeUsers'] ?? 0) }}</td>
                  <td>{{ $formatNumber($row['sessions'] ?? 0) }}</td>
                  <td>{{ $formatNumber($row['screenPageViews'] ?? 0) }}</td>
                  <td>{{ $formatNumber($row['eventCount'] ?? 0) }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="admin-muted">GA4 ยังไม่ส่งข้อมูลกลับมาในช่วงเวลานี้</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>

      <div class="analytics-two-up">
        <section class="admin-card admin-table-card">
          <div style="padding: 18px 20px 0;">
            <h2 style="margin: 0; font-size: 1.1rem;">แหล่งที่มาของผู้ใช้งาน</h2>
            <p class="admin-subtitle" style="margin-top: 6px;">Source / medium ที่พาคนเข้าเว็บมากที่สุด</p>
          </div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Source / Medium</th>
                  <th style="width: 100px;">Sessions</th>
                  <th style="width: 100px;">Users</th>
                  <th style="width: 100px;">Engaged</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($gaSources as $row)
                  <tr>
                    <td>{{ ($row['sessionSourceMedium'] ?? '') !== '' ? $row['sessionSourceMedium'] : '(direct) / (none)' }}</td>
                    <td>{{ $formatNumber($row['sessions'] ?? 0) }}</td>
                    <td>{{ $formatNumber($row['activeUsers'] ?? 0) }}</td>
                    <td>{{ $formatPercent($row['engagementRate'] ?? 0) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="admin-muted">ยังไม่มีข้อมูล source / medium</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <section class="admin-card admin-table-card">
          <div style="padding: 18px 20px 0;">
            <h2 style="margin: 0; font-size: 1.1rem;">อุปกรณ์และประเทศ</h2>
          </div>
          <div class="admin-table-note">อุปกรณ์</div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Device</th>
                  <th style="width: 100px;">Users</th>
                  <th style="width: 100px;">Sessions</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($gaDevices as $row)
                  <tr>
                    <td>{{ ($row['deviceCategory'] ?? '') !== '' ? $row['deviceCategory'] : '-' }}</td>
                    <td>{{ $formatNumber($row['activeUsers'] ?? 0) }}</td>
                    <td>{{ $formatNumber($row['sessions'] ?? 0) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="admin-muted">ยังไม่มีข้อมูลอุปกรณ์</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="admin-table-note">ประเทศ</div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Country</th>
                  <th style="width: 100px;">Users</th>
                  <th style="width: 100px;">Sessions</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($gaCountries as $row)
                  <tr>
                    <td>{{ ($row['country'] ?? '') !== '' ? $row['country'] : '-' }}</td>
                    <td>{{ $formatNumber($row['activeUsers'] ?? 0) }}</td>
                    <td>{{ $formatNumber($row['sessions'] ?? 0) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="admin-muted">ยังไม่มีข้อมูลประเทศ</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <div class="analytics-two-up">
        <section class="admin-card admin-table-card">
          <div style="padding: 18px 20px 0;">
            <h2 style="margin: 0; font-size: 1.1rem;">หน้าที่คนดูมากที่สุด</h2>
            <p class="admin-subtitle" style="margin-top: 6px;">content หรือ flow หน้าไหนกำลังดึงคนเข้าเว็บ</p>
          </div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Page path</th>
                  <th style="width: 100px;">Views</th>
                  <th style="width: 100px;">Users</th>
                  <th style="width: 120px;">Avg session</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($gaPages as $row)
                  <tr>
                    <td>{{ ($row['pagePath'] ?? '') !== '' ? $row['pagePath'] : '/' }}</td>
                    <td>{{ $formatNumber($row['screenPageViews'] ?? 0) }}</td>
                    <td>{{ $formatNumber($row['activeUsers'] ?? 0) }}</td>
                    <td>{{ $formatDuration($row['averageSessionDuration'] ?? 0) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="admin-muted">ยังไม่มีข้อมูล page path</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <section class="admin-card admin-table-card">
          <div style="padding: 18px 20px 0;">
            <h2 style="margin: 0; font-size: 1.1rem;">Events ที่เกิดขึ้นในเว็บ</h2>
            <p class="admin-subtitle" style="margin-top: 6px;">event สำคัญ เช่น search, generate_lead หรือ flow สั่งซื้อ</p>
          </div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Event</th>
                  <th style="width: 100px;">Count</th>
                  <th style="width: 100px;">Users</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($gaEvents as $row)
                  <tr>
                    <td>{{ ($row['eventName'] ?? '') !== '' ? $row['eventName'] : '-' }}</td>
                    <td>{{ $formatNumber($row['eventCount'] ?? 0) }}</td>
                    <td>{{ $formatNumber($row['totalUsers'] ?? 0) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="admin-muted">ยังไม่มีข้อมูล events</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      </div>
    @endif

    {{-- Internal daily + order status --}}
    @php
      $dailyByMonth = [];
      foreach ($internalDaily as $row) {
          $monthKey = substr($row['date'], 0, 7);
          $dailyByMonth[$monthKey][] = $row;
      }
      $monthKeys = array_keys($dailyByMonth);
      $latestMonthKey = end($monthKeys) ?: null;
      $thMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
      $monthLabel = static function (string $key) use ($thMonths): string {
          [$y, $m] = explode('-', $key);
          return ($thMonths[(int)$m] ?? $m) . ' ' . ((int)$y + 543);
      };
    @endphp

    <div class="analytics-two-up">
      <section class="admin-card admin-table-card">
        <div style="padding: 18px 20px 10px; display: flex; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
          <div style="flex: 1; min-width: 0;">
            <h2 style="margin: 0; font-size: 1.1rem;">Lead และ Order ตามวัน</h2>
            <p class="admin-subtitle" style="margin-top: 6px;">ช่วยดูว่า traffic ที่เข้ามาเปลี่ยนเป็น lead และ order จริงในแต่ละวัน</p>
          </div>
          @if (count($monthKeys) > 1)
            <div class="analytics-month-tabs">
              @foreach ($monthKeys as $mk)
                <button
                  class="analytics-month-tab @if ($mk === $latestMonthKey) analytics-month-tab--active @endif"
                  data-month="{{ $mk }}"
                  onclick="switchDailyMonth('{{ $mk }}')"
                  type="button"
                >{{ $monthLabel($mk) }}</button>
              @endforeach
            </div>
          @endif
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>วันที่</th>
                <th style="width: 130px;">ติดต่อ</th>
                <th style="width: 130px;">Estimate</th>
                <th style="width: 130px;">Orders</th>
              </tr>
            </thead>
            @foreach ($dailyByMonth as $mk => $monthRows)
              @php
                $nonZeroRows = array_filter($monthRows, static fn($r) =>
                    ($r['contact_messages'] ?? 0) + ($r['estimate_leads'] ?? 0) + ($r['orders_created'] ?? 0) > 0
                );
              @endphp
              <tbody data-month="{{ $mk }}" @if ($mk !== $latestMonthKey) style="display:none" @endif>
                @if (count($nonZeroRows) > 0)
                  @foreach ($nonZeroRows as $row)
                    <tr>
                      <td>{{ $row['date'] }}</td>
                      <td>{{ $formatNumber($row['contact_messages'] ?? 0) }}</td>
                      <td>{{ $formatNumber($row['estimate_leads'] ?? 0) }}</td>
                      <td>{{ $formatNumber($row['orders_created'] ?? 0) }}</td>
                    </tr>
                  @endforeach
                @else
                  <tr><td colspan="4" class="admin-muted">ไม่มีข้อมูลในเดือนนี้</td></tr>
                @endif
              </tbody>
            @endforeach
            @if (count($dailyByMonth) === 0)
              <tbody>
                <tr><td colspan="4" class="admin-muted">ยังไม่มีข้อมูลในช่วงเวลาที่เลือก</td></tr>
              </tbody>
            @endif
          </table>
        </div>
      </section>

      <section class="admin-card admin-table-card">
        <div style="padding: 18px 20px 0;">
          <h2 style="margin: 0; font-size: 1.1rem;">สถานะ Order</h2>
          <p class="admin-subtitle" style="margin-top: 6px;">ดูคิวงานในระบบจาก order ที่ถูกสร้างในช่วงเวลานี้</p>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>สถานะ</th>
                <th style="width: 120px;">จำนวน</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($orderStatusBreakdown as $status => $count)
                <tr>
                  <td>{{ \App\Models\CustomerOrder::statusLabelOptions()[$status] ?? $status }}</td>
                  <td>{{ $formatNumber($count) }}</td>
                </tr>
              @empty
                <tr><td colspan="2" class="admin-muted">ยังไม่มี order ในช่วงเวลาที่เลือก</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>

  <script>
    function switchDailyMonth(month) {
      document.querySelectorAll('[data-month]').forEach(el => {
        el.style.display = el.dataset.month === month ? '' : 'none';
      });
      document.querySelectorAll('.analytics-month-tab').forEach(btn => {
        btn.classList.toggle('analytics-month-tab--active', btn.dataset.month === month);
      });
    }
  </script>
@endsection
