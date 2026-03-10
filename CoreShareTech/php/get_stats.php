<?php
ob_clean();
header('Content-Type: application/json');

require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["reviews" => 0, "resources" => 0, "downloads" => 0, "rating" => "0.0"]);
    exit;
}

$userId = $_SESSION['user_id'];

// 1. Downloads Today (From user_counters)
$today = date('Y-m-d');
$downloadResult = $conn->query("SELECT downloads_today, downloads_date FROM user_counters WHERE user_id = '$userId'");
$totalDownloads = 0;

if ($downloadResult && $row = $downloadResult->fetch_assoc()) {
    // Only show the count if the date matches today!
    if ($row['downloads_date'] === $today) {
        $totalDownloads = $row['downloads_today'];
    }
}
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