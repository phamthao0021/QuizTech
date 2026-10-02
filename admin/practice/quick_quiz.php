<?php
$root = dirname(__DIR__, 2);

require_once $root . '/includes/config.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';
require_once $root . '/includes/practice_admin_service.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

pa_guard();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $a = $_POST['action'] ?? '';
    try {
        if ($a === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $q = trim($_POST['question'] ?? '');
            $o = [];
            foreach (['a', 'b', 'c', 'd'] as $x) $o[$x] = trim($_POST['option_' . $x] ?? '');
            $ok = strtoupper(trim($_POST['correct_answer'] ?? ''));
            $exp = trim($_POST['explanation'] ?? '');
            $cat = trim($_POST['category'] ?? '');
            $time = max(5, min(60, (int)($_POST['time_limit_seconds'] ?? 5)));
            $active = isset($_POST['is_active']) ? 1 : 0;
            if ($q === '' || in_array('', array_values($o), true) || !in_array($ok, ['A', 'B', 'C', 'D'], true)) throw new RuntimeException('Cần đủ câu hỏi, 4 đáp án và đáp án đúng.');
            $vals = [$q, $o['a'], $o['b'], $o['c'], $o['d'], $ok, $exp, $cat, $time, $active];
            if ($id) {
                $s = $pdo->prepare("UPDATE practice_quiz_questions SET question=?,option_a=?,option_b=?,option_c=?,option_d=?,correct_answer=?,explanation=?,category=?,time_limit_seconds=?,is_active=? WHERE id=?");
                $vals[] = $id;
                $s->execute($vals);
            } else {
                $s = $pdo->prepare("INSERT INTO practice_quiz_questions(question,option_a,option_b,option_c,option_d,correct_answer,explanation,category,time_limit_seconds,is_active) VALUES(?,?,?,?,?,?,?,?,?,?)");
                $s->execute($vals);
            }
            setFlash('success', $id ? 'Đã cập nhật câu hỏi.' : 'Đã thêm câu hỏi.');
        } elseif ($a === 'toggle') {
            $pdo->prepare("UPDATE practice_quiz_questions SET is_active=1-is_active WHERE id=?")->execute([(int)$_POST['id']]);
            setFlash('success', 'Đã đổi trạng thái.');
        } elseif ($a === 'delete' || $a === 'bulk_delete') {
            $ids = $a === 'delete' ? [(int)$_POST['id']] : pa_ids($_POST['ids'] ?? []);
            if ($ids) {
                $in = implode(',', array_fill(0, count($ids), '?'));
                $pdo->prepare("DELETE FROM practice_quiz_questions WHERE id IN($in)")->execute($ids);
            }
            setFlash('success', 'Đã xóa dữ liệu đã chọn.');
        } elseif ($a === 'import') {
            $rows = pa_assoc_rows(pa_csv_rows($_FILES['import_file'] ?? []));
            $pdo->beginTransaction();
            $s = $pdo->prepare("INSERT INTO practice_quiz_questions(question,option_a,option_b,option_c,option_d,correct_answer,explanation,category,time_limit_seconds,is_active) VALUES(?,?,?,?,?,?,?,?,?,?)");
            $n = 0;
            foreach ($rows as $i => $r) {
                $ok = strtoupper(trim($r['correct_answer'] ?? ''));
                $vals = [trim($r['question'] ?? ''), trim($r['option_a'] ?? ''), trim($r['option_b'] ?? ''), trim($r['option_c'] ?? ''), trim($r['option_d'] ?? '')];
                if (in_array('', $vals, true) || !in_array($ok, ['A', 'B', 'C', 'D'], true)) throw new RuntimeException('Dòng ' . ($i + 2) . ' không hợp lệ.');
                $s->execute([$vals[0], $vals[1], $vals[2], $vals[3], $vals[4], $ok, trim($r['explanation'] ?? ''), trim($r['category'] ?? ''), max(5, (int)($r['time_limit_seconds'] ?? 5)), (int)($r['is_active'] ?? 1) ? 1 : 0]);
                $n++;
            }
            $pdo->commit();
            setFlash('success', "Import thành công $n câu.");
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', $e->getMessage());
    }
    header('Location: quick_quiz.php');
    exit;
}
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$cat = trim($_GET['category'] ?? '');
$limit = pa_page_size();
$page = pa_page();
$where = [];
$par = [];
if ($q !== '') {
    $where[] = '(question LIKE ? ESCAPE "\\\\" OR category LIKE ? ESCAPE "\\\\")';
    $x = pa_like($q);
    array_push($par, $x, $x);
}
if ($status === 'active' || $status === 'inactive') {
    $where[] = 'is_active=?';
    $par[] = $status === 'active' ? 1 : 0;
}
if ($cat !== '') {
    $where[] = 'category=?';
    $par[] = $cat;
}
$w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$s = $pdo->prepare("SELECT COUNT(*) FROM practice_quiz_questions$w");
$s->execute($par);
$total = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT * FROM practice_quiz_questions$w ORDER BY id DESC LIMIT $limit OFFSET " . (($page - 1) * $limit));
$s->execute($par);
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
$cats = $pdo->query("SELECT DISTINCT category FROM practice_quiz_questions WHERE category IS NOT NULL AND category<>'' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$page_title = 'Quản lý Phản xạ nhanh';
require $root . '/includes/header_admin.php';
?>
<?php require __DIR__ . '/_practice_ui.php'; ?>
<div class="container-fluid py-4 px-3 px-md-4 pa-wrap">
    <div class="pa-head mb-4 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
            <div class="small text-white-50 fw-bold">QUIZTECH · PRACTICE ADMIN</div>
            <h3 class="fw-bold mb-1">Phản xạ nhanh</h3>
            <p class="mb-0 text-white-50">Quản lý câu hỏi 5 giây, đáp án, giải thích và trạng thái.</p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2"><button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Import Excel/CSV</button><button class="btn btn-warning" onclick="openEdit()"><i class="bi bi-plus-lg me-1"></i>Thêm câu hỏi</button></div>
    </div>
    <?php if (function_exists('getFlash') && ($f = getFlash())): ?><div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show"><?= e($f['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <form class="pa-card p-3 mb-3 pa-toolbar" method="get"><input class="form-control pa-search" name="q" value="<?= e($q) ?>" placeholder="Tìm câu hỏi..."><select class="form-select" name="category">
            <option value="">Tất cả chủ đề</option><?php foreach ($cats as $c): ?><option <?= $cat === $c ? 'selected' : '' ?> value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
        </select><select class="form-select" name="status">
            <option value="">Mọi trạng thái</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Đang dùng</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Đã khóa</option>
        </select><button class="btn btn-pa">Lọc</button></form>
    <form id="bulkForm" method="post" class="pa-card overflow-hidden"><?php csrf_field(); ?><input type="hidden" name="action" value="bulk_delete">
        <div class="p-3 border-bottom d-flex justify-content-between"><b><?= number_format($total) ?> câu hỏi</b><button class="btn btn-outline-danger btn-sm" onclick="return bulkDelete()">Xóa đã chọn</button></div>
        <div class="table-responsive">
            <table class="table pa-table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" onclick="toggleAll(this)"></th>
                        <th>Câu hỏi</th>
                        <th>Đúng</th>
                        <th>Chủ đề</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?><tr>
                            <td><input class="form-check-input rowck" type="checkbox" name="ids[]" value="<?= $r['id'] ?>"></td>
                            <td style="min-width:280px"><b><?= e($r['question']) ?></b></td>
                            <td><span class="badge bg-success"><?= e($r['correct_answer']) ?></span></td>
                            <td><?= e($r['category'] ?: '—') ?></td>
                            <td><?= (int)$r['time_limit_seconds'] ?>s</td>
                            <td><?= $r['is_active'] ? '<span class="badge bg-success-subtle text-success">Đang dùng</span>' : '<span class="badge bg-secondary-subtle text-secondary">Đã khóa</span>' ?></td>
                            <td class="text-end pa-actions"><button type="button" class="btn btn-sm btn-light text-primary" onclick='openEdit(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'><i class="bi bi-pencil"></i></button><button type="button" class="btn btn-sm btn-light text-warning" onclick="postOne('toggle',<?= $r['id'] ?>)"><i class="bi bi-lock"></i></button><button type="button" class="btn btn-sm btn-light text-danger" onclick="postOne('delete',<?= $r['id'] ?>)"><i class="bi bi-trash"></i></button></td>
                        </tr><?php endforeach; ?><?php if (!$rows): ?><tr>
                            <td colspan="7" class="pa-empty">Không có dữ liệu.</td>
                        </tr><?php endif; ?></tbody>
            </table>
        </div>
    </form>
    <div class="d-flex justify-content-between mt-3">
        <small class="text-muted">Trang <?= $page ?> · <?= number_format($total) ?> câu</small>
        <?php pa_render_pagination($total, $limit, $page); ?></div>
</div>

<div class="modal fade" id="editModal">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form method="post"><?php csrf_field(); ?>
            <input name="action" value="save" type="hidden">
            <input name="id" id="e_id" type="hidden">
                <div class="modal-header">
                    <h5 class="modal-title">Câu hỏi phản xạ nhanh</h5><button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12"><label>Câu hỏi *</label><textarea class="form-control" name="question" id="e_question" required></textarea></div><?php foreach (['a', 'b', 'c', 'd'] as $x): ?><div class="col-md-6"><label>Đáp án <?= strtoupper($x) ?> *</label><input class="form-control" name="option_<?= $x ?>" id="e_<?= $x ?>" required></div><?php endforeach; ?><div class="col-md-4"><label>Đáp án đúng</label><select class="form-select" name="correct_answer" id="e_correct"><?php foreach (['A', 'B', 'C', 'D'] as $x): ?><option><?= $x ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label>Chủ đề</label><input class="form-control" name="category" id="e_category"></div>
                    <div class="col-md-4"><label>Thời gian/câu</label><input class="form-control" type="number" min="5" max="60" name="time_limit_seconds" id="e_time" value="5"></div>
                    <div class="col-12"><label>Giải thích</label><textarea class="form-control" name="explanation" id="e_explanation"></textarea></div>
                    <div class="col-12 form-check ms-2"><input class="form-check-input" name="is_active" type="checkbox" id="e_active" checked><label class="form-check-label">Đang sử dụng</label></div>
                </div>
                <div class="modal-footer"><button class="btn btn-pa">Lưu</button></div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="importModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form method="post" enctype="multipart/form-data"><?php csrf_field(); ?><input name="action" value="import" type="hidden">
                <div class="modal-header">
                    <h5 class="modal-title">Import câu hỏi</h5><button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><input class="form-control" type="file" name="import_file" accept=".csv,.xlsx" required><small class="text-muted">question, option_a, option_b, option_c, option_d, correct_answer, explanation, category, time_limit_seconds, is_active</small></div>
                <div class="modal-footer"><button class="btn btn-pa">Import</button></div>
            </form>
        </div>
    </div>
</div>
<form id="oneForm" method="post" class="d-none"><?php csrf_field(); ?><input name="action" id="oneAction"><input name="id" id="oneId"></form>
<script>
    function openEdit(r = {}) {
        e_id.value = r.id || '';
        e_question.value = r.question || '';
        e_a.value = r.option_a || '';
        e_b.value = r.option_b || '';
        e_c.value = r.option_c || '';
        e_d.value = r.option_d || '';
        e_correct.value = r.correct_answer || 'A';
        e_category.value = r.category || '';
        e_time.value = r.time_limit_seconds || 5;
        e_explanation.value = r.explanation || '';
        e_active.checked = r.id ? Number(r.is_active) === 1 : true;
        bootstrap.Modal.getOrCreateInstance(editModal).show()
    }

    function toggleAll(x) {
        document.querySelectorAll('.rowck').forEach(c => c.checked = x.checked)
    }

    function bulkDelete() {
        if (!document.querySelector('.rowck:checked')) {
            alert('Hãy chọn câu hỏi.');
            return false
        }
        return confirm('Xóa các câu đã chọn?')
    }

    function postOne(a, id) {
        if (a === 'delete' && !confirm('Xóa câu hỏi này?')) return;
        oneAction.value = a;
        oneId.value = id;
        oneForm.submit()
    }
</script><?php require $root . '/includes/footer.php'; ?>