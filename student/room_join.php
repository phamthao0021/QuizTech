<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireRole('student');

$error = '';
$code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['room_code'] ?? ''));
    
    if (empty($code)) {
        $error = 'Vui lòng nhập mã phòng thi!';
    } else {
        $room = db_fetch_one("SELECT * FROM exam_rooms WHERE room_code = ?", [$code]);

        if ($room) {
            // Điều hướng linh hoạt theo trạng thái thực tế của phòng thi
            if ($room['status'] === 'closed') {
                $error = 'Phòng thi này đã đóng hoặc đã kết thúc!';
            } elseif ($room['status'] === 'active') {
                // Nếu phòng đang diễn ra, chuyển thẳng vào trang làm bài
                header("Location: do_exam.php?code=" . urlencode($code));
                exit();
            } else {
                // Nếu phòng đang ở trạng thái chờ (waiting)
                header("Location: waiting-room.php?code=" . urlencode($code));
                exit();
            }
        } else {
            $error = 'Mã phòng thi không tồn tại hoặc đã bị hủy!';
        }
    }
}

$page_title = 'Tham gia phòng thi';
require_once dirname(__DIR__) . '/includes/header_student.php';
?>

<style>
    :root {
        --indigo-primary: #4f46e5;
        --indigo-hover: #4338ca;
        --indigo-soft: #eef2ff;
        --border-color: #e2e8f0;
        --bg-body: #f8fafc;
        --card-radius: 1.25rem;
    }
    
    body { 
        background-color: var(--bg-body); 
        font-family: 'Inter', system-ui, -apple-system, sans-serif; 
        color: #1e293b;
    }

    .join-card {
        background: #ffffff;
        border-radius: var(--card-radius);
        border: 1px solid var(--border-color);
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04);
        transition: all 0.25s ease;
    }

    /* Icon Ring hiệu ứng chớp sáng nhẹ */
    .icon-wrapper {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background-color: var(--indigo-soft);
        color: var(--indigo-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #c7d2fe;
        box-shadow: 0 0 0 6px rgba(79, 70, 229, 0.08);
    }

    /* Input nhập mã dạng Code Field chuyên nghiệp */
    .code-input {
        letter-spacing: 6px;
        font-size: 1.6rem;
        text-transform: uppercase;
        border-radius: 14px;
        border: 2px solid var(--border-color);
        padding: 0.75rem 1rem;
        transition: all 0.2s ease;
        color: #0f172a;
        background-color: #fafafa;
    }

    .code-input:focus {
        border-color: var(--indigo-primary);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
        background-color: #ffffff;
    }

    .code-input::placeholder {
        letter-spacing: 2px;
        font-size: 1.1rem;
        font-weight: 500;
        color: #94a3b8;
    }

    .btn-join {
        background-color: var(--indigo-primary);
        border-color: var(--indigo-primary);
        color: #ffffff;
        transition: all 0.2s ease;
    }

    .btn-join:hover {
        background-color: var(--indigo-hover);
        border-color: var(--indigo-hover);
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.35);
    }

    /* Khung trợ giúp bên dưới */
    .tip-box {
        background-color: var(--indigo-soft);
        border-radius: 1rem;
        border: 1px dashed #c7d2fe;
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            
            <div class="join-card p-4 p-md-5">
                <!-- Header Icon & Tiêu đề -->
                <div class="text-center mb-4">
                    <div class="icon-wrapper mb-3">
                        <i class="bi bi-door-open-fill fs-2"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Tham Gia Phòng Thi</h4>
                    <p class="text-muted small mb-0">Nhập mã truy cập do giảng viên hoặc trưởng phòng cấp</p>
                </div>

                <!-- Thống báo lỗi nếu có -->
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 rounded-3 small mb-4 p-3 d-flex align-items-center bg-danger-subtle text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5 flex-shrink-0"></i>
                        <div class="fw-medium"><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Form nhập mã -->
                <form method="POST">
                    <div class="mb-4 text-center">
                        <label class="form-label small fw-bold text-uppercase text-secondary mb-2" style="letter-spacing: 0.5px;">
                            <i class="bi bi-key-fill me-1 text-primary"></i> Mã Phòng Thi
                        </label>
                        <input type="text" 
                               name="room_code" 
                               id="roomCodeInput"
                               class="form-control form-control-lg text-center fw-bold code-input" 
                               placeholder="EXAM123" 
                               value="<?= e($code) ?>" 
                               required 
                               autocomplete="off" 
                               autofocus
                               oninput="this.value = this.value.toUpperCase()">
                    </div>

                    <button type="submit" class="btn btn-join w-100 btn-lg rounded-pill fw-bold fs-6 py-2.5 shadow-sm mb-3">
                        Vào Phòng Thi <i class="bi bi-arrow-right ms-1"></i>
                    </button>

                    <a href="rooms.php" class="btn btn-link w-100 text-decoration-none text-muted small py-1">
                        <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách phòng
                    </a>
                </form>
            </div>

            <!-- Mẹo hỗ trợ nhỏ ở ngoài thẻ chính -->
            <div class="tip-box p-3 mt-4 text-center text-secondary small">
                <i class="bi bi-info-circle-fill text-primary me-1"></i>
                Mã phòng thường là chuỗi ký tự ngắn (VD: <strong class="text-dark">EXAM123</strong>) được gửi trực tiếp từ giảng viên.
            </div>

        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>