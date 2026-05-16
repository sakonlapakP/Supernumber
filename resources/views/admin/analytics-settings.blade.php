@extends('layouts.admin')

@section('title', 'Supernumber Admin | ตั้งค่า GA4')

@section('content')
  <style>
    .settings-status-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 14px;
    }

    .settings-status-card {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 16px 18px;
      border-radius: 16px;
      border: 1px solid #dce6f5;
      background: #f8fbff;
    }

    .settings-status-card__dot {
      flex-shrink: 0;
      margin-top: 3px;
      width: 10px;
      height: 10px;
      border-radius: 50%;
    }

    .settings-status-card--ok   { background: #edf9f5; border-color: #c8eadf; }
    .settings-status-card--warn { background: #fff8e7; border-color: #f0ddb0; }

    .settings-status-card--ok   .settings-status-card__dot { background: #22b87d; }
    .settings-status-card--warn .settings-status-card__dot { background: #e6a700; }

    .settings-status-card__body { min-width: 0; }

    .settings-status-card__label {
      font-size: 12px;
      font-weight: 700;
      color: #6a7f9e;
    }

    .settings-status-card--ok   .settings-status-card__label { color: #3a8265; }
    .settings-status-card--warn .settings-status-card__label { color: #9a6700; }

    .settings-status-card__value {
      margin-top: 4px;
      font-size: 14px;
      font-weight: 700;
      color: #1c2f4c;
      word-break: break-all;
    }

    .settings-status-card__hint {
      margin-top: 4px;
      font-size: 12px;
      color: #7c8ea9;
    }
  </style>

  <div class="admin-page-head">
    <div>
      <h1>ตั้งค่า GA4</h1>
      <p class="admin-subtitle">เชื่อมต่อ Google Analytics 4 สำหรับ Tracking และ Reporting</p>
    </div>
    <a href="{{ route('admin.analytics') }}" class="admin-button admin-button--muted admin-button--compact">
      ดู Analytics
    </a>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  @if ($errors->has('analytics_settings'))
    <div class="admin-alert admin-alert--error">{{ $errors->first('analytics_settings') }}</div>
  @endif

  {{-- Connection status --}}
  <section class="admin-card admin-feature-card" style="margin-bottom: 0;">
    <div class="admin-feature-card__head">
      <div>
        <h2 class="admin-feature-card__title">สถานะการเชื่อมต่อ</h2>
        <p class="admin-feature-card__hint">ตรวจสอบว่า GA4 พร้อมใช้งานในแต่ละส่วน</p>
      </div>
    </div>
    <div style="padding: 0 20px 20px;">
      <div class="settings-status-grid">
        <div class="settings-status-card {{ $ga4ConfiguredForTracking ? 'settings-status-card--ok' : 'settings-status-card--warn' }}">
          <div class="settings-status-card__dot"></div>
          <div class="settings-status-card__body">
            <div class="settings-status-card__label">Client Tracking</div>
            <div class="settings-status-card__value">{{ $ga4ConfiguredForTracking ? 'พร้อมใช้งาน' : 'ยังไม่ได้ตั้งค่า' }}</div>
            <div class="settings-status-card__hint">Measurement ID สำหรับ gtag.js</div>
          </div>
        </div>
        <div class="settings-status-card {{ $ga4ConfiguredForReporting ? 'settings-status-card--ok' : 'settings-status-card--warn' }}">
          <div class="settings-status-card__dot"></div>
          <div class="settings-status-card__body">
            <div class="settings-status-card__label">Data API Reporting</div>
            <div class="settings-status-card__value">{{ $ga4ConfiguredForReporting ? 'พร้อมดึงรายงาน' : 'ยังไม่ครบ' }}</div>
            <div class="settings-status-card__hint">Property ID + Service Account</div>
          </div>
        </div>
        @if ($ga4ServiceAccountEmail !== '')
          <div class="settings-status-card settings-status-card--ok">
            <div class="settings-status-card__dot"></div>
            <div class="settings-status-card__body">
              <div class="settings-status-card__label">Service Account</div>
              <div class="settings-status-card__value">{{ $ga4ServiceAccountEmail }}</div>
              <div class="settings-status-card__hint">ต้องเพิ่มเป็น Analyst ใน GA4 property</div>
            </div>
          </div>
        @endif
      </div>
    </div>
  </section>

  {{-- Settings form --}}
  <section class="admin-card admin-feature-card">
    <div class="admin-feature-card__head">
      <div>
        <h2 class="admin-feature-card__title">ตั้งค่าการเชื่อมต่อ</h2>
        <p class="admin-feature-card__hint">ข้อมูลทั้งหมดถูกเก็บใน <code>.env</code> — ไม่มีการบันทึกลง database</p>
      </div>
    </div>

    <form method="POST" action="{{ route('admin.analytics.settings.update') }}" style="padding: 0 20px 24px; display: grid; gap: 20px;">
      @csrf

      <div class="admin-form-group">
        <label class="admin-form-label" for="ga4_measurement_id">
          GA4 Measurement ID
          <span class="admin-form-hint">รูปแบบ <code>G-XXXXXXXXXX</code> — ใช้สำหรับ client-side tracking (gtag.js)</span>
        </label>
        <input
          class="admin-form-input @error('ga4_measurement_id') admin-form-input--error @enderror"
          type="text"
          id="ga4_measurement_id"
          name="ga4_measurement_id"
          value="{{ old('ga4_measurement_id', $ga4Settings['measurement_id'] ?? '') }}"
          placeholder="G-XXXXXXXXXX"
          autocomplete="off"
          spellcheck="false"
        />
        @error('ga4_measurement_id')
          <p class="admin-form-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="admin-form-group">
        <label class="admin-form-label" for="ga4_property_id">
          GA4 Property ID
          <span class="admin-form-hint">ตัวเลขล้วน เช่น <code>123456789</code> — ใช้สำหรับ GA4 Data API</span>
        </label>
        <input
          class="admin-form-input @error('ga4_property_id') admin-form-input--error @enderror"
          type="text"
          id="ga4_property_id"
          name="ga4_property_id"
          value="{{ old('ga4_property_id', $ga4Settings['property_id'] ?? '') }}"
          placeholder="123456789"
          autocomplete="off"
          spellcheck="false"
        />
        @error('ga4_property_id')
          <p class="admin-form-error">{{ $message }}</p>
        @enderror
      </div>

      <div class="admin-form-group">
        <label class="admin-form-label" for="ga4_service_account_json">
          Service Account JSON
          <span class="admin-form-hint">วาง JSON ทั้งหมดจาก Google Cloud Console — ระบบจะแปลงเป็น Base64 อัตโนมัติ</span>
        </label>
        <textarea
          class="admin-form-input admin-form-textarea @error('ga4_service_account_json') admin-form-input--error @enderror"
          id="ga4_service_account_json"
          name="ga4_service_account_json"
          rows="8"
          placeholder='{ "type": "service_account", "project_id": "...", ... }'
          autocomplete="off"
          spellcheck="false"
        >{{ old('ga4_service_account_json', $ga4Settings['service_account_json'] ?? '') }}</textarea>
        @error('ga4_service_account_json')
          <p class="admin-form-error">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <button type="submit" class="admin-button admin-button--primary">บันทึก</button>
      </div>
    </form>
  </section>
@endsection
