@extends('layouts.admin')

@section('title', 'Supernumber Admin | สร้างยูเซอร์ใหม่')

@section('content')
  <section class="admin-auth">
    <div class="admin-card admin-login-card">
      <h1>สร้างยูเซอร์ใหม่</h1>
      <p class="admin-subtitle">กรุณากรอกรายละเอียดเพื่อขอสิทธิ์การใช้งานระบบ หลังสมัครแล้วต้องรอ Manager อนุมัติ</p>

      @if ($errors->any())
        <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
      @endif

      <form id="register-form" class="admin-form" action="{{ route('admin.register.store') }}" method="post">
        @csrf
        <div class="admin-field">
          <label for="reg-name">ชื่อ-นามสกุล</label>
          <input
            id="reg-name"
            class="admin-input"
            type="text"
            name="name"
            value="{{ old('name') }}"
            required
            autofocus
          />
        </div>
        <div class="admin-field">
          <label for="reg-username">ชื่อผู้ใช้ (Username)</label>
          <input
            id="reg-username"
            class="admin-input"
            type="text"
            name="username"
            value="{{ old('username') }}"
            required
          />
        </div>
        <div class="admin-field">
          <label for="reg-email">อีเมล</label>
          <input
            id="reg-email"
            class="admin-input"
            type="email"
            name="email"
            value="{{ old('email') }}"
            required
          />
        </div>
        <div class="admin-field">
          <label for="reg-password">รหัสผ่าน</label>
          <input
            id="reg-password"
            class="admin-input"
            type="password"
            name="password"
            required
            minlength="8"
          />
        </div>
        <div class="admin-field">
          <label for="reg-password-confirm">ยืนยันรหัสผ่าน</label>
          <input
            id="reg-password-confirm"
            class="admin-input"
            type="password"
            name="password_confirmation"
            required
            minlength="8"
          />
          <small id="password-match-msg" style="display: none; color: var(--admin-red); margin-top: 4px;"></small>
        </div>
        <button type="submit" id="submit-btn" class="admin-button">ส่งข้อมูลสมัครสมาชิก</button>
        
        <div style="text-align: center; margin-top: 20px;">
          <a href="{{ route('admin.login') }}" class="admin-muted" style="text-decoration: none;">ย้อนกลับไปหน้าเข้าสู่ระบบ</a>
        </div>
      </form>
    </div>
  </section>

  <script>
    (() => {
      const form = document.getElementById('register-form');
      const pass = document.getElementById('reg-password');
      const passConfirm = document.getElementById('reg-password-confirm');
      const msg = document.getElementById('password-match-msg');
      const submitBtn = document.getElementById('submit-btn');

      function validatePasswords() {
        if (passConfirm.value === '') {
          msg.style.display = 'none';
          submitBtn.disabled = false;
          return;
        }

        if (pass.value !== passConfirm.value) {
          msg.textContent = 'รหัสผ่านไม่ตรงกัน';
          msg.style.display = 'block';
          submitBtn.disabled = true;
          passConfirm.style.borderColor = 'var(--admin-red)';
        } else {
          msg.style.display = 'none';
          submitBtn.disabled = false;
          passConfirm.style.borderColor = '';
        }
      }

      pass.addEventListener('input', validatePasswords);
      passConfirm.addEventListener('input', validatePasswords);
    })();
  </script>
@endsection
