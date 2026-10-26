<?php
class ControllerSaleAbandonedCart extends Controller {
    public function index() {
        $this->load->language('sale/abandoned_cart');
        $this->document->setTitle($this->language->get('heading_title'));
        // OFF: do not query carts or run cart actions. Core shopping cart unaffected.
        $enabled = !$this->config->has('codecart_abandoned_cart_status') || (bool)$this->config->get('codecart_abandoned_cart_status');
        $source = (string)$this->config->get('codecart_abandoned_cart_source') === 'external' ? 'external' : 'standard';
        if (($this->request->server['REQUEST_METHOD'] ?? '') === 'POST' && isset($this->request->post['save_cart_settings'])) {
            $token = (string)($this->request->get['user_token'] ?? '');
            $sessionToken = (string)($this->session->data['user_token'] ?? '');
            $error = '';
            if ($token === '' || $sessionToken === '' || !hash_equals($token, $sessionToken)
                || !$this->user->hasPermission('access', 'sale/abandoned_cart')
                || !$this->user->hasPermission('modify', 'sale/abandoned_cart')) {
                $error = $this->language->get('error_permission');
            } else {
                $settings = array(
                    'codecart_abandoned_cart_status' => !empty($this->request->post['cart_status']) ? 1 : 0,
                    'codecart_abandoned_cart_source' => ($this->request->post['cart_source'] ?? '') === 'external' ? 'external' : 'standard',
                    'codecart_abandoned_cart_idle_minutes' => max(5, min(10080, (int)($this->request->post['cart_idle_minutes'] ?? 60))),
                    'codecart_abandoned_cart_days' => max(1, min(7, (int)($this->request->post['cart_days'] ?? 7)))
                );
                foreach (array('table','col_session','col_product','col_quantity','col_date','col_customer','col_email','col_phone') as $name) {
                    $value = trim((string)($this->request->post['cart_' . $name] ?? ''));
                    if ($value !== '' && !preg_match('/^[a-zA-Z0-9_]{1,64}$/', $value)) {
                        $error = $this->language->get('error_cart_mapping');
                        break;
                    }
                    $settings['codecart_abandoned_cart_' . $name] = $value;
                }
                if ($error === '' && $settings['codecart_abandoned_cart_status']
                    && $settings['codecart_abandoned_cart_source'] === 'external') {
                    foreach (array('table','col_session','col_product','col_quantity','col_date') as $required) {
                        if ($settings['codecart_abandoned_cart_' . $required] === '') {
                            $error = $this->language->get('error_cart_mapping');
                            break;
                        }
                    }
                }
                if ($error === '') {
                    $this->load->model('setting/setting');
                    $this->model_setting_setting->editSetting('codecart_abandoned_cart', $settings, 0);
                    $this->session->data['success'] = $this->language->get('text_settings_saved');
                }
            }
            if ($error !== '') { $this->session->data['error_warning'] = $error; }
            $this->response->redirect($this->url->link('sale/abandoned_cart', 'user_token=' . $sessionToken, true));
            return;
        }

        $this->load->model('sale/abandoned_cart');

        $page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $limit = (int)$this->config->get('config_limit_admin');
        if ($limit < 1) { $limit = 20; }
        $allowedSort = array('customer','email','telephone','quantity','last_activity','inactive');
        $sort = isset($this->request->get['sort']) && in_array((string)$this->request->get['sort'], $allowedSort, true) ? (string)$this->request->get['sort'] : 'last_activity';
        $order = isset($this->request->get['order']) && strtoupper((string)$this->request->get['order']) === 'ASC' ? 'ASC' : 'DESC';

        $total = $enabled ? $this->model_sale_abandoned_cart->getTotalAbandonedCarts() : 0;
        $rows = $enabled ? $this->model_sale_abandoned_cart->getAbandonedCarts(($page - 1) * $limit, $limit, $sort, $order) : array();

        $data = array();
        foreach (array(
            'heading_title','text_home','text_sale','text_no_results','text_enabled','text_disabled','text_scope_help','text_confirm_delete','text_confirm_clear','text_success_delete','text_success_clear',
            'column_customer','column_email','column_phone','column_products','column_quantity','column_total','column_last_activity','column_inactive','column_action','text_guest',
            'button_customer','button_product','button_delete','button_clear',
            'text_settings','text_disabled_note','text_source_standard','text_source_external','text_cart_mapping_info',
            'text_cart_source_help','text_cart_amount_estimate','text_cart_storefront_note','text_settings_saved','error_cart_mapping',
            'entry_cart_status','entry_cart_source','entry_cart_table','entry_cart_idle_minutes','entry_cart_days',
            'entry_col_session','entry_col_product','entry_col_quantity','entry_col_date',
            'entry_col_customer','entry_col_email','entry_col_phone','button_save'
        ) as $key) { $data[$key] = $this->language->get($key); }

        if (!empty($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else { $data['success'] = ''; }
        $data['error_warning'] = !empty($this->session->data['error_warning']) ? $this->session->data['error_warning'] : '';
        unset($this->session->data['error_warning']);

        $data['cart_enabled'] = $enabled;
        $data['cart_source'] = $source;
        $data['cart_can_modify'] = $this->user->hasPermission('modify', 'sale/abandoned_cart');
        $data['cart_fields'] = array('status' => $enabled ? 1 : 0, 'source' => $source,
            'idle_minutes' => (int)$this->config->get('codecart_abandoned_cart_idle_minutes') ?: 60,
            'days' => (int)$this->config->get('codecart_abandoned_cart_days') ?: 7);
        foreach (array('table','col_session','col_product','col_quantity','col_date','col_customer','col_email','col_phone') as $name) {
            $data['cart_fields'][$name] = (string)$this->config->get('codecart_abandoned_cart_' . $name);
        }
        $data['cart_mapping_valid'] = !$enabled || $source !== 'external'
            || $this->model_sale_abandoned_cart->getExternalSourceStatus();
        $data['carts'] = array();
        foreach ($rows as $row) {
            $products = $source === 'external'
                ? $this->model_sale_abandoned_cart->getExternalCartProducts((int)$row['customer_id'], (string)$row['session_id'])
                : $this->model_sale_abandoned_cart->getCartProducts((int)$row['customer_id'], (string)$row['session_id']);
            $product_rows = array();
            $cart_total = '0.0000';
            foreach ($products as $product) {
                $product_id = (int)$product['product_id'];
                $name = trim((string)$product['name']);
                if ($name === '') { $name = '#' . $product_id; }
                $line_total = isset($product['line_total']) ? (string)$product['line_total'] : '0.0000';
                $cart_total = \CodeCart\Core\Money::add($cart_total, $line_total);
                $product_rows[] = array(
                    'name' => $name,
                    'quantity' => (int)$product['quantity'],
                    'href' => $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $product_id, true)
                );
            }

            $idle_minutes = max(0, (int)$row['idle_minutes']);
            if ($idle_minutes >= 1440) { $idle_text = sprintf($this->language->get('text_idle_days'), (int)floor($idle_minutes / 1440), (int)floor(($idle_minutes % 1440) / 60)); }
            elseif ($idle_minutes >= 60) { $idle_text = sprintf($this->language->get('text_idle_hours'), (int)floor($idle_minutes / 60), $idle_minutes % 60); }
            else { $idle_text = sprintf($this->language->get('text_idle_minutes'), $idle_minutes); }

            $customer_id = (int)$row['customer_id'];
            $data['carts'][] = array(
                'customer_id' => $customer_id,
                'session_id' => (string)$row['session_id'],
                'customer' => $customer_id > 0 && trim((string)$row['customer']) !== '' ? (string)$row['customer'] : $this->language->get('text_guest'),
                'email' => (string)$row['email'],
                'telephone' => (string)$row['telephone'],
                'products' => $product_rows,
                'quantity' => (int)$row['quantity'],
                'total' => $this->currency->format((float)$cart_total, $this->config->get('config_currency')),
                'last_activity' => $row['last_activity'] ? date($this->language->get('date_format_short') . ' ' . $this->language->get('time_format'), strtotime($row['last_activity'])) : '',
                'inactive' => $idle_text,
                'customer_href' => $customer_id > 0 ? $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $customer_id, true) : ''
            );
        }

        $data['breadcrumbs'] = array(
            array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
            array('text' => $this->language->get('text_sale'), 'href' => '#'),
            array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'], true))
        );

        $base = 'user_token=' . $this->session->data['user_token'];
        $nextOrder = $order === 'ASC' ? 'DESC' : 'ASC';
        foreach (array('customer','email','telephone','quantity','last_activity','inactive') as $column) {
            $data['sort_' . $column] = $this->url->link('sale/abandoned_cart', $base . '&sort=' . $column . '&order=' . ($sort === $column ? $nextOrder : 'ASC'), true);
        }
        $data['sort'] = $sort;
        $data['order'] = $order;
        $data['settings_url'] = $this->url->link('sale/abandoned_cart', $base, true);
        $data['delete_url'] = $this->url->link('sale/abandoned_cart/delete', $base, true);
        $data['clear_url'] = $this->url->link('sale/abandoned_cart/clear', $base, true);
        $data['user_token'] = $this->session->data['user_token'];

        $url = '&sort=' . urlencode($sort) . '&order=' . urlencode($order);
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('sale/abandoned_cart', $base . $url . '&page={page}', true);
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), $total ? (($page - 1) * $limit) + 1 : 0, min($page * $limit, $total), $total, ceil($total / $limit));
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('sale/abandoned_cart_list', $data));
    }

    public function delete() {
        $this->load->language('sale/abandoned_cart');
        if ($this->request->server['REQUEST_METHOD'] !== 'POST'
            || (string)($this->request->get['user_token'] ?? '') === ''
            || !hash_equals((string)($this->session->data['user_token'] ?? ''), (string)($this->request->get['user_token'] ?? ''))
            || !$this->user->hasPermission('access', 'sale/abandoned_cart')
            || !$this->user->hasPermission('modify', 'sale/abandoned_cart')
            || ($this->config->has('codecart_abandoned_cart_status') && !$this->config->get('codecart_abandoned_cart_status'))
            || (string)$this->config->get('codecart_abandoned_cart_source') === 'external') {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
        } else {
            $this->load->model('sale/abandoned_cart');
            $customer_id = isset($this->request->post['customer_id']) ? (int)$this->request->post['customer_id'] : 0;
            $session_id = isset($this->request->post['session_id']) ? (string)$this->request->post['session_id'] : '';
            if ($session_id !== '') {
                $this->model_sale_abandoned_cart->deleteAbandonedCart($customer_id, $session_id);
                $this->session->data['success'] = $this->language->get('text_success_delete');
            }
        }
        $this->response->redirect($this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function clear() {
        $this->load->language('sale/abandoned_cart');
        if ($this->request->server['REQUEST_METHOD'] !== 'POST'
            || (string)($this->request->get['user_token'] ?? '') === ''
            || !hash_equals((string)($this->session->data['user_token'] ?? ''), (string)($this->request->get['user_token'] ?? ''))
            || !$this->user->hasPermission('access', 'sale/abandoned_cart')
            || !$this->user->hasPermission('modify', 'sale/abandoned_cart')
            || ($this->config->has('codecart_abandoned_cart_status') && !$this->config->get('codecart_abandoned_cart_status'))
            || (string)$this->config->get('codecart_abandoned_cart_source') === 'external') {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
        } else {
            $this->load->model('sale/abandoned_cart');
            $this->model_sale_abandoned_cart->clearAbandonedCarts();
            $this->session->data['success'] = $this->language->get('text_success_clear');
        }
        $this->response->redirect($this->url->link('sale/abandoned_cart', 'user_token=' . $this->session->data['user_token'], true));
    }
}
