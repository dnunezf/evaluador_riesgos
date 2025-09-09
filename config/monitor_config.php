<?php
// monitor_config.php — OWNER y conexión en XEPDB1 con SYSTEM

define('ORA_OWNER', 'SYSTEM');                 // Las tablas viven en SYSTEM (XEPDB1)
define('ORA_USER',  'SYSTEM');                 // Usuario técnico
define('ORA_PASS',  'root');     // <-- AJUSTA AQUÍ
define('ORA_DSN',   'localhost:1521/XEPDB1');  // EZCONNECT a la PDB XEPDB1

define('ORA_CALL_TIMEOUT_MS', 5000);
define('ORA_PREFER_SESSION_CREDS', false);     // Forzar siempre usuario técnico

function ora_conn() {
    $c = @oci_connect(ORA_USER, ORA_PASS, ORA_DSN);
    if ($c && function_exists('oci_set_call_timeout') && ORA_CALL_TIMEOUT_MS > 0) {
        @oci_set_call_timeout($c, ORA_CALL_TIMEOUT_MS);
    }
    return $c;
}

function ora_username($conn) {
    $st = @oci_parse($conn, "SELECT USER FROM dual");
    if ($st && @oci_execute($st)) {
        $r = oci_fetch_array($st, OCI_NUM);
        if ($r && isset($r[0])) return $r[0];
    }
    return null;
}
