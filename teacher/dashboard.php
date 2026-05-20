<?php
/**
 * Teacher Dashboard - Complete with AI Tools & Review Content Integration
 * Path: /teacher/dashboard.php
 * Version: 2.4 - Added links to Assessment Builder & Exam Builder
 * Includes: teacher_modules, Bloom stats, recent content, activity log
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

// ==================== GET MODULES (ASSIGNED VIA teacher_modules) ====================
if ($role === 'admin') {
    $stmt = $pdo->query("
        SELECT m.*, 
               COUNT(DISTINCT lo.outcome_id) as outcomes_count,
               COUNT(DISTINCT se.student_id) as students_enrolled
        FROM modules m
        LEFT JOIN learning_outcomes lo ON m.module_id = lo.module_id
        LEFT JOIN student_enrollments se ON m.module_id = se.module_id
        GROUP BY m.module_id
        ORDER BY m.created_at DESC
    ");
    $modules = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               COUNT(DISTINCT lo.outcome_id) as outcomes_count,
               COUNT(DISTINCT se.student_id) as students_enrolled
        FROM modules m
        JOIN teacher_modules tm ON m.module_id = tm.module_id
        LEFT JOIN learning_outcomes lo ON m.module_id = lo.module_id
        LEFT JOIN student_enrollments se ON m.module_id = se.module_id
        WHERE tm.teacher_id = ?
        GROUP BY m.module_id
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
}

// ==================== MODULE STATISTICS ====================
$totalModules = count($modules);
$totalPublished = 0;
$totalDraft = 0;
$totalStudents = 0;
foreach ($modules as $module) {
    if ($module['status'] === 'published') $totalPublished++;
    if ($module['status'] === 'draft') $totalDraft++;
    $totalStudents += $module['students_enrolled'];
}

// ==================== REVIEW BANK STATISTICS ====================
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM review_bank WHERE created_by = ? AND status = 'approved'");
$stmt->execute([$userId]);
$totalReviewQuestions = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT module_id) as modules FROM review_bank WHERE created_by = ? AND status = 'approved'");
$stmt->execute([$userId]);
$modulesWithQuestions = $stmt->fetch()['modules'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM review_documents WHERE uploaded_by = ? AND status = 'published'");
$stmt->execute([$userId]);
$totalDocuments = $stmt->fetch()['total'] ?? 0;

// ==================== BLOOM STATISTICS ====================
$bloomStats = ['remember' => 0, 'understand' => 0, 'apply' => 0, 'analyze' => 0, 'evaluate' => 0, 'create' => 0];
$stmt = $pdo->prepare("SELECT bloom_level, COUNT(*) as count FROM review_bank WHERE created_by = ? AND status = 'approved' GROUP BY bloom_level");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll() as $row) {
    $bloomStats[$row['bloom_level']] = $row['count'];
}

// ==================== RECENT ACTIVITIES ====================
$stmt = $pdo->prepare("SELECT * FROM user_activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$userId]);
$recentActivities = $stmt->fetchAll();

// ==================== RECENT REVIEW QUESTIONS ====================
$stmt = $pdo->prepare("
    SELECT r.*, m.module_code, m.module_name 
    FROM review_bank r
    JOIN modules m ON r.module_id = m.module_id
    WHERE r.created_by = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC LIMIT 5
");
$stmt->execute([$userId]);
$recentQuestions = $stmt->fetchAll();

// ==================== RECENT DOCUMENTS ====================
$stmt = $pdo->prepare("
    SELECT d.*, m.module_code, m.module_name 
    FROM review_documents d
    JOIN modules m ON d.module_id = m.module_id
    WHERE d.uploaded_by = ? AND d.status = 'published'
    ORDER BY d.created_at DESC LIMIT 5
");
$stmt->execute([$userId]);
$recentDocs = $stmt->fetchAll();

// ==================== TIME AGO FUNCTION ====================
if (!function_exists('time_ago')) {
    function time_ago($timestamp) {
        if (empty($timestamp)) return "Unknown";
        $time_ago = strtotime($timestamp);
        $current_time = time();
        $time_difference = $current_time - $time_ago;
        $seconds = $time_difference;
        
        $minutes = round($seconds / 60);
        $hours = round($seconds / 3600);
        $days = round($seconds / 86400);
        $weeks = round($seconds / 604800);
        $months = round($seconds / 2629440);
        $years = round($seconds / 31553280);
        
        if ($seconds <= 60) return "Just Now";
        elseif ($minutes <= 60) return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
        elseif ($hours <= 24) return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
        elseif ($days <= 7) return ($days == 1) ? "yesterday" : "$days days ago";
        elseif ($weeks <= 4.3) return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
        elseif ($months <= 12) return ($months == 1) ? "1 month ago" : "$months months ago";
        else return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}

include_once '../includes/templates/header.php';
?>

<div class="teacher-dashboard">
    <div class="page-header">
        <h1><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'Teacher'); ?>!</p>
    </div>

    <!-- ==================== MAIN STATS GRID ==================== -->
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-book"></i></div><div class="stat-info"><h3><?php echo $totalModules; ?></h3><p>My Modules</p></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check-circle"></i></div><div class="stat-info"><h3><?php echo $totalPublished; ?></h3><p>Published</p></div></div>
        <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-pencil-alt"></i></div><div class="stat-info"><h3><?php echo $totalDraft; ?></h3><p>In Draft</p></div></div>
        <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-users"></i></div><div class="stat-info"><h3><?php echo $totalStudents; ?></h3><p>Total Students</p></div></div>
    </div>

    <!-- ==================== REVIEW CONTENT STATS ==================== -->
    <div class="stats-grid secondary">
        <div class="stat-card"><div class="stat-icon teal"><i class="fas fa-question-circle"></i></div><div class="stat-info"><h3><?php echo $totalReviewQuestions; ?></h3><p>Review Questions</p><small>In your bank</small></div></div>
        <div class="stat-card"><div class="stat-icon indigo"><i class="fas fa-file-alt"></i></div><div class="stat-info"><h3><?php echo $totalDocuments; ?></h3><p>Documents</p><small>Uploaded</small></div></div>
        <div class="stat-card"><div class="stat-icon cyan"><i class="fas fa-chart-pie"></i></div><div class="stat-info"><h3><?php echo $modulesWithQuestions; ?></h3><p>Modules with Content</p><small>Have review materials</small></div></div>
        <div class="stat-card"><div class="stat-icon pink"><i class="fas fa-graduation-cap"></i></div><div class="stat-info"><h3><?php echo $totalReviewQuestions + $totalDocuments; ?></h3><p>Total Resources</p><small>Questions + Docs</small></div></div>
    </div>

    <!-- ==================== QUICK ACTIONS (ALL TEACHER TOOLS) ==================== -->
    <div class="quick-actions">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="actions-grid">
            <a href="/teacher/upload-curriculum.php" class="action-card"><i class="fas fa-upload"></i><span>Upload Curriculum</span><small>Import module structure</small></a>
            <a href="/teacher/curriculum-editor.php" class="action-card"><i class="fas fa-edit"></i><span>Edit Curriculum</span><small>Manage LO/IC/Topics</small></a>
            <a href="/teacher/resources-manager.php" class="action-card"><i class="fas fa-book-open"></i><span>Resources Manager</span><small>Add notes, videos, links</small></a>
            <a href="/teacher/review-bank-builder.php" class="action-card highlight"><i class="fas fa-database"></i><span>Review Bank Builder</span><small>Create questions with AI</small></a>
            <a href="/teacher/library-upload.php" class="action-card">
    <i class="fas fa-book"></i>
    <span>Library Upload</span>
    <small>Upload resources for students</small>
</a>
            <a href="/teacher/ai-lesson-prep.php" class="action-card"><i class="fas fa-robot"></i><span>AI Lesson Prep</span><small>AI‑generated notes & videos</small></a>
            <a href="/teacher/quiz-builder.php" class="action-card"><i class="fas fa-pencil-alt"></i><span>Quiz Builder</span><small>Manual quiz preparation</small></a>
            <a href="/teacher/ai-quiz-generator.php" class="action-card"><i class="fas fa-robot"></i><span>AI Quiz Generator</span><small>AI-generated quiz questions</small></a>
            <a href="/teacher/assessment-builder.php" class="action-card"><i class="fas fa-clipboard-list"></i><span>Assessment Builder</span><small>Create LO assessments (A, B, C)</small></a>
            <a href="/teacher/exam-builder.php" class="action-card"><i class="fas fa-file-alt"></i><span>Exam Builder</span><small>Full exam with sections</small></a>
            <a href="/teacher/student-progress.php" class="action-card"><i class="fas fa-chart-line"></i><span>Student Progress</span><small>Track performance</small></a>
            <a href="/teacher/reports.php" class="action-card"><i class="fas fa-file-alt"></i><span>Generate Reports</span><small>Export student data</small></a>
        </div>
    </div>

    <!-- ==================== BLOOM'S TAXONOMY CHART ==================== -->
    <div class="bloom-section">
        <h2><i class="fas fa-chart-bar"></i> Your Question Bank - Bloom's Taxonomy Distribution</h2>
        <div class="bloom-chart-container">
            <?php 
            $totalBloom = array_sum($bloomStats);
            $bloomLevels = ['remember'=>'Remember','understand'=>'Understand','apply'=>'Apply','analyze'=>'Analyze','evaluate'=>'Evaluate','create'=>'Create'];
            $colors = ['remember'=>'#4CAF50','understand'=>'#2196F3','apply'=>'#FF9800','analyze'=>'#9C27B0','evaluate'=>'#F44336','create'=>'#009688'];
            foreach($bloomLevels as $key => $label):
                $count = $bloomStats[$key];
                $percentage = $totalBloom > 0 ? round(($count / $totalBloom) * 100) : 0;
            ?>
            <div class="bloom-bar">
                <div class="bloom-label"><?php echo $label; ?></div>
                <div class="bloom-bar-container">
                    <div class="bloom-bar-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $colors[$key]; ?>;">
                        <?php echo $percentage > 10 ? $count : ''; ?>
                    </div>
                </div>
                <div class="bloom-percent"><?php echo $percentage; ?>% (<?php echo $count; ?>)</div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if($totalBloom == 0): ?>
            <div class="bloom-empty"><p>No questions yet. <a href="/teacher/review-bank-builder.php">Create your first question</a> to see distribution.</p></div>
        <?php endif; ?>
    </div>

    <!-- ==================== MY MODULES ==================== -->
    <div class="modules-section">
        <div class="section-header">
            <h2><i class="fas fa-book-open"></i> My Modules</h2>
            <a href="/teacher/upload-curriculum.php" class="btn-add"><i class="fas fa-plus"></i> New Module</a>
        </div>

        <?php if(empty($modules)): ?>
            <div class="empty-state"><i class="fas fa-folder-open"></i><h3>No modules yet</h3><p>Upload your first curriculum to get started</p><a href="/teacher/upload-curriculum.php" class="btn-primary">Upload Curriculum</a></div>
        <?php else: ?>
            <div class="modules-grid">
                <?php foreach($modules as $module): ?>
                <div class="module-card">
                    <div class="module-header">
                        <span class="module-code"><?php echo htmlspecialchars($module['module_code']); ?></span>
                        <span class="module-status <?php echo $module['status']; ?>"><?php echo ucfirst($module['status']); ?></span>
                    </div>
                    <h3><?php echo htmlspecialchars($module['module_name']); ?></h3>
                    <div class="module-meta">
                        <span><i class="fas fa-star"></i> <?php echo $module['credits']; ?> credits</span>
                        <span><i class="fas fa-chart-line"></i> Level <?php echo $module['rqf_level']; ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo $module['total_learning_hours']; ?> hours</span>
                    </div>
                    <div class="module-stats">
                        <div class="stat"><span class="stat-value"><?php echo $module['outcomes_count']; ?></span><span class="stat-label">Learning Outcomes</span></div>
                        <div class="stat"><span class="stat-value"><?php echo $module['students_enrolled']; ?></span><span class="stat-label">Students</span></div>
                    </div>
                    <div class="module-actions">
                        <a href="/teacher/curriculum-editor.php?module_id=<?php echo $module['module_id']; ?>" class="btn-outline"><i class="fas fa-edit"></i> Edit</a>
                        <a href="/teacher/review-bank-builder.php?module_id=<?php echo $module['module_id']; ?>" class="btn-outline"><i class="fas fa-database"></i> Questions</a>
                        <a href="/teacher/review-documents.php?module_id=<?php echo $module['module_id']; ?>" class="btn-outline"><i class="fas fa-folder-open"></i> Documents</a>
                        <a href="/teacher/ai-quiz-generator.php?module_id=<?php echo $module['module_id']; ?>" class="btn-outline"><i class="fas fa-robot"></i> AI Quiz</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ==================== RECENT CONTENT (Questions + Documents) ==================== -->
    <div class="recent-content-section">
        <h2><i class="fas fa-history"></i> Recent Review Content</h2>
        <div class="content-grid">
            <!-- Recent Questions -->
            <div class="content-card">
                <div class="content-header"><h3><i class="fas fa-question-circle"></i> Recent Questions</h3><a href="/teacher/review-bank-builder.php" class="btn-link">View All →</a></div>
                <?php if(empty($recentQuestions)): ?>
                    <div class="empty-content"><p>No questions yet.</p><a href="/teacher/review-bank-builder.php" class="btn-small">Create Question</a></div>
                <?php else: ?>
                    <div class="content-list">
                        <?php foreach($recentQuestions as $q): ?>
                        <div class="content-item">
                            <div class="item-info"><span class="item-badge <?php echo $q['bloom_level']; ?>"><?php echo ucfirst($q['bloom_level']); ?></span><span class="item-title"><?php echo htmlspecialchars(substr($q['question_text'], 0, 70)); ?>...</span></div>
                            <div class="item-meta"><span><i class="fas fa-book"></i> <?php echo $q['module_code']; ?></span><span><i class="far fa-clock"></i> <?php echo time_ago($q['created_at']); ?></span></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Documents -->
            <div class="content-card">
                <div class="content-header"><h3><i class="fas fa-folder-open"></i> Recent Documents</h3><a href="/teacher/review-documents.php" class="btn-link">View All →</a></div>
                <?php if(empty($recentDocs)): ?>
                    <div class="empty-content"><p>No documents yet.</p><a href="/teacher/review-documents.php" class="btn-small">Upload Document</a></div>
                <?php else: ?>
                    <div class="content-list">
                        <?php foreach($recentDocs as $doc): ?>
                        <div class="content-item">
                            <div class="item-info"><i class="fas fa-file-<?php echo $doc['file_type'] == 'pdf' ? 'pdf' : 'word'; ?>"></i><span class="item-title"><?php echo htmlspecialchars($doc['document_title']); ?></span></div>
                            <div class="item-meta"><span><i class="fas fa-tag"></i> <?php echo str_replace('_', ' ', $doc['document_type']); ?></span><span><i class="fas fa-book"></i> <?php echo $doc['module_code']; ?></span><span><i class="far fa-clock"></i> <?php echo time_ago($doc['created_at']); ?></span></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ==================== RECENT ACTIVITIES ==================== -->
    <div class="recent-section">
        <h2><i class="fas fa-history"></i> Recent Activity</h2>
        <div class="activity-list">
            <?php if(empty($recentActivities)): ?>
                <div class="empty-activity"><p>No recent activities</p></div>
            <?php else: ?>
                <?php foreach($recentActivities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php $iconMap = ['create'=>'<i class="fas fa-plus-circle" style="color:#4CAF50;"></i>','update'=>'<i class="fas fa-edit" style="color:#FF9800;"></i>','delete'=>'<i class="fas fa-trash" style="color:#F44336;"></i>','upload'=>'<i class="fas fa-upload" style="color:#2196F3;"></i>','download'=>'<i class="fas fa-download" style="color:#9C27B0;"></i>']; echo $iconMap[$activity['action_type']] ?? '<i class="fas fa-info-circle" style="color:#666;"></i>'; ?>
                    </div>
                    <div class="activity-details"><p><?php echo htmlspecialchars($activity['action_details']); ?></p><span class="activity-time"><?php echo time_ago($activity['created_at']); ?></span></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* (All styles remain exactly as you had them – no changes needed) */
