<?php
/**
 * @package		SeoPro
 * @author		Oclabs
 * @copyright	Copyright (c) 2017, Oclabs (https://www.oclabs.pro/)
 * @copyright	Copyright (c) 2021, ocStore (https://ocstore.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 */

// CodeCart/ocStore main category flag is stored in product_to_category.main_category.

class SeoPro {

    private $config;
    private $ajax = false;
    private $request;
    private $registry;
    private $response;
    private $url;
    private $session;
    private $db;
    private $cache;
    private $cat_tree = [];
    private $keywords = [];
    private $queries = [];
    private $product_categories = [];
    private $valide_get_param = [];

    public function __construct($registry) {
        $this->registry = $registry;
        $this->config = $registry->get('config');
        $this->request = $registry->get('request');
        $this->detectAjax();

        // config_seo_url is the master switch. SeoPro must never keep alias
        // detection/canonical redirects alive after SEO URLs are disabled.
        if (!$this->config->get('config_seo_url') || !$this->config->get('config_seo_pro'))
            return;

        $this->session = $registry->get('session');
        $this->response = $registry->get('response');
        $this->url = $registry->get('url');
        $this->db = $registry->get('db');
        $this->cache = $registry->get('cache');
        $this->detectPostfix();
        $this->detectLanguage();
        $this->initHelpers();
        $raw_params = (string)$this->config->get('config_valide_params');
        if ($raw_params !== '') {
            $params = preg_split('/\R+/', $raw_params);
            $params = array_map('trim', is_array($params) ? $params : []);
            $this->valide_get_param = array_values(array_filter($params, 'strlen'));
        }
        
    }

    public function prepareRoute($parts) {

        if (!empty($parts) && is_array($parts)) {
            // A trailing slash produces an empty path segment. It is routing
            // syntax, not a SEO keyword, and must never turn a valid URL into 404.
            $parts = array_values(array_filter($parts, static function ($part) {
                return trim((string)$part) !== '';
            }));

            foreach($parts as $id => $part) {
                $query = null;

                if($this->config->get('config_seopro_lowercase'))
                    $parts[$id] = utf8_strtolower($part);

                if($parts[$id] or $parts[$id] == "") {

                    $query = $this->getQueryByKeyword($parts[$id]);

                    $url = explode('=', (string)$query);

                    if(!empty($url[0])) {

                        if(!in_array($url[0], ['category_id', 'product_id', 'manufacturer_id', 'information_id', 'article_id', 'blog_category_id'])) {
                            return $parts;
                        }

                        if ($url[0] == 'category_id') {
                            if (!isset($this->request->get['path'])) {
                                $this->request->get['path'] = $url[1];
                            } else {
                                $this->request->get['path'] .= '_' . $url[1];
                            }
                        } elseif ($url[0] == 'blog_category_id') {
                            if (!isset($this->request->get['blog_category_id'])) {
                                $this->request->get['blog_category_id'] = $url[1];
                            } else {
                                $this->request->get['blog_category_id'] .= '_' . $url[1];
                            }
                        } elseif (count($url) > 1) {
                            $this->request->get[$url[0]] = $url[1];
                        }
                    }
                }

                unset($parts[$id]);
            }

            if(!$query) {
                $this->request->get['route'] = 'error/not_found';
                return [];
            }
        }

        if (isset($this->request->get['product_id'])) {
            if(isset($this->request->get['path'])) {
                unset($this->request->get['path']);
            };
            $path = $this->getCategoryByProduct($this->request->get['product_id']);
            if ($path) $this->request->get['path'] = $path;
            $this->request->get['route'] = 'product/product';
        } elseif (isset($this->request->get['path'])) {
            $this->request->get['route'] = 'product/category';
        } elseif (isset($this->request->get['manufacturer_id'])) {
            $this->request->get['route'] = 'product/manufacturer/info';
        } elseif (isset($this->request->get['information_id'])) {
            $this->request->get['route'] = 'information/information';
        }

        //blog
        if (isset($this->request->get['article_id'])) {
            if(isset($this->request->get['blog_category_id'])) {
                unset($this->request->get['blog_category_id']);
            };
            $blog_category_path = $this->getBlogPathByArticle($this->request->get['article_id']);
            if ($blog_category_path) $this->request->get['blog_category_id'] = $blog_category_path;
            $this->request->get['route'] = 'blog/article';
        } elseif (isset($this->request->get['blog_category_id'])) {
            $this->request->get['route'] = 'blog/category';
        }
        //end blog
        return $parts;
    }

