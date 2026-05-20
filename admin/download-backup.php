<?php
require_once '../includes/auth/session-check.php';
requireRole(['admin']);

$file = basename($_GET['file'] ?? '');
$backupDir = __DIR__ . '/../backups/';
$filepath = $backupDir . $file;

if (!$file || !file_exists($filepath) || !is_file($filepath)) {
    die('File not found.');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;