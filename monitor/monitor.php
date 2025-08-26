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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .legend {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .leyenda {
            margin-top: 0.5rem;
            font-weight: bold;
        }
        .gris { color: gray; }
        .rojo { color: red; }
        .verde { color: green; }
        canvas { width: 100% !important; height: 200px !important; }
    </style>
</head>

<body>
    <main class="page">
        <h1>Monitor de consumo (buffer InnoDB)</h1>

        <!-- Configuration Form -->
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

        <!-- Live Memory Consumption Chart -->
        <div class="card">
            <div class="legend">Consumo de memoria en tiempo real</div>
            <canvas id="memoryChart"></canvas>
            <div id="estado" class="leyenda gris">En espera</div>
        </div>

        <!-- Link to events -->
        <div class="card">
            <a class="btn-volver" href="monitor_eventos.php">Ver eventos críticos</a>
        </div>
    </main>

    <script>
        let delay = <?= (int)$cfg['delay_seg']; ?> * 1000;
        let timer = null;

        // Setup Chart.js
        const ctx = document.getElementById('memoryChart').getContext('2d');
        const data = {
            labels: [], // timestamps
            datasets: [{
                label: 'Consumo memoria (%)',
                data: [],
                borderColor: 'red',
                backgroundColor: 'rgba(255,0,0,0.1)',
                fill: true,
                tension: 0.2
            }]
        };

        const config = {
            type: 'line',
            data: data,
            options: {
                animation: false,
                responsive: true,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            parser: 'HH:mm:ss',
                            unit: 'second',
                            displayFormats: { second: 'HH:mm:ss' }
                        },
                        title: { display: true, text: 'Hora' }
                    },
                    y: {
                        min: 0,
                        max: 100,
                        title: { display: true, text: 'Memoria (%)' }
                    }
                }
            }
        };

        const memoryChart = new Chart(ctx, config);

        // Add new data point
        function addDataPoint(pct) {
            const now = new Date();
            const label = now.toLocaleTimeString();
            data.labels.push(label);
            data.datasets[0].data.push(pct);

            // Keep last 50 points
            if (data.labels.length > 50) {
                data.labels.shift();
                data.datasets[0].data.shift();
            }

            memoryChart.update();
        }

        async function tick() {
            try {
                const r = await fetch('api/monitor_tick.php', { cache: 'no-store' });
                const j = await r.json();

                const pct = j.consumo_pct ?? 0;
                addDataPoint(pct);

                const estado = document.getElementById('estado');
                if (j.critico) {
                    estado.className = 'leyenda rojo';
                    estado.textContent = 'Crítico (≥ ' + j.umbral_pct + '%). Evento registrado.';
                } else {
                    estado.className = 'leyenda verde';
                    estado.textContent = 'OK (< ' + j.umbral_pct + '%)';
                }

                // Update delay if changed on server
                const newDelay = (j.delay_seg ?? 5) * 1000;
                if (newDelay !== delay) {
                    delay = newDelay;
                    restart();
                }
            } catch (e) {
                console.error('Error fetching data:', e);
            }
        }

        function restart() {
            if (timer) clearInterval(timer);
            timer = setInterval(tick, delay);
        }

        // Start monitoring
        tick();
        restart();
    </script>
</body>

</html>