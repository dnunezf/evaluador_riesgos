<?php
// monitor/api/collect_and_alert.php
// Inserta snapshot local desde el cliente vía DBLINK y genera alerta si >= umbral.

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/config/monitor_config.php';

const DBLINK_NAME = 'dblink_cliente_sim';   // cambia al real cuando toque
const CLIENTE     = 'ClienteRemoto';
const UMBRAL      = 30;

$conn = ora_conn();

// =================== 1) Calcular consumo remoto (MAX/USED) ===================
$sql = <<<'SQL'
WITH
blk AS (
  SELECT TO_NUMBER(value) AS block_size
  FROM   v$parameter@{DBL} WHERE name = 'db_block_size'
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
  TO_CHAR(SYSTIMESTAMP, 'YYYY-MM-DD"T"HH24:MI:SS') AS ts_iso,
  (SELECT MAX(max_bytes) FROM mx)                                  AS max_bytes,
  (SELECT non_free_buffers FROM u) * (SELECT block_size FROM blk)  AS used_bytes
FROM dual@{DBL}
SQL;

$sql = str_replace('{DBL}', DBLINK_NAME, $sql);

$st = oci_parse($conn, $sql);
if (!oci_execute($st)) {
    $e = oci_error($st);
    http_response_code(500);
    echo json_encode(array('ok'=>0, 'step'=>'calc', 'error'=>$e['message']??''), JSON_UNESCAPED_UNICODE);
    exit;
}
$r = oci_fetch_assoc($st);
oci_free_statement($st);

if (!$r) {
    echo json_encode(array('ok'=>0, 'step'=>'calc', 'error'=>'sin filas'), JSON_UNESCAPED_UNICODE);
    oci_close($conn);
    exit;
}

$max  = (float)$r['MAX_BYTES'];
$used = (float)$r['USED_BYTES'];
$pct  = $max > 0 ? round(100.0 * $used / $max, 2) : 0.0;

// ======================== 2) Insertar snapshot local =========================
$ins = oci_parse($conn, "INSERT INTO ".ORA_OWNER.".MON_BUFFER_SNAPSHOT (CLIENTE, MAX_BYTES, USED_BYTES) VALUES (:c,:m,:u)");
$c = CLIENTE;
oci_bind_by_name($ins, ':c', $c);
oci_bind_by_name($ins, ':m', $max);
oci_bind_by_name($ins, ':u', $used);

if (!oci_execute($ins, OCI_NO_AUTO_COMMIT)) {
    $e = oci_error($ins);
    oci_rollback($conn);
    http_response_code(500);
    echo json_encode(array('ok'=>0, 'step'=>'insert_snapshot', 'error'=>$e['message']??''), JSON_UNESCAPED_UNICODE);
    exit;
}
oci_free_statement($ins);

// =================== 3) Insertar alerta si cruza el umbral ===================
$alerta_ok = 0;
if ($pct >= UMBRAL) {
    // Intentar capturar sesiones activas remotas (requiere SELECT_CATALOG_ROLE en remoto)
    $desc = '';
    $sqlA = "
      SELECT s.username, p.spid, s.sid, s.serial#, NVL(s.program,'-') program,
             NVL(s.module,'-') module, NVL(s.machine,'-') machine
      FROM   v\$session@".DBLINK_NAME." s
             JOIN v\$process@".DBLINK_NAME." p ON s.paddr=p.addr
      WHERE  s.type='USER' AND s.status='ACTIVE' AND ROWNUM <= 10";
    $stA = @oci_parse($conn, $sqlA);
    if ($stA && @oci_execute($stA)) {
        while ($row = oci_fetch_assoc($stA)) {
            $desc .= "USR={$row['USERNAME']} SPID={$row['SPID']} "
                . "SID={$row['SID']}/{$row['SERIAL#']} "
                . "PROG={$row['PROGRAM']} MOD={$row['MODULE']} MACH={$row['MACHINE']}\n";
        }
        oci_free_statement($stA);
    } else {
        $eA = oci_error($stA);
        $desc = $desc ?: ('Consumo crítico sin detalles de sesión. '.($eA['message']??''));
    }

    // Binds robustos: CONSUMO como string con punto + TO_NUMBER con NLS fijo
    $usr    = 'MONITOR';
    $pid    = null;
    $sqltxt = '-';
    $consS  = number_format((float)$pct, 2, '.', '');  // "32.66"
    $det    = $desc;

    $clobDet = oci_new_descriptor($conn, OCI_D_LOB);
    $clobSql = oci_new_descriptor($conn, OCI_D_LOB);
    if ($clobDet) $clobDet->writeTemporary($det, OCI_TEMP_CLOB);
    if ($clobSql) $clobSql->writeTemporary($sqltxt, OCI_TEMP_CLOB);

    $sqlInsA = "INSERT INTO ".ORA_OWNER.".MON_ALERTA
                (USUARIO, PROCESO, \"SQL\", CONSUMO, DETALLES)
                VALUES (
                  :usr,
                  :pid,
                  :sqltxt,
                  TO_NUMBER(:consS, '9999999990D99', 'NLS_NUMERIC_CHARACTERS=.,'),
                  :det
                )";

    $ia = oci_parse($conn, $sqlInsA);
    oci_bind_by_name($ia, ':usr', $usr);
    oci_bind_by_name($ia, ':pid', $pid);
    if ($clobSql) { oci_bind_by_name($ia, ':sqltxt', $clobSql, -1, OCI_B_CLOB); }
    else          { oci_bind_by_name($ia, ':sqltxt', $sqltxt); }
    oci_bind_by_name($ia, ':consS', $consS);                // string
    if ($clobDet) { oci_bind_by_name($ia, ':det', $clobDet, -1, OCI_B_CLOB); }
    else          { oci_bind_by_name($ia, ':det', $det); }

    if (oci_execute($ia, OCI_NO_AUTO_COMMIT)) {
        $alerta_ok = 1;
    }
    oci_free_statement($ia);
    if ($clobDet) $clobDet->free();
    if ($clobSql) $clobSql->free();
}

// =============================== Commit & out ================================
oci_commit($conn);
oci_close($conn);

echo json_encode(array(
    'ok'               => 1,
    'cliente'          => CLIENTE,
    'pct'              => $pct,
    'umbral'           => UMBRAL,
    'critico'          => ($pct >= UMBRAL ? 1 : 0),
    'snapshot'         => 1,
    'alerta_insertada' => $alerta_ok
), JSON_UNESCAPED_UNICODE);
