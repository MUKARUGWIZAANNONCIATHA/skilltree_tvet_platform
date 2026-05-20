<?php
/**
 * AJAX: Update time spent on a subtopic and return quiz unlock status
 * Path: /student/ajax/track-progress.php
 */
require_once '../../config/database.php';
require_once '../includes/auth.php';

$subtopicId = intval($_POST['subtopic_id'] ?? 0);
$timeIncrement = intval($_POST['time'] ?? 0); // seconds

if (!$subtopicId) die(json_encode(['error' => 'Invalid']));

// Fetch subtopic's estimated total time (sum of note reading time + video minutes)
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN resource_type='note' THEN LENGTH(content)/1500 ELSE 0 END),0) +
        COALESCE(SUM(CASE WHEN resource_type='video' THEN duration_minutes*60 ELSE 0 END),0) as estimated_seconds
    FROM subtopic_resources WHERE subtopic_id = ?
");
$stmt->execute([$subtopicId]);
$estimated = $stmt->fetchColumn();
if ($estimated < 30) $estimated = 60; // minimum 60 seconds

// Update or insert progress
$stmt = $pdo->prepare("INSERT INTO topic_progress (student_id, subtopic_id, content_time_spent, last_activity) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE content_time_spent = content_time_spent + ?, last_activity = NOW()");
$stmt->execute([$studentId, $subtopicId, $timeIncrement, $timeIncrement]);

// Get current time spent and quiz status
$stmt = $pdo->prepare("SELECT content_time_spent, quiz_passed FROM topic_progress WHERE student_id = ? AND subtopic_id = ?");
$stmt->execute([$studentId, $subtopicId]);
$row = $stmt->fetch();
$timeSpent = $row['content_time_spent'] ?? 0;
$passed = $row['quiz_passed'] ?? false;

$progressPercent = min(100, round(($timeSpent / $estimated) * 100));
$quizUnlocked = ($progressPercent >= 90) && !$passed;

echo json_encode(['progress_percent' => $progressPercent, 'quiz_unlocked' => $quizUnlocked]);