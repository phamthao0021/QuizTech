-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3306
-- Thời gian đã tạo: Th8 04, 2026 lúc 07:07 AM
-- Phiên bản máy phục vụ: 5.7.31
-- Phiên bản PHP: 7.3.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quiztech`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `browser`, `created_at`) VALUES
(1, 4, 'submit', 'Nộp bài thi', NULL, NULL, '2026-08-03 11:11:13'),
(2, 4, 'submit', 'Nộp bài thi', NULL, NULL, '2026-08-03 13:22:14'),
(3, 4, 'submit', 'Nộp bài thi', NULL, NULL, '2026-08-03 13:30:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ai_datasets`
--

DROP TABLE IF EXISTS `ai_datasets`;
CREATE TABLE IF NOT EXISTS `ai_datasets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) DEFAULT NULL,
  `dataset_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_records` int(11) DEFAULT '0',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ai_generated_questions`
--

DROP TABLE IF EXISTS `ai_generated_questions`;
CREATE TABLE IF NOT EXISTS `ai_generated_questions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `option_a` text COLLATE utf8mb4_unicode_ci,
  `option_b` text COLLATE utf8mb4_unicode_ci,
  `option_c` text COLLATE utf8mb4_unicode_ci,
  `option_d` text COLLATE utf8mb4_unicode_ci,
  `correct_answer` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `explanation` text COLLATE utf8mb4_unicode_ci,
  `difficulty` enum('easy','medium','hard') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ai_imports`
--

