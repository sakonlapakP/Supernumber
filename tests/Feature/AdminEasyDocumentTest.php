<?php

namespace Tests\Feature;

use App\Models\BillingCustomer;
use App\Models\SalesDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEasyDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_name' => $user->name,
            'admin_user_role' => $user->role,
        ];
    }

    public function test_admin_can_create_easy_document_standard_mode(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-easy-standard',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $customer = BillingCustomer::create([
            'display_name' => 'ลูกค้า มาตรฐาน จำกัด',
            'company_name' => 'ลูกค้า มาตรฐาน จำกัด',
            'contact_name' => 'คุณ มาตรฐาน',
            'tax_id' => '0105550000001',
            'address' => '123 ถนนมาตรฐาน กรุงเทพฯ',
            'email' => 'standard@example.com',
            'phone' => '0812345678',
            'is_active' => true,
        ]);

        $payload = [
            'customerId' => $customer->id,
            'documentType' => 'quotation',
            'items' => [
                [
                    'name' => 'บริการทดสอบพัฒนา',
                    'price' => 50000,
                    'originalPrice' => 50000,
                    'qty' => 1,
                ]
            ],
            'taxMethod' => 'customer-pays', // Standard mode
            'paymentMethod' => 'bank',
            'paymentCondition' => 'full',
            'paymentDetail' => 'โอนเข้าบัญชีบริษัท',
            'contactName' => 'คุณผู้รับมอบ',
            'contactPhone' => '0811112222',
        ];

        $response = $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.easy-documents.create'), $payload);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $document = SalesDocument::query()->sole();
        $this->assertSame('quotation', $document->document_type);
        $this->assertTrue($document->is_draft);
        $this->assertSame($customer->id, $document->customer_id);

        // Verify calculations in payload
        $savedPayload = $document->payload;
        $this->assertSame('standard', $savedPayload['calculation_mode']);
        $this->assertEquals(50000.0, $savedPayload['totals']['subtotal']);
        $this->assertEquals(3500.0, $savedPayload['totals']['vat_amount']);
        $this->assertEquals(53500.0, $savedPayload['totals']['grand_total']);
        $this->assertEquals(1500.0, $savedPayload['totals']['withholding_amount']);
        $this->assertEquals(52000.0, $savedPayload['totals']['net_to_pay']);

        // Verify item mapping in payload
        $this->assertCount(1, $savedPayload['items']);
        $item = $savedPayload['items'][0];
        $this->assertEquals(50000.0, $item['input_unit_price']);
        $this->assertEquals(50000.0, $item['unit_price']);
        $this->assertEquals(50000.0, $item['amount']);
    }

    public function test_admin_can_create_easy_document_reverse_mode(): void
    {
        $this->withoutExceptionHandling();

        $admin = User::factory()->create([
            'username' => 'admin-easy-reverse',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $customer = BillingCustomer::create([
            'display_name' => 'ลูกค้า พิเศษ จำกัด',
            'company_name' => 'ลูกค้า พิเศษ จำกัด',
            'contact_name' => 'คุณ พิเศษ',
            'tax_id' => '0105550000002',
            'address' => '456 ถนนพิเศษ กรุงเทพฯ',
            'email' => 'special@example.com',
            'phone' => '0812345679',
            'is_active' => true,
        ]);

        // input 50,000 in reverse mode -> selling price = 50,000 / 0.97 = 51,546.39
        $payload = [
            'customerId' => $customer->id,
            'documentType' => 'quotation',
            'items' => [
                [
                    'name' => 'บริการ Reverse WHT 3%',
                    'price' => 51546.39, // divided on frontend
                    'originalPrice' => 50000,
                    'qty' => 1,
                ]
            ],
            'taxMethod' => 'we-pay', // Reverse mode
            'paymentMethod' => 'qr',
            'paymentCondition' => 'full',
            'contactName' => 'คุณติดต่อพิเศษ',
        ];

        $response = $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.easy-documents.create'), $payload);

        $response->assertOk();
        
        $document = SalesDocument::query()->sole();
        $savedPayload = $document->payload;

        $this->assertSame('reverse', $savedPayload['calculation_mode']);
        
        // Base amount/Subtotal must be the Selling Price (51,546.39)
        // NOT target income (50,000) nor compounded triple division
        $this->assertEquals(51546.39, $savedPayload['totals']['subtotal']);
        $this->assertEquals(50000.0, $savedPayload['totals']['target_income']);
        
        // VAT = 51,546.39 * 0.07 = 3,608.25
        $this->assertEquals(3608.25, $savedPayload['totals']['vat_amount']);
        
        // Grand Total = 51,546.39 + 3,608.25 = 55,154.64
        $this->assertEquals(55154.64, $savedPayload['totals']['grand_total']);
        
        // WHT = 51,546.39 * 0.03 = 1,546.39
        $this->assertEquals(1546.39, $savedPayload['totals']['withholding_amount']);
        
        // Net to pay = Grand Total - WHT = 55,154.64 - 1,546.39 = 53,608.25
        $this->assertEquals(53608.25, $savedPayload['totals']['net_to_pay']);

        // Verify items
        $item = $savedPayload['items'][0];
        $this->assertEquals(50000.0, $item['input_unit_price']); // Target income
        $this->assertEquals(51546.39, $item['unit_price']); // Selling price
        $this->assertEquals(51546.39, $item['amount']);
    }

    public function test_admin_can_create_easy_document_invoice_type(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-easy-invoice',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $customer = BillingCustomer::create([
            'display_name' => 'ลูกค้า แจ้งหนี้ จำกัด',
            'company_name' => 'ลูกค้า แจ้งหนี้ จำกัด',
            'is_active' => true,
        ]);

        $payload = [
            'customerId' => $customer->id,
            'documentType' => 'invoice', // Invoice type selection
            'items' => [
                [
                    'name' => 'บริการแจ้งหนี้ด่วน',
                    'price' => 10000,
                    'originalPrice' => 10000,
                    'qty' => 1,
                ]
            ],
            'taxMethod' => 'customer-pays',
            'paymentMethod' => 'bank',
            'paymentCondition' => 'full',
        ];

        $response = $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.easy-documents.create'), $payload);

        $response->assertOk();

        $document = SalesDocument::query()->sole();
        $this->assertSame('invoice', $document->document_type);
        $this->assertSame('invoice', $document->payload['document_type']);
        $this->assertStringStartsWith('IV-', $document->payload['document_number']);
    }

    public function test_admin_can_create_easy_document_invoice_with_reference_number(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-easy-invoice-ref',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $customer = BillingCustomer::create([
            'display_name' => 'ลูกค้า แจ้งหนี้ อ้างอิง จำกัด',
            'company_name' => 'ลูกค้า แจ้งหนี้ อ้างอิง จำกัด',
            'is_active' => true,
        ]);

        $payload = [
            'customerId' => $customer->id,
            'documentType' => 'invoice',
            'items' => [
                [
                    'name' => 'บริการแจ้งหนี้ด่วน',
                    'price' => 10000,
                    'originalPrice' => 10000,
                    'qty' => 1,
                ]
            ],
            'taxMethod' => 'customer-pays',
            'paymentMethod' => 'bank',
            'paymentCondition' => 'full',
            'referenceNumber' => 'QT-260523-001',
        ];

        $response = $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.easy-documents.create'), $payload);

        $response->assertOk();

        $document = SalesDocument::query()->sole();
        $this->assertSame('invoice', $document->document_type);
        $this->assertSame('QT-260523-001', $document->payload['document']['reference_number']);
    }

    public function test_easy_document_validation(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-easy-validation',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        // missing customerId and items
        $response = $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.easy-documents.create'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customerId', 'items']);
    }

    // FIX #1 — easy invoice with referenceNumber sets quotation to ACCEPTED (not SENT)
    public function test_easy_invoice_with_reference_number_sets_quotation_to_accepted(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-fix1',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $customer = BillingCustomer::create([
            'display_name' => 'ลูกค้า Fix1 จำกัด',
            'company_name' => 'ลูกค้า Fix1 จำกัด',
            'is_active' => true,
        ]);

        $quotation = \App\Models\SalesDocument::create([
            'document_type' => 'quotation',
            'document_number' => 'QT-FIX1-001',
            'document_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'customer_id' => $customer->id,
            'customer_name' => $customer->display_name,
            'file_name' => 'qt-fix1-001',
            'status' => \App\Models\SalesDocument::STATUS_QUOTATION_DRAFT,
            'is_draft' => true,
            'is_active' => true,
            'pdf_disk' => 'local',
            'pdf_path' => '',
            'payload' => [],
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.easy-documents.create'), [
                'customerId' => $customer->id,
                'documentType' => 'invoice',
                'referenceNumber' => 'QT-FIX1-001',
                'items' => [['name' => 'บริการ', 'price' => 10000, 'originalPrice' => 10000, 'qty' => 1]],
                'taxMethod' => 'customer-pays',
                'paymentMethod' => 'bank',
                'paymentCondition' => 'full',
            ]);

        $response->assertOk();

        $quotation->refresh();
        $this->assertSame(\App\Models\SalesDocument::STATUS_QUOTATION_ACCEPTED, $quotation->status);
    }

    // FIX #2 — easy invoice blocks duplicate when quotation already has an invoice
    public function test_easy_invoice_prevents_duplicate_for_same_quotation(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-fix2',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $customer = BillingCustomer::create([
            'display_name' => 'ลูกค้า Fix2 จำกัด',
            'company_name' => 'ลูกค้า Fix2 จำกัด',
            'is_active' => true,
        ]);

        $quotation = \App\Models\SalesDocument::create([
            'document_type' => 'quotation',
            'document_number' => 'QT-FIX2-001',
            'document_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'customer_id' => $customer->id,
            'customer_name' => $customer->display_name,
            'file_name' => 'qt-fix2-001',
            'status' => \App\Models\SalesDocument::STATUS_QUOTATION_ACCEPTED,
            'is_draft' => false,
            'is_active' => true,
            'pdf_disk' => 'local',
            'pdf_path' => '',
            'payload' => [],
        ]);

        // Existing invoice already linked to the quotation
        \App\Models\SalesDocument::create([
            'document_type' => 'invoice',
            'document_number' => 'IV-FIX2-EXISTING',
            'document_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'customer_id' => $customer->id,
            'customer_name' => $customer->display_name,
            'file_name' => 'iv-fix2-existing',
            'source_quotation_id' => $quotation->id,
            'status' => \App\Models\SalesDocument::STATUS_INVOICE_DRAFT,
            'is_draft' => true,
            'is_active' => true,
            'pdf_disk' => 'local',
            'pdf_path' => '',
            'payload' => [],
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.easy-documents.create'), [
                'customerId' => $customer->id,
                'documentType' => 'invoice',
                'referenceNumber' => 'QT-FIX2-001',
                'items' => [['name' => 'บริการ', 'price' => 10000, 'originalPrice' => 10000, 'qty' => 1]],
                'taxMethod' => 'customer-pays',
                'paymentMethod' => 'bank',
                'paymentCondition' => 'full',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'ใบแจ้งหนี้สำหรับใบเสนอราคานี้มีอยู่แล้ว');

        // No additional invoice should be created
        $this->assertSame(1, \App\Models\SalesDocument::where('document_type', 'invoice')->count());
    }

    // FIX #3 — draft document_number in payload matches the model's document_number
    public function test_easy_quotation_draft_document_number_matches_payload(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-fix3',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $customer = BillingCustomer::create([
            'display_name' => 'ลูกค้า Fix3 จำกัด',
            'company_name' => 'ลูกค้า Fix3 จำกัด',
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.easy-documents.create'), [
                'customerId' => $customer->id,
                'documentType' => 'quotation',
                'items' => [['name' => 'บริการ', 'price' => 10000, 'originalPrice' => 10000, 'qty' => 1]],
                'taxMethod' => 'customer-pays',
                'paymentMethod' => 'bank',
                'paymentCondition' => 'full',
            ]);

        $response->assertOk();

        $document = \App\Models\SalesDocument::query()->sole();
        $this->assertSame($document->document_number, $document->payload['document_number']);
        $this->assertSame($document->document_number, $document->payload['document']['number']);
    }
}
