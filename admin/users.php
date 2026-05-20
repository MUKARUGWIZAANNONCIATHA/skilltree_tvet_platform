<?php
/**
 * Manage All Users
 * Path: /admin/users.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$currentAdminId = $_SESSION['user_id'];

// Helper: check if user exists
function userExists($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch() !== false;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);
    
    // Validate user_id > 0 and exists
    if ($userId <= 0 || !userExists($pdo, $userId)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid user.'];
        header('Location: /admin/users.php');
        exit();
    }
    
    // Prevent admin from deleting themselves
    if ($action === 'delete' && $userId == $currentAdminId) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'You cannot delete your own account.'];
        header('Location: /admin/users.php');
        exit();
    }
    
    if ($action === 'activate') {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        logActivity($_SESSION['user_id'], 'activate', "Activated user ID: $userId");
        $_SESSION['message'] = ['type' => 'success', 'text' => 'User activated successfully!'];
        
    } elseif ($action === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
        logActivity($_SESSION['user_id'], 'deactivate', "Deactivated user ID: $userId");
        $_SESSION['message'] = ['type' => 'success', 'text' => 'User deactivated successfully!'];
        
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        logActivity($_SESSION['user_id'], 'delete', "Deleted user ID: $userId");
        $_SESSION['message'] = ['type' => 'success', 'text' => 'User deleted successfully!'];
        
    } elseif ($action === 'approve_user') {
        $stmt = $pdo->prepare("UPDATE users SET is_approved = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        logActivity($_SESSION['user_id'], 'approve', "Approved user ID: $userId");
        $_SESSION['message'] = ['type' => 'success', 'text' => 'User approved successfully!'];
    }
    
    header('Location: /admin/users.php');
    exit();
}

// Get filter parameters – sanitize search term length (max 100)
$roleFilter = $_GET['role'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$approvalFilter = $_GET['approval'] ?? 'all';
$searchTerm = trim(substr($_GET['search'] ?? '', 0, 100));
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 15;
$offset = ($page - 1) * $itemsPerPage;

// Build query
$whereConditions = [];
$params = [];

if ($roleFilter !== 'all') {
    $whereConditions[] = "role = ?";
    $params[] = $roleFilter;
}
if ($statusFilter === 'active') {
    $whereConditions[] = "is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $whereConditions[] = "is_active = 0";
}
if ($approvalFilter === 'approved') {
    $whereConditions[] = "is_approved = 1";
} elseif ($approvalFilter === 'pending') {
    $whereConditions[] = "is_approved = 0";
}
if (!empty($searchTerm)) {
    $whereConditions[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Get users with pagination
$stmt = $pdo->prepare("SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$itemsPerPage, $offset]));
$users = $stmt->fetchAll();

// Get counts for stats cards
$totalAll = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$totalCompanies = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'company'")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

$pendingTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND is_approved = 0")->fetchColumn();
$pendingCompanies = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'company' AND is_approved = 0")->fetchColumn();

include_once '../includes/templates/header.php';

if (isset($_SESSION['message'])) {
    $msg = $_SESSION['message'];
    echo "<div class='alert alert-{$msg['type']}'>{$msg['text']}</div>";
    unset($_SESSION['message']);
}
?>

<div class="users-container">
    <div class="page-header">
        <h1><i class="fas fa-users"></i> Manage Users</h1>
        <p>View, manage, and control all platform users</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="" class="filter-form">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search by name or email..." maxlength="100" value="<?php echo htmlspecialchars($searchTerm); ?>">
            </div>
            <select name="role" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $roleFilter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Students</option>
                <option value="teacher" <?php echo $roleFilter === 'teacher' ? 'selected' : ''; ?>>Teachers</option>
                <option value="company" <?php echo $roleFilter === 'company' ? 'selected' : ''; ?>>Companies</option>
                <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admins</option>
            </select>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <select name="approval" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $approvalFilter === 'all' ? 'selected' : ''; ?>>All Approval</option>
                <option value="approved" <?php echo $approvalFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="pending" <?php echo $approvalFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
            </select>
            <button type="submit" class="btn-apply">Apply</button>
            <?php if(!empty($searchTerm) || $roleFilter !== 'all' || $statusFilter !== 'all' || $approvalFilter !== 'all'): ?>
                <a href="/admin/users.php" class="btn-clear">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-mini">
            <span class="stat-value"><?php echo $totalAll; ?></span>
            <span class="stat-label">Total Users</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?php echo $totalStudents; ?></span>
            <span class="stat-label">Students</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?php echo $totalTeachers; ?></span>
            <span class="stat-label">Teachers</span>
            <?php if($pendingTeachers > 0): ?>
                <span class="badge-pending"><?php echo $pendingTeachers; ?> pending</span>
            <?php endif; ?>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?php echo $totalCompanies; ?></span>
            <span class="stat-label">Companies</span>
            <?php if($pendingCompanies > 0): ?>
                <span class="badge-pending"><?php echo $pendingCompanies; ?> pending</span>
            <?php endif; ?>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?php echo $totalAdmins; ?></span>
            <span class="stat-label">Admins</span>
        </div>
    </div>

    <!-- Users Table -->
    <?php if(empty($users)): ?>
        <div class="empty-state">
            <i class="fas fa-users-slash"></i>
            <h3>No users found</h3>
            <p>Try adjusting your search or filter criteria</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Approval</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="role-badge <?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                        <td><span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                        <td>
                            <?php if($user['role'] == 'teacher' || $user['role'] == 'company'): ?>
                                <span class="approval-badge <?php echo $user['is_approved'] ? 'approved' : 'pending'; ?>">
                                    <?php echo $user['is_approved'] ? 'Approved' : 'Pending'; ?>
                                </span>
                            <?php else: ?>
                                <span class="approval-badge approved">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td class="actions-cell">
                            <?php if($user['is_active']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="action-icon deactivate" title="Deactivate"><i class="fas fa-ban"></i></button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="action-icon activate" title="Activate"><i class="fas fa-check-circle"></i></button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if(($user['role'] == 'teacher' || $user['role'] == 'company') && !$user['is_approved']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="approve_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="action-icon approve" title="Approve Account"><i class="fas fa-user-check"></i></button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if($user['role'] !== 'admin'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user permanently?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="action-icon delete" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&role=<?php echo $roleFilter; ?>&status=<?php echo $statusFilter; ?>&approval=<?php echo $approvalFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link">&laquo; Previous</a>
            <?php endif; ?>
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&role=<?php echo $roleFilter; ?>&status=<?php echo $statusFilter; ?>&approval=<?php echo $approvalFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&role=<?php echo $roleFilter; ?>&status=<?php echo $statusFilter; ?>&approval=<?php echo $approvalFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
/* All styles remain as before – already well-structured */
.users-container {
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
.page-header p {
    color: #666;
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
    font-size: 14px;
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
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}
.stat-mini {
    background: white;
    border-radius: 16px;
    padding: 15px 20px;
    text-align: center;
    min-width: 120px;
    flex: 1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #667eea;
    display: block;
}
.stat-label {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
    display: block;
}
.badge-pending {
    background: #ff9800;
    color: white;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 10px;
}
.table-wrapper {
    background: white;
    border-radius: 16px;
    overflow-x: auto;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.users-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}
.users-table th,
.users-table td {
    padding: 14px 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
.users-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
    font-size: 13px;
}
.users-table tr:hover {
    background: #fafafa;
}
.role-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    display: inline-block;
}
.role-badge.admin { background: #f44336; color: white; }
.role-badge.teacher { background: #2196F3; color: white; }
.role-badge.student { background: #4CAF50; color: white; }
.role-badge.company { background: #ff9800; color: white; }
.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
    display: inline-block;
}
.status-badge.active { background: #e8f5e9; color: #2e7d32; }
.status-badge.inactive { background: #ffebee; color: #c62828; }
.approval-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
    display: inline-block;
}
.approval-badge.approved { background: #e8f5e9; color: #2e7d32; }
.approval-badge.pending { background: #fff3e0; color: #ff9800; }
.actions-cell {
    white-space: nowrap;
}
.action-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px 8px;
    font-size: 16px;
    transition: all 0.3s;
}
.action-icon.activate { color: #4CAF50; }
.action-icon.deactivate { color: #ff9800; }
.action-icon.approve { color: #2196F3; }
.action-icon.delete { color: #f44336; }
.action-icon:hover {
    transform: scale(1.1);
}
.empty-state {
    text-align: center;
    padding: 60px;
    background: white;
    border-radius: 16px;
}
.empty-state i {
    font-size: 64px;
    color: #ccc;
    margin-bottom: 20px;
}
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 25px;
    flex-wrap: wrap;
}
.page-link {
    padding: 8px 14px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s;
}
.page-link:hover,
.page-link.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}
.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border-left: 4px solid #4CAF50;
}
@media (max-width: 900px) {
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    .stats-row {
        overflow-x: auto;
    }
    .users-table tr {
        display: table-row !important;
    }
    .users-table td {
        display: table-cell !important;
    }
}
</style>

<?php include_once '../includes/templates/footer.php'; ?>