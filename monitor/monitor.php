<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title>Monitor — Consumo del Buffer (tiempo real, pantalla fija)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <!-- Adapter de tiempo para ejes con Date -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3"></script>
    <style>
        :root {
            --w: 900px;
            --h: 320px;
            --border: #e5e7eb;
            --fg: #0f172a;
            --ok: #16a34a;
            --crit: #ef4444;
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            padding: 16px;
            font: 500 16px/1.5 system-ui, Segoe UI, Roboto, Arial;
            color: var(--fg);
            background: #fff;
        }

        h2 {
            margin: 0 0 12px 0;
        }

        .card {
            width: var(--w);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 6px 22px rgba(0, 0, 0, .06);
            background: #fff;
        }

        .hud {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 6px 10px;
            font-weight: 600;
            background: #fff;
            position: relative;
            z-index: 2;
        }

        .hud .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--ok);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .15);
        }

        .hud.crit .dot {
            background: var(--crit);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .15);
        }

        .hud .sep {
            color: #64748b;
        }

        .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        #estado {
            font-weight: 600
        }

        #canvas {
            width: var(--w);
            height: var(--h);
            display: block;
        }

        /* alto/ancho fijos: la pantalla no “salta” */
    </style>
</head>

<body>
    <h2>Monitor</h2>
    <div style="margin:8px 0">
        Cliente: <select id="selCliente"></select>
    </div>
    <div class="card">
        <div class="row">
            <div class="hud" id="hud">
                <span class="dot"></span>
                <span id="hudTime">--:--:--</span>
                <span class="sep">·</span>
                <span id="hudPct">0.0%</span>
            </div>
            <div id="estado">Cargando…</div>
        </div>
        <canvas id="canvas"></canvas>
    </div>

    <script>
        let CLIENTES = [];
        async function loadClients() {
            const r = await fetch('api/clients.php', {
                cache: 'no-store'
            });
            const j = await r.json();
            CLIENTES = j.clients || [];
            const sel = document.getElementById('selCliente');
            sel.innerHTML = CLIENTES.map(c => `<option value="${c.name}">${c.name}</option>`).join('');
        }

        // ===== Ajustes =====
        const endpoint = 'api/monitor_tick_remote.php'; // o 'api/monitor_tick.php' si ya lees Oracle
        const endpointLive = 'api/monitor_tick_remote.php';
        const endpointLocal = 'api/monitor_tick.php'; // ahora acepta ?cliente=
        const UMBRAL = 85;
        const TICK_MS = 1000; // 1s para probar; sube a 5000 en real
        const WINDOW_SEC = 120; // ventana visible de 120 s

        // ===== Refs UI =====
        const hud = document.getElementById('hud');
        const hudTime = document.getElementById('hudTime');
        const hudPct = document.getElementById('hudPct');
        const estadoEl = document.getElementById('estado');

        // ===== Chart (eje de TIEMPO) =====
        let chart;
        (function initChart() {
            const ctx = document.getElementById('canvas').getContext('2d');
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [{
                            label: '% Consumo',
                            data: [], // {x: Date, y: number}
                            borderColor: 'rgba(14,165,233,1)',
                            backgroundColor: 'rgba(14,165,233,0.18)',
                            fill: true,
                            borderWidth: 2,
                            pointRadius: 0,
                            cubicInterpolationMode: 'monotone' // evita overshoot
                        },
                        {
                            label: `Umbral ${UMBRAL}%`,
                            data: [], // línea horizontal a UMBRAL
                            borderColor: 'rgba(100,116,139,.9)',
                            borderDash: [6, 6],
                            borderWidth: 1,
                            pointRadius: 0
                        }
                    ]
                },
                options: {
                    responsive: false, // tamaño fijo → no “salta” la pantalla
                    animation: false,
                    interaction: {
                        mode: 'nearest',
                        intersect: false
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'second',
                                displayFormats: {
                                    second: 'HH:mm:ss'
                                }
                            },
                            min: Date.now() - WINDOW_SEC * 1000,
                            max: Date.now(),
                            title: {
                                display: true,
                                text: 'Hora'
                            }
                        },
                        y: {
                            min: 0,
                            max: 100,
                            title: {
                                display: true,
                                text: '% Consumo'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        })();

        function updateWindow() {
            const now = Date.now();
            chart.options.scales.x.min = now - WINDOW_SEC * 1000;
            chart.options.scales.x.max = now;
        }

        function addPoint(tsISO, pct) {
            const x = tsISO ? new Date(tsISO) : new Date();
            const y = Number.isFinite(+pct) ? +pct : 0;

            // Serie principal
            chart.data.datasets[0].data.push({
                x,
                y
            });
            // Línea de umbral (mismo x, y=UMBRAL) para que se dibuje a lo largo del tiempo
            chart.data.datasets[1].data.push({
                x,
                y: UMBRAL
            });

            // Podar puntos fuera de la ventana
            const minTime = chart.options.scales.x.min;
            for (const ds of chart.data.datasets) {
                while (ds.data.length && ds.data[0].x < minTime) ds.data.shift();
            }

            // Ventana deslizante (solo cambian min/max del eje, el canvas no cambia de tamaño)
            updateWindow();
            chart.update('none');

            // HUD con el MISMO valor
            hudTime.textContent = x.toLocaleTimeString();
            hudPct.textContent = `${y.toFixed(1)}%`;
            if (y >= UMBRAL) {
                hud.classList.add('crit');
                estadoEl.textContent = `CRÍTICO (≥ ${UMBRAL}%).`;
                estadoEl.style.color = '#ef4444';
            } else {
                hud.classList.remove('crit');
                estadoEl.textContent = `OK (< ${UMBRAL}%).`;
                estadoEl.style.color = '#16a34a';
            }
        }

        async function tick() {
            try {
                const c = document.getElementById('selCliente').value || 'ClienteRemoto';
                // usa remoto si el cliente tiene dblink, si no usa local
                const meta = CLIENTES.find(x => x.name === c);
                const url = (meta && meta.dblink) ? `${endpointLive}?cliente=${encodeURIComponent(c)}` :
                    `${endpointLocal}?cliente=${encodeURIComponent(c)}`;
                const r = await fetch(url, {
                    cache: 'no-store'
                });
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const j = await r.json();
                addPoint(j.ts, j.consumo_pct);
            } catch (e) {
                estadoEl.textContent = 'Sin datos';
                estadoEl.style.color = '#64748b';
                console.error(e);
            }
        }


        // Semilla inicial (opcional): rellena 2 s para que no se vea vacío al inicio
        (function seed() {
            const now = Date.now();
            for (let i = 5; i >= 1; i--) {
                const t = new Date(now - i * 1000);
                chart.data.datasets[0].data.push({
                    x: t,
                    y: 0
                });
                chart.data.datasets[1].data.push({
                    x: t,
                    y: UMBRAL
                });
            }
            chart.update('none');
        })();

        await loadClients();
        tick();
        setInterval(tick, TICK_MS);
    </script>
</body>

</html>