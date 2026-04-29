<?php
if (extension_loaded('imagick')) {
    echo "<h1>Imagick is INSTALLED ✅</h1>";
    $v = Imagick::getVersion();
    echo "<p>Version: " . $v['versionString'] . "</p>";
} else {
    echo "<h1>Imagick is NOT installed ❌</h1>";
    echo "<p>PHP is unable to use Imagick extension directly.</p>";
}

echo "<h2>PHP Functions Check:</h2>";
$funcs = ['proc_open', 'exec', 'shell_exec', 'system'];
foreach ($funcs as $f) {
    $status = function_exists($f) ? "ENABLED ✅" : "DISABLED ❌";
    echo "<p>$f: $status</p>";
}
