<?php
require_once '../config/database.php';
require_once 'includes/auth.php';

$message = $_POST['message'] ?? '';
if (!$message) {
    echo json_encode(['reply' => 'Ask me about your courses.']);
    exit;
}

$tradeStmt = $pdo->prepare("
    SELECT t.trade_name FROM student_enrollments e
    JOIN trades t ON e.trade_id = t.trade_id
    WHERE e.student_id = ? LIMIT 1
");
$tradeStmt->execute([$studentId]);
$tradeName = $tradeStmt->fetchColumn();

$reply = "Hello! You are studying **$tradeName**. Your question: \"$message\".\n";
if (stripos($message, 'sql') !== false) {
    $reply .= "Example: In SQL, a JOIN combines two tables based on a common key. Example: `SELECT * FROM students JOIN enrollments ON students.id = enrollments.student_id`.";
} elseif (stripos($message, 'normalization') !== false) {
    $reply .= "Normalization reduces redundancy. A table is in 1NF if each cell contains an atomic value.";
} else {
    $reply .= "Check the videos and notes of the corresponding topic, then attempt the quiz to test your understanding.";
}
echo json_encode(['reply' => $reply]);
?>