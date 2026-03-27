<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LineWebhookEvent;
use App\Services\EnvironmentEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLineSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_line_settings_page(): void
    {
        $envPath = storage_path('framework/testing/line-settings-view.env');
        file_put_contents($envPath, "LINE_CHANNEL_ACCESS_TOKEN=view-token\nLINE_CHANNEL_SECRET=view-secret\nLINE_GROUP_ID=view-group\n");

        $this->app->instance(EnvironmentEditor::class, new EnvironmentEditor($envPath));

        $manager = User::factory()->create([
            'username' => 'manager-line-view',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->managerSession($manager))
            ->get(route('admin.line-settings'));

        $response->assertOk();
        $response->assertSee('LINE Settings');
        $response->assertSee('view-token');
        $response->assertSee('view-secret');
        $response->assertSee('view-group');
        $response->assertSee(route('line.webhook'));
    }

    public function test_admin_can_view_line_settings_page(): void
    {
        $envPath = storage_path('framework/testing/line-settings-admin-view.env');
        file_put_contents($envPath, "LINE_CHANNEL_ACCESS_TOKEN=admin-view-token\nLINE_CHANNEL_SECRET=admin-view-secret\nLINE_GROUP_ID=admin-view-group\n");

        $this->app->instance(EnvironmentEditor::class, new EnvironmentEditor($envPath));

        $admin = User::factory()->create([
            'username' => 'admin-line-view',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->managerSession($admin))
            ->get(route('admin.line-settings'));

        $response->assertOk();
        $response->assertSee('LINE Settings');
        $response->assertSee('admin-view-token');
        $response->assertSee('admin-view-secret');
        $response->assertSee('admin-view-group');
    }

    public function test_manager_can_update_line_settings_from_admin_page(): void
    {
        $envPath = storage_path('framework/testing/line-settings-update.env');
        file_put_contents($envPath, "LINE_CHANNEL_ACCESS_TOKEN=old-token\nLINE_CHANNEL_SECRET=old-secret\nLINE_GROUP_ID=old-group\n");

        $this->app->instance(EnvironmentEditor::class, new EnvironmentEditor($envPath));

        $manager = User::factory()->create([
            'username' => 'manager-line-update',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->managerSession($manager))
            ->post(route('admin.line-settings.update'), [
                'line_channel_access_token' => 'new-token-value',
                'line_channel_secret' => 'new-secret-value',
                'line_group_id' => 'new-group-id',
            ]);

        $response->assertRedirect(route('admin.line-settings'));
        $response->assertSessionHas('status_message');

        $contents = (string) file_get_contents($envPath);

        $this->assertStringContainsString('LINE_CHANNEL_ACCESS_TOKEN=new-token-value', $contents);
        $this->assertStringContainsString('LINE_CHANNEL_SECRET=new-secret-value', $contents);
        $this->assertStringContainsString('LINE_GROUP_ID=new-group-id', $contents);
    }

    public function test_manager_can_apply_group_id_from_a_captured_webhook_event(): void
    {
        $envPath = storage_path('framework/testing/line-settings-group.env');
        file_put_contents($envPath, "LINE_GROUP_ID=old-group\n");

        $this->app->instance(EnvironmentEditor::class, new EnvironmentEditor($envPath));

        $manager = User::factory()->create([
            'username' => 'manager-line-group',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        LineWebhookEvent::query()->create([
            'event_type' => 'message',
            'source_type' => 'group',
            'group_id' => 'C1234567890abcdef',
            'received_at' => now(),
        ]);

        $response = $this
            ->withSession($this->managerSession($manager))
            ->post(route('admin.line-settings.apply-group-id'), [
                'group_id' => 'C1234567890abcdef',
            ]);

        $response->assertRedirect(route('admin.line-settings'));

        $contents = (string) file_get_contents($envPath);

        $this->assertStringContainsString('LINE_GROUP_ID=C1234567890abcdef', $contents);
    }

    /**
     * @return array<string, mixed>
     */
    private function managerSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_name' => $user->name,
            'admin_user_role' => $user->role,
        ];
    }
}
