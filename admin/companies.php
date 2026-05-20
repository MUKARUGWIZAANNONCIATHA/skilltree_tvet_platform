<?php
/**
 * Manage Companies (Employers) – Improved Layout
 * Path: /admin/companies.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$message = '';
$error = '';

// Handle actions (unchanged)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET is_approved = 1 WHERE user_id = ? AND role = 'company'");
        $stmt->execute([$userId]);
        logActivity($_SESSION['user_id'], 'approve_company', "Approved company ID: $userId");
        $message = "Company approved successfully!";
    }
    elseif ($action === 'activate') {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = ? AND role = 'company'");
        $stmt->execute([$userId]);
        logActivity($_SESSION['user_id'], 'activate_company', "Activated company ID: $userId");
        $message = "Company activated successfully!";
    }
    elseif ($action === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = ? AND role = 'company'");
        $stmt->execute([$userId]);
        logActivity($_SESSION['user_id'], 'deactivate_company', "Deactivated company ID: $userId");
        $message = "Company deactivated successfully!";
    }
    elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'company'");
        $stmt->execute([$userId]);
        logActivity($_SESSION['user_id'], 'delete_company', "Deleted company ID: $userId");
        $message = "Company deleted successfully!";
    }
    header('Location: companies.php?msg=' . urlencode($message));
    exit;
}

// Filters (unchanged)
$statusFilter = $_GET['status'] ?? 'all';
$approvalFilter = $_GET['approval'] ?? 'all';
$searchTerm = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where = ["role = 'company'"];
$params = [];

if ($statusFilter === 'active') {
    $where[] = "is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $where[] = "is_active = 0";
}
if ($approvalFilter === 'approved') {
    $where[] = "is_approved = 1";
} elseif ($approvalFilter === 'pending') {
    $where[] = "is_approved = 0";
}
if (!empty($searchTerm)) {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR company_name LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}
$whereClause = "WHERE " . implode(" AND ", $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $limit);

$sql = "SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$limit, $offset]));
$companies = $stmt->fetchAll();

$totalCompanies = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'company'")->fetchColumn();
$pendingCompanies = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'company' AND is_approved = 0")->fetchColumn();
$activeCompanies = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'company' AND is_active = 1")->fetchColumn();

include_once '../includes/templates/header.php';

if (isset($_GET['msg'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['msg']) . '</div>';
}
?>

<div class="companies-container">
    <div class="page-header">
        <h1><i class="fas fa-building"></i> Manage Companies</h1>
        <p>View, approve, activate, and manage employer accounts</p>
    </div>

    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search by name, email, or company name..." value="<?= htmlspecialchars($searchTerm) ?>">
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
            <a href="companies.php" class="btn-clear">Clear</a>
        </form>
    </div>

    <div class="stats-row">
        <div class="stat-mini">
            <span class="stat-value"><?= $totalCompanies ?></span>
            <span class="stat-label">Total Companies</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?= $activeCompanies ?></span>
            <span class="stat-label">Active</span>
        </div>
        <div class="stat-mini">
            <span class="stat-value"><?= $pendingCompanies ?></span>
            <span class="stat-label">Pending Approval</span>
        </div>
    </div>

    <?php if (empty($companies)): ?>
        <div class="empty-state">
            <i class="fas fa-building"></i>
            <h3>No companies found</h3>
            <p>Adjust filters or wait for companies to register.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="companies-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company Name</th>
                        <th>Contact Email</th>
                        <th>Industry</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Approval</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $comp): ?>
                    <tr>
                        <td><?= $comp['user_id'] ?></td>
                        <td><strong><?= htmlspecialchars($comp['full_name']) ?></strong><br>
                            <small><?= htmlspecialchars($comp['company_name']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($comp['email']) ?></td>
                        <td><?= htmlspecialchars($comp['industry'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($comp['location'] ?: '—') ?></td>
                        <td>
                            <span class="status-badge <?= $comp['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $comp['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <span class="approval-badge <?= $comp['is_approved'] ? 'approved' : 'pending' ?>">
                                <?= $comp['is_approved'] ? 'Approved' : 'Pending' ?>
                            </span>
                        </td>
                        <td><?= date('d M Y', strtotime($comp['created_at'])) ?></td>
                        <td class="actions-cell">
                            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                <?php if (!$comp['is_approved']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="user_id" value="<?= $comp['user_id'] ?>">
                                    <button type="submit" class="action-icon approve" title="Approve">✓</button>
                                </form>
                                <?php endif; ?>
                                <?php if ($comp['is_active']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="user_id" value="<?= $comp['user_id'] ?>">
                                    <button type="submit" class="action-icon deactivate" title="Deactivate">🔴</button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="user_id" value="<?= $comp['user_id'] ?>">
                                    <button type="submit" class="action-icon activate" title="Activate">🟢</button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this company permanently? This will also delete all their internships and applications.')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?= $comp['user_id'] ?>">
                                    <button type="submit" class="action-icon delete" title="Delete">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </tr>
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

<style>
    .companies-container {
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
    .companies-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }
    .companies-table th,
    .companies-table td {
        padding: 14px 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
        vertical-align: top;
    }
    .companies-table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    .status-badge,
    .approval-badge {
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
        width: 120px;
    }
    .action-icon {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 4px 6px;
        margin: 0 2px;
        opacity: 0.8;
        transition: 0.2s;
        border-radius: 20px;
    }
    .action-icon:hover {
        opacity: 1;
        background: rgba(0,0,0,0.05);
        transform: scale(1.1);
    }
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
    @media (max-width: 900px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
        .stats-row {
            flex-direction: column;
        }
        .companies-table {
            min-width: 800px;
        }
    }
</style>