<?php
// company/register.php – with full validation (name, phone, email)
session_start();
require_once '../config/database.php';
require_once '../includes/functions/common.php';

// ========== VALIDATION FUNCTIONS ==========
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateCompanyName($name) {
    // Allow letters, numbers, spaces, dots, hyphens, ampersands, apostrophes, parentheses
    return preg_match('/^[A-Za-z0-9\s\.\-\&\'\(\)]+$/', $name) === 1;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isEmailUnique($pdo, $email) {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->rowCount() == 0;
}

function validatePhoneNumber($phone) {
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) !== 10) return false;
    $prefix = substr($phone, 0, 3);
    return in_array($prefix, ['072', '073', '078', '079']);
}

function isPhoneUnique($pdo, $phone) {
    $phone = preg_replace('/\D/', '', $phone);
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE contact_phone = ?");
    $stmt->execute([$phone]);
    return $stmt->rowCount() == 0;
}

function isValidUrl($url) {
    return empty($url) || filter_var($url, FILTER_VALIDATE_URL);
}
// =========================================

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = sanitizeInput($_POST['company_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $description = sanitizeInput($_POST['description'] ?? '');
    $contactEmail = sanitizeInput($_POST['contact_email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $website = sanitizeInput($_POST['website'] ?? '');
    $industry = sanitizeInput($_POST['industry'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');

    // Validation
    if (empty($companyName) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!validateCompanyName($companyName)) {
        $error = 'Company name can only contain letters, numbers, spaces, dots, hyphens, ampersands, apostrophes, and parentheses.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!isEmailUnique($pdo, $email)) {
        $error = 'Email already registered. Please login.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!empty($contactEmail) && !validateEmail($contactEmail)) {
        $error = 'Please enter a valid contact email.';
    } elseif (!empty($phone)) {
        if (!validatePhoneNumber($phone)) {
            $error = 'Invalid phone number. Must be a valid Rwandan number (072/073/078/079 + 7 digits, 10 digits total).';
        } elseif (!isPhoneUnique($pdo, $phone)) {
            $error = 'This phone number is already registered.';
        }
    } elseif (!isValidUrl($website)) {
        $error = 'Please enter a valid website URL (https://...).';
    } else {
        // Clean phone number (store only digits)
        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : null;
        $pdo->beginTransaction();
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $userStmt = $pdo->prepare("
                INSERT INTO users (email, password, full_name, role, company_name, industry, location, website, contact_phone, is_approved, created_at)
                VALUES (?, ?, ?, 'company', ?, ?, ?, ?, ?, 0, NOW())
            ");
            $userStmt->execute([$email, $hashed, $companyName, $companyName, $industry, $location, $website, $cleanPhone]);
            $userId = $pdo->lastInsertId();

            // Insert into companies table (additional details)
            $companyStmt = $pdo->prepare("
                INSERT INTO companies (company_name, description, contact_email, phone, website, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $companyStmt->execute([$companyName, $description, $contactEmail, $cleanPhone, $website]);

            $pdo->commit();
            $success = "Registration successful! Your account is pending admin approval. You will be notified once approved.";
            $_POST = [];
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Registration failed: ' . $e->getMessage();
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Registration – SkillTree TVET</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* (your existing CSS – unchanged) */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:40px 20px; }
        .auth-container { width:100%; display:flex; justify-content:center; }
        .auth-card { background:white; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,0.3); padding:40px; width:100%; max-width:750px; }
        .auth-header { text-align:center; margin-bottom:30px; }
        .auth-header h2 { color:#1e3a5f; font-size:28px; margin-bottom:8px; }
        .auth-header p { color:#666; font-size:14px; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:5px; }
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; margin-bottom:8px; font-weight:500; color:#333; font-size:14px; }
        .form-control { width:100%; padding:12px 15px; border:1px solid #ddd; border-radius:10px; font-size:14px; transition:0.3s; }
        .form-control:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,0.1); }
        textarea.form-control { resize:vertical; }
        .btn-primary { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:white; border:none; padding:14px; border-radius:10px; font-size:16px; font-weight:600; cursor:pointer; width:100%; transition:0.3s; }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(102,126,234,0.4); }
        .auth-divider { text-align:center; margin:25px 0; position:relative; }
        .auth-divider::before { content:''; position:absolute; top:50%; left:0; right:0; height:1px; background:#ddd; }
        .auth-divider span { background:white; padding:0 15px; position:relative; color:#999; font-size:14px; }
        .auth-footer { text-align:center; margin-top:25px; padding-top:20px; border-top:1px solid #eee; }
        .auth-footer a { color:#667eea; text-decoration:none; font-weight:500; }
        .alert { padding:12px 15px; border-radius:10px; margin-bottom:20px; font-size:14px; }
        .alert-error { background:#fee; color:#c00; border:1px solid #fcc; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .text-muted { font-size:12px; color:#666; margin-top:4px; display:block; }
        @media (max-width:600px) { .auth-card { padding:25px; } .form-row { grid-template-columns:1fr; gap:0; } .auth-header h2 { font-size:24px; } }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Company Registration</h2>
            <p>Post internships and find talented students</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!$success): ?>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Company Name *</label>
                    <input type="text" name="company_name" class="form-control" required 
                           value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>"
                           oninput="validateCompanyName(this)"
                           placeholder="e.g., MTN Rwanda Ltd">
                    <small class="text-muted">Letters, numbers, spaces, ., -, &, ' ( ) allowed</small>
                </div>
                <div class="form-group">
                    <label>Email (login) *</label>
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
            <div class="form-row">
                <div class="form-group">
                    <label>Industry</label>
                    <input type="text" name="industry" class="form-control" 
                           value="<?= htmlspecialchars($_POST['industry'] ?? '') ?>"
                           oninput="validateIndustry(this)">
                    <small class="text-muted">Letters and spaces only</small>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" class="form-control" 
                           value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Website</label>
                    <input type="url" name="website" class="form-control" 
                           value="<?= htmlspecialchars($_POST['website'] ?? '') ?>"
                           placeholder="https://example.com">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                           oninput="validatePhone(this)"
                           placeholder="0781234567">
                    <small class="text-muted">Rwandan number: 072/073/078/079 + 7 digits</small>
                </div>
            </div>
            <div class="form-group">
                <label>Contact Email (for applicants)</label>
                <input type="email" name="contact_email" class="form-control" 
                       value="<?= htmlspecialchars($_POST['contact_email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Company Description</label>
                <textarea name="description" rows="4" class="form-control" 
                          placeholder="Tell us about your company..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn-primary"><i class="fas fa-building"></i> Register Company</button>
        </form>
        <div class="auth-divider"><span>OR</span></div>
        <div class="auth-footer">
            <p>Already have a company account? <a href="/includes/auth/login.php">Login here</a></p>
            <p>Are you a student or teacher? <a href="/includes/auth/register.php">Register here</a></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function validateCompanyName(input) {
        // Allow letters, numbers, spaces, ., -, &, ', (, )
        input.value = input.value.replace(/[^A-Za-z0-9\s\.\-\&\'\(\)]/g, '');
    }
    function validateIndustry(input) {
        // Allow letters and spaces only
        input.value = input.value.replace(/[^A-Za-z\s]/g, '');
    }
    function validatePhone(input) {
        // Allow only digits, max 10
        let val = input.value.replace(/\D/g, '');
        if (val.length > 10) val = val.slice(0, 10);
        input.value = val;
    }
</script>

</body>
</html>