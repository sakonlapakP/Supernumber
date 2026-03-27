@extends('layouts.admin')

@section('title', 'Supernumber Admin | LINE Settings')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>LINE Settings</h1>
      <p class="admin-subtitle">ตั้งค่า LINE, Webhook และเลือก `groupId` ที่ระบบดักมาได้จากหน้าแอดมิน</p>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
  @endif

  <section class="admin-card admin-feature-card">
    <form class="admin-form" action="{{ route('admin.line-settings.update') }}" method="post">
      @csrf

      <div class="admin-field">
        <label for="line_channel_access_token">LINE_CHANNEL_ACCESS_TOKEN</label>
        <textarea
          id="line_channel_access_token"
          class="admin-input"
          name="line_channel_access_token"
          rows="4"
          placeholder="ใส่ Channel access token ของ LINE Messaging API"
        >{{ old('line_channel_access_token', $settings['LINE_CHANNEL_ACCESS_TOKEN'] ?? '') }}</textarea>
        <p class="admin-muted" style="margin: 8px 0 0; font-size: 0.9rem;">ค่านี้จะถูกเขียนลงไฟล์ `.env` และใช้เป็น token หลักสำหรับส่ง LINE push message</p>
      </div>

      <div class="admin-field">
        <label for="line_channel_secret">LINE_CHANNEL_SECRET</label>
        <input
          id="line_channel_secret"
          class="admin-input"
          type="text"
          name="line_channel_secret"
          value="{{ old('line_channel_secret', $settings['LINE_CHANNEL_SECRET'] ?? '') }}"
          placeholder="ใส่ Channel secret สำหรับตรวจสอบ X-Line-Signature"
        />
        <p class="admin-muted" style="margin: 8px 0 0; font-size: 0.9rem;">ค่านี้ใช้ตรวจสอบว่า webhook มาจาก LINE จริง ถ้ายังไม่ใส่ ระบบจะรับ webhook ได้แต่จะไม่ verify signature</p>
      </div>

      <div class="admin-field">
        <label for="line_group_id">LINE_GROUP_ID</label>
        <input
          id="line_group_id"
          class="admin-input"
          type="text"
          name="line_group_id"
          value="{{ old('line_group_id', $settings['LINE_GROUP_ID'] ?? '') }}"
          placeholder="เช่น Cxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
        />
        <p class="admin-muted" style="margin: 8px 0 0; font-size: 0.9rem;">ค่านี้เป็น default/fallback group สำหรับระบบ LINE ทั้งหมด ถ้าไม่ได้ตั้งค่า group override ราย event</p>
      </div>

      <div class="admin-muted" style="margin-bottom: 18px; font-size: 0.92rem;">
        หลังบันทึก ระบบจะ clear config cache ให้ทันที ถ้า environment นี้ใช้ config cache อยู่
      </div>

      <button type="submit" class="admin-button">บันทึก LINE Settings</button>
    </form>
  </section>

  <section class="admin-card admin-feature-card" style="margin-top: 18px;">
    <div class="admin-field">
      <label for="line_webhook_url">Webhook URL</label>
      <input id="line_webhook_url" class="admin-input" type="text" value="{{ $webhookUrl }}" readonly />
      <p class="admin-muted" style="margin: 8px 0 0; font-size: 0.9rem;">นำ URL นี้ไปใส่ใน LINE Developers > Messaging API > Webhook URL แล้วเปิด `Use webhook` และ `Allow bot to join group chats`</p>
      @if (! str_starts_with($webhookUrl, 'https://'))
        <p style="margin: 10px 0 0; color: #b45309; font-size: 0.92rem;">Webhook URL นี้ยังไม่ใช่ `https://` แบบ public ซึ่ง LINE จะไม่ยอมยิงเข้ามา ต้องใช้งานผ่านโดเมน public HTTPS เท่านั้น</p>
      @endif
    </div>
  </section>

  <section class="admin-card admin-table-card" style="margin-top: 18px;">
    <div style="padding: 18px 20px 0;">
      <h2 style="margin: 0; font-size: 1.1rem;">Webhook Events ล่าสุด</h2>
      <p class="admin-subtitle" style="margin-top: 6px;">ถ้ามีข้อความจากกลุ่มเข้ามา จะเห็น `groupId` ที่นี่ และกดเอาไปใช้เป็น `LINE_GROUP_ID` ได้ทันที</p>
    </div>

    <div class="admin-table-wrap">
      <table class="admin-table" style="table-layout: fixed;">
        <thead>
          <tr>
            <th style="width: 170px;">เวลา</th>
            <th style="width: 120px;">Event</th>
            <th style="width: 110px;">Source</th>
            <th style="width: 280px;">Group ID</th>
            <th style="width: 120px;">Signature</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($webhookEvents as $event)
            <tr>
              <td>{{ optional($event->received_at)->format('Y-m-d H:i:s') ?: '-' }}</td>
              <td>{{ $event->event_type ?: '-' }}</td>
              <td>{{ $event->source_type ?: '-' }}</td>
              <td style="word-break: break-all;">{{ $event->group_id ?: '-' }}</td>
              <td>
                @if ($event->signature_valid === true)
                  valid
                @elseif ($event->signature_valid === false)
                  invalid
                @else
                  not set
                @endif
              </td>
              <td>
                @if ($event->group_id)
                  <form action="{{ route('admin.line-settings.apply-group-id') }}" method="post" style="margin: 0;">
                    @csrf
                    <input type="hidden" name="group_id" value="{{ $event->group_id }}" />
                    <button type="submit" class="admin-button admin-button--compact">ใช้ค่านี้</button>
                  </form>
                @else
                  <span class="admin-muted">-</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="admin-muted">ยังไม่มี webhook event เข้ามา ให้ตั้ง Webhook URL แล้วส่งข้อความจากกลุ่ม LINE สัก 1 ข้อความ</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@endsection
