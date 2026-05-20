<?php
/**
 * AJAX: Check if all subtopics under a Learning Outcome are completed (quiz passed)
 * Path: /student/ajax/check-lo-assessment.php
 */
require_once '../../config/database.php';
require_once '../includes/auth.php';

$loId = intval($_GET['lo_id'] ?? 0);
if (!$loId) {
    echo json_encode(['unlocked' => false, 'error' => 'Invalid LO ID']);
    exit;
}

// Count total subtopics under this LO
$stmt = $pdo->prepare("
    SELECT COUNT(s.subtopic_id) as total
    FROM subtopics s
    JOIN topics t ON s.topic_id = t.topic_id
    JOIN indicative_contents ic ON t.ic_id = ic.ic_id
    WHERE ic.outcome_id = ?
");
$stmt->execute([$loId]);
$total = (int) $stmt->fetchColumn();

if ($total == 0) {
    // No subtopics → no quizzes needed, LO assessment can be unlocked
    echo json_encode(['unlocked' => true]);
    exit;
}

// Count passed subtopics for this student
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT tp.subtopic_id) as passed
    FROM subtopics s
    JOIN topics t ON s.topic_id = t.topic_id
    JOIN indicative_contents ic ON t.ic_id = ic.ic_id
    LEFT JOIN topic_progress tp ON s.subtopic_id = tp.subtopic_id AND tp.student_id = ? AND tp.quiz_passed = 1
    WHERE ic.outcome_id = ?
");
$stmt->execute([$studentId, $loId]);
$passed = (int) $stmt->fetchColumn();

$unlocked = ($passed >= $total);
echo json_encode(['unlocked' => $unlocked]);
?>