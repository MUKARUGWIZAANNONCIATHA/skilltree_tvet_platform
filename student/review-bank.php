<?php
/**
 * Student Review Bank – Practice Questions
 * Path: /student/review-bank.php
 */

require_once '../config/database.php';
require_once 'includes/auth.php';

// Get student's enrolled modules
$stmt = $pdo->prepare("
    SELECT DISTINCT m.module_id, m.module_code, m.module_name
    FROM student_enrollments e
    JOIN modules m ON e.module_id = m.module_id
    WHERE e.student_id = ? AND e.status != 'dropped'
    ORDER BY m.module_code
");
$stmt->execute([$studentId]);
$modules = $stmt->fetchAll();

$selectedModule = isset($_GET['module_id']) ? intval($_GET['module_id']) : ($modules[0]['module_id'] ?? 0);
$selectedTopic = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$selectedBloom = $_GET['bloom'] ?? '';
$selectedDifficulty = $_GET['difficulty'] ?? '';

// Get topics for selected module
$topics = [];
if ($selectedModule) {
    $stmt = $pdo->prepare("
        SELECT t.topic_id, t.topic_title, lo.outcome_number, ic.ic_title
        FROM topics t
        JOIN indicative_contents ic ON t.ic_id = ic.ic_id
        JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
        WHERE lo.module_id = ?
        ORDER BY lo.outcome_number, ic.ic_order, t.topic_order
    ");
    $stmt->execute([$selectedModule]);
    $topics = $stmt->fetchAll();
}

// Build query for questions
$sql = "SELECT * FROM review_bank WHERE status = 'approved'";
$params = [];
if ($selectedModule) {
    $sql .= " AND module_id = ?";
    $params[] = $selectedModule;
}
if ($selectedTopic) {
    $sql .= " AND topic_id = ?";
    $params[] = $selectedTopic;
}
if ($selectedBloom) {
    $sql .= " AND bloom_level = ?";
    $params[] = $selectedBloom;
}
if ($selectedDifficulty) {
    $sql .= " AND difficulty = ?";
    $params[] = $selectedDifficulty;
}
$sql .= " ORDER BY RAND() LIMIT 20"; // Random selection for practice
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questions = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="review-bank-container">
    <div class="page-header">
        <h1><i class="fas fa-database"></i> Review Bank – Practice Questions</h1>
        <p>Test your knowledge with questions from your modules</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label>Module</label>
                <select name="module_id" onchange="this.form.submit()">
                    <option value="0">All Modules</option>
                    <?php foreach ($modules as $mod): ?>
                        <option value="<?= $mod['module_id'] ?>" <?= $selectedModule == $mod['module_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mod['module_code']) ?> – <?= htmlspecialchars($mod['module_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Topic</label>
                <select name="topic_id" onchange="this.form.submit()">
                    <option value="0">All Topics</option>
                    <?php foreach ($topics as $topic): ?>
                        <option value="<?= $topic['topic_id'] ?>" <?= $selectedTopic == $topic['topic_id'] ? 'selected' : '' ?>>
                            LO<?= $topic['outcome_number'] ?>: <?= htmlspecialchars($topic['topic_title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Bloom Level</label>
                <select name="bloom" onchange="this.form.submit()">
                    <option value="">All Levels</option>
                    <option value="remember" <?= $selectedBloom == 'remember' ? 'selected' : '' ?>>Remember</option>
                    <option value="understand" <?= $selectedBloom == 'understand' ? 'selected' : '' ?>>Understand</option>
                    <option value="apply" <?= $selectedBloom == 'apply' ? 'selected' : '' ?>>Apply</option>
                    <option value="analyze" <?= $selectedBloom == 'analyze' ? 'selected' : '' ?>>Analyze</option>
                    <option value="evaluate" <?= $selectedBloom == 'evaluate' ? 'selected' : '' ?>>Evaluate</option>
                    <option value="create" <?= $selectedBloom == 'create' ? 'selected' : '' ?>>Create</option>
                </select>
            </div>
            <div class="form-group">
                <label>Difficulty</label>
                <select name="difficulty" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="easy" <?= $selectedDifficulty == 'easy' ? 'selected' : '' ?>>Easy</option>
                    <option value="medium" <?= $selectedDifficulty == 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="hard" <?= $selectedDifficulty == 'hard' ? 'selected' : '' ?>>Hard</option>
                </select>
            </div>
            <?php if ($selectedModule || $selectedTopic || $selectedBloom || $selectedDifficulty): ?>
                <a href="review-bank.php" class="btn-clear">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Questions List -->
    <?php if (empty($questions)): ?>
        <div class="empty-state">
            <i class="fas fa-question-circle"></i>
            <p>No questions found for the selected filters. Please adjust your criteria.</p>
        </div>
    <?php else: ?>
        <div class="questions-list">
            <?php foreach ($questions as $index => $q): ?>
                <div class="question-card" data-qid="<?= $q['review_id'] ?>">
                    <div class="question-header">
                        <span class="q-number">Q<?= $index + 1 ?></span>
                        <span class="q-bloom <?= $q['bloom_level'] ?>"><?= ucfirst($q['bloom_level']) ?></span>
                        <span class="q-difficulty <?= $q['difficulty'] ?>"><?= ucfirst($q['difficulty']) ?></span>
                        <span class="q-type"><?= str_replace('_', ' ', $q['question_type']) ?></span>
                    </div>
                    <div class="question-text"><?= nl2br(htmlspecialchars($q['question_text'])) ?></div>
                    <div class="question-actions">
                        <button class="btn-show-answer" onclick="toggleAnswer(this)">Show Answer</button>
                    </div>
                    <div class="question-answer" style="display: none;">
                        <strong>Model Answer:</strong>
                        <p><?= nl2br(htmlspecialchars($q['model_answer'])) ?></p>
                        <?php if ($q['explanation']): ?>
                            <div class="question-explanation">
                                <strong>Explanation:</strong>
                                <p><?= nl2br(htmlspecialchars($q['explanation'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .review-bank-container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .page-header h1 {
        font-size: 2rem;
        color: #1a5f7a;
    }
    .filter-bar {
        background: white;
        border-radius: 1rem;
        padding: 1rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: flex-end;
    }
    .form-group {
        flex: 1;
        min-width: 150px;
    }
    .form-group label {
        display: block;
        font-size: 0.75rem;
        margin-bottom: 0.2rem;
        color: #6c8faa;
    }
    .form-group select {
        width: 100%;
        padding: 0.4rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        background: white;
    }
    .btn-clear {
        background: #f44336;
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 0.5rem;
        text-decoration: none;
        font-size: 0.75rem;
        align-self: center;
    }
    .questions-list {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
    }
    .question-card {
        background: white;
        border-radius: 1rem;
        padding: 1.2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: 0.2s;
    }
    .question-header {
        display: flex;
        gap: 0.6rem;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 0.8rem;
    }
    .q-number {
        font-weight: 700;
        color: #1a5f7a;
        background: #eef2fa;
        padding: 0.2rem 0.5rem;
        border-radius: 1rem;
        font-size: 0.7rem;
    }
    .q-bloom {
        padding: 0.2rem 0.5rem;
        border-radius: 1rem;
        font-size: 0.7rem;
        color: white;
    }
    .q-bloom.remember { background: #4CAF50; }
    .q-bloom.understand { background: #2196F3; }
    .q-bloom.apply { background: #FF9800; }
    .q-bloom.analyze { background: #9C27B0; }
    .q-bloom.evaluate { background: #F44336; }
    .q-bloom.create { background: #009688; }
    .q-difficulty {
        padding: 0.2rem 0.5rem;
        border-radius: 1rem;
        font-size: 0.7rem;
        color: white;
    }
    .q-difficulty.easy { background: #4CAF50; }
    .q-difficulty.medium { background: #ff9800; }
    .q-difficulty.hard { background: #f44336; }
    .q-type {
        padding: 0.2rem 0.5rem;
        border-radius: 1rem;
        font-size: 0.7rem;
        background: #eef2fa;
        color: #2c7da0;
    }
    .question-text {
        font-size: 0.95rem;
        margin-bottom: 1rem;
        line-height: 1.5;
    }
    .question-actions {
        text-align: right;
    }
    .btn-show-answer {
        background: #2c7da0;
        color: white;
        border: none;
        padding: 0.3rem 0.8rem;
        border-radius: 1.5rem;
        cursor: pointer;
        font-size: 0.75rem;
    }
    .question-answer {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eef2f8;
        background: #f8fafc;
        padding: 0.8rem;
        border-radius: 0.5rem;
    }
    .question-answer strong {
        color: #1a5f7a;
    }
    .question-explanation {
        margin-top: 0.5rem;
    }
    .empty-state {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 1rem;
        color: #8aaec0;
    }
    @media (max-width: 700px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
        .btn-clear {
            text-align: center;
        }
    }
</style>

<script>
    function toggleAnswer(btn) {
        const answerDiv = btn.closest('.question-card').querySelector('.question-answer');
        if (answerDiv.style.display === 'none') {
            answerDiv.style.display = 'block';
            btn.textContent = 'Hide Answer';
        } else {
            answerDiv.style.display = 'none';
            btn.textContent = 'Show Answer';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>