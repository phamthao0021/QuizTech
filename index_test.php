<?php
// index_test.php - Phiên bản test đơn giản
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>QuizTech đang chạy!</h1>";

try {
    require_once 'config/database.php';
    echo "<p>Kết nối database thành công!</p>";
    
    // Test query
    $subjects = $pdo->query("SELECT * FROM subjects LIMIT 5")->fetchAll();
    echo "<h3>Danh sách môn học:</h3>";
    echo "<ul>";
    foreach ($subjects as $subject) {
        echo "<li>" . htmlspecialchars($subject['name']) . "</li>";
    }
    echo "</ul>";
    
} catch(Exception $e) {
    echo "<p style='color:red'>Lỗi: " . $e->getMessage() . "</p>";
}
?>