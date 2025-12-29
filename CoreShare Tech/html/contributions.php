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
        <div class="brand"><span>CoreShare <strong>Tech</strong></span><button class="sidebar-close-btn" onclick="document.getElementById('sidebar').classList.remove('open')">×</button></div>
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
        <header class="dashboard-header"><div class="header-title"><h1>My Contributions</h1><p style="color:var(--text-gray); font-size:0.9rem;">Manage the items you've uploaded.</p></div><div class="search-bar"><form action="contributions.php" method="GET" style="width:100%; display:flex;"><input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search your uploads..." style="flex:1;"><button type="submit" style="background:none; border:none; cursor:pointer;"><span style="color:var(--primary-blue); font-weight:bold;">🔍</span></button></form></div></header>
        <section class="table-section">
            <table class="data-table">
                <thead><tr><th>Title</th><th>Status</th><th>Downloads</th></tr></thead>
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
                            
                            // UPDATED: Removed Action Buttons cell
                            echo '<tr>
                                    <td style="font-weight:600;">'.htmlspecialchars($row['title']).'</td>
                                    <td><span class="status-dot" style="background-color:'.$statusColor.';"></span>'.$statusLabel.'</td>
                                    <td>'.$row['downloads'].'</td>
                                  </tr>';
                        }
                    } else { echo '<tr><td colspan="3" style="text-align:center; padding:20px;">No contributions found.</td></tr>'; }
                    $stmt->close();
                    ?>
                </tbody>
            </table>
        </section>
    </main>
    
    <script src="../js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>