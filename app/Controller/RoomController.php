<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Room;
use App\Models\Exam;
use App\Models\Question;
use App\Helpers\Auth;

class RoomController extends Controller
{
    private Room $roomModel;
    private Exam $examModel;
    private Question $questionModel;

    public function __construct()
    {
        $this->roomModel = new Room();
        $this->examModel = new Exam();
        $this->questionModel = new Question();
    }

    public function index(): void
    {
        $userId = Auth::id();
        $activeRooms = $this->roomModel->getActiveRooms();
        
        // Get rooms user is in
        $myRooms = $this->roomModel->query("
            SELECT r.*, e.title as exam_title
            FROM room_members rm
            JOIN rooms r ON r.id = rm.room_id
            JOIN exams e ON e.id = r.exam_id
            WHERE rm.user_id = ?
            ORDER BY r.created_at DESC
        ", [$userId]);

        $exams = $this->examModel->findAll(['is_active' => 1], 'title');

        $this->render('rooms/index', [
            'page_title' => 'Phòng thi',
            'active_rooms' => $activeRooms,
            'my_rooms' => $myRooms,
            'exams' => $exams,
            'layout' => 'main'
        ]);
    }

    public function create(): void
    {
        if (!Auth::isTeacher()) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Chỉ giảng viên mới có thể tạo phòng.'];
            $this->redirect('/rooms');
            return;
        }

        $data = $this->getBody();
        $examId = $data['exam_id'] ?? 0;
        $maxPlayers = $data['max_players'] ?? 10;

        if (!$examId) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Vui lòng chọn đề thi.'];
            $this->redirect('/rooms');
            return;
        }

        $code = $this->roomModel->generateCode();
        $roomId = $this->roomModel->create([
            'room_code' => $code,
            'exam_id' => $examId,
            'host_id' => Auth::id(),
            'max_players' => $maxPlayers
        ]);

        // Add host to room
        $this->roomModel->join($roomId, Auth::id());

        $_SESSION['flash'] = ['type' => 'success', 'message' => "Đã tạo phòng. Mã: $code"];
        $this->redirect('/rooms/join?code=' . $code);
    }

    public function join(): void
    {
        $code = $_GET['code'] ?? '';
        if (!$code) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Không tìm thấy phòng.'];
            $this->redirect('/rooms');
            return;
        }

        $room = $this->roomModel->findByCode($code);
        if (!$room) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Không tìm thấy phòng.'];
            $this->redirect('/rooms');
            return;
        }

        if ($room['status'] === 'finished') {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Phòng đã kết thúc.'];
            $this->redirect('/rooms');
            return;
        }

        $userId = Auth::id();
        if (!$this->roomModel->isMember($room['id'], $userId)) {
            $this->roomModel->join($room['id'], $userId);
        }

        $roomDetails = $this->roomModel->getWithDetails($room['id']);
        $questions = $this->questionModel->getForExam($room['exam_id']);

        $this->render('rooms/join', [
            'page_title' => 'Phòng thi',
            'room' => $roomDetails,
            'questions' => $questions,
            'layout' => 'main'
        ]);
    }

    public function start(): void
    {
        $roomId = (int)($_POST['room_id'] ?? 0);
        if (!$roomId) {
            $this->json(['success' => false, 'message' => 'Room ID required'], 400);
            return;
        }

        $room = $this->roomModel->findById($roomId);
        if (!$room || $room['host_id'] !== Auth::id()) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
            return;
        }

        $this->roomModel->start($roomId);
        $this->json(['success' => true, 'message' => 'Phòng đã bắt đầu!']);
    }

    public function answer(): void
    {
        $data = $this->getBody();
        $roomId = $data['room_id'] ?? 0;
        $questionId = $data['question_id'] ?? 0;
        $answer = $data['answer'] ?? '';

        if (!$roomId || !$questionId) {
            $this->json(['success' => false, 'message' => 'Invalid data'], 400);
            return;
        }

        // Get correct answer
        $question = $this->questionModel->findById($questionId);
        if (!$question) {
            $this->json(['success' => false, 'message' => 'Question not found'], 404);
            return;
        }

        $isCorrect = $answer === $question['correct_answer'];
        
        // Save answer
        $this->roomModel->query(
            "INSERT INTO room_answers (room_id, user_id, question_id, answer, is_correct) 
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE answer = VALUES(answer), is_correct = VALUES(is_correct)",
            [$roomId, Auth::id(), $questionId, $answer, $isCorrect ? 1 : 0]
        );

        // Update score in room_results
        if ($isCorrect) {
            $this->roomModel->query(
                "INSERT INTO room_results (room_id, user_id, score, correct_count) 
                 VALUES (?, ?, 1, 1)
                 ON DUPLICATE KEY UPDATE 
                 score = score + 1, 
                 correct_count = correct_count + 1",
                [$roomId, Auth::id()]
            );
        }

        // Get current question number
        $currentQuestion = $this->roomModel->queryOne(
            "SELECT current_question FROM rooms WHERE id = ?",
            [$roomId]
        );
        $currentIndex = $currentQuestion['current_question'] ?? 0;
        
        // Move to next question
        $this->roomModel->update($roomId, [
            'current_question' => $currentIndex + 1
        ]);

        // Get leaderboard
        $leaderboard = $this->roomModel->query("
            SELECT u.fullname, rr.score, rr.correct_count
            FROM room_results rr
            JOIN users u ON u.id = rr.user_id
            WHERE rr.room_id = ?
            ORDER BY rr.score DESC
        ", [$roomId]);

        $this->json([
            'success' => true,
            'data' => [
                'is_correct' => $isCorrect,
                'correct_answer' => $question['correct_answer'],
                'leaderboard' => $leaderboard
            ]
        ]);
    }

    public function getStatus(): void
    {
        $roomId = (int)($_GET['room_id'] ?? 0);
        if (!$roomId) {
            $this->json(['success' => false, 'message' => 'Room ID required'], 400);
            return;
        }

        $room = $this->roomModel->findById($roomId);
        if (!$room) {
            $this->json(['success' => false, 'message' => 'Room not found'], 404);
            return;
        }

        $members = $this->roomModel->getMembers($roomId);
        $leaderboard = $this->roomModel->query("
            SELECT u.fullname, rr.score, rr.correct_count
            FROM room_results rr
            JOIN users u ON u.id = rr.user_id
            WHERE rr.room_id = ?
            ORDER BY rr.score DESC
        ", [$roomId]);

        $this->json([
            'success' => true,
            'data' => [
                'room' => $room,
                'members' => $members,
                'leaderboard' => $leaderboard
            ]
        ]);
    }
}