    public function baseRewrite($data, $language_id) {

        $url = null;
        $postfix = null;
        $language_id = (int)$this->config->get('config_language_id');

        $current_route = isset($data['route']) ? (string)$data['route'] : '';

        switch ($current_route) {
            case 'product/product':
                if (isset($data['product_id'])) {
                    $route = 'product/product';
                    $path = '';
                    $product_id = $data['product_id'];
                    if (isset($data['path']) || $this->config->get('config_seo_url_include_path')) {
                        $path = $this->getCategoryByProduct($product_id);
                    }

                    //start add valide get-param
                    if ($this->valide_get_param) {
                        $valide_get_param_data = [];
                        foreach($this->valide_get_param as $get_param) {
                            if (isset($data[$get_param])) {
                                $valide_get_param_data[$get_param] = $data[$get_param];
                                $this->response->addHeader('X-Robots-Tag: noindex');
                            }
                        };
                    }
                    //end add valide get-param

                    unset($data);
                    $data['route'] = $route;

                    if ($path && $this->config->get('config_seo_url_include_path')) {
                        $data['path'] = $path;
                    }

                    $data['product_id'] = $product_id;
                    //start add valide get-param
                    if ($this->valide_get_param) {
                        $data = array_merge($data, $valide_get_param_data);
                    }
                    //end add valide get-param
                }
                break;
            //blog

            case 'blog/article':
                if (isset($data['article_id'])) {
                    $route = 'blog/article';
                    $blog_path = '';
                    $article_id = $data['article_id'];

                    if (isset($data['blog_category_id'])) {
                        $blog_path = $this->getBlogPathByArticle($article_id);
                    }

                    //start add valide get-param
                    if ($this->valide_get_param) {
                        $valide_get_param_data = [];
                        foreach($this->valide_get_param as $get_param) {
                            if (isset($data[$get_param])) {
                                $valide_get_param_data[$get_param] = $data[$get_param];
                                /*
                                 * add x-robot-tag noindex
                                 * https://developers.google.com/search/reference/robots_meta_tag?hl=en
                                 */
                                $this->response->addHeader('X-Robots-Tag: noindex');
                            }
                        };
                    }
                    //end add valide get-param
                    unset($data);
                    $data['route'] = $route;

                    if ($blog_path && $this->config->get('config_seo_url_include_path')) {
                        $data['blog_category_id'] = $blog_path;
                    }

                    $data['article_id'] = $article_id;

                    if ($this->valide_get_param) {
                        $data = array_merge($data, $valide_get_param_data);
                    }
                }
                break;

            //blog
            case 'product/category':
                if (isset($data['path'])) {
                    $category = explode('_', $data['path']);
                    $category = end($category);
                    unset($data['information_id']);
                    $data['path'] = $this->getPathByCategory($category);
                }
                break;

            case 'blog/article/review':
                return [$url, $data, $postfix];
                break;
            case 'product/product/review':
                return [$url, $data, $postfix];
                break;
            case 'information/information/info':
            case 'product/manufacturer/info':
                break;
            case 'information/information/agree':
                return [$url, $data, $postfix];
                break;
            default:
                break;
        }

        $queries = [];

        $route = '';
        if (isset($data['route'])) {
            $route = $data['route'];
            unset($data['route']);
        }

        foreach ($data as $key => $value) {

            switch ($key) {
                case 'product_id':
                    $product_id = (int)$value;
                    $queries[] = 'product_id=' . $product_id;
                    $postfix = true;
                    unset($data[$key]);
                    break;
                case 'manufacturer_id':
                    $manufacturer_id = (int)$value;
                    $queries[] = 'manufacturer_id=' . $manufacturer_id;
                    $postfix = true;
                    unset($data[$key]);
                    break;
                case 'category_id':
                    $category_id = (int)$value;
                    $queries[] = 'category_id=' . $category_id;
                    unset($data[$key]);
                    break;
                case 'information_id':
                    $information_id = (int)$value;
                    $queries[] = 'information_id=' . $information_id;
                    $postfix = true;
                    unset($data[$key]);
                    break;
                //blog
                case 'blog_category_id':
                    $blog_categories = explode('_', $value);
                    foreach ($blog_categories as $blog_category_id) {
                        $queries[] = 'blog_category_id=' . (int)$blog_category_id;
                    }
                    unset($data[$key]);
                    break;
                case 'article_id':
                    $article_id = (int)$value;
                    $queries[] = 'article_id=' . $article_id;
                    $postfix = true;
                    unset($data[$key]);
                    break;

                //blog
                case 'path':
                    $categories = explode('_', $value);
                    foreach ($categories as $category_id) {
                        $queries[] = 'category_id=' . (int)$category_id;
                    }
                    unset($data[$key]);
                    break;
                default:
                    break;
            }
        }

        if (empty($queries) && $route) {

            $keyword = $this->getKeywordByQuery($route);
            //check url for route
            if($keyword !== null) {
                //common/home
                $url = '';
                if($keyword  !== '')
                    $url = '/' . rawurlencode($keyword);
            }
            //if not exist keyword for route & empty any keyword return route-param for native seo_url class
            $data['route']  = $route;

        } else {

            $rows = [];

            foreach ($queries as $query) {
                $keyword = $this->getKeywordByQuery($query);
                if ($keyword)
                    $rows[] = $keyword;
            }

            if (!empty($rows) && (count($rows) == count($queries))) {
                foreach($rows as $row) {
                    $url .= '/' . rawurlencode($row);
                }
            }
        }

        return [$url, $data, $postfix];
    }

