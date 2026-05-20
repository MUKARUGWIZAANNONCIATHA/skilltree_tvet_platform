<?php
require_once 'auth.php';
include '../includes/templates/header.php';

$appId = intval($_GET['id'] ?? 0);
if (!$appId) {
    echo '<div class="alert-error">Invalid application ID.</div>';
    include '../includes/templates/footer.php';
    exit;
}

// Fetch application details and ensure it belongs to this company
$stmt = $pdo->prepare("
    SELECT a.*, i.title as internship_title, i.internship_id,
           s.full_name as student_name, s.email as student_email
    FROM internship_applications a
    JOIN internships i ON a.internship_id = i.internship_id
    JOIN users s ON a.student_id = s.user_id
    WHERE a.application_id = ? AND i.company_id = ?
");
$stmt->execute([$appId, $companyId]);
$app = $stmt->fetch();
if (!$app) {
    echo '<div class="alert-error">Application not found or access denied.</div>';
    include '../includes/templates/footer.php';
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decision'])) {
    $decision = $_POST['decision'];
    $feedback = trim($_POST['feedback'] ?? '');
    $newStatus = ($decision === 'accept') ? 'accepted' : 'rejected';
    
    $update = $pdo->prepare("UPDATE internship_applications SET status = ?, reviewed_at = NOW(), feedback = ? WHERE application_id = ?");
    $update->execute([$newStatus, $feedback, $appId]);
    $message = '<div class="alert-success">Application ' . $newStatus . '. The student will be notified.</div>';
    // Refresh the app data
    $app['status'] = $newStatus;
    $app['feedback'] = $feedback;
}
?>

<div class="container" style="max-width:800px; margin:2rem auto;">
    <h1>Application Review</h1>
    <?= $message ?>
    
    <div class="detail-card" style="background:white; border-radius:1rem; padding:1.5rem; margin-bottom:1rem;">
        <p><strong>Student:</strong> <?= htmlspecialchars($app['student_name']) ?> (<?= htmlspecialchars($app['student_email']) ?>)</p>
        <p><strong>Internship:</strong> <?= htmlspecialchars($app['internship_title']) ?></p>
        <p><strong>Applied on:</strong> <?= date('d M Y H:i', strtotime($app['applied_at'])) ?></p>
        
        <div class="cover-letter" style="background:#f8fafc; padding:1rem; border-radius:0.8rem; margin:1rem 0;">
            <strong>Cover Letter:</strong><br>
            <?= nl2br(htmlspecialchars($app['cover_letter'])) ?>
        </div>

        <?php if ($app['status'] !== 'pending'): ?>
            <div class="decision-info" style="background:#eef2fa; padding:1rem; border-radius:0.8rem;">
                <strong>Decision:</strong> <?= ucfirst($app['status']) ?>
                <?php if ($app['feedback']): ?>
                    <br><strong>Feedback:</strong> <?= nl2br(htmlspecialchars($app['feedback'])) ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <form method="post">
                <div class="form-group">
                    <label>Decision *</label>
                    <select name="decision" required class="form-control">
                        <option value="accept">Accept</option>
                        <option value="reject">Reject</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Feedback (optional but recommended)</label>
                    <textarea name="feedback" rows="4" class="form-control" placeholder="Why are you accepting/rejecting this applicant?"></textarea>
                </div>
                <div style="display:flex; gap:1rem; margin-top:1rem;">
                    <button type="submit" class="btn-primary">Submit Decision</button>
                    <a href="applications.php" class="btn-secondary">Back to Applications</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
    .detail-card { background: white; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1rem; }
    .form-group { margin-bottom: 1rem; }
    .form-control { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; }
    .btn-primary { background: #2c7da0; color: white; border: none; padding: 0.5rem 1rem; border-radius: 2rem; cursor: pointer; }
    .btn-secondary { background: #f0f4f8; color: #2c5a74; border: none; padding: 0.5rem 1rem; border-radius: 2rem; text-decoration: none; display: inline-block; }
    .alert-success { background: #e8f5e9; color: #2e7d32; padding: 0.5rem; border-radius: 0.5rem; margin-bottom: 1rem; }
    .alert-error { background: #ffebee; color: #c62828; padding: 0.5rem; border-radius: 0.5rem; margin-bottom: 1rem; }
</style>

<?php include '../includes/templates/footer.php'; ?>