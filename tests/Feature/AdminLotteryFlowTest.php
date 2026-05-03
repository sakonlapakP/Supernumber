<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\LotteryResult;
use App\Models\User;
use App\Services\LineLotteryNotifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminLotteryFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ทดสอบว่าการแจ้งเตือนแอดมิน (Admin Ready) ถูกส่งไปยัง LINE ID ส่วนตัว
     */
    public function test_admin_ready_notification_is_sent_to_private_line_id(): void
    {
        // 1. ตั้งค่า LINE Admin ID ใน Config
        config([
            'services.line.channel_access_token' => 'test-token',
            'services.line.admin_user_id' => 'U_ADMIN_PRIVATE_ID',
            'services.line.groups.admin' => 'U_ADMIN_PRIVATE_ID', // คีย์ใหม่ที่เราเพิ่ม
        ]);

        $article = Article::create([
            'title' => 'ตรวจหวย 1 พ.ค. 2569',
            'slug' => 'thai-government-lottery-202605first',
            'content' => 'Test Content',
            'is_published' => true,
        ]);

        // 2. Mock HTTP สำหรับ LINE Push Message
        Http::fake([
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        // 3. เรียกใช้งานการแจ้งเตือน
        app(LineLotteryNotifier::class)->notifyAdminArticleReady($article, Carbon::create(2026, 5, 1));

        // 4. ตรวจสอบว่าระบบส่งไปยัง ID ส่วนตัว (U...) ไม่ใช่ส่งเข้ากลุ่ม
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.line.me/v2/bot/message/push' &&
                   $request['to'] === 'U_ADMIN_PRIVATE_ID' &&
                   str_contains($request['messages'][0]['text'], 'Admin กรุณาเข้าสู่ระบบ');
        });
    }

    /**
     * ทดสอบหน้า Admin Articles ว่าแสดงสถานะ "รอผลครบ 100%" สำหรับหวยที่ยังไม่เสร็จ
     */
    public function test_admin_articles_page_shows_incomplete_status_badge(): void
    {
        // 1. สร้าง Admin
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);

        // 2. สร้างบทความหวยที่ยังไม่ครบ 100%
        $article = Article::create([
            'title' => 'หวยงวดใหม่',
            'slug' => 'thai-government-lottery-202605first',
            'content' => 'Content',
            'is_published' => true,
        ]);

        // สร้างผลหวยที่มีรางวัลไม่ถึง 6 รางวัล (ทำให้ is_complete เป็น false)
        LotteryResult::create([
            'draw_date' => '2026-05-01',
            'is_complete' => false,
        ]);

        // 3. เข้าหน้า Admin Articles
        $response = $this
            ->withSession([
                'admin_authenticated' => true,
                'admin_user_id' => $admin->id,
                'admin_user_role' => $admin->role,
            ])
            ->get(route('admin.articles'));

        // 4. ตรวจสอบว่าเห็น Badge "รอผลครบ 100%" และปุ่มถูกล็อกในระดับ JS
        $response->assertStatus(200);
        $response->assertSee('รอผลครบ 100%');
        $response->assertSee('isComplete === 0'); // ตรวจสอบว่ามี Logic JS นี้อยู่ในหน้า
    }
}