    private function getPath($categories, $category_id, $current_path = []) {

        if(!$current_path)
            $current_path = [(int)$category_id];

        $path = $current_path;

        $parent_id = 0;

        if(isset($categories[$category_id]['parent_id']))
            $parent_id = (int)$categories[$category_id]['parent_id'];

        if($parent_id > 0) {
            $new_path =  array_merge ([$parent_id] , $current_path);
            $path =  $this->getPath($categories, $parent_id, $new_path);
        }

        return $path;
    }


    private function initHelpers() {
        // start category_tree
        if($this->config->get('config_seo_url_cache')){
            $this->cat_tree = $this->cache->get('seopro.cat_tree');
        }

        if(!$this->cat_tree || empty($this->cat_tree)) {

            $this->cat_tree = [];

            $all_cat_query = $this->db->query("SELECT category_id, parent_id FROM " . DB_PREFIX . "category ORDER BY parent_id");

            $allcats = [];
            $categories = [];

            if($all_cat_query->num_rows) {
                $allcats = $all_cat_query->rows;
            };

            foreach ($allcats as $category) {
                $categories[$category['category_id']]['parent_id'] = $category['parent_id'];
            };
            unset ($allcats);

            foreach ($categories as $category_id => $category) {
                $path = $this->getPath($categories, $category_id);
                $this->cat_tree[$category_id]['path'] = $path;

            };

        }
        //end_category_tree

        //keyword_data
        if ($this->config->get('config_seo_url_cache')) {

            $this->keywords = $this->cache->get('seopro.keywords');
            $this->queries = $this->cache->get('seopro.queries');

            if ((!$this->keywords || empty($this->keywords) || !$this->queries || empty($this->queries))) {

                // Initialising arrays if they are false or null for PHP 8.1+ 
                if (!is_array($this->keywords)) {
                    $this->keywords = [];
                }
                if (!is_array($this->queries)) {
                    $this->queries = [];
                }

                $sql_keyword = 'keyword';
                if ($this->config->get('config_seopro_lowercase'))
                    $sql_keyword = 'LCASE(keyword) as '. $sql_keyword;

                $sql = "SELECT " . $sql_keyword . ", query, store_id, language_id FROM " . DB_PREFIX . "seo_url WHERE 1";

                $query = $this->db->query($sql);
                if($query->num_rows) {
                    foreach($query->rows as $row) {
                        $this->keywords[$row['query']][$row['store_id']][$row['language_id']] = $row['keyword'];
                        $this->queries[$row['keyword']][$row['store_id']][$row['language_id']] = $row['query'];
                    }
                }
            }
        }
        //end_keyword_data
    }

