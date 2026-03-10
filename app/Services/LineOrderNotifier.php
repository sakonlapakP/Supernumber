<?php

namespace App\Services;

use App\Models\CustomerOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineOrderNotifier
{
    public function sendOrderSubmitted(CustomerOrder $order): void
    {
        $token = (string) config('services.line.channel_access_token', '');
        $groupId = (string) config('services.line.group_id', '');

        if ($token === '' || $groupId === '') {
            return;
        }

        $message = $this->buildMessage($order);

        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->post('https://api.line.me/v2/bot/message/push', [
                    'to' => $groupId,
                    'messages' => [
                        [
                            'type' => 'text',
                            'text' => $message,
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('LINE order notification failed', [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'response' => $response->json() ?: $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('LINE order notification exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildMessage(CustomerOrder $order): string
    {
        $fullName = $order->full_name !== '' ? $order->full_name : '-';
        $appointment = $order->appointment_date
            ? $order->appointment_date->format('Y-m-d') . ' ' . ($order->appointment_time_slot ?: '')
            : '-';

        $adminUrl = route('admin.orders.show', $order);

        return implode("\n", [
            'มีคำสั่งซื้อเบอร์ใหม่',
            'Order #' . $order->id,
            'เบอร์: ' . ($order->ordered_number ?: '-'),
            'แพ็กเกจ: ' . number_format((int) $order->selected_package) . ' บาท/เดือน',
            'ชื่อ: ' . $fullName,
            'โทรติดต่อ: ' . ($order->current_phone ?: '-'),
            'นัดรับซิม: ' . trim($appointment) ?: '-',
            'สถานะ: ' . ($order->status ?: '-'),
            'รายละเอียด: ' . $adminUrl,
        ]);
    }
}
