<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminArticleGenerateAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_ai_route_requires_admin_auth(): void
    {
        $response = $this->post(route('admin.articles.generate-ai'), [
            'topic' => 'ทดสอบ',
        ]);

        $response->assertRedirect(route('admin.login'));
    }

    public function test_generate_ai_validation_fails_without_topic(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->withSession([
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
        ])->post(route('admin.articles.generate-ai'), []);

        $response->assertStatus(302); // Redirect back due to validation fail
    }

    public function test_generate_ai_fails_without_api_key(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        // Temporarily clear env value
        \Illuminate\Support\Env::getRepository()->set('INFERSTACK_API_KEY', '');
        unset($_ENV['INFERSTACK_API_KEY']);
        putenv('INFERSTACK_API_KEY=');

        $response = $this->withSession([
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
        ])->post(route('admin.articles.generate-ai'), [
            'topic' => 'ทดสอบ',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'error' => 'กรุณาตั้งค่า INFERSTACK_API_KEY ในไฟล์ .env ก่อนใช้งานครับ',
        ]);
    }

    public function test_generate_ai_returns_parsed_json_on_success(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        // Mock InferStack response
        Http::fake([
            'api.inferstack.net/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'title' => 'หัวข้อบทความทดสอบ AI',
                                'excerpt' => 'คำโปรยบทความทดสอบ AI',
                                'content' => '<p>เนื้อหาบทความทดสอบ AI</p>',
                                'meta_description' => 'คำอธิบายทดสอบ AI',
                                'keywords' => 'ทดสอบ, เอไอ',
                                'lsi_keywords' => 'ทดสอบย่อย, ลิงก์',
                                'landscape_prompt' => 'landscape prompt text',
                                'square_prompt' => 'square prompt text',
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Set env value
        \Illuminate\Support\Env::getRepository()->set('INFERSTACK_API_KEY', 'test_key');
        $_ENV['INFERSTACK_API_KEY'] = 'test_key';
        putenv('INFERSTACK_API_KEY=test_key');

        $response = $this->withSession([
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
        ])->post(route('admin.articles.generate-ai'), [
            'topic' => 'เขียนเรื่องดวงชะตา',
            'keywords' => 'ดวงชะตา, เสริมโชค',
            'model' => 'claude-3-5-sonnet',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'title' => 'หัวข้อบทความทดสอบ AI',
                'excerpt' => 'คำโปรยบทความทดสอบ AI',
                'content' => '<p>เนื้อหาบทความทดสอบ AI</p>',
                'meta_description' => 'คำอธิบายทดสอบ AI',
                'keywords' => 'ทดสอบ, เอไอ',
                'lsi_keywords' => 'ทดสอบย่อย, ลิงก์',
                'landscape_prompt' => 'landscape prompt text',
                'square_prompt' => 'square prompt text',
            ],
        ]);
    }
}
