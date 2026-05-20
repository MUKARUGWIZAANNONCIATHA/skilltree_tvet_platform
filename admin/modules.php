<?php
/**
 * Manage Modules - With Tabs, Search & Module Types
 * Path: /admin/modules.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $moduleId = intval($_POST['module_id'] ?? 0);
    
    if ($action === 'add') {
        $moduleCode = sanitize($_POST['module_code']);
        $moduleName = sanitize($_POST['module_name']);
        $credits = intval($_POST['credits']);
        $rqfLevel = intval($_POST['rqf_level']);
        $hours = intval($_POST['total_hours']);
        $sector = sanitize($_POST['sector']);
        $trade = sanitize($_POST['trade']);
        $moduleType = sanitize($_POST['module_type']);
        $status = sanitize($_POST['status']);
        
        $stmt = $pdo->prepare("INSERT INTO modules (module_code, module_name, credits, rqf_level, total_learning_hours, sector, trade, module_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$moduleCode, $moduleName, $credits, $rqfLevel, $hours, $sector, $trade, $moduleType, $status]);
        logActivity($_SESSION['user_id'], 'create', "Added module: $moduleCode");
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Module added successfully!'];
        
    } elseif ($action === 'edit' && $moduleId) {
        $moduleName = sanitize($_POST['module_name']);
        $credits = intval($_POST['credits']);
        $rqfLevel = intval($_POST['rqf_level']);
        $hours = intval($_POST['total_hours']);
        $sector = sanitize($_POST['sector']);
        $trade = sanitize($_POST['trade']);
        $moduleType = sanitize($_POST['module_type']);
        $status = sanitize($_POST['status']);
        
        $stmt = $pdo->prepare("UPDATE modules SET module_name = ?, credits = ?, rqf_level = ?, total_learning_hours = ?, sector = ?, trade = ?, module_type = ?, status = ? WHERE module_id = ?");
        $stmt->execute([$moduleName, $credits, $rqfLevel, $hours, $sector, $trade, $moduleType, $status, $moduleId]);
        logActivity($_SESSION['user_id'], 'edit', "Edited module ID: $moduleId");
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Module updated successfully!'];
        
    } elseif ($action === 'delete' && $moduleId) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM student_enrollments WHERE module_id = ?");
        $check->execute([$moduleId]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Cannot delete module with active enrollments.'];
        } else {
            $stmt = $pdo->prepare("DELETE FROM modules WHERE module_id = ?");
            $stmt->execute([$moduleId]);
            logActivity($_SESSION['user_id'], 'delete', "Deleted module ID: $moduleId");
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Module deleted successfully!'];
        }
    }
    
    header('Location: /admin/modules.php');
    exit();
}

// Get filter parameters
$typeFilter = $_GET['type'] ?? 'all';
$sectorFilter = $_GET['sector'] ?? 'all';
$levelFilter = $_GET['level'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 15;
$offset = ($page - 1) * $itemsPerPage;

// Build query
$whereConditions = [];
$params = [];

if ($typeFilter !== 'all') {
    $whereConditions[] = "module_type = ?";
    $params[] = $typeFilter;
}
if ($sectorFilter !== 'all') {
    $whereConditions[] = "sector = ?";
    $params[] = $sectorFilter;
}
if ($levelFilter !== 'all') {
    $whereConditions[] = "rqf_level = ?";
    $params[] = $levelFilter;
}
if ($statusFilter !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}
if (!empty($searchTerm)) {
    $whereConditions[] = "(module_code LIKE ? OR module_name LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM modules $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

$stmt = $pdo->prepare("SELECT * FROM modules $whereClause ORDER BY module_code LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$itemsPerPage, $offset]));
$modules = $stmt->fetchAll();

// Get counts for stats
$totalModules = $pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn();
$totalSpecific = $pdo->query("SELECT COUNT(*) FROM modules WHERE module_type = 'specific'")->fetchColumn();
$totalGeneral = $pdo->query("SELECT COUNT(*) FROM modules WHERE module_type = 'general'")->fetchColumn();
$totalComplementary = $pdo->query("SELECT COUNT(*) FROM modules WHERE module_type = 'complementary'")->fetchColumn();
$totalPublished = $pdo->query("SELECT COUNT(*) FROM modules WHERE status = 'published'")->fetchColumn();

$sectors = $pdo->query("SELECT sector_name FROM sectors WHERE status = 'active' ORDER BY sector_name")->fetchAll();
$levels = [3, 4, 5, 6];

include_once '../includes/templates/header.php';

if (isset($_SESSION['message'])) {
    $msg = $_SESSION['message'];
    echo "<div class='alert alert-{$msg['type']}'><i class='fas fa-" . ($msg['type'] == 'success' ? 'check-circle' : 'exclamation-circle') . "'></i> {$msg['text']}</div>";
    unset($_SESSION['message']);
}
?>

<div class="modules-container">
    <div class="page-header">
        <h1><i class="fas fa-book"></i> Manage Modules</h1>
        <button class="btn-add" onclick="openModuleModal()">
            <i class="fas fa-plus"></i> Add Module
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card"><div class="stat-icon">📚</div><div class="stat-info"><h3><?php echo $totalModules; ?></h3><p>Total Modules</p></div></div>
        <div class="stat-card"><div class="stat-icon">🎯</div><div class="stat-info"><h3><?php echo $totalSpecific; ?></h3><p>Specific</p></div></div>
        <div class="stat-card"><div class="stat-icon">🌐</div><div class="stat-info"><h3><?php echo $totalGeneral; ?></h3><p>General</p></div></div>
        <div class="stat-card"><div class="stat-icon">➕</div><div class="stat-info"><h3><?php echo $totalComplementary; ?></h3><p>Complementary</p></div></div>
        <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-info"><h3><?php echo $totalPublished; ?></h3><p>Published</p></div></div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="" class="filter-form">
            <div class="search-wrapper"><i class="fas fa-search search-icon"></i><input type="text" name="search" class="search-input" placeholder="Search by code or name..." value="<?php echo htmlspecialchars($searchTerm); ?>"></div>
            <select name="type" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>All Types</option>
                <option value="specific" <?php echo $typeFilter === 'specific' ? 'selected' : ''; ?>>Specific</option>
                <option value="general" <?php echo $typeFilter === 'general' ? 'selected' : ''; ?>>General</option>
                <option value="complementary" <?php echo $typeFilter === 'complementary' ? 'selected' : ''; ?>>Complementary</option>
            </select>
            <select name="sector" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $sectorFilter === 'all' ? 'selected' : ''; ?>>All Sectors</option>
                <?php foreach($sectors as $sector): ?>
                    <option value="<?php echo htmlspecialchars($sector['sector_name']); ?>" <?php echo $sectorFilter === $sector['sector_name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sector['sector_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="level" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $levelFilter === 'all' ? 'selected' : ''; ?>>All Levels</option>
                <?php foreach($levels as $level): ?>
                    <option value="<?php echo $level; ?>" <?php echo $levelFilter == $level ? 'selected' : ''; ?>>Level <?php echo $level; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Published</option>
                <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Archived</option>
            </select>
            <?php if(!empty($searchTerm) || $typeFilter !== 'all' || $sectorFilter !== 'all' || $levelFilter !== 'all' || $statusFilter !== 'all'): ?>
                <a href="/admin/modules.php" class="btn-clear">Clear Filters</a>
            <?php endif; ?>
            <button type="submit" class="btn-apply">Apply</button>
        </form>
    </div>

    <!-- Type Tabs -->
    <div class="type-tabs">
        <a href="?type=all&sector=<?php echo $sectorFilter; ?>&level=<?php echo $levelFilter; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="type-tab <?php echo $typeFilter === 'all' ? 'active' : ''; ?>"><i class="fas fa-list"></i> All <span class="tab-count"><?php echo $totalModules; ?></span></a>
        <a href="?type=specific&sector=<?php echo $sectorFilter; ?>&level=<?php echo $levelFilter; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="type-tab <?php echo $typeFilter === 'specific' ? 'active' : ''; ?>"><i class="fas fa-target"></i> Specific <span class="tab-count"><?php echo $totalSpecific; ?></span></a>
        <a href="?type=general&sector=<?php echo $sectorFilter; ?>&level=<?php echo $levelFilter; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="type-tab <?php echo $typeFilter === 'general' ? 'active' : ''; ?>"><i class="fas fa-globe"></i> General <span class="tab-count"><?php echo $totalGeneral; ?></span></a>
        <a href="?type=complementary&sector=<?php echo $sectorFilter; ?>&level=<?php echo $levelFilter; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="type-tab <?php echo $typeFilter === 'complementary' ? 'active' : ''; ?>"><i class="fas fa-plus-circle"></i> Complementary <span class="tab-count"><?php echo $totalComplementary; ?></span></a>
    </div>

    <!-- Modules Table -->
    <?php if(empty($modules)): ?>
        <div class="empty-state"><i class="fas fa-book-open"></i><h3>No modules found</h3><p><?php echo !empty($searchTerm) || $typeFilter !== 'all' || $sectorFilter !== 'all' ? 'Try adjusting your filters' : 'Click "Add Module" to create your first module'; ?></p></div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="modules-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Level</th>
                        <th>Credits</th>
                        <th>Hours</th>
                        <th>Sector/Trade</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($modules as $module): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($module['module_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($module['module_name']); ?></td>
                        <td><span class="type-badge <?php echo $module['module_type'] ?? 'specific'; ?>"><?php echo ucfirst($module['module_type'] ?? 'Specific'); ?></span></td>
                        <td><span class="level-badge">Level <?php echo $module['rqf_level']; ?></span></td>
                        <td><?php echo $module['credits']; ?></td>
                        <td><?php echo $module['total_learning_hours']; ?>h</td>
                        <td><small><?php echo htmlspecialchars($module['sector']); ?></small><br><small class="trade-name"><?php echo htmlspecialchars($module['trade']); ?></small></td>
                        <td><span class="status-badge <?php echo $module['status']; ?>"><?php echo ucfirst($module['status']); ?></span></td>
                        <td class="actions-cell">
                            <button class="action-icon edit" onclick="editModule(<?php echo $module['module_id']; ?>)" title="Edit Module Details">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-icon upload" onclick="uploadCurriculum(<?php echo $module['module_id']; ?>)" title="Upload/Paste Curriculum Structure">
                                <i class="fas fa-upload"></i>
                            </button>
                            <button class="action-icon curriculum" onclick="editCurriculum(<?php echo $module['module_id']; ?>)" title="Edit Curriculum Structure">
                                <i class="fas fa-layer-group"></i>
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this module?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="module_id" value="<?php echo $module['module_id']; ?>">
                                <button type="submit" class="action-icon delete" title="Delete Module">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&type=<?php echo $typeFilter; ?>&sector=<?php echo urlencode($sectorFilter); ?>&level=<?php echo $levelFilter; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link">&laquo; Previous</a>
            <?php endif; ?>
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&type=<?php echo $typeFilter; ?>&sector=<?php echo urlencode($sectorFilter); ?>&level=<?php echo $levelFilter; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&type=<?php echo $typeFilter; ?>&sector=<?php echo urlencode($sectorFilter); ?>&level=<?php echo $levelFilter; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="moduleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Add New Module</h2><span class="close" onclick="closeModal()">&times;</span></div>
        <form method="POST" id="moduleForm">
            <input type="hidden" name="action" id="formAction" value="add"><input type="hidden" name="module_id" id="moduleId" value="">
            <div class="form-row"><div class="form-group"><label><i class="fas fa-barcode"></i> Module Code *</label><input type="text" name="module_code" id="moduleCode" class="form-control" required placeholder="e.g., SWDDD401"><small class="form-hint">Unique identifier for the module</small></div>
            <div class="form-group"><label><i class="fas fa-tag"></i> Module Name *</label><input type="text" name="module_name" id="moduleName" class="form-control" required placeholder="e.g., DATABASE DEVELOPMENT"></div></div>
            <div class="form-row"><div class="form-group"><label><i class="fas fa-layer-group"></i> Module Type *</label><select name="module_type" id="moduleType" class="form-control" required><option value="specific">Specific Module (Core for Trade)</option><option value="general">General Module (Cross-Cutting)</option><option value="complementary">Complementary Module (Elective)</option></select><small class="form-hint">Specific = Core for trade | General = All trades | Complementary = Optional</small></div>
            <div class="form-group"><label><i class="fas fa-sort-numeric-up"></i> RQF Level *</label><select name="rqf_level" id="rqfLevel" class="form-control" required><option value="">Select Level</option><option value="3">Level 3 - Certificate III</option><option value="4">Level 4 - Certificate IV</option><option value="5">Level 5 - Diploma</option><option value="6">Level 6 - Advanced Diploma</option></select></div></div>
            <div class="form-row"><div class="form-group"><label><i class="fas fa-star"></i> Credits</label><input type="number" name="credits" id="credits" class="form-control" value="10" step="1"></div>
            <div class="form-group"><label><i class="fas fa-clock"></i> Total Learning Hours</label><input type="number" name="total_hours" id="totalHours" class="form-control" value="120" step="10"></div></div>
            <div class="form-row"><div class="form-group"><label><i class="fas fa-building"></i> Sector</label><select name="sector" id="sector" class="form-control"><option value="">Select Sector</option><?php foreach($sectors as $sector): ?><option value="<?php echo htmlspecialchars($sector['sector_name']); ?>"><?php echo htmlspecialchars($sector['sector_name']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label><i class="fas fa-briefcase"></i> Trade</label><input type="text" name="trade" id="trade" class="form-control" placeholder="e.g., Software Development"></div></div>
            <div class="form-group"><label><i class="fas fa-flag-checkered"></i> Status</label><select name="status" id="status" class="form-control"><option value="draft">Draft (Not visible to students)</option><option value="published">Published (Visible to students)</option><option value="archived">Archived (Hidden)</option></select></div>
            <div class="form-actions"><button type="button" class="btn-cancel" onclick="closeModal()"><i class="fas fa-times"></i> Cancel</button><button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Module</button></div>
        </form>
    </div>
</div>

<style>
.modules-container { max-width: 1400px; margin: 0 auto; padding: 30px 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
.page-header h1 { font-size: 28px; color: #1a1a2e; margin: 0; }
.btn-add { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 12px 24px; border-radius: 30px; cursor: pointer; font-weight: 600; transition: 0.3s; }
.btn-add:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 25px; }
.stat-card { background: white; border-radius: 20px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.stat-icon { font-size: 32px; }
.stat-info h3 { font-size: 28px; margin: 0; color: #667eea; }
.stat-info p { margin: 5px 0 0; color: #666; font-size: 13px; }
.filter-bar { background: white; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.filter-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
.search-wrapper { position: relative; flex: 2; min-width: 200px; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; }
.search-input { width: 100%; padding: 10px 12px 10px 35px; border: 1px solid #ddd; border-radius: 30px; font-size: 14px; }
.filter-select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 30px; background: white; cursor: pointer; font-size: 14px; }
.btn-apply { background: #667eea; color: white; border: none; padding: 10px 25px; border-radius: 30px; cursor: pointer; }
.btn-clear { color: #f44336; text-decoration: none; font-size: 14px; }
.type-tabs { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e0e0e0; }
.type-tab { padding: 8px 20px; background: #f5f5f5; border-radius: 30px; text-decoration: none; font-size: 14px; font-weight: 500; color: #666; transition: 0.3s; }
.type-tab:hover { background: #e0e0e0; }
.type-tab.active { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.tab-count { background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 20px; font-size: 11px; margin-left: 8px; }
.type-tab.active .tab-count { background: rgba(255,255,255,0.2); }
.table-wrapper { background: white; border-radius: 16px; overflow-x: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.modules-table { width: 100%; border-collapse: collapse; }
.modules-table th, .modules-table td { padding: 14px 12px; text-align: left; border-bottom: 1px solid #eee; }
.modules-table th { background: #f8f9fa; font-weight: 600; }
.modules-table tr:hover { background: #fafafa; }
.type-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.type-badge.specific { background: #2196F3; color: white; }
.type-badge.general { background: #4CAF50; color: white; }
.type-badge.complementary { background: #ff9800; color: white; }
.level-badge { background: #e8f0fe; color: #1e3a5f; padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 500; }
.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.status-badge.draft { background: #ff9800; color: white; }
.status-badge.published { background: #4CAF50; color: white; }
.status-badge.archived { background: #9e9e9e; color: white; }
.trade-name { color: #999; font-size: 11px; }
.actions-cell { white-space: nowrap; }
.action-icon { background: none; border: none; cursor: pointer; padding: 6px 10px; font-size: 16px; transition: 0.3s; border-radius: 8px; }
.action-icon.edit { color: #ff9800; }
.action-icon.edit:hover { background: #fff3e0; }
.action-icon.upload { color: #4CAF50; }
.action-icon.upload:hover { background: #e8f5e9; }
.action-icon.curriculum { color: #9c27b0; }
.action-icon.curriculum:hover { background: #f3e5f5; }
.action-icon.delete { color: #f44336; }
.action-icon.delete:hover { background: #ffebee; }
.empty-state { text-align: center; padding: 60px; background: white; border-radius: 20px; }
.empty-state i { font-size: 64px; color: #ccc; margin-bottom: 20px; }
.pagination { display: flex; justify-content: center; gap: 8px; margin-top: 25px; flex-wrap: wrap; }
.page-link { padding: 8px 14px; background: white; border: 1px solid #ddd; border-radius: 8px; text-decoration: none; color: #333; transition: 0.3s; }
.page-link:hover, .page-link.active { background: #667eea; color: white; border-color: #667eea; }
.alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #4CAF50; }
.alert-error { background: #ffebee; color: #c62828; border-left: 4px solid #f44336; }
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: white; border-radius: 24px; width: 650px; max-width: 90%; animation: modalFadeIn 0.3s; }
@keyframes modalFadeIn { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 25px; border-bottom: 1px solid #eee; }
.modal-header h2 { margin: 0; font-size: 20px; color: #1a1a2e; }
.close { font-size: 28px; cursor: pointer; color: #999; transition: 0.3s; }
.close:hover { color: #f44336; }
#moduleForm { padding: 25px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; font-size: 14px; }
.form-group label i { margin-right: 8px; color: #667eea; }
.form-control { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 12px; font-size: 14px; transition: 0.3s; }
.form-control:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-hint { display: block; margin-top: 5px; font-size: 11px; color: #999; }
.form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 25px; }
.btn-cancel { background: #f5f5f5; border: none; padding: 10px 20px; border-radius: 30px; cursor: pointer; font-weight: 500; }
.btn-save { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 10px 24px; border-radius: 30px; cursor: pointer; font-weight: 600; }
.btn-save:hover { transform: scale(1.02); }
@media (max-width: 900px) { .filter-form { flex-direction: column; align-items: stretch; } .search-wrapper { width: 100%; } .form-row { grid-template-columns: 1fr; gap: 0; } .type-tabs { overflow-x: auto; flex-wrap: nowrap; } }
</style>

<script>
function openModuleModal(editData = null) {
    const modal = document.getElementById('moduleModal');
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const moduleId = document.getElementById('moduleId');
    const moduleCode = document.getElementById('moduleCode');
    const moduleName = document.getElementById('moduleName');
    const moduleType = document.getElementById('moduleType');
    const rqfLevel = document.getElementById('rqfLevel');
    const credits = document.getElementById('credits');
    const totalHours = document.getElementById('totalHours');
    const sector = document.getElementById('sector');
    const trade = document.getElementById('trade');
    const status = document.getElementById('status');
    
    if (editData) {
        modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Module';
        formAction.value = 'edit';
        moduleId.value = editData.id;
        moduleCode.value = editData.code;
        moduleCode.readOnly = true;
        moduleName.value = editData.name;
        moduleType.value = editData.type;
        rqfLevel.value = editData.level;
        credits.value = editData.credits;
        totalHours.value = editData.hours;
        sector.value = editData.sector;
        trade.value = editData.trade;
        status.value = editData.status;
    } else {
        modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> Add New Module';
        formAction.value = 'add';
        moduleId.value = '';
        moduleCode.value = '';
        moduleCode.readOnly = false;
        moduleName.value = '';
        moduleType.value = 'specific';
        rqfLevel.value = '';
        credits.value = '10';
        totalHours.value = '120';
        sector.value = '';
        trade.value = '';
        status.value = 'draft';
    }
    modal.style.display = 'flex';
}

function editModule(id) {
    const row = event.target.closest('tr');
    const cells = row.cells;
    const moduleData = {
        id: id,
        code: cells[0].innerText,
        name: cells[1].innerText,
        type: cells[2].innerText.toLowerCase(),
        level: cells[3].innerText.replace('Level ', ''),
        credits: cells[4].innerText,
        hours: cells[5].innerText.replace('h', ''),
        sector: cells[6].innerText.split('\n')[0].trim(),
        trade: cells[6].innerText.split('\n')[1]?.replace('small', '').trim() || '',
        status: cells[7].innerText.toLowerCase()
    };
    openModuleModal(moduleData);
}

function uploadCurriculum(moduleId) {
    window.open('/teacher/upload-curriculum.php?module_id=' + moduleId, '_blank');
}

function editCurriculum(moduleId) {
    window.open('/teacher/curriculum-editor.php?module_id=' + moduleId, '_blank');
}

function closeModal() {
    document.getElementById('moduleModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('moduleModal');
    if (event.target === modal) modal.style.display = 'none';
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>