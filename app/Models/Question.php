<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Question extends Model
{
    protected string $table = 'questions';
    protected array $fillable = [
        'subject_id', 'question', 'option_a', 'option_b', 'option_c', 'option_d',
        'correct_answer', 'difficulty', 'points', 'explanation', 'image', 'ai_generated'
    ];

    public function getAllWithSubject(): array
    {
        return $this->query("
            SELECT q.*, s.name as subject_name 
            FROM questions q 
            JOIN subjects s ON s.id = q.subject_id 
            ORDER BY q.created_at DESC
        ");
    }

    public function getBySubject(int $subjectId): array
    {
        return $this->query(
            "SELECT * FROM questions WHERE subject_id = ? ORDER BY created_at DESC",
            [$subjectId]
        );
    }

    public function getByDifficulty(string $difficulty): array
    {
        return $this->query(
            "SELECT q.*, s.name as subject_name 
             FROM questions q 
             JOIN subjects s ON s.id = q.subject_id 
             WHERE q.difficulty = ?
             ORDER BY RAND()",
            [$difficulty]
        );
    }

    public function getRandom(int $subjectId, int $limit, string $difficulty = ''): array
    {
        $sql = "SELECT * FROM questions WHERE subject_id = ?";
        $params = [$subjectId];
        
        if ($difficulty) {
            $sql .= " AND difficulty = ?";
            $params[] = $difficulty;
        }
        
        $sql .= " ORDER BY RAND() LIMIT ?";
        $params[] = $limit;
        
        return $this->query($sql, $params);
    }

    public function getForExam(int $examId): array
    {
        return $this->query("
            SELECT q.*, eq.question_order 
            FROM exam_questions eq 
            JOIN questions q ON q.id = eq.question_id 
            WHERE eq.exam_id = ? 
            ORDER BY eq.question_order
        ", [$examId]);
    }

    public function countBySubject(int $subjectId): int
    {
        return (int)$this->count(['subject_id' => $subjectId]);
    }
}