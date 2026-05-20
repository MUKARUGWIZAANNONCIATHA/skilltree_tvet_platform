<?php
/**
 * Unified Quiz Page – for Topics, LO Assessments, and Module Exams
 * Path: /student/quiz.php
 * Supports: type=topic&id=subtopic_id
 *            type=lo&id=lo_assessment_id
 *            type=module&id=exam_id
 */

require_once '../config/database.php';
require_once 'includes/auth.php';

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!in_array($type, ['topic', 'lo', 'module']) || !$id) die('Invalid quiz request.');

$quizTitle = '';
$questions = [];
$totalMarks = 0;
$passingMarks = 70;
$returnModuleId = 0;
$backendId = 0; // to be used for saving progress

// ---------- Fetch data based on type ----------
if ($type === 'topic') {
    // Get subtopic info
    $stmt = $pdo->prepare("SELECT topic_id FROM subtopics WHERE subtopic_id = ?");
    $stmt->execute([$id]);
    $topicId = $stmt->fetchColumn();
    if (!$topicId) die('Subtopic not found.');
    $returnModuleId = $pdo->prepare("SELECT module_id FROM topics WHERE topic_id = ?")->execute([$topicId]) ?: 0;
    $backendId = $id; // subtopic_id

    // Fetch questions from review_bank for this subtopic
    $stmt = $pdo->prepare("SELECT question_id, question_text, question_type, option_a, option_b, option_c, option_d, correct_answer, marks, bloom_level FROM review_bank WHERE subtopic_id = ?");
    $stmt->execute([$id]);
    $questions = $stmt->fetchAll();
    $quizTitle = "Topic Quiz";
    $totalMarks = array_sum(array_column($questions, 'marks')) ?: 100;
    $passingMarks = 70;
} elseif ($type === 'lo') {
    // LO Assessment from lo_assessments table
    $stmt = $pdo->prepare("SELECT * FROM lo_assessments WHERE lo_assessment_id = ? AND status = 'published'");
    $stmt->execute([$id]);
    $assessment = $stmt->fetch();
    if (!$assessment) die('Assessment not available.');
    $data = json_decode($assessment['assessment_data_json'], true);
    $questions = array_merge($data['section_a'] ?? [], $data['section_b'] ?? [], $data['section_c'] ?? []);
    foreach ($questions as &$q) {
        if (!isset($q['type'])) $q['type'] = 'short_answer';
        if (!isset($q['marks'])) $q['marks'] = 1;
        if (isset($q['text'])) $q['question_text'] = $q['text'];
    }
    $quizTitle = $assessment['title'] ?? 'LO Assessment';
    $passingMarks = $assessment['passing_score'] ?? 70;
    $totalMarks = $assessment['total_marks'] ?? array_sum(array_column($questions, 'marks'));
    $returnModuleId = $pdo->prepare("SELECT module_id FROM learning_outcomes WHERE outcome_id = ?")->execute([$assessment['outcome_id']]) ?: 0;
    $backendId = $id; // lo_assessment_id
} elseif ($type === 'module') {
    // Final exam from exams table
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE exam_id = ? AND status = 'published'");
    $stmt->execute([$id]);
    $exam = $stmt->fetch();
    if (!$exam) die('Exam not available.');
    $data = json_decode($exam['exam_data_json'], true);
    $questions = array_merge($data['section_a'] ?? [], $data['section_b'] ?? [], $data['section_c'] ?? []);
    foreach ($questions as &$q) {
        if (!isset($q['type'])) $q['type'] = 'short_answer';
        if (!isset($q['marks'])) $q['marks'] = 1;
        if (isset($q['text'])) $q['question_text'] = $q['text'];
    }
    $quizTitle = $exam['exam_title'] ?? 'Final Module Exam';
    $passingMarks = $exam['passing_marks'] ?? 70;
    $totalMarks = $exam['total_marks'] ?? array_sum(array_column($questions, 'marks'));
    $returnModuleId = $exam['module_id'];
    $backendId = $id; // exam_id
}

if (empty($questions)) die('No questions available.');

