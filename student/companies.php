<?php
/**
 * Student Companies Page
 * Path: /student/companies.php
 * Displays a list of active companies with their details and internship opportunities.
 */
require_once '../config/database.php';
require_once '../includes/auth/session-check.php';
include_once '../includes/templates/header.php';

// Fetch all active companies directly from users table (role='company', approved, active)
$sql = "
    SELECT user_id, full_name as company_name, industry, location, website, 
           contact_phone as phone, description,
           (SELECT COUNT(*) FROM internships WHERE company_id = user_id AND is_open = 1 AND (status != 'closed' OR status IS NULL)) as open_internships
    FROM users
    WHERE role = 'company' AND is_approved = 1 AND is_active = 1
    ORDER BY full_name ASC
";
$companies = $pdo->query($sql)->fetchAll();

if (empty($companies)) {
    $companies = [];
}
?>

<div class="companies-container">
    <div class="page-header">
        <h1><i class="fas fa-building"></i> Companies & Employers</h1>
        <p>Explore companies offering internships and career opportunities</p>
    </div>

    <?php if (empty($companies)): ?>
        <div class="empty-state">
            <i class="fas fa-building" style="font-size: 3rem; color: #ccc;"></i>
            <p>No companies are currently registered on the platform.</p>
            <p>Check back later for internship opportunities.</p>
        </div>
    <?php else: ?>
        <div class="companies-grid">
            <?php foreach ($companies as $company): ?>
                <div class="company-card">
                    <div class="company-header">
                        <div class="company-logo-placeholder">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="company-info">
                            <h3><?= htmlspecialchars($company['company_name']) ?></h3>
                            <div class="company-meta">
                                <?php if (!empty($company['industry'])): ?>
                                    <span><i class="fas fa-industry"></i> <?= htmlspecialchars($company['industry']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($company['location'])): ?>
                                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($company['location']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="internship-count">
                            <span class="badge"><?= (int)$company['open_internships'] ?> open internships</span>
                        </div>
                    </div>

                    <?php if (!empty($company['description'])): ?>
                        <div class="company-description">
                            <?= nl2br(htmlspecialchars(substr($company['description'], 0, 200))) ?>
                            <?php if (strlen($company['description']) > 200): ?>...<?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="company-contact">
                        <?php if (!empty($company['website'])): ?>
                            <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank" rel="noopener noreferrer" class="contact-link">
                                <i class="fas fa-globe"></i> Website
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($company['contact_email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($company['contact_email']) ?>" class="contact-link">
                                <i class="fas fa-envelope"></i> Contact
                            </a>
                        <?php else: ?>
                            <?php if (!empty($company['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($company['email']) ?>" class="contact-link">
                                    <i class="fas fa-envelope"></i> Contact
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($company['phone'])): ?>
                            <span class="contact-link"><i class="fas fa-phone"></i> <?= htmlspecialchars($company['phone']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="company-actions">
                        <a href="/student/internships.php?company_id=<?= $company['user_id'] ?>" class="btn-view-internships">
                            <i class="fas fa-briefcase"></i> View Internships
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    /* Same styles as before – keep unchanged */
    .companies-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .page-header h1 {
        font-size: 2rem;
        color: #1a5f7a;
        margin-bottom: 0.5rem;
    }
    .page-header p {
        color: #6c8faa;
    }
    .companies-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 1.5rem;
    }
    .company-card {
        background: white;
        border-radius: 1.2rem;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
    }
    .company-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    }
    .company-header {
        display: flex;
        gap: 1rem;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }
    .company-logo-placeholder {
        width: 60px;
        height: 60px;
        background: #eef2fa;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #2c7da0;
    }
    .company-info {
        flex: 1;
    }
    .company-info h3 {
        margin: 0 0 0.3rem;
        font-size: 1.2rem;
        color: #1a5f7a;
    }
    .company-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
        font-size: 0.8rem;
        color: #6c8faa;
    }
    .company-meta i {
        margin-right: 0.2rem;
        width: 1rem;
    }
    .internship-count .badge {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 0.3rem 0.8rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .company-description {
        margin: 1rem 0;
        font-size: 0.9rem;
        color: #2c5a74;
        line-height: 1.5;
        flex: 1;
    }
    .company-contact {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin: 1rem 0 0.8rem;
        padding-top: 0.8rem;
        border-top: 1px solid #eef2f8;
    }
    .contact-link {
        font-size: 0.8rem;
        color: #2c7da0;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    .contact-link:hover {
        text-decoration: underline;
    }
    .company-actions {
        margin-top: 1rem;
    }
    .btn-view-internships {
        display: inline-block;
        background: #2c7da0;
        color: white;
        padding: 0.5rem 1.2rem;
        border-radius: 2rem;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        transition: 0.2s;
        text-align: center;
    }
    .btn-view-internships:hover {
        background: #1e5f7a;
        transform: translateY(-2px);
    }
    .empty-state {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 1.5rem;
        color: #8aaec0;
    }
    .empty-state i {
        margin-bottom: 1rem;
    }
    @media (max-width: 700px) {
        .companies-grid {
            grid-template-columns: 1fr;
        }
        .company-header {
            flex-direction: column;
            text-align: center;
        }
        .company-meta {
            justify-content: center;
        }
        .internship-count {
            text-align: center;
            width: 100%;
        }
    }
</style>

<?php include_once '../includes/templates/footer.php'; ?>