<?php
// includes/ui.php - UI Components

/**
 * Stat Card
 */
function stat_card($color, $label, $number, $sub, $icon) {
    $colors = array(
        'blue' => 'primary',
        'green' => 'success',
        'amber' => 'warning',
        'red' => 'danger',
        'purple' => 'purple'
    );
    $bg = isset($colors[$color]) ? $colors[$color] : 'primary';
    ?>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-<?= $bg ?> bg-opacity-10 p-3">
                    <i class="bi bi-<?= $icon ?> fs-4 text-<?= $bg ?>"></i>
                </div>
                <div>
                    <div class="h5 mb-0 fw-bold"><?= $number ?></div>
                    <div class="text-muted small"><?= $label ?></div>
                    <div class="text-muted small"><?= $sub ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Panel Table
 */
function panel_table($title, $headers, $rows, $action = '') {
    ?>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><?= $title ?></h6>
            <?= $action ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <?php foreach ($headers as $h): ?>
                                <th class="small text-uppercase text-muted fw-semibold"><?= $h ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="<?= count($headers) ?>" class="text-center text-muted py-3">
                                    Chưa có dữ liệu
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cell): ?>
                                        <td><?= $cell ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Badge
 */
function badge($text, $type = 'secondary', $extra = '') {
    return '<span class="badge bg-' . $type . ' ' . $extra . '">' . $text . '</span>';
}

/**
 * Row Actions
 */
function row_actions() {
    return '
        <div class="d-flex gap-1">
            <button class="btn btn-sm btn-outline-primary" onclick="alert(\'Chức năng đang phát triển\')">
                <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="if(confirm(\'Xóa mục này?\')) alert(\'Đã xóa\')">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    ';
}

/**
 * Tabs
 */
function render_tabs($tabs, $active = 0) {
    ?>
    <ul class="nav nav-tabs border-0 bg-light rounded-pill p-1 mb-4">
        <?php foreach ($tabs as $index => $tab): ?>
            <li class="nav-item">
                <a class="nav-link rounded-pill <?= $index === $active ? 'active bg-grad text-white' : '' ?>" 
                   href="<?= $tab['url'] ?>">
                    <?php if (isset($tab['icon'])): ?>
                        <i class="bi bi-<?= $tab['icon'] ?>"></i>
                    <?php endif; ?>
                    <?= e($tab['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

/**
 * Alert
 */
function render_alert($type, $message, $dismissible = true) {
    ?>
    <div class="alert alert-<?= $type ?> <?= $dismissible ? 'alert-dismissible fade show' : '' ?>" role="alert">
        <?= $message ?>
        <?php if ($dismissible): ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Pagination
 */
function render_pagination($current, $total, $url) {
    if ($total <= 1) return '';
    ?>
    <nav>
        <ul class="pagination justify-content-center">
            <?php if ($current > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= $url . '?page=' . ($current - 1) ?>">«</a></li>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total; $i++): ?>
                <li class="page-item <?= $i === $current ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $url . '?page=' . $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            
            <?php if ($current < $total): ?>
                <li class="page-item"><a class="page-link" href="<?= $url . '?page=' . ($current + 1) ?>">»</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php
}

/**
 * Empty State
 */
function render_empty_state($message = 'Chưa có dữ liệu', $icon = 'inbox', $action = null) {
    ?>
    <div class="text-center py-5">
        <i class="bi bi-<?= $icon ?> fs-1 text-muted"></i>
        <p class="text-muted mt-3"><?= e($message) ?></p>
        <?php if ($action): ?>
            <a href="<?= $action['url'] ?>" class="btn btn-grad">
                <i class="bi bi-<?= $action['icon'] ?? 'plus' ?>"></i> <?= e($action['label']) ?>
            </a>
        <?php endif; ?>
    </div>
    <?php
}