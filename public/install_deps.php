<?php
echo "<pre>Starting installation of Playwright and dependencies...\n";

// Run npm install
echo "Running: npm install playwright\n";
system('npm install playwright 2>&1', $result1);
echo "Result code: $result1\n\n";

// Run playwright install
echo "Running: npx playwright install chromium --with-deps\n";
// We use --with-deps but note it might require sudo in some environments. 
// If it fails, we at least get the browser.
system('npx playwright install chromium 2>&1', $result2);
echo "Result code: $result2\n\n";

echo "Installation attempt finished. Try sharing again now.</pre>";
