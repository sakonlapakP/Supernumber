@extends('layouts.sale')
@section('title', 'Supernumber Sale | สมัครเป็นเซลล์')

@section('content')
<div style="max-width:600px;margin:2rem auto;">
  <div class="sale-card">
    <div style="text-align:center;margin-bottom:1.5rem;">
      <div style="font-size:1.5rem;font-weight:700;color:#1a1a2e;">สมัครเป็นเซลล์</div>
      <div style="color:#888;font-size:.875rem;margin-top:.25rem;">กรอกข้อมูลให้ครบถ้วนเพื่อรอการอนุมัติ</div>
    </div>

    @if($errors->any())
      <div class="alert alert-error">
        <ul style="margin:0;padding-left:1.25rem;">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('sale.register.submit') }}" enctype="multipart/form-data">
      @csrf

      <div class="sale-card__title">ข้อมูลส่วนตัว</div>

      <div class="form-group">
        <label>ชื่อ-นามสกุล <span style="color:#e53e3e">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
          value="{{ old('name') }}" required />
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group">
          <label>อีเมล <span style="color:#e53e3e">*</span></label>
          <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email') }}" required />
          @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label>เบอร์โทรศัพท์ <span style="color:#e53e3e">*</span></label>
          <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror"
            value="{{ old('phone') }}" required />
          @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="form-group">
        <label>เลขบัตรประชาชน (13 หลัก) <span style="color:#e53e3e">*</span></label>
        <input type="text" name="national_id" class="form-control @error('national_id') is-invalid @enderror"
          value="{{ old('national_id') }}" maxlength="13" required
          placeholder="x-xxxx-xxxxx-xx-x" />
        @error('national_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group">
          <label>รหัสผ่าน <span style="color:#e53e3e">*</span></label>
          <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
            required autocomplete="new-password" />
          @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label>ยืนยันรหัสผ่าน <span style="color:#e53e3e">*</span></label>
          <input type="password" name="password_confirmation" class="form-control" required />
        </div>
      </div>

      <div class="sale-card__title" style="margin-top:1rem;">ข้อมูลบัญชีธนาคาร</div>
      <p style="font-size:.8rem;color:#888;margin-bottom:.75rem;">ชื่อบัญชีต้องตรงกับชื่อ-นามสกุลที่สมัคร</p>

      <div class="form-group">
        <label>ธนาคาร <span style="color:#e53e3e">*</span></label>
        <select name="bank_name" class="form-control @error('bank_name') is-invalid @enderror" required>
          <option value="">-- เลือกธนาคาร --</option>
          @foreach(['กสิกรไทย','กรุงเทพ','กรุงไทย','ไทยพาณิชย์','กรุงศรีอยุธยา','ทหารไทยธนชาต','ออมสิน','ธ.ก.ส.','ซีไอเอ็มบี','ยูโอบี','อาคารสงเคราะห์'] as $bank)
            <option value="{{ $bank }}" @selected(old('bank_name') === $bank)>{{ $bank }}</option>
          @endforeach
        </select>
        @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group">
          <label>เลขบัญชี <span style="color:#e53e3e">*</span></label>
          <input type="text" name="bank_account_number"
            class="form-control @error('bank_account_number') is-invalid @enderror"
            value="{{ old('bank_account_number') }}" required />
          @error('bank_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label>ชื่อบัญชี <span style="color:#e53e3e">*</span></label>
          <input type="text" name="bank_account_name"
            class="form-control @error('bank_account_name') is-invalid @enderror"
            value="{{ old('bank_account_name') }}" required />
          @error('bank_account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="sale-card__title" style="margin-top:1rem;">เอกสาร KYC</div>
      <p style="font-size:.8rem;color:#888;margin-bottom:.75rem;">ชื่อในเอกสารต้องตรงกับชื่อที่สมัคร · ไฟล์ JPG/PNG/PDF ไม่เกิน 5MB</p>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div class="form-group">
          <label>สำเนาบัตรประชาชน <span style="color:#e53e3e">*</span></label>
          <input type="file" name="id_card_file" class="form-control @error('id_card_file') is-invalid @enderror"
            accept=".jpg,.jpeg,.png,.pdf" required />
          @error('id_card_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label>สมุดบัญชีธนาคาร <span style="color:#e53e3e">*</span></label>
          <input type="file" name="bank_book_file" class="form-control @error('bank_book_file') is-invalid @enderror"
            accept=".jpg,.jpeg,.png,.pdf" required />
          @error('bank_book_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="form-group" style="margin-top:.5rem;">
        <label>รหัสผู้แนะนำ (ถ้ามี)</label>
        <input type="text" name="ref_code" class="form-control @error('ref_code') is-invalid @enderror"
          value="{{ old('ref_code', $referrerCode ?? '') }}" maxlength="10"
          style="text-transform:uppercase;" />
        @error('ref_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top:1rem;">
        ส่งใบสมัคร
      </button>
    </form>

    <div style="text-align:center;margin-top:1.25rem;font-size:.875rem;color:#888;">
      มีบัญชีแล้ว?
      <a href="{{ route('sale.login') }}" style="color:#1a1a2e;font-weight:600;">เข้าสู่ระบบ</a>
    </div>
  </div>
</div>
@endsection
