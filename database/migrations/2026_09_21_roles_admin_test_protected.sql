-- QuizTech: role admin_test + is_protected + username (non-destructive)
-- Chạy trên DB hiện tại; không DROP dữ liệu.

ALTER TABLE `users`
  MODIFY COLUMN `role` ENUM('student','teacher','admin','admin_test')
  COLLATE utf8mb4_unicode_ci DEFAULT 'student';

-- is_protected: chỉ Super Admin hệ thống ban đầu được gắn protected
SET @col_protected := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'is_protected'
);
SET @sql_protected := IF(
  @col_protected = 0,
  'ALTER TABLE `users` ADD COLUMN `is_protected` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`',
  'SELECT 1'
);
PREPARE stmt_protected FROM @sql_protected;
EXECUTE stmt_protected;
DEALLOCATE PREPARE stmt_protected;

-- username tùy chọn (UI đang dùng; map theo email nếu trống)
SET @col_username := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'username'
);
SET @sql_username := IF(
  @col_username = 0,
  'ALTER TABLE `users` ADD COLUMN `username` VARCHAR(100) NULL DEFAULT NULL AFTER `email`',
  'SELECT 1'
);
PREPARE stmt_username FROM @sql_username;
EXECUTE stmt_username;
DEALLOCATE PREPARE stmt_username;

-- Unique index cho username (bỏ qua nếu đã có)
SET @idx_username := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND INDEX_NAME = 'uq_users_username'
);
SET @sql_idx := IF(
  @idx_username = 0,
  'ALTER TABLE `users` ADD UNIQUE KEY `uq_users_username` (`username`)',
  'SELECT 1'
);
PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- Bảo vệ Super Admin hệ thống ban đầu (id=1 hoặc email admin@quiztech.vn)
UPDATE `users`
SET `is_protected` = 1
WHERE (`id` = 1 OR `email` = 'admin@quiztech.vn')
  AND `role` = 'admin';

-- Đồng bộ username từ phần local của email nếu còn trống
UPDATE `users`
SET `username` = LOWER(SUBSTRING_INDEX(`email`, '@', 1))
WHERE (`username` IS NULL OR `username` = '')
  AND `email` IS NOT NULL
  AND `email` <> '';
