<?php
class ControllerExtensionFeedGoogleBase extends Controller {
    private $chunkSize = 500;

    public function index() {
        if (!$this->config->get('feed_google_base_status')) {
            $this->response->setStatusCode(404);
            return;
        }

        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();
        $language_code = isset($this->request->get['lang']) ? (string)$this->request->get['lang'] : (string)$this->config->get('feed_google_base_language');
        if ($language_code === '' || !isset($languages[$language_code]) || empty($languages[$language_code]['status'])) {
            $language_code = (string)$this->config->get('config_language');
        }
        if (isset($languages[$language_code])) {
            $this->config->set('config_language_id', (int)$languages[$language_code]['language_id']);
            $this->config->set('config_language', $language_code);
            if (!$this->config->get('langdir_status')) {
                $this->config->set('codecart_language_prefix_current', (string)($languages[$language_code]['url_prefix'] ?? ''));
            }
            $this->session->data['language'] = $language_code;
        }

        $currency_code = strtoupper(trim((string)$this->config->get('feed_google_base_currency')));
        if ($currency_code === '' || !$this->currency->has($currency_code)) {
            $currency_code = (string)$this->config->get('config_currency');
        }
        if (!$this->currency->has($currency_code)) {
            $currency_code = 'USD';
        }
        $currency_value = $this->currency->getValue($currency_code);
        $decimal_place = max(0, (int)$this->currency->getDecimalPlace($currency_code));

        $this->load->model('extension/feed/google_base');
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        $base_url = trim((string)$this->config->get('config_ssl'));
        if ($base_url === '') $base_url = trim((string)$this->config->get('config_url'));
        $base_url = rtrim($base_url, '/');

        $feedDir = rtrim(DIR_STORAGE, '/\\') . '/cache/codecart/feeds/';
        if (!is_dir($feedDir) && !@mkdir($feedDir, 0775, true) && !is_dir($feedDir)) {
            $this->response->setStatusCode(500);
            return;
        }
        $safeLanguage = preg_replace('/[^a-z0-9_-]/i', '_', $language_code);
        $safeCurrency = preg_replace('/[^A-Z0-9_-]/', '_', $currency_code);
        $target = $feedDir . 'google-merchant-' . $safeLanguage . '-' . $safeCurrency . '.xml';
        $temp = $target . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $handle = @fopen($temp, 'wb');
        if (!$handle) {
            $this->response->setStatusCode(500);
            return;
        }
        $write = function($xml) use ($handle) {
            $xml = (string)$xml;
            $length = strlen($xml);
            $written = 0;
            while ($written < $length) {
                $n = fwrite($handle, substr($xml, $written));
                if ($n === false || $n === 0) { throw new \RuntimeException('Unable to write Merchant feed'); }
                $written += $n;
            }
        };

        try {
            $write('<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
            $write('<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . PHP_EOL);
            $write('<channel>' . PHP_EOL);
            $write('<title>' . $this->xml($this->config->get('config_name')) . '</title>' . PHP_EOL);
            $write('<link>' . $this->xml(html_entity_decode($this->url->link('common/home', '', true), ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '</link>' . PHP_EOL);
            $write('<description>' . $this->xml($this->config->get('config_meta_description')) . '</description>' . PHP_EOL);

        $product_type_cache = array();
        $start = 0;
        do {
            $products = $this->model_extension_feed_google_base->getMerchantProducts($start, $this->chunkSize);
            $product_ids = array();
            foreach ($products as $batch_product) { $product_ids[] = (int)$batch_product['product_id']; }
            $additional_images = $this->model_extension_feed_google_base->getAdditionalImages($product_ids);
            foreach ($products as $product) {
                $image = trim((string)$product['image']);
                if ($image === '') {
                    $image = trim((string)$this->config->get('config_catalog_fallback_image'));
                }
                if ($image === '' || !is_file(DIR_IMAGE . $image)) {
                    continue; // image_link is required by Merchant Center
                }

                $title = $this->plain($product['name']);
                if (utf8_strlen($title) > 150) $title = utf8_substr($title, 0, 150);
                $description = $this->plain($product['description']);
                if (utf8_strlen($description) > 5000) $description = utf8_substr($description, 0, 5000);
                if ($title === '' || $description === '') continue;

                $product_url = $this->url->link('product/product', 'product_id=' . (int)$product['product_id'], true);
                $image_url = $this->model_tool_image->resize($image, 1000, 1000);
                $normal_price = (float)$product['discount'] > 0 ? (float)$product['discount'] : (float)$product['price'];
                $sale_price = (float)$product['special'];
                $normal_price = (float)$this->currency->format($this->tax->calculate($normal_price, (int)$product['tax_class_id']), $currency_code, $currency_value, false);
                if ($sale_price > 0) $sale_price = (float)$this->currency->format($this->tax->calculate($sale_price, (int)$product['tax_class_id']), $currency_code, $currency_value, false);

                $gtin = $this->gtin($product);
                $brand = $this->plain($product['manufacturer']);
                $mpn = $this->plain($product['mpn']);
                $category_id = (int)$product['category_id'];
                // Category mapping is the primary merchant taxonomy source in CodeCart.
                // The legacy product value remains a compatibility fallback for imported/older data.
                $google_category = $this->model_extension_feed_google_base->getGoogleProductCategoryId($category_id);
                if ($google_category === '') {
                    $google_category = preg_replace('/[^0-9]/', '', (string)(isset($product['google_product_category_id']) ? $product['google_product_category_id'] : ''));
                }
                if (!array_key_exists($category_id, $product_type_cache)) {
                    $product_type_cache[$category_id] = $this->model_extension_feed_google_base->getProductType($category_id);
                }
                $product_type = $product_type_cache[$category_id];

                $availability = (int)$product['quantity'] > 0 ? 'in_stock' : ($this->config->get('config_stock_checkout') ? 'backorder' : 'out_of_stock');

                $write('<item>' . PHP_EOL);
                $write('<g:id>' . (int)$product['product_id'] . '</g:id>' . PHP_EOL);
                $write('<title>' . $this->cdata($title) . '</title>' . PHP_EOL);
                $write('<link>' . $this->xml($product_url) . '</link>' . PHP_EOL);
                $write('<description>' . $this->cdata($description) . '</description>' . PHP_EOL);
                $write('<g:image_link>' . $this->xml($image_url) . '</g:image_link>' . PHP_EOL);
                if (!empty($additional_images[(int)$product['product_id']])) {
                    $seen_images = array($image => true);
                    $additional_count = 0;
                    foreach ($additional_images[(int)$product['product_id']] as $additional_image) {
                        $additional_image = trim((string)$additional_image);
                        if ($additional_image === '' || isset($seen_images[$additional_image]) || !is_file(DIR_IMAGE . $additional_image)) continue;
                        $seen_images[$additional_image] = true;
                        $additional_url = $this->model_tool_image->resize($additional_image, 1000, 1000);
                        if ($additional_url === '') continue;
                        $write('<g:additional_image_link>' . $this->xml($additional_url) . '</g:additional_image_link>' . PHP_EOL);
                        if (++$additional_count >= 10) break;
                    }
                }
                $write('<g:availability>' . $availability . '</g:availability>' . PHP_EOL);
                $write('<g:condition>new</g:condition>' . PHP_EOL);
                $write('<g:price>' . number_format($normal_price, $decimal_place, '.', '') . ' ' . $this->xml($currency_code) . '</g:price>' . PHP_EOL);
                if ($sale_price > 0 && $sale_price < $normal_price) {
                    $write('<g:sale_price>' . number_format($sale_price, $decimal_place, '.', '') . ' ' . $this->xml($currency_code) . '</g:sale_price>' . PHP_EOL);
                }
                if ($brand !== '') $write('<g:brand>' . $this->cdata($brand) . '</g:brand>' . PHP_EOL);
                if ($gtin !== '') $write('<g:gtin>' . $this->xml($gtin) . '</g:gtin>' . PHP_EOL);
                if ($mpn !== '') $write('<g:mpn>' . $this->cdata($mpn) . '</g:mpn>' . PHP_EOL);
                if ($gtin === '' && $mpn === '') $write('<g:identifier_exists>no</g:identifier_exists>' . PHP_EOL);
                if ($google_category !== '') $write('<g:google_product_category>' . $this->xml($google_category) . '</g:google_product_category>' . PHP_EOL);
                if ($product_type !== '') $write('<g:product_type>' . $this->cdata($product_type) . '</g:product_type>' . PHP_EOL);
                $write('</item>' . PHP_EOL);
            }
            $count = count($products);
            $start += $this->chunkSize;
        } while ($count === $this->chunkSize);

            $write('</channel>' . PHP_EOL . '</rss>');
            fflush($handle);
            if (function_exists('fsync')) { @fsync($handle); }
            fclose($handle);
            $handle = null;
            if (!@rename($temp, $target)) {
                @unlink($temp);
                throw new \RuntimeException('Unable to publish Merchant feed');
            }
        } catch (\Throwable $e) {
            if (is_resource($handle)) { fclose($handle); }
            @unlink($temp);
            $this->log->write('Google Merchant feed generation failed: ' . $e->getMessage());
            if (!is_file($target)) {
                $this->response->setStatusCode(500);
                return;
            }
        }

        $this->response->addHeader('Content-Type: application/xml; charset=UTF-8');
        $this->response->addHeader('X-Content-Type-Options: nosniff');
        $this->response->addHeader('Content-Length: ' . filesize($target));
        $this->streamFile($target);
    }

    private function streamFile($file) {
        if (!is_file($file) || !is_readable($file)) {
            $this->response->setStatusCode(500);
            return;
        }
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            header('Content-Length: ' . filesize($file));
        }
        $handle = fopen($file, 'rb');
        if (!$handle) {
            $this->response->setStatusCode(500);
            return;
        }
        while (!feof($handle)) {
            $chunk = fread($handle, 1048576);
            if ($chunk === false) { break; }
            echo $chunk;
        }
        fclose($handle);
        exit;
    }

    private function plain($value) {
        $value = (string)$value;
        for ($i = 0; $i < 2; $i++) {
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $value) break;
            $value = $decoded;
        }
        // Preserve word boundaries when rich product HTML is flattened for Merchant Center.
        $value = preg_replace('#</?(?:p|div|h[1-6]|li|ul|ol|br|blockquote|tr|td|th|section|article)[^>]*>#iu', ' ', $value);
        $value = strip_tags($value);
        $value = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F\\x7F]/u', ' ', $value);
        $value = preg_replace('/\\s+/u', ' ', $value);
        return trim((string)$value);
    }

    private function gtin(array $product) {
        foreach (array('ean', 'upc', 'jan', 'isbn') as $key) {
            $value = preg_replace('/[^0-9]/', '', (string)$product[$key]);
            if (in_array(strlen($value), array(8, 12, 13, 14), true)) return $value;
        }
        return '';
    }

    private function xml($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function cdata($value) {
        return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', (string)$value) . ']]>';
    }
}
