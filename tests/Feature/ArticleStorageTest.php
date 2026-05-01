<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Carbon::setTestNow(Carbon::parse('2026-05-02 12:00:00', 'Asia/Bangkok'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Test Case 1.1: อัปโหลดรูปเข้า tmp และบันทึกบทความเพื่อให้รูปย้ายไปที่ถาวร
     */
    public function test_it_moves_uploaded_images_from_tmp_to_permanent_directory_on_save(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        // 1. จำลองการอัปโหลดรูปเข้า /p/img (Step 1)
        $file = UploadedFile::fake()->image('cover.jpg');
        $uploadResponse = $this
            ->withSession($this->adminSession($admin))
            ->post(route('img.store'), ['img' => $file]);

        $uploadResponse->assertOk();
        $tmpPath = $uploadResponse->json('path');
        $this->assertStringContainsString('articles/tmp/', $tmpPath);
        Storage::disk('public')->assertExists($tmpPath);

        // 2. จำลองการบันทึกบทความ (Step 2)
        $articleData = [
            'title' => 'Test Article Storage',
            'slug' => 'test-article-storage',
            'excerpt' => 'Short summary',
            'content' => '<p>Content with image <img src="/storage/' . $tmpPath . '"></p>',
            'meta_description' => 'Meta description',
            'is_published' => '1',
            'land_path' => $tmpPath,
        ];

        $saveResponse = $this
            ->withSession($this->adminSession($admin))
            ->post(route('admin.articles.store'), $articleData);

        $saveResponse->assertRedirect(route('admin.articles'));

        // 3. ตรวจสอบว่าไฟล์ถูกย้ายไปที่ถาวรแล้ว
        $article = Article::where('slug', 'test-article-storage')->firstOrFail();
        $permPath = "articles/2026/test-article-storage/" . basename($tmpPath);

        $this->assertEquals($permPath, $article->cover_image_landscape_path);
        Storage::disk('public')->assertExists($permPath);
        
        $this->assertStringContainsString($permPath, $article->content);
        $this->assertStringNotContainsString('articles/tmp/', $article->content);

        // ตรวจสอบว่าไฟล์ใน tmp จะยังอยู่ (เพราะเราเปลี่ยนเป็น copy เพื่อความปลอดภัย และรอให้ cleanup มาลบ)
        Storage::disk('public')->assertExists($tmpPath);
    }

    /**
     * Test Case 1.2: การแก้ไขบทความและเปลี่ยนรูปใหม่ รูปเก่าต้องถูกลบ
     */
    public function test_it_replaces_old_image_and_deletes_physical_file_on_update(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

        // สร้างบทความเริ่มต้น
        $article = Article::create([
            'title' => 'Update Test',
            'slug' => 'update-test',
            'content' => 'Content',
            'cover_image_landscape_path' => 'articles/2026/update-test/old-image.jpg',
        ]);
        Storage::disk('public')->put('articles/2026/update-test/old-image.jpg', 'fake-old-content');

        // 1. อัปโหลดรูปใหม่เข้า tmp
        $newFile = UploadedFile::fake()->image('new-cover.jpg');
        $tmpPath = $this->withSession($this->adminSession($admin))
            ->post(route('img.store'), ['img' => $newFile])
            ->json('path');

        // 2. อัปเดตบทความ
        $this->withSession($this->adminSession($admin))
            ->post(route('admin.articles.update', $article), [
                'title' => 'Update Test',
                'slug' => 'update-test',
                'content' => 'Updated content',
                'is_published' => '1',
                'land_path' => $tmpPath,
            ]);

        $article->refresh();
        $newPermPath = "articles/2026/update-test/" . basename($tmpPath);

        $this->assertEquals($newPermPath, $article->cover_image_landscape_path);
        Storage::disk('public')->assertExists($newPermPath);
        
        // ไฟล์เก่าต้องถูกลบออกไปจาก Disk แล้ว
        Storage::disk('public')->assertMissing('articles/2026/update-test/old-image.jpg');
    }



    private function adminSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_role' => $user->role,
        ];
    }
}
