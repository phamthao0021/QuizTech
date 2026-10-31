<?php
$root=dirname(__DIR__,2);
require_once $root.'/includes/config.php';require_once $root.'/includes/functions.php';require_once $root.'/includes/auth.php';require_once $root.'/includes/practice_admin_service.php';
if(session_status()===PHP_SESSION_NONE)session_start();pa_guard();
$q=trim((string)($_GET['q']??''));$action=(string)($_GET['action']??'');$page=max(1,(int)($_GET['page']??1));$limit=25;$where=[];$params=[];
if($q!==''){$where[]='(u.name LIKE ? OR l.table_name LIKE ? OR CAST(l.record_id AS CHAR) LIKE ?)';$like='%'.$q.'%';array_push($params,$like,$like,$like);}
if(in_array($action,['create','update','toggle','delete'],true)){$where[]='l.action_name=?';$params[]=$action;}
$w=$where?' WHERE '.implode(' AND ',$where):'';
$st=$pdo->prepare('SELECT COUNT(*) FROM practice_audit_logs l LEFT JOIN users u ON u.id=l.actor_id'.$w);$st->execute($params);$total=(int)$st->fetchColumn();
$st=$pdo->prepare('SELECT l.*,u.name actor_name FROM practice_audit_logs l LEFT JOIN users u ON u.id=l.actor_id'.$w.' ORDER BY l.id DESC LIMIT 25 OFFSET '.(($page-1)*$limit));$st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC);
$page_title='Nhật ký quản trị Practice';require $root.'/includes/header_admin.php';require __DIR__.'/_practice_ui.php';
?>
<div class="container-fluid py-4 px-3 px-md-4 pa-wrap"><div class="pa-head mb-4"><h2>Nhật ký CRUD Practice</h2><p class="mb-0">Ai đã tạo, chỉnh sửa, đổi trạng thái hoặc xóa mềm nội dung.</p></div>
<form method="get" class="pa-card p-3 mb-3 d-flex flex-wrap gap-2"><input class="form-control" style="max-width:360px" name="q" value="<?=e($q)?>" placeholder="Tên người thực hiện, bảng hoặc ID"><select class="form-select" style="max-width:200px" name="action"><option value="">Mọi thao tác</option><?php foreach(['create'=>'Thêm','update'=>'Sửa','toggle'=>'Trạng thái','delete'=>'Xóa mềm'] as $k=>$v):?><option value="<?=e($k)?>" <?=$action===$k?'selected':''?>><?=e($v)?></option><?php endforeach;?></select><button class="btn btn-primary">Lọc</button></form>
<div class="pa-card table-responsive"><table class="table align-middle"><thead><tr><th>ID</th><th>Người thực hiện</th><th>Thao tác</th><th>Bảng</th><th>Bản ghi</th><th>Thời gian</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=(int)$r['id']?></td><td><?=e($r['actor_name']??'Không xác định')?> (#<?=(int)$r['actor_id']?>)</td><td><?=e($r['action_name'])?></td><td><?=e($r['table_name'])?></td><td>#<?=(int)$r['record_id']?></td><td><?=e($r['created_at'])?></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="6" class="text-center py-4">Chưa có nhật ký</td></tr><?php endif;?></tbody></table></div>
<nav class="mt-3 d-flex gap-2"><?php if($page>1):?><a class="btn btn-outline-secondary" href="?<?=http_build_query(['q'=>$q,'action'=>$action,'page'=>$page-1])?>">Trang trước</a><?php endif;?><?php if($page*$limit<$total):?><a class="btn btn-outline-secondary" href="?<?=http_build_query(['q'=>$q,'action'=>$action,'page'=>$page+1])?>">Trang sau</a><?php endif;?></nav></div>
<?php require $root.'/includes/footer.php';
