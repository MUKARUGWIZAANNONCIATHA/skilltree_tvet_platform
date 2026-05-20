<?php
/**
 * Topic Resources Manager - 3 Options: AI, Paste, Upload
 * Path: /teacher/topic-resources.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$topicId = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
$message = '';
$error = '';

// Get topic info
$stmt = $pdo->prepare("
    SELECT t.*, ic.ic_title, lo.outcome_number, m.module_name, m.module_code
    FROM topics t
    JOIN indicative_contents ic ON t.ic_id = ic.ic_id
    JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
    JOIN modules m ON lo.module_id = m.module_id
    WHERE t.topic_id = ?
");
$stmt->execute([$topicId]);
$topic = $stmt->fetch();

if (!$topic) {
    header('Location: /teacher/curriculum-editor.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // OPTION 1: AI Generate
    if ($action === 'ai_generate') {
        require_once '../includes/functions/ai-resource-generator.php';
        $ai = new AIResourceGenerator();
        $result = $ai->generateForTopic($topicId, $topic['topic_title']);
        
        if ($result['success']) {
            // Save AI generated notes
            $stmt = $pdo->prepare("INSERT INTO topic_resources (topic_id, resource_type, title, content, source) VALUES (?, 'note', ?, ?, 'ai_generated')");
            $stmt->execute([$topicId, 'AI Generated Notes', $result['notes']]);
            
            // Save suggested videos
            foreach ($result['videos'] as $video) {
                $stmt = $pdo->prepare("INSERT INTO topic_resources (topic_id, resource_type, title, url, duration_minutes, source) VALUES (?, 'video', ?, ?, ?, 'ai_generated')");
                $stmt->execute([$topicId, $video['title'], $video['url'], $video['duration']]);
            }
            
            // Save suggested links
            foreach ($result['links'] as $link) {
                $stmt = $pdo->prepare("INSERT INTO topic_resources (topic_id, resource_type, title, url, source) VALUES (?, 'link', ?, ?, 'ai_generated')");
                $stmt->execute([$topicId, $link['title'], $link['url']]);
            }
            
            $message = "AI generated content added successfully!";
        } else {
            $error = "AI generation failed: " . ($result['error'] ?? 'Unknown error');
        }
    }
    
    // OPTION 2: Paste & Save
    elseif ($action === 'paste_save') {
        $noteTitle = sanitize($_POST['note_title']);
        $noteContent = $_POST['note_content'];
        $videoTitle = sanitize($_POST['video_title']);
        $videoUrl = sanitize($_POST['video_url']);
        $linkTitle = sanitize($_POST['link_title']);
        $linkUrl = sanitize($_POST['link_url']);
        
        if (!empty($noteTitle) && !empty($noteContent)) {
            $stmt = $pdo->prepare("INSERT INTO topic_resources (topic_id, resource_type, title, content, source) VALUES (?, 'note', ?, ?, 'teacher_added')");
            $stmt->execute([$topicId, $noteTitle, $noteContent]);
        }
        
        if (!empty($videoTitle) && !empty($videoUrl)) {
            $stmt = $pdo->prepare("INSERT INTO topic_resources (topic_id, resource_type, title, url, source) VALUES (?, 'video', ?, ?, 'teacher_added')");
            $stmt->execute([$topicId, $videoTitle, $videoUrl]);
        }
        
        if (!empty($linkTitle) && !empty($linkUrl)) {
            $stmt = $pdo->prepare("INSERT INTO topic_resources (topic_id, resource_type, title, url, source) VALUES (?, 'link', ?, ?, 'teacher_added')");
            $stmt->execute([$topicId, $linkTitle, $linkUrl]);
        }
        
        $message = "Resources saved successfully!";
    }
    
    // OPTION 3: Upload File
    elseif ($action === 'upload_file') {
        if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/uploads/resources/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileName = time() . '_' . basename($_FILES['resource_file']['name']);
            $filePath = $uploadDir . $fileName;
            $fileTitle = sanitize($_POST['file_title'] ?: $_FILES['resource_file']['name']);
            
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $resourceType = 'document';
            if (in_array($fileExt, ['mp4', 'mov', 'avi', 'webm'])) $resourceType = 'video';
            if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])) $resourceType = 'image';
            if ($fileExt === 'pdf') $resourceType = 'pdf';
            
            if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $filePath)) {
                $stmt = $pdo->prepare("INSERT INTO topic_resources (topic_id, resource_type, title, file_path, source) VALUES (?, ?, ?, ?, 'teacher_added')");
                $stmt->execute([$topicId, $resourceType, $fileTitle, $filePath]);
                $message = "File uploaded successfully!";
            } else {
                $error = "Failed to upload file.";
            }
        } else {
            $error = "Please select a file to upload.";
        }
    }
}

// Get existing resources
$resources = $pdo->prepare("SELECT * FROM topic_resources WHERE topic_id = ? ORDER BY display_order, created_at");
$resources->execute([$topicId]);
$resources = $resources->fetchAll();

include_once '../includes/templates/header.php';
?>

<div class="resources-container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-book-open"></i> Topic Resources</h1>
            <p class="breadcrumb">
                <?php echo htmlspecialchars($topic['module_code']); ?> - <?php echo htmlspecialchars($topic['module_name']); ?>
                &gt; LO<?php echo $topic['outcome_number']; ?> &gt; <?php echo htmlspecialchars($topic['ic_title']); ?>
                &gt; <strong><?php echo htmlspecialchars($topic['topic_title']); ?></strong>
            </p>
        </div>
        <a href="/teacher/curriculum-editor.php?module_id=<?php echo $topic['module_id']; ?>" class="btn-back">
            ← Back to Curriculum
        </a>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success">✅ <?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-error">❌ <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- THREE OPTIONS TABS -->
    <div class="options-tabs">
        <button class="tab-btn active" onclick="showTab('ai')">🤖 AI Generate</button>
        <button class="tab-btn" onclick="showTab('paste')">📋 Paste & Save</button>
        <button class="tab-btn" onclick="showTab('upload')">📤 Upload File</button>
    </div>

    <!-- OPTION 1: AI GENERATE -->
    <div id="ai-tab" class="tab-content active">
        <div class="options-card">
            <h2><i class="fas fa-robot"></i> AI Generate Content</h2>
            <p>AI will automatically generate notes, videos, and links for this topic.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="ai_generate">
                
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>Topic:</strong> <?php echo htmlspecialchars($topic['topic_title']); ?>
                </div>
                
                <button type="submit" class="btn-generate">
                    <i class="fas fa-magic"></i> Generate Content with AI
                </button>
            </form>
            
            <div class="ai-preview-note">
                <i class="fas fa-lightbulb"></i>
                <strong>What AI will provide:</strong>
                <ul>
                    <li>📝 Complete notes with key concepts</li>
                    <li>🎥 2-3 recommended YouTube videos</li>
                    <li>🔗 3-5 relevant resource links</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- OPTION 2: PASTE & SAVE -->
    <div id="paste-tab" class="tab-content">
        <div class="options-card">
            <h2><i class="fas fa-paste"></i> Paste & Save</h2>
            <p>Paste your prepared notes, videos, and links.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="paste_save">
                
                <div class="form-section">
                    <h3>📝 Notes</h3>
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="note_title" class="form-control" placeholder="e.g., Introduction to Node.js">
                    </div>
                    <div class="form-group">
                        <label>Content (Markdown supported)</label>
                        <textarea name="note_content" class="form-control" rows="8" placeholder="Write your notes here..."></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>🎥 Video</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Video Title</label>
                            <input type="text" name="video_title" class="form-control" placeholder="e.g., Node.js Tutorial">
                        </div>
                        <div class="form-group">
                            <label>Video URL</label>
                            <input type="url" name="video_url" class="form-control" placeholder="https://youtube.com/...">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>🔗 Link</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Link Title</label>
                            <input type="text" name="link_title" class="form-control" placeholder="e.g., Official Documentation">
                        </div>
                        <div class="form-group">
                            <label>Link URL</label>
                            <input type="url" name="link_url" class="form-control" placeholder="https://...">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Resources
                </button>
            </form>
        </div>
    </div>

    <!-- OPTION 3: UPLOAD FILE -->
    <div id="upload-tab" class="tab-content">
        <div class="options-card">
            <h2><i class="fas fa-upload"></i> Upload File</h2>
            <p>Upload your own files (videos, PDFs, images, documents).</p>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_file">
                
                <div class="form-group">
                    <label>Title (optional)</label>
                    <input type="text" name="file_title" class="form-control" placeholder="Leave empty to use filename">
                </div>
                
                <div class="drop-area" id="drop-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag & drop your file here or click to browse</p>
                    <p class="file-types">Supported: MP4, PDF, JPG, PNG, DOCX, PPT</p>
                    <input type="file" name="resource_file" id="resource_file" required style="display:none;">
                    <button type="button" class="btn-browse" onclick="document.getElementById('resource_file').click()">Browse Files</button>
                </div>
                <div id="file-info" class="file-info" style="display:none;">
                    <i class="fas fa-file"></i> <span id="file-name"></span>
                    <button type="button" class="btn-remove" onclick="removeFile()">×</button>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-upload"></i> Upload File
                </button>
            </form>
        </div>
    </div>

    <!-- EXISTING RESOURCES -->
    <?php if(!empty($resources)): ?>
    <div class="existing-resources">
        <h2><i class="fas fa-list"></i> Existing Resources</h2>
        <div class="resources-list">
            <?php foreach($resources as $resource): ?>
            <div class="resource-item">
                <div class="resource-icon">
                    <?php
                    $icon = '📄';
                    if ($resource['resource_type'] == 'video') $icon = '🎥';
                    if ($resource['resource_type'] == 'link') $icon = '🔗';
                    if ($resource['resource_type'] == 'image') $icon = '🖼️';
                    if ($resource['resource_type'] == 'pdf') $icon = '📕';
                    echo $icon;
                    ?>
                </div>
                <div class="resource-info">
                    <div class="resource-title"><?php echo htmlspecialchars($resource['title']); ?></div>
                    <div class="resource-meta">
                        <span class="resource-type"><?php echo ucfirst($resource['resource_type']); ?></span>
                        <span class="resource-source <?php echo $resource['source']; ?>">
                            <?php echo $resource['source'] == 'ai_generated' ? '🤖 AI' : '👨‍🏫 Teacher'; ?>
                        </span>
                        <span class="resource-date"><?php echo date('M d, Y', strtotime($resource['created_at'])); ?></span>
                    </div>
                    <?php if($resource['url']): ?>
                        <a href="<?php echo htmlspecialchars($resource['url']); ?>" target="_blank" class="resource-link">View Resource →</a>
                    <?php elseif($resource['file_path']): ?>
                        <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" target="_blank" class="resource-link">Download →</a>
                    <?php elseif($resource['content']): ?>
                        <div class="resource-preview"><?php echo nl2br(htmlspecialchars(substr($resource['content'], 0, 200))); ?>...</div>
                    <?php endif; ?>
                </div>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this resource?')">
                    <input type="hidden" name="action" value="delete_resource">
                    <input type="hidden" name="resource_id" value="<?php echo $resource['resource_id']; ?>">
                    <button type="submit" class="btn-delete" title="Delete">🗑️</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.resources-container{max-width:1000px;margin:0 auto;padding:30px 24px;}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;flex-wrap:wrap;gap:15px;}
.breadcrumb{color:#666;font-size:14px;margin-top:5px;}
.btn-back{background:#667eea;color:white;padding:8px 20px;border-radius:30px;text-decoration:none;}

.options-tabs{display:flex;gap:10px;margin-bottom:25px;border-bottom:1px solid #e0e0e0;}
.tab-btn{padding:12px 24px;background:none;border:none;font-size:16px;cursor:pointer;color:#666;}
.tab-btn.active{color:#667eea;border-bottom:3px solid #667eea;}
.tab-content{display:none;}
.tab-content.active{display:block;}

.options-card{background:white;border-radius:20px;padding:30px;margin-bottom:30px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
.form-section{margin-bottom:25px;padding-bottom:20px;border-bottom:1px solid #eee;}
.form-section h3{margin-bottom:15px;color:#1e3a5f;}
.form-group{margin-bottom:15px;}
.form-group label{display:block;margin-bottom:5px;font-weight:500;}
.form-control{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:15px;}
.btn-generate,.btn-save,.btn-submit{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;padding:12px 30px;border-radius:40px;cursor:pointer;font-weight:600;margin-top:15px;}
.btn-generate:hover,.btn-save:hover,.btn-submit:hover{transform:scale(1.02);}
.info-box{background:#e8f0fe;padding:12px 15px;border-radius:8px;margin-bottom:20px;}
.ai-preview-note{background:#f8f9fa;padding:15px;border-radius:12px;margin-top:20px;}
.ai-preview-note ul{margin-top:10px;padding-left:20px;}
.drop-area{border:2px dashed #ccc;border-radius:12px;padding:40px;text-align:center;cursor:pointer;transition:all 0.3s;background:#fafafa;}
.drop-area:hover{border-color:#667eea;background:#f8f9ff;}
.file-types{font-size:12px;color:#999;margin-top:10px;}
.file-info{margin-top:15px;padding:12px;background:#e8f5e9;border-radius:8px;display:flex;align-items:center;gap:10px;}
.btn-remove{background:none;border:none;font-size:20px;cursor:pointer;margin-left:auto;color:#999;}

.existing-resources{background:white;border-radius:20px;padding:25px;margin-top:30px;}
.existing-resources h2{font-size:18px;margin-bottom:20px;}
.resources-list{display:flex;flex-direction:column;gap:15px;}
.resource-item{display:flex;align-items:flex-start;gap:15px;padding:15px;background:#f8f9fa;border-radius:12px;}
.resource-icon{font-size:28px;}
.resource-info{flex:1;}
.resource-title{font-weight:600;margin-bottom:5px;}
.resource-meta{display:flex;gap:10px;margin-bottom:8px;}
.resource-type{font-size:11px;padding:2px 8px;border-radius:20px;background:#e0e0e0;}
.resource-source.ai_generated{background:#e8f0fe;color:#667eea;}
.resource-source.teacher_added{background:#e8f5e9;color:#4CAF50;}
.resource-date{font-size:11px;color:#999;}
.resource-link{color:#667eea;text-decoration:none;font-size:13px;}
.resource-preview{font-size:13px;color:#666;margin-top:5px;}
.btn-delete{background:none;border:none;cursor:pointer;font-size:18px;color:#f44336;}
.btn-delete:hover{transform:scale(1.1);}
.alert{padding:15px 20px;border-radius:12px;margin-bottom:20px;}
.alert-success{background:#e8f5e9;color:#2e7d32;border-left:4px solid #4CAF50;}
.alert-error{background:#ffebee;color:#c62828;border-left:4px solid #f44336;}
@media(max-width:700px){.form-row{grid-template-columns:1fr;}}
</style>

<script>
function showTab(tab) {
    document.getElementById('ai-tab').classList.remove('active');
    document.getElementById('paste-tab').classList.remove('active');
    document.getElementById('upload-tab').classList.remove('active');
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    if (tab === 'ai') {
        document.getElementById('ai-tab').classList.add('active');
        document.querySelectorAll('.tab-btn')[0].classList.add('active');
    } else if (tab === 'paste') {
        document.getElementById('paste-tab').classList.add('active');
        document.querySelectorAll('.tab-btn')[1].classList.add('active');
    } else {
        document.getElementById('upload-tab').classList.add('active');
        document.querySelectorAll('.tab-btn')[2].classList.add('active');
    }
}

const dropArea = document.getElementById('drop-area');
const fileInput = document.getElementById('resource_file');
if (dropArea) {
    dropArea.addEventListener('click', () => fileInput.click());
    dropArea.addEventListener('dragover', (e) => { e.preventDefault(); dropArea.style.borderColor = '#667eea'; });
    dropArea.addEventListener('dragleave', () => { dropArea.style.borderColor = '#ccc'; });
    dropArea.addEventListener('drop', (e) => {
        e.preventDefault();
        fileInput.files = e.dataTransfer.files;
        document.getElementById('file-info').style.display = 'flex';
        document.getElementById('file-name').innerText = fileInput.files[0].name;
        dropArea.style.display = 'none';
    });
}
fileInput?.addEventListener('change', () => {
    if(fileInput.files.length > 0) {
        document.getElementById('file-info').style.display = 'flex';
        document.getElementById('file-name').innerText = fileInput.files[0].name;
        dropArea.style.display = 'none';
    }
});
function removeFile() {
    fileInput.value = '';
    document.getElementById('file-info').style.display = 'none';
    dropArea.style.display = 'block';
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>