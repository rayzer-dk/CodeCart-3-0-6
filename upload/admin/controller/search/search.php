<?php
// * @source See SOURCE.txt for source and other copyright.
// * @license GNU General Public License version 3; see LICENSE.txt

class ControllerSearchSearch extends Controller {
    private $searchLanguageCache = array();

    public function index() {
        if (empty($this->session->data['user_token']) || !$this->user->isLogged()) {
            return '';
        }

        $can_products = $this->user->hasPermission('access', 'catalog/product');
        $can_categories = $this->user->hasPermission('access', 'catalog/category');
        $can_manufacturers = $this->user->hasPermission('access', 'catalog/manufacturer');
        $can_catalog = $can_products || $can_categories || $can_manufacturers;
        $can_customers = $this->user->hasPermission('access', 'customer/customer');
        $can_orders = $this->user->hasPermission('access', 'sale/order');
        $can_information = $this->user->hasPermission('access', 'catalog/information');
        $can_articles = $this->user->hasPermission('access', 'blog/article');
        $can_extensions = $this->user->hasPermission('access', 'marketplace/extension');
        $can_content = $can_information || $can_articles || $can_extensions;

        $can_core_settings = $this->user->hasPermission('access', 'setting/setting');
        $can_theme_settings = $this->user->hasPermission('access', 'extension/theme/codecart');
        $can_settings = $can_core_settings || $can_theme_settings || $this->hasSearchableAdminPagePermission();

        if (!$can_catalog && !$can_customers && !$can_orders && !$can_content && !$can_settings) {
            return '';
        }

        $this->load->language('search/search');

        $data = array();
        $data['text_search_options'] = $this->language->get('text_search_options');
        $data['text_catalog'] = $this->language->get('text_catalog');
        $data['text_customers'] = $this->language->get('text_customers');
        $data['text_orders'] = $this->language->get('text_orders');
        $data['text_content'] = $this->language->get('text_content');
        $data['text_catalog_placeholder'] = $this->language->get('text_catalog_placeholder');
        $data['text_customers_placeholder'] = $this->language->get('text_customers_placeholder');
        $data['text_orders_placeholder'] = $this->language->get('text_orders_placeholder');
        $data['text_content_placeholder'] = $this->language->get('text_content_placeholder');
        $data['text_settings'] = $this->language->get('text_settings');
        $data['text_settings_placeholder'] = $this->language->get('text_settings_placeholder');
        $data['text_search_placeholder'] = $this->language->get('text_search_placeholder');
        $data['search_examples'] = array(
            'catalog' => $this->normalizeExamples($this->language->get('text_catalog_examples')),
            'customers' => $this->normalizeExamples($this->language->get('text_customers_examples')),
            'orders' => $this->normalizeExamples($this->language->get('text_orders_examples')),
            'content' => $this->normalizeExamples($this->language->get('text_content_examples')),
            'settings' => $this->normalizeExamples($this->language->get('text_settings_examples'))
        );

        $data['can_catalog'] = $can_catalog;
        $data['can_customers'] = $can_customers;
        $data['can_orders'] = $can_orders;
        $data['can_content'] = $can_content;
        $data['can_settings'] = $can_settings;
        $data['user_token'] = $this->session->data['user_token'];

        if ($can_catalog) {
            $data['default_search_option'] = 'catalog';
            $data['default_search_placeholder'] = $data['text_catalog_placeholder'];
            $data['default_search_label'] = $data['text_catalog'];
        } elseif ($can_customers) {
            $data['default_search_option'] = 'customers';
            $data['default_search_placeholder'] = $data['text_customers_placeholder'];
            $data['default_search_label'] = $data['text_customers'];
        } elseif ($can_orders) {
            $data['default_search_option'] = 'orders';
            $data['default_search_placeholder'] = $data['text_orders_placeholder'];
            $data['default_search_label'] = $data['text_orders'];
        } elseif ($can_content) {
            $data['default_search_option'] = 'content';
            $data['default_search_placeholder'] = $data['text_content_placeholder'];
            $data['default_search_label'] = $data['text_content'];
        } else {
            $data['default_search_option'] = 'settings';
            $data['default_search_placeholder'] = $data['text_settings_placeholder'];
            $data['default_search_label'] = $data['text_settings'];
        }

        $data['search_examples_json'] = json_encode($data['search_examples'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return $this->load->view('search/search', $data);
    }

    protected function normalizeExamples($value) {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), 'strlen'));
        }

        $value = trim((string)$value);

        if ($value === '') {
            return array();
        }

        return array_values(array_filter(array_map('trim', explode('|', $value)), 'strlen'));
    }

    public function search() {
        $this->load->language('search/search');

        $json = array();
        $session_token = isset($this->session->data['user_token']) ? (string)$this->session->data['user_token'] : '';
        $request_token = isset($this->request->get['user_token']) ? (string)$this->request->get['user_token'] : '';

        if (!$this->user->isLogged() || $session_token === '' || $request_token === '' || !hash_equals($session_token, $request_token)) {
            $this->response->addHeader('Content-Type: application/json; charset=utf-8');
            $this->response->addHeader('Cache-Control: no-store');
            $this->response->setStatusCode(403);
            $this->response->setOutput(json_encode(array('error' => $this->language->get('error_permission')), JSON_UNESCAPED_UNICODE));
            return;
        }

        $query = isset($this->request->get['query']) ? trim((string)$this->request->get['query']) : '';
        if ($query === '') {
            $json['error'] = $this->language->get('text_empty_query');
            $this->outputJson($json);
            return;
        }

        if (mb_strlen($query, 'UTF-8') > 128) {
            $query = mb_substr($query, 0, 128, 'UTF-8');
        }

        $search_option = isset($this->request->get['search-option']) ? (string)$this->request->get['search-option'] : 'catalog';
        if (!in_array($search_option, array('catalog', 'customers', 'orders', 'content', 'settings'), true)) {
            $search_option = 'catalog';
        }

        $parsed = $this->parseTypedQuery($query, $search_option);
        $term = $parsed['term'];
        $scope = $parsed['scope'];

        if ($term === '') {
            $json['error'] = $this->language->get('text_empty_query');
            $this->outputJson($json);
            return;
        }

        $data = array();
        $data['text_products'] = $this->language->get('text_products');
        $data['text_categories'] = $this->language->get('text_categories');
        $data['text_manufacturers'] = $this->language->get('text_manufacturers');
        $data['text_orders'] = $this->language->get('text_orders');
        $data['text_content'] = $this->language->get('text_content');
        $data['text_order_id'] = $this->language->get('text_order_id');
        $data['text_customers'] = $this->language->get('text_customers');
        $data['text_no_result'] = $this->language->get('text_no_result');
        $data['text_information'] = $this->language->get('text_information');
        $data['text_articles'] = $this->language->get('text_articles');
        $data['text_modules'] = $this->language->get('text_modules');
        $data['text_module_instance'] = $this->language->get('text_module_instance');
        $data['text_settings'] = $this->language->get('text_settings');
        $data['text_settings_fields'] = $this->language->get('text_settings_fields');
        $data['text_system_pages'] = $this->language->get('text_system_pages');
        $data['text_setting_key'] = $this->language->get('text_setting_key');
        $data['text_route'] = $this->language->get('text_route');
        $data['user_token'] = $session_token;

        $filter = array('query' => $term);
        $this->load->model('search/search');

        switch ($search_option) {
            case 'catalog':
                $can_products = $this->user->hasPermission('access', 'catalog/product');
                $can_categories = $this->user->hasPermission('access', 'catalog/category');
                $can_manufacturers = $this->user->hasPermission('access', 'catalog/manufacturer');

                if (!$can_products && !$can_categories && !$can_manufacturers) {
                    $this->denyJson();
                    return;
                }

                $this->load->model('tool/image');
                $data['no_image'] = $this->model_tool_image->resize('no_image.webp', 30, 30);
                $data['can_products'] = $can_products && ($scope === '' || in_array($scope, array('product', 'sku', 'model'), true));
                $data['can_categories'] = $can_categories && ($scope === '' || $scope === 'category');
                $data['can_manufacturers'] = $can_manufacturers && ($scope === '' || $scope === 'manufacturer');
                $data['products'] = array();
                $data['categories'] = array();
                $data['manufacturers'] = array();

                if ($data['can_products']) {
                    $data['products'] = $this->model_search_search->getProducts($filter);
                    foreach ($data['products'] as $key => $product) {
                        $data['products'][$key]['image'] = !empty($product['image']) ? $this->model_tool_image->resize($product['image'], 30, 30) : $data['no_image'];
                        $data['products'][$key]['url'] = $this->url->link('catalog/product/edit', 'user_token=' . $session_token . '&product_id=' . (int)$product['product_id'], true);
                    }
                }

                if ($data['can_categories']) {
                    $data['categories'] = $this->model_search_search->getCategories($filter);
                    foreach ($data['categories'] as $key => $category) {
                        $data['categories'][$key]['image'] = !empty($category['image']) ? $this->model_tool_image->resize($category['image'], 30, 30) : $data['no_image'];
                        $data['categories'][$key]['url'] = $this->url->link('catalog/category/edit', 'user_token=' . $session_token . '&category_id=' . (int)$category['category_id'], true);
                    }
                }

                if ($data['can_manufacturers']) {
                    $data['manufacturers'] = $this->model_search_search->getManufacturers($filter);
                    foreach ($data['manufacturers'] as $key => $manufacturer) {
                        $data['manufacturers'][$key]['image'] = !empty($manufacturer['image']) ? $this->model_tool_image->resize($manufacturer['image'], 30, 30) : $data['no_image'];
                        $data['manufacturers'][$key]['url'] = $this->url->link('catalog/manufacturer/edit', 'user_token=' . $session_token . '&manufacturer_id=' . (int)$manufacturer['manufacturer_id'], true);
                    }
                }

                $json['result'] = $this->load->view('search/catalog_result', $data);
                break;

            case 'customers':
                if (!$this->user->hasPermission('access', 'customer/customer')) {
                    $this->denyJson();
                    return;
                }

                $data['customers'] = $this->model_search_search->getCustomers($filter);
                foreach ($data['customers'] as $key => $customer) {
                    $data['customers'][$key]['url'] = $this->url->link('customer/customer/edit', 'user_token=' . $session_token . '&customer_id=' . (int)$customer['customer_id'], true);
                }
                $json['result'] = $this->load->view('search/customers_result', $data);
                break;

            case 'content':
                $can_information = $this->user->hasPermission('access', 'catalog/information');
                $can_articles = $this->user->hasPermission('access', 'blog/article');
                $can_extensions = $this->user->hasPermission('access', 'marketplace/extension');
                if (!$can_information && !$can_articles && !$can_extensions) {
                    $this->denyJson();
                    return;
                }

                $data['information'] = array();
                $data['articles'] = array();
                $data['modules'] = array();

                if ($can_information && ($scope === '' || $scope === 'page')) {
                    $data['information'] = $this->model_search_search->getInformation($filter);
                    foreach ($data['information'] as $key => $item) {
                        $data['information'][$key]['url'] = $this->url->link('catalog/information/edit', 'user_token=' . $session_token . '&information_id=' . (int)$item['information_id'], true);
                    }
                }

                if ($can_articles && ($scope === '' || $scope === 'article')) {
                    $data['articles'] = $this->model_search_search->getArticles($filter);
                    foreach ($data['articles'] as $key => $item) {
                        $data['articles'][$key]['url'] = $this->url->link('blog/article/edit', 'user_token=' . $session_token . '&article_id=' . (int)$item['article_id'], true);
                    }
                }

                if ($can_extensions && ($scope === '' || $scope === 'module')) {
                    $data['modules'] = $this->buildModuleResults($term, $session_token);
                }

                $json['result'] = $this->load->view('search/content_result', $data);
                break;

            case 'settings':
                $can_core_settings = $this->user->hasPermission('access', 'setting/setting');
                $can_theme_settings = $this->user->hasPermission('access', 'extension/theme/codecart');
                $can_pages = $this->hasSearchableAdminPagePermission();

                if (!$can_core_settings && !$can_theme_settings && !$can_pages) {
                    $this->denyJson();
                    return;
                }

                $this->load->model('search/settings');
                $data['settings'] = array();
                $data['pages'] = array();

                if ($can_core_settings && ($scope === '' || in_array($scope, array('setting', 'key'), true))) {
                    $this->load->language('setting/setting');
                    $tab_labels = array(
                        'general' => $this->language->get('tab_general'),
                        'store' => $this->language->get('tab_store'),
                        'local' => $this->language->get('tab_local'),
                        'option' => $this->language->get('tab_option'),
                        'image' => $this->language->get('tab_image'),
                        'mail' => $this->language->get('tab_mail'),
                        'appearance' => $this->language->get('tab_appearance'),
                        'server' => $this->language->get('tab_server'),
                        'seopro' => $this->language->get('tab_seopro')
                    );
                    $heading = $this->language->get('heading_title');

                    foreach ($this->model_search_settings->getCoreEntries() as $entry) {
                        $titles = $this->languageValues('setting/setting', $entry['label']);
                        $title = $this->language->get($entry['label']);
                        if ($title === $entry['label']) {
                            continue;
                        }

                        $tab = isset($tab_labels[$entry['tab']]) ? $tab_labels[$entry['tab']] : '';
                        $candidate = $entry;
                        $candidate['title'] = $title;
                        $candidate['path'] = $heading . ($tab !== '' ? ' → ' . $tab : '');
                        $candidate['keywords'] = $entry['key'] . ' ' . $entry['label'] . ' ' . implode(' ', $titles);
                        $score = $this->model_search_settings->match($candidate, $term);

                        if ($score > 0) {
                            $url = $this->url->link('setting/setting', 'user_token=' . $session_token . ($entry['tab'] !== '' ? '&tab=' . rawurlencode($entry['tab']) : ''), true);
                            if ($entry['focus'] !== '') {
                                $url .= '#' . rawurlencode($entry['focus']);
                            }
                            $data['settings'][] = array('title'=>$title,'path'=>$candidate['path'],'key'=>$entry['key'],'url'=>$url,'score'=>$score,'icon'=>'fa-cog');
                        }
                    }
                }

                if ($can_theme_settings && ($scope === '' || in_array($scope, array('setting', 'key'), true))) {
                    $this->load->language('extension/theme/codecart');
                    $heading = $this->language->get('heading_title');

                    foreach ($this->model_search_settings->getThemeEntries() as $entry) {
                        $titles = $this->languageValues('extension/theme/codecart', $entry['label']);
                        $title = $this->language->get($entry['label']);
                        if ($title === $entry['label']) {
                            continue;
                        }

                        $candidate = $entry;
                        $candidate['title'] = $title;
                        $candidate['path'] = $heading;
                        $candidate['keywords'] = $entry['key'] . ' ' . $entry['label'] . ' ' . implode(' ', $titles);
                        $score = $this->model_search_settings->match($candidate, $term);

                        if ($score > 0) {
                            $url = $this->url->link('extension/theme/codecart', 'user_token=' . $session_token, true);
                            if ($entry['focus'] !== '') {
                                $url .= '#' . rawurlencode($entry['focus']);
                            }
                            $data['settings'][] = array('title'=>$title,'path'=>$heading,'key'=>$entry['key'],'url'=>$url,'score'=>$score,'icon'=>'fa-paint-brush');
                        }
                    }
                }

                if ($scope === '' || in_array($scope, array('setting', 'section'), true)) {
                    foreach ($this->model_search_settings->getAdminPageEntries() as $entry) {
                        if (!$this->user->hasPermission('access', $entry['permission'])) {
                            continue;
                        }

                        $titles = $this->languageValues($entry['language'], $entry['label']);
                        $title = $this->currentLanguageValue($entry['language'], $entry['label']);
                        if ($title === $entry['label']) {
                            continue;
                        }

                        $page_heading = $this->currentLanguageValue($entry['language'], 'heading_title');
                        if ($page_heading === 'heading_title') {
                            $page_heading = $title;
                        }

                        $candidate = array(
                            'title' => $title,
                            'path' => $page_heading,
                            'key' => $entry['route'],
                            'keywords' => $entry['route'] . ' ' . $entry['keywords'] . ' ' . implode(' ', $titles)
                        );
                        $score = $this->model_search_settings->match($candidate, $term);
                        if ($score <= 0) {
                            continue;
                        }

                        $args = 'user_token=' . $session_token;
                        if ($entry['tab'] !== '') {
                            $args .= '&tab=' . rawurlencode($entry['tab']);
                        }
                        $data['pages'][] = array(
                            'title' => $title,
                            'path' => $page_heading,
                            'route' => $entry['route'],
                            'url' => $this->url->link($entry['route'], $args, true),
                            'score' => $score,
                            'icon' => $entry['icon']
                        );
                    }
                }

                $sorter = function($a, $b) {
                    if ($a['score'] === $b['score']) {
                        return strcasecmp($a['title'], $b['title']);
                    }
                    return ($a['score'] > $b['score']) ? -1 : 1;
                };
                usort($data['settings'], $sorter);
                usort($data['pages'], $sorter);
                $data['settings'] = array_slice($data['settings'], 0, 10);
                $data['pages'] = array_slice($data['pages'], 0, 10);
                $json['result'] = $this->load->view('search/settings_result', $data);
                break;

            case 'orders':
                if (!$this->user->hasPermission('access', 'sale/order')) {
                    $this->denyJson();
                    return;
                }

                $data['orders'] = $this->model_search_search->getOrders($filter);
                foreach ($data['orders'] as $key => $order) {
                    $data['orders'][$key]['url'] = $this->url->link('sale/order/info', 'user_token=' . $session_token . '&order_id=' . (int)$order['order_id'], true);
                }
                $json['result'] = $this->load->view('search/orders_result', $data);
                break;
        }

        $this->outputJson($json);
    }

    private function buildModuleResults($query, $session_token) {
        $results = array();
        $needle = $this->normalizeSearchText($query);
        $seen = array();

        foreach ($this->model_search_search->getModuleCandidates() as $row) {
            $code = trim((string)$row['code']);
            if ($code === '') {
                continue;
            }

            $permission = 'extension/module/' . $code;
            $can_direct = $this->user->hasPermission('access', $permission);
            $can_list = $this->user->hasPermission('access', 'marketplace/extension');
            if (!$can_direct && !$can_list) {
                continue;
            }

            $titles = $this->languageValues('extension/module/' . $code, 'heading_title');
            $title = $this->currentLanguageValue('extension/module/' . $code, 'heading_title');
            if ($title === 'heading_title') {
                $title = $code;
            }

            $instance_name = isset($row['name']) ? trim((string)$row['name']) : '';
            $haystack = $this->normalizeSearchText($code . ' ' . $title . ' ' . implode(' ', $titles) . ' ' . $instance_name);
            if ($needle === '' || strpos($haystack, $needle) === false) {
                continue;
            }

            $module_id = isset($row['module_id']) ? (int)$row['module_id'] : 0;
            $unique = $code . ':' . $module_id . ':' . $instance_name;
            if (isset($seen[$unique])) {
                continue;
            }
            $seen[$unique] = true;

            if ($can_direct) {
                $args = 'user_token=' . $session_token;
                if ($module_id > 0) {
                    $args .= '&module_id=' . $module_id;
                }
                $url = $this->url->link('extension/module/' . $code, $args, true);
            } else {
                $url = $this->url->link('marketplace/extension', 'user_token=' . $session_token . '&type=module', true);
            }

            $results[] = array(
                'code' => $code,
                'title' => $title,
                'name' => $instance_name,
                'module_id' => $module_id,
                'url' => $url
            );

            if (count($results) >= 10) {
                break;
            }
        }

        return $results;
    }

    private function hasSearchableAdminPagePermission() {
        $this->load->model('search/settings');
        foreach ($this->model_search_settings->getAdminPageEntries() as $entry) {
            if ($this->user->hasPermission('access', $entry['permission'])) {
                return true;
            }
        }
        return false;
    }

    private function parseTypedQuery($query, $option) {
        $query = trim((string)$query);
        $scope = '';
        $maps = array(
            'catalog' => array(
                'product'=>array('product','товар','товари','товары'),
                'category'=>array('category','категорія','категории','категория','категорії'),
                'manufacturer'=>array('manufacturer','brand','виробник','виробники','производитель','производители','бренд'),
                'sku'=>array('sku','артикул'),
                'model'=>array('model','модель')
            ),
            'customers' => array('customer'=>array('customer','client','клієнт','клиент','покупець','покупатель'),'email'=>array('email','e-mail'),'phone'=>array('phone','telephone','телефон')),
            'orders' => array('order'=>array('order','замовлення','заказ'),'invoice'=>array('invoice','рахунок','счёт','счет'),'customer'=>array('customer','client','клієнт','клиент')),
            'content' => array('page'=>array('page','сторінка','страница'),'article'=>array('article','стаття','статья'),'module'=>array('module','модуль','модули','модулі')),
            'settings' => array('setting'=>array('setting','settings','налаштування','настройка','настройки'),'key'=>array('key','ключ'),'section'=>array('section','page','розділ','раздел','сторінка','страница'))
        );

        if (isset($maps[$option])) {
            foreach ($maps[$option] as $candidate_scope => $prefixes) {
                foreach ($prefixes as $prefix) {
                    if (preg_match('/^' . preg_quote($prefix, '/') . '\s*:\s*(.+)$/ui', $query, $m)) {
                        $scope = $candidate_scope;
                        $query = trim($m[1]);
                        break 2;
                    }
                }
            }
        }

        return array('term' => $query, 'scope' => $scope);
    }

    private function languageValues($file, $key) {
        $values = array();
        foreach (array('uk-ua', 'ru-ru', 'en-gb') as $code) {
            $language = $this->searchLanguage($file, $code);
            $value = trim((string)$language->get($key));
            if ($value !== '' && $value !== $key) {
                $values[$value] = $value;
            }
        }
        return array_values($values);
    }

    private function currentLanguageValue($file, $key) {
        $code = (string)$this->config->get('config_admin_language');
        if ($code === '') {
            $code = 'en-gb';
        }
        return (string)$this->searchLanguage($file, $code)->get($key);
    }

    private function searchLanguage($file, $code) {
        $cache_key = $code . ':' . $file;
        if (!isset($this->searchLanguageCache[$cache_key])) {
            $language = new Language($code);
            $language->load($file);
            $this->searchLanguageCache[$cache_key] = $language;
        }
        return $this->searchLanguageCache[$cache_key];
    }

    private function normalizeSearchText($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $value = strtr($value, array('ё'=>'е','і'=>'и','ї'=>'и','є'=>'е','ґ'=>'г','’'=>' ','`'=>' ','\''=>' '));
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);
        $value = preg_replace('/\s+/u', ' ', (string)$value);
        return trim((string)$value);
    }

    private function denyJson() {
        $this->response->setStatusCode(403);
        $this->outputJson(array('error' => $this->language->get('error_permission')));
    }

    private function outputJson(array $json) {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Cache-Control: no-store');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE));
    }
}
