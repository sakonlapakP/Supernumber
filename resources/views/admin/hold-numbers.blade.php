@extends('layouts.admin')

@section('title', 'Supernumber Admin | เบอร์ที่พักไว้')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>เบอร์ที่พักไว้</h1>
      <p class="admin-subtitle">แสดงเบอร์ทั้งหมดที่อยู่ในสถานะ hold และสามารถเปลี่ยนกลับเป็น active ได้</p>
    </div>
    <div class="admin-page-actions">
      <div class="admin-kpi">
        <div class="admin-kpi__label">เบอร์ที่พักไว้ที่แสดงอยู่</div>
        <div class="admin-kpi__value"><span id="hold-visible-count">{{ number_format($numbers->count()) }}</span></div>
        <div class="admin-summary">จาก {{ number_format($numbers->count()) }} เบอร์</div>
      </div>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <div class="admin-panel-stack">
    <section
      id="hold-add-panel"
      class="admin-card admin-feature-card"
      @if (! old('phone_number') && ! session('error_message')) hidden @endif
    >
      <div class="admin-feature-card__head">
        <div>
          <h2 class="admin-feature-card__title">เพิ่มเบอร์เข้าคิวพัก</h2>
          <p class="admin-feature-card__hint">กรอกเบอร์ 10 หลัก ระบบจะเปลี่ยนเป็น hold ทันที ถ้าผ่านเงื่อนไข</p>
        </div>
      </div>

      <form action="{{ route('admin.hold-numbers.add') }}" method="post" class="admin-form admin-form--inline">
        @csrf
        <div class="admin-field">
          <label for="hold-lookup-number">หมายเลขโทรศัพท์</label>
          <input
            id="hold-lookup-number"
            class="admin-input"
            type="text"
            name="phone_number"
            inputmode="numeric"
            pattern="[0-9]*"
            maxlength="10"
            value="{{ old('phone_number') }}"
            placeholder="กรอกเบอร์ 10 หลัก"
            required
          />
        </div>
        <button type="submit" class="admin-button admin-button--compact">พักเบอร์ทันที</button>
      </form>

      @if (session('error_message'))
        <div class="admin-alert admin-alert--error" style="margin-top: 14px;">{{ session('error_message') }}</div>
      @endif
    </section>

    <section class="admin-card admin-feature-card admin-feature-card--compact">
      <div class="admin-feature-card__head">
        <div>
          <h2 class="admin-feature-card__title">ค้นหาแบบทันที</h2>
          <p class="admin-feature-card__hint">จำนวนเบอร์ที่พักไว้ทั้งหมด {{ number_format($numbers->count()) }} เบอร์</p>
        </div>
        <div class="admin-feature-card__actions">
          <button type="button" id="hold-add-toggle" class="admin-button admin-button--compact">เพิ่ม</button>
        </div>
      </div>

      <div class="admin-search-shell">
        <div class="admin-field">
          <label for="hold-number-search">ค้นหา</label>
          <input
            id="hold-number-search"
            class="admin-input"
            type="text"
            inputmode="numeric"
            pattern="[0-9]*"
            placeholder="ค้นหาเลขเบอร์ เช่น 629"
          />
        </div>
      </div>
    </section>
  </div>

  <section class="admin-card admin-table-card">
    <div class="admin-feature-card__head" style="padding: 18px 20px 0;">
      <div>
        <h2 class="admin-feature-card__title">รายการเบอร์ที่พักไว้ตอนนี้</h2>
        <p class="admin-feature-card__hint">รายการเบอร์ที่ถูกพักไว้และสามารถเปลี่ยนกลับเป็น active ได้ทันที</p>
      </div>
    </div>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>เบอร์</th>
            <th>ผลรวม</th>
            <th>ประเภท</th>
            <th>เครือข่าย</th>
            <th>ราคา / แพ็กเกจ</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @if ($numbers->isNotEmpty())
            @foreach ($numbers as $number)
              <tr class="hold-number-row" data-phone-number="{{ preg_replace('/\D/', '', $number->phone_number) }}">
                <td>
                  <div class="admin-number">{{ $number->display_number ?: $number->phone_number }}</div>
                </td>
                <td>{{ $number->number_sum ?: '-' }}</td>
                <td>{{ $number->service_type_label }}</td>
                <td>{{ strtoupper(str_replace('_', '-', $number->network_code)) }}</td>
                <td>{{ $number->payment_label }}</td>
                <td><span class="admin-status-pill admin-status-pill--hold">{{ $number->status ?: '-' }}</span></td>
                <td class="admin-action-cell">
                  <div class="admin-action-group">
                    <a href="{{ route('admin.numbers.edit', $number) }}" class="admin-button admin-button--secondary admin-button--compact">แก้ไข</a>
                    <form action="{{ route('admin.hold-numbers.activate', $number) }}" method="post">
                      @csrf
                      <button type="submit" class="admin-button admin-button--secondary admin-button--compact">เปิดใช้งาน</button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
            <tr id="hold-numbers-empty-row" hidden>
              <td colspan="7" class="admin-muted">ไม่พบเบอร์ที่ตรงกับคำค้นหา</td>
            </tr>
          @else
            <tr>
              <td colspan="7" class="admin-muted">ยังไม่มีเบอร์ที่อยู่ในสถานะ hold</td>
            </tr>
          @endif
        </tbody>
      </table>
    </div>
  </section>

  <script>
    (() => {
      const addToggle = document.getElementById("hold-add-toggle");
      const addPanel = document.getElementById("hold-add-panel");
      const lookupInput = document.getElementById("hold-lookup-number");
      const input = document.getElementById("hold-number-search");
      const rows = Array.from(document.querySelectorAll(".hold-number-row"));
      const emptyRow = document.getElementById("hold-numbers-empty-row");
      const visibleCount = document.getElementById("hold-visible-count");

      if (addToggle && addPanel) {
        addToggle.addEventListener("click", () => {
          const isHidden = addPanel.hidden;
          addPanel.hidden = !isHidden;

          if (isHidden && lookupInput) {
            lookupInput.focus();
          }
        });
      }

      if (lookupInput) {
        lookupInput.addEventListener("input", () => {
          lookupInput.value = lookupInput.value.replace(/\D/g, "").slice(0, 10);
        });
      }

      if (!input || rows.length === 0 || !visibleCount) return;

      input.addEventListener("input", () => {
        const digits = input.value.replace(/\D/g, "");
        input.value = digits;
        let matched = 0;

        rows.forEach((row) => {
          const number = row.dataset.phoneNumber || "";
          const isVisible = digits === "" || number.includes(digits);

          row.hidden = !isVisible;

          if (isVisible) {
            matched += 1;
          }
        });

        if (emptyRow) {
          emptyRow.hidden = matched !== 0;
        }

        visibleCount.textContent = matched.toLocaleString("en-US");
      });
    })();
  </script>
@endsection
