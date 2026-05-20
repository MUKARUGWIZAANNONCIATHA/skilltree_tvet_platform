<?php
require_once 'auth.php';
include '../includes/templates/header.php';

$internshipFilter = isset($_GET['internship_id']) ? intval($_GET['internship_id']) : 0;

$sql = "
    SELECT a.*, i.title as internship_title, i.internship_id,
           s.full_name as student_name, s.email as student_email
    FROM internship_applications a
    JOIN internships i ON a.internship_id = i.internship_id
    JOIN users s ON a.student_id = s.user_id
    WHERE i.company_id = ?
";
$params = [$companyId];
if ($internshipFilter) {
    $sql .= " AND a.internship_id = ?";
    $params[] = $internshipFilter;
}
$sql .= " ORDER BY a.applied_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT internship_id, title FROM internships WHERE company_id = ?");
$stmt->execute([$companyId]);
$internshipsList = $stmt->fetchAll();
?>
<div class="container" style="max-width:1000px; margin:2rem auto;">
    <h1>Applications</h1>
    <form method="get" style="margin-bottom:1rem;">
        <label>Filter by internship: </label>
        <select name="internship_id" onchange="this.form.submit()">
            <option value="">-- All --</option>
            <?php foreach ($internshipsList as $opt): ?>
            <option value="<?= $opt['internship_id'] ?>" <?= $internshipFilter == $opt['internship_id'] ? 'selected' : '' ?>><?= htmlspecialchars($opt['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php if (empty($applications)): ?>
        <p>No applications found.</p>
    <?php else: ?>
        <?php foreach ($applications as $app): ?>
        <div class="application-item" style="background:white; border-radius:1rem; padding:1rem; margin-bottom:1rem; border-left:4px solid #2c7da0;">
            <div><strong><?= htmlspecialchars($app['student_name']) ?></strong> (<?= htmlspecialchars($app['student_email']) ?>)</div>
            <div><small>Applied for: <?= htmlspecialchars($app['internship_title']) ?> on <?= date('d M Y', strtotime($app['applied_at'])) ?></small></div>
            <div>Status: <strong><?= ucfirst($app['status']) ?></strong></div>
            <div class="cover-letter" style="margin-top:0.5rem; background:#f8fafc; padding:0.5rem; border-radius:0.5rem;">
                <strong>Cover Letter:</strong><br><?= nl2br(htmlspecialchars($app['cover_letter'])) ?>
            </div>
            <div style="margin-top:0.5rem;">
                <a href="application-detail.php?id=<?= $app['application_id'] ?>" class="btn-sm">Review & Decide</a>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php include '../includes/templates/footer.php';?>