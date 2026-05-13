@extends('layouts.admin')

@section('title', 'Supernumber Admin | แก้ไขแพ็กเกจรายเดือน')

@section('content')
  @php
    $isEditing = $package->exists;
  @endphp

  <div class="admin-page-head">
    <div>
      <h1>{{ $isEditing ? 'แก้ไขแพ็กเกจรายเดือน' : 'เพิ่มแพ็กเกจรายเดือน' }}</h1>
      <p class="admin-subtitle">รหัสแพ็กเกจต้องตรงกับคอลัมน์แพ็กเกจใน CSV รายเดือน</p>
    </div>
    <div class="admin-page-actions">
      <a href="{{ route('admin.phone-packages') }}" class="admin-button admin-button--secondary admin-button--compact">กลับ</a>
    </div>
  </div>

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
  @endif

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <section class="admin-card admin-feature-card">
    <form class="admin-form" action="{{ $action }}" method="post">
      @csrf
      @if ($method === 'put')
        @method('put')
      @endif

      <div class="admin-field">
        <label for="code">รหัสแพ็กเกจ</label>
        <input id="code" class="admin-input" type="text" name="code" value="{{ old('code', $package->code) }}" placeholder="TRUE-SV-1499" required />
      </div>

      <div class="admin-field">
        <label for="service_type">ประเภท</label>
        <select id="service_type" class="admin-input" name="service_type" required>
          <option value="{{ \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID }}" @selected(old('service_type', $package->service_type ?: \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID) === \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID)>รายเดือน</option>
        </select>
      </div>

      <div class="admin-field">
        <label for="network_code">เครือข่าย</label>
        <select id="network_code" class="admin-input" name="network_code" required>
          @foreach (\App\Models\PhoneNumber::supportedNetworkCodes() as $networkCode)
            <option value="{{ $networkCode }}" @selected(old('network_code', $package->network_code ?: \App\Models\PhoneNumber::NETWORK_TRUE) === $networkCode)>
              {{ \App\Models\PhoneNumber::networkLabel($networkCode) }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="admin-field">
        <label for="name">ชื่อแพ็กเกจ</label>
        <input id="name" class="admin-input" type="text" name="name" value="{{ old('name', $package->name) }}" placeholder="True Super Value 1499" required />
      </div>

      <div class="admin-field">
        <label for="monthly_price">ค่ารายเดือน</label>
        <input id="monthly_price" class="admin-input" type="number" min="1" name="monthly_price" value="{{ old('monthly_price', $package->monthly_price) }}" required />
      </div>

      <div class="admin-field">
        <label for="data_quota">Data</label>
        <input id="data_quota" class="admin-input" type="text" name="data_quota" value="{{ old('data_quota', $package->data_quota) }}" placeholder="Unlimited" />
      </div>

      <div class="admin-field">
        <label for="speed_after_quota">ความเร็วหลังเน็ตเต็มสปีด</label>
        <input id="speed_after_quota" class="admin-input" type="text" name="speed_after_quota" value="{{ old('speed_after_quota', $package->speed_after_quota) }}" placeholder="ไม่จำกัด" />
      </div>

      <div class="admin-field">
        <label for="voice_minutes">โทรทุกเครือข่าย</label>
        <input id="voice_minutes" class="admin-input" type="text" name="voice_minutes" value="{{ old('voice_minutes', $package->voice_minutes) }}" placeholder="900 นาที" />
      </div>

      <div class="admin-field">
        <label for="benefits">สิทธิพิเศษ</label>
        <textarea id="benefits" class="admin-input" name="benefits" rows="3">{{ old('benefits', $package->benefits) }}</textarea>
      </div>

      <div class="admin-field">
        <label for="conditions">เงื่อนไขแพ็กเกจ</label>
        <textarea id="conditions" class="admin-input" name="conditions" rows="5">{{ old('conditions', $package->conditions) }}</textarea>
      </div>

      <label style="display: flex; align-items: center; gap: 10px;">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->exists ? $package->is_active : true)) />
        เปิดใช้งานแพ็กเกจนี้
      </label>

      <button type="submit" class="admin-button">{{ $isEditing ? 'บันทึกแพ็กเกจ' : 'สร้างแพ็กเกจ' }}</button>
    </form>
  </section>
@endsection
