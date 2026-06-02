<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบ — ลิเกไลฟ์อินเดอะเธียเตอร์</title>
  <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Sarabun', sans-serif;
      background: #f0f0f0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .card {
      background: #fff;
      border-radius: 16px;
      padding: 36px 32px;
      width: 100%;
      max-width: 380px;
      box-shadow: 0 4px 24px rgba(0,0,0,.12);
    }
    .logo {
      text-align: center;
      margin-bottom: 24px;
    }
    .logo-title {
      font-size: 22px;
      font-weight: 800;
      color: #1a1a2e;
    }
    .logo-sub {
      font-size: 13px;
      color: #777;
      margin-top: 4px;
    }
    .form-group { margin-bottom: 16px; }
    label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #444;
      margin-bottom: 6px;
    }
    input[type="text"], input[type="password"] {
      width: 100%;
      border: 1.5px solid #ddd;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 15px;
      font-family: inherit;
      transition: border-color .15s;
      outline: none;
    }
    input:focus { border-color: #1a1a2e; }
    .error-msg {
      color: #e53935;
      font-size: 13px;
      margin-top: 6px;
    }
    .btn-login {
      width: 100%;
      padding: 12px;
      background: #1a1a2e;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      margin-top: 8px;
      transition: opacity .15s;
    }
    .btn-login:hover { opacity: .85; }
  </style>
</head>
<body>
  <div class="card">
    <div class="logo">
      <div class="logo-title">🎭 ลิเกไลฟ์อินเดอะเธียเตอร์</div>
      <div class="logo-sub">ระบบจองที่นั่ง</div>
    </div>

    <form method="POST" action="{{ route('likay.login.post') }}">
      @csrf
      <div class="form-group">
        <label>ชื่อผู้ใช้</label>
        <input type="text" name="username" value="{{ old('username') }}" autocomplete="username" autofocus>
        @error('username')
          <div class="error-msg">{{ $message }}</div>
        @enderror
      </div>
      <div class="form-group">
        <label>รหัสผ่าน</label>
        <input type="password" name="password" autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login">เข้าสู่ระบบ</button>
    </form>
  </div>
</body>
</html>
