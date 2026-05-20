<?php
/**
 * Student Resource Library – Filter by trade_id
 * Path: /student/library.php
 */
require_once '../config/database.php';
require_once 'includes/auth.php';

// -----------------------------------------------------------------
// 1. Get student's trade ID from enrolled modules
// -----------------------------------------------------------------
// First, get the trade name from the enrolled module
$stmt = $pdo->prepare("
    SELECT DISTINCT m.trade 
    FROM student_enrollments e
    JOIN modules m ON e.module_id = m.module_id
    WHERE e.student_id = ? AND e.status != 'dropped' AND m.trade IS NOT NULL
    LIMIT 1
");
$stmt->execute([$studentId]);
$tradeName = $stmt->fetchColumn();

$studentTradeId = null;
if ($tradeName) {
    // Look up the trade ID from the trades table
    $tradeStmt = $pdo->prepare("SELECT trade_id FROM trades WHERE trade_name = ?");
    $tradeStmt->execute([$tradeName]);
    $studentTradeId = $tradeStmt->fetchColumn();
}

$selectedType = $_GET['type'] ?? '';
$search = trim($_GET['search'] ?? '');

// -----------------------------------------------------------------
// 2. Build query using trade_id
// -----------------------------------------------------------------
$sql = "SELECT * FROM library_resources WHERE (trade_id = ? OR trade_id IS NULL)";
$params = [$studentTradeId];

if (!empty($selectedType)) {
    $sql .= " AND resource_type = ?";
    $params[] = $selectedType;
}
if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resources = $stmt->fetchAll();

// -----------------------------------------------------------------
// 3. Count per type (using trade_id)
// -----------------------------------------------------------------
$countSql = "
    SELECT resource_type, COUNT(*) as cnt 
    FROM library_resources 
    WHERE (trade_id = ? OR trade_id IS NULL)
    GROUP BY resource_type
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute([$studentTradeId]);
$typeCounts = [];
$totalResources = 0;
while ($row = $countStmt->fetch()) {
    $typeCounts[$row['resource_type']] = $row['cnt'];
    $totalResources += $row['cnt'];
}

include 'includes/header.php';
?>

<style>
    .library-header { background: linear-gradient(135deg, #1a5f7a, #0e3a4a); border-radius: 1.5rem; padding: 2rem; color: white; margin-bottom: 2rem; }
    .library-header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
    .filter-bar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; background: white; padding: 1rem 1.5rem; border-radius: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 2rem; }
    .filter-buttons { display: flex; gap: 0.8rem; flex-wrap: wrap; }
    .filter-btn { background: #f0f4f8; border: none; border-radius: 2rem; padding: 0.4rem 1.2rem; font-size: 0.85rem; cursor: pointer; text-decoration: none; color: #1e2f3e; }
    .filter-btn.active { background: #2c7da0; color: white; }
    .search-box { display: flex; gap: 0.5rem; }
    .search-box input { border: 1px solid #cbd5e1; border-radius: 2rem; padding: 0.4rem 1rem; width: 220px; }
    .resources-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
    .resource-card { background: white; border-radius: 1.2rem; overflow: hidden; transition: 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
    .resource-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
    .resource-icon { background: #eef2fa; padding: 1.2rem; text-align: center; font-size: 2.5rem; color: #2c7da0; }
    .resource-details { padding: 1.2rem; flex: 1; }
    .resource-title { font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem; }
    .resource-type { display: inline-block; background: #e9f0f5; padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.7rem; margin-bottom: 0.6rem; color: #2c6b8a; }
    .resource-desc { font-size: 0.85rem; color: #4a6a82; margin-bottom: 1rem; }
    .resource-meta { font-size: 0.7rem; color: #8aaec0; display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; }
    .btn-download { background: #2c7da0; color: white; border: none; border-radius: 2rem; padding: 0.4rem 1rem; font-size: 0.75rem; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-download:hover { background: #1e5f7a; }
    .empty-state { text-align: center; padding: 3rem; background: white; border-radius: 1.5rem; color: #8aaec0; }
    @media (max-width: 700px) { .filter-bar { flex-direction: column; align-items: stretch; } .search-box input { width: 100%; } }
</style>

<div class="library-header">
    <h1><i class="fas fa-book-open"></i> Resource Library</h1>
    <p>Access past papers, marking guides, question banks and reference materials for your trade: <strong><?= htmlspecialchars($tradeName ?: 'General') ?></strong></p>
</div>

<div class="filter-bar">
    <div class="filter-buttons">
        <a href="library.php" class="filter-btn <?= empty($selectedType) ? 'active' : '' ?>">All (<?= $totalResources ?>)</a>
        <a href="library.php?type=past_paper" class="filter-btn <?= $selectedType === 'past_paper' ? 'active' : '' ?>">Past Papers (<?= $typeCounts['past_paper'] ?? 0 ?>)</a>
        <a href="library.php?type=marking_guide" class="filter-btn <?= $selectedType === 'marking_guide' ? 'active' : '' ?>">Marking Guides (<?= $typeCounts['marking_guide'] ?? 0 ?>)</a>
        <a href="library.php?type=review_bank" class="filter-btn <?= $selectedType === 'review_bank' ? 'active' : '' ?>">Question Bank (<?= $typeCounts['review_bank'] ?? 0 ?>)</a>
        <a href="library.php?type=reference" class="filter-btn <?= $selectedType === 'reference' ? 'active' : '' ?>">References (<?= $typeCounts['reference'] ?? 0 ?>)</a>
    </div>
    <form class="search-box" method="get">
        <input type="text" name="search" placeholder="Search resources..." value="<?= htmlspecialchars($search) ?>">
        <?php if (!empty($selectedType)): ?><input type="hidden" name="type" value="<?= htmlspecialchars($selectedType) ?>"><?php endif; ?>
        <button type="submit" class="btn-download" style="background:#6c8faa;">Search</button>
        <?php if (!empty($search) || !empty($selectedType)): ?>
            <a href="library.php" class="btn-download" style="background:#999;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="resources-grid">
    <?php if (empty($resources)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <p>No resources available for your trade yet. Please check back later.</p>
        </div>
    <?php else: ?>
        <?php foreach ($resources as $res): ?>
        <?php
            $icon = 'fa-file-alt';
            if ($res['resource_type'] == 'past_paper') $icon = 'fa-file-pdf';
            elseif ($res['resource_type'] == 'marking_guide') $icon = 'fa-check-double';
            elseif ($res['resource_type'] == 'review_bank') $icon = 'fa-question-circle';
            elseif ($res['resource_type'] == 'reference') $icon = 'fa-book';
        ?>
        <div class="resource-card">
            <div class="resource-icon">
                <i class="fas <?= $icon ?>"></i>
            </div>
            <div class="resource-details">
                <div class="resource-type"><?= str_replace('_', ' ', ucfirst($res['resource_type'])) ?></div>
                <div class="resource-title"><?= htmlspecialchars($res['title']) ?></div>
                <?php if (!empty($res['description'])): ?>
                    <div class="resource-desc"><?= nl2br(htmlspecialchars(substr($res['description'], 0, 100))) ?>…</div>
                <?php endif; ?>
                <div class="resource-meta">
                    <span><i class="far fa-calendar-alt"></i> <?= date('M d, Y', strtotime($res['created_at'])) ?></span>
                    <a href="download-library.php?id=<?= $res['library_id'] ?>" class="btn-download"><i class="fas fa-download"></i> Download</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>