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
            background:#f3f4f6;color:#6b7280;cursor:not-allowed;opacity:.95;
        }

        /* Cajitas de ayuda */
        .helpbox{
            margin-top:8px;background:#fff;border:1px solid var(--line);
            border-radius:10px;padding:12px;box-shadow:var(--shadow-sm);
            color:#111;
        }
        .helpbox h3{margin:0 0 6px;font-size:14px}
        .helpbox p{margin:6px 0}
        .helpbox ul{margin:6px 0 0 18px;padding:0}
        .muted{color:#6b7280}

        /* Toggle combinaciones */
        #comboHelp{display:none}
        #comboHelp.show{display:block}

        /* Botoncito info */
        .info-chip{
            display:inline-flex;align-items:center;gap:8px;padding:8px 10px;
            border:1px solid var(--line);border-radius:10px;background:#fff;cursor:pointer;
        }
        .info-chip .dot{width:18px;height:18px;border-radius:50%;display:grid;place-items:center;
            background:#eef2ff;border:1px solid #dbeafe;color:#1e3a8a;font-weight:700;font-size:12px}
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
                            required inputmode="numeric" pattern="^[0-9]{2,6}$" maxlength="6"
                            placeholder="001" title="Solo dígitos (2 a 6)">
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
                <div id="typeHelp" class="helpbox" aria-live="polite"></div>

                <!-- Toggle combinaciones -->
                <div style="margin-top:10px">
                    <button type="button" id="toggleCombos" class="info-chip">
                        <span class="dot">i</span>
                        <span>Ver combinaciones recomendadas</span>
                    </button>
                </div>
                <div id="comboHelp" class="helpbox">
                    <h3>Combinaciones de respaldo</h3>
                    <ul>
                        <li><b>Semanal L0 + Diaria L1</b>: L0 (INCREMENTAL nivel 0) cada semana; L1 (nivel 1) a diario. Equilibrio entre ventana y tiempo de recuperación.</li>
                        <li><b>FULL mensual + L1 diario</b>: imagen completa mensual y diferenciales diarios. Útil cuando el almacenamiento es amplio y se desea simplicidad de restauración.</li>
                        <li><b>PARTIAL diario + FULL semanal</b>: respalda a diario solo tablespaces críticos (PARTIAL) y realiza un FULL semanal para cobertura integral.</li>
                        <li><b>PITR (INCOMPLETE)</b>: úsalo solo cuando necesites volver a un punto en el tiempo específico (recuperación ante corrupciones lógicas, borrados masivos, etc.).</li>
                    </ul>
                    <p class="muted">Tip: habilita Block Change Tracking para acelerar incrementales; verifica ARCHIVELOG para respaldos en caliente.</p>
                </div>
            </div>

            <div>
                <label for="priority">Priority</label>
                <!-- SIN name: siempre envía el hidden para evitar conflictos -->
                <select id="priority">
                    <option>LOW</option>
                    <option>MEDIUM</option>
                    <option>HIGH</option>
                    <option>CRITICAL</option>
                </select>
                <input type="hidden" id="priorityHidden" name="priority" value="LOW">
                <div id="prioHelp" class="helpbox" aria-live="polite"></div>
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

        const typeHelpEl = document.getElementById('typeHelp');
        const prioHelpEl = document.getElementById('prioHelp');
        const comboBtn   = document.getElementById('toggleCombos');
        const comboBox   = document.getElementById('comboHelp');

        if (!type || !pr || !hid) return;

        /* ---------- HELPERS DE TEXTO ---------- */
        function getTypeHelp(val){
            const v = String(val||'').toUpperCase();
            if (v==='FULL') {
                return `
        <h3>FULL (copia completa)</h3>
        <p>Realiza una copia completa de la base de datos tal como está en el momento del respaldo. Es simple de restaurar y útil para puntos de referencia periódicos.</p>
        <ul>
          <li>Ventaja: restauración directa y predecible.</li>
          <li>Consideración: mayor ventana de backup y consumo de almacenamiento.</li>
          <li>Recomendación: ejecutar en ventanas bajas de uso o programar fuera de horario.</li>
        </ul>`;
            }
            if (v==='INCREMENTAL') {
                return `
        <h3>INCREMENTAL (niveles 0 y 1)</h3>
        <p>Respalda solo los bloques modificados desde el último respaldo de referencia. Nivel 0 crea la base; nivel 1 agrega cambios desde L0/L1 previos.</p>
        <ul>
          <li>Ventaja: reduce tiempo y espacio respecto a FULL frecuentes.</li>
          <li>Recomendación: habilitar Block Change Tracking para acelerar el scaneo.</li>
          <li>Flujo típico: L0 semanal + L1 diario.</li>
        </ul>`;
            }
            if (v==='PARTIAL') {
                return `
        <h3>PARTIAL (por objetos)</h3>
        <p>Permite seleccionar <b>tablespaces</b> y/o <b>datafiles</b> concretos. Ideal cuando hay datos fríos y calientes o cuando la ventana es limitada.</p>
        <ul>
          <li>Use <b>Prioridad</b> para indicar el orden: primero los de mayor valor.</li>
          <li>Combinable con un FULL/INCREMENTAL periódico para cobertura total.</li>
          <li>Útil para TS críticos que requieren respaldo más frecuente.</li>
        </ul>`;
            }
            if (v==='INCOMPLETE') {
                return `
        <h3>INCOMPLETE (PITR)</h3>
        <p>Recuperación a un punto en el tiempo. Útil ante corrupciones lógicas o eliminaciones accidentales. Requiere archivelogs apropiados.</p>
        <ul>
          <li>Úsalo solo cuando necesitas volver a un estado previo exacto.</li>
          <li>Planifica el punto objetivo (SCN/Time) y prueba procedimientos.</li>
        </ul>`;
            }
            return `<p class="muted">Seleccione un tipo de respaldo para ver su descripción.</p>`;
        }

        function getPrioHelp(val, isPartial){
            const base = `
      <h3>Prioridad del respaldo</h3>
      <p>La prioridad determina el <b>orden</b> de atención cuando se hace un <b>PARTIAL</b> con múltiples objetos.</p>`;
            const map = {
                'LOW': `<p><b>LOW</b>: objetos de menor urgencia o impacto.</p>`,
                'MEDIUM': `<p><b>MEDIUM</b>: equilibrio entre criticidad y frecuencia.</p>`,
                'HIGH': `<p><b>HIGH</b>: objetos importantes que deben entrar temprano.</p>`,
                'CRITICAL': `<p><b>CRITICAL</b>: primero en la cola; típicamente TS de transacciones o datos clave.</p>`
            };
            const note = isPartial
                ? `<p class="muted">Aplica porque el tipo actual es <b>PARTIAL</b>.</p>`
                : `<p class="muted">Nota: la prioridad <b>solo aplica</b> cuando el tipo de respaldo es <b>PARTIAL</b>. Actualmente no es PARTIAL.</p>`;
            const body = map[String(val||'LOW').toUpperCase()] || '';
            return base + body + note;
        }

        /* ---------- LÓGICA PRIORIDAD (bloqueo + envío) ---------- */
        function applyPriorityLock(){
            const isPartial = String(type.value||'').toUpperCase()==='PARTIAL';
            pr.disabled = !isPartial;
            pr.setAttribute('aria-disabled', String(!isPartial));
            // Mantener el hidden siempre sincronizado con lo visible
            hid.value = pr.value;
            // Actualizar ayuda de prioridad
            prioHelpEl.innerHTML = getPrioHelp(pr.value, isPartial);
        }

        /* ---------- LÓGICA CODE (prefijo + sufijo + .rma) ---------- */
        function syncCode(){
            if (!pref || !suf || !code) return;
            const sufVal = (suf.value||'').replace(/[^0-9]/g,'');
            if (suf.value !== sufVal) suf.value = sufVal;
            const full = (pref.textContent||'') + sufVal + EXT;
            code.value = full;
            if (name) name.value = full;
        }

        /* ---------- HELP INICIAL ---------- */
        function applyTypeHelp(){ typeHelpEl.innerHTML = getTypeHelp(type.value); }

        /* Eventos */
        type.addEventListener('change', () => { applyTypeHelp(); applyPriorityLock(); }, {passive:true});
        pr.addEventListener('change',   () => { hid.value = pr.value; prioHelpEl.innerHTML = getPrioHelp(pr.value, String(type.value||'').toUpperCase()==='PARTIAL'); }, {passive:true});
        suf && suf.addEventListener('input', syncCode, {passive:true});

        comboBtn.addEventListener('click', () => {
            comboBox.classList.toggle('show');
            comboBtn.querySelector('span:last-child').textContent =
                comboBox.classList.contains('show') ? 'Ocultar combinaciones recomendadas' : 'Ver combinaciones recomendadas';
        });

        /* Estado inicial + reintentos por posibles autofills */
        applyTypeHelp(); applyPriorityLock(); syncCode();
        setTimeout(() => { applyTypeHelp(); applyPriorityLock(); }, 0);
        setTimeout(() => { applyTypeHelp(); applyPriorityLock(); }, 100);
    })();
</script>
</body>
</html>
