<?php
/**
 * Assessment Builder - Fully Dynamic (No AI, Professional Styling)
 * Path: /teacher/assessment-builder.php
 * Features: dynamic question types, save to JSON, student preview
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);
require_once '../config/database.php';
require_once '../includes/functions/common.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$message = '';
$error = '';

// Ensure lo_assessments has required columns
try {
    $pdo->exec("ALTER TABLE lo_assessments ADD COLUMN IF NOT EXISTS assessment_data_json LONGTEXT NULL");
    $pdo->exec("ALTER TABLE lo_assessments ADD COLUMN IF NOT EXISTS status ENUM('draft','published') DEFAULT 'draft'");
    $pdo->exec("ALTER TABLE lo_assessments ADD COLUMN IF NOT EXISTS created_by INT NULL");
    $pdo->exec("ALTER TABLE lo_assessments ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    $pdo->exec("ALTER TABLE lo_assessments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP");
} catch (PDOException $e) {}

// Get modules (assigned OR created)
if ($role === 'admin') {
    $modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT m.module_id, m.module_code, m.module_name
        FROM modules m
        LEFT JOIN teacher_modules tm ON m.module_id = tm.module_id
        WHERE tm.teacher_id = ? OR m.created_by = ?
        GROUP BY m.module_id
        ORDER BY m.module_code
    ");
    $stmt->execute([$userId, $userId]);
    $modules = $stmt->fetchAll();
}

$selectedModuleId   = (int) ($_GET['module_id'] ?? 0);
$selectedOutcomeId  = (int) ($_GET['outcome_id'] ?? 0);
$selectedAssessmentId = (int) ($_GET['assessment_id'] ?? 0);

// Permission check
if ($selectedModuleId > 0 && $role !== 'admin') {
    $check = $pdo->prepare("SELECT COUNT(*) FROM teacher_modules WHERE teacher_id = ? AND module_id = ?");
    $check->execute([$userId, $selectedModuleId]);
    $assigned = $check->fetchColumn() > 0;
    $checkCreator = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE module_id = ? AND created_by = ?");
    $checkCreator->execute([$selectedModuleId, $userId]);
    $created = $checkCreator->fetchColumn() > 0;
    if (!$assigned && !$created) die("You do not have access to this module.");
}

// Load learning outcomes
$outcomes = [];
if ($selectedModuleId) {
    $stmt = $pdo->prepare("SELECT outcome_id, outcome_number, description FROM learning_outcomes WHERE module_id = ? ORDER BY outcome_number");
    $stmt->execute([$selectedModuleId]);
    $outcomes = $stmt->fetchAll();
}

// Load existing assessment for editing
$editAssessment = null;
$editData = null;
if ($selectedAssessmentId) {
    $stmt = $pdo->prepare("SELECT * FROM lo_assessments WHERE lo_assessment_id = ?");
    $stmt->execute([$selectedAssessmentId]);
    $editAssessment = $stmt->fetch();
    if ($editAssessment && !empty($editAssessment['assessment_data_json'])) {
        $editData = json_decode($editAssessment['assessment_data_json'], true);
    }
}

// Handle save (manual, no AI)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assessment'])) {
    $outcomeId = (int) $_POST['outcome_id'];
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $duration = (int) $_POST['duration'];
    $passScore = (int) $_POST['passing_score'];
    $status = $_POST['status'];
    $sectionA = json_decode($_POST['section_a_json'] ?? '[]', true);
    $sectionB = json_decode($_POST['section_b_json'] ?? '[]', true);
    $sectionC = json_decode($_POST['section_c_json'] ?? '[]', true);

    if (empty($title)) {
        $error = "Assessment title is required.";
    } elseif (empty($sectionA) && empty($sectionB) && empty($sectionC)) {
        $error = "Add at least one question.";
    } else {
        $assessmentData = [
            'title' => $title,
            'description' => $desc,
            'duration_minutes' => $duration,
            'passing_score' => $passScore,
            'section_a' => $sectionA,
            'section_b' => $sectionB,
            'section_c' => $sectionC
        ];
        if ($selectedAssessmentId) {
            $stmt = $pdo->prepare("UPDATE lo_assessments SET title=?, description=?, time_limit_minutes=?, passing_score=?, assessment_data_json=?, status=?, updated_at=NOW() WHERE lo_assessment_id=?");
            $stmt->execute([$title, $desc, $duration, $passScore, json_encode($assessmentData), $status, $selectedAssessmentId]);
            $message = "Assessment updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO lo_assessments (outcome_id, title, description, time_limit_minutes, passing_score, assessment_data_json, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$outcomeId, $title, $desc, $duration, $passScore, json_encode($assessmentData), $status, $userId]);
            $message = "Assessment created successfully!";
        }
        header("Location: assessment-builder.php?module_id=$selectedModuleId&outcome_id=$outcomeId&msg=" . urlencode($message));
        exit;
    }
}

// List existing assessments
$assessments = [];
if ($selectedOutcomeId) {
    $stmt = $pdo->prepare("SELECT * FROM lo_assessments WHERE outcome_id = ? ORDER BY created_at DESC");
    $stmt->execute([$selectedOutcomeId]);
    $assessments = $stmt->fetchAll();
}

include_once '../includes/templates/header.php';
?>

<div class="assessment-builder">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-list"></i> Assessment Builder</h1>
        <p>Create structured assessments with dynamic question types (MC, Matching, Fill Table, etc.)</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Selection row -->
    <div class="selection-row">
        <div class="form-group">
            <label><i class="fas fa-book"></i> Module</label>
            <select id="module_sel" class="form-control" onchange="loadOutcomes()">
                <option value="">-- Select Module --</option>
                <?php foreach ($modules as $mod): ?>
                    <option value="<?= $mod['module_id'] ?>" <?= $selectedModuleId == $mod['module_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mod['module_code']) ?> - <?= htmlspecialchars($mod['module_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label><i class="fas fa-bullseye"></i> Learning Outcome</label>
            <select id="outcome_sel" class="form-control" onchange="loadAssessments()" <?= empty($outcomes) ? 'disabled' : '' ?>>
                <option value="">-- Select LO --</option>
                <?php foreach ($outcomes as $lo): ?>
                    <option value="<?= $lo['outcome_id'] ?>" <?= $selectedOutcomeId == $lo['outcome_id'] ? 'selected' : '' ?>>
                        LO<?= $lo['outcome_number'] ?>: <?= htmlspecialchars(substr($lo['description'], 0, 80)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($selectedOutcomeId): ?>
        <!-- Existing assessments card -->
        <div class="existing-card">
            <div class="card-header">
                <h3><i class="fas fa-history"></i> Existing Assessments</h3>
                <a href="?module_id=<?= $selectedModuleId ?>&outcome_id=<?= $selectedOutcomeId ?>" class="btn-new"><i class="fas fa-plus"></i> New Assessment</a>
            </div>
            <?php if (empty($assessments)): ?>
                <div class="empty-state">No assessments yet. Create your first one above.</div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="assessments-table">
                        <thead>
                            <tr><th>Title</th><th>Passing %</th><th>Duration</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assessments as $ass): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ass['title']) ?></td>
                                    <td><?= $ass['passing_score'] ?>%</td
                                    <td><?= $ass['time_limit_minutes'] ?> min</td
                                    <td><span class="status-badge <?= $ass['status'] ?>"><?= ucfirst($ass['status']) ?></span></td>
                                    <td>
                                        <a href="?module_id=<?= $selectedModuleId ?>&outcome_id=<?= $selectedOutcomeId ?>&assessment_id=<?= $ass['lo_assessment_id'] ?>" class="btn-edit">Edit</a>
                                        <a href="javascript:void(0)" onclick="deleteAssessment(<?= $ass['lo_assessment_id'] ?>)" class="btn-delete">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Assessment builder form -->
        <div class="builder-card">
            <h2><i class="fas fa-<?= $editAssessment ? 'edit' : 'plus-circle' ?>"></i> <?= $editAssessment ? 'Edit Assessment' : 'Create New Assessment' ?></h2>
            <form method="post" id="assessmentForm">
                <input type="hidden" name="save_assessment" value="1">
                <input type="hidden" name="outcome_id" value="<?= $selectedOutcomeId ?>">
                <input type="hidden" name="section_a_json" id="sectionAJson">
                <input type="hidden" name="section_b_json" id="sectionBJson">
                <input type="hidden" name="section_c_json" id="sectionCJson">

                <div class="form-grid">
                    <div class="form-group"><label>Assessment Title *</label><input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editAssessment['title'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Duration (minutes)</label><input type="number" name="duration" class="form-control" value="<?= $editAssessment['time_limit_minutes'] ?? 120 ?>"></div>
                    <div class="form-group"><label>Passing Score (%)</label><input type="number" name="passing_score" class="form-control" value="<?= $editAssessment['passing_score'] ?? 70 ?>"></div>
                    <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="draft">Draft</option><option value="published" <?= ($editAssessment['status']??'')=='published' ? 'selected' : '' ?>>Published</option></select></div>
                </div>
                <div class="form-group"><label>Instructions / Description</label><textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($editAssessment['description'] ?? '') ?></textarea></div>

                <!-- Section A -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-check-circle"></i> Section A (Compulsory)</h3>
                        <button type="button" class="btn-add" onclick="addQuestion('A')"><i class="fas fa-plus"></i> Add Question</button>
                    </div>
                    <div id="sectionA_container" class="questions-container"></div>
                    <div class="section-info">Total marks: <span class="section-total">0</span> (recommended 55)</div>
                </div>

                <!-- Section B -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-check-circle"></i> Section B (Choose 3 of 5)</h3>
                        <button type="button" class="btn-add" onclick="addQuestion('B')"><i class="fas fa-plus"></i> Add Question</button>
                    </div>
                    <div id="sectionB_container" class="questions-container"></div>
                    <div class="section-info">Total marks: <span class="section-total">0</span> (recommended 30, each 10 marks)</div>
                </div>

                <!-- Section C -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-check-circle"></i> Section C (Choose 1 of 2)</h3>
                        <button type="button" class="btn-add" onclick="addQuestion('C')"><i class="fas fa-plus"></i> Add Question</button>
                    </div>
                    <div id="sectionC_container" class="questions-container"></div>
                    <div class="section-info">Total marks: <span class="section-total">0</span> (recommended 15)</div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-preview" onclick="previewAssessment()"><i class="fas fa-eye"></i> Preview Assessment</button>
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Assessment</button>
                </div>
            </form>
        </div>
    <?php elseif ($selectedModuleId): ?>
        <div class="info-message">Please select a Learning Outcome to continue.</div>
    <?php else: ?>
        <div class="info-message">Select a module to get started.</div>
    <?php endif; ?>
</div>

<style>
/* ==================== PROFESSIONAL STYLING ==================== */
.assessment-builder { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
.page-header { margin-bottom: 25px; }
.page-header h1 { font-size: 28px; color: #1a1a2e; margin: 0; }
.page-header p { color: #666; margin-top: 5px; }

.selection-row { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
.form-group { flex: 1; min-width: 200px; }
.form-group label { font-weight: 600; margin-bottom: 8px; display: block; color: #333; }
.form-control { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 10px; font-size: 14px; transition: 0.2s; }
.form-control:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }

.existing-card, .builder-card { background: white; border-radius: 20px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; }
.card-header h3 { margin: 0; font-size: 18px; }
.btn-new { background: #667eea; color: white; padding: 6px 15px; border-radius: 25px; text-decoration: none; font-size: 13px; }
.btn-new:hover { background: #5a67d8; }

.table-wrapper { overflow-x: auto; }
.assessments-table { width: 100%; border-collapse: collapse; }
.assessments-table th, .assessments-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
.assessments-table th { background: #f8f9fa; font-weight: 600; }
.status-badge.draft { background: #ff9800; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
.status-badge.published { background: #4CAF50; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
.btn-edit, .btn-delete { margin: 0 3px; text-decoration: none; font-size: 12px; padding: 4px 8px; border-radius: 15px; display: inline-block; }
.btn-edit { background: #2196F3; color: white; }
.btn-delete { background: #f44336; color: white; cursor: pointer; border: none; }

.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }

.section-card { background: #f8f9fa; border-radius: 16px; padding: 20px; margin-top: 25px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; }
.section-header h3 { margin: 0; font-size: 18px; color: #1e3a5f; }
.btn-add { background: #4CAF50; color: white; border: none; padding: 6px 15px; border-radius: 25px; cursor: pointer; font-size: 13px; }
.btn-add i { margin-right: 5px; }
.section-info { text-align: right; font-size: 13px; color: #666; margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd; }

.questions-container { margin-bottom: 15px; }
.question-card { background: white; border-radius: 12px; padding: 18px; margin-bottom: 15px; border-left: 4px solid #667eea; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.q-header { display: flex; gap: 12px; align-items: center; margin-bottom: 12px; }
.q-num { font-weight: bold; color: #667eea; font-size: 16px; }
.q-marks { background: #667eea; color: white; padding: 2px 10px; border-radius: 20px; font-size: 11px; }
.btn-remove { background: #f44336; color: white; border: none; padding: 4px 12px; border-radius: 20px; cursor: pointer; font-size: 11px; margin-left: auto; }
.form-row { display: flex; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
.q-type, .q-bloom, .q-marks-val { padding: 8px; border: 1px solid #ddd; border-radius: 8px; background: white; }
.q-text, .q-answer { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 10px; font-family: inherit; resize: vertical; }
.dynamic-fields { margin: 12px 0; padding: 12px; background: #f0f4ff; border-radius: 10px; display: flex; flex-direction: column; gap: 10px; }
.mc-options { display: flex; gap: 10px; flex-wrap: wrap; }
.mc-options input { flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 6px; }

.form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 25px; }
.btn-preview, .btn-save { padding: 10px 25px; border-radius: 30px; border: none; cursor: pointer; font-weight: 600; transition: 0.2s; }
.btn-preview { background: #2196F3; color: white; }
.btn-preview:hover { background: #0b7dda; }
.btn-save { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.btn-save:hover { opacity: 0.9; transform: scale(1.01); }

.alert { padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; }
.alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #4CAF50; }
.alert-error { background: #ffebee; color: #c62828; border-left: 4px solid #f44336; }
.empty-state, .info-message { text-align: center; padding: 40px; background: #f8f9fa; border-radius: 16px; color: #888; }

@media (max-width: 800px) {
    .selection-row { flex-direction: column; }
    .form-grid { grid-template-columns: 1fr; }
    .mc-options { flex-direction: column; }
}
</style>

<script>
function loadOutcomes() {
    let moduleId = document.getElementById('module_sel').value;
    if (moduleId) window.location.href = 'assessment-builder.php?module_id=' + moduleId;
}
function loadAssessments() {
    let moduleId = document.getElementById('module_sel').value;
    let outcomeId = document.getElementById('outcome_sel').value;
    if (outcomeId) window.location.href = 'assessment-builder.php?module_id=' + moduleId + '&outcome_id=' + outcomeId;
}
function deleteAssessment(id) {
    if (confirm('Delete this assessment permanently?')) {
        window.location.href = 'delete-assessment.php?id=' + id;
    }
}

function addQuestion(section) {
    let container = document.getElementById(`section${section}_container`);
    let idx = container.children.length;
    let marks = section === 'A' ? 3 : (section === 'B' ? 10 : 15);
    let bloomOptions = section === 'A' ? 
        `<option value="remember">Remember</option><option value="understand">Understand</option><option value="apply">Apply</option><option value="analyze">Analyze</option><option value="evaluate">Evaluate</option><option value="create">Create</option>` :
        (section === 'B' ? `<option value="analyze">Analyze</option><option value="evaluate">Evaluate</option><option value="create">Create</option>` :
        `<option value="create">Create</option>`);
    let typeOptions = `
        <option value="multiple_choice">Multiple Choice</option>
        <option value="true_false">True/False</option>
        <option value="matching">Matching</option>
        <option value="fill_table">Fill Table</option>
        <option value="arrange_steps">Arrange Steps</option>
        <option value="short_answer">Short Answer</option>
        <option value="essay">Essay</option>
        <option value="case_study">Case Study</option>
    `;
    let div = document.createElement('div');
    div.className = 'question-card';
    div.setAttribute('data-section', section);
    div.innerHTML = `
        <div class="q-header"><span class="q-num">Q${idx+1}</span><span class="q-marks">${marks} marks</span><button type="button" class="btn-remove" onclick="removeQuestion(this)">🗑️</button></div>
        <div class="form-row"><select class="q-type" onchange="updateDynamicFields(this)">${typeOptions}</select><select class="q-bloom">${bloomOptions}</select><input type="number" class="q-marks-val" value="${marks}" step="1"></div>
        <textarea class="q-text" rows="2" placeholder="Question text"></textarea>
        <div class="dynamic-fields"></div>
        <textarea class="q-answer" rows="2" placeholder="Model answer / rubric"></textarea>
    `;
    container.appendChild(div);
    updateDynamicFields(div.querySelector('.q-type'));
    updateSectionTotal(section);
}

function updateDynamicFields(selectEl) {
    let card = selectEl.closest('.question-card');
    let dynamicDiv = card.querySelector('.dynamic-fields');
    let type = selectEl.value;
    if (type === 'multiple_choice') {
        dynamicDiv.innerHTML = `<div class="mc-options"><input type="text" placeholder="Option A"><input type="text" placeholder="Option B"><input type="text" placeholder="Option C"><input type="text" placeholder="Option D"></div>`;
    } else if (type === 'matching') {
        dynamicDiv.innerHTML = `<textarea rows="3" placeholder="LEFT COLUMN (items, one per line)"></textarea><textarea rows="3" placeholder="RIGHT COLUMN (descriptions, one per line)"></textarea>`;
    } else if (type === 'fill_table') {
        dynamicDiv.innerHTML = `<textarea rows="3" placeholder="Define table structure (e.g., | Concept | Definition | Example |)"></textarea>`;
    } else if (type === 'arrange_steps') {
        dynamicDiv.innerHTML = `<textarea rows="4" placeholder="List steps in random order (one per line)"></textarea>`;
    } else {
        dynamicDiv.innerHTML = '';
    }
}

function removeQuestion(btn) {
    let card = btn.closest('.question-card');
    let section = card.getAttribute('data-section');
    card.remove();
    renumberQuestions(section);
    updateSectionTotal(section);
}
function renumberQuestions(section) {
    let container = document.getElementById(`section${section}_container`);
    Array.from(container.children).forEach((c, i) => c.querySelector('.q-num').innerHTML = `Q${i+1}`);
}
function updateSectionTotal(section) {
    let container = document.getElementById(`section${section}_container`);
    let total = 0;
    Array.from(container.children).forEach(card => total += parseInt(card.querySelector('.q-marks-val').value) || 0);
    let infoSpan = document.querySelector(`#section${section}_container`).closest('.section-card').querySelector('.section-total');
    if (infoSpan) infoSpan.innerText = total;
}
function collectQuestions(section) {
    let questions = [];
    let container = document.getElementById(`section${section}_container`);
    Array.from(container.children).forEach(card => {
        let type = card.querySelector('.q-type').value;
        let bloom = card.querySelector('.q-bloom').value;
        let marks = parseInt(card.querySelector('.q-marks-val').value) || 0;
        let text = card.querySelector('.q-text').value;
        let answer = card.querySelector('.q-answer').value;
        let extra = {};
        if (type === 'multiple_choice') {
            let opts = card.querySelectorAll('.mc-options input');
            if (opts.length) {
                extra.option_a = opts[0].value;
                extra.option_b = opts[1].value;
                extra.option_c = opts[2].value;
                extra.option_d = opts[3].value;
            }
        } else if (type === 'matching') {
            let tas = card.querySelectorAll('.dynamic-fields textarea');
            if (tas.length >= 2) {
                extra.left_column = tas[0].value;
                extra.right_column = tas[1].value;
            }
        } else if (type === 'fill_table') {
            let ta = card.querySelector('.dynamic-fields textarea');
            if (ta) extra.table_template = ta.value;
        } else if (type === 'arrange_steps') {
            let ta = card.querySelector('.dynamic-fields textarea');
            if (ta) extra.steps = ta.value;
        }
        questions.push({type, bloom, marks, text, answer, ...extra});
    });
    return questions;
}
function beforeSave() {
    let sectionA = collectQuestions('A');
    let sectionB = collectQuestions('B');
    let sectionC = collectQuestions('C');
    document.getElementById('sectionAJson').value = JSON.stringify(sectionA);
    document.getElementById('sectionBJson').value = JSON.stringify(sectionB);
    document.getElementById('sectionCJson').value = JSON.stringify(sectionC);
}
function previewAssessment() {
    beforeSave();
    let sectionA = collectQuestions('A');
    let sectionB = collectQuestions('B');
    let sectionC = collectQuestions('C');
    let win = window.open('', '_blank');
    win.document.write('<html><head><title>Assessment Preview</title><style>body{font-family:Arial;padding:30px;line-height:1.5;}h1{color:#2c3e50;}h2{color:#3498db;}</style></head><body>');
    win.document.write('<h1>Assessment Preview</h1><p><strong>Total marks: ' + (sectionA.reduce((s,q)=>s+q.marks,0) + sectionB.reduce((s,q)=>s+q.marks,0) + sectionC.reduce((s,q)=>s+q.marks,0)) + '</strong></p>');
    win.document.write('<h2>Section A (Compulsory)</h2>');
    sectionA.forEach((q,i) => win.document.write(`<div><strong>Q${i+1}.</strong> [${q.marks} marks] ${q.text}</div>`));
    win.document.write('<h2>Section B (Choose 3 of 5)</h2>');
    sectionB.forEach((q,i) => win.document.write(`<div><strong>Q${i+1}.</strong> [${q.marks} marks] ${q.text}</div>`));
    win.document.write('<h2>Section C (Choose 1 of 2)</h2>');
    sectionC.forEach((q,i) => win.document.write(`<div><strong>Q${i+1}.</strong> [${q.marks} marks] ${q.text}</div>`));
    win.document.write('</body></html>');
    win.document.close();
}
document.getElementById('assessmentForm')?.addEventListener('submit', beforeSave);

// Load existing data when editing
window.addEventListener('DOMContentLoaded', () => {
    <?php if ($editData): ?>
        let sections = ['A','B','C'];
        let data = { A: <?= json_encode($editData['section_a'] ?? []) ?>, B: <?= json_encode($editData['section_b'] ?? []) ?>, C: <?= json_encode($editData['section_c'] ?? []) ?> };
        sections.forEach(s => {
            data[s].forEach(q => {
                addQuestion(s);
                let card = document.querySelector(`#section${s}_container .question-card:last-child`);
                if (card) {
                    card.querySelector('.q-type').value = q.type;
                    card.querySelector('.q-bloom').value = q.bloom;
                    card.querySelector('.q-marks-val').value = q.marks;
                    card.querySelector('.q-text').value = q.text;
                    card.querySelector('.q-answer').value = q.answer;
                    if (q.type === 'multiple_choice') {
                        let opts = card.querySelectorAll('.mc-options input');
                        if (opts.length) { opts[0].value = q.option_a || ''; opts[1].value = q.option_b || ''; opts[2].value = q.option_c || ''; opts[3].value = q.option_d || ''; }
                    }
                    if (q.type === 'matching') {
                        let tas = card.querySelectorAll('.dynamic-fields textarea');
                        if (tas.length >= 2) { tas[0].value = q.left_column || ''; tas[1].value = q.right_column || ''; }
                    }
                    if (q.type === 'fill_table' && q.table_template) card.querySelector('.dynamic-fields textarea').value = q.table_template;
                    if (q.type === 'arrange_steps' && q.steps) card.querySelector('.dynamic-fields textarea').value = q.steps;
                    updateDynamicFields(card.querySelector('.q-type'));
                }
            });
            updateSectionTotal(s);
        });
    <?php endif; ?>
});
</script>

<?php include_once '../includes/templates/footer.php'; ?>