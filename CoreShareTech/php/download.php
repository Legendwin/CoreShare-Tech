<?php
ob_start();
require 'db_connect.php';
ob_end_clean();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.php");
    exit;
}

if (isset($_GET['file'])) {
    $filepath = $_GET['file'];
    $baseDir = realpath(__DIR__ . '/../uploads');
    $targetPath = realpath(__DIR__ . '/' . $filepath);

    if ($targetPath && $baseDir && strpos($targetPath, $baseDir) === 0 && file_exists($targetPath)) {
        
        $userPlan = $_SESSION['plan'] ?? 'free';
        $userId = intval($_SESSION['user_id']);
        
        // --- 1. ENFORCE & COUNT DAILY LIMITS (Bulletproof) ---
        if ($userPlan === 'free') {
            $today = date('Y-m-d');
            
            // Check current stats
            $stmt = $conn->prepare("SELECT downloads_date, downloads_today FROM user_counters WHERE user_id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->bind_result($db_date, $db_today);
            $hasRow = $stmt->fetch();
            $stmt->close();

            $freeDownloadLimit = 5;

            if (!$hasRow) {
                // If user is brand new and missing from the table, CREATE them with 1 download
                $db_today = 1;
                $insert = $conn->prepare("INSERT INTO user_counters (user_id, uploads_count, downloads_today, downloads_date) VALUES (?, 0, 1, ?)");
                $insert->bind_param('is', $userId, $today);
                $insert->execute();
                $insert->close();
            } else {
                // If it's a new day, reset to 0
                if ($db_date !== $today) {
                    $db_today = 0;
                }

                // Check limit
                if ($db_today >= $freeDownloadLimit) {
                    $conn->close();
                    die('Free accounts are limited to ' . $freeDownloadLimit . ' downloads per day. Please <a href="../html/billing.php">upgrade</a>.');
                }

                // Increment their count for today
                $u = $conn->prepare("UPDATE user_counters SET downloads_today = downloads_today + 1, downloads_date = ? WHERE user_id = ?");
                $u->bind_param('si', $today, $userId); 
                $u->execute(); 
                $u->close();
            }
        }
        
        // --- 2. UPDATE GLOBAL RESOURCE DOWNLOADS (Bulletproof) ---
        // We strip the folder paths and match ONLY the filename to prevent mismatch errors
        $filenameOnly = basename($filepath);
        $stmt = $conn->prepare("UPDATE resources SET downloads = downloads + 1 WHERE file_path LIKE CONCAT('%', ?, '%')");
        $stmt->bind_param("s", $filenameOnly);
        $stmt->execute();
        $stmt->close();

        // --- 3. SERVE THE FILE ---
        $fileName = basename($targetPath);
        $fileSize = filesize($targetPath);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $fileSize);
        
        if (ob_get_level()) ob_end_clean();
        readfile($targetPath);
        $conn->close();
        exit;
    } else {
        http_response_code(404);
        die("Error: File not found or access denied.");
    }
}
?>