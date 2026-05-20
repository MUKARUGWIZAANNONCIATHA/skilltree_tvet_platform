<?php
/**
 * Validation and Sanitization Helpers
 * Path: /includes/functions/validation.php
 */

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ========== SANITIZATION (client-side & server-side compatible) ==========
function sanitizePhone($phone) {
    return preg_replace('/\D/', '', $phone);
}

// ========== VALIDATION RULES ==========
function validateAlpha($str) {
    return preg_match('/^[A-Za-z\s]+$/', $str) === 1;
}

function validateAlphaNumericSpace($str) {
    return preg_match('/^[A-Za-z0-9\s]+$/', $str) === 1;
}

function validateCompanyName($str) {
    return preg_match('/^[A-Za-z0-9\s\.\-\&\'\(\)]+$/', $str) === 1;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isEmailUnique($pdo, $email, $excludeUserId = null) {
    $sql = "SELECT user_id FROM users WHERE email = ?";
    $params = [$email];
    if ($excludeUserId) {
        $sql .= " AND user_id != ?";
        $params[] = $excludeUserId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount() === 0;
}

function validatePhoneNumber($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) !== 10) return false;
    $prefix = substr($phone, 0, 3);
    return in_array($prefix, ['072', '073', '078', '079']);
}

function isPhoneUnique($pdo, $phone, $excludeUserId = null) {
    $phone = preg_replace('/\D/', '', $phone);
    $sql = "SELECT user_id FROM users WHERE contact_phone = ?";
    $params = [$phone];
    if ($excludeUserId) {
        $sql .= " AND user_id != ?";
        $params[] = $excludeUserId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount() === 0;
}

function validateUrl($url) {
    return empty($url) || filter_var($url, FILTER_VALIDATE_URL);
}

function validatePasswordLength($password) {
    return strlen($password) >= 6;
}
?>