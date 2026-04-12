<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_do_not_seed_default_privileged_users(): void
    {
        $this->assertDatabaseMissing('users', [
            'username' => 'admin',
        ]);

        $this->assertDatabaseMissing('users', [
            'username' => 'manager',
        ]);
    }

    public function test_admin_create_user_command_creates_a_manager_account(): void
    {
        $this->artisan('admin:create-user', [
            'username' => 'manager-secure',
            'password' => 'secret12345',
            '--name' => 'Secure Manager',
            '--email' => 'manager-secure@example.com',
            '--role' => User::ROLE_MANAGER,
        ])->assertExitCode(0);

        /** @var User $user */
        $user = User::query()
            ->where('username', 'manager-secure')
            ->firstOrFail();

        $this->assertSame(User::ROLE_MANAGER, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('secret12345', $user->password));
    }
}
