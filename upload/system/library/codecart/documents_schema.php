<?php
/** Core file/document statistics: independent from OpenCart paid entitlements. */
final class CodeCartDocumentsSchema {
    public static function install($db) {
        if (!defined('DB_PREFIX') || !preg_match('/^[A-Za-z0-9_]*$/', (string)DB_PREFIX)) {
            throw new RuntimeException('Invalid database prefix');
        }
        $tables = array(
            'codecart_public_document' => "(
              document_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              kind VARCHAR(24) NOT NULL DEFAULT 'manual',
              target_type VARCHAR(16) NOT NULL DEFAULT 'none',
              target_id INT UNSIGNED NOT NULL DEFAULT 0,
              filename VARCHAR(128) NOT NULL,
              original_name VARCHAR(255) NOT NULL,
              filesize BIGINT UNSIGNED NOT NULL DEFAULT 0,
              mime VARCHAR(120) NOT NULL DEFAULT 'application/octet-stream',
              status TINYINT(1) NOT NULL DEFAULT 0,
              sort_order INT NOT NULL DEFAULT 0,
              download_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
              last_download DATETIME NULL,
              date_added DATETIME NOT NULL,
              date_modified DATETIME NOT NULL,
              PRIMARY KEY (document_id), KEY target_lookup (status, target_type, target_id, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'codecart_public_document_description' => "(
              document_id INT UNSIGNED NOT NULL,
              language_id INT NOT NULL,
              title VARCHAR(255) NOT NULL,
              PRIMARY KEY (document_id,language_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'codecart_document_to_product' => "(
              document_id INT UNSIGNED NOT NULL,
              product_id INT UNSIGNED NOT NULL,
              sort_order INT NOT NULL DEFAULT 0,
              PRIMARY KEY (document_id, product_id),
              KEY product_lookup (product_id, sort_order, document_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'codecart_public_document_daily' => "(
              document_id INT UNSIGNED NOT NULL, day DATE NOT NULL,
              download_count INT UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (document_id,day), KEY day_lookup (day)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'codecart_download_stats' => "(
              download_id INT UNSIGNED NOT NULL,
              download_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
              last_download DATETIME NULL,
              PRIMARY KEY (download_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            'codecart_download_daily' => "(
              download_id INT UNSIGNED NOT NULL, day DATE NOT NULL,
              download_count INT UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (download_id,day), KEY day_lookup (day)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        foreach ($tables as $name => $definition) {
            $db->query('CREATE TABLE IF NOT EXISTS `' . DB_PREFIX . $name . '` ' . $definition);
        }
        // Upgrade old single-product links without losing files, translations or download counts.
        // INSERT IGNORE is idempotent. Existing category bindings are preserved unchanged.
        $db->query("INSERT IGNORE INTO `" . DB_PREFIX . "codecart_document_to_product` (document_id,product_id,sort_order) SELECT d.document_id,d.target_id,d.sort_order FROM `" . DB_PREFIX . "codecart_public_document` d INNER JOIN `" . DB_PREFIX . "product` p ON p.product_id=d.target_id WHERE d.target_type='product' AND d.target_id>0");
        $db->query("UPDATE `" . DB_PREFIX . "codecart_public_document` SET target_type='none',target_id=0 WHERE target_type='product' AND target_id>0 AND document_id IN (SELECT document_id FROM `" . DB_PREFIX . "codecart_document_to_product`)");
        // Mark the new many-to-many layout as ready only after a successful migration.
        $db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id=0 AND `key`='codecart_public_documents_schema_version'");
        $db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id=0,code='codecart_core',`key`='codecart_public_documents_schema_version',value='2.0.8',serialized=0");
        // Presence flag prevents unexpected queries on stores without the feature.
        $key = 'codecart_file_stats_status';
        $db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id=0 AND `key`='" . $key . "'");
        $db->query("INSERT INTO `" . DB_PREFIX . "setting` SET store_id=0, code='codecart_core', `key`='" . $key . "', value='1', serialized=0");
        $existing=$db->query("SELECT `key` FROM `".DB_PREFIX."setting` WHERE store_id=0 AND `key`='codecart_public_documents_status' LIMIT 1");
        if (!$existing->num_rows) $db->query("INSERT INTO `".DB_PREFIX."setting` SET store_id=0,code='codecart_core',`key`='codecart_public_documents_status',value='0',serialized=0");
    }
}
