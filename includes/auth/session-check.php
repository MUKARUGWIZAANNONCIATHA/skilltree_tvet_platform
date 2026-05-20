<?php
/**
 * Session Check Middleware
 * Path: /includes/auth/session-check.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define SESSION_TIMEOUT if not defined
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 7200); // 2 hours default
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Check remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        require_once '../../config/database.php';
        
        $token = $_COOKIE['remember_token'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login_token = ? AND is_active = 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
        } else {
            header('Location: /includes/auth/login.php');
            exit();
        }
    } else {
        header('Location: /includes/auth/login.php');
        exit();
    }
}

// Validate session timeout
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: /includes/auth/login.php?error=session_expired');
    exit();
}

// Update last activity
$_SESSION['login_time'] = time();

// Check if user has access to this page (role-based)
function requireRole($allowedRoles) {
    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        header('Location: /index.php?error=access_denied');
        exit();
    }
}

// Log page access
function logPageAccess($page) {
    if (isset($_SESSION['user_id'])) {
        require_once '../../config/database.php';
        logActivity($_SESSION['user_id'], 'page_view', "Viewed: $page");
    }
}
?>