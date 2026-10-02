<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/config.php';require_once '../includes/functions.php';require_once '../includes/auth.php';
if(session_status()===PHP_SESSION_NONE)session_start();
$role=strtolower(trim((string)($_SESSION['role']??($_SESSION['user']['role']??''))));
if(!isLoggedIn()||!in_array($role,['admin','teacher'],true)){http_response_code(403);echo json_encode(['status'=>'error','message'=>'Không có quyền.']);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['status'=>'error','message'=>'Method không hợp lệ.']);exit;}
$raw=file_get_contents('php://input');$data=json_decode($raw,true);
if(!is_array($data)){http_response_code(400);echo json_encode(['status'=>'error','message'=>'JSON không hợp lệ.']);exit;}
$token=$data['csrf_token']??'';if(!hash_equals((string)($_SESSION['csrf_token']??''),(string)$token)){http_response_code(419);echo json_encode(['status'=>'error','message'=>'CSRF token không hợp lệ.']);exit;}
$title=trim($data['title']??'');$subject=(int)($data['subject_id']??0);$duration=max(1,min(600,(int)($data['duration']??30)));$description=trim($data['description']??'');$questions=is_array($data['questions']??null)?$data['questions']:[];
if($title===''||$subject<=0||!$questions){http_response_code(422);echo json_encode(['status'=>'error','message'=>'Thiếu tên đề, môn học hoặc câu hỏi.']);exit;}
$uid=(int)($_SESSION['user_id']??($_SESSION['user']['id']??0));if($uid<=0){http_response_code(401);echo json_encode(['status'=>'error','message'=>'Phiên đăng nhập không hợp lệ.']);exit;}
$chk=$pdo->prepare("SELECT id FROM subjects WHERE id=? LIMIT 1");$chk->execute([$subject]);if(!$chk->fetchColumn()){http_response_code(422);echo json_encode(['status'=>'error','message'=>'Môn học không tồn tại.']);exit;}
try{$pdo->beginTransaction();$code='EXAM_'.strtoupper(bin2hex(random_bytes(4)));$s=$pdo->prepare("INSERT INTO exams(subject_id,teacher_id,title,description,exam_code,total_questions,duration,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,'published',NOW(),NOW())");$s->execute([$subject,$uid,$title,$description,$code,0,$duration]);$exam=(int)$pdo->lastInsertId();
$qst=$pdo->prepare("INSERT INTO questions(subject_id,created_by,content,option_a,option_b,option_c,option_d,correct_answer,explanation,difficulty,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,NOW(),NOW())");$link=$pdo->prepare("INSERT INTO exam_questions(exam_id,question_id,question_order) VALUES(?,?,?)");$order=1;
foreach($questions as $i=>$q){$content=trim($q['content']??($q['question_text']??''));$opts=$q['options']??[];$a=trim($q['option_a']??($opts[0]??''));$bb=trim($q['option_b']??($opts[1]??''));$c=trim($q['option_c']??($opts[2]??''));$d=trim($q['option_d']??($opts[3]??''));$ok=strtoupper(trim($q['correct_answer']??''));$diff=$q['difficulty']??'easy';if($content===''||$a===''||$bb===''||$c===''||$d===''||!in_array($ok,['A','B','C','D'],true))throw new RuntimeException('Câu '.($i+1).' không hợp lệ.');if(!in_array($diff,['easy','medium','hard'],true))$diff='easy';$qst->execute([$subject,$uid,$content,$a,$bb,$c,$d,$ok,trim($q['explanation']??''),$diff]);$link->execute([$exam,(int)$pdo->lastInsertId(),$order++]);}
$pdo->prepare("UPDATE exams SET total_questions=? WHERE id=?")->execute([$order-1,$exam]);$pdo->commit();echo json_encode(['status'=>'success','message'=>'Tạo đề thi thành công!','exam_id'=>$exam,'redirect'=>'exams.php']);}
catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();http_response_code(500);echo json_encode(['status'=>'error','message'=>'Không thể lưu đề thi.']);}
