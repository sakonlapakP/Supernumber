<?php

namespace Tests\Feature;

use App\Models\BillingCustomer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSalesDocumentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_sales_document_workspace(): void
    {
        BillingCustomer::query()->create([
            'company_name' => 'บริษัท เลือกจากรายการ จำกัด',
            'tax_id' => '0105559999999',
            'address' => 'กรุงเทพมหานคร',
            'payment_term' => 'เครดิต 30 วัน',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'username' => 'admin-sales-docs',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.sales-documents'));

        $response->assertOk();
        $response->assertSee('Quotation');
        $response->assertSee('บันทึกและเปิด PDF');
        $response->assertSee('บันทึกร่าง');
        $response->assertSee('บริษัท เลือกจากรายการ จำกัด');
        $response->assertSee('ลูกค้ารับผิดชอบภาษีหัก ณ ที่จ่าย');
        $response->assertSee('เรารับผิดชอบภาษีหัก ณ ที่จ่าย');
        $response->assertSee('ลูกค้ารับผิดชอบภาษีมูลค่าเพิ่ม');
        $response->assertSee('เรารับผิดชอบภาษีมูลค่าเพิ่ม');
        $response->assertDontSee('<<<<<<<');
        $response->assertDontSee('>>>>>>>');
    }

    public function test_manager_can_view_sales_document_workspace(): void
    {
        $manager = User::factory()->create([
            'username' => 'manager-sales-docs',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($manager))
            ->get(route('admin.sales-documents'));

        $response->assertOk();
        $response->assertSee('ใบแจ้งหนี้');
        $response->assertSee('บันทึกและเปิด PDF');
        $response->assertSee('บันทึกร่าง');
    }

    public function test_admin_can_view_quick_sales_document_form_updates(): void
    {
        BillingCustomer::query()->create([
            'phone' => '0644623651',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'username' => 'admin-sales-docs-quick',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.sales-documents-quick'));

        $response->assertOk();
        $response->assertSee('วันที่ออกเอกสาร');
        $response->assertSee('วันสิ้นสุดเอกสาร');
        $response->assertSee('0644623651');
        $response->assertSee('data-qdoc-payment-method', false);
        $response->assertSee('data-qdoc-payment-bank value="ธนาคารกสิกรไทย บจก. ซุปเปอร์นัมเบอร์" readonly', false);
        $response->assertSee('data-qdoc-customer-name placeholder="เลือกชื่อบริษัทด้านบน" readonly', false);
        $response->assertDontSee('วันที่เอกสาร');
        $response->assertDontSee('data-qdoc-payment-transfer', false);
    }

    public function test_document_officer_can_view_sales_document_workspace(): void
    {
        $documentOfficer = User::factory()->create([
            'username' => 'document-officer-sales-docs',
            'role' => User::ROLE_DOCUMENT_OFFICER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($documentOfficer))
            ->get(route('admin.sales-documents'));

        $response->assertOk();
        $response->assertSee('ใบแจ้งหนี้');
        $response->assertSee('บันทึกและเปิด PDF');
        $response->assertSee('บันทึกร่าง');
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $response = $this->get(route('admin.sales-documents'));

        $response->assertRedirect(route('admin.login'));
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
