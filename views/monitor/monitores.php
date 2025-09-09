<?php
session_start();
if (!isset($_SESSION['username'], $_SESSION['password'])) {
    header("Location: ./index.php");
    exit;
}
$pageTitle = "Monitores por Cliente";
include '../fragments/index/header.php';
?>
<!-- Chart.js + adapter de tiempo -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3"></script>

<a href="./monitor.php" class="btn-volver" style="margin-bottom:16px;">← Volver a DB Links</a>

<main class="page">
    <h2 style="text-align:center;">Monitores de Consumo (SGA) por Cliente</h2>

    <!-- Monitores en columna -->
    <div id="cards" class="stack"
         style="display:flex; flex-direction:column; gap:16px; margin-top:16px;"></div>

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
    const API = '../../model/monitor/api/';

    const fmtPct   = n => Number.isFinite(+n) ? (+n).toFixed(2) : '0.00';
    const fmtBytes = n => (!Number.isFinite(+n) || +n <= 0) ? '-' : new Intl.NumberFormat().format(+n) + ' B';

    // anti-spam de alertas: 60s por dblink
    const lastAlertAt = new Map();

    async function loadConfigs(){
        const r = await fetch(API + 'config_get.php', {cache:'no-store'});
        const j = await r.json();
        if(!j.ok) return [];
        return j.items.filter(it => it.enabled);
    }

    function renderCard({dblink, alias, cliente, umbral, reload}){
        const id = 'mon_' + dblink;

        const div = document.createElement('div');
        div.className = 'card';
        div.innerHTML = `
    <h3 style="margin:0 0 6px 0;">${alias || cliente}
      <small style="color:#888">(${dblink})</small></h3>
    <div style="display:flex; gap:12px; align-items:flex-start;">
      <div style="flex:2; min-width:360px;">
        <canvas id="${id}_chart" height="220"></canvas>
      </div>
      <div style="flex:1; min-width:260px;">
        <div style="font-size:12px; color:#666; margin-bottom:8px;">Info</div>
        <div id="${id}_info" class="mon-info" style="font-size:13px; line-height:1.5;">
          Umbral: <b id="${id}_umbral">${umbral}</b>%<br>
          Reload: <b id="${id}_reload">${reload}</b> s<br>
          Máximo: <b id="${id}_max">-</b><br>
          Usado: <b id="${id}_used">-</b><br>
          Actual: <b id="${id}_actual">0.00%</b><br>
          Estado: <b id="${id}_estado">...</b><br>
          <hr style="margin:8px 0;">
          DB: <b id="${id}_db">-</b><br>
          Instancia: <b id="${id}_inst">-</b><br>
          Host: <b id="${id}_host">-</b><br>
          Versión: <b id="${id}_ver">-</b><br>
          Plataforma: <b id="${id}_plat">-</b><br>
          SGA_TARGET: <b id="${id}_sga">-</b><br>
          Block size: <b id="${id}_blk">-</b>
        </div>
        <hr>
        <form id="${id}_form" style="font-size:12px;">
          <label>Umbral (%)</label>
          <input type="number" step="1" min="1" max="100" value="${umbral}" name="umbral" style="width:100%; margin-bottom:6px;">
          <label>Reload (s)</label>
          <input type="number" step="1" min="1" max="3600" value="${reload}" name="reload" style="width:100%; margin-bottom:6px;">
          <label>Alias</label>
          <input type="text" value="${alias||''}" name="alias" style="width:100%; margin-bottom:6px;">
          <button type="submit" class="btn-volver" style="width:100%;">Guardar</button>
        </form>
      </div>
    </div>
  `;
        document.getElementById('cards').appendChild(div);

        // Chart
        const ctx = document.getElementById(`${id}_chart`).getContext('2d');
        const data = {
            datasets: [
                { label:'Consumo %', data: [], borderWidth:2, fill:true, pointRadius:0, cubicInterpolationMode:'monotone' },
                { label:'Umbral',    data: [], borderDash:[6,6], borderWidth:1, pointRadius:0 }
            ]
        };
        const chart = new Chart(ctx, {
            type: 'line',
            data,
            options: {
                responsive:true, animation:false, interaction:{mode:'nearest',intersect:false},
                scales: {
                    x: { type:'time', time:{unit:'second', displayFormats:{second:'HH:mm:ss'}},
                        min: Date.now()-120000, max: Date.now() },
                    y: { min:0, max:100, title:{display:true,text:'%'} }
                },
                plugins: { legend:{ display:false } }
            }
        });

        // Semilla para ver ejes (0) y línea de umbral
        const seedUm = Number(umbral);
        for (let i=10;i>0;i--){
            const t = new Date(Date.now() - i*1000);
            data.datasets[0].data.push({x:t, y:0});
            data.datasets[1].data.push({x:t, y:seedUm});
        }
        chart.update('none');

        const els = {
            umbral: document.getElementById(`${id}_umbral`),
            reload: document.getElementById(`${id}_reload`),
            max:    document.getElementById(`${id}_max`),
            used:   document.getElementById(`${id}_used`),
            actual: document.getElementById(`${id}_actual`),
            estado: document.getElementById(`${id}_estado`),
            db:     document.getElementById(`${id}_db`),
            inst:   document.getElementById(`${id}_inst`),
            host:   document.getElementById(`${id}_host`),
            ver:    document.getElementById(`${id}_ver`),
            plat:   document.getElementById(`${id}_plat`),
            sga:    document.getElementById(`${id}_sga`),
            blk:    document.getElementById(`${id}_blk`),
        };

        async function tick(){
            try{
                const um     = Number(els.umbral.textContent);
                const url    = `${API}monitor_tick_remote.php?dblink=${encodeURIComponent(dblink)}&umbral=${encodeURIComponent(um)}`;
                const r      = await fetch(url, {cache:'no-store'});
                const j      = await r.json();
                if (!j || j.ok === 0 || j.ok === false) throw new Error(j.detail||j.error||'tick');

                const ts     = j.ts ? new Date(j.ts) : new Date();
                const y      = Number(j.consumo_pct);
                const umNow  = Number(els.umbral.textContent);

                data.datasets[0].data.push({x:ts, y: Number.isFinite(y) ? y : 0});
                data.datasets[1].data.push({x:ts, y: umNow});

                const minTime = Date.now() - 120000;
                for (const ds of data.datasets){
                    while (ds.data.length && ds.data[0].x < minTime) ds.data.shift();
                }
                chart.options.scales.x.min = minTime;
                chart.options.scales.x.max = Date.now();
                chart.update('none');

                // HUD
                els.actual.textContent = fmtPct(y) + '%';
                els.estado.textContent = (Number.isFinite(y) && y >= umNow) ? 'CRÍTICO' : 'OK';
                els.estado.style.color = (Number.isFinite(y) && y >= umNow) ? '#d00' : '#090';

                // Info técnica
                if ('max_bytes' in j)  els.max.textContent  = fmtBytes(j.max_bytes);
                if ('used_bytes' in j) els.used.textContent = fmtBytes(j.used_bytes);
                if (j.db_name)         els.db.textContent   = j.db_name;
                if (j.instance_name)   els.inst.textContent = j.instance_name;
                if (j.host_name)       els.host.textContent = j.host_name;
                if (j.version)         els.ver.textContent  = j.version;
                if (j.platform_name)   els.plat.textContent = j.platform_name;
                if (j.sga_target != null)  els.sga.textContent = fmtBytes(j.sga_target);
                if (j.db_block_size != null) els.blk.textContent = fmtBytes(j.db_block_size);

                // Si es crítico: insertar alerta (cooldown 60s por dblink)
                if (Number.isFinite(y) && y >= umNow) {
                    const now  = Date.now();
                    const last = lastAlertAt.get(dblink) || 0;
                    if (now - last >= 60000) {
                        lastAlertAt.set(dblink, now);
                        try {
                            const res = await fetch(API + 'alert_insert_from_remote.php', {
                                method:'POST',
                                headers:{'Content-Type':'application/json'},
                                body: JSON.stringify({ dblink, consumo_pct: y })
                            });
                            let payload = {};
                            try { payload = await res.json(); } catch {}
                            if (!res.ok || !payload.ok) {
                                console.warn('alert_insert_from_remote FAIL', res.status, payload);
                            } else {
                                // refrescar tabla si se insertó
                                loadAlerts();
                            }
                        } catch (e) {
                            console.warn('alert_insert_from_remote EXC', e);
                        }
                    }
                }

            }catch(e){
                els.estado.textContent = 'ERROR';
                els.estado.style.color = '#888';
                console.warn('tick error', e);
            }
        }

        let intervalId;
        function startTimer(){
            if (intervalId) clearInterval(intervalId);
            const ms = Math.max(500, parseInt(els.reload.textContent,10) * 1000);
            intervalId = setInterval(tick, ms);
            tick();
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

            let payload = {};
            try { payload = await res.json(); } catch {}

            if (!res.ok || !payload.ok) {
                alert(`Error guardando config (HTTP ${res.status}). Detalle: ${JSON.stringify(payload)}`);
                return;
            }

            els.umbral.textContent = String(newUmbral);
            els.reload.textContent = String(newReload);
            startTimer();
        });
    }

    async function loadAlerts(){
        const tbody = document.querySelector('#tblAlerts tbody');
        tbody.innerHTML = '';
        const res = await fetch(API + 'alerts_list.php?days=7', {cache:'no-store'});
        const js  = await res.json();
        (js.items||[]).forEach(a=>{
            const sqlPreview = (a.sql || '').replace(/\s+/g,' ').slice(0,120);
            const tr = document.createElement('tr');
            tr.innerHTML =
                `<td>${a.dia}</td>
       <td>${a.hora}</td>
       <td>${a.cliente||''}</td>
       <td style="text-align:right;">${fmtPct(a.consumo)}</td>
       <td title="${(a.sql||'').replace(/"/g,'&quot;')}">${sqlPreview}${(a.sql && a.sql.length>120 ? '…':'')}</td>`;
            tbody.appendChild(tr);
        });
    }

    (async ()=>{
        const cfgs = await loadConfigs();
        if (cfgs.length===0){
            document.getElementById('cards').innerHTML =
                "<div class='card'>No hay clientes habilitados en MON_CONFIG_CLIENTE.</div>";
        } else {
            cfgs.forEach(c => renderCard(c));
        }
        await loadAlerts();
        setInterval(loadAlerts, 30000);
    })();
</script>

<?php include '../fragments/index/footer.php'; ?>
