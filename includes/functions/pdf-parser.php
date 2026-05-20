<?php
/**
 * AI PDF Curriculum Parser - Automatic Insert
 * Path: /includes/functions/pdf-parser.php
 */

class CurriculumParser {
    private $filePath;
    private $text;
    private $moduleId;
    
    public function __construct($filePath, $moduleId = null) {
        $this->filePath = $filePath;
        $this->moduleId = $moduleId;
        $this->extractText();
    }
    
    private function extractText() {
        // Extract text from PDF (simplified for demo)
        // In production, use a proper PDF parsing library
        $this->text = file_get_contents($this->filePath);
        if (empty($this->text)) {
            $this->text = "";
        }
    }
    
    public function parseAndSave($pdo) {
        $data = $this->extractFullStructure();
        
        // Save to database automatically
        $savedData = $this->saveToDatabase($pdo, $data);
        
        return [
            'success' => true,
            'module_id' => $this->moduleId,
            'parsed_data' => $data,
            'saved_data' => $savedData
        ];
    }
    
    private function extractFullStructure() {
        $structure = [
            'module_info' => $this->extractModuleInfo(),
            'learning_outcomes' => $this->extractLearningOutcomes()
        ];
        
        // If no structure found, use default template
        if (empty($structure['learning_outcomes'])) {
            $structure['learning_outcomes'] = $this->getDefaultStructure();
        }
        
        return $structure;
    }
    
    private function extractModuleInfo() {
        return [
            'code' => $this->extractModuleCode(),
            'name' => $this->extractModuleName(),
            'credits' => $this->extractCredits(),
            'level' => $this->extractLevel(),
            'hours' => $this->extractHours()
        ];
    }
    
    private function extractModuleCode() {
        if (preg_match('/\b([A-Z]{3,6}\d{3,4})\b/', $this->text, $matches)) {
            return $matches[1];
        }
        return 'NEW' . time();
    }
    
    private function extractModuleName() {
        if (preg_match('/\b([A-Z\s]{10,50})\b/', $this->text, $matches)) {
            return trim($matches[1]);
        }
        return 'Module ' . date('Ymd');
    }
    
    private function extractCredits() {
        if (preg_match('/Credits?\s*:?\s*(\d+)/i', $this->text, $matches)) {
            return intval($matches[1]);
        }
        return 10;
    }
    
    private function extractLevel() {
        if (preg_match('/Level\s*:?\s*(\d+)/i', $this->text, $matches)) {
            return intval($matches[1]);
        }
        return 4;
    }
    
    private function extractHours() {
        if (preg_match('/Hours?\s*:?\s*(\d+)/i', $this->text, $matches)) {
            return intval($matches[1]);
        }
        return 120;
    }
    
    private function extractLearningOutcomes() {
        $outcomes = [];
        
        // Pattern 1: "Learning Outcome 1: ..."
        preg_match_all('/Learning\s+Outcome\s+(\d+)[:\s-]+([^\n]+)/i', $this->text, $matches, PREG_SET_ORDER);
        
        if (empty($matches)) {
            // Pattern 2: "LO1: ..."
            preg_match_all('/LO\s*(\d+)[:\s-]+([^\n]+)/i', $this->text, $matches, PREG_SET_ORDER);
        }
        
        foreach ($matches as $match) {
            $outcomeNum = intval($match[1]);
            $outcomes[] = [
                'number' => $outcomeNum,
                'description' => trim($match[2]),
                'hours' => 20,
                'indicative_contents' => $this->extractIndicativeContents($outcomeNum)
            ];
        }
        
        return $outcomes;
    }
    
    private function extractIndicativeContents($outcomeNum) {
        $contents = [];
        
        // Extract based on patterns in text
        // This is simplified - in production, use more sophisticated parsing
        
        // Return appropriate content based on outcome number
        if ($outcomeNum == 1) {
            $contents = [
                ['title' => 'Fundamentals', 'topics' => $this->getTopicsForOutcome1()],
                ['title' => 'Core Concepts', 'topics' => $this->getTopicsForOutcome1Part2()]
            ];
        } elseif ($outcomeNum == 2) {
            $contents = [
                ['title' => 'Implementation', 'topics' => $this->getTopicsForOutcome2()],
                ['title' => 'Advanced Topics', 'topics' => $this->getTopicsForOutcome2Part2()]
            ];
        } else {
            $contents = [
                ['title' => 'Topic Area', 'topics' => $this->getDefaultTopics()]
            ];
        }
        
        return $contents;
    }
    
    private function getTopicsForOutcome1() {
        return [
            ['title' => 'Introduction', 'subtopics' => [
                ['title' => 'Key Concepts', 'details' => ['Definition', 'Purpose', 'Benefits']],
                ['title' => 'Applications', 'details' => ['Real-world Examples', 'Use Cases']]
            ]],
            ['title' => 'Models and Types', 'subtopics' => [
                ['title' => 'Relational Model', 'details' => ['Tables', 'Rows', 'Columns']],
                ['title' => 'NoSQL', 'details' => ['Document', 'Key-Value', 'Graph']]
            ]]
        ];
    }
    
    private function getTopicsForOutcome1Part2() {
        return [
            ['title' => 'Design Principles', 'subtopics' => [
                ['title' => 'Normalization', 'details' => ['1NF', '2NF', '3NF']],
                ['title' => 'ER Diagrams', 'details' => ['Entities', 'Relationships', 'Cardinality']]
            ]]
        ];
    }
    
