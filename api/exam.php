<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_teacher();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$pdo = db();

// GET - Lấy danh sách hoặc chi tiết
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
    
    if ($id > 0) {
        $stmt = $pdo->prepare('
            SELECT e.*, s.name as subject_name 
            FROM exams e 
            JOIN subjects s ON s.id = e.subject_id 
            WHERE e.id = ?
        ');
        $stmt->execute([$id]);
        $exam = $stmt->fetch();
        
        if ($exam) {
            // Lấy danh sách câu hỏi trong đề
            $stmt2 = $pdo->prepare('
                SELECT q.*, eq.question_order 
                FROM exam_questions eq 
                JOIN questions q ON q.id = eq.question_id 
                WHERE eq.exam_id = ? 
                ORDER BY eq.question_order
            ');
            $stmt2->execute([$id]);
            $exam['questions'] = $stmt2->fetchAll();
            
            echo json_encode($exam);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Exam not found']);
        }
        exit;
    }
    
    $sql = '
        SELECT e.*, s.name as subject_name,
               (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count
        FROM exams e 
        JOIN subjects s ON s.id = e.subject_id 
        WHERE 1=1
    ';
    $params = [];
    
    if ($subject_id > 0) {
        $sql .= ' AND e.subject_id = ?';
        $params[] = $subject_id;
    }
    
    $sql .= ' ORDER BY e.created_at DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $exams = $stmt->fetchAll();
    
    echo json_encode($exams);
    exit;
}

// POST - Tạo mới
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required = ['subject_id', 'title', 'duration', 'total_questions'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "$field is required"]);
            exit;
        }
    }
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO exams (subject_id, title, description, duration, total_questions, difficulty, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['subject_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['duration'],
            $data['total_questions'],
            $data['difficulty'] ?? 'mixed',
            $_SESSION['user_id']
        ]);
        
        $id = (int)$pdo->lastInsertId();
        
        http_response_code(201);
        echo json_encode([
            'id' => $id,
            'message' => 'Exam created successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create exam: ' . $e->getMessage()]);
    }
    exit;
}

// PUT - Cập nhật
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare('
            UPDATE exams 
            SET subject_id = ?, title = ?, description = ?, duration = ?, 
                total_questions = ?, difficulty = ?, is_active = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $data['subject_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['duration'],
            $data['total_questions'],
            $data['difficulty'] ?? 'mixed',
            $data['is_active'] ?? 1,
            $id
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'Exam updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Exam not found or no changes made']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update exam']);
    }
    exit;
}

// DELETE - Xóa
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }
    
    $stmt = $pdo->prepare('DELETE FROM exams WHERE id = ?');
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Exam deleted successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Exam not found']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);