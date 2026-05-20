<?php
/**
 * Google Login Redirect
 * Path: /includes/auth/google-login.php
 */

require_once '../../config/config.php';
require_once '../../config/google-oauth.php';

// Build Google OAuth URL
$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'offline',
    'prompt' => 'consent'
]);

header('Location: ' . $authUrl);
exit();
?>