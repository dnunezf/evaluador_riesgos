<?php
// api/serie_remote.php — Serie de tiempo desde el CLIENTE vía DBLINK.

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../../config/monitor_config.php';

const DBLINK_NAME = 'dblink_cliente_sim';
$rangeMin = isset($_GET['range_min']) ? (int)$_GET['range_min'] : 30;
if ($rangeMin <= 0 || $rangeMin > 7*24*60) $rangeMin = 30;

$conn = ora_conn();

function exec_or_error($conn, $sql, $binds = []) {
    $st = oci_parse($conn, $sql);
    foreach ($binds as $k => &$v) oci_bind_by_name($st, $k, $v);
    if (!oci_execute($st)) {
        $e = oci_error($st);
        return ['err' => $e['code'] ?? 0, 'msg' => $e['message'] ?? ''];
    }
    return $st;
}

// ================================ PLAN A =====================================
// ¿Existe MON_BUFFER_SNAPSHOT en el remoto (mismo OWNER que el usuario del dblink)?
$existsSql = "SELECT COUNT(*) AS c FROM all_tables@" . DBLINK_NAME . " WHERE owner = USER AND table_name = 'MON_BUFFER_SNAPSHOT'";
$st = exec_or_error($conn, $existsSql);
$remoteHasSnapshot = false;
if (!is_array($st)) {
    $row = oci_fetch_assoc($st);
    $remoteHasSnapshot = ($row && (int)$row['C'] > 0);
    oci_free_statement($st);
}

if ($remoteHasSnapshot) {
    // Cliente a consultar
    $cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';

    if ($cliente === '') {
        $sqlC = "SELECT cliente FROM (
                   SELECT cliente, MAX(ts) last_ts
                   FROM   mon_buffer_snapshot@" . DBLINK_NAME . "
                   GROUP  BY cliente
                   ORDER  BY last_ts DESC
                 ) WHERE ROWNUM = 1";
        $stC = exec_or_error($conn, $sqlC);
        if (!is_array($stC)) {
            $rC = oci_fetch_assoc($stC);
            oci_free_statement($stC);
            if ($rC && !empty($rC['CLIENTE'])) $cliente = $rC['CLIENTE'];
        }
        if ($cliente === '') $cliente = 'ClienteRemoto';
    }

    $sql = "SELECT TO_CHAR(ts,'YYYY-MM-DD\"T\"HH24:MI:SS') AS ts_iso,
                   consumo_pct
            FROM   mon_buffer_snapshot@" . DBLINK_NAME . "
            WHERE  cliente = :c
              AND  ts >= SYSTIMESTAMP@" . DBLINK_NAME . " - NUMTODSINTERVAL(:m, 'MINUTE')
            ORDER BY ts";

    $st = exec_or_error($conn, $sql, [':c' => $cliente, ':m' => $rangeMin]);

    if (!is_array($st)) {
        $pts = [];
        while ($r = oci_fetch_assoc($st)) {
            $pts[] = ['ts' => $r['TS_ISO'], 'consumo_pct' => (float)$r['CONSUMO_PCT']];
        }
        oci_free_statement($st);
        oci_close($conn);

        echo json_encode([
            'cliente'   => $cliente,
            'range_min' => $rangeMin,
            'points'    => $pts,
            'source'    => 'remote_table'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    // Si falló el Plan A, seguimos con Plan B
}

// ================================ PLAN B =====================================
// Punto “live” desde V$ por dblink (requiere SELECT_CATALOG_ROLE en remoto)
$sqlLive = <<<'SQL'
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
  TO_CHAR(SYSTIMESTAMP@{DBLINK}, 'YYYY-MM-DD"T"HH24:MI:SS') AS ts_iso,
  (SELECT MAX(max_bytes) FROM mx)                                  AS max_bytes,
  (SELECT non_free_buffers FROM u) * (SELECT block_size FROM blk)  AS used_bytes
FROM dual
SQL;

$sqlLive = str_replace('{DBLINK}', DBLINK_NAME, $sqlLive);
$st = exec_or_error($conn, $sqlLive);

if (is_array($st)) {
    http_response_code(500);
    echo json_encode([
        'cliente' => 'ClienteRemoto',
        'points'  => [],
        'source'  => 'live_fallback',
        'error'   => 'DBLINK/V$',
        'detail'  => $st['msg']
    ], JSON_UNESCAPED_UNICODE);
    oci_close($conn);
    return;
}

$r = oci_fetch_assoc($st);
oci_free_statement($st);
oci_close($conn);

if (!$r) {
    echo json_encode([
        'cliente' => 'ClienteRemoto',
        'points'  => [],
        'source'  => 'live_fallback',
        'error'   => 'Sin filas'
    ], JSON_UNESCAPED_UNICODE);
    return;
}

$max  = (float)$r['MAX_BYTES'];
$used = (float)$r['USED_BYTES'];
$pct  = $max > 0 ? round(100.0 * $used / $max, 2) : 0.0;

echo json_encode([
    'cliente'   => 'ClienteRemoto',
    'range_min' => $rangeMin,
    'points'    => [[ 'ts' => $r['TS_ISO'], 'consumo_pct' => $pct ]],
    'source'    => 'live_fallback'
], JSON_UNESCAPED_UNICODE);
