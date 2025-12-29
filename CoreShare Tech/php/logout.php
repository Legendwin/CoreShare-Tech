<?php
session_start();
// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to Guest Dashboard
header("Location: ../html/index.php?success=logged_out");
exit;
?>