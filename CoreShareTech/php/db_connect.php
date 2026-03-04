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

// ---------------------------------------------------------
// CLOUD VS LOCAL DATABASE CONNECTION
// ---------------------------------------------------------
$db_url = getenv('DATABASE_URL');

if ($db_url) {
    // We are on DigitalOcean - Parse the provided database URL
    $db = parse_url($db_url);
    $servername = $db['host'];
    $username = $db['user'];
    $password = $db['pass'];
    $dbname = ltrim($db['path'], '/');
    $port = $db['port'];
    
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
?>