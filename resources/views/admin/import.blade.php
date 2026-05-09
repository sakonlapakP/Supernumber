@extends('layouts.admin')

@section('title', 'Supernumber Admin | นำเข้าเบอร์ (CSV)')

@section('content')
  <div class="admin-page-head">
    <div>
      <h1>นำเข้าเบอร์โทรศัพท์ (CSV)</h1>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  @if (session('error_message'))
    <div class="admin-alert admin-alert--error">{{ session('error_message') }}</div>
  @endif

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">
      <ul>
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <section class="admin-card admin-feature-card">
    <div class="admin-feature-card__head">
      <div>
        <h2 class="admin-feature-card__title">อัปโหลดไฟล์ CSV</h2>
        <p class="admin-muted">เลือกไฟล์ CSV ที่ต้องการนำเข้าหรืออัปเดตราคาเบอร์ในระบบ</p>
      </div>
    </div>

    <form action="{{ route('admin.numbers.import.process') }}" method="post" enctype="multipart/form-data" class="admin-form">
      @csrf
      <div class="admin-field">
        <label for="csv_file" class="admin-label">ไฟล์ CSV</label>
        <input type="file" name="csv_file" id="csv_file" class="admin-input" accept=".csv" required>
        <div class="admin-field__hint">ไฟล์ต้องมีคอลัมน์ phone_number และ sale_price</div>
      </div>

      <div class="admin-form-actions">
        <button type="submit" class="admin-button">เริ่มการนำเข้า</button>
        <a href="{{ route('admin.numbers') }}" class="admin-button admin-button--secondary">ยกเลิก</a>
      </div>
    </form>
  </section>

  @if (session('import_summary'))
    @php $summary = session('import_summary'); @endphp
    <section class="admin-card admin-feature-card" style="margin-top: 20px;">
      <div class="admin-feature-card__head">
        <h2 class="admin-feature-card__title">สรุปการนำเข้าล่าสุด</h2>
      </div>
      <div class="admin-summary-list">
        <div class="admin-summary-item">
          <strong>เบอร์ใหม่ที่นำเข้า:</strong> {{ number_format($summary['new_imported']) }}
        </div>
        <div class="admin-summary-item">
          <strong>เบอร์เดิมที่อัปเดตราคา:</strong> {{ number_format($summary['updated_prices']) }}
        </div>
        <div class="admin-summary-item">
          <strong>เบอร์เดิมที่ราคาถูกกว่า/เท่าเดิม (ข้าม):</strong> {{ number_format($summary['skipped']) }}
        </div>
        <div class="admin-summary-item">
          <strong>รวมทั้งหมดในไฟล์:</strong> {{ number_format($summary['total']) }}
        </div>
      </div>
    </section>
  @endif
@endsection
