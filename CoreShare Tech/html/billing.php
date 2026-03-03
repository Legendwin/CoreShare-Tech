<?php
session_start();
require '../php/db_connect.php';
require_once '../php/check_plan.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : 'guest';
$currentPlan = $userPlan; 
$allowed = ['free', 'pro', 'semester', 'exam_pass'];

// Fetch User Email for Paystack
$userEmail = 'student@example.com'; // Fallback
if ($isLoggedIn) {
    $emailStmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $emailStmt->bind_param('i', $_SESSION['user_id']);
    $emailStmt->execute();
    $emailStmt->bind_result($userEmail);
    $emailStmt->fetch();
    $emailStmt->close();
}

$planMeta = [
    'pro' => ['name' => 'Pro Monthly', 'cost' => '30.00', 'sub' => 'Monthly Subscription', 'desc' => 'Unlimited uploads, downloads, and custom collections for 30 days.'],
    'semester' => ['name' => 'Semester Plan', 'cost' => '99.00', 'sub' => '4-Month Subscription', 'desc' => 'All Pro features completely unlocked for an entire academic term.'],
    'exam_pass' => ['name' => 'Exam Season Pass', 'cost' => '20.00', 'sub' => '7-Day Unlimited Access', 'desc' => 'Download everything you need ad-free for 1 week. Perfect for cramming.']
];

// Handle downgrades to free plan normally
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_plan']) && $_POST['selected_plan'] === 'free') {
    if (!$isLoggedIn) { header('Location: ./login.php?next=./billing.php'); exit; }
    $stmt = $conn->prepare("UPDATE users SET plan = 'free', plan_expires = NULL WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    if ($stmt->execute()) {
        $_SESSION['plan'] = 'free'; 
        header('Location: ./dashboard.php?success=plan_changed');
    }
    $stmt->close();
    exit;
}

