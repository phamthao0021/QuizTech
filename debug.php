<?php
// debug.php - Kiểm tra toàn bộ hệ thống
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 Debug QuizTech</h1>";

// 1. Kiểm tra PHP
echo "<h3>1. PHP Version: " . phpversion() . "</h3>";

// 2. Kiểm tra extensions
echo "<h3>2. Extensions:</h3>";
$extensions = ['pdo', 'pdo_mysql', 'mysqli', 'session'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext loaded<br>";
    } else {
        echo "❌ $ext NOT loaded<br>";
    }
}

// 3. Kiểm tra thư mục
echo "<h3>3. Thư mục hiện tại: " . __DIR__ . "</h3>";

// 4. Kiểm tra file cấu hình
echo "<h3>4. Kiểm tra file cấu hình:</h3>";
$files = [
    'config/database.php',
    'config/auth.php',
    'index.php',
    'database/schema.sql'
];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file tồn tại<br>";
    } else {
        echo "❌ $file KHÔNG tồn tại<br>";
    }
}

// 5. Kiểm tra database
echo "<h3>5. Kiểm tra database:</h3>";
try {
    require_once 'config/database.php';
    echo "✅ Kết nối database thành công!<br>";
    
    // Kiểm tra bảng
    $tables = ['users', 'subjects', 'exams', 'questions', 'exam_attempts'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "✅ Bảng $table tồn tại ($count records)<br>";
        } else {
            echo "❌ Bảng $table KHÔNG tồn tại<br>";
        }
    }
} catch(Exception $e) {
    echo "❌ Lỗi database: " . $e->getMessage() . "<br>";
}

// 6. Kiểm tra session
echo "<h3>6. Kiểm tra session:</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";

// 7. Kiểm tra quyền ghi
echo "<h3>7. Kiểm tra quyền ghi:</h3>";
$dirs = ['uploads', 'sessions', 'tmp'];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
        echo "✅ Tạo thư mục $dir<br>";
    }
    if (is_writable($dir)) {
        echo "✅ $dir writable<br>";
    } else {
        echo "❌ $dir NOT writable<br>";
    }
}

echo "<h3>✅ Debug hoàn tất!</h3>";
echo "<p>Nếu tất cả đều ✅, hãy thử truy cập <a href='index.php'>index.php</a></p>";
echo "<p>Nếu có ❌, hãy sửa theo hướng dẫn bên dưới.</p>";
?>