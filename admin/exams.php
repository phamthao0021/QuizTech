<?php
/**
 * QUIZTECH - ADMIN / EXAMS
 * Quản lý đề thi
 *
 * - Tạo đề thủ công
 * - Tạo / Import bộ đề qua create_exam.php
 * - Sửa, xóa đề thi
 * - Xóa hàng loạt
 * - Tìm kiếm, lọc tự động
 * - Phân trang dưới bảng
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (function_exists('requireAdmin')) {
    requireAdmin();
}

$page_title = 'Quản lý đề thi';

$msg = '';
$msg_type = 'success';

function examEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function examStatusValue($value)
{
    return in_array(
        (string) $value,
        ['active', '1'],
        true
    ) ? 'active' : 'draft';
}

function examDbStatus(PDO $pdo, $status)
{
    $active = $status === 'active';

    try {
        $stmt = $pdo->query(
            "SHOW COLUMNS FROM exams LIKE 'status'"
        );

        $column = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($column) {
            $type = strtolower($column['Type']);

            if (
                strpos($type, 'int') !== false ||
                strpos($type, 'bit') !== false
            ) {
                return $active ? 1 : 0;
            }

            if (
                strpos($type, 'enum') !== false &&
                strpos($type, "'1'") !== false
            ) {
                return $active ? '1' : '0';
            }
        }
    } catch (Throwable $e) {
        error_log($e->getMessage());
    }

    return $active ? 'active' : 'draft';
}

function examColumnExists(PDO $pdo, $column)
{
    $allowed = [
        'title',
        'name',
        'subject_id',
        'duration',
        'time_limit',
        'status'
    ];

    if (!in_array($column, $allowed, true)) {
        return false;
    }

    $stmt = $pdo->query(
        "SHOW COLUMNS FROM exams LIKE " .
        $pdo->quote($column)
    );

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function examRedirect($message)
{
    header(
        'Location: exams.php?msg=' .
        rawurlencode($message)
    );

    exit;
}

/* ==================================================
   THÔNG BÁO
================================================== */

$messages = [
    'created' => 'Tạo đề thi thành công!',
    'updated' => 'Cập nhật đề thi thành công!',
    'deleted' => 'Xóa đề thi thành công!'
];

$messageKey = $_GET['msg'] ?? '';

if (
    is_string($messageKey) &&
    isset($messages[$messageKey])
) {
    $msg = $messages[$messageKey];
}

/* ==================================================
   XỬ LÝ POST
================================================== */

$requestMethod = filter_input(
    INPUT_SERVER,
    'REQUEST_METHOD'
) ?: ($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($requestMethod === 'POST') {

    if (function_exists('verify_csrf')) {
        verify_csrf();
    }

    $action = $_POST['action'] ?? '';

    try {

        /* ------------------------------------------
           XÓA HÀNG LOẠT
        ------------------------------------------ */

        if ($action === 'bulk_delete_exams') {

            $rawIds = $_POST['exam_ids'] ?? [];

            if (!is_array($rawIds)) {
                $rawIds = [];
            }

            $ids = array_values(
                array_unique(
                    array_filter(
                        array_map('intval', $rawIds),
                        static function ($id) {
                            return $id > 0;
                        }
                    )
                )
            );

            if (!$ids) {
                throw new RuntimeException(
                    'Vui lòng chọn ít nhất một đề thi.'
                );
            }

            $placeholders = implode(
                ',',
                array_fill(0, count($ids), '?')
            );

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "DELETE FROM exam_questions
                 WHERE exam_id IN ($placeholders)"
            );

            $stmt->execute($ids);

            $stmt = $pdo->prepare(
                "DELETE FROM exams
                 WHERE id IN ($placeholders)"
            );

            $stmt->execute($ids);

            $pdo->commit();

            examRedirect('deleted');
        }

        /* ------------------------------------------
           THÊM / SỬA ĐỀ THI
        ------------------------------------------ */

        if (
            $action === 'create_exam' ||
            $action === 'update_exam'
        ) {

            $examId = (int) ($_POST['exam_id'] ?? 0);

            $title = trim(
                (string) ($_POST['title'] ?? '')
            );

            $subjectId = (int) (
                $_POST['subject_id'] ?? 0
            );

            $duration = (int) (
                $_POST['duration'] ?? 0
            );

            $status = examStatusValue(
                $_POST['status'] ?? 'draft'
            );

            if (
                mb_strlen($title) < 5 ||
                mb_strlen($title) > 255
            ) {
                throw new RuntimeException(
                    'Tên đề thi phải có từ 5 đến 255 ký tự.'
                );
            }

            if ($subjectId <= 0) {
                throw new RuntimeException(
                    'Vui lòng chọn môn học.'
                );
            }

            if ($duration < 1 || $duration > 300) {
                throw new RuntimeException(
                    'Thời gian phải từ 1 đến 300 phút.'
                );
            }

            $subjectCheck = $pdo->prepare(
                'SELECT COUNT(*) FROM subjects WHERE id = ?'
            );

            $subjectCheck->execute([$subjectId]);

            if (!(int) $subjectCheck->fetchColumn()) {
                throw new RuntimeException(
                    'Môn học không tồn tại.'
                );
            }

            $dbStatus = examDbStatus(
                $pdo,
                $status
            );

            if ($action === 'create_exam') {

                $examCode = 'EXAM_' .
                    strtoupper(
                        bin2hex(random_bytes(4))
                    );

                $stmt = $pdo->prepare(
                    "INSERT INTO exams
                     (
                        exam_code,
                        title,
                        subject_id,
                        duration,
                        status,
                        created_at
                     )
                     VALUES (?, ?, ?, ?, ?, NOW())"
                );

                $stmt->execute([
                    $examCode,
                    $title,
                    $subjectId,
                    $duration,
                    $dbStatus
                ]);

                examRedirect('created');
            }

            if ($action === 'update_exam') {

                if ($examId <= 0) {
                    throw new RuntimeException(
                        'ID đề thi không hợp lệ.'
                    );
                }

                $fields = [];
                $params = [];

                if (examColumnExists($pdo, 'title')) {
                    $fields[] = 'title = ?';
                    $params[] = $title;
                } elseif (
                    examColumnExists($pdo, 'name')
                ) {
                    $fields[] = 'name = ?';
                    $params[] = $title;
                }

                if (
                    examColumnExists(
                        $pdo,
                        'subject_id'
                    )
                ) {
                    $fields[] = 'subject_id = ?';
                    $params[] = $subjectId;
                }

                if (
                    examColumnExists(
                        $pdo,
                        'duration'
                    )
                ) {
                    $fields[] = 'duration = ?';
                    $params[] = $duration;
                } elseif (
                    examColumnExists(
                        $pdo,
                        'time_limit'
                    )
                ) {
                    $fields[] = 'time_limit = ?';
                    $params[] = $duration;
                }

                if (
                    examColumnExists(
                        $pdo,
                        'status'
                    )
                ) {
                    $fields[] = 'status = ?';
                    $params[] = $dbStatus;
                }

                if (!$fields) {
                    throw new RuntimeException(
                        'Không tìm thấy cột dữ liệu để cập nhật.'
                    );
                }

                $params[] = $examId;

                $sql = 'UPDATE exams SET ' .
                    implode(', ', $fields) .
                    ' WHERE id = ?';

                $stmt = $pdo->prepare($sql);

                $stmt->execute($params);

                examRedirect('updated');
            }
        }

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'QuizTech exams: ' .
            $e->getMessage()
        );

        $msg = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Không thể thực hiện thao tác. ' .
              'Vui lòng kiểm tra dữ liệu liên quan.';

        $msg_type = 'danger';
    }
}

