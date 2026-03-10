@extends('layouts.admin')

@section('title', 'Supernumber Admin | Orders')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Orders</h1>
      <p class="admin-subtitle">รายการคำสั่งซื้อจากลูกค้าหน้าเว็บ พร้อมหลักฐานการโอน</p>
    </div>
    <div class="admin-summary">
      แสดง
      {{ $orders->count() ? $orders->firstItem() . '-' . $orders->lastItem() : '0' }}
      จาก {{ number_format($orders->total()) }} รายการ
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
            <th>เบอร์ที่สั่งซื้อ</th>
            <th>แพคเกจ</th>
            <th>ชื่อผู้สั่งซื้อ</th>
            <th>โทรศัพท์</th>
            <th>สถานะ</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($orders as $order)
            <tr>
              <td><div class="admin-number">{{ $order->ordered_number ?: '-' }}</div></td>
              <td>{{ number_format((int) $order->selected_package) }} บาท / เดือน</td>
              <td>{{ $order->full_name ?: '-' }}</td>
              <td>{{ $order->current_phone ?: '-' }}</td>
              <td>{{ $order->status ?: '-' }}</td>
              <td>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                  <a href="{{ route('admin.orders.show', $order) }}" class="admin-button admin-button--muted admin-button--compact">ดูรายละเอียด</a>
                  <a href="{{ route('admin.orders.edit', $order) }}" class="admin-button admin-button--muted admin-button--compact">แก้ไข</a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="admin-muted">ยังไม่มีรายการคำสั่งซื้อ</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($orders->hasPages())
      <nav class="admin-pagination" aria-label="เปลี่ยนหน้ารายการคำสั่งซื้อ">
        @if ($orders->onFirstPage())
          <span>ก่อนหน้า</span>
        @else
          <a href="{{ $orders->previousPageUrl() }}">ก่อนหน้า</a>
        @endif

        @php
          $startPage = max(1, $orders->currentPage() - 2);
          $endPage = min($orders->lastPage(), $orders->currentPage() + 2);
        @endphp

        @for ($page = $startPage; $page <= $endPage; $page++)
          @if ($page === $orders->currentPage())
            <span class="is-active">{{ $page }}</span>
          @else
            <a href="{{ $orders->url($page) }}">{{ $page }}</a>
          @endif
        @endfor

        @if ($orders->hasMorePages())
          <a href="{{ $orders->nextPageUrl() }}">ถัดไป</a>
        @else
          <span>ถัดไป</span>
        @endif
      </nav>
    @endif
  </section>
@endsection
