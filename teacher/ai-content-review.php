 <?php
/**
 * AI Content Review - Teacher
 * Path: /teacher/ai-content-review.php
 * Allows teachers to review, edit, and approve AI-generated lesson content
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$message = '';
$error = '';

// Get teacher's modules
if ($role === 'admin') {
    $modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT module_id, module_code, module_name FROM modules WHERE created_by = ? ORDER BY module_code");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
}

$selectedModuleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$selectedContentId = isset($_GET['content_id']) ? intval($_GET['content_id']) : 0;

// Handle approval / publishing action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $contentId = intval($_POST['content_id'] ?? 0);
    $topicId = intval($_POST['topic_id'] ?? 0);
    $subtopicId = intval($_POST['subtopic_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $videos = json_decode($_POST['videos_json'] ?? '[]', true);
    $links = json_decode($_POST['links_json'] ?? '[]', true);
    $exercises = json_decode($_POST['exercises_json'] ?? '[]', true);

    if ($action === 'publish') {
        // Determine target table (topic_resources or subtopic_resources)
        $targetTable = $subtopicId ? 'subtopic_resources' : 'topic_resources';
        $idField = $subtopicId ? 'subtopic_id' : 'topic_id';
        $idValue = $subtopicId ?: $topicId;

        // Save notes as resource
        if (!empty($notes)) {
            $stmt = $pdo->prepare("INSERT INTO $targetTable ($idField, resource_type, title, content, source) VALUES (?, 'note', 'AI Generated Lesson Notes', ?, 'ai_generated')");
            $stmt->execute([$idValue, $notes]);
        }
        // Save videos
        foreach ($videos as $video) {
            if (!empty($video['title']) && !empty($video['url'])) {
                $stmt = $pdo->prepare("INSERT INTO $targetTable ($idField, resource_type, title, url, source) VALUES (?, 'video', ?, ?, 'ai_generated')");
                $stmt->execute([$idValue, $video['title'], $video['url']]);
            }
        }
        // Save links
        foreach ($links as $link) {
            if (!empty($link['title']) && !empty($link['url'])) {
                $stmt = $pdo->prepare("INSERT INTO $targetTable ($idField, resource_type, title, url, source) VALUES (?, 'link', ?, ?, 'ai_generated')");
                $stmt->execute([$idValue, $link['title'], $link['url']]);
            }
        }
        // Update status of AI content to 'approved'
        $stmt = $pdo->prepare("UPDATE ai_generated_notes SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE note_id = ?");
        $stmt->execute([$userId, $contentId]);
        $message = "Content approved and published successfully!";
    }
}

// Fetch AI-generated content for the selected module
$contentItems = [];
if ($selectedModuleId > 0) {
    $stmt = $pdo->prepare("
        SELECT n.*, 
               t.topic_title, s.subtopic_title,
               m.module_code, m.module_name
        FROM ai_generated_notes n
        LEFT JOIN topics t ON n.topic_id = t.topic_id
        LEFT JOIN subtopics s ON n.subtopic_id = s.subtopic_id
        JOIN modules m ON (n.topic_id IN (SELECT topic_id FROM topics WHERE ic_id IN (SELECT ic_id FROM indicative_contents WHERE outcome_id IN (SELECT outcome_id FROM learning_outcomes WHERE module_id = ?))) OR n.subtopic_id IN (SELECT subtopic_id FROM subtopics WHERE topic_id IN (SELECT topic_id FROM topics WHERE ic_id IN (SELECT ic_id FROM indicative_contents WHERE outcome_id IN (SELECT outcome_id FROM learning_outcomes WHERE module_id = ?)))))
        WHERE n.status IN ('pending', 'generated')
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$selectedModuleId, $selectedModuleId]);
    $contentItems = $stmt->fetchAll();
}

// Get single content for editing/preview
$currentContent = null;
if ($selectedContentId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM ai_generated_notes WHERE note_id = ?");
    $stmt->execute([$selectedContentId]);
    $currentContent = $stmt->fetch();
    if ($currentContent) {
        $currentContent['videos'] = json_decode($currentContent['suggested_videos'], true) ?: [];
        $currentContent['links'] = json_decode($currentContent['suggested_links'], true) ?: [];
        $currentContent['exercises'] = json_decode($currentContent['suggested_exercises'], true) ?: [];
    }
}

include_once '../includes/templates/header.php';
?>

<div class="ai-review-container">
    <div class="page-header">
        <h1><i class="fas fa-robot"></i> AI Content Review</h1>
        <p>Review, edit, and approve AI-generated lesson content before publishing</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Module Selection -->
    <div class="selection-area">
        <select id="module_select" class="module-select" onchange="loadModule()">
            <option value="">-- Select Module --</option>
            <?php foreach ($modules as $module): ?>
                <option value="<?= $module['module_id']; ?>" <?= $selectedModuleId == $module['module_id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($module['module_code']); ?> - <?= htmlspecialchars($module['module_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($selectedModuleId > 0): ?>
        <div class="content-list">
            <h2>Pending AI Content</h2>
            <?php if (empty($contentItems)): ?>
                <div class="empty-state">No pending AI-generated content for this module.</div>
            <?php else: ?>
                <div class="content-grid">
                    <?php foreach ($contentItems as $item): ?>
                        <div class="content-card">
                            <div class="card-header">
                                <h3><?= htmlspecialchars($item['topic_title'] ?? $item['subtopic_title'] ?? 'Untitled') ?></h3>
                                <span class="status-badge"><?= ucfirst($item['status']) ?></span>
                            </div>
                            <div class="card-meta">
                                <small>Generated: <?= date('M d, Y', strtotime($item['created_at'])) ?></small>
                            </div>
                            <div class="card-preview">
                                <?= htmlspecialchars(substr($item['generated_notes'], 0, 150)) ?>...
                            </div>
                            <div class="card-actions">
                                <a href="?module_id=<?= $selectedModuleId ?>&content_id=<?= $item['note_id'] ?>" class="btn-review">Review & Edit</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($currentContent): ?>
        <div class="review-editor">
            <h2>Review & Edit Content</h2>
            <form method="post" id="reviewForm">
                <input type="hidden" name="action" value="publish">
                <input type="hidden" name="content_id" value="<?= $currentContent['note_id'] ?>">
                <input type="hidden" name="topic_id" value="<?= $currentContent['topic_id'] ?>">
                <input type="hidden" name="subtopic_id" value="<?= $currentContent['subtopic_id'] ?>">
                <input type="hidden" name="videos_json" id="videos_json">
                <input type="hidden" name="links_json" id="links_json">
                <input type="hidden" name="exercises_json" id="exercises_json">

                <div class="form-group">
                    <label>AI Generated Notes (Editable)</label>
                    <textarea name="notes" class="form-control" rows="15"><?= htmlspecialchars($currentContent['generated_notes']) ?></textarea>
                </div>

                <div class="form-group">
                    <label>Suggested Videos</label>
                    <div id="videos_container">
                        <?php foreach ($currentContent['videos'] as $idx => $video): ?>
                            <div class="video-item">
                                <input type="text" class="video-title" value="<?= htmlspecialchars($video['title'] ?? '') ?>" placeholder="Video Title">
                                <input type="url" class="video-url" value="<?= htmlspecialchars($video['url'] ?? '') ?>" placeholder="YouTube URL">
                                <button type="button" class="btn-remove" onclick="removeVideo(this)">🗑️</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-add" onclick="addVideo()">+ Add Video</button>
                </div>

                <div class="form-group">
                    <label>Suggested Links</label>
                    <div id="links_container">
                        <?php foreach ($currentContent['links'] as $idx => $link): ?>
                            <div class="link-item">
                                <input type="text" class="link-title" value="<?= htmlspecialchars($link['title'] ?? '') ?>" placeholder="Link Title">
                                <input type="url" class="link-url" value="<?= htmlspecialchars($link['url'] ?? '') ?>" placeholder="URL">
                                <button type="button" class="btn-remove" onclick="removeLink(this)">🗑️</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-add" onclick="addLink()">+ Add Link</button>
                </div>

                <div class="form-group">
                    <label>Suggested Exercises</label>
                    <div id="exercises_container">
                        <?php foreach ($currentContent['exercises'] as $idx => $ex): ?>
                            <div class="exercise-item">
                                <input type="text" class="exercise-title" value="<?= htmlspecialchars($ex['title'] ?? '') ?>" placeholder="Title">
                                <textarea class="exercise-desc" rows="2" placeholder="Description"><?= htmlspecialchars($ex['description'] ?? '') ?></textarea>
                                <select class="exercise-difficulty">
                                    <option value="easy" <?= ($ex['difficulty'] ?? '') == 'easy' ? 'selected' : '' ?>>Easy</option>
                                    <option value="medium" <?= ($ex['difficulty'] ?? '') == 'medium' ? 'selected' : '' ?>>Medium</option>
                                    <option value="hard" <?= ($ex['difficulty'] ?? '') == 'hard' ? 'selected' : '' ?>>Hard</option>
                                </select>
                                <button type="button" class="btn-remove" onclick="removeExercise(this)">🗑️</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-add" onclick="addExercise()">+ Add Exercise</button>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-publish">Approve & Publish</button>
                    <a href="?module_id=<?= $selectedModuleId ?>" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="info-message">Please select a module to review AI-generated content.</div>
    <?php endif; ?>
</div>

<style>
    .ai-review-container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
    .page-header { margin-bottom: 25px; }
    .selection-area { margin-bottom: 25px; }
    .module-select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 16px; }
    .content-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px; }
    .content-card { background: white; border-radius: 1rem; padding: 1.2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
    .status-badge { background: #ff9800; color: white; padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.7rem; }
    .card-meta { font-size: 0.7rem; color: #8aaec0; margin-bottom: 0.5rem; }
    .card-preview { font-size: 0.85rem; color: #2c5a74; margin-bottom: 1rem; }
    .btn-review { background: #2c7da0; color: white; padding: 0.3rem 0.8rem; border-radius: 1.5rem; text-decoration: none; font-size: 0.8rem; display: inline-block; }
    .review-editor { background: white; border-radius: 1.2rem; padding: 1.5rem; margin-top: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .form-group { margin-bottom: 1.5rem; }
    .form-group label { font-weight: 600; margin-bottom: 0.5rem; display: block; }
    .form-control { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 0.8rem; font-family: monospace; }
    .video-item, .link-item, .exercise-item { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center; flex-wrap: wrap; }
    .video-item input, .link-item input { flex: 1; padding: 0.4rem; }
    .exercise-item { flex-direction: column; background: #f8fafc; padding: 0.8rem; border-radius: 0.8rem; }
    .exercise-item input, .exercise-item textarea, .exercise-item select { width: 100%; margin-bottom: 0.3rem; }
    .btn-add { background: #4CAF50; color: white; border: none; padding: 0.3rem 0.8rem; border-radius: 1rem; cursor: pointer; font-size: 0.75rem; }
    .btn-remove { background: #f44336; color: white; border: none; padding: 0.2rem 0.6rem; border-radius: 1rem; cursor: pointer; font-size: 0.7rem; }
    .form-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }
    .btn-publish { background: #2c7da0; color: white; border: none; padding: 0.5rem 1rem; border-radius: 2rem; cursor: pointer; }
    .btn-cancel { background: #ccc; color: #333; padding: 0.5rem 1rem; border-radius: 2rem; text-decoration: none; }
    .alert { padding: 0.8rem; border-radius: 0.5rem; margin-bottom: 1rem; }
    .alert-success { background: #e8f5e9; color: #2e7d32; }
    .info-message, .empty-state { text-align: center; padding: 3rem; background: #f8fafc; border-radius: 1rem; color: #8aaec0; }
</style>

<script>
    function addVideo() {
        const container = document.getElementById('videos_container');
        const div = document.createElement('div');
        div.className = 'video-item';
        div.innerHTML = '<input type="text" class="video-title" placeholder="Video Title"><input type="url" class="video-url" placeholder="YouTube URL"><button type="button" class="btn-remove" onclick="removeVideo(this)">🗑️</button>';
        container.appendChild(div);
    }
    function removeVideo(btn) { btn.parentElement.remove(); }
    function addLink() {
        const container = document.getElementById('links_container');
        const div = document.createElement('div');
        div.className = 'link-item';
        div.innerHTML = '<input type="text" class="link-title" placeholder="Link Title"><input type="url" class="link-url" placeholder="URL"><button type="button" class="btn-remove" onclick="removeLink(this)">🗑️</button>';
        container.appendChild(div);
    }
    function removeLink(btn) { btn.parentElement.remove(); }
    function addExercise() {
        const container = document.getElementById('exercises_container');
        const div = document.createElement('div');
        div.className = 'exercise-item';
        div.innerHTML = '<input type="text" class="exercise-title" placeholder="Title"><textarea class="exercise-desc" rows="2" placeholder="Description"></textarea><select class="exercise-difficulty"><option value="easy">Easy</option><option value="medium">Medium</option><option value="hard">Hard</option></select><button type="button" class="btn-remove" onclick="removeExercise(this)">🗑️</button>';
        container.appendChild(div);
    }
    function removeExercise(btn) { btn.parentElement.remove(); }

    function collectData() {
        let videos = [];
        document.querySelectorAll('.video-item').forEach(item => {
            let title = item.querySelector('.video-title').value;
            let url = item.querySelector('.video-url').value;
            if (title && url) videos.push({title, url});
        });
        document.getElementById('videos_json').value = JSON.stringify(videos);

        let links = [];
        document.querySelectorAll('.link-item').forEach(item => {
            let title = item.querySelector('.link-title').value;
            let url = item.querySelector('.link-url').value;
            if (title && url) links.push({title, url});
        });
        document.getElementById('links_json').value = JSON.stringify(links);

        let exercises = [];
        document.querySelectorAll('.exercise-item').forEach(item => {
            let title = item.querySelector('.exercise-title').value;
            let desc = item.querySelector('.exercise-desc').value;
            let difficulty = item.querySelector('.exercise-difficulty').value;
            if (title || desc) exercises.push({title, description: desc, difficulty});
        });
        document.getElementById('exercises_json').value = JSON.stringify(exercises);
    }

    document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
        collectData();
    });
</script>

<?php include_once '../includes/templates/footer.php'; ?>
