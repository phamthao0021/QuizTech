<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Subject;
use App\Helpers\Auth;
use App\Helpers\Validation;
use App\Helpers\Response;

class SubjectController extends Controller
{
    private Subject $subjectModel;

    public function __construct()
    {
        $this->subjectModel = new Subject();
    }

    public function index(): void
    {
        if (!Auth::isTeacher()) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Bạn không có quyền truy cập.'];
            $this->redirect('/dashboard');
            return;
        }

        $subjects = $this->subjectModel->getAllWithStats();

        $this->render('subjects/index', [
            'page_title' => 'Quản lý môn học',
            'subjects' => $subjects,
            'layout' => 'main'
        ]);
    }

    public function get(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            $subjects = $this->subjectModel->findAll([], 'name');
            $this->json(['success' => true, 'data' => $subjects]);
            return;
        }

        $subject = $this->subjectModel->findById($id);
        if ($subject) {
            $this->json(['success' => true, 'data' => $subject]);
        } else {
            $this->json(['success' => false, 'message' => 'Không tìm thấy môn học.'], 404);
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
        $validator->required('name', $data['name'] ?? '');

        if ($validator->hasErrors()) {
            $this->json(['success' => false, 'errors' => $validator->getErrors()], 400);
            return;
        }

        $slug = $this->subjectModel->createSlug($data['name']);
        $id = $this->subjectModel->create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'icon' => $data['icon'] ?? 'fa-book',
            'color' => $data['color'] ?? '#6c5ce7'
        ]);

        $this->json([
            'success' => true,
            'data' => ['id' => $id, 'slug' => $slug],
            'message' => 'Đã tạo môn học thành công.'
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
        $validator->required('name', $data['name'] ?? '');

        if ($validator->hasErrors()) {
            $this->json(['success' => false, 'errors' => $validator->getErrors()], 400);
            return;
        }

        $slug = $this->subjectModel->createSlug($data['name']);
        $updated = $this->subjectModel->update($id, [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? '',
            'icon' => $data['icon'] ?? 'fa-book',
            'color' => $data['color'] ?? '#6c5ce7'
        ]);

        if ($updated) {
            $this->json(['success' => true, 'message' => 'Đã cập nhật môn học thành công.']);
        } else {
            $this->json(['success' => false, 'message' => 'Không tìm thấy môn học.'], 404);
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

        $deleted = $this->subjectModel->delete($id);
        if ($deleted) {
            $this->json(['success' => true, 'message' => 'Đã xóa môn học thành công.']);
        } else {
            $this->json(['success' => false, 'message' => 'Không tìm thấy môn học.'], 404);
        }
    }
}