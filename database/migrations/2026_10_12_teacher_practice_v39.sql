-- Add ownership and soft-delete flags; leave all legacy rows owned by Admin (NULL).
-- MySQL 5.7+; checks existence so migration can be safely re-run.
SET @q = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='practice_concepts' AND COLUMN_NAME='created_by')=0, 'ALTER TABLE `practice_concepts` ADD COLUMN `created_by` INT NULL', 'SELECT 1');
PREPARE qstmt FROM @q;
EXECUTE qstmt;
DEALLOCATE PREPARE qstmt;
SET @q = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='practice_concepts' AND COLUMN_NAME='is_deleted')=0, 'ALTER TABLE `practice_concepts` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE qstmt FROM @q;
EXECUTE qstmt;
DEALLOCATE PREPARE qstmt;
-- Manual optional index: CREATE INDEX idx_practice_concepts_owner ON practice_concepts(created_by,is_deleted);

SET @q = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='practice_quiz_questions' AND COLUMN_NAME='created_by')=0, 'ALTER TABLE `practice_quiz_questions` ADD COLUMN `created_by` INT NULL', 'SELECT 1');
PREPARE qstmt FROM @q;
EXECUTE qstmt;
DEALLOCATE PREPARE qstmt;
SET @q = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='practice_quiz_questions' AND COLUMN_NAME='is_deleted')=0, 'ALTER TABLE `practice_quiz_questions` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE qstmt FROM @q;
EXECUTE qstmt;
DEALLOCATE PREPARE qstmt;
-- Manual optional index: CREATE INDEX idx_practice_quiz_questions_owner ON practice_quiz_questions(created_by,is_deleted);

SET @q = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='practice_crosswords' AND COLUMN_NAME='created_by')=0, 'ALTER TABLE `practice_crosswords` ADD COLUMN `created_by` INT NULL', 'SELECT 1');
PREPARE qstmt FROM @q;
EXECUTE qstmt;
DEALLOCATE PREPARE qstmt;
SET @q = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='practice_crosswords' AND COLUMN_NAME='is_deleted')=0, 'ALTER TABLE `practice_crosswords` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE qstmt FROM @q;
EXECUTE qstmt;
DEALLOCATE PREPARE qstmt;
-- Manual optional index: CREATE INDEX idx_practice_crosswords_owner ON practice_crosswords(created_by,is_deleted);

