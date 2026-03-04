<?php
session_start();
// ALLOW GUEST ACCESS
require '../php/db_connect.php';

// Include the new subscription tracker (This line does all the hard work!)
require_once '../php/check_plan.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? intval($_SESSION['user_id']) : 0;
$userName = $isLoggedIn ? $_SESSION['user_name'] : 'Guest';
$userRole = $isLoggedIn ? $_SESSION['user_role'] : 'student';

$downloadsToday = 0; $totalResources = 0; $avgRating = "0.0"; $totalReviews = 0;
if ($isLoggedIn) {
    $today = date('Y-m-d');
    $ucResult = $conn->query("SELECT downloads_today, downloads_date FROM user_counters WHERE user_id = '$userId'");
    if ($ucResult && $ucRow = $ucResult->fetch_assoc()) {
        if ($ucRow['downloads_date'] === $today) { $downloadsToday = intval($ucRow['downloads_today']); }
    }
    $resCountResult = $conn->query("SELECT COUNT(*) as count FROM resources WHERE uploaded_by = '$userId' AND status = 'published'");
    $totalResources = $resCountResult->fetch_assoc()['count'];
    $avgRatingSql = "SELECT AVG(rev.rating) as avg_rating FROM reviews rev JOIN resources res ON rev.resource_id = res.id WHERE res.uploaded_by = '$userId'";
    $avgRatingResult = $conn->query($avgRatingSql);
    $avgData = $avgRatingResult->fetch_assoc();
    $avgRating = $avgData['avg_rating'] ? number_format($avgData['avg_rating'], 1) : "0.0";
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
        <link rel="stylesheet" href="../css/resource.css?v=<?php echo time(); ?>">
        <script>const USER_IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>; const USER_PLAN = '<?php echo $userPlan; ?>';</script>
        <style>
            .plan-badge { font-size: 0.75rem; padding: 3px 8px; border-radius: 12px; font-weight: 700; text-transform: uppercase; margin-left: 8px; vertical-align: middle; display: inline-block; letter-spacing: 0.5px;}
            .badge-free { background: var(--border-subtle); color: var(--text-muted); }
            .badge-pro { background: var(--primary-light); color: var(--primary-blue); border: 1px solid var(--primary-blue); }
            .badge-semester { background: #D1FAE5; color: #059669; border: 1px solid #059669; }
            .badge-exam_pass { background: #FEF3C7; color: #D97706; border: 1px solid #D97706; }
        </style>
        <?php if ($userPlan === 'free'): ?>
            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3596644493360839" crossorigin="anonymous"></script>
        <?php endif; ?>
	</head>
	<body>
        <div class="sidebar-overlay" id="sidebar-overlay"></div>
		<aside class="sidebar" id="sidebar" style="display:flex; flex-direction:column;">
			<div class="brand"><span>CoreShare <strong>Tech</strong></span><button id="theme-toggle" class="theme-toggle-btn">🌙</button></div>
			<nav class="nav" style="margin-top:10px; display:flex; flex-direction:column; gap:10px; flex-grow:1;">
				<a href="./index.php" class="nav-link">Home</a>
				<a href="./dashboard.php" class="nav-link active">Dashboard</a>
				<a href="./search.php" class="nav-link">Search</a>
				<a href="./resource.php" class="nav-link">Resource</a>
				<a href="./contributions.php" class="nav-link">Contributions</a>
				<a href="./contact.php" class="nav-link">Contact</a>
                <a href="./sources.php" class="nav-link">Other Resources</a>
                <?php if($isLoggedIn && $userRole === 'admin'): ?><a href="./moderation.php" class="nav-link">Moderation</a><?php endif; ?>
                <?php if(!$isLoggedIn): ?><a href="./login.php" class="nav-link" style="color:var(--primary-blue); font-weight:700;">Login</a><?php else: ?><a href="../php/logout.php" class="nav-link" style="color:#EF4444; font-weight:700;">Logout</a><?php endif; ?>

                <?php if ($userPlan === 'free'): ?>
                <div style="margin-top:auto; padding-top:20px;">
                    <div style="background:var(--bg-surface); border:1px solid var(--border-subtle); padding:15px; border-radius:8px; text-align:center;">
                        <span style="font-size:0.65rem; color:var(--text-muted); display:block; margin-bottom:5px; text-transform:uppercase;">Sponsored</span>
                        <strong style="font-size:0.85rem; color:var(--text-main); display:block;">Grammarly Premium</strong>
                        <p style="font-size:0.8rem; color:var(--text-muted); margin:5px 0;">Write better essays.</p>
                        <a href="#" style="font-size:0.8rem; color:var(--primary-blue); font-weight:700; text-decoration:none;">Learn More</a>
                    </div>
                </div>
                <?php endif; ?>
			</nav>
		</aside>

		<main class="main-content">
			<button id="menu-toggle" class="mobile-menu-btn"><span id="toggle-icon">☰</span></button>
			<header class="dashboard-header">
                <div class="header-title">
                    <h1 style="display:flex; align-items:center;"><?php echo $isLoggedIn ? ucfirst($userRole) . ' Dashboard' : 'Guest Dashboard'; ?></h1>
                    <p style="color:var(--text-muted); font-size:0.95rem; display:flex; align-items:center; margin-top:4px;">
                        Welcome, <strong style="color:var(--text-main); margin-left:4px;"><?php echo htmlspecialchars($userName); ?></strong>
                        <?php if($isLoggedIn): ?>
                            <span class="plan-badge badge-<?php echo strtolower($userPlan); ?>"><?php echo ucwords(str_replace('_', ' ', $userPlan)); ?> Plan</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="search-bar">
                    <form action="search.php" method="GET" style="width:100%; display:flex;">
                        <input type="text" name="q" placeholder="Search notes, papers..." style="flex:1;"><button type="submit" style="background:none; border:none; cursor:pointer;"><span><img src="../images/Search_Magnifying_Glass.svg" alt="Search"></span></button>
                    </form>
                </div>
            </header>

            <?php if ($userPlan === 'free'): ?>
            <div style="width:100%; max-width:1100px; margin: 0 auto 24px auto; background:var(--bg-surface); border:1px solid var(--border-subtle); border-radius:8px; padding:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div style="display:flex; align-items:center; gap:15px;"><span style="background:#FEE2E2; color:#EF4444; padding:4px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">AD</span><strong style="color:var(--text-main); font-size:0.95rem;">Master Your Classes with CourseHero</strong></div>
                <a href="#" class="btn-card" style="padding:6px 12px; border:1px solid var(--primary-blue); color:var(--primary-blue); background:transparent; font-size:0.85rem;">View Solutions</a>
            </div>
            <?php endif; ?>

            <section class="stats-row">
				<div class="stat-card"><div><div class="stat-value"><?php echo $downloadsToday; ?></div><div class="stat-label">Downloads Today</div></div><div style="background:#DBEAFE; width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--primary-blue);"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></div></div>
				<div class="stat-card"><div><div class="stat-value"><?php echo $totalResources; ?></div><div class="stat-label">My Resources</div></div><div style="background:#D1FAE5; width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--success);"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg></div></div>
				<div class="stat-card"><div><div class="stat-value"><?php echo $avgRating; ?></div><div class="stat-label">Rating Received</div></div><div style="background:#FEF3C7; width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#F59E0B;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></div></div>
			</section>

			<section>
				<div class="section-header"><h3>Trending Study Materials</h3><a href="./resource.php" style="color:var(--primary-blue); text-decoration:none;">View All</a></div>
				<div class="grid-container">
					<?php
					$sql = "SELECT * FROM resources WHERE status='published' ORDER BY downloads DESC LIMIT 4";
					$result = $conn->query($sql);
					if ($result->num_rows > 0) {
                        $count = 0;
						while($row = $result->fetch_assoc()) {
                            $count++;
                            if ($userPlan === 'free' && $count == 3) {
                                echo '<div class="card" style="border: 1px solid var(--border-subtle); background: var(--bg-surface);"><div class="card-body" style="display:flex; flex-direction:column; height:100%;"><span class="tag" style="background: var(--border-subtle); color: var(--text-muted); align-self:flex-start;">Sponsored Ad</span><div class="card-title" style="margin-top: 10px;">Grammarly for Students</div><p style="font-size: 0.9rem; color: var(--text-muted); flex-grow:1; margin-bottom:15px;">Improve your essays and grades.</p><a href="#" class="btn-card" style="background: transparent; border: 1px solid var(--primary-blue); color: var(--primary-blue); display:block; text-align:center; margin-top:auto; text-decoration:none;">Learn More</a></div></div>';
                            }
                            $fileExt = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                            $icon = "📄"; $bgColor = "#F1F5F9"; $textColor = "#64748B";
                            if ($fileExt == 'pdf') { $icon = "📕"; $bgColor = "#FEE2E2"; $textColor = "#EF4444"; } 
                            elseif ($fileExt == 'docx') { $icon = "📘"; $bgColor = "#DBEAFE"; $textColor = "#3B82F6"; }
                            elseif ($fileExt == 'pptx' || $fileExt == 'ppt') { $icon = "📙"; $bgColor = "#FFEDD5"; $textColor = "#F97316"; }
                            echo '<div class="card"><div class="card-image" style="background:'.$bgColor.';display:flex;align-items:center;justify-content:center;flex-direction:column;"><div style="font-size:4rem;">'.$icon.'</div><div style="font-weight:700;color:'.$textColor.';margin-top:10px;">'.$fileExt.'</div></div><div class="card-body"><span class="tag">'.htmlspecialchars($row['programme']).'</span><div class="card-title">'.htmlspecialchars($row['title']).'</div><div class="card-meta"><span>'.htmlspecialchars($row['type']).'</span></div><button class="btn-card" onclick="openResourceModal('.$row['id'].')">View Details</button></div></div>';
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
                        <span class="resource-type-badge">Type</span><h2 class="resource-title">Resource Title</h2>
                        <div class="resource-meta"><span class="course-info">Course Name</span><span style="color:#CBD5E1">•</span><span class="star-display">★★★★★</span></div>
                    </div>
                    <button class="new-close-btn" onclick="closeResourceModal()"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                </div>
                <div class="new-modal-body">
                    <div class="modal-col-left">
                        <div class="file-preview-card"><div class="big-file-icon">📄</div><div class="file-name-display">filename.pdf</div></div>
                        <button class="btn-primary-download" style="width:100%;">Download Material</button>
                        <?php if ($userPlan === 'free'): ?>
                        <div style="margin-top: 15px; padding: 15px; background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: 8px; text-align: center;">
                            <span style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase;">Sponsored</span><strong style="display: block; font-size: 0.9rem; margin: 5px 0; color:var(--text-main);">Audible - 1 Month Free</strong><a href="#" style="font-size: 0.8rem; color: var(--primary-blue); font-weight: 700; text-decoration: none;">Listen While You Study</a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-col-right">
                        <div class="reviews-scroll-area"><div class="reviews-list"></div></div>
                        <div class="review-input-area">
                            <div class="star-select-row"><span data-v="1">★</span><span data-v="2">★</span><span data-v="3">★</span><span data-v="4">★</span><span data-v="5">★</span></div>
                            <div class="input-row"><input type="text" class="modern-input" placeholder="Share your thoughts on this resource..."><button class="btn-send">➤</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($userPlan === 'free'): ?>
        <div id="promo-modal" class="new-modal-overlay" style="z-index: 9999;">
            <div class="new-modal-window" style="max-width: 500px; text-align: center; padding: 0;">
                <div style="background: var(--bg-surface); padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); border-radius: 16px 16px 0 0;">
                    <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Advertisement</span><button onclick="closeAdModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
                </div>
                <div style="padding: 40px 20px;">
                    <h2 style="margin-bottom: 15px; color: var(--text-main);">Tired of limits?</h2>
                    <p style="color: var(--text-muted); margin-bottom: 25px; font-size: 1.05rem;">Upgrade to CoreShare Pro to unlock unlimited downloads, custom collections, and an ad-free experience.</p>
                    <a href="./billing.php" class="btn-card" style="background: var(--grad-primary); color: white; border: none; text-decoration: none; padding: 12px 24px;">View Plans (From GH₵15)</a>
                </div>
                <div style="padding-bottom: 20px;"><button onclick="closeAdModal()" style="background:none; border:none; color:var(--text-muted); text-decoration:underline; cursor:pointer;">No thanks</button></div>
            </div>
        </div>

        <div id="exit-intent-modal" class="new-modal-overlay" style="z-index: 9999; display:none;">
            <div class="new-modal-window" style="max-width: 450px; text-align: center; padding: 0;">
                <div style="background: #FEE2E2; padding: 20px; border-radius: 16px 16px 0 0;"><h2 style="color: #DC2626; margin: 0;">Wait! Don't leave yet.</h2></div>
                <div style="padding: 30px 20px;">
                    <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 1.05rem;">Get <strong>CoreShare Pro</strong> today starting at just GH₵15 and completely remove all ads, upload limits, and wait times.</p>
                    <a href="./billing.php" class="btn-card" style="background: var(--grad-primary); color: white; text-decoration: none; display:inline-block; padding: 12px 24px; margin-bottom: 10px;">See Plans</a>
                    <button onclick="closeExitModal()" style="display:block; width:100%; background:none; border:none; color:var(--text-muted); text-decoration:underline; cursor:pointer; margin-top:10px;">Close</button>
                </div>
            </div>
        </div>

        <div id="download-interstitial" class="new-modal-overlay" style="z-index: 10000; display:none;">
            <div class="new-modal-window" style="max-width: 450px; text-align: center; padding: 30px 20px;">
                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Sponsor Message</span>
                <h3 style="margin: 15px 0;">Master Your Classes with CourseHero</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 20px;">Access millions of study documents.</p>
                <div style="background: var(--bg-body); padding: 15px; border-radius: 8px; margin-bottom: 20px;"><strong id="dl-countdown" style="color: var(--primary-blue); font-size: 1.1rem;">Your download will begin in 5 seconds...</strong></div>
                <button id="dl-skip-btn" class="btn-card" style="display: none; background: var(--success); color: white; border: none; width: 100%; padding: 12px; cursor:pointer;">Skip Ad & Download File</button>
            </div>
        </div>
        <?php endif; ?>

        <script src="../js/script.js?v=<?php echo time(); ?>"></script>
    </body>
</html>