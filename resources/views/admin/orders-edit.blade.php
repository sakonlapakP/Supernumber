@extends('layouts.admin')

@section('title', 'Supernumber Admin | Edit Order')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>Edit Order</h1>
      <p class="admin-subtitle">แก้ไขข้อมูลคำสั่งซื้อ</p>
    </div>
    <div class="admin-page-actions">
      <a href="{{ route('admin.orders.show', $order) }}" class="admin-button admin-button--secondary admin-button--compact">ดูรายละเอียด</a>
      <a href="{{ route('admin.orders') }}" class="admin-button admin-button--secondary admin-button--compact">กลับหน้า Orders</a>
    </div>
  </div>

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
  @endif

  <section class="admin-card admin-feature-card">
    <form class="admin-form" action="{{ route('admin.orders.update', $order) }}" method="post" enctype="multipart/form-data">
      @csrf
      @method('put')

      <div class="admin-field">
        <label for="ordered_number">เบอร์ที่สั่งซื้อ</label>
        <input id="ordered_number" class="admin-input" type="text" name="ordered_number" value="{{ old('ordered_number', $order->ordered_number) }}" required />
      </div>

      <div class="admin-field">
        <label for="selected_package">แพคเกจ (บาท/เดือน)</label>
        <input id="selected_package" class="admin-input" type="number" min="1" name="selected_package" value="{{ old('selected_package', (int) $order->selected_package) }}" required />
      </div>

      <div class="admin-field">
        <label for="title_prefix">คำนำหน้า</label>
        <input id="title_prefix" class="admin-input" type="text" name="title_prefix" value="{{ old('title_prefix', $order->title_prefix) }}" />
      </div>

      <div class="admin-field">
        <label for="first_name">ชื่อ</label>
        <input id="first_name" class="admin-input" type="text" name="first_name" value="{{ old('first_name', $order->first_name) }}" />
      </div>

      <div class="admin-field">
        <label for="last_name">นามสกุล</label>
        <input id="last_name" class="admin-input" type="text" name="last_name" value="{{ old('last_name', $order->last_name) }}" />
      </div>

      <div class="admin-field">
        <label for="current_phone">โทรศัพท์</label>
        <input id="current_phone" class="admin-input" type="text" name="current_phone" value="{{ old('current_phone', $order->current_phone) }}" />
      </div>

      <div class="admin-field">
        <label for="email">อีเมล</label>
        <input id="email" class="admin-input" type="email" name="email" value="{{ old('email', $order->email) }}" />
      </div>

      <div class="admin-field">
        <label for="shipping_address_line">ที่อยู่</label>
        <input id="shipping_address_line" class="admin-input" type="text" name="shipping_address_line" value="{{ old('shipping_address_line', $order->shipping_address_line) }}" />
      </div>

      <div class="admin-field">
        <label for="district">ตำบล/แขวง</label>
        <input id="district" class="admin-input" type="text" name="district" value="{{ old('district', $order->district) }}" />
      </div>

      <div class="admin-field">
        <label for="amphoe">อำเภอ/เขต</label>
        <input id="amphoe" class="admin-input" type="text" name="amphoe" value="{{ old('amphoe', $order->amphoe) }}" />
      </div>

      <div class="admin-field">
        <label for="province">จังหวัด</label>
        <input id="province" class="admin-input" type="text" name="province" value="{{ old('province', $order->province) }}" />
      </div>

      <div class="admin-field">
        <label for="zipcode">รหัสไปรษณีย์</label>
        <input id="zipcode" class="admin-input" type="text" name="zipcode" value="{{ old('zipcode', $order->zipcode) }}" />
      </div>

      <div class="admin-field">
        <label for="appointment_date">วันนัดหมาย</label>
        <input id="appointment_date" class="admin-input" type="date" name="appointment_date" value="{{ old('appointment_date', optional($order->appointment_date)->format('Y-m-d')) }}" />
      </div>

      <div class="admin-field">
        <label for="appointment_time_slot">ช่วงเวลานัดหมาย</label>
        <input id="appointment_time_slot" class="admin-input" type="text" name="appointment_time_slot" value="{{ old('appointment_time_slot', $order->appointment_time_slot) }}" />
      </div>

      <div class="admin-field">
        <label for="status">สถานะ</label>
        <input id="status" class="admin-input" type="text" name="status" value="{{ old('status', $order->status) }}" required />
      </div>

      <div class="admin-field">
        <label for="payment_slip">เปลี่ยนหลักฐานการโอน (ถ้าต้องการ)</label>
        <input id="payment_slip" class="admin-input" type="file" name="payment_slip" accept="image/*,application/pdf,.pdf" />
      </div>

      <button type="submit" class="admin-button">บันทึกการแก้ไข</button>
    </form>
  </section>
@endsection
