<?php

require __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;

// Mock Laravel config
date_default_timezone_set('Asia/Bangkok');

$unix = 1746929340; // Example timestamp

$date = Carbon::createFromTimestamp($unix);
echo "Default (Bangkok): " . $date->format('Y-m-d H:i:s') . "\n";

$dateUtc = Carbon::createFromTimestamp($unix, 'UTC');
echo "UTC: " . $dateUtc->format('Y-m-d H:i:s') . "\n";
echo "UTC to Bangkok: " . $dateUtc->timezone('Asia/Bangkok')->format('Y-m-d H:i:s') . "\n";
