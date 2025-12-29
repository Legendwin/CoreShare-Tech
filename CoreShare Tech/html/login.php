<?php
session_start();
require '../php/db_connect.php'; // Ensures token is generated

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - CoreShare Tech</title>
        <link rel="icon" type="image/png" href="../images/Gemini_Generated_Image_69zr6i69zr6i69zr.png" sizes="32x32">
        <link rel="stylesheet" href="../css/login.css?v=<?php echo time(); ?>">
    </head>
    <body>
        <div class="toast-container"></div>
        
        <div class="login-container">
            <div class="header">
                <h2>CoreShare Tech</h2>
            </div>
            <div class="tabs">
                <button class="tab-btn active" id="tab-login">Login</button>
                <button class="tab-btn" id="tab-register">Register</button>
            </div>
            
            <div class="form-body">
                <form id="login-form" action="../php/login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="student@university.edu" required>
                    </div>
                    
                    <div class="input-group password-group">
                        <label>Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="login-pass" placeholder="••••••••" required>
                            <span class="toggle-password" onclick="togglePassword('login-pass', this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </span>
                        </div>
                    </div>

                    <div class="options-row">
                        <span>Remember me</span>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <button type="submit" class="btn-submit">Secure Log In</button>
                    
                    <div class="footer-link">
                        <a href="forgot_password.html">Forgot your password?</a>
                    </div>
                </form>

                <form id="register-form" class="hidden" action="../php/register.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="input-group">
                        <label>I am a...</label>
                        <select id="role-select" name="role">
                            <option value="student">Student</option>
                            <option value="educator">Educator</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" placeholder="Alex Student" required>
                    </div>

                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="student@university.edu" required>
                    </div>
                    
                    <div class="input-group password-group">
                        <label>Password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="reg-pass" placeholder="Create a password" required>
                            <span class="toggle-password" onclick="togglePassword('reg-pass', this)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Create Account</button>
                </form>

                <div class="footer-link" style="margin-top: 30px; border-top: 1px solid #E2E8F0; padding-top: 20px;">
                    <a href="index.php" style="color: #64748B; font-size: 0.9rem;">← Back to Dashboard</a>
                </div>
            </div>
        </div>

        <script src="../js/script.js?v=<?php echo time(); ?>"></script>
    </body>
</html>