/* ==================================================
   XÓA MỘT ĐỀ THI
   Giữ tương thích liên kết GET từ bản cũ.
================================================== */

if (
    $requestMethod === 'GET' &&
    ($_GET['action'] ?? '') === 'delete'
) {

    $examId = (int) ($_GET['id'] ?? 0);

    if ($examId > 0) {

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'DELETE FROM exam_questions WHERE exam_id = ?'
            );

            $stmt->execute([$examId]);

            $stmt = $pdo->prepare(
                'DELETE FROM exams WHERE id = ?'
            );

            $stmt->execute([$examId]);

            $pdo->commit();

            examRedirect('deleted');

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $msg = 'Không thể xóa đề thi vì có dữ liệu liên quan.';
            $msg_type = 'danger';
        }
    }
}

/* ==================================================
   LẤY MÔN HỌC
================================================== */

$subjects = [];

try {

    $stmt = $pdo->query(
        'SELECT id, name
         FROM subjects
         ORDER BY name ASC'
    );

    $subjects = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (Throwable $e) {

    error_log($e->getMessage());

    $subjects = [];
}

/* ==================================================
   LẤY DANH SÁCH ĐỀ THI
================================================== */

$subjectMap = [];

foreach ($subjects as $subject) {
    $subjectMap[
        (int) $subject['id']
    ] = $subject['name'];
}

$exams = [];

try {

    $stmt = $pdo->query(
        'SELECT * FROM exams ORDER BY id DESC'
    );

    $rawExams = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

    foreach ($rawExams as $exam) {

        $examId = (int) $exam['id'];

        $subjectId = (int) (
            $exam['subject_id'] ?? 0
        );

        $questionCount = (int) (
            $exam['total_questions'] ?? 0
        );

        if (!isset($exam['total_questions'])) {

            $countStmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM exam_questions
                 WHERE exam_id = ?'
            );

            $countStmt->execute([$examId]);

            $questionCount = (int) (
                $countStmt->fetchColumn()
            );
        }

        $exams[] = [
            'id' => $examId,

            'title' =>
                $exam['title'] ??
                $exam['exam_name'] ??
                $exam['name'] ??
                ('Đề thi #' . $examId),

            'subject_id' => $subjectId,

            'subject_name' =>
                $subjectMap[$subjectId] ??
                'Chưa phân loại',

            'question_count' => $questionCount,

            'duration' => (int) (
                $exam['duration'] ??
                $exam['time_limit'] ??
                45
            ),

            'status' => examStatusValue(
                $exam['status'] ?? 'draft'
            ),

            'created_at' =>
                $exam['created_at'] ??
                $exam['date_created'] ??
                ''
        ];
    }

} catch (Throwable $e) {

    error_log($e->getMessage());

    $msg = 'Không thể tải danh sách đề thi.';
    $msg_type = 'danger';
}

/* ==================================================
   HEADER ADMIN
================================================== */

include '../includes/header_admin.php';

?>

<style>
/* ==================================================
   QUIZTECH ADMIN EXAMS - UI
================================================== */

.exams-page {
    width: 100%;
}

/* HEADER */

.exams-page .exams-header {
    border: 0;
    border-radius: 20px;
    overflow: hidden;
    background: linear-gradient(
        110deg,
        #4f46e5,
        #6d28d9
    );
    box-shadow: 0 10px 25px rgba(79,70,229,.12);
}

.exams-page .exams-header .card-body {
    padding: 26px 28px;
}

.exams-page .exams-header h1 {
    font-size: 25px;
    font-weight: 800;
    margin: 0 0 6px;
}

.exams-page .exams-header p {
    margin: 0;
    font-size: 13px;
    color: rgba(255,255,255,.75);
}

.exams-page .header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.exams-page .header-actions .btn {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 15px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
}

/* THANH TÌM KIẾM */

