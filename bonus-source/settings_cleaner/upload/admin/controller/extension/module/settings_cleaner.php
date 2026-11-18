<?php
class ControllerExtensionModuleSettingsCleaner extends Controller {
    public function install() {
        if (!$this->user->hasPermission('modify', 'extension/extension/module')) {
            return;
        }
        $this->load->model('extension/module/settings_cleaner');
        $this->model_extension_module_settings_cleaner->install();
    }

    public function uninstall() {
        if (!$this->user->hasPermission('modify', 'extension/extension/module')) {
            return;
        }
        $this->load->model('extension/module/settings_cleaner');
        $this->model_extension_module_settings_cleaner->uninstall();
    }

    public function index() {
        $this->load->language('extension/module/settings_cleaner');
        $this->load->model('extension/module/settings_cleaner');
        $this->document->setTitle($this->language->get('heading_title'));
        $token = 'user_token=' . $this->session->data['user_token'];
        $route = 'extension/module/settings_cleaner';
        $error = '';

        if (isset($this->request->server['REQUEST_METHOD']) && $this->request->server['REQUEST_METHOD'] === 'POST') {
            if (!$this->user->hasPermission('modify', $route)) {
                $error = $this->language->get('error_permission');
            } else {
                try {
                    $action = isset($this->request->post['action']) ? $this->request->post['action'] : '';
                    if ($action === 'delete') {
                        if (!isset($this->request->post['confirm']) || $this->request->post['confirm'] !== 'DELETE') {
                            throw new RuntimeException('confirmation_required');
                        }
                        $backupId = $this->model_extension_module_settings_cleaner->deleteCandidates(
                            isset($this->request->post['selected']) ? $this->request->post['selected'] : array(),
                            $this->user->getId()
                        );
                        $this->session->data['success'] = sprintf($this->language->get('text_deleted'), $backupId);
                    } elseif ($action === 'restore') {
                        $restored = $this->model_extension_module_settings_cleaner->restoreBackup(isset($this->request->post['backup_id']) ? $this->request->post['backup_id'] : 0);
                        $this->session->data['success'] = sprintf($this->language->get('text_restored'), $restored);
                    } else {
                        throw new RuntimeException('invalid_selection');
                    }
                    $this->response->redirect($this->url->link($route, $token . '&scan=1', true));
                    return;
                } catch (Throwable $exception) {
                    $key = 'error_' . $exception->getMessage();
                    $known = array('error_invalid_selection', 'error_selection_changed', 'error_restore_conflict', 'error_missing_backup', 'error_already_restored', 'error_confirmation_required', 'error_scan_limit');
                    $error = in_array($key, $known, true) ? $this->language->get($key) : $this->language->get('error_operation');
                }
            }
        }

        $data = array();
        $data['heading_title'] = $this->language->get('heading_title');
        $translations = array('text_home','text_extension','text_intro','text_scan','text_all','text_candidate','text_protected','text_review','text_unknown','text_status','text_reason','text_search','text_filter','text_found','text_limit','text_incomplete','text_no_results','text_delete','text_confirm','text_confirm_delete','text_backup','text_restore','text_restore_confirm','text_backup_id','text_date','text_user','text_rows','text_restored_rows','text_reasons_system','text_reasons_custom','text_reasons_installed','text_reasons_file','text_reasons_module_reference','text_reasons_incomplete','text_reasons_missing','text_reasons_parent_extension','text_reasons_modification_reference','column_id','column_store','column_code','column_key','column_status','column_reason','text_select_all','button_cancel');
        foreach ($translations as $key) {
            $data[$key] = $this->language->get($key);
        }
        $data['error_warning'] = $error;
        $data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
        unset($this->session->data['success']);
        $data['breadcrumbs'] = array(
            array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token, true)),
            array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module', true)),
            array('text' => $data['heading_title'], 'href' => $this->url->link($route, $token, true))
        );
        $data['scan_url'] = $this->url->link($route, $token . '&scan=1', true);
        $data['action'] = $this->url->link($route, $token . '&scan=1', true);
        $data['cancel'] = $this->url->link('marketplace/extension', $token . '&type=module', true);
        $data['user_token'] = $this->session->data['user_token'];
        $data['scanned'] = isset($this->request->get['scan']) && $this->request->get['scan'] === '1';
        $data['filter_status'] = isset($this->request->get['status']) && in_array($this->request->get['status'], array('candidate','protected','review','unknown','all'), true) ? $this->request->get['status'] : 'candidate';
        $data['search'] = isset($this->request->get['search']) ? utf8_substr(trim((string)$this->request->get['search']), 0, 100) : '';
        $data['sort'] = isset($this->request->get['sort']) && in_array($this->request->get['sort'], array('setting_id','store_id','code','key','status'), true) ? $this->request->get['sort'] : 'code';
        $data['order'] = isset($this->request->get['order']) && $this->request->get['order'] === 'DESC' ? 'DESC' : 'ASC';
        $data['page'] = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $data['rows'] = array();
        $data['counts'] = array('candidate' => 0, 'protected' => 0, 'review' => 0, 'unknown' => 0);
        $data['truncated'] = false;
        $data['incomplete'] = false;
        $data['total'] = 0;
        $data['sort_urls'] = array();
        $queryParams = '&scan=1&status=' . $data['filter_status'] . '&search=' . urlencode($data['search']);

        if ($data['scanned']) {
            $scan = $this->model_extension_module_settings_cleaner->scan();
            $data['counts'] = $scan['counts'];
            $data['truncated'] = $scan['truncated'];
            $data['incomplete'] = $scan['incomplete'];
            $rows = array();
            foreach ($scan['rows'] as $row) {
                if ($data['filter_status'] !== 'all' && $row['status'] !== $data['filter_status']) {
                    continue;
                }
                if ($data['search'] !== '' && stripos((string)$row['code'] . ' ' . (string)$row['key'] . ' ' . (string)$row['store_id'], $data['search']) === false) {
                    continue;
                }
                $row['status_label'] = $this->language->get('text_' . $row['status']);
                $row['reason_label'] = $this->language->get('text_reasons_' . $row['reason']);
                $rows[] = $row;
            }
            $sort = $data['sort'];
            $direction = $data['order'] === 'DESC' ? -1 : 1;
            usort($rows, function($a, $b) use ($sort, $direction) {
                $compare = in_array($sort, array('setting_id','store_id'), true) ? ((int)$a[$sort] <=> (int)$b[$sort]) : strcmp((string)$a[$sort], (string)$b[$sort]);
                return $compare === 0 ? ((int)$a['setting_id'] <=> (int)$b['setting_id']) : $compare * $direction;
            });
            $data['total'] = count($rows);
            $data['rows'] = array_slice($rows, ($data['page'] - 1) * 50, 50);
            foreach (array('setting_id','store_id','code','key','status') as $field) {
                $direction = $sort === $field && $data['order'] === 'ASC' ? 'DESC' : 'ASC';
                $data['sort_urls'][$field] = $this->url->link($route, $token . $queryParams . '&sort=' . $field . '&order=' . $direction, true);
            }
        }
        $data['pagination'] = '';
        if ($data['total'] > 50) {
            $pagination = new Pagination();
            $pagination->total = $data['total'];
            $pagination->page = $data['page'];
            $pagination->limit = 50;
            $pagination->url = $this->url->link($route, $token . $queryParams . '&sort=' . $data['sort'] . '&order=' . $data['order'] . '&page={page}', true);
            $data['pagination'] = $pagination->render();
        }
        $data['backups'] = $this->model_extension_module_settings_cleaner->getBackups();
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view($route, $data));
    }
}
