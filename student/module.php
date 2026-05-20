<?php
/**
 * Student Module Learning Page - Final Corrected for topic_id progress
 * Path: /student/module.php
 */
require_once '../config/database.php';
require_once 'includes/auth.php';

$moduleId = intval($_GET['module_id'] ?? 0);
if (!$moduleId) die('Module not found.');

// Verify enrollment
$enrollStmt = $pdo->prepare("SELECT * FROM student_enrollments WHERE student_id = ? AND module_id = ?");
$enrollStmt->execute([$studentId, $moduleId]);
$enroll = $enrollStmt->fetch();
if (!$enroll || ($enroll['status'] !== 'in_progress' && $enroll['status'] !== 'enrolled')) {
    die('This module is not available.');
}

// Get module info
$modStmt = $pdo->prepare("SELECT module_name, module_code FROM modules WHERE module_id = ?");
$modStmt->execute([$moduleId]);
$module = $modStmt->fetch();
$moduleTitle = $module['module_name'] ?? 'Module';
$moduleCode = $module['module_code'] ?? '';

// Build hierarchy: LO → IC → Topic → Subtopic
$sql = "
    SELECT 
        lo.outcome_id as lo_id, lo.outcome_number as lo_number, lo.description,
        ic.ic_id, ic.ic_title,
        t.topic_id, t.topic_title,
        s.subtopic_id, s.subtopic_title,
        tp.quiz_passed,
        tp.resource_read,
        tp.video_watched
    FROM learning_outcomes lo
    LEFT JOIN indicative_contents ic ON lo.outcome_id = ic.outcome_id
    LEFT JOIN topics t ON ic.ic_id = t.ic_id
    LEFT JOIN subtopics s ON t.topic_id = s.topic_id
    LEFT JOIN topic_progress tp ON t.topic_id = tp.topic_id AND tp.student_id = ?
    WHERE lo.module_id = ?
    ORDER BY lo.outcome_number, ic.ic_order, t.topic_order, s.subtopic_order
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$studentId, $moduleId]);
$rows = $stmt->fetchAll();

// Build tree
$tree = [];
foreach ($rows as $row) {
    if (!$row['lo_id']) continue;
    $loId = $row['lo_id'];
    if (!isset($tree[$loId])) {
        $tree[$loId] = [
            'number' => $row['lo_number'],
            'description' => $row['description'],
            'ics' => []
        ];
    }
    if ($row['ic_id']) {
        $icId = $row['ic_id'];
        if (!isset($tree[$loId]['ics'][$icId])) {
            $tree[$loId]['ics'][$icId] = [
                'title' => $row['ic_title'],
                'topics' => []
            ];
        }
        if ($row['topic_id']) {
            $topicId = $row['topic_id'];
            if (!isset($tree[$loId]['ics'][$icId]['topics'][$topicId])) {
                $tree[$loId]['ics'][$icId]['topics'][$topicId] = [
                    'id' => $topicId,
                    'title' => $row['topic_title'],
                    'quiz_passed' => (bool)($row['quiz_passed'] ?? false),
                    'resource_read' => (bool)($row['resource_read'] ?? false),
                    'video_watched' => (bool)($row['video_watched'] ?? false),
                    'subtopics' => []
                ];
            }
            if ($row['subtopic_id']) {
                $tree[$loId]['ics'][$icId]['topics'][$topicId]['subtopics'][] = [
                    'id' => $row['subtopic_id'],
                    'title' => $row['subtopic_title']
                ];
            }
        }
    }
}

// Determine if a topic is ready for quiz (all required resources studied)
// Here we can decide: if resource_read AND video_watched are both 1, topic quiz unlocks.
// You may adjust logic based on your requirements.
foreach ($tree as &$lo) {
    foreach ($lo['ics'] as &$ic) {
        foreach ($ic['topics'] as &$topic) {
            $topic['ready_for_quiz'] = ($topic['resource_read'] && $topic['video_watched']);
            // If you have multiple notes/videos, you would need a more complex check.
        }
    }
}
unset($lo, $ic, $topic);

// Compute topic completion (quiz passed) for LO progression
foreach ($tree as &$lo) {
    foreach ($lo['ics'] as &$ic) {
        foreach ($ic['topics'] as $topicId => $topic) {
            $ic['topic_passed'][$topicId] = $topic['quiz_passed'];
        }
    }
}
unset($lo, $ic);

// Determine LO unlock status (cascade)
$losStatus = [];
$prevUnlocked = true;
foreach ($tree as $loId => $lo) {
    $allTopicsCompleted = true;
    foreach ($lo['ics'] as $ic) {
        if (isset($ic['topic_passed'])) {
            foreach ($ic['topic_passed'] as $passed) {
                if (!$passed) $allTopicsCompleted = false;
            }
        } else {
            $allTopicsCompleted = false;
        }
    }
    $unlocked = $prevUnlocked;
    $losStatus[$loId] = [
        'number' => $lo['number'],
        'description' => $lo['description'],
        'allCompleted' => $allTopicsCompleted,
        'unlocked' => $unlocked
    ];
    $prevUnlocked = $unlocked && $allTopicsCompleted;
}

