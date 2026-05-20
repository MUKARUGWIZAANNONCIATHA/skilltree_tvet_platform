<?php
/**
 * Student Help Center
 * Path: /student/help.php
 * Provides guidance on learning, assessments, anti‑cheating, internships, etc.
 */
require_once '../config/database.php';
require_once '../includes/auth/session-check.php';
include_once '../includes/templates/header.php';
?>

<div class="help-container">
    <div class="help-header">
        <h1><i class="fas fa-life-ring"></i> Help Center</h1>
        <p>Everything you need to know about using SkillTree TVET</p>
    </div>

    <div class="help-grid">
        <!-- 1. Start Learning -->
        <div class="help-card">
            <h2><i class="fas fa-play-circle"></i> 1. Start Learning</h2>
            <div class="help-content">
                <p>Once you log in, you will see your enrolled modules on the Dashboard. Click “Access Module” to begin. Each module is divided into Learning Outcomes (LOs), Topics, and Sub‑topics. You must complete topics in order – the next LO unlocks only after you pass all quizzes in the current LO.</p>
                <ul>
                    <li><strong>First time?</strong> If you have no modules, you may need to select a Trade first (system will prompt you).</li>
                    <li><strong>Progress is saved automatically</strong> – you can stop and resume anytime.</li>
                </ul>
            </div>
        </div>

        <!-- 2. Learning & Modules -->
        <div class="help-card">
            <h2><i class="fas fa-book-open"></i> 2. Learning & Modules</h2>
            <div class="help-content">
                <p>Your learning path follows the TVET curriculum:</p>
                <ul>
                    <li><strong>Modules</strong> – broad subject areas (e.g., Programming Fundamentals).</li>
                    <li><strong>Learning Outcomes (LOs)</strong> – specific skills you must master.</li>
                    <li><strong>Indicative Contents (ICs)</strong> – groups of related topics.</li>
                    <li><strong>Topics & Sub‑topics</strong> – the actual study units.</li>
                </ul>
                <p>Each topic includes required resources (notes, videos). You must mark them as “read/watched” to unlock the topic quiz. After passing all topic quizzes, the LO assessment becomes available.</p>
            </div>
        </div>

        <!-- 3. Assessments & Quizzes -->
        <div class="help-card">
            <h2><i class="fas fa-clipboard-list"></i> 3. Assessments & Quizzes</h2>
            <div class="help-content">
                <p><strong>Topic Quizzes</strong> – Appear after you have studied ≥90% of the required resources. Quizzes are short (one question per topic) and you must pass them to complete the topic.</p>
                <p><strong>LO Assessments</strong> – Comprehensive exams covering all topics of a Learning Outcome. Structure: Section A (compulsory, 55 marks), Section B (choose 3 of 5, 30 marks), Section C (choose 1 of 2, 15 marks). Total 100 marks, passing score = 70%.</p>
                <p><strong>Module Final Exam</strong> – Available after all LOs of the module are completed. Same 100‑mark structure.</p>
                <p>You can retake failed quizzes/assessments as many times as needed.</p>
            </div>
        </div>

        <!-- 4. Anti-Cheating Rules -->
        <div class="help-card">
            <h2><i class="fas fa-shield-alt"></i> 4. Anti-Cheating Rules</h2>
            <div class="help-content">
                <p>To maintain academic integrity, the platform enforces:</p>
                <ul>
                    <li><strong>Tab switching detection</strong> – If you leave the quiz window more than 5 times, the quiz auto‑submits.</li>
                    <li><strong>Copy/paste is disabled</strong> during quizzes and assessments.</li>
                    <li><strong>Full‑screen mode may be required</strong> for final exams.</li>
                    <li><strong>Violations are logged</strong> – repeated offences may lead to account suspension.</li>
                </ul>
                <p><em>Tip:</em> Close all other tabs and applications before starting a quiz or exam.</p>
            </div>
        </div>

        <!-- 5. Progress Tracking -->
        <div class="help-card">
            <h2><i class="fas fa-chart-line"></i> 5. Progress Tracking</h2>
            <div class="help-content">
                <p>Your progress is shown on the Dashboard and inside each module:</p>
                <ul>
                    <li><strong>Module progress</strong> – percentage of completed topics (based on passed quizzes).</li>
                    <li><strong>LO progress</strong> – number of topics passed vs total topics.</li>
                    <li><strong>Overall progress</strong> – average of all your modules.</li>
                </ul>
                <p>You can also see detailed activity on your Profile page.</p>
            </div>
        </div>

        <!-- 6. AI Recommendations -->
        <div class="help-card">
            <h2><i class="fas fa-robot"></i> 6. AI Recommendations</h2>
            <div class="help-content">
                <p>The AI Tutor (floating chat icon) helps you understand difficult concepts. You can ask:</p>
                <ul>
                    <li>“Explain JOINs in SQL with a real‑world example.”</li>
                    <li>“What is the difference between 2NF and 3NF?”</li>
                    <li>“Give me a practice question for loops.”</li>
                </ul>
                <p>AI also suggests additional resources based on your performance (coming soon).</p>
            </div>
        </div>

        <!-- 7. Career & Internship -->
        <div class="help-card">
            <h2><i class="fas fa-briefcase"></i> 7. Career & Internship</h2>
            <div class="help-content">
                <ul>
                    <li><strong>Internships</strong> – Visit the “Internships” page to see open positions, apply with a cover letter, and track your applications.</li>
                    <li><strong>Job placement</strong> – Companies may contact you directly if your profile matches their requirements.</li>
                    <li><strong>Career resources</strong> – Access industry guides and CV templates in the Library.</li>
                </ul>
            </div>
        </div>

        <!-- 8. Discussion & Community -->
        <div class="help-card">
            <h2><i class="fas fa-comments"></i> 8. Discussion & Community</h2>
            <div class="help-content">
                <p>The <strong>Community</strong> page lets you:</p>
                <ul>
                    <li>Create or reply to discussion posts.</li>
                    <li>Share study tips or ask for help from other students.</li>
                    <li>Report issues or suggest improvements.</li>
                </ul>
                <p>Remember to be respectful and constructive.</p>
            </div>
        </div>

        <!-- 9. Technical Issues -->
        <div class="help-card">
            <h2><i class="fas fa-wrench"></i> 9. Technical Issues</h2>
            <div class="help-content">
                <p><strong>Common issues and solutions:</strong></p>
                <ul>
                    <li><strong>Page not loading?</strong> – Refresh or clear your browser cache.</li>
                    <li><strong>Quiz won’t unlock?</strong> – Ensure you have completed ≥90% of required resources (notes/videos).</li>
                    <li><strong>Video not playing?</strong> – Check your internet connection and enable third‑party cookies.</li>
                    <li><strong>Assessment auto‑submitted?</strong> – Too many tab switches; next time, stay on the exam window.</li>
                </ul>
                <p>If problems persist, use the “Contact Support” option below.</p>
            </div>
        </div>

        <!-- 10. Account & Security -->
        <div class="help-card">
            <h2><i class="fas fa-user-lock"></i> 10. Account & Security</h2>
            <div class="help-content">
                <ul>
                    <li><strong>Change password</strong> – Go to Profile → Change Password.</li>
                    <li><strong>Update profile info</strong> – Edit your name, phone, or profile picture.</li>
                    <li><strong>Forgot password?</strong> – Use the “Forgot Password” link on the login page to reset it.</li>
                    <li><strong>Account approval</strong> – New teacher/company accounts must be approved by an admin before they can log in.</li>
                </ul>
            </div>
        </div>

        <!-- 11. Contact Support -->
        <div class="help-card">
            <h2><i class="fas fa-headset"></i> 11. Contact Support</h2>
            <div class="help-content">
                <p>For personalised help, reach out via:</p>
                <ul>
                    <li><strong>Email:</strong> <a href="mailto:annasiza10@gmail.com">annasiza10@gmail.com</a></li>
                    <li><strong>Phone:</strong> <a href="tel:+250782513700">+250 782 513 700</a></li>
                    <li><strong>Office hours:</strong> Mon‑Fri, 8:00 – 17:00 (CAT)</li>
                </ul>
                <p>Please describe your issue clearly and include screenshots if possible.</p>
            </div>
        </div>

        <!-- 12. Policies -->
        <div class="help-card">
            <h2><i class="fas fa-file-alt"></i> 12. Policies</h2>
            <div class="help-content">
                <ul>
                    <li><a href="/privacy.php">Privacy Policy</a> – how we handle your data.</li>
                    <li><a href="/terms.php">Terms of Use</a> – platform rules and acceptable use.</li>
                    <li><a href="/student/faq.php">FAQ</a> – frequently asked questions.</li>
                </ul>
                <p>By using SkillTree TVET, you agree to abide by these policies.</p>
            </div>
        </div>
    </div>
