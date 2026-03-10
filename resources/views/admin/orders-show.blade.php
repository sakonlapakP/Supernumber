@extends('layouts.admin')

@section('title', 'Supernumber Admin | Order Detail')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Order Detail</h1>
      <p class="admin-subtitle">รายละเอียดคำสั่งซื้อทั้งหมด รวมหลักฐานการโอน</p>
    </div>
    <div class="admin-page-actions" style="margin-left: 0; margin-right: auto;">
      <a href="{{ route('admin.orders') }}" class="admin-button admin-button--muted admin-button--compact">กลับ</a>
      <a href="{{ route('admin.orders.edit', $order) }}" class="admin-button admin-button--compact">แก้ไข</a>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table" style="table-layout: fixed;">
        <tbody>
          <tr>
            <th style="width: 220px;">เบอร์ที่สั่งซื้อ</th>
            <td>{{ $order->ordered_number ?: '-' }}</td>
          </tr>
          <tr>
            <th>แพคเกจ</th>
            <td>{{ number_format((int) $order->selected_package) }} บาท / เดือน</td>
          </tr>
          <tr>
            <th>ชื่อผู้สั่งซื้อ</th>
            <td>{{ $order->full_name ?: '-' }}</td>
          </tr>
          <tr>
            <th>โทรศัพท์</th>
            <td>{{ $order->current_phone ?: '-' }}</td>
          </tr>
          <tr>
            <th>อีเมล</th>
            <td>{{ $order->email ?: '-' }}</td>
          </tr>
          <tr>
            <th>ที่อยู่จัดส่ง</th>
            <td>{{ $order->shipping_address ?: '-' }}</td>
          </tr>
          <tr>
            <th>วันนัดหมาย</th>
            <td>{{ optional($order->appointment_date)->format('Y-m-d') ?: '-' }}</td>
          </tr>
          <tr>
            <th>ช่วงเวลานัดหมาย</th>
            <td>{{ $order->appointment_time_slot ?: '-' }}</td>
          </tr>
          <tr>
            <th>สถานะ</th>
            <td>{{ $order->status ?: '-' }}</td>
          </tr>
          <tr>
            <th>บันทึกเมื่อ</th>
            <td>{{ optional($order->created_at)->format('Y-m-d H:i:s') ?: '-' }}</td>
          </tr>
          <tr>
            <th>หลักฐานการโอน</th>
            <td>
              @if ($order->payment_slip_path)
                @php
                  $slipUrl = asset('storage/' . $order->payment_slip_path);
                  $ext = strtolower(pathinfo((string) $order->payment_slip_path, PATHINFO_EXTENSION));
                  $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'heic', 'heif'], true);
                @endphp

                <div style="display: grid; gap: 10px;">
                  <a href="{{ $slipUrl }}" target="_blank" rel="noopener noreferrer">เปิดไฟล์ต้นฉบับ</a>
                  @if ($isImage)
                    <img src="{{ $slipUrl }}" alt="หลักฐานการโอน" style="max-width: 520px; width: 100%; border-radius: 10px; border: 1px solid rgba(185, 199, 224, 0.5);" />
                  @else
                    <iframe src="{{ $slipUrl }}" title="หลักฐานการโอน" style="width: 100%; min-height: 420px; border-radius: 10px; border: 1px solid rgba(185, 199, 224, 0.5);"></iframe>
                  @endif
                </div>
              @else
                -
              @endif
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
@endsection
