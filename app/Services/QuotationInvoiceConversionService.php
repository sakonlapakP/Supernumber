<?php

namespace App\Services;

use App\Models\SalesDocument;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QuotationInvoiceConversionService
{
    private InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Convert an accepted quotation to an invoice
     */
    public function convertToInvoice(
        SalesDocument $quotation,
        ?int $convertedByUserId = null
    ): SalesDocument {
        if (! $quotation->isQuotation()) {
            throw new RuntimeException('เอกสารนี้ไม่ใช่ใบเสนอราคา');
        }

        if (! $quotation->isQuotationAccepted()) {
            throw new RuntimeException('เฉพาะใบเสนอราคาที่ได้รับการยอมรับแล้วเท่านั้นที่สามารถแปลงเป็นใบแจ้งหนี้ได้');
        }

        return DB::transaction(function () use ($quotation, $convertedByUserId): SalesDocument {
            $existingInvoice = SalesDocument::query()
                ->where('source_quotation_id', $quotation->id)
                ->where('document_type', SalesDocument::TYPE_INVOICE)
                ->first();

            if ($existingInvoice) {
                throw new RuntimeException('ใบแจ้งหนี้สำหรับใบเสนอราคานี้มีอยู่แล้ว');
            }

            $invoice = $this->invoiceService->save(
                $this->buildInvoiceSnapshot($quotation),
                $convertedByUserId
            );

            $invoice->update(['source_quotation_id' => $quotation->id]);

            return $invoice->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInvoiceSnapshot(SalesDocument $quotation): array
    {
        $invoiceData = $quotation->payload ?? [];
        $draftNumber = 'DRAFT-CONVERT-'.$quotation->id.'-'.now('Asia/Bangkok')->format('YmdHis');
        $documentMeta = is_array($invoiceData['document'] ?? null)
            ? $invoiceData['document']
            : [];

        $invoiceData['document_type'] = SalesDocument::TYPE_INVOICE;
        $invoiceData['document_number'] = $draftNumber;
        $invoiceData['customer_id'] = $quotation->customer_id;
        $invoiceData['customer_name'] = $quotation->customer_name;
        $invoiceData['document_date'] = $quotation->document_date;
        $invoiceData['due_date'] = $quotation->due_date;
        $invoiceData['document'] = array_merge($documentMeta, [
            'type' => SalesDocument::TYPE_INVOICE,
            'title_th' => 'ใบแจ้งหนี้',
            'title_en' => 'Invoice',
            'number' => $draftNumber,
        ]);

        return $invoiceData;
    }
}
