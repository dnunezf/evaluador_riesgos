<?php
session_start();
if (!isset($_SESSION['username'], $_SESSION['password'])) { header("Location: ./index.php"); exit; }
$pageTitle = "Monitores por Cliente";
include '../fragments/index/header.php';
?>
<!-- Chart.js + adapter de tiempo -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3"></script>

<!-- ✅ Volver a la página que lista DBLINKs online/offline -->
<div style="display:flex; gap:8px; align-items:center; margin-bottom:16px;">
    <a href="./monitor.php" class="btn-volver">← Volver a DB Links</a>
</div>

<main class="page">
    <h2 style="text-align:center;">Monitores de Consumo (SGA) por Cliente</h2>

    <!-- Monitores en columna (uno debajo de otro) -->
    <div id="cards" class="stack" style="display:flex; flex-direction:column; gap:16px; margin-top:16px;"></div>

    <h3 style="margin-top:32px;">Alertas recientes (todos los clientes)</h3>
    <table id="tblAlerts">
        <thead>
        <tr>
            <th>Fecha</th><th>Hora</th><th>Cliente</th><th>Consumo (%)</th><th>SQL (texto)</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
</main>

<script>
    // Base hacia /model/monitor/api desde /views/monitor
    const API = '../../model/monitor/api/';

    const fmtPct   = n => Number.isFinite(+n) ? (+n).toFixed(2) : '0.00';
    const fmtBytes = n => (!Number.isFinite(+n) || +n <= 0) ? '-' : new Intl.NumberFormat().format(+n) + ' B';

    async function fetchJSON(url){
        const r = await fetch(url, {cache:'no-store'});
        if(!r.ok) throw new Error('HTTP '+r.status);
        return r.json();
    }

    async function loadConfigs(){
        // Espera: { ok:1, items:[{dblink, cliente, alias, umbral, reload, enabled}] }
        const j = await fetchJSON(API + 'config_get.php');
        if(!j.ok) return [];
        return j.items.filter(it => it.enabled); // solo habilitados
    }

    function mkChart(canvas){
        const ctx = canvas.getContext('2d');
        return new Chart(ctx, {
            type: 'line',
            data: {
                datasets: [
                    // línea de consumo (con picos)
                    { label:'% Consumo', data: [], borderWidth:2, fill:false, pointRadius:2, tension:0, cubicInterpolationMode:'default' },
                    // línea punteada del umbral
                    { label:'Umbral',    data: [], borderDash:[6,6], borderWidth:1, pointRadius:0, fill:false, tension:0 }
                ]
            },
            options: {
                responsive:true, animation:{duration:0}, interaction:{mode:'nearest',intersect:false},
                scales: {
                    x: { type:'time', time:{unit:'second', displayFormats:{second:'HH:mm:ss'}},
                        min: Date.now()-120000, max: Date.now() },
                    y: { min:0, max:100, title:{display:true,text:'%'} }
                },
                plugins: { legend:{ display:false } }
            }
        });
    }

    function pushAndSlide(chart, x, y, umbral){
        chart.data.datasets[0].data.push({x, y});
        chart.data.datasets[1].data.push({x, y: umbral});

        const minTime = Date.now() - 120000;
        for (const ds of chart.data.datasets){
            while (ds.data.length) {
                const v = ds.data[0].x instanceof Date ? ds.data[0].x.valueOf() : +ds.data[0].x;
                if (v >= minTime) break;
                ds.data.shift();
            }
        }
        chart.options.scales.x.min = minTime;
        chart.options.scales.x.max = Date.now();
        chart.update('none');
    }

    function renderCard({dblink, alias, cliente, umbral, reload}){
        const id = 'mon_' + cliente.replace(/\W+/g,'_'); // id único por cliente

        const div = document.createElement('div');
        div.className = 'card';
        div.innerHTML = `
      <h3 style="margin:0 0 6px 0;">
        ${alias || cliente}
        <small style="color:#888">(${dblink})</small>
        <!-- enlace útil directo a la vista de DBLINKs -->
      </h3>
      <div style="display:flex; gap:12px; align-items:flex-start;">
        <div style="flex:2; min-width:360px;">
          <canvas id="${id}_chart" height="220"></canvas>
        </div>
        <div style="flex:1; min-width:240px;">
          <div style="font-size:12px; color:#666; margin-bottom:8px;">Información</div>
          <div id="${id}_info" class="mon-info" style="font-size:13px; line-height:1.5;">
            Umbral: <b id="${id}_umbral">${Number.isFinite(+umbral)?umbral:30}</b>%<br>
            Reload: <b id="${id}_reload">${Number.isFinite(+reload)?reload:1}</b> s<br>
            Máximo: <b id="${id}_max">-</b><br>
            Usado: <b id="${id}_used">-</b><br>
            Actual: <b id="${id}_actual">0.00%</b><br>
            Estado: <b id="${id}_estado">...</b>
          </div>
          <hr>
          <form id="${id}_form" style="font-size:12px;">
            <label>Umbral (%)</label>
            <input type="number" step="1" min="1" max="100" value="${Number.isFinite(+umbral)?umbral:30}" name="umbral" style="width:100%; margin-bottom:6px;">
            <label>Reload (s)</label>
            <input type="number" step="1" min="1" max="3600" value="${Number.isFinite(+reload)?reload:1}" name="reload" style="width:100%; margin-bottom:6px;">
            <label>Alias</label>
            <input type="text" value="${alias||''}" name="alias" style="width:100%; margin-bottom:6px;">
            <button type="submit" class="btn-volver" style="width:100%;">Guardar</button>
          </form>
          <div id="${id}_err" style="margin-top:8px; font-size:12px; color:#b91c1c;"></div>
        </div>
      </div>
    `;
        document.getElementById('cards').appendChild(div);

        // Chart + HUD refs
        const chart = mkChart(document.getElementById(`${id}_chart`));
        const els = {
            umbral: document.getElementById(`${id}_umbral`),
            reload: document.getElementById(`${id}_reload`),
            max:    document.getElementById(`${id}_max`),
            used:   document.getElementById(`${id}_used`),
            actual: document.getElementById(`${id}_actual`),
            estado: document.getElementById(`${id}_estado`),
            err:    document.getElementById(`${id}_err`),
        };

        // Semilla: 5 puntos en 0 para ver ejes vivos
        const seedUm = Number(els.umbral.textContent);
        for (let i=5; i>0; i--){
            const t = new Date(Date.now() - i*1000);
            chart.data.datasets[0].data.push({x:t, y:0});
            chart.data.datasets[1].data.push({x:t, y:seedUm});
        }
        chart.update('none');

        // Control de avance
        let lastTsMs = 0;
        let lastY = 0;

        async function tickOnce(){
            const um = Number(els.umbral.textContent);
            els.err.textContent = '';
            try{
                // 1) Live remoto por DBLINK
                const j = await fetchJSON(`${API}monitor_tick_remote.php?cliente=${encodeURIComponent(cliente)}`);
                if (!j || j.ok === 0 || j.ok === false) throw new Error(j && (j.detail||j.error) ? (j.detail||j.error) : 'tick remoto inválido');

                const y = Number.isFinite(+j.consumo_pct) ? +j.consumo_pct : lastY;

                let tsMs = Date.now();
                if (j.ts) {
                    const tryMs = new Date(j.ts).valueOf();
                    if (Number.isFinite(tryMs)) tsMs = tryMs;
                }
                if (tsMs <= lastTsMs) tsMs = Date.now();
                lastTsMs = tsMs;

                pushAndSlide(chart, new Date(tsMs), y, um);

                // HUD
                els.actual.textContent = fmtPct(y) + '%';
                els.estado.textContent = (y >= um) ? 'CRÍTICO' : 'OK';
                els.estado.style.color = (y >= um) ? '#d00' : '#090';
                if ('max_bytes' in j) els.max.textContent  = fmtBytes(j.max_bytes);
                if ('used_bytes' in j) els.used.textContent = fmtBytes(j.used_bytes);

                lastY = y;
            }catch(e){
                // 2) Fallback local
                try{
                    const j2 = await fetchJSON(`${API}monitor_tick.php?cliente=${encodeURIComponent(cliente)}`);
                    if (j2 && j2.ok){
                        const y2  = Number.isFinite(+j2.consumo_pct) ? +j2.consumo_pct : lastY;
                        const ts2 = j2.ts ? new Date(j2.ts) : new Date();
                        pushAndSlide(chart, ts2, y2, Number(els.umbral.textContent));
                        els.actual.textContent = fmtPct(y2) + '%';
                        els.estado.textContent = (y2 >= Number(els.umbral.textContent)) ? 'CRÍTICO' : 'OK';
                        els.estado.style.color = (y2 >= Number(els.umbral.textContent)) ? '#d00' : '#090';
                        lastY = y2;
                        return;
                    }
                    throw new Error('fallback local inválido');
                }catch(e2){
                    // 3) Último recurso: mantener avance con último valor
                    pushAndSlide(chart, new Date(), lastY, Number(els.umbral.textContent));
                    els.estado.textContent = 'ERROR';
                    els.estado.style.color = '#888';
                    els.err.textContent = (e && e.message) ? e.message : 'tick error';
                }
            }
        }

        // Timer por cliente
        let intervalId;
        function startTimer(){
            if (intervalId) clearInterval(intervalId);
            const parsed = parseInt(els.reload.textContent, 10);
            const reloadSec = (Number.isFinite(parsed) && parsed > 0) ? parsed : 1;
            intervalId = setInterval(tickOnce, reloadSec * 1000);
            tickOnce();
        }
        startTimer();

        // Guardar configuración
        document.getElementById(`${id}_form`).addEventListener('submit', async (ev)=>{
            ev.preventDefault();
            const fd = new FormData(ev.target);
            const newUmbral = Number(fd.get('umbral'));
            const newReload = parseInt(fd.get('reload'), 10);
            const newAlias  = String(fd.get('alias')||'');

            const res = await fetch(API + 'config_set.php', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ dblink, cliente, umbral:newUmbral, reload:newReload, enabled:true, alias:newAlias })
            });
            const j = await res.json();
            if (!j.ok) { alert('No se pudo guardar: ' + (j.detail || j.error || '')); return; }

            els.umbral.textContent = String(newUmbral);
            els.reload.textContent = String((Number.isFinite(+newReload) && newReload>0) ? newReload : 1);
            startTimer();
        });
    }

    async function loadAlerts(){
        const tbody = document.querySelector('#tblAlerts tbody');
        tbody.innerHTML = '';
        try{
            const js = await fetchJSON(API + 'alerts_list.php?days=7');
            (js.items||[]).forEach(a=>{
                const sqlPreview = (a.sql || '').replace(/\s+/g,' ').slice(0,120);
                const tr = document.createElement('tr');
                tr.innerHTML =
                    `<td>${a.dia}</td>
                     <td>${a.hora}</td>
                     <td>${a.cliente||''}</td>
                     <td style="text-align:right;">${fmtPct(a.consumo)}</td>
                     <td title="${(a.sql||'').replace(/"/g,'&quot;')}">${sqlPreview}${(a.sql && a.sql.length>120?'…':'')}</td>`;
                tbody.appendChild(tr);
            });
        }catch(e){ /* no-op */ }
    }

    (async ()=>{
        const cfgs = await loadConfigs();
        if (cfgs.length === 0){
            document.getElementById('cards').innerHTML = "<div class='card'>No hay clientes habilitados en MON_CONFIG_CLIENTE.</div>";
        } else {
            cfgs.forEach(c => renderCard(c));
        }
        await loadAlerts();
        setInterval(loadAlerts, 30000);
    })();
</script>

<?php include '../fragments/index/footer.php'; ?>
