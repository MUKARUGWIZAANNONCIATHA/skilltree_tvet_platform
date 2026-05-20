<?php
/**
 * AJAX: Load subtopic content (notes, videos, links) with marking buttons
 * Path: /student/ajax/subtopic-content.php
 */
require_once '../../config/database.php';
require_once '../includes/auth.php';

$subtopicId = intval($_GET['subtopic_id'] ?? 0);
if (!$subtopicId) {
    echo '<div class="placeholder">Invalid subtopic.</div>';
    exit;
}

// Fetch subtopic and its parent topic
$stmt = $pdo->prepare("
    SELECT s.*, t.topic_id, t.topic_title 
    FROM subtopics s
    JOIN topics t ON s.topic_id = t.topic_id
    WHERE s.subtopic_id = ?
");
$stmt->execute([$subtopicId]);
$subtopic = $stmt->fetch();
if (!$subtopic) {
    echo '<div class="placeholder">Subtopic not found.</div>';
    exit;
}
$topicId = $subtopic['topic_id'];

// Fetch resources for this subtopic
$resStmt = $pdo->prepare("SELECT * FROM subtopic_resources WHERE subtopic_id = ? ORDER BY display_order ASC");
$resStmt->execute([$subtopicId]);
$resources = $resStmt->fetchAll();

// Get current progress for the topic (resource_read, video_watched)
$progStmt = $pdo->prepare("SELECT resource_read, video_watched, quiz_passed FROM topic_progress WHERE student_id = ? AND topic_id = ?");
$progStmt->execute([$studentId, $topicId]);
$progress = $progStmt->fetch();
$read = $progress['resource_read'] ?? false;
$watched = $progress['video_watched'] ?? false;
$quizPassed = $progress['quiz_passed'] ?? false;

?>
<style>
    .subtopic-content h2 { color: #1e5a7a; margin-bottom: 0.5rem; }
    .content-section { margin-bottom: 2rem; }
    .note-content { background: #fef9e6; padding: 1.2rem; border-radius: 1rem; border-left: 4px solid #f7b32b; white-space: pre-wrap; }
    .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; margin: 1rem 0; border-radius: 1rem; }
    .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
    .resource-item { background: #f8fafc; padding: 0.8rem; border-radius: 1rem; margin-bottom: 0.6rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
    .mark-btn { background: #eef2fa; border: none; border-radius: 2rem; padding: 0.2rem 0.8rem; cursor: pointer; font-size: 0.8rem; }
    .mark-btn:hover { background: #dce6f0; }
    .mark-btn.done { background: #4CAF50; color: white; }
</style>
<div class="subtopic-content">
    <h2><?= htmlspecialchars($subtopic['subtopic_title']) ?></h2>
    <p><em>Topic: <?= htmlspecialchars($subtopic['topic_title']) ?></em></p>

    <?php foreach ($resources as $res): ?>
        <?php if ($res['resource_type'] === 'note'): ?>
            <div class="content-section">
                <h3><i class="fas fa-file-alt"></i> Notes: <?= htmlspecialchars($res['title']) ?></h3>
                <div class="note-content">
                    <?php 
                    $content = $res['content'] ?? '';
                    if (!empty($res['file_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $res['file_path'])) {
                        $content = file_get_contents($_SERVER['DOCUMENT_ROOT'] . $res['file_path']);
                    }
                    echo nl2br(htmlspecialchars($content ?: 'No content available.'));
                    ?>
                </div>
                <?php if (!$read): ?>
                    <form method="post" action="ajax/mark-resource.php" class="mark-form" style="margin-top: 0.5rem;">
                        <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                        <input type="hidden" name="resource_type" value="note">
                        <button type="submit" class="mark-btn">✅ Mark as read</button>
                    </form>
                <?php else: ?>
                    <p><i class="fas fa-check-circle" style="color:#4CAF50;"></i> Notes marked as read.</p>
                <?php endif; ?>
            </div>
        <?php elseif ($res['resource_type'] === 'video'): ?>
            <div class="content-section">
                <h3><i class="fas fa-video"></i> Video: <?= htmlspecialchars($res['title']) ?></h3>
                <?php 
                $videoUrl = $res['url'] ?? '';
                if (preg_match('/(youtube\.com\/embed\/|youtu\.be\/|\/v\/|watch\?v=)([a-zA-Z0-9_-]+)/', $videoUrl, $matches)) {
                    $embedUrl = 'https://www.youtube.com/embed/' . $matches[2];
                } else {
                    $embedUrl = $videoUrl;
                }
                ?>
                <?php if ($embedUrl): ?>
                <div class="video-container">
                    <iframe src="<?= htmlspecialchars($embedUrl) ?>" allowfullscreen></iframe>
                </div>
                <?php else: ?>
                <p>Video link: <a href="<?= htmlspecialchars($videoUrl) ?>" target="_blank"><?= htmlspecialchars($videoUrl) ?></a></p>
                <?php endif; ?>
                <?php if (!$watched): ?>
                    <form method="post" action="ajax/mark-resource.php" class="mark-form" style="margin-top: 0.5rem;">
                        <input type="hidden" name="topic_id" value="<?= $topicId ?>">
                        <input type="hidden" name="resource_type" value="video">
                        <button type="submit" class="mark-btn">🎥 Mark as watched</button>
                    </form>
                <?php else: ?>
                    <p><i class="fas fa-check-circle" style="color:#4CAF50;"></i> Video marked as watched.</p>
                <?php endif; ?>
            </div>
        <?php elseif ($res['resource_type'] === 'link'): ?>
            <div class="resource-item">
                <span><i class="fas fa-external-link-alt"></i> <?= htmlspecialchars($res['title']) ?></span>
                <a href="<?= htmlspecialchars($res['url']) ?>" target="_blank">Open link</a>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (empty($resources)): ?>
        <div class="alert-warning">No learning resources for this subtopic.</div>
    <?php endif; ?>
</div>

<script>
    document.querySelectorAll('.mark-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const resp = await fetch('/student/ajax/mark-resource.php', {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();
            if (data.success) {
                // Reload the whole page to reflect updated progress on left tree
                window.location.reload();
            } else {
                alert('Failed to mark. Please try again.');
            }
        });
    });
</script>