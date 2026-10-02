<?php
// includes/functions.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/permissions.php';

// 1. CHUẨN HÓA BẢO MẬT & ESCAPE HTML
if (!function_exists('e')) {
    function e($string)
    {
        return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// 2. PHÂN QUYỀN & NHÃN HIỂN THỊ
if (!function_exists('role_label')) {
    function role_label($role)
    {
        $role_clean = function_exists('normalizeRole')
            ? normalizeRole($role)
            : strtolower(trim($role ?? ''));
        $map = [
            'admin'      => 'Super Admin',
            'admin_test' => 'Admin kiểm thử',
            'teacher'    => 'Giảng viên',
            'giang_vien' => 'Giảng viên',
            'student'    => 'Học sinh / Sinh viên',
            'sinh_vien'  => 'Học sinh / Sinh viên'
        ];
        return $map[$role_clean] ?? 'Người dùng';
    }
}

// FLASH MESSAGES
if (!function_exists('setFlash')) {
    function setFlash($type, $message)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('getFlash')) {
    function getFlash()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

// DIRECT & BASE URL
if (!function_exists('redirect')) {
    function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '')
    {
        return '/' . ltrim($path, '/');
    }
}

// FORMAT DATE & TIME
if (!function_exists('format_date')) {
    function format_date($date)
    {
        if (empty($date)) return '--';
        return date('d/m/Y', strtotime($date));
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime($datetime)
    {
        if (empty($datetime)) return '--';
        return date('d/m/Y H:i', strtotime($datetime));
    }
}

if (!function_exists('time_ago')) {
    function time_ago($datetime)
    {
        if (empty($datetime)) return '--';
        $time = strtotime($datetime);
        $diff = time() - $time;

        if ($diff < 60) return 'vài giây trước';
        if ($diff < 3600) return floor($diff / 60) . ' phút trước';
        if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
        if ($diff < 604800) return floor($diff / 86400) . ' ngày trước';
        return format_date($datetime);
    }
}

// UI BADGES
if (!function_exists('difficulty_badge')) {
    function difficulty_badge($difficulty)
    {
        $map = [
            'easy'   => 'success',
            'medium' => 'warning',
            'hard'   => 'danger',
            'mixed'  => 'info'
        ];
        $class = $map[$difficulty] ?? 'secondary';
        return "<span class='badge bg-$class'>" . ucfirst($difficulty) . "</span>";
    }
}

if (!function_exists('status_badge')) {
    function status_badge($status)
    {
        $map = [
            'waiting'   => 'warning',
            'playing'   => 'primary',
            'finished'  => 'secondary',
            'active'    => 'success',
            'inactive'  => 'secondary',
            'pending'   => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger'
        ];
        $class = $map[$status] ?? 'secondary';
        $label = ucfirst($status);
        return "<span class='badge bg-$class'>$label</span>";
    }
}

// AUTHENTICATION HELPERS
if (!function_exists('isLoggedIn')) {
    function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['user_id']) || isset($_SESSION['user']['id']);
    }
}

if (!function_exists('isGuest')) {
    function isGuest()
    {
        return !isLoggedIn();
    }
}

if (!function_exists('isAdmin')) {
    /**
     * Quyền vào khu vực quản trị (Super Admin + Admin Test).
     * Teacher KHÔNG còn được coi là admin.
     */
    function isAdmin()
    {
        return function_exists('isAdminPanelUser') ? isAdminPanelUser() : false;
    }
}

if (!function_exists('isTeacher')) {
    function isTeacher()
    {
        if (!isLoggedIn()) return false;
        $role = function_exists('qt_current_role')
            ? qt_current_role()
            : strtolower(trim($_SESSION['role'] ?? $_SESSION['user']['role'] ?? ''));
        return $role === 'teacher' || $role === 'giang_vien';
    }
}

if (!function_exists('currentUser')) {
    function currentUser()
    {
        if (!isLoggedIn()) return null;
        global $pdo;
        $userId = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
        if (!$userId) return null;

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// AUTHORIZATION GUARDS
if (!function_exists('requireLogin')) {
    function requireLogin()
    {
        if (!isLoggedIn()) {
            setFlash('warning', 'Vui lòng đăng nhập để tiếp tục.');
            redirect('../login.php');
        }
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin()
    {
        requireLogin();
        if (!isAdmin()) {
            setFlash('danger', 'Bạn không có quyền truy cập vào khu vực Quản trị!');
            $role = function_exists('qt_current_role') ? qt_current_role() : ($_SESSION['role'] ?? 'student');
            $home = function_exists('homeForRole') ? homeForRole($role) : 'index.php';
            redirect('../' . ltrim($home, '/'));
        }
    }
}

if (!function_exists('requireTeacher')) {
    function requireTeacher()
    {
        requireLogin();
        if (!isTeacher()) {
            setFlash('danger', 'Bạn không có quyền truy cập khu vực Giảng viên!');
            $role = function_exists('qt_current_role') ? qt_current_role() : ($_SESSION['role'] ?? 'student');
            $home = function_exists('homeForRole') ? homeForRole($role) : 'index.php';
            redirect('../' . ltrim($home, '/'));
        }
    }
}

if (!function_exists('requireRole')) {
    function requireRole($role)
    {
        requireLogin();

        $wanted = function_exists('normalizeRole')
            ? normalizeRole($role)
            : strtolower(trim((string)$role));
        $currentRole = function_exists('qt_current_role')
            ? qt_current_role()
            : strtolower(trim($_SESSION['role'] ?? $_SESSION['user']['role'] ?? 'guest'));

        // Không bypass: mỗi role chỉ vào khu vực của mình
        if ($currentRole !== $wanted) {
            setFlash('danger', 'Bạn không có quyền thực hiện thao tác này.');
            $home = function_exists('homeForRole') ? homeForRole($currentRole) : 'index.php';
            redirect('../' . ltrim($home, '/'));
        }
    }
}

// CSRF PROTECTION
if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (
            empty($_POST['csrf_token']) ||
            empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            die('Lỗi bảo mật: CSRF token không hợp lệ!');
        }
    }
}

// DATABASE HELPERS
if (!function_exists('db_query')) {
    function db_query($sql, $params = [])
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            die('Lỗi truy vấn CSDL: ' . $e->getMessage());
        }
    }
}

