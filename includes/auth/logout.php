<?php
/**
 * Logout Page
 * Path: /includes/auth/logout.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log activity before logout (only if function exists)
if (isset($_SESSION['user_id'])) {
    // Check if logActivity function exists
    if (function_exists('logActivity')) {
        logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    }
}

// Clear all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear remember me cookie
setcookie('remember_token', '', time() - 3600, '/');

// Destroy session
session_destroy();

// Redirect to login page
header('Location: /includes/auth/login.php?message=logged_out');
exit();
?>