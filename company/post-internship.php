<?php
require_once 'auth.php';
require_once '../includes/functions/validation.php';  // Include validation helpers
include '../includes/templates/header.php';

$message = '';

// Ensure companyId is valid
if (!isset($companyId) || $companyId <= 0) {
    $message = '<div class="alert alert-error">Invalid company session. Please login again.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($message)) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $duration = intval($_POST['duration_months'] ?? 3);
    $requirements = trim($_POST['requirements'] ?? '');
    $deadline = $_POST['application_deadline'] ?? null;

    // Validation
    $errors = [];

    if (empty($title)) {
        $errors[] = 'Title is required.';
    } elseif (strlen($title) < 5) {
        $errors[] = 'Title must be at least 5 characters.';
    } elseif (strlen($title) > 100) {
        $errors[] = 'Title must not exceed 100 characters.';
    } elseif (!preg_match('/^[A-Za-z0-9\s\-_,.!?]+$/', $title)) {
        $errors[] = 'Title contains invalid characters. Only letters, numbers, spaces, and basic punctuation allowed.';
    }

    if (empty($description)) {
        $errors[] = 'Description is required.';
    } elseif (strlen($description) < 20) {
        $errors[] = 'Description must be at least 20 characters.';
    }

    if ($duration < 1 || $duration > 24) {
        $errors[] = 'Duration must be between 1 and 24 months.';
    }

    if (!empty($deadline) && strtotime($deadline) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Application deadline cannot be in the past.';
    }

    // Set nullable fields
    $location = $location === '' ? null : strip_tags($location);
    $requirements = $requirements === '' ? null : strip_tags($requirements);
    $deadline = !empty($deadline) ? $deadline : null;

    if (!empty($errors)) {
        $message = '<div class="alert alert-error"><ul>';
        foreach ($errors as $err) {
            $message .= '<li>' . htmlspecialchars($err) . '</li>';
        }
        $message .= '</ul></div>';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO internships (company_id, title, description, location, duration_months, required_modules, application_deadline, is_open) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$companyId, $title, $description, $location, $duration, $requirements, $deadline]);
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Internship posted successfully!</div>';
            // Clear form after success
            $_POST = [];
        } catch (PDOException $e) {
            $message = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>

<div class="form-page-wrapper">
    <div class="form-card-modern">
        <div class="form-header">
            <i class="fas fa-briefcase"></i>
            <h1>Post a New Internship</h1>
            <p>Share opportunities with talented students</p>
        </div>

        <?php echo $message; ?>

        <form method="post" class="modern-form">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Internship Title <span class="required">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., Software Developer Intern" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Location</label>
                    <input type="text" name="location" class="form-control" placeholder="e.g., Kigali, Remote" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-align-left"></i> Full Description <span class="required">*</span></label>
                <textarea name="description" class="form-control" rows="6" placeholder="Describe the role, responsibilities, and what the intern will learn..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-calendar-alt"></i> Duration (months)</label>
                    <input type="number" name="duration_months" class="form-control" value="<?= htmlspecialchars($_POST['duration_months'] ?? 3) ?>" min="1" max="24">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-hourglass-end"></i> Application Deadline</label>
                    <input type="date" name="application_deadline" class="form-control" value="<?= htmlspecialchars($_POST['application_deadline'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-clipboard-list"></i> Requirements / Preferred Skills (optional)</label>
                <textarea name="requirements" class="form-control" rows="4" placeholder="List technical skills, soft skills, academic background..."><?= htmlspecialchars($_POST['requirements'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Post Internship
                </button>
                <a href="internships.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
    /* Your same modern styles – unchanged */
    .form-page-wrapper { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
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
    .required { color: #e74c3c; margin-left: 3px; }
    .form-control { width: 100%; padding: 0.8rem 1rem; border: 1px solid #cbd5e1; border-radius: 0.8rem; font-size: 0.95rem; transition: all 0.2s; font-family: inherit; }
    .form-control:focus { outline: none; border-color: #2c7da0; box-shadow: 0 0 0 3px rgba(44,125,160,0.1); }
    textarea.form-control { resize: vertical; }
    .form-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }
    .btn-submit { background: linear-gradient(135deg, #1a5f7a, #0e3a4a); color: white; border: none; padding: 0.8rem 1.8rem; border-radius: 2rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(26,95,122,0.3); }
    .btn-secondary { background: #f0f4f8; color: #2c5a74; border: none; padding: 0.8rem 1.8rem; border-radius: 2rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: 0.2s; }
    .btn-secondary:hover { background: #e2eaf1; }
    .alert { margin: 1rem 2rem 0 2rem; padding: 0.8rem 1rem; border-radius: 0.8rem; display: flex; align-items: center; gap: 0.5rem; }
    .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #4caf50; }
    .alert-error { background: #ffebee; color: #c62828; border-left: 4px solid #f44336; }
    .alert-error ul { margin: 0; padding-left: 1.2rem; }
    @media (max-width: 700px) { .form-row { grid-template-columns: 1fr; gap: 0; } .modern-form { padding: 1.5rem; } .form-header { padding: 1.2rem; } .form-header h1 { font-size: 1.4rem; } }
</style>

<?php include '../includes/templates/footer.php'; ?>