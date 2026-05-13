@extends('layouts.admin')

@section('title', 'Supernumber Admin | รายละเอียดคำสั่งซื้อ')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>รายละเอียดคำสั่งซื้อ</h1>
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
            <th>ยอดชำระแรก</th>
            <td>{{ $order->payment_label }}</td>
          </tr>
          @if ($order->is_postpaid)
            <tr>
              <th>แพ็กเกจ</th>
              <td>{{ $order->package_label }}</td>
            </tr>
          @endif
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
            <td>{{ $order->status_label ?: '-' }}</td>
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
                  $slipUrl = route('admin.orders.payment-slip', $order) . '?v=' . time();
                  $slipViewerUrl = route('admin.orders.payment-slip.view', $order) . '?v=' . time();
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
  @if (session('admin_user_role') === 'manager')
  <section class="admin-card admin-table-card" style="margin-top: 18px;">
    <div style="padding: 18px 20px 0;">
      <h2 style="margin: 0; font-size: 1.1rem;">ประวัติการแก้ไข (สำหรับเบอร์ {{ $order->ordered_number }})</h2>
      <p class="admin-subtitle" style="margin-top: 6px;">แสดงบันทึกการเปลี่ยนแปลงของทุกคำสั่งซื้อที่ใช้เบอร์นี้</p>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table" style="table-layout: fixed;">
        <thead>
          <tr>
            <th style="width: 170px;">เวลา</th>
            <th style="width: 140px;">ผู้แก้ไข</th>
            <th style="width: 100px;">Order ID</th>
            <th style="width: 100px;">การกระทำ</th>
            <th>รายละเอียดการเปลี่ยนแปลง</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($order->activityLogs as $log)
            <tr>
              <td>{{ optional($log->created_at)->format('Y-m-d H:i:s') ?: '-' }}</td>
              <td>{{ $log->user?->name ?: 'System / Unknown' }}</td>
              <td>
                <span class="{{ $log->customer_order_id === $order->id ? 'admin-pill' : 'admin-pill admin-pill--muted' }}" style="font-size: 0.75rem;">
                  #{{ $log->customer_order_id }}
                </span>
              </td>
              <td>{{ $log->action }}</td>
              <td>
                <div style="display: grid; gap: 4px;">
                  @if ($log->changes)
                    @foreach ($log->changes as $field => $change)
                      <div style="font-size: 0.9rem;">
                        <strong>{{ $field }}:</strong>
                        @if ($field === 'payment_slip_path')
                          <span style="color: #b42318; text-decoration: line-through; font-size: 0.8rem;">
                            {{ $change['old'] ?: 'None' }}
                          </span>
                          @if ($change['old'])
                            <a href="{{ asset('storage/' . $change['old']) }}" target="_blank" style="text-decoration: underline; color: #b42318; margin-left: 4px;">[ดูรูปเก่า]</a>
                          @endif
                          <span style="color: #1b8b6f; margin-left: 4px;">
                            &rarr; {{ $change['new'] ?: 'None' }}
                          </span>
                          @if ($change['new'])
                            <a href="{{ asset('storage/' . $change['new']) }}" target="_blank" style="text-decoration: underline; color: #1b8b6f; margin-left: 4px;">[ดูรูปใหม่]</a>
                          @endif
                        @else
                          <span style="color: #b42318; text-decoration: line-through;">{{ is_scalar($change['old']) ? $change['old'] : json_encode($change['old']) }}</span>
                          <span style="color: #1b8b6f;">&rarr; {{ is_scalar($change['new']) ? $change['new'] : json_encode($change['new']) }}</span>
                        @endif
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
              <td colspan="5" class="admin-muted">ยังไม่มีประวัติการแก้ไขสำหรับเบอร์นี้</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
  @endif
@endsection
