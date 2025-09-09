<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dblink_name'])) {
    $dblink_name = strtoupper(trim($_POST['dblink_name']));

    $conn = oci_connect($_SESSION['username'], $_SESSION['password'], 'localhost/XEPDB1');
    if (!$conn) {
        $e = oci_error();
        die("Connection failed: " . $e['message']);
    }

    $sql = "DROP DATABASE LINK $dblink_name";
    $stid = oci_parse($conn, $sql);
    $ok = @oci_execute($stid);

    
    if (!$ok) {
        $sql = "DROP PUBLIC DATABASE LINK $dblink_name";
        $stid = oci_parse($conn, $sql);
        $ok = @oci_execute($stid);
        if (!$ok) {
        $e = oci_error($stid);
        $_SESSION['error_message'] = "Failed to drop DB link $dblink_name: " . $e['message'];
        } else {
            $_SESSION['success_message'] = "DB PUBLIC link $dblink_name deleted successfully.";
        }

    } else {
        $_SESSION['success_message'] = "DB link $dblink_name deleted successfully.";
    }

    if ($stid) oci_free_statement($stid);
    oci_close($conn);

    header("Location: ./monitor.php");
    exit;

} else {
    header("Location: ./monitor.php");
    exit;
}
?>
