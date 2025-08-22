<?php
require_once 'modelo.php';
$tareas = getTareas();

/** Normas para el selector */
$normas = function_exists('getNormas') ? getNormas() : [
    ['Id' => 1, 'Nombre' => 'ISO/IEC 27002:2005'],
    ['Id' => 2, 'Nombre' => 'COBIT 4.1'],
];

$labels = ['C' => 'Confidencialidad', 'I' => 'Integridad', 'D' => 'Disponibilidad'];

/** Preselección opcional por GET (?norma=2), 0 = Todas */
$normaSeleccionada = isset($_GET['norma']) ? (int)$_GET['norma'] : 0;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Formulario de Evaluación de Control Interno</title>
    <link rel="stylesheet" href="css/styles.css">
    <!-- Estilos para avisos y resaltado de errores -->
    <style>
        .re-row.error {
            outline: 2px solid #e74c3c;
            background: #ffecec;
            scroll-margin-top: 80px;
        }
        .error-msg {
            display: inline-block;
            margin-left: 8px;
            padding: 2px 8px;
            border-radius: 8px;
            background: #ffd1d1;
            color: #7a0c0c;
            font-weight: 700;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

<div class="page">
    <h1>Formulario de Evaluación de Control Interno</h1>
    <p class="legend">
        Riesgos: <strong>C</strong> = Confidencialidad, <strong>I</strong> = Integridad, <strong>D</strong> = Disponibilidad.
    </p>

    <!-- CONTROLES DE FILTRO -->
    <div class="controls card" id="semaphore">
        <div class="group">
            <strong>Riesgo:</strong>
            <label><input type="checkbox" class="risk-filter" value="C" checked> C</label>
            <label><input type="checkbox" class="risk-filter" value="I" checked> I</label>
            <label><input type="checkbox" class="risk-filter" value="D" checked> D</label>
        </div>

        <div class="group">
            <strong>Norma:</strong>
            <select id="normaSelect">
                <option value="0" <?php echo $normaSeleccionada === 0 ? 'selected' : ''; ?>>Todas</option>
                <?php foreach ($normas as $n): ?>
                    <option value="<?php echo (int)$n['Id']; ?>"
                        <?php echo $normaSeleccionada === (int)$n['Id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($n['Nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="button" id="resetFilters">Mostrar todo</button>
        <div class="muted" id="filterStatus"></div>
    </div>

    <form action="procesar.php" method="post">
        <?php if (empty($tareas)): ?>
            <p>No hay actividades registradas.</p>
        <?php else: ?>
            <?php foreach ($tareas as $index => $tarea): ?>
                <div class="tarea-block" data-index="<?php echo $index; ?>">
                    <h2 class="tarea-title"><?php echo htmlspecialchars($tarea['Nombre']); ?></h2>
                    <table class="tarea-table">
                        <thead>
                        <tr>
                            <th>Pregunta</th>
                            <th>S</th>
                            <th>N</th>
                            <th>NA</th>
                            <th>Riesgo(s)</th>
                            <th>Norma</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $requisitos = getRequisitosPorTarea($tarea['Id']);
                        foreach ($requisitos as $re):
                            $riesgos = getRiesgosPorRequisito($re['Id']);
                            $normaNombre = getNormaPorRequisito($re['Id']);
                            $normaId = (int)($re['NormaId'] ?? 0);
                            $name = "respuesta_" . $tarea['Id'] . "_" . $re['Id'];

                            $keys = [];
                            foreach ($riesgos as $r) {
                                $k = strtoupper(substr($r['Tipo'], 0, 1));
                                if (in_array($k, ['C', 'I', 'D'], true)) $keys[] = $k;
                            }
                            $keys = array_values(array_unique($keys));
                            $dataRisks = implode(',', $keys);

                            $chips = '<div class="chips">';
                            foreach ($keys as $k) {
                                $title = $labels[$k] ?? $k;
                                $chips .= '<span class="riesgo" title="' . htmlspecialchars($title) . '">' . $k . '</span>';
                            }
                            $chips .= '</div>';
                            ?>
                            <tr class="re-row"
                                data-risks="<?php echo htmlspecialchars($dataRisks); ?>"
                                data-norma="<?php echo $normaId; ?>">
                                <td><?php echo htmlspecialchars($re['Texto']); ?></td>
                                <td><input type="radio" name="<?php echo $name; ?>" value="S" required></td>
                                <td><input type="radio" name="<?php echo $name; ?>" value="N"></td>
                                <td><input type="radio" name="<?php echo $name; ?>" value="NA"></td>
                                <td><?php echo $chips; ?></td>
                                <td><?php echo htmlspecialchars($normaNombre); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>

            <!-- Navigation -->
            <div class="nav-controls">
                <button type="button" id="prevTarea">Anterior</button>
                <button type="button" id="nextTarea">Siguiente</button>
            </div>
            <button type="submit">Evaluar Riesgos</button>

        <?php endif; ?>
    </form>
</div>
<div class="circle" id="circle"></div>

<script>
    (function () {
        // --- refs UI ---
        const riskChecks = Array.from(document.querySelectorAll('.risk-filter'));
        const normaSelect = document.getElementById('normaSelect');
        const status = document.getElementById('filterStatus');
        const resetBtn = document.getElementById('resetFilters');
        const prevBtn = document.getElementById('prevTarea');
        const nextBtn = document.getElementById('nextTarea');
        const blocks = Array.from(document.querySelectorAll('.tarea-block'));
        const form = document.querySelector('form[action="procesar.php"]');

        // estado navegación sobre índices VISIBLES
        let visibleIdx = []; // p.ej. [0,3,5]
        let currentPos = 0;  // posición dentro de visibleIdx

        function setRowInputsDisabled(row, disabled) {
            row.querySelectorAll('input[type="radio"]').forEach(inp => {
                if (disabled) {
                    inp.dataset.wasRequired = inp.required ? '1' : '';
                    inp.required = false;
                    inp.disabled = true;
                } else {
                    inp.disabled = false;
                    if (inp.dataset.wasRequired) {
                        const name = inp.name;
                        const group = row.querySelectorAll(`input[type="radio"][name="${CSS.escape(name)}"]`);
                        if (group.length) group[0].required = true;
                        delete inp.dataset.wasRequired;
                    }
                }
            });
        }

        function rebuildVisibleIndex(preferIndex = null) {
            visibleIdx = [];
            blocks.forEach((b, i) => {
                if (!b.classList.contains('hidden')) visibleIdx.push(i);
            });
            if (visibleIdx.length === 0) {
                currentPos = -1;
                prevBtn.disabled = true;
                nextBtn.disabled = true;
                return;
            }
            const target = (preferIndex !== null && visibleIdx.includes(preferIndex))
                ? preferIndex
                : (visibleIdx.includes(getCurrentBlockIndex()) ? getCurrentBlockIndex() : visibleIdx[0]);

            currentPos = visibleIdx.indexOf(target);
            updateNavButtons();
            showBlockByPos(currentPos);
        }

        function getCurrentBlockIndex() {
            const idx = blocks.findIndex(b => !b.classList.contains('hidden') && b.style.display !== 'none');
            return idx === -1 ? (visibleIdx[0] ?? 0) : idx;
        }

        function updateNavButtons() {
            prevBtn.disabled = (currentPos <= 0);
            nextBtn.disabled = (currentPos === -1 || currentPos >= visibleIdx.length - 1);
        }

        function showBlockByPos(pos) {
            const targetIndex = visibleIdx[pos];
            blocks.forEach((b, i) => b.classList.toggle('hidden', i !== targetIndex));
            updateNavButtons();
        }

        function applyFilter() {
            const enabledRisks = new Set(riskChecks.filter(c => c.checked).map(c => c.value));
            const selectedNorma = normaSelect.value;

            const rows = Array.from(document.querySelectorAll('tr.re-row'));
            let shown = 0, total = rows.length;

            rows.forEach(row => {
                const risks = (row.getAttribute('data-risks') || '')
                    .split(',').map(s => s.trim()).filter(Boolean);
                const norma = row.getAttribute('data-norma');

                const matchRisk = (risks.length === 0) ? true : risks.some(r => enabledRisks.has(r));
                const matchNorma = (selectedNorma === '0') ? true : (norma === selectedNorma);
                const show = matchRisk && matchNorma;

                row.classList.toggle('hidden', !show);
                setRowInputsDisabled(row, !show);
                if (show) shown++;
            });

            // Ocultar tabla y título si no quedan filas visibles
            document.querySelectorAll('.tarea-block').forEach(block => {
                const hasVisible = block.querySelector('tr.re-row:not(.hidden)') !== null;
                const table = block.querySelector('table.tarea-table');
                const title = block.querySelector('h2.tarea-title');
                if (table) table.classList.toggle('hidden', !hasVisible);
                if (title) title.classList.toggle('hidden', !hasVisible);
                block.classList.toggle('hidden', !hasVisible);
            });

            const selRisks = riskChecks.filter(c => c.checked).map(c => c.value).join(', ') || 'ninguno';
            const selNormaTxt = normaSelect.options[normaSelect.selectedIndex].text;
            status.textContent = `Mostrando ${shown} de ${total} preguntas (Riesgos: ${selRisks} · Norma: ${selNormaTxt}).`;

            rebuildVisibleIndex();
        }

        // --- Limpieza y validación ---
        function limpiarErrores() {
            document.querySelectorAll('.error-msg').forEach(el => el.remove());
            document.querySelectorAll('tr.re-row.error').forEach(el => el.classList.remove('error'));
        }

        form.addEventListener('submit', function (e) {
            limpiarErrores();

            // Solo filas visibles (no .hidden) cuentan para la validación
            const filasVisibles = Array.from(document.querySelectorAll('tr.re-row:not(.hidden)'));
            let primeraFaltante = null;

            filasVisibles.forEach(row => {
                const anyRadio = row.querySelector('input[type="radio"]');
                if (!anyRadio) return;
                const name = anyRadio.name;
                const checked = row.querySelector(`input[type="radio"][name="${CSS.escape(name)}"]:checked`);
                if (!checked) {
                    if (!primeraFaltante) primeraFaltante = row;
                    row.classList.add('error');
                    const celdaPregunta = row.querySelector('td:first-child');
                    const msg = document.createElement('span');
                    msg.className = 'error-msg';
                    msg.textContent = 'Debe responder la pregunta';
                    celdaPregunta.appendChild(msg);
                }
            });

            if (primeraFaltante) {
                e.preventDefault();

                // mostrar el bloque que contiene la fila faltante
                const bloque = primeraFaltante.closest('.tarea-block');
                const idxBloque = Array.from(blocks).indexOf(bloque);
                blocks.forEach((b, i) => b.classList.toggle('hidden', i !== idxBloque));

                const pos = visibleIdx.indexOf(idxBloque);
                if (pos !== -1) { currentPos = pos; updateNavButtons(); }

                // scroll y foco en el primer radio de esa fila
                primeraFaltante.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const firstRadio = primeraFaltante.querySelector('input[type="radio"]');
                if (firstRadio) firstRadio.focus();
            }
        });

        // Si el usuario responde, limpiar error de esa fila
        document.addEventListener('change', function (ev) {
            const rb = ev.target.closest('input[type="radio"]');
            if (!rb) return;
            const row = rb.closest('tr.re-row');
            if (!row) return;

            const name = rb.name;
            const checked = row.querySelector(`input[type="radio"][name="${CSS.escape(name)}"]:checked`);
            if (checked) {
                row.classList.remove('error');
                const msg = row.querySelector('.error-msg');
                if (msg) msg.remove();
            }
        });

        // eventos de filtros
        riskChecks.forEach(c => c.addEventListener('change', () => { limpiarErrores(); applyFilter(); }));
        normaSelect.addEventListener('change', () => { limpiarErrores(); applyFilter(); });
        resetBtn.addEventListener('click', () => {
            limpiarErrores();
            riskChecks.forEach(c => c.checked = true);
            normaSelect.value = '0';
            applyFilter();
        });

        // navegación
        prevBtn.addEventListener('click', () => {
            if (currentPos > 0) {
                currentPos--;
                showBlockByPos(currentPos);
            }
        });
        nextBtn.addEventListener('click', () => {
            if (currentPos >= 0 && currentPos < visibleIdx.length - 1) {
                currentPos++;
                showBlockByPos(currentPos);
            }
        });

        // init
        applyFilter(); // calcula visibles y muestra el primero
    })();

    // --- Semáforo superior (tu lógica existente) ---
    const radioButtons = document.querySelectorAll('input[type="radio"]');
    const circle = document.getElementById('semaphore');
    radioButtons.forEach(radioButton => {
        radioButton.addEventListener('change', function () {
            if (this.checked) {
                let count = 0; // total answered (S/N)
                let evalYes = 0; // "S" answers

                const groups = new Set(Array.from(radioButtons).map(rb => rb.name));
                groups.forEach(groupName => {
                    const checked = document.querySelector(`input[name="${groupName}"]:checked`);
                    if (checked) {
                        if (checked.value === "S" || checked.value === "N") {
                            count++;
                        }
                        if (checked.value === "S") {
                            evalYes++;
                        }
                    }
                });

                const percentYes = (count > 0) ? (evalYes * 100 / count) : 0;
                let hue = percentYes * 120 / 100;
                if (count === 0) {
                    semaphore.style.backgroundColor = `hsl(${0}, 0%, 100%)`;
                } else {
                    semaphore.style.backgroundColor = `hsl(${hue}, 100%, 50%)`;
                }
            }
        });
    });
</script>

</body>
</html>
