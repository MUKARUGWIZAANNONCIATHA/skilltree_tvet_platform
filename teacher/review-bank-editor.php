 
<?php
/**
 * Review Bank Editor - Edit, update, delete questions
 * Path: /teacher/review-bank-editor.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';
require_once '../includes/functions/validation.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$message = '';
$error = '';

// Get modules for filter
if ($role === 'admin') {
    $modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT module_id, module_code, module_name FROM modules WHERE created_by = ? ORDER BY module_code");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
}

$selectedModuleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$selectedTopicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$editId = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;

// Get topics for selected module
$topics = [];
if ($selectedModuleId > 0) {
    $stmt = $pdo->prepare("
        SELECT t.topic_id, t.topic_title, lo.outcome_number
        FROM topics t
        JOIN indicative_contents ic ON t.ic_id = ic.ic_id
        JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
        WHERE lo.module_id = ?
        ORDER BY lo.outcome_number, ic.ic_order, t.topic_order
    ");
    $stmt->execute([$selectedModuleId]);
    $topics = $stmt->fetchAll();
}

// Fetch question for editing
$editQuestion = null;
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM review_bank WHERE review_id = ?");
    $stmt->execute([$editId]);
    $editQuestion = $stmt->fetch();
    if (!$editQuestion) {
        $error = "Question not found.";
        $editId = 0;
    }
}

// Handle form submission (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_question'])) {
    $questionId = intval($_POST['question_id']);
    $topicId = intval($_POST['topic_id']) ?: null;
    $questionType = sanitizeInput($_POST['question_type']);
    $questionText = sanitizeInput($_POST['question_text']);
    $bloomLevel = sanitizeInput($_POST['bloom_level']);
    $difficulty = sanitizeInput($_POST['difficulty']);
    $complexity = sanitizeInput($_POST['complexity']);
    $modelAnswer = sanitizeInput($_POST['model_answer']);
    $explanation = sanitizeInput($_POST['explanation']);
    $marks = intval($_POST['marks']);

    if (empty($questionText)) {
        $error = "Question text is required.";
    } else {
        $stmt = $pdo->prepare("UPDATE review_bank SET topic_id=?, question_type=?, question_text=?, bloom_level=?, difficulty=?, complexity=?, model_answer=?, explanation=?, marks=? WHERE review_id=?");
        $stmt->execute([$topicId, $questionType, $questionText, $bloomLevel, $difficulty, $complexity, $modelAnswer, $explanation, $marks, $questionId]);
        $message = "Question updated successfully.";
        // Refresh editQuestion data
        $editQuestion['topic_id'] = $topicId;
        $editQuestion['question_type'] = $questionType;
        $editQuestion['question_text'] = $questionText;
        $editQuestion['bloom_level'] = $bloomLevel;
        $editQuestion['difficulty'] = $difficulty;
        $editQuestion['complexity'] = $complexity;
        $editQuestion['model_answer'] = $modelAnswer;
        $editQuestion['explanation'] = $explanation;
        $editQuestion['marks'] = $marks;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM review_bank WHERE review_id = ?");
    $stmt->execute([$delId]);
    $message = "Question deleted.";
    header("Location: review-bank-editor.php?module_id=$selectedModuleId&topic_id=$selectedTopicId&deleted=1");
    exit;
}

// Fetch questions list for the selected module/topic
$questions = [];
if ($selectedModuleId > 0) {
    $sql = "SELECT r.*, t.topic_title FROM review_bank r LEFT JOIN topics t ON r.topic_id = t.topic_id WHERE r.module_id = ?";
    $params = [$selectedModuleId];
    if ($selectedTopicId > 0) {
        $sql .= " AND r.topic_id = ?";
        $params[] = $selectedTopicId;
    }
    $sql .= " ORDER BY r.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $questions = $stmt->fetchAll();
}

include_once '../includes/templates/header.php';
?>

<div class="review-bank-editor">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Review Bank Editor</h1>
        <p>Edit, update, or delete questions in the review bank</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Module & Topic Filter -->
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label>Module</label>
                <select name="module_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Select Module --</option>
                    <?php foreach ($modules as $mod): ?>
                        <option value="<?= $mod['module_id'] ?>" <?= $selectedModuleId == $mod['module_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mod['module_code']) ?> - <?= htmlspecialchars($mod['module_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($selectedModuleId > 0): ?>
                <div class="form-group">
                    <label>Topic</label>
                    <select name="topic_id" class="form-control" onchange="this.form.submit()">
                        <option value="0">-- All Topics --</option>
                        <?php foreach ($topics as $topic): ?>
                            <option value="<?= $topic['topic_id'] ?>" <?= $selectedTopicId == $topic['topic_id'] ? 'selected' : '' ?>>
                                LO<?= $topic['outcome_number'] ?>: <?= htmlspecialchars($topic['topic_title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn-filter">Filter</button>
            <?php if ($selectedModuleId): ?>
                <a href="review-bank-editor.php" class="btn-clear">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Questions List -->
    <?php if ($selectedModuleId > 0): ?>
        <div class="questions-list">
            <h2>Questions</h2>
            <?php if (empty($questions)): ?>
                <div class="empty-state">No questions found for this module/topic.</div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="questions-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Topic</th>
                                <th>Type</th>
                                <th>Bloom</th>
                                <th>Question Preview</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $q): ?>
                                <tr>
                                    <td><?= $q['review_id'] ?></td>
                                    <td><?= htmlspecialchars($q['topic_title'] ?? '—') ?></td>
                                    <td><?= str_replace('_', ' ', $q['question_type']) ?></td>
                                    <td><span class="bloom-badge <?= $q['bloom_level'] ?>"><?= ucfirst($q['bloom_level']); ?></span></td>
                                    <td class="preview"><?= htmlspecialchars(substr($q['question_text'], 0, 80)) ?>...
                                    <td class="actions">
                                        <a href="?module_id=<?= $selectedModuleId ?>&topic_id=<?= $selectedTopicId ?>&edit_id=<?= $q['review_id'] ?>" class="btn-edit">✏️ Edit</a>
                                        <a href="?module_id=<?= $selectedModuleId ?>&topic_id=<?= $selectedTopicId ?>&delete=<?= $q['review_id'] ?>" class="btn-delete" onclick="return confirm('Delete this question?')">🗑️ Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <?php if ($editQuestion): ?>
        <div class="edit-form">
            <h2><i class="fas fa-pen"></i> Edit Question</h2>
            <form method="post">
                <input type="hidden" name="update_question" value="1">
                <input type="hidden" name="question_id" value="<?= $editQuestion['review_id'] ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label>Topic</label>
                        <select name="topic_id" class="form-control">
                            <option value="0">-- No Topic --</option>
                            <?php foreach ($topics as $topic): ?>
                                <option value="<?= $topic['topic_id'] ?>" <?= $editQuestion['topic_id'] == $topic['topic_id'] ? 'selected' : '' ?>>
                                    LO<?= $topic['outcome_number'] ?>: <?= htmlspecialchars($topic['topic_title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Question Type</label>
                        <select name="question_type" class="form-control">
                            <option value="multiple_choice" <?= $editQuestion['question_type'] == 'multiple_choice' ? 'selected' : '' ?>>Multiple Choice</option>
                            <option value="true_false" <?= $editQuestion['question_type'] == 'true_false' ? 'selected' : '' ?>>True/False</option>
                            <option value="short_answer" <?= $editQuestion['question_type'] == 'short_answer' ? 'selected' : '' ?>>Short Answer</option>
                            <option value="essay" <?= $editQuestion['question_type'] == 'essay' ? 'selected' : '' ?>>Essay</option>
                            <option value="sentence_completion" <?= $editQuestion['question_type'] == 'sentence_completion' ? 'selected' : '' ?>>Sentence Completion</option>
                            <option value="multiple_selection" <?= $editQuestion['question_type'] == 'multiple_selection' ? 'selected' : '' ?>>Multiple Selection</option>
                            <option value="matching" <?= $editQuestion['question_type'] == 'matching' ? 'selected' : '' ?>>Matching</option>
                            <option value="fill_table" <?= $editQuestion['question_type'] == 'fill_table' ? 'selected' : '' ?>>Fill Table</option>
                            <option value="arrange_steps" <?= $editQuestion['question_type'] == 'arrange_steps' ? 'selected' : '' ?>>Arrange Steps</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Bloom Level</label>
                        <select name="bloom_level" class="form-control">
                            <option value="remember" <?= $editQuestion['bloom_level'] == 'remember' ? 'selected' : '' ?>>Remember</option>
                            <option value="understand" <?= $editQuestion['bloom_level'] == 'understand' ? 'selected' : '' ?>>Understand</option>
                            <option value="apply" <?= $editQuestion['bloom_level'] == 'apply' ? 'selected' : '' ?>>Apply</option>
                            <option value="analyze" <?= $editQuestion['bloom_level'] == 'analyze' ? 'selected' : '' ?>>Analyze</option>
                            <option value="evaluate" <?= $editQuestion['bloom_level'] == 'evaluate' ? 'selected' : '' ?>>Evaluate</option>
                            <option value="create" <?= $editQuestion['bloom_level'] == 'create' ? 'selected' : '' ?>>Create</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Difficulty</label>
                        <select name="difficulty" class="form-control">
                            <option value="easy" <?= $editQuestion['difficulty'] == 'easy' ? 'selected' : '' ?>>Easy</option>
                            <option value="medium" <?= $editQuestion['difficulty'] == 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="hard" <?= $editQuestion['difficulty'] == 'hard' ? 'selected' : '' ?>>Hard</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Complexity</label>
                        <select name="complexity" class="form-control">
                            <option value="basic" <?= ($editQuestion['complexity'] ?? 'intermediate') == 'basic' ? 'selected' : '' ?>>Basic</option>
                            <option value="intermediate" <?= ($editQuestion['complexity'] ?? 'intermediate') == 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                            <option value="advanced" <?= ($editQuestion['complexity'] ?? 'intermediate') == 'advanced' ? 'selected' : '' ?>>Advanced</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Marks</label>
                        <input type="number" name="marks" class="form-control" value="<?= $editQuestion['marks'] ?? 5 ?>" min="1" max="50">
                    </div>
                </div>

                <div class="form-group">
                    <label>Question Text</label>
                    <textarea name="question_text" class="form-control" rows="4" required><?= htmlspecialchars($editQuestion['question_text']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Model Answer / Rubric</label>
                    <textarea name="model_answer" class="form-control" rows="4" required><?= htmlspecialchars($editQuestion['model_answer']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Explanation (optional)</label>
                    <textarea name="explanation" class="form-control" rows="3"><?= htmlspecialchars($editQuestion['explanation']) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save">Save Changes</button>
                    <a href="review-bank-editor.php?module_id=<?= $selectedModuleId ?>&topic_id=<?= $selectedTopicId ?>" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
.review-bank-editor { max-width: 1200px; margin: 0 auto; padding: 20px; }
.page-header { margin-bottom: 20px; }
.filter-bar { background: white; border-radius: 12px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.filter-form { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
.btn-filter { background: #667eea; color: white; border: none; padding: 8px 20px; border-radius: 25px; cursor: pointer; }
.btn-clear { background: #999; color: white; padding: 8px 20px; border-radius: 25px; text-decoration: none; }
.questions-list { background: white; border-radius: 12px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.table-wrapper { overflow-x: auto; }
.questions-table { width: 100%; border-collapse: collapse; }
.questions-table th, .questions-table td { padding: 10px; text-align: left; border-bottom: 1px solid #eef2f8; }
.bloom-badge { padding: 2px 8px; border-radius: 15px; font-size: 11px; color: white; }
.bloom-badge.remember { background: #4CAF50; }
.bloom-badge.understand { background: #2196F3; }
.bloom-badge.apply { background: #FF9800; }
.bloom-badge.analyze { background: #9C27B0; }
.bloom-badge.evaluate { background: #F44336; }
.bloom-badge.create { background: #009688; }
.btn-edit { background: #ff9800; color: white; padding: 3px 10px; border-radius: 15px; text-decoration: none; font-size: 12px; display: inline-block; margin-right: 5px; }
.btn-delete { background: #f44336; color: white; padding: 3px 10px; border-radius: 15px; text-decoration: none; font-size: 12px; display: inline-block; }
.edit-form { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.form-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px; }
.form-group { flex: 1; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
.form-control { width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 6px; }
textarea.form-control { resize: vertical; }
.form-actions { margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end; }
.btn-save { background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 25px; cursor: pointer; }
.btn-cancel { background: #6c757d; color: white; padding: 8px 20px; border-radius: 25px; text-decoration: none; }
.alert { padding: 10px; border-radius: 8px; margin-bottom: 15px; }
.alert-success { background: #d4edda; color: #155724; }
.alert-error { background: #f8d7da; color: #721c24; }
.empty-state { text-align: center; padding: 40px; color: #6c757d; }
</style>

<?php include_once '../includes/templates/footer.php'; ?>