<?php
ob_start();
require 'db_connect.php';
require 'spaces_connect.php'; // <-- THIS CONNECTS TO THE CLOUD
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
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($db_date, $db_today);
    $hasRow = $stmt->fetch();
    $stmt->close();

    if (!$hasRow) {
        $db_today = 0; // Starts at 0, we will add +1 below
        $insert = $conn->prepare("INSERT INTO user_counters (user_id, uploads_count, downloads_today, downloads_date) VALUES (?, 0, 0, ?)");
        $insert->bind_param('is', $userId, $today);
        $insert->execute();
        $insert->close();
    } else {
        // Reset counter if it is a new day
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
    $u->bind_param('isi', $new_today_count, $today, $userId); 
    $u->execute(); 
    $u->close();
    
    // --- 2. GLOBAL RESOURCE COUNTER ---
    $filenameOnly = basename($filepath);
    $stmt = $conn->prepare("UPDATE resources SET downloads = downloads + 1 WHERE file_path LIKE CONCAT('%', ?, '%')");
    $stmt->bind_param("s", $filenameOnly);
    $stmt->execute();
    $stmt->close();

    // --- 3. SERVE FILE FROM CLOUD ---
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
}
?>