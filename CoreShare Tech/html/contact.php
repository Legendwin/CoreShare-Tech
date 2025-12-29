<?php
session_start();
require '../php/db_connect.php';
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['user_role'] : 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoreShare Tech - Contact Us</title>
    <link rel="icon" type="image/png" href="../images/Gemini_Generated_Image_69zr6i69zr6i69zr.png" sizes="32x32">
    <link rel="stylesheet" href="../css/styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/contact.css?v=<?php echo time(); ?>">
    <script>const USER_IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;</script>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="brand"><span>CoreShare <strong>Tech</strong></span><button class="sidebar-close-btn" onclick="document.getElementById('sidebar').classList.remove('open')">×</button></div>
        <nav class="nav" style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="search.php" class="nav-link">Search</a>
            <a href="resource.php" class="nav-link">Resource</a>
            <a href="contributions.php" class="nav-link">Contributions</a>
            <a href="contact.php" class="nav-link active">Contact</a>
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
            <div class="header-title">
                <h1>Contact Support</h1>
                <p style="color:var(--text-muted); font-size:0.9rem;">Have questions or suggestions? We'd love to hear from you.</p>
            </div>
        </header>

        <section class="contact-info-row">
            <div class="info-card">
                <span class="info-icon">📧</span>
                <div class="info-title">Email Us</div>
                <div class="info-text">support@coresharetech.com</div>
            </div>
            <div class="info-card">
                <span class="info-icon">📍</span>
                <div class="info-title">Visit Us</div>
                <div class="info-text">GCTU Main Campus, Tesano - Accra</div>
            </div>
            <div class="info-card">
                <span class="info-icon">📞</span>
                <div class="info-title">Call Us</div>
                <div class="info-text">+233 55 123 4567</div>
            </div>
        </section>

        <section class="contact-container">
            <div class="contact-header">
                <h2>Send us a Message</h2>
                <p>Fill out the form below and our team will get back to you shortly.</p>
            </div>

            <form id="contact-form">
                <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>">
                
                <div class="form-row-split" style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Your Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Alex Student" required 
                               value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="alex@university.edu" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject" class="form-control">
                        <option value="General Inquiry">General Inquiry</option>
                        <option value="Report an Issue">Report an Issue / Bug</option>
                        <option value="Feature Request">Feature Request</option>
                        <option value="Content Moderation">Content Moderation</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" class="form-control" placeholder="How can we help you today?" required></textarea>
                </div>

                <button type="submit" class="btn-send-message">Send Message</button>
            </form>
        </section>
    </main>

    <div class="toast-container"></div>

    <script src="../js/script.js?v=<?php echo time(); ?>"></script>
    <script>
        // Specific script for Contact Form submission
        document.getElementById('contact-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            const originalText = btn.innerText;
            btn.innerText = 'Sending...';
            btn.disabled = true;

            const formData = new FormData(this);

            fetch('../php/submit_contact.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message, 'success');
                    this.reset();
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                btn.innerText = originalText;
                btn.disabled = false;
            });
        });
    </script>
</body>
</html>