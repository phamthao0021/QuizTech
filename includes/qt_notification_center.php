<?php
/**
 * Admin/Teacher notification composer and history.
 * Requires role guard in wrapper.
 */

require_once __DIR__ . '/qt_notifications.php';

$actor = qt_notice_user();

$qtIsAdmin = in_array(
    $actor['role'],
    ['admin', 'admin_test'],
    true
);

$qtIsTeacher = $actor['role'] === 'teacher';

if (!$qtIsAdmin && !$qtIsTeacher) {
    http_response_code(403);
    exit('Không có quyền truy cập');
}

$qtFlash = '';
$qtError = '';

if (empty($_SESSION['qt_notice_form_nonce'])) {
    $_SESSION['qt_notice_form_nonce'] = bin2hex(random_bytes(16));
}

$requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET';

if ($requestMethod === 'POST') {
    try {
        if (!hash_equals(
            (string) $_SESSION['qt_notice_form_nonce'],
            (string) ($_POST['nonce'] ?? '')
        )) {
            throw new InvalidArgumentException(
                'Biểu mẫu đã gửi hoặc đã hết hạn. Vui lòng tải lại.'
            );
        }
        $expected = (string) ($_SESSION['csrf_token'] ?? '');
        $actual = (string) ($_POST['csrf_token'] ?? '');

        if (
            $expected === ''
            || !hash_equals($expected, $actual)
        ) {
            throw new InvalidArgumentException(
                'CSRF không hợp lệ. Tải lại trang.'
            );
        }

        $role = $qtIsAdmin
            ? (string) ($_POST['target_role'] ?? 'student')
            : 'student';

        $targetId = trim(
            (string) ($_POST['target_user_id'] ?? '')
        );

        $uid = $targetId !== ''
            ? (int) $targetId
            : null;

        if ($uid !== null && $uid < 1) {
            throw new InvalidArgumentException(
                'ID người nhận không hợp lệ.'
            );
        }

        if ($qtIsTeacher) {
            if (!$uid) {
                throw new InvalidArgumentException(
                    'Giảng viên phải chọn ID sinh viên cụ thể, không được gửi cho toàn hệ thống.'
                );
            }

            // Verify teacher/student relationship via completed exam,
            // not client claims.
            $check = $pdo->prepare("
                SELECT 1
                FROM users u
                JOIN results r
                    ON r.user_id = u.id
                JOIN exams e
                    ON e.id = r.exam_id
                WHERE u.id = ?
                  AND u.role = 'student'
                  AND e.teacher_id = ?
                LIMIT 1
            ");

            $check->execute([
                $uid,
                $actor['id']
            ]);

            if (!$check->fetchColumn()) {
                throw new InvalidArgumentException(
                    'Chỉ gửi cho sinh viên đã làm đề thi của giảng viên này.'
                );
            }
        } elseif ($uid !== null) {
            $stmt = $pdo->prepare("
                SELECT role
                FROM users
                WHERE id = ?
                  AND deleted_at IS NULL
                LIMIT 1
            ");

            $stmt->execute([$uid]);

            $recipientRole = $stmt->fetchColumn();

            if (!$recipientRole) {
                throw new InvalidArgumentException(
                    'Không tìm thấy người nhận.'
                );
            }

            if (
                $role !== 'all'
                && normalizeRole($recipientRole) !== $role
                && !(
                    $role === 'admin'
                    && normalizeRole($recipientRole) === 'admin_test'
                )
            ) {
                throw new InvalidArgumentException(
                    'Nhóm người nhận không khớp vai trò của tài khoản.'
                );
            }
        }

        qt_notice_publish(
            $pdo,
            $actor['id'],
            (string) ($_POST['title'] ?? ''),
            (string) ($_POST['body'] ?? ''),
            $role,
            $uid,
            (string) ($_POST['action_url'] ?? '')
        );

        $_SESSION['qt_notice_form_nonce'] = bin2hex(
            random_bytes(16)
        );

        $qtFlash = 'Gửi thông báo thành công.';

    } catch (InvalidArgumentException $e) {
        $qtError = $e->getMessage();

    } catch (PDOException $e) {
        error_log(
            'qt_notification_center: ' . $e->getMessage()
        );

        $qtError =
            'Không ghi được thông báo. Cần chạy migration hoặc kiểm tra MySQL.';
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            title,
            target_role,
            target_user_id,
            created_at
        FROM qt_notifications
        WHERE created_by = ?
        ORDER BY id DESC
        LIMIT 25
    ");

    $stmt->execute([$actor['id']]);

    $created = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $created = [];

    $qtError =
        'Chưa có bảng thông báo. Chạy migration V39 trước khi sử dụng.';
}
?>

<div class="container-fluid qt-notice-page py-3 py-lg-4">

    <!-- HEADER -->

    <section class="qt-notice-hero">

        <div class="qt-notice-hero-icon">
            <i class="bi bi-bell-fill"></i>
        </div>

        <div class="qt-notice-hero-copy">

            <span class="qt-notice-eyebrow">
                <?= $qtIsAdmin ? 'QUIZTECH · ADMIN' : 'QUIZTECH · TEACHER' ?>
            </span>

            <h1>Trung tâm thông báo</h1>

            <p>
                <?= $qtIsAdmin
                    ? 'Soạn và gửi thông báo theo phạm vi quyền quản trị; theo dõi lịch sử gửi tại một nơi.'
                    : 'Soạn và gửi thông báo đến sinh viên đã tham gia bài thi của bạn; theo dõi lịch sử gửi tại một nơi.' ?>
            </p>

        </div>

        <span class="qt-notice-hero-badge">
            <i class="bi bi-shield-check"></i>
            <?= $qtIsAdmin ? 'Quyền Quản trị' : 'Quyền Giảng viên' ?>
        </span>

    </section>

    <!-- THỐNG KÊ -->

    <div class="qt-notice-metrics">

        <div class="qt-notice-metric">

            <span class="qt-notice-metric-icon violet">
                <i class="bi bi-send-check"></i>
            </span>

            <div>
                <small>Thông báo gần đây</small>

                <strong><?= count($created) ?></strong>

                <span>Tối đa 25 thông báo</span>
            </div>

        </div>

        <div class="qt-notice-metric">

            <span class="qt-notice-metric-icon blue">
                <i class="bi bi-person-check"></i>
            </span>

            <div>
                <small>Đối tượng gửi</small>

                <strong>
                    <?= $qtIsTeacher ? 'Sinh viên' : 'Theo vai trò' ?>
                </strong>

                <span>
                    <?= $qtIsTeacher
                        ? 'Sinh viên đã làm bài của bạn'
                        : 'Theo quyền quản trị'
                    ?>
                </span>
            </div>

        </div>

    </div>

    <!-- THÔNG BÁO LỖI / THÀNH CÔNG -->

    <?php if ($qtError): ?>

        <div
            class="alert alert-danger qt-notice-alert"
            role="alert"
        >
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= e($qtError) ?>
        </div>

    <?php endif; ?>

    <?php if ($qtFlash): ?>

        <div
            class="alert alert-success qt-notice-alert"
            role="status"
        >
            <i class="bi bi-check-circle me-2"></i>
            <?= e($qtFlash) ?>
        </div>

    <?php endif; ?>

    <div class="row g-4 align-items-start">

        <!-- =========================================
             FORM SOẠN THÔNG BÁO
        ========================================== -->

        <div class="col-12 col-xl-6">

            <section class="qt-notice-card">

                <header class="qt-notice-card-head">

                    <div class="qt-notice-head-icon">
                        <i class="bi bi-pencil-square"></i>
                    </div>

                    <div>
                        <h2>Soạn thông báo</h2>
                        <p>Nhập đầy đủ thông tin trước khi gửi</p>
                    </div>

                </header>

                <form
                    method="post"
                    autocomplete="off"
                    class="qt-notice-form"
                >

                    <?php csrf_field(); ?>

                    <input
                        type="hidden"
                        name="nonce"
                        value="<?= e($_SESSION['qt_notice_form_nonce']) ?>"
                    >

                    <!-- TIÊU ĐỀ -->

                    <div class="mb-3">

                        <label class="form-label" for="qt-title">
                            Tiêu đề
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            required
                            maxlength="180"
                            class="form-control"
                            id="qt-title"
                            name="title"
                            value="<?= e($_POST['title'] ?? '') ?>"
                            placeholder="Ví dụ: Lịch thi giữa kỳ môn Công nghệ phần mềm"
                        >

                        <small class="qt-notice-hint">
                            Tối đa 180 ký tự
                        </small>

                    </div>

                    <!-- NỘI DUNG -->

                    <div class="mb-3">

                        <label class="form-label" for="qt-content">
                            Nội dung thông báo
                            <span class="text-danger">*</span>
                        </label>

                        <textarea
                            required
                            minlength="5"
                            maxlength="3000"
                            name="body"
                            id="qt-content"
                            class="form-control"
                            rows="6"
                            placeholder="Nhập nội dung muốn gửi đến sinh viên..."
                        ><?= e($_POST['body'] ?? '') ?></textarea>

                        <div class="qt-notice-hint text-end">
                            <span id="qt-notice-length">0</span>/3000 ký tự
                        </div>

                    </div>

                    <!-- NHÓM NGƯỜI NHẬN: ADMIN -->

                    <?php if ($qtIsAdmin): ?>

                        <div class="mb-3">

                            <label class="form-label" for="qt-role">
                                Nhóm người nhận
                            </label>

                            <select
                                name="target_role"
                                id="qt-role"
                                class="form-select"
                            >
                                <option value="student">Sinh viên</option>
                                <option value="teacher">Giảng viên</option>
                                <option value="admin">Quản trị viên</option>
                                <option value="all">Tất cả vai trò</option>
                            </select>

                        </div>

                    <?php endif; ?>

                    <!-- ID NGƯỜI NHẬN -->

                    <div class="mb-3">

                        <label class="form-label" for="qt-to">

                            ID người nhận

                            <?= $qtIsTeacher
                                ? '<span class="text-danger">*</span>'
                                : '<span class="qt-notice-optional">(không bắt buộc)</span>'
                            ?>

                        </label>

                        <input
                            <?= $qtIsTeacher ? 'required' : '' ?>
                            type="number"
                            min="1"
                            name="target_user_id"
                            id="qt-to"
                            class="form-control"
                            value="<?= e($_POST['target_user_id'] ?? '') ?>"
                            placeholder="<?= $qtIsTeacher
                                ? 'Nhập ID sinh viên đã làm bài của bạn'
                                : 'Để trống để gửi theo nhóm'
                            ?>"
                        >

                        <small class="qt-notice-hint">
                            <?= $qtIsTeacher
                                ? 'Chỉ có thể gửi cho sinh viên đã làm đề thi do bạn tạo.'
                                : 'Nhập ID nếu muốn gửi cho một người cụ thể.'
                            ?>
                        </small>

                    </div>

                    <!-- ĐƯỜNG DẪN -->

                    <div class="mb-4">

                        <label class="form-label" for="qt-url">

                            Đường dẫn chi tiết

                            <span class="qt-notice-optional">
                                (không bắt buộc)
                            </span>

                        </label>

                        <input
                            maxlength="400"
                            class="form-control"
                            name="action_url"
                            id="qt-url"
                            value="<?= e($_POST['action_url'] ?? '') ?>"
                            placeholder="student/exams.php"
                        >

                        <small class="qt-notice-hint">
                            Chỉ chấp nhận đường dẫn nội bộ QuizTech,
                            ví dụ student/exams.php
                        </small>

                    </div>

                    <!-- FOOTER -->

                    <div class="qt-notice-form-footer">

                        <div class="qt-notice-security">

                            <i class="bi bi-shield-lock"></i>

                            Thông báo được kiểm tra quyền trước khi gửi.

                        </div>

                        <button
                            class="qt-notice-submit"
                            type="submit"
                        >
                            <i class="bi bi-send-fill"></i>
                            Gửi thông báo
                        </button>

                    </div>

                </form>

            </section>

        </div>

        <!-- =========================================
             LỊCH SỬ THÔNG BÁO
        ========================================== -->

        <div class="col-12 col-xl-6">

            <section class="qt-notice-card">

                <header class="qt-notice-card-head">

                    <div class="qt-notice-head-icon blue">
                        <i class="bi bi-clock-history"></i>
                    </div>

                    <div>
                        <h2>Lịch sử đã gửi</h2>
                        <p>25 thông báo mới nhất của bạn</p>
                    </div>

                    <span class="qt-notice-count">
                        <?= count($created) ?>
                    </span>

                </header>

                <div class="qt-notice-history">

                    <?php if (!$created): ?>

                        <div class="qt-notice-empty">

                            <i class="bi bi-inbox"></i>

                            <h3>Chưa có thông báo</h3>

                            <p>
                                Thông báo bạn gửi sẽ xuất hiện tại đây.
                            </p>

                        </div>

                    <?php else: ?>

                        <?php foreach ($created as $c): ?>

                            <article class="qt-notice-item">

                                <span class="qt-notice-item-icon">
                                    <i class="bi bi-envelope-check"></i>
                                </span>

                                <div class="qt-notice-item-body">

                                    <h3>
                                        <?= e($c['title']) ?>
                                    </h3>

                                    <div class="qt-notice-item-meta">

                                        <span>
                                            <i class="bi bi-hash"></i>
                                            <?= (int) $c['id'] ?>
                                        </span>

                                        <span>
                                            <i class="bi bi-people"></i>

                                            <?= e($c['target_role']) ?>

                                            <?= $c['target_user_id']
                                                ? ' · User #' . (int) $c['target_user_id']
                                                : ''
                                            ?>
                                        </span>

                                        <time>
                                            <i class="bi bi-calendar3"></i>
                                            <?= e($c['created_at']) ?>
                                        </time>

                                    </div>

                                </div>

                                <span class="qt-notice-sent-tag">
                                    Đã gửi
                                </span>

                            </article>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('qt-content');
    const counter = document.getElementById('qt-notice-length');

    if (textarea && counter) {
        const update = () => {
            counter.textContent = textarea.value.length;
        };

        textarea.addEventListener('input', update);
        update();
    }
});
</script>