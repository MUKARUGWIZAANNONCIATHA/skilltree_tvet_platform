<?php
/**
 * Registration Page - Students & Teachers only
 * Path: /includes/auth/register.php
 * Added: Rwanda phone validation (072/073/078/079 + 7 digits) and uniqueness check.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../functions/common.php';

if (isset($_SESSION['user_id'])) {
    redirectToDashboard($_SESSION['user_role']);
}

// Helper functions for phone validation
function isValidRwandaPhone($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) !== 10) return false;
    $prefix = substr($phone, 0, 3);
    return in_array($prefix, ['072', '073', '078', '079']);
}

function isPhoneRegistered($pdo, $phone) {
    $phone = preg_replace('/\D/', '', $phone);
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    return $stmt->fetch() !== false;
}

$error = '';
$success = '';

$sectors = getRows("SELECT * FROM sectors WHERE status = 'active' ORDER BY sector_name");
$levels = getRows("SELECT * FROM levels ORDER BY level_number");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'student');
    $phone = sanitize($_POST['phone'] ?? '');
    $sector = sanitize($_POST['sector'] ?? '');
    $trade = sanitize($_POST['trade'] ?? '');
    $rqf_level = intval($_POST['rqf_level'] ?? 0);

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check email uniqueness
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered. Please login.';
        } else {
            // Phone validation
            if (!empty($phone)) {
                $cleanPhone = preg_replace('/\D/', '', $phone);
                if (!isValidRwandaPhone($cleanPhone)) {
                    $error = 'Invalid phone number. Must be a valid Rwandan number starting with 072, 073, 078, or 079 (10 digits total).';
                } elseif (isPhoneRegistered($pdo, $cleanPhone)) {
                    $error = 'This phone number is already registered. Please use another.';
                } else {
                    $phone = $cleanPhone;
                }
            }

            if (!$error) {
                $hashed = hashPassword($password);
                $isApproved = ($role === 'student') ? 1 : 0;
                $userData = [
                    'email' => $email,
                    'password' => $hashed,
                    'full_name' => $full_name,
                    'role' => $role,
                    'phone' => $phone ?: null,
                    'is_approved' => $isApproved,
                    'is_verified' => 1
                ];
                if ($role === 'student') {
                    $userData['sector'] = $sector;
                    $userData['trade'] = $trade;
                    $userData['rqf_level'] = $rqf_level;
                } elseif ($role === 'teacher') {
                    $userData['is_approved'] = 0;
                }
                $userId = insert('users', $userData);
                if ($userId) {
                    if ($role === 'teacher') {
                        $employeeId = 'TCH' . time();
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
                        $stmt = $pdo->prepare("INSERT IGNORE INTO teachers (user_id, employee_id, department) VALUES (?, ?, ?)");
                        $stmt->execute([$userId, $employeeId, $sector]);
                    }
                    $success = 'Registration successful! ' . 
                              ($role === 'teacher' ? 'Your account is pending admin approval.' : 'You can now login.');
                    $_POST = [];
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

include_once '../templates/header.php';
?>

<div class="auth-container">
    <div class="auth-card register-card">
        <div class="auth-header">
            <h2>Create Account</h2>
            <p>Join SkillTree TVET Platform</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" class="form-control" required 
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                           oninput="validateName(this)"
                           placeholder="Only letters and spaces allowed">
                    <small class="text-muted">Only letters and spaces</small>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label>Phone (Optional)</label>
                <input type="tel" name="phone" class="form-control" 
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                       oninput="validatePhone(this)"
                       placeholder="e.g., 0781234567">
                <small class="text-muted">Rwandan number: 072/073/078/079 + 7 digits (10 total)</small>
            </div>

            <div class="form-group">
                <label>I am a *</label>
                <select name="role" id="role" class="form-control" required onchange="toggleRoleFields()">
                    <option value="student" <?= (($_POST['role'] ?? '') === 'student') ? 'selected' : '' ?>>Student</option>
                    <option value="teacher" <?= (($_POST['role'] ?? '') === 'teacher') ? 'selected' : '' ?>>Teacher</option>
                </select>
            </div>

            <!-- Student Fields -->
            <div id="student-fields" class="role-fields">
                <div class="form-row">
                    <div class="form-group">
                        <label>Sector</label>
                        <select name="sector" id="sector" class="form-control" onchange="loadTrades()">
                            <option value="">Select Sector</option>
                            <?php foreach ($sectors as $s): ?>
                                <option value="<?= htmlspecialchars($s['sector_name']) ?>"
                                    <?= (($_POST['sector'] ?? '') === $s['sector_name']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['sector_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Trade</label>
                        <select name="trade" id="trade" class="form-control">
                            <option value="">Select Sector first</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>RQF Level</label>
                    <select name="rqf_level" class="form-control">
                        <option value="">Select Level</option>
                        <?php foreach ($levels as $l): ?>
                            <option value="<?= $l['level_number'] ?>" <?= (($_POST['rqf_level'] ?? '') == $l['level_number']) ? 'selected' : '' ?>>
                                Level <?= $l['level_number'] ?> - <?= htmlspecialchars($l['level_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Teacher Fields -->
            <div id="teacher-fields" class="role-fields" style="display:none;">
                <div class="form-group">
                    <label>Department / Sector</label>
                    <input type="text" name="sector" class="form-control" 
                           value="<?= htmlspecialchars($_POST['sector'] ?? '') ?>"
                           placeholder="e.g., ICT, Agriculture, Business">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>

        <div class="auth-divider">
            <span>OR</span>
        </div>

        <a href="/includes/auth/google-login.php" class="btn-google">
            <i class="fab fa-google"></i> Continue with Google
        </a>

        <div class="auth-footer">
            <p>Already have an account? <a href="/includes/auth/login.php">Login here</a></p>
            <p>Are you a company or employer? <a href="/company/register.php">Register as Company</a></p>
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }

    .auth-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }

    .auth-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        padding: 40px;
        width: 100%;
        max-width: 750px;
    }

    .auth-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .auth-header h2 {
        color: #1e3a5f;
        font-size: 28px;
        margin-bottom: 8px;
    }

    .auth-header p {
        color: #666;
        font-size: 14px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 5px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }

    .role-fields {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 14px;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    }

    .btn-block {
        display: block;
        width: 100%;
    }

    .btn-google {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: white;
        border: 1px solid #ddd;
        padding: 12px;
        border-radius: 10px;
        text-decoration: none;
        color: #333;
        font-weight: 500;
        transition: all 0.3s;
        margin-top: 20px;
    }

    .btn-google:hover {
        background: #f5f5f5;
        border-color: #667eea;
    }

    .auth-divider {
        text-align: center;
        margin: 25px 0;
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
        margin-top: 25px;
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
        border-radius: 10px;
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

    .text-muted {
        font-size: 12px;
        color: #666;
        margin-top: 4px;
        display: block;
    }

    @media (max-width: 600px) {
        .auth-card {
            padding: 25px;
        }
        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }
        .auth-header h2 {
            font-size: 24px;
        }
    }
</style>

<script>
    function toggleRoleFields() {
        const role = document.getElementById('role').value;
        const studentFields = document.getElementById('student-fields');
        const teacherFields = document.getElementById('teacher-fields');
        if (studentFields) studentFields.style.display = role === 'student' ? 'block' : 'none';
        if (teacherFields) teacherFields.style.display = role === 'teacher' ? 'block' : 'none';
    }

    function loadTrades() {
        const sectorSelect = document.getElementById('sector');
        const tradeSelect = document.getElementById('trade');
        const sector = sectorSelect.value;
        if (!sector || !tradeSelect) return;

        tradeSelect.innerHTML = '<option value="">Loading trades...</option>';

        fetch(`/api/get-trades.php?sector=${encodeURIComponent(sector)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.trades && data.trades.length > 0) {
                    let options = '<option value="">Select Trade</option>';
                    data.trades.forEach(t => {
                        options += `<option value="${escapeHtml(t.trade_name)}">${escapeHtml(t.trade_name)}</option>`;
                    });
                    tradeSelect.innerHTML = options;
                } else {
                    tradeSelect.innerHTML = '<option value="">No trades found for this sector</option>';
                }
            })
            .catch(err => {
                console.error('Error loading trades:', err);
                tradeSelect.innerHTML = '<option value="">Error loading trades. Please refresh.</option>';
            });
    }

    function validateName(input) {
        // Allow only letters and spaces
        input.value = input.value.replace(/[^A-Za-z\s]/g, '');
    }

    function validatePhone(input) {
        // Remove all non-digits
        let val = input.value.replace(/\D/g, '');
        // Limit to 10 digits
        if (val.length > 10) val = val.slice(0, 10);
        input.value = val;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        toggleRoleFields();
        const sector = document.getElementById('sector');
        if (sector && sector.value) loadTrades();
    });
</script>

<?php include_once '../templates/footer.php'; ?>