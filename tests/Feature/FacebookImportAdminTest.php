<?php

namespace Tests\Feature;

use App\Models\FacebookImportedPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_manager_can_sync_facebook_posts_with_paging_next(): void
    {
        config()->set('services.facebook.page_id', '123456');
        config()->set('services.facebook.graph_access_token', 'test-token');
        config()->set('services.facebook.graph_api_version', 'v20.0');

        Http::fake([
            'https://graph.facebook.com/*' => Http::sequence()
                ->push([
                    'data' => [
                        [
                            'id' => '123456_1',
                            'message' => 'โพสต์แรก',
                            'created_time' => '2025-01-01T10:00:00+0000',
                            'permalink_url' => 'https://facebook.com/post/1',
                        ],
                    ],
                    'paging' => [
                        'next' => 'https://graph.facebook.com/v20.0/123456/feed?page=2',
                    ],
                ], 200)
                ->push([
                    'data' => [
                        [
                            'id' => '123456_2',
                            'message' => 'โพสต์สอง',
                            'created_time' => '2025-01-02T10:00:00+0000',
                            'permalink_url' => 'https://facebook.com/post/2',
                        ],
                    ],
                ], 200),
        ]);

        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($manager))
            ->post(route('admin.facebook-imports.sync'), [
                'node_id' => '123456',
                'edge' => 'feed',
                'since' => '',
                'until' => '2025-12-31',
                'limit' => 100,
                'max_pages' => 50,
                'access_token' => '',
            ]);

        $response->assertRedirect(route('admin.facebook-imports'));
        $response->assertSessionHas('status_message');

        $this->assertDatabaseCount('facebook_imported_posts', 2);
        $this->assertDatabaseHas('facebook_imported_posts', [
            'facebook_post_id' => '123456_1',
        ]);
        $this->assertDatabaseHas('facebook_imported_posts', [
            'facebook_post_id' => '123456_2',
        ]);

        $first = FacebookImportedPost::query()->where('facebook_post_id', '123456_1')->first();
        $this->assertNotNull($first);
        $this->assertSame('โพสต์แรก', $first?->message);
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
