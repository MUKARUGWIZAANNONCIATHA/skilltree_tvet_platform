<?php
/**
 * Library Resource Upload – for teachers and admins
 * Path: /teacher/library-upload.php
 */
require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);
require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$message = '';
$error = '';

// Get all trades for selection
$trades = $pdo->query("SELECT trade_id, trade_name FROM trades ORDER BY trade_name")->fetchAll();

// Get modules (for reference, optional)
if ($role === 'admin') {
    $modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT m.module_id, m.module_code, m.module_name
        FROM modules m
        LEFT JOIN teacher_modules tm ON m.module_id = tm.module_id
        WHERE tm.teacher_id = ? OR m.created_by = ?
        GROUP BY m.module_id
        ORDER BY m.module_code
    ");
    $stmt->execute([$userId, $userId]);
    $modules = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $resourceType = $_POST['resource_type'];
    $tradeId = !empty($_POST['trade_id']) ? (int)$_POST['trade_id'] : null;
    $moduleId = !empty($_POST['module_id']) ? (int)$_POST['module_id'] : null;

    if (empty($title)) {
        $error = "Title is required.";
    } elseif (empty($_FILES['resource_file']['name'])) {
        $error = "Please select a file to upload.";
    } else {
        $file = $_FILES['resource_file'];
        $fileName = basename($file['name']);
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'docx', 'doc', 'txt', 'pptx', 'xlsx'];
        if (!in_array($fileExt, $allowed)) {
            $error = "Only PDF, DOCX, DOC, TXT, PPTX, XLSX files are allowed.";
        } elseif ($fileSize > 10485760) { // 10MB
            $error = "File size must be less than 10MB.";
        } else {
            // Create upload directory if not exists
            $uploadDir = __DIR__ . '/../uploads/library/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
            $destination = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmp, $destination)) {
                $relativePath = '../uploads/library/' . $newFileName;
                $stmt = $pdo->prepare("INSERT INTO library_resources (resource_type, title, description, file_path, trade_id, module_id, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$resourceType, $title, $description, $relativePath, $tradeId, $moduleId, $userId]);
                $message = "✅ Resource uploaded successfully!";
                // Clear form
                $_POST = [];
            } else {
                $error = "Failed to move uploaded file.";
            }
        }
    }
}

include_once '../includes/templates/header.php';
?>

<div class="upload-container">
    <div class="page-header">
        <h1><i class="fas fa-upload"></i> Library Resource Upload</h1>
        <p>Upload past papers, marking guides, question banks, and reference materials for students</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="upload-card">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label>Resource Type *</label>
                    <select name="resource_type" class="form-control" required>
                        <option value="past_paper">📄 Past Paper</option>
                        <option value="marking_guide">✅ Marking Guide</option>
                        <option value="review_bank">❓ Question Bank</option>
                        <option value="reference">📚 Reference Material</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Trade (Optional – leave empty for global)</label>
                    <select name="trade_id" class="form-control">
                        <option value="">-- All Trades (Global) --</option>
                        <?php foreach ($trades as $trade): ?>
                            <option value="<?= $trade['trade_id'] ?>"><?= htmlspecialchars($trade['trade_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Description (Optional)</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Select File * (Max 10MB, PDF/DOCX/DOC/TXT/PPTX/XLSX)</label>
                <input type="file" name="resource_file" class="form-control-file" accept=".pdf,.docx,.doc,.txt,.pptx,.xlsx" required>
            </div>
            <div class="form-group">
                <label>Module (Optional – link to specific module)</label>
                <select name="module_id" class="form-control">
                    <option value="">-- No specific module --</option>
                    <?php foreach ($modules as $mod): ?>
                        <option value="<?= $mod['module_id'] ?>"><?= htmlspecialchars($mod['module_code']) ?> - <?= htmlspecialchars($mod['module_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="upload" class="btn-submit"><i class="fas fa-cloud-upload-alt"></i> Upload Resource</button>
        </form>
    </div>
</div>

<style>
    .upload-container { max-width: 800px; margin: 0 auto; padding: 30px 20px; }
    .upload-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .form-row { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
    .form-group { flex: 1; min-width: 200px; margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-control, .form-control-file { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
    .btn-submit { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 12px 24px; border-radius: 30px; cursor: pointer; font-size: 16px; font-weight: 600; }
    .alert { padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; }
    .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #4CAF50; }
    .alert-error { background: #ffebee; color: #c62828; border-left: 4px solid #f44336; }
</style>

<?php include_once '../includes/templates/footer.php'; ?>