.exams-page .exam-toolbar {
    background: #fff;
    border: 1px solid #e5e9f3;
    border-radius: 16px;
    padding: 14px;
    margin-bottom: 18px;
    box-shadow: 0 5px 20px rgba(15,23,42,.04);
}

.exams-page .toolbar-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.exams-page .search-wrap {
    display: flex;
    align-items: center;
    flex: 1 1 300px;
    max-width: 430px;
    min-width: 0;
    height: 42px;
    border: 1px solid #dce2ee;
    border-radius: 10px;
    background: #f8fafc;
    overflow: hidden;
}

.exams-page .search-icon {
    width: 43px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-right: 1px solid #e2e8f0;
    color: #64748b;
}

.exams-page .search-input {
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 100%;
    padding: 0 12px;
    border: 0;
    outline: 0;
    background: transparent;
    color: #334155;
    font-size: 13px;
}

.exams-page .search-wrap:focus-within {
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139,92,246,.12);
}

.exams-page .filter-select {
    width: 195px;
    max-width: 100%;
    min-height: 42px;
    padding: 0 36px 0 12px;
    border: 1px solid #dce2ee;
    border-radius: 10px;
    background-color: #f8fafc;
    color: #334155;
    font-size: 13px;
    cursor: pointer;

    appearance: none;
    -webkit-appearance: none;

    background-image: url(
        "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364758b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"
    );

    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px;
}

.exams-page .filter-select:focus {
    outline: 0;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139,92,246,.12);
}

/* BẢNG */

.exams-page .exam-card {
    background: #fff;
    border: 1px solid #e5e9f3;
    border-radius: 18px;
    padding: 16px;
    box-shadow: 0 6px 22px rgba(15,23,42,.04);
}

.exams-page .exam-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.exams-page .exam-table thead th {
    background: #f6f8fc;
    padding: 13px 12px;
    border: 0;
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
}

.exams-page .exam-table tbody td {
    padding: 13px 12px;
    border: 0;
    background: #f8fafc;
    vertical-align: middle;
    font-size: 13px;
}

.exams-page .exam-table tbody tr:hover td {
    background: #f1f5f9;
}

.exams-page .exam-table tbody td:first-child {
    border-radius: 11px 0 0 11px;
}

.exams-page .exam-table tbody td:last-child {
    border-radius: 0 11px 11px 0;
}

.exams-page .exam-table .form-check-input:checked {
    background-color: #6d28d9;
    border-color: #6d28d9;
}

.exams-page .subject-chip {
    display: inline-flex;
    padding: 6px 9px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #475569;
    font-size: 12px;
}

.exams-page .status-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}

.exams-page .action-btn {
    width: 34px;
    height: 34px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    background: #fff;
    box-shadow: 0 2px 7px rgba(15,23,42,.04);
    text-decoration: none;
}

.exams-page .action-btn:hover {
    background: #f1f5f9;
    transform: translateY(-1px);
}

/* XÓA HÀNG LOẠT */

.exams-page .bulk-bar {
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    padding: 12px 15px;
    margin-bottom: 14px;
    border: 1px solid #ddd6fe;
    border-radius: 12px;
    background: #faf8ff;
}

.exams-page .bulk-bar.show {
    display: flex;
}

.exams-page .bulk-count {
    font-size: 14px;
    font-weight: 600;
    color: #334155;
}

.exams-page .bulk-delete-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 37px;
    padding: 7px 14px;
    border: 1px solid #ef4444;
    border-radius: 9px;
    background: #fff;
    color: #ef4444;
    font-size: 13px;
    font-weight: 700;
}

.exams-page .bulk-delete-btn:hover {
    background: #ef4444;
    color: #fff;
}

/* PHÂN TRANG GIỐNG MÔN HỌC */

.exams-page .exam-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e8eaf4;
}

.exams-page .exam-footer-info {
    font-size: 13px;
    color: #475569;
}

.exams-page .exam-footer-controls {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 9px;
    flex-wrap: wrap;
}

.exams-page .exam-footer-controls label {
    font-size: 13px;
    color: #475569;
    white-space: nowrap;
}

.exams-page .page-size-select {
    width: 78px;
    height: 37px;
    padding: 0 10px;
    border: 1px solid #dce2ee;
    border-radius: 9px;
    background: #fff;
    color: #334155;
    font-size: 13px;
}

.exams-page .page-btn {
    min-height: 37px;
    padding: 7px 13px;
    border: 1px solid #c7d2fe;
    border-radius: 9px;
    background: #fff;
    color: #4f46e5;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.exams-page .page-btn:hover:not(:disabled) {
    background: #5b4cf0;
    color: #fff;
}

.exams-page .page-btn:disabled {
    opacity: .4;
    cursor: not-allowed;
}

.exams-page .page-indicator {
    min-width: 42px;
    text-align: center;
    font-size: 13px;
    font-weight: 700;
    color: #334155;
}

/* MODAL */

.exam-modal .modal-content {
    border: 0;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 25px 65px rgba(30,27,75,.2);
}

.exam-modal .modal-header {
    background: linear-gradient(
        110deg,
        #4f46e5,
        #7c3aed
    );
    color: #fff;
    padding: 17px 20px;
    border: 0;
}

.exam-modal .modal-title {
    font-size: 18px;
    font-weight: 800;
}

.exam-modal .modal-body {
    padding: 20px;
    background: #fbfcfe;
}

.exam-modal .modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #edf0f5;
}

.exam-modal .form-control,
.exam-modal .form-select {
    min-height: 43px;
    border: 1px solid #dce2ee;
    border-radius: 10px;
}

.exam-modal .form-control:focus,
.exam-modal .form-select:focus {
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139,92,246,.12);
}

/* RESPONSIVE */

@media (max-width: 991px) {

    .exams-page .search-wrap {
        max-width: none;
    }

    .exams-page .filter-select {
        flex: 1 1 170px;
    }
}

