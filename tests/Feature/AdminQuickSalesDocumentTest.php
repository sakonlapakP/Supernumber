<?php

namespace Tests\Feature;

use App\Models\BillingCustomer;
use App\Models\SalesDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQuickSalesDocumentTest extends TestCase
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

    public function test_admin_can_view_quick_documents_page(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-quick-view',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.sales-documents-quick'));

        $response->assertOk();
        $response->assertSee('สร้างเอกสารด่วน');
        $response->assertSee('ชื่อลูกค้า / บริษัท');
    }

    public function test_admin_can_load_easy_mode_draft_in_quick_mode(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-quick-draft',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $customer = BillingCustomer::create([
            'company_name' => 'ดราฟท์ แพลนเนอร์',
            'is_active' => true,
        ]);

        $document = SalesDocument::create([
            'document_type' => 'quotation',
            'document_number' => 'DRAFT-123456',
            'document_date' => '2026-05-23',
            'due_date' => '2026-05-30',
            'file_name' => 'draft-123456',
            'customer_id' => $customer->id,
            'customer_name' => $customer->display_name,
            'is_draft' => true,
            'is_active' => true,
            'pdf_disk' => 'local',
            'pdf_path' => '',
            'saved_by_user_id' => $admin->id,
            'payload' => [
                'document_type' => 'quotation',
                'document_number' => 'QT-260523-001',
                'customer_id' => $customer->id,
                'customer_name' => $customer->display_name,
                'customer' => [
                    'name' => $customer->display_name,
                ],
                'items' => [
                    [
                        'index' => 1,
                        'description' => 'บริการล้ำเลิศ',
                        'quantity' => 1,
                        'unit_price' => 51546.39,
                        'input_unit_price' => 50000.0,
                    ]
                ],
                'tax_method' => 'we-pay',
                'totals' => [
                    'subtotal' => 51546.39,
                    'grand_total' => 55154.64,
                ]
            ],
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.sales-documents-quick', ['draft' => $document->id]));

        $response->assertOk();
        $response->assertViewHas('prefillPayload');
        $response->assertViewHas('currentDraftId', $document->id);
        
        $prefillPayload = $response->viewData('prefillPayload');
        $this->assertSame('quotation', $prefillPayload['document_type']);
        $this->assertSame('ดราฟท์ แพลนเนอร์', $prefillPayload['customer_name']);
        $this->assertSame('บริการล้ำเลิศ', $prefillPayload['items'][0]['description']);
    }
}
