<?php
require_once 'auth.php';
include '../includes/templates/header.php';   // Fixed path

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM internships WHERE company_id = ?");
$stmt->execute([$companyId]);
$totalInternships = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as active FROM internships WHERE company_id = ? AND is_open = 1 AND (application_deadline >= CURDATE() OR application_deadline IS NULL)");
$stmt->execute([$companyId]);
$activeInternships = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_apps 
    FROM internship_applications a
    JOIN internships i ON a.internship_id = i.internship_id
    WHERE i.company_id = ?
");
$stmt->execute([$companyId]);
$totalApplications = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT a.*, i.title, i.internship_id, s.full_name as student_name, s.email as student_email
    FROM internship_applications a
    JOIN internships i ON a.internship_id = i.internship_id
    JOIN users s ON a.student_id = s.user_id
    WHERE i.company_id = ? AND a.status = 'pending'
    ORDER BY a.applied_at DESC
    LIMIT 5
");
$stmt->execute([$companyId]);
$recentApps = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM internships WHERE company_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$companyId]);
$recentInternships = $stmt->fetchAll();
?>

<div class="dashboard-container" style="max-width:1200px; margin:2rem auto; padding:0 1rem;">
    <!-- Welcome Section -->
    <div class="welcome-card" style="background:linear-gradient(135deg,#1a5f7a,#0e3a4a); border-radius:1.5rem; padding:2rem; color:white; margin-bottom:2rem;">
        <h1 style="font-size:1.8rem; margin-bottom:0.5rem;">👋 Welcome, <?= htmlspecialchars($companyName) ?>!</h1>
        <p>Manage your internships, review applications, and connect with talented students.</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:1.5rem; margin-bottom:2rem;">
        <div class="stat-card" style="background:white; border-radius:1.2rem; padding:1.5rem; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
            <div style="font-size:2.5rem; font-weight:700; color:#1a5f7a;"><?= $totalInternships ?></div>
            <div style="color:#6c8faa;">Total Internships</div>
        </div>
        <div class="stat-card" style="background:white; border-radius:1.2rem; padding:1.5rem; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
            <div style="font-size:2.5rem; font-weight:700; color:#2c7da0;"><?= $activeInternships ?></div>
            <div style="color:#6c8faa;">Active Offers</div>
        </div>
        <div class="stat-card" style="background:white; border-radius:1.2rem; padding:1.5rem; text-align:center; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
            <div style="font-size:2.5rem; font-weight:700; color:#f7b32b;"><?= $totalApplications ?></div>
            <div style="color:#6c8faa;">Applications Received</div>
        </div>
    </div>

    <!-- Recent Internships -->
    <div class="section-card" style="background:white; border-radius:1.2rem; padding:1.5rem; margin-bottom:2rem; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h3 style="margin:0;">📌 Recent Internships</h3>
            <a href="post-internship.php" style="background:#2c7da0; color:white; padding:0.4rem 1rem; border-radius:2rem; text-decoration:none;">+ Post New</a>
        </div>
        <?php if (empty($recentInternships)): ?>
            <p style="color:#8aaec0;">No internships posted yet.</p>
        <?php else: ?>
            <?php foreach ($recentInternships as $intern): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.8rem 0; border-bottom:1px solid #eef2f8;">
                <div><strong><?= htmlspecialchars($intern['title']) ?></strong><br><small>Deadline: <?= $intern['application_deadline'] ?: 'Rolling' ?></small></div>
                <a href="internships.php?edit=<?= $intern['internship_id'] ?>" style="background:#2196F3; color:white; padding:0.2rem 0.8rem; border-radius:2rem; font-size:0.75rem; text-decoration:none;">Edit</a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div style="margin-top:1rem; text-align:right;"><a href="internships.php" style="color:#2c7da0;">View all →</a></div>
    </div>

    <!-- Recent Applications -->
    <div class="section-card" style="background:white; border-radius:1.2rem; padding:1.5rem; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom:1rem;">📬 Recent Applications (Pending)</h3>
        <?php if (empty($recentApps)): ?>
            <p style="color:#8aaec0;">No pending applications.</p>
        <?php else: ?>
            <?php foreach ($recentApps as $app): ?>
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.8rem 0; border-bottom:1px solid #eef2f8;">
                <div><strong><?= htmlspecialchars($app['student_name']) ?></strong> applied for <strong><?= htmlspecialchars($app['title']) ?></strong><br><small><?= date('d M Y', strtotime($app['applied_at'])) ?></small></div>
                <a href="application-detail.php?id=<?= $app['application_id'] ?>" style="background:#f7b32b; color:#1e2f3e; padding:0.2rem 0.8rem; border-radius:2rem; font-size:0.75rem; text-decoration:none;">Review</a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div style="margin-top:1rem; text-align:right;"><a href="applications.php" style="color:#2c7da0;">View all →</a></div>
    </div>
</div>

<?php include '../includes/templates/footer.php'; ?>