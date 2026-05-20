<?php
/**
 * Curriculum Editor - Review, edit, and manage curriculum structure.
 * Path: /teacher/curriculum-editor.php
 * 
 * This page allows teachers to view and edit the hierarchical curriculum.
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$moduleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$message = '';
$error = '';

// Helper function to check if a column exists
function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->rowCount() > 0;
}

// Permission check for teachers when a specific module is selected
if ($moduleId > 0 && $_SESSION['user_role'] !== 'admin') {
    $checkPerm = $pdo->prepare("SELECT COUNT(*) FROM teacher_modules WHERE teacher_id = ? AND module_id = ?");
    $checkPerm->execute([$_SESSION['user_id'], $moduleId]);
    if ($checkPerm->fetchColumn() == 0) {
        die("You do not have permission to edit this module. <a href='curriculum-editor.php'>Go back</a>");
    }
}

// Handle AJAX requests for editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    try {
        if ($action === 'edit_outcome') {
            $outcomeId = intval($_POST['outcome_id']);
            $description = sanitize($_POST['description']);
            $stmt = $pdo->prepare("UPDATE learning_outcomes SET description = ? WHERE outcome_id = ?");
            $stmt->execute([$description, $outcomeId]);
            $response = ['success' => true, 'message' => 'Learning Outcome updated'];
            
        } elseif ($action === 'edit_ic') {
            $icId = intval($_POST['ic_id']);
            $title = sanitize($_POST['title']);
            $stmt = $pdo->prepare("UPDATE indicative_contents SET ic_title = ? WHERE ic_id = ?");
            $stmt->execute([$title, $icId]);
            $response = ['success' => true, 'message' => 'Indicative Content updated'];
            
        } elseif ($action === 'edit_topic') {
            $topicId = intval($_POST['topic_id']);
            $title = sanitize($_POST['title']);
            $stmt = $pdo->prepare("UPDATE topics SET topic_title = ? WHERE topic_id = ?");
            $stmt->execute([$title, $topicId]);
            $response = ['success' => true, 'message' => 'Topic updated'];
            
        } elseif ($action === 'edit_subtopic') {
            $subtopicId = intval($_POST['subtopic_id']);
            $title = sanitize($_POST['title']);
            $stmt = $pdo->prepare("UPDATE subtopics SET subtopic_title = ? WHERE subtopic_id = ?");
            $stmt->execute([$title, $subtopicId]);
            $response = ['success' => true, 'message' => 'Subtopic updated'];
            
        } elseif ($action === 'add_ic') {
            $outcomeId = intval($_POST['outcome_id']);
            $title = sanitize($_POST['title']);
            $stmt = $pdo->prepare("SELECT MAX(ic_order) as max_order FROM indicative_contents WHERE outcome_id = ?");
            $stmt->execute([$outcomeId]);
            $maxOrder = $stmt->fetch()['max_order'] ?? 0;
            $stmt = $pdo->prepare("INSERT INTO indicative_contents (outcome_id, ic_title, ic_order, module_id) VALUES (?, ?, ?, (SELECT module_id FROM learning_outcomes WHERE outcome_id = ?))");
            $stmt->execute([$outcomeId, $title, $maxOrder + 1, $outcomeId]);
            $response = ['success' => true, 'message' => 'Indicative Content added', 'ic_id' => $pdo->lastInsertId()];
            
        } elseif ($action === 'add_topic') {
            $icId = intval($_POST['ic_id']);
            $title = sanitize($_POST['title']);
            $stmt = $pdo->prepare("SELECT MAX(topic_order) as max_order FROM topics WHERE ic_id = ?");
            $stmt->execute([$icId]);
            $maxOrder = $stmt->fetch()['max_order'] ?? 0;
            $stmt = $pdo->prepare("INSERT INTO topics (ic_id, topic_title, topic_order) VALUES (?, ?, ?)");
            $stmt->execute([$icId, $title, $maxOrder + 1]);
            $response = ['success' => true, 'message' => 'Topic added'];
            
        } elseif ($action === 'add_subtopic') {
            $topicId = intval($_POST['topic_id']);
            $title = sanitize($_POST['title']);
            $stmt = $pdo->prepare("SELECT MAX(subtopic_order) as max_order FROM subtopics WHERE topic_id = ?");
            $stmt->execute([$topicId]);
            $maxOrder = $stmt->fetch()['max_order'] ?? 0;
            $stmt = $pdo->prepare("INSERT INTO subtopics (topic_id, subtopic_title, subtopic_order) VALUES (?, ?, ?)");
            $stmt->execute([$topicId, $title, $maxOrder + 1]);
            $response = ['success' => true, 'message' => 'Subtopic added'];
            
        } elseif ($action === 'delete_ic') {
            $icId = intval($_POST['ic_id']);
            $stmt = $pdo->prepare("DELETE FROM indicative_contents WHERE ic_id = ?");
            $stmt->execute([$icId]);
            $response = ['success' => true, 'message' => 'Indicative Content deleted'];
            
        } elseif ($action === 'delete_topic') {
            $topicId = intval($_POST['topic_id']);
            $stmt = $pdo->prepare("DELETE FROM topics WHERE topic_id = ?");
            $stmt->execute([$topicId]);
            $response = ['success' => true, 'message' => 'Topic deleted'];
            
        } elseif ($action === 'delete_subtopic') {
            $subtopicId = intval($_POST['subtopic_id']);
            $stmt = $pdo->prepare("DELETE FROM subtopics WHERE subtopic_id = ?");
            $stmt->execute([$subtopicId]);
            $response = ['success' => true, 'message' => 'Subtopic deleted'];
            
        } elseif ($action === 'publish_module') {
            // Only admin can publish? Allow teachers to publish their own? Here we allow both.
            $stmt = $pdo->prepare("UPDATE modules SET status = 'published' WHERE module_id = ?");
            $stmt->execute([$moduleId]);
            $response = ['success' => true, 'message' => 'Module published successfully!'];
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    echo json_encode($response);
    exit();
}

// If no module selected, show list of modules accessible to this user
if ($moduleId <= 0) {
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];
    
    if ($role === 'admin') {
        $stmt = $pdo->query("SELECT m.module_id, m.module_code, m.module_name, m.status, COUNT(lo.outcome_id) as outcomes_count FROM modules m LEFT JOIN learning_outcomes lo ON m.module_id = lo.module_id GROUP BY m.module_id ORDER BY m.module_code");
    } else {
        // Teacher: show modules linked via teacher_modules
        $stmt = $pdo->prepare("SELECT m.module_id, m.module_code, m.module_name, m.status, COUNT(lo.outcome_id) as outcomes_count 
                               FROM modules m 
                               JOIN teacher_modules tm ON m.module_id = tm.module_id 
                               LEFT JOIN learning_outcomes lo ON m.module_id = lo.module_id 
                               WHERE tm.teacher_id = ? 
                               GROUP BY m.module_id 
                               ORDER BY m.module_code");
        $stmt->execute([$userId]);
    }
    $modules = $stmt->fetchAll();
    
    include_once '../includes/templates/header.php';
    ?>
    <div class="editor-container">
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> Curriculum Editor</h1>
            <p>Select a module to review and edit its curriculum structure</p>
        </div>
        <div class="modules-grid">
            <?php foreach($modules as $module): ?>
            <a href="?module_id=<?php echo $module['module_id']; ?>" class="module-card">
                <div class="module-code"><?php echo htmlspecialchars($module['module_code']); ?></div>
                <div class="module-name"><?php echo htmlspecialchars($module['module_name']); ?></div>
                <div class="module-stats">
                    <span><i class="fas fa-layer-group"></i> <?php echo $module['outcomes_count']; ?> Learning Outcomes</span>
                    <span class="status-badge <?php echo $module['status']; ?>"><?php echo ucfirst($module['status']); ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>How to upload curriculum:</strong> Use the <a href="upload-curriculum.php">Upload Curriculum</a> page to import from structured text.
        </div>
    </div>
    <style>
    .editor-container{max-width:1200px;margin:0 auto;padding:30px 24px;}
    .modules-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:24px;margin-top:20px;}
    .module-card{background:white;border-radius:20px;padding:24px;text-decoration:none;display:block;transition:all 0.3s;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
    .module-card:hover{transform:translateY(-4px);box-shadow:0 10px 25px rgba(0,0,0,0.1);}
    .module-code{font-size:14px;color:#667eea;font-weight:600;margin-bottom:8px;}
    .module-name{font-size:18px;color:#1a1a2e;font-weight:600;margin-bottom:12px;}
    .module-stats{display:flex;justify-content:space-between;align-items:center;font-size:13px;color:#666;}
    .status-badge.draft{background:#fff3e0;color:#ff9800;padding:4px 12px;border-radius:20px;}
    .status-badge.published{background:#e8f5e9;color:#4CAF50;padding:4px 12px;border-radius:20px;}
    .info-box{background:#e3f2fd;padding:15px;border-radius:12px;margin-top:30px;color:#1565c0;}
    .info-box a{color:#0d47a1;font-weight:bold;}
    </style>
    <?php
    include_once '../includes/templates/footer.php';
    exit();
}

// Get module info
$stmt = $pdo->prepare("SELECT * FROM modules WHERE module_id = ?");
$stmt->execute([$moduleId]);
$module = $stmt->fetch();

if (!$module) {
    header('Location: /teacher/curriculum-editor.php');
    exit();
}

// Check auto-saved flag
$autoSaved = isset($_GET['auto_saved']) && $_GET['auto_saved'] == 1;

// Get learning outcomes with their structure
$stmt = $pdo->prepare("
    SELECT lo.*, 
           ic.ic_id, ic.ic_title, ic.ic_order,
           t.topic_id, t.topic_title, t.topic_order,
           s.subtopic_id, s.subtopic_title, s.subtopic_order
    FROM learning_outcomes lo
    LEFT JOIN indicative_contents ic ON lo.outcome_id = ic.outcome_id
    LEFT JOIN topics t ON ic.ic_id = t.ic_id
    LEFT JOIN subtopics s ON t.topic_id = s.topic_id
    WHERE lo.module_id = ?
    ORDER BY lo.outcome_number, ic.ic_order, t.topic_order, s.subtopic_order
");
$stmt->execute([$moduleId]);
$rows = $stmt->fetchAll();

// Build nested structure
$outcomes = [];
foreach ($rows as $row) {
    $outcomeId = $row['outcome_id'];
    if (!isset($outcomes[$outcomeId])) {
        $outcomes[$outcomeId] = [
            'id' => $row['outcome_id'],
            'number' => $row['outcome_number'],
            'description' => $row['description'],
            'indicative_contents' => []
        ];
    }
    
    if ($row['ic_id'] && !isset($outcomes[$outcomeId]['indicative_contents'][$row['ic_id']])) {
        $outcomes[$outcomeId]['indicative_contents'][$row['ic_id']] = [
            'id' => $row['ic_id'],
            'title' => $row['ic_title'],
            'order' => $row['ic_order'],
            'topics' => []
        ];
    }
    
    if ($row['topic_id'] && isset($outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]) && !isset($outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]['topics'][$row['topic_id']])) {
        $outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]['topics'][$row['topic_id']] = [
            'id' => $row['topic_id'],
            'title' => $row['topic_title'],
            'order' => $row['topic_order'],
            'subtopics' => []
        ];
    }
    
    if ($row['subtopic_id'] && isset($outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]['topics'][$row['topic_id']]) && !isset($outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]['topics'][$row['topic_id']]['subtopics'][$row['subtopic_id']])) {
        $outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]['topics'][$row['topic_id']]['subtopics'][$row['subtopic_id']] = [
            'id' => $row['subtopic_id'],
            'title' => $row['subtopic_title'],
            'order' => $row['subtopic_order']
        ];
    }
}

include_once '../includes/templates/header.php';
?>

<div class="curriculum-editor">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-edit"></i> Edit Curriculum: <?php echo htmlspecialchars($module['module_name']); ?></h1>
            <p class="module-meta"><?php echo $module['module_code']; ?> | Credits: <?php echo $module['credits']; ?> | Level: <?php echo $module['rqf_level']; ?></p>
        </div>
        <div class="header-actions">
            <a href="/teacher/upload-curriculum.php?module_id=<?php echo $moduleId; ?>" class="btn-outline">
                <i class="fas fa-upload"></i> Upload New Curriculum
            </a>
            <button onclick="previewStudentView()" class="btn-preview">
                <i class="fas fa-eye"></i> Preview Student View
            </button>
            <button onclick="publishModule()" class="btn-publish" id="publishBtn">
                <i class="fas fa-check-circle"></i> Publish Module
            </button>
        </div>
    </div>

    <?php if($autoSaved): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> 
            Curriculum has been automatically parsed and saved! Please review the structure below.
        </div>
    <?php endif; ?>

    <?php if(empty($outcomes)): ?>
        <div class="empty-curriculum">
            <i class="fas fa-book-open"></i>
            <h3>No curriculum data found</h3>
            <p>This module does not have any curriculum structure yet.</p>
            <a href="/teacher/upload-curriculum.php?module_id=<?php echo $moduleId; ?>" class="btn-primary">
                <i class="fas fa-upload"></i> Upload Curriculum
            </a>
        </div>
    <?php else: ?>
        <div class="curriculum-structure">
            <?php foreach($outcomes as $outcome): ?>
                <div class="outcome-card" data-outcome-id="<?php echo $outcome['id']; ?>">
                    <div class="outcome-header">
                        <div class="outcome-info">
                            <span class="outcome-number">🎯 Learning Outcome <?php echo $outcome['number']; ?></span>
                        </div>
                        <div class="outcome-actions">
                            <button class="btn-icon edit" onclick="editOutcome(<?php echo $outcome['id']; ?>, '<?php echo addslashes($outcome['description']); ?>')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </div>
                    </div>
                    <p class="outcome-description"><?php echo htmlspecialchars($outcome['description']); ?></p>
                    
                    <?php foreach($outcome['indicative_contents'] as $ic): ?>
                        <div class="ic-card" data-ic-id="<?php echo $ic['id']; ?>">
                            <div class="ic-header">
                                <div class="ic-title">
                                    <i class="fas fa-folder-open"></i>
                                    <?php echo htmlspecialchars($ic['title']); ?>
                                </div>
                                <div class="ic-actions">
                                    <button class="btn-icon edit" onclick="editIC(<?php echo $ic['id']; ?>, '<?php echo addslashes($ic['title']); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon add" onclick="addTopic(<?php echo $ic['id']; ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button class="btn-icon delete" onclick="deleteIC(<?php echo $ic['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <?php foreach($ic['topics'] as $topic): ?>
                                <div class="topic-card" data-topic-id="<?php echo $topic['id']; ?>">
                                    <div class="topic-header">
                                        <div class="topic-title">
                                            <i class="fas fa-book"></i>
                                            <?php echo htmlspecialchars($topic['title']); ?>
                                        </div>
                                        <div class="topic-actions">
                                            <button class="btn-icon edit" onclick="editTopic(<?php echo $topic['id']; ?>, '<?php echo addslashes($topic['title']); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon add" onclick="addSubtopic(<?php echo $topic['id']; ?>)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button class="btn-icon delete" onclick="deleteTopic(<?php echo $topic['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <ul class="subtopic-list">
                                        <?php foreach($topic['subtopics'] as $subtopic): ?>
                                            <li data-subtopic-id="<?php echo $subtopic['id']; ?>">
                                                <span class="checkmark">✓</span>
                                                <span class="subtopic-title"><?php echo htmlspecialchars($subtopic['title']); ?></span>
                                                <div class="subtopic-actions">
                                                    <button class="btn-icon-sm" onclick="editSubtopic(<?php echo $subtopic['id']; ?>, '<?php echo addslashes($subtopic['title']); ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-icon-sm" onclick="deleteSubtopic(<?php echo $subtopic['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                            
                            <button class="btn-add-topic" onclick="addTopic(<?php echo $ic['id']; ?>)">
                                <i class="fas fa-plus"></i> Add Topic
                            </button>
                        </div>
                    <?php endforeach; ?>
                    
                    <button class="btn-add-ic" onclick="addIndicativeContent(<?php echo $outcome['id']; ?>)">
                        <i class="fas fa-plus"></i> Add Indicative Content
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.curriculum-editor{max-width:1200px;margin:0 auto;padding:30px 24px;}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;flex-wrap:wrap;gap:15px;}
.page-header h1{font-size:28px;color:#1a1a2e;margin:0;}
.module-meta{color:#666;margin-top:5px;font-size:14px;}
.header-actions{display:flex;gap:10px;flex-wrap:wrap;}
.btn-outline{border:1px solid #667eea;color:#667eea;background:white;padding:8px 20px;border-radius:30px;text-decoration:none;display:inline-flex;align-items:center;gap:5px;}
.btn-preview{background:#ff8c42;color:white;border:none;padding:8px 20px;border-radius:30px;cursor:pointer;}
.btn-publish{background:#4CAF50;color:white;border:none;padding:8px 20px;border-radius:30px;cursor:pointer;}
.alert-success{background:#e8f5e9;color:#2e7d32;padding:15px 20px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.outcome-card{background:white;border-radius:20px;margin-bottom:30px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
.outcome-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
.outcome-number{font-weight:bold;color:#667eea;font-size:18px;}
.outcome-description{color:#333;margin-bottom:20px;}
.ic-card{background:#f8f9fa;border-radius:16px;margin:15px 0;padding:20px;}
.ic-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;}
.ic-title{font-weight:600;color:#1e3a5f;}
.topic-card{background:white;border-radius:12px;margin:12px 0;padding:16px;border:1px solid #e0e0e0;}
.topic-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.topic-title{font-weight:500;}
.subtopic-list{list-style:none;padding-left:20px;}
.subtopic-list li{margin:8px 0;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.checkmark{color:#4CAF50;}
.subtopic-actions{display:inline-flex;gap:5px;margin-left:auto;}
.btn-icon,.btn-icon-sm{background:none;border:none;cursor:pointer;padding:5px;margin:0 2px;color:#999;border-radius:4px;}
.btn-icon:hover,.btn-icon-sm:hover{background:#f0f0f0;color:#667eea;}
.btn-add-topic,.btn-add-ic{background:none;border:1px dashed #ccc;border-radius:8px;padding:10px;width:100%;cursor:pointer;color:#666;transition:all 0.3s;margin-top:10px;}
.btn-add-topic:hover,.btn-add-ic:hover{border-color:#667eea;color:#667eea;}
.empty-curriculum{text-align:center;padding:60px;background:white;border-radius:20px;}
.empty-curriculum i{font-size:64px;color:#ccc;margin-bottom:20px;}
.btn-primary{display:inline-block;background:#667eea;color:white;padding:12px 30px;border-radius:30px;text-decoration:none;margin-top:15px;}
</style>

<script>
function previewStudentView() {
    window.open('/student/skill-tree.php?module_id=<?php echo $moduleId; ?>', '_blank');
}

function publishModule() {
    if(confirm('Publish this module? Students will be able to see and enroll.')) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: 'action=publish_module'
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                alert('Module published successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

function editOutcome(id, desc) {
    let newDesc = prompt('Edit Learning Outcome Description:', desc);
    if(newDesc) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: `action=edit_outcome&outcome_id=${id}&description=${encodeURIComponent(newDesc)}`
        }).then(() => location.reload());
    }
}

function editIC(id, title) {
    let newTitle = prompt('Edit Indicative Content:', title);
    if(newTitle) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: `action=edit_ic&ic_id=${id}&title=${encodeURIComponent(newTitle)}`
        }).then(() => location.reload());
    }
}

function editTopic(id, title) {
    let newTitle = prompt('Edit Topic:', title);
    if(newTitle) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: `action=edit_topic&topic_id=${id}&title=${encodeURIComponent(newTitle)}`
        }).then(() => location.reload());
    }
}

function editSubtopic(id, title) {
    let newTitle = prompt('Edit Subtopic:', title);
    if(newTitle) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: `action=edit_subtopic&subtopic_id=${id}&title=${encodeURIComponent(newTitle)}`
        }).then(() => location.reload());
    }
}

function addTopic(icId) {
    let title = prompt('Enter Topic Title:');
    if(title) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: `action=add_topic&ic_id=${icId}&title=${encodeURIComponent(title)}`
        }).then(() => location.reload());
    }
}

function addSubtopic(topicId) {
    let title = prompt('Enter Subtopic Title:');
    if(title) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: `action=add_subtopic&topic_id=${topicId}&title=${encodeURIComponent(title)}`
        }).then(() => location.reload());
    }
}

function addIndicativeContent(outcomeId) {
    let title = prompt('Enter Indicative Content Title:');
    if(title) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: `action=add_ic&outcome_id=${outcomeId}&title=${encodeURIComponent(title)}`
        }).then(() => location.reload());
    }
}

function deleteIC(icId) {
    if(confirm('Delete this Indicative Content and all its topics?')) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: `action=delete_ic&ic_id=${icId}`
        }).then(() => location.reload());
    }
}

function deleteTopic(topicId) {
    if(confirm('Delete this Topic and all its subtopics?')) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: `action=delete_topic&topic_id=${topicId}`
        }).then(() => location.reload());
    }
}

function deleteSubtopic(subtopicId) {
    if(confirm('Delete this Subtopic?')) {
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: `action=delete_subtopic&subtopic_id=${subtopicId}`
        }).then(() => location.reload());
    }
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>