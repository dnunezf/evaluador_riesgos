<?php
// api/monitor_tick.php — Devuelve el último snapshot (ts, consumo_pct) y evalúa crítico= (>=85)
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/monitor_config.php';

$umbral = 30;  // fijo para tu práctica
$conn = ora_conn();

// Lee el último punto
$sql = "SELECT ts, consumo_pct FROM " . ORA_OWNER . ".mon_buffer_snapshot
        ORDER BY id DESC FETCH FIRST 1 ROWS ONLY";
$st = oci_parse($conn, $sql);
oci_execute($st);
$row = oci_fetch_assoc($st);
oci_free_statement($st);
oci_close($conn);

if (!$row) {
    echo json_encode([
        'ts' => null, 'consumo_pct' => 0.0, 'umbral_pct' => $umbral, 'critico' => 0,
        'msg' => 'Sin datos. Ejecuta SGA_PLOTE para generar snapshots.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Formatea ISO 8601 para Chart.js
$ts = $row['TS']; // viene como objeto de fecha OCI
$ts_iso = date('c', strtotime($ts));

$consumo = (float)$row['CONSUMO_PCT'];
$critico = ($consumo >= $umbral) ? 1 : 0;

echo json_encode([
    'ts'          => $ts_iso,
    'consumo_pct' => $consumo,
    'umbral_pct'  => $umbral,
    'critico'     => $critico
], JSON_UNESCAPED_UNICODE);