.teacher-dashboard { max-width: 1400px; margin: 0 auto; padding: 30px 24px; }
.page-header { margin-bottom: 30px; }
.page-header h1 { font-size: 28px; color: #1a1a2e; margin-bottom: 5px; }
.page-header p { color: #666; font-size: 14px; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
.stats-grid.secondary { margin-bottom: 30px; }
.stat-card { background: white; border-radius: 20px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: 0.3s; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.stat-icon { width: 55px; height: 55px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 28px; }
.stat-icon.blue { background: #e8f0fe; color: #1e3a5f; }
.stat-icon.green { background: #e8f5e9; color: #2e7d32; }
.stat-icon.orange { background: #fff3e0; color: #ff8c42; }
.stat-icon.purple { background: #f3e5f5; color: #9c27b0; }
.stat-icon.teal { background: #e0f2f1; color: #00897b; }
.stat-icon.indigo { background: #e8eaf6; color: #3f51b5; }
.stat-icon.cyan { background: #e0f7fa; color: #00acc1; }
.stat-icon.pink { background: #fce4ec; color: #e91e63; }
.stat-info h3 { font-size: 28px; margin: 0; color: #1a1a2e; }
.stat-info p { margin: 5px 0 0; color: #666; font-size: 14px; font-weight: 500; }
.stat-info small { font-size: 11px; color: #999; }

.quick-actions { background: white; border-radius: 20px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.quick-actions h2 { font-size: 18px; margin-bottom: 20px; color: #1a1a2e; }
.actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
.action-card { background: #f8f9fa; padding: 20px 15px; border-radius: 16px; text-align: center; text-decoration: none; transition: 0.3s; }
.action-card:hover { background: linear-gradient(135deg, #667eea, #764ba2); color: white; transform: translateY(-3px); }
.action-card i { font-size: 32px; margin-bottom: 10px; display: block; color: #667eea; }
.action-card:hover i { color: white; }
.action-card span { display: block; font-weight: 600; margin-bottom: 5px; color: #333; }
.action-card:hover span { color: white; }
.action-card small { font-size: 11px; color: #888; }
.action-card:hover small { color: rgba(255,255,255,0.8); }
.action-card.highlight { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.action-card.highlight i { color: white; }
.action-card.highlight span { color: white; }
.action-card.highlight small { color: rgba(255,255,255,0.8); }

.bloom-section { background: white; border-radius: 20px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.bloom-section h2 { font-size: 18px; margin-bottom: 20px; color: #1a1a2e; }
.bloom-chart-container { margin-top: 10px; }
.bloom-bar { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.bloom-label { width: 90px; font-size: 13px; font-weight: 500; }
.bloom-bar-container { flex: 1; height: 28px; background: #e0e0e0; border-radius: 14px; overflow: hidden; }
.bloom-bar-fill { height: 100%; border-radius: 14px; display: flex; align-items: center; justify-content: flex-end; padding-right: 10px; color: white; font-size: 12px; font-weight: bold; }
.bloom-percent { width: 65px; font-size: 12px; font-weight: 500; }
.bloom-empty { text-align: center; padding: 30px; background: #f8f9fa; border-radius: 12px; margin-top: 15px; }
.bloom-empty a { color: #667eea; text-decoration: none; }

.modules-section { margin-bottom: 30px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.section-header h2 { font-size: 20px; color: #1a1a2e; }
.btn-add { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 8px 20px; border-radius: 30px; text-decoration: none; font-size: 14px; }
.btn-add:hover { opacity: 0.9; }
.modules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 20px; }
.module-card { background: white; border-radius: 20px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: 0.3s; }
.module-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.module-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.module-code { font-size: 14px; font-weight: 600; color: #667eea; }
.module-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.module-status.draft { background: #fff3e0; color: #ff9800; }
.module-status.published { background: #e8f5e9; color: #4CAF50; }
.module-status.archived { background: #ffebee; color: #f44336; }
.module-card h3 { font-size: 18px; margin-bottom: 10px; color: #1a1a2e; }
.module-meta { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px; font-size: 12px; color: #666; }
.module-meta i { margin-right: 4px; }
.module-stats { display: flex; gap: 20px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
.module-stats .stat { text-align: center; flex: 1; }
.module-stats .stat-value { display: block; font-size: 20px; font-weight: bold; color: #667eea; }
.module-stats .stat-label { font-size: 11px; color: #999; }
.module-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-outline { flex: 1; text-align: center; padding: 8px 12px; border-radius: 30px; text-decoration: none; font-size: 12px; font-weight: 500; transition: 0.3s; background: #f5f5f5; color: #333; min-width: 90px; }
.btn-outline:hover { background: #667eea; color: white; }

.recent-content-section { background: white; border-radius: 20px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.recent-content-section h2 { font-size: 18px; margin-bottom: 20px; color: #1a1a2e; }
.content-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
.content-card { background: #f8f9fa; border-radius: 16px; padding: 15px; }
.content-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e0e0e0; }
.content-header h3 { font-size: 16px; margin: 0; color: #1a1a2e; }
.content-header h3 i { margin-right: 8px; color: #667eea; }
.btn-link { color: #667eea; text-decoration: none; font-size: 12px; }
.content-list { max-height: 320px; overflow-y: auto; }
.content-item { padding: 12px; border-bottom: 1px solid #eee; transition: 0.3s; }
.content-item:hover { background: white; border-radius: 8px; }
.item-info { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap; }
.item-badge { padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; color: white; }
.item-badge.remember { background: #4CAF50; }
.item-badge.understand { background: #2196F3; }
.item-badge.apply { background: #FF9800; }
.item-badge.analyze { background: #9C27B0; }
.item-badge.evaluate { background: #F44336; }
.item-badge.create { background: #009688; }
.item-title { font-size: 13px; color: #333; flex: 1; }
.item-meta { display: flex; gap: 15px; flex-wrap: wrap; padding-left: 5px; font-size: 11px; color: #999; }
.item-meta i { margin-right: 3px; }
.empty-content { text-align: center; padding: 40px 20px; color: #999; font-size: 13px; }
.btn-small { display: inline-block; background: #667eea; color: white; padding: 6px 16px; border-radius: 20px; text-decoration: none; font-size: 12px; margin-top: 10px; }

.recent-section { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.recent-section h2 { font-size: 18px; margin-bottom: 20px; color: #1a1a2e; }
.activity-list { max-height: 300px; overflow-y: auto; }
.activity-item { display: flex; gap: 15px; padding: 12px 0; border-bottom: 1px solid #eee; }
.activity-icon { width: 32px; text-align: center; font-size: 18px; }
.activity-details { flex: 1; }
.activity-details p { margin: 0; font-size: 14px; color: #333; }
.activity-time { font-size: 11px; color: #999; }
.empty-activity, .empty-state { text-align: center; padding: 30px; color: #999; }
.empty-state { background: white; border-radius: 20px; padding: 60px; }
.empty-state i { font-size: 64px; color: #ccc; margin-bottom: 20px; }
.empty-state h3 { font-size: 20px; color: #666; margin-bottom: 10px; }
.btn-primary { display: inline-block; background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; margin-top: 15px; }

@media (max-width: 900px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .content-grid { grid-template-columns: 1fr; }
    .modules-grid { grid-template-columns: 1fr; }
    .module-actions { flex-direction: column; }
    .btn-outline { width: 100%; }
    .actions-grid { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .stats-grid { grid-template-columns: 1fr; }
    .teacher-dashboard { padding: 20px 16px; }
}
</style>

<?php include_once '../includes/templates/footer.php'; ?>