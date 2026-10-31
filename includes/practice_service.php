<?php
// includes/practice_service.php
// Practice V2: server-authoritative answers, daily content and role-aware sessions.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

function pv2_json($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function pv2_uid() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $u = function_exists('currentUser') ? currentUser() : ($_SESSION['user'] ?? null);
    return (int)($u['id'] ?? ($_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 0)));
}

function pv2_role() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $u = function_exists('currentUser') ? currentUser() : ($_SESSION['user'] ?? null);
    $r = $u['role'] ?? ($_SESSION['role'] ?? '');
    return function_exists('normalizeRole') ? normalizeRole($r) : strtolower(trim((string)$r));
}

function pv2_auth_game() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!function_exists('isLoggedIn') || !isLoggedIn()) {
        pv2_json(['ok' => false, 'message' => 'Vui lòng đăng nhập để tham gia luyện tập/game.'], 401);
    }
    $role = pv2_role();
    $allowed = ['student'];
    if (!in_array($role, $allowed, true)) {
        pv2_json(['ok' => false, 'message' => 'Tài khoản không được phân quyền tham gia game.'], 403);
    }
}

function pv2_student() {
    pv2_auth_game();
}

function pv2_csrf() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $a = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $b = (string)($_SESSION['csrf_token'] ?? '');
    if (!$a || !$b || !hash_equals($b, $a)) {
        pv2_json(['ok' => false, 'message' => 'CSRF token không hợp lệ hoặc đã hết hạn. Hãy tải lại trang.'], 419);
    }
}

function pv2_body() {
    $d = json_decode(file_get_contents('php://input') ?: '{}', true);
    return is_array($d) ? $d : [];
}

function pv2_token() {
    return bin2hex(random_bytes(16));
}

function pv2_seed($type) {
    return sprintf('%u', crc32(date('Y-m-d') . '|' . $type));
}

function pv2_session($id, $uid, $lock = false) {
    global $pdo;
    $q = "SELECT *, UNIX_TIMESTAMP(started_at) AS started_epoch FROM practice_sessions WHERE id=? AND user_id=? LIMIT 1" . ($lock ? ' FOR UPDATE' : '');
    $s = $pdo->prepare($q);
    $s->execute([(int)$id, (int)$uid]);
    return $s->fetch(PDO::FETCH_ASSOC);
}

function pv2_snap($s) {
    $x = json_decode($s['snapshot'] ?? '{}', true);
    return is_array($x) ? $x : [];
}

