
<?php
/**
 * Audit/ownership helpers for Admin Practice.
 * Run the accompanying migration first.
 */

function pa_actor_id(): int {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $u = function_exists('currentUser')
        ? currentUser()
        : ($_SESSION['user'] ?? []);

    return (int)($u['id'] ?? $_SESSION['user_id'] ?? 0);
}

function pa_audit(
    PDO $pdo,
    string $table,
    int $recordId,
    string $action,
    $before = null,
    $after = null
): void {
    $allowed = [
        'practice_crosswords',
        'practice_crossword_clues',
        'practice_concepts',
        'practice_quiz_questions',
        'practice_sessions'
    ];

    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Invalid Practice table');
    }

    $st = $pdo->prepare(
        'INSERT INTO practice_audit_logs
         (actor_id, table_name, record_id, action_name, before_json, after_json)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $st->execute([
        pa_actor_id() ?: null,
        $table,
        $recordId,
        $action,
        $before === null
            ? null
            : json_encode($before, JSON_UNESCAPED_UNICODE),
        $after === null
            ? null
            : json_encode($after, JSON_UNESCAPED_UNICODE)
    ]);
}

function pa_record(
    PDO $pdo,
    string $table,
    int $id
): ?array {
    $allowed = [
        'practice_crosswords',
        'practice_crossword_clues',
        'practice_concepts',
        'practice_quiz_questions',
        'practice_sessions'
    ];

    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Invalid Practice table');
    }

    $st = $pdo->prepare(
        "SELECT * FROM `$table` WHERE id=?"
    );

    $st->execute([$id]);

    $r = $st->fetch(PDO::FETCH_ASSOC);

    return $r ?: null;
}

function pa_audit_change(
    PDO $pdo,
    string $table,
    int $id,
    string $action,
    ?array $before
): void {
    pa_audit(
        $pdo,
        $table,
        $id,
        $action,
        $before,
        pa_record($pdo, $table, $id)
    );
}

function pa_soft_delete(
    PDO $pdo,
    string $table,
    array $ids
): int {
    if (!in_array(
        $table,
        [
            'practice_crosswords',
            'practice_concepts',
            'practice_quiz_questions'
        ],
        true
    )) {
        throw new InvalidArgumentException('Invalid table');
    }

    $n = 0;

    $st = $pdo->prepare(
        "UPDATE `$table`
         SET is_deleted=1, is_active=0
         WHERE id=? AND is_deleted=0"
    );

    foreach (array_unique(array_map('intval', $ids)) as $id) {
        if ($id <= 0) {
            continue;
        }

        $before = pa_record($pdo, $table, $id);

        if (!$before || (int)$before['is_deleted'] === 1) {
            continue;
        }

        $st->execute([$id]);

        if ($st->rowCount()) {
            pa_audit_change(
                $pdo,
                $table,
                $id,
                'delete',
                $before
            );

            $n++;
        }
    }

    return $n;
}
