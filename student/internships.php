<?php
require_once '../config/database.php';
require_once 'includes/auth.php';

// Get open internships (assuming companies table exists)
$sql = "SELECT i.*, c.company_name 
        FROM internships i
        LEFT JOIN companies c ON i.company_id = c.company_id
        WHERE i.is_open = 1 
        AND i.status != 'closed'
        AND (i.application_deadline >= CURDATE() OR i.application_deadline IS NULL)
        ORDER BY i.application_deadline ASC";
$internships = $pdo->query($sql)->fetchAll();

// Fallback if no companies table
if (!$internships && $pdo->query("SHOW TABLES LIKE 'companies'")->rowCount() == 0) {
    $sql = "SELECT * FROM internships 
            WHERE is_open = 1 
            AND status != 'closed'
            AND (application_deadline >= CURDATE() OR application_deadline IS NULL)
            ORDER BY application_deadline ASC";
    $internships = $pdo->query($sql)->fetchAll();
    foreach ($internships as &$intern) {
        $intern['company_name'] = "Company ID: " . $intern['company_id'];
    }
}

// Handle application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    $internId = intval($_POST['internship_id']);
    $coverLetter = trim($_POST['cover_letter']);
    if ($internId && $coverLetter) {
        $insert = $pdo->prepare("INSERT INTO internship_applications (internship_id, student_id, cover_letter) VALUES (?, ?, ?)");
        $insert->execute([$internId, $studentId, $coverLetter]);
        $success = "✅ Application submitted successfully!";
    }
}

