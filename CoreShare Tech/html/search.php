<?php
session_start();
require '../php/db_connect.php';

$isLoggedIn = isset($_SESSION['user_id']); 

$search = isset($_GET['q']) ? $_GET['q'] : '';
$progFilter = isset($_GET['programme']) ? $_GET['programme'] : '';
$levelFilter = isset($_GET['level']) ? $_GET['level'] : '';
$ratingFilter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Base query
$sql = "SELECT r.*, AVG(rev.rating) as avg_rating 
        FROM resources r 
        LEFT JOIN reviews rev ON r.id = rev.resource_id 
        WHERE r.status='published'";

// Dynamically build Prepared Statement params
$types = "";
$params = [];

if (!empty($search)) {
    $sql .= " AND (r.title LIKE ? OR r.subject LIKE ?)";
    $searchTerm = "%" . $search . "%";
    $types .= "ss";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
if (!empty($progFilter)) {
    $sql .= " AND r.subject = ?";
    $types .= "s";
    $params[] = $progFilter;
}
if (!empty($levelFilter)) {
    $sql .= " AND r.grade_level = ?";
    $types .= "s";
    $params[] = $levelFilter;
}

$sql .= " GROUP BY r.id";
if ($ratingFilter > 0) {
    // HAVING clause can't easily use params in some MySQL versions depending on config, 
    // but integers are safe to inject directly.
    $sql .= " HAVING avg_rating >= $ratingFilter";
}
$sql .= " ORDER BY r.created_at DESC";

// Execute Prepared Statement
$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CoreShare Tech — Search</title>
  <link rel="icon" type="image/png" href="../images/Gemini_Generated_Image_69zr6i69zr6i69zr.png" sizes="32x32">
  <link rel="stylesheet" href="../css/styles.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../css/search.css?v=<?php echo time(); ?>">
  <script>const USER_IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;</script>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="brand"><span>CoreShare <strong>Tech</strong></span><button class="sidebar-close-btn" id="sidebar-close">×</button></div>
        <nav class="nav" style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="search.php" class="nav-link active">Search</a>
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

    <main class="main-content search-page-layout">
      <button id="menu-toggle" class="mobile-menu-btn">☰</button>
      <aside class="search-filters-aside">
        <h3>Filters</h3>
        <form action="search.php" method="GET" id="filterForm">
            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
            
            <label>Programme / Course</label>
            <select name="programme" onchange="this.form.submit()">
                <option value="">All Programmes</option>
                <optgroup label="Faculty of Computing & Info Systems (FoCIS)">
                    <option value="BSc Computer Science" <?php if($progFilter == 'BSc Computer Science') echo 'selected'; ?>>BSc. Computer Science</option>
                    <option value="BSc IT" <?php if($progFilter == 'BSc IT') echo 'selected'; ?>>BSc. Information Technology</option>
                    <option value="BSc Software Engineering" <?php if($progFilter == 'BSc Software Engineering') echo 'selected'; ?>>BSc. Software Engineering</option>
                    <option value="BSc Data Science" <?php if($progFilter == 'BSc Data Science') echo 'selected'; ?>>BSc. Data Science & Analytics</option>
                    <option value="BSc Info Systems" <?php if($progFilter == 'BSc Info Systems') echo 'selected'; ?>>BSc. Information Systems</option>
                    <option value="BSc Cyber Security" <?php if($progFilter == 'BSc Cyber Security') echo 'selected'; ?>>BSc. Cyber Security</option>
                    <option value="BSc Network Systems" <?php if($progFilter == 'BSc Network Systems') echo 'selected'; ?>>BSc. Network & Systems Admin</option>
                    <option value="Diploma IT" <?php if($progFilter == 'Diploma IT') echo 'selected'; ?>>Diploma in IT</option>
                </optgroup>
                <optgroup label="Faculty of Engineering (FoE)">
                    <option value="BSc Telecom Engineering" <?php if($progFilter == 'BSc Telecom Engineering') echo 'selected'; ?>>BSc. Telecom Engineering</option>
                    <option value="BSc Computer Engineering" <?php if($progFilter == 'BSc Computer Engineering') echo 'selected'; ?>>BSc. Computer Engineering</option>
                    <option value="BSc Electrical Engineering" <?php if($progFilter == 'BSc Electrical Engineering') echo 'selected'; ?>>BSc. Electrical & Electronic Eng.</option>
                    <option value="BSc Mathematics" <?php if($progFilter == 'BSc Mathematics') echo 'selected'; ?>>BSc. Mathematics</option>
                    <option value="BSc Statistics" <?php if($progFilter == 'BSc Statistics') echo 'selected'; ?>>BSc. Computational Statistics</option>
                </optgroup>
                <optgroup label="GCTU Business School">
                    <option value="BSc Business Admin" <?php if($progFilter == 'BSc Business Admin') echo 'selected'; ?>>BSc. Business Administration</option>
                    <option value="BSc Accounting" <?php if($progFilter == 'BSc Accounting') echo 'selected'; ?>>BSc. Accounting</option>
                    <option value="BSc Banking Finance" <?php if($progFilter == 'BSc Banking Finance') echo 'selected'; ?>>BSc. Banking & Finance</option>
                    <option value="BSc Economics" <?php if($progFilter == 'BSc Economics') echo 'selected'; ?>>BSc. Economics</option>
                    <option value="BSc Procurement" <?php if($progFilter == 'BSc Procurement') echo 'selected'; ?>>BSc. Procurement & Logistics</option>
                    <option value="BSc Marketing" <?php if($progFilter == 'BSc Marketing') echo 'selected'; ?>>BSc. Marketing</option>
                    <option value="Diploma Business" <?php if($progFilter == 'Diploma Business') echo 'selected'; ?>>Diploma in Business Admin</option>
                </optgroup>
            </select>

            <label>Course Level</label>
            <select name="level" onchange="this.form.submit()">
              <option value="">All Levels</option>
              <option value="Year 1" <?php if($levelFilter == 'Year 1') echo 'selected'; ?>>Year 1 (Freshman)</option>
              <option value="Year 2" <?php if($levelFilter == 'Year 2') echo 'selected'; ?>>Year 2 (Sophomore)</option>
              <option value="Year 3" <?php if($levelFilter == 'Year 3') echo 'selected'; ?>>Year 3 (Junior)</option>
              <option value="Year 4" <?php if($levelFilter == 'Year 4') echo 'selected'; ?>>Year 4 (Senior)</option>
              <option value="Grad" <?php if($levelFilter == 'Grad') echo 'selected'; ?>>Graduate / Masters</option>
              <option value="PhD" <?php if($levelFilter == 'PhD') echo 'selected'; ?>>PhD</option>
            </select>
            
            <label>Min. Rating</label>
            <div class="rating-labels"><span>1★</span><span>5★</span></div>
            <input type="range" name="rating" min="0" max="5" value="<?php echo $ratingFilter; ?>" onchange="this.form.submit()">
            <div class="rating-value"><?php echo $ratingFilter > 0 ? $ratingFilter . "+ Stars" : "Any Rating"; ?></div>
            <button type="submit">Apply Filters</button>
        </form>
      </aside>
      
      <div class="search-results-column">
        <form action="search.php" method="GET">
            <input type="hidden" name="programme" value="<?php echo htmlspecialchars($progFilter); ?>">
            <input type="hidden" name="level" value="<?php echo htmlspecialchars($levelFilter); ?>">
            <input type="hidden" name="rating" value="<?php echo $ratingFilter; ?>">
            <div class="large-search-bar"><span class="search-icon">🔍</span><input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search resources, courses..."></div>
        </form>

        <div class="grid-container">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $stars = $row['avg_rating'] ? number_format($row['avg_rating'], 1) : 'New';
                    $fileExt = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                    $icon = "📄"; $bgColor = "#F1F5F9"; $textColor = "#64748B";
                    if ($fileExt == 'pdf') { $icon = "📕"; $bgColor = "#FEE2E2"; $textColor = "#EF4444"; } 
                    elseif ($fileExt == 'docx' || $fileExt == 'doc') { $icon = "📘"; $bgColor = "#DBEAFE"; $textColor = "#3B82F6"; } 
                    
                    // FIXED: Now passing ID (int) instead of Title (string) to avoid quoting errors in JS
                    echo '<div class="card"><div class="card-image" style="background:'.$bgColor.';display:flex;align-items:center;justify-content:center;flex-direction:column;"><div style="font-size:4rem;">'.$icon.'</div><div style="font-weight:700;color:'.$textColor.';margin-top:10px;">'.$fileExt.'</div></div><div class="card-body"><span class="tag">'.htmlspecialchars($row['subject']).'</span><div class="card-title">'.htmlspecialchars($row['title']).'</div><div class="card-meta"><span>'.htmlspecialchars($row['grade_level']).'</span><span style="color:#F59E0B;">★ '.$stars.'</span></div><button class="btn-card" onclick="openResourceModal('.$row['id'].')">View Details</button></div></div>';
                }
            } else { echo '<p style="color:var(--text-gray);">No results found matching your criteria.</p>'; }
            ?>
        </div>
      </div>
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