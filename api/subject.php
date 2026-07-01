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
    
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM subjects WHERE id = ?');
        $stmt->execute([$id]);
        $subject = $stmt->fetch();
        
        if ($subject) {
            echo json_encode($subject);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Subject not found']);
        }
    } else {
        $stmt = $pdo->query('SELECT * FROM subjects ORDER BY name');
        $subjects = $stmt->fetchAll();
        echo json_encode($subjects);
    }
    exit;
}

// POST - Tạo mới
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['name']) || empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        exit;
    }
    
    $name = trim($data['name']);
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
    $description = $data['description'] ?? '';
    $icon = $data['icon'] ?? 'fa-book';
    $color = $data['color'] ?? '#0d6efd';
    
    try {
        $stmt = $pdo->prepare('INSERT INTO subjects (name, slug, description, icon, color) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $slug, $description, $icon, $color]);
        $id = (int)$pdo->lastInsertId();
        
        http_response_code(201);
        echo json_encode([
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'message' => 'Subject created successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(409);
        echo json_encode(['error' => 'Subject already exists or invalid data']);
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
    
    if (!isset($data['name']) || empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        exit;
    }
    
    $name = trim($data['name']);
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
    $description = $data['description'] ?? '';
    $icon = $data['icon'] ?? 'fa-book';
    $color = $data['color'] ?? '#0d6efd';
    
    try {
        $stmt = $pdo->prepare('UPDATE subjects SET name = ?, slug = ?, description = ?, icon = ?, color = ? WHERE id = ?');
        $stmt->execute([$name, $slug, $description, $icon, $color, $id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'Subject updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Subject not found or no changes made']);
        }
    } catch (PDOException $e) {
        http_response_code(409);
        echo json_encode(['error' => 'Subject slug already exists']);
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
    
    $stmt = $pdo->prepare('DELETE FROM subjects WHERE id = ?');
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'Subject deleted successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Subject not found']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);