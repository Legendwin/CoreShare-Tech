<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Verify CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(["success" => false, "message" => "Security token invalid."]);
        exit;
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(["success" => false, "message" => "Please fill in all required fields."]);
        exit;
    }

    // 2. Insert into DB
    $stmt = $conn->prepare("INSERT INTO messages (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $name, $email, $subject, $message);

    if ($stmt->execute()) {
        // 3. Send Email to Admin (NEW)
        $adminEmail = "admin@coresharetech.com"; // Replace with real admin email
        $emailSubject = "New Contact Message: " . $subject;
        $emailBody = "You have received a new message from $name ($email).\n\n" .
                     "Subject: $subject\n\n" .
                     "Message:\n$message\n\n" .
                     "Logged In User ID: " . ($userId ? $userId : "Guest");
        
        $headers = "From: no-reply@coresharetech.com\r\n";
        $headers .= "Reply-To: $email\r\n";
        
        // Use @ to suppress errors in local environments without mail server
        @mail($adminEmail, $emailSubject, $emailBody, $headers);

        // Log for testing
        $logMessage = "TO: $adminEmail\nSUBJECT: $emailSubject\nMESSAGE: $emailBody\nHEADERS: $headers\n\n";
        file_put_contents("../contact_log.txt", $logMessage, FILE_APPEND);

        echo json_encode(["success" => true, "message" => "Message sent successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    }
    $stmt->close();
}
$conn->close();
?>