<?php
namespace CodeCart\Core;

final class CompatibilityLayer {
    private $registry;
    private $db;
    private $config;
    private $tablesLoaded = false;
    private $tableCache = array();
    private $columnCache = array();

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
    }

    private function loadTables(): void {
        if ($this->tablesLoaded) return;
        $this->tablesLoaded = true;
        $this->tableCache = array();
        try {
            $query = $this->db->query('SHOW TABLES');
            foreach ($query->rows as $row) {
                foreach ($row as $name) {
                    $name = (string)$name;
                    if ($name !== '') $this->tableCache[$name] = true;
                    break;
                }
            }
        } catch (\Throwable $e) {
            // Compatibility checks must never make the storefront unavailable.
        }
    }

    public function tableExists(string $table): bool {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        if ($table === '') return false;
        $this->loadTables();
        return !empty($this->tableCache[DB_PREFIX . $table]);
    }

    private function loadColumns(string $table): void {
        if (array_key_exists($table, $this->columnCache)) return;
        $this->columnCache[$table] = array();
        if (!$this->tableExists($table)) return;
        try {
            $query = $this->db->query('SHOW COLUMNS FROM `' . DB_PREFIX . $table . '`');
            foreach ($query->rows as $row) {
                $field = isset($row['Field']) ? (string)$row['Field'] : '';
                if ($field !== '') $this->columnCache[$table][$field] = true;
            }
        } catch (\Throwable $e) {
            // Missing or restricted metadata access is treated as unsupported capability.
        }
    }

    public function columnExists(string $table, string $column): bool {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        if ($table === '' || $column === '') return false;
        $this->loadColumns($table);
        return !empty($this->columnCache[$table][$column]);
    }

    public function getMainCategorySource(): string {
        if ($this->columnExists('product_to_category', 'main_category')) return 'product_to_category.main_category';
        if ($this->columnExists('product', 'main_category_id')) return 'product.main_category_id';
        if ($this->tableExists('product_to_category')) return 'product_to_category';
        return 'unavailable';
    }

    public function getMainCategoryId(int $productId): int {
        if ($productId < 1) return 0;
        $items = $this->getMainCategoryIds(array($productId));
        return isset($items[$productId]) ? (int)$items[$productId] : 0;
    }

    public function getMainCategoryIds(array $productIds): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if (!$ids) return array();
        $result = array();
        foreach ($ids as $id) $result[$id] = 0;
        $idList = implode(',', $ids);

        if ($this->columnExists('product_to_category', 'main_category')) {
            $q = $this->db->query("SELECT product_id, category_id FROM `" . DB_PREFIX . "product_to_category` WHERE product_id IN (" . $idList . ") AND main_category='1' ORDER BY product_id ASC, category_id ASC");
            foreach ($q->rows as $row) {
                $id = (int)$row['product_id'];
                if (isset($result[$id]) && $result[$id] === 0) $result[$id] = (int)$row['category_id'];
            }
        }

        $missing = array_keys(array_filter($result, function($value){ return (int)$value === 0; }));
        if ($missing && $this->columnExists('product', 'main_category_id')) {
            $q = $this->db->query("SELECT product_id, main_category_id FROM `" . DB_PREFIX . "product` WHERE product_id IN (" . implode(',', array_map('intval', $missing)) . ")");
            foreach ($q->rows as $row) {
                $id = (int)$row['product_id'];
                if (isset($result[$id]) && (int)$row['main_category_id'] > 0) $result[$id] = (int)$row['main_category_id'];
            }
        }

        $missing = array_keys(array_filter($result, function($value){ return (int)$value === 0; }));
        if ($missing && $this->tableExists('product_to_category')) {
            $q = $this->db->query("SELECT product_id, MIN(category_id) AS category_id FROM `" . DB_PREFIX . "product_to_category` WHERE product_id IN (" . implode(',', array_map('intval', $missing)) . ") GROUP BY product_id");
            foreach ($q->rows as $row) {
                $id = (int)$row['product_id'];
                if (isset($result[$id])) $result[$id] = (int)$row['category_id'];
            }
        }
        return $result;
    }

    public function getActiveLanguages(): array {
        if (!$this->tableExists('language')) return array();
        $fields = array('language_id','name','code','sort_order','status');
        foreach (array('locale','directory') as $optional) {
            if ($this->columnExists('language', $optional)) $fields[] = $optional;
        }
        $safeFields = array();
        foreach ($fields as $field) $safeFields[] = '`' . $field . '`';
        $q = $this->db->query('SELECT ' . implode(',', $safeFields) . " FROM `" . DB_PREFIX . "language` WHERE status='1' ORDER BY sort_order ASC, name ASC");
        foreach ($q->rows as &$row) {
            if (!isset($row['locale'])) $row['locale'] = '';
            if (!isset($row['directory'])) $row['directory'] = '';
        }
        unset($row);
        return $q->rows;
    }

    public function diagnostics(): array {
        $languages = $this->getActiveLanguages();
        return array(
            'main_category_source' => $this->getMainCategorySource(),
            'active_languages' => count($languages),
            'language_locale_column' => $this->columnExists('language', 'locale'),
            'language_directory_column' => $this->columnExists('language', 'directory')
        );
    }

    public function platform(): array {
        return array(
            'version' => defined('VERSION') ? VERSION : '',
            'codecart_core' => defined('CODECART_BUILD') ? CODECART_BUILD : '',
            'package_build' => defined('CODECART_PACKAGE_BUILD') ? CODECART_PACKAGE_BUILD : '',
            'php' => PHP_VERSION,
            'db_prefix' => defined('DB_PREFIX') ? DB_PREFIX : '',
            'store_id' => (int)$this->config->get('config_store_id'),
            'language_id' => (int)$this->config->get('config_language_id')
        );
    }
}
