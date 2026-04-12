@extends('layouts.admin')

@section('title', 'Supernumber Admin | รายละเอียด Lead เลือกเบอร์')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>รายละเอียด Lead เลือกเบอร์</h1>
      <p class="admin-subtitle">ข้อมูลที่ลูกค้ากรอกจากหน้า <code>/estimate</code> พร้อมประวัติการแจ้งเตือน LINE ล่าสุด</p>
    </div>
    <div class="admin-page-actions" style="margin-left: 0; margin-right: auto;">
      <a href="{{ route('admin.estimate-leads') }}" class="admin-button admin-button--muted admin-button--compact">กลับ</a>
    </div>
  </div>

  @php
    $submittedAt = $estimateLead->submitted_at ?? $estimateLead->created_at;

    $eventLabels = [
      'estimate_submitted' => 'lead ใหม่จากฟอร์มเลือกเบอร์',
    ];

    $statusLabels = [
      'queued' => 'queued',
      'sent' => 'sent',
      'failed' => 'failed',
    ];
  @endphp

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table" style="table-layout: fixed;">
        <tbody>
          <tr>
            <th style="width: 220px;">Lead ID</th>
            <td>#{{ $estimateLead->id }}</td>
          </tr>
          <tr>
            <th>เวลาที่ส่งข้อมูล</th>
            <td>{{ $submittedAt?->timezone('Asia/Bangkok')->format('Y-m-d H:i:s') ?: '-' }}</td>
          </tr>
          <tr>
            <th>ชื่อ</th>
            <td>{{ $estimateLead->full_name !== '' ? $estimateLead->full_name : '-' }}</td>
          </tr>
          <tr>
            <th>อีเมล</th>
            <td>
              @if ($estimateLead->email)
                <a href="mailto:{{ $estimateLead->email }}">{{ $estimateLead->email }}</a>
              @else
                -
              @endif
            </td>
          </tr>
          <tr>
            <th>เบอร์หลัก</th>
            <td>
              @if ($estimateLead->main_phone)
                <a href="tel:{{ $estimateLead->main_phone }}">{{ $estimateLead->main_phone }}</a>
              @else
                -
              @endif
            </td>
          </tr>
          <tr>
            <th>เบอร์ปัจจุบัน</th>
            <td>
              @if ($estimateLead->current_phone)
                <a href="tel:{{ $estimateLead->current_phone }}">{{ $estimateLead->current_phone }}</a>
              @else
                -
              @endif
            </td>
          </tr>
          <tr>
            <th>เพศ</th>
            <td>{{ $estimateLead->gender_label }}</td>
          </tr>
          <tr>
            <th>วันเกิด</th>
            <td>{{ $estimateLead->birthday?->format('Y-m-d') ?: '-' }}</td>
          </tr>
          <tr>
            <th>ลักษณะงาน</th>
            <td>{{ $estimateLead->work_type_label }}</td>
          </tr>
          <tr>
            <th>เป้าหมาย</th>
            <td>{{ $estimateLead->goal_label }}</td>
          </tr>
          <tr>
            <th>IP Address</th>
            <td>{{ $estimateLead->ip_address ?: '-' }}</td>
          </tr>
          <tr>
            <th>User Agent</th>
            <td style="white-space: normal; line-height: 1.7; word-break: break-word;">{{ $estimateLead->user_agent ?: '-' }}</td>
          </tr>
          <tr>
            <th>บันทึกในระบบเมื่อ</th>
            <td>{{ $estimateLead->created_at?->timezone('Asia/Bangkok')->format('Y-m-d H:i:s') ?: '-' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <section class="admin-card admin-table-card" style="margin-top: 18px;">
    <div style="padding: 18px 20px 0;">
      <h2 style="margin: 0; font-size: 1.1rem;">ประวัติ LINE Notification</h2>
      <p class="admin-subtitle" style="margin-top: 6px;">แสดงรายการส่งล่าสุด 20 ครั้งของ lead นี้</p>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table" style="table-layout: fixed;">
        <thead>
          <tr>
            <th style="width: 170px;">เวลา</th>
            <th style="width: 180px;">เหตุการณ์</th>
            <th style="width: 120px;">สถานะ</th>
            <th style="width: 90px;">ครั้งที่ส่ง</th>
            <th style="width: 110px;">HTTP</th>
            <th>ข้อความ / ปลายทาง</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($estimateLead->lineNotificationLogs as $log)
            <tr>
              <td>{{ $log->created_at?->timezone('Asia/Bangkok')->format('Y-m-d H:i:s') ?: '-' }}</td>
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
              <td colspan="6" class="admin-muted">ยังไม่มีประวัติการส่ง LINE สำหรับ lead นี้</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@endsection
