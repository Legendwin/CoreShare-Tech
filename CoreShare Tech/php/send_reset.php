<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email']; // Prepared statement handles escaping

    // 1. Check if user exists
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 2. Generate secure token
        $token = bin2hex(random_bytes(32));
        $token_hash = hash("sha256", $token); // Store hash in DB
        $expiry = date("Y-m-d H:i:s", time() + 60 * 15); // 15 mins expiry

        // 3. Store in DB
        $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $updateStmt->bind_param("sss", $token_hash, $expiry, $email);
        
        if ($updateStmt->execute()) {
            // 4. Send Email (Simulated)
            // Note: In production, use $_SERVER['HTTP_HOST']
            $resetLink = "http://localhost/CoreShare%20Tech/html/reset.php?token=$token&email=" . urlencode($email);
            
            $subject = "Password Reset Request";
            $message = "Click here to reset: $resetLink\nThis link expires in 15 minutes.";
            $headers = "From: no-reply@coresharetech.com";

            // Suppress error warnings if mail server isn't set up (common on localhost)
            @mail($email, $subject, $message, $headers);
            
            // Log for testing
            $logMessage = "TO: $email\nSUBJECT: $subject\nMESSAGE: $message\nHEADERS: $headers\n\n";
            file_put_contents("../email_log.txt", $logMessage, FILE_APPEND);
        }
        $updateStmt->close();
    } 
    
    // Always redirect to success to prevent Email Enumeration
    $stmt->close();
    header("Location: ../html/login.php?success=reset_sent");
    exit;
}
$conn->close();
?>