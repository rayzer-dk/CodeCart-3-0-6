<?php
class ControllerApiV1Product extends Controller {
    public function index() {
        $api = $this->registry->get('codecart_api_v1');
        if (!$api->enabled()) { $api->disabled($this->response); return; }
        $productId = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;
        if ($productId < 1) { $api->respond($this->response,array('ok'=>false,'error'=>array('code'=>'invalid_product_id','message'=>'product_id is required.')),400); return; }
        $this->load->model('catalog/product');
        $product = $this->model_catalog_product->getProduct($productId);
        if (!$product) { $api->respond($this->response,array('ok'=>false,'error'=>array('code'=>'not_found','message'=>'Product not found.')),404); return; }
        $compat = $this->registry->get('codecart_compat');
        $events = $this->registry->get('codecart_extension_points');
        $payload = array('product'=>$api->publicProduct($product,$compat->getMainCategoryId($productId)));
        $payload = $events->dispatchSafe('api.v1.product.after', $payload, function($e,$point,$owner){
            if ($this->registry->has('log')) $this->log->write('[CodeCart PRO API] Extension point ' . $point . ' (' . $owner . ') failed: ' . $e->getMessage());
        });
        if (!isset($payload['product']) || !is_array($payload['product'])) $payload = array('product'=>$api->publicProduct($product,$compat->getMainCategoryId($productId)));
        $api->respond($this->response,array('ok'=>true,'data'=>$payload));
    }
}
