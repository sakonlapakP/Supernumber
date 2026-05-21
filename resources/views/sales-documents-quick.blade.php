@extends('layouts.admin')

@section('title', 'Supernumber Admin | สร้างเอกสารด่วน')

@section('content')
  @php
    $today = now('Asia/Bangkok');
    $documentDate = $today->format('Y-m-d');
    $dueDate = $today->copy()->addDays(7)->format('Y-m-d');
    $sequence = $today->format('ymd') . '-001';
    $customerRecords = ($customers ?? collect())->map(fn ($c) => [
      'id' => $c->id,
      'display_name' => $c->display_name,
      'company_name' => $c->company_name,
      'contact_name' => $c->contact_name,
      'tax_id' => $c->tax_id,
      'address' => $c->address,
      'email' => $c->email,
      'phone' => $c->phone,
      'payment_term' => $c->payment_term,
    ])->values();
    $draftRecords = ($drafts ?? collect())->map(fn ($d) => [
      'id' => $d->id,
      'label' => $d->customer_name ?: ($d->payload['customer']['name'] ?? ''),
      'document_number' => $d->payload['document_number'] ?? '',
      'updated_at' => $d->updated_at?->toIso8601String(),
      'updated_at_display' => $d->updated_at?->timezone('Asia/Bangkok')->format('d/m/Y H:i'),
    ])->values();
    $prefillPayload = $prefillPayload ?? null;
    $currentDraftId = $currentDraftId ?? null;
    $initialDocumentType = request('document_type') === 'invoice' ? 'invoice' : 'quotation';
  @endphp

  {{-- Customer add/edit modal --}}
  <div id="qdoc-customer-dialog" class="qdoc-dialog" hidden>
    <div class="qdoc-dialog__backdrop" data-qdoc-dialog-close></div>
    <div class="qdoc-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="qdoc-customer-dialog-title">
      <div class="qdoc-dialog__header">
        <div>
          <strong id="qdoc-customer-dialog-title" data-qdoc-customer-dialog-title>เพิ่มลูกค้า</strong>
          <p data-qdoc-customer-dialog-subtitle>บันทึกข้อมูลลูกค้าสำหรับใบเสนอราคาและใบแจ้งหนี้</p>
        </div>
        <button type="button" class="qdoc-dialog__close" data-qdoc-dialog-close aria-label="ปิด">✕</button>
      </div>
      <div class="qdoc-dialog__status" id="qdoc-customer-dialog-status" hidden></div>
      <div class="qdoc-dialog__body">
        <div class="qdoc-form-row">
          <label class="qdoc-label">
            <span>ชื่อบริษัท / ลูกค้า <em>*</em></span>
            <input type="text" data-qdoc-modal-company-name class="qdoc-input" placeholder="กรอกชื่อบริษัทหรือชื่อลูกค้า">
          </label>
          <label class="qdoc-label">
            <span>ผู้ติดต่อ</span>
            <input type="text" data-qdoc-modal-contact-name class="qdoc-input" placeholder="กรอกชื่อผู้ติดต่อ">
          </label>
        </div>
        <label class="qdoc-label">
          <span>เลขประจำตัวผู้เสียภาษี</span>
          <input type="text" data-qdoc-modal-tax-id class="qdoc-input" placeholder="กรอกเลขประจำตัวผู้เสียภาษี">
        </label>
        <label class="qdoc-label">
          <span>ที่อยู่</span>
          <textarea rows="3" data-qdoc-modal-address class="qdoc-input" placeholder="กรอกที่อยู่ลูกค้า"></textarea>
        </label>
        <div class="qdoc-form-row">
          <label class="qdoc-label">
            <span>อีเมล</span>
            <input type="email" data-qdoc-modal-email class="qdoc-input" placeholder="อีเมลลูกค้า">
          </label>
          <label class="qdoc-label">
            <span>เบอร์โทร</span>
            <input type="text" data-qdoc-modal-phone class="qdoc-input" placeholder="เบอร์โทรลูกค้า">
          </label>
        </div>
      </div>
      <div class="qdoc-dialog__actions">
        <button type="button" class="admin-button admin-button--ghost" data-qdoc-dialog-close>ยกเลิก</button>
        <button type="button" class="admin-button admin-button--primary" data-qdoc-customer-save>บันทึกลูกค้า</button>
      </div>
    </div>
  </div>

  {{-- Preview modal --}}
  <div id="qdoc-preview-modal" class="qdoc-preview-modal" hidden>
    <div class="qdoc-preview-modal__backdrop" data-qdoc-preview-close></div>
    <div class="qdoc-preview-modal__panel">
      <div class="qdoc-preview-modal__toolbar">
        <span class="qdoc-preview-modal__title">ตัวอย่างเอกสาร</span>
        <button type="button" class="admin-button admin-button--ghost admin-button--compact" data-qdoc-preview-close>✕ ปิด</button>
      </div>
      <div class="qdoc-preview-modal__body">
        <div id="qdoc-preview-loading" class="qdoc-preview-loading">กำลังโหลดตัวอย่าง...</div>
        <iframe id="qdoc-preview-iframe" class="qdoc-preview-iframe" hidden title="ตัวอย่างเอกสาร"></iframe>
      </div>
    </div>
  </div>

  <section class="qdoc-page">
    {{-- Page header --}}
    <div class="admin-page-head qdoc-page-head">
      <div>
        <h1>สร้างเอกสารด่วน</h1>
        <p class="admin-subtitle">สร้างใบเสนอราคาหรือใบแจ้งหนี้ได้อย่างรวดเร็ว บันทึกร่าง หรือดาวน์โหลด PDF ได้ทันที</p>
      </div>
      <div class="admin-page-actions">
        <a href="{{ route('admin.sales-documents') }}" class="admin-button admin-button--ghost admin-button--compact">เปิดหน้า Studio เต็ม</a>
        <a href="{{ route('admin.saved-sales-documents.index') }}" class="admin-button admin-button--ghost admin-button--compact">เอกสารที่บันทึกแล้ว</a>
      </div>
    </div>

    {{-- Status banner --}}
    <div id="qdoc-status" class="qdoc-status" hidden></div>

    <div class="qdoc-layout">
    {{-- LEFT / MAIN COLUMN --}}
    <section class="qdoc-main" aria-label="ข้อมูลเอกสาร">

      {{-- Document type + number + dates --}}
      <div class="admin-card qdoc-card">
        <div class="qdoc-card-header">
          <div class="qdoc-type-switch" role="group" aria-label="ประเภทเอกสาร" data-qdoc-type-switch>
            <button type="button" class="is-active" data-qdoc-doc-type="quotation">ใบเสนอราคา</button>
            <button type="button" data-qdoc-doc-type="invoice">ใบแจ้งหนี้</button>
          </div>
          <span class="qdoc-doc-type-label" data-qdoc-type-label>Quotation</span>
        </div>
        <div class="qdoc-meta-grid">
          <label class="qdoc-label">
            <span>เลขที่เอกสาร</span>
            <input type="text" class="qdoc-input" data-qdoc-doc-number value="QT-{{ $sequence }}" data-autonumber="true">
          </label>
          <label class="qdoc-label">
            <span>เลขที่อ้างอิง</span>
            <input type="text" class="qdoc-input" data-qdoc-ref-number placeholder="ไม่บังคับ">
          </label>
          <label class="qdoc-label">
            <span>วันที่ออกเอกสาร</span>
            <input type="date" class="qdoc-input" data-qdoc-doc-date value="{{ $documentDate }}">
          </label>
          <label class="qdoc-label">
            <span>วันสิ้นสุดเอกสาร</span>
            <input type="date" class="qdoc-input" data-qdoc-due-date value="{{ $dueDate }}">
          </label>
        </div>
      </div>

      {{-- Customer selector --}}
      <div class="admin-card qdoc-card">
        <div class="qdoc-section-title">ข้อมูลลูกค้า</div>
        <div class="qdoc-customer-row">
          <select class="qdoc-input qdoc-customer-select" data-qdoc-customer-select>
            <option value="">— เลือกลูกค้า —</option>
            @foreach (($customers ?? collect()) as $customer)
              <option value="{{ $customer->id }}">{{ $customer->display_name }}</option>
            @endforeach
          </select>
          <button type="button" class="admin-button admin-button--compact" data-qdoc-customer-add title="เพิ่มลูกค้าใหม่">+ ลูกค้า</button>
        </div>
        <div class="qdoc-customer-fields" data-qdoc-customer-fields>
          <div class="qdoc-form-row">
            <label class="qdoc-label">
              <span>ชื่อลูกค้า / บริษัท</span>
              <input type="text" class="qdoc-input" data-qdoc-customer-name placeholder="เลือกชื่อบริษัทด้านบน" readonly>
            </label>
            <label class="qdoc-label">
              <span>เลขผู้เสียภาษี</span>
              <input type="text" class="qdoc-input" data-qdoc-customer-tax-id placeholder="เติมอัตโนมัติจากข้อมูลลูกค้า" readonly>
            </label>
          </div>
          <label class="qdoc-label">
            <span>ที่อยู่</span>
            <textarea rows="3" class="qdoc-input" data-qdoc-customer-address placeholder="เติมอัตโนมัติจากข้อมูลลูกค้า" readonly></textarea>
          </label>
          <div class="qdoc-form-row">
            <label class="qdoc-label">
              <span>ผู้ติดต่อ</span>
              <input type="text" class="qdoc-input" data-qdoc-customer-contact placeholder="กรอกชื่อผู้ติดต่อ">
            </label>
            <label class="qdoc-label">
              <span>เบอร์โทร</span>
              <input type="text" class="qdoc-input" data-qdoc-customer-phone placeholder="กรอกเบอร์โทรลูกค้า">
            </label>
          </div>
          <div class="qdoc-form-row">
            <label class="qdoc-label">
              <span>เงื่อนไขการชำระ</span>
              <input type="text" class="qdoc-input" data-qdoc-customer-payment-term placeholder="กรอกเงื่อนไขการชำระ">
            </label>
          </div>
        </div>
      </div>

      {{-- Line items --}}
      <div class="admin-card qdoc-card">
        <div class="qdoc-section-title">รายการสินค้า/บริการ</div>
        <div class="qdoc-items-table-wrap">
          <table class="qdoc-items-table" data-qdoc-items>
            <colgroup>
              <col class="qdoc-col-index">
              <col class="qdoc-col-desc">
              <col class="qdoc-col-qty">
              <col class="qdoc-col-unit">
              <col class="qdoc-col-price">
              <col class="qdoc-col-amount">
              <col class="qdoc-col-del">
            </colgroup>
            <thead>
              <tr>
                <th class="qdoc-col-index">#</th>
                <th class="qdoc-col-desc">รายการ (Description)</th>
                <th class="qdoc-col-qty">จำนวน</th>
                <th class="qdoc-col-unit">หน่วย</th>
                <th class="qdoc-col-price" data-qdoc-price-heading>ราคา/หน่วย</th>
                <th class="qdoc-col-amount">จำนวนเงิน</th>
                <th class="qdoc-col-del"></th>
              </tr>
            </thead>
            <tbody data-qdoc-item-body>
              <tr data-qdoc-item-row>
                <td class="qdoc-col-index qdoc-row-index">1</td>
                <td class="qdoc-col-desc"><input type="text" class="qdoc-input qdoc-input--inline" data-qdoc-item-desc placeholder="ชื่อสินค้า/บริการ"></td>
                <td class="qdoc-col-qty"><input type="text" class="qdoc-input qdoc-input--inline qdoc-input--right" data-qdoc-item-qty inputmode="decimal" placeholder="0"></td>
                <td class="qdoc-col-unit"><input type="text" class="qdoc-input qdoc-input--inline qdoc-input--center" data-qdoc-item-unit placeholder="ชิ้น"></td>
                <td class="qdoc-col-price"><input type="text" class="qdoc-input qdoc-input--inline qdoc-input--right" data-qdoc-item-price inputmode="decimal" placeholder="0.00"></td>
                <td class="qdoc-col-amount"><output data-qdoc-item-amount>0.00</output></td>
                <td class="qdoc-col-del"></td>
              </tr>
            </tbody>
          </table>
        </div>
        <button type="button" class="admin-button admin-button--ghost admin-button--compact qdoc-add-row-btn" data-qdoc-add-row>
          + เพิ่มรายการ
        </button>
      </div>

      {{-- Payment method + bank --}}
      <div class="admin-card qdoc-card">
        <div class="qdoc-section-title">วิธีการชำระเงิน</div>
        <div class="qdoc-form-row" style="margin-top:8px;">
          <label class="qdoc-label">
            <span>ธนาคาร</span>
            <input type="text" class="qdoc-input" data-qdoc-payment-bank value="ธนาคารกสิกรไทย บจก. ซุปเปอร์นัมเบอร์" readonly>
          </label>
          <label class="qdoc-label">
            <span>สาขา</span>
            <input type="text" class="qdoc-input" data-qdoc-payment-branch value="จามจุรีสแควร์" readonly>
          </label>
        </div>
        <div class="qdoc-form-row" style="margin-top:8px;">
          <label class="qdoc-label">
            <span>เลขบัญชี</span>
            <input type="text" class="qdoc-input" data-qdoc-payment-account value="0063701726" readonly>
          </label>
          <label class="qdoc-label qdoc-payment-method">
            <span>เลือกวิธีชำระเงิน</span>
            <select class="qdoc-input" data-qdoc-payment-method>
              <option value="cash">เงินสด</option>
              <option value="transfer" selected>เงินโอน</option>
              <option value="cheque">เช็คธนาคาร</option>
            </select>
          </label>
        </div>
      </div>

      {{-- Signatures --}}
      <div class="admin-card qdoc-card">
        <div class="qdoc-section-title">ผู้ลงนาม (ไม่บังคับ)</div>
        <div class="qdoc-meta-grid">
          <label class="qdoc-label">
            <span>ผู้อนุมัติ</span>
            <input type="text" class="qdoc-input" data-qdoc-approved-by placeholder="ชื่อผู้อนุมัติ">
          </label>
          <label class="qdoc-label">
            <span>วันที่อนุมัติ</span>
            <input type="date" class="qdoc-input" data-qdoc-approved-date value="{{ $documentDate }}">
          </label>
        </div>
        <div class="qdoc-meta-grid" style="margin-top: 10px;">
          <label class="qdoc-label">
            <span>ผู้รับเอกสาร</span>
            <input type="text" class="qdoc-input" data-qdoc-accepted-by placeholder="ชื่อผู้รับ">
          </label>
          <label class="qdoc-label">
            <span>วันที่รับ</span>
            <input type="date" class="qdoc-input" data-qdoc-accepted-date value="{{ $documentDate }}">
          </label>
        </div>
      </div>

      {{-- Document actions stay in the main column so mobile users can finish the document. --}}
      <div class="admin-card qdoc-card qdoc-actions" aria-label="บันทึกและสร้างเอกสาร">
        <div class="qdoc-section-title">จัดการเอกสาร</div>
        <button type="button" class="admin-button admin-button--ghost qdoc-action-btn" data-qdoc-save-draft>
          💾 บันทึกร่าง
        </button>
        <button type="button" class="admin-button admin-button--ghost qdoc-action-btn" data-qdoc-preview>
          👁 ดูตัวอย่าง
        </button>
        <button type="button" class="admin-button admin-button--primary qdoc-action-btn" data-qdoc-download="quotation">
          📄 สร้าง Quotation
        </button>
        <button type="button" class="admin-button admin-button--save qdoc-action-btn" data-qdoc-download="invoice">
          🧾 สร้าง Invoice
        </button>
      </div>
    </section>{{-- end .qdoc-main --}}

    {{-- RIGHT SIDEBAR --}}
    <aside class="qdoc-sidebar" aria-label="การคำนวณและการจัดการเอกสาร">

      {{-- Tax calculator modes --}}
      <div class="admin-card qdoc-card">
        <div class="qdoc-section-title">คำนวณภาษี</div>

        <div class="qdoc-tax-group qdoc-tax-group--mode">
          <div class="qdoc-mode-buttons qdoc-mode-buttons--calculation" role="group" aria-label="รูปแบบการคำนวณภาษี">
            <button type="button" class="is-active" data-qdoc-calculation-mode="standard">Standard</button>
            <button type="button" data-qdoc-calculation-mode="reverse">Reverse</button>
          </div>
          <div class="qdoc-tax-mode-caption" data-qdoc-calculation-caption>กรอกราคาค่าจ้างก่อน VAT</div>
          <div class="qdoc-tax-rates" aria-label="อัตราภาษีที่ใช้">
            <span>VAT 7%</span>
            <span>WHT 3%</span>
          </div>
        </div>

        <div class="qdoc-tax-group" style="margin-top:12px;">
          <label class="qdoc-label qdoc-label--inline">
            <span>ส่วนลด %</span>
            <input type="text" class="qdoc-input qdoc-input--tiny qdoc-input--right" data-qdoc-discount-rate value="0" inputmode="decimal">
          </label>
        </div>
      </div>

      {{-- Totals --}}
      <div class="admin-card qdoc-card qdoc-totals">
        <div class="qdoc-section-title">สรุปยอด</div>
        <div class="qdoc-total-row">
          <span>รวมก่อนหักลด</span>
          <output data-qdoc-subtotal>0.00</output>
        </div>
        <div class="qdoc-total-row">
          <span>ส่วนลด</span>
          <output data-qdoc-discount-amount>0.00</output>
        </div>
        <div class="qdoc-total-row">
          <span>หลังหักลด</span>
          <output data-qdoc-after-discount>0.00</output>
        </div>
        <div class="qdoc-total-row qdoc-total-row--target" data-qdoc-target-income-row hidden>
          <span>รายได้เป้าหมาย</span>
          <output data-qdoc-target-income>0.00</output>
        </div>
        <div class="qdoc-total-row">
          <span>VAT (<span data-qdoc-vat-rate-label>7</span>%)</span>
          <output data-qdoc-vat-amount>0.00</output>
        </div>
        <div class="qdoc-total-row qdoc-total-row--grand">
          <span>ยอดรวม</span>
          <output data-qdoc-grand-total>0.00</output>
        </div>
        <div class="qdoc-total-row">
          <span>หัก ณ ที่จ่าย (<span data-qdoc-wht-rate-label>3</span>%)</span>
          <output data-qdoc-wht-amount>0.00</output>
        </div>
        <div class="qdoc-total-row qdoc-total-row--net">
          <span>ยอดที่ต้องชำระ</span>
          <output data-qdoc-net-to-pay>0.00</output>
        </div>
        <div class="qdoc-total-row" data-qdoc-service-income-row>
          <span>รายได้หลัง WHT</span>
          <output data-qdoc-service-income>0.00</output>
        </div>
        <div class="qdoc-baht-text">
          <strong data-qdoc-baht-text>ศูนย์บาทถ้วน</strong>
        </div>
      </div>

      {{-- Drafts list --}}
      <div class="admin-card qdoc-card" id="qdoc-drafts-panel">
        <div class="qdoc-section-title">ร่างเอกสาร
          <span class="qdoc-draft-count" id="qdoc-draft-count">{{ count($draftRecords) }}</span>
        </div>
        <div id="qdoc-drafts-list" class="qdoc-drafts-list">
          @forelse (($drafts ?? collect()) as $draft)
            @php
              $draftLabel = $draft->customer_name ?: ($draft->payload['customer']['name'] ?? '');
              $draftNumber = $draft->payload['document_number'] ?? '';
            @endphp
            <div class="qdoc-draft-item {{ $currentDraftId == $draft->id ? 'is-current' : '' }}" data-draft-id="{{ $draft->id }}">
              <div class="qdoc-draft-item__info">
                <span class="qdoc-draft-item__name">{{ $draftLabel ?: 'ไม่มีชื่อลูกค้า' }}</span>
                @if ($draftNumber)
                  <span class="qdoc-draft-item__number">{{ $draftNumber }}</span>
                @endif
                <span class="qdoc-draft-item__date">{{ $draft->updated_at?->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</span>
              </div>
              <div class="qdoc-draft-item__actions">
                <a href="{{ route('admin.sales-documents-quick', ['draft' => $draft->id]) }}" class="qdoc-draft-load-btn" title="โหลดร่างนี้">โหลด</a>
                <button type="button" class="qdoc-draft-del-btn" data-draft-delete="{{ $draft->id }}" title="ลบร่าง">✕</button>
              </div>
            </div>
          @empty
            <p class="qdoc-draft-empty" id="qdoc-draft-empty">ยังไม่มีร่างเอกสาร</p>
          @endforelse
        </div>
      </div>

    </aside>{{-- end .qdoc-sidebar --}}
    </div>{{-- end .qdoc-layout --}}
  </section>{{-- end .qdoc-page --}}

  <style>
    /* ─── Layout ─── */
    .qdoc-page {
      display: grid;
      gap: 0;
      min-width: 0;
    }
    .qdoc-page-head {
      margin-bottom: 16px;
    }
    .qdoc-layout {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(300px, 320px);
      gap: 18px;
      align-items: start;
      min-width: 0;
    }
    @media (max-width: 900px) {
      .qdoc-layout { grid-template-columns: 1fr; gap: 16px; }
    }
    @media (max-width: 768px) {
      .qdoc-layout { gap: 14px; }
    }
    .qdoc-main,
    .qdoc-sidebar {
      display: grid;
      gap: 12px;
      align-content: start;
      min-width: 0;
    }

    /* ─── Card & sections ─── */
    .qdoc-card { padding: 16px; }
    .qdoc-card-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 12px;
    }
    .qdoc-section-title {
      font-size: 13px;
      font-weight: 700;
      color: #374151;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* ─── Type switch ─── */
    .qdoc-type-switch {
      display: flex;
      gap: 0;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      overflow: hidden;
    }
    .qdoc-type-switch button {
      padding: 6px 14px;
      background: #f9fafb;
      border: none;
      font-size: 13px;
      cursor: pointer;
      color: #6b7280;
      transition: background .15s;
    }
    .qdoc-type-switch button.is-active {
      background: #2563eb;
      color: #fff;
      font-weight: 600;
    }
    .qdoc-doc-type-label {
      font-size: 13px;
      color: #6b7280;
      font-style: italic;
    }

    /* ─── Form elements ─── */
    .qdoc-meta-grid {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
      gap: 10px;
    }
    .qdoc-meta-grid .qdoc-label,
    .qdoc-meta-grid .qdoc-input {
      min-width: 0;
      min-inline-size: 0;
    }
    .qdoc-meta-grid .qdoc-input {
      display: block;
      max-width: 100%;
      inline-size: 100%;
    }
    .qdoc-meta-grid input[type="date"].qdoc-input {
      -webkit-appearance: none;
      appearance: none;
    }
    @media (max-width: 768px) {
      .qdoc-meta-grid { gap: 8px; grid-template-columns: 1fr; }
      .qdoc-meta-grid .qdoc-input { padding: 6px 8px; font-size: 12px; }
      .qdoc-card { padding: 10px 8px; }
      .qdoc-form-row { grid-template-columns: 1fr; gap: 8px; }
      .qdoc-label span { font-size: 10px; }
      .qdoc-input { padding: 6px 8px; font-size: 12px; min-height: 30px; }
      .qdoc-payment-method { max-width: 100%; }
      .qdoc-type-switch button { padding: 5px 10px; font-size: 11px; }
      .qdoc-main { gap: 8px; }
      .qdoc-sidebar { display: grid; }
    }
    @media (max-width: 480px) {
      .qdoc-meta-grid { grid-template-columns: 1fr; gap: 10px; }
      .qdoc-meta-grid .qdoc-input { padding: 8px 10px; }
      .qdoc-card { padding: 16px 12px; }
      .qdoc-form-row { gap: 10px; }
      .qdoc-label { gap: 6px; }
      .qdoc-label span { font-size: 11px; }
      .qdoc-input { padding: 10px 10px; font-size: 13px; min-height: 38px; }
      h1 { font-size: 18px; margin-bottom: 6px !important; }
      .admin-subtitle { font-size: 12px; }
      .qdoc-items-table { min-width: 100%; }
      col.qdoc-col-index { width: 32px; }
      col.qdoc-col-qty { width: 60px; }
      col.qdoc-col-unit { width: 60px; }
      col.qdoc-col-price,
      col.qdoc-col-amount { width: 80px; }
      col.qdoc-col-del { width: 28px; }
      .qdoc-items-table { font-size: 12px; }
      .qdoc-items-table th { font-size: 10px; padding: 4px 4px 6px; }
      .qdoc-items-table td { padding: 4px 4px; }
      .qdoc-items-table th,
      .qdoc-items-table td { min-width: 0; }
    }

    .qdoc-form-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 10px;
    }
    .qdoc-label {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .qdoc-label span {
      font-size: 11px;
      font-weight: 600;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: .4px;
    }
    .qdoc-label span em {
      color: #ef4444;
      font-style: normal;
    }
    .qdoc-label--inline {
      flex-direction: row;
      align-items: center;
      gap: 8px;
    }
    .qdoc-label--inline span { min-width: 60px; }
    .qdoc-input {
      border: 1px solid #d1d5db;
      border-radius: 5px;
      padding: 7px 9px;
      font-size: 13px;
      color: #111827;
      background: #fff;
      width: 100%;
      box-sizing: border-box;
      transition: border-color .15s;
    }
    .qdoc-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 2px #dbeafe; }
    .qdoc-input[readonly] {
      background: #f9fafb;
      color: #374151;
      cursor: default;
    }
    .qdoc-input--tiny { width: 64px; }
    .qdoc-input--right { text-align: right; }
    .qdoc-input--center { text-align: center; }
    .qdoc-input--inline { border: none; border-bottom: 1px solid #e5e7eb; border-radius: 0; padding: 6px 4px; background: transparent; min-height: 28px; display: block; width: 100%; }
    .qdoc-input--inline:focus { outline: none; border-bottom-color: #2563eb; box-shadow: none; background: #f0f9ff; }
    .qdoc-input--inline::placeholder { color: #d1d5db; }
    textarea.qdoc-input { resize: vertical; }

    /* ─── Customer row ─── */
    .qdoc-customer-row {
      display: flex;
      gap: 8px;
      align-items: center;
      margin-bottom: 12px;
    }
    .qdoc-customer-select { flex: 1; }
    .qdoc-customer-fields { display: flex; flex-direction: column; gap: 10px; }

    /* ─── Line items table ─── */
    .qdoc-items-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .qdoc-items-table {
      width: 100%;
      min-width: 720px;
      border-collapse: collapse;
      font-size: 13px;
      table-layout: fixed;
    }
    .qdoc-items-table th {
      font-size: 11px;
      font-weight: 700;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: .4px;
      padding: 6px 6px 8px;
      border-bottom: 2px solid #e5e7eb;
      text-align: left;
    }
    .qdoc-items-table td { padding: 6px 6px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
    col.qdoc-col-index { width: 36px; }
    col.qdoc-col-qty { width: 80px; }
    col.qdoc-col-unit { width: 72px; }
    col.qdoc-col-price,
    col.qdoc-col-amount { width: 110px; }
    col.qdoc-col-del { width: 30px; }
    .qdoc-col-index { text-align: center; color: #9ca3af; font-size: 12px; }
    .qdoc-col-amount { text-align: right; font-variant-numeric: tabular-nums; }
    .qdoc-col-del { text-align: center; }
    .qdoc-items-table output { font-size: 13px; }
    .qdoc-del-row-btn {
      background: none; border: none; cursor: pointer;
      color: #d1d5db; font-size: 16px; padding: 2px 4px;
      transition: color .15s;
    }
    .qdoc-del-row-btn:hover { color: #ef4444; }
    .qdoc-add-row-btn { margin-top: 10px; }

    /* ─── Payment method ─── */
    .qdoc-payment-method { max-width: 260px; }

    /* ─── Tax groups ─── */
    .qdoc-tax-group { padding: 10px 12px; background: #f9fafb; border-radius: 6px; }
    .qdoc-tax-group--mode { border-left: 4px solid #2563eb; }
    .qdoc-tax-label { font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 8px; }
    .qdoc-mode-buttons { display: flex; gap: 6px; margin-bottom: 8px; }
    .qdoc-mode-buttons--calculation button { min-height: 34px; }
    .qdoc-mode-buttons button {
      flex: 1; padding: 5px 10px; border-radius: 4px; border: 1px solid #d1d5db;
      background: #fff; font-size: 12px; cursor: pointer; color: #6b7280;
      transition: all .15s;
    }
    .qdoc-mode-buttons button.is-active {
      background: #2563eb; color: #fff; border-color: #2563eb; font-weight: 600;
    }
    .qdoc-tax-mode-caption {
      color: #374151;
      font-size: 12px;
      line-height: 1.35;
      min-height: 17px;
    }
    .qdoc-tax-rates {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 8px;
    }
    .qdoc-tax-rates span {
      border: 1px solid #d1d5db;
      border-radius: 4px;
      background: #fff;
      color: #374151;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 7px;
    }

    /* ─── Totals ─── */
    .qdoc-totals { font-size: 13px; }
    .qdoc-total-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: 5px 0; border-bottom: 1px solid #f3f4f6;
      color: #374151;
    }
    .qdoc-total-row[hidden] { display: none; }
    .qdoc-total-row span { color: #6b7280; font-size: 12px; }
    .qdoc-total-row output { font-variant-numeric: tabular-nums; }
    .qdoc-total-row--grand { font-weight: 700; font-size: 15px; border-top: 2px solid #e5e7eb; margin-top: 4px; padding-top: 8px; }
    .qdoc-total-row--grand span { color: #111827; font-size: 14px; }
    .qdoc-total-row--net { font-weight: 700; font-size: 14px; color: #2563eb; }
    .qdoc-total-row--net span { color: #2563eb; }
    .qdoc-total-row--target { color: #166534; font-weight: 700; }
    .qdoc-total-row--target span { color: #166534; }
    .qdoc-baht-text { margin-top: 8px; padding: 8px; background: #f0fdf4; border-radius: 4px; font-size: 12px; text-align: center; color: #166534; }

    /* ─── Action buttons ─── */
    .qdoc-actions { display: flex; flex-direction: column; gap: 8px; }
    .qdoc-action-btn { width: 100%; justify-content: center; }

    /* ─── Drafts ─── */
    .qdoc-draft-count {
      background: #e5e7eb; color: #374151; border-radius: 10px;
      padding: 1px 7px; font-size: 11px; font-weight: 600;
    }
    .qdoc-drafts-list { display: flex; flex-direction: column; gap: 6px; max-height: 340px; overflow-y: auto; }
    .qdoc-draft-item {
      display: flex; align-items: center; justify-content: space-between;
      padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 6px;
      background: #fafafa; font-size: 12px;
      transition: border-color .15s;
    }
    .qdoc-draft-item.is-current { border-color: #2563eb; background: #eff6ff; }
    .qdoc-draft-item:hover { border-color: #93c5fd; }
    .qdoc-draft-item__info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
    .qdoc-draft-item__name { font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .qdoc-draft-item__number { color: #6b7280; }
    .qdoc-draft-item__date { color: #9ca3af; font-size: 11px; }
    .qdoc-draft-item__actions { display: flex; gap: 4px; flex-shrink: 0; margin-left: 8px; }
    .qdoc-draft-load-btn {
      font-size: 11px; color: #2563eb; text-decoration: none; padding: 3px 8px;
      border: 1px solid #bfdbfe; border-radius: 3px; background: #eff6ff;
    }
    .qdoc-draft-load-btn:hover { background: #dbeafe; }
    .qdoc-draft-del-btn {
      background: none; border: 1px solid #e5e7eb; color: #9ca3af;
      border-radius: 3px; padding: 3px 7px; cursor: pointer; font-size: 12px;
      transition: all .15s;
    }
    .qdoc-draft-del-btn:hover { border-color: #ef4444; color: #ef4444; }
    .qdoc-draft-empty { color: #9ca3af; font-size: 12px; text-align: center; padding: 12px 0; }

    /* ─── Status banner ─── */
    .qdoc-status {
      padding: 10px 14px; border-radius: 6px; margin-bottom: 12px;
      font-size: 13px; font-weight: 500;
    }
    .qdoc-status[data-type="success"] { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .qdoc-status[data-type="error"] { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .qdoc-status[data-type="info"] { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }

    /* ─── Dialog modal ─── */
    .qdoc-dialog { position: fixed; inset: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; }
    .qdoc-dialog[hidden] { display: none; }
    .qdoc-dialog__backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.5); }
    .qdoc-dialog__panel {
      position: relative; z-index: 1; background: #fff; border-radius: 10px;
      width: min(540px, 95vw); max-height: 90vh; overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0,0,0,.2);
    }
    .qdoc-dialog__header {
      display: flex; justify-content: space-between; align-items: flex-start;
      padding: 18px 20px 0; border-bottom: 1px solid #e5e7eb; padding-bottom: 14px;
    }
    .qdoc-dialog__header strong { font-size: 15px; }
    .qdoc-dialog__header p { font-size: 12px; color: #6b7280; margin: 4px 0 0; }
    .qdoc-dialog__close {
      background: none; border: none; cursor: pointer; font-size: 18px; color: #9ca3af;
      padding: 2px 6px; transition: color .15s;
    }
    .qdoc-dialog__close:hover { color: #374151; }
    .qdoc-dialog__status {
      margin: 12px 20px 0; padding: 8px 12px; border-radius: 5px; font-size: 13px;
    }
    .qdoc-dialog__status[data-type="error"] { background: #fef2f2; color: #991b1b; }
    .qdoc-dialog__status[data-type="success"] { background: #f0fdf4; color: #166534; }
    .qdoc-dialog__body { padding: 16px 20px; display: flex; flex-direction: column; gap: 12px; }
    .qdoc-dialog__actions { padding: 12px 20px 18px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 8px; }

    /* ─── Preview modal ─── */
    .qdoc-preview-modal { position: fixed; inset: 0; z-index: 1100; display: flex; align-items: stretch; justify-content: center; }
    .qdoc-preview-modal[hidden] { display: none; }
    .qdoc-preview-modal__backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.6); }
    .qdoc-preview-modal__panel {
      position: relative; z-index: 1; background: #f3f4f6;
      width: min(900px, 98vw); display: flex; flex-direction: column;
      max-height: 98vh; border-radius: 8px; overflow: hidden;
      box-shadow: 0 25px 80px rgba(0,0,0,.3);
    }
    .qdoc-preview-modal__toolbar {
      display: flex; align-items: center; justify-content: space-between;
      padding: 10px 16px; background: #fff; border-bottom: 1px solid #e5e7eb;
      flex-shrink: 0;
    }
    .qdoc-preview-modal__title { font-size: 14px; font-weight: 600; }
    .qdoc-preview-modal__body { flex: 1; overflow: hidden; position: relative; }
    .qdoc-preview-loading { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #6b7280; }
    .qdoc-preview-iframe { width: 100%; height: 100%; border: none; display: block; }

    /* ─── Mobile optimizations ─── */
    @media (max-width: 768px) {
      .qdoc-page { gap: 10px; padding: 8px 0; }
      .qdoc-layout,
      .qdoc-main,
      .qdoc-sidebar,
      .qdoc-card,
      .qdoc-items-table-wrap {
        min-width: 0;
        max-width: 100%;
      }
      .qdoc-main,
      .qdoc-sidebar { gap: 8px; }
      .qdoc-items-table-wrap { margin: 0 -4px; padding: 0 4px; }
      .qdoc-card { padding: 10px 8px; }
      .qdoc-main { gap: 8px; }
      .qdoc-form-row { grid-template-columns: minmax(0, 1fr); }
      .qdoc-label { gap: 2px; }
      .qdoc-label span { font-size: 10px; }
      .qdoc-input { padding: 6px 8px; font-size: 12px; min-height: 30px; }
      .qdoc-section-title { font-size: 11px; margin-bottom: 6px; }
      .qdoc-type-switch button { padding: 5px 10px; font-size: 11px; }
      .qdoc-card-header { margin-bottom: 6px; }
    }
    @media (max-width: 600px) {
      div[style*="justify-content: space-between"] > div:last-child { display: flex; flex-direction: column; width: 100%; gap: 6px; }
      div[style*="justify-content: space-between"] > div:last-child a { width: 100%; font-size: 11px; padding: 5px 8px; }
      h1 { font-size: 15px !important; margin-bottom: 3px !important; }
      .admin-subtitle { font-size: 11px !important; line-height: 1.2; }
    }
    @media (max-width: 480px) {
      .qdoc-customer-row { flex-wrap: wrap; gap: 4px; }
      .qdoc-customer-select { width: 100%; font-size: 12px; }
      .qdoc-customer-fields { width: 100%; gap: 4px; }
      .qdoc-card-header { flex-wrap: wrap; gap: 4px; margin-bottom: 4px; }
      .qdoc-type-switch { flex-wrap: wrap; }
      .qdoc-type-switch button { padding: 3px 6px; font-size: 9px; }
      .qdoc-section-title { font-size: 9px; margin-bottom: 4px; }
      .admin-button--compact { padding: 4px 6px; font-size: 10px; }
      [style*="display: flex; gap: 8px;"] { gap: 3px; flex-wrap: wrap; }
      .qdoc-card { padding: 8px 6px; }
      .qdoc-main { gap: 5px; }
      .qdoc-card-header { margin-bottom: 4px; }
      .qdoc-label { gap: 1px; }
      .qdoc-label span { font-size: 8px; letter-spacing: .2px; }
      .qdoc-input { padding: 4px 5px; font-size: 11px; min-height: 24px; }
      .qdoc-meta-grid { gap: 4px; }
      .qdoc-form-row { gap: 4px; }
      h1 { font-size: 13px !important; margin-bottom: 2px !important; }
      .admin-subtitle { font-size: 9px !important; line-height: 1.1; }
      .qdoc-doc-type-label { font-size: 10px; }
      .qdoc-items-table { min-width: 100%; font-size: 10px; }
      .qdoc-items-table th { font-size: 8px; padding: 2px 2px 3px; }
      .qdoc-items-table td { padding: 2px 2px; }
      col.qdoc-col-index { width: 28px; }
      col.qdoc-col-qty,
      col.qdoc-col-unit { width: 48px; }
      col.qdoc-col-price,
      col.qdoc-col-amount { width: 64px; }
      col.qdoc-col-del { width: 24px; }
      .qdoc-payment-method { max-width: 100%; font-size: 11px; }
      .qdoc-totals { font-size: 11px; }
      .qdoc-total-row { padding: 2px 0; }
      .qdoc-total-row span { font-size: 10px; }
    }
  </style>

  <script>
    (() => {
      const customers = @json($customerRecords);
      const prefillPayload = @json($prefillPayload);
      const initialDraftId = @json($currentDraftId);
      const initialDocumentType = @json($initialDocumentType);
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());

      const routes = {
        saveDraft:    @json(route('admin.sales-documents-quick.draft')),
        deleteDraft:  '/admin/sales-documents-quick/draft/', // + id
        previewHtml:  @json(route('admin.sales-documents-quick.preview-html')),
        saveDownload: @json(route('admin.sales-documents.save-download')),
        quickBase:    @json(route('admin.sales-documents-quick')),
        deleteDraftBase: @json(url('/admin/sales-documents-quick/draft')),
      };

      // ─── State ───
      const VAT_RATE = 7;
      const WHT_RATE = 3;
      let calculationMode = 'standard';
      let currentDraftId = initialDraftId;
      let rowCounter = 1;

      // ─── DOM refs ───
      const statusEl     = document.getElementById('qdoc-status');
      const docTypeButtons = document.querySelectorAll('[data-qdoc-doc-type]');
      const typeLabel    = document.querySelector('[data-qdoc-type-label]');
      const docNumberInput = document.querySelector('[data-qdoc-doc-number]');
      const docDateInput = document.querySelector('[data-qdoc-doc-date]');
      const dueDateInput = document.querySelector('[data-qdoc-due-date]');
      const refNumberInput = document.querySelector('[data-qdoc-ref-number]');
      const customerSelect = document.querySelector('[data-qdoc-customer-select]');
      const customerNameInput = document.querySelector('[data-qdoc-customer-name]');
      const customerTaxIdInput = document.querySelector('[data-qdoc-customer-tax-id]');
      const customerAddressInput = document.querySelector('[data-qdoc-customer-address]');
      const customerContactInput = document.querySelector('[data-qdoc-customer-contact]');
      const customerPhoneInput = document.querySelector('[data-qdoc-customer-phone]');
      const customerPaymentTermInput = document.querySelector('[data-qdoc-customer-payment-term]');
      const itemBody = document.querySelector('[data-qdoc-item-body]');
      const addRowBtn = document.querySelector('[data-qdoc-add-row]');
      const discountRateInput = document.querySelector('[data-qdoc-discount-rate]');
      const subtotalOut = document.querySelector('[data-qdoc-subtotal]');
      const discountAmountOut = document.querySelector('[data-qdoc-discount-amount]');
      const afterDiscountOut = document.querySelector('[data-qdoc-after-discount]');
      const targetIncomeRow = document.querySelector('[data-qdoc-target-income-row]');
      const targetIncomeOut = document.querySelector('[data-qdoc-target-income]');
      const vatAmountOut = document.querySelector('[data-qdoc-vat-amount]');
      const grandTotalOut = document.querySelector('[data-qdoc-grand-total]');
      const whtAmountOut = document.querySelector('[data-qdoc-wht-amount]');
      const netToPayOut = document.querySelector('[data-qdoc-net-to-pay]');
      const serviceIncomeOut = document.querySelector('[data-qdoc-service-income]');
      const bahtTextOut = document.querySelector('[data-qdoc-baht-text]');
      const vatRateLabel = document.querySelector('[data-qdoc-vat-rate-label]');
      const whtRateLabel = document.querySelector('[data-qdoc-wht-rate-label]');
      const calculationModeButtons = document.querySelectorAll('[data-qdoc-calculation-mode]');
      const calculationCaption = document.querySelector('[data-qdoc-calculation-caption]');
      const priceHeading = document.querySelector('[data-qdoc-price-heading]');
      const saveDraftBtn = document.querySelector('[data-qdoc-save-draft]');
      const previewBtn = document.querySelector('[data-qdoc-preview]');
      const downloadButtons = document.querySelectorAll('[data-qdoc-download]');
      const customerAddBtn = document.querySelector('[data-qdoc-customer-add]');
      const paymentMethodSelect = document.querySelector('[data-qdoc-payment-method]');
      const previewModal = document.getElementById('qdoc-preview-modal');
      const previewIframe = document.getElementById('qdoc-preview-iframe');
      const previewLoading = document.getElementById('qdoc-preview-loading');
      const customerDialog = document.getElementById('qdoc-customer-dialog');
      const customerDialogStatus = document.getElementById('qdoc-customer-dialog-status');
      const customerDialogSaveBtn = document.querySelector('[data-qdoc-customer-save]');
      const dialogCloseButtons = document.querySelectorAll('[data-qdoc-dialog-close]');
      const previewCloseButtons = document.querySelectorAll('[data-qdoc-preview-close]');

      // ─── Formatters ───
      const moneyFmt = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      const qtyFmt = new Intl.NumberFormat('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
      const parseNum = (v) => { const n = parseFloat(String(v ?? '').replace(/,/g, '')); return isFinite(n) ? n : 0; };
      const roundMoney = (v) => Math.round((v + Number.EPSILON) * 100) / 100;
      const fmtMoney = (v) => moneyFmt.format(v);
      const fmtQty = (v) => qtyFmt.format(v);
      const sanitizeNum = (v) => {
        let s = String(v ?? '').replace(/[^\d.]/g, '');
        const dot = s.indexOf('.');
        return dot === -1 ? s : s.slice(0, dot + 1) + s.slice(dot + 1).replace(/\./g, '');
      };

      // ─── Document type ───
      let currentDocType = 'quotation';
      const typeConfig = {
        quotation: { th: 'ใบเสนอราคา', en: 'Quotation', prefix: 'QT' },
        invoice:   { th: 'ใบแจ้งหนี้',  en: 'Invoice',   prefix: 'IV' },
      };

      const setDocType = (type) => {
        currentDocType = type === 'invoice' ? 'invoice' : 'quotation';
        const cfg = typeConfig[currentDocType];
        docTypeButtons.forEach(b => b.classList.toggle('is-active', b.dataset.qdocDocType === currentDocType));
        if (typeLabel) typeLabel.textContent = cfg.en;
        if (docNumberInput?.dataset.autonumber === 'true') {
          docNumberInput.value = generateDocNumber();
        }
      };

      const twoDigit = (n) => String(n).padStart(2, '0');
      const generateDocNumber = () => {
        const cfg = typeConfig[currentDocType];
        const dateVal = docDateInput?.value || '';
        const d = dateVal ? new Date(dateVal + 'T00:00:00') : new Date();
        const yr = String(d.getFullYear()).slice(-2);
        const mo = twoDigit(d.getMonth() + 1);
        const dy = twoDigit(d.getDate());
        return `${cfg.prefix}-${yr}${mo}${dy}-001`;
      };

      docTypeButtons.forEach(b => b.addEventListener('click', () => setDocType(b.dataset.qdocDocType)));
      docNumberInput?.addEventListener('input', () => { docNumberInput.dataset.autonumber = 'false'; });
      docDateInput?.addEventListener('change', () => {
        if (docNumberInput?.dataset.autonumber === 'true') docNumberInput.value = generateDocNumber();
      });

      // ─── Customer ───
      const applyCustomer = (id) => {
        const c = customers.find(c => String(c.id) === String(id));
        if (customerNameInput) customerNameInput.value = c?.company_name || c?.display_name || '';
        if (customerTaxIdInput) customerTaxIdInput.value = c?.tax_id || '';
        if (customerAddressInput) customerAddressInput.value = c?.address || '';
        if (customerContactInput) customerContactInput.value = c?.contact_name || '';
        if (customerPhoneInput) customerPhoneInput.value = c?.phone || '';
        if (customerPaymentTermInput) customerPaymentTermInput.value = c?.payment_term || '';
      };

      customerSelect?.addEventListener('change', () => applyCustomer(customerSelect.value));

      // ─── Line items ───
      const getItemRows = () => Array.from(itemBody?.querySelectorAll('[data-qdoc-item-row]') ?? []);

      const buildRow = (index) => {
        const tr = document.createElement('tr');
        tr.setAttribute('data-qdoc-item-row', '');
        tr.innerHTML = `
          <td class="qdoc-col-index qdoc-row-index">${index}</td>
          <td class="qdoc-col-desc"><input type="text" class="qdoc-input qdoc-input--inline" data-qdoc-item-desc placeholder="ชื่อสินค้า/บริการ"></td>
          <td class="qdoc-col-qty"><input type="text" class="qdoc-input qdoc-input--inline qdoc-input--right" data-qdoc-item-qty inputmode="decimal" placeholder="0"></td>
          <td class="qdoc-col-unit"><input type="text" class="qdoc-input qdoc-input--inline qdoc-input--center" data-qdoc-item-unit placeholder="ชิ้น"></td>
          <td class="qdoc-col-price"><input type="text" class="qdoc-input qdoc-input--inline qdoc-input--right" data-qdoc-item-price inputmode="decimal" placeholder="0.00"></td>
          <td class="qdoc-col-amount"><output data-qdoc-item-amount>0.00</output></td>
          <td class="qdoc-col-del"><button type="button" class="qdoc-del-row-btn" data-qdoc-del-row title="ลบรายการ">✕</button></td>
        `;
        wireRowEvents(tr);
        return tr;
      };

      const reindexRows = () => {
        getItemRows().forEach((row, i) => {
          const idx = row.querySelector('.qdoc-row-index');
          if (idx) idx.textContent = i + 1;
        });
      };

      const wireRowEvents = (row) => {
        const qtyInput = row.querySelector('[data-qdoc-item-qty]');
        const priceInput = row.querySelector('[data-qdoc-item-price]');
        const delBtn = row.querySelector('[data-qdoc-del-row]');

        [qtyInput, priceInput].forEach(input => {
          if (!input) return;
          input.addEventListener('input', () => {
            const sanitized = sanitizeNum(input.value);
            if (input.value !== sanitized) { const pos = input.selectionStart ?? sanitized.length; input.value = sanitized; input.setSelectionRange(pos, pos); }
            syncTotals();
          });
          input.addEventListener('focus', () => { input.value = input.value.replace(/,/g, ''); });
          input.addEventListener('blur', () => {
            const parsed = parseNum(input.value);
            input.value = parsed === 0 && input.value.trim() === '' ? '' : (input.hasAttribute('data-qdoc-item-qty') ? fmtQty(parsed) : fmtMoney(parsed));
            syncTotals();
          });
          input.addEventListener('beforeinput', (e) => {
            if (!e.data || e.isComposing || e.inputType.startsWith('delete')) return;
            const cur = input.value || ''; const ss = input.selectionStart ?? cur.length; const se = input.selectionEnd ?? cur.length;
            const next = cur.slice(0, ss) + e.data + cur.slice(se);
            if (sanitizeNum(next) !== next) e.preventDefault();
          });
        });

        delBtn?.addEventListener('click', () => {
          if (getItemRows().length <= 1) { showStatus('ต้องมีรายการอย่างน้อย 1 รายการ', 'error'); return; }
          row.remove();
          reindexRows();
          syncTotals();
        });
      };

      addRowBtn?.addEventListener('click', () => {
        rowCounter += 1;
        const row = buildRow(rowCounter);
        itemBody?.appendChild(row);
        row.querySelector('[data-qdoc-item-desc]')?.focus();
      });

      // Wire first row
      getItemRows().forEach(row => wireRowEvents(row));

      // ─── Calculation mode ───
      discountRateInput?.addEventListener('input', () => {
        const sanitized = sanitizeNum(discountRateInput.value);
        if (discountRateInput.value !== sanitized) discountRateInput.value = sanitized;
        syncTotals();
      });

      const isReverseMode = () => calculationMode === 'reverse';

      const syncCalculationModeUi = () => {
        calculationModeButtons.forEach(button => {
          button.classList.toggle('is-active', button.dataset.qdocCalculationMode === calculationMode);
        });

        if (calculationCaption) {
          calculationCaption.textContent = isReverseMode()
            ? 'กรอกรายได้เป้าหมายหลัง WHT'
            : 'กรอกราคาค่าจ้างก่อน VAT';
        }

        if (priceHeading) {
          priceHeading.textContent = isReverseMode() ? 'รายได้เป้า/หน่วย' : 'ราคา/หน่วย';
        }

        if (targetIncomeRow) {
          targetIncomeRow.hidden = !isReverseMode();
        }
      };

      calculationModeButtons.forEach(button => button.addEventListener('click', () => {
        calculationMode = button.dataset.qdocCalculationMode === 'reverse' ? 'reverse' : 'standard';
        syncCalculationModeUi();
        syncTotals();
        showStatus(isReverseMode() ? 'คำนวณแบบ Reverse' : 'คำนวณแบบ Standard', 'info');
      }));

      const documentUnitPrice = (inputUnitPrice) => isReverseMode()
        ? inputUnitPrice / (1 - (WHT_RATE / 100))
        : inputUnitPrice;

      const collectItems = () => {
        let inputSubtotal = 0;
        let subtotal = 0;

        const items = getItemRows().map((row, i) => {
          const description = row.querySelector('[data-qdoc-item-desc]')?.value || '';
          const quantity = parseNum(row.querySelector('[data-qdoc-item-qty]')?.value);
          const unit = row.querySelector('[data-qdoc-item-unit]')?.value || '';
          const inputUnitPrice = parseNum(row.querySelector('[data-qdoc-item-price]')?.value);
          const inputAmount = roundMoney(quantity * inputUnitPrice);
          const unitPrice = documentUnitPrice(inputUnitPrice);
          const amount = roundMoney(quantity * unitPrice);

          inputSubtotal = roundMoney(inputSubtotal + inputAmount);
          subtotal = roundMoney(subtotal + amount);

          const amountOut = row.querySelector('[data-qdoc-item-amount]');
          if (amountOut) amountOut.textContent = fmtMoney(amount);

          return {
            index: i + 1,
            description,
            quantity,
            quantity_display: fmtQty(quantity),
            unit,
            input_unit_price: inputUnitPrice,
            input_unit_price_display: fmtMoney(inputUnitPrice),
            input_amount: inputAmount,
            input_amount_display: fmtMoney(inputAmount),
            unit_price: unitPrice,
            unit_price_display: fmtMoney(unitPrice),
            amount,
            amount_display: fmtMoney(amount),
          };
        });

        return { items, inputSubtotal, subtotal };
      };

      const calculateTotals = (inputSubtotal, subtotal) => {
        const discountRate = parseNum(discountRateInput?.value);
        const discountAmount = roundMoney(subtotal * discountRate / 100);
        const afterDiscount = roundMoney(subtotal - discountAmount);
        const inputDiscountAmount = roundMoney(inputSubtotal * discountRate / 100);
        const targetIncome = roundMoney(inputSubtotal - inputDiscountAmount);
        const vatAmount = roundMoney(afterDiscount * VAT_RATE / 100);
        const grandTotal = roundMoney(afterDiscount + vatAmount);
        const whtAmount = roundMoney(afterDiscount * WHT_RATE / 100);
        const netToPay = roundMoney(grandTotal - whtAmount);
        const serviceNetIncome = roundMoney(afterDiscount - whtAmount);

        return {
          discountRate,
          discountAmount,
          afterDiscount,
          inputDiscountAmount,
          targetIncome,
          vatAmount,
          grandTotal,
          whtAmount,
          netToPay,
          serviceNetIncome,
        };
      };

      // ─── Totals calculation ───
      const syncTotals = () => {
        const itemTotals = collectItems();
        const totals = calculateTotals(itemTotals.inputSubtotal, itemTotals.subtotal);

        if (subtotalOut) subtotalOut.textContent = fmtMoney(itemTotals.subtotal);
        if (discountAmountOut) discountAmountOut.textContent = fmtMoney(totals.discountAmount);
        if (afterDiscountOut) afterDiscountOut.textContent = fmtMoney(totals.afterDiscount);
        if (targetIncomeOut) targetIncomeOut.textContent = fmtMoney(totals.targetIncome);
        if (vatAmountOut) vatAmountOut.textContent = fmtMoney(totals.vatAmount);
        if (grandTotalOut) grandTotalOut.textContent = fmtMoney(totals.grandTotal);
        if (whtAmountOut) whtAmountOut.textContent = fmtMoney(totals.whtAmount);
        if (netToPayOut) netToPayOut.textContent = fmtMoney(totals.netToPay);
        if (serviceIncomeOut) serviceIncomeOut.textContent = fmtMoney(totals.serviceNetIncome);
        if (bahtTextOut) bahtTextOut.textContent = convertBahtText(totals.grandTotal);
      };

      // ─── Thai baht text (same logic) ───
      const convertBahtText = (amount) => {
        const digits = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
        const positions = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน'];
        const largeUnits = ['', 'ล้าน', 'ล้านล้าน'];
        const convertIntegerToThaiText = (n) => {
          if (n === 0) return 'ศูนย์';
          const parts = [];
          let groupIndex = 0;
          while (n > 0) {
            const group = n % 1000000; n = Math.floor(n / 1000000);
            const groupText = String(group).padStart(6, '0').split('').map((dc, idx, arr) => {
              const digit = parseInt(dc, 10); const pos = arr.length - idx - 1;
              if (digit === 0) return '';
              if (pos === 0 && digit === 1 && group > 9) return 'เอ็ด';
              if (pos === 1 && digit === 2) return 'ยี่สิบ';
              if (pos === 1 && digit === 1) return 'สิบ';
              return digits[digit] + positions[pos];
            }).join('');
            parts.unshift(groupText + (largeUnits[groupIndex] || '')); groupIndex += 1;
          }
          return parts.join('');
        };
        const rounded = Math.round((amount + Number.EPSILON) * 100) / 100;
        const intPart = Math.floor(rounded);
        const satPart = Math.round((rounded - intPart) * 100);
        const bahtText = convertIntegerToThaiText(intPart) + 'บาท';
        return satPart === 0 ? bahtText + 'ถ้วน' : bahtText + convertIntegerToThaiText(satPart) + 'สตางค์';
      };

      // ─── Status banner ───
      let statusTimeout;
      const showStatus = (msg, type = 'success') => {
        if (!statusEl) return;
        clearTimeout(statusTimeout);
        statusEl.textContent = msg;
        statusEl.dataset.type = type;
        statusEl.hidden = false;
        statusEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        statusTimeout = setTimeout(() => { statusEl.hidden = true; }, 5000);
      };

      // ─── Build payload ───
      const formatDate = (v) => {
        if (!v?.trim()) return '';
        const d = new Date(v + 'T00:00:00');
        if (isNaN(d.getTime())) return v;
        return [twoDigit(d.getDate()), twoDigit(d.getMonth() + 1), d.getFullYear()].join('/');
      };

      const buildPayload = (docType) => {
        const type = docType || currentDocType;
        const cfg = typeConfig[type];
        const selectedCustomer = customers.find(c => String(c.id) === String(customerSelect?.value ?? ''));
        const paymentMethod = paymentMethodSelect?.value || 'transfer';
        const itemTotals = collectItems();
        const totals = calculateTotals(itemTotals.inputSubtotal, itemTotals.subtotal);
        const docNumber = docNumberInput?.value || generateDocNumber();
        const calculationModeLabel = isReverseMode() ? 'Reverse Calculation' : 'Standard Calculation';

        return {
          document_type: type,
          document_number: docNumber,
          document_date: docDateInput?.value || '',
          due_date: dueDateInput?.value || '',
          customer_id: selectedCustomer?.id || null,
          customer_name: customerNameInput?.value || '',
          calculation_mode: calculationMode,
          calculation_mode_label: calculationModeLabel,
          calculator_mode: calculationMode,
          withholding_calculator_mode: 'customer',
          withholding_calculator_mode_label: 'คำนวณ WHT จากราคาก่อน VAT',
          withholding_tax_responsibility: 'customer',
          vat_calculator_mode: 'company',
          vat_calculator_mode_label: 'บวก VAT จากราคาก่อน VAT',
          vat_tax_responsibility: 'company',
          company: { name_th: 'บริษัท ซุปเปอร์นัมเบอร์ จำกัด (สำนักงานใหญ่)', name_en: 'SUPERNUMBER CO.,LTD.', address: '1418 ถนนพระรามที่ 4 แขวงคลองเตย เขตคลองเตย กรุงเทพมหานคร 10110\nTel. 096-323-2656 , 096-323-2665 E-Mail. superjimmy789@gmail.com', tax_id: '0105557133568' },
          document: { type, title_th: cfg.th, title_en: cfg.en, number: docNumber, date: docDateInput?.value || '', date_display: formatDate(docDateInput?.value), reference_number: refNumberInput?.value || '', due_date: dueDateInput?.value || '', due_date_display: formatDate(dueDateInput?.value) },
          customer: { customer_id: selectedCustomer?.id || null, name: customerNameInput?.value || '', tax_id: customerTaxIdInput?.value || '', address: customerAddressInput?.value || '', contact: customerContactInput?.value || '', phone: customerPhoneInput?.value || '', payment_term: customerPaymentTermInput?.value || '' },
          items: itemTotals.items,
          payment: {
            method: paymentMethod,
            cash: paymentMethod === 'cash',
            transfer: paymentMethod === 'transfer',
            cheque: paymentMethod === 'cheque',
            bank: document.querySelector('[data-qdoc-payment-bank]')?.value || '',
            branch: document.querySelector('[data-qdoc-payment-branch]')?.value || '',
            account_number: document.querySelector('[data-qdoc-payment-account]')?.value || '',
          },
          totals: {
            calculation_mode: calculationMode,
            calculation_mode_label: calculationModeLabel,
            input_subtotal: itemTotals.inputSubtotal,
            input_subtotal_display: fmtMoney(itemTotals.inputSubtotal),
            subtotal: itemTotals.subtotal,
            subtotal_display: fmtMoney(itemTotals.subtotal),
            discount_rate: totals.discountRate,
            discount_rate_display: String(totals.discountRate),
            discount_amount: totals.discountAmount,
            discount_amount_display: fmtMoney(totals.discountAmount),
            after_discount: totals.afterDiscount,
            after_discount_display: fmtMoney(totals.afterDiscount),
            target_income: totals.targetIncome,
            target_income_display: fmtMoney(totals.targetIncome),
            service_net_income: totals.serviceNetIncome,
            service_net_income_display: fmtMoney(totals.serviceNetIncome),
            vat_rate: VAT_RATE,
            vat_rate_display: String(VAT_RATE),
            vat_amount: totals.vatAmount,
            vat_amount_display: fmtMoney(totals.vatAmount),
            grand_total: totals.grandTotal,
            grand_total_display: fmtMoney(totals.grandTotal),
            withholding_rate: WHT_RATE,
            withholding_rate_display: String(WHT_RATE),
            withholding_amount: totals.whtAmount,
            withholding_amount_display: fmtMoney(totals.whtAmount),
            net_to_pay: totals.netToPay,
            net_to_pay_display: fmtMoney(totals.netToPay),
            baht_text: convertBahtText(totals.grandTotal),
            vat_calculator_mode: 'company',
            vat_calculator_mode_label: 'บวก VAT จากราคาก่อน VAT',
            vat_tax_responsibility: 'company',
            withholding_calculator_mode: 'customer',
            withholding_calculator_mode_label: 'คำนวณ WHT จากราคาก่อน VAT',
            withholding_tax_responsibility: 'customer',
          },
          signatures: {
            approved_by: document.querySelector('[data-qdoc-approved-by]')?.value || '',
            approved_date: document.querySelector('[data-qdoc-approved-date]')?.value || '',
            approved_date_display: formatDate(document.querySelector('[data-qdoc-approved-date]')?.value),
            accepted_by: document.querySelector('[data-qdoc-accepted-by]')?.value || '',
            accepted_date: document.querySelector('[data-qdoc-accepted-date]')?.value || '',
            accepted_date_display: formatDate(document.querySelector('[data-qdoc-accepted-date]')?.value),
          },
        };
      };

      // ─── Save Draft ───
      saveDraftBtn?.addEventListener('click', async () => {
        saveDraftBtn.disabled = true;
        try {
          const payload = buildPayload();
          const body = { draft_id: currentDraftId, document_date: docDateInput?.value || null, due_date: dueDateInput?.value || null, customer_id: customers.find(c => String(c.id) === String(customerSelect?.value ?? ''))?.id || null, customer_name: customerNameInput?.value || null, payload, _token: csrfToken };
          const res = await fetch(routes.saveDraft, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify(body) });
          const json = await res.json();
          if (!res.ok) throw new Error(json.message || 'ไม่สามารถบันทึกร่างได้');
          currentDraftId = json.draft_id;
          const newUrl = routes.quickBase + '?draft=' + currentDraftId;
          history.replaceState({}, '', newUrl);
          showStatus(json.message || 'บันทึกร่างเรียบร้อยแล้ว', 'success');
          // Add to draft list if new
          appendDraftToList({ id: currentDraftId, label: customerNameInput?.value || '', number: docNumberInput?.value || '', saved_at: new Date().toISOString() });
        } catch (e) {
          showStatus(e.message || 'เกิดข้อผิดพลาด', 'error');
        } finally {
          saveDraftBtn.disabled = false;
        }
      });

      const appendDraftToList = ({ id, label, number, saved_at }) => {
        const list = document.getElementById('qdoc-drafts-list');
        const empty = document.getElementById('qdoc-draft-empty');
        if (empty) empty.remove();
        const existing = list?.querySelector(`[data-draft-id="${id}"]`);
        if (existing) { existing.querySelector('.qdoc-draft-item__date').textContent = new Date(saved_at).toLocaleString('th-TH', { dateStyle: 'short', timeStyle: 'short' }); return; }
        const div = document.createElement('div');
        div.setAttribute('data-draft-id', String(id));
        div.className = 'qdoc-draft-item is-current';
        div.innerHTML = `<div class="qdoc-draft-item__info"><span class="qdoc-draft-item__name">${label || 'ไม่มีชื่อลูกค้า'}</span>${number ? `<span class="qdoc-draft-item__number">${number}</span>` : ''}<span class="qdoc-draft-item__date">${new Date(saved_at).toLocaleString('th-TH', { dateStyle: 'short', timeStyle: 'short' })}</span></div><div class="qdoc-draft-item__actions"><a href="${routes.quickBase}?draft=${id}" class="qdoc-draft-load-btn">โหลด</a><button type="button" class="qdoc-draft-del-btn" data-draft-delete="${id}" title="ลบร่าง">✕</button></div>`;
        list?.prepend(div);
        wireDraftDelete(div.querySelector('[data-draft-delete]'));
        const countEl = document.getElementById('qdoc-draft-count');
        if (countEl) countEl.textContent = String(parseInt(countEl.textContent || '0', 10) + 1);
      };

      // ─── Draft delete ───
      const wireDraftDelete = (btn) => {
        if (!btn) return;
        btn.addEventListener('click', async () => {
          const id = btn.dataset.draftDelete;
          if (!confirm('ลบร่างนี้หรือไม่?')) return;
          try {
            const res = await fetch(`${routes.deleteDraftBase}/${id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'ลบไม่ได้');
            btn.closest('[data-draft-id]')?.remove();
            if (String(currentDraftId) === String(id)) { currentDraftId = null; history.replaceState({}, '', routes.quickBase); }
            showStatus(json.message || 'ลบร่างเรียบร้อยแล้ว', 'success');
            const countEl = document.getElementById('qdoc-draft-count');
            if (countEl) { const n = Math.max(0, parseInt(countEl.textContent || '0', 10) - 1); countEl.textContent = String(n); }
            if (!document.querySelector('[data-draft-id]')) { const list = document.getElementById('qdoc-drafts-list'); if (list) { const p = document.createElement('p'); p.id = 'qdoc-draft-empty'; p.className = 'qdoc-draft-empty'; p.textContent = 'ยังไม่มีร่างเอกสาร'; list.appendChild(p); } }
          } catch (e) { showStatus(e.message || 'เกิดข้อผิดพลาด', 'error'); }
        });
      };
      document.querySelectorAll('[data-draft-delete]').forEach(wireDraftDelete);

      // ─── Preview ───
      previewBtn?.addEventListener('click', async () => {
        previewBtn.disabled = true;
        try {
          const payload = buildPayload();
          if (previewModal) previewModal.hidden = false;
          if (previewLoading) previewLoading.hidden = false;
          if (previewIframe) previewIframe.hidden = true;
          const res = await fetch(routes.previewHtml, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'text/html', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify({ payload, _token: csrfToken }) });
          if (!res.ok) throw new Error('ไม่สามารถโหลดตัวอย่างได้');
          const html = await res.text();
          const blob = new Blob([html], { type: 'text/html' });
          const url = URL.createObjectURL(blob);
          if (previewIframe) { previewIframe.src = url; previewIframe.hidden = false; previewIframe.onload = () => { if (previewLoading) previewLoading.hidden = true; URL.revokeObjectURL(url); }; }
        } catch (e) {
          if (previewLoading) previewLoading.textContent = e.message || 'เกิดข้อผิดพลาด';
        } finally {
          previewBtn.disabled = false;
        }
      });

      previewCloseButtons.forEach(b => b.addEventListener('click', () => {
        if (previewModal) previewModal.hidden = true;
        if (previewIframe) { previewIframe.src = 'about:blank'; previewIframe.hidden = true; }
        if (previewLoading) { previewLoading.hidden = false; previewLoading.textContent = 'กำลังโหลดตัวอย่าง...'; }
      }));

      // ─── Download ───
      downloadButtons.forEach(btn => {
        btn.addEventListener('click', async () => {
          const docType = btn.dataset.qdocDownload;
          btn.disabled = true;
          try {
            const payload = buildPayload(docType);
            const docNumber = payload.document_number;
            const body = { document_type: docType, document_number: docNumber, document_date: docDateInput?.value || null, due_date: dueDateInput?.value || null, customer_id: payload.customer_id, customer_name: payload.customer_name, payload, _token: csrfToken };
            const res = await fetch(routes.saveDownload, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify(body) });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'ไม่สามารถบันทึกเอกสารได้');
            showStatus(json.message || 'บันทึกเรียบร้อยแล้ว', 'success');
            window.open(json.download_url, '_blank');
            if (currentDraftId) {
              // Auto-delete draft if it exists since document is now finalized
              // (optional: comment out if user prefers to keep drafts)
            }
          } catch (e) {
            showStatus(e.message || 'เกิดข้อผิดพลาด', 'error');
          } finally {
            btn.disabled = false;
          }
        });
      });

      // ─── Customer dialog ───
      let customerDialogMode = 'create';
      let editingCustomerId = null;

      customerAddBtn?.addEventListener('click', () => openCustomerDialog('create'));

      const openCustomerDialog = (mode) => {
        customerDialogMode = mode;
        editingCustomerId = null;
        document.querySelector('[data-qdoc-customer-dialog-title]').textContent = 'เพิ่มลูกค้า';
        document.querySelector('[data-qdoc-customer-dialog-subtitle]').textContent = 'บันทึกข้อมูลลูกค้าใหม่';
        ['[data-qdoc-modal-company-name]', '[data-qdoc-modal-contact-name]', '[data-qdoc-modal-tax-id]', '[data-qdoc-modal-address]', '[data-qdoc-modal-email]', '[data-qdoc-modal-phone]'].forEach(sel => { const el = document.querySelector(sel); if (el) el.value = ''; });
        setCustomerDialogStatus('');
        if (customerDialog) customerDialog.hidden = false;
        document.querySelector('[data-qdoc-modal-company-name]')?.focus();
      };

      const closeCustomerDialog = () => { if (customerDialog) customerDialog.hidden = true; };

      const setCustomerDialogStatus = (msg, type = 'error') => {
        if (!customerDialogStatus) return;
        if (!msg) { customerDialogStatus.hidden = true; customerDialogStatus.textContent = ''; return; }
        customerDialogStatus.hidden = false;
        customerDialogStatus.textContent = msg;
        customerDialogStatus.dataset.type = type;
      };

      dialogCloseButtons.forEach(b => b.addEventListener('click', closeCustomerDialog));
      document.addEventListener('keydown', e => { if (e.key === 'Escape' && customerDialog && !customerDialog.hidden) closeCustomerDialog(); });

      customerDialogSaveBtn?.addEventListener('click', async () => {
        customerDialogSaveBtn.disabled = true;
        try {
          const companyName = document.querySelector('[data-qdoc-modal-company-name]')?.value?.trim() || '';
          const contactName = document.querySelector('[data-qdoc-modal-contact-name]')?.value?.trim() || '';
          const taxId = document.querySelector('[data-qdoc-modal-tax-id]')?.value?.trim() || '';
          const address = document.querySelector('[data-qdoc-modal-address]')?.value?.trim() || '';
          const email = document.querySelector('[data-qdoc-modal-email]')?.value?.trim() || '';
          const phone = document.querySelector('[data-qdoc-modal-phone]')?.value?.trim() || '';
          if (!companyName) { setCustomerDialogStatus('กรุณากรอกชื่อบริษัทหรือลูกค้า'); return; }
          const body = { company_name: companyName, contact_name: contactName, tax_id: taxId, address, email, phone, _token: csrfToken };
          const res = await fetch(@json(route('admin.customers.quick-store')), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify(body) });
          const json = await res.json();
          if (!res.ok) throw new Error(json.message || Object.values(json.errors || {})[0]?.[0] || 'เกิดข้อผิดพลาด');
          const newCustomer = json.customer;
          customers.push(newCustomer);
          const opt = new Option(newCustomer.display_name, String(newCustomer.id));
          customerSelect?.appendChild(opt);
          if (customerSelect) { customerSelect.value = String(newCustomer.id); applyCustomer(newCustomer.id); }
          setCustomerDialogStatus(json.message || 'บันทึกลูกค้าเรียบร้อยแล้ว', 'success');
          setTimeout(closeCustomerDialog, 800);
        } catch (e) { setCustomerDialogStatus(e.message || 'เกิดข้อผิดพลาด'); }
        finally { customerDialogSaveBtn.disabled = false; }
      });

      // ─── Apply prefill payload ───
      const applyPayload = (payload) => {
        if (!payload || typeof payload !== 'object') return;
        setDocType(payload.document_type || 'quotation');
        if (docNumberInput && payload.document_number) { docNumberInput.value = payload.document_number; docNumberInput.dataset.autonumber = 'false'; }
        if (docDateInput && payload.document_date) docDateInput.value = payload.document_date;
        if (dueDateInput && payload.due_date) dueDateInput.value = payload.due_date;
        if (refNumberInput && payload.document?.reference_number) refNumberInput.value = payload.document.reference_number;

        const c = payload.customer || {};
        if (customerNameInput) customerNameInput.value = c.name || '';
        if (customerTaxIdInput) customerTaxIdInput.value = c.tax_id || '';
        if (customerAddressInput) customerAddressInput.value = c.address || '';
        if (customerContactInput) customerContactInput.value = c.contact || c.contact_name || '';
        if (customerPhoneInput) customerPhoneInput.value = c.phone || '';
        if (customerPaymentTermInput) customerPaymentTermInput.value = c.payment_term || '';
        if (customerSelect && payload.customer_id) customerSelect.value = String(payload.customer_id);

        calculationMode = payload.calculation_mode === 'reverse'
          || payload.totals?.calculation_mode === 'reverse'
          || payload.tax_method === 'we-pay'
            ? 'reverse'
            : 'standard';
        syncCalculationModeUi();

        const t = payload.totals || {};
        if (discountRateInput && t.discount_rate !== undefined) discountRateInput.value = String(t.discount_rate);

        // Payment
        const p = payload.payment || {};
        if (paymentMethodSelect) {
          paymentMethodSelect.value = p.method || (p.cash ? 'cash' : (p.cheque ? 'cheque' : 'transfer'));
        }
        // Signatures
        const s = payload.signatures || {};
        const abEl = document.querySelector('[data-qdoc-approved-by]'); if (abEl) abEl.value = s.approved_by || '';
        const adEl = document.querySelector('[data-qdoc-approved-date]'); if (adEl && s.approved_date) adEl.value = s.approved_date;
        const acEl = document.querySelector('[data-qdoc-accepted-by]'); if (acEl) acEl.value = s.accepted_by || '';
        const acdEl = document.querySelector('[data-qdoc-accepted-date]'); if (acdEl && s.accepted_date) acdEl.value = s.accepted_date;

        // Items
        if (Array.isArray(payload.items) && payload.items.length > 0) {
          while (itemBody?.firstChild) itemBody.removeChild(itemBody.firstChild);
          rowCounter = 0;
          payload.items.forEach((item, i) => {
            rowCounter = i + 1;
            const row = buildRow(rowCounter);
            const descEl = row.querySelector('[data-qdoc-item-desc]'); if (descEl) descEl.value = item.description || item.name || '';
            const qtyEl = row.querySelector('[data-qdoc-item-qty]'); if (qtyEl) qtyEl.value = fmtQty(item.quantity || item.qty || 0);
            const unitEl = row.querySelector('[data-qdoc-item-unit]'); if (unitEl) unitEl.value = item.unit || '';
            const priceEl = row.querySelector('[data-qdoc-item-price]');
            if (priceEl) {
              const sourcePrice = item.input_unit_price ?? item.source_unit_price ?? item.originalPrice ?? item.unit_price ?? item.price ?? 0;
              priceEl.value = fmtMoney(sourcePrice);
            }
            itemBody?.appendChild(row);
          });
        }

        syncTotals();
      };

      // ─── Init ───
      if (prefillPayload) {
        applyPayload(prefillPayload);
        showStatus(currentDraftId ? 'โหลดร่างเอกสารเรียบร้อยแล้ว' : 'โหลดข้อมูลเอกสารเรียบร้อยแล้ว', 'info');
      } else {
        setDocType(initialDocumentType);
        syncCalculationModeUi();
        syncTotals();
      }
    })();
  </script>
@endsection
