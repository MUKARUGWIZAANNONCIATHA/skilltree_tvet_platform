<?php
/**
 * About Us Page
 * Path: /about.php
 */
require_once 'config/database.php';
include_once 'includes/templates/header.php';
?>

<div class="about-container">
    <div class="about-hero">
        <h1>About SkillTree TVET</h1>
        <p>Empowering Rwandan youth with practical skills for the future.</p>
    </div>

    <div class="about-content">
        <div class="about-section">
            <h2>Our Mission</h2>
            <p>To provide accessible, high-quality Technical and Vocational Education and Training (TVET) that bridges the gap between classroom learning and industry demands.</p>
        </div>

        <div class="about-section">
            <h2>Our Vision</h2>
            <p>A skilled Rwanda where every young person has the opportunity to build a successful career through competency‑based education.</p>
        </div>

        <div class="about-section">
            <h2>What We Offer</h2>
            <ul>
                <li><strong>AI‑powered learning paths</strong> personalized to each student.</li>
                <li><strong>Industry‑aligned curriculum</strong> based on the Rwanda TVET Board standards.</li>
                <li><strong>Practical assessments</strong> with anti‑cheating proctoring.</li>
                <li><strong>Internship matching</strong> and career support.</li>
                <li><strong>Community forums</strong> for peer learning and collaboration.</li>
            </ul>
        </div>

        <div class="about-section">
            <h2>Contact Information</h2>
            <p><i class="fas fa-envelope"></i> <a href="mailto:annasiza10@gmail.com">annasiza10@gmail.com</a></p>
            <p><i class="fas fa-phone"></i> <a href="tel:+250782513700">+250 782 513 700</a></p>
            <p><i class="fas fa-map-marker-alt"></i> Gisagara, Rwanda</p>
        </div>
    </div>
</div>

<style>
    .about-container {
        max-width: 1000px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    .about-hero {
        background: linear-gradient(135deg, #1a5f7a, #0e3a4a);
        border-radius: 1.5rem;
        padding: 3rem 2rem;
        text-align: center;
        color: white;
        margin-bottom: 2rem;
    }
    .about-hero h1 {
        font-size: 2.2rem;
        margin-bottom: 0.5rem;
    }
    .about-section {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .about-section h2 {
        color: #1a5f7a;
        margin-bottom: 1rem;
    }
    .about-section ul {
        margin-left: 1.5rem;
    }
    .about-section li {
        margin-bottom: 0.5rem;
    }
</style>

<?php include_once 'includes/templates/footer.php'; ?>