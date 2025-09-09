<?php
// model/monitor/api/alerts_list.php
// Lista alertas recientes con SQL (texto) incluido.

header('Content-Type: application/json; charset=utf-8');

define('APP_ROOT', realpath(__DIR__ . '/../../../'));
$cfg = APP_ROOT . '/config/monitor_config.php';
if (!file_exists($cfg)) {
    http_response_code(500);
    echo json_encode(['ok'=>0,'error'=>'CONFIG_NOT_FOUND','detail'=>$cfg], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $cfg;

$days  = isset($_GET['days'])  ? (int)$_GET['days']  : 7;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
if ($days < 0)  $days  = 0;
if ($limit < 1) $limit = 100;

$conn = ora_conn();
if (!$conn) {
    $e = oci_error();
    http_response_code(500);
    echo json_encode(['ok'=>0,'error'=>'NO_CONN','detail'=>$e['message']??''], JSON_UNESCAPED_UNICODE);
    exit;
}

/*
  Columnas esperadas en MON_ALERTA:
    ID, DIA, HORA, CLIENTE, USUARIO, PROCESO, "SQL"(CLOB), CONSUMO, DETALLES
*/
$sql = "
SELECT *
FROM (
  SELECT
    TO_CHAR(a.dia,'YYYY-MM-DD')      AS dia,
    a.hora                           AS hora,
    a.cliente                        AS cliente,
    a.consumo                        AS consumo,
    DBMS_LOB.SUBSTR(a.\"SQL\", 4000, 1) AS sql_text
  FROM ".ORA_OWNER.".MON_ALERTA a
  WHERE a.dia >= TRUNC(SYSDATE) - :days
  ORDER BY a.id DESC
)
WHERE ROWNUM <= :lim
";

$st = oci_parse($conn, $sql);
oci_bind_by_name($st, ':days', $days);
oci_bind_by_name($st, ':lim',  $limit);
if (!oci_execute($st)) {
    $e = oci_error($st);
    oci_free_statement($st);
    oci_close($conn);
    http_response_code(500);
    echo json_encode(['ok'=>0,'error'=>'SELECT_ALERTS_FAIL','detail'=>$e['message']??''], JSON_UNESCAPED_UNICODE);
    exit;
}

$out = [];
while ($r = oci_fetch_assoc($st)) {
    $out[] = [
        'dia'     => $r['DIA'],
        'hora'    => $r['HORA'],
        'cliente' => $r['CLIENTE'],
        'consumo' => isset($r['CONSUMO']) ? (float)$r['CONSUMO'] : null,
        'sql'     => $r['SQL_TEXT'] ?? ''
    ];
}
oci_free_statement($st);
oci_close($conn);

echo json_encode(['ok'=>1,'items'=>$out], JSON_UNESCAPED_UNICODE);
