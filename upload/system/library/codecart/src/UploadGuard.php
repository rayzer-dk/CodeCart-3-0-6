<?php
namespace CodeCart\Core;

final class UploadGuard {
    const GENERIC_MAX_BYTES = 6291456;
    const EXTENSION_MAX_BYTES = 67108864;
    const MODIFICATION_MAX_BYTES = 2097152;
    const SQL_BACKUP_MAX_BYTES = 134217728;

    private static $dangerousExtensions = array(
        'php','php3','php4','php5','php7','php8','phtml','pht','phar','cgi','pl','py','sh','bash','cmd','bat','exe','com','scr','dll','so','htaccess','user.ini'
    );

    public static function validateGeneric(array $file, $extensionAllowed, $mimeAllowed, $maxBytes = 0, $maxNameLength = 128) {
        $result = self::basicUploadCheck($file, $maxBytes ?: self::GENERIC_MAX_BYTES, $maxNameLength);
        if (!$result['ok']) {
            return $result;
        }

        $filename = $result['filename'];
        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === '' || in_array($extension, self::$dangerousExtensions, true)) {
            return self::error('filetype');
        }

        $extensions = self::normalizeList($extensionAllowed);
        if (!$extensions || !in_array($extension, $extensions, true)) {
            return self::error('filetype');
        }

        $mimes = self::normalizeList($mimeAllowed, false);
        $mime = self::detectMime($file['tmp_name']);
        if (!$mime || !$mimes || !in_array(strtolower($mime), $mimes, true)) {
            return self::error('filetype');
        }

        // Read only the first 1 MiB. This catches executable PHP payloads without
        // loading a large customer upload into PHP memory.
        $handle = @fopen($file['tmp_name'], 'rb');
        if (!$handle) {
            return self::error('upload');
        }
        $sample = (string)fread($handle, 1048576);
        fclose($handle);
        if (preg_match('/<\?(?:php|=)/i', $sample)) {
            return self::error('filetype');
        }

