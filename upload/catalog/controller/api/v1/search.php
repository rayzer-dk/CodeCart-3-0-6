<?php
class ControllerApiV1Search extends Controller {
    public function index() {
        $api = $this->registry->get('codecart_api_v1');
        if (!$api->enabled()) { $api->disabled($this->response); return; }
        if (!$this->allowRequest()) {
            $this->response->addHeader('Retry-After: 60');
            $api->respond($this->response,array('ok'=>false,'error'=>array('code'=>'rate_limit','message'=>'Too many API requests.')),429);
            return;
        }
        $q = isset($this->request->get['q']) ? trim((string)$this->request->get['q']) : '';
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 20;
        $limit = max(1, min(50, $limit));
        $start = isset($this->request->get['start']) ? max(0, (int)$this->request->get['start']) : 0;
        $categoryId = isset($this->request->get['category_id']) ? max(0, (int)$this->request->get['category_id']) : 0;
        if ($q === '' && $categoryId < 1) { $api->respond($this->response,array('ok'=>false,'error'=>array('code'=>'search_criteria_required','message'=>'q or category_id is required.')),400); return; }
        $query = function_exists('mb_substr') ? mb_substr($q,0,200,'UTF-8') : substr($q,0,200);
        $criteria = array('query'=>$query,'start'=>$start,'limit'=>$limit,'category_id'=>$categoryId);
        $events = $this->registry->get('codecart_extension_points');
        $logError = function($e,$point,$owner){ if ($this->registry->has('log')) $this->log->write('[CodeCart PRO API] Extension point ' . $point . ' (' . $owner . ') failed: ' . $e->getMessage()); };
        $criteria = $events->dispatchSafe('api.v1.search.before', $criteria, $logError);
        $criteria['query'] = isset($criteria['query']) ? trim((string)$criteria['query']) : '';
        $criteria['query'] = function_exists('mb_substr') ? mb_substr($criteria['query'],0,200,'UTF-8') : substr($criteria['query'],0,200);
        $criteria['start'] = isset($criteria['start']) ? max(0,(int)$criteria['start']) : 0;
        $criteria['limit'] = isset($criteria['limit']) ? max(1,min(50,(int)$criteria['limit'])) : 20;
        $criteria['category_id'] = isset($criteria['category_id']) ? max(0,(int)$criteria['category_id']) : 0;
        if ($criteria['query'] === '' && $criteria['category_id'] < 1) { $api->respond($this->response,array('ok'=>false,'error'=>array('code'=>'search_criteria_required','message'=>'q or category_id is required.')),400); return; }
        $this->load->model('catalog/product');
        $adapter = $this->registry->get('codecart_search');
        $result = $adapter->search($criteria, function($c) {
            $filter = array('filter_name'=>$c['query'],'start'=>(int)$c['start'],'limit'=>(int)$c['limit']);
            if (!empty($c['category_id'])) $filter['filter_category_id']=(int)$c['category_id'];
            return $this->model_catalog_product->getProducts($filter);
        });
        $compat = $this->registry->get('codecart_compat');
        $rawItems = array_values(is_array($result['items']) ? $result['items'] : array());
        // External providers are optional and may be implemented by third-party code.
        // Enforce the public API limit again even when a provider ignores the criteria.
        if (count($rawItems) > (int)$criteria['limit']) { $rawItems = array_slice($rawItems, 0, (int)$criteria['limit']); }
        $productIds = array();
        foreach ($rawItems as $product) if (is_array($product) && !empty($product['product_id'])) $productIds[] = (int)$product['product_id'];
        $mainCategories = $compat->getMainCategoryIds($productIds);
        $items = array();
        foreach ($rawItems as $product) {
            if (!is_array($product) || empty($product['product_id'])) continue;
            $productId = (int)$product['product_id'];
            $items[] = $api->publicProduct($product,isset($mainCategories[$productId])?(int)$mainCategories[$productId]:0);
        }
        $payload = array('query'=>$criteria['query'],'provider'=>$result['provider'],'fallback'=>$result['fallback'],'count'=>count($items),'items'=>$items);
        $payload = $events->dispatchSafe('api.v1.search.after', $payload, $logError);
        if (!isset($payload['items']) || !is_array($payload['items'])) $payload['items'] = $items;
        $payload['count'] = count($payload['items']);
        $api->respond($this->response,array('ok'=>true,'data'=>$payload));
    }


    private function allowRequest() {
        $ip = isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : 'unknown';
        $bucket = (int)floor(time() / 60);
        $dir = rtrim(DIR_CACHE, '/\\') . DIRECTORY_SEPARATOR . 'codecart-api-rate';
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) { return true; }
        $file = $dir . DIRECTORY_SEPARATOR . hash('sha256', $ip . '|' . $bucket) . '.json';
        $fp = @fopen($file, 'c+');
        if (!$fp) { return true; }
        $allowed = true;
        if (@flock($fp, LOCK_EX)) {
            $raw = stream_get_contents($fp);
            $data = json_decode((string)$raw, true);
            $count = is_array($data) && isset($data['count']) ? (int)$data['count'] : 0;
            $count++;
            $allowed = $count <= 120;
            ftruncate($fp, 0); rewind($fp);
            fwrite($fp, json_encode(array('count'=>$count,'expires'=>($bucket+1)*60), JSON_UNESCAPED_SLASHES));
            fflush($fp); @flock($fp, LOCK_UN);
        }
        fclose($fp);
        return $allowed;
    }
}
