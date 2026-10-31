<?php
/** QuizTech notification service v39. Tables installed by separate migration. */
require_once __DIR__ . '/auth.php';
function qt_notice_user(): array {
    $u = currentUser();
    if (!$u || (int)($u['id'] ?? 0) < 1 || !empty($u['deleted_at']) || strtolower((string)($u['status'] ?? 'active')) !== 'active') {
        throw new RuntimeException('Phiên đăng nhập không hợp lệ hoặc tài khoản đã bị khóa.');
    }
    $r = normalizeRole($u['role'] ?? 'student');
    return ['id'=>(int)$u['id'], 'role'=>$r];
}
function qt_notice_filter(array $u): array {
    // Match exactly the recipient and role, including admin_test compatibility.
    return ["(n.target_role = 'all' OR n.target_role = ? OR (? = 'admin_test' AND n.target_role = 'admin')) AND (n.target_user_id IS NULL OR n.target_user_id = ?) AND (n.expires_at IS NULL OR n.expires_at > NOW())", [$u['role'],$u['role'],$u['id']]];
}
function qt_notice_list(PDO $pdo, array $u): array {
    [$f,$a] = qt_notice_filter($u);
    $sql = "SELECT n.id,n.title,n.body,n.category,n.action_url,n.created_at,n.created_by,
        (SELECT u.name FROM users u WHERE u.id=n.created_by LIMIT 1) AS sender_name,
        (r.user_id IS NOT NULL) AS is_read
        FROM qt_notifications n LEFT JOIN qt_notification_reads r ON r.notification_id=n.id AND r.user_id=?
        WHERE $f ORDER BY n.id DESC LIMIT 30";
    $q=$pdo->prepare($sql); $q->execute(array_merge([$u['id']],$a));
    $items=$q->fetchAll(PDO::FETCH_ASSOC);
    $q=$pdo->prepare("SELECT COUNT(*) FROM qt_notifications n LEFT JOIN qt_notification_reads r ON r.notification_id=n.id AND r.user_id=? WHERE $f AND r.user_id IS NULL");
    $q->execute(array_merge([$u['id']],$a));
    return ['items'=>$items,'unread'=>(int)$q->fetchColumn()];
}
function qt_notice_mark(PDO $pdo,array $u,?int $id): void {
    [$f,$a] = qt_notice_filter($u);
    if ($id !== null && $id < 1) throw new InvalidArgumentException('ID thông báo không hợp lệ.');
    $q=$pdo->prepare('INSERT IGNORE INTO qt_notification_reads(notification_id,user_id) SELECT n.id, ? FROM qt_notifications n WHERE '.$f.($id!==null?' AND n.id = ?':''));
    $params=array_merge([$u['id']],$a,$id!==null?[$id]:[]);
    $q->execute($params);
    if ($id!==null && $q->rowCount()===0) {
      // Read twice is allowed; but non-owned/nonexistent IDs must not be silently accepted.
      $check=$pdo->prepare('SELECT n.id FROM qt_notifications n WHERE '.$f.' AND n.id=? LIMIT 1');
      $check->execute(array_merge($a,[$id]));
      if (!$check->fetchColumn()) throw new InvalidArgumentException('Thông báo không tồn tại hoặc không thuộc tài khoản của bạn.');
    }
}
function qt_notice_url(string $url): string {
    $url=trim($url);
    if ($url==='') return '';
    // No absolute/external URLs, traversal, fragments, encoded slashes or control chars.
    if (strlen($url)>400 || preg_match('~(?:\x00-\x1f|\\|%2f|%5c|\.\.|^/|//|^[a-z]+:)~i',$url)) throw new InvalidArgumentException('Đường dẫn chi tiết phải nằm trong QuizTech.');
    if (!preg_match('~^(student|teacher|admin)/[a-z0-9_/-]+\.php(?:\?[a-z0-9_=&%-]+)?$~i',$url)) throw new InvalidArgumentException('Đường dẫn chi tiết không hợp lệ.');
    return $url;
}
function qt_notice_publish(PDO $pdo,int $actor,string $title,string $body,string $targetRole,?int $targetUser,string $url='',string $category='system'): int {
    $title=trim($title);$body=trim($body);
    if ($title==='' || mb_strlen($title)>180 || mb_strlen($body)<5 || mb_strlen($body)>3000) throw new InvalidArgumentException('Tiêu đề hoặc nội dung không hợp lệ.');
    if (!in_array($targetRole,['all','student','teacher','admin'],true)) throw new InvalidArgumentException('Vai trò nhận không hợp lệ.');
    $url=qt_notice_url($url);
    $st=$pdo->prepare('INSERT INTO qt_notifications (title,body,category,target_role,target_user_id,action_url,created_by) VALUES (?,?,?,?,?,?,?)');
    $st->execute([$title,$body,$category,$targetRole,$targetUser,$url,$actor]);
    return (int)$pdo->lastInsertId();
}
