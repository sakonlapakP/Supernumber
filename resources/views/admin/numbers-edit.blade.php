@extends('layouts.admin')

@section('title', 'Supernumber Admin | Edit Number')

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
      \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID => 'กลับหน้า Postpaid Numbers',
      \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID => 'กลับหน้า Prepaid Numbers',
      default => 'กลับหน้า All Numbers',
    };
  @endphp

  <div class="admin-page-head">
    <div>
      <h1>Edit Number</h1>
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
        <h2 class="admin-feature-card__title">{{ $phoneNumber->display_number ?: $phoneNumber->phone_number }}</h2>
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
        <label for="phone_number">เบอร์จริงในระบบ</label>
        <input id="phone_number" class="admin-input" type="text" value="{{ $phoneNumber->phone_number }}" readonly />
        <p class="admin-muted" style="margin: 8px 0 0; font-size: 0.9rem;">ฟิลด์นี้ถูกล็อกไว้เพื่อไม่ให้กระทบ order และ activity log เดิม</p>
      </div>

      <div class="admin-field">
        <label for="display_number">รูปแบบแสดงผล</label>
        <input id="display_number" class="admin-input" type="text" name="display_number" value="{{ old('display_number', $phoneNumber->display_number) }}" placeholder="เช่น 089-123-4567" />
      </div>

      <div class="admin-field">
        <label for="service_type">ประเภทเบอร์</label>
        <select id="service_type" class="admin-select" name="service_type" required>
          @foreach (\App\Models\PhoneNumber::serviceTypeOptions() as $serviceType)
            <option value="{{ $serviceType }}" @selected(old('service_type', $phoneNumber->service_type) === $serviceType)>
              {{ $serviceType === \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID ? 'เติมเงิน' : 'รายเดือน' }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="admin-field">
        <label for="network_code">เครือข่าย</label>
        <input id="network_code" class="admin-input" type="text" name="network_code" value="{{ old('network_code', $phoneNumber->network_code) }}" placeholder="เช่น true_dtac" required />
      </div>

      <div class="admin-field">
        <label for="plan_name" id="plan_name_label">ชื่อโปร / ข้อเสนอ</label>
        <input id="plan_name" class="admin-input" type="text" name="plan_name" value="{{ old('plan_name', $phoneNumber->plan_name) }}" placeholder="เช่น True Super Value หรือ เติมเงิน" />
        <p id="plan_name_hint" class="admin-muted" style="margin: 8px 0 0; font-size: 0.9rem;"></p>
      </div>

      <div class="admin-field">
        <label for="sale_price" id="sale_price_label">ราคา</label>
        <input id="sale_price" class="admin-input" type="number" name="sale_price" min="1" step="1" value="{{ old('sale_price', (int) $phoneNumber->sale_price) }}" required />
        <p id="sale_price_hint" class="admin-muted" style="margin: 8px 0 0; font-size: 0.9rem;"></p>
      </div>

      <div class="admin-field">
        <label for="price_text">ข้อความราคาสำรอง</label>
        <input id="price_text" class="admin-input" type="text" name="price_text" value="{{ old('price_text', $phoneNumber->price_text) }}" placeholder="ถ้าเว้นว่าง ระบบจะใช้ตัวเลขจากราคา" />
      </div>

      <div class="admin-field">
        <label for="status">สถานะ</label>
        <select id="status" class="admin-select" name="status" required>
          @foreach (\App\Models\PhoneNumber::adminStatusOptions() as $status)
            <option value="{{ $status }}" @selected(old('status', $phoneNumber->status) === $status)>{{ $status }}</option>
          @endforeach
        </select>
      </div>

      <p id="service_type_help" class="admin-subtitle" style="margin: 0;"></p>

      <button type="submit" class="admin-button">บันทึกการแก้ไข</button>
    </form>
  </section>
@endsection

@push('scripts')
  <script>
    (() => {
      const serviceTypeInput = document.getElementById("service_type");
      const planNameLabel = document.getElementById("plan_name_label");
      const planNameHint = document.getElementById("plan_name_hint");
      const salePriceLabel = document.getElementById("sale_price_label");
      const salePriceHint = document.getElementById("sale_price_hint");
      const serviceTypeHelp = document.getElementById("service_type_help");

      if (!serviceTypeInput || !planNameLabel || !planNameHint || !salePriceLabel || !salePriceHint || !serviceTypeHelp) {
        return;
      }

      const renderByType = () => {
        const isPrepaid = serviceTypeInput.value === "{{ \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID }}";

        planNameLabel.textContent = isPrepaid ? "ชื่อข้อเสนอเติมเงิน" : "ชื่อโปรรายเดือน";
        planNameHint.textContent = isPrepaid
          ? "ถ้าเว้นว่าง ระบบจะตั้งชื่อเริ่มต้นเป็น \"เติมเงิน\""
          : "ถ้าเว้นว่าง ระบบจะตั้งชื่อเริ่มต้นเป็น \"{{ \App\Models\PhoneNumber::PACKAGE_NAME }}\"";
        salePriceLabel.textContent = isPrepaid ? "ราคาขายครั้งเดียว (บาท)" : "ราคาแพ็กเกจ (บาท / เดือน)";
        salePriceHint.textContent = isPrepaid
          ? "ราคานี้จะเป็นยอดโอนเต็มจำนวนของเบอร์เติมเงิน"
          : "ราคานี้จะใช้เป็นแพ็กเกจเริ่มต้นของเบอร์รายเดือน";
        serviceTypeHelp.textContent = isPrepaid
          ? "เบอร์เติมเงิน: ลูกค้ากรอกชื่อ ที่อยู่ และอัปโหลดสลิป จากนั้นระบบจะ hold เพื่อรอ admin ตรวจสอบ"
          : "เบอร์รายเดือน: ใช้ flow เดิมที่เลือกแพ็กเกจและดำเนินการตามขั้นตอนของรายเดือน";
      };

      serviceTypeInput.addEventListener("change", renderByType);
      renderByType();
    })();
  </script>
@endpush
