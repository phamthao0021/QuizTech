<?php
// api/practice/endpoint.php

ob_start();

$root = dirname(__DIR__, 2);

require_once $root . '/includes/config.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';

$practiceService = $root . '/includes/practice_service.php';

if (!file_exists($practiceService)) {
    ob_clean();

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'ok' => false,
        'message' => 'Thiếu file includes/practice_service.php'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

require_once $practiceService;

ob_clean();

pv2_student();

$action = (string) ($_GET['action'] ?? '');

try {

    // =========================================================
    // DASHBOARD
    // =========================================================
    if ($action === 'dashboard') {
        pv2_json([
            'ok' => true,
            'data' => pv2_dashboard(pv2_uid())
        ]);
    }

    // Các action thay đổi dữ liệu bắt buộc CSRF
    pv2_csrf();

    $data = pv2_body();

    // =========================================================
    // START GAME
    // =========================================================
    if ($action === 'start') {

        $gameType = (string) ($data['game_type'] ?? '');
        $count = (int) ($data['count'] ?? 8);

        pv2_json([
            'ok' => true,
            'data' => pv2_start($gameType, $count)
        ]);
    }

    // =========================================================
    // ANSWER
    // =========================================================
    if ($action === 'answer') {

        $sessionId = (int) ($data['session_id'] ?? 0);
        $itemToken = (string) ($data['item_token'] ?? '');
        $answer = (string) ($data['answer'] ?? '');
        $timeout = !empty($data['timeout']);
        $combo = (int) ($data['combo'] ?? 0);

        pv2_json([
            'ok' => true,
            'data' => pv2_answer(
                $sessionId,
                $itemToken,
                $answer,
                $timeout,
                $combo
            )
        ]);
    }

    // =========================================================
    // HINT
    // =========================================================
    if ($action === 'hint') {

        $sessionId = (int) ($data['session_id'] ?? 0);
        $itemToken = (string) ($data['item_token'] ?? '');

        pv2_json([
            'ok' => true,
            'data' => pv2_hint(
                $sessionId,
                $itemToken
            )
        ]);
    }

    // =========================================================
    // FINISH
    // =========================================================
    if ($action === 'finish') {

        $sessionId = (int) ($data['session_id'] ?? 0);
        $timeout = !empty($data['timeout']);

        pv2_json([
            'ok' => true,
            'data' => pv2_finish(
                $sessionId,
                $timeout
            )
        ]);
    }

    // =========================================================
    // RESULT
    // =========================================================
    if ($action === 'result') {

        $sessionId = (int) ($data['session_id'] ?? 0);

        $result = pv2_result($sessionId);

        if (!$result) {
            pv2_json([
                'ok' => false,
                'message' => 'Không tìm thấy kết quả.'
            ], 404);
        }

        pv2_json([
            'ok' => true,
            'data' => $result
        ]);
    }

    // =========================================================
    // RESTART
    // =========================================================
    if ($action === 'restart') {

        global $pdo;

        $sessionId = (int) ($data['session_id'] ?? 0);

        $oldSession = pv2_session(
            $sessionId,
            pv2_uid()
        );

        if (!$oldSession) {
            throw new RuntimeException(
                'Phiên luyện tập không tồn tại.'
            );
        }

        // Chỉ hủy session đang chơi
        if ($oldSession['status'] === 'playing') {

            $stmt = $pdo->prepare("
                UPDATE practice_sessions
                SET
                    status = 'cancelled',
                    completed_at = NOW()
                WHERE id = ?
                  AND user_id = ?
                  AND status = 'playing'
            ");

            $stmt->execute([
                $sessionId,
                pv2_uid()
            ]);
        }

        // Tạo session mới
        $newSession = pv2_start(
            $oldSession['game_type'],
            (int) $oldSession['total_count']
        );

        pv2_json([
            'ok' => true,
            'data' => $newSession
        ]);
    }

    // =========================================================
    // ACTION KHÔNG TỒN TẠI
    // =========================================================
    pv2_json([
        'ok' => false,
        'message' => 'API action không hợp lệ.'
    ], 404);

} catch (Throwable $e) {

    error_log(
        '[QuizTech Practice API] ' .
        $e->getMessage()
    );

    pv2_json([
        'ok' => false,

        // Trong lúc DEV để thông báo thật nhằm tìm lỗi.
        'message' => $e->getMessage()

    ], 400);
}