<?php

namespace App\Services;

use App\Models\SalesDocument;
use RuntimeException;

class InvoiceService
{
    private SalesDocumentPdfService $pdfService;

    public function __construct(SalesDocumentPdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Create or update an invoice
     */
    public function save(array $data, ?int $savedByUserId = null): SalesDocument
    {
        $data['document_type'] = 'invoice';
        $documentNumber = trim((string) ($data['document_number'] ?? ''));
        $existingInvoice = $documentNumber !== ''
            ? SalesDocument::query()
                ->where('document_type', SalesDocument::TYPE_INVOICE)
                ->where('document_number', $documentNumber)
                ->first()
            : null;

        if ($existingInvoice && ! $existingInvoice->isInvoiceEditable()) {
            throw new RuntimeException('ไม่สามารถแก้ไขใบแจ้งหนี้ที่ออกแล้วหรือมีสถานะการชำระเงินแล้ว');
        }

        $document = $this->pdfService->saveDocument($data, $savedByUserId);

        // Set invoice status if not already set
        if (! $document->status || str_starts_with($document->status, 'quotation_')) {
            $document->update(['status' => SalesDocument::STATUS_INVOICE_DRAFT]);
        }

        return $document->fresh();
    }

    /**
     * Issue invoice (change status to issued)
     */
    public function issue(SalesDocument $invoice): SalesDocument
    {
        $this->assertInvoiceTransition($invoice, SalesDocument::STATUS_INVOICE_ISSUED);

        $invoice->update(['status' => SalesDocument::STATUS_INVOICE_ISSUED]);

        return $invoice->fresh();
    }

    /**
     * Mark invoice as partially paid
     */
    public function markPartiallyPaid(SalesDocument $invoice): SalesDocument
    {
        $this->assertInvoiceTransition($invoice, SalesDocument::STATUS_INVOICE_PARTIALLY_PAID);

        $invoice->update(['status' => SalesDocument::STATUS_INVOICE_PARTIALLY_PAID]);

        return $invoice->fresh();
    }

    /**
     * Mark invoice as paid
     */
    public function markPaid(SalesDocument $invoice): SalesDocument
    {
        $this->assertInvoiceTransition($invoice, SalesDocument::STATUS_INVOICE_PAID);

        $invoice->update(['status' => SalesDocument::STATUS_INVOICE_PAID]);

        return $invoice->fresh();
    }

    /**
     * Mark invoice as overdue
     */
    public function markOverdue(SalesDocument $invoice): SalesDocument
    {
        $this->assertInvoiceTransition($invoice, SalesDocument::STATUS_INVOICE_OVERDUE);

        $invoice->update(['status' => SalesDocument::STATUS_INVOICE_OVERDUE]);

        return $invoice->fresh();
    }

    /**
     * Void invoice
     */
    public function void(SalesDocument $invoice): SalesDocument
    {
        $this->assertInvoiceTransition($invoice, SalesDocument::STATUS_INVOICE_VOID);

        $invoice->update(['status' => SalesDocument::STATUS_INVOICE_VOID]);

        return $invoice->fresh();
    }

    /**
     * Check if invoice can be created from quotation
     */
    public function canCreateFromQuotation(SalesDocument $quotation): bool
    {
        if (! $quotation->isQuotation()) {
            return false;
        }

        return $quotation->isQuotationAccepted();
    }

    /**
     * Get allowed status transitions for invoice
     */
    public static function getAllowedStatusTransitions(string $currentStatus): array
    {
        $transitions = [
            SalesDocument::STATUS_INVOICE_DRAFT => [
                SalesDocument::STATUS_INVOICE_ISSUED,
                SalesDocument::STATUS_INVOICE_VOID,
            ],
            SalesDocument::STATUS_INVOICE_ISSUED => [
                SalesDocument::STATUS_INVOICE_PARTIALLY_PAID,
                SalesDocument::STATUS_INVOICE_PAID,
                SalesDocument::STATUS_INVOICE_OVERDUE,
                SalesDocument::STATUS_INVOICE_VOID,
            ],
            SalesDocument::STATUS_INVOICE_PARTIALLY_PAID => [
                SalesDocument::STATUS_INVOICE_PAID,
                SalesDocument::STATUS_INVOICE_OVERDUE,
                SalesDocument::STATUS_INVOICE_VOID,
            ],
            SalesDocument::STATUS_INVOICE_PAID => [],
            SalesDocument::STATUS_INVOICE_OVERDUE => [
                SalesDocument::STATUS_INVOICE_PAID,
                SalesDocument::STATUS_INVOICE_VOID,
            ],
            SalesDocument::STATUS_INVOICE_VOID => [],
        ];

        return $transitions[$currentStatus] ?? [];
    }

    private function assertInvoiceTransition(SalesDocument $invoice, string $nextStatus): void
    {
        if (! $invoice->isInvoice()) {
            throw new RuntimeException('เอกสารนี้ไม่ใช่ใบแจ้งหนี้');
        }

        if (! in_array($nextStatus, self::getAllowedStatusTransitions((string) $invoice->status), true)) {
            throw new RuntimeException('ไม่สามารถเปลี่ยนสถานะใบแจ้งหนี้จากสถานะปัจจุบันได้');
        }
    }
}
