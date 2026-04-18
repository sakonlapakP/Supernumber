<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\CustomerOrder;
use App\Models\LineNotificationLog;
use App\Models\LotteryResult;
use App\Models\PhoneNumber;
use App\Models\User;
use App\Services\LineOrderNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use RuntimeException;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LineNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_and_sends_estimate_lead_notifications_to_the_configured_group(): void
    {
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.group_id', 'group-default');
        config()->set('services.line.groups.estimate', 'group-estimate');
        config()->set('services.line.retry_sleep_ms', 0);

        Http::fake([
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        $response = $this->post('/estimate', [
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'gender' => 'male',
            'birthday' => '1990-01-01',
            'work_type' => 'sales',
            'current_phone' => '081-234-5678',
            'main_phone' => '0899998888',
            'email' => 'somchai@example.com',
            'goal' => 'money',
        ]);

        $response->assertRedirect('/estimate');
        $response->assertSessionHas('estimate_status_message');

        Http::assertSent(function (HttpRequest $request): bool {
            $payload = $request->data();
            $message = (string) ($payload['messages'][0]['text'] ?? '');

            return $request->url() === 'https://api.line.me/v2/bot/message/push'
                && $request->hasHeader('Authorization', 'Bearer line-token')
                && ($payload['to'] ?? null) === 'group-estimate'
                && ($payload['messages'][0]['type'] ?? null) === 'text'
                && str_contains($message, 'มีลูกค้าใหม่กรอกแบบฟอร์มเลือกเบอร์')
                && str_contains($message, 'เบอร์หลัก: 0899998888')
                && str_contains($message, 'เป้าหมาย: เน้นการเงิน / ปิดการขาย');
        });

        Http::assertSentCount(1);

        $this->assertDatabaseHas('line_notification_logs', [
            'event_type' => 'estimate_submitted',
            'destination_key' => 'estimate',
            'destination_id' => 'group-estimate',
            'status' => LineNotificationLog::STATUS_SENT,
        ]);
    }

    public function test_it_sends_order_submissions_with_a_slip_link_and_image_message_when_the_asset_url_is_public(): void
    {
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.group_id', 'group-default');
        config()->set('services.line.groups.order_submission', 'group-order');
        config()->set('services.line.retry_sleep_ms', 0);
        config()->set('app.url', 'https://supernumber.example');

        Http::fake([
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        Storage::fake('public');

        $phoneNumber = PhoneNumber::query()->create([
            'phone_number' => '0891234567',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => 'true_dtac',
            'plan_name' => 'True Super Value',
            'sale_price' => 399,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $response = $this->post('/book', [
            'ordered_number' => '089-123-4567',
            'selected_package' => 399,
            'title_prefix' => 'คุณ',
            'first_name' => 'สุดา',
            'last_name' => 'ดีพร้อม',
            'email' => 'suda@example.com',
            'current_phone' => '081-234-5678',
            'shipping_address_line' => '123 ถนนสุขุมวิท',
            'district' => 'คลองตัน',
            'amphoe' => 'คลองเตย',
            'province' => 'กรุงเทพมหานคร',
            'zipcode' => '10110',
            'appointment_date' => '2026-03-30',
            'appointment_time_slot' => '13:00 - 15:00',
            'payment_slip' => UploadedFile::fake()->image('slip.jpg'),
        ]);

        $response->assertRedirect(route('book', [
            'number' => '089-123-4567',
            'package' => 399,
        ]));

        $this->assertDatabaseHas('customer_orders', [
            'phone_number_id' => $phoneNumber->id,
            'ordered_number' => '0891234567',
            'selected_package' => 399,
            'current_phone' => '0812345678',
            'status' => 'submitted',
        ]);

        Http::assertSent(function (HttpRequest $request): bool {
            $payload = $request->data();
            $message = (string) ($payload['messages'][0]['text'] ?? '');
            $imageMessage = $payload['messages'][1] ?? [];

            return $request->url() === 'https://api.line.me/v2/bot/message/push'
                && $request->hasHeader('Authorization', 'Bearer line-token')
                && ($payload['to'] ?? null) === 'group-order'
                && ($payload['messages'][0]['type'] ?? null) === 'text'
                && ($imageMessage['type'] ?? null) === 'image'
                && str_contains((string) ($imageMessage['originalContentUrl'] ?? ''), '/line/payment-slips/')
                && str_contains((string) ($imageMessage['originalContentUrl'] ?? ''), 'signature=')
                && str_contains($message, 'มีคำสั่งซื้อเบอร์ใหม่')
                && str_contains($message, 'เบอร์: 0891234567')
                && str_contains($message, 'ประเภท: รายเดือน')
                && str_contains($message, 'ยอดชำระ: 399 บาท / เดือน')
                && str_contains($message, 'ชื่อ: คุณ สุดา ดีพร้อม')
                && ! str_contains($message, 'หลักฐานการโอน:');
        });

        Http::assertSentCount(1);

        $this->assertDatabaseHas('line_notification_logs', [
            'event_type' => 'order_submitted',
            'destination_key' => 'order_submission',
            'destination_id' => 'group-order',
            'status' => LineNotificationLog::STATUS_SENT,
        ]);
    }

    public function test_order_notifier_omits_slip_url_from_text_when_image_message_is_attached(): void
    {
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.group_id', 'group-default');
        config()->set('services.line.groups.order_submission', 'group-order');
        config()->set('services.line.retry_sleep_ms', 0);
        config()->set('app.url', 'https://supernumber.example');

        Http::fake([
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        Storage::fake('public');
        Storage::disk('public')->put('payment-slips/slip.jpg', 'fake-image-binary');

        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'selected_package' => 399,
            'title_prefix' => 'คุณ',
            'first_name' => 'สุดา',
            'last_name' => 'ดีพร้อม',
            'current_phone' => '0812345678',
            'payment_slip_path' => 'payment-slips/slip.jpg',
            'status' => 'submitted',
        ]);

        app(LineOrderNotifier::class)->sendOrderSubmitted($order);

        Http::assertSent(function (HttpRequest $request): bool {
            $payload = $request->data();
            $message = (string) ($payload['messages'][0]['text'] ?? '');
            $imageMessage = $payload['messages'][1] ?? [];

            return ($payload['to'] ?? null) === 'group-order'
                && ($payload['messages'][0]['type'] ?? null) === 'text'
                && ($imageMessage['type'] ?? null) === 'image'
                && ! str_contains($message, 'หลักฐานการโอน:')
                && str_contains((string) ($imageMessage['originalContentUrl'] ?? ''), '/line/payment-slips/')
                && str_contains((string) ($imageMessage['originalContentUrl'] ?? ''), 'signature=');
        });
    }

    public function test_it_retries_line_delivery_before_marking_the_notification_as_sent(): void
    {
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.group_id', 'group-default');
        config()->set('services.line.groups.estimate', 'group-estimate');
        config()->set('services.line.retry_times', 3);
        config()->set('services.line.retry_sleep_ms', 0);

        Http::fakeSequence()
            ->push([], 500)
            ->push([], 200);

        $this->post('/estimate', [
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'gender' => 'male',
            'birthday' => '1990-01-01',
            'work_type' => 'sales',
            'current_phone' => '081-234-5678',
            'main_phone' => '0899998888',
            'email' => 'somchai@example.com',
            'goal' => 'money',
        ])->assertRedirect('/estimate');

        Http::assertSentCount(2);

        $this->assertDatabaseHas('line_notification_logs', [
            'event_type' => 'estimate_submitted',
            'destination_id' => 'group-estimate',
            'status' => LineNotificationLog::STATUS_SENT,
            'response_status' => 200,
        ]);
    }

    public function test_it_sends_line_notification_when_admin_changes_order_status_to_a_configured_event(): void
    {
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.group_id', 'group-default');
        config()->set('services.line.groups.order_status', 'group-status');
        config()->set('services.line.order_status_events', ['submitted', 'paid', 'completed']);
        config()->set('services.line.retry_sleep_ms', 0);

        Http::fake([
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        Storage::fake('public');

        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'selected_package' => 399,
            'title_prefix' => 'คุณ',
            'first_name' => 'สุดา',
            'last_name' => 'ดีพร้อม',
            'email' => 'suda@example.com',
            'current_phone' => '0812345678',
            'payment_slip_path' => 'payment-slips/slip.jpg',
            'status' => 'submitted',
        ]);

        $admin = User::factory()->create([
            'username' => 'manager01',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->put(route('admin.orders.update', $order), [
                'ordered_number' => '0891234567',
                'selected_package' => 399,
                'title_prefix' => 'คุณ',
                'first_name' => 'สุดา',
                'last_name' => 'ดีพร้อม',
                'email' => 'suda@example.com',
                'current_phone' => '0812345678',
                'status' => 'paid',
            ]);

        $response->assertRedirect(route('admin.orders.show', $order));

        Http::assertSent(function (HttpRequest $request): bool {
            $payload = $request->data();
            $message = (string) ($payload['messages'][0]['text'] ?? '');

            return ($payload['to'] ?? null) === 'group-status'
                && str_contains($message, 'มีการอัปเดตสถานะคำสั่งซื้อ')
                && str_contains($message, 'สถานะเดิม: submitted')
                && str_contains($message, 'สถานะใหม่: paid');
        });

        $this->assertDatabaseHas('line_notification_logs', [
            'event_type' => 'order_status_updated',
            'destination_key' => 'order_status',
            'destination_id' => 'group-status',
            'status' => LineNotificationLog::STATUS_SENT,
        ]);
    }

    public function test_it_sends_line_notification_when_lottery_result_becomes_complete(): void
    {
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.group_id', 'group-default');
        config()->set('services.line.groups.lottery', 'group-lottery');
        config()->set('services.line.retry_sleep_ms', 0);
        config()->set('app.url', 'https://supernumber.example');

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response([
                'date' => '01/04/2026',
                'data' => $this->completeLotteryPayload(),
            ], 200),
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 4, 1, 16, 0, 0, 'Asia/Bangkok'));

        $this->artisan('lottery:fetch-latest')
            ->assertExitCode(0);

        Http::assertSent(function (HttpRequest $request): bool {
            $payload = $request->data();
            $message = (string) ($payload['messages'][0]['text'] ?? '');
            $imageMessage = $payload['messages'][1] ?? [];

            return $request->url() === 'https://api.line.me/v2/bot/message/push'
                && ($payload['to'] ?? null) === 'group-lottery'
                && ($payload['messages'][0]['type'] ?? null) === 'text'
                && ($imageMessage['type'] ?? null) === 'image'
                && str_contains((string) ($imageMessage['originalContentUrl'] ?? ''), '/line/lottery-results/')
                && str_contains((string) ($imageMessage['originalContentUrl'] ?? ''), 'signature=')
                && str_contains($message, 'ผลหวยออกแล้ว')
                && str_contains($message, 'รางวัลที่ 1: 123456')
                && str_contains($message, 'เลขท้าย 2 ตัว: 12');
        });

        $this->assertDatabaseHas('line_notification_logs', [
            'event_type' => 'lottery_completed',
            'destination_key' => 'lottery',
            'destination_id' => 'group-lottery',
            'status' => LineNotificationLog::STATUS_SENT,
        ]);
    }

    public function test_it_sends_a_text_only_line_notification_when_retry_day_ends_without_any_lottery_data(): void
    {
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.group_id', 'group-default');
        config()->set('services.line.groups.lottery', 'group-lottery');
        config()->set('services.line.retry_sleep_ms', 0);

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response([], 200),
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        Carbon::setTestNow(Carbon::create(2026, 4, 17, 16, 20, 0, 'Asia/Bangkok'));

        $this->artisan('lottery:fetch-latest')
            ->assertExitCode(0);

        Http::assertSent(function (HttpRequest $request): bool {
            $payload = $request->data();
            $messages = $payload['messages'] ?? [];
            $message = (string) ($messages[0]['text'] ?? '');

            return $request->url() === 'https://api.line.me/v2/bot/message/push'
                && ($payload['to'] ?? null) === 'group-lottery'
                && count($messages) === 1
                && ($messages[0]['type'] ?? null) === 'text'
                && str_contains($message, 'ยังไม่พบข้อมูลผลสลากกินแบ่งรัฐบาล')
                && str_contains($message, 'งวดวันที่: 16/04/2026')
                && str_contains($message, '17/04/2026 16:20');
        });

        $this->assertDatabaseHas('lottery_results', [
            'draw_date' => '2026-04-16',
            'is_complete' => 0,
        ]);

        $this->assertDatabaseHas('line_notification_logs', [
            'event_type' => 'lottery_unavailable_after_retry',
            'destination_key' => 'lottery',
            'destination_id' => 'group-lottery',
            'status' => LineNotificationLog::STATUS_SENT,
        ]);
    }

    public function test_it_does_not_send_duplicate_lottery_line_notification_for_an_already_complete_draw(): void
    {
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.group_id', 'group-default');
        config()->set('services.line.groups.lottery', 'group-lottery');
        config()->set('services.line.retry_sleep_ms', 0);

        $result = LotteryResult::query()->create([
            'draw_date' => '2026-04-01',
            'source_draw_date' => '2026-04-01',
            'source_draw_date_text' => '01/04/2026',
            'is_complete' => true,
            'fetched_at' => now(),
            'source_payload' => [],
        ]);

        foreach ($this->completeLotteryPayload() as $index => $prize) {
            $result->prizes()->create([
                'position' => $index,
                'prize_name' => $prize['name'],
                'prize_number' => $prize['number'],
            ]);
        }

        Http::fake([
            'https://www.glo.or.th/api/lottery/getLatestLottery' => Http::response([
                'date' => '01/04/2026',
                'data' => $this->completeLotteryPayload(),
            ], 200),
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2026, 4, 1, 16, 20, 0, 'Asia/Bangkok'));

        $this->artisan('lottery:fetch-latest', ['--force' => true])
            ->assertExitCode(0);

        Http::assertSentCount(1);

        $this->assertDatabaseMissing('line_notification_logs', [
            'event_type' => 'lottery_completed',
            'destination_key' => 'lottery',
        ]);
    }

    public function test_signed_lottery_line_image_route_serves_the_detail_image(): void
    {
        Storage::fake('public');

        $result = LotteryResult::query()->create([
            'draw_date' => '2026-04-01',
            'source_draw_date' => '2026-04-01',
            'source_draw_date_text' => '01/04/2026',
            'is_complete' => true,
            'fetched_at' => now(),
            'source_payload' => [],
        ]);

        foreach ($this->completeLotteryPayload() as $index => $prize) {
            $result->prizes()->create([
                'position' => $index,
                'prize_name' => $prize['name'],
                'prize_number' => $prize['number'],
            ]);
        }

        $validPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+pY9sAAAAASUVORK5CYII=',
            true
        );

        $this->assertIsString($validPng);

        Storage::disk('public')->put(
            'article/2026/thai-goverment-lottery-202604first/thai-goverment-lottery-202604first.png',
            $validPng
        );

        Article::query()->create([
            'title' => 'สลากกินแบ่งรัฐบาล ประจำวันที่ 1 เมษายน 2569',
            'slug' => 'thai-goverment-lottery-202604first',
            'excerpt' => 'lottery summary',
            'content' => '<p>lottery content</p>',
            'cover_image_path' => 'article/2026/thai-goverment-lottery-202604first/thai-goverment-lottery-202604first.png',
            'cover_image_square_path' => 'article/2026/thai-goverment-lottery-202604first/thai-goverment-lottery-202604first.png',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(URL::signedRoute('line.lottery-result-image', [
            'lotteryResult' => $result,
        ], absolute: false));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
    }

    private function completeLotteryPayload(): array
    {
        return [
            ['name' => 'รางวัลที่ 1', 'number' => '123456'],
            ['name' => 'เลขหน้า 3 ตัว', 'number' => '123'],
            ['name' => 'เลขหน้า 3 ตัว', 'number' => '456'],
            ['name' => 'เลขท้าย 3 ตัว', 'number' => '789'],
            ['name' => 'เลขท้าย 3 ตัว', 'number' => '012'],
            ['name' => 'เลขท้าย 2 ตัว', 'number' => '12'],
        ];
    }

    public function test_it_does_not_send_order_status_notifications_for_unconfigured_statuses(): void
    {
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.group_id', 'group-default');
        config()->set('services.line.groups.order_status', 'group-status');
        config()->set('services.line.order_status_events', ['submitted', 'paid', 'completed']);

        Http::fake([
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'selected_package' => 399,
            'payment_slip_path' => 'payment-slips/slip.jpg',
            'status' => 'submitted',
        ]);

        $admin = User::factory()->create([
            'username' => 'manager02',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this
            ->withSession($this->adminSession($admin))
            ->put(route('admin.orders.update', $order), [
                'ordered_number' => '0891234567',
                'selected_package' => 399,
                'status' => 'reviewing',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        Http::assertNothingSent();
        $this->assertDatabaseMissing('line_notification_logs', [
            'event_type' => 'order_status_updated',
        ]);
    }

    public function test_manager_can_trigger_a_line_test_message_from_the_order_detail_page(): void
    {
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.group_id', 'group-default');
        config()->set('services.line.groups.admin_test', 'group-admin-test');
        config()->set('services.line.retry_sleep_ms', 0);

        Http::fake([
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'selected_package' => 399,
            'payment_slip_path' => 'payment-slips/slip.jpg',
            'status' => 'submitted',
        ]);

        $admin = User::factory()->create([
            'username' => 'manager03',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->post(route('admin.orders.line-test', $order));

        $response->assertRedirect();
        $response->assertSessionHas('status_message');

        Http::assertSent(function (HttpRequest $request): bool {
            $payload = $request->data();
            $message = (string) ($payload['messages'][0]['text'] ?? '');

            return ($payload['to'] ?? null) === 'group-admin-test'
                && str_contains($message, 'ทดสอบระบบแจ้งเตือน LINE จากแอดมิน');
        });

        $this->assertDatabaseHas('line_notification_logs', [
            'event_type' => 'order_admin_test',
            'destination_key' => 'admin_test',
            'destination_id' => 'group-admin-test',
            'status' => LineNotificationLog::STATUS_SENT,
        ]);
    }

    public function test_admin_cannot_trigger_a_line_test_message_from_the_order_detail_page(): void
    {
        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'selected_package' => 399,
            'payment_slip_path' => 'payment-slips/slip.jpg',
            'status' => 'submitted',
        ]);

        $admin = User::factory()->create([
            'username' => 'admin-line-test-blocked',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->post(route('admin.orders.line-test', $order));

        $response->assertForbidden();
    }

    public function test_admin_line_test_returns_back_with_error_instead_of_500_when_notifier_throws(): void
    {
        config()->set('services.line.channel_access_token', 'line-token');
        config()->set('services.line.group_id', 'group-default');
        config()->set('services.line.groups.admin_test', 'group-admin-test');

        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'selected_package' => 399,
            'payment_slip_path' => 'payment-slips/slip.jpg',
            'status' => 'submitted',
        ]);

        $admin = User::factory()->create([
            'username' => 'manager04',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $this->app->instance(LineOrderNotifier::class, new class extends LineOrderNotifier
        {
            public function __construct()
            {
            }

            public function sendAdminTest(CustomerOrder $order): ?LineNotificationLog
            {
                throw new RuntimeException('boom');
            }
        });

        $response = $this
            ->withSession($this->adminSession($admin))
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.line-test', $order));

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHasErrors('line');
    }

    /**
     * @return array<string, mixed>
     */
    private function adminSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
        ];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
