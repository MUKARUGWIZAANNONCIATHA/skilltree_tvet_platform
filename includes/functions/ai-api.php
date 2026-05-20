<?php
/**
 * AI API Helper - OpenAI Integration
 * Path: /includes/functions/ai-api.php
 */

function callAIAPI($messages, $model = 'gpt-3.5-turbo', $maxTokens = 1000, $temperature = 0.7) {
    $apiKey = defined('AI_API_KEY') ? AI_API_KEY : '';
    if (empty($apiKey)) {
        return fallbackResponse($messages);
    }

    $url = defined('AI_API_URL') ? AI_API_URL : 'https://api.openai.com/v1/chat/completions';

    $data = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => $maxTokens,
        'temperature' => $temperature
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("AI API Curl Error: " . $error);
        return fallbackResponse($messages);
    }

    if ($httpCode !== 200) {
        error_log("AI API HTTP Error: $httpCode - $response");
        return fallbackResponse($messages);
    }

    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    }

    error_log("AI API Parse Error: " . $response);
    return fallbackResponse($messages);
}

function fallbackResponse($messages) {
    $lastMsg = end($messages);
    $userMsg = strtolower($lastMsg['content'] ?? '');

    if (stripos($userMsg, 'hello') !== false || stripos($userMsg, 'hi') !== false) {
        return "Hello! I'm your AI learning assistant. How can I help you today? You can ask me to:\n- Explain concepts (Teacher mode)\n- Identify areas to improve (Mentor mode)\n- Give you practice tasks (Coach mode)";
    }

    $keywords = [
        'sql' => "SQL (Structured Query Language) is used to manage databases. Common commands include SELECT, INSERT, UPDATE, DELETE. For example: SELECT * FROM users WHERE role = 'student';",
        'join' => "A SQL JOIN combines rows from two or more tables. INNER JOIN returns matching records, LEFT JOIN returns all from left table plus matches. Example: SELECT * FROM students JOIN enrollments ON students.id = enrollments.student_id;",
        'normalization' => "Normalization reduces data redundancy. 1NF requires atomic values, 2NF removes partial dependencies, 3NF removes transitive dependencies. This ensures database efficiency.",
        'database' => "A database is an organized collection of data. In relational databases, data is stored in tables with rows and columns, linked by foreign keys.",
        'primary key' => "A primary key uniquely identifies each row in a table. It cannot be NULL and must be unique. Example: student_id in a Students table.",
        'foreign key' => "A foreign key links two tables together. It references a primary key in another table to maintain referential integrity.",
        'function' => "A function is a reusable block of code that performs a specific task. Functions take input (parameters), process it, and return output.",
        'variable' => "A variable stores data in memory. In programming, you declare a variable like: let name = 'John'; or int age = 25;",
        'loop' => "Loops repeat code until a condition is met. Common types: for (count-controlled), while (condition-controlled), do-while (executes at least once).",
        'array' => "An array stores multiple values in one variable. Indexed arrays use numeric keys, associative arrays use named keys.",
        'oop' => "Object-Oriented Programming (OOP) organizes code into classes and objects. Key concepts: encapsulation, inheritance, polymorphism, abstraction.",
        'api' => "API (Application Programming Interface) allows different software systems to communicate. REST APIs use HTTP methods: GET, POST, PUT, DELETE.",
        'html' => "HTML (HyperText Markup Language) structures web content using tags like div, p, h1, a, img. Each tag has an opening and closing tag.",
        'css' => "CSS (Cascading Style Sheets) styles HTML elements. You can control colors, fonts, layout, and responsive design with CSS rules.",
        'javascript' => "JavaScript adds interactivity to websites. It runs in the browser and can manipulate DOM, handle events, and make AJAX requests.",
        'python' => "Python is a versatile programming language known for readability. Used in web development, data science, AI, and automation.",
        'php' => "PHP is a server-side scripting language for web development. It can generate dynamic content, handle forms, and connect to databases.",
    ];

    foreach ($keywords as $key => $text) {
        if (stripos($userMsg, $key) !== false) {
            return $text . "\n\nWould you like me to explain more or provide an example?";
        }
    }

    return "I understand you're asking about \"{$lastMsg['content']}\". To give you the best answer, I recommend:\n1. Check your course materials for this topic\n2. Try the practice exercises\n3. Ask me to explain specific concepts\n\nCan you be more specific about what you'd like to learn?";
}

function getAIMentorResponse($studentData, $message) {
    $systemPrompt = "You are a helpful AI Mentor for TVET (Technical Vocational Education & Training) students. Your role is to:
1. Identify areas where the student needs improvement based on their performance data
2. Suggest specific study strategies and resources
3. Provide encouraging, constructive feedback
4. Recommend practice exercises for weak areas
5. Track progress and celebrate improvements

The student is studying: {$studentData['trade']}
Their enrolled modules include: {$studentData['modules']}
Their weak areas include: {$studentData['weakAreas']}

Be supportive and practical in your advice.";

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $message]
    ];

    return callAIAPI($messages);
}

function getAICoachResponse($studentData, $message) {
    $systemPrompt = "You are an AI Coach for TVET students. Your role is to:
1. Assign practical tasks and exercises relevant to the student's course
2. Set deadlines and challenge the student
3. Review completed work and provide feedback
4. Design progressive learning challenges
5. Keep the student motivated with achievable goals

The student is studying: {$studentData['trade']}
Their modules: {$studentData['modules']}
Current weak areas: {$studentData['weakAreas']}

Assign clear, actionable tasks with expected outcomes.";

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $message]
    ];

    return callAIAPI($messages);
}

function getAITeacherResponse($studentData, $message) {
    $systemPrompt = "You are an AI Teacher for TVET students. Your role is to:
1. Explain concepts clearly with real-world examples relevant to TVET
2. Break down complex topics into simple, understandable parts
3. Use analogies and practical demonstrations
4. Provide step-by-step tutorials
5. Answer follow-up questions patiently
6. Suggest additional learning resources

The student is studying: {$studentData['trade']}
Their modules: {$studentData['modules']}

Be thorough, clear, and use examples from the Rwandan/TVET context where possible.";

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $message]
    ];

    return callAIAPI($messages);
}
