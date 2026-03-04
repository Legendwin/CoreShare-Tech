<?php
require 'db_connect.php';

header('Content-Type: application/json');

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

// Read the incoming JSON payload (Because JS fetch uses JSON.stringify)
$data = json_decode(file_get_contents("php://input"), true);

// 2. Handle Request
if ($data) {
    // Validate CSRF
    if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(["success" => false, "message" => "Security check failed. Refresh page."]);
        exit;
    }

    // Map the correct variable names sent from the JS fetch request
    $resource_id = intval($data['id']);
    $title = trim($data['title']);
    $course_name = trim($data['course_name']);
    $type = $data['type'];
    $programme = $data['programme'];
    $grade = $data['grade_level'];
    $user_id = $_SESSION['user_id'];

    if (empty($title) || empty($programme) || empty($type)) {
        echo json_encode(["success" => false, "message" => "Required fields missing."]);
        exit;
    }

    // 3. Update Database (Ensure user owns the resource)
    $sql = "UPDATE resources SET title=?, course_name=?, type=?, programme=?, grade_level=? 
            WHERE id=? AND uploaded_by=?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssii", $title, $course_name, $type, $programme, $grade, $resource_id, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Resource updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request format. Expected JSON."]);
}
$conn->close();
?>