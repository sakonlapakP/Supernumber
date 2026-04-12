@extends('layouts.admin')

@section('title', 'Supernumber Admin | ลูกค้า')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>ลูกค้า</h1>
      <p class="admin-subtitle">ตั้งค่าข้อมูลลูกค้าแยกไว้ก่อน เพื่อให้หน้าออกเอกสารเลือกชื่อแล้วเติมข้อมูลอัตโนมัติได้ทันที</p>
    </div>
    <div class="admin-page-actions">
      <div class="admin-summary">ทั้งหมด {{ number_format($customers->count()) }} ราย</div>
      <button type="button" id="customers-add-toggle" class="admin-button admin-button--compact">เพิ่มลูกค้า</button>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success" style="margin-bottom: 18px;">{{ session('status_message') }}</div>
  @endif

  @if ($errors->any())
    <div class="admin-alert admin-alert--error" style="margin-bottom: 18px;">{{ $errors->first() }}</div>
  @endif

  <section
    id="customers-add-panel"
    class="admin-card admin-feature-card"
    style="margin-bottom: 18px;"
    @if (! $errors->any()) hidden @endif
  >
    <h2 class="admin-feature-card__title" style="margin-bottom: 14px; font-size: 24px;">สร้างลูกค้า</h2>
    <form class="admin-form" action="{{ route('admin.customers.store') }}" method="post">
      @csrf
      <div class="admin-field">
        <label for="customer-company-name">ชื่อบริษัท</label>
        <input id="customer-company-name" class="admin-input" type="text" name="company_name" value="{{ old('company_name') }}" />
      </div>
      <div class="admin-field">
        <label for="customer-first-name">ชื่อ</label>
        <input id="customer-first-name" class="admin-input" type="text" name="first_name" value="{{ old('first_name') }}" />
      </div>
      <div class="admin-field">
        <label for="customer-last-name">นามสกุล</label>
        <input id="customer-last-name" class="admin-input" type="text" name="last_name" value="{{ old('last_name') }}" />
      </div>
      <div class="admin-field">
        <label for="customer-tax-id">เลขประจำตัวผู้เสียภาษี</label>
        <input id="customer-tax-id" class="admin-input" type="text" name="tax_id" value="{{ old('tax_id') }}" />
      </div>
      <div class="admin-field">
        <label for="customer-email">อีเมล</label>
        <input id="customer-email" class="admin-input" type="email" name="email" value="{{ old('email') }}" />
      </div>
      <div class="admin-field">
        <label for="customer-phone">โทรศัพท์</label>
        <input id="customer-phone" class="admin-input" type="text" name="phone" value="{{ old('phone') }}" />
      </div>
      <div class="admin-field">
        <label for="customer-payment-term">เงื่อนไขการชำระเงิน</label>
        <input id="customer-payment-term" class="admin-input" type="text" name="payment_term" value="{{ old('payment_term', 'ชำระภายใน 7 วัน') }}" />
      </div>
      <div class="admin-field">
        <label for="customer-address">ที่อยู่</label>
        <textarea id="customer-address" class="admin-input" name="address" style="min-height: 110px; padding-top: 12px;">{{ old('address') }}</textarea>
      </div>
      <div class="admin-field">
        <label for="customer-notes">หมายเหตุ</label>
        <textarea id="customer-notes" class="admin-input" name="notes" style="min-height: 110px; padding-top: 12px;">{{ old('notes') }}</textarea>
      </div>
      <label class="admin-field" style="grid-template-columns: auto 1fr; align-items: center; gap: 10px; display: grid;">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1') />
        <span>เปิดใช้งานลูกค้ารายนี้ในหน้าออกเอกสาร</span>
      </label>
      <button type="submit" class="admin-button">บันทึกข้อมูลลูกค้า</button>
    </form>
  </section>

  <section class="admin-card admin-table-card">
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ชื่อบริษัท / ลูกค้า</th>
            <th>เลขภาษี</th>
            <th>ผู้ติดต่อ</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($customers as $customer)
            <tr>
              <td>
                <strong>{{ $customer->display_name !== '' ? $customer->display_name : '-' }}</strong>
                @if ($customer->address)
                  <div class="admin-muted" style="margin-top: 6px; white-space: pre-line;">{{ $customer->address }}</div>
                @endif
              </td>
              <td>{{ $customer->tax_id ?: '-' }}</td>
              <td>
                {{ $customer->contact_name !== '' ? $customer->contact_name : '-' }}
                @if ($customer->email || $customer->phone)
                  <div class="admin-muted" style="margin-top: 6px;">
                    {{ $customer->email ?: '-' }}{{ $customer->email && $customer->phone ? ' | ' : '' }}{{ $customer->phone ?: '' }}
                  </div>
                @endif
              </td>
              <td>{{ $customer->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</td>
              <td>
                <details>
                  <summary class="admin-button admin-button--muted admin-button--compact" style="cursor: pointer; list-style: none;">แก้ไข</summary>
                  <form class="admin-form" action="{{ route('admin.customers.update', $customer) }}" method="post" style="margin-top: 12px; min-width: 360px;">
                    @csrf
                    @method('PUT')
                    <div class="admin-field">
                      <label>ชื่อบริษัท</label>
                      <input class="admin-input" type="text" name="company_name" value="{{ $customer->company_name }}" />
                    </div>
                    <div class="admin-field">
                      <label>ชื่อ</label>
                      <input class="admin-input" type="text" name="first_name" value="{{ $customer->first_name }}" />
                    </div>
                    <div class="admin-field">
                      <label>นามสกุล</label>
                      <input class="admin-input" type="text" name="last_name" value="{{ $customer->last_name }}" />
                    </div>
                    <div class="admin-field">
                      <label>เลขภาษี</label>
                      <input class="admin-input" type="text" name="tax_id" value="{{ $customer->tax_id }}" />
                    </div>
                    <div class="admin-field">
                      <label>อีเมล</label>
                      <input class="admin-input" type="email" name="email" value="{{ $customer->email }}" />
                    </div>
                    <div class="admin-field">
                      <label>โทรศัพท์</label>
                      <input class="admin-input" type="text" name="phone" value="{{ $customer->phone }}" />
                    </div>
                    <div class="admin-field">
                      <label>เงื่อนไขการชำระเงิน</label>
                      <input class="admin-input" type="text" name="payment_term" value="{{ $customer->payment_term }}" />
                    </div>
                    <div class="admin-field">
                      <label>ที่อยู่</label>
                      <textarea class="admin-input" name="address" style="min-height: 110px; padding-top: 12px;">{{ $customer->address }}</textarea>
                    </div>
                    <div class="admin-field">
                      <label>หมายเหตุ</label>
                      <textarea class="admin-input" name="notes" style="min-height: 110px; padding-top: 12px;">{{ $customer->notes }}</textarea>
                    </div>
                    <label class="admin-field" style="grid-template-columns: auto 1fr; align-items: center; gap: 10px; display: grid;">
                      <input type="checkbox" name="is_active" value="1" @checked($customer->is_active) />
                      <span>เปิดใช้งาน</span>
                    </label>
                    <button type="submit" class="admin-button admin-button--compact">บันทึก</button>
                  </form>
                </details>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="admin-muted">ยังไม่มีข้อมูลลูกค้า</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <script>
    (() => {
      const addToggle = document.getElementById("customers-add-toggle");
      const addPanel = document.getElementById("customers-add-panel");
      const firstInput = document.getElementById("customer-company-name");

      if (!addToggle || !addPanel) return;

      addToggle.addEventListener("click", () => {
        const isHidden = addPanel.hidden;
        addPanel.hidden = !isHidden;

        if (isHidden && firstInput) {
          firstInput.focus();
        }
      });
    })();
  </script>
@endsection
