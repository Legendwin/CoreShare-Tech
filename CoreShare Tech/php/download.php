<?php
ob_start();
session_start();
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
        
        // Enforce limits
        if ($userPlan === 'free') {
            $today = date('Y-m-d');
            
            // Validate limits against user_counters
            $stmt = $conn->prepare("SELECT downloads_date, downloads_today FROM user_counters WHERE user_id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->bind_result($db_date, $db_today);
            $stmt->fetch();
            $stmt->close();

            if ($db_date !== $today) {
                // Reset limit for a new day
                $db_today = 0;
                $update = $conn->prepare("UPDATE user_counters SET downloads_date = ?, downloads_today = 0 WHERE user_id = ?");
                $update->bind_param('si', $today, $userId); 
                $update->execute(); 
                $update->close();
            }

            $freeDownloadLimit = 5;
            if ($db_today >= $freeDownloadLimit) {
                $conn->close();
                die('Free accounts are limited to ' . $freeDownloadLimit . ' downloads per day. Please <a href="../html/billing.php">upgrade</a>.');
            }

            // Increment daily downloads
            $u = $conn->prepare("UPDATE user_counters SET downloads_today = downloads_today + 1 WHERE user_id = ?");
            $u->bind_param('i', $userId); 
            $u->execute(); 
            $u->close();
        }
        
        // 3. Update Global download count for resource
        $stmt = $conn->prepare("UPDATE resources SET downloads = downloads + 1 WHERE file_path = ?");
        $stmt->bind_param("s", $filepath);
        $stmt->execute();
        $stmt->close();

        // 4. Serve file
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