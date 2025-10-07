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
    <div class="wrap">
        <h1><?= htmlspecialchars($s['NAME']) ?> <span class="small">[<?= htmlspecialchars($s['CODE']) ?>]</span></h1>
        <div class="card grid cols-2">
            <div>
                <p><b>Type:</b> <?= htmlspecialchars($s['TYPE']) ?><?= $s['TYPE'] === 'INCREMENTAL' ? ' L' . $s['INCREMENTAL_LVL'] : '' ?></p>
                <p><b>Priority:</b> <?= htmlspecialchars($s['PRIORITY']) ?></p>
                <p><b>Output:</b> <?= htmlspecialchars($s['OUTPUT_DIR']) ?></p>
                <p><b>Options:</b> ctrlfile <?= $s['INCLUDE_CTRLFILE'] ?>, archivelogs <?= $s['INCLUDE_ARCHIVE'] ?>, compression <?= $s['COMPRESSION'] ?>, enc <?= $s['ENCRYPTION'] ?></p>
            </div>
            <div>
                <?php if ($s['OBJECT_SCOPE']): ?>
                    <pre class="small"><?= htmlspecialchars($s['OBJECT_SCOPE']) ?></pre>
                <?php else: ?>
                    <p class="small">Object scope: N/A</p>
                <?php endif; ?>
                <div>
                    <a class="button" href="strategy_run.php?id=<?= $id ?>">Run now</a>
                    <a class="button" href="strategy_schedule.php?id=<?= $id ?>">Schedule</a>
                    <a class="button" href="index.php">Back</a>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Runs</h2>
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
                            <td><?= $r['ENDED_AT'] ?></td>
                            <td><?= htmlspecialchars($r['STATUS']) ?></td>
                            <td>
                                <?php if ($r['LOG_PATH']): ?>
                                    <code class="small"><?= htmlspecialchars($r['LOG_PATH']) ?></code>
                                    <?php else: ?>N/A<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>