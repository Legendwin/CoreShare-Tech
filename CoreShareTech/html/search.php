<?php
// Removed session_start() because db_connect.php handles it now
require '../php/db_connect.php';

$isLoggedIn = isset($_SESSION['user_id']);

// Include the new subscription tracker 
require_once '../php/check_plan.php';

// Grab Search & Filter Parameters
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$filterType = isset($_GET['type']) ? $_GET['type'] : '';
$filterGrade = isset($_GET['grade_level']) ? $_GET['grade_level'] : '';
$minRating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Upgraded SQL with LEFT JOIN to calculate average rating from reviews
$sql = "SELECT r.*, IFNULL(AVG(rv.rating), 0) as avg_rating 
        FROM resources r 
        LEFT JOIN reviews rv ON r.id = rv.resource_id 
        WHERE r.status='published'";

// 1. Apply Search Query
if (!empty($searchQuery)) {
    $cleanQuery = $conn->real_escape_string($searchQuery);
    $sql .= " AND (r.title LIKE '%$cleanQuery%' OR r.course_name LIKE '%$cleanQuery%' OR r.programme LIKE '%$cleanQuery%' OR r.type LIKE '%$cleanQuery%')";
}

// 2. Apply Type Filter
if (!empty($filterType)) {
    $cleanType = $conn->real_escape_string($filterType);
    $sql .= " AND r.type = '$cleanType'";
}

// 3. Apply Grade Level Filter
if (!empty($filterGrade)) {
    $cleanGrade = $conn->real_escape_string($filterGrade);
    $sql .= " AND r.grade_level = '$cleanGrade'";
}

// Group by resource ID so the AVG() math works correctly
$sql .= " GROUP BY r.id";

// 4. Apply Rating Filter (HAVING must come after GROUP BY)
if ($minRating > 0) {
    $sql .= " HAVING avg_rating >= $minRating";
}

$sql .= " ORDER BY r.created_at DESC";
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
        <link rel="stylesheet" href="../css/search.css?v=<?php echo time(); ?>">
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
            </nav>
        </aside>

        <main class="main-content">
            <button id="menu-toggle" class="mobile-menu-btn"><span id="toggle-icon">☰</span></button>
            <header class="dashboard-header" style="margin-bottom: 20px;">
                <div class="header-title">
                    <h1>Search Results</h1>
                    <p style="color:var(--text-gray); font-size:0.9rem;">
                        Found <?php echo $numResults; ?> results <?php if(!empty($searchQuery)) echo 'for "'.htmlspecialchars($searchQuery).'"'; ?>
                    </p>
                </div>
            </header>

            <div class="search-page-layout">
                
                <aside class="search-filters-aside">
                    <form action="search.php" method="GET" id="filter-form">
                        <h3>Filter Results</h3>
                        
                        <label>Resource Type</label>
                        <select name="type">
                            <option value="">All Types</option>
                            <option value="Lecture Notes" <?php if($filterType=='Lecture Notes') echo 'selected'; ?>>Lecture Notes</option>
                            <option value="Exam Paper" <?php if($filterType=='Exam Paper') echo 'selected'; ?>>Exam Paper</option>
                            <option value="Assignment" <?php if($filterType=='Assignment') echo 'selected'; ?>>Assignment</option>
                            <option value="Textbook" <?php if($filterType=='Textbook') echo 'selected'; ?>>Textbook</option>
                            <option value="Presentation" <?php if($filterType=='Presentation') echo 'selected'; ?>>Presentation</option>
                            <option value="Other" <?php if($filterType=='Other') echo 'selected'; ?>>Other</option>
                        </select>

                        <label>Grade Level</label>
                        <select name="grade_level">
                            <option value="">All Levels</option>
                            <option value="Year 1" <?php if($filterGrade=='Year 1') echo 'selected'; ?>>Year 1</option>
                            <option value="Year 2" <?php if($filterGrade=='Year 2') echo 'selected'; ?>>Year 2</option>
                            <option value="Year 3" <?php if($filterGrade=='Year 3') echo 'selected'; ?>>Year 3</option>
                            <option value="Year 4" <?php if($filterGrade=='Year 4') echo 'selected'; ?>>Year 4</option>
                            <option value="Grad" <?php if($filterGrade=='Grad') echo 'selected'; ?>>Graduate</option>
                            <option value="PhD" <?php if($filterGrade=='PhD') echo 'selected'; ?>>PhD</option>
                        </select>
                        
                        <label>Minimum Rating</label>
                        <input type="range" name="rating" min="0" max="5" step="1" value="<?php echo $minRating; ?>" oninput="document.getElementById('rating-val').innerText = this.value > 0 ? this.value + ' Stars' : 'Any'">
                        <div class="rating-labels">
                            <span>Any</span>
                            <span>5 Stars</span>
                        </div>
                        <div class="rating-value" id="rating-val"><?php echo $minRating > 0 ? $minRating . ' Stars' : 'Any'; ?></div>

                        <button type="submit">Apply Filters</button>
                    </form>
                </aside>

                <div class="search-results-column">
                    
                    <div class="large-search-bar">
                        <img src="../images/Search_Magnifying_Glass.svg" alt="Search" width="20" height="20" style="margin-right: 12px; opacity: 0.6;">
                        <input type="text" form="filter-form" name="q" placeholder="Search for courses, topics, or file types..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                    </div>

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
                                elseif ($fileExt == 'docx' || $fileExt == 'doc') { $icon = "📘"; $bgColor = "#DBEAFE"; $textColor = "#3B82F6"; }
                                elseif ($fileExt == 'pptx' || $fileExt == 'ppt') { $icon = "📙"; $bgColor = "#FFEDD5"; $textColor = "#F97316"; }
                                echo '<div class="card"><div class="card-image" style="background:'.$bgColor.';display:flex;align-items:center;justify-content:center;flex-direction:column;"><div style="font-size:4rem;">'.$icon.'</div><div style="font-weight:700;color:'.$textColor.';margin-top:10px;">'.$fileExt.'</div></div><div class="card-body"><span class="tag">'.htmlspecialchars($row['programme']).'</span><div class="card-title">'.htmlspecialchars($row['title']).'</div><div class="card-meta"><span>'.htmlspecialchars($row['type']).'</span></div><button class="btn-card" onclick="openResourceModal('.$row['id'].')">View Details</button></div></div>';
                            }
                        } else { 
                            if ($userPlan === 'free') {
                                echo '<div style="grid-column: 1 / -1; text-align:center; padding: 40px 20px; background:var(--bg-surface); border:1px dashed var(--border-subtle); border-radius:12px;"><div style="font-size:3rem; margin-bottom:15px;">📚</div><h3 style="color:var(--text-main); margin-bottom:10px;">Didn\'t find what you need?</h3><p style="color:var(--text-muted); margin-bottom:20px;">Check out our sponsor for thousands of verified textbook solutions.</p><a href="#" class="btn-card" style="background:var(--primary-blue); color:white; text-decoration:none; display:inline-block; max-width: 200px;">Search Course Hero</a></div>';
                            } else {
                                echo "<p style='color:#64748B; padding:20px; grid-column:1/-1;'>No resources match your search.</p>"; 
                            }
                        }
                        ?>
                    </div>
                </div>
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

        <script src="../js/script.js?v=<?php echo time(); ?>"></script>
    </body>
</html>