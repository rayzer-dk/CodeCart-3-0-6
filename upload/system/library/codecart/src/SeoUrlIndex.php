<?php
namespace CodeCart\Core;

/**
 * Request-scoped lookups used while generating storefront SEO links.
 *
 * Link rewriting runs once per generated URL, so per-link SELECTs grow with
 * menus, listings and modules. Taxonomy keywords (categories, manufacturers,
 * information pages, blog categories) are loaded once per store/language;
 * product and article keywords are prefetched in batches by listing models and
 * memoized for single lookups. Missing keywords are remembered as well.
 */
final class SeoUrlIndex {
    private const TAXONOMY_KEYS = array('category_id', 'manufacturer_id', 'information_id', 'blog_category_id');
    private const CHUNK = 500;

    private $registry;
    private $db;
    private $config;
    /** @var array<int, array<int, array<string, ?string>>> */
    private $keywords = array();
    /** @var array<int, array<int, bool>> */
    private $taxonomyLoaded = array();
    /** @var array<int, int> */
    private $mainCategories = array();
    /** @var array<int, int> */
    private $mainBlogCategories = array();

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
    }

    public static function fromRegistry($registry): self {
        $index = $registry->get('codecart_seo_url_index');
        if (!$index instanceof self) {
            $index = new self($registry);
            $registry->set('codecart_seo_url_index', $index);
        }
        return $index;
    }

    /**
     * Returns the newest non-empty keyword for a seo_url query, or null.
     */
    public function keyword(string $query, int $storeId, int $languageId): ?string {
        $key = $this->queryKey($query);
        if ($key !== '' && in_array($key, self::TAXONOMY_KEYS, true)) {
            $this->loadTaxonomy($storeId, $languageId);
            return $this->keywords[$storeId][$languageId][$query] ?? null;
        }
        if (!isset($this->keywords[$storeId][$languageId]) || !array_key_exists($query, $this->keywords[$storeId][$languageId])) {
            $this->fetch(array($query), $storeId, $languageId);
        }
        return $this->keywords[$storeId][$languageId][$query];
    }

    /**
     * Loads keywords (and SeoPro path data) for entities a page is about to link.
     */
    public function prefetch(string $key, array $ids): void {
        if (!$this->config->get('config_seo_url') || !in_array($key, array('product_id', 'article_id'), true)) {
            return;
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($id) { return $id > 0; })));
        if (!$ids) {
            return;
        }
        $storeId = (int)$this->config->get('config_store_id');
        $languageId = (int)$this->config->get('config_language_id');
        $queries = array();
        foreach ($ids as $id) {
            $query = $key . '=' . $id;
            if (!isset($this->keywords[$storeId][$languageId]) || !array_key_exists($query, $this->keywords[$storeId][$languageId])) {
                $queries[] = $query;
            }
        }
        if ($queries) {
            $this->fetch($queries, $storeId, $languageId);
        }
        if (!$this->config->get('config_seo_pro')) {
            return;
        }
        if ($key === 'product_id' && $this->config->get('config_seo_url_include_path')) {
            $this->loadMainCategories($ids);
        } elseif ($key === 'article_id') {
            $this->loadMainBlogCategories($ids);
        }
    }

    public function mainCategoryId(int $productId): int {
        if ($productId < 1) {
            return 0;
        }
        if (!array_key_exists($productId, $this->mainCategories)) {
            $this->loadMainCategories(array($productId));
        }
        return $this->mainCategories[$productId];
    }

    public function mainBlogCategoryId(int $articleId): int {
        if ($articleId < 1) {
            return 0;
        }
        if (!array_key_exists($articleId, $this->mainBlogCategories)) {
            $this->loadMainBlogCategories(array($articleId));
        }
        return $this->mainBlogCategories[$articleId];
    }

    private function queryKey(string $query): string {
        $position = strpos($query, '=');
        return $position === false ? '' : substr($query, 0, $position);
    }

    private function loadTaxonomy(int $storeId, int $languageId): void {
        if (!empty($this->taxonomyLoaded[$storeId][$languageId])) {
            return;
        }
        $this->taxonomyLoaded[$storeId][$languageId] = true;
        $conditions = array();
        foreach (self::TAXONOMY_KEYS as $key) {
            $conditions[] = "`query` LIKE '" . str_replace('_', '\\_', $key) . "=%'";
        }
        $query = $this->db->query("SELECT `query`, keyword FROM `" . DB_PREFIX . "seo_url` WHERE store_id = '" . $storeId . "' AND language_id = '" . $languageId . "' AND (" . implode(' OR ', $conditions) . ") ORDER BY seo_url_id ASC");
        $this->store($query->rows, $storeId, $languageId);
    }

    private function fetch(array $queries, int $storeId, int $languageId): void {
        foreach (array_chunk($queries, self::CHUNK) as $chunk) {
            $escaped = array();
            foreach ($chunk as $query) {
                $this->keywords[$storeId][$languageId][$query] = null;
                $escaped[] = "'" . $this->db->escape($query) . "'";
            }
            $result = $this->db->query("SELECT `query`, keyword FROM `" . DB_PREFIX . "seo_url` WHERE store_id = '" . $storeId . "' AND language_id = '" . $languageId . "' AND `query` IN (" . implode(',', $escaped) . ") ORDER BY seo_url_id ASC");
            $this->store($result->rows, $storeId, $languageId);
        }
    }

    private function store(array $rows, int $storeId, int $languageId): void {
        foreach ($rows as $row) {
            $keyword = (string)$row['keyword'];
            if ($keyword !== '') {
                $this->keywords[$storeId][$languageId][(string)$row['query']] = $keyword;
            }
        }
    }

    private function loadMainCategories(array $productIds): void {
        $missing = array();
        foreach ($productIds as $productId) {
            if (!array_key_exists((int)$productId, $this->mainCategories)) {
                $missing[] = (int)$productId;
            }
        }
        if (!$missing) {
            return;
        }
        $compat = $this->registry->get('codecart_compat');
        if (!$compat instanceof CompatibilityLayer) {
            $compat = new CompatibilityLayer($this->registry);
            $this->registry->set('codecart_compat', $compat);
        }
        foreach (array_chunk($missing, self::CHUNK) as $chunk) {
            foreach ($compat->getMainCategoryIds($chunk) as $productId => $categoryId) {
                $this->mainCategories[(int)$productId] = (int)$categoryId;
            }
            foreach ($chunk as $productId) {
                if (!array_key_exists($productId, $this->mainCategories)) {
                    $this->mainCategories[$productId] = 0;
                }
            }
        }
    }

    private function loadMainBlogCategories(array $articleIds): void {
        $missing = array();
        foreach ($articleIds as $articleId) {
            if (!array_key_exists((int)$articleId, $this->mainBlogCategories)) {
                $missing[] = (int)$articleId;
                $this->mainBlogCategories[(int)$articleId] = 0;
            }
        }
        foreach (array_chunk($missing, self::CHUNK) as $chunk) {
            // Same choice as the former per-article query: main category first, then the lowest category id.
            $query = $this->db->query("SELECT article_id, blog_category_id, main_blog_category FROM `" . DB_PREFIX . "article_to_blog_category` WHERE article_id IN (" . implode(',', $chunk) . ") ORDER BY article_id ASC, main_blog_category DESC, blog_category_id ASC");
            $seen = array();
            foreach ($query->rows as $row) {
                $articleId = (int)$row['article_id'];
                if (!isset($seen[$articleId])) {
                    $seen[$articleId] = true;
                    $this->mainBlogCategories[$articleId] = (int)$row['blog_category_id'];
                }
            }
        }
    }
}
