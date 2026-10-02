document.addEventListener('DOMContentLoaded', function() {
    // 1. Handle Debounce Search & Auto Submit Filters
    const filterForm = document.getElementById('dt-filter-form');
    if (filterForm) {
        let debounceTimer;
        const searchInput = document.getElementById('dt-search-input');
        const filterSelects = document.querySelectorAll('.dt-filter-select');
        const limitSelect = document.getElementById('dt-limit-select');
        const limitInput = document.getElementById('dt-limit-input');
        
        // Function to submit form
        const submitFilter = () => {
            filterForm.submit();
        };

        // Debounce on search input
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(submitFilter, 300);
            });
        }

        // Auto submit on select change
        filterSelects.forEach(select => {
            select.addEventListener('change', submitFilter);
        });

        // Limit change
        if (limitSelect && limitInput) {
            limitSelect.addEventListener('change', function() {
                limitInput.value = this.value;
                submitFilter();
            });
        }
    }

    // 2. Handle Checkbox & Bulk Actions
    const checkAllBtn = document.getElementById('dtCheckAll');
    const checkItems = document.querySelectorAll('.dt-check-item');
    const bulkActionBtn = document.getElementById('btnBulkAction');
    const selectedCountSpan = document.getElementById('dt-selected-count');
    const modalCountSpan = document.getElementById('dt-modal-count');
    const btnConfirmAction = document.getElementById('dtBtnConfirmAction');
    const bulkForm = document.getElementById('dt-bulk-form');

    function updateBulkActionState() {
        let checkedCount = 0;
        checkItems.forEach(item => {
            if (item.checked) checkedCount++;
        });

        if (checkedCount > 0) {
            bulkActionBtn.classList.remove('d-none');
            if(selectedCountSpan) selectedCountSpan.textContent = checkedCount;
            if(modalCountSpan) modalCountSpan.textContent = checkedCount;
        } else {
            bulkActionBtn.classList.add('d-none');
        }

        if (checkAllBtn) {
            checkAllBtn.checked = (checkedCount === checkItems.length && checkItems.length > 0);
        }
    }

    if (checkAllBtn) {
        checkAllBtn.addEventListener('change', function() {
            const isChecked = this.checked;
            checkItems.forEach(item => {
                item.checked = isChecked;
            });
            updateBulkActionState();
        });
    }

    checkItems.forEach(item => {
        item.addEventListener('change', updateBulkActionState);
    });

    if (btnConfirmAction && bulkForm) {
        btnConfirmAction.addEventListener('click', function() {
            // Disable button to prevent double submit
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...';
            bulkForm.submit();
        });
    }
});
