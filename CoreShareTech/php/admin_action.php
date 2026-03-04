<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Clear output buffer to prevent PHP warnings breaking JSON
ob_clean(); 
header('Content-Type: application/json');

// REMOVED session_start() SO DB_CONNECT HANDLES IT
require 'db_connect.php';

// Load environment variables or use defaults
if (!function_exists('get_env_or_default')) {
    function get_env_or_default($var, $default = '') {
        return getenv($var) ?: $default;
    }
}

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
            $gmailUsername = get_env_or_default('GMAIL_USERNAME', 'legendwin0647@gmail.com');
            $gmailPassword = get_env_or_default('GMAIL_APP_PASSWORD', '');
            $adminEmail = get_env_or_default('ADMIN_EMAIL', 'legendwin0647@gmail.com');
            
            $to = $userData['email'];
            $subject = "CoreShare Tech: Resource " . ucfirst($action);
            $message = "Hello " . $userData['full_name'] . ",\n\n" .
                      "Your resource '" . $userData['title'] . "' has been reviewed.\n" .
                      "Status: " . ucfirst($action);
            
            $email_sent = false;
            // ADDED THE MISSING SLASH HERE
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                try {
                    // ADDED THE MISSING SLASH HERE
                    require __DIR__ . '/../vendor/autoload.php';
                    
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $gmailUsername;
                    $mail->Password = $gmailPassword;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    
                    // Only send if password is configured
                    if (!empty($gmailPassword)) {
                        $mail->setFrom($gmailUsername, 'CoreShare Tech');
                        $mail->addAddress($to);
                        $mail->Subject = $subject;
                        $mail->Body = $message;
                        $mail->AltBody = $message;
                        
                        $mail->send();
                        $email_sent = true;
                    }
                } catch (Exception $e) {
                    error_log("Mailer Error: " . $e->getMessage());
                }
            }
            
            // Log for testing (fallback if email fails)
            $logMessage = "TO: $to\nSUBJECT: $subject\nMESSAGE: $message\nSTATUS: " . ($action === 'published' ? 'APPROVED' : 'REJECTED') . "\n---\n";
            file_put_contents(__DIR__ . "/../action_log.txt", $logMessage, FILE_APPEND);
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