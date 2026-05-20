<?php
/**
 * Public FAQ Page
 * Path: /faq.php
 */
require_once 'config/database.php';
// No authentication required – public page
include_once 'includes/templates/header.php';
?>

<div class="faq-container">
    <div class="faq-header">
        <h1><i class="fas fa-question-circle"></i> Frequently Asked Questions</h1>
        <p>Find answers to common questions about the SkillTree TVET platform</p>
    </div>

    <div class="faq-grid">
        <!-- General Questions -->
        <div class="faq-category">
            <h2><i class="fas fa-info-circle"></i> General Questions</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>1. What is SkillTree TVET?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>SkillTree TVET is an intelligent learning platform based on the TVET curriculum that supports students, teachers, and institutions in online learning, assessments, career guidance, and internships.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>2. How do I register on the platform?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>You can register by providing your personal details such as name, email, and institution. After registration, you can log in and start learning.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>3. Can I continue learning after logging out?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, your learning progress is automatically saved, and you can continue from where you stopped after logging back in.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Learning Structure -->
        <div class="faq-category">
            <h2><i class="fas fa-book-open"></i> Learning Structure</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>4. How is learning organized on the platform?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Learning is structured into modules based on the TVET curriculum, including learning outcomes, topics, and resources. You must complete each step in order.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>5. Can I skip modules or topics?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>No. You must complete each learning outcome and pass required assessments before moving to the next section.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>6. Is there a minimum score required to continue?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes. You must score at least 70% in quizzes or assessments to proceed to the next learning outcome.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>7. Do I need to study all resources before taking a quiz?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes. You are required to study at least 90% of the provided learning materials before accessing assessments.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assessments & Quizzes -->
        <div class="faq-category">
            <h2><i class="fas fa-clipboard-list"></i> Assessments & Quizzes</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>8. What happens if I fail a quiz?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>If you fail, you can review the materials and retake the quiz until you achieve the required score.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>9. Are exams monitored for cheating?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes. The platform uses anti-cheating measures such as camera monitoring, screen activity detection, keyboard restrictions, and app-switch detection to ensure exam integrity.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>10. What happens if my internet disconnects during an assessment?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Your progress is automatically saved, and you can resume the assessment after logging back in.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress & AI -->
        <div class="faq-category">
            <h2><i class="fas fa-chart-line"></i> Progress & AI</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>11. Can I see my learning progress?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes. The platform displays your progress per topic, learning outcome, and module.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>12. Does the platform use AI?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes. SkillTree TVET uses AI-based recommendations to provide personalized learning paths based on your performance.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Community & Resources -->
        <div class="faq-category">
            <h2><i class="fas fa-users"></i> Community & Resources</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>13. Can I interact with other students?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes. The platform includes discussion boards and peer learning features to encourage collaboration among students.</p>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>14. Does the platform provide revision materials?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes. Students can access mock exams, past papers, marking guides, and revision questions for exam preparation.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Support -->
        <div class="faq-category">
            <h2><i class="fas fa-headset"></i> Support</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <span>15. Can I get help if I face problems?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes. You can contact support via email at <a href="mailto:annasiza10@gmail.com">annasiza10@gmail.com</a> for assistance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="faq-contact">
        <i class="fas fa-envelope"></i>
        <p>Still have questions? <a href="mailto:annasiza10@gmail.com">Email our support team</a> and we'll get back to you within 24 hours.</p>
    </div>
</div>

<style>
    /* Same styles as before – include them */
    .faq-container {
        max-width: 1100px;
        margin: 2rem auto;
        padding: 0 1.5rem;
    }
    .faq-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .faq-header h1 {
        font-size: 2rem;
        color: #1a5f7a;
        margin-bottom: 0.5rem;
    }
    .faq-header p {
        color: #6c8faa;
        font-size: 1rem;
    }
    .faq-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 1.5rem;
    }
    .faq-category {
        background: white;
        border-radius: 1.2rem;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .faq-category h2 {
        font-size: 1.3rem;
        color: #1a5f7a;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #eef2f8;
    }
    .faq-category h2 i {
        margin-right: 8px;
        color: #2c7da0;
    }
    .faq-list {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }
    .faq-item {
        border: 1px solid #eef2f8;
        border-radius: 0.8rem;
        overflow: hidden;
    }
    .faq-question {
        background: #f8fafc;
        padding: 1rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        color: #2c5a74;
        transition: background 0.2s;
    }
    .faq-question:hover {
        background: #eef2fa;
    }
    .faq-question i {
        transition: transform 0.2s;
        color: #2c7da0;
    }
    .faq-answer {
        display: none;
        padding: 1rem;
        background: #fefefe;
        border-top: 1px solid #eef2f8;
        line-height: 1.6;
        color: #4a6a82;
    }
    .faq-answer a {
        color: #2c7da0;
        text-decoration: none;
    }
    .faq-answer a:hover {
        text-decoration: underline;
    }
    .faq-contact {
        margin-top: 2rem;
        text-align: center;
        background: linear-gradient(135deg, #e8f0fe, #f8fafc);
        border-radius: 1rem;
        padding: 1.5rem;
    }
    .faq-contact i {
        font-size: 2rem;
        color: #2c7da0;
        margin-bottom: 0.5rem;
    }
    .faq-contact p {
        color: #2c5a74;
    }
    .faq-contact a {
        font-weight: 600;
        color: #1a5f7a;
        text-decoration: none;
    }
    .faq-contact a:hover {
        text-decoration: underline;
    }
    @media (max-width: 700px) {
        .faq-grid {
            grid-template-columns: 1fr;
        }
        .faq-header h1 {
            font-size: 1.5rem;
        }
    }
</style>

<script>
    function toggleAnswer(element) {
        const answer = element.nextElementSibling;
        const icon = element.querySelector('i');
        if (answer.style.display === 'block') {
            answer.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
        } else {
            answer.style.display = 'block';
            icon.style.transform = 'rotate(180deg)';
        }
    }
</script>

<?php include_once 'includes/templates/footer.php'; ?>