// Get student's previous applications, join with companies
$stmt = $pdo->prepare("
    SELECT a.*, i.title as role_title, c.company_name
    FROM internship_applications a
    JOIN internships i ON a.internship_id = i.internship_id
    LEFT JOIN companies c ON i.company_id = c.company_id
    WHERE a.student_id = ?
    ORDER BY a.applied_at DESC
");
$stmt->execute([$studentId]);
$applications = $stmt->fetchAll();

// Fallback if no companies table
if (empty($applications) && $pdo->query("SHOW TABLES LIKE 'companies'")->rowCount() == 0) {
    $stmt = $pdo->prepare("
        SELECT a.*, i.title as role_title, i.company_id
        FROM internship_applications a
        JOIN internships i ON a.internship_id = i.internship_id
        WHERE a.student_id = ?
        ORDER BY a.applied_at DESC
    ");
    $stmt->execute([$studentId]);
    $applications = $stmt->fetchAll();
    foreach ($applications as &$app) {
        $app['company_name'] = "Company ID: " . $app['company_id'];
    }
}
?>
<?php include 'includes/header.php'; ?>

<style>
    .internships-header {
        background: linear-gradient(135deg, #1a5f7a, #0e3a4a);
        border-radius: 1.5rem;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
    }
    .stats-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    .stat-card {
        background: white;
        border-radius: 1rem;
        padding: 1rem 1.5rem;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        flex: 1;
    }
    /* --- Improved intern card --- */
    .intern-card {
        background: white;
        border-radius: 1.2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        overflow: hidden;
        transition: 0.2s;
    }
    .intern-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    }
    .intern-header {
        background: #f8fafc;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #eef2f8;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .intern-header h3 {
        margin: 0;
        font-size: 1.2rem;
        color: #1a5f7a;
    }
    .company-name {
        font-size: 0.9rem;
        color: #2c7da0;
        font-weight: 500;
    }
    .deadline-badge {
        background: #fff3e0;
        padding: 0.2rem 0.8rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #c76f1c;
    }
    .intern-details {
        padding: 1rem 1.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        border-bottom: 1px solid #eef2f8;
        background: white;
    }
    .detail-item {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.85rem;
        color: #4a6a82;
        background: #f8fafc;
        padding: 0.3rem 0.8rem;
        border-radius: 2rem;
    }
    .detail-item i {
        width: 1rem;
        color: #2c7da0;
    }
    .intern-description {
        padding: 1rem 1.5rem;
        color: #2c5a74;
        line-height: 1.5;
        border-bottom: 1px solid #eef2f8;
    }
    .requirements-section {
        padding: 1rem 1.5rem;
        background: #fef9e6;
        border-bottom: 1px solid #eef2f8;
    }
    .requirements-section strong {
        color: #c76f1c;
    }
    .apply-section {
        padding: 1rem 1.5rem;
        background: #f8fafc;
    }
    summary {
        cursor: pointer;
        color: #2c7da0;
        font-weight: 600;
        display: inline-block;
        padding: 0.3rem 0;
    }
    summary:hover {
        color: #1a5f7a;
    }
    .apply-form {
        margin-top: 1rem;
    }
    textarea {
        width: 100%;
        padding: 0.6rem;
        border-radius: 0.8rem;
        border: 1px solid #cbd5e1;
        font-family: inherit;
        margin-top: 0.5rem;
        resize: vertical;
    }
    .btn-primary {
        background: #2c7da0;
        border: none;
        border-radius: 2rem;
        padding: 0.5rem 1.2rem;
        color: white;
        cursor: pointer;
        margin-top: 0.5rem;
        transition: 0.2s;
    }
    .btn-primary:hover {
        background: #1e5f7a;
        transform: scale(1.02);
    }
    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 0.8rem;
        border-radius: 0.8rem;
        margin-bottom: 1rem;
    }
    .empty-state {
        text-align: center;
        padding: 2rem;
        background: #f8fafc;
        border-radius: 1rem;
        color: #8aaec0;
    }
    .application-item {
        background: #f8fafc;
        padding: 0.8rem 1rem;
        border-radius: 0.8rem;
        margin-bottom: 0.5rem;
        border-left: 3px solid #2c7da0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .app-status {
        font-weight: 600;
        font-size: 0.75rem;
        padding: 0.2rem 0.6rem;
        border-radius: 2rem;
    }
    .app-status.pending { background: #fff3e0; color: #c76f1c; }
    .app-status.accepted { background: #e8f5e9; color: #2e7d32; }
    .app-status.rejected { background: #ffebee; color: #c62828; }
</style>

<div class="internships-header">
    <h1><i class="fas fa-briefcase"></i> Internship Opportunities</h1>
    <p>Find real‑world experience and kickstart your career.</p>
</div>

<?php if (isset($success)): ?>
    <div class="alert-success"><?= $success ?></div>
<?php endif; ?>

<div class="stats-row">
    <div class="stat-card">📢 <strong><?= count($internships) ?></strong> open positions</div>
    <div class="stat-card">📝 <strong><?= count($applications) ?></strong> applications sent</div>
</div>

<h2>📌 Open Internships</h2>
<?php if (empty($internships)): ?>
    <div class="empty-state">No internships available at the moment. Check back later.</div>
<?php else: ?>
    <?php foreach ($internships as $intern): ?>
    <div class="intern-card">
        <div class="intern-header">
            <div>
                <h3><?= htmlspecialchars($intern['title']) ?></h3>
                <div class="company-name">
                    <i class="fas fa-building"></i> <?= htmlspecialchars($intern['company_name']) ?>
                </div>
            </div>
            <div class="deadline-badge">
                <i class="fas fa-calendar-alt"></i> Deadline: <?= $intern['application_deadline'] ? date('d M Y', strtotime($intern['application_deadline'])) : 'Rolling' ?>
            </div>
        </div>

        <div class="intern-details">
            <?php if (!empty($intern['location'])): ?>
                <span class="detail-item"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($intern['location']) ?></span>
            <?php endif; ?>
            <?php if (!empty($intern['duration_months'])): ?>
                <span class="detail-item"><i class="fas fa-clock"></i> <?= $intern['duration_months'] ?> months</span>
            <?php endif; ?>
            <span class="detail-item"><i class="fas fa-tag"></i> Open position</span>
        </div>

        <div class="intern-description">
            <?= nl2br(htmlspecialchars($intern['description'])) ?>
        </div>

        <?php if (!empty($intern['required_modules'])): ?>
            <div class="requirements-section">
                <strong><i class="fas fa-graduation-cap"></i> Preferred modules / skills:</strong>
                <div style="margin-top: 0.3rem;"><?= nl2br(htmlspecialchars($intern['required_modules'])) ?></div>
            </div>
        <?php endif; ?>

        <div class="apply-section">
            <details>
                <summary><i class="fas fa-paper-plane"></i> Apply for this internship</summary>
                <form method="post" class="apply-form">
                    <input type="hidden" name="internship_id" value="<?= $intern['internship_id'] ?>">
                    <textarea name="cover_letter" rows="4" placeholder="Why are you a good fit? Write your cover letter / motivation..." required></textarea>
                    <button type="submit" name="apply" class="btn-primary">Send Application</button>
                </form>
            </details>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<h2 style="margin-top: 2rem;">📬 My Applications</h2>
<?php if (empty($applications)): ?>
    <div class="empty-state">You haven't applied to any internships yet.</div>
<?php else: ?>
    <?php foreach ($applications as $app): ?>
    <div class="application-item">
        <div>
            <strong><?= htmlspecialchars($app['company_name']) ?></strong> – <?= htmlspecialchars($app['role_title']) ?><br>
            <small>Applied on: <?= date('d M Y', strtotime($app['applied_at'])) ?></small>
        </div>
        <div>
            <span class="app-status <?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php include_once '../includes/templates/footer.php'; ?>