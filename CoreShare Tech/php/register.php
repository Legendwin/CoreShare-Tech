<?php
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // SECURITY: CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../html/login.php?error=Session expired or invalid request");
        exit;
    }

    // 1. Validate Input
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // ... [Rest of logic remains the same] ...
    
    $allowed_roles = ['student', 'educator']; 
    $role = (isset($_POST['role']) && in_array($_POST['role'], $allowed_roles)) ? $_POST['role'] : 'student';

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
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        header("Location: ../html/login.php?success=registered");
        exit;
    } else {
        header("Location: ../html/login.php?error=Database error");
        exit;
    }
}
$conn->close();
?>