@extends('layouts.admin')

@section('title', 'Supernumber Admin | Lead เลือกเบอร์')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Lead เลือกเบอร์</h1>
      <p class="admin-subtitle">รายการลูกค้าที่กรอกฟอร์มหน้าเลือกเบอร์ พร้อม filter สำหรับค้นหาชื่อ เบอร์ และอีเมลได้ทันที</p>
    </div>
    <div class="admin-summary">
      แสดง
      {{ $leads->count() ? $leads->firstItem() . '-' . $leads->lastItem() : '0' }}
      จาก {{ number_format($leads->total()) }} รายการ
    </div>
  </div>

  <section class="admin-kpi-grid" aria-label="สรุป lead">
    <div class="admin-kpi">
      <div class="admin-kpi__label">Lead วันนี้</div>
      <div class="admin-kpi__value">{{ number_format($stats['today']) }}</div>
      <div class="admin-summary">ส่งเข้ามาในวันนี้</div>
    </div>
    <div class="admin-kpi">
      <div class="admin-kpi__label">7 วันล่าสุด</div>
      <div class="admin-kpi__value">{{ number_format($stats['last_7_days']) }}</div>
      <div class="admin-summary">ใช้ดูจังหวะ lead ล่าสุด</div>
    </div>
    <div class="admin-kpi">
      <div class="admin-kpi__label">เดือนนี้</div>
      <div class="admin-kpi__value">{{ number_format($stats['this_month']) }}</div>
      <div class="admin-summary">นับจากต้นเดือน</div>
    </div>
    <div class="admin-kpi">
      <div class="admin-kpi__label">ผลลัพธ์ตาม filter</div>
      <div class="admin-kpi__value">{{ number_format($stats['filtered']) }}</div>
      <div class="admin-summary">จากทั้งหมด {{ number_format($stats['total']) }} lead</div>
    </div>
  </section>

  <section class="admin-card admin-feature-card">
    <div class="admin-feature-card__head">
      <div>
        <h2 class="admin-feature-card__title">ค้นหาและกรองข้อมูล</h2>
        <p class="admin-feature-card__hint">ค้นหาจากชื่อ นามสกุล อีเมล หรือเบอร์โทร และกรองตามเพศ งาน หรือเป้าหมายได้</p>
      </div>
    </div>

    <form action="{{ route('admin.estimate-leads') }}" method="get" class="admin-form" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
      <div class="admin-field" style="grid-column: 1 / -1;">
        <label for="estimate-lead-search">ค้นหา</label>
        <input
          id="estimate-lead-search"
          type="text"
          name="q"
          class="admin-input"
          value="{{ $search }}"
          placeholder="ชื่อ, อีเมล, เบอร์หลัก, เบอร์ปัจจุบัน"
        />
      </div>

      <div class="admin-field">
        <label for="estimate-lead-gender">เพศ</label>
        <select id="estimate-lead-gender" name="gender" class="admin-select">
          <option value="">ทั้งหมด</option>
          @foreach ($genderLabels as $value => $label)
            <option value="{{ $value }}" @selected($selectedGender === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="admin-field">
        <label for="estimate-lead-work-type">ลักษณะงาน</label>
        <select id="estimate-lead-work-type" name="work_type" class="admin-select">
          <option value="">ทั้งหมด</option>
          @foreach ($workTypeLabels as $value => $label)
            <option value="{{ $value }}" @selected($selectedWorkType === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="admin-field">
        <label for="estimate-lead-goal">เป้าหมาย</label>
        <select id="estimate-lead-goal" name="goal" class="admin-select">
          <option value="">ทั้งหมด</option>
          @foreach ($goalLabels as $value => $label)
            <option value="{{ $value }}" @selected($selectedGoal === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <div class="admin-page-actions" style="align-self: end; justify-content: flex-start;">
        <button type="submit" class="admin-button admin-button--compact">กรองข้อมูล</button>
        <a href="{{ route('admin.estimate-leads') }}" class="admin-button admin-button--muted admin-button--compact">ล้าง filter</a>
      </div>
    </form>
  </section>

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>เวลาที่ส่ง</th>
            <th>ลูกค้า</th>
            <th>เบอร์หลัก</th>
            <th>เบอร์ปัจจุบัน</th>
            <th>โปรไฟล์</th>
            <th>IP</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($leads as $lead)
            @php
              $submittedAt = $lead->submitted_at ?? $lead->created_at;
            @endphp
            <tr>
              <td>{{ $submittedAt?->timezone('Asia/Bangkok')->format('Y-m-d H:i') ?: '-' }}</td>
              <td style="min-width: 240px;">
                <div style="display: grid; gap: 4px;">
                  <div class="admin-number" style="font-size: 16px;">{{ $lead->full_name !== '' ? $lead->full_name : '-' }}</div>
                  <div><a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></div>
                  <div class="admin-muted">Lead #{{ $lead->id }}</div>
                </div>
              </td>
              <td><a href="tel:{{ $lead->main_phone }}">{{ $lead->main_phone ?: '-' }}</a></td>
              <td>
                @if ($lead->current_phone)
                  <a href="tel:{{ $lead->current_phone }}">{{ $lead->current_phone }}</a>
                @else
                  -
                @endif
              </td>
              <td style="min-width: 220px;">
                <div style="display: grid; gap: 4px;">
                  <div>เพศ: {{ $lead->gender_label }}</div>
                  <div>งาน: {{ $lead->work_type_label }}</div>
                  <div>เป้าหมาย: {{ $lead->goal_label }}</div>
                </div>
              </td>
              <td>{{ $lead->ip_address ?: '-' }}</td>
              <td class="admin-action-cell">
                <a href="{{ route('admin.estimate-leads.show', $lead) }}" class="admin-button admin-button--muted admin-button--compact">ดูรายละเอียด</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="admin-muted">ยังไม่มี lead จากฟอร์มเลือกเบอร์</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($leads->hasPages())
      <nav class="admin-pagination" aria-label="เปลี่ยนหน้ารายการ lead เลือกเบอร์">
        @if ($leads->onFirstPage())
          <span>ก่อนหน้า</span>
        @else
          <a href="{{ $leads->previousPageUrl() }}">ก่อนหน้า</a>
        @endif

        @php
          $startPage = max(1, $leads->currentPage() - 2);
          $endPage = min($leads->lastPage(), $leads->currentPage() + 2);
        @endphp

        @for ($page = $startPage; $page <= $endPage; $page++)
          @if ($page === $leads->currentPage())
            <span class="is-active">{{ $page }}</span>
          @else
            <a href="{{ $leads->url($page) }}">{{ $page }}</a>
          @endif
        @endfor

        @if ($leads->hasMorePages())
          <a href="{{ $leads->nextPageUrl() }}">ถัดไป</a>
        @else
          <span>ถัดไป</span>
        @endif
      </nav>
    @endif
  </section>
@endsection
