<?php
class ControllerExtensionFeedGoogleSitemap extends Controller {
    private $chunkSize = 10000;
    private $sitemapDefaultLanguage = '';
    private $types = array(
        'products',
        'categories',
        'manufacturers',
        'information',
        'blog_categories',
        'blog_articles'
    );

    public function index() {
        if (!$this->config->get('feed_google_sitemap_status')) {
            $this->response->setStatusCode(404);
            return;
        }

        $type = isset($this->request->get['type']) ? (string)$this->request->get['type'] : '';
        $page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $lang = isset($this->request->get['lang']) ? (string)$this->request->get['lang'] : '';

        if ($type !== '' && !in_array($type, $this->types, true)) {
            $this->response->setStatusCode(404);
            return;
        }

        if ($type !== '' && $lang !== '' && !$this->activateLanguage($lang)) {
            $this->response->setStatusCode(404);
            return;
        }
        // Serve the XML directly on both the clean URL and the legacy route URL.
        // A sitemap endpoint must never redirect to itself; this avoids loops with
        // legacy .htaccess/SeoPro rules and reverse-proxy canonicalization.

        $this->response->addHeader('Content-Type: application/xml; charset=UTF-8');
        $this->response->addHeader('X-Content-Type-Options: nosniff');

        if ($type === '') {
            $this->response->setOutput($this->renderIndex());
            return;
        }

        $this->response->setOutput($this->renderUrlset($type, $page));
    }

    private function renderIndex() {
        $this->load->model('localisation/language');
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('catalog/manufacturer');
        $this->load->model('catalog/information');
        $this->load->model('blog/article');
        $this->load->model('blog/category');

        $parts = array();
        $original_language_id = (int)$this->config->get('config_language_id');
        $original_language = (string)$this->config->get('config_language');
        $this->sitemapDefaultLanguage = $original_language;
        $original_prefix = (string)$this->config->get('codecart_language_prefix_current');
        $original_session_language = isset($this->session->data['language']) ? (string)$this->session->data['language'] : null;
        $languages = $this->model_localisation_language->getLanguages();

        foreach ($languages as $code => $language) {
            if (empty($language['status'])) {
                continue;
            }

            $prefix = $this->resolveLanguagePrefix($language, $code, $original_language);
            $this->config->set('config_language_id', (int)$language['language_id']);
            $this->config->set('config_language', $code);
            $this->config->set('codecart_language_prefix_current', $prefix);
            $this->session->data['language'] = $code;

            $this->appendPagedParts($parts, 'products', $this->model_catalog_product->getTotalProductsForSitemap(), $prefix, $code);
            $this->appendPagedParts($parts, 'categories', $this->model_catalog_category->getTotalCategoriesForSitemap(), $prefix, $code);
            $this->appendPagedParts($parts, 'manufacturers', $this->model_catalog_manufacturer->getTotalManufacturersForSitemap(), $prefix, $code);
            $this->appendPagedParts($parts, 'information', $this->model_catalog_information->getTotalInformationsForSitemap(), $prefix, $code);
            $this->appendPagedParts($parts, 'blog_categories', $this->model_blog_category->getTotalCategoriesForSitemap(), $prefix, $code);
            $this->appendPagedParts($parts, 'blog_articles', $this->model_blog_article->getTotalArticlesForSitemap(), $prefix, $code);
        }

        $this->config->set('config_language_id', $original_language_id);
        $this->config->set('config_language', $original_language);
        $this->config->set('codecart_language_prefix_current', $original_prefix);
        if ($original_session_language === null) {
            unset($this->session->data['language']);
        } else {
            $this->session->data['language'] = $original_session_language;
        }

        $output = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $output .= '<?xml-stylesheet type="text/xsl" href="' . $this->xml($this->sitemapStylesheetUrl()) . '"?>' . PHP_EOL;
        $output .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($parts as $part) {
            $output .= '  <sitemap><loc>' . $this->xml($this->sitemapUrl($part['type'], $part['page'], $part['prefix'], $part['code'])) . '</loc></sitemap>' . PHP_EOL;
        }

        $output .= '</sitemapindex>';

        return $output;
    }

    private function appendPagedParts(array &$parts, $type, $total, $prefix, $code) {
        $pages = (int)ceil(max(0, (int)$total) / $this->chunkSize);

        for ($page = 1; $page <= $pages; $page++) {
            $parts[] = array(
                'type' => (string)$type,
                'prefix' => (string)$prefix,
                'code' => (string)$code,
                'page' => $page
            );
        }
    }

