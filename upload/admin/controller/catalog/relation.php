<?php
class ControllerCatalogRelation extends Controller {
    public function index() {
        $this->load->language('catalog/relation');
        $this->document->setTitle($this->language->get('heading_title'));

        if (!$this->user->hasPermission('access', 'catalog/relation')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            if (!$this->validToken() || !$this->user->hasPermission('modify', 'catalog/relation')) {
                $this->session->data['error_warning'] = $this->language->get('error_permission');
            } else {
                $action = isset($this->request->post['relation_action']) ? (string)$this->request->post['relation_action'] : 'save';
                if ($action === 'save') {
                    $this->saveSettings();
                    $this->session->data['success'] = $this->language->get('text_success');
                } elseif ($action === 'queue_all') {
                    if ($this->queueAll()) {
                        $this->session->data['success'] = $this->language->get('text_queue_added');
                    } else {
                        $this->session->data['error_warning'] = $this->language->get('error_disabled');
                    }
                } elseif ($action === 'clear_auto') {
                    $count = (new \CodeCart\Core\RelationLayer($this->registry))->clearAuto(true);
                    $this->session->data['success'] = sprintf($this->language->get('text_cleared'), $count);
                } elseif (in_array($action, array('bulk_enable','bulk_disable','bulk_delete'), true)) {
                    $selected = isset($this->request->post['selected']) && is_array($this->request->post['selected']) ? array_values(array_unique(array_filter(array_map('intval', $this->request->post['selected'])))) : array();
                    if (!$selected) {
                        $this->session->data['error_warning'] = $this->language->get('error_selected_relation');
                    } else {
                        $layer = new \CodeCart\Core\RelationLayer($this->registry);
                        if ($action === 'bulk_delete') {
                            $count = $layer->deleteRelations($selected);
                            $this->session->data['success'] = sprintf($this->language->get('text_bulk_deleted'), $count);
                        } else {
                            $status = $action === 'bulk_enable' ? 1 : 0;
                            $count = $layer->setRelationStatus($selected, $status);
                            $this->session->data['success'] = sprintf($this->language->get($status ? 'text_bulk_enabled' : 'text_bulk_disabled'), $count);
                        }
                    }
                }
            }
            $this->response->redirect($this->url->link('catalog/relation', 'user_token=' . $this->session->data['user_token'] . $this->listStateQuery(), true));
            return;
        }

        $data = array();
        foreach (array(
            'heading_title','text_edit','text_enabled','text_disabled','text_engine_help','text_storefront_help','text_manual_priority','text_manual_priority_help','text_scoring','text_scoring_help','text_queue','text_queue_help','text_stats','text_sources','text_product_relations','text_article_relations','text_total_relations','text_no_data','entry_status','entry_storefront_status','entry_product_limit','entry_article_limit','entry_in_stock','entry_use_manufacturer','entry_use_attributes','button_save','button_cancel','button_queue_all','button_clear','text_confirm_clear','error_permission','text_relations_table','text_filter','text_search','text_all','text_product','text_article','text_active','text_inactive','text_queue_pending','text_queue_processing','text_queue_done','text_queue_failed','text_queue_waiting','column_source','column_target','column_type','column_score','column_reason','column_status','column_modified','button_filter','button_reset','button_enable','button_disable','button_delete','button_scheduler','text_confirm_delete_selected','error_selected_relation'
        ) as $key) {
            $data[$key] = $this->language->get($key);
        }

        $data['breadcrumbs'] = array(
            array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
            array('text' => $this->language->get('text_catalog'), 'href' => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true)),
            array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('catalog/relation', 'user_token=' . $this->session->data['user_token'], true))
        );

        $data['action'] = $this->url->link('catalog/relation', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true);
        $data['scheduler_url'] = $this->url->link('tool/scheduler', 'user_token=' . $this->session->data['user_token'], true);
        $data['user_token'] = $this->session->data['user_token'];
        $data['error_warning'] = isset($this->session->data['error_warning']) ? $this->session->data['error_warning'] : '';
        unset($this->session->data['error_warning']);
        $data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
        unset($this->session->data['success']);

        $data['codecart_relation_status'] = (int)$this->config->get('codecart_relation_status');
        $data['codecart_relation_storefront_status'] = (int)$this->config->get('codecart_relation_storefront_status');
        $data['codecart_relation_product_limit'] = $this->settingInt('codecart_relation_product_limit', 8, 1, 24);
        $data['codecart_relation_article_limit'] = $this->settingInt('codecart_relation_article_limit', 3, 0, 12);
        $data['codecart_relation_in_stock_only'] = $this->settingBool('codecart_relation_in_stock_only', true);
        $data['codecart_relation_use_manufacturer'] = $this->settingBool('codecart_relation_use_manufacturer', true);
        $data['codecart_relation_use_attributes'] = $this->settingBool('codecart_relation_use_attributes', true);
        $layer = new \CodeCart\Core\RelationLayer($this->registry);
        $data['stats'] = $layer->stats();

        $filterSearch = isset($this->request->get['filter_search']) ? trim((string)$this->request->get['filter_search']) : '';
        $filterType = isset($this->request->get['filter_type']) ? (string)$this->request->get['filter_type'] : '';
        $filterStatus = isset($this->request->get['filter_status']) ? (string)$this->request->get['filter_status'] : '';
        $sort = isset($this->request->get['sort']) ? (string)$this->request->get['sort'] : 'score';
        $order = isset($this->request->get['order']) && strtoupper((string)$this->request->get['order']) === 'ASC' ? 'ASC' : 'DESC';
        $page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $limit = isset($this->request->get['limit']) ? (int)$this->request->get['limit'] : 25;
        if (!in_array($limit, array(25,50,100), true)) { $limit = 25; }

        $relationFilter = array(
            'search' => $filterSearch,
            'target_type' => $filterType,
            'status' => $filterStatus,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $limit,
            'limit' => $limit
        );
        $data['relations'] = $layer->getRelations($relationFilter);
        foreach ($data['relations'] as $index => $row) {
            $data['relations'][$index]['reason'] = $this->formatReason(isset($row['reason']) ? (string)$row['reason'] : '');
        }
        $relationTotal = $layer->getRelationTotal($relationFilter);
        $data['filter_search'] = $filterSearch;
        $data['filter_type'] = $filterType;
        $data['filter_status'] = $filterStatus;
        $data['sort'] = $sort;
        $data['order'] = $order;
        $data['page'] = $page;
        $data['limit'] = $limit;
        $data['relation_total'] = $relationTotal;
        $data['filter_action'] = $this->url->link('catalog/relation', 'user_token=' . $this->session->data['user_token'], true);
        $data['list_action'] = $this->url->link('catalog/relation', 'user_token=' . $this->session->data['user_token'] . $this->listStateQuery(), true);

        $base = 'user_token=' . $this->session->data['user_token'];
        if ($filterSearch !== '') { $base .= '&filter_search=' . urlencode($filterSearch); }
        if ($filterType !== '') { $base .= '&filter_type=' . urlencode($filterType); }
        if ($filterStatus !== '') { $base .= '&filter_status=' . urlencode($filterStatus); }
        $base .= '&limit=' . $limit;
        $data['sort_urls'] = array();
        foreach (array('source','target','type','score','status','date_modified') as $sortKey) {
            $nextOrder = ($sort === $sortKey && $order === 'ASC') ? 'DESC' : 'ASC';
            $data['sort_urls'][$sortKey] = $this->url->link('catalog/relation', $base . '&sort=' . $sortKey . '&order=' . $nextOrder, true);
        }
        $pagination = new Pagination();
        $pagination->total = $relationTotal;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('catalog/relation', $base . '&sort=' . urlencode($sort) . '&order=' . $order . '&page={page}', true);
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($relationTotal) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($relationTotal - $limit)) ? $relationTotal : ((($page - 1) * $limit) + $limit), $relationTotal, ceil($relationTotal / $limit));

        $data['queue_stats'] = array('pending'=>0,'processing'=>0,'done'=>0,'failed'=>0);
        try {
            $queueQuery = $this->db->query("SELECT status, COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_queue` WHERE code LIKE 'relation.generate.%' GROUP BY status");
            foreach ($queueQuery->rows as $row) {
                if (isset($data['queue_stats'][$row['status']])) { $data['queue_stats'][$row['status']] = (int)$row['total']; }
            }
        } catch (\Throwable $e) {}

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('catalog/relation', $data));
    }

    public function preview() {
        $this->load->language('catalog/relation');
        $json = array();
        if (!$this->validToken() || !$this->user->hasPermission('access', 'catalog/product') || !$this->user->hasPermission('access', 'catalog/relation')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->json($json, 403);
        }
        if (!(bool)$this->config->get('codecart_relation_status')) {
            $json['error'] = $this->language->get('error_disabled');
            return $this->json($json, 409);
        }
        $productId = isset($this->request->get['product_id']) ? (int)$this->request->get['product_id'] : 0;
        if ($productId < 1) {
            $json['error'] = $this->language->get('error_product');
            return $this->json($json, 400);
        }
        $suggestions = (new \CodeCart\Core\RelationLayer($this->registry))->suggestProduct($productId);
        foreach (array('products','articles') as $group) {
            foreach ($suggestions[$group] as $index => $row) {
                $suggestions[$group][$index]['reason'] = $this->formatReason(isset($row['reason']) ? (string)$row['reason'] : '');
            }
        }
        $json += $suggestions;
        $json['success'] = $this->language->get('text_preview_success');
        $this->json($json);
    }

    public function previewCategory() {
        $this->load->language('catalog/relation');
        $json = array();
        if (!$this->validToken() || !$this->user->hasPermission('access', 'catalog/category') || !$this->user->hasPermission('access', 'catalog/relation')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->json($json, 403);
        }
        if (!(bool)$this->config->get('codecart_relation_status')) {
            $json['error'] = $this->language->get('error_disabled');
            return $this->json($json, 409);
        }
        $categoryId = isset($this->request->get['category_id']) ? (int)$this->request->get['category_id'] : 0;
        if ($categoryId < 1) {
            $json['error'] = $this->language->get('error_category');
            return $this->json($json, 400);
        }
        $suggestions = (new \CodeCart\Core\RelationLayer($this->registry))->suggestCategory($categoryId);
        return $this->previewJson($suggestions);
    }

    public function previewArticle() {
        $this->load->language('catalog/relation');
        $json = array();
        if (!$this->validToken() || !$this->user->hasPermission('access', 'blog/article') || !$this->user->hasPermission('access', 'catalog/relation')) {
            $json['error'] = $this->language->get('error_permission');
            return $this->json($json, 403);
        }
        if (!(bool)$this->config->get('codecart_relation_status')) {
            $json['error'] = $this->language->get('error_disabled');
            return $this->json($json, 409);
        }
        $articleId = isset($this->request->get['article_id']) ? (int)$this->request->get['article_id'] : 0;
        if ($articleId < 1) {
            $json['error'] = $this->language->get('error_article');
            return $this->json($json, 400);
        }
        $suggestions = (new \CodeCart\Core\RelationLayer($this->registry))->suggestArticle($articleId);
        return $this->previewJson($suggestions);
    }

    private function previewJson(array $suggestions) {
        foreach (array('products','articles') as $group) {
            if (!isset($suggestions[$group]) || !is_array($suggestions[$group])) { $suggestions[$group] = array(); }
            foreach ($suggestions[$group] as $index => $row) {
                $suggestions[$group][$index]['reason'] = $this->formatReason(isset($row['reason']) ? (string)$row['reason'] : '');
            }
        }
        $suggestions['success'] = $this->language->get('text_preview_success');
        $this->json($suggestions);
    }

    public function queueSelected() {
        $this->load->language('catalog/relation');
        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->response->setStatusCode(405);
            return;
        }
        if (!$this->validToken() || !$this->user->hasPermission('modify', 'catalog/relation')) {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
        } elseif (!(bool)$this->config->get('codecart_relation_status')) {
            $this->session->data['error_warning'] = $this->language->get('error_disabled');
        } else {
            $selected = isset($this->request->post['selected']) && is_array($this->request->post['selected']) ? array_values(array_unique(array_filter(array_map('intval', $this->request->post['selected'])))) : array();
            if (!$selected) {
                $this->session->data['error_warning'] = $this->language->get('error_selected');
            } else {
                $queue = new \CodeCart\Core\Queue($this->registry);
                foreach (array_chunk(array_slice($selected, 0, 1000), 50) as $chunk) {
                    $queue->enqueue('relation.generate.selected', 'cron/relation/batch', array('ids' => $chunk), 90, null, 3);
                }
                $this->session->data['success'] = sprintf($this->language->get('text_selected_queued'), count($selected));
            }
        }
        $this->response->redirect($this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token'], true));
    }

    private function saveSettings() {
        $this->load->model('setting/setting');
        $wasEnabled = (bool)$this->config->get('codecart_relation_status');
        $settings = array(
            'codecart_relation_status' => !empty($this->request->post['codecart_relation_status']) ? 1 : 0,
            'codecart_relation_storefront_status' => !empty($this->request->post['codecart_relation_storefront_status']) ? 1 : 0,
            'codecart_relation_product_limit' => max(1, min(24, isset($this->request->post['codecart_relation_product_limit']) ? (int)$this->request->post['codecart_relation_product_limit'] : 8)),
            'codecart_relation_article_limit' => max(0, min(12, isset($this->request->post['codecart_relation_article_limit']) ? (int)$this->request->post['codecart_relation_article_limit'] : 3)),
            'codecart_relation_in_stock_only' => !empty($this->request->post['codecart_relation_in_stock_only']) ? 1 : 0,
            'codecart_relation_use_manufacturer' => !empty($this->request->post['codecart_relation_use_manufacturer']) ? 1 : 0,
            'codecart_relation_use_attributes' => !empty($this->request->post['codecart_relation_use_attributes']) ? 1 : 0
        );
        $this->model_setting_setting->editSetting('codecart_relation', $settings);
        foreach ($settings as $key => $value) { $this->config->set($key, $value); }

        if (!$settings['codecart_relation_status']) {
            // OFF must not leave queued generation waiting to resume unexpectedly.
            $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_queue` WHERE code LIKE 'relation.generate.%' AND status='pending'");
        } elseif (!$wasEnabled) {
            // Rule relations are derived data. While OFF no relation SQL is executed,
            // so discard any pre-OFF snapshot before enabling again and rebuild on demand.
            $layer = new \CodeCart\Core\RelationLayer($this->registry);
            $layer->clearAuto(true);
            $layer->cleanupOrphans();
        }
    }

    private function queueAll() {
        if (!(bool)$this->config->get('codecart_relation_status')) {
            return false;
        }
        $existing = $this->db->query("SELECT queue_id FROM `" . DB_PREFIX . "codecart_queue` WHERE code='relation.generate.all' AND status IN ('pending','processing') LIMIT 1");
        if (!$existing->num_rows) {
            (new \CodeCart\Core\Queue($this->registry))->enqueue('relation.generate.all', 'cron/relation/batch', array('after_id' => 0, 'limit' => 50), 95, null, 3);
        }
        return true;
    }

    private function settingInt($key, $default, $min, $max) {
        $value = $this->config->get($key);
        if ($value === null || $value === '') { $value = $default; }
        return max($min, min($max, (int)$value));
    }

    private function settingBool($key, $default) {
        $value = $this->config->get($key);
        return ($value === null || $value === '') ? (bool)$default : (bool)$value;
    }

    private function listStateQuery() {
        $query = '';
        $allowed = array('filter_search','filter_type','filter_status','sort','order','page','limit');
        foreach ($allowed as $key) {
            if (!isset($this->request->get[$key]) || $this->request->get[$key] === '') { continue; }
            $value = (string)$this->request->get[$key];
            if ($key === 'filter_type' && !in_array($value, array('product','article'), true)) { continue; }
            if ($key === 'filter_status' && !in_array($value, array('0','1'), true)) { continue; }
            if ($key === 'sort' && !in_array($value, array('source','target','type','score','status','date_modified'), true)) { continue; }
            if ($key === 'order' && !in_array(strtoupper($value), array('ASC','DESC'), true)) { continue; }
            if (in_array($key, array('page','limit'), true)) { $value = (string)max(1, (int)$value); }
            $query .= '&' . $key . '=' . urlencode($value);
        }
        return $query;
    }

    private function validToken() {
        $session = isset($this->session->data['user_token']) ? (string)$this->session->data['user_token'] : '';
        $request = isset($this->request->get['user_token']) ? (string)$this->request->get['user_token'] : '';
        return $this->user->isLogged() && $session !== '' && $request !== '' && hash_equals($session, $request);
    }

    private function json(array $json, $status = 200) {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Cache-Control: no-store');
        $this->response->setStatusCode((int)$status);
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function formatReason($reason) {
        $labels = array(
            'same_category' => $this->language->get('reason_same_category'),
            'parent_category' => $this->language->get('reason_parent_category'),
            'subcategory' => $this->language->get('reason_subcategory'),
            'manufacturer' => $this->language->get('reason_manufacturer'),
            'same_blog_category' => $this->language->get('reason_same_blog_category')
        );
        $parts = array();
        foreach (array_filter(explode(',', (string)$reason)) as $item) {
            if (isset($labels[$item])) { $parts[] = $labels[$item]; continue; }
            if (strpos($item, 'attributes:') === 0) { $parts[] = sprintf($this->language->get('reason_attributes'), (int)substr($item, 11)); continue; }
            if (strpos($item, 'name:') === 0) { $parts[] = sprintf($this->language->get('reason_name'), (int)substr($item, 5)); continue; }
            if (strpos($item, 'text:') === 0) { $parts[] = sprintf($this->language->get('reason_text'), (int)substr($item, 5)); continue; }
        }
        return implode(' · ', $parts);
    }

}
