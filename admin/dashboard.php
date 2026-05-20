<?php
/**
 * Admin Dashboard - Complete with Profile & Backup Links
 * Path: /admin/dashboard.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

// === STATISTICS ===
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND is_active = 1");
$students = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending FROM users WHERE role = 'teacher'");
$teachers = $stmt->fetch();

$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending FROM users WHERE role = 'company'");
$companies = $stmt->fetch();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM modules");
$modules = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM sectors");
$sectors = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM internships");
$internships = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM anti_cheat_logs");
$antiCheatLogs = $stmt->fetch()['total'];

// === PENDING APPROVALS (teachers and companies) ===
$pendTeachers = $pdo->query("SELECT user_id, full_name, email, created_at FROM users WHERE role = 'teacher' AND is_approved = 0");
$pendingTeachers = $pendTeachers->fetchAll();

$pendCompanies = $pdo->query("SELECT user_id, company_name, email, location, created_at FROM users WHERE role = 'company' AND is_approved = 0");
$pendingCompanies = $pendCompanies->fetchAll();

// === RECENT ACTIVITIES ===
$stmt = $pdo->query("SELECT * FROM user_activity_log ORDER BY created_at DESC LIMIT 10");
$recentActivities = $stmt->fetchAll();

// Handle approval/rejection directly (simple POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);

    if ($action === 'approve_teacher') {
        $stmt = $pdo->prepare("UPDATE users SET is_approved = 1 WHERE user_id = ? AND role = 'teacher'");
        $stmt->execute([$userId]);
        $message = "Teacher approved.";
        logActivity($_SESSION['user_id'], 'approve_teacher', "Approved teacher ID: $userId");
    } elseif ($action === 'approve_company') {
        $stmt = $pdo->prepare("UPDATE users SET is_approved = 1 WHERE user_id = ? AND role = 'company'");
        $stmt->execute([$userId]);
        $message = "Company approved.";
        logActivity($_SESSION['user_id'], 'approve_company', "Approved company ID: $userId");
    } elseif ($action === 'reject_user') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $message = "User rejected and deleted.";
        logActivity($_SESSION['user_id'], 'reject_user', "Rejected user ID: $userId");
    }
    header("Location: dashboard.php?msg=" . urlencode($message));
    exit;
}

include_once '../includes/templates/header.php';
?>

<div class="admin-container">
    <div class="page-header">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3><?php echo $students; ?></h3>
                <p>Active Students</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-chalkboard-user"></i></div>
            <div class="stat-info">
                <h3><?php echo $teachers['total']; ?></h3>
                <p>Teachers</p>
                <?php if($teachers['pending'] > 0): ?>
                    <span class="badge pending"><?php echo $teachers['pending']; ?> pending</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-building"></i></div>
            <div class="stat-info">
                <h3><?php echo $companies['total']; ?></h3>
                <p>Companies</p>
                <?php if($companies['pending'] > 0): ?>
                    <span class="badge pending"><?php echo $companies['pending']; ?> pending</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-book"></i></div>
            <div class="stat-info">
                <h3><?php echo $modules; ?></h3>
                <p>Modules</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <h3><?php echo $sectors; ?></h3>
                <p>Sectors</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon indigo"><i class="fas fa-briefcase"></i></div>
            <div class="stat-info">
                <h3><?php echo $internships; ?></h3>
                <p>Internships</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-shield-alt"></i></div>
            <div class="stat-info">
                <h3><?php echo $antiCheatLogs; ?></h3>
                <p>Anti-Cheat logs</p>
            </div>
        </div>
    </div>

    <!-- Pending Approvals Section -->
    <?php if ($teachers['pending'] > 0 || $companies['pending'] > 0): ?>
    <div class="pending-section">
        <h2><i class="fas fa-clock"></i> Pending Approvals</h2>

        <?php if ($teachers['pending'] > 0): ?>
        <div class="pending-card">
            <h3>Teachers Awaiting Approval</h3>
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Registered</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($pendingTeachers as $teacher): ?>
                    <tr>
                        <td><?= htmlspecialchars($teacher['full_name']) ?></td>
                        <td><?= htmlspecialchars($teacher['email']) ?></td>
                        <td><?= date('M d, Y', strtotime($teacher['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="approve_teacher">
                                <input type="hidden" name="user_id" value="<?= $teacher['user_id'] ?>">
                                <button type="submit" class="btn-approve">Approve</button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Reject and delete this teacher?')">
                                <input type="hidden" name="action" value="reject_user">
                                <input type="hidden" name="user_id" value="<?= $teacher['user_id'] ?>">
                                <button type="submit" class="btn-reject">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($companies['pending'] > 0): ?>
        <div class="pending-card">
            <h3>Companies Awaiting Approval</h3>
            <table class="data-table">
                <thead><tr><th>Company</th><th>Email</th><th>Location</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($pendingCompanies as $company): ?>
                    <tr>
                        <td><?= htmlspecialchars($company['company_name']) ?></td>
                        <td><?= htmlspecialchars($company['email']) ?></td>
                        <td><?= htmlspecialchars($company['location'] ?: '—') ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="approve_company">
                                <input type="hidden" name="user_id" value="<?= $company['user_id'] ?>">
                                <button type="submit" class="btn-approve">Approve</button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Reject and delete this company?')">
                                <input type="hidden" name="action" value="reject_user">
                                <input type="hidden" name="user_id" value="<?= $company['user_id'] ?>">
                                <button type="submit" class="btn-reject">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Quick Actions – added Profile and Backup -->
    <div class="quick-actions">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="actions-grid">
            <a href="/admin/profile.php" class="action-card">
                <i class="fas fa-user-cog"></i>
                <span>My Profile</span>
            </a>
            <a href="/admin/backup.php" class="action-card">
                <i class="fas fa-database"></i>
                <span>Backup</span>
            </a>
            <a href="/admin/students.php" class="action-card">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="/admin/teachers.php" class="action-card">
                <i class="fas fa-chalkboard-user"></i>
                <span>Teachers</span>
            </a>
            <a href="/admin/companies.php" class="action-card">
                <i class="fas fa-building"></i>
                <span>Companies</span>
            </a>
            <a href="/admin/users.php" class="action-card">
                <i class="fas fa-users"></i>
                <span>All Users</span>
            </a>
            <a href="/admin/internships.php" class="action-card">
                <i class="fas fa-briefcase"></i>
                <span>Internships</span>
            </a>
            <a href="/admin/anti-cheat-logs.php" class="action-card">
                <i class="fas fa-shield-alt"></i>
                <span>Anti-Cheat Logs</span>
            </a>
            <a href="/admin/modules.php" class="action-card">
                <i class="fas fa-book"></i>
                <span>Modules</span>
            </a>
            <a href="/admin/assign-teachers.php" class="action-card">
                <i class="fas fa-book"></i>
                <span>Assign teacher</span>
            </a>
            <a href="/admin/sectors.php" class="action-card">
                <i class="fas fa-chart-pie"></i>
                <span>Sectors</span>
            </a>
            <a href="/admin/trades.php" class="action-card">
                <i class="fas fa-briefcase"></i>
                <span>Trades</span>
            </a>
            <a href="/admin/levels.php" class="action-card">
                <i class="fas fa-layer-group"></i>
                <span>Levels</span>
            </a>
            <a href="/admin/system-logs.php" class="action-card">
                <i class="fas fa-history"></i>
                <span>System Logs</span>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h2><i class="fas fa-clock"></i> Recent Activity</h2>
        <div class="activity-list">
            <?php foreach ($recentActivities as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <?php echo getActivityIcon($activity['action_type']); ?>
                </div>
                <div class="activity-details">
                    <p><?php echo htmlspecialchars($activity['action_details']); ?></p>
                    <span class="activity-time"><?php echo time_ago($activity['created_at']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
/* (existing styles – kept as before) */
.admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px 20px;
}
.page-header {
    margin-bottom: 30px;
}
.page-header h1 {
    font-size: 28px;
    color: #1e3a5f;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}
.stat-icon.blue { background: #667eea; }
.stat-icon.green { background: #4CAF50; }
.stat-icon.orange { background: #ff8c42; }
.stat-icon.purple { background: #9c27b0; }
.stat-icon.teal { background: #009688; }
.stat-icon.indigo { background: #3f51b5; }
.stat-icon.red { background: #f44336; }
.stat-info h3 {
    font-size: 28px;
    margin: 0;
    color: #333;
}
.stat-info p {
    margin: 5px 0 0;
    color: #666;
}
.badge.pending {
    background: #ff9800;
    color: white;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 12px;
    margin-left: 10px;
}
.pending-section, .quick-actions, .recent-activity {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 30px;
}
.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
.data-table th, .data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
.data-table th {
    background: #f8f9fa;
    font-weight: 600;
}
.btn-approve, .btn-reject {
    padding: 5px 12px;
    border-radius: 20px;
    border: none;
    cursor: pointer;
    margin: 0 5px;
}
.btn-approve {
    background: #4CAF50;
    color: white;
}
.btn-reject {
    background: #f44336;
    color: white;
}
.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 15px;
}
.action-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s;
    display: block;
}
.action-card:hover {
    background: #667eea;
    color: white;
    transform: translateY(-3px);
}
.action-card i {
    font-size: 28px;
    margin-bottom: 8px;
    display: block;
    color: #667eea;
}
.action-card:hover i {
    color: white;
}
.action-card span {
    display: block;
    font-size: 13px;
}
.activity-list {
    max-height: 400px;
    overflow-y: auto;
}
.activity-item {
    display: flex;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}
.activity-time {
    font-size: 12px;
    color: #999;
}
.alert {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
}
</style>

<?php include_once '../includes/templates/footer.php'; ?>