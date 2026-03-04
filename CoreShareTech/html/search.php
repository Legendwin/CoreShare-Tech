<?php
session_start();
require '../php/db_connect.php';

$isLoggedIn = isset($_SESSION['user_id']);

// Include the new subscription tracker (This line does all the hard work!)
require_once '../php/check_plan.php';

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM resources WHERE status='published'";
if (!empty($searchQuery)) {
    $cleanQuery = $conn->real_escape_string($searchQuery);
    $sql .= " AND (title LIKE '%$cleanQuery%' OR course_name LIKE '%$cleanQuery%' OR programme LIKE '%$cleanQuery%' OR type LIKE '%$cleanQuery%')";
}
$sql .= " ORDER BY created_at DESC";
$result = $conn->query($sql);
$numResults = $result ? $result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Search - CoreShare Tech</title>
        <link rel="icon" type="image/png" href="../images/Gemini_Generated_Image_69zr6i69zr6i69zr.png" sizes="32x32">
        <link rel="stylesheet" href="../css/styles.css?v=<?php echo time(); ?>">
        <link rel="stylesheet" href="../css/resource.css?v=<?php echo time(); ?>">
        <script>const USER_IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>; const USER_PLAN = '<?php echo $userPlan; ?>';</script>
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
                <a href="./dashboard.php" class="nav-link">Dashboard</a>
                <a href="./search.php" class="nav-link active">Search</a>
                <a href="./resource.php" class="nav-link">Resource</a>
                <a href="./contributions.php" class="nav-link">Contributions</a>
                <a href="./contact.php" class="nav-link">Contact</a>
                <a href="./sources.php" class="nav-link">Other Resources</a>
                
                <?php if($isLoggedIn && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                	<a href="./moderation.php" class="nav-link">Moderation</a>
				<?php endif; ?>
                <?php if($isLoggedIn): ?>
				    <a href="../php/logout.php" class="nav-link" style="color:#EF4444; font-weight:700;">Logout</a>
                <?php else: ?>
                    <a href="./login.php" class="nav-link" style="color:var(--primary-blue); font-weight:700;">Login</a>
                <?php endif; ?>

                <?php if ($userPlan === 'free'): ?>
                <div style="margin-top:auto; padding-top:20px;">
                    <div style="background:var(--bg-surface); border:1px solid var(--border-subtle); padding:15px; border-radius:8px; text-align:center;">
                        <span style="font-size:0.65rem; color:var(--text-muted); display:block; margin-bottom:5px; text-transform:uppercase;">Sponsored</span>
                        <strong style="font-size:0.85rem; color:var(--text-main); display:block;">Grammarly Premium</strong>
                        <a href="#" style="font-size:0.8rem; color:var(--primary-blue); font-weight:700; text-decoration:none;">Learn More</a>
                    </div>
                </div>
                <?php endif; ?>
            </nav>
        </aside>

        <main class="main-content">
            <button id="menu-toggle" class="mobile-menu-btn"><span id="toggle-icon">☰</span></button>
            <header class="dashboard-header">
                <div class="header-title"><h1>Search Results</h1><p style="color:var(--text-gray); font-size:0.9rem;">Found <?php echo $numResults; ?> results for "<?php echo htmlspecialchars($searchQuery); ?>"</p></div>
                <div class="search-actions">
                    <div class="search-bar resource-search"><form action="search.php" method="GET"><input type="text" name="q" placeholder="Filter by course code, topic..." value="<?php echo htmlspecialchars($searchQuery); ?>"><button type="submit"><span><img src="../images/Search_Magnifying_Glass.svg" alt="Search"></span></button></form></div>
                </div>
            </header>

            <?php if ($userPlan === 'free'): ?>
            <div style="width:100%; max-width:1100px; margin: 0 auto 24px auto; background:var(--bg-surface); border:1px solid var(--border-subtle); border-radius:8px; padding:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div style="display:flex; align-items:center; gap:15px;"><span style="background:#FEE2E2; color:#EF4444; padding:4px 8px; border-radius:4px; font-size:0.75rem; font-weight:700;">AD</span><strong style="color:var(--text-main); font-size:0.95rem;">Master Your Classes with CourseHero</strong></div>
                <a href="#" class="btn-card" style="padding:6px 12px; border:1px solid var(--primary-blue); color:var(--primary-blue); background:transparent; font-size:0.85rem;">View Solutions</a>
            </div>
            <?php endif; ?>

            <div class="resource-page-layout">
                <div class="resource-main-col">
                    <div class="grid-container">
                        <?php
                        if ($numResults > 0) {
                            $count = 0;
                            while($row = $result->fetch_assoc()) {
                                $count++;
                                if ($userPlan === 'free' && $count == 3) {
                                    echo '<div class="card" style="border: 1px solid var(--border-subtle); background: var(--bg-surface);"><div class="card-body" style="display:flex; flex-direction:column; height:100%;"><span class="tag" style="background: var(--border-subtle); color: var(--text-muted); align-self:flex-start;">Sponsored Ad</span><div class="card-title" style="margin-top: 10px;">Grammarly for Students</div><a href="#" class="btn-card" style="background: transparent; border: 1px solid var(--primary-blue); color: var(--primary-blue); display:block; text-align:center; margin-top:auto; text-decoration:none;">Learn More</a></div></div>';
                                }
                                $fileExt = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                                $icon = "📄"; $bgColor = "#F1F5F9"; $textColor = "#64748B";
                                if ($fileExt == 'pdf') { $icon = "📕"; $bgColor = "#FEE2E2"; $textColor = "#EF4444"; } 
                                elseif ($fileExt == 'docx') { $icon = "📘"; $bgColor = "#DBEAFE"; $textColor = "#3B82F6"; }
                                elseif ($fileExt == 'pptx' || $fileExt == 'ppt') { $icon = "📙"; $bgColor = "#FFEDD5"; $textColor = "#F97316"; }
                                echo '<div class="card"><div class="card-image" style="background:'.$bgColor.';display:flex;align-items:center;justify-content:center;flex-direction:column;"><div style="font-size:4rem;">'.$icon.'</div><div style="font-weight:700;color:'.$textColor.';margin-top:10px;">'.$fileExt.'</div></div><div class="card-body"><span class="tag">'.htmlspecialchars($row['programme']).'</span><div class="card-title">'.htmlspecialchars($row['title']).'</div><div class="card-meta"><span>'.htmlspecialchars($row['type']).'</span></div><button class="btn-card" onclick="openResourceModal('.$row['id'].')">View Details</button></div></div>';
                            }
                        } else { 
                            if ($userPlan === 'free') {
                                echo '<div style="grid-column: 1 / -1; text-align:center; padding: 40px 20px; background:var(--bg-surface); border:1px dashed var(--border-subtle); border-radius:12px;"><div style="font-size:3rem; margin-bottom:15px;">📚</div><h3 style="color:var(--text-main); margin-bottom:10px;">Didn\'t find what you need?</h3><p style="color:var(--text-muted); margin-bottom:20px;">Check out our sponsor for thousands of verified textbook solutions.</p><a href="#" class="btn-card" style="background:var(--primary-blue); color:white; text-decoration:none;">Search Course Hero</a></div>';
                            } else {
                                echo "<p style='color:#64748B; padding:20px; grid-column:1/-1;'>No resources match your search.</p>"; 
                            }
                        }
                        ?>
                    </div>
                </div>

                <aside class="resource-sidebar">
                    <?php if ($userPlan === 'free') { ?>
                        <div class="card" style="border:1px solid var(--border-subtle); position: sticky; top: 20px;">
                            <strong style="color:var(--text-main);">Sponsored</strong>
                            <div style="margin-top:10px;color:var(--text-muted); font-size:0.95rem;">Ad: Upgrade to Pro for unlimited downloads, direct edits, and no ads.</div>
                            <div style="margin-top:16px;"><a href="./billing.php" class="btn-card" style="display:block; text-align:center; background:transparent; color:var(--primary-blue); border:1px solid var(--primary-blue); box-shadow:none;">View Plans</a></div>
                        </div>
                    <?php } ?>
                </aside>
            </div>
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
                            <div class="input-row"><input type="text" class="modern-input" placeholder="Share your thoughts..."><button class="btn-send">➤</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($userPlan === 'free'): ?>
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
                <div style="background: var(--bg-body); padding: 15px; border-radius: 8px; margin-bottom: 20px;"><strong id="dl-countdown" style="color: var(--primary-blue); font-size: 1.1rem;">Your download will begin in 5 seconds...</strong></div>
                <button id="dl-skip-btn" class="btn-card" style="display: none; background: var(--success); color: white; border: none; width: 100%; padding: 12px; cursor:pointer;">Skip Ad & Download File</button>
            </div>
        </div>
        <?php endif; ?>

        <script src="../js/script.js?v=<?php echo time(); ?>"></script>
    </body>
</html>