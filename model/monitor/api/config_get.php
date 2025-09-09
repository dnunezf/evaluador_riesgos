<?php
// model/monitor/api/config_get.php
header('Content-Type: application/json; charset=utf-8');

$cfg1 = dirname(__DIR__, 3) . '/config/monitor_config.php';
$cfg2 = dirname(__DIR__, 2) . '/config/monitor_config.php';
$cfg  = file_exists($cfg1) ? $cfg1 : $cfg2;
if (!file_exists($cfg)) { echo json_encode(['ok'=>0,'error'=>'Config no encontrada']); exit; }
require_once $cfg;

$conn = ora_conn();
if (!$conn) { echo json_encode(['ok'=>0,'error'=>'Sin conexión']); exit; }

function has_col($conn, $owner, $table, $col) {
    $sql = "SELECT 1 FROM ALL_TAB_COLUMNS WHERE OWNER=:o AND TABLE_NAME=:t AND COLUMN_NAME=:c";
    $st = oci_parse($conn, $sql);
    $o = strtoupper($owner); $t = strtoupper($table); $c = strtoupper($col);
    oci_bind_by_name($st, ':o', $o); oci_bind_by_name($st, ':t', $t); oci_bind_by_name($st, ':c', $c);
    @oci_execute($st);
    $row = oci_fetch_assoc($st);
    oci_free_statement($st);
    return !!$row;
}

$OWNER = ORA_OWNER; $TABLE='MON_CONFIG_CLIENTE';
$has_dblink      = has_col($conn,$OWNER,$TABLE,'DBLINK');
$has_dblink_name = has_col($conn,$OWNER,$TABLE,'DBLINK_NAME');
$has_habilitado  = has_col($conn,$OWNER,$TABLE,'HABILITADO');
$has_enabled     = has_col($conn,$OWNER,$TABLE,'ENABLED');
$has_alias       = has_col($conn,$OWNER,$TABLE,'ALIAS');

$DBL_COL = $has_dblink ? 'DBLINK' : ($has_dblink_name ? 'DBLINK_NAME' : null);
$ENA_COL = $has_habilitado ? 'HABILITADO' : ($has_enabled ? 'ENABLED' : null);
if ($DBL_COL===null || $ENA_COL===null){ oci_close($conn); echo json_encode(['ok'=>0,'error'=>'Tabla MON_CONFIG_CLIENTE inválida']); exit; }

$select = "SELECT {$DBL_COL} AS DBLINK, CLIENTE, UMBRAL_PCT, RELOAD_SEC, {$ENA_COL} AS ENA"
    . ($has_alias? ", NVL(ALIAS, CLIENTE) AS ALIAS" : ", CLIENTE AS ALIAS")
    . " FROM {$OWNER}.{$TABLE}";
$st = oci_parse($conn, $select);
oci_execute($st);

$out = [];
while ($r = oci_fetch_assoc($st)) {
    $enaRaw = strtoupper(trim((string)($r['ENA'] ?? '')));
    $enabled = ($enaRaw==='Y'||$enaRaw==='1'||$enaRaw==='TRUE');
    $out[] = [
        'dblink'  => $r['DBLINK'],
        'cliente' => $r['CLIENTE'],
        'alias'   => $r['ALIAS'],
        'umbral'  => (float)$r['UMBRAL_PCT'],
        'reload'  => (int)$r['RELOAD_SEC'],
        'enabled' => $enabled
    ];
}
oci_free_statement($st);
oci_close($conn);
echo json_encode(['ok'=>1,'items'=>$out], JSON_UNESCAPED_UNICODE);
