-- migration.sql - Thêm các cột thiếu

-- Users
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'blocked') DEFAULT 'active';
ALTER TABLE users ADD COLUMN IF NOT EXISTS student_code VARCHAR(30) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS teacher_code VARCHAR(30) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS class_name VARCHAR(100) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) NULL;

-- Questions
ALTER TABLE questions ADD COLUMN IF NOT EXISTS explanation TEXT NULL;
ALTER TABLE questions ADD COLUMN IF NOT EXISTS points INT DEFAULT 1;
ALTER TABLE questions ADD COLUMN IF NOT EXISTS ai_generated TINYINT(1) DEFAULT 0;

-- Tạo bảng multiselect_questions nếu chưa có
CREATE TABLE IF NOT EXISTS multiselect_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    question_text TEXT NOT NULL,
    options JSON NOT NULL,
    correct_answers JSON NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    explanation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Tạo bảng matching_questions
CREATE TABLE IF NOT EXISTS matching_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    question_text TEXT NOT NULL,
    left_items JSON NOT NULL,
    right_items JSON NOT NULL,
    correct_matches JSON NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Tạo bảng tf_questions
CREATE TABLE IF NOT EXISTS tf_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    question_text TEXT NOT NULL,
    is_true BOOLEAN NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'easy',
    explanation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);