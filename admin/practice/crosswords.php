<?php
$root = dirname(__DIR__, 2);

require_once $root . '/includes/config.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';
require_once $root . '/includes/practice_admin_service.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Kiểm tra service Practice đã được nạp */
if (!function_exists('pa_page_size')) {
    die('Lỗi Practice Admin: không tìm thấy pa_page_size(). '
        . 'Kiểm tra file: '
        . $root
        . '/includes/practice_admin_service.php');
}

if (function_exists('pa_guard')) {
    pa_guard();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $a = $_POST['action'] ?? '';
    try {
        if ($a === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $rows = max(3, min(30, (int)($_POST['rows_count'] ?? 9)));
            $cols = max(3, min(30, (int)($_POST['cols_count'] ?? 9)));
            $diff = $_POST['difficulty'] ?? 'medium';
            if (!in_array($diff, ['easy', 'medium', 'hard'], true)) $diff = 'medium';
            $time = max(60, min(3600, (int)($_POST['time_limit_seconds'] ?? 420)));
            $active = isset($_POST['is_active']) ? 1 : 0;
            if ($title === '') throw new RuntimeException('Tên ô chữ không được trống.');
            if ($id) $pdo->prepare("UPDATE practice_crosswords SET title=?,description=?,rows_count=?,cols_count=?,difficulty=?,time_limit_seconds=?,is_active=? WHERE id=?")->execute([$title, $desc, $rows, $cols, $diff, $time, $active, $id]);
            else $pdo->prepare("INSERT INTO practice_crosswords(title,description,rows_count,cols_count,difficulty,time_limit_seconds,is_active) VALUES(?,?,?,?,?,?,?)")->execute([$title, $desc, $rows, $cols, $diff, $time, $active]);
            setFlash('success', 'Đã lưu bộ ô chữ.');
        } elseif ($a === 'toggle') {
            $pdo->prepare("UPDATE practice_crosswords SET is_active=1-is_active WHERE id=?")->execute([(int)$_POST['id']]);
            setFlash('success', 'Đã đổi trạng thái.');
        } elseif ($a === 'delete' || $a === 'bulk_delete') {
            $ids = $a === 'delete' ? [(int)$_POST['id']] : pa_ids($_POST['ids'] ?? []);
            if ($ids) {
                $in = implode(',', array_fill(0, count($ids), '?'));
                $pdo->prepare("DELETE FROM practice_crosswords WHERE id IN($in)")->execute($ids);
            }
            setFlash('success', 'Đã xóa ô chữ và clue liên quan.');
        }
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
    header('Location: crosswords.php');
    exit;
}
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$diff = $_GET['difficulty'] ?? '';
$limit = pa_page_size();
$page = pa_page();
$where = [];
$par = [];
if ($q !== '') {
    $where[] = 'c.title LIKE ? ESCAPE "\\\\"';
    $par[] = pa_like($q);
}
if (in_array($status, ['active', 'inactive'], true)) {
    $where[] = 'c.is_active=?';
    $par[] = $status === 'active' ? 1 : 0;
}
if (in_array($diff, ['easy', 'medium', 'hard'], true)) {
    $where[] = 'c.difficulty=?';
    $par[] = $diff;
}
$w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$s = $pdo->prepare("SELECT COUNT(*) FROM practice_crosswords c$w");
$s->execute($par);
$total = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT c.*,COUNT(cl.id) clue_count FROM practice_crosswords c LEFT JOIN practice_crossword_clues cl ON cl.crossword_id=c.id $w GROUP BY c.id ORDER BY c.id DESC LIMIT $limit OFFSET " . (($page - 1) * $limit));
$s->execute($par);
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Quản lý Daily Crossword';
require $root . '/includes/header_admin.php'; ?>
<?php require __DIR__ . '/_practice_ui.php'; ?>

<div class="container-fluid py-4 px-3 px-md-4 pa-wrap">
    <div class="pa-head mb-4 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
            <div class="small text-white-50 fw-bold">QUIZTECH · PRACTICE ADMIN</div>
            <h3 class="fw-bold mb-1">Daily Crossword</h3>
            <p class="mb-0 text-white-50">Quản lý bộ ô chữ, kích thước, thời gian và clue.</p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2"><a class="btn btn-light" href="crossword_clues.php"><i class="bi bi-list-ol me-1"></i>Quản lý Clue</a><button class="btn btn-warning" onclick="openEdit()"><i class="bi bi-plus-lg me-1"></i>Thêm ô chữ</button></div>
    </div>
    <?php if (function_exists('getFlash') && ($f = getFlash())): ?><div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show"><?= e($f['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <form class="pa-card p-3 mb-3 pa-toolbar" method="get"><input class="form-control pa-search" name="q" value="<?= e($q) ?>" placeholder="Tìm bộ ô chữ..."><select class="form-select" name="difficulty">
            <option value="">Mọi độ khó</option>
            <option value="easy" <?= $diff === 'easy' ? 'selected' : '' ?>>Dễ</option>
            <option value="medium" <?= $diff === 'medium' ? 'selected' : '' ?>>Trung bình</option>
            <option value="hard" <?= $diff === 'hard' ? 'selected' : '' ?>>Khó</option>
        </select><select class="form-select" name="status">
            <option value="">Mọi trạng thái</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Đang dùng</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Đã khóa</option>
        </select><button class="btn btn-pa">Lọc</button></form>
    <form id="bulkForm" method="post" class="pa-card overflow-hidden"><?php csrf_field(); ?><input name="action" value="bulk_delete" type="hidden">
        <div class="p-3 border-bottom d-flex justify-content-between"><b><?= number_format($total) ?> bộ ô chữ</b><button class="btn btn-outline-danger btn-sm" onclick="return bulkDelete()">Xóa đã chọn</button></div>
        <div class="table-responsive">
            <table class="table pa-table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" onclick="toggleAll(this)"></th>
                        <th>Ô chữ</th>
                        <th>Kích thước</th>
                        <th>Clue</th>
                        <th>Độ khó</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><?php foreach ($rows as $r): ?><tr>
                            <td><input class="rowck form-check-input" name="ids[]" value="<?= $r['id'] ?>" type="checkbox"></td>
                            <td><b><?= e($r['title']) ?></b><br><small class="text-muted"><?= e($r['description'] ?? '') ?></small></td>
                            <td><?= $r['rows_count'] ?>×<?= $r['cols_count'] ?></td>
                            <td><?= $r['clue_count'] ?></td>
                            <td><?= e($r['difficulty']) ?></td>
                            <td><?= $r['time_limit_seconds'] ?>s</td>
                            <td><?= $r['is_active'] ? '<span class="badge bg-success-subtle text-success">Đang dùng</span>' : '<span class="badge bg-secondary-subtle text-secondary">Đã khóa</span>' ?></td>
                            <td class="text-end"><a class="btn btn-sm btn-light text-info" href="crossword_clues.php?crossword_id=<?= $r['id'] ?>"><i class="bi bi-grid-3x3"></i></a><button type="button" class="btn btn-sm btn-light text-primary" onclick='openEdit(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'><i class="bi bi-pencil"></i></button><button type="button" class="btn btn-sm btn-light text-warning" onclick="postOne('toggle',<?= $r['id'] ?>)"><i class="bi bi-lock"></i></button><button type="button" class="btn btn-sm btn-light text-danger" onclick="postOne('delete',<?= $r['id'] ?>)"><i class="bi bi-trash"></i></button></td>
                        </tr><?php endforeach; ?><?php if (!$rows): ?><tr>
                            <td colspan="8" class="pa-empty">Không có bộ ô chữ.</td>
                        </tr><?php endif; ?></tbody>
            </table>
        </div>
    </form>
    <div class="d-flex justify-content-between mt-3"><small class="text-muted">Trang <?= $page ?> · <?= number_format($total) ?> bộ</small><?php pa_render_pagination($total, $limit, $page); ?></div>
</div>
<div class="modal fade" id="editModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form method="post"><?php csrf_field(); ?><input name="action" value="save" type="hidden"><input name="id" id="e_id" type="hidden">
                <div class="modal-header">
                    <h5 class="modal-title">Bộ Daily Crossword</h5><button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12"><label>Tên *</label><input class="form-control" name="title" id="e_title" required></div>
                    <div class="col-12"><label>Mô tả</label><textarea class="form-control" name="description" id="e_description"></textarea></div>
                    <div class="col-md-3"><label>Số dòng</label><input class="form-control" type="number" min="3" max="30" name="rows_count" id="e_rows" value="9"></div>
                    <div class="col-md-3"><label>Số cột</label><input class="form-control" type="number" min="3" max="30" name="cols_count" id="e_cols" value="9"></div>
                    <div class="col-md-3"><label>Độ khó</label><select class="form-select" name="difficulty" id="e_diff">
                            <option value="easy">Dễ</option>
                            <option value="medium">Trung bình</option>
                            <option value="hard">Khó</option>
                        </select></div>
                    <div class="col-md-3"><label>Thời gian (giây)</label><input class="form-control" type="number" name="time_limit_seconds" id="e_time" value="420"></div>
                    <div class="col-12 form-check ms-2"><input class="form-check-input" name="is_active" id="e_active" type="checkbox" checked><label class="form-check-label">Đang sử dụng</label></div>
                </div>
                <div class="modal-footer"><button class="btn btn-pa">Lưu</button></div>
            </form>
        </div>
    </div>
</div>
<form id="oneForm" method="post" class="d-none"><?php csrf_field(); ?><input name="action" id="oneAction"><input name="id" id="oneId"></form>
<script>
    function openEdit(r = {}) {
        e_id.value = r.id || '';
        e_title.value = r.title || '';
        e_description.value = r.description || '';
        e_rows.value = r.rows_count || 9;
        e_cols.value = r.cols_count || 9;
        e_diff.value = r.difficulty || 'medium';
        e_time.value = r.time_limit_seconds || 420;
        e_active.checked = r.id ? Number(r.is_active) === 1 : true;
        bootstrap.Modal.getOrCreateInstance(editModal).show()
    }

    function toggleAll(x) {
        document.querySelectorAll('.rowck').forEach(c => c.checked = x.checked)
    }

    function bulkDelete() {
        if (!document.querySelector('.rowck:checked')) {
            alert('Hãy chọn dữ liệu.');
            return false
        }
        return confirm('Xóa các ô chữ đã chọn?')
    }

    function postOne(a, id) {
        if (a === 'delete' && !confirm('Xóa ô chữ và toàn bộ clue?')) return;
        oneAction.value = a;
        oneId.value = id;
        oneForm.submit()
    }
</script><?php require $root . '/includes/footer.php'; ?>