DROP TABLE IF EXISTS `ai_imports`;
CREATE TABLE IF NOT EXISTS `ai_imports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_questions` int(11) DEFAULT '0',
  `imported_questions` int(11) DEFAULT '0',
  `duplicate_questions` int(11) DEFAULT '0',
  `status` enum('processing','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'processing',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ai_logs`
--

DROP TABLE IF EXISTS `ai_logs`;
CREATE TABLE IF NOT EXISTS `ai_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `type` enum('generate_questions','analyze','chat','ocr') COLLATE utf8mb4_unicode_ci NOT NULL,
  `input` text COLLATE utf8mb4_unicode_ci,
  `output` text COLLATE utf8mb4_unicode_ci,
  `model` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'mock',
  `tokens_used` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ai_models`
--

DROP TABLE IF EXISTS `ai_models`;
CREATE TABLE IF NOT EXISTS `ai_models` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ai_prompts`
--

DROP TABLE IF EXISTS `ai_prompts`;
CREATE TABLE IF NOT EXISTS `ai_prompts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prompt` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exams`
--

DROP TABLE IF EXISTS `exams`;
CREATE TABLE IF NOT EXISTS `exams` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `exam_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exam_type` enum('practice','quiz','midterm','final','custom') COLLATE utf8mb4_unicode_ci DEFAULT 'quiz',
  `difficulty` enum('easy','medium','hard','mixed') COLLATE utf8mb4_unicode_ci DEFAULT 'mixed',
  `total_questions` int(11) DEFAULT '10',
  `total_score` decimal(6,2) DEFAULT '10.00',
  `duration` int(11) NOT NULL DEFAULT '30',
  `pass_score` decimal(5,2) DEFAULT '5.00',
  `attempt_limit` int(11) DEFAULT '1',
  `shuffle_questions` tinyint(1) DEFAULT '1',
  `shuffle_answers` tinyint(1) DEFAULT '1',
  `show_result` tinyint(1) DEFAULT '1',
  `show_answer` tinyint(1) DEFAULT '0',
  `allow_review` tinyint(1) DEFAULT '1',
  `negative_marking` tinyint(1) DEFAULT '0',
  `negative_score` decimal(4,2) DEFAULT '0.00',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('draft','published','closed','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_code` (`exam_code`),
  KEY `idx_exam_subject` (`subject_id`),
  KEY `idx_exam_teacher` (`teacher_id`),
  KEY `idx_exam_status` (`status`),
  KEY `idx_exam_code` (`exam_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `exams`
--

INSERT INTO `exams` (`id`, `subject_id`, `teacher_id`, `title`, `description`, `exam_code`, `exam_type`, `difficulty`, `total_questions`, `total_score`, `duration`, `pass_score`, `attempt_limit`, `shuffle_questions`, `shuffle_answers`, `show_result`, `show_answer`, `allow_review`, `negative_marking`, `negative_score`, `password`, `start_time`, `end_time`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Đề thi Cơ sở dữ liệu', 'Kiểm tra SQL và thiết kế CSDL', 'CSDL001', 'quiz', 'easy', 20, '10.00', 30, '5.00', 1, 1, 1, 1, 0, 1, 0, '0.00', NULL, NULL, NULL, 'published', '2026-08-02 12:01:27', '2026-08-02 12:01:27'),
(2, 3, 2, 'Đề thi PHP Cơ bản', 'Kiểm tra kiến thức PHP', 'PHP001', 'practice', 'mixed', 15, '10.00', 25, '5.00', 1, 1, 1, 1, 0, 1, 0, '0.00', NULL, NULL, NULL, 'published', '2026-08-02 12:01:27', '2026-08-02 12:01:27'),
(3, 4, 2, 'Đề thi JavaScript ES6', 'Kiểm tra JavaScript hiện đại', 'JS001', 'quiz', 'medium', 20, '10.00', 40, '6.00', 1, 1, 1, 1, 0, 1, 0, '0.00', NULL, NULL, NULL, 'published', '2026-08-02 12:01:27', '2026-08-02 12:01:27');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_attempts`
--

DROP TABLE IF EXISTS `exam_attempts`;
CREATE TABLE IF NOT EXISTS `exam_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` int(10) UNSIGNED DEFAULT NULL,
  `exam_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `started_at` datetime NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT '0',
  `score` decimal(6,2) DEFAULT '0.00',
  `total_questions` int(11) DEFAULT '0',
  `correct_answers` int(11) DEFAULT '0',
  `wrong_answers` int(11) DEFAULT '0',
  `unanswered` int(11) DEFAULT '0',
  `answers_json` json DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT '0.00',
  `status` enum('doing','submitted','timeout','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'doing',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_id` (`room_id`,`student_id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `exam_attempts`
--

INSERT INTO `exam_attempts` (`id`, `room_id`, `exam_id`, `student_id`, `started_at`, `submitted_at`, `duration_seconds`, `score`, `total_questions`, `correct_answers`, `wrong_answers`, `unanswered`, `answers_json`, `percentage`, `status`, `created_at`) VALUES
(1, NULL, 3, 4, '2026-08-03 17:31:13', '2026-08-03 18:11:13', 2400, '10.00', 1, 1, 0, 0, '{\"1\": \"A\"}', '100.00', 'submitted', '2026-08-03 11:11:13'),
(2, 0, 3, 4, '2026-08-03 18:11:13', '2026-08-03 18:11:13', 2400, '10.00', 1, 1, 0, 0, NULL, '100.00', 'submitted', '2026-08-03 11:11:13'),
(3, NULL, 2, 4, '2026-08-03 20:22:07', '2026-08-03 20:22:14', 7, '10.00', 1, 1, 0, 0, '{\"3\": \"A\"}', '100.00', 'submitted', '2026-08-03 13:22:14'),
(5, NULL, 1, 4, '2026-08-03 20:30:49', '2026-08-03 20:30:55', 6, '10.00', 3, 3, 0, 0, '{\"1\": \"A\", \"2\": \"A\", \"3\": \"A\"}', '100.00', 'submitted', '2026-08-03 13:30:55'),
(6, NULL, 1, 4, '2026-08-03 20:30:55', '2026-08-03 20:30:55', 6, '10.00', 3, 3, 0, 0, '{\"1\": \"A\", \"2\": \"A\", \"3\": \"A\"}', '100.00', 'submitted', '2026-08-03 13:30:55'),
(7, NULL, 3, 4, '2026-08-04 01:04:40', '2026-08-04 01:04:40', 65, '10.00', 1, 1, 0, 0, '{\"1\": \"A\"}', '100.00', 'submitted', '2026-08-03 18:04:40'),
(8, NULL, 3, 3, '2026-08-04 11:10:57', '2026-08-04 11:10:57', 4, '10.00', 1, 1, 0, 0, '{\"1\": \"A\"}', '100.00', 'submitted', '2026-08-04 04:10:57'),
(9, NULL, 2, 3, '2026-08-04 11:35:50', '2026-08-04 11:35:50', 3, '10.00', 1, 1, 0, 0, '{\"3\": \"A\"}', '100.00', 'submitted', '2026-08-04 04:35:50');

--
-- Bẫy `exam_attempts`
--
DROP TRIGGER IF EXISTS `trg_submit_exam`;
DELIMITER $$
CREATE TRIGGER `trg_submit_exam` AFTER UPDATE ON `exam_attempts` FOR EACH ROW BEGIN

    IF NEW.status='submitted'
    AND OLD.status<>'submitted' THEN

        INSERT INTO activity_logs
        (
            user_id,
            action,
            description
        )
        VALUES
        (
            NEW.student_id,
            'submit',
            'Nộp bài thi'
        );

    END IF;

END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_questions`
--

DROP TABLE IF EXISTS `exam_questions`;
CREATE TABLE IF NOT EXISTS `exam_questions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id` bigint(20) UNSIGNED NOT NULL,
  `question_type` enum('single','multiple','matching','true_false') COLLATE utf8mb4_unicode_ci DEFAULT 'single',
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `question_order` int(11) DEFAULT '1',
  `score` decimal(5,2) DEFAULT '1.00',
  `required` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exam_question_exam` (`exam_id`),
  KEY `idx_exam_question` (`question_id`),
  KEY `idx_exam_order` (`question_order`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `exam_questions`
--

INSERT INTO `exam_questions` (`id`, `exam_id`, `question_type`, `question_id`, `question_order`, `score`, `required`, `created_at`) VALUES
(1, 1, 'single', 1, 1, '1.00', 1, '2026-08-02 12:01:27'),
(2, 1, 'single', 2, 2, '1.00', 1, '2026-08-02 12:01:27'),
(3, 1, 'single', 3, 3, '1.00', 1, '2026-08-02 12:01:27'),
(4, 2, 'single', 3, 1, '1.00', 1, '2026-08-02 12:01:27'),
(5, 3, 'single', 1, 1, '1.00', 1, '2026-08-02 12:01:27');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `exam_rooms`
--

DROP TABLE IF EXISTS `exam_rooms`;
CREATE TABLE IF NOT EXISTS `exam_rooms` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `room_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `room_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `room_password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `max_students` int(11) DEFAULT '50',
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `allow_late_join` tinyint(1) DEFAULT '0',
  `late_minutes` int(11) DEFAULT '0',
  `auto_start` tinyint(1) DEFAULT '1',
  `auto_close` tinyint(1) DEFAULT '1',
  `status` enum('waiting','running','finished','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'waiting',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_code` (`room_code`),
  KEY `exam_id` (`exam_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `room_code_2` (`room_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `matching_questions`
--

DROP TABLE IF EXISTS `matching_questions`;
CREATE TABLE IF NOT EXISTS `matching_questions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `left_items` json NOT NULL,
  `right_items` json NOT NULL,
  `correct_matches` json NOT NULL,
  `explanation` text COLLATE utf8mb4_unicode_ci,
  `difficulty` enum('easy','medium','hard') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `points` int(11) DEFAULT '2',
  `ai_generated` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_matching_subject` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `multiselect_questions`
--

DROP TABLE IF EXISTS `multiselect_questions`;
CREATE TABLE IF NOT EXISTS `multiselect_questions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` json NOT NULL,
  `correct_answers` json NOT NULL,
  `explanation` text COLLATE utf8mb4_unicode_ci,
  `difficulty` enum('easy','medium','hard') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `points` int(11) DEFAULT '2',
  `ai_generated` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_multi_subject` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ocr_documents`
--

DROP TABLE IF EXISTS `ocr_documents`;
CREATE TABLE IF NOT EXISTS `ocr_documents` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extracted_text` longtext COLLATE utf8mb4_unicode_ci,
  `total_pages` int(11) DEFAULT NULL,
  `status` enum('processing','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expired_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reset_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `questions`
--

DROP TABLE IF EXISTS `questions`;
CREATE TABLE IF NOT EXISTS `questions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `option_a` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_b` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_c` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `option_d` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correct_answer` enum('A','B','C','D') COLLATE utf8mb4_unicode_ci NOT NULL,
  `explanation` text COLLATE utf8mb4_unicode_ci,
  `difficulty` enum('easy','medium','hard') COLLATE utf8mb4_unicode_ci DEFAULT 'easy',
  `points` int(11) DEFAULT '1',
  `ai_generated` tinyint(1) DEFAULT '0',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT '1',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_question_subject` (`subject_id`),
  KEY `idx_question_creator` (`created_by`),
  KEY `idx_question_difficulty` (`difficulty`),
  KEY `idx_question_ai` (`ai_generated`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `questions`
--

INSERT INTO `questions` (`id`, `subject_id`, `created_by`, `content`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `explanation`, `difficulty`, `points`, `ai_generated`, `image`, `audio`, `video`, `is_public`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'SQL là viết tắt của?', 'Structured Query Language', 'Simple Query Language', 'System Query Language', 'Standard Question Language', 'A', 'SQL = Structured Query Language.', 'easy', 1, 0, NULL, NULL, NULL, 1, 1, '2026-08-02 12:00:42', '2026-08-02 12:00:42'),
(2, 1, NULL, 'Lệnh dùng để truy vấn dữ liệu là?', 'SELECT', 'UPDATE', 'DELETE', 'INSERT', 'A', 'SELECT dùng để lấy dữ liệu.', 'easy', 1, 0, NULL, NULL, NULL, 1, 1, '2026-08-02 12:00:42', '2026-08-02 12:00:42'),
(3, 3, NULL, 'PHP là gì?', 'Ngôn ngữ lập trình phía Server', 'Hệ quản trị CSDL', 'Trình duyệt Web', 'Hệ điều hành', 'A', 'PHP là ngôn ngữ lập trình phía máy chủ.', 'easy', 1, 0, NULL, NULL, NULL, 1, 1, '2026-08-02 12:00:42', '2026-08-02 12:00:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `question_images`
--

DROP TABLE IF EXISTS `question_images`;
CREATE TABLE IF NOT EXISTS `question_images` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` bigint(20) UNSIGNED NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(11) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_question_image` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `results`
--

DROP TABLE IF EXISTS `results`;
CREATE TABLE IF NOT EXISTS `results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT '0.00',
  `correct_answers` int(11) DEFAULT '0',
  `total_questions` int(11) DEFAULT '0',
  `time_taken` int(11) DEFAULT '0',
  `answers` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `exam_id` (`exam_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `results`
--

INSERT INTO `results` (`id`, `user_id`, `exam_id`, `score`, `correct_answers`, `total_questions`, `time_taken`, `answers`, `created_at`) VALUES
(1, 3, 2, '10.00', 1, 1, 13, '{\"3\":\"A\"}', '2026-08-04 04:49:23'),
(2, 3, 2, '10.00', 1, 1, 9, '{\"3\":\"A\"}', '2026-08-04 04:49:37'),
(3, 3, 2, '10.00', 1, 1, 3, '{\"3\":\"A\"}', '2026-08-04 04:52:48'),
(4, 3, 2, '10.00', 1, 1, 71, '{\"3\":\"A\"}', '2026-08-04 04:55:36'),
(5, 3, 1, '5.00', 1, 2, 8, '{\"1\":\"A\",\"2\":\"D\"}', '2026-08-04 04:59:33');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_answers`
--

DROP TABLE IF EXISTS `room_answers`;
CREATE TABLE IF NOT EXISTS `room_answers` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `answer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT '0',
  `answered_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `student_id` (`student_id`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `room_members`
--

DROP TABLE IF EXISTS `room_members`;
CREATE TABLE IF NOT EXISTS `room_members` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `joined_at` datetime DEFAULT NULL,
  `left_at` datetime DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT '1',
  `is_submitted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_id` (`room_id`,`student_id`),
  KEY `room_id_2` (`room_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_subject_name` (`name`),
  KEY `idx_subject_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `code`, `description`, `icon`, `color`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Cơ sở dữ liệu', 'CSDL', 'Môn học về SQL và thiết kế CSDL', 'database', '#0d6efd', 'active', NULL, '2026-08-02 12:00:42', '2026-08-02 12:00:42'),
(2, 'Lập trình C', 'C', 'Ngôn ngữ lập trình C', 'terminal', '#198754', 'active', NULL, '2026-08-02 12:00:42', '2026-08-02 12:00:42'),
(3, 'PHP', 'PHP', 'Lập trình Web PHP', 'code', '#6f42c1', 'active', NULL, '2026-08-02 12:00:42', '2026-08-02 12:00:42'),
(4, 'JavaScript', 'JS', 'Lập trình JavaScript', 'javascript', '#ffc107', 'active', NULL, '2026-08-02 12:00:42', '2026-08-02 12:00:42'),
(5, 'Kiểm thử phần mềm', 'TEST', 'Software Testing', 'bug', '#dc3545', 'active', NULL, '2026-08-02 12:00:42', '2026-08-02 12:00:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tf_questions`
--

DROP TABLE IF EXISTS `tf_questions`;
CREATE TABLE IF NOT EXISTS `tf_questions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_true` tinyint(1) NOT NULL,
  `explanation` text COLLATE utf8mb4_unicode_ci,
  `difficulty` enum('easy','medium','hard') COLLATE utf8mb4_unicode_ci DEFAULT 'easy',
  `points` int(11) DEFAULT '1',
  `ai_generated` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tf_subject` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `uploads`
--

DROP TABLE IF EXISTS `uploads`;
CREATE TABLE IF NOT EXISTS `uploads` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `status` enum('uploaded','processing','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'uploaded',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_upload_user` (`user_id`),
  KEY `idx_upload_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('student','teacher','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'student',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teacher_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','inactive','blocked') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `avatar`, `phone`, `student_code`, `teacher_code`, `department`, `class_name`, `gender`, `birthday`, `address`, `remember_token`, `email_verified_at`, `last_login`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin@quiztech.vn', '$argon2id$v=19$m=19456,t=2,p=1$Mep0Ipk2jMTtVacHIt2sbg$jIQH9buMMatMyQUjAipR3PSIBLEGbcf/WhK9zN5j2Ww', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-04 01:42:44', 'active', NULL, '2026-08-02 11:57:21', '2026-08-03 18:42:44'),
(2, 'Teacher', 'teacher@quiztech.vn', '$argon2id$v=19$m=19456,t=2,p=1$Mep0Ipk2jMTtVacHIt2sbg$OvnmyY0q6IeTHKk2mwmPFJMJp9QD9LOYEqpqb2+ezvY', 'teacher', 'uploads/avatars/avatar_teacher_2_1785787849.webp', '', NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-04 12:59:03', 'active', NULL, '2026-08-02 11:57:21', '2026-08-04 05:59:03'),
(3, 'Student', 'student@quiztech.vn', '$argon2id$v=19$m=19456,t=2,p=1$Mep0Ipk2jMTtVacHIt2sbg$At5ksUG+2A7BAtuOCy/ZaqBFuR8KqpHWn/sksFEWBfQ', 'student', 'uploads/avatars/avatar_student_3_1785822415.jpg', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-04 11:08:49', 'active', NULL, '2026-08-02 11:57:21', '2026-08-04 05:46:55'),
(4, 'Phạm Thị Thanh Thảo', 'thaoptt@gmail.com', '$2y$10$QZK7u6OnMjGlFg/5.tVXs.WMJBUk32vAXqj.1gUe13Ma80cWoyZdO', 'student', 'avatar_4_1785780914.jpg', '0923456789', '24211TT0021', NULL, NULL, 'CD24TT3', NULL, NULL, NULL, NULL, NULL, '2026-08-03 17:10:55', 'active', NULL, '2026-08-03 10:06:37', '2026-08-03 18:15:14');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_active_rooms`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `v_active_rooms`;
CREATE TABLE IF NOT EXISTS `v_active_rooms` (
`id` int(10) unsigned
,`room_code` varchar(20)
,`room_name` varchar(255)
,`title` varchar(255)
,`teacher` varchar(100)
,`start_time` datetime
,`end_time` datetime
,`status` enum('waiting','running','finished','cancelled')
);

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_active_rooms`
--
DROP TABLE IF EXISTS `v_active_rooms`;

DROP VIEW IF EXISTS `v_active_rooms`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_rooms`  AS  select `r`.`id` AS `id`,`r`.`room_code` AS `room_code`,`r`.`room_name` AS `room_name`,`e`.`title` AS `title`,`u`.`name` AS `teacher`,`r`.`start_time` AS `start_time`,`r`.`end_time` AS `end_time`,`r`.`status` AS `status` from ((`exam_rooms` `r` join `exams` `e` on((`e`.`id` = `r`.`exam_id`))) join `users` `u` on((`u`.`id` = `r`.`teacher_id`))) where (`r`.`status` = 'running') ;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `ai_datasets`
--
ALTER TABLE `ai_datasets`
  ADD CONSTRAINT `ai_datasets_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `ai_generated_questions`
--
ALTER TABLE `ai_generated_questions`
  ADD CONSTRAINT `ai_generated_questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `ai_imports`
--
ALTER TABLE `ai_imports`
  ADD CONSTRAINT `ai_imports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `ai_logs`
--
ALTER TABLE `ai_logs`
  ADD CONSTRAINT `ai_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `ocr_documents`
--
ALTER TABLE `ocr_documents`
  ADD CONSTRAINT `ocr_documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

DELIMITER $$
--
-- Sự kiện
--
DROP EVENT `auto_close_rooms`$$
CREATE DEFINER=`root`@`localhost` EVENT `auto_close_rooms` ON SCHEDULE EVERY 1 MINUTE STARTS '2026-08-02 19:10:35' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE exam_rooms

SET status='finished'

WHERE status='running'

AND end_time<=NOW()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
