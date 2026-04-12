<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_customers_page(): void
    {
        Customer::query()->create([
            'company_name' => 'บริษัท ลูกค้าทดสอบ จำกัด',
            'tax_id' => '0105550000001',
            'address' => 'กรุงเทพมหานคร',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'username' => 'admin-customers-view',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.customers'));

        $response->assertOk();
        $response->assertSee('ลูกค้า');
        $response->assertSee('บริษัท ลูกค้าทดสอบ จำกัด');
    }

    public function test_admin_can_create_customer(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-customers-create',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->post(route('admin.customers.store'), [
                'company_name' => 'บริษัท ทดสอบสร้างลูกค้า จำกัด',
                'first_name' => 'สมชาย',
                'last_name' => 'ใจดี',
                'tax_id' => '0105551111111',
                'address' => '123 ถนนสุขุมวิท กรุงเทพฯ',
                'email' => 'customer@example.com',
                'phone' => '0812345678',
                'payment_term' => 'เครดิต 15 วัน',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.customers'));
        $response->assertSessionHas('status_message');
        $this->assertDatabaseHas('customers', [
            'company_name' => 'บริษัท ทดสอบสร้างลูกค้า จำกัด',
            'tax_id' => '0105551111111',
            'payment_term' => 'เครดิต 15 วัน',
        ]);
    }

    public function test_admin_can_update_customer(): void
    {
        $customer = Customer::query()->create([
            'company_name' => 'บริษัท ก่อนแก้ไข จำกัด',
            'tax_id' => '0105552222222',
            'address' => 'ที่อยู่เดิม',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'username' => 'admin-customers-update',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->put(route('admin.customers.update', $customer), [
                'company_name' => 'บริษัท หลังแก้ไข จำกัด',
                'first_name' => 'วิภา',
                'last_name' => 'รุ่งเรือง',
                'tax_id' => '0105553333333',
                'address' => 'ที่อยู่ใหม่',
                'email' => 'updated@example.com',
                'phone' => '0899999999',
                'payment_term' => 'ชำระทันที',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.customers'));
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'company_name' => 'บริษัท หลังแก้ไข จำกัด',
            'tax_id' => '0105553333333',
            'payment_term' => 'ชำระทันที',
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
