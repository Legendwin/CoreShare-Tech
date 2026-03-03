<?php
ob_clean(); 
header('Content-Type: application/json');

session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// FIXED: CSRF Check
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(["success" => false, "message" => "Security check failed"]);
    exit;
}

if (isset($data['id'])) {
    $resourceId = intval($data['id']);
    $userId = $_SESSION['user_id'];

    $query = "SELECT id, file_path FROM resources WHERE id = ? AND uploaded_by = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $resourceId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $filePath = $row['file_path'];

        $fullPath = __DIR__ . "/" . $filePath; 
        
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $deleteStmt = $conn->prepare("DELETE FROM resources WHERE id = ?");
        $deleteStmt->bind_param("i", $resourceId);
        
        if ($deleteStmt->execute()) {
            echo json_encode(["success" => true, "message" => "Resource deleted successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database delete failed"]);
        }
        $deleteStmt->close();

    } else {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Permission denied or file not found."]);
    }
    $stmt->close();

} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid Request"]);
}

$conn->close();
?>