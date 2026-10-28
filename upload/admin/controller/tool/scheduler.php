<?php
class ControllerToolScheduler extends Controller {
    public function index() {
        $this->load->language('tool/scheduler');
        $this->load->language('tool/diagnostics');
        $this->document->setTitle($this->language->get('heading_title'));

        $scheduler = new \CodeCart\Core\Scheduler($this->registry);

        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            $token = isset($this->request->get['user_token']) && is_string($this->request->get['user_token']) ? $this->request->get['user_token'] : '';
            $sessionToken = (string)($this->session->data['user_token'] ?? '');
            if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)
                || !$this->user->hasPermission('access', 'tool/scheduler') || !$this->user->hasPermission('modify', 'tool/scheduler')) {
                $this->session->data['error_warning'] = $this->language->get('error_permission');
            } else {
                try {
                    $this->processAction($scheduler);
                    unset($this->session->data['scheduler_form_old'], $this->session->data['scheduler_form_error']);
                } catch (\Throwable $e) {
                    $message = $this->safeDiagnostic($e->getMessage());
                    $this->session->data['error_warning'] = $message;
                    if (($this->request->post['scheduler_action'] ?? '') === 'save') {
                        $this->session->data['scheduler_form_error'] = $message;
                        $this->session->data['scheduler_form_old'] = array(
                            'scheduler_id' => max(0, (int)($this->request->post['scheduler_id'] ?? 0)),
                            'code' => substr((string)($this->request->post['code'] ?? ''), 0, 128),
                            'route' => substr((string)($this->request->post['route'] ?? ''), 0, 255),
                            'args' => substr((string)($this->request->post['args'] ?? '{}'), 0, 8192),
                            'interval_seconds' => (int)($this->request->post['interval_seconds'] ?? 3600),
                            'status' => !empty($this->request->post['status']) ? 1 : 0,
                            'available_handler' => substr((string)($this->request->post['available_handler'] ?? ''), 0, 128)
                        );
                    }
                }
            }
            $back = $this->url->link('tool/scheduler', 'user_token=' . $this->session->data['user_token'], true);
            $action = (string)($this->request->post['scheduler_action'] ?? '');
            if (!empty($this->session->data['scheduler_form_error'])) { $back .= '#scheduler-form'; }
            elseif ($action === 'save_external') { $back .= '#ccp-scheduler-tab-external'; }
            elseif (strpos($action, 'queue') !== false) { $back .= '#ccp-scheduler-tab-queue'; }
            $this->response->redirect($back);
            return;
        }

        $data['breadcrumbs'] = array(
            array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
            array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('tool/scheduler', 'user_token=' . $this->session->data['user_token'], true))
        );

        foreach (array(
            'heading_title','text_cli','text_web_cron','text_jobs','text_queue','text_queue_jobs','text_queue_worker_help','text_queue_empty','text_no_results','text_add_task','text_edit_task','text_core_task','text_enabled','text_disabled','text_success','text_last_result','text_duration','text_seconds','text_args_help','text_route_help','text_interval_help','text_queue_run_result','text_queue_pending','text_queue_processing','text_queue_failed','text_queue_done','text_all',
            'column_code','column_route','column_interval','column_status','column_last','column_next','column_result','column_action','column_queue_id','column_attempts','column_available','column_modified','column_error','entry_code','entry_route','entry_interval','entry_args','entry_status','entry_queue_search','entry_queue_status','entry_queue_limit','help_cron','button_close','button_copy','button_add','button_save','button_cancel','button_edit','button_delete','button_run','button_run_queue','button_queue_run','button_queue_retry','button_queue_delete','button_enable','button_disable','button_filter','button_reset','text_confirm','text_queue_confirm_delete','text_queue_one_result','error_queue_processing'
        ) as $key) {
            $data[$key] = $this->language->get($key);
        }

        $data['action'] = $this->url->link('tool/scheduler', 'user_token=' . $this->session->data['user_token'], true);
        $data['task_info_url'] = html_entity_decode($this->url->link('tool/scheduler/taskInfo', 'user_token=' . $this->session->data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['scheduler_examples'] = array(
            array('code' => 'custom.cart_cleanup', 'route' => 'cron/cart', 'interval' => 3600, 'key' => 'text_task_core_cart_cleanup'),
            array('code' => 'custom.health_check', 'route' => 'cron/health', 'interval' => 21600, 'key' => 'text_task_core_health_check'),
            array('code' => 'custom.security_cleanup', 'route' => 'cron/security', 'interval' => 86400, 'key' => 'text_task_core_security_cleanup')
        );
        foreach ($data['scheduler_examples'] as &$example) {
            $example['label'] = $this->language->get($example['key']);
        }
        unset($example);
        foreach (array('text_server_setup', 'text_server_interval', 'text_server_one_command', 'text_job_help', 'text_job_type', 'text_manual', 'text_choose_example', 'text_handler_warning',
          'text_session_cleanup', 'text_cache_note', 'text_extra_modules', 'text_json_example', 'text_job_code_help','text_existing_codes_info',
          'text_choose_available_handler','text_advanced_options','text_scheduler_health','text_scheduler_health_ok','text_scheduler_health_warn','text_scheduler_health_none',
          'text_last_execution_help','text_no_custom_handlers','text_scheduler_usage','text_scheduler_limit_info','text_ready_handlers_count','text_no_free_handler','text_save_failed','text_interval_preset','text_interval_custom','text_scheduler_host_unverified','text_scheduler_intro','text_create_task_hint','text_task_paused','text_native_task_type','text_host_task_type','text_host_command_label','text_host_command_help','text_host_schedule_help','text_host_copy','text_host_security','text_handler_select_help','text_advanced_identifiers','text_task_no_handler','text_host_minute','text_host_hour','text_host_dom','text_host_month','text_host_dow','text_job_name_header','text_external_import_title','text_external_import_help','text_external_import_command','text_external_import_frequency','text_external_import_submit','text_external_import_safety','text_external_import_secret_note','text_external_import_success','text_external_import_failed','text_external_job_name') as $key) {
            $data[$key] = $this->language->get($key);
        }
        foreach (array('text_scheduled_tab','text_external_tab','text_queue_tab','text_run_history','text_history_empty','text_history_task','text_history_source','text_history_result','text_history_date','text_history_duration','text_history_message','text_manual_run_disabled','text_real_frequency_help','text_host_source_cli','text_host_source_web','text_history_filter','text_host_failed','text_history_source_manual','text_history_source_visitor') as $key) {
            $data[$key] = $this->language->get($key);
        }
        $data['text_loading_task'] = $this->language->get('text_loading_task');
        $data['error_loading_task'] = $this->language->get('error_loading_task');
        $data['cancel'] = $data['action'];
        $data['user_token'] = $this->session->data['user_token'];
        $data['can_modify'] = $this->user->hasPermission('modify', 'tool/scheduler');

        $root = rtrim(dirname(DIR_APPLICATION), '/\\');
        $data['cli_command'] = 'php ' . $root . '/cli.php cron:run';
        $key = (string)$this->config->get('codecart_scheduler_key');
        $catalog = $this->getCatalogBaseUrl();
        $data['http_command'] = 'curl -fsS --max-time 120 ' . escapeshellarg($catalog . 'cron.php?key=' . rawurlencode($key));

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
        $data['history_rows'] = array();
        $data['history_total'] = 0;
        $data['history_filter_task'] = max(0, (int)($this->request->get['history_task'] ?? 0));
        $data['history_page'] = max(1, (int)($this->request->get['history_page'] ?? 1));
        $data['history_limit'] = 25;
        $data['queue_stats'] = array('pending'=>0,'processing'=>0,'failed'=>0,'done'=>0);
        $data['available_handlers'] = array();
        $data['scheduler_health'] = 'none';
        $data['scheduler_health_date'] = '';
        $data['scheduler_health_source'] = '';
        $data['scheduler_health_errors'] = 0;
        $data['history_pagination'] = '';
        $data['scheduler_form_error'] = (string)($this->session->data['scheduler_form_error'] ?? '');
        unset($this->session->data['scheduler_form_error']);
        try {
            $data['jobs'] = $scheduler->listTasks();
            foreach ($data['jobs'] as &$job) {
                $job['is_core'] = strpos((string)$job['code'], 'core.') === 0;
                $job['interval_text'] = $this->formatInterval((int)$job['interval_seconds']);
                $codeKey = 'text_task_' . str_replace('.', '_', (string)$job['code']);
                $codeDesc = $codeKey . '_description';
                $localizedName = $this->language->get($codeKey);
                $job['display_name'] = $localizedName !== $codeKey ? $localizedName : (string)$job['code'];
                if ($job['route'] === 'cron/codecart_external_http') {
                    $arguments = json_decode((string)$job['args'], true);
                    $hostname = parse_url((string)($arguments['url'] ?? ''), PHP_URL_HOST);
                    $job['display_name'] = $this->language->get('text_external_job_name') . ': ' . (string)$hostname;
                }
                $localizedDesc = $this->language->get($codeDesc);
                $job['description'] = $localizedDesc !== $codeDesc ? $localizedDesc : '';
                $job['last_status_text'] = $job['last_status'] === 'success' ? $this->language->get('text_result_success') : ($job['last_status'] === 'error' ? $this->language->get('text_result_error') : '');
                $job['last_message_localized'] = trim((string)$job['last_message']) !== '' ? $this->safeDiagnostic((string)$job['last_message']) : '';
            }
            unset($job);
            $registered = array();
            foreach ($data['jobs'] as $job) { $registered[(string)$job['route']] = true; }
            // The host heartbeat is written only by a real CLI or authenticated Web Cron.
            // Manual runs and visitor-triggered heartbeats never count as server Cron health.
            $heartbeat = $scheduler->hostHeartbeat();
            $lastRun = (string)($heartbeat['date_last'] ?? '');
            $age = isset($heartbeat['age_minutes']) ? (int)$heartbeat['age_minutes'] : 999999;
            $data['scheduler_health'] = !$lastRun ? 'none' : (($age >= 0 && $age <= 12 && ($heartbeat['last_status'] ?? '') === 'ok') ? 'ok' : 'warn');
            $data['scheduler_health_date'] = $lastRun;
            $data['scheduler_health_source'] = (string)($heartbeat['source'] ?? '');
            $data['scheduler_health_errors'] = (int)($heartbeat['failed'] ?? 0);
            $data['history_total'] = $scheduler->historyCount($data['history_filter_task']);
            $lastHistoryPage = max(1, (int)ceil($data['history_total'] / $data['history_limit']));
            $data['history_page'] = min($lastHistoryPage, $data['history_page']);
            $data['history_rows'] = $scheduler->history($data['history_page'], $data['history_limit'], $data['history_filter_task']);
            $historyBase = 'user_token=' . $this->session->data['user_token'] . '&history_task=' . (int)$data['history_filter_task'];
            $historyPagination = new Pagination();
            $historyPagination->total = $data['history_total'];
            $historyPagination->page = $data['history_page'];
            $historyPagination->limit = $data['history_limit'];
            $historyPagination->url = $this->url->link('tool/scheduler', $historyBase . '&history_page={page}', true) . '#ccp-scheduler-history';
            $data['history_pagination'] = $historyPagination->render();
            $data['history_filter_action'] = $this->url->link('tool/scheduler', '', true);
            $blocked = array('codecart','codecart_external_http','mail_delivery','cart_reminder','cart_guest_purge','relation','lost_url');
            foreach (glob(DIR_CATALOG . 'controller/cron/*.php') ?: array() as $handlerFile) {
                $handler = basename($handlerFile,'.php');
                if (!preg_match('/^[a-z0-9_]+$/i',$handler) || in_array($handler,$blocked,true)) { continue; }
                $source = file_get_contents($handlerFile);
                if ($source === false || !preg_match('/function\s+index\s*\(/i',$source)) { continue; }
                $route = 'cron/' . $handler;
                if (isset($registered[$route])) { continue; }
                $data['available_handlers'][] = array('route'=>$route,'code'=>'custom.' . $handler);
            }
            $stats = $this->db->query("SELECT status, COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_queue` GROUP BY status");
            foreach ($stats->rows as $row) { $data['queue_stats'][(string)$row['status']] = (int)$row['total']; }
        } catch (\Throwable $e) {
            $this->log->write('CodeCart scheduler list failure: ' . $e->getMessage());
            $data['warning'] = $this->safeDiagnostic($e->getMessage());
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
            foreach ($data['queue_jobs'] as &$queueJob) {
                $queueJob['last_error_localized'] = trim((string)$queueJob['last_error']) !== '' ? $this->safeDiagnostic((string)$queueJob['last_error']) : '';
            }
            unset($queueJob);
        } catch (\Throwable $e) {
            $this->log->write('CodeCart scheduler queue listing failed: ' . $e->getMessage());
            if ($data['warning'] === '') { $data['warning'] = $this->safeDiagnostic($e->getMessage()); }
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

        $data['frequency_presets'] = array();
        foreach (array(300=>5, 600=>10, 900=>15, 1800=>30, 3600=>1, 21600=>6, 43200=>12, 86400=>1) as $seconds=>$amount) {
            $unit = $seconds < 3600 ? 'text_interval_unit_minute' : ($seconds < 86400 ? 'text_interval_unit_hour' : 'text_interval_unit_day');
            $data['frequency_presets'][] = array('seconds'=>$seconds,'label'=>sprintf($this->language->get($unit), $amount));
        }
        $data['edit_task'] = array('scheduler_id'=>0,'code'=>'','route'=>'','args'=>'{}','interval_seconds'=>3600,'status'=>1,'is_core'=>false);
        if (!empty($this->request->get['edit_id'])) {
            $task = $scheduler->getTask((int)$this->request->get['edit_id']);
            if ($task) {
                $decoded = json_decode((string)$task['args'], true);
                $task['args'] = (string)$task['route'] === 'cron/codecart_external_http' ? '{}' : (is_array($decoded) ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '{}');
                $task['is_core'] = strpos((string)$task['code'], 'core.') === 0;
                $data['edit_task'] = $task;
            }
        }

        if (isset($this->session->data['scheduler_form_old']) && is_array($this->session->data['scheduler_form_old'])) {
            $old = $this->session->data['scheduler_form_old'];
            unset($this->session->data['scheduler_form_old']);
            $id = max(0, (int)($old['scheduler_id'] ?? 0));
            $original = $id > 0 ? $scheduler->getTask($id) : array();
            $data['edit_task'] = array(
                'scheduler_id' => $original ? $id : 0,
                'code' => (string)($old['code'] ?? ''),
                'route' => (string)($old['route'] ?? ''),
                'args' => (string)($old['args'] ?? '{}'),
                'interval_seconds' => (int)($old['interval_seconds'] ?? 3600),
                'status' => (int)($old['status'] ?? 0),
                'available_handler' => (string)($old['available_handler'] ?? ''),
                'is_core' => $original && strpos((string)$original['code'], 'core.') === 0
            );
        }
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('tool/scheduler', $data));
    }

    /**
     * Tolerate a partially deployed SystemNotification file or stale PHP OPcache.
     * Avoid throwing a second error while reporting the original scheduler error.
     */
    private function safeDiagnostic($message): string {
        $message = (string)$message;
        if (is_callable(array('\CodeCart\Core\SystemNotification', 'localizeDiagnostic'))) {
            try {
                return \CodeCart\Core\SystemNotification::localizeDiagnostic($message, $this->language);
            } catch (\Throwable $ignored) {
                // Use the original diagnostic if an outdated helper cannot run.
            }
        }
        $technical = $this->language->get('text_diag_technical_details');
        return $technical !== 'text_diag_technical_details' && strpos($technical, '%s') !== false
            ? sprintf($technical, $message) : $message;
    }

    public function taskInfo() {
        $this->load->language('tool/scheduler');
        $json = array('success' => false);
        $token = isset($this->request->get['user_token']) && is_string($this->request->get['user_token']) ? $this->request->get['user_token'] : '';
        $sessionToken = (string)($this->session->data['user_token'] ?? '');
        $taskId = (int)($this->request->get['scheduler_id'] ?? 0);
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Cache-Control: no-store, private');
        $this->response->addHeader('X-Content-Type-Options: nosniff');

        if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'GET' || $token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)
            || !$this->user->hasPermission('access', 'tool/scheduler') || !$this->user->hasPermission('modify', 'tool/scheduler')) {
            $this->response->setStatusCode(403);
            $json['error'] = $this->language->get('error_permission');
        } elseif ($taskId <= 0) {
            $this->response->setStatusCode(400);
            $json['error'] = $this->language->get('error_task');
        } else {
            try {
                $task = (new \CodeCart\Core\Scheduler($this->registry))->getTask($taskId);
                if (!$task) {
                    $this->response->setStatusCode(404);
                    $json['error'] = $this->language->get('error_task');
                } else {
                    $args = json_decode((string)$task['args'], true);
                    $json = array(
                        'success' => true,
                        'scheduler_id' => (int)$task['scheduler_id'],
                        'code' => (string)$task['code'],
                        'route' => (string)$task['route'],
                        'interval_seconds' => (int)$task['interval_seconds'],
                        'status' => (int)$task['status'],
                        'args' => (string)$task['route'] === 'cron/codecart_external_http' ? '{}' : (is_array($args) ? json_encode($args, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '[]'),
                        'is_core' => strpos((string)$task['code'], 'core.') === 0
                    );
                }
            } catch (\Throwable $e) {
                $this->response->setStatusCode(500);
                $json['error'] = $this->language->get('error_loading_task');
                $this->log->write('CodeCart scheduler taskInfo failed for #' . $taskId . ': ' . $e->getMessage());
            }
        }
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function processAction(\CodeCart\Core\Scheduler $scheduler): void {
        $action = isset($this->request->post['scheduler_action']) ? (string)$this->request->post['scheduler_action'] : '';
        $schedulerId = isset($this->request->post['scheduler_id']) ? (int)$this->request->post['scheduler_id'] : 0;

        if ($action === 'run_queue') {
            $result = $this->runCatalogQueue();
            if (!empty($result['success']) && (int)$result['failed'] === 0) {
                $this->session->data['success'] = sprintf($this->language->get('text_queue_run_result'), (int)$result['processed'], (int)$result['failed']);
            } else {
                $this->session->data['error_warning'] = sprintf($this->language->get('text_queue_run_result'), (int)$result['processed'], (int)$result['failed']) . ' ' . $this->safeDiagnostic((string)$result['message']);
            }
            return;
        }

        if ($action === 'run_queue_one') {
            $queueId = isset($this->request->post['queue_id']) ? (int)$this->request->post['queue_id'] : 0;
            $retry = !empty($this->request->post['retry']);
            if ($queueId < 1) { throw new \InvalidArgumentException($this->language->get('error_task')); }
            $result = $this->runCatalogQueueOne($queueId, $retry);
            $queueState = (string)$result['status'];
            $queueStateKey = 'text_queue_' . $queueState;
            $queueStateLabel = in_array($queueState, array('pending', 'processing', 'failed', 'done'), true) ? $this->language->get($queueStateKey) : $queueState;
            if (!empty($result['success'])) {
                $this->session->data['success'] = sprintf($this->language->get('text_queue_one_result'), $queueId, $queueStateLabel);
            } else {
                $this->session->data['error_warning'] = $this->safeDiagnostic((string)$result['message']);
            }
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

        if ($action === 'save_external') {
            if (!isset($this->request->post['external_command'])) {
                throw new \InvalidArgumentException($this->language->get('text_external_import_failed'));
            }
            $command = trim((string)$this->request->post['external_command']);
            $interval = (int)($this->request->post['external_interval'] ?? 900);
            if ($interval < 300 || $interval > 86400 || $interval % 300 !== 0) {
                throw new \InvalidArgumentException($this->language->get('error_interval'));
            }
            $remote = new \CodeCart\Core\ExternalHttpTask($this->registry);
            $endpoint = $remote->parseCommand($command);
            $code = 'external.http.' . substr(hash('sha256', $endpoint['url']), 0, 16);
            $scheduler->createTask($code, 'cron/codecart_external_http', $interval, false, array(
                'url' => $endpoint['url'],
                'key_enc' => $remote->protect($endpoint['secret'])
            ));
            $this->session->data['success'] = $this->language->get('text_external_import_success');
            return;
        }

        if ($action === 'save') {
            $code = isset($this->request->post['code']) ? trim((string)$this->request->post['code']) : '';
            $route = isset($this->request->post['route']) ? trim((string)$this->request->post['route']) : '';
            $interval = isset($this->request->post['interval_seconds']) ? (int)$this->request->post['interval_seconds'] : 0;
            if ($schedulerId === 0) {
                // The chosen installed handler is the sole source for the route and task code.
                $selected = trim((string)($this->request->post['available_handler'] ?? ''));
                if (!preg_match('#^cron/([a-z0-9_]+)$#i', $selected, $match)
                    || in_array(strtolower($match[1]), array('codecart','mail_delivery','cart_reminder','cart_guest_purge','relation','lost_url'), true)) {
                    throw new \InvalidArgumentException($this->language->get('text_task_no_handler'));
                }
                $handlerFile = DIR_CATALOG . 'controller/cron/' . $match[1] . '.php';
                if (!is_file($handlerFile)) {
                    throw new \InvalidArgumentException($this->language->get('error_route_missing'));
                }
                $source = file_get_contents($handlerFile);
                if ($source === false || !preg_match('/function\s+index\s*\(/i', $source)) {
                    throw new \InvalidArgumentException($this->language->get('error_route_missing'));
                }
                $route = $selected;
                $code = 'custom.' . strtolower($match[1]);
            }
            $status = !empty($this->request->post['status']);
            $rawArgs = isset($this->request->post['args']) ? trim((string)$this->request->post['args']) : '{}';
            if ($rawArgs === '') { $rawArgs = '{}'; }
            $args = json_decode($rawArgs, true);
            if (!is_array($args) || json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException($this->language->get('error_args'));
            }
            // Only existing catalog Cron handlers may be scheduled from admin.
            // Arbitrary admin routes and script/URL commands are not supported.
            if (!preg_match('#^cron/[a-z0-9_]+(?:/(?:index|batch))?$#i', $route) ||
                in_array($route, array('cron/codecart', 'cron/mail_delivery', 'cron/relation/batch'), true)) {
                throw new \InvalidArgumentException($this->language->get('error_route_allowed'));
            }
            $parts = explode('/', $route);
            $handlerFile = DIR_CATALOG . 'controller/cron/' . $parts[1] . '.php';
            if (!is_file($handlerFile)) {
                throw new \InvalidArgumentException($this->language->get('error_route_missing'));
            }
            if ($schedulerId < 1 && strpos($code, 'core.') === 0) {
                throw new \InvalidArgumentException($this->language->get('error_reserved_code'));
            }
            if ($schedulerId > 0) {
                $oldTask = $scheduler->getTask($schedulerId);
                if ($oldTask && strpos((string)$oldTask['code'], 'core.') === 0 && (string)$oldTask['route'] !== $route) {
                    throw new \InvalidArgumentException($this->language->get('error_core_route'));
                }
            }
            foreach ($scheduler->listTasks() as $existingTask) {
                if ((int)$existingTask['scheduler_id'] === $schedulerId) { continue; }
                if (strcasecmp((string)$existingTask['code'], $code) === 0) {
                    throw new \InvalidArgumentException($this->language->get('error_duplicate_code'));
                }
                if (rtrim((string)$existingTask['route'], '/') === rtrim($route, '/')
                    && !($schedulerId > 0 && $route === 'cron/codecart_external_http')) {
                    throw new \InvalidArgumentException($this->language->get('error_duplicate_route'));
                }
            }
            if ($schedulerId > 0) {
                $existing = $scheduler->getTask($schedulerId);
                if ($existing && (string)$existing['route'] === 'cron/codecart_external_http') {
                    if ((string)$existing['code'] !== $code || $route !== 'cron/codecart_external_http') {
                        throw new \InvalidArgumentException($this->language->get('error_core_route'));
                    }
                    $protectedArgs = json_decode((string)$existing['args'], true);
                    if (!is_array($protectedArgs)) { throw new \RuntimeException('Protected task configuration is invalid.'); }
                    $args = $protectedArgs; // Editing a task never drops or exposes the encrypted key.
                }
            }
            if ($interval < ($schedulerId === 0 ? 300 : 60) || $interval > 31536000) {
                throw new \InvalidArgumentException($this->language->get('error_interval'));
            }
            if ($schedulerId > 0) {
                $scheduler->save($schedulerId, $code, $route, $interval, $status, $args);
            } else {
                $scheduler->createTask($code, $route, $interval, $status, $args);
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
            if (!empty($result['success'])) {
                $this->session->data['success'] = $this->language->get('text_run_result') . ': ' . $this->safeDiagnostic((string)$result['message']) . ', ' . (int)$result['duration_ms'] . ' ms';
            } else {
                $this->session->data['error_warning'] = $this->safeDiagnostic((string)$result['message']);
            }
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
        $ch=curl_init($url); curl_setopt_array($ch,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>120,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_ENCODING=>'',CURLOPT_HTTPHEADER=>array('Accept: application/json')));
        \CodeCart\Core\TlsPolicy::applyCurl($ch);
        $body=curl_exec($ch); $error=curl_error($ch); $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        if ($body !== false && in_array($status, array(301, 302, 303, 307, 308), true)) {
            list($body, $error, $status) = $this->retryCanonicalCronRedirect($url, $ch);
        }

        // CurlHandle is released automatically; curl_close() is deprecated in PHP 8.5.
        if ($body===false || $status<200 || $status>=300) return array('success'=>false,'status'=>'error','message'=>$error!==''?$error:(in_array($status, array(301,302,303,307,308), true) ? $this->language->get('error_scheduler_redirect') : 'HTTP '.$status),'duration_ms'=>0);
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
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_MAXREDIRS=>0, CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>120, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_ENCODING=>'', CURLOPT_HTTPHEADER=>array('Accept: application/json')));
        \CodeCart\Core\TlsPolicy::applyCurl($ch);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($body !== false && in_array($status, array(301, 302, 303, 307, 308), true)) {
            list($body, $error, $status) = $this->retryCanonicalCronRedirect($url, $ch);
        }

        if ($body === false || $status < 200 || $status >= 300) {
            return array('success'=>false, 'processed'=>0, 'failed'=>0, 'message'=>$error !== '' ? $error : (in_array($status, array(301,302,303,307,308), true) ? $this->language->get('error_scheduler_redirect') : 'HTTP ' . $status));
        }
        $json = json_decode(trim((string)$body), true);
        if (!is_array($json) || !isset($json['queue']) || !is_array($json['queue'])) {
            return array('success'=>false, 'processed'=>0, 'failed'=>0, 'message'=>'Invalid queue worker response (HTTP ' . $status . ').');
        }
        return array(
            'success' => isset($json['queue']['status']) && (string)$json['queue']['status'] === 'ok' && (int)($json['queue']['failed'] ?? 0) === 0,
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
        curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_MAXREDIRS=>0, CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>120, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_ENCODING=>'', CURLOPT_HTTPHEADER=>array('Accept: application/json')));
        \CodeCart\Core\TlsPolicy::applyCurl($ch);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($body !== false && in_array($status, array(301, 302, 303, 307, 308), true)) {
            list($body, $error, $status) = $this->retryCanonicalCronRedirect($url, $ch);
        }

        if ($body === false || $status < 200 || $status >= 300) {
            return array('success'=>false, 'status'=>'error', 'message'=>$error !== '' ? $error : (in_array($status, array(301,302,303,307,308), true) ? $this->language->get('error_scheduler_redirect') : 'HTTP ' . $status));
        }
        $json = json_decode(trim((string)$body), true);
        if (!is_array($json) || !isset($json['queue_one']) || !is_array($json['queue_one'])) {
            return array('success'=>false, 'status'=>'error', 'message'=>'Invalid queue worker response (HTTP ' . $status . ').');
        }
        $row = $json['queue_one'];
        return array(
            'success' => isset($row['status']) && (string)$row['status'] === 'ok' && (int)($row['failed'] ?? 0) === 0,
            'status' => isset($row['status']) ? (string)$row['status'] : 'error',
            'message' => isset($row['message']) ? (string)$row['message'] : ''
        );
    }

    /**
     * Retry one HTTP redirect only when it changes the canonical HTTPS host
     * between www/non-www. Never send the Cron secret to arbitrary redirect URLs.
     * Location query parameters are deliberately ignored.
     */
    private function retryCanonicalCronRedirect(string $originalUrl, $handle): array {
        $location = (string)curl_getinfo($handle, CURLINFO_REDIRECT_URL);
        $source = parse_url($originalUrl);
        $target = $location !== '' && !preg_match('/[\x00-\x20\x7f]/', $location) ? parse_url($location) : false;
        $fail = array(false, 'HTTP 301: redirect is not a trusted HTTPS Cron endpoint. Configure the canonical storefront URL.', 301);
        if (!is_array($source) || !is_array($target)
            || strtolower((string)($source['scheme'] ?? '')) !== 'https'
            || strtolower((string)($target['scheme'] ?? '')) !== 'https'
            || empty($source['host']) || empty($source['path']) || empty($source['query'])
            || empty($target['host']) || (string)($target['path'] ?? '') !== (string)$source['path']
            || isset($target['user']) || isset($target['pass']) || isset($target['fragment'])
            || (isset($target['port']) && (int)$target['port'] !== 443)) {
            return $fail;
        }
        $oldHost = strtolower((string)$source['host']);
        $newHost = strtolower((string)$target['host']);
        $normalize = function ($host) {
            return strpos($host, 'www.') === 0 ? substr($host, 4) : $host;
        };
        if ($normalize($oldHost) !== $normalize($newHost)) { return $fail; }
        $retryUrl = 'https://' . $newHost . $source['path'] . '?' . $source['query'];
        if ($retryUrl === $originalUrl || !function_exists('curl_init')) { return $fail; }
        $retry = curl_init($retryUrl);
        if (!$retry) { return array(false, 'Unable to initialize HTTPS Cron request.', 0); }
        curl_setopt_array($retry, array(
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 0, CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 120, CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => array('Accept: application/json')
        ));
        \CodeCart\Core\TlsPolicy::applyCurl($retry);
        $body = curl_exec($retry);
        $error = (string)curl_error($retry);
        $status = (int)curl_getinfo($retry, CURLINFO_HTTP_CODE);
        return array($body, $error, $status);
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

