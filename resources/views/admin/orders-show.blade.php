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
      @if ($canTestLineNotification)
        <form action="{{ route('admin.orders.line-test', $order) }}" method="post" style="margin: 0;">
          @csrf
          <button type="submit" class="admin-button admin-button--secondary admin-button--compact">ทดสอบส่ง LINE</button>
        </form>
      @endif
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
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
            <th>ประเภท</th>
            <td>{{ $order->service_type_label }}</td>
          </tr>
          <tr>
            <th>ยอดชำระ</th>
            <td>{{ $order->payment_label }}</td>
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
                  $slipUrl = route('admin.orders.payment-slip', $order);
                  $slipViewerUrl = route('admin.orders.payment-slip.view', $order);
                @endphp

                <div style="display: grid; gap: 10px;">
                  <div class="admin-muted" style="font-size: 0.9rem;">
                    path: {{ $paymentSlip['stored_path'] ?: '-' }}
                  </div>

                  @if ($paymentSlip['exists'])
                    <a href="{{ $slipViewerUrl }}" target="_blank" rel="noopener noreferrer">เปิดไฟล์ต้นฉบับ</a>

                    @if ($paymentSlip['is_image'])
                      <img src="{{ $slipUrl }}" alt="หลักฐานการโอน" style="max-width: 520px; width: 100%; border-radius: 10px; border: 1px solid rgba(185, 199, 224, 0.5);" />
                    @else
                      <iframe src="{{ $slipUrl }}" title="หลักฐานการโอน" style="width: 100%; min-height: 420px; border-radius: 10px; border: 1px solid rgba(185, 199, 224, 0.5);"></iframe>
                    @endif
                  @else
                    <div style="padding: 12px 14px; border-radius: 10px; background: #fff4e5; color: #9a6700; border: 1px solid #f5d18c;">
                      ไม่พบไฟล์หลักฐานการโอนใน storage ของเซิร์ฟเวอร์สำหรับ path นี้
                    </div>
                  @endif

                  @if ($paymentSlip['mime_type'])
                    <div class="admin-muted" style="font-size: 0.86rem;">
                      MIME: {{ $paymentSlip['mime_type'] }}
                    </div>
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

  @php
    $eventLabels = [
      'order_submitted' => 'คำสั่งซื้อใหม่',
      'order_status_updated' => 'อัปเดตสถานะ',
      'order_admin_test' => 'ทดสอบจากแอดมิน',
    ];

    $statusLabels = [
      'queued' => 'queued',
      'sent' => 'sent',
      'failed' => 'failed',
    ];
  @endphp

  <section class="admin-card admin-table-card" style="margin-top: 18px;">
    <div style="padding: 18px 20px 0;">
      <h2 style="margin: 0; font-size: 1.1rem;">ประวัติ LINE Notification</h2>
      <p class="admin-subtitle" style="margin-top: 6px;">แสดงรายการส่งล่าสุด 20 ครั้งของคำสั่งซื้อนี้</p>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table" style="table-layout: fixed;">
        <thead>
          <tr>
            <th style="width: 170px;">เวลา</th>
            <th style="width: 140px;">เหตุการณ์</th>
            <th style="width: 120px;">สถานะ</th>
            <th style="width: 90px;">ครั้งที่ส่ง</th>
            <th style="width: 110px;">HTTP</th>
            <th>ข้อความ / ปลายทาง</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($order->lineNotificationLogs as $log)
            <tr>
              <td>{{ optional($log->created_at)->format('Y-m-d H:i:s') ?: '-' }}</td>
              <td>{{ $eventLabels[$log->event_type] ?? $log->event_type }}</td>
              <td>{{ $statusLabels[$log->status] ?? $log->status }}</td>
              <td>{{ (int) $log->attempts }}</td>
              <td>{{ $log->response_status ?: '-' }}</td>
              <td>
                <div style="display: grid; gap: 8px;">
                  <div style="white-space: pre-line;">{{ $log->message_preview ?: '-' }}</div>
                  <div class="admin-muted" style="font-size: 0.86rem;">
                    ปลายทาง: {{ $log->destination_key ?: 'default' }} / {{ $log->destination_id ?: '-' }}
                  </div>
                  @if ($log->error_message)
                    <div style="color: #b42318;">{{ $log->error_message }}</div>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="admin-muted">ยังไม่มีประวัติการส่ง LINE สำหรับคำสั่งซื้อนี้</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@endsection
