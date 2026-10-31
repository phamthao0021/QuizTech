<?php
/**
 * includes/components/datatable_users.php
 * DataTable dùng cho trang Quản lý người dùng.
 *
 * Chức năng:
 * - Tìm kiếm tự động.
 * - Lọc vai trò và trạng thái tự động.
 * - Phân trang dưới bảng.
 * - Chọn tất cả bản ghi trên trang hiện tại.
 * - Xóa hàng loạt qua POST và CSRF.
 */

if (!function_exists('render_datatable')) {

    function render_datatable(
        $config,
        $data,
        $total_records,
        $current_page,
        $limit
    ) {
        $config = is_array($config) ? $config : [];
        $data = is_array($data) ? $data : [];

        $total_records = max(0, (int) $total_records);

        $limits = [10, 25, 50, 100];

        $limit = (int) $limit;

        if (!in_array($limit, $limits, true)) {
            $limit = 10;
        }

        $total_pages = max(
            1,
            (int) ceil($total_records / $limit)
        );

        $current_page = max(
            1,
            min((int) $current_page, $total_pages)
        );

        $first_record = $total_records > 0
            ? (($current_page - 1) * $limit + 1)
            : 0;

        $last_record = min(
            $current_page * $limit,
            $total_records
        );

        $search_val = isset($_GET['q']) && is_string($_GET['q'])
            ? trim($_GET['q'])
            : '';

        $bulk_url = $config['bulk_action_url'] ?? 'users.php';

        $bulk_action = $config['bulk_action'] ?? 'bulk_delete';

        // Phải khớp với $_POST['user_ids'] trong admin/users.php.
        $checkbox_name = $config['checkbox_name'] ?? 'user_ids[]';

        $columns = $config['columns'] ?? [];

        $filters = $config['filters'] ?? [];

        $row_renderer = $config['row_renderer'] ?? null;

        $page_query = [];

        foreach (['q', 'role', 'status'] as $key) {
            if (
                isset($_GET[$key]) &&
                is_string($_GET[$key]) &&
                $_GET[$key] !== ''
            ) {
                $page_query[$key] = $_GET[$key];
            }
        }

        $page_query['limit'] = $limit;

        $page_url = static function ($page) use ($page_query) {
            $params = $page_query;
            $params['page'] = max(1, (int) $page);

            return '?' . http_build_query($params);
        };
        ?>

        <style>
            .datatable-component {
                width: 100%;
            }

            /* THANH TÌM KIẾM */

            .datatable-component .qt-filter-card {
                background: #fff;
                border: 1px solid #e8eaf4;
                border-radius: 16px;
                box-shadow: 0 5px 20px rgba(15, 23, 42, .04);
                margin-bottom: 20px;
            }

            .datatable-component .qt-filter-body {
                padding: 15px;
            }

            .datatable-component .qt-filter-row {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 10px;
            }

            .datatable-component .qt-search-wrap {
                display: flex;
                align-items: center;
                flex: 1 1 300px;
                min-width: 0;
                height: 42px;
                border: 1px solid #dce2ee;
                border-radius: 10px;
                background: #f8fafc;
                overflow: hidden;
            }

            .datatable-component .qt-search-icon {
                width: 42px;
                min-width: 42px;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #64748b;
                border-right: 1px solid #e2e8f0;
            }

            .datatable-component .qt-search-input {
                width: 100%;
                min-width: 0;
                height: 100%;
                border: 0;
                outline: 0;
                background: transparent;
                padding: 0 12px;
                font-size: 13px;
                color: #334155;
            }

            .datatable-component .qt-search-wrap:focus-within {
                border-color: #8b5cf6;
                box-shadow: 0 0 0 3px rgba(139, 92, 246, .12);
            }

            /* DROPDOWN BỘ LỌC */

            .datatable-component .qt-filter-select {
                flex: 0 1 210px;
                min-width: 165px;
                height: 42px;
                padding: 0 36px 0 12px;
                border: 1px solid #dce2ee;
                border-radius: 10px;
                background-color: #f8fafc;
                color: #334155;
                font-size: 13px;
                cursor: pointer;
                appearance: auto;
            }

            .datatable-component .qt-filter-select:focus {
                border-color: #8b5cf6;
                outline: 0;
                box-shadow: 0 0 0 3px rgba(139, 92, 246, .12);
            }

            /* BẢNG DỮ LIỆU */

            .datatable-component .qt-table-card {
                background: #fff;
                border: 1px solid #e8eaf4;
                border-radius: 16px;
                padding: 15px;
                box-shadow: 0 5px 20px rgba(15, 23, 42, .04);
            }

            .datatable-component .qt-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 7px;
            }

            .datatable-component .qt-table thead th {
                padding: 13px 12px;
                background: #f6f8fc;
                color: #64748b;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .035em;
                white-space: nowrap;
                border: 0;
            }

            .datatable-component .qt-table tbody td {
                padding: 13px 12px;
                background: #f8fafc;
                border: 0;
                vertical-align: middle;
                font-size: 13px;
            }

            .datatable-component .qt-table tbody tr:hover td {
                background: #f1f5f9;
            }

            .datatable-component .qt-table tbody td:first-child {
                border-radius: 10px 0 0 10px;
            }

            .datatable-component .qt-table tbody td:last-child {
                border-radius: 0 10px 10px 0;
            }

            .datatable-component .qt-table .form-check-input {
                cursor: pointer;
                border-color: #cbd5e1;
            }

            .datatable-component .qt-table .form-check-input:checked {
                background-color: #6845ed;
                border-color: #6845ed;
            }

            /* THANH XÓA HÀNG LOẠT */

            .datatable-component .qt-bulk-bar {
                display: none;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
                padding: 12px 15px;
                margin: 14px 0;
                border: 1px solid #fecaca;
                border-radius: 11px;
                background: #fff7f7;
                color: #dc2626;
            }

            .datatable-component .qt-bulk-bar.is-visible {
                display: flex;
            }

            .datatable-component .qt-bulk-count {
                font-size: 14px;
                font-weight: 600;
            }

            .datatable-component .qt-bulk-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                min-height: 38px;
                padding: 8px 15px;
                background: #dc3545;
                border: 1px solid #dc3545;
                border-radius: 9px;
                color: #fff;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
            }

            .datatable-component .qt-bulk-btn:hover {
                background: #bb2d3b;
                color: #fff;
            }

            /* PHÂN TRANG */

            .datatable-component .qt-pager {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
                margin-top: 16px;
                padding-top: 15px;
                border-top: 1px solid #e8eaf4;
            }

            .datatable-component .qt-pager-info {
                color: #475569;
                font-size: 13px;
            }

            .datatable-component .qt-pager-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                flex-wrap: wrap;
                gap: 9px;
            }

            .datatable-component .qt-pager-label {
                font-size: 13px;
                color: #475569;
                white-space: nowrap;
            }

            .datatable-component .qt-limit-select {
                width: 78px;
                height: 37px;
                border: 1px solid #dce2ee;
                border-radius: 9px;
                background-color: #f8fafc;
                color: #334155;
                font-size: 13px;
                padding: 0 9px;
            }

            .datatable-component .qt-page-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 37px;
                padding: 7px 13px;
                border: 1px solid #c7d2fe;
                border-radius: 9px;
                background: #fff;
                color: #4f46e5;
                font-size: 13px;
                font-weight: 600;
                text-decoration: none;
                white-space: nowrap;
            }

            .datatable-component .qt-page-btn:hover {
                background: #5b4cf0;
                border-color: #5b4cf0;
                color: #fff;
            }

            .datatable-component .qt-page-btn.is-disabled {
                opacity: .45;
                pointer-events: none;
            }

            .datatable-component .qt-page-counter {
                min-width: 42px;
                text-align: center;
                font-size: 13px;
                font-weight: 700;
                color: #334155;
            }

            /* MODAL XÁC NHẬN */

            .datatable-component .qt-confirm-modal .modal-content {
                border: 0;
                border-radius: 16px;
                overflow: hidden;
            }

            .datatable-component .qt-confirm-modal .modal-header {
                padding: 17px 20px;
                background: linear-gradient(110deg, #4f46e5, #7c3aed);
                color: #fff;
                border: 0;
            }

            .datatable-component .qt-confirm-modal .modal-title {
                font-size: 17px;
                font-weight: 700;
            }

            .datatable-component .qt-confirm-modal .modal-body {
                padding: 22px 20px;
                color: #334155;
            }

            .datatable-component .qt-confirm-modal .modal-footer {
                padding: 14px 20px;
                border-top: 1px solid #edf0f5;
            }

            @media (max-width: 767px) {
                .datatable-component .qt-filter-row {
                    flex-direction: column;
                    align-items: stretch;
                }

                .datatable-component .qt-search-wrap,
                .datatable-component .qt-filter-select {
                    width: 100%;
                    min-width: 0;
                    flex: auto;
                }

                .datatable-component .qt-pager {
                    flex-direction: column;
                    align-items: stretch;
                }

                .datatable-component .qt-pager-info {
                    text-align: center;
                }

                .datatable-component .qt-pager-actions {
                    justify-content: center;
                }

                .datatable-component .qt-bulk-btn {
                    width: 100%;
                }
            }
        </style>

        <div class="datatable-component">

            <!-- THANH TÌM KIẾM VÀ BỘ LỌC -->
            <div class="qt-filter-card">
                <div class="qt-filter-body">

                    <form
                        method="GET"
                        action=""
                        id="dt-filter-form"
                        class="qt-filter-row"
                    >

                        <div class="qt-search-wrap">

                            <span class="qt-search-icon">
                                <i class="bi bi-search"></i>
                            </span>

                            <input
                                type="search"
                                name="q"
                                id="dt-search-input"
                                class="qt-search-input"
                                placeholder="<?= e(
                                    $config['search_placeholder']
                                    ?? 'Tìm theo tên, email, tài khoản...'
                                ) ?>"
                                value="<?= e($search_val) ?>"
                                autocomplete="off"
                            >

                        </div>

                        <?php foreach ($filters as $filter): ?>

                            <?php
                            $filter_name = (string) ($filter['name'] ?? '');

                            if ($filter_name === '') {
                                continue;
                            }

                            $filter_options = $filter['options'] ?? [];

                            $selected_value = (string) (
                                $filter['selected'] ?? ''
                            );
                            ?>

                            <select
                                name="<?= e($filter_name) ?>"
                                class="qt-filter-select dt-filter-select"
                                aria-label="<?= e(
                                    $filter['default_label'] ?? 'Bộ lọc'
                                ) ?>"
                            >

                                <option value="">
                                    <?= e(
                                        $filter['default_label']
                                        ?? '-- Tất cả --'
                                    ) ?>
                                </option>

                                <?php foreach ($filter_options as $value => $label): ?>

                                    <option
                                        value="<?= e((string) $value) ?>"
                                        <?= $selected_value === (string) $value
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= e((string) $label) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        <?php endforeach; ?>

                        <input
                            type="hidden"
                            name="limit"
                            id="dt-limit-input"
                            value="<?= $limit ?>"
                        >

                        <input
                            type="hidden"
                            name="page"
                            id="dt-page-input"
                            value="1"
                        >

                    </form>

                </div>
            </div>

            <!-- FORM XÓA HÀNG LOẠT -->
            <form
                id="dt-bulk-form"
                method="POST"
                action="<?= e($bulk_url) ?>"
            >

                <input
                    type="hidden"
                    name="action"
                    value="<?= e($bulk_action) ?>"
                >

                <?php
                if (function_exists('csrf_field')) {
                    csrf_field();
                }
                ?>

                <!-- THANH THÔNG BÁO XÓA HÀNG LOẠT -->
                <div
                    class="qt-bulk-bar"
                    id="dt-bulk-bar"
                    aria-live="polite"
                >

                    <span class="qt-bulk-count">
                        <i class="bi bi-check2-square me-1"></i>
                        <span id="dt-selected-count">0</span>
                        tài khoản đã chọn
                    </span>

                    <button
                        type="button"
                        id="btnBulkAction"
                        class="qt-bulk-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#dtConfirmModal"
                    >
                        <i class="bi bi-trash3"></i>
                        Xóa đã chọn
                    </button>

                </div>

                <!-- BẢNG -->
                <div class="qt-table-card">

                    <?php if (empty($data)): ?>

                        <div class="text-center py-5 text-muted">

                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>

                            Không tìm thấy tài khoản phù hợp.

                        </div>

                    <?php else: ?>

                        <div class="table-responsive">

                            <table class="qt-table">

                                <thead>
                                    <tr>

                                        <th
                                            class="text-center"
                                            style="width:44px"
                                        >
                                            <input
                                                type="checkbox"
                                                id="dtCheckAll"
                                                class="form-check-input"
                                                title="Chọn tất cả trên trang"
                                                aria-label="Chọn tất cả trên trang"
                                            >
                                        </th>

                                        <?php foreach ($columns as $column): ?>

                                            <?php
                                            $align = $column['align'] ?? '';

                                            $align_class = in_array(
                                                $align,
                                                ['start', 'center', 'end'],
                                                true
                                            )
                                                ? 'text-' . $align
                                                : '';

                                            $width = $column['width'] ?? '';
                                            ?>

                                            <th
                                                class="<?= e($align_class) ?>"
                                                <?= $width !== ''
                                                    ? 'style="width:' . e($width) . '"'
                                                    : '' ?>
                                            >
                                                <?= e(
                                                    $column['label'] ?? ''
                                                ) ?>
                                            </th>

                                        <?php endforeach; ?>

                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($data as $row): ?>

                                        <tr>

                                            <td class="text-center">

                                                <input
                                                    type="checkbox"
                                                    name="<?= e($checkbox_name) ?>"
                                                    value="<?= (int) ($row['id'] ?? 0) ?>"
                                                    class="form-check-input dt-check-item"
                                                    aria-label="Chọn tài khoản"
                                                >

                                            </td>

                                            <?php foreach ($columns as $column): ?>

                                                <?php
                                                $key = $column['key'] ?? '';

                                                $align = $column['align'] ?? '';

                                                $align_class = in_array(
                                                    $align,
                                                    ['start', 'center', 'end'],
                                                    true
                                                )
                                                    ? 'text-' . $align
                                                    : '';
                                                ?>

                                                <td class="<?= e($align_class) ?>">

                                                    <?php
                                                    if (is_callable($row_renderer)) {

                                                        // Renderer trả về HTML
                                                        // đã được xử lý trong users.php.
                                                        echo $row_renderer(
                                                            $row,
                                                            $key
                                                        );

                                                    } else {

                                                        echo e(
                                                            $row[$key] ?? ''
                                                        );

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

                    <!-- PHÂN TRANG DƯỚI BẢNG -->
                    <div class="qt-pager">

                        <div class="qt-pager-info">

                            Hiển thị
                            <strong><?= $first_record ?>–<?= $last_record ?></strong>
                            /
                            <strong><?= $total_records ?></strong>
                            tài khoản

                        </div>

                        <div class="qt-pager-actions">

                            <label
                                for="dt-limit-select"
                                class="qt-pager-label"
                            >
                                Số dòng / trang
                            </label>

                            <select
                                id="dt-limit-select"
                                class="qt-limit-select"
                                aria-label="Số dòng trên trang"
                            >

                                <?php foreach ($limits as $option_limit): ?>

                                    <option
                                        value="<?= $option_limit ?>"
                                        <?= $limit === $option_limit
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= $option_limit ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <a
                                href="<?= e(
                                    $page_url($current_page - 1)
                                ) ?>"
                                class="qt-page-btn <?= $current_page <= 1
                                    ? 'is-disabled'
                                    : '' ?>"
                                <?= $current_page <= 1
                                    ? 'aria-disabled="true" tabindex="-1"'
                                    : '' ?>
                            >
                                ‹ Trước
                            </a>

                            <span class="qt-page-counter">
                                <?= $current_page ?> / <?= $total_pages ?>
                            </span>

                            <a
                                href="<?= e(
                                    $page_url($current_page + 1)
                                ) ?>"
                                class="qt-page-btn <?= $current_page >= $total_pages
                                    ? 'is-disabled'
                                    : '' ?>"
                                <?= $current_page >= $total_pages
                                    ? 'aria-disabled="true" tabindex="-1"'
                                    : '' ?>
                            >
                                Sau ›
                            </a>

                        </div>

                    </div>

                </div>

            </form>

            <!-- MODAL XÁC NHẬN XÓA -->
            <div
                class="modal fade qt-confirm-modal"
                id="dtConfirmModal"
                tabindex="-1"
                aria-hidden="true"
            >

                <div class="modal-dialog modal-dialog-centered">

                    <div class="modal-content shadow">

                        <div class="modal-header">

                            <h5 class="modal-title">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                Xác nhận xóa hàng loạt
                            </h5>

                            <button
                                type="button"
                                class="btn-close btn-close-white"
                                data-bs-dismiss="modal"
                                aria-label="Đóng"
                            ></button>

                        </div>

                        <div class="modal-body">

                            Bạn có chắc chắn muốn xóa

                            <strong id="dt-modal-count">0</strong>

                            tài khoản đã chọn?

                            <div class="text-danger small mt-2">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                Hành động này không thể hoàn tác.
                                Những tài khoản được bảo vệ hoặc không đủ
                                quyền sẽ không được xóa.
                            </div>

                        </div>

                        <div class="modal-footer">

                            <button
                                type="button"
                                class="btn btn-light rounded-3"
                                data-bs-dismiss="modal"
                            >
                                Hủy bỏ
                            </button>

                            <button
                                type="button"
                                class="btn btn-danger rounded-3"
                                id="dtBtnConfirmAction"
                            >
                                <i class="bi bi-trash3 me-1"></i>
                                Xác nhận xóa
                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <script>
        (function () {
            'use strict';

            const root = document.currentScript
                .previousElementSibling;

            if (
                !root ||
                !root.classList.contains('datatable-component')
            ) {
                return;
            }

            const filterForm = root.querySelector(
                '#dt-filter-form'
            );

            const searchInput = root.querySelector(
                '#dt-search-input'
            );

            const filterSelects = root.querySelectorAll(
                '.dt-filter-select'
            );

            const limitSelect = root.querySelector(
                '#dt-limit-select'
            );

            const limitInput = root.querySelector(
                '#dt-limit-input'
            );

            const pageInput = root.querySelector(
                '#dt-page-input'
            );

            const bulkForm = root.querySelector(
                '#dt-bulk-form'
            );

            const checkAll = root.querySelector(
                '#dtCheckAll'
            );

            const checkItems = Array.from(
                root.querySelectorAll('.dt-check-item')
            );

            const bulkBar = root.querySelector(
                '#dt-bulk-bar'
            );

            const selectedCount = root.querySelector(
                '#dt-selected-count'
            );

            const modalCount = root.querySelector(
                '#dt-modal-count'
            );

            const confirmButton = root.querySelector(
                '#dtBtnConfirmAction'
            );

            let searchTimer = null;

            // TỰ ĐỘNG TÌM KIẾM / LỌC
            function submitFilters() {
                if (!filterForm) {
                    return;
                }

                if (pageInput) {
                    pageInput.value = '1';
                }

                filterForm.submit();
            }

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(searchTimer);

                    searchTimer = setTimeout(function () {
                        submitFilters();
                    }, 450);
                });
            }

            filterSelects.forEach(function (select) {
                select.addEventListener('change', function () {
                    clearTimeout(searchTimer);
                    submitFilters();
                });
            });

            if (limitSelect) {
                limitSelect.addEventListener('change', function () {
                    if (limitInput) {
                        limitInput.value = limitSelect.value;
                    }

                    submitFilters();
                });
            }

            // CẬP NHẬT TRẠNG THÁI CHỌN
            function updateSelection() {
                const selected = checkItems.filter(function (item) {
                    return item.checked && !item.disabled;
                });

                const selectedTotal = selected.length;

                if (selectedCount) {
                    selectedCount.textContent = selectedTotal;
                }

                if (modalCount) {
                    modalCount.textContent = selectedTotal;
                }

                if (bulkBar) {
                    bulkBar.classList.toggle(
                        'is-visible',
                        selectedTotal > 0
                    );
                }

                if (checkAll) {
                    const selectable = checkItems.filter(
                        function (item) {
                            return !item.disabled;
                        }
                    );

                    checkAll.checked =
                        selectable.length > 0 &&
                        selectedTotal === selectable.length;

                    checkAll.indeterminate =
                        selectedTotal > 0 &&
                        selectedTotal < selectable.length;
                }
            }

            // CHỌN TẤT CẢ TRÊN TRANG
            if (checkAll) {
                checkAll.addEventListener('change', function () {
                    checkItems.forEach(function (item) {
                        if (!item.disabled) {
                            item.checked = checkAll.checked;
                        }
                    });

                    updateSelection();
                });
            }

            // CHỌN TỪNG TÀI KHOẢN
            checkItems.forEach(function (item) {
                item.addEventListener('change', updateSelection);
            });

            // XÁC NHẬN XÓA HÀNG LOẠT
            if (confirmButton && bulkForm) {
                confirmButton.addEventListener('click', function () {
                    const hasSelection = checkItems.some(
                        function (item) {
                            return item.checked && !item.disabled;
                        }
                    );

                    if (!hasSelection) {
                        return;
                    }

                    confirmButton.disabled = true;
                    bulkForm.submit();
                });
            }

            // KHỞI TẠO
            updateSelection();

        })();
        </script>

        <?php
    }
}