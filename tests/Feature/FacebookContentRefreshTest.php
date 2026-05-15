<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticlePlan;
use App\Models\FacebookImportedPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacebookContentRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_refresh_matching_imports_into_articles_and_keep_video_posts(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        ArticlePlan::query()->create([
            'publish_date' => '2026-01-01',
            'publish_time' => '09:00',
            'type' => 'หวย/สำคัญ',
            'topic' => 'วันขึ้นปีใหม่: เปิดดวงตัวเลขปี 69 + หวย',
            'is_lottery' => true,
        ]);
        ArticlePlan::query()->create([
            'publish_date' => '2027-01-01',
            'publish_time' => '09:00',
            'type' => 'หวย/สำคัญ',
            'topic' => 'วันขึ้นปีใหม่: เปิดดวงตัวเลขปี 70 + หวย',
            'is_lottery' => true,
        ]);
        ArticlePlan::query()->create([
            'publish_date' => '2026-01-23',
            'publish_time' => '09:09',
            'type' => 'Evergreen',
            'topic' => '(อุดช่องว่างปลายเดือน)',
            'is_lottery' => false,
        ]);

        $firstImport = FacebookImportedPost::query()->create([
            'facebook_post_id' => 'fb_new_year_1',
            'source_node_type' => 'page',
            'message' => 'สวัสดีปีใหม่ 2561 คอนเทนต์ต้นฉบับสำหรับรีเฟรชบทความ',
            'facebook_created_time' => '2018-01-01 09:00:00',
            'last_synced_at' => now(),
        ]);
        $latestImport = FacebookImportedPost::query()->create([
            'facebook_post_id' => 'fb_new_year_2',
            'source_node_type' => 'page',
            'message' => "สวัสดีปีใหม่ 2563 คอนเทนต์ล่าสุดสำหรับรีเฟรชบทความ\nเนื้อหาอัปเดตใหม่",
            'facebook_created_time' => '2020-01-01 09:00:00',
            'last_synced_at' => now(),
        ]);
        $videoImport = FacebookImportedPost::query()->create([
            'facebook_post_id' => 'fb_new_year_video',
            'source_node_type' => 'page',
            'message' => 'สวัสดีปีใหม่ 2564 วิดีโอ',
            'attachments_json' => [
                'data' => [
                    [
                        'media_type' => 'video',
                    ],
                ],
            ],
            'facebook_created_time' => '2021-01-01 09:00:00',
            'last_synced_at' => now(),
        ]);

        $this->withSession($this->adminSession($manager))
            ->post(route('admin.facebook-imports.refresh-articles'))
            ->assertRedirect();

        $article = Article::query()->where('slug', 'new-year-lucky-numbers')->first();

        $this->assertNotNull($article);
        $this->assertSame('ปีใหม่ 2563: เลขมงคลเริ่มต้นปีให้ปัง', $article->title);
        $this->assertSame('2018-01-01 09:00:00', $article->published_at?->format('Y-m-d H:i:s'));
        $this->assertStringContainsString('2563', (string) $article->content);

        $this->assertDatabaseMissing('facebook_imported_posts', ['id' => $firstImport->id]);
        $this->assertDatabaseMissing('facebook_imported_posts', ['id' => $latestImport->id]);
        $this->assertDatabaseHas('facebook_imported_posts', ['id' => $videoImport->id]);
    }

    public function test_manager_can_preview_refresh_without_mutating_data(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        ArticlePlan::query()->create([
            'publish_date' => '2026-11-24',
            'publish_time' => '09:00',
            'type' => 'วันสำคัญ',
            'topic' => 'วันลอยกระทง: เลขขอพรโชคลาภ',
            'is_lottery' => false,
        ]);

        $import = FacebookImportedPost::query()->create([
            'facebook_post_id' => 'fb_preview_1',
            'source_node_type' => 'page',
            'message' => 'วันลอยกระทง 2566 ตรงกับวันที่ 27 พ.ย. แฟนเก่าจงลอยไป',
            'facebook_created_time' => '2023-11-27 09:00:00',
            'last_synced_at' => now(),
        ]);

        $response = $this->withSession($this->adminSession($manager))
            ->get(route('admin.facebook-imports.refresh-preview'));

        $response->assertOk();
        $response->assertSee('วันลอยกระทง');
        $response->assertSee('ยืนยันอัปโหลด');

        $this->assertDatabaseHas('facebook_imported_posts', ['id' => $import->id]);
        $this->assertDatabaseMissing('articles', ['slug' => 'loy-krathong-wish-luck-numbers']);
    }

    public function test_manager_can_refresh_imports_using_date_fallback_when_text_is_not_explicit(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        ArticlePlan::query()->create([
            'publish_date' => '2026-04-06',
            'publish_time' => '09:00',
            'type' => 'วันสำคัญ',
            'topic' => 'วันจักรี: เลขเสริมอำนาจ',
            'is_lottery' => false,
        ]);

        $firstImport = FacebookImportedPost::query()->create([
            'facebook_post_id' => 'fb_chakri_1',
            'source_node_type' => 'page',
            'message' => 'เลขดีประจำวันนี้สำหรับคนทำงานและคนมีเป้าหมาย',
            'facebook_created_time' => '2019-04-06 08:30:00',
            'last_synced_at' => now(),
        ]);
        $latestImport = FacebookImportedPost::query()->create([
            'facebook_post_id' => 'fb_chakri_2',
            'source_node_type' => 'page',
            'message' => "เลขดีประจำวันนี้เวอร์ชันล่าสุด\nพร้อมแนวคิดเสริมพลัง",
            'facebook_created_time' => '2021-04-06 10:15:00',
            'last_synced_at' => now(),
        ]);

        $this->withSession($this->adminSession($manager))
            ->post(route('admin.facebook-imports.refresh-articles'))
            ->assertRedirect();

        $article = Article::query()->where('slug', 'chakri-day-power-numbers')->first();

        $this->assertNotNull($article);
        $this->assertSame('วันจักรี 2564: เลขเสริมอำนาจและเกียรติยศ', $article->title);
        $this->assertSame('2019-04-06 08:30:00', $article->published_at?->format('Y-m-d H:i:s'));
        $this->assertStringContainsString('เวอร์ชันล่าสุด', (string) $article->content);

        $this->assertDatabaseMissing('facebook_imported_posts', ['id' => $firstImport->id]);
        $this->assertDatabaseMissing('facebook_imported_posts', ['id' => $latestImport->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_name' => $user->name,
            'admin_user_role' => $user->role,
        ];
    }
}
