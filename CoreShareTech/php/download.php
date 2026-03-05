<?php
ob_start();
require 'db_connect.php';
require 'spaces_connect.php'; // ADDED: Connect to the Cloud
ob_end_clean();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.php");
    exit;
}

if (isset($_GET['file'])) {
    // Strip out any old "../" paths from older database entries
    $filepath = $_GET['file'];
    $s3_key = ltrim($filepath, './'); 

    $userPlan = $_SESSION['plan'] ?? 'free';
    $userId = intval($_SESSION['user_id']);
    
    // Enforce limits
    if ($userPlan === 'free') {
        $today = date('Y-m-d');
        
        $stmt = $conn->prepare("SELECT downloads_date, downloads_today FROM user_counters WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($db_date, $db_today);
        $stmt->fetch();
        $stmt->close();

        if ($db_date !== $today) {
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

    try {
        // 4. GENERATE THE SECURE PRE-SIGNED URL (Valid for 15 minutes)
        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $spaces_bucket,
            'Key'    => $s3_key,
            'ResponseContentDisposition' => 'attachment' // Forces the browser to download it
        ]);

        $request = $s3->createPresignedRequest($cmd, '+15 minutes');
        $presignedUrl = (string) $request->getUri();

        // 5. Redirect the user's browser to the secure cloud link!
        header("Location: " . $presignedUrl);
        exit;
        
    } catch (Aws\Exception\AwsException $e) {
        http_response_code(404);
        die("Error: File not found in Cloud Storage.");
    }
}
?>