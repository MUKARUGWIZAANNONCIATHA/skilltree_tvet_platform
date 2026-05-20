<?php
/**
 * TVET Curriculum Parser - Reads exactly as pasted with proper hierarchy
 * Path: /includes/functions/curriculum-parser-text.php
 */

class TextCurriculumParser {
    private $text;
    private $moduleId;
    private $pdo;
    
    public function __construct($text, $moduleId, $pdo) {
        $this->text = $text;
        $this->moduleId = $moduleId;
        $this->pdo = $pdo;
    }
    
    /**
     * Preview the parsed curriculum without saving
     */
    public function preview() {
        $outcomes = $this->parseCurriculum();
        return [
            'learning_outcomes' => $outcomes
        ];
    }
    
    /**
     * Parse and save curriculum directly
     */
    public function parseAndSave() {
        try {
            $this->clearExistingData();
            $outcomes = $this->parseCurriculum();
            
            if (empty($outcomes)) {
                return ['success' => false, 'error' => 'No learning outcomes found. Please use correct format.'];
            }
            
            $this->saveOutcomes($outcomes);
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Save curriculum with pre-parsed data (from preview)
     */
    public function parseAndSaveWithData($previewData) {
        try {
            $this->clearExistingData();
            $this->saveOutcomes($previewData['learning_outcomes']);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Clear existing data for this module
     */
    private function clearExistingData() {
        $this->pdo->prepare("DELETE FROM subtopic_details WHERE subtopic_id IN (SELECT subtopic_id FROM subtopics WHERE topic_id IN (SELECT topic_id FROM topics WHERE ic_id IN (SELECT ic_id FROM indicative_contents WHERE module_id = ?)))")->execute([$this->moduleId]);
        $this->pdo->prepare("DELETE FROM subtopics WHERE topic_id IN (SELECT topic_id FROM topics WHERE ic_id IN (SELECT ic_id FROM indicative_contents WHERE module_id = ?))")->execute([$this->moduleId]);
        $this->pdo->prepare("DELETE FROM topics WHERE ic_id IN (SELECT ic_id FROM indicative_contents WHERE module_id = ?)")->execute([$this->moduleId]);
        $this->pdo->prepare("DELETE FROM indicative_contents WHERE module_id = ?")->execute([$this->moduleId]);
        $this->pdo->prepare("DELETE FROM learning_outcomes WHERE module_id = ?")->execute([$this->moduleId]);
    }
    
    /**
     * Parse curriculum structure
     */
    private function parseCurriculum() {
        $outcomes = [];
        $lines = explode("\n", $this->text);
        
        $currentOutcome = null;
        $currentIC = null;
        $currentTopic = null;
        
        $icOrder = 1;
        $topicOrder = 1;
        $subtopicOrder = 1;
        
        foreach ($lines as $line) {
            $line = rtrim($line);
            $line = trim($line);
            
            if (empty($line)) continue;
            
            // ============================================================
            // DETECT LEARNING OUTCOME
            // Matches: "LEARNING OUTCOME 1: Analyse Database (20 hours)"
            // ============================================================
            if (preg_match('/^LEARNING\s+OUTCOME\s+(\d+):\s*(.+?)(?:\s*\((\d+)\s*hours?\))?$/i', $line, $matches)) {
                if ($currentOutcome) {
                    $outcomes[] = $currentOutcome;
                }
                $currentOutcome = [
                    'number' => intval($matches[1]),
                    'description' => trim($matches[2]),
                    'hours' => isset($matches[3]) ? intval($matches[3]) : 20,
                    'indicative_contents' => []
                ];
                $icOrder = 1;
                $topicOrder = 1;
                $subtopicOrder = 1;
                $currentIC = null;
                $currentTopic = null;
                continue;
            }
            
            if (!$currentOutcome) continue;
            
            // ============================================================
            // DETECT INDICATIVE CONTENT
            // Matches: "INDICATIVE CONTENT 1.1: Database fundamentals"
            // ============================================================
            if (preg_match('/^INDICATIVE\s+CONTENT\s+[\d.]+:\s*(.+)$/i', $line, $matches)) {
                $icTitle = trim($matches[1]);
                $currentIC = [
                    'title' => $icTitle,
                    'order' => $icOrder++,
                    'topics' => []
                ];
                $currentOutcome['indicative_contents'][] = $currentIC;
                $topicOrder = 1;
                $currentTopic = null;
                continue;
            }
            
            // ============================================================
            // DETECT TOPIC
            // Matches: "TOPIC: Description of database fundamental"
            // ============================================================
            if (preg_match('/^TOPIC:\s*(.+)$/i', $line, $matches)) {
                $topicTitle = trim($matches[1]);
                $currentTopic = [
                    'title' => $topicTitle,
                    'order' => $topicOrder++,
                    'subtopics' => []
                ];
                if ($currentIC) {
                    $lastICIndex = count($currentOutcome['indicative_contents']) - 1;
                    if ($lastICIndex >= 0) {
                        $currentOutcome['indicative_contents'][$lastICIndex]['topics'][] = $currentTopic;
                        $currentIC = $currentOutcome['indicative_contents'][$lastICIndex];
                    }
                }
                $subtopicOrder = 1;
                continue;
            }
            
            // ============================================================
            // DETECT SUBTOPIC (with ✓)
            // Matches: "✓ Subtopic: Definition of key terms"
            // ============================================================
            if (preg_match('/^✓\s*Subtopic:\s*(.+)$/i', $line, $matches)) {
                $subtopicTitle = trim($matches[1]);
                if ($currentTopic) {
                    $subtopic = [
                        'title' => $subtopicTitle,
                        'order' => $subtopicOrder++,
                        'details' => []
                    ];
                    $currentTopic['subtopics'][] = $subtopic;
                    // Update back
                    if ($currentIC) {
                        $lastICIndex = count($currentOutcome['indicative_contents']) - 1;
                        $lastTopicIndex = count($currentOutcome['indicative_contents'][$lastICIndex]['topics']) - 1;
                        if ($lastTopicIndex >= 0) {
                            $currentOutcome['indicative_contents'][$lastICIndex]['topics'][$lastTopicIndex] = $currentTopic;
                            $currentIC = $currentOutcome['indicative_contents'][$lastICIndex];
                        }
                    }
                }
                continue;
            }
            
            // ============================================================
            // DETECT DETAIL (bullet point •)
            // Matches: "• Database"
            // ============================================================
            if (preg_match('/^•\s*(.+)$/', $line, $matches)) {
                $detailText = trim($matches[1]);
                if ($currentTopic && !empty($currentTopic['subtopics'])) {
                    $lastSubtopicIndex = count($currentTopic['subtopics']) - 1;
                    if ($lastSubtopicIndex >= 0) {
                        $currentTopic['subtopics'][$lastSubtopicIndex]['details'][] = $detailText;
                        // Update back
                        if ($currentIC) {
                            $lastICIndex = count($currentOutcome['indicative_contents']) - 1;
                            $lastTopicIndex = count($currentOutcome['indicative_contents'][$lastICIndex]['topics']) - 1;
                            if ($lastTopicIndex >= 0) {
                                $currentOutcome['indicative_contents'][$lastICIndex]['topics'][$lastTopicIndex] = $currentTopic;
                            }
                        }
                    }
                }
                continue;
            }
        }
        
        if ($currentOutcome) {
            $outcomes[] = $currentOutcome;
        }
        
        return $outcomes;
    }
    
    /**
     * Save outcomes to database
     */
    private function saveOutcomes($outcomes) {
        foreach ($outcomes as $outcome) {
            $stmt = $this->pdo->prepare("INSERT INTO learning_outcomes (module_id, outcome_number, description, learning_hours, order_position) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$this->moduleId, $outcome['number'], $outcome['description'], $outcome['hours'], $outcome['number']]);
            $outcomeId = $this->pdo->lastInsertId();
            
            foreach ($outcome['indicative_contents'] as $ic) {
                $stmt = $this->pdo->prepare("INSERT INTO indicative_contents (module_id, outcome_id, ic_title, ic_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$this->moduleId, $outcomeId, $ic['title'], $ic['order']]);
                $icId = $this->pdo->lastInsertId();
                
                foreach ($ic['topics'] as $topic) {
                    $stmt = $this->pdo->prepare("INSERT INTO topics (ic_id, topic_title, topic_order) VALUES (?, ?, ?)");
                    $stmt->execute([$icId, $topic['title'], $topic['order']]);
                    $topicId = $this->pdo->lastInsertId();
                    
                    foreach ($topic['subtopics'] as $subtopic) {
                        $stmt = $this->pdo->prepare("INSERT INTO subtopics (topic_id, subtopic_title, subtopic_order, has_checkmark) VALUES (?, ?, ?, 1)");
                        $stmt->execute([$topicId, $subtopic['title'], $subtopic['order']]);
                        $subtopicId = $this->pdo->lastInsertId();
                        
                        foreach ($subtopic['details'] as $detail) {
                            $stmt = $this->pdo->prepare("INSERT INTO subtopic_details (subtopic_id, detail_text, detail_order) VALUES (?, ?, ?)");
                            $stmt->execute([$subtopicId, $detail, 1]);
                        }
                    }
                }
            }
        }
    }
}
?>