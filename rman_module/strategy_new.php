<?php
declare(strict_types=1);
$config = require __DIR__ . '/config/config.php';
require __DIR__ . '/lib/OracleClient.php';
$db = new OracleClient($config['oracle']);
$db->connect();

$tablespaces = $db->query("SELECT TABLESPACE_NAME FROM DBA_TABLESPACES ORDER BY 1");
$datafiles   = $db->query("SELECT FILE_ID, FILE_NAME FROM DBA_DATA_FILES ORDER BY FILE_ID");

/** Prefijo del código (solo lectura en UI) */
$codePrefix = $config['ui']['code_prefix'] ?? 'DBX-01-PD-';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create RMAN Strategy</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        /* Composer de código (prefijo + sufijo + .rma) */
        .compose-code{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
        .compose-code .kbd{
            background:#f3f4f6;border:1px solid var(--line);color:#111;
            padding:6px 10px;border-radius:10px;font-weight:600;
        }
        #codeSuffix{max-width:140px;text-align:center;font-weight:600;letter-spacing:1px}

        /* Apariencia clara del select deshabilitado */
        select:disabled{
            background:#f3f4f6;
            color:#6b7280;
            cursor:not-allowed;
            opacity:.95;
        }
    </style>
</head>
<body>
<header class="appbar">
    <div class="inner">
        <div class="brand"><div class="logo">R</div><div>New Strategy</div></div>
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
            <!-- CODE compuesto: prefijo fijo + sufijo numérico editable + .rma -->
            <div>
                <label for="codeSuffix">
                    Strategy Code
                    <span class="help small">Solo edita los <b>últimos dígitos</b>; el prefijo y la extensión están fijos.</span>
                </label>

                <div class="compose-code">
                    <span class="kbd" id="codePrefix" aria-label="Prefix"><?= htmlspecialchars($codePrefix) ?></span>
                    <input
                            id="codeSuffix"
                            name="code_suffix"
                            required
                            inputmode="numeric"
                            pattern="^[0-9]{2,6}$"
                            maxlength="6"
                            placeholder="001"
                            title="Solo dígitos (2 a 6)"
                    >
                    <span class="kbd" aria-hidden="true">.rma</span>
                </div>

                <!-- backend compatible -->
                <input type="hidden" id="code" name="code" value="">
                <input type="hidden" id="name" name="name" value="">

                <div class="help">Ejemplo: <span class="kbd"><?= htmlspecialchars($codePrefix) ?>123.rma</span> se guardará como código y nombre.</div>
            </div>

            <div>
                <label for="type">Backup Type</label>
                <select id="type" name="type" required>
                    <option value="FULL">FULL</option>
                    <option value="INCREMENTAL">INCREMENTAL</option>
                    <option value="PARTIAL">PARTIAL</option>
                    <option value="INCOMPLETE">INCOMPLETE</option>
                </select>
                <div class="help small">Partial habilita la selección de objetos más abajo.</div>
            </div>

            <div>
                <label for="priority">Priority</label>
                <!-- SIN name: nunca se envía este select; solo el hidden -->
                <select id="priority">
                    <option>LOW</option>
                    <option>MEDIUM</option>
                    <option>HIGH</option>
                    <option>CRITICAL</option>
                </select>
                <!-- SIEMPRE se envía este (único) 'priority' -->
                <input type="hidden" id="priorityHidden" name="priority" value="LOW">
                <div class="help small">Solo modificable cuando Backup Type = PARTIAL.</div>
            </div>

            <div>
                <label for="output">Output directory for pieces and logs</label>
                <input id="output" name="output" required placeholder="/opt/backups/rman">
                <div class="help">Ruta vista desde el host/contenedor Oracle.</div>
            </div>

            <div>
                <label for="lvl">Incremental Level</label>
                <select id="lvl" name="lvl">
                    <option value="0">0</option>
                    <option value="1">1</option>
                </select>
                <div class="help small">Solo aplica cuando el tipo es INCREMENTAL.</div>
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
                <div class="help small">Requerido para respaldos en caliente.</div>
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
            <p class="small">Seleccione tablespaces y/o datafiles para respaldar (solo para PARTIAL).</p>
            <div class="grid cols-2">
                <div>
                    <label>Tablespaces</label>
                    <div class="card" style="max-height:240px;overflow:auto">
                        <?php foreach ($tablespaces as $t): ?>
                            <label class="small">
                                <input type="checkbox" name="ts[]" value="<?= htmlspecialchars($t['TABLESPACE_NAME']) ?>">
                                <?= htmlspecialchars($t['TABLESPACE_NAME']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <label>Datafiles</label>
                    <div class="card" style="max-height:240px;overflow:auto">
                        <?php foreach ($datafiles as $f): ?>
                            <label class="small">
                                <input type="checkbox" name="df[]" value="<?= (int)$f['FILE_ID'] ?>">
                                ID <?= (int)$f['FILE_ID'] ?> — <?= htmlspecialchars($f['FILE_NAME']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <p class="help small">Cuando el tipo no es PARTIAL, esta selección se ignora.</p>
        </div>

        <div class="btn-row">
            <button class="button primary" type="submit">Save Strategy</button>
            <a class="button ghost" href="index.php">Cancel</a>
        </div>
    </form>
</main>

<script src="assets/app.js?v=lock"></script>
<!-- Fijador inline minimalista y a prueba de caché -->
<script>
    (function(){
        const type = document.getElementById('type');
        const pr   = document.getElementById('priority');
        const hid  = document.getElementById('priorityHidden');
        const pref = document.getElementById('codePrefix');
        const suf  = document.getElementById('codeSuffix');
        const code = document.getElementById('code');
        const name = document.getElementById('name');
        const EXT  = '.rma';

        if (!type || !pr || !hid) return;

        function applyPriorityLock(){
            const isPartial = String(type.value||'').toUpperCase()==='PARTIAL';
            pr.disabled = !isPartial;
            pr.setAttribute('aria-disabled', String(!isPartial));
            hid.value = pr.value; // siempre sincronizamos lo que ve el usuario
        }

        function syncCode(){
            if (!pref || !suf || !code) return;
            const sufVal = (suf.value||'').replace(/[^0-9]/g,'');
            if (suf.value !== sufVal) suf.value = sufVal;
            const full = (pref.textContent||'') + sufVal + EXT;
            code.value = full;
            if (name) name.value = full;
        }

        type.addEventListener('change', applyPriorityLock, {passive:true});
        pr.addEventListener('change',   () => { hid.value = pr.value; }, {passive:true});
        suf && suf.addEventListener('input', syncCode, {passive:true});

        // estado inicial + reintentos por posibles autofills
        applyPriorityLock(); syncCode();
        setTimeout(applyPriorityLock, 0);
        setTimeout(applyPriorityLock, 100);
    })();
</script>
</body>
</html>
