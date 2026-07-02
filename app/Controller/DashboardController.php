<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Models\User;
use App\Models\Exam;
use App\Models\Result;
use App\Helpers\Auth;

class DashboardController extends Controller
{
    private User $userModel;
    private Exam $examModel;
    private Result $resultModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->examModel = new Exam();
        $this->resultModel = new Result();
    }

    public function index(): void
    {
        $userId = Auth::id();
        $stats = $this->userModel->getStats($userId);
        $recentResults = $this->userModel->getRecentResults($userId, 5);
        $weakSubjects = $this->userModel->getWeakSubjects($userId);

        // Recommended exams (not taken yet)
        $takenExamIds = $this->resultModel->query(
            "SELECT DISTINCT exam_id FROM results WHERE user_id = ?",
            [$userId]
        );
        $takenIds = array_column($takenExamIds, 'exam_id');
        $where = "is_active = 1";
        if (!empty($takenIds)) {
            $where .= " AND id NOT IN (" . implode(',', $takenIds) . ")";
        }
        $recommended = $this->examModel->query(
            "SELECT e.*, s.name as subject_name 
             FROM exams e 
             JOIN subjects s ON s.id = e.subject_id 
             WHERE $where 
             ORDER BY RAND() 
             LIMIT 4"
        );

        $this->render('dashboard/index', [
            'page_title' => 'Dashboard',
            'stats' => $stats,
            'recent_results' => $recentResults,
            'weak_subjects' => $weakSubjects,
            'recommended' => $recommended,
            'layout' => 'main'
        ]);
    }

    public function getStats(): void
    {
        $userId = Auth::id();
        $stats = $this->userModel->getStats($userId);
        $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}