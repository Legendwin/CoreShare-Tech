<?php
require '../php/db_connect.php';
$isLoggedIn = isset($_SESSION['user_id']); 

// Include the new subscription tracker (This line does all the hard work!)
require_once '../php/check_plan.php';

$canUpload = true;
if ($isLoggedIn && $userPlan === 'free') {
    $uid = intval($_SESSION['user_id']);
    $cntRes = $conn->query("SELECT COUNT(*) as c FROM resources WHERE uploaded_by = '$uid'");
    $cRow = $cntRes->fetch_assoc();
    if (intval($cRow['c']) >= 5) $canUpload = false;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CoreShare Tech - All Resources</title>
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
                <a href="./search.php" class="nav-link">Search</a>
                <a href="./resource.php" class="nav-link active">Resource</a>
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
            <header class="dashboard-header">
                <div class="header-title"><h1>University Library</h1><p style="color:var(--text-gray); font-size:0.9rem;">Browse lecture notes, papers, and study guides.</p></div>
                <div class="search-actions">
                    <div class="search-bar resource-search"><form action="search.php" method="GET"><input type="text" name="q" placeholder="Filter by course code, topic..."><button type="submit"><span><img src="../images/Search_Magnifying_Glass.svg" alt="Search"></span></button></form></div>
                    <?php if (!$isLoggedIn): ?><button class="btn-card btn-add-resource" onclick="openUploadModal()">+ Add Resource</button>
                    <?php else: ?>
                        <?php if ($canUpload): ?><button class="btn-card btn-add-resource" onclick="openUploadModal()">+ Add Resource</button>
                        <?php else: ?><button class="btn-card disabled btn-add-resource" disabled>Upload limit reached</button><?php endif; ?>
                    <?php endif; ?>
                </div>
            </header>

            <div class="resource-page-layout">
                <div class="resource-main-col">
                    <div class="grid-container">
                        <?php
                        $sql = "SELECT * FROM resources WHERE status='published' ORDER BY created_at DESC";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            $count = 0;
                            while($row = $result->fetch_assoc()) {
                                $count++;
                                if ($userPlan === 'free' && $count > 0 && $count % 4 == 0) {
                                    echo '<div class="card" style="border: 1px solid var(--border-subtle); background: var(--bg-surface);"><div class="card-body" style="display:flex; flex-direction:column; height:100%;"><span class="tag" style="background: var(--border-subtle); color: var(--text-muted); align-self:flex-start;">Sponsored Ad</span><div class="card-title" style="margin-top: 10px;">Grammarly for Students</div><a href="#" class="btn-card" style="background: transparent; border: 1px solid var(--primary-blue); color: var(--primary-blue); display:block; text-align:center; margin-top:auto; text-decoration:none;">Learn More</a></div></div>';
                                }
                                $fileExt = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                                $icon = "📄"; $bgColor = "#F1F5F9"; $textColor = "#64748B";
                                if ($fileExt == 'pdf') { $icon = "📕"; $bgColor = "#FEE2E2"; $textColor = "#EF4444"; } 
                                elseif ($fileExt == 'docx') { $icon = "📘"; $bgColor = "#DBEAFE"; $textColor = "#3B82F6"; }
                                elseif ($fileExt == 'pptx' || $fileExt == 'ppt') { $icon = "📙"; $bgColor = "#FFEDD5"; $textColor = "#F97316"; }
                                echo '<div class="card"><div class="card-image" style="background:'.$bgColor.';display:flex;align-items:center;justify-content:center;flex-direction:column;"><div style="font-size:4rem;">'.$icon.'</div><div style="font-weight:700;color:'.$textColor.';margin-top:10px;">'.$fileExt.'</div></div><div class="card-body"><span class="tag">'.htmlspecialchars($row['programme']).'</span><div class="card-title">'.htmlspecialchars($row['title']).'</div><div class="card-meta"><span>'.htmlspecialchars($row['type']).'</span></div><button class="btn-card" onclick="openResourceModal('.$row['id'].')">View Details</button></div></div>';
                            }
                        } else { echo "<p style='color:#64748B; padding:20px; grid-column:1/-1;'>No resources found.</p>"; }
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

        <div class="new-modal-overlay" id="upload-modal">
            <div class="new-modal-window" style="max-height:90vh;">
                <div class="new-modal-header">
                    <div class="header-left"><h2 class="resource-title">Upload Resource</h2><div class="resource-meta">Contribute to the library</div></div>
                    <button class="new-close-btn" onclick="closeUploadModal()"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                </div>
                <form action="../php/upload.php" method="POST" enctype="multipart/form-data" id="main-upload-form" style="display:flex; flex:1; overflow:hidden;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">           
                    <input type="file" id="file-input" name="resource_file" accept=".pdf,.docx,.doc,.pptx,.ppt,.txt,.md,.rtf,.odp">
                    <div class="new-modal-body" style="width:100%;">
                        <div class="upload-left-col">
                            <div class="modal-drop-zone" id="drop-zone"><div class="file-icon">📂</div><div class="file-text">Drag & Drop file here<br><span style="color:var(--primary-blue); text-decoration:underline;">or Browse</span></div></div>
                            <div class="upload-helper-text">Supported Formats: PDF, DOCX, PPTX<br>Max Size: 500MB</div>
                        </div>
                        <div class="modal-col-right">
                            <div class="upload-placeholder" id="upload-placeholder"><div class="upload-placeholder-icon">⬅️</div><div class="upload-placeholder-text">Select a file to continue</div></div>
                            <div class="upload-form-container" id="category-box" style="display:none;"> 
                                <div class="form-grid">
                                    <div class="input-group"><label class="category-label">Resource Title</label><input type="text" name="title" class="input-field" required></div>
                                    <div class="input-group"><label class="category-label">Course Name</label><input type="text" name="course_name" class="input-field" required></div>
                                    <div class="input-group"><label class="category-label">Programme</label><input type="text" name="programme" id="edit-programme" class="input-field" required></div>
                                    <div class="form-row-split">
                                        <div class="input-group"><label class="category-label">Type</label>
                                            <select class="category-select" name="type" id="edit-type" required>
                                                <option value="Lecture Notes">Lecture Notes</option>
                                                <option value="Exam Paper">Exam Paper</option>
                                                <option value="Assignment">Assignment</option>
                                                <option value="Textbook">Textbook</option>
                                                <option value="Presentation">Presentation</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="input-group"><label class="category-label">Level</label>
                                            <select class="category-select" name="grade_level" id="edit-grade" required>
                                                <option value="Year 1">Year 1</option>
                                                <option value="Year 2">Year 2</option>
                                                <option value="Year 3">Year 3</option>
                                                <option value="Year 4">Year 4</option>
                                                <option value="Grad">Graduate</option>
                                                <option value="PhD">PhD</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="upload-actions"><button type="submit" class="btn-card btn-upload">Upload Resource</button><button type="button" id="btn-reset-upload" class="btn-card btn-cancel">Cancel</button></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <script src="../js/script.js?v=<?php echo time(); ?>"></script>
    </body>
</html>