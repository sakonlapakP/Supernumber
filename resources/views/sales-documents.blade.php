@extends('layouts.app')

@section('title', 'Supernumber | ใบเสนอราคาและใบแจ้งหนี้')
@section('meta_description', 'กรอกข้อมูลบนใบเสนอราคาและใบแจ้งหนี้ในหน้าตาเอกสารจริง พร้อมคำนวณยอดอัตโนมัติและพิมพ์เป็น PDF ได้ทันที')
@section('og_title', 'Supernumber | ใบเสนอราคาและใบแจ้งหนี้')
@section('og_description', 'สร้างใบเสนอราคาและใบแจ้งหนี้จากหน้าตา final document ที่ผู้ใช้เห็นจริง')
@section('canonical', url('/sales-documents'))
@section('og_url', url('/sales-documents'))
@section('body_class', 'document-studio-page')
@section('hide_header', '1')
@section('hide_footer', '1')

@section('content')
  @php
    $today = now('Asia/Bangkok');
    $documentDate = $today->format('Y-m-d');
    $dueDate = $today->copy()->addDays(7)->format('Y-m-d');
    $sequence = $today->format('ymd') . '-001';
    $customerRecords = ($customers ?? collect())->map(fn ($customer) => [
      'id' => $customer->id,
      'display_name' => $customer->display_name,
      'company_name' => $customer->company_name,
      'contact_name' => $customer->contact_name,
      'tax_id' => $customer->tax_id,
      'address' => $customer->address,
      'email' => $customer->email,
      'phone' => $customer->phone,
      'payment_term' => $customer->payment_term,
    ])->values();
    $isPrintPreview = request()->boolean('print_preview');
    $prefillPayload = $prefillPayload ?? null;
  @endphp

  @if ($isPrintPreview)
    <section class="document-print-route" data-print-route data-print-mode="{{ request('mode', 'print') }}">
      <div class="document-print-route__toolbar">
        <button type="button" class="document-action-button document-action-button--ghost" data-print-route-back>กลับไปแก้เอกสาร</button>
        <button type="button" class="document-action-button document-action-button--primary" data-print-route-print>
          {{ request('mode', 'print') === 'pdf' ? 'บันทึก PDF' : 'พิมพ์' }}
        </button>
      </div>
      <div class="document-print-route__status">กำลังเตรียมเอกสารสำหรับพิมพ์...</div>
      <div class="document-print-route__sheet" data-print-route-sheet hidden></div>
    </section>

    <script>
      (() => {
        const storageKey = "sales-document-print-preview-html";
        const titleKey = "sales-document-print-preview-title";
        const returnUrlKey = "sales-document-print-preview-return-url";
        const routeRoot = document.querySelector("[data-print-route]");
        const sheet = document.querySelector("[data-print-route-sheet]");
        const backButton = document.querySelector("[data-print-route-back]");
        const printButton = document.querySelector("[data-print-route-print]");
        const mode = routeRoot?.dataset.printMode || "print";

        if (! routeRoot || ! sheet) {
          return;
        }

        const html = window.localStorage.getItem(storageKey);
        const title = window.localStorage.getItem(titleKey) || "sales-document";

        if (! html) {
          routeRoot.innerHTML = '<div class="document-print-route__status document-print-route__status--error">ไม่พบข้อมูลเอกสารสำหรับพิมพ์ กรุณากลับไปหน้าเอกสารแล้วลองใหม่</div>';
          return;
        }

        document.title = title;
        sheet.innerHTML = html;
        sheet.hidden = false;
        routeRoot.querySelector(".document-print-route__status")?.remove();

        const returnToEditor = () => {
          const returnUrl = window.localStorage.getItem(returnUrlKey) || @json(route('admin.sales-documents'));
          window.location.href = returnUrl;
        };

        const runPrint = () => {
          window.print();
        };

        backButton?.addEventListener("click", returnToEditor);
        printButton?.addEventListener("click", runPrint);

        window.addEventListener("afterprint", () => {
          if (mode === "pdf") {
            window.localStorage.removeItem(storageKey);
            window.localStorage.removeItem(titleKey);
          }
        }, { once: true });

        window.setTimeout(runPrint, 250);
      })();
    </script>
  @else

  <section class="document-studio">
    <div class="document-studio__toolbar">
      <a class="document-studio__home" href="{{ route('home') }}" aria-label="กลับหน้าหลัก">
        <img src="{{ asset('images/supernumber-document-logo.png') }}" alt="Supernumber">
      </a>

      <div class="document-studio__controls">
        <label class="document-studio__customer-picker">
          <span>ลูกค้า</span>
          <select data-customer-select>
            <option value="">เลือกลูกค้า</option>
            @foreach (($customers ?? collect()) as $customer)
              <option value="{{ $customer->id }}">{{ $customer->display_name }}</option>
            @endforeach
          </select>
        </label>
        <button type="button" class="document-action-button document-action-button--save" data-customer-action>เพิ่มลูกค้า</button>
        <button type="button" class="document-action-button document-action-button--ghost" data-save-draft>บันทึกร่าง</button>
        <button type="button" class="document-action-button" data-load-draft hidden>โหลดร่าง</button>
        <div class="document-type-switch" role="group" aria-label="เลือกประเภทเอกสาร">
          <button type="button" class="is-active" data-doc-switch="quotation">ใบเสนอราคา</button>
          <button type="button" data-doc-switch="invoice">ใบแจ้งหนี้</button>
        </div>
        <button type="button" class="document-action-button document-action-button--primary" data-print-document aria-label="บันทึกและเปิดหน้าพิมพ์ PDF" title="บันทึกและเปิดหน้าพิมพ์ PDF">บันทึกและเปิด PDF</button>
      </div>
    </div>

    <div class="document-studio__status" data-document-status hidden></div>

    <div class="document-customer-dialog" data-customer-dialog hidden>
      <div class="document-customer-dialog__backdrop" data-customer-dialog-close></div>
      <div class="document-customer-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="document-customer-dialog-title">
        <div class="document-customer-dialog__header">
          <div>
            <strong id="document-customer-dialog-title" data-customer-dialog-title>เพิ่มลูกค้า</strong>
            <p data-customer-dialog-subtitle>บันทึกข้อมูลลูกค้าเพื่อเรียกใช้งานในใบเสนอราคาและใบแจ้งหนี้</p>
          </div>
          <button type="button" class="document-customer-dialog__close" data-customer-dialog-close aria-label="ปิดหน้าต่าง">ปิด</button>
        </div>

        <div class="document-customer-dialog__status" data-customer-dialog-status hidden></div>

        <div class="document-customer-dialog__body">
          <label class="document-customer-dialog__field">
            <span>ชื่อบริษัท / ชื่อลูกค้า</span>
            <input type="text" data-customer-modal-company-name placeholder="กรอกชื่อบริษัทหรือชื่อลูกค้า">
          </label>

          <label class="document-customer-dialog__field">
            <span>ผู้ติดต่อ</span>
            <input type="text" data-customer-modal-contact-name placeholder="กรอกชื่อผู้ติดต่อ">
          </label>

          <label class="document-customer-dialog__field">
            <span>เลขประจำตัวผู้เสียภาษี</span>
            <input type="text" data-customer-modal-tax-id placeholder="กรอกเลขประจำตัวผู้เสียภาษี">
          </label>

          <label class="document-customer-dialog__field document-customer-dialog__field--full">
            <span>ที่อยู่</span>
            <textarea rows="4" data-customer-modal-address placeholder="กรอกที่อยู่ลูกค้า"></textarea>
          </label>

          <label class="document-customer-dialog__field">
            <span>อีเมล</span>
            <input type="email" data-customer-modal-email placeholder="กรอกอีเมลลูกค้า">
          </label>

          <label class="document-customer-dialog__field">
            <span>เบอร์โทร</span>
            <input type="text" data-customer-modal-phone placeholder="กรอกเบอร์โทรลูกค้า">
          </label>
        </div>

        <div class="document-customer-dialog__actions">
          <button type="button" class="document-action-button document-action-button--ghost" data-customer-dialog-close>ยกเลิก</button>
          <button type="button" class="document-action-button document-action-button--save" data-customer-dialog-save>บันทึกลูกค้า</button>
        </div>
      </div>
    </div>

    <div class="document-studio__viewport">
      <article class="document-sheet" data-document-root data-document-type="quotation">
        <div class="document-sheet__watermark" aria-hidden="true"></div>

        <header class="document-header">
          <div class="document-header__brand">
            <div class="document-header__brand-mark">
              <img src="{{ asset('images/supernumber-document-logo.png') }}" alt="Supernumber">
            </div>

            <div class="document-header__company">
              <textarea class="doc-textarea doc-textarea--title" rows="2" data-company-name-th>บริษัท ซุปเปอร์นัมเบอร์ จำกัด (สำนักงานใหญ่)</textarea>
              <input class="doc-input doc-input--subtitle" value="SUPERNUMBER CO.,LTD." data-company-name-en>
              <textarea class="doc-textarea doc-textarea--company-address" rows="3" data-company-address>1418 ถนนพระรามที่ 4 แขวงคลองเตย เขตคลองเตย กรุงเทพมหานคร 10110
