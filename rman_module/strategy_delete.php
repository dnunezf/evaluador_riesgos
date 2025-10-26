<?php

declare(strict_types=1);
$config = require __DIR__ . '/config/config.php';
require __DIR__ . '/lib/OracleClient.php';
require __DIR__ . '/lib/StrategyRepo.php';

$id = (int)($_GET['id'] ?? 0);
$db = new OracleClient($config['oracle']);
$db->connect();
$repo = new StrategyRepo($db);
$repo->deleteStrategy($id);

header('Location: index.php');