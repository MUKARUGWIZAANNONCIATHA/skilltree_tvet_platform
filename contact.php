<?php
/**
 * Contact Page – stores messages in database with validation
 * Path: /contact.php
 */
require_once 'config/database.php';
require_once 'includes/functions/validation.php'; // Include validation functions
include_once 'includes/templates/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $msg = sanitizeInput($_POST['message'] ?? '');

    // Validation
    if (empty($name)) {
        $error = 'Name is required.';
    } elseif (!validateAlpha($name)) {
        $error = 'Name must contain only letters and spaces.';
    } elseif (empty($email)) {
        $error = 'Email is required.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($msg)) {
        $error = 'Message cannot be empty.';
    } elseif (strlen($msg) < 10) {
        $error = 'Message must be at least 10 characters.';
    } else {
        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $msg]);
        $message = 'Your message has been sent. We will get back to you soon.';
        // Clear form
        $_POST = [];
        $name = $email = $subject = $msg = '';
    }
}
?>

<div class="contact-container">
    <div class="contact-header">
        <h1>Contact Us</h1>
        <p>Have questions? We'd love to hear from you.</p>
    </div>

    <div class="contact-grid">
        <div class="contact-info">
            <h3>Get in Touch</h3>
            <p><i class="fas fa-envelope"></i> <a href="mailto:annasiza10@gmail.com">annasiza10@gmail.com</a></p>
            <p><i class="fas fa-phone"></i> <a href="tel:+250782513700">+250 782 513 700</a></p>
            <p><i class="fas fa-map-marker-alt"></i> Gisagara, Rwanda</p>
            <p><i class="fas fa-clock"></i> Mon‑Fri, 8:00 – 17:00</p>
        </div>

        <div class="contact-form">
            <h3>Send Us a Message</h3>
            <?php if ($message): ?>
                <div class="alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Your Name *</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required
                           oninput="validateName(this)">
                    <small class="text-muted">Only letters and spaces allowed</small>
                </div>
                <div class="form-group">
                    <label>Your Email *</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Subject (optional)</label>
                    <input type="text" name="subject" class="form-control" 
                           value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" rows="5" class="form-control" required minlength="10"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    <small class="text-muted">Minimum 10 characters</small>
                </div>
                <button type="submit" class="btn-primary">Send Message</button>
            </form>
        </div>
    </div>
</div>

<style>
    /* Your existing styles – unchanged */
    .contact-container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    .contact-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .contact-header h1 {
        font-size: 2rem;
        color: #1a5f7a;
    }
    .contact-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 2rem;
    }
    .contact-info, .contact-form {
        background: white;
        border-radius: 1.2rem;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .contact-info h3, .contact-form h3 {
        margin-bottom: 1rem;
        color: #1a5f7a;
    }
    .contact-info p {
        margin-bottom: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
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
        border-radius: 0.6rem;
    }
    .btn-primary {
        background: #2c7da0;
        color: white;
        border: none;
        padding: 0.6rem 1.2rem;
        border-radius: 2rem;
        cursor: pointer;
    }
    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 0.5rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
    .alert-error {
        background: #ffebee;
        color: #c62828;
        padding: 0.5rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
    .text-muted {
        font-size: 0.7rem;
        color: #6c8faa;
        margin-top: 0.2rem;
        display: block;
    }
    @media (max-width: 700px) {
        .contact-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    function validateName(input) {
        // Allow only letters and spaces
        input.value = input.value.replace(/[^A-Za-z\s]/g, '');
    }
</script>

<?php include_once 'includes/templates/footer.php'; ?>