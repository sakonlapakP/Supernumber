<?php

namespace Tests\Feature;

use App\Models\FacebookImportedPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiFacebookImportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_fetch_facebook_import_posts(): void
    {
        FacebookImportedPost::query()->create([
            'facebook_post_id' => 'page_1',
            'message' => 'โพสต์ทดสอบ manager',
            'permalink_url' => 'https://facebook.com/post/1',
            'full_picture' => 'https://example.com/image.jpg',
            'facebook_created_time' => now()->subDay(),
            'last_synced_at' => now(),
        ]);

        $token = $this->issueTokenForRole(User::ROLE_MANAGER);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/facebook-imports')
            ->assertOk()
            ->assertJsonPath('data.0.facebook_post_id', 'page_1')
            ->assertJsonPath('data.0.image_url', 'https://example.com/image.jpg');
    }

    public function test_image_url_falls_back_to_attachments_data(): void
    {
        FacebookImportedPost::query()->create([
            'facebook_post_id' => 'page_2',
            'message' => 'โพสต์ fallback image',
            'attachments_json' => [
                'data' => [
                    [
                        'media' => [
                            'image' => [
                                'src' => 'https://example.com/fallback.jpg',
                            ],
                        ],
                    ],
                ],
            ],
            'facebook_created_time' => now()->subHours(2),
            'last_synced_at' => now(),
        ]);

        $token = $this->issueTokenForRole(User::ROLE_MANAGER);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/facebook-imports')
            ->assertOk()
            ->assertJsonPath('data.0.image_url', 'https://example.com/fallback.jpg');
    }

    public function test_admin_cannot_access_facebook_import_posts_api(): void
    {
        $token = $this->issueTokenForRole(User::ROLE_ADMIN);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/facebook-imports')
            ->assertForbidden();
    }

    public function test_manager_can_delete_single_facebook_import_post(): void
    {
        $post = FacebookImportedPost::query()->create([
            'facebook_post_id' => 'page_delete_1',
            'message' => 'โพสต์สำหรับลบเดี่ยว',
            'facebook_created_time' => now()->subDay(),
            'last_synced_at' => now(),
        ]);

        $token = $this->issueTokenForRole(User::ROLE_MANAGER);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/facebook-imports/' . $post->id)
            ->assertOk()
            ->assertJsonPath('deleted_id', $post->id);

        $this->assertDatabaseMissing('facebook_imported_posts', [
            'id' => $post->id,
        ]);
    }

    public function test_manager_can_bulk_delete_selected_facebook_import_posts(): void
    {
        $first = FacebookImportedPost::query()->create([
            'facebook_post_id' => 'page_bulk_1',
            'message' => 'โพสต์สำหรับลบชุด 1',
            'facebook_created_time' => now()->subDay(),
            'last_synced_at' => now(),
        ]);
        $second = FacebookImportedPost::query()->create([
            'facebook_post_id' => 'page_bulk_2',
            'message' => 'โพสต์สำหรับลบชุด 2',
            'facebook_created_time' => now()->subHours(20),
            'last_synced_at' => now(),
        ]);
        $third = FacebookImportedPost::query()->create([
            'facebook_post_id' => 'page_bulk_3',
            'message' => 'โพสต์สำหรับคงไว้',
            'facebook_created_time' => now()->subHours(10),
            'last_synced_at' => now(),
        ]);

        $token = $this->issueTokenForRole(User::ROLE_MANAGER);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/facebook-imports/bulk-delete', [
                'ids' => [$first->id, $second->id],
            ])
            ->assertOk()
            ->assertJsonPath('deleted_count', 2);

        $this->assertDatabaseMissing('facebook_imported_posts', ['id' => $first->id]);
        $this->assertDatabaseMissing('facebook_imported_posts', ['id' => $second->id]);
        $this->assertDatabaseHas('facebook_imported_posts', ['id' => $third->id]);
    }

    private function issueTokenForRole(string $role): string
    {
        $username = 'api-' . $role . '-' . uniqid();
        $password = 'secret-pass';

        User::factory()->create([
            'username' => $username,
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make($password),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => $username,
            'password' => $password,
            'device_name' => 'FeatureTest',
        ]);

        $response->assertOk();

        return (string) $response->json('token');
    }
}
