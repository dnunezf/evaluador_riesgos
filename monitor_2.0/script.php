<?php
$mysqlHost = 'localhost';
$mysqlDb   = 'test_db';
$mysqlUser = 'root';
$mysqlPass = 'root';

$mysqlHost2 = 'localhost';
$mysqlDb2   = 'monitor_db';
$mysqlUser2 = 'root';
$mysqlPass2 = 'root';

try {
    $pdo = new PDO(
        "mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8",
        $mysqlUser,
        $mysqlPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

echo "🔧 Setting InnoDB buffer pool size...\n";

try {
    // 4G (in bytes)
    $pdo->exec("SET GLOBAL innodb_buffer_pool_size = " . (20 * 1024 * 1024 * 1024));
} catch (PDOException $e) {
    echo "⚠️ Could not resize buffer pool dynamically: " . $e->getMessage() . "\n";
}

$stmt = $pdo->query("SHOW VARIABLES LIKE 'innodb_buffer_pool_size'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "Starting InnoDB memory stress test...\n";

$tableName   = "big_inno";
$rowsToInsert = 500000;   // adjust upward if you want to fill more
$payloadSize  = 5000000;    // ~50 KB per row

// Drop and recreate table
$pdo->exec("DROP TABLE IF EXISTS $tableName");
$pdo->exec("
    CREATE TABLE $tableName (
        id INT AUTO_INCREMENT PRIMARY KEY,
        data LONGBLOB
    ) ENGINE=InnoDB
");

$stmt = $pdo->prepare("INSERT INTO $tableName (data) VALUES (:d)");
$payload = str_repeat('X', $payloadSize);

for ($i = 1; $i <= $rowsToInsert; $i++) {
    $stmt->execute([':d' => $payload]);

    if ($i % 1000 == 0) {
        echo "Inserted $i rows (~" . round($i * $payloadSize / 1024 / 1024) . " MB data)\n";
    }
    sleep(1);
}

echo "✅ InnoDB memory stress test complete.\n";
