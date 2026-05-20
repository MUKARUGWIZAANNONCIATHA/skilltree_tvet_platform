-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: tvet_platform
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ai_conversations`
--

DROP TABLE IF EXISTS `ai_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_conversations` (
  `conversation_id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `subtopic_id` int(11) DEFAULT NULL,
  `teacher_message` text NOT NULL,
  `ai_response` text DEFAULT NULL,
  `rating` int(11) DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`conversation_id`),
  KEY `subtopic_id` (`subtopic_id`),
  KEY `idx_topic` (`topic_id`),
  KEY `idx_teacher` (`teacher_id`),
  CONSTRAINT `ai_conversations_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `ai_conversations_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE,
  CONSTRAINT `ai_conversations_ibfk_3` FOREIGN KEY (`subtopic_id`) REFERENCES `subtopics` (`subtopic_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_conversations`
--

LOCK TABLES `ai_conversations` WRITE;
/*!40000 ALTER TABLE `ai_conversations` DISABLE KEYS */;
INSERT INTO `ai_conversations` VALUES (1,7,29,NULL,'change question number 2 make it sentence completion','Thank you for your feedback! I have learned from your comments. I will improve future question generation based on: \"change question number 2 make it sentence completion\". I will continue improving. Is there anything specific you\'d like me to change?',3,'2026-04-28 13:16:52'),(2,7,27,NULL,'focusonprimitive data type and put practical exercises','Content generated based on your feedback!',3,'2026-04-28 14:08:35'),(3,7,49,NULL,'prepare notes on node js concept','Content generated based on your feedback!',3,'2026-05-06 07:40:19'),(4,7,175,NULL,'tell more bout functionalities of blockchain','Content generated based on your feedback!',3,'2026-05-10 11:59:35'),(6,7,175,NULL,'explain Company solutions  with exples and more details in blockchain','AI content regenerated based on your feedback.',3,'2026-05-10 12:14:28');
/*!40000 ALTER TABLE `ai_conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_generated_content`
--

DROP TABLE IF EXISTS `ai_generated_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_generated_content` (
  `ai_content_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `screenshots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`screenshots`)),
  `videos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`videos`)),
  `resource_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resource_links`)),
  `exercises` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`exercises`)),
  `quiz` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`quiz`)),
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ai_content_id`),
  KEY `idx_topic` (`topic_id`),
  CONSTRAINT `ai_generated_content_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_generated_content`
--

LOCK TABLES `ai_generated_content` WRITE;
/*!40000 ALTER TABLE `ai_generated_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_generated_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_generated_notes`
--

DROP TABLE IF EXISTS `ai_generated_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_generated_notes` (
  `note_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) DEFAULT NULL,
  `subtopic_id` int(11) DEFAULT NULL,
  `generated_notes` text DEFAULT NULL,
  `suggested_videos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suggested_videos`)),
  `suggested_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suggested_links`)),
  `suggested_exercises` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suggested_exercises`)),
  `suggested_resources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suggested_resources`)),
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `source` enum('ai_generated','teacher_edited') DEFAULT 'ai_generated',
  PRIMARY KEY (`note_id`),
  KEY `topic_id` (`topic_id`),
  KEY `subtopic_id` (`subtopic_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_generated_notes`
--

LOCK TABLES `ai_generated_notes` WRITE;
/*!40000 ALTER TABLE `ai_generated_notes` DISABLE KEYS */;
INSERT INTO `ai_generated_notes` VALUES (1,26,NULL,'# Boolean logic gates\n\n## Overview\nThis is AI-generated content for **Boolean logic gates**. The system has analyzed the curriculum and prepared comprehensive learning materials.\n\n## Key Concepts\n1. First key concept explained in detail\n2. Second key concept with examples\n3. Third key concept with practical applications\n\n## Learning Objectives\nBy the end of this lesson, you will be able to:\n- Understand the fundamental concepts\n- Apply the knowledge in real scenarios\n- Analyze and evaluate different approaches\n\n## Detailed Explanation\n\n### Section 1: Introduction\nLorem ipsum dolor sit amet, consectetur adipiscing elit.\n\n### Section 2: Core Concepts\nSed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\n\n### Section 3: Examples and Applications\nUt enim ad minim veniam, quis nostrud exercitation ullamco.\n\n## Summary\n- Point 1: Key takeaway\n- Point 2: Important reminder\n- Point 3: What to remember\n\n## Next Steps\nPractice with the exercises below and review the video materials.','[{\"title\":\"Introduction to Boolean logic gates\",\"url\":\"https:\\/\\/www.youtube.com\\/embed\\/example1\",\"duration\":\"12:34\"},{\"title\":\"Deep Dive into Boolean logic gates\",\"url\":\"https:\\/\\/www.youtube.com\\/embed\\/example2\",\"duration\":\"18:22\"},{\"title\":\"Practical Applications\",\"url\":\"https:\\/\\/www.youtube.com\\/embed\\/example3\",\"duration\":\"9:45\"}]','[{\"title\":\"Official Documentation\",\"url\":\"https:\\/\\/developer.mozilla.org\\/en-US\\/\"},{\"title\":\"Tutorial and Examples\",\"url\":\"https:\\/\\/www.w3schools.com\\/\"},{\"title\":\"Community Resources\",\"url\":\"https:\\/\\/stackoverflow.com\\/\"}]','[{\"title\":\"Beginner Exercise\",\"description\":\"Practice the basic concepts with this exercise.\",\"difficulty\":\"easy\"},{\"title\":\"Intermediate Challenge\",\"description\":\"Apply your knowledge to solve this problem.\",\"difficulty\":\"medium\"},{\"title\":\"Advanced Project\",\"description\":\"Build a complete solution using what you learned.\",\"difficulty\":\"hard\"}]',NULL,'2026-04-28 08:28:19','ai_generated'),(2,27,NULL,'# Data types in JavaScript\n\n## Overview\nThis is AI-generated content for **Data types in JavaScript**.\n\n📝 **Based on your feedback:** focusonprimitive data type and put practical exercises\n\n## Key Concepts\n1. First key concept explained in detail\n2. Second key concept with examples\n3. Third key concept with practical applications\n\n## Learning Objectives\n- Understand the fundamental concepts\n- Apply the knowledge in real scenarios\n\n## Detailed Explanation\n\n### Introduction\nComprehensive explanation of the topic.\n\n### Core Concepts\nDetailed breakdown of main ideas.\n\n### Examples and Applications\nReal-world examples and use cases.\n\n## Summary\n- Key takeaway 1\n- Key takeaway 2\n- Key takeaway 3','[{\"title\":\"Introduction to Data types in JavaScript\",\"url\":\"https:\\/\\/www.youtube.com\\/embed\\/example\",\"duration\":\"12:34\"}]','[{\"title\":\"Documentation\",\"url\":\"https:\\/\\/developer.mozilla.org\\/\"},{\"title\":\"Tutorial\",\"url\":\"https:\\/\\/www.w3schools.com\\/\"}]','[{\"title\":\"Practice Exercise\",\"description\":\"Apply what you learned.\",\"difficulty\":\"medium\"}]',NULL,'2026-04-28 14:08:35','ai_generated'),(3,49,NULL,'# Node.js Key Concepts\n\n## Overview\nThis is AI-generated content for **Node.js Key Concepts**.\n\n📝 **Based on your feedback:** prepare notes on node js concept\n\n## Key Concepts\n1. First key concept explained in detail\n2. Second key concept with examples\n3. Third key concept with practical applications\n\n## Learning Objectives\n- Understand the fundamental concepts\n- Apply the knowledge in real scenarios\n\n## Detailed Explanation\n\n### Introduction\nComprehensive explanation of the topic.\n\n### Core Concepts\nDetailed breakdown of main ideas.\n\n### Examples and Applications\nReal-world examples and use cases.\n\n## Summary\n- Key takeaway 1\n- Key takeaway 2\n- Key takeaway 3','[{\"title\":\"Introduction to Node.js Key Concepts\",\"url\":\"https:\\/\\/www.youtube.com\\/embed\\/example\",\"duration\":\"12:34\"}]','[{\"title\":\"Documentation\",\"url\":\"https:\\/\\/developer.mozilla.org\\/\"},{\"title\":\"Tutorial\",\"url\":\"https:\\/\\/www.w3schools.com\\/\"}]','[{\"title\":\"Practice Exercise\",\"description\":\"Apply what you learned.\",\"difficulty\":\"medium\"}]',NULL,'2026-05-06 07:40:19','ai_generated'),(4,175,NULL,'# Introduction to blockchain\n\n## Overview\nThis is AI-generated content for **Introduction to blockchain**.\n\n📝 **Based on your feedback:** tell more bout functionalities of blockchain\n\n## Key Concepts\n1. First key concept explained in detail\n2. Second key concept with examples\n3. Third key concept with practical applications\n\n## Learning Objectives\n- Understand the fundamental concepts\n- Apply the knowledge in real scenarios\n\n## Detailed Explanation\n\n### Introduction\nComprehensive explanation of the topic.\n\n### Core Concepts\nDetailed breakdown of main ideas.\n\n### Examples and Applications\nReal-world examples and use cases.\n\n## Summary\n- Key takeaway 1\n- Key takeaway 2\n- Key takeaway 3','[{\"title\":\"Introduction to Introduction to blockchain\",\"url\":\"https:\\/\\/www.youtube.com\\/embed\\/example\",\"duration\":\"12:34\"}]','[{\"title\":\"Documentation\",\"url\":\"https:\\/\\/developer.mozilla.org\\/\"},{\"title\":\"Tutorial\",\"url\":\"https:\\/\\/www.w3schools.com\\/\"}]','[{\"title\":\"Practice Exercise\",\"description\":\"Apply what you learned.\",\"difficulty\":\"medium\"}]',NULL,'2026-05-10 11:59:35','ai_generated'),(5,NULL,382,'# Blockchain Company solutions\n\n## 📝 Incorporating your feedback:\nexplain what is company solution\n\n## Overview\nThis content is generated for *Blockchain Company solutions*. It includes key concepts, practical examples, and learning activities.\n\n### Key Concepts\n- Concept 1 explained\n- Concept 2 with examples\n- Concept 3: application\n\n### Learning Objectives\n- Understand the fundamentals\n- Apply knowledge to real tasks\n\n### Summary\n- Main takeaway 1\n- Main takeaway 2\n','[{\"title\":\"Intro to Blockchain Company solutions\",\"url\":\"https:\\/\\/www.youtube.com\\/embed\\/dQw4w9WgXcQ\",\"duration\":\"10:00\"}]','[{\"title\":\"Official Documentation\",\"url\":\"https:\\/\\/example.com\\/docs\"},{\"title\":\"Interactive Tutorial\",\"url\":\"https:\\/\\/example.com\\/tutorial\"}]','[{\"title\":\"Practical Exercise\",\"description\":\"Apply what you learned.\",\"difficulty\":\"medium\"}]',NULL,'2026-05-10 12:09:30','ai_generated'),(6,NULL,382,'# Blockchain Company solutions\n\n## 📝 Teacher feedback applied:\nexplain Company solutions  with exples and more details in blockchain\n\n## Overview\nThis content is for *Blockchain Company solutions*.\n\n### Key Concepts\n- Concept 1\n- Concept 2\n\n### Summary\n- Key takeaway\n','[{\"title\":\"Intro to Blockchain Company solutions\",\"url\":\"https:\\/\\/example.com\",\"duration\":\"5\"}]','[{\"title\":\"Documentation\",\"url\":\"https:\\/\\/example.com\\/docs\"}]','[{\"title\":\"Practice\",\"description\":\"Apply the concept\",\"difficulty\":\"easy\"}]',NULL,'2026-05-10 12:14:27','ai_generated');
/*!40000 ALTER TABLE `ai_generated_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_generated_quiz`
--

DROP TABLE IF EXISTS `ai_generated_quiz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_generated_quiz` (
  `ai_quiz_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','multiple_selection','true_false','short_answer','essay','matching') DEFAULT 'multiple_choice',
  `points` int(11) DEFAULT 5,
  `options_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options_json`)),
  `correct_answer` text DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `order_number` int(11) DEFAULT 0,
  `ai_confidence` decimal(3,2) DEFAULT 0.85,
  `teacher_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ai_quiz_id`),
  KEY `idx_topic` (`topic_id`),
  KEY `idx_approved` (`teacher_approved`),
  CONSTRAINT `ai_generated_quiz_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_generated_quiz`
--

LOCK TABLES `ai_generated_quiz` WRITE;
/*!40000 ALTER TABLE `ai_generated_quiz` DISABLE KEYS */;
INSERT INTO `ai_generated_quiz` VALUES (1,29,'What is the binary equivalent of decimal 25?','multiple_choice',5,'[\"10011\",\"11001\",\"10101\",\"11100\"]','1','Divide 25 by 2 repeatedly: 25÷2=12 R1, 12÷2=6 R0, 6÷2=3 R0, 3÷2=1 R1, 1÷2=0 R1 → Read from bottom: 11001',0,0.85,0,'2026-04-28 13:15:55'),(2,29,'Binary number 11001 is equal to which decimal number?','multiple_choice',5,'[\"23\",\"24\",\"25\",\"26\"]','2','1×16 + 1×8 + 0×4 + 0×2 + 1×1 = 16+8+0+0+1 = 25',1,0.85,0,'2026-04-28 13:15:55'),(3,29,'Convert 83 decimal to octal.','short_answer',10,NULL,'123','Divide 83 by 8: 83÷8=10 R3, 10÷8=1 R2, 1÷8=0 R1 → Read from bottom: 123',2,0.85,0,'2026-04-28 13:15:55'),(4,29,'Binary 11001 equals decimal 25. (True/False)','true_false',2,'[\"True\",\"False\"]','true','1×16 + 1×8 + 0×4 + 0×2 + 1×1 = 25, so True',3,0.85,0,'2026-04-28 13:15:55'),(5,29,'Explain the process of converting decimal to binary. Provide an example.','essay',15,NULL,'Divide the decimal number by 2 repeatedly, record remainders, then read from bottom to top.','Example: 25 → 11001',4,0.85,0,'2026-04-28 13:15:55'),(6,28,'What is the binary equivalent of decimal 25?','multiple_choice',5,'[\"10011\",\"11001\",\"10101\",\"11100\"]','1','Divide 25 by 2 repeatedly: 11001',0,0.85,0,'2026-04-28 13:41:29'),(7,28,'Which of the following is a relational database?','multiple_choice',5,'[\"MongoDB\",\"MySQL\",\"Redis\",\"Cassandra\"]','1','MySQL is a relational database',1,0.85,0,'2026-04-28 13:41:29'),(8,28,'Which of the following are valid SQL data types? (Select all that apply)','multiple_selection',10,'[\"INT\",\"VARCHAR\",\"BOOLEAN\",\"IMAGE\"]','0,1,2','INT, VARCHAR, and BOOLEAN are valid SQL data types',2,0.85,0,'2026-04-28 13:41:29'),(9,28,'Which of these are advantages of using a database? (Select all that apply)','multiple_selection',10,'[\"Data redundancy\",\"Data security\",\"Data consistency\",\"Concurrent access\"]','1,2,3','Security, consistency, and concurrent access are advantages',3,0.85,0,'2026-04-28 13:41:30'),(10,28,'A primary key can be NULL.','true_false',3,'[\"True\",\"False\"]','false','Primary keys cannot be NULL',4,0.85,0,'2026-04-28 13:41:30'),(11,28,'SQL stands for Structured Query Language.','true_false',3,'[\"True\",\"False\"]','true','SQL = Structured Query Language',5,0.85,0,'2026-04-28 13:41:30'),(12,28,'Convert 83 decimal to octal.','short_answer',10,'[]','123','83÷8=10 R3, 10÷8=1 R2, 1÷8=0 R1 → 123',6,0.85,0,'2026-04-28 13:41:30'),(13,28,'What is the difference between DELETE and TRUNCATE?','short_answer',10,'[]','DELETE removes rows one by one, TRUNCATE removes all rows at once and resets the table','DELETE is DML, TRUNCATE is DDL',7,0.85,0,'2026-04-28 13:41:30'),(14,28,'Explain the process of database normalization. Include 1NF, 2NF, and 3NF.','essay',10,'[]','1NF: No repeating groups, 2NF: No partial dependency, 3NF: No transitive dependency','Normalization reduces redundancy and improves data integrity',8,0.85,1,'2026-04-28 13:41:30'),(15,28,'Explain the process of database normalization. Include 1NF, 2NF, and 3NF.','essay',15,'[]','1NF: No repeating groups, 2NF: No partial dependency, 3NF: No transitive dependency','Normalization reduces redundancy and improves data integrity',9,0.85,0,'2026-04-28 13:41:30'),(16,25,'What is the binary equivalent of decimal 25?','multiple_choice',5,'[\"10011\",\"11001\",\"10101\",\"11100\"]','1','Divide 25 by 2 repeatedly: 11001',0,0.85,0,'2026-04-30 18:20:01'),(17,25,'Which of the following is a relational database?','multiple_choice',5,'[\"MongoDB\",\"MySQL\",\"Redis\",\"Cassandra\"]','1','MySQL is a relational database',1,0.85,0,'2026-04-30 18:20:02'),(18,25,'What does SQL stand for?','multiple_choice',5,'[\"Structured Query Language\",\"Simple Query Language\",\"Standard Query Logic\",\"Structured Question Language\"]','0','SQL = Structured Query Language',2,0.85,0,'2026-04-30 18:20:02'),(19,25,'What is the binary equivalent of decimal 25?','multiple_choice',5,'[\"10011\",\"11001\",\"10101\",\"11100\"]','1','Divide 25 by 2 repeatedly: 11001',3,0.85,0,'2026-04-30 18:20:02'),(20,25,'Which of the following are valid SQL data types? (Select all that apply)','multiple_selection',10,'[\"INT\",\"VARCHAR\",\"BOOLEAN\",\"IMAGE\"]','0,1,2','INT, VARCHAR, and BOOLEAN are valid SQL data types',4,0.85,0,'2026-04-30 18:20:02'),(21,25,'Which of these are advantages of using a database? (Select all that apply)','multiple_selection',10,'[\"Data redundancy\",\"Data security\",\"Data consistency\",\"Concurrent access\"]','1,2,3','Security, consistency, and concurrent access are advantages',5,0.85,0,'2026-04-30 18:20:02'),(22,25,'Which of the following are valid SQL data types? (Select all that apply)','multiple_selection',10,'[\"INT\",\"VARCHAR\",\"BOOLEAN\",\"IMAGE\"]','0,1,2','INT, VARCHAR, and BOOLEAN are valid SQL data types',6,0.85,0,'2026-04-30 18:20:02'),(23,25,'Which of these are advantages of using a database? (Select all that apply)','multiple_selection',10,'[\"Data redundancy\",\"Data security\",\"Data consistency\",\"Concurrent access\"]','1,2,3','Security, consistency, and concurrent access are advantages',7,0.85,0,'2026-04-30 18:20:02'),(24,25,'A primary key can be NULL.','true_false',3,'[\"True\",\"False\"]','false','Primary keys cannot be NULL',8,0.85,0,'2026-04-30 18:20:02'),(25,25,'SQL stands for Structured Query Language.','true_false',3,'[\"True\",\"False\"]','true','SQL = Structured Query Language',9,0.85,0,'2026-04-30 18:20:02'),(26,25,'A primary key can be NULL.','true_false',3,'[\"True\",\"False\"]','false','Primary keys cannot be NULL',10,0.85,0,'2026-04-30 18:20:02'),(27,25,'SQL stands for Structured Query Language.','true_false',3,'[\"True\",\"False\"]','true','SQL = Structured Query Language',11,0.85,0,'2026-04-30 18:20:02'),(28,25,'Convert 83 decimal to octal.','short_answer',10,'[]','123','83÷8=10 R3, 10÷8=1 R2, 1÷8=0 R1 → 123',12,0.85,0,'2026-04-30 18:20:02'),(29,25,'What is the difference between DELETE and TRUNCATE?','short_answer',10,'[]','DELETE removes rows one by one, TRUNCATE removes all rows at once','DELETE is DML, TRUNCATE is DDL',13,0.85,0,'2026-04-30 18:20:03'),(30,25,'Convert 83 decimal to octal.','short_answer',10,'[]','123','83÷8=10 R3, 10÷8=1 R2, 1÷8=0 R1 → 123',14,0.85,0,'2026-04-30 18:20:03');
/*!40000 ALTER TABLE `ai_generated_quiz` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_grading_log`
--

DROP TABLE IF EXISTS `ai_grading_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_grading_log` (
  `gradelog_id` int(11) NOT NULL AUTO_INCREMENT,
  `answer_id` int(11) DEFAULT NULL,
  `lo_answer_id` int(11) DEFAULT NULL,
  `summative_answer_id` int(11) DEFAULT NULL,
  `question_type` varchar(50) DEFAULT NULL,
  `student_answer` text DEFAULT NULL,
  `ai_response` text DEFAULT NULL,
  `score_given` int(11) DEFAULT NULL,
  `confidence_score` decimal(3,2) DEFAULT NULL,
  `graded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`gradelog_id`),
  KEY `idx_answer` (`answer_id`),
  KEY `idx_confidence` (`confidence_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_grading_log`
--

LOCK TABLES `ai_grading_log` WRITE;
/*!40000 ALTER TABLE `ai_grading_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_grading_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ai_quiz_feedback`
--

DROP TABLE IF EXISTS `ai_quiz_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_quiz_feedback` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `ai_quiz_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `feedback_type` enum('approved','rejected','modified','needs_improvement') DEFAULT 'needs_improvement',
  `original_text` text DEFAULT NULL,
  `modified_text` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`feedback_id`),
  KEY `idx_quiz` (`ai_quiz_id`),
  KEY `idx_teacher` (`teacher_id`),
  CONSTRAINT `ai_quiz_feedback_ibfk_1` FOREIGN KEY (`ai_quiz_id`) REFERENCES `ai_generated_quiz` (`ai_quiz_id`) ON DELETE CASCADE,
  CONSTRAINT `ai_quiz_feedback_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_quiz_feedback`
--

LOCK TABLES `ai_quiz_feedback` WRITE;
/*!40000 ALTER TABLE `ai_quiz_feedback` DISABLE KEYS */;
INSERT INTO `ai_quiz_feedback` VALUES (1,1,7,'modified',NULL,NULL,'Teacher edited question: What is the binary equivalent of decimal 25?','2026-04-28 13:18:54'),(2,4,7,'modified',NULL,'Binary 11001 equals decimal 25. (True/False)',NULL,'2026-04-28 13:29:07'),(3,14,7,'approved',NULL,NULL,NULL,'2026-04-28 13:48:13'),(4,14,7,'modified',NULL,'Explain the process of database normalization. Include 1NF, 2NF, and 3NF.',NULL,'2026-04-28 13:48:48');
/*!40000 ALTER TABLE `ai_quiz_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `anti_cheat_logs`
--

DROP TABLE IF EXISTS `anti_cheat_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `anti_cheat_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `event_type` enum('tab_switch','copy_attempt','window_blur') NOT NULL,
  `context` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `anti_cheat_logs_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `anti_cheat_logs`
--

LOCK TABLES `anti_cheat_logs` WRITE;
/*!40000 ALTER TABLE `anti_cheat_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `anti_cheat_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_methods`
--

DROP TABLE IF EXISTS `assessment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_methods` (
  `assessment_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `assessment_type` enum('Formative','Summative') NOT NULL,
  `method_name` varchar(100) NOT NULL,
  PRIMARY KEY (`assessment_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `assessment_methods_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_methods`
--

LOCK TABLES `assessment_methods` WRITE;
/*!40000 ALTER TABLE `assessment_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `assessment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_comments`
--

DROP TABLE IF EXISTS `community_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`comment_id`),
  KEY `post_id` (`post_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `community_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  CONSTRAINT `community_comments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_comments`
--

LOCK TABLES `community_comments` WRITE;
/*!40000 ALTER TABLE `community_comments` DISABLE KEYS */;
INSERT INTO `community_comments` VALUES (1,2,9,'SKILL TREE LEARNINGPLATFOM','2026-05-05 22:13:25'),(2,3,9,'oh it is a collection of data','2026-05-06 06:56:11');
/*!40000 ALTER TABLE `community_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_posts`
--

DROP TABLE IF EXISTS `community_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `category` enum('question','suggestion','resource_share') DEFAULT 'question',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`post_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_posts`
--

LOCK TABLES `community_posts` WRITE;
/*!40000 ALTER TABLE `community_posts` DISABLE KEYS */;
INSERT INTO `community_posts` VALUES (1,9,'ANSWER FOR ME','WHAT ABOUT SKILL TREE LLEARNING PLATFORRM','question','2026-05-05 22:07:57','2026-05-05 22:07:57'),(2,9,'GUESS','STLP  IN FULL','question','2026-05-05 22:09:42','2026-05-05 22:09:42'),(3,9,'help','any one to explain database','question','2026-05-06 06:55:41','2026-05-06 06:55:41');
/*!40000 ALTER TABLE `community_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `company_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'MTN Rwanda','Leading telecom and digital services',NULL,'hr@mtn.rw',NULL,NULL,'active','2026-05-05 22:50:22'),(2,'KLab','Tech hub & startup incubator',NULL,'contact@klab.rw',NULL,NULL,'active','2026-05-05 22:50:22'),(3,'KIZITO TECH SOLUTION','',NULL,'','078','https://www.kizito.edu.rw','','2026-05-06 14:20:53');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_requirements`
--

DROP TABLE IF EXISTS `company_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_requirements` (
  `requirement_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `required_level` enum('basic','intermediate','advanced') DEFAULT 'intermediate',
  PRIMARY KEY (`requirement_id`),
  UNIQUE KEY `unique_requirement` (`company_id`,`module_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `company_requirements_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `company_requirements_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_requirements`
--

LOCK TABLES `company_requirements` WRITE;
/*!40000 ALTER TABLE `company_requirements` DISABLE KEYS */;
/*!40000 ALTER TABLE `company_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_messages`
--

LOCK TABLES `contact_messages` WRITE;
/*!40000 ALTER TABLE `contact_messages` DISABLE KEYS */;
INSERT INTO `contact_messages` VALUES (1,'claudine','bugenimana@skilltree.com','i need a help','assist me about credential','2026-05-07 13:25:56',0);
/*!40000 ALTER TABLE `contact_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_questions`
--

DROP TABLE IF EXISTS `exam_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_questions` (
  `exam_q_id` int(11) NOT NULL AUTO_INCREMENT,
  `paper_id` int(11) NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `question_number` int(11) DEFAULT NULL,
  `question_text` text NOT NULL,
  `marks` int(11) DEFAULT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','essay','sql_practical','case_study') NOT NULL,
  `option_a` varchar(500) DEFAULT NULL,
  `option_b` varchar(500) DEFAULT NULL,
  `option_c` varchar(500) DEFAULT NULL,
  `option_d` varchar(500) DEFAULT NULL,
  `correct_option` char(1) DEFAULT NULL,
  `correct_boolean` tinyint(1) DEFAULT NULL,
  `model_answer` text DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `learning_outcome_id` int(11) DEFAULT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `bloom_level` enum('remember','understand','apply','analyze','evaluate','create') DEFAULT NULL,
  `ai_confidence` decimal(3,2) DEFAULT NULL,
  PRIMARY KEY (`exam_q_id`),
  KEY `topic_id` (`topic_id`),
  KEY `idx_paper` (`paper_id`),
  KEY `idx_lo` (`learning_outcome_id`),
  CONSTRAINT `exam_questions_ibfk_1` FOREIGN KEY (`paper_id`) REFERENCES `past_papers` (`paper_id`) ON DELETE CASCADE,
  CONSTRAINT `exam_questions_ibfk_2` FOREIGN KEY (`learning_outcome_id`) REFERENCES `learning_outcomes` (`outcome_id`) ON DELETE SET NULL,
  CONSTRAINT `exam_questions_ibfk_3` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_questions`
--

LOCK TABLES `exam_questions` WRITE;
/*!40000 ALTER TABLE `exam_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `exam_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_retake_tracking`
--

DROP TABLE IF EXISTS `exam_retake_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_retake_tracking` (
  `retake_id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL,
  `previous_score` decimal(5,2) DEFAULT NULL,
  `new_score` decimal(5,2) DEFAULT NULL,
  `status` enum('pending','passed','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`retake_id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `exam_retake_tracking_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  CONSTRAINT `exam_retake_tracking_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_retake_tracking`
--

LOCK TABLES `exam_retake_tracking` WRITE;
/*!40000 ALTER TABLE `exam_retake_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `exam_retake_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_submissions`
--

DROP TABLE IF EXISTS `exam_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_submissions` (
  `submission_id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `answers_json` longtext DEFAULT NULL,
  `score` decimal(5,2) DEFAULT 0.00,
  `total_marks` decimal(5,2) DEFAULT 0.00,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('pending','passed','failed') DEFAULT 'pending',
  `attempt_number` int(11) DEFAULT 1,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `teacher_feedback` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`submission_id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`),
  KEY `reviewed_by` (`reviewed_by`),
  CONSTRAINT `exam_submissions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  CONSTRAINT `exam_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `exam_submissions_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_submissions`
--

LOCK TABLES `exam_submissions` WRITE;
/*!40000 ALTER TABLE `exam_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `exam_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `exam_title` varchar(255) NOT NULL,
  `exam_description` text DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 180,
  `total_marks` int(11) DEFAULT 100,
  `passing_marks` int(11) DEFAULT 70,
  `exam_date` date DEFAULT NULL,
  `exam_data_json` longtext NOT NULL,
  `instructions` text DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`exam_id`),
  KEY `module_id` (`module_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE,
  CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
INSERT INTO `exams` VALUES (1,5,'asdf','sdfg',180,43,70,'2026-04-30','{\"title\":\"asdf\",\"description\":\"sdfg\",\"instructions\":\"asfn\\r\\nasdfgn\\r\\nasdfbn\",\"duration\":180,\"passing_marks\":70,\"total_marks\":43,\"bloom_distribution\":{\"remember\":3,\"understand\":0,\"apply\":0,\"analyze\":10,\"evaluate\":0,\"create\":30},\"section_a\":[{\"type\":\"multiple_selection\",\"text\":\"\",\"marks\":3,\"bloom\":\"remember\",\"answer\":\"\"}],\"section_b\":[{\"type\":\"short_answer\",\"text\":\"\",\"marks\":10,\"bloom\":\"analyze\",\"answer\":\"\"}],\"section_c\":[{\"type\":\"essay\",\"text\":\"\",\"marks\":15,\"bloom\":\"create\",\"answer\":\"\"},{\"type\":\"essay\",\"text\":\"\",\"marks\":15,\"bloom\":\"create\",\"answer\":\"\"}]}',NULL,'published',7,'2026-04-30 19:39:18','2026-04-30 19:39:54'),(2,9,'blockchain exam1','',180,135,70,'2026-05-10','{\"title\":\"blockchain exam1\",\"description\":\"\",\"instructions\":\"\",\"duration\":180,\"passing_marks\":70,\"total_marks\":135,\"bloom_distribution\":{\"remember\":0,\"understand\":4,\"apply\":20,\"analyze\":33,\"evaluate\":29,\"create\":49},\"section_a\":[{\"type\":\"short_answer\",\"text\":\"[create] True or False: \\\"prepare exam of blockchain vundamental is essential.\\\"\",\"marks\":3,\"bloom\":\"create\",\"answer\":\"Model answer for create level question about prepare exam of blockchain vundamental.\"},{\"type\":\"fill_table\",\"text\":\"[create] Complete: The process of prepare exam of blockchain vundamental involves __________.\",\"marks\":3,\"bloom\":\"create\",\"answer\":\"Model answer for create level question about prepare exam of blockchain vundamental.\"},{\"type\":\"fill_table\",\"text\":\"[apply] MATCHING: Match Column A with Column B about prepare exam of blockchain vundamental.\\n\\nLEFT COLUMN (Items):\\n1. Item 1\\n2. Item 2\\n3. Item 3\\n\\nRIGHT COLUMN (Descriptions):\\nA. Description A\\nB. Description B\\nC. Description C\",\"marks\":3,\"bloom\":\"apply\",\"answer\":\"Model answer for apply level question about prepare exam of blockchain vundamental.\"},{\"type\":\"matching\",\"text\":\"[evaluate] FILL THE TABLE: Complete the table about prepare exam of blockchain vundamental.\\n\\n| Concept | Definition | Example |\\n|---------|------------|---------|\\n| Concept 1 | __________ | __________ |\\n| Concept 2 | __________ | __________ |\",\"marks\":3,\"bloom\":\"evaluate\",\"answer\":\"Model answer for evaluate level question about prepare exam of blockchain vundamental.\"},{\"type\":\"multiple_choice\",\"text\":\"[create] ARRANGE STEPS: Put these steps in correct order for prepare exam of blockchain vundamental.\\n\\n__ Step A\\n__ Step B\\n__ Step C\\n__ Step D\",\"marks\":3,\"bloom\":\"create\",\"answer\":\"Model answer for create level question about prepare exam of blockchain vundamental.\"},{\"type\":\"short_answer\",\"text\":\"[evaluate] True or False: \\\"prepare exam of blockchain vundamental is essential.\\\"\",\"marks\":3,\"bloom\":\"evaluate\",\"answer\":\"Model answer for evaluate level question about prepare exam of blockchain vundamental.\"},{\"type\":\"sentence_completion\",\"text\":\"[evaluate] FILL THE TABLE: Complete the table about prepare exam of blockchain vundamental.\\n\\n| Concept | Definition | Example |\\n|---------|------------|---------|\\n| Concept 1 | __________ | __________ |\\n| Concept 2 | __________ | __________ |\",\"marks\":3,\"bloom\":\"evaluate\",\"answer\":\"Model answer for evaluate level question about prepare exam of blockchain vundamental.\"},{\"type\":\"fill_table\",\"text\":\"[analyze] ARRANGE STEPS: Put these steps in correct order for prepare exam of blockchain vundamental.\\n\\n__ Step A\\n__ Step B\\n__ Step C\\n__ Step D\",\"marks\":3,\"bloom\":\"analyze\",\"answer\":\"Model answer for analyze level question about prepare exam of blockchain vundamental.\"},{\"type\":\"true_false\",\"text\":\"[apply] Briefly apply prepare exam of blockchain vundamental in 2-3 sentences.\",\"marks\":3,\"bloom\":\"apply\",\"answer\":\"Model answer for apply level question about prepare exam of blockchain vundamental.\"},{\"type\":\"arrange_steps\",\"text\":\"[analyze] Complete: The process of prepare exam of blockchain vundamental involves __________.\",\"marks\":3,\"bloom\":\"analyze\",\"answer\":\"Model answer for analyze level question about prepare exam of blockchain vundamental.\"},{\"type\":\"sentence_completion\",\"text\":\"[apply] MATCHING: Match Column A with Column B about prepare exam of blockchain vundamental.\\n\\nLEFT COLUMN (Items):\\n1. Item 1\\n2. Item 2\\n3. Item 3\\n\\nRIGHT COLUMN (Descriptions):\\nA. Description A\\nB. Description B\\nC. Description C\",\"marks\":3,\"bloom\":\"apply\",\"answer\":\"Model answer for apply level question about prepare exam of blockchain vundamental.\"},{\"type\":\"true_false\",\"text\":\"[apply] True or False: \\\"prepare exam of blockchain vundamental is essential.\\\"\",\"marks\":3,\"bloom\":\"apply\",\"answer\":\"Model answer for apply level question about prepare exam of blockchain vundamental.\"},{\"type\":\"arrange_steps\",\"text\":\"[analyze] True or False: \\\"prepare exam of blockchain vundamental is essential.\\\"\",\"marks\":4,\"bloom\":\"analyze\",\"answer\":\"Model answer for analyze level question about prepare exam of blockchain vundamental.\"},{\"type\":\"matching\",\"text\":\"[apply] ARRANGE STEPS: Put these steps in correct order for prepare exam of blockchain vundamental.\\n\\n__ Step A\\n__ Step B\\n__ Step C\\n__ Step D\",\"marks\":4,\"bloom\":\"apply\",\"answer\":\"Model answer for apply level question about prepare exam of blockchain vundamental.\"},{\"type\":\"fill_table\",\"text\":\"[apply] FILL THE TABLE: Complete the table about prepare exam of blockchain vundamental.\\n\\n| Concept | Definition | Example |\\n|---------|------------|---------|\\n| Concept 1 | __________ | __________ |\\n| Concept 2 | __________ | __________ |\",\"marks\":4,\"bloom\":\"apply\",\"answer\":\"Model answer for apply level question about prepare exam of blockchain vundamental.\"},{\"type\":\"matching\",\"text\":\"[understand] Choose the correct answer about prepare exam of blockchain vundamental.\\nA) Option A\\nB) Option B\\nC) Option C\\nD) Option D\",\"marks\":4,\"bloom\":\"understand\",\"answer\":\"Model answer for understand level question about prepare exam of blockchain vundamental.\"},{\"type\":\"matching\",\"text\":\"[analyze] FILL THE TABLE: Complete the table about prepare exam of blockchain vundamental.\\n\\n| Concept | Definition | Example |\\n|---------|------------|---------|\\n| Concept 1 | __________ | __________ |\\n| Concept 2 | __________ | __________ |\",\"marks\":3,\"bloom\":\"analyze\",\"answer\":\"Model answer for analyze level question about prepare exam of blockchain vundamental.\"}],\"section_b\":[{\"type\":\"short_answer\",\"text\":\"[analyze] Briefly analyze prepare exam of blockchain vundamental in 2-3 sentences.\",\"marks\":10,\"bloom\":\"analyze\",\"answer\":\"Comprehensive answer for analyze level question.\"},{\"type\":\"essay\",\"text\":\"[analyze] Write an essay discussing prepare exam of blockchain vundamental. Include examples.\",\"marks\":10,\"bloom\":\"analyze\",\"answer\":\"Comprehensive answer for analyze level question.\"},{\"type\":\"case_study\",\"text\":\"[evaluate] CASE STUDY: Read and analyze this scenario about prepare exam of blockchain vundamental.\\n\\nScenario: [Provide scenario here]\\n\\nQuestions:\\n1. Identify the main issue\\n2. Analyze the causes\\n3. Recommend solutions\",\"marks\":10,\"bloom\":\"evaluate\",\"answer\":\"Comprehensive answer for evaluate level question.\"},{\"type\":\"short_answer\",\"text\":\"[evaluate] Briefly evaluate prepare exam of blockchain vundamental in 2-3 sentences.\",\"marks\":10,\"bloom\":\"evaluate\",\"answer\":\"Comprehensive answer for evaluate level question.\"},{\"type\":\"essay\",\"text\":\"[create] Write an essay discussing prepare exam of blockchain vundamental. Include examples.\",\"marks\":10,\"bloom\":\"create\",\"answer\":\"Comprehensive answer for create level question.\"}],\"section_c\":[{\"type\":\"essay\",\"text\":\"[create] Write an essay discussing prepare exam of blockchain vundamental. Include examples.\",\"marks\":15,\"bloom\":\"create\",\"answer\":\"Detailed rubric-based answer for creation level.\"},{\"type\":\"case_study\",\"text\":\"[create] CASE STUDY: Read and analyze this scenario about prepare exam of blockchain vundamental.\\n\\nScenario: [Provide scenario here]\\n\\nQuestions:\\n1. Identify the main issue\\n2. Analyze the causes\\n3. Recommend solutions\",\"marks\":15,\"bloom\":\"create\",\"answer\":\"Detailed rubric-based answer for creation level.\"}]}',NULL,'published',7,'2026-05-10 13:58:01','2026-05-10 13:58:33');
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facilitation_methods`
--

DROP TABLE IF EXISTS `facilitation_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facilitation_methods` (
  `method_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `method_name` varchar(100) NOT NULL,
  PRIMARY KEY (`method_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `facilitation_methods_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facilitation_methods`
--

LOCK TABLES `facilitation_methods` WRITE;
/*!40000 ALTER TABLE `facilitation_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `facilitation_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `final_lesson_content`
--

DROP TABLE IF EXISTS `final_lesson_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `final_lesson_content` (
  `final_content_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `final_notes` text DEFAULT NULL,
  `final_screenshots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`final_screenshots`)),
  `final_videos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`final_videos`)),
  `final_resource_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`final_resource_links`)),
  `final_exercises` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`final_exercises`)),
  `final_quiz` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`final_quiz`)),
  `published_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `version` int(11) DEFAULT 1,
  `is_published` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`final_content_id`),
  KEY `idx_topic_published` (`topic_id`,`is_published`),
  CONSTRAINT `final_lesson_content_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `final_lesson_content`
--

LOCK TABLES `final_lesson_content` WRITE;
/*!40000 ALTER TABLE `final_lesson_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `final_lesson_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indicative_contents`
--

DROP TABLE IF EXISTS `indicative_contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indicative_contents` (
  `ic_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `outcome_id` int(11) NOT NULL,
  `ic_title` varchar(255) NOT NULL,
  `ic_order` int(11) DEFAULT 0,
  PRIMARY KEY (`ic_id`),
  KEY `module_id` (`module_id`),
  KEY `outcome_id` (`outcome_id`),
  CONSTRAINT `indicative_contents_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE,
  CONSTRAINT `indicative_contents_ibfk_2` FOREIGN KEY (`outcome_id`) REFERENCES `learning_outcomes` (`outcome_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=197 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indicative_contents`
--

LOCK TABLES `indicative_contents` WRITE;
/*!40000 ALTER TABLE `indicative_contents` DISABLE KEYS */;
INSERT INTO `indicative_contents` VALUES (1,2,1,'Database fundamentals',1),(2,2,1,'Data Dictionary',2),(3,2,1,'Database Requirements',3),(4,2,2,'Database Schema',1),(5,2,2,'Logical Database Design',2),(6,2,2,'Database Optimization',3),(7,2,2,'Physical Database Schema',4),(8,2,3,'SQL Commands - DDL',1),(9,2,3,'SQL Commands - DML',2),(10,2,3,'SQL Commands - DQL',3),(11,2,3,'SQL Commands - DCL',4),(12,2,3,'SQL Commands - TCL',5),(13,2,4,'Access Control',1),(14,2,4,'Auditing and Logging',2),(15,2,4,'Data Encryption',3),(16,2,4,'Backup and Recovery',4),(17,5,5,'Conversion of number systems',1),(18,5,5,'Logic gates and expressions',2),(19,5,5,'Data types and operators',3),(20,5,5,'Algorithm development',4),(21,5,6,'Data structure concepts',1),(22,5,6,'Sorting techniques',2),(23,5,6,'Linear data structures',3),(24,5,6,'Non-linear data structures',4),(25,5,7,'JavaScript source code development',1),(26,5,7,'Run JavaScript code',2),(27,5,7,'Complexity testing',3),(28,6,8,'Setup Node.js Environment',1),(29,6,8,'Connection of Node.js to the ES5 or ES6 server',2),(30,6,8,'Establishment of database connection',3),(31,6,8,'Develop RESTFUL APIs',4),(32,6,9,'Data encryption in securing RESTFUL APIs',1),(33,6,9,'Third-Party Libraries',2),(34,6,9,'Maintaining and Updating Third-Party Libraries',3),(35,6,9,'Authentication',4),(36,6,9,'Authorization',5),(37,6,9,'Accountability',6),(38,6,9,'Environment Variables',7),(39,6,9,'Monitor Environment Variables',8),(40,6,10,'Unit testing',1),(41,6,10,'Usability testing',2),(42,6,10,'Security Testing',3),(43,6,11,'Deployment Environment',1),(44,6,11,'Manual Deployment',2),(45,6,11,'Maintenance',3),(46,6,11,'Documentation',4),(152,8,16,'Preparation of environment',1),(153,8,16,'Applying Linux basics commands',2),(154,8,16,'Management of server services',3),(155,8,17,'Preparation of deployment environment',1),(156,8,17,'Use Continuous delivery',2),(157,8,17,'Configuration of container',3),(158,8,17,'Perform migration',4),(159,8,18,'Preparation of monitoring tools in DevOps environment',1),(160,8,18,'Analysis of Performance Metrics and Feedback Data',2),(161,8,18,'Documentation of monitoring report',3),(162,9,19,'Identification of blockchain requirements',1),(163,9,19,'Selecting Blockchain Technologies',2),(164,9,19,'Designing the architecture of blockchain application',3),(165,9,20,'Preparation of environment',1),(166,9,20,'Applying solidity concepts',2),(167,9,20,'Implementing function Interaction',3),(168,9,20,'Optimizing Gas Costs',4),(169,9,21,'Creating smart contracts',1),(170,9,21,'Creating Tokens',2),(171,9,21,'Applying security of smart contracts',3),(172,9,21,'Deploying smart contracts',4),(173,9,22,'Installing web3 dependencies',1),(174,9,22,'Connecting smart contract',2),(175,9,22,'Use of function',3),(176,10,23,'Preparation of React js environment',1),(177,10,23,'Applying React basics',2),(178,10,23,'Applying UI navigation',3),(179,10,23,'Applying React hooks',4),(180,10,23,'Implementation of Events handling',5),(181,10,23,'Implementation of API integration',6),(182,10,24,'Applying Tailwind utility classes',1),(183,10,24,'Applying responsive design principles',2),(184,10,24,'Customization of tailwind styles',3),(185,10,25,'Applying TypeScript basics',1),(186,10,25,'Setup NextJS project',2),(187,10,25,'Implementing Rendering Techniques',3),(188,10,25,'Implementing routing',4),(189,10,25,'Creation of API',5),(190,10,25,'Securing the Application',6),(191,10,26,'Maintain Responsiveness',1),(192,10,26,'Configuring web application manifest',2),(193,10,26,'Implementation of service workers',3),(194,10,27,'Configuration of environment variables',1),(195,10,27,'Deploying React Application',2),(196,10,27,'Setup custom Domain',3);
/*!40000 ALTER TABLE `indicative_contents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internship_applications`
--

DROP TABLE IF EXISTS `internship_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `internship_applications` (
  `application_id` int(11) NOT NULL AUTO_INCREMENT,
  `internship_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  PRIMARY KEY (`application_id`),
  KEY `internship_id` (`internship_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `internship_applications_ibfk_1` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`internship_id`) ON DELETE CASCADE,
  CONSTRAINT `internship_applications_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internship_applications`
--

LOCK TABLES `internship_applications` WRITE;
/*!40000 ALTER TABLE `internship_applications` DISABLE KEYS */;
INSERT INTO `internship_applications` VALUES (1,4,9,'i am a learner in software development at saint kizito save tss.i prefer your company can you please accept my request','accepted','2026-05-07 12:25:12','2026-05-07 16:11:07','you are allowed to come and start your internership');
/*!40000 ALTER TABLE `internship_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internships`
--

DROP TABLE IF EXISTS `internships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `internships` (
  `internship_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `duration_months` int(11) DEFAULT NULL,
  `required_modules` longtext DEFAULT NULL,
  `application_deadline` date DEFAULT NULL,
  `is_open` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('open','closed') DEFAULT 'open',
  PRIMARY KEY (`internship_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_is_open` (`is_open`),
  KEY `idx_deadline` (`application_deadline`),
  CONSTRAINT `fk_internship_user` FOREIGN KEY (`company_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internships`
--

LOCK TABLES `internships` WRITE;
/*!40000 ALTER TABLE `internships` DISABLE KEYS */;
INSERT INTO `internships` VALUES (4,17,'dsfghjk','sdffghjkl;','sdfghjk',3,'sdfghjk','2026-05-22',1,'2026-05-07 08:12:43','open');
/*!40000 ALTER TABLE `internships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `learning_outcomes`
--

DROP TABLE IF EXISTS `learning_outcomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learning_outcomes` (
  `outcome_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `outcome_number` int(11) NOT NULL,
  `outcome_description` text DEFAULT NULL,
  `description` text NOT NULL,
  `learning_hours` int(11) DEFAULT 0,
  `order_position` int(11) DEFAULT 0,
  PRIMARY KEY (`outcome_id`),
  UNIQUE KEY `unique_outcome` (`module_id`,`outcome_number`),
  CONSTRAINT `learning_outcomes_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `learning_outcomes`
--

LOCK TABLES `learning_outcomes` WRITE;
/*!40000 ALTER TABLE `learning_outcomes` DISABLE KEYS */;
INSERT INTO `learning_outcomes` VALUES (1,2,1,NULL,'Analyse Database',20,1),(2,2,2,NULL,'Design Database',25,2),(3,2,3,NULL,'Implement Database',36,3),(4,2,4,NULL,'Secure Database',19,4),(5,5,1,NULL,'Apply Algorithm Fundamentals',30,1),(6,5,2,NULL,'Apply Data Structure',45,2),(7,5,3,NULL,'Implement Algorithm using JavaScript',55,3),(8,6,1,NULL,'Develop RESTFUL APIs with Node JS',45,1),(9,6,2,NULL,'Secure Backend Application',20,2),(10,6,3,NULL,'Test Backend Application',20,3),(11,6,4,NULL,'Manage Backend Application',15,4),(16,8,1,NULL,'Perform server configuration',0,0),(17,8,2,NULL,'Deploy the system',0,0),(18,8,3,NULL,'Implement monitoring strategies',0,0),(19,9,1,NULL,'Design blockchain system architecture',0,0),(20,9,2,NULL,'Apply Solidity Basics',0,0),(21,9,3,NULL,'Develop Smart contracts system',0,0),(22,9,4,NULL,'Apply frontend Integration',0,0),(23,10,1,NULL,'Develop React.js application',0,0),(24,10,2,NULL,'Apply Tailwind CSS framework',0,0),(25,10,3,NULL,'Develop NextJS Application',0,0),(26,10,4,NULL,'Apply Progressive Web Application',0,0),(27,10,5,NULL,'Publish the application',0,0);
/*!40000 ALTER TABLE `learning_outcomes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `levels`
--

DROP TABLE IF EXISTS `levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `levels` (
  `level_id` int(11) NOT NULL AUTO_INCREMENT,
  `level_number` int(11) NOT NULL,
  `level_name` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`level_id`),
  UNIQUE KEY `level_number` (`level_number`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `levels`
--

LOCK TABLES `levels` WRITE;
/*!40000 ALTER TABLE `levels` DISABLE KEYS */;
INSERT INTO `levels` VALUES (1,3,'Certificate III','Basic skills and knowledge','2026-04-25 12:41:09'),(2,4,'Certificate IV','Intermediate skills and knowledge','2026-04-25 12:41:09'),(3,5,'Diploma','Advanced skills and knowledge','2026-04-25 12:41:09'),(4,6,'Advanced Diploma','Specialized skills and knowledge','2026-04-25 12:41:09');
/*!40000 ALTER TABLE `levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_resources`
--

DROP TABLE IF EXISTS `library_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_resources` (
  `library_id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_type` enum('past_paper','marking_guide','review_bank','reference') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `trade_id` int(11) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `trade_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`library_id`),
  KEY `trade_id` (`trade_id`),
  KEY `module_id` (`module_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `library_resources_ibfk_1` FOREIGN KEY (`trade_id`) REFERENCES `trades` (`trade_id`) ON DELETE SET NULL,
  CONSTRAINT `library_resources_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE SET NULL,
  CONSTRAINT `library_resources_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_resources`
--

LOCK TABLES `library_resources` WRITE;
/*!40000 ALTER TABLE `library_resources` DISABLE KEYS */;
INSERT INTO `library_resources` VALUES (1,'past_paper','Sample Past Paper','A sample past paper for practice.','/uploads/library/sample.pdf',1,NULL,NULL,1,'2026-05-10 20:28:38',NULL),(2,'reference','Software Development Guide','Comprehensive reference book.','/uploads/library/guide.pdf',NULL,NULL,NULL,1,'2026-05-10 20:28:38',NULL),(3,'marking_guide','exam of blockchain_marking_guides_term1','questions and answers','../uploads/library/1778445519_FUNDAMENTAL_OF_BLOCKCHAIN_APPLICATION.docx',1,9,NULL,7,'2026-05-10 20:38:40',NULL);
/*!40000 ALTER TABLE `library_resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lo_assessment_questions`
--

DROP TABLE IF EXISTS `lo_assessment_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lo_assessment_questions` (
  `lo_question_id` int(11) NOT NULL AUTO_INCREMENT,
  `lo_assessment_id` int(11) NOT NULL,
  `question_type` enum('sentence_completion','true_false','multiple_choice','multiple_answer','essay','short_answer','practical') NOT NULL,
  `question_text` text NOT NULL,
  `points` int(11) DEFAULT 1,
  `options_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options_json`)),
  `rubric_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rubric_json`)),
  `order_number` int(11) DEFAULT NULL,
  PRIMARY KEY (`lo_question_id`),
  KEY `idx_lo_assessment` (`lo_assessment_id`),
  CONSTRAINT `lo_assessment_questions_ibfk_1` FOREIGN KEY (`lo_assessment_id`) REFERENCES `lo_assessments` (`lo_assessment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lo_assessment_questions`
--

LOCK TABLES `lo_assessment_questions` WRITE;
/*!40000 ALTER TABLE `lo_assessment_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `lo_assessment_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lo_assessment_submissions`
--

DROP TABLE IF EXISTS `lo_assessment_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lo_assessment_submissions` (
  `submission_id` int(11) NOT NULL AUTO_INCREMENT,
  `lo_assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `answers_json` longtext DEFAULT NULL,
  `score` decimal(5,2) DEFAULT 0.00,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('passed','failed','pending') DEFAULT 'pending',
  `attempt_number` int(11) DEFAULT 1,
  `violations` int(11) DEFAULT 0,
  `started_at` datetime DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`submission_id`),
  KEY `lo_assessment_id` (`lo_assessment_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `lo_assessment_submissions_ibfk_1` FOREIGN KEY (`lo_assessment_id`) REFERENCES `lo_assessments` (`lo_assessment_id`) ON DELETE CASCADE,
  CONSTRAINT `lo_assessment_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lo_assessment_submissions`
--

LOCK TABLES `lo_assessment_submissions` WRITE;
/*!40000 ALTER TABLE `lo_assessment_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `lo_assessment_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lo_assessments`
--

DROP TABLE IF EXISTS `lo_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lo_assessments` (
  `lo_assessment_id` int(11) NOT NULL AUTO_INCREMENT,
  `outcome_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assessment_data_json` longtext DEFAULT NULL,
  `passing_score` int(11) DEFAULT 70,
  `time_limit_minutes` int(11) DEFAULT 90,
  `created_by_ai` tinyint(1) DEFAULT 1,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`lo_assessment_id`),
  KEY `idx_outcome` (`outcome_id`),
  CONSTRAINT `lo_assessments_ibfk_1` FOREIGN KEY (`outcome_id`) REFERENCES `learning_outcomes` (`outcome_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lo_assessments`
--

LOCK TABLES `lo_assessments` WRITE;
/*!40000 ALTER TABLE `lo_assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `lo_assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marking_guides`
--

DROP TABLE IF EXISTS `marking_guides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `marking_guides` (
  `guide_id` int(11) NOT NULL AUTO_INCREMENT,
  `paper_id` int(11) NOT NULL,
  `exam_q_id` int(11) NOT NULL,
  `correct_answer` text DEFAULT NULL,
  `marking_scheme` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`marking_scheme`)),
  `total_marks_allocated` int(11) DEFAULT NULL,
  `common_mistakes` text DEFAULT NULL,
  `teacher_notes` text DEFAULT NULL,
  `ai_confidence` decimal(3,2) DEFAULT NULL,
  `reviewed_by_teacher` tinyint(1) DEFAULT 0,
  `approved_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`guide_id`),
  KEY `paper_id` (`paper_id`),
  KEY `idx_exam_q` (`exam_q_id`),
  CONSTRAINT `marking_guides_ibfk_1` FOREIGN KEY (`paper_id`) REFERENCES `past_papers` (`paper_id`) ON DELETE CASCADE,
  CONSTRAINT `marking_guides_ibfk_2` FOREIGN KEY (`exam_q_id`) REFERENCES `exam_questions` (`exam_q_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marking_guides`
--

LOCK TABLES `marking_guides` WRITE;
/*!40000 ALTER TABLE `marking_guides` DISABLE KEYS */;
/*!40000 ALTER TABLE `marking_guides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `module_resources`
--

DROP TABLE IF EXISTS `module_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_resources` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `resource_type` enum('Equipment','Materials','Tools') NOT NULL,
  `resource_name` varchar(255) NOT NULL,
  `resource_url` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`resource_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `module_resources_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `module_resources`
--

LOCK TABLES `module_resources` WRITE;
/*!40000 ALTER TABLE `module_resources` DISABLE KEYS */;
/*!40000 ALTER TABLE `module_resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modules`
--

DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modules` (
  `module_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_code` varchar(20) NOT NULL,
  `module_name` varchar(200) NOT NULL,
  `credits` int(11) DEFAULT 0,
  `rqf_level` int(11) DEFAULT 4,
  `total_learning_hours` int(11) DEFAULT 0,
  `sector` varchar(100) DEFAULT NULL,
  `trade` varchar(100) DEFAULT NULL,
  `module_type` enum('specific','general','complementary') DEFAULT 'specific',
  `description` text DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`module_id`),
  UNIQUE KEY `module_code` (`module_code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modules`
--

LOCK TABLES `modules` WRITE;
/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES (2,'SWDDS401','DATABASE DEVELOPMENT',6,5,60,'ICT and Multimedia','Software Development','specific',NULL,'published',2,'2026-04-27 10:42:56'),(5,'SWDDSS401','DATA STRUCTURE AND ALGORITHM',13,4,130,'','Software Development','specific',NULL,'published',7,'2026-04-27 17:28:32'),(6,'SWDBD401','BACKEND APPLICATION',10,4,100,'ICT AND MULTMEDIA','Software Development','specific',NULL,'published',7,'2026-04-27 18:00:15'),(8,'SWDOT501','DevOps APPLICATION',6,5,60,'ICT and Multimedia','Software Development','specific',NULL,'published',NULL,'2026-05-08 19:47:55'),(9,'SWDBF501','BLOCKCHAI FUNDAMENTALS',10,5,100,'ICT and Multimedia','Software Development','specific',NULL,'published',NULL,'2026-05-10 07:04:48'),(10,'SWDFA501','Develop Frontend Application using React.JS',11,5,110,'ICT and Multimedia','Software Development','specific',NULL,'published',NULL,'2026-05-10 07:53:23');
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `past_papers`
--

DROP TABLE IF EXISTS `past_papers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `past_papers` (
  `paper_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `exam_year` int(11) NOT NULL,
  `exam_type` enum('national','mock','mid_term','end_term') DEFAULT 'national',
  `exam_level` varchar(50) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `total_marks` int(11) DEFAULT NULL,
  `passing_score` int(11) DEFAULT 70,
  `file_path` varchar(500) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('uploaded','processing','ready','published') DEFAULT 'uploaded',
  PRIMARY KEY (`paper_id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_module_year` (`module_id`,`exam_year`),
  KEY `idx_status` (`status`),
  CONSTRAINT `past_papers_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE,
  CONSTRAINT `past_papers_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `past_papers`
--

LOCK TABLES `past_papers` WRITE;
/*!40000 ALTER TABLE `past_papers` DISABLE KEYS */;
/*!40000 ALTER TABLE `past_papers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_questions`
--

DROP TABLE IF EXISTS `quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_questions` (
  `question_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer','essay') DEFAULT 'multiple_choice',
  `question_text` text NOT NULL,
  `points` int(11) DEFAULT 5,
  `order_number` int(11) DEFAULT 0,
  `options_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options_json`)),
  `correct_answer` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`question_id`),
  KEY `idx_quiz_topic` (`topic_id`),
  CONSTRAINT `quiz_questions_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_questions`
--

LOCK TABLES `quiz_questions` WRITE;
/*!40000 ALTER TABLE `quiz_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_bank`
--

DROP TABLE IF EXISTS `review_bank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `review_bank` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `outcome_id` int(11) DEFAULT NULL,
  `subtopic_id` int(11) DEFAULT NULL,
  `ic_id` int(11) DEFAULT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `bloom_level` enum('remember','understand','apply','analyze','evaluate','create') NOT NULL,
  `question_type` enum('multiple_choice','true_false','sentence_completion','matching','short_answer','essay') NOT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(500) DEFAULT NULL,
  `option_b` varchar(500) DEFAULT NULL,
  `option_c` varchar(500) DEFAULT NULL,
  `option_d` varchar(500) DEFAULT NULL,
  `correct_option` char(1) DEFAULT NULL,
  `correct_boolean` tinyint(1) DEFAULT NULL,
  `sentence_before` text DEFAULT NULL,
  `sentence_after` text DEFAULT NULL,
  `correct_word` varchar(255) DEFAULT NULL,
  `matching_pairs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`matching_pairs`)),
  `model_answer` text DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `marks` int(11) DEFAULT 5,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `tags` varchar(255) DEFAULT NULL,
  `has_diagram` tinyint(1) DEFAULT 0,
  `diagram_url` varchar(500) DEFAULT NULL,
  `created_by_ai` tinyint(1) DEFAULT 0,
  `reviewed_by_teacher` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `ai_enhanced` tinyint(4) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `complexity` enum('basic','intermediate','advanced') DEFAULT 'intermediate',
  PRIMARY KEY (`review_id`),
  KEY `outcome_id` (`outcome_id`),
  KEY `idx_module_bloom` (`module_id`,`bloom_level`),
  KEY `idx_topic` (`topic_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `review_bank_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE,
  CONSTRAINT `review_bank_ibfk_2` FOREIGN KEY (`outcome_id`) REFERENCES `learning_outcomes` (`outcome_id`) ON DELETE SET NULL,
  CONSTRAINT `review_bank_ibfk_3` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE SET NULL,
  CONSTRAINT `review_bank_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_bank`
--

LOCK TABLES `review_bank` WRITE;
/*!40000 ALTER TABLE `review_bank` DISABLE KEYS */;
INSERT INTO `review_bank` VALUES (27,5,NULL,NULL,NULL,33,'understand','multiple_choice','[AI Generated] Understand level question: matching',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level question based on: matching','This assesses understand level of Bloom\'s Taxonomy.',5,'medium',NULL,0,NULL,0,0,'2026-04-29 07:30:41',7,0,'approved',7,'2026-04-29 09:32:14','intermediate'),(28,5,NULL,NULL,NULL,30,'apply','matching','dfghjk.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ssdvfbgnhjkl','',5,'medium',NULL,0,NULL,0,0,'2026-04-29 07:34:32',7,0,'approved',7,'2026-04-29 09:34:51','intermediate'),(29,5,NULL,NULL,NULL,34,'understand','','[AI Generated] Understand level question: creating question stared with essay',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level question based on: creating question stared with essay','This assesses understand level of Bloom\'s Taxonomy.',10,'medium',NULL,0,NULL,0,0,'2026-04-29 07:39:31',7,0,'approved',7,'2026-04-29 09:40:01','intermediate'),(31,5,NULL,NULL,NULL,25,'understand','','[UNDERSTAND] fill table question about understand level concepts.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level.','This assesses understand level.',3,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:06',7,1,'approved',7,'2026-04-29 11:19:04','intermediate'),(32,5,NULL,NULL,NULL,25,'understand','sentence_completion','[UNDERSTAND] sentence completion question about understand level concepts.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level.','This assesses understand level.',3,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:06',7,1,'approved',7,'2026-04-29 11:19:09','intermediate'),(35,5,NULL,NULL,NULL,25,'apply','','[APPLY] arrange steps question about apply level concepts.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for apply level.','This assesses apply level.',3,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:06',7,1,'approved',7,'2026-04-29 11:19:29','intermediate'),(36,5,NULL,NULL,NULL,25,'apply','multiple_choice','[APPLY] multiple choice question about apply level concepts.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for apply level.','This assesses apply level.',3,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:06',7,1,'approved',7,'2026-04-29 11:19:42','intermediate'),(40,5,NULL,NULL,NULL,25,'analyze','','[ANALYZE] fill table question about analyze level concepts.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for analyze level.','This assesses analyze level.',3,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:17:38','intermediate'),(41,5,NULL,NULL,NULL,25,'analyze','','[ANALYZE] multiple selection question about analyze level concepts.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for analyze level.','This assesses analyze level.',3,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:17:44','intermediate'),(42,5,NULL,NULL,NULL,25,'evaluate','','[EVALUATE] arrange steps question about evaluate level concepts.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level.','This assesses evaluate level.',4,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:18:03','intermediate'),(43,5,NULL,NULL,NULL,25,'evaluate','matching','[EVALUATE] matching question about evaluate level concepts.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level.','This assesses evaluate level.',4,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:18:13','intermediate'),(44,5,NULL,NULL,NULL,25,'evaluate','','[EVALUATE] fill table question about evaluate level concepts.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level.','This assesses evaluate level.',4,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:16:17','intermediate'),(45,5,NULL,NULL,NULL,25,'create','sentence_completion','[CREATE] sentence completion question about create level concepts.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level.','This assesses create level.',4,'hard',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:18:23','intermediate'),(46,5,NULL,NULL,NULL,25,'create','true_false','[CREATE] true false question about create level concepts.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level.','This assesses create level.',3,'hard',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:18:27','intermediate'),(47,5,NULL,NULL,NULL,25,'analyze','short_answer','[ANALYZE] (10 marks) short answer - Analyze and discuss.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Comprehensive answer.','Assesses analyze level.',10,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:18:09','intermediate'),(48,5,NULL,NULL,NULL,25,'analyze','short_answer','[ANALYZE] (10 marks) short answer - Analyze and discuss.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Comprehensive answer.','Assesses analyze level.',10,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:18:31','intermediate'),(49,5,NULL,NULL,NULL,25,'evaluate','essay','[EVALUATE] (10 marks) essay - Analyze and discuss.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Comprehensive answer.','Assesses evaluate level.',10,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:18:37','intermediate'),(50,5,NULL,NULL,NULL,25,'evaluate','essay','[EVALUATE] (10 marks) essay - Analyze and discuss.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Comprehensive answer.','Assesses evaluate level.',10,'medium',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:18:40','intermediate'),(51,5,NULL,NULL,NULL,25,'create','','[CREATE] (10 marks) case study - Analyze and discuss.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Comprehensive answer.','Assesses create level.',10,'hard',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:18:59','intermediate'),(52,5,NULL,NULL,NULL,25,'create','essay','[CREATE] (15 marks) essay - Design a comprehensive solution.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Detailed rubric-based answer.','Assesses creation level.',15,'hard',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:18:17','intermediate'),(53,5,NULL,NULL,NULL,25,'create','','[CREATE] (15 marks) case_study - Design a comprehensive solution.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Detailed rubric-based answer.','Assesses creation level.',15,'hard',NULL,0,NULL,0,0,'2026-04-29 08:11:07',7,1,'approved',7,'2026-04-29 11:18:51','intermediate'),(54,5,NULL,NULL,NULL,26,'create','essay','cvbnm',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'vbnm','bnm',5,'hard',NULL,0,NULL,0,0,'2026-04-29 08:12:46',7,0,'approved',7,'2026-04-29 11:16:06','intermediate'),(55,5,NULL,NULL,NULL,35,'apply','true_false','wer',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'sdfgh',' ,./',5,'medium',NULL,0,NULL,0,0,'2026-04-29 08:55:53',7,0,'approved',NULL,NULL,'intermediate'),(56,5,NULL,NULL,NULL,31,'analyze','sentence_completion','what is-----------------name',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'your','your indicate the adjectives',5,'medium',NULL,0,NULL,0,0,'2026-04-29 09:36:20',7,0,'approved',NULL,NULL,'advanced'),(57,5,NULL,NULL,NULL,37,'evaluate','','[EVALUATE] multiple selection question about LO2: Linked Lists.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level question.','Assesses evaluate level.',5,'hard',NULL,0,NULL,0,0,'2026-04-29 10:25:47',7,1,'approved',NULL,NULL,'intermediate'),(58,5,NULL,NULL,NULL,37,'evaluate','','[EVALUATE] fill table question about LO2: Linked Lists.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level question.','Assesses evaluate level.',5,'hard',NULL,0,NULL,0,0,'2026-04-29 10:25:47',7,1,'approved',NULL,NULL,'intermediate'),(59,5,NULL,NULL,NULL,37,'evaluate','','[EVALUATE] multiple selection question about LO2: Linked Lists.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level question.','Assesses evaluate level.',5,'hard',NULL,0,NULL,0,0,'2026-04-29 10:25:47',7,1,'approved',NULL,NULL,'intermediate'),(60,5,NULL,NULL,NULL,37,'evaluate','','[EVALUATE] fill table question about LO2: Linked Lists.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level question.','Assesses evaluate level.',5,'hard',NULL,0,NULL,0,0,'2026-04-29 10:25:47',7,1,'approved',NULL,NULL,'intermediate'),(61,5,NULL,NULL,NULL,37,'evaluate','','[EVALUATE] multiple selection question about LO2: Linked Lists.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level question.','Assesses evaluate level.',5,'hard',NULL,0,NULL,0,0,'2026-04-29 10:25:47',7,1,'approved',NULL,NULL,'intermediate'),(62,5,NULL,NULL,NULL,37,'evaluate','','[EVALUATE] fill table question about LO2: Linked Lists.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level question.','Assesses evaluate level.',5,'hard',NULL,0,NULL,0,0,'2026-04-29 10:25:47',7,1,'approved',NULL,NULL,'intermediate'),(63,5,NULL,NULL,NULL,37,'evaluate','','[EVALUATE] multiple selection question about LO2: Linked Lists.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level question.','Assesses evaluate level.',5,'hard',NULL,0,NULL,0,0,'2026-04-29 10:25:48',7,1,'approved',NULL,NULL,'intermediate'),(64,5,NULL,NULL,NULL,37,'evaluate','','[EVALUATE] fill table question about LO2: Linked Lists.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level question.','Assesses evaluate level.',5,'hard',NULL,0,NULL,0,0,'2026-04-29 10:25:48',7,1,'approved',NULL,NULL,'intermediate'),(65,5,NULL,NULL,NULL,37,'evaluate','','[EVALUATE] multiple selection question about LO2: Linked Lists.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level question.','Assesses evaluate level.',5,'hard',NULL,0,NULL,0,0,'2026-04-29 10:25:48',7,1,'approved',NULL,NULL,'intermediate'),(66,5,NULL,NULL,NULL,37,'evaluate','','[EVALUATE] fill table question about LO2: Linked Lists.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for evaluate level question.','Assesses evaluate level.',5,'hard',NULL,0,NULL,0,0,'2026-04-29 10:25:48',7,1,'approved',NULL,NULL,'intermediate'),(67,5,NULL,NULL,NULL,35,'create','essay','[CREATE] essay question about LO2: Arrays.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'medium',NULL,0,NULL,0,0,'2026-04-29 10:28:09',7,1,'approved',NULL,NULL,'intermediate'),(68,5,NULL,NULL,NULL,35,'create','essay','[CREATE] essay question about LO2: Arrays.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'medium',NULL,0,NULL,0,0,'2026-04-29 10:28:09',7,1,'approved',NULL,NULL,'intermediate'),(69,5,NULL,NULL,NULL,35,'create','essay','[CREATE] essay question about LO2: Arrays.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'medium',NULL,0,NULL,0,0,'2026-04-29 10:28:09',7,1,'approved',NULL,NULL,'intermediate'),(70,5,NULL,NULL,NULL,35,'create','essay','[CREATE] essay question about LO2: Arrays.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'medium',NULL,0,NULL,0,0,'2026-04-29 10:28:09',7,1,'approved',NULL,NULL,'intermediate'),(71,5,NULL,NULL,NULL,35,'create','essay','[CREATE] essay question about LO2: Arrays.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'medium',NULL,0,NULL,0,0,'2026-04-29 10:28:09',7,1,'approved',NULL,NULL,'intermediate'),(72,5,NULL,NULL,NULL,35,'create','essay','[CREATE] essay question about LO2: Arrays.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'medium',NULL,0,NULL,0,0,'2026-04-29 10:28:10',7,1,'approved',NULL,NULL,'intermediate'),(73,5,NULL,NULL,NULL,35,'create','essay','[CREATE] essay question about LO2: Arrays.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'medium',NULL,0,NULL,0,0,'2026-04-29 10:28:10',7,1,'approved',NULL,NULL,'intermediate'),(74,5,NULL,NULL,NULL,35,'create','essay','[CREATE] essay question about LO2: Arrays.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'medium',NULL,0,NULL,0,0,'2026-04-29 10:28:10',7,1,'approved',NULL,NULL,'intermediate'),(75,5,NULL,NULL,NULL,35,'create','essay','[CREATE] essay question about LO2: Arrays.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'medium',NULL,0,NULL,0,0,'2026-04-29 10:28:10',7,1,'approved',NULL,NULL,'intermediate'),(76,5,NULL,NULL,NULL,35,'create','essay','[CREATE] essay question about LO2: Arrays.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'medium',NULL,0,NULL,0,0,'2026-04-29 10:28:10',7,1,'approved',NULL,NULL,'intermediate'),(77,5,NULL,NULL,NULL,34,'create','multiple_choice','a.dfgh\r\nbjkl;\r\nc.bn',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'b','sdfghjkl;',5,'hard',NULL,0,NULL,0,0,'2026-04-29 10:30:14',7,0,'approved',NULL,NULL,'advanced'),(79,9,NULL,NULL,NULL,175,'create','multiple_choice','[CREATE] multiple choice question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'easy',NULL,0,NULL,0,0,'2026-05-10 13:00:32',7,1,'approved',NULL,NULL,'advanced'),(80,9,NULL,NULL,NULL,175,'create','true_false','[CREATE] true false question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'easy',NULL,0,NULL,0,0,'2026-05-10 13:00:32',7,1,'approved',NULL,NULL,'advanced'),(81,9,NULL,NULL,NULL,175,'create','multiple_choice','[CREATE] multiple choice question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'easy',NULL,0,NULL,0,0,'2026-05-10 13:00:32',7,1,'approved',NULL,NULL,'advanced'),(82,9,NULL,NULL,NULL,175,'create','true_false','[CREATE] true false question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'easy',NULL,0,NULL,0,0,'2026-05-10 13:00:32',7,1,'approved',NULL,NULL,'advanced'),(83,9,NULL,NULL,NULL,175,'create','multiple_choice','[CREATE] multiple choice question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'easy',NULL,0,NULL,0,0,'2026-05-10 13:00:32',7,1,'approved',NULL,NULL,'advanced'),(84,9,NULL,NULL,NULL,175,'create','true_false','[CREATE] true false question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'easy',NULL,0,NULL,0,0,'2026-05-10 13:00:32',7,1,'approved',NULL,NULL,'advanced'),(85,9,NULL,NULL,NULL,175,'create','multiple_choice','[CREATE] multiple choice question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'easy',NULL,0,NULL,0,0,'2026-05-10 13:00:32',7,1,'approved',NULL,NULL,'advanced'),(86,9,NULL,NULL,NULL,175,'create','true_false','[CREATE] true false question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'easy',NULL,0,NULL,0,0,'2026-05-10 13:00:33',7,1,'approved',NULL,NULL,'advanced'),(87,9,NULL,NULL,NULL,175,'create','multiple_choice','[CREATE] multiple choice question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'easy',NULL,0,NULL,0,0,'2026-05-10 13:00:33',7,1,'approved',NULL,NULL,'advanced'),(88,9,NULL,NULL,NULL,175,'create','true_false','[CREATE] true false question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for create level question.','Assesses create level.',5,'easy',NULL,0,NULL,0,0,'2026-05-10 13:00:33',7,1,'approved',NULL,NULL,'advanced'),(89,9,NULL,NULL,NULL,175,'understand','multiple_choice','[UNDERSTAND] multiple choice question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level question.','Assesses understand level.',5,'medium',NULL,0,NULL,0,0,'2026-05-10 13:01:27',7,1,'approved',NULL,NULL,'intermediate'),(90,9,NULL,NULL,NULL,175,'understand','true_false','[UNDERSTAND] true false question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level question.','Assesses understand level.',5,'medium',NULL,0,NULL,0,0,'2026-05-10 13:01:27',7,1,'approved',NULL,NULL,'intermediate'),(91,9,NULL,NULL,NULL,175,'understand','multiple_choice','[UNDERSTAND] multiple choice question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level question.','Assesses understand level.',5,'medium',NULL,0,NULL,0,0,'2026-05-10 13:01:27',7,1,'approved',NULL,NULL,'intermediate'),(92,9,NULL,NULL,NULL,175,'understand','true_false','[UNDERSTAND] true false question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level question.','Assesses understand level.',5,'medium',NULL,0,NULL,0,0,'2026-05-10 13:01:28',7,1,'approved',NULL,NULL,'intermediate'),(93,9,NULL,NULL,NULL,175,'understand','multiple_choice','[UNDERSTAND] multiple choice question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level question.','Assesses understand level.',5,'medium',NULL,0,NULL,0,0,'2026-05-10 13:01:28',7,1,'approved',NULL,NULL,'intermediate'),(94,9,NULL,NULL,NULL,175,'understand','true_false','[UNDERSTAND] true false question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level question.','Assesses understand level.',5,'medium',NULL,0,NULL,0,0,'2026-05-10 13:01:28',7,1,'approved',NULL,NULL,'intermediate'),(95,9,NULL,NULL,NULL,175,'understand','multiple_choice','[UNDERSTAND] multiple choice question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level question.','Assesses understand level.',5,'medium',NULL,0,NULL,0,0,'2026-05-10 13:01:28',7,1,'approved',NULL,NULL,'intermediate'),(96,9,NULL,NULL,NULL,175,'understand','true_false','[UNDERSTAND] true false question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level question.','Assesses understand level.',5,'medium',NULL,0,NULL,0,0,'2026-05-10 13:01:28',7,1,'approved',NULL,NULL,'intermediate'),(97,9,NULL,NULL,NULL,175,'understand','multiple_choice','[UNDERSTAND] multiple choice question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level question.','Assesses understand level.',5,'medium',NULL,0,NULL,0,0,'2026-05-10 13:01:28',7,1,'approved',NULL,NULL,'intermediate'),(98,9,NULL,NULL,NULL,175,'understand','true_false','[UNDERSTAND] true false question about LO1: Introduction to blockchain.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Model answer for understand level question.','Assesses understand level.',5,'medium',NULL,0,NULL,0,0,'2026-05-10 13:01:28',7,1,'approved',NULL,NULL,'intermediate');
/*!40000 ALTER TABLE `review_bank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review_documents`
--

DROP TABLE IF EXISTS `review_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `review_documents` (
  `document_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `document_title` varchar(255) NOT NULL,
  `document_description` text DEFAULT NULL,
  `document_type` enum('review_notes','past_papers','summary','exercises','reference') DEFAULT 'review_notes',
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_content` longblob NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`document_id`),
  KEY `module_id` (`module_id`),
  KEY `topic_id` (`topic_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `review_documents_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE,
  CONSTRAINT `review_documents_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE SET NULL,
  CONSTRAINT `review_documents_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_documents`
--

LOCK TABLES `review_documents` WRITE;
/*!40000 ALTER TABLE `review_documents` DISABLE KEYS */;
INSERT INTO `review_documents` VALUES (1,5,NULL,'DADA STRUCTURE QUESTION AND ANSWER','','summary','marking-guide_devops.docx','docx',25014,'PK\\0\\0\\0\\0\\0!\\0�/,f\\0\\0T\\0\\0\\0[Content_Types].xml �(�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0���n�0E�����Ub袪*�>�-R�{V��m^�1����H6���{�$��hm4YB��ي��%`����*�5y+)��[ɵ�P�\rD:\Z��&���Ɗ�S�O�E1�c�<X��.��6̘��π��zL8���\\\"e:�@�:��5>nH�Ԕ<7}9���d���vP@�?\\\"�V�\\\'����ȊU��mO�+��HB���>�u%��yH��`[� �tbaPY��9���Z	h���\\\' F�NF�m�pe��G9b�h���h|��!%\\\\`�܉�����(~�w�Ԙ;�S\r��h�;!�Zh���9�6�\\\"�s����?��٬.p`!��]���g�yH���v\\\'\\0\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0K E��\\0\\0\\0�\\0\\0\\0_rels/.rels �(�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0���J1@��!�{7�*\\\"�l_D���c2��\\\\H����A�-�E��s;�If�ٻQ�P�6x˪A^c}�౽[\\\\�Ȍ��<)8P�Ms~�~�����,\n�gs��2��*D�҅�K�zQ?cOrU�W2�d@3a��Q���D{��?�t�h�Q�hS�Nl�.���+0Aߗt~�\n���B<�ܓG;Ψ|�*r�o>˿�����n��9�<�5��Vz\r�H�>�:����=�7d���i$\\\'Wټ\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0�d�Q�\\0\\0\\01\\0\\0\\0word/_rels/document.xml.rels �(�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0���j�0E�����}-;}PB�lJ!���\\0E?�,	��`HI��`��r��sπ6����w��{���r茯{�*x���AkWk�*�`[^^l��jNK���D�8R�1���d:4e>�K/����4�Vm^u�r��w2NP�0ŮVw�5�j��o����7o:>S!?p����8JX[d�0KD��EVK���c2�P,���ũ�a���]���.���ﰘs�Yҡ�+�����(!O>z�\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0�~kڦ\\0\\0�5\\0\\0\\0\\0word/document.xml�]�n�8�_`߁��pb;���3����6ӿ�Ng/�D�S�JRv<Ws��\\0;��t}�=�$�$����V�ND&E��?����M(Ș)�etRi��+�E�x48�|���=�mhP!#vR�2]�����q�\n���,2DD�5����И�U�i�B��B�+�e���2��~���6�*���u�+V�gZ��:4\ZS]���7��(:����Ú?�ʰ���ƃ�4k�jǷ�w�&c�;T!5T�ZH�(�wAnL\r���f\n\\\"�G�yRIT��D�βbi�Y�.��>�M�f���XSL@d��<��4�Rips�o*�8y�I�8�:@t�Z��O���E����{Ԉ1{�>YX~g����h��/R͂r͇	ؿ#�H���hf\\\"jz\ZΛ�$|]-?W2�����I��F3Y�^=@V��E��LoHchʡߺDRQO@���	Tq5@l+���5�d0�טLZ`���\\\'�z���iw�Gu�VDvY�&�ܽs��$_){�1�!o��c�Z�Q�^�Ϥ�vbd���x�7L�R���D�[m��46�>�����R�����ѯ����̥7T�-u!#����gR�B\Z��\\\" =3�\nfT�����r�C��6!�%$�7��H�{��wqo�ҿ��#��<{Gi\n�{���tlF�jY1j3-��u�m坴�������o���.�����.��5iǱ����\r\r�S&}6�T���(ܷ��q�s}��\ri;���Ur]������C��*��B��T8��U�\\0��Z\Z*Z��D��^�B��=��ĉ�l�j��R����7�$l4�)���^�BҢ}]Z��f�mO\Z#�\r	���^Ό\\\'^A-�z��]Q�\\\\i�^Z!�4�ov�HB;����.I$_���w�%\r9#��a�����\\0� #�z�ٳ�<K���q��.Eԟ6�s�f�{\Z���x79z<�р��T��L�����C�@5n>��\nƐL�w�<`���L�7���I�����7U��.�u|�0m��\n�谆(F���	S�F^�)��J��F@?^@;�J�B�t\\\"`=��p�4���98�+hiki�yG4�g; T*F`�K���^b	��.N35�>CN��-h7XP���G)��:#\ZD):�Q%2�� 4P�pm�F��BN�~#/��Rl�h��EAcg�|��$J%R��\nH�:#\Z4��#[mv=с\r� w*�M�1C%���h_��e��2`�e�22\rd\Z�\n�4�h��A�=@�\r*$V��`;c��Q(����l�S��ZYC���pKZg�p�>�+�ylU�WC�6f��E=o7\Z�GX˟��s]�9~v.ع �G�������3��(h��c��F�\r�n\\\"4�H�8`{( qx���X4vw���c*b�X!0�6 m��PH�p���X4�S�L0�J<��!�V%D(�������uF4������Iy�%\r������4\\\\^�Y��l{�Yl�Ȩ�Qa{(D{xר#�B8�R]Q��-��Ϡ�#�I�b,�9�� l�U ���PLV��y�E���Dg?Sv\\\'�@�p\\\'w���������MI��y	����R������F�v����G\\\'��8:AO�����z���@���3�zBt\\\":�< y�������F8��r�4~�!����B2t�p,���b#�c.�O�)n�B�\\\"�@�����t#�k���\ZO�#*����[q�$�h�A��w6�mG�=咘S�C.���!�U⻿����/r*4h.8z?F8�������;7r��A|��s\\\"e��)��R�Vh����6�~�N�����b�G��6�{�;\\\\�0E���r�q��X�4��>O4���b�K8j���`@�%�;6>Cb�ʅ�&B�~��x�ߎ�\\\"��\\0�v\r�M������f�X[�lV���>��-l�\r$#.Io\ZzRܷ�+���E5���^ai���n�6Vb�J�-�I����M����ç���N*�a��Ð��д\Z{��T�l\r��iƘ\\\\�6 &��~��RH����+X����,?iF�\\\\�i(c�_�E^-�\\\"��/�M���<�\\\\_�}C�ȧ?�$��T���#5r�(T&#���>#O�*5#�c´�TI��qB����m3�R�ر�9�� y9\n��\\\\��<�i�|Ϲ�YBx�I��ui�\r�=i�7$P��Z{?a-f���r��ۮ�}=�{i�8h�,4�ّ\\\"	���y�K�g4\nf�_Ґ��if\Z~�x`�\n2��7�=K˳}t�<^�8��%���I��L=��mf��K8󛿪���:��m=t*\\\\�;S�Z�Y�.��WL�B�7����K6%W�ی���M>d,���~��vl��������hv��=4��_@��\n� �Ы���B�ca�x�ǌt.ӓ���$NT,�-������mݧ�[�}t�뿱G~fшG�M��&\ZR�RǴ)<9\r�-Փ�tuy\nu۴�z�r��^z�s�z�*}I�����ҁ5��N��\\0�ݼ��Ԣ޶���{vEj�u�rϣD&���A�ݮkZ�����x)-^Vۗ���5�|�:����{�Ԭ�����㴹G�h�I��~L�4��H{�O,w_C�k8�xj�VV��-i���!i/i�Yi��,��h\\\'��C������id�B���bZ�{�\Z9R	\\\"���t�\r�}.Ϩ?Jߒ�\\\"6K�V�(\n2��.��LM�9[��~��R��t�_%<�:f��P�͔<�<�l���i�k�\\0\\\'�ER�P@�FUdM�����jL�5jA��	kn$�� �3a���\\\"��o�W�G���\\\"sQ\r�,ַ����3nu�XL��%��4d���\n&T��\\\'qY��O�%�z����:�|�պ�tɄ�!��-�*.��U��� \n^%#6�$@�J\\\"f&R�ʢ�+�r�kܭN�J��U2�!+�\\\"`���d,�6�8���*I4Sp�^pd�l�������ȰAtD��G�㷅c���L��w��ǚt�[>�����@�o?��W�cMH̗����kٖ3�N��&ݞ�]�A	N?���dtZ\Z},�&�X��t3�bA�Û�R���>���ĨMy�\\0�L�L��!rt���#G�Qd������ñ4��/i�+�~|�dO%�I�׌嗄�^R�KW��L�ʰ�r��ʡ�^��+��I\r��K�h�z�����M���\\\"eG��)��|s_��G�M~��ѯ1�5ޒV��es�!�k�q��\\\\k:%5Ҏ���yP��>�y5n̼\ZWIc��iFO���${x�*�q�t.=�n*$�#D��br\r��\\\"�=��_,\\\"{;�mq��bݠ�/�ʾ6x���X8v��m�I����2���Ts�nN�Oo���u�_����΢�����y����3χ�#��H���e���	�G�<e;5�@/\\0� =qӗ��\nA�!z=�>�x}@�����&��9:6��n���>#���3�F�!�l�xç���t��;���y@����6��{�S����ST��m����������8��yrT\nbz~�#£81�&c������(�2�F>�3Ƣkv3�������;�..�v	��zΒ/(�!�Y���,)�d���5���eг��{?�����Ǉ���x\\0���1��Iܮ�y0��1�M!����E8�<�w���uYp����u��dQ�Q��qс��>��W���˃#w������d0u?���NΜ�\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0�@�$\\0\\0�\Z\\0\\0\\0\\0\\0word/theme/theme1.xml�YM�7����;�3�X�\r㱝��MBv���<#�(֌�$�	���z)��C��PJ\r4����Ц?���c�l�K���5������H�z�,��	���k׮8���D(�����a�m[��,�d�k�!�����U���BK�glt���^��B��2����.�4�F�\n�)���YM�l+�p{{<F!���K{�p>��_ƙl1=���f��Ѥ&�؜�Z\\\'\\0wm1NDN���-]�Qvu�jui��ے�P�-�Ѥ��h<Z\Z���6���|7h\r���ҟ�03͹��^���{l	�\r���~���K�xߓ\r�@y�����*�%P^�1i�W�+P^ln�[��w[\Z^�����񚍠��2&�����a����P�����3�m���!�CP�e�O��\\0�FY(N���0��ԝ�����U%�A�:o\n�F��c���)�ڟ\n�v	��ի�\\\'/ϟ�z������co��\\0Y\\\\�{��W=������>�ڌge����x�����k��y�����~�Ǐ�p��Q~�RȬ[�ԺKR1A�\\0pD���8�l�g1�6�\\\'\Z��``�����Tȅ	x}�P#|��G��$Հ�����9ݔc��0�b��tV����4v����l*�=2��Ѽ�E�A3�-�G&\Z� �����02��d�\\02����մ2��R�����ȷ���V�`��><ёbo\\0lr	���`�AjdR\\\\F\\0��H�i��q��bb\r\\\"Ș��6�kto\n�1���SI9�����2�O&Aҩ�3ʒ2�6KXw7� ��u��mM�}�t_���	2/�3��-���x�r^]��e����{�Oޅ����Ysw �f�e�ܧȸ��%|n]�B#���v̲;Pl���_���m?�^�W��.��u]�I�����#>���)egbz�P4��2Z>*LQ\\\\��b\nT٢��xr�����F���u̬)a�lP�F߲��C孵Z�t*\\0_����h\\\'�[���c�ҽ���q�  m߅Di0�D�@�U4^@B�l\\\',:m�~+��Ȋ��?lxn�H�7�a$����y��S�v�0��些Lk$J�M\\\'QZ�	��z�s�Y�T�\\\'C�I��~��\\\"��\r8�k֩�s\rO�	��k�ŭPө�Ǥng];�@�e�R���%9Lu��O���(k�������-9��\\\\���\\\"���I��1���UU��N����\n�	�GItj����\\\"P^�&!Ɨь--�U��j��_�V[�i\\\'JY�s�*/�桘��J�/&3�e�.}�^l$;J���\\0���Y?��!_b��}�U.��Z�)�n�)q��Dm5�FM26P[���vx!(\r�\\\\��Έ]��VŽR�6^O��C����:Ü)��L<#�˹��B]θ5��k?r<�\r�^Pq�ޠ�6\\\\����F���Fm�՜~��X�\\\'i�����<_�}Q�o`��}%$i��{pU�70���70�yԬ;�N�Y�4�a���ڕN��U�͠����>��v�F�6�J���H��N������������y�]�W���\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0w� ڼ%\\0\\0̶\\0\\0\\0\\0\\0docProps/thumbnail.emf�}�\\\\�y�o�� H !��q%����H6«u�(�n=p��qM�L{4�ye�G�	-�@�M<6�E%N�q�!D5���!�ٲ�a!Ab6��u�(�I-������ۧO���ӱ�=u瞾���u��;��WGD4L����ow��� �;F��k���(���%�\r��s��,\\\":m���v%�E�PL,�z�G���ݻ��p����n����]�>h`ɹ?�\Z>j��1��L(�e�UEg�k���oS�6���?�_?-�>lju��{��ؑ��h����@��pѳǿ;\n��(2��@Z��+��R/���K�%�t]߽�v\\\'�oܳ��d�Eg��b���r�)��G�%�m%��}-K����u~\\\\Q\\\'ai�:�l�8Z���U<���ɤ{||x��P\Z\ZM���0�6,��l�R�����\\\".-�3x�����Khy��SQ�%g;u���vs�}���Q��9��7$��2hD����y=�0B��W��r��:\\\'i��f=�&7&7_�1��P�?<4:�1�nhx������mCD�&BK���x-{�!�?_�wW��/�:\\\'�ة34\n�Ւ���J�o��?;�En��v��_�h��/r���X}p�T�:پ�C=qd�D�D���o�[⻌�^�:?6_�W�V��\n]����z�N�s��п�ѫ��������G��Wi�Tq�t�A��i����Ln,��*-��wF�OWZ���l^f5��x�,k��W�{t���D�9�[<��ǆFK�&��d�B|K�6,�|[j�|F �^f|���Ňy��+��h��j�$^�{���y�d0�t�	s���\\0;��*`y�*/lyyJQ|��i�V{���y���Z��p�U���ě�A�D}�ޢ}[�޾�oҴE��j�Zoq���\\\".�={��9�`p�\ZKx����36QL\n����dib�o�TL\n���6Y�8:�_�v�La�.æ��#����2���w�9�P����}�Y�-K�9\n>fsϛ��b�5��m\r��9u:��E��pM�8�X��h�!���R�q��bV�1~�b��h��i�:b	�#�a��hihtjlj2�3Z*N��g��â� ����vQt\n>�{�D0O�׷��gۉ���]���c#�ђ�0���J����\\\\�h9:K<�ٌ��&RDG,a}B��H-u:�;i����)�/g	;�}W��#E��j�;��.o\n�k��9�{��)�wF=���FKS)f��2���r�ŉ���y��y��Hqr9K��Ɏ\\\'R�3p2����N�8�f	�Ѿm!�Jq\\\"�\Z�� �TwS8�m4�\\\'����f	�`�]ŏ���t�<�/�����\\\'\n�!O������u�Ʋ�#$�k�� ���>�}-zgp��EFm��l,�gK��XZ:<165x8��\Z/M\Zk���*Sx���`l���b����1*�c��l�/z��~�c�<����f	�A�J�/u:��1*������GX�mѾ�!�O��Z�[�6n�#-��tʒGX�m`ɩ��HQ{v靁��-�Q��D�\r�K���Uԙ0H��h�,u�x;��6���oGX�mA�M�x�:����w����ow����}�	�m&ś�j��sK�a��YMs�\\\\�,k������~������u�Ha���`���L�.�����y��y�̤ȹ�%�D�L����y�[���p��X£Ѿ����p��ո�z�)���j���m�\\0Kx4ڵ!�;�W�-�x6��xӡg��2ӭf���K-fDN�����1�p��X£A�<�bF�tf��<�f�H1s�%<��*��\\\'R�H��-��ԉ��f��4G盶>�����@q��_J\nI_a����e���#$��p6߄y��X���ϢI4d�e�}iMO�h:��\n��MR�3��\\\"��3-���Mϰ��};Bhz>E��j�/rK=�ɹ���sϰ��wm\Z*\r�A@�m`��Mc�]yc��s�����m:62��s�ڗ���O����<{�<��O��Kx1���S�I���s����o�`���^bd��[̵�j�߈����[!��KMa�j��{���M{�Ac0_�?\\\"3�B���dr�P鰎���<��F�QX)�@=;���!]|V�s���\\\'l�Ī=����@q��8	}X�O٘����It�Ű���y�^�~#��K��8a���R�30��1�8g�x��fU�^��0�����X1,5��\Z���X0�ͦK��9��Y��kb����\\\'�����{f���3�q�D�mT[���G�U2��sW��#��J�R2r��%�d�J��P-��\n�Y\\\"3ϑi��b��b��&��U��S�t>�0f.l�S|^��0>���ύ)>�f�V�­u��g6�]o5�ѹ��/d	[�Ϟ���Tihxhڰk�2J��\\\\&����E\\\"5���l�(ژ��B��%���)��Ng�h7��Pԓ�hK��(���zRI�ƭ��[k[S(:�j�3�5˚�x7z��ɒ��7�iT��];J����7��5���%�X�� JzR�H��@�M�r{[@ɡ%{Y�M��m5Pr(E��l�\n7qk�m�/���1Jp�,k���]��:PL��FƇ��kill�|�C�T8����X.��WpQ�W�nHte�j��j�~���,� ��ؒ:������-`k:�V�%����^[�)��f�V��[���|u:��j�%���*n��0<Y�3d�O�R}fc:^b�\\\"Gg�s���d��R�i�Ѳ� ��\\\'#枥˶&�B��\r�c�3Hq�p(0_}mb<��.�)3!��JK����dK.p�*lH�ۘ�1soL�������������_g��v�Ǳ:�w�Ox�vB�26�m!J��^�0�y�\Zy�P�R�1�f��6���9Zi�|/K���\r���F�nώF&�.��;䚃��\\\\�����Y\\\"��x1�^4é���16�E=Ϛ�*g	\n�8ϟ�y�[dN�1I�LH���X��Vm�c8o:��m�;�t�FϏ�c�Q�o���>Ř{�|?���q�p����\Z�~6ŷ�l��S��Ǜ\ZC����_���,�T�ۮ�y�[��X��ֺ�se9k�l�\r{�\\\\���dI�G�	&����+9S���|Nɷ�V�5�!Kl�96�dͻ���ǫ�g	���5W�ҦF�Rޢ��Ƴ:s��/�F\\\\�T��9s/��(n�H�0���/�z!�\r�=\\\"�g�=�w������Ǌɍ{��F��ܧ^�#-����u��E���-_�>�+��r��v��>\\\'����twb�Ov�7��]:��\Z�ܗ#���]�Qn�ݫwՃ=�V�������fY������M%v�\\\\K���×?�:>� i�s�\Zt���p���E�K��z靹�1�/��1��ca�\\09�WA���X�\\\"R�ҭ�\\\"�_Z��^�\Z\\\"��n=��z�G�k*��Q����_ⷿ��vݎ������,�4��>W;>��Y�ѣ���Gw��,��i�Q+������4��+2�ۛ�гa�$Y�\rۛk�l��\\\\�g��l!Ꜥ��V�(%�4\\\'\nC��b�sxl���\\\\���bre�k�y�ɍ��-	^v����\\0��S��-w~��}�Ԓ���|SƯǌ���X�1�,�T�d�`�����+�~9�G�c��x�=�W���5�iw�τ]���τݙ��L�����#�2�qV���,G袷�rn|��C?����������ç��|�5$�4G�t^�t�K[rR�~-�ޕ1�S�]�U.m��\\\"*�d��8&�eϋ/�*�6�z1�K�����̀�k/�y�ϼ�^���ߗ�����[y�Zޏ3Ð�6^��2�e8J���y�֌�ν�����P�F)��0\r��\\0�����5>��W�!�����q��n�����3�c�9v���������������ol���JO�>��w`~>��4|j������p����E����;�O���a}��m�������$~� ���������S��\\\"�3�8j��.%�=�U>�)C&����y��0/˸­���C�u?y�sݽ\\\\�#��d�~0��q����m����\r���q7���]t-�k�>,���pk|��X�Ƿ�Y��[��$�b[-[�\\\\>/܇䯲�1 ���x�/���\\\\�?3���)m䯮#����8�3N������1C�ϹT���ɨk��>bm@�\\\"�k���߱�%�\ru�����n}�P�|<?��)�c��VW~=�C��O��</���\\\\�Gb{-��s����^����`����\Z�,b��P,^ ��_�ڦ�IlZ^�i�\\\' ����������!����ǽ��\\\'pC�c�ndMi3]gJ���3R��f�u\\\\6�XamA��;�|�_�xE]�+��FX�{,��{��z._���([<�m���?l�B��	�?a壛�\\\'�R�B>|���a@Ǔ}H�:��o�}~�.�h_�N�c�Z�B�W�seE��Gy�G���o�w�[������{��ҟ\\\\������������X��~_n�L�f_�f�ˌvT�~_��ʾ��o?�r���q`�2����g��c�n�/?��붞˭_$g��r� ��Ę��\r��OX`��2I���<�<�w�Ӝ_a��v+K�B��#^��Q�_����%W`�+-�]��}�<��	^^����n�}�wy�^~l���\\\\~���ݷ᣼�)���t܀�W�ı� C�eǟ[;��v�)�u�����9ke�-�|��\\\"�㟢j?r��q���m(���1���9���f^�r���_0w�`��[+��o��-t܆�8e�~ԯ5.@?_�����&�/yj�Mx�id�[�?��P,~|?���X�XNe?|�	����0��+��j>�,آ�c�и8��qޮ*��n7���yjw;�`v{E���~�#�_#��(����p�C�E2\\\'s�n{�����6�	܆���c�m��1�1mu�mґ�7�-�@��#V�x#��c]\\\\����2�]��\Z�!#�1Ε\\\"�1vèm��������(��&\nN�I�yG�<=Je�������+��\Z1C}�]8VsR��ڕ9�Se�Z�	^A<�����j\\\\���Sk�	��pۼձ�U֏k~,����m��j�O��}@�9�\\\'�n�:��Hr}�>��4�ʋ8nMT�q�\ry�����3�8�p�(�#	ȯ��Y+�ЎX�npΝ!77y~�qy9ox\\\'U�k�gI9������G���x��`��V�A�0�,\ZFp�#�F�7��{���Yt;b�|>t9Q��c��t<�U��z���$��#�\\\"sM ����f~l{I�I�,��uG��x��W��D׏nalzo��s#�	�jٔ��82�O���m!������Ϸ!O�9����|pp�I@��N���#ᦝQ�8��#�ɏx��\\0���]��s�9:f:����QHG�_˳V�����\\\'��EQ�s$~ ��߆^��c8�F3�GLY=.��X�=���őس!`���q���Sk��pc�����*?�j?|�Ԙ# ��Gd���q�$��$w��q�p$y|�?p�!`�*�׵!O�<�S�#�c&�n�t$���<ke ���	G��z=W����#\\\\;\\\'��*sҵ᳤�4�(>n�\\\\r}�<Ӕ�\nަ{�����Lu������F���w=��c��]�6��øǠ�+K�r���aul枻S9n����I�{3��}^~;��H���[	�)sd0��f��fv?n�M����u8����x:�w��c�_7�؏�:.�:�����?	�����j���ָ2�{���B�y\rn��\Z���oC�\Zs;��}���,�|7N���G\\\"�ۢ�q�L$���?p�40E���ڐ\\\'p;������1�n���B:���zX��2���]$�?0���#����6����0�ý)`g�����.d���5��{p�Ə�oO���[aΛDrmۗ�{��ȟ �Ő|�q!s\\\\_>�Y��Msd �!�m�p�\\0��~��|?|�Ԙ� ���e���q�$��#�ܿ?j����З����[����6�	�\\\'�st�t���m���$ ��g��d,ˏ�p��|��c���>߆aR�;��֟�A�H�h�m�N��t�莱0u�P�b[a�����B�x��m�Sk<ȱv�^y~�G|?�#��\ryj�#���G��O��8u�<I~=\Z��G�6�\\\\��^��#ST��\ry�����<3��p�(�#	ȯ��Y+�ЎX}��G��ھs$~ �o�a�9u��^�>smu���\Z���X�����B�(�����s��;1����ۙ�ָ2�W���r!�?����߆<5��w���Y�\\\'�n�:�;p�rꩨ}��\\\"�����x��ʼvm��㪆�3��p�(�#	ȯ��Y+�\\0��I�wE�~̑�q>U�k�-���0���8��:	��sd���h�\\\\�~<���8Dϋ��<�}��D�k����7#���p��o#߷<��7��\\\\lG)��������÷!O����7�|�M�I@��N�g\\\"�Cܷ�.��A$�pF\\\\�7�ST��\ry��h�7�1�����B:���zX��2���=$|?�G�~̑��߆^�9�&sN�ĜS`o�G�A�k��Z�]�M�6�imrc�۔���a�c���q�/����ck�����M�~�a�F���B�C�A\\\'t(�{S�ͷ���@�\\0?@����p �\\0�~�}?|�Ԙ!��f���q�$|)n@;/4�{I8pu,�]���F$���Ϸrp�*?7X����~?��.��m+_W�Z�B����ٙ�p��\\\\�\\\\N<?�H����*sӵῐra�9���T\\\\�,���5��\ro�j�ؤ\\\\⎵��<�^T.Ţ���)/�uH|W=z}���z���$Vkc�`[ V?��Z�\Z ,�o���4j83\\\\�U�����k,!L^�}�Gڷ�H�s1�ۗ��x2�A���p!<�������s˷!O�y����|�l�I@��N��ű�Κ6�O/	�^\\\"�n�����j�umP�Ee�Y�������嶕�+O��,d����?#��������˩27]�I��i�#��+�W�g�q�t��Q�\\\'�l|�{7�n������} `7�W���۝���R��B��o��a{-����;p��1��`T^��9��_�����sK��o�����i�C!���)��p��8��÷!O�9�!ۗ�\\\"?	�w��Iza,���\r��K¡c�A/~�Ѝq5��6(�^A�8����.� t�m���Sk\n���\\\'7��E¡�㦨ڏ9? C��m\\\'����F�}�b|:�O�W9e���tܨ�uL\Zw���c��p8������}�Sk�Ƚv�W�~�_|?�/��\ryj�/�n��_��O��8u�`��|�݆��%ᗑXr��?����k��˕��_�����@��/��<��/��������-$�?p>��c�����߆���ᗃ��CyB�!�#4�ݰaITm�w�\r>?�6�5~��N;��\\0?����߆<5��?���Y�\\\'�n�:��ƒ/7��}zI��b�������\\\\��Q6~�.����\\0]>?����\Z?@�1�	?��?����#���~�6\\\'��OX����s>�#�B��0�\\\\�o���s��~����;�	�IP�{$���%�nF��������|��\ZA���\\\\?�E��t�߆<5�\\\"����=��\\\'�n�:��\n����mh�^.��Xx⯽�����j.rmP.�Nٸ��E�.pt�\\\\���Sk\\\\��X�X�.�x.��c��ب~�6��\\\'���\ruxK�\\0\\\'��Si,]�@9�K\\\"�	�y�6a<��ַ)O��d ���/�����,/s����X�ɥ~�6���$�m�{x.c|&_\\\\#9l��2W�9*������qǎ���j�\r�O��k��G��y�v�\\\\W~����$n_�e\\\'���N^.���W0��7&�ٱ4��Ny�)mY���L��q�Cݎ8��ת�e�]����v��\\\'��Y�����/�����1�~͋�����~��A���P�~\\0�����C�r��ו����\\0�A�B��+$�\\0��TT���+��s]J$��7�&\\\\O��q��9�M���f1�)��!���}�Ҿ��z]�B�r_$��A�����]|��Z��v`�.�+/���\\0���.�\rh��(ԇ��\\\\G�5�f�����m�%+�?H��˱��?�+ߥ��y�p_����F�Mx(x���m�3v��1�l���b�֣�V[��y�����c�+��v\\\\?�o�$���x��ٵA����������?C��V��<��ϐ.iG�H�~��{~̑�����ѷD�c�Z����J@�W�>�?]}y�}��$�\\\\�0���\r+߿Wە���+{��w��Bگ��g�7ݮ�z��k�S����H|���o�F�b�X���=��i�F��?����2u�\\0\\\'?��*��9������+�-��47�	��� �nL|!����K�^�����Su�1���v���^?�b���E�[a��l�\rk��5��<un_������ױ����)�t�7��3�A��x~w��3�Y?��t�7I����7��G�c�>��*ߛYX_������{�k���$�=G��	�f\nZX��{�G���{���~o:�ݨ��J�r=9n.f��c��$�Vߙ�w���ߋ�x�۩�}�l��	o8N��\n�T=_�M8��[Oh\\\'U�q]��M��k;�O�(�;k���?(�p�i����f�����9yO��Ӊ�����K����\\0�\r�}5％��=vg���$���\Z�c6w��M����Z��Z��}y���4}��/��X�۵���2H�~�e�F\\\\s����/+�\\\'9?f����\\\\�S;�Y|�������Fm}�>���Z�\n�*�`;�=@e��]���m,�����8�(�?�M?Kݗٲ��Ybs�Į�9YP�ף�?o�}���zv��)��^e�=߮�wV\\\\Z���F�r��+ֽ�^�S�k�v�t��O���5�	��<��^�^�=���ԝ%f��$����\\0}�^��r��I�\\\'����6Ģ����ű�ʵ����\\\\�DN~��!϶^.�͉�Z����{l���q%taѱ*Ο�;�����p�ɷ�i�\Z��D�2$��V�w�q�)�|�&���J\\\\-�����=���!�������߫�U��9U43�S[~��=���}�\\0΂���B�KoxnP�ʝ?�<C�q��p�3�fw^�s��WT����(t��[qe���w���?�6���Fn��˃�<@[{�5�ˉ�Zms�4���˼o���������$��Bն}�Ķ,xE�ՊI.�m{�$n_��o�}+�;s���ؕ���cϲ�xG���~e�����I�3���T���#�cL�K�t�dk�\\\\�h����}�ר����ʿH���>��h?����~�����ݏ����s����XTƢ�Q��9~S���_��;�����s|�-��lg�8���rK�G�Mː�e�E�����c�yZ����_VZ}�m��\\0\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0����\\0\\0-\n\\0\\0\\0\\0\\0word/settings.xml�V�n�8}_`����:�;N�:�����6���đM�7��w���CJ��MP�[��Ԝ�3C����?<\n>؂�L�Y����\\0d�(��Y��~9<O�I	Wf�l�������\nΡ� ����g��9]�F�ހ �Di�6���ӬG���Vk%4q�b���(Oӳ��Q��5��)���FY�8R��a5�?1����Ru+@��qd�c\rJ�\r�6���ˆ�&�l������o��Glw�}�8�<����Z� �c�L�_=�>����giX=�|�c�+�3?F1�)Fv/�1Y~̑t�-�1����uq��ʐ�c9x.�� T�\\\\�ʿ)%�B���E�4y\\0X5�#�\Z8=Ss H�+ֆT{��\n\ri��\\\'U�F�-��yOYo�!�SjR#ۥ��(���K�K���EX��;[�;V��@G��*�VE\\\"In�E��_pk����BQ٤���D\nG�a���n�a�{*�7�K����!ch��{���?� ��\Z�@��_�,\\\\В3�b�(s#)J�%cM0��\nUŌڅs���D�ɼ��2���ڸ�����i�M�W�YW�G�A��Y��|Y.\\\'ٻw}�>�(�l�3q�%4]�%�ad���w�=*�`2����H�V;�\n��[/�EA��WЄ5_�>���M+���\\\'.?6��iT�;tg��]��d��2����%qn=�ZI?oM8����\n�WZ��_�ïe/%nJ/X�;5U�l�p�޸����G:|T����>H�w����`ˣ��i��l�hl�h�lg�v�m�c�������(��������\r�pՍb����l��m�8�2��}4��<���I���d�Z���c�Y�d�đ�R/����S�\\\"j�r,��:L���p�,����S&b,����ݣ��b�@� h�QU�P��u1����z�_�Y�����|�M����<K��x1?�����?��_\\0\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0�P�\\0\\0b\\0\\0\\0\\0\\0word/fontTable.xmlĔ�n�0��\\\'�,ߗ8!�\Z*��ҴI�}\\0c�Ī\\\"�@����\\0��`K�(9��r�a����`O�aJ�0!�$j�d�����a��Xn1W�氥>/�~y:�K%�n�4sArX[�̣Ȑ�\nlF���%K���UW���c�<%\Zlنqf�(Ah{����ʒ�]���҆�����45k̑v��vPz�hE�1n͂w<��<a��$�ʨҎ�b���M�Qx�Ȇ����a��GD��A�/�T\Zo�#�%W`��Lp�K,\\\\��l�YH4X*Cc��c�C��e�����F~ ��6�C����X0������\r��>��X3_Z�2�r��٠�R��UQ�.�ꐋ��eI�����GƧ�8�5�8$pNc�7�����7&�?��*��#	�8���͌с;Ȉ_����,�\\\'F�J}���/�k��n�\\\\:ɜ�ßa���I(;�M�N�\\\\�_N���N��@\\\'kZ)\n�_��ůYi�}r������g#��lE�:��Hz�3�w��U���G|���}$�c���7\\0\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\09�g��\\0\\0%\r\\0\\0\\0\\0\\0word/webSettings.xml��n�0�����%��Q�@Vt0��=�\\\"˱0I4$%n�����M��h[v�I4%�L�/\Z��̓���+@��L0\n�fP	�,�����)\n����4/ц[t3{��+:�����\\\'m�)����q�-�в�+j\\\'�r�7k0�:�h����ת�b�Z��BH�6a�q�v�\nԵ`��Jq���p鉠m#Z��uo�u`��\\0���z����	C��̀��M|1���\\\'x��|$��@j�y�d��F�(V|^j0t!=ɗ����f^�J��n\r�BT%�p��gq>X@��6�T��������k���\\\'�w�l^p�C{꜃s����D��-���MD��>��z����l��+[�<����QF�Ś���	\r�����Y�2����44��o�O�$M������[s��u�{���@R2�9!��4#�,���C��(�H��z\\\\`�8�#�9J��_j\Z^��)@�����@g�2�RB�����~nf�\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0�*e{\\0\\0\\0\\0\\0docProps/core.xml �(�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0���O�0��M����n#�Yƈ?$&b4������6�����n��\\\"&���>����&���`��jL�ADP\\\\��c�2��7$pȔ`�V0&{pd�_^dܤ\\\\[x�ڀE	.�Jʥ܌�\nѤ�:�����\\\'�/.�-��.�a|͖@�(\Z��	��ւ���AR�N�ll�N��:\Zbzbl��64�d)qo�,z,v�����jP\r��ӷ��ss�P��+$�OQbyFO�����8��]�cn����l�fv���n�ҊK�+��G�v\r�J[�R/�\\0ǭ4�ߴ��;�t���#/$����#�u�����K�4D�f��5A޴���Xy�?̧$O�dF�0���Wir�F�{�i��$X���0�+Z��7�\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0@�HZ|\\0\\0�z\\0\\0\\0\\0\\0word/styles.xmlܝ[sۺ��;����S��Ȓo9��q���4��i�!�P���Kd��A	���I�Kb��A�wX^��1��<˅L/G��G�����Dz9�v��ūQ�,�X,S~9z�����˯닼x�y(@�_$��hY���8�<a�K��Z��Y�\n�3�\\\',{(W/B��X!�\\\"��xztt62��E.\\\"�oeX&<-��8�\\\"�4_�U���}hk�E�L�<��A\\\'q�K�H7��	\\0%\\\"�d.�Ku0�E\Z��\\\'G��$�Nq�)\\0���85�q����Q��7����<V$uH�jU����J�H�o���q�W?����4���eZ�����w�\n�E�p��b��p�W�`�+���k¼�����=��U+��r4�6K���,�Yz�,��o3�%֢��^�X�bvU�́��[�����w�b���a��+G��U�XTq1=����z���4;р��\rvz\\\\����YTj-_|���f�Zq9��R���fBf*p.G��}��3��\\\"�xjm�.EĿ/y�-��v�����,S����D{A�G�C��BI�MY���� ��.�v���?\rlb�h�_rV�`����G!��Enm;��;v�jG�ϵ������s���vt�\\\\;z�\\\\;Ҙ?rG\\\"��c�p7�z��F4�lh�#��G��9�H@s���8��q�)�S��兖�;���{x������G\\0?����=�����ӹ�p���N�xn=�\nnT����([HY���A���X�X�Ȣ�U��H�\\0Sg63��L�>�!:H���*����Tm>��<��cU%,�����=���_���!�tl:hU	i��	|s���X<����!�$��C��yY� pꄅ��4����G��\n�)��>Ӹ�f\r�\r4fxi�1�+�^X�Qu�����u���[�T�fhD�fhD�fh���N�N���c����u,����1�)S��Í9g\Zܲ��gl�����X����y#����bLې����E��Q��ޡ;4������k�#\n�\rox�}R��j��������5h5�W��X\\\\����Ɗ��\r��\\\"�� K�����l%\\\'E�۶rxö��a���H�g���e�@��?<�x�ʲ����2��Gt�Y����쐟jIz���d�d�е���P�\\\\P>������Hit{�\\\"a\\\"�f�>}��*3�����E!2�9���|�w�^�\\\"8}\\\":�+��C\Zv-��$#\\\"��f�T�����O�4�,�hh���a)8qƒU=� �-��*�̆4�_,�y!���#�Y�\r�r�oOu�e@rf�KY��z����pç	;��S��\Z*�%8������Q�u��\\\\8/�z���Q�����d,�E�u`$��Hօ2.�4�<b�#<`ͣ>^B��<�Sr���LDdbh�\ZF%��Qi�a��Cǂ\r�Mǂ\r�W��M,����DWy,��i��i��i���\r�b�&�tC����9I7ФOV2c��]���	Қv��E�p�L뛸	��9�p�]�D���dM�X��\\\"8#��XJ�sk�G[�޻v�L?�1�	�1�R����U��~,c����N{~��\\\"�-7g�m���A˦`�1;�ö>?k�gi3��#Q&MC��g����G��6��$v,O{Z�}���Βw,�{Z�}��i��tǲ+޲��λ�gS�9��ˋ6ƭ��r��e��wy�N�WaX]-����}��q�c��M�����;�܈�\\0���jd�$M���� ��It���{)���;��?�u�&Ni΃V�q�W;Y�ݏ�Ӎ�;����+9�Q)�M靛܈�Iʍ@g+8\\\"���e+h �\\\'[\r����n:P!�f\nn*P��W�B\n:P!��T8�*��*��	TH�	THA*D�\\\"Ё\n�@�t�z���^�\n)�@�t�B:P�|q@�B{\\\\�B{�@��@�t�B:P!��T�@*D��{*��\\\"Ё\n�@�5�Th�Th�������T�@*D�\\\"Ё\n�@�T�s�@�t�B:P!��b�@���@��>�\n)>�\n)�@�t�B:P!��T�@*0�\nTHA*D�\\\"���\\\\�t�f?���tޱ��ҕi�W�Qnu�մ����,�)���u��\\\"汐��㲺�շD�.|~��~�Ǧ|�yB_3𓾖���I��ۖ��;��t��:O���m	������㲹)E\rG��+�X��yW���aw�h��pWf�aw�c��4����i�~:��_\n]�h�݄.��Z5�F_�܄��	}etPz:1xa�(��n���0̰R�����\Z���!�[j��\Z&F�Ԑ���?9�	^R����-5D�I\r�2�Ԑ��\Z�R���!�[j��\ZN�RCVjH�J\r	^R����-5D�I\r�d�Ԑ��\Z�RC���\\0�/5DyK\rQ]R�(;R���q�0�7 [���lzTK��g�d<�%�U�9�Z�Es���&���M@�����u��\n�Q~R㪥6���M�J����R㪥N�q�R�Ըj�-5�Zj�\ZW-�Iퟜ�/�q�R�Ըj�Sj\\\\��\ZW-�I���ڤ�UKmR���q�R�Ըj�-5�Zj�\ZW-�I���ڤ�UKN�q�R�Ըj�Sj\\\\��\ZW-�I���ڤ�UKmR�%�Ըj�Sj\\\\��)��Z\Z�w>�T�������ӊW�����w����zÛh�ʸjI`>Ie������]m��je��&�����Ü�<�R��y���fy��^��^��f#����,W��Y}t49?;5.>�5�^Q�gr��^��4���k��Uo����G�N6_������B����#޴��j���	��ѝHx|���L�~�j�ᰖ��f�k�.�������<�hsl�n���&V����ˬ�������6&���f��uE?��l]d�4[��v�P��B�^6G3�W�< �߮��<��0;�d��M�v;7TtfĢJ�m�ɾ3����c��j�j�<�=B�q�V>�6�«[\Z=�\Z��_�8����ʽi�E�vr�_Ǳ�~^�Y�i��)�0�mL���O�oM�{c��]5ζt��QkhO����U��1{�};�~+�J5�&���\Z�|h<ܙ����?.!���`S\\\"�̨�,�0\\\"l�0�#��,�u>��泭�&������C�w>w\r��vێϞ\r?~Ή��0߱>�kn2��ԯ^�o��_�\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0���Qx\\0\\0�\\0\\0\\0docProps/app.xml �(�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�R�N�0�#�Q�� BA[W�q�Q�Ζ�I,۲M��=!�9��z�3����d�>(kV�b��	\Zi+e�U�\\\\�Ζi�0����*=bH�����:�QaHHU�����N�9�\ruj�;	��ٺVo�|��D�gY���TX��(��W��_����_x)���8��9-\\\"��~R	(m�T���V4����W�+��6��BFZ��b	lB��sZIi��AIo��c��i6��M�\\0ء|�*yl\n�^r�����y�x����{�#��\Z7���B�C��vN�ccEzo�ٕ������or�U�v�$yQd����Ŋ�F��x�_@�������F��e�E1�����7G��\\\'�?\\0\\0\\0��\\0PK-\\0\\0\\0\\0\\0\\0!\\0�/,f\\0\\0T\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0[Content_Types].xmlPK-\\0\\0\\0\\0\\0\\0!\\0K E��\\0\\0\\0�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�\\0\\0_rels/.relsPK-\\0\\0\\0\\0\\0\\0!\\0�d�Q�\\0\\0\\01\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�\\0\\0word/_rels/document.xml.relsPK-\\0\\0\\0\\0\\0\\0!\\0�~kڦ\\0\\0�5\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0	\\0\\0word/document.xmlPK-\\0\\0\\0\\0\\0\\0!\\0�@�$\\0\\0�\Z\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�\\0\\0word/theme/theme1.xmlPK-\\0\\0\\0\\0\\0\\0!\\0w� ڼ%\\0\\0̶\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\00\\0\\0docProps/thumbnail.emfPK-\\0\\0\\0\\0\\0\\0!\\0����\\0\\0-\n\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0 D\\0\\0word/settings.xmlPK-\\0\\0\\0\\0\\0\\0!\\0�P�\\0\\0b\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0H\\0\\0word/fontTable.xmlPK-\\0\\0\\0\\0\\0\\0!\\09�g��\\0\\0%\r\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0lJ\\0\\0word/webSettings.xmlPK-\\0\\0\\0\\0\\0\\0!\\0�*e{\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�L\\0\\0docProps/core.xmlPK-\\0\\0\\0\\0\\0\\0!\\0@�HZ|\\0\\0�z\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0DO\\0\\0word/styles.xmlPK-\\0\\0\\0\\0\\0\\0!\\0���Qx\\0\\0�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�[\\0\\0docProps/app.xmlPK\\0\\0\\0\\0\\0\\0\\0\\0�^\\0\\0\\0\\0',7,'published','2026-04-29 14:00:20','2026-04-29 14:00:20'),(2,9,NULL,'exam of blockchain_term1','cover marking guide lo1and 2','past_papers','FUNDAMENTAL_OF_BLOCKCHAIN_APPLICATION.docx','docx',30797,'PK\\0\\0\\0\\0\\0!\\0�P�+q\\0\\0�\\0\\0\\0[Content_Types].xml �(�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0���n�0E�����Ub袪*�>�-R�{V��m^�1����H6���{�ؚ�hm4YB��ي��%`����*�5y+)��[ɵ�P�\rD:\Z��&���Ɗ�S�O�E1�c�<X��.��5̘��π��zL8���\\\"e:�@�:��5~nH�Ԕ<7}9���d���vP@�?\\\"�V�\\\'����ȊU��mO�+��HB���>�w%��yH��`[� �tbaPY��9���Z	h���\\\' F<\\\'�˶b��{��va�Pyy�ֺ\\\"���xy�Ʒ;RB�5\\0vΝ+�~^��y\\\'H��>�py�ֺ\\\"����?�cks*;�����(�c�������:}��D�>{>�+I�<�Ͷ�y�\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0K E��\\0\\0\\0�\\0\\0\\0_rels/.rels �(�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0���J1@��!�{7�*\\\"�l_D���c2��\\\\H����A�-�E��s;�If�ٻQ�P�6x˪A^c}�౽[\\\\�Ȍ��<)8P�Ms~�~�����,\n�gs��2��*D�҅�K�zQ?cOrU�W2�d@3a��Q���D{��?�t�h�Q�hS�Nl�.���+0Aߗt~�\n���B<�ܓG;Ψ|�*r�o>˿�����n��9�<�5��Vz\r�H�>�:����=�7d���i$\\\'Wټ\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0���\\0\\0�\\0\\0\\0word/_rels/document.xml.rels �(�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0���j�0���{-;mC	�s)�\\\\[�d{�C�c�MZ�}EJ�Ӄ�3bg���z�;��5�$���uoZ����\\\'ij��A#z���w�7T������b���hXq���Є��:-)H��AV��E�H�%w�ȯ2ٮ�v�#�b�?ٶi�\n_m��h�F�H6�!S�I��IB����4*��\\\\}���u�.l|!8[s˘f�p��f6������\nY�	�ٚ�x�	�������\\\'~���\\0\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0��GV�$\\0\\0��\\0\\0\\0\\0word/document.xml�}�n㺶�{��\\\\�EmdW,9snՁ<�N<q�F〒h[�,*��!O��?��\Z�~����IJvl\\\'N�T%\Z�%��5s����&\\\"���u$�HG\\0Y\Z�\r������Lǅ�Ml�_G����������X��ra9g#[�u�s]�,r�\Z@�d`h;��hx����=�d��k�q����\ZB�(\\0��׃�8�3���փ�E�G��@��d(�h�5l#���`2�.=%��\\0��g��pm�\Z�a\Z�bS0�בG��\\0��YW�#g~W�?�\\\'�:������\\\"Ȥ}���3�Mo�Fo��@�/!1��v#[���!2��<\\\\���PL��/C��5F���=�N�9��\\0\Z���D�9����\\0��\\0�9h3�\\0DșEcdw�7��{�#4�}�\nV��\r`�2����:��A���@;+t-L�j�ѱt�\\0����7զ*�\\\'�\rFgT��_G�M�\\\"Q���KU�.������]̠�L�i�*���ə���`W	���P�}��L�Q(��\\\'�I�c����B�)� 9l�m͠�¸?���:h���{��<{�Crh�!4��\\\"�+ir�Z(xa(�*���\\\"��4z�6E��%E�p<\\\'?\\\"�\ZE��J�q�8%��S����J=0���YK��IGO��Жv{t��n(�r\\\\n�\nh(WY�l4X�o���N���*�-\n�&�Ad��~��2�Eg��_��I��:�Z7]�������l�Y���B�	�r�Z�f�����ͺ�ɞ�F%�l+�,�d���J��-7��+t\\\\�1௣44\r�;�*߉c�z-��g�@q[��a_����ԡo��d�V��rdI��g���R%�*fA��ɂ����Yh�FZ�}��V��ɥ���9h<\n�G���vޤr�rFaFC)�J����e:O�r�T��BZi*��æ���hN{�G��f�����\Z.�V7h���H�@�h�Č=����|�r�9�V�Z����Č!/\\\'N#J�y����M����������?�^+�9@�ŢRf_�]��/����9]�x���9\nN5�\\\'��Wu�3���}Y9J�FQ�B\Z�d���E��\\\"���K!z�X�Y�����A��Y��-}�����O�+�:�=�v�.�1���t��Oc�WYl���� /���k�r�RN�f^�֥Ro���ߞ�٥���̛^p{�/Soq����r�Yo��_�\\0�\nH+�L!�4������9�}\\\'��:j\Z�2\Z�:�\r6%�s���ƽhg��\\\"bA�i/����64���3�Q�u4���`���g��z��\r�mrĎR���#Y��J�i���1�����m�j�!�#�ۣ��ʱh2��K�`�74��G��3�{n�J#��$h�xT\\\"bB�]�����S����l��D#?{}���/H���?N�T:���I)*Q��_���p�^-A�!�]��2�p$����?��lh\\0�ˉ�cq>ѬM�hJ#���t��f���YL�(�)I���#���T[�?ӱV%���#��u\nZ]��ƃG���bh9B�T	��8v�L��7���9P�B��#�\\0Xǝ���=�(4l�qv�p9�g�����@F��z|�&�{��\\0��_G����E���N��v�1�f�M��:t$�+��i�<J�p)��<\nZ��4g1x��N\n�r�8���^~۬5#U��az���kWv��17B��L�ca��0�kY����46�X�0w�T����}��y�y��p����,�`������[��R��ڂj@�:B� �h�B�A���ʟ �\\\'H�qr��@}3`X�K��I�j~�Z/z�O\\0i�-gD�\ruC��V�r��T-\\\\�B��P�$�b6���`��̶�㷕���8���b�5�c��h.��O?������G\\\\��o܏IS����[��*��xTR2���-�9S���`2	�B�(()���*.�RwD���_��R�QÿC�i��b+���^�r?�����Ѥ℺����q�ǒ�7��.`�b�sQ9��h��+:�u���wZ4�N�w�IV�Ğ�Yj/�F�>O�\n#����\Z��K��%q�;»jUv�r�����h��Hc��h�a�����gw���֨sSs���֐E�k�~���|�����;=bAV	 ؟3rl-g�-BǭB��!\\\".�e�x��GG����s�Y�_G�.�=aSsν	%�Y�����#6K�O\\\"�x�MV��Q��X� �M>�G{�Y{לv�1��Vz��PR��dz�v���R�\ZR~�4^�\Z�+z�M�4�mH�`�Ec����IO�s��솅����|�kp��B�\\\\��l$�șc<�:2_����l�m������/�S(�@��&�\\0��a�W�^��`�3�ln84�A��9h�*���Y\r�\Z��i1>�8?�0wv�_c��ҿY\n��63�=��$6=����O�u���O��;��b�LN���ʈ��,�<��{�39��\\\'?\rKG�ٌ�?���p�M��d���w���_�*���F�	ܙį��.���8%��^����s����Q���A�ؔF?�TС9G�����ÙO��vL�\rm%���G LP6$o�\\\\/��˷�?�K~��w60t�)p|��u*P��ZY\\\'�JՔTW)(��PK��F#ԥ\\\'�l��n��m�<T��I�w����������^4�V<SJ�r���c�I�Nu\n�V,^S�f��RjHi�?O�������8�<�\r�Ԩ���!OX^r�����y���j��X]�\\0%\\\'z3Ht�&͌|1Je\n�J��2o�M�Ē�=77�htn�[\r�]��+^�\\\\��e���(����	�X1���u��e\rs�U�u��ޥ��L.i����&���FL�E�	�8�&�5i2�F������e�n���S�Ѫ�_���h�t��{�W�Z\\\'{Yh�3�L����Q�~o%���3���(|5��T9����n;~74��rh�^8�H+�K���Ll���τB���t��H���l�.jl�����酩�Kâuar�j���r�����3Ûj�s}Gc�i�I�C;�dn�A	���I�C¥H5U���RC�d�uB��!���^�Nō�.����v:����\\\\C^IG�j��p>�t��u]\rG�%�wo�G�Q�q��+��\r�M����\\\'��[˜J!�:8�n��XNt�HT�k���9�T�)�j�d5�٫ӻNvP�`��G��ȑ�ถ�2L��^H�%5|q����;�]+�ݟ�B����p�^Eg�)Q��k���v�6�P�&�T�L��=q���0Li=��n:�t},��Z��7������\\0�WH�_���t|���֚��P����\\\"q{�p�5x���B.Z��$���Q���W��v�\\\\���HSk��r4���F�x5©f�y�G��v<y�����M�\\\\k�Rk���!C|Z/F�1�O\n�D���J�n����=�g���H�����DWP����4�[�+J��8��I�W���4n[�A�t\\\"zJ-j��\r�f�z7j\Zy�L�9��^��B�����ڨ���\\\\�(w��y�?��k�hur�Ԫ���U_�ױ�egr��N9�\r�8�]�=�u��]��T�H-Q�ݝF*�T;/޻zKQ�Ʌ�L�qOH�*%�x�6���MU��v>�8@4�;�|�8�NC*����u�\\\\_��g�+w��z�i&�+��s����/[��f�\\\'��݈����79@Yy�M��>�w���E[��t��/����P��N���R)=_O:�Q1����x��jK�N[�ץ�ƺU��-��N���F��l�;������ý�UU��54��\r;�P�me\\\'%��w�N��BN�oI$���x���;ūr����pٰ\\\"�8���8\Z�R�^��Շҍs���5�k���U�$�F�,z���ǭI��-r��H�u/�ϥ�E4�iݨf�t!�w�I��I�Z�F�^�f���-�IX�Q�]k�c�z>_�IJ�xu�8@��\Z�a���(��q�Q�j(��R�ƨ���x�(L	*=%�G��]���kY����T_�J���@c�T1���q�.��P�X�W��P�\\\"7�ch��WR/m���Z-Wkez:����u��$+����J)^��m��n]�(����o�W�Z:s�`5[��^�#�~![l���t�J+�ҞG\\\"�z;������tW)�ӸޖJ��/z�eΓ��HWJ\r$�*��|{ܿ�d�Uܸ\\\\�O\Z�+���@	%.��q�D��PB�d2\ZŇ�h��ls��ؕG·I:\\\'>����D��_nb^�r[���pU5j��j��P;|���6��i�\ZF;���m(ʔbH��\\0a�rQ<f\\\'6\\0�;|��s3�.H����^���v~�p��CG6�.֔���(i>�Y�R�z��\r;i)5�{��]�ΙA5_~��e��	k�M5���H,�x1�����ʌ�֛̓^f|�,��>���������e��5��{B	XS@Ib6��������a����_G���\\\"�?NÒ�?Y@p�T�F��4�5\n*H��Ă�%�X���9!�\rQZ&J���(-�@�[����2�RߧREi�(-�eb���:/J�Di�(-{��Ҳ��QZ&J����;m�Eiٳ���Ҳѳ���Z���?_�6���%�XAP��>8����~9�����ܬꈝ=]�b}r�#���ol>�S:��:f��@�R\\\'3����t499��߂�[<{A�-��ׯ��F��h3ɳ����QHF�g����\r�\Z�s\\\'�ݟWNC}\\\"B��h���zRuw�ɠ��+ڝ�𩤃63��?��:� ��?�4�)��#hD=�5\nZ񳧍^�.�\\\\v�84gt؜�˺//j�Ҙ�W�A|ˢ�m��	��zK>�tY���aK��#����hdb���3�֒V�	Ɲ,a�r\\\'6���4y���>R���y�z�Vk�#k��L��2u�����\\\'�����4xND��\\\\�O�!�SY9L.���y�}w(�w��S�T6���@G&rY���.\rfV���FW%���;ű�:֑��ئ9��<�&�t@��>rf_��7�u^�N��@�T��ԫ��v���A��k���l���J2�_e�UA�^q���r	6M��C����/���-ほ�\\\"�!�:&B|�kC������J��ML_%��h��/�L��:�tD�^�]�\\\"���L���D%Ʋ��gL�9il9���78(� �;�w�	��3��\n�\Z]A_9�r<��-���Akq�D� ��m\r2�Ŋ�}An1r�*�Y�6&��Ɩz�J���nu�Q�����M����ϛ�N��׊|�����\\\"�:r4b�Hs:}�,n�.�Ɨ�8��p��s�Qy�b#b�.�\\0���&��y^S\\\\�#�����L��bZ���5m�+��^���8�� A�44d9{<���\nP���K\Z �>�����ݏ=��G�9p�P�E�8.e��q44�G]u��@:=^j�N�p`�d�H���b\\\"���FD4_Ѵ�i�E/e_�χ����\\\'u�S��FP?�p�z߯ٚ}\r9K���e?�ps�4�:(;*b��m%�ƂU,�q��}Fd�?P|3��WZ�8���.���J\nQ�$�8���D�\nm#9��H�D��&rJ������|���	��	C�::�\\0V<�B��L���\r+٪/�����>;,m*�i� �������>���j8�`�U����<��\\\\u>�J}�J��F�:���*�[�%�3��[-�3���Lg��5l�$���e�\\0{��W��4.�n{�6�̔������;¤�AL�n�F}�Y�s��y��xq��1�&�(\\0�_uGL2r�S��P�&t�J�b.MDk�Ȕ��D�&�����R����v�W~ѹ/^Ϧ㞥~!AK������L�>\\\\��S숉�z��\\\\o_4\\\\�x�)��i���mr����>0L�X���&B�s�B�t��,l�$b]��e\\\"����u�6֍��=������LOf������Z�Ӳ�=×mY�4lj�ax��������[������౏��U�A������Ub����b��l$V������UbX�\\\"���ch�l��^{+�Q��\Z\\\"����S��\Z.�����X&�З�\\\\9�a[�3�a�Z���)�S�2	��������)�m�W�|�����ָ�##�p\\\"&�N��Q�7��8R�O>�\\\\��\nW�.�z+09��Ȯ��#ί@�p�6��!K�\Z(��i����V!��h�=���7�[\Z棆�f�\n����6{@��O������4�7Uޟ������\\\"���Çf�f��G�\\0�s����#\r�i�x@����Th҃Ӥ���>������FߠR�ѣ�cOVb�P�:2\\\\���[��K�!���K�.=p]�X��mjY�e\n�mJ�\Z���-�����\\\'�ec)T聨�߳��ؾ�Lk:�Σ4d!ҝ\\0�ꍥȄ�Y-#�����Ӓj]�����K#��h~QZ���:�K.ɦ�\\\\��R�R8�M��R��/�>[�w��^���q�c��P�r�e��l���{�ArּaX�	K�9�\Z����OF�yR����[������\\\\x[k��~�W�\n������l/�^��`{����EJI���5�����dH&r�x.��H������d��2��/`x	!��tA����ū��ã���B��O��䫳�N\\\'8����%~;���k����$�	\r\\\"o�&\\\"iHD)�HF\n�l/�^��`{��\\\"i(��_�4\\\\[fh2$%)�d�;�$[g���ᗐ=�Ҩ�/ReOX`�S�y��[,�5E��*��o�-�ߪ�yq��������Zt��?( ����l/�^��!�}B���ή��R6Uf�_e���97�ʶ����!�����>������;��S�����2�5��f�R�U��\\\'\\\"�����[�h��ag�&��C�|0����Ë��\\\\Y_�k���0f�eYl��(��\\0�S	���e�u�\Z͜�*��b��S�_F#�wwڙ<�l�Ӡ�3��M�h\r�`�4�(j~���y��2�u�{��ZTW�NV��׼�/C�#ѭ#H[ǌO�8.�yN��h���\\0�2���#�����FL<r���^�G�9��6�]o�V�a:L�|�\r@��خ��G�i/�ٗ������O���\\\"һ��ء��`\Z��n�L��c�X�s�F���m�_����%XD��B����l��!K,6_�%ʘ��F���v���H5�3����v��ګ��k��U	�z|?r����iͼ���������6��p�Pב�<u6�&�������d�Bv�ld�Uh�4ϥ*���жMC�����Kü�0�����9����!�%9�\\\"���/]|{^�K��7���\n������{�������=ycPZ����_l�v_^���`8���3M���шa?]Ss*��j�W�f��~��(�s��g\\0�1��G�Y>t&���}�a�gd~����X9�9�<�ï#��`t��j��d^MԙM�m���]��4��\r��a������{��m���^$�?j%�2�U�{��IW5�R���~��;��E*;SD���1��q�a�&�o���\r��������)h鳳+�l���<ßCg�]�����$�>>�cr<�b��K�fF�ɯ�χt���<ם�3qbiAD6�B��)`��==��S�����it�i؎}��wz�\\\'������\\0�w�oj{��ޞ�9W�45]�\\\'�`�u�n�f��,��.o�iv�9g��8}�\\0��-l��(\\\'`q��W�ze۠��D��B}U���\\\"�\\\\rP�w����W��N������[��۵���:�7-�j=M� `d{.d�ߟ�*�5�ɟ\\0@ź�	=;i<�)Nk����ں�����杬�y��j��G��y#{�ł����&�� ,~;$��vG!�����~y:�������|߹���y�[�nV�utd�./��Zp��}d�6���k�|��o�{E^\\\\��;\\\"(i����Bt�D�����I覯e�Ӆt���!\Z��.�>��34`{&}��������)��M����Gyy���Bƶ���彔��!�a���Ji�2;19�O�;A�g��8�V��*\\\"̠iذ��cఈ���B�\nx7�Lv�Z�3�pwnwFg�\\0[���#��ud������a�j#�G4j5=��C\r&�����$����5T�Ƃ�����#�����(0��릃��c\\\\��T*�΄�xv������~���>��m=����%�l��9O�pQ�$]r|�ח�p�v�\r�n!s�	b%��g;��A������P�\n�54���Ǿ�W��A�v��\r�X��/�ރ�|k�%�l�����e/~ӷ~:g��b5�V���!\r8l\r#�e�5�\\\\�ep4�\Z\rl�d�D��Em����ÿ��eB�R�o\\\"���֋Jk�u�=�6X�oC7c7׃�,�a��Z_:�\\\"_�+�[��\\\'��O�����&7�3���,�G1\\\\�؄�G�+���?����w��\\\\V�E�0�>�Va� �z�ក��25�� `Foivt	�\\0�M��\\\\7g�p\\\"&�N?�M�\\\\hq�œ��_.E�a-�<�Sվ�a�:��s�՗��9A֢�7KE�}�6�|�%ޓ��?#�>�\\\'�5K. �Ž�o0�Zw��s��c�x��t��`�;��r����3	�=�.���\n#u�F�s��_��g�ߴ���4�d�,�݊d�\r���.υ��|(�\r[�=ec�U[G�A��M�`R�������뿂����h�]�/i�h.���#\\\'�\\\"g��-����X5����3F)��#����b�2۬�������~FvT��0�|J�|}>Z�j�%��,lQ�W:��8�_�⭰��Ƀ�שnt:� KC@E�!뱒n��L���D������BoǪIV��zt�u���09�ztN@vl��`��#`X��E�a����	+,�B����Dj��}��6��C�ja��QEn?C<�1����6_dY�޿���3�I\ZlH�S��ݨ~[�t\r��R5k�x�NP%���F=C�cL�Fأ�	��$��HE]òq\\0���O�|KǄ�>E\\0A���tgt�	\r��/��j�����bI��`�`����zz��D$Ȍ���|�mz=�7��<���<�����Y�o2��(����N��$���4l2�����:���86UzU��h/Oc�nh�7?T�>���UM���\\0\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0�@�$\\0\\0�\Z\\0\\0\\0\\0\\0word/theme/theme1.xml�YM�7����;�3�X�\r㱝��MBv���<#�(֌�$�	���z)��C��PJ\r4����Ц?���c�l�K���5������H�z�,��	���k׮8���D(�����a�m[��,�d�k�!�����U���BK�glt���^��B��2����.�4�F�\n�)���YM�l+�p{{<F!���K{�p>��_ƙl1=���f��Ѥ&�؜�Z\\\'\\0wm1NDN���-]�Qvu�jui��ے�P�-�Ѥ��h<Z\Z���6���|7h\r���ҟ�03͹��^���{l	�\r���~���K�xߓ\r�@y�����*�%P^�1i�W�+P^ln�[��w[\Z^�����񚍠��2&�����a����P�����3�m���!�CP�e�O��\\0�FY(N���0��ԝ�����U%�A�:o\n�F��c���)�ڟ\n�v	��ի�\\\'/ϟ�z������co��\\0Y\\\\�{��W=������>�ڌge����x�����k��y�����~�Ǐ�p��Q~�RȬ[�ԺKR1A�\\0pD���8�l�g1�6�\\\'\Z��``�����Tȅ	x}�P#|��G��$Հ�����9ݔc��0�b��tV����4v����l*�=2��Ѽ�E�A3�-�G&\Z� �����02��d�\\02����մ2��R�����ȷ���V�`��><ёbo\\0lr	���`�AjdR\\\\F\\0��H�i��q��bb\r\\\"Ș��6�kto\n�1���SI9�����2�O&Aҩ�3ʒ2�6KXw7� ��u��mM�}�t_���	2/�3��-���x�r^]��e����{�Oޅ����Ysw �f�e�ܧȸ��%|n]�B#���v̲;Pl���_���m?�^�W��.��u]�I�����#>���)egbz�P4��2Z>*LQ\\\\��b\nT٢��xr�����F���u̬)a�lP�F߲��C孵Z�t*\\0_����h\\\'�[���c�ҽ���q�  m߅Di0�D�@�U4^@B�l\\\',:m�~+��Ȋ��?lxn�H�7�a$����y��S�v�0��些Lk$J�M\\\'QZ�	��z�s�Y�T�\\\'C�I��~��\\\"��\r8�k֩�s\rO�	��k�ŭPө�Ǥng];�@�e�R���%9Lu��O���(k�������-9��\\\\���\\\"���I��1���UU��N����\n�	�GItj����\\\"P^�&!Ɨь--�U��j��_�V[�i\\\'JY�s�*/�桘��J�/&3�e�.}�^l$;J���\\0���Y?��!_b��}�U.��Z�)�n�)q��Dm5�FM26P[���vx!(\r�\\\\��Έ]��VŽR�6^O��C����:Ü)��L<#�˹��B]θ5��k?r<�\r�^Pq�ޠ�6\\\\����F���Fm�՜~��X�\\\'i�����<_�}Q�o`��}%$i��{pU�70���70�yԬ;�N�Y�4�a���ڕN��U�͠����>��v�F�6�J���H��N������������y�]�W���\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0��\\0\\0t\\0\\0\\0\\0\\0docProps/thumbnail.emf�\\\\}�\\\\U�?��䛐I�6^�����$!h%�=_a�|1�!�(әi2SL�Ǚ���(�J�E�w�pw-u�����,,n\rP��`����+��R������������&�����ι�{�}��(����ו����i?Q���N��%�g�%Ns- :]�u�������tHL�\\\\Gt/���-��0�x������mF`ʙ�+����Mo���1\\0-�PtJ��nsu��ٴ�﯅������i���C���~Aq���\\\'^��h���������mO�������Й>.=6f�KiUs�x���X�3:������K��{�w ^T�r�زt�Hj��2Q]�ܖ�$���f�J�W\Z��z�(�jP��(�pP�g;�sѮ�wv�z���mQ�����)���2sK9-39�௎s�+�������2���i-$�tS���Jݛ-�U�J]k�T�~�͍�nnlܴ�i�6�l	���Y��s|�.��˼�O����iS=[L��x���C[K��o{�ђ��ݭQ���\\\\GW[kGV$�n��|�o�4�(�7%��c|�ƃ�`���z�1�vz����mۣ���ܞl_[��vE[gOoW[w��SD�0rl�),G�]ay��|����M�iƹ���wy{�	lۣ�mFnI5��w&���1�=�X����\n���i�������E�\\\\G��eƒ�\\0E�HE�Hl\n	5�]��9��E�WS���7ok�Y��z/e�q-[|<p��}wwk��zڣ�Ξ�]-���T:��B!�:�Ě�cu5e�e�`���񳽽�-�\\\\GO�	}M�@9>\\\'k�gg5\\\'+M�Ky�:�W[TY;��Y��-��i�rɵ�um.k�r���٥��b�\\\\��f���`�\\\'p\\\\��N�ٝ/��6��.^m�~V6�`z�a����D�rWGKtU[�ݎ�}e���J�$~\\\'���	.��sa�ߕ��\\\\����]�L�\Z��:��9���R^bu%��ku��:��XR�����b���\\0��ʺ�����#��,�~}W�o��=��Z��{Â�ϰw�R��JX���|&&\\\'�z�/�:>(���P�3��������-����C�^�:Z�����vR���;�A�H-[|n�����yN����ͮh�{GD�[C�Bm�����%ki����c̿�>~\\0��Bs�\\\\��e+�>�#��+��?�^�p.�ޚ���W}�S믢�&>���WR���ζ=P��w�}54�4:?�P�5&IGċ�VzDylQl�r��z:�t�`iaɔin�r�\nQ��B��İ�H֞�:z�����\n��!�ӛm���J����S���Hqlrc��ul	S\\\\�k-��+b�zfF�ŎugEL3X�?>5:Y�8ɵi۶�+۷������k)���%HT/14��j/{wxc��~f9	��R�x�n�[�5Ԟfj��9k���K��dT\Z.D��#c����xq���\\\'����&��Ç\\\"��;Z�np�[]Y\n��E�=fi�yƾ���).�z�D􆹔2|��	��F{!��Eߺ�t�\\\'&�j{z:�:t��a+s9q��B��=�ͪ��3��`���\Z��l�Z�1����h�T`�^[�؟���ʷ�p\r �����\nS&6G�Z�Ƣ��ʗK�hz�gX�^����P!\Z+&%T2C�*�99�qQ�g~\Z���(�fz�g�:�Z�����D!ʏ\rE��Ra�\r�KyCQ1A�].�Etϐ��`�S����Y�L���֍�Z�h�`T�ȏM��?3ZSƉ\\\"X������Ķ#sv��<���6Șw8Զ��;�����c����ha(�{(*��O�ؗy���Kl�\\0�����+lO�%���r�,_\\\"V�`~d�@�����Ľ���/0�y����`����ɭ,	{�ީ��#�V����ք[3��ʦd���GtW��މ��RA�GV8�����d�0Ø8G���<�;����$2�����M��:���N�H�	���b.郕ť��b�~���*Ka_�Ό�0F�S�}:���������h�Y�`�R�hp�����&�ѯ�aB�5�h�R�`����1����b�+\\\\�����b���a=�\rO\nQA�JԐ�5o�Z64F�l�p;��CV�q������:�M�\\\'Q����Cʙ\n���!�Z(\\\"�S99�h�R�]h�X}-�|��{i8L�.�c���)�ؾ��6\n��F��ٙdq������AVޡr����R�����u������5^E�V��\\\'�O5���D%���D��ԕ�K�����VyNW-9�Z�R�U�lnjG����}�i�J5��jE�պ�V]��J���n�+|��#����oj�T(�!��U���+\\\\eh��s�д͙m\Z(�����%���nW�6�]��>9�\Z9)�\n?���ev5\\\'lە��:w�?p��� ��{t������\n�2�d���Z\\\'�jd>A#�礑r��g��\ZP�����OY�̌H\\\"�Ra��Ri���ȇu�Lx5���̌m��?��j�p����!��X]\Z�n�\\\"�>Hl��#�tC��x)�Ӄj/�ύ�q鈟ȇ�B�Ol��6��bNfڅ�ÏΠ��Ѻ��k��։�N�ֹ��=*,\Z9`-��Ħ�c��Z�S�tE�t�O��\\\\�w�,�v*�����-�k�U~�Ŷ�a�Wcu5�݄��|�z5������D�;K�]�9�����6;�Z�Y=+й:[��M�7��Y�I�\\\"SD�z�|�q�~�a]K~td�Ĉ{.��FÕw���-�1��Z�r�s����ɝFu�\\\\SFr�khN{$Nϯ+�6�v�sR{z�Sg۝WW�5�v!�s��+.�}����e��9�ĕa������>����~-ןii�ͩ��x ���o��%�_��`�C4I%*�~����`�#�L����7`&���lk�:���gQ����)�z���\\\'A�-���E�_���M��>��+�7pz�;��yv�k�4p��A{}��p�]��/��)�?��|s������G����_+�5t�L��D����u�ʴ��9q����A*��u<y\Z��zh�����ss���c¥�0��N\\\\�ګ�?�&m��g���ӗ��Z��i�A�O}����2��<$�;r�O��l&�W�<��ȣ*�H�XH��n��\\\\�L����p;i�u�x��\\\'�K/�#n\\\\�iƽ�dܿ���qo�e�Wub�렼��?dOct8�e��W�٧��g��z� $����_�q�����اM=�\\0�-�F�q��qQ]V�����l������g��}��^��� 䝔��GIt�K�vq��l;W��t%�����,uP7�VD�8�NN9�U�h���6��8�O��\\0�B{�3V+��(�N�r-�>��e��|]�Lt�W{X�Wc |�����$A����\\\"��x>�>׺��&.�Id�%���tU(2]I�2�|+�<����>Z5�&�\\\'�[���6;.b��S�M�k|�����O\\\'����g��?d�l&��L�o_ �18��q��m;�ƀ{äql��Z�U���\\\"�0<El_�,ӈ�h7��qy�l�;β�6����a�5�	m0�Z�m�6��\\\'�����AY��)�iY���W���v���㴚��� K���I���۹l��_�\Zx�N��5��+�8.G$�SY�l�m�~H�k��\n�<I��������;�}�e�	���M˂j�O[�I����Y��Q�B����ު��끖c��e��C�qaP���$8�[>GI�[���szcl�kH�A���.ֵVc�����z�ڊ�5��薫�*/�GuN�0����]����������7<|����;���_�Ü�S3�tm6�]�P�	6>|~����rX����L~��e��=a�Yn���ll������F=Q�>\\\")~�Ů�Z�s���E۴:w;�M6p���G1Yp~M �&�b��Y��^n��&k������S�5�W<+~��H�����,	6�%a���!��Lz��n�zdri �%�:�	���|�q����^��w�oq��F�w9��5r����ߏ��_�8������őF�q�Q����A5�_Z���D�J�\\\\�46�q\\\\ق/��#a<���wm��/��!�]����~�g�����p ��w��{|�2y��va�X#���Ft��`�����	2W��5U���Q}r�5�5^����@x�I��O2D�o�?��ǩ,�n}�i\\\\�����n�wUJ~\\\"�։>�������������	xO�ײ���)���\\\"��/3�?��@/����1�?��GI�<T�y��\\\'ṁ�7���|��C�L�#��]�증��\\\'�`��i����\\07�1��M�C�������{x�L <��?�M�ɪ����c�&ч���_����`�\\\'4��h�n�×����D\r^H��Q���T�������k���)��=i���@x����C��f^�G}!ƪq�\\\\�����s�5�⥧P���-����Z���^_x셗��R���i��/_վ�N�_@2��{���6(�����C���}�H��Gp�ğ�_�C�3�z(�7�|<fx�6�������<6��,�<mL��b�]n����d��>�q�{��ز�1n��Zϸo��qu���I�$l7����N��������;�=�N�q�4rHc/�EǕC��ȯђ��?q�78=�����%}=~\\\'��}���<�1��3Ow�i���_����a�����\\\\t��9=E\\\"�(��|�3��fD�9�����3\\\"|OKڃ_���s����Z3|%	^��m�8�n𜄻�����\\\"�IƇ����c�ű��q�o`����Xr����6c��ȥy�許]�\n	�^ܱ�C�r����~s��$�A�G�j߁Ǚ�W������3��	�Ԣ��M������nc[��;?�\\\'���;�UzGr��s���B�~������g��үȎq2֡��Lǰ\rp�2\r��� ���c�\\\\ ؖp�;ù�jlO����&�e#��C��q�4\\\'�����-��GL�m���\\\"ף�H���ѭqn_�}��s~�h����\ZX�V�q�(���Bu:�6�N����L��\\\\��U�T�TG��_i���ڮ�N�Z�v��j�Ꭵw�?��|��Xs��9�ʜ��\\\'`𭋠�/z�_m�ϴ.6�V;�<s��3e<�A=����A��n���Wq<�����m;�=ײ��I��=#��.���J�$l7�r`?9���#������k���$u�Q�.�Ψ>i9�p�pe�r�����$�{{ |�y��{ �f@7ɿ���\\\"�wA�{���ȷ>#��4�(��3E�8b9MnҌ铧k�(}�Q-�������(���S��-�ߒ�k���\r���z.;���E�����z��m��78�����A��v�-�,՗�~Ve�z��E�Wvc\\\'������!�3��39̷��G\\\"�0������wdf>�m��?V����k#\\\"ͼ��Ɲ�w=>�kp�L�C��;/���\\\'[��^/��ف�F����س݌�3�����vP2�$�g�xf���3��E�4\\\"��cf��fP0����2���g���������غ~}�}VGx��h��(Z��E������K\Z�([ǋ۫bQ�H���i��s����C\\\\��K�W�׀.�#������I�h~���x�v��|~��w\\\\Y!����|��ǎnM!+�kzy�����*u܍��,���=	�G�\ZϽ$x��j6�k!��KL�!�U�ឌ�%�?zYcF耱�B�:���6;���uL�Ba�H0�E�軖d.��4�凯\\0=N�8���3m8h����\n�I��-��_Ju�x�8pW���q�ŵ��qA��Ɛ4n��6lf�������<�����x/q��tg7�t�JN�V0����a�<�>.��8�^����~���@hc����1>`�h�1�1��`�+�����	�!���ﮌ8M��?VE��\\\"K���6�Ը��Ys��l>[�����Q��2����-���w̳�=�!�����\\\\��*Ww���O1�N�Κ��Q�y���H��^������m�$��B�o��z�ki(�4�?%�=�\n1�Tl��oW�g8��b��Tv��\Z��3	�Cf�O��է����\n�b7��W�,�� o�mTN�9�y�r7��gW��������h���1�^1�N�:�:�vn�����e:�;��ؕ��TZ���Do:y������=$p%���̰���H*����:��86�\r�Gۯ=؎Zl(��3�#�9p-�ǆ�\nr;�c�+�?�[WXSű\r���}�^�oS�\\\'���?>�x�k�),\Z>��^uQ��k�5	���L���f�Q�������)�7��6]�ڏ�D�J��S���x_��!�cM��\Z8����}�(	o�5����G�O��U~_VG���(�SC���qh�=�Ƹ��i�Ε�W���*g���b��y3��?��\\\'��\n�������E�O4���~��|8{?�L\\\'�O�@��6�\\\\Tc��f�}�I�t�����h���M�joH��������:�~l����O��\ry���P�M�A�_��?��<�q���箰��A�˙Tۏ�\\\\�Ҕ7����Q���\\\'_�8��y�=���nL�q��GR��1�&���\\\'�v�����t�Y,>���\Z��|1N��Y���G�������:K��i���D�eo�6���+��3�g`&|�*�	�iy>Y1�C�g_�a��M����-t�|�(	��\\0kP���1Ff��%�-$=g\\0��y���hǝ��\\\"-���{�O���`��E�06��I�cx9%�-���\n�\r9����`�CX+�J�����y���j����B�p5�cC�\Z?3?����u>��\n[<<�jyH���5�����%Ic�X\\\'��_�|_����qW�qg�}�X��_� i�H�g?l�|ۃ�&�\\\'�����Z}�{��:���n���w\\\'��\r��\\0�W�ߜ�_��^����]��a��]�;9gQ�W�8q��N�ד�ߝh{�}Ț�*��;���#K?r��7t�\\\\��^/�=����Y�!K���?d.���C+��eO�w����*���}����Z(���t/?ܒR��k���t��j��?y;�Q]��)S�o�K�6��#��S��9��Ň)B�z��s�7f���푔w���5���&�_ᔳm�e�������<�jb�<�J�n_�y���i��s���x�6�\\0\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0�B�\\0\\0�\\0\\0\\0\\0\\0word/settings.xml�W[o�6~��`�y�u�\\\"4-l�ZS�kP�?��h�)\n$e���wH�q.Z��蓩�;υ~����Ixs�x�3�M�k�쮜�w�4u&R��F�7��9a�|x��o�J����Ff��r�J��l&�=fH^�7\\0n�`H�����];�8k�\\\"%�D�f���Π�_9�h�AŔ�JpɷJ�d|�%~��x��^$�U�p��ř�|��ܓVZm��jpo��āQ�w��7�{�~�x�{Z���R�1j$��p�Jѣ��=�hT����SϣS�RK�c*�A�L�~��$}KJz膔����|�*��5\\\\���;��	�61�9�ʿq�&ǬŢ���	\\\\g�����UNdK�����w�%�`i`�?�nR�e�)5-UQ���1�	Ġ,�W�������[`: .���	T),6-�@ے7Jpj��CKh,��KHt��>ޒJu��L��O���AQ�d�Yw�y��Ý o�.-`����QC&� 5���ߨ�Ĵ!��?uR�hz�\\\'<���і?C�ܝZ\\\\`�s$�1sA%���uSCE�2cd��(�5T�h���\Z�Oڝ=-#X���/�+��A�Q�{��3��<OF?)�tY��<C�4\\0uc���^1���]�a4��$����Qި�$v�h5��^���E�C�<G.�8�G���8rGs�X���C����r��;z���Q;�y��Qd���h�E��C-�@\\\"/OG#-b/_�G�<�ˡB��d�^η����^b�X)�����i�R�/Hc��\Z�O�MWZp:���g��aY\r�!�[s�k$vg����\\\"���K�,��k{�(P���� I\ZuC��ˮ�X�����0y:��)f� 3L/n�_7}�+*6zP�5j�~ޔ;�ʡd�W�\n�`ޛ�r��o0����td�=�4�Ҟ���i���gZdiљ[Z�i{����=�>{��-��q��\\\"�I�{��_�P^�\\\'�[N~������sKj���\\\\?��7�\Zx���jL3��5�H!;t�	��~DT�qsb��mp�;N��E��3Bqa�?�𢩮���d�I�y\Z�]�E�������޿��I\\\\��zѿ����cw>�L�x\Z��t�&�7\r}7Iҹ%����I�����\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0�0ĩ_\\0\\0X\\0\\0\\0\\0\\0word/webSettings.xml��O�0��\\\'�����8�+\nCL��ib�H��f�\\\"��_?7i��i�Cy�S.��7��X��8�x0:���)h爜b�ȶ�F��9�y{}�Q�|�6��V��F:tq���Y?����>\\\\钠Һ���h�}7KSW����)t�\r�K����ԮRS�_w�I\r���Z(��&�h\\\'cߢ˥���wF�~�O��AZ�V�۫�oQ��6��Z:�c��g*�>ɐ���Q�K\Z&��h�\n�����8�J�p�8	��H�����z�eՂ�:(�)%!�dF�i�����3��/-�Ȇ�4��a��a��t�\r<�ʥ�{��F��q�B��y	ރ��Ҹl����1mX�(����u[��j��k��Ou�a��/2;.rq��q���̏	M�\\\'=��4����c8ʂ��8��##</��\\\"���9a�8��#���qLG�p�Q�ˈc\n8JV�>�g�4��0�J��,>ZM�%ex�eY���c�ܧ���!�g�2\\\"Ʒ�X�w�>�xY\Z����ߋ��ÿ��������oM��P\\\\Ai�����d���S�Ǐ����[}����y�����λM:��z��`/-�N�!�Jk��<�������\\0\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0�z<|I\r\\0\\0w}\\0\\0\\0\\0\\0word/styles.xml�Ks����w`�flY~ֻ̺e{�ص��;�d�	YX�����Χ\\0��&(6����ˌE���o������$~�,g<=�?���<b��������O� /H\Z����l�F�ѯ���/?�N��-�y \\0i~��g�EQ,O���pA��K���s�%�?�罄d/��Cȓ%)،Ŭx�;��?iLև��s��<,��~/�� �4_�e^�V}h+�Eˌ�4��A\\\'q�KKט�!\\0%,�x���Gq0�E\n%����$�\\0�p�\\08�)q�{�[B_GA��>�<#�X��!�U��~ތx���I���=f�������i��S���=�VT���<��H��$/�sFZW.��k¼0_��������?H|6:8��\\\\�4��$}����÷��c�Lp�F$�0=��{������]n�R;^�����yAE����%4fRG?�?����IYp���_c�@����<�D%���_h4-Ċ��ڗX���1c<�9���)Ni�nX���0]��~_��[N���߯U��!/S���d�� Σ�א.���ڔH�<H�Xn]��Ε��j�X{��~A��\\\'�x���BH��8�vf�u�j+Ԏ&ﵣ�����{����vt�^;��^;R���X\Z��J�p7���cQ#�c�c��c�\n�cQ�c	t4��h�%L����(4�}b��n��1�{Hp��ܸ��ww~w��N�n���ۍ�;Y��T+�2K��*�s^���AA_��H*X���Ó�ͼ�L���@<��{w�(����,�>���Dm>��4�AcQ%$��#0�E�Yz�%�3:�MC�3��Ae%�e2��K��E��s��D/IaТ~^H�0A��0�ÛƉ��p���}%!�E����O�)���@a��\n3�2P�ᅁ�3_]�i�zJ�<u��y�*>}���y�7M��o�6�ߞX�o�:����]�\\\\�܎){N��\\0n�9���d�9#�E �J�c�c���Go���1mM�5�W!r)�����m�|�k��$�5ϓ�ּ���d9A��S�L�Y�*ZE�%�)��jB;\\\\m�a\\\\�,�&�v��~��Y�N�o���\r۰��j;+ym�Fzhe��?i��mI3Q��&]�8�+\Z�#N��W�fJ�@���䯒��L�J\rD�����ܓ��z�	K����CBX��A�<��O|)�L�1~��(x⍩���;���O�E��y:�sO���yd*�<��4�����x�ѷ\\\'Y�������z\\\"NI��&�%��J��!��\\\'ɘ</�KTO^`�iü��A���^�})u�QMu��?��iB7|���)����~�\r�����I�3�%Tg��íy��wx�y<�ټ��u`\r�փ5�[�L���+��V<���1d��)9��G�\\\"o�P0_�P0_nP0_>P0�~��~��~�N�40`�������*��g\n�+��W�)��8�|�|.&����+����&-h����<!�b�L<� �h��ˇxZ���)�Q�\\\'�Η��ә��I��vy8#J�sO��6��l޻��L=�1�	�1	���,�d���z,c����N{ޱ�EL��&�x�e]�7�vﰭϏ��Y���i�ʤn(|��x��XEt��p��f&Ѱ<�i	�y��r3KnX��������R�a٥��${i\r����Y�x��;銢�q�n�im��\\\']QԐJp��j�N?������n�Q�������[WvD����L�오����{�}5��9/yu޾q���C]�b��4h�L�_�jd{?�N7vD�cG�N@vD�Ld5G�$;�wn�#z\\\');������V�����K���l5``G���h�BZ�f\nvJ���I���*D��\nh��	N��\\\'Th�\\\"THq*���\nh�BZ��*D���8���;	R�B��P!-T5_ Th�*�w*��R�B��P!-T�@\\\"�B��P���P!-T�@\\\"�B�5t*��	ڻR\\\\�\n)h�BZ��*D��\nh�BJ���I���*D��\nh�����\n�qB��.B��B\nZ��*D��\nh�BZ��*0w*���\nh�BDW|�K���������;��_�ҍ�j>�m�&�Qu����\\\"\\\\p��>x8Q�F?�Ō�SԖ��&W�������	�>�K�Yu���Z�s*�]!oZ�\\\"�+�MK0�<�ʾ�%����e}S���qW�1���lm��.��ц!���l���ǆ�Q ���Q�~:^�_\n]�hN섮�����1F_��	}�g\\\'�u�����w������j(3��݅j\\\'`]\r	N�wWC���!���01b]\r	XW�\\\'g;���\\0��j�rv5D��\ZeXWC�Ր�u��يqw5D9�\Z��\\\\\r\\\'wXWC�Ր�u5$8�\Z`�]\rQή�(7W�*�jH��\Z���\\\'W���!�����ju��j��\rs�$�0�\rȆ!.9�Ւa�X-�j	���9�Z2�f\\\'�����׍vʟVޱv��v���q�R��݅j\\\'`]������UK���UK���UKvW㪥6W㪥6W�\\\'g;��ոj��ոj��ոj��j\\\\���j\\\\���j\\\\�������j\\\\���j\\\\�dw5�Zjs5�Zjs5�Zjs5�Z��\ZW-u�\ZW-u�\ZW-�]����\\\\����\\\\����\\\\������UK���UK���TK{���$[}�Ll\\\\�-�|���LT��T_T�F�%Icْ@�J/V\r��=*��Z�����;M&~�y%���i�E��y*_�ײ\\\\�®^^��rA�j����mt8l�eu��Bի���N��:���^(]>���e��Ki�~m��5�o�=pp�����j|fK�~fkuʫ�\n�����Ў�-������#fr�^&�7�cְ�|�L.�X�,�QX������B����Pq$*>U@���	����/�MZ��V-3>h�Zkh�����B�A!49ܟ���/��q2�W�Q�<��7�¬?�Vm�~��t`U�/����\n���Kh<�U�\\\'D=���&]�JU�k�.�R�{�u��X�4\Z�S�v�H(�FB��6K���`^?D����<�5[Bg�͍�v��.:�f!~G�ՀЙ��1�\Z�:dw�P�gW!��MeL�t�Z\Z��\n%�_�8�\\\'��|i�4�s��W2��+;��Ϫ�OZ�35M�����~v�I�=\n}��u@�cqKw�����t��\\\\t�\Z׷���Tۭ�+��j�ȶ2`���pG.�����v�~��b\\\\��.���o���m���Ze��tf�9�^ێ�^���?��g>�x���5�Y-su�Lp��췝�J��j��\\\'���	S��J�\r��\Z���^����U*�s�\\\"�y��S���>^�\\\"Pk�*�5�/./?�,`��K^f�f2J�Fۖ��4s�v��.oDY]�`���!��߀�R�Ӑ�\\\'m=�4�*�,���_�\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0Va�~\\0\\0\\0\\0\\0docProps/core.xml �(�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0���O�0��M����n H�1��$���o�=���M{0��`���ow��_�צ��\\\"v`��jB�^DP\\\\�V򼘅c8dJ�\\\\+��=82�./Rn�-<Zm��x�r	7�F4	����`��	�Km�>�+j߰�~�h�CF+ahZ#9(o�fk�Z 8�\nP�h܋�E��;���� �{g�c��?�l��,{�F}�1}�?<�W\r��fŁd��	J�!K�)��۾\\0�f�M|�-0�6�o7�nW��b��RZq�p�j��T�����V8o�dต��6�t<�3�s��K	�v�����j�����K_�H����7}��Ԓf������~1#Y?��8��E<H��I�U�v���š��G�q0�\Z��fZݟ�}\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0�Ur�\\0\\0M \\0\\0\\0\\0\\0word/numbering.xml�ێ�0��+�P�^��@*[�n��j���>�	�D�X޾>$�1A�,���|�d�k�xEYg	Y�<��{��@�i��C����.�:9x\n2���Z���������\\0/�2��#4p>X�xh%�Ӂm�q��Q\Z3����	��l���^6�=�u�_�������V!�6S�2��Rз�0_7\Z��H`��hW��\n����\\0?��F��,�Х���4K�ZH:a)C�ւ�A!qW�\\\"C:��QF�&��!�$^ ��z��`&r 8ORZ�):WML&����e�u�v�Oe#�$��(Q�3?��:\rNDJTMR�~g�	)޼����gs��L��sh&v�F�OcE��N�#�QK۩=�JK6,��Z���]2�@ŧ����&��H�}G_G�@G~%փh�`�sb�c�:[���CK�e2`P�b&u��8㐍/r�T�y:�K��ny�n�Y��A�����f�k\n�5�z���w9��9��#��+�G7\Z��g���H�C\\\'5�4���#*D�r���q��jpA)d� �W�����W�_�r4�3���O&)��rxh�<�N�\\\\�]�Pel�m��K���Ո�{�y.�\\\\ь(#��D�1���g y{H�6H��0Q�K�I=���Hn�\\0�����D��8E�(�]����(:���]`�$�@\n�Vt�y��ݝ�Ш���]���眶л^�E�H����)�Z��͋.�wZCM��[W�T9��e��n�G��N��P�u�E}oV�P�~W�O��]��N�r�x��bJr}���-^�\\\\�{ofN�*gPO��8\\\\��G0�U��w�T4�զ�X�S��U�ma:.���^��U����!�0�A�a���H=�Yi�����&\\\'p�[��mcf.��̶�N�n�sa܃��W��z�=T�kh��Mel���g��@㙛 7���3A�����	����	����	���w�ae~p��9���dJ9| ̫�u���.�a�����_\\0\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0�t\\0\\0\\\\\\0\\0\\0\\0\\0word/fontTable.xml�]o�0��\\\'�?X�o0��Tj�H��M�����8!���\rI7eђ��f !x��p�����h�-�K��F\\\\3S	�.���#pTWT\Z�K���?�u��h���0W�čs�<I�5\\\\Q��k_��U��G�N������R\\\'VB\n�O2Bn񀱗PL]�?�Q\\\\�8?�\\\\z��Ј��Zgl�Z�8�_��=OQ���4?)��S��_��QD��)�wJ���\\0�	��u�b@$�W|��b��km,]IO�KB�+�x1|L��5U��H�XY-�x�k[*KL2�$���3\\\'�p�I�\Zj�H?��rM����\n�\\0��p�9�[jEh�/�X��V��O9!��r�{%���䓇A�»�1��Q!Aa�Ӟ�\\\"�8ƿ3�8q�E(�+�гQT�q$#�މ���_刍ܫ	�?qd2-�ő!�X7�lBB.���<��܆��qc�=�E7B:��P���O�ŎW�g#��Ƽ�4�o�B��^���SG\n�_�^	gL�?�/��l:y�������A9�	��͓�?\\0\\0��\\0PK\\0\\0\\0\\0\\0!\\0����\\0\\0`\\0\\0\\0docProps/app.xml �(�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�X�n�F��?\\\\h��(Y~������cÒ�nG�49Č�(��F��/陡DS��(]��<x_s�>�,Z��R��N����TgRM�:�7��;d�P�(��Βm�<�����芍�l	,�=�L��N�ȦS.��bYa%צC3�t�˔�t:/Y�h��;���c�q��jvj���_�f:����e~I<�*������f\\\"i\\\'��,9�`��wb�69������lrxtG5_N��������[�EU2�Mndj�չ�۠.yq��Ä!�s#�2��Q{��\n\Z�⨦��#��MN���(���KX�䢰G��[�d��\n.��S�\rY�	g�ߡ���}v�Y#�r�z[=tQYg��tx7�@���i����[7ּVZ��`?��?���7����s/8c�፠E틖B+����_������M�������������T8��ܔ�RQ57��L:��,+�ù.	K�B��t�]��j��ff�*`����*9�%�Z9���3\Z/	��p����w�0XK��ކ���E/Ĳ�sK%C���ܕ�~�.�b��Uŵ�<�lelS#�P]����5MuY�\\\\�شA��/����K4vJ�x���jW���S�\r�Y�t������Z�#��.!���x�N̘,����(d&��\r��pr�z�x�Y)⏰��p�\\\\ >������kwlv�&����[��3Ι3K�Q�T�x׿~�^�Fz�k�fB������L���k���\\\\�^ӫ~o����cD`��I��lh<wT_\\0k���J����q�����L�����s~���r�o��d�U.p�s���\\\\���z�\n�k%\\\'C�׳*��[*6�D�����kkKY��,�-(�	��<�xƘ�DYzHz�J\r�ŕ�����o��Gػ��`kG�g.��������S�o?�����\Z�˒�<g��cvO�\nӯkH�(�r5j��D�w=?\ZB�7K/B!���d7k-R\\0\rb�EN:�	�]�\rԽga�O��q���6H�%B�Ԏ�k����_l&\\\'�~����ю�E�l�<4�(��\n�|_�r��\nV�\n�|��ȉ/�0cC\\0�и��-�]�/�V⾪��,����SJ�(�n��J:\\\'��\\\"C��~\\\'��ɦ���D=��\r�&wf����-���ݜl�ޏ>�D��x�`o���[K�0�Qh��Ƹ��ߢ)2�pY�D�=�^�}����I�?�����z�x�%#�\\0\\0��\\0PK-\\0\\0\\0\\0\\0\\0!\\0�P�+q\\0\\0�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0[Content_Types].xmlPK-\\0\\0\\0\\0\\0\\0!\\0K E��\\0\\0\\0�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�\\0\\0_rels/.relsPK-\\0\\0\\0\\0\\0\\0!\\0���\\0\\0�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�\\0\\0word/_rels/document.xml.relsPK-\\0\\0\\0\\0\\0\\0!\\0��GV�$\\0\\0��\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0 	\\0\\0word/document.xmlPK-\\0\\0\\0\\0\\0\\0!\\0�@�$\\0\\0�\Z\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�-\\0\\0word/theme/theme1.xmlPK-\\0\\0\\0\\0\\0\\0!\\0��\\0\\0t\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0+4\\0\\0docProps/thumbnail.emfPK-\\0\\0\\0\\0\\0\\0!\\0�B�\\0\\0�\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0Q\\0\\0word/settings.xmlPK-\\0\\0\\0\\0\\0\\0!\\0�0ĩ_\\0\\0X\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�U\\0\\0word/webSettings.xmlPK-\\0\\0\\0\\0\\0\\0!\\0�z<|I\r\\0\\0w}\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0CX\\0\\0word/styles.xmlPK-\\0\\0\\0\\0\\0\\0!\\0Va�~\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�e\\0\\0docProps/core.xmlPK-\\0\\0\\0\\0\\0\\0!\\0�Ur�\\0\\0M \\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0nh\\0\\0word/numbering.xmlPK-\\0\\0\\0\\0\\0\\0!\\0�t\\0\\0\\\\\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�l\\0\\0word/fontTable.xmlPK-\\0\\0\\0\\0\\0\\0!\\0����\\0\\0`\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0\\0�n\\0\\0docProps/app.xmlPK\\0\\0\\0\\0\r\\0\r\\0E\\0\\0�t\\0\\0\\0\\0',7,'published','2026-05-10 20:04:23','2026-05-10 20:04:23');
/*!40000 ALTER TABLE `review_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sectors`
--

DROP TABLE IF EXISTS `sectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sectors` (
  `sector_id` int(11) NOT NULL AUTO_INCREMENT,
  `sector_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`sector_id`),
  UNIQUE KEY `sector_name` (`sector_name`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sectors`
--

LOCK TABLES `sectors` WRITE;
/*!40000 ALTER TABLE `sectors` DISABLE KEYS */;
INSERT INTO `sectors` VALUES (1,'ICT and Multimedia','Information and Communication Technology, Software Development, Networking, Database',NULL,'active','2026-04-25 12:41:09'),(3,'Construction','Building, Carpentry, Masonry, Plumbing, Electrical Installation',NULL,'active','2026-04-25 12:41:09'),(7,'Business and Finance','Accounting, Marketing, Human Resources, Banking, Finance',NULL,'active','2026-04-25 12:41:09'),(8,'Automotive','Mechanics, Auto Electrical, Panel Beating, Vehicle Maintenan',NULL,'active','2026-04-25 12:41:09'),(9,'Fashion and Design','Tailoring, Fashion Design, Garment Making',NULL,'active','2026-04-25 12:41:09');
/*!40000 ALTER TABLE `sectors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_enrollments`
--

DROP TABLE IF EXISTS `student_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_enrollments` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `completion_date` timestamp NULL DEFAULT NULL,
  `overall_progress` decimal(5,2) DEFAULT 0.00,
  `status` enum('enrolled','in_progress','completed','dropped') DEFAULT 'enrolled',
  PRIMARY KEY (`enrollment_id`),
  UNIQUE KEY `unique_enrollment` (`student_id`,`module_id`),
  KEY `module_id` (`module_id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `student_enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `student_enrollments_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_enrollments`
--

LOCK TABLES `student_enrollments` WRITE;
/*!40000 ALTER TABLE `student_enrollments` DISABLE KEYS */;
INSERT INTO `student_enrollments` VALUES (1,9,5,'2026-05-05 19:41:31',NULL,0.00,'in_progress'),(2,9,6,'2026-05-05 19:41:31',NULL,0.00,'enrolled'),(3,8,2,'2026-05-10 18:12:45',NULL,0.00,'in_progress'),(4,8,5,'2026-05-10 18:12:45',NULL,0.00,'enrolled'),(5,8,6,'2026-05-10 18:12:45',NULL,0.00,'enrolled'),(6,8,8,'2026-05-10 18:12:46',NULL,0.00,'enrolled'),(7,8,9,'2026-05-10 18:12:46',NULL,0.00,'enrolled'),(8,8,10,'2026-05-10 18:12:46',NULL,0.00,'enrolled');
/*!40000 ALTER TABLE `student_enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_progress`
--

DROP TABLE IF EXISTS `student_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_progress` (
  `progress_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subtopic_id` int(11) NOT NULL,
  `status` enum('locked','available','in_progress','completed') DEFAULT 'locked',
  `viewed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`progress_id`),
  KEY `subtopic_id` (`subtopic_id`),
  CONSTRAINT `student_progress_ibfk_1` FOREIGN KEY (`subtopic_id`) REFERENCES `subtopics` (`subtopic_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_progress`
--

LOCK TABLES `student_progress` WRITE;
/*!40000 ALTER TABLE `student_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_resource_tracking`
--

DROP TABLE IF EXISTS `student_resource_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_resource_tracking` (
  `tracking_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `resource_type` enum('notes','video','screenshot','guide','exercise','link') NOT NULL,
  `resource_identifier` varchar(255) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_spent_seconds` int(11) DEFAULT 0,
  `completed` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`tracking_id`),
  UNIQUE KEY `unique_resource_view` (`student_id`,`topic_id`,`resource_type`,`resource_identifier`),
  KEY `topic_id` (`topic_id`),
  KEY `idx_student_topic` (`student_id`,`topic_id`),
  CONSTRAINT `student_resource_tracking_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `student_resource_tracking_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_resource_tracking`
--

LOCK TABLES `student_resource_tracking` WRITE;
/*!40000 ALTER TABLE `student_resource_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_resource_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_review_bookmarks`
--

DROP TABLE IF EXISTS `student_review_bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_review_bookmarks` (
  `bookmark_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `marked_important` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`bookmark_id`),
  UNIQUE KEY `unique_bookmark` (`student_id`,`review_id`),
  KEY `review_id` (`review_id`),
  CONSTRAINT `student_review_bookmarks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `student_review_bookmarks_ibfk_2` FOREIGN KEY (`review_id`) REFERENCES `review_bank` (`review_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_review_bookmarks`
--

LOCK TABLES `student_review_bookmarks` WRITE;
/*!40000 ALTER TABLE `student_review_bookmarks` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_review_bookmarks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_subtopic_progress`
--

DROP TABLE IF EXISTS `student_subtopic_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_subtopic_progress` (
  `progress_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subtopic_id` int(11) NOT NULL,
  `status` enum('locked','available','viewed','completed') DEFAULT 'locked',
  `viewed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`progress_id`),
  UNIQUE KEY `unique_subtopic_progress` (`student_id`,`subtopic_id`),
  KEY `subtopic_id` (`subtopic_id`),
  KEY `idx_student_status` (`student_id`,`status`),
  CONSTRAINT `student_subtopic_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `student_subtopic_progress_ibfk_2` FOREIGN KEY (`subtopic_id`) REFERENCES `subtopics` (`subtopic_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_subtopic_progress`
--

LOCK TABLES `student_subtopic_progress` WRITE;
/*!40000 ALTER TABLE `student_subtopic_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_subtopic_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_topic_progress`
--

DROP TABLE IF EXISTS `student_topic_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_topic_progress` (
  `topic_progress_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `resources_completed_percent` int(11) DEFAULT 0,
  `quiz_passed` tinyint(1) DEFAULT 0,
  `quiz_score` int(11) DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`topic_progress_id`),
  UNIQUE KEY `unique_topic_progress` (`student_id`,`topic_id`),
  KEY `topic_id` (`topic_id`),
  KEY `idx_student_completed` (`student_id`,`is_completed`),
  CONSTRAINT `student_topic_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `student_topic_progress_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_topic_progress`
--

LOCK TABLES `student_topic_progress` WRITE;
/*!40000 ALTER TABLE `student_topic_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_topic_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subtopic_details`
--

DROP TABLE IF EXISTS `subtopic_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subtopic_details` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `subtopic_id` int(11) NOT NULL,
  `detail_text` varchar(500) NOT NULL,
  `detail_order` int(11) DEFAULT 0,
  PRIMARY KEY (`detail_id`),
  KEY `subtopic_id` (`subtopic_id`),
  CONSTRAINT `subtopic_details_ibfk_1` FOREIGN KEY (`subtopic_id`) REFERENCES `subtopics` (`subtopic_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subtopic_details`
--

LOCK TABLES `subtopic_details` WRITE;
/*!40000 ALTER TABLE `subtopic_details` DISABLE KEYS */;
INSERT INTO `subtopic_details` VALUES (1,1,'Database',1),(2,1,'Data',1),(3,1,'Information',1),(4,1,'Entities',1),(5,1,'Attributes/Field',1),(6,1,'Records',1),(7,1,'Table',1),(8,1,'Database schema',1),(9,1,'DBMS',1),(10,1,'SQL',1),(11,17,'Functional requirement',1),(12,17,'Non-functional requirement',1),(13,18,'Interview',1),(14,18,'Documentation',1),(15,18,'Questionnaire',1),(16,18,'Observation',1),(17,24,'Description of ERD',1),(18,24,'Components of ERD',1),(19,24,'Define relationships',1),(20,24,'Create an ERD',1),(21,24,'Draw an ERD (MS-Visio, Draw-Max)',1),(22,26,'NOT NULL Constraint',1),(23,26,'UNIQUE Constraint',1),(24,26,'DEFAULT Constraint',1),(25,26,'CHECK Constraint',1),(26,26,'PRIMARY KEY Constraint',1),(27,26,'FOREIGN KEY Constraint',1),(28,53,'Create roles',1),(29,53,'Assign permissions/privilege to roles',1),(30,53,'Assign roles to users',1),(31,53,'Test the authorisation system',1),(32,53,'Monitor and maintain',1),(33,54,'Identify logging requirements',1),(34,54,'Configure logging settings',1),(35,54,'Monitor log data',1),(36,54,'Analyse log data',1),(37,54,'Archive log data',1),(38,54,'Corrective action',1),(39,55,'Identify data that needs to be audited',1),(40,55,'Execute SQL commands',1),(41,55,'Configure audit settings',1),(42,55,'Review audit',1),(43,55,'Analyse audit data',1),(44,55,'Corrective action',1),(45,64,'Full database recovery',1),(46,64,'Rollback recovery',1),(47,64,'Point-in-time recovery',1),(48,91,'Sequence structures',1),(49,91,'Selection structures',1),(50,91,'Looping structures',1);
/*!40000 ALTER TABLE `subtopic_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subtopic_resources`
--

DROP TABLE IF EXISTS `subtopic_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subtopic_resources` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `subtopic_id` int(11) NOT NULL,
  `resource_type` enum('note','video','link','pdf','image','exercise') NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `source` enum('ai_generated','teacher_added') DEFAULT 'teacher_added',
  `is_approved` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`resource_id`),
  KEY `subtopic_id` (`subtopic_id`),
  CONSTRAINT `subtopic_resources_ibfk_1` FOREIGN KEY (`subtopic_id`) REFERENCES `subtopics` (`subtopic_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subtopic_resources`
--

LOCK TABLES `subtopic_resources` WRITE;
/*!40000 ALTER TABLE `subtopic_resources` DISABLE KEYS */;
INSERT INTO `subtopic_resources` VALUES (1,66,'note','Decimal → Binary Conversion','1. Decimal → Binary Conversion\r\nExample: Convert 25 (decimal) to binary\r\n\r\nWe divide the number by 2 repeatedly and keep the remainders:\r\n\r\nDivision	Quotient	Remainder\r\n25 ÷ 2	12	1\r\n12 ÷ 2	6	0\r\n6 ÷ 2	3	0\r\n3 ÷ 2	1	1\r\n1 ÷ 2	0	1\r\n\r\n👉 Now read remainders from bottom to top:\r\n\r\n25₁₀ = 11001₂\r\n\r\n🔹 2. Binary → Decimal Conversion\r\nExample: Convert 11001 (binary) to decimal\r\n\r\nWe multiply each bit by powers of 2:\r\n\r\n1×2\r\n4\r\n+1×2\r\n3\r\n+0×2\r\n2\r\n+0×2\r\n1\r\n+1×2\r\n0\r\n=16+8+0+0+1=25\r\n\r\n👉 11001₂ = 25₁₀',NULL,NULL,NULL,'teacher_added',1,0,'2026-04-27 19:00:33'),(2,67,'note','DECIAMAL TO OCTAL','1. Example: Convert 83 (decimal) to octal\r\n\r\nWe divide the number by 8 repeatedly and keep the remainders:\r\n\r\nDivision	Quotient	Remainder\r\n83 ÷ 8	10	3\r\n10 ÷ 8	1	2\r\n1 ÷ 8	0	1\r\n\r\n👉 Read the remainders from bottom to top:\r\n\r\n83₁₀ = 123₈\r\n\r\n🔹 2. Algorithm (Pseudocode)\r\nfunction decimalToOctal(n):\r\n    while n > 0:\r\n        remainder = n % 8\r\n        store remainder\r\n        n = n // 8\r\n    reverse stored remainders\r\n🔹 3. Quick Tip\r\nUse base 8 instead of base 2\r\nSame idea as binary conversion\r\nRemainders must be between 0–7',NULL,NULL,NULL,'teacher_added',1,0,'2026-04-27 19:11:05'),(3,67,'note','DECIMAL TO OCTAL CONVERSION','1. Example: Convert 83 (decimal) to octal\r\n\r\nWe divide the number by 8 repeatedly and keep the remainders:\r\n\r\nDivision	Quotient	Remainder\r\n83 ÷ 8	10	3\r\n10 ÷ 8	1	2\r\n1 ÷ 8	0	1\r\n\r\n👉 Read the remainders from bottom to top:\r\n\r\n83₁₀ = 123₈\r\n\r\n🔹 2. Algorithm (Pseudocode)\r\nfunction decimalToOctal(n):\r\n    while n > 0:\r\n        remainder = n % 8\r\n        store remainder\r\n        n = n // 8\r\n    reverse stored remainders\r\n🔹 3. Quick Tip\r\nUse base 8 instead of base 2\r\nSame idea as binary conversion\r\nRemainders must be between 0–7',NULL,NULL,NULL,'teacher_added',1,0,'2026-04-27 19:31:41'),(4,376,'note','Define blockchain,cryptography','Blockchain\r\n\r\nBlockchain Technology is a decentralized digital system used to record transactions across many computers in a secure and transparent way.\r\nEach group of records is called a block, and these blocks are linked together in sequence to form a chain.\r\nOnce information is recorded, it becomes very difficult to change or delete, which increases trust and security.\r\n\r\nKey features of blockchain:\r\n\r\nDecentralization\r\nTransparency\r\nSecurity\r\nImmutability (data cannot easily be changed)\r\nPeer-to-peer transactions\r\n\r\nExample:\r\nBitcoin uses blockchain to record cryptocurrency transactions.\r\n\r\nCryptography\r\n\r\nCryptography is the science of protecting information by converting it into a secret format so that only authorized people can read it.\r\n\r\nIt uses mathematical techniques to:\r\n\r\nSecure data\r\nProtect privacy\r\nVerify identities\r\nEnsure message integrity\r\n\r\nMain types of cryptography:\r\n\r\nSymmetric encryption\r\nAsymmetric encryption\r\nHashing\r\n\r\nExample:\r\nWhen sending a password securely over the internet, cryptography encrypts the password to prevent hackers from reading it.',NULL,NULL,NULL,'teacher_added',1,0,'2026-05-10 08:22:31'),(5,376,'note','Define blockchain, cryptography','Blockchain\r\nBlockchain Technology is a decentralized digital system used to record transactions across many computers in a secure and transparent way.\r\nEach group of records is called a block, and these blocks are linked together in sequence to form a chain.\r\nOnce information is recorded, it becomes very difficult to change or delete, which increases trust and security.\r\nKey features of blockchain:\r\n\r\n\r\nDecentralization\r\n\r\n\r\nTransparency\r\n\r\n\r\nSecurity\r\n\r\n\r\nImmutability (data cannot easily be changed)\r\n\r\n\r\nPeer-to-peer transactions\r\n\r\n\r\nExample:\r\nBitcoin uses blockchain to record cryptocurrency transactions.\r\n\r\nCryptography\r\nCryptography is the science of protecting information by converting it into a secret format so that only authorized people can read it.\r\nIt uses mathematical techniques to:\r\n\r\n\r\nSecure data\r\n\r\n\r\nProtect privacy\r\n\r\n\r\nVerify identities\r\n\r\n\r\nEnsure message integrity\r\n\r\n\r\nMain types of cryptography:\r\n\r\n\r\nSymmetric encryption\r\n\r\n\r\nAsymmetric encryption\r\n\r\n\r\nHashing\r\n\r\n\r\nExample:\r\nWhen sending a password securely over the internet, cryptography encrypts the password to prevent hackers from reading it.',NULL,NULL,NULL,'teacher_added',1,1,'2026-05-10 08:42:41'),(6,376,'video','understand blockchain&quot;watch this 3 minutes video&quot;',NULL,'https://www.youtube.com/watch?v=d5cwmGzECkM',NULL,3,'teacher_added',1,2,'2026-05-10 08:49:01'),(8,377,'note','History of blockchain','Short History of Blockchain Technology\r\n1991 – The idea of blockchain was first introduced by researchers Stuart Haber and W. Scott Stornetta to create a secure system for timestamping digital documents.\r\n2008 – A person or group using the name Satoshi Nakamoto published the Bitcoin: A Peer-to-Peer Electronic Cash System, explaining how blockchain could support digital currency without banks.\r\n2009 – Bitcoin became the first blockchain-based cryptocurrency and the first real use of blockchain technology.\r\n2015 – Ethereum introduced smart contracts, allowing developers to build decentralized applications (DApps) on blockchain.\r\nToday – Blockchain is used in many fields such as finance, healthcare, supply chain management, education, and voting systems.',NULL,NULL,NULL,'teacher_added',1,1,'2026-05-10 09:03:57'),(9,378,'note','types of blockchain','Types of Blockchain Technology (Summary)\r\n1. Public Blockchain\r\n\r\nA blockchain that anyone can join, read, and participate in.\r\n\r\nFeatures:\r\n\r\nFully decentralized\r\nTransparent\r\nOpen to everyone\r\n\r\nExamples:\r\n\r\nBitcoin\r\nEthereum\r\n2. Private Blockchain\r\n\r\nA blockchain controlled by one organization.\r\n\r\nFeatures:\r\n\r\nRestricted access\r\nFaster transactions\r\nMore privacy\r\n\r\nUsed by:\r\nCompanies and private businesses.\r\n\r\n3. Consortium Blockchain\r\n\r\nA blockchain managed by a group of organizations instead of one single organization.\r\n\r\nFeatures:\r\n\r\nSemi-decentralized\r\nShared control\r\nBetter collaboration\r\n\r\nExample use:\r\nBanking and supply chain systems.\r\n\r\n4. Hybrid Blockchain\r\n\r\nA combination of public and private blockchain features.\r\n\r\nFeatures:\r\n\r\nSome data is public\r\nSome data is private\r\nFlexible security and control\r\n\r\nUsed in:\r\nHealthcare, government, and enterprise systems.',NULL,NULL,NULL,'teacher_added',1,1,'2026-05-10 09:48:40'),(10,378,'video','sdfghj',NULL,'https://www.youtube.com/watch?v=FcfPU3rYVAk',NULL,4,'teacher_added',1,2,'2026-05-10 11:47:43'),(11,378,'link','sdfghjk',NULL,'https://www.ibm.com/think/topics/blockchain?utm_source=chatgpt.com',NULL,NULL,'teacher_added',1,3,'2026-05-10 11:49:55'),(12,382,'note','AI Lesson Notes','# Blockchain Company solutions\r\n\r\n## 📝 Teacher feedback applied:\r\nexplain Company solutions  with exples and more details in blockchain\r\n\r\n## Overview\r\nThis content is for *Blockchain Company solutions*.\r\n\r\n### Key Concepts\r\n- Concept 1\r\n- Concept 2\r\n\r\n### Summary\r\n- Key takeaway\r\n',NULL,NULL,NULL,'ai_generated',1,0,'2026-05-10 12:15:24'),(13,382,'video','Intro to Blockchain Company solutions',NULL,'https://example.com',NULL,NULL,'ai_generated',1,0,'2026-05-10 12:15:24'),(14,382,'link','Documentation',NULL,'https://example.com/docs',NULL,NULL,'ai_generated',1,0,'2026-05-10 12:15:24');
/*!40000 ALTER TABLE `subtopic_resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subtopics`
--

DROP TABLE IF EXISTS `subtopics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subtopics` (
  `subtopic_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `subtopic_title` varchar(255) NOT NULL,
  `subtopic_order` int(11) DEFAULT 0,
  `has_checkmark` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`subtopic_id`),
  KEY `topic_id` (`topic_id`),
  CONSTRAINT `subtopics_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=554 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subtopics`
--

LOCK TABLES `subtopics` WRITE;
/*!40000 ALTER TABLE `subtopics` DISABLE KEYS */;
INSERT INTO `subtopics` VALUES (1,1,'Definition of key terms',1,1),(2,1,'Applications of database',2,1),(3,1,'Advantages and Disadvantages of Database',3,1),(4,2,'Relational database',1,1),(5,2,'Hierarchical database',2,1),(6,2,'Network database',3,1),(7,2,'Object oriented model',4,1),(8,3,'One to one',1,1),(9,3,'One to many',2,1),(10,3,'Many to one',3,1),(12,4,'Character',1,1),(13,4,'Number',2,1),(14,4,'Date',3,1),(15,5,'Definition of data dictionary',1,1),(16,5,'Elements of data dictionary',2,1),(17,6,'Types of database requirements',1,1),(18,6,'Methods to collect data',2,1),(19,7,'Introduction of database schema',1,1),(20,7,'Types of database schema',2,1),(21,7,'Data abstraction levels',3,1),(22,7,'Types of data independence',4,1),(23,8,'Description of conceptual database schema',1,1),(24,8,'Entity relationship diagram (ERD)',2,1),(25,9,'Description of logical database schema',1,1),(26,9,'Table constraints',2,1),(27,11,'First normal form (1NF)',1,1),(28,11,'Second normal form (2NF)',2,1),(29,11,'Third normal form (3NF)',3,1),(30,16,'CREATE (Database, Table, Constraints)',1,1),(31,16,'ALTER TABLE',2,1),(32,16,'DROP (Database, Table)',3,1),(33,16,'TRUNCATE TABLE',4,1),(34,16,'MODIFY (Database, Table)',5,1),(35,17,'INSERT',1,1),(36,17,'UPDATE',2,1),(37,17,'DELETE',3,1),(38,17,'CALL',4,1),(39,17,'EXPLAIN CALL',5,1),(40,17,'LOCK',6,1),(41,18,'SELECT',1,1),(42,18,'SQL aggregate function',2,1),(43,18,'SQL clause',3,1),(44,19,'GRANT',1,1),(45,19,'REVOKE',2,1),(46,20,'COMMIT',1,1),(47,20,'SAVEPOINT',2,1),(48,20,'ROLLBACK',3,1),(49,20,'SET Transaction',4,1),(50,20,'SET Constraints',5,1),(51,21,'Database security concepts',1,1),(52,21,'Authentication',2,1),(53,21,'Authorization',3,1),(54,22,'Logging',1,1),(55,22,'Auditing',2,1),(56,23,'Symmetric Encryption',1,1),(57,23,'Asymmetric Encryption',2,1),(58,23,'Hashing',3,1),(59,24,'Full backup',1,1),(60,24,'Differential backup',2,1),(61,24,'Incremental backup',3,1),(62,24,'Backup schedule',4,1),(63,24,'Create Backup',5,1),(64,24,'Perform recovery method',6,1),(65,24,'Test your backup and recovery plan',7,1),(66,25,'Decimal to Binary and vice versa',1,1),(67,25,'Decimal to Octal and vice versa',2,1),(68,25,'Decimal to Hexadecimal and vice versa',3,1),(69,25,'Binary to Hexadecimal and vice versa',4,1),(70,25,'Octal to Binary and vice versa',5,1),(71,26,'AND gate',1,1),(72,26,'OR gate',2,1),(73,26,'NOT gate',3,1),(74,26,'NAND gate',4,1),(75,26,'NOR gate',5,1),(76,26,'XOR gate',6,1),(77,26,'Truth table',7,1),(78,27,'Primitive data types',1,1),(79,27,'Non-Primitive data types',2,1),(80,28,'Assignment operators',1,1),(81,28,'Arithmetic operators',2,1),(82,28,'Logical operators',3,1),(83,28,'Relational operators',4,1),(84,28,'Compound operators',5,1),(85,28,'Conditional operators',6,1),(86,28,'Bitwise operators',7,1),(87,29,'Definition of algorithm',1,1),(88,29,'Types of algorithm',2,1),(89,29,'Characteristics of algorithm',3,1),(90,29,'Structured English',4,1),(91,29,'Pseudocode',5,1),(92,30,'Flowchart elements',1,1),(93,30,'Flowchart tools',2,1),(94,30,'Flowchart best practices',3,1),(95,30,'Draw a flowchart',4,1),(96,31,'Linear data structures',1,1),(97,31,'Non-linear data structures',2,1),(98,31,'List representation',3,1),(99,31,'List operations',4,1),(100,32,'Linear search',1,1),(101,32,'Binary search',2,1),(102,33,'Time complexity',1,1),(103,33,'Space complexity',2,1),(104,34,'By number of comparisons',1,1),(105,34,'By number of swaps',2,1),(106,34,'By memory usage',3,1),(107,34,'By recursion',4,1),(108,34,'By stability',5,1),(109,34,'By adaptability',6,1),(110,34,'Internal sorting',7,1),(111,34,'External sorting',8,1),(112,35,'Selection Sort',1,1),(113,35,'Bubble Sort',2,1),(114,35,'Insertion Sort',3,1),(115,35,'Merge Sort',4,1),(116,35,'Quick Sort',5,1),(117,35,'Shell Sort',6,1),(118,35,'Heap Sort',7,1),(119,35,'Radix Sort',8,1),(120,35,'Counting Sort',9,1),(121,35,'Bucket Sort',10,1),(122,36,'Arrays',1,1),(123,36,'Write procedures',2,1),(124,37,'Linked lists',1,1),(125,37,'Write procedures',2,1),(126,38,'Queue',1,1),(127,38,'Stack',2,1),(128,38,'Write procedures',3,1),(129,39,'Tree',1,1),(130,39,'Write procedures',2,1),(131,40,'Graph',1,1),(132,40,'Write procedures',2,1),(133,41,'Tables',1,1),(134,41,'Write procedures',2,1),(135,42,'JavaScript running environment',1,1),(136,43,'Linked lists',1,1),(137,43,'Arrays',2,1),(138,43,'Queue',3,1),(139,43,'Stack',4,1),(140,43,'Tree',5,1),(141,43,'Graph',6,1),(142,43,'Tables',7,1),(143,44,'Bubble sort',1,1),(144,44,'Quick sort',2,1),(145,45,'Binary search',1,1),(146,45,'Linear search',2,1),(147,46,'Rendering engine',1,1),(148,46,'Web dev tools',2,1),(149,47,'Using IDE Terminal',1,1),(150,48,'Measuring concepts',1,1),(151,48,'Profiling tools',2,1),(152,48,'Benchmark.js',3,1),(153,48,'Benchmarkify',4,1),(154,48,'jsPerf',5,1),(155,48,'Document test findings',6,1),(156,49,'Node.js',1,1),(157,49,'Routes',2,1),(158,49,'NPM',3,1),(159,49,'Express.js',4,1),(160,49,'Backend Application',5,1),(161,49,'Class',6,1),(162,49,'Object',7,1),(163,49,'Method',8,1),(164,49,'Properties',9,1),(165,49,'Dependencies',10,1),(166,49,'APIs',11,1),(167,49,'Postman',12,1),(168,49,'Nodemon',13,1),(169,49,'DBMS (SQL Based, NoSQL Based)',14,1),(170,50,'Node.js and NPM installation',1,1),(171,50,'Express.js installation',2,1),(172,50,'Postman installation',3,1),(173,50,'Nodemon installation',4,1),(174,53,'HTTP',1,1),(175,53,'HTTPS',2,1),(176,53,'Axios',3,1),(177,53,'Request',4,1),(178,54,'Setup Connection parameters',1,1),(179,54,'Create/send Request',2,1),(180,54,'Handle the response',3,1),(181,56,'Create Database',1,1),(182,56,'Schema Setup',2,1),(183,56,'Configure Database Connection',3,1),(184,56,'Test Database Connection',4,1),(185,57,'Create POST Endpoint',1,1),(186,57,'Create all Items GET endpoint',2,1),(187,57,'Create specific ID GET endpoint',3,1),(188,57,'Create PUT endpoint',4,1),(189,57,'Create DELETE endpoint',5,1),(190,59,'Types of middleware services',1,1),(191,59,'Error Handling',2,1),(192,59,'Logging',3,1),(193,59,'Input validation',4,1),(194,60,'Perform CRUD operations using MySQL Database',1,1),(195,60,'Use HTTP Status code',2,1),(196,62,'Types of data encryption',1,1),(197,62,'Encryption techniques',2,1),(198,62,'Benefits and importance of data encryption',3,1),(199,63,'Install the crypto module',1,1),(200,63,'Create a key for encryption',2,1),(201,63,'Use the key to encrypt data',3,1),(202,63,'Convert the data to a buffer',4,1),(203,63,'Encrypt the data',5,1),(204,63,'Store the encrypted data',6,1),(205,65,'Express',1,1),(206,65,'Lodash',2,1),(207,65,'Moment.js',3,1),(208,66,'Callbacks',1,1),(209,66,'Promises',2,1),(210,66,'async/await',3,1),(211,67,'Package.json',1,1),(212,67,'npm-shrinkwrap.json',2,1),(213,68,'NPM outdated',1,1),(214,68,'NPM audit',2,1),(215,68,'Snyk',3,1),(216,69,'Versioning',1,1),(217,69,'semver rules',2,1),(218,70,'Staging environments',1,1),(219,70,'Version control systems',2,1),(220,73,'Passport',1,1),(221,73,'JWT (JSON Web Tokens)',2,1),(222,73,'Social Auth (Google, Facebook)',3,1),(223,78,'Role-based access control',1,1),(224,78,'Attribute-based access control',2,1),(225,83,'Winston',1,1),(226,83,'Morgan',2,1),(227,84,'Securely storing log data',1,1),(228,84,'Audit logs for security events',2,1),(229,85,'Database credentials',1,1),(230,85,'API keys',2,1),(231,85,'Encryption keys',3,1),(232,87,'Encrypting secrets',1,1),(233,87,'Decrypting secrets',2,1),(234,88,'Key management service',1,1),(235,88,'.env file',2,1),(236,93,'Importance of Unit Testing',1,1),(237,93,'Unit Testing Process',2,1),(238,93,'Unit Testing tools',3,1),(239,93,'Frameworks',4,1),(240,93,'Libraries',5,1),(241,94,'Installation and Configuration',1,1),(242,94,'Writing Unit tests',2,1),(243,94,'Running Tests',3,1),(244,95,'Installation and configuration',1,1),(245,95,'Writing assertions',2,1),(246,95,'Chai Expect and Should APIs',3,1),(247,97,'Importance of Usability Testing',1,1),(248,97,'Usability Testing Process',2,1),(249,97,'Usability Testing tools',3,1),(250,98,'Installation of Postman',1,1),(251,98,'Create a collection',2,1),(252,98,'Define Request',3,1),(253,98,'Write test Cases',4,1),(254,98,'Run tests',5,1),(255,98,'Iterate and improve',6,1),(256,99,'Installation of Puppeteer',1,1),(257,99,'Define test scenarios',2,1),(258,99,'Automate user interaction',3,1),(259,99,'Measure page performance',4,1),(260,99,'Test accessibility',5,1),(261,99,'Generate Report',6,1),(262,100,'Injection Attacks',1,1),(263,100,'Broken Authentication',2,1),(264,100,'Cross-Site Scripting (XSS)',3,1),(265,100,'Cross-Site Request Forgery (CSRF)',4,1),(266,100,'Security Misconfiguration',5,1),(267,100,'Insecure Cryptographic Storage',6,1),(268,100,'Insufficient Authorization',7,1),(269,100,'Insufficient Logging',8,1),(270,101,'Static Analysis Tools',1,1),(271,101,'Dynamic Analysis Tools',2,1),(272,101,'OWASP, Mocha, Chai',3,1),(273,104,'Security Testing Lifecycle',1,1),(274,104,'Reporting Vulnerabilities',2,1),(275,104,'Remediation and Mitigation',3,1),(276,105,'Test Authentication and Authorization',1,1),(277,105,'Test input validation',2,1),(278,105,'Use SSL/TLS encryption',3,1),(279,105,'Test Error Handling',4,1),(280,105,'Update dependencies regularly',5,1),(281,106,'Identify scope',1,1),(282,106,'Gather API Information',2,1),(283,106,'Identify Vulnerabilities',3,1),(284,106,'Perform manual testing',4,1),(285,106,'Document findings',5,1),(286,106,'Remediate Vulnerabilities',6,1),(287,106,'Re-test',7,1),(288,107,'Install OWASP tool',1,1),(289,107,'Perform scan',2,1),(290,107,'Exploit vulnerabilities',3,1),(291,107,'Interpret Scan report',4,1),(292,107,'Document results',5,1),(293,109,'Manual Deployment',1,1),(294,109,'Continuous Deployment',2,1),(295,109,'Docker-based deployment',3,1),(296,110,'NodeJS Runtime',1,1),(297,110,'Package Manager',2,1),(298,110,'Operating system',3,1),(299,110,'Webserver',4,1),(300,110,'Database',5,1),(301,114,'Update',1,1),(302,114,'Monitor',2,1),(303,114,'Perform test',3,1),(304,115,'Identify requirements',1,1),(305,115,'Schedule updates',2,1),(306,115,'Automate tasks',3,1),(307,115,'Monitor performance',4,1),(308,115,'Test regularly',5,1),(309,115,'Disaster recovery plan',6,1),(310,115,'Document changes',7,1),(311,116,'Upgrade functionalities',1,1),(312,116,'Develop new functionalities',2,1),(313,116,'Secure functionalities',3,1),(314,116,'Test new functionalities',4,1),(315,116,'Deploy changes',5,1),(316,120,'Swagger for API documentation',1,1),(317,120,'Postman for API documentation',2,1),(318,120,'Writing comments',3,1),(319,120,'Documentation generators',4,1),(320,122,'Hosting options',1,1),(321,122,'GitHub for documentation',2,1),(322,124,'Server',1,1),(323,124,'Linux',2,1),(324,124,'Development Operations(DevOps)',3,1),(325,124,'DevSecOps',4,1),(326,124,'Container',5,1),(327,124,'Node',6,1),(328,124,'Infrastructure as Code IaC',7,1),(329,124,'IaaS',8,1),(330,124,'CI/CD',9,1),(331,134,'Web',1,1),(332,134,'Mail',2,1),(333,134,'File',3,1),(334,134,'SSH',4,1),(335,134,'Network',5,1),(336,134,'DNS',6,1),(337,134,'PROXY',7,1),(338,134,'Monitoring and Logging',8,1),(339,134,'Backup',9,1),(340,135,'Web',1,1),(341,135,'Mail',2,1),(342,135,'File',3,1),(343,135,'SSH',4,1),(344,135,'Network',5,1),(345,135,'DNS',6,1),(346,135,'PROXY',7,1),(347,136,'Deployment',1,1),(348,136,'Build agent',2,1),(349,136,'Containerisation',3,1),(350,136,'Docker',4,1),(351,136,'Kubernetes',5,1),(352,136,'Jargon',6,1),(353,136,'Dependence',7,1),(354,145,'Deployment orchestration',1,1),(355,145,'CI server',2,1),(356,146,'Configure server',1,1),(357,146,'Set up Automated build',2,1),(358,146,'Implement Automated testing',3,1),(359,146,'Check Code Quality',4,1),(360,146,'Artifact Management',5,1),(361,146,'Integration with version control',6,1),(362,146,'Configure CI pipeline',7,1),(363,147,'Develop deployment scripts',1,1),(364,147,'Use infrastructure as code (IaC)',2,1),(365,147,'Use deployment orchestration tool',3,1),(366,147,'Implement automated rollback',4,1),(367,147,'Configure CD pipeline',5,1),(368,159,'Application tools',1,1),(369,159,'Networking tools',2,1),(370,159,'Infrastructure tools',3,1),(371,165,'Regular Review',1,1),(372,165,'Root Cause Analysis',2,1),(373,165,'Actionable Insights',3,1),(374,165,'Feedback Loop Integration',4,1),(376,175,'Define blockchain, cryptography',1,1),(377,175,'History of blockchain',2,1),(378,175,'Types of Blockchain',3,1),(379,175,'Blockchain Principles',4,1),(380,175,'Functionalities of blockchain',5,1),(381,175,'Pros and Cons of blockchain',6,1),(382,175,'Blockchain Company solutions',7,1),(383,176,'Essential components of wallet (Private Keys, Public Keys, Addresses)',1,1),(384,176,'Transactions, Merkle Trees, and Blocks',2,1),(385,176,'Hierarchical Deterministic Wallets, Mnemonic Seeds and Smart Contracts',3,1),(386,176,'Working of Blockchain Transaction',4,1),(387,178,'Consensus Layer (PoW, PoS, etc)',1,1),(388,178,'Network Layer (Ethereum\'s Peer-to-Peer Network)',2,1),(389,178,'Protocol Layer (Ethereum\'s EVM)',3,1),(390,178,'Smart Contracts Layer (DeFi Platforms)',4,1),(391,178,'Application Layer (CryptoKitties)',5,1),(392,178,'Storage Layer (IPFS)',6,1),(393,178,'Identity and Access Management (SelfKey)',7,1),(394,178,'Security and Encryption (Public and Private Key Encryption)',8,1),(395,178,'Interoperability Layer (Polkadot)',9,1),(396,178,'Scalability Solutions (Lightning Network for Bitcoin)',10,1),(397,178,'Governance Mechanisms (Tezos)',11,1),(398,178,'User Interfaces (MetaMask)',12,1),(399,179,'Proof of Work',1,1),(400,179,'Proof of Stake',2,1),(401,179,'Delegated Proof of Stake',3,1),(402,179,'Proof of Authority',4,1),(403,179,'Proof of Weight',5,1),(404,181,'Attack in consensus mechanism',1,1),(405,181,'Sybil Attack',2,1),(406,181,'Double Spending',3,1),(407,181,'Eclipse Attack',4,1),(408,181,'Smart Contract Vulnerabilities (re‑entrancy, integer overflow/underflow)',5,1),(409,181,'DDoS Attack',6,1),(410,181,'Blockchain Spamming',7,1),(411,181,'Long‑Range Attack',8,1),(412,181,'Selfish Mining',9,1),(413,181,'Routing Attacks',10,1),(414,181,'Transaction Malleability',11,1),(415,181,'Consensus Manipulation',12,1),(416,182,'Components',1,1),(417,182,'Connection',2,1),(418,182,'Instance relation',3,1),(419,183,'Design Blockchain based Systems',1,1),(420,183,'Designing the Blockchain Network',2,1),(421,183,'Design Smart Contract',3,1),(422,184,'Identify the Use Case',1,1),(423,184,'Identify third party Integration',2,1),(424,184,'Identify the Consensus Mechanism',3,1),(425,184,'Identify the Platform',4,1),(426,184,'Design the Blockchain Instance',5,1),(427,184,'Design the Architecture',6,1),(428,185,'Solidity, Syntax, Data types, Variables, identifiers',1,1),(429,185,'Arrays, Struct, Functions, Control structures',2,1),(430,185,'State variables, Modifiers, smart contract',3,1),(431,185,'Visibility and Access Control',4,1),(432,185,'Ethereum, Ethereum Virtual Machine (EVM)',5,1),(433,186,'Installing Code editor (Remix, Visual Studio Code)',1,1),(434,186,'Installing Node.js and npm',2,1),(435,186,'Installing Solidity compiler (solc) and Ethereum development tools (Truffle, Hardhat)',3,1),(436,193,'Metamask Wallet',1,1),(437,193,'Trust wallet',2,1),(438,196,'Read only operations',1,1),(439,196,'Write operation',2,1),(440,197,'Calculating the cost of Ethereum transfer',1,1),(441,197,'Heavy and Light functions',2,1),(442,197,'Block limit',3,1),(443,197,'Opcode Gas cost',4,1),(444,197,'Non‑payable functions',5,1),(445,198,'Smaller Integers, Unchanged Storage Values, Arrays',1,1),(446,198,'Refunds and Setting to Zero',2,1),(447,198,'ERC20 Transfers',3,1),(448,198,'Storage Cost for Files',4,1),(449,198,'Structs and Strings, Variable Packing, Array Length',5,1),(450,199,'Memory vs Calldata',1,1),(451,199,'Mappings vs Arrays',2,1),(452,199,'Freeing Up Unused Storage',3,1),(453,199,'immutable and constant',4,1),(454,199,'Access Modifier',5,1),(455,199,'Indexed Events',6,1),(456,199,'Minimizing On‑Chain Data',7,1),(457,200,'Mapping, Arrays, Structs and Error handling',1,1),(458,200,'Use of modifiers, Interfaces, Events, and Inheritance',2,1),(459,200,'Contracts composition',3,1),(460,200,'Storage locations',4,1),(461,200,'Compiling',5,1),(462,200,'Test with Hardhat (chai, mocha)',6,1),(463,202,'ERC20 Token Standard',1,1),(464,202,'Writing an ERC20 Token in Solidity',2,1),(465,203,'ERC721 standard',1,1),(466,203,'Write NFT smart contracts using ERC721 standard',2,1),(467,203,'ERC1155 Multi Token Smart Contract',3,1),(468,206,'OpenZeppelin (includes SafeMath)',1,1),(469,206,'Chainlink',2,1),(470,207,'Local network (Ganache)',1,1),(471,207,'Public network (mainnet, testnet)',2,1),(472,208,'Alchemy',1,1),(473,208,'Infura',2,1),(474,209,'Truffle',1,1),(475,209,'Hardhat',2,1),(476,210,'Install contract extension in browser for development (Metamask)',1,1),(477,210,'Create wallet',2,1),(478,210,'Load enough balance in wallet (faucet)',3,1),(479,211,'Install web3 libraries (ethers.js, web3.js)',1,1),(480,211,'Connect to smart contract using keys (contract address, ABI)',2,1),(481,215,'Test web application',1,1),(482,215,'Build production bundles',2,1),(483,215,'Configure keys on production environment variables',3,1),(484,215,'Deploy production builds',4,1),(485,216,'ReactJS',1,1),(486,216,'Component',2,1),(487,216,'JSX (JavaScript XML)',3,1),(488,216,'Props',4,1),(489,216,'State',5,1),(490,216,'Lifecycle Methods',6,1),(491,216,'Hooks',7,1),(492,216,'Virtual DOM',8,1),(493,216,'React Router',9,1),(494,216,'Redux',10,1),(495,217,'Uses of react',1,1),(496,217,'Features',2,1),(497,222,'Class components',1,1),(498,222,'Functional components',2,1),(499,225,'componentDidMount',1,1),(500,225,'componentDidUpdate',2,1),(501,225,'componentWillUnmount',3,1),(502,233,'State Hooks',1,1),(503,233,'Effect Hooks',2,1),(504,233,'Context Hooks',3,1),(505,233,'Ref Hooks',4,1),(506,233,'Callback Hooks',5,1),(507,237,'Context API',1,1),(508,237,'Redux',2,1),(509,237,'MobX',3,1),(510,237,'Zustand',4,1),(511,238,'Types of events',1,1),(512,238,'Synthetic events',2,1),(513,238,'Event bubbling',3,1),(514,241,'Arrow Function (in JSX)',1,1),(515,241,'Bind Method',2,1),(516,244,'Describe API',1,1),(517,244,'Dependencies Installation (Axios)',2,1),(518,245,'Defining and Grouping API Calls',1,1),(519,245,'Handling Data Fetching and Responses',2,1),(520,245,'Error Handling',3,1),(521,245,'Asynchronous Handling and Concurrency',4,1),(522,247,'Install Tailwind CSS',1,1),(523,247,'Configuring Tailwind CSS',2,1),(524,269,'Installing TypeScript',1,1),(525,269,'Configuring TypeScript',2,1),(526,272,'API data validation',1,1),(527,272,'Form validation',2,1),(528,272,'Error handling and exceptions',3,1),(529,275,'Creating Pages and components',1,1),(530,275,'Implementing search engine optimization (SEO)',2,1),(531,275,'Styling',3,1),(532,275,'Caching Strategies',4,1),(533,280,'File-system Based Routing',1,1),(534,280,'Dynamic Routes',2,1),(535,280,'Nested Routes',3,1),(536,280,'Link Component',4,1),(537,280,'Programmatic Navigation',5,1),(538,280,'API Routes',6,1),(539,280,'Catch-all Routes',7,1),(540,289,'Client-side rendering (CSR) security',1,1),(541,289,'Cross-Origin Resource Sharing (CORS)',2,1),(542,289,'Session management',3,1),(543,289,'Third-party libraries (Auth0)',4,1),(544,290,'HTTPS enforcement',1,1),(545,290,'Server-side rendering (SSR) security',2,1),(546,290,'API routes security',3,1),(547,290,'Content Security Policy (CSP)',4,1),(548,290,'Authentication',5,1),(549,303,'Platform-specific options',1,1),(550,303,'Separate .env files',2,1),(551,308,'Translation of Domain Names to IP Addresses',1,1),(552,308,'Hierarchy and Structure',2,1),(553,308,'Name Resolution Process',3,1);
/*!40000 ALTER TABLE `subtopics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `summative_assessments`
--

DROP TABLE IF EXISTS `summative_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `summative_assessments` (
  `summative_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `passing_score` int(11) DEFAULT 70,
  `time_limit_minutes` int(11) DEFAULT 180,
  `guide_text` text DEFAULT NULL,
  `created_by_ai` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`summative_id`),
  KEY `idx_module` (`module_id`),
  CONSTRAINT `summative_assessments_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `summative_assessments`
--

LOCK TABLES `summative_assessments` WRITE;
/*!40000 ALTER TABLE `summative_assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `summative_assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `summative_questions`
--

DROP TABLE IF EXISTS `summative_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `summative_questions` (
  `summative_q_id` int(11) NOT NULL AUTO_INCREMENT,
  `summative_id` int(11) NOT NULL,
  `section` enum('Section_A_Theory','Section_B_Practical','Section_C_Case_Study') DEFAULT 'Section_A_Theory',
  `question_type` enum('sentence_completion','true_false','multiple_choice','multiple_answer','essay','short_answer','practical_sql','case_study') NOT NULL,
  `question_text` text NOT NULL,
  `points` int(11) DEFAULT 1,
  `options_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options_json`)),
  `rubric_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rubric_json`)),
  `order_number` int(11) DEFAULT NULL,
  PRIMARY KEY (`summative_q_id`),
  KEY `idx_summative` (`summative_id`),
  CONSTRAINT `summative_questions_ibfk_1` FOREIGN KEY (`summative_id`) REFERENCES `summative_assessments` (`summative_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `summative_questions`
--

LOCK TABLES `summative_questions` WRITE;
/*!40000 ALTER TABLE `summative_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `summative_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_feedback`
--

DROP TABLE IF EXISTS `teacher_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_feedback` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `feedback_text` text DEFAULT NULL,
  `requested_change` text DEFAULT NULL,
  `ai_response` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','implemented') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`feedback_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `idx_topic` (`topic_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `teacher_feedback_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_feedback_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_feedback`
--

LOCK TABLES `teacher_feedback` WRITE;
/*!40000 ALTER TABLE `teacher_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_feedback_ai`
--

DROP TABLE IF EXISTS `teacher_feedback_ai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_feedback_ai` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `note_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `feedback` text DEFAULT NULL,
  `accepted` tinyint(1) DEFAULT 0,
  `modified_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`feedback_id`),
  KEY `note_id` (`note_id`),
  CONSTRAINT `teacher_feedback_ai_ibfk_1` FOREIGN KEY (`note_id`) REFERENCES `ai_generated_notes` (`note_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_feedback_ai`
--

LOCK TABLES `teacher_feedback_ai` WRITE;
/*!40000 ALTER TABLE `teacher_feedback_ai` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_feedback_ai` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_modules`
--

DROP TABLE IF EXISTS `teacher_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_module` (`teacher_id`,`module_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `teacher_modules_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_modules_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_modules`
--

LOCK TABLES `teacher_modules` WRITE;
/*!40000 ALTER TABLE `teacher_modules` DISABLE KEYS */;
INSERT INTO `teacher_modules` VALUES (3,7,5,'2026-05-10 07:48:06'),(4,7,9,'2026-05-10 07:48:25'),(5,7,8,'2026-05-10 07:48:33'),(7,12,6,'2026-05-10 08:02:06'),(8,7,2,'2026-05-10 08:04:02');
/*!40000 ALTER TABLE `teacher_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topic_progress`
--

DROP TABLE IF EXISTS `topic_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topic_progress` (
  `progress_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `resource_read` tinyint(1) DEFAULT 0,
  `video_watched` tinyint(1) DEFAULT 0,
  `quiz_passed` tinyint(1) DEFAULT 0,
  `quiz_score` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`progress_id`),
  KEY `student_id` (`student_id`),
  KEY `topic_id` (`topic_id`),
  CONSTRAINT `topic_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `topic_progress_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topic_progress`
--

LOCK TABLES `topic_progress` WRITE;
/*!40000 ALTER TABLE `topic_progress` DISABLE KEYS */;
INSERT INTO `topic_progress` VALUES (1,9,25,1,0,0,NULL,'2026-05-06 08:50:38');
/*!40000 ALTER TABLE `topic_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topic_quizzes`
--

DROP TABLE IF EXISTS `topic_quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topic_quizzes` (
  `quiz_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `passing_score` int(11) DEFAULT 70,
  `time_limit_minutes` int(11) DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `question_type` enum('multiple_choice','multiple_selection','matching','fill_table','true_false','short_answer','essay') DEFAULT 'multiple_choice',
  `correct_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`correct_options`)),
  `matching_left` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`matching_left`)),
  `matching_right` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`matching_right`)),
  `correct_matches` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`correct_matches`)),
  `table_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`table_headers`)),
  `table_rows` int(11) DEFAULT 1,
  `correct_table_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`correct_table_values`)),
  PRIMARY KEY (`quiz_id`),
  KEY `idx_topic_quiz` (`topic_id`),
  CONSTRAINT `topic_quizzes_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topic_quizzes`
--

LOCK TABLES `topic_quizzes` WRITE;
/*!40000 ALTER TABLE `topic_quizzes` DISABLE KEYS */;
INSERT INTO `topic_quizzes` VALUES (1,26,70,30,'2026-04-28 09:01:20','multiple_choice',NULL,NULL,NULL,NULL,NULL,1,NULL),(2,29,70,30,'2026-04-28 09:22:27','multiple_choice',NULL,NULL,NULL,NULL,NULL,1,NULL),(3,29,70,30,'2026-04-28 10:59:06','multiple_choice',NULL,NULL,NULL,NULL,NULL,1,NULL),(4,29,70,30,'2026-04-28 11:28:04','multiple_choice',NULL,NULL,NULL,NULL,NULL,1,NULL),(5,28,70,40,'2026-04-28 13:41:05','multiple_choice',NULL,NULL,NULL,NULL,NULL,1,NULL);
/*!40000 ALTER TABLE `topic_quizzes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topic_resources`
--

DROP TABLE IF EXISTS `topic_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topic_resources` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `resource_type` enum('note','video','link','pdf','image','exercise') NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `source` enum('ai_generated','teacher_added') DEFAULT 'teacher_added',
  `is_approved` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `required_for_quiz` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`resource_id`),
  KEY `topic_id` (`topic_id`),
  CONSTRAINT `topic_resources_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`topic_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topic_resources`
--

LOCK TABLES `topic_resources` WRITE;
/*!40000 ALTER TABLE `topic_resources` DISABLE KEYS */;
INSERT INTO `topic_resources` VALUES (1,25,'note','NUMBER CONVERSION','NUMBER SYSTEM CONVERSION HANDOUT\r\n🔹 1. Introduction\r\n\r\nA number system defines how numbers are represented using digits.\r\n\r\nSystem	Base	Digits Used\r\nDecimal	10	0 – 9\r\nBinary	2	0, 1\r\nOctal	8	0 – 7\r\nHexadecimal	16	0 – 9, A – F\r\n🔹 2. Decimal → Binary\r\nMethod:\r\nDivide number by 2\r\nRecord remainders\r\nRead bottom → top\r\nExample:\r\n\r\nConvert 13₁₀ to binary\r\n\r\nDivision	Quotient	Remainder\r\n13 ÷ 2	6	1\r\n6 ÷ 2	3	0\r\n3 ÷ 2	1	1\r\n1 ÷ 2	0	1\r\n\r\n👉 13₁₀ = 1101₂\r\n\r\n🔹 3. Binary → Decimal\r\nMethod:\r\n\r\nMultiply each digit by powers of 2\r\n\r\nExample:\r\n\r\nConvert 1011₂ to decimal\r\n\r\n1×2\r\n3\r\n+0×2\r\n2\r\n+1×2\r\n1\r\n+1×2\r\n0\r\n=8+0+2+1=11\r\n\r\n👉 1011₂ = 11₁₀\r\n\r\n🔹 4. Decimal → Octal\r\nMethod:\r\nDivide number by 8\r\nRecord remainders\r\nRead bottom → top\r\nExample:\r\n\r\nConvert 83₁₀ to octal\r\n\r\nDivision	Quotient	Remainder\r\n83 ÷ 8	10	3\r\n10 ÷ 8	1	2\r\n1 ÷ 8	0	1\r\n\r\n👉 83₁₀ = 123₈',NULL,NULL,NULL,'teacher_added',1,0,'2026-04-27 20:03:06',1),(2,26,'note','AI Generated Notes','# Boolean logic gates\r\n\r\n## Overview\r\nThis is AI-generated content for **Boolean logic gates**. The system has analyzed the curriculum and prepared comprehensive learning materials.\r\n\r\n## Key Concepts\r\n1. First key concept explained in detail\r\n2. Second key concept with examples\r\n3. Third key concept with practical applications\r\n\r\n## Learning Objectives\r\nBy the end of this lesson, you will be able to:\r\n- Understand the fundamental concepts\r\n- Apply the knowledge in real scenarios\r\n- Analyze and evaluate different approaches\r\n\r\n## Detailed Explanation\r\n\r\n### Section 1: Introduction\r\nLorem ipsum dolor sit amet, consectetur adipiscing elit.\r\n\r\n### Section 2: Core Concepts\r\nSed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\r\n\r\n### Section 3: Examples and Applications\r\nUt enim ad minim veniam, quis nostrud exercitation ullamco.\r\n\r\n## Summary\r\n- Point 1: Key takeaway\r\n- Point 2: Important reminder\r\n- Point 3: What to remember\r\n\r\n## Next Steps\r\nPractice with the exercises below and review the video materials.',NULL,NULL,NULL,'ai_generated',1,0,'2026-04-28 08:29:42',1),(3,26,'video','Introduction to Boolean logic gates',NULL,'https://www.youtube.com/embed/example1',NULL,NULL,'ai_generated',1,0,'2026-04-28 08:29:43',1),(4,26,'video','Deep Dive into Boolean logic gates',NULL,'https://www.youtube.com/embed/example2',NULL,NULL,'ai_generated',1,0,'2026-04-28 08:29:43',1),(5,26,'video','Practical Applications',NULL,'https://www.youtube.com/embed/example3',NULL,NULL,'ai_generated',1,0,'2026-04-28 08:29:43',1),(6,26,'link','Official Documentation',NULL,'https://developer.mozilla.org/en-US/',NULL,NULL,'ai_generated',1,0,'2026-04-28 08:29:43',1),(7,26,'link','Tutorial and Examples',NULL,'https://www.w3schools.com/',NULL,NULL,'ai_generated',1,0,'2026-04-28 08:29:43',1),(8,26,'link','Community Resources',NULL,'https://stackoverflow.com/',NULL,NULL,'ai_generated',1,0,'2026-04-28 08:29:43',1),(9,49,'note','Lesson Notes','# Node.js Key Concepts\r\n\r\n## Overview\r\nThis is AI-generated content for **Node.js Key Concepts**.\r\n\r\n📝 **Based on your feedback:** prepare notes on node js concept\r\n\r\n## Key Concepts\r\n1. First key concept explained in detail\r\n2. Second key concept with examples\r\n3. Third key concept with practical applications\r\n\r\n## Learning Objectives\r\n- Understand the fundamental concepts\r\n- Apply the knowledge in real scenarios\r\n\r\n## Detailed Explanation\r\n\r\n### Introduction\r\nComprehensive explanation of the topic.\r\n\r\n### Core Concepts\r\nDetailed breakdown of main ideas.\r\n\r\n### Examples and Applications\r\nReal-world examples and use cases.\r\n\r\n## Summary\r\n- Key takeaway 1\r\n- Key takeaway 2\r\n- Key takeaway 3',NULL,NULL,NULL,'ai_generated',1,0,'2026-05-06 07:42:56',1),(10,49,'video','Introduction to Node.js Key Concepts',NULL,'https://www.youtube.com/embed/example',NULL,NULL,'ai_generated',1,0,'2026-05-06 07:42:56',1),(11,49,'link','Documentation',NULL,'https://developer.mozilla.org/',NULL,NULL,'ai_generated',1,0,'2026-05-06 07:42:56',1),(12,49,'link','Tutorial',NULL,'https://www.w3schools.com/',NULL,NULL,'ai_generated',1,0,'2026-05-06 07:42:56',1);
/*!40000 ALTER TABLE `topic_resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topics`
--

DROP TABLE IF EXISTS `topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topics` (
  `topic_id` int(11) NOT NULL AUTO_INCREMENT,
  `ic_id` int(11) NOT NULL,
  `topic_title` varchar(255) NOT NULL,
  `topic_order` int(11) DEFAULT 0,
  PRIMARY KEY (`topic_id`),
  KEY `ic_id` (`ic_id`),
  CONSTRAINT `topics_ibfk_1` FOREIGN KEY (`ic_id`) REFERENCES `indicative_contents` (`ic_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=311 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topics`
--

LOCK TABLES `topics` WRITE;
/*!40000 ALTER TABLE `topics` DISABLE KEYS */;
INSERT INTO `topics` VALUES (1,1,'Description of database fundamental',1),(2,1,'Identification of database models',2),(3,1,'Identification of database Relationship',3),(4,1,'Determination of data types',4),(5,2,'Description of data dictionary',1),(6,3,'Identification of database requirements',1),(7,4,'Description of database schema',1),(8,4,'Design of conceptual database schema',2),(9,5,'Design of logical database schema',1),(10,5,'Convert conceptual database schema to logical schema',2),(11,6,'Data normalization',1),(12,6,'Indexing',2),(13,7,'Description of DBMS',1),(14,7,'Preparation of DBMS Environment (MySQL)',2),(15,7,'Convert logic database schema to physical database schema',3),(16,8,'Data definition language commands',1),(17,9,'Data manipulation language commands',1),(18,10,'Data Query Language commands',1),(19,11,'Data control language commands',1),(20,12,'Transaction Control Language commands',1),(21,13,'Access control enforcement',1),(22,14,'Auditing and log management',1),(23,15,'Data encryption implementation',1),(24,16,'Backup and recovery configuration',1),(25,17,'Number system conversions',1),(26,18,'Boolean logic gates',1),(27,19,'Data types in JavaScript',1),(28,19,'JavaScript operators',2),(29,20,'Algorithm writing',1),(30,20,'Flowchart design',2),(31,21,'Classifications',1),(32,21,'Searching techniques',2),(33,21,'Time and Space complexity',3),(34,21,'Sorting classification',4),(35,22,'Sorting algorithms',1),(36,23,'Arrays',1),(37,23,'Linked Lists',2),(38,23,'Queue and Stack',3),(39,24,'Trees',1),(40,24,'Graphs',2),(41,24,'Tables',3),(42,25,'JavaScript environment',1),(43,25,'Data structures implementation',2),(44,25,'Sorting implementation',3),(45,25,'Searching implementation',4),(46,26,'Browser tools',1),(47,26,'IDE terminal',2),(48,27,'Time and space complexity',1),(49,28,'Node.js Key Concepts',1),(50,28,'Installation of Node.js Modules and packages',2),(51,28,'Configuration of MySQL Server',3),(52,29,'Creation of basic server with Express.js',1),(53,29,'Application of Client Libraries',2),(54,29,'Establishment of server connection',3),(55,29,'Test of Server Connection',4),(56,30,'Database setup',1),(57,31,'Define endpoints and HTTP Methods',1),(58,31,'Implementation of API endpoints',2),(59,31,'Use of Middleware services',3),(60,31,'Database Operations',4),(61,31,'Debugging RESTFUL APIs',5),(62,32,'Introduction to data encryption',1),(63,32,'Steps in securing RESTFUL APIs',2),(64,33,'Installing Node.js Package Manager (NPM)',1),(65,33,'Incorporating common Node.js third-party libraries',2),(66,33,'Interacting with third-party libraries',3),(67,34,'Monitoring dependencies',1),(68,34,'Checking for updates and vulnerabilities',2),(69,34,'Updating libraries safely',3),(70,34,'Managing library updates',4),(71,35,'Principles of authentication',1),(72,35,'Role of authentication in system security',2),(73,35,'Implementing user authentication in Node.js',3),(74,35,'Using authentication middleware to protect routes',4),(75,35,'Best practices for password storage',5),(76,36,'Principles of authorization',1),(77,36,'Role of authorization in system security',2),(78,36,'Implementing access control in Node.js',3),(79,36,'Using authorization middleware',4),(80,36,'Custom authorization logic',5),(81,37,'Principles of accountability',1),(82,37,'Roles of Accountability in system security',2),(83,37,'Implementing logging and auditing',3),(84,37,'Logs management',4),(85,38,'Types of information stored',1),(86,38,'Security risks and best practices',2),(87,38,'Implementing security measures',3),(88,38,'Storing environment variables',4),(89,38,'Loading environment variables using dotenv',5),(90,39,'Logging and auditing features',1),(91,39,'Monitoring changes',2),(92,39,'Managing and rotating environment variables',3),(93,40,'Introduction to unit tests',1),(94,40,'Mocha Testing Framework',2),(95,40,'Chai assertion library',3),(96,40,'Monitor Test results',4),(97,41,'Introduction to Usability tests',1),(98,41,'Postman Testing Tool',2),(99,41,'Puppeteer Testing Tool',3),(100,42,'Node.js Security vulnerabilities',1),(101,42,'Tools for Security Testing',2),(102,42,'Secure Coding Practices',3),(103,42,'Testing Techniques',4),(104,42,'Best Practices for Security Testing',5),(105,42,'Implement Security Testing',6),(106,42,'Penetration Testing steps',7),(107,42,'OWASP Penetration Testing',8),(108,43,'Description of NodeJS application deployment',1),(109,43,'Types of deployment',2),(110,43,'Deployment tools',3),(111,44,'Copy source code to server',1),(112,44,'Install dependencies',2),(113,44,'Start application using command line',3),(114,45,'Best practices',1),(115,45,'Maintenance plan',2),(116,45,'Continuous maintenance',3),(117,46,'Documentation Overview',1),(118,46,'Importance of documentation',2),(119,46,'Types of documentation',3),(120,46,'Documentation tools',4),(121,46,'Best practices for documentation',5),(122,46,'Publishing Documentation',6),(123,46,'Documentation Maintenance',7),(124,152,'Definitions of key terms',1),(125,152,'Identification of Linux distributions',2),(126,152,'Installation of Linux operating system',3),(127,153,'System Information',1),(128,153,'File and Directory Management',2),(129,153,'Text Processing',3),(130,153,'Process Management',4),(131,153,'Package Management',5),(132,153,'User and Group Management',6),(133,153,'System Control',7),(134,154,'Description of server services',1),(135,154,'Configure server services',2),(136,155,'Definitions of key terms',1),(137,155,'Evolution of DevOps and its importance',2),(138,155,'DevOps advantages and Disadvantages',3),(139,155,'Description of DevOps technologies',4),(140,155,'Description of devOps principles',5),(141,155,'Description of DevOps lifecycle',6),(142,155,'Identification of technologies used in system to be deployed',7),(143,155,'Selection of deployment technologies and tools',8),(144,155,'Installation of system dependencies',9),(145,156,'Select CD tools',1),(146,156,'Performing Continuous integration (CI)',2),(147,156,'Continuous deployment (CD)',3),(148,157,'Identification of containerisation tools',1),(149,157,'Setup docker',2),(150,157,'Build Docker Images',3),(151,157,'Store Docker Images',4),(152,157,'Implement Continuous Integration',5),(153,158,'Identify data migration best practice',1),(154,158,'Selecting the Right Tools & Technology',2),(155,158,'Creating a data migration pipeline',3),(156,158,'Implement Continuous Integration',4),(157,159,'Benefits of DevOps monitoring',1),(158,159,'Importance of monitoring tools',2),(159,159,'Identification of monitoring tools types',3),(160,159,'Installation of monitoring tools',4),(161,160,'Introduce performance metrics and Feedback Data',1),(162,160,'Describe significance of Data Analysis',2),(163,160,'Describe types of data in Devops',3),(164,160,'Utilizing Monitoring Tools',4),(165,160,'Analysing Data in DevOps',5),(166,161,'Executive Summary',1),(167,161,'Key Metrics',2),(168,161,'Report findings',3),(169,161,'Trends Analysis',4),(170,161,'Alerts and Incidents',5),(171,161,'Action Items',6),(172,161,'Optimization or remediation',7),(173,161,'Conclusion',8),(174,161,'Appendix (Include additional details, charts, graphs, or raw data)',9),(175,162,'Introduction to blockchain',1),(176,162,'Description of blockchain key concepts',2),(177,162,'Apply blockchain use cases',3),(178,163,'Description of Blockchain technology stack principles',1),(179,163,'Describe types of Consensus mechanism',2),(180,163,'Use appropriate Consensus Mechanism (Proof of Work, Proof of Stake)',3),(181,163,'Identify the types of attacks and vulnerabilities of blockchain',4),(182,164,'Description of blockchain architecture',1),(183,164,'Designing system architecture',2),(184,164,'Drawing blockchain architecture',3),(185,165,'Description of key terms',1),(186,165,'Set up solidity environment',2),(187,166,'Data types and variables',1),(188,166,'Use of functions',2),(189,166,'Control structures',3),(190,166,'Arrays and structs',4),(191,166,'Events and logging',5),(192,166,'Error handling',6),(193,167,'Connect to wallet',1),(194,167,'Access the Contract Address',2),(195,167,'Use a Blockchain Explorer',3),(196,167,'Perform function operations',4),(197,168,'Proper analysis of Gas cost',1),(198,168,'Elaboration of Storage',2),(199,168,'Optimization of Memory cost',3),(200,169,'Applying of Solidity programming language',1),(201,169,'Writing smart contract',2),(202,170,'Implementation of Fungible token (FT) standards',1),(203,170,'Implementation of Non‑Fungible Token standards (NFT)',2),(204,171,'Protection of smart contracts against Re‑entrancy Attack',1),(205,171,'Securing smart contract using Escrow Service Contract',2),(206,171,'Usage of third‑party libraries',3),(207,172,'Selection of development blockchain network',1),(208,172,'Create infrastructure services for blockchain applications',2),(209,172,'Deploy contract',3),(210,173,'Configure contract network',1),(211,173,'Connect to smart contract wallet using frontend',2),(212,174,'Consume smart contract functions based on defined functionalities',1),(213,174,'Create instance of smart contract',2),(214,175,'Implement operations based on smart contract predefined functions',1),(215,175,'Deploy web3 frontend based on specific requirements',2),(216,176,'Definition of key concepts',1),(217,176,'Introduction',2),(218,176,'Installation of NodeJS and Node Package Manager (NPM)',3),(219,176,'Creating React Application',4),(220,176,'Explore React project structure',5),(221,176,'Installation of additional React tools and libraries (React Developer tools)',6),(222,177,'React components',1),(223,177,'JSX (JavaScript XML)',2),(224,177,'Props (Properties)',3),(225,177,'Lifecycle Methods',4),(226,178,'Installing React Route',1),(227,178,'Configuring Routes',2),(228,178,'Basic React Navigation',3),(229,178,'Handling 404 Pages',4),(230,178,'Redirects',5),(231,178,'URL Parameters',6),(232,178,'Nested Routing',7),(233,179,'Identifying hooks',1),(234,179,'Hook selection and combination',2),(235,179,'Optimizing Performance',3),(236,179,'Handling Complex State Logic',4),(237,179,'Managing Global State',5),(238,180,'Description of Events',1),(239,180,'Debouncing and Throttling Events',2),(240,180,'Using Controlled Components',3),(241,180,'Passing Arguments to Event Handlers',4),(242,180,'Use Custom Hooks for Event Listeners',5),(243,180,'Handling Events on Dynamic Lists',6),(244,181,'Initial Setup and Planning',1),(245,181,'Organizing API Calls',2),(246,181,'Performing API Security and testing',3),(247,182,'Integrating of Tailwind CSS in React.JS',1),(248,182,'Using Utility-First Fundamentals',2),(249,182,'Handling Hover, Focus, and Other States',3),(250,182,'Animation and Transitions',4),(251,182,'Flexbox and Grid',5),(252,182,'Reusing Styles',6),(253,182,'Adding Custom Styles',7),(254,182,'Functions & Directives',8),(255,183,'Mobile-First Approach',1),(256,183,'Flexible Grid Layouts',2),(257,183,'Responsive Images and Media',3),(258,183,'Media Queries and Breakpoints',4),(259,183,'Typography and Readability',5),(260,183,'Interactive Elements',6),(261,183,'Testing and Iteration',7),(262,184,'Extending the Default Theme',1),(263,184,'Adding Custom Variants',2),(264,184,'Custom Fonts and Typography',3),(265,184,'Customizing Colors',4),(266,184,'Plugins for Additional Functionality',5),(267,184,'Custom Directives for Complex Designs',6),(268,184,'Conditional Styles with JavaScript',7),(269,185,'Environment setup',1),(270,185,'Implementing interface of variables',2),(271,185,'Handling functions in TypeScript',3),(272,185,'Data Handling',4),(273,186,'Preparation of Environment',1),(274,186,'Project Creation',2),(275,186,'Initial Development',3),(276,187,'Static Site Generation (SSG)',1),(277,187,'Server-Side Rendering (SSR)',2),(278,187,'Incremental Static Regeneration (ISR)',3),(279,187,'Client-Side Rendering (CSR)',4),(280,188,'Description of key concepts',1),(281,188,'Linking Components',2),(282,188,'Programmatic Navigation',3),(283,188,'Dynamic Routes',4),(284,188,'Query Parameters',5),(285,189,'Define the API Endpoint',1),(286,189,'Handling Request Types',2),(287,189,'Using Dynamic API Routes',3),(288,189,'Testing your API',4),(289,190,'Performing Client-Side Security',1),(290,190,'Performing Server-Side Security',2),(291,190,'Performing General Security Measures',3),(292,191,'Leverage Progressive Enhancement',1),(293,191,'Prioritize Mobile-First Design',2),(294,191,'Utilize Performance Optimization Techniques',3),(295,192,'Creating and Configuring the Manifest File',1),(296,192,'Referencing the Manifest in Your HTML',2),(297,192,'Testing and Validation',3),(298,193,'Describe Service workers',1),(299,193,'Registration and Installation',2),(300,193,'Caching Strategy Implementation',3),(301,193,'Updating service worker',4),(302,194,'Set variables (backend host, ftp host, ftp-user, ftp-pass …)',1),(303,194,'Setup storage environment',2),(304,195,'Run Build Script in React Application',1),(305,195,'Configure Deployment Platform (Vercel)',2),(306,195,'Migrate the application files',3),(307,195,'Test the Deployed Application',4),(308,196,'Description of DNS',1),(309,196,'Configure DNS and SSL Settings',2),(310,196,'Testing and verification',3);
/*!40000 ALTER TABLE `topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trades`
--

DROP TABLE IF EXISTS `trades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trades` (
  `trade_id` int(11) NOT NULL AUTO_INCREMENT,
  `sector_id` int(11) NOT NULL,
  `trade_name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`trade_id`),
  UNIQUE KEY `unique_trade_per_sector` (`sector_id`,`trade_name`),
  KEY `idx_sector` (`sector_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `trades_ibfk_1` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`sector_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trades`
--

LOCK TABLES `trades` WRITE;
/*!40000 ALTER TABLE `trades` DISABLE KEYS */;
INSERT INTO `trades` VALUES (1,1,'Software Development','SD001','Application development, programming, web development','active','2026-04-25 12:41:09'),(2,1,'Database Administration','DB001','Database design, management, SQL, data security','active','2026-04-25 12:41:09'),(3,1,'Networking','NT001','Network setup, configuration, maintenance, security','active','2026-04-25 12:41:09'),(7,7,'Marketing','MKT01','Digital marketing, brand management, sales','active','2026-04-25 12:41:09'),(9,3,'Carpentry','CAR01','Woodwork, furniture making, construction carpentry','active','2026-04-25 12:41:09'),(10,3,'Masonry','MAS01','Bricklaying, concrete work, plastering','active','2026-04-25 12:41:09'),(11,3,'Plumbing','PLU01','Pipe installation, water systems, sanitation','active','2026-04-25 12:41:09'),(12,3,'Electrical Installation','ELE01','Wiring, electrical systems, safety','active','2026-04-25 12:41:09');
/*!40000 ALTER TABLE `trades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activity_log`
--

DROP TABLE IF EXISTS `user_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_activity_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `action_details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_user_activity` (`user_id`,`created_at`),
  KEY `idx_action_type` (`action_type`),
  CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activity_log`
--

LOCK TABLES `user_activity_log` WRITE;
/*!40000 ALTER TABLE `user_activity_log` DISABLE KEYS */;
INSERT INTO `user_activity_log` VALUES (1,1,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-04-27 16:55:40'),(2,1,'delete','Deleted module ID: 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/admin/modules.php','2026-04-27 16:57:08'),(3,1,'deactivate','Deactivated user ID: 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/admin/users.php','2026-04-27 16:58:28'),(4,1,'activate','Activated user ID: 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/admin/users.php','2026-04-27 16:58:40'),(5,1,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-04-27 17:04:01'),(6,1,'deactivate','Deactivated user ID: 7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/admin/users.php','2026-04-27 17:04:25'),(7,1,'activate','Activated user ID: 7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/admin/users.php','2026-04-27 17:04:42'),(8,1,'approve','Approved teacher ID: 7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/admin/users.php','2026-04-27 17:05:19'),(9,1,'deactivate','Deactivated user ID: 8','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/admin/users.php','2026-04-27 17:05:36'),(10,1,'activate','Activated user ID: 8','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/admin/users.php','2026-04-27 17:05:41'),(11,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-04-27 17:06:21'),(12,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-04-28 07:59:27'),(13,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php?error=session_expired','2026-04-29 05:19:42'),(14,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-04-30 18:01:30'),(15,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-05-05 17:45:48'),(16,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-05-05 18:23:58'),(17,9,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-05-05 19:29:55'),(18,9,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-05-05 22:50:35'),(20,9,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-05-06 06:47:51'),(21,1,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-05-06 07:09:58'),(22,1,'approve','Approved teacher ID: 12','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/api/v1/admin.php','2026-05-06 07:10:44'),(23,1,'verify','Verified company ID: 10','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/api/v1/admin.php','2026-05-06 07:11:01'),(24,12,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-06 07:23:55'),(25,12,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-06 07:29:43'),(26,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-06 07:31:21'),(27,9,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-05-06 07:59:56'),(28,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-05-06 08:05:16'),(29,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php?error=session_expired','2026-05-06 10:36:09'),(30,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php','2026-05-06 12:41:53'),(31,1,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-06 12:42:10'),(32,1,'verify','Verified company ID: 16','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','/skilltree_tvet_platform/api/v1/admin.php','2026-05-06 12:42:33'),(34,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php','2026-05-06 13:01:45'),(35,1,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php','2026-05-06 14:23:04'),(36,1,'verify','Verified company ID: 17','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/api/v1/admin.php','2026-05-06 14:23:29'),(37,1,'deactivate','Deactivated user ID: 17','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/users.php','2026-05-06 14:24:40'),(38,1,'activate','Activated user ID: 17','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/users.php','2026-05-06 14:24:53'),(39,1,'approve','Approved teacher ID: 13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/users.php','2026-05-06 14:25:33'),(40,1,'approve','Approved teacher ID: 11','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/users.php','2026-05-06 14:25:54'),(41,17,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-06 14:26:34'),(42,1,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php','2026-05-06 14:47:22'),(43,1,'deactivate','Deactivated user ID: 17','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/users.php','2026-05-06 14:47:49'),(44,1,'activate','Activated user ID: 17','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/users.php','2026-05-06 14:47:56'),(45,17,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-06 15:07:44'),(46,1,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php','2026-05-06 15:22:12'),(47,17,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-06 15:28:35'),(48,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-07 12:01:57'),(49,9,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-07 12:05:02'),(50,9,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php','2026-05-07 13:28:21'),(51,17,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php','2026-05-07 13:30:14'),(52,9,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-07 14:11:24'),(53,1,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php','2026-05-08 17:52:09'),(54,1,'approve_teacher','Approved teacher ID: 22','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/dashboard.php','2026-05-08 17:52:47'),(55,1,'approve_teacher','Approved teacher ID: 18','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/dashboard.php?msg=Teacher+approved.','2026-05-08 17:53:00'),(56,1,'deactivate','Deactivated user ID: 18','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/users.php','2026-05-08 17:54:55'),(57,1,'delete','Deleted user ID: 18','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/users.php','2026-05-08 17:55:55'),(58,1,'deactivate_student','Deactivated student ID: 21','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/students.php','2026-05-08 17:58:39'),(59,1,'delete_student','Deleted student ID: 21','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/students.php?msg=Student+deactivated+successfully%21','2026-05-08 17:58:59'),(60,1,'delete_teacher','Deleted teacher ID: 13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/teachers.php','2026-05-08 18:04:51'),(61,1,'deactivate_teacher','Deactivated teacher ID: 12','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/teachers.php?msg=Teacher+deleted+successfully%21','2026-05-08 18:04:58'),(62,1,'delete','Deleted trade ID: 6','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/trades.php','2026-05-08 18:07:13'),(63,1,'deactivate_company','Deactivated company ID: 16','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/companies.php','2026-05-08 18:09:49'),(64,1,'delete_company','Deleted company ID: 16','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/companies.php?msg=Company+deactivated+successfully%21','2026-05-08 18:10:02'),(65,1,'delete_company','Deleted company ID: 10','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/companies.php','2026-05-08 18:20:39'),(66,1,'edit','Edited module ID: 2','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/modules.php','2026-05-08 18:24:30'),(67,1,'create','Added module: SWDOT501','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/modules.php?search=&type=all&sector=all&level=all&status=draft','2026-05-08 18:28:22'),(68,1,'edit','Edited module ID: 5','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/modules.php','2026-05-08 18:52:54'),(69,1,'edit','Edited module ID: 7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/modules.php','2026-05-08 19:08:39'),(70,1,'edit','Edited module ID: 5','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/modules.php','2026-05-08 19:10:15'),(71,1,'edit','Edited module ID: 7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/modules.php','2026-05-08 19:27:05'),(72,1,'delete','Deleted module ID: 7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/modules.php','2026-05-08 19:42:04'),(73,1,'create','Added module: SWDOT501','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/admin/modules.php','2026-05-08 19:47:56'),(74,1,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php','2026-05-08 20:52:28'),(75,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-08 20:53:10'),(76,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','/skilltree_tvet_platform/includes/auth/login.php','2026-05-10 06:28:50'),(77,1,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-10 07:34:49'),(78,1,'create','Added module: SWDFA501','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','/skilltree_tvet_platform/admin/modules.php','2026-05-10 07:53:23'),(79,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-10 08:02:24'),(80,1,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','/skilltree_tvet_platform/includes/auth/login.php','2026-05-10 08:03:16'),(81,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-10 08:04:19'),(82,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Avast/146.0.34394.179','/skilltree_tvet_platform/includes/auth/login.php','2026-05-10 09:58:50'),(83,9,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Avast/146.0.34394.179','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-10 17:53:10'),(84,8,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Avast/146.0.34394.179','/skilltree_tvet_platform/includes/auth/login.php','2026-05-10 18:12:17'),(85,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Avast/146.0.34394.179','/skilltree_tvet_platform/includes/auth/login.php','2026-05-10 19:57:30'),(86,8,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Avast/146.0.34394.179','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-10 20:07:19'),(87,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Avast/146.0.34394.179','/skilltree_tvet_platform/includes/auth/login.php','2026-05-10 20:36:49'),(88,8,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Avast/146.0.34394.179','/skilltree_tvet_platform/includes/auth/login.php?message=logged_out','2026-05-10 20:39:02');
/*!40000 ALTER TABLE `user_activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `role` enum('admin','teacher','student','company') NOT NULL,
  `sector` varchar(100) DEFAULT NULL,
  `trade` varchar(100) DEFAULT NULL,
  `rqf_level` int(11) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `assigned_modules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`assigned_modules`)),
  `is_approved` tinyint(1) DEFAULT 0,
  `company_name` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `company_registration_number` varchar(100) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `preferences` text DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `idx_unique_phone` (`contact_phone`),
  KEY `idx_role` (`role`),
  KEY `idx_email` (`email`),
  KEY `idx_sector_trade` (`sector`,`trade`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_login_token` (`login_token`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `qualification` text DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`teacher_id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin@tvet.rw','$2y$12$Fek2sr60ncVDi1R795kdo.wmgCKeeSXOx2KOBMXGEqtGx/xlaoTWi','System Administrator','admin',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'0726062138',NULL,1,'2026-05-10 08:03:16','9dad4a31772501669898fad33b24ed7cdf08494392dec97ff8013945082cb4f3',NULL,NULL,'2026-04-25 12:04:35','2026-05-10 08:03:16',NULL),(7,'annonciatha@skilltree.com','$2y$12$4R.h2uXWWDckq2Pr6xgJLeJXS4HAmVWPHC.fwNwYvlBeWDJqnouzW','Annonciatha MUKARUGWIZA','teacher',NULL,NULL,NULL,'TCH1777309210','ICT AND MULTMEDIA',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'07878496860',NULL,1,'2026-05-10 20:36:49','222f1e3ebdb8cac55b00f1c95fd216ac46a031f0987ed2694e33f1c182b04d5f',NULL,NULL,'2026-04-27 17:00:10','2026-05-10 20:36:49',NULL),(8,'kangabo@gmail.com','$2y$12$WbUu4zlNIZbrmOXCuyV5mu7EV40CAgvNHofTa1JVre0fCanS1/que','kangabo perpetue','student','','Software Development',4,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'0788888888',NULL,1,'2026-05-10 20:39:02','7ed5f469d29476deff146a3a1a2624a71af7c8c74ebff9b4393ac3acd3610e7a',NULL,NULL,'2026-04-27 17:03:36','2026-05-10 20:39:02',NULL),(9,'rozeizabayo01@gmail.com','$2y$10$2B2BtdNjUi.oEYrzMIeHXOW8sa9D9UPongl8ezUY5KmjIO0I/5XVm','IZABAYO MARIE ROSE','student','','Software Development',3,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'0782513700',NULL,1,'2026-05-10 17:53:09','94214914de931e6ff1f45e7da6dc6ff506306f55b2d00a7569263a235822e872',NULL,NULL,'2026-05-05 19:29:24','2026-05-10 17:53:09',NULL),(11,'kanani@skilltree.com','$2y$12$/Ge0vkc1hXaI3yVFEjwH9uNqpIygquLLwSs32i7zezgib2kJP3WIS','kanani vincent','teacher',NULL,NULL,NULL,'TCH1778051131','ICT AND MULTMEDIA',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'0789876543',NULL,1,NULL,NULL,NULL,NULL,'2026-05-06 07:05:31','2026-05-06 14:25:54',NULL),(12,'kananivincent@skilltree.com','$2y$12$ijG8cPZ8g1Nb.lIvacHOouBze/j.AEjpM.42FqnVtWcna2r9qxfZa','kanani vincent','teacher',NULL,NULL,NULL,'TCH1778051189','ICT AND MULTMEDIA',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'0789876543',NULL,0,'2026-05-06 07:29:43','8bba2f27fd45bb4de2b0c2478d1c34ab737f5939dc47bbbdc06ebc18bd33855f',NULL,NULL,'2026-05-06 07:06:29','2026-05-08 18:04:57',NULL),(17,'kizitotech@gmail.com','$2y$10$QwkNiTSk8tvRvE7LEAu/E.cWLAYqN9xyMYitBPtEPmsk7cTmfzIJK','KIZITO TECH SOLUTION','company',NULL,NULL,NULL,NULL,NULL,NULL,1,'KIZITO TECH SOLUTION',NULL,NULL,'ICT','Gisagara,Rwanda','https://www.kizito.edu.rw',NULL,'0784567891',1,NULL,NULL,1,'2026-05-07 13:30:14','3c8c1164d3872ff24ba0f34267ba46833d4a49458e0e9efec5d99538430842a8',NULL,NULL,'2026-05-06 14:20:53','2026-05-07 13:30:14',NULL),(19,'valentine@skilltree.com','$2y$12$58Lx50DYqQFkt0vs.HumDukzixsoX/tGzQnZfHYfGlYHrFtUjfS4i','mukantwali valentine','student','','Software Development',4,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'0782513700',NULL,1,NULL,NULL,NULL,NULL,'2026-05-07 18:03:06','2026-05-07 18:03:06',NULL),(20,'valentin@skilltree.com','$2y$12$5sX0wg2VmgwJPzDcVq7QkOO6rbMSoTuF1RwjlGDA8KDs9BMMdT7NS','k00000)))))))))','student','','Software Development',5,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'0782513700',NULL,1,NULL,NULL,NULL,NULL,'2026-05-07 18:05:10','2026-05-07 18:05:10',NULL),(22,'uwizeyenadine@skilltree.com','$2y$12$xPX.3YMUL8xF8ZOKsaZswOX/7M3orKEl1D8XxREJp0hlYMQFkcvi6','Uwizeye nadine','teacher',NULL,NULL,NULL,'TCH1778262690','ICT AND MULTIMEDIA',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'0783393655',NULL,1,NULL,NULL,NULL,NULL,'2026-05-08 17:51:30','2026-05-08 17:52:47',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-11 21:10:45
