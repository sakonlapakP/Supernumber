@extends('layouts.admin')

@section('title', 'Supernumber Admin | Activity Logs')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Activity Logs</h1>
      <p class="admin-subtitle">บันทึกว่าใครเปลี่ยนข้อมูลเมื่อไร และเปลี่ยนสถานะจากอะไรเป็นอะไร</p>
    </div>
    <div class="admin-summary">
      แสดง
      {{ $logs->count() ? $logs->firstItem() . '-' . $logs->lastItem() : '0' }}
      จาก {{ number_format($logs->total()) }} รายการ
    </div>
  </div>

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>เวลา</th>
            <th>ผู้ใช้</th>
            <th>เบอร์</th>
            <th>Action</th>
            <th>เปลี่ยนสถานะ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($logs as $log)
            <tr>
              <td>{{ $log->created_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
              <td>{{ $log->user?->name ?: 'Unknown' }}</td>
              <td>{{ $log->phoneNumber?->display_number ?: $log->phoneNumber?->phone_number ?: '-' }}</td>
              <td>{{ $log->action }}</td>
              <td>{{ ($log->from_status ?: '-') . ' -> ' . ($log->to_status ?: '-') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="admin-muted">ยังไม่มี log การเปลี่ยนแปลงข้อมูล</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($logs->hasPages())
      <nav class="admin-pagination" aria-label="เปลี่ยนหน้ารายการ activity logs">
        @if ($logs->onFirstPage())
          <span>ก่อนหน้า</span>
        @else
          <a href="{{ $logs->previousPageUrl() }}">ก่อนหน้า</a>
        @endif

        @php
          $startPage = max(1, $logs->currentPage() - 2);
          $endPage = min($logs->lastPage(), $logs->currentPage() + 2);
        @endphp

        @for ($page = $startPage; $page <= $endPage; $page++)
          @if ($page === $logs->currentPage())
            <span class="is-active">{{ $page }}</span>
          @else
            <a href="{{ $logs->url($page) }}">{{ $page }}</a>
          @endif
        @endfor

        @if ($logs->hasMorePages())
          <a href="{{ $logs->nextPageUrl() }}">ถัดไป</a>
        @else
          <span>ถัดไป</span>
        @endif
      </nav>
    @endif
  </section>
@endsection
