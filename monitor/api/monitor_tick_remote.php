<?php
// Lee consumo del buffer EN LA BD DEL CLIENTE vía DBLINK (en vivo)
// Requiere: GRANT SELECT_CATALOG_ROLE TO maria_link;

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/monitor_config.php';

const DBLINK_NAME = 'dblink_cliente_sim';
$UMBRAL = 85;

$conn = ora_conn();

// usar NOWDOC para que NO se interpole nada dentro (v$... , {DBLINK}, etc.)
$sql = <<<'SQL'
WITH
blk AS (
  SELECT TO_NUMBER(value) AS block_size
  FROM   v$parameter@{DBLINK} WHERE name = 'db_block_size'
),
mx AS (
  SELECT CURRENT_SIZE AS max_bytes
  FROM   v$sga_dynamic_components@{DBLINK}
  WHERE  component = 'DEFAULT buffer cache'
  UNION ALL
  SELECT bytes
  FROM   v$sgainfo@{DBLINK}
  WHERE  name = 'Buffer Cache Size'
  UNION ALL
  SELECT NVL(TO_NUMBER(value),0)
  FROM   v$parameter@{DBLINK}
  WHERE  name = 'db_cache_size'
),
u AS (
  SELECT COUNT(*) AS non_free_buffers
  FROM   v$bh@{DBLINK}
  WHERE  status <> 'free'
)
SELECT
  TO_CHAR(SYSTIMESTAMP, 'YYYY-MM-DD"T"HH24:MI:SS.FF3TZH:TZM') AS ts_iso,
  (SELECT MAX(max_bytes) FROM mx)                                  AS max_bytes,
  (SELECT non_free_buffers FROM u) * (SELECT block_size FROM blk)  AS used_bytes
FROM dual@{DBLINK}
SQL;


$sql = str_replace('{DBLINK}', DBLINK_NAME, $sql);

$st = oci_parse($conn, $sql);
if (!oci_execute($st)) {
    $e = oci_error($st);
    http_response_code(500);
    echo json_encode([
        'error'  => 'DBLINK/SQL',
        'detail' => $e['message'] ?? 'Error ejecutando SQL'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$r = oci_fetch_assoc($st);
oci_free_statement($st);
oci_close($conn);

if (!$r) {
    echo json_encode(['error' => 'Sin filas desde remoto'], JSON_UNESCAPED_UNICODE);
    exit;
}

$max  = (float)$r['MAX_BYTES'];
$used = (float)$r['USED_BYTES'];
$pct  = $max > 0 ? round(100.0 * $used / $max, 2) : 0.0;

echo json_encode([
    'ts'          => $r['TS_ISO'],
    'consumo_pct' => $pct,
    'umbral_pct'  => $UMBRAL,
    'critico'     => ($pct >= $UMBRAL) ? 1 : 0
], JSON_UNESCAPED_UNICODE);
