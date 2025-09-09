<?php
// model/monitor/api/monitor_tick_remote.php
// Lee consumo “en vivo” del cliente vía DBLINK y (si supera umbral) registra alerta.
// Uso: ?dblink=DBLINK_MARIA  (o)  ?cliente=CLIENTE1   |  añade ?debug=1 para ver errores PHP.

$DEBUG = isset($_GET['debug']) && $_GET['debug'] == '1';
if ($DEBUG) { error_reporting(E_ALL); ini_set('display_errors', 1); }

header('Content-Type: application/json; charset=utf-8');

// ---- Cargar config ----
define('APP_ROOT', realpath(__DIR__ . '/../../../'));
$cfg = APP_ROOT . '/config/monitor_config.php';
if (!file_exists($cfg)) {
    http_response_code(500);
    echo json_encode(['ok'=>0,'error'=>'CONFIG_NOT_FOUND','detail'=>$cfg], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $cfg;

// ---- Helpers JSON ----
function jerr($msg, $detail='') {
    http_response_code(500);
    echo json_encode(['ok'=>0,'error'=>$msg,'detail'=>$detail], JSON_UNESCAPED_UNICODE);
    exit;
}
function jout($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Parámetros ----
$dblink  = isset($_GET['dblink'])  ? trim($_GET['dblink'])  : '';
$cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';

$conn = ora_conn();
if (!$conn) { $e = oci_error(); jerr('NO_CONN', $e['message'] ?? ''); }

// Resolver dblink/umbral/cliente desde MON_CONFIG_CLIENTE si hace falta
$umbral = null;
if ($dblink === '') {
    if ($cliente === '') jerr('FALTA_PARAM', 'Envía ?dblink=... o ?cliente=...');
    $sqlC = "SELECT DBLINK, UMBRAL_PCT FROM ".ORA_OWNER.".MON_CONFIG_CLIENTE
           WHERE CLIENTE=:c AND HABILITADO='Y'";
    $stC = oci_parse($conn, $sqlC);
    oci_bind_by_name($stC, ':c', $cliente);
    if (!oci_execute($stC)) { $e=oci_error($stC); oci_free_statement($stC); oci_close($conn); jerr('SELECT_CONFIG_FAIL',$e['message']??''); }
    $rC = oci_fetch_assoc($stC); oci_free_statement($stC);
    if (!$rC) { oci_close($conn); jerr('CLIENTE_NO_HABILITADO', $cliente); }
    $dblink = $rC['DBLINK'];
    $umbral = (float)$rC['UMBRAL_PCT'];
}
if ($umbral === null) {
    $sqlU = "SELECT UMBRAL_PCT, CLIENTE FROM ".ORA_OWNER.".MON_CONFIG_CLIENTE
           WHERE DBLINK=:d AND HABILITADO='Y' FETCH FIRST 1 ROWS ONLY";
    $stU = oci_parse($conn, $sqlU);
    oci_bind_by_name($stU, ':d', $dblink);
    if (oci_execute($stU)) {
        $rU = oci_fetch_assoc($stU);
        if ($rU) { $umbral = (float)$rU['UMBRAL_PCT']; if ($cliente==='') $cliente=$rU['CLIENTE']; }
    }
    oci_free_statement($stU);
}
if ($umbral === null || !is_numeric($umbral)) $umbral = 85.0;

// ---- SQL remoto para medir consumo (NO usar SYSTIMESTAMP@DBLINK ni DUAL@DBLINK) ----
$sql = <<<'SQL'
WITH
blk AS (
  SELECT TO_NUMBER(value) AS block_size
  FROM   v$parameter@{DBL}
  WHERE  name = 'db_block_size'
),
mx AS (
  SELECT CURRENT_SIZE AS max_bytes
  FROM   v$sga_dynamic_components@{DBL}
  WHERE  component = 'DEFAULT buffer cache'
  UNION ALL
  SELECT bytes
  FROM   v$sgainfo@{DBL}
  WHERE  name = 'Buffer Cache Size'
  UNION ALL
  SELECT NVL(TO_NUMBER(value),0)
  FROM   v$parameter@{DBL}
  WHERE  name = 'db_cache_size'
),
u AS (
  SELECT COUNT(*) AS non_free_buffers
  FROM   v$bh@{DBL}
  WHERE  status <> 'free'
)
SELECT
  TO_CHAR(SYSTIMESTAMP, 'YYYY-MM-DD"T"HH24:MI:SS.FF3TZH:TZM') AS ts_iso,
  (SELECT MAX(max_bytes) FROM mx)                                  AS max_bytes,
  (SELECT non_free_buffers FROM u) * (SELECT block_size FROM blk)  AS used_bytes
FROM dual
SQL;
$sql = str_replace('{DBL}', $dblink, $sql);

$st = oci_parse($conn, $sql);
if (!$st) { $e = oci_error($conn); oci_close($conn); jerr('PARSE_FAIL', $e['message'] ?? ''); }
if (!oci_execute($st)) {
    $e = oci_error($st);
    oci_free_statement($st);
    oci_close($conn);
    // Si aquí ves ORA-01031, al usuario REMOTO le falta SELECT_CATALOG_ROLE
    jerr('DBLINK_SQL_FAIL', $e['message'] ?? '');
}
$r = oci_fetch_assoc($st);
oci_free_statement($st);

if (!$r) { oci_close($conn); jerr('REMOTE_EMPTY', 'Sin filas desde remoto'); }

$max  = (float)$r['MAX_BYTES'];
$used = (float)$r['USED_BYTES'];
$pct  = ($max > 0) ? round(100.0 * $used / $max, 2) : 0.0;
$tsIso = $r['TS_ISO'];

// ===================== NUEVO: registrar alerta si supera umbral =====================
$alert_inserted = 0;
if ($pct >= (float)$umbral) {
    // Intentar capturar top sesiones activas en remoto (best-effort)
    $detalles = '';
    $sqlSess = "SELECT s.username, p.spid, s.sid, s.serial#, NVL(s.program,'-') program,
                     NVL(s.module,'-') module, NVL(s.machine,'-') machine
              FROM   v\$session@".$dblink." s
                     JOIN v\$process@".$dblink." p ON s.paddr = p.addr
              WHERE  s.type='USER' AND s.status='ACTIVE'
              AND    ROWNUM <= 10";
    $stA = @oci_parse($conn, $sqlSess);
    if ($stA && @@oci_execute($stA)) {
        while ($row = oci_fetch_assoc($stA)) {
            $detalles .= "USR={$row['USERNAME']} SPID={$row['SPID']} SID={$row['SID']}/{$row['SERIAL#']} ".
                "PROG={$row['PROGRAM']} MOD={$row['MODULE']} MACH={$row['MACHINE']}\n";
        }
        oci_free_statement($stA);
    } else {
        $eA = oci_error($stA);
        $detalles = $detalles ?: ('Consumo crítico. Detalle de sesiones no disponible. '.($eA['message']??''));
    }

    // SQL de la sesión “más reciente” (best-effort, puede no estar)
    $sqlText = '';
    $stQ = @oci_parse($conn, "SELECT q.sql_text
                             FROM v\$session@".$dblink." s
                             JOIN v\$sql@".$dblink." q ON s.sql_id=q.sql_id
                             WHERE s.type='USER' AND s.status='ACTIVE' AND ROWNUM=1");
    if ($stQ && @oci_execute($stQ)) {
        $rowQ = oci_fetch_assoc($stQ);
        if ($rowQ && isset($rowQ['SQL_TEXT'])) $sqlText = $rowQ['SQL_TEXT'];
        oci_free_statement($stQ);
    }

    // Binds robustos para CLOBs y número con NLS fijo
    $sqlIns = "INSERT INTO ".ORA_OWNER.".MON_ALERTA
             (CLIENTE, DIA, HORA, USUARIO, PROCESO, \"SQL\", CONSUMO, DETALLES)
             VALUES (
               :cl, TRUNC(SYSDATE), TO_CHAR(SYSDATE,'HH24:MI:SS'),
               :usr, :pid, :sqltxt,
               TO_NUMBER(:consS, '9999999990D99', 'NLS_NUMERIC_CHARACTERS=.,'),
               :det
             )";
    $stI = oci_parse($conn, $sqlIns);

    $usr = 'MONITOR';
    $pid = null;
    $consS = number_format((float)$pct, 2, '.', ''); // "34.00"

    $clobSql = oci_new_descriptor($conn, OCI_D_LOB);
    $clobDet = oci_new_descriptor($conn, OCI_D_LOB);
    if ($clobSql) $clobSql->writeTemporary($sqlText !== '' ? $sqlText : '-', OCI_TEMP_CLOB);
    if ($clobDet) $clobDet->writeTemporary($detalles, OCI_TEMP_CLOB);

    oci_bind_by_name($stI, ':cl',    $cliente);
    oci_bind_by_name($stI, ':usr',   $usr);
    oci_bind_by_name($stI, ':pid',   $pid);
    if ($clobSql) { oci_bind_by_name($stI, ':sqltxt', $clobSql, -1, OCI_B_CLOB); }
    else          { oci_bind_by_name($stI, ':sqltxt', $sqlText); }
    oci_bind_by_name($stI, ':consS', $consS);
    if ($clobDet) { oci_bind_by_name($stI, ':det', $clobDet, -1, OCI_B_CLOB); }
    else          { oci_bind_by_name($stI, ':det', $detalles); }

    if (oci_execute($stI, OCI_NO_AUTO_COMMIT)) {
        oci_commit($conn);
        $alert_inserted = 1;
    }
    if ($clobSql) $clobSql->free();
    if ($clobDet) $clobDet->free();
    oci_free_statement($stI);
}

oci_close($conn);

// ---- Respuesta ----
jout([
    'ok'            => 1,
    'cliente'       => $cliente ?: null,
    'dblink'        => $dblink,
    'ts'            => $tsIso,
    'max_bytes'     => $max,
    'used_bytes'    => $used,
    'consumo_pct'   => $pct,
    'umbral_pct'    => (float)$umbral,
    'critico'       => ($pct >= (float)$umbral ? 1 : 0),
    'alerta_insert' => $alert_inserted
]);
