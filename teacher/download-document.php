<?php
// /teacher/download-document.php
require_once '../config/database.php';
require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin', 'student']);

$documentId = intval($_GET['id'] ?? 0);

if ($documentId) {
    $stmt = $pdo->prepare("SELECT * FROM review_documents WHERE document_id = ? AND status = 'published'");
    $stmt->execute([$documentId]);
    $doc = $stmt->fetch();
    
    if ($doc) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $doc['file_name'] . '"');
        header('Content-Length: ' . $doc['file_size']);
        echo $doc['file_content'];
        exit();
    }
}

header('Location: review-documents.php');
exit();
?>