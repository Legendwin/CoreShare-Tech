<?php
// Run this script once (via CLI or browser) to add the user_counters table
require 'db_connect.php';

// Add 'plan' column to users if it doesn't exist
$hasPlan = false;
$res = $conn->query("SHOW COLUMNS FROM users LIKE 'plan'");
if ($res && $res->num_rows > 0) $hasPlan = true;
if (!$hasPlan) {
    $alter = "ALTER TABLE users ADD COLUMN plan VARCHAR(32) NOT NULL DEFAULT 'free'";
    if ($conn->query($alter)) echo "Added users.plan column\n";
    else echo "Failed to add users.plan: " . $conn->error . "\n";
} else echo "users.plan already exists\n";

// Create user_counters table if missing (be tolerant of engine differences)
$check = $conn->query("SHOW TABLES LIKE 'user_counters'");
if (!($check && $check->num_rows > 0)) {
    // Detect users table engine - only add FK if InnoDB
    $engine = null;
    $st = $conn->query("SHOW TABLE STATUS WHERE Name = 'users'");
    if ($st && $st->num_rows > 0) {
        $row = $st->fetch_assoc();
        $engine = isset($row['Engine']) ? $row['Engine'] : null;
    }

    // Build CREATE TABLE SQL; add FK only for InnoDB
    $sql = "CREATE TABLE IF NOT EXISTS user_counters (
        user_id INT NOT NULL PRIMARY KEY,
        downloads_date DATE DEFAULT NULL,
        downloads_today INT DEFAULT 0,
        uploads_count INT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) DEFAULT CHARSET=utf8mb4";

    // If users uses InnoDB, recreate with engine and FK for referential integrity
    if (strtoupper($engine) === 'INNODB') {
        $sql = "CREATE TABLE IF NOT EXISTS user_counters (
            user_id INT NOT NULL PRIMARY KEY,
            downloads_date DATE DEFAULT NULL,
            downloads_today INT DEFAULT 0,
            uploads_count INT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_uc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }

    if ($conn->query($sql)) echo "Created or ensured user_counters table exists\n";
    else echo "Failed to create user_counters: " . $conn->error . "\n";
} else echo "user_counters already exists\n";

$conn->close();
?>
