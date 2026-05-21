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

    public function test_admin_can_create_easy_document_draft(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-easy-document',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $customer = BillingCustomer::query()->create([
            'company_name' => 'บริษัท ทดสอบเอกสาร จำกัด',
            'tax_id' => '0105551234567',
            'address' => 'กรุงเทพมหานคร',
            'phone' => '0648246915',
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.easy-documents.create'), [
                'customerId' => $customer->id,
                'items' => [
                    [
                        'name' => 'Item',
                        'price' => 51546.39,
                        'qty' => 1,
                    ],
                ],
                'taxMethod' => 'customer-pays',
                'paymentMethod' => 'bank',
                'paymentCondition' => 'full',
                'paymentDetail' => '',
                'contactName' => 'คุณเอกสาร',
                'contactPhone' => '0890000000',
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'สร้างร่างเอกสารสำเร็จ',
            ]);

        $document = SalesDocument::query()->sole();

        $this->assertTrue($document->is_draft);
        $this->assertSame('quotation', $document->document_type);
        $this->assertSame($customer->display_name, $document->customer_name);
        $this->assertSame('standard', $document->payload['calculation_mode']);
        $this->assertSame('Item', $document->payload['items'][0]['description']);
        $this->assertSame(1, $document->payload['items'][0]['quantity']);
        $this->assertSame(51546.39, $document->payload['items'][0]['input_unit_price']);
        $this->assertSame('คุณเอกสาร', $document->payload['customer']['contact']);
        $this->assertSame('0890000000', $document->payload['customer']['phone']);
        $this->assertSame('transfer', $document->payload['payment']['method']);
    }

    public function test_easy_reverse_draft_keeps_target_income_for_quick_editor(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-easy-reverse-document',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $customer = BillingCustomer::query()->create([
            'company_name' => 'บริษัท Reverse จำกัด',
            'is_active' => true,
        ]);

        $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.easy-documents.create'), [
                'customerId' => $customer->id,
                'items' => [
                    [
                        'name' => 'Target Item',
                        'price' => 51546.39,
                        'originalPrice' => 50000,
                        'qty' => 1,
                    ],
                ],
                'taxMethod' => 'we-pay',
                'paymentMethod' => 'cash',
                'paymentCondition' => 'full',
                'paymentDetail' => '',
            ])
            ->assertOk();

        $document = SalesDocument::query()->sole();

        $this->assertSame('reverse', $document->payload['calculation_mode']);
        $this->assertEquals(50000.0, $document->payload['items'][0]['input_unit_price']);
        $this->assertSame('cash', $document->payload['payment']['method']);
    }

    public function test_easy_document_validation_returns_json_for_wizard_request(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-invalid-easy-document',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.easy-documents.create'), []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customerId',
                'items',
                'taxMethod',
                'paymentMethod',
                'paymentCondition',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_name' => $user->name,
            'admin_user_role' => $user->role,
        ];
    }
}
