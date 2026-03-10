<?php
ob_start();
// TEMPORARY: Turn on Error Reporting so we can see if the Database crashes!
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db_connect.php';
require 'spaces_connect.php'; // Connect to DigitalOcean
ob_end_clean();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.php");
    exit;
}

if (isset($_GET['file'])) {
    $filepath = $_GET['file'];
    $s3_key = ltrim($filepath, './'); 

    $userPlan = $_SESSION['plan'] ?? 'free';
    $userId = intval($_SESSION['user_id']);
    
    // --- 1. DAILY TRACKING (For ALL Users) ---
    $today = date('Y-m-d');
    
    $stmt = $conn->prepare("SELECT downloads_date, downloads_today FROM user_counters WHERE user_id = ?");
    if (!$stmt) die("Database Error 1: " . $conn->error);
    
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($db_date, $db_today);
    $hasRow = $stmt->fetch();
    $stmt->close();

    if (!$hasRow) {
        $db_today = 0; 
        $insert = $conn->prepare("INSERT INTO user_counters (user_id, uploads_count, downloads_today, downloads_date) VALUES (?, 0, 0, ?)");
        if (!$insert) die("Database Error 2: " . $conn->error);
        $insert->bind_param('is', $userId, $today);
        $insert->execute();
        $insert->close();
    } else {
        if ($db_date !== $today) {
            $db_today = 0;
        }
    }

    // --- 2. ENFORCE LIMITS (Only for Free Users) ---
    $freeDownloadLimit = 5;
    if ($userPlan === 'free' && $db_today >= $freeDownloadLimit) {
        $conn->close();
        die('Free accounts are limited to ' . $freeDownloadLimit . ' downloads per day. Please <a href="../html/billing.php">upgrade</a>.');
    }

    // --- 3. INCREMENT DAILY COUNTER (For EVERYONE) ---
    $new_today_count = $db_today + 1;
    $u = $conn->prepare("UPDATE user_counters SET downloads_today = ?, downloads_date = ? WHERE user_id = ?");
    if (!$u) die("Database Error 3: " . $conn->error);
    $u->bind_param('isi', $new_today_count, $today, $userId); 
    $u->execute(); 
    $u->close();

    // --- 4. GLOBAL RESOURCE COUNTER ---
    $filenameOnly = basename($filepath);
    $stmtRes = $conn->prepare("UPDATE resources SET downloads = downloads + 1 WHERE file_path LIKE CONCAT('%', ?, '%')");
    if (!$stmtRes) die("Database Error 4: " . $conn->error);
    $stmtRes->bind_param("s", $filenameOnly);
    $stmtRes->execute();
    $stmtRes->close();

    // --- 5. USER LIFETIME DOWNLOAD COUNTER ---
    $stmtUser = $conn->prepare("UPDATE users SET total_downloads = total_downloads + 1 WHERE id = ?");
    // If you forgot to run db_update.php, this next line will catch the crash!
    if (!$stmtUser) die("Database Error 5 (Missing Column): " . $conn->error); 
    
    $stmtUser->bind_param("i", $userId);
    $stmtUser->execute();
    $stmtUser->close();

    // --- 6. SERVE FILE FROM CLOUD ---
    try {
        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $spaces_bucket,
            'Key'    => $s3_key,
            'ResponseContentDisposition' => 'attachment'
        ]);

        $request = $s3->createPresignedRequest($cmd, '+15 minutes');
        $presignedUrl = (string) $request->getUri();

        header("Location: " . $presignedUrl);
        exit;
        
    } catch (\Throwable $e) {
        http_response_code(404);
        die("Error: File not found in Cloud Storage.");
    }
} else {
    die("Error: No file specified.");
}
?>