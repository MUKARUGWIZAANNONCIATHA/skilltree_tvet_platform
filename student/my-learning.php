<?php
/**
 * Student My Learning Page
 * Path: /student/my-learning.php
 */
require_once '../config/database.php';
require_once '../includes/auth/session-check.php';
include_once '../includes/templates/header.php';

$studentId = $_SESSION['user_id'];

// Get all enrolled modules without ordering by non-existent column
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
    echo '<div class="container" style="text-align:center; padding:3rem;">
            <h2>No modules found</h2>
            <p>You are not enrolled in any module yet. Please select a trade to start learning.</p>
            <a href="/student/select-trade.php" class="btn-primary">Select Trade</a>
          </div>';
    include_once '../includes/templates/footer.php';
    exit;
}

// Calculate progress for each module and overall
$totalProgress = 0;
$modulesWithProgress = [];
foreach ($modules as $module) {
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

    // Completed topics where quiz passed
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
    $module['progress_percent'] = $progress;
    $totalProgress += $progress;
    $modulesWithProgress[] = $module;
}
$overallProgress = count($modulesWithProgress) ? round($totalProgress / count($modulesWithProgress)) : 0;

$tradeName = $modulesWithProgress[0]['trade'] ?? '';
$completedModulesCount = 0;
foreach ($modulesWithProgress as $m) {
    if ($m['progress_percent'] >= 100) $completedModulesCount++;
}
?>

<div class="my-learning-container">
    <div class="page-header">
        <h1><i class="fas fa-book-open"></i> My Learning</h1>
        <p>Your enrolled modules and progress</p>
    </div>

    <div class="progress-summary">
        <div class="summary-card">
            <div class="summary-value"><?= count($modulesWithProgress) ?></div>
            <div class="summary-label">Modules Enrolled</div>
        </div>
        <div class="summary-card">
            <div class="summary-value"><?= $overallProgress ?>%</div>
            <div class="summary-label">Overall Progress</div>
        </div>
        <div class="summary-card">
            <div class="summary-value"><?= $completedModulesCount ?></div>
            <div class="summary-label">Completed Modules</div>
        </div>
    </div>

    <div class="modules-list">
        <?php foreach ($modulesWithProgress as $module): ?>
            <div class="module-card">
                <div class="module-header">
                    <div>
                        <span class="module-code"><?= htmlspecialchars($module['module_code']) ?></span>
                        <h3><?= htmlspecialchars($module['module_name']) ?></h3>
                    </div>
                    <div class="module-status">
                        <?php if ($module['status'] === 'locked'): ?>
                            <span class="badge locked"><i class="fas fa-lock"></i> Locked</span>
                        <?php elseif ($module['progress_percent'] >= 100): ?>
                            <span class="badge completed"><i class="fas fa-check-circle"></i> Completed</span>
                        <?php else: ?>
                            <span class="badge in-progress"><i class="fas fa-spinner"></i> In Progress</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="progress-section">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $module['progress_percent'] ?>%;"></div>
                    </div>
                    <span class="progress-text"><?= $module['progress_percent'] ?>%</span>
                </div>
                <?php if ($module['status'] !== 'locked'): ?>
                    <a href="/student/module.php?module_id=<?= $module['module_id'] ?>" class="btn-access">
                        Continue Learning <i class="fas fa-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <button class="btn-access locked" disabled><i class="fas fa-lock"></i> Complete previous module first</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    /* same styles as before – unchanged */
    .my-learning-container {
        max-width: 1000px;
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
    .progress-summary {
        display: flex;
        gap: 1.5rem;
        justify-content: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    .summary-card {
        background: white;
        border-radius: 1rem;
        padding: 1.2rem 2rem;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        flex: 1;
        min-width: 150px;
    }
    .summary-value {
        font-size: 2rem;
        font-weight: 700;
        color: #1a5f7a;
    }
    .modules-list {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
    }
    .module-card {
        background: white;
        border-radius: 1.2rem;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: 0.2s;
    }
    .module-card:hover {
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .module-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }
    .module-code {
        background: #eef2fa;
        padding: 0.2rem 0.6rem;
        border-radius: 1rem;
        font-size: 0.7rem;
        font-weight: 600;
        color: #2c7da0;
    }
    .module-header h3 {
        margin-top: 0.3rem;
        font-size: 1.2rem;
    }
    .badge {
        padding: 0.3rem 0.8rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge.locked {
        background: #f0f4f8;
        color: #8aaec0;
    }
    .badge.completed {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .badge.in-progress {
        background: #fff3e0;
        color: #c76f1c;
    }
    .progress-section {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin: 1rem 0;
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
        width: 0%;
    }
    .btn-access {
        display: inline-block;
        background: #2c7da0;
        color: white;
        padding: 0.5rem 1.2rem;
        border-radius: 2rem;
        text-decoration: none;
        font-weight: 500;
        margin-top: 0.5rem;
        transition: 0.2s;
    }
    .btn-access:hover {
        background: #1e5f7a;
        transform: translateY(-2px);
    }
    .btn-access.locked {
        background: #cbd5e1;
        cursor: not-allowed;
    }
    @media (max-width: 700px) {
        .progress-summary {
            flex-direction: column;
        }
        .module-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
    }
</style>

<?php include_once '../includes/templates/footer.php'; ?>