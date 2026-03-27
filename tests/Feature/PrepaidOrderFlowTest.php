<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\PhoneNumber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrepaidOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepaid_book_page_shows_fixed_price_flow(): void
    {
        PhoneNumber::query()->create([
            'phone_number' => '0891234567',
            'display_number' => '089-123-4567',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => 'true_dtac',
            'plan_name' => 'เติมเงิน',
            'sale_price' => 5000,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $response = $this->get('/book?number=0891234567');

        $response->assertOk();
        $response->assertSee('ราคาขายเบอร์');
        $response->assertSee('ไม่ต้องยืนยันตัวตน');
        $response->assertSee('ตรวจสอบข้อมูลก่อนยืนยันคำสั่งซื้อ');
    }

    public function test_prepaid_step_two_save_creates_pending_review_order_and_holds_the_number(): void
    {
        Storage::fake('public');

        $phoneNumber = PhoneNumber::query()->create([
            'phone_number' => '0891234567',
            'display_number' => '089-123-4567',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => 'true_dtac',
            'plan_name' => 'เติมเงิน',
            'sale_price' => 5000,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $response = $this->post(route('book.save-step2'), [
            'ordered_number' => '089-123-4567',
            'selected_package' => 5000,
            'title_prefix' => 'คุณ',
            'first_name' => 'สมหญิง',
            'last_name' => 'ใจดี',
            'current_phone' => '081-111-2222',
            'shipping_address_line' => '123 ถนนสุขุมวิท',
            'district' => 'คลองเตย',
            'amphoe' => 'คลองเตย',
            'province' => 'กรุงเทพมหานคร',
            'zipcode' => '10110',
            'payment_slip' => UploadedFile::fake()->image('slip.jpg'),
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $this->assertDatabaseHas('customer_orders', [
            'ordered_number' => '0891234567',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'selected_package' => 5000,
            'status' => 'pending_review',
        ]);

        $this->assertDatabaseHas('phone_numbers', [
            'id' => $phoneNumber->id,
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        $this->assertDatabaseHas('phone_number_status_logs', [
            'phone_number_id' => $phoneNumber->id,
            'action' => 'hold',
            'from_status' => PhoneNumber::STATUS_ACTIVE,
            'to_status' => PhoneNumber::STATUS_HOLD,
        ]);
    }

    public function test_prepaid_order_marked_as_sold_changes_number_status_to_sold(): void
    {
        $phoneNumber = PhoneNumber::query()->create([
            'phone_number' => '0891234567',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => 'true_dtac',
            'sale_price' => 5000,
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'selected_package' => 5000,
            'payment_slip_path' => '202603/1.png',
            'status' => 'pending_review',
        ]);

        $manager = User::factory()->create([
            'username' => 'manager-prepaid-sold',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->withSession($this->adminSession($manager))
            ->put(route('admin.orders.update', $order), [
                'ordered_number' => '0891234567',
                'selected_package' => 5000,
                'status' => 'sold',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('phone_numbers', [
            'id' => $phoneNumber->id,
            'status' => PhoneNumber::STATUS_SOLD,
        ]);
    }

    public function test_prepaid_order_rejected_returns_number_to_active(): void
    {
        $phoneNumber = PhoneNumber::query()->create([
            'phone_number' => '0891234567',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => 'true_dtac',
            'sale_price' => 5000,
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'selected_package' => 5000,
            'payment_slip_path' => '202603/1.png',
            'status' => 'pending_review',
        ]);

        $manager = User::factory()->create([
            'username' => 'manager-prepaid-rejected',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->withSession($this->adminSession($manager))
            ->put(route('admin.orders.update', $order), [
                'ordered_number' => '0891234567',
                'selected_package' => 5000,
                'status' => 'rejected',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('phone_numbers', [
            'id' => $phoneNumber->id,
            'status' => PhoneNumber::STATUS_ACTIVE,
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
