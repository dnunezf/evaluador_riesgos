<?php
// model/monitor/api/alert_insert_from_remote.php
// Inserta una alerta en MON_ALERTA usando datos obtenidos en vivo por DBLINK
// Entrada (JSON o x-www-form-urlencoded):
//   - dblink (requerido)  | ó cliente (si falta dblink)
//   - consumo_pct (requerido, número)
//   - ts, max_bytes, used_bytes (opcional, informativos)
// Devuelve: { ok:1, ... } o { ok:0, error, detail }

header('Content-Type: application/json; charset=utf-8');

define('APP_ROOT', realpath(__DIR__ . '/../../../'));
$cfg = APP_ROOT . '/config/monitor_config.php';
if (!file_exists($cfg)) {
    http_response_code(500);
    echo json_encode(['ok'=>0,'error'=>'CONFIG_NOT_FOUND','detail'=>$cfg], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $cfg;

function jerr($msg, $detail='') {
    http_response_code(500);
    echo json_encode(['ok'=>0,'error'=>$msg,'detail'=>$detail], JSON_UNESCAPED_UNICODE);
    exit;
}
function jout($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

// -------- Leer input --------
$raw = file_get_contents('php://input');
$in  = [];
if ($raw) {
    $tmp = json_decode($raw, true);
    if (is_array($tmp)) $in = $tmp;
}
if (empty($in)) {
    // fallback a form-data o querystring
    $in = $_POST + $_GET;
}

$dblink      = isset($in['dblink'])  ? trim($in['dblink'])  : '';
$cliente     = isset($in['cliente']) ? trim($in['cliente']) : '';
$consumo_pct = isset($in['consumo_pct']) ? (float)$in['consumo_pct'] : null;

if ($consumo_pct === null || !is_numeric($consumo_pct)) {
    jerr('BAD_INPUT', 'consumo_pct requerido');
}

$conn = ora_conn();
if (!$conn) {
    $e = oci_error();
    jerr('NO_CONN', $e['message'] ?? '');
}

// Resolver dblink/cliente desde MON_CONFIG_CLIENTE si falta alguno
if ($dblink === '') {
    if ($cliente === '') { oci_close($conn); jerr('FALTA_PARAM','dblink o cliente'); }
    $sqlR = "SELECT DBLINK FROM ".ORA_OWNER.".MON_CONFIG_CLIENTE WHERE CLIENTE=:c AND HABILITADO='Y'";
    $stR  = oci_parse($conn, $sqlR);
    oci_bind_by_name($stR, ':c', $cliente);
    if (!oci_execute($stR)) {
        $e = oci_error($stR); oci_free_statement($stR); oci_close($conn);
        jerr('SELECT_CONFIG_FAIL', $e['message'] ?? '');
    }
    $rR = oci_fetch_assoc($stR);
    oci_free_statement($stR);
    if (!$rR) { oci_close($conn); jerr('CLIENTE_NO_HABILITADO',$cliente); }
    $dblink = $rR['DBLINK'];
}
if ($cliente === '') {
    $sqlR = "SELECT CLIENTE FROM ".ORA_OWNER.".MON_CONFIG_CLIENTE WHERE DBLINK=:d AND HABILITADO='Y'";
    $stR  = oci_parse($conn, $sqlR);
    oci_bind_by_name($stR, ':d', $dblink);
    if (oci_execute($stR)) {
        $rR = oci_fetch_assoc($stR);
        if ($rR) $cliente = $rR['CLIENTE'];
    }
    oci_free_statement($stR);
}
if ($cliente === '') $cliente = $dblink; // fallback

// -------- Consultar sesiones activas remotas para obtener SQL --------
$usr = null; $pid = null; $sqltxt = '-'; $det = '';
$remoteOk = false;

$sqlRemote = "
SELECT *
FROM (
  SELECT
    s.username,
    p.spid AS proceso,
    s.sid, s.serial#,
    NVL(s.program,'-') AS program,
    NVL(s.module,'-')  AS module,
    NVL(s.machine,'-') AS machine,
    q.sql_text
  FROM   v\$session@{$dblink} s
         JOIN v\$process@{$dblink} p ON s.paddr = p.addr
         LEFT JOIN v\$sql@{$dblink} q ON s.sql_id = q.sql_id
  WHERE  s.type='USER' AND s.status='ACTIVE'
  ORDER  BY s.last_call_et DESC
)
WHERE ROWNUM <= 1";

$stA = @oci_parse($conn, $sqlRemote);
if ($stA && @oci_execute($stA)) {
    $row = oci_fetch_assoc($stA);
    if ($row) {
        $remoteOk = true;
        $usr    = $row['USERNAME'] ?? null;
        $pid    = $row['PROCESO']  ?? null;
        $sqltxt = $row['SQL_TEXT'] ?? '-';
        $det    = "SID={$row['SID']}/{$row['SERIAL#']} PROG={$row['PROGRAM']} MOD={$row['MODULE']} MACH={$row['MACHINE']}";
    }
    oci_free_statement($stA);
} else {
    $eA = oci_error($stA);
    $det = 'No se pudo leer sesiones remotas: ' . ($eA['message'] ?? '');
}

// -------- Insertar en MON_ALERTA --------
// Usar CLOBs y TO_NUMBER con NLS para no sufrir decimales
$clobSql = oci_new_descriptor($conn, OCI_D_LOB);
$clobDet = oci_new_descriptor($conn, OCI_D_LOB);
if ($clobSql) $clobSql->writeTemporary($sqltxt ?? '-', OCI_TEMP_CLOB);
if ($clobDet) $clobDet->writeTemporary($det ?? '-', OCI_TEMP_CLOB);

$consS = number_format((float)$consumo_pct, 2, '.', '');

$sqlIns = "INSERT INTO ".ORA_OWNER.".MON_ALERTA
  (CLIENTE, USUARIO, PROCESO, \"SQL\", CONSUMO, DETALLES)
  VALUES (:cli, :usr, :pid,
          :sqltxt,
          TO_NUMBER(:consS, '9999999990D99', 'NLS_NUMERIC_CHARACTERS=.,'),
          :det)";

$stI = oci_parse($conn, $sqlIns);
oci_bind_by_name($stI, ':cli',    $cliente);
oci_bind_by_name($stI, ':usr',    $usr);
oci_bind_by_name($stI, ':pid',    $pid);
if ($clobSql) { oci_bind_by_name($stI, ':sqltxt', $clobSql, -1, OCI_B_CLOB); }
else          { oci_bind_by_name($stI, ':sqltxt', $sqltxt); }
oci_bind_by_name($stI, ':consS',  $consS);
if ($clobDet) { oci_bind_by_name($stI, ':det',    $clobDet, -1, OCI_B_CLOB); }
else          { oci_bind_by_name($stI, ':det',    $det); }

if (!oci_execute($stI, OCI_NO_AUTO_COMMIT)) {
    $e = oci_error($stI);
    if ($clobDet) $clobDet->free();
    if ($clobSql) $clobSql->free();
    oci_free_statement($stI);
    oci_rollback($conn);
    oci_close($conn);
    jerr('INSERT_ALERT_FAIL', $e['message'] ?? '');
}

oci_free_statement($stI);
if ($clobDet) $clobDet->free();
if ($clobSql) $clobSql->free();

oci_commit($conn);
oci_close($conn);

jout([
    'ok' => 1,
    'cliente' => $cliente,
    'dblink'  => $dblink,
    'remote_sessions_ok' => $remoteOk ? 1 : 0
]);
