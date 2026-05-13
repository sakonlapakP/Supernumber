@extends('layouts.admin')

@section('title', 'Supernumber Admin | นำเข้าข้อมูลเบอร์ (CSV)')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>นำเข้าข้อมูลเบอร์ (CSV)</h1>
      <p class="admin-subtitle">อัพเดทเบอร์โทรศัพท์ในระบบทั้งหมดด้วยไฟล์ CSV</p>
    </div>
    <div class="admin-page-actions">
      <a href="{{ route('admin.import-numbers.sample') }}" class="admin-button admin-button--secondary">
        <span style="display: flex; align-items: center; gap: 8px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
          ดาวน์โหลดไฟล์ตัวอย่าง
        </span>
      </a>
    </div>
  </div>

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">
      <ul style="margin: 0; padding-left: 20px;">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <div class="admin-panel-stack">
    <section class="admin-card admin-feature-card">
      <div class="admin-feature-card__head">
        <div>
          <h2 class="admin-feature-card__title">อัพโหลดไฟล์ CSV</h2>
          <p class="admin-feature-card__hint">
            คำเตือน: การกดปุ่ม Start จะลบข้อมูลเบอร์โทรศัพท์เดิมในระบบทั้งหมดและแทนที่ด้วยข้อมูลใหม่จากไฟล์ที่อัพโหลด
          </p>
        </div>
      </div>

      <form action="{{ route('admin.import-numbers.store') }}" method="post" enctype="multipart/form-data" class="admin-form">
        @csrf
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
          <div class="admin-field">
            <label for="prepaid_file">ไฟล์เบอร์เติมเงิน (Prepaid CSV)</label>
            <input type="file" id="prepaid_file" name="prepaid_file" class="admin-input" accept=".csv" required>
            <span class="admin-feature-card__hint">ตัวอย่าง Header: เบอร์, ยอดจ่ายก้อนแรก, แพ็กเกจ, เครือข่าย, สถานะ</span>
          </div>

          <div class="admin-field">
            <label for="postpaid_file">ไฟล์เบอร์รายเดือน (Postpaid CSV)</label>
            <input type="file" id="postpaid_file" name="postpaid_file" class="admin-input" accept=".csv" required>
            <span class="admin-feature-card__hint">ตัวอย่าง Header: เบอร์, ยอดจ่ายก้อนแรก, แพ็กเกจ, เครือข่าย, สถานะ</span>
          </div>
        </div>

        <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--admin-border);">
          <button type="submit" class="admin-button" id="start-import-btn" onclick="return confirm('ยืนยันการลบข้อมูลเบอร์เดิมทั้งหมดและนำเข้าใหม่?')">
            <span style="display: flex; align-items: center; gap: 8px;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
              เริ่มนำเข้าข้อมูล (Start Import)
            </span>
          </button>
        </div>
      </form>
    </section>

    <section class="admin-card admin-feature-card">
      <div class="admin-feature-card__head">
        <div>
          <h2 class="admin-feature-card__title">ข้อแนะนำการเตรียมไฟล์</h2>
        </div>
      </div>
      <div class="admin-muted" style="font-size: 14px; line-height: 1.8;">
        <p>1. ไฟล์ต้องเป็นนามสกุล <strong>.csv</strong> เท่านั้น</p>
        <p>2. คอลัมน์ที่จำเป็นต้องมี: <strong>เบอร์</strong> (เช่น 0812345678), <strong>ยอดจ่ายก้อนแรก</strong> (ตัวเลขเท่านั้น) และ <strong>แพ็กเกจ</strong></p>
        <p>3. เบอร์เติมเงินให้เว้นแพ็กเกจว่างหรือใส่ <strong>-</strong>; เบอร์รายเดือนต้องใส่ <strong>รหัสแพ็กเกจ</strong> เช่น TRUE-SV-1499</p>
        <p>4. คอลัมน์เสริม: <strong>เครือข่าย</strong> (true, ais, dtac) และ <strong>สถานะ</strong> (active, sold, hold หรือ ว่าง, จอง, ขายแล้ว)</p>
        <p>5. หากไม่ระบุสถานะ ระบบจะกำหนดให้เป็น <strong>active (ว่าง)</strong> โดยอัตโนมัติ</p>
        <p>6. ข้อมูลในไฟล์ทั้งสองควรมีคอลัมน์เหมือนกันเพื่อให้ระบบประมวลผลได้อย่างถูกต้อง</p>
        <p>7. สามารถกดปุ่ม <strong>"ดาวน์โหลดไฟล์ตัวอย่าง"</strong> ด้านบนเพื่อดูรูปแบบไฟล์ที่ถูกต้องได้ครับ</p>
      </div>
    </section>
  </div>

  <style>
    .admin-input[type="file"] {
      padding: 8px;
      cursor: pointer;
    }
    .admin-input[type="file"]::file-selector-button {
      margin-right: 12px;
      padding: 4px 12px;
      border-radius: 8px;
      border: 1px solid var(--admin-border-strong);
      background: var(--admin-surface-soft);
      cursor: pointer;
      font-weight: 600;
      font-size: 13px;
    }
  </style>

  <script>
    document.querySelector('form').addEventListener('submit', function() {
      const btn = document.getElementById('start-import-btn');
      btn.disabled = true;
      btn.innerHTML = '<span style="display: flex; align-items: center; gap: 8px;">Processing...</span>';
    });
  </script>
@endsection
