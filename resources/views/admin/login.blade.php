@extends('layouts.admin')

@section('title', 'Supernumber Admin | เข้าสู่ระบบ')

@section('content')
  <section class="admin-auth">
    <div class="admin-card admin-login-card">
      <h1>Admin Login</h1>
      <p class="admin-subtitle">เข้าสู่ระบบหลังบ้านเพื่อดูรายการเบอร์ทั้งหมด และจัดการเบอร์ที่อยู่ในสถานะ hold</p>

      @if ($errors->any())
        <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
      @endif

      <form class="admin-form" action="{{ route('admin.login.attempt') }}" method="post">
        @csrf
        <div class="admin-field">
          <label for="admin-username">ชื่อผู้ใช้</label>
          <input
            id="admin-username"
            class="admin-input"
            type="text"
            name="username"
            value="{{ old('username') }}"
            autocomplete="username"
            required
          />
        </div>
        <div class="admin-field">
          <label for="admin-password">รหัสผ่าน</label>
          <input
            id="admin-password"
            class="admin-input"
            type="password"
            name="password"
            autocomplete="current-password"
            required
          />
        </div>
        <button type="submit" class="admin-button">เข้าสู่ระบบ</button>
      </form>
    </div>
  </section>
@endsection
