<?php
$mysqlHost = 'localhost';
$mysqlDb   = 'test_db';
$mysqlUser = 'root';
$mysqlPass = 'root';

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

echo "🧹 Starting cleanup...\n";

// 1. Drop the big stress table
$pdo->exec("DROP TABLE IF EXISTS big_inno");
echo "✅ Dropped stress-test table.\n";

// 2. Ask InnoDB to free unused pages
try {
    $pdo->exec("SET GLOBAL innodb_buffer_pool_size = 134217728"); // shrink to 128M
    echo "✅ Shrunk buffer pool size to 128 MB.\n";
} catch (PDOException $e) {
    echo "⚠️ Could not shrink buffer pool dynamically: " . $e->getMessage() . "\n";
}

// 3. Explicitly flush caches
$pdo->exec("FLUSH TABLES");
$pdo->exec("FLUSH TABLES WITH READ LOCK");
$pdo->exec("UNLOCK TABLES");
$pdo->exec("RESET QUERY CACHE"); // no-op if query cache disabled

echo "✅ Flushed caches.\n";

echo "🧹 Cleanup complete. MySQL memory should return closer to baseline (may not release all until process recycles).\n";
