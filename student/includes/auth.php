<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: /includes/auth/login.php');
    exit;
}
$studentId = $_SESSION['user_id'];
?>