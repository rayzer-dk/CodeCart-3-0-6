<?php
// * @source See SOURCE.txt for source and other copyright.
// * @license GNU General Public License version 3; see LICENSE.txt

class ControllerSearchSearch extends Controller {
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
        $can_settings = $can_core_settings || $can_theme_settings;

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

        // Keep global admin search deliberately bounded: it is an interactive helper,
        // not a full catalog export endpoint.
        if (mb_strlen($query, 'UTF-8') > 128) {
            $query = mb_substr($query, 0, 128, 'UTF-8');
        }

        $search_option = isset($this->request->get['search-option']) ? (string)$this->request->get['search-option'] : 'catalog';
        if (!in_array($search_option, array('catalog', 'customers', 'orders', 'content', 'settings'), true)) {
            $search_option = 'catalog';
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
        $data['text_settings'] = $this->language->get('text_settings');
        $data['text_setting_key'] = $this->language->get('text_setting_key');
        $data['user_token'] = $session_token;

        $filter = array('query' => $query);
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
                $data['can_products'] = $can_products;
                $data['can_categories'] = $can_categories;
                $data['can_manufacturers'] = $can_manufacturers;
                $data['products'] = array();
                $data['categories'] = array();
                $data['manufacturers'] = array();

                if ($can_products) {
                    $data['products'] = $this->model_search_search->getProducts($filter);
                    foreach ($data['products'] as $key => $product) {
                        $data['products'][$key]['image'] = !empty($product['image']) ? $this->model_tool_image->resize($product['image'], 30, 30) : $data['no_image'];
                        $data['products'][$key]['url'] = $this->url->link('catalog/product/edit', 'user_token=' . $session_token . '&product_id=' . (int)$product['product_id'], true);
                    }
                }

                if ($can_categories) {
                    $data['categories'] = $this->model_search_search->getCategories($filter);
                    foreach ($data['categories'] as $key => $category) {
                        $data['categories'][$key]['image'] = !empty($category['image']) ? $this->model_tool_image->resize($category['image'], 30, 30) : $data['no_image'];
                        $data['categories'][$key]['url'] = $this->url->link('catalog/category/edit', 'user_token=' . $session_token . '&category_id=' . (int)$category['category_id'], true);
                    }
                }

                if ($can_manufacturers) {
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
                if ($can_information) {
                    $data['information'] = $this->model_search_search->getInformation($filter);
                    foreach ($data['information'] as $key => $item) {
                        $data['information'][$key]['url'] = $this->url->link('catalog/information/edit', 'user_token=' . $session_token . '&information_id=' . (int)$item['information_id'], true);
                    }
                }
                if ($can_articles) {
                    $data['articles'] = $this->model_search_search->getArticles($filter);
                    foreach ($data['articles'] as $key => $item) {
                        $data['articles'][$key]['url'] = $this->url->link('blog/article/edit', 'user_token=' . $session_token . '&article_id=' . (int)$item['article_id'], true);
                    }
                }
                if ($can_extensions) {
                    $data['modules'] = $this->model_search_search->getModules($filter);
                    foreach ($data['modules'] as $key => $item) {
                        $data['modules'][$key]['url'] = $this->url->link('marketplace/extension', 'user_token=' . $session_token . '&type=module', true);
                    }
                }
                $json['result'] = $this->load->view('search/content_result', $data);
                break;

            case 'settings':
                $can_core_settings = $this->user->hasPermission('access', 'setting/setting');
                $can_theme_settings = $this->user->hasPermission('access', 'extension/theme/codecart');

                if (!$can_core_settings && !$can_theme_settings) {
                    $this->denyJson();
                    return;
                }

                $this->load->model('search/settings');
                $data['settings'] = array();

                if ($can_core_settings) {
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
                        $title = $this->language->get($entry['label']);
                        if ($title === $entry['label']) {
                            continue;
                        }

                        $tab = isset($tab_labels[$entry['tab']]) ? $tab_labels[$entry['tab']] : '';
                        $candidate = $entry;
                        $candidate['title'] = $title;
                        $candidate['path'] = $heading . ($tab !== '' ? ' → ' . $tab : '');
                        $candidate['keywords'] = $entry['key'] . ' ' . $entry['label'];
                        $score = $this->model_search_settings->match($candidate, $query);

                        if ($score > 0) {
                            $url = $this->url->link('setting/setting', 'user_token=' . $session_token . ($entry['tab'] !== '' ? '&tab=' . rawurlencode($entry['tab']) : ''), true);
                            if ($entry['focus'] !== '') {
                                $url .= '#' . rawurlencode($entry['focus']);
                            }

                            $data['settings'][] = array(
                                'title' => $title,
                                'path' => $candidate['path'],
                                'key' => $entry['key'],
                                'url' => $url,
                                'score' => $score
                            );
                        }
                    }
                }

                if ($can_theme_settings) {
                    $this->load->language('extension/theme/codecart');
                    $heading = $this->language->get('heading_title');

                    foreach ($this->model_search_settings->getThemeEntries() as $entry) {
                        $title = $this->language->get($entry['label']);
                        if ($title === $entry['label']) {
                            continue;
                        }

                        $candidate = $entry;
                        $candidate['title'] = $title;
                        $candidate['path'] = $heading;
                        $candidate['keywords'] = $entry['key'] . ' ' . $entry['label'];
                        $score = $this->model_search_settings->match($candidate, $query);

                        if ($score > 0) {
                            $url = $this->url->link('extension/theme/codecart', 'user_token=' . $session_token, true);
                            if ($entry['focus'] !== '') {
                                $url .= '#' . rawurlencode($entry['focus']);
                            }

                            $data['settings'][] = array(
                                'title' => $title,
                                'path' => $heading,
                                'key' => $entry['key'],
                                'url' => $url,
                                'score' => $score
                            );
                        }
                    }
                }

                usort($data['settings'], function($a, $b) {
                    if ($a['score'] === $b['score']) {
                        return strcasecmp($a['title'], $b['title']);
                    }
                    return ($a['score'] > $b['score']) ? -1 : 1;
                });

                $data['settings'] = array_slice($data['settings'], 0, 12);
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
