<?php
// teacher/questions.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';

requireTeacher();

$teacherId = (int)(
    (function_exists('currentUser')
        ? (currentUser()['id'] ?? null)
        : null)
    ?? ($_SESSION['user_id'] ?? null)
    ?? ($_SESSION['user']['id'] ?? 0)
);

// Lấy danh sách môn học của giáo viên
$stmtSubj = $pdo->prepare("
    SELECT id, name
    FROM subjects
    WHERE created_by = ?
       OR id IN (
           SELECT subject_id
           FROM teacher_subjects
           WHERE teacher_id = ?
       )
    ORDER BY name ASC
");

$stmtSubj->execute([$teacherId, $teacherId]);

$subjects = $stmtSubj->fetchAll(PDO::FETCH_ASSOC);

$allowed_subject_ids = array_column($subjects, 'id');

// ============================================================
// THÊM CÂU HỎI
// ============================================================

if (
    (filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'add'
) {
    verify_csrf();

    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct_answer = $_POST['correct_answer'] ?? 'A';
    $difficulty = $_POST['difficulty'] ?? 'easy';
    $explanation = trim($_POST['explanation'] ?? '');

    $allowed_answers = ['A', 'B', 'C', 'D'];
    $allowed_difficult = ['easy', 'medium', 'hard'];

    if (!in_array($subject_id, $allowed_subject_ids)) {
        setFlash(
            'danger',
            'Môn học không hợp lệ hoặc bạn không có quyền thao tác!'
        );
    } elseif (
        empty($content)
        || empty($option_a)
        || empty($option_b)
    ) {
        setFlash(
            'danger',
            'Vui lòng nhập nội dung câu hỏi và ít nhất 2 đáp án A, B!'
        );
    } elseif (
        !in_array($correct_answer, $allowed_answers, true)
        || trim((string)(
            [
                'A' => $option_a,
                'B' => $option_b,
                'C' => $option_c,
                'D' => $option_d
            ][$correct_answer] ?? ''
        )) === ''
    ) {
        setFlash(
            'danger',
            'Đáp án đúng không hợp lệ!'
        );
    } elseif (!in_array($difficulty, $allowed_difficult)) {
        setFlash(
            'danger',
            'Mức độ khó không hợp lệ!'
        );
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO questions (
                    subject_id,
                    created_by,
                    content,
                    option_a,
                    option_b,
                    option_c,
                    option_d,
                    correct_answer,
                    difficulty,
                    explanation,
                    created_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $subject_id,
                $teacherId,
                $content,
                $option_a,
                $option_b,
                $option_c,
                $option_d,
                $correct_answer,
                $difficulty,
                $explanation
            ]);

            setFlash(
                'success',
                'Đã thêm câu hỏi mới thành công!'
            );
        } catch (PDOException $e) {
            setFlash(
                'danger',
                'Lỗi CSDL: Không thể lưu câu hỏi!'
            );
        }

        redirect('questions.php');
    }
}

// ============================================================
// CHỈNH SỬA CÂU HỎI
// Chỉ sửa câu hỏi do Teacher tạo
// ============================================================

