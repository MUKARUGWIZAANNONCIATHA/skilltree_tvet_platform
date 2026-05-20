<?php
/**
 * Student Assessments – List all available LO assessments and module exams
 * Path: /student/assessment.php
 */

require_once '../config/database.php';
require_once 'includes/auth.php';

// Get student's enrolled modules
$stmt = $pdo->prepare("
    SELECT DISTINCT module_id 
    FROM student_enrollments 
    WHERE student_id = ? AND status != 'dropped'
");
$stmt->execute([$studentId]);
$enrolledModules = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($enrolledModules)) {
    echo '<div class="container"><p>You are not enrolled in any module yet. Go to Dashboard to select a trade.</p></div>';
    include 'includes/footer.php';
    exit;
}

// Prepare placeholders for module IDs
$in = str_repeat('?,', count($enrolledModules) - 1) . '?';

// ========== 1. LO ASSESSMENTS ==========
// Get all LOs for enrolled modules that have an assessment published
$sql = "
    SELECT lo.outcome_id as lo_id, lo.outcome_number, lo.description, lo.module_id,
           m.module_code, m.module_name,
           a.lo_assessment_id, a.title as assessment_title, a.total_marks, a.passing_marks,
           (SELECT COUNT(*) FROM topic_progress tp 
            JOIN topics t ON tp.topic_id = t.topic_id
            JOIN indicative_contents ic ON t.ic_id = ic.ic_id
            WHERE ic.outcome_id = lo.outcome_id AND tp.student_id = ? AND tp.quiz_passed = 1) as topics_passed,
           (SELECT COUNT(*) FROM topics t 
            JOIN indicative_contents ic ON t.ic_id = ic.ic_id
            WHERE ic.outcome_id = lo.outcome_id) as total_topics,
           (SELECT status FROM lo_assessment_submissions 
            WHERE lo_assessment_id = a.lo_assessment_id AND student_id = ? 
            ORDER BY attempt_number DESC LIMIT 1) as last_status,
           (SELECT percentage FROM lo_assessment_submissions 
            WHERE lo_assessment_id = a.lo_assessment_id AND student_id = ? 
            ORDER BY attempt_number DESC LIMIT 1) as last_score,
           (SELECT COUNT(*) FROM lo_assessment_submissions 
            WHERE lo_assessment_id = a.lo_assessment_id AND student_id = ?) as attempts
    FROM learning_outcomes lo
    JOIN modules m ON lo.module_id = m.module_id
    LEFT JOIN lo_assessments a ON lo.outcome_id = a.lo_id AND a.status = 'published'
    WHERE lo.module_id IN ($in)
    ORDER BY m.module_code, lo.outcome_number
";
$params = array_merge([$studentId, $studentId, $studentId, $studentId], $enrolledModules);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allLos = $stmt->fetchAll();

$los = [];
foreach ($allLos as $lo) {
    $unlocked = ($lo['topics_passed'] == $lo['total_topics'] && $lo['total_topics'] > 0);
    $canRetake = ($lo['attempts'] < 3 && $lo['last_status'] !== 'passed');
    $los[] = [
        'lo_id' => $lo['lo_id'],
        'number' => $lo['outcome_number'],
        'description' => $lo['description'],
        'module_code' => $lo['module_code'],
        'module_name' => $lo['module_name'],
        'assessment_id' => $lo['lo_assessment_id'],
        'assessment_title' => $lo['assessment_title'],
        'unlocked' => $unlocked && $lo['lo_assessment_id'] !== null,
        'status' => $lo['last_status'] ?? 'not_taken',
        'score' => $lo['last_score'] ?? null,
        'can_retake' => $canRetake,
        'attempts' => $lo['attempts'] ?? 0,
        'passing_marks' => $lo['passing_marks']
    ];
}

