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
    $difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';
    
    if ($id > 0) {
        $stmt = $pdo->prepare('
            SELECT q.*, s.name as subject_name 
            FROM questions q 
            JOIN subjects s ON s.id = q.subject_id 
            WHERE q.id = ?
        ');
        $stmt->execute([$id]);
        $question = $stmt->fetch();
        
        if ($question) {
            echo json_encode($question);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Question not found']);
        }
        exit;
    }
    
    $sql = '
        SELECT q.*, s.name as subject_name 
        FROM questions q 
        JOIN subjects s ON s.id = q.subject_id 
        WHERE 1=1
    ';
    $params = [];
    
    if ($subject_id > 0) {
        $sql .= ' AND q.subject_id = ?';
        $params[] = $subject_id;
    }
    
    if ($difficulty) {
        $sql .= ' AND q.difficulty = ?';
        $params[] = $difficulty;
    }
    
    $sql .= ' ORDER BY q.created_at DESC';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $questions = $stmt->fetchAll();
    
    echo json_encode($questions);
    exit;
}

// POST - Tạo mới
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required = ['subject_id', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "$field is required"]);
            exit;
        }
    }
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO questions (subject_id, question, option_a, option_b, option_c, option_d, correct_answer, difficulty, points, explanation)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['subject_id'],
            $data['question'],
            $data['option_a'],
            $data['option_b'],
            $data['option_c'],
            $data['option_d'],
            $data['correct_answer'],
            $data['difficulty'] ?? 'medium',
            $data['points'] ?? 1,
            $data['explanation'] ?? null
        ]);
        
        $id = (int)$pdo->lastInsertId();
        
        http_response_code(201);
        echo json_encode([
            'id' => $id,
            'message' => 'Question created successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create question: ' . $e->getMessage()]);
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
    
    $required = ['subject_id', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "$field is required"]);
            exit;
        }
    }
    
    try {
        $stmt = $pdo->prepare('
            UPDATE questions 
            SET subject_id = ?, question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, 
                correct_answer = ?, difficulty = ?, points = ?, explanation = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $data['subject_id'],
            $data['question'],
            $data['option_a'],
            $data['option_b'],
            $data['option_c'],
            $data['option_d'],
            $data['correct_answer'],
            $data['difficulty'] ?? 'medium',
            $data['points'] ?? 1,
            $data['explanation'] ?? null,
            $id
        ]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'Question updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Question not found or no changes made']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update question']);
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
    
    $stmt = $pdo->prepare('DELETE FROM questions WHERE id = ?');
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Question deleted successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Question not found']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);