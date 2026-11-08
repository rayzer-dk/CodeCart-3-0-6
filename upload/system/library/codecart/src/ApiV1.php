<?php
namespace CodeCart\Core;

final class ApiV1 {
    private $registry;
    public function __construct($registry) { $this->registry = $registry; }

    public function enabled(): bool { return (bool)$this->registry->get('config')->get('codecart_api_v1_status'); }

    public function respond($response, array $payload, int $status = 200): void {
        $codes = array(200=>'200 OK',400=>'400 Bad Request',403=>'403 Forbidden',404=>'404 Not Found',429=>'429 Too Many Requests',500=>'500 Internal Server Error',503=>'503 Service Unavailable');
        $response->addHeader('Content-Type: application/json; charset=utf-8');
        $response->addHeader('Cache-Control: no-store, max-age=0');
        $response->addHeader('X-Content-Type-Options: nosniff');
        if (isset($codes[$status])) $response->addHeader('HTTP/1.1 ' . $codes[$status]);
        $response->setOutput(json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }

    public function disabled($response): void {
        $this->respond($response, array('ok'=>false,'error'=>array('code'=>'api_disabled','message'=>'CodeCart PRO API v1 is disabled.')), 403);
    }

    public function allowRequest(): bool {
        $request = $this->registry->get('request');
        $ip = codecart_client_ip($request ? (array)$request->server : array());
        $bucket = (int)floor(time() / 60);
        $dir = rtrim(DIR_CACHE, '/\\') . DIRECTORY_SEPARATOR . 'codecart-api-rate';
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) { return true; }
        $file = $dir . DIRECTORY_SEPARATOR . hash('sha256', ($ip !== '' ? $ip : 'unknown') . '|' . $bucket) . '.json';
        $fp = @fopen($file, 'c+');
        if (!$fp) { return true; }
        $allowed = true;
        $count = 0;
        if (@flock($fp, LOCK_EX)) {
            $data = json_decode((string)stream_get_contents($fp), true);
            $count = (is_array($data) && isset($data['count']) ? (int)$data['count'] : 0) + 1;
            $allowed = $count <= 120;
            ftruncate($fp, 0); rewind($fp);
            fwrite($fp, json_encode(array('count'=>$count,'expires'=>($bucket+1)*60), JSON_UNESCAPED_SLASHES));
            fflush($fp); @flock($fp, LOCK_UN);
        }
        fclose($fp);
        if ($count === 1) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: array() as $old) {
                if (@filemtime($old) < time() - 120) { @unlink($old); }
            }
        }
        return $allowed;
    }

    public function publicProduct(array $product, int $mainCategoryId = 0): array {
        $config = $this->registry->get('config');
        $customer = $this->registry->get('customer');
        $showPrice = !$config->get('config_customer_price') || ($customer && $customer->isLogged());
        $data = array(
            'product_id' => isset($product['product_id']) ? (int)$product['product_id'] : 0,
            'name' => isset($product['name']) ? (string)$product['name'] : '',
            'model' => isset($product['model']) ? (string)$product['model'] : '',
            'sku' => isset($product['sku']) ? (string)$product['sku'] : '',
            'quantity' => $config->get('config_stock_display') && isset($product['quantity']) ? (int)$product['quantity'] : null,
            'stock_status' => isset($product['stock_status']) ? (string)$product['stock_status'] : '',
            'manufacturer_id' => isset($product['manufacturer_id']) ? (int)$product['manufacturer_id'] : 0,
            'manufacturer' => isset($product['manufacturer']) ? (string)$product['manufacturer'] : '',
            'price' => $showPrice && isset($product['price']) ? (float)$product['price'] : null,
            'special' => $showPrice && isset($product['special']) && $product['special'] !== '' ? (float)$product['special'] : null,
            'currency' => (string)$config->get('config_currency'),
            'image' => isset($product['image']) ? (string)$product['image'] : '',
            'rating' => isset($product['rating']) ? (float)$product['rating'] : 0.0,
            'reviews' => isset($product['reviews']) ? (int)$product['reviews'] : 0,
            'minimum' => isset($product['minimum']) ? max(1,(int)$product['minimum']) : 1,
            'main_category_id' => max(0,$mainCategoryId)
        );
        if ($data['product_id'] > 0 && $this->registry->has('url')) {
            $data['href'] = $this->registry->get('url')->link('product/product','product_id=' . $data['product_id'],true);
        }
        return $data;
    }
}
