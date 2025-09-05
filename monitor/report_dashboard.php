<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Reportes — Consumo del Buffer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        body{margin:0;padding:16px;font:500 16px/1.5 system-ui,Segoe UI,Roboto,Arial;color:#0f172a}
        .wrap{max-width:1000px;margin:auto}
        .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .card{border:1px solid #e5e7eb;border-radius:12px;padding:12px;box-shadow:0 6px 22px rgba(0,0,0,.06)}
        h2{margin:0 0 8px 0}
        canvas{width:100%;height:320px}
        table{width:100%;border-collapse:collapse;font-size:14px}
        th,td{border-bottom:1px solid #e5e7eb;padding:6px 8px;text-align:left}
    </style>
</head>
<body>
<div class="wrap">
    <h2>Reportes — Consumo del Buffer</h2>
    <div style="margin:10px 0">
        Cliente: <input id="cliente" value="ClienteRemoto" />
        Últimos días: <input id="dias" type="number" value="7" min="1" max="60" />
        <button id="btn">Actualizar</button>
    </div>

    <div class="row">
        <div class="card"><h3>Promedio por día de semana</h3><canvas id="cDow"></canvas></div>
        <div class="card"><h3>Promedio por hora</h3><canvas id="cHour"></canvas></div>
    </div>

    <div class="row" style="margin-top:16px">
        <div class="card"><h3>Tendencia (máx diario)</h3><canvas id="cTrend"></canvas></div>
        <div class="card">
            <h3>Últimas alertas</h3>
            <table id="tbl"><thead><tr><th>Día</th><th>Hora</th><th>Consumo %</th></tr></thead><tbody></tbody></table>
        </div>
    </div>
</div>

<script>
    const ctxDow  = document.getElementById('cDow').getContext('2d');
    const ctxHour = document.getElementById('cHour').getContext('2d');
    const ctxTr   = document.getElementById('cTrend').getContext('2d');
    let chDow, chHour, chTr;

    function mkChart(ctx, labels, data, label){
        return new Chart(ctx, {
            type: 'line',
            data: { labels, datasets: [{ label, data, fill:false, borderWidth:2, pointRadius:2 }] },
            options: { responsive:true, animation:false, scales:{ y:{ min:0, max:100 } }, plugins:{legend:{display:false}}}
        });
    }

    async function load(){
        const c = document.getElementById('cliente').value.trim() || 'ClienteRemoto';
        const d = Math.max(1, Math.min(60, +document.getElementById('dias').value||7));
        const url = `api/report_summary.php?cliente=${encodeURIComponent(c)}&dias=${d}`;
        const r = await fetch(url, {cache:'no-store'}); const j = await r.json();

        // DOW
        const dowNames = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        const by_dow = Array(7).fill(0);
        j.by_dow.forEach(o => by_dow[o.dow] = o.avg_pct);
        if (chDow) chDow.destroy();
        chDow = mkChart(ctxDow, dowNames, by_dow, 'Promedio %');

        // HOUR
        const labelsH = Array.from({length:24}, (_,i)=>i.toString().padStart(2,'0'));
        const by_hour = Array(24).fill(0);
        j.by_hour.forEach(o => by_hour[o.hh] = o.avg_pct);
        if (chHour) chHour.destroy();
        chHour = mkChart(ctxHour, labelsH, by_hour, 'Promedio %');

        // Trend
        const labelsT = j.trend.map(x=>x.day);
        const dataT   = j.trend.map(x=>x.max_pct);
        if (chTr) chTr.destroy();
        chTr = mkChart(ctxTr, labelsT, dataT, 'Máx % diario');

        // Tabla de alertas
        const tb = document.querySelector('#tbl tbody'); tb.innerHTML = '';
        j.alerts.forEach(a=>{
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${a.dia}</td><td>${a.hora}</td><td>${a.consumo.toFixed(2)}</td>`;
            tb.appendChild(tr);
        });
    }

    document.getElementById('btn').addEventListener('click', load);
    load();
</script>
</body>
</html>
