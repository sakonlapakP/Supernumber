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
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    echo "<strong>Running optimize:clear...</strong><br>";
    $kernel->call('optimize:clear');
    echo "Done.<br>";
}

// 2. Database Connection Check
echo "<strong>Checking Database Connection...</strong><br>";
try {
    \DB::connection()->getPdo();
    echo "✅ Database connection is OK.<br>";
} catch (\Exception $e) {
    die("<div style='color:red'>Database Error: " . $e->getMessage() . "</div>");
}

// 3. Run Migrations
echo "<strong>Running migrate --force...</strong><br>";
try {
    // Try to increase execution time
    set_time_limit(60);
    $result = $kernel->call('migrate', ['--force' => true]);
    echo "Migration Result Code: " . $result . "<br>";
} catch (\Exception $e) {
    echo "<div style='color:red'>Error: " . $e->getMessage() . "</div>";
}

echo "<hr>✅ Process finished. Try logging in now!";
echo "<br><br><a href='?key=$secret_key&action=clear'>Click here to try clearing cache again</a>";
