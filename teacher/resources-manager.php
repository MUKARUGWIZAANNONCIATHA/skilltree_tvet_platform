<?php
/**
 * Resources Manager – FINAL VERSION (Separate forms, no JS validation conflicts)
 * Path: /teacher/resources-manager.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);
require_once '../config/database.php';
require_once '../includes/functions/common.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userId = $_SESSION['user_id'];
$role   = $_SESSION['user_role'];

// ----- 1. Read current selection from URL -----
$selectedModuleId   = (int) ($_GET['module_id'] ?? 0);
$selectedLoId       = (int) ($_GET['lo_id'] ?? 0);
$selectedIcId       = (int) ($_GET['ic_id'] ?? 0);
$selectedTopicId    = (int) ($_GET['topic_id'] ?? 0);
$selectedSubtopicId = (int) ($_GET['subtopic_id'] ?? 0);

// ----- 2. Get modules (teacher only assigned) -----
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

// ----- 3. Load hierarchy based on current selection -----
$los = [];
if ($selectedModuleId) {
    $stmt = $pdo->prepare("SELECT outcome_id, outcome_number, description FROM learning_outcomes WHERE module_id = ? ORDER BY outcome_number");
    $stmt->execute([$selectedModuleId]);
    $los = $stmt->fetchAll();
}
$ics = [];
if ($selectedLoId) {
    $stmt = $pdo->prepare("SELECT ic_id, ic_title FROM indicative_contents WHERE outcome_id = ? ORDER BY ic_order");
    $stmt->execute([$selectedLoId]);
    $ics = $stmt->fetchAll();
}
$topics = [];
if ($selectedIcId) {
    $stmt = $pdo->prepare("SELECT topic_id, topic_title FROM topics WHERE ic_id = ? ORDER BY topic_order");
    $stmt->execute([$selectedIcId]);
    $topics = $stmt->fetchAll();
}
$subtopics = [];
if ($selectedTopicId) {
    $stmt = $pdo->prepare("SELECT subtopic_id, subtopic_title FROM subtopics WHERE topic_id = ? ORDER BY subtopic_order");
    $stmt->execute([$selectedTopicId]);
    $subtopics = $stmt->fetchAll();
}

// ----- 4. Handle POST (add / edit / delete) -----
$message = '';
$error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add_resource') {
            $targetType = $_POST['target_type'];   // 'topic' or 'subtopic'
            $targetId   = (int) $_POST['target_id'];
            $resType    = $_POST['resource_type']; // 'note', 'video', 'link'
            $title      = trim($_POST['title'] ?? '');
            $content    = $_POST['content'] ?? '';
            $url        = trim($_POST['url'] ?? '');
            $duration   = !empty($_POST['duration']) ? (int) $_POST['duration'] : null;

            if (empty($title)) throw new Exception("Title is required.");

            $table   = ($targetType === 'topic') ? 'topic_resources' : 'subtopic_resources';
            $idField = ($targetType === 'topic') ? 'topic_id' : 'subtopic_id';

            // get next order
            $ordStmt = $pdo->prepare("SELECT MAX(display_order) FROM $table WHERE $idField = ?");
            $ordStmt->execute([$targetId]);
            $nextOrder = ($ordStmt->fetchColumn() ?: 0) + 1;

            if ($resType === 'note') {
                $stmt = $pdo->prepare("INSERT INTO $table ($idField, resource_type, title, content, source, display_order) VALUES (?, 'note', ?, ?, 'teacher_added', ?)");
                $stmt->execute([$targetId, $title, $content, $nextOrder]);
                $message = "✅ Note added!";
            }
            elseif ($resType === 'video') {
                if (empty($url)) throw new Exception("Video URL is required.");
                $stmt = $pdo->prepare("INSERT INTO $table ($idField, resource_type, title, url, duration_minutes, source, display_order) VALUES (?, 'video', ?, ?, ?, 'teacher_added', ?)");
                $stmt->execute([$targetId, $title, $url, $duration, $nextOrder]);
                $message = "✅ Video added!";
            }
            elseif ($resType === 'link') {
                if (empty($url)) throw new Exception("Link URL is required.");
                $stmt = $pdo->prepare("INSERT INTO $table ($idField, resource_type, title, url, source, display_order) VALUES (?, 'link', ?, ?, 'teacher_added', ?)");
                $stmt->execute([$targetId, $title, $url, $nextOrder]);
                $message = "✅ Link added!";
            }
            else throw new Exception("Invalid resource type.");

            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
        elseif ($action === 'edit_resource') {
            // same as before (unchanged) – omitted for brevity, but keep it
            $resId      = (int) $_POST['resource_id'];
            $targetType = $_POST['target_type'];
            $resType    = $_POST['resource_type'];
            $title      = trim($_POST['title']);
            $content    = $_POST['content'];
            $url        = trim($_POST['url']);
            $duration   = !empty($_POST['duration']) ? (int) $_POST['duration'] : null;
            $table = ($targetType === 'topic') ? 'topic_resources' : 'subtopic_resources';
            if ($resType === 'note') {
                $stmt = $pdo->prepare("UPDATE $table SET title = ?, content = ? WHERE resource_id = ?");
                $stmt->execute([$title, $content, $resId]);
                $message = "✅ Note updated!";
            }
            elseif ($resType === 'video') {
                $stmt = $pdo->prepare("UPDATE $table SET title = ?, url = ?, duration_minutes = ? WHERE resource_id = ?");
                $stmt->execute([$title, $url, $duration, $resId]);
                $message = "✅ Video updated!";
            }
            elseif ($resType === 'link') {
                $stmt = $pdo->prepare("UPDATE $table SET title = ?, url = ? WHERE resource_id = ?");
                $stmt->execute([$title, $url, $resId]);
                $message = "✅ Link updated!";
            }
        }
        elseif ($action === 'delete_resource') {
            $resId      = (int) $_POST['resource_id'];
            $targetType = $_POST['target_type'];
            $table = ($targetType === 'topic') ? 'topic_resources' : 'subtopic_resources';
            $pdo->prepare("DELETE FROM $table WHERE resource_id = ?")->execute([$resId]);
            $message = "🗑️ Resource deleted.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ----- 5. Retrieve resources -----
$resources = [];
if ($selectedSubtopicId > 0) {
    $sql = "SELECT resource_id, subtopic_id as target_id, resource_type, title, content, url, duration_minutes,
                   source, created_at, 'subtopic' as target_type, display_order
            FROM subtopic_resources
            WHERE subtopic_id = ?
            ORDER BY display_order ASC, created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$selectedSubtopicId]);
    $resources = $stmt->fetchAll();
} elseif ($selectedTopicId > 0) {
    $sql = "SELECT resource_id, topic_id as target_id, resource_type, title, content, url, duration_minutes,
                   source, created_at, 'topic' as target_type, display_order
            FROM topic_resources
            WHERE topic_id = ?
            ORDER BY display_order ASC, created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$selectedTopicId]);
    $resources = $stmt->fetchAll();
}

// ----- 6. Labels -----
$selectedModuleName = $selectedLoDesc = $selectedIcTitle = $selectedTopicTitle = $selectedSubtopicTitle = '';
if ($selectedModuleId) {
    $m = $pdo->prepare("SELECT module_code, module_name FROM modules WHERE module_id = ?");
    $m->execute([$selectedModuleId]);
    $row = $m->fetch();
    $selectedModuleName = $row['module_code'] . ' - ' . $row['module_name'];
}
if ($selectedLoId) {
    $l = $pdo->prepare("SELECT description FROM learning_outcomes WHERE outcome_id = ?");
    $l->execute([$selectedLoId]);
    $selectedLoDesc = substr($l->fetchColumn(), 0, 60);
}
if ($selectedIcId) {
    $i = $pdo->prepare("SELECT ic_title FROM indicative_contents WHERE ic_id = ?");
    $i->execute([$selectedIcId]);
    $selectedIcTitle = substr($i->fetchColumn(), 0, 60);
}
if ($selectedTopicId) {
    $t = $pdo->prepare("SELECT topic_title FROM topics WHERE topic_id = ?");
    $t->execute([$selectedTopicId]);
    $selectedTopicTitle = $t->fetchColumn();
}
if ($selectedSubtopicId) {
    $s = $pdo->prepare("SELECT subtopic_title FROM subtopics WHERE subtopic_id = ?");
    $s->execute([$selectedSubtopicId]);
    $selectedSubtopicTitle = $s->fetchColumn();
}

include_once '../includes/templates/header.php';
?>

<div class="resources-manager">
    <div class="page-header">
        <h1><i class="fas fa-chalkboard-teacher"></i> Resources Manager</h1>
        <a href="/teacher/dashboard.php" class="btn-back">← Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Navigation path -->
    <?php if ($selectedModuleId): ?>
        <div class="nav-path">
            📍 <strong><?= htmlspecialchars($selectedModuleName) ?></strong>
            <?php if ($selectedLoId): ?> → LO <?= htmlspecialchars($selectedLoDesc) ?><?php endif; ?>
            <?php if ($selectedIcId): ?> → IC <?= htmlspecialchars($selectedIcTitle) ?><?php endif; ?>
            <?php if ($selectedTopicId): ?> → <strong><?= htmlspecialchars($selectedTopicTitle) ?></strong><?php endif; ?>
            <?php if ($selectedSubtopicId): ?> → ✓ <strong><?= htmlspecialchars($selectedSubtopicTitle) ?></strong><?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Hierarchy selector (auto‑submit on change) -->
    <form method="GET" class="selector-card">
        <div class="selector-flex">
            <div class="sel-item">
                <span class="sel-num">1</span> Module<br>
                <select name="module_id" class="sel-input" onchange="this.form.submit()">
                    <option value="">-- Select Module --</option>
                    <?php foreach ($modules as $mod): ?>
                        <option value="<?= $mod['module_id'] ?>" <?= $selectedModuleId == $mod['module_id'] ? 'selected' : '' ?>><?= htmlspecialchars($mod['module_code']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sel-arrow">→</div>
            <div class="sel-item">
                <span class="sel-num">2</span> LO<br>
                <select name="lo_id" class="sel-input" <?= empty($los) ? 'disabled' : '' ?> onchange="this.form.submit()">
                    <option value="">-- Select LO --</option>
                    <?php foreach ($los as $lo): ?>
                        <option value="<?= $lo['outcome_id'] ?>" <?= $selectedLoId == $lo['outcome_id'] ? 'selected' : '' ?>>LO<?= $lo['outcome_number'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sel-arrow">→</div>
            <div class="sel-item">
                <span class="sel-num">3</span> IC<br>
                <select name="ic_id" class="sel-input" <?= empty($ics) ? 'disabled' : '' ?> onchange="this.form.submit()">
                    <option value="">-- Select IC --</option>
                    <?php foreach ($ics as $ic): ?>
                        <option value="<?= $ic['ic_id'] ?>" <?= $selectedIcId == $ic['ic_id'] ? 'selected' : '' ?>><?= htmlspecialchars(substr($ic['ic_title'], 0, 35)) ?>…</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sel-arrow">→</div>
            <div class="sel-item">
                <span class="sel-num">4</span> Topic<br>
                <select name="topic_id" class="sel-input" <?= empty($topics) ? 'disabled' : '' ?> onchange="this.form.submit()">
                    <option value="">-- Select Topic --</option>
                    <?php foreach ($topics as $top): ?>
                        <option value="<?= $top['topic_id'] ?>" <?= $selectedTopicId == $top['topic_id'] ? 'selected' : '' ?>><?= htmlspecialchars(substr($top['topic_title'], 0, 40)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sel-arrow">→</div>
            <div class="sel-item">
                <span class="sel-num">5</span> Subtopic<br>
                <select name="subtopic_id" class="sel-input" <?= empty($subtopics) ? 'disabled' : '' ?> onchange="this.form.submit()">
                    <option value="">-- Select Subtopic --</option>
                    <?php foreach ($subtopics as $sub): ?>
                        <option value="<?= $sub['subtopic_id'] ?>" <?= $selectedSubtopicId == $sub['subtopic_id'] ? 'selected' : '' ?>><?= htmlspecialchars($sub['subtopic_title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>

    <!-- Debug info (helps diagnose missing data) -->
    <?php if ($selectedModuleId): ?>
        <div class="debug-box">
            🔍 LO count: <?= count($los) ?> | IC count: <?= count($ics) ?> | Topic count: <?= count($topics) ?> | Subtopic count: <?= count($subtopics) ?> | Resources here: <?= count($resources) ?>
        </div>
    <?php endif; ?>

    <!-- Add resource area (only when a subtopic or topic is selected) -->
    <?php if ($selectedSubtopicId > 0 || $selectedTopicId > 0): ?>
        <div class="add-card">
            <h2>📥 Add Resource to: <?= htmlspecialchars($selectedSubtopicId ? $selectedSubtopicTitle : $selectedTopicTitle) ?></h2>
            
            <!-- Three separate forms – one for each resource type -->
            <div style="display: flex; gap: 20px; border-bottom: 1px solid #ddd; margin-bottom: 20px;">
                <div class="form-header active" data-type="note">📝 Note</div>
                <div class="form-header" data-type="video">🎥 Video</div>
                <div class="form-header" data-type="link">🔗 Link</div>
            </div>

            <!-- Note form -->
            <div id="note-form" class="resource-form active">
                <form method="post">
                    <input type="hidden" name="action" value="add_resource">
                    <input type="hidden" name="target_type" value="<?= $selectedSubtopicId > 0 ? 'subtopic' : 'topic' ?>">
                    <input type="hidden" name="target_id" value="<?= $selectedSubtopicId ?: $selectedTopicId ?>">
                    <input type="hidden" name="resource_type" value="note">
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" rows="6" class="form-control" placeholder="Write your notes here..."></textarea>
                    </div>
                    <button type="submit" class="btn-submit">💾 Save Note</button>
                </form>
            </div>

            <!-- Video form -->
            <div id="video-form" class="resource-form" style="display:none;">
                <form method="post">
                    <input type="hidden" name="action" value="add_resource">
                    <input type="hidden" name="target_type" value="<?= $selectedSubtopicId > 0 ? 'subtopic' : 'topic' ?>">
                    <input type="hidden" name="target_id" value="<?= $selectedSubtopicId ?: $selectedTopicId ?>">
                    <input type="hidden" name="resource_type" value="video">
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Video URL *</label>
                        <input type="url" name="url" class="form-control" placeholder="https://youtube.com/..." required>
                    </div>
                    <div class="form-group">
                        <label>Duration (minutes, optional)</label>
                        <input type="number" name="duration" class="form-control">
                    </div>
                    <button type="submit" class="btn-submit">💾 Save Video</button>
                </form>
            </div>

            <!-- Link form -->
            <div id="link-form" class="resource-form" style="display:none;">
                <form method="post">
                    <input type="hidden" name="action" value="add_resource">
                    <input type="hidden" name="target_type" value="<?= $selectedSubtopicId > 0 ? 'subtopic' : 'topic' ?>">
                    <input type="hidden" name="target_id" value="<?= $selectedSubtopicId ?: $selectedTopicId ?>">
                    <input type="hidden" name="resource_type" value="link">
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Link URL *</label>
                        <input type="url" name="url" class="form-control" placeholder="https://..." required>
                    </div>
                    <button type="submit" class="btn-submit">💾 Save Link</button>
                </form>
            </div>
        </div>

        <!-- Existing resources list -->
        <div class="resources-list">
            <h3><i class="fas fa-list-ul"></i> Existing Resources (<?= count($resources) ?>)</h3>
            <?php if (empty($resources)): ?>
                <div class="empty"><i class="fas fa-folder-open"></i><p>No resources yet. Use the forms above to add notes, videos, or links.</p></div>
            <?php else: ?>
                <?php foreach ($resources as $res): ?>
                    <div class="resource-item">
                        <div class="res-icon">
                            <?php if ($res['resource_type'] == 'video'): ?>
                                <i class="fas fa-video"></i>
                            <?php elseif ($res['resource_type'] == 'link'): ?>
                                <i class="fas fa-link"></i>
                            <?php else: ?>
                                <i class="fas fa-sticky-note"></i>
                            <?php endif; ?>
                        </div>
                        <div class="res-content">
                            <div class="res-title"><?= htmlspecialchars($res['title']) ?></div>
                            <?php if ($res['resource_type'] == 'note' && !empty($res['content'])): ?>
                                <div class="res-preview"><?= nl2br(htmlspecialchars(substr($res['content'], 0, 200))) ?>…</div>
                            <?php elseif ($res['resource_type'] == 'video' && !empty($res['url'])): ?>
                                <div class="res-meta">
                                    <a href="<?= htmlspecialchars($res['url']) ?>" target="_blank" class="resource-link">🎬 Watch video</a>
                                    <?php if ($res['duration_minutes']): ?> • <?= $res['duration_minutes'] ?> min<?php endif; ?>
                                </div>
                            <?php elseif ($res['resource_type'] == 'link' && !empty($res['url'])): ?>
                                <div class="res-meta"><a href="<?= htmlspecialchars($res['url']) ?>" target="_blank" class="resource-link">🔗 Open link</a></div>
                            <?php endif; ?>
                            <div class="res-meta">
                                <span><i class="far fa-calendar-alt"></i> <?= date('M d, Y', strtotime($res['created_at'])) ?></span>
                                <span><i class="fas fa-sort-numeric-down"></i> Order <?= $res['display_order'] ?></span>
                            </div>
                        </div>
                        <div class="res-actions">
                            <button class="btn-edit" onclick='editResource(<?= json_encode($res) ?>)'><i class="fas fa-edit"></i> Edit</button>
                            <form method="post" onsubmit="return confirm('Delete this resource?')">
                                <input type="hidden" name="action" value="delete_resource">
                                <input type="hidden" name="resource_id" value="<?= $res['resource_id'] ?>">
                                <input type="hidden" name="target_type" value="<?= $res['target_type'] ?>">
                                <button type="submit" class="btn-delete"><i class="fas fa-trash-alt"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php elseif ($selectedModuleId > 0): ?>
        <div class="info-card">Continue selecting LO → IC → Topic → Subtopic using the dropdowns above.</div>
    <?php else: ?>
        <div class="info-card">Select a module from the dropdown above.</div>
    <?php endif; ?>
</div>

<!-- Edit Modal (unchanged) -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h2>✏️ Edit Resource</h2><span class="close" onclick="closeModal()">&times;</span></div>
        <form method="post" id="editForm">
            <input type="hidden" name="action" value="edit_resource">
            <input type="hidden" name="resource_id" id="edit_id">
            <input type="hidden" name="target_type" id="edit_target_type">
            <input type="hidden" name="resource_type" id="edit_resource_type">
            <div class="form-group"><label>Title</label><input type="text" name="title" id="edit_title" class="form-control" required></div>
            <div id="edit_content_group" class="form-group"><label>Content</label><textarea name="content" id="edit_content" rows="6" class="form-control"></textarea></div>
            <div id="edit_url_group" class="form-group" style="display:none"><label>URL</label><input type="url" name="url" id="edit_url" class="form-control"></div>
            <div id="edit_duration_group" class="form-group" style="display:none"><label>Duration (minutes)</label><input type="number" name="duration" id="edit_duration" class="form-control"></div>
            <div class="form-actions"><button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button><button type="submit" class="btn-save">Save changes</button></div>
        </form>
    </div>
</div>

<style>
/* Styles – same as before, keep everything */
.resources-manager{max-width:1200px;margin:0 auto;padding:30px 24px;}
.page-header{display:flex;justify-content:space-between;margin-bottom:20px;}
.btn-back{background:#667eea;color:white;padding:8px 20px;border-radius:30px;text-decoration:none;}
.alert{padding:12px;border-radius:12px;margin-bottom:20px;}
.success{background:#e8f5e9;color:#2e7d32;border-left:4px solid #4caf50;}
.error{background:#ffebee;color:#c62828;border-left:4px solid #f44336;}
.nav-path{background:#e8f0fe;padding:12px 20px;border-radius:12px;margin-bottom:20px;}
.selector-card{background:white;border-radius:20px;padding:20px;margin-bottom:30px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}
.selector-flex{display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px;}
.sel-item{min-width:110px;flex:1;}
.sel-num{background:#667eea;color:white;display:inline-block;width:22px;height:22px;border-radius:50%;text-align:center;line-height:22px;font-size:12px;margin-right:6px;}
.sel-input{width:100%;padding:6px;border-radius:8px;border:1px solid #ccc;margin-top:5px;}
.sel-arrow{color:#aaa;font-size:18px;margin-bottom:10px;}
.debug-box{background:#e8f0fe;padding:10px;margin-bottom:20px;border-radius:8px;font-size:13px;text-align:center;}
.add-card{background:white;border-radius:20px;padding:24px;margin-bottom:30px;}
.form-header{padding:8px 20px;cursor:pointer;font-weight:500;}
.form-header.active{color:#667eea;border-bottom:2px solid #667eea;}
.resource-form{display:block;margin-top:20px;}
.form-group{margin-bottom:15px;}
.form-group label{font-weight:500;display:block;margin-bottom:6px;}
.form-control{width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;}
.btn-submit{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;padding:10px 24px;border-radius:30px;cursor:pointer;}
.resources-list{background:white;border-radius:20px;padding:20px;margin-top:20px;}
.resource-item{display:flex;gap:15px;padding:16px;background:#f9fafc;border-radius:16px;margin-bottom:12px;}
.res-icon{font-size:28px;min-width:50px;text-align:center;color:#667eea;}
.res-content{flex:1;}
.res-title{font-weight:600;margin-bottom:6px;}
.res-preview{color:#555;font-size:13px;margin-bottom:6px;background:#f0f2f5;padding:8px;border-radius:8px;}
.res-meta{font-size:12px;color:#888;margin-top:6px;display:flex;gap:16px;}
.resource-link{color:#667eea;text-decoration:none;}
.res-actions{display:flex;gap:8px;}
.btn-edit,.btn-delete{padding:5px 12px;border:none;border-radius:30px;cursor:pointer;font-size:12px;}
.btn-edit{background:#ff9800;color:white;}
.btn-delete{background:#f44336;color:white;}
.empty,.info-card{text-align:center;padding:40px;color:#888;background:#f8f9fa;border-radius:20px;}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:1000;}
.modal-content{background:white;border-radius:24px;width:550px;max-width:90%;padding:25px;}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.close{font-size:28px;cursor:pointer;}
.form-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px;}
.btn-cancel{background:#aaa;color:white;padding:6px 20px;border-radius:30px;}
.btn-save{background:#4caf50;color:white;padding:6px 24px;border-radius:30px;}
@media (max-width:800px){.selector-flex{flex-direction:column;align-items:stretch;}.sel-arrow{display:none;}}
</style>

<script>
// Simple tab switching
document.querySelectorAll('.form-header').forEach(header => {
    header.addEventListener('click', function() {
        let type = this.getAttribute('data-type');
        document.querySelectorAll('.form-header').forEach(h => h.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.resource-form').forEach(form => form.style.display = 'none');
        document.getElementById(type + '-form').style.display = 'block';
    });
});

function editResource(res) {
    document.getElementById('edit_id').value = res.resource_id;
    document.getElementById('edit_target_type').value = res.target_type;
    document.getElementById('edit_resource_type').value = res.resource_type;
    document.getElementById('edit_title').value = res.title;
    let contentGroup = document.getElementById('edit_content_group');
    let urlGroup = document.getElementById('edit_url_group');
    let durationGroup = document.getElementById('edit_duration_group');
    if (res.resource_type === 'note') {
        contentGroup.style.display = 'block';
        urlGroup.style.display = 'none';
        durationGroup.style.display = 'none';
        document.getElementById('edit_content').value = res.content || '';
    } else if (res.resource_type === 'video') {
        contentGroup.style.display = 'none';
        urlGroup.style.display = 'block';
        durationGroup.style.display = 'block';
        document.getElementById('edit_url').value = res.url || '';
        document.getElementById('edit_duration').value = res.duration_minutes || '';
    } else {
        contentGroup.style.display = 'none';
        urlGroup.style.display = 'block';
        durationGroup.style.display = 'none';
        document.getElementById('edit_url').value = res.url || '';
    }
    document.getElementById('editModal').style.display = 'flex';
}
function closeModal() { document.getElementById('editModal').style.display = 'none'; }
</script>

<?php include_once '../includes/templates/footer.php'; ?>