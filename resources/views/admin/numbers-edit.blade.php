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
    <div class="admin-feature-card__head">
      <div>
        <h2 class="admin-feature-card__title">{{ $phoneNumber->formatted_number }}</h2>
        <p class="admin-feature-card__hint">เบอร์จริง: {{ $phoneNumber->phone_number }} | การแสดงผลปัจจุบัน: {{ $phoneNumber->service_type_label }} | {{ $phoneNumber->payment_label }}</p>
      </div>
    </div>

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

      <div class="admin-field">
        <label>เบอร์จริงในระบบ</label>
        <div class="admin-readonly-display">{{ $phoneNumber->phone_number }}</div>
        <p class="admin-muted" style="margin: 8px 0 0; font-size: 0.9rem;">ฟิลด์นี้ถูกล็อกไว้เพื่อไม่ให้กระทบ order และ activity log เดิม</p>
      </div>

      <div class="admin-field">
        <label>ผลรวมเบอร์</label>
        <div class="admin-readonly-display">{{ $phoneNumber->number_sum ?: '-' }}</div>
      </div>

      <div class="admin-field">
        <label>ประเภทเบอร์</label>
        <div class="admin-readonly-display">{{ $phoneNumber->service_type === \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID ? 'เติมเงิน' : 'รายเดือน' }}</div>
      </div>

      <div class="admin-field">
        <label>เครือข่าย</label>
        <div class="admin-readonly-display">{{ $phoneNumber->network_code }}</div>
      </div>

      <div class="admin-field">
        <label>ชื่อโปร / ข้อเสนอ</label>
        <div class="admin-readonly-display">{{ $phoneNumber->plan_name ?: '-' }}</div>
      </div>

      <div class="admin-field">
        <label>ราคา</label>
        <div class="admin-readonly-display">{{ number_format($phoneNumber->sale_price) }} บาท</div>
      </div>

      <div class="admin-field">
        <label for="status">สถานะ</label>
        <select id="status" class="admin-select" name="status" required>
          @foreach (\App\Models\PhoneNumber::adminStatusOptions() as $status)
            <option value="{{ $status }}" @selected(old('status', $phoneNumber->status) === $status)>{{ $status }}</option>
          @endforeach
        </select>
      </div>

      <button type="submit" class="admin-button" style="margin-top: 1rem;">บันทึกการแก้ไข</button>
    </form>
  </section>

  <style>
    .admin-readonly-display {
      padding: 12px 16px;
      background-color: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      color: #1e293b;
      font-weight: 500;
    }
  </style>
@endsection

@push('scripts')
  {{-- Scripts removed as fields are now static --}}
@endpush
