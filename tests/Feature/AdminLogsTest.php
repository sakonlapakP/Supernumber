<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_application_logs_page(): void
    {
        File::put(storage_path('logs/laravel.log'), "[2026-03-26 12:00:00] local.ERROR: Sample line for manager view\n");

        $manager = User::factory()->create([
            'username' => 'manager-log-view',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($manager))
            ->get(route('admin.logs'));

        $response->assertOk();
        $response->assertSee('Application Logs');
        $response->assertSee('Sample line for manager view');
    }

    public function test_manager_can_filter_application_logs(): void
    {
        File::put(
            storage_path('logs/custom.log'),
            implode("\n", [
                '[2026-03-26 12:00:00] local.ERROR: Failed payment webhook',
                'Stack trace line',
                '[2026-03-25 09:30:00] local.INFO: Queue worker started',
            ]) . "\n"
        );

        $manager = User::factory()->create([
            'username' => 'manager-log-filter',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($manager))
            ->get(route('admin.logs', [
                'file' => 'custom.log',
                'level' => 'ERROR',
                'date' => '2026-03-26',
                'search' => 'payment',
            ]));

        $response->assertOk();
        $response->assertSee('Failed payment webhook');
        $response->assertSee('Stack trace line');
        $response->assertDontSee('Queue worker started');
    }

    public function test_manager_can_clear_selected_log_file(): void
    {
        $path = storage_path('logs/clear-me.log');
        File::put($path, "[2026-03-26 12:00:00] local.ERROR: Will be cleared\n");

        $manager = User::factory()->create([
            'username' => 'manager-log-clear',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($manager))
            ->post(route('admin.logs.clear'), [
                'file' => 'clear-me.log',
            ]);

        $response->assertRedirect(route('admin.logs', ['file' => 'clear-me.log']));
        $response->assertSessionHas('status_message');
        $this->assertSame('', File::get($path));
    }

    public function test_admin_cannot_view_application_logs_page(): void
    {
        $admin = User::factory()->create([
            'username' => 'admin-log-view',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.logs'))
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