</div>

<style>
    .help-container {
        max-width: 1300px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    .help-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .help-header h1 {
        font-size: 2rem;
        color: #1a5f7a;
        margin-bottom: 0.5rem;
    }
    .help-header p {
        color: #6c8faa;
        font-size: 1rem;
    }
    .help-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 1.5rem;
    }
    .help-card {
        background: white;
        border-radius: 1.2rem;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .help-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .help-card h2 {
        font-size: 1.3rem;
        color: #1a5f7a;
        margin-bottom: 1rem;
        border-left: 4px solid #2c7da0;
        padding-left: 0.8rem;
    }
    .help-card h2 i {
        margin-right: 8px;
        color: #2c7da0;
    }
    .help-content {
        line-height: 1.6;
        color: #2c5a74;
        font-size: 0.95rem;
    }
    .help-content ul {
        margin: 0.5rem 0 0 1.2rem;
    }
    .help-content li {
        margin-bottom: 0.3rem;
    }
    .help-content a {
        color: #2c7da0;
        text-decoration: none;
    }
    .help-content a:hover {
        text-decoration: underline;
    }
    @media (max-width: 780px) {
        .help-grid {
            grid-template-columns: 1fr;
        }
        .help-header h1 {
            font-size: 1.6rem;
        }
    }
</style>

<?php include_once '../includes/templates/footer.php'; ?> 