// ========== 2. MODULE EXAMS ==========
$sql = "
    SELECT e.exam_id, e.exam_title, e.total_marks, e.passing_marks,
           m.module_id, m.module_code, m.module_name,
           (SELECT COUNT(*) FROM lo_assessment_submissions las
            JOIN learning_outcomes lo2 ON las.lo_assessment_id IN (SELECT lo_assessment_id FROM lo_assessments WHERE lo_id IN (SELECT outcome_id FROM learning_outcomes WHERE module_id = m.module_id))
            WHERE las.student_id = ? AND las.status = 'passed') as los_passed,
           (SELECT COUNT(*) FROM learning_outcomes WHERE module_id = m.module_id) as total_los,
           (SELECT status FROM exam_submissions 
            WHERE exam_id = e.exam_id AND student_id = ? 
            ORDER BY attempt_number DESC LIMIT 1) as last_status,
           (SELECT percentage FROM exam_submissions 
            WHERE exam_id = e.exam_id AND student_id = ? 
            ORDER BY attempt_number DESC LIMIT 1) as last_score,
           (SELECT COUNT(*) FROM exam_submissions 
            WHERE exam_id = e.exam_id AND student_id = ?) as attempts
    FROM exams e
    JOIN modules m ON e.module_id = m.module_id
    WHERE e.status = 'published' AND m.module_id IN ($in)
    ORDER BY m.module_code
";
$params = array_merge([$studentId, $studentId, $studentId, $studentId], $enrolledModules);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$exams = $stmt->fetchAll();

$moduleExams = [];
foreach ($exams as $exam) {
    $unlocked = ($exam['los_passed'] == $exam['total_los'] && $exam['total_los'] > 0);
    $canRetake = ($exam['attempts'] < 3 && $exam['last_status'] !== 'passed');
    $moduleExams[] = [
        'exam_id' => $exam['exam_id'],
        'title' => $exam['exam_title'],
        'module_code' => $exam['module_code'],
        'module_name' => $exam['module_name'],
        'total_marks' => $exam['total_marks'],
        'passing_marks' => $exam['passing_marks'],
        'unlocked' => $unlocked,
        'status' => $exam['last_status'] ?? 'not_taken',
        'score' => $exam['last_score'] ?? null,
        'can_retake' => $canRetake,
        'attempts' => $exam['attempts'] ?? 0
    ];
}

include 'includes/header.php';
?>

