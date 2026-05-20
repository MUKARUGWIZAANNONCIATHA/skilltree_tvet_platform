<?php
/**
 * Index Page - Landing Page & Router
 * Path: /index.php
 */

// Start session only if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions/common.php';

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to appropriate dashboard based on role
    $role = $_SESSION['user_role'];
    switch($role) {
        case 'admin':
            header('Location: /admin/dashboard.php');
            break;
        case 'teacher':
            header('Location: /teacher/dashboard.php');
            break;
        case 'student':
            header('Location: /student/dashboard.php');
            break;
        case 'company':
            header('Location: /company/dashboard.php');
            break;
        default:
            header('Location: /includes/auth/login.php');
    }
    exit();
}

// If not logged in, show landing page
include_once 'includes/templates/header.php';
?>

<div class="landing-container">
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to <span class="highlight">SkillTree</span> TVET Platform</h1>
            <p>Your pathway to professional excellence in Technical and Vocational Education</p>
            <!-- Animated motivational slogans (replaces buttons) -->
            <div class="motivation-slogans">
                <span class="motivation-text">💡 Every expert was once a beginner – start today</span><br>
                <span class="motivation-text">🚀 Skills open doors – build yours with confidence</span><br>
                <span class="motivation-text">🌱 Learn, practice, succeed – step by step</span><br>
                <span class="motivation-text">🏆 Your future starts now – invest in your growth</span>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <h2>Why Choose SkillTree?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🎓</div>
                <h3>TVET Curriculum Aligned</h3>
                <p>Fully aligned with Rwanda TVET Board curriculum standards</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3>AI-Powered Learning</h3>
                <p>Smart content generation and personalized learning paths</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Secure Assessments</h3>
                <p>Anti-cheating proctoring for all quizzes and exams</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏢</div>
                <h3>Company Matching</h3>
                <p>Connect with employers for internships</p>
            </div>
        </div>
    </section>
</div>

<style>
.landing-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.hero {
    text-align: center;
    padding: 100px 20px;
    color: white;
}
.hero h1 {
    font-size: 48px;
    margin-bottom: 20px;
}
.hero .highlight {
    color: #ffd700;
}
.hero p {
    font-size: 20px;
    margin-bottom: 30px;
    opacity: 0.9;
}
.hero-buttons .btn {
    padding: 12px 30px;
    margin: 0 10px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
}
.btn-primary {
    background: #ffd700;
    color: #333;
}
.btn-outline {
    border: 2px solid white;
    color: white;
    background: transparent;
}
.features {
    background: white;
    padding: 60px 20px;
    text-align: center;
}
.features h2 {
    font-size: 36px;
    margin-bottom: 40px;
    color: #333;
}
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}
.feature-card {
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}
.feature-card:hover {
    transform: translateY(-5px);
}
.feature-icon {
    font-size: 48px;
    margin-bottom: 15px;
}
</style>

<?php include_once 'includes/templates/footer.php'; ?> 
