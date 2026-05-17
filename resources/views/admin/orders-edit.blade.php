@extends('layouts.admin')

@section('title', 'Supernumber Admin | แก้ไขคำสั่งซื้อ')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>แก้ไขคำสั่งซื้อ</h1>
      <p class="admin-subtitle">แก้ไขข้อมูลคำสั่งซื้อ</p>
    </div>
    <div class="admin-page-actions">
      <a href="{{ route('admin.orders.show', $order) }}" class="admin-button admin-button--secondary admin-button--compact">ดูรายละเอียด</a>
      <a href="{{ route('admin.orders') }}" class="admin-button admin-button--secondary admin-button--compact">กลับหน้าคำสั่งซื้อ</a>
      @if (session('admin_user_role') === 'manager')
        <button type="button" class="admin-button admin-button--danger admin-button--compact" onclick="openDeleteModal()">ลบคำสั่งซื้อ</button>
      @endif
    </div>
  </div>

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
  @endif

  <style>
    .admin-form--inline-fields .admin-field {
      grid-template-columns: 200px 1fr;
      align-items: center;
      gap: 12px;
      border-bottom: 1px solid var(--admin-border);
      padding-bottom: 4px;
      margin-bottom: 4px;
    }
    .admin-form--inline-fields .admin-field label {
      margin: 0;
      color: var(--admin-muted);
      font-weight: 600;
      font-size: 12px;
    }
    .admin-form--inline-fields .admin-input,
    .admin-form--inline-fields .admin-select {
      border: none;
      padding: 4px 0;
      background: transparent;
      box-shadow: none;
      min-height: 28px;
      font-weight: 500;
      font-size: 14px;
    }
    .admin-form--inline-fields .admin-input:focus,
    .admin-form--inline-fields .admin-select:focus {
      box-shadow: none;
      border-bottom: 1px solid var(--admin-primary);
      border-radius: 0;
    }
    .admin-form--inline-fields .admin-input[readonly] {
      color: var(--admin-text);
      cursor: not-allowed;
      opacity: 0.8;
    }
  </style>

  <section class="admin-card admin-feature-card">
    <form class="admin-form admin-form--inline-fields" action="{{ route('admin.orders.update', $order) }}" method="post" enctype="multipart/form-data">
      @csrf
      @method('put')

      <div class="admin-field">
        <label for="ordered_number">เบอร์ที่สั่งซื้อ</label>
        <input id="ordered_number" class="admin-input" type="text" name="ordered_number" value="{{ old('ordered_number', $order->ordered_number) }}" readonly required />
      </div>

      <div class="admin-field">
        <label for="initial_payment_price">ยอดชำระแรก (บาท)</label>
        <input id="initial_payment_price" class="admin-input" type="number" min="1" name="initial_payment_price" value="{{ old('initial_payment_price', $order->initial_payment_amount) }}" readonly required />
      </div>

      @if ($order->is_postpaid)
        <div class="admin-field" style="display: none;">
          <label for="package_code">รหัสแพ็กเกจ</label>
          <input id="package_code" class="admin-input" type="text" name="package_code" value="{{ old('package_code', $order->package_code ?: $order->package?->code) }}" />
        </div>

        <div class="admin-field">
          <label for="package_name">ชื่อแพ็กเกจ</label>
          <input id="package_name" class="admin-input" type="text" name="package_name" value="{{ old('package_name', $order->package_name ?: $order->package?->name) }}" />
        </div>

        <div class="admin-field" style="display: none;">
          <label for="monthly_price">ค่าบริการรายเดือน (บาท)</label>
          <input id="monthly_price" class="admin-input" type="number" name="monthly_price" value="{{ old('monthly_price', $order->monthly_price) }}" readonly />
        </div>
      @endif

      <div class="admin-field">
        <label for="title_prefix">คำนำหน้า</label>
        <select id="title_prefix" class="admin-select" name="title_prefix">
          <option value="">- ไม่ระบุ -</option>
          @foreach(['นาย', 'นางสาว'] as $prefix)
            <option value="{{ $prefix }}" {{ old('title_prefix', $order->title_prefix) === $prefix ? 'selected' : '' }}>{{ $prefix }}</option>
          @endforeach
        </select>
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
        <select id="status" class="admin-select" name="status" required>
          @foreach (App\Models\CustomerOrder::statusOptions() as $option)
            <option value="{{ $option }}" {{ old('status', $order->status) === $option ? 'selected' : '' }}>
              {{ App\Models\CustomerOrder::statusLabelOptions()[$option] ?? $option }}
            </option>
          @endforeach
        </select>
        <p class="admin-muted" style="margin: 8px 0 0; font-size: 0.9rem; grid-column: 2;">เบอร์จะถูกล็อคเป็น 'จอง' ระหว่างดำเนินการ และจะเปลี่ยนเป็น 'ขายแล้ว' เมื่อสถานะเป็น 'สำเร็จ'</p>
      </div>

      <div class="admin-field">
        <label for="payment_slip">หลักฐานการโอน</label>
        <div style="display: grid; gap: 8px;">
          @php
            $slipPath = $order->payment_slip_path;
            $slipUrl = $slipPath;
            if ($slipPath && !Str::startsWith($slipPath, ['http://', 'https://'])) {
                $slipUrl = asset('storage/' . $slipPath);
            }
          @endphp
          @if ($slipUrl)
            <div style="font-size: 0.8rem; color: var(--admin-muted);">รูปปัจจุบัน:</div>
            <a href="{{ $slipUrl }}" target="_blank">
              <img src="{{ $slipUrl }}" alt="หลักฐานการโอน" style="max-width: 180px; border-radius: 8px; border: 1px solid var(--admin-border);" />
            </a>
          @endif
          <div style="font-size: 0.8rem; color: var(--admin-muted); margin-top: 4px;">เปลี่ยนหลักฐานการโอน (ถ้าต้องการ):</div>
          <input id="payment_slip" class="admin-input" type="file" name="payment_slip" accept="image/*,application/pdf,.pdf" />
        </div>
      </div>

      <button type="submit" class="admin-button">บันทึกการแก้ไข</button>
    </form>
  </section>

  @if (session('admin_user_role') === 'manager')
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;">
      <div class="admin-card" style="width: 90%; max-width: 500px;">
        <h2 style="margin-top: 0; color: #d32f2f;">ยืนยันการลบคำสั่งซื้อ</h2>
        <p style="color: var(--admin-text);">คำสั่งซื้อ <strong>{{ $order->ordered_number }}</strong> จะถูกลบออกจากระบบ</p>
        <p style="color: var(--admin-muted); font-size: 0.9rem;">กรุณาพิมพ์คำว่า <strong>DELETE</strong> เพื่อยืนยันการลบ:</p>
        <input id="deleteConfirmInput" type="text" class="admin-input" placeholder="พิมพ์ DELETE" style="margin-bottom: 16px;" />
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
          <button type="button" class="admin-button admin-button--secondary" onclick="closeDeleteModal()">ยกเลิก</button>
          <button id="confirmDeleteBtn" type="button" class="admin-button admin-button--danger" onclick="confirmDelete()" disabled>ลบ</button>
        </div>
      </div>
    </div>

    <script>
      function openDeleteModal() {
        document.getElementById('deleteModal').style.display = 'flex';
        document.getElementById('deleteConfirmInput').value = '';
        document.getElementById('confirmDeleteBtn').disabled = true;
        document.getElementById('deleteConfirmInput').focus();
      }

      function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
      }

      document.getElementById('deleteConfirmInput').addEventListener('input', function() {
        document.getElementById('confirmDeleteBtn').disabled = this.value !== 'DELETE';
      });

      function confirmDelete() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('admin.orders.destroy', $order) }}';
        form.innerHTML = `
          @csrf
          @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
      }

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('deleteModal').style.display === 'flex') {
          closeDeleteModal();
        }
      });
    </script>
  @endif
@endsection
