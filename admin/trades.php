<?php
/**
 * Manage Trades - With Tabs, Search & Quick Access
 * Path: /admin/trades.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tradeId = intval($_POST['trade_id'] ?? 0);
    
    if ($action === 'add') {
        $sectorId = intval($_POST['sector_id']);
        $tradeName = sanitize($_POST['trade_name']);
        $code = sanitize($_POST['code']);
        $description = sanitize($_POST['description']);
        $stmt = $pdo->prepare("INSERT INTO trades (sector_id, trade_name, code, description, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$sectorId, $tradeName, $code, $description]);
        logActivity($_SESSION['user_id'], 'create', "Added trade: $tradeName");
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Trade added successfully!'];
        
    } elseif ($action === 'edit' && $tradeId) {
        $sectorId = intval($_POST['sector_id']);
        $tradeName = sanitize($_POST['trade_name']);
        $code = sanitize($_POST['code']);
        $description = sanitize($_POST['description']);
        $stmt = $pdo->prepare("UPDATE trades SET sector_id = ?, trade_name = ?, code = ?, description = ? WHERE trade_id = ?");
        $stmt->execute([$sectorId, $tradeName, $code, $description, $tradeId]);
        logActivity($_SESSION['user_id'], 'edit', "Edited trade ID: $tradeId");
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Trade updated successfully!'];
        
    } elseif ($action === 'delete' && $tradeId) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE trade = (SELECT trade_name FROM trades WHERE trade_id = ?)");
        $check->execute([$tradeId]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Cannot delete trade with existing modules.'];
        } else {
            $stmt = $pdo->prepare("DELETE FROM trades WHERE trade_id = ?");
            $stmt->execute([$tradeId]);
            logActivity($_SESSION['user_id'], 'delete', "Deleted trade ID: $tradeId");
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Trade deleted successfully!'];
        }
        
    } elseif ($action === 'toggle' && $tradeId) {
        $stmt = $pdo->prepare("UPDATE trades SET status = IF(status='active', 'inactive', 'active') WHERE trade_id = ?");
        $stmt->execute([$tradeId]);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Trade status updated!'];
    }
    
    header('Location: /admin/trades.php');
    exit();
}

// Get filter parameters
$sectorFilter = isset($_GET['sector']) ? intval($_GET['sector']) : 0;
$statusFilter = $_GET['status'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 12;
$offset = ($page - 1) * $itemsPerPage;

// Build query
$whereConditions = [];
$params = [];

if ($sectorFilter > 0) {
    $whereConditions[] = "t.sector_id = ?";
    $params[] = $sectorFilter;
}
if ($statusFilter !== 'all') {
    $whereConditions[] = "t.status = ?";
    $params[] = $statusFilter;
}
if (!empty($searchTerm)) {
    $whereConditions[] = "(t.trade_name LIKE ? OR t.code LIKE ? OR t.description LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM trades t $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Get trades with pagination
$stmt = $pdo->prepare("
    SELECT t.*, s.sector_name 
    FROM trades t 
    JOIN sectors s ON t.sector_id = s.sector_id 
    $whereClause 
    ORDER BY s.sector_name, t.trade_name 
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$itemsPerPage, $offset]));
$trades = $stmt->fetchAll();

// Get sectors for filter
$sectors = $pdo->query("SELECT sector_id, sector_name FROM sectors WHERE status = 'active' ORDER BY sector_name")->fetchAll();

// Get counts for tabs
$totalAll = $pdo->query("SELECT COUNT(*) FROM trades")->fetchColumn();
$totalActive = $pdo->query("SELECT COUNT(*) FROM trades WHERE status = 'active'")->fetchColumn();
$totalInactive = $pdo->query("SELECT COUNT(*) FROM trades WHERE status = 'inactive'")->fetchColumn();

include_once '../includes/templates/header.php';

// Display message if exists
if (isset($_SESSION['message'])) {
    $msg = $_SESSION['message'];
    echo "<div class='alert alert-{$msg['type']}'><i class='fas fa-" . ($msg['type'] == 'success' ? 'check-circle' : 'exclamation-circle') . "'></i> {$msg['text']}</div>";
    unset($_SESSION['message']);
}
?>

<div class="trades-container">
    <div class="page-header">
        <h1><i class="fas fa-briefcase"></i> Manage Trades</h1>
        <button class="btn-add" onclick="openTradeModal()">
            <i class="fas fa-plus"></i> New Trade
        </button>
    </div>

    <!-- Quick Access - Sector Tabs -->
    <div class="sector-tabs">
        <a href="?sector=0&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="sector-tab <?php echo $sectorFilter == 0 ? 'active' : ''; ?>">
            <i class="fas fa-globe"></i> All Sectors
            <span class="tab-count"><?php echo $totalAll; ?></span>
        </a>
        <?php foreach($sectors as $sector): 
            $count = $pdo->prepare("SELECT COUNT(*) FROM trades WHERE sector_id = ?");
            $count->execute([$sector['sector_id']]);
            $tradeCount = $count->fetchColumn();
        ?>
            <a href="?sector=<?php echo $sector['sector_id']; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="sector-tab <?php echo $sectorFilter == $sector['sector_id'] ? 'active' : ''; ?>">
                <?php echo getSectorIcon($sector['sector_name']); ?> <?php echo htmlspecialchars($sector['sector_name']); ?>
                <span class="tab-count"><?php echo $tradeCount; ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Search and Status Bar -->
    <div class="search-status-bar">
        <form method="GET" action="" class="search-form">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search trades by name, code, or description..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <?php if($sectorFilter > 0): ?>
                    <input type="hidden" name="sector" value="<?php echo $sectorFilter; ?>">
                <?php endif; ?>
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
            </div>
        </form>
        
        <div class="status-tabs">
            <a href="?status=all&sector=<?php echo $sectorFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="status-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> All
                <span class="tab-count"><?php echo $totalAll; ?></span>
            </a>
            <a href="?status=active&sector=<?php echo $sectorFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="status-tab <?php echo $statusFilter === 'active' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i> Active
                <span class="tab-count active-count"><?php echo $totalActive; ?></span>
            </a>
            <a href="?status=inactive&sector=<?php echo $sectorFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="status-tab <?php echo $statusFilter === 'inactive' ? 'active' : ''; ?>">
                <i class="fas fa-ban"></i> Inactive
                <span class="tab-count inactive-count"><?php echo $totalInactive; ?></span>
            </a>
            <?php if(!empty($searchTerm) || $sectorFilter > 0): ?>
                <a href="/admin/trades.php" class="clear-filters">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Trades Grid -->
    <?php if(empty($trades)): ?>
        <div class="empty-state">
            <i class="fas fa-briefcase-slash"></i>
            <h3>No trades found</h3>
            <p><?php echo !empty($searchTerm) || $sectorFilter > 0 ? 'Try adjusting your filters' : 'Click "New Trade" to add your first trade'; ?></p>
        </div>
    <?php else: ?>
        <div class="trades-grid">
            <?php foreach($trades as $trade): ?>
            <div class="trade-card <?php echo $trade['status'] === 'inactive' ? 'inactive-card' : ''; ?>">
                <div class="trade-header">
                    <div class="trade-icon">
                        <?php echo getTradeIcon($trade['trade_name']); ?>
                    </div>
                    <div class="trade-info">
                        <h3><?php echo htmlspecialchars($trade['trade_name']); ?></h3>
                        <div class="trade-meta">
                            <span class="trade-code"><?php echo htmlspecialchars($trade['code'] ?? 'No code'); ?></span>
                            <span class="trade-sector"><i class="fas fa-building"></i> <?php echo htmlspecialchars($trade['sector_name']); ?></span>
                        </div>
                    </div>
                    <div class="trade-status">
                        <span class="status-badge <?php echo $trade['status']; ?>">
                            <?php echo $trade['status'] === 'active' ? '● Active' : '● Inactive'; ?>
                        </span>
                    </div>
                </div>
                <div class="trade-body">
                    <p><?php echo htmlspecialchars($trade['description'] ?? 'No description provided'); ?></p>
                </div>
                <div class="trade-footer">
                    <button class="action-btn edit" onclick="editTrade(<?php echo $trade['trade_id']; ?>, <?php echo $trade['sector_id']; ?>, '<?php echo htmlspecialchars(addslashes($trade['trade_name'])); ?>', '<?php echo htmlspecialchars(addslashes($trade['code'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($trade['description'] ?? '')); ?>')">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="trade_id" value="<?php echo $trade['trade_id']; ?>">
                        <button type="submit" class="action-btn toggle">
                            <i class="fas fa-<?php echo $trade['status'] === 'active' ? 'pause-circle' : 'play-circle'; ?>"></i>
                            <?php echo $trade['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                        </button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this trade permanently?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="trade_id" value="<?php echo $trade['trade_id']; ?>">
                        <button type="submit" class="action-btn delete">
                            <i class="fas fa-trash-alt"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&sector=<?php echo $sectorFilter; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link">&laquo; Previous</a>
            <?php endif; ?>
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&sector=<?php echo $sectorFilter; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&sector=<?php echo $sectorFilter; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="tradeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Add New Trade</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" id="tradeForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="trade_id" id="tradeId" value="">
            
            <div class="form-group">
                <label><i class="fas fa-building"></i> Sector *</label>
                <select name="sector_id" id="sectorId" class="form-control" required>
                    <option value="">Select Sector</option>
                    <?php foreach($sectors as $sector): ?>
                        <option value="<?php echo $sector['sector_id']; ?>"><?php echo htmlspecialchars($sector['sector_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Trade Name *</label>
                    <input type="text" name="trade_name" id="tradeName" class="form-control" required placeholder="e.g., Software Development">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-barcode"></i> Trade Code</label>
                    <input type="text" name="code" id="tradeCode" class="form-control" placeholder="e.g., SD001">
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Description</label>
                <textarea name="description" id="tradeDesc" class="form-control" rows="4" placeholder="Brief description of this trade..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Trade
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.trades-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px 24px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.page-header h1 {
    font-size: 28px;
    color: #1a1a2e;
    margin: 0;
}

.btn-add {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102,126,234,0.4);
}

/* Sector Tabs */
.sector-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
}

