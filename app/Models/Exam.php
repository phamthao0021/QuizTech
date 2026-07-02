<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Exam extends Model
{
    protected string $table = 'exams';
    protected array $fillable = [
        'subject_id', 'title', 'description', 'duration', 
        'total_questions', 'difficulty', 'is_active', 'created_by'
    ];

    public function getAllWithDetails(): array
    {
        return $this->query("
            SELECT e.*, s.name as subject_name,
                   (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count
            FROM exams e
            JOIN subjects s ON s.id = e.subject_id
            WHERE e.is_active = 1
            ORDER BY e.created_at DESC
        ");
    }

    public function getByIdWithQuestions(int $id): ?array
    {
        $exam = $this->findById($id);
        if (!$exam) {
            return null;
        }

        $exam['questions'] = (new Question())->getForExam($id);
        $exam['subject_name'] = $this->queryOne(
            "SELECT name FROM subjects WHERE id = ?", 
            [$exam['subject_id']]
        )['name'] ?? '';

        return $exam;
    }

    public function addQuestion(int $examId, int $questionId, int $order): bool
    {
        $stmt = self::getDb()->prepare(
            "INSERT IGNORE INTO exam_questions (exam_id, question_id, question_order) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$examId, $questionId, $order]);
    }

    public function removeQuestion(int $examId, int $questionId): bool
    {
        $stmt = self::getDb()->prepare(
            "DELETE FROM exam_questions WHERE exam_id = ? AND question_id = ?"
        );
        return $stmt->execute([$examId, $questionId]);
    }

    public function getQuestionCount(int $examId): int
    {
        return (int)$this->queryOne(
            "SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?",
            [$examId]
        )['COUNT(*)'] ?? 0;
    }

    public function getResults(int $examId): array
    {
        return $this->query("
            SELECT r.*, u.fullname, u.student_code
            FROM results r
            JOIN users u ON u.id = r.user_id
            WHERE r.exam_id = ?
            ORDER BY r.score DESC
        ", [$examId]);
    }
}