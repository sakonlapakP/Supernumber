@extends('layouts.admin')

@section('title', 'Supernumber Admin | All Numbers')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>All Numbers</h1>
      <p class="admin-subtitle">แสดงเบอร์ทั้งหมดในระบบ พร้อมสถานะของแต่ละเบอร์</p>
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

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>เบอร์</th>
            <th>เครือข่าย</th>
            <th>แพ็กเกจ</th>
            <th>สถานะ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($numbers as $number)
            <tr>
              <td>
                <div class="admin-number">{{ $number->display_number ?: $number->phone_number }}</div>
              </td>
              <td>{{ strtoupper(str_replace('_', '-', $number->network_code)) }}</td>
              <td>{{ $number->package_label }}</td>
              <td>{{ $number->status ?: '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="admin-muted">ยังไม่มีข้อมูลเบอร์ในระบบ</td>
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
          <a href="{{ $numbers->previousPageUrl() }}">ก่อนหน้า</a>
        @endif

        @php
          $startPage = max(1, $numbers->currentPage() - 2);
          $endPage = min($numbers->lastPage(), $numbers->currentPage() + 2);
        @endphp

        @for ($page = $startPage; $page <= $endPage; $page++)
          @if ($page === $numbers->currentPage())
            <span class="is-active">{{ $page }}</span>
          @else
            <a href="{{ $numbers->url($page) }}">{{ $page }}</a>
          @endif
        @endfor

        @if ($numbers->hasMorePages())
          <a href="{{ $numbers->nextPageUrl() }}">ถัดไป</a>
        @else
          <span>ถัดไป</span>
        @endif
      </nav>
    @endif
  </section>
@endsection
