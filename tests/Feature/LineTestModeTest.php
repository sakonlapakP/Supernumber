<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LineNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LineTestModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.line.channel_access_token', 'fake-token');
        config()->set('services.line.group_id', 'group-id-123');
    }

    /**
     * ทดสอบกรณีปิด Test Mode (ต้องส่งเข้ากลุ่มปกติ)
     */
    public function test_it_sends_to_group_when_test_mode_is_disabled(): void
    {
        Http::fake();
        config()->set('services.line.test_mode', false);

        $notifier = new LineNotifier();
        $notifier->queueText('test_event', 'Hello Group');

        Http::assertSent(function ($request) {
            return $request['to'] === 'group-id-123';
        });
    }

    /**
     * ทดสอบกรณีเปิด Test Mode (ต้องส่งไปที่ Admin User ID แทน)
     */
    public function test_it_redirects_to_admin_when_test_mode_is_enabled(): void
    {
        Http::fake();
        config()->set('services.line.test_mode', true);
        config()->set('services.line.admin_user_id', 'U-admin-999');

        $notifier = new LineNotifier();
        $notifier->queueText('test_event', 'Hello Admin');

        Http::assertSent(function ($request) {
            // ต้องส่งไปหา Admin ID แทน Group ID
            return $request['to'] === 'U-admin-999' && $request['to'] !== 'group-id-123';
        });
    }

    /**
     * ทดสอบกรณีเปิด Test Mode แต่ไม่ได้ระบุ Admin User ID (ต้องส่งเข้ากลุ่มปกติเป็น fallback)
     */
    public function test_it_falls_back_to_group_if_test_mode_is_on_but_admin_id_is_empty(): void
    {
        Http::fake();
        config()->set('services.line.test_mode', true);
        config()->set('services.line.admin_user_id', ''); // ว่างไว้

        $notifier = new LineNotifier();
        $notifier->queueText('test_event', 'Hello Fallback');

        Http::assertSent(function ($request) {
            return $request['to'] === 'group-id-123';
        });
    }
}
