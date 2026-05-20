<?php
/**
 * AI Practice Exercises - Generate, Answer, Get Corrected, Track Weaknesses
 * Path: /student/ai-practice.php
 */

require_once '../config/database.php';
require_once 'includes/auth.php';
require_once '../includes/functions/ai-api.php';

// Ensure practice table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ai_practice_sessions (
        practice_id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        module_id INT DEFAULT NULL,
        topic_title VARCHAR(255) DEFAULT NULL,
        questions_json LONGTEXT,
        answers_json LONGTEXT,
        grading_json LONGTEXT,
        score INT DEFAULT 0,
        total_questions INT DEFAULT 0,
        correct_count INT DEFAULT 0,
        weaknesses TEXT,
        strengths TEXT,
        status ENUM('generated','completed') DEFAULT 'generated',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY student_id (student_id),
        KEY created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {}

// Get enrolled modules
$stmt = $pdo->prepare("
    SELECT m.module_id, m.module_code, m.module_name
    FROM student_enrollments e
    JOIN modules m ON e.module_id = m.module_id
    WHERE e.student_id = ? AND e.status != 'dropped'
");
$stmt->execute([$studentId]);
$modules = $stmt->fetchAll();

// Get previous practice sessions
$stmt = $pdo->prepare("
    SELECT * FROM ai_practice_sessions
    WHERE student_id = ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute([$studentId]);
$pastSessions = $stmt->fetchAll();

// Aggregate weaknesses across all sessions
$weaknessAgg = [];
foreach ($pastSessions as $s) {
    if (!empty($s['weaknesses'])) {
        $ws = explode(',', $s['weaknesses']);
        foreach ($ws as $w) {
            $w = trim($w);
            if (!empty($w)) $weaknessAgg[$w] = ($weaknessAgg[$w] ?? 0) + 1;
        }
    }
}
arsort($weaknessAgg);
$topWeaknesses = array_slice($weaknessAgg, 0, 8, true);

// Handle AJAX: Generate questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    header('Content-Type: application/json');

    if ($action === 'generate') {
        $moduleId = intval($_POST['module_id'] ?? 0);
        $topic = trim($_POST['topic'] ?? '');
        $numQuestions = min(intval($_POST['num_questions'] ?? 5), 10);

        if (empty($topic)) {
            echo json_encode(['error' => 'Please enter a topic or select a module.']);
            exit;
        }

        // Find module name if selected
        $moduleName = '';
        if ($moduleId > 0) {
            foreach ($modules as $m) {
                if ($m['module_id'] == $moduleId) { $moduleName = $m['module_name']; break; }
            }
        }

        $context = !empty($moduleName) ? "Module: $moduleName. " : "";
        $prompt = "You are a TVET exam preparer. Generate $numQuestions practice questions for a student studying: $context Topic: $topic.
        Include a mix of: multiple choice, true/false, short answer, and fill-in-the-blank questions.
        For each question, provide:
        - type (multiple_choice, true_false, short_answer, fill_blank)
        - question text
        - options (only for multiple_choice: array of 4 options)
        - correct_answer
        - explanation (brief explanation of the correct answer)

        Return as a JSON array ONLY, no other text. Example format:
        [{\"type\":\"multiple_choice\",\"question\":\"question text\",\"options\":[\"A\",\"B\",\"C\",\"D\"],\"correct_answer\":\"B\",\"explanation\":\"why B is correct\"},
         {\"type\":\"true_false\",\"question\":\"statement\",\"correct_answer\":\"True\",\"explanation\":\"explanation\"},
         {\"type\":\"short_answer\",\"question\":\"question?\",\"correct_answer\":\"answer\",\"explanation\":\"explanation\"}]";

        $messages = [['role' => 'system', 'content' => 'You are a TVET exam question generator. Return only valid JSON.']];
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $aiResponse = callAIAPI($messages, 'gpt-3.5-turbo', 2000, 0.7);

        // Try to parse JSON from response
        $questions = json_decode($aiResponse, true);
        if (!$questions) {
            // Fallback: generate questions manually
            $questions = generateFallbackQuestions($topic, $numQuestions);
        }

        // Store session
        $stmt = $pdo->prepare("INSERT INTO ai_practice_sessions (student_id, module_id, topic_title, questions_json, status) VALUES (?, ?, ?, ?, 'generated')");
        $stmt->execute([$studentId, $moduleId, $topic, json_encode($questions)]);
        $practiceId = $pdo->lastInsertId();

        echo json_encode([
            'practice_id' => $practiceId,
            'questions' => $questions,
            'topic' => $topic
        ]);
        exit;
    }

    if ($action === 'submit_answers') {
        $practiceId = intval($_POST['practice_id'] ?? 0);
        $answers = json_decode($_POST['answers'] ?? '[]', true);

        if (!$practiceId || empty($answers)) {
            echo json_encode(['error' => 'Invalid submission.']);
            exit;
        }

        // Get the session
        $stmt = $pdo->prepare("SELECT * FROM ai_practice_sessions WHERE practice_id = ? AND student_id = ?");
        $stmt->execute([$practiceId, $studentId]);
        $session = $stmt->fetch();

        if (!$session) {
            echo json_encode(['error' => 'Session not found.']);
            exit;
        }

        $questions = json_decode($session['questions_json'], true);
        if (!$questions) {
            echo json_encode(['error' => 'Questions data corrupted.']);
            exit;
        }

        // Grade each answer using AI
        $correctCount = 0;
        $gradedQuestions = [];
        $wrongTopics = [];

        foreach ($questions as $idx => &$q) {
            $studentAnswer = $answers[$idx] ?? '';
            $isCorrect = false;
            $feedback = '';

            // Auto-grade based on question type
            $correctAns = trim(strtolower($q['correct_answer'] ?? ''));
            $stuAns = trim(strtolower($studentAnswer));

            if ($q['type'] === 'multiple_choice') {
                $isCorrect = $stuAns === $correctAns;
                $feedback = $isCorrect ? 'Correct!' : "Incorrect. The correct answer is: {$q['correct_answer']}";
            } elseif ($q['type'] === 'true_false') {
                $isCorrect = $stuAns === $correctAns;
                $feedback = $isCorrect ? 'Correct!' : "Incorrect. The answer is: {$q['correct_answer']}";
            } elseif ($q['type'] === 'fill_blank') {
                // Check if answer contains key terms
                $keyTerms = explode(' ', $correctAns);
                $matchCount = 0;
                foreach ($keyTerms as $term) {
                    if (strlen($term) > 3 && strpos($stuAns, $term) !== false) $matchCount++;
                }
                $isCorrect = $matchCount >= min(count($keyTerms), 2);
                $feedback = $isCorrect ? 'Good job!' : "Expected answer: {$q['correct_answer']}";
            } else {
                // short_answer - use AI to grade
                $gradePrompt = "Grade this student answer. Question: {$q['question']}\nCorrect answer: {$q['correct_answer']}\nStudent answer: $studentAnswer\n\nRespond with ONLY: PASS or FAIL, then a brief comment.";
                $gradeMsgs = [['role' => 'system', 'content' => 'You are a strict but fair TVET teacher. Grade accurately.']];
                $gradeMsgs[] = ['role' => 'user', 'content' => $gradePrompt];
                $gradeResult = callAIAPI($gradeMsgs, 'gpt-3.5-turbo', 150, 0.3);
                $isCorrect = stripos($gradeResult, 'PASS') !== false;
                $feedback = $gradeResult;
            }

            if ($isCorrect) $correctCount++;

            $gradedQuestions[] = [
                'question' => $q['question'],
                'type' => $q['type'],
                'student_answer' => $studentAnswer,
                'correct_answer' => $q['correct_answer'],
                'explanation' => $q['explanation'] ?? '',
                'is_correct' => $isCorrect,
                'feedback' => $feedback
            ];

            if (!$isCorrect) {
                $wrongTopics[] = $q['question'];
            }
        }
        unset($q);

        $totalQuestions = count($questions);
        $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

        // Generate weakness analysis
        $weaknessText = '';
        $strengthText = '';
        if (!empty($wrongTopics)) {
            $weakPrompt = "Based on these incorrectly answered questions, identify 3-5 specific topics/areas the student needs to improve on. List them comma-separated, no other text:\n" . implode("\n", array_slice($wrongTopics, 0, 5));
            $weakMsgs = [['role' => 'system', 'content' => 'You are an educational analyst. List only comma-separated weakness topics.']];
            $weakMsgs[] = ['role' => 'user', 'content' => $weakPrompt];
            $weaknessText = callAIAPI($weakMsgs, 'gpt-3.5-turbo', 200, 0.3);
        }

        if ($correctCount > 0) {
            $strongPrompt = "Based on these correctly answered questions, identify 2-3 areas the student shows strength in. List them comma-separated, no other text:\n" . implode("\n", array_slice($gradedQuestions, 0, 5));
            $strongMsgs = [['role' => 'system', 'content' => 'You are an educational analyst. List only comma-separated strengths.']];
            $strongMsgs[] = ['role' => 'user', 'content' => "Questions the student got right: " . implode(", ", array_column(array_filter($gradedQuestions, fn($g) => $g['is_correct']), 'question'))];
            $strengthText = callAIAPI($strongMsgs, 'gpt-3.5-turbo', 200, 0.3);
        }

        // Save results
        $stmt = $pdo->prepare("UPDATE ai_practice_sessions SET
            answers_json = ?, grading_json = ?, score = ?, total_questions = ?,
            correct_count = ?, weaknesses = ?, strengths = ?, status = 'completed'
            WHERE practice_id = ? AND student_id = ?");
        $stmt->execute([
            json_encode($answers), json_encode($gradedQuestions),
            $score, $totalQuestions, $correctCount,
            $weaknessText, $strengthText, $practiceId, $studentId
        ]);

        echo json_encode([
            'score' => $score,
            'correct' => $correctCount,
            'total' => $totalQuestions,
            'graded' => $gradedQuestions,
            'weaknesses' => $weaknessText,
            'strengths' => $strengthText
        ]);
        exit;
    }

    if ($action === 'review') {
        $practiceId = intval($_POST['practice_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM ai_practice_sessions WHERE practice_id = ? AND student_id = ?");
        $stmt->execute([$practiceId, $studentId]);
        $session = $stmt->fetch();

        if (!$session || $session['status'] !== 'completed') {
            echo json_encode(['error' => 'Session not found or not yet completed.']);
            exit;
        }

        echo json_encode([
            'topic' => $session['topic_title'],
            'score' => $session['score'],
            'correct' => $session['correct_count'],
            'total' => $session['total_questions'],
            'graded' => json_decode($session['grading_json'], true),
            'weaknesses' => $session['weaknesses'],
            'strengths' => $session['strengths'],
            'date' => $session['created_at']
        ]);
        exit;
    }

    echo json_encode(['error' => 'Unknown action.']);
    exit;
}

function generateFallbackQuestions($topic, $num) {
    $questions = [];
    $types = ['multiple_choice', 'true_false', 'short_answer', 'fill_blank'];
    for ($i = 0; $i < $num; $i++) {
        $type = $types[$i % count($types)];
        $q = [
            'type' => $type,
            'question' => "Question " . ($i + 1) . " about $topic: Explain the key concept.",
            'correct_answer' => 'Sample answer for ' . $topic,
            'explanation' => 'This tests understanding of ' . $topic
        ];
        if ($type === 'multiple_choice') {
            $q['question'] = "Which of the following best describes $topic?";
            $q['options'] = ['Correct definition of ' . $topic, 'Wrong option A', 'Wrong option B', 'Wrong option C'];
            $q['correct_answer'] = 'A';
        } elseif ($type === 'true_false') {
            $q['question'] = "$topic is essential for TVET students.";
            $q['correct_answer'] = 'True';
        } elseif ($type === 'fill_blank') {
            $q['question'] = "The process of __________ is central to $topic.";
            $q['correct_answer'] = 'understanding and application';
        }
        $questions[] = $q;
    }
    return $questions;
}

include 'includes/header.php';
?>

<div class="practice-page">
    <div class="page-heading">
        <h1><i class="fas fa-graduation-cap"></i> AI Practice Exercises</h1>
        <p>Generate questions, test yourself, get AI-graded feedback, and track your improvement areas.</p>
    </div>

    <div class="practice-layout">
        <!-- LEFT: Controls & Previous sessions -->
        <div class="practice-sidebar">
            <!-- Generate Form -->
            <div class="card generate-card">
                <h3><i class="fas fa-robot"></i> Generate Practice</h3>
                <div class="form-group">
                    <label>Module (optional)</label>
                    <select id="moduleSelect" class="form-control">
                        <option value="">-- All Modules --</option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= $m['module_id'] ?>"><?= htmlspecialchars($m['module_code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Topic / Area to Practice</label>
                    <input type="text" id="topicInput" class="form-control" placeholder="e.g., Database Normalization, SQL Joins...">
                </div>
                <div class="form-group">
                    <label>Number of Questions</label>
                    <select id="numQuestions" class="form-control">
                        <?php for ($i = 3; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $i === 5 ? 'selected' : '' ?>><?= $i ?> questions</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button class="btn-generate" onclick="generatePractice()"><i class="fas fa-magic"></i> Generate Questions</button>
                <div id="generateStatus" class="status-msg" style="display:none;"></div>
            </div>

            <!-- Weakness Analysis -->
            <?php if (!empty($topWeaknesses)): ?>
            <div class="card weaknesses-card">
                <h3><i class="fas fa-exclamation-triangle"></i> Areas to Improve</h3>
                <div class="weakness-list">
                    <?php foreach ($topWeaknesses as $w => $c): ?>
                        <div class="weakness-item">
                            <span class="weakness-name"><?= htmlspecialchars($w) ?></span>
                            <span class="weakness-count"><?= $c ?>x</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Past Sessions -->
            <?php if (!empty($pastSessions)): ?>
            <div class="card history-card">
                <h3><i class="fas fa-history"></i> Past Practice</h3>
                <div class="history-list">
                    <?php foreach ($pastSessions as $ps): ?>
                        <div class="history-item" onclick="reviewSession(<?= $ps['practice_id'] ?>)">
                            <div class="history-info">
                                <div class="history-topic"><?= htmlspecialchars($ps['topic_title']) ?></div>
                                <div class="history-meta"><?= date('M d, H:i', strtotime($ps['created_at'])) ?></div>
                            </div>
                            <div class="history-score <?= $ps['status'] === 'completed' ? ($ps['score'] >= 70 ? 'pass' : 'fail') : 'pending' ?>">
                                <?= $ps['status'] === 'completed' ? $ps['score'] . '%' : '...' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: Questions Area -->
        <div class="practice-main">
            <div id="questionsArea" class="questions-area">
                <div class="welcome-prompt">
                    <i class="fas fa-robot" style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
                    <h2>Ready to Practice?</h2>
                    <p>Choose a topic from the left panel and click "Generate Questions" to start. You'll receive a mix of multiple choice, true/false, short answer, and fill-in-the-blank questions.</p>
                    <p style="margin-top:10px; color:#999;">After answering, submit to get AI-graded feedback with detailed explanations and personalized weakness analysis.</p>
                </div>
            </div>

            <!-- Loading -->
            <div id="loadingArea" class="loading-area" style="display:none;">
                <div class="spinner"></div>
                <p>Generating questions with AI...</p>
            </div>

            <!-- Results (after grading) -->
            <div id="resultsArea" class="results-area" style="display:none;"></div>
        </div>
    </div>
</div>

<style>
.practice-page { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
.page-heading { margin-bottom: 25px; }
.page-heading h1 { font-size: 28px; color: #1a1a2e; margin-bottom: 5px; }
.practice-layout { display: grid; grid-template-columns: 320px 1fr; gap: 25px; align-items: start; }

/* Sidebar cards */
.card { background: white; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.card h3 { font-size: 16px; margin-bottom: 15px; color: #1a1a2e; display: flex; align-items: center; gap: 8px; }
.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 13px; font-weight: 500; margin-bottom: 4px; color: #555; }
.form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
.btn-generate { width: 100%; padding: 12px; background: linear-gradient(135deg,#667eea,#764ba2); color: white; border: none; border-radius: 30px; font-size: 15px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-generate:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102,126,234,0.4); }
.btn-generate:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.status-msg { margin-top: 10px; padding: 8px; border-radius: 8px; font-size: 13px; text-align: center; }
.status-msg.error { background: #ffebee; color: #c62828; }
.status-msg.success { background: #e8f5e9; color: #2e7d32; }

/* Weakness list */
.weakness-list { display: flex; flex-direction: column; gap: 6px; }
.weakness-item { display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; background: #fff3e0; border-radius: 8px; font-size: 13px; }
.weakness-name { color: #e65100; flex: 1; }
.weakness-count { background: #ff9800; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; }

/* History */
.history-list { max-height: 300px; overflow-y: auto; }
.history-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; cursor: pointer; border-radius: 10px; transition: 0.2s; margin-bottom: 4px; }
.history-item:hover { background: #f0f2ff; }
.history-topic { font-size: 13px; font-weight: 500; }
.history-meta { font-size: 11px; color: #999; margin-top: 2px; }
.history-score { font-weight: 700; font-size: 14px; padding: 4px 10px; border-radius: 15px; }
.history-score.pass { background: #e8f5e9; color: #2e7d32; }
.history-score.fail { background: #ffebee; color: #c62828; }
.history-score.pending { background: #fff3e0; color: #ff9800; }

/* Questions area */
.practice-main { min-height: 400px; }
.questions-area { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.welcome-prompt { text-align: center; padding: 40px 20px; color: #666; }

/* Loading */
.loading-area { background: white; border-radius: 20px; padding: 60px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.spinner { width: 40px; height: 40px; border: 4px solid #e0e0e0; border-top-color: #667eea; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 15px; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Question cards */
.question-card { background: #f8f9fa; border-radius: 16px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #667eea; }
.q-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.q-number { font-weight: 700; color: #667eea; font-size: 14px; }
.q-type-badge { padding: 3px 10px; border-radius: 15px; font-size: 11px; font-weight: 500; }
.q-type-badge.multiple_choice { background: #e3f2fd; color: #1565c0; }
.q-type-badge.true_false { background: #fce4ec; color: #c62828; }
.q-type-badge.short_answer { background: #e8f5e9; color: #2e7d32; }
.q-type-badge.fill_blank { background: #fff3e0; color: #e65100; }
.q-text { font-size: 15px; margin-bottom: 12px; line-height: 1.5; }

/* MC options */
.mc-options { display: flex; flex-direction: column; gap: 8px; margin: 12px 0; }
.mc-option { display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: white; border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; transition: 0.2s; }
.mc-option:hover { border-color: #667eea; background: #f0f2ff; }
.mc-option.selected { border-color: #667eea; background: #eef0ff; }
.mc-option input[type="radio"] { accent-color: #667eea; }
.mc-label { font-size: 14px; }

/* TF options */
.tf-options { display: flex; gap: 12px; margin: 12px 0; }
.tf-option { flex: 1; padding: 14px; text-align: center; background: white; border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; transition: 0.2s; font-weight: 500; }
.tf-option:hover { border-color: #667eea; }
.tf-option.selected { border-color: #667eea; background: #eef0ff; }
.tf-option.true { color: #4CAF50; }
.tf-option.false { color: #f44336; }

/* Input for short answer / fill blank */
.answer-input { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 14px; margin: 8px 0; resize: vertical; }
.answer-input:focus { border-color: #667eea; outline: none; }

/* Submit button */
.btn-submit-all { width: 100%; padding: 14px; background: linear-gradient(135deg,#4CAF50,#45a049); color: white; border: none; border-radius: 30px; font-size: 16px; cursor: pointer; margin-top: 10px; transition: 0.3s; }
.btn-submit-all:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(76,175,80,0.4); }
.btn-submit-all:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

/* Results area */
.results-area { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.results-header { text-align: center; margin-bottom: 30px; }
.score-circle { width: 120px; height: 120px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 32px; font-weight: 800; }
.score-circle.pass { background: #e8f5e9; color: #2e7d32; }
.score-circle.fail { background: #ffebee; color: #c62828; }
.result-stats { display: flex; justify-content: center; gap: 30px; margin-bottom: 20px; }
.stat { text-align: center; }
.stat-value { font-size: 24px; font-weight: 700; color: #1a1a2e; }
.stat-label { font-size: 12px; color: #666; }

/* Graded questions */
.graded-card { background: #f8f9fa; border-radius: 14px; padding: 18px; margin-bottom: 15px; border-left: 4px solid; }
.graded-card.correct { border-left-color: #4CAF50; }
.graded-card.incorrect { border-left-color: #f44336; }
.graded-q { font-weight: 500; margin-bottom: 8px; }
.graded-answer { font-size: 13px; margin-bottom: 6px; }
.graded-answer strong { color: #333; }
.graded-feedback { font-size: 13px; padding: 8px 12px; border-radius: 8px; margin-top: 8px; }
.graded-feedback.correct { background: #e8f5e9; color: #2e7d32; }
.graded-feedback.incorrect { background: #ffebee; color: #c62828; }
.graded-explanation { font-size: 13px; color: #666; margin-top: 6px; font-style: italic; }

/* Weaknesses in results */
.weakness-section { margin-top: 25px; padding: 20px; border-radius: 16px; }
.weakness-section.improve { background: #fff3e0; }
.weakness-section.strong { background: #e8f5e9; }
.weakness-section h4 { margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
.weakness-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.weakness-tag { padding: 5px 14px; border-radius: 20px; font-size: 13px; }
.weakness-tag.improve { background: #ffcc80; color: #e65100; }
.weakness-tag.strong { background: #a5d6a7; color: #1b5e20; }

.btn-try-again { padding: 10px 25px; background: linear-gradient(135deg,#667eea,#764ba2); color: white; border: none; border-radius: 30px; cursor: pointer; margin-top: 15px; }

@media (max-width: 768px) {
    .practice-layout { grid-template-columns: 1fr; }
}
</style>

<script>
let currentPracticeId = 0;
let currentQuestions = [];
let studentAnswers = [];

function generatePractice() {
    const moduleId = document.getElementById('moduleSelect').value;
    const topic = document.getElementById('topicInput').value.trim();
    const num = document.getElementById('numQuestions').value;

    if (!topic) {
        document.getElementById('generateStatus').style.display = 'block';
        document.getElementById('generateStatus').className = 'status-msg error';
        document.getElementById('generateStatus').innerText = 'Please enter a topic or area to practice.';
        return;
    }

    document.getElementById('generateStatus').style.display = 'none';
    document.getElementById('questionsArea').style.display = 'none';
    document.getElementById('resultsArea').style.display = 'none';
    document.getElementById('loadingArea').style.display = 'block';

    const formData = new FormData();
    formData.append('action', 'generate');
    formData.append('module_id', moduleId);
    formData.append('topic', topic);
    formData.append('num_questions', num);

    fetch('ai-practice.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        document.getElementById('loadingArea').style.display = 'none';
        if (data.error) {
            document.getElementById('generateStatus').style.display = 'block';
            document.getElementById('generateStatus').className = 'status-msg error';
            document.getElementById('generateStatus').innerText = data.error;
            document.getElementById('questionsArea').style.display = 'block';
            return;
        }
        currentPracticeId = data.practice_id;
        currentQuestions = data.questions;
        studentAnswers = new Array(data.questions.length).fill('');
        renderQuestions(data.questions);
        document.getElementById('questionsArea').style.display = 'block';
        document.querySelector('.welcome-prompt')?.remove();
        document.getElementById('generateStatus').style.display = 'block';
        document.getElementById('generateStatus').className = 'status-msg success';
        document.getElementById('generateStatus').innerText = '✅ Generated ' + data.questions.length + ' questions for "' + data.topic + '"';
        document.getElementById('questionsArea').scrollIntoView({ behavior: 'smooth' });
    })
    .catch(err => {
        document.getElementById('loadingArea').style.display = 'none';
        document.getElementById('generateStatus').style.display = 'block';
        document.getElementById('generateStatus').className = 'status-msg error';
        document.getElementById('generateStatus').innerText = 'Error generating questions. Please try again.';
        document.getElementById('questionsArea').style.display = 'block';
    });
}

function renderQuestions(questions) {
    const area = document.getElementById('questionsArea');
    let html = '<div class="questions-list">';

    questions.forEach((q, idx) => {
        const typeLabels = { multiple_choice: 'Multiple Choice', true_false: 'True/False', short_answer: 'Short Answer', fill_blank: 'Fill in the Blank' };
        html += `<div class="question-card" data-idx="${idx}">
            <div class="q-header">
                <span class="q-number">Q${idx + 1}</span>
                <span class="q-type-badge ${q.type}">${typeLabels[q.type] || q.type}</span>
            </div>
            <div class="q-text">${q.question}</div>`;

        if (q.type === 'multiple_choice' && q.options) {
            html += '<div class="mc-options">';
            const labels = ['A', 'B', 'C', 'D'];
            q.options.forEach((opt, oi) => {
                html += `<div class="mc-option" onclick="selectMC(${idx}, ${oi})" id="mc-${idx}-${oi}">
                    <input type="radio" name="q_${idx}" value="${labels[oi]}" onchange="recordAnswer(${idx}, '${labels[oi]}')">
                    <span class="mc-label">${labels[oi]}. ${opt}</span>
                </div>`;
            });
            html += '</div>';
        } else if (q.type === 'true_false') {
            html += `<div class="tf-options">
                <div class="tf-option true" onclick="selectTF(${idx}, 'True')" id="tf-${idx}-true">✓ True</div>
                <div class="tf-option false" onclick="selectTF(${idx}, 'False')" id="tf-${idx}-false">✗ False</div>
            </div>`;
        } else {
            html += `<textarea class="answer-input" rows="3" placeholder="${q.type === 'fill_blank' ? 'Fill in the blank...' : 'Type your answer here...'}" oninput="recordAnswer(${idx}, this.value)"></textarea>`;
        }

        html += '</div>';
    });

    html += `<button class="btn-submit-all" id="submitBtn" onclick="submitAnswers()"><i class="fas fa-check-circle"></i> Submit All Answers for AI Grading</button>`;
    html += '</div>';
    area.innerHTML = html;
}

function selectMC(qIdx, optIdx) {
    const labels = ['A', 'B', 'C', 'D'];
    document.querySelectorAll(`[id^="mc-${qIdx}-"]`).forEach(el => el.classList.remove('selected'));
    document.getElementById(`mc-${qIdx}-${optIdx}`).classList.add('selected');
    const radio = document.querySelector(`input[name="q_${qIdx}"]`);
    if (radio) radio.checked = true;
    recordAnswer(qIdx, labels[optIdx]);
}

function selectTF(qIdx, val) {
    document.getElementById(`tf-${qIdx}-true`).classList.remove('selected');
    document.getElementById(`tf-${qIdx}-false`).classList.remove('selected');
    document.getElementById(`tf-${qIdx}-${val.toLowerCase()}`).classList.add('selected');
    recordAnswer(qIdx, val);
}

function recordAnswer(idx, value) {
    studentAnswers[idx] = value;
}

function submitAnswers() {
    // Check all answered
    let allAnswered = true;
    const unanswered = [];
    currentQuestions.forEach((q, idx) => {
        if (!studentAnswers[idx] || studentAnswers[idx].trim() === '') {
            allAnswered = false;
            unanswered.push(idx + 1);
        }
    });

    if (!allAnswered) {
        if (!confirm(`Questions ${unanswered.join(', ')} are unanswered. Submit anyway?`)) return;
    }

    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Grading...';

    const formData = new FormData();
    formData.append('action', 'submit_answers');
    formData.append('practice_id', currentPracticeId);
    formData.append('answers', JSON.stringify(studentAnswers));

    fetch('ai-practice.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            document.getElementById('submitBtn').disabled = false;
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-check-circle"></i> Submit All Answers for AI Grading';
            return;
        }
        renderResults(data);
    })
    .catch(() => {
        alert('Error submitting. Please try again.');
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-check-circle"></i> Submit All Answers for AI Grading';
    });
}

function renderResults(data) {
    document.getElementById('questionsArea').style.display = 'none';
    document.getElementById('resultsArea').style.display = 'block';
    document.getElementById('resultsArea').scrollIntoView({ behavior: 'smooth' });

    const pass = data.score >= 70;
    let html = `
        <div class="results-header">
            <div class="score-circle ${pass ? 'pass' : 'fail'}">${data.score}%</div>
            <h2>${pass ? '🎉 Great Job!' : '📚 Keep Practicing!'}</h2>
            <div class="result-stats">
                <div class="stat"><div class="stat-value">${data.correct}/${data.total}</div><div class="stat-label">Correct</div></div>
                <div class="stat"><div class="stat-value">${data.total - data.correct}</div><div class="stat-label">Incorrect</div></div>
                <div class="stat"><div class="stat-value">${data.score}%</div><div class="stat-label">Score</div></div>
            </div>
        </div>
        <div class="graded-list">`;

    data.graded.forEach((g, idx) => {
        const correct = g.is_correct;
        html += `
            <div class="graded-card ${correct ? 'correct' : 'incorrect'}">
                <div class="graded-q">Q${idx + 1}: ${g.question}</div>
                <div class="graded-answer"><strong>Your answer:</strong> ${g.student_answer || '(no answer)'}</div>
                <div class="graded-answer"><strong>Correct answer:</strong> ${g.correct_answer}</div>
                <div class="graded-feedback ${correct ? 'correct' : 'incorrect'}">${g.feedback}</div>
                ${g.explanation ? `<div class="graded-explanation">💡 ${g.explanation}</div>` : ''}
            </div>`;
    });

    html += '</div>';

    if (data.weaknesses) {
        const weakItems = data.weaknesses.split(',').map(s => s.trim()).filter(s => s);
        if (weakItems.length > 0) {
            html += `<div class="weakness-section improve"><h4><i class="fas fa-exclamation-triangle" style="color:#e65100;"></i> Areas to Improve</h4><div class="weakness-tags">`;
            weakItems.forEach(w => { html += `<span class="weakness-tag improve">${w}</span>`; });
            html += `</div></div>`;
        }
    }
    if (data.strengths) {
        const strongItems = data.strengths.split(',').map(s => s.trim()).filter(s => s);
        if (strongItems.length > 0) {
            html += `<div class="weakness-section strong"><h4><i class="fas fa-star" style="color:#2e7d32;"></i> Your Strengths</h4><div class="weakness-tags">`;
            strongItems.forEach(s => { html += `<span class="weakness-tag strong">${s}</span>`; });
            html += `</div></div>`;
        }
    }

    html += `<button class="btn-try-again" onclick="location.reload()"><i class="fas fa-redo"></i> Practice Another Topic</button>`;
    document.getElementById('resultsArea').innerHTML = html;
}

function reviewSession(practiceId) {
    const formData = new FormData();
    formData.append('action', 'review');
    formData.append('practice_id', practiceId);

    fetch('ai-practice.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.error) { alert(data.error); return; }
        renderResults(data);
    })
    .catch(() => alert('Could not load session.'));
}
</script>

<?php include 'includes/footer.php'; ?>
