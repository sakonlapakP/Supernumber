<?php

namespace App\Services;

use App\Models\SalesDocument;
use RuntimeException;

class QuotationService
{
    private SalesDocumentPdfService $pdfService;

    public function __construct(SalesDocumentPdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Create or update a quotation
     */
    public function save(array $data, ?int $savedByUserId = null): SalesDocument
    {
        $data['document_type'] = 'quotation';

        $documentNumber = trim((string) ($data['document_number'] ?? ''));
        $existingQuotation = $documentNumber !== ''
            ? SalesDocument::query()
                ->where('document_type', SalesDocument::TYPE_QUOTATION)
                ->where('document_number', $documentNumber)
                ->first()
            : null;

        if ($existingQuotation && ! $existingQuotation->isQuotationEditable()) {
            throw new RuntimeException('ไม่สามารถแก้ไขใบเสนอราคาที่ส่งแล้วหรือมีสถานะสุดท้าย');
        }

        $document = $this->pdfService->saveDocument($data, $savedByUserId);

        // Set quotation status if not already set
        if (! $document->status || str_starts_with($document->status, 'invoice_')) {
            $document->update(['status' => SalesDocument::STATUS_QUOTATION_DRAFT]);
        }

        return $document->fresh();
    }

    /**
     * Send quotation (change status to sent)
     */
    public function send(SalesDocument $quotation): SalesDocument
    {
        $this->assertQuotationTransition($quotation, SalesDocument::STATUS_QUOTATION_SENT);

        $quotation->update(['status' => SalesDocument::STATUS_QUOTATION_SENT]);

        return $quotation->fresh();
    }

    /**
     * Accept quotation (change status to accepted)
     */
    public function accept(SalesDocument $quotation): SalesDocument
    {
        $this->assertQuotationTransition($quotation, SalesDocument::STATUS_QUOTATION_ACCEPTED);

        $quotation->update(['status' => SalesDocument::STATUS_QUOTATION_ACCEPTED]);

        return $quotation->fresh();
    }

    /**
     * Reject quotation
     */
    public function reject(SalesDocument $quotation): SalesDocument
    {
        $this->assertQuotationTransition($quotation, SalesDocument::STATUS_QUOTATION_REJECTED);

        $quotation->update(['status' => SalesDocument::STATUS_QUOTATION_REJECTED]);

        return $quotation->fresh();
    }

    /**
     * Expire quotation
     */
    public function expire(SalesDocument $quotation): SalesDocument
    {
        $this->assertQuotationTransition($quotation, SalesDocument::STATUS_QUOTATION_EXPIRED);

        $quotation->update(['status' => SalesDocument::STATUS_QUOTATION_EXPIRED]);

        return $quotation->fresh();
    }

    /**
     * Check if quotation can be converted to invoice
     */
    public function canConvertToInvoice(SalesDocument $quotation): bool
    {
        if (! $quotation->isQuotation()) {
            return false;
        }

        return $quotation->isQuotationAccepted();
    }

    public function cancel(SalesDocument $quotation): SalesDocument
    {
        $this->assertQuotationTransition($quotation, SalesDocument::STATUS_QUOTATION_CANCELLED);

        $quotation->update(['status' => SalesDocument::STATUS_QUOTATION_CANCELLED]);

        return $quotation->fresh();
    }

    /**
     * Get allowed status transitions for quotation
     */
    public static function getAllowedStatusTransitions(string $currentStatus): array
    {
        $transitions = [
            SalesDocument::STATUS_QUOTATION_DRAFT => [
                SalesDocument::STATUS_QUOTATION_SENT,
                SalesDocument::STATUS_QUOTATION_CANCELLED,
            ],
            SalesDocument::STATUS_QUOTATION_SENT => [
                SalesDocument::STATUS_QUOTATION_ACCEPTED,
                SalesDocument::STATUS_QUOTATION_REJECTED,
                SalesDocument::STATUS_QUOTATION_EXPIRED,
            ],
            SalesDocument::STATUS_QUOTATION_ACCEPTED => [
                SalesDocument::STATUS_QUOTATION_CANCELLED,
            ],
            SalesDocument::STATUS_QUOTATION_REJECTED => [],
            SalesDocument::STATUS_QUOTATION_EXPIRED => [],
            SalesDocument::STATUS_QUOTATION_CANCELLED => [],
        ];

        return $transitions[$currentStatus] ?? [];
    }

    private function assertQuotationTransition(SalesDocument $quotation, string $nextStatus): void
    {
        if (! $quotation->isQuotation()) {
            throw new RuntimeException('เอกสารนี้ไม่ใช่ใบเสนอราคา');
        }

        if (! in_array($nextStatus, self::getAllowedStatusTransitions((string) $quotation->status), true)) {
            throw new RuntimeException('ไม่สามารถเปลี่ยนสถานะใบเสนอราคาจากสถานะปัจจุบันได้');
        }
    }
}