// ---------- Check if already passed (to prevent unnecessary retakes) ----------
$alreadyPassed = false;
if ($type === 'topic') {
    $stmt = $pdo->prepare("SELECT quiz_passed FROM topic_progress WHERE student_id = ? AND subtopic_id = ?");
    $stmt->execute([$studentId, $id]);
    $alreadyPassed = (bool) $stmt->fetchColumn();
} elseif ($type === 'lo') {
    $stmt = $pdo->prepare("SELECT lo_assessment_passed FROM lo_progress WHERE student_id = ? AND lo_id = ?");
    $stmt->execute([$studentId, $id]);
    $alreadyPassed = (bool) $stmt->fetchColumn();
} elseif ($type === 'module') {
    $stmt = $pdo->prepare("SELECT module_passed FROM module_progress WHERE student_id = ? AND module_id = ?");
    $stmt->execute([$studentId, $returnModuleId]);
    $alreadyPassed = (bool) $stmt->fetchColumn();
}
if ($alreadyPassed) {
    die("You have already passed this assessment. <a href='module.php?module_id=$returnModuleId'>Back to module</a>");
}

// ---------- Handle submission ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = json_decode($_POST['answers_json'] ?? '[]', true);
    $violations = intval($_POST['violations'] ?? 0);
    $keyboardViolations = intval($_POST['keyboard_violations'] ?? 0);

    $earned = 0;
    $total = 0;
    foreach ($questions as $idx => $q) {
        $marks = $q['marks'] ?? 1;
        $total += $marks;
        $userAnswer = $answers[$idx] ?? null;
        if (evaluateQuestion($q, $userAnswer)) $earned += $marks;
    }
    $percentage = $total > 0 ? round(($earned / $total) * 100) : 0;
    $passed = ($percentage >= $passingMarks);

    // Save to progress table
    if ($type === 'topic') {
        $stmt = $pdo->prepare("INSERT INTO topic_progress (student_id, subtopic_id, quiz_passed, quiz_score, quiz_attempts, last_quiz_date) VALUES (?, ?, ?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE quiz_passed = VALUES(quiz_passed), quiz_score = VALUES(quiz_score), quiz_attempts = quiz_attempts + 1, last_quiz_date = NOW()");
        $stmt->execute([$studentId, $id, $passed ? 1 : 0, $percentage]);
    } elseif ($type === 'lo') {
        $stmt = $pdo->prepare("INSERT INTO lo_progress (student_id, lo_id, lo_assessment_passed, lo_assessment_score) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE lo_assessment_passed = VALUES(lo_assessment_passed), lo_assessment_score = VALUES(lo_assessment_score)");
        $stmt->execute([$studentId, $id, $passed ? 1 : 0, $percentage]);
    } elseif ($type === 'module') {
        $stmt = $pdo->prepare("INSERT INTO module_progress (student_id, module_id, module_passed, module_score) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE module_passed = VALUES(module_passed), module_score = VALUES(module_score)");
        $stmt->execute([$studentId, $returnModuleId, $passed ? 1 : 0, $percentage]);
    }

    // Log violations
    if ($violations > 0) {
        $log = $pdo->prepare("INSERT INTO anti_cheat_logs (student_id, event_type, context, details) VALUES (?, 'tab_switch', ?, ?)");
        $log->execute([$studentId, "quiz_$type", "Violations: $violations"]);
    }
    if ($keyboardViolations > 0) {
        $log = $pdo->prepare("INSERT INTO anti_cheat_logs (student_id, event_type, context, details) VALUES (?, 'keyboard_shortcut', ?, ?)");
        $log->execute([$studentId, "quiz_$type", "Violations: $keyboardViolations"]);
    }

    // For module exams, also insert into exam_submissions table (optional, for detailed records)
    if ($type === 'module') {
        $subStmt = $pdo->prepare("INSERT INTO exam_submissions (exam_id, student_id, answers_json, score, total_marks, percentage, status, attempt_number, violations, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?, (SELECT COALESCE(MAX(attempt_number),0)+1 FROM exam_submissions WHERE exam_id=? AND student_id=?), ?, NOW())");
        $subStmt->execute([$id, $studentId, json_encode($answers), $earned, $total, $percentage, $passed ? 'passed' : 'failed', $id, $studentId, $violations + $keyboardViolations]);
    }

    // Redirect to a result page (we'll reuse a simple result display)
    $resultMsg = "You scored $percentage% ($earned/$total marks). " . ($passed ? "🎉 Passed! Returning to module..." : "❌ Failed. You may retake.");
    echo "<!DOCTYPE html><html><head><title>Quiz Result</title><meta charset='utf-8'><style>body{font-family:Arial;text-align:center;padding:50px;}</style></head><body>";
    echo "<h2>$resultMsg</h2>";
    if (!$passed) echo "<p><a href='?type=$type&id=$id' class='btn-retake'>Retake Quiz</a></p>";
    echo "<p><a href='module.php?module_id=$returnModuleId'>Back to Module</a></p>";
    echo "</body></html>";
    exit;
}

