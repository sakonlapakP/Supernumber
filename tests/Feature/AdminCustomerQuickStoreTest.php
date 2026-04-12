<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerQuickStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_quick_store_customer_from_document_page(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-quick-customer',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->postJson(route('admin.customers.quick-store'), [
                'company_name' => 'บริษัท บันทึกด่วน จำกัด',
                'contact_name' => 'คุณนิดา ทดสอบ',
                'tax_id' => '0105554444444',
                'address' => '99/9 กรุงเทพมหานคร',
                'payment_term' => 'ชำระภายใน 7 วัน',
            ]);

        $response->assertOk();
        $response->assertJsonPath('customer.display_name', 'บริษัท บันทึกด่วน จำกัด');
        $this->assertDatabaseHas('customers', [
            'company_name' => 'บริษัท บันทึกด่วน จำกัด',
            'tax_id' => '0105554444444',
        ]);
    }

    public function test_admin_can_quick_update_customer_from_document_page(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-quick-customer-update',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $customer = Customer::query()->create([
            'company_name' => 'บริษัท เดิม จำกัด',
            'tax_id' => '0105551111111',
            'address' => 'กรุงเทพมหานคร',
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->putJson(route('admin.customers.quick-update', $customer), [
                'company_name' => 'บริษัท ใหม่ จำกัด',
                'contact_name' => 'คุณใหม่ ทดสอบ',
                'tax_id' => '0105552222222',
                'address' => '88/8 กรุงเทพมหานคร',
                'payment_term' => 'เครดิต 15 วัน',
                'email' => 'new@example.com',
            ]);

        $response->assertOk();
        $response->assertJsonPath('customer.display_name', 'บริษัท ใหม่ จำกัด');
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'company_name' => 'บริษัท ใหม่ จำกัด',
            'tax_id' => '0105552222222',
            'email' => 'new@example.com',
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
