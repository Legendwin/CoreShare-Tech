<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // SECURITY: CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../html/login.php?error=Session expired or invalid request");
        exit;
    }

    $email = $_POST['email'];
    // ... [Rest of logic remains the same] ...
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['full_name'];
            $_SESSION['user_role'] = $row['role'];
            $_SESSION['LAST_ACTIVITY'] = time();
            
            if ($row['role'] == 'admin') {
                header("Location: ../html/moderation.php");
            } else {
                header("Location: ../html/index.php");
            }
            exit;
        } else {
            header("Location: ../html/login.php?error=Invalid password");
            exit;
        }
    } else {
        header("Location: ../html/login.php?error=No account found with this email");
        exit;
    }
}
$stmt->close();
$conn->close();
?>