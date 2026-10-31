-- Chạy duy nhất trên database đã sao lưu; không xóa dữ liệu hiện có.
CREATE TABLE IF NOT EXISTS qt_notifications (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(180) NOT NULL,
 body TEXT NOT NULL,
 category VARCHAR(32) NOT NULL DEFAULT 'system',
 target_role VARCHAR(24) NOT NULL DEFAULT 'all',
 target_user_id INT NULL,
 action_url VARCHAR(400) NOT NULL DEFAULT '',
 created_by INT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 expires_at DATETIME NULL,
 KEY idx_qtn_role_date (target_role,created_at),
 KEY idx_qtn_user_date (target_user_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS qt_notification_reads (
 notification_id BIGINT UNSIGNED NOT NULL,
 user_id INT NOT NULL,
 read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (notification_id,user_id),
 KEY idx_qtnr_user (user_id,read_at),
 CONSTRAINT fk_qtnr_notification FOREIGN KEY(notification_id) REFERENCES qt_notifications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
