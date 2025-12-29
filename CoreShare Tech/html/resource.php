<?php
session_start();
require '../php/db_connect.php';
$isLoggedIn = isset($_SESSION['user_id']); 
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
      <script>const USER_IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;</script>
  </head>
  <body>
      <aside class="sidebar" id="sidebar">
        <div class="brand"><span>CoreShare <strong>Tech</strong></span><button class="sidebar-close-btn" onclick="document.getElementById('sidebar').classList.remove('open')">×</button></div>
        <nav class="nav" style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="search.php" class="nav-link">Search</a>
            <a href="resource.php" class="nav-link active">Resource</a>
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
              <div class="header-title"><h1>University Library</h1><p style="color:var(--text-gray); font-size:0.9rem;">Browse lecture notes, papers, and study guides.</p></div>
              <div class="search-actions" style="display:flex; gap:15px; align-items:center; flex:1; justify-content: flex-end;">
                  <div class="search-bar" style="width: 100%; max-width: 400px;"><form action="search.php" method="GET" style="width:100%; display:flex; align-items:center;"><input type="text" name="q" placeholder="Filter by course code, topic..." style="flex:1; border:none; outline:none; background:transparent;"><button type="submit" style="background:none; border:none; cursor:pointer; padding:0;"><span style="color:var(--primary-blue); font-weight:bold; font-size:1.2rem;">🔍</span></button></form></div>
                  
                  <button class="btn-card" onclick="openUploadModal()" style="width:auto; padding: 12px 20px; white-space:nowrap; background-color: var(--success); color:white; box-shadow: var(--shadow-sm); font-weight: 700;">+ Add Resource</button>
              </div>
          </header>

          <div class="grid-container">
            <?php
            $sql = "SELECT * FROM resources WHERE status='published' ORDER BY created_at DESC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $fileExt = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                    $icon = "📄"; $bgColor = "#F1F5F9"; $textColor = "#64748B";
                    if ($fileExt == 'pdf') { $icon = "📕"; $bgColor = "#FEE2E2"; $textColor = "#EF4444"; } 
                    elseif ($fileExt == 'docx') { $icon = "📘"; $bgColor = "#DBEAFE"; $textColor = "#3B82F6"; }
                    elseif ($fileExt == 'pptx' || $fileExt == 'ppt') { $icon = "📙"; $bgColor = "#FFEDD5"; $textColor = "#F97316"; }
                    
                    echo '<div class="card"><div class="card-image" style="background:'.$bgColor.';display:flex;align-items:center;justify-content:center;flex-direction:column;"><div style="font-size:4rem;">'.$icon.'</div><div style="font-weight:700;color:'.$textColor.';margin-top:10px;">'.$fileExt.'</div></div><div class="card-body"><span class="tag">'.htmlspecialchars($row['subject']).'</span><div class="card-title">'.htmlspecialchars($row['title']).'</div><div class="card-meta"><span>'.htmlspecialchars($row['type']).'</span></div><button class="btn-card" onclick="openResourceModal('.$row['id'].')">View Details</button></div></div>';
                }
            } else { echo "<p style='color:#64748B; padding:20px;'>No resources found.</p>"; }
            ?>
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
                    <div class="reviews-scroll-area"><div class="reviews-list"></div></div>
                    <div class="review-input-area">
                        <div class="star-select-row"><span data-v="1">★</span><span data-v="2">★</span><span data-v="3">★</span><span data-v="4">★</span><span data-v="5">★</span></div>
                        <div class="input-row"><input type="text" class="modern-input" placeholder="Share your thoughts on this resource..."><button class="btn-send">➤</button></div>
                    </div>
                </div>
            </div>
        </div>
      </div>

      <div class="new-modal-overlay" id="upload-modal">
        <div class="new-modal-window" style="max-height:90vh;">
            <div class="new-modal-header">
                <div class="header-left">
                    <h2 class="resource-title">Upload Resource</h2>
                    <div class="resource-meta">Contribute to the library</div>
                </div>
                <button class="new-close-btn" onclick="closeUploadModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form action="../php/upload.php" method="POST" enctype="multipart/form-data" id="main-upload-form" style="display:flex; flex:1; overflow:hidden;">
                <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">           
                
                <input type="file" id="file-input" name="resource_file" accept=".pdf,.docx,.doc,.pptx,.ppt,.txt,.md,.rtf,.odp">

                <div class="new-modal-body" style="width:100%;">
                    
                    <div class="upload-left-col">
                        <div class="modal-drop-zone" id="drop-zone">
                            <div class="file-icon">📂</div>
                            <div class="file-text">
                                Drag & Drop file here<br><span style="color:var(--primary-blue); text-decoration:underline;">or Browse</span>
                            </div>
                        </div>
                        <div class="upload-helper-text">
                            Supported Formats: PDF, DOCX, PPTX<br>Max Size: 500MB
                        </div>
                    </div>
                    
                    <div class="modal-col-right" style="background:#fff;">
                        <div class="upload-placeholder" id="upload-placeholder">
                            <div class="upload-placeholder-icon">⬅️</div>
                            <div class="upload-placeholder-text">Select a file to continue details</div>
                        </div>

                        <div class="upload-form-container" id="category-box" style="display:none;"> 
                            <div class="form-grid">
                                <div class="input-group">
                                    <label class="category-label">Resource Title</label>
                                    <input type="text" name="title" class="input-field" placeholder="e.g. Intro to Data Structures" required>
                                </div>
                                <div class="input-group">
                                    <label class="category-label">Course Code</label>
                                    <input type="text" name="course_name" class="input-field" placeholder="e.g. CS202" required>
                                </div>
                                <div class="form-row-split">
                                    <div class="input-group">
                                        <label class="category-label">Type</label>
                                        <select class="category-select" name="type" required>
                                            <option value="" disabled selected>Select...</option>
                                            <option value="Lecture Notes">Lecture Notes</option>
                                            <option value="Exam Paper">Exam Paper</option>
                                            <option value="Assignment">Assignment</option>
                                            <option value="Textbook">Textbook</option>
                                            <option value="Presentation">Presentation</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label class="category-label">Level</label>
                                        <select class="category-select" name="grade" required>
                                            <option value="" disabled selected>Select...</option>
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
                                    <label class="category-label">Programme</label>
                                    <select class="category-select" name="subject" required>
                                        <option value="" disabled selected>Select Programme...</option>
                                        <optgroup label="FoCIS">
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
                     <div class="upload-actions">
                                    <button type="submit" class="btn-card btn-upload">Upload Resource</button>
                                    <button type="button" id="btn-reset-upload" class="btn-card btn-cancel">Cancel</button>
                                </div>
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