    private function activateLanguage($code) {
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();

        if (!isset($languages[$code]) || empty($languages[$code]['status'])) {
            return false;
        }

        $this->config->set('config_language_id', (int)$languages[$code]['language_id']);
        $this->config->set('config_language', $code);
        $this->session->data['language'] = $code;
        if (!$this->config->get('langdir_status')) {
            $this->config->set('codecart_language_prefix_current', isset($languages[$code]['url_prefix']) ? (string)$languages[$code]['url_prefix'] : '');
        }

        return true;
    }

    private function sitemapStylesheetUrl() {
        $base = trim((string)$this->config->get('config_ssl'));
        if ($base === '') { $base = trim((string)$this->config->get('config_url')); }
        return rtrim($base, '/') . '/sitemap.xsl';
    }

    private function normalizeLanguagePrefix($prefix) {
        $prefix = strtolower(trim((string)$prefix, " /\t\n\r\0\x0B"));
        return ($prefix !== '' && preg_match('/^[a-z0-9][a-z0-9-]{0,31}$/', $prefix)) ? $prefix : '';
    }

    private function resolveLanguagePrefix(array $language, $code, $default_code) {
        if ($this->config->get('langdir_status')) {
            if ($this->config->get('langdir_off') && (string)$code === (string)$default_code) {
                return '';
            }

            $dirs = $this->config->get('langdir_dir');
            if (!is_array($dirs)) {
                $dirs = (array)$dirs;
            }
            $language_id = isset($language['language_id']) ? (int)$language['language_id'] : 0;
            return $this->normalizeLanguagePrefix(isset($dirs[$language_id]) ? $dirs[$language_id] : '');
        }

        return $this->normalizeLanguagePrefix(isset($language['url_prefix']) ? $language['url_prefix'] : '');
    }

    private function sitemapUrl($type = '', $page = 1, $prefix = '', $code = '') {
        $base = trim((string)$this->config->get('config_ssl'));
        if ($base === '') {
            $base = trim((string)$this->config->get('config_url'));
        }
        $base = rtrim($base, '/');

        if ($type === '') {
            return $base . '/sitemap.xml';
        }

        $prefix = $this->normalizeLanguagePrefix($prefix);
        $slug = str_replace('_', '-', (string)$type);
        $page = max(1, (int)$page);
        $clean_folder_routing = (bool)$this->config->get('codecart_language_prefix_enabled') || (bool)$this->config->get('langdir_status');
        $root_language = (string)$code !== '' && (string)$code === (string)$this->sitemapDefaultLanguage;
        $language_slug = strtolower(trim((string)$code));
        $language_slug = preg_replace('/[^a-z0-9-]+/', '-', $language_slug);
        $language_slug = trim((string)$language_slug, '-');
        if ($language_slug === '') {
            $language_slug = 'default';
        }

        // Every sitemap URL contains the language code explicitly. Language folders,
        // when enabled, are still respected. This makes multilingual sitemap indexes
        // unambiguous while the old sitemap-products-1.xml endpoint stays a legacy alias.
        if ($clean_folder_routing && ($prefix !== '' || $root_language)) {
            return $base . ($prefix !== '' ? '/' . rawurlencode($prefix) : '') . '/sitemap-' . $slug . '-' . rawurlencode($language_slug) . '-' . $page . '.xml';
        }

        return $base . '/sitemap-' . $slug . '-' . rawurlencode($language_slug) . '-' . $page . '.xml';
    }

    private function renderUrlset($type, $page) {
        $output = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $output .= '<?xml-stylesheet type="text/xsl" href="' . $this->xml($this->sitemapStylesheetUrl()) . '"?>' . PHP_EOL;
        $output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . PHP_EOL;

        switch ($type) {
            case 'products':
                $output .= $this->renderProducts($page);
                break;
            case 'categories':
                $output .= $this->renderCategories($page);
                break;
            case 'manufacturers':
                $output .= $this->renderManufacturers($page);
                break;
            case 'information':
                $output .= $this->renderInformation($page);
                break;
            case 'blog_categories':
                $output .= $this->renderBlogCategories($page);
                break;
            case 'blog_articles':
                $output .= $this->renderBlogArticles($page);
                break;
        }

        $output .= '</urlset>';
        return $output;
    }

    private function renderProducts($page) {
        $this->load->model('catalog/product');
        $start = (max(1, (int)$page) - 1) * $this->chunkSize;
        $products = $this->model_catalog_product->getProductsForSitemap($start, $this->chunkSize);
        $output = '';

        foreach ($products as $product) {
            if ($this->isNoindex($product)) {
                continue;
            }

            $output .= $this->urlEntry(
                $this->url->link('product/product', 'product_id=' . (int)$product['product_id'], true),
                isset($product['date_modified']) ? $product['date_modified'] : null,
                isset($product['image']) ? $product['image'] : ''
            );
        }

        return $output;
    }

