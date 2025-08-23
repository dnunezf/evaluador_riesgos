<?php
// api/monitor_tick.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../modelo_monitor.php';

$config  = monitor_get_config();
$metric  = monitor_calc_consumo();
$critico = ($metric['consumo_pct'] >= (float)$config['critico_pct']);

if ($config['habilitado'] && $critico) {
    monitor_log_evento($metric['consumo_pct'], $metric['sql_text']);
}

echo json_encode([
    'ts'           => date('c'),
    'consumo_pct'  => $metric['consumo_pct'],
    'umbral_pct'   => (float)$config['critico_pct'],
    'critico'      => $critico ? 1 : 0,
    'delay_seg'    => (int)$config['delay_seg'],
    'habilitado'   => (int)$config['habilitado']
]);
