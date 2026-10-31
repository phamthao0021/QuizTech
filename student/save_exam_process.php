<?php
// V39: Student endpoint previously created/published exams without a role check.
// Not referenced by UI; disabled. Admin authoring uses admin/save_exam_process.php.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
http_response_code(403);
echo json_encode(['status'=>'error','message'=>'Sinh viên không có quyền tạo đề thi.'],JSON_UNESCAPED_UNICODE);
exit;
