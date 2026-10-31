<?php
declare(strict_types=1);

/**
 * QuizTech teacher import: strict 8-column template, CSV/XLSX/DOCX.
 * Parsing never writes to database. All records are validated before import.
 */
function qt_import_headers(): array {
    return ['content','option_a','option_b','option_c','option_d','correct_answer','difficulty','explanation'];
}
function qt_import_rows(string $path, string $extension): array {
    $extension = strtolower($extension);
    if ($extension === 'csv' || $extension === 'txt') {
        $handle = fopen($path, 'rb');
        if (!$handle) throw new RuntimeException('Không thể đọc file CSV.');
        try {
            $first = fgets($handle);
            if ($first === false) return [];
            $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
            $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
            rewind($handle);
            $rows = [];
            while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
                if (isset($row[0])) $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                $rows[] = $row;
                if (count($rows) > 1001) throw new RuntimeException('Tối đa 1.000 câu hỏi mỗi lần import.');
            }
            return $rows;
        } finally { fclose($handle); }
    }
    if ($extension === 'xlsx') {
        $autoload = dirname(__DIR__) . '/vendor/autoload.php';
        if (!is_file($autoload)) throw new RuntimeException('Thiếu thư viện PhpSpreadsheet trong vendor/.');
        require_once $autoload;
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) throw new RuntimeException('PhpSpreadsheet chưa được cài đặt.');
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);
        try {
            $sheet = $book->getActiveSheet();
            $last = $sheet->getHighestDataRow();
            if ($last > 1001) throw new RuntimeException('Tối đa 1.000 câu hỏi mỗi lần import.');
            $rows = [];
            for ($i=1;$i<=$last;$i++) {
                $row=[];
                for ($j=1;$j<=8;$j++) {
                    $value = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j).$i)->getFormattedValue();
                    $row[] = trim((string)$value);
                }
                $rows[]=$row;
            }
            return $rows;
        } finally { $book->disconnectWorksheets(); }
    }
    if ($extension === 'docx') {
        if (!class_exists('ZipArchive')) throw new RuntimeException('PHP cần bật extension zip để đọc DOCX.');
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Không thể mở file DOCX.');
        try {
            $xml = $zip->getFromName('word/document.xml');
        } finally { $zip->close(); }
        if ($xml === false || strlen($xml) > 8*1024*1024) throw new RuntimeException('DOCX không hợp lệ hoặc quá phức tạp.');
        $doc = new DOMDocument();
        $prior = libxml_use_internal_errors(true);
        try {
            if (!$doc->loadXML($xml, LIBXML_NONET)) throw new RuntimeException('Không thể đọc nội dung DOCX.');
        } finally { libxml_clear_errors(); libxml_use_internal_errors($prior); }
        $xp = new DOMXPath($doc);
        $xp->registerNamespace('w','http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $tables=$xp->query('//w:body/w:tbl');
        if (!$tables || $tables->length === 0) throw new RuntimeException('DOCX phải có bảng 8 cột theo template.');
        $rows=[];
        foreach ($tables as $table) {
            foreach ($xp->query('./w:tr',$table) as $tr) {
                $cells=[];
                foreach ($xp->query('./w:tc',$tr) as $tc) {
                    $parts=[];
                    foreach ($xp->query('.//w:p',$tc) as $p) {
                        $text='';
                        foreach ($xp->query('.//w:t',$p) as $t) $text.=$t->textContent;
                        $parts[]=$text;
                    }
                    $cells[]=trim(implode("\n",$parts));
                }
                $rows[]=$cells;
                if (count($rows)>1001) throw new RuntimeException('Tối đa 1.000 câu hỏi mỗi lần import.');
            }
        }
        return $rows;
    }
    throw new RuntimeException('Chỉ hỗ trợ CSV, XLSX hoặc DOCX.');
}
function qt_import_validate(array $rows): array {
    $headers=qt_import_headers();
    $valid=[];$errors=[];
    if (!$rows) return [[],['File không có dữ liệu.']];
    $first=array_map(static fn($v)=>strtolower(trim((string)$v)), $rows[0]);
    if (array_slice($first,0,8)!==$headers) return [[],['Dòng 1 phải là tiêu đề 8 cột đúng thứ tự theo template: '.implode(', ',$headers)]];
    foreach (array_slice($rows,1) as $offset=>$row) {
        $line=$offset+2;
        if (!array_filter($row,static fn($v)=>trim((string)$v)!=='')) continue;
        if (count($row)!==8) { $errors[]="Dòng $line: Cần đúng 8 cột (không gộp ô).";continue; }
        $r=array_map(static fn($v)=>trim((string)$v),$row);
        if ($r[0]===''||$r[1]===''||$r[2]===''){$errors[]="Dòng $line: Thiếu nội dung câu hỏi hoặc đáp án A/B.";continue;}
        $r[5]=strtoupper($r[5]);$r[6]=strtolower($r[6] ?: 'easy');
        if (!in_array($r[5],['A','B','C','D'],true)){$errors[]="Dòng $line: Đáp án đúng phải là A/B/C/D.";continue;}
        if ($r[ord($r[5])-ord('A')+1]===''){$errors[]="Dòng $line: Đáp án đúng {$r[5]} chưa có nội dung.";continue;}
        if (!in_array($r[6],['easy','medium','hard'],true)){$errors[]="Dòng $line: Độ khó chỉ nhận easy/medium/hard.";continue;}
        if (mb_strlen($r[0])>5000){$errors[]="Dòng $line: Nội dung quá dài.";continue;}
        $valid[]=['line'=>$line,'data'=>$r];
    }
    return [$valid,$errors];
}
