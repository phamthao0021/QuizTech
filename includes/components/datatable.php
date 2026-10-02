<?php
// includes/components/datatable.php
// Reusable DataTable Component

/**
 * Render a DataTable
 *
 * @param array $config Bảng cấu hình gồm:
 *   - 'search_placeholder' (string)
 *   - 'filters' (array) Mảng các filters, mỗi filter có:
 *       - 'name' (string) Tên query param
 *       - 'options' (array) mảng key => value (value là nhãn hiển thị)
 *       - 'selected' (string) Giá trị đang chọn
 *       - 'default_label' (string) Nhãn mặc định
 *   - 'bulk_action' (string) Tên action xử lý xóa (vd: 'bulk_delete')
 *   - 'bulk_action_url' (string) Tùy chọn URL submit form, mặc định là trang hiện tại
 *   - 'columns' (array) Cấu hình các cột, mỗi cột:
 *       - 'label' (string) Tiêu đề cột
 *       - 'width' (string) Độ rộng (vd: '80px')
 *       - 'align' (string) Căn lề (vd: 'center', 'end')
 *       - 'key' (string) Tương ứng key trong row_renderer hoặc data
 *   - 'row_renderer' (callable) Hàm callback để render HTML từng cột. function($row, $col_key)
 * @param array $data Mảng dữ liệu hiện tại (đã phân trang)
 * @param int $total_records Tổng số bản ghi (để tính phân trang)
 * @param int $current_page Trang hiện tại
 * @param int $limit Số bản ghi trên mỗi trang
 */
