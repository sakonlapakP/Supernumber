<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'line' => [
        'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        'channel_secret' => env('LINE_CHANNEL_SECRET'),
        'group_id' => env('LINE_GROUP_ID'),
        'groups' => [
            'estimate' => env('LINE_ESTIMATE_GROUP_ID'),
            'order_submission' => env('LINE_ORDER_GROUP_ID'),
            'order_status' => env('LINE_ORDER_STATUS_GROUP_ID'),
            'admin_test' => env('LINE_TEST_GROUP_ID'),
        ],
        'order_status_events' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('LINE_ORDER_STATUS_EVENTS', 'submitted,paid,completed'))
        ))),
        'retry_times' => (int) env('LINE_RETRY_TIMES', 3),
        'retry_sleep_ms' => (int) env('LINE_RETRY_SLEEP_MS', 1000),
    ],

];
