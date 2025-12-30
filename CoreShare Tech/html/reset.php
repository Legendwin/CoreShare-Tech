<?php
require '../php/db_connect.php';

$error = "";
$token = "";
$email = "";

// 1. Handle GET (User clicks link)
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['token']) && isset($_GET['email'])) {
        $token = $_GET['token'];
        $email = $_GET['email'];
        
        // Optional: Pre-check validity to show error immediately (UX improvement)
        $token_hash = hash("sha256", $token);
        $current_time = date("Y-m-d H:i:s");
        
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expires > ?");
        $check->bind_param("sss", $email, $token_hash, $current_time);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows === 0) {
            $check->close();
            header("Location: login.php?error=Link expired or invalid");
            exit;
        }
        $check->close();

    } else {
        header("Location: login.php?error=Invalid link");
        exit;
    }
}

// 2. Handle POST (User submits new password)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $token = $_POST['token'];
    $new_pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        // Pass error back to self
        header("Location: reset.php?token=$token&email=$email&error=Passwords do not match");
        exit;
    }

    $token_hash = hash("sha256", $token);
    
    // CRITICAL FIX: Use PHP time to ensure timezone consistency
    $current_time = date("Y-m-d H:i:s");

    // 3. Verify Token and Expiry using PHP time
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expires > ?");
    $stmt->bind_param("sss", $email, $token_hash, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 4. Update Password & Clear Token (Invalidate immediately)
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        
        $updateStmt = $conn->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
        $updateStmt->bind_param("ss", $new_hash, $email);
        
        if ($updateStmt->execute()) {
            $updateStmt->close();
            header("Location: login.php?success=password_reset");
            exit;
        } else {
            $updateStmt->close();
            // Redirect with specific error
            header("Location: reset.php?token=$token&email=$email&error=Database update failed");
            exit;
        }
    } else {
        header("Location: login.php?error=Invalid or expired token");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - CoreShare Tech</title>
    <link rel="icon" type="image/png" href="../images/Gemini_Generated_Image_69zr6i69zr6i69zr.png" sizes="32x32">   
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="header"><h2>Set New Password</h2></div>
        <div class="form-body">
            
            <?php if (isset($_GET['error'])): ?>
                <div style="background:#FEE2E2; color:#B91C1C; padding:10px; border-radius:8px; margin-bottom:15px; font-size:0.9rem; text-align:center;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="reset.php" method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="input-group password-group">
                    <label>New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="new-pass" placeholder="Enter new password" required>
                        <span class="toggle-password" onclick="togglePassword('new-pass', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </span>
                    </div>
                </div>
                <div class="input-group password-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm-pass" placeholder="Confirm new password" required>
                        <span class="toggle-password" onclick="togglePassword('confirm-pass', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Update Password</button>
            </form>
        </div>
    </div>

    <script src="../js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>