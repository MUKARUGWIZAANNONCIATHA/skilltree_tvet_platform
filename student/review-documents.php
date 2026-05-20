<?php
/**
 * Review Documents Manager - Upload and Manage Documents for Students
 * Path: /teacher/review-documents.php
 * FIXED: Removed u.username column error
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$message = '';
$error = '';

// Get modules
if ($role === 'admin') {
    $modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT module_id, module_code, module_name FROM modules WHERE created_by = ? ORDER BY module_code");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
}

$selectedModuleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;

// Get topics/learning outcomes
$topics = [];
if ($selectedModuleId > 0) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT lo.outcome_id, lo.outcome_number, lo.outcome_description
        FROM learning_outcomes lo
        WHERE lo.module_id = ?
        ORDER BY lo.outcome_number
    ");
    $stmt->execute([$selectedModuleId]);
    $topics = $stmt->fetchAll();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload_document') {
        $topicId = intval($_POST['topic_id'] ?? 0);
        $documentTitle = trim($_POST['document_title'] ?? '');
        $documentDescription = trim($_POST['document_description'] ?? '');
        $documentType = $_POST['document_type'] ?? 'review_notes';
        
        if (empty($documentTitle)) {
            $error = "Please enter a document title.";
        } elseif (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['document_file']['tmp_name'];
            $fileName = $_FILES['document_file']['name'];
            $fileSize = $_FILES['document_file']['size'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowed = ['pdf', 'docx', 'doc', 'txt', 'pptx', 'xlsx'];
            if (!in_array($fileExt, $allowed)) {
                $error = "Only PDF, DOCX, DOC, TXT, PPTX, XLSX files are allowed.";
            } elseif ($fileSize > 10485760) {
                $error = "File size must be less than 10MB.";
            } else {
                $fileContent = file_get_contents($fileTmp);
                $fileContent = addslashes($fileContent);
                
                $stmt = $pdo->prepare("INSERT INTO review_documents (module_id, topic_id, document_title, document_description, document_type, file_name, file_type, file_size, file_content, uploaded_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')");
                $stmt->execute([$selectedModuleId, $topicId ?: null, $documentTitle, $documentDescription, $documentType, $fileName, $fileExt, $fileSize, $fileContent, $userId]);
                
                $message = "✅ Document uploaded successfully!";
                header("Location: ?module_id=$selectedModuleId&uploaded=1");
                exit();
            }
        } else {
            $error = "Please select a file to upload.";
        }
    }
    
    if ($action === 'delete_document') {
        $documentId = intval($_POST['document_id']);
        $stmt = $pdo->prepare("DELETE FROM review_documents WHERE document_id = ?");
        $stmt->execute([$documentId]);
        $message = "✅ Document deleted!";
        header("Location: ?module_id=$selectedModuleId&deleted=1");
        exit();
    }
    
    if ($action === 'update_status') {
        $documentId = intval($_POST['document_id']);
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE review_documents SET status = ? WHERE document_id = ?");
        $stmt->execute([$status, $documentId]);
        $message = "✅ Document status updated!";
        header("Location: ?module_id=$selectedModuleId&updated=1");
        exit();
    }
}

// Get uploaded documents - FIXED: Removed u.username column
$documents = [];
if ($selectedModuleId > 0) {
    $stmt = $pdo->prepare("
        SELECT d.*, lo.outcome_number
        FROM review_documents d
        LEFT JOIN learning_outcomes lo ON d.topic_id = lo.outcome_id
        WHERE d.module_id = ?
        ORDER BY d.created_at DESC
    ");
    $stmt->execute([$selectedModuleId]);
    $documents = $stmt->fetchAll();
}

include_once '../includes/templates/header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Documents Manager</title>
    <style>
        .documents-manager { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .page-header { margin-bottom: 25px; }
        .page-header h1 { font-size: 28px; color: #1a1a2e; margin: 0; }
        .page-header p { color: #666; margin: 5px 0 0; }
        
        .selection-area { margin-bottom: 25px; }
        .module-select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 16px; background: white; cursor: pointer; }
        
        .stats-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .stat-card { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 12px; }
        .stat-number { font-size: 28px; font-weight: bold; }
        .stat-label { font-size: 13px; color: #666; margin-top: 5px; }
        
        .upload-card { background: white; border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .upload-area { border: 2px dashed #ccc; border-radius: 16px; padding: 40px; text-align: center; cursor: pointer; transition: 0.3s; margin-bottom: 20px; }
        .upload-area:hover { border-color: #667eea; background: #f8f9ff; }
        .upload-area i { font-size: 48px; color: #667eea; margin-bottom: 15px; }
        
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; font-size: 14px; }
        
        .btn-upload { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 12px 30px; border-radius: 40px; cursor: pointer; font-size: 16px; font-weight: 600; }
        .btn-delete { background: #f44336; color: white; border: none; padding: 6px 14px; border-radius: 20px; cursor: pointer; font-size: 12px; }
        .btn-download { background: #4CAF50; color: white; border: none; padding: 6px 14px; border-radius: 20px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-publish { background: #2196F3; color: white; border: none; padding: 6px 14px; border-radius: 20px; cursor: pointer; font-size: 12px; }
        .btn-draft { background: #ff9800; color: white; border: none; padding: 6px 14px; border-radius: 20px; cursor: pointer; font-size: 12px; }
        
        .documents-table { width: 100%; border-collapse: collapse; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .documents-table th, .documents-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .documents-table th { background: #f8f9fa; font-weight: 600; }
        
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .badge-published { background: #4CAF50; color: white; }
        .badge-draft { background: #ff9800; color: white; }
        .badge-pdf { background: #f44336; color: white; }
        .badge-word { background: #2196F3; color: white; }
        
        .alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #4CAF50; }
        .alert-error { background: #ffebee; color: #c62828; border-left: 4px solid #f44336; }
        
        .empty-state { text-align: center; padding: 50px; background: white; border-radius: 20px; color: #999; }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .form-row { flex-direction: column; }
            .documents-table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>

<div class="documents-manager">
    <div class="page-header">
        <h1><i class="fas fa-folder-open"></i> Review Documents Manager</h1>
        <p>Upload and manage review documents (PDF, Word, PowerPoint) for students to download</p>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Module Selection -->
    <div class="selection-area">
        <select id="module_select" class="module-select" onchange="loadModule()">
            <option value="">-- Select Module --</option>
            <?php foreach($modules as $module): ?>
                <option value="<?php echo $module['module_id']; ?>" <?php echo $selectedModuleId == $module['module_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($module['module_code']); ?> - <?php echo htmlspecialchars($module['module_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if($selectedModuleId > 0): ?>
    
    <!-- Statistics -->
    <?php 
        $totalDocs = count($documents);
        $publishedDocs = 0;
        $totalSize = 0;
        foreach($documents as $doc) {
            if($doc['status'] == 'published') $publishedDocs++;
            $totalSize += $doc['file_size'];
        }
        $totalSizeMB = round($totalSize / 1048576, 2);
    ?>
    <div class="stats-card">
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?php echo $totalDocs; ?></div><div class="stat-label">Total Documents</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $publishedDocs; ?></div><div class="stat-label">Published</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $totalDocs - $publishedDocs; ?></div><div class="stat-label">Draft</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $totalSizeMB; ?> MB</div><div class="stat-label">Total Size</div></div>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="upload-card">
        <h3><i class="fas fa-cloud-upload-alt"></i> Upload New Document</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_document">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Learning Outcome / Topic</label>
                    <select name="topic_id" class="form-control">
                        <option value="0">-- General (All Topics) --</option>
                        <?php foreach($topics as $topic): ?>
                            <option value="<?php echo $topic['outcome_id']; ?>">LO<?php echo $topic['outcome_number']; ?>: <?php echo htmlspecialchars(substr($topic['outcome_description'] ?? '', 0, 60)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Document Type</label>
                    <select name="document_type" class="form-control">
                        <option value="review_notes">📝 Review Notes</option>
                        <option value="past_papers">📄 Past Papers</option>
                        <option value="summary">📋 Summary</option>
                        <option value="exercises">✏️ Exercises</option>
                        <option value="reference">📚 Reference Material</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Document Title</label>
                <input type="text" name="document_title" class="form-control" placeholder="e.g., Database Normalization Summary" required>
            </div>
            
            <div class="form-group">
                <label>Description (Optional)</label>
                <textarea name="document_description" class="form-control" rows="2" placeholder="Brief description of what this document covers..."></textarea>
            </div>
            
            <div class="upload-area" id="upload_area" onclick="document.getElementById('doc_file').click()">
                <i class="fas fa-cloud-upload-alt"></i>
                <p><strong>Click to upload or drag and drop</strong></p>
                <p>Supported formats: PDF, DOCX, DOC, TXT, PPTX, XLSX (Max 10MB)</p>
                <input type="file" name="document_file" id="doc_file" accept=".pdf,.docx,.doc,.txt,.pptx,.xlsx" style="display:none;" onchange="updateFileName(this)">
            </div>
            <div id="file_name_display" style="margin-bottom: 20px; font-size: 13px; color: #666;"></div>
            
            <button type="submit" class="btn-upload"><i class="fas fa-upload"></i> Upload Document</button>
        </form>
    </div>

    <!-- Documents List -->
    <h3 style="margin: 20px 0 15px;"><i class="fas fa-list"></i> Uploaded Documents</h3>
    
    <?php if(empty($documents)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open" style="font-size: 48px; color: #ccc; margin-bottom: 15px; display: block;"></i>
            <p>No documents uploaded yet. Use the form above to upload review materials.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="documents-table">
                <thead>
                    <tr><th>ID</th><th>Title</th><th>Type</th><th>Topic</th><th>File</th><th>Size</th><th>Status</th><th>Uploaded</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach($documents as $doc): ?>
                    <tr>
                        <td><?php echo $doc['document_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($doc['document_title']); ?></strong><br><small><?php echo htmlspecialchars(substr($doc['document_description'] ?? '', 0, 50)); ?></small></td>
                        <td><span class="badge"><?php echo str_replace('_', ' ', $doc['document_type']); ?></span></td>
                        <td><?php echo $doc['outcome_number'] ? "LO{$doc['outcome_number']}" : "General"; ?></td>
                        <td><span class="badge <?php echo $doc['file_type'] == 'pdf' ? 'badge-pdf' : 'badge-word'; ?>"><?php echo strtoupper($doc['file_type']); ?></span></td>
                        <td><?php echo round($doc['file_size'] / 1048576, 2); ?> MB</td>
                        <td><span class="badge <?php echo $doc['status'] == 'published' ? 'badge-published' : 'badge-draft'; ?>"><?php echo ucfirst($doc['status']); ?></span></td>
                        <td><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></td>
                        <td>
                            <a href="download-document.php?id=<?php echo $doc['document_id']; ?>" class="btn-download" target="_blank"><i class="fas fa-download"></i> Download</a>
                            <?php if($doc['status'] == 'published'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="document_id" value="<?php echo $doc['document_id']; ?>">
                                <input type="hidden" name="status" value="draft">
                                <button type="submit" class="btn-draft"><i class="fas fa-eye-slash"></i> Draft</button>
                            </form>
                            <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="document_id" value="<?php echo $doc['document_id']; ?>">
                                <input type="hidden" name="status" value="published">
                                <button type="submit" class="btn-publish"><i class="fas fa-eye"></i> Publish</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this document?')">
                                <input type="hidden" name="action" value="delete_document">
                                <input type="hidden" name="document_id" value="<?php echo $doc['document_id']; ?>">
                                <button type="submit" class="btn-delete"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-folder-open" style="font-size: 48px; color: #ccc; margin-bottom: 15px; display: block;"></i>
            <p>Please select a module to manage review documents.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function loadModule() {
    let moduleId = document.getElementById('module_select').value;
    if (moduleId) window.location.href = '?module_id=' + moduleId;
}

function updateFileName(input) {
    if (input.files && input.files[0]) {
        let sizeMB = (input.files[0].size / 1048576).toFixed(2);
        document.getElementById('file_name_display').innerHTML = '<i class="fas fa-file"></i> Selected: ' + input.files[0].name + ' (' + sizeMB + ' MB)';
    }
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>
</body>
</html>