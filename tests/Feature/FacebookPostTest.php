<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Services\FacebookPagePoster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookPostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // เซ็ตค่า Config จำลองสำหรับ Facebook
        config()->set('services.facebook.page_id', '12345');
        config()->set('services.facebook.page_access_token', 'fake-token');
        config()->set('services.lottery.fb_template', "{title}\n\n{excerpt}\n\n{article_url}");
    }

    /**
     * Case 2.1: ทดสอบการแชร์สำเร็จ
     */
    public function test_it_successfully_posts_to_facebook_with_image(): void
    {
        Http::fake([
            'https://graph.facebook.com' => Http::response(['success' => true], 200),
            'https://graph.facebook.com/*/feed' => Http::response(['id' => 'fb_post_123'], 200),
        ]);

        $article = Article::create([
            'title' => 'FB Success Test',
            'slug' => 'fb-success-test',
            'excerpt' => 'Short excerpt',
            'content' => 'Content',
        ]);

        $poster = new FacebookPagePoster();
        $result = $poster->postArticle($article);

        $this->assertTrue($result['success']);
        $this->assertEquals('fb_post_123', $result['id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com'
                && str_contains($request->body(), 'scrape=true')
                && str_contains($request->body(), urlencode('https://localhost/articles/fb-success-test'));
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '12345/feed')
                && ! $request->isMultipart()
                && str_contains($request->body(), 'FB Success Test')
                && str_contains($request->body(), 'link=')
                && str_contains($request->body(), urlencode('https://localhost/articles/fb-success-test'));
        });
    }

    public function test_it_posts_without_requiring_a_local_image_file(): void
    {
        Http::fake([
            'https://graph.facebook.com' => Http::response(['success' => true], 200),
            'https://graph.facebook.com/*/feed' => Http::response(['id' => 'fb_post_no_image'], 200),
        ]);

        $article = Article::create([
            'title' => 'FB No Image Test',
            'slug' => 'fb-no-image-test',
            'content' => 'Some content',
        ]);

        $poster = new FacebookPagePoster();
        $result = $poster->postArticle($article);

        $this->assertTrue($result['success']);
        $this->assertEquals('fb_post_no_image', $result['id']);
    }

    public function test_admin_share_social_route_posts_only_to_facebook_page(): void
    {
        Http::fake([
            'https://graph.facebook.com' => Http::response(['success' => true], 200),
            'https://graph.facebook.com/*/feed' => Http::response(['id' => 'fb_post_456'], 200),
        ]);

        $manager = User::factory()->create([
            'username' => 'manager-facebook-only-share',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $article = Article::create([
            'title' => 'FB Only Route Test',
            'slug' => 'fb-only-route-test',
            'excerpt' => 'Short excerpt',
            'content' => 'Content',
            'is_published' => true,
            'is_line_broadcasted' => false,
        ]);

        $response = $this
            ->withSession($this->managerSession($manager))
            ->post(route('admin.articles.share-social', $article), [
                'manual_image_url' => 'articles/2026/fb-only-route-test.png',
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status_message', 'แชร์ไปที่ Facebook Page สำเร็จ ✅');

        $article->refresh();

        $this->assertFalse($article->is_line_broadcasted);

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com'
                && str_contains($request->body(), 'scrape=true')
                && str_contains($request->body(), urlencode('https://localhost/articles/fb-only-route-test'));
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '12345/feed')
                && ! $request->isMultipart()
                && str_contains($request->body(), 'FB Only Route Test')
                && str_contains($request->body(), 'link=')
                && str_contains($request->body(), urlencode('https://localhost/articles/fb-only-route-test'));
        });
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
