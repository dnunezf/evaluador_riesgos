<?php
// Devuelve agregados para dashboards: por día de la semana, por hora, y tendencia últimos N días.

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/monitor_config.php';

$cliente  = isset($_GET['cliente']) ? trim($_GET['cliente']) : 'ClienteRemoto';
$dias     = isset($_GET['dias']) ? max(1, min(60, (int)$_GET['dias'])) : 7;

$conn = ora_conn();

// 1) Por día de la semana (0=Dom, 6=Sáb) — promedio de consumo_pct
$sqlDow = "
SELECT TO_CHAR(ts,'D')-1 AS dow, ROUND(AVG(CASE WHEN max_bytes>0 THEN 100*used_bytes/max_bytes END),2) AS avg_pct
FROM   " . ORA_OWNER . ".MON_BUFFER_SNAPSHOT
WHERE  cliente = :c
  AND  ts >= SYSTIMESTAMP - NUMTODSINTERVAL(:d, 'DAY')
GROUP  BY TO_CHAR(ts,'D')-1
ORDER  BY 1";
$st1 = oci_parse($conn, $sqlDow);
oci_bind_by_name($st1, ':c', $cliente);
oci_bind_by_name($st1, ':d', $dias);
oci_execute($st1);
$dow = [];
while ($r = oci_fetch_assoc($st1)) {
  $dow[] = ['dow' => (int)$r['DOW'], 'avg_pct' => (float)$r['AVG_PCT']];
}
oci_free_statement($st1);

// 2) Por hora del día (0..23) — promedio
$sqlHour = "
SELECT TO_NUMBER(TO_CHAR(ts,'HH24')) AS hh, ROUND(AVG(CASE WHEN max_bytes>0 THEN 100*used_bytes/max_bytes END),2) AS avg_pct
FROM   " . ORA_OWNER . ".MON_BUFFER_SNAPSHOT
WHERE  cliente = :c
  AND  ts >= SYSTIMESTAMP - NUMTODSINTERVAL(:d, 'DAY')
GROUP  BY TO_NUMBER(TO_CHAR(ts,'HH24'))
ORDER  BY 1";
$st2 = oci_parse($conn, $sqlHour);
oci_bind_by_name($st2, ':c', $cliente);
oci_bind_by_name($st2, ':d', $dias);
oci_execute($st2);
$hour = [];
while ($r = oci_fetch_assoc($st2)) {
  $hour[] = ['hh' => (int)$r['HH'], 'avg_pct' => (float)$r['AVG_PCT']];
}
oci_free_statement($st2);

// 3) Tendencia diaria (max pct por día)
$sqlTrend = "
SELECT TO_CHAR(ts,'YYYY-MM-DD') AS dia,
       ROUND(MAX(CASE WHEN max_bytes>0 THEN 100*used_bytes/max_bytes END),2) AS max_pct
FROM   " . ORA_OWNER . ".MON_BUFFER_SNAPSHOT
WHERE  cliente = :c
  AND  ts >= TRUNC(SYSDATE) - (:d-1)
GROUP  BY TO_CHAR(ts,'YYYY-MM-DD')
ORDER  BY 1";
$st3 = oci_parse($conn, $sqlTrend);
oci_bind_by_name($st3, ':c', $cliente);
oci_bind_by_name($st3, ':d', $dias);
oci_execute($st3);
$trend = [];
while ($r = oci_fetch_assoc($st3)) {
  $trend[] = ['day' => $r['DIA'], 'max_pct' => (float)$r['MAX_PCT']];
}
oci_free_statement($st3);

// 4) Últimas alertas
$sqlA = "
SELECT TO_CHAR(dia,'YYYY-MM-DD') AS dia, hora, consumo
FROM   " . ORA_OWNER . ".MON_ALERTA
WHERE  dia >= TRUNC(SYSDATE) - (:d-1)
ORDER  BY dia DESC, hora DESC FETCH FIRST 20 ROWS ONLY";
$st4 = oci_parse($conn, $sqlA);
oci_bind_by_name($st4, ':d', $dias);
oci_execute($st4);
$alerts = [];
while ($r = oci_fetch_assoc($st4)) {
  $alerts[] = ['dia' => $r['DIA'], 'hora' => $r['HORA'], 'consumo' => (float)$r['CONSUMO']];
}
oci_free_statement($st4);

oci_close($conn);
echo json_encode(['cliente' => $cliente, 'dias' => $dias, 'by_dow' => $dow, 'by_hour' => $hour, 'trend' => $trend, 'alerts' => $alerts], JSON_UNESCAPED_UNICODE);
