<?php
session_start();
// ALLOW GUEST ACCESS
require '../php/db_connect.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : 0;
$userName = $isLoggedIn ? $_SESSION['user_name'] : 'Guest';
$userRole = $isLoggedIn ? $_SESSION['user_role'] : 'student';

// Initialize stats to 0 for guests
$totalDownloads = 0; $totalResources = 0; $avgRating = "0.0"; $totalReviews = 0;

if ($isLoggedIn) {
    $downloadResult = $conn->query("SELECT SUM(downloads) as total FROM resources WHERE uploaded_by = '$userId'");
    $row = $downloadResult->fetch_assoc();
    $totalDownloads = $row['total'] ? $row['total'] : 0;

    $resCountResult = $conn->query("SELECT COUNT(*) as count FROM resources WHERE uploaded_by = '$userId' AND status = 'published'");
    $totalResources = $resCountResult->fetch_assoc()['count'];

    $avgRatingSql = "SELECT AVG(rev.rating) as avg_rating FROM reviews rev JOIN resources res ON rev.resource_id = res.id WHERE res.uploaded_by = '$userId'";
    $avgRatingResult = $conn->query($avgRatingSql);
    $avgData = $avgRatingResult->fetch_assoc();
    $avgRating = $avgData['avg_rating'] ? number_format($avgData['avg_rating'], 1) : "0.0";
    
    $revCountResult = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE user_id = '$userId'");
    $totalReviews = $revCountResult->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>CoreShare Tech - Dashboard</title>
		<link rel="icon" type="image/png" href="../images/Gemini_Generated_Image_69zr6i69zr6i69zr.png" sizes="32x32">
		<link rel="stylesheet" href="../css/styles.css?v=<?php echo time(); ?>">
        <script>const USER_IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;</script>
	</head>
	<body>
		<aside class="sidebar" id="sidebar">
			<div class="brand"><span>CoreShare <strong>Tech</strong></span><button class="sidebar-close-btn" id="sidebar-close">×</button></div>
			<nav class="nav" style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
				<a href="index.php" class="nav-link active">Dashboard</a>
				<a href="search.php" class="nav-link">Search</a>
				<a href="resource.php" class="nav-link">Resource</a>
				<a href="contributions.php" class="nav-link">Contributions</a>
				<a href="contact.php" class="nav-link">Contact</a>
				<?php if($isLoggedIn && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                	<a href="moderation.php" class="nav-link">Moderation</a>
				<?php endif; ?>
                <?php if($isLoggedIn): ?>
				    <a href="../php/logout.php" class="nav-link" style="color:#EF4444;">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link" style="color:var(--primary-blue); font-weight:700;">Login</a>
                <?php endif; ?>
			</nav>
		</aside>

		<main class="main-content">
			<button id="menu-toggle" class="mobile-menu-btn">☰</button>
			<header class="dashboard-header">
                <div class="header-title">
                    <h1><?php echo ucfirst($userRole) . ' Dashboard'; ?></h1>
                    <p style="color:var(--text-gray); font-size:0.9rem;">Welcome, <?php echo htmlspecialchars($userName); ?></p>
                </div>
                <div class="search-bar"><form action="search.php" method="GET" style="width:100%; display:flex;"><input type="text" name="q" placeholder="Search notes, papers, or study guides..." style="flex:1;"><button type="submit" style="background:none; border:none; cursor:pointer;"><span style="color:var(--primary-blue); font-weight:bold;">🔍</span></button></form></div>
            </header>

            <section class="stats-row">
				<div class="stat-card"><div><div class="stat-value" id="stat-downloads"><?php echo $totalDownloads; ?></div><div class="stat-label">Total Downloads</div></div><div style="background:#DBEAFE; width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--primary-blue);"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></div></div>
				<div class="stat-card"><div><div class="stat-value" id="stat-resources"><?php echo $totalResources; ?></div><div class="stat-label">My Resources</div></div><div style="background:#D1FAE5; width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--success);"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg></div></div>
				<div class="stat-card"><div><div class="stat-value" id="stat-rating"><?php echo $avgRating; ?></div><div class="stat-label">Rating Received</div></div><div style="background:#FEF3C7; width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#F59E0B;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></div></div>
			</section>

			<section>
				<div class="section-header"><h3>Trending Study Materials</h3><a href="./resource.php" style="color:var(--primary-blue); text-decoration:none;">View All</a></div>
				<div class="grid-container">
					<?php
					$sql = "SELECT * FROM resources WHERE status='published' ORDER BY downloads DESC LIMIT 4";
					$result = $conn->query($sql);
					if ($result->num_rows > 0) {
						while($row = $result->fetch_assoc()) {
							$title = htmlspecialchars($row['title']);
							$subject = htmlspecialchars($row['subject']);
                            $type = htmlspecialchars($row['type']);
                            $fileExt = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                            $icon = "📄"; $bgColor = "#F1F5F9"; $textColor = "#64748B";
                            if ($fileExt == 'pdf') { $icon = "📕"; $bgColor = "#FEE2E2"; $textColor = "#EF4444"; } 
                            elseif ($fileExt == 'docx' || $fileExt == 'doc') { $icon = "📘"; $bgColor = "#DBEAFE"; $textColor = "#3B82F6"; }
                            
                            echo '<div class="card"><div class="card-image" style="background:'.$bgColor.';display:flex;align-items:center;justify-content:center;flex-direction:column;"><div style="font-size:4rem;">'.$icon.'</div><div style="font-weight:700;color:'.$textColor.';margin-top:10px;">'.$fileExt.'</div></div><div class="card-body"><span class="tag">'.$subject.'</span><div class="card-title">'.$title.'</div><div class="card-meta"><span>'.$type.'</span></div><button class="btn-card" onclick="openResourceModal('.$row['id'].')">View Resource</button></div></div>';
						}
					} else { echo '<p style="color:var(--text-muted);">No resources found. Be the first to upload!</p>'; }
					?>
				</div>
			</section>
		</main>
                
<div class="new-modal-overlay" id="resource-modal">
        <div class="new-modal-window">
            <div class="new-modal-header">
                <div class="header-left">
                    <span class="resource-type-badge">Type</span>
                    <h2 class="resource-title">Resource Title</h2>
                    <div class="resource-meta">
                        <span class="course-info">Course Name</span>
                        <span style="color:#CBD5E1">•</span>
                        <span class="star-display">★★★★★</span>
                    </div>
                </div>
                <button class="new-close-btn" onclick="closeResourceModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="new-modal-body">
                <div class="modal-col-left">
                    <div class="file-preview-card">
                        <div class="big-file-icon">📄</div>
                        <div class="file-name-display">filename.pdf</div>
                    </div>
                    <button class="btn-primary-download">Download Material</button>
                </div>
                
                <div class="modal-col-right">
                    <div class="reviews-scroll-area">
                        <div class="reviews-list">
                            </div>
                    </div>
                    
                    <div class="review-input-area">
                        <div class="star-select-row">
                            <span data-v="1">★</span><span data-v="2">★</span><span data-v="3">★</span><span data-v="4">★</span><span data-v="5">★</span>
                        </div>
                        <div class="input-row">
                            <input type="text" class="modern-input" placeholder="Share your thoughts on this resource...">
                            <button class="btn-send">➤</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>    
    <script src="../js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>