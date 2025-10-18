<?php

declare(strict_types=1);
$config = require __DIR__ . '/config/config.php';
require __DIR__ . '/lib/OracleClient.php';

$db = new OracleClient($config['oracle']);
$db->connect();

// Load tablespaces and datafiles for PARTIAL
$tablespaces = $db->query("SELECT TABLESPACE_NAME FROM DBA_TABLESPACES ORDER BY 1");
$datafiles   = $db->query("SELECT FILE_ID, FILE_NAME FROM DBA_DATA_FILES ORDER BY FILE_ID");
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create RMAN Strategy</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
    <div class="wrap">
        <h1>Create Strategy</h1>
        <form class="card" method="post" action="strategy_save.php">
            <div class="grid cols-2">
                <div>
                    <label for="code">Strategy Code (e.g., rmadb0101.rma)</label>
                    <input id="code" name="code" required pattern="^[a-zA-Z0-9_.-]+\.rma[n]?$" maxlength="32">
                </div>
                <div>
                    <label for="name">Name</label>
                    <input id="name" name="name" required maxlength="120">
                </div>
                <div>
                    <label for="type">Backup Type</label>
                    <select id="type" name="type" required>
                        <option value="FULL">FULL</option>
                        <option value="INCREMENTAL">INCREMENTAL</option>
                        <option value="PARTIAL">PARTIAL</option>
                        <option value="INCOMPLETE">INCOMPLETE</option>
                    </select>
                </div>
                <div>
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority" required>
                        <option>LOW</option>
                        <option>MEDIUM</option>
                        <option>HIGH</option>
                        <option>CRITICAL</option>
                    </select>
                </div>
                <div>
                    <label for="output">Output directory for pieces and logs</label>
                    <input id="output" name="output" required placeholder="/opt/backups/rman">
                </div>
                <div>
                    <label for="lvl">Incremental Level</label>
                    <select id="lvl" name="lvl">
                        <option value="0">0</option>
                        <option value="1">1</option>
                    </select>
                </div>
                <div>
                    <label>Include controlfile</label>
                    <select name="ctrl">
                        <option value="Y">Yes</option>
                        <option value="N">No</option>
                    </select>
                </div>
                <div>
                    <label>Include archivelogs</label>
                    <select name="arch">
                        <option value="Y">Yes</option>
                        <option value="N">No</option>
                    </select>
                </div>
                <div>
                    <label>Compression</label>
                    <select name="cmp">
                        <option value="N">No</option>
                        <option value="Y">Yes</option>
                    </select>
                </div>
                <div>
                    <label>Encryption</label>
                    <select name="enc">
                        <option value="N">No</option>
                        <option value="Y">Yes</option>
                    </select>
                </div>
            </div>

            <div id="scopeBox" class="card" style="display:none">
                <h3>Objects (tablespaces or datafiles)</h3>
                <div class="grid cols-2">
                    <div>
                        <label>Tablespaces</label>
                        <?php foreach ($tablespaces as $t): ?>
                            <label class="small"><input type="checkbox" name="ts[]" value="<?= htmlspecialchars($t['TABLESPACE_NAME']) ?>"> <?= htmlspecialchars($t['TABLESPACE_NAME']) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <label>Datafiles</label>
                        <div class="card" style="max-height:220px;overflow:auto">
                            <?php foreach ($datafiles as $f): ?>
                                <label class="small"><input type="checkbox" name="df[]" value="<?= (int)$f['FILE_ID'] ?>"> ID <?= (int)$f['FILE_ID'] ?> — <?= htmlspecialchars($f['FILE_NAME']) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <p class="small">When FULL is selected, object selection is disabled.</p>
            </div>

            <div>
                <button class="button primary" type="submit">Save Strategy</button>
                <a class="button primary" href="index.php">Back</a>
            </div>
        </form>
    </div>
    <script src="assets/app.js"></script>
</body>

</html>