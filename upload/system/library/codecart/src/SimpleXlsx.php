<?php
namespace CodeCart\Core;

/**
 * Small dependency-free XLSX reader/writer used by CodeCart PRO catalog transfer.
 * Supports UTF-8 text, numeric/boolean cells, inline strings and sharedStrings.
 * It intentionally implements only the OOXML subset required by catalog transfer.
 */
final class SimpleXlsx {
    const NULL_TOKEN = '__CODECART_NULL_8F4E2B1D__';

    public static function writeAvailable() {
        return extension_loaded('zip') && class_exists('\\ZipArchive');
    }

    public static function readAvailable() {
        return extension_loaded('zip') && class_exists('\\ZipArchive') && extension_loaded('simplexml') && function_exists('simplexml_load_string');
    }

    public static function streamReadAvailable() {
        return self::readAvailable() && class_exists('\\XMLReader');
    }

    public static function getSheetNames($filename) {
        $map = self::sheetTargetMap($filename);
        return array_keys($map);
    }

    /**
     * Read one worksheet in bounded memory. The callback receives each associative data row.
     * Returns ['columns'=>[], 'rows'=>N]. Falls back to regular read when XMLReader is unavailable.
     */
    public static function streamSheet($filename, $sheetName, callable $callback) {
        if (!self::streamReadAvailable()) {
            $book = self::read($filename);
            if (!isset($book[$sheetName])) { return array('columns'=>array(), 'rows'=>0); }
            foreach ($book[$sheetName]['rows'] as $row) { $callback($row); }
            return array('columns'=>$book[$sheetName]['columns'], 'rows'=>count($book[$sheetName]['rows']));
        }
        $targets = self::sheetTargetMap($filename);
        if (!isset($targets[$sheetName])) { return array('columns'=>array(), 'rows'=>0); }
        $zip = new \ZipArchive();
        if ($zip->open($filename) !== true) { throw new \RuntimeException('Invalid XLSX archive.'); }
        $tmp = tempnam(sys_get_temp_dir(), 'ccpxlsx_');
        if (!$tmp) { $zip->close(); throw new \RuntimeException('Unable to create XLSX read buffer.'); }
        try {
            $stream = $zip->getStream($targets[$sheetName]);
            $out = fopen($tmp, 'wb');
            if (!$stream || !$out) { throw new \RuntimeException('Unable to open XLSX worksheet stream.'); }
            stream_copy_to_stream($stream, $out);
            fclose($stream); fclose($out);
            $shared = self::readSharedStrings($zip);
        } finally {
            $zip->close();
        }
        $reader = new \XMLReader();
        if (!$reader->open($tmp, null, LIBXML_NONET | LIBXML_COMPACT)) { @unlink($tmp); throw new \RuntimeException('Unable to parse XLSX worksheet.'); }
        $columns = array(); $rowCount = 0; $headerDone = false;
        try {
            while ($reader->read()) {
                if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'row') { continue; }
                $outer = $reader->readOuterXML();
                if ($outer === '') { continue; }
                $rowXml = self::xml($outer);
                $cells = array();
                foreach ($rowXml->c as $cell) {
                    $attr = $cell->attributes();
                    $idx = self::columnIndex((string)$attr['r']);
                    if ($idx < 0) { continue; }
                    $type = (string)$attr['t']; $value = null;
                    if ($type === 'inlineStr') { $value = self::inlineString($cell); }
                    elseif ($type === 's') { $si = isset($cell->v) ? (int)$cell->v : -1; $value = isset($shared[$si]) ? $shared[$si] : ''; }
                    elseif ($type === 'b') { $value = isset($cell->v) && (string)$cell->v === '1' ? '1' : '0'; }
                    elseif (isset($cell->v)) { $value = (string)$cell->v; }
                    if ($value === self::NULL_TOKEN) { $value = null; }
                    $cells[$idx] = $value;
                }
                if (!$headerDone) {
                    $headerDone = true;
                    if ($cells) {
                        $max = max(array_keys($cells));
                        for ($i=0; $i<=$max; $i++) { $name = isset($cells[$i]) ? trim((string)$cells[$i]) : ''; if ($name !== '') { $columns[$i] = $name; } }
                    }
                    continue;
                }
                $assoc = array(); $has = false;
                foreach ($columns as $idx=>$name) { $value = array_key_exists($idx,$cells)?$cells[$idx]:null; $assoc[$name]=$value; if ($value !== null && $value !== '') { $has=true; } }
                if ($has) { $callback($assoc); $rowCount++; }
            }
        } finally {
            $reader->close(); @unlink($tmp);
        }
        return array('columns'=>array_values($columns), 'rows'=>$rowCount);
    }

    private static function sheetTargetMap($filename) {
        if (!self::readAvailable() || !is_file($filename)) { throw new \RuntimeException('XLSX file not found or XML/ZIP support unavailable.'); }
        $zip = new \ZipArchive();
        if ($zip->open($filename) !== true) { throw new \RuntimeException('Invalid XLSX archive.'); }
        try {
            $workbookRaw=$zip->getFromName('xl/workbook.xml'); $relsRaw=$zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($workbookRaw===false || $relsRaw===false) { throw new \RuntimeException('Invalid XLSX workbook structure.'); }
            $workbook=self::xml($workbookRaw); $rels=self::xml($relsRaw); $relMap=array();
            foreach($rels->Relationship as $rel){$a=$rel->attributes();$relMap[(string)$a['Id']]=(string)$a['Target'];}
            $ns=$workbook->getNamespaces(true);$rNs=isset($ns['r'])?$ns['r']:'http://schemas.openxmlformats.org/officeDocument/2006/relationships';$out=array();
            if (!isset($workbook->sheets->sheet)) { return $out; }
            foreach($workbook->sheets->sheet as $sheet){$a=$sheet->attributes();$name=(string)$a['name'];$ra=$sheet->attributes($rNs);$rid=(string)$ra['id'];if(!isset($relMap[$rid]))continue;$target=str_replace('\\','/',$relMap[$rid]);$target=ltrim($target,'/');if(strpos($target,'../')!==false)continue;if(strpos($target,'xl/')!==0)$target='xl/'.$target;$out[$name]=$target;}
            return $out;
        } finally { $zip->close(); }
    }

    // Backward-compatible capability check means full read/write support.
    public static function isAvailable() {
        return self::readAvailable();
    }

    public static function write($filename, array $sheets) {
        if (!self::writeAvailable()) {
            throw new \RuntimeException('ZIP extension is required for XLSX export.');
        }
        if (!$sheets) {
            throw new \InvalidArgumentException('At least one worksheet is required.');
        }
        $dir = dirname($filename);
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \RuntimeException('Target directory is not writable.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create XLSX file.');
        }

        $names = array();
        $i = 1;
        foreach ($sheets as $name => $sheet) {
            $safe = self::sheetName($name, $names);
            $names[] = $safe;
            $xml = self::sheetXml(isset($sheet['columns']) ? $sheet['columns'] : array(), isset($sheet['rows']) ? $sheet['rows'] : array());
            self::zipAdd($zip, 'xl/worksheets/sheet' . $i . '.xml', $xml);
            $i++;
        }

        self::zipAdd($zip, '[Content_Types].xml', self::contentTypes(count($names)));
        self::zipAdd($zip, '_rels/.rels', self::rootRels());
        self::zipAdd($zip, 'docProps/app.xml', self::appXml($names));
        self::zipAdd($zip, 'docProps/core.xml', self::coreXml());
        self::zipAdd($zip, 'xl/workbook.xml', self::workbookXml($names));
        self::zipAdd($zip, 'xl/_rels/workbook.xml.rels', self::workbookRels(count($names)));
        self::zipAdd($zip, 'xl/styles.xml', self::stylesXml());
        if (!$zip->close()) { throw new \RuntimeException('Unable to finalize XLSX archive.'); }
        if (!is_file($filename) || filesize($filename) < 32) { throw new \RuntimeException('XLSX archive was not created.'); }
        return true;
    }

    /**
     * Streaming XLSX writer. Each sheet accepts columns plus either an iterable rows value
     * or a callable returning an iterable. Worksheet XML is written to a temporary file and
     * added to the ZIP without ever assembling the whole sheet in PHP memory.
     */
    public static function writeStreaming($filename, array $sheets) {
        if (!self::writeAvailable()) { throw new \RuntimeException('ZIP extension is required for XLSX export.'); }
        if (!$sheets) { throw new \InvalidArgumentException('At least one worksheet is required.'); }
        $dir = dirname($filename);
        if (!is_dir($dir) || !is_writable($dir)) { throw new \RuntimeException('Target directory is not writable.'); }

        $zip = new \ZipArchive();
        if ($zip->open($filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) { throw new \RuntimeException('Unable to create XLSX file.'); }
        $names = array(); $temps = array(); $i = 1;
        try {
            foreach ($sheets as $name => $sheet) {
                $safe = self::sheetName($name, $names); $names[] = $safe;
                $tmp = tempnam($dir, 'ccpsheet_');
                if (!$tmp) { throw new \RuntimeException('Unable to create temporary XLSX worksheet.'); }
                $temps[] = $tmp;
                $h = fopen($tmp, 'wb');
                if (!$h) { throw new \RuntimeException('Unable to open temporary XLSX worksheet.'); }
                try {
                    fwrite($h, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');
                    $columns = isset($sheet['columns']) ? array_values((array)$sheet['columns']) : array();
                    $rowNum = 1;
                    fwrite($h, '<row r="1">');
                    foreach ($columns as $col => $column) { fwrite($h, self::cellXml($col, $rowNum, (string)$column, false)); }
                    fwrite($h, '</row>');
                    $rowNum++;
                    $rows = isset($sheet['rows']) ? $sheet['rows'] : array();
                    if (is_callable($rows)) { $rows = call_user_func($rows); }
                    if (!is_array($rows) && !($rows instanceof \Traversable)) { throw new \RuntimeException('Invalid XLSX row provider for sheet ' . $safe); }
                    foreach ($rows as $row) {
                        fwrite($h, '<row r="' . $rowNum . '">');
                        foreach ($columns as $col => $column) {
                            $value = is_array($row) && array_key_exists($column, $row) ? $row[$column] : null;
                            fwrite($h, self::cellXml($col, $rowNum, $value === null ? self::NULL_TOKEN : $value, $value !== null && (is_int($value) || is_float($value))));
                        }
                        fwrite($h, '</row>');
                        $rowNum++;
                    }
                    fwrite($h, '</sheetData></worksheet>');
                } finally { fclose($h); }
                if (!$zip->addFile($tmp, 'xl/worksheets/sheet' . $i . '.xml')) { throw new \RuntimeException('Unable to add XLSX worksheet: ' . $safe); }
                $i++;
            }
            self::zipAdd($zip, '[Content_Types].xml', self::contentTypes(count($names)));
            self::zipAdd($zip, '_rels/.rels', self::rootRels());
            self::zipAdd($zip, 'docProps/app.xml', self::appXml($names));
            self::zipAdd($zip, 'docProps/core.xml', self::coreXml());
            self::zipAdd($zip, 'xl/workbook.xml', self::workbookXml($names));
            self::zipAdd($zip, 'xl/_rels/workbook.xml.rels', self::workbookRels(count($names)));
            self::zipAdd($zip, 'xl/styles.xml', self::stylesXml());
            if (!$zip->close()) { throw new \RuntimeException('Unable to finalize XLSX archive.'); }
        } catch (\Throwable $e) {
            try { $zip->close(); } catch (\Throwable $ignored) {}
            @unlink($filename);
            throw $e;
        } finally {
            foreach ($temps as $tmp) { @unlink($tmp); }
        }
        if (!is_file($filename) || filesize($filename) < 32) { throw new \RuntimeException('XLSX archive was not created.'); }
        return true;
    }

    public static function read($filename) {
        if (!self::readAvailable()) {
            throw new \RuntimeException('ZIP and SimpleXML extensions are required for XLSX import.');
        }
        if (!is_file($filename)) {
            throw new \RuntimeException('XLSX file not found.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($filename) !== true) {
            throw new \RuntimeException('Invalid XLSX archive.');
        }
        try {
            $workbookRaw = $zip->getFromName('xl/workbook.xml');
            $relsRaw = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($workbookRaw === false || $relsRaw === false) {
                throw new \RuntimeException('Invalid XLSX workbook structure.');
            }
            $workbook = self::xml($workbookRaw);
            $rels = self::xml($relsRaw);
            $relMap = array();
            foreach ($rels->Relationship as $rel) {
                $attr = $rel->attributes();
                $relMap[(string)$attr['Id']] = (string)$attr['Target'];
            }
            $shared = self::readSharedStrings($zip);
            $ns = $workbook->getNamespaces(true);
            $rNs = isset($ns['r']) ? $ns['r'] : 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
            $result = array();
            if (!isset($workbook->sheets->sheet)) {
                return $result;
            }
            foreach ($workbook->sheets->sheet as $sheet) {
                $attrs = $sheet->attributes();
                $name = (string)$attrs['name'];
                $rAttrs = $sheet->attributes($rNs);
                $rid = (string)$rAttrs['id'];
                if (!isset($relMap[$rid])) {
                    continue;
                }
                $target = str_replace('\\', '/', $relMap[$rid]);
                $target = ltrim($target, '/');
                if (strpos($target, '../') !== false) {
                    continue;
                }
                if (strpos($target, 'xl/') !== 0) {
                    $target = 'xl/' . $target;
                }
                $raw = $zip->getFromName($target);
                if ($raw === false) {
                    continue;
                }
                $result[$name] = self::parseSheet($raw, $shared);
            }
            return $result;
        } finally {
            $zip->close();
        }
    }

    public static function inspect($filename) {
        $sheets = self::read($filename);
        $out = array();
        foreach ($sheets as $name => $sheet) {
            $out[$name] = array(
                'columns' => isset($sheet['columns']) ? $sheet['columns'] : array(),
                'rows' => isset($sheet['rows']) ? count($sheet['rows']) : 0
            );
        }
        return $out;
    }

    private static function zipAdd(\ZipArchive $zip, $name, $contents) {
        if (!$zip->addFromString((string)$name, (string)$contents)) {
            throw new \RuntimeException('Unable to add XLSX entry: ' . (string)$name);
        }
    }

    private static function parseSheet($raw, array $shared) {
        $xml = self::xml($raw);
        $rows = array();
        $maxCol = -1;
        $matrix = array();
        if (isset($xml->sheetData->row)) {
            foreach ($xml->sheetData->row as $row) {
                $cells = array();
                foreach ($row->c as $cell) {
                    $attr = $cell->attributes();
                    $ref = (string)$attr['r'];
                    $idx = self::columnIndex($ref);
                    if ($idx < 0) { continue; }
                    $type = (string)$attr['t'];
                    $value = null;
                    if ($type === 'inlineStr') {
                        $value = self::inlineString($cell);
                    } elseif ($type === 's') {
                        $si = isset($cell->v) ? (int)$cell->v : -1;
                        $value = isset($shared[$si]) ? $shared[$si] : '';
                    } elseif ($type === 'b') {
                        $value = isset($cell->v) && (string)$cell->v === '1' ? '1' : '0';
                    } elseif (isset($cell->v)) {
                        $value = (string)$cell->v;
                    }
                    if ($value === self::NULL_TOKEN) { $value = null; }
                    $cells[$idx] = $value;
                    if ($idx > $maxCol) { $maxCol = $idx; }
                }
                $matrix[] = $cells;
            }
        }
        if (!$matrix) {
            return array('columns' => array(), 'rows' => array());
        }
        $headerCells = array_shift($matrix);
        $columns = array();
        $headerMax = $headerCells ? max(array_keys($headerCells)) : -1;
        for ($i=0; $i <= $headerMax; $i++) {
            $name = isset($headerCells[$i]) ? trim((string)$headerCells[$i]) : '';
            if ($name !== '') { $columns[$i] = $name; }
        }
        $dataRows = array();
        foreach ($matrix as $cells) {
            $assoc = array();
            $has = false;
            foreach ($columns as $idx => $name) {
                $value = array_key_exists($idx, $cells) ? $cells[$idx] : null;
                $assoc[$name] = $value;
                if ($value !== null && $value !== '') { $has = true; }
            }
            if ($has) { $dataRows[] = $assoc; }
        }
        return array('columns' => array_values($columns), 'rows' => $dataRows);
    }

    private static function readSharedStrings(\ZipArchive $zip) {
        $raw = $zip->getFromName('xl/sharedStrings.xml');
        if ($raw === false) { return array(); }
        $xml = self::xml($raw);
        $out = array();
        foreach ($xml->si as $si) {
            $text = '';
            if (isset($si->t)) { $text .= (string)$si->t; }
            if (isset($si->r)) {
                foreach ($si->r as $run) { if (isset($run->t)) { $text .= (string)$run->t; } }
            }
            $out[] = $text;
        }
        return $out;
    }

    private static function inlineString($cell) {
        if (!isset($cell->is)) { return ''; }
        $text = '';
        if (isset($cell->is->t)) { $text .= (string)$cell->is->t; }
        if (isset($cell->is->r)) {
            foreach ($cell->is->r as $run) { if (isset($run->t)) { $text .= (string)$run->t; } }
        }
        return $text;
    }

    private static function xml($raw) {
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if ($xml === false) { throw new \RuntimeException('Invalid XML inside XLSX.'); }
        return $xml;
    }

    private static function sheetXml(array $columns, $rows) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        $rowNum = 1;
        $xml .= '<row r="1">';
        foreach (array_values($columns) as $i => $column) {
            $xml .= self::cellXml($i, $rowNum, (string)$column, false);
        }
        $xml .= '</row>';
        $rowNum++;
        foreach ($rows as $row) {
            $xml .= '<row r="' . $rowNum . '">';
            foreach (array_values($columns) as $i => $column) {
                $value = is_array($row) && array_key_exists($column, $row) ? $row[$column] : null;
                if ($value === null) {
                    $xml .= self::cellXml($i, $rowNum, self::NULL_TOKEN, false);
                } else {
                    $xml .= self::cellXml($i, $rowNum, $value, is_int($value) || is_float($value));
                }
            }
            $xml .= '</row>';
            $rowNum++;
        }
        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private static function cellXml($col, $row, $value, $numeric) {
        $ref = self::columnName($col) . $row;
        if ($numeric && is_finite((float)$value)) {
            return '<c r="' . $ref . '"><v>' . self::esc((string)$value) . '</v></c>';
        }
        $text = self::cleanText((string)$value);
        // OOXML inlineStr cells are literal text (never formula elements). For additional
        // spreadsheet hardening, mark formula-like user strings with the Excel quotePrefix
        // style as well. The visible/imported value is unchanged; only the cell style says
        // "treat this as literal text" to Excel-compatible applications.
        $style = self::needsFormulaGuard($text) ? ' s="1"' : '';
        return '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">' . self::esc($text) . '</t></is></c>';
    }

    private static function needsFormulaGuard($text) {
        return preg_match('/^[\x00-\x20]*[=+\-@]/u', (string)$text) === 1;
    }

    private static function cleanText($text) {
        return preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text);
    }
    private static function esc($text) { return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
    private static function columnName($index) {
        $name = '';
        $n = (int)$index + 1;
        while ($n > 0) { $n--; $name = chr(65 + ($n % 26)) . $name; $n = intdiv($n, 26); }
        return $name;
    }
    private static function columnIndex($ref) {
        if (!preg_match('/^([A-Z]+)[0-9]+$/i', $ref, $m)) { return -1; }
        $letters = strtoupper($m[1]); $n = 0;
        for ($i=0,$l=strlen($letters); $i<$l; $i++) { $n = $n * 26 + (ord($letters[$i]) - 64); }
        return $n - 1;
    }
    private static function sheetName($name, array $used) {
        $name = trim((string)$name);
        $name = str_replace(array('\\', '/', '?', '*', '[', ']', ':'), '_', $name);
        if ($name === '') { $name = 'Sheet'; }
        $name = function_exists('mb_substr') ? mb_substr($name, 0, 31, 'UTF-8') : substr($name, 0, 31);
        $base = $name; $i = 2;
        while (in_array($name, $used, true)) {
            $suffix = '_' . $i++;
            $limit = 31 - strlen($suffix);
            $name = (function_exists('mb_substr') ? mb_substr($base, 0, $limit, 'UTF-8') : substr($base, 0, $limit)) . $suffix;
        }
        return $name;
    }

    private static function contentTypes($count) {
        $x='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
        for($i=1;$i<=$count;$i++){$x.='<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';}
        return $x.'</Types>';
    }
    private static function rootRels(){return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';}
    private static function workbookRels($count){$x='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';for($i=1;$i<=$count;$i++){$x.='<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';}$x.='<Relationship Id="rId'.($count+1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';return $x;}
    private static function workbookXml(array $names){$x='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';foreach($names as $i=>$name){$x.='<sheet name="'.self::esc($name).'" sheetId="'.($i+1).'" r:id="rId'.($i+1).'"/>'; }return $x.'</sheets></workbook>';}
    private static function stylesXml(){return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" quotePrefix="1"/></cellXfs></styleSheet>';}
    private static function coreXml(){return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>CodeCart PRO</dc:creator><cp:lastModifiedBy>CodeCart PRO</cp:lastModifiedBy></cp:coreProperties>';}
    private static function appXml(array $names){$titles='';foreach($names as $name){$titles.='<vt:lpstr>'.self::esc($name).'</vt:lpstr>'; }return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>CodeCart</Application><TitlesOfParts><vt:vector size="'.count($names).'" baseType="lpstr">'.$titles.'</vt:vector></TitlesOfParts></Properties>';}
}
