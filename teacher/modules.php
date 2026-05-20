<?php
/**
 * Teacher Module Manager – list, create, and access modules
 * Path: /teacher/modules.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$teacherId = $_SESSION['user_id'];
$message = '';
$error = '';

// Helper to check if column exists
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Handle module creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_module') {
    $moduleCode = sanitize($_POST['module_code']);
    $moduleName = sanitize($_POST['module_name']);
    $credits = intval($_POST['credits']);
    $rqfLevel = intval($_POST['rqf_level']);
    $hours = intval($_POST['total_hours']);
    $sector = sanitize($_POST['sector']);
    $trade = sanitize($_POST['trade']);
    $moduleType = sanitize($_POST['module_type']);

    // Teacher-created modules start as 'draft'
    $status = 'draft';

    try {
        $stmt = $pdo->prepare("INSERT INTO modules (module_code, module_name, credits, rqf_level, total_learning_hours, sector, trade, module_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$moduleCode, $moduleName, $credits, $rqfLevel, $hours, $sector, $trade, $moduleType, $status]);
        $newModuleId = $pdo->lastInsertId();

        // Set created_by if column exists
        if (columnExists($pdo, 'modules', 'created_by')) {
            $updateCreator = $pdo->prepare("UPDATE modules SET created_by = ? WHERE module_id = ?");
            $updateCreator->execute([$teacherId, $newModuleId]);
        }

        // Auto-assign this teacher to the module
        $assignStmt = $pdo->prepare("INSERT IGNORE INTO teacher_modules (teacher_id, module_id) VALUES (?, ?)");
        $assignStmt->execute([$teacherId, $newModuleId]);

        $message = "Module created successfully. It is saved as DRAFT until admin approves.";
    } catch (PDOException $e) {
        $error = "Error creating module: " . $e->getMessage();
    }
}

// Fetch modules that this teacher is assigned to (via teacher_modules)
$sql = "SELECT m.* FROM modules m
        JOIN teacher_modules tm ON m.module_id = tm.module_id
        WHERE tm.teacher_id = ?
        ORDER BY m.module_code";
$stmt = $pdo->prepare($sql);
$stmt->execute([$teacherId]);
$modules = $stmt->fetchAll();

// If no modules found, show empty state
$sectors = $pdo->query("SELECT sector_name FROM sectors WHERE status = 'active' ORDER BY sector_name")->fetchAll();
$levels = [3, 4, 5, 6];

include_once '../includes/templates/header.php';
?>

<div class="teacher-modules">
    <div class="page-header">
        <h1><i class="fas fa-chalkboard-teacher"></i> My Modules</h1>
        <button class="btn-add" onclick="openAddModuleModal()">
            <i class="fas fa-plus"></i> Create Module
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($modules)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <h3>No modules assigned</h3>
            <p>Click "Create Module" to add a new module, or wait for admin to assign modules to you.</p>
        </div>
    <?php else: ?>
        <div class="modules-grid">
            <?php foreach ($modules as $module): ?>
                <div class="module-card">
                    <div class="module-header">
                        <span class="module-code"><?= htmlspecialchars($module['module_code']) ?></span>
                        <span class="status-badge <?= $module['status'] ?>"><?= ucfirst($module['status']) ?></span>
                    </div>
                    <div class="module-title"><?= htmlspecialchars($module['module_name']) ?></div>
                    <div class="module-meta">
                        <span><i class="fas fa-layer-group"></i> <?= ucfirst($module['module_type']) ?></span>
                        <span><i class="fas fa-chart-line"></i> Level <?= $module['rqf_level'] ?></span>
                        <span><i class="fas fa-clock"></i> <?= $module['total_learning_hours'] ?>h</span>
                    </div>
                    <div class="module-actions">
                        <a href="upload-curriculum.php?module_id=<?= $module['module_id'] ?>" class="btn-upload">
                            <i class="fas fa-upload"></i> Upload Curriculum
                        </a>
                        <a href="curriculum-editor.php?module_id=<?= $module['module_id'] ?>" class="btn-edit-curriculum">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal for creating a new module -->
<div id="moduleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Create New Module</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" id="moduleForm">
            <input type="hidden" name="action" value="add_module">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-barcode"></i> Module Code *</label>
                    <input type="text" name="module_code" class="form-control" required placeholder="e.g., SWDDD401">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Module Name *</label>
                    <input type="text" name="module_name" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-layer-group"></i> Module Type *</label>
                    <select name="module_type" class="form-control" required>
                        <option value="specific">Specific (Core for Trade)</option>
                        <option value="general">General (Cross-Cutting)</option>
                        <option value="complementary">Complementary (Elective)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-sort-numeric-up"></i> RQF Level *</label>
                    <select name="rqf_level" class="form-control" required>
                        <option value="">Select Level</option>
                        <?php foreach ($levels as $level): ?>
                            <option value="<?= $level ?>">Level <?= $level ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-star"></i> Credits</label>
                    <input type="number" name="credits" class="form-control" value="10">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Total Hours</label>
                    <input type="number" name="total_hours" class="form-control" value="120">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Sector</label>
                    <select name="sector" class="form-control">
                        <option value="">Select Sector</option>
                        <?php foreach ($sectors as $sector): ?>
                            <option value="<?= htmlspecialchars($sector['sector_name']) ?>"><?= htmlspecialchars($sector['sector_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-briefcase"></i> Trade</label>
                    <input type="text" name="trade" class="form-control" placeholder="e.g., Software Development">
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-save">Create Module</button>
            </div>
        </form>
    </div>
</div>

<style>
.teacher-modules { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 30px; }
.btn-add { background: linear-gradient(135deg, #28a745, #218838); color: white; border: none; padding: 10px 20px; border-radius: 30px; cursor: pointer; font-weight: bold; }
.modules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
.module-card { background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); overflow: hidden; transition: 0.3s; }
.module-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
.module-header { background: #f8f9fa; padding: 12px 16px; display: flex; justify-content: space-between; border-bottom: 1px solid #eee; }
.module-code { font-weight: bold; color: #2c7da0; }
.status-badge { font-size: 11px; padding: 4px 10px; border-radius: 30px; font-weight: 500; }
.status-badge.draft { background: #ffc107; color: #856404; }
.status-badge.published { background: #28a745; color: white; }
.status-badge.archived { background: #6c757d; color: white; }
.module-title { font-size: 18px; font-weight: 600; padding: 16px 16px 8px; }
.module-meta { padding: 8px 16px; display: flex; gap: 16px; font-size: 13px; color: #666; border-bottom: 1px solid #f0f0f0; }
.module-actions { padding: 16px; display: flex; gap: 12px; }
.btn-upload, .btn-edit-curriculum { flex: 1; text-align: center; padding: 8px; border-radius: 30px; text-decoration: none; font-size: 14px; transition: 0.3s; }
.btn-upload { background: #2c7da0; color: white; }
.btn-upload:hover { background: #1f5e7a; }
.btn-edit-curriculum { background: #f0f0f0; color: #333; }
.btn-edit-curriculum:hover { background: #e0e0e0; }
.empty-state { text-align: center; padding: 60px; background: white; border-radius: 20px; }
.empty-state i { font-size: 60px; color: #ccc; margin-bottom: 20px; }
.alert { padding: 12px 20px; border-radius: 12px; margin-bottom: 25px; }
.alert-success { background: #d4edda; color: #155724; }
.alert-error { background: #f8d7da; color: #721c24; }
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: white; border-radius: 24px; width: 600px; max-width: 90%; animation: fadeIn 0.3s; }
@keyframes fadeIn { from { opacity:0; transform: translateY(-20px); } to { opacity:1; transform: translateY(0); } }
.modal-header { display: flex; justify-content: space-between; padding: 20px 25px; border-bottom: 1px solid #eee; }
.close { font-size: 28px; cursor: pointer; }
#moduleForm { padding: 25px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 6px; font-weight: 500; }
.form-control { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 12px; }
.form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; }
.btn-cancel { background: #f0f0f0; border: none; padding: 8px 20px; border-radius: 30px; cursor: pointer; }
.btn-save { background: #28a745; color: white; border: none; padding: 8px 24px; border-radius: 30px; cursor: pointer; }
@media (max-width: 700px) { .form-row { grid-template-columns: 1fr; gap: 0; } }
</style>

<script>
function openAddModuleModal() {
    document.getElementById('moduleModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('moduleModal').style.display = 'none';
}
window.onclick = function(e) {
    let modal = document.getElementById('moduleModal');
    if (e.target === modal) modal.style.display = 'none';
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>