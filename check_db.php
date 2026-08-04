<?php
// check_db.php - Kiểm tra database
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Kiểm tra Database</h1>";

try {
    // Kết nối
    $pdo = new PDO("mysql:host=localhost;dbname=quiztech", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ Kết nối database thành công!</p>";
    
    // Kiểm tra bảng
    $tables = ['users', 'subjects', 'exams', 'questions', 'exam_attempts'];
    echo "<h3>Kiểm tra bảng:</h3>";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<p>✅ Bảng <strong>$table</strong> tồn tại - <strong>$count</strong> records</p>";
        } else {
            echo "<p style='color:red'>❌ Bảng <strong>$table</strong> KHÔNG tồn tại</p>";
        }
    }
    
    // Hiển thị dữ liệu
    echo "<h3>Dữ liệu mẫu:</h3>";
    $subjects = $pdo->query("SELECT * FROM subjects")->fetchAll();
    if ($subjects) {
        echo "<ul>";
        foreach ($subjects as $subject) {
            echo "<li>" . htmlspecialchars($subject['name']) . ": " . htmlspecialchars($subject['description']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:orange'>⚠️ Không có dữ liệu môn học. Vui lòng import database!</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Lỗi: " . $e->getMessage() . "</p>";
    echo "<p>Hãy kiểm tra:</p>";
    echo "<ul>";
    echo "<li>Database đã tạo chưa? (quiztech)</li>";
    echo "<li>MySQL đang chạy?</li>";
    echo "<li>Tên đăng nhập và mật khẩu đúng?</li>";
    echo "</ul>";
}
?>