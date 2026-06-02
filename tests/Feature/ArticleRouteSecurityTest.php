<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ArticleRouteSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_clear_query_is_ignored_for_guests(): void
    {
        $article = Article::query()->create([
            'title' => 'บทความทดสอบ',
            'slug' => 'guest-force-clear-test',
            'content' => '<p>เนื้อหาทดสอบ</p>',
            'is_published' => true,
            'published_at' => Carbon::now('Asia/Bangkok')->subDay(),
        ]);

        $response = $this->get(route('articles.show', $article->slug) . '?force_clear=1');

        $response->assertOk();
        $response->assertDontSee('Cache Cleared');
        $response->assertSee('บทความทดสอบ');
    }

    public function test_comment_honeypot_silently_drops_bot_submissions(): void
    {
        $article = Article::query()->create([
            'title' => 'บทความคอมเมนต์',
            'slug' => 'comment-honeypot-test',
            'content' => '<p>เนื้อหา</p>',
            'is_published' => true,
            'published_at' => Carbon::now('Asia/Bangkok')->subDay(),
        ]);

        $this->post(route('articles.comments.store', $article->slug), [
            'commenter_name' => 'spambot',
            'content' => 'buy cheap stuff',
            'website' => 'http://spam.example.com',
        ])->assertRedirect(route('articles.show', $article->slug));

        $this->assertDatabaseCount('article_comments', 0);
    }

    public function test_legitimate_comment_is_stored_as_pending(): void
    {
        $article = Article::query()->create([
            'title' => 'บทความคอมเมนต์',
            'slug' => 'comment-valid-test',
            'content' => '<p>เนื้อหา</p>',
            'is_published' => true,
            'published_at' => Carbon::now('Asia/Bangkok')->subDay(),
        ]);

        $this->post(route('articles.comments.store', $article->slug), [
            'commenter_name' => 'คุณผู้อ่าน',
            'content' => 'บทความดีมากครับ',
        ])->assertRedirect(route('articles.show', $article->slug));

        $this->assertDatabaseHas('article_comments', [
            'article_id' => $article->id,
            'commenter_name' => 'คุณผู้อ่าน',
            'status' => \App\Models\ArticleComment::STATUS_PENDING,
        ]);
    }

    public function test_upload_temp_image_requires_admin(): void
    {
        $this->post(route('admin.articles.upload-temp-image'), [
            'image' => 'data:image/png;base64,aGVsbG8=',
        ])->assertRedirect(route('admin.login'));
    }

    public function test_get_svg_proxy_requires_admin(): void
    {
        $this->get(route('admin.articles.get-svg-proxy', ['path' => 'anything.svg']))
            ->assertRedirect(route('admin.login'));
    }
}
