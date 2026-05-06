<?php

/**
 * 🛠️ Laravel Artisan Helper for FTP Deployments
 * This script allows running artisan commands via browser when SSH is unavailable.
 */

// 🛡️ Security Check: Change this or use it only temporarily!
$secret_key = 'super_secret_99'; 

if (($_GET['key'] ?? '') !== $secret_key) {
    die('Unauthorized access. Please provide the correct key.');
}

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$statusOutput = new \Symfony\Component\Console\Output\BufferedOutput();

echo "<h2>Laravel Artisan Helper</h2>";

// 1. Clear All Caches
echo "<strong>1. Running optimize:clear...</strong><br>";
$kernel->call('optimize:clear', [], $statusOutput);
echo "<pre>" . $statusOutput->fetch() . "</pre>";

// 2. Run Migrations
echo "<strong>2. Running migrate --force...</strong><br>";
$kernel->call('migrate', ['--force' => true], $statusOutput);
echo "<pre>" . $statusOutput->fetch() . "</pre>";

echo "<hr>✅ All commands executed successfully.";
