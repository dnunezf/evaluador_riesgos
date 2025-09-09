<?php
session_start();

// Capture form input
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Oracle connection string
$dsn = "(DESCRIPTION =
    (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521))
    (CONNECT_DATA = (SERVICE_NAME = XEPDB1))
)";

// Try connecting with provided username/password
$conn = @oci_connect($username, $password, $dsn);

if ($conn) {
    // Success → save session
    $_SESSION['username'] = $username;
    $_SESSION['password'] = $password;

    // Optionally test query
    $stmt = oci_parse($conn, "SELECT 'Connected as ' || USER FROM dual");
    oci_execute($stmt);
    $row = oci_fetch_array($stmt, OCI_ASSOC);
    $_SESSION['welcome'] = $row['CONNECTEDASUSER'] ?? "Connected";

    // Close connection (reconnect later with stored session if needed)
    oci_close($conn);

    header("Location: ../../../views/monitor/monitor.php");
    exit;
} else {
    // Failed → show error
     $e = oci_error();
    $_SESSION['error_message'] = "Login failed: " . htmlentities($e['message']);
    header("Location: ../../../views/monitor/index.php");
    exit;
}
?>
