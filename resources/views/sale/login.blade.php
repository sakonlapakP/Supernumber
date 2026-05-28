@extends('layouts.sale')
@section('title', 'Supernumber Sale | เข้าสู่ระบบ')

@section('content')
<div style="max-width:420px;margin:3rem auto;">
  <div class="sale-card">
    <div style="text-align:center;margin-bottom:1.5rem;">
      <div style="font-size:1.8rem;font-weight:700;color:#1a1a2e;">Sale Portal</div>
      <div style="color:#888;font-size:.9rem;margin-top:.25rem;">เข้าสู่ระบบเซลล์</div>
    </div>

    @if($errors->any())
      <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('sale.login.attempt') }}">
      @csrf
      <div class="form-group">
        <label>อีเมล</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
          value="{{ old('email') }}" required autocomplete="email" />
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group">
        <label>รหัสผ่าน</label>
        <input type="password" name="password" class="form-control" required autocomplete="current-password" />
      </div>
      <button type="submit" class="btn btn-primary btn-block" style="margin-top:.5rem;">เข้าสู่ระบบ</button>
    </form>

    <div style="text-align:center;margin-top:1.25rem;font-size:.875rem;color:#888;">
      ยังไม่มีบัญชี?
      <a href="{{ route('sale.register') }}" style="color:#1a1a2e;font-weight:600;">สมัครเป็นเซลล์</a>
    </div>
  </div>
</div>
@endsection
