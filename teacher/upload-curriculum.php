<?php
/**
 * Upload / Paste Curriculum Structure - Fixed for Teacher Permissions
 * Path: /teacher/upload-curriculum.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$moduleId = intval($_GET['module_id'] ?? 0);
$message = '';
$error = '';

// If no module selected, go back to module list
if (!$moduleId) {
    header('Location: /teacher/modules.php');
    exit;
}

// Check permission: teacher must be assigned to this module (or be admin)
$teacherId = $_SESSION['user_id'];
// Use the correct session variable for role (must match your session-check.php)
$isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ||
           (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

if (!$isAdmin) {
    $checkPerm = $pdo->prepare("SELECT COUNT(*) FROM teacher_modules WHERE teacher_id = ? AND module_id = ?");
    $checkPerm->execute([$teacherId, $moduleId]);
    $hasAccess = $checkPerm->fetchColumn() > 0;
    if (!$hasAccess) {
        die("You are not assigned to this module. <a href='modules.php'>Go back</a>");
    }
}

// Fetch module details
$stmt = $pdo->prepare("SELECT module_code, module_name FROM modules WHERE module_id = ?");
$stmt->execute([$moduleId]);
$module = $stmt->fetch();
if (!$module) {
    die('Module not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curriculumText = trim($_POST['curriculum_text'] ?? '');
    if (empty($curriculumText)) {
        $error = 'Please paste the curriculum structure.';
    } else {
        $lines = explode("\n", $curriculumText);
        $currentLo = null;
        $currentIc = null;
        $currentTopic = null;
        
        try {
            $pdo->beginTransaction();
            
            foreach ($lines as $line) {
                $line = rtrim($line);
                if (trim($line) === '') continue;
                
                $leadingSpaces = strlen($line) - strlen(ltrim($line, " \t"));
                $indentLevel = floor($leadingSpaces / 2);
                $trimmed = trim($line);
                
                // Learning Outcome (level 0)
                if (preg_match('/^(LO\s*\d+|[Ll]earning\s+[Oo]utcome\s*\d+)\s*[:.-]\s*(.+)/', $trimmed, $matches)) {
                    $loNumber = preg_replace('/\D/', '', $matches[1]);
                    $loDesc = trim($matches[2]);
                    
                    $stmtCheck = $pdo->prepare("SELECT outcome_id FROM learning_outcomes WHERE module_id = ? AND outcome_number = ?");
                    $stmtCheck->execute([$moduleId, $loNumber]);
                    if ($row = $stmtCheck->fetch()) {
                        $currentLo = $row['outcome_id'];
                        $pdo->prepare("UPDATE learning_outcomes SET description = ? WHERE outcome_id = ?")->execute([$loDesc, $currentLo]);
                    } else {
                        $stmtIns = $pdo->prepare("INSERT INTO learning_outcomes (module_id, outcome_number, description) VALUES (?, ?, ?)");
                        $stmtIns->execute([$moduleId, $loNumber, $loDesc]);
                        $currentLo = $pdo->lastInsertId();
                    }
                    $currentIc = null;
                    $currentTopic = null;
                }
                // Indicative Content (level 1)
                elseif ($currentLo && $indentLevel == 1 && preg_match('/^(IC\s*\d*|[Ii]ndicative\s+[Cc]ontent\s*\d*)\s*[:.-]\s*(.+)/', $trimmed, $matches)) {
                    $icTitle = trim($matches[2]);
                    $orderStmt = $pdo->prepare("SELECT MAX(ic_order) FROM indicative_contents WHERE outcome_id = ?");
                    $orderStmt->execute([$currentLo]);
                    $maxOrder = $orderStmt->fetchColumn() + 1;
                    $stmtIns = $pdo->prepare("INSERT INTO indicative_contents (outcome_id, ic_title, ic_order, module_id) VALUES (?, ?, ?, ?)");
                    $stmtIns->execute([$currentLo, $icTitle, $maxOrder, $moduleId]);
                    $currentIc = $pdo->lastInsertId();
                    $currentTopic = null;
                }
                // Topic (level 2)
                elseif ($currentIc && $indentLevel == 2 && (preg_match('/^[-*]\s+(.+)/', $trimmed, $matches) || preg_match('/^\d+[.)]\s+(.+)/', $trimmed, $matches))) {
                    $topicTitle = trim($matches[1]);
                    $orderStmt = $pdo->prepare("SELECT MAX(topic_order) FROM topics WHERE ic_id = ?");
                    $orderStmt->execute([$currentIc]);
                    $maxOrder = $orderStmt->fetchColumn() + 1;
                    $stmtIns = $pdo->prepare("INSERT INTO topics (ic_id, topic_title, topic_order) VALUES (?, ?, ?)");
                    $stmtIns->execute([$currentIc, $topicTitle, $maxOrder]);
                    $currentTopic = $pdo->lastInsertId();
                }
                // Subtopic (level 3+)
                elseif ($currentTopic && $indentLevel >= 3 && preg_match('/^[-*]\s+(.+)/', $trimmed, $matches)) {
                    $subtitle = trim($matches[1]);
                    $orderStmt = $pdo->prepare("SELECT MAX(subtopic_order) FROM subtopics WHERE topic_id = ?");
                    $orderStmt->execute([$currentTopic]);
                    $maxOrder = $orderStmt->fetchColumn() + 1;
                    $stmtIns = $pdo->prepare("INSERT INTO subtopics (topic_id, subtopic_title, subtopic_order) VALUES (?, ?, ?)");
                    $stmtIns->execute([$currentTopic, $subtitle, $maxOrder]);
                }
            }
            
            $pdo->commit();
            $message = "Curriculum structure saved successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

include_once '../includes/templates/header.php';
?>

<div class="upload-curriculum">
    <div class="page-header">
        <h1><i class="fas fa-upload"></i> Upload / Paste Curriculum Structure</h1>
        <p>Module: <?= htmlspecialchars($module['module_code']) ?> - <?= htmlspecialchars($module['module_name']) ?></p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>Paste Your Curriculum Structure</h3>
        <p>Use the format with consistent indentation (2 spaces per level). Example:</p>
        <pre>
LO1: Understand database concepts
  IC1: Introduction to Databases
    - What is a database?
      - Definition
      - Purpose
    - Types of databases
  IC2: Database Components
    - Tables
    - Records
LO2: Apply SQL queries
  IC1: SELECT statements
    - Basic SELECT
    - WHERE clause
        </pre>
        <form method="post">
            <textarea name="curriculum_text" rows="20" class="form-control" placeholder="Paste your structured curriculum here..."></textarea>
            <button type="submit" class="btn-primary" style="margin-top:15px;">Save Curriculum</button>
        </form>
    </div>
</div>

<style>
.upload-curriculum { max-width: 1000px; margin: 0 auto; padding: 20px; }
.card { background: white; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.form-control { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 6px; font-family: monospace; }
.btn-primary { background: #2c7da0; color: white; border: none; padding: 8px 16px; border-radius: 2rem; cursor: pointer; }
.alert { padding: 10px; border-radius: 8px; margin-bottom: 15px; }
.alert-success { background: #e8f5e9; color: #2e7d32; }
.alert-error { background: #ffebee; color: #c62828; }
</style>

<?php include_once '../includes/templates/footer.php'; ?>