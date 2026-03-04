<?php
session_start();
require_once '../php/db_connect.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : 'guest';

// Include the new subscription tracker (This line does all the hard work!)
require_once '../php/check_plan.php';

// Fetch Real-time Quick Stats
$totalResources = 0; $totalDownloads = 0; $totalContributors = 0;
$statsQuery = "SELECT COUNT(id) as total_res, SUM(downloads) as total_dl, COUNT(DISTINCT uploaded_by) as total_contrib FROM resources WHERE status='published'";
if ($statsResult = $conn->query($statsQuery)) {
    $row = $statsResult->fetch_assoc();
    $totalResources = $row['total_res'] ? $row['total_res'] : 0;
    $totalDownloads = $row['total_dl'] ? $row['total_dl'] : 0;
    $totalContributors = $row['total_contrib'] ? $row['total_contrib'] : 0;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CoreShare Tech — Home</title>
  <link rel="icon" type="image/png" href="../images/Gemini_Generated_Image_69zr6i69zr6i69zr.png" sizes="32x32">
  <link rel="stylesheet" href="../css/styles.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../css/home.css?v=<?php echo time(); ?>">
  <script>const USER_IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;</script>
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
      <a href="./index.php" class="nav-link active">Home</a>
      <a href="./dashboard.php" class="nav-link">Dashboard</a>
      <a href="./search.php" class="nav-link">Search</a>
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
              <p style="font-size:0.8rem; color:var(--text-muted); margin:5px 0;">Write better essays.</p>
              <a href="#" style="font-size:0.8rem; color:var(--primary-blue); font-weight:700; text-decoration:none;">Learn More</a>
          </div>
      </div>
      <?php endif; ?>
    </nav>
  </aside>

  <button id="menu-toggle" class="mobile-menu-btn"><span id="toggle-icon">☰</span></button>

  <main class="main-content">
    <section class="hero">
      <div class="hero-content">
        <h1 class="hero-title">Share and discover technical resources quickly</h1>
        <p class="hero-desc">CoreShare Tech is a lightweight resource hub for developers and contributors — upload, search, and collaborate on learning materials, tools, and project assets.</p>
        <div class="cta-buttons">
          <?php if(!$isLoggedIn): ?>
            <a class="btn btn-primary" href="./login.php">Get Started</a>
          <?php else: ?>
            <a class="btn btn-primary" href="./dashboard.php">Go to Dashboard</a>
          <?php endif; ?>
          <a class="btn btn-ghost" href="./resource.php">Browse Resources</a>
        </div>
      </div>
      <aside class="card hero-stats">
        <h3 class="stats-title">Quick Stats</h3>
        <p class="stats-desc">Community contributions, downloads, and recent uploads at a glance.</p>
        <ul class="stats-list">
          <li><strong><?php echo number_format($totalResources); ?></strong> resources</li>
          <li><strong><?php echo number_format($totalDownloads); ?></strong> downloads</li>
          <li><strong><?php echo number_format($totalContributors); ?></strong> contributors</li>
        </ul>
      </aside>
    </section>

    <section class="features-section">
      <h2 class="section-title">Everything you need</h2>
      <div class="features">
        <div class="feature card"><div class="feature-icon">📁</div><strong class="feature-title">Upload & Manage</strong><div class="desc feature-desc">Add resources, attach files, and edit metadata in seconds.</div></div>
        <div class="feature card"><div class="feature-icon">🔍</div><strong class="feature-title">Search & Discover</strong><div class="desc feature-desc">Fast filtering and full-text search to find what you need.</div></div>
        <div class="feature card"><div class="feature-icon">🔒</div><strong class="feature-title">Secure Access</strong><div class="desc feature-desc">User roles and simple permissions keep your resources protected.</div></div>
      </div>
    </section>
    
    <section class="pricing-section">
      <div class="pricing-header"><h2 class="section-title">Pricing & Plans</h2><p class="pricing-intro">Select the access tier that fits your academic needs.</p></div>
      
      <div class="pricing-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px;">
        
        <div class="card pricing-card">
          <div class="card-body">
            <strong class="card-plan-title">Free</strong>
            <div class="card-plan-price">GH₵0 <span class="card-price-unit">/mo</span></div>
            <ul class="card-features">
              <li>Up to 5 Resource Uploads</li>
              <li>5 Daily Downloads Limit</li>
              <li>Standard Search</li>
              <li>Ad-Supported Experience</li>
            </ul>
            <div class="card-action"><a class="btn btn-ghost w-100" style="text-align:center; display:block;" href="./billing.php?plan=free">Current Plan</a></div>
          </div>
        </div>

        <div class="card pricing-card" style="border-top: 4px solid #D97706;">
          <div class="card-body">
            <strong class="card-plan-title">Exam Pass</strong>
            <div class="card-plan-price">GH₵15 <span class="card-price-unit">/wk</span></div>
            <ul class="card-features">
              <li>7 Days Unlimited Access</li>
              <li>Unlimited Downloads</li>
              <li>Ad-Free Experience</li>
              <li>Perfect for Finals Week</li>
            </ul>
            <div class="card-action"><a class="btn btn-ghost w-100" style="text-align:center; display:block; border-color:#D97706; color:#D97706;" href="./billing.php?plan=exam_pass">Get Pass</a></div>
          </div>
        </div>

        <div class="card pricing-card">
          <div class="card-body">
            <strong class="card-plan-title">Pro Monthly</strong>
            <div class="card-plan-price">GH₵30 <span class="card-price-unit">/mo</span></div>
            <ul class="card-features">
              <li>Unlimited Uploads & Downloads</li>
              <li>Create Custom Collections</li>
              <li>Advanced Deep Search</li>
              <li>Priority Support</li>
            </ul>
            <div class="card-action"><a class="btn btn-ghost w-100" style="text-align:center; display:block;" href="./billing.php?plan=pro">Upgrade Monthly</a></div>
          </div>
        </div>

        <div class="card pricing-card pro-card">
          <div class="card-body">
            <div class="pro-badge">Best Value</div>
            <strong class="card-plan-title">Semester Plan</strong>
            <div class="card-plan-price">GH₵90 <span class="card-price-unit">/term</span></div>
            <ul class="card-features" style="margin-bottom: 12px;">
              <li><strong>All Pro Features Included</strong></li>
              <li>Full 4-Month Coverage</li>
              <li>Saves GH₵30 total</li>
              <li>Priority Feature Requests</li>
            </ul>
            <div class="card-action"><a class="btn btn-primary w-100" style="text-align:center; display:block;" href="./billing.php?plan=semester">Get Semester Plan</a></div>
          </div>
        </div>

      </div>
    </section>
  </main>
  <footer>© <span id="year"></span> CoreShare Tech — Built for sharing knowledge. • <a href="./contact.php">Contact</a></footer>
  <script src="../js/script.js?v=<?php echo time(); ?>"></script>
  <script>document.getElementById('year').textContent = new Date().getFullYear();</script>
</body>
</html>