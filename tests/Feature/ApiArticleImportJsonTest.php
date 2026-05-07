<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_imported_article_defaults_to_draft_when_publish_flag_is_missing(): void
    {
        User::factory()->create([
            'username' => 'draft-importer',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
            'password' => Hash::make('secret-pass'),
        ]);

        $token = $this->postJson('/api/login', [
            'login' => 'draft-importer',
            'password' => 'secret-pass',
            'device_name' => 'FeatureTest',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/articles/import-json', [
                'json_data' => json_encode([
                    'title' => 'Draft by Default',
                    'content' => '<p>Imported body</p>',
                ], JSON_UNESCAPED_UNICODE),
            ])
            ->assertCreated()
            ->assertJsonPath('imported', 1);

        $article = Article::firstOrFail();

        $this->assertFalse((bool) $article->is_published);
        $this->assertNull($article->published_at);
    }

    public function test_preview_url_uses_signed_preview_for_drafts_and_public_url_for_published_articles(): void
    {
        $user = User::factory()->create([
            'username' => 'preview-manager',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
            'password' => Hash::make('secret-pass'),
        ]);

        $token = $this->postJson('/api/login', [
            'login' => 'preview-manager',
            'password' => 'secret-pass',
            'device_name' => 'FeatureTest',
        ])->json('token');

        $draft = Article::create([
            'title' => 'Draft Preview Test',
            'slug' => 'draft-preview-test',
            'content' => '<p>Draft body</p>',
            'author_user_id' => $user->id,
            'is_published' => false,
            'published_at' => null,
        ]);

        $published = Article::create([
            'title' => 'Published Preview Test',
            'content' => '<p>Published body</p>',
            'author_user_id' => $user->id,
            'slug' => 'published-preview-test',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $draftUrl = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/articles/{$draft->id}/preview-url")
            ->assertOk()
            ->json('url');

        $this->assertStringContainsString("/articles/preview/{$draft->id}", $draftUrl);
        $this->assertStringContainsString('signature=', $draftUrl);

        $this->get($draftUrl)->assertOk();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/articles/{$published->id}/preview-url")
            ->assertOk()
            ->assertJsonPath('url', route('articles.show', $published->slug));
    }

    public function test_share_api_requires_published_article_and_can_post_to_facebook_page(): void
    {
        config([
            'services.facebook.page_id' => 'page-123',
            'services.facebook.page_access_token' => 'token-123',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'fb-photo-1'], 200),
        ]);

        $user = User::factory()->create([
            'username' => 'share-manager',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
            'password' => Hash::make('secret-pass'),
        ]);

        $token = $this->postJson('/api/login', [
            'login' => 'share-manager',
            'password' => 'secret-pass',
            'device_name' => 'FeatureTest',
        ])->json('token');

        $draft = Article::create([
            'title' => 'Draft Share Test',
            'slug' => 'draft-share-test',
            'content' => '<p>Draft body</p>',
            'author_user_id' => $user->id,
            'is_published' => false,
        ]);

        $published = Article::create([
            'title' => 'Published Share Test',
            'slug' => 'published-share-test',
            'content' => '<p>Published body</p>',
            'cover_image_landscape_path' => 'articles/share-test.jpg',
            'author_user_id' => $user->id,
            'is_published' => true,
            'published_at' => now(),
        ]);

        \Illuminate\Support\Facades\Storage::disk('public')->put(
            'articles/share-test.jpg',
            'fake image contents'
        );

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/articles/{$draft->id}/share", ['platform' => 'facebook'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'กรุณาเผยแพร่บทความก่อนแชร์');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/articles/{$published->id}/share", ['platform' => 'facebook'])
            ->assertOk()
            ->assertJsonPath('message', 'แชร์ไปที่ Facebook Page สำเร็จ');
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
