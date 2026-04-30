<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LineNotificationLog;

$logs = LineNotificationLog::orderBy('id', 'desc')->take(5)->get();

foreach ($logs as $log) {
    echo "ID: {$log->id} | Event: {$log->event_type} | Status: {$log->status} | Error: {$log->error_message} | Created: {$log->created_at}\n";
    if ($log->response_payload) {
        echo "Response: " . json_encode($log->response_payload) . "\n";
    }
    echo "---------------------------------\n";
}
