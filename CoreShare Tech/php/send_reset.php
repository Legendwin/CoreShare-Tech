<?php
session_start();
require 'db_connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../html/login.php?error=Invalid email format");
        exit;
    } 

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
        
        // 3. Set Expiry (15 mins)
        $expiry = date("Y-m-d H:i:s", time() + 60 * 15); 

        // 4. Store in DB
        $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $updateStmt->bind_param("sss", $token_hash, $expiry, $email);
        
        if ($updateStmt->execute()) {
            
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $domain = $_SERVER['HTTP_HOST'];
            $resetLink = "$protocol://$domain/CoreShare%20Tech/html/reset.php?token=$token&email=" . urlencode($email);
            
            $subject = "Password Reset Request - CoreShare Tech";
            $message = "Hello,\n\nYou requested a password reset for your CoreShare Tech account.\n\n" .
                       "Click the link below to reset your password:\n" . 
                       $resetLink . "\n\n" .
                       "This link expires in 15 minutes. If you did not request this, please ignore this email.";

            // 5. Send Email using PHPMailer
            
            // === YOUR GMAIL DETAILS ===
            $gmailUsername = 'coresharetech3@gmail.com'; 
            $gmailPassword = 'jzxkxwmxmyerxohe'; 
            // ==========================

            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require __DIR__ . '/../vendor/autoload.php';
                $mail = new PHPMailer(true);
                
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $gmailUsername;
                    $mail->Password = $gmailPassword;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    
                    $mail->setFrom($gmailUsername, 'CoreShare Tech');
                    $mail->addAddress($email); // Send to the user who forgot their password
                    
                    $mail->Subject = $subject;
                    $mail->Body = $message;
                    
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Mailer Error: " . $e->getMessage());
                }
            }
            
            // Log for testing (Useful if testing locally without internet)
            $logMessage = "TO: $email\nSUBJECT: $subject\nLINK: $resetLink\nEXPIRY: $expiry\n------------------------\n";
            file_put_contents("../email_log.txt", $logMessage, FILE_APPEND);
        }
        $updateStmt->close();
    } 
    
    // Always redirect to success to prevent hackers from guessing registered emails
    $stmt->close();
    header("Location: ../html/login.php?success=reset_sent");
    exit;
}
$conn->close();
?>