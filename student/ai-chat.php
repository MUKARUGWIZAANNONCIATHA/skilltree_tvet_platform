<?php
/**
 * AI Chat - Mentor, Coach, Teacher Modes
 * Path: /student/ai-chat.php
 */

require_once '../config/database.php';
require_once '../includes/auth/session-check.php';
requireRole(['student']);

require_once '../includes/functions/common.php';
require_once '../includes/functions/ai-api.php';

$studentId = $_SESSION['user_id'];
$mode = $_GET['mode'] ?? 'mentor';

// Get student enrollment info
$stmt = $pdo->prepare("
    SELECT t.trade_name, GROUP_CONCAT(DISTINCT m.module_name SEPARATOR ', ') as modules
    FROM student_enrollments e
    JOIN trades t ON e.trade_id = t.trade_id
    JOIN modules m ON e.module_id = m.module_id
    WHERE e.student_id = ?
    GROUP BY t.trade_name
");
$stmt->execute([$studentId]);
$enrollment = $stmt->fetch();

// Get weak areas
$weakAreas = [];
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT t.topic_title
        FROM review_bank_attempts ra
        JOIN review_bank q ON ra.question_id = q.question_id
        JOIN topics t ON q.topic_id = t.topic_id
        WHERE ra.student_id = ? AND ra.is_correct = 0
        ORDER BY ra.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$studentId]);
    $weakAreas = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}

if (empty($weakAreas)) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT t.topic_title
            FROM student_progress sp
            JOIN topics t ON sp.topic_id = t.topic_id
            WHERE sp.student_id = ? AND sp.status = 'struggling'
            LIMIT 5
        ");
        $stmt->execute([$studentId]);
        $weakAreas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

$studentData = [
    'trade' => $enrollment['trade_name'] ?? 'General',
    'modules' => $enrollment['modules'] ?? 'Various',
    'weakAreas' => !empty($weakAreas) ? implode(', ', $weakAreas) : 'None identified yet'
];

// Handle AJAX chat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    $message = trim($_POST['message'] ?? '');
    $chatMode = $_POST['chat_mode'] ?? 'mentor';

    if (empty($message)) {
        echo json_encode(['reply' => 'Please type a message.']);
        exit;
    }

    switch ($chatMode) {
        case 'coach':
            $reply = getAICoachResponse($studentData, $message);
            $modeLabel = 'Coach';
            break;
        case 'teacher':
            $reply = getAITeacherResponse($studentData, $message);
            $modeLabel = 'Teacher';
            break;
        default:
            $reply = getAIMentorResponse($studentData, $message);
            $modeLabel = 'Mentor';
    }

    echo json_encode(['reply' => nl2br(htmlspecialchars($reply))]);
    exit;
}

include_once '../includes/templates/header.php';
?>