    private function getTopicsForOutcome2() {
        return [
            ['title' => 'SQL Basics', 'subtopics' => [
                ['title' => 'DDL Commands', 'details' => ['CREATE', 'ALTER', 'DROP']],
                ['title' => 'DML Commands', 'details' => ['INSERT', 'UPDATE', 'DELETE']]
            ]],
            ['title' => 'Queries', 'subtopics' => [
                ['title' => 'SELECT Statement', 'details' => ['WHERE', 'ORDER BY', 'GROUP BY']],
                ['title' => 'JOINs', 'details' => ['INNER', 'LEFT', 'RIGHT']]
            ]]
        ];
    }
    
    private function getTopicsForOutcome2Part2() {
        return [
            ['title' => 'Advanced SQL', 'subtopics' => [
                ['title' => 'Subqueries', 'details' => ['Nested Queries', 'Correlated Subqueries']],
                ['title' => 'Views and Indexes', 'details' => ['Creating Views', 'Indexing Strategies']]
            ]]
        ];
    }
    
    private function getDefaultTopics() {
        return [
            ['title' => 'Introduction', 'subtopics' => [
                ['title' => 'Overview', 'details' => ['Key Concepts', 'Learning Objectives']]
            ]],
            ['title' => 'Practical Application', 'subtopics' => [
                ['title' => 'Exercises', 'details' => ['Hands-on Practice', 'Case Studies']]
            ]]
        ];
    }
    
    private function getDefaultStructure() {
        return [
            [
                'number' => 1,
                'description' => 'Analyse Requirements and Design',
                'hours' => 30,
                'indicative_contents' => [
                    [
                        'title' => 'Fundamentals',
                        'topics' => $this->getTopicsForOutcome1()
                    ],
                    [
                        'title' => 'Design',
                        'topics' => $this->getTopicsForOutcome1Part2()
                    ]
                ]
            ],
            [
                'number' => 2,
                'description' => 'Implementation',
                'hours' => 45,
                'indicative_contents' => [
                    [
                        'title' => 'Core Implementation',
                        'topics' => $this->getTopicsForOutcome2()
                    ],
                    [
                        'title' => 'Advanced Features',
                        'topics' => $this->getTopicsForOutcome2Part2()
                    ]
                ]
            ]
        ];
    }
    
    private function saveToDatabase($pdo, $data) {
        $saved = [];
        
        try {
            // First, ensure module exists
            if (!$this->moduleId) {
                // Insert module if not exists
                $stmt = $pdo->prepare("INSERT INTO modules (module_code, module_name, credits, rqf_level, total_learning_hours, status) VALUES (?, ?, ?, ?, ?, 'draft')");
                $stmt->execute([
                    $data['module_info']['code'],
                    $data['module_info']['name'],
                    $data['module_info']['credits'],
                    $data['module_info']['level'],
                    $data['module_info']['hours']
                ]);
                $this->moduleId = $pdo->lastInsertId();
            }
            
            // Delete old curriculum for this module
            $pdo->prepare("DELETE FROM learning_outcomes WHERE module_id = ?")->execute([$this->moduleId]);
            
            // Insert new curriculum
            foreach ($data['learning_outcomes'] as $lo) {
                $stmt = $pdo->prepare("INSERT INTO learning_outcomes (module_id, outcome_number, description, learning_hours, order_position) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$this->moduleId, $lo['number'], $lo['description'], $lo['hours'], $lo['number']]);
                $outcomeId = $pdo->lastInsertId();
                $saved['outcomes'][] = $outcomeId;
                
                $icOrder = 1;
                foreach ($lo['indicative_contents'] as $ic) {
                    $stmt = $pdo->prepare("INSERT INTO indicative_contents (module_id, outcome_id, ic_title, ic_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$this->moduleId, $outcomeId, $ic['title'], $icOrder]);
                    $icId = $pdo->lastInsertId();
                    $icOrder++;
                    
                    $topicOrder = 1;
                    foreach ($ic['topics'] as $topic) {
                        $stmt = $pdo->prepare("INSERT INTO topics (ic_id, topic_title, topic_order) VALUES (?, ?, ?)");
                        $stmt->execute([$icId, $topic['title'], $topicOrder]);
                        $topicId = $pdo->lastInsertId();
                        $topicOrder++;
                        
                        $subtopicOrder = 1;
                        foreach ($topic['subtopics'] as $subtopic) {
                            $stmt = $pdo->prepare("INSERT INTO subtopics (topic_id, subtopic_title, subtopic_order, has_checkmark) VALUES (?, ?, ?, 1)");
                            $stmt->execute([$topicId, $subtopic['title'], $subtopicOrder]);
                            $subtopicId = $pdo->lastInsertId();
                            $subtopicOrder++;
                            
                            if (!empty($subtopic['details'])) {
                                $detailOrder = 1;
                                foreach ($subtopic['details'] as $detail) {
                                    $stmt = $pdo->prepare("INSERT INTO subtopic_details (subtopic_id, detail_text, detail_order) VALUES (?, ?, ?)");
                                    $stmt->execute([$subtopicId, $detail, $detailOrder]);
                                    $detailOrder++;
                                }
                            }
                        }
                    }
                }
            }
            
            return ['success' => true, 'module_id' => $this->moduleId];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>