<?php

namespace App\Services;

use App\Models\LineNotificationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class LineNotifier
{
    public function isConfigured(?string $destinationKey = null): bool
    {
        return $this->resolveToken() !== ''
            && $this->resolveDestinationId($destinationKey) !== '';
    }

    public function queueText(
        string $eventType,
        string $message,
        ?Model $notifiable = null,
        ?string $destinationKey = null,
    ): ?LineNotificationLog {
        return $this->queueMessages(
            eventType: $eventType,
            messages: [
                [
                    'type' => 'text',
                    'text' => $message,
                ],
            ],
            notifiable: $notifiable,
            destinationKey: $destinationKey,
        );
    }

    public function queueMessages(
        string $eventType,
        array $messages,
        ?Model $notifiable = null,
        ?string $destinationKey = null,
    ): ?LineNotificationLog {
        if (! Schema::hasTable('line_notification_logs')) {
            return null;
        }

        $messages = array_values($messages);
        $token = $this->resolveToken();
        $destinationId = $this->resolveDestinationId($destinationKey);

        if ($token === '' || $destinationId === '' || $messages === []) {
            Log::warning('LINE: Skipping notification. Config missing.', [
                'has_token' => $token !== '',
                'has_destination' => $destinationId !== '',
                'has_messages' => $messages !== [],
                'event' => $eventType
            ]);
            return null;
        }

        $payload = [
            'to' => $destinationId,
            'messages' => $messages,
        ];

        $log = new LineNotificationLog();
        $log->forceFill([
            'event_type' => $eventType,
            'destination_key' => $destinationKey,
            'destination_id' => $destinationId,
            'status' => LineNotificationLog::STATUS_QUEUED,
            'message_preview' => $this->buildMessagePreview($messages),
            'request_payload' => $payload,
        ]);

        if ($notifiable) {
            $log->notifiable()->associate($notifiable);
        }

        $log->save();
        $this->deliverImmediately($log);

        return $log;
    }

    public function deliverLog(int $logId, int $attempt): void
    {
        if (! Schema::hasTable('line_notification_logs')) {
            return;
        }

        $log = LineNotificationLog::query()->find($logId);

        if (! $log) {
            return;
        }

        $token = $this->resolveToken();

        if ($token === '') {
            $this->markPermanentFailure($log, $attempt, 'LINE channel access token is missing.');
            return;
        }

        $payload = (array) ($log->request_payload ?? []);

        if (($payload['to'] ?? '') === '') {
            $payload['to'] = $log->destination_id ?: $this->resolveDestinationId($log->destination_key);
        }

        if (($payload['to'] ?? '') === '') {
            $this->markPermanentFailure($log, $attempt, 'LINE destination group is missing.');
            return;
        }

        try {
            $response = Http::timeout(10)
                ->retry(
                    (int) config('services.line.retry_times', 3),
                    (int) config('services.line.retry_sleep_ms', 1000),
                    null,
                    false
                )
                ->withToken($token)
                ->post('https://api.line.me/v2/bot/message/push', $payload);
        } catch (\Throwable $e) {
            $log->forceFill([
                'status' => LineNotificationLog::STATUS_FAILED,
                'attempts' => max((int) $log->attempts, $attempt),
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ])->save();

            throw $e;
        }

        $log->forceFill([
            'attempts' => max((int) $log->attempts, $attempt),
            'response_status' => $response->status(),
            'response_payload' => $this->normalizeResponsePayload($response),
        ]);

        if ($response->successful()) {
            $log->forceFill([
                'status' => LineNotificationLog::STATUS_SENT,
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ])->save();

            return;
        }

        $message = 'LINE returned HTTP ' . $response->status();

        $log->forceFill([
            'status' => LineNotificationLog::STATUS_FAILED,
            'error_message' => $message,
            'failed_at' => now(),
        ])->save();

        throw new RuntimeException($message);
    }

    private function resolveToken(): string
    {
        return trim((string) config('services.line.channel_access_token', ''));
    }

    private function resolveDestinationId(?string $destinationKey = null): string
    {
        $defaultId = trim((string) config('services.line.group_id', ''));

        if ($destinationKey !== null) {
            $configured = trim((string) config("services.line.groups.{$destinationKey}", ''));

            if ($configured !== '') {
                return $configured;
            }
            
            // Log that we're falling back to default
            if ($defaultId !== '') {
                Log::info("LINE: Destination key [{$destinationKey}] not found. Falling back to default Group ID.");
            }
        }

        return $defaultId;
    }

    private function markPermanentFailure(LineNotificationLog $log, int $attempt, string $message): void
    {
        $log->forceFill([
            'status' => LineNotificationLog::STATUS_FAILED,
            'attempts' => max((int) $log->attempts, $attempt),
            'error_message' => $message,
            'failed_at' => now(),
        ])->save();
    }

    private function buildMessagePreview(array $messages): string
    {
        $parts = [];

        foreach ($messages as $message) {
            $type = (string) ($message['type'] ?? '');

            if ($type === 'text') {
                $parts[] = trim((string) ($message['text'] ?? ''));
                continue;
            }

            if ($type === 'image') {
                $parts[] = '[image] ' . trim((string) ($message['originalContentUrl'] ?? ''));
                continue;
            }

            $parts[] = '[' . ($type !== '' ? $type : 'message') . ']';
        }

        return mb_substr(trim(implode("\n\n", array_filter($parts))), 0, 2000);
    }

    private function normalizeResponsePayload(Response $response): array
    {
        $json = $response->json();

        if (is_array($json)) {
            return $json;
        }

        return [
            'body' => trim($response->body()),
        ];
    }

    private function deliverImmediately(LineNotificationLog $log): void
    {
        try {
            $this->deliverLog($log->id, 1);
        } catch (\Throwable $e) {
            Log::warning('LINE notification delivery failed.', [
                'line_notification_log_id' => $log->id,
                'event_type' => $log->event_type,
                'destination_key' => $log->destination_key,
                'destination_id' => $log->destination_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
