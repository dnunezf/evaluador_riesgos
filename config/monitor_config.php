<?php
// config_oracle.php — Ajusta DSN/usuario/clave y el esquema dueño de las tablas.
//const ORA_DSN   = 'localhost:1521/XEPDB1';   // o tu servicio: localhost:1521/ORCLPDB1
//const ORA_USER  = 'SYS';                     // si creaste tablas como SYS (académico)
//const ORA_PASS  = 'root';
//const ORA_ROLE  = 'AS SYSDBA';               // si usas SYS
//const ORA_OWNER = 'SYS';                     // esquema dueño de MON_BUFFER_SNAPSHOT


// config/monitor_config.php — conexión del monitor a Oracle

// Datos de conexión a tu PDB local
const ORA_DSN   = 'localhost:1521/xepdb1';   // servicio que viste en lsnrctl status
const ORA_USER  = 'CLIENTE_SIM';             // tu usuario normal (ojo: en mayúsculas)
const ORA_PASS  = 'ClaveSim#2025';           // contraseña de cliente_sim
const ORA_OWNER = 'SYS';             // dueño de MON_BUFFER_SNAPSHOT y MON_ALERTA

function ora_conn() {
    // Conexión normal SIN SYSDBA
    $c = @oci_pconnect(ORA_USER, ORA_PASS, ORA_DSN, 'AL32UTF8');
    if (!$c) {
        $e = oci_error();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error'  => 'Oracle connect failed',
            'detail' => $e['message'] ?? ''
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    return $c;
}

