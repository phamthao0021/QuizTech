-- QUIZTECH IDEMPOTENT DATABASE SCHEMA SNAPSHOT
-- Database: db_quiz_tech

-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: db_quiz_tech
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
-- Table structure for table `activity_logs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `browser` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ai_datasets`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `ai_datasets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) DEFAULT NULL,
  `dataset_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `total_records` int(11) DEFAULT 0,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `ai_datasets_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ai_generated_questions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `ai_generated_questions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `option_a` text DEFAULT NULL,
  `option_b` text DEFAULT NULL,
  `option_c` text DEFAULT NULL,
  `option_d` text DEFAULT NULL,
  `correct_answer` char(1) DEFAULT NULL,
  `explanation` text DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ai_generated_questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ai_imports`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `ai_imports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(30) DEFAULT NULL,
  `total_questions` int(11) DEFAULT 0,
  `imported_questions` int(11) DEFAULT 0,
  `duplicate_questions` int(11) DEFAULT 0,
  `status` enum('processing','completed','failed') DEFAULT 'processing',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ai_imports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ai_logs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `ai_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `type` enum('generate_questions','analyze','chat','ocr') NOT NULL,
  `input` text DEFAULT NULL,
  `output` text DEFAULT NULL,
  `model` varchar(50) DEFAULT 'mock',
  `tokens_used` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ai_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ai_models`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `ai_models` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model_name` varchar(100) DEFAULT NULL,
  `provider` varchar(100) DEFAULT NULL,
  `version` varchar(50) DEFAULT NULL,
  `api_url` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ai_prompts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `ai_prompts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `prompt` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `badges`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `duration` int(11) DEFAULT 30,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `exam_attempts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `exam_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) unsigned DEFAULT NULL,
  `exam_id` bigint(20) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `started_at` datetime NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT 0,
  `score` decimal(6,2) DEFAULT 0.00,
  `total_questions` int(11) DEFAULT 0,
  `correct_answers` int(11) DEFAULT 0,
  `wrong_answers` int(11) DEFAULT 0,
  `unanswered` int(11) DEFAULT 0,
  `answers_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answers_json`)),
  `percentage` decimal(5,2) DEFAULT 0.00,
  `status` enum('doing','submitted','timeout','cancelled') DEFAULT 'doing',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_id` (`room_id`,`student_id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `exam_attempts_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `exam_rooms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exam_attempts_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_attempts_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
  IF NEW.status='submitted' AND OLD.status<>'submitted' THEN
    INSERT INTO activity_logs (user_id, action, description)
    VALUES (NEW.student_id, 'submit', 'Nộp bài thi');
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `exam_questions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `exam_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` bigint(20) unsigned NOT NULL,
  `question_type` enum('single','multiple','matching','true_false') DEFAULT 'single',
  `question_id` bigint(20) unsigned NOT NULL,
  `question_order` int(11) DEFAULT 1,
  `score` decimal(5,2) DEFAULT 1.00,
  `required` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_exam_question_exam` (`exam_id`),
  KEY `idx_exam_question` (`question_id`),
  KEY `idx_exam_order` (`question_order`),
  CONSTRAINT `exam_questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `exam_rooms`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `exam_rooms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` bigint(20) unsigned NOT NULL,
  `teacher_id` int(10) unsigned NOT NULL,
  `room_code` varchar(20) NOT NULL,
  `room_name` varchar(255) NOT NULL,
  `room_password` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `max_students` int(11) DEFAULT 50,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `allow_late_join` tinyint(1) DEFAULT 0,
  `late_minutes` int(11) DEFAULT 0,
  `auto_start` tinyint(1) DEFAULT 1,
  `auto_close` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('waiting','active','closed','ongoing','finished') DEFAULT 'waiting',
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_code` (`room_code`),
  KEY `exam_id` (`exam_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `room_code_2` (`room_code`),
  CONSTRAINT `exam_rooms_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_rooms_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `exam_submissions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `exam_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` decimal(4,2) DEFAULT 0.00,
  `duration_seconds` int(11) DEFAULT 0,
  `cheat_count` int(11) DEFAULT 0,
  `status` enum('in_progress','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'completed',
  `submitted_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `exams`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `exams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) unsigned NOT NULL,
  `teacher_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `exam_code` varchar(30) DEFAULT NULL,
  `exam_type` enum('practice','quiz','midterm','final','custom') DEFAULT 'quiz',
  `difficulty` enum('easy','medium','hard','mixed') DEFAULT 'mixed',
  `total_questions` int(11) DEFAULT 10,
  `total_score` decimal(6,2) DEFAULT 10.00,
  `duration` int(11) NOT NULL DEFAULT 30,
  `pass_score` decimal(5,2) DEFAULT 5.00,
  `attempt_limit` int(11) DEFAULT 1,
  `shuffle_questions` tinyint(1) DEFAULT 1,
  `shuffle_answers` tinyint(1) DEFAULT 1,
  `show_result` tinyint(1) DEFAULT 1,
  `show_answer` tinyint(1) DEFAULT 0,
  `allow_review` tinyint(1) DEFAULT 1,
  `negative_marking` tinyint(1) DEFAULT 0,
  `negative_score` decimal(4,2) DEFAULT 0.00,
  `password` varchar(255) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `time_limit` int(11) DEFAULT 20,
  `question_count` int(11) DEFAULT 10,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_code` (`exam_code`),
  KEY `idx_exam_subject` (`subject_id`),
  KEY `idx_exam_teacher` (`teacher_id`),
  KEY `idx_exam_status` (`status`),
  KEY `idx_exam_code` (`exam_code`),
  CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `matching_questions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `matching_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) unsigned NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `question_text` text NOT NULL,
  `left_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`left_items`)),
  `right_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`right_items`)),
  `correct_matches` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`correct_matches`)),
  `explanation` text DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `points` int(11) DEFAULT 2,
  `ai_generated` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_matching_subject` (`subject_id`),
  KEY `matching_questions_ibfk_2` (`created_by`),
  CONSTRAINT `matching_questions_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matching_questions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `multiselect_questions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `multiselect_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) unsigned NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `question_text` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `correct_answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`correct_answers`)),
  `explanation` text DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `points` int(11) DEFAULT 2,
  `ai_generated` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_multi_subject` (`subject_id`),
  KEY `multiselect_questions_ibfk_2` (`created_by`),
  CONSTRAINT `multiselect_questions_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `multiselect_questions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ocr_documents`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `ocr_documents` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `extracted_text` longtext DEFAULT NULL,
  `total_pages` int(11) DEFAULT NULL,
  `status` enum('processing','completed','failed') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ocr_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_resets`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `expired_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reset_user` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `practice_answers`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `practice_answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) unsigned NOT NULL,
  `item_token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_answer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `is_timeout` tinyint(1) NOT NULL DEFAULT 0,
  `score_awarded` int(11) NOT NULL DEFAULT 0,
  `explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_practice_answer_session` (`session_id`),
  CONSTRAINT `fk_pv2_ans` FOREIGN KEY (`session_id`) REFERENCES `practice_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `practice_concepts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `practice_concepts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `term` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `definition` varchar(700) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `practice_crossword_clues`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `practice_crossword_clues` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `crossword_id` int(10) unsigned NOT NULL,
  `clue_no` smallint(5) unsigned NOT NULL,
  `direction` enum('across','down') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `row_start` tinyint(3) unsigned NOT NULL,
  `col_start` tinyint(3) unsigned NOT NULL,
  `answer` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `clue` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `crossword_id` (`crossword_id`),
  CONSTRAINT `fk_pv2_cw` FOREIGN KEY (`crossword_id`) REFERENCES `practice_crosswords` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `practice_crosswords`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `practice_crosswords` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rows_count` tinyint(3) unsigned NOT NULL,
  `cols_count` tinyint(3) unsigned NOT NULL,
  `difficulty` enum('easy','medium','hard') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'medium',
  `time_limit_seconds` smallint(5) unsigned NOT NULL DEFAULT 420,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `practice_feedback`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `practice_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'feedback',
  `exam_id` int(11) DEFAULT NULL,
  `question_id` int(11) DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `admin_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_feedback_user_created` (`user_id`,`created_at`),
  KEY `idx_feedback_status_created` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `practice_games`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `practice_games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_type` enum('crossword','concept_match','quick_quiz') NOT NULL,
  `title` varchar(255) NOT NULL,
  `scheduled_date` date NOT NULL,
  `content_json` longtext NOT NULL COMMENT 'Chứa ô chữ, cặp nối từ hoặc danh sách câu hỏi',
  `subject_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_practice_game_type` (`game_type`),
  KEY `idx_practice_scheduled` (`scheduled_date`),
  KEY `idx_practice_subject` (`subject_id`),
  KEY `practice_games_ibfk_2` (`created_by`),
  CONSTRAINT `practice_games_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `practice_games_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `practice_quiz_questions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `practice_quiz_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(900) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `option_a` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `option_b` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `option_c` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `option_d` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `correct_answer` enum('A','B','C','D') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `explanation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `time_limit_seconds` smallint(5) unsigned NOT NULL DEFAULT 15,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `practice_sessions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `practice_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `game_type` enum('crossword','concept_match','quick_quiz') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `daily_key` date NOT NULL,
  `content_id` int(10) unsigned DEFAULT NULL,
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('playing','completed','timeout','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'playing',
  `score` int(11) NOT NULL DEFAULT 0,
  `correct_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `wrong_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `total_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `hints_used` smallint(5) unsigned NOT NULL DEFAULT 0,
  `max_combo` smallint(5) unsigned NOT NULL DEFAULT 0,
  `duration_seconds` int(10) unsigned NOT NULL DEFAULT 0,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ps_user` (`user_id`,`daily_key`),
  CONSTRAINT `fk_pv2_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `qt_notification_reads`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `qt_notification_reads` (
  `notification_id` bigint(20) unsigned NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`,`user_id`),
  KEY `idx_qtnr_user` (`user_id`,`read_at`),
  CONSTRAINT `fk_qtnr_notification` FOREIGN KEY (`notification_id`) REFERENCES `qt_notifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `qt_notifications`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `qt_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(180) NOT NULL,
  `body` text NOT NULL,
  `category` varchar(32) NOT NULL DEFAULT 'system',
  `target_role` varchar(24) NOT NULL DEFAULT 'all',
  `target_user_id` int(11) DEFAULT NULL,
  `action_url` varchar(400) NOT NULL DEFAULT '',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qtn_role_date` (`target_role`,`created_at`),
  KEY `idx_qtn_user_date` (`target_user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `question_images`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `question_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question_id` bigint(20) unsigned NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_question_image` (`question_id`),
  CONSTRAINT `question_images_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `questions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) unsigned NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `content` text NOT NULL,
  `option_a` varchar(500) DEFAULT NULL,
  `option_b` varchar(500) DEFAULT NULL,
  `option_c` varchar(500) DEFAULT NULL,
  `option_d` varchar(500) DEFAULT NULL,
  `correct_answer` enum('A','B','C','D') NOT NULL,
  `explanation` text DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT 'easy',
  `points` int(11) DEFAULT 1,
  `ai_generated` tinyint(1) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `audio` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_question_subject` (`subject_id`),
  KEY `idx_question_creator` (`created_by`),
  KEY `idx_question_difficulty` (`difficulty`),
  KEY `idx_question_ai` (`ai_generated`),
  CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `questions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `results`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT 0.00,
  `correct_answers` int(11) DEFAULT 0,
  `total_questions` int(11) DEFAULT 0,
  `time_taken` int(11) DEFAULT 0,
  `answers` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `exam_id` (`exam_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `room_answers`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `room_answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `question_id` int(10) unsigned NOT NULL,
  `answer` varchar(255) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `answered_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `student_id` (`student_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `room_answers_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `exam_rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `room_answers_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `room_members`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `room_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `joined_at` datetime DEFAULT NULL,
  `left_at` datetime DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 1,
  `is_submitted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_id` (`room_id`,`student_id`),
  KEY `room_id_2` (`room_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `room_members_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `exam_rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `room_members_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_wrong_questions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `student_wrong_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_question` (`student_id`,`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `subjects`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `subject_name` varchar(255) DEFAULT NULL,
  `code` varchar(30) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_subject_name` (`name`),
  KEY `idx_subject_status` (`status`),
  KEY `subjects_ibfk_1` (`created_by`),
  CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `teacher_subjects`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `teacher_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tf_questions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `tf_questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) unsigned NOT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `question_text` text NOT NULL,
  `is_true` tinyint(1) NOT NULL,
  `explanation` text DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') DEFAULT 'easy',
  `points` int(11) DEFAULT 1,
  `ai_generated` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tf_subject` (`subject_id`),
  KEY `tf_questions_ibfk_2` (`created_by`),
  CONSTRAINT `tf_questions_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tf_questions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploads`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `uploads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `status` enum('uploaded','processing','completed','failed') DEFAULT 'uploaded',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_upload_user` (`user_id`),
  KEY `idx_upload_status` (`status`),
  CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_badges`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `user_badges` (
  `user_id` int(10) unsigned NOT NULL,
  `badge_id` int(11) NOT NULL,
  `unlocked_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`badge_id`),
  KEY `user_badges_ibfk_2` (`badge_id`),
  CONSTRAINT `user_badges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_badges_ibfk_2` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_practice_logs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `user_practice_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `game_id` int(11) NOT NULL,
  `score` int(11) DEFAULT 0,
  `correct_count` int(11) DEFAULT 0,
  `total_count` int(11) DEFAULT 0,
  `duration_seconds` int(11) DEFAULT 0,
  `completed_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_practice_log_user` (`user_id`),
  KEY `idx_practice_log_game` (`game_id`),
  CONSTRAINT `user_practice_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_practice_logs_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `practice_games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','teacher','admin','admin_test') DEFAULT 'student',
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `student_code` varchar(30) DEFAULT NULL,
  `teacher_code` varchar(30) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `class_name` varchar(100) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','inactive','blocked') DEFAULT 'active',
  `is_protected` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `v_active_rooms`
--

/*!50001 DROP VIEW IF EXISTS `v_active_rooms`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_active_rooms` AS SELECT
 1 AS `id`,
  1 AS `room_code`,
  1 AS `room_name`,
  1 AS `title`,
  1 AS `teacher`,
  1 AS `start_time`,
  1 AS `end_time`,
  1 AS `status` */;
SET character_set_client = @saved_cs_client;

--
-- Dumping routines for database 'db_quiz_tech'
--

--
-- Final view structure for view `v_active_rooms`
--

/*!50001 DROP VIEW IF EXISTS `v_active_rooms`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY INVOKER */
/*!50001 VIEW `v_active_rooms` AS select `r`.`id` AS `id`,`r`.`room_code` AS `room_code`,`r`.`room_name` AS `room_name`,`e`.`title` AS `title`,`u`.`name` AS `teacher`,`r`.`start_time` AS `start_time`,`r`.`end_time` AS `end_time`,`r`.`status` AS `status` from ((`exam_rooms` `r` join `exams` `e` on(`e`.`id` = `r`.`exam_id`)) join `users` `u` on(`u`.`id` = `r`.`teacher_id`)) where `r`.`status` in ('active','ongoing') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-12 16:10:32
