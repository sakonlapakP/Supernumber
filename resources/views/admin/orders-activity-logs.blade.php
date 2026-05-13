@extends('layouts.admin')

@section('title', 'Supernumber Admin | ประวัติแก้ไขคำสั่งซื้อ')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>ประวัติแก้ไขคำสั่งซื้อ</h1>
      <p class="admin-subtitle">บันทึกการเปลี่ยนแปลงข้อมูลคำสั่งซื้อทั้งหมดในระบบ</p>
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
            <th style="width: 170px;">เวลา</th>
            <th style="width: 140px;">ผู้แก้ไข</th>
            <th style="width: 180px;">คำสั่งซื้อ (เบอร์)</th>
            <th style="width: 120px;">การกระทำ</th>
            <th>รายละเอียดการเปลี่ยนแปลง</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($logs as $log)
            <tr>
              <td>{{ $log->created_at?->format('Y-m-d H:i:s') ?: '-' }}</td>
              <td>{{ $log->user?->name ?: 'System / Unknown' }}</td>
              <td>
                @if ($log->customerOrder)
                  <a href="{{ route('admin.orders.show', $log->customerOrder) }}" style="text-decoration: underline;">
                    {{ $log->customerOrder->ordered_number ?: 'Order #' . $log->customer_order_id }}
                  </a>
                @else
                  Order #{{ $log->customer_order_id }} (Deleted)
                @endif
              </td>
              <td>{{ $log->action }}</td>
              <td>
                <div style="display: grid; gap: 4px;">
                  @if ($log->changes)
                    @foreach ($log->changes as $field => $change)
                      <div style="font-size: 0.9rem;">
                        <strong>{{ $field }}:</strong>
                        <span style="color: #b42318; text-decoration: line-through;">{{ is_scalar($change['old']) ? $change['old'] : json_encode($change['old']) }}</span>
                        <span style="color: #1b8b6f;">&rarr; {{ is_scalar($change['new']) ? $change['new'] : json_encode($change['new']) }}</span>
                      </div>
                    @endforeach
                  @else
                    <span class="admin-muted">-</span>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="admin-muted">ยังไม่มีบันทึกการแก้ไขคำสั่งซื้อ</td>
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
