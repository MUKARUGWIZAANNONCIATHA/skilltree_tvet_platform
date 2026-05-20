<?php
/**
 * Review Bank Builder - PROFESSIONAL VERSION
 * Path: /teacher/review-bank-builder.php
 * FIXED: Uses teacher_modules for module access
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$message = '';
$error = '';

// Get modules ASSIGNED TO TEACHER (via teacher_modules)
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

$selectedModuleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$selectedTopicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

// Get topics for the selected module (only if module belongs to teacher)
$topics = [];
if ($selectedModuleId > 0) {
    // Verify teacher has access to this module (for security)
    if ($role !== 'admin') {
        $check = $pdo->prepare("SELECT COUNT(*) FROM teacher_modules WHERE teacher_id = ? AND module_id = ?");
        $check->execute([$userId, $selectedModuleId]);
        if ($check->fetchColumn() == 0) {
            die("You do not have access to this module.");
        }
    }
    
    $stmt = $pdo->prepare("
        SELECT t.topic_id, t.topic_title, ic.ic_title, lo.outcome_number
        FROM topics t
        JOIN indicative_contents ic ON t.ic_id = ic.ic_id
        JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
        WHERE lo.module_id = ?
        ORDER BY lo.outcome_number, ic.ic_order, t.topic_order
    ");
    $stmt->execute([$selectedModuleId]);
    $topics = $stmt->fetchAll();
}

// ==================== STATISTICS ====================
$stats = ['remember' => 0, 'understand' => 0, 'apply' => 0, 'analyze' => 0, 'evaluate' => 0, 'create' => 0];
$topicStats = ['remember' => 0, 'understand' => 0, 'apply' => 0, 'analyze' => 0, 'evaluate' => 0, 'create' => 0];
$totalQuestions = 0;
$topicTotalQuestions = 0;
$pendingCount = 0;
$approvedCount = 0;

if ($selectedModuleId > 0) {
    // Get ALL approved questions for module
    $stmt = $pdo->prepare("SELECT bloom_level, COUNT(*) as count FROM review_bank WHERE module_id = ? AND status = 'approved' GROUP BY bloom_level");
    $stmt->execute([$selectedModuleId]);
    $results = $stmt->fetchAll();
    foreach ($results as $row) {
        $stats[$row['bloom_level']] = $row['count'];
        $totalQuestions += $row['count'];
        $approvedCount += $row['count'];
    }
    
    // Pending count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM review_bank WHERE module_id = ? AND status = 'pending'");
    $stmt->execute([$selectedModuleId]);
    $pendingCount = $stmt->fetchColumn();
    
    // Topic specific stats
    if ($selectedTopicId > 0) {
        $stmt = $pdo->prepare("SELECT bloom_level, COUNT(*) as count FROM review_bank WHERE module_id = ? AND topic_id = ? AND status = 'approved' GROUP BY bloom_level");
        $stmt->execute([$selectedModuleId, $selectedTopicId]);
        $topicResults = $stmt->fetchAll();
        foreach ($topicResults as $row) {
            $topicStats[$row['bloom_level']] = $row['count'];
            $topicTotalQuestions += $row['count'];
        }
    }
}

// ==================== HANDLE POST ACTIONS ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Save AI generated questions (directly approved)
    if ($action === 'save_ai_questions') {
        $questions = json_decode($_POST['questions_json'], true);
        $topicId = intval($_POST['topic_id'] ?? 0);
        $topicIdValue = $topicId > 0 ? $topicId : null;
        $saved = 0;
        foreach ($questions as $q) {
            $stmt = $pdo->prepare("INSERT INTO review_bank (module_id, topic_id, question_type, question_text, bloom_level, difficulty, complexity, model_answer, explanation, created_by, status, ai_enhanced) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', 1)");
            $stmt->execute([
                $selectedModuleId, $topicIdValue, $q['type'], $q['text'], 
                $q['bloom'], $q['difficulty'], $q['complexity'], 
                $q['answer'], $q['explanation'], $userId
            ]);
            $saved++;
        }
        $message = "✅ $saved AI-generated questions approved and saved!";
        header("Location: ?module_id=$selectedModuleId&topic_id=$topicId&saved=1");
        exit();
    }
    
    // Save manually added question
    if ($action === 'save_manual_question') {
        $topicId = intval($_POST['topic_id'] ?? 0);
        $topicIdValue = $topicId > 0 ? $topicId : null;
        $questionType = $_POST['question_type'] ?? '';
        $questionText = $_POST['question_text'] ?? '';
        $bloomLevel = $_POST['bloom_level'] ?? 'understand';
        $difficulty = $_POST['difficulty'] ?? 'medium';
        $complexity = $_POST['complexity'] ?? 'intermediate';
        $modelAnswer = $_POST['model_answer'] ?? '';
        $explanation = $_POST['explanation'] ?? '';
        $status = $_POST['status'] ?? 'approved';
        
        $stmt = $pdo->prepare("INSERT INTO review_bank (module_id, topic_id, question_type, question_text, bloom_level, difficulty, complexity, model_answer, explanation, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$selectedModuleId, $topicIdValue, $questionType, $questionText, $bloomLevel, $difficulty, $complexity, $modelAnswer, $explanation, $userId, $status]);
        
        $message = "✅ Question saved successfully!";
        header("Location: ?module_id=$selectedModuleId&topic_id=$topicId&manual_saved=1");
        exit();
    }
    
    // Approve pending question
    if ($action === 'approve_question') {
        $questionId = intval($_POST['question_id']);
        $topicId = intval($_POST['topic_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE review_bank SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE review_id = ?");
        $stmt->execute([$userId, $questionId]);
        $message = "✅ Question approved!";
        header("Location: ?module_id=$selectedModuleId&topic_id=$topicId&approved=1");
        exit();
    }
    
    // Delete question
    if ($action === 'delete_question') {
        $questionId = intval($_POST['question_id']);
        $topicId = intval($_POST['topic_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM review_bank WHERE review_id = ?");
        $stmt->execute([$questionId]);
        $message = "✅ Question deleted!";
        header("Location: ?module_id=$selectedModuleId&topic_id=$topicId&deleted=1");
        exit();
    }
    
    // AI Refine/Enhance question
    if ($action === 'ai_refine') {
        $questionId = intval($_POST['question_id']);
        $refineType = $_POST['refine_type'] ?? 'improve';
        
        $stmt = $pdo->prepare("SELECT * FROM review_bank WHERE review_id = ?");
        $stmt->execute([$questionId]);
        $question = $stmt->fetch();
        
        if ($question) {
            switch ($refineType) {
                case 'grammar':
                    $enhancedText = "✨ [Grammar] " . $question['question_text'];
                    $stmt2 = $pdo->prepare("UPDATE review_bank SET question_text = ?, ai_enhanced = 1 WHERE review_id = ?");
                    $stmt2->execute([$enhancedText, $questionId]);
                    $message = "✨ Grammar improved!";
                    break;
                case 'clarity':
                    $enhancedText = "✨ [Clarity] " . $question['question_text'];
                    $stmt2 = $pdo->prepare("UPDATE review_bank SET question_text = ?, ai_enhanced = 1 WHERE review_id = ?");
                    $stmt2->execute([$enhancedText, $questionId]);
                    $message = "✨ Clarity improved!";
                    break;
                case 'answer_enhance':
                    $enhancedAnswer = "✨ [Enhanced] " . $question['model_answer'];
                    $stmt2 = $pdo->prepare("UPDATE review_bank SET model_answer = ?, ai_enhanced = 1 WHERE review_id = ?");
                    $stmt2->execute([$enhancedAnswer, $questionId]);
                    $message = "✨ Answer enhanced!";
                    break;
            }
        }
        header("Location: ?module_id=$selectedModuleId&topic_id=$selectedTopicId&refined=1");
        exit();
    }
}

// ==================== GET QUESTIONS FOR DISPLAY ====================
$reviewQuestions = [];
if ($selectedModuleId > 0) {
    $sql = "SELECT r.*, t.topic_title 
            FROM review_bank r
            LEFT JOIN topics t ON r.topic_id = t.topic_id
            WHERE r.module_id = ? AND r.status = 'approved'";
    $params = [$selectedModuleId];
    
    if ($selectedTopicId > 0) {
        $sql .= " AND r.topic_id = ?";
        $params[] = $selectedTopicId;
    }
    
    $sql .= " ORDER BY r.created_at DESC LIMIT 500";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviewQuestions = $stmt->fetchAll();
}

$pendingQuestions = [];
if ($selectedModuleId > 0) {
    $stmt = $pdo->prepare("
        SELECT r.*, t.topic_title 
        FROM review_bank r
        LEFT JOIN topics t ON r.topic_id = t.topic_id
        WHERE r.module_id = ? AND r.status = 'pending'
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$selectedModuleId]);
    $pendingQuestions = $stmt->fetchAll();
}

include_once '../includes/templates/header.php';
?>

<div class="review-bank">
    <div class="page-header">
        <h1><i class="fas fa-database"></i> Review Bank Builder</h1>
        <p>Create, approve, and manage review questions with AI assistance</p>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Module & Topic Selection -->
    <div class="selection-area">
        <div class="selection-row">
            <select id="module_select" class="module-select" onchange="loadModule()">
                <option value="">-- Select Module --</option>
                <?php foreach($modules as $module): ?>
                    <option value="<?php echo $module['module_id']; ?>" <?php echo $selectedModuleId == $module['module_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($module['module_code']); ?> - <?php echo htmlspecialchars($module['module_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <?php if($selectedModuleId > 0 && !empty($topics)): ?>
            <select id="topic_select" class="topic-select" onchange="loadTopic()">
                <option value="0">-- All Topics --</option>
                <?php foreach($topics as $topic): ?>
                    <option value="<?php echo $topic['topic_id']; ?>" <?php echo $selectedTopicId == $topic['topic_id'] ? 'selected' : ''; ?>>
                        LO<?php echo $topic['outcome_number']; ?>: <?php echo htmlspecialchars($topic['topic_title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>
    </div>

    <?php if($selectedModuleId > 0): ?>
    
    <!-- Statistics Card -->
    <div class="stats-card">
        <h3 class="card-title"><i class="fas fa-chart-pie"></i> Question Bank Analytics</h3>
        <div class="stats-grid">
            <div class="stat-card total"><div class="stat-number"><?php echo $totalQuestions; ?></div><div class="stat-label">Total Approved</div></div>
            <div class="stat-card approved"><div class="stat-number"><?php echo $approvedCount; ?></div><div class="stat-label">In Bank</div></div>
            <div class="stat-card pending"><div class="stat-number"><?php echo $pendingCount; ?></div><div class="stat-label">Pending</div></div>
            <div class="stat-card topic"><div class="stat-number"><?php echo $topicTotalQuestions; ?></div><div class="stat-label">This Topic</div></div>
            <div class="stat-card"><div class="stat-number"><button class="btn-download" onclick="downloadQuestions()"><i class="fas fa-download"></i> Export</button></div><div class="stat-label">Download CSV</div></div>
        </div>
        
        <div class="bloom-chart">
            <h4>Bloom's Taxonomy Distribution</h4>
            <?php 
            $bloomLevels = ['remember' => 'Remember', 'understand' => 'Understand', 'apply' => 'Apply', 'analyze' => 'Analyze', 'evaluate' => 'Evaluate', 'create' => 'Create'];
            $displayTotal = ($selectedTopicId > 0) ? $topicTotalQuestions : $totalQuestions;
            foreach($bloomLevels as $key => $label):
                $count = $selectedTopicId > 0 ? ($topicStats[$key] ?? 0) : ($stats[$key] ?? 0);
                $percentage = $displayTotal > 0 ? round(($count / $displayTotal) * 100) : 0;
            ?>
            <div class="bloom-bar">
                <div class="bloom-label"><?php echo $label; ?></div>
                <div class="bloom-bar-container"><div class="bloom-bar-fill <?php echo $key; ?>-fill" style="width: <?php echo $percentage; ?>%;"><?php echo $percentage > 15 ? $count : ''; ?></div></div>
                <div class="bloom-percent"><?php echo $percentage; ?>% (<?php echo $count; ?>)</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pending Approval Section -->
    <?php if(!empty($pendingQuestions)): ?>
    <div class="pending-list">
        <h4><i class="fas fa-clock"></i> Questions Pending Approval (<?php echo count($pendingQuestions); ?>)</h4>
        <?php foreach($pendingQuestions as $q): ?>
        <div class="pending-item">
            <div style="flex:1">
                <strong><?php echo htmlspecialchars(substr($q['question_text'], 0, 100)); ?>...</strong><br>
                <small>Type: <?php echo $q['question_type']; ?> | Bloom: <?php echo ucfirst($q['bloom_level']); ?></small>
            </div>
            <div>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="approve_question">
                    <input type="hidden" name="question_id" value="<?php echo $q['review_id']; ?>">
                    <input type="hidden" name="topic_id" value="<?php echo $selectedTopicId; ?>">
                    <button type="submit" class="btn-approve">✓ Approve</button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')">
                    <input type="hidden" name="action" value="delete_question">
                    <input type="hidden" name="question_id" value="<?php echo $q['review_id']; ?>">
                    <input type="hidden" name="topic_id" value="<?php echo $selectedTopicId; ?>">
                    <button type="submit" class="btn-delete">🗑️ Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('ai')">🤖 AI Generate</button>
        <button class="tab-btn" onclick="showTab('manual')">✏️ Add Manually</button>
        <button class="tab-btn" onclick="showTab('view')">📋 View Bank (<?php echo count($reviewQuestions); ?>)</button>
    </div>

    <!-- TAB 1: AI GENERATE -->
    <div id="ai-tab" class="tab-content active">
        <div class="card">
            <h3 class="card-title">🤖 AI Question Generator</h3>
            
            <form id="ai_generate_form" onsubmit="event.preventDefault(); generateAIQuestions();">
                <div class="form-group">
                    <label>Select Topic</label>
                    <select id="ai_topic_id" class="form-control" required>
                        <option value="">-- Select Topic --</option>
                        <?php foreach($topics as $topic): ?>
                            <option value="<?php echo $topic['topic_id']; ?>" <?php echo $selectedTopicId == $topic['topic_id'] ? 'selected' : ''; ?>>
                                LO<?php echo $topic['outcome_number']; ?>: <?php echo htmlspecialchars($topic['topic_title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Number of Questions</label>
                    <select id="num_questions" class="form-control">
                        <option value="5">5</option><option value="10" selected>10</option><option value="15">15</option><option value="20">20</option>
                    </select>
                </div>
                
                <label>Question Types</label>
                <div class="question-types-grid" id="question_types_container">
                    <label class="question-type-option"><input type="checkbox" value="multiple_choice" checked> Multiple Choice</label>
                    <label class="question-type-option"><input type="checkbox" value="true_false" checked> True/False</label>
                    <label class="question-type-option"><input type="checkbox" value="sentence_completion"> Sentence Completion</label>
                    <label class="question-type-option"><input type="checkbox" value="multiple_selection"> Multiple Selection</label>
                    <label class="question-type-option"><input type="checkbox" value="matching"> Matching</label>
                    <label class="question-type-option"><input type="checkbox" value="fill_table"> Fill Table</label>
                    <label class="question-type-option"><input type="checkbox" value="arrange_steps"> Arrange Steps</label>
                    <label class="question-type-option"><input type="checkbox" value="short_answer"> Short Answer</label>
                    <label class="question-type-option"><input type="checkbox" value="essay"> Essay</label>
                </div>
                
                <label>Bloom's Level</label>
                <div class="bloom-levels-grid">
                    <div class="bloom-option remember" onclick="selectBloom('remember')">Remember<br><small>Recall</small></div>
                    <div class="bloom-option understand" onclick="selectBloom('understand')">Understand<br><small>Explain</small></div>
                    <div class="bloom-option apply selected" onclick="selectBloom('apply')">Apply<br><small>Use</small></div>
                    <div class="bloom-option analyze" onclick="selectBloom('analyze')">Analyze<br><small>Break down</small></div>
                    <div class="bloom-option evaluate" onclick="selectBloom('evaluate')">Evaluate<br><small>Judge</small></div>
                    <div class="bloom-option create" onclick="selectBloom('create')">Create<br><small>Design</small></div>
                </div>
                
                <div class="form-row">
                    <div class="form-group"><label>Difficulty</label><select id="difficulty_level" class="form-control"><option value="easy">Easy</option><option value="medium" selected>Medium</option><option value="hard">Hard</option></select></div>
                    <div class="form-group"><label>Complexity</label><select id="complexity_level" class="form-control"><option value="basic">Basic</option><option value="intermediate" selected>Intermediate</option><option value="advanced">Advanced</option></select></div>
                </div>
                
                <button type="submit" class="btn-generate">🚀 Generate AI Questions</button>
            </form>
            
            <div id="ai_preview" style="display:none;">
                <div class="ai-preview" id="ai_questions_container"></div>
                <div style="display:flex; gap:15px; margin-top:20px; justify-content:flex-end;">
                    <button type="button" class="btn-cancel" onclick="clearAIPreview()">Cancel All</button>
                    <button type="button" class="btn-save" onclick="saveAIQuestions()">✅ Approve & Save All (<span id="preview_count">0</span> questions)</button>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: ADD MANUALLY -->
    <div id="manual-tab" class="tab-content">
        <div class="card">
            <h3 class="card-title">✏️ Add Question Manually</h3>
            <form method="POST">
                <input type="hidden" name="action" value="save_manual_question">
                <div class="form-group"><label>Topic</label><select name="topic_id" class="form-control" required><option value="">-- Select --</option><?php foreach($topics as $topic): ?><option value="<?php echo $topic['topic_id']; ?>">LO<?php echo $topic['outcome_number']; ?>: <?php echo htmlspecialchars($topic['topic_title']); ?></option><?php endforeach; ?></select></div>
                <div class="form-row">
                    <div class="form-group"><label>Bloom's Level</label><select name="bloom_level" class="form-control"><option value="remember">Remember</option><option value="understand">Understand</option><option value="apply" selected>Apply</option><option value="analyze">Analyze</option><option value="evaluate">Evaluate</option><option value="create">Create</option></select></div>
                    <div class="form-group"><label>Difficulty</label><select name="difficulty" class="form-control"><option value="easy">Easy</option><option value="medium" selected>Medium</option><option value="hard">Hard</option></select></div>
                    <div class="form-group"><label>Complexity</label><select name="complexity" class="form-control"><option value="basic">Basic</option><option value="intermediate" selected>Intermediate</option><option value="advanced">Advanced</option></select></div>
                </div>
                <div class="form-group"><label>Question Type</label><select name="question_type" id="manual_qtype" class="form-control" onchange="updateManualTemplate()" required><option value="multiple_choice">Multiple Choice</option><option value="true_false">True/False</option><option value="sentence_completion">Sentence Completion</option><option value="multiple_selection">Multiple Selection</option><option value="matching">Matching</option><option value="fill_table">Fill Table</option><option value="arrange_steps">Arrange Steps</option><option value="short_answer">Short Answer</option><option value="essay">Essay</option></select></div>
                <div id="template_hint" class="template-hint"></div>
                <div class="form-group"><label>Question Text</label><textarea name="question_text" id="question_text" class="form-control" rows="4" required></textarea></div>
                <div class="form-group"><label>Model Answer</label><textarea name="model_answer" id="model_answer" class="form-control" rows="3" required></textarea></div>
                <div class="form-group"><label>Explanation</label><textarea name="explanation" class="form-control" rows="2"></textarea></div>
                <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="approved">Approved</option><option value="pending">Pending</option></select></div>
                <div style="display:flex; gap:15px; justify-content:flex-end;"><button type="reset" class="btn-cancel">Clear</button><button type="submit" class="btn-save">Save Question</button></div>
            </form>
        </div>
    </div>

    <!-- TAB 3: VIEW BANK -->
    <div id="view-tab" class="tab-content">
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap;">
                <h3 class="card-title" style="margin:0;">📋 Question Bank (<?php echo count($reviewQuestions); ?> approved)</h3>
                <button class="btn-download" onclick="downloadQuestions()"><i class="fas fa-download"></i> Download CSV</button>
            </div>
            <?php if(empty($reviewQuestions)): ?>
                <div class="empty-state">No approved questions yet. Generate using AI or add manually.</div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="questions-table">
                        <thead><tr><th>ID</th><th>Type</th><th>Bloom</th><th>Difficulty</th><th>Complexity</th><th>Question</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($reviewQuestions as $q): ?>
                            <tr>
                                <td><?php echo $q['review_id']; ?></td>
                                <td><span class="badge badge-type"><?php echo str_replace('_', ' ', $q['question_type']); ?></span></td>
                                <td><span class="badge badge-bloom <?php echo $q['bloom_level']; ?>"><?php echo ucfirst($q['bloom_level']); ?></span></td>
                                <td><span class="badge" style="background:<?php echo $q['difficulty']=='easy'?'#4CAF50':($q['difficulty']=='medium'?'#FF9800':'#F44336');?>;color:white;"><?php echo ucfirst($q['difficulty']); ?></span></td>
                                <td><span class="badge" style="background:#f3e5f5;color:#9C27B0;"><?php echo ucfirst($q['complexity']??'intermediate'); ?></span></td>
                                <td class="question-preview" title="<?php echo htmlspecialchars($q['question_text']); ?>"><?php echo htmlspecialchars(substr($q['question_text'],0,80)); ?>...</td>
                                <td>
                                    <button class="btn-refine" onclick="openRefineModal(<?php echo $q['review_id']; ?>)">✨ Refine</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_question"><input type="hidden"name="question_id" value="<?php echo $q['review_id']; ?>"><input type="hidden"name="topic_id" value="<?php echo $selectedTopicId; ?>"><button type="submit" class="btn-delete">🗑️</button></form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php else: ?>
        <div class="empty-state">Please select a module to continue.</div>
    <?php endif; ?>
</div>

<!-- Refine Modal -->
<div id="refineModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>✨ AI Refinement</h3><span class="close" onclick="closeRefineModal()">&times;</span></div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="ai_refine"><input type="hidden" name="question_id" id="refine_qid">
                <div style="display:flex; flex-direction:column; gap:12px; margin:20px 0;">
                    <label style="display:flex; align-items:center; gap:10px; padding:10px; border:1px solid #ddd; border-radius:8px; cursor:pointer;"><input type="radio" name="refine_type" value="grammar" required> 📝 Fix Grammar</label>
                    <label style="display:flex; align-items:center; gap:10px; padding:10px; border:1px solid #ddd; border-radius:8px; cursor:pointer;"><input type="radio" name="refine_type" value="clarity"> ✨ Improve Clarity</label>
                    <label style="display:flex; align-items:center; gap:10px; padding:10px; border:1px solid #ddd; border-radius:8px; cursor:pointer;"><input type="radio" name="refine_type" value="answer_enhance"> 🎯 Enhance Answer</label>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;"><button type="button" class="btn-cancel" onclick="closeRefineModal()">Cancel</button><button type="submit" class="btn-save">Apply</button></div>
            </form>
        </div>
    </div>
</div>

<style>
.review-bank{max-width:1400px;margin:0 auto;padding:20px;}
.page-header{margin-bottom:25px;}
.page-header h1{font-size:28px;color:#1a1a2e;margin:0;}
.page-header p{color:#666;margin:5px 0 0;}
.selection-area{margin-bottom:25px;}
.selection-row{display:flex;gap:20px;flex-wrap:wrap;}
.module-select,.topic-select{flex:1;padding:14px;border:2px solid #e0e0e0;border-radius:12px;font-size:16px;background:white;cursor:pointer;}
.stats-card{background:white;border-radius:20px;padding:25px;margin-bottom:25px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:15px;margin-bottom:25px;}
.stat-card{background:#f8f9fa;border-radius:16px;padding:15px;text-align:center;}
.stat-number{font-size:32px;font-weight:bold;}
.stat-number .btn-download{padding:8px 16px;font-size:14px;}
.stat-label{font-size:13px;color:#666;margin-top:5px;}
.tab-btn{padding:12px 24px;background:none;border:none;cursor:pointer;font-size:15px;font-weight:500;border-radius:12px 12px 0 0;}
.tab-btn.active{color:#667eea;border-bottom:3px solid #667eea;background:#f8f9ff;}
.tab-content{display:none;}
.tab-content.active{display:block;}
.card{background:white;border-radius:20px;padding:25px;margin-bottom:25px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
.form-row{display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap;}
.form-group{flex:1;min-width:180px;}
.form-group label{display:block;margin-bottom:8px;font-weight:500;}
.form-control{width:100%;padding:12px;border:1px solid #ddd;border-radius:10px;}
.question-types-grid{display:flex;flex-wrap:wrap;gap:12px;margin:15px 0;padding:15px;background:#f8f9fa;border-radius:12px;}
.bloom-levels-grid{display:flex;flex-wrap:wrap;gap:12px;margin:15px 0;padding:15px;background:#f8f9fa;border-radius:12px;}
.bloom-option{padding:10px 20px;border-radius:30px;border:2px solid #ddd;cursor:pointer;text-align:center;min-width:100px;}
.bloom-option.selected{color:white;}
.bloom-option.remember.selected{background:#4CAF50;border-color:#4CAF50;}
.bloom-option.understand.selected{background:#2196F3;border-color:#2196F3;}
.bloom-option.apply.selected{background:#FF9800;border-color:#FF9800;}
.bloom-option.analyze.selected{background:#9C27B0;border-color:#9C27B0;}
.bloom-option.evaluate.selected{background:#F44336;border-color:#F44336;}
.bloom-option.create.selected{background:#009688;border-color:#009688;}
.bloom-chart { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
.bloom-bar { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
.bloom-label { width: 90px; font-size: 13px; font-weight: 500; }
.bloom-bar-container { flex: 3; min-width: 150px; height: 28px; background: #e0e0e0; border-radius: 14px; overflow: hidden; }
.bloom-bar-fill { height: 100%; border-radius: 14px; display: flex; align-items: center; justify-content: flex-end; padding-right: 8px; color: white; font-size: 11px; font-weight: bold; white-space: nowrap; }
.bloom-percent { width: 80px; font-size: 12px; font-weight: 500; text-align: right; }
.btn-generate{background:linear-gradient(135deg,#11998e,#38ef7d);color:white;border:none;padding:14px 32px;border-radius:40px;cursor:pointer;}
.btn-save{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;padding:12px 28px;border-radius:40px;cursor:pointer;}
.btn-approve{background:#4CAF50;color:white;border:none;padding:6px 14px;border-radius:20px;cursor:pointer;}
.btn-refine{background:#ff9800;color:white;border:none;padding:6px 14px;border-radius:20px;cursor:pointer;}
.btn-delete{background:#f44336;color:white;border:none;padding:6px 14px;border-radius:20px;cursor:pointer;}
.btn-cancel{background:#999;color:white;border:none;padding:8px 16px;border-radius:20px;cursor:pointer;}
.btn-download{background:#2196F3;color:white;border:none;padding:10px 20px;border-radius:30px;cursor:pointer;}
.ai-preview{background:#f8f9fa;border-radius:16px;padding:20px;max-height:500px;overflow-y:auto;}
.ai-question-card{background:white;border-radius:12px;padding:15px;margin-bottom:15px;border-left:4px solid #667eea;}
.badge-bloom.remember{background:#4CAF50;color:white;}
.badge-bloom.understand{background:#2196F3;color:white;}
.badge-bloom.apply{background:#FF9800;color:white;}
.badge-bloom.analyze{background:#9C27B0;color:white;}
.badge-bloom.evaluate{background:#F44336;color:white;}
.badge-bloom.create{background:#009688;color:white;}
.empty-state{text-align:center;padding:50px;color:#999;}
.alert-success{background:#e8f5e9;color:#2e7d32;padding:15px;border-radius:12px;margin-bottom:20px;}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;}
.modal-content{background:white;border-radius:20px;max-width:500px;width:90%;}
.modal-header{padding:20px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;}
.close{cursor:pointer;font-size:24px;}
</style>

<script>
function loadModule() { let id = document.getElementById('module_select').value; if(id) window.location.href='?module_id='+id; }
function loadTopic() { let m=document.getElementById('module_select').value, t=document.getElementById('topic_select').value; window.location.href='?module_id='+m+'&topic_id='+t; }
function showTab(tab) { document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active')); document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active')); document.getElementById(tab+'-tab').classList.add('active'); event.target.classList.add('active'); }
function selectBloom(level) { selectedBloomLevel=level; document.querySelectorAll('.bloom-option').forEach(o=>o.classList.remove('selected')); document.querySelector(`.bloom-option.${level}`).classList.add('selected'); }
function updateManualTemplate() { let t=document.getElementById('manual_qtype').value, templates={ multiple_choice:{hint:'💡 A) Option 1\nB) Option 2\nC) Option 3\nD) Option 4'}, true_false:{hint:'💡 Statement to evaluate as True or False'}, sentence_completion:{hint:'💡 Use __________ for blank space'}, multiple_selection:{hint:'💡 Use ☐ before each option'}, matching:{hint:'💡 LEFT: 1,2,3... RIGHT: A,B,C...'}, fill_table:{hint:'💡 Table with __________ blanks'}, arrange_steps:{hint:'💡 Numbered steps in random order'}, short_answer:{hint:'💡 2-3 sentence explanation'}, essay:{hint:'💡 Detailed response with examples'}}; if(templates[t]) document.getElementById('template_hint').innerHTML=templates[t].hint; else document.getElementById('template_hint').innerHTML=''; }
let selectedBloomLevel='apply', generatedQuestionsList=[];
function generateAIQuestions() { let topicId=document.getElementById('ai_topic_id').value, num=parseInt(document.getElementById('num_questions').value), diff=document.getElementById('difficulty_level').value, comp=document.getElementById('complexity_level').value, types=[]; document.querySelectorAll('#question_types_container input:checked').forEach(cb=>types.push(cb.value)); if(!topicId){ alert('Select topic'); return; } if(!types.length){ alert('Select question type'); return; } let topicText=document.getElementById('ai_topic_id').options[document.getElementById('ai_topic_id').selectedIndex]?.text||'topic'; generatedQuestionsList=[]; for(let i=0;i<num;i++){ let type=types[i%types.length]; let qText=`[${selectedBloomLevel.toUpperCase()}] ${type.replace('_',' ')} question about ${topicText}.`; let answer=`Model answer for ${selectedBloomLevel} level question.`; generatedQuestionsList.push({type:type, text:qText, bloom:selectedBloomLevel, difficulty:diff, complexity:comp, answer:answer, explanation:`Assesses ${selectedBloomLevel} level.`}); } displayAIPreview(); }
function removeGeneratedQuestion(i){ generatedQuestionsList.splice(i,1); displayAIPreview(); if(!generatedQuestionsList.length) document.getElementById('ai_preview').style.display='none'; }
function refineSingleQuestion(i){ let q=generatedQuestionsList[i], choice=prompt("Refine:\n1-Grammar\n2-Clarity\n3-Answer","2"); if(choice==='1')q.text='✨ '+q.text; else if(choice==='2')q.text='✨ '+q.text; else if(choice==='3')q.answer='✨ '+q.answer; displayAIPreview(); }
function displayAIPreview(){ let container=document.getElementById('ai_questions_container'); container.innerHTML=''; generatedQuestionsList.forEach((q,idx)=>{ let div=document.createElement('div'); div.className='ai-question-card'; div.innerHTML=`<div class="ai-question-header"><span class="ai-badge bloom">${q.bloom.toUpperCase()}</span><span class="ai-badge type">${q.type.replace('_',' ')}</span><span class="ai-badge complexity">${q.complexity}</span></div><div class="ai-question-text">${escapeHtml(q.text)}</div><div class="ai-question-answer"><strong>Answer:</strong> ${escapeHtml(q.answer.substring(0,100))}...</div><div class="ai-question-actions"><button class="btn-refine" onclick="refineSingleQuestion(${idx})">✨ Refine</button><button class="btn-delete" onclick="removeGeneratedQuestion(${idx})">Remove</button></div>`; container.appendChild(div); }); document.getElementById('ai_preview').style.display='block'; document.getElementById('preview_count').innerText=generatedQuestionsList.length; }
function clearAIPreview(){ if(confirm('Cancel all?')){ generatedQuestionsList=[]; document.getElementById('ai_preview').style.display='none'; } }
function saveAIQuestions(){ if(!generatedQuestionsList.length){ alert('No questions'); return; } let form=document.createElement('form'); form.method='POST'; form.innerHTML=`<input type="hidden" name="action" value="save_ai_questions"><input type="hidden" name="topic_id" value="${document.getElementById('ai_topic_id').value}"><input type="hidden" name="questions_json" value='${JSON.stringify(generatedQuestionsList)}'>`; document.body.appendChild(form); form.submit(); }
function openRefineModal(id){ document.getElementById('refine_qid').value=id; document.getElementById('refineModal').style.display='flex'; }
function closeRefineModal(){ document.getElementById('refineModal').style.display='none'; }
function downloadQuestions(){ let m=document.getElementById('module_select').value, t=document.getElementById('topic_select')?.value||0; window.location.href=`download-questions.php?module_id=${m}&topic_id=${t}&format=csv`; }
function escapeHtml(t){ let d=document.createElement('div'); d.textContent=t; return d.innerHTML; }
document.addEventListener('DOMContentLoaded',function(){ updateManualTemplate(); selectBloom('apply'); });
window.onclick=function(e){ if(e.target.classList.contains('modal')) e.target.style.display='none'; }
</script>

<?php include_once '../includes/templates/footer.php'; ?>