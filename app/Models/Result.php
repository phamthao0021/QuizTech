<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Result extends Model
{
    protected string $table = 'results';
    protected array $fillable = [
        'user_id', 'exam_id', 'score', 'correct_answers', 
        'total_questions', 'time_taken', 'answers', 'ai_analysis'
    ];

    public function getByUser(int $userId): array
    {
        return $this->query("
            SELECT r.*, e.title as exam_title, s.name as subject_name
            FROM results r
            JOIN exams e ON e.id = r.exam_id
            JOIN subjects s ON s.id = e.subject_id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ", [$userId]);
    }

    public function getByIdWithDetails(int $id): ?array
    {
        $result = $this->queryOne("
            SELECT r.*, e.title as exam_title, s.name as subject_name,
                   u.fullname, u.student_code
            FROM results r
            JOIN exams e ON e.id = r.exam_id
            JOIN subjects s ON s.id = e.subject_id
            JOIN users u ON u.id = r.user_id
            WHERE r.id = ?
        ", [$id]);
        
        if ($result) {
            $result['answers'] = json_decode($result['answers'] ?? '{}', true);
            
            // Get question details for each answer
            if (!empty($result['answers'])) {
                $questionIds = array_keys($result['answers']);
                $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
                $questions = $this->query(
                    "SELECT id, question, correct_answer FROM questions WHERE id IN ($placeholders)",
                    $questionIds
                );
                $result['questions'] = $questions;
            }
        }
        
        return $result;
    }

    public function getLeaderboard(string $subjectSlug = '', int $limit = 10): array
    {
        $sql = "
            SELECT 
                u.fullname,
                u.student_code,
                COUNT(r.id) as exam_count,
                AVG(r.score) as avg_score,
                MAX(r.score) as best_score,
                SUM(r.correct_answers) as total_correct,
                SUM(r.total_questions) as total_questions
            FROM results r
            JOIN users u ON u.id = r.user_id
        ";
        $params = [];

        if ($subjectSlug) {
            $sql .= " JOIN exams e ON e.id = r.exam_id JOIN subjects s ON s.id = e.subject_id ";
            $sql .= " WHERE s.slug = ?";
            $params[] = $subjectSlug;
        }

        $sql .= " GROUP BY u.id ORDER BY avg_score DESC LIMIT ?";
        $params[] = $limit;

        return $this->query($sql, $params);
    }

    public function calculateScore(array $answers, array $questions): array
    {
        $correct = 0;
        $total = count($questions);
        $details = [];

        foreach ($questions as $index => $q) {
            $userAnswer = $answers[$index] ?? '';
            $isCorrect = $userAnswer === $q['correct_answer'];
            if ($isCorrect) {
                $correct++;
            }
            $details[] = [
                'question_id' => $q['id'],
                'user_answer' => $userAnswer,
                'correct_answer' => $q['correct_answer'],
                'is_correct' => $isCorrect
            ];
        }

        $score = round(($correct / $total) * 10, 1);

        return [
            'score' => $score,
            'correct' => $correct,
            'total' => $total,
            'details' => $details
        ];
    }
}