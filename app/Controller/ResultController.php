<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Result;
use App\Models\Exam;
use App\Models\Question;
use App\Helpers\Auth;

class ResultController extends Controller
{
    private Result $resultModel;
    private Exam $examModel;
    private Question $questionModel;

    public function __construct()
    {
        $this->resultModel = new Result();
        $this->examModel = new Exam();
        $this->questionModel = new Question();
    }

    public function view(int $id): void
    {
        $userId = Auth::id();
        $result = $this->resultModel->getByIdWithDetails($id);

        if (!$result || $result['user_id'] !== $userId) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Không tìm thấy kết quả.'];
            $this->redirect('/dashboard');
            return;
        }

        // Decode answers and get question details
        $answers = json_decode($result['answers'] ?? '{}', true);
        $questionIds = array_keys($answers);
        $questions = [];
        
        if (!empty($questionIds)) {
            $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
            $questions = $this->questionModel->query(
                "SELECT * FROM questions WHERE id IN ($placeholders)",
                $questionIds
            );
            // Map questions by id
            $questionsMap = [];
            foreach ($questions as $q) {
                $questionsMap[$q['id']] = $q;
            }
            $questions = $questionsMap;
        }

        $this->render('exams/result', [
            'page_title' => 'Kết quả thi',
            'result' => $result,
            'answers' => $answers,
            'questions' => $questions,
            'layout' => 'main'
        ]);
    }

    public function submit(): void
    {
        $userId = Auth::id();
        $data = $this->getBody();
        
        $examId = $data['exam_id'] ?? 0;
        $answers = $data['answers'] ?? [];
        $timeTaken = $data['time_taken'] ?? 0;

        if (!$examId) {
            $this->json(['success' => false, 'message' => 'Exam ID required'], 400);
            return;
        }

        // Get exam questions
        $questions = $this->questionModel->getForExam($examId);
        if (empty($questions)) {
            $this->json(['success' => false, 'message' => 'No questions found for this exam'], 400);
            return;
        }

        // Calculate score
        $scoreData = $this->resultModel->calculateScore($answers, $questions);

        // Save result
        $resultId = $this->resultModel->create([
            'user_id' => $userId,
            'exam_id' => $examId,
            'score' => $scoreData['score'],
            'correct_answers' => $scoreData['correct'],
            'total_questions' => $scoreData['total'],
            'time_taken' => $timeTaken,
            'answers' => json_encode($answers)
        ]);

        $this->json([
            'success' => true,
            'data' => [
                'result_id' => $resultId,
                'score' => $scoreData['score'],
                'correct' => $scoreData['correct'],
                'total' => $scoreData['total'],
                'details' => $scoreData['details']
            ]
        ]);
    }

    public function leaderboard(): void
    {
        $subjectSlug = $_GET['subject'] ?? '';
        $limit = (int)($_GET['limit'] ?? 10);
        
        $leaderboard = $this->resultModel->getLeaderboard($subjectSlug, $limit);

        $this->render('leaderboard/index', [
            'page_title' => 'Bảng xếp hạng',
            'leaderboard' => $leaderboard,
            'selected_subject' => $subjectSlug,
            'layout' => 'main'
        ]);
    }

    public function getLeaderboardData(): void
    {
        $subjectSlug = $_GET['subject'] ?? '';
        $limit = (int)($_GET['limit'] ?? 10);
        
        $leaderboard = $this->resultModel->getLeaderboard($subjectSlug, $limit);
        $this->json(['success' => true, 'data' => $leaderboard]);
    }
}