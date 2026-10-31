<?php
// includes/components/datatable.php

if (!function_exists('render_datatable')) {
    function render_datatable(
        $config,
        $data,
        $total_records,
        $current_page,
        $limit
    ) {
        // Chuẩn hóa dữ liệu đầu vào
        $config = is_array($config) ? $config : [];
        $data = is_array($data) ? $data : [];

        $total_records = max(0, (int) $total_records);
        $limit = (int) $limit;

        $allowed_limits = [10, 25, 50, 100];

        if (!in_array($limit, $allowed_limits, true)) {
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

        $last_record = $total_records > 0
            ? min($current_page * $limit, $total_records)
            : 0;

        $search_val = isset($_GET['q']) && is_string($_GET['q'])
            ? trim($_GET['q'])
            : '';

        $bulk_url = $config['bulk_action_url'] ?? '';
        $bulk_action_name = $config['bulk_action'] ?? 'bulk_delete';

        // Cho phép cấu hình riêng tên checkbox.
        // Trang Người dùng sử dụng user_ids[].
        $checkbox_name = $config['checkbox_name'] ?? 'selected_ids[]';

// Lấy tham số URL và kiểm tra kiểu dữ liệu.
$query_params = filter_input_array(INPUT_GET) ?: [];

// Giữ số dòng trên mỗi trang.
$query_params['limit'] = (int) $limit;

// Xóa tham số trang cũ.
unset($query_params['page']);

// Tạo URL phân trang.
$page_url = static function (int $page) use ($query_params): string {
    $params = $query_params;
    $params['page'] = max(1, $page);

    return '?' . http_build_query($params);
};

        $reset_url = strtok(
            (string) ($_SERVER['REQUEST_URI'] ?? ''),
            '?'
        );

        $escape = static function ($value): string {
            return htmlspecialchars(
                (string) $value,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
        };
        ?>

        <style>
            .datatable-component {
                --dt-primary: #5b4cf0;
                --dt-border: #e2e8f0;
                --dt-text: #334155;
            }

            .datatable-component .dt-filter-card,
            .datatable-component .dt-table-card {
                background: #fff;
                border: 1px solid var(--dt-border);
                border-radius: 16px;
                box-shadow: 0 6px 24px rgba(31, 41, 55, .035);
            }

            .datatable-component .dt-filter-card {
                padding: 14px;
                margin-bottom: 18px;
            }

            .datatable-component .dt-filter-row {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 10px;
            }

            .datatable-component .dt-search {
                flex: 1 1 320px;
                max-width: 440px;
                min-width: 220px;
            }

            .datatable-component .dt-search .input-group-text,
            .datatable-component .dt-search .form-control {
                background: #f8fafc;
                border-color: #dbe2ed;
                min-height: 40px;
            }

            .datatable-component .dt-search .input-group-text {
                border-radius: 10px 0 0 10px;
            }

            .datatable-component .dt-search .form-control {
                border-radius: 0 10px 10px 0;
            }

            .datatable-component .dt-filter-select {
                flex: 0 1 220px;
                width: 220px;
                min-height: 40px;
                padding-right: 35px;
                border: 1px solid #dbe2ed;
                border-radius: 10px;
                background-color: #f8fafc;
                background-position: right 12px center;
                cursor: pointer;
            }

            .datatable-component .dt-search .form-control:focus,
            .datatable-component .dt-filter-select:focus,
            .datatable-component .dt-page-size:focus {
                border-color: #8b5cf6;
                box-shadow: 0 0 0 3px rgba(139, 92, 246, .12);
            }

            .datatable-component .dt-table-card {
                padding: 16px;
            }

            .datatable-component .dt-table-info {
                color: #64748b;
                font-size: 13px;
                margin-bottom: 14px;
            }

            .datatable-component .dt-table-card table {
                width: 100%;
            }

            .datatable-component .dt-table-card thead th {
                background: #f7f8fc;
                color: #64748b;
                font-size: 12px;
                font-weight: 700;
                padding: 14px 12px;
                white-space: nowrap;
            }

            .datatable-component .dt-table-card tbody td {
                padding: 13px 12px;
                vertical-align: middle;
            }

            /* Thanh xóa hàng loạt */
            .datatable-component .dt-bulk-toolbar {
                display: none;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
                padding: 12px 16px;
                margin-top: 16px;
                border: 1px solid #fecaca;
                border-radius: 12px;
                background: #fff7f7;
                color: #dc2626;
            }

            .datatable-component .dt-bulk-toolbar.is-visible {
                display: flex;
            }

            .datatable-component .dt-bulk-delete {
                border-radius: 9px;
                padding: 8px 16px;
                font-weight: 600;
            }

            /* Phân trang */
            .datatable-component .dt-pagination {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 14px;
                padding-top: 16px;
                margin-top: 16px;
                border-top: 1px solid #e8eaf2;
            }

            .datatable-component .dt-pagination-info {
                color: #64748b;
                font-size: 13px;
            }

            .datatable-component .dt-pagination-controls {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                flex-wrap: wrap;
                gap: 10px;
            }

            .datatable-component .dt-pagination-controls label {
                margin: 0;
                font-size: 13px;
                color: #475569;
                white-space: nowrap;
            }

            .datatable-component .dt-page-size {
                width: 82px !important;
                min-width: 82px;
                max-width: 82px;
                height: 38px;
                padding: 6px 30px 6px 10px;
                border: 1px solid #dbe2ed;
                border-radius: 10px;
                background-color: #fff;
            }

            .datatable-component .dt-page-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
                min-height: 38px;
                padding: 7px 13px;
                border: 1px solid #c7d2fe;
                border-radius: 10px;
                color: #4f46e5;
                background: #fff;
                text-decoration: none;
                font-size: 13px;
                font-weight: 600;
            }

            .datatable-component .dt-page-btn:hover:not(.disabled) {
                color: #fff;
                background: #5b4cf0;
            }

            .datatable-component .dt-page-btn.disabled {
                opacity: .45;
                cursor: not-allowed;
                pointer-events: none;
            }

            .datatable-component .dt-page-current {
                min-width: 42px;
                text-align: center;
                font-size: 13px;
                font-weight: 700;
                color: #334155;
            }

            @media (max-width: 767px) {
                .datatable-component .dt-search,
                .datatable-component .dt-filter-select {
                    flex: 1 1 100%;
                    max-width: none;
                    width: 100%;
                }

                .datatable-component .dt-pagination {
                    flex-direction: column;
                    align-items: stretch;
                }

                .datatable-component .dt-pagination-info {
                    text-align: center;
                }

                .datatable-component .dt-pagination-controls {
                    justify-content: center;
                }
            }
        </style>

        <div class="datatable-component" id="qtDatatable">

            <!-- TÌM KIẾM VÀ BỘ LỌC -->
            <div class="dt-filter-card">
                <form
                    method="GET"
                    action=""
                    id="dt-filter-form"
                    class="dt-filter-row"
                >
                    <div class="input-group dt-search">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>

                        <input
                            type="search"
                            name="q"
                            id="dt-search-input"
                            class="form-control"
                            placeholder="<?= $escape($config['search_placeholder'] ?? 'Tìm kiếm...') ?>"
                            value="<?= $escape($search_val) ?>"
                            autocomplete="off"
                        >
                    </div>

                    <?php foreach (($config['filters'] ?? []) as $filter): ?>
                        <select
                            name="<?= $escape($filter['name'] ?? '') ?>"
                            class="form-select dt-filter-select"
                        >
                            <option value="">
                                <?= $escape($filter['default_label'] ?? 'Tất cả') ?>
                            </option>

                            <?php foreach (($filter['options'] ?? []) as $value => $label): ?>
                                <option
                                    value="<?= $escape($value) ?>"
                                    <?= (string) ($filter['selected'] ?? '') === (string) $value ? 'selected' : '' ?>
                                >
                                    <?= $escape($label) ?>
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

                    <a
                        href="<?= $escape($reset_url ?: '') ?>"
                        class="btn btn-outline-secondary"
                        title="Xóa bộ lọc"
                    >
                        <i class="bi bi-arrow-clockwise"></i>
                        Làm mới
                    </a>
                </form>
            </div>

            <!-- BẢNG DỮ LIỆU -->
            <form
                id="dt-bulk-form"
                method="POST"
                action="<?= $escape($bulk_url) ?>"
            >
                <input
                    type="hidden"
                    name="action"
                    value="<?= $escape($bulk_action_name) ?>"
                >

                <?php
                if (function_exists('csrf_field')) {
                    csrf_field();
                }
                ?>

                <div class="dt-table-card">

                    <div class="dt-table-info">
                        <i class="bi bi-info-circle me-1"></i>
                        Hiển thị <?= count($data) ?> /
                        <?= $total_records ?> bản ghi.
                    </div>

                    <?php if (!empty($config['custom_actions'])): ?>
                        <div class="mb-3">
                            <?= $config['custom_actions'] ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($data)): ?>

                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Không có dữ liệu phù hợp.
                        </div>

                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table creative-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:44px">
                                            <input
                                                type="checkbox"
                                                id="dtCheckAll"
                                                class="form-check-input"
                                                aria-label="Chọn tất cả bản ghi trên trang"
                                            >
                                        </th>

                                        <?php foreach (($config['columns'] ?? []) as $col): ?>
                                            <th
                                                class="<?= $escape(isset($col['align']) ? 'text-' . $col['align'] : '') ?>"
                                                style="<?= $escape(isset($col['width']) ? 'width:' . $col['width'] . ';' : '') ?>"
                                            >
                                                <?= $escape($col['label'] ?? '') ?>
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
                                                    name="<?= $escape($checkbox_name) ?>"
                                                    value="<?= (int) ($row['id'] ?? 0) ?>"
                                                    class="form-check-input dt-check-item"
                                                    aria-label="Chọn bản ghi"
                                                >
                                            </td>

                                            <?php foreach (($config['columns'] ?? []) as $col): ?>
                                                <td class="<?= $escape(isset($col['align']) ? 'text-' . $col['align'] : '') ?>">
                                                    <?php
                                                    if (
                                                        isset($config['row_renderer']) &&
                                                        is_callable($config['row_renderer'])
                                                    ) {
                                                        echo $config['row_renderer'](
                                                            $row,
                                                            $col['key'] ?? ''
                                                        );
                                                    } else {
                                                        echo $escape(
                                                            $row[$col['key'] ?? ''] ?? ''
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

                    <!-- XÓA HÀNG LOẠT: BÊN DƯỚI BẢNG -->
                    <div
                        class="dt-bulk-toolbar"
                        id="dtBulkToolbar"
                    >
                        <div>
                            <i class="bi bi-check2-square me-1"></i>
                            <strong id="dt-selected-count">0</strong>
                            bản ghi đã chọn
                        </div>

                        <button
                            type="button"
                            id="btnBulkAction"
                            class="btn btn-danger dt-bulk-delete"
                            data-bs-toggle="modal"
                            data-bs-target="#dtConfirmModal"
                        >
                            <i class="bi bi-trash3 me-1"></i>
                            Xóa đã chọn
                        </button>
                    </div>

                    <!-- PHÂN TRANG DƯỚI BẢNG -->
                    <div class="dt-pagination">

                        <div class="dt-pagination-info">
                            Hiển thị
                            <strong><?= $first_record ?>–<?= $last_record ?></strong>
                            /
                            <strong><?= $total_records ?></strong>
                            bản ghi
                        </div>

                        <div class="dt-pagination-controls">

                            <label for="dt-limit-select">
                                Số dòng / trang
                            </label>

                            <select
                                id="dt-limit-select"
                                class="form-select form-select-sm dt-page-size"
                            >
                                <?php foreach ($allowed_limits as $size): ?>
                                    <option
                                        value="<?= $size ?>"
                                        <?= $limit === $size ? 'selected' : '' ?>
                                    >
                                        <?= $size ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if ($current_page > 1): ?>
                                <a
                                    class="dt-page-btn"
                                    href="<?= $escape($page_url($current_page - 1)) ?>"
                                >
                                    <i class="bi bi-chevron-left"></i>
                                    Trước
                                </a>
                            <?php else: ?>
                                <span class="dt-page-btn disabled">
                                    <i class="bi bi-chevron-left"></i>
                                    Trước
                                </span>
                            <?php endif; ?>

                            <span class="dt-page-current">
                                <?= $current_page ?> / <?= $total_pages ?>
                            </span>

                            <?php if ($current_page < $total_pages): ?>
                                <a
                                    class="dt-page-btn"
                                    href="<?= $escape($page_url($current_page + 1)) ?>"
                                >
                                    Sau
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="dt-page-btn disabled">
                                    Sau
                                    <i class="bi bi-chevron-right"></i>
                                </span>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>
            </form>

            <!-- MODAL XÁC NHẬN XÓA -->
            <div
                class="modal fade"
                id="dtConfirmModal"
                tabindex="-1"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 rounded-4 shadow">

                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                Xác nhận xóa hàng loạt
                            </h5>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Đóng"
                            ></button>
                        </div>

                        <div class="modal-body">
                            Bạn có chắc chắn muốn xóa
                            <strong id="dt-modal-count">0</strong>
                            bản ghi đã chọn?

                            <p class="text-danger small mt-2 mb-0">
                                Hành động này không thể hoàn tác.
                            </p>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal"
                            >
                                Hủy bỏ
                            </button>

                            <button
                                type="button"
                                class="btn btn-danger"
                                id="dtBtnConfirmAction"
                            >
                                <i class="bi bi-trash3 me-1"></i>
                                Đồng ý xóa
                            </button>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <script>
        (function () {
            "use strict";

            const root = document.getElementById("qtDatatable");

            if (!root) return;

            const filterForm = root.querySelector("#dt-filter-form");
            const searchInput = root.querySelector("#dt-search-input");
            const filterSelects = root.querySelectorAll(".dt-filter-select");
            const pageSizeSelect = root.querySelector("#dt-limit-select");
            const limitInput = root.querySelector("#dt-limit-input");
            const pageInput = root.querySelector("#dt-page-input");

            const bulkForm = root.querySelector("#dt-bulk-form");
            const checkAll = root.querySelector("#dtCheckAll");
            const checkboxes = Array.from(
                root.querySelectorAll(".dt-check-item")
            );

            const bulkToolbar = root.querySelector("#dtBulkToolbar");
            const selectedCount = root.querySelector("#dt-selected-count");
            const modalCount = root.querySelector("#dt-modal-count");
            const confirmButton = root.querySelector("#dtBtnConfirmAction");

            let searchTimer = null;
            let submitting = false;

            function applyFilters() {
                if (!filterForm) return;

                if (pageInput) {
                    pageInput.value = "1";
                }

                filterForm.submit();
            }

            // Tự động tìm kiếm sau khi ngừng nhập
            if (searchInput) {
                searchInput.addEventListener("input", function () {
                    clearTimeout(searchTimer);

                    searchTimer = setTimeout(
                        applyFilters,
                        450
                    );
                });
            }

            // Tự động lọc dropdown
            filterSelects.forEach(function (select) {
                select.addEventListener("change", applyFilters);
            });

            // Thay đổi số dòng / trang
            if (pageSizeSelect) {
                pageSizeSelect.addEventListener("change", function () {
                    if (!limitInput || !filterForm) return;

                    limitInput.value = this.value;

                    if (pageInput) {
                        pageInput.value = "1";
                    }

                    filterForm.submit();
                });
            }

            function updateSelection() {
                const checked = checkboxes.filter(function (item) {
                    return item.checked;
                });

                const count = checked.length;

                if (selectedCount) {
                    selectedCount.textContent = String(count);
                }

                if (modalCount) {
                    modalCount.textContent = String(count);
                }

                if (bulkToolbar) {
                    bulkToolbar.classList.toggle(
                        "is-visible",
                        count > 0
                    );
                }

                if (checkAll) {
                    checkAll.checked =
                        checkboxes.length > 0 &&
                        count === checkboxes.length;

                    checkAll.indeterminate =
                        count > 0 &&
                        count < checkboxes.length;
                }
            }

            if (checkAll) {
                checkAll.addEventListener("change", function () {
                    checkboxes.forEach(function (item) {
                        item.checked = checkAll.checked;
                    });

                    updateSelection();
                });
            }

            checkboxes.forEach(function (item) {
                item.addEventListener("change", updateSelection);
            });

            // Xác nhận xóa
            if (confirmButton && bulkForm) {
                confirmButton.addEventListener("click", function () {
                    const checkedCount = checkboxes.filter(function (item) {
                        return item.checked;
                    }).length;

                    if (checkedCount === 0 || submitting) {
                        return;
                    }

                    submitting = true;
                    confirmButton.disabled = true;

                    bulkForm.submit();
                });
            }

            updateSelection();
        })();
        </script>

        <?php
    }
}