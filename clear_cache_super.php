<?php
// Special script to clear Laravel caches without SSH
// Place this in your public_html folder

define('LARAVEL_START', microtime(true));

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "<h2>🧼 Supernumber Cache Cleaner</h2>";

try {
    $kernel->call('view:clear');
    echo "✅ View Cache Cleared!<br>";
    
    $kernel->call('cache:clear');
    echo "✅ General Cache Cleared!<br>";
    
    $kernel->call('config:clear');
    echo "✅ Config Cache Cleared!<br>";
    
    echo "<br><b>Done!</b> Now please delete this file for security.";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
