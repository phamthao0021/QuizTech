<?php
// Presentation-only shared UI for Practice Admin. No DB/session/business logic here.
$currentPracticePage = basename($_SERVER['PHP_SELF'] ?? '');
$practiceNav = [
    'index.php'      => ['Tổng quan', 'bi-speedometer2'],
    'crosswords.php' => ['Crossword', 'bi-grid-3x3-gap'],
    'concepts.php'   => ['Nối cặp', 'bi-diagram-3'],
    'quick_quiz.php' => ['Phản xạ nhanh', 'bi-lightning-charge'],
    'results.php'    => ['Kết quả', 'bi-clipboard-data'],
    'statistics.php' => ['Thống kê', 'bi-bar-chart-line'],
];
?>
<style>
/* QuizTech Practice Admin UI — scoped to Practice pages */
:root{--pa-primary:#5b4cf0;--pa-primary-2:#7117d6;--pa-bg:#f5f7ff;--pa-text:#172033;--pa-muted:#6f7890;--pa-border:#e5e9f4;--pa-soft:#f8f9fd;--pa-success:#079669;--pa-danger:#e5484d;--pa-warning:#d99000}
body{background:var(--pa-bg)}
.pa-wrap,.pa{color:var(--pa-text);max-width:1600px;margin-inline:auto}
.pa-practice-nav{max-width:1600px;margin:20px auto 0;padding:0 1.5rem}
.pa-practice-nav-inner{display:flex;gap:7px;overflow-x:auto;padding:7px;background:rgba(255,255,255,.94);border:1px solid var(--pa-border);border-radius:16px;box-shadow:0 8px 24px rgba(38,45,78,.05);scrollbar-width:none}
.pa-practice-nav-inner::-webkit-scrollbar{display:none}
.pa-practice-nav a{display:inline-flex;align-items:center;gap:8px;white-space:nowrap;padding:9px 13px;border-radius:11px;text-decoration:none;color:#5f6880;font-weight:650;font-size:.88rem;transition:.18s ease}
.pa-practice-nav a:hover{background:#f0efff;color:var(--pa-primary)}
.pa-practice-nav a.active{background:linear-gradient(135deg,var(--pa-primary),var(--pa-primary-2));color:#fff;box-shadow:0 7px 16px rgba(91,76,240,.22)}
.pa-head,.pa-hero{position:relative;overflow:hidden;background:linear-gradient(120deg,#5448f7 0%,#6654e8 50%,#6b0dcc 100%)!important;color:#fff!important;border:0!important;border-radius:22px!important;padding:25px 28px!important;box-shadow:0 18px 36px rgba(76,57,190,.16)!important}
.pa-head:after,.pa-hero:after{content:"";position:absolute;width:250px;height:250px;border-radius:50%;right:-90px;top:-145px;background:rgba(255,255,255,.08);pointer-events:none}
.pa-head h2,.pa-head h3,.pa-hero h2,.pa-hero h3{font-weight:800!important;letter-spacing:-.025em}.pa-head p,.pa-hero p{color:rgba(255,255,255,.72)!important}
.pa-head .btn,.pa-hero .btn{position:relative;z-index:1;border-radius:11px;font-weight:700;padding:.62rem .9rem;border-width:1px}.pa-head .btn-light,.pa-hero .btn-light{color:#4f46e5;background:#fff;border-color:#fff}.pa-head .btn-warning,.pa-hero .btn-warning{background:#fff;color:#4f46e5;border-color:#fff}
.pa-card,.pa-stat,.pa-game{background:#fff!important;border:1px solid var(--pa-border)!important;border-radius:18px!important;box-shadow:0 8px 28px rgba(30,41,80,.055)!important}
.pa-stat{position:relative;overflow:hidden}.pa-stat:before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:linear-gradient(var(--pa-primary),#8b5cf6)}.pa-stat small{color:var(--pa-muted)!important}.pa-stat b,.pa-stat strong{color:var(--pa-text)!important;font-weight:800}
.pa-game{transition:transform .18s ease,box-shadow .18s ease}.pa-game:hover{transform:translateY(-3px);box-shadow:0 14px 34px rgba(30,41,80,.09)!important}.pa-icon{background:#efedff!important;color:var(--pa-primary)!important}
.pa-toolbar{display:grid!important;grid-template-columns:minmax(250px,1fr) minmax(160px,220px) minmax(160px,220px) auto!important;gap:10px!important;align-items:center}.pa-toolbar .form-control,.pa-toolbar .form-select{height:44px}
.form-control,.form-select{border-color:#dfe4ef;border-radius:11px;box-shadow:none!important}.form-control:focus,.form-select:focus{border-color:#8d82f6;box-shadow:0 0 0 .2rem rgba(91,76,240,.1)!important}
.btn-pa,.btn-primary{background:linear-gradient(135deg,var(--pa-primary),#6d28d9)!important;border-color:transparent!important;color:#fff!important}.btn{border-radius:10px;font-weight:650}.btn-sm{border-radius:9px}.btn-outline-danger{border-color:#f1b7ba;color:#d93c42}.btn-outline-danger:hover{background:#fff0f1;color:#c92f36}
.pa-card>.p-3.border-bottom,.pa-card>.card-header{background:#fff!important;border-color:#edf0f6!important}
.pa-table,.pa-card .table{--bs-table-bg:transparent;margin-bottom:0}.pa-table thead th,.pa-card .table thead th{background:#f8f9fd!important;color:#667085!important;border-bottom:1px solid #e8ebf2!important;font-size:.75rem;text-transform:uppercase;letter-spacing:.035em;font-weight:800;white-space:nowrap;padding:.86rem .9rem}.pa-table tbody td,.pa-card .table tbody td{padding:.9rem;border-color:#eef1f6;vertical-align:middle}.pa-table tbody tr,.pa-card .table tbody tr{transition:background .15s}.pa-table tbody tr:hover,.pa-card .table tbody tr:hover{background:#fafaff}
.pa-actions .btn,.pa-table td:last-child .btn{width:34px;height:34px;display:inline-grid;place-items:center;padding:0;margin-left:3px;border:1px solid #e7eaf2;background:#fff!important}.pa-table td:last-child .btn:hover{transform:translateY(-1px);box-shadow:0 5px 12px rgba(20,25,45,.08)}
.badge{border-radius:999px;padding:.43em .7em;font-weight:700}.bg-success-subtle{background:#e9fbf3!important}.bg-secondary-subtle{background:#f0f2f6!important}
.pagination{gap:5px}.pagination .page-link{border:1px solid #e2e6f0;border-radius:9px!important;color:#5d667d;min-width:34px;text-align:center}.pagination .active .page-link{background:var(--pa-primary);border-color:var(--pa-primary);box-shadow:0 5px 12px rgba(91,76,240,.2)}
.modal-content{border:0!important;border-radius:20px!important;box-shadow:0 24px 70px rgba(20,24,45,.2)}.modal-header,.modal-footer{border-color:#edf0f6}.modal-title{font-weight:800}.modal-body label{font-weight:650;color:#3c455b;margin-bottom:6px}
.alert{border:0;border-radius:13px}.pa-empty{padding:60px 20px!important;color:#929bb0!important}
.table-responsive{scrollbar-color:#cbd1df transparent;scrollbar-width:thin}.table-responsive::-webkit-scrollbar{height:7px}.table-responsive::-webkit-scrollbar-thumb{background:#cbd1df;border-radius:10px}
/* Fix accidental raw placeholder from previous build */
.pa-wrap + style{display:none}
@media(max-width:1199.98px){.pa-toolbar{grid-template-columns:1fr 1fr!important}.pa-toolbar .pa-search{grid-column:1/-1}.pa-toolbar .btn{min-height:44px}}
@media(max-width:767.98px){.pa-practice-nav{padding:0 12px;margin-top:12px}.pa-practice-nav-inner{border-radius:13px}.pa-wrap,.pa{padding-left:12px!important;padding-right:12px!important}.pa-head,.pa-hero{border-radius:17px!important;padding:19px!important}.pa-head h3,.pa-hero h2{font-size:1.35rem}.pa-head>div:last-child,.pa-hero>div:last-child{width:100%;display:grid!important;grid-template-columns:1fr!important}.pa-head .btn,.pa-hero .btn{width:100%}.pa-toolbar{grid-template-columns:1fr!important;padding:12px!important}.pa-toolbar .pa-search{grid-column:auto}.pa-card{border-radius:15px!important}.pa-card>.p-3.border-bottom{gap:10px;flex-wrap:wrap}.pa-card>.p-3.border-bottom .btn{width:100%}.pa-stat{padding:14px!important}.pa-stat b,.pa-stat strong{font-size:1.25rem!important}.modal-dialog{margin:.65rem}.modal-content{border-radius:16px!important}.pa-table{min-width:760px}.pa-table tbody td{padding:.75rem}.pagination{flex-wrap:wrap}}
</style>
<nav class="pa-practice-nav" aria-label="Practice Admin">
  <div class="pa-practice-nav-inner">
    <?php foreach ($practiceNav as $file => $item): ?>
      <a href="<?= e($file) ?>" class="<?= $currentPracticePage === $file || ($currentPracticePage === 'crossword_clues.php' && $file === 'crosswords.php') ? 'active' : '' ?>">
        <i class="bi <?= e($item[1]) ?>"></i><span><?= e($item[0]) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</nav>