function render_datatable($config, $data, $total_records, $current_page, $limit) {
    $search_val = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limits = [10, 25, 50, 100];
    $total_pages = ceil($total_records / $limit);
    if ($total_pages < 1) $total_pages = 1;

    $bulk_url = $config['bulk_action_url'] ?? ''; // Nếu trống sẽ tự submit tới form action hiện tại
    $bulk_action_name = $config['bulk_action'] ?? 'bulk_delete';
    ?>
    <div class="datatable-component">
        <!-- Toolbar: Search & Filters -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 1.25rem;">
            <div class="card-body p-3">
                <form method="GET" action="" class="row g-2 search-box-group" id="dt-filter-form">
                    <div class="col-12 col-md-6 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted" style="border-top-left-radius: 0.75rem; border-bottom-left-radius: 0.75rem;">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="q" id="dt-search-input" class="form-control bg-light border-start-0 ps-0" placeholder="<?= e($config['search_placeholder'] ?? 'Tìm kiếm...') ?>" value="<?= e($search_val) ?>" style="border-top-right-radius: 0.75rem; border-bottom-right-radius: 0.75rem;">
                        </div>
                    </div>
                    
                    <?php if (!empty($config['filters'])): ?>
                        <?php foreach ($config['filters'] as $filter): ?>
                            <div class="col-6 col-md-3 col-lg-3">
                                <select name="<?= e($filter['name']) ?>" class="form-select bg-light dt-filter-select">
                                    <option value=""><?= e($filter['default_label'] ?? '-- Tất cả --') ?></option>
                                    <?php foreach ($filter['options'] as $val => $label): ?>
                                        <option value="<?= e($val) ?>" <?= ($filter['selected'] ?? '') == (string)$val ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <input type="hidden" name="limit" value="<?= $limit ?>" id="dt-limit-input">
                    <input type="hidden" name="page" value="1" id="dt-page-input"> <!-- Reset page on search/filter -->

                    <!-- Ẩn nút submit, JS sẽ lo việc submit khi thay đổi -->
                    <button type="submit" class="d-none"></button>
                </form>
            </div>
        </div>

        <!-- Table Card -->
        <form id="dt-bulk-form" method="POST" action="<?= e($bulk_url) ?>">
            <input type="hidden" name="action" value="<?= e($bulk_action_name) ?>">
            <?php if (function_exists('csrf_field')) csrf_field(); ?>
            
            <div class="card border-0 shadow-sm creative-table-card" style="background: #ffffff;">
                <div class="card-body p-3 pt-2">
                    
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div class="small text-muted"><i class="bi bi-info-circle me-1"></i> Hiển thị <?= count($data) ?> / <?= $total_records ?> bản ghi.</div>
                        <div class="d-flex gap-2">
                            <?= $config['custom_actions'] ?? '' ?>
                            <button type="button" id="btnBulkAction" class="btn btn-outline-danger rounded-3 fw-semibold d-none" data-bs-toggle="modal" data-bs-target="#dtConfirmModal">
                                <i class="bi bi-trash3 me-1"></i> <span id="dt-selected-count">0</span> đã chọn
                            </button>
                        </div>
                    </div>

                    <?php if (empty($data)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-black-50"></i>
                            Không có dữ liệu
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table creative-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3 text-center" style="width: 44px;">
                                            <input type="checkbox" id="dtCheckAll" class="form-check-input" title="Chọn tất cả">
                                        </th>
                                        <?php foreach ($config['columns'] as $col): ?>
                                            <th class="<?= isset($col['align']) ? 'text-' . $col['align'] : '' ?>" style="<?= isset($col['width']) ? 'width: '.$col['width'].';' : '' ?>">
                                                <?= e($col['label']) ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $row): ?>
                                        <tr>
                                            <td class="ps-3 text-center">
                                                <input type="checkbox" name="selected_ids[]" value="<?= e($row['id'] ?? '') ?>" class="form-check-input dt-check-item">
                                            </td>
                                            <?php foreach ($config['columns'] as $col): ?>
                                                <td class="<?= isset($col['align']) ? 'text-' . $col['align'] : '' ?>">
                                                    <?php 
                                                        if (is_callable($config['row_renderer'])) {
                                                            echo $config['row_renderer']($row, $col['key']);
                                                        } else {
                                                            echo e($row[$col['key']] ?? '');
                                                        }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Pagination & Limit Selector -->
                    <?php if ($total_records > 0): ?>
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3 gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">Hiển thị:</span>
                            <select class="form-select form-select-sm bg-light" style="width: 80px;" id="dt-limit-select">
                                <?php foreach ($limits as $l): ?>
                                    <option value="<?= $l ?>" <?= $limit == $l ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <?php
                                // Rebuild query params for pagination links
                                $query = $_GET;
                                unset($query['page']);
                                $base_url = '?' . http_build_query($query) . '&page=';
                            ?>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= ($current_page > 1) ? $base_url . ($current_page - 1) : '#' ?>">Trước</a>
                                    </li>
                                    
                                    <?php
                                        // Hiển thị tối đa 5 trang xung quanh trang hiện tại
                                        $start_page = max(1, $current_page - 2);
                                        $end_page = min($total_pages, $current_page + 2);
                                        
                                        if ($start_page > 1) {
                                            echo '<li class="page-item"><a class="page-link" href="'.$base_url.'1">1</a></li>';
                                            if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }

                                        for ($p = $start_page; $p <= $end_page; $p++) {
                                            $active = ($p == $current_page) ? 'active' : '';
                                            echo '<li class="page-item '.$active.'"><a class="page-link" href="'.$base_url.$p.'">'.$p.'</a></li>';
                                        }

                                        if ($end_page < $total_pages) {
                                            if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            echo '<li class="page-item"><a class="page-link" href="'.$base_url.$total_pages.'">'.$total_pages.'</a></li>';
                                        }
                                    ?>

                                    <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= ($current_page < $total_pages) ? $base_url . ($current_page + 1) : '#' ?>">Sau</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </form>

        <!-- Confirmation Modal -->
        <div class="modal fade" id="dtConfirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Xác nhận xóa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Bạn có chắc chắn muốn xóa <b id="dt-modal-count">0</b> mục đã chọn? Hành động này không thể hoàn tác.
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-danger rounded-3" id="dtBtnConfirmAction">Đồng ý Xóa</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
