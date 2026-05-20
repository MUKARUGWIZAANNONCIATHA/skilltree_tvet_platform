<?php
/**
 * Manage RQF Levels
 * Path: /admin/levels.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $levelId = intval($_POST['level_id'] ?? 0);
    
    if ($action === 'add') {
        $levelNumber = intval($_POST['level_number']);
        $levelName = sanitize($_POST['level_name']);
        $description = sanitize($_POST['description']);
        $stmt = $pdo->prepare("INSERT INTO levels (level_number, level_name, description) VALUES (?, ?, ?)");
        $stmt->execute([$levelNumber, $levelName, $description]);
        logActivity($_SESSION['user_id'], 'create', "Added level: $levelNumber - $levelName");
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Level added successfully!'];
        
    } elseif ($action === 'edit' && $levelId) {
        $levelNumber = intval($_POST['level_number']);
        $levelName = sanitize($_POST['level_name']);
        $description = sanitize($_POST['description']);
        $stmt = $pdo->prepare("UPDATE levels SET level_number = ?, level_name = ?, description = ? WHERE level_id = ?");
        $stmt->execute([$levelNumber, $levelName, $description, $levelId]);
        logActivity($_SESSION['user_id'], 'edit', "Edited level ID: $levelId");
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Level updated successfully!'];
        
    } elseif ($action === 'delete' && $levelId) {
        // Check if level is being used by modules or students
        $checkModules = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE rqf_level = (SELECT level_number FROM levels WHERE level_id = ?)");
        $checkModules->execute([$levelId]);
        $moduleCount = $checkModules->fetchColumn();
        
        $checkStudents = $pdo->prepare("SELECT COUNT(*) FROM users WHERE rqf_level = (SELECT level_number FROM levels WHERE level_id = ?) AND role = 'student'");
        $checkStudents->execute([$levelId]);
        $studentCount = $checkStudents->fetchColumn();
        
        if ($moduleCount > 0 || $studentCount > 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Cannot delete level. It is used by $moduleCount module(s) and $studentCount student(s)."];
        } else {
            $stmt = $pdo->prepare("DELETE FROM levels WHERE level_id = ?");
            $stmt->execute([$levelId]);
            logActivity($_SESSION['user_id'], 'delete', "Deleted level ID: $levelId");
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Level deleted successfully!'];
        }
    }
    
    header('Location: /admin/levels.php');
    exit();
}

// Get filter parameters
$searchTerm = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Build query
$whereConditions = [];
$params = [];

if (!empty($searchTerm)) {
    $whereConditions[] = "(level_number LIKE ? OR level_name LIKE ? OR description LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM levels $whereClause");
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// Get levels with pagination
$stmt = $pdo->prepare("SELECT * FROM levels $whereClause ORDER BY level_number LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$itemsPerPage, $offset]));
$levels = $stmt->fetchAll();

// Get counts for stats
$totalLevels = $pdo->query("SELECT COUNT(*) FROM levels")->fetchColumn();

include_once '../includes/templates/header.php';

// Display message if exists
if (isset($_SESSION['message'])) {
    $msg = $_SESSION['message'];
    echo "<div class='alert alert-{$msg['type']}'><i class='fas fa-" . ($msg['type'] == 'success' ? 'check-circle' : 'exclamation-circle') . "'></i> {$msg['text']}</div>";
    unset($_SESSION['message']);
}
?>

<div class="levels-container">
    <div class="page-header">
        <h1><i class="fas fa-layer-group"></i> Manage RQF Levels</h1>
        <button class="btn-add" onclick="openLevelModal()">
            <i class="fas fa-plus"></i> New Level
        </button>
    </div>

    <!-- Search Bar -->
    <div class="search-bar">
        <form method="GET" action="" class="search-form">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search levels by number, name, or description..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
                <?php if(!empty($searchTerm)): ?>
                    <a href="/admin/levels.php" class="btn-clear">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
                <h3><?php echo $totalLevels; ?></h3>
                <p>Total Levels</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🎓</div>
            <div class="stat-info">
                <h3>RQF</h3>
                <p>Rwanda Qualifications Framework</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-info">
                <h3>Levels 3-6</h3>
                <p>Certificate to Advanced Diploma</p>
            </div>
        </div>
    </div>

    <!-- Levels Grid -->
    <?php if(empty($levels)): ?>
        <div class="empty-state">
            <i class="fas fa-layer-group"></i>
            <h3>No levels found</h3>
            <p><?php echo !empty($searchTerm) ? 'Try a different search term' : 'Click "New Level" to add your first level'; ?></p>
        </div>
    <?php else: ?>
        <div class="levels-grid">
            <?php foreach($levels as $level): ?>
            <div class="level-card">
                <div class="level-header">
                    <div class="level-number">
                        <span class="level-badge">Level <?php echo $level['level_number']; ?></span>
                    </div>
                    <div class="level-actions">
                        <button class="action-btn edit" onclick="editLevel(<?php echo $level['level_id']; ?>, <?php echo $level['level_number']; ?>, '<?php echo htmlspecialchars(addslashes($level['level_name'])); ?>', '<?php echo htmlspecialchars(addslashes($level['description'] ?? '')); ?>')">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this level? This may affect modules and students using this level.')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="level_id" value="<?php echo $level['level_id']; ?>">
                            <button type="submit" class="action-btn delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="level-body">
                    <h3><?php echo htmlspecialchars($level['level_name']); ?></h3>
                    <p><?php echo htmlspecialchars($level['description'] ?? 'No description provided'); ?></p>
                </div>
                <div class="level-footer">
                    <span class="level-info">
                        <i class="fas fa-calendar-alt"></i> Created: <?php echo date('M j, Y', strtotime($level['created_at'])); ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link">&laquo; Previous</a>
            <?php endif; ?>
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($searchTerm); ?>" class="page-link">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="levelModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Add New Level</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" id="levelForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="level_id" id="levelId" value="">
            
            <div class="form-group">
                <label><i class="fas fa-sort-numeric-up"></i> Level Number *</label>
                <select name="level_number" id="levelNumber" class="form-control" required>
                    <option value="">Select Level</option>
                    <option value="3">Level 3 - Certificate III</option>
                    <option value="4">Level 4 - Certificate IV</option>
                    <option value="5">Level 5 - Diploma</option>
                    <option value="6">Level 6 - Advanced Diploma</option>
                </select>
                <small class="form-hint">RQF Level as per Rwanda TVET standards</small>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Level Name</label>
                <input type="text" name="level_name" id="levelName" class="form-control" placeholder="e.g., Certificate IV, Diploma">
                <small class="form-hint">Full qualification name</small>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Description</label>
                <textarea name="description" id="levelDesc" class="form-control" rows="4" placeholder="Describe what this level covers..."></textarea>
                <small class="form-hint">Optional: Learning outcomes and competencies for this level</small>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Level
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.levels-container {
    max-width: 1200px;
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

.search-bar {
    margin-bottom: 25px;
}

.search-form {
    margin-bottom: 15px;
}

.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    max-width: 450px;
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

.btn-clear {
    color: #f44336;
    text-decoration: none;
    margin-left: 10px;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 20px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.stat-icon {
    font-size: 32px;
}

.stat-info h3 {
    font-size: 28px;
    margin: 0;
    color: #667eea;
}

.stat-info p {
    margin: 5px 0 0;
    color: #666;
    font-size: 13px;
}

.levels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
}

.level-card {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;
    border: 1px solid #f0f0f0;
}

.level-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.level-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.level-number {
    display: flex;
    align-items: center;
    gap: 10px;
}

.level-badge {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 6px 14px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 14px;
}

.level-actions {
    display: flex;
    gap: 8px;
}

.action-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 8px;
    transition: all 0.3s;
    font-size: 14px;
}

.action-btn.edit {
    color: #ff9800;
}

.action-btn.edit:hover {
    background: #fff3e0;
}

.action-btn.delete {
    color: #f44336;
}

.action-btn.delete:hover {
    background: #ffebee;
}

.level-body {
    margin-bottom: 15px;
}

.level-body h3 {
    font-size: 20px;
    margin: 0 0 10px;
    color: #1a1a2e;
}

.level-body p {
    color: #666;
    font-size: 13px;
    line-height: 1.5;
    margin: 0;
}

.level-footer {
    border-top: 1px solid #eee;
    padding-top: 12px;
}

.level-info {
    font-size: 12px;
    color: #999;
}

.level-info i {
    margin-right: 5px;
}

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

#levelForm {
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

.form-hint {
    display: block;
    margin-top: 5px;
    font-size: 11px;
    color: #999;
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
    .levels-grid {
        grid-template-columns: 1fr;
    }
    .stats-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function openLevelModal() {
    const modal = document.getElementById('levelModal');
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const levelId = document.getElementById('levelId');
    const levelNumber = document.getElementById('levelNumber');
    const levelName = document.getElementById('levelName');
    const levelDesc = document.getElementById('levelDesc');
    
    modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> Add New Level';
    formAction.value = 'add';
    levelId.value = '';
    levelNumber.value = '';
    levelName.value = '';
    levelDesc.value = '';
    modal.style.display = 'flex';
}

function editLevel(id, number, name, desc) {
    const modal = document.getElementById('levelModal');
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const levelId = document.getElementById('levelId');
    const levelNumber = document.getElementById('levelNumber');
    const levelName = document.getElementById('levelName');
    const levelDesc = document.getElementById('levelDesc');
    
    modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Level';
    formAction.value = 'edit';
    levelId.value = id;
    levelNumber.value = number;
    levelName.value = name;
    levelDesc.value = desc;
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('levelModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('levelModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>