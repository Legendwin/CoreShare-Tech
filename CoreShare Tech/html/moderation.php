<?php
session_start();
require '../php/db_connect.php';
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { header("Location: index.php"); exit; }
$statsQuery = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected FROM resources";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();
$total = $stats['total'] > 0 ? $stats['total'] : 1; 
$pubPct = round(($stats['published'] / $total) * 100);
$penPct = round(($stats['pending'] / $total) * 100);
$rejPct = round(($stats['rejected'] / $total) * 100);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CoreShare Tech - Moderation</title>
        <link rel="icon" type="image/png" href="../images/Gemini_Generated_Image_69zr6i69zr6i69zr.png" sizes="32x32">
        <link rel="stylesheet" href="../css/styles.css?v=<?php echo time(); ?>">
        <link rel="stylesheet" href="../css/moderation.css?v=<?php echo time(); ?>">
    </head>
    <body>
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <span>CoreShare <strong>Tech</strong></span>
                <button class="sidebar-close-btn" id="sidebar-close">×</button>
            </div>
            <nav class="nav" style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
                <a href="index.php" class="nav-link">Dashboard</a>
                <a href="search.php" class="nav-link">Search</a>
                <a href="resource.php" class="nav-link">Resource</a>
                <a href="contributions.php" class="nav-link">Contributions</a>
                <a href="contact.php" class="nav-link">Contact</a>
                <a href="moderation.php" class="nav-link active">Moderation</a>
                <a href="../php/logout.php" class="nav-link" style="color:#EF4444;">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <button id="menu-toggle" class="mobile-menu-btn">☰</button>
            <header class="dashboard-header">
                <div class="header-title">
                    <h1>Admin Moderation</h1>
                    <p style="color:var(--text-gray); font-size:0.9rem;">Overview of content status and pending approvals.</p>
                </div>
            </header>
            <section class="overview-section">
                <div class="admin-card">
                    <div class="card-header-title">Content Status Overview</div>
                    <div class="chart-row">
                        <div class="pie-chart" style="background: conic-gradient(var(--primary-blue) 0% <?php echo $pubPct; ?>%, #DBEAFE <?php echo $pubPct; ?>% <?php echo $pubPct + $penPct; ?>%, #EF4444 <?php echo $pubPct + $penPct; ?>% 100%);"></div>
                        <ul class="stats-list">
                            <li><span class="dot dot-blue"></span> <?php echo $pubPct; ?>% Published (<?php echo $stats['published']; ?>)</li>
                            <li><span class="dot dot-light"></span> <?php echo $penPct; ?>% Pending (<?php echo $stats['pending']; ?>)</li>
                            <li><span class="dot dot-red"></span> <?php echo $rejPct; ?>% Rejected (<?php echo $stats['rejected']; ?>)</li>
                        </ul>
                    </div>
                </div>
                <div class="admin-card">
                    <div class="card-header-title">Quick Actions</div>
                    <div class="review-box" style="background: white; padding:0;">
                        <p style="color:var(--text-muted);">Review the queue below to update item status.</p>
                    </div>
                    <div style="margin-top: 20px; font-size: 0.9rem; color: var(--text-muted);"><strong><?php echo $stats['pending']; ?></strong> items currently require attention.</div>
                </div>
            </section>
            <section class="table-section" style="margin-top: 0;">
                <div class="section-header">
                    <h3>Pending Queue</h3>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                          <th>Resource Title</th>
                          <th>Subject</th>
                          <th>Type</th>
                          <th>Date</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sql = "SELECT * FROM resources WHERE status='pending' ORDER BY created_at ASC";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo '<tr><td style="font-weight:600;">'.htmlspecialchars($row['title']).'</td><td>'.htmlspecialchars($row['subject']).'</td><td>'.htmlspecialchars($row['type']).'</td><td>'.date("M d, Y", strtotime($row['created_at'])).'</td><td><span class="status-dot" style="background-color:#F59E0B;"></span>Pending</td><td><button class="btn-approve" data-id="'.$row['id'].'" style="background:var(--success); color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; margin-right:5px;">Approve</button><button class="btn-reject" data-id="'.$row['id'].'" style="background:#EF4444; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer;">Reject</button></td></tr>';
                            }
                        } else {
                          echo '<tr><td colspan="6" style="text-align:center; padding: 20px; color: var(--text-muted);">No pending items to review.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </section>
        </main>

        <script src="../js/script.js?v=<?php echo time(); ?>"></script>
    </body>
</html>