<?php
session_start();

/*
 * create_dblink.php
 * - Crea (PUBLIC o privado) un DBLINK con los datos del formulario.
 * - Tras crearlo con éxito, registra/actualiza una fila en MON_CONFIG_CLIENTE
 *   para que el monitor lo detecte automáticamente.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ./monitor.php");
    exit;
}

/* === 1) Entradas del formulario === */
$dblink_name = strtoupper(trim($_POST['dblink_name'] ?? ''));
$username    = trim($_POST['username'] ?? '');
$password    = (string)($_POST['password'] ?? '');
$host        = trim($_POST['host'] ?? '');
$port        = trim($_POST['port'] ?? '1521');
$service     = trim($_POST['service'] ?? '');
$sid         = trim($_POST['sid'] ?? '');    // por si algún día agregas un input opcional
$public      = isset($_POST['public']) ? true : false;

/* Validaciones básicas */
if ($dblink_name === '' || $username === '' || $password === '' || $host === '' || $port === '' || ($service === '' && $sid === '')) {
    $_SESSION['error_message'] = "Faltan datos para crear el DB Link.";
    header("Location: ./monitor.php");
    exit;
}

/* Nombre de DBLINK: sólo letras, números, _ y $ para evitar problemas (Oracle suele usarlos en mayúsculas) */
if (!preg_match('/^[A-Z0-9_#\$]+$/', $dblink_name)) {
    $_SESSION['error_message'] = "Nombre de DB Link inválido. Usa sólo A-Z, 0-9, _, # o $.";
    header("Location: ./monitor.php");
    exit;
}

/* Escapar comillas en password para IDENTIFIED BY "..." */
$password_escaped = str_replace('"', '""', $password);

/* === 2) Construir CONNECT_DATA y descriptor === */
if ($service !== '') {
    $connect_data = "(SERVICE_NAME=$service)";
} else {
    // fallback si decides usar SID
    $connect_data = "(SID=$sid)";
}

/* Agregamos timeouts para que no se cuelgue cuando el destino no responde */
$conn_str = "(DESCRIPTION="
    . "(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))"
    . "(CONNECT_DATA=$connect_data)"
    . "(CONNECT_TIMEOUT=3)(RETRY_COUNT=1)(RETRY_DELAY=0)"
    . ")";

/* === 3) Conexión local con las credenciales de sesión === */
$conn = @oci_connect($_SESSION['username'] ?? '', $_SESSION['password'] ?? '', 'localhost/XEPDB1');
if (!$conn) {
    $e = oci_error();
    $_SESSION['error_message'] = "No se pudo conectar a Oracle local: " . ($e['message'] ?? '');
    header("Location: ./monitor.php");
    exit;
}

/* Timeout por llamada OCI (si está disponible) */
if (function_exists('oci_set_call_timeout')) {
    oci_set_call_timeout($conn, 5000); // 5s
}

/* === 4) Crear el DBLINK === */
$sql = "CREATE " . ($public ? "PUBLIC " : "") . "DATABASE LINK $dblink_name
        CONNECT TO $username IDENTIFIED BY \"$password_escaped\"
        USING '$conn_str'";

$stid = oci_parse($conn, $sql);
$ok   = @oci_execute($stid);

/* Manejo de error al crear el DBLINK */
if (!$ok) {
    $e = oci_error($stid);
    $_SESSION['error_message'] = "Error creando DB Link $dblink_name: " . ($e['message'] ?? '');
    if ($stid) oci_free_statement($stid);
    oci_close($conn);
    header("Location: ./monitor.php");
    exit;
}

/* Liberar statement de creación */
if ($stid) oci_free_statement($stid);

/* === 5) Registrar/actualizar en MON_CONFIG_CLIENTE (MERGE) ===
 * Para esto necesitamos saber el OWNER donde viven las tablas del monitor.
 * Usamos ORA_OWNER de tu config si existe; si no, asumimos el usuario de sesión.
 */
$owner = null;
$cfg   = realpath(__DIR__ . '/../../config/monitor_config.php');
if ($cfg && file_exists($cfg)) {
    require_once $cfg;
    if (defined('ORA_OWNER')) {
        $owner = ORA_OWNER;
    }
}
if ($owner === null || $owner === '') {
    $owner = strtoupper($_SESSION['username'] ?? '');
}

/* Valores por defecto para la fila de config */
$cliente_default = $dblink_name;   // puedes cambiarlo a algo más descriptivo luego en la UI
$alias_default   = $dblink_name;
$umbral_default  = 85;
$reload_default  = 5;

/* MERGE: si no existe lo inserta; si existe no lo pisa (puedes ajustar a tu gusto) */
$sqlCfg = "MERGE INTO {$owner}.MON_CONFIG_CLIENTE t
           USING (SELECT :dblink dblink FROM dual) s
           ON (t.DBLINK = s.dblink)
           WHEN NOT MATCHED THEN
             INSERT (DBLINK, CLIENTE, UMBRAL_PCT, RELOAD_SEC, HABILITADO, ALIAS)
             VALUES (:dblink, :cliente, :umbral, :reload, 'Y', :alias)";

$stid2 = oci_parse($conn, $sqlCfg);
oci_bind_by_name($stid2, ':dblink',  $dblink_name);
oci_bind_by_name($stid2, ':cliente', $cliente_default);
oci_bind_by_name($stid2, ':umbral',  $umbral_default);
oci_bind_by_name($stid2, ':reload',  $reload_default);
oci_bind_by_name($stid2, ':alias',   $alias_default);

$ok_cfg = @oci_execute($stid2);
if (!$ok_cfg) {
    $e = oci_error($stid2);
    // No frenamos la navegación; dejamos el mensaje para que lo revises
    $_SESSION['error_message'] = "DB Link creado, pero no se registró en MON_CONFIG_CLIENTE: " . ($e['message'] ?? '');
} else {
    $_SESSION['success_message'] = "DB Link $dblink_name creado correctamente y registrado en monitores.";
}

if ($stid2) oci_free_statement($stid2);

/* === 6) Cerrar y volver a la página === */
oci_close($conn);
header("Location: ./monitor.php");
exit;
