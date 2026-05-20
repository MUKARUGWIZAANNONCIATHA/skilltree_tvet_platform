<?php
/**
 * Privacy Policy Page
 * Path: /privacy.php
 */
require_once 'config/database.php';
// No authentication required – public page
include_once 'includes/templates/header.php';
?>

<div class="policy-container">
    <div class="policy-card">
        <h1>Privacy Policy</h1>
        <div class="last-updated">Last updated: <?php echo date('F d, Y'); ?></div>

        <p>SkillTree TVET is committed to protecting your privacy. This Privacy Policy explains how we collect, use, and protect your personal information when you use our platform.</p>

        <h2>1. Information We Collect</h2>
        <p>We may collect the following information:</p>
        <ul>
            <li>Full name</li>
            <li>Email address</li>
            <li>Phone number</li>
            <li>Other details (sector, trade, level)</li>
            <li>Learning activity (courses accessed, quizzes, progress, certificates)</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <p>We use your information to:</p>
        <ul>
            <li>Provide access to learning services</li>
            <li>Track learning progress</li>
            <li>Improve platform performance</li>
            <li>Communicate important updates</li>
        </ul>

        <h2>3. Sharing of Information</h2>
        <p>We do not sell your personal data. We may share information only with:</p>
        <ul>
            <li>Authorized teachers or institutions</li>
            <li>Service providers that help us operate the platform</li>
            <li>Legal authorities if required by law</li>
        </ul>

        <h2>4. Data Protection</h2>
        <p>We take reasonable technical and organizational measures to protect your data from unauthorized access, loss, or misuse.</p>

        <h2>5. Your Rights</h2>
        <p>You have the right to:</p>
        <ul>
            <li>Access your data</li>
            <li>Request correction of your data</li>
            <li>Request deletion of your account and data</li>
        </ul>

        <h2>6. Cookies</h2>
        <p>We may use cookies to improve user experience and track usage of the platform. You can disable cookies in your browser settings, but some features may not work properly.</p>

        <h2>7. Children's Privacy</h2>
        <p>Our platform is not intended for children under 14 without parental supervision. If we become aware that we have collected personal information from a child under 14 without verification of parental consent, we will take steps to remove that information.</p>

        <h2>8. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. Users will be notified of any major changes via email or platform notification.</p>

        <h2>9. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, please contact us at:</p>
        <p>
            <i class="fas fa-envelope"></i> <a href="mailto:annasiza10@gmail.com">annasiza10@gmail.com</a><br>
            <i class="fas fa-phone"></i> <a href="tel:+250782513700">+250 782 513 700</a>
        </p>
    </div>
</div>

<style>
    .policy-container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    .policy-card {
        background: white;
        border-radius: 1.5rem;
        padding: 2.5rem;
        box-shadow: 0 8px 25px rgba(0,0,0,0.05);
    }
    .policy-card h1 {
        color: #1a5f7a;
        font-size: 2rem;
        margin-bottom: 0.5rem;
        border-bottom: 2px solid #eef2f8;
        padding-bottom: 0.75rem;
    }
    .last-updated {
        color: #6c8faa;
        font-style: italic;
        margin-bottom: 1.8rem;
        font-size: 0.9rem;
    }
    .policy-card h2 {
        color: #1a5f7a;
        font-size: 1.4rem;
        margin: 1.5rem 0 0.8rem;
    }
    .policy-card p, .policy-card li {
        line-height: 1.6;
        color: #2c5a74;
    }
    .policy-card ul {
        margin-left: 1.5rem;
        margin-bottom: 1rem;
    }
    .policy-card li {
        margin-bottom: 0.3rem;
    }
    .policy-card a {
        color: #2c7da0;
        text-decoration: none;
    }
    .policy-card a:hover {
        text-decoration: underline;
    }
    @media (max-width: 700px) {
        .policy-card {
            padding: 1.5rem;
        }
        .policy-card h1 {
            font-size: 1.6rem;
        }
        .policy-card h2 {
            font-size: 1.2rem;
        }
    }
</style>

<?php include_once 'includes/templates/footer.php'; ?>