<?php
// Start output buffering immediately to prevent header errors
ob_start();
session_start();
require 'db_connect.php';

// Clear any existing output (whitespace from includes, etc.)
ob_end_clean();

// 1. Redirect guests
if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.php");
    exit;
}

if (isset($_GET['file'])) {
    $filepath = $_GET['file'];

    // Define the absolute path to the uploads directory
    $baseDir = realpath(__DIR__ . '/../uploads');
    
    // Resolve the absolute path of the requested file
    // Note: $filepath comes from DB as "../uploads/filename.ext"
    $targetPath = realpath(__DIR__ . '/' . $filepath);

    // 2. Security Check: File must exist AND be inside the uploads folder
    if ($targetPath && $baseDir && strpos($targetPath, $baseDir) === 0 && file_exists($targetPath)) {
        
        // 3. Update download count in DB
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
        
        // Final buffer clean before outputting file data
        if (ob_get_level()) ob_end_clean();
        
        readfile($targetPath);
        $conn->close();
        exit;
    } else {
        http_response_code(404);
        $conn->close();
        die("Error: File not found or access denied.");
    }
} else {
    $conn->close();
    die("No file specified.");
}
?>