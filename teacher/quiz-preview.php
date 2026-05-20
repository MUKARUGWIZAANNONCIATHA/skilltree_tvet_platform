<?php
/**
 * Teacher Quiz Preview - Shows quiz as student sees it + teacher tools
 * Path: /teacher/quiz-preview.php
 */

require_once '../config/database.php';

$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;

// Get quiz settings
$stmt = $pdo->prepare("SELECT * FROM topic_quizzes WHERE topic_id = ?");
$stmt->execute([$topicId]);
$quiz = $stmt->fetch();

// Get quiz questions
$stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE topic_id = ? ORDER BY order_number");
$stmt->execute([$topicId]);
$questions = $stmt->fetchAll();

// Get topic info
$stmt = $pdo->prepare("SELECT topic_title FROM topics WHERE topic_id = ?");
$stmt->execute([$topicId]);
$topic = $stmt->fetch();

include_once '../includes/templates/header.php';
?>

<div class="preview-container">
    <!-- Preview Header -->
    <div class="preview-header">
        <h1><i class="fas fa-eye"></i> Quiz Preview</h1>
        <div class="preview-badge">👁️ TEACHER PREVIEW MODE</div>
        <p>This is how students will see the quiz. Answer key is visible to you only.</p>
    </div>

    <!-- Quiz Display (Same as Student View) -->
    <div class="quiz-container">
        <div class="quiz-header">
            <h2><?php echo htmlspecialchars($topic['topic_title'] ?? 'Topic Quiz'); ?></h2>
            <div class="quiz-meta">
                <span><i class="fas fa-clock"></i> Time Limit: <?php echo $quiz['time_limit_minutes'] ?? 30; ?> minutes</span>
                <span><i class="fas fa-star"></i> Passing Score: <?php echo $quiz['passing_score'] ?? 70; ?>%</span>
                <span><i class="fas fa-question-circle"></i> Questions: <?php echo count($questions); ?></span>
                <span><i class="fas fa-trophy"></i> Total Points: <?php 
                    $total = 0;
                    foreach($questions as $q) $total += $q['points'];
                    echo $total;
                ?></span>
            </div>
        </div>

        <?php if(empty($questions)): ?>
            <div class="empty-quiz">
                <i class="fas fa-folder-open"></i>
                <h3>No questions yet</h3>
                <p>This quiz has no questions. Please add questions first.</p>
                <a href="/teacher/quiz-builder.php?topic_id=<?php echo $topicId; ?>" class="btn-back">← Back to Quiz Builder</a>
            </div>
        <?php else: ?>
            <form class="quiz-form" id="previewForm">
                <?php foreach($questions as $index => $q): 
                    $options = json_decode($q['options_json'], true);
                ?>
                <div class="question-card">
                    <div class="question-header">
                        <span class="question-num">Question <?php echo $index + 1; ?></span>
                        <span class="question-points"><?php echo $q['points']; ?> points</span>
                    </div>
                    <div class="question-text"><?php echo nl2br(htmlspecialchars($q['question_text'])); ?></div>
                    
                    <div class="question-options">
                        <?php if($q['question_type'] == 'multiple_choice' && is_array($options)): ?>
                            <?php foreach($options as $optIndex => $option): ?>
                                <label class="option-label">
                                    <input type="radio" name="q_<?php echo $q['question_id']; ?>" value="<?php echo $optIndex; ?>">
                                    <span class="option-text"><?php echo chr(65 + $optIndex); ?>. <?php echo htmlspecialchars($option); ?></span>
                                </label>
                            <?php endforeach; ?>
                            
                        <?php elseif($q['question_type'] == 'true_false'): ?>
                            <label class="option-label">
                                <input type="radio" name="q_<?php echo $q['question_id']; ?>" value="true">
                                <span class="option-text">True</span>
                            </label>
                            <label class="option-label">
                                <input type="radio" name="q_<?php echo $q['question_id']; ?>" value="false">
                                <span class="option-text">False</span>
                            </label>
                            
                        <?php elseif($q['question_type'] == 'short_answer'): ?>
                            <div class="short-answer">
                                <input type="text" name="q_<?php echo $q['question_id']; ?>" class="short-answer-input" placeholder="Type your answer here...">
                            </div>
                            
                        <?php elseif($q['question_type'] == 'essay'): ?>
                            <div class="essay-answer">
                                <textarea name="q_<?php echo $q['question_id']; ?>" class="essay-input" rows="5" placeholder="Write your answer here..."></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="quiz-actions">
                    <button type="button" class="btn-submit-disabled" disabled>Submit Quiz (Preview Mode)</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Teacher Tools (Answer Key) - Only visible to teacher -->
    <div class="teacher-tools">
        <div class="tools-header">
            <h3><i class="fas fa-chalkboard-teacher"></i> Teacher Tools (Answer Key)</h3>
            <button class="btn-toggle" onclick="toggleAnswerKey()">Show/Hide Answer Key</button>
        </div>
        
        <div id="answerKey" class="answer-key">
            <div class="answer-key-header">
                <span class="q-num">Question</span>
                <span class="q-answer">Correct Answer</span>
                <span class="q-points">Points</span>
            </div>
            <?php foreach($questions as $index => $q): ?>
                <div class="answer-row">
                    <div class="q-num"><?php echo $index + 1; ?></div>
                    <div class="q-answer">
                        <?php 
                        if($q['question_type'] == 'multiple_choice') {
                            $options = json_decode($q['options_json'], true);
                            $correctIndex = intval($q['correct_answer']);
                            echo chr(65 + $correctIndex) . ". " . htmlspecialchars($options[$correctIndex] ?? '');
                        } elseif($q['question_type'] == 'true_false') {
                            echo ucfirst($q['correct_answer']);
                        } elseif($q['question_type'] == 'short_answer') {
                            echo htmlspecialchars($q['correct_answer']);
                        } else {
                            echo nl2br(htmlspecialchars(substr($q['correct_answer'], 0, 100))) . (strlen($q['correct_answer']) > 100 ? '...' : '');
                        }
                        ?>
                    </div>
                    <div class="q-points"><?php echo $q['points']; ?> pts</div>
                </div>
            <?php endforeach; ?>
            <div class="answer-total">
                <strong>Total Points: <?php 
                    $total = 0;
                    foreach($questions as $q) $total += $q['points'];
                    echo $total;
                ?></strong>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="/teacher/quiz-builder.php?topic_id=<?php echo $topicId; ?>" class="btn-edit-quiz">
            <i class="fas fa-edit"></i> Edit Quiz
        </a>
        <a href="/teacher/quiz-builder.php?module_id=<?php echo $_GET['module_id'] ?? 0; ?>" class="btn-done">
            <i class="fas fa-check"></i> Done
        </a>
    </div>
