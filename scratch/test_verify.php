<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use App\Services\TestDiscoveryService;
use Illuminate\Contracts\Console\Kernel;

$app->make(Kernel::class)->bootstrap();

$service = new TestDiscoveryService();
$tests = $service->discoverTests();

echo "Found " . count($tests) . " tests.\n";
foreach (array_slice($tests, 0, 5) as $test) {
    echo "- {$test['thai_title']} ({$test['method']})\n";
}

// Test running one
if (!empty($tests)) {
    echo "\nRunning first test: {$tests[0]['method']} with filter: {$tests[0]['filter']}...\n";
    $result = $service->runTest($tests[0]['filter']);
    echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    echo "Output: " . substr($result['output'], 0, 200) . "...\n";
}