    private function detectPostfix() {
        if($this->config->get('config_page_postfix') && isset($this->request->get['_route_'])) {
            $postfix = (string)$this->config->get('config_page_postfix');
            $this->request->get['_route_'] = preg_replace('/' . preg_quote($postfix, '/') . '$/', '', (string)$this->request->get['_route_']);
        }
    }

    private function addpostfix($url) {
        if($this->config->get('config_page_postfix')) {
            $url = rtrim($url, "/") . $this->config->get('config_page_postfix');
        }
        return $url;
    }

    private function getQueryByKeyword($keyword, $language_id = null) {
        $query = null;
        $store_id = (int)$this->config->get('config_store_id');

        if (!$language_id)
            $language_id = (int)$this->config->get('config_language_id');

        $lookup_keyword = trim((string)$keyword);
        if ($this->config->get('config_seopro_lowercase')) {
            $lookup_keyword = utf8_strtolower($lookup_keyword);
        }

        if ($this->config->get('config_seo_url_cache')) {
            // Old SeoPro caches can use a flat keyword=>query shape. Do not
            // interpret scalar string offsets as store/language cache levels.
            if (isset($this->queries[$lookup_keyword]) && is_array($this->queries[$lookup_keyword])
                && isset($this->queries[$lookup_keyword][$store_id]) && is_array($this->queries[$lookup_keyword][$store_id])
                && isset($this->queries[$lookup_keyword][$store_id][$language_id])
                && is_string($this->queries[$lookup_keyword][$store_id][$language_id])) {
                $query = $this->queries[$lookup_keyword][$store_id][$language_id];
            }
        }

        // Cache miss must always fall back to the active seo_url table.
        // Otherwise a stale SeoPro cache can turn a valid language URL into 404.
        if ($query === null || $query === '') {
            $keyword_sql = $this->db->escape($lookup_keyword);
            $keyword_condition = $this->config->get('config_seopro_lowercase')
                ? "LCASE(keyword) = '" . $keyword_sql . "'"
                : "keyword = '" . $keyword_sql . "'";

            $_query = $this->db->query("SELECT query FROM " . DB_PREFIX . "seo_url WHERE " . $keyword_condition . " AND store_id = '" . $store_id . "' AND language_id = '" . (int)$language_id . "' ORDER BY seo_url_id DESC LIMIT 1");
            $query = !empty($_query->row) ? (string)$_query->row['query'] : null;

            // In native prefix mode an unprefixed path belongs to the configured
            // default language. Resolve against it before returning a false 404.
            if (($query === null || $query === '') && $this->config->get('codecart_language_prefix_enabled')
                && trim((string)$this->config->get('codecart_language_prefix_current')) === '') {
                $defaultCode = (string)$this->config->get('config_language');
                if ($defaultCode !== '') {
                    $defaultLanguage = $this->db->query("SELECT language_id FROM " . DB_PREFIX . "language WHERE code = '" . $this->db->escape($defaultCode) . "' AND status = '1' LIMIT 1");
                    if ($defaultLanguage->num_rows && (int)$defaultLanguage->row['language_id'] !== (int)$language_id) {
                        $language_id = (int)$defaultLanguage->row['language_id'];
                        $_query = $this->db->query("SELECT query FROM " . DB_PREFIX . "seo_url WHERE " . $keyword_condition . " AND store_id = '" . $store_id . "' AND language_id = '" . (int)$language_id . "' ORDER BY seo_url_id DESC LIMIT 1");
                        $query = !empty($_query->row) ? (string)$_query->row['query'] : null;

                        // An unprefixed URL is owned by the configured default language.
                        // If a stale session/cache left another language active, align the
                        // runtime as soon as the default-language alias is confirmed.
                        if ($query !== null && $query !== '') {
                            $this->config->set('config_language_id', $language_id);
                            if ($this->session) {
                                $this->session->data['language'] = $defaultCode;
                            }
                            if (class_exists('Language')) {
                                $language = new Language($defaultCode);
                                $language->load($defaultCode);
                                $this->registry->set('language', $language);
                            }
                        }
                    }
                }
            }

            if ($query !== null && $query !== '') {
                if (!isset($this->queries[$lookup_keyword])) $this->queries[$lookup_keyword] = array();
                if (!isset($this->queries[$lookup_keyword][$store_id])) $this->queries[$lookup_keyword][$store_id] = array();
                $this->queries[$lookup_keyword][$store_id][$language_id] = $query;
            }
        }

        return $query;
    }

