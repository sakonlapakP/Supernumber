<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Services\FacebookPagePoster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacebookPostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
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
            'https://graph.facebook.com/*' => Http::response(['id' => 'fb_post_123'], 200),
        ]);

        $article = Article::create([
            'title' => 'FB Success Test',
            'slug' => 'fb-success-test',
            'excerpt' => 'Short excerpt',
            'content' => 'Content',
            'cover_image_landscape_path' => 'articles/2026/test.jpg',
        ]);
        Storage::disk('public')->put('articles/2026/test.jpg', 'fake image content');

        $poster = new FacebookPagePoster();
        $result = $poster->postArticle($article);

        $this->assertTrue($result['success']);
        $this->assertEquals('fb_post_123', $result['id']);

        Http::assertSent(function ($request) {
            // สำหรับ Multipart เราจะเช็คผ่าน body หรือตรวจสอบว่ามีการส่งไฟล์ไปจริง
            return str_contains($request->url(), '12345/photos') &&
                   $request->isMultipart() &&
                   str_contains($request->body(), 'FB Success Test') &&
                   str_contains($request->body(), 'fake-token');
        });
    }

    /**
     * Case 2.2: ทดสอบการระงับการโพสต์ (Abort) หากไม่พบไฟล์รูปภาพ
     */
    public function test_it_aborts_posting_to_facebook_if_image_is_missing(): void
    {
        Http::fake();

        $article = Article::create([
            'title' => 'FB Missing Image Test',
            'slug' => 'fb-missing-image-test',
            'content' => 'Some content',
            'cover_image_landscape_path' => 'articles/2026/non-existent.jpg',
        ]);

        $poster = new FacebookPagePoster();
        $result = $poster->postArticle($article);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ไม่พบไฟล์รูปภาพ', $result['error']);

        Http::assertNothingSent();
    }

    /**
     * ทดสอบการระงับการโพสต์หากไฟล์เป็น SVG
     */
    public function test_it_aborts_posting_to_facebook_if_image_is_svg(): void
    {
        Http::fake();

        $article = Article::create([
            'title' => 'FB SVG Test',
            'slug' => 'fb-svg-test',
            'content' => 'Some content',
            'cover_image_landscape_path' => 'articles/2026/test.svg',
        ]);
        Storage::disk('public')->put('articles/2026/test.svg', '<svg>...</svg>');

        $poster = new FacebookPagePoster();
        $result = $poster->postArticle($article);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ไม่พบไฟล์รูปภาพ', $result['error']);
        
        Http::assertNothingSent();
    }
}
