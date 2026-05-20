<?php
/**
 * Google OAuth Callback Handler
 * Path: /includes/auth/google-callback.php
 */

session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/google-oauth.php';
require_once '../functions/common.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    header('Location: /includes/auth/login.php?error=google_auth_failed');
    exit();
}

// Exchange code for access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);

if (!isset($tokenData['access_token'])) {
    header('Location: /includes/auth/login.php?error=google_token_failed');
    exit();
}

// Get user info from Google
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$googleUser = json_decode($response, true);

if (!isset($googleUser['email'])) {
    header('Location: /includes/auth/login.php?error=google_userinfo_failed');
    exit();
}

// Check if user exists in database
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$googleUser['email']]);
$user = $stmt->fetch();

if (!$user) {
    // Create new user
    $userData = [
        'email' => $googleUser['email'],
        'full_name' => $googleUser['name'],
        'password' => hashPassword(generateSecureToken(16)),
        'role' => 'student', // Default role
        'is_approved' => 1,
        'is_active' => 1
    ];
    
    $userId = insert('users', $userData);
    
    if ($userId) {
        $user = $userData;
        $user['user_id'] = $userId;
        $user['role'] = 'student';
    } else {
        header('Location: /includes/auth/register.php?error=google_signup_failed');
        exit();
    }
}

// Login the user
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['login_time'] = time();

// Update last login
$updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
$updateStmt->execute([$user['user_id']]);

// Log activity
logActivity($user['user_id'], 'google_login', 'User logged in with Google');

redirectToDashboard($user['role']);
?>