    private function getKeywordByQuery($query, $language_id = null) {
        $keyword = null;
        $store_id = (int)$this->config->get('config_store_id');

        if (!$language_id)
            $language_id = $this->config->get('config_language_id');

        if ($this->config->get('config_seo_url_cache')) {
            if (isset($this->keywords[$query]) && is_array($this->keywords[$query])
                && isset($this->keywords[$query][$store_id]) && is_array($this->keywords[$query][$store_id])
                && isset($this->keywords[$query][$store_id][$language_id])
                && is_string($this->keywords[$query][$store_id][$language_id])) {
                $keyword = $this->keywords[$query][$store_id][$language_id];
            }
        }

        if ($keyword === null || $keyword === '') {
            $sql_keyword = 'keyword';
            if ($this->config->get('config_seopro_lowercase'))
                $sql_keyword = 'LCASE(keyword) as ' . $sql_keyword;

            $_query = $this->db->query("SELECT " . $sql_keyword . " FROM " . DB_PREFIX . "seo_url WHERE query = '" . $this->db->escape($query) . "' AND store_id = '" . $store_id . "' AND language_id = '" . (int)$language_id . "' ORDER BY seo_url_id DESC LIMIT 1");
            $keyword = !empty($_query->row) ? (string)$_query->row['keyword'] : null;

            if ($keyword !== null && $keyword !== '') {
                if (!isset($this->keywords[$query])) $this->keywords[$query] = array();
                if (!isset($this->keywords[$query][$store_id])) $this->keywords[$query][$store_id] = array();
                $this->keywords[$query][$store_id][$language_id] = $keyword;
            }
        }

        return $keyword;
    }

