<?php

namespace App\Services;

use App\Models\BillingCustomer;
use App\Models\SalesDocument;
use RuntimeException;

class EasyDocumentService
{
    public function __construct(
        private SalesDocumentPdfService $pdfService
    ) {}

    /**
     * Create an Easy Document (Quotation or Invoice) from minimal wizard input.
     *
     * @param  array<string, mixed>  $data
     * @return array{document: SalesDocument, redirect_url: string}
     */
    public function create(array $data, ?int $savedByUserId = null): array
    {
        $customer = BillingCustomer::findOrFail($data['customerId']);

        $documentType = ($data['documentType'] ?? 'quotation') === 'invoice' ? 'invoice' : 'quotation';
        $prefix = $documentType === 'invoice' ? 'IV' : 'QT';

        $calculationMode = ($data['taxMethod'] ?? 'customer-pays') === 'we-pay' ? 'reverse' : 'standard';

        $documentItems = collect($data['items'])->values()->map(function (array $item, int $index): array {
            $quantity = (int) $item['qty'];
            $originalPrice = (float) ($item['originalPrice'] ?? $item['price']);
            $price = (float) $item['price'];

            return [
                'index' => $index + 1,
                'description' => $item['name'],
                'quantity' => $quantity,
                'unit' => '',
                'input_unit_price' => $originalPrice,
                'input_amount' => round($originalPrice * $quantity, 2),
                'unit_price' => $price,
                'amount' => round($price * $quantity, 2),
            ];
        })->all();

        $subtotal = collect($documentItems)->sum(fn (array $item): float => (float) $item['amount']);
        $targetIncome = collect($documentItems)->sum(fn (array $item): float => (float) $item['input_amount']);
        $quickPaymentMethod = ($data['paymentMethod'] ?? 'bank') === 'cash' ? 'cash' : 'transfer';

        $baseAmount = $subtotal;
        $vatAmount = round($baseAmount * 0.07, 2);
        $whtAmount = round($baseAmount * 0.03, 2);
        $grandTotal = round($baseAmount + $vatAmount, 2);
        $netToPay = round($grandTotal - $whtAmount, 2);

        $totals = [
            'discount_rate' => 0.00,
            'discount_rate_display' => '0.00',
            'discount_amount' => 0.00,
            'discount_amount_display' => '0.00',
            'after_discount' => $subtotal,
            'after_discount_display' => number_format($subtotal, 2, '.', ','),
            'target_income' => $targetIncome,
            'target_income_display' => number_format($targetIncome, 2, '.', ','),
            'service_net_income' => $targetIncome,
            'service_net_income_display' => number_format($targetIncome, 2, '.', ','),
            'calculation_mode' => $calculationMode,
            'subtotal' => $subtotal,
            'subtotal_display' => number_format($subtotal, 2, '.', ','),
            'vat_rate' => 7.00,
            'vat_rate_display' => '7.00',
            'vat_amount' => $vatAmount,
            'vat_amount_display' => number_format($vatAmount, 2, '.', ','),
            'grand_total' => $grandTotal,
            'grand_total_display' => number_format($grandTotal, 2, '.', ','),
            'withholding_rate' => 3.00,
            'withholding_rate_display' => '3.00',
            'withholding_amount' => $whtAmount,
            'withholding_amount_display' => number_format($whtAmount, 2, '.', ','),
            'net_to_pay' => $netToPay,
            'net_to_pay_display' => number_format($netToPay, 2, '.', ','),
        ];

        $now = now('Asia/Bangkok');
        $placeholderNumber = $prefix . '-' . $now->format('ymd') . '-001';

        $payload = [
            'document_type' => $documentType,
            'document_number' => $placeholderNumber,
            'document_date' => $now->format('Y-m-d'),
            'due_date' => $now->copy()->addDays(7)->format('Y-m-d'),
            'document' => [
                'type' => $documentType,
                'title_th' => $documentType === 'invoice' ? 'ใบแจ้งหนี้' : 'ใบเสนอราคา',
                'title_en' => $documentType === 'invoice' ? 'Invoice' : 'Quotation',
                'number' => $placeholderNumber,
                'date' => $now->format('Y-m-d'),
                'due_date' => $now->copy()->addDays(7)->format('Y-m-d'),
                'reference_number' => trim((string) ($data['referenceNumber'] ?? '')) ?: null,
            ],
            'customer_id' => $customer->id,
            'customer_name' => $customer->display_name,
            'customer' => [
                'id' => $customer->id,
                'customer_id' => $customer->id,
                'name' => $customer->display_name,
                'company_name' => $customer->company_name,
                'contact' => trim((string) ($data['contactName'] ?? '')) ?: $customer->contact_name,
                'contact_name' => trim((string) ($data['contactName'] ?? '')) ?: $customer->contact_name,
                'tax_id' => $customer->tax_id,
                'address' => $customer->address,
                'email' => $customer->email,
                'phone' => trim((string) ($data['contactPhone'] ?? '')) ?: $customer->phone,
                'payment_term' => $customer->payment_term,
            ],
            'items' => $documentItems,
            'calculation_mode' => $calculationMode,
            'tax_method' => $data['taxMethod'] ?? 'customer-pays',
            'payment_method' => $data['paymentMethod'] ?? 'bank',
            'payment_condition' => $data['paymentCondition'] ?? 'full',
            'payment_detail' => $data['paymentDetail'] ?? null,
            'payment' => [
                'method' => $quickPaymentMethod,
                'cash' => $quickPaymentMethod === 'cash',
                'transfer' => $quickPaymentMethod === 'transfer',
                'cheque' => false,
                'bank' => 'ธนาคารกสิกรไทย บจก. ซุปเปอร์นัมเบอร์',
                'branch' => 'จามจุรีสแควร์',
                'account_number' => '0063701726',
            ],
            'totals' => $totals,
            'total' => $subtotal,
        ];

        $isDirectInvoiceCreate = $documentType === 'invoice' && !empty($data['referenceNumber']);

        if ($isDirectInvoiceCreate) {
            $sourceQuotation = SalesDocument::where('document_number', $data['referenceNumber'])
                ->where('document_type', 'quotation')
                ->first();

            if ($sourceQuotation) {
                $alreadyHasInvoice = SalesDocument::where('source_quotation_id', $sourceQuotation->id)
                    ->where('document_type', 'invoice')
                    ->exists();

                if ($alreadyHasInvoice) {
                    throw new RuntimeException('ใบแจ้งหนี้สำหรับใบเสนอราคานี้มีอยู่แล้ว');
                }
            }

            $payload['document_number'] = 'IV-' . $now->format('YmdHis');
            $payload['document']['number'] = $payload['document_number'];
            $document = $this->pdfService->saveDocument($payload, $savedByUserId);
            $document->update(['status' => SalesDocument::STATUS_INVOICE_DRAFT]);

            if ($sourceQuotation) {
                $sourceQuotation->update([
                    'status' => SalesDocument::STATUS_QUOTATION_ACCEPTED,
                ]);
                $document->update(['source_quotation_id' => $sourceQuotation->id]);
            }

            $redirectUrl = route('admin.saved-sales-documents.index', ['type' => 'invoice'], false);
        } else {
            $draftDocumentNumber = $prefix . '-' . $now->format('YmdHis');
            $payload['document_number'] = $draftDocumentNumber;
            $payload['document']['number'] = $draftDocumentNumber;

            $document = SalesDocument::create([
                'document_type' => $documentType,
                'document_number' => $draftDocumentNumber,
                'document_date' => $now->format('Y-m-d'),
                'due_date' => $now->copy()->addDays(7)->format('Y-m-d'),
                'file_name' => strtolower($prefix) . '-' . $now->format('YmdHis'),
                'customer_id' => $customer->id,
                'customer_name' => $customer->display_name,
                'is_draft' => true,
                'is_active' => true,
                'pdf_disk' => 'local',
                'pdf_path' => '',
                'saved_by_user_id' => $savedByUserId,
                'payload' => $payload,
            ]);

            $redirectUrl = route('admin.sales-documents-quick', ['draft' => $document->id], false);
        }

        return [
            'document' => $document->fresh(),
            'redirect_url' => $redirectUrl,
        ];
    }
}
