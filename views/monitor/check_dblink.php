<?php
session_start();
header('Content-Type: application/json');

// Limitar tiempo total del script (defensa extra)
set_time_limit(10);

// Validaciones básicas
if (!isset($_SESSION['username'], $_SESSION['password'])) {
    echo json_encode(["online" => false, "error" => "Not logged in"]);
    exit;
}

if (!isset($_GET['dblink']) || trim($_GET['dblink']) === '') {
    echo json_encode(["online" => false, "error" => "No DB link specified"]);
    exit;
}

$dblink = trim($_GET['dblink']);

// Conexión a Oracle (local)
$conn = @oci_connect($_SESSION['username'], $_SESSION['password'], 'localhost/XEPDB1');
if (!$conn) {
    $e = oci_error();
    echo json_encode([
        "online" => false,
        "error"  => "DB connection failed",
        "detail" => $e['message'] ?? null
    ]);
    exit;
}

// ⏱️ Timeout por llamada OCI (milisegundos). Evita cuelgues en DBLINKs caídos.
if (function_exists('oci_set_call_timeout')) {
    // 3000 ms = 3 s. Podés subir/bajar este valor si lo necesitás.
    oci_set_call_timeout($conn, 3000);
}

$ok = false;
$detail = null;

try {
    // Preparar ping vía DBLINK
    $sql  = "SELECT 1 FROM dual@" . $dblink;
    $stid = @oci_parse($conn, $sql);

    if ($stid) {
        // Ejecutar sin autocommit para que corte rápido si hay timeout
        $ok = @oci_execute($stid, OCI_NO_AUTO_COMMIT);
        if (!$ok) {
            $e = oci_error($stid);
            $detail = $e['message'] ?? 'Unknown error';
        }
        oci_free_statement($stid);
    } else {
        $e = oci_error($conn);
        $detail = $e['message'] ?? 'Parse failed';
    }
} catch (Throwable $ex) {
    $ok = false;
    $detail = $ex->getMessage();
} finally {
    oci_close($conn);
}

// Respuesta
echo json_encode([
    "online" => (bool)$ok,
    // Podés comentar 'detail' en prod si no querés exponer mensajes
    "error"  => $ok ? null : "DBLINK check failed",
    "detail" => $ok ? null : $detail
], JSON_UNESCAPED_UNICODE);