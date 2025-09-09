<?php
// model/monitor/api/config_set.php
header('Content-Type: application/json; charset=utf-8');

define('APP_ROOT', realpath(__DIR__ . '/../../../'));
require_once APP_ROOT . '/config/monitor_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>0,'error'=>'Método no permitido']); exit;
}

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
if (!is_array($in)) {
    http_response_code(400);
    echo json_encode(['ok'=>0,'error'=>'JSON inválido']); exit;
}

$dblink   = isset($in['dblink'])  ? trim($in['dblink'])  : '';
$cliente  = isset($in['cliente']) ? trim($in['cliente']) : '';
$umbral   = isset($in['umbral'])  ? floatval($in['umbral']) : null;
$reload   = isset($in['reload'])  ? intval($in['reload'])    : null;
$enabled  = array_key_exists('enabled',$in) ? (bool)$in['enabled'] : true;
$alias    = isset($in['alias']) ? trim($in['alias']) : null;

if ($dblink === '' || $cliente === '' || $umbral === null || $reload === null) {
    http_response_code(400);
    echo json_encode(['ok'=>0,'error'=>'Faltan parámetros requeridos (dblink, cliente, umbral, reload)']); exit;
}
if ($umbral < 0 || $umbral > 100) {
    http_response_code(400);
    echo json_encode(['ok'=>0,'error'=>'Umbral fuera de rango (0..100)']); exit;
}
if ($reload < 1 || $reload > 3600) {
    http_response_code(400);
    echo json_encode(['ok'=>0,'error'=>'Reload fuera de rango (1..3600)']); exit;
}

$conn = ora_conn();
if (!$conn) {
    http_response_code(500);
    echo json_encode(['ok'=>0,'error'=>'Sin conexión']); exit;
}

$habYN = $enabled ? 'Y' : 'N';

// Intentar UPDATE por DBLINK; si no toca filas, hacer INSERT
$sqlUpd = "UPDATE ".ORA_OWNER.".MON_CONFIG_CLIENTE
           SET CLIENTE = :cliente,
               UMBRAL_PCT = :umbral,
               RELOAD_SEC = :reload,
               HABILITADO = :hab,
               ALIAS = :alias
           WHERE DBLINK = :dblink";
$stU = oci_parse($conn, $sqlUpd);
oci_bind_by_name($stU, ':cliente', $cliente);
oci_bind_by_name($stU, ':umbral',  $umbral);
oci_bind_by_name($stU, ':reload',  $reload);
oci_bind_by_name($stU, ':hab',     $habYN);
oci_bind_by_name($stU, ':alias',   $alias);
oci_bind_by_name($stU, ':dblink',  $dblink);

if (!@oci_execute($stU, OCI_NO_AUTO_COMMIT)) {
    $e = oci_error($stU);
    oci_rollback($conn);
    http_response_code(500);
    echo json_encode(['ok'=>0,'error'=>'UPDATE config','detail'=>$e['message']??'']); exit;
}

$rows = oci_num_rows($stU);
oci_free_statement($stU);

if ($rows === 0) {
    // No existía → INSERT
    $sqlIns = "INSERT INTO ".ORA_OWNER.".MON_CONFIG_CLIENTE
               (DBLINK, CLIENTE, UMBRAL_PCT, RELOAD_SEC, HABILITADO, ALIAS)
               VALUES (:dblink, :cliente, :umbral, :reload, :hab, :alias)";
    $stI = oci_parse($conn, $sqlIns);
    oci_bind_by_name($stI, ':dblink',  $dblink);
    oci_bind_by_name($stI, ':cliente', $cliente);
    oci_bind_by_name($stI, ':umbral',  $umbral);
    oci_bind_by_name($stI, ':reload',  $reload);
    oci_bind_by_name($stI, ':hab',     $habYN);
    oci_bind_by_name($stI, ':alias',   $alias);

    if (!@oci_execute($stI, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stI);
        oci_rollback($conn);
        http_response_code(500);
        echo json_encode(['ok'=>0,'error'=>'INSERT config','detail'=>$e['message']??'']); exit;
    }
    oci_free_statement($stI);
}

oci_commit($conn);
oci_close($conn);

echo json_encode([
    'ok'=>1,
    'saved'=>[
        'dblink'=>$dblink,
        'cliente'=>$cliente,
        'umbral'=>$umbral,
        'reload'=>$reload,
        'enabled'=>$enabled,
        'alias'=>$alias
    ]
], JSON_UNESCAPED_UNICODE);