@media (max-width: 767px) {

    .exams-page .exams-header .card-body {
        padding: 22px 18px;
    }

    .exams-page .header-actions {
        width: 100%;
    }

    .exams-page .header-actions .btn {
        flex: 1 1 100%;
    }

    .exams-page .toolbar-row {
        flex-direction: column;
        align-items: stretch;
    }

    .exams-page .search-wrap,
    .exams-page .filter-select {
        width: 100%;
        max-width: none;
        flex: auto;
    }

    .exams-page .exam-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .exams-page .exam-footer-info {
        text-align: center;
    }

    .exams-page .exam-footer-controls {
        justify-content: center;
    }

    .exams-page .bulk-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .exams-page .bulk-delete-btn {
        justify-content: center;
    }
}
/* ========================================
   QUIZTECH - REFRESH BUTTON
======================================== */

.qt-refresh-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    height: 42px;
    padding: 0 16px;

    border: 1px solid #cbd5e1;
    border-radius: 10px;

    background: #ffffff;
    color: #475569;

    font-size: 13px;
    font-weight: 600;

    cursor: pointer;
    white-space: nowrap;

    transition: all 0.2s ease;
}

.qt-refresh-btn:hover {
    background: #f5f3ff;
    border-color: #8b5cf6;
    color: #6d28d9;
}

.qt-refresh-btn:active {
    transform: scale(0.97);
}

.qt-refresh-btn:focus-visible {
    outline: 3px solid rgba(139, 92, 246, 0.25);
    outline-offset: 2px;
}

.qt-refresh-btn:disabled {
    opacity: 0.65;
    cursor: wait;
}

.qt-refresh-btn i {
    font-size: 15px;
}

.qt-refresh-btn.is-loading i {
    animation: qtRefreshSpin 0.7s linear infinite;
}

@keyframes qtRefreshSpin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 576px) {
    .qt-refresh-btn {
        width: 100%;
    }
}
</style>