if (
    ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') ?? 'GET') === 'POST'
    && ($_POST['action'] ?? '') === 'edit'
) {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $content = trim((string)($_POST['content'] ?? ''));

    $options = [
        'A' => trim((string)($_POST['option_a'] ?? '')),
        'B' => trim((string)($_POST['option_b'] ?? '')),
        'C' => trim((string)($_POST['option_c'] ?? '')),
        'D' => trim((string)($_POST['option_d'] ?? ''))
    ];

    $correct = strtoupper(
        trim((string)($_POST['correct_answer'] ?? ''))
    );

    $difficulty = (string)($_POST['difficulty'] ?? '');

    $explanation = trim(
        (string)($_POST['explanation'] ?? '')
    );

    $owner = $pdo->prepare("
        SELECT id
        FROM questions
        WHERE id = ?
          AND created_by = ?
    ");

    $owner->execute([$id, $teacherId]);

    if (!$owner->fetchColumn()) {
        setFlash(
            'danger',
            'Không tìm thấy câu hỏi hoặc bạn không có quyền chỉnh sửa.'
        );
    } elseif (
        !in_array(
            $subject_id,
            array_map('intval', $allowed_subject_ids),
            true
        )
        || $content === ''
        || $options['A'] === ''
        || $options['B'] === ''
        || !in_array($correct, ['A', 'B', 'C', 'D'], true)
        || $options[$correct] === ''
        || !in_array(
            $difficulty,
            ['easy', 'medium', 'hard'],
            true
        )
    ) {
        setFlash(
            'danger',
            'Vui lòng nhập đầy đủ nội dung, đáp án và chọn đáp án đúng có dữ liệu.'
        );
    } else {
        try {
            $update = $pdo->prepare("
                UPDATE questions
                SET
                    subject_id = ?,
                    content = ?,
                    option_a = ?,
                    option_b = ?,
                    option_c = ?,
                    option_d = ?,
                    correct_answer = ?,
                    difficulty = ?,
                    explanation = ?
                WHERE id = ?
                  AND created_by = ?
            ");

            $update->execute([
                $subject_id,
                $content,
                $options['A'],
                $options['B'],
                $options['C'],
                $options['D'],
                $correct,
                $difficulty,
                $explanation,
                $id,
                $teacherId
            ]);

            setFlash(
                'success',
                'Đã cập nhật câu hỏi.'
            );
        } catch (PDOException $e) {
            setFlash(
                'danger',
                'Không thể cập nhật câu hỏi. Vui lòng thử lại.'
            );
        }
    }

    redirect('questions.php');
}

// ============================================================
// XÓA CÂU HỎI
// ============================================================

if (
    (filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'delete'
) {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmtChk = $pdo->prepare("
            SELECT created_by
            FROM questions
            WHERE id = ?
        ");

        $stmtChk->execute([$id]);

        $q = $stmtChk->fetch(PDO::FETCH_ASSOC);

        if (!$q || $q['created_by'] != $teacherId) {
            setFlash(
                'danger',
                'Bạn không có quyền xóa câu hỏi này!'
            );
        } else {
            try {
                $stmt = $pdo->prepare("
                    DELETE FROM questions
                    WHERE id = ?
                ");

                $stmt->execute([$id]);

                setFlash(
                    'success',
                    'Đã xóa câu hỏi thành công!'
                );
            } catch (PDOException $e) {
                setFlash(
                    'danger',
                    'Lỗi: Không thể xóa câu hỏi này (có thể đã được dùng trong đề thi)!'
                );
            }
        }
    }

    redirect('questions.php');
}

// ============================================================
// LẤY DANH SÁCH CÂU HỎI
// ============================================================

$stmt = $pdo->prepare("
    SELECT
        q.*,
        s.name AS subject_name
    FROM questions q
    LEFT JOIN subjects s
        ON q.subject_id = s.id
    WHERE q.created_by = ?
       OR q.subject_id IN (
           SELECT subject_id
           FROM teacher_subjects
           WHERE teacher_id = ?
       )
    ORDER BY q.id DESC
");

$stmt->execute([$teacherId, $teacherId]);

$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Quản lý câu hỏi';

include '../includes/header_teacher.php';
?>

<main class="p-4 bg-light flex-grow-1 qt-t-page qt-t-questions">

    <!-- HEADER -->

    <section class="qt-t-hero qt-q-hero">

        <div class="qt-t-hero-icon">
            <i class="bi bi-patch-question"></i>
        </div>

        <div class="qt-t-hero-copy">

            <div class="qt-t-eyebrow">
                QUIZTECH · TEACHER
            </div>

            <h1>Ngân hàng câu hỏi</h1>

            <p>
                Quản lý câu hỏi trắc nghiệm theo từng môn học
            </p>

        </div>

        <div class="qt-q-hero-actions">

            <button
                type="button"
                class="qt-q-hero-add"
                data-bs-toggle="modal"
                data-bs-target="#questionFormModal"
                data-mode="add"
            >
                <i class="bi bi-plus-circle"></i>
                Thêm câu hỏi
            </button>

            <a
                href="dashboard.php"
                class="qt-t-hero-home"
            >
                <i class="bi bi-house-door"></i>
                Dashboard
            </a>

        </div>

    </section>

    <!-- THỐNG KÊ -->

    <section class="qt-q-summary">

        <div class="qt-q-stat">

            <span class="qt-q-stat-icon">
                <i class="bi bi-question-square"></i>
            </span>

            <div>
                <small>Tổng câu hỏi hiển thị</small>
                <strong><?= count($questions) ?></strong>
            </div>

        </div>

        <div class="qt-q-stat">

            <span class="qt-q-stat-icon violet">
                <i class="bi bi-pencil-square"></i>
            </span>

            <div>
                <small>Câu hỏi của tôi</small>

                <strong>
                    <?= count(
                        array_filter(
                            $questions,
                            static fn($q) =>
                                (int)$q['created_by'] === $teacherId
                        )
                    ) ?>
                </strong>
            </div>

        </div>

        <div class="qt-q-stat">

            <span class="qt-q-stat-icon pink">
                <i class="bi bi-collection"></i>
            </span>

            <div>
                <small>Môn học có câu hỏi</small>

                <strong>
                    <?= count(
                        array_unique(
                            array_column($questions, 'subject_id')
                        )
                    ) ?>
                </strong>
            </div>

        </div>

    </section>

    <!-- DANH SÁCH -->

    <section class="qt-q-panel">

        <div class="qt-q-heading">

            <div>
                <h2>
                    <i class="bi bi-list-ul"></i>
                    Danh sách câu hỏi
                </h2>

                <p>
                    Câu hỏi thuộc môn được phân công có thể xem;
                    chỉ câu hỏi do bạn tạo mới được sửa hoặc xóa.
                </p>
            </div>

            <span
                id="questionCount"
                class="qt-q-count"
            >
                <?= count($questions) ?> câu hỏi
            </span>

        </div>

        <!-- TÌM KIẾM VÀ BỘ LỌC -->

        <div class="qt-q-toolbar">

            <label class="qt-q-search">

                <i class="bi bi-search"></i>

                <input
                    type="search"
                    id="questionSearch"
                    placeholder="Tìm nội dung câu hỏi, môn học, ID..."
                    autocomplete="off"
                    aria-label="Tìm câu hỏi"
                >

            </label>

            <select
                id="questionSubject"
                class="form-select"
                aria-label="Lọc môn học"
            >

                <option value="">
                    Tất cả môn học
                </option>

                <?php foreach ($subjects as $subject): ?>

                    <option
                        value="<?= (int)$subject['id'] ?>"
                    >
                        <?= e($subject['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <select
                id="questionDifficulty"
                class="form-select"
                aria-label="Lọc độ khó"
            >
                <option value="">Tất cả độ khó</option>
                <option value="easy">Dễ</option>
                <option value="medium">Trung bình</option>
                <option value="hard">Khó</option>
            </select>

            <select
                id="questionOwnership"
                class="form-select"
                aria-label="Lọc quyền sở hữu"
            >
                <option value="">Tất cả câu hỏi</option>
                <option value="mine">Của tôi</option>
                <option value="shared">Được phân công</option>
            </select>

            <select
                id="questionSort"
                class="form-select"
                aria-label="Sắp xếp"
            >
                <option value="newest">Mới nhất</option>
                <option value="oldest">Cũ nhất</option>
                <option value="content">Nội dung A–Z</option>
            </select>

            <button
                type="button"
                id="questionRefresh"
                class="qt-q-refresh"
            >
                <i class="bi bi-arrow-clockwise"></i>
                Làm mới
            </button>

        </div>

        <!-- BẢNG CÂU HỎI -->

        <div class="qt-q-table-wrap">

            <table class="qt-q-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nội dung câu hỏi</th>
                        <th>Môn học</th>
                        <th>Đáp án</th>
                        <th>Độ khó</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody id="questionRows">

                    <?php foreach ($questions as $q): ?>

                        <?php
                        $mine = (int)$q['created_by'] === $teacherId;
                        ?>

                        <tr
                            class="qt-q-row"

                            data-id="<?= (int)$q['id'] ?>"

                            data-subject="<?= (int)$q['subject_id'] ?>"

                            data-difficulty="<?= e($q['difficulty']) ?>"

                            data-owner="<?= $mine ? 'mine' : 'shared' ?>"

                            data-content="<?= e($q['content']) ?>"

                            data-search="<?= e(
                                '#' . $q['id'] . ' ' .
                                $q['content'] . ' ' .
                                ($q['subject_name'] ?? '')
                            ) ?>"
                        >

                            <!-- ID -->

                            <td data-label="ID">

                                <span class="qt-q-id">
                                    #<?= (int)$q['id'] ?>
                                </span>

                            </td>

                            <!-- NỘI DUNG -->

                            <td data-label="Câu hỏi">

                                <strong class="qt-q-question">
                                    <?= e($q['content']) ?>
                                </strong>

                                <?php if (!$mine): ?>

                                    <span class="qt-q-readonly">
                                        <i class="bi bi-eye"></i>
                                        Chỉ xem
                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- MÔN HỌC -->

                            <td data-label="Môn học">

                                <span class="qt-q-subject">
                                    <?= e(
                                        $q['subject_name']
                                        ?? 'Chưa phân loại'
                                    ) ?>
                                </span>

                            </td>

                            <!-- ĐÁP ÁN -->

                            <td data-label="Đáp án">

                                <span class="qt-q-answer">
                                    <?= e($q['correct_answer']) ?>
                                </span>

                            </td>

                            <!-- ĐỘ KHÓ -->

                            <td data-label="Độ khó">

                                <span
                                    class="qt-q-difficulty <?= e($q['difficulty']) ?>"
                                >
                                    <?= [
                                        'easy' => 'Dễ',
                                        'medium' => 'Trung bình',
                                        'hard' => 'Khó'
                                    ][$q['difficulty']]
                                    ?? e($q['difficulty']) ?>
                                </span>

                            </td>

                            <!-- THAO TÁC -->

                            <td data-label="Thao tác">

                                <div class="qt-q-actions">

                                    <!-- XEM -->

                                    <button
                                        type="button"
                                        class="qt-q-action view"

                                        data-bs-toggle="modal"
                                        data-bs-target="#questionViewModal"

                                        data-id="<?= (int)$q['id'] ?>"

                                        data-subject-name="<?= e(
                                            $q['subject_name']
                                            ?? 'Chưa phân loại'
                                        ) ?>"

                                        data-content="<?= e($q['content']) ?>"

                                        data-option-a="<?= e($q['option_a'] ?? '') ?>"
                                        data-option-b="<?= e($q['option_b'] ?? '') ?>"
                                        data-option-c="<?= e($q['option_c'] ?? '') ?>"
                                        data-option-d="<?= e($q['option_d'] ?? '') ?>"

                                        data-correct="<?= e($q['correct_answer']) ?>"

                                        data-difficulty="<?= e($q['difficulty']) ?>"

                                        data-explanation="<?= e(
                                            $q['explanation'] ?? ''
                                        ) ?>"
                                    >
                                        <i class="bi bi-eye"></i>
                                        Xem
                                    </button>

                                    <?php if ($mine): ?>

                                        <!-- SỬA -->

                                        <button
                                            type="button"
                                            class="qt-q-action update"

                                            data-bs-toggle="modal"
                                            data-bs-target="#questionFormModal"

                                            data-mode="edit"

                                            data-id="<?= (int)$q['id'] ?>"

                                            data-subject="<?= (int)$q['subject_id'] ?>"

                                            data-content="<?= e($q['content']) ?>"

                                            data-option-a="<?= e($q['option_a'] ?? '') ?>"
                                            data-option-b="<?= e($q['option_b'] ?? '') ?>"
                                            data-option-c="<?= e($q['option_c'] ?? '') ?>"
                                            data-option-d="<?= e($q['option_d'] ?? '') ?>"

                                            data-correct="<?= e($q['correct_answer']) ?>"

                                            data-difficulty="<?= e($q['difficulty']) ?>"

                                            data-explanation="<?= e(
                                                $q['explanation'] ?? ''
                                            ) ?>"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                            Sửa
                                        </button>

                                        <!-- XÓA -->

                                        <button
                                            type="button"
                                            class="qt-q-action delete"

                                            data-bs-toggle="modal"
                                            data-bs-target="#questionDeleteModal"

                                            data-id="<?= (int)$q['id'] ?>"

                                            data-content="<?= e($q['content']) ?>"
                                        >
                                            <i class="bi bi-trash"></i>
                                            Xóa
                                        </button>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

            <!-- KHÔNG CÓ DỮ LIỆU -->

            <div
                class="qt-q-empty"
                id="questionEmpty"
                hidden
            >

                <i class="bi bi-search"></i>

                <strong>Không tìm thấy câu hỏi</strong>

                <span>
                    Thử đổi từ khóa hoặc bộ lọc.
                </span>

            </div>

        </div>

        <!-- PHÂN TRANG -->

        <div class="qt-q-footer">

            <span id="questionRange"></span>

            <label>

                Hiển thị

                <select
                    id="questionPageSize"
                    class="form-select form-select-sm"
                >
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>

            </label>

            <nav
                id="questionPagination"
                aria-label="Phân trang câu hỏi"
            ></nav>

        </div>

    </section>

</main>

<!-- ============================================================
     MODAL THÊM / SỬA CÂU HỎI
============================================================ -->

<div
    class="modal fade"
    id="questionFormModal"
    tabindex="-1"
    aria-labelledby="questionFormTitle"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">

        <div class="modal-content qt-q-modal">

            <form method="POST" action="questions.php">

                <?php csrf_field(); ?>

                <input
                    type="hidden"
                    name="action"
                    id="questionFormAction"
                    value="add"
                >

                <input
                    type="hidden"
                    name="id"
                    id="questionFormId"
                >

                <!-- HEADER -->

                <div class="modal-header">

                    <div>

                        <small>NGÂN HÀNG CÂU HỎI</small>

                        <h5
                            class="modal-title"
                            id="questionFormTitle"
                        >
                            Thêm câu hỏi mới
                        </h5>

                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Đóng"
                    ></button>

                </div>

                <!-- BODY -->

                <div class="modal-body">

                    <div class="qt-q-note">

                        <i class="bi bi-info-circle"></i>

                        Nhập nội dung và ít nhất hai lựa chọn A, B.
                        Đáp án đúng phải có nội dung.

                    </div>

                    <div class="row g-3">

                        <!-- MÔN HỌC -->

                        <div class="col-12">

                            <label
                                class="form-label"
                                for="qFormSubject"
                            >
                                Môn học
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                id="qFormSubject"
                                name="subject_id"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Chọn môn học
                                </option>

                                <?php foreach ($subjects as $sub): ?>

                                    <option
                                        value="<?= (int)$sub['id'] ?>"
                                    >
                                        <?= e($sub['name']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <!-- NỘI DUNG CÂU HỎI -->

                        <div class="col-12">

                            <label
                                class="form-label"
                                for="qFormContent"
                            >
                                Nội dung câu hỏi
                                <span class="text-danger">*</span>
                            </label>

                            <textarea
                                id="qFormContent"
                                name="content"
                                class="form-control"
                                rows="3"
                                required
                                placeholder="Ví dụ: Kiểm thử hồi quy là gì?"
                            ></textarea>

                        </div>

                        <!-- ĐÁP ÁN A, B, C, D -->

                        <?php foreach (
                            [
                                'a' => 'A',
                                'b' => 'B',
                                'c' => 'C',
                                'd' => 'D'
                            ] as $key => $letter
                        ): ?>

                            <div class="col-12 col-md-6">

                                <label
                                    class="form-label"
                                    for="qFormOption<?= $letter ?>"
                                >
                                    Đáp án <?= $letter ?>

                                    <?= in_array(
                                        $letter,
                                        ['A', 'B'],
                                        true
                                    ) ? '*' : '' ?>
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    name="option_<?= $key ?>"
                                    id="qFormOption<?= $letter ?>"

                                    <?= in_array(
                                        $letter,
                                        ['A', 'B'],
                                        true
                                    ) ? 'required' : '' ?>
                                >

                            </div>

                        <?php endforeach; ?>

                        <!-- ĐÁP ÁN ĐÚNG -->

                        <div class="col-12 col-md-6">

                            <label
                                class="form-label"
                                for="qFormCorrect"
                            >
                                Đáp án đúng
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                name="correct_answer"
                                id="qFormCorrect"
                                class="form-select"
                                required
                            >
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>

                        </div>

                        <!-- ĐỘ KHÓ -->

                        <div class="col-12 col-md-6">

                            <label
                                class="form-label"
                                for="qFormDifficulty"
                            >
                                Độ khó
                            </label>

                            <select
                                name="difficulty"
                                id="qFormDifficulty"
                                class="form-select"
                            >
                                <option value="easy">
                                    Dễ
                                </option>

                                <option
                                    value="medium"
                                    selected
                                >
                                    Trung bình
                                </option>

                                <option value="hard">
                                    Khó
                                </option>
                            </select>

                        </div>

                        <!-- GIẢI THÍCH -->

                        <div class="col-12">

                            <label
                                class="form-label"
                                for="qFormExplanation"
                            >
                                Giải thích đáp án
                            </label>

                            <textarea
                                id="qFormExplanation"
                                name="explanation"
                                class="form-control"
                                rows="2"
                                placeholder="Giải thích vì sao đáp án này đúng..."
                            ></textarea>

                        </div>

                    </div>

                </div>

                <!-- FOOTER -->

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >
                        <i class="bi bi-x-circle"></i>
                        Hủy
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-check-circle"></i>

                        <span id="questionFormSubmit">
                            Thêm câu hỏi
                        </span>
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- ============================================================
     MODAL XEM CHI TIẾT
============================================================ -->

<div
    class="modal fade"
    id="questionViewModal"
    tabindex="-1"
    aria-labelledby="questionViewTitle"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">

        <div class="modal-content qt-q-modal">

            <div class="modal-header">

                <div>

                    <small>CHI TIẾT CÂU HỎI</small>

                    <h5
                        class="modal-title"
                        id="questionViewTitle"
                    >
                        Xem câu hỏi
                    </h5>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Đóng"
                ></button>

            </div>

            <div class="modal-body">

                <div class="qt-q-view-meta">

                    <span id="qViewSubject"></span>

                    <span id="qViewDifficulty"></span>

                    <span id="qViewId"></span>

                </div>

                <h5
                    id="qViewContent"
                    class="qt-q-view-question"
                ></h5>

                <div
                    id="qViewOptions"
                    class="qt-q-view-options"
                ></div>

                <div class="qt-q-explanation">

                    <strong>
                        <i class="bi bi-lightbulb"></i>
                        Giải thích
                    </strong>

                    <p id="qViewExplanation"></p>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-primary"
                    data-bs-dismiss="modal"
                >
                    Đóng
                </button>

            </div>

        </div>

    </div>

</div>

<!-- ============================================================
     MODAL XÓA CÂU HỎI
============================================================ -->

<div
    class="modal fade"
    id="questionDeleteModal"
    tabindex="-1"
    aria-labelledby="questionDeleteTitle"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content qt-q-modal">

            <form method="POST" action="questions.php">

                <?php csrf_field(); ?>

                <input
                    type="hidden"
                    name="action"
                    value="delete"
                >

                <input
                    type="hidden"
                    name="id"
                    id="qDeleteId"
                >

                <div class="modal-header">

                    <h5
                        class="modal-title"
                        id="questionDeleteTitle"
                    >
                        <i class="bi bi-exclamation-triangle text-danger"></i>
                        Xác nhận xóa câu hỏi
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Đóng"
                    ></button>

                </div>

                <div class="modal-body text-center">

                    <div class="qt-q-delete-icon">
                        <i class="bi bi-trash3"></i>
                    </div>

                    <p>
                        Bạn có chắc chắn muốn xóa câu hỏi sau?
                    </p>

                    <div
                        class="qt-q-delete-content"
                        id="qDeleteContent"
                    ></div>

                    <p class="text-danger small mt-3 mb-0">
                        Nếu câu hỏi đã được dùng trong đề thi,
                        hệ thống có thể không cho phép xóa.
                    </p>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >
                        Giữ lại
                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger"
                    >
                        <i class="bi bi-trash"></i>
                        Xác nhận xóa
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<!-- JAVASCRIPT -->

<script
    src="../assets/js/qt-teacher-questions.js?v=1"
    defer
></script>

<?php
echo '</div></div>';
include '../includes/footer.php';
?>