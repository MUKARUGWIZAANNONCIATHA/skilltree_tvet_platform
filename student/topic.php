<?php
require_once '../config/database.php';
require_once 'includes/auth.php';

$topicId = intval($_GET['topic_id'] ?? 0);
if (!$topicId) die('Invalid topic.');

$topicStmt = $pdo->prepare("SELECT * FROM topics WHERE topic_id = ?");
$topicStmt->execute([$topicId]);
$topic = $topicStmt->fetch();
if (!$topic) die('Topic not found.');

$resourcesStmt = $pdo->prepare("SELECT * FROM topic_resources WHERE topic_id = ? ORDER BY position_order");
$resourcesStmt->execute([$topicId]);
$resources = $resourcesStmt->fetchAll();

$quizStmt = $pdo->prepare("SELECT * FROM topic_quizzes WHERE topic_id = ? LIMIT 1");
$quizStmt->execute([$topicId]);
$quiz = $quizStmt->fetch();

$progressStmt = $pdo->prepare("SELECT * FROM topic_progress WHERE student_id = ? AND topic_id = ?");
$progressStmt->execute([$studentId, $topicId]);
$prog = $progressStmt->fetch();
$read = $prog['resource_read'] ?? false;
$watched = $prog['video_watched'] ?? false;
$quizPassed = $prog['quiz_passed'] ?? false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $upd = $pdo->prepare("INSERT INTO topic_progress (student_id, topic_id, resource_read) VALUES (?,?,1) ON DUPLICATE KEY UPDATE resource_read=1");
    $upd->execute([$studentId, $topicId]);
    header("Location: topic.php?topic_id=$topicId");
    exit;
}
?>
<?php include 'includes/header.php'; ?>
<h2><?= htmlspecialchars($topic['topic_title']) ?></h2>
<div class="resources-list">
    <?php foreach ($resources as $res): ?>
    <div class="resource-item">
        <i class="fas fa-<?= $res['resource_type'] === 'video' ? 'video' : ($res['resource_type'] === 'note' ? 'file-alt' : 'link') ?>"></i>
        <span><?= htmlspecialchars($res['title']) ?></span>
        <a href="<?= $res['url'] ?: $res['file_path'] ?>" target="_blank" class="btn-link">Open</a>
        <?php if ($res['required_for_quiz'] && !$read && $res['resource_type'] === 'note'): ?>
            <form method="post" style="display:inline;">
                <button type="submit" name="mark_read" class="btn-sm">✅ Mark as read</button>
            </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($quiz): ?>
    <?php if ($quizPassed): ?>
        <div class="alert success">✅ You have passed this quiz.</div>
    <?php else: ?>
        <?php
        $requiredStmt = $pdo->prepare("SELECT COUNT(*) FROM topic_resources WHERE topic_id = ? AND required_for_quiz=1");
        $requiredStmt->execute([$topicId]);
        $totalRequired = $requiredStmt->fetchColumn();
        $completed = ($read ? 1 : 0) + ($watched ? 1 : 0);
        $percent = $totalRequired ? ($completed / $totalRequired) * 100 : 0;
        if ($percent >= 90):
        ?>
            <button id="startQuizBtn" class="btn-primary">📝 Take Quiz (anti‑cheat)</button>
        <?php else: ?>
            <div class="alert warning">⚠️ You must study at least 90% of the required resources before taking the quiz.</div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<script>
document.getElementById('startQuizBtn')?.addEventListener('click', () => {
    window.open('quiz.php?topic_id=<?= $topicId ?>', '_blank', 'width=700,height=500');
});
</script>
<?php include 'includes/footer.php'; ?>