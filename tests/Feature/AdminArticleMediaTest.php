<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminArticleMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_create_page_includes_both_article_image_inputs_and_store_action(): void
    {
        $manager = User::factory()->create([
            'username' => 'manager-article-create-form',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->managerSession($manager))
            ->get(route('admin.articles.create'));

        $response->assertOk();
        $response->assertSee(route('admin.articles.store'), false);
        $response->assertSee('id="upload_media_land"', false);
        $response->assertSee('id="upload_media_sq"', false);
        $response->assertSee('name="land_path"', false);
        $response->assertSee('name="sq_path"', false);
    }

    public function test_edit_page_shows_landscape_preview_and_both_image_inputs(): void
    {
        $manager = User::factory()->create([
            'username' => 'manager-article-edit-form',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $article = Article::query()->create([
            'title' => 'Existing Article',
            'slug' => 'existing-article',
            'excerpt' => 'Excerpt',
            'content' => '<p>Existing content</p>',
            'is_published' => false,
            'cover_image_path' => null,
            'cover_image_landscape_path' => 'articles/2026/existing-land.jpg',
            'cover_image_square_path' => null,
            'image_guidelines' => [
                'landscape_prompt' => 'Existing landscape prompt',
                'square_prompt' => 'Existing square prompt',
            ],
        ]);

        $response = $this
            ->withSession($this->managerSession($manager))
            ->get(route('admin.articles.edit', $article));

        $response->assertOk();
        $response->assertSee(route('admin.articles.update', $article), false);
        $response->assertSee('id="upload_media_land"', false);
        $response->assertSee('id="upload_media_sq"', false);
        $response->assertSee('name="land_path"', false);
        $response->assertSee('name="sq_path"', false);
        $response->assertSee('existing-land.jpg', false);
        $response->assertSee('name="image_guidelines[landscape_prompt]"', false);
        $response->assertSee('name="image_guidelines[square_prompt]"', false);
        $response->assertSee('Existing landscape prompt', false);
        $response->assertSee('Existing square prompt', false);
        $response->assertSee(route('admin.articles.delete', $article), false);
        $response->assertSee('ลบบทความ', false);
    }

    public function test_manager_can_see_and_delete_article(): void
    {
        Storage::fake('public');

        $manager = User::factory()->create([
            'username' => 'manager-article-delete',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        Storage::disk('public')->put('articles/2026/delete-me/cover.jpg', 'fake-cover');
        Storage::disk('public')->put('articles/2026/delete-me/land.jpg', 'fake-land');
        Storage::disk('public')->put('articles/2026/delete-me/square.jpg', 'fake-square');

        $article = Article::query()->create([
            'title' => 'Delete Me',
            'slug' => 'delete-me',
            'excerpt' => 'Excerpt',
            'content' => '<p>Delete content</p>',
            'is_published' => false,
            'cover_image_path' => 'articles/2026/delete-me/cover.jpg',
            'cover_image_landscape_path' => 'articles/2026/delete-me/land.jpg',
            'cover_image_square_path' => 'articles/2026/delete-me/square.jpg',
        ]);

        $indexResponse = $this
            ->withSession($this->managerSession($manager))
            ->get(route('admin.articles'));

        $indexResponse->assertOk();
        $indexResponse->assertSee(route('admin.articles.delete', $article), false);
        $indexResponse->assertSee('ลบ', false);

        $deleteResponse = $this
            ->withSession($this->managerSession($manager))
            ->delete(route('admin.articles.delete', $article));

        $deleteResponse->assertRedirect(route('admin.articles'));

        $this->assertDatabaseMissing('articles', [
            'id' => $article->id,
        ]);
        Storage::disk('public')->assertMissing('articles/2026/delete-me/cover.jpg');
        Storage::disk('public')->assertMissing('articles/2026/delete-me/land.jpg');
        Storage::disk('public')->assertMissing('articles/2026/delete-me/square.jpg');
    }

    public function test_manager_json_import_persists_lsi_keywords_and_auto_post_flag(): void
    {
        $manager = User::factory()->create([
            'username' => 'manager-article-json-import',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $payload = [[
            'title' => 'Imported Article',
            'slug' => 'imported-article',
            'excerpt' => 'Imported excerpt',
            'content' => '<p>Imported content</p>',
            'meta_description' => 'Imported meta',
            'keywords' => ['keyword one', 'keyword two'],
            'lsi_keywords' => ['lsi one', 'lsi two'],
            'is_published' => false,
            'published_at' => '2024-05-22 09:00:00',
            'is_auto_post' => true,
            'image_guidelines' => [
                'landscape_prompt' => 'Cinematic wide article cover prompt',
                'square_prompt' => 'Square article cover prompt',
            ],
        ]];

        $response = $this
            ->withSession($this->managerSession($manager))
            ->post(route('admin.articles.import-json'), [
                'json_data' => json_encode($payload),
            ]);

        $response->assertRedirect(route('admin.articles'));

        $article = Article::query()
            ->where('slug', 'imported-article')
            ->firstOrFail();

        $this->assertSame('keyword one, keyword two', $article->keywords);
        $this->assertSame('lsi one, lsi two', $article->lsi_keywords);
        $this->assertNull($article->cover_image_path);
        $this->assertNull($article->cover_image_landscape_path);
        $this->assertNull($article->cover_image_square_path);
        $this->assertSame([
            'landscape_prompt' => 'Cinematic wide article cover prompt',
            'square_prompt' => 'Square article cover prompt',
        ], $article->image_guidelines);
        $this->assertTrue((bool) $article->is_auto_post);
        $this->assertFalse((bool) $article->is_published);
    }

    public function test_manager_can_create_update_media_and_public_content_is_sanitized(): void
    {
        Storage::fake('public');
        Carbon::setTestNow(Carbon::parse('2026-04-23 12:00:00', 'Asia/Bangkok'));

        $manager = User::factory()->create([
            'username' => 'manager-article-media',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        Storage::disk('public')->put('articles/tmp/land.jpg', 'fake-landscape-image');
        Storage::disk('public')->put('articles/tmp/square.jpg', 'fake-square-image');

        $createResponse = $this
            ->withSession($this->managerSession($manager))
            ->post(route('admin.articles.store'), [
                'title' => 'Media Article',
                'slug' => 'media-article',
                'excerpt' => 'Excerpt',
                'content' => '<p>Hello <strong>world</strong></p><script>alert(1)</script>',
                'meta_description' => 'Meta',
                'keywords' => 'kw',
                'lsi_keywords' => 'lsi',
                'is_published' => '1',
                'land_path' => 'articles/tmp/land.jpg',
                'sq_path' => 'articles/tmp/square.jpg',
                'image_guidelines' => [
                    'landscape_prompt' => 'Create landscape prompt',
                    'square_prompt' => 'Create square prompt',
                ],
            ]);

        $createResponse->assertRedirect(route('admin.articles'));

        $article = Article::query()
            ->where('slug', 'media-article')
            ->firstOrFail();

        $this->assertStringNotContainsString('<script>', $article->content);
        $this->assertStringContainsString('<strong>world</strong>', $article->content);
        $this->assertNotNull($article->cover_image_landscape_path);
        $this->assertNotNull($article->cover_image_square_path);
        $this->assertSame([
            'landscape_prompt' => 'Create landscape prompt',
            'square_prompt' => 'Create square prompt',
        ], $article->image_guidelines);

        Storage::disk('public')->assertExists($article->cover_image_landscape_path);
        Storage::disk('public')->assertExists($article->cover_image_square_path);
        $this->assertStringEndsWith('/land.jpg', (string) $article->cover_image_landscape_path);
        $this->assertStringEndsWith('/square.jpg', (string) $article->cover_image_square_path);

        $showResponse = $this->get(route('articles.show', $article->slug));

        $showResponse->assertOk();
        $showResponse->assertSee('Hello', false);
        $showResponse->assertDontSee('alert(1)');

        Storage::disk('public')->put('articles/tmp/land-updated.jpg', 'fake-updated-landscape-image');
        Storage::disk('public')->put('articles/tmp/square-updated.jpg', 'fake-updated-square-image');

        $updateResponse = $this
            ->withSession($this->managerSession($manager))
            ->post(route('admin.articles.update', $article), [
                'title' => 'Media Article Updated',
                'slug' => 'media-article',
                'excerpt' => 'Updated excerpt',
                'content' => '<p>Updated <em>body</em></p><script>alert(2)</script>',
                'meta_description' => 'Updated meta',
                'keywords' => 'updated,kw',
                'lsi_keywords' => 'updated,lsi',
                'is_published' => '1',
                'land_path' => 'articles/tmp/land-updated.jpg',
                'sq_path' => 'articles/tmp/square-updated.jpg',
                'image_guidelines' => [
                    'landscape_prompt' => 'Updated landscape prompt',
                    'square_prompt' => 'Updated square prompt',
                ],
            ]);

        $updateResponse->assertRedirect(route('admin.articles'));

        $article->refresh();

        $this->assertSame('Media Article Updated', $article->title);
        $this->assertStringNotContainsString('<script>', $article->content);
        $this->assertStringContainsString('<em>body</em>', $article->content);
        $this->assertNotNull($article->cover_image_landscape_path);
        $this->assertNotNull($article->cover_image_square_path);
        $this->assertSame([
            'landscape_prompt' => 'Updated landscape prompt',
            'square_prompt' => 'Updated square prompt',
        ], $article->image_guidelines);
        Storage::disk('public')->assertExists($article->cover_image_landscape_path);
        Storage::disk('public')->assertExists($article->cover_image_square_path);
        $this->assertStringEndsWith('/land-updated.jpg', (string) $article->cover_image_landscape_path);
        $this->assertStringEndsWith('/square-updated.jpg', (string) $article->cover_image_square_path);

        $updatedShowResponse = $this->get(route('articles.show', $article->slug));
        $updatedShowResponse->assertOk();
        $updatedShowResponse->assertSee('Updated', false);
        $updatedShowResponse->assertDontSee('alert(2)');

        Carbon::setTestNow();
    }

    public function test_manager_can_save_browser_rendered_square_png_as_article_cover(): void
    {
        Storage::fake('public');

        $manager = User::factory()->create([
            'username' => 'manager-rendered-lottery-image',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $article = Article::query()->create([
            'title' => 'Lottery Render Article',
            'slug' => 'thai-government-lottery-202605first',
            'excerpt' => 'Excerpt',
            'content' => '<p>Lottery content</p>',
            'is_published' => true,
            'cover_image_path' => 'articles/2026/thai-government-lottery-202605first/thai-government-lottery-202605first_20260501.svg',
            'cover_image_square_path' => 'articles/2026/thai-government-lottery-202605first/thai-government-lottery-202605first_20260501.svg',
            'cover_image_landscape_path' => 'articles/2026/thai-government-lottery-202605first/thai-government-lottery-202605first_cover_20260501.svg',
        ]);

        Storage::disk('public')->put((string) $article->cover_image_square_path, '<svg></svg>');

        $pngDataUri = 'data:image/png;base64,' . base64_encode('fake-rendered-png');

        $response = $this
            ->withSession($this->managerSession($manager))
            ->postJson(route('admin.articles.upload-rendered-image', $article), [
                'type' => 'square',
                'image' => $pngDataUri,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'path' => 'articles/2026/thai-government-lottery-202605first/thai-government-lottery-202605first_20260501.png',
            ]);

        $article->refresh();

        $this->assertSame(
            'articles/2026/thai-government-lottery-202605first/thai-government-lottery-202605first_20260501.png',
            $article->cover_image_square_path
        );
        $this->assertSame($article->cover_image_square_path, $article->cover_image_path);
        $this->assertStringEndsWith('.svg', (string) $article->cover_image_landscape_path);
        Storage::disk('public')->assertExists((string) $article->cover_image_square_path);
        $this->assertSame('fake-rendered-png', Storage::disk('public')->get((string) $article->cover_image_square_path));
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
