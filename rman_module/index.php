<?php

declare(strict_types=1);
$config = require __DIR__ . '/config/config.php';
require __DIR__ . '/lib/OracleClient.php';
require __DIR__ . '/lib/StrategyRepo.php';

$db = new OracleClient($config['oracle']);
$db->connect();
$repo = new StrategyRepo($db);

// ARCHIVELOG check
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
    <div class="wrap">
        <h1>RMAN Backup Strategies</h1>
        <p class="small">Database log mode:
            <?php if ($arch === 'ARCHIVELOG'): ?>
                <span class="badge ok">ARCHIVELOG</span>
            <?php else: ?>
                <span class="badge warn">NOT ARCHIVELOG</span>
                <?php if ($config['safety']['require_archivelog']): ?>
                    <span class="small">Hot backups may be unsafe.</span>
                <?php endif; ?>
            <?php endif; ?>
        </p>

        <div class="card">
            <a class="button primary" href="strategy_new.php">Create Strategy</a>
        </div>

        <div class="card">
            <h2>Catalog</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Last Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($strategies as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['CODE']) ?></td>
                            <td><?= htmlspecialchars($s['NAME']) ?></td>
                            <td><?= htmlspecialchars($s['TYPE']) ?></td>
                            <td><?= htmlspecialchars($s['PRIORITY']) ?></td>
                            <td class="small">
                                <?php
                                $row = $db->query("SELECT STATUS, ENDED_AT FROM RBACKUP_RUN WHERE STRATEGY_ID=:id ORDER BY STARTED_AT DESC FETCH FIRST 1 ROWS ONLY", [':id' => $s['ID']]);
                                if ($row) {
                                    $st = $row[0]['STATUS'];
                                    $cls = $st === 'SUCCESS' ? 'ok' : ($st === 'FAILED' ? 'err' : 'warn');
                                    echo "<span class='badge $cls'>" . htmlspecialchars($st) . "</span>";
                                } else echo '<span class="badge">N/A</span>';
                                ?>
                            </td>
                            <td>
                                <a class="button " href="strategy_view.php?id=<?= (int)$s['ID'] ?>">Open</a>
                                <a class="button " href="strategy_run.php?id=<?= (int)$s['ID'] ?>">Run now</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="assets/app.js"></script>
</body>

</html>