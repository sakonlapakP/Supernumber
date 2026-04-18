@php
  $items = collect(data_get($payload, 'items', []));
  $company = data_get($payload, 'company', []);
  $customer = data_get($payload, 'customer', []);
  $documentMeta = data_get($payload, 'document', []);
  $payment = data_get($payload, 'payment', []);
  $totals = data_get($payload, 'totals', []);
  $signatures = data_get($payload, 'signatures', []);
  $showPrintToolbar = (bool) ($showPrintToolbar ?? false);
  $autoPrint = (bool) ($autoPrint ?? false);
  $printButtonLabel = trim((string) ($printButtonLabel ?? 'บันทึก PDF'));
@endphp
<!doctype html>
<html lang="th">
  <head>
    <meta charset="utf-8">
    <title>{{ $document->file_name }}</title>
    @php
      $pdfCssVersion = @filemtime(public_path('css/sales-document-pdf.css')) ?: time();
      $appCssVersion = @filemtime(public_path('css/supernumber.css')) ?: time();
    @endphp
    <link rel="stylesheet" href="{{ asset('css/supernumber.css') }}?v={{ $appCssVersion }}">
    <link rel="stylesheet" href="{{ asset('css/sales-document-pdf.css') }}?v={{ $pdfCssVersion }}">
  </head>
  <body class="document-studio-page">
    @if ($showPrintToolbar)
      <div class="document-print-toolbar">
        <button type="button" class="document-print-toolbar__button" data-document-print-trigger>{{ $printButtonLabel }}</button>
        <button type="button" class="document-print-toolbar__button document-print-toolbar__button--secondary" data-document-close-trigger>ปิด / ย้อนกลับ</button>
      </div>
    @endif

    <article class="document-sheet" data-document-type="{{ $document->document_type }}">
      <header class="document-header">
        <div class="document-header__brand">
          <div class="document-header__brand-mark">
            <img src="{{ $logoUrl ?? asset('images/supernumber-document-logo.png') }}" alt="Supernumber">
          </div>

          <div class="document-header__company">
            <div class="print-value doc-textarea doc-textarea--title">{{ data_get($company, 'name_th') }}</div>
            <div class="print-value doc-input doc-input--subtitle">{{ data_get($company, 'name_en') }}</div>
            <div class="print-value doc-textarea doc-textarea--company-address">{{ data_get($company, 'address') }}</div>
          </div>
        </div>

        <div class="document-title-box">
          <div class="document-title-box__name">{{ data_get($documentMeta, 'title_th', $document->document_type === 'invoice' ? 'ใบแจ้งหนี้' : 'ใบเสนอราคา') }}</div>
          <div class="document-title-box__name-en">{{ data_get($documentMeta, 'title_en', $document->document_type === 'invoice' ? 'Invoice' : 'Quotation') }}</div>

          <div class="document-inline-field">
            <span>เลขประจำตัวผู้เสียภาษี / Tax ID</span>
            <div class="print-value doc-input doc-input--align-right">{{ data_get($company, 'tax_id') }}</div>
          </div>
        </div>
      </header>

      <section class="document-party">
        <div class="document-party__customer">
          <div class="document-line-field">
            <span>ชื่อลูกค้า / Name.</span>
            <div class="print-value doc-textarea doc-textarea--customer-name doc-input--strong">{{ data_get($customer, 'name') }}</div>
          </div>

          <div class="document-line-field document-line-field--split">
            <span>เลขประจำตัวผู้เสียภาษี / Tax ID.</span>
            <div class="print-value doc-input">{{ data_get($customer, 'tax_id') }}</div>
          </div>

          <div class="document-address-stack">
            <span>ที่อยู่ / Address.</span>
            <div class="print-value doc-textarea">{{ data_get($customer, 'address') }}</div>
          </div>
        </div>

        <div class="document-meta-grid">
          <label>
            <span>วันที่ / Date</span>
            <div class="print-value doc-input">{{ data_get($documentMeta, 'date_display', data_get($documentMeta, 'date')) }}</div>
          </label>

          <label>
            <span>เลขที่ / No.</span>
            <div class="print-value doc-input">{{ $document->document_number }}</div>
          </label>

          <label>
            <span>เลขที่อ้างอิง / Ref. No.</span>
            <div class="print-value doc-input">{{ data_get($documentMeta, 'reference_number') }}</div>
          </label>

          <label>
            <span>ผู้ติดต่อ / Purchase By.</span>
            <div class="print-value doc-input">{{ data_get($customer, 'contact') }}</div>
          </label>

          <label>
            <span>เงื่อนไขการชำระเงิน / Payment Term.</span>
            <div class="print-value doc-input">{{ data_get($customer, 'payment_term') }}</div>
          </label>

          <label>
            <span>วันครบกำหนด / Due Date</span>
            <div class="print-value doc-input">{{ data_get($documentMeta, 'due_date_display', data_get($documentMeta, 'due_date')) }}</div>
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
            @foreach ($items as $index => $item)
              <tr>
                <td class="document-item-row__index">{{ $index + 1 }}.</td>
                <td class="document-item-row__description"><div class="print-value doc-textarea doc-textarea--item">{{ data_get($item, 'description') }}</div></td>
                <td class="document-item-row__qty"><div class="print-value doc-input doc-input--align-right">{{ data_get($item, 'quantity_display', data_get($item, 'quantity')) }}</div></td>
                <td class="document-item-row__unit"><div class="print-value doc-input doc-input--align-center">{{ data_get($item, 'unit') }}</div></td>
                <td class="document-item-row__price"><div class="print-value doc-input doc-input--align-right">{{ data_get($item, 'unit_price_display', data_get($item, 'unit_price')) }}</div></td>
                <td class="document-item-row__amount"><output>{{ data_get($item, 'amount_display', data_get($item, 'amount', '0.00')) }}</output></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </section>

      <section class="document-summary">
        <div class="document-payment">
          <div class="document-payment__section-title">รายการรับชำระผ่าน</div>
          <div class="document-payment__checkboxes">
            <label>{{ data_get($payment, 'cash') ? '☑' : '☐' }} เงินสด</label>
            <label>{{ data_get($payment, 'transfer') ? '☑' : '☐' }} เงินโอน</label>
            <label>{{ data_get($payment, 'cheque') ? '☑' : '☐' }} เช็คธนาคาร</label>
          </div>

          <div class="document-line-field">
            <span>ธนาคาร / Bank</span>
            <div class="print-value doc-input">{{ data_get($payment, 'bank') }}</div>
          </div>

          <div class="document-line-field">
            <span>สาขา / Branch</span>
            <div class="print-value doc-input">{{ data_get($payment, 'branch') }}</div>
          </div>

          <div class="document-line-field">
            <span>เลขที่บัญชี</span>
            <div class="print-value doc-input">{{ data_get($payment, 'account_number') }}</div>
          </div>

          <div class="document-baht-text">
            <span>จำนวนเงินตัวหนังสือ</span>
            <strong>{{ data_get($totals, 'baht_text', 'ศูนย์บาทถ้วน') }}</strong>
          </div>
        </div>

        <div class="document-totals">
          <div class="document-total-row">
            <span>รวมเป็นเงิน / Sub Total</span>
            <output>{{ data_get($totals, 'subtotal_display', '0.00') }}</output>
          </div>

          <div class="document-total-row">
            <span>หัก ส่วนลด / Discount</span>
            <div class="document-total-row__value">
              <div class="print-value doc-input doc-input--tiny doc-input--align-right">{{ data_get($totals, 'discount_rate_display', data_get($totals, 'discount_rate', '0.00')) }}</div>
              <span>%</span>
              <output>{{ data_get($totals, 'discount_amount_display', '0.00') }}</output>
            </div>
          </div>

          <div class="document-total-row">
            <span>ยอดหลังหักส่วนลด / Total After Discount</span>
            <output>{{ data_get($totals, 'after_discount_display', '0.00') }}</output>
          </div>

          <div class="document-total-row">
            <span>ภาษีมูลค่าเพิ่ม / Vat</span>
            <div class="document-total-row__value">
              <div class="print-value doc-input doc-input--tiny doc-input--align-right">{{ data_get($totals, 'vat_rate_display', data_get($totals, 'vat_rate', '7.00')) }}</div>
              <span>%</span>
              <output>{{ data_get($totals, 'vat_amount_display', '0.00') }}</output>
            </div>
          </div>

          <div class="document-total-row document-total-row--grand">
            <span>จำนวนเงินรวม / Grand Total</span>
            <output>{{ data_get($totals, 'grand_total_display', '0.00') }}</output>
          </div>
        </div>
      </section>

      <section class="document-footer">
        <div class="document-signatures">
          <div class="document-signature-box">
            <div class="document-signature-box__line">
              <div class="print-value doc-input doc-input--signature">{{ data_get($signatures, 'approved_by') }}</div>
            </div>
            <div class="document-signature-box__meta">ผู้อนุมัติ / Approved by</div>

            <div class="document-signature-box__date">
              <span>วันที่ / Date</span>
              <div class="print-value doc-input doc-input--align-center">{{ data_get($signatures, 'approved_date_display', data_get($signatures, 'approved_date')) }}</div>
            </div>
          </div>

          <div class="document-signature-box">
            <div class="document-signature-box__line">
              <div class="print-value doc-input doc-input--signature">{{ data_get($signatures, 'accepted_by') }}</div>
            </div>
            <div class="document-signature-box__meta">ผู้รับใบเสนอ/ผู้รับเอกสาร / Accepted by</div>

            <div class="document-signature-box__date">
              <span>วันที่ / Date</span>
              <div class="print-value doc-input doc-input--align-center">{{ data_get($signatures, 'accepted_date_display', data_get($signatures, 'accepted_date')) }}</div>
            </div>
          </div>
        </div>

        <div class="document-balance-box">
          <div class="document-total-row">
            <span>ภาษีหัก ณ ที่จ่าย (บาท) / Withheld Tax</span>
            <div class="document-total-row__value">
              <div class="print-value doc-input doc-input--tiny doc-input--align-right">{{ data_get($totals, 'withholding_rate_display', data_get($totals, 'withholding_rate', '3.00')) }}</div>
              <span>%</span>
              <output>{{ data_get($totals, 'withholding_amount_display', '0.00') }}</output>
            </div>
          </div>

          <div class="document-total-row document-total-row--net">
            <span>จำนวนเงินที่ต้องชำระ (บาท) / Net to Pay</span>
            <output>{{ data_get($totals, 'net_to_pay_display', '0.00') }}</output>
          </div>
        </div>
      </section>
    </article>

    @if ($showPrintToolbar)
      <script>
        (() => {
          const printButton = document.querySelector("[data-document-print-trigger]");
          const closeButton = document.querySelector("[data-document-close-trigger]");

          const runPrint = () => {
            window.print();
          };

          printButton?.addEventListener("click", runPrint);
          closeButton?.addEventListener("click", () => {
            if (window.opener && ! window.opener.closed) {
              window.close();
              return;
            }

            if (window.history.length > 1) {
              window.history.back();
              return;
            }

            window.close();
          });

          if (@json($autoPrint)) {
            window.setTimeout(runPrint, 300);
          }
        })();
      </script>
    @endif
  </body>
</html>
