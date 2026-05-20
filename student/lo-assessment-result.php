<?php
require_once '../config/database.php';
require_once 'includes/auth.php';

$submissionId = intval($_GET['submission_id'] ?? 0);
if (!$submissionId) die('Invalid submission.');

// Fetch submission details
$stmt = $pdo->prepare("
    SELECT s.*, a.title, a.lo_id, a.passing_marks, a.total_marks as assessment_total
    FROM lo_assessment_submissions s
    JOIN lo_assessments a ON s.lo_assessment_id = a.lo_assessment_id
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

// Check if retake is allowed
$canRetake = (!$passed && $attemptNumber < $maxAttempts);
?>
<?php include 'includes/header.php'; ?>
<div class="result-container">
    <div class="result-card <?= $passed ? 'passed' : 'failed' ?>">
        <div class="result-icon">
            <i class="fas <?= $passed ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
        </div>
        <h1><?= $passed ? 'Congratulations!' : 'Not Yet' ?></h1>
        <p class="assessment-title"><?= htmlspecialchars($submission['title']) ?></p>
        <div class="score-box">
            <span class="score"><?= $score ?></span> / <span class="total"><?= $total ?></span>
            <span class="percentage">(<?= $percentage ?>%)</span>
        </div>
        <div class="passing-info">Passing mark: <?= $submission['passing_marks'] ?>%</div>
        <?php if ($passed): ?>
            <div class="feedback success">🎉 You have successfully passed this LO assessment!</div>
        <?php else: ?>
            <div class="feedback error">❌ You did not reach the passing score. Review the material and try again.</div>
            <?php if ($canRetake): ?>
                <div class="retake-info">
                    <p>You have <?= $maxAttempts - $attemptNumber ?> attempt(s) left.</p>
                    <a href="lo-assessment.php?lo_id=<?= $submission['lo_id'] ?>" class="btn-retake">Retake Assessment</a>
                </div>
            <?php else: ?>
                <div class="retake-info error">You have used all attempts. Please contact your instructor for assistance.</div>
            <?php endif; ?>
        <?php endif; ?>
        <a href="module.php?module_id=<?= $_GET['module_id'] ?? '' ?>" class="btn-back">Back to Module</a>
    </div>
</div>

<style>
    .result-container {
        max-width: 600px;
        margin: 3rem auto;
        padding: 0 1rem;
    }
    .result-card {
        background: white;
        border-radius: 1.5rem;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 20px 35px rgba(0,0,0,0.1);
    }
    .result-card.passed { border-top: 5px solid #4CAF50; }
    .result-card.failed { border-top: 5px solid #f44336; }
    .result-icon i {
        font-size: 4rem;
        margin-bottom: 1rem;
    }
    .passed .result-icon i { color: #4CAF50; }
    .failed .result-icon i { color: #f44336; }
    .assessment-title {
        color: #1a5f7a;
        margin-bottom: 1rem;
    }
    .score-box {
        font-size: 2rem;
        margin: 1rem 0;
    }
    .score { font-weight: 700; color: #2c7da0; }
    .total { color: #8aaec0; }
    .percentage { font-size: 1rem; }
    .feedback {
        padding: 0.8rem;
        border-radius: 0.8rem;
        margin: 1rem 0;
    }
    .feedback.success { background: #e8f5e9; color: #2e7d32; }
    .feedback.error { background: #ffebee; color: #c62828; }
    .btn-retake {
        display: inline-block;
        background: #ff9800;
        color: white;
        padding: 0.6rem 1.2rem;
        border-radius: 2rem;
        text-decoration: none;
        margin-top: 0.5rem;
    }
    .btn-back {
        display: inline-block;
        background: #2c7da0;
        color: white;
        padding: 0.6rem 1.2rem;
        border-radius: 2rem;
        text-decoration: none;
        margin-top: 1rem;
    }
</style>
<?php include 'includes/footer.php'; ?>