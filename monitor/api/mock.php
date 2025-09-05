<?php
header('Content-Type: application/json; charset=utf-8');
$pct = round(55 + 30 * sin(time() / 2.7) + (mt_rand(-80, 80) / 100), 1);
$pct = max(0, min(100, $pct));
echo json_encode([
    'ts'          => date('c'),
    'consumo_pct' => $pct,
    'umbral_pct'  => 85,
    'critico'     => $pct >= 85 ? 1 : 0
], JSON_UNESCAPED_UNICODE);
