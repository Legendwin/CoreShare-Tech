<?php
// Intended to be run daily (cron) to reset download counters for users who didn't download today
require 'db_connect.php';

$today = date('Y-m-d');

// Set downloads_today to 0 for rows where downloads_date != today
$sql = "UPDATE user_counters SET downloads_today = 0, downloads_date = ? WHERE downloads_date != ? OR downloads_date IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $today, $today);
if ($stmt->execute()) echo "Counters reset for users not on $today\n";
else echo "Reset failed: " . $conn->error . "\n";

$stmt->close();
$conn->close();
?>
