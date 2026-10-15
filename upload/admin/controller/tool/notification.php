<?php
class ControllerToolNotification extends Controller {
    public function index() {
        $this->load->language('tool/notification');
        $this->document->setTitle($this->language->get('heading_title'));

        $service = new \CodeCart\Core\SystemNotification($this->registry);
        $status = isset($this->request->get['status']) ? (string)$this->request->get['status'] : 'all';
        if (!in_array($status, array('all', 'unread', 'read', 'resolved', 'dismissed'), true)) { $status = 'all'; }
        $page = max(1, (int)($this->request->get['page'] ?? 1));
        $pageSize = 50;
        $counts = $service->counts();
        $pages = max(1, (int)ceil($counts[$status] / $pageSize));
        $page = min($page, $pages);

        $data['notifications'] = $service->present($service->page($page, $pageSize, $status));
        $data['counts'] = $counts;
        $data['selected_status'] = $status;
        $data['page'] = $page;
        $data['pages'] = $pages;
        $data['notification_total'] = $counts[$status];
        $data['can_modify'] = $this->user->hasPermission('modify', 'tool/notification');
        $data['user_token'] = $this->session->data['user_token'];

        foreach (array(
            'heading_title', 'text_list', 'text_empty', 'text_read_all', 'text_read', 'text_resolved',
            'text_level_critical', 'text_level_error', 'text_level_warning', 'text_level_info',
            'text_delete', 'text_clear_read', 'text_confirm_delete', 'text_confirm_clear',
            'text_status_all', 'text_status_unread', 'text_status_read', 'text_status_resolved',
            'text_page', 'text_previous', 'text_next', 'text_status_dismissed', 'text_archive_help', 'error_session', 'error_permission', 'error_action', 'error_network'
        ) as $key) { $data[$key] = $this->language->get($key); }

        $data['breadcrumbs'] = array(
            array('text' => $this->language->get('text_home'), 'href' => html_entity_decode($this->url->link('common/dashboard', 'user_token=' . $data['user_token'], true), ENT_QUOTES, 'UTF-8')),
            array('text' => $this->language->get('heading_title'), 'href' => html_entity_decode($this->url->link('tool/notification', 'user_token=' . $data['user_token'], true), ENT_QUOTES, 'UTF-8'))
        );
        $data['notification_urls'] = array();
        foreach (array('read','readAll','delete','clearRead') as $action) {
            $data['notification_urls'][$action] = html_entity_decode($this->url->link('tool/notification/' . $action, 'user_token=' . $data['user_token'], true), ENT_QUOTES, 'UTF-8');
        }
        $data['notification_list_url'] = html_entity_decode($this->url->link('tool/notification', 'user_token=' . $data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('tool/notification', $data));
    }

    private function action(string $operation): void {
        $this->load->language('tool/notification');
        $json = array();
        // A valid admin user must still have explicit Access and Modify rights.
        $token = (string)($this->request->get['user_token'] ?? '');
        $sessionToken = (string)($this->session->data['user_token'] ?? '');
        if ((string)($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (!$this->user->hasPermission('access', 'tool/notification') || !$this->user->hasPermission('modify', 'tool/notification')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            try {
                $service = new \CodeCart\Core\SystemNotification($this->registry);
                if (in_array($operation, array('read', 'delete'), true)) {
                    $id = (int)($this->request->post['notification_id'] ?? 0);
                    if ($id <= 0) { $json['error'] = $this->language->get('error_action'); }
                    elseif ($operation === 'read') { $service->markRead($id); $json['success'] = true; }
                    elseif ($service->delete($id)) { $json['success'] = true; }
                    else { $json['error'] = $this->language->get('error_action'); }
                } elseif ($operation === 'readAll') {
                    $service->markAllRead();
                    $json['success'] = true;
                } elseif ($operation === 'clearRead') {
                    $service->deleteReadAndResolved();
                    $json['success'] = true;
                } else { $json['error'] = $this->language->get('error_action'); }
            } catch (\Throwable $e) {
                $json['error'] = $this->language->get('error_action');
                $this->log->write('System notification action failed (' . $operation . '): ' . $e->getMessage());
            }
        }
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode($json));
    }

    public function read() { $this->action('read'); }
    public function readAll() { $this->action('readAll'); }
    public function delete() { $this->action('delete'); }
    public function clearRead() { $this->action('clearRead'); }
}