$moduleCompleted = true;
foreach ($losStatus as $lo) if (!$lo['allCompleted']) $moduleCompleted = false;

// Get LO assessment IDs (published) for each LO
$loAssessments = [];
$stmt = $pdo->prepare("SELECT lo_assessment_id, outcome_id FROM lo_assessments WHERE status = 'published'");
$stmt->execute();
while ($row = $stmt->fetch()) {
    $loAssessments[$row['outcome_id']] = $row['lo_assessment_id'];
}

// Get final exam ID for this module
$examId = null;
$stmt = $pdo->prepare("SELECT exam_id FROM exams WHERE module_id = ? AND status = 'published' LIMIT 1");
$stmt->execute([$moduleId]);
$examId = $stmt->fetchColumn();
?>
<?php include 'includes/header.php'; ?>

<style>
    /* same CSS as before – kept but abbreviated for space; keep your original styles */
    .module-container { display: flex; gap: 2rem; margin-top: 1rem; flex-wrap: wrap; }
    .learning-tree { flex: 1.2; min-width: 280px; background: white; border-radius: 1.5rem; padding: 1.5rem; box-shadow: 0 8px 20px rgba(0,0,0,0.05); height: fit-content; position: sticky; top: 90px; max-height: calc(100vh - 100px); overflow-y: auto; }
    .topic-content { flex: 2.5; min-width: 300px; background: white; border-radius: 1.5rem; padding: 1.5rem; box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
    .module-header { background: linear-gradient(135deg, #1a5f7a, #0e3a4a); border-radius: 1.5rem; padding: 1.5rem; color: white; margin-bottom: 1.5rem; }
    .lo-group { margin-bottom: 1.5rem; border-left: 3px solid #e2e8f0; padding-left: 0.8rem; }
    .lo-title { font-weight: 700; color: #1e5a7a; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 1rem; }
    .lo-title.locked { color: #8aaec0; }
    .ic-block { margin-left: 1rem; margin-top: 0.5rem; }
    .ic-title { font-weight: 600; color: #2c6b8a; margin-bottom: 0.3rem; font-size: 0.9rem; }
    .topic-item, .subtopic-item { padding: 0.4rem 0.6rem; margin-left: 1rem; border-radius: 0.6rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.85rem; }
    .subtopic-item { margin-left: 2rem; }
    .topic-item.unlocked, .subtopic-item.unlocked { cursor: pointer; }
    .topic-item.locked, .subtopic-item.locked { color: #b8cfdf; cursor: not-allowed; }
    .topic-item:hover.unlocked, .subtopic-item:hover.unlocked { background: #eef2fa; }
    .topic-status { font-size: 0.7rem; width: 1.2rem; }
    .status-passed { color: #4CAF50; }
    .status-pending { color: #ff9800; }
    .btn-assessment { background: #f7b32b; color: #1e2f3e; border-radius: 2rem; padding: 0.4rem 1rem; font-size: 0.8rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; margin-top: 0.5rem; }
    .btn-assessment.disabled { background: #ccc; cursor: not-allowed; pointer-events: none; }
    .btn-start-quiz { background: #4CAF50; color: white; border: none; padding: 0.4rem 1rem; border-radius: 2rem; font-size: 0.8rem; cursor: pointer; text-decoration: none; display: inline-block; }
    .placeholder { text-align: center; padding: 3rem; color: #8aaec0; }
    .loading { text-align: center; padding: 3rem; }
    @media (max-width: 800px) { .learning-tree { position: relative; top: 0; max-height: none; } .module-container { flex-direction: column; } }
</style>

<div class="module-header">
    <h1><i class="fas fa-cube"></i> <?= htmlspecialchars($moduleCode) ?> – <?= htmlspecialchars($moduleTitle) ?></h1>
    <p>Complete each topic (study its resources, then take quiz). Finish all topics in a LO to unlock LO assessment.</p>
</div>

<div class="module-container">
    <!-- LEFT: Learning Tree -->
    <div class="learning-tree">
        <h3><i class="fas fa-sitemap"></i> Learning Path</h3>
        <?php foreach ($tree as $loId => $lo):
            $status = $losStatus[$loId];
        ?>
        <div class="lo-group">
            <div class="lo-title <?= $status['unlocked'] ? 'unlocked' : 'locked' ?>">
                <i class="fas fa-<?= $status['unlocked'] ? ($status['allCompleted'] ? 'check-circle' : 'flag-checkered') : 'lock' ?>"></i>
                LO <?= $lo['number'] ?>: <?= htmlspecialchars($lo['description']) ?>
                <?php if ($status['allCompleted']): ?> <span style="font-size:0.7rem;">(completed)</span><?php endif; ?>
            </div>
            <?php foreach ($lo['ics'] as $ic): ?>
            <div class="ic-block">
                <div class="ic-title">📘 <?= htmlspecialchars($ic['title']) ?></div>
                <?php foreach ($ic['topics'] as $topic):
                    $topicCompleted = $topic['quiz_passed'];
                    $readyForQuiz = $topic['ready_for_quiz'];
                    $isLocked = !$status['unlocked'];
                ?>
                <div class="topic-item <?= $isLocked ? 'locked' : 'unlocked' ?>" data-topic-id="<?= $topic['id'] ?>" data-locked="<?= $isLocked ? '1' : '0' ?>">
                    <span class="topic-status">
                        <?php if ($topicCompleted): ?>
                            <i class="fas fa-check-circle status-passed"></i>
                        <?php else: ?>
                            <i class="fas fa-circle status-pending"></i>
                        <?php endif; ?>
                    </span>
                    <span><?= htmlspecialchars($topic['title']) ?></span>
                    <?php if (!$topicCompleted && !$isLocked && $readyForQuiz): ?>
                        <a href="quiz.php?type=topic&id=<?= $topic['id'] ?>" class="btn-start-quiz">Take Quiz</a>
                    <?php elseif (!$topicCompleted && !$isLocked && !$readyForQuiz): ?>
                        <span style="font-size:0.7rem; color:#888;">(complete resources first)</span>
                    <?php endif; ?>
                </div>
                <?php foreach ($topic['subtopics'] as $sub): ?>
                    <div class="subtopic-item <?= $isLocked ? 'locked' : 'unlocked' ?>" data-subtopic-id="<?= $sub['id'] ?>" data-locked="<?= $isLocked ? '1' : '0' ?>">
                        <span class="topic-status"><i class="fas fa-chevron-right"></i></span>
                        <span><?= htmlspecialchars($sub['title']) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
            <?php if ($status['unlocked'] && $status['allCompleted'] && isset($loAssessments[$loId])): ?>
                <div style="margin: 0.5rem 0 0 1rem;">
                    <a href="quiz.php?type=lo&id=<?= $loAssessments[$loId] ?>" class="btn-assessment">📝 LO Assessment</a>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- Module final assessment -->
        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #eef2f8; text-align: center;">
            <?php if ($moduleCompleted && $examId): ?>
                <a href="quiz.php?type=module&id=<?= $examId ?>" class="btn-assessment" style="background:#2c7da0; color:white;">🎓 Take Final Module Exam</a>
            <?php else: ?>
                <span class="btn-assessment disabled"><i class="fas fa-lock"></i> <?= !$moduleCompleted ? 'Complete all LOs first' : 'Exam not yet available' ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT: Dynamic Content Area (loads subtopic resources) -->
    <div class="topic-content" id="contentArea">
        <div class="placeholder">
            <i class="fas fa-hand-point-left"></i>
            <p>Select a subtopic from the left to start learning.</p>
        </div>
    </div>
</div>

<script>
    const subtopicItems = document.querySelectorAll('.subtopic-item');
    const contentArea = document.getElementById('contentArea');
    let currentSubtopicId = null;

    window.loadSubtopic = async function(subtopicId, element) {
        if (!subtopicId) return;
        subtopicItems.forEach(item => item.classList.remove('active'));
        if (element) element.classList.add('active');
        contentArea.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-pulse"></i> Loading content...</div>';
        try {
            const response = await fetch(`ajax/subtopic-content.php?subtopic_id=${subtopicId}`);
            const html = await response.text();
            contentArea.innerHTML = html;
        } catch {
            contentArea.innerHTML = '<div class="placeholder"><i class="fas fa-exclamation-triangle"></i><p>Error loading content. Please refresh.</p></div>';
        }
    };

    subtopicItems.forEach(item => {
        const isLocked = item.getAttribute('data-locked') === '1';
        if (!isLocked) {
            item.addEventListener('click', () => {
                const subtopicId = item.getAttribute('data-subtopic-id');
                if (subtopicId) window.loadSubtopic(subtopicId, item);
            });
        }
    });

    // Auto-load first unlocked subtopic if any
    const firstUnlocked = document.querySelector('.subtopic-item.unlocked');
    if (firstUnlocked) {
        const sid = firstUnlocked.getAttribute('data-subtopic-id');
        if (sid) window.loadSubtopic(sid, firstUnlocked);
    }
</script>

<?php include_once '../includes/templates/footer.php'; ?>