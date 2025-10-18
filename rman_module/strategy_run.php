<?php

declare(strict_types=1);
$config = require __DIR__ . '/config/config.php';
require __DIR__ . '/lib/OracleClient.php';
require __DIR__ . '/lib/StrategyRepo.php';
require __DIR__ . '/lib/Scheduler.php';

$id = (int)($_GET['id'] ?? 0);
$db = new OracleClient($config['oracle']);
$db->connect();
$repo = new StrategyRepo($db);
$scheduler = new Scheduler($config);
$st = $repo->getStrategy($id);

// Make work dir
$work = $config['paths']['work_dir'];
@mkdir($work, 0775, true);

// Build RMAN script text from template
$tplName = match ($st['TYPE']) {
    'FULL'        => 'full_backup.rman.tpl',
    'INCREMENTAL' => 'incr_level1.rman.tpl',
    'PARTIAL'     => 'ts_backup.rman.tpl',
    default       => 'full_backup.rman.tpl'
};
$tpl = file_get_contents($config['paths']['templates'] . '/' . $tplName);
$compression = $st['COMPRESSION'] === 'Y' ? 'COMPRESSED' : '';
$incArch = $st['INCLUDE_ARCHIVE'] === 'Y' ? 'PLUS ARCHIVELOG' : '';
$ctrlCmd = $st['INCLUDE_CTRLFILE'] === 'Y' ? 'BACKUP CURRENT CONTROLFILE;' : '';
$objectList = '';
if ($st['TYPE'] === 'PARTIAL' && !empty($st['OBJECT_SCOPE'])) {
    $obj = json_decode($st['OBJECT_SCOPE'], true);
    $pieces = [];
    if (!empty($obj['tablespaces'])) $pieces[] = 'TABLESPACE ' . implode(', ', array_map(fn($x) => $x, $obj['tablespaces']));
    if (!empty($obj['datafiles']))   $pieces[] = 'DATAFILE ' . implode(', ', array_map('intval', $obj['datafiles']));
    $objectList = implode(' ', $pieces);
}


$user = $config['rman']['username'];   // C##RMAN
$pass = $config['rman']['password'];   // rman_pass
$tns  = $config['rman']['tns'];        // localhost/xe  (servicio ROOT, CON_ID=1)

$rmanConnect = $user . '/' . $pass . '@' . $tns; // SIN "AS SYSBACKUP", sin comillas

$scriptText = strtr($tpl, [
    '{COMPRESSION}'        => $compression,
    '{INCLUDE_ARCHIVE}'    => $incArch,
    '{CONTROLFILE_BACKUP}' => $ctrlCmd,
    '{OBJECT_LIST}'        => $objectList,
    '{OUTPUT_DIR}'         => $st['OUTPUT_DIR'],
    '{RMAN_CONNECT}'       => $rmanConnect,   // 👈 AÑADIDO
]);


$scriptPath = $work . '/' . $st['CODE'];
$logPath    = $work . '/' . preg_replace('/\.rma[n]?$/i', '.log', $st['CODE']);
file_put_contents($scriptPath, $scriptText);

// Create run record
$runId = $repo->createRun($id, $scriptPath, $logPath, false);

// Build command
$cmd = $scheduler->buildCommand($scriptPath, $logPath);

// Execute
$repo->appendLog($runId, 'INFO', 'Executing: ' . $cmd);
$rc = 1;
if (PHP_OS_FAMILY === 'Windows') {
    $ps1 = escapeshellarg(__DIR__ . '/bin/run_rman.ps1');
    $cmdline = "powershell -ExecutionPolicy Bypass -File $ps1 -Script " . escapeshellarg($scriptPath) . " -Log " . escapeshellarg($logPath);
    $rc = system($cmdline, $exit);
    $rc = $exit;
} else {
    $sh = escapeshellarg(__DIR__ . '/bin/run_rman.sh');
    $cmdline = "$sh " . escapeshellarg($scriptPath) . ' ' . escapeshellarg($logPath);
    if ($config['exec']['use_docker']) {
        $container = $config['exec']['docker_container'];
        $cmdline = "docker exec $container bash -lc " . escapeshellarg("rman cmdfile='$scriptPath' log='$logPath'");
    }
    $rc = system($cmdline, $exit);
    $rc = $exit;
}

$status = ($rc === 0) ? 'SUCCESS' : 'FAILED';
$repo->finishRun($runId, $status, (int)$rc);
$repo->appendLog($runId, $rc === 0 ? 'INFO' : 'ERROR', "Return code: $rc");

header('Location: strategy_view.php?id=' . $id);
