<?php
/**
 * Student AI Assistant - Professional Learning Assistant
 * Path: /student/ai-assistant.php
 */

require_once '../config/database.php';
require_once '../includes/auth/session-check.php';

// Only students can use this assistant
requireRole(['student']);

$studentId = $_SESSION['user_id'];
$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(['reply' => 'Hi! Ask me anything about your courses. I’ll help you understand concepts you are struggling with.']);
    exit;
}

// -------------------------------------------------------------------
// 1. Get student's enrolled courses / trade / modules
// -------------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT t.trade_name, m.module_name, m.module_code
    FROM student_enrollments e
    JOIN trades t ON e.trade_id = t.trade_id
    JOIN modules m ON e.module_id = m.module_id
    WHERE e.student_id = ? 
    LIMIT 1
");
$stmt->execute([$studentId]);
$enrollment = $stmt->fetch();
$tradeName = $enrollment['trade_name'] ?? 'your course';
$moduleName = $enrollment['module_name'] ?? 'the module';

// -------------------------------------------------------------------
// 2. Identify weak topics (from review_bank attempts or quiz attempts)
// -------------------------------------------------------------------
// Get all topics where student answered incorrectly (or below passing score)
$weakTopics = [];

// If review_bank_attempts table exists (customize to your schema)
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT t.topic_title, t.topic_id, COUNT(*) as wrong_count
        FROM review_bank_attempts ra
        JOIN review_bank q ON ra.question_id = q.question_id
        JOIN topics t ON q.topic_id = t.topic_id
        WHERE ra.student_id = ? AND ra.is_correct = 0
        GROUP BY t.topic_id
        ORDER BY wrong_count DESC
        LIMIT 5
    ");
    $stmt->execute([$studentId]);
    $weakTopics = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table doesn't exist – fallback to generic
    $weakTopics = [];
}

// If no weak topics found, try from lessons (student progress)
if (empty($weakTopics)) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT t.topic_title, t.topic_id
            FROM student_progress sp
            JOIN topics t ON sp.topic_id = t.topic_id
            WHERE sp.student_id = ? AND sp.status = 'struggling'
            LIMIT 5
        ");
        $stmt->execute([$studentId]);
        $weakTopics = $stmt->fetchAll();
    } catch (PDOException $e) {}
}

// -------------------------------------------------------------------
// 3. Helper: get relevant resources for a topic (notes, videos, links)
// -------------------------------------------------------------------
function getResourcesForTopic($pdo, $topicId) {
    $resources = [];
    $stmt = $pdo->prepare("SELECT resource_type, title, url, content FROM topic_resources WHERE topic_id = ? LIMIT 3");
    $stmt->execute([$topicId]);
    $resources = $stmt->fetchAll();
    if (empty($resources)) {
        $stmt = $pdo->prepare("SELECT resource_type, title, url, content FROM subtopic_resources WHERE subtopic_id IN (SELECT subtopic_id FROM subtopics WHERE topic_id = ?) LIMIT 3");
        $stmt->execute([$topicId]);
        $resources = $stmt->fetchAll();
    }
    return $resources;
}

// -------------------------------------------------------------------
// 4. Build the AI reply (rule‑based + dynamic using weak topics)
// -------------------------------------------------------------------
$reply = "";

// Check if the question is about a specific topic from weak areas
$mentionedTopic = null;
foreach ($weakTopics as $wt) {
    if (stripos($message, $wt['topic_title']) !== false) {
        $mentionedTopic = $wt;
        break;
    }
}

