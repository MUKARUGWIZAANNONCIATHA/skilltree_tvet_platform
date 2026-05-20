<?php
/**
 * Manage All Internships (Admin)
 * Path: /admin/internships.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $internId = intval($_POST['internship_id'] ?? 0);

    if ($action === 'toggle_status') {
        $stmt = $pdo->prepare("UPDATE internships SET is_open = NOT is_open WHERE internship_id = ?");
        $stmt->execute([$internId]);
        $message = "Internship status updated.";
    }
    elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM internships WHERE internship_id = ?");
        $stmt->execute([$internId]);
        $message = "Internship deleted successfully.";
    }
    header("Location: internships.php?msg=" . urlencode($message));
    exit;
}

// Filters
$companyFilter = $_GET['company'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query (join with users table to get company name)
$sql = "SELECT i.*, u.full_name as company_name, u.company_name as company_legal_name
        FROM internships i
        JOIN users u ON i.company_id = u.user_id
        WHERE 1=1";
$params = [];

if (!empty($companyFilter)) {
    $sql .= " AND u.full_name LIKE ?";
    $params[] = "%$companyFilter%";
}
if ($statusFilter === 'open') {
    $sql .= " AND i.is_open = 1";
} elseif ($statusFilter === 'closed') {
    $sql .= " AND i.is_open = 0";
}
if (!empty($search)) {
    $sql .= " AND (i.title LIKE ? OR i.description LIKE ? OR i.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$limit, $offset]));
$internships = $stmt->fetchAll();

// Count total for pagination
$countSql = "SELECT COUNT(*) FROM internships i
             JOIN users u ON i.company_id = u.user_id
             WHERE 1=1";
if (!empty($companyFilter)) $countSql .= " AND u.full_name LIKE ?";
if ($statusFilter === 'open') $countSql .= " AND i.is_open = 1";
elseif ($statusFilter === 'closed') $countSql .= " AND i.is_open = 0";
if (!empty($search)) $countSql .= " AND (i.title LIKE ? OR i.description LIKE ? OR i.location LIKE ?)";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// Get list of companies for filter dropdown
$companies = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'company' ORDER BY full_name")->fetchAll();

include_once '../includes/templates/header.php';

if (isset($_GET['msg'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['msg']) . '</div>';
}
?>

<div class="internships-container">
    <div class="page-header">
        <h1><i class="fas fa-briefcase"></i> Manage Internships</h1>
        <p>View and manage all internship postings across the platform</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search by title, description, location..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="company" class="filter-select">
                <option value="">All Companies</option>
                <?php foreach ($companies as $comp): ?>
                    <option value="<?= htmlspecialchars($comp['full_name']) ?>" <?= $companyFilter === $comp['full_name'] ? 'selected' : '' ?>><?= htmlspecialchars($comp['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="filter-select">
                <option value="">All Status</option>
                <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
            </select>
            <button type="submit" class="btn-apply">Filter</button>
            <a href="internships.php" class="btn-clear">Clear</a>
        </form>
    </div>

    <!-- Internships Table -->
    <?php if (empty($internships)): ?>
        <div class="empty-state">
            <i class="fas fa-briefcase"></i>
            <h3>No internships found</h3>
            <p>Adjust filters or wait for companies to post opportunities.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="internships-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company</th>
                        <th>Title</th>
                        <th>Location</th>
                        <th>Duration</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($internships as $intern): ?>
                    <tr>
                        <td><?= $intern['internship_id'] ?></td>
                        <td><strong><?= htmlspecialchars($intern['company_name']) ?></strong><br><small><?= htmlspecialchars($intern['company_legal_name'] ?: '') ?></small></td>
                        <td><?= htmlspecialchars($intern['title']) ?></td>
                        <td><?= htmlspecialchars($intern['location'] ?: '—') ?></td>
                        <td><?= $intern['duration_months'] ?> months</td>
                        <td><?= $intern['application_deadline'] ? date('d M Y', strtotime($intern['application_deadline'])) : 'Rolling' ?></td>
                        <td>
                            <span class="status-badge <?= $intern['is_open'] ? 'open' : 'closed' ?>">
                                <?= $intern['is_open'] ? 'Open' : 'Closed' ?>
                            </span>
                        </td>
                        <td><?= date('d M Y', strtotime($intern['created_at'])) ?></td>
                        <td class="actions-cell">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="internship_id" value="<?= $intern['internship_id'] ?>">
                                <button type="submit" class="action-icon toggle" title="Toggle Open/Closed">
                                    <?= $intern['is_open'] ? '🔒' : '🔓' ?>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this internship permanently? This will also delete all applications.')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="internship_id" value="<?= $intern['internship_id'] ?>">
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
                    <a href="?page=<?= $page-1 ?>&company=<?= urlencode($companyFilter) ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>" class="page-link">Previous</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&company=<?= urlencode($companyFilter) ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page+1 ?>&company=<?= urlencode($companyFilter) ?>&status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>" class="page-link">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    .internships-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 30px 24px;
    }
    .page-header { margin-bottom: 25px; }
    .page-header h1 { font-size: 28px; color: #1a1a2e; margin-bottom: 5px; }
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
    .table-wrapper {
        background: white;
        border-radius: 16px;
        overflow-x: auto;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .internships-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }
    .internships-table th, .internships-table td {
        padding: 14px 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    .internships-table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
    }
    .status-badge.open { background: #e8f5e9; color: #2e7d32; }
    .status-badge.closed { background: #ffebee; color: #c62828; }
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
        .filter-form { flex-direction: column; align-items: stretch; }
    }
</style>

<?php include_once '../includes/templates/footer.php'; ?>