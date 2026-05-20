<?php
/**
 * Anti-Cheat Logs Viewer
 * Path: /admin/anti-cheat-logs.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$message = '';
$error = '';

// Handle clear logs
if (isset($_POST['clear_logs']) && $_POST['clear_logs'] === 'yes') {
    $stmt = $pdo->prepare("DELETE FROM anti_cheat_logs");
    $stmt->execute();
    $message = 'All anti-cheat logs have been cleared.';
}

// Filters
$studentFilter = trim($_GET['student'] ?? '');
$eventFilter = $_GET['event'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 30;
$offset = ($page - 1) * $limit;

// Build query
$where = [];
$params = [];

if (!empty($studentFilter)) {
    $where[] = "(u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$studentFilter%";
    $params[] = "%$studentFilter%";
}
if (!empty($eventFilter)) {
    $where[] = "l.event_type = ?";
    $params[] = $eventFilter;
}
if (!empty($dateFrom)) {
    $where[] = "DATE(l.created_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $where[] = "DATE(l.created_at) <= ?";
    $params[] = $dateTo;
}
$whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

// Count total
$countSql = "SELECT COUNT(*) as total FROM anti_cheat_logs l JOIN users u ON l.student_id = u.user_id $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// Fetch logs
$sql = "SELECT l.*, u.full_name, u.email
        FROM anti_cheat_logs l
        JOIN users u ON l.student_id = u.user_id
        $whereClause
        ORDER BY l.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$limit, $offset]));
$logs = $stmt->fetchAll();

// Get unique event types for filter dropdown
$eventTypes = $pdo->query("SELECT DISTINCT event_type FROM anti_cheat_logs")->fetchAll(PDO::FETCH_COLUMN);

include_once '../includes/templates/header.php';
?>

<div class="logs-container">
    <div class="page-header">
        <h1><i class="fas fa-shield-alt"></i> Anti-Cheat Logs</h1>
        <p>Monitor student violations during quizzes and assessments</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <input type="text" name="student" class="form-control" placeholder="Student name or email" value="<?= htmlspecialchars($studentFilter) ?>">
            </div>
            <div class="filter-group">
                <select name="event" class="form-control">
                    <option value="">All event types</option>
                    <?php foreach ($eventTypes as $ev): ?>
                        <option value="<?= htmlspecialchars($ev) ?>" <?= $eventFilter === $ev ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $ev)) ?></option>
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
            <a href="anti-cheat-logs.php" class="btn-secondary">Reset</a>
        </form>
    </div>

    <!-- Logs Table -->
    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <i class="fas fa-shield-alt"></i>
            <h3>No logs found</h3>
            <p>Adjust filters or clear search criteria.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Event Type</th>
                        <th>Context</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $log['log_id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($log['full_name']) ?></strong><br>
                            <small><?= htmlspecialchars($log['email']) ?></small>
                        </td>
                        <td>
                            <span class="event-badge <?= $log['event_type'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $log['event_type'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($log['context']): ?>
                                <?= htmlspecialchars($log['context']) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
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
                    <a href="?page=<?= $page-1 ?>&student=<?= urlencode($studentFilter) ?>&event=<?= urlencode($eventFilter) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="page-link">Previous</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&student=<?= urlencode($studentFilter) ?>&event=<?= urlencode($eventFilter) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page+1 ?>&student=<?= urlencode($studentFilter) ?>&event=<?= urlencode($eventFilter) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="page-link">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Clear logs button -->
        <div class="clear-logs" style="margin-top: 20px; text-align: right;">
            <form method="post" onsubmit="return confirm('Are you sure you want to delete all anti-cheat logs? This action cannot be undone.');">
                <input type="hidden" name="clear_logs" value="yes">
                <button type="submit" class="btn-danger"><i class="fas fa-trash-alt"></i> Clear all logs</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
    .logs-container {
        max-width: 1200px;
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
    .logs-table th,
    .logs-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eef2f8;
    }
    .logs-table th {
        background: #f8fafc;
        font-weight: 600;
    }
    .event-badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .event-badge.tab_switch { background: #fff3e0; color: #c76f1c; }
    .event-badge.copy_attempt { background: #ffebee; color: #c62828; }
    .event-badge.window_blur { background: #e8f0fe; color: #2c7da0; }
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
</style>

<?php include_once '../includes/templates/footer.php'; ?>