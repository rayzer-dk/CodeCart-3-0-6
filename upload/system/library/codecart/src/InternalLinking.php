<?php
namespace CodeCart\Core;

final class InternalLinking {
    private $registry;
    private $db;
    private $config;
    private $url;

    public function __construct($registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');
        $this->url = $registry->get('url');
    }

    public function enabled(): bool {
        return (bool)(int)$this->config->get('codecart_internal_linking_status');
    }

    public function product(int $productId, int $categoryId = 0, int $manufacturerId = 0, int $limit = 8): array {
        if (!$this->enabled() || $productId < 1) { return array(); }
        $items = array();
        $languageId = (int)$this->config->get('config_language_id');
        $storeId = (int)$this->config->get('config_store_id');
        $limit = max(1, min(12, $limit));

        if ($categoryId < 1) {
            $categoryId = $this->productCategoryId($productId);
        }

        if ($categoryId > 0) {
            $q = $this->safeQuery("SELECT cd.name FROM `" . DB_PREFIX . "category_description` cd JOIN `" . DB_PREFIX . "category_to_store` c2s ON c2s.category_id=cd.category_id WHERE cd.category_id='".(int)$categoryId."' AND cd.language_id='".$languageId."' AND c2s.store_id='".$storeId."' LIMIT 1");
            if ($q && $q->num_rows) { $this->push($items, 'category:' . $categoryId, $q->row['name'], $this->url->link('product/category','path='.(int)$categoryId), 'category'); }
        }
        if ($manufacturerId > 0) {
            $q = $this->safeQuery("SELECT name FROM `" . DB_PREFIX . "manufacturer` WHERE manufacturer_id='".(int)$manufacturerId."' LIMIT 1");
            if ($q && $q->num_rows) { $this->push($items, 'manufacturer:' . $manufacturerId, $q->row['name'], $this->url->link('product/manufacturer/info','manufacturer_id='.(int)$manufacturerId), 'manufacturer'); }
        }
        if ($this->tableExists('article_related_product') && $this->tableExists('article_description') && $this->tableExists('article_to_store')) {
            $q = $this->safeQuery("SELECT a.article_id, ad.name FROM `".DB_PREFIX."article_related_product` rp JOIN `".DB_PREFIX."article` a ON a.article_id=rp.article_id JOIN `".DB_PREFIX."article_description` ad ON ad.article_id=a.article_id JOIN `".DB_PREFIX."article_to_store` a2s ON a2s.article_id=a.article_id WHERE rp.product_id='".(int)$productId."' AND ad.language_id='".$languageId."' AND a2s.store_id='".$storeId."' AND a.status='1' AND a.date_available<=NOW() ORDER BY a.sort_order ASC,a.article_id DESC LIMIT ".$limit);
            if ($q) { foreach ($q->rows as $row) { $this->push($items, 'article:' . (int)$row['article_id'], $row['name'], $this->url->link('blog/article','article_id='.(int)$row['article_id']), 'article'); } }
        }
        return array_slice(array_values($items), 0, $limit);
    }

    public function category(int $categoryId, int $limit = 8): array {
        if (!$this->enabled() || $categoryId < 1) { return array(); }
        $items = array(); $languageId=(int)$this->config->get('config_language_id'); $storeId=(int)$this->config->get('config_store_id'); $limit=max(1,min(12,$limit));
        $q=$this->safeQuery("SELECT c.category_id,cd.name FROM `".DB_PREFIX."category` c JOIN `".DB_PREFIX."category_description` cd ON cd.category_id=c.category_id JOIN `".DB_PREFIX."category_to_store` c2s ON c2s.category_id=c.category_id WHERE c.parent_id='".(int)$categoryId."' AND c.status='1' AND cd.language_id='".$languageId."' AND c2s.store_id='".$storeId."' ORDER BY c.sort_order ASC,LCASE(cd.name) ASC LIMIT ".$limit);
        if($q){foreach($q->rows as $row){$this->push($items,'category:'.(int)$row['category_id'],$row['name'],$this->url->link('product/category','path='.(int)$categoryId.'_'.(int)$row['category_id']),'category');}}
        if($this->tableExists('article_related_wb')&&$this->tableExists('article_description')&&$this->tableExists('article_to_store')){
            $remaining=max(0,$limit-count($items));
            if($remaining){$q=$this->safeQuery("SELECT a.article_id,ad.name FROM `".DB_PREFIX."article_related_wb` r JOIN `".DB_PREFIX."article` a ON a.article_id=r.article_id JOIN `".DB_PREFIX."article_description` ad ON ad.article_id=a.article_id JOIN `".DB_PREFIX."article_to_store` a2s ON a2s.article_id=a.article_id WHERE r.category_id='".(int)$categoryId."' AND ad.language_id='".$languageId."' AND a2s.store_id='".$storeId."' AND a.status='1' AND a.date_available<=NOW() ORDER BY a.sort_order ASC,a.article_id DESC LIMIT ".$remaining); if($q){foreach($q->rows as $row){$this->push($items,'article:'.(int)$row['article_id'],$row['name'],$this->url->link('blog/article','article_id='.(int)$row['article_id']),'article');}}}
        }
        return array_slice(array_values($items),0,$limit);
    }

