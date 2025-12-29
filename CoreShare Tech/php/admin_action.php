<?php
// Clear output buffer to prevent PHP warnings breaking JSON
ob_clean(); 
header('Content-Type: application/json');

session_start();
require 'db_connect.php';

// Check Admin Role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id']) && isset($data['action'])) {
    $id = intval($data['id']);
    $action = $data['action']; // 'published' or 'rejected'
    
    if ($action !== 'published' && $action !== 'rejected') {
        echo json_encode(["success" => false, "message" => "Invalid action state"]);
        exit;
    }

    // 1. Fetch User Email and Resource Title (For notification)
    $userQuery = "SELECT u.email, u.full_name, r.title 
                  FROM resources r 
                  JOIN users u ON r.uploaded_by = u.id 
                  WHERE r.id = ?";
    
    $stmtUser = $conn->prepare($userQuery);
    $stmtUser->bind_param("i", $id);
    $stmtUser->execute();
    $userData = $stmtUser->get_result()->fetch_assoc();
    $stmtUser->close();

    // 2. Update Status in DB
    $stmt = $conn->prepare("UPDATE resources SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $action, $id);

    if ($stmt->execute()) {
        // 3. Try Sending Email (Only if configured)
        if ($userData) {
            $to = $userData['email'];
            $subject = "CoreShare Tech: Resource " . ucfirst($action);
            $message = "Hello " . $userData['full_name'] . ",\n\n" .
                       "Your resource '" . $userData['title'] . "' has been reviewed.\n" .
                       "Status: " . ucfirst($action);
            $headers = "From: no-reply@coresharetech.com";
            
            // Suppress error warnings if mail server isn't set up (common on localhost)
            @mail($to, $subject, $message, $headers); 

            // Log for testing
            $logMessage = "TO: $to\nSUBJECT: $subject\nMESSAGE: $message\nHEADERS: $headers\n\n";
            file_put_contents("../action_log.txt", $logMessage, FILE_APPEND);
        }
        
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Database Update Failed: " . $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid parameters"]);
}
$conn->close();
?>