.sector-tab {
    padding: 8px 16px;
    background: #f5f5f5;
    border-radius: 30px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    color: #666;
    transition: all 0.3s;
}

.sector-tab:hover {
    background: #e0e0e0;
    color: #333;
}

.sector-tab.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.tab-count {
    background: rgba(0,0,0,0.1);
    padding: 2px 6px;
    border-radius: 20px;
    font-size: 11px;
    margin-left: 6px;
}

.sector-tab.active .tab-count {
    background: rgba(255,255,255,0.2);
}

/* Search and Status Bar */
.search-status-bar {
    background: white;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.search-form {
    margin-bottom: 15px;
}

.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 15px;
    color: #999;
}

.search-input {
    flex: 1;
    padding: 12px 15px 12px 40px;
    border: 1px solid #ddd;
    border-radius: 30px;
    font-size: 14px;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}

.btn-search {
    background: #667eea;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 30px;
    margin-left: 10px;
    cursor: pointer;
}

.status-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.status-tab {
    padding: 6px 14px;
    background: #f5f5f5;
    border-radius: 30px;
    text-decoration: none;
    font-size: 13px;
    color: #666;
    transition: all 0.3s;
}

.status-tab:hover {
    background: #e0e0e0;
}

.status-tab.active {
    background: #667eea;
    color: white;
}

