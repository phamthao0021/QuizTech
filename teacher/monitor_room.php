<?php
// teacher/monitor_room.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
requireTeacher();

$teacherId = (int)((function_exists('currentUser') ? (currentUser()['id'] ?? null) : null) ?? ($_SESSION['user_id'] ?? null) ?? ($_SESSION['user']['id'] ?? 0));
$room_id = (int)($_GET['id'] ?? 0);

if ($room_id <= 0) {
    setFlash('danger', 'Mã phòng không hợp lệ!');
    redirect('rooms.php');
}

// Kiểm tra quyền sở hữu phòng thi
$stmtRoom = $pdo->prepare("
    SELECT r.*, e.title as exam_title, e.total_questions 
    FROM exam_rooms r 
    LEFT JOIN exams e ON r.exam_id = e.id 
    WHERE r.id = ? AND r.teacher_id = ?
");
$stmtRoom->execute([$room_id, $teacherId]);
$room = $stmtRoom->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    setFlash('danger', 'Phòng thi không tồn tại hoặc bạn không có quyền truy cập!');
    redirect('rooms.php');
}

// Xử lý đóng phòng (đổi status)
if ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'close_room') {
    verify_csrf();
    try {
        $stmtClose = $pdo->prepare("UPDATE exam_rooms SET status = 'finished' WHERE id = ?");
        $stmtClose->execute([$room_id]);
        setFlash('success', 'Đã kết thúc phòng thi!');
    } catch (Exception $e) {
        setFlash('danger', 'Lỗi: Không thể đóng phòng.');
    }
    redirect("monitor_room.php?id=$room_id");
}

// Nếu request AJAX yêu cầu lấy dữ liệu realtime
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $stmtAtt = $pdo->prepare("
        SELECT a.id, u.username, u.fullname, a.started_at, a.submitted_at, 
               a.status, a.score, a.correct_answers, a.total_questions, a.percentage
        FROM exam_attempts a
        JOIN users u ON a.student_id = u.id
        WHERE a.room_id = ?
        ORDER BY a.started_at DESC
    ");
    $stmtAtt->execute([$room_id]);
    $attempts = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($attempts);
    exit;
}

$page_title = 'Giám sát phòng thi';
include '../includes/header_teacher.php';
?>

