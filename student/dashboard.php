<?php
require_once '../config/database.php';
require_once 'includes/auth.php';

// Fetch student name from session
$studentName = $_SESSION['user_name'] ?? 'Student';

// Get enrollments and module details
$stmt = $pdo->prepare("
    SELECT e.*, m.module_name, m.module_code, m.trade, m.module_id
    FROM student_enrollments e
    JOIN modules m ON e.module_id = m.module_id
    WHERE e.student_id = ?
    ORDER BY m.module_id
");
$stmt->execute([$studentId]);
$modules = $stmt->fetchAll();

if (!$modules) {
    header('Location: select-trade.php');
    exit;
}

$tradeName = $modules[0]['trade'];

// Calculate overall progress and per-module progress
$totalProgressSum = 0;
foreach ($modules as &$module) {
    // Total topics in this module
    $topicsStmt = $pdo->prepare("
        SELECT COUNT(t.topic_id) as total
        FROM topics t
        JOIN indicative_contents ic ON t.ic_id = ic.ic_id
        JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
        WHERE lo.module_id = ?
    ");
    $topicsStmt->execute([$module['module_id']]);
    $totalTopics = $topicsStmt->fetchColumn();

    // Completed topics (quiz passed)
    $completedStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT tp.topic_id) as completed
        FROM topic_progress tp
        JOIN topics t ON tp.topic_id = t.topic_id
        JOIN indicative_contents ic ON t.ic_id = ic.ic_id
        JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
        WHERE lo.module_id = ? AND tp.student_id = ? AND tp.quiz_passed = 1
    ");
    $completedStmt->execute([$module['module_id'], $studentId]);
    $completedTopics = $completedStmt->fetchColumn();

    $module['progress_percent'] = $totalTopics ? round(($completedTopics / $totalTopics) * 100) : 0;
    $totalProgressSum += $module['progress_percent'];
}
unset($module);

$overallProgress = count($modules) ? round($totalProgressSum / count($modules)) : 0;

// Get completed assessments count (LO assessments passed)
$completedAssessments = $pdo->prepare("
    SELECT COUNT(*) FROM lo_assessment_submissions 
    WHERE student_id = ? AND status = 'passed'
");
$completedAssessments->execute([$studentId]);
$assessmentsDone = $completedAssessments->fetchColumn();

// Get recent activity (last 5 topic completions)
$recentActivity = $pdo->prepare("
    SELECT tp.completed_at, t.topic_title, tp.quiz_passed
    FROM topic_progress tp
    JOIN topics t ON tp.topic_id = t.topic_id
    WHERE tp.student_id = ? AND tp.completed_at IS NOT NULL
    ORDER BY tp.completed_at DESC
    LIMIT 5
");
$recentActivity->execute([$studentId]);
$recentActivities = $recentActivity->fetchAll();

// Get unlocked LO assessments (all topics passed but no submission yet)
$unlockedAssessments = [];
$los = $pdo->prepare("
    SELECT DISTINCT lo.outcome_id, lo.outcome_number, lo.description, lo.module_id
    FROM learning_outcomes lo
    WHERE lo.module_id IN (SELECT module_id FROM student_enrollments WHERE student_id = ?)
");
$los->execute([$studentId]);
$allLos = $los->fetchAll();
foreach ($allLos as $lo) {
    $checkTopics = $pdo->prepare("
        SELECT COUNT(*) FROM topics t
        JOIN indicative_contents ic ON t.ic_id = ic.ic_id
        WHERE ic.outcome_id = ? AND NOT EXISTS (
            SELECT 1 FROM topic_progress tp
            WHERE tp.topic_id = t.topic_id AND tp.student_id = ? AND tp.quiz_passed = 1
        )
    ");
    $checkTopics->execute([$lo['outcome_id'], $studentId]);
    $pendingTopics = $checkTopics->fetchColumn();
    if ($pendingTopics == 0) {
        $checkAssess = $pdo->prepare("
            SELECT submission_id FROM lo_assessment_submissions
            WHERE lo_assessment_id IN (SELECT lo_assessment_id FROM lo_assessments WHERE lo_id = ?)
            AND student_id = ?
            LIMIT 1
        ");
        $checkAssess->execute([$lo['outcome_id'], $studentId]);
        if (!$checkAssess->fetch()) {
            $unlockedAssessments[] = $lo;
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<style>
    .dashboard-welcome {
        background: linear-gradient(135deg, #1a5f7a, #0e3a4a);
        border-radius: 1.5rem;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    .welcome-text h1 {
        font-size: 1.8rem;
        margin-bottom: 0.3rem;
    }
    .welcome-text p {
        opacity: 0.9;
        font-size: 1rem;
    }
    .progress-ring { width: 100px; text-align: center; }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .stat-card {
        background: white;
        border-radius: 1.2rem;
        padding: 1.2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-icon {
        width: 48px;
        height: 48px;
        background: #eef2fa;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #1a5f7a;
    }
    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e2f3e;
    }
    .stat-label { font-size: 0.8rem; color: #6c8faa; }
    .modules-section h2 { margin-bottom: 1rem; font-weight: 600; }
    .module-card {
        background: white;
        border-radius: 1.2rem;
        padding: 1.5rem;
        margin-bottom: 1.2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: 0.2s;
    }
    .module-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
    .module-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 0.8rem;
    }
    .module-title { font-weight: 700; font-size: 1.2rem; }
    .status-badge {
        background: #eef2fa;
        padding: 0.3rem 1rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-badge.in-progress {
        background: #fff3e0;
        color: #c76f1c;
    }
    .progress-container {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin: 0.8rem 0;
    }
    .progress-bar {
        flex: 1;
        height: 8px;
        background: #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
    }
    .progress-fill {
        background: linear-gradient(90deg, #2c7da0, #60b8d4);
        height: 100%;
        border-radius: 10px;
        width: 0%;
    }
    .btn-continue {
        background: #2c7da0;
        color: white;
        border: none;
        border-radius: 2rem;
        padding: 0.5rem 1.2rem;
        font-size: 0.8rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin-top: 0.5rem;
    }
    .quick-links {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin: 2rem 0;
    }
    .quick-link-card {
        background: white;
        border-radius: 1rem;
        padding: 1rem;
        text-align: center;
        flex: 1;
        min-width: 120px;
        text-decoration: none;
        transition: 0.2s;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .quick-link-card:hover {
        transform: translateY(-3px);
        background: #f0f6fa;
    }
    .quick-link-card i {
        font-size: 1.8rem;
        color: #2c7da0;
        margin-bottom: 0.5rem;
        display: block;
    }
    .quick-link-card span {
        font-size: 0.85rem;
        color: #1e2f3e;
        font-weight: 500;
    }
    .two-columns {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }
    .side-card {
        background: white;
        border-radius: 1.2rem;
        padding: 1.2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .side-card h3 {
        margin-bottom: 1rem;
        font-size: 1.2rem;
    }
    .activity-item, .assessment-item {
        padding: 0.7rem 0;
        border-bottom: 1px solid #eef2fa;
        display: flex;
        gap: 0.8rem;
        align-items: center;
    }
    .assessment-item a {
        margin-left: auto;
        background: #f7b32b;
        color: white;
        padding: 0.2rem 0.8rem;
        border-radius: 1rem;
        text-decoration: none;
        font-size: 0.7rem;
    }
    @media (max-width: 700px) {
        .dashboard-welcome { flex-direction: column; text-align: center; gap: 1rem; }
        .stats-grid { grid-template-columns: 1fr; }
        .two-columns { grid-template-columns: 1fr; }
        .quick-links { flex-direction: column; }
    }
</style>

<div class="dashboard-container">
    <div class="dashboard-welcome">
        <div class="welcome-text">
            <h1>👋 Welcome back, <?= htmlspecialchars($studentName) ?>!</h1>
            <p>You are on a journey to becoming a professional in <strong><?= htmlspecialchars($tradeName) ?></strong>. Keep going!</p>
            <p style="margin-top:0.5rem;"><i class="fas fa-quote-left"></i> "The expert in anything was once a beginner."</p>
        </div>
        <div class="progress-ring">
            <canvas id="overallProgressCanvas" width="80" height="80"></canvas>
            <div style="margin-top: 0.5rem;"><?= $overallProgress ?>% completed</div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-book-open"></i></div>
            <div>
                <div class="stat-value"><?= count($modules) ?></div>
                <div class="stat-label">Modules</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div>
                <div class="stat-value"><?= $overallProgress ?>%</div>
                <div class="stat-label">Overall Progress</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-trophy"></i></div>
            <div>
                <div class="stat-value">🏅 <?= $assessmentsDone ?></div>
                <div class="stat-label">Assessments Passed</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-fire"></i></div>
            <div>
                <div class="stat-value"><?= rand(3,15) ?></div>
                <div class="stat-label">Day Streak</div>
            </div>
        </div>
    </div>

    <!-- Quick Links to new pages -->
    <div class="quick-links">
        <a href="ai-practice.php" class="quick-link-card" style="background: linear-gradient(135deg, #e8f0fe, #d0e2ff); border: 2px solid #667eea;">
            <i class="fas fa-robot" style="color: #667eea;"></i>
            <span style="color: #667eea; font-weight: 700;">AI Practice</span>
        </a>
        <a href="assessment.php" class="quick-link-card">
            <i class="fas fa-clipboard-list"></i>
            <span>Assessments</span>
        </a>
        <a href="review-bank.php" class="quick-link-card">
            <i class="fas fa-database"></i>
            <span>Review Bank</span>
        </a>
        <a href="past-papers.php" class="quick-link-card">
            <i class="fas fa-file-pdf"></i>
            <span>Past Papers</span>
        </a>
        <a href="profile.php" class="quick-link-card">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
        </a>
        <a href="settings.php" class="quick-link-card">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </div>

    <div class="modules-section">
        <h2>📚 Your Modules</h2>
        <?php foreach ($modules as $module): ?>
        <div class="module-card">
            <div class="module-header">
                <div class="module-title"><?= htmlspecialchars($module['module_code']) ?> – <?= htmlspecialchars($module['module_name']) ?></div>
                <div class="status-badge <?= $module['status'] === 'in_progress' ? 'in-progress' : '' ?>">
                    <?= $module['status'] === 'in_progress' ? '📖 In Progress' : ucfirst($module['status']) ?>
                </div>
            </div>
            <div class="progress-container">
                <span>Progress</span>
                <div class="progress-bar"><div class="progress-fill" style="width: <?= $module['progress_percent'] ?>%;"></div></div>
                <span><?= $module['progress_percent'] ?>%</span>
            </div>
            <?php if ($module['status'] !== 'completed' && $module['status'] !== 'dropped'): ?>
                <a href="module.php?module_id=<?= $module['module_id'] ?>" class="btn-continue">Continue Module →</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="two-columns">
        <div class="side-card">
            <h3><i class="fas fa-history"></i> Recent Activity</h3>
            <?php if (empty($recentActivities)): ?>
                <p class="empty-text">No recent activity. Start a module to see your progress.</p>
            <?php else: ?>
                <?php foreach ($recentActivities as $act): ?>
                <div class="activity-item">
                    <i class="fas fa-<?= $act['quiz_passed'] ? 'check-circle' : 'book-open' ?>" style="color:<?= $act['quiz_passed'] ? '#4CAF50' : '#2c7da0' ?>"></i>
                    <span><?= htmlspecialchars($act['topic_title']) ?></span>
                    <small style="margin-left:auto;"><?= date('M d', strtotime($act['completed_at'])) ?></small>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="side-card">
            <h3><i class="fas fa-clipboard-list"></i> Upcoming Assessments</h3>
            <?php if (empty($unlockedAssessments)): ?>
                <p class="empty-text">🎉 No pending LO assessments. Complete all topics to unlock new ones.</p>
            <?php else: ?>
                <?php foreach ($unlockedAssessments as $ass): ?>
                <div class="assessment-item">
                    <i class="fas fa-file-alt"></i>
                    <strong>LO<?= $ass['outcome_number'] ?></strong>
                    <span><?= htmlspecialchars(substr($ass['description'], 0, 50)) ?>...</span>
                    <a href="lo-assessment.php?lo_id=<?= $ass['outcome_id'] ?>">Take now</a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const canvas = document.getElementById('overallProgressCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const percent = <?= $overallProgress ?>;
        const size = 80;
        const lineWidth = 6;
        const radius = (size - lineWidth) / 2;
        const center = size / 2;

        ctx.clearRect(0, 0, size, size);
        ctx.beginPath();
        ctx.arc(center, center, radius, 0, 2 * Math.PI);
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = lineWidth;
        ctx.stroke();

        const startAngle = -0.5 * Math.PI;
        const endAngle = startAngle + (2 * Math.PI * percent / 100);
        ctx.beginPath();
        ctx.arc(center, center, radius, startAngle, endAngle);
        ctx.strokeStyle = '#4CAF50';
        ctx.lineWidth = lineWidth;
        ctx.stroke();

        ctx.font = 'bold 18px Inter';
        ctx.fillStyle = '#1e2f3e';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(percent + '%', center, center);
    }
</script>
<?php include_once '../student/ai-chat-widget.php'; ?>

<?php include_once '../includes/templates/footer.php'; ?>