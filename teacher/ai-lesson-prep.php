<?php
/**
 * AI Lesson Preparation - Fixed FK error for subtopics
 * Path: /teacher/ai-lesson-prep.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);
require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

// Get selections
$selectedModuleId   = (int) ($_GET['module_id'] ?? 0);
$selectedLoId       = (int) ($_GET['lo_id'] ?? 0);
$selectedIcId       = (int) ($_GET['ic_id'] ?? 0);
$selectedTopicId    = (int) ($_GET['topic_id'] ?? 0);
$selectedSubtopicId = (int) ($_GET['subtopic_id'] ?? 0);

// ----- Get modules assigned to teacher -----
if ($role === 'admin') {
    $modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT m.module_id, m.module_code, m.module_name
        FROM modules m
        JOIN teacher_modules tm ON m.module_id = tm.module_id
        WHERE tm.teacher_id = ?
        ORDER BY m.module_code
    ");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
}

// Load hierarchy
$los = $ics = $topics = $subtopics = [];
if ($selectedModuleId) {
    $stmt = $pdo->prepare("SELECT outcome_id, outcome_number, description FROM learning_outcomes WHERE module_id = ? ORDER BY outcome_number");
    $stmt->execute([$selectedModuleId]);
    $los = $stmt->fetchAll();
}
if ($selectedLoId) {
    $stmt = $pdo->prepare("SELECT ic_id, ic_title FROM indicative_contents WHERE outcome_id = ? ORDER BY ic_order");
    $stmt->execute([$selectedLoId]);
    $ics = $stmt->fetchAll();
}
if ($selectedIcId) {
    $stmt = $pdo->prepare("SELECT topic_id, topic_title FROM topics WHERE ic_id = ? ORDER BY topic_order");
    $stmt->execute([$selectedIcId]);
    $topics = $stmt->fetchAll();
}
if ($selectedTopicId) {
    $stmt = $pdo->prepare("SELECT subtopic_id, subtopic_title FROM subtopics WHERE topic_id = ? ORDER BY subtopic_order");
    $stmt->execute([$selectedTopicId]);
    $subtopics = $stmt->fetchAll();
}

// Determine selected level and get parent topic_id (for FK)
$selectedLevelId = 0;
$levelType = '';
$selectedName = '';
$parentTopicId = 0; // for ai_conversations foreign key

if ($selectedSubtopicId > 0) {
    $levelType = 'subtopic';
    $selectedLevelId = $selectedSubtopicId;
    $stmt = $pdo->prepare("SELECT subtopic_title, topic_id FROM subtopics WHERE subtopic_id = ?");
    $stmt->execute([$selectedSubtopicId]);
    $row = $stmt->fetch();
    if ($row) {
        $selectedName = $row['subtopic_title'];
        $parentTopicId = $row['topic_id'];
    }
} elseif ($selectedTopicId > 0) {
    $levelType = 'topic';
    $selectedLevelId = $selectedTopicId;
    $stmt = $pdo->prepare("SELECT topic_title FROM topics WHERE topic_id = ?");
    $stmt->execute([$selectedTopicId]);
    $selectedName = $stmt->fetchColumn() ?: '';
    $parentTopicId = $selectedTopicId; // topic is its own parent
}

// Get existing AI generated content
$aiNotes = '';
$aiVideos = [];
$aiLinks = [];
$aiExercises = [];

if ($selectedLevelId && $levelType) {
    if ($levelType === 'topic') {
        $stmt = $pdo->prepare("SELECT * FROM ai_generated_notes WHERE topic_id = ? ORDER BY note_id DESC LIMIT 1");
        $stmt->execute([$selectedLevelId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM ai_generated_notes WHERE subtopic_id = ? ORDER BY note_id DESC LIMIT 1");
        $stmt->execute([$selectedLevelId]);
    }
    $aiData = $stmt->fetch();
    if ($aiData) {
        $aiNotes = $aiData['generated_notes'] ?? '';
        $aiVideos = json_decode($aiData['suggested_videos'] ?? '[]', true) ?: [];
        $aiLinks = json_decode($aiData['suggested_links'] ?? '[]', true) ?: [];
        $aiExercises = json_decode($aiData['suggested_exercises'] ?? '[]', true) ?: [];
    }
}

// Get feedback history (using parent topic_id for FK)
$feedbackHistory = [];
if ($parentTopicId) {
    $stmt = $pdo->prepare("
        SELECT * FROM ai_conversations 
        WHERE topic_id = ? AND teacher_id = ? 
        ORDER BY created_at DESC LIMIT 10
    ");
    $stmt->execute([$parentTopicId, $userId]);
    $feedbackHistory = $stmt->fetchAll();
}

// Handle POST
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate_ai') {
        $feedback = trim($_POST['feedback_text'] ?? '');
        if (!$selectedLevelId || !$levelType) {
            $error = "Please select a topic or subtopic first.";
        } else {
            // ----- Simulate AI (replace with real API) -----
            $aiNotes = "# {$selectedName}\n\n";
            if (!empty($feedback)) {
                $aiNotes .= "## 📝 Teacher feedback applied:\n{$feedback}\n\n";
            }
            $aiNotes .= "## Overview\nThis content is for *{$selectedName}*.\n\n";
            $aiNotes .= "### Key Concepts\n- Concept 1\n- Concept 2\n\n### Summary\n- Key takeaway\n";
            $aiVideos = [['title' => "Intro to {$selectedName}", 'url' => 'https://example.com', 'duration' => '5']];
            $aiLinks = [['title' => 'Documentation', 'url' => 'https://example.com/docs']];
            $aiExercises = [['title' => 'Practice', 'description' => 'Apply the concept', 'difficulty' => 'easy']];
            
            // Save to ai_generated_notes
            if ($levelType === 'topic') {
                $stmt = $pdo->prepare("INSERT INTO ai_generated_notes (topic_id, generated_notes, suggested_videos, suggested_links, suggested_exercises, source) VALUES (?, ?, ?, ?, ?, 'ai_generated')");
                $stmt->execute([$selectedLevelId, $aiNotes, json_encode($aiVideos), json_encode($aiLinks), json_encode($aiExercises)]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO ai_generated_notes (subtopic_id, generated_notes, suggested_videos, suggested_links, suggested_exercises, source) VALUES (?, ?, ?, ?, ?, 'ai_generated')");
                $stmt->execute([$selectedLevelId, $aiNotes, json_encode($aiVideos), json_encode($aiLinks), json_encode($aiExercises)]);
            }
            
            // Save conversation (using parentTopicId for FK)
            if (!empty($feedback)) {
                $stmt = $pdo->prepare("INSERT INTO ai_conversations (teacher_id, topic_id, teacher_message, ai_response) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $parentTopicId, $feedback, "AI content regenerated based on your feedback."]);
            }
            
            $message = "✅ AI content generated!";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
    elseif ($action === 'save_approved') {
        $notes = $_POST['notes'] ?? '';
        $videos = json_decode($_POST['videos_json'] ?? '[]', true);
        $links = json_decode($_POST['links_json'] ?? '[]', true);
        
        $targetTable = ($levelType === 'topic') ? 'topic_resources' : 'subtopic_resources';
        $idField = ($levelType === 'topic') ? 'topic_id' : 'subtopic_id';
        
        if (!empty($notes)) {
            $stmt = $pdo->prepare("INSERT INTO $targetTable ($idField, resource_type, title, content, source) VALUES (?, 'note', 'AI Lesson Notes', ?, 'ai_generated')");
            $stmt->execute([$selectedLevelId, $notes]);
        }
        foreach ($videos as $video) {
            if (!empty($video['title']) && !empty($video['url'])) {
                $stmt = $pdo->prepare("INSERT INTO $targetTable ($idField, resource_type, title, url, source) VALUES (?, 'video', ?, ?, 'ai_generated')");
                $stmt->execute([$selectedLevelId, $video['title'], $video['url']]);
            }
        }
        foreach ($links as $link) {
            if (!empty($link['title']) && !empty($link['url'])) {
                $stmt = $pdo->prepare("INSERT INTO $targetTable ($idField, resource_type, title, url, source) VALUES (?, 'link', ?, ?, 'ai_generated')");
                $stmt->execute([$selectedLevelId, $link['title'], $link['url']]);
            }
        }
        $message = "✅ Content saved to resources!";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

include_once '../includes/templates/header.php';
?>

<div class="ai-prep-container">
    <div class="page-header">
        <h1><i class="fas fa-robot"></i> AI Lesson Preparation</h1>
        <p>Generate AI content for Topics or Subtopics. Provide feedback to improve.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="info-warning">
        <i class="fas fa-info-circle"></i> 
        <strong>Demo AI:</strong> This is a simulated AI. To use a real LLM (GPT, Gemini), provide an API key.
    </div>

    <!-- Hierarchy selector (auto-submit) -->
    <div class="hierarchy-selector">
        <select id="module_sel" onchange="applyFilters()">
            <option value="">-- Module --</option>
            <?php foreach ($modules as $mod): ?>
                <option value="<?= $mod['module_id'] ?>" <?= $selectedModuleId == $mod['module_id'] ? 'selected' : '' ?>><?= htmlspecialchars($mod['module_code']) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="lo_sel" onchange="applyFilters()" <?= empty($los) ? 'disabled' : '' ?>>
            <option value="">-- LO --</option>
            <?php foreach ($los as $lo): ?>
                <option value="<?= $lo['outcome_id'] ?>" <?= $selectedLoId == $lo['outcome_id'] ? 'selected' : '' ?>>LO<?= $lo['outcome_number'] ?></option>
            <?php endforeach; ?>
        </select>
        <select id="ic_sel" onchange="applyFilters()" <?= empty($ics) ? 'disabled' : '' ?>>
            <option value="">-- IC --</option>
            <?php foreach ($ics as $ic): ?>
                <option value="<?= $ic['ic_id'] ?>" <?= $selectedIcId == $ic['ic_id'] ? 'selected' : '' ?>><?= htmlspecialchars(substr($ic['ic_title'], 0, 30)) ?>…</option>
            <?php endforeach; ?>
        </select>
        <select id="topic_sel" onchange="applyFilters()" <?= empty($topics) ? 'disabled' : '' ?>>
            <option value="">-- Topic --</option>
            <?php foreach ($topics as $top): ?>
                <option value="<?= $top['topic_id'] ?>" <?= $selectedTopicId == $top['topic_id'] ? 'selected' : '' ?>><?= htmlspecialchars(substr($top['topic_title'], 0, 35)) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="subtopic_sel" onchange="applyFilters()" <?= empty($subtopics) ? 'disabled' : '' ?>>
            <option value="">-- Subtopic --</option>
            <?php foreach ($subtopics as $sub): ?>
                <option value="<?= $sub['subtopic_id'] ?>" <?= $selectedSubtopicId == $sub['subtopic_id'] ? 'selected' : '' ?>><?= htmlspecialchars($sub['subtopic_title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if ($selectedLevelId && $levelType): ?>
        <div class="ai-content-area">
            <div class="content-header">
                <h2>🤖 AI Content for: <?= htmlspecialchars($selectedName) ?> (<?= ucfirst($levelType) ?>)</h2>
            </div>

            <div class="feedback-card">
                <h3><i class="fas fa-comment-dots"></i> Give feedback to improve AI</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="generate_ai">
                    <textarea name="feedback_text" rows="3" class="feedback-textarea" placeholder="e.g., Add more examples, include a quiz, simplify language..."></textarea>
                    <button type="submit" class="btn-generate"><i class="fas fa-magic"></i> Generate / Regenerate</button>
                </form>
            </div>

            <!-- AI Notes -->
            <div class="ai-section">
                <div class="section-header" onclick="toggleSection('notes-section')">
                    <h3><i class="fas fa-file-alt"></i> AI Notes</h3>
                    <span class="toggle">−</span>
                </div>
                <div id="notes-section" class="section-content">
                    <textarea id="ai_notes" class="notes-textarea" rows="12"><?= htmlspecialchars($aiNotes) ?></textarea>
                </div>
            </div>

            <!-- Videos -->
            <div class="ai-section">
                <div class="section-header" onclick="toggleSection('videos-section')">
                    <h3><i class="fas fa-video"></i> Suggested Videos</h3>
                    <span class="toggle">−</span>
                </div>
                <div id="videos-section" class="section-content">
                    <div id="videos_list"><?php foreach ($aiVideos as $v): ?>
                        <div class="video-item"><input type="text" class="video-title" value="<?= htmlspecialchars($v['title']) ?>"><input type="url" class="video-url" value="<?= htmlspecialchars($v['url']) ?>"><button class="btn-remove" onclick="removeItem(this)">🗑️</button></div>
                    <?php endforeach; if (empty($aiVideos)): ?><div class="empty-message">No videos yet.</div><?php endif; ?></div>
                    <button class="btn-add" onclick="addVideo()">+ Add Video</button>
                </div>
            </div>

            <!-- Links -->
            <div class="ai-section">
                <div class="section-header" onclick="toggleSection('links-section')">
                    <h3><i class="fas fa-link"></i> Suggested Links</h3>
                    <span class="toggle">−</span>
                </div>
                <div id="links-section" class="section-content">
                    <div id="links_list"><?php foreach ($aiLinks as $l): ?>
                        <div class="link-item"><input type="text" class="link-title" value="<?= htmlspecialchars($l['title']) ?>"><input type="url" class="link-url" value="<?= htmlspecialchars($l['url']) ?>"><button class="btn-remove" onclick="removeItem(this)">🗑️</button></div>
                    <?php endforeach; if (empty($aiLinks)): ?><div class="empty-message">No links yet.</div><?php endif; ?></div>
                    <button class="btn-add" onclick="addLink()">+ Add Link</button>
                </div>
            </div>

            <!-- Exercises -->
            <div class="ai-section">
                <div class="section-header" onclick="toggleSection('exercises-section')">
                    <h3><i class="fas fa-tasks"></i> Suggested Exercises</h3>
                    <span class="toggle">−</span>
                </div>
                <div id="exercises-section" class="section-content">
                    <div id="exercises_list"><?php foreach ($aiExercises as $e): ?>
                        <div class="exercise-item"><input type="text" class="exercise-title" value="<?= htmlspecialchars($e['title']) ?>"><textarea class="exercise-desc" rows="2"><?= htmlspecialchars($e['description'] ?? '') ?></textarea><select class="exercise-diff"><option <?= ($e['difficulty']??'')=='easy'?'selected':'' ?>>Easy</option><option <?= ($e['difficulty']??'')=='medium'?'selected':'' ?>>Medium</option><option <?= ($e['difficulty']??'')=='hard'?'selected':'' ?>>Hard</option></select><button class="btn-remove" onclick="removeItem(this)">🗑️</button></div>
                    <?php endforeach; if (empty($aiExercises)): ?><div class="empty-message">No exercises yet.</div><?php endif; ?></div>
                    <button class="btn-add" onclick="addExercise()">+ Add Exercise</button>
                </div>
            </div>

            <div class="review-section">
                <h3><i class="fas fa-check-circle"></i> Review & Save</h3>
                <div class="review-buttons">
                    <button class="btn-approve" onclick="submitApproval()"><i class="fas fa-save"></i> Approve & Save to Resources</button>
                    <button class="btn-edit" onclick="enableEdit()"><i class="fas fa-edit"></i> Edit Notes</button>
                </div>
            </div>
        </div>
    <?php elseif ($selectedModuleId): ?>
        <div class="info-message">Continue selecting LO → IC → Topic → Subtopic.</div>
    <?php else: ?>
        <div class="info-message">Select a module to start.</div>
    <?php endif; ?>
</div>

<form method="POST" id="approveForm">
    <input type="hidden" name="action" value="save_approved">
    <input type="hidden" name="notes" id="final_notes">
    <input type="hidden" name="videos_json" id="final_videos">
    <input type="hidden" name="links_json" id="final_links">
    <input type="hidden" name="exercises_json" id="final_exercises">
</form>

<style>
/* (styles same as before, keep your existing CSS) */
.ai-prep-container{max-width:1100px;margin:0 auto;padding:30px 20px;}
.hierarchy-selector{background:white;border-radius:16px;padding:20px;margin-bottom:25px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.hierarchy-selector select{flex:1;min-width:120px;padding:8px;border:1px solid #ddd;border-radius:8px;}
.info-warning{background:#fff3cd;border-left:4px solid #ffc107;padding:12px;border-radius:8px;margin-bottom:20px;}
.feedback-card{background:#e8f0fe;border-radius:16px;padding:20px;margin-bottom:25px;}
.feedback-textarea{width:100%;padding:12px;border-radius:12px;border:1px solid #ccc;margin-bottom:15px;}
.btn-generate{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;padding:10px 24px;border-radius:30px;cursor:pointer;}
.ai-section{background:#f8f9fa;border-radius:16px;margin-bottom:20px;}
.section-header{display:flex;justify-content:space-between;padding:12px 20px;background:#e9ecef;cursor:pointer;}
.section-content{padding:20px;}
.notes-textarea{width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-family:monospace;}
.video-item,.link-item{display:flex;gap:10px;margin-bottom:10px;flex-wrap:wrap;}
.video-item input,.link-item input{flex:1;padding:8px;}
.exercise-item{background:white;padding:12px;border-radius:10px;margin-bottom:10px;}
.exercise-item input,.exercise-item textarea,.exercise-item select{width:100%;margin-bottom:8px;}
.btn-remove{background:#f44336;color:white;border:none;padding:5px 12px;border-radius:20px;}
.btn-add{background:#4CAF50;color:white;border:none;padding:8px 20px;border-radius:30px;cursor:pointer;margin-top:10px;}
.review-section{background:#e8f5e9;border-radius:16px;padding:20px;text-align:center;margin-top:20px;}
.review-buttons{display:flex;gap:15px;justify-content:center;}
.btn-approve{background:#2e7d32;color:white;border:none;padding:10px 30px;border-radius:30px;cursor:pointer;}
.btn-edit{background:#ff9800;color:white;border:none;padding:10px 30px;border-radius:30px;cursor:pointer;}
.alert{padding:12px;border-radius:10px;margin-bottom:15px;}
.alert-success{background:#e8f5e9;color:#2e7d32;}
.info-message{text-align:center;padding:40px;background:#f8f9fa;border-radius:20px;}
.empty-message{text-align:center;color:#999;padding:15px;}
</style>

<script>
function applyFilters() {
    let m = document.getElementById('module_sel').value;
    let l = document.getElementById('lo_sel').value;
    let i = document.getElementById('ic_sel').value;
    let t = document.getElementById('topic_sel').value;
    let s = document.getElementById('subtopic_sel').value;
    let url = '?';
    if(m) url += 'module_id=' + m;
    if(l) url += '&lo_id=' + l;
    if(i) url += '&ic_id=' + i;
    if(t) url += '&topic_id=' + t;
    if(s) url += '&subtopic_id=' + s;
    window.location.href = url;
}

function toggleSection(id) {
    let section = document.getElementById(id);
    let toggle = event.currentTarget.querySelector('.toggle');
    if (section.style.display === 'none') {
        section.style.display = 'block';
        toggle.textContent = '−';
    } else {
        section.style.display = 'none';
        toggle.textContent = '+';
    }
}

function addVideo() { addItem('videos_list', 'video-item', '<input type="text" class="video-title" placeholder="Title"><input type="url" class="video-url" placeholder="URL"><button class="btn-remove" onclick="removeItem(this)">🗑️</button>'); }
function addLink() { addItem('links_list', 'link-item', '<input type="text" class="link-title" placeholder="Title"><input type="url" class="link-url" placeholder="URL"><button class="btn-remove" onclick="removeItem(this)">🗑️</button>'); }
function addExercise() { addItem('exercises_list', 'exercise-item', '<input type="text" class="exercise-title" placeholder="Title"><textarea class="exercise-desc" rows="2" placeholder="Description"></textarea><select class="exercise-diff"><option>Easy</option><option>Medium</option><option>Hard</option></select><button class="btn-remove" onclick="removeItem(this)">🗑️</button>'); }
function addItem(containerId, className, html) {
    let container = document.getElementById(containerId);
    let empty = container.querySelector('.empty-message');
    if (empty) empty.remove();
    let div = document.createElement('div');
    div.className = className;
    div.innerHTML = html;
    container.appendChild(div);
}
function removeItem(btn) { btn.parentElement.remove(); }
function enableEdit() {
    document.getElementById('ai_notes').readOnly = false;
    document.getElementById('ai_notes').style.background = 'white';
    alert('You can now edit the notes manually. Click Approve when done.');
}
function submitApproval() {
    let notes = document.getElementById('ai_notes').value;
    let videos = [], links = [], exercises = [];
    document.querySelectorAll('.video-item').forEach(el => {
        let title = el.querySelector('.video-title')?.value;
        let url = el.querySelector('.video-url')?.value;
        if (title && url) videos.push({title, url});
    });
    document.querySelectorAll('.link-item').forEach(el => {
        let title = el.querySelector('.link-title')?.value;
        let url = el.querySelector('.link-url')?.value;
        if (title && url) links.push({title, url});
    });
    document.querySelectorAll('.exercise-item').forEach(el => {
        let title = el.querySelector('.exercise-title')?.value;
        let desc = el.querySelector('.exercise-desc')?.value;
        let diff = el.querySelector('.exercise-diff')?.value;
        if (title) exercises.push({title, description: desc, difficulty: diff});
    });
    document.getElementById('final_notes').value = notes;
    document.getElementById('final_videos').value = JSON.stringify(videos);
    document.getElementById('final_links').value = JSON.stringify(links);
    document.getElementById('final_exercises').value = JSON.stringify(exercises);
    document.getElementById('approveForm').submit();
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>