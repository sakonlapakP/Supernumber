<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserApprovalTest extends TestCase
{
    use RefreshDatabase;

    // ── Registration ──────────────────────────────────────────────────────────

    public function test_registration_creates_inactive_staff_user(): void
    {
        $this->post(route('admin.register.store'), [
            'name' => 'New User',
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('admin.login'));

        $user = User::where('username', 'newuser')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->is_active);
        $this->assertSame(User::ROLE_STAFF, $user->role);
    }

    // ── Login error messages ──────────────────────────────────────────────────

    public function test_pending_user_gets_approval_error_on_login(): void
    {
        User::factory()->create([
            'username' => 'pendinguser',
            'is_active' => false,
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('admin.login.attempt'), [
            'username' => 'pendinguser',
            'password' => 'Password123!',
        ])->assertSessionHasErrors(['username']);

        $errors = session('errors');
        $this->assertStringContainsString('รอการอนุมัติ', $errors->first('username'));
    }

    public function test_wrong_password_gets_generic_error(): void
    {
        User::factory()->create([
            'username' => 'activeuser',
            'is_active' => true,
            'role' => User::ROLE_STAFF,
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $this->post(route('admin.login.attempt'), [
            'username' => 'activeuser',
            'password' => 'WrongPassword!',
        ])->assertSessionHasErrors(['username']);

        $errors = session('errors');
        $this->assertStringContainsString('ไม่ถูกต้อง', $errors->first('username'));
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function test_manager_can_approve_pending_user(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $pending = User::factory()->create([
            'is_active' => false,
            'role' => User::ROLE_STAFF,
        ]);

        $this->withSession($this->managerSession($manager))
            ->post(route('admin.users.approve', $pending), ['role' => User::ROLE_ADMIN])
            ->assertRedirect(route('admin.users'));

        $pending->refresh();
        $this->assertTrue($pending->is_active);
        $this->assertSame(User::ROLE_ADMIN, $pending->role);
    }

    public function test_manager_can_approve_document_officer_user(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $pending = User::factory()->create([
            'is_active' => false,
            'role' => User::ROLE_STAFF,
        ]);

        $this->withSession($this->managerSession($manager))
            ->post(route('admin.users.approve', $pending), ['role' => User::ROLE_DOCUMENT_OFFICER])
            ->assertRedirect(route('admin.users'));

        $pending->refresh();
        $this->assertTrue($pending->is_active);
        $this->assertSame(User::ROLE_DOCUMENT_OFFICER, $pending->role);
    }

    public function test_document_officer_login_redirects_to_sales_documents(): void
    {
        User::factory()->create([
            'username' => 'document-login',
            'role' => User::ROLE_DOCUMENT_OFFICER,
            'is_active' => true,
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('admin.login.attempt'), [
            'username' => 'document-login',
            'password' => 'Password123!',
        ])->assertRedirect(route('admin.saved-sales-documents.index'));
    }

    public function test_non_manager_cannot_approve_users(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $pending = User::factory()->create(['is_active' => false]);

        $this->withSession($this->managerSession($admin))
            ->post(route('admin.users.approve', $pending), ['role' => User::ROLE_STAFF])
            ->assertForbidden();
    }

    // ── Email updates ────────────────────────────────────────────────────────

    public function test_manager_can_update_any_user_email(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        $this->withSession($this->managerSession($manager))
            ->post(route('admin.users.email.update', $user), ['email' => 'new@example.com'])
            ->assertRedirect(route('admin.users'));

        $this->assertSame('new@example.com', $user->refresh()->email);
    }

    public function test_manager_cannot_update_user_email_to_existing_email(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $existing = User::factory()->create([
            'email' => 'existing@example.com',
        ]);
        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        $this->withSession($this->managerSession($manager))
            ->post(route('admin.users.email.update', $user), ['email' => $existing->email])
            ->assertSessionHasErrors(['email']);

        $this->assertSame('old@example.com', $user->refresh()->email);
    }

    public function test_non_manager_cannot_update_user_email(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'old@example.com',
        ]);

        $this->withSession($this->managerSession($admin))
            ->post(route('admin.users.email.update', $user), ['email' => 'new@example.com'])
            ->assertForbidden();

        $this->assertSame('old@example.com', $user->refresh()->email);
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function test_manager_can_reject_pending_user(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $pending = User::factory()->create(['is_active' => false]);
        $pendingId = $pending->id;

        $this->withSession($this->managerSession($manager))
            ->post(route('admin.users.reject', $pending))
            ->assertRedirect(route('admin.users'));

        $this->assertNull(User::find($pendingId));
    }

    public function test_manager_cannot_reject_active_user(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $active = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->withSession($this->managerSession($manager))
            ->post(route('admin.users.reject', $active))
            ->assertSessionHasErrors();

        $this->assertNotNull(User::find($active->id));
    }

    // ── Deactivate ────────────────────────────────────────────────────────────

    public function test_manager_can_deactivate_active_user(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $active = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        $this->withSession($this->managerSession($manager))
            ->post(route('admin.users.toggle-active', $active))
            ->assertRedirect(route('admin.users'));

        $active->refresh();
        $this->assertFalse($active->is_active);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        User::factory()->create([
            'username' => 'deactivated',
            'is_active' => false,
            'role' => User::ROLE_STAFF,
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('admin.login.attempt'), [
            'username' => 'deactivated',
            'password' => 'Password123!',
        ])->assertSessionHasErrors(['username']);
    }

    private function managerSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_role' => $user->role,
        ];
    }
}
