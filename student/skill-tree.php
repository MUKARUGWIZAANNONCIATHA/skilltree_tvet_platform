<?php
/**
 * Student Skill Tree - View Curriculum Structure
 * Path: /student/skill-tree.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['student']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$moduleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$userId = $_SESSION['user_id'];

// If no module selected, show list of enrolled modules
if ($moduleId <= 0) {
    $stmt = $pdo->prepare("
        SELECT m.module_id, m.module_code, m.module_name, m.credits, m.rqf_level, 
               se.overall_progress, se.status
        FROM modules m
        JOIN student_enrollments se ON m.module_id = se.module_id
        WHERE se.student_id = ? AND se.status IN ('enrolled', 'in_progress')
        ORDER BY se.enrollment_date DESC
    ");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
    
    include_once '../includes/templates/header.php';
    ?>
    <div class="skill-tree-container">
        <div class="page-header">
            <h1><i class="fas fa-tree"></i> My Skill Tree</h1>
            <p>Select a module to view your learning path</p>
        </div>
        
        <?php if(empty($modules)): ?>
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>No modules enrolled</h3>
                <p>You haven't enrolled in any modules yet. Browse available modules to start learning.</p>
                <a href="/student/modules.php" class="btn-primary">Browse Modules</a>
            </div>
        <?php else: ?>
            <div class="modules-grid">
                <?php foreach($modules as $module): ?>
                <a href="?module_id=<?php echo $module['module_id']; ?>" class="module-card">
                    <div class="module-code"><?php echo htmlspecialchars($module['module_code']); ?></div>
                    <div class="module-name"><?php echo htmlspecialchars($module['module_name']); ?></div>
                    <div class="module-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $module['overall_progress']; ?>%"></div>
                        </div>
                        <span class="progress-text"><?php echo $module['overall_progress']; ?>% complete</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <style>
    .skill-tree-container { max-width: 1200px; margin: 0 auto; padding: 30px 24px; }
    .modules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px; margin-top: 20px; }
    .module-card { background: white; border-radius: 20px; padding: 24px; text-decoration: none; display: block; transition: all 0.3s; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .module-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .module-code { font-size: 14px; color: #667eea; font-weight: 600; margin-bottom: 8px; }
    .module-name { font-size: 18px; color: #1a1a2e; font-weight: 600; margin-bottom: 12px; }
    .progress-bar { background: #e0e0e0; border-radius: 10px; height: 6px; overflow: hidden; }
    .progress-fill { background: linear-gradient(90deg, #667eea, #764ba2); height: 100%; border-radius: 10px; }
    .progress-text { font-size: 12px; color: #666; margin-top: 8px; display: block; }
    </style>
    <?php
    include_once '../includes/templates/footer.php';
    exit();
}

// Get module info
$stmt = $pdo->prepare("SELECT * FROM modules WHERE module_id = ?");
$stmt->execute([$moduleId]);
$module = $stmt->fetch();

if (!$module) {
    header('Location: /student/skill-tree.php');
    exit();
}

// Get student progress for this module
$stmt = $pdo->prepare("SELECT overall_progress, status FROM student_enrollments WHERE student_id = ? AND module_id = ?");
$stmt->execute([$userId, $moduleId]);
$enrollment = $stmt->fetch();

$overallProgress = $enrollment ? $enrollment['overall_progress'] : 0;

// Get learning outcomes with their structure
$stmt = $pdo->prepare("
    SELECT lo.*, 
           ic.ic_id, ic.ic_title, ic.ic_order,
           t.topic_id, t.topic_title, t.topic_order,
           s.subtopic_id, s.subtopic_title, s.subtopic_order,
           sd.detail_id, sd.detail_text, sd.detail_order,
           sp.status as progress_status
    FROM learning_outcomes lo
    LEFT JOIN indicative_contents ic ON lo.outcome_id = ic.outcome_id
    LEFT JOIN topics t ON ic.ic_id = t.ic_id
    LEFT JOIN subtopics s ON t.topic_id = s.topic_id
    LEFT JOIN subtopic_details sd ON s.subtopic_id = sd.subtopic_id
    LEFT JOIN student_subtopic_progress sp ON s.subtopic_id = sp.subtopic_id AND sp.student_id = ?
    WHERE lo.module_id = ?
    ORDER BY lo.order_position, ic.ic_order, t.topic_order, s.subtopic_order, sd.detail_order
");
$stmt->execute([$userId, $moduleId]);
$rows = $stmt->fetchAll();

// Build nested structure
$outcomes = [];
foreach ($rows as $row) {
    $outcomeId = $row['outcome_id'];
    if (!isset($outcomes[$outcomeId])) {
        $outcomes[$outcomeId] = [
            'id' => $row['outcome_id'],
            'number' => $row['outcome_number'],
            'description' => $row['description'],
            'hours' => $row['learning_hours'],
            'indicative_contents' => []
        ];
    }
    
    if ($row['ic_id'] && !isset($outcomes[$outcomeId]['indicative_contents'][$row['ic_id']])) {
        $outcomes[$outcomeId]['indicative_contents'][$row['ic_id']] = [
            'id' => $row['ic_id'],
            'title' => $row['ic_title'],
            'order' => $row['ic_order'],
            'topics' => []
        ];
    }
    
    if ($row['topic_id'] && isset($outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]) && !isset($outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]['topics'][$row['topic_id']])) {
        $outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]['topics'][$row['topic_id']] = [
            'id' => $row['topic_id'],
            'title' => $row['topic_title'],
            'order' => $row['topic_order'],
            'subtopics' => []
        ];
    }
    
    if ($row['subtopic_id'] && isset($outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]['topics'][$row['topic_id']]) && !isset($outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]['topics'][$row['topic_id']]['subtopics'][$row['subtopic_id']])) {
        $status = $row['progress_status'] ?? 'locked';
        $outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]['topics'][$row['topic_id']]['subtopics'][$row['subtopic_id']] = [
            'id' => $row['subtopic_id'],
            'title' => $row['subtopic_title'],
            'order' => $row['subtopic_order'],
            'status' => $status,
            'details' => []
        ];
    }
    
    if ($row['detail_id'] && isset($outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]['topics'][$row['topic_id']]['subtopics'][$row['subtopic_id']])) {
        $outcomes[$outcomeId]['indicative_contents'][$row['ic_id']]['topics'][$row['topic_id']]['subtopics'][$row['subtopic_id']]['details'][] = $row['detail_text'];
    }
}

include_once '../includes/templates/header.php';
?>

<div class="skill-tree-container">
    <div class="module-header">
        <div class="module-info">
            <h1><?php echo htmlspecialchars($module['module_name']); ?></h1>
            <p class="module-meta">
                <?php echo htmlspecialchars($module['module_code']); ?> | 
                Credits: <?php echo $module['credits']; ?> | 
                Level: <?php echo $module['rqf_level']; ?> |
                Hours: <?php echo $module['total_learning_hours']; ?>
            </p>
        </div>
        <div class="overall-progress">
            <div class="progress-circle">
                <svg width="80" height="80">
                    <circle cx="40" cy="40" r="34" fill="none" stroke="#e0e0e0" stroke-width="6"/>
                    <circle cx="40" cy="40" r="34" fill="none" stroke="#667eea" stroke-width="6" 
                            stroke-dasharray="213.628" stroke-dashoffset="<?php echo 213.628 * (1 - $overallProgress / 100); ?>"
                            transform="rotate(-90 40 40)"/>
                </svg>
                <span class="progress-value"><?php echo round($overallProgress); ?>%</span>
            </div>
            <div class="progress-label">Overall Progress</div>
        </div>
    </div>

    <div class="skill-tree">
        <?php foreach($outcomes as $outcome): ?>
            <div class="outcome-section">
                <div class="outcome-header">
                    <div class="outcome-info">
                        <h2>🎯 Learning Outcome <?php echo $outcome['number']; ?></h2>
                        <p class="outcome-desc"><?php echo htmlspecialchars($outcome['description']); ?></p>
                        <span class="outcome-hours">⏱️ <?php echo $outcome['hours']; ?> hours</span>
                    </div>
                </div>

                <div class="indicative-contents">
                    <?php foreach($outcome['indicative_contents'] as $ic): ?>
                        <div class="ic-block">
                            <div class="ic-title">
                                <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($ic['title']); ?>
                            </div>
                            
                            <div class="topics-list">
                                <?php foreach($ic['topics'] as $topic): ?>
                                    <div class="topic-block">
                                        <div class="topic-title">
                                            <i class="fas fa-book"></i> <?php echo htmlspecialchars($topic['title']); ?>
                                        </div>
                                        
                                        <div class="subtopics-list">
                                            <?php foreach($topic['subtopics'] as $subtopic): ?>
                                                <div class="subtopic-item <?php echo $subtopic['status']; ?>">
                                                    <div class="subtopic-header">
                                                        <span class="checkmark">✓</span>
                                                        <span class="subtopic-name"><?php echo htmlspecialchars($subtopic['title']); ?></span>
                                                        <span class="subtopic-status">
                                                            <?php if($subtopic['status'] == 'completed'): ?>
                                                                <i class="fas fa-check-circle" style="color: #4CAF50;"></i> Completed
                                                            <?php elseif($subtopic['status'] == 'viewed'): ?>
                                                                <i class="fas fa-eye" style="color: #2196F3;"></i> Viewed
                                                            <?php elseif($subtopic['status'] == 'available'): ?>
                                                                <i class="fas fa-play-circle" style="color: #ff9800;"></i> Available
                                                            <?php else: ?>
                                                                <i class="fas fa-lock" style="color: #999;"></i> Locked
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    
                                                    <?php if(!empty($subtopic['details'])): ?>
                                                        <ul class="details-list">
                                                            <?php foreach($subtopic['details'] as $detail): ?>
                                                                <li>• <?php echo htmlspecialchars($detail); ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($subtopic['status'] !== 'locked'): ?>
                                                        <a href="/student/learn.php?subtopic_id=<?php echo $subtopic['id']; ?>" class="btn-learn">
                                                            <i class="fas fa-play"></i> Start Learning
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.skill-tree-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 24px;
}

.module-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 24px;
    padding: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    color: white;
}

.module-info h1 {
    font-size: 28px;
    margin-bottom: 8px;
}

.module-meta {
    opacity: 0.9;
    font-size: 14px;
}

.progress-circle {
    position: relative;
    width: 80px;
    height: 80px;
}

.progress-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 18px;
    font-weight: bold;
}

.progress-label {
    text-align: center;
    margin-top: 8px;
    font-size: 12px;
    opacity: 0.8;
}

.outcome-section {
    background: white;
    border-radius: 20px;
    margin-bottom: 30px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.outcome-header {
    background: linear-gradient(135deg, #1e3a5f, #0f2440);
    padding: 20px 24px;
    color: white;
}

.outcome-info h2 {
    font-size: 20px;
    margin-bottom: 8px;
}

.outcome-desc {
    opacity: 0.9;
    font-size: 14px;
    margin-bottom: 8px;
}

.outcome-hours {
    font-size: 12px;
    opacity: 0.7;
}

.indicative-contents {
    padding: 24px;
}

.ic-block {
    margin-bottom: 24px;
    border-left: 3px solid #667eea;
    padding-left: 20px;
}

.ic-title {
    font-size: 18px;
    font-weight: 600;
    color: #1e3a5f;
    margin-bottom: 16px;
}

.topics-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.topic-block {
    background: #f8f9fa;
    border-radius: 16px;
    padding: 16px;
}

.topic-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 12px;
    font-size: 16px;
}

.subtopics-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.subtopic-item {
    background: white;
    border-radius: 12px;
    padding: 16px;
    transition: all 0.3s;
    border: 1px solid #e0e0e0;
}

.subtopic-item.completed {
    border-left: 4px solid #4CAF50;
    background: #f9fff9;
}

.subtopic-item.viewed {
    border-left: 4px solid #2196F3;
}

.subtopic-item.available {
    border-left: 4px solid #ff9800;
}

.subtopic-item.locked {
    border-left: 4px solid #999;
    opacity: 0.7;
}

.subtopic-header {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 8px;
}

.checkmark {
    color: #4CAF50;
    font-weight: bold;
}

.subtopic-name {
    flex: 1;
    font-weight: 500;
}

.subtopic-status {
    font-size: 12px;
}

.details-list {
    list-style: none;
    padding-left: 28px;
    margin: 10px 0;
    color: #666;
    font-size: 13px;
}

.details-list li {
    margin: 5px 0;
}

.btn-learn {
    display: inline-block;
    margin-top: 12px;
    padding: 6px 16px;
    background: #667eea;
    color: white;
    border-radius: 20px;
    text-decoration: none;
    font-size: 12px;
    transition: all 0.3s;
}

.btn-learn:hover {
    background: #5a67d8;
    transform: scale(1.02);
}

@media (max-width: 900px) {
    .module-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    .subtopic-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<?php include_once '../includes/templates/footer.php'; ?> 
