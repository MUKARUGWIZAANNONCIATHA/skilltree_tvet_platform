<?php
require_once 'auth.php';
require_once '../includes/functions/validation.php';  // Include validation
include '../includes/templates/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = trim($_POST['company_name'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Validation
    if (empty($companyName)) {
        $error = 'Company name is required.';
    } elseif (!validateCompanyName($companyName)) {
        $error = 'Company name can only contain letters, numbers, spaces, dots, hyphens, ampersands, apostrophes, and parentheses.';
    } elseif (!empty($website) && !validateUrl($website)) {
        $error = 'Please enter a valid website URL (e.g., https://example.com).';
    } elseif (!empty($phone)) {
        $cleanPhone = sanitizePhone($phone);
        if (!validatePhoneNumber($cleanPhone)) {
            $error = 'Invalid phone number. Must be a valid Rwandan number (072/073/078/079 + 7 digits, 10 digits total).';
        } elseif (!isPhoneUnique($pdo, $cleanPhone, $companyId)) {
            $error = 'This phone number is already registered by another company.';
        } else {
            $phone = $cleanPhone;
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare("UPDATE users SET company_name=?, industry=?, location=?, website=?, contact_phone=? WHERE user_id=?");
        $stmt->execute([$companyName, $industry, $location, $website, $phone, $companyId]);
        $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Profile updated successfully!</div>';
        // Update the shown values to reflect the new input (or keep as is)
        $companyName = $companyName;
        $companyIndustry = $industry;
        $companyLocation = $location;
        $companyWebsite = $website;
        $companyPhone = $phone;
    } else {
        // Keep the submitted values in the form fields for correction
        // (they are already in the POST variables)
    }
}
?>

<div class="form-page-wrapper">
    <div class="form-card-modern">
        <div class="form-header">
            <i class="fas fa-building"></i>
            <h1>Company Profile</h1>
            <p>Update your company information</p>
        </div>

        <?php if ($message): ?>
            <?= $message ?>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="modern-form">
            <div class="form-group">
                <label><i class="fas fa-building"></i> Company Name</label>
                <input type="text" name="company_name" class="form-control" 
                       value="<?= htmlspecialchars($companyName) ?>" required
                       oninput="validateCompanyName(this)">
                <small class="text-muted">Letters, numbers, spaces, ., -, &, ', ( ) allowed</small>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-industry"></i> Industry</label>
                    <input type="text" name="industry" class="form-control" 
                           value="<?= htmlspecialchars($companyIndustry) ?>"
                           oninput="validateIndustry(this)">
                    <small class="text-muted">Letters and spaces only</small>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Location</label>
                    <input type="text" name="location" class="form-control" 
                           value="<?= htmlspecialchars($companyLocation) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-globe"></i> Website</label>
                    <input type="url" name="website" class="form-control" 
                           value="<?= htmlspecialchars($companyWebsite) ?>"
                           placeholder="https://example.com">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?= htmlspecialchars($companyPhone) ?>"
                           oninput="validatePhone(this)"
                           placeholder="0781234567">
                    <small class="text-muted">Rwandan number: 072/073/078/079 + 7 digits</small>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="dashboard.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
    /* Existing styles plus extras */
    .form-page-wrapper { max-width: 700px; margin: 2rem auto; padding: 0 1rem; }
    .form-card-modern { background: white; border-radius: 1.5rem; box-shadow: 0 20px 35px rgba(0,0,0,0.1); overflow: hidden; }
    .form-header { background: linear-gradient(135deg, #1a5f7a, #0e3a4a); padding: 1.8rem 2rem; text-align: center; color: white; }
    .form-header i { font-size: 2.5rem; margin-bottom: 0.5rem; }
    .form-header h1 { font-size: 1.8rem; margin: 0.5rem 0 0.2rem; }
    .form-header p { opacity: 0.9; font-size: 0.95rem; }
    .modern-form { padding: 2rem; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.2rem; }
    .form-group { margin-bottom: 1.2rem; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e2f3e; font-size: 0.9rem; }
    .form-group label i { width: 1.5rem; color: #2c7da0; }
    .form-control { width: 100%; padding: 0.8rem 1rem; border: 1px solid #cbd5e1; border-radius: 0.8rem; font-size: 0.95rem; transition: all 0.2s; font-family: inherit; }
    .form-control:focus { outline: none; border-color: #2c7da0; box-shadow: 0 0 0 3px rgba(44,125,160,0.1); }
    .form-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }
    .btn-submit { background: linear-gradient(135deg, #1a5f7a, #0e3a4a); color: white; border: none; padding: 0.8rem 1.8rem; border-radius: 2rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(26,95,122,0.3); }
    .btn-secondary { background: #f0f4f8; color: #2c5a74; border: none; padding: 0.8rem 1.8rem; border-radius: 2rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: 0.2s; }
    .btn-secondary:hover { background: #e2eaf1; }
    .alert { margin: 1rem 2rem 0 2rem; padding: 0.8rem 1rem; border-radius: 0.8rem; display: flex; align-items: center; gap: 0.5rem; }
    .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #4caf50; }
    .alert-error { background: #ffebee; color: #c62828; border-left: 4px solid #f44336; }
    .text-muted { font-size: 0.75rem; color: #6c8faa; margin-top: 0.25rem; display: block; }
    @media (max-width: 700px) { .form-row { grid-template-columns: 1fr; gap: 0; } .modern-form { padding: 1.5rem; } .form-header { padding: 1.2rem; } .form-header h1 { font-size: 1.4rem; } }
</style>

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

<?php include '../includes/templates/footer.php'; ?>