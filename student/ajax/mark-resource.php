<?php
/**
 * AJAX: Manually mark a resource as studied (adds 60 seconds to progress)
 * Path: /student/ajax/mark-resource.php
 */
require_once '../../config/database.php';
require_once '../includes/auth.php';

$subtopicId = intval($_POST['subtopic_id'] ?? 0);
$resourceType = $_POST['resource_type'] ?? ''; // 'note' or 'video'

if (!$subtopicId || !in_array($resourceType, ['note', 'video'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Add 60 seconds of study time for this resource (manual marking)
$stmt = $pdo->prepare("
    INSERT INTO topic_progress (student_id, subtopic_id, content_time_spent) 
    VALUES (?, ?, 60) 
    ON DUPLICATE KEY UPDATE content_time_spent = content_time_spent + 60
");
$success = $stmt->execute([$studentId, $subtopicId]);

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Progress updated (+60 seconds)' : 'Failed to update progress'
]);