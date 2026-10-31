<?php
require_once dirname(__DIR__, 2) . '/includes/qt_teacher_practice.php';
$teacher = qt_tp_guard();
$tid = (int)$teacher['id'];
$game = (string)($_GET['game'] ?? 'concepts');
try {
  $cfg = qt_tp_config($game);
} catch (Throwable $e) {
  http_response_code(404);
  exit('Không tìm thấy game.');
}
$table = $cfg['table'];
$msg = '';
$err = '';
$preview = [];
if ((filter_input(INPUT_SERVER, 'REQUEST_METHOD') ?: 'GET') === 'POST') {
  verify_csrf();
  $act = (string)($_POST['action'] ?? '');
  try {
    if ($act === 'save') {
      qt_tp_save($pdo, $game, $_POST, $tid, (int)($_POST['id'] ?? 0));
      $msg = 'Đã lưu bản nháp. Kiểm tra nội dung rồi nhấn Mở để công bố.';
    } elseif (in_array($act, ['delete', 'hide', 'publish'], true)) {
      qt_tp_action($pdo, $game, $act, [(int)($_POST['id'] ?? 0)], $tid);
      $msg = 'Đã cập nhật trạng thái.';
    } elseif ($act === 'bulk_delete') {
      $count = qt_tp_action($pdo, $game, 'delete', $_POST['ids'] ?? [], $tid);
      $msg = "Đã xóa mềm $count mục.";
    } elseif ($act === 'preview_import') {
      if (empty($_FILES['data_file']) || ($_FILES['data_file']['error'] ?? 1) !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Chưa chọn được file import.');
      $file = $_FILES['data_file'];
      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['csv', 'xlsx'], true) || $file['size'] > 2 * 1024 * 1024) throw new InvalidArgumentException('Chỉ nhận CSV/XLSX tối đa 2MB.');
      $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
      if (!is_file($autoload)) throw new RuntimeException('Chưa có thư viện PhpSpreadsheet. Chạy composer install.');
      require_once $autoload;
      $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($ext === 'xlsx' ? 'Xlsx' : 'Csv');
      $reader->setReadDataOnly(true);
      if ($ext === 'csv' && method_exists($reader, 'setInputEncoding')) $reader->setInputEncoding('UTF-8');
      $sheet = $reader->load($file['tmp_name'])->getActiveSheet();
      $arr = $sheet->rangeToArray('A1:K252', null, true, false, false);
      $keys = array_map(fn($v) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string)$v))), array_shift($arr) ?: []);
      $fields = array_keys($cfg['fields']);
      foreach ($cfg['required'] as $required) {
        if (!in_array($required, $keys, true)) throw new InvalidArgumentException('File thiếu cột: ' . $required);
      }
      if (count(array_filter($keys)) !== count(array_unique(array_filter($keys)))) throw new InvalidArgumentException('Tiêu đề cột bị trùng.');
      $rows = [];
      foreach ($arr as $i => $line) {
        if (!array_filter($line, fn($v) => trim((string)$v) !== '')) continue;
        if (count($rows) >= 200) throw new InvalidArgumentException('Import tối đa 200 dòng một lần.');
        $input = [];
        foreach ($fields as $field) {
          $j = array_search($field, $keys, true);
          $input[$field] = $j === false ? '' : (string)($line[$j] ?? '');
        }
        try {
          $rows[] = qt_tp_data($game, $input);
        } catch (Throwable $e) {
          throw new InvalidArgumentException('Dòng ' . ($i + 2) . ': ' . $e->getMessage());
        }
      }
      if (!$rows) throw new InvalidArgumentException('File không có dòng hợp lệ.');
      $_SESSION['qt_tp_import'] = ['game' => $game, 'uid' => $tid, 'data' => $rows];
      $preview = $rows;
    } elseif ($act === 'confirm_import') {
      $stash = $_SESSION['qt_tp_import'] ?? null;
      if (!$stash || $stash['game'] !== $game || (int)$stash['uid'] !== $tid) throw new InvalidArgumentException('Dữ liệu xem trước đã hết hạn.');
      $pdo->beginTransaction();
      try {
        foreach ($stash['data'] as $row) qt_tp_save($pdo, $game, $row, $tid, 0);
        $pdo->commit();
        $msg = 'Đã nhập ' . count($stash['data']) . ' dòng dưới dạng bản nháp; hãy kiểm tra rồi công bố.';
        unset($_SESSION['qt_tp_import']);
      } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
      }
    }
  } catch (Throwable $e) {
    $err = $e->getMessage();
  }
}
if ($msg && $act !== 'preview_import') {
  $_SESSION['qt_tp_flash'] = $msg;
  header('Location: manage.php?game=' . rawurlencode($game));
  exit;
}
$flash = (string)($_SESSION['qt_tp_flash'] ?? '');
unset($_SESSION['qt_tp_flash']);
if ($flash && !$msg) $msg = $flash;
$q = trim((string)($_GET['q'] ?? ''));
$status = (string)($_GET['status'] ?? '');
$limit = (int)($_GET['per_page'] ?? 10);
if (!in_array($limit, [5, 10, 20, 50], true)) $limit = 10;
$sort = (string)($_GET['sort'] ?? 'newest');
if (!in_array($sort, ['newest', 'oldest', 'az'], true)) $sort = 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$searchfield = $game === 'concepts' ? 'term' : ($game === 'crosswords' ? 'title' : 'question');
$orderSql = $sort === 'oldest' ? 'id ASC' : ($sort === 'az' ? "`$searchfield` ASC, id DESC" : 'id DESC');
$where = ' WHERE created_by=? AND is_deleted=0';
$params = [$tid];
if ($q !== '') {
  $where .= " AND `$searchfield` LIKE ?";
  $params[] = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
}
if ($status === 'active' || $status === 'inactive') {
  $where .= ' AND is_active=?';
  $params[] = $status === 'active' ? 1 : 0;
}
$records = [];
$total = 0;
try {
  $st = $pdo->prepare("SELECT COUNT(*) FROM `$table` $where");
  $st->execute($params);
  $total = (int)$st->fetchColumn();
  $page = min($page, max(1, (int)ceil($total / $limit)));
  $offset = ($page - 1) * $limit;
  $st = $pdo->prepare("SELECT * FROM `$table` $where ORDER BY $orderSql LIMIT $limit OFFSET $offset");
  $st->execute($params);
  $records = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $err = 'Cần chạy migration Practice V2 và Teacher Practice V39 trước khi mở trang này.';
}
$page_title = 'Practice – ' . $cfg['label'];
include dirname(__DIR__, 2) . '/includes/header_teacher.php';
?>
<div class="container-fluid qt-v39-center">
  <div class="qt-v39-hero">
    <div class="qt-v39-eyebrow">TEACHER · PRACTICE</div>
    <h1><?= e($cfg['label']) ?></h1>
    <p>Quản lý nội dung của bạn · Thêm/sửa/ẩn/xóa · CSV/XLSX · Công bố trực tiếp</p>
  </div>
  <nav class="d-flex flex-wrap gap-2 mb-3"><a class="btn btn-outline-primary" href="index.php">Tổng quan</a>
    <?php foreach (['concepts' => 'Nối cặp', 'quick_quiz' => 'Phản xạ nhanh', 'crosswords' => 'Ô chữ CNTT'] as $k => $name): ?><a class="btn <?= $game === $k ? 'btn-primary' : 'btn-outline-primary' ?>" href="?game=<?= e($k) ?>"><?= e($name) ?></a><?php endforeach; ?></nav>
  <?php if ($err): ?><div class="alert alert-danger" role="alert"><?= e($err) ?></div><?php endif; ?>
  <?php if ($msg): ?><div class="alert alert-success" role="status"><?= e($msg) ?></div><?php endif; ?>
  <div class="row g-3">
    <div class="col-12 col-xl-8">
      <div class="qt-v39-box mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h5 fw-bold mb-0">Danh sách của tôi (<?= $total ?>)</h2><button type="button" class="btn btn-primary" onclick="qtEdit()"><i class="bi bi-plus-circle"></i> Thêm mới</button>
        </div>
        <form method="get" class="d-flex flex-wrap gap-2 mb-3"><input type="hidden" name="game" value="<?= e($game) ?>"><input class="form-control flex-grow-1" style="max-width:360px" name="q" placeholder="Tìm kiếm..." value="<?= e($q) ?>"><select name="status" class="form-select" style="width:auto">
            <option value="">Mọi trạng thái</option>
            <option <?= $status === 'active' ? 'selected' : '' ?> value="active">Đang công bố</option>
            <option <?= $status === 'inactive' ? 'selected' : '' ?> value="inactive">Đã ẩn</option>
          </select><select class="form-select" name="sort" aria-label="Sắp xếp" style="width:auto"><option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option><option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Cũ nhất</option><option value="az" <?= $sort === 'az' ? 'selected' : '' ?>>Nội dung A–Z</option></select><select class="form-select" name="per_page" aria-label="Số dòng mỗi trang" style="width:auto"><?php foreach ([5,10,20,50] as $size): ?><option value="<?= $size ?>" <?= $limit === $size ? 'selected' : '' ?>><?= $size ?> dòng</option><?php endforeach; ?></select><button class="btn btn-primary">Lọc</button><a href="?game=<?= e($game) ?>" class="btn btn-outline-secondary">Làm mới</a></form>
        <form method="post" id="qtBulkForm"><?php csrf_field(); ?><input type="hidden" name="action" value="bulk_delete">
          <div class="table-responsive">
            <table data-qt-mobile="cards" class="table align-middle">
              <thead>
                <tr>
                  <th style="width:50px"><input type="checkbox" id="qtAll" aria-label="Chọn tất cả"></th>
                  <th>Nội dung</th>
                  <th>Trạng thái</th>
                  <th>Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($records as $row): ?>
                  <tr>
                    <td><input aria-label="Chọn #<?= (int)$row['id'] ?>" type="checkbox" class="qtSelected" name="ids[]" value="<?= (int)$row['id'] ?>"></td>
                    <td><strong><?= e($row[$searchfield]) ?></strong>
                      <div class="text-muted small">#<?= (int)$row['id'] ?> · <?= e($row['category'] ?? $row['difficulty'] ?? '') ?></div>
                    </td>
                    <td><span class="badge <?= $row['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $row['is_active'] ? 'Công bố' : 'Đã ẩn' ?></span></td>
                    <td>
                      <div class="d-flex gap-1 flex-wrap"><button type="button" class="btn btn-sm btn-outline-primary" onclick='qtEdit(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'>Sửa</button>
                        <?php if ($game === 'crosswords'): ?><a class="btn btn-sm btn-outline-primary" href="clues.php?crossword_id=<?= (int)$row['id'] ?>">Gợi ý</a><?php endif; ?>
                        <button type="submit" form="qtRowAction" name="id" value="<?= (int)$row['id'] ?>" onclick="document.getElementById('qtRowActionAction').value='<?= $row['is_active'] ? 'hide' : 'publish' ?>'" class="btn btn-sm btn-outline-warning"><?= $row['is_active'] ? 'Ẩn' : 'Mở' ?></button>
                        <button type="submit" form="qtRowAction" name="id" value="<?= (int)$row['id'] ?>" onclick="return confirm('Xóa mềm nội dung này?')&& (document.getElementById('qtRowActionAction').value='delete')" class="btn btn-sm btn-outline-danger">Xóa</button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?><tr>
                    <td colspan="4" class="text-center text-muted py-4">Chưa có nội dung phù hợp.</td>
                  </tr><?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2"><button type="submit" class="btn btn-outline-danger" onclick="return confirm('Xóa mềm các nội dung đã chọn?')">Xóa đã chọn <span id="qtSelectedCount">(0)</span></button>
            <div class="d-flex gap-2 flex-wrap align-items-center"><?php if ($page > 1): ?><a class="btn btn-sm btn-outline-secondary" href="?<?= e(http_build_query(['game' => $game, 'q' => $q, 'status' => $status, 'sort' => $sort, 'per_page' => $limit, 'page' => $page - 1])) ?>">Trước</a><?php endif; ?><span class="small text-muted align-self-center">Trang <?= $page ?> / <?= max(1, (int)ceil($total / $limit)) ?> · <?= $total ? (($page - 1) * $limit + 1) : 0 ?>–<?= min($total, $page * $limit) ?> / <?= $total ?></span><?php for ($n = max(1,$page-2); $n <= min(max(1,(int)ceil($total/$limit)),$page+2); $n++): ?><a class="btn btn-sm <?= $n === $page ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?<?= e(http_build_query(['game'=>$game,'q'=>$q,'status'=>$status,'sort'=>$sort,'per_page'=>$limit,'page'=>$n])) ?>"><?= $n ?></a><?php endfor; ?><?php if ($page * $limit < $total): ?><a class="btn btn-sm btn-outline-secondary" href="?<?= e(http_build_query(['game' => $game, 'q' => $q, 'status' => $status, 'sort' => $sort, 'per_page' => $limit, 'page' => $page + 1])) ?>">Sau</a><?php endif; ?></div>
          </div>
        </form>
        <form id="qtRowAction" method="post"><?php csrf_field(); ?><input type="hidden" name="action" id="qtRowActionAction"></form>
      </div>
    </div>
    <div class="col-12 col-xl-4">
      <section class="qt-v39-box">
        <h2 class="h5 fw-bold">Import CSV/XLSX</h2>
        <p class="small text-muted">Dòng tiêu đề gồm tên trường như mẫu bên dưới. Giới hạn 200 dòng/lần, 2MB.</p>
        <div class="small mb-3" style="overflow-wrap:anywhere"><strong>Cột:</strong> <?= e(implode(', ', array_keys($cfg['fields']))) ?></div>
        <form method="post" enctype="multipart/form-data"><?php csrf_field(); ?><input type="hidden" name="action" value="preview_import"><input class="form-control mb-3" type="file" name="data_file" accept=".xlsx,.csv" required><button class="btn btn-primary">Kiểm tra và xem trước</button></form>
        <?php if ($preview): ?>
          <hr>
          <p class="fw-semibold">Dữ liệu hợp lệ: <?= count($preview) ?> dòng.</p>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr><?php foreach (array_slice(array_keys($cfg['fields']), 0, 2) as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr>
              </thead>
              <tbody><?php foreach (array_slice($preview, 0, 5) as $r): ?><tr><?php foreach (array_slice($r, 0, 2) as $cell): ?><td><?= e((string)$cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
            </table>
          </div>
          <form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="confirm_import"><button class="btn btn-success" onclick="return confirm('Nhập <?= count($preview) ?> dòng dưới dạng bản nháp?')">Xác nhận import</button></form><?php endif; ?>
      </section>
    </div>
  </div>
</div>
<div class="modal fade" id="qtEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="qtEditId">
        <div class="modal-header">
          <h2 class="h5 modal-title">Chỉnh sửa <?= e($cfg['label']) ?></h2><button class="btn-close" type="button" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body row g-3">
          <?php foreach ($cfg['fields'] as $key => $label): ?><div class="col-12 <?= in_array($key, ['category', 'difficulty', 'rows_count', 'cols_count', 'time_limit_seconds', 'correct_answer']) ? 'col-md-6' : '' ?>"><label class="form-label" for="qt_<?= e($key) ?>"><?= e($label) ?></label>
              <?php if (in_array($key, ['description', 'definition', 'question', 'explanation'], true)): ?><textarea class="form-control" rows="2" name="<?= e($key) ?>" id="qt_<?= e($key) ?>" <?= in_array($key, $cfg['required'], true) ? 'required' : '' ?>></textarea>
              <?php elseif ($key === 'correct_answer'): ?><select class="form-select" name="correct_answer" id="qt_correct_answer">
                  <option>A</option>
                  <option>B</option>
                  <option>C</option>
                  <option>D</option>
                </select>
              <?php elseif ($key === 'difficulty'): ?><select class="form-select" name="difficulty" id="qt_difficulty">
                  <option value="easy">Dễ</option>
                  <option value="medium">Trung bình</option>
                  <option value="hard">Khó</option>
                </select>
              <?php else: ?><input class="form-control" name="<?= e($key) ?>" id="qt_<?= e($key) ?>" <?= in_array($key, $cfg['required'], true) ? 'required' : '' ?>><?php endif; ?></div><?php endforeach; ?>
        </div>
        <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu bản nháp</button></div>
      </form>
    </div>
  </div>
</div>
<script>
  function qtEdit(row = {}) {
    document.getElementById('qtEditId').value = row.id || '';
    document.querySelectorAll('#qtEditModal [name]').forEach(field => {
      if (field.name !== 'csrf_token' && field.name !== 'action' && field.name !== 'id') field.value = row[field.name] ?? ''
    });
    bootstrap.Modal.getOrCreateInstance(document.getElementById('qtEditModal')).show()
  }
  (function() {
    const all = document.getElementById('qtAll'),
      items = [...document.querySelectorAll('.qtSelected')],
      counter = document.getElementById('qtSelectedCount');

    function upd() {
      const n = items.filter(x => x.checked).length;
      counter.textContent = '(' + n + ')';
      all.checked = n === items.length && n > 0;
    }
    all.addEventListener('change', () => {
      items.forEach(x => x.checked = all.checked);
      upd()
    });
    items.forEach(x => x.addEventListener('change', upd));
  })();
</script>
<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>