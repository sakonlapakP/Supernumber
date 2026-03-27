@extends('layouts.admin')

@section('title', 'Supernumber Admin | ' . ($pageTitle ?? 'All Numbers'))

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>{{ $pageTitle ?? 'All Numbers' }}</h1>
      <p class="admin-subtitle">{{ $pageSubtitle ?? 'แสดงเบอร์ทั้งหมดในระบบ พร้อมสถานะของแต่ละเบอร์' }}</p>
    </div>
    <div class="admin-summary">
      แสดง
      {{ $numbers->count() ? $numbers->firstItem() . '-' . $numbers->lastItem() : '0' }}
      จาก {{ number_format($numbers->total()) }} เบอร์
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <section class="admin-card admin-feature-card admin-feature-card--compact">
    <div class="admin-feature-card__head">
      <div>
        <h2 class="admin-feature-card__title">Search Numbers</h2>
        <p class="admin-feature-card__hint">
          ค้นหาจากเบอร์, ผลรวม, เครือข่าย, แพ็กเกจ, ราคา หรือสถานะ
          @if (($selectedServiceType ?? null) === \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID)
            | กำลังกรอง: รายเดือน
          @elseif (($selectedServiceType ?? null) === \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID)
            | กำลังกรอง: เติมเงิน
          @endif
        </p>
      </div>
    </div>

    <form action="{{ route('admin.numbers') }}" method="get" class="admin-form admin-form--inline">
      @if (($selectedServiceType ?? null) !== null)
        <input type="hidden" name="service_type" value="{{ $selectedServiceType }}" />
      @endif
      <div class="admin-field">
        <label for="admin-number-search">Search</label>
        <input
          id="admin-number-search"
          class="admin-input"
          type="text"
          name="q"
          value="{{ $search ?? '' }}"
          placeholder="เช่น 064929, dtac, 1499, active"
        />
      </div>
      <button type="submit" class="admin-button admin-button--compact">Search</button>
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
            <th>Action</th>
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
              <td>{{ $number->status ?: '-' }}</td>
              <td class="admin-action-cell">
                <a
                  href="{{ route('admin.numbers.edit', array_filter([
                    'phoneNumber' => $number,
                    'service_type_filter' => $selectedServiceType ?? null,
                  ], static fn ($value) => $value !== null && $value !== '')) }}"
                  class="admin-button admin-button--secondary admin-button--compact"
                >
                  Edit
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
