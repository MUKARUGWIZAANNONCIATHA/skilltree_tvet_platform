<?php
/**
 * Student Results - Summary of all students in a module
 * Path: /teacher/student-results.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

// Get teacher's modules
if ($role === 'admin') {
    $modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT module_id, module_code, module_name FROM modules WHERE created_by = ? ORDER BY module_code");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
}

$selectedModuleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$export = isset($_GET['export']) ? $_GET['export'] : '';

// Fetch students results for the selected module
$studentsResults = [];
$moduleInfo = null;

if ($selectedModuleId > 0) {
    // Get module info
    $modStmt = $pdo->prepare("SELECT module_code, module_name FROM modules WHERE module_id = ?");
    $modStmt->execute([$selectedModuleId]);
    $moduleInfo = $modStmt->fetch();

    // Get all students enrolled in this module
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.user_id, u.full_name, u.email
        FROM student_enrollments e
        JOIN users u ON e.student_id = u.user_id
        WHERE e.module_id = ? AND e.status != 'dropped'
        ORDER BY u.full_name
    ");
    $stmt->execute([$selectedModuleId]);
    $students = $stmt->fetchAll();

    // For each student, calculate metrics
    foreach ($students as $student) {
        // Overall module progress (topics quiz passed)
        $progressStmt = $pdo->prepare("
            SELECT COUNT(t.topic_id) as total,
                   COUNT(tp.quiz_passed) as completed
            FROM topics t
            JOIN indicative_contents ic ON t.ic_id = ic.ic_id
            JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
            LEFT JOIN topic_progress tp ON t.topic_id = tp.topic_id AND tp.student_id = ? AND tp.quiz_passed = 1
            WHERE lo.module_id = ?
        ");
        $progressStmt->execute([$student['user_id'], $selectedModuleId]);
        $progress = $progressStmt->fetch();
        $moduleProgress = $progress['total'] > 0 ? round(($progress['completed'] / $progress['total']) * 100) : 0;

        // Average LO assessment score (only passed ones)
        $loScoreStmt = $pdo->prepare("
            SELECT AVG(las.percentage) as avg_score
            FROM lo_assessment_submissions las
            JOIN lo_assessments la ON las.lo_assessment_id = la.lo_assessment_id
            JOIN learning_outcomes lo ON la.lo_id = lo.outcome_id
            WHERE lo.module_id = ? AND las.student_id = ? AND las.status = 'passed'
        ");
        $loScoreStmt->execute([$selectedModuleId, $student['user_id']]);
        $avgLoScore = round($loScoreStmt->fetchColumn() ?: 0);

        // Number of LO assessments passed
        $loPassedStmt = $pdo->prepare("
            SELECT COUNT(*) as passed
            FROM lo_assessment_submissions las
            JOIN lo_assessments la ON las.lo_assessment_id = la.lo_assessment_id
            JOIN learning_outcomes lo ON la.lo_id = lo.outcome_id
            WHERE lo.module_id = ? AND las.student_id = ? AND las.status = 'passed'
        ");
        $loPassedStmt->execute([$selectedModuleId, $student['user_id']]);
        $loPassed = $loPassedStmt->fetchColumn();

        // Total number of LOs for this module
        $totalLoStmt = $pdo->prepare("SELECT COUNT(*) FROM learning_outcomes WHERE module_id = ?");
        $totalLoStmt->execute([$selectedModuleId]);
        $totalLos = $totalLoStmt->fetchColumn();

        // Module exam result (latest submission)
        $examStmt = $pdo->prepare("
            SELECT es.percentage, es.status
            FROM exam_submissions es
            JOIN exams e ON es.exam_id = e.exam_id
            WHERE e.module_id = ? AND es.student_id = ? AND es.status IN ('passed', 'failed')
            ORDER BY es.submitted_at DESC LIMIT 1
        ");
        $examStmt->execute([$selectedModuleId, $student['user_id']]);
        $examResult = $examStmt->fetch();
        $examScore = $examResult ? round($examResult['percentage']) : 0;
        $examStatus = $examResult ? ucfirst($examResult['status']) : 'Not taken';

        $studentsResults[] = [
            'user_id' => $student['user_id'],
            'full_name' => $student['full_name'],
            'email' => $student['email'],
            'module_progress' => $moduleProgress,
            'lo_passed' => $loPassed,
            'total_los' => $totalLos,
            'avg_lo_score' => $avgLoScore,
            'exam_score' => $examScore,
            'exam_status' => $examStatus
        ];
    }

    // Export to CSV if requested
    if ($export === 'csv' && !empty($studentsResults)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="student_results_' . $moduleInfo['module_code'] . '.csv"');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['Student Name', 'Email', 'Module Progress (%)', 'LO Assessments Passed', 'Total LOs', 'Avg LO Score (%)', 'Exam Score (%)', 'Exam Status']);
        foreach ($studentsResults as $row) {
            fputcsv($output, [
                $row['full_name'],
                $row['email'],
                $row['module_progress'],
                $row['lo_passed'],
                $row['total_los'],
                $row['avg_lo_score'],
                $row['exam_score'],
                $row['exam_status']
            ]);
        }
        fclose($output);
        exit;
    }
}

include_once '../includes/templates/header.php';
?>

<div class="student-results">
    <div class="page-header">
        <h1><i class="fas fa-chart-bar"></i> Student Results</h1>
        <p>View and export student performance data for your modules</p>
    </div>

    <!-- Module Selection -->
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
            <?php if ($selectedModuleId && !empty($studentsResults)): ?>
                <a href="?module_id=<?= $selectedModuleId ?>&export=csv" class="btn-export">📊 Export to CSV</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($selectedModuleId == 0): ?>
        <div class="info-message">Please select a module to view student results.</div>
    <?php elseif (empty($studentsResults)): ?>
        <div class="info-message">No students enrolled in this module.</div>
    <?php else: ?>
        <div class="results-table-wrapper">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Module Progress</th>
                        <th>LO Assessments</th>
                        <th>Avg LO Score</th>
                        <th>Exam Score</th>
                        <th>Exam Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studentsResults as $res): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($res['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($res['email']) ?></td>
                            <td>
                                <div class="progress-bar-small">
                                    <div class="progress-fill-small" style="width: <?= $res['module_progress'] ?>%;"></div>
                                    <span><?= $res['module_progress'] ?>%</span>
                                </div>
                            </td>
                            <td><?= $res['lo_passed'] ?> / <?= $res['total_los'] ?></td>
                            <td><?= $res['avg_lo_score'] ?>%</td>
                            <td class="exam-score <?= $res['exam_score'] >= 70 ? 'high' : ($res['exam_score'] > 0 ? 'medium' : 'low') ?>">
                                <?= $res['exam_score'] ?>%
                            </td>
                            <td>
                                <span class="status-badge <?= strtolower($res['exam_status']) ?>">
                                    <?= $res['exam_status'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="student-progress.php?module_id=<?= $selectedModuleId ?>&student_id=<?= $res['user_id'] ?>" class="btn-view">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
    .student-results { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .filter-bar { background: white; border-radius: 1rem; padding: 1rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .filter-form { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
    .form-group { flex: 1; min-width: 200px; }
    .form-group label { display: block; margin-bottom: 0.3rem; font-size: 0.8rem; color: #6c8faa; }
    .form-control { width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; }
    .btn-export { background: #4CAF50; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 0.5rem; text-decoration: none; display: inline-block; }
    .results-table-wrapper { overflow-x: auto; background: white; border-radius: 1rem; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .results-table { width: 100%; border-collapse: collapse; }
    .results-table th, .results-table td { padding: 10px; text-align: left; border-bottom: 1px solid #eef2f8; }
    .results-table th { background: #f8fafc; font-weight: 600; }
    .progress-bar-small { width: 100px; background: #e2e8f0; border-radius: 20px; height: 20px; position: relative; display: inline-block; overflow: hidden; }
    .progress-fill-small { background: linear-gradient(90deg, #2c7da0, #60b8d4); height: 100%; border-radius: 20px; }
    .progress-bar-small span { position: absolute; left: 50%; transform: translateX(-50%); line-height: 20px; font-size: 0.7rem; font-weight: bold; color: #1e2f3e; }
    .exam-score { font-weight: 600; }
    .exam-score.high { color: #2e7d32; }
    .exam-score.medium { color: #ed6c02; }
    .exam-score.low { color: #d32f2f; }
    .status-badge { padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; display: inline-block; }
    .status-badge.passed { background: #e8f5e9; color: #2e7d32; }
    .status-badge.failed { background: #ffebee; color: #c62828; }
    .status-badge.not\ taken { background: #fff3e0; color: #c76f1c; }
    .btn-view { background: #2196F3; color: white; padding: 2px 8px; border-radius: 15px; text-decoration: none; font-size: 0.7rem; display: inline-block; }
    .info-message { text-align: center; padding: 2rem; background: #f8fafc; border-radius: 1rem; color: #8aaec0; }
</style>

<?php include_once '../includes/templates/footer.php'; ?>