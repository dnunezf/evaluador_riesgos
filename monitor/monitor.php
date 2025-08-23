<?php
require_once __DIR__ . '/modelo_monitor.php';
$cfg = monitor_get_config();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crit = isset($_POST['critico_pct']) ? (float)$_POST['critico_pct'] : $cfg['critico_pct'];
    $del  = isset($_POST['delay_seg'])   ? (int)$_POST['delay_seg']     : $cfg['delay_seg'];
    $hab  = isset($_POST['habilitado'])  ? 1 : 0;
    monitor_update_config($crit, $del, $hab);
    $cfg = monitor_get_config();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Monitor de Consumo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="../css/styles.css" />
    <style>
        .gauge {
            font-size: 2rem;
            font-weight: 800;
        }
    </style>
</head>

<body>
    <main class="page">
        <h1>Monitor de consumo (buffer InnoDB)</h1>

        <div class="card">
            <form method="post" class="controls" id="cfgForm">
                <div class="group">
                    <label><strong>Punto crítico (%)</strong>
                        <input type="number" name="critico_pct" min="1" max="100" step="1" value="<?= htmlspecialchars($cfg['critico_pct']); ?>">
                    </label>
                </div>
                <div class="group">
                    <label><strong>Delay (s)</strong>
                        <input type="number" name="delay_seg" min="1" max="3600" step="1" value="<?= htmlspecialchars($cfg['delay_seg']); ?>">
                    </label>
                </div>
                <div class="group">
                    <label><input type="checkbox" name="habilitado" <?= $cfg['habilitado'] ? 'checked' : ''; ?>> Habilitado</label>
                </div>
                <button type="submit">Guardar</button>
            </form>
        </div>

        <div class="card">
            <div class="legend">Consumo actual</div>
            <div id="valor" class="gauge">-- %</div>
            <div class="progress">
                <div class="gradient"></div>
                <div id="mk" class="marker" style="left:1%"></div>
            </div>
            <div id="estado" class="leyenda gris">En espera</div>
        </div>

        <div class="card">
            <a class="btn-volver" href="monitor_eventos.php">Ver eventos críticos</a>
        </div>
    </main>

    <script>
        let delay = <?= (int)$cfg['delay_seg']; ?> * 1000;
        let timer = null;

        async function tick() {
            const r = await fetch('api/monitor_tick.php', {
                cache: 'no-store'
            });
            const j = await r.json();

            const pct = j.consumo_pct ?? 0;
            document.getElementById('valor').textContent = pct.toFixed(2) + ' %';
            const left = Math.max(0.5, Math.min(97, pct));
            document.getElementById('mk').style.left = left + '%';

            const estado = document.getElementById('estado');
            if (j.critico) {
                estado.className = 'leyenda rojo';
                estado.textContent = 'Crítico (≥ ' + j.umbral_pct + '%). Evento registrado.';
            } else {
                estado.className = 'leyenda verde';
                estado.textContent = 'OK (< ' + j.umbral_pct + '%)';
            }

            // actualiza delay si cambia en el server
            const newDelay = (j.delay_seg ?? 5) * 1000;
            if (newDelay !== delay) {
                delay = newDelay;
                restart();
            }
        }

        function restart() {
            if (timer) clearInterval(timer);
            timer = setInterval(tick, delay);
        }

        // inicio
        tick();
        restart();
    </script>
</body>

</html>