 
<?php
/**
 * Student Account Settings
 * Path: /student/settings.php
 */

require_once '../config/database.php';
require_once 'includes/auth.php';
require_once '../includes/functions/validation.php';

$message = '';
$error = '';

// Handle notification preferences update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifications'])) {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $assessmentReminders = isset($_POST['assessment_reminders']) ? 1 : 0;

    // Store preferences in a new column or user meta table (simple approach: add columns to users table)
    // For now, we'll assume columns exist or we create a user_settings table. To keep it simple, we'll use JSON in a new column.
    // If not present, we can store as JSON in a text column.
    $preferences = json_encode([
        'email_notifications' => $emailNotifications,
        'assessment_reminders' => $assessmentReminders
    ]);
    $stmt = $pdo->prepare("UPDATE users SET preferences = ? WHERE user_id = ?");
    $stmt->execute([$preferences, $studentId]);
    $message = "Notification preferences updated.";
}

// Handle account deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_delete'] ?? '';

    // Verify password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$studentId]);
    $user = $stmt->fetch();
    if (!password_verify($password, $user['password'])) {
        $error = 'Incorrect password. Account not deleted.';
    } elseif ($confirm !== 'DELETE') {
        $error = 'Please type DELETE to confirm account deletion.';
    } else {
        // Delete account (cascade should handle related records)
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$studentId]);
        session_destroy();
        header('Location: /index.php?msg=account_deleted');
        exit;
    }
}

// Fetch current preferences (if any)
$preferences = [];
$stmt = $pdo->prepare("SELECT preferences FROM users WHERE user_id = ?");
$stmt->execute([$studentId]);
$prefJson = $stmt->fetchColumn();
if ($prefJson) {
    $preferences = json_decode($prefJson, true);
}
$emailNotifications = $preferences['email_notifications'] ?? 1;
$assessmentReminders = $preferences['assessment_reminders'] ?? 1;

include 'includes/header.php';
?>

<div class="settings-container">
    <div class="page-header">
        <h1><i class="fas fa-cog"></i> Account Settings</h1>
        <p>Manage your preferences and account security</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Notification Settings -->
    <div class="card">
        <h3><i class="fas fa-bell"></i> Notification Preferences</h3>
        <form method="post">
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="email_notifications" value="1" <?= $emailNotifications ? 'checked' : '' ?>>
                    Receive email notifications about course updates
                </label>
            </div>
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="assessment_reminders" value="1" <?= $assessmentReminders ? 'checked' : '' ?>>
                    Send reminders for upcoming assessments
                </label>
            </div>
            <button type="submit" name="update_notifications" class="btn-primary">Save Preferences</button>
        </form>
    </div>

    <!-- Privacy & Security -->
    <div class="card">
        <h3><i class="fas fa-shield-alt"></i> Privacy & Security</h3>
        <p><a href="profile.php">Go to Profile</a> to change your password or update personal information.</p>
        <p><a href="logout.php">Logout</a> from all devices.</p>
    </div>

    <!-- Danger Zone -->
    <div class="card danger">
        <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
        <p>Once you delete your account, all your progress, certificates, and personal data will be permanently removed. This action cannot be undone.</p>
        <details>
            <summary><strong>Delete Account</strong></summary>
            <form method="post" onsubmit="return confirm('Are you absolutely sure? This will erase all your data.');">
                <div class="form-group">
                    <label>Enter your password to confirm</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Type <kbd>DELETE</kbd> to confirm</label>
                    <input type="text" name="confirm_delete" class="form-control" placeholder="DELETE" required>
                </div>
                <button type="submit" name="delete_account" class="btn-danger">Permanently Delete My Account</button>
            </form>
        </details>
    </div>
</div>

<style>
    .settings-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .page-header h1 {
        font-size: 2rem;
        color: #1a5f7a;
    }
    .card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .card.danger {
        border: 1px solid #ffcdd2;
        background: #fff5f5;
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
    .btn-primary {
        background: #2c7da0;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        cursor: pointer;
    }
    .btn-danger {
        background: #f44336;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        cursor: pointer;
    }
    .form-group {
        margin-bottom: 1rem;
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
    details {
        margin-top: 1rem;
    }
    summary {
        cursor: pointer;
        color: #f44336;
        font-weight: 600;
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

<?php include 'includes/footer.php'; ?>