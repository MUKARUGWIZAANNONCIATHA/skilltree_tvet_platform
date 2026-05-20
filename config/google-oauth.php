<?php
/**
 * Google OAuth Configuration
 * Path: /config/google-oauth.php
 * 
 * INSTRUCTIONS:
 * 1. Go to https://console.cloud.google.com/
 * 2. Create a new project
 * 3. Enable Google+ API
 * 4. Create OAuth 2.0 Client ID
 * 5. Authorized redirect URI: http://localhost/includes/auth/google-callback.php
 * 6. Copy Client ID and Client Secret here
 */

define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', APP_URL . '/includes/auth/google-callback.php');

// Scopes required
define('GOOGLE_SCOPES', 'email profile');
?>