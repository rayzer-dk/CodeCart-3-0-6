<?php
class ModelUpgrade3052 extends Model {
    public function upgrade() {
        if (!defined('DB_PREFIX') || !preg_match('/^[A-Za-z0-9_]*$/', (string)DB_PREFIX)) {
            throw new \RuntimeException('Upgrade database prefix is invalid.');
        }

        $required = array('setting', 'user', 'product', 'order', 'seo_url', 'event');
        foreach ($required as $table) {
            $query = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . $table) . "'");
            if (!$query->num_rows) {
                throw new \RuntimeException('Required OpenCart 3.x table is missing: ' . DB_PREFIX . $table);
            }
        }

        // Commerce operations rely on real transactions. Legacy ocStore/OpenCart
        // databases can still have MyISAM/Aria tables, where rollback cannot protect
        // stock and order state. Conversion is performed only inside the explicit
        // Installer 2.0 upgrade flow, after the administrator confirmed a backup.
        $this->ensureTransactionalCommerceTables();

        $storeConfig = new Config();
        $settings = $this->db->query("SELECT `key`, `value`, `serialized` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0'");
        foreach ($settings->rows as $setting) {
            $value = $setting['value'];
            if (!empty($setting['serialized'])) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded;
                } else {
                    $legacy = @unserialize($value, array('allowed_classes' => false));
                    if ($legacy !== false || $value === 'b:0;') {
                        $value = $legacy;
                    }
                }
            }
            $storeConfig->set($setting['key'], $value);
        }

        $migrationRegistry = new Registry();
        $migrationRegistry->set('db', $this->db);
        $migrationRegistry->set('config', $storeConfig);
        $migrationRegistry->set('log', $this->registry->get('log'));

        require_once(DIR_SYSTEM . 'library/codecart/migration.php');
        $migration = new \CodeCart\Migration($migrationRegistry);
        $migration->run();

        $expected = defined('CODECART_BUILD') ? (string)CODECART_BUILD : \CodeCart\Migration::VERSION;
        $result = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "setting` WHERE `store_id` = '0' AND `key` = 'codecart_core_schema_version' LIMIT 1");
        $actual = $result->num_rows ? (string)$result->row['value'] : '';

        if ($actual !== $expected) {
            throw new \RuntimeException('CodeCart PRO core migration did not reach the expected schema version. Check the protected error log.');
        }
    }

    private function ensureTransactionalCommerceTables() {
        $tables = array(
            // Settings save now uses a real transaction; legacy OpenCart/ocStore
            // databases must not leave this table on MyISAM/Aria.
            'setting',
            'product',
            'product_option_value',
            'order',
            'order_product',
            'order_option',
            'order_total',
            'order_history',
            'customer',
            'customer_reward',
            'coupon_history',
            'voucher_history'
        );

        foreach ($tables as $name) {
            $table = DB_PREFIX . $name;
            $exists = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'");
            if (!$exists->num_rows) {
                continue;
            }

            $meta = $this->db->query("SELECT ENGINE, DATA_LENGTH, INDEX_LENGTH FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $this->db->escape($table) . "' LIMIT 1");
            if (!$meta->num_rows) {
                throw new \RuntimeException('Unable to inspect storage engine for ' . $table . '.');
            }

            $engine = strtoupper((string)$meta->row['ENGINE']);
            if ($engine === 'INNODB') {
                continue;
            }

            if (!in_array($engine, array('MYISAM', 'ARIA'), true)) {
                throw new \RuntimeException('Unsafe storage engine for transactional commerce table ' . $table . ': ' . $engine . '. Convert it to InnoDB before upgrading.');
            }

            $bytes = (int)$meta->row['DATA_LENGTH'] + (int)$meta->row['INDEX_LENGTH'];
            // Large ALTER TABLE operations can lock a production store for a long time.
            // Do not hide that risk inside the web updater: require the explicit CLI
            // modernization command where --large is an intentional administrator action.
            if ($bytes >= 268435456) {
                throw new \RuntimeException('Transactional table ' . $table . ' is 256 MB or larger and still uses ' . $engine . '. Run php cli.php db:preflight and php cli.php db:migrate --backup-confirmed --large before continuing the upgrade.');
            }

            $restoreSqlMode = null;

            // OpenCart/ocStore 3.0.5.x ships product.date_available with
            // DEFAULT '0000-00-00'. MySQL 8.x strict mode rejects that legacy
            // default while rebuilding the MyISAM table as InnoDB (error 1067).
            // Preserve merchant row values, modernize only the default, perform
            // the engine conversion in the same relaxed session, then restore
            // the exact previous sql_mode.
            if ($name === 'product') {
                $dateColumn = $this->db->query("SHOW COLUMNS FROM `" . str_replace('`', '``', $table) . "` LIKE 'date_available'");

                if ($dateColumn->num_rows && (string)$dateColumn->row['Default'] === '0000-00-00') {
                    $modeQuery = $this->db->query("SELECT @@SESSION.sql_mode AS sql_mode");

                    if ($modeQuery->num_rows) {
                        $restoreSqlMode = (string)$modeQuery->row['sql_mode'];
                        $parts = array_filter(array_map('trim', explode(',', $restoreSqlMode)), function($part) {
                            return !in_array(strtoupper($part), array('STRICT_TRANS_TABLES','STRICT_ALL_TABLES','NO_ZERO_DATE','NO_ZERO_IN_DATE'), true);
                        });
                        $this->db->query("SET SESSION sql_mode='" . $this->db->escape(implode(',', $parts)) . "'");
                    }

                    $this->db->query("ALTER TABLE `" . str_replace('`', '``', $table) . "` MODIFY `date_available` DATE NOT NULL DEFAULT '1970-01-01'");
                }
            }

            try {
                $this->db->query("ALTER TABLE `" . str_replace('`', '``', $table) . "` ENGINE=InnoDB");
            } finally {
                if ($restoreSqlMode !== null) {
                    $this->db->query("SET SESSION sql_mode='" . $this->db->escape($restoreSqlMode) . "'");
                }
            }

            $verify = $this->db->query("SELECT ENGINE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $this->db->escape($table) . "' LIMIT 1");
            if (!$verify->num_rows || strtoupper((string)$verify->row['ENGINE']) !== 'INNODB') {
                throw new \RuntimeException('Failed to convert transactional commerce table to InnoDB: ' . $table . '.');
            }
        }
    }
}
