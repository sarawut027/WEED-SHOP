<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "DEBUGGING INDEX.PHP:\n";
try {
    require_once __DIR__ . '/includes/auth.php';
    echo "1. auth.php OK\n";
    require_once __DIR__ . '/config/Database.php';
    echo "2. Database.php OK\n";
    require_once __DIR__ . '/classes/ProductFactory.php';
    echo "3. ProductFactory.php OK\n";
    require_once __DIR__ . '/classes/CustomerGuard.php';
    echo "4. CustomerGuard.php OK\n";
    require_once __DIR__ . '/classes/Customer.php';
    echo "5. Customer.php OK\n";

    $db = Database::getInstance()->getConnection();
    echo "6. DB getConnection: " . ($db ? "Connected" : "Null") . "\n";

    require_once __DIR__ . '/includes/navbar.php';
    echo "7. navbar.php OK\n";

    echo "ALL INCLUDES OK!\n";
} catch (\Throwable $e) {
    echo "CAUGHT ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
