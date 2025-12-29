<?php
ob_clean();
header('Content-Type: application/json');

session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["reviews" => 0, "resources" => 0, "downloads" => 0, "rating" => "0.0"]);
    exit;
}

$userId = $_SESSION['user_id'];

// 1. Total Downloads
$downloadResult = $conn->query("SELECT SUM(downloads) as total FROM resources WHERE uploaded_by = '$userId'");
$totalDownloads = ($downloadResult && $row = $downloadResult->fetch_assoc()) ? ($row['total'] ?? 0) : 0;

// 2. Resources Shared
$resCountResult = $conn->query("SELECT COUNT(*) as count FROM resources WHERE uploaded_by = '$userId' AND status = 'published'");
$totalResources = ($resCountResult && $row = $resCountResult->fetch_assoc()) ? $row['count'] : 0;

// 3. Average Rating
$avgRatingSql = "SELECT AVG(rev.rating) as avg_rating FROM reviews rev JOIN resources res ON rev.resource_id = res.id WHERE res.uploaded_by = '$userId'";
$avgRatingResult = $conn->query($avgRatingSql);
$avgData = $avgRatingResult->fetch_assoc();
$avgRating = $avgData['avg_rating'] ? number_format($avgData['avg_rating'], 1) : "0.0";

echo json_encode([
    "reviews" => 0, // Placeholder if not needed, or query it
    "resources" => $totalResources,
    "downloads" => $totalDownloads,
    "rating" => $avgRating
]);

$conn->close();
?>