<?php
/**
 * Common Functions
 * Path: /includes/functions/common.php
 * NOTE: This file should NOT start session
 */

// Redirect to appropriate dashboard based on role
function redirectToDashboard($role) {
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    switch($role) {
        case 'admin':
            header('Location: ' . $base . '/admin/dashboard.php');
            break;
        case 'teacher':
            header('Location: ' . $base . '/teacher/dashboard.php');
            break;
        case 'student':
            header('Location: ' . $base . '/student/dashboard.php');
            break;
        case 'company':
            header('Location: ' . $base . '/company/dashboard.php');
            break;
        default:
            header('Location: ' . $base . '/includes/auth/login.php');
    }
    exit();
}

// Log user activity
function logActivity($userId, $action, $details = '') {
    global $pdo;
    $ip = getUserIP();
    $userAgent = getUserAgent();
    $pageUrl = $_SERVER['REQUEST_URI'] ?? '';
    
    $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, action_type, action_details, ip_address, user_agent, page_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $details, $ip, $userAgent, $pageUrl]);
}

// Get user IP
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'];
}

// Get user agent
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

// Generate secure random token
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim(htmlspecialchars(strip_tags($input)));
}

// Check if user has permission for module
function hasModuleAccess($userId, $moduleId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT enrollment_id FROM student_enrollments WHERE student_id = ? AND module_id = ? AND status IN ('enrolled', 'in_progress')");
    $stmt->execute([$userId, $moduleId]);
    return $stmt->fetch() ? true : false;
}

// Get student progress for module
function getStudentProgress($userId, $moduleId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT overall_progress FROM student_enrollments WHERE student_id = ? AND module_id = ?");
    $stmt->execute([$userId, $moduleId]);
    $result = $stmt->fetch();
    return $result ? $result['overall_progress'] : 0;
}

// Format time (seconds to readable)
function formatTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf("%dh %dm", $hours, $minutes);
    } elseif ($minutes > 0) {
        return sprintf("%dm %ds", $minutes, $secs);
    } else {
        return sprintf("%ds", $secs);
    }
}

// Get current timestamp
function now() {
    return date('Y-m-d H:i:s');
}

// Escape for HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateSecureToken();
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Get module by code
function getModuleByCode($moduleCode) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE module_code = ?");
    $stmt->execute([$moduleCode]);
    return $stmt->fetch();
}

// Get learning outcomes for module
function getLearningOutcomes($moduleId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM learning_outcomes WHERE module_id = ? ORDER BY order_position");
    $stmt->execute([$moduleId]);
    return $stmt->fetchAll();
}

// Check login attempts
function checkLoginAttempts($email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "login_attempts_{$ip}_{$email}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        return true;
    }
    
    $attempts = $_SESSION[$key];
    
    if (time() - $attempts['first_attempt'] > LOGIN_ATTEMPTS_TIMEOUT) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        return true;
    }
    
    if ($attempts['count'] >= LOGIN_ATTEMPTS_MAX) {
        return false;
    }
    
    return true;
}

// Record login attempt
function recordLoginAttempt($email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "login_attempts_{$ip}_{$email}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
    } else {
        $_SESSION[$key]['count']++;
    }
}

// Reset login attempts
function resetLoginAttempts($email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "login_attempts_{$ip}_{$email}";
    unset($_SESSION[$key]);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Set security headers
function setSecurityHeaders() {
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("X-Content-Type-Options: nosniff");
}

// ============================================
// SECTOR ICON FUNCTION
// ============================================
function getSectorIcon($sectorName) {
    $icons = [
        'ICT' => '💻', 'Information' => '💻', 'Technology' => '💻',
        'Computer' => '🖥️', 'Software' => '📱', 'Database' => '🗄️',
        'Networking' => '🌐', 'Agriculture' => '🌾', 'Construction' => '🏗️',
        'Building' => '🏠', 'Carpentry' => '🪚', 'Plumbing' => '🚰',
        'Electrical' => '⚡', 'Manufacturing' => '🏭', 'Tourism' => '✈️',
        'Hospitality' => '🍽️', 'Health' => '🏥', 'Business' => '📊',
        'Finance' => '💰', 'Accounting' => '📈', 'Marketing' => '📢',
        'Automotive' => '🚗', 'Mechanics' => '🔧', 'Fashion' => '👗',
        'Design' => '🎨'
    ];
    foreach ($icons as $key => $icon) {
        if (stripos($sectorName, $key) !== false) {
            return $icon;
        }
    }
    return '📂';
}

// ============================================
// ACTIVITY ICON FUNCTION
// ============================================
function getActivityIcon($action) {
    $icons = [
        'login' => '🔐', 'logout' => '🚪', 'upload' => '📤', 
        'delete' => '🗑️', 'edit' => '✏️', 'create' => '➕',
        'approve' => '✅', 'verify' => '✔️', 'reject' => '❌',
        'activate' => '🔓', 'deactivate' => '🔒'
    ];
    return $icons[$action] ?? '📌';
}

// ============================================
// TIME AGO FUNCTION
// ============================================
function time_ago($timestamp) {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', strtotime($timestamp));
}

// ============================================
// TRADE ICON FUNCTION
// ============================================
function getTradeIcon($tradeName) {
    $icons = [
        'Software' => '💻', 'Development' => '⚙️', 'Programming' => '⌨️',
        'Database' => '🗄️', 'Data' => '📊', 'Network' => '🌐', 'Web' => '🌐',
        'Security' => '🔒', 'Cyber' => '🛡️', 'Agriculture' => '🌾',
        'Farming' => '🚜', 'Crops' => '🌽', 'Livestock' => '🐄',
        'Construction' => '🏗️', 'Building' => '🏠', 'Electrical' => '⚡',
        'Plumbing' => '🚰', 'Carpentry' => '🪚', 'Masonry' => '🧱',
        'Welding' => '🔥', 'Painting' => '🎨', 'Manufacturing' => '🏭',
        'Textile' => '🧵', 'Tourism' => '✈️', 'Hospitality' => '🍽️',
        'Hotel' => '🏨', 'Culinary' => '🍳', 'Health' => '🏥',
        'Medical' => '💊', 'Nursing' => '🩺', 'Pharmacy' => '💊',
        'Laboratory' => '🔬', 'Business' => '📊', 'Finance' => '💰',
        'Accounting' => '📈', 'Marketing' => '📢', 'HR' => '👥',
        'Human Resources' => '👥', 'Banking' => '🏦', 'Automotive' => '🚗',
        'Mechanics' => '🔧', 'Auto Electrical' => '🔌', 'Fashion' => '👗',
        'Design' => '🎨', 'Tailoring' => '🧥', 'Garment' => '👕'
    ];
    foreach ($icons as $key => $icon) {
        if (stripos($tradeName, $key) !== false) {
            return $icon;
        }
    }
    return '📂';
}

?>