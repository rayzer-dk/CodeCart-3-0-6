<?php
/**
 * Domovyk dashboard compatibility widget, modernized for CodeCart PRO Core.
 * Original module: Dinox / GPL-3.0.
 */
class ControllerExtensionDashboardDomovyk extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/dashboard/domovyk');
        $this->document->setTitle($this->language->get('heading_h1'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('dashboard_domovyk', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        }

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['breadcrumbs'] = array(
            array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
            array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=dashboard', true)),
            array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/dashboard/domovyk', 'user_token=' . $this->session->data['user_token'], true))
        );
        $data['action'] = $this->url->link('extension/dashboard/domovyk', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

        $data['columns'] = range(3, 12);
        $data['dashboard_domovyk_width'] = isset($this->request->post['dashboard_domovyk_width']) ? (int)$this->request->post['dashboard_domovyk_width'] : (int)$this->config->get('dashboard_domovyk_width');
        if ($data['dashboard_domovyk_width'] < 3 || $data['dashboard_domovyk_width'] > 12) { $data['dashboard_domovyk_width'] = 12; }
        $data['dashboard_domovyk_status'] = isset($this->request->post['dashboard_domovyk_status']) ? (int)$this->request->post['dashboard_domovyk_status'] : (int)$this->config->get('dashboard_domovyk_status');
        $data['dashboard_domovyk_sort_order'] = isset($this->request->post['dashboard_domovyk_sort_order']) ? (int)$this->request->post['dashboard_domovyk_sort_order'] : (int)$this->config->get('dashboard_domovyk_sort_order');
        $data['dashboard_domovyk_disk_free_space'] = isset($this->request->post['dashboard_domovyk_disk_free_space']) ? (int)$this->request->post['dashboard_domovyk_disk_free_space'] : ((int)$this->config->get('dashboard_domovyk_disk_free_space') ?: 500);
        $data['dashboard_domovyk_free_space_status'] = isset($this->request->post['dashboard_domovyk_free_space_status']) ? (int)$this->request->post['dashboard_domovyk_free_space_status'] : (int)$this->config->get('dashboard_domovyk_free_space_status');

        $configured = isset($this->request->post['dashboard_domovyk_cron']) ? $this->request->post['dashboard_domovyk_cron'] : $this->config->get('dashboard_domovyk_cron');
        $configured = is_array($configured) ? $configured : array();
        $folders = array('logs' => DIR_STORAGE . 'logs', 'cache' => DIR_CACHE, 'imagescache' => DIR_IMAGE . 'cache');
        $data['folders'] = array();
        foreach ($folders as $key => $dir) {
            $data['folders'][] = array(
                'key' => $key,
                'name' => $this->language->get('text_dir_' . $key),
                'size' => isset($configured[$key]['size']) ? max(0, (int)$configured[$key]['size']) : 100
            );
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/dashboard/domovyk_form', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/dashboard/domovyk')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

    public function dashboard() {
        $this->load->language('common/developer');
        $this->load->language('extension/dashboard/domovyk');

        $data['user_token'] = $this->session->data['user_token'];
        $data['developer_theme'] = (bool)$this->config->get('developer_theme');
        $data['setting'] = $this->url->link('extension/dashboard/domovyk', 'user_token=' . $this->session->data['user_token'], true);
        $data['diagnostics'] = $this->url->link('tool/codecart_core', 'user_token=' . $this->session->data['user_token'], true);
        $data['notifications'] = $this->url->link('tool/notification', 'user_token=' . $this->session->data['user_token'], true);
        $data['phpinfo_link'] = $this->url->link('extension/dashboard/domovyk/phpinfo', 'user_token=' . $this->session->data['user_token'], true);
        $data['phpinfo_download_link'] = $this->url->link('extension/dashboard/domovyk/phpinfoDownload', 'user_token=' . $this->session->data['user_token'], true);
        $data['text_phpinfo_download'] = $this->language->get('text_phpinfo_download');

        $data['core_build'] = defined('CODECART_BUILD') ? (string)CODECART_BUILD : VERSION;
        $data['phpversion'] = PHP_VERSION;
        $data['database_version'] = '';
        try {
            $q = $this->db->query('SELECT VERSION() AS version');
            $data['database_version'] = isset($q->row['version']) ? (string)$q->row['version'] : '';
        } catch (\Throwable $e) {}
        $data['cache_engine'] = strtoupper((string)$this->config->get('cache_engine'));
        if ($data['cache_engine'] === '') { $data['cache_engine'] = 'FILE'; }
        $data['cache_requested'] = $data['cache_engine'];
        $data['cache_fallback'] = '';
        if ($this->registry->has('cache')) {
            $cache = $this->registry->get('cache');
            if (is_object($cache) && method_exists($cache, 'getRequestedEngine')) { $data['cache_requested'] = strtoupper((string)$cache->getRequestedEngine()); }
            if (is_object($cache) && method_exists($cache, 'getActiveEngine')) { $data['cache_engine'] = strtoupper((string)$cache->getActiveEngine()); }
            if (is_object($cache) && method_exists($cache, 'getFallbackReason')) { $data['cache_fallback'] = (string)$cache->getFallbackReason(); }
        }
        $data['opcache_status'] = function_exists('opcache_get_status') && is_array(@opcache_get_status(false)) ? $this->language->get('text_enabled') : $this->language->get('text_disabled');
        $data['php_sapi'] = PHP_SAPI;
        $data['memory_limit'] = (string)ini_get('memory_limit');
        $data['upload_max_filesize'] = (string)ini_get('upload_max_filesize');
        $data['post_max_size'] = (string)ini_get('post_max_size');
        $data['max_execution_time'] = (string)ini_get('max_execution_time');
        $data['php_gzip_level'] = max(0, min(9, (int)$this->config->get('config_compression')));
        $data['cache_generation_mode'] = in_array(strtolower($data['cache_engine']), array('apcu','redis','memcached'), true);
        $data['http_compression'] = '';
        $data['http_compression_server'] = '';
        $data['http_compression_checked'] = '';
        try {
            if (class_exists('CodeCart\Core\DashboardHealth')) {
                $performance = (new \CodeCart\Core\DashboardHealth($this->registry))->lastPerformance();
                if (is_array($performance) && $performance) {
                    $data['http_compression'] = isset($performance['content_encoding']) ? strtolower((string)$performance['content_encoding']) : '';
                    $data['http_compression_server'] = isset($performance['server']) ? (string)$performance['server'] : '';
                    if (!empty($performance['cf_ray'])) { $data['http_compression_server'] = trim($data['http_compression_server'] . ' / Cloudflare'); }
                    $data['http_compression_checked'] = isset($performance['checked_at']) ? (string)$performance['checked_at'] : '';
                }
            }
        } catch (\Throwable $e) {}
        $data['notification_count'] = 0;
        if (class_exists('CodeCart\\Core\\SystemNotification')) {
            try { $data['notification_count'] = (new \CodeCart\Core\SystemNotification($this->registry))->unreadCount(); } catch (\Throwable $e) {}
        }

        if (function_exists('disk_free_space') && $this->config->get('dashboard_domovyk_free_space_status')) {
            $disk_space = @disk_free_space(DIR_STORAGE);
            if ($disk_space !== false) {
                $data['disk_free_space'] = $this->formatSize((float)$disk_space);
                $space_limit = max(0, (int)$this->config->get('dashboard_domovyk_disk_free_space')) * 1024 * 1024;
                $data['disk_free_space_warning'] = $space_limit > 0 && $disk_space < $space_limit ? sprintf($this->language->get('text_warning_free_space'), (int)$this->config->get('dashboard_domovyk_disk_free_space')) : '';
            }
        }

        $limits = $this->config->get('dashboard_domovyk_cron');
        $limits = is_array($limits) ? $limits : array();
        $folders = array('logs' => DIR_STORAGE . 'logs', 'cache' => DIR_CACHE, 'imagescache' => DIR_IMAGE . 'cache');
        $data['folders'] = array();
        foreach ($folders as $key => $dir) {
            $cache = $this->config->get('domovyk_folders_' . $key);
            $limitMb = isset($limits[$key]['size']) ? max(0, (int)$limits[$key]['size']) : 100;
            $row = array('key'=>$key, 'name'=>$this->language->get('text_dir_' . $key), 'size'=>$this->language->get('text_not_calculated'), 'files'=>'', 'warning_size'=>'');
            if (is_array($cache) && isset($cache['size'], $cache['unit'])) {
                $row['size'] = sprintf($this->language->get('text_folder_size'), $cache['unit']['size'] . ' ' . $cache['unit']['unit']);
                $row['files'] = sprintf($this->language->get('text_folder_files'), isset($cache['files']) ? (int)$cache['files'] : 0) . (!empty($cache['date']) ? ' | ' . $cache['date'] : '');
                if ($limitMb > 0 && (float)$cache['size'] > $limitMb * 1024 * 1024) {
                    $row['warning_size'] = sprintf($this->language->get('text_warning_size'), $limitMb);
                }
            }
            $data['folders'][] = $row;
        }

        return $this->load->view('extension/dashboard/domovyk_info', $data);
    }

    public function clear($dir = false) {
        $this->load->language('extension/dashboard/domovyk');
        $json = array();
        if (!$this->user->hasPermission('modify', 'extension/dashboard/domovyk')) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            $key = $dir ?: (isset($this->request->get['dir']) ? (string)$this->request->get['dir'] : '');
            $folders = array('logs' => DIR_STORAGE . 'logs/*', 'cache' => DIR_CACHE . 'cache.*', 'imagescache' => DIR_IMAGE . 'cache/*');
            if (!isset($folders[$key])) {
                $json['error'] = $this->language->get('error_folder');
            } else {
                if ($key === 'cache') {
                    try { $this->cache->delete('*'); } catch (\Throwable $e) { $this->log->write('Domovyk cache reset: ' . $e->getMessage()); }
                }
                $files = glob($folders[$key]);
                if ($files) { foreach ($files as $file) { $this->deletePath($file); } }
                $json['success'] = sprintf($this->language->get('text_cache'), $this->language->get('text_clearfolder'));
            }
        }
        if (!$dir) { $this->response->addHeader('Content-Type: application/json'); $this->response->setOutput(json_encode($json)); }
    }

    public function calc($dir = false) {
        $this->load->language('extension/dashboard/domovyk');
        $this->load->model('setting/setting');
        $json = array();
        $key = $dir ?: (isset($this->request->get['dir']) ? (string)$this->request->get['dir'] : '');
        $folders = array('logs' => DIR_STORAGE . 'logs', 'cache' => DIR_CACHE, 'imagescache' => DIR_IMAGE . 'cache');
        if (!$this->user->hasPermission('modify', 'extension/dashboard/domovyk')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (!isset($folders[$key]) || !is_dir($folders[$key])) {
            $json['error'] = $this->language->get('error_folder');
        } else {
            $stats = $this->getFilesStats($folders[$key]);
            $folder = array('size'=>$stats['size'], 'unit'=>$this->formatSize($stats['size']), 'files'=>$stats['files'], 'date'=>date('Y-m-d H:i:s'));
            $settings = $this->model_setting_setting->getSetting('domovyk');
            $settings['domovyk_folders_' . $key] = $folder;
            $this->model_setting_setting->editSetting('domovyk', $settings);
            $limits = $this->config->get('dashboard_domovyk_cron');
            $limitMb = is_array($limits) && isset($limits[$key]['size']) ? max(0, (int)$limits[$key]['size']) : 100;
            $state = ($limitMb > 0 && $folder['size'] > $limitMb * 1024 * 1024) ? sprintf($this->language->get('text_warning_size'), $limitMb) : $this->language->get('text_normal');
            $json['success'] = sprintf($this->language->get('text_folder_size'), $folder['unit']['size'] . ' ' . $folder['unit']['unit']) . ' ' . sprintf($this->language->get('text_folder_files'), $folder['files']) . ' | ' . $state;
        }
        if (!$dir) { $this->response->addHeader('Content-Type: application/json'); $this->response->setOutput(json_encode($json)); }
    }

    // Admin-only PHP information endpoint used by the dashboard modal.
    public function phpinfo() {
        if (!$this->user->hasPermission('modify', 'extension/dashboard/domovyk')) {
            $this->response->setStatusCode(403);
            return;
        }
        $this->load->language('extension/dashboard/domovyk');
        ob_start(); phpinfo(); $data['phpinfo'] = ob_get_clean();
        $data['heading_title'] = 'PHP Info';
        $data['phpinfo_download_link'] = $this->url->link('extension/dashboard/domovyk/phpinfoDownload', 'user_token=' . $this->session->data['user_token'], true);
        $data['phpinfo_download_text'] = $this->language->get('text_phpinfo_download');
        $data['button_close'] = $this->language->get('button_close');
        $data['phpinfo'] = preg_replace('@<style[^>]*?>.*?</style>@si', '', $data['phpinfo']);
        $this->response->setOutput($this->load->view('extension/dashboard/phpinfo', $data));
    }

    public function phpinfoDownload() {
        if (!$this->user->hasPermission('modify', 'extension/dashboard/domovyk')) {
            $this->response->setStatusCode(403);
            return;
        }
        ob_start(); phpinfo(); $html = (string)ob_get_clean();
        $filename = 'phpinfo-' . date('Ymd-His') . '.html';
        $this->response->addHeader('Content-Type: text/html; charset=UTF-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->addHeader('X-Content-Type-Options: nosniff');
        $this->response->setOutput($html);
    }

    private function getFilesStats($path) {
        $size = 0.0; $files = 0;
        if (!is_dir($path)) { return array('size'=>0, 'files'=>0); }
        try {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isFile() && !$file->isLink()) { $size += (float)$file->getSize(); $files++; }
            }
        } catch (\Throwable $e) { $this->log->write('Domovyk size scan failed: ' . $e->getMessage()); }
        return array('size'=>$size, 'files'=>$files);
    }

    private function formatSize($size) {
        $metrics = array($this->language->get('text_metrics_bit'), $this->language->get('text_metrics_kbit'), $this->language->get('text_metrics_mbit'), $this->language->get('text_metrics_gbit'), $this->language->get('text_metrics_tbit'));
        $metric = 0; $size = max(0, (float)$size);
        while ($size >= 1024 && $metric < count($metrics) - 1) { $metric++; $size /= 1024; }
        return array('size'=>round($size, 1), 'unit'=>$metrics[$metric]);
    }

    private function deletePath($dirname) {
        if (!file_exists($dirname) && !is_link($dirname)) { return; }
        if (is_dir($dirname) && !is_link($dirname)) {
            $items = scandir($dirname);
            if ($items) { foreach ($items as $item) { if ($item !== '.' && $item !== '..') { $this->deletePath($dirname . '/' . $item); } } }
            if (!@rmdir($dirname)) { $this->log->write('Domovyk cleanup: unable to delete directory ' . $dirname); }
        } elseif (!@unlink($dirname)) { $this->log->write('Domovyk cleanup: unable to delete ' . $dirname); }
    }
}
