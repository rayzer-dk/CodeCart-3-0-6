<?php
class ModelExtensionModuleSettingsCleanerFiles extends Model {
    private $types = array('payment', 'shipping', 'module', 'analytics', 'captcha', 'feed', 'total', 'report');
    private $maxFiles = 40;
    private $maxBytes = 8388608;

    public function install() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "settings_cleaner_file_backup` (
            `file_backup_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `code` VARCHAR(64) NOT NULL,
            `user_id` INT NOT NULL,
            `date_added` DATETIME NOT NULL,
            `file_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`file_backup_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "settings_cleaner_file_backup_row` (
            `file_backup_row_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `file_backup_id` INT UNSIGNED NOT NULL,
            `relative_path` VARCHAR(512) NOT NULL,
            `sha256` CHAR(64) NOT NULL,
            `content_base64` MEDIUMTEXT NOT NULL,
            `removed` TINYINT(1) NOT NULL DEFAULT 0,
            `restored` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`file_backup_row_id`),
            KEY `file_backup_id` (`file_backup_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "settings_cleaner_file_backup_row`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "settings_cleaner_file_backup`");
    }

    private function validCode($code) {
        return is_string($code) && preg_match('/^[a-z][a-z0-9_]{1,63}$/D', $code) === 1;
    }

    private function roots() {
        return array('admin' => rtrim(DIR_APPLICATION, '/\\') . DIRECTORY_SEPARATOR,
                     'catalog' => rtrim(DIR_CATALOG, '/\\') . DIRECTORY_SEPARATOR);
    }

    private function safePath($relative, $forExisting) {
        if (!is_string($relative) || strlen($relative) > 512 || strpos($relative, '..') !== false || strpos($relative, '\\') !== false) {
            throw new RuntimeException('unsafe_path');
        }
        if (!preg_match('~^(admin|catalog)/(controller|model|view|language)/~D', $relative, $m)) {
            throw new RuntimeException('unsafe_path');
        }
        $roots = $this->roots();
        $base = realpath($roots[$m[1]]);
        if ($base === false || !is_dir($base)) {
            throw new RuntimeException('unsafe_path');
        }
        $suffix = substr($relative, strlen($m[1]) + 1);
        $path = $roots[$m[1]] . str_replace('/', DIRECTORY_SEPARATOR, $suffix);
        if (is_link($path)) {
            throw new RuntimeException('unsafe_path');
        }
        $parent = realpath(dirname($path));
        if ($parent === false || ($parent !== $base && strpos($parent, $base . DIRECTORY_SEPARATOR) !== 0)) {
            throw new RuntimeException('unsafe_path');
        }
        if ($forExisting && (!is_file($path) || !is_readable($path))) {
            throw new RuntimeException('file_changed');
        }
        if (file_exists($path) && (!is_file($path) || is_link($path) || strpos(realpath($path), $base . DIRECTORY_SEPARATOR) !== 0)) {
            throw new RuntimeException('unsafe_path');
        }
        return $path;
    }

    private function paymentPaths($code) {
        if (!$this->validCode($code)) {
            throw new RuntimeException('invalid_selection');
        }
        $paths = array();
        foreach (array('admin', 'catalog') as $side) {
            foreach (array('controller', 'model') as $kind) {
                $paths[] = $side . '/' . $kind . '/extension/payment/' . $code . '.php';
            }
            $dir = $this->roots()[$side] . 'language/';
            foreach (glob($dir . '*/extension/payment/' . $code . '.php') ?: array() as $file) {
                $language = basename(dirname(dirname(dirname($file))));
                if (preg_match('/^[a-z0-9_-]{2,32}$/iD', $language)) {
                    $paths[] = $side . '/language/' . $language . '/extension/payment/' . $code . '.php';
                }
            }
        }
        foreach (array('.twig', '.tpl') as $extension) {
            $paths[] = 'admin/view/template/extension/payment/' . $code . $extension;
            $paths[] = 'catalog/view/theme/default/template/extension/payment/' . $code . $extension;
        }
        return array_values(array_unique($paths));
    }

    private function allowedPaymentPath($relative, $code) {
        if (!$this->validCode($code)) {
            return false;
        }
        $q = preg_quote($code, '~');
        return preg_match('~^(?:admin|catalog)/(?:controller|model)/extension/payment/' . $q . '\.php$~D', $relative)
            || preg_match('~^(?:admin|catalog)/language/[a-z0-9_-]{2,32}/extension/payment/' . $q . '\.php$~iD', $relative)
            || preg_match('~^admin/view/template/extension/payment/' . $q . '\.(?:twig|tpl)$~D', $relative)
            || preg_match('~^catalog/view/theme/default/template/extension/payment/' . $q . '\.(?:twig|tpl)$~D', $relative);
    }

    private function existingPaymentPaths($code) {
        $out = array();
        foreach ($this->paymentPaths($code) as $relative) {
            if (!$this->allowedPaymentPath($relative, $code)) {
                continue;
            }
            $side = substr($relative, 0, strpos($relative, '/'));
            $absolute = $this->roots()[$side] . substr($relative, strlen($side) + 1);
            if (!is_file($absolute)) {
                continue;
            }
            $path = $this->safePath($relative, true);
            if (is_file($path)) {
                $out[] = array('path' => $relative, 'bytes' => filesize($path));
            }
        }
        return $out;
    }

    private function evidence() {
        $installed = array();
        foreach ($this->db->query("SELECT `type`, `code` FROM `" . DB_PREFIX . "extension`")->rows as $row) {
            $installed[$row['type'] . '/' . $row['code']] = true;
        }
        $settings = array();
        foreach ($this->db->query("SELECT `code`, `key`, `value` FROM `" . DB_PREFIX . "setting` WHERE `code` LIKE 'payment\\_%' OR `key` LIKE 'payment\\_%'")->rows as $row) {
            foreach (array($row['code'], $row['key']) as $value) {
                if (strpos($value, 'payment_') === 0) {
                    // An exact namespace is checked separately, so even a custom key is only a warning.
                    $settings[] = $value;
                }
            }
            if (preg_match('/^payment_[a-z0-9_]+_status$/iD', $row['key']) && (string)$row['value'] !== '0') {
                $settings[] = $row['key'];
            }
        }
        $history = array();
        foreach ($this->db->query("SELECT `payment_code`, COUNT(*) AS `total` FROM `" . DB_PREFIX . "order` WHERE `payment_code` <> '' GROUP BY `payment_code`")->rows as $row) {
            $history[$row['payment_code']] = (int)$row['total'];
        }
        $references = array();
        foreach ($this->db->query("SELECT `trigger`, `action` FROM `" . DB_PREFIX . "event`")->rows as $row) {
            $references[] = $row['trigger'] . "\n" . $row['action'];
        }
        $size = $this->db->query("SELECT COALESCE(SUM(OCTET_LENGTH(`xml`)), 0) AS `total` FROM `" . DB_PREFIX . "modification`");
        $incomplete = (float)$size->row['total'] > 16777216;
        if (!$incomplete) {
            foreach ($this->db->query("SELECT `xml` FROM `" . DB_PREFIX . "modification`")->rows as $row) {
                $references[] = $row['xml'];
            }
        }
        $packagePaths = array();
        $manifestRows = $this->db->query("SELECT `path` FROM `" . DB_PREFIX . "extension_path` WHERE `path` LIKE '%/payment/%' LIMIT 10001")->rows;
        foreach ($manifestRows as $row) {
            $packagePaths[str_replace('\\', '/', ltrim($row['path'], '/'))] = true;
        }
        if (count($manifestRows) > 10000) {
            $incomplete = true;
        }
        return array('installed' => $installed, 'settings' => $settings, 'history' => $history,
                     'references' => $references, 'packages' => $packagePaths, 'incomplete' => $incomplete);
    }

    private function statusPayment($code, $evidence, $files) {
        $reasons = array();
        if (isset($evidence['installed']['payment/' . $code])) {
            $reasons[] = 'installed';
        }
        if ($evidence['incomplete']) {
            $reasons[] = 'incomplete';
        }
        if (!empty($evidence['history'][$code])) {
            $reasons[] = 'orders';
        }
        $prefix = 'payment_' . $code;
        foreach ($evidence['settings'] as $setting) {
            if ($setting === $prefix || strpos($setting, $prefix . '_') === 0) {
                $reasons[] = 'settings';
                break;
            }
        }
        foreach ($evidence['references'] as $reference) {
            if (strpos($reference, 'extension/payment/' . $code) !== false || strpos($reference, $prefix . '_') !== false) {
                $reasons[] = 'references';
                break;
            }
        }
        foreach ($files as $file) {
            $path = $file['path'];
            $withoutRoot = preg_replace('~^(admin|catalog)/~', '', $path);
            if (isset($evidence['packages'][$path]) || isset($evidence['packages']['upload/' . $path]) || isset($evidence['packages'][$withoutRoot])) {
                $reasons[] = 'package';
                break;
            }
        }
        $roots = $this->roots();
        foreach (glob($roots['catalog'] . 'view/theme/*/template/extension/payment/' . $code . '.*') ?: array() as $path) {
            if (strpos(str_replace('\\', '/', $path), '/theme/default/') === false) {
                $reasons[] = 'theme';
                break;
            }
        }
        if (!$files) {
            $reasons[] = 'no_files';
        }
        if (count($files) > $this->maxFiles || array_sum(array_column($files, 'bytes')) > $this->maxBytes) {
            $reasons[] = 'size';
        }
        return array('status' => $reasons ? 'review' : 'candidate', 'reasons' => array_values(array_unique($reasons)));
    }

    public function scanPayments() {
        $evidence = $this->evidence();
        $roots = $this->roots();
        $codes = array();
        foreach (array('admin', 'catalog') as $side) {
            foreach (array($roots[$side] . 'controller/extension/payment/*.php', $roots[$side] . 'model/extension/payment/*.php') as $pattern) {
                foreach (glob($pattern) ?: array() as $file) {
                    $code = basename($file, '.php');
                    if ($this->validCode($code)) {
                        $codes[$code] = true;
                    }
                }
            }
        }
        foreach (array('admin', 'catalog') as $side) {
            foreach (glob($roots[$side] . 'language/*/extension/payment/*.php') ?: array() as $file) {
                $code = basename($file, '.php');
                if ($this->validCode($code)) {
                    $codes[$code] = true;
                }
            }
        }
        foreach (array('admin/view/template/extension/payment/*.{twig,tpl}', 'catalog/view/theme/default/template/extension/payment/*.{twig,tpl}') as $pattern) {
            $parts = explode('/', $pattern, 2);
            foreach (glob($roots[$parts[0]] . $parts[1], GLOB_BRACE) ?: array() as $file) {
                $code = pathinfo($file, PATHINFO_FILENAME);
                if ($this->validCode($code)) {
                    $codes[$code] = true;
                }
            }
        }
        foreach ($evidence['installed'] as $key => $unused) {
            if (strpos($key, 'payment/') === 0) {
                $codes[substr($key, 8)] = true;
            }
        }
        ksort($codes);
        $result = array();
        foreach (array_keys($codes) as $code) {
            if (!$this->validCode($code)) {
                continue;
            }
            $files = $this->existingPaymentPaths($code);
            $state = $this->statusPayment($code, $evidence, $files);
            $result[] = array('code' => $code, 'installed' => isset($evidence['installed']['payment/' . $code]),
                'orders' => isset($evidence['history'][$code]) ? $evidence['history'][$code] : 0,
                'files' => $files, 'size' => array_sum(array_column($files, 'bytes')),
                'status' => $state['status'], 'reasons' => $state['reasons']);
        }
        return $result;
    }

    public function scanExtensionControllers() {
        $installed = array();
        foreach ($this->db->query("SELECT `type`, `code` FROM `" . DB_PREFIX . "extension`")->rows as $row) {
            $installed[$row['type'] . '/' . $row['code']] = true;
        }
        $result = array();
        foreach (array('admin' => DIR_APPLICATION, 'catalog' => DIR_CATALOG) as $side => $root) {
            foreach ($this->types as $type) {
                foreach (glob(rtrim($root, '/\\') . '/controller/extension/' . $type . '/*.php') ?: array() as $file) {
                    $code = basename($file, '.php');
                    if (!$this->validCode($code)) {
                        continue;
                    }
                    $result[] = array('path' => $side . '/controller/extension/' . $type . '/' . $code . '.php',
                        'code' => $type . '/' . $code, 'status' => isset($installed[$type . '/' . $code]) ? 'installed' : 'review');
                }
            }
        }
        return $result;
    }

    public function scanIntegrations() {
        $items = array();
        foreach ($this->db->query("SELECT `modification_id`, `code`, `status` FROM `" . DB_PREFIX . "modification` ORDER BY `modification_id` DESC LIMIT 250")->rows as $row) {
            $items[] = array('kind' => 'OCMOD', 'id' => (int)$row['modification_id'],
                'code' => $row['code'], 'status' => (int)$row['status']);
        }
        foreach ($this->db->query("SELECT `event_id`, `code`, `status` FROM `" . DB_PREFIX . "event` ORDER BY `event_id` DESC LIMIT 250")->rows as $row) {
            $items[] = array('kind' => 'Event', 'id' => (int)$row['event_id'],
                'code' => $row['code'], 'status' => (int)$row['status']);
        }
        return $items;
    }

    public function quarantinePayment($code, $userId) {
        if (!$this->validCode($code)) {
            throw new RuntimeException('invalid_selection');
        }
        $evidence = $this->evidence();
        $files = $this->existingPaymentPaths($code);
        $state = $this->statusPayment($code, $evidence, $files);
        if ($state['status'] !== 'candidate') {
            throw new RuntimeException('not_safe');
        }
        $data = array();
        $total = 0;
        foreach ($files as $file) {
            $relative = $file['path'];
            if (!$this->allowedPaymentPath($relative, $code)) {
                throw new RuntimeException('unsafe_path');
            }
            $path = $this->safePath($relative, true);
            $bytes = file_get_contents($path);
            if ($bytes === false || strlen($bytes) > $this->maxBytes) {
                throw new RuntimeException('file_changed');
            }
            $total += strlen($bytes);
            if ($total > $this->maxBytes) {
                throw new RuntimeException('size');
            }
            $data[] = array('relative' => $relative, 'bytes' => $bytes, 'sha' => hash('sha256', $bytes));
        }
        if (!$data) {
            throw new RuntimeException('not_safe');
        }
        // Back up all bytes and commit first. Filesystem operations are not part of a DB transaction.
        $this->db->query('START TRANSACTION');
        try {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "settings_cleaner_file_backup` SET `code` = '" . $this->db->escape($code) . "', `user_id` = " . (int)$userId . ", `date_added` = NOW(), `file_count` = " . count($data));
            $backupId = (int)$this->db->getLastId();
            foreach ($data as $file) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "settings_cleaner_file_backup_row` SET `file_backup_id` = " . $backupId
                    . ", `relative_path` = '" . $this->db->escape($file['relative'])
                    . "', `sha256` = '" . $file['sha'] . "', `content_base64` = '" . base64_encode($file['bytes']) . "'");
            }
            $this->db->query('COMMIT');
        } catch (Throwable $error) {
            $this->db->query('ROLLBACK');
            throw $error;
        }
        // Installation or configuration might have changed while the backup was being persisted.
        if ($this->statusPayment($code, $this->evidence(), $this->existingPaymentPaths($code))['status'] !== 'candidate') {
            throw new RuntimeException('not_safe');
        }
        $removed = 0;
        foreach ($data as $file) {
            $path = $this->safePath($file['relative'], true);
            if (!hash_equals($file['sha'], hash_file('sha256', $path))) {
                throw new RuntimeException('file_changed');
            }
            if (!unlink($path)) {
                throw new RuntimeException('file_changed');
            }
            $this->db->query("UPDATE `" . DB_PREFIX . "settings_cleaner_file_backup_row` SET `removed` = 1 WHERE `file_backup_id` = " . $backupId . " AND `relative_path` = '" . $this->db->escape($file['relative']) . "'");
            $removed++;
        }
        return array('backup_id' => $backupId, 'removed' => $removed);
    }

    public function getFileBackups() {
        return $this->db->query("SELECT b.`file_backup_id`, b.`code`, b.`date_added`, b.`file_count`, b.`user_id`, COALESCE(SUM(r.`restored`), 0) AS `restored_count` FROM `" . DB_PREFIX . "settings_cleaner_file_backup` b LEFT JOIN `" . DB_PREFIX . "settings_cleaner_file_backup_row` r ON r.`file_backup_id` = b.`file_backup_id` GROUP BY b.`file_backup_id`, b.`code`, b.`date_added`, b.`file_count`, b.`user_id` ORDER BY b.`file_backup_id` DESC LIMIT 30")->rows;
    }

    public function restoreFileBackup($backupId) {
        $backupId = (int)$backupId;
        if ($backupId < 1) {
            throw new RuntimeException('invalid_selection');
        }
        $metadata = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "settings_cleaner_file_backup` WHERE `file_backup_id` = " . $backupId . " LIMIT 1");
        if (!$metadata->num_rows || !$this->validCode($metadata->row['code'])) {
            throw new RuntimeException('missing_backup');
        }
        $code = $metadata->row['code'];
        $rows = $this->db->query("SELECT * FROM `" . DB_PREFIX . "settings_cleaner_file_backup_row` WHERE `file_backup_id` = " . $backupId . " ORDER BY `file_backup_row_id`")->rows;
        if (!$rows) {
            throw new RuntimeException('missing_backup');
        }
        $pending = array();
        foreach ($rows as $row) {
            if ((int)$row['restored']) {
                continue;
            }
            if (!$this->allowedPaymentPath($row['relative_path'], $code)) {
                throw new RuntimeException('unsafe_path');
            }
            $path = $this->safePath($row['relative_path'], false);
            if (file_exists($path) || is_link($path)) {
                // A failed/aborted deletion may have left the original file intact.
                if (!(int)$row['removed'] && is_file($path) && !is_link($path) && hash_equals($row['sha256'], hash_file('sha256', $path))) {
                    $pending[] = array('row' => $row, 'path' => null, 'bytes' => '');
                    continue;
                }
                throw new RuntimeException('restore_conflict');
            }
            $bytes = base64_decode($row['content_base64'], true);
            if ($bytes === false || !hash_equals($row['sha256'], hash('sha256', $bytes))) {
                throw new RuntimeException('file_changed');
            }
            $pending[] = array('row' => $row, 'path' => $path, 'bytes' => $bytes);
        }
        if (!$pending) {
            throw new RuntimeException('already_restored');
        }
        $restored = 0;
        foreach ($pending as $item) {
            if ($item['path'] === null) {
                $this->db->query("UPDATE `" . DB_PREFIX . "settings_cleaner_file_backup_row` SET `restored` = 1 WHERE `file_backup_row_id` = " . (int)$item['row']['file_backup_row_id']);
                $restored++;
                continue;
            }
            $tmp = tempnam(dirname($item['path']), '.sc_restore_');
            if ($tmp === false) {
                throw new RuntimeException('file_changed');
            }
            try {
                if (file_put_contents($tmp, $item['bytes'], LOCK_EX) !== strlen($item['bytes']) || !hash_equals(hash('sha256', $item['bytes']), hash_file('sha256', $tmp))) {
                    throw new RuntimeException('file_changed');
                }
                if (file_exists($item['path']) || is_link($item['path']) || !rename($tmp, $item['path'])) {
                    throw new RuntimeException('restore_conflict');
                }
                $this->db->query("UPDATE `" . DB_PREFIX . "settings_cleaner_file_backup_row` SET `restored` = 1 WHERE `file_backup_row_id` = " . (int)$item['row']['file_backup_row_id']);
                $restored++;
            } finally {
                if (is_file($tmp)) {
                    unlink($tmp);
                }
            }
        }
        return $restored;
    }
}
