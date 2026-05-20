<?php
/**
 * AI Quiz Generator - Fixed Version (Proper review_bank table creation)
 * Path: /teacher/ai-quiz-generator.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);
require_once '../config/database.php';
require_once '../includes/functions/common.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

// Determine selected level name
$selectedName = '';
$selectedLevelId = 0;
$levelType = '';
if ($selectedSubtopicId > 0) {
    $levelType = 'subtopic';
    $selectedLevelId = $selectedSubtopicId;
    $stmt = $pdo->prepare("SELECT subtopic_title FROM subtopics WHERE subtopic_id = ?");
    $stmt->execute([$selectedSubtopicId]);
    $selectedName = $stmt->fetchColumn() ?: '';
} elseif ($selectedTopicId > 0) {
    $levelType = 'topic';
    $selectedLevelId = $selectedTopicId;
    $stmt = $pdo->prepare("SELECT topic_title FROM topics WHERE topic_id = ?");
    $stmt->execute([$selectedTopicId]);
    $selectedName = $stmt->fetchColumn() ?: '';
}

// ----- Create / alter review_bank table to ensure all columns exist -----
$pdo->exec("
    CREATE TABLE IF NOT EXISTS review_bank (
        question_id INT AUTO_INCREMENT PRIMARY KEY,
        module_id INT NOT NULL,
        outcome_id INT NULL,
        ic_id INT NULL,
        topic_id INT NULL,
        subtopic_id INT NULL,
        question_text TEXT NOT NULL,
        option_a VARCHAR(500) NOT NULL,
        option_b VARCHAR(500) NOT NULL,
        option_c VARCHAR(500) NOT NULL,
        option_d VARCHAR(500) NOT NULL,
        correct_answer CHAR(1) NOT NULL,
        explanation TEXT NULL,
        bloom_level ENUM('remember','understand','apply','analyze','evaluate','create') DEFAULT 'understand',
        difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
        created_by INT NOT NULL,
        status ENUM('draft','approved','published') DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (module_id) REFERENCES modules(module_id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Also add columns if they are missing (in case table existed without them)
$columnsNeeded = ['ic_id', 'topic_id', 'subtopic_id'];
foreach ($columnsNeeded as $col) {
    try {
        $pdo->exec("ALTER TABLE review_bank ADD COLUMN $col INT NULL AFTER outcome_id");
    } catch (PDOException $e) {
        // Column already exists – ignore
    }
}

// Handle AJAX generation and saving
$message = '';
$error = '';
$generatedQuestions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate_quiz') {
        $feedback = trim($_POST['feedback'] ?? '');
        $numQuestions = (int) ($_POST['num_questions'] ?? 5);
        $difficulty = $_POST['difficulty'] ?? 'medium';
        $bloomLevel = $_POST['bloom_level'] ?? 'understand';

        if (!$selectedLevelId || !$levelType) {
            $error = "Please select a topic or subtopic first.";
        } else {
            // ----- SIMULATED AI GENERATION (replace with real API) -----
            $generatedQuestions = [];
            for ($i = 1; $i <= $numQuestions; $i++) {
                $question = "Sample question #$i about \"$selectedName\". " . ($feedback ? "Based on: " . substr($feedback, 0, 50) : "");
                $generatedQuestions[] = [
                    'question_text' => $question,
                    'option_a' => "Option A for Q$i",
                    'option_b' => "Option B for Q$i",
                    'option_c' => "Option C for Q$i",
                    'option_d' => "Option D for Q$i",
                    'correct_answer' => 'A',
                    'explanation' => "This is an AI-generated explanation for question $i.",
                    'bloom_level' => $bloomLevel,
                    'difficulty' => $difficulty
                ];
            }
            $_SESSION['ai_quiz_temp'] = $generatedQuestions;
            $message = "✅ {$numQuestions} questions generated. Review and save below.";
        }
    }
    elseif ($action === 'save_questions') {
        $questions = json_decode($_POST['questions_json'] ?? '[]', true);
        $saved = 0;
        $errors = [];

        foreach ($questions as $q) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO review_bank 
                    (module_id, outcome_id, ic_id, topic_id, subtopic_id, question_text, 
                     option_a, option_b, option_c, option_d, correct_answer, explanation, 
                     bloom_level, difficulty, created_by, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
                ");
                $stmt->execute([
                    $selectedModuleId ?: null,
                    $selectedLoId ?: null,
                    $selectedIcId ?: null,
                    $selectedTopicId ?: null,
                    $selectedSubtopicId ?: null,
                    $q['question_text'],
                    $q['option_a'],
                    $q['option_b'],
                    $q['option_c'],
                    $q['option_d'],
                    $q['correct_answer'],
                    $q['explanation'],
                    $q['bloom_level'],
                    $q['difficulty'],
                    $userId
                ]);
                $saved++;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        if ($saved > 0) {
            $message = "✅ $saved questions saved to Review Bank.";
            unset($_SESSION['ai_quiz_temp']);
        } else {
            $error = "Failed to save questions: " . implode(', ', $errors);
        }
    }
}

$generatedQuestions = $_SESSION['ai_quiz_temp'] ?? [];

include_once '../includes/templates/header.php';
?>

<div class="quiz-generator">
    <div class="page-header">
        <h1><i class="fas fa-puzzle-piece"></i> AI Quiz Generator</h1>
        <p>Generate quiz questions for any topic/subtopic using AI</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="info-card">
        <i class="fas fa-robot"></i> 
        <strong>Demo AI:</strong> This is a simulated AI. Provide an API key (OpenAI, Gemini) in the backend to enable real question generation.
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

    <?php if ($selectedLevelId): ?>
        <div class="selected-info">
            📌 <strong>Selected:</strong> <?= htmlspecialchars($selectedName) ?> (<?= ucfirst($levelType) ?>)
        </div>

        <!-- Generation controls -->
        <div class="generation-card">
            <h3><i class="fas fa-sliders-h"></i> Quiz Settings</h3>
            <form method="POST" id="generateForm">
                <input type="hidden" name="action" value="generate_quiz">
                <div class="form-row">
                    <div class="form-group">
                        <label>Number of questions</label>
                        <input type="number" name="num_questions" min="1" max="20" value="5" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Difficulty</label>
                        <select name="difficulty" class="form-control">
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Bloom's Taxonomy level</label>
                        <select name="bloom_level" class="form-control">
                            <option value="remember">Remember</option>
                            <option value="understand" selected>Understand</option>
                            <option value="apply">Apply</option>
                            <option value="analyze">Analyze</option>
                            <option value="evaluate">Evaluate</option>
                            <option value="create">Create</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Additional feedback / instructions (optional)</label>
                    <textarea name="feedback" rows="2" class="form-control" placeholder="e.g., Focus on real-world examples, include true/false questions, or specify a particular subtopic..."></textarea>
                </div>
                <button type="submit" class="btn-generate"><i class="fas fa-magic"></i> Generate Quiz</button>
            </form>
        </div>

        <!-- Generated questions preview and edit -->
        <?php if (!empty($generatedQuestions)): ?>
            <div class="questions-card">
                <h3><i class="fas fa-list"></i> Generated Questions (<?= count($generatedQuestions) ?>)</h3>
                <form method="POST" id="saveForm">
                    <input type="hidden" name="action" value="save_questions">
                    <input type="hidden" name="questions_json" id="questions_json">
                    
                    <div id="questions-container">
                        <?php foreach ($generatedQuestions as $idx => $q): ?>
                            <div class="question-item" data-index="<?= $idx ?>">
                                <div class="q-header">
                                    <span class="q-num">Question <?= $idx+1 ?></span>
                                    <button type="button" class="btn-remove-q" onclick="removeQuestion(this)">🗑️ Remove</button>
                                </div>
                                <div class="form-group">
                                    <label>Question Text</label>
                                    <textarea class="q-text form-control" rows="2"><?= htmlspecialchars($q['question_text']) ?></textarea>
                                </div>
                                <div class="options-grid">
                                    <div class="form-group"><label>A</label><input class="q-opt-a form-control" value="<?= htmlspecialchars($q['option_a']) ?>"></div>
                                    <div class="form-group"><label>B</label><input class="q-opt-b form-control" value="<?= htmlspecialchars($q['option_b']) ?>"></div>
                                    <div class="form-group"><label>C</label><input class="q-opt-c form-control" value="<?= htmlspecialchars($q['option_c']) ?>"></div>
                                    <div class="form-group"><label>D</label><input class="q-opt-d form-control" value="<?= htmlspecialchars($q['option_d']) ?>"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Correct Answer</label>
                                        <select class="q-correct form-control">
                                            <option value="A" <?= $q['correct_answer'] == 'A' ? 'selected' : '' ?>>A</option>
                                            <option value="B" <?= $q['correct_answer'] == 'B' ? 'selected' : '' ?>>B</option>
                                            <option value="C" <?= $q['correct_answer'] == 'C' ? 'selected' : '' ?>>C</option>
                                            <option value="D" <?= $q['correct_answer'] == 'D' ? 'selected' : '' ?>>D</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Bloom Level</label>
                                        <select class="q-bloom form-control">
                                            <option value="remember" <?= ($q['bloom_level']??'') == 'remember' ? 'selected' : '' ?>>Remember</option>
                                            <option value="understand" <?= ($q['bloom_level']??'') == 'understand' ? 'selected' : '' ?>>Understand</option>
                                            <option value="apply" <?= ($q['bloom_level']??'') == 'apply' ? 'selected' : '' ?>>Apply</option>
                                            <option value="analyze" <?= ($q['bloom_level']??'') == 'analyze' ? 'selected' : '' ?>>Analyze</option>
                                            <option value="evaluate" <?= ($q['bloom_level']??'') == 'evaluate' ? 'selected' : '' ?>>Evaluate</option>
                                            <option value="create" <?= ($q['bloom_level']??'') == 'create' ? 'selected' : '' ?>>Create</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Difficulty</label>
                                        <select class="q-difficulty form-control">
                                            <option value="easy" <?= ($q['difficulty']??'') == 'easy' ? 'selected' : '' ?>>Easy</option>
                                            <option value="medium" <?= ($q['difficulty']??'') == 'medium' ? 'selected' : '' ?>>Medium</option>
                                            <option value="hard" <?= ($q['difficulty']??'') == 'hard' ? 'selected' : '' ?>>Hard</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Explanation (optional)</label>
                                    <textarea class="q-explanation form-control" rows="2"><?= htmlspecialchars($q['explanation']) ?></textarea>
                                </div>
                                <hr>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="action-buttons">
                        <button type="button" class="btn-add-question" onclick="addQuestion()">+ Add Another Question</button>
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save All to Review Bank</button>
                    </div>
                </form>
            </div>
        <?php elseif ($selectedLevelId): ?>
            <div class="info-message">Configure the settings above and click "Generate Quiz".</div>
        <?php endif; ?>
    <?php elseif ($selectedModuleId): ?>
        <div class="info-message">Continue selecting LO → IC → Topic → Subtopic.</div>
    <?php else: ?>
        <div class="info-message">Select a module to start.</div>
    <?php endif; ?>
</div>

<style>
.quiz-generator{max-width:1100px;margin:0 auto;padding:30px 20px;}
.page-header{margin-bottom:25px;}
.page-header h1{font-size:28px;color:#1a1a2e;}
.info-card{background:#e3f2fd;border-left:4px solid #2196f3;padding:12px;border-radius:8px;margin-bottom:20px;}
.hierarchy-selector{background:white;border-radius:16px;padding:20px;margin-bottom:25px;display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.hierarchy-selector select{flex:1;min-width:120px;padding:8px;border:1px solid #ddd;border-radius:8px;}
.selected-info{background:#e8f0fe;padding:10px 15px;border-radius:12px;margin-bottom:20px;}
.generation-card{background:white;border-radius:20px;padding:25px;margin-bottom:30px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}
.form-row{display:flex;gap:20px;flex-wrap:wrap;margin-bottom:15px;}
.form-group{flex:1;min-width:150px;}
.form-group label{display:block;margin-bottom:5px;font-weight:500;}
.form-control{width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:8px;}
.btn-generate{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;padding:10px 30px;border-radius:30px;cursor:pointer;font-weight:bold;}
.questions-card{background:white;border-radius:20px;padding:25px;box-shadow:0 2px 8px rgba(0,0,0,0.05);margin-top:20px;}
.question-item{background:#f9fafc;border-radius:16px;padding:20px;margin-bottom:20px;}
.q-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;}
.q-num{font-weight:bold;font-size:16px;color:#667eea;}
.btn-remove-q{background:#f44336;color:white;border:none;padding:4px 12px;border-radius:20px;cursor:pointer;}
.options-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:15px;}
.action-buttons{display:flex;gap:15px;justify-content:flex-end;margin-top:20px;}
.btn-add-question{background:#4CAF50;color:white;border:none;padding:8px 20px;border-radius:30px;cursor:pointer;}
.btn-save{background:#2196f3;color:white;border:none;padding:10px 30px;border-radius:30px;cursor:pointer;}
.alert{padding:12px;border-radius:12px;margin-bottom:20px;}
.alert-success{background:#e8f5e9;color:#2e7d32;}
.alert-error{background:#ffebee;color:#c62828;}
.info-message{text-align:center;padding:40px;background:#f8f9fa;border-radius:20px;}
hr{margin:15px 0;}
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

function removeQuestion(btn) {
    btn.closest('.question-item').remove();
}

function addQuestion() {
    let container = document.getElementById('questions-container');
    let template = container.querySelector('.question-item');
    if (!template) return;
    let newDiv = template.cloneNode(true);
    // Clear inputs but keep default structure
    newDiv.querySelectorAll('.q-text, .q-opt-a, .q-opt-b, .q-opt-c, .q-opt-d, .q-explanation').forEach(el => el.value = '');
    newDiv.querySelector('.q-text').value = 'New question. Please edit.';
    newDiv.querySelector('.q-opt-a').value = 'Option A';
    newDiv.querySelector('.q-opt-b').value = 'Option B';
    newDiv.querySelector('.q-opt-c').value = 'Option C';
    newDiv.querySelector('.q-opt-d').value = 'Option D';
    newDiv.querySelector('.q-correct').value = 'A';
    newDiv.querySelector('.q-bloom').value = 'understand';
    newDiv.querySelector('.q-difficulty').value = 'medium';
    newDiv.querySelector('.q-explanation').value = '';
    let idx = container.children.length + 1;
    newDiv.querySelector('.q-num').innerText = 'Question ' + idx;
    container.appendChild(newDiv);
}

document.getElementById('saveForm')?.addEventListener('submit', function(e) {
    let questions = [];
    document.querySelectorAll('.question-item').forEach(item => {
        questions.push({
            question_text: item.querySelector('.q-text').value,
            option_a: item.querySelector('.q-opt-a').value,
            option_b: item.querySelector('.q-opt-b').value,
            option_c: item.querySelector('.q-opt-c').value,
            option_d: item.querySelector('.q-opt-d').value,
            correct_answer: item.querySelector('.q-correct').value,
            bloom_level: item.querySelector('.q-bloom').value,
            difficulty: item.querySelector('.q-difficulty').value,
            explanation: item.querySelector('.q-explanation').value
        });
    });
    document.getElementById('questions_json').value = JSON.stringify(questions);
});
</script>

<?php include_once '../includes/templates/footer.php'; ?>