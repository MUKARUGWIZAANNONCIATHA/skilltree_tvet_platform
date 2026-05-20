<?php
/**
 * Teacher Profile Page
 * Path: /teacher/profile.php
 */
require_once '../config/database.php';
require_once '../includes/auth/session-check.php';
requireRole(['teacher']);
require_once '../includes/functions/validation.php';

$teacherId = $_SESSION['user_id'];
$message = '';
$error = '';

// Auto-create teachers table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS teachers (
        teacher_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        employee_id VARCHAR(50),
        department VARCHAR(100),
        qualification TEXT,
        specialization VARCHAR(255),
        bio TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {}

// Fetch current user data (JOIN users + teachers)
$stmt = $pdo->prepare("SELECT u.*, t.employee_id, t.department, t.qualification, t.specialization, t.bio, t.teacher_id FROM users u LEFT JOIN teachers t ON u.user_id = t.user_id WHERE u.user_id = ?");
$stmt->execute([$teacherId]);
$user = $stmt->fetch();

// Ensure teachers row exists (for legacy users)
if ($user && !$user['teacher_id']) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO teachers (user_id, employee_id, department) VALUES (?, ?, ?)");
    $stmt->execute([$teacherId, $user['employee_id'] ?? ('TCH' . time()), $user['department'] ?? '']);
    $user['teacher_id'] = $pdo->lastInsertId();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullName = trim($_POST['full_name']);
        $phone = trim($_POST['phone']);
        $department = trim($_POST['department']);

        // Validation
        if (empty($fullName)) {
            $error = "Full name is required.";
        } elseif (!validateAlpha($fullName)) {
            $error = "Full name must contain only letters and spaces.";
        } elseif (!empty($phone)) {
            $cleanPhone = sanitizePhone($phone);
            if (!validatePhoneNumber($cleanPhone)) {
                $error = "Invalid phone number. Must be a valid Rwandan number (072/073/078/079 + 7 digits).";
            } elseif (!isPhoneUnique($pdo, $cleanPhone, $teacherId)) {
                $error = "This phone number is already used by another account.";
            } else {
                $phone = $cleanPhone;
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?");
            $stmt->execute([$fullName, $phone, $teacherId]);
            $stmt = $pdo->prepare("UPDATE teachers SET department = ? WHERE user_id = ?");
            $stmt->execute([$department, $teacherId]);
            $_SESSION['user_name'] = $fullName;
            $message = "✅ Profile updated successfully!";
            // Refresh user data
            $user['full_name'] = $fullName;
            $user['phone'] = $phone;
            $user['department'] = $department;
        }
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$teacherId]);
        $userRow = $stmt->fetch();

        if (password_verify($current, $userRow['password'])) {
            if ($new === $confirm && validatePasswordLength($new)) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed, $teacherId]);
                $message = "✅ Password changed successfully!";
            } else {
                $error = "New password must be at least 6 characters and match confirmation.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }

    if (isset($_POST['upload_picture']) && isset($_FILES['profile_picture'])) {
        $file = $_FILES['profile_picture'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        if (in_array($file['type'], $allowed) && $file['size'] < 2097152) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = "teacher_{$teacherId}_" . time() . ".$ext";
            $uploadPath = "../uploads/profiles/" . $filename;
            if (!is_dir("../uploads/profiles")) mkdir("../uploads/profiles", 0777, true);
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $stmt->execute([$filename, $teacherId]);
                $message = "✅ Profile picture updated!";
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Only JPG, PNG images under 2MB are allowed.";
        }
    }
}
?>
<?php include '../includes/templates/header.php'; ?>

<style>
    .profile-container {
        max-width: 800px;
        margin: 0 auto;
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
    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 0.8rem;
        border-radius: 0.8rem;
        margin-bottom: 1rem;
    }
    .alert-error {
        background: #ffebee;
        color: #c62828;
        padding: 0.8rem;
        border-radius: 0.8rem;
        margin-bottom: 1rem;
    }
    .text-muted {
        font-size: 0.8rem;
        color: #6c8faa;
        margin-top: 0.2rem;
        display: block;
    }
</style>

<div class="profile-container">
    <div class="profile-header">
        <div class="avatar">
            <?php if (!empty($user['profile_picture']) && file_exists("../uploads/profiles/" . $user['profile_picture'])): ?>
                <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile">
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
        </div>
        <h2><?= htmlspecialchars($user['full_name']) ?></h2>
        <p><?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Role:</strong> <?= ucfirst($user['role']) ?></p>
    </div>

    <?php if ($message): ?>
        <div class="alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>Edit Profile</h3>
        <form method="post">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" 
                       value="<?= htmlspecialchars($user['full_name']) ?>" required
                       oninput="validateName(this)">
                <small class="text-muted">Only letters and spaces allowed</small>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                <small class="text-muted">Email cannot be changed. Contact support.</small>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="tel" name="phone" class="form-control" 
                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                       oninput="validatePhone(this)"
                       placeholder="0781234567">
                <small class="text-muted">Rwandan number: 072/073/078/079 + 7 digits (10 total)</small>
            </div>
            <div class="form-group">
                <label>Department / Sector</label>
                <input type="text" name="department" class="form-control" 
                       value="<?= htmlspecialchars($user['department'] ?? $user['sector'] ?? '') ?>"
                       oninput="validateDepartment(this)">
                <small class="text-muted">Letters and spaces only</small>
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

<script>
    function validateName(input) {
        // Allow only letters and spaces
        input.value = input.value.replace(/[^A-Za-z\s]/g, '');
    }
    function validatePhone(input) {
        // Allow only digits, max 10
        let val = input.value.replace(/\D/g, '');
        if (val.length > 10) val = val.slice(0, 10);
        input.value = val;
    }
    function validateDepartment(input) {
        // Allow only letters, spaces, and commas
        input.value = input.value.replace(/[^A-Za-z\s,]/g, '');
    }
</script>

<?php include '../includes/templates/footer.php'; ?>