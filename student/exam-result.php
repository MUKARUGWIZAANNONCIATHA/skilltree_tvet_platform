<?php
/**
 * Exam Result Page – Final Module Exam Result
 * Path: /student/exam-result.php
 */
require_once '../config/database.php';
require_once 'includes/auth.php';

$submissionId = intval($_GET['submission_id'] ?? 0);
if (!$submissionId) die('Invalid submission.');

$stmt = $pdo->prepare("
    SELECT s.*, e.exam_title, e.module_id, e.passing_marks, e.total_marks as exam_total
    FROM exam_submissions s
    JOIN exams e ON s.exam_id = e.exam_id
    WHERE s.submission_id = ? AND s.student_id = ?
");
$stmt->execute([$submissionId, $studentId]);
$submission = $stmt->fetch();
if (!$submission) die('Submission not found.');

$passed = ($submission['status'] === 'passed');
$percentage = round($submission['percentage']);
$score = $submission['score'];
$total = $submission['total_marks'];
$attemptNumber = $submission['attempt_number'];
$maxAttempts = 3;
$canRetake = (!$passed && $attemptNumber < $maxAttempts);

// Also check module_progress (in case already passed)
$modProgress = $pdo->prepare("SELECT module_passed FROM module_progress WHERE student_id = ? AND module_id = ?");
$modProgress->execute([$studentId, $submission['module_id']]);
$alreadyPassed = $modProgress->fetchColumn();
if ($alreadyPassed) {
    $canRetake = false;
}
?>
<?php include 'includes/header.php'; ?>
<div class="result-container">
    <div class="result-card <?= $passed ? 'passed' : 'failed' ?>">
        <div class="result-icon">
            <i class="fas <?= $passed ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
        </div>
        <h1><?= $passed ? 'Congratulations!' : 'Not Yet' ?></h1>
        <p class="exam-title"><?= htmlspecialchars($submission['exam_title']) ?></p>
        <div class="score-box">
            <span class="score"><?= $score ?></span> / <span class="total"><?= $total ?></span>
            <span class="percentage">(<?= $percentage ?>%)</span>
        </div>
        <div class="passing-info">Passing mark: <?= $submission['passing_marks'] ?>%</div>
        <?php if ($passed): ?>
            <div class="feedback success">🎉 You have successfully passed the module exam! You can now proceed to the next module.</div>
            <a href="module.php?module_id=<?= $submission['module_id'] ?>" class="btn-back">Back to Module</a>
        <?php else: ?>
            <div class="feedback error">❌ You did not reach the passing score. Review the material and try again.</div>
            <?php if ($canRetake): ?>
                <div class="retake-info">
                    <p>You have <?= $maxAttempts - $attemptNumber ?> attempt(s) left.</p>
                    <a href="quiz.php?type=module&id=<?= $submission['exam_id'] ?>" class="btn-retake">Retake Exam</a>
                </div>
            <?php else: ?>
                <div class="retake-info error">You have used all attempts. Please contact your instructor for assistance.</div>
                <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
    </div>
</div>

<style>
    .result-container { max-width: 600px; margin: 3rem auto; padding: 0 1rem; }
    .result-card { background: white; border-radius: 1.5rem; padding: 2rem; text-align: center; box-shadow: 0 20px 35px rgba(0,0,0,0.1); }
    .result-card.passed { border-top: 5px solid #4CAF50; }
    .result-card.failed { border-top: 5px solid #f44336; }
    .result-icon i { font-size: 4rem; margin-bottom: 1rem; }
    .passed .result-icon i { color: #4CAF50; }
    .failed .result-icon i { color: #f44336; }
    .exam-title { color: #1a5f7a; margin-bottom: 1rem; }
    .score-box { font-size: 2rem; margin: 1rem 0; }
    .score { font-weight: 700; color: #2c7da0; }
    .total { color: #8aaec0; }
    .percentage { font-size: 1rem; }
    .feedback { padding: 0.8rem; border-radius: 0.8rem; margin: 1rem 0; }
    .feedback.success { background: #e8f5e9; color: #2e7d32; }
    .feedback.error { background: #ffebee; color: #c62828; }
    .btn-retake { display: inline-block; background: #ff9800; color: white; padding: 0.6rem 1.2rem; border-radius: 2rem; text-decoration: none; margin-top: 0.5rem; }
    .btn-back { display: inline-block; background: #2c7da0; color: white; padding: 0.6rem 1.2rem; border-radius: 2rem; text-decoration: none; margin-top: 1rem; margin-left: 0.5rem; margin-right: 0.5rem; }
</style>
<?php include 'includes/footer.php'; ?>