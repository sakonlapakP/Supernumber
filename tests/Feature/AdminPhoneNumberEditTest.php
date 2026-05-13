<?php

namespace Tests\Feature;

use App\Models\PhoneNumber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPhoneNumberEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_phone_number_edit_form(): void
    {
        $phoneNumber = PhoneNumber::query()->create([
            'phone_number' => '0891234567',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => 'true_dtac',
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $manager = User::factory()->create([
            'username' => 'manager-number-edit-view',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->withSession($this->adminSession($manager))
            ->get(route('admin.numbers.edit', $phoneNumber))
            ->assertOk()
            ->assertSee('แก้ไขเบอร์')
            ->assertSee('089-123-4567');
    }

    public function test_admin_can_change_number_status_and_log_it(): void
    {
        $phoneNumber = PhoneNumber::query()->create([
            'phone_number' => '0891234567',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => 'true_dtac',
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $manager = User::factory()->create([
            'username' => 'manager-number-edit-save',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->withSession($this->adminSession($manager))
            ->put(route('admin.numbers.update', $phoneNumber), [
                'status' => PhoneNumber::STATUS_HOLD,
            ])
            ->assertRedirect(route('admin.numbers.edit', $phoneNumber));

        $this->assertDatabaseHas('phone_numbers', [
            'id' => $phoneNumber->id,
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => 'true_dtac',
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        $this->assertDatabaseHas('phone_number_status_logs', [
            'phone_number_id' => $phoneNumber->id,
            'user_id' => $manager->id,
            'action' => 'hold',
            'from_status' => PhoneNumber::STATUS_ACTIVE,
            'to_status' => PhoneNumber::STATUS_HOLD,
        ]);
    }

    public function test_admin_can_change_number_status_to_unactive_and_log_it(): void
    {
        $phoneNumber = PhoneNumber::query()->create([
            'phone_number' => '0891234567',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => 'true_dtac',
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $manager = User::factory()->create([
            'username' => 'manager-number-edit-unactive',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->withSession($this->adminSession($manager))
            ->put(route('admin.numbers.update', $phoneNumber), [
                'status' => PhoneNumber::STATUS_UNACTIVE,
            ])
            ->assertRedirect(route('admin.numbers.edit', $phoneNumber));

        $this->assertDatabaseHas('phone_numbers', [
            'id' => $phoneNumber->id,
            'status' => PhoneNumber::STATUS_UNACTIVE,
        ]);

        $this->assertDatabaseHas('phone_number_status_logs', [
            'phone_number_id' => $phoneNumber->id,
            'user_id' => $manager->id,
            'action' => 'deactivate',
            'from_status' => PhoneNumber::STATUS_ACTIVE,
            'to_status' => PhoneNumber::STATUS_UNACTIVE,
        ]);
    }

    public function test_admin_can_filter_numbers_page_by_service_type_from_sidebar(): void
    {
        PhoneNumber::query()->create([
            'phone_number' => '0891234567',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => 'true_dtac',
            'plan_name' => 'เติมเงิน',
            'sale_price' => 5000,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        PhoneNumber::query()->create([
            'phone_number' => '0811112222',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => 'true_dtac',
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $manager = User::factory()->create([
            'username' => 'manager-number-filter',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->withSession($this->adminSession($manager))
            ->get(route('admin.numbers', ['service_type' => PhoneNumber::SERVICE_TYPE_PREPAID]))
            ->assertOk()
            ->assertSee('เบอร์เติมเงิน')
            ->assertSee('089-123-4567')
            ->assertDontSee('081-111-2222');
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
