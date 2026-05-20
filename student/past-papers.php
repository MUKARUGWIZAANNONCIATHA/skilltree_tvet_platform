 
<?php
/**
 * Past Papers & Marking Guides Library
 * Path: /student/past-papers.php
 */

require_once '../config/database.php';
require_once 'includes/auth.php';

// Get student's trade (from their enrollment modules)
$stmt = $pdo->prepare("
    SELECT m.trade 
    FROM student_enrollments e
    JOIN modules m ON e.module_id = m.module_id
    WHERE e.student_id = ? AND e.status != 'dropped'
    LIMIT 1
");
$stmt->execute([$studentId]);
$studentTrade = $stmt->fetchColumn();

// Filters
$type = $_GET['type'] ?? 'all'; // 'all', 'past_paper', 'marking_guide'
$search = trim($_GET['search'] ?? '');
$year = intval($_GET['year'] ?? 0);
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query
$sql = "SELECT * FROM library_resources 
        WHERE resource_type IN ('past_paper', 'marking_guide')
        AND (trade_id IS NULL OR trade_id IN (SELECT trade_id FROM trades WHERE trade_name = ?))";
$params = [$studentTrade];

if ($type !== 'all') {
    $sql .= " AND resource_type = ?";
    $params[] = $type;
}
if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($year > 0) {
    $sql .= " AND year = ?";
    $params[] = $year;
}
$sql .= " ORDER BY year DESC, title ASC LIMIT ? OFFSET ?";
$countSql = str_replace("ORDER BY year DESC, title ASC LIMIT ? OFFSET ?", "", $sql);
$countSql = preg_replace('/LIMIT\s+\?\s+OFFSET\s+\?/', '', $countSql);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute(array_slice($params, 0, -2));
$totalItems = $countStmt->rowCount();
$totalPages = ceil($totalItems / $limit);

$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($params, [$limit, $offset]));
$papers = $stmt->fetchAll();

// Get distinct years for filter
$yearStmt = $pdo->prepare("
    SELECT DISTINCT year FROM library_resources 
    WHERE resource_type IN ('past_paper', 'marking_guide')
    AND (trade_id IS NULL OR trade_id IN (SELECT trade_id FROM trades WHERE trade_name = ?))
    ORDER BY year DESC
");
$yearStmt->execute([$studentTrade]);
$years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<div class="past-papers-container">
    <div class="page-header">
        <h1><i class="fas fa-file-pdf"></i> Past Papers & Marking Guides</h1>
        <p>Download examination materials and official marking schemes</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" class="filter-form">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by title or description..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="type">
                <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>All Types</option>
                <option value="past_paper" <?= $type === 'past_paper' ? 'selected' : '' ?>>Past Papers</option>
                <option value="marking_guide" <?= $type === 'marking_guide' ? 'selected' : '' ?>>Marking Guides</option>
            </select>
            <select name="year">
                <option value="0">All Years</option>
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-filter">Filter</button>
            <a href="past-papers.php" class="btn-clear">Clear</a>
        </form>
    </div>

    <!-- Results Grid -->
    <?php if (empty($papers)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <p>No past papers or marking guides available for your trade.</p>
        </div>
    <?php else: ?>
        <div class="papers-grid">
            <?php foreach ($papers as $paper): ?>
                <div class="paper-card">
                    <div class="paper-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="paper-info">
                        <h3><?= htmlspecialchars($paper['title']) ?></h3>
                        <div class="paper-meta">
                            <span><i class="fas fa-calendar-alt"></i> <?= $paper['year'] ?: 'N/A' ?></span>
                            <span><i class="fas fa-tag"></i> <?= str_replace('_', ' ', ucfirst($paper['resource_type'])) ?></span>
                            <?php if ($paper['file_path'] && file_exists($paper['file_path'])): ?>
                                <span><i class="fas fa-download"></i> <?= round(filesize($paper['file_path']) / 1048576, 2) ?> MB</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($paper['description']): ?>
                            <p class="paper-desc"><?= htmlspecialchars(substr($paper['description'], 0, 80)) ?>...</p>
                        <?php endif; ?>
                    </div>
                    <div class="paper-actions">
                        <a href="past-paper-view.php?id=<?= $paper['library_id'] ?>" class="btn-view">View</a>
                        <a href="<?= $paper['file_path'] ?>" download class="btn-download">Download</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&type=<?= urlencode($type) ?>&year=<?= $year ?>&search=<?= urlencode($search) ?>" class="page-link">Previous</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&type=<?= urlencode($type) ?>&year=<?= $year ?>&search=<?= urlencode($search) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page+1 ?>&type=<?= urlencode($type) ?>&year=<?= $year ?>&search=<?= urlencode($search) ?>" class="page-link">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    .past-papers-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .page-header h1 {
        font-size: 2rem;
        color: #1a5f7a;
    }
    .filter-bar {
        background: white;
        border-radius: 1rem;
        padding: 1rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
        align-items: center;
    }
    .search-wrapper {
        flex: 2;
        min-width: 200px;
        position: relative;
    }
    .search-wrapper i {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }
    .search-wrapper input {
        width: 100%;
        padding: 0.5rem 0.5rem 0.5rem 2rem;
        border: 1px solid #cbd5e1;
        border-radius: 2rem;
    }
    .filter-form select {
        padding: 0.5rem;
        border: 1px solid #cbd5e1;
        border-radius: 2rem;
        background: white;
    }
    .btn-filter {
        background: #2c7da0;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        cursor: pointer;
    }
    .btn-clear {
        color: #f44336;
        text-decoration: none;
    }
    .papers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.2rem;
    }
    .paper-card {
        background: white;
        border-radius: 1rem;
        display: flex;
        padding: 1rem;
        gap: 1rem;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: 0.2s;
    }
    .paper-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }
    .paper-icon i {
        font-size: 2.5rem;
        color: #f44336;
    }
    .paper-info {
        flex: 1;
    }
    .paper-info h3 {
        font-size: 1rem;
        margin-bottom: 0.2rem;
    }
    .paper-meta {
        font-size: 0.7rem;
        color: #8aaec0;
        display: flex;
        gap: 0.8rem;
        flex-wrap: wrap;
        margin-bottom: 0.3rem;
    }
    .paper-desc {
        font-size: 0.8rem;
        color: #4a6a82;
    }
    .paper-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .btn-view, .btn-download {
        padding: 0.3rem 0.8rem;
        border-radius: 2rem;
        text-decoration: none;
        font-size: 0.75rem;
        text-align: center;
    }
    .btn-view {
        background: #eef2fa;
        color: #2c7da0;
    }
    .btn-download {
        background: #2c7da0;
        color: white;
    }
    .empty-state {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 1rem;
        color: #8aaec0;
    }
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
    }
    .page-link {
        padding: 0.3rem 0.7rem;
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
    @media (max-width: 700px) {
        .paper-card {
            flex-direction: column;
            text-align: center;
        }
        .paper-actions {
            flex-direction: row;
            justify-content: center;
        }
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>