<main class="p-4 bg-light flex-grow-1 qt-t-page qt-t-monitor-room">
<section class="qt-t-hero" aria-label="Giới thiệu trang"><div class="qt-t-hero-icon"><i class="bi bi-display"></i></div><div class="qt-t-hero-copy"><div class="qt-t-eyebrow">QUIZTECH · TEACHER</div><h1>Giám sát phòng thi</h1><p>Theo dõi tình trạng và tiến độ làm bài</p></div><a class="qt-t-hero-home" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a></section>
    <div class="topbar d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1">
                <a href="rooms.php" class="text-decoration-none text-muted me-2"><i class="bi bi-arrow-left"></i></a>
                Giám sát phòng thi: <span class="text-primary"><?= e($room['room_name']) ?></span>
            </h4>
            <p class="text-muted mb-0">Mã phòng: <strong><?= e($room['room_code']) ?></strong> | Đề thi: <?= e($room['exam_title']) ?></p>
        </div>
        <div>
            <span class="badge bg-<?= $room['status'] === 'waiting' ? 'warning text-dark' : ($room['status'] === 'running' ? 'success' : 'secondary') ?> fs-6 p-2 me-2">
                Trạng thái: <?= strtoupper($room['status']) ?>
            </span>
            <?php if ($room['status'] !== 'finished'): ?>
                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn đóng phòng thi này? Mọi học sinh đang làm bài có thể bị gián đoạn.')">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="close_room">
                    <button type="submit" class="btn btn-danger shadow-sm">
                        <i class="bi bi-stop-circle me-1"></i> Kết thúc ngay
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card shadow-sm border-0 bg-primary text-white h-100">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-white bg-opacity-25 p-3 rounded-circle me-3">
                        <i class="bi bi-people-fill fs-3"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-white-50">Đang làm bài</h6>
                        <h3 class="mb-0 fw-bold" id="statDoing">0</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card shadow-sm border-0 bg-success text-white h-100">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="bg-white bg-opacity-25 p-3 rounded-circle me-3">
                        <i class="bi bi-check-circle-fill fs-3"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-white-50">Đã nộp bài</h6>
                        <h3 class="mb-0 fw-bold" id="statSubmitted">0</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách thí sinh -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-list-ol text-primary me-2"></i>Danh sách thí sinh tham gia</h5>
            <div class="spinner-border spinner-border-sm text-primary" id="refreshSpinner" role="status" style="display: none;">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <div class="qt-shared-toolbar" data-qt-table-toolbar><label class="qt-shared-search"><i class="bi bi-search"></i><input type="search" placeholder="Tìm kiếm trong danh sách..." aria-label="Tìm kiếm dữ liệu"></label><select class="qt-shared-sort" aria-label="Sắp xếp"><option value="original">Mặc định</option><option value="az">A–Z</option><option value="za">Z–A</option></select><button type="button" class="qt-shared-refresh"><i class="bi bi-arrow-clockwise"></i> Làm mới</button></div><table class="table table-striped table-hover align-middle mb-0 qt-enhanced-table" id="studentsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tài khoản</th>
                            <th>Họ tên</th>
                            <th>Bắt đầu lúc</th>
                            <th>Trạng thái</th>
                            <th class="text-center">Số câu đúng</th>
                            <th class="text-center">Điểm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Đang tải dữ liệu...</td>
                        </tr>
                    </tbody>
                </table><div class="qt-shared-pagination"><span class="qt-shared-count"></span><label>Hiển thị <select class="qt-shared-pagesize"><option>5</option><option selected>10</option><option>20</option><option>50</option></select></label><nav class="qt-shared-pages" aria-label="Phân trang"></nav></div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const roomId = <?= $room_id ?>;
    const tbody = document.querySelector("#studentsTable tbody");
    const statDoing = document.getElementById("statDoing");
    const statSubmitted = document.getElementById("statSubmitted");
    const spinner = document.getElementById("refreshSpinner");
    
    function fetchRealtimeData() {
        spinner.style.display = 'inline-block';
        fetch(`monitor_room.php?id=${roomId}&ajax=1`)
            .then(res => res.json())
            .then(data => {
                spinner.style.display = 'none';
                
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Chưa có thí sinh nào tham gia.</td></tr>';
                    statDoing.textContent = '0';
                    statSubmitted.textContent = '0';
                    return;
                }
                
                let doingCount = 0;
                let submittedCount = 0;
                let html = '';
                
                data.forEach(student => {
                    let statusBadge = '';
                    if (student.status === 'doing') {
                        statusBadge = '<span class="badge bg-warning text-dark"><i class="spinner-grow spinner-grow-sm me-1" style="width: 10px; height: 10px;"></i> Đang thi</span>';
                        doingCount++;
                    } else if (student.status === 'submitted' || student.status === 'timeout') {
                        statusBadge = '<span class="badge bg-success">Đã nộp</span>';
                        submittedCount++;
                    } else {
                        statusBadge = `<span class="badge bg-secondary">${student.status}</span>`;
                    }
                    
                    const score = (student.status === 'doing') ? '-' : parseFloat(student.score).toFixed(2);
                    const correct = (student.status === 'doing') ? '-' : `${student.correct_answers}/${student.total_questions}`;
                    const fullname = student.fullname ? student.fullname : student.username;
                    
                    html += `
                        <tr>
                            <td class="ps-3 fw-semibold">@${student.username}</td>
                            <td>${fullname}</td>
                            <td>${student.started_at}</td>
                            <td>${statusBadge}</td>
                            <td class="text-center">${correct}</td>
                            <td class="text-center fw-bold text-primary">${score}</td>
                        </tr>
                    `;
                });
                
                tbody.innerHTML = html;
                statDoing.textContent = doingCount;
                statSubmitted.textContent = submittedCount;
            })
            .catch(err => {
                console.error("Error fetching realtime data:", err);
                spinner.style.display = 'none';
            });
    }

    // Tải lần đầu
    fetchRealtimeData();
    
    // Auto refresh mỗi 5 giây
    setInterval(fetchRealtimeData, 5000);
});
</script>

<?php 
echo '</div></div>';
include '../includes/footer.php'; 
?>

<script src="../assets/js/qt-teacher-pages.js?v=20261025" defer></script>
