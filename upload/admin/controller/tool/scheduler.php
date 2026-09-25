<?php
class ControllerToolScheduler extends Controller {
    public function index() {
        $this->load->language('tool/scheduler');
        $this->document->setTitle($this->language->get('heading_title'));

        $scheduler = new \CodeCart\Core\Scheduler($this->registry);

        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            if (!$this->user->hasPermission('modify', 'tool/scheduler')) {
                $this->session->data['error_warning'] = $this->language->get('error_permission');
            } else {
                try {
                    $this->processAction($scheduler);
                } catch (\Throwable $e) {
                    $this->session->data['error_warning'] = $e->getMessage();
                }
            }
            $this->response->redirect($this->url->link('tool/scheduler', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $data['breadcrumbs'] = array(
            array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
            array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('tool/scheduler', 'user_token=' . $this->session->data['user_token'], true))
        );

        foreach (array(
            'heading_title','text_cli','text_web_cron','text_jobs','text_queue','text_queue_jobs','text_queue_worker_help','text_queue_empty','text_no_results','text_add_task','text_edit_task','text_core_task','text_enabled','text_disabled','text_success','text_last_result','text_duration','text_seconds','text_args_help','text_route_help','text_interval_help','text_queue_run_result','text_queue_pending','text_queue_processing','text_queue_failed','text_queue_done','text_all',
            'column_code','column_route','column_interval','column_status','column_last','column_next','column_result','column_action','column_queue_id','column_attempts','column_available','column_modified','column_error','entry_code','entry_route','entry_interval','entry_args','entry_status','entry_queue_search','entry_queue_status','entry_queue_limit','help_cron','button_copy','button_add','button_save','button_cancel','button_edit','button_delete','button_run','button_run_queue','button_queue_run','button_queue_retry','button_queue_delete','button_enable','button_disable','button_filter','button_reset','text_confirm','text_queue_confirm_delete','text_queue_one_result','error_queue_processing'
        ) as $key) {
            $data[$key] = $this->language->get($key);
        }

        $data['action'] = $this->url->link('tool/scheduler', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $data['action'];
        $data['user_token'] = $this->session->data['user_token'];
        $data['can_modify'] = $this->user->hasPermission('modify', 'tool/scheduler');

        $root = rtrim(dirname(DIR_APPLICATION), '/\\');
        $data['cli_command'] = 'php ' . $root . '/cli.php cron:run';
        $key = (string)$this->config->get('codecart_scheduler_key');
        $catalog = $this->getCatalogBaseUrl();
        $data['http_command'] = 'curl -fsSL --max-redirs 3 --max-time 120 ' . escapeshellarg($catalog . 'cron.php?key=' . rawurlencode($key));

        $data['warning'] = '';
        if (!empty($this->session->data['error_warning'])) {
            $data['warning'] = $this->session->data['error_warning'];
            unset($this->session->data['error_warning']);
        }
        $data['success'] = '';
        if (!empty($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }

        $data['jobs'] = array();
        $data['queue_stats'] = array('pending'=>0,'processing'=>0,'failed'=>0,'done'=>0);
        try {
            $data['jobs'] = $scheduler->listTasks();
            foreach ($data['jobs'] as &$job) {
                $job['is_core'] = strpos((string)$job['code'], 'core.') === 0;
                $job['interval_text'] = $this->formatInterval((int)$job['interval_seconds']);
            }
            unset($job);
            $stats = $this->db->query("SELECT status, COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_queue` GROUP BY status");
            foreach ($stats->rows as $row) { $data['queue_stats'][(string)$row['status']] = (int)$row['total']; }
        } catch (\Throwable $e) {
            $data['warning'] = $e->getMessage();
        }

        $queueStatus = isset($this->request->get['queue_status']) ? (string)$this->request->get['queue_status'] : '';
        if (!in_array($queueStatus, array('', 'pending', 'processing', 'failed', 'done'), true)) { $queueStatus = ''; }
        $queueSearch = isset($this->request->get['queue_search']) ? trim((string)$this->request->get['queue_search']) : '';
        if (function_exists('utf8_substr')) { $queueSearch = utf8_substr($queueSearch, 0, 128); } else { $queueSearch = substr($queueSearch, 0, 128); }
        $queueLimit = isset($this->request->get['queue_limit']) ? (int)$this->request->get['queue_limit'] : 25;
        if (!in_array($queueLimit, array(10, 25, 50, 100), true)) { $queueLimit = 25; }
        $queuePage = isset($this->request->get['queue_page']) ? max(1, (int)$this->request->get['queue_page']) : 1;
        $queueSort = isset($this->request->get['queue_sort']) ? (string)$this->request->get['queue_sort'] : 'status';
        $queueSortMap = array(
            'queue_id' => 'queue_id',
            'code' => 'code',
            'route' => 'route',
            'status' => 'status',
            'attempts' => 'attempts',
            'available_at' => 'available_at',
            'date_modified' => 'date_modified'
        );
        if (!isset($queueSortMap[$queueSort])) { $queueSort = 'status'; }
        if (isset($this->request->get['queue_order'])) {
            $queueOrder = strtoupper((string)$this->request->get['queue_order']) === 'ASC' ? 'ASC' : 'DESC';
        } else {
            // Default status order keeps processing/pending/failed visible before history.
            $queueOrder = $queueSort === 'status' ? 'ASC' : 'DESC';
        }
        $queueWhere = array();
        if ($queueStatus !== '') { $queueWhere[] = "status = '" . $this->db->escape($queueStatus) . "'"; }
        if ($queueSearch !== '') {
            $escapedSearch = $this->db->escape($queueSearch);
            $queueWhere[] = "(code LIKE '%" . $escapedSearch . "%' OR route LIKE '%" . $escapedSearch . "%')";
        }
        $queueWhereSql = $queueWhere ? ' WHERE ' . implode(' AND ', $queueWhere) : '';
        $data['queue_jobs'] = array();
        $data['queue_total'] = 0;
        try {
            $queueCount = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_queue`" . $queueWhereSql);
            $data['queue_total'] = isset($queueCount->row['total']) ? (int)$queueCount->row['total'] : 0;
            $queueStart = ($queuePage - 1) * $queueLimit;
            if ($queueSort === 'status') {
                $queueOrderSql = "FIELD(status,'processing','pending','failed','done') " . ($queueOrder === 'ASC' ? 'ASC' : 'DESC') . ", priority ASC, queue_id DESC";
            } else {
                $queueOrderSql = $queueSortMap[$queueSort] . ' ' . $queueOrder . ', queue_id DESC';
            }
            $queueRows = $this->db->query("SELECT queue_id, code, route, priority, status, attempts, max_attempts, available_at, locked_at, last_error, date_added, date_modified FROM `" . DB_PREFIX . "codecart_queue`" . $queueWhereSql . " ORDER BY " . $queueOrderSql . " LIMIT " . (int)$queueStart . "," . (int)$queueLimit);
            $data['queue_jobs'] = $queueRows->rows;
        } catch (\Throwable $e) {
            if ($data['warning'] === '') { $data['warning'] = $e->getMessage(); }
        }
        $data['queue_filter_status'] = $queueStatus;
        $data['queue_filter_search'] = $queueSearch;
        $data['queue_limit'] = $queueLimit;
        $data['queue_sort'] = $queueSort;
        $data['queue_order'] = $queueOrder;
        $queueBase = 'user_token=' . $this->session->data['user_token'] . '&queue_status=' . urlencode($queueStatus) . '&queue_search=' . urlencode($queueSearch) . '&queue_limit=' . (int)$queueLimit . '&queue_sort=' . urlencode($queueSort) . '&queue_order=' . urlencode($queueOrder);
        $queuePagination = new Pagination();
        $queuePagination->total = $data['queue_total'];
        $queuePagination->page = $queuePage;
        $queuePagination->limit = $queueLimit;
        $queuePagination->url = $this->url->link('tool/scheduler', $queueBase . '&queue_page={page}', true);
        $data['queue_pagination'] = $queuePagination->render();
        $data['queue_results'] = sprintf($this->language->get('text_pagination'), ($data['queue_total']) ? (($queuePage - 1) * $queueLimit) + 1 : 0, ((($queuePage - 1) * $queueLimit) > ($data['queue_total'] - $queueLimit)) ? $data['queue_total'] : ((($queuePage - 1) * $queueLimit) + $queueLimit), $data['queue_total'], max(1, (int)ceil($data['queue_total'] / $queueLimit)));
        $data['queue_reset'] = $this->url->link('tool/scheduler', 'user_token=' . $this->session->data['user_token'], true);
        $queueSortBase = 'user_token=' . $this->session->data['user_token'] . '&queue_status=' . urlencode($queueStatus) . '&queue_search=' . urlencode($queueSearch) . '&queue_limit=' . (int)$queueLimit;
        $data['queue_sort_links'] = array();
        foreach (array('queue_id','code','route','status','attempts','available_at','date_modified') as $sortKey) {
            $nextOrder = ($queueSort === $sortKey && $queueOrder === 'ASC') ? 'DESC' : 'ASC';
            $data['queue_sort_links'][$sortKey] = $this->url->link('tool/scheduler', $queueSortBase . '&queue_sort=' . urlencode($sortKey) . '&queue_order=' . $nextOrder, true);
        }

        $data['edit_task'] = array('scheduler_id'=>0,'code'=>'','route'=>'','args'=>'{}','interval_seconds'=>3600,'status'=>1,'is_core'=>false);
        if (!empty($this->request->get['edit_id'])) {
            $task = $scheduler->getTask((int)$this->request->get['edit_id']);
            if ($task) {
                $decoded = json_decode((string)$task['args'], true);
                $task['args'] = is_array($decoded) ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '{}';
                $task['is_core'] = strpos((string)$task['code'], 'core.') === 0;
                $data['edit_task'] = $task;
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('tool/scheduler', $data));
    }

    private function processAction(\CodeCart\Core\Scheduler $scheduler): void {
        $action = isset($this->request->post['scheduler_action']) ? (string)$this->request->post['scheduler_action'] : '';
        $schedulerId = isset($this->request->post['scheduler_id']) ? (int)$this->request->post['scheduler_id'] : 0;

        if ($action === 'run_queue') {
            $result = $this->runCatalogQueue();
            $this->session->data['success'] = sprintf($this->language->get('text_queue_run_result'), (int)$result['processed'], (int)$result['failed']);
            if (empty($result['success'])) { $this->session->data['error_warning'] = $result['message']; }
            return;
        }

        if ($action === 'run_queue_one') {
            $queueId = isset($this->request->post['queue_id']) ? (int)$this->request->post['queue_id'] : 0;
            $retry = !empty($this->request->post['retry']);
            if ($queueId < 1) { throw new \InvalidArgumentException($this->language->get('error_task')); }
            $result = $this->runCatalogQueueOne($queueId, $retry);
            $this->session->data['success'] = sprintf($this->language->get('text_queue_one_result'), $queueId, (string)$result['status']);
            if (empty($result['success'])) { $this->session->data['error_warning'] = $result['message']; }
            return;
        }

        if ($action === 'delete_queue_one') {
            $queueId = isset($this->request->post['queue_id']) ? (int)$this->request->post['queue_id'] : 0;
            if ($queueId < 1) { throw new \InvalidArgumentException($this->language->get('error_task')); }
            $queue = new \CodeCart\Core\Queue($this->registry);
            try {
                $deleted = $queue->deleteById($queueId);
            } catch (\RuntimeException $e) {
                throw new \RuntimeException($this->language->get('error_queue_processing'));
            }
            if (!$deleted) { throw new \InvalidArgumentException($this->language->get('error_task')); }
            $this->session->data['success'] = $this->language->get('text_success');
            return;
        }

        if ($action === 'save') {
            $code = isset($this->request->post['code']) ? trim((string)$this->request->post['code']) : '';
            $route = isset($this->request->post['route']) ? trim((string)$this->request->post['route']) : '';
            $interval = isset($this->request->post['interval_seconds']) ? (int)$this->request->post['interval_seconds'] : 0;
            $status = !empty($this->request->post['status']);
            $rawArgs = isset($this->request->post['args']) ? trim((string)$this->request->post['args']) : '{}';
            if ($rawArgs === '') { $rawArgs = '{}'; }
            $args = json_decode($rawArgs, true);
            if (!is_array($args) || json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException($this->language->get('error_args'));
            }
            if ($interval < 60 || $interval > 31536000) {
                throw new \InvalidArgumentException($this->language->get('error_interval'));
            }
            if ($schedulerId > 0) {
                $scheduler->save($schedulerId, $code, $route, $interval, $status, $args);
            } else {
                $scheduler->register($code, $route, $interval, $status, $args);
            }
            $this->session->data['success'] = $this->language->get('text_success');
            return;
        }

        if ($schedulerId < 1) {
            throw new \InvalidArgumentException($this->language->get('error_task'));
        }
        if ($action === 'toggle') {
            $scheduler->setStatus($schedulerId, !empty($this->request->post['status']));
            $this->session->data['success'] = $this->language->get('text_success');
        } elseif ($action === 'run') {
            $result = $this->runCatalogTask($schedulerId);
            $this->session->data['success'] = $this->language->get('text_run_result') . ': ' . $result['status'] . ', ' . (int)$result['duration_ms'] . ' ms';
            if (empty($result['success'])) { $this->session->data['error_warning'] = $result['message']; }
        } elseif ($action === 'delete') {
            $scheduler->deleteTask($schedulerId);
            $this->session->data['success'] = $this->language->get('text_success');
        } else {
            throw new \InvalidArgumentException($this->language->get('error_action'));
        }
    }

    private function runCatalogTask(int $schedulerId): array {
        $key=(string)$this->config->get('codecart_scheduler_key');
        $catalog=$this->getCatalogBaseUrl();
        $url=$catalog.'cron.php?key='.rawurlencode($key).'&mode=scheduler_one&scheduler_id='.(int)$schedulerId;
        if (!function_exists('curl_init')) return array('success'=>false,'status'=>'error','message'=>'cURL is required for Run Now from admin. The normal CLI/HTTP scheduler is not affected.','duration_ms'=>0);
        $ch=curl_init($url); curl_setopt_array($ch,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>3,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>120,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_ENCODING=>'',CURLOPT_HTTPHEADER=>array('Accept: application/json')));
        \CodeCart\Core\TlsPolicy::applyCurl($ch);
        $body=curl_exec($ch); $error=curl_error($ch); $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        // CurlHandle is released automatically; curl_close() is deprecated in PHP 8.5.
        if ($body===false || $status<200 || $status>=300) return array('success'=>false,'status'=>'error','message'=>$error!==''?$error:'HTTP '.$status,'duration_ms'=>0);
        $body=trim((string)$body); $json=json_decode($body,true); if (!is_array($json) || !isset($json['scheduler_one']) || !is_array($json['scheduler_one'])) { $contentType=(string)curl_getinfo($ch,CURLINFO_CONTENT_TYPE); return array('success'=>false,'status'=>'error','message'=>'Invalid scheduler response (HTTP '.(int)$status.($contentType!==''?', '.$contentType:'').').','duration_ms'=>0); }
        return $json['scheduler_one'];
    }

    private function runCatalogQueue(): array {
        $key = (string)$this->config->get('codecart_scheduler_key');
        $catalog = $this->getCatalogBaseUrl();
        $url = $catalog . 'cron.php?key=' . rawurlencode($key) . '&mode=queue';
        if (!function_exists('curl_init')) {
            return array('success'=>false, 'processed'=>0, 'failed'=>0, 'message'=>'cURL is required for Run Queue Now from admin. The normal CLI/HTTP worker is not affected.');
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_MAXREDIRS=>3, CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>120, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_ENCODING=>'', CURLOPT_HTTPHEADER=>array('Accept: application/json')));
        \CodeCart\Core\TlsPolicy::applyCurl($ch);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($body === false || $status < 200 || $status >= 300) {
            return array('success'=>false, 'processed'=>0, 'failed'=>0, 'message'=>$error !== '' ? $error : 'HTTP ' . $status);
        }
        $json = json_decode(trim((string)$body), true);
        if (!is_array($json) || !isset($json['queue']) || !is_array($json['queue'])) {
            return array('success'=>false, 'processed'=>0, 'failed'=>0, 'message'=>'Invalid queue worker response (HTTP ' . $status . ').');
        }
        return array(
            'success' => isset($json['queue']['status']) && (string)$json['queue']['status'] === 'ok',
            'processed' => isset($json['queue']['processed']) ? (int)$json['queue']['processed'] : 0,
            'failed' => isset($json['queue']['failed']) ? (int)$json['queue']['failed'] : 0,
            'message' => isset($json['queue']['status']) ? (string)$json['queue']['status'] : 'ok'
        );
    }

    private function runCatalogQueueOne(int $queueId, bool $retry): array {
        $key = (string)$this->config->get('codecart_scheduler_key');
        $catalog = $this->getCatalogBaseUrl();
        $url = $catalog . 'cron.php?key=' . rawurlencode($key) . '&mode=queue_one&queue_id=' . (int)$queueId . ($retry ? '&retry=1' : '');
        if (!function_exists('curl_init')) {
            return array('success'=>false, 'status'=>'error', 'message'=>'cURL is required for Run Now from admin. The normal CLI/HTTP worker is not affected.');
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_MAXREDIRS=>3, CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>120, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_ENCODING=>'', CURLOPT_HTTPHEADER=>array('Accept: application/json')));
        \CodeCart\Core\TlsPolicy::applyCurl($ch);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($body === false || $status < 200 || $status >= 300) {
            return array('success'=>false, 'status'=>'error', 'message'=>$error !== '' ? $error : 'HTTP ' . $status);
        }
        $json = json_decode(trim((string)$body), true);
        if (!is_array($json) || !isset($json['queue_one']) || !is_array($json['queue_one'])) {
            return array('success'=>false, 'status'=>'error', 'message'=>'Invalid queue worker response (HTTP ' . $status . ').');
        }
        $row = $json['queue_one'];
        return array(
            'success' => isset($row['status']) && in_array((string)$row['status'], array('ok','skipped'), true),
            'status' => isset($row['status']) ? (string)$row['status'] : 'error',
            'message' => isset($row['message']) ? (string)$row['message'] : ''
        );
    }

    private function formatInterval(int $seconds): string {
        if ($seconds % 86400 === 0) { return ($seconds / 86400) . ' d'; }
        if ($seconds % 3600 === 0) { return ($seconds / 3600) . ' h'; }
        if ($seconds % 60 === 0) { return ($seconds / 60) . ' min'; }
        return $seconds . ' s';
    }

    private function getCatalogBaseUrl(): string {
        $candidates = array();
        if (defined('HTTPS_CATALOG')) { $candidates[] = (string)HTTPS_CATALOG; }
        if (defined('HTTP_CATALOG')) { $candidates[] = (string)HTTP_CATALOG; }
        $candidates[] = (string)$this->config->get('config_ssl');
        $candidates[] = (string)$this->config->get('config_url');

        // Prefer a canonical HTTPS storefront URL. This avoids the common admin "Run now"
        // failure where an HTTP catalog URL returns 301 before cron.php is reached.
        foreach (array('https', 'http') as $scheme) {
            foreach ($candidates as $candidate) {
                $candidate = trim($candidate);
                $parts = $candidate !== '' ? parse_url($candidate) : false;
                if (is_array($parts) && !empty($parts['host']) && strtolower((string)($parts['scheme'] ?? '')) === $scheme) {
                    return rtrim($candidate, '/') . '/';
                }
            }
        }

        $https = !empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off';
        $host = isset($this->request->server['HTTP_HOST']) ? preg_replace('/[^A-Za-z0-9.:-]/', '', (string)$this->request->server['HTTP_HOST']) : '';
        if ($host !== '') {
            $script = isset($this->request->server['SCRIPT_NAME']) ? str_replace('\\', '/', (string)$this->request->server['SCRIPT_NAME']) : '/admin/index.php';
            $dir = str_replace('\\', '/', dirname($script));
            if (strtolower(basename($dir)) === 'admin') {
                $dir = dirname($dir);
            }
            $path = trim(str_replace('\\', '/', $dir), '/.');
            return ($https ? 'https://' : 'http://') . $host . ($path !== '' ? '/' . $path . '/' : '/');
        }

        throw new \RuntimeException($this->language->get('error_catalog_url'));
    }

}

