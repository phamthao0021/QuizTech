<?php
/**
 * includes/permissions.php
 * Phân quyền QuizTech: admin (Super Admin), admin_test, teacher, student.
 */
if (!defined('QUIZTECH_PERMISSIONS_LOADED')) {
    define('QUIZTECH_PERMISSIONS_LOADED', true);
}

if (!function_exists('qt_current_role')) {
    function qt_current_role()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $raw = $_SESSION['role'] ?? ($_SESSION['user']['role'] ?? '');
        return function_exists('normalizeRole') ? normalizeRole($raw) : strtolower(trim((string)$raw));
    }
}

if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin()
    {
        return isLoggedIn() && qt_current_role() === 'admin';
    }
}

if (!function_exists('isAdminTest')) {
    function isAdminTest()
    {
        return isLoggedIn() && qt_current_role() === 'admin_test';
    }
}

if (!function_exists('isAdminPanelUser')) {
    /** Có quyền vào khu vực admin UI (admin + admin_test). */
    function isAdminPanelUser()
    {
        return isLoggedIn() && in_array(qt_current_role(), ['admin', 'admin_test'], true);
    }
}

if (!function_exists('isStudent')) {
    function isStudent()
    {
        return isLoggedIn() && qt_current_role() === 'student';
    }
}

if (!function_exists('can_assign_role')) {
    /**
     * Ai được gán role nào khi tạo/sửa user.
     * - Super Admin: mọi role
     * - admin_test: teacher, student, admin_test — KHÔNG được gán/nâng admin
     */
    function can_assign_role($target_role)
    {
        $target = function_exists('normalizeRole') ? normalizeRole($target_role) : strtolower(trim((string)$target_role));
        if (isSuperAdmin()) {
            return in_array($target, ['admin', 'admin_test', 'teacher', 'student'], true);
        }
        if (isAdminTest()) {
            return in_array($target, ['admin_test', 'teacher', 'student'], true);
        }
        return false;
    }
}

if (!function_exists('can_manage_user')) {
    /**
     * Kiểm tra actor có được thao tác (edit/delete/lock/đổi role) lên target user không.
     * $action: edit|delete|lock|change_role|set_protected
     */
    function can_manage_user(array $target, $action = 'edit')
    {
        if (!isAdminPanelUser()) {
            return false;
        }

        $target_id   = (int)($target['id'] ?? 0);
        $target_role = function_exists('normalizeRole')
            ? normalizeRole($target['role'] ?? 'student')
            : strtolower(trim((string)($target['role'] ?? 'student')));
        $is_protected = !empty($target['is_protected']);
        $actor_id     = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);

        if ($target_id <= 0) {
            return false;
        }

        // Không tự xóa / khóa chính mình
        if (in_array($action, ['delete', 'lock'], true) && $actor_id === $target_id) {
            return false;
        }

        // Chỉ Super Admin được sửa is_protected
        if ($action === 'set_protected') {
            return isSuperAdmin();
        }

        if (isSuperAdmin()) {
            // Super Admin không tự hạ role admin của chính mình (xử lý thêm ở caller)
            return true;
        }

        // admin_test
        if (isAdminTest()) {
            // Không đụng tài khoản protected
            if ($is_protected) {
                return false;
            }
            // Không sửa/xóa/khóa/đổi quyền Super Admin
            if ($target_role === 'admin') {
                return false;
            }
            if ($action === 'change_role') {
                return true; // vẫn phải qua can_assign_role cho role đích
            }
            return in_array($action, ['edit', 'delete', 'lock'], true);
        }

        return false;
    }
}

if (!function_exists('assignable_roles_for_actor')) {
    function assignable_roles_for_actor()
    {
        if (isSuperAdmin()) {
            return [
                'student'    => 'Học sinh / Sinh viên',
                'teacher'    => 'Giảng viên',
                'admin_test' => 'Admin kiểm thử',
                'admin'      => 'Super Admin',
            ];
        }
        if (isAdminTest()) {
            return [
                'student'    => 'Học sinh / Sinh viên',
                'teacher'    => 'Giảng viên',
                'admin_test' => 'Admin kiểm thử',
            ];
        }
        return [];
    }
}

if (!function_exists('requireSuperAdmin')) {
    function requireSuperAdmin()
    {
        requireLogin();
        if (!isSuperAdmin()) {
            setFlash('danger', 'Chỉ Super Admin mới được thực hiện thao tác này.');
            if (strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false) {
                redirect('dashboard.php');
            }
            $role = qt_current_role();
            $home = function_exists('homeForRole') ? homeForRole($role) : 'index.php';
            redirect('../' . ltrim($home, '/'));
        }
    }
}

if (!function_exists('ensure_users_role_schema')) {
    /** Đảm bảo schema role/is_protected/username tồn tại (idempotent). */
    function ensure_users_role_schema()
    {
        global $pdo;
        if (!$pdo) {
            return;
        }
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('student','teacher','admin','admin_test') COLLATE utf8mb4_unicode_ci DEFAULT 'student'");
        } catch (Throwable $e) {
            // ignore
        }
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'is_protected'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `is_protected` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`");
            }
        } catch (Throwable $e) {
        }
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'username'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `username` VARCHAR(100) NULL DEFAULT NULL AFTER `email`");
            }
        } catch (Throwable $e) {
        }
    }
}
