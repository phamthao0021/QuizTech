<?php

/**
 * includes/practice_admin_service.php
 * Helper quản trị Practice — cho phép admin + admin_test.
 */
if (!defined('QUIZTECH_PRACTICE_ADMIN_SERVICE')) {
    define('QUIZTECH_PRACTICE_ADMIN_SERVICE', true);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
if (file_exists(__DIR__ . '/practice_service.php')) {
    require_once __DIR__ . '/practice_service.php';
}
if (!function_exists('pa_guard')) {
    function pa_guard(): void
    {
        $role = strtolower(trim(
            (string) (
                $_SESSION['role']
                ?? $_SESSION['user']['role']
                ?? ''
            )
        ));

        if (
            !function_exists('isLoggedIn')
            || !isLoggedIn()
        ) {
            redirect('../../login.php');
            exit;
        }

        if (!in_array($role, ['admin', 'admin_test'], true)) {
            http_response_code(403);
            exit('Bạn không có quyền truy cập Practice Admin.');
        }
    }
}
if (!function_exists('practice_admin_only')) {
    function practice_admin_only()
    {
        return function_exists('isAdminPanelUser') ? isAdminPanelUser() : (function_exists('isAdmin') && isAdmin());
    }
}

if (!function_exists('practice_stats')) {
    function practice_stats(PDO $pdo)
    {
        $out = [
            'crosswords' => 0,
            'concepts'   => 0,
            'quizzes'    => 0,
            'sessions'   => 0,
            'students'   => 0,
            'avg_score'  => 0,
        ];
        $map = [
            'crosswords' => 'practice_crosswords',
            'concepts'   => 'practice_concepts',
            'quizzes'    => 'practice_quiz_questions',
            'sessions'   => 'practice_sessions',
        ];
        foreach ($map as $key => $table) {
            try {
                $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
                $q->execute([$table]);
                if ($q->fetchColumn()) {
                    $out[$key] = (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                }
            } catch (Throwable $e) {
                $out[$key] = 0;
            }
        }
        try {
            $q = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM practice_sessions");
            if ($q) $out['students'] = (int)$q->fetchColumn();
            $q = $pdo->query("SELECT AVG(score) FROM practice_sessions WHERE score IS NOT NULL");
            if ($q) $out['avg_score'] = (float)$q->fetchColumn();
        } catch (Throwable $e) {
        }
        return $out;
    }
}

if (!function_exists('practice_recent')) {
    function practice_recent(PDO $pdo, $limit = 8)
    {
        $limit = max(1, (int)$limit);
        try {
            $stmt = $pdo->prepare("
                SELECT s.*, u.name AS user_name
                FROM practice_sessions s
                LEFT JOIN users u ON u.id = s.user_id
                ORDER BY s.id DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('pa_page')) {
    function pa_page()
    {
        return max(1, (int)($_GET['page'] ?? 1));
    }
}

if (!function_exists('pa_page_size')) {
    function pa_page_size($default = 20)
    {
        $n = (int)($_GET['limit'] ?? $default);
        return in_array($n, [10, 20, 25, 50, 100], true) ? $n : (int)$default;
    }
}
/**
 * Lấy số bản ghi / trang.
 */
if (!function_exists('pa_page_size')) {
    function pa_page_size(): int
    {
        $allowed = [10, 20, 25, 50, 100];

        $limit = isset($_GET['limit'])
            ? (int) $_GET['limit']
            : 20;

        return in_array($limit, $allowed, true)
            ? $limit
            : 20;
    }
}


/**
 * Lấy trang hiện tại.
 */
if (!function_exists('pa_page')) {
    function pa_page(): int
    {
        $page = isset($_GET['page'])
            ? (int) $_GET['page']
            : 1;

        return max(1, $page);
    }
}


/**
 * Escape chuỗi dùng cho LIKE.
 *
 * Ví dụ:
 * $keyword = pa_like('Java');
 * => %Java%
 */
if (!function_exists('pa_like')) {
    function pa_like(string $value): string
    {
        $value = trim($value);

        $value = str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );

        return '%' . $value . '%';
    }
}


/**
 * Chuẩn hóa danh sách ID.
 *
 * Dùng cho:
 * - checkbox
 * - bulk delete
 * - bulk action
 */
if (!function_exists('pa_ids')) {
    function pa_ids($ids): array
    {
        if (!is_array($ids)) {
            $ids = explode(',', (string) $ids);
        }

        $result = [];

        foreach ($ids as $id) {
            $id = (int) $id;

            if ($id > 0) {
                $result[$id] = $id;
            }
        }

        return array_values($result);
    }
}


/**
 * Chuyển dữ liệu CSV/XLSX thành associative array
 * dựa vào dòng đầu tiên làm header.
 *
 * Ví dụ:
 *
 * [
 *   ['term', 'definition', 'category'],
 *   ['API', 'Giao diện lập trình', 'Software']
 * ]
 *
 * =>
 *
 * [
 *   [
 *     'term' => 'API',
 *     'definition' => 'Giao diện lập trình',
 *     'category' => 'Software'
 *   ]
 * ]
 */
if (!function_exists('pa_assoc_rows')) {
    function pa_assoc_rows(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $headers = array_shift($rows);

        if (!is_array($headers) || empty($headers)) {
            return [];
        }

        $normalizedHeaders = [];

        foreach ($headers as $header) {

            $header = (string) $header;

            // Xóa UTF-8 BOM nếu có
            $header = preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $header
            );

            $header = trim($header);
            $header = strtolower($header);

            // Chuẩn hóa khoảng trắng
            $header = preg_replace(
                '/\s+/',
                '_',
                $header
            );

            $normalizedHeaders[] = $header;
        }

        $result = [];

        foreach ($rows as $row) {

            if (!is_array($row)) {
                continue;
            }

            // Bỏ dòng trống
            $hasValue = false;

            foreach ($row as $value) {
                if (trim((string) $value) !== '') {
                    $hasValue = true;
                    break;
                }
            }

            if (!$hasValue) {
                continue;
            }

            // Đủ số cột
            $row = array_pad(
                $row,
                count($normalizedHeaders),
                ''
            );

            // Nếu dư cột thì cắt
            $row = array_slice(
                $row,
                0,
                count($normalizedHeaders)
            );

            $item = [];

            foreach ($normalizedHeaders as $index => $header) {

                if ($header === '') {
                    continue;
                }

                $item[$header] = isset($row[$index])
                    ? trim((string) $row[$index])
                    : '';
            }

            if (!empty($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }
}


/**
 * Đọc CSV hoặc XLSX.
 *
 * Không cần PhpSpreadsheet.
 * XLSX sử dụng ZipArchive + SimpleXML.
 */
if (!function_exists('pa_csv_rows')) {
    function pa_csv_rows(array $file): array
    {
        if (
            !isset($file['error']) ||
            $file['error'] !== UPLOAD_ERR_OK
        ) {
            throw new RuntimeException(
                'Vui lòng chọn file hợp lệ.'
            );
        }

        $size = isset($file['size'])
            ? (int) $file['size']
            : 0;

        if ($size <= 0) {
            throw new RuntimeException(
                'File import đang trống.'
            );
        }

        if ($size > 5 * 1024 * 1024) {
            throw new RuntimeException(
                'Dung lượng file tối đa là 5MB.'
            );
        }

        $filename = isset($file['name'])
            ? (string) $file['name']
            : '';

        $extension = strtolower(
            pathinfo($filename, PATHINFO_EXTENSION)
        );

        /* ==========================
         * CSV
         * ========================== */

        if ($extension === 'csv') {

            $handle = fopen(
                $file['tmp_name'],
                'rb'
            );

            if (!$handle) {
                throw new RuntimeException(
                    'Không thể đọc file CSV.'
                );
            }

            // Tự nhận diện , hoặc ;
            $firstLine = fgets($handle);

            rewind($handle);

            $commaCount = substr_count(
                (string) $firstLine,
                ','
            );

            $semicolonCount = substr_count(
                (string) $firstLine,
                ';'
            );

            $delimiter = $semicolonCount > $commaCount
                ? ';'
                : ',';

            $rows = [];

            while (
                ($row = fgetcsv(
                    $handle,
                    0,
                    $delimiter
                )) !== false
            ) {
                $rows[] = $row;
            }

            fclose($handle);

            return $rows;
        }


        /* ==========================
         * XLSX
         * ========================== */

        if ($extension === 'xlsx') {

            if (!class_exists('ZipArchive')) {
                throw new RuntimeException(
                    'PHP chưa bật extension ZipArchive.'
                );
            }

            $zip = new ZipArchive();

            if (
                $zip->open($file['tmp_name'])
                !== true
            ) {
                throw new RuntimeException(
                    'Không thể mở file Excel.'
                );
            }

            /*
             * Shared strings
             */
            $sharedStrings = [];

            $sharedXml = $zip->getFromName(
                'xl/sharedStrings.xml'
            );

            if ($sharedXml !== false) {

                $xml = @simplexml_load_string(
                    $sharedXml
                );

                if ($xml !== false) {

                    $xml->registerXPathNamespace(
                        'a',
                        'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
                    );

                    $items = $xml->xpath('//a:si');

                    if ($items) {

                        foreach ($items as $item) {

                            $text = '';

                            $parts = $item->xpath(
                                './/a:t'
                            );

                            if ($parts) {
                                foreach ($parts as $part) {
                                    $text .= (string) $part;
                                }
                            }

                            $sharedStrings[] = $text;
                        }
                    }
                }
            }

            /*
             * Worksheet đầu tiên
             */
            $sheetXml = $zip->getFromName(
                'xl/worksheets/sheet1.xml'
            );

            $zip->close();

            if ($sheetXml === false) {
                throw new RuntimeException(
                    'Không tìm thấy worksheet trong Excel.'
                );
            }

            $sheet = @simplexml_load_string(
                $sheetXml
            );

            if ($sheet === false) {
                throw new RuntimeException(
                    'Worksheet Excel không hợp lệ.'
                );
            }

            $sheet->registerXPathNamespace(
                'a',
                'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
            );

            $xmlRows = $sheet->xpath(
                '//a:sheetData/a:row'
            );

            $rows = [];

            if (!$xmlRows) {
                return [];
            }

            foreach ($xmlRows as $xmlRow) {

                $values = [];

                $cells = $xmlRow->xpath('./a:c');

                if (!$cells) {
                    continue;
                }

                foreach ($cells as $cell) {

                    $reference = (string) $cell['r'];

                    preg_match(
                        '/([A-Z]+)/',
                        $reference,
                        $matches
                    );

                    $letters = isset($matches[1])
                        ? $matches[1]
                        : 'A';

                    // A = 0, B = 1...
                    $columnIndex = 0;

                    for (
                        $i = 0;
                        $i < strlen($letters);
                        $i++
                    ) {
                        $columnIndex =
                            $columnIndex * 26
                            + (ord($letters[$i]) - 64);
                    }

                    $columnIndex--;

                    $type = (string) $cell['t'];

                    $valueNodes = $cell->xpath('./a:v');

                    $value = isset($valueNodes[0])
                        ? (string) $valueNodes[0]
                        : '';

                    // Shared string
                    if ($type === 's') {

                        $sharedIndex = (int) $value;

                        $value = isset(
                            $sharedStrings[$sharedIndex]
                        )
                            ? $sharedStrings[$sharedIndex]
                            : '';
                    }

                    // Inline string
                    elseif ($type === 'inlineStr') {

                        $textNodes = $cell->xpath(
                            './/a:t'
                        );

                        $value = '';

                        if ($textNodes) {
                            foreach ($textNodes as $node) {
                                $value .= (string) $node;
                            }
                        }
                    }

                    $values[$columnIndex] = $value;
                }

                if (!empty($values)) {

                    $maxIndex = max(
                        array_keys($values)
                    );

                    $row = [];

                    for (
                        $i = 0;
                        $i <= $maxIndex;
                        $i++
                    ) {
                        $row[] = isset($values[$i])
                            ? $values[$i]
                            : '';
                    }

                    $rows[] = $row;
                }
            }

            return $rows;
        }

        throw new RuntimeException(
            'Chỉ hỗ trợ file .csv hoặc .xlsx.'
        );
    }
}


/**
 * Pagination dùng chung Practice Admin.
 */
if (!function_exists('pa_render_pagination')) {
    function pa_render_pagination(
        int $total,
        int $limit,
        int $currentPage
    ): void {

        if ($limit <= 0) {
            return;
        }

        $totalPages = max(
            1,
            (int) ceil($total / $limit)
        );

        if ($totalPages <= 1) {
            return;
        }

        $currentPage = max(
            1,
            min($currentPage, $totalPages)
        );

        $start = max(
            1,
            $currentPage - 2
        );

        $end = min(
            $totalPages,
            $currentPage + 2
        );

        echo '<nav aria-label="Phân trang">';
        echo '<ul class="pagination pagination-sm mb-0">';

        /*
         * Previous
         */
        $query = $_GET;

        $query['page'] = max(
            1,
            $currentPage - 1
        );

        echo '<li class="page-item '
            . ($currentPage <= 1 ? 'disabled' : '')
            . '">';

        echo '<a class="page-link" href="?'
            . htmlspecialchars(
                http_build_query($query),
                ENT_QUOTES,
                'UTF-8'
            )
            . '">&laquo;</a>';

        echo '</li>';


        /*
         * Page numbers
         */
        for ($page = $start; $page <= $end; $page++) {

            $query = $_GET;
            $query['page'] = $page;

            echo '<li class="page-item '
                . (
                    $page === $currentPage
                    ? 'active'
                    : ''
                )
                . '">';

            echo '<a class="page-link" href="?'
                . htmlspecialchars(
                    http_build_query($query),
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '">'
                . $page
                . '</a>';

            echo '</li>';
        }
        /*
         * Next
         */
        $query = $_GET;

        $query['page'] = min(
            $totalPages,
            $currentPage + 1
        );
        echo '<li class="page-item '
            . (
                $currentPage >= $totalPages
                ? 'disabled'
                : ''
            )
            . '">';
        echo '<a class="page-link" href="?'
            . htmlspecialchars(
                http_build_query($query),
                ENT_QUOTES,
                'UTF-8'
            )
            . '">&raquo;</a>';
        echo '</li>';
        echo '</ul>';
        echo '</nav>';
    }
}
