<?php
class ControllerExtensionModuleCodeCartAiReady extends Controller {
    public function index(): void {
        if (!$this->config->get('codecart_webmcp_status') || (int)$this->config->get('config_store_id') !== 0) {
            $this->respond(404, array('error' => 'unavailable'));
            return;
        }

        if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'GET') {
            $this->response->addHeader('Allow: GET');
            $this->respond(405, array('error' => 'method_not_allowed'));
            return;
        }

        if (($this->request->server['HTTP_SEC_FETCH_SITE'] ?? 'same-origin') !== 'same-origin') {
            $this->respond(403, array('error' => 'forbidden'));
            return;
        }

        $input = $this->request->get['q'] ?? '';
        $query = is_string($input) ? html_entity_decode($input, ENT_QUOTES, 'UTF-8') : '';
        $query = trim(preg_replace('/\s+/u', ' ', $query) ?? '');
        if (!preg_match('/\A.{2,64}\z/us', $query) || !preg_match('/[\p{L}\p{N}]/u', $query)) {
            $this->respond(400, array('error' => 'invalid_query'));
            return;
        }

        $guard = (new \CodeCart\Core\SpamService($this->registry))->consume('webmcp_search', 60, 60, 0);
        if (!$guard['allowed']) {
            $this->response->addHeader('Retry-After: ' . (int)$guard['retry_after']);
            $this->respond(429, array('error' => 'rate_limited'));
            return;
        }

        $this->load->model('catalog/product');
        $products = $this->model_catalog_product->getProducts(array(
            'filter_name' => $query,
            'sort' => 'p.sort_order',
            'order' => 'ASC',
            'start' => 0,
            'limit' => 8
        ));
        $items = array();
        $secure = codecart_is_https((array)$this->request->server);
        foreach (array_slice($products, 0, 8) as $product) {
            $items[] = array(
                'name' => trim(strip_tags(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'))),
                'url' => str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . (int)$product['product_id'], $secure))
            );
        }

        $this->respond(200, array('products' => $items));
    }

    private function respond(int $status, array $payload): void {
        $this->response->setStatusCode($status);
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Cache-Control: no-store');
        $this->response->addHeader('X-Content-Type-Options: nosniff');
        $this->response->setOutput(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
    }
}
