<?php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/monitor_config.php';
$clients = require dirname(__DIR__, 2) . '/config/monitor_clients.php';

$cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';
$umbral  = (int)($clients[$cliente]['umbral'] ?? 30);

$conn = ora_conn();
if ($cliente === '') {
    $sqlC = "SELECT cliente FROM " . ORA_OWNER . ".mon_buffer_snapshot
           ORDER BY id DESC FETCH FIRST 1 ROWS ONLY";
    $stC = oci_parse($conn, $sqlC);
    oci_execute($stC);
    $rC = oci_fetch_assoc($stC);
    oci_free_statement($stC);
    $cliente = $rC['CLIENTE'] ?? null;
}

if (!$cliente) {
    echo json_encode(['ts' => null, 'consumo_pct' => 0.0, 'umbral_pct' => $umbral, 'critico' => 0, 'msg' => 'Sin datos'], JSON_UNESCAPED_UNICODE);
    oci_close($conn);
    exit;
}

$sql = "SELECT ts, consumo_pct FROM " . ORA_OWNER . ".mon_buffer_snapshot
        WHERE cliente = :c
        ORDER BY id DESC FETCH FIRST 1 ROWS ONLY";
$st = oci_parse($conn, $sql);
oci_bind_by_name($st, ':c', $cliente);
oci_execute($st);
$row = oci_fetch_assoc($st);
oci_free_statement($st);
oci_close($conn);

if (!$row) {
    echo json_encode(['ts' => null, 'consumo_pct' => 0.0, 'umbral_pct' => $umbral, 'critico' => 0, 'msg' => 'Sin datos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ts_iso = date('c', strtotime($row['TS']));
$consumo = (float)$row['CONSUMO_PCT'];
echo json_encode([
    'cliente'      => $cliente,
    'ts'           => $ts_iso,
    'consumo_pct'  => $consumo,
    'umbral_pct'   => $umbral,
    'critico'      => ($consumo >= $umbral) ? 1 : 0
], JSON_UNESCAPED_UNICODE);
