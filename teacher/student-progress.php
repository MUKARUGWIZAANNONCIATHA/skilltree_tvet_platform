 
<?php
/**
 * Student Progress - Monitor individual student performance
 * Path: /teacher/student-progress.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$message = '';

// Get teacher's modules
if ($role === 'admin') {
    $modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT module_id, module_code, module_name FROM modules WHERE created_by = ? ORDER BY module_code");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
}

$selectedModuleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$selectedStudentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Get students enrolled in the selected module
$students = [];
if ($selectedModuleId > 0) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.user_id, u.full_name, u.email, e.enrollment_date
        FROM student_enrollments e
        JOIN users u ON e.student_id = u.user_id
        WHERE e.module_id = ? AND e.status != 'dropped'
        ORDER BY u.full_name
    ");
    $stmt->execute([$selectedModuleId]);
    $students = $stmt->fetchAll();
}

// If only one student in the module and none selected, auto-select the first one
if ($selectedModuleId > 0 && $selectedStudentId == 0 && count($students) == 1) {
    $selectedStudentId = $students[0]['user_id'];
}

// Fetch student data
$studentData = null;
$moduleProgress = 0;
$topics = [];
$loAssessments = [];
$quizScores = [];

if ($selectedStudentId > 0 && $selectedModuleId > 0) {
    // Get student info
    $stmt = $pdo->prepare("SELECT user_id, full_name, email FROM users WHERE user_id = ?");
    $stmt->execute([$selectedStudentId]);
    $studentData = $stmt->fetch();

    // Calculate overall module progress (based on topics with quiz passed)
    $progressStmt = $pdo->prepare("
        SELECT COUNT(t.topic_id) as total,
               COUNT(tp.quiz_passed) as completed
        FROM topics t
        JOIN indicative_contents ic ON t.ic_id = ic.ic_id
        JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
        LEFT JOIN topic_progress tp ON t.topic_id = tp.topic_id AND tp.student_id = ? AND tp.quiz_passed = 1
        WHERE lo.module_id = ?
    ");
    $progressStmt->execute([$selectedStudentId, $selectedModuleId]);
    $progress = $progressStmt->fetch();
    $moduleProgress = $progress['total'] > 0 ? round(($progress['completed'] / $progress['total']) * 100) : 0;

    // Get topics with quiz scores
    $topicsStmt = $pdo->prepare("
        SELECT t.topic_id, t.topic_title, lo.outcome_number, 
               tp.quiz_passed, tp.quiz_score
        FROM topics t
        JOIN indicative_contents ic ON t.ic_id = ic.ic_id
        JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
        LEFT JOIN topic_progress tp ON t.topic_id = tp.topic_id AND tp.student_id = ?
        WHERE lo.module_id = ?
        ORDER BY lo.outcome_number, ic.ic_order, t.topic_order
    ");
    $topicsStmt->execute([$selectedStudentId, $selectedModuleId]);
    $topics = $topicsStmt->fetchAll();

    // Get LO assessment results for this module
    $loStmt = $pdo->prepare("
        SELECT lo.outcome_number, lo.description,
               las.percentage, las.status, las.submitted_at, las.attempt_number
        FROM learning_outcomes lo
        LEFT JOIN lo_assessments la ON lo.outcome_id = la.lo_id AND la.status = 'published'
        LEFT JOIN lo_assessment_submissions las ON la.lo_assessment_id = las.lo_assessment_id AND las.student_id = ?
        WHERE lo.module_id = ?
        ORDER BY lo.outcome_number
    ");
    $loStmt->execute([$selectedStudentId, $selectedModuleId]);
    $loAssessments = $loStmt->fetchAll();

    // Get module exam result
    $examStmt = $pdo->prepare("
        SELECT e.exam_title, es.percentage, es.status, es.submitted_at
        FROM exams e
        LEFT JOIN exam_submissions es ON e.exam_id = es.exam_id AND es.student_id = ?
        WHERE e.module_id = ? AND e.status = 'published'
        ORDER BY es.submitted_at DESC LIMIT 1
    ");
    $examStmt->execute([$selectedStudentId, $selectedModuleId]);
    $moduleExam = $examStmt->fetch();
}

include_once '../includes/templates/header.php';
?>

<div class="student-progress">
    <div class="page-header">
        <h1><i class="fas fa-chart-line"></i> Student Progress Monitor</h1>
        <p>View detailed progress of individual students</p>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label>Select Module</label>
                <select name="module_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Select Module --</option>
                    <?php foreach ($modules as $mod): ?>
                        <option value="<?= $mod['module_id'] ?>" <?= $selectedModuleId == $mod['module_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mod['module_code']) ?> - <?= htmlspecialchars($mod['module_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($selectedModuleId > 0 && !empty($students)): ?>
                <div class="form-group">
                    <label>Select Student</label>
                    <select name="student_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $stu): ?>
                            <option value="<?= $stu['user_id'] ?>" <?= $selectedStudentId == $stu['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($stu['full_name']) ?> (<?= htmlspecialchars($stu['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if ($selectedModuleId && $selectedStudentId): ?>
                <a href="student-progress.php?module_id=<?= $selectedModuleId ?>" class="btn-clear">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($selectedStudentId && $studentData): ?>
        <div class="student-info">
            <h2><?= htmlspecialchars($studentData['full_name']) ?></h2>
            <p><?= htmlspecialchars($studentData['email']) ?></p>
        </div>

        <!-- Overall Progress Card -->
        <div class="progress-overview">
            <div class="progress-bar-large">
                <div class="progress-fill" style="width: <?= $moduleProgress ?>%;">
                    <span><?= $moduleProgress ?>%</span>
                </div>
            </div>
            <p>Overall Module Completion</p>
        </div>

        <!-- Topics Progress Table -->
        <div class="card">
            <h3><i class="fas fa-book"></i> Topics & Quizzes</h3>
            <div class="table-wrapper">
                <table class="progress-table">
                    <thead>
                        <tr><th>LO</th><th>Topic</th><th>Quiz Passed</th><th>Score</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topics as $topic): ?>
                            <tr>
                                <td>LO<?= $topic['outcome_number'] ?></td>
                                <td><?= htmlspecialchars($topic['topic_title']) ?></td>
                                <td>
                                    <?php if ($topic['quiz_passed']): ?>
                                        <span class="badge passed">✅ Passed</span>
                                    <?php else: ?>
                                        <span class="badge not-passed">❌ Not passed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $topic['quiz_score'] ? $topic['quiz_score'] . ' pts' : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- LO Assessments -->
        <div class="card">
            <h3><i class="fas fa-clipboard-list"></i> Learning Outcome Assessments</h3>
            <div class="table-wrapper">
                <table class="progress-table">
                    <thead>
                        <tr><th>LO</th><th>Description</th><th>Score (%)</th><th>Status</th><th>Attempts</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loAssessments as $lo): ?>
                            <tr>
                                <td>LO<?= $lo['outcome_number'] ?></td>
                                <td><?= htmlspecialchars(substr($lo['description'], 0, 60)) ?>...</td>
                                <td class="score"><?= $lo['percentage'] ? round($lo['percentage']) . '%' : '—' ?></td>
                                <td>
                                    <?php if ($lo['status'] == 'passed'): ?>
                                        <span class="badge passed">Passed</span>
                                    <?php elseif ($lo['status'] == 'failed'): ?>
                                        <span class="badge failed">Failed</span>
                                    <?php else: ?>
                                        <span class="badge pending">Not taken</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $lo['attempt_number'] ?: '0' ?></td>
                                <td><?= $lo['submitted_at'] ? date('M d, Y', strtotime($lo['submitted_at'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Module Exam -->
        <div class="card">
            <h3><i class="fas fa-graduation-cap"></i> Module Final Exam</h3>
            <?php if ($moduleExam): ?>
                <p><strong>Exam:</strong> <?= htmlspecialchars($moduleExam['exam_title']) ?></p>
                <p><strong>Score:</strong> <?= round($moduleExam['percentage']) ?>%</p>
                <p><strong>Status:</strong> 
                    <span class="badge <?= $moduleExam['status'] ?>"><?= ucfirst($moduleExam['status']) ?></span>
                </p>
                <p><strong>Submitted:</strong> <?= date('M d, Y H:i', strtotime($moduleExam['submitted_at'])) ?></p>
            <?php else: ?>
                <p>Module exam not yet taken.</p>
            <?php endif; ?>
        </div>

    <?php elseif ($selectedModuleId > 0 && empty($students)): ?>
        <div class="info-message">No students enrolled in this module.</div>
    <?php elseif ($selectedModuleId > 0 && $selectedStudentId == 0): ?>
        <div class="info-message">Please select a student to view progress.</div>
    <?php elseif ($selectedModuleId == 0): ?>
        <div class="info-message">Please select a module to view student progress.</div>
    <?php endif; ?>
</div>

<style>
    .student-progress { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .filter-bar { background: white; border-radius: 1rem; padding: 1rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .filter-form { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
    .form-group { flex: 1; min-width: 200px; }
    .form-group label { display: block; margin-bottom: 0.3rem; font-size: 0.8rem; color: #6c8faa; }
    .form-control { width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; }
    .btn-clear { background: #999; color: white; padding: 0.3rem 0.8rem; border-radius: 1rem; text-decoration: none; font-size: 0.8rem; align-self: center; }
    .student-info { background: white; border-radius: 1rem; padding: 1rem; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .progress-overview { background: white; border-radius: 1rem; padding: 1rem; margin-bottom: 1.5rem; text-align: center; }
    .progress-bar-large { background: #e2e8f0; border-radius: 30px; height: 30px; overflow: hidden; }
    .progress-fill { background: linear-gradient(90deg, #2c7da0, #60b8d4); height: 100%; border-radius: 30px; display: flex; align-items: center; justify-content: flex-end; padding-right: 10px; color: white; font-weight: bold; }
    .card { background: white; border-radius: 1rem; padding: 1rem; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .table-wrapper { overflow-x: auto; }
    .progress-table { width: 100%; border-collapse: collapse; }
    .progress-table th, .progress-table td { padding: 8px; text-align: left; border-bottom: 1px solid #eef2f8; }
    .badge { padding: 2px 8px; border-radius: 1rem; font-size: 0.7rem; font-weight: 600; display: inline-block; }
    .badge.passed, .badge.passed { background: #e8f5e9; color: #2e7d32; }
    .badge.not-passed, .badge.failed { background: #ffebee; color: #c62828; }
    .badge.pending { background: #fff3e0; color: #c76f1c; }
    .score { font-weight: 600; }
    .info-message { text-align: center; padding: 2rem; background: #f8fafc; border-radius: 1rem; color: #8aaec0; }
</style>

<?php include_once '../includes/templates/footer.php'; ?>