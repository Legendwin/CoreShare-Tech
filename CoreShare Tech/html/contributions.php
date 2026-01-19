<?php
session_start();
require '../php/db_connect.php';

// STRICT REDIRECT: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$my_id = $_SESSION['user_id'];
$search = isset($_GET['q']) ? $_GET['q'] : '';
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
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <span>CoreShare <strong>Tech</strong></span>
            <button id="theme-toggle" class="theme-toggle-btn" title="Toggle Theme">🌙</button>
            <button class="sidebar-close-btn" onclick="document.getElementById('sidebar').classList.remove('open')">×</button>
        </div>
        <nav class="nav" style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="search.php" class="nav-link">Search</a>
            <a href="resource.php" class="nav-link">Resource</a>
            <a href="contributions.php" class="nav-link active">Contributions</a>
            <a href="contact.php" class="nav-link">Contact</a> 
            <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                <a href="moderation.php" class="nav-link">Moderation</a>
            <?php endif; ?>
            <a href="../php/logout.php" class="nav-link" style="color:#EF4444;">Logout</a>
        </nav>
    </aside>
    <main class="main-content">
        <button id="menu-toggle" class="mobile-menu-btn">☰</button>
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
                    </button></form></div></header>
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
                            $statusColor = ($row['status'] == 'published') ? 'var(--success)' : '#F59E0B';
                            $statusLabel = ucfirst($row['status']);
                            if ($row['status'] == 'rejected') $statusColor = '#EF4444';
                            
                            echo '<tr>
                                    <td style="font-weight:600;">'.htmlspecialchars($row['title']).'</td>
                                    <td><span class="status-dot" style="background-color:'.$statusColor.';"></span>'.$statusLabel.'</td>
                                    <td>'.$row['downloads'].'</td>
                                    <td class="action-cell">
                                        <button class="btn-action btn-edit" onclick="openEditModal('.$row['id'].')">Edit</button>
                                        <button class="btn-action btn-delete" onclick="deleteResource('.$row['id'].', this)">Delete</button>
                                    </td>
                                </tr>';
                        }
                    } else { 
                        echo '<tr><td colspan="4" style="text-align:center; padding:20px; color:#64748B;">No contributions found.</td></tr>'; 
                    }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </section>
    </main>
    
    <div class="new-modal-overlay" id="edit-modal">
        <div class="new-modal-window" style="max-width: 600px; height: auto; max-height: 90vh;">
            <div class="new-modal-header">
                <div class="header-left">
                    <h2 class="resource-title" style="font-size:1.4rem;">Edit Resource</h2>
                </div>
                <button class="new-close-btn" onclick="document.getElementById('edit-modal').classList.remove('open')">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form id="edit-resource-form" style="display:flex; flex-direction:column; padding: 24px; gap: 15px; overflow-y: auto;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="resource_id" id="edit-resource-id">

                <div class="input-group">
                    <label style="font-weight:600; font-size:0.9rem; margin-bottom:5px;">Title</label>
                    <input type="text" name="title" id="edit-title" class="input-field" required>
                </div>

                <div class="input-group">
                    <label style="font-weight:600; font-size:0.9rem; margin-bottom:5px;">Course Code</label>
                    <input type="text" name="course_name" id="edit-course" class="input-field" required>
                </div>

                <div class="form-row-split" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="input-group">
                        <label style="font-weight:600; font-size:0.9rem; margin-bottom:5px;">Type</label>
                        <select name="type" id="edit-type" class="category-select" required>
                            <option value="Lecture Notes">Lecture Notes</option>
                            <option value="Exam Paper">Exam Paper</option>
                            <option value="Assignment">Assignment</option>
                            <option value="Textbook">Textbook</option>
                            <option value="Presentation">Presentation</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label style="font-weight:600; font-size:0.9rem; margin-bottom:5px;">Level</label>
                        <select name="grade" id="edit-grade" class="category-select" required>
                            <option value="Year 1">Year 1 (Freshman)</option>
                            <option value="Year 2">Year 2 (Sophomore)</option>
                            <option value="Year 3">Year 3 (Junior)</option>
                            <option value="Year 4">Year 4 (Senior)</option>
                            <option value="Grad">Graduate / Masters</option>
                            <option value="PhD">PhD</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <label style="font-weight:600; font-size:0.9rem; margin-bottom:5px;">Programme</label>
                    <select name="subject" id="edit-subject" class="category-select" required>
                        <optgroup label="Faculty of Computing & Info Systems (FoCIS)">
                            <option value="BSc Computer Science">BSc. Computer Science</option>
                            <option value="BSc IT">BSc. Information Technology</option>
                            <option value="BSc Software Engineering">BSc. Software Engineering</option>
                            <option value="BSc Data Science">BSc. Data Science & Analytics</option>
                            <option value="BSc Info Systems">BSc. Information Systems</option>
                            <option value="BSc Cyber Security">BSc. Cyber Security</option>
                            <option value="BSc Network Systems">BSc. Network & Systems Admin</option>
                            <option value="Diploma IT">Diploma in IT</option>
                        </optgroup>
                        <optgroup label="Engineering">
                            <option value="BSc Telecom Engineering">BSc. Telecom Engineering</option>
                            <option value="BSc Computer Engineering">BSc. Computer Engineering</option>
                            <option value="BSc Electrical Engineering">BSc. Electrical & Electronic Eng.</option>
                            <option value="BSc Mathematics">BSc. Mathematics</option>
                            <option value="BSc Statistics">BSc. Computational Statistics</option>
                        </optgroup>
                        <optgroup label="Business">
                            <option value="BSc Business Admin">BSc. Business Administration</option>
                            <option value="BSc Accounting">BSc. Accounting</option>
                            <option value="BSc Banking Finance">BSc. Banking & Finance</option>
                            <option value="BSc Economics">BSc. Economics</option>
                            <option value="BSc Procurement">BSc. Procurement & Logistics</option>
                            <option value="BSc Marketing">BSc. Marketing</option>
                            <option value="Diploma Business Admin">Diploma in Business Administration</option>
                        </optgroup>
                    </select>
                </div>

                <div style="margin-top:10px;">
                    <button type="submit" class="btn-card" style="width:100%; background:var(--primary-blue);">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>