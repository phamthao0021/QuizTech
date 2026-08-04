<?php
// admin/questions.php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/data.php';
requireAdmin();

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();
    $action       = $_POST['action'];
    $subject_id   = (int)($_POST['subject_id']    ?? 0);
    $content      = trim($_POST['content']         ?? '');
    $option_a     = trim($_POST['option_a']        ?? '');
    $option_b     = trim($_POST['option_b']        ?? '');
    $option_c     = trim($_POST['option_c']        ?? '');
    $option_d     = trim($_POST['option_d']        ?? '');
    $correct      = $_POST['correct_answer']        ?? 'A';
    $difficulty   = $_POST['difficulty']            ?? 'easy';
    $explanation  = trim($_POST['explanation']      ?? '');

    $valid = $subject_id > 0 && $content !== '' && $option_a !== '' && $option_b !== ''
             && in_array($correct, ['A','B','C','D']);

    if ($action === 'add') {
        if (!$valid) {
            setFlash('danger', 'Vui lòng điền đủ môn học, nội dung câu hỏi, đáp án A & B và chọn đáp án đúng!');
        } else {
            $stmt = $pdo->prepare("INSERT INTO questions
                (subject_id, content, option_a, option_b, option_c, option_d, correct_answer, difficulty, explanation)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$subject_id,$content,$option_a,$option_b,$option_c,$option_d,$correct,$difficulty,$explanation]);
            setFlash('success', 'Đã thêm câu hỏi thành công!');
        }
        redirect('questions.php');
    }

    if ($action === 'update' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        if (!$valid || $id <= 0) {
            setFlash('danger', 'Thông tin cập nhật không hợp lệ!');
        } else {
            $stmt = $pdo->prepare("UPDATE questions
                SET subject_id=?, content=?, option_a=?, option_b=?, option_c=?, option_d=?,
                    correct_answer=?, difficulty=?, explanation=?
                WHERE id=?");
            $stmt->execute([$subject_id,$content,$option_a,$option_b,$option_c,$option_d,$correct,$difficulty,$explanation,$id]);
            setFlash('success', 'Đã cập nhật câu hỏi!');
        }
        redirect('questions.php');
    }

    if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        if ($id > 0) {
            $pdo->prepare("DELETE FROM questions WHERE id=?")->execute([$id]);
            resetAutoIncrement($pdo, 'questions');
            setFlash('success', 'Đã xóa câu hỏi!');
        }
        redirect('questions.php');
    }
}

$questions  = getQuestions() ?? [];
$subjects   = getSubjects()  ?? [];
$page_title = 'Quản lý câu hỏi';
include '../includes/header.php';
?>

<div class="page-wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="topbar">
            <div>
                <h4>Quản lý câu hỏi</h4>
                <p class="text-muted">Thêm, sửa, xóa các câu hỏi trắc nghiệm</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Thêm câu hỏi
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">ID</th>
                                <th style="width:40%">Câu hỏi</th>
                                <th>Môn</th>
                                <th>Đáp án</th>
                                <th>Độ khó</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($questions)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Chưa có câu hỏi nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($questions as $q): ?>
                                <tr>
                                    <td class="ps-3"><?= $q['id'] ?></td>
                                    <td><strong><?= e(mb_strimwidth($q['content'], 0, 80, '...')) ?></strong></td>
                                    <td><span class="badge bg-secondary"><?= e($q['subject_name'] ?? '—') ?></span></td>
                                    <td><span class="badge bg-success"><?= $q['correct_answer'] ?></span></td>
                                    <td><?= difficulty_badge($q['difficulty'] ?? 'easy') ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" title="Sửa"
                                                onclick='editQuestion(<?= json_encode($q, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" title="Xóa"
                                                onclick="openDeleteModal(<?= $q['id'] ?>, '<?= e(addslashes(mb_strimwidth($q['content'], 0, 50, '...'))) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
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

