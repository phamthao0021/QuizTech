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
$id = (int)($_GET['crossword_id'] ?? $_POST['crossword_id'] ?? 0);
if ($id <= 0) {
    header('Location: crosswords.php');
    exit;
}
$s = $pdo->prepare("SELECT * FROM practice_crosswords WHERE id=?");
$s->execute([$id]);
$cw = $s->fetch(PDO::FETCH_ASSOC);
if (!$cw) {
    http_response_code(404);
    exit('Không tìm thấy ô chữ.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $a = $_POST['action'] ?? '';
    try {
        if ($a === 'save') {
            $cid = (int)($_POST['id'] ?? 0);
            $no = max(1, (int)$_POST['clue_no']);
            $dir = $_POST['direction'] === 'down' ? 'down' : 'across';
            $row = max(0, (int)$_POST['row_start']);
            $col = max(0, (int)$_POST['col_start']);
            $ans = strtoupper(preg_replace('/\s+/u', '', trim($_POST['answer'] ?? '')));
            $clue = trim($_POST['clue'] ?? '');
            $exp = trim($_POST['explanation'] ?? '');
            if ($ans === '' || $clue === '') throw new RuntimeException('Đáp án và gợi ý không được trống.');
            $len = mb_strlen($ans);
            if (($dir === 'across' && $col + $len > $cw['cols_count']) || ($dir === 'down' && $row + $len > $cw['rows_count'])) throw new RuntimeException('Đáp án vượt khỏi kích thước lưới.');
            if ($cid) $pdo->prepare("UPDATE practice_crossword_clues SET clue_no=?,direction=?,row_start=?,col_start=?,answer=?,clue=?,explanation=? WHERE id=? AND crossword_id=?")->execute([$no, $dir, $row, $col, $ans, $clue, $exp, $cid, $id]);
            else $pdo->prepare("INSERT INTO practice_crossword_clues(crossword_id,clue_no,direction,row_start,col_start,answer,clue,explanation,sort_order) VALUES(?,?,?,?,?,?,?,?,?)")->execute([$id, $no, $dir, $row, $col, $ans, $clue, $exp, $no]);
            setFlash('success', 'Đã lưu clue.');
        } elseif ($a === 'delete') {
            $pdo->prepare("DELETE FROM practice_crossword_clues WHERE id=? AND crossword_id=?")->execute([(int)$_POST['id'], $id]);
            setFlash('success', 'Đã xóa clue.');
        }
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
    header('Location: crossword_clues.php?crossword_id=' . $id);
    exit;
}
$s = $pdo->prepare("SELECT * FROM practice_crossword_clues WHERE crossword_id=? ORDER BY clue_no,direction");
$s->execute([$id]);
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Clue Crossword';
require $root . '/includes/header_admin.php'; ?>
<?php require __DIR__ . '/_practice_ui.php'; ?>

<div class="container-fluid py-4 px-3 px-md-4 pa-wrap">
    <div class="pa-head mb-4 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
            <div class="small text-white-50 fw-bold">QUIZTECH · PRACTICE ADMIN</div>
            <h3 class="fw-bold mb-1">Clue · <?= e($cw['title']) ?></h3>
            <p class="mb-0 text-white-50">Thiết lập vị trí dòng/cột theo chỉ số bắt đầu từ 0.</p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2"><a class="btn btn-light" href="crosswords.php">Quay lại</a><button class="btn btn-warning" onclick="openEdit()">Thêm clue</button></div>
    </div>
    <?php if (function_exists('getFlash') && ($f = getFlash())): ?><div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show"><?= e($f['message']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <div class="pa-card overflow-hidden">
        <div class="table-responsive">
            <table class="table pa-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Hướng</th>
                        <th>Vị trí</th>
                        <th>Đáp án</th>
                        <th>Gợi ý</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody><?php foreach ($rows as $r): ?><tr>
                            <td><?= $r['clue_no'] ?></td>
                            <td><?= e($r['direction']) ?></td>
                            <td>(<?= $r['row_start'] ?>, <?= $r['col_start'] ?>)</td>
                            <td><b><?= e($r['answer']) ?></b></td>
                            <td><?= e($r['clue']) ?></td>
                            <td class="text-end"><button class="btn btn-sm btn-light text-primary" onclick='openEdit(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-light text-danger" onclick="del(<?= $r['id'] ?>)"><i class="bi bi-trash"></i></button></td>
                        </tr><?php endforeach; ?></tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="editModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <form method="post"><?php csrf_field(); ?><input name="action" value="save" type="hidden"><input name="crossword_id" value="<?= $id ?>" type="hidden"><input name="id" id="e_id" type="hidden">
                <div class="modal-header">
                    <h5 class="modal-title">Clue Crossword</h5><button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-md-2"><label>Số</label><input class="form-control" type="number" min="1" name="clue_no" id="e_no" required></div>
                    <div class="col-md-3"><label>Hướng</label><select class="form-select" name="direction" id="e_dir">
                            <option value="across">Across</option>
                            <option value="down">Down</option>
                        </select></div>
                    <div class="col-md-2"><label>Dòng</label><input class="form-control" type="number" min="0" name="row_start" id="e_row"></div>
                    <div class="col-md-2"><label>Cột</label><input class="form-control" type="number" min="0" name="col_start" id="e_col"></div>
                    <div class="col-md-3"><label>Đáp án</label><input class="form-control text-uppercase" name="answer" id="e_answer" required></div>
                    <div class="col-12"><label>Gợi ý</label><textarea class="form-control" name="clue" id="e_clue" required></textarea></div>
                    <div class="col-12"><label>Giải thích</label><textarea class="form-control" name="explanation" id="e_exp"></textarea></div>
                </div>
                <div class="modal-footer"><button class="btn btn-pa">Lưu</button></div>
            </form>
        </div>
    </div>
</div>
<form id="delForm" method="post" class="d-none"><?php csrf_field(); ?><input name="action" value="delete"><input name="crossword_id" value="<?= $id ?>"><input name="id" id="delId"></form>
<script>
    function openEdit(r = {}) {
        e_id.value = r.id || '';
        e_no.value = r.clue_no || 1;
        e_dir.value = r.direction || 'across';
        e_row.value = r.row_start || 0;
        e_col.value = r.col_start || 0;
        e_answer.value = r.answer || '';
        e_clue.value = r.clue || '';
        e_exp.value = r.explanation || '';
        bootstrap.Modal.getOrCreateInstance(editModal).show()
    }

    function del(id) {
        if (confirm('Xóa clue này?')) {
            delId.value = id;
            delForm.submit()
        }
    }
</script><?php require $root . '/includes/footer.php'; ?>