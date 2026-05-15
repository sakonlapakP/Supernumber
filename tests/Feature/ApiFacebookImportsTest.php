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
