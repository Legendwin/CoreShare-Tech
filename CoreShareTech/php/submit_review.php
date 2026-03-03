<?php
ob_clean(); 
header('Content-Type: application/json');

session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['resource_id']) && isset($data['rating']) && isset($data['comment'])) {
    $resourceId = intval($data['resource_id']);
    $rating = intval($data['rating']);
    $comment = trim($data['comment']);
    $userId = $_SESSION['user_id'];

    if ($rating < 1 || $rating > 5) {
        echo json_encode(["success" => false, "message" => "Invalid rating"]);
        exit;
    }

    // Use Prepared Statement
    $stmt = $conn->prepare("INSERT INTO reviews (resource_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $resourceId, $userId, $rating, $comment);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Database Error"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Missing data"]);
}
$conn->close();
?>