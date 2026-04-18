@extends('layouts.app')

@section('title', 'Supernumber | สั่งซื้อเบอร์')

@section('content')
  @php
    $orderedNumber = $currentNumber?->phone_number ?? request('number', '-');
    $serviceType = trim((string) ($serviceType ?? $currentNumber?->service_type ?? \App\Models\PhoneNumber::SERVICE_TYPE_POSTPAID));
    $isPrepaid = $serviceType === \App\Models\PhoneNumber::SERVICE_TYPE_PREPAID;
    $packagePlanName = trim((string) ($packagePlanName ?? \App\Models\PhoneNumber::PACKAGE_NAME));
    $minimumFirstPayment = 999;
    if ($isPrepaid) {
        $basePackage = (int) ($currentNumber?->sale_price ?? request('package', 0));
        $basePackage = $basePackage > 0 ? $basePackage : 0;
        $allPackages = $basePackage > 0 ? [$basePackage] : [];
        $packageCatalog = $basePackage > 0 ? [
            $basePackage => [
                'label' => 'เบอร์เติมเงินพร้อมใช้งาน',
                'data' => 'เติมเงิน',
                'speed' => number_format($basePackage) . ' บาท',
                'voice' => 'จัดส่งตามที่อยู่',
                'ent' => 'ไม่ต้องยืนยันตัวตน',
            ],
        ] : [];
        $availablePackages = $allPackages;
        $selectedPackage = $basePackage;
        $selectedPackageLabel = $selectedPackage > 0
            ? 'ราคาขาย ' . number_format($selectedPackage) . ' บาท'
            : 'ราคาขาย -';
    } else {
        $allPackages = [499, 599, 699, 899, 999, 1199, 1499, 1699, 1999, 2199, 2499, 2699, 2999, 3499];
        $packageCatalog = [
            499 => ['label' => 'พื้นฐาน', 'data' => '50GB', 'speed' => '1 Mbps (หลังหมดสปีดเต็ม)', 'voice' => '150 นาที', 'ent' => 'ไม่รวม'],
            599 => ['label' => 'เริ่มคุ้ม', 'data' => '60GB', 'speed' => '1 Mbps (หลังหมดสปีดเต็ม)', 'voice' => '200 นาที', 'ent' => 'ไม่รวม'],
            699 => ['label' => 'แพคเกจแนะนำ', 'data' => '70GB', 'speed' => '1 Mbps (หลังหมดสปีดเต็ม)', 'voice' => '250 นาที', 'ent' => 'NOW ENT 12 เดือน'],
            899 => ['label' => 'ยอดนิยม', 'data' => '90GB', 'speed' => '1 Mbps (หลังหมดสปีดเต็ม)', 'voice' => '300 นาที', 'ent' => 'NOW ENT 12 เดือน'],
            999 => ['label' => 'คุ้มค่า+', 'data' => '100GB', 'speed' => '1 Mbps (หลังหมดสปีดเต็ม)', 'voice' => '400 นาที', 'ent' => 'NOW ENT 12 เดือน'],
            1199 => ['label' => 'โปรสปีดสูง', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '600 นาที', 'ent' => 'NOW ENT + CyberSafe PRO'],
            1499 => ['label' => 'พรีเมียม', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '900 นาที', 'ent' => 'NOW ENT + CyberSafe PRO'],
            1699 => ['label' => 'พรีเมียม+', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '1,100 นาที', 'ent' => 'NOW ENT + CyberSafe PRO'],
            1999 => ['label' => 'โปรธุรกิจ', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '1,500 นาที', 'ent' => 'NOW ENT + CyberSafe PRO'],
            2199 => ['label' => 'โปรธุรกิจ+', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '1,800 นาที', 'ent' => 'NOW ENT + CyberSafe PRO'],
            2499 => ['label' => 'Max', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '2,300 นาที', 'ent' => 'NOW ENT + CyberSafe PRO'],
            2699 => ['label' => 'Max+', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '2,700 นาที', 'ent' => 'NOW ENT + CyberSafe PRO'],
            2999 => ['label' => 'Ultra', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '3,000 นาที', 'ent' => 'NOW ENT + CyberSafe PRO'],
            3499 => ['label' => 'Ultra Max', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '3,400 นาที', 'ent' => 'NOW ENT + CyberSafe PRO'],
        ];
        $basePackage = (int) request('package', 699);
        if (!in_array($basePackage, $allPackages, true)) {
            $basePackage = 699;
        }
        $availablePackages = array_values(array_filter($allPackages, fn ($price) => $price >= $basePackage));
        $selectedPackage = (int) request('selected_package', $basePackage);
        if (!in_array($selectedPackage, $availablePackages, true)) {
            $selectedPackage = $basePackage;
        }
        $selectedPackageLabel = \App\Models\PhoneNumber::buildPackageLabel($packagePlanName, $selectedPackage);
    }
    $initialPaymentAmount = $isPrepaid ? $selectedPackage : max($selectedPackage, $minimumFirstPayment);
  @endphp

  <section class="book-page">
    <div class="container">
      <div class="book-head">
        <h1>ลงทะเบียนสั่งซื้อเบอร์</h1>
        <p>กรอกข้อมูลง่ายๆ 3 ขั้นตอนเพื่อยืนยันการสั่งซื้อ</p>
      </div>

      <div class="book-steps">
        <div class="book-step is-active" data-step-pill="1">
          <span class="book-step__index">1</span>
          <div>
            <h2>ข้อมูลสินค้าและที่อยู่ที่จัดส่ง</h2>
          </div>
        </div>

        <div class="book-step" data-step-pill="2">
          <span class="book-step__index">2</span>
          <div>
            <h2 id="step2-title">การชำระเงิน</h2>
          </div>
        </div>

        <div class="book-step" data-step-pill="3">
          <span class="book-step__index">3</span>
          <div>
            <h2>{{ $isPrepaid ? 'สำเร็จ' : 'การนัดหมายและยืนยันตัวตน' }}</h2>
          </div>
        </div>
      </div>

      <div class="book-save-banner book-card-step is-hidden" data-step-panel="3" id="book-save-banner">
        บันทึกคำสั่งซื้อของคุณแล้ว
      </div>

      @if (session('status_message'))
        <div class="book-card" style="border-color: rgba(35, 165, 138, 0.35); background: #effcf7; color: #1c584a;">
          {{ session('status_message') }}
        </div>
      @endif

      @if ($errors->any())
        <div class="book-card" style="border-color: rgba(196, 56, 56, 0.32); background: #fff5f5; color: #8f2a2a;">
          {{ $errors->first() }}
        </div>
      @endif

      <form class="book-form" action="{{ route('book.submit') }}" method="post" enctype="multipart/form-data" id="book-form">
        @csrf
        <input type="hidden" name="saved_order_id" id="saved-order-id" value="">
        <div class="book-card book-card-step is-active" data-step-panel="1">
          <h3>ขั้นตอนที่ 1. ข้อมูลผู้สั่งซื้อ</h3>
          <div class="book-grid">
            <label class="full">
              เบอร์ที่สั่งซื้อ
              <input type="text" name="ordered_number" value="{{ $orderedNumber }}" readonly>
            </label>
            <div class="full book-network-row">
              <p class="book-network-line">
                เครือข่าย:
                <span class="network-brand-gradient">True-Dtac</span>
              </p>
              <label class="book-network-select-label">
                {{ $isPrepaid ? 'ราคาขายเบอร์' : 'กรุณาเลือกแพคเกจ' }}
                <select name="selected_package" id="selected_package">
                  @foreach ($availablePackages as $packagePrice)
                    @php $plan = $packageCatalog[$packagePrice]; @endphp
                    <option
                      value="{{ $packagePrice }}"
                      data-promo="{{ \App\Models\PhoneNumber::buildPackageLabel($packagePlanName, $packagePrice) }}"
                      data-label="{{ $plan['label'] }}"
                      data-data="{{ $plan['data'] }}"
                      data-speed="{{ $plan['speed'] }}"
                      data-voice="{{ $plan['voice'] }}"
                      data-ent="{{ $plan['ent'] }}"
                      {{ $packagePrice === $selectedPackage ? 'selected' : '' }}
                    >
                      {{ number_format($packagePrice) }} บาท{{ $isPrepaid ? '' : ' / เดือน' }}
                    </option>
                  @endforeach
                </select>
              </label>
            </div>
            @php
              $selectedPlan = $packageCatalog[$selectedPackage] ?? ['data' => '-', 'speed' => '-', 'voice' => '-', 'ent' => '-'];
            @endphp
            <div class="full package-preview" id="package-preview">
              <p class="package-preview__name" id="preview-name">{{ $isPrepaid ? 'ประเภท: เบอร์เติมเงิน' : 'ชื่อโปร: ' . $selectedPackageLabel }}</p>
              <h4>{{ $isPrepaid ? 'ราคาขาย ' . number_format($selectedPackage) . ' บาท' : 'โปรโมชั่นแพคเกจ ' . number_format($selectedPackage) . ' บาท' }}</h4>
              <div class="package-preview__grid">
                <div><span>{{ $isPrepaid ? 'ประเภท' : 'Data' }}</span><strong id="preview-data">{{ $selectedPlan['data'] }}</strong></div>
                <div><span>{{ $isPrepaid ? 'ยอดชำระ' : 'ความเร็วหลังเน็ตเต็มสปีด' }}</span><strong id="preview-speed">{{ $selectedPlan['speed'] }}</strong></div>
                <div><span>{{ $isPrepaid ? 'การจัดส่ง' : 'โทรทุกเครือข่าย' }}</span><strong id="preview-voice">{{ $selectedPlan['voice'] }}</strong></div>
                <div><span>{{ $isPrepaid ? 'หมายเหตุ' : 'สิทธิพิเศษ' }}</span><strong id="preview-ent">{{ $selectedPlan['ent'] }}</strong></div>
              </div>
              <div class="package-conditions">
                <p class="package-conditions__title">{{ $isPrepaid ? 'เงื่อนไขการสั่งซื้อ' : 'เงื่อนไขแพคเกจ' }}</p>
                <ul>
                  @if ($isPrepaid)
                    <li>เบอร์เติมเงินชำระตามราคาที่แสดงและรอแอดมินตรวจสอบสลิปก่อนจัดส่ง</li>
                    <li>ไม่ต้องนัดยืนยันตัวตนผ่านเครือข่ายหลังสั่งซื้อ</li>
                    <li>เมื่อทีมงานตรวจสอบการชำระเงินเรียบร้อย จะเปลี่ยนสถานะเบอร์เป็น sold และจัดส่งตามที่อยู่ที่แจ้งไว้</li>
                  @else
                    <li>แพคเกจนี้สำหรับเบอร์ใหม่/ย้ายค่ายที่ร่วมรายการ</li>
                    <li>ค่าโทรเกินแพคเกจและบริการเสริม คิดตามเงื่อนไขผู้ให้บริการ</li>
                    <li>สิทธิพิเศษอาจมีการเปลี่ยนแปลงตามเงื่อนไขค่ายมือถือ</li>
                  @endif
                </ul>
              </div>
            </div>
            <div class="full book-name-row">
              <label class="book-name-row__prefix">
                คำนำหน้าชื่อ
                <select name="title_prefix" {{ $isPrepaid ? 'required' : '' }}>
                  <option value="">เลือกคำนำหน้า</option>
                  <option value="นาย">นาย</option>
                  <option value="นาง">นาง</option>
                  <option value="นางสาว">นางสาว</option>
                </select>
              </label>
              <label class="book-name-row__first">
                ชื่อ
                <input type="text" name="first_name" placeholder="ชื่อจริง" {{ $isPrepaid ? 'required' : '' }}>
              </label>
              <label class="book-name-row__last">
                นามสกุล
                <input type="text" name="last_name" placeholder="นามสกุล" {{ $isPrepaid ? 'required' : '' }}>
              </label>
            </div>
            <label>
              อีเมลล์
              <input type="email" name="email" placeholder="name@example.com" {{ $isPrepaid ? 'required' : '' }}>
            </label>
            <label>
              เบอร์มือถือปัจจุบัน
              <input type="tel" name="current_phone" inputmode="numeric" pattern="[0-9]*" maxlength="10" placeholder="เบอร์ที่ติดต่อได้" {{ $isPrepaid ? 'required' : '' }}>
            </label>
            <label class="full">
              <span class="address-section-title">ที่อยู่ที่จัดส่ง</span>
              <input type="text" name="shipping_address_line" placeholder="บ้านเลขที่,ซอย,หมู่,ถนน,แขวง/ตำบล" {{ $isPrepaid ? 'required' : '' }}>
            </label>
            <label class="address-autocomplete">
              ตำบล / แขวง
              <input type="text" id="th-district" name="district" placeholder="เช่น คลองตันเหนือ" autocomplete="off" {{ $isPrepaid ? 'required' : '' }}>
              <div class="address-suggest" id="district-suggest" hidden></div>
            </label>
            <label>
              อำเภอ / เขต
              <input type="text" id="th-amphoe" name="amphoe" list="amphoe-list" placeholder="เช่น วัฒนา" {{ $isPrepaid ? 'required' : '' }}>
            </label>
            <label>
              จังหวัด
              <input type="text" id="th-province" name="province" list="province-list" placeholder="เช่น กรุงเทพมหานคร" {{ $isPrepaid ? 'required' : '' }}>
            </label>
            <label>
              รหัสไปรษณีย์
              <input type="text" id="th-zipcode" name="zipcode" inputmode="numeric" maxlength="5" placeholder="10110" {{ $isPrepaid ? 'required' : '' }}>
            </label>
          </div>
        </div>

        <div class="book-card book-card-step is-hidden" data-step-panel="2">
          <h3 id="pay-card-title">ขั้นตอนที่ 2. ชำระเงิน {{ number_format($initialPaymentAmount) }} บาท</h3>
          <p class="book-note" id="pay-card-note">โอนเงินจำนวน {{ number_format($initialPaymentAmount) }} บาท แล้วแนบสลิปด้านล่าง</p>
          <div class="step2-layout">
            <div class="step2-upload">
              <div class="bank-account-box" aria-label="ข้อมูลบัญชีสำหรับโอนเงิน">
                <h4>ข้อมูลบัญชีสำหรับโอนเงิน</h4>
                <p><span>ธนาคาร</span><strong>ธนาคารกสิกรไทย</strong></p>
                <p><span>สาขา</span><strong>คลองเตย</strong></p>
                <p><span>ชื่อบัญชี</span><strong>นาย สมบัติ เชาวนิเกษม</strong></p>
                <p class="bank-account-copy-row">
                  <span>เลขบัญชี</span>
                  <strong class="bank-account-no" id="bank-account-no">020-1-70470-7</strong>
                  <button type="button" class="bank-copy-btn" id="copy-account-btn" aria-label="คัดลอกเลขบัญชี">Copy</button>
                </p>
                <small class="bank-copy-hint" id="copy-account-hint" aria-live="polite"></small>
              </div>
              <label class="full">
                แนบสลิปโอนเงิน
                <div class="slip-upload-row">
                  <input type="file" class="slip-upload-input" id="payment-slip-input" name="payment_slip" accept="image/*,application/pdf,.pdf" capture="environment">
                  <button type="button" class="slip-clear-btn" id="clear-slip-btn" aria-label="ลบไฟล์สลิป" hidden>X</button>
                </div>
                <small class="book-help">รองรับไฟล์จากมือถือ: JPG, PNG, HEIC และ PDF</small>
                <small class="book-help book-help--warning" id="payment-slip-required-hint">โปรดแนบหลักฐานการโอนเงินเพื่อสั่งซื้อเบอร์</small>
              </label>
            </div>
            <aside class="step2-summary" aria-label="สรุปข้อมูลที่สั่งซื้อ">
              <h4>สรุปข้อมูลที่สั่งซื้อ</h4>
              <div class="step2-summary__grid">
                <p><span>เบอร์ที่สั่งซื้อ</span><strong id="summary-ordered-number">-</strong></p>
                <p><span>{{ $isPrepaid ? 'ยอดชำระ' : 'ยอดชำระครั้งแรก' }}</span><strong id="summary-package">-</strong></p>
                <p><span>ชื่อผู้สั่งซื้อ</span><strong id="summary-full-name">-</strong></p>
                <p><span>เบอร์มือถือปัจจุบัน</span><strong id="summary-phone">-</strong></p>
                <p><span>อีเมลล์</span><strong id="summary-email">-</strong></p>
                <p><span>ที่อยู่จัดส่ง</span><strong id="summary-address">-</strong></p>
              </div>
            </aside>
          </div>
        </div>

        <div class="book-card book-card-step is-hidden" data-step-panel="3">
          <h3>ขั้นตอนที่ 3. {{ $isPrepaid ? 'สำเร็จ' : 'ยืนยันตัวตน' }}</h3>
          @if ($isPrepaid)
            <p class="book-note">
              บันทึกรายการคำสั่งซื้อเรียบร้อยแล้ว กรุณาทวนรายละเอียดผู้รับและที่อยู่จัดส่งอีกครั้ง
              ทีมงานจะจัดส่งซิมตามชื่อและที่อยู่ที่คุณแจ้งไว้ภายในวันและเวลาทำการของบริษัท
            </p>
            <div class="step2-summary" aria-label="สรุปรายการคำสั่งซื้อและที่อยู่จัดส่ง">
              <h4>สรุปรายการคำสั่งซื้อ</h4>
              <div class="step2-summary__grid">
                <p><span>เบอร์ที่สั่งซื้อ</span><strong id="final-summary-ordered-number">-</strong></p>
                <p><span>ยอดชำระ</span><strong id="final-summary-package">-</strong></p>
                <p><span>ชื่อผู้รับ</span><strong id="final-summary-full-name">-</strong></p>
                <p><span>เบอร์มือถือปัจจุบัน</span><strong id="final-summary-phone">-</strong></p>
                <p><span>อีเมลล์</span><strong id="final-summary-email">-</strong></p>
                <p><span>ที่อยู่จัดส่ง</span><strong id="final-summary-address">-</strong></p>
              </div>
            </div>
          @else
            <p class="book-note">
              บริษัท ซุปเปอร์นัมเบอร์ ตระหนักถึงความสำคัญของความปลอดภัยของข้อมูลส่วนบุคคลเป็นลำดับแรก
              เรามีนโยบายไม่จัดเก็บข้อมูลส่วนตัวของท่านในระบบ เพื่อความปลอดภัยสูงสุด
              กรุณาระบุวันและเวลาที่สะดวก เพื่อให้เจ้าหน้าที่ติดต่อกลับสำหรับการยืนยันตัวตนผ่านระบบมาตรฐานของเครือข่ายผู้ให้บริการ (Operator) โดยตรง
            </p>
            <div class="book-grid booking-grid">
              <label>
                วันนัดหมาย
                <input type="date" name="appointment_date" id="appointment-date">
              </label>
              <label>
                ช่วงเวลานัดหมาย
                <select name="appointment_time_slot" id="appointment-time-slot">
                  <option value="">เลือกช่วงเวลา</option>
                </select>
              </label>
            </div>
          @endif
          <div class="support-contact-box">
            <p class="support-contact-box__title">{{ $isPrepaid ? 'ต้องการสอบถามสถานะเพิ่มเติม' : 'หรือติดต่อเจ้าหน้าที่' }}</p>
            <div class="support-contact-box__actions">
              <a class="support-contact-box__line" href="https://line.me/ti/p/~supernumber" target="_blank" rel="noopener noreferrer">LINE @supernumber</a>
              <div class="support-contact-box__phone-group">
                <a href="tel:0963232656">โทร 096-323-2656</a>
                <a href="tel:0963232665">โทร 096-323-2665</a>
              </div>
            </div>
          </div>
        </div>

        <div class="book-wizard-actions book-wizard-actions--outside book-wizard-actions--next book-card-step is-active" data-step-panel="1">
          <button type="button" class="card-btn card-btn--buy" data-go-step="2">ต่อไป</button>
        </div>
        <div class="book-wizard-actions book-wizard-actions--outside book-card-step is-hidden" data-step-panel="2">
          <button type="button" class="card-btn" data-go-step="1">ย้อนกลับ</button>
          <button type="button" class="card-btn card-btn--buy" id="step2-next-btn">บันทึกและไปขั้นตอนต่อไป</button>
        </div>
        @unless ($isPrepaid)
          <div class="book-wizard-actions book-wizard-actions--outside book-card-step is-hidden" data-step-panel="3">
            <button type="button" class="card-btn" data-go-step="2">ย้อนกลับ</button>
            <button type="submit" class="card-btn card-btn--buy" id="step3-submit-btn">สั่งซื้อ</button>
          </div>
        @endunless
      </form>
    </div>
  </section>

  <link rel="stylesheet" href="{{ asset('vendor/jquery-thailand/jquery.Thailand.min.css') }}"
    onerror="this.onerror=null;this.href='https://earthchie.github.io/jquery.Thailand.js/dist/jquery.Thailand.min.css'">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="{{ asset('vendor/jquery-thailand/JQL.min.js') }}"
    onerror="this.onerror=null;this.src='https://earthchie.github.io/jquery.Thailand.js/dependencies/JQL.min.js'"></script>
  <script src="{{ asset('vendor/jquery-thailand/typeahead.bundle.js') }}"
    onerror="this.onerror=null;this.src='https://earthchie.github.io/jquery.Thailand.js/dependencies/typeahead.bundle.js'"></script>
  <script src="{{ asset('vendor/jquery-thailand/zip.js') }}"
    onerror="this.onerror=null;this.src='https://earthchie.github.io/jquery.Thailand.js/dependencies/zip.js'"></script>
  <script src="{{ asset('vendor/jquery-thailand/jquery.Thailand.min.js') }}"
    onerror="this.onerror=null;this.src='https://earthchie.github.io/jquery.Thailand.js/dist/jquery.Thailand.min.js'"></script>
  <script>
    (function () {
      const isPrepaid = @json($isPrepaid);
      const minimumFirstPayment = @json($minimumFirstPayment);
      const form = document.getElementById("book-form");
      const packageSelect = document.getElementById("selected_package");
      const previewBox = document.getElementById("package-preview");
      const stepPills = Array.from(document.querySelectorAll("[data-step-pill]"));
      const stepPanels = Array.from(document.querySelectorAll("[data-step-panel]"));
      const goStepButtons = Array.from(document.querySelectorAll("[data-go-step]"));
      const storageKey = `bookFormDraft:${form?.querySelector('[name=\"ordered_number\"]')?.value || "default"}`;
      if (!form || !packageSelect || !previewBox) return;

      const trackAnalyticsEvent = (eventName, params = {}) => {
        if (!window.SupernumberAnalytics || typeof window.SupernumberAnalytics.track !== "function") {
          return false;
        }

        return window.SupernumberAnalytics.track(eventName, params);
      };

      const title = previewBox.querySelector("h4");
      const nameEl = document.getElementById("preview-name");
      const dataEl = document.getElementById("preview-data");
      const speedEl = document.getElementById("preview-speed");
      const voiceEl = document.getElementById("preview-voice");
      const entEl = document.getElementById("preview-ent");
      const step2Title = document.getElementById("step2-title");
      const payCardTitle = document.getElementById("pay-card-title");
      const payCardNote = document.getElementById("pay-card-note");
      const summaryOrderedNumber = document.getElementById("summary-ordered-number");
      const summaryPackage = document.getElementById("summary-package");
      const summaryFullName = document.getElementById("summary-full-name");
      const summaryPhone = document.getElementById("summary-phone");
      const summaryEmail = document.getElementById("summary-email");
      const summaryAddress = document.getElementById("summary-address");
      const finalSummaryOrderedNumber = document.getElementById("final-summary-ordered-number");
      const finalSummaryPackage = document.getElementById("final-summary-package");
      const finalSummaryFullName = document.getElementById("final-summary-full-name");
      const finalSummaryPhone = document.getElementById("final-summary-phone");
      const finalSummaryEmail = document.getElementById("final-summary-email");
      const finalSummaryAddress = document.getElementById("final-summary-address");
      const bankAccountNoEl = document.getElementById("bank-account-no");
      const copyAccountBtn = document.getElementById("copy-account-btn");
      const copyAccountHint = document.getElementById("copy-account-hint");
      const paymentSlipInput = document.getElementById("payment-slip-input");
      const clearSlipBtn = document.getElementById("clear-slip-btn");
      const paymentSlipRequiredHint = document.getElementById("payment-slip-required-hint");
      const step2NextBtn = document.getElementById("step2-next-btn");
      const savedOrderIdInput = document.getElementById("saved-order-id");
      const saveBanner = document.getElementById("book-save-banner");
      const appointmentDateInput = document.getElementById("appointment-date");
      const appointmentTimeSlotInput = document.getElementById("appointment-time-slot");
      const addressHelper = document.getElementById("address-helper");
      const districtInput = document.getElementById("th-district");
      const amphoeInput = document.getElementById("th-amphoe");
      const provinceInput = document.getElementById("th-province");
      const zipcodeInput = document.getElementById("th-zipcode");
      const districtSuggest = document.getElementById("district-suggest");

      let fallbackAddressData = [
        { district: "คลองเตย", amphoe: "คลองเตย", province: "กรุงเทพมหานคร", zipcode: "10110" },
        { district: "คลองเตยเหนือ", amphoe: "วัฒนา", province: "กรุงเทพมหานคร", zipcode: "10110" },
        { district: "คลองตันเหนือ", amphoe: "วัฒนา", province: "กรุงเทพมหานคร", zipcode: "10110" },
        { district: "พระโขนงเหนือ", amphoe: "วัฒนา", province: "กรุงเทพมหานคร", zipcode: "10110" },
        { district: "ลาดยาว", amphoe: "จตุจักร", province: "กรุงเทพมหานคร", zipcode: "10900" },
        { district: "จตุจักร", amphoe: "จตุจักร", province: "กรุงเทพมหานคร", zipcode: "10900" },
        { district: "สามเสนใน", amphoe: "พญาไท", province: "กรุงเทพมหานคร", zipcode: "10400" },
        { district: "ทุ่งพญาไท", amphoe: "ราชเทวี", province: "กรุงเทพมหานคร", zipcode: "10400" },
        { district: "มักกะสัน", amphoe: "ราชเทวี", province: "กรุงเทพมหานคร", zipcode: "10400" },
        { district: "บางนาเหนือ", amphoe: "บางนา", province: "กรุงเทพมหานคร", zipcode: "10260" },
        { district: "บางนาใต้", amphoe: "บางนา", province: "กรุงเทพมหานคร", zipcode: "10260" },
        { district: "บางจาก", amphoe: "พระโขนง", province: "กรุงเทพมหานคร", zipcode: "10260" },
        { district: "หัวหมาก", amphoe: "บางกะปิ", province: "กรุงเทพมหานคร", zipcode: "10240" },
        { district: "คลองจั่น", amphoe: "บางกะปิ", province: "กรุงเทพมหานคร", zipcode: "10240" },
        { district: "สุเทพ", amphoe: "เมืองเชียงใหม่", province: "เชียงใหม่", zipcode: "50200" },
        { district: "ศรีภูมิ", amphoe: "เมืองเชียงใหม่", province: "เชียงใหม่", zipcode: "50200" },
        { district: "ในเมือง", amphoe: "เมืองขอนแก่น", province: "ขอนแก่น", zipcode: "40000" },
        { district: "ในเมือง", amphoe: "เมืองนครราชสีมา", province: "นครราชสีมา", zipcode: "30000" },
        { district: "ในเมือง", amphoe: "เมืองอุบลราชธานี", province: "อุบลราชธานี", zipcode: "34000" },
        { district: "หาดใหญ่", amphoe: "หาดใหญ่", province: "สงขลา", zipcode: "90110" },
        { district: "ตลาดใหญ่", amphoe: "เมืองภูเก็ต", province: "ภูเก็ต", zipcode: "83000" }
      ];

      const updatePreview = () => {
        const opt = packageSelect.options[packageSelect.selectedIndex];
        if (!opt) return;
        const packageAmount = Number(opt.value);
        const priceText = packageAmount.toLocaleString("th-TH");
        const initialPaymentAmount = isPrepaid ? packageAmount : Math.max(packageAmount, minimumFirstPayment);
        const initialPaymentText = initialPaymentAmount.toLocaleString("th-TH");
        if (isPrepaid) {
          if (nameEl) nameEl.textContent = "ประเภท: เบอร์เติมเงิน";
          title.textContent = `ราคาขาย ${priceText} บาท`;
        } else {
          if (nameEl) nameEl.textContent = `ชื่อโปร: ${opt.dataset.promo || "-"}`;
          title.textContent = `โปรโมชั่นแพคเกจ ${priceText} บาท`;
        }
        dataEl.textContent = opt.dataset.data || "-";
        speedEl.textContent = opt.dataset.speed || "-";
        voiceEl.textContent = opt.dataset.voice || "-";
        entEl.textContent = opt.dataset.ent || "-";
        if (step2Title) step2Title.textContent = "การชำระเงิน";
        if (payCardTitle) payCardTitle.textContent = `ขั้นตอนที่ 2. ชำระเงิน ${initialPaymentText} บาท`;
        if (payCardNote) payCardNote.textContent = `โอนเงินจำนวน ${initialPaymentText} บาท แล้วแนบสลิปด้านล่าง`;
        if (summaryPackage) summaryPackage.textContent = `${initialPaymentText} บาท`;
        if (finalSummaryPackage) finalSummaryPackage.textContent = `${initialPaymentText} บาท`;
      };

      const clean = (value) => (value || "").toString().trim();
      const dash = (value) => (value ? value : "-");
      const phoneInput = form.elements.namedItem("current_phone");
      const prepaidRequiredFields = isPrepaid ? [
        { name: "title_prefix", emptyMessage: "กรุณาเลือกคำนำหน้าชื่อ" },
        { name: "first_name", emptyMessage: "กรุณากรอกชื่อ" },
        { name: "last_name", emptyMessage: "กรุณากรอกนามสกุล" },
        { name: "email", emptyMessage: "กรุณากรอกอีเมลล์" },
        {
          name: "current_phone",
          emptyMessage: "กรุณากรอกเบอร์มือถือปัจจุบัน",
          invalidMessage: "กรุณากรอกเบอร์มือถือปัจจุบันให้ครบ 10 หลัก",
          normalize: (value) => value.replace(/\D+/g, ""),
          isValid: (value) => value.length === 10,
        },
        { name: "shipping_address_line", emptyMessage: "กรุณากรอกที่อยู่ที่จัดส่ง" },
        { name: "district", emptyMessage: "กรุณากรอกตำบล / แขวง" },
        { name: "amphoe", emptyMessage: "กรุณากรอกอำเภอ / เขต" },
        { name: "province", emptyMessage: "กรุณากรอกจังหวัด" },
        {
          name: "zipcode",
          emptyMessage: "กรุณากรอกรหัสไปรษณีย์",
          invalidMessage: "กรุณากรอกรหัสไปรษณีย์ให้ครบ 5 หลัก",
          normalize: (value) => value.replace(/\D+/g, ""),
          isValid: (value) => value.length === 5,
        },
      ] : [];

      const copyBankAccount = async () => {
        const accountNo = clean(bankAccountNoEl?.textContent);
        if (!accountNo) return;
        let copied = false;
        try {
          await navigator.clipboard.writeText(accountNo);
          copied = true;
        } catch (_) {
          const ta = document.createElement("textarea");
          ta.value = accountNo;
          ta.setAttribute("readonly", "");
          ta.style.position = "fixed";
          ta.style.opacity = "0";
          document.body.appendChild(ta);
          ta.select();
          copied = document.execCommand("copy");
          document.body.removeChild(ta);
        }
        if (!copyAccountHint) return;
        copyAccountHint.textContent = copied ? "คัดลอกเลขบัญชีแล้ว" : "คัดลอกไม่สำเร็จ";
        window.setTimeout(() => {
          if (copyAccountHint) copyAccountHint.textContent = "";
        }, 1800);
      };

      const updateSlipClearButton = () => {
        if (!clearSlipBtn || !paymentSlipInput) return;
        const hasFile = Boolean(paymentSlipInput.files && paymentSlipInput.files.length > 0);
        clearSlipBtn.hidden = !hasFile;
        if (paymentSlipRequiredHint) paymentSlipRequiredHint.hidden = hasFile;
      };

      const clearPaymentSlip = () => {
        if (!paymentSlipInput) return;
        paymentSlipInput.value = "";
        updateSlipClearButton();
      };

      const getLocalDateText = (date) => {
        const tzOffset = date.getTimezoneOffset() * 60000;
        return new Date(date.getTime() - tzOffset).toISOString().slice(0, 10);
      };

      const refillTimeSlots = () => {
        if (!appointmentTimeSlotInput) return;
        const allSlots = [
          { value: "11:00-12:00", label: "11.00 - 12.00 น.", isAfternoon: false },
          { value: "12:00-13:00", label: "12.00 - 13.00 น.", isAfternoon: false },
          { value: "13:00-14:00", label: "13.00 - 14.00 น.", isAfternoon: true },
          { value: "14:00-15:00", label: "14.00 - 15.00 น.", isAfternoon: true },
          { value: "15:00-16:00", label: "15.00 - 16.00 น.", isAfternoon: true },
          { value: "16:00-17:00", label: "16.00 - 17.00 น.", isAfternoon: true },
          { value: "17:00-18:00", label: "17.00 - 18.00 น.", isAfternoon: true },
        ];

        const now = new Date();
        const isAfterNoon = now.getHours() >= 12;
        const todayText = getLocalDateText(now);
        const selectedDate = appointmentDateInput?.value || "";
        const shouldUseAfternoonOnly = !isAfterNoon && selectedDate === todayText;
        const selectedBefore = appointmentTimeSlotInput.value;
        const filteredSlots = shouldUseAfternoonOnly ? allSlots.filter((s) => s.isAfternoon) : allSlots;

        appointmentTimeSlotInput.innerHTML = '<option value="">เลือกช่วงเวลา</option>';
        filteredSlots.forEach((slot) => {
          const option = document.createElement("option");
          option.value = slot.value;
          option.textContent = slot.label;
          appointmentTimeSlotInput.appendChild(option);
        });

        const stillExists = filteredSlots.some((slot) => slot.value === selectedBefore);
        appointmentTimeSlotInput.value = stillExists ? selectedBefore : "";
      };

      const updateOrderSummary = () => {
        const orderedNumber = clean(form.elements.namedItem("ordered_number")?.value);
        const titlePrefix = clean(form.elements.namedItem("title_prefix")?.value);
        const firstName = clean(form.elements.namedItem("first_name")?.value);
        const lastName = clean(form.elements.namedItem("last_name")?.value);
        const phone = clean(form.elements.namedItem("current_phone")?.value);
        const email = clean(form.elements.namedItem("email")?.value);
        const line = clean(form.elements.namedItem("shipping_address_line")?.value);
        const district = clean(form.elements.namedItem("district")?.value);
        const amphoe = clean(form.elements.namedItem("amphoe")?.value);
        const province = clean(form.elements.namedItem("province")?.value);
        const zipcode = clean(form.elements.namedItem("zipcode")?.value);

        const fullName = [titlePrefix, firstName, lastName].filter(Boolean).join(" ");
        const address = [line, district, amphoe, province, zipcode].filter(Boolean).join(" ");

        if (summaryOrderedNumber) summaryOrderedNumber.textContent = dash(orderedNumber);
        if (summaryFullName) summaryFullName.textContent = dash(fullName);
        if (summaryPhone) summaryPhone.textContent = dash(phone);
        if (summaryEmail) summaryEmail.textContent = dash(email);
        if (summaryAddress) summaryAddress.textContent = dash(address);
        if (finalSummaryOrderedNumber) finalSummaryOrderedNumber.textContent = dash(orderedNumber);
        if (finalSummaryFullName) finalSummaryFullName.textContent = dash(fullName);
        if (finalSummaryPhone) finalSummaryPhone.textContent = dash(phone);
        if (finalSummaryEmail) finalSummaryEmail.textContent = dash(email);
        if (finalSummaryAddress) finalSummaryAddress.textContent = dash(address);
      };

      const enforceNumericPhone = () => {
        if (!phoneInput || phoneInput instanceof RadioNodeList) return;
        phoneInput.value = (phoneInput.value || "").replace(/\D+/g, "");
      };

      const enforceNumericZipcode = () => {
        if (!zipcodeInput) return;
        zipcodeInput.value = (zipcodeInput.value || "").replace(/\D+/g, "");
      };

      const clearPrepaidFieldValidity = (fieldName) => {
        if (!isPrepaid) return;
        const field = form.elements.namedItem(fieldName);
        if (!field || field instanceof RadioNodeList || typeof field.setCustomValidity !== "function") return;
        field.setCustomValidity("");
      };

      const normalizeAddressRecord = (item) => {
        if (!item || typeof item !== "object") return null;
        const district = item.district || item.tambon || item.district_name || item.t;
        const amphoe = item.amphoe || item.amphur || item.district_area || item.a;
        const province = item.province || item.changwat || item.province_name || item.p;
        const zipcode = item.zipcode || item.postcode || item.zip || item.z;
        if (!district || !amphoe || !province || !zipcode) return null;
        return {
          district: String(district).trim(),
          amphoe: String(amphoe).trim(),
          province: String(province).trim(),
          zipcode: String(zipcode).trim(),
        };
      };

      const loadFallbackAddressData = async () => {
        try {
          const response = await fetch("{{ asset('vendor/jquery-thailand/raw_database.json') }}", { cache: "no-store" });
          if (!response.ok) return;
          const payload = await response.json();
          if (!Array.isArray(payload)) return;
          const normalized = payload.map(normalizeAddressRecord).filter(Boolean);
          if (!normalized.length) return;
          fallbackAddressData = normalized;
          if (addressHelper) {
            addressHelper.textContent = "ใช้ฐานข้อมูลที่อยู่ทั้งประเทศ (โหมดโลคัล)";
          }
        } catch (_) {
        }
      };

      const initFallbackAutocomplete = () => {
        const uniqueRows = fallbackAddressData.filter((row) => row && row.district);

        const fillFromRecord = (recordIndex) => {
          const found = uniqueRows[recordIndex];
          if (!found) return;
          if (districtInput) districtInput.value = found.district;
          if (amphoeInput) amphoeInput.value = found.amphoe;
          if (provinceInput) provinceInput.value = found.province;
          if (zipcodeInput) zipcodeInput.value = found.zipcode;
          if (districtSuggest) districtSuggest.hidden = true;
        };

        const renderSuggestions = (keyword) => {
          if (!districtSuggest) return;
          const q = keyword.trim().toLowerCase();
          if (!q) {
            districtSuggest.hidden = true;
            districtSuggest.innerHTML = "";
            return;
          }
          const matches = uniqueRows
            .map((row, idx) => ({ idx, row }))
            .filter(({ row }) => (
              row.district.toLowerCase().includes(q)
              || row.amphoe.toLowerCase().includes(q)
              || row.province.toLowerCase().includes(q)
              || row.zipcode.includes(q)
            ))
            .slice(0, 8);
          if (!matches.length) {
            districtSuggest.hidden = true;
            districtSuggest.innerHTML = "";
            return;
          }
          districtSuggest.innerHTML = matches
            .map(({ idx, row }) => (
              `<button type="button" class="address-suggest__item" data-row-index="${idx}">`
              + `${row.district} / ${row.amphoe} / ${row.province} ${row.zipcode}`
              + "</button>"
            ))
            .join("");
          districtSuggest.hidden = false;
        };

        districtInput?.addEventListener("input", (e) => renderSuggestions(e.target.value));
        districtInput?.addEventListener("blur", () => {
          setTimeout(() => {
            if (districtSuggest) districtSuggest.hidden = true;
          }, 120);
          const keyword = districtInput?.value?.trim();
          if (!keyword) return;
          const exactIndex = uniqueRows.findIndex((row) => row.district === keyword);
          if (exactIndex >= 0) fillFromRecord(exactIndex);
        });
        districtSuggest?.addEventListener("click", (e) => {
          const target = e.target.closest(".address-suggest__item");
          if (!target) return;
          fillFromRecord(Number(target.dataset.rowIndex));
        });
      };

      let fallbackAutocompleteInited = false;
      let fallbackAutocompleteLoading = null;
      const ensureFallbackAutocomplete = () => {
        if (fallbackAutocompleteInited) return;
        if (fallbackAutocompleteLoading) return;
        fallbackAutocompleteLoading = Promise.resolve()
          .then(loadFallbackAddressData)
          .finally(() => {
            fallbackAutocompleteInited = true;
            initFallbackAutocomplete();
          });
      };

      let isSavingStep2 = false;
      const saveOrderAtStep2 = async () => {
        if (isSavingStep2) return false;
        const hasFile = Boolean(paymentSlipInput && paymentSlipInput.files && paymentSlipInput.files.length > 0);
        const alreadySaved = Boolean(clean(savedOrderIdInput?.value));
        if (!hasFile && !alreadySaved) {
          window.alert("โปรดแนบหลักฐานการโอนเงินเพื่อสั่งซื้อเบอร์");
          if (paymentSlipInput) paymentSlipInput.focus();
          return false;
        }

        isSavingStep2 = true;
        if (step2NextBtn) {
          step2NextBtn.disabled = true;
          step2NextBtn.textContent = "กำลังบันทึก...";
        }

        try {
          const formData = new FormData(form);
          const response = await fetch("{{ route('book.save-step2') }}", {
            method: "POST",
            headers: {
              "X-Requested-With": "XMLHttpRequest",
              "Accept": "application/json",
            },
            body: formData,
          });

          const payload = await response.json().catch(() => ({}));
          if (!response.ok || !payload?.ok) {
            const message = payload?.message || "ไม่สามารถบันทึกคำสั่งซื้อได้ กรุณาลองใหม่อีกครั้ง";
            window.alert(message);
            return false;
          }

          if (savedOrderIdInput && payload.order_id) {
            savedOrderIdInput.value = String(payload.order_id);
          }
          if (saveBanner) {
            saveBanner.textContent = "บันทึกคำสั่งซื้อของคุณแล้ว";
          }

          trackAnalyticsEvent(isPrepaid ? "purchase_request_submitted" : "add_payment_info", {
            service_type: isPrepaid ? "prepaid" : "postpaid",
            checkout_step: 2,
          });

          return true;
        } catch (_) {
          window.alert("ไม่สามารถเชื่อมต่อระบบบันทึกคำสั่งซื้อได้ กรุณาลองใหม่อีกครั้ง");
          return false;
        } finally {
          isSavingStep2 = false;
          if (step2NextBtn) {
            step2NextBtn.disabled = false;
            step2NextBtn.textContent = "บันทึกและไปขั้นตอนต่อไป";
          }
        }
      };

      const setStep = (step, pushToHistory = true) => {
        const nextStep = Math.min(3, Math.max(1, step));
        stepPanels.forEach((panel) => {
          const panelStep = Number(panel.dataset.stepPanel || 1);
          panel.classList.toggle("is-hidden", panelStep !== nextStep);
          panel.classList.toggle("is-active", panelStep === nextStep);
        });
        stepPills.forEach((pill) => {
          const pillStep = Number(pill.dataset.stepPill || 1);
          pill.classList.toggle("is-active", pillStep === nextStep);
        });
        if (pushToHistory) {
          history.pushState({ bookStep: nextStep }, "", `#step-${nextStep}`);
        }
        window.scrollTo({ top: 0, behavior: "smooth" });
      };

      const saveDraft = () => {
        const draft = {};
        Array.from(form.elements).forEach((el) => {
          if (!el.name || el.type === "file") return;
          draft[el.name] = el.value;
        });
        sessionStorage.setItem(storageKey, JSON.stringify(draft));
      };

      const restoreDraft = () => {
        const raw = sessionStorage.getItem(storageKey);
        if (!raw) return;
        try {
          const draft = JSON.parse(raw);
          Object.entries(draft).forEach(([name, value]) => {
            const el = form.elements.namedItem(name);
            if (!el || name === "ordered_number") return;
            if (el instanceof RadioNodeList) return;
            el.value = value;
          });
        } catch (_) {
        }
      };

      const validatePrepaidStepOne = (revealStepOne = false) => {
        if (!isPrepaid) return true;

        let firstInvalidField = null;
        prepaidRequiredFields.forEach((config) => {
          const field = form.elements.namedItem(config.name);
          if (!field || field instanceof RadioNodeList || typeof field.setCustomValidity !== "function") return;

          field.setCustomValidity("");
          const rawValue = clean(field.value);
          const normalizedValue = typeof config.normalize === "function" ? config.normalize(rawValue) : rawValue;

          let errorMessage = "";
          if (!normalizedValue) {
            errorMessage = config.emptyMessage;
          } else if (typeof config.isValid === "function" && !config.isValid(normalizedValue)) {
            errorMessage = config.invalidMessage || config.emptyMessage;
          }

          if (errorMessage !== "") {
            field.setCustomValidity(errorMessage);
            if (!firstInvalidField) {
              firstInvalidField = field;
            }
          }
        });

        if (!firstInvalidField) {
          return true;
        }

        if (revealStepOne) {
          setStep(1);
        }
        firstInvalidField.reportValidity();
        firstInvalidField.focus();
        return false;
      };

      goStepButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
          const target = Number(btn.dataset.goStep || 1);
          if (target === 2 && !validatePrepaidStepOne()) {
            return;
          }
          if (target === 3) {
            const hasFile = Boolean(paymentSlipInput && paymentSlipInput.files && paymentSlipInput.files.length > 0);
            if (!hasFile) {
              window.alert("โปรดแนบหลักฐานการโอนเงินเพื่อสั่งซื้อเบอร์");
              if (paymentSlipInput) paymentSlipInput.focus();
              return;
            }
          }
          saveDraft();
          setStep(target);
        });
      });

      step2NextBtn?.addEventListener("click", async () => {
        if (!validatePrepaidStepOne(true)) {
          return;
        }
        saveDraft();
        updateOrderSummary();
        const saved = await saveOrderAtStep2();
        if (saved) {
          setStep(3);
        }
      });

      form.addEventListener("submit", (event) => {
        if (!validatePrepaidStepOne(true)) {
          event.preventDefault();
          return;
        }
        const hasFile = Boolean(paymentSlipInput && paymentSlipInput.files && paymentSlipInput.files.length > 0);
        const alreadySaved = Boolean(clean(savedOrderIdInput?.value));
        if (!hasFile && !alreadySaved) {
          event.preventDefault();
          window.alert("โปรดแนบหลักฐานการโอนเงินเพื่อสั่งซื้อเบอร์");
          if (paymentSlipInput) paymentSlipInput.focus();
        }
      });

      form.addEventListener("input", saveDraft);
      form.addEventListener("change", saveDraft);
      form.addEventListener("input", updateOrderSummary);
      form.addEventListener("change", updateOrderSummary);
      phoneInput?.addEventListener("input", () => {
        enforceNumericPhone();
        saveDraft();
        updateOrderSummary();
      });
      phoneInput?.addEventListener("paste", () => {
        window.setTimeout(() => {
          enforceNumericPhone();
          saveDraft();
          updateOrderSummary();
        }, 0);
      });
      zipcodeInput?.addEventListener("input", () => {
        enforceNumericZipcode();
        clearPrepaidFieldValidity("zipcode");
        saveDraft();
        updateOrderSummary();
      });
      zipcodeInput?.addEventListener("paste", () => {
        window.setTimeout(() => {
          enforceNumericZipcode();
          clearPrepaidFieldValidity("zipcode");
          saveDraft();
          updateOrderSummary();
        }, 0);
      });
      prepaidRequiredFields.forEach(({ name }) => {
        const field = form.elements.namedItem(name);
        if (!field || field instanceof RadioNodeList) return;
        field.addEventListener("input", () => clearPrepaidFieldValidity(name));
        field.addEventListener("change", () => clearPrepaidFieldValidity(name));
      });
      window.addEventListener("popstate", (event) => {
        const next = Number(event.state?.bookStep || 1);
        setStep(next, false);
      });

      packageSelect.addEventListener("change", updatePreview);
      copyAccountBtn?.addEventListener("click", copyBankAccount);
      paymentSlipInput?.addEventListener("change", updateSlipClearButton);
      clearSlipBtn?.addEventListener("click", clearPaymentSlip);
      restoreDraft();
      if (appointmentDateInput) {
        const now = new Date();
        const isAfterNoon = now.getHours() >= 12;
        const minDate = new Date(now);
        if (isAfterNoon) {
          minDate.setDate(minDate.getDate() + 1);
        }
        const maxDate = new Date(now);
        maxDate.setDate(maxDate.getDate() + 3);
        appointmentDateInput.min = getLocalDateText(minDate);
        appointmentDateInput.max = getLocalDateText(maxDate);
        if (!appointmentDateInput.value || appointmentDateInput.value < appointmentDateInput.min) {
          appointmentDateInput.value = appointmentDateInput.min;
        } else if (appointmentDateInput.value > appointmentDateInput.max) {
          appointmentDateInput.value = appointmentDateInput.max;
        }
      }
      appointmentDateInput?.addEventListener("change", refillTimeSlots);
      refillTimeSlots();
      enforceNumericPhone();
      enforceNumericZipcode();
      updateSlipClearButton();
      updatePreview();
      updateOrderSummary();
      const hashStep = Number((location.hash.match(/step-(\d+)/) || [])[1] || 1);
      history.replaceState({ bookStep: hashStep }, "", location.hash || "#step-1");
      setStep(hashStep, false);

      window.addEventListener("load", () => {
        const hasSubmittedOrder = @json((bool) session('status_message'));

        if (!hasSubmittedOrder) {
          trackAnalyticsEvent("begin_checkout", {
            service_type: isPrepaid ? "prepaid" : "postpaid",
          });
        }

        @if (session('status_message') && ! $isPrepaid)
          trackAnalyticsEvent("purchase_request_submitted", {
            service_type: "postpaid",
            checkout_step: 3,
          });
        @endif
      }, { once: true });

      let fallbackTimer = null;
      if (window.jQuery && typeof window.jQuery.Thailand === "function") {
        fallbackTimer = window.setTimeout(() => {
          ensureFallbackAutocomplete();
        }, 1200);

        window.jQuery(function ($) {
          try {
            $.Thailand({
              $district: $("#th-district"),
              $amphoe: $("#th-amphoe"),
              $province: $("#th-province"),
              $zipcode: $("#th-zipcode"),
              database: "{{ asset('vendor/jquery-thailand/db.json') }}",
              onLoad: function () {
                if (fallbackTimer) window.clearTimeout(fallbackTimer);
                if (addressHelper) {
                  addressHelper.textContent = "ค้นหาที่อยู่พร้อมใช้งาน: พิมพ์ตำบล/เขต/จังหวัดได้เลย";
                }
              },
            });
          } catch (err) {
            if (fallbackTimer) window.clearTimeout(fallbackTimer);
            if (addressHelper) {
              addressHelper.textContent = "ใช้โหมดช่วยกรอกพื้นฐาน: พิมพ์ตำบล/แขวงเพื่อเติมเขต จังหวัด รหัสไปรษณีย์";
            }
            ensureFallbackAutocomplete();
          }
        });
      } else if (addressHelper) {
        addressHelper.textContent = "ใช้โหมดช่วยกรอกพื้นฐาน: พิมพ์ตำบล/แขวงเพื่อเติมเขต จังหวัด รหัสไปรษณีย์";
        ensureFallbackAutocomplete();
      } else {
        ensureFallbackAutocomplete();
      }
    })();
  </script>
@endsection
