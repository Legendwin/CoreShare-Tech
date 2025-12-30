<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email']; 

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
        
        // 3. Set Expiry (PHP Time - Matches reset.php check)
        $expiry = date("Y-m-d H:i:s", time() + 60 * 15); // 15 mins expiry

        // 4. Store in DB
        $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $updateStmt->bind_param("sss", $token_hash, $expiry, $email);
        
        if ($updateStmt->execute()) {
            // 5. Send Email (Dynamic Host)
            // Use HTTP_HOST to get the actual domain/IP (e.g., 192.168.x.x or coreshare.com)
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $domain = $_SERVER['HTTP_HOST'];
            
            // Construct the path relative to the server root
            // Assuming your file structure is /CoreShare Tech/html/
            $resetLink = "$protocol://$domain/CoreShare%20Tech/html/reset.php?token=$token&email=" . urlencode($email);
            
            $subject = "Password Reset Request";
            $message = "You requested a password reset for CoreShare Tech.\n\n" .
                       "Click the link below to reset your password:\n" . 
                       $resetLink . "\n\n" .
                       "This link expires in 15 minutes.";
                       
            $headers = "From: no-reply@coresharetech.com\r\n";
            $headers .= "Reply-To: no-reply@coresharetech.com\r\n";

            // Attempt to send email (Suppress errors for local environments)
            @mail($email, $subject, $message, $headers);
            
            // Log for testing (Essential for localhost where mail() might fail)
            $logMessage = "TO: $email\nSUBJECT: $subject\nLINK: $resetLink\nEXPIRY: $expiry\n------------------------\n";
            file_put_contents("../email_log.txt", $logMessage, FILE_APPEND);
        }
        $updateStmt->close();
    } 
    
    // Always redirect to success to prevent Email Enumeration security risks
    $stmt->close();
    header("Location: ../html/login.php?success=reset_sent");
    exit;
}
$conn->close();
?>