.status-tab.active .tab-count {
    background: rgba(255,255,255,0.2);
}

.tab-count.active-count {
    background: #4CAF50;
    color: white;
}

.tab-count.inactive-count {
    background: #f44336;
    color: white;
}

.clear-filters {
    margin-left: auto;
    color: #f44336;
    text-decoration: none;
    font-size: 13px;
}

/* Trades Grid */
.trades-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 24px;
}

.trade-card {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;
    border: 1px solid #f0f0f0;
}

.trade-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.trade-card.inactive-card {
    background: #fafafa;
    opacity: 0.8;
}

.trade-header {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    align-items: flex-start;
}

.trade-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.trade-info {
    flex: 1;
}

.trade-info h3 {
    font-size: 18px;
    margin: 0 0 5px;
    color: #1a1a2e;
}

.trade-meta {
    display: flex;
    gap: 12px;
    font-size: 12px;
}

.trade-code {
    color: #999;
    font-family: monospace;
}

.trade-sector {
    color: #667eea;
}

.trade-status {
    display: flex;
    align-items: flex-start;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.active {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-badge.inactive {
    background: #ffebee;
    color: #c62828;
}

.trade-body {
    margin-bottom: 15px;
}

.trade-body p {
    color: #666;
    font-size: 13px;
    line-height: 1.5;
    margin: 0;
}

.trade-footer {
    border-top: 1px solid #eee;
    padding-top: 15px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.action-btn {
    padding: 6px 12px;
    border-radius: 20px;
    border: none;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.3s;
}

.action-btn.edit {
    background: #ff9800;
    color: white;
}

.action-btn.edit:hover {
    background: #e68900;
}

.action-btn.toggle {
    background: #2196F3;
    color: white;
}

.action-btn.toggle:hover {
    background: #0b7dda;
}

.action-btn.delete {
    background: #f44336;
    color: white;
}

.action-btn.delete:hover {
    background: #da190b;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px;
    background: white;
    border-radius: 20px;
}

.empty-state i {
    font-size: 64px;
    color: #ccc;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 20px;
    color: #666;
    margin-bottom: 10px;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 30px;
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
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border-left: 4px solid #4CAF50;
}

.alert-error {
    background: #ffebee;
    color: #c62828;
    border-left: 4px solid #f44336;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 24px;
    width: 550px;
    max-width: 90%;
    animation: modalFadeIn 0.3s;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #1a1a2e;
}

.close {
    font-size: 28px;
    cursor: pointer;
    color: #999;
    transition: color 0.3s;
}

.close:hover {
    color: #f44336;
}

#tradeForm {
    padding: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.form-group label i {
    margin-right: 8px;
    color: #667eea;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 25px;
}

.btn-cancel {
    background: #f5f5f5;
    border: none;
    padding: 10px 20px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 500;
}

.btn-save {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 600;
}

.btn-save:hover {
    transform: scale(1.02);
}

@media (max-width: 900px) {
    .trades-grid {
        grid-template-columns: 1fr;
    }
    .sector-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 10px;
    }
    .status-tabs {
        flex-wrap: wrap;
    }
    .clear-filters {
        margin-left: 0;
    }
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>

<script>
function openTradeModal() {
    const modal = document.getElementById('tradeModal');
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const tradeId = document.getElementById('tradeId');
    const sectorId = document.getElementById('sectorId');
    const tradeName = document.getElementById('tradeName');
    const tradeCode = document.getElementById('tradeCode');
    const tradeDesc = document.getElementById('tradeDesc');
    
    modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> Add New Trade';
    formAction.value = 'add';
    tradeId.value = '';
    sectorId.value = '';
    tradeName.value = '';
    tradeCode.value = '';
    tradeDesc.value = '';
    modal.style.display = 'flex';
}

function editTrade(id, sector, name, code, desc) {
    const modal = document.getElementById('tradeModal');
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const tradeId = document.getElementById('tradeId');
    const sectorId = document.getElementById('sectorId');
    const tradeName = document.getElementById('tradeName');
    const tradeCode = document.getElementById('tradeCode');
    const tradeDesc = document.getElementById('tradeDesc');
    
    modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Trade';
    formAction.value = 'edit';
    tradeId.value = id;
    sectorId.value = sector;
    tradeName.value = name;
    tradeCode.value = code;
    tradeDesc.value = desc;
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('tradeModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('tradeModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

function getTradeIcon(tradeName) {
    const icons = {
        'Software': '💻', 'Development': '⚙️', 'Programming': '⌨️',
        'Database': '🗄️', 'Data': '📊', 'Network': '🌐', 'Web': '🌐',
        'Security': '🔒', 'Cyber': '🛡️', 'Agriculture': '🌾',
        'Construction': '🏗️', 'Building': '🏠', 'Electrical': '⚡',
        'Plumbing': '🚰', 'Carpentry': '🪚', 'Masonry': '🧱',
        'Manufacturing': '🏭', 'Tourism': '✈️', 'Hospitality': '🍽️',
        'Health': '🏥', 'Medical': '💊', 'Nursing': '🩺',
        'Business': '📊', 'Finance': '💰', 'Accounting': '📈',
        'Marketing': '📢', 'HR': '👥', 'Automotive': '🚗',
        'Mechanics': '🔧', 'Fashion': '👗', 'Design': '🎨', 'Tailoring': '🧥'
    };
    for (let key in icons) {
        if (tradeName.toLowerCase().includes(key.toLowerCase())) {
            return icons[key];
        }
    }
    return '📂';
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>