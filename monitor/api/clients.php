<?php
header('Content-Type: application/json; charset=utf-8');
$clients = require dirname(__DIR__, 2) . '/config/monitor_clients.php';
echo json_encode([
    'clients' => array_map(
        fn($name, $cfg) => ['name' => $name, 'dblink' => $cfg['dblink'], 'umbral' => $cfg['umbral']],
        array_keys($clients),
        $clients
    )
], JSON_UNESCAPED_UNICODE);