// ---------- Helper function to grade a single question ----------
function evaluateQuestion($q, $userAnswer) {
    $type = $q['type'] ?? 'short_answer';
    $correct = $q['correct_answer'] ?? ($q['answer'] ?? '');
    if ($type === 'multiple_choice') {
        return $userAnswer === $correct;
    } elseif ($type === 'true_false') {
        return strtolower($userAnswer) === strtolower($correct);
    } elseif ($type === 'short_answer' || $type === 'essay' || $type === 'sentence_completion') {
        return trim(strtolower($userAnswer)) === trim(strtolower($correct));
    } elseif ($type === 'multiple_selection') {
        $selected = $userAnswer ?? [];
        $correctOpts = explode(',', $correct);
        sort($selected);
        sort($correctOpts);
        return $selected == $correctOpts;
    } elseif ($type === 'matching') {
        $matches = $userAnswer ?? [];
        $correctMatches = is_array($correct) ? $correct : json_decode($correct, true);
        return $matches == $correctMatches;
    } elseif ($type === 'fill_table') {
        $userTable = $userAnswer ?? [];
        $correctTable = is_array($correct) ? $correct : json_decode($correct, true);
        return $userTable == $correctTable;
    } elseif ($type === 'arrange_steps') {
        $steps = $userAnswer ?? [];
        $correctOrder = explode(',', $correct);
        return $steps == $correctOrder;
    }
    return false;
}

