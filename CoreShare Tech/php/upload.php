<?php
session_start();
require 'db_connect.php';

function redirectWithError($msg) {
    header("Location: ../html/resource.php?error=" . urlencode($msg));
    exit;
}

if (!isset($_SESSION['user_id'])) {
    redirectWithError("Please login to upload files.");
}

// Check for PHP post_max_size overflow (File too big crash)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $maxPost = ini_get('post_max_size');
    redirectWithError("File exceeds server limit ($maxPost). Please upload a smaller file.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // SECURITY: Verify CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        redirectWithError("Security validation failed (CSRF). Please refresh and try again.");
    }

    $title = trim($_POST['title']);
    $course_name = trim($_POST['course_name']);
    $type = $_POST['type'];
    $subject = $_POST['subject'];
    $grade = $_POST['grade'];
    $uploader_id = $_SESSION['user_id'];

    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0755, true);
    
    // Check for upload errors
    if (!isset($_FILES['resource_file']) || $_FILES['resource_file']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $_FILES['resource_file']['error'] ?? 4; 
        $phpFileUploadErrors = [
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        ];
        $errorMsg = $phpFileUploadErrors[$errorCode] ?? 'Unknown upload error';
        redirectWithError($errorMsg);
    }

    $file_name = basename($_FILES["resource_file"]["name"]);
    $file_tmp = $_FILES["resource_file"]["tmp_name"];
    $file_size = $_FILES["resource_file"]["size"];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    $allowed_exts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'md', 'rtf', 'odp'];
    
    if (!in_array($file_ext, $allowed_exts)) {
        redirectWithError("Invalid file type. Allowed: PDF, Word, PowerPoint, Text.");
    }

    if ($file_size > 524288000) { 
        redirectWithError("File is too large (Max 500MB).");
    }

    $new_file_name = time() . "_" . uniqid() . "." . $file_ext;
    $target_file = $target_dir . $new_file_name;

    if (move_uploaded_file($file_tmp, $target_file)) {
        // FIX: Set status to 'pending' so it goes to Moderation Queue
        $stmt = $conn->prepare("INSERT INTO resources (title, course_name, type, subject, grade_level, file_path, uploaded_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("ssssssi", $title, $course_name, $type, $subject, $grade, $target_file, $uploader_id);

        if ($stmt->execute()) {
            $stmt->close();
            // Success message updated to reflect pending status
            header("Location: ../html/resource.php?success=uploaded_pending");
            exit();
        } else {
            if(file_exists($target_file)) unlink($target_file);
            $stmt->close();
            redirectWithError("Database Error: " . $conn->error);
        }
    } else {
        redirectWithError("Failed to move uploaded file.");
    }
}
$conn->close();
?>