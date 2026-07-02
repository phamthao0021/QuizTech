<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Question;
use App\Models\Subject;
use App\Helpers\Auth;
use App\Helpers\Validation;

class QuestionController extends Controller
{
    private Question $questionModel;
    private Subject $subjectModel;

    public function __construct()
    {
        $this->questionModel = new Question();
        $this->subjectModel = new Subject();
    }

    public function index(): void
    {
        if (!Auth::isTeacher()) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Bạn không có quyền truy cập.'];
            $this->redirect('/dashboard');
            return;
        }

        $subjectId = (int)($_GET['subject_id'] ?? 0);
        $difficulty = $_GET['difficulty'] ?? '';

        $questions = $this->questionModel->getAllWithSubject();
        $subjects = $this->subjectModel->findAll([], 'name');

        $this->render('questions/index', [
            'page_title' => 'Quản lý câu hỏi',
            'questions' => $questions,
            'subjects' => $subjects,
            'selected_subject' => $subjectId,
            'selected_difficulty' => $difficulty,
            'layout' => 'main'
        ]);
    }

    public function get(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            $subjectId = (int)($_GET['subject_id'] ?? 0);
            $difficulty = $_GET['difficulty'] ?? '';
            
            $conditions = [];
            if ($subjectId) $conditions['subject_id'] = $subjectId;
            if ($difficulty) $conditions['difficulty'] = $difficulty;
            
            $questions = $this->questionModel->findAll($conditions, 'created_at DESC');
            $this->json(['success' => true, 'data' => $questions]);
            return;
        }

        $question = $this->questionModel->findById($id);
        if ($question) {
            $this->json(['success' => true, 'data' => $question]);
        } else {
            $this->json(['success' => false, 'message' => 'Không tìm thấy câu hỏi.'], 404);
        }
    }

    public function create(): void
    {
        if (!Auth::isTeacher()) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
            return;
        }

        $data = $this->getBody();
        $validator = new Validation();
        $validator->required('subject_id', $data['subject_id'] ?? '');
        $validator->required('question', $data['question'] ?? '');
        $validator->required('option_a', $data['option_a'] ?? '');
        $validator->required('option_b', $data['option_b'] ?? '');
        $validator->required('correct_answer', $data['correct_answer'] ?? '');

        if ($validator->hasErrors()) {
            $this->json(['success' => false, 'errors' => $validator->getErrors()], 400);
            return;
        }

        $id = $this->questionModel->create([
            'subject_id' => $data['subject_id'],
            'question' => $data['question'],
            'option_a' => $data['option_a'],
            'option_b' => $data['option_b'],
            'option_c' => $data['option_c'] ?? '',
            'option_d' => $data['option_d'] ?? '',
            'correct_answer' => $data['correct_answer'],
            'difficulty' => $data['difficulty'] ?? 'medium',
            'points' => $data['points'] ?? 1,
            'explanation' => $data['explanation'] ?? '',
            'ai_generated' => $data['ai_generated'] ?? 0
        ]);

        $this->json([
            'success' => true,
            'data' => ['id' => $id],
            'message' => 'Đã tạo câu hỏi thành công.'
        ], 201);
    }

    public function update(): void
    {
        if (!Auth::isTeacher()) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID không hợp lệ.'], 400);
            return;
        }

        $data = $this->getBody();
        $validator = new Validation();
        $validator->required('subject_id', $data['subject_id'] ?? '');
        $validator->required('question', $data['question'] ?? '');
        $validator->required('option_a', $data['option_a'] ?? '');
        $validator->required('option_b', $data['option_b'] ?? '');
        $validator->required('correct_answer', $data['correct_answer'] ?? '');

        if ($validator->hasErrors()) {
            $this->json(['success' => false, 'errors' => $validator->getErrors()], 400);
            return;
        }

        $updated = $this->questionModel->update($id, [
            'subject_id' => $data['subject_id'],
            'question' => $data['question'],
            'option_a' => $data['option_a'],
            'option_b' => $data['option_b'],
            'option_c' => $data['option_c'] ?? '',
            'option_d' => $data['option_d'] ?? '',
            'correct_answer' => $data['correct_answer'],
            'difficulty' => $data['difficulty'] ?? 'medium',
            'points' => $data['points'] ?? 1,
            'explanation' => $data['explanation'] ?? ''
        ]);

        if ($updated) {
            $this->json(['success' => true, 'message' => 'Đã cập nhật câu hỏi thành công.']);
        } else {
            $this->json(['success' => false, 'message' => 'Không tìm thấy câu hỏi.'], 404);
        }
    }

    public function delete(): void
    {
        if (!Auth::isTeacher()) {
            $this->json(['success' => false, 'message' => 'Unauthorized'], 403);
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID không hợp lệ.'], 400);
            return;
        }

        $deleted = $this->questionModel->delete($id);
        if ($deleted) {
            $this->json(['success' => true, 'message' => 'Đã xóa câu hỏi thành công.']);
        } else {
            $this->json(['success' => false, 'message' => 'Không tìm thấy câu hỏi.'], 404);
        }
    }
}