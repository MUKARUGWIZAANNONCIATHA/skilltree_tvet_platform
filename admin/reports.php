<?php
/**
 * Reports Dashboard
 * Path: /admin/reports.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$totalStudents = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'teacher' AND is_approved = 1");
$totalTeachers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM modules WHERE status = 'published'");
$totalModules = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM student_enrollments WHERE status = 'in_progress'");
$activeEnrollments = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM student_enrollments WHERE status = 'completed'");
$completedModules = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT AVG(overall_progress) as avg FROM student_enrollments");
$avgProgress = round($stmt->fetch()['avg'] ?? 0);

// Get module popularity
$stmt = $pdo->query("
    SELECT m.module_code, m.module_name, COUNT(se.enrollment_id) as enrollments 
    FROM modules m 
    LEFT JOIN student_enrollments se ON m.module_id = se.module_id 
    GROUP BY m.module_id 
    ORDER BY enrollments DESC 
    LIMIT 5
");
$popularModules = $stmt->fetchAll();

include_once '../includes/templates/header.php';
?>

<div class="reports-container">
    <div class="page-header">
        <h1><i class="fas fa-chart-line"></i> Reports Dashboard</h1>
        <button class="btn-export" onclick="window.print()"><i class="fas fa-print"></i> Export Report</button>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-users"></i></div><div class="stat-info"><h3><?php echo $totalStudents; ?></h3><p>Total Students</p></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-chalkboard-user"></i></div><div class="stat-info"><h3><?php echo $totalTeachers; ?></h3><p>Active Teachers</p></div></div>
        <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-book"></i></div><div class="stat-info"><h3><?php echo $totalModules; ?></h3><p>Published Modules</p></div></div>
        <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-play-circle"></i></div><div class="stat-info"><h3><?php echo $activeEnrollments; ?></h3><p>Active Enrollments</p></div></div>
        <div class="stat-card"><div class="stat-icon teal"><i class="fas fa-check-circle"></i></div><div class="stat-info"><h3><?php echo $completedModules; ?></h3><p>Completed Modules</p></div></div>
        <div class="stat-card"><div class="stat-icon pink"><i class="fas fa-chart-line"></i></div><div class="stat-info"><h3><?php echo $avgProgress; ?>%</h3><p>Avg Progress</p></div></div>
    </div>

    <div class="reports-grid">
        <div class="report-card"><h3><i class="fas fa-fire"></i> Most Popular Modules</h3>
            <table class="report-table"><?php foreach($popularModules as $module): ?><tr><td><?php echo htmlspecialchars($module['module_code']); ?></td><td><?php echo htmlspecialchars($module['module_name']); ?></td><td class="text-right"><?php echo $module['enrollments']; ?> students</td></tr><?php endforeach; ?></table>
        </div>
        <div class="report-card"><h3><i class="fas fa-calendar"></i> Quick Actions</h3>
            <a href="/admin/users.php" class="action-link"><i class="fas fa-users"></i> Manage Users</a>
            <a href="/admin/modules.php" class="action-link"><i class="fas fa-book"></i> Manage Modules</a>
            <a href="/admin/sectors.php" class="action-link"><i class="fas fa-chart-pie"></i> Manage Sectors</a>
            <a href="/admin/system-logs.php" class="action-link"><i class="fas fa-history"></i> View System Logs</a>
        </div>
    </div>
</div>

<style>
.reports-container{max-width:1400px;margin:0 auto;padding:30px 24px;}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;}.btn-export{background:#4CAF50;color:white;border:none;padding:12px 24px;border-radius:30px;cursor:pointer;}.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px;}.stat-card{background:white;border-radius:20px;padding:20px;display:flex;align-items:center;gap:15px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}.stat-icon{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;color:white;}.stat-icon.blue{background:#667eea;}.stat-icon.green{background:#4CAF50;}.stat-icon.orange{background:#ff8c42;}.stat-icon.purple{background:#9c27b0;}.stat-icon.teal{background:#009688;}.stat-icon.pink{background:#e91e63;}.stat-info h3{font-size:28px;margin:0;}.stat-info p{margin:5px 0 0;color:#666;}.reports-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;}.report-card{background:white;border-radius:20px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}.report-card h3{margin-bottom:20px;color:#1a1a2e;}.report-table{width:100%;}.report-table td{padding:10px 0;border-bottom:1px solid #eee;}.text-right{text-align:right;}.action-link{display:flex;align-items:center;gap:10px;padding:12px;background:#f8f9fa;border-radius:12px;text-decoration:none;color:#333;margin-bottom:10px;transition:all 0.3s;}.action-link:hover{background:#667eea;color:white;}@media(max-width:900px){.reports-grid{grid-template-columns:1fr;}}
</style>

<?php include_once '../includes/templates/footer.php'; ?> 
