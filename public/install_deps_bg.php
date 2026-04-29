<?php
echo "Starting installation in background... Check back in 2-3 minutes.\n";

// Run commands in background and redirect output to a log file
$cmd = "(npm install playwright && npx playwright install chromium) > install_log.txt 2>&1 &";
exec($cmd);

echo "Command dispatched. Monitoring log: <a href='install_log.txt'>install_log.txt</a>";
