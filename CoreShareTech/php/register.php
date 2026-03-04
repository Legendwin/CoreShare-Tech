<?php
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // SECURITY: CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../html/login.php?error=Session expired or invalid request");
        exit;
    }

    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // 1. ADDED 'admin' TO ALLOWED ROLES
    $allowed_roles = ['student', 'educator', 'admin']; 
    $role = (isset($_POST['role']) && in_array($_POST['role'], $allowed_roles)) ? $_POST['role'] : 'student';

    // 2. ADMIN LIMIT CHECK (MAXIMUM 4 ADMINS)
    if ($role === 'admin') {
        $adminCountStmt = $conn->prepare("SELECT COUNT(id) FROM users WHERE role = 'admin'");
        $adminCountStmt->execute();
        $adminCountStmt->bind_result($adminCount);
        $adminCountStmt->fetch();
        $adminCountStmt->close();

        // If 4 or more admins already exist, disable admin registration
        if ($adminCount >= 4) {
            header("Location: ../html/login.php?error=" . urlencode("Admin registration is disabled. Maximum limit of 4 admin accounts reached."));
            exit;
        }
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        header("Location: ../html/login.php?error=Email already exists");
        exit;
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // 3. SET ADMIN TO PRO PLAN FOREVER
    $assignedPlan = ($role === 'admin') ? 'pro' : 'free';

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, plan) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $assignedPlan);

    if ($stmt->execute()) {
        $newUserId = $conn->insert_id;
        $stmt->close();
        
        // Initialize user counters automatically
        $countStmt = $conn->prepare("INSERT INTO user_counters (user_id, downloads_today, uploads_count) VALUES (?, 0, 0)");
        $countStmt->bind_param("i", $newUserId);
        $countStmt->execute();
        $countStmt->close();

        // Auto-login configuration
        $_SESSION['user_id'] = intval($newUserId);
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;
        $_SESSION['plan'] = $assignedPlan; // Use the dynamically assigned plan

        header("Location: ../html/dashboard.php?success=registered");
        exit;
    } else {
        header("Location: ../html/login.php?error=Database error");
        exit;
    }
}
$conn->close();
?>