@extends('layouts.admin')

@section('title', 'Supernumber Admin | รายการใบเสนอราคา / ใบแจ้งหนี้ทั้งหมด')

@section('content')
  @php
    $documentTypeLabels = [
      'quotation' => 'ใบเสนอราคา',
      'invoice' => 'ใบแจ้งหนี้',
    ];
  @endphp
  <div class="admin-page-head">
    <div>
      <h1>{{ $type === 'invoice' ? 'รายการใบแจ้งหนี้' : ($type === 'quotation' ? 'รายการใบเสนอราคา' : 'รายการใบเสนอราคา / ใบแจ้งหนี้ทั้งหมด') }}</h1>
      <p class="admin-subtitle">ดูย้อนหลังเอกสารทั้งหมดในรูปแบบตาราง พร้อมเปิดดูรายละเอียดหรือพิมพ์/บันทึก PDF ได้ทันที</p>
    </div>
    <div class="admin-page-actions">
      <div class="admin-summary">ทั้งหมด {{ number_format($documents->total()) }} เอกสาร</div>
    </div>
  </div>

  @if (session('status_message'))
    <div class="admin-alert admin-alert--success" style="margin-bottom: 18px;">{{ session('status_message') }}</div>
  @endif
  @if (session('status_error'))
    <div class="admin-alert admin-alert--error" style="margin-bottom: 18px;">{{ session('status_error') }}</div>
  @endif

  <style>
    /* Premium Dropdown CSS */
    .admin-dropdown {
      position: relative;
      display: inline-block;
    }
    .admin-dropdown-menu {
      display: none;
      position: absolute;
      right: 0;
      top: 100%;
      z-index: 1000;
      background: white;
      border: 1px solid var(--admin-border);
      border-radius: 8px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
      min-width: 180px;
      padding: 6px 0;
      margin-top: 4px;
    }
    .admin-dropdown-menu.is-open {
      display: block;
    }
    .admin-dropdown-item {
      display: block;
      width: 100%;
      padding: 8px 16px;
      font-size: 13px;
      text-align: left;
      color: #374151;
      background: none;
      border: none;
      text-decoration: none;
      cursor: pointer;
      box-sizing: border-box;
      transition: background-color 0.1s ease, color 0.1s ease;
    }
    .admin-dropdown-item:hover {
      background-color: #f3f4f6;
      color: #111827;
    }
  </style>

  @php
    $quotations = $documents->filter(fn($doc) => $doc->isQuotation());
    $invoices = $documents->filter(fn($doc) => $doc->isInvoice());
  @endphp

  {{-- 1. Quotations Section --}}
  @if (! $type || $type === 'quotation')
  <section class="admin-card admin-table-card" style="margin-bottom: 28px;">
    <div class="admin-feature-card__head" style="padding: 18px 20px 0;">
      <div>
        <h2 class="admin-feature-card__title" style="font-size: 18px; display: flex; align-items: center; gap: 8px;">
          <span>📄 รายการใบเสนอราคา (Quotations)</span>
        </h2>
        <p class="admin-feature-card__hint">ใบเสนอราคาที่ออกโดยระบบ สามารถยอมรับ ปฏิเสธ หรือแปลงเป็นใบแจ้งหนี้ได้</p>
      </div>
      <div class="admin-feature-card__actions" style="margin-top: 4px;">
        <div class="admin-dropdown">
          <button type="button" class="admin-button admin-button--primary admin-button--compact admin-dropdown-toggle" style="display: flex; align-items: center; gap: 4px;">
            สร้างใบเสนอราคา ▾
          </button>
          <div class="admin-dropdown-menu">
            <a href="{{ route('admin.sales-documents', ['type' => 'quotation']) }}" class="admin-dropdown-item">📝 สร้างแบบละเอียด (Editor)</a>
            <button type="button" class="admin-dropdown-item" data-easy-docs-open="quotation" style="color: #2563eb; font-weight: 500;">✨ สร้างด่วน (Easy Quotation)</button>
          </div>
        </div>
      </div>
    </div>
    <div class="admin-table-wrap" style="margin-top: 14px;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>เลขที่เอกสาร</th>
            <th>ลูกค้า</th>
            <th>สถานะ</th>
            <th>วันที่เอกสาร</th>
            <th>สร้างเมื่อ / สร้างโดย</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($quotations as $document)
            <tr>
              <td>
                <div class="admin-number" style="font-size: 14px; font-weight: 700;">{{ $document->document_number }}</div>
              </td>
              <td>{{ $document->customer_name ?: '-' }}</td>
              <td>
                <strong>{{ $document->status_label }}</strong>
                @if ($document->convertedInvoice)
                  <div class="admin-muted" style="margin-top: 6px;">
                    แปลงเป็น {{ $document->convertedInvoice->document_number }}
                  </div>
                @endif
              </td>
              <td>{{ $document->document_date?->format('d/m/Y') ?: '-' }}</td>
              <td>
                <div class="admin-muted">
                  {{ $document->created_at?->format('d/m/Y H:i') ?: '-' }}
                </div>
                <div class="admin-muted" style="margin-top: 4px; font-size: 0.9em;">
                  {{ $document->savedByUser?->name ?? '-' }}
                </div>
              </td>
              <td>
                <div class="admin-dropdown">
                  <button type="button" class="admin-button admin-button--compact admin-dropdown-toggle">
                    จัดการ ▾
                  </button>
                  <div class="admin-dropdown-menu">
                    <!-- Edit -->
                    @if (! $document->isInvoice() || $document->isInvoiceEditable())
                      <a href="{{ route('admin.sales-documents', ['document' => $document->id]) }}" class="admin-dropdown-item">แก้ไข</a>
                    @endif

                    <!-- Print / PDF -->
                    <a href="{{ route('admin.saved-sales-documents.download', $document) }}" class="admin-dropdown-item" target="_blank" rel="noopener">พิมพ์ / บันทึก PDF</a>

                    <!-- Workflow Actions -->
                    @if ($document->isQuotationDraft())
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.quotation-status', [$document, 'send']) }}" data-action-name="ส่งใบเสนอราคา" data-document-name="ใบเสนอราคา #{{ $document->document_number }}">ส่งใบเสนอราคา</button>
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.quotation-status', [$document, 'cancel']) }}" data-action-name="ยกเลิก" data-document-name="ใบเสนอราคา #{{ $document->document_number }}">ยกเลิก</button>
                    @elseif ($document->isQuotationSent())
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.quotation-status', [$document, 'accept']) }}" data-action-name="ยอมรับ" data-document-name="ใบเสนอราคา #{{ $document->document_number }}">ยอมรับ</button>
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.quotation-status', [$document, 'reject']) }}" data-action-name="ปฏิเสธ" data-document-name="ใบเสนอราคา #{{ $document->document_number }}">ปฏิเสธ</button>
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.quotation-status', [$document, 'expire']) }}" data-action-name="หมดอายุ" data-document-name="ใบเสนอราคา #{{ $document->document_number }}">หมดอายุ</button>
                    @elseif ($document->isQuotationAccepted())
                      @if ($document->convertedInvoice)
                        <a href="{{ route('admin.saved-sales-documents.show', $document->convertedInvoice) }}" class="admin-dropdown-item">
                          เปิด Invoice {{ $document->convertedInvoice->document_number }}
                        </a>
                      @else
                        <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.convert-to-invoice', $document) }}" data-action-name="แปลงเป็น Invoice" data-document-name="ใบเสนอราคา #{{ $document->document_number }}">แปลงเป็น Invoice</button>
                      @endif
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.quotation-status', [$document, 'cancel']) }}" data-action-name="ยกเลิก" data-document-name="ใบเสนอราคา #{{ $document->document_number }}">ยกเลิก</button>
                    @endif

                    <!-- Delete -->
                    @if (session('admin_user_role') === \App\Models\User::ROLE_MANAGER)
                      <div style="border-top: 1px solid var(--admin-border); margin: 4px 0;"></div>
                      <button type="button" class="admin-dropdown-item doc-delete-btn" data-action-url="{{ route('admin.saved-sales-documents.delete', $document) }}" data-document-name="ใบเสนอราคา #{{ $document->document_number }}" style="color: #b42318;">ลบเอกสาร</button>
                    @endif
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="admin-muted">ยังไม่มีใบเสนอราคาที่บันทึกไว้</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
  @endif

  {{-- 2. Invoices Section --}}
  @if (! $type || $type === 'invoice')
  <section class="admin-card admin-table-card">
    <div class="admin-feature-card__head" style="padding: 18px 20px 0;">
      <div>
        <h2 class="admin-feature-card__title" style="font-size: 18px; display: flex; align-items: center; gap: 8px;">
          <span>🧾 รายการใบแจ้งหนี้ (Invoices)</span>
        </h2>
        <p class="admin-feature-card__hint">ใบแจ้งหนี้ที่ออกโดยระบบ สามารถอัปเดตสถานะการรับเงินได้</p>
      </div>
      <div class="admin-feature-card__actions" style="margin-top: 4px;">
        <div class="admin-dropdown">
          <button type="button" class="admin-button admin-button--primary admin-button--compact admin-dropdown-toggle" style="display: flex; align-items: center; gap: 4px;">
            สร้างใบแจ้งหนี้ ▾
          </button>
          <div class="admin-dropdown-menu">
            <a href="{{ route('admin.sales-documents', ['type' => 'invoice']) }}" class="admin-dropdown-item">📝 สร้างแบบละเอียด (Editor)</a>
            <button type="button" class="admin-dropdown-item" data-easy-docs-open="invoice" style="color: #2563eb; font-weight: 500;">✨ สร้างด่วน (Easy Invoice)</button>
          </div>
        </div>
      </div>
    </div>
    <div class="admin-table-wrap" style="margin-top: 14px;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>เลขที่เอกสาร</th>
            <th>ลูกค้า</th>
            <th>สถานะ</th>
            <th>วันที่เอกสาร</th>
            <th>สร้างเมื่อ / สร้างโดย</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($invoices as $document)
            <tr>
              <td>
                <div class="admin-number" style="font-size: 14px; font-weight: 700;">{{ $document->document_number }}</div>
              </td>
              <td>{{ $document->customer_name ?: '-' }}</td>
              <td>
                <strong>{{ $document->status_label }}</strong>
              </td>
              <td>{{ $document->document_date?->format('d/m/Y') ?: '-' }}</td>
              <td>
                <div class="admin-muted">
                  {{ $document->created_at?->format('d/m/Y H:i') ?: '-' }}
                </div>
                <div class="admin-muted" style="margin-top: 4px; font-size: 0.9em;">
                  {{ $document->savedByUser?->name ?? '-' }}
                </div>
              </td>
              <td>
                <div class="admin-dropdown">
                  <button type="button" class="admin-button admin-button--compact admin-dropdown-toggle">
                    จัดการ ▾
                  </button>
                  <div class="admin-dropdown-menu">
                    <!-- Edit -->
                    @if (! $document->isInvoice() || $document->isInvoiceEditable())
                      <a href="{{ route('admin.sales-documents', ['document' => $document->id]) }}" class="admin-dropdown-item">แก้ไข</a>
                    @endif

                    <!-- Print / PDF -->
                    <a href="{{ route('admin.saved-sales-documents.download', $document) }}" class="admin-dropdown-item" target="_blank" rel="noopener">พิมพ์ / บันทึก PDF</a>

                    <!-- Workflow Actions -->
                    @if ($document->isInvoiceDraft())
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'issue']) }}" data-action-name="ออกใบแจ้งหนี้" data-document-name="ใบแจ้งหนี้ #{{ $document->document_number }}">ออกใบแจ้งหนี้</button>
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'void']) }}" data-action-name="Void" data-document-name="ใบแจ้งหนี้ #{{ $document->document_number }}">Void</button>
                    @elseif ($document->isInvoiceIssued())
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'partial-paid']) }}" data-action-name="ชำระบางส่วน" data-document-name="ใบแจ้งหนี้ #{{ $document->document_number }}">ชำระบางส่วน</button>
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'paid']) }}" data-action-name="ชำระแล้ว" data-document-name="ใบแจ้งหนี้ #{{ $document->document_number }}">ชำระแล้ว</button>
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'overdue']) }}" data-action-name="ค้างชำระ" data-document-name="ใบแจ้งหนี้ #{{ $document->document_number }}">ค้างชำระ</button>
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'void']) }}" data-action-name="Void" data-document-name="ใบแจ้งหนี้ #{{ $document->document_number }}">Void</button>
                    @elseif ($document->status === \App\Models\SalesDocument::STATUS_INVOICE_PARTIALLY_PAID)
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'paid']) }}" data-action-name="ชำระแล้ว" data-document-name="ใบแจ้งหนี้ #{{ $document->document_number }}">ชำระแล้ว</button>
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'overdue']) }}" data-action-name="ค้างชำระ" data-document-name="ใบแจ้งหนี้ #{{ $document->document_number }}">ค้างชำระ</button>
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'void']) }}" data-action-name="Void" data-document-name="ใบแจ้งหนี้ #{{ $document->document_number }}">Void</button>
                    @elseif ($document->status === \App\Models\SalesDocument::STATUS_INVOICE_OVERDUE)
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'paid']) }}" data-action-name="ชำระแล้ว" data-document-name="ใบแจ้งหนี้ #{{ $document->document_number }}">ชำระแล้ว</button>
                      <button type="button" class="admin-dropdown-item workflow-action-btn" data-action-url="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'void']) }}" data-action-name="Void" data-document-name="ใบแจ้งหนี้ #{{ $document->document_number }}">Void</button>
                    @endif

                    <!-- Delete -->
                    @if (session('admin_user_role') === \App\Models\User::ROLE_MANAGER)
                      <div style="border-top: 1px solid var(--admin-border); margin: 4px 0;"></div>
                      <button type="button" class="admin-dropdown-item doc-delete-btn" data-action-url="{{ route('admin.saved-sales-documents.delete', $document) }}" data-document-name="ใบแจ้งหนี้ #{{ $document->document_number }}" style="color: #b42318;">ลบเอกสาร</button>
                    @endif
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="admin-muted">ยังไม่มีใบแจ้งหนี้ที่บันทึกไว้</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
  @endif

  <div style="margin-top: 18px;">
    {{ $documents->links() }}
  </div>

  {{-- Unified Action Confirmation Modal --}}
  <div id="action-confirmation-modal" style="display: none; position: fixed; inset: 0; z-index: 2000; background: rgba(0, 0, 0, 0.5); align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 24px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); width: min(450px, 90vw);">
      <h2 style="margin: 0 0 12px; font-size: 18px; font-weight: 600; color: #111827;" id="action-modal-title">⚠️ ยืนยันการดำเนินการ</h2>
      <p style="margin: 0 0 12px; color: #6b7280; font-size: 14px;" id="action-modal-text">คุณแน่ใจว่าต้องการดำเนินการกับเอกสารนี้หรือไม่?</p>
      <p style="margin: 0 0 16px; padding: 12px; border-radius: 6px; border-left: 4px solid #3b82f6; font-weight: 500;" id="action-doc-name">-</p>
      <p style="margin: 0 0 12px; color: #dc2626; font-size: 13px; font-weight: 600;" id="action-modal-warning"></p>
      <p style="margin: 0 0 8px; color: #111827; font-size: 13px; font-weight: 600;">
        พิมพ์ <code style="background: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-family: monospace; color: #dc2626;" id="action-required-word">Confirm</code> เพื่อยืนยัน:
      </p>
      <input type="text" id="action-confirm-input" placeholder="พิมพ์ Confirm" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box; margin-bottom: 16px;">
      <div style="display: flex; gap: 8px; justify-content: flex-end;">
        <button type="button" class="admin-button admin-button--ghost" onclick="closeActionModal();">ยกเลิก</button>
        <button type="button" id="action-confirm-btn" class="admin-button admin-button--primary" style="opacity: 0.5; cursor: not-allowed;" disabled onclick="submitActionForm();">ยืนยัน</button>
      </div>
    </div>
  </div>

  {{-- Hidden Action Form --}}
  <form id="hidden-action-form" method="post" style="display: none;">
    @csrf
    <input type="hidden" name="_method" id="hidden-action-method" value="POST">
  </form>

  {{-- Easy Documents Wizard Modal --}}
  <div id="easy-docs-modal" class="easy-docs-modal" hidden>
    <div class="easy-docs-backdrop" data-easy-docs-close></div>
    <div class="easy-docs-panel">
      <div class="easy-docs-header">
        <h2 id="easy-docs-title" class="easy-docs-title">ขั้นตอน 1: เลือกลูกค้า</h2>
        <div class="easy-docs-step-indicator">
          <span id="easy-docs-step" class="easy-docs-step">1 / 4</span>
        </div>
        <button type="button" class="easy-docs-close" data-easy-docs-close aria-label="ปิด">✕</button>
      </div>

      <div class="easy-docs-body">
        {{-- Step 1: Customer Selection --}}
        <div id="easy-docs-step-1" class="easy-docs-step-content is-active">
          {{-- Quotation Selection (For Invoice) --}}
          <div id="easy-docs-quotation-field-container" class="easy-docs-field" style="display: none; margin-bottom: 16px;">
            <label class="easy-docs-label">
              <span>เลือกใบเสนอราคา (Quotation) / ค้นหาใบเสนอราคา</span>
              <input type="text" id="easy-docs-quotation-search" class="easy-docs-input" placeholder="พิมพ์เลขที่เอกสาร หรือ ชื่อลูกค้า เพื่อค้นหา..." style="margin-bottom: 8px;">
              <select id="easy-docs-quotation" class="easy-docs-input" size="5" style="padding-top: 4px; padding-bottom: 4px;">
                <option value="">-- เลือกใบเสนอราคา --</option>
                @foreach ($allQuotations ?? [] as $qt)
                  <option value="{{ $qt->id }}">{{ $qt->document_number }} - {{ $qt->customer_name }} (฿{{ number_format($qt->payload['totals']['net_to_pay'] ?? $qt->payload['total'] ?? 0, 2) }})</option>
                @endforeach
              </select>
            </label>
          </div>

          {{-- Customer Selection (For Quotation) --}}
          <div id="easy-docs-customer-field-container">
            <div class="easy-docs-field">
              <label class="easy-docs-label">
                <span>เลือกลูกค้า</span>
                <select id="easy-docs-customer" class="easy-docs-input">
                  <option value="">-- เลือกลูกค้า --</option>
                  @foreach ($customers ?? [] as $customer)
                    <option value="{{ $customer->id }}" data-phone="{{ $customer->phone ?? '' }}" data-contact="{{ $customer->contact_name ?? '' }}">{{ $customer->display_name }}</option>
                  @endforeach
                </select>
              </label>
            </div>
            <button type="button" class="easy-docs-button easy-docs-button--link" data-easy-docs-create-customer>
              ➕ สร้างลูกค้าใหม่
            </button>
          </div>

          {{-- Customer Details Section (shown after selection) --}}
          <div id="easy-docs-customer-details" class="easy-docs-section" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <h3 class="easy-docs-section-title">📋 รายละเอียดลูกค้า</h3>
            <div class="easy-docs-field">
              <label class="easy-docs-label">
                <span>ชื่อผู้ติดต่อ</span>
                <input type="text" id="easy-docs-contact-name" class="easy-docs-input" placeholder="ชื่อผู้ติดต่อ">
              </label>
            </div>
            <div class="easy-docs-field">
              <label class="easy-docs-label">
                <span>เบอร์ติดต่อ</span>
                <input type="tel" id="easy-docs-contact-phone" class="easy-docs-input" placeholder="เบอร์ติดต่อ">
              </label>
            </div>
          </div>
        </div>

        {{-- Step 2: Products & Tax Method --}}
        <div id="easy-docs-step-2" class="easy-docs-step-content">
          <div class="easy-docs-section">
            <h3 class="easy-docs-section-title">📦 รายการสินค้า</h3>

            {{-- Product Input Form --}}
            <div id="easy-docs-product-form" class="easy-docs-product-form">
              <div style="display: grid; grid-template-columns: 1fr 80px 80px; gap: 8px; margin-bottom: 12px;">
                <input type="text" id="easy-docs-product-name" class="easy-docs-input" placeholder="ชื่อสินค้า/บริการ">
                <input type="number" id="easy-docs-product-price" class="easy-docs-input" placeholder="ราคา" min="0" step="0.01">
                <input type="number" id="easy-docs-product-qty" class="easy-docs-input" placeholder="จำนวน" value="1" min="1">
              </div>
              <button type="button" class="easy-docs-button easy-docs-button--secondary" id="easy-docs-add-item-btn" style="width: 100%;">
                ➕ เพิ่มรายการ
              </button>
            </div>

            {{-- Items List --}}
            <div id="easy-docs-items-list" class="easy-docs-items-list"></div>
          </div>

          <div class="easy-docs-section">
            <h3 class="easy-docs-section-title">💰 Calculation Method</h3>
            <div class="easy-docs-radio-group">
              <label class="easy-docs-radio-label">
                <input type="radio" name="tax-method" value="customer-pays" checked>
                <span>Standard - Set Base Price</span>
              </label>
              <label class="easy-docs-radio-label">
                <input type="radio" name="tax-method" value="we-pay">
                <span>Reverse - Set Target Income</span>
              </label>
            </div>
          </div>

          {{-- Calculate Button (shows when subtotal >= 50,000) --}}
          <div id="easy-docs-calculate-section" class="easy-docs-section" style="display: none;">
            <button type="button" class="easy-docs-button easy-docs-button--secondary" id="easy-docs-calculate-btn" style="width: 100%;">
              🧮 คำนวนราคา
            </button>
          </div>

          {{-- Detailed Pricing Breakdown --}}
          <div id="easy-docs-pricing-breakdown" class="easy-docs-section" style="display: none;">
            <h3 class="easy-docs-section-title">💵 รายละเอียดราคา</h3>
            <div class="easy-docs-pricing-table">
              <div class="easy-docs-pricing-row">
                <span>ราคารวมก่อนภาษี:</span>
                <strong id="easy-docs-subtotal">฿0.00</strong>
              </div>
              <div class="easy-docs-pricing-row">
                <span>ภาษีมูลค่าเพิ่ม (7%):</span>
                <strong id="easy-docs-vat">+ ฿0.00</strong>
              </div>
              <div class="easy-docs-pricing-row easy-docs-pricing-row--divider">
                <span>ยอดรวมทั้งหมด:</span>
                <strong id="easy-docs-grand-total">฿0.00</strong>
              </div>
              <div class="easy-docs-pricing-row">
                <span>ภาษีหัก ณ ที่จ่าย (3%):</span>
                <strong id="easy-docs-wht">- ฿0.00</strong>
              </div>
              <div class="easy-docs-pricing-row easy-docs-pricing-row--highlight">
                <span>ยอดสุทธิที่ลูกค้าต้องชำระ:</span>
                <strong id="easy-docs-net-payment">฿0.00</strong>
              </div>
            </div>
          </div>

          <div class="easy-docs-summary">
            <span>ราคารวม:</span>
            <strong id="easy-docs-total">฿0.00</strong>
          </div>
        </div>

        {{-- Step 3: Payment Method & Conditions --}}
        <div id="easy-docs-step-3" class="easy-docs-step-content">
          <div class="easy-docs-section" style="display: none !important;">
            <h3 class="easy-docs-section-title">📄 ประเภทเอกสาร</h3>
            <div class="easy-docs-radio-group">
              <label class="easy-docs-radio-label">
                <input type="radio" name="document-type" value="quotation" checked>
                <span>ใบเสนอราคา (Quotation)</span>
              </label>
              <label class="easy-docs-radio-label">
                <input type="radio" name="document-type" value="invoice">
                <span>ใบแจ้งหนี้ (Invoice)</span>
              </label>
            </div>
          </div>

          <div class="easy-docs-section">
            <h3 class="easy-docs-section-title">💳 วิธีการชำระเงิน</h3>
            <div class="easy-docs-radio-group">
              <label class="easy-docs-radio-label">
                <input type="radio" name="payment-method" value="bank" checked>
                <span>ธนาคาร</span>
              </label>
              <label class="easy-docs-radio-label">
                <input type="radio" name="payment-method" value="qr">
                <span>โอน QR</span>
              </label>
              <label class="easy-docs-radio-label">
                <input type="radio" name="payment-method" value="cash">
                <span>เงินสด</span>
              </label>
            </div>
          </div>

          <div class="easy-docs-section">
            <h3 class="easy-docs-section-title">⏰ เงื่อนการชำระเงิน</h3>
            <div class="easy-docs-radio-group">
              <label class="easy-docs-radio-label">
                <input type="radio" name="payment-condition" value="full" checked>
                <span>ชำระทั้งหมด</span>
              </label>
              <label class="easy-docs-radio-label">
                <input type="radio" name="payment-condition" value="installment">
                <span>ชำระงวด</span>
              </label>
              <label class="easy-docs-radio-label">
                <input type="radio" name="payment-condition" value="specific-date">
                <span>ชำระตามกำหนดวัน</span>
              </label>
            </div>
            <div id="easy-docs-payment-detail" style="margin-top: 12px; display: none;">
              <input type="text" id="easy-docs-payment-detail-input" class="easy-docs-input" placeholder="กรอกรายละเอียด">
            </div>
          </div>
        </div>

        {{-- Step 4: Summary --}}
        <div id="easy-docs-step-4" class="easy-docs-step-content">
          <div class="easy-docs-summary-section">
            <div class="easy-docs-summary-row">
              <span>ลูกค้า:</span>
              <strong id="easy-docs-summary-customer">-</strong>
            </div>

            <!-- Items List -->
            <div style="margin: 16px 0; border-top: 1px solid #e5e7eb; padding-top: 12px;">
              <div style="font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 8px;">รายการสินค้า</div>
              <div id="easy-docs-summary-items-list" style="font-size: 12px; color: #374151;"></div>
            </div>

            <!-- Pricing Breakdown -->
            <div style="margin: 16px 0; border-top: 1px solid #e5e7eb; padding-top: 12px;">
              <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                <span style="color: #6b7280;">ราคารวมก่อนภาษี:</span>
                <strong id="easy-docs-summary-subtotal">฿0.00</strong>
              </div>
              <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                <span style="color: #6b7280;">ภาษีมูลค่าเพิ่ม (7%):</span>
                <strong id="easy-docs-summary-vat">+ ฿0.00</strong>
              </div>
              <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 2px solid #bfdbfe; margin-bottom: 8px;">
                <span style="color: #6b7280;">ยอดรวมทั้งหมด:</span>
                <strong id="easy-docs-summary-grand-total">฿0.00</strong>
              </div>
              <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                <span style="color: #6b7280;">ภาษีหัก ณ ที่จ่าย (3%):</span>
                <strong id="easy-docs-summary-wht">- ฿0.00</strong>
              </div>
            </div>

            <div class="easy-docs-summary-row">
              <span>วิธีชำระ:</span>
              <strong id="easy-docs-summary-payment">-</strong>
            </div>
            <div class="easy-docs-summary-row">
              <span>เงื่อนการชำระ:</span>
              <strong id="easy-docs-summary-condition">-</strong>
            </div>
            <div class="easy-docs-summary-row easy-docs-summary-row--highlight">
              <span>ราคารวม (สุทธิ):</span>
              <strong id="easy-docs-summary-total">฿0.00</strong>
            </div>
          </div>
        </div>
      </div>

      <div class="easy-docs-footer">
        <button type="button" class="admin-button admin-button--ghost" data-easy-docs-close>ยกเลิก</button>
        <button type="button" class="admin-button" id="easy-docs-prev-btn" style="display: none;">← ย้อนกลับ</button>
        <button type="button" class="admin-button admin-button--primary" id="easy-docs-next-btn">ถัดไป →</button>
      </div>
    </div>
  </div>

  <style>
    /* Easy Documents Wizard Styles */
    .easy-docs-modal {
      position: fixed;
      inset: 0;
      z-index: 2000;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .easy-docs-modal[hidden] {
      display: none;
    }
    .easy-docs-backdrop {
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      cursor: pointer;
    }
    .easy-docs-panel {
      position: relative;
      z-index: 1;
      background: #fff;
      border-radius: 12px;
      width: min(600px, 95vw);
      height: min(90vh, 100%);
      max-height: 90vh;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      display: flex;
      flex-direction: column;
    }
    .easy-docs-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding: 20px;
      border-bottom: 1px solid #e5e7eb;
      flex-shrink: 0;
    }
    .easy-docs-title {
      margin: 0;
      font-size: 18px;
      font-weight: 600;
      color: #111827;
    }
    .easy-docs-step-indicator {
      font-size: 12px;
      color: #6b7280;
      background: #f3f4f6;
      padding: 4px 10px;
      border-radius: 6px;
    }
    .easy-docs-close {
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
      color: #9ca3af;
      padding: 0 8px;
      transition: color 0.15s;
    }
    .easy-docs-close:hover {
      color: #374151;
    }
    .easy-docs-body {
      flex: 1;
      min-height: 0;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 20px;
      -webkit-overflow-scrolling: touch;
    }
    .easy-docs-step-content {
      display: none;
    }
    .easy-docs-step-content.is-active {
      display: block;
    }
    .easy-docs-section {
      margin-bottom: 20px;
    }
    .easy-docs-section-title {
      margin: 0 0 12px;
      font-size: 13px;
      font-weight: 600;
      color: #374151;
    }
    .easy-docs-field {
      margin-bottom: 12px;
    }
    .easy-docs-label {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .easy-docs-label span {
      font-size: 12px;
      font-weight: 600;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }
    .easy-docs-input {
      border: 1px solid #d1d5db;
      border-radius: 6px;
      padding: 8px 12px;
      font-size: 13px;
      width: 100%;
      box-sizing: border-box;
    }
    .easy-docs-input:focus {
      outline: none;
      border-color: #2563eb;
      box-shadow: 0 0 0 2px #dbeafe;
    }
    .easy-docs-radio-group {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .easy-docs-radio-label {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      font-size: 13px;
      color: #111827;
    }
    .easy-docs-radio-label input {
      margin: 0;
      cursor: pointer;
    }
    .easy-docs-button {
      padding: 8px 16px;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      background: #fff;
      font-size: 13px;
      cursor: pointer;
      transition: all 0.15s;
    }
    .easy-docs-button:hover {
      border-color: #9ca3af;
      background: #f9fafb;
    }
    .easy-docs-button--secondary {
      background: #f0f9ff;
      border-color: #bfdbfe;
      color: #1e40af;
    }
    .easy-docs-button--secondary:hover {
      background: #e0f2fe;
    }
    .easy-docs-button--link {
      border: none;
      background: none;
      color: #2563eb;
      text-decoration: none;
      padding: 4px 0;
      margin-top: 8px;
    }
    .easy-docs-button--link:hover {
      text-decoration: underline;
    }
    .easy-docs-summary {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px;
      background: #f3f4f6;
      border-radius: 6px;
      font-size: 13px;
    }
    .easy-docs-summary strong {
      font-size: 16px;
      color: #2563eb;
    }
    .easy-docs-summary-section {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 16px;
    }
    .easy-docs-summary-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid #e5e7eb;
      font-size: 13px;
    }
    .easy-docs-summary-row:last-child {
      border-bottom: none;
    }
    .easy-docs-summary-row--highlight {
      background: #eff6ff;
      padding: 8px 12px;
      margin: 0 -16px;
      padding-left: 16px;
      padding-right: 16px;
      border-top: 2px solid #bfdbfe;
      border-bottom: none;
      font-weight: 600;
      font-size: 14px;
    }
    .easy-docs-summary-row strong {
      font-weight: 600;
      color: #111827;
    }
    .easy-docs-items-list {
      margin-bottom: 12px;
    }
    .easy-docs-item {
      padding: 10px;
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      margin-bottom: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 12px;
    }
    .easy-docs-item-text {
      flex: 1;
    }
    .easy-docs-item-delete {
      background: none;
      border: none;
      color: #d1d5db;
      cursor: pointer;
      font-size: 16px;
      padding: 0 4px;
    }
    .easy-docs-item-delete:hover {
      color: #ef4444;
    }
    .easy-docs-product-form {
      background: #f9fafb;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 12px;
      border: 1px solid #e5e7eb;
    }
    .easy-docs-pricing-table {
      display: flex;
      flex-direction: column;
      gap: 0;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      overflow: hidden;
      background: #fff;
    }
    .easy-docs-pricing-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 12px;
      border-bottom: 1px solid #f3f4f6;
      font-size: 13px;
    }
    .easy-docs-pricing-row:last-child {
      border-bottom: none;
    }
    .easy-docs-pricing-row--divider {
      border-bottom: 2px solid #bfdbfe;
      background: #eff6ff;
      font-weight: 600;
    }
    .easy-docs-pricing-row--highlight {
      background: #f0fdf4;
      border-bottom: none;
      font-weight: 600;
      color: #15803d;
    }
    .easy-docs-footer {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      padding: 16px 20px;
      border-top: 1px solid #e5e7eb;
      flex-shrink: 0;
    }
  </style>

  <script>
    (() => {
      const modal = document.getElementById('easy-docs-modal');
      const backdrop = document.querySelector('.easy-docs-backdrop');
      const openBtns = document.querySelectorAll('[data-easy-docs-open]');
      const closeBtn = document.querySelectorAll('[data-easy-docs-close]');
      const nextBtn = document.getElementById('easy-docs-next-btn');
      const prevBtn = document.getElementById('easy-docs-prev-btn');
      const customerSelect = document.getElementById('easy-docs-customer');
      const taxMethodRadios = document.querySelectorAll('input[name="tax-method"]');
      const paymentConditionRadios = document.querySelectorAll('input[name="payment-condition"]');
      const paymentDetailDiv = document.getElementById('easy-docs-payment-detail');
      const addItemBtn = document.getElementById('easy-docs-add-item-btn');
      const productNameInput = document.getElementById('easy-docs-product-name');
      const productPriceInput = document.getElementById('easy-docs-product-price');
      const productQtyInput = document.getElementById('easy-docs-product-qty');
      const itemsList = document.getElementById('easy-docs-items-list');
      const totalDisplay = document.getElementById('easy-docs-total');

      // Calculation elements
      const calculateBtn = document.getElementById('easy-docs-calculate-btn');
      const calculateSection = document.getElementById('easy-docs-calculate-section');
      const pricingBreakdown = document.getElementById('easy-docs-pricing-breakdown');

      let currentStep = 1;
      const totalSteps = 4;
      const wizardData = {
        customerId: null,
        customerName: '',
        contactName: '',
        contactPhone: '',
        items: [],
        taxMethod: 'customer-pays',
        paymentMethod: 'bank',
        paymentCondition: 'full',
        paymentDetail: '',
        documentType: 'quotation',
        referenceNumber: '',
      };

      const customers = @json($customers ?? []);
      const quotationsMap = {};
      @foreach ($allQuotations ?? [] as $qt)
        quotationsMap[{{ $qt->id }}] = @json($qt);
      @endforeach
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
      const createEasyDocumentUrl = @json(route('admin.easy-documents.create', [], false));

      // Open modal
      openBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
          const type = e.currentTarget.getAttribute('data-easy-docs-open') || 'quotation';
          modal.removeAttribute('hidden');
          currentStep = 1;
          wizardData.items = [];
          wizardData.customerId = null;
          wizardData.customerName = '';
          wizardData.contactName = '';
          wizardData.contactPhone = '';
          wizardData.referenceNumber = '';
          customerSelect.value = '';

          const quotationSelect = document.getElementById('easy-docs-quotation');
          if (quotationSelect) quotationSelect.value = '';

          const quotationFieldContainer = document.getElementById('easy-docs-quotation-field-container');
          const customerFieldContainer = document.getElementById('easy-docs-customer-field-container');

          if (type === 'invoice') {
            if (quotationFieldContainer) quotationFieldContainer.style.display = 'block';
            if (customerFieldContainer) customerFieldContainer.style.display = 'none';
          } else {
            if (quotationFieldContainer) quotationFieldContainer.style.display = 'none';
            if (customerFieldContainer) customerFieldContainer.style.display = 'block';
          }

          customerDetailsSection.style.display = 'none';
          contactNameInput.value = '';
          contactPhoneInput.value = '';
          productNameInput.value = '';
          productPriceInput.value = '';
          productQtyInput.value = '1';
          pricingBreakdown.style.display = 'none';
          calculateSection.style.display = 'none';
          
          const docTypeRadio = document.querySelector(`input[name="document-type"][value="${type}"]`);
          if (docTypeRadio) docTypeRadio.checked = true;
          wizardData.documentType = type;
          syncItemsForTaxMethod();
          showStep(1);
        });
      });

      // Customer selection and details
      const customerDetailsSection = document.getElementById('easy-docs-customer-details');
      const contactNameInput = document.getElementById('easy-docs-contact-name');
      const contactPhoneInput = document.getElementById('easy-docs-contact-phone');

      customerSelect?.addEventListener('change', (e) => {
        const selectedOption = e.target.options[e.target.selectedIndex];
        if (e.target.value) {
          // Show customer details section
          customerDetailsSection.style.display = 'block';
          // Populate from data attributes
          contactNameInput.value = selectedOption.dataset.contact || '';
          contactPhoneInput.value = selectedOption.dataset.phone || '';
          // Store in wizard data
          wizardData.customerId = e.target.value;
          wizardData.customerName = selectedOption.textContent; // Store company name from option text
          wizardData.contactName = contactNameInput.value;
          wizardData.contactPhone = contactPhoneInput.value;
        } else {
          // Hide customer details section
          customerDetailsSection.style.display = 'none';
          contactNameInput.value = '';
          contactPhoneInput.value = '';
          wizardData.customerId = null;
          wizardData.customerName = '';
          wizardData.contactName = '';
          wizardData.contactPhone = '';
        }
      });

      // Quotation search filter
      const quotationSearchInput = document.getElementById('easy-docs-quotation-search');
      quotationSearchInput?.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        Array.from(quotationSelect.options).forEach(opt => {
          if (!opt.value) return; // skip placeholder
          const text = opt.textContent.toLowerCase();
          opt.style.display = text.includes(term) ? '' : 'none';
        });
      });

      // Quotation selection (Easy Invoice)
      const quotationSelect = document.getElementById('easy-docs-quotation');
      quotationSelect?.addEventListener('change', (e) => {
        const quotationId = e.target.value;
        if (quotationId && quotationsMap[quotationId]) {
          const qt = quotationsMap[quotationId];
          const payload = qt.payload || {};
          
          // 1. Pre-fill customer selection & contact details
          customerSelect.value = qt.customer_id || '';
          customerDetailsSection.style.display = wizardData.documentType === 'invoice' ? 'none' : 'block';
          
          const customerData = payload.customer || {};
          contactNameInput.value = customerData.contact_name || customerData.contact || '';
          contactPhoneInput.value = customerData.phone || '';
          
          wizardData.customerId = qt.customer_id;
          wizardData.customerName = qt.customer_name || '';
          wizardData.contactName = contactNameInput.value;
          wizardData.contactPhone = contactPhoneInput.value;
          
          // 2. Pre-fill items list
          wizardData.items = (payload.items || []).map(item => ({
            id: Date.now() + Math.random(),
            name: item.description || '',
            price: parseFloat(item.unit_price) || 0,
            originalPrice: parseFloat(item.input_unit_price) || parseFloat(item.unit_price) || 0,
            qty: parseInt(item.quantity) || 1,
          }));
          
          // 3. Pre-fill tax method
          const taxMethod = payload.tax_method || 'customer-pays';
          wizardData.taxMethod = taxMethod;
          const taxRadio = document.querySelector(`input[name="tax-method"][value="${taxMethod}"]`);
          if (taxRadio) taxRadio.checked = true;
          
          // 4. Pre-fill payment details
          const paymentMethod = payload.payment_method || 'bank';
          wizardData.paymentMethod = paymentMethod;
          const paymentRadio = document.querySelector(`input[name="payment-method"][value="${paymentMethod}"]`);
          if (paymentRadio) paymentRadio.checked = true;
          
          const paymentCondition = payload.payment_condition || 'full';
          wizardData.paymentCondition = paymentCondition;
          const conditionRadio = document.querySelector(`input[name="payment-condition"][value="${paymentCondition}"]`);
          if (conditionRadio) {
            conditionRadio.checked = true;
            conditionRadio.dispatchEvent(new Event('change'));
          }
          
          const paymentDetail = payload.payment_detail || '';
          wizardData.paymentDetail = paymentDetail;
          const detailInput = document.getElementById('easy-docs-payment-detail-input');
          if (detailInput) detailInput.value = paymentDetail;

          // 5. Pre-fill reference number (the quotation's document number)
          wizardData.referenceNumber = qt.document_number || '';
          
          // Render items & update total
          renderItems();
        } else {
          // Clear everything
          customerSelect.value = '';
          customerDetailsSection.style.display = 'none';
          contactNameInput.value = '';
          contactPhoneInput.value = '';
          wizardData.customerId = null;
          wizardData.customerName = '';
          wizardData.contactName = '';
          wizardData.contactPhone = '';
          
          wizardData.items = [];
          wizardData.taxMethod = 'customer-pays';
          const defaultTaxRadio = document.querySelector('input[name="tax-method"][value="customer-pays"]');
          if (defaultTaxRadio) defaultTaxRadio.checked = true;
          
          wizardData.paymentMethod = 'bank';
          const defaultPaymentRadio = document.querySelector('input[name="payment-method"][value="bank"]');
          if (defaultPaymentRadio) defaultPaymentRadio.checked = true;
          
          wizardData.paymentCondition = 'full';
          const defaultConditionRadio = document.querySelector('input[name="payment-condition"][value="full"]');
          if (defaultConditionRadio) {
            defaultConditionRadio.checked = true;
            defaultConditionRadio.dispatchEvent(new Event('change'));
          }
          
          wizardData.paymentDetail = '';
          const detailInput = document.getElementById('easy-docs-payment-detail-input');
          if (detailInput) detailInput.value = '';
          
          wizardData.referenceNumber = '';
          
          renderItems();
        }
      });

      // Contact name field change
      contactNameInput?.addEventListener('change', (e) => {
        wizardData.contactName = e.target.value;
      });

      // Contact phone field change
      contactPhoneInput?.addEventListener('change', (e) => {
        wizardData.contactPhone = e.target.value;
      });

      // Close modal
      backdrop?.addEventListener('click', closeModal);
      closeBtn?.forEach(btn => btn.addEventListener('click', closeModal));

      function closeModal() {
        modal.setAttribute('hidden', '');
        currentStep = 1;
        wizardData.items = [];
      }

      // Add product item
      addItemBtn?.addEventListener('click', () => {
        const name = productNameInput.value.trim();
        const price = parseFloat(productPriceInput.value) || 0;
        const qty = parseInt(productQtyInput.value) || 1;

        if (!name || price <= 0 || qty <= 0) {
          alert('กรุณากรอกข้อมูลให้ครบถ้วน');
          return;
        }

        wizardData.items.push({
          id: Date.now(),
          name,
          price,
          originalPrice: price,  // Keep original price for recalculation
          qty,
        });

        productNameInput.value = '';
        productPriceInput.value = '';
        productQtyInput.value = '1';
        syncItemsForTaxMethod();
      });

      function renderItems() {
        itemsList.innerHTML = '';
        wizardData.items.forEach((item, idx) => {
          const itemEl = document.createElement('div');
          itemEl.className = 'easy-docs-item';
          itemEl.innerHTML = `
            <div class="easy-docs-item-text">
              <strong>${item.name}</strong><br>
              ฿${item.price.toFixed(2)} × ${item.qty} = ฿${(item.price * item.qty).toFixed(2)}
            </div>
            <button type="button" class="easy-docs-item-delete" data-item-id="${item.id}">✕</button>
          `;
          itemEl.querySelector('[data-item-id]').addEventListener('click', (e) => {
            e.preventDefault();
            wizardData.items = wizardData.items.filter(i => i.id !== item.id);
            renderItems();
            updateTotal();
          });
          itemsList.appendChild(itemEl);
        });
        updateTotal();
      }

      function updateTotal() {
        const total = wizardData.items.reduce((sum, item) => sum + (item.price * item.qty), 0);
        totalDisplay.textContent = new Intl.NumberFormat('th-TH', {
          style: 'currency',
          currency: 'THB',
        }).format(total);

        calculateSection.style.display = 'none';
        if (wizardData.items.length === 0) {
          pricingBreakdown.style.display = 'none';
          return;
        }

        showPricingBreakdown(total);
      }

      function syncItemsForTaxMethod() {
        const selectedTaxMethod = document.querySelector('input[name="tax-method"]:checked')?.value;

        const isReverseMode = selectedTaxMethod === 'we-pay';
        wizardData.taxMethod = isReverseMode ? 'we-pay' : 'customer-pays';

        if (isReverseMode) {
          wizardData.items.forEach(item => {
            item.price = item.originalPrice / 0.97;
          });
        } else {
          wizardData.items.forEach(item => {
            item.price = item.originalPrice;
          });
        }

        renderItems();

        // Recalculate and show pricing breakdown if items exist
        if (wizardData.items.length > 0) {
          const total = wizardData.items.reduce((sum, item) => sum + (item.price * item.qty), 0);
          showPricingBreakdown(total);
        }
      }

      calculateBtn?.addEventListener('click', syncItemsForTaxMethod);
      taxMethodRadios.forEach(radio => radio.addEventListener('change', syncItemsForTaxMethod));


      // Calculate pricing breakdown for display
      function showPricingBreakdown(basePrice) {
        const amount = parseFloat(basePrice) || 0;

        // amount is already the selling price (Reverse mode: items already divided by 0.97 in syncItemsForTaxMethod)
        const sellingPrice = amount;

        const vat = sellingPrice * 0.07;
        const grandTotal = sellingPrice + vat;
        const wht = sellingPrice * 0.03;
        const customerNetPayment = grandTotal - wht;

        // Update pricing breakdown display
        updatePricingBreakdown(sellingPrice, vat, grandTotal, wht, customerNetPayment);
        pricingBreakdown.style.display = 'block';
      }

      function updatePricingBreakdown(sellingPrice, vat, grandTotal, wht, customerNetPayment) {
        const formatter = new Intl.NumberFormat('th-TH', {
          style: 'currency',
          currency: 'THB',
        });

        document.getElementById('easy-docs-subtotal').textContent = formatter.format(sellingPrice);
        document.getElementById('easy-docs-vat').textContent = '+ ' + formatter.format(vat);
        document.getElementById('easy-docs-grand-total').textContent = formatter.format(grandTotal);
        document.getElementById('easy-docs-wht').textContent = '- ' + formatter.format(wht);
        document.getElementById('easy-docs-net-payment').textContent = formatter.format(customerNetPayment);
      }

      // Step navigation
      nextBtn?.addEventListener('click', () => {
        if (validateStep(currentStep)) {
          if (wizardData.documentType === 'invoice' && currentStep === 1) {
            submitWizard();
            return;
          }
          currentStep++;
          if (currentStep > totalSteps) {
            submitWizard();
          } else {
            showStep(currentStep);
          }
        }
      });

      prevBtn?.addEventListener('click', () => {
        currentStep--;
        showStep(currentStep);
      });

      // Payment condition detail input visibility
      paymentConditionRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
          wizardData.paymentCondition = e.target.value;
          if (e.target.value === 'installment' || e.target.value === 'specific-date') {
            paymentDetailDiv.style.display = 'block';
          } else {
            paymentDetailDiv.style.display = 'none';
          }
        });
      });

      function showStep(step) {
        // Hide all steps
        document.querySelectorAll('.easy-docs-step-content').forEach(el => {
          el.classList.remove('is-active');
        });

        // Show current step
        document.getElementById(`easy-docs-step-${step}`)?.classList.add('is-active');

        // Update title
        const step1Title = wizardData.documentType === 'invoice' ? 'สร้างใบแจ้งหนี้จากใบเสนอราคา' : 'ขั้นตอน 1: เลือกลูกค้า';
        const titles = [
          step1Title,
          'ขั้นตอน 2: เพิ่มรายการสินค้า',
          'ขั้นตอน 3: เลือกวิธีชำระเงิน',
          'ขั้นตอน 4: สรุปก่อนสร้าง',
        ];
        document.getElementById('easy-docs-title').textContent = titles[step - 1];
        
        const stepIndicator = document.getElementById('easy-docs-step');
        if (wizardData.documentType === 'invoice') {
          stepIndicator.style.display = 'none';
        } else {
          stepIndicator.style.display = 'inline-block';
          stepIndicator.textContent = `${step} / ${totalSteps}`;
        }

        // Update buttons
        prevBtn.style.display = step === 1 ? 'none' : 'block';
        if (wizardData.documentType === 'invoice') {
          nextBtn.textContent = 'สร้างใบแจ้งหนี้ ✓';
        } else {
          nextBtn.textContent = step === totalSteps ? 'สร้างเอกสาร ✓' : 'ถัดไป →';
        }

        // Update summary if on final step
        if (step === totalSteps) {
          updateSummary();
        }
      }

      function validateStep(step) {
        if (step === 1) {
          if (wizardData.documentType === 'invoice') {
            const quotationSelect = document.getElementById('easy-docs-quotation');
            if (!quotationSelect || !quotationSelect.value) {
              alert('กรุณาเลือกใบเสนอราคา');
              return false;
            }
          } else {
            if (!customerSelect.value) {
              alert('กรุณาเลือกลูกค้า');
              return false;
            }
          }
          const selectedOption = customerSelect.options[customerSelect.selectedIndex];
          wizardData.customerId = customerSelect.value;
          wizardData.customerName = selectedOption ? selectedOption.textContent : '';
          wizardData.contactName = contactNameInput.value;
          wizardData.contactPhone = contactPhoneInput.value;
        }
        if (step === 2) {
          if (wizardData.items.length === 0) {
            alert('กรุณาเพิ่มอย่างน้อย 1 รายการ');
            return false;
          }
          wizardData.taxMethod = document.querySelector('input[name="tax-method"]:checked')?.value || 'customer-pays';
        }
        if (step === 3) {
          wizardData.documentType = document.querySelector('input[name="document-type"]:checked')?.value || 'quotation';
          wizardData.paymentMethod = document.querySelector('input[name="payment-method"]:checked')?.value || 'bank';
          wizardData.paymentDetail = document.getElementById('easy-docs-payment-detail-input')?.value || '';
        }
        return true;
      }

      function updateSummary() {
        const formatter = new Intl.NumberFormat('th-TH', {
          style: 'currency',
          currency: 'THB',
        });

        // Build customer display with all details
        let customerDisplay = wizardData.customerName || '-';
        if (wizardData.contactName) {
          customerDisplay += ` (${wizardData.contactName})`;
        }
        if (wizardData.contactPhone) {
          customerDisplay += ` - ${wizardData.contactPhone}`;
        }
        document.getElementById('easy-docs-summary-customer').textContent = customerDisplay;

        // Show items list
        const itemsListHtml = wizardData.items.map(item => {
          return `<div style="margin-bottom: 6px;">• ${item.name} ${formatter.format(item.price)} × ${item.qty}</div>`;
        }).join('');
        document.getElementById('easy-docs-summary-items-list').innerHTML = itemsListHtml;

        // Calculate and show pricing breakdown - items.price is already selling price in both modes
        const sellingPrice = wizardData.items.reduce((sum, item) => sum + (item.price * item.qty), 0);
        const vat = sellingPrice * 0.07;
        const grandTotal = sellingPrice + vat;
        const wht = sellingPrice * 0.03;
        const netPayment = grandTotal - wht;

        document.getElementById('easy-docs-summary-subtotal').textContent = formatter.format(sellingPrice);
        document.getElementById('easy-docs-summary-vat').textContent = '+ ' + formatter.format(vat);
        document.getElementById('easy-docs-summary-grand-total').textContent = formatter.format(grandTotal);
        document.getElementById('easy-docs-summary-wht').textContent = '- ' + formatter.format(wht);
        document.getElementById('easy-docs-summary-total').textContent = formatter.format(netPayment);

        const paymentMethodLabels = {
          'bank': 'ธนาคาร',
          'qr': 'โอน QR',
          'cash': 'เงินสด',
        };
        document.getElementById('easy-docs-summary-payment').textContent = paymentMethodLabels[wizardData.paymentMethod] || '-';

        const conditionLabels = {
          'full': 'ชำระทั้งหมด',
          'installment': 'ชำระงวด',
          'specific-date': 'ชำระตามกำหนดวัน',
        };
        document.getElementById('easy-docs-summary-condition').textContent = conditionLabels[wizardData.paymentCondition] || '-';
      }

      function submitWizard() {
        nextBtn.disabled = true;
        nextBtn.textContent = 'กำลังสร้าง...';

        // Only send required fields to backend
        const payloadData = {
          _token: csrfToken,
          customerId: wizardData.customerId,
          documentType: wizardData.documentType,
          contactName: wizardData.contactName,
          contactPhone: wizardData.contactPhone,
          items: wizardData.items,
          taxMethod: wizardData.taxMethod,
          paymentMethod: wizardData.paymentMethod,
          paymentCondition: wizardData.paymentCondition,
          paymentDetail: wizardData.paymentDetail,
          referenceNumber: wizardData.referenceNumber,
        };

        fetch(createEasyDocumentUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: JSON.stringify(payloadData),
        })
          .then(async response => {
            const responseText = await response.text();
            let data = {};

            if (responseText) {
              try {
                data = JSON.parse(responseText);
              } catch (error) {
                throw new Error(response.ok
                  ? 'ระบบตอบกลับข้อมูลไม่ถูกต้อง'
                  : `ไม่สามารถสร้างเอกสารได้ (HTTP ${response.status})`);
              }
            }

            if (!response.ok) {
              if (response.status === 419) {
                throw new Error('เซสชันหมดอายุหรือ token ไม่ตรง กรุณารีเฟรชหน้าแล้วลองสร้างเอกสารอีกครั้ง');
              }

              throw new Error(data.message || `ไม่สามารถสร้างเอกสารได้ (HTTP ${response.status})`);
            }

            return data;
          })
          .then(data => {
            if (data.success && data.redirect_url) {
              window.location.href = data.redirect_url;
            } else {
              alert(data.message || 'เกิดข้อผิดพลาด');
              nextBtn.disabled = false;
              nextBtn.textContent = 'สร้างเอกสาร ✓';
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาด: ' + error.message);
            nextBtn.disabled = false;
            nextBtn.textContent = 'สร้างเอกสาร ✓';
          });
      }

      // Create customer button
      const createCustomerBtn = document.querySelector('[data-easy-docs-create-customer]');
      createCustomerBtn?.addEventListener('click', () => {
        window.open(@json(route('admin.customers', [], false)) + '#create', '_blank');
      });

      // Initialize
      showStep(1);
    })();
  </script>

  <script>
    (() => {
      // 1. Dropdown Toggle and Click Outside Handler
      document.addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('.admin-dropdown-toggle');
        const openMenus = Array.from(document.querySelectorAll('.admin-dropdown-menu.is-open'));

        if (toggleBtn) {
          e.preventDefault();
          const currentMenu = toggleBtn.nextElementSibling;
          const isCurrentlyOpen = currentMenu.classList.contains('is-open');

          // Close other open menus
          openMenus.forEach(menu => {
            if (menu !== currentMenu) {
              menu.classList.remove('is-open');
            }
          });

          // Toggle current menu
          if (isCurrentlyOpen) {
            currentMenu.classList.remove('is-open');
          } else {
            currentMenu.classList.add('is-open');
          }
        } else {
          // Clicked outside a dropdown, close all menus
          const insideDropdown = e.target.closest('.admin-dropdown');
          if (!insideDropdown) {
            openMenus.forEach(menu => menu.classList.remove('is-open'));
          }
        }
      });

      // 2. Action Confirmation Modal Handler
      const actionModal = document.getElementById('action-confirmation-modal');
      const actionDocNameEl = document.getElementById('action-doc-name');
      const actionModalTitle = document.getElementById('action-modal-title');
      const actionModalText = document.getElementById('action-modal-text');
      const actionModalWarning = document.getElementById('action-modal-warning');
      const actionRequiredWord = document.getElementById('action-required-word');
      const actionConfirmInput = document.getElementById('action-confirm-input');
      const actionConfirmBtn = document.getElementById('action-confirm-btn');
      const hiddenActionForm = document.getElementById('hidden-action-form');
      const hiddenActionMethod = document.getElementById('hidden-action-method');

      let targetActionUrl = '';
      let targetMethod = 'POST';
      let expectedWord = 'Confirm';

      const updateActionButtonState = () => {
        const isConfirmed = actionConfirmInput.value.trim().toLowerCase() === expectedWord.toLowerCase();
        actionConfirmBtn.disabled = !isConfirmed;
        actionConfirmBtn.style.opacity = isConfirmed ? '1' : '0.5';
        actionConfirmBtn.style.cursor = isConfirmed ? 'pointer' : 'not-allowed';
        if (isConfirmed) {
          actionConfirmBtn.style.background = expectedWord === 'Delete' ? '#dc2626' : '#2563eb';
        } else {
          actionConfirmBtn.style.background = '#e5e7eb';
        }
      };

      actionConfirmInput.addEventListener('input', updateActionButtonState);

      // Event delegation for workflow and delete buttons inside dropdowns
      document.addEventListener('click', (e) => {
        const workflowBtn = e.target.closest('.workflow-action-btn');
        const deleteBtn = e.target.closest('.doc-delete-btn');

        if (workflowBtn || deleteBtn) {
          // Close all open dropdown menus when modal opens
          document.querySelectorAll('.admin-dropdown-menu.is-open').forEach(menu => {
            menu.classList.remove('is-open');
          });
        }

        if (workflowBtn) {
          e.preventDefault();
          targetActionUrl = workflowBtn.dataset.actionUrl;
          targetMethod = 'POST';
          expectedWord = 'Confirm';

          actionModalTitle.textContent = '⚙️ ยืนยันการดำเนินการ';
          actionModalText.textContent = `คุณแน่ใจว่าต้องการทำรายการ "${workflowBtn.dataset.actionName}" สำหรับเอกสาร:`;
          actionDocNameEl.textContent = workflowBtn.dataset.documentName || 'เอกสาร';
          actionDocNameEl.style.borderLeftColor = '#2563eb';
          actionDocNameEl.style.color = '#1e3a8a';
          actionDocNameEl.style.background = '#eff6ff';
          
          actionModalWarning.textContent = '';
          actionRequiredWord.textContent = 'Confirm';
          actionConfirmInput.placeholder = 'พิมพ์ Confirm';
          actionConfirmInput.value = '';
          
          updateActionButtonState();
          actionModal.style.display = 'flex';
          actionConfirmInput.focus();
        }

        if (deleteBtn) {
          e.preventDefault();
          targetActionUrl = deleteBtn.dataset.actionUrl;
          targetMethod = 'DELETE';
          expectedWord = 'Delete';

          actionModalTitle.textContent = '⚠️ ยืนยันการ' + 'ล' + 'บเอกสาร';
          actionModalText.textContent = 'คุณแน่ใจว่าต้องการ' + 'ล' + 'บเอกสาร:';
          actionDocNameEl.textContent = deleteBtn.dataset.documentName || 'เอกสาร';
          actionDocNameEl.style.borderLeftColor = '#dc2626';
          actionDocNameEl.style.color = '#991b1b';
          actionDocNameEl.style.background = '#fef2f2';
          
          actionModalWarning.textContent = '⚠️ เอกสารจะถูก' + 'ล' + 'บถาวรพร้อมไฟล์ PDF (ไม่สามารถกู้คืนได้)';
          actionRequiredWord.textContent = 'Delete';
          actionConfirmInput.placeholder = 'พิมพ์ Delete';
          actionConfirmInput.value = '';
          
          updateActionButtonState();
          actionModal.style.display = 'flex';
          actionConfirmInput.focus();
        }
      });

      // Close modal on backdrop click
      actionModal.addEventListener('click', (e) => {
        if (e.target === actionModal) {
          closeActionModal();
        }
      });

      window.closeActionModal = function() {
        actionModal.style.display = 'none';
        actionConfirmInput.value = '';
        targetActionUrl = '';
      };

      window.submitActionForm = function() {
        if (actionConfirmBtn.disabled) return;
        hiddenActionForm.action = targetActionUrl;
        hiddenActionMethod.value = targetMethod;
        hiddenActionForm.submit();
        closeActionModal();
      };
    })();
  </script>
@endsection
