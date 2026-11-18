<?php
class ModelExtensionModuleSettingsCleaner extends Model {
    private $types = array('advertise', 'analytics', 'captcha', 'currency', 'dashboard', 'feed', 'fraud', 'menu', 'module', 'payment', 'report', 'shipping', 'theme', 'total');

    public function install() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "settings_cleaner_backup` (
            `backup_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `date_added` DATETIME NOT NULL,
            `row_count` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`backup_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "settings_cleaner_backup_row` (
            `backup_row_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `backup_id` INT UNSIGNED NOT NULL,
            `original_setting_id` INT NOT NULL,
            `store_id` INT NOT NULL,
            `code` VARCHAR(128) NOT NULL,
            `key` VARCHAR(128) NOT NULL,
            `value` MEDIUMTEXT NOT NULL,
            `serialized` TINYINT(1) NOT NULL DEFAULT 0,
            `restored` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`backup_row_id`),
            KEY `backup_id` (`backup_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "settings_cleaner_backup_row`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "settings_cleaner_backup`");
    }

    private function evidence() {
        $installed = array();
        $query = $this->db->query("SELECT `type`, `code` FROM `" . DB_PREFIX . "extension`");
        foreach ($query->rows as $row) {
            $installed[$row['type'] . '/' . $row['code']] = true;
        }

        $instances = array();
        $query = $this->db->query("SELECT DISTINCT `code` FROM `" . DB_PREFIX . "module`");
        foreach ($query->rows as $row) {
            $instances[$row['code']] = true;
        }

        $layouts = array();
        $query = $this->db->query("SELECT DISTINCT `code` FROM `" . DB_PREFIX . "layout_module`");
        foreach ($query->rows as $row) {
            $name = explode('.', $row['code'], 2);
            $layouts[$name[0]] = true;
        }

        $references = array();
        $incomplete = false;
        $query = $this->db->query("SELECT `trigger`, `action` FROM `" . DB_PREFIX . "event` WHERE `status` = 1");
        foreach ($query->rows as $row) {
            $references[] = (string)$row['trigger'] . "\n" . (string)$row['action'];
        }

        // Restrict XML inspection to a bounded amount; missing evidence must never authorize deletion.
        $size = $this->db->query("SELECT COALESCE(SUM(OCTET_LENGTH(`xml`)), 0) AS `total` FROM `" . DB_PREFIX . "modification` WHERE `status` = 1");
        if ((float)$size->row['total'] > 16777216) {
            $incomplete = true;
        } else {
            $query = $this->db->query("SELECT `xml` FROM `" . DB_PREFIX . "modification` WHERE `status` = 1");
            foreach ($query->rows as $row) {
                $references[] = (string)$row['xml'];
            }
        }
        return array('installed' => $installed, 'instances' => $instances, 'layouts' => $layouts, 'references' => $references, 'incomplete' => $incomplete);
    }

    private function extensionInfo($code) {
        foreach ($this->types as $type) {
            $prefix = $type . '_';
            if (strpos($code, $prefix) === 0) {
                $name = substr($code, strlen($prefix));
                if ($name !== '' && preg_match('/^[a-z0-9_]+$/iD', $name)) {
                    return array($type, $name);
                }
                break;
            }
        }
        return null;
    }

    private function controllerExists($type, $name) {
        $paths = array(
            DIR_APPLICATION . 'controller/extension/' . $type . '/' . $name . '.php',
            DIR_CATALOG . 'controller/extension/' . $type . '/' . $name . '.php'
        );
        if ($type === 'module') {
            $paths[] = DIR_APPLICATION . 'controller/module/' . $name . '.php';
            $paths[] = DIR_CATALOG . 'controller/module/' . $name . '.php';
        }
        if (defined('DIR_MODIFICATION')) {
            $paths[] = DIR_MODIFICATION . 'admin/controller/extension/' . $type . '/' . $name . '.php';
            $paths[] = DIR_MODIFICATION . 'catalog/controller/extension/' . $type . '/' . $name . '.php';
        }
        foreach ($paths as $path) {
            if (is_file($path)) {
                return true;
            }
        }
        return false;
    }

    private function hasParentExtension($type, $name, $evidence) {
        $segments = explode('_', $name);
        array_pop($segments);
        while ($segments) {
            $parent = implode('_', $segments);
            if (isset($evidence['installed'][$type . '/' . $parent]) || $this->controllerExists($type, $parent)) {
                return true;
            }
            array_pop($segments);
        }
        return false;
    }

    private function classify($row, $evidence, &$cache) {
        $code = (string)$row['code'];
        if (!isset($cache[$code])) {
            $info = $this->extensionInfo($code);
            if ($code === 'config' || strpos($code, 'config_') === 0) {
                $cache[$code] = array('status' => 'protected', 'reason' => 'system');
            } elseif ($info === null) {
                $cache[$code] = array('status' => 'unknown', 'reason' => 'custom');
            } else {
                list($type, $name) = $info;
                $path = $type . '/' . $name;
                if (isset($evidence['installed'][$path])) {
                    $cache[$code] = array('status' => 'protected', 'reason' => 'installed');
                } elseif ($this->controllerExists($type, $name)) {
                    $cache[$code] = array('status' => 'protected', 'reason' => 'file');
                } elseif ($this->hasParentExtension($type, $name, $evidence)) {
                    $cache[$code] = array('status' => 'review', 'reason' => 'parent_extension');
                } elseif ($type === 'module' && (isset($evidence['instances'][$name]) || isset($evidence['layouts'][$name]))) {
                    $cache[$code] = array('status' => 'review', 'reason' => 'module_reference');
                } elseif ($evidence['incomplete']) {
                    $cache[$code] = array('status' => 'review', 'reason' => 'incomplete');
                } else {
                    $cache[$code] = array('status' => 'candidate', 'reason' => 'missing');
                }
            }
        }
        $result = $cache[$code];
        if ($result['status'] === 'candidate') {
            $info = $this->extensionInfo($code);
            $pattern = 'extension/' . $info[0] . '/' . $info[1];
            foreach ($evidence['references'] as $reference) {
                if (strpos($reference, $pattern) !== false || strpos($reference, $code) !== false || strpos($reference, (string)$row['key']) !== false) {
                    return array('status' => 'review', 'reason' => 'modification_reference');
                }
            }
        }
        return $result;
    }

    public function scan() {
        $evidence = $this->evidence();
        $cache = array();
        $rows = array();
        $lastId = 0;
        $limit = 20000;
        $counts = array('candidate' => 0, 'protected' => 0, 'review' => 0, 'unknown' => 0);
        while (count($rows) < $limit) {
            $query = $this->db->query("SELECT `setting_id`, `store_id`, `code`, `key` FROM `" . DB_PREFIX . "setting` WHERE `setting_id` > " . (int)$lastId . " ORDER BY `setting_id` ASC LIMIT 1000");
            if (!$query->rows) {
                break;
            }
            foreach ($query->rows as $row) {
                $classification = $this->classify($row, $evidence, $cache);
                $row['status'] = $classification['status'];
                $row['reason'] = $classification['reason'];
                $rows[] = $row;
                $counts[$classification['status']]++;
                $lastId = (int)$row['setting_id'];
            }
            if (count($query->rows) < 1000) {
                break;
            }
        }
        $truncated = count($rows) >= $limit;
        return array('rows' => $rows, 'counts' => $counts, 'truncated' => $truncated, 'incomplete' => $evidence['incomplete']);
    }

    public function deleteCandidates($ids, $userId) {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array)$ids), function($id) { return $id > 0; })));
        if (!$ids || count($ids) > 200) {
            throw new RuntimeException('invalid_selection');
        }
        $total = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "setting`");
        if ((int)$total->row['total'] > 20000) {
            throw new RuntimeException('scan_limit');
        }
        $evidence = $this->evidence();
        $cache = array();
        $this->db->query('START TRANSACTION');
        try {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE `setting_id` IN (" . implode(',', $ids) . ") FOR UPDATE");
            if (count($query->rows) !== count($ids)) {
                throw new RuntimeException('selection_changed');
            }
            foreach ($query->rows as $row) {
                if ($this->classify($row, $evidence, $cache)['status'] !== 'candidate') {
                    throw new RuntimeException('selection_changed');
                }
            }
            $this->db->query("INSERT INTO `" . DB_PREFIX . "settings_cleaner_backup` SET `user_id` = " . (int)$userId . ", `date_added` = NOW(), `row_count` = " . count($ids));
            $backupId = (int)$this->db->getLastId();
            foreach ($query->rows as $row) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "settings_cleaner_backup_row` SET "
                    . "`backup_id` = " . $backupId . ", "
                    . "`original_setting_id` = " . (int)$row['setting_id'] . ", "
                    . "`store_id` = " . (int)$row['store_id'] . ", "
                    . "`code` = '" . $this->db->escape($row['code']) . "', "
                    . "`key` = '" . $this->db->escape($row['key']) . "', "
                    . "`value` = '" . $this->db->escape($row['value']) . "', "
                    . "`serialized` = " . (int)$row['serialized']);
            }
            $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `setting_id` IN (" . implode(',', $ids) . ")");
            if ($this->db->countAffected() !== count($ids)) {
                throw new RuntimeException('selection_changed');
            }
            $this->db->query('COMMIT');
            return $backupId;
        } catch (Throwable $exception) {
            $this->db->query('ROLLBACK');
            throw $exception;
        }
    }

    public function getBackups() {
        return $this->db->query("SELECT b.`backup_id`, b.`user_id`, b.`date_added`, b.`row_count`, COALESCE(SUM(r.`restored`), 0) AS `restored_count` FROM `" . DB_PREFIX . "settings_cleaner_backup` b LEFT JOIN `" . DB_PREFIX . "settings_cleaner_backup_row` r ON r.`backup_id` = b.`backup_id` GROUP BY b.`backup_id`, b.`user_id`, b.`date_added`, b.`row_count` ORDER BY b.`backup_id` DESC LIMIT 30")->rows;
    }

    public function restoreBackup($backupId) {
        $backupId = (int)$backupId;
        if ($backupId < 1) {
            throw new RuntimeException('invalid_selection');
        }
        $this->db->query('START TRANSACTION');
        try {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "settings_cleaner_backup_row` WHERE `backup_id` = " . $backupId . " ORDER BY `backup_row_id` ASC FOR UPDATE");
            if (!$query->rows) {
                throw new RuntimeException('missing_backup');
            }
            $pending = array();
            $keys = array();
            foreach ($query->rows as $row) {
                if ((int)$row['restored']) {
                    continue;
                }
                $identity = (int)$row['store_id'] . "\0" . $row['code'] . "\0" . $row['key'];
                if (!isset($keys[$identity])) {
                    $exists = $this->db->query("SELECT `setting_id` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = " . (int)$row['store_id'] . " AND `code` = '" . $this->db->escape($row['code']) . "' AND `key` = '" . $this->db->escape($row['key']) . "' LIMIT 1");
                    if ($exists->num_rows) {
                        throw new RuntimeException('restore_conflict');
                    }
                    $keys[$identity] = true;
                }
                $pending[] = $row;
            }
            if (!$pending) {
                throw new RuntimeException('already_restored');
            }
            foreach ($pending as $row) {
                $this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET "
                    . "`store_id` = " . (int)$row['store_id'] . ", "
                    . "`code` = '" . $this->db->escape($row['code']) . "', "
                    . "`key` = '" . $this->db->escape($row['key']) . "', "
                    . "`value` = '" . $this->db->escape($row['value']) . "', "
                    . "`serialized` = " . (int)$row['serialized']);
                $this->db->query("UPDATE `" . DB_PREFIX . "settings_cleaner_backup_row` SET `restored` = 1 WHERE `backup_row_id` = " . (int)$row['backup_row_id']);
            }
            $this->db->query('COMMIT');
            return count($pending);
        } catch (Throwable $exception) {
            $this->db->query('ROLLBACK');
            throw $exception;
        }
    }
}
