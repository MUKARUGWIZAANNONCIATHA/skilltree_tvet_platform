<?php
/**
 * Available Modules - Student can browse and enroll
 * Path: /student/modules.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['student']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];

// Get enrolled module IDs
$stmt = $pdo->prepare("SELECT module_id FROM student_enrollments WHERE student_id = ?");
$stmt->execute([$userId]);
$enrolledModules = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $moduleId = intval($_POST['module_id']);
    
    // Check if already enrolled
    if (!in_array($moduleId, $enrolledModules)) {
        $stmt = $pdo->prepare("INSERT INTO student_enrollments (student_id, module_id, status, enrollment_date) VALUES (?, ?, 'enrolled', NOW())");
        $stmt->execute([$userId, $moduleId]);
        
        // Initialize subtopic progress for this module
        $stmt = $pdo->prepare("
            INSERT INTO student_subtopic_progress (student_id, subtopic_id, status)
            SELECT ?, subtopic_id, 'available'
            FROM subtopics s
            JOIN topics t ON s.topic_id = t.topic_id
            JOIN indicative_contents ic ON t.ic_id = ic.ic_id
            JOIN learning_outcomes lo ON ic.outcome_id = lo.outcome_id
            WHERE lo.module_id = ?
        ");
        $stmt->execute([$userId, $moduleId]);
        
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Successfully enrolled in module!'];
    }
    
    header('Location: /student/modules.php');
    exit();
}

// Get available modules (not enrolled)
if (empty($enrolledModules)) {
    $stmt = $pdo->query("SELECT * FROM modules WHERE status = 'published' ORDER BY module_code");
} else {
    $placeholders = str_repeat('?,', count($enrolledModules) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE status = 'published' AND module_id NOT IN ($placeholders) ORDER BY module_code");
    $stmt->execute($enrolledModules);
}
$availableModules = $stmt->fetchAll();

// Get enrolled modules with progress
if (!empty($enrolledModules)) {
    $placeholders = str_repeat('?,', count($enrolledModules) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT m.*, se.overall_progress, se.enrollment_date
        FROM modules m
        JOIN student_enrollments se ON m.module_id = se.module_id
        WHERE m.module_id IN ($placeholders) AND se.student_id = ?
        ORDER BY se.enrollment_date DESC
    ");
    $params = array_merge($enrolledModules, [$userId]);
    $stmt->execute($params);
    $myModules = $stmt->fetchAll();
} else {
    $myModules = [];
}

include_once '../includes/templates/header.php';

// Display message if exists
if (isset($_SESSION['message'])) {
    $msg = $_SESSION['message'];
    echo "<div class='alert alert-{$msg['type']}'><i class='fas fa-" . ($msg['type'] == 'success' ? 'check-circle' : 'exclamation-circle') . "'></i> {$msg['text']}</div>";
    unset($_SESSION['message']);
}
?>

<div class="modules-container">
    <div class="page-header">
        <h1><i class="fas fa-book"></i> My Learning</h1>
        <p>Browse and enroll in modules</p>
    </div>

    <!-- My Modules Section -->
    <?php if(!empty($myModules)): ?>
        <div class="section">
            <h2><i class="fas fa-play-circle"></i> My Current Modules</h2>
            <div class="modules-grid">
                <?php foreach($myModules as $module): ?>
                <div class="module-card enrolled">
                    <div class="module-image">
                        <div class="module-code"><?php echo htmlspecialchars($module['module_code']); ?></div>
                    </div>
                    <div class="module-info">
                        <h3><?php echo htmlspecialchars($module['module_name']); ?></h3>
                        <div class="module-meta">
                            <span><i class="fas fa-star"></i> <?php echo $module['credits']; ?> credits</span>
                            <span><i class="fas fa-chart-line"></i> Level <?php echo $module['rqf_level']; ?></span>
                        </div>
                        <div class="progress-section">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $module['overall_progress']; ?>%"></div>
                            </div>
                            <span class="progress-text"><?php echo $module['overall_progress']; ?>% complete</span>
                        </div>
                        <div class="module-actions">
                            <a href="/student/skill-tree.php?module_id=<?php echo $module['module_id']; ?>" class="btn-continue">
                                <i class="fas fa-play"></i> Continue Learning
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Available Modules Section -->
    <div class="section">
        <h2><i class="fas fa-plus-circle"></i> Available Modules</h2>
        <?php if(empty($availableModules)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>All caught up!</h3>
                <p>You've enrolled in all available modules. Keep learning!</p>
            </div>
        <?php else: ?>
            <div class="modules-grid">
                <?php foreach($availableModules as $module): ?>
                <div class="module-card available">
                    <div class="module-image">
                        <div class="module-code"><?php echo htmlspecialchars($module['module_code']); ?></div>
                    </div>
                    <div class="module-info">
                        <h3><?php echo htmlspecialchars($module['module_name']); ?></h3>
                        <div class="module-meta">
                            <span><i class="fas fa-star"></i> <?php echo $module['credits']; ?> credits</span>
                            <span><i class="fas fa-chart-line"></i> Level <?php echo $module['rqf_level']; ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo $module['total_learning_hours']; ?> hours</span>
                        </div>
                        <p class="module-desc"><?php echo htmlspecialchars(substr($module['description'] ?? 'No description available', 0, 120)); ?>...</p>
                        <form method="POST" action="">
                            <input type="hidden" name="module_id" value="<?php echo $module['module_id']; ?>">
                            <button type="submit" name="enroll" class="btn-enroll">
                                <i class="fas fa-plus"></i> Enroll Now
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.modules-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 24px;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 28px;
    color: #1a1a2e;
    margin-bottom: 5px;
}

.section {
    margin-bottom: 40px;
}

.section h2 {
    font-size: 22px;
    color: #1a1a2e;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e0e0e0;
}

.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
}

.module-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.module-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.module-image {
    background: linear-gradient(135deg, #667eea, #764ba2);
    padding: 30px;
    text-align: center;
}

.module-code {
    background: rgba(255,255,255,0.2);
    display: inline-block;
    padding: 8px 20px;
    border-radius: 30px;
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.module-info {
    padding: 20px;
}

.module-info h3 {
    font-size: 18px;
    margin-bottom: 10px;
    color: #1a1a2e;
}

.module-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 12px;
    font-size: 12px;
    color: #666;
}

.module-meta i {
    margin-right: 4px;
}

.module-desc {
    font-size: 13px;
    color: #666;
    line-height: 1.5;
    margin-bottom: 15px;
}

.progress-section {
    margin-top: 12px;
}

.progress-bar {
    background: #e0e0e0;
    border-radius: 10px;
    height: 6px;
    overflow: hidden;
}

.progress-fill {
    background: linear-gradient(90deg, #667eea, #764ba2);
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s;
}

.progress-text {
    font-size: 11px;
    color: #666;
    margin-top: 5px;
    display: block;
}

.module-actions {
    margin-top: 15px;
}

.btn-continue, .btn-enroll {
    display: inline-block;
    width: 100%;
    padding: 10px;
    text-align: center;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-continue {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-enroll {
    background: #4CAF50;
    color: white;
}

.btn-continue:hover, .btn-enroll:hover {
    transform: scale(1.02);
}

.empty-state {
    text-align: center;
    padding: 60px;
    background: white;
    border-radius: 20px;
}

.empty-state i {
    font-size: 64px;
    color: #4CAF50;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 20px;
    color: #666;
    margin-bottom: 10px;
}

.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border-left: 4px solid #4CAF50;
}

@media (max-width: 900px) {
    .modules-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include_once '../includes/templates/footer.php'; ?>
