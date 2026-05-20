<?php
/**
 * Past Paper Manager - Upload and manage library resources
 * Path: /teacher/past-paper-manager.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$message = '';
$error = '';

// Get modules and trades for dropdowns
$modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();
$trades = $pdo->query("SELECT trade_id, trade_name FROM trades ORDER BY trade_name")->fetchAll();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_resource'])) {
    $resourceType = $_POST['resource_type'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $year = intval($_POST['year']);
    $tradeId = intval($_POST['trade_id']) ?: null;
    $moduleId = intval($_POST['module_id']) ?: null;

    if (empty($title)) {
        $error = "Title is required.";
    } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select a file to upload.";
    } else {
        $file = $_FILES['file'];
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!in_array($file['type'], $allowedTypes)) {
            $error = "Only PDF, DOC, DOCX, XLS, XLSX files are allowed.";
        } elseif ($file['size'] > 10485760) { // 10MB
            $error = "File size must be less than 10MB.";
        } else {
            $uploadDir = '../uploads/library/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $filePath = $uploadDir . $filename;
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $stmt = $pdo->prepare("INSERT INTO library_resources (resource_type, title, description, file_path, year, trade_id, module_id, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$resourceType, $title, $description, 'uploads/library/' . $filename, $year, $tradeId, $moduleId, $userId]);
                $message = "Resource uploaded successfully.";
            } else {
                $error = "Failed to upload file.";
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("SELECT file_path FROM library_resources WHERE library_id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    if ($res && file_exists($res['file_path'])) {
        unlink($res['file_path']);
    }
    $stmt = $pdo->prepare("DELETE FROM library_resources WHERE library_id = ?");
    $stmt->execute([$id]);
    $message = "Resource deleted.";
    header("Location: past-paper-manager.php?msg=" . urlencode($message));
    exit;
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_resource'])) {
    $id = intval($_POST['resource_id']);
    $resourceType = $_POST['resource_type'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $year = intval($_POST['year']);
    $tradeId = intval($_POST['trade_id']) ?: null;
    $moduleId = intval($_POST['module_id']) ?: null;

    $stmt = $pdo->prepare("UPDATE library_resources SET resource_type=?, title=?, description=?, year=?, trade_id=?, module_id=? WHERE library_id=?");
    $stmt->execute([$resourceType, $title, $description, $year, $tradeId, $moduleId, $id]);
    $message = "Resource updated.";
    header("Location: past-paper-manager.php?msg=" . urlencode($message));
    exit;
}

// Fetch resources for listing
$resources = [];
$sql = "SELECT r.*, m.module_code, t.trade_name FROM library_resources r
        LEFT JOIN modules m ON r.module_id = m.module_id
        LEFT JOIN trades t ON r.trade_id = t.trade_id
        ORDER BY r.created_at DESC";
$resources = $pdo->query($sql)->fetchAll();

include_once '../includes/templates/header.php';
?>

<div class="past-paper-manager">
    <div class="page-header">
        <h1><i class="fas fa-file-pdf"></i> Past Paper Manager</h1>
        <p>Upload, manage, and organize past papers, marking guides, and reference materials</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="card upload-card">
        <h3><i class="fas fa-upload"></i> Upload New Resource</h3>
        <form method="post" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label>Resource Type *</label>
                    <select name="resource_type" class="form-control" required>
                        <option value="past_paper">Past Paper</option>
                        <option value="marking_guide">Marking Guide</option>
                        <option value="reference">Reference Material</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" name="year" class="form-control" placeholder="e.g., 2024">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Trade (optional)</label>
                    <select name="trade_id" class="form-control">
                        <option value="">-- All Trades --</option>
                        <?php foreach ($trades as $trade): ?>
                            <option value="<?= $trade['trade_id'] ?>"><?= htmlspecialchars($trade['trade_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Module (optional)</label>
                    <select name="module_id" class="form-control">
                        <option value="">-- All Modules --</option>
                        <?php foreach ($modules as $mod): ?>
                            <option value="<?= $mod['module_id'] ?>"><?= htmlspecialchars($mod['module_code']) ?> - <?= htmlspecialchars($mod['module_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>File *</label>
                    <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
            </div>
            <button type="submit" name="upload_resource" class="btn-primary">Upload Resource</button>
        </form>
    </div>

    <!-- Resources List -->
    <div class="card">
        <h3><i class="fas fa-list"></i> Uploaded Resources</h3>
        <?php if (empty($resources)): ?>
            <p>No resources uploaded yet.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="resources-table">
                    <thead>
                        <tr><th>ID</th><th>Type</th><th>Title</th><th>Trade</th><th>Module</th><th>Year</th><th>Uploaded</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resources as $res): ?>
                            <tr>
                                <td><?= $res['library_id'] ?></td>
                                <td><span class="badge-type"><?= str_replace('_', ' ', ucfirst($res['resource_type'])) ?></span></td>
                                <td><strong><?= htmlspecialchars($res['title']) ?></strong><br><small><?= htmlspecialchars(substr($res['description'], 0, 50)) ?></small></td>
                                <td><?= htmlspecialchars($res['trade_name'] ?: '—') ?></td>
                                <td><?= htmlspecialchars($res['module_code'] ?: '—') ?></td>
                                <td><?= $res['year'] ?: '—' ?></td>
                                <td><?= date('M d, Y', strtotime($res['created_at'])) ?></td>
                                <td class="actions">
                                    <button class="btn-edit" onclick="editResource(<?= $res['library_id'] ?>, '<?= htmlspecialchars($res['resource_type']) ?>', '<?= htmlspecialchars($res['title']) ?>', '<?= htmlspecialchars($res['description']) ?>', <?= $res['year'] ?: 0 ?>, <?= $res['trade_id'] ?: 0 ?>, <?= $res['module_id'] ?: 0 ?>)">✏️ Edit</button>
                                    <a href="<?= $res['file_path'] ?>" class="btn-download" target="_blank">📄 View</a>
                                    <a href="past-paper-manager.php?delete=<?= $res['library_id'] ?>" class="btn-delete" onclick="return confirm('Delete this resource?')">🗑️ Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Resource</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="post">
                <input type="hidden" name="update_resource" value="1">
                <input type="hidden" name="resource_id" id="edit_id">
                <div class="form-group">
                    <label>Resource Type</label>
                    <select name="resource_type" id="edit_type" class="form-control">
                        <option value="past_paper">Past Paper</option>
                        <option value="marking_guide">Marking Guide</option>
                        <option value="reference">Reference Material</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_desc" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" name="year" id="edit_year" class="form-control">
                </div>
                <div class="form-group">
                    <label>Trade</label>
                    <select name="trade_id" id="edit_trade" class="form-control">
                        <option value="">-- All Trades --</option>
                        <?php foreach ($trades as $trade): ?>
                            <option value="<?= $trade['trade_id'] ?>"><?= htmlspecialchars($trade['trade_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Module</label>
                    <select name="module_id" id="edit_module" class="form-control">
                        <option value="">-- All Modules --</option>
                        <?php foreach ($modules as $mod): ?>
                            <option value="<?= $mod['module_id'] ?>"><?= htmlspecialchars($mod['module_code']) ?> - <?= htmlspecialchars($mod['module_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Update Resource</button>
            </form>
        </div>
    </div>
</div>

<style>
    .past-paper-manager { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .card { background: white; border-radius: 1rem; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .form-row { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .form-group { flex: 1; min-width: 150px; }
    .btn-primary { background: #2c7da0; color: white; border: none; padding: 0.5rem 1rem; border-radius: 2rem; cursor: pointer; }
    .badge-type { background: #eef2fa; padding: 2px 8px; border-radius: 12px; font-size: 11px; }
    .table-wrapper { overflow-x: auto; }
    .resources-table { width: 100%; border-collapse: collapse; }
    .resources-table th, .resources-table td { padding: 10px; text-align: left; border-bottom: 1px solid #eef2f8; }
    .actions { white-space: nowrap; }
    .btn-edit, .btn-download, .btn-delete { padding: 2px 8px; border-radius: 15px; text-decoration: none; font-size: 12px; margin: 0 2px; display: inline-block; }
    .btn-edit { background: #ff9800; color: white; border: none; cursor: pointer; }
    .btn-download { background: #2196F3; color: white; }
    .btn-delete { background: #f44336; color: white; cursor: pointer; }
    .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
    .modal-content { background: white; border-radius: 12px; width: 500px; max-width: 90%; }
    .modal-header { padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .close { cursor: pointer; font-size: 24px; }
    .alert { padding: 10px; border-radius: 8px; margin-bottom: 15px; }
    .alert-success { background: #e8f5e9; color: #2e7d32; }
    .alert-error { background: #ffebee; color: #c62828; }
</style>

<script>
function editResource(id, type, title, desc, year, tradeId, moduleId) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_desc').value = desc;
    document.getElementById('edit_year').value = year;
    document.getElementById('edit_trade').value = tradeId;
    document.getElementById('edit_module').value = moduleId;
    document.getElementById('editModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}
window.onclick = function(e) {
    if (e.target.classList.contains('modal')) closeModal();
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>