<?php
// Deprecated unsafe API. Use authenticated Student exam submission flow instead.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
http_response_code(410);
echo json_encode(['ok'=>false,'message'=>'API nộp bài cũ đã đóng. Vui lòng sử dụng chức năng nộp bài trong Student.'],JSON_UNESCAPED_UNICODE);
exit;
