<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$page_title = 'Hồ sơ';
$pdo = db();
$user_id = $_SESSION['user_id'];

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        flash_set('danger', 'Phiên không hợp lệ.');
        redirect('profile.php');
    }
    
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($fullname) || empty($email)) {
        flash_set('danger', 'Vui lòng điền đầy đủ thông tin.');
    } else {
        $stmt = $pdo->prepare('UPDATE users SET fullname = ?, email = ? WHERE id = ?');
        $stmt->execute([$fullname, $email, $user_id]);
        
        $_SESSION['fullname'] = $fullname;
        $_SESSION['email'] = $email;
        
        flash_set('success', 'Đã cập nhật thông tin.');
    }
    redirect('profile.php');
}

$user = current_user();

// Thống kê
$stats = [
    'exams_taken' => $pdo->prepare('SELECT COUNT(*) FROM results WHERE user_id = ?')->execute([$user_id]),
    'avg_score' => $pdo->prepare('SELECT AVG(score) FROM results WHERE user_id = ?')->execute([$user_id]),
    'total_questions' => $pdo->prepare('SELECT SUM(correct_answers) FROM results WHERE user_id = ?')->execute([$user_id]),
];

$exams_taken = $pdo->prepare('SELECT COUNT(*) FROM results WHERE user_id = ?');
$exams_taken->execute([$user_id]);
$exams_taken = $exams_taken->fetchColumn();

$avg_score = $pdo->prepare('SELECT AVG(score) FROM results WHERE user_id = ?');
$avg_score->execute([$user_id]);
$avg_score = round($avg_score->fetchColumn(), 1) ?? 0;

$total_correct = $pdo->prepare('SELECT SUM(correct_answers) FROM results WHERE user_id = ?');
$total_correct->execute([$user_id]);
$total_correct = $total_correct->fetchColumn() ?? 0;

include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <div class="avatar-circle">
                        <i class="fas fa-user fa-4x text-primary"></i>
                    </div>
                    <h5 class="mt-3"><?= e($user['fullname']) ?></h5>
                    <p class="text-muted"><?= e($user['student_code']) ?></p>
                    <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'teacher' ? 'warning' : 'primary') ?>">
                        <?= ucfirst(e($user['role'])) ?>
                    </span>
                </div>
            </div>
            
            <div class="card shadow mt-3">
                <div class="card-body">
                    <h6>Thống kê</h6>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Đã thi:</span>
                        <strong><?= number_format($exams_taken) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Điểm TB:</span>
                        <strong><?= number_format($avg_score, 1) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Câu đúng:</span>
                        <strong><?= number_format($total_correct) ?></strong>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header">
                    <h5><i class="fas fa-edit"></i> Chỉnh sửa hồ sơ</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">MSSV</label>
                            <input type="text" class="form-control" value="<?= e($user['student_code']) ?>" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Họ tên</label>
                            <input type="text" name="fullname" class="form-control" value="<?= e($user['fullname']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Cập nhật
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>