<div class="container-fluid px-0 exams-page">

    <!-- THÔNG BÁO -->
    <?php if ($msg !== ''): ?>

        <div
            class="alert alert-<?= examEscape($msg_type) ?>
                   alert-dismissible fade show rounded-3 mb-4"
            role="alert"
        >

            <i class="bi bi-info-circle me-2"></i>

            <?= examEscape($msg) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Đóng"
            ></button>

        </div>

    <?php endif; ?>

    <!-- ========================================
         HEADER
    ========================================= -->

    <div class="card exams-header mb-4">

        <div
            class="card-body d-flex flex-column
                   flex-xl-row justify-content-between
                   align-items-xl-center gap-3"
        >

            <div>

                <h1 class="text-white">
                    <i class="bi bi-journal-text me-2"></i>
                    Quản lý đề thi
                </h1>

                <p>
                    <?= count($exams) ?> đề thi trong hệ thống
                </p>

            </div>

            <div class="header-actions">

                <a
                    href="create_exam.php"
                    class="btn btn-light text-primary"
                >

                    <i class="bi bi-file-earmark-arrow-up"></i>

                    Tạo / Import bộ đề

                </a>

                <button
                    type="button"
                    class="btn btn-outline-light"
                    data-bs-toggle="modal"
                    data-bs-target="#createExamModal"
                >

                    <i class="bi bi-plus-circle"></i>

                    Tạo đề thủ công

                </button>

            </div>

        </div>

    </div>

    <!-- ========================================
         TÌM KIẾM VÀ BỘ LỌC
    ========================================= -->

    <div class="exam-toolbar">

        <div class="toolbar-row">

            <div class="search-wrap">

                <span class="search-icon">
                    <i class="bi bi-search"></i>
                </span>

                <input
                    type="search"
                    id="searchInput"
                    class="search-input"
                    placeholder="Tìm tên đề thi, môn học..."
                    autocomplete="off"
                >

            </div>

            <select
                id="statusFilter"
                class="filter-select"
                aria-label="Lọc trạng thái"
            >

                <option value="">
                    Tất cả trạng thái
                </option>

                <option value="active">
                    Hoạt động
                </option>

                <option value="draft">
                    Bản nháp
                </option>

            </select>

            <select
                id="subjectFilter"
                class="filter-select"
                aria-label="Lọc môn học"
            >

                <option value="">
                    Tất cả môn học
                </option>

                <?php foreach ($subjects as $subject): ?>

                    <option
                        value="<?= (int) $subject['id'] ?>"
                    >
                        <?= examEscape($subject['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>
            <button
    type="button"
    class="qt-refresh-btn"
    id="qtRefreshBtn"
    title="Làm mới danh sách đề thi"
>
    <i class="bi bi-arrow-clockwise"></i>
    <span>Làm mới</span>
</button>

        </div>

    </div>

    <!-- ========================================
         BẢNG DỮ LIỆU
    ========================================= -->

    <div class="exam-card">

        <!-- THANH XÓA HÀNG LOẠT -->

        <div
            class="bulk-bar"
            id="bulkBar"
            aria-live="polite"
        >

            <span class="bulk-count">

                <i class="bi bi-check2-square me-1"></i>

                <strong id="selectedCount">0</strong>

                đề thi đã chọn

            </span>

            <button
                type="button"
                class="bulk-delete-btn"
                id="bulkDeleteButton"
            >

                <i class="bi bi-trash3"></i>

                Xóa hàng loạt

            </button>

        </div>

        <div class="table-responsive">

            <table
                class="exam-table"
                id="examsTable"
            >

                <thead>

                    <tr>

                        <th style="width:45px">

                            <input
                                type="checkbox"
                                id="checkAll"
                                class="form-check-input"
                                aria-label="Chọn tất cả"
                            >

                        </th>

                        <th>ID</th>

                        <th>Tên đề thi</th>

                        <th>Môn học</th>

                        <th class="text-center">
                            Câu hỏi
                        </th>

                        <th class="text-center">
                            Thời gian
                        </th>

                        <th class="text-center">
                            Trạng thái
                        </th>

                        <th class="text-end">
                            Thao tác
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($exams as $exam): ?>

                        <?php

                        $examId = (int) $exam['id'];

                        $title = $exam['title'];

                        $subjectId = (int) (
                            $exam['subject_id']
                        );

                        $subjectName = $exam['subject_name'];

                        $questionCount = (int) (
                            $exam['question_count']
                        );

                        $duration = (int) (
                            $exam['duration']
                        );

                        $status = $exam['status'];

                        $createdAt = $exam['created_at'];

                        $timestamp = strtotime(
                            (string) $createdAt
                        );

                        ?>

                        <tr
                            class="exam-row"
                            data-title="<?= examEscape(
                                mb_strtolower($title, 'UTF-8')
                            ) ?>"
                            data-subject="<?= $subjectId ?>"
                            data-subject-name="<?= examEscape(
                                mb_strtolower(
                                    $subjectName,
                                    'UTF-8'
                                )
                            ) ?>"
                            data-status="<?= examEscape($status) ?>"
                        >

                            <td>

                                <input
                                    type="checkbox"
                                    class="form-check-input exam-check"
                                    value="<?= $examId ?>"
                                    aria-label="Chọn đề thi"
                                >

                            </td>

                            <td class="fw-bold text-secondary">

                                #<?= $examId ?>

                            </td>

                            <td>

                                <div
                                    class="fw-bold text-dark"
                                    style="max-width:280px"
                                >

                                    <?= examEscape($title) ?>

                                </div>

                                <small class="text-muted">

                                    <i class="bi bi-clock me-1"></i>

                                    <?= $timestamp
                                        ? date(
                                            'd/m/Y H:i',
                                            $timestamp
                                        )
                                        : '—' ?>

                                </small>

                            </td>

                            <td>

                                <span class="subject-chip">

                                    <?= examEscape(
                                        $subjectName
                                    ) ?>

                                </span>

                            </td>

                            <td class="text-center">

                                <a
                                    href="exam_questions.php?exam_id=<?= $examId ?>"
                                    class="btn btn-sm btn-outline-primary
                                           rounded-pill"
                                >

                                    <i class="bi bi-question-circle me-1"></i>

                                    <?= $questionCount ?> câu

                                </a>

                            </td>

                            <td class="text-center">

                                <span
                                    class="status-chip
                                           bg-primary bg-opacity-10
                                           text-primary"
                                >

                                    <?= $duration ?> phút

                                </span>

                            </td>

                            <td class="text-center">

                                <?php if ($status === 'active'): ?>

                                    <span
                                        class="status-chip
                                               bg-success bg-opacity-10
                                               text-success"
                                    >

                                        Hoạt động

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="status-chip
                                               bg-secondary bg-opacity-10
                                               text-secondary"
                                    >

                                        Bản nháp

                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="text-end">

                                <div class="d-inline-flex gap-1">

                                    <!-- QUẢN LÝ CÂU HỎI -->

                                    <a
                                        href="exam_questions.php?exam_id=<?= $examId ?>"
                                        class="action-btn text-primary"
                                        title="Quản lý câu hỏi"
                                    >

                                        <i class="bi bi-list-check"></i>

                                    </a>

                                    <!-- SỬA -->

                                    <button
                                        type="button"
                                        class="action-btn text-primary
                                               edit-exam-btn"
                                        title="Sửa đề thi"
                                        data-id="<?= $examId ?>"
                                        data-title="<?= examEscape($title) ?>"
                                        data-subject="<?= $subjectId ?>"
                                        data-duration="<?= $duration ?>"
                                        data-status="<?= examEscape($status) ?>"
                                    >

                                        <i class="bi bi-pencil-square"></i>

                                    </button>

                                    <!-- XÓA -->

                                    <button
                                        type="button"
                                        class="action-btn text-danger
                                               delete-exam-btn"
                                        title="Xóa đề thi"
                                        data-id="<?= $examId ?>"
                                        data-title="<?= examEscape($title) ?>"
                                    >

                                        <i class="bi bi-trash3"></i>

                                    </button>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <!-- KHÔNG CÓ DỮ LIỆU -->

        <div
            id="examEmptyState"
            class="text-center text-muted py-5"
            style="display:none"
        >

            <i class="bi bi-inbox fs-1 d-block mb-2"></i>

            Không tìm thấy đề thi phù hợp.

        </div>

        <!-- ====================================
             PHÂN TRANG DƯỚI BẢNG
        ===================================== -->

        <div class="exam-footer">

            <!-- BÊN TRÁI -->

            <div
                class="exam-footer-info"
                id="tableMeta"
                aria-live="polite"
            >

                Hiển thị 0–0 / 0 đề thi

            </div>

            <!-- BÊN PHẢI -->

            <div class="exam-footer-controls">

                <label for="pageSize">

                    Số dòng / trang

                </label>

                <select
                    id="pageSize"
                    class="page-size-select"
                >

                    <option value="10">10</option>

                    <option value="25">25</option>

                    <option value="50">50</option>

                    <option value="100">100</option>

                </select>

                <button
                    type="button"
                    id="examPrev"
                    class="page-btn"
                >

                    ‹ Trước

                </button>

                <span
                    id="examPageIndicator"
                    class="page-indicator"
                >

                    1 / 1

                </span>

                <button
                    type="button"
                    id="examNext"
                    class="page-btn"
                >

                    Sau ›

                </button>

            </div>

        </div>

    </div>

</div>

<!-- ==================================================
     FORM XÓA HÀNG LOẠT
================================================== -->

<form
    id="bulkExamDeleteForm"
    method="POST"
    action="exams.php"
    class="d-none"
