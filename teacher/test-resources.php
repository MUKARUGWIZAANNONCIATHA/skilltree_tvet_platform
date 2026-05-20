<?php
require_once '../config/database.php';

echo "<h1>Resources Test</h1>";

// Test 1: Check topics
$topics = $pdo->query("SELECT topic_id, topic_title FROM topics LIMIT 5")->fetchAll();
echo "<h2>Topics in database:</h2>";
echo "<ul>";
foreach($topics as $topic) {
    echo "<li>ID: {$topic['topic_id']} - {$topic['topic_title']}</li>";
}
echo "</ul>";

// Test 2: Check topic_resources
$topicResources = $pdo->query("SELECT * FROM topic_resources LIMIT 5")->fetchAll();
echo "<h2>Topic Resources (".count($topicResources)."):</h2>";
echo "<ul>";
foreach($topicResources as $r) {
    echo "<li>Topic {$r['topic_id']}: {$r['title']} ({$r['resource_type']})</li>";
}
echo "</ul>";

// Test 3: Check subtopic_resources
$subtopicResources = $pdo->query("SELECT * FROM subtopic_resources LIMIT 5")->fetchAll();
echo "<h2>Subtopic Resources (".count($subtopicResources)."):</h2>";
echo "<ul>";
foreach($subtopicResources as $r) {
    echo "<li>Subtopic {$r['subtopic_id']}: {$r['title']} ({$r['resource_type']})</li>";
}
echo "</ul>";

// Test 4: Direct query for a specific topic
if(!empty($topics)) {
    $testTopicId = $topics[0]['topic_id'];
    $stmt = $pdo->prepare("SELECT * FROM topic_resources WHERE topic_id = ?");
    $stmt->execute([$testTopicId]);
    $resources = $stmt->fetchAll();
    echo "<h2>Resources for Topic ID {$testTopicId}:</h2>";
    echo "<ul>";
    foreach($resources as $r) {
        echo "<li>{$r['title']} - {$r['content']}</li>";
    }
    echo "</ul>";
}
?>