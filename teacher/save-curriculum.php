<?php
/**
 * Save Parsed Curriculum to Database
 * Path: /teacher/save-curriculum.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

if (!isset($_SESSION['parsed_curriculum'])) {
    header('Location: /teacher/upload-curriculum.php?error=no_data');
    exit();
}

$parsedData = $_SESSION['parsed_curriculum'];
$moduleId = $parsedData['module_id'];

try {
    $pdo->beginTransaction();
    
    // Delete existing curriculum for this module (optional)
    // $pdo->prepare("DELETE FROM learning_outcomes WHERE module_id = ?")->execute([$moduleId]);
    
    // Insert Learning Outcomes, Indicative Contents, Topics, Subtopics
    foreach ($parsedData['learning_outcomes'] as $lo) {
        $stmt = $pdo->prepare("INSERT INTO learning_outcomes (module_id, outcome_number, description, learning_hours, order_position) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$moduleId, $lo['number'], $lo['description'], $lo['hours'], $lo['number']]);
        $outcomeId = $pdo->lastInsertId();
        
        $icOrder = 1;
        foreach ($lo['indicative_contents'] as $ic) {
            $stmt = $pdo->prepare("INSERT INTO indicative_contents (module_id, outcome_id, ic_title, ic_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$moduleId, $outcomeId, $ic['title'], $icOrder]);
            $icId = $pdo->lastInsertId();
            $icOrder++;
            
            $topicOrder = 1;
            foreach ($ic['topics'] as $topic) {
                $stmt = $pdo->prepare("INSERT INTO topics (ic_id, topic_title, topic_order) VALUES (?, ?, ?)");
                $stmt->execute([$icId, $topic['title'], $topicOrder]);
                $topicId = $pdo->lastInsertId();
                $topicOrder++;
                
                $subtopicOrder = 1;
                foreach ($topic['subtopics'] as $subtopic) {
                    $stmt = $pdo->prepare("INSERT INTO subtopics (topic_id, subtopic_title, subtopic_order, has_checkmark) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$topicId, $subtopic['title'], $subtopicOrder]);
                    $subtopicId = $pdo->lastInsertId();
                    $subtopicOrder++;
                    
                    if (!empty($subtopic['details'])) {
                        $detailOrder = 1;
                        foreach ($subtopic['details'] as $detail) {
                            $stmt = $pdo->prepare("INSERT INTO subtopic_details (subtopic_id, detail_text, detail_order) VALUES (?, ?, ?)");
                            $stmt->execute([$subtopicId, $detail, $detailOrder]);
                            $detailOrder++;
                        }
                    }
                }
            }
        }
    }
    
    // Update module status to draft (ready for review)
    $pdo->prepare("UPDATE modules SET status = 'draft' WHERE module_id = ?")->execute([$moduleId]);
    
    $pdo->commit();
    
    unset($_SESSION['parsed_curriculum']);
    unset($_SESSION['parsed_file']);
    
    header("Location: /teacher/curriculum-editor.php?module_id=$moduleId&success=1");
    exit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: /teacher/upload-curriculum.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>