if (!function_exists('db_fetch_all')) {
    function db_fetch_all($sql, $params = [])
    {
        return db_query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('db_fetch_one')) {
    function db_fetch_one($sql, $params = [])
    {
        return db_query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('db_fetch_var')) {
    function db_fetch_var($sql, $params = [])
    {
        return db_query($sql, $params)->fetchColumn();
    }
}

if (!function_exists('db_insert')) {
    function db_insert($sql, $params = [])
    {
        global $pdo;
        db_query($sql, $params);
        return $pdo->lastInsertId();
    }
}

if (!function_exists('resetAutoIncrement')) {
    function resetAutoIncrement($pdo, $table)
    {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        if ($count == 0) {
            $pdo->exec("ALTER TABLE `$table` AUTO_INCREMENT = 1");
            return true;
        }
        return false;
    }
}

// --- 1. Quản lý Môn học (Subjects) ---

if (!function_exists('createSubject')) {
    function createSubject($code = '', $name = '', $description = '')
    {
        global $pdo;
        try {
            if (empty($name) && !empty($code)) {
                $name = $code;
            }
            $stmt = $pdo->prepare("INSERT INTO subjects (code, name, description, status) VALUES (?, ?, ?, 'active')");
            return $stmt->execute([trim($code), trim($name), trim($description)]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('updateSubject')) {
    function updateSubject($id, $code = '', $name = '', $description = '', $status = 'active')
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare("UPDATE subjects SET code = ?, name = ?, description = ?, status = ? WHERE id = ?");
            return $stmt->execute([trim($code), trim($name), trim($description), $status, (int)$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('deleteSubject')) {
    function deleteSubject($id)
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
            return $stmt->execute([(int)$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

// --- 2. Quản lý Phòng thi (Exam Rooms) ---

if (!function_exists('createRoom')) {
    function createRoom($room_code = '', $room_name = '', $description = '', $exam_id = null, $max_participants = 10)
    {
        global $pdo;
        try {
            $created_by = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
            $stmt = $pdo->prepare("
                INSERT INTO exam_rooms (room_code, room_name, exam_id, max_participants, created_by, status) 
                VALUES (?, ?, ?, ?, ?, 'waiting')
            ");
            return $stmt->execute([trim($room_code), trim($room_name), $exam_id, (int)$max_participants, $created_by]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('updateRoom')) {
    function updateRoom($id, $room_code = '', $room_name = '', $description = '', $status = 'waiting')
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                UPDATE exam_rooms 
                SET room_code = ?, room_name = ?, status = ? 
                WHERE id = ?
            ");
            return $stmt->execute([trim($room_code), trim($room_name), $status, (int)$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('deleteRoom')) {
    function deleteRoom($id)
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare("DELETE FROM exam_rooms WHERE id = ?");
            return $stmt->execute([(int)$id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

    if (!function_exists('auto_init_tables')) {
        function auto_init_tables()
        {
            global $pdo;
            if (!$pdo) return;

            $queries = [
                "CREATE TABLE IF NOT EXISTS `subjects` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `code` VARCHAR(50) NULL,
                    `name` VARCHAR(255) NOT NULL,
                    `description` TEXT NULL,
                    `status` ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active',
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `exams` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `title` VARCHAR(255) NOT NULL,
                    `subject_id` INT NOT NULL,
                    `duration` INT NOT NULL DEFAULT 45,
                    `pass_score` FLOAT NOT NULL DEFAULT 5.0,
                    `description` TEXT NULL,
                    `created_by` INT NULL,
                    `is_public` TINYINT(1) DEFAULT 1,
                    `status` ENUM('active', 'inactive', 'pending', 'approved', 'rejected') DEFAULT 'approved',
                    `views_count` INT DEFAULT 0,
                    `downloads_count` INT DEFAULT 0,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                // TẠO BẢNG QUESTIONS ĐA DẠNG CÂU HỎI (Single, Multiple, True/False, Fill Blank, Matching)
                "CREATE TABLE IF NOT EXISTS `questions` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `exam_id` INT NOT NULL,
                    `question_type` VARCHAR(50) NOT NULL DEFAULT 'single_choice',
                    `question_text` TEXT NOT NULL,
                    `options` LONGTEXT NULL,
                    `correct_answer` LONGTEXT NULL,
                    `score` FLOAT DEFAULT 1.0,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX (`exam_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `exam_submissions` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `exam_id` INT NOT NULL,
                    `student_id` INT NOT NULL,
                    `score` DECIMAL(4,2) DEFAULT 0.00,
                    `duration_seconds` INT DEFAULT 0,
                    `cheat_count` INT DEFAULT 0,
                    `status` ENUM('in_progress', 'completed') DEFAULT 'completed',
                    `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `student_wrong_questions` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `student_id` INT NOT NULL,
                    `question_id` INT NOT NULL,
                    `selected_option` VARCHAR(255) DEFAULT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY `unique_student_question` (`student_id`, `question_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `exam_rooms` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `room_name` VARCHAR(255) NOT NULL,
                    `room_code` VARCHAR(50) NOT NULL UNIQUE,
                    `exam_id` INT DEFAULT NULL,
                    `teacher_id` INT NULL DEFAULT NULL,
                    `max_participants` INT DEFAULT 10,
                    `created_by` INT NULL,
                    `status` ENUM('waiting', 'active', 'closed') DEFAULT 'waiting',
                    `start_time` DATETIME NULL DEFAULT NULL,
                    `end_time` DATETIME NULL DEFAULT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `categories` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255) NOT NULL,
                    `duration` INT DEFAULT 30,
                    `description` TEXT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                "CREATE TABLE IF NOT EXISTS `teacher_subjects` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `subject_id` INT NOT NULL,
                    `teacher_id` INT NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
            ];

            // 1. Thực thi tạo các bảng
            foreach ($queries as $sql) {
                try {
                    $pdo->exec($sql);
                } catch (PDOException $e) {
                    // Bỏ qua lỗi nếu bảng đã tồn tại
                }
            }

            // 2. Cập nhật cấu trúc bảng EXAM_ROOMS
            try {
                $pdo->exec("ALTER TABLE `exam_rooms` MODIFY COLUMN `status` ENUM('waiting', 'active', 'closed', 'ongoing', 'finished') DEFAULT 'waiting'");
            } catch (PDOException $e) {
                try {
                    $pdo->exec("ALTER TABLE `exam_rooms` MODIFY COLUMN `status` VARCHAR(50) DEFAULT 'waiting'");
                } catch (PDOException $ex) {
                }
            }

            // 3. Tự động kiểm tra và thêm/sửa các cột cho bảng QUESTIONS
            try {
                $check_qtype = $pdo->query("SHOW COLUMNS FROM `questions` LIKE 'question_type'")->fetch();
                if (!$check_qtype) {
                    $pdo->exec("ALTER TABLE `questions` ADD COLUMN `question_type` VARCHAR(50) NOT NULL DEFAULT 'single_choice' AFTER `exam_id` ");
                }

                $check_opts = $pdo->query("SHOW COLUMNS FROM `questions` LIKE 'options'")->fetch();
                if (!$check_opts) {
                    $pdo->exec("ALTER TABLE `questions` ADD COLUMN `options` LONGTEXT NULL AFTER `question_text` ");
                }

                $check_ans = $pdo->query("SHOW COLUMNS FROM `questions` LIKE 'correct_answer'")->fetch();
                if (!$check_ans) {
                    $pdo->exec("ALTER TABLE `questions` ADD COLUMN `correct_answer` LONGTEXT NULL AFTER `options` ");
                }
            } catch (PDOException $e) {
            }

            // 4. Các nâng cấp cấu trúc bổ sung cho các bảng khác
            try {
                $pdo->exec("DELETE e1 FROM `exams` e1 
                            INNER JOIN `exams` e2 
                            WHERE e1.id < e2.id AND e1.title = e2.title AND e1.created_by = e2.created_by");

                $check_teacher = $pdo->query("SHOW COLUMNS FROM `exam_rooms` LIKE 'teacher_id'")->fetch();
                if ($check_teacher) {
                    $pdo->exec("ALTER TABLE `exam_rooms` MODIFY COLUMN `teacher_id` INT NULL DEFAULT NULL");
                } else {
                    $pdo->exec("ALTER TABLE `exam_rooms` ADD COLUMN `teacher_id` INT NULL DEFAULT NULL AFTER `exam_id` ");
                }

                $check_start_time = $pdo->query("SHOW COLUMNS FROM `exam_rooms` LIKE 'start_time'")->fetch();
                if ($check_start_time) {
                    $pdo->exec("ALTER TABLE `exam_rooms` MODIFY COLUMN `start_time` DATETIME NULL DEFAULT NULL");
                } else {
                    $pdo->exec("ALTER TABLE `exam_rooms` ADD COLUMN `start_time` DATETIME NULL DEFAULT NULL AFTER `status` ");
                }

                $check_end_time = $pdo->query("SHOW COLUMNS FROM `exam_rooms` LIKE 'end_time'")->fetch();
                if ($check_end_time) {
                    $pdo->exec("ALTER TABLE `exam_rooms` MODIFY COLUMN `end_time` DATETIME NULL DEFAULT NULL");
                } else {
                    $pdo->exec("ALTER TABLE `exam_rooms` ADD COLUMN `end_time` DATETIME NULL DEFAULT NULL AFTER `start_time` ");
                }

                $check_max = $pdo->query("SHOW COLUMNS FROM `exam_rooms` LIKE 'max_participants'")->fetch();
                if (!$check_max) {
                    $pdo->exec("ALTER TABLE `exam_rooms` ADD COLUMN `max_participants` INT DEFAULT 10 AFTER `teacher_id` ");
                }

                $check_owner = $pdo->query("SHOW COLUMNS FROM `exam_rooms` LIKE 'created_by'")->fetch();
                if (!$check_owner) {
                    $pdo->exec("ALTER TABLE `exam_rooms` ADD COLUMN `created_by` INT NULL AFTER `max_participants` ");
                }

                $check_cheat = $pdo->query("SHOW COLUMNS FROM `exam_submissions` LIKE 'cheat_count'")->fetch();
                if (!$check_cheat) {
                    $pdo->exec("ALTER TABLE `exam_submissions` ADD COLUMN `cheat_count` INT DEFAULT 0 AFTER `duration_seconds` ");
                }
            } catch (PDOException $e) {
            }

            // 5. Kiểm tra bảng SUBJECTS
            try {
                $check_col = $pdo->query("SHOW COLUMNS FROM `subjects` LIKE 'subject_name'")->fetch();
                if (!$check_col) {
                    $pdo->exec("ALTER TABLE `subjects` ADD COLUMN `subject_name` VARCHAR(255) NULL AFTER `name`");
                    $pdo->exec("UPDATE `subjects` SET `subject_name` = `name` WHERE `subject_name` IS NULL OR `subject_name` = ''");
                }

                $checkCol = $pdo->query("SHOW COLUMNS FROM `subjects` LIKE 'description'");
                if ($checkCol->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE `subjects` ADD COLUMN `description` TEXT NULL AFTER `subject_name`");
                }
            } catch (PDOException $e) {
            }
            try {
                $check_exp = $pdo->query("SHOW COLUMNS FROM `questions` LIKE 'explanation'")->fetch();
                if (!$check_exp) {
                    $pdo->exec("ALTER TABLE `questions` ADD COLUMN `explanation` TEXT NULL AFTER `correct_answer` ");
                }
            } catch (PDOException $e) {
            }
            try {
            // 1. Tạo bảng exams nếu chưa có
            $pdo->exec("CREATE TABLE IF NOT EXISTS `exams` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `exam_code` VARCHAR(50) NULL,
                `title` VARCHAR(255) NOT NULL,
                `subject_id` INT NOT NULL,
                `duration` INT NOT NULL DEFAULT 45,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // 2. Tự động sửa/nâng cấp kiểu dữ liệu cột 'status' nếu bảng đã tồn tại sẵn từ trước
            $stmt = $pdo->query("SHOW COLUMNS FROM `exams` LIKE 'status'");
            $column = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($column) {
                $type = strtolower($column['Type']);
                // Nếu status là TINYINT, INT hoặc ENUM('0','1') -> Sửa thành VARCHAR(20)
                if (strpos($type, 'int') !== false || (strpos($type, 'enum') !== false && strpos($type, 'active') === false)) {
                    $pdo->exec("ALTER TABLE `exams` MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active'");
                }
            }
        } catch (PDOException $e) {
            // Bỏ qua lỗi hoặc ghi log nếu cần
        }
        }
    }

    // Chạy khởi tạo tự động
    auto_init_tables();


if (!function_exists('getSubjects')) {
    function getSubjects(string $q = ''): array
    {
        $where  = '';
        $params = [];

        if ($q !== '') {
            $where  = "WHERE s.name LIKE ? OR s.code LIKE ?";
            $params = ['%' . $q . '%', '%' . $q . '%'];
        }

        return db_fetch_all("
            SELECT s.*, COUNT(DISTINCT e.id) AS total_exams
            FROM subjects s
            LEFT JOIN exams e ON s.id = e.subject_id AND e.status = 'published'
            $where
            GROUP BY s.id
            ORDER BY s.name ASC
        ", $params) ?: [];
    }
}

if (!function_exists('columnExists')) {
    function columnExists(PDO $pdo, string $table, string $column): bool
    {
        static $cache = [];
        $key = "$table.$column";
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            $cache[$key] = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $cache[$key] = false;
        }
        return $cache[$key];
    }
}

if (!function_exists('indexExists')) {
    function indexExists(PDO $pdo, string $table, string $indexName): bool
    {
        try {
            $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
            $stmt->execute([$indexName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('tableExists')) {
    function tableExists(PDO $pdo, string $table): bool
    {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('autoInitTables')) {
    function autoInitTables(PDO $pdo): void
    {
        static $alreadyRan = false;
        if ($alreadyRan) {
            return;
        }
        $alreadyRan = true;

        $createStatements = [
            'subjects' => "
                CREATE TABLE IF NOT EXISTS subjects (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    subject_name VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'exams' => "
                CREATE TABLE IF NOT EXISTS exams (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    subject_id INT NULL,
                    duration INT DEFAULT 60,
                    description TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'questions' => "
                CREATE TABLE IF NOT EXISTS questions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    exam_id INT NULL,
                    subject_id INT NULL,
                    content TEXT NOT NULL,
                    option_a VARCHAR(1000) NULL,
                    option_b VARCHAR(1000) NULL,
                    option_c VARCHAR(1000) NULL,
                    option_d VARCHAR(1000) NULL,
                    correct_answer VARCHAR(5) DEFAULT 'A',
                    difficulty VARCHAR(20) DEFAULT 'easy',
                    explanation TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];

        foreach ($createStatements as $sql) {
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                error_log('[autoInitTables] Create table failed: ' . $e->getMessage());
            }
        }

        $columnsToEnsure = [
            'questions' => [
                'exam_id'        => "ALTER TABLE questions ADD COLUMN exam_id INT NULL AFTER id",
                'subject_id'     => "ALTER TABLE questions ADD COLUMN subject_id INT NULL",
                'difficulty'     => "ALTER TABLE questions ADD COLUMN difficulty VARCHAR(20) DEFAULT 'easy'",
                'explanation'    => "ALTER TABLE questions ADD COLUMN explanation TEXT NULL",
                'correct_answer' => "ALTER TABLE questions ADD COLUMN correct_answer VARCHAR(5) DEFAULT 'A'",
            ],
            'exams' => [
                'duration'    => "ALTER TABLE exams ADD COLUMN duration INT DEFAULT 60",
                'description' => "ALTER TABLE exams ADD COLUMN description TEXT NULL",
                'subject_id'  => "ALTER TABLE exams ADD COLUMN subject_id INT NULL",
            ],
            'subjects' => [
                'subject_name' => "ALTER TABLE subjects ADD COLUMN subject_name VARCHAR(255) NOT NULL",
            ],
        ];

        foreach ($columnsToEnsure as $table => $cols) {
            if (!tableExists($pdo, $table)) {
                continue;
            }
            foreach ($cols as $column => $alterSql) {
                if (!columnExists($pdo, $table, $column)) {
                    try {
                        $pdo->exec($alterSql);
                    } catch (PDOException $e) {
                        error_log("[autoInitTables] Add column {$table}.{$column} failed: " . $e->getMessage());
                    }
                }
            }
        }

        if (
            tableExists($pdo, 'questions')
            && columnExists($pdo, 'questions', 'exam_id')
            && !indexExists($pdo, 'questions', 'idx_questions_exam_id')
        ) {
            try {
                $pdo->exec("ALTER TABLE questions ADD INDEX idx_questions_exam_id (exam_id)");
            } catch (PDOException $e) {
                error_log('[autoInitTables] Add index failed: ' . $e->getMessage());
            }
        }

        if (
            tableExists($pdo, 'questions')
            && columnExists($pdo, 'questions', 'subject_id')
            && !indexExists($pdo, 'questions', 'idx_questions_subject_id')
        ) {
            try {
                $pdo->exec("ALTER TABLE questions ADD INDEX idx_questions_subject_id (subject_id)");
            } catch (PDOException $e) {
                error_log('[autoInitTables] Add index failed: ' . $e->getMessage());
            }
        }
    }
}
/**
 * Lấy giá trị cài đặt từ bảng settings
 */
if (!function_exists('getSetting')) {
function getSetting($key, $default = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}
}

/**
 * Kiểm tra chế độ bảo trì - Nếu bật và không phải Admin thì chặn lại
 */
if (!function_exists('checkMaintenanceMode')) {
function checkMaintenanceMode() {
    // Nếu là Admin thì vẫn cho phép vào hệ thống
    if (function_exists('isAdmin') && isAdmin()) {
        return;
    }

    // Kiểm tra trạng thái bảo trì trong CSDL
    $isMaintenance = (int)getSetting('maintenance_mode', 0);

    if ($isMaintenance === 1) {
        http_response_code(503);
        echo '
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Hệ thống đang bảo trì</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700&display=swap" rel="stylesheet">
            <style>
                body {
                    background: #f8fafc;
                    font-family: "Plus Jakarta Sans", sans-serif;
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .card-maintenance {
                    background: #ffffff;
                    border-radius: 24px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
                    max-width: 480px;
                    width: 90%;
                    padding: 2.5rem;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="card-maintenance">
                <div class="display-1 text-warning mb-3">🛠️</div>
                <h3 class="fw-bold text-dark mb-2">Hệ Thống Đang Bảo Trì</h3>
                <p class="text-muted mb-4">Hệ thống thi trắc nghiệm đang trong quá trình nâng cấp và bảo trì định kỳ. Sinh viên vui lòng quay lại sau!</p>
                <a href="javascript:location.reload()" class="btn btn-outline-primary rounded-pill px-4">Thử tải lại trang</a>
            </div>
        </body>
        </html>';
        exit;
    }
}
}

// Auto DB Migration - Tự động nâng cấp bảng nếu chưa có cột
function auto_migrate_db() {
    global $pdo;
    if (!$pdo) return;

    $queries = [
        // Bảng exams
        "ALTER TABLE exams ADD COLUMN IF NOT EXISTS user_id INT NULL",
        "ALTER TABLE exams ADD COLUMN IF NOT EXISTS exam_code VARCHAR(20) NULL",
        "ALTER TABLE exams ADD COLUMN IF NOT EXISTS description TEXT NULL",
        "ALTER TABLE exams ADD COLUMN IF NOT EXISTS time_limit INT DEFAULT 20",
        "ALTER TABLE exams ADD COLUMN IF NOT EXISTS question_count INT DEFAULT 10",

        // Bảng questions
        "ALTER TABLE questions ADD COLUMN IF NOT EXISTS user_id INT NULL",
        "ALTER TABLE questions ADD COLUMN IF NOT EXISTS option_a TEXT NULL",
        "ALTER TABLE questions ADD COLUMN IF NOT EXISTS option_b TEXT NULL",
        "ALTER TABLE questions ADD COLUMN IF NOT EXISTS option_c TEXT NULL",
        "ALTER TABLE questions ADD COLUMN IF NOT EXISTS option_d TEXT NULL",
        "ALTER TABLE questions ADD COLUMN IF NOT EXISTS correct_answer VARCHAR(10) DEFAULT 'A'",
        "ALTER TABLE questions ADD COLUMN IF NOT EXISTS difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium'",
        "ALTER TABLE questions ADD COLUMN IF NOT EXISTS explanation TEXT NULL",

        // Bảng exam_rooms (Phòng thi)
        "CREATE TABLE IF NOT EXISTS exam_rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            exam_id INT NOT NULL,
            room_code VARCHAR(20) NOT NULL UNIQUE,
            status ENUM('active', 'closed') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Bỏ qua lỗi nếu MySQL phiên bản cũ không hỗ trợ IF NOT EXISTS
        }
    }
}

// Chạy tự động
auto_migrate_db();
/**
 * Lấy danh sách môn học của giáo viên
 */
if (!function_exists('getTeacherSubjects')) {
    function getTeacherSubjects($teacher_id) {
        global $pdo;
        if (!$pdo) return [];
        
        try {
            // Kiểm tra bảng teacher_subjects có tồn tại không
            $stmt = $pdo->query("SHOW TABLES LIKE 'teacher_subjects'");
            if ($stmt->rowCount() == 0) {
                // Nếu chưa có bảng, tạo bảng
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `teacher_subjects` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `subject_id` INT NOT NULL,
                        `teacher_id` INT NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE KEY `unique_teacher_subject` (`teacher_id`, `subject_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
                return [];
            }
            
            $stmt = $pdo->prepare("
                SELECT s.* 
                FROM subjects s
                INNER JOIN teacher_subjects ts ON s.id = ts.subject_id
                WHERE ts.teacher_id = ?
                ORDER BY s.name
            ");
            $stmt->execute([$teacher_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getTeacherSubjects error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Lấy tên môn học theo ID
 */
if (!function_exists('getSubjectName')) {
    function getSubjectName($subject_id) {
        global $pdo;
        if (!$pdo) return 'Môn học';
        
        try {
            $stmt = $pdo->prepare("SELECT name FROM subjects WHERE id = ?");
            $stmt->execute([$subject_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['name'] : 'Môn học';
        } catch (Exception $e) {
            return 'Môn học';
        }
    }
}

/**
 * Tạo câu hỏi bằng AI (Sử dụng dữ liệu mẫu thông minh)
 * 
 * @param int $subject_id ID của môn học
 * @param int $num_questions Số lượng câu hỏi cần tạo
 * @param string $difficulty Độ khó: easy, medium, hard, mixed
 * @param string $topic Chủ đề cụ thể (optional)
 * @return array Mảng câu hỏi đã tạo
 */
if (!function_exists('generateQuestionsWithAI')) {
    function generateQuestionsWithAI($subject_id, $num_questions, $difficulty, $topic = '') {
        // Lấy tên môn học
        $subject_name = getSubjectName($subject_id);
        
        // Nếu không có chủ đề, tạo chủ đề từ tên môn học
        if (empty($topic)) {
            $topic = $subject_name;
        }
        
        // Sử dụng dữ liệu mẫu để tạo câu hỏi
        return generateQuestionsWithMockData($subject_name, $num_questions, $difficulty, $topic);
    }
}

/**
 * Tạo câu hỏi bằng dữ liệu mẫu
 */
if (!function_exists('generateQuestionsWithMockData')) {
    function generateQuestionsWithMockData($subject_name, $num_questions, $difficulty, $topic) {
        $questions = [];
        
        // Mẫu câu hỏi theo môn học
        $subject_questions = getSubjectQuestionBank($subject_name);
        
        // Nếu không có câu hỏi mẫu, tạo câu hỏi chung về CNTT
        if (empty($subject_questions)) {
            $subject_questions = getDefaultITQuestionBank();
        }
        
        // Lấy số lượng câu hỏi cần
        $total_available = count($subject_questions);
        
        // Nếu yêu cầu nhiều hơn số có sẵn, lặp lại
        for ($i = 0; $i < $num_questions; $i++) {
            $index = $i % $total_available;
            $sample = $subject_questions[$index];
            
            // Tạo câu hỏi từ mẫu
            $question_text = "Câu " . ($i + 1) . ": " . $sample['question'];
            $options = $sample['options'];
            $correct_index = $sample['correct'] ?? rand(0, 3);
            
            // Điều chỉnh độ khó
            $question_difficulty = $difficulty;
            if ($difficulty === 'mixed') {
                $levels = ['easy', 'medium', 'hard'];
                $question_difficulty = $levels[$i % 3];
            }
            
            $questions[] = [
                'question' => $question_text,
                'options' => $options,
                'correct' => $correct_index,
                'difficulty' => $question_difficulty,
                'explanation' => 'Đáp án đúng là ' . $options[$correct_index] . '.'
            ];
        }
        
        return $questions;
    }
}

/**
 * Lấy ngân hàng câu hỏi mẫu theo môn học (Chỉ các môn CNTT)
 */
if (!function_exists('getSubjectQuestionBank')) {
    function getSubjectQuestionBank($subject_name) {
        $banks = [
            // Lập trình Web
            'Lập trình Web' => [
                ['question' => 'Ngôn ngữ lập trình nào được sử dụng để phát triển web phía server?', 'options' => ['PHP', 'JavaScript', 'HTML', 'CSS'], 'correct' => 0],
                ['question' => 'HTML là viết tắt của?', 'options' => ['HyperText Markup Language', 'HighTech Machine Language', 'HyperTransfer Markup Language', 'HomeText Markup Language'], 'correct' => 0],
                ['question' => 'CSS dùng để làm gì?', 'options' => ['Trang trí giao diện', 'Xử lý dữ liệu', 'Tạo cơ sở dữ liệu', 'Lập trình backend'], 'correct' => 0],
                ['question' => 'Thẻ HTML nào dùng để tạo liên kết?', 'options' => ['<a>', '<link>', '<href>', '<url>'], 'correct' => 0],
                ['question' => 'JavaScript chạy ở đâu?', 'options' => ['Trình duyệt', 'Máy chủ', 'Cả A và B', 'Không nơi nào'], 'correct' => 2],
                ['question' => 'Framework PHP phổ biến nhất hiện nay là?', 'options' => ['Laravel', 'Symfony', 'CodeIgniter', 'Yii'], 'correct' => 0],
                ['question' => 'Cú pháp để in ra màn hình trong PHP là:', 'options' => ['echo', 'print', 'printf', 'Tất cả đều đúng'], 'correct' => 3],
                ['question' => 'Biến trong PHP bắt đầu bằng ký tự nào?', 'options' => ['$', '#', '@', '&'], 'correct' => 0],
                ['question' => 'Phương thức HTTP nào được sử dụng để gửi dữ liệu biểu mẫu?', 'options' => ['POST', 'GET', 'PUT', 'DELETE'], 'correct' => 0],
                ['question' => 'JSON là viết tắt của?', 'options' => ['JavaScript Object Notation', 'Java Standard Object Notation', 'JavaScript Online Notation', 'Java Source Object Notation'], 'correct' => 0],
            ],
            
            // Cơ sở dữ liệu
            'Cơ sở dữ liệu' => [
                ['question' => 'SQL là viết tắt của?', 'options' => ['Structured Query Language', 'Standard Query Language', 'Simple Query Language', 'System Query Language'], 'correct' => 0],
                ['question' => 'Lệnh SQL nào dùng để lấy dữ liệu từ bảng?', 'options' => ['SELECT', 'INSERT', 'UPDATE', 'DELETE'], 'correct' => 0],
                ['question' => 'Khóa chính trong CSDL là gì?', 'options' => ['Dùng để xác định duy nhất mỗi bản ghi', 'Khóa ngoại', 'Khóa dùng để tìm kiếm', 'Không phải khóa'], 'correct' => 0],
                ['question' => 'MySQL là loại CSDL gì?', 'options' => ['Quan hệ', 'Phi quan hệ', 'Đối tượng', 'XML'], 'correct' => 0],
                ['question' => 'Câu lệnh nào dùng để tạo bảng trong SQL?', 'options' => ['CREATE TABLE', 'CREATE DATABASE', 'CREATE INDEX', 'ALTER TABLE'], 'correct' => 0],
                ['question' => 'INDEX trong CSDL dùng để làm gì?', 'options' => ['Tăng tốc truy vấn', 'Tạo khóa chính', 'Tạo quan hệ', 'Xóa dữ liệu'], 'correct' => 0],
                ['question' => 'Hàm COUNT() trong SQL dùng để?', 'options' => ['Đếm số hàng', 'Đếm số cột', 'Tính tổng', 'Tính trung bình'], 'correct' => 0],
                ['question' => 'Câu lệnh nào dùng để cập nhật dữ liệu?', 'options' => ['UPDATE', 'INSERT', 'DELETE', 'ALTER'], 'correct' => 0],
            ],
            
            // Lập trình hướng đối tượng
            'Lập trình hướng đối tượng' => [
                ['question' => 'OOP là viết tắt của?', 'options' => ['Object-Oriented Programming', 'Online Object Programming', 'Object-Origin Programming', 'Official Object Programming'], 'correct' => 0],
                ['question' => '4 tính chất cơ bản của OOP là gì?', 'options' => ['Đóng gói, Kế thừa, Đa hình, Trừu tượng', 'Đóng gói, Kế thừa, Đa nhiệm, Trừu tượng', 'Đóng gói, Kế thừa, Đa hình, Tái sử dụng', 'Kế thừa, Đa hình, Trừu tượng, Tái sử dụng'], 'correct' => 0],
                ['question' => 'Lớp trong OOP là gì?', 'options' => ['Khuôn mẫu để tạo đối tượng', 'Một đối tượng cụ thể', 'Một biến', 'Một hàm'], 'correct' => 0],
                ['question' => 'Đối tượng trong OOP là gì?', 'options' => ['Thể hiện của lớp', 'Một lớp cụ thể', 'Một phương thức', 'Một thuộc tính'], 'correct' => 0],
                ['question' => 'Tính kế thừa (Inheritance) trong OOP là gì?', 'options' => ['Lớp con kế thừa từ lớp cha', 'Lớp cha kế thừa từ lớp con', 'Hai lớp không liên quan', 'Một lớp chỉ có một phương thức'], 'correct' => 0],
                ['question' => 'Tính đa hình (Polymorphism) trong OOP là gì?', 'options' => ['Cùng một tên phương thức nhưng hành vi khác nhau', 'Một lớp có nhiều phương thức', 'Một đối tượng có nhiều lớp', 'Không liên quan đến OOP'], 'correct' => 0],
                ['question' => 'Tính đóng gói (Encapsulation) trong OOP có ý nghĩa gì?', 'options' => ['Che giấu dữ liệu bên trong', 'Mở tất cả dữ liệu', 'Chia sẻ dữ liệu công khai', 'Không liên quan'], 'correct' => 0],
            ],
            
            // Cấu trúc dữ liệu và giải thuật
            'Cấu trúc dữ liệu' => [
                ['question' => 'Mảng là gì?', 'options' => ['Tập hợp các phần tử có cùng kiểu dữ liệu', 'Tập hợp các phần tử khác kiểu', 'Một biến đơn', 'Một hàm'], 'correct' => 0],
                ['question' => 'Cấu trúc dữ liệu Stack hoạt động theo nguyên tắc nào?', 'options' => ['LIFO (Last In First Out)', 'FIFO (First In First Out)', 'LILO (Last In Last Out)', 'FILO (First In Last Out)'], 'correct' => 0],
                ['question' => 'Cấu trúc dữ liệu Queue hoạt động theo nguyên tắc nào?', 'options' => ['FIFO (First In First Out)', 'LIFO (Last In First Out)', 'LILO (Last In Last Out)', 'FILO (First In Last Out)'], 'correct' => 0],
                ['question' => 'Linked List là gì?', 'options' => ['Cấu trúc dữ liệu động', 'Cấu trúc dữ liệu tĩnh', 'Một mảng', 'Một biến'], 'correct' => 0],
                ['question' => 'Độ phức tạp của thuật toán tìm kiếm nhị phân là?', 'options' => ['O(log n)', 'O(n)', 'O(n²)', 'O(1)'], 'correct' => 0],
                ['question' => 'Độ phức tạp của thuật toán sắp xếp nổi bọt (Bubble Sort) là?', 'options' => ['O(n²)', 'O(n log n)', 'O(n)', 'O(log n)'], 'correct' => 0],
                ['question' => 'Độ phức tạp của thuật toán sắp xếp nhanh (Quick Sort) trung bình là?', 'options' => ['O(n log n)', 'O(n²)', 'O(n)', 'O(log n)'], 'correct' => 0],
                ['question' => 'Tree là cấu trúc dữ liệu gì?', 'options' => ['Cấu trúc phân cấp', 'Cấu trúc tuyến tính', 'Cấu trúc ngang', 'Cấu trúc vòng'], 'correct' => 0],
            ],
            
            // Mạng máy tính
            'Mạng máy tính' => [
                ['question' => 'OSI model có bao nhiêu tầng?', 'options' => ['7', '5', '4', '3'], 'correct' => 0],
                ['question' => 'TCP/IP là viết tắt của?', 'options' => ['Transmission Control Protocol/Internet Protocol', 'Transfer Control Protocol/Internet Protocol', 'Transmission Control Program/Internet Protocol', 'Transfer Control Program/Internet Protocol'], 'correct' => 0],
                ['question' => 'HTTP là viết tắt của?', 'options' => ['HyperText Transfer Protocol', 'HighTech Transfer Protocol', 'HyperText Transport Protocol', 'HighTech Transport Protocol'], 'correct' => 0],
                ['question' => 'Port của HTTP và HTTPS lần lượt là?', 'options' => ['80 và 443', '443 và 80', '8080 và 443', '80 và 8080'], 'correct' => 0],
                ['question' => 'IP là viết tắt của?', 'options' => ['Internet Protocol', 'Internet Program', 'Internal Protocol', 'International Protocol'], 'correct' => 0],
                ['question' => 'DNS dùng để làm gì?', 'options' => ['Chuyển đổi tên miền thành IP', 'Chuyển đổi IP thành tên miền', 'Tạo tên miền', 'Quản lý IP'], 'correct' => 0],
                ['question' => 'Lớp mạng (Network Layer) trong OSI model có chức năng gì?', 'options' => ['Định tuyến', 'Truyền dữ liệu', 'Mã hóa', 'Xác thực'], 'correct' => 0],
                ['question' => 'Giao thức nào dùng để gửi email?', 'options' => ['SMTP', 'FTP', 'HTTP', 'DNS'], 'correct' => 0],
            ],
            
            // Hệ điều hành
            'Hệ điều hành' => [
                ['question' => 'Hệ điều hành nào sau đây là mã nguồn mở?', 'options' => ['Linux', 'Windows', 'macOS', 'DOS'], 'correct' => 0],
                ['question' => 'Linux thuộc họ hệ điều hành nào?', 'options' => ['Unix-like', 'Windows', 'MacOS', 'Android'], 'correct' => 0],
                ['question' => 'Hệ điều hành làm nhiệm vụ gì?', 'options' => ['Quản lý tài nguyên máy tính', 'Soạn thảo văn bản', 'Lập trình', 'Thiết kế đồ họa'], 'correct' => 0],
                ['question' => 'Đa nhiệm (Multitasking) là gì?', 'options' => ['Chạy nhiều tiến trình cùng lúc', 'Chạy một tiến trình', 'Chạy nhiều tiến trình tuần tự', 'Không liên quan'], 'correct' => 0],
                ['question' => 'Bộ nhớ ảo (Virtual Memory) dùng để làm gì?', 'options' => ['Mở rộng RAM', 'Thay thế RAM', 'Tăng tốc CPU', 'Tăng tốc ổ cứng'], 'correct' => 0],
                ['question' => 'System call trong OS là gì?', 'options' => ['Giao diện giữa chương trình và OS', 'Giao diện giữa phần cứng và OS', 'Giao diện giữa hai chương trình', 'Giao diện người dùng'], 'correct' => 0],
            ],
            
            // Trí tuệ nhân tạo
            'Trí tuệ nhân tạo' => [
                ['question' => 'AI là viết tắt của?', 'options' => ['Artificial Intelligence', 'Advanced Intelligence', 'Automated Intelligence', 'Algorithm Intelligence'], 'correct' => 0],
                ['question' => 'Machine Learning là gì?', 'options' => ['Máy học từ dữ liệu', 'Máy lập trình', 'Máy tính toán', 'Máy phân tích'], 'correct' => 0],
                ['question' => 'Deep Learning sử dụng mạng nơ-ron có bao nhiêu lớp?', 'options' => ['Nhiều lớp ẩn', '1 lớp', '2 lớp', 'Không có lớp'], 'correct' => 0],
                ['question' => 'Mạng nơ-ron tích chập (CNN) thường được dùng để làm gì?', 'options' => ['Xử lý ảnh', 'Xử lý văn bản', 'Xử lý âm thanh', 'Xử lý video'], 'correct' => 0],
                ['question' => 'Mạng nơ-ron hồi quy (RNN) thường được dùng để làm gì?', 'options' => ['Xử lý dữ liệu chuỗi', 'Xử lý ảnh', 'Xử lý số', 'Xử lý logic'], 'correct' => 0],
                ['question' => 'GPT là viết tắt của?', 'options' => ['Generative Pre-trained Transformer', 'General Purpose Transformer', 'Generative Processing Technology', 'General Processing Tool'], 'correct' => 0],
                ['question' => 'NLP là viết tắt của?', 'options' => ['Natural Language Processing', 'New Language Programming', 'Network Language Protocol', 'Next Level Processing'], 'correct' => 0],
            ],
            
            // An toàn thông tin
            'An toàn thông tin' => [
                ['question' => 'Mã hóa dùng để làm gì?', 'options' => ['Bảo vệ dữ liệu', 'Tăng tốc dữ liệu', 'Nén dữ liệu', 'Sao lưu dữ liệu'], 'correct' => 0],
                ['question' => 'Tấn công SQL Injection là gì?', 'options' => ['Tấn công vào CSDL qua SQL', 'Tấn công vào HTML', 'Tấn công vào CSS', 'Tấn công vào JavaScript'], 'correct' => 0],
                ['question' => 'XSS là viết tắt của?', 'options' => ['Cross-Site Scripting', 'Cross-Site Security', 'Cross-Site Server', 'Cross-Site System'], 'correct' => 0],
                ['question' => 'Tấn công DDoS là gì?', 'options' => ['Tấn công từ chối dịch vụ phân tán', 'Tấn công vào DNS', 'Tấn công vào Email', 'Tấn công vào Website'], 'correct' => 0],
                ['question' => 'Mật mã hóa bất đối xứng sử dụng bao nhiêu khóa?', 'options' => ['2 khóa (public và private)', '1 khóa', '3 khóa', 'Không dùng khóa'], 'correct' => 0],
                ['question' => 'HTTPS là HTTP với gì?', 'options' => ['SSL/TLS', 'SMTP', 'FTP', 'DNS'], 'correct' => 0],
                ['question' => 'Firewall dùng để làm gì?', 'options' => ['Bảo vệ mạng khỏi truy cập trái phép', 'Tăng tốc mạng', 'Quản lý mạng', 'Giám sát mạng'], 'correct' => 0],
            ],
            
            // Khoa học dữ liệu
            'Khoa học dữ liệu' => [
                ['question' => 'Big Data là gì?', 'options' => ['Dữ liệu khổng lồ và phức tạp', 'Dữ liệu nhỏ', 'Dữ liệu đơn giản', 'Dữ liệu cũ'], 'correct' => 0],
                ['question' => 'Python được sử dụng nhiều trong lĩnh vực nào?', 'options' => ['Khoa học dữ liệu', 'Thiết kế đồ họa', 'Kế toán', 'Nội thất'], 'correct' => 0],
                ['question' => 'Pandas trong Python dùng để làm gì?', 'options' => ['Xử lý dữ liệu', 'Vẽ đồ thị', 'Học máy', 'Phân tích số'], 'correct' => 0],
                ['question' => 'Data Visualization là gì?', 'options' => ['Trực quan hóa dữ liệu', 'Thu thập dữ liệu', 'Xử lý dữ liệu', 'Lưu trữ dữ liệu'], 'correct' => 0],
                ['question' => 'EDA trong Data Science là viết tắt của?', 'options' => ['Exploratory Data Analysis', 'Enterprise Data Analysis', 'Enhanced Data Analysis', 'External Data Analysis'], 'correct' => 0],
            ],
            
            // Điện toán đám mây
            'Điện toán đám mây' => [
                ['question' => 'Cloud Computing là gì?', 'options' => ['Cung cấp tài nguyên qua Internet', 'Lưu trữ trên máy tính', 'Phần mềm cài đặt', 'Mạng nội bộ'], 'correct' => 0],
                ['question' => 'IaaS là viết tắt của?', 'options' => ['Infrastructure as a Service', 'Internet as a Service', 'Information as a Service', 'Integration as a Service'], 'correct' => 0],
                ['question' => 'SaaS là viết tắt của?', 'options' => ['Software as a Service', 'System as a Service', 'Security as a Service', 'Storage as a Service'], 'correct' => 0],
                ['question' => 'PaaS là viết tắt của?', 'options' => ['Platform as a Service', 'Program as a Service', 'Process as a Service', 'Protocol as a Service'], 'correct' => 0],
                ['question' => 'AWS của hãng nào?', 'options' => ['Amazon', 'Google', 'Microsoft', 'IBM'], 'correct' => 0],
            ]
        ];
        
        // Tìm ngân hàng phù hợp (kiểm tra tất cả các tên môn học liên quan đến CNTT)
        $subject_name_lower = strtolower($subject_name);
        $it_keywords = ['web', 'database', 'data', 'oop', 'algorithm', 'network', 'operating', 'ai', 'security', 'cloud', 'python', 'java', 'php', 'sql', 'html', 'css', 'javascript', 'laravel', 'machine', 'deep', 'big data', 'software'];
        
        foreach ($banks as $subject => $question_list) {
            $subject_lower = strtolower($subject);
            // Kiểm tra tên môn học khớp hoặc chứa từ khóa
            if (stripos($subject, $subject_name) !== false || stripos($subject_name, $subject) !== false) {
                return $question_list;
            }
            // Kiểm tra từ khóa
            foreach ($it_keywords as $keyword) {
                if (stripos($subject_name_lower, $keyword) !== false && stripos($subject_lower, $keyword) !== false) {
                    return $question_list;
                }
            }
        }
        
        return [];
    }
}

/**
 * Lấy ngân hàng câu hỏi mặc định về CNTT
 */
if (!function_exists('getDefaultITQuestionBank')) {
    function getDefaultITQuestionBank() {
        return [
            ['question' => 'Ngôn ngữ lập trình nào được sử dụng để phát triển web?', 'options' => ['PHP', 'Python', 'Java', 'C++'], 'correct' => 0],
            ['question' => 'HTML là viết tắt của?', 'options' => ['HyperText Markup Language', 'HighTech Machine Language', 'HyperTransfer Markup Language', 'HomeText Markup Language'], 'correct' => 0],
            ['question' => 'CSS dùng để làm gì?', 'options' => ['Trang trí giao diện', 'Xử lý dữ liệu', 'Tạo cơ sở dữ liệu', 'Lập trình backend'], 'correct' => 0],
            ['question' => 'Cú pháp để in ra màn hình trong PHP là:', 'options' => ['echo', 'print', 'printf', 'Tất cả đều đúng'], 'correct' => 3],
            ['question' => 'Hệ điều hành nào sau đây là mã nguồn mở?', 'options' => ['Linux', 'Windows', 'macOS', 'DOS'], 'correct' => 0],
            ['question' => 'SQL là viết tắt của?', 'options' => ['Structured Query Language', 'Standard Query Language', 'Simple Query Language', 'System Query Language'], 'correct' => 0],
            ['question' => 'OOP là viết tắt của?', 'options' => ['Object-Oriented Programming', 'Online Object Programming', 'Object-Origin Programming', 'Official Object Programming'], 'correct' => 0],
            ['question' => 'AI là viết tắt của?', 'options' => ['Artificial Intelligence', 'Advanced Intelligence', 'Automated Intelligence', 'Algorithm Intelligence'], 'correct' => 0],
            ['question' => 'Mạng nơ-ron tích chập (CNN) thường được dùng để làm gì?', 'options' => ['Xử lý ảnh', 'Xử lý văn bản', 'Xử lý âm thanh', 'Xử lý video'], 'correct' => 0],
            ['question' => 'Cloud Computing là gì?', 'options' => ['Cung cấp tài nguyên qua Internet', 'Lưu trữ trên máy tính', 'Phần mềm cài đặt', 'Mạng nội bộ'], 'correct' => 0],
        ];
    }
}

/**
 * Lưu đề thi được tạo bởi AI vào database
 * 
 * @param string $title Tên đề thi
 * @param int $subject_id ID môn học
 * @param int $teacher_id ID giáo viên
 * @param array $questions Mảng câu hỏi
 * @return int|false ID của đề thi hoặc false nếu thất bại
 */
if (!function_exists('saveAIExam')) {
    function saveAIExam($title, $subject_id, $teacher_id, $questions) {
        global $pdo;
        if (!$pdo || empty($questions)) return false;
        
        try {
            $pdo->beginTransaction();
            
            // Tạo mã đề thi
            $exam_code = 'AI_' . date('Ymd') . '_' . strtoupper(substr(uniqid(), -6));
            
            // Kiểm tra bảng exams có cột exam_code không
            $check_column = $pdo->query("SHOW COLUMNS FROM exams LIKE 'exam_code'");
            $has_exam_code = $check_column->rowCount() > 0;
            
            // Kiểm tra bảng exams có cột code không
            $check_column2 = $pdo->query("SHOW COLUMNS FROM exams LIKE 'code'");
            $has_code = $check_column2->rowCount() > 0;
            
            // Tạo đề thi
            if ($has_exam_code) {
                $stmt = $pdo->prepare("
                    INSERT INTO exams (title, exam_code, subject_id, user_id, status, created_at)
                    VALUES (?, ?, ?, ?, 'draft', NOW())
                ");
                $stmt->execute([$title, $exam_code, $subject_id, $teacher_id]);
            } elseif ($has_code) {
                $stmt = $pdo->prepare("
                    INSERT INTO exams (title, code, subject_id, user_id, status, created_at)
                    VALUES (?, ?, ?, ?, 'draft', NOW())
                ");
                $stmt->execute([$title, $exam_code, $subject_id, $teacher_id]);
            } else {
                // Nếu không có cột exam_code hay code, thêm vào bảng với cấu trúc cơ bản
                $stmt = $pdo->prepare("
                    INSERT INTO exams (title, subject_id, user_id, status, created_at)
                    VALUES (?, ?, ?, 'draft', NOW())
                ");
                $stmt->execute([$title, $subject_id, $teacher_id]);
            }
            
            $exam_id = $pdo->lastInsertId();
            
            if (!$exam_id) {
                $pdo->rollBack();
                return false;
            }
            
            // Kiểm tra bảng questions có các cột cần thiết không
            $check_columns = $pdo->query("SHOW COLUMNS FROM questions");
            $columns = [];
            while ($row = $check_columns->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            
            // Xác định cột exam_id
            $exam_id_column = in_array('exam_id', $columns) ? 'exam_id' : (in_array('exam_id', $columns) ? 'exam_id' : 'exam_id');
            
            // Lưu câu hỏi
            if (in_array('option_a', $columns) && in_array('option_b', $columns) && in_array('option_c', $columns) && in_array('option_d', $columns)) {
                // Cấu trúc bảng questions có option_a, option_b, option_c, option_d
                $stmt_question = $pdo->prepare("
                    INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, difficulty, explanation, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                foreach ($questions as $index => $q) {
                    $options = $q['options'] ?? ['A', 'B', 'C', 'D'];
                    
                    // Đảm bảo có đủ 4 lựa chọn
                    while (count($options) < 4) {
                        $options[] = 'Lựa chọn ' . (count($options) + 1);
                    }
                    
                    $option_a = $options[0] ?? '';
                    $option_b = $options[1] ?? '';
                    $option_c = $options[2] ?? '';
                    $option_d = $options[3] ?? '';
                    
                    $correct_index = isset($q['correct']) ? intval($q['correct']) : 0;
                    if ($correct_index >= count($options)) {
                        $correct_index = 0;
                    }
                    $correct_answer = $options[$correct_index] ?? $options[0] ?? '';
                    
                    $difficulty = $q['difficulty'] ?? 'mixed';
                    $explanation = $q['explanation'] ?? 'Đáp án đúng là: ' . $correct_answer;
                    
                    $stmt_question->execute([
                        $exam_id,
                        $q['question'],
                        $option_a,
                        $option_b,
                        $option_c,
                        $option_d,
                        $correct_answer,
                        $difficulty,
                        $explanation
                    ]);
                }
            } elseif (in_array('options', $columns) && in_array('correct_answer', $columns)) {
                // Cấu trúc bảng questions có options và correct_answer dạng JSON
                $stmt_question = $pdo->prepare("
                    INSERT INTO questions (exam_id, question_text, options, correct_answer, difficulty, explanation, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                foreach ($questions as $index => $q) {
                    $options = $q['options'] ?? ['A', 'B', 'C', 'D'];
                    $correct_index = isset($q['correct']) ? intval($q['correct']) : 0;
                    if ($correct_index >= count($options)) {
                        $correct_index = 0;
                    }
                    $correct_answer = $options[$correct_index] ?? $options[0] ?? '';
                    $difficulty = $q['difficulty'] ?? 'mixed';
                    $explanation = $q['explanation'] ?? 'Đáp án đúng là: ' . $correct_answer;
                    
                    $stmt_question->execute([
                        $exam_id,
                        $q['question'],
                        json_encode($options),
                        $correct_answer,
                        $difficulty,
                        $explanation
                    ]);
                }
            } else {
                // Cấu trúc đơn giản nhất
                $stmt_question = $pdo->prepare("
                    INSERT INTO questions (exam_id, question_text, correct_answer, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                
                foreach ($questions as $index => $q) {
                    $options = $q['options'] ?? ['A', 'B', 'C', 'D'];
                    $correct_index = isset($q['correct']) ? intval($q['correct']) : 0;
                    if ($correct_index >= count($options)) {
                        $correct_index = 0;
                    }
                    $correct_answer = $options[$correct_index] ?? $options[0] ?? '';
                    
                    $stmt_question->execute([
                        $exam_id,
                        $q['question'],
                        $correct_answer
                    ]);
                }
            }
            
            $pdo->commit();
            return $exam_id;
            
        } catch (Exception $e) {
            if ($pdo) $pdo->rollBack();
            error_log("saveAIExam error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Kiểm tra AI có sẵn sàng không
 */
if (!function_exists('isAIAvailable')) {
    function isAIAvailable() {
        return true; // Luôn trả về true vì sử dụng dữ liệu mẫu
    }
}

/**
 * Lấy danh sách cấp độ khó
 */
if (!function_exists('getDifficultyLevels')) {
    function getDifficultyLevels() {
        return [
            'easy' => 'Dễ',
            'medium' => 'Trung bình',
            'hard' => 'Khó',
            'mixed' => 'Hỗn hợp'
        ];
    }
}

/**
 * Tạo đề thi bằng AI với chủ đề nâng cao
 */
if (!function_exists('generateAIExamWithTopic')) {
    function generateAIExamWithTopic($subject_id, $num_questions, $difficulty, $topic, $exam_title) {
        $questions = generateQuestionsWithAI($subject_id, $num_questions, $difficulty, $topic);
        
        if (empty($questions)) {
            return false;
        }
        
        // Lưu vào database
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            return false;
        }
        
        return saveAIExam($exam_title, $subject_id, $user_id, $questions);
    }
}

/**
 * Lấy danh sách phòng thi đang hoạt động
 */
if (!function_exists('getActiveRooms')) {
    function getActiveRooms($teacher_id = null) {
        global $pdo;
        if (!$pdo) return [];
        
        try {
            $sql = "SELECT * FROM exam_rooms WHERE status IN ('active', 'waiting', 'ongoing')";
            $params = [];
            
            if ($teacher_id) {
                // Nếu có teacher_id, lọc theo teacher_id hoặc created_by
                $sql .= " AND (teacher_id = ? OR created_by = ?)";
                $params = [$teacher_id, $teacher_id];
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT 10";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getActiveRooms error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Lấy bảng xếp hạng
 */
if (!function_exists('getLeaderboard')) {
    function getLeaderboard($limit = 5) {
        global $pdo;
        if (!$pdo) return [];
        
        try {
            // Kiểm tra bảng exam_submissions có tồn tại không
            $stmt = $pdo->query("SHOW TABLES LIKE 'exam_submissions'");
            if ($stmt->rowCount() == 0) {
                return [];
            }
            
            // Kiểm tra cột student_id
            $check_col = $pdo->query("SHOW COLUMNS FROM exam_submissions LIKE 'student_id'");
            if ($check_col->rowCount() == 0) {
                return [];
            }
            
            // Kiểm tra bảng users
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() == 0) {
                return [];
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    u.id, 
                    u.name, 
                    u.email,
                    AVG(es.score) as avg_score,
                    COUNT(es.id) as total_exams
                FROM exam_submissions es
                INNER JOIN users u ON es.student_id = u.id
                WHERE es.status = 'completed'
                GROUP BY u.id
                ORDER BY avg_score DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getLeaderboard error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Lấy thống kê
 */
if (!function_exists('getStats')) {
    function getStats($user_id = null) {
        global $pdo;
        if (!$pdo) return [];
        
        $stats = [
            'subjects' => 0,
            'exams' => 0,
            'questions' => 0,
            'results' => 0,
            'users' => 0
        ];
        
        try {
            // Đếm môn học
            if ($user_id) {
                // Lấy số môn học của giáo viên
                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT ts.subject_id) as count
                    FROM teacher_subjects ts
                    WHERE ts.teacher_id = ?
                ");
                $stmt->execute([$user_id]);
                $stats['subjects'] = $stmt->fetchColumn() ?: 0;
            } else {
                $stmt = $pdo->query("SELECT COUNT(*) FROM subjects");
                $stats['subjects'] = $stmt->fetchColumn() ?: 0;
            }
            
            // Đếm đề thi
            if ($user_id) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE user_id = ? OR created_by = ?");
                $stmt->execute([$user_id, $user_id]);
            } else {
                $stmt = $pdo->query("SELECT COUNT(*) FROM exams");
            }
            $stats['exams'] = $stmt->fetchColumn() ?: 0;
            
            // Đếm câu hỏi
            if ($user_id) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM questions q
                    INNER JOIN exams e ON q.exam_id = e.id
                    WHERE e.user_id = ? OR e.created_by = ?
                ");
                $stmt->execute([$user_id, $user_id]);
            } else {
                $stmt = $pdo->query("SELECT COUNT(*) FROM questions");
            }
            $stats['questions'] = $stmt->fetchColumn() ?: 0;
            
            // Đếm kết quả
            if ($user_id) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM exam_submissions es
                    INNER JOIN exams e ON es.exam_id = e.id
                    WHERE e.user_id = ? OR e.created_by = ?
                ");
                $stmt->execute([$user_id, $user_id]);
            } else {
                $stmt = $pdo->query("SELECT COUNT(*) FROM exam_submissions");
            }
            $stats['results'] = $stmt->fetchColumn() ?: 0;
            
            // Đếm người dùng
            if (!$user_id) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                $stats['users'] = $stmt->fetchColumn() ?: 0;
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("getStats error: " . $e->getMessage());
            return $stats;
        }
    }
}

/**
 * Lấy danh sách đề thi
 */
if (!function_exists('getExams')) {
    function getExams($user_id = null) {
        global $pdo;
        if (!$pdo) return [];
        
        try {
            $sql = "SELECT e.*, s.name as subject_name 
                    FROM exams e
                    LEFT JOIN subjects s ON e.subject_id = s.id";
            $params = [];
            
            if ($user_id) {
                $sql .= " WHERE e.user_id = ? OR e.created_by = ?";
                $params = [$user_id, $user_id];
            }
            
            $sql .= " ORDER BY e.created_at DESC LIMIT 20";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getExams error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Lấy danh sách người dùng
 */
if (!function_exists('getUsers')) {
    function getUsers($limit = 10) {
        global $pdo;
        if (!$pdo) return [];
        
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT ?");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getUsers error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Lấy chi tiết đề thi theo ID
 */
function getExamById($exam_id) {
    global $pdo;
    if (!$pdo) return null;
    
    try {
        $stmt = $pdo->prepare("
            SELECT e.*, s.name as subject_name,
                   (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count
            FROM exams e
            LEFT JOIN subjects s ON e.subject_id = s.id
            WHERE e.id = ?
        ");
        $stmt->execute([$exam_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getExamById error: " . $e->getMessage());
        return null;
    }
}

/**
 * Lấy danh sách câu hỏi của đề thi
 */
function getExamQuestions($exam_id) {
    global $pdo;
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT q.*, eq.question_order 
            FROM questions q
            INNER JOIN exam_questions eq ON q.id = eq.question_id
            WHERE eq.exam_id = ?
            ORDER BY eq.question_order ASC, q.id ASC
        ");
        $stmt->execute([$exam_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getExamQuestions error: " . $e->getMessage());
        return [];
    }
}


/**
 * Lấy danh sách phòng thi đang hoạt động
 */
function getActiveRooms($teacher_id = null) {
    global $pdo;
    if (!$pdo) return [];
    
    try {
        $sql = "SELECT r.*, e.title as exam_title 
                FROM rooms r
                LEFT JOIN exams e ON r.exam_id = e.id
                WHERE r.status IN ('active', 'waiting')
                AND (r.status = 'active' OR r.status = 'waiting')";
        $params = [];
        
        if ($teacher_id) {
            $sql .= " AND (r.teacher_id = ? OR r.created_by = ?)";
            $params = [$teacher_id, $teacher_id];
        }
        
        $sql .= " ORDER BY r.id DESC LIMIT 5";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getActiveRooms error: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy bảng xếp hạng học sinh
 */
function getLeaderboard($limit = 5, $teacher_id = null) {
    global $pdo;
    if (!$pdo) return [];
    
    try {
        $sql = "
            SELECT 
                u.id,
                u.name,
                u.email,
                AVG(es.score) as avg_score,
                COUNT(es.id) as total_exams,
                MAX(es.score) as best_score
            FROM exam_submissions es
            INNER JOIN users u ON es.student_id = u.id
            INNER JOIN exams e ON es.exam_id = e.id
            WHERE es.status = 'completed'
        ";
        $params = [];
        
        if ($teacher_id) {
            $sql .= " AND (e.teacher_id = ? OR e.created_by = ? OR e.user_id = ?)";
            $params = [$teacher_id, $teacher_id, $teacher_id];
        }
        
        $sql .= " GROUP BY u.id ORDER BY avg_score DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getLeaderboard error: " . $e->getMessage());
        return [];
    }
}


/**
 * Kiểm tra quyền của giáo viên với môn học
 */
function canTeacherAccessSubject($teacher_id, $subject_id) {
    global $pdo;
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id FROM teacher_subjects 
            WHERE teacher_id = ? AND subject_id = ?
        ");
        $stmt->execute([$teacher_id, $subject_id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}
/**
 * Lấy danh sách đề thi của giáo viên
 */
function getExams($teacher_id = null) {
    global $pdo;
    if (!$pdo) return [];
    
    try {
        $sql = "SELECT e.*, s.name as subject_name 
                FROM exams e
                LEFT JOIN subjects s ON e.subject_id = s.id";
        $params = [];
        
        if ($teacher_id) {
            $sql .= " WHERE e.teacher_id = ?";
            $params = [$teacher_id];
        }
        
        $sql .= " ORDER BY e.id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getExams error: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy danh sách phòng thi của giáo viên
 */
function getRooms($teacher_id = null) {
    global $pdo;
    if (!$pdo) return [];
    
    try {
        $sql = "SELECT r.*, e.title as exam_title 
                FROM rooms r
                LEFT JOIN exams e ON r.exam_id = e.id";
        $params = [];
        
        if ($teacher_id) {
            $sql .= " WHERE r.teacher_id = ?";
            $params = [$teacher_id];
        }
        
        $sql .= " ORDER BY r.id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getRooms error: " . $e->getMessage());
        return [];
    }
}

/**
 * Kiểm tra quyền của giáo viên với đề thi
 */
function canTeacherEditExam($teacher_id, $exam_id) {
    global $pdo;
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM exams WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$exam_id, $teacher_id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Lấy thống kê cho teacher
 */
function getTeacherStats($teacher_id) {
    global $pdo;
    if (!$pdo) return [];
    
    try {
        $stats = [];
        
        // Số môn học được phân công
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM teacher_subjects WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $stats['subjects'] = $stmt->fetchColumn() ?: 0;
        
        // Số đề thi đã tạo
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $stats['exams'] = $stmt->fetchColumn() ?: 0;
        
        // Số câu hỏi trong ngân hàng
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE created_by = ?");
        $stmt->execute([$teacher_id]);
        $stats['questions'] = $stmt->fetchColumn() ?: 0;
        
        // Số lượt nộp bài
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM exam_submissions es
            INNER JOIN exams e ON es.exam_id = e.id
            WHERE e.teacher_id = ?
        ");
        $stmt->execute([$teacher_id]);
        $stats['results'] = $stmt->fetchColumn() ?: 0;
        
        return $stats;
    } catch (Exception $e) {
        error_log("getTeacherStats error: " . $e->getMessage());
        return ['subjects' => 0, 'exams' => 0, 'questions' => 0, 'results' => 0];
    }
}
if (!function_exists('auto_init_tables')) {
    function auto_init_tables()
    {
        global $pdo;
        if (!$pdo) return;

        // ==========================================
        // 1. TẠO CÁC BẢNG NẾU CHƯA TỒN TẠI
        // ==========================================
        $queries = [
            "CREATE TABLE IF NOT EXISTS `subjects` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `code` VARCHAR(50) NULL,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT NULL,
                `status` ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `exams` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `subject_id` INT NOT NULL,
                `duration` INT NOT NULL DEFAULT 45,
                `pass_score` FLOAT NOT NULL DEFAULT 5.0,
                `description` TEXT NULL,
                `created_by` INT NULL,
                `teacher_id` INT NULL,
                `exam_code` VARCHAR(50) NULL,
                `total_questions` INT DEFAULT 0,
                `is_public` TINYINT(1) DEFAULT 1,
                `status` ENUM('active', 'inactive', 'pending', 'approved', 'rejected', 'draft') DEFAULT 'draft',
                `views_count` INT DEFAULT 0,
                `downloads_count` INT DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `questions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `exam_id` INT NULL,
                `subject_id` INT NULL,
                `created_by` INT NULL,
                `question_type` VARCHAR(50) NOT NULL DEFAULT 'single',
                `content` TEXT NOT NULL,
                `option_a` TEXT NULL,
                `option_b` TEXT NULL,
                `option_c` TEXT NULL,
                `option_d` TEXT NULL,
                `options_json` LONGTEXT NULL,
                `correct_answer` VARCHAR(255) NULL,
                `difficulty` VARCHAR(20) DEFAULT 'medium',
                `explanation` TEXT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (`exam_id`),
                INDEX (`subject_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `exam_questions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `exam_id` INT NOT NULL,
                `question_id` INT NOT NULL,
                `question_order` INT DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_exam_question` (`exam_id`, `question_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `exam_submissions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `exam_id` INT NOT NULL,
                `student_id` INT NOT NULL,
                `room_id` INT NULL,
                `score` DECIMAL(4,2) DEFAULT 0.00,
                `duration_seconds` INT DEFAULT 0,
                `cheat_count` INT DEFAULT 0,
                `status` ENUM('in_progress', 'completed') DEFAULT 'completed',
                `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `exam_rooms` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `room_name` VARCHAR(255) NOT NULL,
                `room_code` VARCHAR(50) NOT NULL UNIQUE,
                `exam_id` INT DEFAULT NULL,
                `teacher_id` INT NULL DEFAULT NULL,
                `created_by` INT NULL,
                `max_participants` INT DEFAULT 10,
                `status` ENUM('waiting', 'active', 'closed') DEFAULT 'waiting',
                `start_time` DATETIME NULL DEFAULT NULL,
                `end_time` DATETIME NULL DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `teacher_subjects` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `subject_id` INT NOT NULL,
                `teacher_id` INT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_teacher_subject` (`teacher_id`, `subject_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `role` ENUM('admin', 'teacher', 'student') DEFAULT 'student',
                `avatar` VARCHAR(255) NULL,
                `phone` VARCHAR(20) NULL,
                `status` ENUM('active', 'inactive') DEFAULT 'active',
                `last_login` DATETIME NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];

        foreach ($queries as $sql) {
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                // Bỏ qua lỗi nếu bảng đã tồn tại
            }
        }

        // ==========================================
        // 2. TỰ ĐỘNG SỬA CẤU TRÚC BẢNG - KHẮC PHỤC LỖI "Data too long"
        // ==========================================
        
        // 2.1. Sửa cấu trúc bảng questions - Đổi TEXT cho các cột option
        $table = 'questions';
        if (tableExists($pdo, $table)) {
            $columns_to_fix = [
                'option_a' => "ALTER TABLE `$table` MODIFY COLUMN `option_a` TEXT NULL",
                'option_b' => "ALTER TABLE `$table` MODIFY COLUMN `option_b` TEXT NULL",
                'option_c' => "ALTER TABLE `$table` MODIFY COLUMN `option_c` TEXT NULL",
                'option_d' => "ALTER TABLE `$table` MODIFY COLUMN `option_d` TEXT NULL",
                'content' => "ALTER TABLE `$table` MODIFY COLUMN `content` TEXT NOT NULL",
                'correct_answer' => "ALTER TABLE `$table` MODIFY COLUMN `correct_answer` VARCHAR(255) NULL",
                'explanation' => "ALTER TABLE `$table` MODIFY COLUMN `explanation` TEXT NULL",
                'options_json' => "ALTER TABLE `$table` MODIFY COLUMN `options_json` LONGTEXT NULL"
            ];
            
            foreach ($columns_to_fix as $column => $alter_sql) {
                if (columnExists($pdo, $table, $column)) {
                    try {
                        $pdo->exec($alter_sql);
                    } catch (PDOException $e) {
                        // Nếu lỗi, thử cách khác
                        try {
                            // Thử kiểm tra và thêm mới nếu cột chưa đúng kiểu
                            $pdo->exec($alter_sql);
                        } catch (PDOException $ex) {
                            // Bỏ qua nếu không thể sửa
                        }
                    }
                } else {
                    // Thêm cột nếu chưa có
                    $col_type = [
                        'option_a' => 'TEXT NULL',
                        'option_b' => 'TEXT NULL',
                        'option_c' => 'TEXT NULL',
                        'option_d' => 'TEXT NULL',
                        'content' => 'TEXT NOT NULL',
                        'correct_answer' => 'VARCHAR(255) NULL',
                        'explanation' => 'TEXT NULL',
                        'options_json' => 'LONGTEXT NULL'
                    ];
                    try {
                        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` {$col_type[$column]}");
                    } catch (PDOException $e) {
                        // Bỏ qua nếu không thể thêm
                    }
                }
            }
        }

        // 2.2. Sửa bảng exams - Đảm bảo có cột teacher_id và exam_code
        $table = 'exams';
        if (tableExists($pdo, $table)) {
            $exam_columns = [
                'teacher_id' => "ALTER TABLE `$table` ADD COLUMN `teacher_id` INT NULL AFTER `subject_id`",
                'exam_code' => "ALTER TABLE `$table` ADD COLUMN `exam_code` VARCHAR(50) NULL AFTER `teacher_id`",
                'total_questions' => "ALTER TABLE `$table` ADD COLUMN `total_questions` INT DEFAULT 0",
                'description' => "ALTER TABLE `$table` MODIFY COLUMN `description` TEXT NULL"
            ];
            
            foreach ($exam_columns as $column => $alter_sql) {
                if (!columnExists($pdo, $table, $column)) {
                    try {
                        $pdo->exec($alter_sql);
                    } catch (PDOException $e) {
                        // Bỏ qua nếu không thể thêm
                    }
                }
            }
        }

        // 2.3. Sửa bảng exam_rooms - Đảm bảo có đầy đủ cột
        $table = 'exam_rooms';
        if (tableExists($pdo, $table)) {
            $room_columns = [
                'teacher_id' => "ALTER TABLE `$table` ADD COLUMN `teacher_id` INT NULL AFTER `exam_id`",
                'created_by' => "ALTER TABLE `$table` ADD COLUMN `created_by` INT NULL AFTER `max_participants`",
                'start_time' => "ALTER TABLE `$table` ADD COLUMN `start_time` DATETIME NULL AFTER `status`",
                'end_time' => "ALTER TABLE `$table` ADD COLUMN `end_time` DATETIME NULL AFTER `start_time`"
            ];
            
            foreach ($room_columns as $column => $alter_sql) {
                if (!columnExists($pdo, $table, $column)) {
                    try {
                        $pdo->exec($alter_sql);
                    } catch (PDOException $e) {
                        // Bỏ qua nếu không thể thêm
                    }
                }
            }
        }

        // 2.4. Sửa bảng teacher_subjects - Đảm bảo có unique key
        $table = 'teacher_subjects';
        if (tableExists($pdo, $table)) {
            // Kiểm tra và thêm unique key nếu chưa có
            try {
                $stmt = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = 'unique_teacher_subject'");
                if ($stmt->rowCount() == 0) {
                    try {
                        $pdo->exec("ALTER TABLE `$table` ADD UNIQUE KEY `unique_teacher_subject` (`teacher_id`, `subject_id`)");
                    } catch (PDOException $e) {
                        // Bỏ qua nếu key đã tồn tại
                    }
                }
            } catch (PDOException $e) {
                // Bỏ qua
            }
        }

        // 2.5. Sửa bảng users - Đảm bảo có đầy đủ cột
        $table = 'users';
        if (tableExists($pdo, $table)) {
            $user_columns = [
                'avatar' => "ALTER TABLE `$table` ADD COLUMN `avatar` VARCHAR(255) NULL",
                'phone' => "ALTER TABLE `$table` ADD COLUMN `phone` VARCHAR(20) NULL",
                'last_login' => "ALTER TABLE `$table` ADD COLUMN `last_login` DATETIME NULL",
                'password' => "ALTER TABLE `$table` MODIFY COLUMN `password` VARCHAR(255) NOT NULL"
            ];
            
            foreach ($user_columns as $column => $alter_sql) {
                if (!columnExists($pdo, $table, $column)) {
                    try {
                        $pdo->exec($alter_sql);
                    } catch (PDOException $e) {
                        // Bỏ qua nếu không thể thêm
                    }
                }
            }
        }

        // ==========================================
        // 3. XỬ LÝ ĐẶC BIỆT: SỬA KIỂU DỮ LIỆU CHO CÁC CỘT QUAN TRỌNG
        // ==========================================
        
        // 3.1. Đảm bảo cột status trong exams có đầy đủ giá trị ENUM
        if (tableExists($pdo, 'exams')) {
            try {
                $pdo->exec("ALTER TABLE `exams` MODIFY COLUMN `status` VARCHAR(20) DEFAULT 'draft'");
            } catch (PDOException $e) {
                // Bỏ qua nếu không thể sửa
            }
        }

        // 3.2. Đảm bảo cột status trong exam_rooms có đầy đủ giá trị ENUM
        if (tableExists($pdo, 'exam_rooms')) {
            try {
                $pdo->exec("ALTER TABLE `exam_rooms` MODIFY COLUMN `status` VARCHAR(20) DEFAULT 'waiting'");
            } catch (PDOException $e) {
                // Bỏ qua nếu không thể sửa
            }
        }

        // ==========================================
        // 4. TẠO BẢNG settings NẾU CHƯA CÓ
        // ==========================================
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `settings` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                    `setting_value` TEXT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            
            // Thêm setting mặc định nếu chưa có
            $check = $pdo->query("SELECT COUNT(*) FROM `settings` WHERE setting_key = 'maintenance_mode'");
            if ($check->fetchColumn() == 0) {
                $pdo->exec("INSERT INTO `settings` (setting_key, setting_value) VALUES ('maintenance_mode', '0')");
            }
        } catch (PDOException $e) {
            // Bỏ qua
        }

        // ==========================================
        // 5. TẠO BẢNG rooms (NẾU CHƯA CÓ - TƯƠNG THÍCH NGƯỢC)
        // ==========================================
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `rooms` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `code` VARCHAR(50) NOT NULL UNIQUE,
                    `name` VARCHAR(255) NOT NULL,
                    `exam_id` INT NULL,
                    `teacher_id` INT NULL,
                    `max_players` INT DEFAULT 30,
                    `image` VARCHAR(255) NULL,
                    `status` VARCHAR(20) DEFAULT 'waiting',
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } catch (PDOException $e) {
            // Bỏ qua
        }

        // ==========================================
        // 6. ĐẢM BẢO BẢNG exam_questions CÓ CỘT question_order
        // ==========================================
        if (tableExists($pdo, 'exam_questions')) {
            if (!columnExists($pdo, 'exam_questions', 'question_order')) {
                try {
                    $pdo->exec("ALTER TABLE `exam_questions` ADD COLUMN `question_order` INT DEFAULT 0 AFTER `question_id`");
                } catch (PDOException $e) {
                    // Bỏ qua
                }
            }
        }

        // ==========================================
        // 7. ĐẢM BẢO BẢNG exam_submissions CÓ CỘT room_id
        // ==========================================
        if (tableExists($pdo, 'exam_submissions')) {
            if (!columnExists($pdo, 'exam_submissions', 'room_id')) {
                try {
                    $pdo->exec("ALTER TABLE `exam_submissions` ADD COLUMN `room_id` INT NULL AFTER `student_id`");
                } catch (PDOException $e) {
                    // Bỏ qua
                }
            }
        }
    }
}
if (!function_exists('tableExists')) {
    function tableExists($pdo, $table) {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('columnExists')) {
    function columnExists($pdo, $table, $column) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}

if (!function_exists('indexExists')) {
    function indexExists($pdo, $table, $indexName) {
        try {
            $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
            $stmt->execute([$indexName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}   
/**
 * Kiểm tra sự tồn tại của bảng trong CSDL
 */
if (!function_exists('tableExists')) {
    function tableExists(PDO $pdo, string $table): bool
    {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}

/**
 * Kiểm tra sự tồn tại của cột trong bảng
 */
if (!function_exists('columnExists')) {
    function columnExists(PDO $pdo, string $table, string $column): bool
    {
        static $cache = [];
        $key = "$table.$column";
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            $cache[$key] = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $cache[$key] = false;
        }
        return $cache[$key];
    }
}

/**
 * Kiểm tra sự tồn tại của Index
 */
if (!function_exists('indexExists')) {
    function indexExists(PDO $pdo, string $table, string $indexName): bool
    {
        try {
            $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
            $stmt->execute([$indexName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}

/**
 * Hàm khởi tạo và đồng bộ hóa CSDL duy nhất (Thay thế cho các hàm cũ bị trùng lặp)
 */
if (!function_exists('auto_init_tables')) {
    function auto_init_tables()
    {
        global $pdo;
        if (!$pdo) return;

        // 1. Tạo các bảng cơ bản nếu chưa tồn tại
        $queries = [
            "CREATE TABLE IF NOT EXISTS `subjects` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `code` VARCHAR(50) NULL,
                `name` VARCHAR(255) NOT NULL,
                `subject_name` VARCHAR(255) NULL,
                `description` TEXT NULL,
                `status` ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `exams` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `exam_code` VARCHAR(50) NULL,
                `title` VARCHAR(255) NOT NULL,
                `subject_id` INT NULL,
                `user_id` INT NULL,
                `created_by` INT NULL,
                `duration` INT NOT NULL DEFAULT 45,
                `pass_score` FLOAT NOT NULL DEFAULT 5.0,
                `description` TEXT NULL,
                `is_public` TINYINT(1) DEFAULT 1,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `views_count` INT DEFAULT 0,
                `downloads_count` INT DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `questions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `exam_id` INT NULL,
                `subject_id` INT NULL,
                `user_id` INT NULL,
                `question_type` VARCHAR(50) NOT NULL DEFAULT 'single_choice',
                `type` VARCHAR(50) NOT NULL DEFAULT 'single_choice',
                `question_text` TEXT NULL,
                `content` TEXT NULL,
                `options` LONGTEXT NULL,
                `option_a` TEXT NULL,
                `option_b` TEXT NULL,
                `option_c` TEXT NULL,
                `option_d` TEXT NULL,
                `correct_answer` LONGTEXT NULL,
                `score` FLOAT DEFAULT 1.0,
                `difficulty` VARCHAR(20) DEFAULT 'medium',
                `explanation` TEXT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (`exam_id`),
                INDEX (`subject_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `exam_submissions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `exam_id` INT NOT NULL,
                `student_id` INT NOT NULL,
                `score` DECIMAL(4,2) DEFAULT 0.00,
                `duration_seconds` INT DEFAULT 0,
                `cheat_count` INT DEFAULT 0,
                `status` ENUM('in_progress', 'completed') DEFAULT 'completed',
                `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `student_wrong_questions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `student_id` INT NOT NULL,
                `question_id` INT NOT NULL,
                `selected_option` VARCHAR(255) DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_student_question` (`student_id`, `question_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `exam_rooms` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `room_name` VARCHAR(255) NULL,
                `room_code` VARCHAR(50) NOT NULL UNIQUE,
                `exam_id` INT DEFAULT NULL,
                `teacher_id` INT NULL DEFAULT NULL,
                `max_participants` INT DEFAULT 10,
                `created_by` INT NULL,
                `status` VARCHAR(50) DEFAULT 'waiting',
                `start_time` DATETIME NULL DEFAULT NULL,
                `end_time` DATETIME NULL DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `teacher_subjects` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `subject_id` INT NOT NULL,
                `teacher_id` INT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_teacher_subject` (`teacher_id`, `subject_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];

        foreach ($queries as $sql) {
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                // Bỏ qua nếu đã tồn tại
            }
        }

        // 2. Tự động Migration bổ sung các cột còn thiếu cho bảng `questions`
        $cols_questions = [
            'type'          => "ALTER TABLE questions ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'single_choice' AFTER exam_id",
            'question_type' => "ALTER TABLE questions ADD COLUMN `question_type` VARCHAR(50) NOT NULL DEFAULT 'single_choice' AFTER type",
            'content'       => "ALTER TABLE questions ADD COLUMN `content` TEXT NULL AFTER question_type",
            'question_text' => "ALTER TABLE questions ADD COLUMN `question_text` TEXT NULL AFTER content",
            'options'       => "ALTER TABLE questions ADD COLUMN `options` LONGTEXT NULL AFTER question_text",
            'option_a'      => "ALTER TABLE questions ADD COLUMN `option_a` TEXT NULL AFTER options",
            'option_b'      => "ALTER TABLE questions ADD COLUMN `option_b` TEXT NULL AFTER option_a",
            'option_c'      => "ALTER TABLE questions ADD COLUMN `option_c` TEXT NULL AFTER option_b",
            'option_d'      => "ALTER TABLE questions ADD COLUMN `option_d` TEXT NULL AFTER option_c",
            'correct_answer'=> "ALTER TABLE questions ADD COLUMN `correct_answer` LONGTEXT NULL AFTER option_d",
            'difficulty'    => "ALTER TABLE questions ADD COLUMN `difficulty` VARCHAR(20) DEFAULT 'medium' AFTER correct_answer",
            'explanation'   => "ALTER TABLE questions ADD COLUMN `explanation` TEXT NULL AFTER difficulty"
        ];

        foreach ($cols_questions as $col => $sql) {
            if (!columnExists($pdo, 'questions', $col)) {
                try { $pdo->exec($sql); } catch (PDOException $e) {}
            }
        }

        // 3. Đồng bộ hóa dữ liệu song song giữa các cột bí danh (Alias)
        try {
            $pdo->exec("UPDATE questions SET type = question_type WHERE (type IS NULL OR type = '') AND question_type IS NOT NULL");
            $pdo->exec("UPDATE questions SET question_type = type WHERE (question_type IS NULL OR question_type = '') AND type IS NOT NULL");
            $pdo->exec("UPDATE questions SET content = question_text WHERE (content IS NULL OR content = '') AND question_text IS NOT NULL");
            $pdo->exec("UPDATE questions SET question_text = content WHERE (question_text IS NULL OR question_text = '') AND content IS NOT NULL");
        } catch (PDOException $e) {}
    }
}

// Chạy tự động khởi tạo khi load file
auto_init_tables();

/**
 * Lấy danh sách môn học
 */
if (!function_exists('getSubjects')) {
    function getSubjects(string $q = ''): array
    {
        $where  = '';
        $params = [];

        if ($q !== '') {
            $where  = "WHERE s.name LIKE ? OR s.code LIKE ?";
            $params = ['%' . $q . '%', '%' . $q . '%'];
        }

        return db_fetch_all("
            SELECT s.*, COUNT(DISTINCT e.id) AS total_exams
            FROM subjects s
            LEFT JOIN exams e ON s.id = e.subject_id AND e.status = 'published'
            $where
            GROUP BY s.id
            ORDER BY s.name ASC
        ", $params) ?: [];
    }
}

/**
 * Lấy giá trị cài đặt từ bảng settings
 */
if (!function_exists('getSetting')) {
    function getSetting($key, $default = null) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? $val : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

/**
 * Kiểm tra chế độ bảo trì
 */
if (!function_exists('checkMaintenanceMode')) {
    function checkMaintenanceMode() {
        if (function_exists('isAdmin') && isAdmin()) {
            return;
        }

        $isMaintenance = (int)getSetting('maintenance_mode', 0);

        if ($isMaintenance === 1) {
            http_response_code(503);
            echo '
            <!DOCTYPE html>
            <html lang="vi">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Hệ thống đang bảo trì</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700&display=swap" rel="stylesheet">
                <style>
                    body {
                        background: #f8fafc;
                        font-family: "Plus Jakarta Sans", sans-serif;
                        height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .card-maintenance {
                        background: #ffffff;
                        border-radius: 24px;
                        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
                        max-width: 480px;
                        width: 90%;
                        padding: 2.5rem;
                        text-align: center;
                    }
                </style>
            </head>
            <body>
                <div class="card-maintenance">
                    <div class="display-1 text-warning mb-3">🛠️</div>
                    <h3 class="fw-bold text-dark mb-2">Hệ Thống Đang Bảo Trì</h3>
                    <p class="text-muted mb-4">Hệ thống thi trắc nghiệm đang trong quá trình nâng cấp và bảo trì định kỳ. Sinh viên vui lòng quay lại sau!</p>
                    <a href="javascript:location.reload()" class="btn btn-outline-primary rounded-pill px-4">Thử tải lại trang</a>
                </div>
            </body>
            </html>';
            exit;
        }
    }
}

/**
 * Lấy danh sách môn học của giáo viên
 */
if (!function_exists('getTeacherSubjects')) {
    function getTeacherSubjects($teacher_id) {
        global $pdo;
        if (!$pdo) return [];
        
        try {
            $stmt = $pdo->prepare("
                SELECT s.* 
                FROM subjects s
                INNER JOIN teacher_subjects ts ON s.id = ts.subject_id
                WHERE ts.teacher_id = ?
                ORDER BY s.name
            ");
            $stmt->execute([$teacher_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("getTeacherSubjects error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Lấy tên môn học theo ID
 */
if (!function_exists('getSubjectName')) {
    function getSubjectName($subject_id) {
        global $pdo;
        if (!$pdo) return 'Môn học';
        
        try {
            $stmt = $pdo->prepare("SELECT name FROM subjects WHERE id = ?");
            $stmt->execute([$subject_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['name'] : 'Môn học';
        } catch (Exception $e) {
            return 'Môn học';
        }
    }
}

/**
 * Tạo câu hỏi bằng AI / Mock Data
 */
if (!function_exists('generateQuestionsWithAI')) {
    function generateQuestionsWithAI($subject_id, $num_questions, $difficulty, $topic = '') {
        $subject_name = getSubjectName($subject_id);
        if (empty($topic)) {
            $topic = $subject_name;
        }
        return generateQuestionsWithMockData($subject_name, $num_questions, $difficulty, $topic);
    }
}

if (!function_exists('generateQuestionsWithMockData')) {
    function generateQuestionsWithMockData($subject_name, $num_questions, $difficulty, $topic) {
        $questions = [];
        $subject_questions = getSubjectQuestionBank($subject_name);
        
        if (empty($subject_questions)) {
            $subject_questions = getDefaultITQuestionBank();
        }
        
        $total_available = count($subject_questions);
        
        for ($i = 0; $i < $num_questions; $i++) {
            $index = $i % $total_available;
            $sample = $subject_questions[$index];
            
            $question_text = "Câu " . ($i + 1) . ": " . $sample['question'];
            $options = $sample['options'];
            $correct_index = $sample['correct'] ?? rand(0, 3);
            
            $question_difficulty = $difficulty;
            if ($difficulty === 'mixed') {
                $levels = ['easy', 'medium', 'hard'];
                $question_difficulty = $levels[$i % 3];
            }
            
            $questions[] = [
                'question' => $question_text,
                'options' => $options,
                'correct' => $correct_index,
                'difficulty' => $question_difficulty,
                'explanation' => 'Đáp án đúng là ' . $options[$correct_index] . '.'
            ];
        }
        
        return $questions;
    }
}

/**
 * Ngân hàng câu hỏi mặc định
 */
if (!function_exists('getDefaultITQuestionBank')) {
    function getDefaultITQuestionBank() {
        return [
            ['question' => 'Đâu là một ngôn ngữ lập trình phổ biến?', 'options' => ['Python', 'HTML', 'CSS', 'JSON'], 'correct' => 0],
            ['question' => 'RAM viết tắt của từ gì?', 'options' => ['Random Access Memory', 'Read Access Memory', 'Run Access Module', 'Real Access Memory'], 'correct' => 0],
            ['question' => 'Giao thức nào được bảo mật bằng mã hóa SSL/TLS?', 'options' => ['HTTPS', 'HTTP', 'FTP', 'SMTP'], 'correct' => 0]
        ];
    }
}

/**
 * Lấy ngân hàng câu hỏi mẫu theo môn học
 */
if (!function_exists('getSubjectQuestionBank')) {
    function getSubjectQuestionBank($subject_name) {
        $banks = [
            'Lập trình Web' => [
                ['question' => 'Ngôn ngữ lập trình nào được sử dụng để phát triển web phía server?', 'options' => ['PHP', 'JavaScript', 'HTML', 'CSS'], 'correct' => 0],
                ['question' => 'HTML là viết tắt của?', 'options' => ['HyperText Markup Language', 'HighTech Machine Language', 'HyperTransfer Markup Language', 'HomeText Markup Language'], 'correct' => 0],
                ['question' => 'CSS dùng để làm gì?', 'options' => ['Trang trí giao diện', 'Xử lý dữ liệu', 'Tạo cơ sở dữ liệu', 'Lập trình backend'], 'correct' => 0],
                ['question' => 'Thẻ HTML nào dùng để tạo liên kết?', 'options' => ['<a>', '<link>', '<href>', '<url>'], 'correct' => 0],
                ['question' => 'JavaScript chạy ở đâu?', 'options' => ['Trình duyệt', 'Máy chủ', 'Cả A và B', 'Không nơi nào'], 'correct' => 2],
                ['question' => 'Framework PHP phổ biến nhất hiện nay là?', 'options' => ['Laravel', 'Symfony', 'CodeIgniter', 'Yii'], 'correct' => 0],
                ['question' => 'Cú pháp để in ra màn hình trong PHP là:', 'options' => ['echo', 'print', 'printf', 'Tất cả đều đúng'], 'correct' => 3],
                ['question' => 'Biến trong PHP bắt đầu bằng ký tự nào?', 'options' => ['$', '#', '@', '&'], 'correct' => 0],
                ['question' => 'Phương thức HTTP nào được sử dụng để gửi dữ liệu biểu mẫu?', 'options' => ['POST', 'GET', 'PUT', 'DELETE'], 'correct' => 0],
                ['question' => 'JSON là viết tắt của?', 'options' => ['JavaScript Object Notation', 'Java Standard Object Notation', 'JavaScript Online Notation', 'Java Source Object Notation'], 'correct' => 0],
            ],
            
            'Cơ sở dữ liệu' => [
                ['question' => 'SQL là viết tắt của?', 'options' => ['Structured Query Language', 'Standard Query Language', 'Simple Query Language', 'System Query Language'], 'correct' => 0],
                ['question' => 'Lệnh SQL nào dùng để lấy dữ liệu từ bảng?', 'options' => ['SELECT', 'INSERT', 'UPDATE', 'DELETE'], 'correct' => 0],
                ['question' => 'Khóa chính trong CSDL là gì?', 'options' => ['Dùng để xác định duy nhất mỗi bản ghi', 'Khóa ngoại', 'Khóa dùng để tìm kiếm', 'Không phải khóa'], 'correct' => 0],
                ['question' => 'MySQL là loại CSDL gì?', 'options' => ['Quan hệ', 'Phi quan hệ', 'Đối tượng', 'XML'], 'correct' => 0],
                ['question' => 'Câu lệnh nào dùng để tạo bảng trong SQL?', 'options' => ['CREATE TABLE', 'CREATE DATABASE', 'CREATE INDEX', 'ALTER TABLE'], 'correct' => 0],
                ['question' => 'INDEX trong CSDL dùng để làm gì?', 'options' => ['Tăng tốc truy vấn', 'Tạo khóa chính', 'Tạo quan hệ', 'Xóa dữ liệu'], 'correct' => 0],
                ['question' => 'Hàm COUNT() trong SQL dùng để?', 'options' => ['Đếm số hàng', 'Đếm số cột', 'Tính tổng', 'Tính trung bình'], 'correct' => 0],
                ['question' => 'Câu lệnh nào dùng để cập nhật dữ liệu?', 'options' => ['UPDATE', 'INSERT', 'DELETE', 'ALTER'], 'correct' => 0],
            ],
            
            'Lập trình hướng đối tượng' => [
                ['question' => 'OOP là viết tắt của?', 'options' => ['Object-Oriented Programming', 'Online Object Programming', 'Object-Origin Programming', 'Official Object Programming'], 'correct' => 0],
                ['question' => '4 tính chất cơ bản của OOP là gì?', 'options' => ['Đóng gói, Kế thừa, Đa hình, Trừu tượng', 'Đóng gói, Kế thừa, Đa nhiệm, Trừu tượng', 'Đóng gói, Kế thừa, Đa hình, Tái sử dụng', 'Kế thừa, Đa hình, Trừu tượng, Tái sử dụng'], 'correct' => 0],
                ['question' => 'Lớp trong OOP là gì?', 'options' => ['Khuôn mẫu để tạo đối tượng', 'Một đối tượng cụ thể', 'Một biến', 'Một hàm'], 'correct' => 0],
                ['question' => 'Đối tượng trong OOP là gì?', 'options' => ['Thể hiện của lớp', 'Một lớp cụ thể', 'Một phương thức', 'Một thuộc tính'], 'correct' => 0],
                ['question' => 'Tính kế thừa (Inheritance) trong OOP là gì?', 'options' => ['Lớp con kế thừa từ lớp cha', 'Lớp cha kế thừa từ lớp con', 'Hai lớp không liên quan', 'Một lớp chỉ có một phương thức'], 'correct' => 0],
                ['question' => 'Tính đa hình (Polymorphism) trong OOP là gì?', 'options' => ['Cùng một tên phương thức nhưng hành vi khác nhau', 'Một lớp có nhiều phương thức', 'Một đối tượng có nhiều lớp', 'Không liên quan đến OOP'], 'correct' => 0],
                ['question' => 'Tính đóng gói (Encapsulation) trong OOP có ý nghĩa gì?', 'options' => ['Che giấu dữ liệu bên trong', 'Mở tất cả dữ liệu', 'Chia sẻ dữ liệu công khai', 'Không liên quan'], 'correct' => 0],
            ],
            
            'Cấu trúc dữ liệu' => [
                ['question' => 'Mảng là gì?', 'options' => ['Tập hợp các phần tử có cùng kiểu dữ liệu', 'Tập hợp các phần tử khác kiểu', 'Một biến đơn', 'Một hàm'], 'correct' => 0],
                ['question' => 'Cấu trúc dữ liệu Stack hoạt động theo nguyên tắc nào?', 'options' => ['LIFO (Last In First Out)', 'FIFO (First In First Out)', 'LILO (Last In Last Out)', 'FILO (First In First Out)'], 'correct' => 0],
                ['question' => 'Cấu trúc dữ liệu Queue hoạt động theo nguyên tắc nào?', 'options' => ['FIFO (First In First Out)', 'LIFO (Last In First Out)', 'LILO (Last In Last Out)', 'FILO (First In Last Out)'], 'correct' => 0],
                ['question' => 'Linked List là gì?', 'options' => ['Cấu trúc dữ liệu động', 'Cấu trúc dữ liệu tĩnh', 'Một mảng', 'Một biến'], 'correct' => 0],
            ]
        ];

        return $banks[$subject_name] ?? [];
    }
}
if (!function_exists('auto_init_tables')) {
    function auto_init_tables()
    {
        global $pdo;
        if (!$pdo) return;

        // 1. Tạo các bảng nếu chưa tồn tại
        $queries = [
            "CREATE TABLE IF NOT EXISTS `subjects` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `code` VARCHAR(50) NULL,
                `name` VARCHAR(255) NOT NULL,
                `subject_name` VARCHAR(255) NULL,
                `description` TEXT NULL,
                `status` ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `exams` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `exam_code` VARCHAR(50) NULL,
                `title` VARCHAR(255) NOT NULL,
                `subject_id` INT NULL,
                `user_id` INT NULL,
                `created_by` INT NULL,
                `duration` INT NOT NULL DEFAULT 45,
                `pass_score` FLOAT NOT NULL DEFAULT 5.0,
                `description` TEXT NULL,
                `is_public` TINYINT(1) DEFAULT 1,
                `status` VARCHAR(20) NOT NULL DEFAULT 'active',
                `views_count` INT DEFAULT 0,
                `downloads_count` INT DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `questions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `exam_id` INT NULL,
                `subject_id` INT NULL,
                `user_id` INT NULL,
                `question_type` VARCHAR(50) NOT NULL DEFAULT 'single_choice',
                `type` VARCHAR(50) NOT NULL DEFAULT 'single_choice',
                `question_text` TEXT NULL,
                `content` TEXT NULL,
                `options` LONGTEXT NULL,
                `option_a` TEXT NULL,
                `option_b` TEXT NULL,
                `option_c` TEXT NULL,
                `option_d` TEXT NULL,
                `correct_answer` LONGTEXT NULL,
                `score` FLOAT DEFAULT 1.0,
                `difficulty` VARCHAR(20) DEFAULT 'medium',
                `explanation` TEXT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (`exam_id`),
                INDEX (`subject_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];

        foreach ($queries as $sql) {
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {}
        }

        // 2. Migration tự động bổ sung cột còn thiếu vào `questions` (Không dùng AFTER)
        $cols_questions = [
            'type'          => "ALTER TABLE `questions` ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'single_choice'",
            'question_type' => "ALTER TABLE `questions` ADD COLUMN `question_type` VARCHAR(50) NOT NULL DEFAULT 'single_choice'",
            'content'       => "ALTER TABLE `questions` ADD COLUMN `content` TEXT NULL",
            'question_text' => "ALTER TABLE `questions` ADD COLUMN `question_text` TEXT NULL",
            'options'       => "ALTER TABLE `questions` ADD COLUMN `options` LONGTEXT NULL",
            'option_a'      => "ALTER TABLE `questions` ADD COLUMN `option_a` TEXT NULL",
            'option_b'      => "ALTER TABLE `questions` ADD COLUMN `option_b` TEXT NULL",
            'option_c'      => "ALTER TABLE `questions` ADD COLUMN `option_c` TEXT NULL",
            'option_d'      => "ALTER TABLE `questions` ADD COLUMN `option_d` TEXT NULL",
            'correct_answer'=> "ALTER TABLE `questions` ADD COLUMN `correct_answer` LONGTEXT NULL",
            'score'         => "ALTER TABLE `questions` ADD COLUMN `score` FLOAT DEFAULT 1.0",
            'difficulty'    => "ALTER TABLE `questions` ADD COLUMN `difficulty` VARCHAR(20) DEFAULT 'medium'",
            'explanation'   => "ALTER TABLE `questions` ADD COLUMN `explanation` TEXT NULL"
        ];

        foreach ($cols_questions as $col => $sql) {
            if (!columnExists($pdo, 'questions', $col)) {
                try {
                    $pdo->exec($sql);
                } catch (PDOException $e) {
                    error_log("Lỗi Migration thêm cột {$col}: " . $e->getMessage());
                }
            }
        }

        // 3. Đồng bộ hóa dữ liệu giữa các biến Alias
        try {
            $pdo->exec("UPDATE questions SET type = question_type WHERE (type IS NULL OR type = '') AND question_type IS NOT NULL");
            $pdo->exec("UPDATE questions SET question_type = type WHERE (question_type IS NULL OR question_type = '') AND type IS NOT NULL");
            $pdo->exec("UPDATE questions SET content = question_text WHERE (content IS NULL OR content = '') AND question_text IS NOT NULL");
            $pdo->exec("UPDATE questions SET question_text = content WHERE (question_text IS NULL OR question_text = '') AND content IS NOT NULL");
        } catch (PDOException $e) {}
    }
}

if (!function_exists('columnExists')) {
    function columnExists(PDO $pdo, string $table, string $column): bool {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}

/**
 * Khởi tạo và cập nhật CSDL tự động (Tương thích cả autoInitTables và auto_init_tables)
 */
if (!function_exists('autoInitTables')) {
    function autoInitTables($pdo = null) {
        if (!$pdo) {
            global $pdo;
        }
        if (!$pdo) return;

        // 1. Tạo bảng questions nếu chưa có
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `questions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `exam_id` INT NULL,
                `subject_id` INT NULL,
                `user_id` INT NULL,
                `type` VARCHAR(50) NOT NULL DEFAULT 'single_choice',
                `question_type` VARCHAR(50) NOT NULL DEFAULT 'single_choice',
                `question_text` TEXT NULL,
                `content` TEXT NULL,
                `options` LONGTEXT NULL,
                `option_a` TEXT NULL,
                `option_b` TEXT NULL,
                `option_c` TEXT NULL,
                `option_d` TEXT NULL,
                `correct_answer` LONGTEXT NULL,
                `score` FLOAT DEFAULT 1.0,
                `difficulty` VARCHAR(20) DEFAULT 'medium',
                `explanation` TEXT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (PDOException $e) {}

        // 2. Bổ sung các cột còn thiếu vào bảng questions
        $cols = [
            'type'           => "ALTER TABLE `questions` ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'single_choice'",
            'question_type'  => "ALTER TABLE `questions` ADD COLUMN `question_type` VARCHAR(50) NOT NULL DEFAULT 'single_choice'",
            'content'        => "ALTER TABLE `questions` ADD COLUMN `content` TEXT NULL",
            'question_text'  => "ALTER TABLE `questions` ADD COLUMN `question_text` TEXT NULL",
            'options'        => "ALTER TABLE `questions` ADD COLUMN `options` LONGTEXT NULL",
            'option_a'       => "ALTER TABLE `questions` ADD COLUMN `option_a` TEXT NULL",
            'option_b'       => "ALTER TABLE `questions` ADD COLUMN `option_b` TEXT NULL",
            'option_c'       => "ALTER TABLE `questions` ADD COLUMN `option_c` TEXT NULL",
            'option_d'       => "ALTER TABLE `questions` ADD COLUMN `option_d` TEXT NULL",
            'correct_answer' => "ALTER TABLE `questions` ADD COLUMN `correct_answer` LONGTEXT NULL",
            'score'          => "ALTER TABLE `questions` ADD COLUMN `score` FLOAT DEFAULT 1.0",
            'difficulty'     => "ALTER TABLE `questions` ADD COLUMN `difficulty` VARCHAR(20) DEFAULT 'medium'",
            'explanation'    => "ALTER TABLE `questions` ADD COLUMN `explanation` TEXT NULL"
        ];

        foreach ($cols as $col => $sql) {
            if (!columnExists($pdo, 'questions', $col)) {
                try {
                    $pdo->exec($sql);
                } catch (PDOException $e) {}
            }
        }

        // 3. Đồng bộ hóa dữ liệu bí danh giữa type và question_type
        try {
            $pdo->exec("UPDATE questions SET type = question_type WHERE (type IS NULL OR type = '') AND question_type IS NOT NULL");
            $pdo->exec("UPDATE questions SET question_type = type WHERE (question_type IS NULL OR question_type = '') AND type IS NOT NULL");
        } catch (PDOException $e) {}
    }
}

// Alias hỗ trợ tên hàm dạng snake_case
if (!function_exists('auto_init_tables')) {
    function auto_init_tables($pdo = null) {
        autoInitTables($pdo);
    }
}
if (!function_exists('formatQuestionRow')) {
    function formatQuestionRow($q) {
        if (!$q || !is_array($q)) return $q;
        
        // Đồng bộ 'type' và 'question_type'
        $type = $q['question_type'] ?? $q['type'] ?? 'single_choice';
        $q['type']          = $type;
        $q['question_type'] = $type;

        // Đồng bộ 'content' và 'question_text'
        $content = $q['question_text'] ?? $q['content'] ?? '';
        $q['content']       = $content;
        $q['question_text'] = $content;

        return $q;
    }
}

/**
 * Hàm lưu hoặc cập nhật câu hỏi tương thích CSDL GỐC (chỉ dùng question_type và question_text)
 */
if (!function_exists('saveQuestionToDB')) {
    function saveQuestionToDB($data) {
        global $pdo;

        $id            = $data['id'] ?? null;
        $exam_id       = $data['exam_id'] ?? null;
        $subject_id    = $data['subject_id'] ?? null;
        $created_by    = $data['created_by'] ?? $data['user_id'] ?? null;
        
        // Lấy giá trị type & content dù gửi bằng key nào
        $question_type = $data['question_type'] ?? $data['type'] ?? 'single_choice';
        $question_text = $data['question_text'] ?? $data['content'] ?? '';
        
        $options        = isset($data['options']) ? (is_array($data['options']) ? json_encode($data['options'], JSON_UNESCAPED_UNICODE) : $data['options']) : null;
        $option_a       = $data['option_a'] ?? null;
        $option_b       = $data['option_b'] ?? null;
        $option_c       = $data['option_c'] ?? null;
        $option_d       = $data['option_d'] ?? null;
        $correct_answer = $data['correct_answer'] ?? null;
        $score          = $data['score'] ?? 1.0;
        $difficulty     = $data['difficulty'] ?? 'medium';
        $explanation    = $data['explanation'] ?? null;

        if ($id) {
            // Câu lệnh UPDATE đúng CSDL gốc
            $stmt = $pdo->prepare("
                UPDATE questions SET 
                    exam_id = ?, subject_id = ?, question_type = ?, question_text = ?, 
                    options = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, 
                    correct_answer = ?, score = ?, difficulty = ?, explanation = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                $exam_id, $subject_id, $question_type, $question_text,
                $options, $option_a, $option_b, $option_c, $option_d,
                $correct_answer, $score, $difficulty, $explanation, $id
            ]);
        } else {
            // Câu lệnh INSERT đúng CSDL gốc
            $stmt = $pdo->prepare("
                INSERT INTO questions 
                    (exam_id, subject_id, user_id, question_type, question_text, options, option_a, option_b, option_c, option_d, correct_answer, score, difficulty, explanation)
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $exam_id, $subject_id, $created_by, $question_type, $question_text,
                $options, $option_a, $option_b, $option_c, $option_d,
                $correct_answer, $score, $difficulty, $explanation
            ]);
            return $pdo->lastInsertId();
        }
    }
}