>

    <input
        type="hidden"
        name="action"
        value="bulk_delete_exams"
    >

    <?php if (function_exists('csrf_token')): ?>

        <input
            type="hidden"
            name="csrf_token"
            value="<?= examEscape(csrf_token()) ?>"
        >

    <?php endif; ?>

</form>

<!-- ==================================================
     MODAL THÊM ĐỀ THI
================================================== -->

<div
    class="modal fade exam-modal"
    id="createExamModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-plus-circle me-2"></i>

                    Thêm đề thi mới

                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                    aria-label="Đóng"
                ></button>

            </div>

            <form
                action="exams.php"
                method="POST"
                class="exam-form"
                id="formCreateExam"
            >

                <input
                    type="hidden"
                    name="action"
                    value="create_exam"
                >

                <?php if (function_exists('csrf_token')): ?>

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= examEscape(csrf_token()) ?>"
                    >

                <?php endif; ?>

                <div class="modal-body">

                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Tên đề thi

                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="text"
                            name="title"
                            class="form-control"
                            minlength="5"
                            maxlength="255"
                            required
                            placeholder="VD: Kiểm tra giữa kỳ Cơ sở dữ liệu"
                        >

                    </div>

                    <div class="row g-3 mb-3">

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Môn học

                                <span class="text-danger">*</span>

                            </label>

                            <select
                                name="subject_id"
                                class="form-select"
                                required
                            >

                                <option value="">

                                    -- Chọn môn học --

                                </option>

                                <?php foreach ($subjects as $subject): ?>

                                    <option
                                        value="<?= (int) $subject['id'] ?>"
                                    >

                                        <?= examEscape(
                                            $subject['name']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Thời gian (phút)

                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="number"
                                name="duration"
                                class="form-control"
                                min="1"
                                max="300"
                                value="45"
                                required
                            >

                        </div>

                    </div>

                    <div class="mb-2">

                        <label class="form-label fw-semibold">

                            Trạng thái

                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option value="draft">

                                Bản nháp

                            </option>

                            <option value="active">

                                Hoạt động

                            </option>

                        </select>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >

                        Hủy bỏ

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-plus-circle me-1"></i>

                        Thêm đề thi

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- ==================================================
     MODAL SỬA ĐỀ THI
================================================== -->

<div
    class="modal fade exam-modal"
    id="editExamModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-pencil-square me-2"></i>

                    Chỉnh sửa đề thi

                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                    aria-label="Đóng"
                ></button>

            </div>

            <form
                action="exams.php"
                method="POST"
                class="exam-form"
                id="formEditExam"
            >

                <input
                    type="hidden"
                    name="action"
                    value="update_exam"
                >

                <input
                    type="hidden"
                    name="exam_id"
                    id="edit_exam_id"
                >

                <?php if (function_exists('csrf_token')): ?>

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= examEscape(csrf_token()) ?>"
                    >

                <?php endif; ?>

                <div class="modal-body">

                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Tên đề thi

                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="text"
                            name="title"
                            id="edit_title"
                            class="form-control"
                            minlength="5"
                            maxlength="255"
                            required
                        >

                    </div>

                    <div class="row g-3 mb-3">

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Môn học

                                <span class="text-danger">*</span>

                            </label>

                            <select
                                name="subject_id"
                                id="edit_subject_id"
                                class="form-select"
                                required
                            >

                                <option value="">

                                    -- Chọn môn học --

                                </option>

                                <?php foreach ($subjects as $subject): ?>

                                    <option
                                        value="<?= (int) $subject['id'] ?>"
                                    >

                                        <?= examEscape(
                                            $subject['name']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">

                                Thời gian (phút)

                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="number"
                                name="duration"
                                id="edit_duration"
                                class="form-control"
                                min="1"
                                max="300"
                                required
                            >

                        </div>

                    </div>

                    <div class="mb-2">

                        <label class="form-label fw-semibold">

                            Trạng thái

                        </label>

                        <select
                            name="status"
                            id="edit_status"
                            class="form-select"
                        >

                            <option value="draft">

                                Bản nháp

                            </option>

                            <option value="active">

                                Hoạt động

                            </option>

                        </select>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >

                        Hủy bỏ

                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-check2 me-1"></i>

                        Lưu thay đổi

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- ==================================================
     JAVASCRIPT
================================================== -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function () {
    'use strict';

    const table = document.getElementById('examsTable');

    const searchInput = document.getElementById(
        'searchInput'
    );

    const statusFilter = document.getElementById(
        'statusFilter'
    );

    const subjectFilter = document.getElementById(
        'subjectFilter'
    );

    const pageSize = document.getElementById(
        'pageSize'
    );

    const tableMeta = document.getElementById(
        'tableMeta'
    );

    const prevButton = document.getElementById(
        'examPrev'
    );

    const nextButton = document.getElementById(
        'examNext'
    );

    const pageIndicator = document.getElementById(
        'examPageIndicator'
    );

    const checkAll = document.getElementById(
        'checkAll'
    );

    const bulkBar = document.getElementById(
        'bulkBar'
    );

    const selectedCount = document.getElementById(
        'selectedCount'
    );

    const bulkDeleteButton = document.getElementById(
        'bulkDeleteButton'
    );

    const emptyState = document.getElementById(
        'examEmptyState'
    );

    const rows = table
        ? Array.from(
            table.querySelectorAll(
                'tbody tr.exam-row'
            )
        )
        : [];

    let currentPage = 1;

    let currentLimit = 10;

    let filteredRows = [];

    let searchTimer = null;

    /* ==========================================
       THÔNG BÁO XÁC NHẬN
    ========================================== */

    function confirmAction(options) {

        if (typeof Swal !== 'undefined') {

            return Swal.fire({

                title: options.title,

                text: options.text,

                icon: 'warning',

                showCancelButton: true,

                confirmButtonText:
                    options.confirmText || 'Xác nhận',

                cancelButtonText: 'Hủy bỏ',

                confirmButtonColor: '#dc3545',

                cancelButtonColor: '#64748b',

                reverseButtons: true,

                customClass: {
                    popup: 'rounded-4'
                }

            }).then(function (result) {

                return result.isConfirmed;

            });

        }

        return Promise.resolve(
            window.confirm(options.text)
        );
    }

    /* ==========================================
       TÌM KIẾM / LỌC
    ========================================== */

    function normalizeText(value) {

        return String(value || '')
            .toLocaleLowerCase('vi')
            .trim();

    }

    function applyFilters(resetPage = true) {

        const keyword = normalizeText(
            searchInput ? searchInput.value : ''
        );

        const selectedStatus = statusFilter
            ? statusFilter.value
            : '';

        const selectedSubject = subjectFilter
            ? subjectFilter.value
            : '';

        filteredRows = rows.filter(function (row) {

            const title = normalizeText(
                row.dataset.title
            );

            const subjectName = normalizeText(
                row.dataset.subjectName
            );

            const subjectId = String(
                row.dataset.subject || ''
            );

            const status = String(
                row.dataset.status || ''
            );

            const matchesKeyword =
                keyword === '' ||
                title.includes(keyword) ||
                subjectName.includes(keyword);

            const matchesStatus =
                selectedStatus === '' ||
                status === selectedStatus;

            const matchesSubject =
                selectedSubject === '' ||
                subjectId === selectedSubject;

            return (
                matchesKeyword &&
                matchesStatus &&
                matchesSubject
            );

        });

        if (resetPage) {
            currentPage = 1;
        }

        renderPage();

    }

    /* ==========================================
       PHÂN TRANG
    ========================================== */

    function renderPage() {

        const total = filteredRows.length;

        const totalPages = Math.max(
            1,
            Math.ceil(total / currentLimit)
        );

        currentPage = Math.max(
            1,
            Math.min(currentPage, totalPages)
        );

        const start = (
            currentPage - 1
        ) * currentLimit;

        const end = Math.min(
            start + currentLimit,
            total
        );

        // Ẩn toàn bộ dòng
        rows.forEach(function (row) {
            row.style.display = 'none';
        });

        // Hiện dòng của trang hiện tại
        filteredRows
            .slice(start, end)
            .forEach(function (row) {
                row.style.display = '';
            });

        // Cập nhật thông tin
        if (tableMeta) {

            const first = total > 0
                ? start + 1
                : 0;

            tableMeta.textContent =
                'Hiển thị ' +
                first +
                '–' +
                end +
                ' / ' +
                total +
                ' đề thi';

        }

        if (pageIndicator) {

            pageIndicator.textContent =
                currentPage +
                ' / ' +
                totalPages;

        }

        if (prevButton) {

            prevButton.disabled =
                currentPage <= 1;

        }

        if (nextButton) {

            nextButton.disabled =
                currentPage >= totalPages;

        }

        if (emptyState) {

            emptyState.style.display =
                total === 0
                    ? 'block'
                    : 'none';

        }

        updateSelection();

    }

    function goToPage(page) {

        const totalPages = Math.max(
            1,
            Math.ceil(
                filteredRows.length /
                currentLimit
            )
        );

        currentPage = Math.max(
            1,
            Math.min(page, totalPages)
        );

        renderPage();

    }

    /* ==========================================
       CHECKBOX / XÓA HÀNG LOẠT
    ========================================== */

    function getCheckboxes() {

        return Array.from(
            document.querySelectorAll(
                '.exam-check'
            )
        );

    }

    function getSelectedCheckboxes() {

        return getCheckboxes().filter(
            function (checkbox) {
                return checkbox.checked;
            }
        );

    }

    function updateSelection() {

        const selected = getSelectedCheckboxes();

        const selectedTotal = selected.length;

        if (selectedCount) {

            selectedCount.textContent =
                selectedTotal;

        }

        if (bulkBar) {

            bulkBar.classList.toggle(
                'show',
                selectedTotal > 0
            );

        }

        if (checkAll) {

            const visibleCheckboxes = rows
                .filter(function (row) {
                    return row.style.display !== 'none';
                })
                .map(function (row) {
                    return row.querySelector(
                        '.exam-check'
                    );
                })
                .filter(Boolean);

            const checkedVisible =
                visibleCheckboxes.filter(
                    function (checkbox) {
                        return checkbox.checked;
                    }
                ).length;

            checkAll.checked =
                visibleCheckboxes.length > 0 &&
                checkedVisible ===
                visibleCheckboxes.length;

            checkAll.indeterminate =
                checkedVisible > 0 &&
                checkedVisible <
                visibleCheckboxes.length;

        }

    }

    if (checkAll) {

        checkAll.addEventListener(
            'change',
            function () {

                rows.forEach(function (row) {

                    if (
                        row.style.display === 'none'
                    ) {
                        return;
                    }

                    const checkbox =
                        row.querySelector(
                            '.exam-check'
                        );

                    if (checkbox) {

                        checkbox.checked =
                            checkAll.checked;

                    }

                });

                updateSelection();

            }
        );

    }

    getCheckboxes().forEach(
        function (checkbox) {

            checkbox.addEventListener(
                'change',
                updateSelection
            );

        }
    );

    /* ==========================================
       XÓA HÀNG LOẠT
    ========================================== */

    function bulkDeleteSelected() {

        const selected =
            getSelectedCheckboxes();

        if (!selected.length) {
            return;
        }

        confirmAction({

            title:
                'Xóa ' +
                selected.length +
                ' đề thi đã chọn?',

            text:
                'Thao tác này xóa đề thi và ' +
                'liên kết câu hỏi trong đề. ' +
                'Không thể hoàn tác.',

            confirmText: 'Xóa hàng loạt'

        }).then(function (confirmed) {

            if (!confirmed) {
                return;
            }

            const form = document.getElementById(
                'bulkExamDeleteForm'
            );

            if (!form) {
                return;
            }

            form.querySelectorAll(
                'input[name="exam_ids[]"]'
            ).forEach(function (input) {
                input.remove();
            });

            selected.forEach(function (checkbox) {

                const input =
                    document.createElement('input');

                input.type = 'hidden';

                input.name = 'exam_ids[]';

                input.value = checkbox.value;

                form.appendChild(input);

            });

            form.submit();

        });

    }

    if (bulkDeleteButton) {

        bulkDeleteButton.addEventListener(
            'click',
            bulkDeleteSelected
        );

    }

    /* ==========================================
       SỬA ĐỀ THI
    ========================================== */

    document.querySelectorAll(
        '.edit-exam-btn'
    ).forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                document.getElementById(
                    'edit_exam_id'
                ).value = button.dataset.id || '';

                document.getElementById(
                    'edit_title'
                ).value = button.dataset.title || '';

                document.getElementById(
                    'edit_subject_id'
                ).value = button.dataset.subject || '';

                document.getElementById(
                    'edit_duration'
                ).value = button.dataset.duration || 45;

                document.getElementById(
                    'edit_status'
                ).value = button.dataset.status || 'draft';

                const modalElement =
                    document.getElementById(
                        'editExamModal'
                    );

                if (
                    modalElement &&
                    typeof bootstrap !== 'undefined'
                ) {

                    bootstrap.Modal
                        .getOrCreateInstance(
                            modalElement
                        )
                        .show();

                }

            }
        );

    });

    /* ==========================================
       XÓA MỘT ĐỀ THI
    ========================================== */

    document.querySelectorAll(
        '.delete-exam-btn'
    ).forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                const examId =
                    button.dataset.id;

                const examTitle =
                    button.dataset.title || '';

                confirmAction({

                    title: 'Xác nhận xóa đề thi?',

                    text:
                        'Bạn có chắc muốn xóa "' +
                        examTitle +
                        '"?',

                    confirmText: 'Xóa đề thi'

                }).then(function (confirmed) {

                    if (!confirmed) {
                        return;
                    }

                    window.location.href =
                        'exams.php?action=delete&id=' +
                        encodeURIComponent(examId);

                });

            }
        );

    });

    /* ==========================================
       VALIDATION FORM
    ========================================== */

    document.querySelectorAll(
        '.exam-form'
    ).forEach(function (form) {

        form.addEventListener(
            'submit',
            function (event) {

                const titleInput =
                    form.querySelector(
                        'input[name="title"]'
                    );

                const subjectInput =
                    form.querySelector(
                        'select[name="subject_id"]'
                    );

                const durationInput =
                    form.querySelector(
                        'input[name="duration"]'
                    );

                if (titleInput) {

                    titleInput.value =
                        titleInput.value.trim();

                }

                const title =
                    titleInput
                        ? titleInput.value
                        : '';

                const subject =
                    subjectInput
                        ? subjectInput.value
                        : '';

                const duration =
                    durationInput
                        ? Number(durationInput.value)
                        : 0;

                const valid =
                    title.length >= 5 &&
                    title.length <= 255 &&
                    subject !== '' &&
                    Number.isInteger(duration) &&
                    duration >= 1 &&
                    duration <= 300;

                if (!valid) {

                    event.preventDefault();

                    if (
                        typeof Swal !== 'undefined'
                    ) {

                        Swal.fire({

                            icon: 'warning',

                            title:
                                'Dữ liệu không hợp lệ',

                            text:
                                'Vui lòng kiểm tra tên đề thi, ' +
                                'môn học và thời gian.',

                            confirmButtonColor:
                                '#6d28d9'

                        });

                    } else {

                        alert(
                            'Vui lòng kiểm tra dữ liệu.'
                        );

                    }

                }

            }
        );

    });

    /* ==========================================
       SỰ KIỆN TÌM KIẾM
    ========================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            function () {

                clearTimeout(searchTimer);

                searchTimer = setTimeout(
                    function () {
                        applyFilters(true);
                    },
                    300
                );

            }
        );

    }

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            function () {

                applyFilters(true);

            }
        );

    }

    if (subjectFilter) {

        subjectFilter.addEventListener(
            'change',
            function () {

                applyFilters(true);

            }
        );

    }

    if (pageSize) {

        pageSize.addEventListener(
            'change',
            function () {

                const value =
                    Number(pageSize.value);

                currentLimit = [
                    10,
                    25,
                    50,
                    100
                ].includes(value)
                    ? value
                    : 10;

                currentPage = 1;

                renderPage();

            }
        );

    }

    if (prevButton) {

        prevButton.addEventListener(
            'click',
            function () {

                goToPage(
                    currentPage - 1
                );

            }
        );

    }

    if (nextButton) {

        nextButton.addEventListener(
            'click',
            function () {

                goToPage(
                    currentPage + 1
                );

            }
        );

    }

    /* ==========================================
       KHỞI TẠO PHÂN TRANG
    ========================================== */

    applyFilters(false);

})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const refreshBtn = document.getElementById('qtRefreshBtn');

    if (!refreshBtn) {
        return;
    }

    refreshBtn.addEventListener('click', function () {

        if (refreshBtn.disabled) {
            return;
        }

        refreshBtn.disabled = true;
        refreshBtn.classList.add('is-loading');

        const label = refreshBtn.querySelector('span');

        if (label) {
            label.textContent = 'Đang tải...';
        }

        // Tải lại dữ liệu từ máy chủ.
        // Giữ nguyên URL và các tham số lọc hiện tại.
        window.location.reload();

    });

});
</script>

<?php

if (file_exists('../includes/footer.php')) {
    include '../includes/footer.php';
}

?>