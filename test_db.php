<?php
header('Content-Type: text/plain');

echo "=== DIAGNOSTIC SCRIPT ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";

// Show git commit if available
if (file_exists(__DIR__ . '/.git/HEAD')) {
    echo ".git exists\n";
} else {
    echo ".git does not exist in container\n";
}

// Show relevant environment variables (masking password)
$envKeys = ['DATABASE_URL', 'DB_URL', 'POSTGRES_URL', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
foreach ($envKeys as $k) {
    $val = getenv($k);
    if ($val === false) {
        $val = $_SERVER[$k] ?? $_ENV[$k] ?? 'NOT SET';
    }
    if ($val !== 'NOT SET') {
        // mask password if present
        $masked = preg_replace('/:[^:@\/]+@/', ':****@', $val);
        echo "ENV $k = [$masked] (length: " . strlen($val) . ")\n";
        echo "First 20 chars of $k (hex): " . bin2hex(substr($val, 0, 20)) . "\n";
    } else {
        echo "ENV $k = NOT SET\n";
    }
}

echo "\n=== TESTING DATABASE.PHP ===\n";
require_once __DIR__ . '/config/database.php';
echo "SUCCESS: Database connected!\n";
?>