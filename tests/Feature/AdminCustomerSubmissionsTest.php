<?php

namespace Tests\Feature;

use App\Models\CustomerSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerSubmissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_customer_submissions_dashboard(): void
    {
        CustomerSubmission::query()->create([
            'form_type' => CustomerSubmission::FORM_EVALUATE,
            'name' => 'ลูกค้าทดสอบ',
            'phone' => '0812345678',
            'payload' => ['phone' => '0812345678'],
            'consent_dev' => true,
            'consent_marketing' => false,
            'submitted_at' => now(),
        ]);

        $manager = User::factory()->create([
            'username' => 'manager-submissions',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($manager))
            ->get(route('admin.customer-submissions', ['q' => '0812345678']));

        $response->assertOk();
        $response->assertSee('Submission ลูกค้า');
        $response->assertSee('ลูกค้าทดสอบ');
        $response->assertSee('0812345678');
        $response->assertSee('พัฒนาบริการ: ยินยอม');
        $response->assertSee('โปรโมชั่น: ไม่ยินยอม');
    }

    public function test_admin_cannot_view_customer_submissions_dashboard(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-submissions-denied',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.customer-submissions'));

        $response->assertForbidden();
    }

    public function test_admin_does_not_see_customer_submissions_nav_item(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-no-submissions-nav',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.numbers'));

        $response->assertOk();
        $response->assertDontSee('Submission ลูกค้า');
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