<?php
// ── Reusable options helpers ──────────────────────────
$subject_options = function($selected_id = 0) use ($subjects) {
    echo '<option value="">-- Chọn môn học --</option>';
    foreach ($subjects as $s) {
        $sel = (int)$s['id'] === (int)$selected_id ? 'selected' : '';
        echo "<option value=\"{$s['id']}\" $sel>" . e($s['name']) . "</option>";
    }
};
$answer_options = function($selected = 'A') {
    foreach (['A','B','C','D'] as $opt) {
        $sel = $opt === $selected ? 'selected' : '';
        echo "<option value=\"$opt\" $sel>$opt</option>";
    }
};
$diff_options = function($selected = 'easy') {
    $map = ['easy'=>'Dễ','medium'=>'Trung bình','hard'=>'Khó'];
    foreach ($map as $val => $label) {
        $sel = $val === $selected ? 'selected' : '';
        echo "<option value=\"$val\" $sel>$label</option>";
    }
};
?>

<!-- Modal Thêm -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" onsubmit="return validateQForm(this)">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Thêm câu hỏi mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Môn học <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" required>
                            <?php $subject_options(); ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nội dung câu hỏi <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <?php foreach (['A','B','C','D'] as $opt): ?>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Đáp án <?= $opt ?><?= in_array($opt,['A','B']) ? ' <span class="text-danger">*</span>' : '' ?></label>
                            <input type="text" name="option_<?= strtolower($opt) ?>" class="form-control"
                                   <?= in_array($opt,['A','B']) ? 'required' : '' ?>>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Đáp án đúng <span class="text-danger">*</span></label>
                            <select name="correct_answer" class="form-select"><?php $answer_options(); ?></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Độ khó</label>
                            <select name="difficulty" class="form-select"><?php $diff_options(); ?></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Giải thích</label>
                            <input type="text" name="explanation" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Lưu câu hỏi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sửa -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" onsubmit="return validateQForm(this)">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Cập nhật câu hỏi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Môn học <span class="text-danger">*</span></label>
                        <select name="subject_id" id="edit_subject_id" class="form-select" required>
                            <?php $subject_options(); ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nội dung câu hỏi <span class="text-danger">*</span></label>
                        <textarea name="content" id="edit_content" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <?php foreach (['A','B','C','D'] as $opt): ?>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Đáp án <?= $opt ?><?= in_array($opt,['A','B']) ? ' <span class="text-danger">*</span>' : '' ?></label>
                            <input type="text" name="option_<?= strtolower($opt) ?>" id="edit_option_<?= strtolower($opt) ?>"
                                   class="form-control" <?= in_array($opt,['A','B']) ? 'required' : '' ?>>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Đáp án đúng <span class="text-danger">*</span></label>
                            <select name="correct_answer" id="edit_correct_answer" class="form-select"><?php $answer_options(); ?></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Độ khó</label>
                            <select name="difficulty" id="edit_difficulty" class="form-select"><?php $diff_options(); ?></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Giải thích</label>
                            <input type="text" name="explanation" id="edit_explanation" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check-circle"></i> Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Xác nhận xóa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">Bạn có chắc muốn xóa câu hỏi: <strong id="delete_preview" class="text-danger"></strong>?</p>
                    <small class="text-muted">Hành động này không thể hoàn tác.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Xóa vĩnh viễn</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function validateQForm(form) {
    const content = form.querySelector('textarea[name="content"]').value.trim();
    const a = form.querySelector('input[name="option_a"]').value.trim();
    const b = form.querySelector('input[name="option_b"]').value.trim();
    if (!content || !a || !b) {
        alert('Nội dung câu hỏi và đáp án A, B không được bỏ trống!');
        return false;
    }
    return true;
}

function editQuestion(q) {
    document.getElementById('edit_id').value             = q.id;
    document.getElementById('edit_subject_id').value     = q.subject_id;
    document.getElementById('edit_content').value        = q.content;
    document.getElementById('edit_option_a').value       = q.option_a;
    document.getElementById('edit_option_b').value       = q.option_b;
    document.getElementById('edit_option_c').value       = q.option_c || '';
    document.getElementById('edit_option_d').value       = q.option_d || '';
    document.getElementById('edit_correct_answer').value = q.correct_answer;
    document.getElementById('edit_difficulty').value     = q.difficulty;
    document.getElementById('edit_explanation').value    = q.explanation || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function openDeleteModal(id, preview) {
    document.getElementById('delete_id').value             = id;
    document.getElementById('delete_preview').textContent  = preview;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>