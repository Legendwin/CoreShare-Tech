<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Verify CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(["success" => false, "message" => "Security token invalid."]);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

    // Validate required fields
    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(["success" => false, "message" => "Please fill in all required fields."]);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Invalid email address."]);
        exit;
    }

    // 2. Send Email to Admin using PHPMailer (No Database Insertion)
    $gmailUsername = 'coresharetech3@gmail.com'; 
    $gmailPassword = 'jzxkxwmxmyerxohe'; 

    $emailSubject = "New Contact Message: " . ($subject ? $subject : "(No subject)");
    $emailBody = "You have received a new message from $name ($email).\n\n" .
                 "Subject: " . ($subject ? $subject : "(No subject)") . "\n\n" .
                 "Message:\n$message\n\n" .
                 "Logged In User ID: " . ($userId ? $userId : "Guest");
    
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        try {
            require __DIR__ . '/../vendor/autoload.php';
            
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $gmailUsername;
            $mail->Password = $gmailPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom($gmailUsername, 'CoreShare System');
            $mail->addAddress($gmailUsername, 'CoreShare Admin'); // Send it to yourself
            $mail->addReplyTo($email, $name); // Allows you to hit 'Reply' and email the user back
            
            $mail->isHTML(false);
            $mail->Subject = $emailSubject;
            $mail->Body = $emailBody;
            
            $mail->send();

            // Log for debugging
            $logMessage = "FROM: $name <$email>\nTO: $gmailUsername\nSUBJECT: $emailSubject\nMESSAGE: $message\nUSER_ID: " . ($userId ? $userId : "Guest") . "\n---\n";
            file_put_contents(__DIR__ . "/../contact_log.txt", $logMessage, FILE_APPEND);
            
            echo json_encode(["success" => true, "message" => "Message sent successfully!"]);

        } catch (Exception $e) {
            error_log("Mailer Error: " . $e->getMessage());
            echo json_encode(["success" => false, "message" => "Failed to send message. Please try again later."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Server configuration error."]);
    }
}
?>