if ($mentionedTopic) {
    $topicTitle = $mentionedTopic['topic_title'];
    $reply = "📘 I see you are asking about **{$topicTitle}**. ";
    $reply .= "This topic seems to be an area where you can improve. Let me help you understand it better.\n\n";
    
    // Get resources
    $resources = getResourcesForTopic($pdo, $mentionedTopic['topic_id']);
    if (!empty($resources)) {
        $reply .= "**Recommended resources:**\n";
        foreach ($resources as $res) {
            if ($res['resource_type'] == 'video' && $res['url']) {
                $reply .= "• 🎥 Watch: [{$res['title']}]({$res['url']})\n";
            } elseif ($res['resource_type'] == 'link' && $res['url']) {
                $reply .= "• 🔗 Read: [{$res['title']}]({$res['url']})\n";
            } elseif ($res['resource_type'] == 'note' && $res['content']) {
                $reply .= "• 📝 Note: {$res['title']} – " . substr(strip_tags($res['content']), 0, 100) . "...\n";
            }
        }
    } else {
        $reply .= "I couldn't find specific resources, but I can explain the concept. ";
    }
    
    // Provide a conceptual explanation (rule‑based or AI)
    $reply .= "\n**Brief explanation:**\n";
    $reply .= generateExplanation($message, $topicTitle);
    $reply .= "\n\n👉 **Practice suggestion:** Try the quiz on this topic again after reviewing the materials. If you still have questions, ask me for more details.";
    
} else {
    // No specific weak topic mentioned → general assistance
    $reply = "Hi! I'm your learning assistant for **{$tradeName}**. ";
    $reply .= "I noticed you've been working on **{$moduleName}**. ";
    
    if (!empty($weakTopics)) {
        $reply .= "Based on your performance, you might need extra help with:\n";
        foreach (array_slice($weakTopics, 0, 3) as $wt) {
            $reply .= "• {$wt['topic_title']}\n";
        }
        $reply .= "\nYou can ask me about any of these topics. For example, type: *'Explain normalization'*.\n\n";
    } else {
        $reply .= "How can I assist you today? Ask me about any concept, and I'll explain it with examples and resources.\n\n";
    }
    
    // Answer the student's direct question (generic)
    $reply .= "**Your question:** \"{$message}\"\n\n";
    $reply .= genericExplanation($message);
}

// -------------------------------------------------------------------
// Helper: generateExplanation (rule‑based, can be replaced with AI API)
// -------------------------------------------------------------------
function generateExplanation($message, $topicTitle) {
    // Simple keyword mapping - expand as needed
    $explanations = [
        'sql' => "Structured Query Language (SQL) is used to manage databases. A JOIN combines rows from two tables using a common field. Example: `SELECT * FROM Users JOIN Orders ON Users.id = Orders.user_id`.",
        'join' => "A JOIN clause in SQL is used to combine rows from two or more tables based on a related column. There are INNER JOIN, LEFT JOIN, RIGHT JOIN, and FULL OUTER JOIN.",
        'normalization' => "Normalization organizes data to reduce redundancy. For example, instead of storing customer address in every order, you store it once in a Customers table and link via foreign key.",
        'primary key' => "A primary key is a unique identifier for each record in a table. No two rows can have the same primary key, and it cannot be NULL.",
        'foreign key' => "A foreign key is a column that links to the primary key of another table, creating a relationship between tables.",
        'index' => "An index speeds up data retrieval. Think of it like the index in a book – you can quickly find a topic without reading every page.",
        'transaction' => "A transaction groups multiple SQL statements into one atomic operation. If any statement fails, the whole transaction is rolled back (ACID).",
    ];
    $lower = strtolower($message);
    foreach ($explanations as $key => $text) {
        if (strpos($lower, $key) !== false) {
            return $text;
        }
    }
    return "Let me explain {$topicTitle}. In short, {$topicTitle} is a key concept in your module. I recommend watching the attached video and reading the notes for a detailed understanding. Would you like a more specific explanation?";
}

function genericExplanation($message) {
    // Same as above but without topic assumption
    $keywords = [
        'sql' => "SQL is a standard language for storing, manipulating, and retrieving data in databases. For example, `SELECT * FROM students WHERE grade > 80`.",
        'database' => "A database is an organized collection of data. You can think of it like a digital filing cabinet.",
        'programming' => "Programming is writing instructions for computers to execute. Languages like Python, Java, or JavaScript are used to build software.",
        'react' => "React is a JavaScript library for building user interfaces. It uses components and a virtual DOM to update efficiently.",
        'javascript' => "JavaScript is a scripting language that makes websites interactive. It runs in the browser.",
        'nodejs' => "Node.js allows you to run JavaScript on the server side, enabling full‑stack development with one language.",
        'api' => "API (Application Programming Interface) is a set of rules that lets different software applications communicate with each other.",
    ];
    $lower = strtolower($message);
    foreach ($keywords as $key => $text) {
        if (strpos($lower, $key) !== false) {
            return $text . "\n\nWould you like more details or specific examples?";
        }
    }
    return "I'll help you understand that topic. Start by reviewing the lesson materials (notes and videos) related to your question. If you need a step‑by‑step explanation, just ask me to break it down!";
}

// -------------------------------------------------------------------
// 5. Output JSON response
// -------------------------------------------------------------------
echo json_encode(['reply' => nl2br($reply)]);