    public function validate() {

        // break redirect for php-cli-script
        if (php_sapi_name() === 'cli')
            return;

        // XML/feed endpoints must not be canonicalized as HTML pages.
        if (isset($this->request->get['route'])) {
            $route = (string)$this->request->get['route'];

            if ($route === 'error/not_found' || strpos($route, 'extension/feed/') === 0) {
                return;
            }
        }

        if (!empty($this->request->post))
            return;

        if ($this->ajax) {
            $this->response->addHeader('X-Robots-Tag: noindex');
            return;
        }

        if (empty($this->request->get['route']))
            $this->request->get['route'] = 'common/home';


        $uri = isset($this->request->server['REQUEST_URI']) ? (string)$this->request->server['REQUEST_URI'] : '/';
        $route = $this->request->get['route'];

        // page=1 is the base document. Normalize it here instead of relying on
        // web-server-specific rewrite rules; invalid page values are normalized too.
        if (isset($this->request->get['page']) && (float)$this->request->get['page'] <= 1) {
            unset($this->request->get['page']);
        }

        $is_https = function_exists('codecart_is_https') ? codecart_is_https() : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');

        $configured_base = (string)($is_https ? $this->config->get('config_ssl') : $this->config->get('config_url'));
        $parts = parse_url($configured_base);
        if (!is_array($parts) || empty($parts['host'])) {
            return;
        }

        $scheme = !empty($parts['scheme']) ? strtolower((string)$parts['scheme']) : ($is_https ? 'https' : 'http');
        $host = $scheme . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $host .= ':' . (int)$parts['port'];
        }

        // An HTTP origin always serializes the root document with '/'. When
        // config_seopro_addslash is disabled, building the current root as
        // https://host (without '/') makes it differ forever from Url::link(),
        // which returns https://host/. That creates a 301 self-redirect loop.
        // Always separate the origin from REQUEST_URI with exactly one slash;
        // SeoPro still controls trailing slashes for non-root SEO paths.
        $host .= '/';

        $url = str_replace('&amp;', '&', $host . ltrim($uri, '/'));
        $seo = str_replace('&amp;', '&', $this->url->link($route, $this->getQueryString(array('_route_', 'route')), $is_https));

        // Defensive root guard: never redirect '/' to the same origin merely
        // because one representation omitted the syntactic trailing slash.
        if ($uri === '/' && rtrim(rawurldecode($url), '/') === rtrim(rawurldecode($seo), '/')) {
            return;
        }

