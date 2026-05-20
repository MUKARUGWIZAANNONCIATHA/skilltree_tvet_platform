<?php
/**
 * Application Configuration
 * TVET Skill Tree Platform
 */

// Application Settings
define('BASE_URL', '/');
define('APP_NAME', 'SkillTree TVET Platform');
define('APP_VERSION', '1.0.0');
define('APP_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('BASE_PATH', '');
define('APP_ENV', 'production'); // development, production

// Timezone
date_default_timezone_set('Africa/Kigali');

// Session Settings - Check if session is NOT active before setting ini
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.gc_maxlifetime', 7200); // 2 hours
}

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// File Upload Settings
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'doc', 'docx']);
define('UPLOAD_PATH', dirname(__DIR__) . '/assets/uploads/');

// Pagination
define('ITEMS_PER_PAGE', 20);

// Pass Mark (TVET Standard)
define('PASS_MARK', 70);

// Resource Completion Required (Before Quiz)
define('REQUIRED_RESOURCE_COMPLETION', 90);

// Cache Settings
define('CACHE_ENABLED', false);
define('CACHE_PATH', dirname(__DIR__) . '/cache/');

// Email Settings (Configure for production)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM', 'noreply@skilltree.rw');
define('SMTP_FROM_NAME', 'SkillTree TVET Platform');

// Security Settings
define('BCRYPT_COST', 12);
define('LOGIN_ATTEMPTS_MAX', 5);
define('LOGIN_ATTEMPTS_TIMEOUT', 900); // 15 minutes
define('SESSION_TIMEOUT', 7200); // 2 hours

// API Settings
define('API_RATE_LIMIT', 100); // requests per minute
define('AI_API_KEY', ''); // Your OpenAI API key
define('AI_API_URL', 'https://api.openai.com/v1/chat/completions');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>