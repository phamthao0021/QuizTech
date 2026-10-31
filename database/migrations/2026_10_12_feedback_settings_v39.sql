-- Non-destructive migration. If table exists, does not overwrite data.
CREATE TABLE IF NOT EXISTS practice_feedback (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 type VARCHAR(50) NOT NULL DEFAULT 'feedback',
 exam_id INT NULL,
 question_id INT NULL,
 title VARCHAR(255) NULL,
 content TEXT NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'pending',
 admin_note TEXT NULL,
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 KEY idx_feedback_user_created (user_id,created_at),
 KEY idx_feedback_status_created (status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS settings(
 setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
 setting_value TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