    private function renderCategories($page) {
        $this->load->model('catalog/category');
        $start = (max(1, (int)$page) - 1) * $this->chunkSize;
        $output = '';

        foreach ($this->model_catalog_category->getCategoriesForSitemap($start, $this->chunkSize) as $category) {
            if ($this->isNoindex($category)) {
                continue;
            }

            $output .= $this->urlEntry(
                $this->url->link('product/category', 'path=' . (int)$category['category_id'], true),
                isset($category['date_modified']) ? $category['date_modified'] : null
            );
        }

        return $output;
    }

    private function renderManufacturers($page) {
        $this->load->model('catalog/manufacturer');
        $start = (max(1, (int)$page) - 1) * $this->chunkSize;
        $output = '';

        foreach ($this->model_catalog_manufacturer->getManufacturersForSitemap($start, $this->chunkSize) as $manufacturer) {
            if ($this->isNoindex($manufacturer)) {
                continue;
            }

            $output .= $this->urlEntry(
                $this->url->link('product/manufacturer/info', 'manufacturer_id=' . (int)$manufacturer['manufacturer_id'], true)
            );
        }

        return $output;
    }

    private function renderInformation($page) {
        $this->load->model('catalog/information');
        $start = (max(1, (int)$page) - 1) * $this->chunkSize;
        $output = '';

        foreach ($this->model_catalog_information->getInformationsForSitemap($start, $this->chunkSize) as $information) {
            if ($this->isNoindex($information)) {
                continue;
            }

            $output .= $this->urlEntry(
                $this->url->link('information/information', 'information_id=' . (int)$information['information_id'], true)
            );
        }

        return $output;
    }

    private function renderBlogCategories($page) {
        $this->load->model('blog/category');
        $start = (max(1, (int)$page) - 1) * $this->chunkSize;
        $output = '';

        foreach ($this->model_blog_category->getCategoriesForSitemap($start, $this->chunkSize) as $category) {
            if ($this->isNoindex($category)) {
                continue;
            }

            $output .= $this->urlEntry(
                $this->url->link('blog/category', 'blog_category_id=' . (int)$category['blog_category_id'], true),
                isset($category['date_modified']) ? $category['date_modified'] : null
            );
        }

        return $output;
    }

    private function renderBlogArticles($page) {
        $this->load->model('blog/article');
        $start = (max(1, (int)$page) - 1) * $this->chunkSize;
        $output = '';

        foreach ($this->model_blog_article->getArticlesForSitemap($start, $this->chunkSize) as $article) {
            if ($this->isNoindex($article)) {
                continue;
            }

            $output .= $this->urlEntry(
                $this->url->link('blog/article', 'article_id=' . (int)$article['article_id'], true),
                isset($article['date_modified']) ? $article['date_modified'] : null
            );
        }

        return $output;
    }

    private function isNoindex(array $row) {
        // ocStore compatibility: historical `noindex` field is inverted: 1 = indexing allowed, 0 = noindex.
        return $this->config->get('config_noindex_status') && isset($row['noindex']) && (int)$row['noindex'] <= 0;
    }

    private function urlEntry($url, $lastmod = null, $image = '') {
        $url = html_entity_decode((string)$url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $output = '  <url>' . PHP_EOL;
        $output .= '    <loc>' . $this->xml($url) . '</loc>' . PHP_EOL;

        if ($lastmod && strtotime($lastmod) > 1) {
            $output .= '    <lastmod>' . date('c', strtotime($lastmod)) . '</lastmod>' . PHP_EOL;
        }

        $imageUrl = $this->imageUrl($image);
        if ($imageUrl) {
            $output .= '    <image:image>' . PHP_EOL;
            $output .= '      <image:loc>' . $this->xml($imageUrl) . '</image:loc>' . PHP_EOL;
            $output .= '    </image:image>' . PHP_EOL;
        }

        $output .= '  </url>' . PHP_EOL;
        return $output;
    }

    private function imageUrl($image) {
        $image = str_replace('\\', '/', trim((string)$image));

        if ($image === '' || strpos('/' . $image . '/', '/../') !== false) {
            return '';
        }

        $secure = function_exists('codecart_is_https') ? codecart_is_https((array)$this->request->server) : (!empty($this->request->server['HTTPS']) && $this->request->server['HTTPS'] !== 'off');
        $base = $secure ? $this->config->get('config_ssl') : $this->config->get('config_url');
        $segments = array_filter(explode('/', ltrim($image, '/')), 'strlen');
        $segments = array_map('rawurlencode', $segments);

        return rtrim($base, '/') . '/image/' . implode('/', $segments);
    }

    private function xml($value) {
        return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
