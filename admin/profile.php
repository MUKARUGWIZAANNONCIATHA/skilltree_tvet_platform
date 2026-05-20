<?php
/**
 * Admin Profile Page
 * Path: /admin/profile.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';
require_once '../includes/functions/validation.php';

$adminId = $_SESSION['user_id'];
$message = '';
$error = '';

// Fetch current admin data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin) {
    die('Admin not found.');
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');

        if (empty($fullName)) {
            $error = 'Full name is required.';
        } elseif (!validateAlpha($fullName)) {
            $error = 'Full name must contain only letters and spaces.';
        } elseif (!empty($phone)) {
            $cleanPhone = sanitizePhone($phone);
            if (!validatePhoneNumber($cleanPhone)) {
                $error = 'Invalid phone number. Must be a valid Rwandan number (072/073/078/079 + 7 digits, 10 digits total).';
            } elseif (!isPhoneUnique($pdo, $cleanPhone, $adminId)) {
                $error = 'This phone number is already used by another account.';
            } else {
                $phone = $cleanPhone;
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?");
            $stmt->execute([$fullName, $phone ?: null, $adminId]);
            $_SESSION['user_name'] = $fullName;
            $message = 'Profile updated successfully.';
            // Refresh admin data
            $admin['full_name'] = $fullName;
            $admin['phone'] = $phone;
        }
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($current) || empty($new)) {
            $error = 'Current password and new password are required.';
        } elseif (!password_verify($current, $admin['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New password and confirmation do not match.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed, $adminId]);
            $message = 'Password changed successfully.';
        }
    }

    if (isset($_POST['upload_picture']) && isset($_FILES['profile_picture'])) {
        $file = $_FILES['profile_picture'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        if (in_array($file['type'], $allowed) && $file['size'] < 2097152) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = "admin_{$adminId}_" . time() . ".$ext";
            $uploadPath = "../uploads/profiles/" . $filename;
            if (!is_dir("../uploads/profiles")) mkdir("../uploads/profiles", 0777, true);
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $stmt->execute([$filename, $adminId]);
                $message = 'Profile picture updated.';
                $admin['profile_picture'] = $filename;
            } else {
                $error = 'Failed to upload image.';
            }
        } else {
            $error = 'Only JPG, PNG images under 2MB are allowed.';
        }
    }
}

include_once '../includes/templates/header.php';
?>

<div class="profile-container">
    <div class="profile-header">
        <div class="avatar">
            <?php if (!empty($admin['profile_picture']) && file_exists("../uploads/profiles/" . $admin['profile_picture'])): ?>
                <img src="../uploads/profiles/<?= htmlspecialchars($admin['profile_picture']) ?>" alt="Profile">
            <?php else: ?>
                <i class="fas fa-user-shield"></i>
            <?php endif; ?>
        </div>
        <h2><?= htmlspecialchars($admin['full_name']) ?></h2>
        <p><?= htmlspecialchars($admin['email']) ?></p>
        <p><strong>Role:</strong> Administrator</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>Edit Profile</h3>
        <form method="post">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($admin['full_name']) ?>" required
                       oninput="validateName(this)">
                <small class="text-muted">Only letters and spaces allowed</small>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" disabled>
                <small class="text-muted">Email cannot be changed. Contact IT support.</small>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($admin['phone'] ?? '') ?>"
                       oninput="validatePhone(this)" placeholder="0781234567">
                <small class="text-muted">Rwandan number: 072/073/078/079 + 7 digits</small>
            </div>
            <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
        </form>
    </div>

    <div class="card">
        <h3>Change Password</h3>
        <form method="post">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" name="change_password" class="btn-primary">Change Password</button>
        </form>
    </div>

    <div class="card">
        <h3>Profile Picture</h3>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <input type="file" name="profile_picture" accept="image/jpeg,image/png,image/jpg" required>
            </div>
            <button type="submit" name="upload_picture" class="btn-primary">Upload Picture</button>
        </form>
    </div>
</div>

<style>
    .profile-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .profile-header {
        background: linear-gradient(135deg, #1a5f7a, #0e3a4a);
        border-radius: 1.5rem;
        padding: 2rem;
        color: white;
        text-align: center;
        margin-bottom: 2rem;
    }
    .avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: #eef2fa;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        overflow: hidden;
    }
    .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .avatar i {
        font-size: 3rem;
        color: #2c7da0;
    }
    .card {
        background: white;
        border-radius: 1.2rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
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
        padding: 0.6rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.8rem;
    }
    .btn-primary {
        background: #2c7da0;
        border: none;
        border-radius: 2rem;
        padding: 0.5rem 1.2rem;
        color: white;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-primary:hover {
        background: #1e5f7a;
        transform: scale(1.02);
    }
    .alert {
        padding: 0.8rem;
        border-radius: 0.8rem;
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
    .text-muted {
        font-size: 0.75rem;
        color: #6c8faa;
        margin-top: 0.2rem;
        display: block;
    }
</style>

<script>
    function validateName(input) {
        input.value = input.value.replace(/[^A-Za-z\s]/g, '');
    }
    function validatePhone(input) {
        let val = input.value.replace(/\D/g, '');
        if (val.length > 10) val = val.slice(0, 10);
        input.value = val;
    }
</script>

<?php include_once '../includes/templates/footer.php'; ?>