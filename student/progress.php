<?php
/**
 * Student Progress Dashboard
 * Path: /student/progress.php
 */

require_once '../config/database.php';
require_once 'includes/auth.php';

// Get student's enrolled modules with progress
$stmt = $pdo->prepare("
    SELECT e.*, m.module_name, m.module_code, m.trade
    FROM student_enrollments e
    JOIN modules m ON e.module_id = m.module_id
    WHERE e.student_id = ? AND e.status != 'dropped'
    ORDER BY m.position_order, m.module_id
");
$stmt->execute([$studentId]);
$modules = $stmt->fetchAll();

if (empty($modules)) {
    echo '<div class="container"><p>You are not enrolled in any module yet. Go to Dashboard to select a trade.</p></div>';
    include 'includes/footer.php';
    exit;
}

$tradeName = $modules[0]['trade'];

// Compute progress per module, LO, topic
$moduleProgress = [];
$totalProgressSum = 0;
foreach ($modules as $module) {
    // Count total topics in module
    $topicsStmt = $pdo->prepare("
        SELECT COUNT(t.topic_id) as total
        FROM topics t
        JOIN indicative_contents ic ON t.ic_id = ic.ic_id
        JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
        WHERE lo.module_id = ?
    ");
    $topicsStmt->execute([$module['module_id']]);
    $totalTopics = $topicsStmt->fetchColumn();

    // Count completed topics (quiz passed)
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

    $progress = $totalTopics ? round(($completedTopics / $totalTopics) * 100) : 0;
    $moduleProgress[] = [
        'module_id' => $module['module_id'],
        'code' => $module['module_code'],
        'name' => $module['module_name'],
        'progress' => $progress,
        'status' => $module['status']
    ];
    $totalProgressSum += $progress;
}
$overallProgress = count($moduleProgress) ? round($totalProgressSum / count($moduleProgress)) : 0;

// Get recent activity (last 10 quiz completions / assessment submissions)
$recentStmt = $pdo->prepare("
    (SELECT 'quiz' as type, t.topic_title as title, tp.completed_at as date, tp.quiz_score as score
     FROM topic_progress tp
     JOIN topics t ON tp.topic_id = t.topic_id
     WHERE tp.student_id = ? AND tp.quiz_passed = 1 AND tp.completed_at IS NOT NULL
     ORDER BY tp.completed_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'lo_assessment' as type, la.title as title, las.submitted_at as date, las.percentage as score
     FROM lo_assessment_submissions las
     JOIN lo_assessments la ON las.lo_assessment_id = la.lo_assessment_id
     WHERE las.student_id = ? AND las.status = 'passed'
     ORDER BY las.submitted_at DESC LIMIT 3)
    ORDER BY date DESC LIMIT 10
");
$recentStmt->execute([$studentId, $studentId]);
$recentActivities = $recentStmt->fetchAll();

// Get LO breakdown for a specific module (if selected)
$selectedModuleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : ($modules[0]['module_id'] ?? 0);
$loBreakdown = [];
if ($selectedModuleId) {
    $loStmt = $pdo->prepare("
        SELECT lo.outcome_id, lo.outcome_number, lo.description,
               COUNT(t.topic_id) as total_topics,
               SUM(CASE WHEN tp.quiz_passed = 1 THEN 1 ELSE 0 END) as completed_topics
        FROM learning_outcomes lo
        LEFT JOIN indicative_contents ic ON lo.outcome_id = ic.outcome_id
        LEFT JOIN topics t ON ic.ic_id = t.ic_id
        LEFT JOIN topic_progress tp ON t.topic_id = tp.topic_id AND tp.student_id = ?
        WHERE lo.module_id = ?
        GROUP BY lo.outcome_id
        ORDER BY lo.outcome_number
    ");
    $loStmt->execute([$studentId, $selectedModuleId]);
    $loBreakdown = $loStmt->fetchAll();
}

include 'includes/header.php';
?>

<div class="progress-container">
    <div class="page-header">
        <h1><i class="fas fa-chart-line"></i> My Progress</h1>
        <p>Track your learning journey across all modules</p>
    </div>

    <!-- Overall Progress -->
    <div class="overall-card">
        <div class="overall-progress-ring">
            <canvas id="overallProgressCanvas" width="120" height="120"></canvas>
        </div>
        <div class="overall-stats">
            <h3>Overall Progress: <?= $overallProgress ?>%</h3>
            <p>Trade: <strong><?= htmlspecialchars($tradeName) ?></strong></p>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value"><?= count($moduleProgress) ?></span>
                    <span class="stat-label">Modules</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= count(array_filter($moduleProgress, fn($m) => $m['progress'] >= 100)) ?></span>
                    <span class="stat-label">Completed</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= count($recentActivities) ?></span>
                    <span class="stat-label">Recent Activities</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modules Progress Bars -->
    <div class="modules-progress">
        <h2><i class="fas fa-cubes"></i> Module Progress</h2>
        <?php foreach ($moduleProgress as $mp): ?>
            <div class="module-progress-item">
                <div class="module-info">
                    <span class="module-code"><?= htmlspecialchars($mp['code']) ?></span>
                    <span class="module-name"><?= htmlspecialchars($mp['name']) ?></span>
                    <span class="module-status <?= $mp['status'] ?>"><?= ucfirst($mp['status']) ?></span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $mp['progress'] ?>%;"></div>
                    </div>
                    <span class="progress-percent"><?= $mp['progress'] ?>%</span>
                </div>
                <a href="module.php?module_id=<?= $mp['module_id'] ?>" class="btn-view-module">View Module</a>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- LO Breakdown for Selected Module -->
    <div class="lo-breakdown">
        <h2><i class="fas fa-sitemap"></i> Learning Outcomes Breakdown</h2>
        <div class="module-selector">
            <label>Select Module:</label>
            <select id="moduleSelect" onchange="location.href='?module_id='+this.value">
                <?php foreach ($modules as $m): ?>
                    <option value="<?= $m['module_id'] ?>" <?= $selectedModuleId == $m['module_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['module_code']) ?> – <?= htmlspecialchars($m['module_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (empty($loBreakdown)): ?>
            <p>No learning outcomes data available for this module.</p>
        <?php else: ?>
            <div class="lo-list">
                <?php foreach ($loBreakdown as $lo): 
                    $loProgress = $lo['total_topics'] > 0 ? round(($lo['completed_topics'] / $lo['total_topics']) * 100) : 0;
                ?>
                    <div class="lo-item">
                        <div class="lo-header">
                            <span class="lo-number">LO<?= $lo['outcome_number'] ?></span>
                            <span class="lo-desc"><?= htmlspecialchars(substr($lo['description'], 0, 80)) ?>...</span>
                            <span class="lo-progress"><?= $loProgress ?>%</span>
                        </div>
                        <div class="progress-bar-small">
                            <div class="progress-fill-small" style="width: <?= $loProgress ?>%;"></div>
                        </div>
                        <div class="lo-stats">
                            <span><?= $lo['completed_topics'] ?>/<?= $lo['total_topics'] ?> topics completed</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h2><i class="fas fa-history"></i> Recent Achievements</h2>
        <?php if (empty($recentActivities)): ?>
            <p class="empty-message">No recent activities yet. Complete some quizzes or assessments to see progress.</p>
        <?php else: ?>
            <div class="activity-timeline">
                <?php foreach ($recentActivities as $act): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?php if ($act['type'] == 'quiz'): ?>
                                <i class="fas fa-puzzle-piece"></i>
                            <?php else: ?>
                                <i class="fas fa-clipboard-list"></i>
                            <?php endif; ?>
                        </div>
                        <div class="activity-details">
                            <div class="activity-title"><?= htmlspecialchars($act['title']) ?></div>
                            <div class="activity-meta">
                                <?= $act['type'] == 'quiz' ? 'Quiz passed' : 'LO assessment passed' ?> • Score: <?= round($act['score']) ?>% • <?= date('d M Y', strtotime($act['date'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .progress-container {
        max-width: 1100px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .page-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .overall-card {
        background: white;
        border-radius: 1.5rem;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 2rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .overall-progress-ring {
        text-align: center;
    }
    .overall-stats {
        flex: 1;
    }
    .stats-grid {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }
    .stat-item {
        background: #f8fafc;
        padding: 0.5rem 1rem;
        border-radius: 1rem;
        text-align: center;
    }
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a5f7a;
        display: block;
    }
    .modules-progress, .lo-breakdown, .recent-activity {
        background: white;
        border-radius: 1.2rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    h2 {
        font-size: 1.3rem;
        margin-bottom: 1rem;
        color: #1a5f7a;
    }
    .module-progress-item {
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #eef2f8;
    }
    .module-info {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.8rem;
        margin-bottom: 0.3rem;
    }
    .module-code {
        font-weight: 700;
        color: #2c7da0;
    }
    .module-status {
        font-size: 0.7rem;
        padding: 0.2rem 0.6rem;
        border-radius: 1rem;
        background: #eef2fa;
    }
    .module-status.in_progress { background: #fff3e0; color: #c76f1c; }
    .module-status.completed { background: #e8f5e9; color: #2e7d32; }
    .progress-bar-container {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin: 0.5rem 0;
    }
    .progress-bar {
        flex: 1;
        height: 8px;
        background: #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #2c7da0, #60b8d4);
        border-radius: 10px;
    }
    .btn-view-module {
        font-size: 0.75rem;
        color: #2c7da0;
        text-decoration: none;
    }
    .lo-list {
        margin-top: 1rem;
    }
    .lo-item {
        margin-bottom: 1rem;
    }
    .lo-header {
        display: flex;
        gap: 1rem;
        align-items: baseline;
        flex-wrap: wrap;
        margin-bottom: 0.3rem;
    }
    .lo-number {
        font-weight: 700;
        color: #1a5f7a;
    }
    .lo-desc {
        flex: 1;
        font-size: 0.85rem;
    }
    .progress-bar-small {
        height: 5px;
        background: #e2e8f0;
        border-radius: 5px;
        overflow: hidden;
        margin: 0.3rem 0;
    }
    .progress-fill-small {
        height: 100%;
        background: #4CAF50;
    }
    .activity-timeline {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }
    .activity-item {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    .activity-icon {
        width: 36px;
        height: 36px;
        background: #eef2fa;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2c7da0;
    }
    .activity-title {
        font-weight: 600;
    }
    .activity-meta {
        font-size: 0.7rem;
        color: #8aaec0;
    }
    .empty-message {
        text-align: center;
        padding: 2rem;
        color: #8aaec0;
    }
    @media (max-width: 700px) {
        .overall-card {
            flex-direction: column;
            text-align: center;
        }
        .stats-grid {
            justify-content: center;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Overall progress ring (canvas)
    const canvas = document.getElementById('overallProgressCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const percent = <?= $overallProgress ?>;
        const width = canvas.width;
        const height = canvas.height;
        const centerX = width / 2;
        const centerY = height / 2;
        const radius = width * 0.4;
        const startAngle = -0.5 * Math.PI;
        const endAngle = startAngle + (2 * Math.PI * percent / 100);
        
        // Background circle
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 12;
        ctx.stroke();
        
        // Progress arc
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, startAngle, endAngle);
        ctx.strokeStyle = '#2c7da0';
        ctx.lineWidth = 12;
        ctx.stroke();
        
        // Center text
        ctx.font = 'bold 20px "Inter", sans-serif';
        ctx.fillStyle = '#1e2f3e';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(percent + '%', centerX, centerY);
    }
</script>

<?php include 'includes/footer.php'; ?>