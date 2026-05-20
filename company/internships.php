<?php
require_once 'auth.php';
include '../includes/templates/header.php';
require_once '../includes/functions/validation.php';  // Added validation helpers

$deleteMessage = '';
$updateMessage = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM internships WHERE internship_id = ? AND company_id = ?");
    $stmt->execute([$id, $companyId]);
    $deleteMessage = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Internship deleted successfully.</div>';
}

// Handle edit update with validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_internship'])) {
    $id = intval($_POST['internship_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $duration = intval($_POST['duration_months']);
    $requirements = trim($_POST['requirements']);
    $deadline = $_POST['application_deadline'] ?: null;
    $is_open = isset($_POST['is_open']) ? 1 : 0;

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

    if (!empty($errors)) {
        $updateMessage = '<div class="alert alert-error"><ul>';
        foreach ($errors as $err) {
            $updateMessage .= '<li>' . htmlspecialchars($err) . '</li>';
        }
        $updateMessage .= '</ul></div>';
    } else {
        $stmt = $pdo->prepare("UPDATE internships SET title=?, description=?, location=?, duration_months=?, required_modules=?, application_deadline=?, is_open=? WHERE internship_id=? AND company_id=?");
        $stmt->execute([$title, $description, $location, $duration, $requirements, $deadline, $is_open, $id, $companyId]);
        $updateMessage = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Internship updated successfully.</div>';
    }
}

$stmt = $pdo->prepare("SELECT * FROM internships WHERE company_id = ? ORDER BY created_at DESC");
$stmt->execute([$companyId]);
$internships = $stmt->fetchAll();

$editIntern = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM internships WHERE internship_id = ? AND company_id = ?");
    $stmt->execute([$editId, $companyId]);
    $editIntern = $stmt->fetch();
}
?>

<!-- ========== HTML PART (unchanged except for the message display area) ========== -->
<!-- Your original HTML goes here – I'll embed the PHP messages where they appear -->
<!-- Since you provided the HTML in the original code, I'll keep it exactly as is, 
     but ensure $deleteMessage and $updateMessage are displayed. 
     In the original, there was a placeholder for these messages. I'll insert them. -->

<div class="form-page-wrapper">
    <div class="form-card-modern">
        <div class="form-header">
            <i class="fas fa-briefcase"></i>
            <h1>My Internships</h1>
            <p>Manage your posted opportunities</p>
        </div>

        <?php echo $deleteMessage; ?>
        <?php echo $updateMessage; ?>

        <?php if ($editIntern): ?>
            <!-- Modern Edit Form (unchanged) -->
            <div style="padding: 2rem;">
                <h2 style="margin-bottom: 1.5rem; color: #1a5f7a;"><i class="fas fa-edit"></i> Edit Internship</h2>
                <form method="post" class="modern-form" onsubmit="return validateEditForm()">
                    <input type="hidden" name="internship_id" value="<?= $editIntern['internship_id'] ?>">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Internship Title *</label>
                        <input type="text" name="title" id="edit_title" class="form-control" value="<?= htmlspecialchars($editIntern['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" id="edit_desc" class="form-control" rows="5"><?= htmlspecialchars($editIntern['description']) ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Location</label>
                            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($editIntern['location']) ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Duration (months)</label>
                            <input type="number" name="duration_months" id="edit_duration" class="form-control" value="<?= $editIntern['duration_months'] ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clipboard-list"></i> Requirements / Skills</label>
                        <textarea name="requirements" class="form-control" rows="4"><?= htmlspecialchars($editIntern['required_modules']) ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-hourglass-end"></i> Application Deadline</label>
                            <input type="date" name="application_deadline" id="edit_deadline" class="form-control" value="<?= $editIntern['application_deadline'] ?>">
                        </div>
                        <div class="form-group" style="display: flex; align-items: center;">
                            <label style="margin-right: 1rem;">
                                <input type="checkbox" name="is_open" value="1" <?= $editIntern['is_open'] ? 'checked' : '' ?>> 
                                Open for applications
                            </label>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_internship" class="btn-submit">
                            <i class="fas fa-save"></i> Update Internship
                        </button>
                        <a href="internships.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Internships List (unchanged) -->
        <div style="padding: 0 2rem 2rem 2rem;">
            <?php if (empty($internships)): ?>
                <div class="empty-state">
                    <i class="fas fa-briefcase"></i>
                    <p>No internships posted yet. <a href="post-internship.php">Post your first internship</a></p>
                </div>
            <?php else: ?>
                <div class="internships-list">
                    <?php foreach ($internships as $intern): ?>
                    <div class="internship-card">
                        <div class="internship-header">
                            <h3><?= htmlspecialchars($intern['title']) ?></h3>
                            <div class="card-actions">
                                <a href="?edit=<?= $intern['internship_id'] ?>" class="action-btn edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?delete=<?= $intern['internship_id'] ?>" class="action-btn delete" onclick="return confirm('Delete this internship?')"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                        </div>
                        <div class="internship-description"><?= nl2br(htmlspecialchars($intern['description'])) ?></div>
                        <div class="internship-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?= $intern['location'] ?: 'Any location' ?></span>
                            <span><i class="fas fa-clock"></i> <?= $intern['duration_months'] ?> months</span>
                            <span><i class="fas fa-calendar-alt"></i> Deadline: <?= $intern['application_deadline'] ? date('d M Y', strtotime($intern['application_deadline'])) : 'Rolling' ?></span>
                            <span class="status-badge <?= $intern['is_open'] ? 'open' : 'closed' ?>"><?= $intern['is_open'] ? 'Open' : 'Closed' ?></span>
                        </div>
                        <div class="internship-footer">
                            <a href="applications.php?internship_id=<?= $intern['internship_id'] ?>" class="btn-view-apps"><i class="fas fa-users"></i> View Applications</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Client-side validation for edit form
    function validateEditForm() {
        const title = document.getElementById('edit_title');
        const desc = document.getElementById('edit_desc');
        const duration = document.getElementById('edit_duration');
        const deadline = document.getElementById('edit_deadline');

        if (title.value.trim().length < 5) {
            alert('Title must be at least 5 characters.');
            title.focus();
            return false;
        }
        if (desc.value.trim().length < 20) {
            alert('Description must be at least 20 characters.');
            desc.focus();
            return false;
        }
        let dur = parseInt(duration.value);
        if (isNaN(dur) || dur < 1 || dur > 24) {
            alert('Duration must be between 1 and 24 months.');
            duration.focus();
            return false;
        }
        if (deadline.value) {
            const today = new Date().toISOString().slice(0,10);
            if (deadline.value < today) {
                alert('Application deadline cannot be in the past.');
                deadline.focus();
                return false;
            }
        }
        return true;
    }
