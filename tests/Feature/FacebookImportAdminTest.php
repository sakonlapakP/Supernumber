<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacebookImportAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_facebook_import_page(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($manager))
            ->get(route('admin.facebook-imports'));

        $response->assertOk();
        $response->assertSee('นำเข้าโพสต์ Facebook');
    }

    public function test_admin_cannot_view_facebook_import_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.facebook-imports'))
            ->assertForbidden();
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
