<?php
/**
 * Export Review Bank Questions as CSV
 * Path: /teacher/export-csv.php
 */

require_once '../config/database.php';
require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

$moduleId = intval($_GET['module_id'] ?? 0);
if (!$moduleId) {
    die('Module ID required.');
}

// Fetch questions for the module
$stmt = $pdo->prepare("
    SELECT r.*, t.topic_title 
    FROM review_bank r
    LEFT JOIN topics t ON r.topic_id = t.topic_id
    WHERE r.module_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
");
$stmt->execute([$moduleId]);
$questions = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="review_bank_questions.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write headers
fputcsv($output, [
    'ID', 'Topic', 'Question Type', 'Question Text', 'Bloom Level', 'Difficulty', 
    'Complexity', 'Marks', 'Model Answer', 'Explanation', 'Created At'
]);

// Write data rows
foreach ($questions as $q) {
    fputcsv($output, [
        $q['review_id'],
        $q['topic_title'],
        $q['question_type'],
        $q['question_text'],
        $q['bloom_level'],
        $q['difficulty'],
        $q['complexity'] ?? 'intermediate',
        $q['marks'] ?? 5,
        $q['model_answer'],
        $q['explanation'],
        $q['created_at']
    ]);
}

fclose($output);
exit;