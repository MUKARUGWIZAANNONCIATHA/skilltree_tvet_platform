<?php
/**
 * View a specific past paper / exam resource
 * Path: /student/past-paper-view.php
 */

require_once '../config/database.php';
require_once 'includes/auth.php';

$libraryId = intval($_GET['id'] ?? 0);
if (!$libraryId) {
    die('Invalid resource.');
}

// Fetch the resource (must be published and accessible to student's trade)
$stmt = $pdo->prepare("
    SELECT l.*, t.trade_name 
    FROM library_resources l
    LEFT JOIN trades t ON l.trade_id = t.trade_id
    WHERE l.library_id = ? AND l.resource_type IN ('past_paper', 'marking_guide', 'reference')
");
$stmt->execute([$libraryId]);
$resource = $stmt->fetch();
if (!$resource) {
    die('Resource not found.');
}

// Optionally, check if the student's trade matches the resource trade or resource is global (trade_id IS NULL)
$studentTrade = $pdo->prepare("SELECT trade FROM users WHERE user_id = ?");
$studentTrade->execute([$studentId]);
$studentTradeName = $studentTrade->fetchColumn();

if ($resource['trade_id'] !== null && $resource['trade_name'] !== $studentTradeName) {
    die('You do not have access to this resource.');
}

// Record a view (optional tracking)
// $log = $pdo->prepare("INSERT INTO resource_views (resource_id, student_id) VALUES (?, ?)");
// $log->execute([$libraryId, $studentId]);

include 'includes/header.php';
?>

<div class="past-paper-container">
    <div class="back-link">
        <a href="library.php"><i class="fas fa-arrow-left"></i> Back to Library</a>
    </div>

    <div class="paper-card">
        <div class="paper-header">
            <h1><?= htmlspecialchars($resource['title']) ?></h1>
            <span class="resource-type"><?= str_replace('_', ' ', ucfirst($resource['resource_type'])) ?></span>
        </div>

        <div class="paper-meta">
            <?php if ($resource['year']): ?>
                <span><i class="fas fa-calendar-alt"></i> Year: <?= $resource['year'] ?></span>
            <?php endif; ?>
            <?php if ($resource['trade_name']): ?>
                <span><i class="fas fa-briefcase"></i> Trade: <?= htmlspecialchars($resource['trade_name']) ?></span>
            <?php endif; ?>
            <span><i class="fas fa-file-alt"></i> Format: <?= strtoupper(pathinfo($resource['file_path'], PATHINFO_EXTENSION)) ?></span>
            <span><i class="fas fa-download"></i> Size: <?= file_exists($resource['file_path']) ? round(filesize($resource['file_path']) / 1048576, 2) . ' MB' : '—' ?></span>
        </div>

        <?php if (!empty($resource['description'])): ?>
            <div class="paper-description">
                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($resource['description'])) ?></p>
            </div>
        <?php endif; ?>

        <div class="paper-preview">
            <h3>Preview</h3>
            <?php
            $ext = pathinfo($resource['file_path'], PATHINFO_EXTENSION);
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                <img src="<?= $resource['file_path'] ?>" alt="Preview" style="max-width:100%; border-radius:0.5rem;">
            <?php elseif ($ext === 'pdf'): ?>
                <embed src="<?= $resource['file_path'] ?>" type="application/pdf" width="100%" height="600px" style="border:1px solid #eee; border-radius:0.5rem;">
            <?php else: ?>
                <p>Preview not available for this file type. Please download to view.</p>
            <?php endif; ?>
        </div>

        <div class="download-section">
            <a href="<?= htmlspecialchars($resource['file_path']) ?>" download class="btn-download">
                <i class="fas fa-download"></i> Download Resource
            </a>
        </div>

        <div class="resource-info">
            <p><strong>Uploaded:</strong> <?= date('d M Y', strtotime($resource['created_at'])) ?></p>
        </div>
    </div>
</div>

<style>
    .past-paper-container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .back-link {
        margin-bottom: 1rem;
    }
    .back-link a {
        color: #2c7da0;
        text-decoration: none;
    }
    .paper-card {
        background: white;
        border-radius: 1.2rem;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .paper-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .paper-header h1 {
        font-size: 1.6rem;
        color: #1a5f7a;
        margin: 0;
    }
    .resource-type {
        background: #eef2fa;
        padding: 0.3rem 0.8rem;
        border-radius: 2rem;
        font-size: 0.8rem;
        color: #2c7da0;
    }
    .paper-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eef2f8;
        font-size: 0.85rem;
        color: #6c8faa;
    }
    .paper-meta i {
        margin-right: 0.3rem;
    }
    .paper-description {
        margin-bottom: 1.5rem;
    }
    .paper-description h3 {
        font-size: 1.1rem;
        color: #1a5f7a;
        margin-bottom: 0.5rem;
    }
    .paper-preview {
        margin-bottom: 1.5rem;
    }
    .paper-preview h3 {
        font-size: 1.1rem;
        color: #1a5f7a;
        margin-bottom: 0.5rem;
    }
    .btn-download {
        display: inline-block;
        background: #2c7da0;
        color: white;
        padding: 0.6rem 1.2rem;
        border-radius: 2rem;
        text-decoration: none;
        margin: 0.5rem 0;
    }
    .btn-download:hover {
        background: #1e5f7a;
    }
    .resource-info {
        margin-top: 1rem;
        font-size: 0.8rem;
        color: #8aaec0;
    }
    @media (max-width: 700px) {
        .paper-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        .paper-preview embed {
            height: 300px;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>