<div class="ai-chat-page">
    <div class="page-header">
        <h1><i class="fas fa-robot"></i> AI Learning Assistant</h1>
        <p>Your personal Mentor, Coach, and Teacher</p>
    </div>

    <!-- Mode Selector -->
    <div class="mode-selector">
        <a href="?mode=mentor" class="mode-card <?= $mode === 'mentor' ? 'active' : '' ?>" data-mode="mentor">
            <div class="mode-icon"><i class="fas fa-compass"></i></div>
            <div class="mode-name">Mentor</div>
            <div class="mode-desc">Identify weaknesses & improve</div>
        </a>
        <a href="?mode=coach" class="mode-card <?= $mode === 'coach' ? 'active' : '' ?>" data-mode="coach">
            <div class="mode-icon"><i class="fas fa-dumbbell"></i></div>
            <div class="mode-name">Coach</div>
            <div class="mode-desc">Assign tasks & challenge you</div>
        </a>
        <a href="?mode=teacher" class="mode-card <?= $mode === 'teacher' ? 'active' : '' ?>" data-mode="teacher">
            <div class="mode-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="mode-name">Teacher</div>
            <div class="mode-desc">Explain topics step by step</div>
        </a>
    </div>

    <!-- Chat Container -->
    <div class="chat-container">
        <div class="chat-header">
            <div class="chat-mode-label">
                <span class="mode-badge <?= $mode ?>">
                    <i class="fas fa-<?= $mode === 'mentor' ? 'compass' : ($mode === 'coach' ? 'dumbbell' : 'chalkboard-teacher') ?>"></i>
                    <?= ucfirst($mode) ?> Mode
                </span>
            </div>
            <div class="chat-status"><?= htmlspecialchars($enrollment['trade_name'] ?? 'General') ?></div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="message ai-message">
                <div class="message-avatar"><i class="fas fa-robot"></i></div>
                <div class="message-content">
                    <div class="message-sender">AI <?= ucfirst($mode) ?></div>
                    <div class="message-text">
                        <?php if ($mode === 'mentor'): ?>
                            Hi there! I'm your AI <strong>Mentor</strong>. I'll help you identify areas for improvement and suggest ways to strengthen your skills.
                            <?php if (!empty($weakAreas)): ?>
                            <br><br>Based on your progress, you might want to focus on:
                            <ul>
                                <?php foreach ($weakAreas as $area): ?>
                                <li><?= htmlspecialchars($area) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            Ask me about any topic or tell me what you're struggling with!
                            <?php else: ?>
                            <br><br>Keep up the good work! Ask me anything about your courses or tell me if you're finding any topic difficult.
                            <?php endif; ?>
                        <?php elseif ($mode === 'coach'): ?>
                            Welcome to <strong>Coach</strong> mode! I'm here to challenge you with practical tasks and exercises.
                            <br><br>Tell me what skill or topic you want to practice, and I'll assign you a task. Complete it and I'll give you feedback!
                        <?php else: ?>
                            Welcome to <strong>Teacher</strong> mode! I'm here to explain concepts clearly with examples.
                            <br><br>Ask me about any topic in your course, and I'll break it down for you step by step.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="chat-input-area">
            <div class="input-wrapper">
                <textarea id="chatInput" class="chat-input" rows="1" placeholder="Type your message..." onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}"></textarea>
                <button class="btn-send" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
            <div class="suggestions">
                <?php if ($mode === 'mentor'): ?>
                    <span class="suggestion-chip" onclick="useSuggestion(this)">What are my weak areas?</span>
                    <span class="suggestion-chip" onclick="useSuggestion(this)">How can I improve?</span>
                    <span class="suggestion-chip" onclick="useSuggestion(this)">Give me study tips</span>
                <?php elseif ($mode === 'coach'): ?>
                    <span class="suggestion-chip" onclick="useSuggestion(this)">Assign me a task</span>
                    <span class="suggestion-chip" onclick="useSuggestion(this)">Challenge me</span>
                    <span class="suggestion-chip" onclick="useSuggestion(this)">Practice exercise please</span>
                <?php else: ?>
                    <span class="suggestion-chip" onclick="useSuggestion(this)">Explain a concept</span>
                    <span class="suggestion-chip" onclick="useSuggestion(this)">Give me an example</span>
                    <span class="suggestion-chip" onclick="useSuggestion(this)">Step by step tutorial</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hidden student data for JS -->
    <input type="hidden" id="currentMode" value="<?= $mode ?>">
</div>

