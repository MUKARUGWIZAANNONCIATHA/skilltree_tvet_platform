<?php
/**
 * Secure download for library resources
 * Path: /student/download-library.php
 */
require_once '../config/database.php';
require_once 'includes/auth.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) die('Invalid download request.');

// Fetch the file path
$stmt = $pdo->prepare("SELECT file_path, title FROM library_resources WHERE library_id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch();
if (!$file) die('Resource not found.');

$filePath = $file['file_path'];
if (empty($filePath) || !file_exists($filePath)) {
    die('File not found on server.');
}

// Security: restrict to allowed directory (adjust base path below)
$allowedBase = realpath(__DIR__ . '/../uploads/library');
$realPath = realpath($filePath);
if ($realPath === false || strpos($realPath, $allowedBase) !== 0) {
    die('Access denied.');
}

// Serve file
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($realPath));
readfile($realPath);
exit;