        if (rawurldecode($url) != rawurldecode($seo)) {
            $this->response->redirect($seo, 301);
        }
    }

    private function detectAjax () {
        if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            $this->ajax = true;
    }

    private function detectLanguage() {

        if ($this->ajax || $this->config->get('codecart_language_prefix_explicit'))
            return;

        $request_language_id = null;
        $request_language_code = '';
        $active_language_id = $this->config->get('config_language_id');

        $keyword = '';
        if (isset($this->request->get['_route_'])) {
            $parts = explode('/', (string)$this->request->get['_route_']);
            foreach ($parts as $_part) {
                if ($_part !== '' && trim((string)$_part) !== '') {
                    $keyword = (string)$_part;
                }
            }
        }

        // URL language prefixes and the active session are authoritative for the
        // storefront root. An empty seo_url keyword must never override / or /<lang>/.
        if ($keyword !== '') {
            $query = $this->db->query("SELECT language_id FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape(trim($keyword)) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY (language_id = '" . (int)$active_language_id . "') DESC, language_id ASC, seo_url_id DESC LIMIT 1");
            if ($query->row) {
                $request_language_id = (int)$query->row['language_id'];

                $query = $this->db->query("SELECT code FROM " . DB_PREFIX . "language WHERE language_id = '" . (int)$request_language_id . "' AND status = '1' LIMIT 1");

                if ($query->row) {
                    $request_language_code = $query->row['code'];
                    $this->session->data['language'] = $request_language_code;
                }
            }
        }

        if (isset($this->session->data['language'])) {
            $query = $this->db->query("SELECT language_id FROM " . DB_PREFIX . "language WHERE code = '" . $this->db->escape((string)$this->session->data['language']) . "' AND status = '1' LIMIT 1");
            if ($query->num_rows) {
                $active_language_id = (int)$query->row['language_id'];
            }
        }

        if($request_language_id  && $request_language_code && $active_language_id != $request_language_id) {
            $language = new Language($request_language_code);
            $language->load($request_language_code);
            $this->config->set('config_language_id', $request_language_id);
            $this->registry->set('language', $language);
        }
    }

    private function getCategoryByProduct($product_id) {

        if ((int)$product_id < 1)
            return false;

        if ($this->config->get('config_seo_url_cache')) {
            $this->product_categories = $this->cache->get('seopro.product_categories');
            if (!is_array($this->product_categories)) {
                $this->product_categories = [];
            }
            if(isset($this->product_categories[$product_id]))
                return $this->product_categories[$product_id];
        }

        $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "' ORDER BY main_category DESC, category_id ASC LIMIT 1");
        $category_id = $this->getPathByCategory($query->num_rows ? (int)$query->row['category_id'] : 0);

        if ($this->config->get('config_seo_url_cache')) {
            if (!is_array($this->product_categories)) {
                $this->product_categories = [];
            }
            $this->product_categories[$product_id] = $category_id;
        }

        return $category_id;
    }

    private function getPathByCategory($category_id) {

        $path = '';

        if ((int)$category_id < 1 && !isset($this->cat_tree[$category_id]))
            return false;

        if (!empty($this->cat_tree[$category_id]['path']) && is_array($this->cat_tree[$category_id]['path'])) {
            $path = implode('_', $this->cat_tree[$category_id]['path']);
        }

        return $path;

    }

    private function getBlogPathByArticle($article_id) {

        if ($article_id < 1)
            return false;

        $query = $this->db->query("SELECT blog_category_id FROM " . DB_PREFIX . "article_to_blog_category WHERE article_id = '" . (int)$article_id . "' ORDER BY main_blog_category DESC LIMIT 1");
        $blog_category_path = $this->getBlogPathByCategory($query->num_rows ? (int)$query->row['blog_category_id'] : 0);

        return $blog_category_path;
    }

    private function getBlogPathByCategory($blog_category_id) {
        $blog_category_id = (int)$blog_category_id;
        if ($blog_category_id < 1)
            return false;

        static $blog_path = [];
        $cache = 'seopro.blog_category.seopath';

        if (!is_array($blog_path)) {
            if ($this->config->get('config_seo_url_cache'))
                $blog_path = $this->cache->get($cache);
            if (!is_array($blog_path))
                $blog_path = [];
        }

        if (!isset($blog_path[$blog_category_id])) {
            $max_level = 10;
            $sql = "SELECT CONCAT_WS('_'";
            for ($i = $max_level-1; $i >= 0; --$i) {
                $sql .= ",t$i.blog_category_id";
            }
            $sql .= ") AS path FROM " . DB_PREFIX . "blog_category t0";
            for ($i = 1; $i < $max_level; ++$i) {
                $sql .= " LEFT JOIN " . DB_PREFIX . "blog_category t$i ON (t$i.blog_category_id = t" . ($i-1) . ".parent_id)";
            }
            $sql .= " WHERE t0.blog_category_id = '" . $blog_category_id . "'";
            $query = $this->db->query($sql);
            $blog_path[$blog_category_id] = $query->num_rows ? $query->row['path'] : false;

            if ($this->config->get('config_seo_url_cache'))
                $this->cache->set($cache, $blog_path);
        }

        return $blog_path[$blog_category_id];
    }

    private function getQueryString($exclude = []) {
        if (!is_array($exclude))
            $exclude = [];

        return urldecode(http_build_query(array_diff_key($this->request->get, array_flip($exclude))));
    }

    public function __destruct() {

        // config_seo_url is the master switch. SeoPro must never keep alias
        // detection/canonical redirects alive after SEO URLs are disabled.
        if (!$this->config->get('config_seo_url') || !$this->config->get('config_seo_pro'))
            return;

        if ($this->config->get('config_seo_url_cache')){
            $this->cache->set('seopro.keywords', $this->keywords);
            $this->cache->set('seopro.queries', $this->queries);
            $this->cache->set('seopro.cat_tree', $this->cat_tree);
            $this->cache->set('seopro.product_categories', $this->product_categories);
        }
    }
}