function pv2_pick_daily($table, $where = 'is_active=1 AND is_deleted=0') {
    global $pdo;
    try {
        $rows = $pdo->query("SELECT * FROM {$table} WHERE {$where} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback nếu không có cột is_deleted
        $rows = $pdo->query("SELECT * FROM {$table} WHERE is_active=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    }
    if (!$rows) return null;
    $i = (int)(pv2_seed($table) % count($rows));
    return $rows[$i];
}

function pv2_active_rows($table) {
    global $pdo;
    if (!in_array($table, ['practice_crosswords','practice_concepts','practice_quiz_questions'], true)) throw new RuntimeException('Bảng dữ liệu không hợp lệ.');
    try { return $pdo->query("SELECT * FROM {$table} WHERE is_active=1 AND is_deleted=0 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC); }
    catch (PDOException $e) { return $pdo->query("SELECT * FROM {$table} WHERE is_active=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC); }
}
function pv2_start($type, $count = 8) {
    global $pdo;
    $uid = pv2_uid();
    if ($uid <= 0) throw new RuntimeException('Không tìm thấy ID người dùng. Vui lòng đăng nhập lại.');
    if (!in_array($type, ['crossword', 'concept_match', 'quick_quiz'], true)) {
        throw new RuntimeException('Chế độ chơi không hợp lệ.');
    }

    $snap = [];
    $contentId = null;

    if ($type === 'crossword') {
        // Tìm ô chữ active
        $cwList = pv2_active_rows('practice_crosswords');
        if (!$cwList) throw new RuntimeException('Hiện chưa có bộ Daily Crossword nào được kích hoạt.');

        // Chọn ô chữ theo seed ngày nhưng ưu tiên bộ có clues
        $cw = null;
        $cl = [];
        $seedIdx = (int)(pv2_seed('practice_crosswords') % count($cwList));
        
        // Thử theo seedIdx trước, nếu ko có clues thì duyệt tìm bộ có clues
        for ($k = 0; $k < count($cwList); $k++) {
            $candidate = $cwList[($seedIdx + $k) % count($cwList)];
            $st = $pdo->prepare("SELECT * FROM practice_crossword_clues WHERE crossword_id=? ORDER BY sort_order, id");
            $st->execute([$candidate['id']]);
            $candidateClues = $st->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($candidateClues)) {
                $cw = $candidate;
                $cl = $candidateClues;
                break;
            }
        }

        if (!$cw || !$cl) {
            throw new RuntimeException('Bộ Daily Crossword chưa có gợi ý (clues). Vui lòng thêm clue trước khi chơi.');
        }

        // Dedupe exact duplicates in-memory; retain original database rows.
        $unique = [];
        foreach ($cl as $candidate) {
            $key = implode('|', [$candidate['clue_no'], $candidate['direction'], $candidate['row_start'], $candidate['col_start'], strtoupper(trim($candidate['answer']))]);
            if (!isset($unique[$key])) $unique[$key] = $candidate;
        }
        $cl = array_values($unique);
        foreach ($cl as &$x) {
            $x['token'] = pv2_token();
            $x['answer'] = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper((string)$x['answer'])));
        }
        unset($x);

        // Reject malformed grids before creating a paid/scored session.
        $grid = [];
        foreach ($cl as $x) {
            $dir = $x['direction'];
            $word = $x['answer'];
            $row = (int)$x['row_start']; $col = (int)$x['col_start'];
            if (!in_array($dir, ['across', 'down'], true) || $word === '' || $row < 0 || $col < 0) throw new RuntimeException('Dữ liệu ô chữ không hợp lệ.');
            for ($i=0; $i<strlen($word); $i++) {
                $r = $row + ($dir === 'down' ? $i : 0);
                $c = $col + ($dir === 'across' ? $i : 0);
                if ($r >= (int)$cw['rows_count'] || $c >= (int)$cw['cols_count']) throw new RuntimeException('Từ vượt ngoài lưới ô chữ.');
                $key = "$r:$c";
                if (isset($grid[$key]) && $grid[$key] !== $word[$i]) throw new RuntimeException('Các từ ô chữ giao nhau không khớp. Hãy kiểm tra dữ liệu quản trị.');
                $grid[$key] = $word[$i];
            }
        }
        $snap = [
            'title' => $cw['title'],
            'description' => $cw['description'],
            'rows' => (int)$cw['rows_count'],
            'cols' => (int)$cw['cols_count'],
            'difficulty' => $cw['difficulty'],
            'time_limit' => 120,
            'clues' => $cl
        ];
        $contentId = (int)$cw['id'];
        $total = count($cl);

    } elseif ($type === 'concept_match') {
        $count = max(4, min(10, (int)$count));
        $all = pv2_active_rows('practice_concepts');
        if (count($all) < 4) {
            throw new RuntimeException('Cần ít nhất 4 cặp khái niệm kích hoạt để bắt đầu Nối Cặp Từ.');
        }
        mt_srand((int)pv2_seed('concept'));
        shuffle($all);
        mt_srand();
        $all = array_slice($all, 0, min($count, count($all)));
        foreach ($all as &$x) {
            $x['term_token'] = pv2_token();
            $x['def_token'] = pv2_token();
        }
        unset($x);
        $snap = ['items' => $all, 'time_limit' => 60];
        $total = count($all);

    } else { // quick_quiz
        $count = in_array((int)$count, [5, 8, 10, 12, 15, 20], true) ? (int)$count : 8;
        $all = pv2_active_rows('practice_quiz_questions');
        if (!$all) throw new RuntimeException('Chưa có câu hỏi Quick Quiz nào đang kích hoạt.');

        mt_srand((int)pv2_seed('quiz'));
        shuffle($all);
        mt_srand();
        $all = array_slice($all, 0, min($count, count($all)));
        foreach ($all as &$x) {
            $x['token'] = pv2_token();
            $x['time_limit_seconds'] = 5;
            $ops = [
                ['src' => 'A', 'text' => $x['option_a']],
                ['src' => 'B', 'text' => $x['option_b']],
                ['src' => 'C', 'text' => $x['option_c']],
                ['src' => 'D', 'text' => $x['option_d']],
            ];
            foreach ($ops as &$o) {
                $o['token'] = pv2_token();
            }
            unset($o);
            shuffle($ops);
            $x['display'] = $ops;
        }
        unset($x);
        $snap = ['items' => $all];
        $total = count($all);
    }

    $st = $pdo->prepare("INSERT INTO practice_sessions(user_id,game_type,daily_key,content_id,snapshot,total_count) VALUES(?,?,CURDATE(),?,?,?)");
    $st->execute([$uid, $type, $contentId, json_encode($snap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $total]);
    $newId = (int)$pdo->lastInsertId();

    return pv2_public($newId, $uid);
}

function pv2_public($id, $uid) {
    $s = pv2_session($id, $uid);
    if (!$s) return null;
    $z = pv2_snap($s);

    if ($s['game_type'] === 'crossword') {
        $cl = [];
        foreach ($z['clues'] as $x) {
            $cl[] = [
                'token' => $x['token'],
                'no' => (int)$x['clue_no'],
                'direction' => $x['direction'],
                'row' => (int)$x['row_start'],
                'col' => (int)$x['col_start'],
                'length' => strlen($x['answer']),
                'clue' => $x['clue']
            ];
        }
        $c = [
            'title' => $z['title'],
            'description' => $z['description'],
            'rows' => (int)$z['rows'],
            'cols' => (int)$z['cols'],
            'difficulty' => $z['difficulty'],
            'time_limit' => (int)$z['time_limit'],
            'clues' => $cl
        ];
    } elseif ($s['game_type'] === 'concept_match') {
        $a = [];
        $b = [];
        foreach ($z['items'] as $x) {
            $a[] = ['token' => $x['term_token'], 'term' => $x['term'], 'category' => $x['category'] ?? 'IT'];
            $b[] = ['token' => $x['def_token'], 'definition' => $x['definition']];
        }
        shuffle($a);
        shuffle($b);
        $c = ['terms' => $a, 'definitions' => $b, 'time_limit' => (int)($z['time_limit'] ?? 240)];
    } else {
        $items = [];
        foreach ($z['items'] as $x) {
            $ops = [];
            foreach ($x['display'] as $o) {
                $ops[] = ['token' => $o['token'], 'text' => $o['text']];
            }
            $items[] = [
                'token' => $x['token'],
                'question' => $x['question'],
                'category' => $x['category'] ?? 'CNTT',
                'time_limit' => (int)($x['time_limit_seconds'] ?? 5),
                'options' => $ops
            ];
        }
        $c = ['items' => $items];
    }

    return [
        'id' => (int)$s['id'],
        'game_type' => $s['game_type'],
        'daily_key' => $s['daily_key'],
        'total' => (int)$s['total_count'],
        'content' => $c,
        'status' => $s['status'],
        'score' => (int)$s['score'],
        'correct_count' => (int)$s['correct_count'],
        'wrong_count' => (int)$s['wrong_count'],
        'started_at' => $s['started_at'],
        'remaining_seconds' => max(0, (int)((pv2_session_deadline($s) ?? time()) - time())),
        'resolved_tokens' => pv2_resolved_tokens((int)$s['id'], $s['game_type']),
        'answered_count' => pv2_answered_count((int)$s['id'], $s['game_type']),
        'resolved_pairs' => pv2_resolved_pairs($s),
        'solved_words' => pv2_solved_words($s)
    ];
}

function pv2_resolved_pairs($s) {
    if ($s['game_type'] !== 'concept_match') return [];
    $resolved = pv2_resolved_tokens((int)$s['id'], $s['game_type']);
    $pairs = [];
    foreach ((pv2_snap($s)['items'] ?? []) as $item) {
        if (in_array($item['term_token'], $resolved, true)) $pairs[] = ['term_token'=>$item['term_token'], 'def_token'=>$item['def_token']];
    }
    return $pairs;
}
function pv2_solved_words($s) {
    if ($s['game_type'] !== 'crossword') return [];
    $resolved = pv2_resolved_tokens((int)$s['id'], $s['game_type']);
    $words = [];
    foreach ((pv2_snap($s)['clues'] ?? []) as $item) {
        if (in_array($item['token'], $resolved, true)) $words[] = ['token'=>$item['token'], 'answer'=>$item['answer']];
    }
    return $words;
}
function pv2_resolved_tokens($sid, $game) {
    global $pdo;
    $sql = 'SELECT DISTINCT item_token FROM practice_answers WHERE session_id=?' . ($game === 'quick_quiz' ? '' : ' AND is_correct=1');
    $st = $pdo->prepare($sql);
    $st->execute([$sid]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}
function pv2_answered_count($sid, $game) {
    return count(pv2_resolved_tokens($sid, $game));
}
function pv2_resume($uid) {
    global $pdo;
    $st = $pdo->prepare("SELECT id FROM practice_sessions WHERE user_id=? AND status='playing' ORDER BY id DESC LIMIT 1");
    $st->execute([$uid]);
    $sid = (int)$st->fetchColumn();
    if (!$sid) return null;
    $pdo->beginTransaction();
    try {
        $session = pv2_session($sid, $uid, true);
        $expired = $session && pv2_expire_locked($session, $uid);
        $pdo->commit();
        if ($expired) return null;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    return pv2_public($sid, $uid);
}
function pv2_find($s, $token) {
    $z = pv2_snap($s);
    if ($s['game_type'] === 'crossword') {
        foreach ($z['clues'] as $x) {
            if (hash_equals($x['token'], $token)) return $x;
        }
    } elseif ($s['game_type'] === 'concept_match') {
        foreach ($z['items'] as $x) {
            if (hash_equals($x['term_token'], $token)) return $x;
        }
    } else {
        foreach ($z['items'] as $x) {
            if (hash_equals($x['token'], $token)) return $x;
        }
    }
    return null;
}

/** Server-side deadline for timed crossword/matching sessions. */
function pv2_session_deadline($s) {
    if (!in_array($s['game_type'], ['crossword', 'concept_match'], true)) return null;
    $snap = pv2_snap($s);
    $limit = (int)($snap['time_limit'] ?? 0);
    if ($limit <= 0) return null;
    $started = isset($s['started_epoch']) ? (int)$s['started_epoch'] : false;
    return $started === false ? null : $started + $limit;
}

/** Mark an expired session exactly once; call with the session row locked. */
function pv2_expire_locked($s, $uid) {
    global $pdo;
    $deadline = pv2_session_deadline($s);
    if ($deadline === null || time() < $deadline || $s['status'] !== 'playing') return false;
    $duration = max(1, time() - (int)$s['started_epoch']);
    $sessionLimit = (int)(pv2_snap($s)['time_limit'] ?? 0);
    if ($sessionLimit > 0 && in_array($s['game_type'], ['crossword','concept_match'], true)) $duration = min($duration, $sessionLimit);
    $stmt = $pdo->prepare("UPDATE practice_sessions SET status='timeout', duration_seconds=?, completed_at=NOW() WHERE id=? AND user_id=? AND status='playing'");
    $stmt->execute([$duration, (int)$s['id'], $uid]);
    return true;
}

/** Start the server-authoritative clock for the next unanswered quick-quiz item. */
function pv2_quiz_activate($sid, $token) {
    global $pdo;
    $uid = pv2_uid();
    $pdo->beginTransaction();
    try {
        $s = pv2_session($sid, $uid, true);
        if (!$s || $s['status'] !== 'playing' || $s['game_type'] !== 'quick_quiz') {
            throw new RuntimeException('Phiên Phản xạ nhanh không còn hoạt động.');
        }
        $z = pv2_snap($s);
        $st = $pdo->prepare('SELECT COUNT(*) FROM practice_answers WHERE session_id=?');
        $st->execute([$sid]);
        $index = (int)$st->fetchColumn();
        $item = $z['items'][$index] ?? null;
        if (!$item || !hash_equals((string)$item['token'], $token)) {
            throw new RuntimeException('Câu hỏi không đúng thứ tự.');
        }
        $limit = max(1, (int)($item['time_limit_seconds'] ?? 5));
        $st = $pdo->prepare('SELECT item_token, started_at, UNIX_TIMESTAMP(started_at) AS started_epoch FROM practice_quiz_clock WHERE session_id=? FOR UPDATE');
        $st->execute([$sid]);
        $clock = $st->fetch(PDO::FETCH_ASSOC);
        if ($clock && !hash_equals((string)$clock['item_token'], $token)) {
            throw new RuntimeException('Đồng hồ của câu trước chưa được xử lý.');
        }
        if (!$clock) {
            $pdo->prepare('INSERT INTO practice_quiz_clock(session_id,item_token,started_at) VALUES(?,?,NOW())')->execute([$sid,$token]);
            $st = $pdo->prepare('SELECT UNIX_TIMESTAMP(started_at) FROM practice_quiz_clock WHERE session_id=?');
            $st->execute([$sid]);
            $started = (int)$st->fetchColumn();
        } else {
            $started = (int)$clock['started_epoch'];
        }
        $remaining = max(0, $started + $limit - time());
        $pdo->commit();
        return ['remaining_seconds'=>$remaining, 'time_limit_seconds'=>$limit, 'question_index'=>$index];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function pv2_answer($sid, $token, $answer, $timeout = false, $combo = 0) {
    global $pdo;
    $uid = pv2_uid();
    $pdo->beginTransaction();
    try {
        $s = pv2_session($sid, $uid, true);
        if (!$s || $s['status'] !== 'playing') throw new RuntimeException('Phiên luyện tập không còn hoạt động.');
        if (pv2_expire_locked($s, $uid)) {
            $pdo->commit();
            throw new RuntimeException('Phiên luyện tập đã hết thời gian. Hãy quay lại trang luyện tập để xem kết quả.');
        }
        $x = pv2_find($s, $token);
        if (!$x) throw new RuntimeException('Nội dung không thuộc phiên này.');
        $game = $s['game_type'];
        $quizClockExpired = false;
        if ($game === 'quick_quiz') {
            $stClock = $pdo->prepare('SELECT item_token, UNIX_TIMESTAMP(started_at) AS started_epoch FROM practice_quiz_clock WHERE session_id=? FOR UPDATE');
            $stClock->execute([$sid]);
            $clock = $stClock->fetch(PDO::FETCH_ASSOC);
            if (!$clock || !hash_equals((string)$clock['item_token'], $token)) {
                throw new RuntimeException('Câu hỏi chưa được kích hoạt hoặc đã được xử lý.');
            }
            $limit = max(1, (int)($x['time_limit_seconds'] ?? 5));
            $quizClockExpired = time() >= (int)$clock['started_epoch'] + $limit;
            if ($quizClockExpired) $timeout = true;
            // A client cannot claim timeout while the authoritative timer is still running.
            if ($timeout && !$quizClockExpired) {
                throw new RuntimeException('Câu hỏi chưa hết thời gian.');
            }
        }
        // Quick Quiz is strictly sequential: a client cannot skip ahead or reorder questions.
        if ($game === 'quick_quiz') {
            $snapshot = pv2_snap($s);
            $items = $snapshot['items'] ?? [];
            $stOrder = $pdo->prepare('SELECT COUNT(DISTINCT item_token) FROM practice_answers WHERE session_id=?');
            $stOrder->execute([$sid]);
            $nextIndex = (int)$stOrder->fetchColumn();
            if (!isset($items[$nextIndex]) || !hash_equals((string)$items[$nextIndex]['token'], $token)) {
                throw new RuntimeException('Câu hỏi không đúng thứ tự hoặc đã được xử lý.');
            }
        }
        $st = $pdo->prepare('SELECT is_correct FROM practice_answers WHERE session_id=? AND item_token=? ORDER BY id');
        $st->execute([$sid, $token]);
        $previous = $st->fetchAll(PDO::FETCH_COLUMN);
        // Crossword and matching allow retries after wrong attempts; resolved items cannot score twice.
        // Quick quiz allows exactly one answer per question, including timeout.
        if (($game === 'quick_quiz' && count($previous)) || in_array(1, array_map('intval', $previous), true)) {
            throw new RuntimeException('Nội dung này đã được ghi nhận.');
        }
        $ex = $x['explanation'] ?? '';
        $correct = false; $points = 0; $correctLabel = '';
        if ($game === 'crossword') {
            $clean = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$answer));
            $correct = !$timeout && hash_equals($x['answer'], $clean);
            $correctLabel = $x['answer'];
            $points = $correct ? 100 : 0;
        } elseif ($game === 'concept_match') {
            $correct = !$timeout && hash_equals($x['def_token'], (string)$answer);
            $correctLabel = $x['definition'];
            $points = $correct ? 100 : 0;
        } else {
            $src = '';
            foreach ($x['display'] as $o) {
                if (hash_equals($o['token'], (string)$answer)) { $src = $o['src']; break; }
            }
            if (!$timeout && $src === '') throw new RuntimeException('Đáp án không hợp lệ.');
            $correct = !$timeout && hash_equals($x['correct_answer'], $src);
            $optKey = 'option_' . strtolower($x['correct_answer']);
            $correctLabel = $x[$optKey] ?? $x['correct_answer'];
            // Combo is computed exclusively from recorded answers; client combo is ignored.
            $st = $pdo->prepare('SELECT is_correct FROM practice_answers WHERE session_id=? ORDER BY id DESC');
            $st->execute([$sid]);
            $streak = 0;
            foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $prior) {
                if (!(int)$prior) break;
                $streak++;
            }
            $points = $timeout ? 0 : ($correct ? 100 + min(50, $streak * 10) : -25);
            $combo = $correct ? $streak + 1 : 0;
        }
        $st = $pdo->prepare('INSERT INTO practice_answers(session_id,item_token,user_answer,is_correct,is_timeout,score_awarded,explanation) VALUES(?,?,?,?,?,?,?)');
        $st->execute([$sid, $token, (string)$answer, (int)$correct, (int)$timeout, $points, $ex]);
        if ($game === 'quick_quiz') $pdo->prepare('DELETE FROM practice_quiz_clock WHERE session_id=?')->execute([$sid]);
        $pdo->prepare('UPDATE practice_sessions SET score=GREATEST(0,score+?), correct_count=correct_count+?, wrong_count=wrong_count+?, max_combo=GREATEST(max_combo,?) WHERE id=? AND user_id=?')
            ->execute([$points, (int)$correct, (int)!$correct, (int)$combo, $sid, $uid]);
        $pdo->commit();
        return ['correct'=>$correct, 'points'=>$points, 'is_timeout'=>(bool)$timeout, 'correct_answer'=>$correctLabel, 'explanation'=>$ex];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function pv2_hint($sid, $token) {
    global $pdo;
    $uid = pv2_uid();
    $pdo->beginTransaction();
    try {
        $s = pv2_session($sid, $uid, true);
        if (!$s || $s['status'] !== 'playing') throw new RuntimeException('Phiên không còn hoạt động.');
        if (pv2_expire_locked($s, $uid)) {
            $pdo->commit();
            throw new RuntimeException('Đã hết thời gian; không thể sử dụng gợi ý.');
        }
        if ($s['game_type'] !== 'crossword') throw new RuntimeException('Chế độ này chưa hỗ trợ gợi ý.');
        $x = pv2_find($s, $token);
        if (!$x) throw new RuntimeException('Không tìm thấy nội dung.');
        $st = $pdo->prepare('SELECT COUNT(*) FROM practice_answers WHERE session_id=? AND item_token=? AND is_correct=1');
        $st->execute([$sid, $token]);
        if ((int)$st->fetchColumn()) throw new RuntimeException('Từ này đã hoàn thành.');
        // A hint always costs points, including repeated requests. UI prevents accidental double clicks.
        $pdo->prepare('UPDATE practice_sessions SET hints_used=hints_used+1, score=GREATEST(0,score-20) WHERE id=? AND user_id=?')->execute([$sid, $uid]);
        $pdo->commit();
        return ['letter' => $x['answer'][0] ?? '', 'index' => 0];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function pv2_finish($sid, $timeout = false) {
    global $pdo;
    $uid = pv2_uid();
    $pdo->beginTransaction();
    try {
        $s = pv2_session($sid, $uid, true);
        if (!$s) throw new RuntimeException('Không tìm thấy phiên luyện tập.');
        if ($s['status'] === 'playing') {
            $expired = pv2_expire_locked($s, $uid);
            if (!$expired) {
                $st = $pdo->prepare('SELECT COUNT(DISTINCT item_token) FROM practice_answers WHERE session_id=?' . ($s['game_type'] === 'quick_quiz' ? '' : ' AND is_correct=1'));
                $st->execute([$sid]);
                $resolved = (int)$st->fetchColumn();
                if ((!$timeout || $s['game_type'] === 'quick_quiz') && $resolved < (int)$s['total_count']) {
                    throw new RuntimeException('Bạn chưa hoàn thành đủ nội dung.');
                }
                // A client cannot forge a timeout before the configured crossword/matching deadline.
                $deadline = pv2_session_deadline($s);
                if ($timeout && $deadline !== null && time() < $deadline) {
                    throw new RuntimeException('Phiên chưa hết thời gian. Hãy tiếp tục chơi hoặc hủy lượt.');
                }
                $duration = max(1, time() - (int)$s['started_epoch']);
                $sessionLimit = (int)(pv2_snap($s)['time_limit'] ?? 0);
                if ($sessionLimit > 0 && in_array($s['game_type'], ['crossword','concept_match'], true)) $duration = min($duration, $sessionLimit);
                $status = $timeout ? 'timeout' : 'completed';
                $pdo->prepare("UPDATE practice_sessions SET status=?, duration_seconds=?, completed_at=NOW() WHERE id=? AND user_id=? AND status='playing'")
                    ->execute([$status, $duration, $sid, $uid]);
            }
        }
        $pdo->commit();
        return pv2_result($sid, $uid);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function pv2_result($sid, $uid = null) {
    global $pdo;
    $uid = $uid ?: pv2_uid();
    $s = pv2_session($sid, $uid);
    if (!$s) return null;
    // Never expose answers or the raw snapshot of an unfinished session.
    if (!in_array($s['status'], ['completed', 'timeout'], true)) return null;

    $total = max(1, (int)$s['total_count']);
    $s['accuracy'] = round(((int)$s['correct_count'] / $total) * 100, 1);
    $stCount = $pdo->prepare('SELECT COUNT(DISTINCT CASE WHEN is_correct=1 THEN item_token END) AS solved, COUNT(DISTINCT CASE WHEN is_timeout=1 THEN item_token END) AS timed_out, COUNT(DISTINCT item_token) AS attempted FROM practice_answers WHERE session_id=?');
    $stCount->execute([$sid]);
    $counts = $stCount->fetch(PDO::FETCH_ASSOC) ?: [];
    $s['resolved_count'] = $s['game_type'] === 'quick_quiz' ? (int)($counts['attempted'] ?? 0) : (int)($counts['solved'] ?? 0);
    $s['timeout_count'] = (int)($counts['timed_out'] ?? 0);
    $s['is_fully_completed'] = $s['status'] === 'completed' && $s['resolved_count'] >= (int)$s['total_count'] && $s['timeout_count'] === 0;

    $ans = [];
    try {
        $st = $pdo->prepare("SELECT item_token,user_answer,is_correct,is_timeout,score_awarded,explanation FROM practice_answers WHERE session_id=? ORDER BY id");
        $st->execute([$sid]);
        $ans = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    $review = [];
    $byToken = [];
    foreach ($ans as $a) {
        $token = (string)$a['item_token'];
        if (!isset($byToken[$token]) || (int)$a['is_correct']) $byToken[$token] = $a;
    }
    $snap = pv2_snap($s);
    $items = $s['game_type'] === 'crossword' ? ($snap['clues'] ?? []) : ($snap['items'] ?? []);
    foreach ($items as $x) {
        $token = (string)($s['game_type'] === 'concept_match' ? ($x['term_token'] ?? '') : ($x['token'] ?? ''));
        $a = $byToken[$token] ?? null;
        if ($s['game_type'] === 'crossword') {
            $review[] = ['label'=>($x['clue_no'] ?? '').' '.($x['direction'] ?? ''), 'prompt'=>$x['clue'] ?? '', 'correct'=>$x['answer'] ?? '', 'ok'=>$a ? (bool)$a['is_correct'] : false, 'explanation'=>$x['explanation'] ?? ''];
        } elseif ($s['game_type'] === 'concept_match') {
            $review[] = ['label'=>$x['term'] ?? '', 'prompt'=>$x['definition'] ?? '', 'correct'=>$x['definition'] ?? '', 'ok'=>$a ? (bool)$a['is_correct'] : false, 'explanation'=>$x['explanation'] ?? ''];
        } else {
            $key = 'option_' . strtolower($x['correct_answer'] ?? 'a');
            $review[] = ['label'=>$x['category'] ?? 'CNTT', 'prompt'=>$x['question'] ?? '', 'correct'=>$x[$key] ?? '', 'ok'=>$a ? (bool)$a['is_correct'] : false, 'explanation'=>$x['explanation'] ?? ''];
        }
    }
    // Snapshot contains correct answers and internal option tokens; never send it to the browser.
    unset($s['snapshot']);
    return ['session' => $s, 'review' => $review];
}

function pv2_dashboard($uid) {
    global $pdo;
    $st = $pdo->prepare("SELECT COUNT(*) sessions, COALESCE(AVG(score),0) avg_score, COALESCE(MAX(score),0) best_score, COALESCE(AVG(CASE WHEN total_count>0 THEN correct_count*100.0/total_count END),0) avg_accuracy, COALESCE(SUM(duration_seconds),0) total_time FROM practice_sessions WHERE user_id=? AND status IN('completed','timeout')");
    $st->execute([$uid]);
    $stats = $st->fetch(PDO::FETCH_ASSOC);

    $st = $pdo->prepare("SELECT game_type, MAX(score) best_score, COUNT(*) plays FROM practice_sessions WHERE user_id=? AND status IN('completed','timeout') GROUP BY game_type");
    $st->execute([$uid]);
    $games = $st->fetchAll(PDO::FETCH_ASSOC);

    $st = $pdo->prepare("SELECT id,game_type,daily_key,score,correct_count,total_count,duration_seconds,status,completed_at FROM practice_sessions WHERE user_id=? ORDER BY id DESC LIMIT 30");
    $st->execute([$uid]);
    $history = $st->fetchAll(PDO::FETCH_ASSOC);

    return ['stats' => $stats, 'games' => $games, 'history' => $history];
}
