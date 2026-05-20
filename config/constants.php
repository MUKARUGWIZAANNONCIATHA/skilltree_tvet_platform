<?php
/**
 * Global Constants
 * TVET Skill Tree Platform
 * NOTE: Sectors, Trades, and Levels are stored in DATABASE, not hardcoded here
 */

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_TEACHER', 'teacher');
define('ROLE_STUDENT', 'student');
define('ROLE_COMPANY', 'company');

// Bloom's Taxonomy Levels
define('BLOOM_REMEMBER', 'remember');
define('BLOOM_UNDERSTAND', 'understand');
define('BLOOM_APPLY', 'apply');
define('BLOOM_ANALYZE', 'analyze');
define('BLOOM_EVALUATE', 'evaluate');
define('BLOOM_CREATE', 'create');

$BLOOM_LEVELS = [
    BLOOM_REMEMBER => ['name' => 'Remember', 'color' => '#4CAF50', 'icon' => '🧠'],
    BLOOM_UNDERSTAND => ['name' => 'Understand', 'color' => '#2196F3', 'icon' => '📖'],
    BLOOM_APPLY => ['name' => 'Apply', 'color' => '#FF9800', 'icon' => '🔧'],
    BLOOM_ANALYZE => ['name' => 'Analyze', 'color' => '#9C27B0', 'icon' => '🔍'],
    BLOOM_EVALUATE => ['name' => 'Evaluate', 'color' => '#F44336', 'icon' => '⭐'],
    BLOOM_CREATE => ['name' => 'Create', 'color' => '#009688', 'icon' => '🚀']
];

// Question Types
define('QT_MULTIPLE_CHOICE', 'multiple_choice');
define('QT_TRUE_FALSE', 'true_false');
define('QT_SENTENCE_COMPLETION', 'sentence_completion');
define('QT_MULTIPLE_ANSWER', 'multiple_answer');
define('QT_SHORT_ANSWER', 'short_answer');
define('QT_ESSAY', 'essay');
define('QT_MATCHING', 'matching');

// Assessment Types
define('ASSESSMENT_TOPIC_QUIZ', 'topic_quiz');
define('ASSESSMENT_LO', 'learning_outcome');
define('ASSESSMENT_SUMMATIVE', 'summative');

// Progress Status
define('STATUS_LOCKED', 'locked');
define('STATUS_AVAILABLE', 'available');
define('STATUS_VIEWED', 'viewed');
define('STATUS_COMPLETED', 'completed');
define('STATUS_IN_PROGRESS', 'in_progress');

// Enrollment Status
define('ENROLLED', 'enrolled');
define('IN_PROGRESS', 'in_progress');
define('COMPLETED', 'completed');
define('DROPPED', 'dropped');

// Quiz Attempt Status
define('QUIZ_IN_PROGRESS', 'in_progress');
define('QUIZ_SUBMITTED', 'submitted');
define('QUIZ_GRADED', 'graded');
define('QUIZ_PASSED', 'passed');
define('QUIZ_FAILED', 'failed');

// Resource Types
define('RESOURCE_NOTES', 'notes');
define('RESOURCE_VIDEO', 'video');
define('RESOURCE_SCREENSHOT', 'screenshot');
define('RESOURCE_GUIDE', 'guide');
define('RESOURCE_EXERCISE', 'exercise');
define('RESOURCE_LINK', 'link');

// Past Paper Types
define('PAPER_NATIONAL', 'national');
define('PAPER_MOCK', 'mock');
define('PAPER_MID_TERM', 'mid_term');
define('PAPER_END_TERM', 'end_term');

// Difficulty Levels
define('DIFFICULTY_EASY', 'easy');
define('DIFFICULTY_MEDIUM', 'medium');
define('DIFFICULTY_HARD', 'hard');

// Job/Internship Application Status
define('APP_PENDING', 'pending');
define('APP_REVIEWED', 'reviewed');
define('APP_ACCEPTED', 'accepted');
define('APP_REJECTED', 'rejected');

// Company Requirement Levels
define('REQ_BASIC', 'basic');
define('REQ_INTERMEDIATE', 'intermediate');
define('REQ_ADVANCED', 'advanced');

// Module Status
define('MODULE_DRAFT', 'draft');
define('MODULE_PUBLISHED', 'published');
define('MODULE_ARCHIVED', 'archived');

// Past Paper Status
define('PAPER_UPLOADED', 'uploaded');
define('PAPER_PROCESSING', 'processing');
define('PAPER_READY', 'ready');
define('PAPER_PUBLISHED', 'published');

// Cache Keys
define('CACHE_MODULES', 'modules_list');
define('CACHE_SECTORS', 'sectors');
define('CACHE_TRADES', 'trades');
define('CACHE_LEVELS', 'levels');

// API Response Codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_SERVER_ERROR', 500);

// Error Messages
define('ERROR_DB_CONNECTION', 'Database connection failed. Please try again later.');
define('ERROR_INVALID_CREDENTIALS', 'Invalid email or password.');
define('ERROR_ACCESS_DENIED', 'Access denied. You do not have permission.');
define('ERROR_SESSION_EXPIRED', 'Your session has expired. Please login again.');
define('ERROR_FILE_UPLOAD', 'File upload failed. Please check file size and type.');
define('ERROR_INVALID_ROLE', 'Invalid user role.');

// Success Messages
define('SUCCESS_LOGIN', 'Login successful. Welcome back!');
define('SUCCESS_LOGOUT', 'You have been logged out successfully.');
define('SUCCESS_REGISTER', 'Registration successful. Please login.');
define('SUCCESS_SAVE', 'Data saved successfully.');
define('SUCCESS_DELETE', 'Data deleted successfully.');
define('SUCCESS_UPDATE', 'Data updated successfully.');

// TVET Defaults (only for reference, actual values from database)
define('DEFAULT_RQF_LEVEL', 4);

?>