</div>

<style>
.preview-container{max-width:900px;margin:0 auto;padding:30px 20px;}
.preview-header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:20px;border-radius:16px;margin-bottom:25px;text-align:center;}
.preview-badge{background:#ff8c42;display:inline-block;padding:5px 15px;border-radius:30px;font-size:12px;margin:10px 0;}
.quiz-container{background:white;border-radius:20px;padding:25px;margin-bottom:30px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.quiz-header{border-bottom:2px solid #667eea;padding-bottom:15px;margin-bottom:20px;}
.quiz-header h2{color:#1e3a5f;margin-bottom:10px;}
.quiz-meta{display:flex;gap:15px;flex-wrap:wrap;}
.quiz-meta span{background:#f0f0f0;padding:5px 12px;border-radius:20px;font-size:12px;}
.question-card{background:#f8f9fa;border-radius:16px;padding:20px;margin-bottom:20px;}
.question-header{display:flex;justify-content:space-between;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid #ddd;}
.question-num{font-weight:bold;color:#667eea;}
.question-points{color:#ff8c42;}
.question-text{font-size:16px;margin-bottom:15px;line-height:1.5;}
.question-options{margin-top:10px;}
.option-label{display:flex;align-items:center;gap:12px;padding:8px;margin:5px 0;border-radius:8px;cursor:pointer;transition:background 0.3s;}
.option-label:hover{background:#e8f0fe;}
.option-text{font-size:14px;}
.short-answer-input{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;}
.essay-input{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;resize:vertical;}
.quiz-actions{text-align:center;margin-top:25px;}
.btn-submit-disabled{background:#ccc;color:#666;border:none;padding:12px 40px;border-radius:30px;cursor:not-allowed;}
.teacher-tools{background:#f0f7f0;border-radius:20px;padding:20px;margin-bottom:30px;border:1px solid #4CAF50;}
.tools-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;flex-wrap:wrap;gap:10px;}
.tools-header h3{color:#2e7d32;margin:0;}
.btn-toggle{background:#4CAF50;color:white;border:none;padding:8px 20px;border-radius:30px;cursor:pointer;}
.answer-key{border-top:1px solid #c8e6c9;padding-top:15px;margin-top:10px;}
.answer-key-header{display:flex;font-weight:bold;padding:10px;background:#e8f5e9;border-radius:8px;margin-bottom:10px;}
.answer-row{display:flex;padding:8px;border-bottom:1px solid #eee;}
.answer-row .q-num{width:60px;font-weight:bold;}
.answer-row .q-answer{flex:1;}
.answer-row .q-points{width:80px;}
.answer-total{text-align:right;padding-top:10px;margin-top:10px;border-top:2px solid #c8e6c9;}
.action-buttons{display:flex;gap:15px;justify-content:center;}
.btn-edit-quiz, .btn-done{background:#667eea;color:white;border:none;padding:12px 30px;border-radius:30px;text-decoration:none;cursor:pointer;}
.btn-edit-quiz:hover, .btn-done:hover{transform:scale(1.02);}
.empty-quiz{text-align:center;padding:60px;}
.btn-back{background:#667eea;color:white;padding:10px 20px;border-radius:30px;text-decoration:none;display:inline-block;margin-top:15px;}
</style>

<script>
function toggleAnswerKey() {
    var answerKey = document.getElementById('answerKey');
    if(answerKey.style.display === 'none') {
        answerKey.style.display = 'block';
    } else {
        answerKey.style.display = 'none';
    }
}
// Initially hide answer key? Or show? Your choice
document.getElementById('answerKey').style.display = 'block';
</script>

<?php include_once '../includes/templates/footer.php'; ?>