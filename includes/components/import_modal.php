<?php
// includes/components/import_modal.php
?>
<div class="modal fade" id="modalUniversalImport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
      
      <!-- Header -->
      <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #4c1d95, #6d28d9);">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-file-earmark-spreadsheet me-2 text-warning"></i>
          Import dữ liệu: <span id="importModalTypeLabel">...</span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4 bg-light">
        
        <!-- BƯỚC 1: UPLOAD FILE -->
        <div id="importStep1">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-1-circle-fill text-primary me-2"></i>Bước 1: Tải lên file dữ liệu</h6>
            <a href="#" id="btnDownloadTemplate" class="btn btn-sm btn-outline-success rounded-3 fw-semibold shadow-sm" download>
              <i class="bi bi-download me-1"></i>Tải file mẫu chuẩn
            </a>
          </div>

          <!-- Drag and Drop Zone -->
          <div id="importDropZone" class="border-2 border-dashed border-primary rounded-4 text-center p-5 bg-white position-relative" style="cursor: pointer; transition: all 0.2s;">
            <input type="file" id="importFileInput" class="position-absolute w-100 h-100 opacity-0" style="top:0; left:0; cursor:pointer;" accept=".xlsx, .csv">
            <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 3.5rem;"></i>
            <h5 class="mt-3 text-dark fw-bold">Kéo thả file vào đây hoặc nhấn để chọn file</h5>
            <p class="text-muted mb-0 small">Chỉ chấp nhận file <strong>.xlsx</strong> hoặc <strong>.csv</strong>. Dung lượng tối đa: <strong>5MB</strong></p>
          </div>
          <div id="importFileError" class="text-danger small mt-2 fw-semibold d-none"><i class="bi bi-exclamation-triangle-fill me-1"></i><span class="msg"></span></div>
        </div>

        <!-- BƯỚC 2: PREVIEW -->
        <div id="importStep2" class="d-none">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-2-circle-fill text-primary me-2"></i>Bước 2: Kiểm tra dữ liệu (Preview)</h6>
            <button type="button" id="btnBackToStep1" class="btn btn-sm btn-outline-secondary rounded-3 fw-semibold">
              <i class="bi bi-arrow-left me-1"></i>Tải file khác
            </button>
          </div>

          <div class="alert alert-info border-0 rounded-3 small py-2 d-flex align-items-center justify-content-between shadow-sm">
            <span>Đã đọc thành công <strong id="previewTotalRows">0</strong> dòng. Dòng hợp lệ: <strong id="previewValidRows" class="text-success">0</strong>. Lỗi: <strong id="previewErrorRows" class="text-danger">0</strong>.</span>
          </div>

          <div class="table-responsive border rounded-3 bg-white" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0 text-sm table-bordered" id="importPreviewTable">
              <thead class="table-light position-sticky top-0" style="z-index: 1;" id="importPreviewThead">
                <!-- Header động theo type -->
              </thead>
              <tbody id="importPreviewTbody">
                <!-- Dữ liệu động -->
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Footer -->
      <div class="modal-footer border-0 p-3 bg-white">
        <button type="button" class="btn btn-light rounded-3 fw-semibold px-4" data-bs-dismiss="modal">Hủy</button>
        <button type="button" id="btnConfirmImport" class="btn btn-primary rounded-3 fw-semibold px-4 d-none" disabled>
          <i class="bi bi-check-circle me-2"></i>Xác nhận Import (<span id="btnConfirmCount">0</span>)
        </button>
      </div>

    </div>
  </div>
</div>
