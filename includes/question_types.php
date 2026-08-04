<?php
// includes/question_types.php - Các hàm xử lý dạng câu hỏi (nếu cần)

function render_question_type_badge($type) {
    $badges = array(
        'mcq' => '<span class="badge bg-primary">Trắc nghiệm</span>',
        'multiselect' => '<span class="badge bg-warning text-dark">Chọn nhiều</span>',
        'matching' => '<span class="badge bg-info text-dark">Nối ô</span>',
        'tf' => '<span class="badge bg-success">Đúng/Sai</span>'
    );
    return $badges[$type] ?? '<span class="badge bg-secondary">Khác</span>';
}

function get_question_type_label($type) {
    $labels = array(
        'mcq' => 'Trắc nghiệm',
        'multiselect' => 'Chọn nhiều',
        'matching' => 'Nối ô',
        'tf' => 'Đúng/Sai'
    );
    return $labels[$type] ?? $type;
}