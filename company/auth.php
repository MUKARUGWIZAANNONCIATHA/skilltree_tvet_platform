<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
    header('Location: /includes/auth/login.php');
    exit;
}

require_once '../config/database.php';

$userId = $_SESSION['user_id'];

// Fetch company user from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'company' AND is_active = 1 AND is_approved = 1");
$stmt->execute([$userId]);
$company = $stmt->fetch();

if (!$company) {
    session_destroy();
    header('Location: /includes/auth/login.php?error=account_inactive');
    exit;
}

// Use the actual user_id as company_id
$companyId = $company['user_id'];
$companyName = $company['full_name'];
$companyEmail = $company['email'];
$companyPhone = $company['phone'] ?? '';
$companyIndustry = $company['industry'] ?? '';
$companyLocation = $company['location'] ?? '';
$companyWebsite = $company['website'] ?? '';
?>