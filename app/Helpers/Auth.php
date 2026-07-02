<?php
declare(strict_types=1);

namespace App\Helpers;

class Auth
{
    public static function login(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['student_code'] = $user['student_code'];
        $_SESSION['role'] = $user['role'];
        session_regenerate_id(true);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        static $user = null;
        if ($user === null) {
            $pdo = \App\Config\Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([self::id()]);
            $user = $stmt->fetch() ?: null;
        }
        return $user;
    }

    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isTeacher(): bool
    {
        return in_array(self::role(), ['admin', 'teacher']);
    }

    public static function isStudent(): bool
    {
        return self::role() === 'student';
    }

    public static function hasRole(string $role): bool
    {
        return self::role() === $role;
    }
}