// ---------- Render the quiz interface (same dynamic HTML as before) ----------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($quizTitle) ?> – Anti‑cheat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e6edf4 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .quiz-wrapper { max-width: 1000px; margin: 0 auto; }
        .quiz-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 20px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .quiz-header {
            background: linear-gradient(135deg, #1a5f7a, #0e3a4a);
            padding: 1.5rem;
            color: white;
        }
        .quiz-header h1 { font-size: 1.8rem; margin-bottom: 0.3rem; }
        .quiz-header p { opacity: 0.9; display: flex; align-items: center; gap: 0.5rem; }
        .quiz-body { padding: 2rem; }
        .question-card {
            background: #f9fafc;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #2c7da0;
        }
        .question-text {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1.2rem;
        }
        .option {
            margin: 0.6rem 0;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .option input { accent-color: #2c7da0; width: 1.2rem; height: 1.2rem; cursor: pointer; }
        textarea, input[type="text"], input[type="number"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.8rem;
            font-size: 1rem;
            font-family: inherit;
        }
        .matching-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .matching-row select {
            flex: 1;
            padding: 0.5rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 0.6rem;
            text-align: left;
        }
        td input {
            width: 100%;
            border: none;
            padding: 0.4rem;
        }
        .btn-submit {
            background: #2c7da0;
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-submit:hover { background: #1e5f7a; transform: translateY(-2px); }
        .violation-banner {
            background: #fff3e0;
            color: #c76f1c;
            padding: 0.6rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .info-banner {
            background: #eef2fa;
            color: #4a6a82;
            padding: 0.6rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        @media (max-width: 600px) {
            .quiz-body { padding: 1rem; }
            .question-card { padding: 1rem; }
        }
    </style>
</head>
<body>
<div class="quiz-wrapper">
    <div class="quiz-card">
        <div class="quiz-header">
            <h1><i class="fas fa-shield-alt"></i> <?= htmlspecialchars($quizTitle) ?></h1>
            <p>Total marks: <?= $totalMarks ?> | Passing: <?= $passingMarks ?>% | Anti‑cheat active</p>
        </div>
        <div class="quiz-body">
            <div id="violationMsg" class="violation-banner" style="display:none;"><i class="fas fa-eye-slash"></i><span></span></div>
            <div id="keyboardMsg" class="violation-banner" style="display:none;"><i class="fas fa-keyboard"></i><span></span></div>
            <form id="quizForm" method="post">
                <input type="hidden" name="violations" id="violationsInput" value="0">
                <input type="hidden" name="keyboard_violations" id="keyboardViolationsInput" value="0">
                <div id="questionsContainer"></div>
                <button type="submit" class="btn-submit"><i class="fas fa-check-circle"></i> Submit Quiz</button>
            </form>
            <div class="info-banner"><i class="fas fa-info-circle"></i> Do not switch tabs, use shortcuts, or copy/paste. Violations are logged.</div>
        </div>
    </div>
</div>

<script>
    const questionsData = <?= json_encode($questions) ?>;
    let violations = 0;
    let keyboardViolations = 0;
    const violationDiv = document.getElementById('violationMsg');
    const keyboardDiv = document.getElementById('keyboardMsg');
    const violationsInput = document.getElementById('violationsInput');
    const keyboardInput = document.getElementById('keyboardViolationsInput');
    const container = document.getElementById('questionsContainer');

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    function renderQuestionFields(q, idx, containerDiv) {
        const type = q.type || 'short_answer';
        if (type === 'multiple_choice') {
            let options = ['A','B','C','D'].map(opt => ({val: opt, text: q[`option_${opt.toLowerCase()}`]}));
            let html = `<div class="options">`;
            options.forEach(opt => {
                if (opt.text) {
                    html += `<div class="option"><input type="radio" name="q_${idx}" value="${opt.val}" id="q_${idx}_${opt.val}"><label for="q_${idx}_${opt.val}">${opt.val}) ${escapeHtml(opt.text)}</label></div>`;
                }
            });
            html += `</div>`;
            containerDiv.innerHTML = html;
        } else if (type === 'true_false') {
            containerDiv.innerHTML = `<div class="options"><div class="option"><input type="radio" name="q_${idx}" value="True" id="q_${idx}_true"><label for="q_${idx}_true">True</label></div><div class="option"><input type="radio" name="q_${idx}" value="False" id="q_${idx}_false"><label for="q_${idx}_false">False</label></div></div>`;
        } else if (type === 'short_answer' || type === 'essay' || type === 'sentence_completion') {
            containerDiv.innerHTML = `<textarea name="q_${idx}" rows="4" placeholder="Type your answer here..."></textarea>`;
        } else if (type === 'multiple_selection') {
            let options = ['A','B','C','D'].map(opt => ({val: opt, text: q[`option_${opt.toLowerCase()}`]}));
            let html = `<div><p>Select all that apply:</p>`;
            options.forEach(opt => {
                if (opt.text) {
                    html += `<div class="option"><input type="checkbox" name="q_${idx}[]" value="${opt.val}" id="q_${idx}_chk_${opt.val}"><label for="q_${idx}_chk_${opt.val}">${opt.val}) ${escapeHtml(opt.text)}</label></div>`;
                }
            });
            html += `</div>`;
            containerDiv.innerHTML = html;
        } else if (type === 'matching') {
            let leftItems = q.left_column ? q.left_column.split('\n').filter(l=>l.trim()) : (q.matching_left || []);
            let rightItems = q.right_column ? q.right_column.split('\n').filter(r=>r.trim()) : (q.matching_right || []);
            let html = `<div class="matching-container">`;
            leftItems.forEach((item, i) => {
                html += `<div class="matching-row"><span style="width:200px;">${escapeHtml(item)}</span><select name="q_${idx}_match_${i}"><option value="">-- Select --</option>`;
                rightItems.forEach(right => {
                    html += `<option value="${escapeHtml(right)}">${escapeHtml(right)}</option>`;
                });
                html += `</select></div>`;
            });
            html += `<input type="hidden" name="q_${idx}_match_count" value="${leftItems.length}"></div>`;
            containerDiv.innerHTML = html;
        } else if (type === 'fill_table') {
            let headers = q.table_template ? q.table_template.split('|').filter(h=>h.trim()).map(h=>h.trim()) : ['Column 1','Column 2'];
            let rows = 2;
            let html = `</table><thead><tr>${headers.map(h => `<th>${escapeHtml(h)}</th>`).join('')}</thead><tbody>`;
            for (let r=0; r<rows; r++) {
                html += `<tr>${headers.map((h, c) => `<td><input type="text" name="q_${idx}_cell_${r}_${c}" placeholder="___"></td>`).join('')}</tr>`;
            }
            html += `</tbody></table><input type="hidden" name="q_${idx}_table_rows" value="${rows}"><input type="hidden" name="q_${idx}_table_cols" value="${headers.length}">`;
            containerDiv.innerHTML = html;
        } else if (type === 'arrange_steps') {
            let steps = q.steps ? q.steps.split('\n').filter(s=>s.trim()) : ['Step A','Step B','Step C','Step D'];
            let html = `<div><p>Arrange in correct order (drag & drop simulation):</p><div class="sortable-list">`;
            steps.forEach((step, i) => {
                html += `<div class="step-item" style="margin:0.5rem 0; display:flex; gap:0.5rem;"><span style="width:40px;">${i+1}.</span> <input type="text" name="q_${idx}_step_${i}" value="${escapeHtml(step)}" readonly style="background:#f5f5f5; width:100%;"></div>`;
            });
            html += `</div><p><small>Reorder using the number inputs (simplified).</small></p></div>`;
            containerDiv.innerHTML = html;
        } else {
            containerDiv.innerHTML = `<textarea name="q_${idx}" rows="3" placeholder="Answer here..."></textarea>`;
        }
    }

    function renderQuestions() {
        container.innerHTML = '';
        questionsData.forEach((q, idx) => {
            const div = document.createElement('div');
            div.className = 'question-card';
            const marks = q.marks || 1;
            div.innerHTML = `<div class="question-text">${idx+1}. ${escapeHtml(q.question_text || q.text)} <span style="font-size:0.8rem; color:#2c7da0;">(${marks} marks)</span></div><div id="q_${idx}_fields"></div>`;
            container.appendChild(div);
            const fieldsDiv = div.querySelector(`#q_${idx}_fields`);
            renderQuestionFields(q, idx, fieldsDiv);
        });
    }

    function collectAnswers() {
        let answers = {};
        questionsData.forEach((q, idx) => {
            const type = q.type || 'short_answer';
            let value = null;
            if (type === 'multiple_choice' || type === 'true_false') {
                let selected = document.querySelector(`input[name="q_${idx}"]:checked`);
                value = selected ? selected.value : '';
            } else if (type === 'short_answer' || type === 'essay' || type === 'sentence_completion') {
                let textarea = document.querySelector(`textarea[name="q_${idx}"]`);
                value = textarea ? textarea.value : '';
            } else if (type === 'multiple_selection') {
                let checkboxes = document.querySelectorAll(`input[name="q_${idx}[]"]:checked`);
                value = Array.from(checkboxes).map(cb => cb.value);
            } else if (type === 'matching') {
                let count = parseInt(document.querySelector(`input[name="q_${idx}_match_count"]`)?.value) || 0;
                let matches = {};
                for (let i=0; i<count; i++) {
                    let val = document.querySelector(`select[name="q_${idx}_match_${i}"]`)?.value;
                    if (val) matches[i] = val;
                }
                value = matches;
            } else if (type === 'fill_table') {
                let rows = parseInt(document.querySelector(`input[name="q_${idx}_table_rows"]`)?.value) || 0;
                let cols = parseInt(document.querySelector(`input[name="q_${idx}_table_cols"]`)?.value) || 0;
                let data = [];
                for (let r=0; r<rows; r++) {
                    let row = [];
                    for (let c=0; c<cols; c++) {
                        let inp = document.querySelector(`input[name="q_${idx}_cell_${r}_${c}"]`);
                        row.push(inp ? inp.value : '');
                    }
                    data.push(row);
                }
                value = data;
            } else if (type === 'arrange_steps') {
                let steps = [];
                let inputs = document.querySelectorAll(`input[name^="q_${idx}_step_"]`);
                inputs.forEach(inp => steps.push(inp.value));
                value = steps;
            } else {
                let textarea = document.querySelector(`textarea[name="q_${idx}"]`);
                value = textarea ? textarea.value : '';
            }
            answers[idx] = value;
        });
        return answers;
    }

    document.getElementById('quizForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let answers = collectAnswers();
        let ansField = document.createElement('input');
        ansField.type = 'hidden';
        ansField.name = 'answers_json';
        ansField.value = JSON.stringify(answers);
        this.appendChild(ansField);
        this.submit();
    });

    // Anti‑cheat: detect tab/window blur, copy/paste, keyboard shortcuts, right-click, F5
    let blurInterval = setInterval(() => {
        if (document.hidden || !document.hasFocus()) {
            violations++;
            violationsInput.value = violations;
            violationDiv.style.display = 'flex';
            violationDiv.querySelector('span').textContent = `⚠️ Tab/window switch detected (${violations}/5).`;
            if (violations >= 5) {
                clearInterval(blurInterval);
                setTimeout(() => document.getElementById('quizForm').submit(), 500);
            }
        }
    }, 800);
    document.addEventListener('contextmenu', e => e.preventDefault());
    const blockAction = (e) => {
        e.preventDefault();
        keyboardViolations++;
        keyboardInput.value = keyboardViolations;
        keyboardDiv.style.display = 'flex';
        keyboardDiv.querySelector('span').textContent = `⚠️ Copy/paste disabled (${keyboardViolations}/5).`;
        if (keyboardViolations >= 5) setTimeout(() => document.getElementById('quizForm').submit(), 500);
    };
    document.addEventListener('copy', blockAction);
    document.addEventListener('cut', blockAction);
    document.addEventListener('paste', blockAction);
    document.addEventListener('keydown', (e) => {
        const isCtrl = e.ctrlKey || e.metaKey;
        const isAlt = e.altKey;
        const key = e.key.toLowerCase();
        const active = document.activeElement;
        const isTextInput = active.tagName === 'TEXTAREA' || (active.tagName === 'INPUT' && (active.type === 'text' || active.type === 'email' || active.type === 'number'));
        if (isTextInput && !isCtrl && !isAlt && key.length === 1) return;
        if (isCtrl && (key === 'c' || key === 'v' || key === 'x' || key === 'z' || key === 'r')) {
            e.preventDefault();
            keyboardViolations++; keyboardInput.value = keyboardViolations;
            keyboardDiv.style.display = 'flex';
            keyboardDiv.querySelector('span').textContent = `⚠️ Ctrl+${key.toUpperCase()} disabled (${keyboardViolations}/5).`;
            if (keyboardViolations >= 5) setTimeout(() => document.getElementById('quizForm').submit(), 500);
        }
        if (key === 'f5') {
            e.preventDefault();
            keyboardViolations++; keyboardInput.value = keyboardViolations;
            keyboardDiv.style.display = 'flex';
            keyboardDiv.querySelector('span').textContent = `⚠️ Refresh (F5) disabled (${keyboardViolations}/5).`;
            if (keyboardViolations >= 5) setTimeout(() => document.getElementById('quizForm').submit(), 500);
        }
        if (isAlt && key === 'tab') {
            keyboardViolations++; keyboardInput.value = keyboardViolations;
            keyboardDiv.style.display = 'flex';
            keyboardDiv.querySelector('span').textContent = `⚠️ Alt+Tab detected (${keyboardViolations}/5).`;
            if (keyboardViolations >= 5) setTimeout(() => document.getElementById('quizForm').submit(), 500);
        }
    });

    renderQuestions();
</script>
</body>
</html>