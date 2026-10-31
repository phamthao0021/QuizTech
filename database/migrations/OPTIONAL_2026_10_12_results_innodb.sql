-- OPTIONAL. Verify backup and maintenance window first; this ALTER rebuilds the table and may lock writes.
-- Check current engine before proceeding: SELECT TABLE_NAME, ENGINE FROM information_schema.tables WHERE table_schema=DATABASE() AND TABLE_NAME='results';
ALTER TABLE results ENGINE=InnoDB;
