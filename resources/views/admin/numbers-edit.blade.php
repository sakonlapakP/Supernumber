@extends('layouts.admin')

@section('title', 'Supernumber Admin | แก้ไขเบอร์')

@section('content')
  @php
    $serviceTypeFilter = request()->query('service_type_filter');
    $returnRouteParameters = [];

    if (in_array($serviceTypeFilter, \App\Models\PhoneNumber::serviceTypeOptions(), true)) {
      $returnRouteParameters['service_type'] = $serviceTypeFilter;
    } else {
      $serviceTypeFilter = null;
    }

    $returnLabel = match ($serviceTypeFilter) {
      \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID => 'กลับหน้าเบอร์รายเดือน',
      \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID => 'กลับหน้าเบอร์เติมเงิน',
      default => 'กลับหน้าเบอร์ทั้งหมด',
    };
  @endphp

  <div class="admin-page-head">
    <div>
      <h1>แก้ไขเบอร์</h1>
      <p class="admin-subtitle">ตั้งค่าประเภทเบอร์ ราคา และสถานะจากหลังบ้าน</p>
    </div>
    <div class="admin-page-actions">
      <a href="{{ route('admin.numbers', $returnRouteParameters) }}" class="admin-button admin-button--secondary admin-button--compact">{{ $returnLabel }}</a>
    </div>
  </div>

  @if ($errors->any())
    <div class="admin-alert admin-alert--error">{{ $errors->first() }}</div>
  @endif

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success">{{ session('status_message') }}</div>
  @endif

  <section class="admin-card admin-feature-card">
    <form
      class="admin-form"
      action="{{ route('admin.numbers.update', array_filter([
        'phoneNumber' => $phoneNumber,
        'service_type_filter' => $serviceTypeFilter,
      ], static fn ($value) => $value !== null && $value !== '')) }}"
      method="post"
    >
      @csrf
      @method('put')

      <div class="admin-readonly-field">
        <div class="admin-readonly-field__label">เบอร์</div>
        <div class="admin-readonly-field__value">{{ $phoneNumber->formatted_number }}</div>
      </div>

      <div class="admin-readonly-field">
        <div class="admin-readonly-field__label">ผลรวมเบอร์</div>
        <div class="admin-readonly-field__value">{{ $phoneNumber->number_sum ?: '-' }}</div>
      </div>

      <div class="admin-readonly-field">
        <div class="admin-readonly-field__label">ประเภทเบอร์</div>
        <div class="admin-readonly-field__value">{{ $phoneNumber->service_type === \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID ? 'เติมเงิน' : 'รายเดือน' }}</div>
      </div>

      <div class="admin-readonly-field">
        <div class="admin-readonly-field__label">เครือข่าย</div>
        <div class="admin-readonly-field__value">{{ $phoneNumber->network_code }}</div>
      </div>

      <div class="admin-readonly-field">
        <div class="admin-readonly-field__label">ชื่อโปร / ข้อเสนอ</div>
        <div class="admin-readonly-field__value">{{ $phoneNumber->plan_name ?: '-' }}</div>
      </div>

      <div class="admin-readonly-field">
        <div class="admin-readonly-field__label">ราคา</div>
        <div class="admin-readonly-field__value">{{ number_format($phoneNumber->sale_price) }} บาท</div>
      </div>

      <div class="admin-field" style="margin-top: 1.5rem;">
        <label for="status">สถานะการแสดงผล</label>
        <select id="status" class="admin-select" name="status" required>
          @foreach (\App\Models\PhoneNumber::adminStatusOptions() as $status)
            <option value="{{ $status }}" @selected(old('status', $phoneNumber->status) === $status)>{{ $status }}</option>
          @endforeach
        </select>
      </div>

      <button type="submit" class="admin-button" style="margin-top: 1rem;">บันทึกการแก้ไขสถานะ</button>
    </form>
  </section>

  <style>
    .admin-readonly-field {
      display: flex;
      align-items: center;
      padding: 10px 0;
      margin-bottom: 4px;
      border-bottom: 1px solid #f1f5f9;
    }
    .admin-readonly-field:last-of-type {
      border-bottom: none;
    }
    .admin-readonly-field__label {
      flex: 0 0 160px;
      color: #64748b;
      font-size: 0.95rem;
      font-weight: 500;
    }
    .admin-readonly-field__value {
      flex: 1;
      color: #1e293b;
      font-weight: 600;
      font-size: 1.05rem;
    }
  </style>
@endsection

@push('scripts')
  {{-- Scripts removed as fields are now static --}}
@endpush
