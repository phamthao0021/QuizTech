<?php

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

$router = new Router();

// ============================================
// Public Routes
// ============================================
$router->get('/', 'HomeController', 'index');
$router->get('/home', 'HomeController', 'index');

// Auth
$router->get('/login', 'AuthController', 'login');
$router->post('/login', 'AuthController', 'postLogin');
$router->get('/register', 'AuthController', 'register');
$router->post('/register', 'AuthController', 'postRegister');
$router->get('/logout', 'AuthController', 'logout');

// ============================================
// Protected Routes (require login)
// ============================================
$router->middleware(AuthMiddleware::class);

// Dashboard
$router->get('/dashboard', 'DashboardController', 'index');
$router->get('/api/dashboard/stats', 'DashboardController', 'getStats');

// Subjects (require teacher)
$router->get('/subjects', 'SubjectController', 'index');
$router->get('/api/subjects', 'SubjectController', 'get');
$router->post('/api/subjects', 'SubjectController', 'create');
$router->put('/api/subjects/{id}', 'SubjectController', 'update');
$router->delete('/api/subjects/{id}', 'SubjectController', 'delete');

// Questions (require teacher)
$router->get('/questions', 'QuestionController', 'index');
$router->get('/api/questions', 'QuestionController', 'get');
$router->post('/api/questions', 'QuestionController', 'create');
$router->put('/api/questions/{id}', 'QuestionController', 'update');
$router->delete('/api/questions/{id}', 'QuestionController', 'delete');

// Exams
$router->get('/exams', 'ExamController', 'index');
$router->get('/exam/{id}', 'ExamController', 'view');
$router->get('/exam-take/{id}', 'ExamController', 'take');
$router->post('/api/exams', 'ExamController', 'create');
$router->put('/api/exams/{id}', 'ExamController', 'update');
$router->delete('/api/exams/{id}', 'ExamController', 'delete');

// Results
$router->get('/result/{id}', 'ResultController', 'view');
$router->post('/api/results', 'ResultController', 'submit');
$router->get('/leaderboard', 'ResultController', 'leaderboard');
$router->get('/api/leaderboard', 'ResultController', 'getLeaderboardData');

// Rooms
$router->get('/rooms', 'RoomController', 'index');
$router->post('/api/rooms', 'RoomController', 'create');
$router->get('/rooms/join', 'RoomController', 'join');
$router->post('/api/rooms/start', 'RoomController', 'start');
$router->post('/api/rooms/answer', 'RoomController', 'answer');
$router->get('/api/rooms/status', 'RoomController', 'getStatus');

// AI
$router->get('/ai/ocr', 'AiController', 'ocr');
$router->post('/api/ai/ocr', 'AiController', 'processOcr');
$router->get('/ai/analyze', 'AiController', 'analyze');
$router->post('/api/ai/analyze', 'AiController', 'processAnalyze');
$router->post('/api/ai/generate', 'AiController', 'generateQuestions');

// Profile
$router->get('/profile', 'ProfileController', 'index');
$router->post('/api/profile', 'ProfileController', 'update');

return $router;