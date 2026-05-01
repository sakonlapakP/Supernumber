<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ทดสอบว่า Manager ไม่สามารถเข้าหน้า Auto Messages ได้ (ต้องได้รับ 403 Forbidden)
     */
    public function test_manager_cannot_access_auto_messages_settings(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($manager))
            ->get(route('admin.auto-messages'));

        // ตรวจสอบว่าต้องได้รับ 403 Forbidden ตาม Logic ใน web.php
        $response->assertForbidden();
    }

    /**
     * ทดสอบว่า Admin สามารถเข้าหน้า Auto Messages ได้ปกติ
     */
    public function test_admin_can_access_auto_messages_settings(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.auto-messages'));

        $response->assertOk();
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
