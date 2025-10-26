<?php

declare(strict_types=1);
$config = require __DIR__ . '/config/config.php';
require __DIR__ . '/lib/OracleClient.php';
require __DIR__ . '/lib/StrategyRepo.php';

$id = (int)($_GET['id'] ?? 0);
$db = new OracleClient($config['oracle']);
$db->connect();
$repo = new StrategyRepo($db);
$s = $repo->getStrategy($id);
$runs = $db->query("SELECT * FROM RBACKUP_RUN WHERE STRATEGY_ID=:id ORDER BY STARTED_AT DESC", [':id' => $id]);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Strategy <?= htmlspecialchars($s['CODE']) ?></title>
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
    <header class="appbar">
        <div class="inner">
            <div class="brand">
                <div class="logo">R</div>
                <div>Strategy</div>
            </div>
            <div class="right btn-row">
                <a class="button ghost" href="index.php">← Catalog</a>
                <a class="button success" href="strategy_run.php?id=<?= $id ?>">Run now</a>
                <a class="button" href="strategy_schedule.php?id=<?= $id ?>">Schedule</a>
            </div>
        </div>
    </header>

    <main class="wrap">
        <div class="page-title">
            <h1><?= htmlspecialchars($s['NAME']) ?> <span class="small">[<?= htmlspecialchars($s['CODE']) ?>]</span></h1>
            <p class="subtitle">Review configuration and execution history.</p>
        </div>

        <div class="card grid cols-2">
            <div>
                <p><b>Type:</b> <?= htmlspecialchars($s['TYPE']) ?><?= $s['TYPE'] === 'INCREMENTAL' ? ' L' . $s['INCREMENTAL_LVL'] : '' ?></p>
                <p><b>Priority:</b> <span class="badge"><?= htmlspecialchars($s['PRIORITY']) ?></span></p>
                <p><b>Output:</b> <code><?= htmlspecialchars($s['OUTPUT_DIR']) ?></code></p>
                <p><b>Options:</b> ctrlfile <?= $s['INCLUDE_CTRLFILE'] ?>, archivelogs <?= $s['INCLUDE_ARCHIVE'] ?>, compression <?= $s['COMPRESSION'] ?>, enc <?= $s['ENCRYPTION'] ?></p>
            </div>
            <div>
                <?php if ($s['OBJECT_SCOPE']): ?>
                    <label>Object scope</label>
                    <pre class="small" style="margin-top:6px"><?= htmlspecialchars($s['OBJECT_SCOPE']) ?></pre>
                <?php else: ?>
                    <p class="small">Object scope: N/A</p>
                <?php endif; ?>
                <div class="spacer"></div>
                <div class="btn-row">
                    <a class="button success" href="strategy_run.php?id=<?= $id ?>">Run now</a>
                    <a class="button" href="strategy_schedule.php?id=<?= $id ?>">Schedule</a>
                    <a class="button ghost" href="strategy_delete.php?id=<?= $id ?>">Delete</a>
                    <a class="button ghost" href="index.php">Back</a>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-top:0">Runs</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Started</th>
                        <th>Ended</th>
                        <th>Status</th>
                        <th>Log</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($runs as $r): ?>
                        <tr>
                            <td><?= $r['STARTED_AT'] ?></td>
                            <td><?= $r['ENDED_AT'] ?: '-' ?></td>
                            <td>
                                <?php $st = $r['STATUS'];
                                $cls = $st === 'SUCCESS' ? 'ok' : ($st === 'FAILED' ? 'err' : 'warn'); ?>
                                <span class="badge <?= $cls ?>"><span class="badge-dot" style="background:<?= $cls === 'ok' ? '#22c55e' : ($cls === 'err' ? '#ef4444' : '#f59e0b') ?>"></span><?= htmlspecialchars($st) ?></span>
                            </td>
                            <td>
                                <?php if ($r['LOG_PATH']): ?>
                                    <code class="small"><?= htmlspecialchars($r['LOG_PATH']) ?></code>
                                    <?php else: ?>N/A<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$runs): ?>
                        <tr>
                            <td colspan="4" class="small">No runs yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>