@extends('layouts.admin')

@section('title', 'Supernumber Admin | Submission ลูกค้า')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Submission ลูกค้า</h1>
      <p class="admin-subtitle">ข้อมูลที่ลูกค้าส่งจากฟอร์มวิเคราะห์เบอร์ ฟอร์มเลือกเบอร์ และหน้าติดต่อ พร้อมสถานะ consent</p>
    </div>
    <div class="admin-summary">
      แสดง
      {{ $submissions->count() ? $submissions->firstItem() . '-' . $submissions->lastItem() : '0' }}
      จาก {{ number_format($submissions->total()) }} รายการ
    </div>
  </div>

  <section class="admin-kpi-grid" aria-label="สรุป submission">
    <div class="admin-kpi">
      <div class="admin-kpi__label">ทั้งหมด</div>
      <div class="admin-kpi__value">{{ number_format($stats['total']) }}</div>
      <div class="admin-summary">ทุกฟอร์มรวมกัน</div>
    </div>
    <div class="admin-kpi">
      <div class="admin-kpi__label">วันนี้</div>
      <div class="admin-kpi__value">{{ number_format($stats['today']) }}</div>
      <div class="admin-summary">นับจากเวลาไทย 00:00</div>
    </div>
    <div class="admin-kpi">
      <div class="admin-kpi__label">ยินยอมพัฒนาบริการ</div>
      <div class="admin-kpi__value">{{ number_format($stats['consent_dev_yes']) }}</div>
      <div class="admin-summary">consent_dev = ยินยอม</div>
    </div>
    <div class="admin-kpi">
      <div class="admin-kpi__label">ยินยอมโปรโมชั่น</div>
      <div class="admin-kpi__value">{{ number_format($stats['consent_marketing_yes']) }}</div>
      <div class="admin-summary">consent_marketing = ยินยอม</div>
    </div>
  </section>

  <section class="admin-card admin-feature-card">
    <div class="admin-feature-card__head">
      <div>
        <h2 class="admin-feature-card__title">แยกตามประเภทฟอร์ม</h2>
        <p class="admin-feature-card__hint">ใช้ดูปริมาณ submission จากแต่ละจุดในเว็บ</p>
      </div>
    </div>
    <div class="admin-kpi-grid">
      @foreach ($formTypeLabels as $type => $label)
        <div class="admin-kpi">
          <div class="admin-kpi__label">{{ $label }}</div>
          <div class="admin-kpi__value">{{ number_format((int) ($stats['by_form_type'][$type] ?? 0)) }}</div>
          <div class="admin-summary">{{ $type }}</div>
        </div>
      @endforeach
    </div>
  </section>

  <section class="admin-card admin-feature-card">
    <div class="admin-feature-card__head">
      <div>
        <h2 class="admin-feature-card__title">ค้นหาและกรองข้อมูล</h2>
        <p class="admin-feature-card__hint">ค้นหาจากชื่อ อีเมล หรือเบอร์ และกรองตามประเภทฟอร์ม consent หรือช่วงวันที่</p>
      </div>
    </div>

    <form action="{{ route('admin.customer-submissions') }}" method="get" class="admin-form" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
      <div class="admin-field" style="grid-column: 1 / -1;">
        <label for="submission-search">ค้นหา</label>
        <input
          id="submission-search"
          type="text"
          name="q"
          class="admin-input"
          value="{{ $search }}"
          placeholder="ชื่อ, อีเมล, เบอร์โทร"
        />
      </div>

      <div class="admin-field">
        <label for="submission-form-type">ประเภทฟอร์ม</label>
        <select id="submission-form-type" name="form_type" class="admin-select">
          <option value="">ทั้งหมด</option>
          @foreach ($formTypeLabels as $value => $label)
            <option value="{{ $value }}" @selected($selectedFormType === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="admin-field">
        <label for="submission-consent">Consent</label>
        <select id="submission-consent" name="consent" class="admin-select">
          <option value="">ทั้งหมด</option>
          <option value="dev_yes" @selected($selectedConsent === 'dev_yes')>พัฒนาบริการ: ยินยอม</option>
          <option value="dev_no" @selected($selectedConsent === 'dev_no')>พัฒนาบริการ: ไม่ยินยอม</option>
          <option value="marketing_yes" @selected($selectedConsent === 'marketing_yes')>โปรโมชั่น: ยินยอม</option>
          <option value="marketing_no" @selected($selectedConsent === 'marketing_no')>โปรโมชั่น: ไม่ยินยอม</option>
        </select>
      </div>

      <div class="admin-field">
        <label for="submission-date-from">วันที่เริ่ม</label>
        <input id="submission-date-from" type="date" name="date_from" class="admin-input" value="{{ $dateFrom }}" />
      </div>

      <div class="admin-field">
        <label for="submission-date-to">วันที่สิ้นสุด</label>
        <input id="submission-date-to" type="date" name="date_to" class="admin-input" value="{{ $dateTo }}" />
      </div>

      <div class="admin-page-actions" style="align-self: end; justify-content: flex-start;">
        <button type="submit" class="admin-button admin-button--compact">กรองข้อมูล</button>
        <a href="{{ route('admin.customer-submissions') }}" class="admin-button admin-button--muted admin-button--compact">ล้าง filter</a>
      </div>
    </form>
  </section>

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>เวลา</th>
            <th>ประเภท</th>
            <th>ลูกค้า</th>
            <th>Consent</th>
            <th>ข้อมูลฟอร์ม</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($submissions as $submission)
            @php
              $submittedAt = $submission->submitted_at ?? $submission->created_at;
              $payload = is_array($submission->payload) ? $submission->payload : [];
            @endphp
            <tr>
              <td>{{ $submittedAt?->timezone('Asia/Bangkok')->format('Y-m-d H:i') ?: '-' }}</td>
              <td>
                <div style="display: grid; gap: 4px;">
                  <strong>{{ $submission->form_type_label }}</strong>
                  <span class="admin-muted">#{{ $submission->id }}</span>
                </div>
              </td>
              <td style="min-width: 220px;">
                <div style="display: grid; gap: 4px;">
                  <div>{{ $submission->name ?: '-' }}</div>
                  <div>
                    @if ($submission->phone)
                      <a href="tel:{{ $submission->phone }}">{{ $submission->phone }}</a>
                    @else
                      -
                    @endif
                  </div>
                  <div>
                    @if ($submission->email)
                      <a href="mailto:{{ $submission->email }}">{{ $submission->email }}</a>
                    @else
                      <span class="admin-muted">ไม่มีอีเมล</span>
                    @endif
                  </div>
                </div>
              </td>
              <td style="min-width: 190px;">
                <div style="display: grid; gap: 4px;">
                  <div>พัฒนาบริการ: {{ $submission->consent_dev ? 'ยินยอม' : 'ไม่ยินยอม' }}</div>
                  <div>โปรโมชั่น: {{ $submission->consent_marketing ? 'ยินยอม' : 'ไม่ยินยอม' }}</div>
                </div>
              </td>
              <td style="max-width: 420px; white-space: normal; line-height: 1.6;">
                @if ($payload !== [])
                  @foreach ($payload as $key => $value)
                    <div>
                      <span class="admin-muted">{{ $key }}:</span>
                      {{ is_scalar($value) || $value === null ? ($value ?? '-') : json_encode($value, JSON_UNESCAPED_UNICODE) }}
                    </div>
                  @endforeach
                @else
                  <span class="admin-muted">ไม่มี payload</span>
                @endif
              </td>
              <td>{{ $submission->ip_address ?: '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="admin-muted">ยังไม่มี submission ลูกค้า</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($submissions->hasPages())
      <nav class="admin-pagination" aria-label="เปลี่ยนหน้ารายการ submission ลูกค้า">
        @if ($submissions->onFirstPage())
          <span>ก่อนหน้า</span>
        @else
          <a href="{{ $submissions->previousPageUrl() }}">ก่อนหน้า</a>
        @endif

        @php
          $startPage = max(1, $submissions->currentPage() - 2);
          $endPage = min($submissions->lastPage(), $submissions->currentPage() + 2);
        @endphp

        @for ($page = $startPage; $page <= $endPage; $page++)
          @if ($page === $submissions->currentPage())
            <span class="is-active">{{ $page }}</span>
          @else
            <a href="{{ $submissions->url($page) }}">{{ $page }}</a>
          @endif
        @endfor

        @if ($submissions->hasMorePages())
          <a href="{{ $submissions->nextPageUrl() }}">ถัดไป</a>
        @else
          <span>ถัดไป</span>
        @endif
      </nav>
    @endif
  </section>
@endsection
