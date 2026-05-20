<?php
/**
 * Login Page
 * Path: /includes/auth/login.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../functions/common.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirectToDashboard($_SESSION['user_role']);
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check login attempts
        if (!checkLoginAttempts($email)) {
            $error = 'Too many failed attempts. Please try again later.';
        } else {
            // Get user from database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();

                // Update last login
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW(), login_token = ? WHERE user_id = ?");
                $token = generateSecureToken();
                $updateStmt->execute([$token, $user['user_id']]);

                // Set remember me cookie
                if ($remember) {
                    setcookie('remember_token', $token, time() + (86400 * 30), '/');
                }

                // Log activity
                logActivity($user['user_id'], 'login', 'User logged in successfully');

                resetLoginAttempts($email);
                redirectToDashboard($user['role']);
            } else {
                $error = 'Invalid email or password';
                recordLoginAttempt($email);
            }
        }
    }
}

include_once '../templates/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Welcome Back</h2>
            <p>Login to continue your learning journey</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="auth-form">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required 
                       placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" class="form-control" required 
                       placeholder="Enter your password">
                <span class="toggle-password" onclick="togglePassword()">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="remember"> Remember Me
                </label>
                <a href="/includes/auth/password-reset.php" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="auth-divider">
            <span>OR</span>
        </div>

        <!-- Google Login Button -->
        <a href="/includes/auth/google-login.php" class="btn-google">
            <i class="fab fa-google"></i> Continue with Google
        </a>

        <div class="auth-footer">
            <p>Don't have an account? <a href="/includes/auth/register.php">Register here</a></p>
        </div>
    </div>
</div>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 20px;
}

.auth-card {
    background: white;
    max-width: 450px;
    width: 100%;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-header h2 {
    color: #1e3a5f;
    font-size: 28px;
    margin-bottom: 5px;
}

.auth-header p {
    color: #666;
    font-size: 14px;
}

.form-group {
    margin-bottom: 20px;
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s;
}

.form-control:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}

.checkbox-group {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
}

.checkbox-group a {
    color: #667eea;
    text-decoration: none;
}

.checkbox-group a:hover {
    text-decoration: underline;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    width: 100%;
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102,126,234,0.4);
}

.btn-google {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: white;
    border: 1px solid #ddd;
    padding: 12px;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-google:hover {
    background: #f5f5f5;
    border-color: #667eea;
}

.auth-divider {
    text-align: center;
    margin: 20px 0;
    position: relative;
}

.auth-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #ddd;
}

.auth-divider span {
    background: white;
    padding: 0 15px;
    position: relative;
    color: #999;
    font-size: 14px;
}

.auth-footer {
    text-align: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.auth-footer a {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.auth-footer a:hover {
    text-decoration: underline;
}

.alert {
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-error {
    background: #fee;
    color: #c00;
    border: 1px solid #fcc;
}

.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 40px;
    cursor: pointer;
    color: #999;
}

@media (max-width: 480px) {
    .auth-card {
        padding: 25px;
    }
    .auth-header h2 {
        font-size: 24px;
    }
}
</style>

<script>
function togglePassword() {
    const password = document.getElementById('password');
    const type = password.type === 'password' ? 'text' : 'password';
    password.type = type;
}
</script>

<?php include_once '../templates/footer.php'; ?>