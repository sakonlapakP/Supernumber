<?php

namespace Tests\Feature;

use App\Models\LineWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LineWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_group_id_from_a_valid_line_webhook_event(): void
    {
        config()->set('services.line.channel_secret', 'test-secret');

        $payload = [
            'destination' => 'U123456789',
            'events' => [
                [
                    'type' => 'message',
                    'message' => [
                        'type' => 'text',
                        'text' => 'hello',
                    ],
                    'source' => [
                        'type' => 'group',
                        'groupId' => 'Cabcdef1234567890',
                        'userId' => 'Uabcdef1234567890',
                    ],
                    'timestamp' => 1710000000000,
                ],
            ],
        ];

        $rawBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = base64_encode(hash_hmac('sha256', (string) $rawBody, 'test-secret', true));

        $response = $this->call(
            'POST',
            route('line.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LINE_SIGNATURE' => $signature,
            ],
            $rawBody
        );

        $response->assertOk();

        $this->assertDatabaseHas('line_webhook_events', [
            'event_type' => 'message',
            'source_type' => 'group',
            'group_id' => 'Cabcdef1234567890',
            'user_id' => 'Uabcdef1234567890',
            'message_type' => 'text',
            'signature_valid' => 1,
        ]);
    }

    public function test_it_rejects_invalid_signature_when_channel_secret_is_set(): void
    {
        config()->set('services.line.channel_secret', 'test-secret');

        $rawBody = json_encode([
            'destination' => 'U123456789',
            'events' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response = $this->call(
            'POST',
            route('line.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LINE_SIGNATURE' => 'invalid-signature',
            ],
            $rawBody
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('line_webhook_events', [
            'event_type' => 'invalid_signature',
            'signature_valid' => 0,
        ]);
    }
}
