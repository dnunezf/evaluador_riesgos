<?php
// config_oracle.php — Ajusta DSN/usuario/clave y el esquema dueño de las tablas.
const ORA_DSN   = 'localhost:1521/XEPDB1';   // o tu servicio: localhost:1521/ORCLPDB1
const ORA_USER  = 'SYS';                     // si creaste tablas como SYS (académico)
const ORA_PASS  = 'root';
const ORA_ROLE  = 'AS SYSDBA';               // si usas SYS
const ORA_OWNER = 'SYS';                     // esquema dueño de MON_BUFFER_SNAPSHOT

function ora_conn() {
    // Conexión con rol (SYSDBA) si aplica, si no, usa oci_connect(ORA_USER, ORA_PASS, ORA_DSN)
    $c = @oci_pconnect(ORA_USER, ORA_PASS, ORA_DSN, 'AL32UTF8', ORA_ROLE);
    if (!$c) {
        $e = oci_error();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Oracle connect failed', 'detail' => $e['message'] ?? '']);
        exit;
    }
    return $c;
}
