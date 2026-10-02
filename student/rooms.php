<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireRole('student');
/**
 * Đồng bộ schema phòng thi để Teacher/Admin/Student dùng chung một cấu trúc.
 * Quan trọng: chuyển status về VARCHAR trước khi đổi dữ liệu, tránh lỗi
 * SQLSTATE[01000]: Warning: 1265 Data truncated khi DB cũ đang dùng ENUM khác.
 */
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


$stmt = $pdo->query("SELECT r.id,r.exam_id,r.room_code,r.room_name,r.description,r.max_students,r.start_time,r.end_time,r.status,r.created_at,e.title AS exam_title,(SELECT COUNT(*) FROM room_members rm WHERE rm.room_id=r.id) AS participant_count FROM exam_rooms r LEFT JOIN exams e ON e.id=r.exam_id WHERE r.status IN ('waiting','running') ORDER BY CASE WHEN r.status='running' THEN 1 ELSE 2 END,r.created_at DESC,r.id DESC");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Phòng thi trực tuyến';
require_once dirname(__DIR__) . '/includes/header_student.php';
?>
<style>
    :root {
        --purple: #7c3aed;
        --purple-dark: #6d28d9;
        --purple-soft: #f5f3ff;
        --border: #e2e8f0;
        --text: #172033;
        --muted: #64748b;
        --grad: linear-gradient(135deg, #4c1d95, #6d28d9, #8b5cf6)
    }

    .rooms-page {
        animation: up .3s ease
    }

    @keyframes up {
        from {
            opacity: 0;
            transform: translateY(10px)
        }

        to {
            opacity: 1;
            transform: none
        }
    }

    .room-hero {
        background: var(--grad);
        border-radius: 1.2rem;
        color: #fff;
        box-shadow: 0 16px 35px -14px #6d28d966
    }

    .room-card {
        height: 100%;
        border: 1px solid var(--border);
        border-radius: 1rem;
        background: #fff;
        padding: 1.15rem;
        transition: .25s;
        display: flex;
        flex-direction: column
    }

    .room-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 30px #0f172a0d;
        border-color: #ddd6fe
    }

    .room-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        background: var(--purple-soft);
        color: var(--purple);
        font-size: 1.25rem
    }

    .room-code {
        display: inline-flex;
        align-items: center;
        background: var(--purple-soft);
        color: #5b21b6;
        border: 1px solid #ddd6fe;
        border-radius: .55rem;
        padding: .35rem .65rem;
        font: 700 .78rem monospace;
        letter-spacing: 1px
    }

    .meta-chip {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .35rem .58rem;
        border: 1px solid #e5e7eb;
        border-radius: .55rem;
        background: #f8fafc;
        color: #475569;
        font-size: .72rem
    }

    .search-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1rem
    }

    .btn-purple {
        background: var(--purple);
        border-color: var(--purple);
        color: #fff
    }

    .btn-purple:hover {
        background: var(--purple-dark);
        color: #fff
    }

    .clamp2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.8em
    }

    .empty-state {
        padding: 4rem 1rem;
        text-align: center;
        color: var(--muted)
    }
