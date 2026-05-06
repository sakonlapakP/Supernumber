<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiArticleImportJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_single_article_from_json(): void
    {
        $user = User::factory()->create([
            'username' => 'json-manager',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
            'password' => Hash::make('secret-pass'),
        ]);

        $token = $this->postJson('/api/login', [
            'login' => 'json-manager',
            'password' => 'secret-pass',
            'device_name' => 'FeatureTest',
        ])->json('token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/articles/import-json', [
                'json_data' => json_encode([
                    'title' => 'JSON Import Test',
                    'content' => '<p>Imported body</p>',
                    'keywords' => ['alpha', 'beta'],
                    'image_guidelines' => [
                        'landscape_prompt' => 'Landscape prompt',
                        'square_prompt' => 'Square prompt',
                    ],
                    'is_published' => true,
                ], JSON_UNESCAPED_UNICODE),
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('imported', 1)
            ->assertJsonPath('skipped', 0);

        $article = Article::firstOrFail();

        $this->assertSame('JSON Import Test', $article->title);
        $this->assertSame('alpha, beta', $article->keywords);
        $this->assertSame($user->id, $article->author_user_id);
        $this->assertSame('Landscape prompt', $article->image_guidelines['landscape_prompt']);
    }

    public function test_invalid_json_returns_validation_error(): void
    {
        User::factory()->create([
            'username' => 'json-admin',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'password' => Hash::make('secret-pass'),
        ]);

        $token = $this->postJson('/api/login', [
            'login' => 'json-admin',
            'password' => 'secret-pass',
            'device_name' => 'FeatureTest',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/articles/import-json', [
                'json_data' => '{"title":',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('json_data');
    }
}