</script>

<!-- The rest of the styles are exactly as in the original – unchanged -->
<?php include '../includes/templates/footer.php'; ?>

<div class="form-page-wrapper">
    <div class="form-card-modern">
        <div class="form-header">
            <i class="fas fa-briefcase"></i>
            <h1>My Internships</h1>
            <p>Manage your posted opportunities</p>
        </div>

        <?php echo $deleteMessage ?? ''; ?>
        <?php echo $updateMessage ?? ''; ?>

        <?php if ($editIntern): ?>
            <!-- Modern Edit Form -->
            <div style="padding: 2rem;">
                <h2 style="margin-bottom: 1.5rem; color: #1a5f7a;"><i class="fas fa-edit"></i> Edit Internship</h2>
                <form method="post" class="modern-form">
                    <input type="hidden" name="internship_id" value="<?= $editIntern['internship_id'] ?>">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Internship Title *</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editIntern['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($editIntern['description']) ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Location</label>
                            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($editIntern['location']) ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Duration (months)</label>
                            <input type="number" name="duration_months" class="form-control" value="<?= $editIntern['duration_months'] ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clipboard-list"></i> Requirements / Skills</label>
                        <textarea name="requirements" class="form-control" rows="4"><?= htmlspecialchars($editIntern['required_modules']) ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-hourglass-end"></i> Application Deadline</label>
                            <input type="date" name="application_deadline" class="form-control" value="<?= $editIntern['application_deadline'] ?>">
                        </div>
                        <div class="form-group" style="display: flex; align-items: center;">
                            <label style="margin-right: 1rem;">
                                <input type="checkbox" name="is_open" value="1" <?= $editIntern['is_open'] ? 'checked' : '' ?>> 
                                Open for applications
                            </label>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_internship" class="btn-submit">
                            <i class="fas fa-save"></i> Update Internship
                        </button>
                        <a href="internships.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Internships List -->
        <div style="padding: 0 2rem 2rem 2rem;">
            <?php if (empty($internships)): ?>
                <div class="empty-state">
                    <i class="fas fa-briefcase"></i>
                    <p>No internships posted yet. <a href="post-internship.php">Post your first internship</a></p>
                </div>
            <?php else: ?>
                <div class="internships-list">
                    <?php foreach ($internships as $intern): ?>
                    <div class="internship-card">
                        <div class="internship-header">
                            <h3><?= htmlspecialchars($intern['title']) ?></h3>
                            <div class="card-actions">
                                <a href="?edit=<?= $intern['internship_id'] ?>" class="action-btn edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?delete=<?= $intern['internship_id'] ?>" class="action-btn delete" onclick="return confirm('Delete this internship?')"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                        </div>
                        <div class="internship-description"><?= nl2br(htmlspecialchars($intern['description'])) ?></div>
                        <div class="internship-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?= $intern['location'] ?: 'Any location' ?></span>
                            <span><i class="fas fa-clock"></i> <?= $intern['duration_months'] ?> months</span>
                            <span><i class="fas fa-calendar-alt"></i> Deadline: <?= $intern['application_deadline'] ? date('d M Y', strtotime($intern['application_deadline'])) : 'Rolling' ?></span>
                            <span class="status-badge <?= $intern['is_open'] ? 'open' : 'closed' ?>"><?= $intern['is_open'] ? 'Open' : 'Closed' ?></span>
                        </div>
                        <div class="internship-footer">
                            <a href="applications.php?internship_id=<?= $intern['internship_id'] ?>" class="btn-view-apps"><i class="fas fa-users"></i> View Applications</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* Global styles – reuse from post-internship.php, add missing ones */
    .form-page-wrapper {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .form-card-modern {
        background: white;
        border-radius: 1.5rem;
        box-shadow: 0 20px 35px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .form-header {
        background: linear-gradient(135deg, #1a5f7a, #0e3a4a);
        padding: 1.8rem 2rem;
        text-align: center;
        color: white;
    }
    .form-header i {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }
    .form-header h1 {
        font-size: 1.8rem;
        margin: 0.5rem 0 0.2rem;
    }
    .form-header p {
        opacity: 0.9;
        font-size: 0.95rem;
    }
    .modern-form {
        padding: 0;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1.2rem;
    }
    .form-group {
        margin-bottom: 1.2rem;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #1e2f3e;
        font-size: 0.9rem;
    }
    .form-group label i {
        width: 1.5rem;
        color: #2c7da0;
    }
    .form-control {
        width: 100%;
        padding: 0.8rem 1rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.8rem;
        font-size: 0.95rem;
        transition: all 0.2s;
        font-family: inherit;
    }
    .form-control:focus {
        outline: none;
        border-color: #2c7da0;
        box-shadow: 0 0 0 3px rgba(44,125,160,0.1);
    }
    textarea.form-control {
        resize: vertical;
    }
    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 1.5rem;
    }
    .btn-submit {
        background: linear-gradient(135deg, #1a5f7a, #0e3a4a);
        color: white;
        border: none;
        padding: 0.8rem 1.8rem;
        border-radius: 2rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(26,95,122,0.3);
    }
    .btn-secondary {
        background: #f0f4f8;
        color: #2c5a74;
        border: none;
        padding: 0.8rem 1.8rem;
        border-radius: 2rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: 0.2s;
    }
    .btn-secondary:hover {
        background: #e2eaf1;
    }
    .alert {
        margin: 1rem 2rem 0 2rem;
        padding: 0.8rem 1rem;
        border-radius: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        border-left: 4px solid #4caf50;
    }
    /* Internship cards */
    .internships-list {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
    }
    .internship-card {
        background: #f8fafc;
        border-radius: 1rem;
        padding: 1.2rem;
        border: 1px solid #eef2f8;
        transition: 0.2s;
    }
    .internship-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .internship-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 0.8rem;
    }
    .internship-header h3 {
        margin: 0;
        color: #1a5f7a;
    }
    .card-actions {
        display: flex;
        gap: 0.8rem;
    }
    .action-btn {
        padding: 0.2rem 0.8rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    .action-btn.edit {
        background: #2196F3;
        color: white;
    }
    .action-btn.delete {
        background: #f44336;
        color: white;
    }
    .internship-description {
        margin: 0.8rem 0;
        color: #2c5a74;
        font-size: 0.9rem;
    }
    .internship-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        font-size: 0.8rem;
        color: #6c8faa;
        margin: 0.5rem 0;
    }
    .status-badge {
        padding: 0.2rem 0.6rem;
        border-radius: 2rem;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .status-badge.open {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .status-badge.closed {
        background: #ffebee;
        color: #c62828;
    }
    .internship-footer {
        margin-top: 0.8rem;
        text-align: right;
    }
    .btn-view-apps {
        background: #2c7da0;
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 2rem;
        text-decoration: none;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #8aaec0;
    }
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    @media (max-width: 700px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }
        .internship-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
    }
</style>

<?php include '../includes/templates/footer.php'; ?>