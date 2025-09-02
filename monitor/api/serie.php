<?php
// api/serie.php — Serie de tiempo desde MON_BUFFER_SNAPSHOT
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/monitor_config.php';

$conn = ora_conn();

// Parámetros: ?cliente=ClienteLocal&range_min=30
$cliente  = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';
$rangeMin = isset($_GET['range_min']) ? (int)$_GET['range_min'] : 30;
if ($rangeMin <= 0 || $rangeMin > 7*24*60) $rangeMin = 30;

// Si no mandan cliente, tomamos el más reciente automáticamente
if ($cliente === '') {
    $sqlC = "SELECT cliente FROM (SELECT cliente, MAX(ts) last_ts
           FROM " . ORA_OWNER . ".mon_buffer_snapshot GROUP BY cliente
           ORDER BY last_ts DESC) WHERE ROWNUM = 1";
    $stC = oci_parse($conn, $sqlC);
    oci_execute($stC);
    $rC = oci_fetch_assoc($stC);
    oci_free_statement($stC);
    if ($rC && !empty($rC['CLIENTE'])) {
        $cliente = $rC['CLIENTE'];
    } else {
        echo json_encode(['cliente'=>null,'points'=>[],'msg'=>'Sin datos'], JSON_UNESCAPED_UNICODE);
        oci_close($conn);
        exit;
    }
}

$sql = "SELECT TO_CHAR(ts,'YYYY-MM-DD\"T\"HH24:MI:SS') AS ts_iso,
               consumo_pct
        FROM " . ORA_OWNER . ".mon_buffer_snapshot
        WHERE cliente = :c
          AND ts >= SYSTIMESTAMP - NUMTODSINTERVAL(:m, 'MINUTE')
        ORDER BY ts";

$st = oci_parse($conn, $sql);
oci_bind_by_name($st, ':c', $cliente);
oci_bind_by_name($st, ':m', $rangeMin);
oci_execute($st);

$pts = [];
while ($r = oci_fetch_assoc($st)) {
    $pts[] = ['ts' => $r['TS_ISO'], 'consumo_pct' => (float)$r['CONSUMO_PCT']];
}
oci_free_statement($st);
oci_close($conn);

echo json_encode(['cliente'=>$cliente, 'range_min'=>$rangeMin, 'points'=>$pts], JSON_UNESCAPED_UNICODE);
