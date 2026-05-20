<?php
require_once '../config/database.php';
$moduleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$stmt = $pdo->prepare("SELECT r.*, m.module_name FROM review_bank r JOIN modules m ON r.module_id = m.module_id WHERE r.module_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$moduleId]);
$questions = $stmt->fetchAll();
$module = $pdo->prepare("SELECT module_name FROM modules WHERE module_id = ?");
$module->execute([$moduleId]);
$moduleName = $module->fetch()['module_name'] ?? 'Review Questions';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Review Questions - <?php echo htmlspecialchars($moduleName); ?></title>
    <style>
        body{font-family:Arial,sans-serif;margin:40px;line-height:1.6}
        h1{color:#1e3a5f;border-bottom:2px solid #667eea;padding-bottom:10px}
        .question{margin-bottom:30px;page-break-inside:avoid}
        .q-title{font-weight:bold;background:#f0f0f0;padding:8px;border-radius:5px}
        .q-text{margin:10px 0;padding-left:15px}
        .q-answer{background:#e8f5e9;padding:10px;border-radius:5px;margin:10px 0}
        .q-meta{color:#666;font-size:12px;margin-top:5px}
        hr{margin:20px 0}
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($moduleName); ?> - Review Questions</h1>
    <p>Total Questions: <?php echo count($questions); ?> | Generated: <?php echo date('F d, Y'); ?></p>
    <?php foreach($questions as $index => $q): ?>
    <div class="question">
        <div class="q-title">Question <?php echo $index + 1; ?> (<?php echo ucfirst($q['bloom_level']); ?> | <?php echo ucfirst($q['difficulty']); ?>)</div>
        <div class="q-text"><?php echo nl2br(htmlspecialchars($q['question_text'])); ?></div>
        <div class="q-answer"><strong>Answer:</strong> <?php echo nl2br(htmlspecialchars($q['explanation'] ?? 'No answer provided')); ?></div>
        <div class="q-meta">Type: <?php echo str_replace('_', ' ', ucfirst($q['question_type'])); ?></div>
    </div>
    <?php endforeach; ?>
</body>
</html>