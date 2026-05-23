<?php

namespace Tests\Feature;

use App\Models\BillingCustomer;
use App\Models\SalesDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiEasyDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_active_customers(): void
    {
        $token = $this->issueTokenForRole(User::ROLE_ADMIN);

        BillingCustomer::create([
            'company_name' => 'Acme Active',
            'first_name' => 'Some',
            'is_active' => true,
        ]);
        BillingCustomer::create([
            'company_name' => 'Inactive Co',
            'is_active' => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('api.admin.billing-customers.index'));

        $response->assertOk()
            ->assertJsonStructure(['customers' => [['id', 'display_name', 'company_name']]]);

        $this->assertCount(1, $response->json('customers'));
        $this->assertSame('Acme Active', $response->json('customers.0.company_name'));
    }

    public function test_staff_cannot_access_easy_document_endpoints(): void
    {
        $token = $this->issueTokenForRole(User::ROLE_STAFF);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('api.admin.billing-customers.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_customer_via_api(): void
    {
        $token = $this->issueTokenForRole(User::ROLE_ADMIN);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('api.admin.billing-customers.store'), [
                'company_name' => 'New Wizard Customer',
                'contact_name' => 'สมชาย ใจดี',
                'tax_id' => '0105550000999',
                'address' => 'BKK',
                'email' => 'wiz@example.com',
                'phone' => '0800000000',
            ]);

        $response->assertCreated()
            ->assertJsonPath('customer.company_name', 'New Wizard Customer')
            ->assertJsonPath('customer.contact_name', 'สมชาย ใจดี');

        $this->assertDatabaseHas('billing_customers', [
            'company_name' => 'New Wizard Customer',
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
        ]);
    }

    public function test_customer_create_requires_company_or_contact_name(): void
    {
        $token = $this->issueTokenForRole(User::ROLE_ADMIN);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('api.admin.billing-customers.store'), ['phone' => '12345'])
            ->assertStatus(422);
    }

    public function test_admin_can_search_quotations(): void
    {
        $token = $this->issueTokenForRole(User::ROLE_ADMIN);

        $customer = BillingCustomer::create(['company_name' => 'Q Customer', 'is_active' => true]);

        SalesDocument::create([
            'document_type' => 'quotation',
            'document_number' => 'QT-SEARCH-001',
            'document_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'customer_id' => $customer->id,
            'customer_name' => 'Acme Search',
            'file_name' => 'qt-search-001',
            'status' => SalesDocument::STATUS_QUOTATION_DRAFT,
            'is_draft' => true,
            'is_active' => true,
            'pdf_disk' => 'local',
            'pdf_path' => '',
            'payload' => ['totals' => ['grand_total' => 1000, 'grand_total_display' => '1,000.00']],
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('api.admin.quotations.search', ['q' => 'SEARCH']));

        $response->assertOk();
        $this->assertSame('QT-SEARCH-001', $response->json('quotations.0.document_number'));
    }

    public function test_admin_can_create_easy_document_via_api(): void
    {
        $token = $this->issueTokenForRole(User::ROLE_ADMIN);

        $customer = BillingCustomer::create([
            'company_name' => 'API Customer',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('api.admin.easy-documents.create'), [
                'customerId' => $customer->id,
                'documentType' => 'quotation',
                'items' => [[
                    'name' => 'บริการ API',
                    'price' => 50000,
                    'originalPrice' => 50000,
                    'qty' => 1,
                ]],
                'taxMethod' => 'customer-pays',
                'paymentMethod' => 'bank',
                'paymentCondition' => 'full',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('document.document_type', 'quotation')
            ->assertJsonPath('document.is_draft', true);

        $document = SalesDocument::query()->sole();
        $this->assertSame($document->document_number, $document->payload['document_number']);
    }

    public function test_api_blocks_duplicate_invoice_from_quotation(): void
    {
        $token = $this->issueTokenForRole(User::ROLE_ADMIN);

        $customer = BillingCustomer::create([
            'company_name' => 'Dup Customer',
            'is_active' => true,
        ]);

        $quotation = SalesDocument::create([
            'document_type' => 'quotation',
            'document_number' => 'QT-API-DUP-001',
            'document_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'customer_id' => $customer->id,
            'customer_name' => $customer->company_name,
            'file_name' => 'qt-api-dup-001',
            'status' => SalesDocument::STATUS_QUOTATION_ACCEPTED,
            'is_draft' => false,
            'is_active' => true,
            'pdf_disk' => 'local',
            'pdf_path' => '',
            'payload' => [],
        ]);

        SalesDocument::create([
            'document_type' => 'invoice',
            'document_number' => 'IV-API-DUP-EXISTING',
            'document_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'customer_id' => $customer->id,
            'customer_name' => $customer->company_name,
            'file_name' => 'iv-api-dup-existing',
            'source_quotation_id' => $quotation->id,
            'status' => SalesDocument::STATUS_INVOICE_DRAFT,
            'is_draft' => true,
            'is_active' => true,
            'pdf_disk' => 'local',
            'pdf_path' => '',
            'payload' => [],
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('api.admin.easy-documents.create'), [
                'customerId' => $customer->id,
                'documentType' => 'invoice',
                'referenceNumber' => 'QT-API-DUP-001',
                'items' => [['name' => 'บริการ', 'price' => 1000, 'originalPrice' => 1000, 'qty' => 1]],
                'taxMethod' => 'customer-pays',
                'paymentMethod' => 'bank',
                'paymentCondition' => 'full',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'ใบแจ้งหนี้สำหรับใบเสนอราคานี้มีอยู่แล้ว');

        $this->assertSame(1, SalesDocument::where('document_type', 'invoice')->count());
    }

    private function issueTokenForRole(string $role): string
    {
        $username = 'api-easy-' . $role . '-' . uniqid();
        $password = 'secret-pass';

        User::factory()->create([
            'username' => $username,
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make($password),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => $username,
            'password' => $password,
            'device_name' => 'ApiEasyDocumentTest',
        ]);

        $response->assertOk();

        return (string) $response->json('token');
    }
}
