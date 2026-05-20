 
<?php
/**
 * System Logs Viewer
 * Path: /admin/system-logs.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$message = '';
$error = '';

// Handle clear logs (optional)
if (isset($_POST['clear_logs']) && $_POST['clear_logs'] === 'yes') {
    $stmt = $pdo->prepare("DELETE FROM user_activity_log");
    $stmt->execute();
    logActivity($_SESSION['user_id'], 'clear_logs', "Cleared all system logs");
    $message = 'All system logs have been cleared.';
    header('Location: system-logs.php?msg=' . urlencode($message));
    exit;
}

// Filters
$userFilter = $_GET['user'] ?? '';
$actionFilter = $_GET['action'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 30;
$offset = ($page - 1) * $limit;

// Build query
$sql = "SELECT l.*, u.full_name, u.email FROM user_activity_log l LEFT JOIN users u ON l.user_id = u.user_id WHERE 1=1";
$params = [];

if (!empty($userFilter)) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$userFilter%";
    $params[] = "%$userFilter%";
}
if (!empty($actionFilter)) {
    $sql .= " AND l.action_type = ?";
    $params[] = $actionFilter;
}
if (!empty($dateFrom)) {
    $sql .= " AND DATE(l.created_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $sql .= " AND DATE(l.created_at) <= ?";
    $params[] = $dateTo;
}
$sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$limit, $offset]));
$logs = $stmt->fetchAll();

// Count total for pagination
$countSql = "SELECT COUNT(*) FROM user_activity_log l LEFT JOIN users u ON l.user_id = u.user_id WHERE 1=1";
if (!empty($userFilter)) $countSql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
if (!empty($actionFilter)) $countSql .= " AND l.action_type = ?";
if (!empty($dateFrom)) $countSql .= " AND DATE(l.created_at) >= ?";
if (!empty($dateTo)) $countSql .= " AND DATE(l.created_at) <= ?";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// Get distinct action types for filter dropdown
$actions = $pdo->query("SELECT DISTINCT action_type FROM user_activity_log")->fetchAll(PDO::FETCH_COLUMN);

include_once '../includes/templates/header.php';

if (isset($_GET['msg'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['msg']) . '</div>';
}
?>

<div class="logs-container">
    <div class="page-header">
        <h1><i class="fas fa-history"></i> System Logs</h1>
        <p>View user activity logs, track actions, and monitor platform usage</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <input type="text" name="user" class="form-control" placeholder="User name or email" value="<?= htmlspecialchars($userFilter) ?>">
            </div>
            <div class="filter-group">
                <select name="action" class="form-control">
                    <option value="">All actions</option>
                    <?php foreach ($actions as $act): ?>
                        <option value="<?= htmlspecialchars($act) ?>" <?= $actionFilter === $act ? 'selected' : '' ?>><?= ucfirst($act) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <input type="date" name="date_from" class="form-control" placeholder="From date" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="filter-group">
                <input type="date" name="date_to" class="form-control" placeholder="To date" value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <button type="submit" class="btn-primary">Filter</button>
            <a href="system-logs.php" class="btn-secondary">Reset</a>
        </form>
    </div>

    <!-- Logs Table -->
    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <i class="fas fa-history"></i>
            <h3>No logs found</h3>
            <p>Adjust filters or wait for user activity.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action Type</th>
                        <th>Details</th>
                        <th>IP Address</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $log['log_id'] ?></td>
                        <td>
                            <?php if ($log['user_id']): ?>
                                <strong><?= htmlspecialchars($log['full_name'] ?: 'Unknown') ?></strong><br>
                                <small><?= htmlspecialchars($log['email'] ?: '') ?></small>
                            <?php else: ?>
                                <span class="text-muted">System / Deleted user</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="action-badge <?= $log['action_type'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $log['action_type'])) ?>
                            </span>
                        </td>
                        <td><?= nl2br(htmlspecialchars($log['action_details'] ?: '—')) ?></td>
                        <td><?= htmlspecialchars($log['ip_address'] ?: '—') ?></td>
                        <td><?= date('d M Y H:i:s', strtotime($log['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&user=<?= urlencode($userFilter) ?>&action=<?= urlencode($actionFilter) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="page-link">Previous</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&user=<?= urlencode($userFilter) ?>&action=<?= urlencode($actionFilter) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page+1 ?>&user=<?= urlencode($userFilter) ?>&action=<?= urlencode($actionFilter) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="page-link">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Clear logs button -->
        <div class="clear-logs" style="margin-top: 20px; text-align: right;">
            <form method="post" onsubmit="return confirm('Are you sure you want to delete all system logs? This action cannot be undone.');">
                <input type="hidden" name="clear_logs" value="yes">
                <button type="submit" class="btn-danger"><i class="fas fa-trash-alt"></i> Clear all logs</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
    .logs-container {
        max-width: 1300px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .filter-bar {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: flex-end;
    }
    .filter-group {
        flex: 1;
        min-width: 150px;
    }
    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
    }
    .btn-primary {
        background: #2c7da0;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        cursor: pointer;
    }
    .btn-secondary {
        background: #e2e8f0;
        color: #1e2f3e;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        text-decoration: none;
        display: inline-block;
    }
    .btn-danger {
        background: #e74c3c;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        cursor: pointer;
    }
    .table-wrapper {
        overflow-x: auto;
    }
    .logs-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .logs-table th, .logs-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eef2f8;
    }
    .logs-table th {
        background: #f8fafc;
        font-weight: 600;
    }
    .action-badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 2rem;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .action-badge.login { background: #e8f0fe; color: #2c7da0; }
    .action-badge.logout { background: #eef2fa; color: #6c8faa; }
    .action-badge.create { background: #e8f5e9; color: #2e7d32; }
    .action-badge.update { background: #fff3e0; color: #c76f1c; }
    .action-badge.delete { background: #ffebee; color: #c62828; }
    .action-badge.upload, .action-badge.download { background: #e3f2fd; color: #1565c0; }
    .action-badge.approve, .action-badge.verify, .action-badge.activate, .action-badge.deactivate { background: #f3e5f5; color: #6a1b9a; }
    .text-muted { color: #8aaec0; }
    .empty-state {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 1rem;
    }
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }
    .page-link {
        padding: 0.3rem 0.8rem;
        border: 1px solid #ddd;
        border-radius: 0.3rem;
        text-decoration: none;
        color: #2c7da0;
    }
    .page-link.active {
        background: #2c7da0;
        color: white;
        border-color: #2c7da0;
    }
    .alert {
        padding: 0.8rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
    }
    @media (max-width: 900px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<?php include_once '../includes/templates/footer.php'; ?>