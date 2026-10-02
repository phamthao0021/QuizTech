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
            $term = trim($_POST['term'] ?? '');
            $def = trim($_POST['definition'] ?? '');
            $cat = trim($_POST['category'] ?? '');
            $exp = trim($_POST['explanation'] ?? '');
            $active = isset($_POST['is_active']) ? 1 : 0;
            if ($term === '' || $def === '') throw new RuntimeException('Thuật ngữ và định nghĩa không được trống.');
            if ($id) {
                $s = $pdo->prepare("UPDATE practice_concepts SET term=?,definition=?,category=?,explanation=?,is_active=? WHERE id=?");
                $s->execute([$term, $def, $cat, $exp, $active, $id]);
            } else {
                $s = $pdo->prepare("INSERT INTO practice_concepts(term,definition,category,explanation,is_active) VALUES(?,?,?,?,?)");
                $s->execute([$term, $def, $cat, $exp, $active]);
            }
            setFlash('success', $id ? 'Đã cập nhật nối cặp.' : 'Đã thêm nối cặp.');
        } elseif ($a === 'toggle') {
            $id = (int)$_POST['id'];
            $pdo->prepare("UPDATE practice_concepts SET is_active=1-is_active WHERE id=?")->execute([$id]);
            setFlash('success', 'Đã đổi trạng thái.');
        } elseif ($a === 'delete' || $a === 'bulk_delete') {
            $ids = $a === 'delete' ? [(int)$_POST['id']] : pa_ids($_POST['ids'] ?? []);
            if ($ids) {
                $in = implode(',', array_fill(0, count($ids), '?'));
                $pdo->prepare("DELETE FROM practice_concepts WHERE id IN($in)")->execute($ids);
            }
            setFlash('success', 'Đã xóa dữ liệu đã chọn.');
        } elseif ($a === 'import') {
            $rows = pa_assoc_rows(pa_csv_rows($_FILES['import_file'] ?? []));
            if (!$rows) throw new RuntimeException('File không có dữ liệu.');
            $pdo->beginTransaction();
            $s = $pdo->prepare("INSERT INTO practice_concepts(term,definition,category,explanation,is_active) VALUES(?,?,?,?,?)");
            $n = 0;
            foreach ($rows as $i => $r) {
                $term = trim($r['term'] ?? '');
                $def = trim($r['definition'] ?? '');
                if ($term === '' || $def === '') throw new RuntimeException('Dòng ' . ($i + 2) . ' thiếu term/definition.');
                $s->execute([$term, $def, trim($r['category'] ?? ''), trim($r['explanation'] ?? ''), (int)($r['is_active'] ?? 1) ? 1 : 0]);
                $n++;
            }
            $pdo->commit();
            setFlash('success', "Import thành công $n dòng.");
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', $e->getMessage());
    }
    header('Location: concepts.php');
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
    $where[] = '(term LIKE ? ESCAPE "\\\\" OR definition LIKE ? ESCAPE "\\\\" OR category LIKE ? ESCAPE "\\\\")';
    $x = pa_like($q);
    array_push($par, $x, $x, $x);
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
$s = $pdo->prepare("SELECT COUNT(*) FROM practice_concepts$w");
$s->execute($par);
$total = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT * FROM practice_concepts$w ORDER BY id DESC LIMIT $limit OFFSET " . (($page - 1) * $limit));
$s->execute($par);
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
$cats = $pdo->query("SELECT DISTINCT category FROM practice_concepts WHERE category IS NOT NULL AND category<>'' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$page_title = 'Quản lý Nối cặp từ';
require $root . '/includes/header_admin.php';
?>
<?php require __DIR__ . '/_practice_ui.php'; ?>
<div class="container-fluid py-4 px-3 px-md-4 pa-wrap">
    <div class="pa-head mb-4 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
            <div class="small text-white-50 fw-bold">QUIZTECH · PRACTICE ADMIN</div>
            <h3 class="fw-bold mb-1">Nối cặp từ</h3>
            <p class="mb-0 text-white-50">Quản lý thuật ngữ, định nghĩa và bộ dữ liệu luyện tập.</p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2"><button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Import Excel/CSV</button><button class="btn btn-warning" onclick="openEdit()"><i class="bi bi-plus-lg me-1"></i>Thêm mới</button></div>
    </div>
    <?php if (function_exists('getFlash') && ($f = getFlash())): ?><div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show"><?= e($f['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <form class="pa-card p-3 mb-3 pa-toolbar" method="get"><input class="form-control pa-search" name="q" value="<?= e($q) ?>" placeholder="Tìm thuật ngữ, định nghĩa..."><select class="form-select" name="category">
            <option value="">Tất cả chủ đề</option><?php foreach ($cats as $c): ?><option <?= ($cat === $c ? 'selected' : '') ?> value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
        </select><select class="form-select" name="status">
            <option value="">Mọi trạng thái</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Đang dùng</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Đã khóa</option>
        </select><button class="btn btn-pa">Lọc</button></form>
    <form id="bulkForm" method="post" class="pa-card overflow-hidden"><?php csrf_field(); ?><input type="hidden" name="action" value="bulk_delete">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center"><b><?= number_format($total) ?> bản ghi</b><button class="btn btn-outline-danger btn-sm" onclick="return bulkDelete()">Xóa đã chọn</button></div>
        <div class="table-responsive pa-desktop">
            <table class="table pa-table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" onclick="toggleAll(this)"></th>
                        <th>Thuật ngữ</th>
                        <th>Định nghĩa</th>
                        <th>Chủ đề</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?><tr>
                            <td><input class="form-check-input rowck" type="checkbox" name="ids[]" value="<?= $r['id'] ?>"></td>
                            <td><b><?= e($r['term']) ?></b></td>
                            <td style="max-width:420px"><?= e($r['definition']) ?></td>
                            <td><?= e($r['category'] ?: '—') ?></td>
                            <td><span class="badge <?= $r['is_active'] ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>"><?= $r['is_active'] ? 'Đang dùng' : 'Đã khóa' ?></span></td>
                            <td class="text-end pa-actions"><button type="button" class="btn btn-sm btn-light text-primary" onclick='openEdit(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'><i class="bi bi-pencil"></i></button><button type="submit" name="action" value="toggle" class="btn btn-sm btn-light text-warning" formaction="concepts.php" onclick="this.form.querySelector('#singleId').value=<?= $r['id'] ?>"><i class="bi bi-lock"></i></button><button type="button" class="btn btn-sm btn-light text-danger" onclick="singleDelete(<?= $r['id'] ?>)"><i class="bi bi-trash"></i></button></td>
                        </tr><?php endforeach; ?>
                    <?php if (!$rows): ?><tr>
                            <td colspan="6" class="pa-empty">Không tìm thấy dữ liệu.</td>
                        </tr><?php endif; ?></tbody>
            </table>
        </div>
        <input type="hidden" id="singleId" name="id">
    </form>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Trang <?= $page ?> · <?= number_format($total) ?> bản ghi</small>
        <?php pa_render_pagination($total, $limit, $page); ?></div>
</div>

<div class="modal fade" id="editModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form method="post" action="concepts.php"><?php csrf_field(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="e_id">
                <div class="modal-header">
                    <h5 class="modal-title">Nối cặp từ</h5><button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-6"><label class="form-label">Thuật ngữ *</label><input class="form-control" name="term" id="e_term" required></div>
                    <div class="col-md-6"><label class="form-label">Chủ đề</label><input class="form-control" name="category" id="e_category"></div>
                    <div class="col-12"><label class="form-label">Định nghĩa *</label><textarea class="form-control" rows="3" name="definition" id="e_definition" required></textarea></div>
                    <div class="col-12"><label class="form-label">Giải thích</label><textarea class="form-control" rows="2" name="explanation" id="e_explanation"></textarea></div>
                    <div class="col-12 form-check ms-2"><input class="form-check-input" type="checkbox" name="is_active" id="e_active" checked><label class="form-check-label">Đang sử dụng</label></div>
                </div>
                <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal" type="button">Hủy</button><button class="btn btn-pa">Lưu</button></div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="importModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form method="post" enctype="multipart/form-data"><?php csrf_field(); ?><input type="hidden" name="action" value="import">
                <div class="modal-header">
                    <h5 class="modal-title">Import Nối cặp</h5><button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><input class="form-control" type="file" name="import_file" accept=".csv,.xlsx" required><small class="text-muted">Cột: term, definition, category, explanation, is_active. Tối đa 5MB.</small></div>
                <div class="modal-footer"><button class="btn btn-pa">Import</button></div>
            </form>
        </div>
    </div>
</div>
<form id="deleteForm" method="post" action="concepts.php" class="d-none"><?php csrf_field(); ?><input name="action" value="delete"><input name="id" id="deleteId"></form>
<script>
    function openEdit(r = {}) {
        e_id.value = r.id || '';
        e_term.value = r.term || '';
        e_category.value = r.category || '';
        e_definition.value = r.definition || '';
        e_explanation.value = r.explanation || '';
        e_active.checked = r.id ? Number(r.is_active) === 1 : true;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')).show()
    }

    function toggleAll(x) {
        document.querySelectorAll('.rowck').forEach(c => c.checked = x.checked)
    }

    function bulkDelete() {
        if (!document.querySelector('.rowck:checked')) {
            alert('Hãy chọn dữ liệu.');
            return false
        }
        return confirm('Xóa các dòng đã chọn?')
    }

    function singleDelete(id) {
        if (confirm('Xóa dữ liệu này?')) {
            deleteId.value = id;
            deleteForm.submit()
        }
    }
</script><?php require $root . '/includes/footer.php'; ?>