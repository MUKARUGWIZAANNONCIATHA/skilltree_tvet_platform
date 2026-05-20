<?php
/**
 * Topic Quiz Builder - Professional Edition with Save Feature
 * Path: /teacher/quiz-builder.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

// Get selections
$selectedModuleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$selectedTopicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$editQuestionId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;

// Get modules
if ($role === 'admin') {
    $modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT module_id, module_code, module_name FROM modules WHERE created_by = ? ORDER BY module_code");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
}

// Get topics for selected module
$topics = [];
if ($selectedModuleId > 0) {
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

// Get quiz settings
$passingScore = 70;
$timeLimit = 30;
$totalMarks = 0;
if ($selectedTopicId > 0) {
    $stmt = $pdo->prepare("SELECT passing_score, time_limit_minutes FROM topic_quizzes WHERE topic_id = ?");
    $stmt->execute([$selectedTopicId]);
    $quizSettings = $stmt->fetch();
    if ($quizSettings) {
        $passingScore = $quizSettings['passing_score'];
        $timeLimit = $quizSettings['time_limit_minutes'];
    }
}

// Get existing questions
$quizQuestions = [];
if ($selectedTopicId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE topic_id = ? ORDER BY order_number");
    $stmt->execute([$selectedTopicId]);
    $quizQuestions = $stmt->fetchAll();
    foreach ($quizQuestions as $q) {
        $totalMarks += $q['points'];
    }
}

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_quiz_settings') {
        $passingScore = intval($_POST['passing_score']);
        $timeLimit = intval($_POST['time_limit']);
        
        $stmt = $pdo->prepare("INSERT INTO topic_quizzes (topic_id, passing_score, time_limit_minutes) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE passing_score = ?, time_limit_minutes = ?");
        $stmt->execute([$selectedTopicId, $passingScore, $timeLimit, $passingScore, $timeLimit]);
        $message = "✅ Quiz settings saved!";
        
    } elseif ($action === 'save_question') {
        $questionId = intval($_POST['question_id'] ?? 0);
        $questionType = $_POST['question_type'];
        $questionText = $_POST['question_text'];
        $points = intval($_POST['points']);
        $orderNum = intval($_POST['order_num']);
        
        if ($questionType === 'multiple_choice') {
            $options = [
                $_POST['option_a'] ?? '',
                $_POST['option_b'] ?? '',
                $_POST['option_c'] ?? '',
                $_POST['option_d'] ?? ''
            ];
            $correctAnswer = $_POST['correct_option'] ?? '0';
            $optionsJson = json_encode($options);
        } elseif ($questionType === 'multiple_selection') {
            $options = [
                $_POST['opt_a'] ?? '',
                $_POST['opt_b'] ?? '',
                $_POST['opt_c'] ?? '',
                $_POST['opt_d'] ?? ''
            ];
            $correctAnswers = isset($_POST['correct_options']) ? implode(',', $_POST['correct_options']) : '';
            $optionsJson = json_encode(['options' => $options, 'correct' => $correctAnswers]);
            $correctAnswer = $correctAnswers;
        } elseif ($questionType === 'true_false') {
            $optionsJson = json_encode(['True', 'False']);
            $correctAnswer = $_POST['correct_tf'] ?? 'true';
        } elseif ($questionType === 'sentence_completion') {
            $sentenceBefore = $_POST['sentence_before'] ?? '';
            $sentenceAfter = $_POST['sentence_after'] ?? '';
            $correctWord = $_POST['correct_word'] ?? '';
            $optionsJson = json_encode(['before' => $sentenceBefore, 'after' => $sentenceAfter]);
            $correctAnswer = $correctWord;
        } elseif ($questionType === 'matching') {
            $leftItems = $_POST['left_items'] ?? [];
            $rightItems = $_POST['right_items'] ?? [];
            $matches = [];
            for ($i = 0; $i < count($leftItems); $i++) {
                $matches[] = ['left' => $leftItems[$i], 'right' => $rightItems[$i]];
            }
            $optionsJson = json_encode($matches);
            $correctAnswer = json_encode($matches);
        } else {
            $optionsJson = null;
            $correctAnswer = $_POST['correct_answer'] ?? '';
        }
        
        if ($questionId > 0) {
            $stmt = $pdo->prepare("UPDATE quiz_questions SET question_type = ?, question_text = ?, points = ?, options_json = ?, correct_answer = ? WHERE question_id = ?");
            $stmt->execute([$questionType, $questionText, $points, $optionsJson, $correctAnswer, $questionId]);
            $message = "✅ Question updated!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO quiz_questions (topic_id, question_type, question_text, points, order_number, options_json, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$selectedTopicId, $questionType, $questionText, $points, $orderNum, $optionsJson, $correctAnswer]);
            $message = "✅ Question added!";
        }
        
        header("Location: ?module_id=$selectedModuleId&topic_id=$selectedTopicId");
        exit();
        
    } elseif ($action === 'delete_question') {
        $questionId = intval($_POST['question_id']);
        $stmt = $pdo->prepare("DELETE FROM quiz_questions WHERE question_id = ?");
        $stmt->execute([$questionId]);
        $message = "✅ Question deleted!";
        header("Location: ?module_id=$selectedModuleId&topic_id=$selectedTopicId");
        exit();
        
    } elseif ($action === 'reorder_questions') {
        $orderData = json_decode($_POST['order_data'], true);
        foreach ($orderData as $item) {
            $stmt = $pdo->prepare("UPDATE quiz_questions SET order_number = ? WHERE question_id = ?");
            $stmt->execute([$item['order'], $item['id']]);
        }
        $message = "✅ Questions reordered!";
        
    } elseif ($action === 'save_all_quiz') {
        $questions = json_decode($_POST['questions_json'], true);
        $passingScore = intval($_POST['passing_score']);
        $timeLimit = intval($_POST['time_limit']);
        
        $stmt = $pdo->prepare("INSERT INTO topic_quizzes (topic_id, passing_score, time_limit_minutes) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE passing_score = ?, time_limit_minutes = ?");
        $stmt->execute([$selectedTopicId, $passingScore, $timeLimit, $passingScore, $timeLimit]);
        
        $pdo->prepare("DELETE FROM quiz_questions WHERE topic_id = ?")->execute([$selectedTopicId]);
        
        foreach ($questions as $q) {
            $stmt = $pdo->prepare("INSERT INTO quiz_questions (topic_id, question_type, question_text, points, order_number, options_json, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$selectedTopicId, $q['type'], $q['text'], $q['points'], $q['order'], json_encode($q['options']), $q['correct']]);
        }
        
        $message = "✅ All quiz questions saved successfully!";
        header("Location: ?module_id=$selectedModuleId&topic_id=$selectedTopicId");
        exit();
    }
}

$editQuestion = null;
if ($editQuestionId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE question_id = ?");
    $stmt->execute([$editQuestionId]);
    $editQuestion = $stmt->fetch();
}

include_once '../includes/templates/header.php';
?>

<div class="quiz-builder">
    <div class="page-header">
        <h1><i class="fas fa-puzzle-piece"></i> Topic Quiz Builder</h1>
        <p>Create professional quizzes with multiple question types</p>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="selection-area">
        <select id="module_select" class="form-select" onchange="updateTopics()">
            <option value="">-- Select Module --</option>
            <?php foreach($modules as $module): ?>
                <option value="<?php echo $module['module_id']; ?>" <?php echo $selectedModuleId == $module['module_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($module['module_code']); ?> - <?php echo htmlspecialchars($module['module_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select id="topic_select" class="form-select" onchange="loadQuiz()">
            <option value="">-- Select Topic --</option>
            <?php foreach($topics as $topic): ?>
                <option value="<?php echo $topic['topic_id']; ?>" <?php echo $selectedTopicId == $topic['topic_id'] ? 'selected' : ''; ?>>
                    LO<?php echo $topic['outcome_number']; ?>: <?php echo htmlspecialchars(substr($topic['topic_title'], 0, 50)); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if($selectedTopicId > 0): ?>
    
    <div class="settings-card">
        <h3>⚙️ Quiz Settings</h3>
        <div class="settings-row">
            <div class="setting">
                <label>Passing Score (%)</label>
                <input type="number" id="passing_score_input" class="form-control" value="<?php echo $passingScore; ?>" min="0" max="100">
            </div>
            <div class="setting">
                <label>Time Limit (minutes)</label>
                <input type="number" id="time_limit_input" class="form-control" value="<?php echo $timeLimit; ?>" min="0">
            </div>
            <div class="setting">
                <label>Total Marks</label>
                <input type="text" id="total_marks_display" class="form-control" value="<?php echo $totalMarks; ?>" readonly disabled style="background:#f0f0f0;">
            </div>
        </div>
    </div>

    <div class="add-question-card">
        <h3>➕ Add New Question</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Question Type</label>
                <select id="new_question_type" class="form-control" onchange="showNewQuestionTypeFields()">
                    <option value="multiple_choice">Multiple Choice (Single Answer)</option>
                    <option value="multiple_selection">Multiple Selection (Checkboxes)</option>
                    <option value="true_false">True / False</option>
                    <option value="short_answer">Short Answer</option>
                    <option value="essay">Essay</option>
                    <option value="matching">Matching</option>
                    <option value="fill_table">Fill Table</option>
                    <option value="arrange_steps">Arrange Steps</option>
                    <option value="case_study">Case Study</option>
                </select>
            </div>
            <div class="form-group">
                <label>Points</label>
                <input type="number" id="new_question_points" class="form-control" value="5" min="1">
            </div>
        </div>
        
        <div class="form-group">
            <label>Question Text</label>
            <textarea id="new_question_text" class="form-control" rows="2" placeholder="Enter your question here..."></textarea>
        </div>
        
        <div id="new_mc_fields" class="question-type-fields">
            <div class="options-grid">
                <div class="option-row"><input type="radio" name="new_correct_option" value="0"><input type="text" id="new_opt_a" class="option-input" placeholder="Option A"></div>
                <div class="option-row"><input type="radio" name="new_correct_option" value="1"><input type="text" id="new_opt_b" class="option-input" placeholder="Option B"></div>
                <div class="option-row"><input type="radio" name="new_correct_option" value="2"><input type="text" id="new_opt_c" class="option-input" placeholder="Option C"></div>
                <div class="option-row"><input type="radio" name="new_correct_option" value="3"><input type="text" id="new_opt_d" class="option-input" placeholder="Option D"></div>
            </div>
        </div>
        
        <div id="new_ms_fields" class="question-type-fields" style="display:none;">
            <div class="options-grid">
                <div class="option-row"><input type="checkbox" value="0"><input type="text" id="new_ms_a" class="option-input" placeholder="Option A"></div>
                <div class="option-row"><input type="checkbox" value="1"><input type="text" id="new_ms_b" class="option-input" placeholder="Option B"></div>
                <div class="option-row"><input type="checkbox" value="2"><input type="text" id="new_ms_c" class="option-input" placeholder="Option C"></div>
                <div class="option-row"><input type="checkbox" value="3"><input type="text" id="new_ms_d" class="option-input" placeholder="Option D"></div>
            </div>
        </div>
        
        <div id="new_tf_fields" class="question-type-fields" style="display:none;">
            <div class="option-row">
                <input type="radio" name="new_tf_correct" value="true"> True
                <input type="radio" name="new_tf_correct" value="false"> False
            </div>
        </div>
        
        <div id="new_sa_fields" class="question-type-fields" style="display:none;">
            <div class="form-group">
                <label>Model Answer</label>
                <textarea id="new_sa_answer" class="form-control" rows="2" placeholder="Enter the expected answer..."></textarea>
            </div>
        </div>

        <div id="new_essay_fields" class="question-type-fields" style="display:none;">
            <div class="form-group">
                <label>Model Answer / Rubric</label>
                <textarea id="new_essay_answer" class="form-control" rows="3" placeholder="Provide a model answer or rubric for marking..."></textarea>
            </div>
        </div>

        <div id="new_matching_fields" class="question-type-fields" style="display:none;">
            <div class="form-group">
                <label>Left Column (items, one per line)</label>
                <textarea id="new_match_left" class="form-control" rows="3" placeholder="Item 1&#10;Item 2&#10;Item 3"></textarea>
            </div>
            <div class="form-group">
                <label>Right Column (descriptions, one per line)</label>
                <textarea id="new_match_right" class="form-control" rows="3" placeholder="Description A&#10;Description B&#10;Description C"></textarea>
            </div>
            <div class="form-group">
                <label>Matching Answer (e.g., 1-A, 2-B, 3-C)</label>
                <input type="text" id="new_match_answer" class="form-control" placeholder="1-A, 2-B, 3-C">
            </div>
        </div>

        <div id="new_filltable_fields" class="question-type-fields" style="display:none;">
            <div class="form-group">
                <label>Table Structure</label>
                <textarea id="new_filltable_structure" class="form-control" rows="3" placeholder="| Concept | Definition | Example |"></textarea>
            </div>
            <div class="form-group">
                <label>Model Answer</label>
                <textarea id="new_filltable_answer" class="form-control" rows="3" placeholder="Provide the completed table..."></textarea>
            </div>
        </div>

        <div id="new_arrange_fields" class="question-type-fields" style="display:none;">
            <div class="form-group">
                <label>Steps (in random order, one per line)</label>
                <textarea id="new_arrange_steps" class="form-control" rows="3" placeholder="Step A&#10;Step B&#10;Step C"></textarea>
            </div>
            <div class="form-group">
                <label>Correct Order</label>
                <input type="text" id="new_arrange_answer" class="form-control" placeholder="e.g., 3, 1, 4, 2">
            </div>
        </div>

        <div id="new_casestudy_fields" class="question-type-fields" style="display:none;">
            <div class="form-group">
                <label>Scenario</label>
                <textarea id="new_casestudy_scenario" class="form-control" rows="3" placeholder="Describe the scenario..."></textarea>
            </div>
            <div class="form-group">
                <label>Follow-up Questions</label>
                <textarea id="new_casestudy_questions" class="form-control" rows="2" placeholder="Questions to answer based on the scenario..."></textarea>
            </div>
            <div class="form-group">
                <label>Model Answer</label>
                <textarea id="new_casestudy_answer" class="form-control" rows="3" placeholder="Provide model answers..."></textarea>
            </div>
        </div>
        
        <button class="btn-add-question-submit" onclick="addNewQuestion()">+ Add Question</button>
    </div>

    <div class="questions-list">
        <h3>📋 Quiz Questions (<span id="question_count"><?php echo count($quizQuestions); ?></span> questions | Total: <span id="total_marks_span"><?php echo $totalMarks; ?></span> marks)</h3>
        
        <div id="questions_container" class="questions-container">
            <?php if(empty($quizQuestions)): ?>
                <div class="empty-questions" id="empty_questions">No questions yet. Add your first question above.</div>
            <?php else: ?>
                <?php foreach($quizQuestions as $index => $q): 
                    $options = json_decode($q['options_json'], true);
                ?>
                <div class="question-item" data-id="<?php echo $q['question_id']; ?>" data-order="<?php echo $q['order_number']; ?>">
                    <div class="question-drag-handle">⋮⋮</div>
                    <div class="question-content">
                        <div class="question-header">
                            <span class="q-num">Q<?php echo $index + 1; ?></span>
                            <span class="q-type"><?php echo str_replace('_', ' ', ucfirst($q['question_type'])); ?></span>
                            <span class="q-points"><?php echo $q['points']; ?> pts</span>
                        </div>
                        <div class="q-text"><?php echo nl2br(htmlspecialchars($q['question_text'])); ?></div>
                        <div class="q-actions">
                            <a href="?module_id=<?php echo $selectedModuleId; ?>&topic_id=<?php echo $selectedTopicId; ?>&edit=<?php echo $q['question_id']; ?>" class="btn-edit-q">✏️ Edit</a>
                            <button class="btn-delete-q" onclick="deleteQuestion(<?php echo $q['question_id']; ?>)">🗑️ Delete</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="action-buttons">
        <button class="btn-save-all" onclick="saveAllQuiz()">💾 Save All Quiz Questions</button>
        <button class="btn-preview" onclick="previewQuiz()">👁️ Preview Quiz</button>
    </div>
    
    <?php endif; ?>
</div>

<?php if($editQuestion): ?>
<div class="modal show" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>✏️ Edit Question</h2>
            <a href="?module_id=<?php echo $selectedModuleId; ?>&topic_id=<?php echo $selectedTopicId; ?>" class="close">&times;</a>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_question">
            <input type="hidden" name="question_id" value="<?php echo $editQuestion['question_id']; ?>">
            <input type="hidden" name="order_num" value="<?php echo $editQuestion['order_number']; ?>">
            
            <div class="form-group">
                <label>Question Type</label>
                <select name="question_type" class="form-control" disabled>
                    <option value="<?php echo $editQuestion['question_type']; ?>"><?php echo ucfirst(str_replace('_', ' ', $editQuestion['question_type'])); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Points</label>
                <input type="number" name="points" class="form-control" value="<?php echo $editQuestion['points']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Question Text</label>
                <textarea name="question_text" class="form-control" rows="3" required><?php echo htmlspecialchars($editQuestion['question_text']); ?></textarea>
            </div>
            
            <?php 
            $options = json_decode($editQuestion['options_json'], true);
            $correct = $editQuestion['correct_answer'];
            ?>
            
            <?php if($editQuestion['question_type'] == 'multiple_choice'): ?>
                <div class="options-grid">
                    <?php for($i = 0; $i < 4; $i++): ?>
                    <div class="option-row">
                        <input type="radio" name="correct_option" value="<?php echo $i; ?>" <?php echo ($correct == $i) ? 'checked' : ''; ?>>
                        <input type="text" name="option_<?php echo chr(97+$i); ?>" class="option-input" value="<?php echo htmlspecialchars($options[$i] ?? ''); ?>" placeholder="Option <?php echo chr(65+$i); ?>">
                    </div>
                    <?php endfor; ?>
                </div>
            <?php elseif($editQuestion['question_type'] == 'true_false'): ?>
                <div class="option-row">
                    <input type="radio" name="correct_tf" value="true" <?php echo $correct == 'true' ? 'checked' : ''; ?>> True
                    <input type="radio" name="correct_tf" value="false" <?php echo $correct == 'false' ? 'checked' : ''; ?>> False
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label>Answer</label>
                    <textarea name="correct_answer" class="form-control" rows="3"><?php echo htmlspecialchars($correct); ?></textarea>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn-save-edit">Save Changes</button>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.quiz-builder{max-width:1100px;margin:0 auto;padding:30px 20px;}
.selection-area{display:flex;gap:20px;margin-bottom:30px;}
.form-select{flex:1;padding:12px;border:1px solid #ddd;border-radius:8px;}
.settings-card{background:white;border-radius:16px;padding:20px;margin-bottom:25px;}
.settings-row{display:flex;gap:15px;}
.setting{flex:1;}
.add-question-card{background:white;border-radius:16px;padding:25px;margin-bottom:25px;}
.form-row{display:flex;gap:20px;margin-bottom:20px;}
.form-group{flex:1;margin-bottom:15px;}
.form-group label{display:block;margin-bottom:5px;font-weight:500;}
.form-control{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;}
.options-grid{background:#f8f9fa;padding:15px;border-radius:8px;margin-top:10px;}
.option-row{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
.option-input{flex:1;padding:8px;border:1px solid #ddd;border-radius:6px;}
.question-type-fields{margin-top:15px;padding-top:15px;border-top:1px solid #eee;}
.btn-add-question-submit{background:#4CAF50;color:white;border:none;padding:10px 20px;border-radius:30px;cursor:pointer;margin-top:15px;}
.questions-list{background:white;border-radius:16px;padding:20px;}
.questions-container{display:flex;flex-direction:column;gap:12px;margin-top:15px;}
.question-item{display:flex;align-items:center;gap:15px;padding:15px;background:#f8f9fa;border-radius:12px;}
.question-drag-handle{font-size:20px;color:#999;cursor:grab;}
.question-content{flex:1;}
.question-header{display:flex;gap:12px;margin-bottom:8px;flex-wrap:wrap;}
.q-num{font-weight:bold;color:#667eea;}
.q-type{font-size:12px;padding:2px 8px;border-radius:20px;background:#e0e0e0;}
.q-points{font-size:12px;padding:2px 8px;border-radius:20px;background:#e8f5e9;color:#4CAF50;}
.q-text{margin-bottom:10px;color:#333;}
.q-actions{display:flex;gap:10px;}
.btn-edit-q{color:#ff9800;text-decoration:none;font-size:13px;}
.btn-delete-q{background:#f44336;color:white;border:none;padding:4px 10px;border-radius:15px;cursor:pointer;}
.empty-questions{text-align:center;padding:40px;color:#999;}
.action-buttons{display:flex;gap:15px;justify-content:center;margin-top:25px;}
.btn-save-all{background:#4CAF50;color:white;border:none;padding:12px 30px;border-radius:30px;cursor:pointer;font-size:16px;}
.btn-preview{background:#ff8c42;color:white;border:none;padding:12px 30px;border-radius:30px;cursor:pointer;font-size:16px;}
.alert{padding:12px;border-radius:10px;margin-bottom:15px;}
.alert-success{background:#e8f5e9;color:#2e7d32;}
.modal{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:1000;}
.modal-content{background:white;border-radius:20px;width:600px;max-width:90%;padding:25px;}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.close{font-size:28px;text-decoration:none;color:#999;}
.btn-save-edit{background:#4CAF50;color:white;border:none;padding:10px 20px;border-radius:30px;cursor:pointer;margin-top:15px;}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
let questionCount = <?php echo count($quizQuestions); ?>;

function updateTopics() {
    var moduleId = document.getElementById('module_select').value;
    if(moduleId) window.location.href = '?module_id=' + moduleId;
}

function loadQuiz() {
    var moduleId = document.getElementById('module_select').value;
    var topicId = document.getElementById('topic_select').value;
    if(topicId) window.location.href = '?module_id=' + moduleId + '&topic_id=' + topicId;
}

function showNewQuestionTypeFields() {
    var type = document.getElementById('new_question_type').value;
    var fields = ['new_mc_fields','new_ms_fields','new_tf_fields','new_sa_fields','new_essay_fields','new_matching_fields','new_filltable_fields','new_arrange_fields','new_casestudy_fields'];
    fields.forEach(function(id){ document.getElementById(id).style.display = 'none'; });
    
    if(type === 'multiple_choice') document.getElementById('new_mc_fields').style.display = 'block';
    else if(type === 'multiple_selection') document.getElementById('new_ms_fields').style.display = 'block';
    else if(type === 'true_false') document.getElementById('new_tf_fields').style.display = 'block';
    else if(type === 'short_answer') document.getElementById('new_sa_fields').style.display = 'block';
    else if(type === 'essay') document.getElementById('new_essay_fields').style.display = 'block';
    else if(type === 'matching') document.getElementById('new_matching_fields').style.display = 'block';
    else if(type === 'fill_table') document.getElementById('new_filltable_fields').style.display = 'block';
    else if(type === 'arrange_steps') document.getElementById('new_arrange_fields').style.display = 'block';
    else if(type === 'case_study') document.getElementById('new_casestudy_fields').style.display = 'block';
}

function addNewQuestion() {
    var type = document.getElementById('new_question_type').value;
    var text = document.getElementById('new_question_text').value;
    var points = document.getElementById('new_question_points').value;
    
    if(!text.trim()) { alert('Please enter question text.'); return; }
    
    var container = document.getElementById('questions_container');
    var emptyMsg = document.getElementById('empty_questions');
    if(emptyMsg) emptyMsg.remove();
    
    var index = questionCount;
    var div = document.createElement('div');
    div.className = 'question-item temp-question';
    div.innerHTML = `
        <div class="question-drag-handle">⋮⋮</div>
        <div class="question-content">
            <div class="question-header">
                <span class="q-num">Q${index + 1}</span>
                <span class="q-type">${type.replace(/_/g, ' ')}</span>
                <span class="q-points">${points} pts</span>
            </div>
            <div class="q-text">${text.replace(/</g,'&lt;')}</div>
        </div>
    `;
    container.appendChild(div);
    questionCount++;
    
    document.getElementById('new_question_text').value = '';
    document.getElementById('new_opt_a').value = '';
    document.getElementById('new_opt_b').value = '';
    document.getElementById('new_opt_c').value = '';
    document.getElementById('new_opt_d').value = '';
    document.getElementById('new_sa_answer').value = '';
    document.getElementById('new_essay_answer').value = '';
    document.getElementById('new_match_left').value = '';
    document.getElementById('new_match_right').value = '';
    document.getElementById('new_match_answer').value = '';
    document.getElementById('new_filltable_structure').value = '';
    document.getElementById('new_filltable_answer').value = '';
    document.getElementById('new_arrange_steps').value = '';
    document.getElementById('new_arrange_answer').value = '';
    document.getElementById('new_casestudy_scenario').value = '';
    document.getElementById('new_casestudy_questions').value = '';
    document.getElementById('new_casestudy_answer').value = '';
    
    document.getElementById('question_count').innerText = questionCount;
    
    alert('Question added. Click "Save All Quiz Questions" to save to database.');
}

function deleteQuestion(questionId) {
    if(confirm('Delete this question?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete_question"><input type="hidden" name="question_id" value="' + questionId + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function saveAllQuiz() {
    var moduleId = document.getElementById('module_select').value;
    var topicId = document.getElementById('topic_select').value;
    var passingScore = document.getElementById('passing_score_input').value;
    var timeLimit = document.getElementById('time_limit_input').value;
    
    var questions = [];
    var cards = document.querySelectorAll('.question-item');
    
    cards.forEach((card) => {
        if(card.classList.contains('temp-question')) return;
        var questionId = card.dataset.id;
        if(questionId) {
            questions.push({id: questionId});
        }
    });
    
    var form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="action" value="reorder_questions"><input type="hidden" name="order_data" value=\'' + JSON.stringify(questions) + '\'>';
    document.body.appendChild(form);
    form.submit();
}

function previewQuiz() {
    var topicId = document.getElementById('topic_select').value;
    if(topicId) {
        window.open('/teacher/quiz-preview.php?topic_id=' + topicId, '_blank');
    } else {
        alert('Please select a topic first');
    }
}

if(document.getElementById('questions_container')) {
    new Sortable(document.getElementById('questions_container'), {
        handle: '.question-drag-handle',
        onEnd: function() {
            var items = document.querySelectorAll('.question-item');
            items.forEach((item, idx) => {
                item.querySelector('.q-num').innerText = 'Q' + (idx + 1);
            });
        }
    });
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>