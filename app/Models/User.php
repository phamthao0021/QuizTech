<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'student_code', 'fullname', 'email', 'password_hash', 
        'avatar', 'role', 'is_active'
    ];

    public function findByEmail(string $email): ?array
    {
        return $this->queryOne("SELECT * FROM users WHERE email = ?", [$email]);
    }

    public function findByStudentCode(string $code): ?array
    {
        return $this->queryOne("SELECT * FROM users WHERE student_code = ?", [$code]);
    }

    public function findByEmailOrStudentCode(string $identifier): ?array
    {
        return $this->queryOne(
            "SELECT * FROM users WHERE email = ? OR student_code = ?", 
            [$identifier, $identifier]
        );
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function getRole(int $userId): ?string
    {
        $stmt = self::getDb()->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['role'] ?? null;
    }

    public function getStats(int $userId): array
    {
        $stmt = self::getDb()->prepare("
            SELECT 
                COUNT(*) as total_exams,
                AVG(score) as avg_score,
                MAX(score) as best_score,
                SUM(correct_answers) as total_correct,
                SUM(total_questions) as total_questions,
                SUM(time_taken) as total_time
            FROM results 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [
            'total_exams' => 0,
            'avg_score' => 0,
            'best_score' => 0,
            'total_correct' => 0,
            'total_questions' => 0,
            'total_time' => 0
        ];
    }

    public function getRecentResults(int $userId, int $limit = 10): array
    {
        return $this->query("
            SELECT r.*, e.title as exam_title, s.name as subject_name
            FROM results r
            JOIN exams e ON e.id = r.exam_id
            JOIN subjects s ON s.id = e.subject_id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
            LIMIT ?
        ", [$userId, $limit]);
    }

    public function getWeakSubjects(int $userId): array
    {
        return $this->query("
            SELECT s.name, AVG(r.score) as avg_score, COUNT(r.id) as exam_count
            FROM subjects s
            JOIN exams e ON e.subject_id = s.id
            JOIN results r ON r.exam_id = e.id
            WHERE r.user_id = ?
            GROUP BY s.id
            ORDER BY avg_score ASC
            LIMIT 3
        ", [$userId]);
    }
}