<div class="assessments-container">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-list"></i> My Assessments</h1>
        <p>Learning Outcome assessments and module final exams</p>
    </div>

    <!-- LO Assessments Section -->
    <div class="section">
        <h2><i class="fas fa-flag-checkered"></i> Learning Outcome Assessments</h2>
        <?php
        $hasLos = false;
        foreach ($los as $lo):
            if ($lo['assessment_id']) $hasLos = true;
        ?>
        <div class="assessment-card <?= $lo['unlocked'] ? 'unlocked' : 'locked' ?>">
            <div class="card-header">
                <div>
                    <span class="module-badge"><?= htmlspecialchars($lo['module_code']) ?></span>
                    <h3>LO<?= $lo['number'] ?>: <?= htmlspecialchars($lo['description']) ?></h3>
                </div>
                <div class="status-badge <?= $lo['status'] ?>">
                    <?php if ($lo['status'] == 'passed'): ?>
                        ✅ Passed (<?= $lo['score'] ?>%)
                    <?php elseif ($lo['status'] == 'failed'): ?>
                        ❌ Failed (<?= $lo['score'] ?>%) – Retry allowed
                    <?php elseif ($lo['unlocked']): ?>
                        🔓 Ready to take
                    <?php else: ?>
                        🔒 Locked – complete all topic quizzes first
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Assessment:</strong> <?= htmlspecialchars($lo['assessment_title']) ?></p>
                <p><strong>Marks:</strong> <?= $lo['total_marks'] ?? '100' ?> | <strong>Passing:</strong> <?= $lo['passing_marks'] ?? '70' ?>%</p>
                <?php if ($lo['attempts'] > 0): ?>
                    <p><strong>Attempts:</strong> <?= $lo['attempts'] ?>/3</p>
                <?php endif; ?>
            </div>
            <div class="card-actions">
                <?php if ($lo['unlocked'] && ($lo['status'] != 'passed' || $lo['can_retake'])): ?>
                    <a href="lo-assessment.php?lo_id=<?= $lo['lo_id'] ?>" class="btn-take">Take Assessment</a>
                <?php elseif ($lo['status'] == 'passed'): ?>
                    <button class="btn-completed" disabled>Completed</button>
                <?php else: ?>
                    <button class="btn-locked" disabled>Locked</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$hasLos): ?>
            <div class="empty-message">No LO assessments available for your enrolled modules yet.</div>
        <?php endif; ?>
    </div>

    <!-- Module Exams Section -->
    <div class="section">
        <h2><i class="fas fa-graduation-cap"></i> Module Final Exams</h2>
        <?php if (empty($moduleExams)): ?>
            <div class="empty-message">No module exams available for your enrolled modules yet.</div>
        <?php else: ?>
            <?php foreach ($moduleExams as $exam): ?>
            <div class="assessment-card <?= $exam['unlocked'] ? 'unlocked' : 'locked' ?>">
                <div class="card-header">
                    <div>
                        <span class="module-badge"><?= htmlspecialchars($exam['module_code']) ?></span>
                        <h3><?= htmlspecialchars($exam['title']) ?></h3>
                        <p class="module-name"><?= htmlspecialchars($exam['module_name']) ?></p>
                    </div>
                    <div class="status-badge <?= $exam['status'] ?>">
                        <?php if ($exam['status'] == 'passed'): ?>
                            ✅ Passed (<?= $exam['score'] ?>%)
                        <?php elseif ($exam['status'] == 'failed'): ?>
                            ❌ Failed (<?= $exam['score'] ?>%) – Retry allowed
                        <?php elseif ($exam['unlocked']): ?>
                            🔓 Ready to take
                        <?php else: ?>
                            🔒 Locked – complete all LOs first
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <p><strong>Marks:</strong> <?= $exam['total_marks'] ?> | <strong>Passing:</strong> <?= $exam['passing_marks'] ?>%</p>
                    <?php if ($exam['attempts'] > 0): ?>
                        <p><strong>Attempts:</strong> <?= $exam['attempts'] ?>/3</p>
                    <?php endif; ?>
                </div>
                <div class="card-actions">
                    <?php if ($exam['unlocked'] && ($exam['status'] != 'passed' || $exam['can_retake'])): ?>
                        <a href="module-exam.php?exam_id=<?= $exam['exam_id'] ?>" class="btn-take">Take Exam</a>
                    <?php elseif ($exam['status'] == 'passed'): ?>
                        <button class="btn-completed" disabled>Completed</button>
                    <?php else: ?>
                        <button class="btn-locked" disabled>Locked</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .assessments-container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .section {
        margin-bottom: 3rem;
    }
    .section h2 {
        color: #1a5f7a;
        border-bottom: 2px solid #eef2f8;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
    }
    .assessment-card {
        background: white;
        border-radius: 1rem;
        padding: 1.2rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: 0.2s;
        border-left: 4px solid #cbd5e1;
    }
    .assessment-card.unlocked {
        border-left-color: #2c7da0;
    }
    .assessment-card.locked {
        opacity: 0.7;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        margin-bottom: 0.8rem;
    }
    .module-badge {
        background: #eef2fa;
        padding: 0.2rem 0.6rem;
        border-radius: 1rem;
        font-size: 0.7rem;
        font-weight: 600;
        color: #2c7da0;
        display: inline-block;
        margin-bottom: 0.4rem;
    }
    .card-header h3 {
        margin: 0;
        font-size: 1.1rem;
    }
    .module-name {
        font-size: 0.85rem;
        color: #6c8faa;
        margin-top: 0.2rem;
    }
    .status-badge {
        font-size: 0.75rem;
        padding: 0.2rem 0.6rem;
        border-radius: 2rem;
        white-space: nowrap;
    }
    .status-badge.passed { background: #e8f5e9; color: #2e7d32; }
    .status-badge.failed { background: #ffebee; color: #c62828; }
    .status-badge.not_taken, .status-badge.pending { background: #fff3e0; color: #c76f1c; }
    .card-body {
        font-size: 0.9rem;
        color: #2c5a74;
        margin-bottom: 1rem;
    }
    .card-actions {
        text-align: right;
    }
    .btn-take {
        background: #2c7da0;
        color: white;
        padding: 0.4rem 1rem;
        border-radius: 2rem;
        text-decoration: none;
        display: inline-block;
        font-size: 0.85rem;
    }
    .btn-take:hover {
        background: #1e5f7a;
    }
    .btn-completed, .btn-locked {
        background: #ccc;
        color: #666;
        padding: 0.4rem 1rem;
        border-radius: 2rem;
        border: none;
        cursor: default;
        font-size: 0.85rem;
    }
    .empty-message {
        text-align: center;
        padding: 2rem;
        background: #f8fafc;
        border-radius: 1rem;
        color: #8aaec0;
    }
    @media (max-width: 700px) {
        .card-header {
            flex-direction: column;
            gap: 0.5rem;
        }
        .status-badge {
            align-self: flex-start;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>