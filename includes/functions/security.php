<?php
/**
 * Security Functions – CSRF, rate limiting, IP blocking, etc.
 * Path: /includes/functions/security.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Generate a CSRF token and store it in the session.
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify the submitted CSRF token.
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output a hidden CSRF token field inside a form.
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Simple rate limiting: limit number of requests per IP per minute.
 * Returns true if allowed, false if limit exceeded.
 */
function rateLimit($key, $maxRequests = 10, $timeWindow = 60) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $file = sys_get_temp_dir() . "/rate_limit_{$ip}_{$key}";
    $now = time();
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([$now, 1]));
        return true;
    }
    $data = json_decode(file_get_contents($file), true);
    if ($data[0] < $now - $timeWindow) {
        $data = [$now, 1];
        file_put_contents($file, json_encode($data));
        return true;
    }
    if ($data[1] >= $maxRequests) {
        return false;
    }
    $data[1]++;
    file_put_contents($file, json_encode($data));
    return true;
}

/**
 * Sanitize output (already in common.php, but here as a fallback).
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}