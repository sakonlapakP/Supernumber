<?php

namespace Tests\Feature;

use App\Models\SalesDocument;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\QuotationInvoiceConversionService;
use App\Services\QuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class QuotationInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QuotationService $quotationService;

    protected InvoiceService $invoiceService;

    protected QuotationInvoiceConversionService $conversionService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quotationService = $this->app->make(QuotationService::class);
        $this->invoiceService = $this->app->make(InvoiceService::class);
        $this->conversionService = $this->app->make(QuotationInvoiceConversionService::class);

        $this->user = User::factory()->create([
            'username' => 'test-user',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    // ============ QUOTATION SERVICE TESTS ============

    public function test_quotation_service_creates_quotation_with_draft_status(): void
    {
        $data = [
            'document_type' => 'quotation',
            'document_number' => 'QT-260501-001',
            'document_date' => '2026-05-01',
            'due_date' => '2026-05-08',
            'customer_name' => 'บริษัท ทดสอบ จำกัด',
            'customer_id' => null,
        ];

        $quotation = $this->quotationService->save($data, $this->user->id);

        $this->assertDatabaseHas('sales_documents', [
            'id' => $quotation->id,
            'document_type' => 'quotation',
            'status' => SalesDocument::STATUS_QUOTATION_DRAFT,
            'document_number' => 'QT-260501-001',
        ]);
    }

    public function test_quotation_service_send_changes_status_to_sent(): void
    {
        $quotation = $this->createQuotation('QT-260501-002');

        $result = $this->quotationService->send($quotation);

        $this->assertEquals(SalesDocument::STATUS_QUOTATION_SENT, $result->status);
        $this->assertDatabaseHas('sales_documents', [
            'id' => $quotation->id,
            'status' => SalesDocument::STATUS_QUOTATION_SENT,
        ]);
    }

    public function test_quotation_service_accept_changes_status_to_accepted(): void
    {
        $quotation = $this->createQuotation('QT-260501-003');
        $this->quotationService->send($quotation);
        $quotation->refresh();

        $result = $this->quotationService->accept($quotation);

        $this->assertEquals(SalesDocument::STATUS_QUOTATION_ACCEPTED, $result->status);
        $this->assertDatabaseHas('sales_documents', [
            'id' => $quotation->id,
            'status' => SalesDocument::STATUS_QUOTATION_ACCEPTED,
        ]);
    }

    public function test_quotation_service_reject_changes_status_to_rejected(): void
    {
        $quotation = $this->createQuotation('QT-260501-004');
        $this->quotationService->send($quotation);
        $quotation->refresh();

        $result = $this->quotationService->reject($quotation);

        $this->assertEquals(SalesDocument::STATUS_QUOTATION_REJECTED, $result->status);
        $this->assertDatabaseHas('sales_documents', [
            'id' => $quotation->id,
            'status' => SalesDocument::STATUS_QUOTATION_REJECTED,
        ]);
    }

    public function test_quotation_service_expire_changes_status_to_expired(): void
    {
        $quotation = $this->createQuotation('QT-260501-005');
        $this->quotationService->send($quotation);
        $quotation->refresh();

        $result = $this->quotationService->expire($quotation);

        $this->assertEquals(SalesDocument::STATUS_QUOTATION_EXPIRED, $result->status);
        $this->assertDatabaseHas('sales_documents', [
            'id' => $quotation->id,
            'status' => SalesDocument::STATUS_QUOTATION_EXPIRED,
        ]);
    }

    public function test_quotation_service_cannot_accept_rejected_quotation(): void
    {
        $quotation = $this->createQuotation('QT-260501-006');
        $this->quotationService->send($quotation);
        $quotation->refresh();
        $this->quotationService->reject($quotation);
        $quotation->refresh();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ไม่สามารถเปลี่ยนสถานะใบเสนอราคาจากสถานะปัจจุบันได้');

        $this->quotationService->accept($quotation);
    }

    public function test_quotation_service_cannot_send_already_sent_quotation(): void
    {
        $quotation = $this->createQuotation('QT-260501-007');
        $this->quotationService->send($quotation);
        $quotation->refresh();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ไม่สามารถเปลี่ยนสถานะใบเสนอราคาจากสถานะปัจจุบันได้');

        $this->quotationService->send($quotation);
    }

    public function test_quotation_service_cannot_accept_non_quotation(): void
    {
        $invoice = $this->createInvoice('IV-260501-001');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('เอกสารนี้ไม่ใช่ใบเสนอราคา');

        $this->quotationService->accept($invoice);
    }

    public function test_quotation_service_can_convert_accepted_quotation_to_invoice(): void
    {
        $quotation = $this->createQuotation('QT-260501-008');
        $this->quotationService->send($quotation);
        $quotation->refresh();
        $this->quotationService->accept($quotation);
        $quotation->refresh();

        $canConvert = $this->quotationService->canConvertToInvoice($quotation);

        $this->assertTrue($canConvert);
    }

    public function test_quotation_service_cannot_convert_non_accepted_quotation(): void
    {
        $quotation = $this->createQuotation('QT-260501-009');

        $canConvert = $this->quotationService->canConvertToInvoice($quotation);

        $this->assertFalse($canConvert);
    }

    public function test_quotation_allowed_status_transitions(): void
    {
        $draftTransitions = QuotationService::getAllowedStatusTransitions(
            SalesDocument::STATUS_QUOTATION_DRAFT
        );
        $this->assertContains(SalesDocument::STATUS_QUOTATION_SENT, $draftTransitions);
        $this->assertContains(SalesDocument::STATUS_QUOTATION_CANCELLED, $draftTransitions);

        $sentTransitions = QuotationService::getAllowedStatusTransitions(
            SalesDocument::STATUS_QUOTATION_SENT
        );
        $this->assertContains(SalesDocument::STATUS_QUOTATION_ACCEPTED, $sentTransitions);
        $this->assertContains(SalesDocument::STATUS_QUOTATION_REJECTED, $sentTransitions);
        $this->assertContains(SalesDocument::STATUS_QUOTATION_EXPIRED, $sentTransitions);

        $rejectedTransitions = QuotationService::getAllowedStatusTransitions(
            SalesDocument::STATUS_QUOTATION_REJECTED
        );
        $this->assertEmpty($rejectedTransitions);
    }

    // ============ INVOICE SERVICE TESTS ============

    public function test_invoice_service_creates_invoice_with_draft_status(): void
    {
        $data = [
            'document_type' => 'invoice',
            'document_number' => 'IV-260501-001',
            'document_date' => '2026-05-01',
            'due_date' => '2026-05-08',
            'customer_name' => 'บริษัท ทดสอบ จำกัด',
            'customer_id' => null,
        ];

        $invoice = $this->invoiceService->save($data, $this->user->id);

        $this->assertDatabaseHas('sales_documents', [
            'id' => $invoice->id,
            'document_type' => 'invoice',
            'status' => SalesDocument::STATUS_INVOICE_DRAFT,
            'document_number' => 'IV-260501-001',
        ]);
    }

    public function test_invoice_service_issue_changes_status_to_issued(): void
    {
        $invoice = $this->createInvoice('IV-260501-002');

        $result = $this->invoiceService->issue($invoice);

        $this->assertEquals(SalesDocument::STATUS_INVOICE_ISSUED, $result->status);
        $this->assertDatabaseHas('sales_documents', [
            'id' => $invoice->id,
            'status' => SalesDocument::STATUS_INVOICE_ISSUED,
        ]);
    }

    public function test_invoice_service_mark_partially_paid(): void
    {
        $invoice = $this->createInvoice('IV-260501-003');
        $this->invoiceService->issue($invoice);
        $invoice->refresh();

        $result = $this->invoiceService->markPartiallyPaid($invoice);

        $this->assertEquals(SalesDocument::STATUS_INVOICE_PARTIALLY_PAID, $result->status);
    }

    public function test_invoice_service_mark_paid_from_issued(): void
    {
        $invoice = $this->createInvoice('IV-260501-004');
        $this->invoiceService->issue($invoice);
        $invoice->refresh();

        $result = $this->invoiceService->markPaid($invoice);

        $this->assertEquals(SalesDocument::STATUS_INVOICE_PAID, $result->status);
    }

    public function test_invoice_service_mark_paid_from_partially_paid(): void
    {
        $invoice = $this->createInvoice('IV-260501-005');
        $this->invoiceService->issue($invoice);
        $invoice->refresh();
        $this->invoiceService->markPartiallyPaid($invoice);
        $invoice->refresh();

        $result = $this->invoiceService->markPaid($invoice);

        $this->assertEquals(SalesDocument::STATUS_INVOICE_PAID, $result->status);
    }

    public function test_invoice_service_mark_overdue(): void
    {
        $invoice = $this->createInvoice('IV-260501-006');
        $this->invoiceService->issue($invoice);
        $invoice->refresh();

        $result = $this->invoiceService->markOverdue($invoice);

        $this->assertEquals(SalesDocument::STATUS_INVOICE_OVERDUE, $result->status);
    }

    public function test_invoice_service_void_invoice(): void
    {
        $invoice = $this->createInvoice('IV-260501-007');
        $this->invoiceService->issue($invoice);
        $invoice->refresh();

        $result = $this->invoiceService->void($invoice);

        $this->assertEquals(SalesDocument::STATUS_INVOICE_VOID, $result->status);
    }

    public function test_invoice_service_cannot_mark_paid_twice(): void
    {
        $invoice = $this->createInvoice('IV-260501-008');
        $this->invoiceService->issue($invoice);
        $invoice->refresh();
        $this->invoiceService->markPaid($invoice);
        $invoice->refresh();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ไม่สามารถเปลี่ยนสถานะใบแจ้งหนี้จากสถานะปัจจุบันได้');

        $this->invoiceService->markPaid($invoice);
    }

    public function test_invoice_service_cannot_issue_already_issued_invoice(): void
    {
        $invoice = $this->createInvoice('IV-260501-009');
        $this->invoiceService->issue($invoice);
        $invoice->refresh();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ไม่สามารถเปลี่ยนสถานะใบแจ้งหนี้จากสถานะปัจจุบันได้');

        $this->invoiceService->issue($invoice);
    }

    public function test_invoice_service_cannot_mark_paid_void_invoice(): void
    {
        $invoice = $this->createInvoice('IV-260501-010');
        $this->invoiceService->issue($invoice);
        $invoice->refresh();
        $this->invoiceService->void($invoice);
        $invoice->refresh();

        $this->expectException(RuntimeException::class);
        $this->invoiceService->markPaid($invoice);
    }

    public function test_invoice_allowed_status_transitions(): void
    {
        $draftTransitions = InvoiceService::getAllowedStatusTransitions(
            SalesDocument::STATUS_INVOICE_DRAFT
        );
        $this->assertContains(SalesDocument::STATUS_INVOICE_ISSUED, $draftTransitions);

        $issuedTransitions = InvoiceService::getAllowedStatusTransitions(
            SalesDocument::STATUS_INVOICE_ISSUED
        );
        $this->assertContains(SalesDocument::STATUS_INVOICE_PARTIALLY_PAID, $issuedTransitions);
        $this->assertContains(SalesDocument::STATUS_INVOICE_PAID, $issuedTransitions);
        $this->assertContains(SalesDocument::STATUS_INVOICE_OVERDUE, $issuedTransitions);

        $paidTransitions = InvoiceService::getAllowedStatusTransitions(
            SalesDocument::STATUS_INVOICE_PAID
        );
        $this->assertEmpty($paidTransitions);
    }

    // ============ CONVERSION SERVICE TESTS ============

    public function test_conversion_service_converts_accepted_quotation_to_invoice(): void
    {
        $quotation = $this->createQuotation('QT-260501-010');
        $this->quotationService->send($quotation);
        $quotation->refresh();
        $this->quotationService->accept($quotation);
        $quotation->refresh();

        $invoice = $this->conversionService->convertToInvoice($quotation, $this->user->id);

        $this->assertDatabaseHas('sales_documents', [
            'id' => $invoice->id,
            'document_type' => 'invoice',
            'status' => SalesDocument::STATUS_INVOICE_DRAFT,
            'source_quotation_id' => $quotation->id,
        ]);
        $this->assertStringStartsWith('IV-', $invoice->document_number);
        $this->assertNotSame($quotation->document_number, $invoice->document_number);
    }

    public function test_conversion_service_links_invoice_to_source_quotation(): void
    {
        $quotation = $this->createQuotation('QT-260501-011');
        $this->quotationService->send($quotation);
        $quotation->refresh();
        $this->quotationService->accept($quotation);
        $quotation->refresh();

        $invoice = $this->conversionService->convertToInvoice($quotation, $this->user->id);

        $this->assertEquals($quotation->id, $invoice->source_quotation_id);
    }

    public function test_conversion_service_snapshots_quotation_payload_for_invoice(): void
    {
        $quotation = $this->quotationService->save([
            'document_type' => 'quotation',
            'document_number' => 'QT-260501-014',
            'document_date' => '2026-05-01',
            'due_date' => '2026-05-08',
            'customer_name' => 'บริษัท Snapshot จำกัด',
            'document' => [
                'title_th' => 'ใบเสนอราคา',
                'title_en' => 'Quotation',
                'number' => 'QT-260501-014',
            ],
            'items' => [
                [
                    'description' => 'บริการเดิม',
                    'quantity' => 1,
                    'unit_price' => 5000,
                    'amount' => 5000,
                ],
            ],
        ], $this->user->id);
        $this->quotationService->send($quotation);
        $quotation->refresh();
        $this->quotationService->accept($quotation);
        $quotation->refresh();

        $invoice = $this->conversionService->convertToInvoice($quotation, $this->user->id);

        $quotation->update([
            'payload' => array_merge($quotation->payload, [
                'items' => [
                    [
                        'description' => 'บริการที่แก้ภายหลัง',
                        'quantity' => 1,
                        'unit_price' => 1,
                        'amount' => 1,
                    ],
                ],
            ]),
        ]);

        $invoice->refresh();

        $this->assertSame('บริการเดิม', data_get($invoice->payload, 'items.0.description'));
        $this->assertSame('Invoice', data_get($invoice->payload, 'document.title_en'));
        $this->assertSame($invoice->document_number, data_get($invoice->payload, 'document.number'));
    }

    public function test_conversion_service_cannot_convert_non_accepted_quotation(): void
    {
        $quotation = $this->createQuotation('QT-260501-012');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('เฉพาะใบเสนอราคาที่ได้รับการยอมรับแล้วเท่านั้น');

        $this->conversionService->convertToInvoice($quotation, $this->user->id);
    }

    public function test_conversion_service_cannot_convert_non_quotation(): void
    {
        $invoice = $this->createInvoice('IV-260501-011');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('เอกสารนี้ไม่ใช่ใบเสนอราคา');

        $this->conversionService->convertToInvoice($invoice, $this->user->id);
    }

    public function test_conversion_service_prevents_duplicate_invoices_from_quotation(): void
    {
        $quotation = $this->createQuotation('QT-260501-013');
        $this->quotationService->send($quotation);
        $quotation->refresh();
        $this->quotationService->accept($quotation);
        $quotation->refresh();

        $this->conversionService->convertToInvoice($quotation, $this->user->id);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ใบแจ้งหนี้สำหรับใบเสนอราคานี้มีอยู่แล้ว');

        $this->conversionService->convertToInvoice($quotation, $this->user->id);
    }

    // ============ HELPER METHODS ============

    private function createQuotation(string $documentNumber): SalesDocument
    {
        return $this->quotationService->save([
            'document_type' => 'quotation',
            'document_number' => $documentNumber,
            'document_date' => '2026-05-01',
            'due_date' => '2026-05-08',
            'customer_name' => 'บริษัท ทดสอบ จำกัด',
        ], $this->user->id);
    }

    private function createInvoice(string $documentNumber): SalesDocument
    {
        return $this->invoiceService->save([
            'document_type' => 'invoice',
            'document_number' => $documentNumber,
            'document_date' => '2026-05-01',
            'due_date' => '2026-05-08',
            'customer_name' => 'บริษัท ทดสอบ จำกัด',
        ], $this->user->id);
    }
}
