<?php
require '../php/db_connect.php';

// STRICT REDIRECT: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$my_id = $_SESSION['user_id'];
$search = isset($_GET['q']) ? $_GET['q'] : '';

// Include the new subscription tracker (This line does all the hard work!)
require_once '../php/check_plan.php';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
    
    <title>CoreShare Tech — My Contributions</title>
    <link rel="icon" type="image/png" href="../images/Gemini_Generated_Image_69zr6i69zr6i69zr.png" sizes="32x32">
    <link rel="stylesheet" href="../css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/resource.css?v=<?php echo time(); ?>">
    <script>const USER_IS_LOGGED_IN = true;</script>
    <?php if ($userPlan === 'free'): ?>
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3596644493360839" crossorigin="anonymous"></script>
    <?php endif; ?>
    </head>
    <body>
        <div class="sidebar-overlay" id="sidebar-overlay"></div>
        <aside class="sidebar" id="sidebar" style="display:flex; flex-direction:column;">
            <div class="brand">
                <span>CoreShare <strong>Tech</strong></span>
                <button id="theme-toggle" class="theme-toggle-btn" title="Toggle Theme">🌙</button>
            </div>
            <nav class="nav" style="margin-top:10px; display:flex; flex-direction:column; gap:10px; flex-grow:1;">
                <a href="./index.php" class="nav-link">Home</a>
                <a href="./dashboard.php" class="nav-link">Dashboard</a>
                <a href="./search.php" class="nav-link">Search</a>
                <a href="./resource.php" class="nav-link">Resource</a>
                <a href="./contributions.php" class="nav-link active">Contributions</a>
                <a href="./contact.php" class="nav-link">Contact</a> 
                <a href="./sources.php" class="nav-link">Other Resources</a>
                <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                    <a href="./moderation.php" class="nav-link">Moderation</a>
                <?php endif; ?>
                <a href="../php/logout.php" class="nav-link" style="color:#EF4444; font-weight:700;">Logout</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <button id="menu-toggle" class="mobile-menu-btn"><span id="toggle-icon">☰</span></button>
            <header class="dashboard-header">
                <div class="header-title">
                    <h1>My Contributions</h1>
                    <p style="color:var(--text-gray); font-size:0.9rem;">Manage the items you've uploaded.</p>
                </div>
                <div class="search-bar">
                    <form action="contributions.php" method="GET" style="width:100%; display:flex;">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search your uploads..." style="flex:1;">
                        <button type="submit" style="background:none; border:none; cursor:pointer;">
                            <span><img src="../images/Search_Magnifying_Glass.svg" alt="Search"></span>
                        </button>
                    </form>
                </div>
            </header>

            <section class="table-section">
                <table class="data-table">
                    <thead><tr><th>Title</th><th>Status</th><th>Downloads</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php
                        if (!empty($search)) {
                            $sql = "SELECT * FROM resources WHERE uploaded_by=? AND title LIKE ? ORDER BY created_at DESC";
                            $stmt = $conn->prepare($sql);
                            $likeSearch = "%$search%";
                            $stmt->bind_param("is", $my_id, $likeSearch);
                        } else {
                            $sql = "SELECT * FROM resources WHERE uploaded_by=? ORDER BY created_at DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $my_id);
                        }
                        
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $statusColor = ($row['status'] == 'published') ? 'color: var(--success); font-weight:600;' : (($row['status'] == 'rejected') ? 'color: #EF4444; font-weight:600;' : 'color: #F59E0B; font-weight:600;');
                                echo '<tr>';
                                echo '<td style="font-weight:600;">'.htmlspecialchars($row['title']).'</td>';
                                echo '<td style="'.$statusColor.'">'.ucfirst($row['status']).'</td>';
                                echo '<td>'.intval($row['downloads']).'</td>';
                                echo '<td class="action-cell">';
                                if ($row['status'] != 'rejected') {
                                    echo '<button class="btn-action btn-edit" onclick="openEditModal('.$row['id'].')">Edit</button>';
                                }
                                echo '<button class="btn-action btn-delete" onclick="deleteResource('.$row['id'].', this)">Delete</button>';
                                echo '</td></tr>';
                            }
                        } else { echo "<tr><td colspan='4' style='text-align:center; padding:20px;'>No contributions found.</td></tr>"; }
                        $stmt->close();
                        ?>
                    </tbody>
                </table>
            </section>
        </main>

        <div class="new-modal-overlay" id="edit-modal">
            <div class="new-modal-window" style="max-height:90vh; max-width:600px; padding:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h2 style="margin:0;">Edit Resource</h2>
                    <button class="new-close-btn" onclick="document.getElementById('edit-modal').classList.remove('open')">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="edit-resource-form" class="form-grid">
                    <input type="hidden" name="id" id="edit-resource-id">
                    <div class="input-group"><label class="category-label">Resource Title</label><input type="text" name="title" id="edit-title" class="input-field" required placeholder="Introduction to Programming"></div>
                    <div class="input-group"><label class="category-label">Course Name</label><input type="text" name="course_name" id="edit-course" class="input-field" required placeholder="CSNS 141 - Digital Electronics"></div>
                    <div class="input-group"><label class="category-label">Programme</label><input type="text" name="programme" id="edit-programme" class="input-field" required placeholder="BSc. Computer Science"></div>
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
                    <div class="upload-actions" style="margin-top:20px;">
                        <button type="submit" class="btn-card btn-upload">Save Changes</button>
                        <button type="button" onclick="document.getElementById('edit-modal').classList.remove('open')" class="btn-card btn-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        <script src="../js/script.js?v=<?php echo time(); ?>"></script>
    </body>
</html>