<?php
class ControllerDesignForm extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('design/form');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('design/form');
        $this->getList();
    }

    public function add() {
        $this->load->language('design/form');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('design/form');
        if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validateForm()) {
            $form_id = $this->model_design_form->addForm($this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('design/form/edit', 'user_token=' . $this->session->data['user_token'] . '&form_id=' . (int)$form_id, true));
        }
        $this->getForm();
    }

    public function edit() {
        $this->load->language('design/form');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('design/form');
        $form_id = isset($this->request->get['form_id']) ? (int)$this->request->get['form_id'] : 0;
        if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validateForm()) {
            $this->model_design_form->editForm($form_id, $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('design/form/edit', 'user_token=' . $this->session->data['user_token'] . '&form_id=' . $form_id, true));
        }
        $this->getForm();
    }

    public function delete() {
        $this->load->language('design/form');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('design/form');
        if (isset($this->request->post['selected']) && $this->validateDelete()) {
            foreach ((array)$this->request->post['selected'] as $form_id) { $this->model_design_form->deleteForm((int)$form_id); }
            $this->session->data['success'] = $this->language->get('text_success');
        }
        $this->response->redirect($this->url->link('design/form', 'user_token=' . $this->session->data['user_token'], true));
    }


    public function icons() {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Cache-Control: private, no-store, max-age=0');

        if (!$this->user->hasPermission('access', 'design/form')) {
            $this->response->setStatusCode(403);
            $this->response->setOutput(json_encode(array('error' => 'Forbidden', 'icons' => array())));
            return;
        }

        $icons = array();
        try {
            $file = DIR_SYSTEM . 'config/codecart_fontawesome_full_catalog.json';
            if (is_file($file) && is_readable($file)) {
                $payload = json_decode((string)file_get_contents($file), true);
                if (is_array($payload) && isset($payload['icons']) && is_array($payload['icons'])) {
                    foreach ($payload['icons'] as $row) {
                        if (!is_array($row)) { continue; }
                        $name = isset($row['name']) ? strtolower(trim((string)$row['name'])) : '';
                        if ($name === '' || !preg_match('/^[a-z0-9-]+$/', $name)) { continue; }
                        $styles = isset($row['styles']) && is_array($row['styles']) ? array_values(array_intersect($row['styles'], array('solid', 'regular', 'brands'))) : array();
                        if (!$styles) { continue; }
                        $style = isset($row['primary_style']) && in_array($row['primary_style'], $styles, true) ? (string)$row['primary_style'] : (in_array('brands', $styles, true) ? 'brands' : (in_array('solid', $styles, true) ? 'solid' : 'regular'));
                        $search = array($name);
                        if (!empty($row['label'])) { $search[] = (string)$row['label']; }
                        if (!empty($row['search']) && is_array($row['search'])) { $search = array_merge($search, array_map('strval', $row['search'])); }
                        $icons[] = array('c' => 'fa-' . $style . ' fa-' . $name, 'n' => $name, 's' => implode(' ', array_unique($search)));
                    }
                }
            }
        } catch (\Throwable $e) {
            if (isset($this->log)) { $this->log->write('CodeCart icon picker: ' . $e->getMessage()); }
        }

        $this->response->setOutput(json_encode(array('icons' => $icons), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function getList() {
        $sort = isset($this->request->get['sort']) ? (string)$this->request->get['sort'] : 'f.name';
        $order = isset($this->request->get['order']) && strtoupper((string)$this->request->get['order']) === 'DESC' ? 'DESC' : 'ASC';
        $page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $limit = 25;
        $data['breadcrumbs'] = array(
            array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/dashboard','user_token=' . $this->session->data['user_token'],true)),
            array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('design/form','user_token=' . $this->session->data['user_token'],true))
        );
        $data['add'] = $this->url->link('design/form/add', 'user_token=' . $this->session->data['user_token'], true);
        $data['delete'] = $this->url->link('design/form/delete', 'user_token=' . $this->session->data['user_token'], true);
        $data['forms'] = array();
        $total = $this->model_design_form->getTotalForms();
        foreach ($this->model_design_form->getForms(array('sort'=>$sort,'order'=>$order,'start'=>($page-1)*$limit,'limit'=>$limit)) as $row) {
            $data['forms'][] = array(
                'form_id'=>(int)$row['form_id'],
                'name'=>(string)$row['name'],
                'kind'=>(string)(isset($row['kind']) ? $row['kind'] : 'request'),
                'title'=>(string)(isset($row['title']) ? $row['title'] : ''),
                'status'=>(int)$row['status'],
                'shortcode'=>'[ccp_form id="' . (int)$row['form_id'] . '"]',
                'button_shortcode'=>'[ccp_form id="' . (int)$row['form_id'] . '" mode="button"]',
                'edit'=>$this->url->link('design/form/edit','user_token=' . $this->session->data['user_token'] . '&form_id=' . (int)$row['form_id'],true)
            );
        }
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
        unset($this->session->data['success']);
        $data['selected'] = isset($this->request->post['selected']) ? (array)$this->request->post['selected'] : array();
        $toggle = $order === 'ASC' ? 'DESC' : 'ASC';
        $data['sort_name'] = $this->url->link('design/form','user_token=' . $this->session->data['user_token'] . '&sort=f.name&order=' . $toggle,true);
        $data['sort_title'] = $this->url->link('design/form','user_token=' . $this->session->data['user_token'] . '&sort=fd.title&order=' . $toggle,true);
        $data['sort_status'] = $this->url->link('design/form','user_token=' . $this->session->data['user_token'] . '&sort=f.status&order=' . $toggle,true);
        $pagination = new Pagination();
        $pagination->total = $total;
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('design/form','user_token=' . $this->session->data['user_token'] . '&sort=' . urlencode($sort) . '&order=' . $order . '&page={page}',true);
        $data['pagination'] = $pagination->render();
        $data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, min($page * $limit, $total), $total, ceil($total / $limit));
        $data['sort'] = $sort;
        $data['order'] = $order;
        foreach (array('text_form_usage_title','text_form_usage_product','text_form_usage_category_products','text_form_usage_category_page','text_form_usage_global','text_form_usage_shortcode') as $usage_key) {
            $data[$usage_key] = $this->language->get($usage_key);
        }
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('design/form_list', $data));
    }

    protected function getForm() {
        $this->document->addStyle('view/stylesheet/codecart-form-builder.css?v=3.0.6.0');
        $this->document->addScript('view/javascript/codecart-form-builder.js?v=3.0.6.0');
        $data['text_form'] = !$this->error ? $this->language->get('text_form') : $this->language->get('heading_title');
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';
        $data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
        unset($this->session->data['success']);
        $form_id = isset($this->request->get['form_id']) ? (int)$this->request->get['form_id'] : 0;
        $form_info = $form_id ? $this->model_design_form->getForm($form_id) : array();
        $data['breadcrumbs'] = array(
            array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/dashboard','user_token=' . $this->session->data['user_token'],true)),
            array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('design/form','user_token=' . $this->session->data['user_token'],true))
        );
        $data['action'] = $form_id ? $this->url->link('design/form/edit','user_token=' . $this->session->data['user_token'] . '&form_id=' . $form_id,true) : $this->url->link('design/form/add','user_token=' . $this->session->data['user_token'],true);
        $data['cancel'] = $this->url->link('design/form','user_token=' . $this->session->data['user_token'],true);
        foreach (array('button_choose_icon','button_clear_icon','text_icon_picker_title','text_icon_search','text_icon_loading') as $key) { $data[$key] = $this->language->get($key); }
        foreach (array('name','kind','recipient','button_icon','button_bg','button_text_color','button_hover_bg','status') as $key) {
            if (isset($this->request->post[$key])) { $data[$key] = $this->request->post[$key]; }
            elseif (isset($form_info[$key])) { $data[$key] = $form_info[$key]; }
            else { $defaults = array('status'=>1,'kind'=>'request','button_icon'=>'fa-envelope-o','button_bg'=>'#0b6fd3','button_text_color'=>'#ffffff','button_hover_bg'=>'#095eb4'); $data[$key] = isset($defaults[$key]) ? $defaults[$key] : ''; }
        }
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();
        if (isset($this->request->post['form_description'])) { $data['form_description'] = $this->request->post['form_description']; }
        elseif ($form_id) { $data['form_description'] = $this->model_design_form->getFormDescriptions($form_id); }
        else { $data['form_description'] = array(); }
        foreach ($data['languages'] as $language) {
            $id = (int)$language['language_id'];
            if (!isset($data['form_description'][$id])) { $data['form_description'][$id] = array(); }
            $row =& $data['form_description'][$id];
            if (!isset($row['title'])) { $row['title'] = ''; }
            if (!isset($row['description'])) { $row['description'] = ''; }
            if (!isset($row['submit_text']) || $row['submit_text'] === '') { $row['submit_text'] = $this->language->get('text_default_submit'); }
            if (!isset($row['success_text']) || $row['success_text'] === '') { $row['success_text'] = $this->language->get('text_default_success'); }
            if (!isset($row['fields_json'])) { $row['fields_json'] = '[]'; }
        }
        unset($row);
        $data['icons_url'] = $this->url->link('design/form/icons', 'user_token=' . $this->session->data['user_token'], true);
        $data['js_presets_json'] = $this->language->get('js_presets_json');
        $data['form_id'] = $form_id;
        $data['shortcode'] = $form_id ? '[ccp_form id="' . $form_id . '"]' : '';
        $data['button_shortcode'] = $form_id ? '[ccp_form id="' . $form_id . '" mode="button"]' : '';
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('design/form_form', $data));
    }

    protected function validateForm() {
        if (!$this->user->hasPermission('modify', 'design/form')) { $this->error['warning'] = $this->language->get('error_permission'); }
        $name = trim((string)(isset($this->request->post['name']) ? $this->request->post['name'] : ''));
        if (utf8_strlen($name) < 2 || utf8_strlen($name) > 128) { $this->error['name'] = $this->language->get('error_name'); }
        $recipient = trim((string)(isset($this->request->post['recipient']) ? $this->request->post['recipient'] : ''));
        if ($recipient !== '' && !filter_var($recipient, FILTER_VALIDATE_EMAIL)) { $this->error['warning'] = $this->language->get('error_recipient'); }
        if (isset($this->request->post['form_description']) && is_array($this->request->post['form_description'])) {
            foreach ($this->request->post['form_description'] as $language_id => &$row) {
                if (!is_array($row)) { $row = array(); }
                $row['fields_json'] = \CodeCart\Core\FormBuilder::encode(isset($row['fields_json']) ? $row['fields_json'] : array());
            }
            unset($row);
        }
        return !$this->error;
    }

    protected function validateDelete() {
        if (!$this->user->hasPermission('modify', 'design/form')) { $this->error['warning'] = $this->language->get('error_permission'); }
        return !$this->error;
    }
}
