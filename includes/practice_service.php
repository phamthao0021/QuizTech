<?php
// includes/practice_service.php
// Practice V2: server-authoritative answers, daily content and student-owned sessions.
require_once __DIR__.'/config.php';
require_once __DIR__.'/functions.php';

function pv2_json($data,$status=200){http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function pv2_uid(){if(session_status()===PHP_SESSION_NONE)session_start();return (int)($_SESSION['user_id']??($_SESSION['user']['id']??0));}
function pv2_role(){if(session_status()===PHP_SESSION_NONE)session_start();return strtolower(trim((string)($_SESSION['role']??($_SESSION['user']['role']??''))));}
function pv2_student(){if(!isLoggedIn())pv2_json(['ok'=>false,'message'=>'Vui lòng đăng nhập.'],401);if(pv2_role()!=='student')pv2_json(['ok'=>false,'message'=>'Practice dành cho tài khoản sinh viên.'],403);}
function pv2_csrf(){if(session_status()===PHP_SESSION_NONE)session_start();$a=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'');$b=(string)($_SESSION['csrf_token']??'');if(!$a||!$b||!hash_equals($b,$a))pv2_json(['ok'=>false,'message'=>'CSRF token không hợp lệ. Hãy tải lại trang.'],419);}
function pv2_body(){$d=json_decode(file_get_contents('php://input')?:'{}',true);return is_array($d)?$d:[];}
function pv2_token(){return bin2hex(random_bytes(16));}
function pv2_seed($type){return sprintf('%u',crc32(date('Y-m-d').'|'.$type));}
function pv2_session($id,$uid,$lock=false){global $pdo;$q="SELECT * FROM practice_sessions WHERE id=? AND user_id=? LIMIT 1".($lock?' FOR UPDATE':'');$s=$pdo->prepare($q);$s->execute([(int)$id,(int)$uid]);return $s->fetch(PDO::FETCH_ASSOC);}
function pv2_snap($s){$x=json_decode($s['snapshot']??'{}',true);return is_array($x)?$x:[];}
function pv2_pick_daily($table,$where='is_active=1'){
 global $pdo;$rows=$pdo->query("SELECT * FROM {$table} WHERE {$where} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
 if(!$rows)return null;$i=(int)(pv2_seed($table)%count($rows));return $rows[$i];
}
function pv2_start($type,$count=8){
 global $pdo;$uid=pv2_uid();if(!in_array($type,['crossword','concept_match','quick_quiz'],true))throw new RuntimeException('Chế độ không hợp lệ.');
 $snap=[];$contentId=null;
 if($type==='crossword'){
  $cw=pv2_pick_daily('practice_crosswords');if(!$cw)throw new RuntimeException('Chưa có Daily Crossword Active.');
  $st=$pdo->prepare("SELECT * FROM practice_crossword_clues WHERE crossword_id=? ORDER BY sort_order,id");$st->execute([$cw['id']]);$cl=$st->fetchAll(PDO::FETCH_ASSOC);if(!$cl)throw new RuntimeException('Crossword chưa có clue.');
  foreach($cl as &$x){$x['token']=pv2_token();$x['answer']=strtoupper(preg_replace('/[^A-Z0-9]/','',strtoupper($x['answer'])));}
  $snap=['title'=>$cw['title'],'description'=>$cw['description'],'rows'=>(int)$cw['rows_count'],'cols'=>(int)$cw['cols_count'],'difficulty'=>$cw['difficulty'],'time_limit'=>(int)$cw['time_limit_seconds'],'clues'=>$cl];$contentId=(int)$cw['id'];$total=count($cl);
 }elseif($type==='concept_match'){
  $count=max(6,min(10,(int)$count));$all=$pdo->query("SELECT * FROM practice_concepts WHERE is_active=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);if(count($all)<6)throw new RuntimeException('Cần ít nhất 6 khái niệm Active.');
  mt_srand((int)pv2_seed('concept'));shuffle($all);mt_srand();$all=array_slice($all,0,min($count,count($all)));
  foreach($all as &$x){$x['term_token']=pv2_token();$x['def_token']=pv2_token();}$snap=['items'=>$all,'time_limit'=>240];$total=count($all);
 }else{
  $count=in_array((int)$count,[5,8,10,15,20],true)?(int)$count:8;$all=$pdo->query("SELECT * FROM practice_quiz_questions WHERE is_active=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);if(!$all)throw new RuntimeException('Chưa có Quick Quiz Active.');
  mt_srand((int)pv2_seed('quiz'));shuffle($all);mt_srand();$all=array_slice($all,0,min($count,count($all)));
  foreach($all as &$x){$x['token']=pv2_token();$ops=[['src'=>'A','text'=>$x['option_a']],['src'=>'B','text'=>$x['option_b']],['src'=>'C','text'=>$x['option_c']],['src'=>'D','text'=>$x['option_d']]];foreach($ops as &$o)$o['token']=pv2_token();shuffle($ops);$x['display']=$ops;}$snap=['items'=>$all];$total=count($all);
 }
 $st=$pdo->prepare("INSERT INTO practice_sessions(user_id,game_type,daily_key,content_id,snapshot,total_count) VALUES(?,?,CURDATE(),?,?,?)");$st->execute([$uid,$type,$contentId,json_encode($snap,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$total]);
 return pv2_public((int)$pdo->lastInsertId(),$uid);
}
function pv2_public($id,$uid){
 $s=pv2_session($id,$uid);if(!$s)return null;$z=pv2_snap($s);
 if($s['game_type']==='crossword'){$cl=[];foreach($z['clues'] as $x)$cl[]=['token'=>$x['token'],'no'=>(int)$x['clue_no'],'direction'=>$x['direction'],'row'=>(int)$x['row_start'],'col'=>(int)$x['col_start'],'length'=>strlen($x['answer']),'clue'=>$x['clue']];$c=['title'=>$z['title'],'description'=>$z['description'],'rows'=>$z['rows'],'cols'=>$z['cols'],'difficulty'=>$z['difficulty'],'time_limit'=>$z['time_limit'],'clues'=>$cl];}
 elseif($s['game_type']==='concept_match'){$a=[];$b=[];foreach($z['items'] as $x){$a[]=['token'=>$x['term_token'],'term'=>$x['term'],'category'=>$x['category']];$b[]=['token'=>$x['def_token'],'definition'=>$x['definition']];}shuffle($a);shuffle($b);$c=['terms'=>$a,'definitions'=>$b,'time_limit'=>$z['time_limit']];}
 else{$items=[];foreach($z['items'] as $x){$ops=[];foreach($x['display'] as $o)$ops[]=['token'=>$o['token'],'text'=>$o['text']];$items[]=['token'=>$x['token'],'question'=>$x['question'],'category'=>$x['category'],'time_limit'=>(int)$x['time_limit_seconds'],'options'=>$ops];}$c=['items'=>$items];}
 return ['id'=>(int)$s['id'],'game_type'=>$s['game_type'],'daily_key'=>$s['daily_key'],'total'=>(int)$s['total_count'],'content'=>$c];
}
function pv2_find($s,$token){$z=pv2_snap($s);if($s['game_type']==='crossword'){foreach($z['clues'] as $x)if(hash_equals($x['token'],$token))return $x;}elseif($s['game_type']==='concept_match'){foreach($z['items'] as $x)if(hash_equals($x['term_token'],$token))return $x;}else{foreach($z['items'] as $x)if(hash_equals($x['token'],$token))return $x;}return null;}
function pv2_answer($sid,$token,$answer,$timeout=false,$combo=0){
 global $pdo;$uid=pv2_uid();$pdo->beginTransaction();
 try{$s=pv2_session($sid,$uid,true);if(!$s||$s['status']!=='playing')throw new RuntimeException('Phiên luyện tập không còn hoạt động.');$x=pv2_find($s,$token);if(!$x)throw new RuntimeException('Câu hỏi không thuộc phiên này.');
  $correct=false;$ex=$x['explanation']??'';$correctLabel='';$points=0;
  if($s['game_type']==='crossword'){$correctLabel=$x['answer'];$correct=!$timeout&&hash_equals($x['answer'],strtoupper(preg_replace('/[^A-Z0-9]/','',strtoupper((string)$answer))));$points=$correct?100:0;}
  elseif($s['game_type']==='concept_match'){$correctLabel=$x['definition'];$correct=!$timeout&&hash_equals($x['def_token'],(string)$answer);$points=$correct?100:0;}
  else{$src='';$chosen='';foreach($x['display'] as $o)if(hash_equals($o['token'],(string)$answer)){$src=$o['src'];$chosen=$o['text'];break;}$correct=$src!==''&&!$timeout&&hash_equals($x['correct_answer'],$src);$correctLabel=$x['option_'.strtolower($x['correct_answer'])];$elapsed=max(0,time()-strtotime($s['started_at']));$speed=max(0,30-min(30,$elapsed%60));$points=$correct?100+$speed+min(50,max(0,(int)$combo)*10):0;}
  $st=$pdo->prepare("INSERT INTO practice_answers(session_id,item_token,user_answer,is_correct,is_timeout,score_awarded,explanation) VALUES(?,?,?,?,?,?,?)");$st->execute([$sid,$token,(string)$answer,$correct?1:0,$timeout?1:0,$points,$ex]);
  $pdo->prepare("UPDATE practice_sessions SET score=score+?,correct_count=correct_count+?,wrong_count=wrong_count+?,max_combo=GREATEST(max_combo,?) WHERE id=?")->execute([$points,$correct?1:0,$correct?0:1,max(0,(int)$combo),$sid]);$pdo->commit();
  return ['correct'=>$correct,'points'=>$points,'correct_answer'=>$correctLabel,'explanation'=>$ex];
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function pv2_hint($sid,$token){
 global $pdo;$s=pv2_session($sid,pv2_uid());if(!$s||$s['status']!=='playing')throw new RuntimeException('Phiên không hợp lệ.');$x=pv2_find($s,$token);if(!$x)throw new RuntimeException('Không tìm thấy nội dung.');
 $pdo->prepare("UPDATE practice_sessions SET hints_used=hints_used+1,score=GREATEST(0,score-20) WHERE id=? AND user_id=?")->execute([$sid,pv2_uid()]);
 if($s['game_type']==='crossword'){return ['letter'=>$x['answer'][0],'index'=>0];}
 if($s['game_type']==='quick_quiz'){$wrong=[];foreach($x['display'] as $o)if($o['src']!==$x['correct_answer'])$wrong[]=$o['token'];shuffle($wrong);return ['disable'=>$wrong[0]??''];}
 return ['text'=>'Hãy đối chiếu nhóm '.($x['category']?:'CNTT').' và ý nghĩa cốt lõi của thuật ngữ.'];
}
function pv2_finish($sid,$timeout=false){
 global $pdo;$uid=pv2_uid();$s=pv2_session($sid,$uid,true);if(!$s)throw new RuntimeException('Không tìm thấy phiên.');
 if($s['status']==='playing'){$duration=max(0,time()-strtotime($s['started_at']));$status=$timeout?'timeout':'completed';$pdo->prepare("UPDATE practice_sessions SET status=?,duration_seconds=?,completed_at=NOW() WHERE id=? AND user_id=?")->execute([$status,$duration,$sid,$uid]);}
 return pv2_result($sid,$uid);
}
function pv2_result($sid,$uid=null){global $pdo;$uid=$uid?:pv2_uid();$s=pv2_session($sid,$uid);if(!$s)return null;$z=pv2_snap($s);$total=max(1,(int)$s['total_count']);$s['accuracy']=round(((int)$s['correct_count']/$total)*100,1);$st=$pdo->prepare("SELECT item_token,user_answer,is_correct,is_timeout,score_awarded,explanation FROM practice_answers WHERE session_id=? ORDER BY id");$st->execute([$sid]);$ans=$st->fetchAll(PDO::FETCH_ASSOC);
 $review=[];foreach($ans as $a){$x=pv2_find($s,$a['item_token']);if(!$x)continue;if($s['game_type']==='crossword')$review[]=['label'=>$x['clue_no'].' '.ucfirst($x['direction']),'prompt'=>$x['clue'],'correct'=>$x['answer'],'ok'=>(bool)$a['is_correct'],'explanation'=>$x['explanation']];elseif($s['game_type']==='concept_match')$review[]=['label'=>$x['term'],'prompt'=>$x['definition'],'correct'=>$x['definition'],'ok'=>(bool)$a['is_correct'],'explanation'=>$x['explanation']];else$review[]=['label'=>$x['category'],'prompt'=>$x['question'],'correct'=>$x['option_'.strtolower($x['correct_answer'])],'ok'=>(bool)$a['is_correct'],'explanation'=>$x['explanation']];}
 return ['session'=>$s,'review'=>$review];
}
function pv2_dashboard($uid){global $pdo;$st=$pdo->prepare("SELECT COUNT(*) sessions,COALESCE(AVG(score),0) avg_score,COALESCE(MAX(score),0) best_score,COALESCE(AVG(CASE WHEN total_count>0 THEN correct_count*100.0/total_count END),0) avg_accuracy,COALESCE(SUM(duration_seconds),0) total_time FROM practice_sessions WHERE user_id=? AND status IN('completed','timeout')");$st->execute([$uid]);$stats=$st->fetch(PDO::FETCH_ASSOC);$st=$pdo->prepare("SELECT game_type,MAX(score) best_score,COUNT(*) plays FROM practice_sessions WHERE user_id=? AND status IN('completed','timeout') GROUP BY game_type");$st->execute([$uid]);$games=$st->fetchAll(PDO::FETCH_ASSOC);$st=$pdo->prepare("SELECT id,game_type,daily_key,score,correct_count,total_count,duration_seconds,status,completed_at FROM practice_sessions WHERE user_id=? AND status IN('completed','timeout') ORDER BY id DESC LIMIT 30");$st->execute([$uid]);return ['stats'=>$stats,'games'=>$games,'history'=>$st->fetchAll(PDO::FETCH_ASSOC)];}
