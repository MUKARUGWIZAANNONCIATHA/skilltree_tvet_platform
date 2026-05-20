<?php
/**
 * Manage Sectors - With Tabs & Search
 * Path: /admin/sectors.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $sectorId = intval($_POST['sector_id'] ?? 0);
    
    if ($action === 'add') {
        $sectorName = sanitize($_POST['sector_name']);
        $description = sanitize($_POST['description']);
        $stmt = $pdo->prepare("INSERT INTO sectors (sector_name, description, status) VALUES (?, ?, 'active')");
        $stmt->execute([$sectorName, $description]);
        logActivity($_SESSION['user_id'], 'create', "Added sector: $sectorName");
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Sector added successfully!'];
        
    } elseif ($action === 'edit' && $sectorId) {
        $sectorName = sanitize($_POST['sector_name']);
        $description = sanitize($_POST['description']);
        $stmt = $pdo->prepare("UPDATE sectors SET sector_name = ?, description = ? WHERE sector_id = ?");
        $stmt->execute([$sectorName, $description, $sectorId]);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Sector updated successfully!'];
        
    } elseif ($action === 'delete' && $sectorId) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM trades WHERE sector_id = ?");
        $check->execute([$sectorId]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Cannot delete sector with existing trades. Delete trades first.'];
        } else {
            $stmt = $pdo->prepare("DELETE FROM sectors WHERE sector_id = ?");
            $stmt->execute([$sectorId]);
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Sector deleted successfully!'];
        }
        
    } elseif ($action === 'toggle' && $sectorId) {
        $stmt = $pdo->prepare("UPDATE sectors SET status = IF(status='active', 'inactive', 'active') WHERE sector_id = ?");
        $stmt->execute([$sectorId]);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Sector status updated!'];
    }
    
    header('Location: /admin/sectors.php');
    exit();
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}
if (!empty($searchTerm)) {
    $whereConditions[] = "(sector_name LIKE ? OR description LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM sectors $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Get sectors with pagination
$stmt = $pdo->prepare("SELECT * FROM sectors $whereClause ORDER BY sector_name LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$itemsPerPage, $offset]));
$sectors = $stmt->fetchAll();

// Get counts for tabs
$totalActive = $pdo->query("SELECT COUNT(*) FROM sectors WHERE status = 'active'")->fetchColumn();
$totalInactive = $pdo->query("SELECT COUNT(*) FROM sectors WHERE status = 'inactive'")->fetchColumn();
$totalAll = $totalActive + $totalInactive;

include_once '../includes/templates/header.php';

// Display message if exists
if (isset($_SESSION['message'])) {
    $msg = $_SESSION['message'];
    echo "<div class='alert alert-{$msg['type']}'>{$msg['text']}</div>";
    unset($_SESSION['message']);
}
?>

<div class="sectors-container">
    <div class="page-header">
        <h1><i class="fas fa-chart-pie"></i> Manage Sectors</h1>
        <button class="btn-add" onclick="openModal('add')">
            <i class="fas fa-plus"></i> New Sector
        </button>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
        <form method="GET" action="">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search sectors by name or description..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit" class="btn-search">Search</button>
                <?php if(!empty($searchTerm)): ?>
                    <a href="/admin/sectors.php" class="btn-clear">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <a href="?status=all&search=<?php echo urlencode($searchTerm); ?>" class="tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> All Sectors
            <span class="tab-count"><?php echo $totalAll; ?></span>
        </a>
        <a href="?status=active&search=<?php echo urlencode($searchTerm); ?>" class="tab <?php echo $statusFilter === 'active' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> Active
            <span class="tab-count active-count"><?php echo $totalActive; ?></span>
        </a>
        <a href="?status=inactive&search=<?php echo urlencode($searchTerm); ?>" class="tab <?php echo $statusFilter === 'inactive' ? 'active' : ''; ?>">
            <i class="fas fa-ban"></i> Inactive
            <span class="tab-count inactive-count"><?php echo $totalInactive; ?></span>
        </a>
    </div>

    <!-- Sectors Grid -->
    <?php if(empty($sectors)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <h3>No sectors found</h3>
            <p><?php echo !empty($searchTerm) ? 'Try a different search term or ' : ''; ?>Click "New Sector" to add your first sector.</p>
            <?php if(!empty($searchTerm)): ?>
                <a href="/admin/sectors.php" class="btn-clear-all">Clear Search</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="sectors-grid">
            <?php foreach($sectors as $sector): ?>
            <div class="sector-card <?php echo $sector['status'] === 'inactive' ? 'inactive-card' : ''; ?>">
                <div class="sector-header">
                    <div class="sector-icon">
                        <?php echo getSectorIcon($sector['sector_name']); ?>
                    </div>
                    <div class="sector-info">
                        <h3><?php echo htmlspecialchars($sector['sector_name']); ?></h3>
                        <p><?php echo htmlspecialchars($sector['description'] ?? 'No description provided'); ?></p>
                    </div>
                    <div class="sector-status">
                        <span class="status-badge <?php echo $sector['status']; ?>">
                            <?php echo $sector['status'] === 'active' ? '● Active' : '● Inactive'; ?>
                        </span>
                    </div>
                </div>
                <div class="sector-footer">
                    <div class="sector-stats">
                        <?php
                        $tradeCount = $pdo->prepare("SELECT COUNT(*) FROM trades WHERE sector_id = ?");
                        $tradeCount->execute([$sector['sector_id']]);
                        $tradesCount = $tradeCount->fetchColumn();
                        ?>
                        <span><i class="fas fa-briefcase"></i> <?php echo $tradesCount; ?> Trades</span>
                        <span><i class="fas fa-clock"></i> <?php echo date('M j, Y', strtotime($sector['created_at'])); ?></span>
                    </div>
                    <div class="sector-actions">
                        <button class="action-btn edit" onclick="editSector(<?php echo $sector['sector_id']; ?>, '<?php echo htmlspecialchars(addslashes($sector['sector_name'])); ?>', '<?php echo htmlspecialchars(addslashes($sector['description'] ?? '')); ?>')" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="sector_id" value="<?php echo $sector['sector_id']; ?>">
                            <button type="submit" class="action-btn toggle" title="<?php echo $sector['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                <i class="fas fa-<?php echo $sector['status'] === 'active' ? 'pause-circle' : 'play-circle'; ?>"></i>
                            </button>
                        </form>
                        <button class="action-btn delete" onclick="deleteSector(<?php echo $sector['sector_id']; ?>)" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link">&laquo; Previous</a>
            <?php endif; ?>
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal for Add/Edit Sector -->
<div id="sectorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Add New Sector</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" id="sectorForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="sector_id" id="sectorId" value="">
            
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Sector Name *</label>
                <input type="text" name="sector_name" id="sectorName" class="form-control" required 
                       placeholder="e.g., Information Technology, Agriculture, Health Sciences">
                <small class="form-hint">This name will appear in student registration and module filters</small>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Description</label>
                <textarea name="description" id="sectorDesc" class="form-control" rows="4" 
                          placeholder="Brief description of this sector..."></textarea>
                <small class="form-hint">Optional: Describe what this sector covers</small>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Sector
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.sectors-container{max-width:1400px;margin:0 auto;padding:30px 24px;}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;flex-wrap:wrap;gap:15px;}
.page-header h1{font-size:28px;color:#1a1a2e;margin:0;}
.btn-add{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;padding:12px 24px;border-radius:30px;cursor:pointer;font-weight:600;transition:all 0.3s;}
.btn-add:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(102,126,234,0.4);}
.search-bar{margin-bottom:25px;}
.search-wrapper{position:relative;display:flex;align-items:center;max-width:450px;}
.search-icon{position:absolute;left:15px;color:#999;}
.search-input{width:100%;padding:12px 15px 12px 45px;border:1px solid #e0e0e0;border-radius:30px;font-size:14px;}
.search-input:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1);}
.btn-search{background:#667eea;color:white;border:none;padding:10px 20px;border-radius:30px;margin-left:10px;cursor:pointer;}
.btn-clear{color:#999;margin-left:10px;text-decoration:none;}
.tabs{display:flex;gap:8px;margin-bottom:30px;border-bottom:1px solid #e0e0e0;}
.tab{padding:12px 24px;background:transparent;font-size:14px;font-weight:500;color:#666;text-decoration:none;border-radius:30px 30px 0 0;}
.tab:hover{color:#667eea;background:#f0f0f5;}
.tab.active{color:#667eea;background:white;border-bottom:3px solid #667eea;}
.tab-count{background:#e0e0e0;padding:2px 8px;border-radius:20px;font-size:12px;margin-left:8px;}
.tab-count.active-count{background:#4CAF50;color:white;}
.tab-count.inactive-count{background:#f44336;color:white;}
.sectors-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:24px;margin-top:10px;}
.sector-card{background:white;border-radius:20px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);transition:all 0.3s;border:1px solid #f0f0f0;}
.sector-card:hover{transform:translateY(-4px);box-shadow:0 10px 25px rgba(0,0,0,0.1);}
.sector-card.inactive-card{background:#fafafa;opacity:0.8;}
.sector-header{display:flex;gap:15px;margin-bottom:15px;}
.sector-icon{width:50px;height:50px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:15px;display:flex;align-items:center;justify-content:center;font-size:24px;color:white;}
.sector-info{flex:1;}
.sector-info h3{font-size:18px;margin:0 0 5px;color:#1a1a2e;}
.sector-info p{font-size:13px;color:#666;margin:0;}
.sector-status{display:flex;align-items:flex-start;}
.status-badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.status-badge.active{background:#e8f5e9;color:#2e7d32;}
.status-badge.inactive{background:#ffebee;color:#c62828;}
.sector-footer{border-top:1px solid #eee;padding-top:15px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;}
.sector-stats{display:flex;gap:15px;font-size:12px;color:#888;}
.sector-actions{display:flex;gap:8px;}
.action-btn{background:none;border:none;cursor:pointer;padding:6px 10px;border-radius:8px;font-size:14px;}
.action-btn.edit{color:#ff9800;}
.action-btn.edit:hover{background:#fff3e0;}
.action-btn.toggle{color:#2196F3;}
.action-btn.toggle:hover{background:#e3f2fd;}
.action-btn.delete{color:#f44336;}
.action-btn.delete:hover{background:#ffebee;}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:1000;}
.modal-content{background:white;border-radius:24px;width:550px;max-width:90%;animation:modalFadeIn 0.3s;}
@keyframes modalFadeIn{from{opacity:0;transform:translateY(-30px);}to{opacity:1;transform:translateY(0);}}
.modal-header{display:flex;justify-content:space-between;align-items:center;padding:20px 25px;border-bottom:1px solid #eee;}
.modal-header h2{margin:0;font-size:20px;color:#1a1a2e;}
.close{font-size:28px;cursor:pointer;color:#999;}
.close:hover{color:#f44336;}
#sectorForm{padding:25px;}
.form-group{margin-bottom:20px;}
.form-group label{display:block;margin-bottom:8px;font-weight:500;color:#333;font-size:14px;}
.form-control{width:100%;padding:12px 15px;border:1px solid #ddd;border-radius:12px;font-size:14px;}
.form-control:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1);}
.form-hint{display:block;margin-top:5px;font-size:11px;color:#999;}
.form-actions{display:flex;justify-content:flex-end;gap:12px;margin-top:25px;}
.btn-cancel{background:#f5f5f5;border:none;padding:10px 20px;border-radius:30px;cursor:pointer;font-weight:500;}
.btn-save{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;padding:10px 24px;border-radius:30px;cursor:pointer;font-weight:600;}
.btn-save:hover{transform:scale(1.02);}
.empty-state{text-align:center;padding:60px 20px;background:white;border-radius:24px;}
.empty-state i{font-size:64px;color:#ccc;margin-bottom:20px;}
.pagination{display:flex;justify-content:center;gap:8px;margin-top:30px;flex-wrap:wrap;}
.page-link{padding:8px 14px;background:white;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#333;}
.page-link:hover,.page-link.active{background:#667eea;color:white;border-color:#667eea;}
.alert{padding:15px 20px;border-radius:12px;margin-bottom:20px;}
.alert-success{background:#e8f5e9;color:#2e7d32;border-left:4px solid #4CAF50;}
.alert-error{background:#ffebee;color:#c62828;border-left:4px solid #f44336;}
@media (max-width:900px){.sectors-grid{grid-template-columns:1fr;}.tabs{overflow-x:auto;}.page-header{flex-direction:column;align-items:stretch;}.btn-add{width:100%;text-align:center;}}
</style>

<script>
function openModal(type, id, name, desc) {
    var modal = document.getElementById('sectorModal');
    var modalTitle = document.getElementById('modalTitle');
    var formAction = document.getElementById('formAction');
    var sectorId = document.getElementById('sectorId');
    var sectorName = document.getElementById('sectorName');
    var sectorDesc = document.getElementById('sectorDesc');
    
    if (type === 'add') {
        modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> Add New Sector';
        formAction.value = 'add';
        sectorId.value = '';
        sectorName.value = '';
        sectorDesc.value = '';
    } else {
        modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Sector';
        formAction.value = 'edit';
        sectorId.value = id;
        sectorName.value = name;
        sectorDesc.value = desc;
    }
    modal.style.display = 'flex';
}

function editSector(id, name, desc) {
    openModal('edit', id, name, desc);
}

function closeModal() {
    document.getElementById('sectorModal').style.display = 'none';
}

function deleteSector(id) {
    if (confirm('⚠️ Delete this sector? This will also check for existing trades.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input name="action" value="delete"><input name="sector_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

window.onclick = function(event) {
    var modal = document.getElementById('sectorModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>