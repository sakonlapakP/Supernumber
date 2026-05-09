@extends('layouts.admin')

@section('title', 'Supernumber Admin | ' . ($pageTitle ?? 'เบอร์ทั้งหมด'))

@section('content')
  @php
    $numbersHeading = match ($selectedServiceType ?? null) {
      \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID => 'Postpaid Numbers',
      \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID => 'Prepaid Numbers',
      default => 'All Numbers',
    };
    $numbersSubtitle = $pageSubtitle ?? 'แสดงเบอร์ทั้งหมดในระบบ พร้อมสถานะของแต่ละเบอร์';
    $numbersSummary = $numbers->count()
      ? $numbers->firstItem() . '-' . $numbers->lastItem()
      : '0';
    $selectedServiceTypeLabel = match ($selectedServiceType ?? null) {
      \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID => 'รายเดือน',
      \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID => 'เติมเงิน',
      default => null,
    };
  @endphp

  <div class="admin-page-head">
    <div>
      <h1>{{ $numbersHeading }}</h1>
    </div>
    <div class="admin-summary">
      แสดง {{ $numbersSummary }} จาก {{ number_format($numbers->total()) }} เบอร์
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <section class="admin-card admin-feature-card admin-feature-card--compact">
    <div class="admin-feature-card__head">
      <div>
        <h2 class="admin-feature-card__title">
          ค้นหาเบอร์
          <span class="admin-feature-card__hint" style="margin: 0 0 0 10px; display: inline; font-size: 0.9em; font-weight: 400;">
            ผลรวม, เครือข่าย, แพ็กเกจ, ราคา หรือสถานะ
            @if ($selectedServiceTypeLabel !== null)
              | กำลังกรอง: {{ $selectedServiceTypeLabel }}
            @endif
          </span>
        </h2>
      </div>
    </div>

    <form action="{{ route('admin.numbers') }}" method="get" class="admin-form admin-form--inline admin-form--numbers-search">
      @if (($selectedServiceType ?? null) !== null)
        <input type="hidden" name="service_type" value="{{ $selectedServiceType }}" />
      @endif
      <div class="admin-field">
        <input
          id="admin-number-search"
          class="admin-input"
          type="text"
          name="q"
          value="{{ $search ?? '' }}"
          placeholder="เช่น 064929, dtac, 1499, active"
        />
      </div>
      <button type="submit" class="admin-button admin-button--compact">ค้นหา</button>
    </form>
  </section>

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>เบอร์</th>
            <th>ผลรวม</th>
            <th>ประเภท</th>
            <th>เครือข่าย</th>
            <th>ราคา / แพ็กเกจ</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($numbers as $number)
            <tr>
              <td>
                <div class="admin-number">{{ $number->display_number ?: $number->phone_number }}</div>
              </td>
              <td>{{ $number->number_sum ?: '-' }}</td>
              <td>{{ $number->service_type_label }}</td>
              <td>{{ strtoupper(str_replace('_', '-', $number->network_code)) }}</td>
              <td>{{ $number->payment_label }}</td>
              <td>
                @php
                  $status = trim((string) ($number->status ?: '-'));
                  $statusClass = match (strtolower($status)) {
                    'active' => 'admin-status-pill admin-status-pill--active',
                    'hold' => 'admin-status-pill admin-status-pill--hold',
                    default => 'admin-status-pill',
                  };
                @endphp
                <span class="{{ $statusClass }}">{{ $status }}</span>
              </td>
              <td class="admin-action-cell">
                <a
                  href="{{ route('admin.numbers.edit', array_filter([
                    'phoneNumber' => $number,
                    'service_type_filter' => $selectedServiceType ?? null,
                  ], static fn ($value) => $value !== null && $value !== '')) }}"
                  class="admin-button admin-button--secondary admin-button--compact"
                >
                  แก้ไข
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="admin-muted">
                {{ ($search ?? '') !== '' ? 'ไม่พบเบอร์ที่ตรงกับคำค้นหา' : 'ยังไม่มีข้อมูลเบอร์ในระบบ' }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($numbers->hasPages())
      <nav class="admin-pagination" aria-label="เปลี่ยนหน้ารายการเบอร์">
        @if ($numbers->onFirstPage())
          <span>ก่อนหน้า</span>
        @else
          <a href="{{ $numbers->appends(request()->query())->previousPageUrl() }}">ก่อนหน้า</a>
        @endif

        @php
          $startPage = max(1, $numbers->currentPage() - 2);
          $endPage = min($numbers->lastPage(), $numbers->currentPage() + 2);
        @endphp

        @for ($page = $startPage; $page <= $endPage; $page++)
          @if ($page === $numbers->currentPage())
            <span class="is-active">{{ $page }}</span>
          @else
            <a href="{{ $numbers->appends(request()->query())->url($page) }}">{{ $page }}</a>
          @endif
        @endfor

        @if ($numbers->hasMorePages())
          <a href="{{ $numbers->appends(request()->query())->nextPageUrl() }}">ถัดไป</a>
        @else
          <span>ถัดไป</span>
        @endif
      </nav>
    @endif
  </section>
@endsection
