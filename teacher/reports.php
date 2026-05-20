 
<?php
/**
 * Reports - Student Progress & Assessment Reports
 * Path: /teacher/reports.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$message = '';
$error = '';

// Get teacher's modules
if ($role === 'admin') {
    $modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT module_id, module_code, module_name FROM modules WHERE created_by = ? ORDER BY module_code");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
}

$selectedModuleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$reportType = $_GET['report'] ?? 'progress'; // progress, assessment, lo_performance

// Fetch report data
$reportData = [];
$headers = [];

if ($selectedModuleId > 0) {
    // Get enrolled students for this module
    $students = $pdo->prepare("
        SELECT DISTINCT u.user_id, u.full_name, u.email
        FROM student_enrollments e
        JOIN users u ON e.student_id = u.user_id
        WHERE e.module_id = ?
        ORDER BY u.full_name
    ");
    $students->execute([$selectedModuleId]);
    $studentsList = $students->fetchAll();

    if ($reportType === 'progress') {
        // Progress report: module and LO completion
        $headers = ['Student', 'Email', 'Module Progress (%)', 'LO1', 'LO2', 'LO3', 'LO4', 'LO5', 'Overall Status'];
        // Get LOs for this module
        $los = $pdo->prepare("SELECT outcome_id, outcome_number FROM learning_outcomes WHERE module_id = ? ORDER BY outcome_number");
        $los->execute([$selectedModuleId]);
        $losList = $los->fetchAll();
        $loCount = count($losList);
        
        foreach ($studentsList as $student) {
            // Overall module progress
            $progressStmt = $pdo->prepare("SELECT COUNT(t.topic_id) as total,
                (SELECT COUNT(DISTINCT tp.topic_id) FROM topic_progress tp
                 JOIN topics t2 ON tp.topic_id = t2.topic_id
                 JOIN indicative_contents ic ON t2.ic_id = ic.ic_id
                 JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
                 WHERE lo.module_id = ? AND tp.student_id = ? AND tp.quiz_passed = 1) as completed
                 FROM topics t
                 JOIN indicative_contents ic ON t.ic_id = ic.ic_id
                 JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
                 WHERE lo.module_id = ?");
            $progressStmt->execute([$selectedModuleId, $student['user_id'], $selectedModuleId]);
            $progress = $progressStmt->fetch();
            $moduleProgress = $progress['total'] > 0 ? round(($progress['completed'] / $progress['total']) * 100) : 0;
            
            // Per LO progress
            $loProgress = [];
            foreach ($losList as $lo) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(t.topic_id) as total,
                           COUNT(tp.quiz_passed) as passed
                    FROM topics t
                    JOIN indicative_contents ic ON t.ic_id = ic.ic_id
                    LEFT JOIN topic_progress tp ON t.topic_id = tp.topic_id AND tp.student_id = ? AND tp.quiz_passed = 1
                    WHERE ic.outcome_id = ?
                ");
                $stmt->execute([$student['user_id'], $lo['outcome_id']]);
                $res = $stmt->fetch();
                $pct = $res['total'] > 0 ? round(($res['passed'] / $res['total']) * 100) : 0;
                $loProgress[] = $pct . '%';
            }
            $status = ($moduleProgress >= 70) ? 'Passing' : (($moduleProgress > 0) ? 'In Progress' : 'Not Started');
            $row = array_merge([$student['full_name'], $student['email'], $moduleProgress . '%'], $loProgress, [$status]);
            $reportData[] = $row;
        }
        
    } elseif ($reportType === 'assessment') {
        // Assessment results: LO assessments and module exams
        $headers = ['Student', 'Email', 'LO Assessments (Avg %)', 'Module Exam Score (%)', 'Module Exam Status'];
        
        foreach ($studentsList as $student) {
            // Average LO assessment score for this module
            $loScoreStmt = $pdo->prepare("
                SELECT AVG(las.percentage) as avg_score
                FROM lo_assessment_submissions las
                JOIN lo_assessments la ON las.lo_assessment_id = la.lo_assessment_id
                JOIN learning_outcomes lo ON la.lo_id = lo.outcome_id
                WHERE lo.module_id = ? AND las.student_id = ? AND las.status = 'passed'
            ");
            $loScoreStmt->execute([$selectedModuleId, $student['user_id']]);
            $avgLoScore = round($loScoreStmt->fetchColumn() ?: 0);
            
            // Module exam score
            $examStmt = $pdo->prepare("
                SELECT es.percentage, es.status
                FROM exam_submissions es
                JOIN exams e ON es.exam_id = e.exam_id
                WHERE e.module_id = ? AND es.student_id = ? AND es.status IN ('passed', 'failed')
                ORDER BY es.submitted_at DESC LIMIT 1
            ");
            $examStmt->execute([$selectedModuleId, $student['user_id']]);
            $exam = $examStmt->fetch();
            $examScore = $exam ? round($exam['percentage']) : 0;
            $examStatus = $exam ? ucfirst($exam['status']) : 'Not taken';
            
            $reportData[] = [$student['full_name'], $student['email'], $avgLoScore . '%', $examScore . '%', $examStatus];
        }
        
    } elseif ($reportType === 'lo_performance') {
        // LO performance across all students (average scores per LO)
        $los = $pdo->prepare("SELECT outcome_id, outcome_number, description FROM learning_outcomes WHERE module_id = ? ORDER BY outcome_number");
        $los->execute([$selectedModuleId]);
        $losList = $los->fetchAll();
        $headers = ['Learning Outcome', 'Description', '# Students Attempted', 'Average Score (%)', 'Pass Rate (%)'];
        
        foreach ($losList as $lo) {
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT student_id) as attempted,
                       AVG(las.percentage) as avg_score,
                       SUM(CASE WHEN las.status = 'passed' THEN 1 ELSE 0 END) as passed,
                       COUNT(*) as total
                FROM lo_assessment_submissions las
                JOIN lo_assessments la ON las.lo_assessment_id = la.lo_assessment_id
                WHERE la.lo_id = ?
            ");
            $stmt->execute([$lo['outcome_id']]);
            $stats = $stmt->fetch();
            $attempted = $stats['attempted'] ?: 0;
            $avgScore = round($stats['avg_score'] ?: 0);
            $passRate = $stats['total'] > 0 ? round(($stats['passed'] / $stats['total']) * 100) : 0;
            $reportData[] = [
                "LO{$lo['outcome_number']}",
                htmlspecialchars(substr($lo['description'], 0, 60)),
                $attempted,
                $avgScore . '%',
                $passRate . '%'
            ];
        }
    }
}

// Handle export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv' && !empty($reportData)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report_' . $reportType . '_module_' . $selectedModuleId . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
    fputcsv($output, $headers);
    foreach ($reportData as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

include_once '../includes/templates/header.php';
?>

<div class="reports-container">
    <div class="page-header">
        <h1><i class="fas fa-chart-bar"></i> Student Reports</h1>
        <p>Generate progress reports, assessment results, and LO performance analytics</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label>Select Module</label>
                <select name="module_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- All Modules --</option>
                    <?php foreach ($modules as $mod): ?>
                        <option value="<?= $mod['module_id'] ?>" <?= $selectedModuleId == $mod['module_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mod['module_code']) ?> - <?= htmlspecialchars($mod['module_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Report Type</label>
                <select name="report" class="form-control" onchange="this.form.submit()">
                    <option value="progress" <?= $reportType == 'progress' ? 'selected' : '' ?>>Student Progress</option>
                    <option value="assessment" <?= $reportType == 'assessment' ? 'selected' : '' ?>>Assessment Results</option>
                    <option value="lo_performance" <?= $reportType == 'lo_performance' ? 'selected' : '' ?>>LO Performance Analytics</option>
                </select>
            </div>
            <?php if ($selectedModuleId && !empty($reportData)): ?>
                <a href="?module_id=<?= $selectedModuleId ?>&report=<?= $reportType ?>&export=csv" class="btn-export">📊 Export CSV</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($selectedModuleId == 0): ?>
        <div class="info-message">Please select a module to view reports.</div>
    <?php elseif (empty($reportData)): ?>
        <div class="info-message">No data available for the selected module and report type.</div>
    <?php else: ?>
        <div class="report-table-wrapper">
            <table class="report-table">
                <thead>
                    <tr>
                        <?php foreach ($headers as $h): ?>
                            <th><?= htmlspecialchars($h) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= htmlspecialchars($cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
    .reports-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .filter-bar { background: white; border-radius: 1rem; padding: 1rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .filter-form { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
    .form-group { flex: 1; min-width: 200px; }
    .form-group label { display: block; margin-bottom: 0.3rem; font-size: 0.8rem; color: #6c8faa; }
    .form-control { width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; }
    .btn-export { background: #4CAF50; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 0.5rem; text-decoration: none; display: inline-block; margin-top: 0.5rem; }
    .report-table-wrapper { overflow-x: auto; background: white; border-radius: 1rem; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .report-table { width: 100%; border-collapse: collapse; }
    .report-table th, .report-table td { padding: 10px; text-align: left; border-bottom: 1px solid #eef2f8; }
    .report-table th { background: #f8fafc; font-weight: 600; }
    .info-message { text-align: center; padding: 2rem; background: #f8fafc; border-radius: 1rem; color: #8aaec0; }
</style>

<?php include_once '../includes/templates/footer.php'; ?>