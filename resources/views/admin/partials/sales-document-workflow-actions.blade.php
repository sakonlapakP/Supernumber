<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
  @if ($document->isQuotationDraft())
    <form action="{{ route('admin.saved-sales-documents.quotation-status', [$document, 'send']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact">ส่งใบเสนอราคา</button>
    </form>
    <form action="{{ route('admin.saved-sales-documents.quotation-status', [$document, 'cancel']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact admin-button--muted">ยกเลิก</button>
    </form>
  @elseif ($document->isQuotationSent())
    <form action="{{ route('admin.saved-sales-documents.quotation-status', [$document, 'accept']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact">ยอมรับ</button>
    </form>
    <form action="{{ route('admin.saved-sales-documents.quotation-status', [$document, 'reject']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact admin-button--muted">ปฏิเสธ</button>
    </form>
    <form action="{{ route('admin.saved-sales-documents.quotation-status', [$document, 'expire']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact admin-button--muted">หมดอายุ</button>
    </form>
  @elseif ($document->isQuotationAccepted())
    @if ($document->convertedInvoice)
      <a href="{{ route('admin.saved-sales-documents.show', $document->convertedInvoice) }}" class="admin-button admin-button--compact admin-button--muted">
        เปิด Invoice {{ $document->convertedInvoice->document_number }}
      </a>
    @else
      <form action="{{ route('admin.saved-sales-documents.convert-to-invoice', $document) }}" method="post" style="margin: 0;">
        @csrf
        <button type="submit" class="admin-button admin-button--compact">แปลงเป็น Invoice</button>
      </form>
    @endif
    <form action="{{ route('admin.saved-sales-documents.quotation-status', [$document, 'cancel']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact admin-button--muted">ยกเลิก</button>
    </form>
  @elseif ($document->isInvoiceDraft())
    <form action="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'issue']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact">ออกใบแจ้งหนี้</button>
    </form>
    <form action="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'void']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact admin-button--muted">Void</button>
    </form>
  @elseif ($document->isInvoiceIssued())
    <form action="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'partial-paid']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact">ชำระบางส่วน</button>
    </form>
    <form action="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'paid']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact">ชำระแล้ว</button>
    </form>
    <form action="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'overdue']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact admin-button--muted">ค้างชำระ</button>
    </form>
    <form action="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'void']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact admin-button--muted">Void</button>
    </form>
  @elseif ($document->status === \App\Models\SalesDocument::STATUS_INVOICE_PARTIALLY_PAID)
    <form action="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'paid']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact">ชำระแล้ว</button>
    </form>
    <form action="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'overdue']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact admin-button--muted">ค้างชำระ</button>
    </form>
    <form action="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'void']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact admin-button--muted">Void</button>
    </form>
  @elseif ($document->status === \App\Models\SalesDocument::STATUS_INVOICE_OVERDUE)
    <form action="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'paid']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact">ชำระแล้ว</button>
    </form>
    <form action="{{ route('admin.saved-sales-documents.invoice-status', [$document, 'void']) }}" method="post" style="margin: 0;">
      @csrf
      <button type="submit" class="admin-button admin-button--compact admin-button--muted">Void</button>
    </form>
  @endif
</div>
