<?php
/** Best-effort admin alert after feedback was saved. No schema changes. */
function qt_feedback_alert_admin(PDO $pdo, int $actor, int $feedbackId, string $type, string $title): void {
    if ($feedbackId < 1 || $actor < 1) return;
    try {
        require_once __DIR__ . '/qt_notifications.php';
        $labels = ['subject_request' => 'Đề xuất môn học mới', 'question_report' => 'Báo lỗi câu hỏi', 'feedback' => 'Góp ý từ sinh viên'];
        $label = $labels[$type] ?? 'Phản hồi mới';
        // Use existing notification widget; notify both super admin and admin_test.
        qt_notice_publish($pdo, $actor, $label, 'Có yêu cầu mới: ' . mb_substr($title, 0, 140), 'admin', null,
            'admin/approve_items.php?feedback_id=' . $feedbackId, 'system');
    } catch (Throwable $e) {
        // The feedback has already been saved: notification failures must not fail the submission.
        error_log('QuizTech feedback admin notification: ' . $e->getMessage());
    }
}
