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
            ]);

        $createResponse->assertRedirect(route('admin.articles'));

        $article = Article::query()
            ->where('slug', 'media-article')
            ->firstOrFail();

        $this->assertStringNotContainsString('<script>', $article->content);
        $this->assertStringContainsString('<strong>world</strong>', $article->content);
        $this->assertNotNull($article->cover_image_landscape_path);
        $this->assertNotNull($article->cover_image_square_path);

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
            ]);

        $updateResponse->assertRedirect(route('admin.articles'));

        $article->refresh();

        $this->assertSame('Media Article Updated', $article->title);
        $this->assertStringNotContainsString('<script>', $article->content);
        $this->assertStringContainsString('<em>body</em>', $article->content);
        $this->assertNotNull($article->cover_image_landscape_path);
        $this->assertNotNull($article->cover_image_square_path);
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
