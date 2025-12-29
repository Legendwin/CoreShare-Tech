<?php
ob_clean();
header('Content-Type: application/json');
session_start();
require 'db_connect.php';

$currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$resource = null;

// 1. Get Resource (Prepared Statement)
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM resources WHERE id = ? LIMIT 1");
    $id = intval($_GET['id']);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) $resource = $result->fetch_assoc();
    $stmt->close();
} elseif (isset($_GET['title'])) {
    $stmt = $conn->prepare("SELECT * FROM resources WHERE title = ? LIMIT 1");
    $title = $_GET['title'];
    $stmt->bind_param("s", $title);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) $resource = $result->fetch_assoc();
    $stmt->close();
}

if ($resource) {
    $resourceId = $resource['id'];

    // 2. Get Reviews (Prepared Statement)
    $stmtRev = $conn->prepare("SELECT r.id, r.user_id, r.rating, r.comment, r.created_at, u.full_name 
                               FROM reviews r 
                               JOIN users u ON r.user_id = u.id 
                               WHERE r.resource_id = ? 
                               ORDER BY r.created_at DESC");
    $stmtRev->bind_param("i", $resourceId);
    $stmtRev->execute();
    $reviewResult = $stmtRev->get_result();
    
    $reviews = [];
    while($row = $reviewResult->fetch_assoc()) {
        $reviews[] = $row;
    }
    $stmtRev->close();

    echo json_encode([
        "success" => true,
        "resource" => $resource,
        "reviews" => $reviews,
        "current_user_id" => $currentUserId
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Resource not found"]);
}
$conn->close();
?>