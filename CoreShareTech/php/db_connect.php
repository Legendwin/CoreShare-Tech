<?php
// ---------------------------------------------------------
// 1. CLOUD VS LOCAL DATABASE CONNECTION (MUST HAPPEN FIRST)
// ---------------------------------------------------------
$db_url = getenv('DATABASE_URL');

if ($db_url) {
    // We are on DigitalOcean - Parse the provided database URL
    $db = parse_url($db_url);
    $servername = $db['host'];
    $username = $db['user'];
    $password = $db['pass'];
    $dbname = ltrim($db['path'], '/');
    $port = $db['port'] ?? 25060;
    
    // Connect to DigitalOcean Database
    $conn = mysqli_init();
    $conn->real_connect($servername, $username, $password, $dbname, $port);
} else {
    // We are on Localhost (XAMPP/WAMP)
    $servername = "localhost";
    $username = "root"; 
    $password = ""; 
    $dbname = "coreshare_db";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---------------------------------------------------------
// 2. INJECT DATABASE SESSION HANDLER
// ---------------------------------------------------------
// Require the handler class we just created
require_once 'session_handler.php';

// Instantiate the handler with our active database connection
$handler = new DatabaseSessionHandler($conn);

// Tell PHP to use this class for all session data
session_set_save_handler($handler, true);

// NOW we can safely start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------
// 3. SECURITY: CSRF Token Generation
// ---------------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ---------------------------------------------------------
// 4. AUTOMATIC SESSION TIMEOUT (30 Minutes)
// ---------------------------------------------------------
if (isset($_SESSION['user_id'])) {
    $timeout_duration = 1800; // 30 minutes in seconds

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
        session_unset();     
        session_destroy();   
        header("Location: ../html/login.php?error=" . urlencode("Session expired due to inactivity. Please log in again."));
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}
?>