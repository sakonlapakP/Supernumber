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

        if ($date === '' || strlen($date) !== 8) {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::createFromFormat('Ymd', $date, 'Asia/Bangkok')
                ->locale('th')
                ->translatedFormat('j M Y');
        } catch (\Throwable) {
            return $date;
        }
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

    .analytics-status-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 14px;
    }

    .analytics-status-pill {
      display: inline-flex;
      align-items: center;
      min-height: 30px;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
    }

    .analytics-status-pill--ready {
      background: #edf9f5;
      color: #157357;
      border: 1px solid #c8eadf;
    }

    .analytics-status-pill--pending {
      background: #fff8e7;
      color: #9a6700;
      border: 1px solid #f0ddb0;
    }

    .analytics-table-note {
      padding: 0 20px 18px;
      color: #7084a3;
      font-size: 13px;
      line-height: 1.6;
    }

    .analytics-range-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .analytics-range-actions .admin-button {
      min-width: 124px;
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
      <p class="admin-subtitle">ดู traffic จาก Google Analytics 4 ควบคู่กับ lead และ order ที่เกิดขึ้นจริงในระบบหลังบ้านของ Supernumber</p>
    </div>
    <div class="analytics-range-actions">
      @foreach ($rangeOptions as $rangeValue => $rangeLabel)
        <a
          href="{{ route('admin.analytics', ['range' => $rangeValue]) }}"
          class="admin-button @if ($days === $rangeValue) admin-button--secondary @else admin-button--muted @endif admin-button--compact"
        >
          {{ $rangeLabel }}
        </a>
      @endforeach
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
  @endif

  @if ($ga4Error)
    <div class="admin-alert admin-alert--error">GA4 เชื่อมต่อไม่สำเร็จ: {{ $ga4Error }}</div>
  @endif

  <div class="analytics-panels">
    <section class="admin-card admin-feature-card">
      <div class="admin-feature-card__head">
        <div>
          <h2 class="admin-feature-card__title">ตั้งค่า Google Analytics 4</h2>
          <p class="admin-feature-card__hint">ใส่ Measurement ID สำหรับ tracking หน้าเว็บ และใส่ Property ID พร้อม Service Account สำหรับดึงรายงานเข้า manager dashboard</p>
        </div>
      </div>

      <form class="admin-form" action="{{ route('admin.analytics.settings.update') }}" method="post">
        @csrf

        <div class="admin-field">
          <label for="ga4_measurement_id">GA4 Measurement ID</label>
          <input
            id="ga4_measurement_id"
            class="admin-input"
            type="text"
            name="ga4_measurement_id"
            value="{{ old('ga4_measurement_id', $ga4Settings['measurement_id'] ?? '') }}"
            placeholder="เช่น G-ABC123XYZ9"
          />
          <p class="admin-muted" style="margin: 8px 0 0; font-size: 0.9rem;">ค่านี้ใช้ฝัง GA4 ฝั่งหน้าเว็บโดยจะ track แบบตัด query string ออก เพื่อไม่ส่งเบอร์โทรหรือข้อมูลส่วนตัวขึ้น Google Analytics</p>
        </div>

        <div class="admin-field">
          <label for="ga4_property_id">GA4 Property ID</label>
          <input
            id="ga4_property_id"
            class="admin-input"
            type="text"
            name="ga4_property_id"
            value="{{ old('ga4_property_id', $ga4Settings['property_id'] ?? '') }}"
            placeholder="เช่น 123456789"
          />
          <p class="admin-muted" style="margin: 8px 0 0; font-size: 0.9rem;">Property ID ใช้กับ Google Analytics Data API เพื่อดึงรายงาน traffic, page, event, source และ device เข้า dashboard นี้</p>
        </div>

        <div class="admin-field">
          <label for="ga4_service_account_json">GA4 Service Account JSON</label>
          <textarea
            id="ga4_service_account_json"
            class="admin-input"
            name="ga4_service_account_json"
            rows="10"
            placeholder='วาง JSON ของ Google service account ตรงนี้'
          >{{ old('ga4_service_account_json', $ga4Settings['service_account_json'] ?? '') }}</textarea>
          <p class="admin-muted" style="margin: 8px 0 0; font-size: 0.9rem;">
            หลังสร้าง service account แล้ว ให้นำอีเมลของ service account ไปเพิ่มสิทธิ์ใน GA4 property อย่างน้อยระดับ Viewer หรือ Analyst
            @if ($ga4ServiceAccountEmail !== '')
              <br />บัญชีที่อ่านได้ตอนนี้: <strong>{{ $ga4ServiceAccountEmail }}</strong>
            @endif
          </p>
        </div>

        <button type="submit" class="admin-button">บันทึก GA4 settings</button>
      </form>
    </section>

    <section class="admin-card admin-feature-card">
      <div class="admin-feature-card__head">
        <div>
          <h2 class="admin-feature-card__title">สถานะการเชื่อมต่อ</h2>
          <p class="admin-feature-card__hint">หน้าเว็บจะเริ่มส่งข้อมูลเมื่อ Measurement ID พร้อม และ dashboard จะอ่านรายงานได้เมื่อ Property ID กับ Service Account ถูกต้อง</p>
        </div>
      </div>

      <div class="analytics-status-grid">
        <div class="analytics-stat-card">
          <div class="analytics-stat-card__label">Tracking ฝั่งเว็บ</div>
          <div style="margin-top: 10px;">
            <span class="analytics-status-pill {{ $ga4ConfiguredForTracking ? 'analytics-status-pill--ready' : 'analytics-status-pill--pending' }}">
              {{ $ga4ConfiguredForTracking ? 'พร้อมใช้งาน' : 'รอตั้งค่า' }}
            </span>
          </div>
          <div class="analytics-stat-card__hint">Measurement ID: {{ ($ga4Settings['measurement_id'] ?? '') !== '' ? $ga4Settings['measurement_id'] : '-' }}</div>
        </div>

        <div class="analytics-stat-card">
          <div class="analytics-stat-card__label">Reporting ใน dashboard</div>
          <div style="margin-top: 10px;">
            <span class="analytics-status-pill {{ $ga4ConfiguredForReporting ? 'analytics-status-pill--ready' : 'analytics-status-pill--pending' }}">
              {{ $ga4ConfiguredForReporting ? 'พร้อมดึงรายงาน' : 'ยังไม่ครบ' }}
            </span>
          </div>
          <div class="analytics-stat-card__hint">Property ID: {{ ($ga4Settings['property_id'] ?? '') !== '' ? $ga4Settings['property_id'] : '-' }}</div>
        </div>

        <div class="analytics-stat-card">
          <div class="analytics-stat-card__label">Service account</div>
          <div class="analytics-stat-card__value" style="font-size: 16px; line-height: 1.4;">{{ $ga4ServiceAccountEmail !== '' ? $ga4ServiceAccountEmail : '-' }}</div>
          <div class="analytics-stat-card__hint">อีเมลนี้ต้องถูกเพิ่มสิทธิ์ใน property เดียวกับ Property ID ด้านบน</div>
        </div>
      </div>
    </section>

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
          <div class="analytics-stat-card__hint">สถานะ `completed` หรือ `sold`</div>
        </article>
      </div>
    </section>

    <section class="admin-card admin-table-card">
      <div style="padding: 18px 20px 0;">
        <h2 style="margin: 0; font-size: 1.1rem;">Lead และ Order ตามวัน</h2>
        <p class="admin-subtitle" style="margin-top: 6px;">ช่วยดูว่า traffic ที่เข้ามาเปลี่ยนเป็น lead และ order จริงในแต่ละวันมากน้อยแค่ไหน</p>
      </div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 180px;">วันที่</th>
              <th style="width: 180px;">ข้อความติดต่อ</th>
              <th style="width: 180px;">Estimate leads</th>
              <th>Orders created</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($internalDaily as $row)
              <tr>
                <td>{{ $row['date'] }}</td>
                <td>{{ $formatNumber($row['contact_messages'] ?? 0) }}</td>
                <td>{{ $formatNumber($row['estimate_leads'] ?? 0) }}</td>
                <td>{{ $formatNumber($row['orders_created'] ?? 0) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="admin-muted">ยังไม่มีข้อมูล lead หรือ order ในช่วงเวลาที่เลือก</td>
              </tr>
            @endforelse
          </tbody>
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
              <th style="width: 160px;">จำนวน</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($orderStatusBreakdown as $status => $count)
              <tr>
                <td>{{ $status }}</td>
                <td>{{ $formatNumber($count) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="2" class="admin-muted">ยังไม่มี order ในช่วงเวลาที่เลือก</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="admin-card admin-feature-card">
      <div class="admin-feature-card__head">
        <div>
          <h2 class="admin-feature-card__title">ภาพรวมจาก GA4</h2>
          <p class="admin-feature-card__hint">ส่วนนี้อ่านจาก Google Analytics Data API โดยใช้ข้อมูลย้อนหลัง {{ $rangeOptions[$days] ?? $days . ' วันล่าสุด' }}</p>
        </div>
      </div>

      @if (! $ga4ConfiguredForReporting)
        <div class="admin-muted">ยังไม่พร้อมดึงรายงานจาก GA4 กรุณาใส่ Property ID และ Service Account JSON ให้ครบก่อน</div>
      @elseif (! $ga4Dashboard)
        <div class="admin-muted">ยังไม่มีข้อมูลจาก GA4 ในตอนนี้ หรือระบบกำลังรอการเชื่อมต่อ</div>
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
            <div class="analytics-stat-card__label">Average session duration</div>
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
                <th style="width: 160px;">Users</th>
                <th style="width: 160px;">Sessions</th>
                <th style="width: 160px;">Page views</th>
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
                <tr>
                  <td colspan="5" class="admin-muted">GA4 ยังไม่ส่งข้อมูลกลับมาในช่วงเวลานี้</td>
                </tr>
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
                  <th style="width: 120px;">Sessions</th>
                  <th style="width: 120px;">Users</th>
                  <th style="width: 120px;">Engaged</th>
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
                  <tr>
                    <td colspan="4" class="admin-muted">ยังไม่มีข้อมูล source / medium</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <section class="admin-card admin-table-card">
          <div style="padding: 18px 20px 0;">
            <h2 style="margin: 0; font-size: 1.1rem;">อุปกรณ์และประเทศ</h2>
            <p class="admin-subtitle" style="margin-top: 6px;">สรุปว่า user ใช้อุปกรณ์อะไรและมาจากประเทศไหน</p>
          </div>
          <div class="admin-table-note">อุปกรณ์</div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Device</th>
                  <th style="width: 120px;">Users</th>
                  <th style="width: 120px;">Sessions</th>
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
                  <tr>
                    <td colspan="3" class="admin-muted">ยังไม่มีข้อมูลอุปกรณ์</td>
                  </tr>
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
                  <th style="width: 120px;">Users</th>
                  <th style="width: 120px;">Sessions</th>
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
                  <tr>
                    <td colspan="3" class="admin-muted">ยังไม่มีข้อมูลประเทศ</td>
                  </tr>
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
            <p class="admin-subtitle" style="margin-top: 6px;">ช่วยดูว่า content หรือ flow หน้าไหนกำลังดึงคนเข้าเว็บ</p>
          </div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Page path</th>
                  <th style="width: 120px;">Views</th>
                  <th style="width: 120px;">Users</th>
                  <th style="width: 140px;">Avg session</th>
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
                  <tr>
                    <td colspan="4" class="admin-muted">ยังไม่มีข้อมูล page path</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <section class="admin-card admin-table-card">
          <div style="padding: 18px 20px 0;">
            <h2 style="margin: 0; font-size: 1.1rem;">Events ที่เกิดขึ้นในเว็บ</h2>
            <p class="admin-subtitle" style="margin-top: 6px;">ใช้ดูว่า event สำคัญ เช่น search, generate_lead หรือ flow สั่งซื้อ เกิดขึ้นมากน้อยแค่ไหน</p>
          </div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Event</th>
                  <th style="width: 120px;">Count</th>
                  <th style="width: 120px;">Users</th>
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
                  <tr>
                    <td colspan="3" class="admin-muted">ยังไม่มีข้อมูล events</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      </div>
    @endif
  </div>
@endsection
