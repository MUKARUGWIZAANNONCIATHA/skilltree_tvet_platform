<?php
/**
 * Professional Exam Builder - FINAL PRODUCTION VERSION
 * Path: /teacher/exam-builder.php
 * FIXED: Uses teacher_modules for module access
 */

require_once '../includes/auth/session-check.php';
requireRole(['teacher', 'admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
$message = '';
$error = '';

// Get modules assigned to teacher (via teacher_modules)
if ($role === 'admin') {
    $modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT m.module_id, m.module_code, m.module_name
        FROM modules m
        JOIN teacher_modules tm ON m.module_id = tm.module_id
        WHERE tm.teacher_id = ?
        ORDER BY m.module_code
    ");
    $stmt->execute([$userId]);
    $modules = $stmt->fetchAll();
}

$selectedModuleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$selectedExamId = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Permission check for selected module
if ($selectedModuleId > 0 && $role !== 'admin') {
    $check = $pdo->prepare("SELECT COUNT(*) FROM teacher_modules WHERE teacher_id = ? AND module_id = ?");
    $check->execute([$userId, $selectedModuleId]);
    if ($check->fetchColumn() == 0) {
        die("You do not have access to this module.");
    }
}

// Get learning outcomes
$learningOutcomes = [];
if ($selectedModuleId > 0) {
    $stmt = $pdo->prepare("SELECT outcome_id, outcome_number, description FROM learning_outcomes WHERE module_id = ? ORDER BY outcome_number");
    $stmt->execute([$selectedModuleId]);
    $learningOutcomes = $stmt->fetchAll();
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Save exam
    if ($action === 'save_exam') {
        $examTitle = trim($_POST['exam_title'] ?? '');
        $examDescription = trim($_POST['exam_description'] ?? '');
        $moduleId = intval($_POST['module_id'] ?? 0);
        $duration = intval($_POST['duration'] ?? 180);
        $passingMarks = intval($_POST['passing_marks'] ?? 70);
        $examDate = $_POST['exam_date'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'draft';
        $instructions = $_POST['instructions'] ?? '';
        
        $sectionA = json_decode($_POST['section_a_json'] ?? '[]', true);
        $sectionB = json_decode($_POST['section_b_json'] ?? '[]', true);
        $sectionC = json_decode($_POST['section_c_json'] ?? '[]', true);
        
        $totalMarks = 0;
        $bloomStats = ['remember' => 0, 'understand' => 0, 'apply' => 0, 'analyze' => 0, 'evaluate' => 0, 'create' => 0];
        
        foreach($sectionA as $q) { $totalMarks += $q['marks']; $bloomStats[$q['bloom']] += $q['marks']; }
        foreach($sectionB as $q) { $totalMarks += $q['marks']; $bloomStats[$q['bloom']] += $q['marks']; }
        foreach($sectionC as $q) { $totalMarks += $q['marks']; $bloomStats[$q['bloom']] += $q['marks']; }
        
        $examData = [
            'title' => $examTitle,
            'description' => $examDescription,
            'instructions' => $instructions,
            'duration' => $duration,
            'passing_marks' => $passingMarks,
            'total_marks' => $totalMarks,
            'bloom_distribution' => $bloomStats,
            'section_a' => $sectionA,
            'section_b' => $sectionB,
            'section_c' => $sectionC
        ];
        
        $stmt = $pdo->prepare("INSERT INTO exams (module_id, exam_title, exam_description, duration_minutes, total_marks, passing_marks, exam_date, exam_data_json, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$moduleId, $examTitle, $examDescription, $duration, $totalMarks, $passingMarks, $examDate, json_encode($examData), $status, $userId]);
        
        $message = "✅ Exam created successfully!";
        header("Location: ?module_id=$moduleId&exam_created=1");
        exit();
    }
    
    // AI Generate Exam
    if ($action === 'ai_generate_exam') {
        $topic = $_POST['ai_topic'] ?? '';
        $teacherRequest = $_POST['teacher_request'] ?? '';
        $bloomFocus = $_POST['bloom_focus'] ?? 'balanced';
        
        $bloomPercentages = [
            'balanced' => ['remember' => 10, 'understand' => 15, 'apply' => 25, 'analyze' => 25, 'evaluate' => 15, 'create' => 10],
            'lower' => ['remember' => 30, 'understand' => 30, 'apply' => 25, 'analyze' => 10, 'evaluate' => 5, 'create' => 0],
            'higher' => ['remember' => 5, 'understand' => 10, 'apply' => 15, 'analyze' => 30, 'evaluate' => 25, 'create' => 15]
        ];
        
        $percentages = $bloomPercentages[$bloomFocus] ?? $bloomPercentages['balanced'];
        
        // Section A
        $sectionA = [];
        $marksA = [3,3,3,3,3,3,3,3,3,3,3,3,4,4,4,4,3];
        $questionTypes = ['multiple_choice', 'sentence_completion', 'true_false', 'short_answer', 'matching', 'fill_table', 'arrange_steps'];
        
        for ($i = 0; $i < 17; $i++) {
            $rand = rand(1, 100);
            $cumulative = 0;
            $bloom = 'understand';
            foreach ($percentages as $level => $pct) {
                $cumulative += $pct;
                if ($rand <= $cumulative) { $bloom = $level; break; }
            }
            $sectionA[] = ['num' => $i+1, 'type' => $questionTypes[array_rand($questionTypes)], 'text' => generateQuestionText($questionTypes[array_rand($questionTypes)], $bloom, $topic, $teacherRequest), 'marks' => $marksA[$i], 'bloom' => $bloom, 'answer' => "Model answer for {$bloom} level question about $topic."];
        }
        
        // Section B
        $sectionB = [];
        $bTypes = ['short_answer', 'essay', 'case_study'];
        for ($i = 0; $i < 5; $i++) {
            $bloom = $i < 2 ? 'analyze' : ($i < 4 ? 'evaluate' : 'create');
            $sectionB[] = ['num' => $i+1, 'type' => $bTypes[$i % 3], 'text' => generateQuestionText($bTypes[$i % 3], $bloom, $topic, $teacherRequest), 'marks' => 10, 'bloom' => $bloom, 'answer' => "Comprehensive answer for {$bloom} level question."];
        }
        
        // Section C
        $sectionC = [];
        for ($i = 0; $i < 2; $i++) {
            $sectionC[] = ['num' => $i+1, 'type' => $i == 0 ? 'essay' : 'case_study', 'text' => generateQuestionText($i == 0 ? 'essay' : 'case_study', 'create', $topic, $teacherRequest), 'marks' => 15, 'bloom' => 'create', 'answer' => "Detailed rubric-based answer for creation level."];
        }
        
        $_SESSION['ai_exam_data'] = ['sectionA' => $sectionA, 'sectionB' => $sectionB, 'sectionC' => $sectionC, 'bloomPercentages' => $percentages, 'topic' => $topic];
        $message = "🤖 AI generated " . (count($sectionA) + count($sectionB) + count($sectionC)) . " questions!";
        header("Location: ?module_id=$selectedModuleId&ai_generated=1");
        exit();
    }
    
    // Update status
    if ($action === 'update_status') {
        $examId = intval($_POST['exam_id']);
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE exams SET status = ? WHERE exam_id = ?");
        $stmt->execute([$status, $examId]);
        $message = "✅ Exam status updated!";
        header("Location: ?module_id=$selectedModuleId&status_updated=1");
        exit();
    }
    
    // Delete exam
    if ($action === 'delete_exam') {
        $examId = intval($_POST['exam_id']);
        $stmt = $pdo->prepare("DELETE FROM exams WHERE exam_id = ?");
        $stmt->execute([$examId]);
        $message = "✅ Exam deleted!";
        header("Location: ?module_id=$selectedModuleId&deleted=1");
        exit();
    }
}

function generateQuestionText($type, $bloom, $topic, $request = '') {
    $verbs = ['remember'=>'list, define', 'understand'=>'explain, summarize', 'apply'=>'apply, solve', 'analyze'=>'analyze, compare', 'evaluate'=>'evaluate, justify', 'create'=>'design, create'];
    $v = $verbs[$bloom] ?? 'explain';
    $extra = !empty($request) ? " " . $request : "";
    
    switch($type) {
        case 'multiple_choice': return "[{$bloom}] Choose the correct answer about $topic.$extra\nA) Option A\nB) Option B\nC) Option C\nD) Option D";
        case 'true_false': return "[{$bloom}] True or False: \"$topic is essential.\"$extra";
        case 'sentence_completion': return "[{$bloom}] Complete: The process of $topic involves __________.$extra";
        case 'short_answer': return "[{$bloom}] Briefly " . explode(',', $v)[0] . " $topic in 2-3 sentences.$extra";
        case 'essay': return "[{$bloom}] Write an essay discussing $topic.$extra Include examples.";
        case 'matching': return "[{$bloom}] MATCHING: Match Column A with Column B about $topic.$extra\n\nLEFT COLUMN (Items):\n1. Item 1\n2. Item 2\n3. Item 3\n\nRIGHT COLUMN (Descriptions):\nA. Description A\nB. Description B\nC. Description C";
        case 'fill_table': return "[{$bloom}] FILL THE TABLE: Complete the table about $topic.$extra\n\n| Concept | Definition | Example |\n|---------|------------|---------|\n| Concept 1 | __________ | __________ |\n| Concept 2 | __________ | __________ |";
        case 'arrange_steps': return "[{$bloom}] ARRANGE STEPS: Put these steps in correct order for $topic.$extra\n\n__ Step A\n__ Step B\n__ Step C\n__ Step D";
        case 'case_study': return "[{$bloom}] CASE STUDY: Read and analyze this scenario about $topic.$extra\n\nScenario: [Provide scenario here]\n\nQuestions:\n1. Identify the main issue\n2. Analyze the causes\n3. Recommend solutions";
        default: return ucfirst($bloom) . " question about $topic.";
    }
}

// Get exams for this module (only if accessible)
$exams = [];
if ($selectedModuleId > 0) {
    $stmt = $pdo->prepare("SELECT e.*, COUNT(es.submission_id) as submissions_count FROM exams e LEFT JOIN exam_submissions es ON e.exam_id = es.exam_id WHERE e.module_id = ? GROUP BY e.exam_id ORDER BY e.created_at DESC");
    $stmt->execute([$selectedModuleId]);
    $exams = $stmt->fetchAll();
}

$currentExam = null;
$currentExamData = null;
if ($selectedExamId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE exam_id = ?");
    $stmt->execute([$selectedExamId]);
    $currentExam = $stmt->fetch();
    if ($currentExam) $currentExamData = json_decode($currentExam['exam_data_json'], true);
}

$aiSectionA = $_SESSION['ai_exam_data']['sectionA'] ?? [];
$aiSectionB = $_SESSION['ai_exam_data']['sectionB'] ?? [];
$aiSectionC = $_SESSION['ai_exam_data']['sectionC'] ?? [];
// Clear AI session data after use (optional, we keep for one page load)
if (isset($_GET['ai_generated'])) unset($_SESSION['ai_exam_data']);

include_once '../includes/templates/header.php';
?>

<div class="exam-builder">
    <div class="page-header">
        <h1><i class="fas fa-file-alt"></i> Professional Exam Builder</h1>
        <p>AI Generate | Manual Builder | PDF Export | Marking Guide | Bloom Analytics</p>
    </div>

    <div class="selection-area">
        <select id="module_select" class="module-select" onchange="loadModule()">
            <option value="">-- Select Module --</option>
            <?php foreach($modules as $module): ?>
                <option value="<?php echo $module['module_id']; ?>" <?php echo $selectedModuleId == $module['module_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($module['module_code']); ?> - <?php echo htmlspecialchars($module['module_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if($selectedModuleId > 0): ?>
    
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-number"><?php echo count($exams); ?></div><div class="stat-label">Total Exams</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo count(array_filter($exams, fn($e) => $e['status'] == 'published')); ?></div><div class="stat-label">Published</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo array_sum(array_column($exams, 'submissions_count')); ?></div><div class="stat-label">Submissions</div></div>
    </div>

    <div class="two-columns">
        <!-- LEFT: Exam List -->
        <div class="exam-list">
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-list"></i> My Exams</h3><button class="btn-primary" onclick="location.href='?module_id=<?php echo $selectedModuleId; ?>'">+ New Exam</button></div>
                <?php if(empty($exams)): ?>
                    <div class="empty-state"><p>No exams yet. Use AI generator or manual builder.</p></div>
                <?php else: ?>
                    <div class="exam-items">
                        <?php foreach($exams as $exam):
                            $ed = json_decode($exam['exam_data_json'], true);
                            $totalQ = (count($ed['section_a'] ?? []) + count($ed['section_b'] ?? []) + count($ed['section_c'] ?? []));
                        ?>
                        <div class="exam-item">
                            <div class="exam-info">
                                <h4><?php echo htmlspecialchars($exam['exam_title']); ?></h4>
                                <div class="exam-meta"><span><i class="fas fa-clock"></i> <?php echo $exam['duration_minutes']; ?> min</span><span><i class="fas fa-star"></i> <?php echo $exam['total_marks']; ?> marks</span><span class="status-badge <?php echo $exam['status']; ?>"><?php echo ucfirst($exam['status']); ?></span></div>
                            </div>
                            <div class="exam-actions">
                                <a href="?module_id=<?php echo $selectedModuleId; ?>&exam_id=<?php echo $exam['exam_id']; ?>" class="btn-edit">✏️ Edit</a>
                                <a href="export-exam-pdf.php?exam_id=<?php echo $exam['exam_id']; ?>&type=exam" class="btn-download" target="_blank">📄 Exam PDF</a>
                                <a href="export-exam-pdf.php?exam_id=<?php echo $exam['exam_id']; ?>&type=marking" class="btn-marking" target="_blank">📋 Marking Guide</a>
                                <form method="POST" style="display:inline;"><input type="hidden" name="action" value="update_status"><input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>"><input type="hidden" name="status" value="<?php echo $exam['status'] == 'published' ? 'draft' : 'published'; ?>"><button type="submit" class="btn-status"><?php echo $exam['status'] == 'published' ? '📥 Draft' : '📤 Publish'; ?></button></form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete_exam"><input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>"><button type="submit" class="btn-delete">🗑️</button></form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT: Exam Builder -->
        <div class="exam-form-container">
            <div class="card">
                <h3><i class="fas fa-<?php echo $currentExam ? 'edit' : 'plus'; ?>"></i> <?php echo $currentExam ? 'Edit Exam' : 'Create New Exam'; ?></h3>
                
                <!-- AI Generator Toggle -->
                <div class="ai-generate-section">
                    <div class="ai-form-row">
                        <input type="text" id="ai_topic" class="form-control" placeholder="Topic (e.g., Database Normalization)" style="flex:2;">
                        <textarea id="ai_request" class="form-control" rows="2" placeholder="Special instructions (e.g., Include real-world examples from Rwanda, focus on practical applications)" style="flex:3;"></textarea>
                        <select id="bloom_focus" class="form-control" style="width:150px;"><option value="balanced">Balanced</option><option value="lower">Lower Levels</option><option value="higher">Higher Levels</option></select>
                        <button type="button" class="btn-ai" onclick="generateAIExam()">🤖 Generate</button>
                    </div>
                    <?php if(!empty($aiSectionA)): ?>
                    <div class="ai-preview"><strong>🤖 AI Generated:</strong> <?php echo count($aiSectionA); ?> A + <?php echo count($aiSectionB); ?> B + <?php echo count($aiSectionC); ?> C questions <button type="button" class="btn-use-ai" onclick="useAIGenerated()">Use This Exam</button></div>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <form method="POST" id="exam_form">
                    <input type="hidden" name="action" value="save_exam">
                    <input type="hidden" name="module_id" value="<?php echo $selectedModuleId; ?>">
                    <input type="hidden" name="section_a_json" id="section_a_json">
                    <input type="hidden" name="section_b_json" id="section_b_json">
                    <input type="hidden" name="section_c_json" id="section_c_json">
                    
                    <div class="form-row">
                        <div class="form-group"><label>Exam Title</label><input type="text" name="exam_title" class="form-control" value="<?php echo htmlspecialchars($currentExam['exam_title'] ?? ''); ?>" required></div>
                        <div class="form-group"><label>Exam Date</label><input type="date" name="exam_date" class="form-control" value="<?php echo $currentExam['exam_date'] ?? date('Y-m-d'); ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Duration (minutes)</label><input type="number" name="duration" class="form-control" value="<?php echo $currentExam['duration_minutes'] ?? 180; ?>"></div>
                        <div class="form-group"><label>Passing Marks (%)</label><input type="number" name="passing_marks" class="form-control" value="<?php echo $currentExam['passing_marks'] ?? 70; ?>"></div>
                        <div class="form-group"><label>Status</label><select name="status" class="form-control"><option value="draft">Draft</option><option value="published" <?php echo ($currentExam['status'] ?? '') == 'published' ? 'selected' : ''; ?>>Published</option></select></div>
                    </div>
                    <div class="form-group"><label>Instructions for Students</label><textarea name="instructions" class="form-control" rows="3" placeholder="Answer ALL questions in Section A. Choose any 3 from Section B. Choose any 1 from Section C. Total: 100 marks. Time: 3 hours."><?php echo htmlspecialchars($currentExamData['instructions'] ?? ''); ?></textarea></div>
                    
                    <!-- Bloom Analytics -->
                    <div class="bloom-analytics">
                        <h4>📊 Bloom's Taxonomy Distribution</h4>
                        <div class="bloom-bars" id="bloom_bars"></div>
                        <div class="bloom-total">Total: <span id="bloom_total">0</span> / 100 marks <span id="bloom_warning"></span></div>
                    </div>
                    
                    <!-- Section A -->
                    <div class="section-card">
                        <div class="section-header"><h4>📚 SECTION A (Compulsory - 55 marks, 17 questions)</h4><button type="button" class="btn-add-q" onclick="addQuestion('A')">+ Add Question</button></div>
                        <div id="section_a_container" class="questions-container">
                            <?php 
                            $sqA = $currentExamData['section_a'] ?? $aiSectionA;
                            foreach($sqA as $idx => $q): 
                            ?>
                            <div class="question-card" data-section="A">
                                <div class="question-header"><span class="q-num">Q<?php echo $idx+1; ?></span><span class="q-marks"><?php echo $q['marks']; ?> marks</span><button type="button" class="btn-remove" onclick="removeQuestion(this)">🗑️</button></div>
                                <div class="form-row-small">
                                    <select class="q-type" onchange="updateQuestionTemplate(this)"><?php $types = ['multiple_choice','sentence_completion','multiple_selection','true_false','matching','fill_table','arrange_steps','short_answer','essay']; foreach($types as $t){ echo "<option value='$t' ".($q['type']==$t?'selected':'').">".str_replace('_',' ',ucfirst($t))."</option>"; } ?></select>
                                    <select class="q-bloom" onchange="updateBloomAnalytics()"><?php $blooms = ['remember','understand','apply','analyze','evaluate','create']; foreach($blooms as $b){ echo "<option value='$b' ".($q['bloom']==$b?'selected':'').">".ucfirst($b)."</option>"; } ?></select>
                                    <input type="number" class="q-marks-val" value="<?php echo $q['marks']; ?>" placeholder="Marks" onchange="updateBloomAnalytics()">
                                </div>
                                <textarea class="q-text" rows="2" placeholder="Question text"><?php echo htmlspecialchars($q['text']); ?></textarea>
                                <div class="answer-section">
                                    <textarea class="q-answer" rows="2" placeholder="Model answer / Rubric"><?php echo htmlspecialchars($q['answer']); ?></textarea>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="section-info">Total: <span class="section-total">0</span> / 55 marks</div>
                    </div>
                    
                    <!-- Section B -->
                    <div class="section-card">
                        <div class="section-header"><h4>📚 SECTION B (Choose 3 of 5 - 30 marks, each 10 marks)</h4><button type="button" class="btn-add-q" onclick="addQuestion('B')">+ Add Question</button></div>
                        <div id="section_b_container" class="questions-container">
                            <?php 
                            $sqB = $currentExamData['section_b'] ?? $aiSectionB;
                            foreach($sqB as $idx => $q): 
                            ?>
                            <div class="question-card" data-section="B">
                                <div class="question-header"><span class="q-num">Q<?php echo $idx+1; ?></span><span class="q-marks"><?php echo $q['marks']; ?> marks</span><button type="button" class="btn-remove" onclick="removeQuestion(this)">🗑️</button></div>
                                <div class="form-row-small">
                                    <select class="q-type"><?php $btypes = ['short_answer','essay','case_study']; foreach($btypes as $t){ echo "<option value='$t' ".($q['type']==$t?'selected':'').">".str_replace('_',' ',ucfirst($t))."</option>"; } ?></select>
                                    <select class="q-bloom" onchange="updateBloomAnalytics()"><?php $bblooms = ['analyze','evaluate','create']; foreach($bblooms as $b){ echo "<option value='$b' ".($q['bloom']==$b?'selected':'').">".ucfirst($b)."</option>"; } ?></select>
                                    <input type="number" class="q-marks-val" value="<?php echo $q['marks']; ?>" onchange="updateBloomAnalytics()">
                                </div>
                                <textarea class="q-text" rows="2" placeholder="Question text"><?php echo htmlspecialchars($q['text']); ?></textarea>
                                <textarea class="q-answer" rows="2" placeholder="Model answer"><?php echo htmlspecialchars($q['answer']); ?></textarea>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="section-info">Total: <span class="section-total">0</span> / 30 marks (Choose 3 of 5)</div>
                    </div>
                    
                    <!-- Section C -->
                    <div class="section-card">
                        <div class="section-header"><h4>📚 SECTION C (Choose 1 of 2 - 15 marks)</h4><button type="button" class="btn-add-q" onclick="addQuestion('C')">+ Add Question</button></div>
                        <div id="section_c_container" class="questions-container">
                            <?php 
                            $sqC = $currentExamData['section_c'] ?? $aiSectionC;
                            foreach($sqC as $idx => $q): 
                            ?>
                            <div class="question-card" data-section="C">
                                <div class="question-header"><span class="q-num">Q<?php echo $idx+1; ?></span><span class="q-marks"><?php echo $q['marks']; ?> marks</span><button type="button" class="btn-remove" onclick="removeQuestion(this)">🗑️</button></div>
                                <div class="form-row-small">
                                    <select class="q-type"><?php $ctypes = ['essay','case_study']; foreach($ctypes as $t){ echo "<option value='$t' ".($q['type']==$t?'selected':'').">".str_replace('_',' ',ucfirst($t))."</option>"; } ?></select>
                                    <select class="q-bloom"><option value="create" <?php echo ($q['bloom']??'') == 'create' ? 'selected' : ''; ?>>Create</option></select>
                                    <input type="number" class="q-marks-val" value="<?php echo $q['marks']; ?>" onchange="updateBloomAnalytics()">
                                </div>
                                <textarea class="q-text" rows="3" placeholder="Question text"><?php echo htmlspecialchars($q['text']); ?></textarea>
                                <textarea class="q-answer" rows="3" placeholder="Rubric / Marking guide"><?php echo htmlspecialchars($q['answer']); ?></textarea>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="section-info">Total: <span class="section-total">0</span> / 15 marks (Choose 1 of 2)</div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-preview" onclick="previewExam()">👁️ Preview Full Exam</button>
                        <button type="button" class="btn-preview-marking" onclick="previewMarkingGuide()">📋 Preview Marking Guide</button>
                        <button type="submit" class="btn-save">💾 Save Exam</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php else: ?>
        <div class="info-message">Please select a module to manage exams.</div>
    <?php endif; ?>
</div>

<style>
/* (styling remains the same, omitted for brevity – keep your existing CSS) */
.exam-builder{max-width:1400px;margin:0 auto;padding:30px 20px;}
.page-header{margin-bottom:25px;}
.page-header h1{font-size:28px;color:#1a1a2e;margin:0;}
.selection-area{margin-bottom:25px;}
.module-select{width:100%;padding:12px;border:2px solid #e0e0e0;border-radius:12px;}
.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:30px;}
.stat-card{background:white;border-radius:20px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
.stat-number{font-size:32px;font-weight:bold;color:#667eea;}
.two-columns{display:grid;grid-template-columns:1fr 1.6fr;gap:25px;}
.card{background:white;border-radius:20px;padding:25px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
.card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;padding:8px 16px;border-radius:20px;cursor:pointer;}
.exam-items{max-height:500px;overflow-y:auto;}
.exam-item{background:#f8f9fa;border-radius:12px;padding:15px;margin-bottom:15px;border-left:4px solid #667eea;}
.exam-info h4{margin:0 0 8px;}
.exam-meta{display:flex;gap:15px;font-size:12px;color:#666;}
.status-badge{padding:2px 8px;border-radius:12px;font-size:10px;}
.status-badge.published{background:#4CAF50;color:white;}
.status-badge.draft{background:#ff9800;color:white;}
.exam-actions{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;}
.btn-edit,.btn-download,.btn-marking,.btn-status,.btn-delete{padding:5px 12px;border-radius:15px;font-size:12px;text-decoration:none;border:none;cursor:pointer;}
.btn-edit{background:#2196F3;color:white;}
.btn-download{background:#4CAF50;color:white;}
.btn-marking{background:#9C27B0;color:white;}
.btn-status{background:#ff9800;color:white;}
.btn-delete{background:#f44336;color:white;}
.ai-generate-section{background:#e8f0fe;border-radius:16px;padding:15px;margin-bottom:20px;}
.ai-form-row{display:flex;gap:10px;flex-wrap:wrap;}
.btn-ai{background:linear-gradient(135deg,#11998e,#38ef7d);color:white;border:none;padding:10px 20px;border-radius:25px;cursor:pointer;}
.ai-preview{background:white;border-radius:12px;padding:10px;margin-top:10px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;}
.btn-use-ai{background:#4CAF50;color:white;border:none;padding:5px 15px;border-radius:15px;cursor:pointer;}
.form-row{display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap;}
.form-group{flex:1;}
.form-group label{display:block;margin-bottom:5px;font-weight:500;font-size:13px;}
.form-control{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;}
.bloom-analytics{background:#f8f9fa;border-radius:16px;padding:15px;margin-bottom:20px;}
.bloom-bars{margin:10px 0;}
.bloom-bar{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
.bloom-label{width:80px;font-size:12px;}
.bloom-bar-container{flex:1;height:20px;background:#e0e0e0;border-radius:10px;overflow:hidden;}
.bloom-bar-fill{height:100%;border-radius:10px;transition:width 0.3s;}
.bloom-percent{width:60px;font-size:12px;}
.section-card{background:#f8f9fa;border-radius:16px;padding:20px;margin-bottom:25px;}
.section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;flex-wrap:wrap;}
.btn-add-q{background:#4CAF50;color:white;border:none;padding:6px 15px;border-radius:20px;cursor:pointer;font-size:12px;}
.question-card{background:white;border-radius:12px;padding:15px;margin-bottom:15px;border-left:3px solid #667eea;}
.question-header{display:flex;gap:10px;align-items:center;margin-bottom:10px;}
.q-num{font-weight:bold;color:#667eea;}
.q-marks{background:#667eea;color:white;padding:2px 8px;border-radius:12px;font-size:11px;}
.btn-remove{background:#f44336;color:white;border:none;padding:4px 10px;border-radius:15px;cursor:pointer;font-size:11px;margin-left:auto;}
.form-row-small{display:flex;gap:10px;margin-bottom:10px;flex-wrap:wrap;}
.q-type,.q-bloom,.q-marks-val{padding:6px;border:1px solid #ddd;border-radius:6px;}
.q-text,.q-answer{width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;margin-bottom:8px;font-family:inherit;}
.section-info{text-align:right;font-size:12px;color:#666;margin-top:10px;padding-top:10px;border-top:1px solid #ddd;}
.form-actions{display:flex;gap:15px;justify-content:flex-end;margin-top:20px;}
.btn-preview,.btn-preview-marking,.btn-save{padding:10px 25px;border-radius:25px;border:none;cursor:pointer;}
.btn-preview{background:#2196F3;color:white;}
.btn-preview-marking{background:#9C27B0;color:white;}
.btn-save{background:linear-gradient(135deg,#667eea,#764ba2);color:white;}
.empty-state{text-align:center;padding:40px;color:#999;}
.info-message{text-align:center;padding:40px;background:#f8f9fa;border-radius:20px;}
.dynamic-fields-eb{margin:8px 0;padding:10px;background:#f0f4ff;border-radius:8px;display:flex;flex-direction:column;gap:8px;}
.dynamic-fields-eb textarea,.dynamic-fields-eb input{width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-family:inherit;}
.mc-options-eb{display:flex;gap:8px;flex-wrap:wrap;}
.mc-options-eb input{flex:1;min-width:120px;}
@media (max-width:1000px){.two-columns{grid-template-columns:1fr;}}
</style>

<script>
function loadModule(){let id=document.getElementById('module_select').value;if(id)window.location.href='?module_id='+id;}
function generateAIExam(){
    let topic=document.getElementById('ai_topic').value, req=document.getElementById('ai_request').value, focus=document.getElementById('bloom_focus').value;
    if(!topic){alert('Enter topic');return;}
    let form=document.createElement('form');form.method='POST';
    form.innerHTML=`<input type="hidden" name="action" value="ai_generate_exam"><input type="hidden" name="ai_topic" value="${topic}"><input type="hidden" name="teacher_request" value="${req}"><input type="hidden" name="bloom_focus" value="${focus}">`;
    document.body.appendChild(form);form.submit();
}
function useAIGenerated(){location.reload();}
function addQuestion(section){
    let container=document.getElementById(`section_${section.toLowerCase()}_container`);
    let idx=container.children.length;
    let marks=section==='A'?3:(section==='B'?10:15);
    let bloom=section==='A'?'understand':(section==='B'?'analyze':'create');
    let div=document.createElement('div');div.className='question-card';
    let typeOptions=section==='A'?`<option value="multiple_choice">Multiple Choice</option><option value="sentence_completion">Sentence Completion</option><option value="multiple_selection">Multiple Selection</option><option value="true_false">True/False</option><option value="matching">Matching</option><option value="fill_table">Fill Table</option><option value="arrange_steps">Arrange Steps</option><option value="short_answer">Short Answer</option><option value="essay">Essay</option>`:section==='B'?`<option value="short_answer">Short Answer</option><option value="essay">Essay</option><option value="case_study">Case Study</option>`:`<option value="essay">Essay</option><option value="case_study">Case Study</option>`;
    let bloomOptions=section==='A'?`<option value="remember">Remember</option><option value="understand">Understand</option><option value="apply">Apply</option><option value="analyze">Analyze</option><option value="evaluate">Evaluate</option><option value="create">Create</option>`:section==='B'?`<option value="analyze">Analyze</option><option value="evaluate">Evaluate</option><option value="create">Create</option>`:`<option value="create">Create</option>`;
    let ts=Date.now()+idx;
    div.innerHTML=`<div class="question-header"><span class="q-num">Q${idx+1}</span><span class="q-marks">${marks} marks</span><button type="button" class="btn-remove" onclick="removeQuestion(this)">🗑️</button></div><div class="form-row-small"><select class="q-type" onchange="updateQuestionTemplate(this)">${typeOptions}</select><select class="q-bloom" onchange="updateBloomAnalytics()">${bloomOptions}</select><input type="number" class="q-marks-val" value="${marks}" onchange="updateBloomAnalytics()"></div><textarea class="q-text" rows="2" placeholder="Question text"></textarea><div class="dynamic-fields-eb" id="dyn-${ts}"></div><textarea class="q-answer" rows="2" placeholder="Model answer / Rubric"></textarea>`;
    container.appendChild(div);
    updateQuestionTemplate(div.querySelector('.q-type'));
    updateBloomAnalytics();
}
function removeQuestion(btn){btn.closest('.question-card').remove();renumberQuestions();updateBloomAnalytics();}
function renumberQuestions(){
    ['A','B','C'].forEach(s=>{document.querySelectorAll(`#section_${s.toLowerCase()}_container .question-card`).forEach((c,i)=>{c.querySelector('.q-num').innerText=`Q${i+1}`;});});
}
function updateBloomAnalytics(){
    let marks={remember:0,understand:0,apply:0,analyze:0,evaluate:0,create:0},total=0;
    ['A','B','C'].forEach(s=>{document.querySelectorAll(`#section_${s.toLowerCase()}_container .question-card`).forEach(c=>{let m=parseInt(c.querySelector('.q-marks-val').value)||0,b=c.querySelector('.q-bloom').value;marks[b]+=m;total+=m;});let sp=document.querySelector(`#section_${s.toLowerCase()}_container`).closest('.section-card').querySelector('.section-total');if(sp)sp.innerText=document.querySelectorAll(`#section_${s.toLowerCase()}_container .question-card`).length>0?[...document.querySelectorAll(`#section_${s.toLowerCase()}_container .question-card`)].reduce((sum,cc)=>sum+(parseInt(cc.querySelector('.q-marks-val').value)||0),0):0;});
    let colors={remember:'#4CAF50',understand:'#2196F3',apply:'#FF9800',analyze:'#9C27B0',evaluate:'#F44336',create:'#009688'},bars='';
    for(let b of ['remember','understand','apply','analyze','evaluate','create']){let pct=total>0?Math.round((marks[b]/total)*100):0;bars+=`<div class="bloom-bar"><div class="bloom-label">${b}</div><div class="bloom-bar-container"><div class="bloom-bar-fill" style="width:${pct}%;background:${colors[b]};">${pct>15?marks[b]:''}</div></div><div class="bloom-percent">${pct}% (${marks[b]})</div></div>`;}
    document.getElementById('bloom_bars').innerHTML=bars;
    document.getElementById('bloom_total').innerText=total;
    let warn=document.getElementById('bloom_warning');
    if(total!==100)warn.innerHTML=`⚠️ Total must be 100! Currently ${total}`;
    else warn.innerHTML='✅ Perfect!';
}
function updateQuestionTemplate(select){
    let type=select.value,card=select.closest('.question-card'),ans=card.querySelector('.q-answer'),dyn=card.querySelector('.dynamic-fields-eb');
    if(!dyn) return;
    ans.placeholder='Provide model answer or rubric.';
    if(type==='multiple_choice'){
        dyn.innerHTML=`<div class="mc-options-eb"><input type="text" placeholder="Option A" class="eb-opt"><input type="text" placeholder="Option B" class="eb-opt"><input type="text" placeholder="Option C" class="eb-opt"><input type="text" placeholder="Option D" class="eb-opt"></div>`;
        ans.placeholder='Correct option letter (e.g., B)';
    } else if(type==='multiple_selection'){
        dyn.innerHTML=`<div class="mc-options-eb"><input type="text" placeholder="Option A" class="eb-opt"><input type="text" placeholder="Option B" class="eb-opt"><input type="text" placeholder="Option C" class="eb-opt"><input type="text" placeholder="Option D" class="eb-opt"></div>`;
        ans.placeholder='Correct options (e.g., A, C)';
    } else if(type==='true_false'){
        dyn.innerHTML=``;
        ans.placeholder='True or False? Explain why.';
    } else if(type==='matching'){
        dyn.innerHTML=`<textarea class="eb-left" rows="3" placeholder="LEFT COLUMN (one per line)"></textarea><textarea class="eb-right" rows="3" placeholder="RIGHT COLUMN (one per line)"></textarea>`;
        ans.placeholder='Correct matches (e.g., 1-A, 2-B, 3-C)';
    } else if(type==='fill_table'){
        dyn.innerHTML=`<textarea class="eb-table-struct" rows="3" placeholder="Table structure (e.g., | Concept | Definition | Example |)"></textarea>`;
        ans.placeholder='Provide the completed table content.';
    } else if(type==='arrange_steps'){
        dyn.innerHTML=`<textarea class="eb-steps" rows="3" placeholder="Steps in random order (one per line)"></textarea>`;
        ans.placeholder='Correct order (e.g., 3, 1, 4, 2)';
    } else if(type==='sentence_completion'){
        dyn.innerHTML=``;
        ans.placeholder='Provide the correct word(s) to fill the blank.';
    } else if(type==='short_answer'||type==='essay'||type==='case_study'){
        dyn.innerHTML=``;
        ans.placeholder=type==='case_study'?'Provide scenario and model answers.':'Provide model answer or rubric.';
    } else {
        dyn.innerHTML=``;
    }
}
function collectDynFields(card){
    let dyn=card.querySelector('.dynamic-fields-eb');
    if(!dyn) return {};
    let type=card.querySelector('.q-type').value;
    if(type==='multiple_choice'||type==='multiple_selection'){
        let opts=dyn.querySelectorAll('.eb-opt');
        if(opts.length) return {option_a:opts[0].value,option_b:opts[1].value,option_c:opts[2].value,option_d:opts[3].value};
    } else if(type==='matching'){
        let left=dyn.querySelector('.eb-left'),right=dyn.querySelector('.eb-right');
        if(left&&right) return {left_column:left.value,right_column:right.value};
    } else if(type==='fill_table'){
        let ts=dyn.querySelector('.eb-table-struct');
        if(ts) return {table_structure:ts.value};
    } else if(type==='arrange_steps'){
        let st=dyn.querySelector('.eb-steps');
        if(st) return {steps:st.value};
    }
    return {};
}
function previewExam(){
    let sectionA=[],sectionB=[],sectionC=[];
    document.querySelectorAll('#section_a_container .question-card').forEach(c=>{let d=collectDynFields(c);sectionA.push({type:c.querySelector('.q-type').value,text:c.querySelector('.q-text').value,marks:parseInt(c.querySelector('.q-marks-val').value),bloom:c.querySelector('.q-bloom').value,answer:c.querySelector('.q-answer').value,...d});});
    document.querySelectorAll('#section_b_container .question-card').forEach(c=>{let d=collectDynFields(c);sectionB.push({type:c.querySelector('.q-type').value,text:c.querySelector('.q-text').value,marks:parseInt(c.querySelector('.q-marks-val').value),bloom:c.querySelector('.q-bloom').value,answer:c.querySelector('.q-answer').value,...d});});
    document.querySelectorAll('#section_c_container .question-card').forEach(c=>{let d=collectDynFields(c);sectionC.push({type:c.querySelector('.q-type').value,text:c.querySelector('.q-text').value,marks:parseInt(c.querySelector('.q-marks-val').value),bloom:c.querySelector('.q-bloom').value,answer:c.querySelector('.q-answer').value,...d});});
    document.getElementById('section_a_json').value=JSON.stringify(sectionA);
    document.getElementById('section_b_json').value=JSON.stringify(sectionB);
    document.getElementById('section_c_json').value=JSON.stringify(sectionC);
    let title=document.querySelector('input[name="exam_title"]').value||'Exam';
    let duration=document.querySelector('input[name="duration"]').value||180;
    let instructions=document.querySelector('textarea[name="instructions"]').value||'Answer all questions.';
    let win=window.open('','_blank');
    win.document.write(`<html><head><title>${title}</title><style>body{font-family:Arial;padding:40px;line-height:1.6;}h1{color:#2c3e50;}h2{color:#3498db;margin-top:30px;}.question{margin-bottom:25px;page-break-inside:avoid;}.marks{color:#666;font-size:12px;}.header{margin-bottom:30px;}.instructions{background:#f8f9fa;padding:15px;border-radius:10px;margin:20px 0;}</style></head><body>`);
    win.document.write(`<h1>${title}</h1><div class="header"><strong>Duration:</strong> ${duration} minutes | <strong>Total Marks:</strong> 100 | <strong>Passing:</strong> 70%</div><div class="instructions">${instructions.replace(/\n/g,'<br>')}</div>`);
    win.document.write(`<h2>SECTION A (Compulsory - 55 marks)</h2>`);
    sectionA.forEach((q,i)=>{win.document.write(`<div class="question"><strong>Q${i+1}.</strong> [${q.marks} marks] ${q.text.replace(/\n/g,'<br>')}</div>`);});
    win.document.write(`<h2>SECTION B (Choose 3 of 5 - 30 marks)</h2>`);
    sectionB.forEach((q,i)=>{win.document.write(`<div class="question"><strong>Q${i+1}.</strong> [${q.marks} marks] ${q.text.replace(/\n/g,'<br>')}</div>`);});
    win.document.write(`<h2>SECTION C (Choose 1 of 2 - 15 marks)</h2>`);
    sectionC.forEach((q,i)=>{win.document.write(`<div class="question"><strong>Q${i+1}.</strong> [${q.marks} marks] ${q.text.replace(/\n/g,'<br>')}</div>`);});
    win.document.write(`</body></html>`);
    win.document.close();
}
function previewMarkingGuide(){
    let sectionA=[],sectionB=[],sectionC=[];
    document.querySelectorAll('#section_a_container .question-card').forEach(c=>{let d=collectDynFields(c);sectionA.push({type:c.querySelector('.q-type').value,text:c.querySelector('.q-text').value,marks:parseInt(c.querySelector('.q-marks-val').value),bloom:c.querySelector('.q-bloom').value,answer:c.querySelector('.q-answer').value,...d});});
    document.querySelectorAll('#section_b_container .question-card').forEach(c=>{let d=collectDynFields(c);sectionB.push({type:c.querySelector('.q-type').value,text:c.querySelector('.q-text').value,marks:parseInt(c.querySelector('.q-marks-val').value),bloom:c.querySelector('.q-bloom').value,answer:c.querySelector('.q-answer').value,...d});});
    document.querySelectorAll('#section_c_container .question-card').forEach(c=>{let d=collectDynFields(c);sectionC.push({type:c.querySelector('.q-type').value,text:c.querySelector('.q-text').value,marks:parseInt(c.querySelector('.q-marks-val').value),bloom:c.querySelector('.q-bloom').value,answer:c.querySelector('.q-answer').value,...d});});
    let title=document.querySelector('input[name="exam_title"]').value||'Exam';
    let win=window.open('','_blank');
    win.document.write(`<html><head><title>Marking Guide - ${title}</title><style>body{font-family:Arial;padding:40px;}h1{color:#2c3e50;}h2{color:#9C27B0;}.marking-item{margin-bottom:30px;border-bottom:1px solid #eee;padding-bottom:15px;}.rubric{background:#f8f9fa;padding:15px;border-radius:10px;margin-top:10px;}</style></head><body>`);
    win.document.write(`<h1>📋 MARKING GUIDE: ${title}</h1><p>Total Marks: 100 | Passing: 70%</p>`);
    win.document.write(`<h2>SECTION A (Compulsory - 55 marks)</h2>`);
    sectionA.forEach((q,i)=>{win.document.write(`<div class="marking-item"><strong>Q${i+1}.</strong> ${q.text.substring(0,100)}...<br><small>Bloom: ${q.bloom} | Marks: ${q.marks}</small><div class="rubric"><strong>Model Answer / Rubric:</strong><br>${q.answer.replace(/\n/g,'<br>')}</div></div>`);});
    win.document.write(`<h2>SECTION B (30 marks)</h2>`);
    sectionB.forEach((q,i)=>{win.document.write(`<div class="marking-item"><strong>Q${i+1}.</strong> ${q.text.substring(0,100)}...<br><small>Bloom: ${q.bloom} | Marks: ${q.marks}</small><div class="rubric">${q.answer.replace(/\n/g,'<br>')}</div></div>`);});
    win.document.write(`<h2>SECTION C (15 marks)</h2>`);
    sectionC.forEach((q,i)=>{win.document.write(`<div class="marking-item"><strong>Q${i+1}.</strong> ${q.text.substring(0,100)}...<br><small>Bloom: ${q.bloom} | Marks: ${q.marks}</small><div class="rubric">${q.answer.replace(/\n/g,'<br>')}</div></div>`);});
    win.document.write(`</body></html>`);
    win.document.close();
}
document.addEventListener('DOMContentLoaded',updateBloomAnalytics);
</script>

<?php include_once '../includes/templates/footer.php'; ?>