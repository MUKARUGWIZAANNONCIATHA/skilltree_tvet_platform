<?php
/**
 * Features Page – detailed platform capabilities
 * Path: /features.php
 */
require_once 'config/database.php';
include_once 'includes/templates/header.php';
?>

<div class="features-container">
    <div class="features-hero">
        <h1>SkillTree TVET Features</h1>
        <p>Everything you need to succeed in Technical and Vocational Education</p>
    </div>

    <div class="features-grid">
        <div class="feature-block">
            <i class="fas fa-robot"></i>
            <h2>AI‑Powered Learning</h2>
            <p>Personalised learning paths, intelligent recommendations, and an AI tutor that answers your questions in real time.</p>
        </div>
        <div class="feature-block">
            <i class="fas fa-tree"></i>
            <h2>Structured Skill Tree</h2>
            <p>Follow a clear path from Learning Outcomes to Topics. Complete resources, pass quizzes, and unlock the next level.</p>
        </div>
        <div class="feature-block">
            <i class="fas fa-shield-alt"></i>
            <h2>Secure Assessments</h2>
            <p>Anti‑cheating proctoring (tab switch detection, copy protection) ensures exam integrity.</p>
        </div>
        <div class="feature-block">
            <i class="fas fa-briefcase"></i>
            <h2>Internship Matching</h2>
            <p>Connect with companies, apply for internships, and track your applications.</p>
        </div>
        <div class="feature-block">
            <i class="fas fa-users"></i>
            <h2>Community & Peer Support</h2>
            <p>Discussion forums where students help each other and share resources.</p>
        </div>
        <div class="feature-block">
            <i class="fas fa-chart-line"></i>
            <h2>Progress Tracking</h2>
            <p>Detailed analytics per module, LO, and topic. Know exactly where you stand.</p>
        </div>
    </div>
</div>

<style>
    .features-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    .features-hero {
        text-align: center;
        margin-bottom: 2rem;
    }
    .features-hero h1 {
        font-size: 2rem;
        color: #1a5f7a;
        margin-bottom: 0.5rem;
    }
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.5rem;
    }
    .feature-block {
        background: white;
        border-radius: 1.2rem;
        padding: 1.8rem;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: transform 0.2s;
    }
    .feature-block:hover {
        transform: translateY(-5px);
    }
    .feature-block i {
        font-size: 2.5rem;
        color: #2c7da0;
        margin-bottom: 1rem;
    }
    .feature-block h2 {
        margin-bottom: 0.8rem;
        color: #1a5f7a;
    }
    .feature-block p {
        color: #2c5a74;
    }
</style>

<?php include_once 'includes/templates/footer.php'; ?>