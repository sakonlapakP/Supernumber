<?php

namespace App\Services;

use App\Models\CustomerOrder;
use App\Models\LineNotificationLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class LineOrderNotifier
{
    public function __construct(
        private readonly LineNotifier $lineNotifier,
    ) {
    }

    public function sendOrderSubmitted(CustomerOrder $order): ?LineNotificationLog
    {
        return $this->lineNotifier->queueMessages(
            eventType: 'order_submitted',
            messages: $this->buildOrderMessages($order, 'มีคำสั่งซื้อเบอร์ใหม่'),
            notifiable: $order,
            destinationKey: 'order_submission',
        );
    }

    public function sendStatusUpdated(CustomerOrder $order, ?string $previousStatus = null): ?LineNotificationLog
    {
        if (! $this->shouldNotifyStatus($order->status)) {
            return null;
        }

        return $this->lineNotifier->queueMessages(
            eventType: 'order_status_updated',
            messages: $this->buildOrderMessages($order, 'มีการอัปเดตสถานะคำสั่งซื้อ', [
                'สถานะเดิม: ' . $this->displayStatus($previousStatus),
                'สถานะใหม่: ' . $this->displayStatus($order->status),
            ]),
            notifiable: $order,
            destinationKey: 'order_status',
        );
    }

    public function sendAdminTest(CustomerOrder $order): ?LineNotificationLog
    {
        return $this->lineNotifier->queueMessages(
            eventType: 'order_admin_test',
            messages: $this->buildOrderMessages($order, 'ทดสอบระบบแจ้งเตือน LINE จากแอดมิน', [
                'ทดสอบเมื่อ: ' . now()->timezone('Asia/Bangkok')->format('Y-m-d H:i'),
            ]),
            notifiable: $order,
            destinationKey: 'admin_test',
        );
    }

    public function notifyContactMessage(string $name, string $phone, string $message): ?LineNotificationLog
    {
        $text = implode("\n", [
            'มีข้อความติดต่อใหม่',
            'ชื่อ: ' . $name,
            'โทร: ' . $phone,
            'ข้อความ: ' . $message,
        ]);

        return $this->lineNotifier->queueText(
            eventType: 'contact_message',
            message: $text,
            destinationKey: 'contact_message',
        );
    }

    public function shouldNotifyStatus(?string $status): bool
    {
        $normalizedStatus = $this->normalizeStatus($status);

        if ($normalizedStatus === '') {
            return false;
        }

        $configuredStatuses = array_filter(array_map(
            fn ($value) => $this->normalizeStatus($value),
            (array) config('services.line.order_status_events', [])
        ));

        return in_array($normalizedStatus, $configuredStatuses, true);
    }

    private function buildOrderMessages(CustomerOrder $order, string $headline, array $extraLines = []): array
    {
        $fullName = $order->full_name !== '' ? $order->full_name : '-';
        $appointment = $order->appointment_date
            ? $order->appointment_date->format('Y-m-d') . ' ' . ($order->appointment_time_slot ?: '')
            : '-';
        $adminUrl = $this->absoluteUrl(route('admin.orders.show', $order, false));
        $slipUrl = $this->resolveSlipUrl($order);
        $slipImageUrl = $this->resolveLineImageUrl($order);

        $lines = array_merge([
            $headline,
            'Order #' . $order->id,
            'เบอร์: ' . ($order->ordered_number ?: '-'),
            'ประเภท: ' . $order->service_type_label,
            'ยอดชำระ: ' . $order->payment_label,
            'ชื่อ: ' . $fullName,
            'โทรติดต่อ: ' . ($order->current_phone ?: '-'),
            'นัดรับซิม: ' . trim($appointment) ?: '-',
            'สถานะ: ' . ($order->status ?: '-'),
        ], $extraLines);

        if ($slipUrl !== null && $slipImageUrl === null) {
            $lines[] = 'หลักฐานการโอน: ' . $slipUrl;
        }

        $lines[] = 'รายละเอียด: ' . $adminUrl;

        $messages = [
            [
                'type' => 'text',
                'text' => implode("\n", $lines),
            ],
        ];

        if ($slipImageUrl !== null) {
            $messages[] = [
                'type' => 'image',
                'originalContentUrl' => $slipImageUrl,
                'previewImageUrl' => $slipImageUrl,
            ];
        }

        return $messages;
    }

    private function resolveSlipUrl(CustomerOrder $order): ?string
    {
        $path = trim((string) $order->payment_slip_path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $signedUrl = $this->absoluteUrl(URL::signedRoute('line.payment-slip', [
            'order' => $order,
        ], absolute: false));

        if ($this->isPublicHttpsUrl($signedUrl)) {
            return $signedUrl;
        }

        $url = Storage::disk('public')->url($path);

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return $this->absoluteUrl($url);
    }

    private function resolveLineImageUrl(CustomerOrder $order): ?string
    {
        $path = trim((string) $order->payment_slip_path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return null;
        }

        $url = $this->resolveSlipUrl($order);

        if ($url === null || ! $this->isPublicHttpsUrl($url)) {
            return null;
        }

        return $url;
    }

    private function isPublicHttpsUrl(string $url): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        return ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            && ! str_ends_with($host, '.local');
    }

    private function normalizeStatus(?string $status): string
    {
        return Str::of((string) $status)->trim()->lower()->toString();
    }

    private function displayStatus(?string $status): string
    {
        $status = trim((string) $status);

        return $status !== '' ? $status : '-';
    }

    private function absoluteUrl(string $pathOrUrl): string
    {
        if (Str::startsWith($pathOrUrl, ['http://', 'https://'])) {
            return $pathOrUrl;
        }

        $baseUrl = rtrim((string) config('app.url', ''), '/');

        if ($baseUrl === '') {
            return url($pathOrUrl);
        }

        return $baseUrl . '/' . ltrim($pathOrUrl, '/');
    }
}
