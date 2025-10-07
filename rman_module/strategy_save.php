<?php

declare(strict_types=1);

function required(string $k): string
{
    if (!isset($_POST[$k]) || trim((string)$_POST[$k]) === '') {
        http_response_code(400);
        exit("Missing field: $k");
    }
    return trim((string)$_POST[$k]);
}

$config = require __DIR__ . '/config/config.php';
require __DIR__ . '/lib/OracleClient.php';
require __DIR__ . '/lib/StrategyRepo.php';

$code = required('code');
$name = required('name');
$type = required('type');
$prio = required('priority');
$output = required('output');
$lvl = (int)($_POST['lvl'] ?? 0);
$ctrl = ($_POST['ctrl'] ?? 'Y') === 'Y' ? 'Y' : 'N';
$arch = ($_POST['arch'] ?? 'Y') === 'Y' ? 'Y' : 'N';
$cmp  = ($_POST['cmp']  ?? 'N') === 'Y' ? 'Y' : 'N';
$enc  = ($_POST['enc']  ?? 'N') === 'Y' ? 'Y' : 'N';

if (!preg_match('/^[A-Za-z0-9_.-]+\.rma[n]?$/', $code)) {
    http_response_code(422);
    exit('Invalid code format');
}
if ($type === 'PARTIAL') {
    $obj = ['tablespaces' => ($_POST['ts'] ?? []), 'datafiles' => array_map('intval', ($_POST['df'] ?? []))];
    if (!$obj['tablespaces'] && !$obj['datafiles']) {
        http_response_code(422);
        exit('Select at least one tablespace or datafile');
    }
    $objectScope = json_encode($obj, JSON_UNESCAPED_SLASHES);
} else {
    $objectScope = null;
    $lvl = ($type === 'INCREMENTAL') ? $lvl : 0;
}

$db = new OracleClient($config['oracle']);
$db->connect();
$repo = new StrategyRepo($db);

$id = $repo->insert([
    'CODE' => $code,
    'NAME' => $name,
    'TYPE' => $type,
    'INCREMENTAL_LVL' => $lvl,
    'INCLUDE_CTRLFILE' => $ctrl,
    'INCLUDE_ARCHIVE' => $arch,
    'PRIORITY' => $prio,
    'OBJECT_SCOPE' => $objectScope,
    'OUTPUT_DIR' => $output,
    'COMPRESSION' => $cmp,
    'ENCRYPTION' => $enc
]);

header('Location: strategy_view.php?id=' . (int)$id);
