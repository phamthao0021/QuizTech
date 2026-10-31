<?php

/** Teacher-owned Practice CRUD, backed by existing Practice tables + additive migration. */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/practice_audit.php';
function qt_tp_config(string $game): array
{
  $configs = [
    'concepts' => ['table' => 'practice_concepts', 'label' => 'Nối cặp thuật ngữ', 'fields' => ['term' => 'Thuật ngữ', 'definition' => 'Định nghĩa', 'category' => 'Chủ đề', 'explanation' => 'Giải thích'], 'required' => ['term', 'definition']],
    'quick_quiz' => ['table' => 'practice_quiz_questions', 'label' => 'Phản xạ nhanh', 'fields' => ['question' => 'Câu hỏi', 'option_a' => 'Đáp án A', 'option_b' => 'Đáp án B', 'option_c' => 'Đáp án C', 'option_d' => 'Đáp án D', 'correct_answer' => 'Đáp án đúng', 'category' => 'Chủ đề', 'explanation' => 'Giải thích', 'time_limit_seconds' => 'Giây/câu'], 'required' => ['question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer']],
    'crosswords' => ['table' => 'practice_crosswords', 'label' => 'Ô chữ CNTT', 'fields' => ['title' => 'Tên bộ ô chữ', 'description' => 'Mô tả', 'rows_count' => 'Số hàng', 'cols_count' => 'Số cột', 'difficulty' => 'Độ khó', 'time_limit_seconds' => 'Thời gian (giây)'], 'required' => ['title']]
  ];
  if (!isset($configs[$game])) throw new InvalidArgumentException('Game không hợp lệ.');
  return $configs[$game];
}
function qt_tp_guard(): array
{
  $u = currentUser();
  if (!$u || normalizeRole($u['role'] ?? '') !== 'teacher' || strtolower((string)($u['status'] ?? 'active')) !== 'active') {
    http_response_code(403);
    exit('Chỉ tài khoản Teacher đang hoạt động được quản lý luyện tập.');
  }
  return $u;
}
function qt_tp_data(string $game, array $input): array
{
  $cfg = qt_tp_config($game);
  $vals = [];
  foreach ($cfg['fields'] as $name => $label) {
    $value = trim((string)($input[$name] ?? ''));
    if (in_array($name, $cfg['required'], true) && $value === '') throw new InvalidArgumentException($label . ' không được bỏ trống.');
    $max = [
      'term' => 180,
      'definition' => 700,
      'category' => 80,
      'title' => 180,
      'description' => 500,
      'question' => 900,
      'option_a' => 500,
      'option_b' => 500,
      'option_c' => 500,
      'option_d' => 500,
      'correct_answer' => 1,
      'difficulty' => 10,
      'explanation' => 3000
    ];
    if (mb_strlen($value) > ($max[$name] ?? 40)) throw new InvalidArgumentException($label . ' quá dài.');
    $vals[$name] = $value;
  }
  if ($game === 'quick_quiz') {
    $ans = strtoupper($vals['correct_answer']);
    if (!in_array($ans, ['A', 'B', 'C', 'D'], true)) throw new InvalidArgumentException('Đáp án đúng phải là A/B/C/D.');
    $vals['correct_answer'] = $ans;
    $t = $vals['time_limit_seconds'] === '' ? 5 : (int)$vals['time_limit_seconds'];
    if ($t !== 5) throw new InvalidArgumentException('Phản xạ nhanh được cố định 5 giây/câu.');
    $vals['time_limit_seconds'] = $t;
  }
  if ($game === 'crosswords') {
    $rows = $vals['rows_count'] === '' ? 9 : (int)$vals['rows_count'];
    $cols = $vals['cols_count'] === '' ? 9 : (int)$vals['cols_count'];
    if ($rows < 3 || $rows > 30 || $cols < 3 || $cols > 30) throw new InvalidArgumentException('Lưới ô chữ phải từ 3–30 ô mỗi chiều.');
    $vals['rows_count'] = $rows;
    $vals['cols_count'] = $cols;
    $d = $vals['difficulty'] ?: 'medium';
    if (!in_array($d, ['easy', 'medium', 'hard'], true)) throw new InvalidArgumentException('Độ khó không hợp lệ.');
    $vals['difficulty'] = $d;
    $t = $vals['time_limit_seconds'] === '' ? 120 : (int)$vals['time_limit_seconds'];
    if ($t !== 120) throw new InvalidArgumentException('Ô chữ được cố định 120 giây.');
    $vals['time_limit_seconds'] = $t;
  }
  return $vals;
}
function qt_tp_owned(PDO $pdo, string $table, int $id, int $uid): bool
{
  $s = $pdo->prepare("SELECT 1 FROM `$table` WHERE id=? AND created_by=? AND is_deleted=0 LIMIT 1");
  $s->execute([$id, $uid]);
  return (bool)$s->fetchColumn();
}
function qt_tp_save(PDO $pdo, string $game, array $input, int $uid, int $id = 0): int
{
  $cfg = qt_tp_config($game);
  $table = $cfg['table'];
  $vals = qt_tp_data($game, $input);
  $ownTransaction = !$pdo->inTransaction();
  if ($ownTransaction) $pdo->beginTransaction();
  try {
    $before = null;
    if ($id > 0) {
      $q = $pdo->prepare("SELECT * FROM `$table` WHERE id=? AND created_by=? AND is_deleted=0 FOR UPDATE");
      $q->execute([$id, $uid]);
      $before = $q->fetch(PDO::FETCH_ASSOC);
      if (!$before) throw new RuntimeException('Không có quyền sửa nội dung của người khác.');
      if ((int)$before['is_active'] === 1) throw new RuntimeException('Vui lòng ẩn nội dung trước khi chỉnh sửa.');
    }
    $fields = array_keys($vals);
    $params = array_values($vals);
    if ($id) {
      $set = implode(',', array_map(fn($f) => "`$f`=?", $fields));
      $st = $pdo->prepare("UPDATE `$table` SET $set WHERE id=? AND created_by=? AND is_deleted=0");
      $st->execute(array_merge($params, [$id, $uid]));
      pa_audit_change($pdo, $table, $id, 'update', $before);
    } else {
      $sqlFields = implode(',', array_map(fn($f) => "`$f`", $fields));
      $placeholders = implode(',', array_fill(0, count($fields), '?'));
      $st = $pdo->prepare("INSERT INTO `$table` ($sqlFields,created_by,is_deleted,is_active) VALUES ($placeholders,?,0,0)");
      $st->execute(array_merge($params, [$uid]));
      $id = (int)$pdo->lastInsertId();
      pa_audit_change($pdo, $table, $id, 'create', null);
    }
    if ($ownTransaction) $pdo->commit();
    return $id;
  } catch (Throwable $e) {
    if ($ownTransaction && $pdo->inTransaction()) $pdo->rollBack();
    throw $e;
  }
}
function qt_tp_validate_crossword(PDO $pdo, int $cwId): void
{
  $st = $pdo->prepare('SELECT rows_count,cols_count FROM practice_crosswords WHERE id=?');
  $st->execute([$cwId]);
  $cw = $st->fetch(PDO::FETCH_ASSOC);
  if (!$cw) throw new RuntimeException('Không tìm thấy bộ ô chữ.');
  $st = $pdo->prepare('SELECT direction,row_start,col_start,answer FROM practice_crossword_clues WHERE crossword_id=?');
  $st->execute([$cwId]);
  $clues = $st->fetchAll(PDO::FETCH_ASSOC);
  $across = 0;
  $down = 0;
  $grid = [];
  foreach ($clues as $c) {
    $word = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$c['answer']));
    $dir = $c['direction'];
    $r = (int)$c['row_start'];
    $col = (int)$c['col_start'];
    if ($dir === 'across') $across++;
    elseif ($dir === 'down') $down++;
    else throw new RuntimeException('Hướng ô chữ không hợp lệ.');
    if (strlen($word) < 2 || $r < 0 || $col < 0) throw new RuntimeException('Từ hoặc tọa độ không hợp lệ.');
    for ($i = 0; $i < strlen($word); $i++) {
      $y = $r + ($dir === 'down' ? $i : 0);
      $x = $col + ($dir === 'across' ? $i : 0);
      if ($y >= (int)$cw['rows_count'] || $x >= (int)$cw['cols_count']) throw new RuntimeException('Từ vượt ngoài lưới.');
      $key = "$y:$x";
      if (isset($grid[$key]) && $grid[$key] !== $word[$i]) throw new RuntimeException('Các từ giao nhau không khớp tại ô ' . $key . '.');
      $grid[$key] = $word[$i];
    }
  }
  if ($across < 1 || $down < 1) throw new RuntimeException('Cần ít nhất một gợi ý ngang và một gợi ý dọc.');
}
function qt_tp_action(PDO $pdo, string $game, string $action, array $ids, int $uid): int
{
  $t = qt_tp_config($game)['table'];
  $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($n) => $n > 0)));
  if (!$ids || count($ids) > 100) throw new InvalidArgumentException('Chọn 1–100 nội dung hợp lệ.');
  if (!in_array($action, ['delete', 'hide', 'publish'], true)) throw new InvalidArgumentException('Hành động không hợp lệ.');
  $in = implode(',', array_fill(0, count($ids), '?'));
  $ownTransaction = !$pdo->inTransaction();
  if ($ownTransaction) $pdo->beginTransaction();
  try {
    $st = $pdo->prepare("SELECT * FROM `$t` WHERE id IN($in) AND created_by=? AND is_deleted=0 FOR UPDATE");
    $st->execute(array_merge($ids, [$uid]));
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) !== count($ids)) throw new RuntimeException('Có nội dung không thuộc quyền của bạn.');
    if ($game === 'crosswords' && $action === 'publish') foreach ($ids as $cwId) qt_tp_validate_crossword($pdo, $cwId);
    $set = $action === 'delete' ? 'is_deleted=1,is_active=0' : ($action === 'hide' ? 'is_active=0' : 'is_active=1');
    $st = $pdo->prepare("UPDATE `$t` SET $set WHERE id IN($in) AND created_by=? AND is_deleted=0");
    $st->execute(array_merge($ids, [$uid]));
    foreach ($rows as $row) pa_audit_change($pdo, $t, (int)$row['id'], $action, $row);
    if ($ownTransaction) $pdo->commit();
    return count($ids);
  } catch (Throwable $e) {
    if ($ownTransaction && $pdo->inTransaction()) $pdo->rollBack();
    throw $e;
  }
}
