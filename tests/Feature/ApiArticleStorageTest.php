<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiArticleStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_stores_scheduled_publish_time_as_bangkok_time(): void
    {
        User::factory()->create([
            'username' => 'article-api-manager',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
            'password' => Hash::make('secret-pass'),
        ]);

        $token = $this->postJson('/api/login', [
            'login' => 'article-api-manager',
            'password' => 'secret-pass',
            'device_name' => 'FeatureTest',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/articles', [
                'title' => 'Scheduled Article',
                'content' => '<p>Scheduled body</p>',
                'is_published' => true,
                'published_at' => '2026-05-20T09:09:00.000',
            ])
            ->assertCreated();

        $article = Article::firstOrFail();

        $this->assertTrue($article->is_published);
        $this->assertSame(
            '2026-05-20 09:09',
            $article->published_at?->timezone('Asia/Bangkok')->format('Y-m-d H:i')
        );
    }
}
