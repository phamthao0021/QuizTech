<?php
// admin/modals/question_type_modals.php - Modal cho các dạng câu hỏi
global $SUBJECTS;

// ==========================================
// MODAL THÊM MCQ (TRẮC NGHIỆM)
// ==========================================
?>
<div class="modal fade" id="addMCQModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="type" value="mcq">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm câu hỏi trắc nghiệm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Môn học</label>
                        <select name="subject_id" class="form-select" required>
                            <?php foreach ($SUBJECTS as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nội dung câu hỏi</label>
                        <textarea name="content" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2"><label>A. Đáp án A</label><input type="text" name="option_a" class="form-control" required></div>
                            <div class="mb-2"><label>B. Đáp án B</label><input type="text" name="option_b" class="form-control" required></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2"><label>C. Đáp án C</label><input type="text" name="option_c" class="form-control"></div>
                            <div class="mb-2"><label>D. Đáp án D</label><input type="text" name="option_d" class="form-control"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Đáp án đúng</label>
                            <select name="correct_answer" class="form-select">
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Độ khó</label>
                            <select name="difficulty" class="form-select">
                                <option value="easy">Dễ</option>
                                <option value="medium">Trung bình</option>
                                <option value="hard">Khó</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Điểm</label>
                            <input type="number" name="points" class="form-control" value="1" min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Giải thích</label>
                        <textarea name="explanation" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-grad">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
MODAL THÊM MULTISELECT (CHỌN NHIỀU)
========================================== -->
<div class="modal fade" id="addMultiSelectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="type" value="multiselect">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm câu hỏi chọn nhiều</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Môn học</label>
                        <select name="subject_id" class="form-select" required>
                            <?php foreach ($SUBJECTS as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Câu hỏi</label>
                        <textarea name="question_text" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2"><label>Đáp án 1</label><input type="text" name="options[]" class="form-control" required></div>
                            <div class="mb-2"><label>Đáp án 2</label><input type="text" name="options[]" class="form-control" required></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2"><label>Đáp án 3</label><input type="text" name="options[]" class="form-control"></div>
                            <div class="mb-2"><label>Đáp án 4</label><input type="text" name="options[]" class="form-control"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Đáp án đúng (số thứ tự, cách nhau dấu phẩy)</label>
                        <input type="text" name="correct_answers" class="form-control" placeholder="VD: 0,2" required>
                        <div class="form-text">0=Đáp án 1, 1=Đáp án 2, 2=Đáp án 3, 3=Đáp án 4</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Độ khó</label>
                            <select name="difficulty" class="form-select">
                                <option value="easy">Dễ</option>
                                <option value="medium">Trung bình</option>
                                <option value="hard">Khó</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Giải thích</label>
                        <textarea name="explanation" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-grad">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
MODAL THÊM MATCHING (NỐI Ô)
========================================== -->
<div class="modal fade" id="addMatchingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="type" value="matching">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm câu hỏi nối ô</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Môn học</label>
                        <select name="subject_id" class="form-select" required>
                            <?php foreach ($SUBJECTS as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Câu hỏi</label>
                        <textarea name="question_text" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Cột A (mỗi dòng 1 mục)</label>
                            <textarea name="left_items" class="form-control" rows="4" placeholder="PHP&#10;JavaScript&#10;Python" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cột B (mỗi dòng 1 mục)</label>
                            <textarea name="right_items" class="form-control" rows="4" placeholder="Web&#10;Frontend&#10;Data Science" required></textarea>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Đáp án đúng (số thứ tự cột B tương ứng)</label>
                        <input type="text" name="correct_matches" class="form-control" placeholder="VD: 1,0,2" required>
                        <div class="form-text">0=mục đầu tiên cột B, 1=mục thứ hai, v.v.</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Độ khó</label>
                            <select name="difficulty" class="form-select">
                                <option value="easy">Dễ</option>
                                <option value="medium">Trung bình</option>
                                <option value="hard">Khó</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-grad">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
MODAL THÊM TRUE/FALSE (ĐÚNG/SAI)
========================================== -->
<div class="modal fade" id="addTFModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="type" value="tf">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm câu hỏi Đúng/Sai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Môn học</label>
                        <select name="subject_id" class="form-select" required>
                            <?php foreach ($SUBJECTS as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Câu hỏi</label>
                        <textarea name="question_text" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Đáp án</label>
                        <select name="is_true" class="form-select">
                            <option value="1">✅ Đúng</option>
                            <option value="0">❌ Sai</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Độ khó</label>
                            <select name="difficulty" class="form-select">
                                <option value="easy">Dễ</option>
                                <option value="medium">Trung bình</option>
                                <option value="hard">Khó</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Giải thích</label>
                        <textarea name="explanation" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-grad">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>