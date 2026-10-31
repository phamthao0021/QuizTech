<?php
require_once dirname(__DIR__, 2) . '/includes/qt_teacher_practice.php';
$teacher = qt_tp_guard();
$uid = (int)$teacher['id'];
$cwId = (int)($_GET['crossword_id'] ?? $_POST['crossword_id'] ?? 0);
if ($cwId <= 0 || !qt_tp_owned($pdo, 'practice_crosswords', $cwId, $uid)) {
    http_response_code(403);
    exit('Không thể chỉnh gợi ý của bộ ô chữ không thuộc quyền của bạn.');
}
$st = $pdo->prepare('SELECT id,title,rows_count,cols_count,is_active FROM practice_crosswords WHERE id=?');
$st->execute([$cwId]);
$cw = $st->fetch(PDO::FETCH_ASSOC);
$err = '';
$ok = '';
if ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST') {
    verify_csrf();
    if ((int)$cw['is_active'] === 1) {
        http_response_code(409);
        exit('Vui lòng ẩn bộ ô chữ trước khi sửa gợi ý.');
    }
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $no = (int)($_POST['clue_no'] ?? 0);
            $dir = (string)($_POST['direction'] ?? '');
            $row = (int)($_POST['row_start'] ?? -1);
            $col = (int)($_POST['col_start'] ?? -1);
            $ans = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim((string)($_POST['answer'] ?? ''))));
            $clue = trim((string)($_POST['clue'] ?? ''));
            $exp = trim((string)($_POST['explanation'] ?? ''));
            $len = strlen($ans);
            if ($no < 1 || !in_array($dir, ['across', 'down'], true) || $row < 0 || $col < 0 || $row >= $cw['rows_count'] || $col >= $cw['cols_count'] || $len < 2 || $len > 60 || mb_strlen($clue) > 500 || $clue === '') throw new InvalidArgumentException('Gợi ý/đáp án/vị trí không hợp lệ.');
            if (($dir === 'across' && $col + $len > $cw['cols_count']) || ($dir === 'down' && $row + $len > $cw['rows_count'])) throw new InvalidArgumentException('Đáp án vượt kích thước ô chữ.');
            // Unique numbers by orientation per crossword.
            $st = $pdo->prepare('SELECT 1 FROM practice_crossword_clues WHERE crossword_id=? AND clue_no=? AND direction=? AND id<>? LIMIT 1');
            $st->execute([$cwId, $no, $dir, $id]);
            if ($st->fetchColumn()) throw new InvalidArgumentException('Trùng số thứ tự và hướng của gợi ý.');
            if ($id) {
                $before = pa_record($pdo, 'practice_crossword_clues', $id);
                if (!$before || (int)$before['crossword_id'] !== $cwId) throw new RuntimeException('Gợi ý không thuộc bộ ô chữ này.');
                $st = $pdo->prepare('UPDATE practice_crossword_clues SET clue_no=?,direction=?,row_start=?,col_start=?,answer=?,clue=?,explanation=? WHERE id=? AND crossword_id=?');
                $st->execute([$no, $dir, $row, $col, $ans, $clue, $exp, $id, $cwId]);
                pa_audit_change($pdo, 'practice_crossword_clues', $id, 'update', $before);
            } else {
                $st = $pdo->prepare('INSERT INTO practice_crossword_clues(crossword_id,clue_no,direction,row_start,col_start,answer,clue,explanation,sort_order) VALUES(?,?,?,?,?,?,?,?,?)');
                $st->execute([$cwId, $no, $dir, $row, $col, $ans, $clue, $exp, $no]);
                pa_audit_change($pdo, 'practice_crossword_clues', (int)$pdo->lastInsertId(), 'create', null);
            }
            $ok = 'Đã lưu gợi ý.';
        } elseif ($action === 'delete') {
            $clueId = (int)($_POST['id'] ?? 0);
            $before = pa_record($pdo, 'practice_crossword_clues', $clueId);
            if (!$before || (int)$before['crossword_id'] !== $cwId) throw new RuntimeException('Không có quyền xóa gợi ý này.');
            $st = $pdo->prepare('DELETE FROM practice_crossword_clues WHERE id=? AND crossword_id=?');
            $st->execute([$clueId, $cwId]);
            pa_audit($pdo, 'practice_crossword_clues', $clueId, 'delete', $before, null);
            $ok = 'Đã xóa gợi ý.';
        }
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}
$st = $pdo->prepare('SELECT * FROM practice_crossword_clues WHERE crossword_id=? ORDER BY clue_no,direction');
$st->execute([$cwId]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$page_title = 'Gợi ý Ô chữ CNTT';
include dirname(__DIR__, 2) . '/includes/header_teacher.php';
?>
<div class="container-fluid qt-v39-center">
    <div class="qt-v39-hero">
        <div class="qt-v39-eyebrow">TEACHER PRACTICE · CROSSWORD CLUES</div>
        <h1>Gợi ý: <?= e($cw['title']) ?></h1>
        <p>Lưới <?= (int)$cw['rows_count'] ?> hàng × <?= (int)$cw['cols_count'] ?> cột · Tọa độ tính từ 0.</p>
    </div>
    <a class="btn btn-outline-primary mb-3" href="manage.php?game=crosswords">← Danh sách Ô chữ</a>
    <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?><?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <section class="qt-v39-box">
                <h2 class="h5 mb-3">Gợi ý đã tạo</h2>
                <div class="table-responsive">
                    <table data-qt-mobile="cards" class="table align-middle">
                        <thead>
                            <tr>
                                <th>Thứ tự</th>
                                <th>Hướng</th>
                                <th>Vị trí</th>
                                <th>Đáp án</th>
                                <th>Gợi ý</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?><tr>
                                    <td><?= (int)$r['clue_no'] ?></td>
                                    <td><?= e($r['direction']) ?></td>
                                    <td><?= (int)$r['row_start'] ?>,<?= (int)$r['col_start'] ?></td>
                                    <td><?= e($r['answer']) ?></td>
                                    <td><?= e($r['clue']) ?></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-primary" onclick='editClue(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP) ?>)'>Sửa</button>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Xóa gợi ý?')"><?php csrf_field(); ?><input name="crossword_id" type="hidden" value="<?= $cwId ?>"><input name="action" type="hidden" value="delete"><button type="submit" name="id" value="<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger">Xóa</button></form>
                                    </td>
                                </tr><?php endforeach; ?>
                            <?php if (!$rows): ?><tr>
                                    <td colspan="6" class="text-muted text-center">Chưa có gợi ý.</td>
                                </tr><?php endif; ?></tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-5">
            <section class="qt-v39-box">
                <h2 class="h5 mb-3">Thêm / Sửa gợi ý</h2>
                <form method="post" id="clueForm"><?php csrf_field(); ?><input type="hidden" name="crossword_id" value="<?= $cwId ?>"><input type="hidden" name="action" value="save"><input type="hidden" id="clueId" name="id">
                    <div class="row g-2">
                        <div class="col-4"><label class="form-label">Số</label><input name="clue_no" type="number" min="1" id="clueNo" required class="form-control"></div>
                        <div class="col-8"><label class="form-label">Hướng</label><select name="direction" id="clueDir" class="form-select">
                                <option value="across">Across (ngang)</option>
                                <option value="down">Down (dọc)</option>
                            </select></div>
                        <div class="col-6"><label class="form-label">Dòng</label><input name="row_start" id="clueRow" type="number" min="0" required class="form-control"></div>
                        <div class="col-6"><label class="form-label">Cột</label><input name="col_start" id="clueCol" type="number" min="0" required class="form-control"></div>
                        <div class="col-12"><label class="form-label">Đáp án</label><input name="answer" id="clueAnswer" required maxlength="60" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Gợi ý</label><textarea name="clue" id="clueText" required maxlength="500" class="form-control"></textarea></div>
                        <div class="col-12"><label class="form-label">Giải thích</label><textarea name="explanation" id="clueExp" class="form-control"></textarea></div>
                    </div>
                    <div class="d-flex gap-2 mt-3"><button type="submit" class="btn btn-primary">Lưu gợi ý</button><button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('clueForm').reset();clueId.value=''">Làm mới</button></div>
                </form>
            </section>
        </div>
    </div>
</div>
<script>
    function editClue(r) {
        clueId.value = r.id || '';
        clueNo.value = r.clue_no || '';
        clueDir.value = r.direction || 'across';
        clueRow.value = r.row_start ?? 0;
        clueCol.value = r.col_start ?? 0;
        clueAnswer.value = r.answer || '';
        clueText.value = r.clue || '';
        clueExp.value = r.explanation || '';
        document.getElementById('clueForm').scrollIntoView({
            behavior: 'smooth'
        })
    }
</script>
<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>