        return array('ok' => true, 'filename' => $filename, 'mime' => $mime, 'size' => (int)$file['size']);
    }


    public static function validateSqlBackup(array $file, $maxBytes = 0) {
        $result = self::basicUploadCheck($file, $maxBytes ?: self::SQL_BACKUP_MAX_BYTES, 255);
        if (!$result['ok']) {
            return $result;
        }

        $filename = $result['filename'];
        if (strtolower((string)pathinfo($filename, PATHINFO_EXTENSION)) !== 'sql') {
            return self::error('filetype');
        }

        $mime = self::detectMime($file['tmp_name']);
        $allowed = array('text/plain', 'application/sql', 'application/octet-stream', 'text/x-sql');
        if (!$mime || !in_array(strtolower($mime), $allowed, true)) {
            return self::error('filetype');
        }

        $handle = @fopen($file['tmp_name'], 'rb');
        if (!$handle) {
            return self::error('upload');
        }
        $sample = (string)fread($handle, 1048576);
        fclose($handle);

        if ($sample === '' || strpos($sample, "\0") !== false || preg_match('/<\?(?:php|=)/i', $sample)) {
            return self::error('filetype');
        }
        if (!preg_match('/(?:^|\R)\s*(?:TRUNCATE\s+TABLE|INSERT\s+INTO)\s+/i', $sample)) {
            return self::error('filetype');
        }

        return array('ok' => true, 'filename' => $filename, 'mime' => $mime, 'size' => (int)$file['size']);
    }

    public static function validateInstallerZip(array $file, $maxBytes = 0) {
        $result = self::basicUploadCheck($file, $maxBytes ?: self::EXTENSION_MAX_BYTES, 255);
        if (!$result['ok']) {
            return $result;
        }

        $filename = $result['filename'];
        if (substr(strtolower($filename), -10) !== '.ocmod.zip') {
            return self::error('filetype');
        }

        $mime = self::detectMime($file['tmp_name']);
        $zipMimes = array('application/zip','application/x-zip','application/x-zip-compressed','application/octet-stream');
        if (!$mime || !in_array(strtolower($mime), $zipMimes, true)) {
            return self::error('filetype');
        }

        if (!class_exists('ZipArchive')) {
            return self::error('zip');
        }

        $zip = new \ZipArchive();
        $opened = $zip->open($file['tmp_name'], \ZipArchive::CHECKCONS);
        if ($opened !== true) {
            if ($opened === true) { $zip->close(); }
            return self::error('unsafe_archive');
        }
        $count = (int)$zip->numFiles;
        $zip->close();
        if ($count < 1 || $count > 20000) {
            return self::error('unsafe_archive');
        }

        return array('ok' => true, 'filename' => $filename, 'mime' => $mime, 'size' => (int)$file['size']);
    }

    public static function validateModificationXml(array $file, $expectedFilename = '', $maxBytes = 0) {
        $result = self::basicUploadCheck($file, $maxBytes ?: self::MODIFICATION_MAX_BYTES, 255);
        if (!$result['ok']) {
            return $result;
        }

        $filename = $result['filename'];
        if (strtolower((string)pathinfo($filename, PATHINFO_EXTENSION)) !== 'xml') {
            return self::error('filetype');
        }
        if ($expectedFilename !== '' && $filename !== basename($expectedFilename)) {
            return self::error('filetype');
        }
        if (!class_exists('DOMDocument')) {
            return self::error('xml');
        }

        $xml = @file_get_contents($file['tmp_name']);
        if ($xml === false || $xml === '') {
            return self::error('xml');
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$ok || strtolower((string)$dom->documentElement->nodeName) !== 'modification') {
            return self::error('xml');
        }
        $scan = self::scanModificationDom($dom);
        if (!$scan['ok']) {
            return $scan;
        }

        return array('ok' => true, 'filename' => $filename, 'mime' => 'application/xml', 'size' => (int)$file['size']);
    }

    public static function scanExtractedExtension($root) {
        $rootReal = realpath($root);
        if (!$rootReal || !is_dir($rootReal)) {
            return array('ok' => false, 'code' => 'unsafe_archive', 'message' => 'Invalid extraction directory.');
        }
        $rootPrefix = rtrim(str_replace('\\', '/', $rootReal), '/') . '/';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($rootReal, \FilesystemIterator::SKIP_DOTS));
        $files = 0;
        foreach ($iterator as $item) {
            if (!$item->isFile()) { continue; }
            $files++;
            if ($files > 20000) {
                return array('ok' => false, 'code' => 'unsafe_archive', 'message' => 'Too many files.');
            }
            $real = $item->getRealPath();
            $normalized = $real ? str_replace('\\', '/', $real) : '';
            if (!$real || strpos($normalized, $rootPrefix) !== 0) {
                return array('ok' => false, 'code' => 'unsafe_archive', 'message' => 'File escapes extraction directory.');
            }
            $relative = substr($normalized, strlen($rootPrefix));
            $extension = strtolower((string)pathinfo($relative, PATHINFO_EXTENSION));
            $basename = strtolower((string)basename($relative));
            if ($basename === '.htaccess' || $basename === '.user.ini') {
                return array('ok' => false, 'code' => 'unsafe_code', 'message' => 'Server configuration file is not allowed.');
            }
            if (strpos($relative, 'upload/image/') === 0 && in_array($extension, self::$dangerousExtensions, true)) {
                return array('ok' => false, 'code' => 'unsafe_code', 'message' => 'Executable file is not allowed in image/.');
            }
            if ($item->getSize() > 16 * 1024 * 1024) { continue; }
            if ($extension === 'xml' && ($basename === 'install.xml' || substr($basename, -10) === '.ocmod.xml')) {
                if (!class_exists('DOMDocument')) {
                    return array('ok' => false, 'code' => 'xml', 'message' => 'PHP DOM extension is required for modification XML validation.');
                }
                $xml = @file_get_contents($real);
                if ($xml === false || $xml === '') {
                    return array('ok' => false, 'code' => 'xml', 'message' => 'Modification XML cannot be read.');
                }
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $previous = libxml_use_internal_errors(true);
                $ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
                if (!$ok) {
                    return array('ok' => false, 'code' => 'xml', 'message' => 'Invalid modification XML.');
                }
                $scan = self::scanModificationDom($dom);
                if (!$scan['ok']) { return $scan; }
            }
            if ($extension === 'php') {
                $code = @file_get_contents($real);
                if ($code === false) { continue; }
                $tokens = @token_get_all($code);
                $dangerous = array('shell_exec','proc_open','passthru','system','popen','exec','create_function','gzinflate','gzuncompress','str_rot13');
                $count = count($tokens);
                for ($i = 0; $i < $count; $i++) {
                    $token = $tokens[$i];
                    if (is_array($token) && $token[0] === T_EVAL) {
                        return array('ok' => false, 'code' => 'unsafe_code', 'message' => 'eval() is not allowed.');
                    }
                    if (!is_array($token) || $token[0] !== T_STRING) { continue; }
                    $name = strtolower($token[1]);
                    if (!in_array($name, $dangerous, true) && $name !== 'assert') { continue; }
                    $prev = $i - 1;
                    while ($prev >= 0 && is_array($tokens[$prev]) && in_array($tokens[$prev][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) { $prev--; }
                    if ($prev >= 0 && is_array($tokens[$prev]) && in_array($tokens[$prev][0], array(T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION), true)) { continue; }
                    $j = $i + 1;
                    while ($j < $count && is_array($tokens[$j]) && in_array($tokens[$j][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), true)) { $j++; }
                    if ($j < $count && $tokens[$j] === '(') {
                        return array('ok' => false, 'code' => 'unsafe_code', 'message' => $name . '() is not allowed.');
                    }
                }
            } elseif ($extension === 'js') {
                $code = @file_get_contents($real, false, null, 0, 1048576);
                if ($code !== false && preg_match('/\beval\s*\(/i', $code)) {
                    return array('ok' => false, 'code' => 'unsafe_code', 'message' => 'JavaScript eval() is not allowed.');
                }
            }
        }
        return array('ok' => true);
    }

    private static function scanModificationDom(\DOMDocument $dom) {
        $nodes = $dom->getElementsByTagName('add');
        foreach ($nodes as $node) {
            $code = (string)$node->textContent;
            if ($code === '') { continue; }
            if (preg_match('/\beval\s*\(/i', $code)) {
                return array('ok' => false, 'code' => 'unsafe_code', 'message' => 'eval() is not allowed in modification code.');
            }
            if (preg_match('/\b(?:shell_exec|proc_open|passthru|system|popen|exec|create_function|gzinflate|gzuncompress|str_rot13)\s*\(/i', $code, $m)) {
                return array('ok' => false, 'code' => 'unsafe_code', 'message' => strtolower($m[0]) . ' is not allowed in modification code.');
            }
            if (preg_match('/\bassert\s*\(/i', $code)) {
                return array('ok' => false, 'code' => 'unsafe_code', 'message' => 'assert() is not allowed in modification code.');
            }
            if (preg_match('/\b(?:base64_decode\s*\([^)]*\)\s*(?:;|\)|,)?\s*){2,}/i', $code)) {
                return array('ok' => false, 'code' => 'unsafe_code', 'message' => 'Suspicious encoded modification payload is not allowed.');
            }
        }
        return array('ok' => true);
    }

    private static function basicUploadCheck(array $file, $maxBytes, $maxNameLength) {
        if (empty($file['name']) || empty($file['tmp_name']) || !isset($file['error'])) {
            return self::error('upload');
        }
        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            return array('ok' => false, 'code' => 'upload_' . (int)$file['error']);
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return self::error('upload');
        }
        $size = isset($file['size']) ? (int)$file['size'] : (int)@filesize($file['tmp_name']);
        if ($size <= 0) {
            return self::error('upload');
        }
        if ($maxBytes > 0 && $size > (int)$maxBytes) {
            return self::error('filesize');
        }
        $filename = basename(html_entity_decode((string)$file['name'], ENT_QUOTES, 'UTF-8'));
        $filename = preg_replace('/[\x00-\x1F\x7F]+/u', '', $filename);
        if ($filename === '' || self::stringLength($filename) < 3 || self::stringLength($filename) > (int)$maxNameLength) {
            return self::error('filename');
        }
        return array('ok' => true, 'filename' => $filename, 'size' => $size);
    }

    private static function stringLength($value) {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen((string)$value, 'UTF-8');
        }
        if (function_exists('utf8_strlen')) {
            return (int)utf8_strlen((string)$value);
        }
        return strlen((string)$value);
    }

    private static function detectMime($file) {
        if (!class_exists('finfo')) { return false; }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($file);
    }

    private static function normalizeList($value, $lower = true) {
        $value = preg_replace('~\r?\n~', "\n", (string)$value);
        $rows = array_filter(array_map('trim', explode("\n", $value)), 'strlen');
        if ($lower) {
            $rows = array_map('strtolower', $rows);
        } else {
            $rows = array_map('strtolower', $rows);
        }
        return array_values(array_unique($rows));
    }

    private static function error($code) {
        return array('ok' => false, 'code' => $code);
    }
}
