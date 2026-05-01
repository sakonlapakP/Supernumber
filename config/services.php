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
            'lottery' => env('LINE_LOTTERY_GROUP_ID'),
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

    'facebook' => [
        'page_id' => env('FB_PAGE_ID'),
        'page_access_token' => env('FB_PAGE_ACCESS_TOKEN'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 60),
    ],

    'ga4' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID'),
        'property_id' => env('GA4_PROPERTY_ID'),
        'service_account_json_base64' => env('GA4_SERVICE_ACCOUNT_JSON_BASE64'),
        'dashboard_cache_seconds' => (int) env('GA4_DASHBOARD_CACHE_SECONDS', 900),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'lottery' => [
        'article_footer' => env('LOTTERY_MSG_FOOTER', 'อัปเดตล่าสุด {updated_at} น. ข้อความนี้จัดทำขึ้นโดยอัตโนมัติหากข้อมูลผลรางวัลมีการระบุผิดพลาดขอน้อมรับและขออภัยในความไม่สะดวก'),
        'line_template' => env('LOTTERY_MSG_LINE', "ผลหวยออกแล้ว\nงวดวันที่: {draw_date}\nรางวัลที่ 1: {first_prize}\nเลขหน้า 3 ตัว: {front_three}\nเลขท้าย 3 ตัว: {back_three}\nเลขท้าย 2 ตัว: {last_two}\nข้างเคียงรางวัลที่ 1: {near_first}"),
        'fb_template' => env('LOTTERY_MSG_FB', "📝 ผลสลากกินแบ่งรัฐบาล: {title}\n\n{excerpt}\n\nอ่านผลรางวัลฉบับเต็มและตรวจเลขอื่นๆ ได้ที่นี่ครับ 👇\n{article_url}"),
    ],

];
