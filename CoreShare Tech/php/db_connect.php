<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------
// SECURITY: CSRF Token Generation
// ---------------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "coreshare_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---------------------------------------------------------
// AUTOMATIC SESSION TIMEOUT (30 Minutes)
// ---------------------------------------------------------
if (isset($_SESSION['user_id'])) {
    $timeout_duration = 1800; // 30 minutes in seconds

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
        session_unset();     
        session_destroy();   
        header("Location: ../html/login.php?timeout=1"); 
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}