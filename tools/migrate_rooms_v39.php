<?php
// CLI migration: php tools/migrate_rooms_v39.php. Run on a backed-up staging DB first.
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require_once dirname(__DIR__).'/includes/config.php';
if (!function_exists('qtEnsureRoomSchema')) {
    function qtEnsureRoomSchema(PDO $pdo): void
    {
        if (!function_exists('qtColumnExists')) {
            function qtColumnExists(PDO $pdo, string $table, string $column): bool
            {
                $s = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
                $s->execute([$column]);
                return (bool)$s->fetch(PDO::FETCH_ASSOC);
            }
        }

        try {
            // exam_rooms phải tồn tại.
            $pdo->exec("CREATE TABLE IF NOT EXISTS `exam_rooms` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `exam_id` INT NULL,
                `teacher_id` INT NULL DEFAULT NULL,
                `room_code` VARCHAR(50) NOT NULL UNIQUE,
                `room_name` VARCHAR(255) NOT NULL,
                `room_password` VARCHAR(255) NULL,
                `description` TEXT NULL,
                `max_students` INT NOT NULL DEFAULT 50,
                `start_time` DATETIME NULL,
                `end_time` DATETIME NULL,
                `allow_late_join` TINYINT(1) NOT NULL DEFAULT 0,
                `late_minutes` INT NOT NULL DEFAULT 0,
                `auto_start` TINYINT(1) NOT NULL DEFAULT 0,
                `auto_close` TINYINT(1) NOT NULL DEFAULT 0,
                `status` VARCHAR(20) NOT NULL DEFAULT 'waiting',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Bổ sung các cột mà database cũ có thể thiếu.
            $columns = [
                'exam_id' => "ALTER TABLE `exam_rooms` ADD COLUMN `exam_id` INT NULL",
                'teacher_id' => "ALTER TABLE `exam_rooms` ADD COLUMN `teacher_id` INT NULL",
                'room_password' => "ALTER TABLE `exam_rooms` ADD COLUMN `room_password` VARCHAR(255) NULL",
                'description' => "ALTER TABLE `exam_rooms` ADD COLUMN `description` TEXT NULL",
                'max_students' => "ALTER TABLE `exam_rooms` ADD COLUMN `max_students` INT NOT NULL DEFAULT 50",
                'start_time' => "ALTER TABLE `exam_rooms` ADD COLUMN `start_time` DATETIME NULL",
                'end_time' => "ALTER TABLE `exam_rooms` ADD COLUMN `end_time` DATETIME NULL",
                'allow_late_join' => "ALTER TABLE `exam_rooms` ADD COLUMN `allow_late_join` TINYINT(1) NOT NULL DEFAULT 0",
                'late_minutes' => "ALTER TABLE `exam_rooms` ADD COLUMN `late_minutes` INT NOT NULL DEFAULT 0",
                'auto_start' => "ALTER TABLE `exam_rooms` ADD COLUMN `auto_start` TINYINT(1) NOT NULL DEFAULT 0",
                'auto_close' => "ALTER TABLE `exam_rooms` ADD COLUMN `auto_close` TINYINT(1) NOT NULL DEFAULT 0",
                'created_at' => "ALTER TABLE `exam_rooms` ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP",
                'updated_at' => "ALTER TABLE `exam_rooms` ADD COLUMN `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
            ];
            foreach ($columns as $column => $sql) {
                if (!qtColumnExists($pdo, 'exam_rooms', $column)) {
                    try {
                        $pdo->exec($sql);
                    } catch (Throwable $e) {
                    }
                }
            }

            // Database cũ có max_participants: sao chép sang tên chuẩn max_students.
            if (qtColumnExists($pdo, 'exam_rooms', 'max_participants')) {
                try {
                    $pdo->exec("UPDATE `exam_rooms` SET `max_students` = `max_participants` WHERE (`max_students` IS NULL OR `max_students` = 0) AND `max_participants` IS NOT NULL");
                } catch (Throwable $e) {
                }
            }

            // TUYỆT ĐỐI không ALTER ENUM trực tiếp khi còn dữ liệu active/closed.
            // Đổi sang VARCHAR trước để không phát sinh Warning 1265.
            try {
                $pdo->exec("ALTER TABLE `exam_rooms` MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'waiting'");
            } catch (Throwable $e) {
            }

            // Chuẩn hóa các trạng thái từ những phiên bản cũ.
            try {
                $pdo->exec("UPDATE `exam_rooms` SET `status`='waiting' WHERE `status` IS NULL OR TRIM(`status`)='' OR `status` IN ('pending','pending_start','waiting_start','Chờ bắt đầu')");
                $pdo->exec("UPDATE `exam_rooms` SET `status`='running' WHERE `status` IN ('active','ongoing','in_progress','playing','Đang thi','Đang diễn ra')");
                $pdo->exec("UPDATE `exam_rooms` SET `status`='finished' WHERE `status` IN ('closed','complete','completed','done','Đã kết thúc')");
                $pdo->exec("UPDATE `exam_rooms` SET `status`='cancelled' WHERE `status` IN ('cancel','canceled','Đã hủy')");
                $pdo->exec("UPDATE `exam_rooms` SET `status`='waiting' WHERE `status` NOT IN ('waiting','running','finished','cancelled')");
            } catch (Throwable $e) {
            }

            // exams cũ có thể chỉ có created_by; bổ sung teacher_id để liên kết phòng.
            if (qtColumnExists($pdo, 'exams', 'created_by') && !qtColumnExists($pdo, 'exams', 'teacher_id')) {
                try {
                    $pdo->exec("ALTER TABLE `exams` ADD COLUMN `teacher_id` INT NULL");
                } catch (Throwable $e) {
                }
            }
            if (qtColumnExists($pdo, 'exams', 'teacher_id') && qtColumnExists($pdo, 'exams', 'created_by')) {
                try {
                    $pdo->exec("UPDATE `exams` e INNER JOIN `users` u ON u.id=e.created_by SET e.teacher_id=e.created_by WHERE (e.teacher_id IS NULL OR e.teacher_id=0) AND u.role='teacher'");
                } catch (Throwable $e) {
                }
            }

            // Gán teacher_id còn thiếu của phòng theo đề thi.
            if (qtColumnExists($pdo, 'exams', 'teacher_id')) {
                try {
                    $pdo->exec("UPDATE `exam_rooms` r INNER JOIN `exams` e ON e.id=r.exam_id SET r.teacher_id=e.teacher_id WHERE (r.teacher_id IS NULL OR r.teacher_id=0) AND e.teacher_id IS NOT NULL");
                } catch (Throwable $e) {
                }
            }
        } catch (Throwable $e) {
            // Không làm trang chết nếu hosting hạn chế quyền ALTER/CREATE.
        }
    }
}

qtEnsureRoomSchema($pdo);
echo "Room schema migration attempted. Verify columns and statuses on MySQL.\n";