</style>
<div class="container-fluid px-2 px-md-4 rooms-page">
    <div class="room-hero p-3 p-md-4 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="small text-white-50 mb-1">STUDENT / PHÒNG THI</div>
                <h3 class="fw-bold mb-1"><i class="bi bi-door-open-fill me-2"></i>Phòng thi trực tuyến</h3>
                <p class="text-white-50 small mb-0">Tham gia phòng thi do giảng viên mở hoặc nhập mã phòng để tham gia.</p>
            </div><a href="room_join.php" class="btn btn-light text-primary fw-semibold rounded-3 px-3"><i class="bi bi-key-fill me-1"></i>Nhập mã phòng</a>
        </div>
    </div>
    <div class="search-box mb-4">
        <div class="row g-2">
            <div class="col-12 col-md-8">
                <div class="input-group"><span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span><input id="roomSearch" class="form-control border-start-0" placeholder="Tìm tên phòng, mã phòng hoặc đề thi..." oninput="filterRooms()"></div>
            </div>
            <div class="col-12 col-md-4"><select id="roomStatus" class="form-select" onchange="filterRooms()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="running">Đang diễn ra</option>
                    <option value="waiting">Chờ bắt đầu</option>
                </select></div>
        </div>
    </div>
    <?php if (!$rooms): ?><div class="card border-0 shadow-sm rounded-4 empty-state"><i class="bi bi-door-closed fs-1 d-block opacity-50 mb-3"></i>
            <div class="fw-semibold">Hiện chưa có phòng thi đang mở</div>
            <div class="small mt-1">Hãy nhập mã phòng do giảng viên cung cấp để tham gia.</div>
        </div><?php else: ?>
        <div class="row g-3 g-md-4" id="roomList"><?php foreach ($rooms as $room): $running = $room['status'] === 'running';
                                                        $full = (int)$room['participant_count'] >= (int)$room['max_students'];
                                                        $hay = strtolower(($room['room_code'] ?? '') . ' ' . ($room['room_name'] ?? '') . ' ' . ($room['exam_title'] ?? '')); ?>
                <div class="col-12 col-md-6 col-xl-4 room-item" data-search="<?= e($hay) ?>" data-status="<?= e($room['status']) ?>">
                    <div class="room-card">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div class="room-icon"><i class="bi bi-broadcast"></i></div><span class="badge <?= $running ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' ?> rounded-pill px-3 py-2"><?= $running ? '<i class="bi bi-play-circle-fill me-1"></i>Đang diễn ra' : '<i class="bi bi-clock-history me-1"></i>Đang chờ' ?></span>
                        </div>
                        <h6 class="fw-bold mb-1 clamp2"><?= e($room['room_name']) ?></h6>
                        <div class="small text-muted mb-3"><i class="bi bi-journal-text me-1"></i><?= e($room['exam_title'] ?: 'Chưa gán đề thi') ?></div>
                        <div class="d-flex justify-content-between align-items-center mb-3"><span class="small text-muted">Mã phòng</span><span class="room-code"><?= e($room['room_code']) ?></span></div>
                        <div class="d-flex flex-wrap gap-2 mb-3"><span class="meta-chip"><i class="bi bi-people"></i><?= e($room['participant_count']) ?>/<?= e($room['max_students']) ?> SV</span><?php if ($room['start_time']): ?><span class="meta-chip"><i class="bi bi-calendar-event"></i><?= date('d/m/Y H:i', strtotime($room['start_time'])) ?></span><?php endif; ?></div><?php if ($room['description']): ?><div class="small text-muted border-top pt-2 mb-3 clamp2"><?= e($room['description']) ?></div><?php endif; ?><div class="mt-auto"><a href="waiting-room.php?code=<?= urlencode($room['room_code']) ?>" class="btn btn-purple w-100 rounded-3 fw-bold py-2 <?= $full ? 'disabled' : '' ?>" <?= $full ? 'aria-disabled="true" tabindex="-1"' : '' ?>><i class="bi bi-box-arrow-in-right me-2"></i><?= $full ? 'Phòng đã đầy' : 'Tham gia phòng' ?></a></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div id="noResult" class="empty-state d-none"><i class="bi bi-search fs-1 d-block opacity-50 mb-2"></i>Không tìm thấy phòng phù hợp.</div><?php endif; ?>
</div>
<script>
    function filterRooms() {
        var q = document.getElementById('roomSearch').value.toLowerCase().trim(),
            s = document.getElementById('roomStatus').value,
            shown = 0;
        document.querySelectorAll('.room-item').forEach(function(r) {
            var ok = (!q || r.dataset.search.indexOf(q) >= 0) && (!s || r.dataset.status === s);
            r.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });
        var n = document.getElementById('noResult');
        if (n) n.classList.toggle('d-none', shown !== 0);
    }
</script>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>