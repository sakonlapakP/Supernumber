<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\LineNotificationLog;
use App\Models\User;
use App\Services\LineOrderNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
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
                && str_contains($message, 'แพ็กเกจ: 399 บาท/เดือน')
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

    public function test_admin_can_trigger_a_line_test_message_from_the_order_detail_page(): void
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
}
