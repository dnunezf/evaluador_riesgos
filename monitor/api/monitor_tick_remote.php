<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/monitor_config.php';
$clients = require dirname(__DIR__, 2) . '/config/monitor_clients.php';

$cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : 'ClienteRemoto';
if (!isset($clients[$cliente]) || empty($clients[$cliente]['dblink'])) {
  http_response_code(400);
  echo json_encode(['error' => 'cliente_inválido_o_sin_dblink']);
  exit;
}
$DBLINK_NAME = $clients[$cliente]['dblink'];
$UMBRAL = (int)($clients[$cliente]['umbral'] ?? 85);

$conn = ora_conn();
$sql = <<<'SQL'
WITH
blk AS (SELECT TO_NUMBER(value) AS block_size FROM v$parameter@{DBLINK} WHERE name='db_block_size'),
mx  AS (
  SELECT CURRENT_SIZE AS max_bytes FROM v$sga_dynamic_components@{DBLINK} WHERE component='DEFAULT buffer cache'
  UNION ALL SELECT bytes FROM v$sgainfo@{DBLINK} WHERE name='Buffer Cache Size'
  UNION ALL SELECT NVL(TO_NUMBER(value),0) FROM v$parameter@{DBLINK} WHERE name='db_cache_size'
),
u AS (SELECT COUNT(*) AS non_free_buffers FROM v$bh@{DBLINK} WHERE status<>'free')
SELECT TO_CHAR(SYSTIMESTAMP,'YYYY-MM-DD"T"HH24:MI:SS.FF3TZH:TZM') AS ts_iso,
       (SELECT MAX(max_bytes) FROM mx) AS max_bytes,
       (SELECT non_free_buffers FROM u)*(SELECT block_size FROM blk) AS used_bytes
FROM dual
SQL;
$sql = str_replace('{DBLINK}', $DBLINK_NAME, $sql);

$st = oci_parse($conn, $sql);
if (!oci_execute($st)) {
  $e = oci_error($st);
  http_response_code(500);
  echo json_encode(['error' => 'DBLINK/SQL', 'detail' => $e['message'] ?? '']);
  exit;
}
$r = oci_fetch_assoc($st);
oci_free_statement($st);
oci_close($conn);
if (!$r) {
  echo json_encode(['error' => 'Sin filas']);
  exit;
}

$max = (float)$r['MAX_BYTES'];
$used = (float)$r['USED_BYTES'];
$pct = $max > 0 ? round(100.0 * $used / $max, 2) : 0.0;

echo json_encode([
  'cliente'      => $cliente,
  'ts'           => $r['TS_ISO'],
  'consumo_pct'  => $pct,
  'umbral_pct'   => $UMBRAL,
  'critico'      => ($pct >= $UMBRAL) ? 1 : 0
], JSON_UNESCAPED_UNICODE);
