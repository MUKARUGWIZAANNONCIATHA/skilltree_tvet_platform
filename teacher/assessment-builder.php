<?php
/**
 * Assessment Builder - Fully Dynamic + AI Generation
 * Path: /teacher/assessment-builder.php
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

// Ensure required columns exist
try {
    $pdo->exec("ALTER TABLE lo_assessments ADD COLUMN IF NOT EXISTS assessment_data_json LONGTEXT NULL");
    $pdo->exec("ALTER TABLE lo_assessments ADD COLUMN IF NOT EXISTS status ENUM('draft','published') DEFAULT 'draft'");
    $pdo->exec("ALTER TABLE lo_assessments ADD COLUMN IF NOT EXISTS created_by INT NULL");
    $pdo->exec("ALTER TABLE lo_assessments ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    $pdo->exec("ALTER TABLE lo_assessments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP");
} catch (PDOException $e) {}

// Get modules (teacher assigned OR created)
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

$selectedModuleId = (int)($_GET['module_id'] ?? 0);
$selectedOutcomeId = (int)($_GET['outcome_id'] ?? 0);
$selectedAssessmentId = (int)($_GET['assessment_id'] ?? 0);

// Permission check
if ($selectedModuleId > 0 && $role !== 'admin') {
    $check = $pdo->prepare("SELECT COUNT(*) FROM teacher_modules WHERE teacher_id = ? AND module_id = ?");
    $check->execute([$userId, $selectedModuleId]);
    $assigned = $check->fetchColumn() > 0;
    $checkCreator = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE module_id = ? AND created_by = ?");
    $checkCreator->execute([$selectedModuleId, $userId]);
    $created = $checkCreator->fetchColumn() > 0;
    if (!$assigned && !$created) die("Access denied.");
}

// Learning outcomes
$outcomes = [];
if ($selectedModuleId) {
    $stmt = $pdo->prepare("SELECT outcome_id, outcome_number, description FROM learning_outcomes WHERE module_id = ? ORDER BY outcome_number");
    $stmt->execute([$selectedModuleId]);
    $outcomes = $stmt->fetchAll();
}

// Load existing assessment
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

// Handle AI generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ai_generate'])) {
    $topic = trim($_POST['ai_topic'] ?? '');
    $bloom = $_POST['ai_bloom'] ?? 'understand';
    $num = (int)($_POST['ai_num_questions'] ?? 5);
    $section = $_POST['ai_section'] ?? 'A';
    $marks = $section === 'A' ? 3 : ($section === 'B' ? 10 : 15);
    $generated = [];
    for ($i = 0; $i < $num; $i++) {
        $generated[] = [
            'type' => 'short_answer',
            'bloom' => $bloom,
            'marks' => $marks,
            'text' => "AI generated: Explain the concept of '$topic' in your own words. (Bloom: $bloom)",
            'answer' => "Model answer for $topic depending on $bloom level."
        ];
    }
    $_SESSION['ai_gen'] = ['section' => $section, 'questions' => $generated];
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Use AI generated if present
if (isset($_SESSION['ai_gen'])) {
    $aiData = $_SESSION['ai_gen'];
    $targetContainer = "section{$aiData['section']}_container";
    unset($_SESSION['ai_gen']);
}

// Handle manual save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assessment'])) {
    $outcomeId = (int)$_POST['outcome_id'];
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $duration = (int)$_POST['duration'];
    $passScore = (int)$_POST['passing_score'];
    $status = $_POST['status'];
    $sectionA = json_decode($_POST['section_a_json'] ?? '[]', true);
    $sectionB = json_decode($_POST['section_b_json'] ?? '[]', true);
    $sectionC = json_decode($_POST['section_c_json'] ?? '[]', true);
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
        $message = "Assessment updated!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO lo_assessments (outcome_id, title, description, time_limit_minutes, passing_score, assessment_data_json, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$outcomeId, $title, $desc, $duration, $passScore, json_encode($assessmentData), $status, $userId]);
        $message = "Assessment created!";
    }
    header("Location: assessment-builder.php?module_id=$selectedModuleId&outcome_id=$outcomeId&msg=" . urlencode($message));
    exit;
}

// Existing assessments list
$assessments = [];
if ($selectedOutcomeId) {
    $stmt = $pdo->prepare("SELECT * FROM lo_assessments WHERE outcome_id = ? ORDER BY created_at DESC");
    $stmt->execute([$selectedOutcomeId]);
    $assessments = $stmt->fetchAll();
}

include_once '../includes/templates/header.php';
?>

<div class="assessment-builder">
    <h1><i class="fas fa-clipboard-list"></i> Learning Outcome Assessment Builder</h1>
    <p>Create structured assessments with dynamic question types + AI generation.</p>

    <?php if (isset($_GET['msg'])) echo "<div class='alert success'>".htmlspecialchars($_GET['msg'])."</div>"; ?>
    <?php if ($error) echo "<div class='alert error'>$error</div>"; ?>

    <!-- Module & Outcome selection -->
    <div class="selection-row">
        <div class="form-group">
            <label>Module</label>
            <select id="module_sel" onchange="loadOutcomes()">
                <option value="">-- Select --</option>
                <?php foreach ($modules as $m): ?>
                <option value="<?= $m['module_id'] ?>" <?= $selectedModuleId == $m['module_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['module_code']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Learning Outcome</label>
            <select id="outcome_sel" onchange="loadAssessments()" <?= empty($outcomes) ? 'disabled' : '' ?>>
                <option value="">-- Select --</option>
                <?php foreach ($outcomes as $lo): ?>
                <option value="<?= $lo['outcome_id'] ?>" <?= $selectedOutcomeId == $lo['outcome_id'] ? 'selected' : '' ?>>LO<?= $lo['outcome_number'] ?>: <?= substr($lo['description'],0,60) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($selectedOutcomeId): ?>
        <!-- Existing Assessments -->
        <div class="existing-list">
            <h3>Existing Assessments</h3>
            <?php if (empty($assessments)): ?><p>None yet.</p>
            <?php else: ?>
                <table>
                    <tr><th>Title</th><th>Passing %</th><th>Duration</th><th>Status</th><th>Actions</th></tr>
                    <?php foreach ($assessments as $a): ?>
                    <tr><td><?= htmlspecialchars($a['title']) ?></td><td><?= $a['passing_score'] ?>%</td><td><?= $a['time_limit_minutes'] ?> min</td><td><?= ucfirst($a['status']) ?></td><td><a href="?module_id=<?= $selectedModuleId ?>&outcome_id=<?= $selectedOutcomeId ?>&assessment_id=<?= $a['lo_assessment_id'] ?>">Edit</a> | <a href="javascript:void(0)" onclick="deleteAssessment(<?= $a['lo_assessment_id'] ?>)">Delete</a></td></tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
            <a href="?module_id=<?= $selectedModuleId ?>&outcome_id=<?= $selectedOutcomeId ?>" class="btn-new">+ New Assessment</a>
        </div>

        <!-- Builder Form -->
        <div class="builder-card">
            <h2><?= $editAssessment ? 'Edit' : 'Create' ?> Assessment</h2>

            <!-- AI Generator Toggle -->
            <div class="ai-section">
                <button type="button" class="btn-toggle-ai" onclick="toggleAI()">🤖 AI Generate Questions</button>
                <div id="ai-panel" style="display:none; margin-top:15px;">
                    <div class="ai-fields">
                        <input type="text" id="ai_topic" placeholder="Topic / Keyword" style="flex:2">
                        <select id="ai_bloom">
                            <option value="remember">Remember</option>
                            <option value="understand">Understand</option>
                            <option value="apply">Apply</option>
                            <option value="analyze">Analyze</option>
                            <option value="evaluate">Evaluate</option>
                            <option value="create">Create</option>
                        </select>
                        <select id="ai_section">
                            <option value="A">Section A (Compulsory)</option>
                            <option value="B">Section B (Choose 3 of 5)</option>
                            <option value="C">Section C (Choose 1 of 2)</option>
                        </select>
                        <input type="number" id="ai_num" value="5" min="1" max="10">
                        <button type="button" onclick="generateAI()">Generate</button>
                    </div>
                    <div id="ai-result" style="margin-top:10px; font-size:13px; color:#666;"></div>
                </div>
            </div>

            <form method="post" id="assessmentForm">
                <input type="hidden" name="save_assessment" value="1">
                <input type="hidden" name="outcome_id" value="<?= $selectedOutcomeId ?>">
                <input type="hidden" name="section_a_json" id="sectionAJson">
                <input type="hidden" name="section_b_json" id="sectionBJson">
                <input type="hidden" name="section_c_json" id="sectionCJson">

                <div class="form-row">
                    <div class="form-group"><label>Assessment Title</label><input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editAssessment['title'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Duration (minutes)</label><input type="number" name="duration" class="form-control" value="<?= $editAssessment['time_limit_minutes'] ?? 120 ?>"></div>
                    <div class="form-group"><label>Passing Score (%)</label><input type="number" name="passing_score" class="form-control" value="<?= $editAssessment['passing_score'] ?? 70 ?>"></div>
                    <div class="form-group"><label>Status</label><select name="status"><option value="draft">Draft</option><option value="published" <?= ($editAssessment['status']??'')=='published'?'selected':'' ?>>Published</option></select></div>
                </div>
                <div class="form-group"><label>Instructions / Description</label><textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($editAssessment['description'] ?? '') ?></textarea></div>

                <!-- Section A -->
                <div class="section-card">
                    <div class="section-header"><h3>Section A (Compulsory)</h3><button type="button" class="btn-add" onclick="addQuestion('A')">+ Add Question</button></div>
                    <div id="sectionA_container" class="questions-container"></div>
                    <div class="section-info">Total: <span class="section-total">0</span> / recommended 55 marks</div>
                </div>

                <!-- Section B -->
                <div class="section-card">
                    <div class="section-header"><h3>Section B (Choose 3 of 5)</h3><button type="button" class="btn-add" onclick="addQuestion('B')">+ Add Question</button></div>
                    <div id="sectionB_container" class="questions-container"></div>
                    <div class="section-info">Total: <span class="section-total">0</span> / recommended 30 marks (each 10)</div>
                </div>

                <!-- Section C -->
                <div class="section-card">
                    <div class="section-header"><h3>Section C (Choose 1 of 2)</h3><button type="button" class="btn-add" onclick="addQuestion('C')">+ Add Question</button></div>
                    <div id="sectionC_container" class="questions-container"></div>
                    <div class="section-info">Total: <span class="section-total">0</span> / recommended 15 marks</div>
                </div>

                <div class="form-actions"><button type="submit" class="btn-save">Save Assessment</button></div>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
    /* Basic styling */
    .assessment-builder { max-width: 1200px; margin:0 auto; padding:20px; }
    .selection-row { display:flex; gap:20px; margin-bottom:20px; }
    .form-group { flex:1; }
    .section-card { background:#f8f9fa; border-radius:16px; padding:20px; margin-bottom:25px; }
    .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
    .btn-add { background:#4CAF50; color:white; border:none; padding:5px 15px; border-radius:25px; cursor:pointer; }
    .question-card { background:white; border-radius:12px; padding:15px; margin-bottom:15px; border-left:4px solid #667eea; }
    .q-header { display:flex; gap:10px; align-items:center; margin-bottom:10px; }
    .q-marks { background:#667eea; color:white; padding:2px 8px; border-radius:15px; font-size:11px; }
    .btn-remove { background:#f44336; color:white; border:none; padding:2px 10px; border-radius:20px; cursor:pointer; margin-left:auto; }
    .form-row { display:flex; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
    .q-type, .q-bloom, .q-marks-val { padding:6px; border:1px solid #ddd; border-radius:6px; }
    .q-text, .q-answer { width:100%; margin-bottom:8px; padding:8px; border:1px solid #ddd; border-radius:6px; }
    .dynamic-fields { margin:10px 0; padding:10px; background:#f9f9ff; border-radius:8px; display:flex; flex-direction:column; gap:10px; }
    .dynamic-fields input, .dynamic-fields textarea { width:100%; padding:6px; border:1px solid #ccc; border-radius:6px; }
    .mc-options { display:flex; gap:8px; flex-wrap:wrap; }
    .mc-options input { flex:1; }
    .ai-section { background:#e8f0fe; border-radius:12px; padding:15px; margin-bottom:20px; }
    .ai-fields { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
    .alert.success { background:#e8f5e9; color:#2e7d32; padding:10px; border-radius:8px; margin-bottom:15px; }
    .btn-save { background:linear-gradient(135deg,#667eea,#764ba2); color:white; padding:10px 25px; border-radius:30px; border:none; cursor:pointer; }
    .existing-list table { width:100%; border-collapse:collapse; margin:10px 0; }
    .existing-list th, .existing-list td { padding:8px; border-bottom:1px solid #eee; text-align:left; }
    .btn-new { display:inline-block; margin-top:10px; background:#667eea; color:white; padding:6px 15px; border-radius:25px; text-decoration:none; }
</style>

<script>
// Global counts for auto JSON collection
let questionBank = {A:[], B:[], C:[]};

function loadOutcomes() {
    let moduleId = document.getElementById('module_sel').value;
    if(moduleId) location.href = 'assessment-builder.php?module_id=' + moduleId;
}
function loadAssessments() {
    let moduleId = document.getElementById('module_sel').value;
    let outcomeId = document.getElementById('outcome_sel').value;
    if(outcomeId) location.href = 'assessment-builder.php?module_id=' + moduleId + '&outcome_id=' + outcomeId;
}
function deleteAssessment(id) {
    if(confirm('Delete this assessment?')) location.href = 'delete-assessment.php?id=' + id;
}

function toggleAI() {
    let panel = document.getElementById('ai-panel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}
function generateAI() {
    let topic = document.getElementById('ai_topic').value;
    let bloom = document.getElementById('ai_bloom').value;
    let section = document.getElementById('ai_section').value;
    let num = document.getElementById('ai_num').value;
    if(!topic) { alert('Enter a topic'); return; }
    fetch('assessment-builder.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ai_generate=1&ai_topic=${encodeURIComponent(topic)}&ai_bloom=${bloom}&ai_section=${section}&ai_num_questions=${num}`
    }).then(() => location.reload());
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
        <div class="form-row">
            <select class="q-type" onchange="updateDynamicFields(this)">${typeOptions}</select>
            <select class="q-bloom">${bloomOptions}</select>
            <input type="number" class="q-marks-val" value="${marks}" step="1">
        </div>
        <textarea class="q-text" rows="2" placeholder="Question text"></textarea>
        <div class="dynamic-fields" id="dynamic-fields-${Date.now()}-${idx}"></div>
        <textarea class="q-answer" rows="2" placeholder="Model answer / rubric"></textarea>
    `;
    container.appendChild(div);
    const typeSelect = div.querySelector('.q-type');
    updateDynamicFields(typeSelect);
    updateSectionTotal(section);
}

function updateDynamicFields(selectEl) {
    let card = selectEl.closest('.question-card');
    let dynamicDiv = card.querySelector('.dynamic-fields');
    let type = selectEl.value;
    if(type === 'multiple_choice') {
        dynamicDiv.innerHTML = `
            <div class="mc-options">
                <input type="text" placeholder="Option A"><input type="text" placeholder="Option B">
                <input type="text" placeholder="Option C"><input type="text" placeholder="Option D">
            </div>
        `;
    } else if(type === 'matching') {
        dynamicDiv.innerHTML = `
            <textarea rows="3" placeholder="LEFT COLUMN (items, one per line)"></textarea>
            <textarea rows="3" placeholder="RIGHT COLUMN (descriptions, one per line)"></textarea>
        `;
    } else if(type === 'fill_table') {
        dynamicDiv.innerHTML = `<textarea rows="4" placeholder="Define table structure (e.g., | Concept | Definition | Example |)"></textarea>`;
    } else if(type === 'arrange_steps') {
        dynamicDiv.innerHTML = `<textarea rows="4" placeholder="List steps in random order (one per line)"></textarea>`;
    } else {
        dynamicDiv.innerHTML = ''; // no extra fields
    }
}

function removeQuestion(btn) {
    let card = btn.closest('.question-card');
    let section = card.getAttribute('data-section');
    card.remove();
    renumber(section);
    updateSectionTotal(section);
}
function renumber(section) {
    let container = document.getElementById(`section${section}_container`);
    Array.from(container.children).forEach((c,i) => c.querySelector('.q-num').innerText = `Q${i+1}`);
}
function updateSectionTotal(section) {
    let container = document.getElementById(`section${section}_container`);
    let total = 0;
    Array.from(container.children).forEach(card => total += parseInt(card.querySelector('.q-marks-val').value) || 0);
    document.querySelector(`#section${section}_container`).closest('.section-card').querySelector('.section-total').innerText = total;
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
        if(type === 'multiple_choice') {
            let opts = card.querySelectorAll('.mc-options input');
            if(opts.length) {
                extra.option_a = opts[0].value;
                extra.option_b = opts[1].value;
                extra.option_c = opts[2].value;
                extra.option_d = opts[3].value;
            }
        } else if(type === 'matching') {
            let texts = card.querySelectorAll('.dynamic-fields textarea');
            if(texts.length >= 2) {
                extra.left_column = texts[0].value;
                extra.right_column = texts[1].value;
            }
        } else if(type === 'fill_table') {
            let ta = card.querySelector('.dynamic-fields textarea');
            if(ta) extra.table_template = ta.value;
        } else if(type === 'arrange_steps') {
            let ta = card.querySelector('.dynamic-fields textarea');
            if(ta) extra.steps = ta.value;
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
document.getElementById('assessmentForm')?.addEventListener('submit', beforeSave);

// Load existing data if editing
window.addEventListener('DOMContentLoaded', () => {
    <?php if ($editData): ?>
        let sections = ['A','B','C'];
        sections.forEach(s => {
            let qs = <?= json_encode($editData['section_a'] ?? []) ?>;
            if(s === 'B') qs = <?= json_encode($editData['section_b'] ?? []) ?>;
            if(s === 'C') qs = <?= json_encode($editData['section_c'] ?? []) ?>;
            qs.forEach(q => {
                addQuestion(s);
                let card = document.querySelector(`#section${s}_container .question-card:last-child`);
                if(card) {
                    card.querySelector('.q-type').value = q.type;
                    card.querySelector('.q-bloom').value = q.bloom;
                    card.querySelector('.q-marks-val').value = q.marks;
                    card.querySelector('.q-text').value = q.text;
                    card.querySelector('.q-answer').value = q.answer;
                    if(q.type === 'multiple_choice') {
                        let opts = card.querySelectorAll('.mc-options input');
                        if(opts.length) { opts[0].value = q.option_a || ''; opts[1].value = q.option_b || ''; opts[2].value = q.option_c || ''; opts[3].value = q.option_d || ''; }
                    }
                    if(q.type === 'matching') {
                        let tareas = card.querySelectorAll('.dynamic-fields textarea');
                        if(tareas.length) { tareas[0].value = q.left_column || ''; tareas[1].value = q.right_column || ''; }
                    }
                    if(q.type === 'fill_table' && q.table_template) card.querySelector('.dynamic-fields textarea').value = q.table_template;
                    if(q.type === 'arrange_steps' && q.steps) card.querySelector('.dynamic-fields textarea').value = q.steps;
                    updateDynamicFields(card.querySelector('.q-type'));
                }
            });
            updateSectionTotal(s);
        });
    <?php endif; ?>
});
</script>

<?php include_once '../includes/templates/footer.php'; ?>