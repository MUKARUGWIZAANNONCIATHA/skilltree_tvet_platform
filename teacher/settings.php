<?php
/**
 * Teacher Settings - Account preferences and security
 * Path: /teacher/settings.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';
require_once '../includes/functions/validation.php';

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Fetch current teacher data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$teacher = $stmt->fetch();

// Fetch preferences (if stored as JSON in a column; if not, we'll use a separate table or ignore)
// For simplicity, we'll store preferences in a 'preferences' JSON column (assuming it exists)
// If not, we can skip or add the column.

$preferences = [];
if (!empty($teacher['preferences'])) {
    $preferences = json_decode($teacher['preferences'], true);
}
$emailNotifications = $preferences['email_notifications'] ?? 1;
$assessmentReminders = $preferences['assessment_reminders'] ?? 1;
$defaultDifficulty = $preferences['default_difficulty'] ?? 'medium';

// Handle update preferences
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_preferences'])) {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $assessmentReminders = isset($_POST['assessment_reminders']) ? 1 : 0;
    $defaultDifficulty = $_POST['default_difficulty'] ?? 'medium';

    $prefs = json_encode([
        'email_notifications' => $emailNotifications,
        'assessment_reminders' => $assessmentReminders,
        'default_difficulty' => $defaultDifficulty
    ]);

    // Check if preferences column exists, if not, add it (or use a separate table)
    try {
        $stmt = $pdo->prepare("UPDATE users SET preferences = ? WHERE user_id = ?");
        $stmt->execute([$prefs, $userId]);
        $message = "Preferences updated successfully.";
    } catch (PDOException $e) {
        // If column doesn't exist, we skip or create it; for now, show error but don't break
        $error = "Preferences column missing. Please contact administrator.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new)) {
        $error = "Current password and new password are required.";
    } elseif (!password_verify($current, $teacher['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $error = "New password and confirmation do not match.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashed, $userId]);
        $message = "Password changed successfully.";
    }
}

include_once '../includes/templates/header.php';
?>

<div class="settings-container">
    <div class="page-header">
        <h1><i class="fas fa-cog"></i> Teacher Settings</h1>
        <p>Manage your account preferences and security</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- Notification Preferences -->
        <div class="card">
            <h3><i class="fas fa-bell"></i> Notification Preferences</h3>
            <form method="post">
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="email_notifications" value="1" <?= $emailNotifications ? 'checked' : '' ?>>
                        Receive email notifications for new student submissions
                    </label>
                </div>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="assessment_reminders" value="1" <?= $assessmentReminders ? 'checked' : '' ?>>
                        Send reminders for pending assessments
                    </label>
                </div>
                <div class="form-group">
                    <label>Default Difficulty for AI-generated questions</label>
                    <select name="default_difficulty" class="form-control">
                        <option value="easy" <?= $defaultDifficulty == 'easy' ? 'selected' : '' ?>>Easy</option>
                        <option value="medium" <?= $defaultDifficulty == 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="hard" <?= $defaultDifficulty == 'hard' ? 'selected' : '' ?>>Hard</option>
                    </select>
                </div>
                <button type="submit" name="update_preferences" class="btn-primary">Save Preferences</button>
            </form>
        </div>

        <!-- Security / Password Change -->
        <div class="card">
            <h3><i class="fas fa-lock"></i> Change Password</h3>
            <form method="post">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                    <small>Minimum 6 characters</small>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" name="change_password" class="btn-primary">Change Password</button>
            </form>
        </div>

        <!-- Profile & Account -->
        <div class="card">
            <h3><i class="fas fa-user"></i> Profile Information</h3>
            <p><strong>Name:</strong> <?= htmlspecialchars($teacher['full_name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($teacher['email']) ?></p>
            <p><strong>Role:</strong> <?= ucfirst($teacher['role']) ?></p>
            <a href="profile.php" class="btn-secondary">Edit Profile</a>
            <a href="logout.php" class="btn-secondary logout">Logout</a>
        </div>
    </div>
</div>

<style>
    .settings-container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    .card {
        background: white;
        border-radius: 1rem;
        padding: 1.2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .card h3 {
        margin-bottom: 1rem;
        font-size: 1.2rem;
        border-bottom: 1px solid #eef2f8;
        padding-bottom: 0.5rem;
    }
    .checkbox-group {
        margin-bottom: 0.8rem;
    }
    .checkbox-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }
    .form-group {
        margin-bottom: 0.8rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.3rem;
        font-weight: 500;
    }
    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
    }
    .btn-primary {
        background: #2c7da0;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        cursor: pointer;
        margin-top: 0.5rem;
    }
    .btn-secondary {
        background: #eef2fa;
        color: #1a5f7a;
        border: none;
        padding: 0.3rem 0.8rem;
        border-radius: 1.5rem;
        text-decoration: none;
        display: inline-block;
        margin-right: 0.5rem;
        font-size: 0.8rem;
    }
    .logout {
        background: #ffebee;
        color: #c62828;
    }
    .alert {
        padding: 0.8rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .alert-error {
        background: #ffebee;
        color: #c62828;
    }
</style>

<?php include_once '../includes/templates/footer.php'; ?>