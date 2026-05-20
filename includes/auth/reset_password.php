<?php
/**
 * Reset Password Page (After clicking email link)
 * Path: /includes/auth/reset-password.php
 */

session_start();
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../functions/common.php';

$error = '';
$success = '';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: /includes/auth/login.php?error=invalid_token');
    exit();
}

// Verify token
$stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = 'Invalid or expired reset link. Please request a new one.';
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($password)) {
            $error = 'Please enter a password';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } else {
            // Update password
            $hashedPassword = hashPassword($password);
            $updateStmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
            $updateStmt->execute([$hashedPassword, $user['user_id']]);
            
            $success = 'Password has been reset successfully. You can now login.';
            
            // Redirect after 3 seconds
            header('refresh:3;url=/includes/auth/login.php');
        }
    }
}

include_once '../templates/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Reset Your Password</h2>
            <p>Create a new password for <?php echo htmlspecialchars($user['full_name'] ?? ''); ?></p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>
            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" class="form-control" required 
                           placeholder="Enter new password">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                           placeholder="Confirm new password">
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>