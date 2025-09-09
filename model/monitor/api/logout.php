<?php
session_start();
session_unset();
header("Location: ../../../views/monitor/index.php");
exit;