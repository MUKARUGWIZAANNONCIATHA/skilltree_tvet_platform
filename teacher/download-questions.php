<?php
require_once '../config/database.php';
require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

$moduleId = intval($_GET['module_id'] ?? 0);
$topicId = intval($_GET['topic_id'] ?? 0);

if (!$moduleId) die('Module required');

$sql = "SELECT r.*, t.topic_title FROM review_bank r LEFT JOIN topics t ON r.topic_id = t.topic_id WHERE r.module_id = ? AND r.status = 'approved'";
$params = [$moduleId];
if ($topicId > 0) { $sql .= " AND r.topic_id = ?"; $params[] = $topicId; }

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questions = $stmt->fetchAll();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="review_bank.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Type', 'Bloom', 'Difficulty', 'Complexity', 'Question', 'Answer', 'Explanation', 'Topic']);
foreach ($questions as $q) {
    fputcsv($output, [$q['review_id'], $q['question_type'], $q['bloom_level'], $q['difficulty'], $q['complexity'], $q['question_text'], $q['model_answer'], $q['explanation'], $q['topic_title']]);
}
fclose($output);
?>