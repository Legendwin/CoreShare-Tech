<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

// 2. Handle Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(["success" => false, "message" => "Security check failed. Refresh page."]);
        exit;
    }

    $resource_id = intval($_POST['resource_id']);
    $title = trim($_POST['title']);
    $course_name = trim($_POST['course_name']);
    $type = $_POST['type'];
    $subject = $_POST['subject'];
    $grade = $_POST['grade'];
    $user_id = $_SESSION['user_id'];

    if (empty($title) || empty($subject) || empty($type)) {
        echo json_encode(["success" => false, "message" => "Required fields missing."]);
        exit;
    }

    // 3. Update Database (Ensure user owns the resource)
    $sql = "UPDATE resources SET title=?, course_name=?, type=?, subject=?, grade_level=? 
            WHERE id=? AND uploaded_by=?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssii", $title, $course_name, $type, $subject, $grade, $resource_id, $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows >= 0) { // 0 means no changes made, but query successful
            echo json_encode(["success" => true, "message" => "Resource updated successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Update failed or permission denied"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}
$conn->close();
?>