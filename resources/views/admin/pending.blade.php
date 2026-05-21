@extends('layouts.admin')

@section('title', 'Supernumber Admin | รอการอนุมัติ')

@section('content')
  <section class="admin-auth">
    <div class="admin-card admin-login-card" style="text-align: center;">
      <div style="font-size: 48px; margin-bottom: 12px;">⏳</div>
      <h1>รอการอนุมัติ</h1>
      <p class="admin-subtitle" style="margin-bottom: 24px;">
        @if(session('pending_username'))
          บัญชี <strong>{{ session('pending_username') }}</strong> ถูกสร้างเรียบร้อยแล้ว<br>
        @endif
        กรุณารอให้ Manager อนุมัติการเข้าใช้งานก่อน
      </p>

      <div style="background: var(--admin-bg, #f9fafb); border: 1px solid var(--admin-border, #e5e7eb); border-radius: 8px; padding: 16px; margin-bottom: 24px; text-align: left;">
        <p style="margin: 0 0 6px; font-size: 13px; color: var(--admin-muted, #6b7280);">ขั้นตอนถัดไป</p>
        <ol style="margin: 0; padding-left: 20px; font-size: 14px; line-height: 1.8;">
          <li>Manager จะเห็นบัญชีของคุณในหน้า <strong>ผู้ใช้งาน</strong></li>
          <li>Manager จะกำหนดสิทธิ์และกดอนุมัติ</li>
          <li>หลังได้รับการอนุมัติ คุณสามารถเข้าสู่ระบบได้ทันที</li>
        </ol>
      </div>

      <a href="{{ route('admin.login') }}" class="admin-button" style="display: inline-block; text-decoration: none;">
        ไปหน้าเข้าสู่ระบบ
      </a>
    </div>
  </section>
@endsection
