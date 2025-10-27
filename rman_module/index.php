<?php

declare(strict_types=1);
$config = require __DIR__ . '/config/config.php';
require __DIR__ . '/lib/OracleClient.php';
require __DIR__ . '/lib/StrategyRepo.php';

$db = new OracleClient($config['oracle']);
$db->connect();
$repo = new StrategyRepo($db);

$arch = $db->query("SELECT LOG_MODE FROM V\$DATABASE")[0]['LOG_MODE'] ?? 'UNKNOWN';
$strategies = $repo->listStrategies();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RMAN Backup Strategies</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
<!-- App bar -->
<header class="appbar">
    <div class="inner">
        <div class="brand">
            <div class="logo">R</div>
            <div>RMAN Strategies <small>Oracle 21c XE</small></div>
        </div>
        <div class="right subtitle">Module catalog & run console</div>
    </div>
</header>

<main class="wrap">
    <div class="page-title">
        <h1>Backup Catalog</h1>
        <div class="btn-row">
            <a class="button ghost" href="../index.php" title="Back to site">← Back</a>
            <a class="button primary" href="strategy_new.php">＋ Create Strategy</a>
        </div>
    </div>

    <div class="card header-card">
        <div class="badge <?= $arch === 'ARCHIVELOG' ? 'ok' : 'warn' ?>">
            <span class="badge-dot" style="background:<?= $arch === 'ARCHIVELOG' ? '#22c55e' : '#f59e0b' ?>"></span>
            <?= $arch === 'ARCHIVELOG' ? 'ARCHIVELOG' : 'NOT ARCHIVELOG' ?>
        </div>
        <span class="small">
                <?= $arch === 'ARCHIVELOG'
                    ? 'Hot backups supported.'
                    : (($config['safety']['require_archivelog'] ?? false) ? 'Hot backups may be unsafe.' : ''); ?>
            </span>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Code</th>
                <th>Type</th>
                <th>Priority</th>
                <th>Last Status</th>
                <th class="right">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($strategies as $s): ?>
                <tr>
                    <td><code><?= htmlspecialchars($s['CODE']) ?></code></td>
                    <td><span class="badge"><?= htmlspecialchars($s['TYPE']) ?></span></td>
                    <td>
                        <?php
                        $prio = htmlspecialchars($s['PRIORITY']);
                        $cls = $prio === 'CRITICAL' ? 'err' : ($prio === 'HIGH' ? 'warn' : '');
                        ?>
                        <span class="badge <?= $cls ?>"><?= $prio ?></span>
                    </td>
                    <td>
                        <?php
                        $row = $db->query(
                            "SELECT STATUS, ENDED_AT FROM RBACKUP_RUN WHERE STRATEGY_ID=:id ORDER BY STARTED_AT DESC FETCH FIRST 1 ROWS ONLY",
                            [':id' => $s['ID']]
                        );
                        if ($row) {
                            $st = $row[0]['STATUS'];
                            $cls = $st === 'SUCCESS' ? 'ok' : ($st === 'FAILED' ? 'err' : 'warn');
                            echo "<span class='badge $cls'><span class=\"badge-dot\" style=\"background:"
                                . ($cls === 'ok' ? '#22c55e' : ($cls === 'err' ? '#ef4444' : '#f59e0b'))
                                . "\"></span>" . htmlspecialchars($st) . "</span>";
                        } else {
                            echo '<span class="badge">N/A</span>';
                        }
                        ?>
                    </td>
                    <td class="right">
                        <div class="actions">
                            <a class="button" href="strategy_view.php?id=<?= (int)$s['ID'] ?>">Open</a>
                            <a class="button success" href="strategy_run.php?id=<?= (int)$s['ID'] ?>">Run now</a>
                            <a class="button ghost" href="strategy_delete.php?id=<?= (int)$s['ID'] ?>">Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<footer class="wrap small">Powered by RMAN • UI refresh only</footer>
<script src="assets/app.js"></script>
</body>

</html>
