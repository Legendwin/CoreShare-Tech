<?php
require 'db_connect.php';
require 'spaces_connect.php'; // ADDED: Connect to the Cloud

function redirectWithError($msg) {
    header("Location: ../html/resource.php?error=" . urlencode($msg));
    exit;
}

if (!isset($_SESSION['user_id'])) {
    redirectWithError("Please login to upload files.");
}

$userPlan = $_SESSION['plan'] ?? 'free';
$uploader_id = intval($_SESSION['user_id']);

// Enforce plan upload limits
if ($userPlan === 'free') {
    $countRes = $conn->prepare("SELECT uploads_count FROM user_counters WHERE user_id = ? LIMIT 1");
    $countRes->bind_param('i', $uploader_id);
    $countRes->execute();
    $countRes->bind_result($uploadedCount);
    $countRes->fetch();
    $countRes->close();

    $freeUploadLimit = 5; 
    if ($uploadedCount >= $freeUploadLimit) {
        redirectWithError("Free accounts can upload up to $freeUploadLimit resources. Please upgrade to Pro to upload more.");
    }
}

// Check for PHP post_max_size overflow
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $maxPost = ini_get('post_max_size');
    redirectWithError("File exceeds server limit ($maxPost). Please upload a smaller file.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        redirectWithError("Security validation failed (CSRF). Please refresh and try again.");
    }

    $title = trim($_POST['title']);
    $course_name = trim($_POST['course_name']);
    $type = $_POST['type'];
    $programme = $_POST['programme'];
    $grade = $_POST['grade_level'];

    // --- Check if a resource with this title already exists ---
    $checkStmt = $conn->prepare("SELECT id FROM resources WHERE title = ? LIMIT 1");
    $checkStmt->bind_param("s", $title);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        $checkStmt->close();
        redirectWithError("A resource with this title already exists. Please change the file name/title and try uploading again.");
    }
    $checkStmt->close();

    // Check for upload errors
    if (!isset($_FILES['resource_file']) || $_FILES['resource_file']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $_FILES['resource_file']['error'] ?? 4; 
        redirectWithError("Upload Error Code: " . $errorCode);
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

    // Generate a unique file name and define the Cloud path
    $new_file_name = time() . "_" . uniqid() . "." . $file_ext;
    $s3_key = "uploads/" . $new_file_name; // We will save this exact path in the DB

    try {
        // BEAM THE FILE TO THE CLOUD LOCKER
        $s3->putObject([
            'Bucket'     => $spaces_bucket,
            'Key'        => $s3_key,
            'SourceFile' => $file_tmp,
            'ACL'        => 'private', // Critical: Keep the file private so users can't bypass your app!
        ]);

        // Save the cloud path into the database
        $stmt = $conn->prepare("INSERT INTO resources (title, course_name, type, programme, grade_level, file_path, uploaded_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("ssssssi", $title, $course_name, $type, $programme, $grade, $s3_key, $uploader_id);

        if ($stmt->execute()) {
            $stmt->close();
            // Increment uploads_count efficiently
            $uup = $conn->prepare("UPDATE user_counters SET uploads_count = uploads_count + 1 WHERE user_id = ?");
            $uup->bind_param('i', $uploader_id);
            $uup->execute();
            $uup->close();
            
            header("Location: ../html/resource.php?success=uploaded_pending");
            exit();
        } else {
            // If the DB fails, delete the file from the cloud so we don't waste space
            $s3->deleteObject(['Bucket' => $spaces_bucket, 'Key' => $s3_key]);
            redirectWithError("Database Error: " . $conn->error);
        }
    } catch (Aws\Exception\AwsException $e) {
        redirectWithError("Cloud Storage Error: Could not upload file.");
    }
}
$conn->close();
?>