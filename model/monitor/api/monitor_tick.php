<?php
// model/monitor/api/monitor_tick_remote.php
// Versión DIAGNÓSTICA: nunca lanza 500; siempre devuelve JSON con step y ORA-XXXX.

header('Content-Type: application/json; charset=utf-8');

// ====== DEBUG local de este script (no tocar php.ini) ======
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Captura warnings/notices como JSON
set_error_handler(function($errno, $errstr, $errfile, $errline){
    echo json_encode(['ok'=>0, 'step'=>'PHP', 'error'=>"[$errno] $errstr @ $errfile:$errline"]);
    exit;
});

define('APP_ROOT', realpath(__DIR__ . '/../../../'));
$cfg = APP_ROOT . '/config/monitor_config.php';
if (!file_exists($cfg)) {
    echo json_encode(['ok'=>0,'step'=>'CONFIG','error'=>"No existe: $cfg"]);
    exit;
}
require_once $cfg;

function jfail($step, $msg){
    echo json_encode(['ok'=>0,'step'=>$step,'error'=>$msg], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn = @ora_conn();
if (!$conn) {
    $e = oci_error();
    jfail('CONN_LOCAL', $e['message'] ?? 'sin detalle');
}

// Parámetros
$dblink  = isset($_GET['dblink'])  ? trim($_GET['dblink'])  : '';
$cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';
$umParam = isset($_GET['umbral'])  ? floatval($_GET['umbral']) : null;

if ($dblink === '' && $cliente === '') {
    jfail('PARAMS', 'Falta ?dblink= o ?cliente=');
}

// Resolver config
$umbral = 85.0;
if ($cliente !== '') {
    $sqlC = "SELECT dblink, cliente, umbral_pct FROM ".ORA_OWNER.".MON_CONFIG_CLIENTE
           WHERE UPPER(cliente)=UPPER(:c) AND habilitado='Y'";
    $stC = @oci_parse($conn, $sqlC);
    if (!$stC) jfail('PARSE_CFG_CLI', (oci_error($conn)['message'] ?? 'parse'));
    oci_bind_by_name($stC, ':c', $cliente);
    if (!@oci_execute($stC)) jfail('EXEC_CFG_CLI', (oci_error($stC)['message'] ?? 'exec'));
    $rC = oci_fetch_assoc($stC);
    oci_free_statement($stC);
    if (!$rC) jfail('CFG_CLI', 'Cliente no existe o está deshabilitado');
    $dblink  = $rC['DBLINK'];
    $cliente = $rC['CLIENTE'];
    $umbral  = $umParam !== null ? $umParam : (float)$rC['UMBRAL_PCT'];
} else {
    $sqlD = "SELECT cliente, umbral_pct FROM ".ORA_OWNER.".MON_CONFIG_CLIENTE
           WHERE UPPER(dblink)=UPPER(:d) AND habilitado='Y'";
    $stD = @oci_parse($conn, $sqlD);
    if ($stD) {
        oci_bind_by_name($stD, ':d', $dblink);
        @oci_execute($stD);
        $rD = oci_fetch_assoc($stD);
        oci_free_statement($stD);
        if ($rD) {
            $cliente = $rD['CLIENTE'];
            $umbral  = $umParam !== null ? $umParam : (float)$rD['UMBRAL_PCT'];
        }
    }
    if ($cliente === '') $cliente = $dblink;
    if ($umParam !== null) $umbral = $umParam;
}

// Paso 1: prueba DBLINK básica
$stPing = @oci_parse($conn, "SELECT 1 AS ok FROM dual@".$dblink);
if (!$stPing) jfail('PARSE_PING', (oci_error($conn)['message'] ?? 'parse'));
if (!@oci_execute($stPing)) jfail('EXEC_PING', (oci_error($stPing)['message'] ?? 'exec'));
$rPing = oci_fetch_assoc($stPing);
oci_free_statement($stPing);
if (!$rPing) jfail('PING', 'dual@dblink sin filas');

// Paso 2: lee live consumo + info
$sqlLive = <<<'SQL'
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
),
inst AS (
  SELECT instance_name, host_name, version
  FROM   v$instance@{DBL}
),
dbi AS (
  SELECT name AS db_name, platform_name
  FROM   v$database@{DBL}
),
sgap AS (
  SELECT TO_NUMBER(value) AS sga_target
  FROM   v$parameter@{DBL}
  WHERE  name = 'sga_target'
)
SELECT
  TO_CHAR(SYSTIMESTAMP@{DBL}, 'YYYY-MM-DD"T"HH24:MI:SS.FF3') AS ts_iso,
  (SELECT MAX(max_bytes) FROM mx)                                  AS max_bytes,
  (SELECT non_free_buffers FROM u) * (SELECT block_size FROM blk)  AS used_bytes,
  (SELECT block_size FROM blk)                                     AS block_size,
  (SELECT sga_target FROM sgap)                                    AS sga_target,
  (SELECT instance_name FROM inst)                                 AS instance_name,
  (SELECT host_name FROM inst)                                     AS host_name,
  (SELECT version FROM inst)                                       AS version,
  (SELECT db_name FROM dbi)                                        AS db_name,
  (SELECT platform_name FROM dbi)                                  AS platform_name
FROM dual@{DBL}
SQL;
$sqlLive = str_replace('{DBL}', $dblink, $sqlLive);
$stL = @oci_parse($conn, $sqlLive);
if (!$stL) jfail('PARSE_LIVE', (oci_error($conn)['message'] ?? 'parse'));
if (!@oci_execute($stL)) jfail('EXEC_LIVE', (oci_error($stL)['message'] ?? 'exec'));
$rl = oci_fetch_assoc($stL);
oci_free_statement($stL);
if (!$rl) jfail('LIVE_FETCH', 'sin filas');

// Calcular consumo
$max  = (float)($rl['MAX_BYTES']  ?? 0);
$used = (float)($rl['USED_BYTES'] ?? 0);
$pct  = $max > 0 ? round(100.0 * $used / $max, 2) : 0.0;
$ts   = $rl['TS_ISO'] ?? date('c');

// Paso 3: si cruza umbral, generar alerta local con SQL_TEXT del remoto (best-effort)
$alerta_ok = 0;
$sqlText   = null;

if ($pct >= $umbral) {
    $sqlA = "SELECT s.username, p.spid, s.sid, s.serial#, NVL(s.program,'-') program,
                  NVL(s.module,'-') module, NVL(s.machine,'-') machine, q.sql_text
           FROM   v\$session@$dblink s
                  JOIN v\$process@$dblink p ON s.paddr=p.addr
                  LEFT JOIN v\$sql@$dblink q ON s.sql_id=q.sql_id
           WHERE  s.type='USER' AND s.status='ACTIVE'
           ORDER  BY s.last_call_et DESC FETCH FIRST 1 ROWS ONLY";
    $stA = @oci_parse($conn, $sqlA);
    $det = '';
    if ($stA && @oci_execute($stA)) {
        $ra = oci_fetch_assoc($stA);
        if ($ra) {
            $det = "USR={$ra['USERNAME']} SPID={$ra['SPID']} SID={$ra['SID']}/{$ra['SERIAL#']} "
                . "PROG={$ra['PROGRAM']} MOD={$ra['MODULE']} MACH={$ra['MACHINE']}";
            $sqlText = $ra['SQL_TEXT'] ?? null;
        }
        oci_free_statement($stA);
    } // si falla, seguimos sin abortar

    $clobSql = null; $clobDet = null;
    if ($sqlText !== null) { $clobSql = oci_new_descriptor($conn, OCI_D_LOB); $clobSql->writeTemporary($sqlText, OCI_TEMP_CLOB); }
    $clobDet = oci_new_descriptor($conn, OCI_D_LOB); $clobDet->writeTemporary($det, OCI_TEMP_CLOB);

    $insA = @oci_parse($conn, "INSERT INTO ".ORA_OWNER.".MON_ALERTA
                            (CLIENTE, USUARIO, PROCESO, \"SQL\", CONSUMO, DETALLES)
                            VALUES (:cliente, :usr, :pid, :sqltxt,
                                    TO_NUMBER(:cons, '9999999990D99', 'NLS_NUMERIC_CHARACTERS=.,'),
                                    :det)");
    if ($insA) {
        $usr   = 'MONITOR';
        $pid   = null;
        $consS = number_format($pct, 2, '.', '');
        oci_bind_by_name($insA, ':cliente', $cliente);
        oci_bind_by_name($insA, ':usr', $usr);
        oci_bind_by_name($insA, ':pid', $pid);
        if ($clobSql) oci_bind_by_name($insA, ':sqltxt', $clobSql, -1, OCI_B_CLOB); else oci_bind_by_name($insA, ':sqltxt', $sqlText);
        oci_bind_by_name($insA, ':cons', $consS);
        oci_bind_by_name($insA, ':det',  $clobDet, -1, OCI_B_CLOB);
        if (@oci_execute($insA, OCI_NO_AUTO_COMMIT)) { $alerta_ok = 1; @oci_commit($conn); }
        else {
            $e = oci_error($insA);
            // No abortamos; solo informamos que no se pudo insertar alerta
            // y seguimos devolviendo el consumo.
        }
        oci_free_statement($insA);
    }
    if ($clobSql) $clobSql->free();
    if ($clobDet) $clobDet->free();
}

@oci_close($conn);

// Respuesta
echo json_encode([
    'ok'            => 1,
    'cliente'       => $cliente,
    'dblink'        => $dblink,
    'ts'            => $ts,
    'consumo_pct'   => $pct,
    'umbral_pct'    => $umbral,
    'max_bytes'     => $max,
    'used_bytes'    => $used,
    'db_name'       => $rl['DB_NAME']        ?? null,
    'instance_name' => $rl['INSTANCE_NAME']  ?? null,
    'host_name'     => $rl['HOST_NAME']      ?? null,
    'version'       => $rl['VERSION']        ?? null,
    'platform_name' => $rl['PLATFORM_NAME']  ?? null,
    'sga_target'    => isset($rl['SGA_TARGET']) ? (float)$rl['SGA_TARGET'] : null,
    'db_block_size' => isset($rl['BLOCK_SIZE']) ? (float)$rl['BLOCK_SIZE'] : null,
    'critico'       => ($pct >= $umbral ? 1 : 0),
    'alerta'        => $alerta_ok
], JSON_UNESCAPED_UNICODE);
