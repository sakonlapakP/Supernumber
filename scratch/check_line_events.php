<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$events = \DB::table('line_webhook_events')->orderBy('id', 'desc')->take(5)->get();

foreach ($events as $event) {
    $payload = json_decode($event->payload, true);
    $groupId = data_get($payload, 'source.groupId');
    echo "ID: {$event->id} | Type: {$event->event_type} | GroupID: {$groupId} | Created: {$event->created_at}\n";
}
