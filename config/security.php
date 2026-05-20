 
<?php
/**
 * Security Configuration
 * TVET Skill Tree Platform
 */

// CSRF Protection
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// XSS Protection - Escape output
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Input Sanitization
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim(htmlspecialchars(strip_tags($input)));
}

// Password Hashing
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate Secure Random String
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Login Attempt Tracking
function checkLoginAttempts($email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "login_attempts_{$ip}_{$email}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        return true;
    }
    
    $attempts = $_SESSION[$key];
    
    // Reset after timeout
    if (time() - $attempts['first_attempt'] > LOGIN_ATTEMPTS_TIMEOUT) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        return true;
    }
    
    if ($attempts['count'] >= LOGIN_ATTEMPTS_MAX) {
        return false;
    }
    
    return true;
}

function recordLoginAttempt($email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "login_attempts_{$ip}_{$email}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
    } else {
        $_SESSION[$key]['count']++;
    }
}

function resetLoginAttempts($email) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "login_attempts_{$ip}_{$email}";
    unset($_SESSION[$key]);
}

// Session Security
function regenerateSession() {
    session_regenerate_id(true);
    $_SESSION['session_id'] = session_id();
    $_SESSION['last_activity'] = time();
}

function validateSession() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

// IP Address
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'];
}

// User Agent
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

// Browser Fingerprint (for anti-cheating)
function getBrowserFingerprint() {
    $data = [
        'user_agent' => getUserAgent(),
        'language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        'platform' => php_uname('s'),
        'screen_resolution' => $_SESSION['screen_resolution'] ?? 'unknown'
    ];
    return hash('sha256', json_encode($data));
}

// SQL Injection Prevention (using PDO prepared statements already)
// But additional helper for manual queries
function escapeLike($string) {
    return addcslashes($string, '%_');
}

// File Upload Security
function validateFileUpload($file, $allowedTypes = []) {
    $allowed = $allowedTypes ?: ALLOWED_EXTENSIONS;
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowed)) {
        return ['valid' => false, 'error' => 'File type not allowed'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['valid' => false, 'error' => 'File too large'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload error'];
    }
    
    return ['valid' => true];
}

// Rate Limiting
function checkRateLimit($key, $limit = API_RATE_LIMIT, $window = 60) {
    $ip = getUserIP();
    $rateKey = "rate_limit_{$ip}_{$key}";
    
    if (!isset($_SESSION[$rateKey])) {
        $_SESSION[$rateKey] = ['count' => 1, 'reset' => time() + $window];
        return true;
    }
    
    if (time() > $_SESSION[$rateKey]['reset']) {
        $_SESSION[$rateKey] = ['count' => 1, 'reset' => time() + $window];
        return true;
    }
    
    if ($_SESSION[$rateKey]['count'] >= $limit) {
        return false;
    }
    
    $_SESSION[$rateKey]['count']++;
    return true;
}

// Headers Security
function setSecurityHeaders() {
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Content-Security-Policy: default-src 'self'");
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

// SQL Query Logger (for debugging)
function logQuery($sql, $params = [], $error = null) {
    $logFile = dirname(__DIR__) . '/logs/queries.log';
    $timestamp = date('Y-m-d H:i:s');
    $data = "[$timestamp] SQL: $sql\n";
    if ($params) {
        $data .= "Params: " . json_encode($params) . "\n";
    }
    if ($error) {
        $data .= "Error: $error\n";
    }
    $data .= "----------------------------------------\n";
    file_put_contents($logFile, $data, FILE_APPEND);
}

?>