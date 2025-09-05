<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* Localiza config/monitor_config.php subiendo 2 niveles desde /monitor/api */
$base = realpath(__DIR__ . '/../../'); // -> C:\wamp64\www\indaraConsultores
$cfg  = $base . '/config/monitor_config.php';
if (!file_exists($cfg)) {
    http_response_code(500);
    die("No encuentro config: $cfg");
}
require_once $cfg;

try {
    $conn = ora_conn(); // tu función de conexión
    if (!$conn) {
        $e = oci_error();
        throw new Exception('Fallo ora_conn(): ' . ($e['message'] ?? ''));
    }

    // 1) Prueba local
    $st = oci_parse($conn, "SELECT 'LOCAL_OK' AS txt FROM dual");
    oci_execute($st);
    $r = oci_fetch_assoc($st);
    echo "LOCAL: " . $r['TXT'] . "<br>";
    oci_free_statement($st);

    // 2) Prueba DBLINK (ajusta el nombre si usas otro)
    $st = oci_parse($conn, "SELECT 'DBLINK_OK' AS txt FROM dual@dblink_cliente_sim");
    if (!oci_execute($st)) {
        $e = oci_error($st);
        throw new Exception('Fallo DBLINK: ' . ($e['message'] ?? ''));
    }
    $r = oci_fetch_assoc($st);
    echo "DBLINK: " . $r['TXT'] . "<br>";
    oci_free_statement($st);

    oci_close($conn);
    echo "Todo bien ✅";

} catch (Exception $ex) {
    http_response_code(500);
    echo "ERROR: " . htmlspecialchars($ex->getMessage());
}
