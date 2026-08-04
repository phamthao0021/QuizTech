<?php
// teacher/rooms.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireTeacher();

// -------------------------------------------------------------
// XỬ LÝ THÊM PHÒNG THI MỚI (CÓ TẢI ẢNH BÌA/AVATAR GÓC TRÁI)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf();

    $code        = strtoupper(trim($_POST['code'] ?? ''));
    $name        = trim($_POST['name'] ?? '');
    $exam_id     = (int)($_POST['exam_id'] ?? 0);
    $max_players = (int)($_POST['max_players'] ?? 10);
    $image_path  = '';

    if (empty($code) || empty($name)) {
        setFlash('danger', 'Vui lòng nhập đầy đủ Mã phòng và Tên phòng thi!');
    } elseif ($max_players < 1) {
        setFlash('danger', 'Số lượng người tham gia phải lớn hơn 0!');
    } else {
        // Xử lý upload ảnh phòng thi (Góc trái)
        if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['room_image']['tmp_name'];
            $fileName    = $_FILES['room_image']['name'];
            $fileSize    = $_FILES['room_image']['size'];
            $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($fileExt, $allowedExtensions) && $fileSize <= 2 * 1024 * 1024) {
                $uploadDir = '../uploads/rooms/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $newFileName = 'room_' . time() . '_' . mt_rand(1000, 9999) . '.' . $fileExt;
                if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                    $image_path = 'uploads/rooms/' . $newFileName;
                }
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO rooms (code, name, exam_id, max_players, image, status, created_at) VALUES (?, ?, ?, ?, ?, 'waiting', NOW())");
            $stmt->execute([$code, $name, $exam_id, $max_players, $image_path]);

            setFlash('success', 'Đã tạo phòng thi mới thành công!');
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi CSDL: Mã phòng thi có thể đã tồn tại!');
        }
        redirect('rooms.php');
    }
}

// -------------------------------------------------------------
// XỬ LÝ XÓA PHÒNG THI
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$id]);

            resetAutoIncrement($pdo, 'rooms');
            setFlash('success', 'Đã xóa phòng thi thành công!');
        } catch (PDOException $e) {
            setFlash('danger', 'Lỗi: Không thể xóa phòng thi!');
        }
    }
    redirect('rooms.php');
}

$rooms = getRooms();
$exams = getExams();

$page_title = 'Quản lý phòng thi';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="mb-1">Quản lý phòng thi</h4>
                <p class="text-muted mb-0">Tạo phòng thi trực tuyến và điều phối sinh viên tham gia</p>
            </div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-door-open-fill me-1"></i> Mở phòng thi mới
            </button>
        </div>

        <!-- Bảng danh sách phòng thi -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 250px;">Phòng thi</th>
                                <th>Mã phòng</th>
                                <th>Đề thi liên kết</th>
                                <th class="text-center">Số người tối đa</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-end pe-3">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rooms)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Chưa có phòng thi nào được mở.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rooms as $r): ?>
                                    <?php 
                                        $roomImg = !empty($r['image']) && file_exists('../' . $r['image']) 
                                            ? '../' . e($r['image']) 
                                            : 'https://via.placeholder.com/80?text=Room';
                                    ?>
                                    <tr>
                                        <!-- Cột thông tin phòng + Ảnh góc trái -->
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= $roomImg ?>" class="rounded border shadow-sm flex-shrink-0" style="width: 48px; height: 48px; object-fit: cover;" alt="Room Image">
                                                <div>
                                                    <strong class="d-block text-dark"><?= e($r['name']) ?></strong>
                                                    <small class="text-muted">ID: #<?= $r['id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark-subtle text-dark border px-2 py-1 font-monospace">
                                                <?= e($r['code']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $exam_name = '';
                                            foreach ($exams as $e) {
                                                if ($e['id'] == $r['exam_id']) {
                                                    $exam_name = $e['title'];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <span class="text-secondary fw-semibold">
                                                <?= e($exam_name ?: 'Chưa gán đề thi') ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">
                                            <i class="bi bi-people me-1 text-muted"></i><?= $r['max_players'] ?? 10 ?>
                                        </td>
                                        <td class="text-center">
                                            <?= status_badge($r['status'] ?? 'waiting') ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng thi này?')">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa phòng thi">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mở Phòng Thi (Tích hợp Upload Ảnh Góc Trái) -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" enctype="multipart/form-data" action="rooms.php">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary">Mở phòng thi mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-4">
                        <!-- Ô Tải Ảnh Góc Trái -->
                        <div class="col-12 col-md-4 text-center border-end-md">
                            <label class="form-label fw-semibold d-block">Ảnh đại diện phòng</label>
                            <div class="position-relative d-inline-block mb-2">
                                <img id="roomImgPreview" src="https://via.placeholder.com/120?text=Upload+Image" class="rounded border shadow-sm" style="width: 120px; height: 120px; object-fit: cover;" alt="Preview">
                                <label for="roomImageInput" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 shadow" style="cursor: pointer; width: 36px; height: 36px;" title="Chọn ảnh">
                                    <i class="bi bi-camera-fill"></i>
                                </label>
                            </div>
                            <input type="file" id="roomImageInput" name="room_image" class="d-none" accept="image/*" onchange="previewRoomImg(this);">
                            <div class="form-text small">Tải lên logo/hình biểu tượng phòng thi (Tùy chọn)</div>
                        </div>

                        <!-- Cột Form Nhập Liệu Bên Phải -->
                        <div class="col-12 col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mã phòng thi <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control text-uppercase" placeholder="VD: PT-101" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tên phòng thi <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="VD: Phòng thi Lập trình Web - Nhóm 1" required>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-7">
                                    <label class="form-label fw-semibold">Chọn đề thi</label>
                                    <select name="exam_id" class="form-select">
                                        <option value="0">-- Chọn đề thi --</option>
                                        <?php foreach ($exams as $e): ?>
                                            <option value="<?= $e['id'] ?>"><?= e($e['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-5">
                                    <label class="form-label fw-semibold">Số người tối đa</label>
                                    <input type="number" name="max_players" class="form-control" value="10" min="1" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary px-4">Tạo phòng thi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewRoomImg(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('roomImgPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include '../includes/footer.php'; ?>