Tel. 096-323-2656 , 096-323-2665 E-Mail. superjimmy789@gmail.com</textarea>
            </div>
          </div>

          <div class="document-title-box">
            <div class="document-title-box__name" data-document-title-th>ใบเสนอราคา</div>
            <div class="document-title-box__name-en" data-document-title-en>Quotation</div>

            <label class="document-inline-field">
              <span>เลขประจำตัวผู้เสียภาษี / Tax ID</span>
              <input class="doc-input doc-input--align-right" value="0105557133568" data-company-tax-id>
            </label>
          </div>
        </header>

        <section class="document-party">
          <div class="document-party__customer">
            <label class="document-line-field">
              <span>ชื่อลูกค้า / Name.</span>
              <textarea class="doc-textarea doc-textarea--customer-name doc-input--strong" rows="2" data-editable-field data-customer-name placeholder="กรอกชื่อบริษัทหรือชื่อลูกค้า"></textarea>
            </label>

            <label class="document-line-field document-line-field--split">
              <span>เลขประจำตัวผู้เสียภาษี / Tax ID.</span>
              <input class="doc-input" data-editable-field data-customer-tax-id value="" placeholder="กรอกเลขประจำตัวผู้เสียภาษี">
            </label>

            <div class="document-address-stack">
              <span>ที่อยู่ / Address.</span>
              <textarea class="doc-textarea" rows="4" data-editable-field data-customer-address placeholder="กรอกที่อยู่ลูกค้า"></textarea>
            </div>
          </div>

          <div class="document-meta-grid">
            <label>
              <span>วันที่ / Date</span>
              <input type="date" class="doc-input" data-editable-field data-document-date value="{{ $documentDate }}">
            </label>

            <label>
              <span>เลขที่ / No.</span>
              <input class="doc-input" data-editable-field data-document-number data-document-autonumber="true" value="QT-{{ $sequence }}">
            </label>

            <label>
              <span>เลขที่อ้างอิง / Ref. No.</span>
              <input class="doc-input" data-editable-field data-reference-number value="" placeholder="กรอกเลขอ้างอิง">
            </label>

            <label>
              <span>ผู้ติดต่อ / Purchase By.</span>
              <input class="doc-input" data-editable-field data-customer-contact value="" placeholder="กรอกชื่อผู้ติดต่อ">
            </label>

            <label>
              <span>เงื่อนไขการชำระเงิน / Payment Term.</span>
              <input class="doc-input" data-editable-field data-customer-payment-term value="" placeholder="เช่น ชำระภายใน 7 วัน">
            </label>

            <label>
              <span>วันครบกำหนด / Due Date</span>
              <input type="date" class="doc-input" data-editable-field data-due-date value="{{ $dueDate }}">
            </label>
          </div>
        </section>

        <section class="document-items">
          <table class="document-items__table">
            <thead>
              <tr>
                <th class="document-items__item-head">ลำดับ<br><small>ITEM</small></th>
                <th>รายการสินค้า/บริการ<br><small>DESCRIPTION</small></th>
                <th>จำนวน<br><small>QUANTITY</small></th>
                <th>หน่วย<br><small>UNIT</small></th>
                <th>ราคา/หน่วย<br><small>UNIT/PRICE</small></th>
                <th>จำนวนเงิน<br><small>AMOUNT</small></th>
              </tr>
            </thead>
            <tbody>
              @for ($i = 0; $i < 9; $i++)
                <tr data-item-row>
                  <td class="document-item-row__index">{{ $i + 1 }}.</td>
                  <td class="document-item-row__description">
                    <textarea class="doc-textarea doc-textarea--item" rows="2" data-editable-field data-item-description placeholder="กรอกรายการสินค้า/บริการ"></textarea>
                  </td>
                  <td class="document-item-row__qty">
                    <input class="doc-input doc-input--align-right" data-editable-field data-item-qty value="" placeholder="0" inputmode="decimal">
                  </td>
                  <td class="document-item-row__unit">
                    <input class="doc-input doc-input--align-center" data-editable-field data-item-unit value="" placeholder="EA.">
                  </td>
                  <td class="document-item-row__price">
                    <input class="doc-input doc-input--align-right" data-editable-field data-item-unit-price value="" placeholder="0.00" inputmode="decimal">
                  </td>
                  <td class="document-item-row__amount">
                    <output data-item-amount>0.00</output>
                  </td>
                </tr>
              @endfor
            </tbody>
          </table>

          <div class="document-items__actions" data-item-actions>
            <button type="button" class="document-items__add-button" data-add-item-row aria-label="เพิ่มรายการ">
              +
            </button>
          </div>
        </section>

        <section class="document-summary">
          <div class="document-payment">
            <div class="document-payment__section-title">รายการรับชำระผ่าน</div>

            <div class="document-payment__checkboxes">
              <label><input type="checkbox" data-payment-cash> เงินสด</label>
              <label><input type="checkbox" data-payment-transfer> เงินโอน</label>
              <label><input type="checkbox" data-payment-cheque> เช็คธนาคาร</label>
            </div>

            <label class="document-line-field">
              <span>ธนาคาร / Bank</span>
              <input class="doc-input" data-editable-field data-payment-bank value="ธนาคารกสิกรไทย บจก. ซุปเปอร์นัมเบอร์">
            </label>

            <label class="document-line-field">
              <span>สาขา / Branch</span>
              <input class="doc-input" data-editable-field data-payment-branch value="จามจุรีสแควร์">
            </label>

            <label class="document-line-field">
              <span>เลขที่บัญชี</span>
              <input class="doc-input" data-editable-field data-payment-account value="0063701726">
            </label>

            <div class="document-baht-text">
              <span>จำนวนเงินตัวหนังสือ</span>
              <strong data-baht-text>ศูนย์บาทถ้วน</strong>
            </div>
          </div>

          <div class="document-totals">
            <div class="document-total-row">
              <span>รวมเป็นเงิน / Sub Total</span>
              <output data-subtotal>0.00</output>
            </div>

            <div class="document-total-row">
              <span>หัก ส่วนลด / Discount</span>
              <div class="document-total-row__value">
                <input class="doc-input doc-input--tiny doc-input--align-right" data-editable-field data-discount-rate value="0" inputmode="decimal">
                <span>%</span>
                <output data-discount-amount>0.00</output>
              </div>
            </div>

            <div class="document-total-row">
              <span>ยอดหลังหักส่วนลด / Total After Discount</span>
              <output data-after-discount>0.00</output>
            </div>

            <div class="document-total-row">
              <span>ภาษีมูลค่าเพิ่ม / Vat</span>
              <div class="document-total-row__value">
                <input class="doc-input doc-input--tiny doc-input--align-right" data-editable-field data-vat-rate value="7" inputmode="decimal">
                <span>%</span>
                <output data-vat-amount>0.00</output>
              </div>
            </div>

            <div class="document-total-row document-total-row--grand">
              <span>จำนวนเงินรวม / Grand Total</span>
              <output data-grand-total>0.00</output>
            </div>
          </div>
        </section>

        <section class="document-footer">
          <div class="document-signatures">
            <div class="document-signature-box">
              <div class="document-signature-box__line">
                <input class="doc-input doc-input--signature" data-editable-field data-approved-by value="">
              </div>
              <div class="document-signature-box__meta">ผู้อนุมัติ / Approved by</div>

              <label class="document-signature-box__date">
                <span>วันที่ / Date</span>
                <input type="date" class="doc-input doc-input--align-center" data-editable-field data-approved-date value="{{ $documentDate }}">
              </label>
            </div>

            <div class="document-signature-box">
              <div class="document-signature-box__line">
                <input class="doc-input doc-input--signature" data-editable-field data-accepted-by placeholder="ลงชื่อผู้ยืนยัน">
              </div>
              <div class="document-signature-box__meta">ผู้รับใบเสนอ/ผู้รับเอกสาร / Accepted by</div>

              <label class="document-signature-box__date">
                <span>วันที่ / Date</span>
                <input type="date" class="doc-input doc-input--align-center" data-editable-field data-accepted-date value="{{ $documentDate }}">
              </label>
            </div>
          </div>

          <div class="document-balance-box">
            <div class="document-total-row">
              <span>ภาษีหัก ณ ที่จ่าย (บาท) / Withheld Tax</span>
              <div class="document-total-row__value">
                <input class="doc-input doc-input--tiny doc-input--align-right" data-editable-field data-withholding-rate value="3" inputmode="decimal">
                <span>%</span>
                <output data-withholding-amount>0.00</output>
              </div>
            </div>

            <div class="document-total-row document-total-row--net">
              <span>จำนวนเงินที่ต้องชำระ (บาท) / Net to Pay</span>
              <output data-net-to-pay>0.00</output>
            </div>
          </div>
        </section>
      </article>
    </div>

    <div class="document-studio__settings">
      <div class="document-calculator">
        <div class="document-calculator__groups">
          <div class="document-calculator__modes" role="group" aria-label="วิธีคิดภาษีหัก ณ ที่จ่าย">
            <span class="document-calculator__label">ภาษีหัก ณ ที่จ่าย</span>
            <button type="button" class="is-active" data-withholding-calc-mode="customer">ลูกค้ารับผิดชอบภาษีหัก ณ ที่จ่าย</button>
            <button type="button" data-withholding-calc-mode="company">เรารับผิดชอบภาษีหัก ณ ที่จ่าย</button>
          </div>

          <div class="document-calculator__modes" role="group" aria-label="วิธีคิดภาษีมูลค่าเพิ่ม">
            <span class="document-calculator__label">ภาษีมูลค่าเพิ่ม</span>
            <button type="button" data-vat-calc-mode="customer">ลูกค้ารับผิดชอบภาษีมูลค่าเพิ่ม</button>
            <button type="button" class="is-active" data-vat-calc-mode="company">เรารับผิดชอบภาษีมูลค่าเพิ่ม</button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <script>
    (() => {
      const customers = @json($customerRecords);
      const root = document.querySelector("[data-document-root]");

      if (! root) {
        return;
      }

      const titles = {
        quotation: {
          th: "ใบเสนอราคา",
          en: "Quotation",
          prefix: "QT",
        },
        invoice: {
          th: "ใบแจ้งหนี้",
          en: "Invoice",
          prefix: "IV",
        },
      };

      const typeButtons = document.querySelectorAll("[data-doc-switch]");
      const documentDateInput = root.querySelector("[data-document-date]");
      const documentNumberInput = root.querySelector("[data-document-number]");
      const titleTh = root.querySelector("[data-document-title-th]");
      const titleEn = root.querySelector("[data-document-title-en]");
      const companyNameThInput = root.querySelector("[data-company-name-th]");
      const companyNameEnInput = root.querySelector("[data-company-name-en]");
      const companyAddressInput = root.querySelector("[data-company-address]");
      const companyTaxIdInput = root.querySelector("[data-company-tax-id]");
      const subtotalOutput = root.querySelector("[data-subtotal]");
      const discountAmountOutput = root.querySelector("[data-discount-amount]");
      const afterDiscountOutput = root.querySelector("[data-after-discount]");
      const vatAmountOutput = root.querySelector("[data-vat-amount]");
      const grandTotalOutput = root.querySelector("[data-grand-total]");
      const withholdingAmountOutput = root.querySelector("[data-withholding-amount]");
      const netToPayOutput = root.querySelector("[data-net-to-pay]");
      const bahtTextOutput = root.querySelector("[data-baht-text]");
      const withholdingCalculatorModeButtons = document.querySelectorAll("[data-withholding-calc-mode]");
      const vatCalculatorModeButtons = document.querySelectorAll("[data-vat-calc-mode]");
      const printButton = document.querySelector("[data-print-document]");
      const itemRows = Array.from(root.querySelectorAll("[data-item-row]"));
      const addItemRowButton = root.querySelector("[data-add-item-row]");
      const itemActions = root.querySelector("[data-item-actions]");
      const customerSelect = document.querySelector("[data-customer-select]");
      const customerNameInput = root.querySelector("[data-customer-name]");
      const customerTaxIdInput = root.querySelector("[data-customer-tax-id]");
      const customerAddressInput = root.querySelector("[data-customer-address]");
      const customerContactInput = root.querySelector("[data-customer-contact]");
      const customerPaymentTermInput = root.querySelector("[data-customer-payment-term]");
      const referenceNumberInput = root.querySelector("[data-reference-number]");
      const dueDateInput = root.querySelector("[data-due-date]");
      const paymentCashInput = root.querySelector("[data-payment-cash]");
      const paymentTransferInput = root.querySelector("[data-payment-transfer]");
      const paymentChequeInput = root.querySelector("[data-payment-cheque]");
      const paymentBankInput = root.querySelector("[data-payment-bank]");
      const paymentBranchInput = root.querySelector("[data-payment-branch]");
      const paymentAccountInput = root.querySelector("[data-payment-account]");
      const approvedByInput = root.querySelector("[data-approved-by]");
      const approvedDateInput = root.querySelector("[data-approved-date]");
      const acceptedByInput = root.querySelector("[data-accepted-by]");
      const acceptedDateInput = root.querySelector("[data-accepted-date]");
      const customerActionButton = document.querySelector("[data-customer-action]");
      const statusBanner = document.querySelector("[data-document-status]");
      const customerDialog = document.querySelector("[data-customer-dialog]");
      const customerDialogTitle = document.querySelector("[data-customer-dialog-title]");
      const customerDialogSubtitle = document.querySelector("[data-customer-dialog-subtitle]");
      const customerDialogStatus = document.querySelector("[data-customer-dialog-status]");
      const customerDialogSaveButton = document.querySelector("[data-customer-dialog-save]");
      const customerDialogCloseButtons = document.querySelectorAll("[data-customer-dialog-close]");
      const saveDraftButton = document.querySelector("[data-save-draft]");
      const loadDraftButton = document.querySelector("[data-load-draft]");
      const customerModalCompanyName = document.querySelector("[data-customer-modal-company-name]");
      const customerModalContactName = document.querySelector("[data-customer-modal-contact-name]");
      const customerModalTaxId = document.querySelector("[data-customer-modal-tax-id]");
      const customerModalAddress = document.querySelector("[data-customer-modal-address]");
      const customerModalEmail = document.querySelector("[data-customer-modal-email]");
      const customerModalPhone = document.querySelector("[data-customer-modal-phone]");

      const numericFields = root.querySelectorAll(
        "[data-item-qty], [data-item-unit-price], [data-discount-rate], [data-vat-rate], [data-withholding-rate]"
      );

      const moneyFormatter = new Intl.NumberFormat("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });

      const quantityFormatter = new Intl.NumberFormat("en-US", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
      });

      const parseNumber = (value) => {
        const normalized = String(value ?? "").replace(/,/g, "").replace(/[^\d.-]/g, "");
        const parsed = Number.parseFloat(normalized);

        return Number.isFinite(parsed) ? parsed : 0;
      };

      const normalizeCompanyAddressValue = (value) => String(value ?? "")
        .replace(/\n?1418 Rama IV Road, Khlong Toei, Khlong Toei, Bangkok 10110/g, "")
        .replace(/\n{3,}/g, "\n\n")
        .trim();

      const sanitizeNumericInputValue = (value, allowDecimal = true) => {
        let sanitized = String(value ?? "").replace(/[^\d.]/g, "");

        if (! allowDecimal) {
          return sanitized;
        }

        const firstDotIndex = sanitized.indexOf(".");

        if (firstDotIndex === -1) {
          return sanitized;
        }

        const beforeDot = sanitized.slice(0, firstDotIndex + 1);
        const afterDot = sanitized.slice(firstDotIndex + 1).replace(/\./g, "");

        return beforeDot + afterDot;
      };
      const formatMoney = (value) => moneyFormatter.format(value);
      const formatQuantity = (value) => quantityFormatter.format(value);
      const roundMoney = (value) => Math.round((value + Number.EPSILON) * 100) / 100;
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || @json(csrf_token());
      const printStorageKey = "sales-document-print-preview-html";
      const printTitleKey = "sales-document-print-preview-title";
      const printReturnUrlKey = "sales-document-print-preview-return-url";
      const draftStorageKey = "sales-document-editor-draft";
      const returnStateStorageKey = "sales-document-editor-return-state";
      const draftStorageVersion = 2;
      const saveDownloadRoute = @json(route('admin.sales-documents.save-download'));
      const customerQuickUpdateRouteTemplate = @json(route('admin.customers.quick-update', ['customer' => '__CUSTOMER__']));
      const prefillPayload = @json($prefillPayload);
      let withholdingCalculatorMode = "customer";
      let vatCalculatorMode = "company";
      let customerDialogMode = "create";
      let editingCustomerId = null;

      const withholdingCalculatorModeMeta = {
        customer: {
          responsibility: "customer",
          label: "ลูกค้ารับผิดชอบภาษีหัก ณ ที่จ่าย",
          successMessage: "คำนวณแบบลูกค้ารับผิดชอบภาษีหัก ณ ที่จ่ายเรียบร้อยแล้ว",
        },
        company: {
          responsibility: "company",
          label: "เรารับผิดชอบภาษีหัก ณ ที่จ่าย",
          successMessage: "คำนวณแบบเรารับผิดชอบภาษีหัก ณ ที่จ่ายเรียบร้อยแล้ว",
        },
      };

      const vatCalculatorModeMeta = {
        customer: {
          responsibility: "customer",
          label: "ลูกค้ารับผิดชอบภาษีมูลค่าเพิ่ม",
          successMessage: "คำนวณแบบลูกค้ารับผิดชอบภาษีมูลค่าเพิ่มเรียบร้อยแล้ว",
        },
        company: {
          responsibility: "company",
          label: "เรารับผิดชอบภาษีมูลค่าเพิ่ม",
          successMessage: "คำนวณแบบเรารับผิดชอบภาษีมูลค่าเพิ่มเรียบร้อยแล้ว",
        },
      };

      const resolveWithholdingCalculatorMode = (mode) => (
        mode === "company" || mode === "post-vat-net" ? "company" : "customer"
      );
      const resolveVatCalculatorMode = (mode) => mode === "customer" ? "customer" : "company";
      const resolveLegacyCalculatorMode = (mode) => (
        resolveWithholdingCalculatorMode(mode) === "company" ? "post-vat-net" : "normal"
      );
      const currentWithholdingCalculatorModeMeta = () => (
        withholdingCalculatorModeMeta[resolveWithholdingCalculatorMode(withholdingCalculatorMode)]
        || withholdingCalculatorModeMeta.customer
      );
      const currentVatCalculatorModeMeta = () => (
        vatCalculatorModeMeta[resolveVatCalculatorMode(vatCalculatorMode)]
        || vatCalculatorModeMeta.company
      );
      const isCompanyWithholdingMode = () => resolveWithholdingCalculatorMode(withholdingCalculatorMode) === "company";
      const isCustomerVatMode = () => resolveVatCalculatorMode(vatCalculatorMode) === "customer";

      const twoDigit = (value) => String(value).padStart(2, "0");

      const formatDateForDocument = (value) => {
        if (String(value || "").trim() === "") {
          return "";
        }

        const date = new Date(String(value) + "T00:00:00");

        if (Number.isNaN(date.getTime())) {
          return String(value);
        }

        return [
          twoDigit(date.getDate()),
          twoDigit(date.getMonth() + 1),
          date.getFullYear(),
        ].join("/");
      };

      const generateDocumentNumber = () => {
        const currentType = root.dataset.documentType || "quotation";
        const config = titles[currentType];
        const dateValue = documentDateInput?.value || "";
        const date = dateValue !== "" ? new Date(dateValue + "T00:00:00") : new Date();
        const year = String(date.getFullYear()).slice(-2);
        const month = twoDigit(date.getMonth() + 1);
        const day = twoDigit(date.getDate());

        return config.prefix + "-" + year + month + day + "-001";
      };

      const setDocumentType = (type) => {
        const config = titles[type] || titles.quotation;

        root.dataset.documentType = type;
        titleTh.textContent = config.th;
        titleEn.textContent = config.en;

        typeButtons.forEach((button) => {
          button.classList.toggle("is-active", button.dataset.docSwitch === type);
        });

        if (documentNumberInput?.dataset.documentAutonumber === "true") {
          documentNumberInput.value = generateDocumentNumber();
        }
      };

      const buildDocumentFilename = () => {
        const currentType = root.dataset.documentType || "quotation";
        const number = String(documentNumberInput?.value || "").trim() || generateDocumentNumber();
        const sanitizedNumber = number.replace(/[\\/:*?"<>|]+/g, "-").trim();
        const label = currentType === "invoice" ? "Invoice" : "Quotation";

        return label + " " + sanitizedNumber;
      };

      const getRateValue = (selector) => parseNumber(root.querySelector(selector)?.value);

      const getPricingAdjustmentFactor = () => {
        const discountRate = getRateValue("[data-discount-rate]");
        const withholdingRate = getRateValue("[data-withholding-rate]");
        const discountMultiplier = 1 - (discountRate / 100);
        const withholdingMultiplier = 1 - (withholdingRate / 100);
        const combined = discountMultiplier * withholdingMultiplier;

        return combined > 0 ? combined : 1;
      };

      const storeRowBaseUnitPrice = (row, displayedPrice = null) => {
        const priceInput = row.querySelector("[data-item-unit-price]");

        if (! priceInput) {
          return;
        }

        const currentDisplayedPrice = displayedPrice === null ? parseNumber(priceInput.value) : displayedPrice;

        if (String(priceInput.value || "").trim() === "" && currentDisplayedPrice === 0) {
          delete row.dataset.baseUnitPrice;
          return;
        }

        row.dataset.baseUnitPrice = String(roundMoney(currentDisplayedPrice));
      };

      const syncUnitPricesForMode = () => {
        const factor = getPricingAdjustmentFactor();
        const shouldGrossUpUnitPrices = isCompanyWithholdingMode();

        itemRows.forEach((row) => {
          const priceInput = row.querySelector("[data-item-unit-price]");

          if (! priceInput) {
            return;
          }

          if (! row.dataset.baseUnitPrice) {
            const currentValue = parseNumber(priceInput.value);

            if (String(priceInput.value || "").trim() === "" && currentValue === 0) {
              return;
            }

            row.dataset.baseUnitPrice = String(roundMoney(currentValue));
          }

          const baseUnitPrice = parseNumber(row.dataset.baseUnitPrice);

          if (baseUnitPrice === 0 && String(priceInput.value || "").trim() === "") {
            return;
          }

          const displayedPrice = shouldGrossUpUnitPrices
            ? roundMoney(baseUnitPrice / factor)
            : roundMoney(baseUnitPrice);

          priceInput.value = formatMoney(displayedPrice);
        });
      };

      const buildPrintableSheet = () => {
        const clone = root.cloneNode(true);

        clone.querySelectorAll("[data-item-row]").forEach((row, index) => {
          if (itemRows[index]?.hidden) {
            row.remove();
          }
        });

        clone.querySelectorAll("textarea, input, select, output").forEach((field) => {
          const tagName = field.tagName.toLowerCase();
          const isTextarea = tagName === "textarea";
          const isOutput = tagName === "output";
          const isSelect = tagName === "select";
          const inputType = tagName === "input" ? (field.getAttribute("type") || "text").toLowerCase() : "";
          let value = "";

          if (isOutput) {
            value = field.textContent || "";
          } else if (isSelect) {
            const selectedOption = field.options?.[field.selectedIndex];
            value = selectedOption ? selectedOption.textContent || "" : "";
          } else if (inputType === "checkbox") {
            value = field.checked ? "☑" : "☐";
          } else if (inputType === "date") {
            value = formatDateForDocument(field.value);
          } else {
            value = field.value || "";
          }

          const replacement = document.createElement("span");
          replacement.className = field.className + " print-value";
          const hasValue = String(value).trim() !== "";

          if (inputType === "checkbox") {
            replacement.classList.add("print-checkbox");
          }

          if (! hasValue) {
            replacement.classList.add("print-value--empty");
          }

          if (isTextarea) {
            replacement.classList.add("print-value--multiline");
            replacement.textContent = hasValue ? value : "\u00A0";
          } else {
            replacement.textContent = hasValue ? value : "\u00A0";
          }

          field.replaceWith(replacement);
        });

        return clone;
      };

      const buildDocumentPayload = () => {
        const currentType = root.dataset.documentType || "quotation";
        const selectedCustomer = findSelectedCustomer();
        const items = itemRows.map((row, index) => {
          const descriptionInput = row.querySelector("[data-item-description]");
          const quantityInput = row.querySelector("[data-item-qty]");
          const unitInput = row.querySelector("[data-item-unit]");
          const unitPriceInput = row.querySelector("[data-item-unit-price]");
          const amountOutput = row.querySelector("[data-item-amount]");

          return {
            index: index + 1,
            description: descriptionInput?.value || "",
            quantity: parseNumber(quantityInput?.value),
            quantity_display: quantityInput?.value || "",
            unit: unitInput?.value || "",
            unit_price: parseNumber(unitPriceInput?.value),
            unit_price_display: unitPriceInput?.value || "",
            amount: parseNumber(amountOutput?.textContent),
            amount_display: amountOutput?.textContent || "0.00",
          };
        });

        return {
          document_type: currentType,
          document_number: documentNumberInput?.value || generateDocumentNumber(),
          document_date: documentDateInput?.value || "",
          due_date: dueDateInput?.value || "",
          customer_id: selectedCustomer?.id || null,
          customer_name: customerNameInput?.value || "",
          calculator_mode: resolveLegacyCalculatorMode(withholdingCalculatorMode),
          calculator_mode_label: currentWithholdingCalculatorModeMeta().label,
          withholding_calculator_mode: resolveWithholdingCalculatorMode(withholdingCalculatorMode),
          withholding_calculator_mode_label: currentWithholdingCalculatorModeMeta().label,
          withholding_tax_responsibility: currentWithholdingCalculatorModeMeta().responsibility,
          vat_calculator_mode: resolveVatCalculatorMode(vatCalculatorMode),
          vat_calculator_mode_label: currentVatCalculatorModeMeta().label,
          vat_tax_responsibility: currentVatCalculatorModeMeta().responsibility,
          company: {
            name_th: companyNameThInput?.value || "",
            name_en: companyNameEnInput?.value || "",
            address: companyAddressInput?.value || "",
            tax_id: companyTaxIdInput?.value || "",
          },
          document: {
            type: currentType,
            title_th: titleTh?.textContent || "",
            title_en: titleEn?.textContent || "",
            number: documentNumberInput?.value || generateDocumentNumber(),
            date: documentDateInput?.value || "",
            date_display: formatDateForDocument(documentDateInput?.value || ""),
            reference_number: referenceNumberInput?.value || "",
            due_date: dueDateInput?.value || "",
            due_date_display: formatDateForDocument(dueDateInput?.value || ""),
          },
          customer: {
            customer_id: selectedCustomer?.id || null,
            name: customerNameInput?.value || "",
            tax_id: customerTaxIdInput?.value || "",
            address: customerAddressInput?.value || "",
            contact: customerContactInput?.value || "",
            payment_term: customerPaymentTermInput?.value || "",
          },
          items,
          payment: {
            cash: Boolean(paymentCashInput?.checked),
            transfer: Boolean(paymentTransferInput?.checked),
            cheque: Boolean(paymentChequeInput?.checked),
            bank: paymentBankInput?.value || "",
            branch: paymentBranchInput?.value || "",
            account_number: paymentAccountInput?.value || "",
          },
          totals: {
            subtotal: parseNumber(subtotalOutput?.textContent),
            subtotal_display: subtotalOutput?.textContent || "0.00",
            discount_rate: getRateValue("[data-discount-rate]"),
            discount_rate_display: root.querySelector("[data-discount-rate]")?.value || "0.00",
            discount_amount: parseNumber(discountAmountOutput?.textContent),
            discount_amount_display: discountAmountOutput?.textContent || "0.00",
            after_discount: parseNumber(afterDiscountOutput?.textContent),
            after_discount_display: afterDiscountOutput?.textContent || "0.00",
            vat_rate: getRateValue("[data-vat-rate]"),
            vat_rate_display: root.querySelector("[data-vat-rate]")?.value || "7.00",
            vat_amount: parseNumber(vatAmountOutput?.textContent),
            vat_amount_display: vatAmountOutput?.textContent || "0.00",
            grand_total: parseNumber(grandTotalOutput?.textContent),
            grand_total_display: grandTotalOutput?.textContent || "0.00",
            withholding_rate: getRateValue("[data-withholding-rate]"),
            withholding_rate_display: root.querySelector("[data-withholding-rate]")?.value || "3.00",
            withholding_amount: parseNumber(withholdingAmountOutput?.textContent),
            withholding_amount_display: withholdingAmountOutput?.textContent || "0.00",
            net_to_pay: parseNumber(netToPayOutput?.textContent),
            net_to_pay_display: netToPayOutput?.textContent || "0.00",
            baht_text: bahtTextOutput?.textContent || "ศูนย์บาทถ้วน",
          },
          signatures: {
            approved_by: approvedByInput?.value || "",
            approved_date: approvedDateInput?.value || "",
            approved_date_display: formatDateForDocument(approvedDateInput?.value || ""),
            accepted_by: acceptedByInput?.value || "",
            accepted_date: acceptedDateInput?.value || "",
            accepted_date_display: formatDateForDocument(acceptedDateInput?.value || ""),
          },
        };
      };

      const openSavedDocumentDownload = (url, targetWindow = null) => {
        if (targetWindow && ! targetWindow.closed) {
          targetWindow.location.href = url;
          return;
        }

        window.location.href = url;
      };

      const clearValidationState = () => {
        root.querySelectorAll(".is-invalid").forEach((element) => {
          element.classList.remove("is-invalid");
        });
      };

      const markInvalidField = (field) => {
        if (! field) {
          return;
        }

        field.classList.add("is-invalid");
      };

      const focusInvalidField = (field) => {
        if (! field) {
          return;
        }

        field.scrollIntoView({
          behavior: "smooth",
          block: "center",
        });

        if (typeof field.focus === "function") {
          window.setTimeout(() => field.focus(), 120);
        }
      };

      const validateDocumentBeforeSave = (payload) => {
        clearValidationState();

        const currentType = payload.document_type || "quotation";
        const visibleItems = itemRows.filter((row) => ! row.hidden);
        const completedItems = visibleItems.filter((row) => {
          const descriptionInput = row.querySelector("[data-item-description]");
          const quantityInput = row.querySelector("[data-item-qty]");
          const unitPriceInput = row.querySelector("[data-item-unit-price]");

          return String(descriptionInput?.value || "").trim() !== ""
            && parseNumber(quantityInput?.value) > 0
            && parseNumber(unitPriceInput?.value) > 0;
        });

        const findFirstInvalidItemField = () => {
          for (const row of visibleItems) {
            const descriptionInput = row.querySelector("[data-item-description]");
            const quantityInput = row.querySelector("[data-item-qty]");
            const unitPriceInput = row.querySelector("[data-item-unit-price]");
            const hasDescription = String(descriptionInput?.value || "").trim() !== "";
            const quantity = parseNumber(quantityInput?.value);
            const unitPrice = parseNumber(unitPriceInput?.value);
            const hasAnyValue = hasDescription
              || String(quantityInput?.value || "").trim() !== ""
              || String(unitPriceInput?.value || "").trim() !== "";

            if (! hasAnyValue) {
              continue;
            }

            if (! hasDescription) {
              return {
                field: descriptionInput,
                message: "กรุณากรอกรายการสินค้า/บริการให้ครบ",
              };
            }

            if (quantity <= 0) {
              return {
                field: quantityInput,
                message: "กรุณากรอกจำนวนของรายการให้มากกว่า 0",
              };
            }

            if (unitPrice <= 0) {
              return {
                field: unitPriceInput,
                message: "กรุณากรอกราคาต่อหน่วยของรายการให้มากกว่า 0",
              };
            }
          }

          return {
            field: visibleItems[0]?.querySelector("[data-item-description]") || null,
            message: "กรุณากรอกรายการสินค้า/บริการอย่างน้อย 1 รายการ",
          };
        };

        const firstInvalidItem = findFirstInvalidItemField();

        const checks = [
          {
            valid: String(payload.document_number || "").trim() !== "",
            field: documentNumberInput,
            message: "กรุณากรอกเลขที่เอกสารก่อนบันทึก",
          },
          {
            valid: String(payload.document_date || "").trim() !== "",
            field: documentDateInput,
            message: "กรุณาเลือกวันที่เอกสารก่อนบันทึก",
          },
          {
            valid: String(payload.customer_name || "").trim() !== "",
            field: customerNameInput,
            message: "กรุณากรอกชื่อลูกค้าก่อนบันทึก",
          },
          {
            valid: completedItems.length > 0,
            field: firstInvalidItem.field,
            message: firstInvalidItem.message,
          },
          {
            valid: parseNumber(payload.totals?.grand_total_display) > 0,
            field: firstInvalidItem.field || visibleItems[0]?.querySelector("[data-item-unit-price]") || null,
            message: "ยอดรวมเอกสารต้องมากกว่า 0 ก่อนบันทึก",
          },
        ];

        if (currentType === "invoice") {
          checks.push(
            {
              valid: String(payload.customer?.address || "").trim() !== "",
              field: customerAddressInput,
              message: "กรุณากรอกที่อยู่ลูกค้าก่อนบันทึกใบแจ้งหนี้",
            },
            {
              valid: String(payload.due_date || "").trim() !== "",
              field: dueDateInput,
              message: "กรุณาเลือกวันครบกำหนดก่อนบันทึกใบแจ้งหนี้",
            }
          );
        }

        const failed = checks.find((check) => ! check.valid);

        if (! failed) {
          return null;
        }

        markInvalidField(failed.field);
        focusInvalidField(failed.field);

        return failed.message;
      };

      const saveAndDownloadDocument = async () => {
        if (! printButton) {
          return;
        }

        const payload = buildDocumentPayload();
        const validationMessage = validateDocumentBeforeSave(payload);

        if (validationMessage) {
          showStatus(validationMessage, "error");
          return;
        }

        storeReturnState();

        printButton.disabled = true;
        const printWindow = window.open("", "_blank");

        try {
          showStatus("กำลังบันทึกเอกสารและเตรียมหน้าพิมพ์ PDF...", "success");

          const response = await fetch(saveDownloadRoute, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "Accept": "application/json",
              "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({
              document_type: payload.document_type,
              document_number: payload.document_number,
              document_date: payload.document_date,
              due_date: payload.due_date,
              customer_id: payload.customer_id,
              customer_name: payload.customer_name,
              payload,
            }),
          });

          const responsePayload = await response.json();

          if (! response.ok) {
            throw new Error(responsePayload.message || "ไม่สามารถบันทึกเอกสารได้");
          }

          openSavedDocumentDownload(responsePayload.download_url, printWindow);
          showStatus(responsePayload.message || "บันทึกเอกสารเรียบร้อยแล้ว", "success");
        } catch (error) {
          if (printWindow && ! printWindow.closed) {
            printWindow.close();
          }

          showStatus(error instanceof Error ? error.message : "ไม่สามารถบันทึกเอกสารได้", "error");
        } finally {
          printButton.disabled = false;
        }
      };

      const captureDraftState = () => ({
        documentType: root.dataset.documentType || "quotation",
        withholdingCalculatorMode,
        vatCalculatorMode,
        customerSelectValue: customerSelect?.value || "",
        documentNumberAutonumber: documentNumberInput?.dataset.documentAutonumber || "true",
        fields: Array.from(root.querySelectorAll("input, textarea, select")).map((field) => ({
          key: field.getAttribute("data-document-field") || field.getAttribute("data-item-field") || field.getAttribute("data-item-row") || field.name || field.dataset.customerName || field.dataset.customerTaxId || field.dataset.customerAddress || field.dataset.customerContact || field.dataset.customerPaymentTerm || field.dataset.documentDate || field.dataset.documentNumber || "",
          type: field.type || field.tagName.toLowerCase(),
          value: field.type === "checkbox" ? field.checked : field.value,
        })),
        itemRows: itemRows.map((row, index) => ({
          index,
          hidden: row.hidden,
          baseUnitPrice: row.dataset.baseUnitPrice || "",
        })),
      });

      const applyDraftState = (state) => {
        if (! state || typeof state !== "object") {
          return false;
        }

        if (state.documentType) {
          setDocumentType(state.documentType);
        }

        if (documentNumberInput && state.documentNumberAutonumber) {
          documentNumberInput.dataset.documentAutonumber = state.documentNumberAutonumber;
        }

        if (Array.isArray(state.fields)) {
          const allFields = Array.from(root.querySelectorAll("input, textarea, select"));

          state.fields.forEach((savedField, index) => {
            const field = allFields[index];

            if (! field) {
              return;
            }

            if ((savedField.type || "").toLowerCase() === "checkbox") {
              field.checked = Boolean(savedField.value);
            } else {
              field.value = savedField.value ?? "";
            }
          });
        }

        if (customerSelect && typeof state.customerSelectValue === "string") {
          customerSelect.value = state.customerSelectValue;
        }

        if (Array.isArray(state.itemRows)) {
          state.itemRows.forEach((savedRow) => {
            const row = itemRows[savedRow.index];

            if (! row) {
              return;
            }

            row.hidden = Boolean(savedRow.hidden);

            if (savedRow.hidden) {
              row.classList.add("is-hidden");
            } else {
              row.classList.remove("is-hidden");
            }

            if (savedRow.baseUnitPrice) {
              row.dataset.baseUnitPrice = savedRow.baseUnitPrice;
            } else {
              delete row.dataset.baseUnitPrice;
            }
          });
        }

        if (typeof state.withholdingCalculatorMode === "string" && state.withholdingCalculatorMode !== "") {
          withholdingCalculatorMode = resolveWithholdingCalculatorMode(state.withholdingCalculatorMode);
        } else if (typeof state.calculatorMode === "string" && state.calculatorMode !== "") {
          withholdingCalculatorMode = resolveWithholdingCalculatorMode(state.calculatorMode);
        }

        if (typeof state.vatCalculatorMode === "string" && state.vatCalculatorMode !== "") {
          vatCalculatorMode = resolveVatCalculatorMode(state.vatCalculatorMode);
        }

        if (companyAddressInput) {
          companyAddressInput.value = normalizeCompanyAddressValue(companyAddressInput.value);
        }

        numericFields.forEach((field) => {
          if (field.value.trim() === "") {
            return;
          }

          const parsed = parseNumber(field.value);
          field.value = field.hasAttribute("data-item-qty") ? formatQuantity(parsed) : formatMoney(parsed);
        });

        syncTextareas();
        syncUnitPricesForMode();
        syncTotals();
        syncAddRowButton();
        syncCalculatorMode();
        setCustomerActionState();

        return true;
      };

      const readSavedDraft = () => {
        const raw = window.localStorage.getItem(draftStorageKey);

        if (! raw) {
          return null;
        }

        try {
          const parsed = JSON.parse(raw);

          if (parsed?.version === draftStorageVersion && parsed?.state) {
            return parsed;
          }

          window.localStorage.removeItem(draftStorageKey);
          return null;
        } catch (error) {
          console.warn("Unable to read sales document draft", error);
          window.localStorage.removeItem(draftStorageKey);
          return null;
        }
      };

      const updateDraftControls = () => {
        if (! loadDraftButton) {
          return;
        }

        const savedDraft = readSavedDraft();
        loadDraftButton.hidden = ! savedDraft;

        if (! savedDraft) {
          loadDraftButton.removeAttribute("title");
          return;
        }

        const savedAt = savedDraft.savedAt ? new Date(savedDraft.savedAt) : null;
        const savedAtLabel = savedAt && ! Number.isNaN(savedAt.getTime())
          ? savedAt.toLocaleString("th-TH", {
            dateStyle: "short",
            timeStyle: "short",
          })
          : "";

        if (savedAtLabel !== "") {
          loadDraftButton.title = "ร่างล่าสุด: " + savedAtLabel;
        }
      };

      const saveDraftState = () => {
        try {
          const state = captureDraftState();
          window.localStorage.setItem(draftStorageKey, JSON.stringify({
            version: draftStorageVersion,
            savedAt: new Date().toISOString(),
            state,
          }));
          updateDraftControls();
          showStatus("บันทึกร่างเรียบร้อยแล้ว", "success");
        } catch (error) {
          console.warn("Unable to save sales document draft", error);
          showStatus("ไม่สามารถบันทึกร่างได้", "error");
        }
      };

      const loadSavedDraft = () => {
        const savedDraft = readSavedDraft();

        if (! savedDraft?.state) {
          showStatus("ยังไม่มีร่างเอกสารที่บันทึกไว้", "error");
          updateDraftControls();
          return;
        }

        applyDraftState(savedDraft.state);
        showStatus("โหลดร่างล่าสุดเรียบร้อยแล้ว", "success");
      };

      const storeReturnState = () => {
        try {
          window.sessionStorage.setItem(returnStateStorageKey, JSON.stringify(captureDraftState()));
        } catch (error) {
          console.warn("Unable to store return sales document state", error);
        }
      };

      const restoreReturnState = () => {
        const raw = window.sessionStorage.getItem(returnStateStorageKey);

        if (! raw) {
          return false;
        }

        try {
          const state = JSON.parse(raw);
          return applyDraftState(state);
        } catch (error) {
          console.warn("Unable to restore return sales document state", error);
          return false;
        } finally {
          window.sessionStorage.removeItem(returnStateStorageKey);
        }
      };

      const applyPrefillPayload = (payload) => {
        if (! payload || typeof payload !== "object") {
          return false;
        }

        const payloadDocumentType = payload.document_type || payload.document?.type || "quotation";
        setDocumentType(payloadDocumentType);

        withholdingCalculatorMode = typeof payload.withholding_calculator_mode === "string" && payload.withholding_calculator_mode !== ""
          ? resolveWithholdingCalculatorMode(payload.withholding_calculator_mode)
          : typeof payload.calculator_mode === "string" && payload.calculator_mode !== ""
            ? resolveWithholdingCalculatorMode(payload.calculator_mode)
            : typeof payload.withholding_tax_responsibility === "string" && payload.withholding_tax_responsibility !== ""
              ? resolveWithholdingCalculatorMode(payload.withholding_tax_responsibility)
              : withholdingCalculatorMode;

        vatCalculatorMode = typeof payload.vat_calculator_mode === "string" && payload.vat_calculator_mode !== ""
          ? resolveVatCalculatorMode(payload.vat_calculator_mode)
          : typeof payload.vat_tax_responsibility === "string" && payload.vat_tax_responsibility !== ""
            ? resolveVatCalculatorMode(payload.vat_tax_responsibility)
            : vatCalculatorMode;

        if (companyNameThInput) {
          companyNameThInput.value = payload.company?.name_th || companyNameThInput.value;
        }

        if (companyNameEnInput) {
          companyNameEnInput.value = payload.company?.name_en || companyNameEnInput.value;
        }

        if (companyAddressInput) {
          companyAddressInput.value = normalizeCompanyAddressValue(payload.company?.address || companyAddressInput.value);
        }

        if (companyTaxIdInput) {
          companyTaxIdInput.value = payload.company?.tax_id || companyTaxIdInput.value;
        }

        if (documentNumberInput) {
          documentNumberInput.value = payload.document_number || payload.document?.number || documentNumberInput.value;
          documentNumberInput.dataset.documentAutonumber = "false";
        }

        if (documentDateInput) {
          documentDateInput.value = payload.document_date || payload.document?.date || documentDateInput.value;
        }

        if (dueDateInput) {
          dueDateInput.value = payload.due_date || payload.document?.due_date || dueDateInput.value;
        }

        if (referenceNumberInput) {
          referenceNumberInput.value = payload.document?.reference_number || "";
        }

        if (customerSelect) {
          customerSelect.value = payload.customer_id ? String(payload.customer_id) : "";
        }

        if (customerNameInput) {
          customerNameInput.value = payload.customer?.name || payload.customer_name || "";
        }

        if (customerTaxIdInput) {
          customerTaxIdInput.value = payload.customer?.tax_id || "";
        }

        if (customerAddressInput) {
          customerAddressInput.value = payload.customer?.address || "";
        }

        if (customerContactInput) {
          customerContactInput.value = payload.customer?.contact || "";
        }

        if (customerPaymentTermInput) {
          customerPaymentTermInput.value = payload.customer?.payment_term || "";
        }

        if (paymentCashInput) {
          paymentCashInput.checked = Boolean(payload.payment?.cash);
        }

        if (paymentTransferInput) {
          paymentTransferInput.checked = Boolean(payload.payment?.transfer);
        }

        if (paymentChequeInput) {
          paymentChequeInput.checked = Boolean(payload.payment?.cheque);
        }

        if (paymentBankInput) {
          paymentBankInput.value = payload.payment?.bank || paymentBankInput.value;
        }

        if (paymentBranchInput) {
          paymentBranchInput.value = payload.payment?.branch || paymentBranchInput.value;
        }

        if (paymentAccountInput) {
          paymentAccountInput.value = payload.payment?.account_number || paymentAccountInput.value;
        }

        if (approvedByInput) {
          approvedByInput.value = payload.signatures?.approved_by || "";
        }

        if (approvedDateInput) {
          approvedDateInput.value = payload.signatures?.approved_date || approvedDateInput.value;
        }

        if (acceptedByInput) {
          acceptedByInput.value = payload.signatures?.accepted_by || "";
        }

        if (acceptedDateInput) {
          acceptedDateInput.value = payload.signatures?.accepted_date || acceptedDateInput.value;
        }

        const discountRateInput = root.querySelector("[data-discount-rate]");
        const vatRateInput = root.querySelector("[data-vat-rate]");
        const withholdingRateInput = root.querySelector("[data-withholding-rate]");

        if (discountRateInput) {
          discountRateInput.value = payload.totals?.discount_rate_display || payload.totals?.discount_rate || discountRateInput.value;
        }

        if (vatRateInput) {
          vatRateInput.value = payload.totals?.vat_rate_display || payload.totals?.vat_rate || vatRateInput.value;
        }

        if (withholdingRateInput) {
          withholdingRateInput.value = payload.totals?.withholding_rate_display || payload.totals?.withholding_rate || withholdingRateInput.value;
        }

        itemRows.forEach((row, index) => {
          const item = Array.isArray(payload.items) ? payload.items[index] : null;
          const descriptionInput = row.querySelector("[data-item-description]");
          const quantityInput = row.querySelector("[data-item-qty]");
          const unitInput = row.querySelector("[data-item-unit]");
          const unitPriceInput = row.querySelector("[data-item-unit-price]");

          if (! item) {
            row.hidden = false;
            row.classList.remove("is-hidden");
            if (descriptionInput) descriptionInput.value = "";
            if (quantityInput) quantityInput.value = "";
            if (unitInput) unitInput.value = "";
            if (unitPriceInput) unitPriceInput.value = "";
            delete row.dataset.baseUnitPrice;
            return;
          }

          row.hidden = false;
          row.classList.remove("is-hidden");

          if (descriptionInput) {
            descriptionInput.value = item.description || "";
          }

          if (quantityInput) {
            quantityInput.value = item.quantity_display || item.quantity || "";
          }

          if (unitInput) {
            unitInput.value = item.unit || "";
          }

          if (unitPriceInput) {
            unitPriceInput.value = item.unit_price_display || item.unit_price || "";
          }

          storeRowBaseUnitPrice(row, parseNumber(item.unit_price_display || item.unit_price || ""));
        });

        syncTextareas();
        syncUnitPricesForMode();
        syncTotals();
        syncAddRowButton();
        syncCalculatorMode();
        setCustomerActionState();

        return true;
      };

      const openPrintPreview = (mode = "print") => {
        const filename = buildDocumentFilename();
        const printableSheet = buildPrintableSheet();
        storeReturnState();
        window.localStorage.setItem(printStorageKey, printableSheet.outerHTML);
        window.localStorage.setItem(printTitleKey, filename);
        window.localStorage.setItem(printReturnUrlKey, window.location.href);
        window.location.href = @json(route('admin.sales-documents')) + "?print_preview=1&mode=" + encodeURIComponent(mode);
      };

      const syncTextareas = () => {
        root.querySelectorAll("textarea").forEach((textarea) => {
          textarea.style.height = "auto";
          textarea.style.height = textarea.scrollHeight + "px";
        });
      };

      const syncAddRowButton = () => {
        const hasHiddenRow = itemRows.some((row) => row.hidden);

        if (itemActions) {
          itemActions.hidden = !hasHiddenRow;
        }
      };

      const revealNextRow = () => {
        const nextHiddenRow = itemRows.find((row) => row.hidden);

        if (! nextHiddenRow) {
          syncAddRowButton();
          return;
        }

        nextHiddenRow.hidden = false;
        nextHiddenRow.classList.remove("is-hidden");
        syncTextareas();
        syncAddRowButton();

        const firstTextarea = nextHiddenRow.querySelector("textarea, input");
        firstTextarea?.focus();
      };

      const clearCustomerFields = () => {
        if (customerNameInput) {
          customerNameInput.value = "";
        }

        if (customerTaxIdInput) {
          customerTaxIdInput.value = "";
        }

        if (customerAddressInput) {
          customerAddressInput.value = "";
        }

        if (customerContactInput) {
          customerContactInput.value = "";
        }

        if (customerPaymentTermInput) {
          customerPaymentTermInput.value = "";
        }

        syncTextareas();
      };

      const applyCustomer = (customerId) => {
        if (String(customerId || "").trim() === "") {
          clearCustomerFields();
          return;
        }

        const selectedCustomer = customers.find((customer) => String(customer.id) === String(customerId));

        if (! selectedCustomer) {
          clearCustomerFields();
          return;
        }

        if (customerNameInput) {
          customerNameInput.value = selectedCustomer.company_name || selectedCustomer.display_name || "";
        }

        if (customerTaxIdInput) {
          customerTaxIdInput.value = selectedCustomer.tax_id || "";
        }

        if (customerAddressInput) {
          const addressLines = [
            selectedCustomer.address || "",
            selectedCustomer.email || "",
          ].filter((line) => String(line).trim() !== "");

          customerAddressInput.value = addressLines.join("\n");
        }

        if (customerContactInput) {
          customerContactInput.value = selectedCustomer.contact_name || "";
        }

        if (customerPaymentTermInput && selectedCustomer.payment_term) {
          customerPaymentTermInput.value = selectedCustomer.payment_term;
        }

        syncTextareas();
      };

      const findSelectedCustomer = () => {
        if (! customerSelect || String(customerSelect.value || "").trim() === "") {
          return null;
        }

        return customers.find((customer) => String(customer.id) === String(customerSelect.value)) || null;
      };

      const showStatus = (message, type = "success") => {
        if (! statusBanner) {
          return;
        }

        statusBanner.textContent = message;
        statusBanner.hidden = false;
        statusBanner.dataset.statusType = type;
      };

      const setCustomerDialogStatus = (message = "", type = "error") => {
        if (! customerDialogStatus) {
          return;
        }

        if (String(message).trim() === "") {
          customerDialogStatus.hidden = true;
          customerDialogStatus.textContent = "";
          customerDialogStatus.dataset.statusType = "";
          return;
        }

        customerDialogStatus.hidden = false;
        customerDialogStatus.textContent = message;
        customerDialogStatus.dataset.statusType = type;
      };

      const setCustomerActionState = () => {
        if (! customerActionButton) {
          return;
        }

        const selectedCustomer = findSelectedCustomer();

        if (selectedCustomer) {
          customerActionButton.textContent = "แก้ไขลูกค้า";
          customerActionButton.dataset.customerActionMode = "edit";
          return;
        }

        customerActionButton.textContent = "เพิ่มลูกค้า";
        customerActionButton.dataset.customerActionMode = "create";
      };

      const fillCustomerDialog = (customer) => {
        if (customerModalCompanyName) {
          customerModalCompanyName.value = customer.company_name || "";
        }

        if (customerModalContactName) {
          customerModalContactName.value = customer.contact_name || "";
        }

        if (customerModalTaxId) {
          customerModalTaxId.value = customer.tax_id || "";
        }

        if (customerModalAddress) {
          customerModalAddress.value = customer.address || "";
        }

        if (customerModalEmail) {
          customerModalEmail.value = customer.email || "";
        }

        if (customerModalPhone) {
          customerModalPhone.value = customer.phone || "";
        }

      };

      const getCustomerDraftFromDocument = () => ({
        company_name: customerNameInput?.value || "",
        contact_name: customerContactInput?.value || "",
        tax_id: customerTaxIdInput?.value || "",
        address: customerAddressInput?.value || "",
        email: "",
        phone: "",
      });

      const openCustomerDialog = () => {
        if (! customerDialog || ! customerDialogSaveButton) {
          return;
        }

        const selectedCustomer = findSelectedCustomer();
        customerDialogMode = selectedCustomer ? "edit" : "create";
        editingCustomerId = selectedCustomer ? selectedCustomer.id : null;

        if (customerDialogTitle) {
          customerDialogTitle.textContent = customerDialogMode === "edit" ? "แก้ไขลูกค้า" : "เพิ่มลูกค้า";
        }

        if (customerDialogSubtitle) {
          customerDialogSubtitle.textContent = customerDialogMode === "edit"
            ? "อัปเดตข้อมูลลูกค้าที่เลือกไว้ แล้วนำข้อมูลล่าสุดกลับมาใช้บนเอกสารทันที"
            : "เพิ่มลูกค้าใหม่จากหน้านี้ได้เลย แล้วระบบจะเลือกให้พร้อมใช้งานบนเอกสาร";
        }

        customerDialogSaveButton.textContent = customerDialogMode === "edit" ? "บันทึกการแก้ไข" : "บันทึกลูกค้า";
        setCustomerDialogStatus();
        fillCustomerDialog(selectedCustomer || getCustomerDraftFromDocument());

        customerDialog.hidden = false;
        document.body.classList.add("document-modal-open");
        window.setTimeout(() => customerModalCompanyName?.focus(), 40);
      };

      const closeCustomerDialog = () => {
        if (! customerDialog) {
          return;
        }

        customerDialog.hidden = true;
        document.body.classList.remove("document-modal-open");
        setCustomerDialogStatus();
      };

      const upsertCustomerRecord = (customer) => {
        const existingIndex = customers.findIndex((entry) => String(entry.id) === String(customer.id));

        if (existingIndex === -1) {
          customers.push(customer);
        } else {
          customers.splice(existingIndex, 1, customer);
        }

        if (! customerSelect) {
          return;
        }

        let option = Array.from(customerSelect.options).find((entry) => String(entry.value) === String(customer.id));

        if (! option) {
          option = document.createElement("option");
          option.value = String(customer.id);
          customerSelect.append(option);
        }

        option.textContent = customer.display_name;
        customerSelect.value = String(customer.id);
      };

      const saveCustomerFromDialog = async () => {
        if (! customerDialogSaveButton) {
          return;
        }

        const requestPayload = {
          company_name: customerModalCompanyName?.value || "",
          contact_name: customerModalContactName?.value || "",
          tax_id: customerModalTaxId?.value || "",
          address: customerModalAddress?.value || "",
          email: customerModalEmail?.value || "",
          phone: customerModalPhone?.value || "",
        };

        customerDialogSaveButton.disabled = true;
        customerDialogSaveButton.textContent = customerDialogMode === "edit" ? "กำลังบันทึก..." : "กำลังเพิ่ม...";
        setCustomerDialogStatus();

        try {
          const isEditMode = customerDialogMode === "edit" && editingCustomerId !== null;
          const endpoint = isEditMode
            ? customerQuickUpdateRouteTemplate.replace("__CUSTOMER__", String(editingCustomerId))
            : @json(route('admin.customers.quick-store'));
          const response = await fetch(endpoint, {
            method: isEditMode ? "PUT" : "POST",
            headers: {
              "Content-Type": "application/json",
              "Accept": "application/json",
              "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify(requestPayload),
          });

          const responsePayload = await response.json();

          if (! response.ok) {
            const firstError = responsePayload.errors ? Object.values(responsePayload.errors)[0]?.[0] : null;
            throw new Error(firstError || responsePayload.message || "ไม่สามารถบันทึกลูกค้าได้");
          }

          if (responsePayload.customer) {
            upsertCustomerRecord(responsePayload.customer);
            applyCustomer(responsePayload.customer.id);
            setCustomerActionState();
          }

          closeCustomerDialog();
          showStatus(responsePayload.message || "บันทึกลูกค้าเรียบร้อยแล้ว", "success");
        } catch (error) {
          setCustomerDialogStatus(error instanceof Error ? error.message : "ไม่สามารถบันทึกลูกค้าได้", "error");
        } finally {
          customerDialogSaveButton.disabled = false;
          customerDialogSaveButton.textContent = customerDialogMode === "edit" ? "บันทึกการแก้ไข" : "บันทึกลูกค้า";
        }
      };

      const convertIntegerToThaiText = (value) => {
        if (value === 0) {
          return "ศูนย์";
        }

        const digits = ["", "หนึ่ง", "สอง", "สาม", "สี่", "ห้า", "หก", "เจ็ด", "แปด", "เก้า"];
        const positions = ["", "สิบ", "ร้อย", "พัน", "หมื่น", "แสน"];
        const largeUnits = ["", "ล้าน", "ล้านล้าน", "ล้านล้านล้าน"];
        const parts = [];
        let remaining = value;
        let groupIndex = 0;

        while (remaining > 0) {
          const group = remaining % 1000000;
          remaining = Math.floor(remaining / 1000000);

          if (group === 0) {
            groupIndex += 1;
            continue;
          }

          const groupText = String(group)
            .padStart(6, "0")
            .split("")
            .map((digitChar, index, digitsInGroup) => {
              const digit = Number.parseInt(digitChar, 10);
              const position = digitsInGroup.length - index - 1;

              if (digit === 0) {
                return "";
              }

              if (position === 0 && digit === 1 && group > 9) {
                return "เอ็ด";
              }

              if (position === 1 && digit === 2) {
                return "ยี่สิบ";
              }

              if (position === 1 && digit === 1) {
                return "สิบ";
              }

              return digits[digit] + positions[position];
            })
            .join("");

          parts.unshift(groupText + (largeUnits[groupIndex] || ""));
          groupIndex += 1;
        }

        return parts.join("");
      };

      const convertBahtText = (amount) => {
        const roundedAmount = Math.round((amount + Number.EPSILON) * 100) / 100;
        const integerPart = Math.floor(roundedAmount);
        const satangPart = Math.round((roundedAmount - integerPart) * 100);
        const bahtText = convertIntegerToThaiText(integerPart) + "บาท";

        if (satangPart === 0) {
          return bahtText + "ถ้วน";
        }

        return bahtText + convertIntegerToThaiText(satangPart) + "สตางค์";
      };

      const syncTotals = () => {
        let subtotal = 0;

        itemRows.forEach((row) => {
          if (row.hidden) {
            return;
          }

          const quantityInput = row.querySelector("[data-item-qty]");
          const priceInput = row.querySelector("[data-item-unit-price]");
          const amountOutput = row.querySelector("[data-item-amount]");
          const quantity = parseNumber(quantityInput?.value);
          const unitPrice = parseNumber(priceInput?.value);
          const amount = roundMoney(quantity * unitPrice);

          subtotal = roundMoney(subtotal + amount);

          if (amountOutput) {
            amountOutput.textContent = formatMoney(amount);
          }
        });

        const discountRate = parseNumber(root.querySelector("[data-discount-rate]")?.value);
        const vatRate = parseNumber(root.querySelector("[data-vat-rate]")?.value);
        const withholdingRate = parseNumber(root.querySelector("[data-withholding-rate]")?.value);

        const discountAmount = roundMoney(subtotal * discountRate / 100);
        const afterDiscount = roundMoney(subtotal - discountAmount);
        const vatMultiplier = 1 + (vatRate / 100);
        const taxableAmount = isCustomerVatMode() && vatMultiplier > 0
          ? roundMoney(afterDiscount / vatMultiplier)
          : afterDiscount;
        const vatAmount = isCustomerVatMode()
          ? roundMoney(afterDiscount - taxableAmount)
          : roundMoney(taxableAmount * vatRate / 100);
        const grandTotal = isCustomerVatMode()
          ? roundMoney(afterDiscount)
          : roundMoney(afterDiscount + vatAmount);
        const withholdingAmount = roundMoney(taxableAmount * withholdingRate / 100);
        const netToPay = roundMoney(grandTotal - withholdingAmount);

        subtotalOutput.textContent = formatMoney(subtotal);
        discountAmountOutput.textContent = formatMoney(discountAmount);
        afterDiscountOutput.textContent = formatMoney(afterDiscount);
        vatAmountOutput.textContent = formatMoney(vatAmount);
        grandTotalOutput.textContent = formatMoney(grandTotal);
        withholdingAmountOutput.textContent = formatMoney(withholdingAmount);
        netToPayOutput.textContent = formatMoney(netToPay);
        bahtTextOutput.textContent = convertBahtText(grandTotal);
      };

      const syncCalculatorMode = () => {
        withholdingCalculatorModeButtons.forEach((button) => {
          button.classList.toggle(
            "is-active",
            button.dataset.withholdingCalcMode === resolveWithholdingCalculatorMode(withholdingCalculatorMode)
          );
        });

        vatCalculatorModeButtons.forEach((button) => {
          button.classList.toggle(
            "is-active",
            button.dataset.vatCalcMode === resolveVatCalculatorMode(vatCalculatorMode)
          );
        });
      };

      typeButtons.forEach((button) => {
        button.addEventListener("click", () => {
          setDocumentType(button.dataset.docSwitch || "quotation");
        });
      });

      withholdingCalculatorModeButtons.forEach((button) => {
        button.addEventListener("click", () => {
          withholdingCalculatorMode = resolveWithholdingCalculatorMode(button.dataset.withholdingCalcMode);
          syncCalculatorMode();
          syncUnitPricesForMode();
          syncTotals();
          showStatus(currentWithholdingCalculatorModeMeta().successMessage, "success");
        });
      });

      vatCalculatorModeButtons.forEach((button) => {
        button.addEventListener("click", () => {
          vatCalculatorMode = resolveVatCalculatorMode(button.dataset.vatCalcMode);
          syncCalculatorMode();
          syncTotals();
          showStatus(currentVatCalculatorModeMeta().successMessage, "success");
        });
      });

      numericFields.forEach((field) => {
        const isPriceField = field.hasAttribute("data-item-unit-price");

        field.addEventListener("beforeinput", (event) => {
          if (! event.data || event.isComposing || event.inputType.startsWith("delete")) {
            return;
          }

          const currentValue = field.value || "";
          const selectionStart = field.selectionStart ?? currentValue.length;
          const selectionEnd = field.selectionEnd ?? currentValue.length;
          const nextValue = currentValue.slice(0, selectionStart) + event.data + currentValue.slice(selectionEnd);

          if (sanitizeNumericInputValue(nextValue) !== nextValue) {
            event.preventDefault();
          }
        });

        field.addEventListener("paste", (event) => {
          const pasted = event.clipboardData?.getData("text") || "";
          const sanitized = sanitizeNumericInputValue(pasted);

          if (pasted === sanitized) {
            return;
          }

          event.preventDefault();
          const selectionStart = field.selectionStart ?? field.value.length;
          const selectionEnd = field.selectionEnd ?? field.value.length;
          field.setRangeText(sanitized, selectionStart, selectionEnd, "end");
          field.dispatchEvent(new Event("input", { bubbles: true }));
        });

        field.addEventListener("focus", () => {
          if (isPriceField && isCompanyWithholdingMode()) {
            const row = field.closest("[data-item-row]");

            if (row?.dataset.baseUnitPrice) {
              field.value = String(row.dataset.baseUnitPrice).replace(/,/g, "");
              return;
            }
          }

          field.value = field.value.replace(/,/g, "");
        });

        field.addEventListener("blur", () => {
          const parsed = parseNumber(field.value);
          const isQuantityField = field.hasAttribute("data-item-qty");

          if (field.value.trim() === "" && parsed === 0) {
            field.value = "";
          } else {
            field.value = isQuantityField ? formatQuantity(parsed) : formatMoney(parsed);
          }

          if (isPriceField) {
            const row = field.closest("[data-item-row]");

            if (row) {
              storeRowBaseUnitPrice(row, parsed);
            }

            if (isCompanyWithholdingMode()) {
              syncUnitPricesForMode();
            }
          }

          syncTotals();
        });

        field.addEventListener("input", () => {
          const sanitized = sanitizeNumericInputValue(field.value);

          if (field.value !== sanitized) {
            const cursor = field.selectionStart ?? sanitized.length;
            field.value = sanitized;
            field.setSelectionRange(cursor, cursor);
          }

          if (field.hasAttribute("data-item-unit-price")) {
            const row = field.closest("[data-item-row]");

            if (row) {
              storeRowBaseUnitPrice(row, parseNumber(sanitized));
            }
          }

          syncTotals();
        });
      });

      root.querySelectorAll("textarea").forEach((textarea) => {
        textarea.addEventListener("input", syncTextareas);
      });

      documentNumberInput?.addEventListener("input", () => {
        documentNumberInput.dataset.documentAutonumber = "false";
      });

      documentDateInput?.addEventListener("change", () => {
        if (documentNumberInput?.dataset.documentAutonumber === "true") {
          documentNumberInput.value = generateDocumentNumber();
        }
      });

      addItemRowButton?.addEventListener("click", revealNextRow);
      customerSelect?.addEventListener("change", () => {
        const selectedValue = customerSelect.value;

        applyCustomer(selectedValue);

        setCustomerActionState();
      });
      saveDraftButton?.addEventListener("click", saveDraftState);
      loadDraftButton?.addEventListener("click", loadSavedDraft);
      customerActionButton?.addEventListener("click", openCustomerDialog);
      customerDialogSaveButton?.addEventListener("click", saveCustomerFromDialog);
      customerDialogCloseButtons.forEach((button) => {
        button.addEventListener("click", closeCustomerDialog);
      });

      document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && customerDialog && ! customerDialog.hidden) {
          closeCustomerDialog();
        }
      });

      printButton?.addEventListener("click", saveAndDownloadDocument);

      setDocumentType("quotation");
      const restoredFromReturnState = restoreReturnState();
      if (! restoredFromReturnState && prefillPayload) {
        applyPrefillPayload(prefillPayload);
        const editingDocumentNumber = prefillPayload.document_number || prefillPayload.document?.number || "";
        showStatus(
          editingDocumentNumber !== ""
            ? "กำลังแก้ไขเอกสารเลขที่ " + editingDocumentNumber
            : "กำลังแก้ไขเอกสารที่บันทึกไว้",
          "success"
        );
      }
      if (companyAddressInput) {
        companyAddressInput.value = normalizeCompanyAddressValue(companyAddressInput.value);
      }
      numericFields.forEach((field) => {
        if (field.value.trim() === "") {
          return;
        }

        const parsed = parseNumber(field.value);
        field.value = field.hasAttribute("data-item-qty") ? formatQuantity(parsed) : formatMoney(parsed);
      });
      itemRows.forEach((row) => storeRowBaseUnitPrice(row));
      syncTextareas();
      syncUnitPricesForMode();
      syncTotals();
      syncAddRowButton();
      syncCalculatorMode();
      setCustomerActionState();
      updateDraftControls();
    })();
  </script>
  @endif
@endsection
