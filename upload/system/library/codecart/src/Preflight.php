<?php
namespace CodeCart\Core;

final class Preflight {
    private $registry;
    private $db;
    private $config;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
    }

    public function rows(): array {
        $rows = array();

        $phpOk = version_compare(PHP_VERSION, '8.1.0', '>=') && version_compare(PHP_VERSION, '8.6.0', '<');
        $this->row($rows, 'runtime.php', 'PHP', PHP_VERSION . ' / ' . PHP_SAPI, $phpOk ? 'ok' : 'error', 'Supported: PHP 8.1–8.5 in the WEB/FPM profile.');

        foreach (array(
            'mysqli' => extension_loaded('mysqli'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'gd' => extension_loaded('gd'),
            'curl' => extension_loaded('curl'),
            'mbstring' => extension_loaded('mbstring'),
            'intl' => extension_loaded('intl'),
            'dom' => extension_loaded('dom'),
            'xmlwriter' => extension_loaded('xmlwriter'),
            'zip' => extension_loaded('zip'),
            'simplexml' => extension_loaded('simplexml') && function_exists('simplexml_load_string'),
            'openssl' => extension_loaded('openssl'),
            'fileinfo' => extension_loaded('fileinfo')
        ) as $name => $ok) {
            $this->row($rows, 'ext.' . $name, $name, $ok ? 'loaded' : 'missing', $ok ? 'ok' : 'error', 'Required WEB/FPM extension.');
        }

        $xmlReader = class_exists('\\XMLReader');
        $this->row($rows, 'ext.xmlreader', 'XMLReader', $xmlReader ? 'loaded' : 'missing', $xmlReader ? 'ok' : 'warning', $xmlReader ? 'Large XLSX imports use streaming XMLReader mode.' : 'Regular XLSX import remains available through SimpleXML, but very large workbooks can require significantly more memory.');

        if (extension_loaded('gd')) {
            foreach (array(
                'JPEG' => function_exists('imagejpeg'),
                'PNG' => function_exists('imagepng'),
                'WebP' => function_exists('imagewebp'),
                'AVIF' => function_exists('imageavif') && function_exists('imagecreatefromavif')
            ) as $name => $ok) {
                $state = $ok ? 'ok' : ($name === 'AVIF' ? 'warning' : 'error');
                $this->row($rows, 'gd.' . strtolower($name), 'GD ' . $name, $ok ? 'supported' : 'missing', $state, $name === 'AVIF' ? 'Recommended for native AVIF support.' : 'Required image format.');
            }
        }

        $opcache = extension_loaded('Zend OPcache') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN);
        $this->row($rows, 'runtime.opcache', 'OPcache', $opcache ? 'enabled' : 'disabled', $opcache ? 'ok' : 'warning', 'Recommended for production.');

        foreach (array('memory_limit','upload_max_filesize','post_max_size','max_input_vars','max_execution_time') as $key) {
            $this->row($rows, 'ini.' . $key, $key, (string)ini_get($key), 'info', 'Current WEB/FPM value.');
        }
        $fileLimit = (int)$this->config->get('config_file_max_size');
        if ($fileLimit <= 0) { $fileLimit = \CodeCart\Core\UploadGuard::GENERIC_MAX_BYTES; }
        $this->row($rows, 'upload.store_limit', 'Store file upload limit', sprintf('%.1f MB', $fileLimit / 1048576), 'ok', 'Applied to customer/admin generic file uploads in addition to PHP limits.');
        $this->row($rows, 'upload.extension_limit', 'Extension installer limit', sprintf('%.0f MB', \CodeCart\Core\UploadGuard::EXTENSION_MAX_BYTES / 1048576), 'ok', 'Compressed .ocmod.zip limit; extracted archives also have entry/path safety limits.');

        $displayErrors = filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN);
        $this->row($rows, 'security.display_errors', 'display_errors', $displayErrors ? 'On' : 'Off', $displayErrors ? 'warning' : 'ok', 'Production should use Off with log_errors On.');

        $https = function_exists('codecart_is_https') ? codecart_is_https((array)$this->registry->get('request')->server) : false;
        $this->row($rows, 'http.https', 'HTTPS', $https ? 'active' : 'not detected', $https ? 'ok' : 'warning', 'Required for production checkout and secure cookies.');

        foreach (array('storage'=>DIR_STORAGE,'cache'=>DIR_CACHE,'logs'=>DIR_LOGS,'image'=>DIR_IMAGE) as $name => $path) {
            $ok = is_dir($path) && is_writable($path);
            $this->row($rows, 'path.' . $name, $name, $path, $ok ? 'ok' : 'error', $ok ? 'Writable.' : 'Directory must exist and be writable.');
        }

        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string)$_SERVER['DOCUMENT_ROOT']) : false;
        $storage = realpath(DIR_STORAGE);
        if ($documentRoot && $storage) {
            $outside = strpos($storage, rtrim($documentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) !== 0;
            $this->row($rows, 'path.storage_public', 'Storage exposure', $outside ? 'outside document root' : 'inside document root', $outside ? 'ok' : 'warning', 'Prefer storage outside the public web root.');
        }

        try {
            $version = $this->db->query('SELECT VERSION() AS version');
            $this->row($rows, 'db.version', 'MySQL/MariaDB', $version->num_rows ? (string)$version->row['version'] : 'unknown', 'ok', '');

            $mode = $this->db->query('SELECT @@SESSION.sql_mode AS sql_mode');
            $sqlMode = $mode->num_rows ? strtoupper((string)$mode->row['sql_mode']) : '';
            $strict = strpos($sqlMode, 'STRICT_TRANS_TABLES') !== false || strpos($sqlMode, 'STRICT_ALL_TABLES') !== false;
            $strictRequested = (int)$this->config->get('codecart_db_strict_mode') === 1;
            $this->row($rows, 'db.strict_sql', 'Strict SQL mode', $strict ? 'active' : 'not active', ($strict || !$strictRequested) ? 'ok' : 'warning', $strict ? 'Core is running with a strict SQL mode.' : ($strictRequested ? 'Compatibility SQL mode is active. Use strict SQL mode after the compatibility check reports no blockers.' : 'OpenCart-compatible SQL mode (default for OpenCart/ocStore modules).'));

            $bad = $this->db->query("SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA='" . $this->db->escape(DB_DATABASE) . "' AND (ENGINE<>'InnoDB' OR TABLE_COLLATION NOT LIKE 'utf8mb4%')");
            $count = (int)($bad->row['total'] ?? 0);
            $this->row($rows, 'db.modern', 'DB engine/charset', $count ? 'legacy tables: ' . $count : 'InnoDB / utf8mb4', $count ? 'warning' : 'ok', $count ? 'Use Database Modernizer only after a full backup.' : '');

            $expected = defined('CODECART_BUILD') ? (string)CODECART_BUILD : '3.0.6.0';
            $actual = trim((string)$this->config->get('codecart_core_schema_version'));
            $schemaOk = $actual !== '' && $actual === $expected;
            $this->row($rows, 'db.schema_version', 'Core schema version', $actual !== '' ? $actual : 'not recorded', $schemaOk ? 'ok' : 'warning', $schemaOk ? 'Schema version matches this build.' : 'Core schema version differs from this build. Run the migration check.');

            if ($this->tableExists('codecart_migration')) {
                $migration = $this->db->query("SELECT migration,version,date_applied FROM `" . DB_PREFIX . "codecart_migration` WHERE scope='core' ORDER BY migration_id DESC LIMIT 1");
                $value = $migration->num_rows ? (string)$migration->row['version'] . ' / ' . (string)$migration->row['date_applied'] : 'no history';
                // A clean install creates the current schema directly and has no migration
                // rows; that is only a problem when the recorded schema does not match.
                $historyOk = $migration->num_rows || $schemaOk;
                $this->row($rows, 'db.migration_history', 'Migration history', $value, $historyOk ? 'ok' : 'warning', $migration->num_rows ? 'Latest recorded Core migration.' : ($schemaOk ? 'Clean installation: schema created by the installer.' : 'No CodeCart PRO Core migration records are available yet.'));
            }
        } catch (\Throwable $e) {
            $this->row($rows, 'db.connection', 'Database', 'check failed', 'error', $e->getMessage());
        }

        try {
            if ($this->tableExists('codecart_scheduler')) {
                $q = $this->db->query("SELECT date_last,last_status,last_message FROM `" . DB_PREFIX . "codecart_scheduler` WHERE code='core.health.check' LIMIT 1");
                if ($q->num_rows) {
                    $last = (string)$q->row['date_last'];
                    $state = $last !== '' && $last !== '0000-00-00 00:00:00' ? ((string)$q->row['last_status'] === 'error' ? 'warning' : 'ok') : 'warning';
                    $this->row($rows, 'cron.health', 'Cron heartbeat', $last ?: 'never', $state, trim((string)$q->row['last_status'] . ' ' . (string)$q->row['last_message']));
                }
            }
        } catch (\Throwable $e) {}

        try {
            if ($this->tableExists('codecart_queue')) {
                $q = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_queue` WHERE status='failed'");
                $count = (int)($q->row['total'] ?? 0);
                $this->row($rows, 'queue.failed', 'Queue failed', $count, $count ? 'warning' : 'ok', $count ? 'Review Scheduler / Queue.' : '');
            }
        } catch (\Throwable $e) {}

        $mail = (string)$this->config->get('config_mail_engine');
        if ($mail === 'smtp') {
            $host = trim((string)$this->config->get('config_mail_smtp_hostname'));
            $port = (int)$this->config->get('config_mail_smtp_port');
            $configured = $host !== '' && $port > 0;
            $this->row($rows, 'mail.smtp', 'SMTP', $configured ? $host . ':' . $port : 'not configured', $configured ? 'ok' : 'warning', 'Configuration check only; delivery still requires a send-test.');
        } else {
            $this->row($rows, 'mail.engine', 'Mail engine', $mail !== '' ? $mail : 'mail', 'info', 'Current OpenCart mail engine.');
        }

        $free = @disk_free_space(DIR_STORAGE);
        $total = @disk_total_space(DIR_STORAGE);
        if ($free !== false && $total > 0) {
            $percent = 100 * $free / $total;
            // Absolute free space matters more than a percentage: 27 GB free on a large
            // volume is healthy even when it is only 10% of the disk.
            $state = ($free < 1073741824) ? 'error' : (($free < 5368709120 || $percent < 5) ? 'warning' : 'ok');
            $this->row($rows, 'disk.free', 'Disk free', sprintf('%.2f GB / %.1f%%', $free / 1073741824, $percent), $state, $state === 'ok' ? '' : 'Low disk space can break cache, image generation, logs and updates.');
        }

        return $rows;
    }

    public function actionable(): array {
        return array_values(array_filter($this->rows(), function($row) {
            return in_array($row['state'], array('error','warning'), true);
        }));
    }

    private function tableExists(string $table): bool {
        try {
            $q = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . $table) . "'");
            return (bool)$q->num_rows;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function row(array &$rows, string $id, string $component, $value, string $state, string $message): void {
        $rows[] = array('id'=>$id,'component'=>$component,'value'=>(string)$value,'state'=>$state,'message'=>$message);
    }
}
