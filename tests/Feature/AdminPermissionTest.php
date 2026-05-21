<?php

namespace Tests\Feature;

use App\Models\PhoneNumber;
use App\Models\PhonePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ทดสอบว่า Manager สามารถเข้าหน้า Auto Messages ได้ปกติ
     */
    public function test_manager_can_access_auto_messages_settings(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($manager))
            ->get(route('admin.auto-messages'));

        $response->assertOk();
    }

    /**
     * ทดสอบว่า Admin (ปกติ) ไม่สามารถเข้าหน้า Auto Messages ได้ (ต้องได้รับ 403 Forbidden)
     */
    public function test_admin_cannot_access_auto_messages_settings(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.auto-messages'));

        // ต้องได้รับ 403 Forbidden เพราะหน้านี้จำกัดไว้ให้ Manager เท่านั้น
        $response->assertForbidden();
    }

    public function test_admin_can_access_phone_packages_page(): void
    {
        PhonePackage::query()->create([
            'code' => 'TRUE699',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => 'true_dtac',
            'name' => 'True Super Value 699',
            'monthly_price' => 699,
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.phone-packages'));

        $response->assertOk();
        $response->assertSee('True Super Value 699');
    }

    public function test_staff_cannot_access_phone_packages_page(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($staff))
            ->get(route('admin.phone-packages'));

        $response->assertForbidden();
    }

    private function adminSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_role' => $user->role,
        ];
    }
}
