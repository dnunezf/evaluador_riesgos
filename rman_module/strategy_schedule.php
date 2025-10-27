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
$st = $repo->getStrategy($id);
$scheduler = new Scheduler($config);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Siempre Oracle Scheduler
    $h = (int)$_POST['hour'];
    $m = (int)$_POST['min'];

    // --- Sanitizar días: solo MON..SUN o '*'
    $validDays = ['MON','TUE','WED','THU','FRI','SAT','SUN'];
    $daysIn = isset($_POST['days']) ? (array)$_POST['days'] : ['*'];
    $days = array_values(array_unique(array_map('strtoupper', $daysIn)));

    // Si viene '*' junto con otros, nos quedamos SOLO con '*'
    if (in_array('*', $days, true)) {
        $days = ['*'];
    } else {
        // Filtrar valores inválidos
        $days = array_values(array_intersect($days, $validDays));
        if (!$days) {
            $days = ['*']; // fallback a todos los días
        }
    }

    $scriptPath = $config['paths']['work_dir'] . '/' . $st['CODE'];
    $logPath    = $config['paths']['work_dir'] . '/' . preg_replace('/\.rma[n]?$/i', '.log', $st['CODE']);
    @mkdir($config['paths']['work_dir'], 0775, true);
    if (!file_exists($scriptPath)) {
        file_put_contents($scriptPath, "# Placeholder. Run once to materialize template.");
    }

    $cmd = $scheduler->buildCommand($scriptPath, $logPath);

    // --- RRULE: si es '*' -> DAILY; si hay días concretos -> WEEKLY + BYDAY
    if ($days === ['*']) {
        $ri = sprintf('FREQ=DAILY;BYHOUR=%d;BYMINUTE=%d;BYSECOND=0', $h, $m);
    } else {
        $ri = sprintf('FREQ=WEEKLY;BYDAY=%s;BYHOUR=%d;BYMINUTE=%d;BYSECOND=0',
            implode(',', $days), $h, $m);
    }

    // Crear/rehacer el JOB EXECUTABLE que ejecuta el comando shell
    $scheduler->createOracleScheduler($db, "RMAN_JOB_$id", $cmd, $ri);
    $msg = "Oracle Scheduler job created with interval: $ri";
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Schedule Strategy</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header class="appbar">
    <div class="inner">
        <div class="brand">
            <div class="logo">S</div>
            <div>Schedule</div>
        </div>
        <div class="right"><a class="button ghost" href="strategy_view.php?id=<?= $id ?>">← Back</a></div>
    </div>
</header>

<main class="wrap">
    <div class="page-title">
        <h1>Schedule “<?= htmlspecialchars($st['NAME']) ?>”</h1>
        <p class="subtitle">This module uses <b>Oracle Scheduler (DBMS_SCHEDULER)</b> exclusively.</p>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="card">
            <pre class="small"><?= htmlspecialchars($msg) ?></pre>
        </div>
    <?php endif; ?>

    <form class="card" method="post">
        <div class="grid cols-2">
            <div>
                <label>Time (24h)</label>
                <div class="grid cols-2">
                    <input type="number" name="hour" min="0" max="23" value="16" required>
                    <input type="number" name="min" min="0" max="59" value="0" required>
                </div>
                <div class="help">Example: <span class="kbd">16:00</span> daily. Se agenda con <span class="kbd">BYSECOND=0</span> y timezone de la sesión.</div>
            </div>

            <div>
                <label>Days</label>
                <div class="grid cols-2">
                    <?php foreach (['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as $d): ?>
                        <label class="small"><input type="checkbox" name="days[]" value="<?= $d ?>"> <?= $d ?></label>
                    <?php endforeach; ?>
                    <label class="small"><input type="checkbox" name="days[]" value="*" checked> EVERY DAY</label>
                </div>
                <div class="help small">Si seleccionas <b>EVERY DAY</b>, se ignoran los demás días.</div>
            </div>
        </div>

        <div class="btn-row">
            <button class="button primary" type="submit">Create Schedule</button>
            <a class="button ghost" href="strategy_view.php?id=<?= $id ?>">Cancel</a>
        </div>
    </form>
</main>
</body>
</html>