<style>
.ai-chat-page { max-width: 900px; margin: 0 auto; padding: 30px 20px; }
.page-header { margin-bottom: 25px; }
.page-header h1 { font-size: 28px; color: #1a1a2e; margin-bottom: 5px; }

/* Mode Selector */
.mode-selector { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px; }
.mode-card { background: white; border-radius: 16px; padding: 20px; text-align: center; text-decoration: none; color: #333; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: 0.3s; border: 2px solid transparent; }
.mode-card:hover { transform: translateY(-3px); box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
.mode-card.active { border-color: #667eea; background: #f0f2ff; }
.mode-icon { font-size: 32px; margin-bottom: 10px; }
.mode-card[data-mode="mentor"] .mode-icon { color: #4CAF50; }
.mode-card[data-mode="coach"] .mode-icon { color: #FF9800; }
.mode-card[data-mode="teacher"] .mode-icon { color: #2196F3; }
.mode-card.active[data-mode="mentor"] .mode-icon { color: #4CAF50; }
.mode-card.active[data-mode="coach"] .mode-icon { color: #FF9800; }
.mode-card.active[data-mode="teacher"] .mode-icon { color: #2196F3; }
.mode-name { font-weight: 700; font-size: 16px; margin-bottom: 5px; }
.mode-desc { font-size: 12px; color: #666; }

/* Chat Container */
.chat-container { background: white; border-radius: 20px; box-shadow: 0 2px 20px rgba(0,0,0,0.08); overflow: hidden; display: flex; flex-direction: column; height: 550px; }
.chat-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
.mode-badge { padding: 6px 15px; border-radius: 20px; font-size: 13px; font-weight: 600; color: white; }
.mode-badge.mentor { background: #4CAF50; }
.mode-badge.coach { background: #FF9800; }
.mode-badge.teacher { background: #2196F3; }
.chat-status { font-size: 13px; color: #666; }
.chat-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 15px; }

.message { display: flex; gap: 12px; max-width: 85%; }
.message.ai-message { align-self: flex-start; }
.message.user-message { align-self: flex-end; flex-direction: row-reverse; }
.message-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.ai-message .message-avatar { background: #e8f0fe; color: #2196F3; }
.user-message .message-avatar { background: #667eea; color: white; }
.message-content { padding: 12px 16px; border-radius: 16px; }
.ai-message .message-content { background: #f8f9fa; border-top-left-radius: 4px; }
.user-message .message-content { background: #667eea; color: white; border-top-right-radius: 4px; }
.message-sender { font-size: 11px; font-weight: 600; margin-bottom: 5px; opacity: 0.7; }
.user-message .message-sender { color: rgba(255,255,255,0.8); }
.message-text { line-height: 1.6; font-size: 14px; }
.message-text ul { margin: 8px 0; padding-left: 20px; }
.message-text ul li { margin-bottom: 4px; }

/* Input Area */
.chat-input-area { padding: 15px 20px; border-top: 1px solid #eee; background: #fafafa; }
.input-wrapper { display: flex; gap: 10px; align-items: flex-end; }
.chat-input { flex: 1; padding: 12px 16px; border: 1px solid #ddd; border-radius: 24px; font-size: 14px; resize: none; outline: none; font-family: inherit; max-height: 100px; }
.chat-input:focus { border-color: #667eea; }
.btn-send { width: 44px; height: 44px; border-radius: 50%; border: none; background: #667eea; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: 0.2s; }
.btn-send:hover { background: #5a6fd6; transform: scale(1.05); }
.suggestions { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
.suggestion-chip { padding: 5px 14px; background: white; border: 1px solid #ddd; border-radius: 20px; font-size: 12px; cursor: pointer; transition: 0.2s; color: #666; }
.suggestion-chip:hover { background: #667eea; color: white; border-color: #667eea; }

/* Loading */
.typing-indicator { display: flex; gap: 4px; padding: 5px 0; }
.typing-indicator span { width: 8px; height: 8px; background: #999; border-radius: 50%; animation: typing 1.4s infinite; }
.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing { 0%, 60%, 100% { opacity: 0.3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-4px); } }

@media (max-width: 600px) {
    .mode-selector { grid-template-columns: 1fr; }
    .chat-container { height: 450px; }
}
</style>

<script>
let chatMode = document.getElementById('currentMode').value;

function sendMessage() {
    let input = document.getElementById('chatInput');
    let text = input.value.trim();
    if (!text) return;

    addMessage(text, 'user');
    input.value = '';

    let loadingDiv = document.createElement('div');
    loadingDiv.className = 'message ai-message';
    loadingDiv.id = 'loadingMessage';
    loadingDiv.innerHTML = `<div class="message-avatar"><i class="fas fa-robot"></i></div><div class="message-content"><div class="message-sender">AI ${chatMode.charAt(0).toUpperCase() + chatMode.slice(1)}</div><div class="typing-indicator"><span></span><span></span><span></span></div></div>`;
    document.getElementById('chatMessages').appendChild(loadingDiv);
    scrollToBottom();

    let formData = new FormData();
    formData.append('ajax', '1');
    formData.append('message', text);
    formData.append('chat_mode', chatMode);

    fetch('ai-chat.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('loadingMessage')?.remove();
        addMessage(data.reply, 'ai');
    })
    .catch(() => {
        document.getElementById('loadingMessage')?.remove();
        addMessage('Sorry, I encountered an error. Please try again.', 'ai');
    });
}

function addMessage(text, sender) {
    let div = document.createElement('div');
    div.className = `message ${sender}-message`;
    let avatar = sender === 'user'
        ? `<div class="message-avatar">${document.querySelector('.user-avatar')?.innerText || 'U'}</div>`
        : `<div class="message-avatar"><i class="fas fa-robot"></i></div>`;
    let senderName = sender === 'user' ? 'You' : `AI ${chatMode.charAt(0).toUpperCase() + chatMode.slice(1)}`;
    div.innerHTML = `${avatar}<div class="message-content"><div class="message-sender">${senderName}</div><div class="message-text">${text}</div></div>`;
    document.getElementById('chatMessages').appendChild(div);
    scrollToBottom();
}

function scrollToBottom() {
    let container = document.getElementById('chatMessages');
    container.scrollTop = container.scrollHeight;
}

function useSuggestion(el) {
    document.getElementById('chatInput').value = el.innerText;
    sendMessage();
}

function switchMode(mode) {
    chatMode = mode;
    document.getElementById('currentMode').value = mode;
    document.querySelectorAll('.mode-card').forEach(c => c.classList.toggle('active', c.dataset.mode === mode));
    document.getElementById('chatMessages').innerHTML = '';
    window.location.href = '?mode=' + mode;
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>
