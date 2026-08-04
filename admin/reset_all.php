<?php
// admin/reset_all.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireAdmin();

$page_title = 'Reset Database';
include '../includes/header.php';

$tables = [
    'users' => 'Người dùng',
    'subjects' => 'Môn học',
    'questions' => 'Câu hỏi trắc nghiệm',
    'multiselect_questions' => 'Câu hỏi chọn nhiều',
    'matching_questions' => 'Câu hỏi nối ô',
    'tf_questions' => 'Câu hỏi Đúng/Sai',
    'exams' => 'Đề thi',
    'rooms' => 'Phòng thi',
    'results' => 'Kết quả'
];

$results = [];
foreach ($tables as $table => $label) {
    $check = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
    if ($check) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        if ($count == 0) {
            $results[$table] = ['label' => $label, 'status' => '✅ Đã reset về 1', 'class' => 'success', 'count' => 0];
        } else {
            $results[$table] = ['label' => $label, 'status' => "⚠️ Còn $count dòng", 'class' => 'warning', 'count' => $count];
        }
    } else {
        $results[$table] = ['label' => $label, 'status' => '❌ Chưa có bảng', 'class' => 'secondary', 'count' => 0];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_all'])) {
    verify_csrf();
    foreach ($tables as $table => $label) {
        $check = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($check) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            if ($count == 0) {
                $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
            }
        }
    }
    setFlash('success', 'Đã reset tất cả bảng trống!');
    redirect('reset_all.php');
}
?>
<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <div>
                <h4>Reset Database</h4>
                <p class="text-muted">Quản lý ID tự động</p>
            </div>
            <span class="badge bg-primary">Admin</span>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Kết quả kiểm tra</span>
                <form method="POST" class="d-inline" onsubmit="return confirm('Reset tất cả ID?')">
                    <button type="submit" name="reset_all" value="1" class="btn btn-primary btn-sm">
                        <i class="bi bi-arrow-repeat"></i> Reset tất cả bảng trống
                    </button>
                </form>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Bảng</th>
                            <th>Mô tả</th>
                            <th>Số dòng</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $table => $info): ?>
                            <tr>
                                <td><code><?= e($table) ?></code></td>
                                <td><?= e($info['label']) ?></td>
                                <td><?= number_format($info['count']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $info['class'] ?>">
                                        <?= $info['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i> Chỉ reset bảng không có dữ liệu
                </small>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>