$showCheckout = false;
$checkoutPlan = '';
if (isset($_GET['plan']) && in_array($_GET['plan'], $allowed)) {
    if (!$isLoggedIn) { header('Location: ./login.php?next=./billing.php'); exit; }
    if ($_GET['plan'] !== $currentPlan) {
        $showCheckout = true;
        $checkoutPlan = $_GET['plan'];
    } else {
        header('Location: ./billing.php'); exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Billing & Checkout — CoreShare Tech</title>
  <link rel="icon" type="image/png" href="../images/Gemini_Generated_Image_69zr6i69zr6i69zr.png" sizes="32x32">
  <link rel="stylesheet" href="../css/styles.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../css/home.css?v=<?php echo time(); ?>">
  <style>
      .checkout-container { max-width: 900px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
      @media (max-width: 768px) { .checkout-container { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="sidebar-overlay" id="sidebar-overlay"></div>
  <aside class="sidebar" id="sidebar" style="display:flex; flex-direction:column;">
    <div class="brand"><span>CoreShare <strong>Tech</strong></span><button id="theme-toggle" class="theme-toggle-btn">🌙</button></div>
    <nav class="nav" style="margin-top:20px; display:flex; flex-direction:column; gap:10px; flex-grow:1;">
      <a href="./index.php" class="nav-link">Home</a>
      <a href="./dashboard.php" class="nav-link">Dashboard</a>
      <a href="./search.php" class="nav-link">Search</a>
      <a href="./resource.php" class="nav-link">Resource</a>
      <a href="./contributions.php" class="nav-link">Contributions</a>
      <?php if($isLoggedIn): ?><a href="../php/logout.php" class="nav-link" style="color:#EF4444; font-weight:700;">Logout</a><?php else: ?><a href="./login.php" class="nav-link" style="color:var(--primary-blue); font-weight:700;">Login</a><?php endif; ?>
    </nav>
  </aside>

  <button id="menu-toggle" class="mobile-menu-btn"><span id="toggle-icon">☰</span></button>

  <main class="main-content">
    <?php if ($showCheckout): ?>
        <?php if (in_array($checkoutPlan, ['pro', 'semester', 'exam_pass'])): ?>
            <header class="dashboard-header" style="margin-bottom: 24px;">
                <div class="header-title">
                    <h1>Complete Your Upgrade</h1>
                    <p style="color:var(--text-muted); font-size:0.95rem;">Pay securely via Mobile Money or Card.</p>
                </div>
            </header>
            <div class="checkout-container">
                <div class="card" style="border: 1px solid var(--border-subtle); align-self: start;">
                    <div class="card-body">
                        <h3 style="margin-bottom: 20px; padding-bottom:10px; border-bottom:1px solid var(--border-subtle);">Order Summary</h3>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                            <div><strong style="font-size:1.2rem; color:var(--text-main);"><?php echo $planMeta[$checkoutPlan]['name']; ?></strong><div style="color:var(--text-muted); font-size:0.9rem;"><?php echo $planMeta[$checkoutPlan]['sub']; ?></div></div>
                            <div style="font-size:1.5rem; font-weight:800; color:var(--text-main);">GH₵<?php echo $planMeta[$checkoutPlan]['cost']; ?></div>
                        </div>
                        <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:20px; line-height:1.6;"><?php echo $planMeta[$checkoutPlan]['desc']; ?></p>
                        <ul style="color:var(--text-muted); padding-left:18px; line-height:1.8; margin-bottom:24px; font-size:0.95rem;">
                            <li><strong style="color:var(--text-main)">Zero</strong> Daily Download Limits</li>
                            <li><strong style="color:var(--text-main)">Zero</strong> Waiting & Popups</li>
                            <li>Ad-Free Guarantee</li>
                        </ul>
                    </div>
                </div>
                <div class="card" style="border: 2px solid var(--primary-blue); box-shadow: 0 10px 30px rgba(59, 130, 246, 0.1); display:flex; flex-direction:column; justify-content:center; text-align:center;">
                    <div class="card-body">
                        <img src="https://paystack.com/assets/payment/img/paystack-badge-cards-momo.png" alt="Paystack Supported Methods" style="max-width: 100%; height: auto; margin-bottom:20px;">
                        <h3 style="margin-bottom: 10px;">Secure Payment</h3>
                        <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:20px;">Click the button below to complete your payment using MTN MoMo, Vodafone Cash, Telecel, AT, or Bank Card.</p>
                        
                        <?php if(isset($_GET['error'])): ?><div class="alert-error"><strong>Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?></div><?php endif; ?>
                        
                        <button type="button" onclick="payWithPaystack()" class="btn-card" style="padding:16px; font-size:1.1rem; background:var(--grad-primary); border:none; color:white; width:100%; cursor:pointer;">Pay GH₵<?php echo $planMeta[$checkoutPlan]['cost']; ?></button>
                        <a href="billing.php" style="display:block; text-align:center; margin-top:16px; color:var(--text-muted); text-decoration:none; font-weight:500;">Cancel</a>
                    </div>
                </div>
            </div>

            <script src="https://js.paystack.co/v1/inline.js"></script>
            <script>
            function payWithPaystack() {
                let handler = PaystackPop.setup({
                    // === REPLACE THIS WITH YOUR PAYSTACK TEST PUBLIC KEY ===
                    key: 'pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 
                    // =======================================================
                    email: '<?php echo $userEmail; ?>',
                    amount: <?php echo floatval($planMeta[$checkoutPlan]['cost']) * 100; ?>, // Paystack requires amount in pesewas
                    currency: 'GHS',
                    ref: 'CST_' + Math.floor((Math.random() * 1000000000) + 1), // Generate random reference
                    
                    callback: function(response) {
                        // If payment is successful, redirect to our verification script
                        window.location.href = "../php/verify_payment.php?reference=" + response.reference + "&plan=<?php echo $checkoutPlan; ?>";
                    },
                    onClose: function() {
                        alert('Payment window closed. Your transaction was not completed.');
                    }
                });
                handler.openIframe();
            }
            </script>

        <?php elseif ($checkoutPlan === 'free'): ?>
            <header class="dashboard-header" style="margin-bottom: 24px; justify-content:center; text-align:center;"><div class="header-title" style="width:100%;"><h1>Cancel Subscription</h1></div></header>
            <div class="card" style="max-width: 500px; margin: 0 auto; text-align:center; border:1px solid var(--border-subtle);">
                <div class="card-body" style="padding:40px 20px;">
                    <div style="font-size:3rem; margin-bottom:10px;">⚠️</div>
                    <h2 style="margin-bottom: 10px;">Switch to Free Plan?</h2>
                    <p style="color:var(--text-muted); margin-bottom: 30px; font-size:1.05rem;">By downgrading, you will lose access to <strong>Unlimited Downloads</strong>, and your account will immediately become ad-supported.</p>
                    <form method="POST" action="billing.php">
                        <input type="hidden" name="selected_plan" value="free"><input type="hidden" name="confirm_plan" value="1">
                        <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                            <a href="billing.php" class="btn-card" style="background:var(--primary-blue); color:white; text-decoration:none; flex:1; min-width:180px;">Keep Current Plan</a>
                            <button type="submit" class="btn-card" style="background:transparent; color:var(--danger); border:1px solid var(--danger); flex:1; min-width:180px; box-shadow:none;">Confirm Downgrade</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <header class="dashboard-header">
            <div class="header-title"><h1>Billing & Plans</h1><p style="color:var(--text-muted); font-size:0.95rem; margin-top:4px;">Manage your subscription and upgrade your account.</p></div>
        </header>
        <div class="pricing-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px; max-width:1100px; margin: 0 auto;">
            <div class="card plan-card" style="border: 1px solid var(--border-subtle);">
                <div class="card-body" style="display:flex; flex-direction:column; height:100%;">
                <div>
                    <strong style="font-size:1.25rem; color:var(--text-main);">Free</strong>
                    <div class="plan-price" style="margin-top:12px; margin-bottom: 20px;"><span style="font-size:2.5rem; font-weight:800; color:var(--text-main); line-height:1;">GH₵0</span><span style="font-size:1rem; color:var(--text-muted);">/mo</span></div>
                    <ul style="color:var(--text-muted); padding-left:18px; line-height:1.8; margin-bottom:24px;"><li>Up to 5 Resource Uploads</li><li>5 Daily Downloads Limit</li><li>Ad-Supported Experience</li></ul>
                </div>
                <div style="margin-top:auto;"><?php if ($isLoggedIn && $currentPlan === 'free'): ?><button class="btn-card disabled" style="display:block; width:100%; text-align:center;">Current Plan</button><?php else: ?><a class="btn-card" href="?plan=free" style="display:block; text-align:center; background:transparent; color:var(--primary-blue); border:1px solid var(--primary-blue); box-shadow:none;">Select Free</a><?php endif; ?></div>
                </div>
            </div>
            
            <div class="card plan-card" style="border: 2px solid #D97706;">
                <div class="card-body" style="display:flex; flex-direction:column; height:100%;">
                <div>
                    <strong style="font-size:1.25rem; color:var(--text-main);">Exam Pass</strong>
                    <div class="plan-price" style="margin-top:12px; margin-bottom: 20px;"><span style="font-size:2.5rem; font-weight:800; color:var(--text-main); line-height:1;">GH₵20</span><span style="font-size:1rem; color:var(--text-muted);">/wk</span></div>
                    <ul style="color:var(--text-muted); padding-left:18px; line-height:1.8; margin-bottom:24px;"><li>7 Days Unlimited Access</li><li>Unlimited Downloads</li><li>Ad-Free Experience</li></ul>
                </div>
                <div style="margin-top:auto;"><?php if ($isLoggedIn && $currentPlan === 'exam_pass'): ?><button class="btn-card disabled" style="display:block; width:100%; text-align:center;">Current Plan</button><?php else: ?><a class="btn-card" style="display:block; text-align:center; background:transparent; color:#D97706; border:1px solid #D97706;" href="?plan=exam_pass">Select Pass</a><?php endif; ?></div>
                </div>
            </div>

            <div class="card plan-card" style="border: 1px solid var(--border-subtle);">
                <div class="card-body" style="display:flex; flex-direction:column; height:100%;">
                <div>
                    <strong style="font-size:1.25rem; color:var(--text-main);">Pro Monthly</strong>
                    <div class="plan-price" style="margin-top:12px; margin-bottom: 20px;"><span style="font-size:2.5rem; font-weight:800; color:var(--text-main); line-height:1;">GH₵30</span><span style="font-size:1rem; color:var(--text-muted);">/mo</span></div>
                    <ul style="color:var(--text-muted); padding-left:18px; line-height:1.8; margin-bottom:24px;"><li>Unlimited Downloads</li><li>Custom Study Collections</li><li>Ad-Free & Priority Support</li></ul>
                </div>
                <div style="margin-top:auto;"><?php if ($isLoggedIn && $currentPlan === 'pro'): ?><button class="btn-card disabled" style="display:block; width:100%; text-align:center;">Current Plan</button><?php else: ?><a class="btn-card" style="display:block; text-align:center; background:transparent; color:var(--primary-blue); border:1px solid var(--primary-blue); box-shadow:none;" href="?plan=pro">Upgrade Pro</a><?php endif; ?></div>
                </div>
            </div>

            <div class="card plan-card" style="border: 2px solid var(--primary-blue); position:relative; box-shadow: 0 10px 30px rgba(59, 130, 246, 0.1);">
                <div class="card-body" style="display:flex; flex-direction:column; height:100%;">
                <div style="position:absolute; top:-6px; right:20px; background:var(--primary-blue); color:white; padding:4px 12px; border-radius:6px; font-size:0.75rem; font-weight:800; letter-spacing:1px;">BEST VALUE</div>
                <div>
                    <strong style="font-size:1.25rem; color:var(--text-main);">Semester Plan</strong>
                    <div class="plan-price" style="margin-top:12px; margin-bottom: 20px;"><span style="font-size:2.5rem; font-weight:800; color:var(--text-main); line-height:1;">GH₵99</span><span style="font-size:1rem; color:var(--text-muted);">/term</span></div>
                    <ul style="color:var(--text-muted); padding-left:18px; line-height:1.8; margin-bottom:24px;"><li><strong>All Pro Features</strong></li><li>Full 4-Month Coverage</li><li>Saves GH₵21 Total</li></ul>
                </div>
                <div style="margin-top:auto;"><?php if ($isLoggedIn && $currentPlan === 'semester'): ?><button class="btn-card disabled" style="display:block; width:100%; text-align:center;">Current Plan</button><?php else: ?><a class="btn-card" style="display:block; text-align:center;" href="?plan=semester">Get Semester Plan</a><?php endif; ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
  </main>
  <script src="../js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>