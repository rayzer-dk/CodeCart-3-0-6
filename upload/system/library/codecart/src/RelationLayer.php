<?php
namespace CodeCart\Core;

final class RelationLayer {
    private $registry;
    private $db;
    private $config;
    private $tableExistsCache = array();

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
    }

    public function enabled(): bool {
        return (bool)$this->config->get('codecart_relation_status');
    }

    public function storefrontEnabled(): bool {
        return $this->enabled() && (bool)$this->config->get('codecart_relation_storefront_status');
    }

    public function saveRelation(string $sourceType, int $sourceId, string $targetType, int $targetId, string $relationType = 'related', string $origin = 'module', int $score = 50, string $reason = ''): bool {
        if (!$this->enabled()) { return false; }
        $types = array('product','article','category','manufacturer');
        $origins = array('rule','ai','import','module');
        $sourceType = strtolower(trim($sourceType));
        $targetType = strtolower(trim($targetType));
        $relationType = strtolower(trim($relationType));
        $origin = strtolower(trim($origin));
        if (!in_array($sourceType, $types, true) || !in_array($targetType, $types, true) || !in_array($origin, $origins, true)) { return false; }
        if ($sourceId < 1 || $targetId < 1 || !preg_match('/^[a-z0-9_.:-]{2,32}$/', $relationType)) { return false; }
        if ($sourceType === $targetType && $sourceId === $targetId) { return false; }
        $this->upsertRelation($sourceType, $sourceId, $targetType, $targetId, $relationType, $origin, $score, $reason);
        return true;
    }

    public function getTargetIds(string $sourceType, int $sourceId, string $targetType, string $relationType = 'related', int $limit = 24, ?string $origin = null): array {
        if (!$this->enabled()) { return array(); }
        $types = array('product','article','category','manufacturer');
        $sourceType = strtolower(trim($sourceType));
        $targetType = strtolower(trim($targetType));
        $relationType = strtolower(trim($relationType));
        if (!in_array($sourceType, $types, true) || !in_array($targetType, $types, true) || $sourceId < 1 || !preg_match('/^[a-z0-9_.:-]{2,32}$/', $relationType)) { return array(); }
        $limit = max(1, min(100, $limit));
        $originSql = '';
        if ($origin !== null && $origin !== '') {
            $origin = strtolower(trim($origin));
            if (!in_array($origin, array('rule','ai','import','module'), true)) { return array(); }
            $originSql = " AND origin='" . $this->db->escape($origin) . "'";
        }
        $query = $this->db->query("SELECT target_id FROM `" . DB_PREFIX . "codecart_relation` WHERE source_type='" . $this->db->escape($sourceType) . "' AND source_id='" . (int)$sourceId . "' AND target_type='" . $this->db->escape($targetType) . "' AND relation_type='" . $this->db->escape($relationType) . "' AND status='1'" . $originSql . " ORDER BY score DESC, relation_id ASC LIMIT " . (int)$limit);
        $ids = array();
        foreach ($query->rows as $row) { $ids[] = (int)$row['target_id']; }
        return $ids;
    }

    public function suggestProduct(int $productId, ?int $productLimit = null, ?int $articleLimit = null): array {
        if (!$this->enabled()) { return array('products' => array(), 'articles' => array()); }
        $productId = max(0, $productId);
        if ($productId < 1) {
            return array('products' => array(), 'articles' => array());
        }

        $productLimit = $productLimit === null ? $this->intSetting('codecart_relation_product_limit', 8, 1, 24) : max(1, min(24, $productLimit));
        $articleLimit = $articleLimit === null ? $this->intSetting('codecart_relation_article_limit', 3, 0, 12) : max(0, min(12, $articleLimit));
        $source = $this->getProductSource($productId);
        if (!$source) {
            return array('products' => array(), 'articles' => array());
        }

        $products = $this->excludeManualProductTargets($productId, 'product', $this->suggestProductsForSource($source, $productLimit * 2));
        $articles = $articleLimit > 0 ? $this->excludeManualProductTargets($productId, 'article', $this->suggestArticlesForSource($source, $articleLimit * 3)) : array();
        return array(
            'products' => array_slice($products, 0, $productLimit),
            'articles' => array_slice($articles, 0, $articleLimit)
        );
    }

    public function suggestCategory(int $categoryId, ?int $productLimit = null, ?int $articleLimit = null): array {
        if (!$this->enabled() || $categoryId < 1) {
            return array('products' => array(), 'articles' => array());
        }
        $productLimit = $productLimit === null ? $this->intSetting('codecart_relation_product_limit', 8, 1, 24) : max(1, min(24, $productLimit));
        $articleLimit = $articleLimit === null ? $this->intSetting('codecart_relation_article_limit', 3, 0, 12) : max(0, min(12, $articleLimit));
        $languageId = (int)$this->config->get('config_language_id');
        $sourceQuery = $this->db->query("SELECT c.category_id, cd.name, cd.meta_keyword FROM `" . DB_PREFIX . "category` c INNER JOIN `" . DB_PREFIX . "category_description` cd ON (cd.category_id=c.category_id AND cd.language_id='" . $languageId . "') WHERE c.category_id='" . (int)$categoryId . "' LIMIT 1");
        if (!$sourceQuery->num_rows) { return array('products'=>array(),'articles'=>array()); }
        $tokens = $this->tokens((string)$sourceQuery->row['name'] . ' ' . (string)$sourceQuery->row['meta_keyword']);

        $branch = $this->branchCategories(array($categoryId));
        $products = array();
        if ($branch) {
            $stockSql = $this->boolSetting('codecart_relation_in_stock_only', true) ? " AND p.quantity > 0" : '';
            $query = $this->db->query("SELECT p.product_id, p.quantity, p.sort_order, pd.name, MAX(p2c.category_id='" . (int)$categoryId . "') AS direct_category FROM `" . DB_PREFIX . "product_to_category` p2c INNER JOIN `" . DB_PREFIX . "product` p ON (p.product_id=p2c.product_id) INNER JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id=p.product_id AND pd.language_id='" . $languageId . "') WHERE p2c.category_id IN (" . implode(',', array_map('intval',$branch)) . ") AND p.status='1' AND p.date_available <= NOW()" . $stockSql . " GROUP BY p.product_id,p.quantity,p.sort_order,pd.name ORDER BY direct_category DESC,p.quantity DESC,p.sort_order ASC,p.product_id ASC LIMIT 120");
            foreach ($query->rows as $row) {
                $score = !empty($row['direct_category']) ? 90 : 72;
                $overlap = $this->tokenOverlap($tokens, $this->tokens((string)$row['name']));
                if ($overlap > 0) { $score += min(8, $overlap * 2); }
                if ((int)$row['quantity'] > 0) { $score += 2; }
                $products[] = array('id'=>(int)$row['product_id'],'name'=>(string)$row['name'],'score'=>min(100,$score),'reason'=>(!empty($row['direct_category'])?'same_category':'subcategory') . ($overlap ? ',name:' . $overlap : ''));
            }
        }
        $products = $this->excludeManualCategoryTargets($categoryId, 'product', $products);
        $products = array_slice($products, 0, $productLimit);

        $articles = $articleLimit > 0 ? $this->suggestArticlesByTokens($tokens, $articleLimit * 3) : array();
        $articles = $this->excludeManualCategoryTargets($categoryId, 'article', $articles);
        $articles = array_slice($articles, 0, $articleLimit);
        return array('products'=>$products,'articles'=>$articles);
    }

    public function suggestArticle(int $articleId, ?int $productLimit = null, ?int $articleLimit = null): array {
        if (!$this->enabled() || $articleId < 1 || !$this->tableExists('article')) {
            return array('products' => array(), 'articles' => array());
        }
        $productLimit = $productLimit === null ? $this->intSetting('codecart_relation_product_limit', 8, 1, 24) : max(1, min(24, $productLimit));
        $articleLimit = $articleLimit === null ? $this->intSetting('codecart_relation_article_limit', 3, 0, 12) : max(0, min(12, $articleLimit));
        $languageId = (int)$this->config->get('config_language_id');
        $sourceQuery = $this->db->query("SELECT a.article_id, ad.name, ad.tag, ad.meta_keyword FROM `" . DB_PREFIX . "article` a INNER JOIN `" . DB_PREFIX . "article_description` ad ON (ad.article_id=a.article_id AND ad.language_id='" . $languageId . "') WHERE a.article_id='" . (int)$articleId . "' LIMIT 1");
        if (!$sourceQuery->num_rows) { return array('products'=>array(),'articles'=>array()); }
        $tokens = $this->tokens((string)$sourceQuery->row['name'] . ' ' . (string)$sourceQuery->row['tag'] . ' ' . (string)$sourceQuery->row['meta_keyword']);

        $blogCategoryIds = array();
        if ($this->tableExists('article_to_blog_category')) {
            $cq = $this->db->query("SELECT blog_category_id FROM `" . DB_PREFIX . "article_to_blog_category` WHERE article_id='" . (int)$articleId . "'");
            foreach ($cq->rows as $r) { $blogCategoryIds[]=(int)$r['blog_category_id']; }
        }

        $articles = array();
        $conditions = array();
        if ($blogCategoryIds) { $conditions[] = "a2c.blog_category_id IN (" . implode(',',array_map('intval',$blogCategoryIds)) . ")"; }
        $likes = $this->articleTokenConditions($tokens, 'ad');
        if ($likes) { $conditions[] = '(' . implode(' OR ', $likes) . ')'; }
        if ($conditions) {
            $join = $blogCategoryIds ? " LEFT JOIN `" . DB_PREFIX . "article_to_blog_category` a2c ON (a2c.article_id=a.article_id)" : '';
            $query = $this->db->query("SELECT a.article_id, ad.name, ad.tag, ad.meta_keyword, " . ($blogCategoryIds ? "MAX(a2c.blog_category_id IN (" . implode(',',array_map('intval',$blogCategoryIds)) . "))" : "0") . " AS same_blog_category FROM `" . DB_PREFIX . "article` a INNER JOIN `" . DB_PREFIX . "article_description` ad ON (ad.article_id=a.article_id AND ad.language_id='" . $languageId . "')" . $join . " WHERE a.article_id <> '" . (int)$articleId . "' AND a.status='1' AND a.date_available <= NOW() AND (" . implode(' OR ',$conditions) . ") GROUP BY a.article_id,ad.name,ad.tag,ad.meta_keyword ORDER BY same_blog_category DESC,a.viewed DESC,a.sort_order ASC,a.article_id ASC LIMIT 100");
            foreach ($query->rows as $row) {
                $overlap=$this->tokenOverlap($tokens,$this->tokens((string)$row['name'].' '.(string)$row['tag'].' '.(string)$row['meta_keyword']));
                $score=!empty($row['same_blog_category'])?70:35;
                if ($overlap>0) { $score+=min(30,$overlap*10); }
                if ($score<40) { continue; }
                $reason=array(); if(!empty($row['same_blog_category']))$reason[]='same_blog_category'; if($overlap)$reason[]='text:'.$overlap;
                $articles[]=array('id'=>(int)$row['article_id'],'name'=>(string)$row['name'],'score'=>min(100,$score),'reason'=>implode(',',$reason));
            }
        }
        $articles=$this->excludeManualArticleTargets($articleId,'article',$articles);
        $articles=array_slice($articles,0,$articleLimit);

        $products=$this->suggestProductsByTokens($tokens,$productLimit*3);
        $products=$this->excludeManualArticleTargets($articleId,'product',$products);
        $products=array_slice($products,0,$productLimit);
        return array('products'=>$products,'articles'=>$articles);
    }

    public function generateProduct(int $productId): array {
        if (!$this->enabled()) {
            return array('status' => 'disabled', 'product_id' => $productId, 'products' => 0, 'articles' => 0);
        }

        $suggestions = $this->suggestProduct($productId);
        $products = 0;
        $articles = 0;

        $this->db->beginTransaction();
        try {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_relation` WHERE source_type='product' AND source_id='" . (int)$productId . "' AND origin='rule'");
            foreach ($suggestions['products'] as $row) {
                $this->upsertRelation('product', $productId, 'product', (int)$row['id'], 'related', 'rule', (int)$row['score'], (string)$row['reason']);
                $products++;
            }
            foreach ($suggestions['articles'] as $row) {
                $this->upsertRelation('product', $productId, 'article', (int)$row['id'], 'related', 'rule', (int)$row['score'], (string)$row['reason']);
                $articles++;
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            try { $this->db->rollback(); } catch (\Throwable $ignored) {}
            throw $e;
        }

        return array('status' => 'ok', 'product_id' => $productId, 'products' => $products, 'articles' => $articles);
    }

    public function getAutoProductIds(int $productId, int $limit = 12): array {
        if (!$this->storefrontEnabled() || $productId < 1) {
            return array();
        }
        $limit = max(1, min(48, $limit));
        $query = $this->db->query("SELECT target_id FROM `" . DB_PREFIX . "codecart_relation` WHERE source_type='product' AND source_id='" . (int)$productId . "' AND target_type='product' AND relation_type='related' AND origin='rule' AND status='1' ORDER BY score DESC, relation_id ASC LIMIT " . (int)$limit);
        $ids = array();
        foreach ($query->rows as $row) {
            $ids[] = (int)$row['target_id'];
        }
        return $ids;
    }

    public function getAutoArticleIds(int $productId, int $limit = 6): array {
        if (!$this->storefrontEnabled() || $productId < 1) {
            return array();
        }
        $limit = max(1, min(24, $limit));
        $query = $this->db->query("SELECT target_id FROM `" . DB_PREFIX . "codecart_relation` WHERE source_type='product' AND source_id='" . (int)$productId . "' AND target_type='article' AND relation_type='related' AND origin='rule' AND status='1' ORDER BY score DESC, relation_id ASC LIMIT " . (int)$limit);
        $ids = array();
        foreach ($query->rows as $row) {
            $ids[] = (int)$row['target_id'];
        }
        return $ids;
    }

    public function stats(): array {
        $stats = array('total' => 0, 'products' => 0, 'articles' => 0, 'sources' => 0);
        try {
            $query = $this->db->query("SELECT COUNT(*) AS total, COUNT(DISTINCT CONCAT(source_type, ':', source_id)) AS sources, SUM(target_type='product') AS products, SUM(target_type='article') AS articles FROM `" . DB_PREFIX . "codecart_relation` WHERE origin='rule' AND status='1'");
            if ($query->num_rows) {
                $stats['total'] = (int)$query->row['total'];
                $stats['sources'] = (int)$query->row['sources'];
                $stats['products'] = (int)$query->row['products'];
                $stats['articles'] = (int)$query->row['articles'];
            }
        } catch (\Throwable $e) {
            // Migration/preflight can render the settings page before the table is created.
        }
        return $stats;
    }

    public function getRelations(array $filter = array()): array {
        if (!$this->tableExists('codecart_relation')) { return array(); }
        $languageId = (int)$this->config->get('config_language_id');
        $start = max(0, isset($filter['start']) ? (int)$filter['start'] : 0);
        $limit = max(1, min(100, isset($filter['limit']) ? (int)$filter['limit'] : 25));
        $sortMap = array(
            'source' => 'source_name',
            'target' => 'target_name',
            'type' => 'cr.target_type',
            'score' => 'cr.score',
            'status' => 'cr.status',
            'date_modified' => 'cr.date_modified'
        );
        $sort = isset($filter['sort']) && isset($sortMap[$filter['sort']]) ? $sortMap[$filter['sort']] : 'cr.score';
        $order = isset($filter['order']) && strtoupper((string)$filter['order']) === 'ASC' ? 'ASC' : 'DESC';
        $where = $this->relationWhere($filter, $languageId);
        $nameSource = "COALESCE(spd.name,sad.name,scd.name,sm.name,CONCAT(cr.source_type,' #',cr.source_id))";
        $nameTarget = "COALESCE(tpd.name,tad.name,tcd.name,tm.name,CONCAT(cr.target_type,' #',cr.target_id))";
        $joins = $this->relationNameJoins($languageId);
        $sql = "SELECT cr.*, " . $nameSource . " AS source_name, " . $nameTarget . " AS target_name FROM `" . DB_PREFIX . "codecart_relation` cr " . $joins . " WHERE " . $where . " ORDER BY " . $sort . " " . $order . ", cr.relation_id DESC LIMIT " . $start . "," . $limit;
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getRelationTotal(array $filter = array()): int {
        if (!$this->tableExists('codecart_relation')) { return 0; }
        $languageId = (int)$this->config->get('config_language_id');
        $joins = $this->relationNameJoins($languageId);
        $where = $this->relationWhere($filter, $languageId);
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_relation` cr " . $joins . " WHERE " . $where);
        return $query->num_rows ? (int)$query->row['total'] : 0;
    }

    public function setRelationStatus(array $relationIds, int $status): int {
        if (!$this->tableExists('codecart_relation')) { return 0; }
        $ids = array_values(array_unique(array_filter(array_map('intval', $relationIds))));
        $ids = array_slice($ids, 0, 1000);
        if (!$ids) { return 0; }
        $this->db->query("UPDATE `" . DB_PREFIX . "codecart_relation` SET status='" . ($status ? 1 : 0) . "', date_modified=NOW() WHERE origin='rule' AND relation_id IN (" . implode(',', $ids) . ")");
        return method_exists($this->db, 'countAffected') ? (int)$this->db->countAffected() : 0;
    }

    public function deleteRelations(array $relationIds): int {
        if (!$this->tableExists('codecart_relation')) { return 0; }
        $ids = array_values(array_unique(array_filter(array_map('intval', $relationIds))));
        $ids = array_slice($ids, 0, 1000);
        if (!$ids) { return 0; }
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_relation` WHERE origin='rule' AND relation_id IN (" . implode(',', $ids) . ")");
        return method_exists($this->db, 'countAffected') ? (int)$this->db->countAffected() : 0;
    }

    private function relationNameJoins(int $languageId): string {
        return " LEFT JOIN `" . DB_PREFIX . "product_description` spd ON (cr.source_type='product' AND spd.product_id=cr.source_id AND spd.language_id='" . $languageId . "')"
            . " LEFT JOIN `" . DB_PREFIX . "article_description` sad ON (cr.source_type='article' AND sad.article_id=cr.source_id AND sad.language_id='" . $languageId . "')"
            . " LEFT JOIN `" . DB_PREFIX . "category_description` scd ON (cr.source_type='category' AND scd.category_id=cr.source_id AND scd.language_id='" . $languageId . "')"
            . " LEFT JOIN `" . DB_PREFIX . "manufacturer` sm ON (cr.source_type='manufacturer' AND sm.manufacturer_id=cr.source_id)"
            . " LEFT JOIN `" . DB_PREFIX . "product_description` tpd ON (cr.target_type='product' AND tpd.product_id=cr.target_id AND tpd.language_id='" . $languageId . "')"
            . " LEFT JOIN `" . DB_PREFIX . "article_description` tad ON (cr.target_type='article' AND tad.article_id=cr.target_id AND tad.language_id='" . $languageId . "')"
            . " LEFT JOIN `" . DB_PREFIX . "category_description` tcd ON (cr.target_type='category' AND tcd.category_id=cr.target_id AND tcd.language_id='" . $languageId . "')"
            . " LEFT JOIN `" . DB_PREFIX . "manufacturer` tm ON (cr.target_type='manufacturer' AND tm.manufacturer_id=cr.target_id)";
    }

    private function relationWhere(array $filter, int $languageId): string {
        $where = array("cr.origin='rule'");
        if (!empty($filter['target_type']) && in_array($filter['target_type'], array('product','article','category','manufacturer'), true)) {
            $where[] = "cr.target_type='" . $this->db->escape($filter['target_type']) . "'";
        }
        if (isset($filter['status']) && $filter['status'] !== '' && in_array((string)$filter['status'], array('0','1'), true)) {
            $where[] = "cr.status='" . (int)$filter['status'] . "'";
        }
        if (!empty($filter['search'])) {
            $needle = $this->db->escape((string)$filter['search']);
            $where[] = "(spd.name LIKE '%" . $needle . "%' OR sad.name LIKE '%" . $needle . "%' OR scd.name LIKE '%" . $needle . "%' OR sm.name LIKE '%" . $needle . "%' OR tpd.name LIKE '%" . $needle . "%' OR tad.name LIKE '%" . $needle . "%' OR tcd.name LIKE '%" . $needle . "%' OR tm.name LIKE '%" . $needle . "%' OR cr.reason LIKE '%" . $needle . "%' OR cr.source_id='" . (int)$filter['search'] . "' OR cr.target_id='" . (int)$filter['search'] . "')";
        }
        return implode(' AND ', $where);
    }

    public function purgeEntity(string $type, int $id): int {
        if (!$this->enabled()) { return 0; }
        $type = strtolower(trim($type));
        if ($id < 1 || !in_array($type, array('product','article','category','manufacturer'), true) || !$this->tableExists('codecart_relation')) { return 0; }
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_relation` WHERE (source_type='" . $this->db->escape($type) . "' AND source_id='" . (int)$id . "') OR (target_type='" . $this->db->escape($type) . "' AND target_id='" . (int)$id . "')");
        return method_exists($this->db, 'countAffected') ? (int)$this->db->countAffected() : 0;
    }

    public function invalidateProductRulesByCategory(int $categoryId): int {
        if (!$this->enabled()) { return 0; }
        if ($categoryId < 1 || !$this->tableExists('codecart_relation') || !$this->tableExists('product_to_category')) { return 0; }
        $this->db->query("DELETE cr FROM `" . DB_PREFIX . "codecart_relation` cr INNER JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p2c.product_id=cr.source_id) WHERE cr.source_type='product' AND cr.origin='rule' AND p2c.category_id='" . (int)$categoryId . "'");
        return method_exists($this->db, 'countAffected') ? (int)$this->db->countAffected() : 0;
    }

    public function invalidateProductRulesByManufacturer(int $manufacturerId): int {
        if (!$this->enabled()) { return 0; }
        if ($manufacturerId < 1 || !$this->tableExists('codecart_relation') || !$this->tableExists('product')) { return 0; }
        $this->db->query("DELETE cr FROM `" . DB_PREFIX . "codecart_relation` cr INNER JOIN `" . DB_PREFIX . "product` p ON (p.product_id=cr.source_id) WHERE cr.source_type='product' AND cr.origin='rule' AND p.manufacturer_id='" . (int)$manufacturerId . "'");
        return method_exists($this->db, 'countAffected') ? (int)$this->db->countAffected() : 0;
    }

    public function cleanupOrphans(): int {
        if (!$this->enabled() || !$this->tableExists('codecart_relation')) { return 0; }
        $map = array(
            'product' => array('product', 'product_id'),
            'category' => array('category', 'category_id'),
            'manufacturer' => array('manufacturer', 'manufacturer_id'),
            'article' => array('article', 'article_id')
        );
        $removed = 0;
        foreach ($map as $type => $definition) {
            [$table, $idColumn] = $definition;
            if (!$this->tableExists($table)) { continue; }
            foreach (array('source' => 'source_id', 'target' => 'target_id') as $side => $relationIdColumn) {
                $typeColumn = $side . '_type';
                $alias = $side === 'source' ? 'src' : 'dst';
                $this->db->query("DELETE cr FROM `" . DB_PREFIX . "codecart_relation` cr LEFT JOIN `" . DB_PREFIX . $table . "` " . $alias . " ON (" . $alias . ".`" . $idColumn . "`=cr.`" . $relationIdColumn . "`) WHERE cr.`" . $typeColumn . "`='" . $this->db->escape($type) . "' AND " . $alias . ".`" . $idColumn . "` IS NULL");
                if (method_exists($this->db, 'countAffected')) { $removed += (int)$this->db->countAffected(); }
            }
        }
        return $removed;
    }

    public function clearAuto(bool $force = false): int {
        if (!$force && !$this->enabled()) { return 0; }
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_relation` WHERE origin='rule'");
        return method_exists($this->db, 'countAffected') ? (int)$this->db->countAffected() : 0;
    }

    private function getProductSource(int $productId): array {
        $languageId = (int)$this->config->get('config_language_id');
        $query = $this->db->query("SELECT p.product_id, p.manufacturer_id, p.quantity, p.model, pd.name FROM `" . DB_PREFIX . "product` p INNER JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id=p.product_id AND pd.language_id='" . $languageId . "') WHERE p.product_id='" . (int)$productId . "' LIMIT 1");
        if (!$query->num_rows) {
            return array();
        }

        $source = $query->row;
        $source['categories'] = array();
        $categoryQuery = $this->db->query("SELECT category_id FROM `" . DB_PREFIX . "product_to_category` WHERE product_id='" . (int)$productId . "'");
        foreach ($categoryQuery->rows as $row) {
            $source['categories'][] = (int)$row['category_id'];
        }
        $source['categories'] = array_values(array_unique(array_filter($source['categories'])));

        $source['attributes'] = array();
        $attributeQuery = $this->db->query("SELECT DISTINCT attribute_id FROM `" . DB_PREFIX . "product_attribute` WHERE product_id='" . (int)$productId . "' AND language_id='" . $languageId . "'");
        foreach ($attributeQuery->rows as $row) {
            $source['attributes'][] = (int)$row['attribute_id'];
        }
        $source['attributes'] = array_values(array_unique(array_filter($source['attributes'])));
        $source['tokens'] = $this->tokens((string)$source['name'] . ' ' . (string)$source['model']);
        return $source;
    }

    private function suggestProductsForSource(array $source, int $limit): array {
        $directCategories = $source['categories'];
        $branchCategories = $this->branchCategories($directCategories);
        $manufacturerId = (int)$source['manufacturer_id'];
        $conditions = array();
        if ($branchCategories) {
            // Category/subcategory is the primary candidate pool. Manufacturer is
            // used as a ranking signal, not as a broad OR that could scan a very
            // large brand catalog on 100k+ product stores.
            $conditions[] = "p2c.category_id IN (" . implode(',', array_map('intval', $branchCategories)) . ")";
        } elseif ($this->boolSetting('codecart_relation_use_manufacturer', true) && $manufacturerId > 0) {
            $conditions[] = "p.manufacturer_id='" . $manufacturerId . "'";
        }
        if (!$conditions) {
            return $this->fallbackProductNameSuggestions($source, $limit);
        }

        $languageId = (int)$this->config->get('config_language_id');
        $directSql = $directCategories ? implode(',', array_map('intval', $directCategories)) : '0';
        $branchSql = $branchCategories ? implode(',', array_map('intval', $branchCategories)) : '0';
        $stockSql = $this->boolSetting('codecart_relation_in_stock_only', true) ? " AND p.quantity > 0" : '';

        $sql = "SELECT p.product_id, p.manufacturer_id, p.quantity, p.sort_order, pd.name, " .
            "MAX(CASE WHEN p2c.category_id IN (" . $directSql . ") THEN 1 ELSE 0 END) AS direct_category, " .
            "MAX(CASE WHEN p2c.category_id IN (" . $branchSql . ") THEN 1 ELSE 0 END) AS branch_category " .
            "FROM `" . DB_PREFIX . "product` p " .
            "INNER JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id=p.product_id AND pd.language_id='" . $languageId . "') " .
            "LEFT JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p2c.product_id=p.product_id) " .
            "WHERE p.product_id <> '" . (int)$source['product_id'] . "' AND p.status='1' AND p.date_available <= NOW()" . $stockSql . " AND (" . implode(' OR ', $conditions) . ") " .
            "GROUP BY p.product_id, p.manufacturer_id, p.quantity, p.sort_order, pd.name " .
            "ORDER BY direct_category DESC, branch_category DESC, (p.manufacturer_id='" . $manufacturerId . "') DESC, p.quantity DESC, p.sort_order ASC, p.product_id ASC LIMIT 120";

        $query = $this->db->query($sql);
        if (!$query->num_rows) {
            return $this->fallbackProductNameSuggestions($source, $limit);
        }

        $candidateIds = array();
        foreach ($query->rows as $row) {
            $candidateIds[] = (int)$row['product_id'];
        }
        $sharedAttributes = $this->sharedAttributeCounts($candidateIds, $source['attributes']);
        $rows = array();
        foreach ($query->rows as $row) {
            $id = (int)$row['product_id'];
            $score = 0;
            $reasons = array();
            if (!empty($row['direct_category'])) {
                $score += 60;
                $reasons[] = 'same_category';
            } elseif (!empty($row['branch_category'])) {
                $score += 40;
                $reasons[] = 'subcategory';
            }
            if ($this->boolSetting('codecart_relation_use_manufacturer', true) && $manufacturerId > 0 && (int)$row['manufacturer_id'] === $manufacturerId) {
                $score += 20;
                $reasons[] = 'manufacturer';
            }
            $shared = isset($sharedAttributes[$id]) ? (int)$sharedAttributes[$id] : 0;
            if ($shared > 0) {
                $score += min(18, $shared * 3);
                $reasons[] = 'attributes:' . $shared;
            }
            $overlap = $this->tokenOverlap($source['tokens'], $this->tokens((string)$row['name']));
            if ($overlap > 0) {
                $score += min(15, $overlap * 5);
                $reasons[] = 'name:' . $overlap;
            }
            if ((int)$row['quantity'] > 0) {
                $score += 4;
            }
            if ($score < 35) {
                continue;
            }
            $score = min(100, $score);
            $rows[] = array('id' => $id, 'name' => (string)$row['name'], 'score' => $score, 'reason' => implode(',', $reasons));
        }
        usort($rows, static function($a, $b) {
            if ((int)$a['score'] === (int)$b['score']) { return (int)$a['id'] <=> (int)$b['id']; }
            return (int)$b['score'] <=> (int)$a['score'];
        });
        return array_slice($rows, 0, $limit);
    }

    private function fallbackProductNameSuggestions(array $source, int $limit): array {
        $tokens = $source['tokens'];
        if (!$tokens) { return array(); }
        $languageId = (int)$this->config->get('config_language_id');
        $likes = array();
        foreach (array_slice($tokens, 0, 4) as $token) {
            $likes[] = "pd.name LIKE '%" . $this->db->escape($token) . "%'";
        }
        if (!$likes) { return array(); }
        $stockSql = $this->boolSetting('codecart_relation_in_stock_only', true) ? " AND p.quantity > 0" : '';
        $query = $this->db->query("SELECT p.product_id, pd.name, p.quantity FROM `" . DB_PREFIX . "product` p INNER JOIN `" . DB_PREFIX . "product_description` pd ON (pd.product_id=p.product_id AND pd.language_id='" . $languageId . "') WHERE p.product_id <> '" . (int)$source['product_id'] . "' AND p.status='1' AND p.date_available <= NOW()" . $stockSql . " AND (" . implode(' OR ', $likes) . ") ORDER BY p.sort_order ASC, p.product_id ASC LIMIT 80");
        $rows = array();
        foreach ($query->rows as $row) {
            $overlap = $this->tokenOverlap($tokens, $this->tokens((string)$row['name']));
            if ($overlap < 1) { continue; }
            $rows[] = array('id'=>(int)$row['product_id'], 'name'=>(string)$row['name'], 'score'=>min(45, 20 + $overlap * 8), 'reason'=>'name:' . $overlap);
        }
        usort($rows, static function($a,$b){ return (int)$b['score'] <=> (int)$a['score']; });
        return array_slice($rows, 0, $limit);
    }

    private function suggestArticlesForSource(array $source, int $limit): array {
        if (!$this->tableExists('article') || !$this->tableExists('article_description')) { return array(); }
        $limit=max(1,min(100,$limit));
        $languageId=(int)$this->config->get('config_language_id');
        $rows=array();

        // Existing category/manufacturer editorial relations are the strongest signal.
        // Product categories are considered first; articles attached to their parent
        // categories are inherited with a slightly lower score.
        if (!empty($source['categories']) && $this->tableExists('article_related_wb')) {
            $directCategoryIds=array_values(array_unique(array_filter(array_map('intval',$source['categories']))));
            $categoryScores=array();
            foreach($directCategoryIds as $categoryId){$categoryScores[$categoryId]=95;}
            if($directCategoryIds && $this->tableExists('category_path')){
                $pq=$this->db->query("SELECT DISTINCT path_id FROM `".DB_PREFIX."category_path` WHERE category_id IN (".implode(',',$directCategoryIds).")");
                foreach($pq->rows as $pr){$parentId=(int)$pr['path_id'];if($parentId>0&&!isset($categoryScores[$parentId])){$categoryScores[$parentId]=88;}}
            }
            if($categoryScores){
                $categoryIds=array_keys($categoryScores);
                $q=$this->db->query("SELECT DISTINCT ar.category_id,a.article_id,ad.name FROM `".DB_PREFIX."article_related_wb` ar INNER JOIN `".DB_PREFIX."article` a ON (a.article_id=ar.article_id) INNER JOIN `".DB_PREFIX."article_description` ad ON (ad.article_id=a.article_id AND ad.language_id='".$languageId."') WHERE ar.category_id IN (".implode(',',array_map('intval',$categoryIds)).") AND a.status='1' AND a.date_available<=NOW() ORDER BY a.sort_order ASC,a.article_id ASC LIMIT 80");
                foreach($q->rows as $r){
                    $id=(int)$r['article_id'];$categoryId=(int)$r['category_id'];$score=isset($categoryScores[$categoryId])?(int)$categoryScores[$categoryId]:88;$reason=$score>=95?'same_category':'parent_category';
                    if(!isset($rows[$id])||$score>(int)$rows[$id]['score']){$rows[$id]=array('id'=>$id,'name'=>(string)$r['name'],'score'=>$score,'reason'=>$reason);}
                }
            }
        }
        $manufacturerId=(int)($source['manufacturer_id']??0);
        if($manufacturerId>0 && $this->tableExists('article_related_mn')){
            $q=$this->db->query("SELECT DISTINCT a.article_id,ad.name FROM `".DB_PREFIX."article_related_mn` ar INNER JOIN `".DB_PREFIX."article` a ON (a.article_id=ar.article_id) INNER JOIN `".DB_PREFIX."article_description` ad ON (ad.article_id=a.article_id AND ad.language_id='".$languageId."') WHERE ar.manufacturer_id='".$manufacturerId."' AND a.status='1' AND a.date_available<=NOW() ORDER BY a.sort_order ASC,a.article_id ASC LIMIT 40");
            foreach($q->rows as $r){$id=(int)$r['article_id'];if(!isset($rows[$id])){$rows[$id]=array('id'=>$id,'name'=>(string)$r['name'],'score'=>82,'reason'=>'manufacturer');}}
        }
        foreach($this->suggestArticlesByTokens(isset($source['tokens'])?$source['tokens']:array(),80) as $r){
            $id=(int)$r['id']; if(!isset($rows[$id])){$rows[$id]=$r;} elseif((int)$r['score']>(int)$rows[$id]['score']){$rows[$id]=$r;}
        }
        $rows=array_values($rows);
        usort($rows,static function($a,$b){return ((int)$b['score']<=> (int)$a['score']) ?: ((int)$a['id']<=> (int)$b['id']);});
        return array_slice($rows,0,$limit);
    }

    private function suggestArticlesByTokens(array $tokens, int $limit): array {
        if (!$tokens || !$this->tableExists('article') || !$this->tableExists('article_description')) { return array(); }
        $languageId=(int)$this->config->get('config_language_id');
        $likes=$this->articleTokenConditions($tokens,'ad');
        if(!$likes)return array();
        $limit=max(1,min(100,$limit));
        $q=$this->db->query("SELECT a.article_id,ad.name,ad.tag,ad.meta_keyword FROM `".DB_PREFIX."article` a INNER JOIN `".DB_PREFIX."article_description` ad ON (ad.article_id=a.article_id AND ad.language_id='".$languageId."') WHERE a.status='1' AND a.date_available<=NOW() AND (".implode(' OR ',$likes).") ORDER BY a.viewed DESC,a.sort_order ASC,a.article_id ASC LIMIT ".$limit);
        $rows=array();
        foreach($q->rows as $row){$overlap=$this->tokenOverlap($tokens,$this->tokens((string)$row['name'].' '.(string)$row['tag'].' '.(string)$row['meta_keyword']));if($overlap<1)continue;$rows[]=array('id'=>(int)$row['article_id'],'name'=>(string)$row['name'],'score'=>min(85,35+$overlap*12),'reason'=>'text:'.$overlap);}
        usort($rows,static function($a,$b){return ((int)$b['score']<=> (int)$a['score']) ?: ((int)$a['id']<=> (int)$b['id']);});
        return $rows;
    }

    private function suggestProductsByTokens(array $tokens, int $limit): array {
        if(!$tokens)return array();
        $languageId=(int)$this->config->get('config_language_id');
        $likes=array();
        foreach(array_slice($tokens,0,5) as $token){$e=$this->db->escape($token);$likes[]="(pd.name LIKE '%".$e."%' OR pd.tag LIKE '%".$e."%' OR p.model LIKE '%".$e."%')";}
        if(!$likes)return array();
        $stockSql=$this->boolSetting('codecart_relation_in_stock_only',true)?" AND p.quantity>0":'';
        $limit=max(1,min(120,$limit));
        $q=$this->db->query("SELECT p.product_id,p.quantity,p.model,pd.name,pd.tag FROM `".DB_PREFIX."product` p INNER JOIN `".DB_PREFIX."product_description` pd ON (pd.product_id=p.product_id AND pd.language_id='".$languageId."') WHERE p.status='1' AND p.date_available<=NOW()".$stockSql." AND (".implode(' OR ',$likes).") ORDER BY p.quantity DESC,p.sort_order ASC,p.product_id ASC LIMIT ".$limit);
        $rows=array();
        foreach($q->rows as $row){$overlap=$this->tokenOverlap($tokens,$this->tokens((string)$row['name'].' '.(string)$row['model'].' '.(string)$row['tag']));if($overlap<1)continue;$score=min(80,30+$overlap*12+((int)$row['quantity']>0?3:0));$rows[]=array('id'=>(int)$row['product_id'],'name'=>(string)$row['name'],'score'=>$score,'reason'=>'text:'.$overlap);}
        usort($rows,static function($a,$b){return ((int)$b['score']<=> (int)$a['score']) ?: ((int)$a['id']<=> (int)$b['id']);});
        return $rows;
    }

    private function articleTokenConditions(array $tokens, string $alias): array {
        $conditions=array();
        foreach(array_slice($tokens,0,5) as $token){$e=$this->db->escape($token);$conditions[]="(".$alias.".name LIKE '%".$e."%' OR ".$alias.".tag LIKE '%".$e."%' OR ".$alias.".meta_keyword LIKE '%".$e."%')";}
        return $conditions;
    }

    private function excludeManualProductTargets(int $productId, string $targetType, array $rows): array {
        if(!$rows)return array();
        $table=$targetType==='product'?'product_related':'product_related_article';
        $sourceColumn='product_id';
        $column=$targetType==='product'?'related_id':'article_id';
        if(!$this->tableExists($table))return $rows;
        $q=$this->db->query("SELECT `".$column."` AS target_id FROM `".DB_PREFIX.$table."` WHERE `".$sourceColumn."`='".(int)$productId."'");
        $exclude=array();foreach($q->rows as $r){$exclude[(int)$r['target_id']]=true;}
        return array_values(array_filter($rows,static function($r)use($exclude){return !isset($exclude[(int)$r['id']]);}));
    }

    private function excludeManualCategoryTargets(int $categoryId, string $targetType, array $rows): array {
        if(!$rows)return array();
        $table=$targetType==='product'?'product_related_wb':'article_related_wb';
        $column=$targetType==='product'?'product_id':'article_id';
        if(!$this->tableExists($table))return $rows;
        $q=$this->db->query("SELECT `".$column."` AS target_id FROM `".DB_PREFIX.$table."` WHERE category_id='".(int)$categoryId."'");
        $exclude=array();foreach($q->rows as $r){$exclude[(int)$r['target_id']]=true;}
        return array_values(array_filter($rows,static function($r)use($exclude){return !isset($exclude[(int)$r['id']]);}));
    }

    private function excludeManualArticleTargets(int $articleId, string $targetType, array $rows): array {
        if(!$rows)return array();
        $table=$targetType==='product'?'article_related_product':'article_related';
        $column=$targetType==='product'?'product_id':'related_id';
        if(!$this->tableExists($table))return $rows;
        $q=$this->db->query("SELECT `".$column."` AS target_id FROM `".DB_PREFIX.$table."` WHERE article_id='".(int)$articleId."'");
        $exclude=array();foreach($q->rows as $r){$exclude[(int)$r['target_id']]=true;}
        return array_values(array_filter($rows,static function($r)use($exclude){return !isset($exclude[(int)$r['id']]);}));
    }

    private function branchCategories(array $categoryIds): array {
        $categoryIds = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if (!$categoryIds) { return array(); }
        $query = $this->db->query("SELECT DISTINCT category_id FROM `" . DB_PREFIX . "category_path` WHERE path_id IN (" . implode(',', $categoryIds) . ") ORDER BY category_id ASC LIMIT 1500");
        $ids = $categoryIds;
        foreach ($query->rows as $row) { $ids[] = (int)$row['category_id']; }
        return array_values(array_unique(array_filter($ids)));
    }

    private function sharedAttributeCounts(array $candidateIds, array $attributeIds): array {
        $candidateIds = array_values(array_unique(array_filter(array_map('intval', $candidateIds))));
        $attributeIds = array_values(array_unique(array_filter(array_map('intval', $attributeIds))));
        if (!$candidateIds || !$attributeIds || !$this->boolSetting('codecart_relation_use_attributes', true)) {
            return array();
        }
        $languageId = (int)$this->config->get('config_language_id');
        $query = $this->db->query("SELECT product_id, COUNT(DISTINCT attribute_id) AS total FROM `" . DB_PREFIX . "product_attribute` WHERE language_id='" . $languageId . "' AND product_id IN (" . implode(',', $candidateIds) . ") AND attribute_id IN (" . implode(',', $attributeIds) . ") GROUP BY product_id");
        $counts = array();
        foreach ($query->rows as $row) { $counts[(int)$row['product_id']] = (int)$row['total']; }
        return $counts;
    }

    private function upsertRelation(string $sourceType, int $sourceId, string $targetType, int $targetId, string $relationType, string $origin, int $score, string $reason): void {
        if ($sourceId < 1 || $targetId < 1 || ($sourceType === $targetType && $sourceId === $targetId)) { return; }
        $score = max(0, min(100, $score));
        $reason = substr($reason, 0, 255);
        $sql = "INSERT INTO `" . DB_PREFIX . "codecart_relation` SET source_type='" . $this->db->escape($sourceType) . "', source_id='" . (int)$sourceId . "', target_type='" . $this->db->escape($targetType) . "', target_id='" . (int)$targetId . "', relation_type='" . $this->db->escape($relationType) . "', origin='" . $this->db->escape($origin) . "', score='" . (int)$score . "', reason='" . $this->db->escape($reason) . "', status='1', date_added=NOW(), date_modified=NOW() ON DUPLICATE KEY UPDATE score=VALUES(score), reason=VALUES(reason), status='1', date_modified=NOW()";
        $this->db->query($sql);
    }

    private function tokens(string $value): array {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES, 'UTF-8');
        $value = mb_strtolower($value, 'UTF-8');
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        $stop = array('для'=>1,'with'=>1,'the'=>1,'and'=>1,'або'=>1,'или'=>1,'та'=>1,'из'=>1,'для'=>1,'від'=>1,'from'=>1,'product'=>1,'товар'=>1);
        $tokens = array();
        foreach ((array)$parts as $part) {
            if (mb_strlen($part, 'UTF-8') < 3 || isset($stop[$part])) { continue; }
            $tokens[$part] = true;
            if (count($tokens) >= 12) { break; }
        }
        return array_keys($tokens);
    }

    private function tokenOverlap(array $a, array $b): int {
        if (!$a || !$b) { return 0; }
        return count(array_intersect($a, $b));
    }

    private function intSetting(string $key, int $default, int $min, int $max): int {
        $value = $this->config->get($key);
        if ($value === null || $value === '') { return $default; }
        return max($min, min($max, (int)$value));
    }

    private function boolSetting(string $key, bool $default): bool {
        $value = $this->config->get($key);
        if ($value === null || $value === '') { return $default; }
        return (bool)$value;
    }

    private function tableExists(string $table): bool {
        if (array_key_exists($table, $this->tableExistsCache)) { return $this->tableExistsCache[$table]; }
        $query = $this->db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='" . $this->db->escape(DB_PREFIX . $table) . "' LIMIT 1");
        $this->tableExistsCache[$table] = (bool)$query->num_rows;
        return $this->tableExistsCache[$table];
    }
}