    public function article(int $articleId, int $limit = 8): array {
        if(!$this->enabled()||$articleId<1){return array();}
        $items=array();$languageId=(int)$this->config->get('config_language_id');$storeId=(int)$this->config->get('config_store_id');$limit=max(1,min(12,$limit));
        if($this->tableExists('article_related_product')){$q=$this->safeQuery("SELECT p.product_id,pd.name FROM `".DB_PREFIX."article_related_product` r JOIN `".DB_PREFIX."product` p ON p.product_id=r.product_id JOIN `".DB_PREFIX."product_description` pd ON pd.product_id=p.product_id JOIN `".DB_PREFIX."product_to_store` p2s ON p2s.product_id=p.product_id WHERE r.article_id='".(int)$articleId."' AND pd.language_id='".$languageId."' AND p2s.store_id='".$storeId."' AND p.status='1' AND p.date_available<=NOW() ORDER BY p.sort_order ASC,LCASE(pd.name) ASC LIMIT ".$limit);if($q){foreach($q->rows as $row){$this->push($items,'product:'.(int)$row['product_id'],$row['name'],$this->url->link('product/product','product_id='.(int)$row['product_id']),'product');}}}
        if(count($items)<$limit&&$this->tableExists('article_related')){$remaining=$limit-count($items);$q=$this->safeQuery("SELECT a.article_id,ad.name FROM `".DB_PREFIX."article_related` r JOIN `".DB_PREFIX."article` a ON a.article_id=r.related_id JOIN `".DB_PREFIX."article_description` ad ON ad.article_id=a.article_id JOIN `".DB_PREFIX."article_to_store` a2s ON a2s.article_id=a.article_id WHERE r.article_id='".(int)$articleId."' AND ad.language_id='".$languageId."' AND a2s.store_id='".$storeId."' AND a.status='1' AND a.date_available<=NOW() ORDER BY a.sort_order ASC,a.article_id DESC LIMIT ".$remaining);if($q){foreach($q->rows as $row){$this->push($items,'article:'.(int)$row['article_id'],$row['name'],$this->url->link('blog/article','article_id='.(int)$row['article_id']),'article');}}}
        return array_slice(array_values($items),0,$limit);
    }

    private function productCategoryId(int $productId): int {
        if ($productId < 1 || !$this->tableExists('product_to_category')) { return 0; }
        $q = $this->safeQuery("SELECT category_id FROM `" . DB_PREFIX . "product_to_category` WHERE product_id='" . (int)$productId . "' ORDER BY main_category DESC, category_id ASC LIMIT 1");
        if ($q && $q->num_rows) { return (int)$q->row['category_id']; }
        return 0;
    }

    private function push(array &$items,string $key,string $title,string $href,string $type): void { $title=trim(strip_tags(html_entity_decode($title,ENT_QUOTES,'UTF-8'))); if($title===''||$href===''){return;} $items[$key]=array('title'=>$title,'href'=>$href,'type'=>$type); }
    private function tableExists(string $table): bool { try{$q=$this->db->query("SHOW TABLES LIKE '".$this->db->escape(DB_PREFIX.$table)."'");return (bool)$q->num_rows;}catch(\Throwable $e){return false;} }
    private function safeQuery(string $sql){try{return $this->db->query($sql);}catch(\Throwable $e){return null;}}
}
