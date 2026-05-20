<?php
/**
 * Manage Teachers
 * Path: /admin/teachers.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions/common.php';

$message = '';
$error = '';

// Auto-create teachers table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS teachers (
        teacher_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        employee_id VARCHAR(50),
        department VARCHAR(100),
        qualification TEXT,
        specialization VARCHAR(255),
        bio TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {}

// Get sectors for department dropdown
$sectors = $pdo->query("SELECT sector_name FROM sectors WHERE status = 'active' ORDER BY sector_name")->fetchAll(PDO::FETCH_COLUMN);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);

    if ($action === 'register_teacher') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $department = trim($_POST['department'] ?? '');
        $employeeId = 'TCH' . date('Ymd') . rand(1000, 9999);

        if (empty($fullName) || empty($email) || empty($password)) {
            $error = "Full name, email, and password are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetchColumn() > 0) {
                $error = "A user with this email already exists.";
            } else {
                $pdo->beginTransaction();
                try {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, is_active, is_approved) VALUES (?, ?, ?, 'teacher', 1, 1)");
                    $stmt->execute([$fullName, $email, $hashedPassword]);
                    $newUserId = $pdo->lastInsertId();
                    $stmt = $pdo->prepare("INSERT INTO teachers (user_id, employee_id, department) VALUES (?, ?, ?)");
                    $stmt->execute([$newUserId, $employeeId, $department]);
                    $pdo->commit();
                    logActivity($_SESSION['user_id'], 'register_teacher', "Registered teacher: $fullName ($email)");
                    $message = "Teacher registered successfully! Employee ID: $employeeId";
                    header('Location: teachers.php?msg=' . urlencode($message));
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Registration failed: " . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'activate') {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = ? AND role = 'teacher'");
        $stmt->execute([$userId]);
        logActivity($_SESSION['user_id'], 'activate_teacher', "Activated teacher ID: $userId");
        $message = "Teacher activated successfully!";
    }
    elseif ($action === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = ? AND role = 'teacher'");
        $stmt->execute([$userId]);
        logActivity($_SESSION['user_id'], 'deactivate_teacher', "Deactivated teacher ID: $userId");
        $message = "Teacher deactivated successfully!";
    }
    elseif ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET is_approved = 1 WHERE user_id = ? AND role = 'teacher'");
        $stmt->execute([$userId]);
        logActivity($_SESSION['user_id'], 'approve_teacher', "Approved teacher ID: $userId");
        $message = "Teacher approved successfully!";
    }
    elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM teachers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'teacher'");
        $stmt->execute([$userId]);
        logActivity($_SESSION['user_id'], 'delete_teacher', "Deleted teacher ID: $userId");
        $message = "Teacher deleted successfully!";
    }
    header('Location: teachers.php?msg=' . urlencode($message));
    exit;
}

// Filters
$statusFilter = $_GET['status'] ?? 'all';
$approvalFilter = $_GET['approval'] ?? 'all';
$searchTerm = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query
$where = ["u.role = 'teacher'"];
$params = [];

if ($statusFilter === 'active') {
    $where[] = "u.is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $where[] = "u.is_active = 0";
}
if ($approvalFilter === 'approved') {
    $where[] = "u.is_approved = 1";
} elseif ($approvalFilter === 'pending') {
    $where[] = "u.is_approved = 0";
}
if (!empty($searchTerm)) {
    $where[] = "(u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}
$whereClause = "WHERE " . implode(" AND ", $where);

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users u LEFT JOIN teachers t ON u.user_id = t.user_id $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// Fetch teachers with JOIN
$sql = "SELECT u.*, t.teacher_id, COALESCE(t.employee_id, u.employee_id) AS employee_id, COALESCE(t.department, u.department) AS department FROM users u LEFT JOIN teachers t ON u.user_id = t.user_id $whereClause ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$limit, $offset]));
$teachers = $stmt->fetchAll();

// Stats for cards
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$activeTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND is_active = 1")->fetchColumn();
$pendingApproval = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND is_approved = 0")->fetchColumn();

include_once '../includes/templates/header.php';

if (isset($_GET['msg'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['msg']) . '</div>';
}
?>

<div class="teachers-container">
    <div class="page-header">
        <h1><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</h1>
        <p>View, activate, approve, and manage teacher accounts</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search by name or email..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Status</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <select name="approval" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?= $approvalFilter === 'all' ? 'selected' : '' ?>>All Approval</option>
                <option value="approved" <?= $approvalFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="pending" <?= $approvalFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            </select>
            <button type="submit" class="btn-apply">Filter</button>
            <a href="teachers.php" class="btn-clear">Clear</a>
        </form>
    </div>

    <!-- Register Button -->
    <div class="register-bar">
        <button class="btn-register" onclick="document.getElementById('registerModal').style.display='flex'">
            <i class="fas fa-user-plus"></i> Register New Teacher
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-mini">
            <span class="stat-value"><?= $totalTeachers ?></span>
            <span class="stat-label">Total Teachers</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?= $activeTeachers ?></span>
            <span class="stat-label">Active</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?= $pendingApproval ?></span>
            <span class="stat-label">Pending Approval</span>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Teachers Table -->
    <?php if (empty($teachers)): ?>
        <div class="empty-state">
            <i class="fas fa-chalkboard-teacher"></i>
            <h3>No teachers found</h3>
            <p>Adjust filters or wait for teachers to register.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="teachers-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Employee ID</th>
                        <th>Status</th>
                        <th>Approval</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <tr><?= $teacher['user_id'] ?></td>
                        <td><strong><?= htmlspecialchars($teacher['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($teacher['email']) ?></td>
                        <td><?= htmlspecialchars($teacher['department'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($teacher['employee_id'] ?: '—') ?></td>
                        <td>
                            <span class="status-badge <?= $teacher['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $teacher['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <span class="approval-badge <?= $teacher['is_approved'] ? 'approved' : 'pending' ?>">
                                <?= $teacher['is_approved'] ? 'Approved' : 'Pending' ?>
                            </span>
                        </td>
                        <td><?= date('d M Y', strtotime($teacher['created_at'])) ?></td>
                        <td class="actions-cell">
                            <?php if ($teacher['is_active']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="user_id" value="<?= $teacher['user_id'] ?>">
                                    <button type="submit" class="action-icon deactivate" title="Deactivate">🔴</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="user_id" value="<?= $teacher['user_id'] ?>">
                                    <button type="submit" class="action-icon activate" title="Activate">🟢</button>
                                </form>
                            <?php endif; ?>
                            <?php if (!$teacher['is_approved']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="user_id" value="<?= $teacher['user_id'] ?>">
                                    <button type="submit" class="action-icon approve" title="Approve">✓</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this teacher permanently? This will also delete their modules and activities.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?= $teacher['user_id'] ?>">
                                <button type="submit" class="action-icon delete" title="Delete">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&status=<?= urlencode($statusFilter) ?>&approval=<?= urlencode($approvalFilter) ?>&search=<?= urlencode($searchTerm) ?>" class="page-link">Previous</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&status=<?= urlencode($statusFilter) ?>&approval=<?= urlencode($approvalFilter) ?>&search=<?= urlencode($searchTerm) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page+1 ?>&status=<?= urlencode($statusFilter) ?>&approval=<?= urlencode($approvalFilter) ?>&search=<?= urlencode($searchTerm) ?>" class="page-link">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</div>

<!-- Register Teacher Modal -->
<div class="modal" id="registerModal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> Register New Teacher</h2>
            <span class="close" onclick="document.getElementById('registerModal').style.display='none'">&times;</span>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="register_teacher">
            <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password <span class="required">*</span></label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="form-group">
                <label>Department / Sector</label>
                <select name="department" class="form-control" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach ($sectors as $sector): ?>
                        <option value="<?= htmlspecialchars($sector) ?>"><?= htmlspecialchars($sector) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="background:#f0f4ff; padding:10px 14px; border-radius:10px; font-size:13px; color:#666;">
                <i class="fas fa-id-card"></i> Employee ID will be auto-generated (e.g., TCH20260519XXXX)
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-save-register">Register Teacher</button>
                <button type="button" class="btn-cancel-modal" onclick="document.getElementById('registerModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
    .teachers-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 30px 24px;
    }
    .page-header {
        margin-bottom: 25px;
    }
    .page-header h1 {
        font-size: 28px;
        color: #1a1a2e;
        margin-bottom: 5px;
    }
    .filter-bar {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }
    .search-wrapper {
        position: relative;
        flex: 2;
        min-width: 200px;
    }
    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }
    .search-input {
        width: 100%;
        padding: 10px 12px 10px 35px;
        border: 1px solid #ddd;
        border-radius: 30px;
        font-size: 14px;
    }
    .filter-select {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 30px;
        background: white;
        cursor: pointer;
    }
    .btn-apply {
        background: #667eea;
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 30px;
        cursor: pointer;
    }
    .btn-clear {
        color: #f44336;
        text-decoration: none;
        font-size: 14px;
    }
    .stats-row {
        display: flex;
        gap: 20px;
        margin-bottom: 25px;
    }
    .stat-mini {
        background: white;
        border-radius: 16px;
        padding: 15px 20px;
        text-align: center;
        flex: 1;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #667eea;
        display: block;
    }
    .table-wrapper {
        background: white;
        border-radius: 16px;
        overflow-x: auto;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .teachers-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }
    .teachers-table th, .teachers-table td {
        padding: 14px 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    .teachers-table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    .status-badge, .approval-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
    }
    .status-badge.active { background: #e8f5e9; color: #2e7d32; }
    .status-badge.inactive { background: #ffebee; color: #c62828; }
    .approval-badge.approved { background: #e8f5e9; color: #2e7d32; }
    .approval-badge.pending { background: #fff3e0; color: #ff9800; }
    .actions-cell {
        white-space: nowrap;
    }
    .action-icon {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        margin: 0 3px;
        opacity: 0.7;
        transition: 0.2s;
    }
    .action-icon:hover { opacity: 1; transform: scale(1.1); }
    .action-icon.approve { color: #4CAF50; }
    .action-icon.activate { color: #4CAF50; }
    .action-icon.deactivate { color: #ff9800; }
    .action-icon.delete { color: #f44336; }
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 20px;
    }
    .page-link {
        padding: 6px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        text-decoration: none;
        color: #667eea;
    }
    .page-link.active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    .empty-state {
        text-align: center;
        padding: 60px;
        background: white;
        border-radius: 16px;
    }
    .alert {
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        border-left: 4px solid #4CAF50;
    }
    .register-bar { margin-bottom: 20px; }
    .btn-register { background: linear-gradient(135deg,#667eea,#764ba2); color: white; border: none; padding: 12px 24px; border-radius: 30px; cursor: pointer; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
    .btn-register:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102,126,234,0.4); }
    .alert-error { background: #ffebee; color: #c62828; border-left: 4px solid #f44336; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; }
    .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .modal-content { background: white; border-radius: 20px; width: 500px; max-width: 90%; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-header h2 { margin: 0; font-size: 20px; color: #1a1a2e; }
    .close { font-size: 28px; cursor: pointer; color: #999; }
    .close:hover { color: #333; }
    .required { color: #f44336; }
    .form-actions { display: flex; gap: 12px; margin-top: 20px; }
    .btn-save-register { background: linear-gradient(135deg,#667eea,#764ba2); color: white; border: none; padding: 10px 25px; border-radius: 30px; cursor: pointer; flex: 1; }
    .btn-cancel-modal { background: #f0f0f0; color: #666; border: none; padding: 10px 25px; border-radius: 30px; cursor: pointer; }
    @media (max-width: 900px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
        .stats-row {
            flex-direction: column;
        }
    }
</style>

<?php include_once '../includes/templates/footer.php'; ?>