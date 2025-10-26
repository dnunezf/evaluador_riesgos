<?php

declare(strict_types=1);
$config = require __DIR__ . '/config/config.php';
require __DIR__ . '/lib/OracleClient.php';
$db = new OracleClient($config['oracle']);
$db->connect();

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
    <header class="appbar">
        <div class="inner">
            <div class="brand">
                <div class="logo">R</div>
                <div>New Strategy</div>
            </div>
            <div class="right"><a class="button ghost" href="index.php">← Back</a></div>
        </div>
    </header>

    <main class="wrap">
        <div class="page-title">
            <h1>Create Strategy</h1>
            <p class="subtitle">Design the RMAN plan and catalog it.</p>
        </div>

        <form class="card" method="post" action="strategy_save.php" novalidate>
            <div class="grid cols-2">
                <div>
                    <label for="code">Strategy Code <span class="small">(e.g., <span class="kbd">rmadb0101.rma</span>)</span></label>
                    <input id="code" name="code" required pattern="^[a-zA-Z0-9_.-]+\.rma[n]?$" maxlength="32" placeholder="rmadb0101.rma">
                    <div class="help">This becomes the file name in <span class="kbd">/work</span>.</div>
                </div>
                <div>
                    <label for="name">Name</label>
                    <input id="name" name="name" required maxlength="120" placeholder="Full Daily Backup">
                </div>

                <div>
                    <label for="type">Backup Type</label>
                    <select id="type" name="type" required>
                        <option value="FULL">FULL</option>
                        <option value="INCREMENTAL">INCREMENTAL</option>
                        <option value="PARTIAL">PARTIAL</option>
                        <option value="INCOMPLETE">INCOMPLETE</option>
                    </select>
                    <div class="help">Partial enables object selection below.</div>
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
                    <div class="help">Path seen from the Oracle container/host.</div>
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

            <!-- PARTIAL scope -->
            <div id="scopeBox" class="card" hidden>
                <h2 style="margin-top:0">Objects</h2>
                <p class="small">Select tablespaces and/or datafiles.</p>
                <div class="grid cols-2">
                    <div>
                        <label>Tablespaces</label>
                        <div class="card" style="max-height:240px;overflow:auto">
                            <?php foreach ($tablespaces as $t): ?>
                                <label class="small"><input type="checkbox" name="ts[]" value="<?= htmlspecialchars($t['TABLESPACE_NAME']) ?>"> <?= htmlspecialchars($t['TABLESPACE_NAME']) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label>Datafiles</label>
                        <div class="card" style="max-height:240px;overflow:auto">
                            <?php foreach ($datafiles as $f): ?>
                                <label class="small"><input type="checkbox" name="df[]" value="<?= (int)$f['FILE_ID'] ?>"> ID <?= (int)$f['FILE_ID'] ?> — <?= htmlspecialchars($f['FILE_NAME']) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <p class="help">When FULL is selected, object selection is hidden.</p>
            </div>

            <div class="btn-row">
                <button class="button primary" type="submit">Save Strategy</button>
                <a class="button ghost" href="index.php">Cancel</a>
            </div>
        </form>
    </main>
    <script src="assets/app.js"></script>
</body>

</html>