<?php
class ControllerCronCodeCart extends Controller {
    public function index($args = array()) {
        if (!$this->isAuthorized()) {
            $this->response->setStatusCode(403);
            $this->response->addHeader('Content-Type: text/plain; charset=utf-8');
            $this->response->setOutput('Forbidden');
            return 'Forbidden';
        }

        // Detached heartbeat/loopback and external cron callers may disconnect
        // immediately; scheduled work must still complete.
        @ignore_user_abort(true);
        if (!empty($this->request->get['heartbeat'])) {
            \CodeCart\Core\Heartbeat::markLoopbackRun();
        }

        $scheduler = new \CodeCart\Core\Scheduler($this->registry);
        $queue = new \CodeCart\Core\Queue($this->registry);
        $mode = isset($this->request->get['mode']) ? (string)$this->request->get['mode'] : 'all';
        // Explicit server Cron runs are tracked separately from visitor-triggered loopbacks.
        $source = (PHP_SAPI === 'cli' || (defined('CODECART_CLI') && CODECART_CLI))
            ? 'cli' : (!empty($this->request->get['heartbeat']) ? 'visitor' : 'web');

        if ($mode === 'scheduler') {
            $result = array('scheduler' => $scheduler->runDue(20, $source));
        } elseif ($mode === 'queue') {
            $result = array('queue' => $queue->run(25));
        } elseif ($mode === 'queue_one') {
            $queueId = isset($this->request->get['queue_id']) ? (int)$this->request->get['queue_id'] : 0;
            $retry = !empty($this->request->get['retry']);
            if ($queueId < 1) {
                $result = array('queue_one' => array('status' => 'error', 'processed' => 0, 'failed' => 0, 'message' => 'Invalid queue_id'));
            } else {
                try { $result = array('queue_one' => $queue->runOne($queueId, $retry)); }
                catch (\Throwable $e) { $result = array('queue_one' => array('status' => 'error', 'processed' => 0, 'failed' => 1, 'message' => $e->getMessage())); }
            }
        } elseif ($mode === 'queue_purge') {
            $hours = isset($this->request->get['hours']) ? (int)$this->request->get['hours'] : 24;
            $result = array('queue_purge' => array('deleted' => $queue->purgeCompleted($hours), 'stats' => $queue->stats()));
        } elseif ($mode === 'scheduler_list' && (PHP_SAPI === 'cli' || (defined('CODECART_CLI') && CODECART_CLI))) {
            $result = array('scheduler' => $scheduler->listTasks(), 'queue' => $queue->stats());
        } elseif ($mode === 'scheduler_one') {
            $schedulerId = isset($this->request->get['scheduler_id']) ? (int)$this->request->get['scheduler_id'] : 0;
            if ($schedulerId < 1) {
                $result = array('scheduler_one' => array('success' => false, 'status' => 'error', 'message' => 'Invalid scheduler_id', 'duration_ms' => 0));
            } else {
                try { $result = array('scheduler_one' => $scheduler->runOne($schedulerId)); }
                catch (\Throwable $e) { $result = array('scheduler_one' => array('success' => false, 'status' => 'error', 'message' => $e->getMessage(), 'duration_ms' => 0)); }
            }
        } elseif ($mode === 'key' && (PHP_SAPI === 'cli' || (defined('CODECART_CLI') && CODECART_CLI))) {
            $result = array('codecart_scheduler_key' => (string)$this->config->get('codecart_scheduler_key'));
        } elseif ($mode === 'db_preflight' && (PHP_SAPI === 'cli' || (defined('CODECART_CLI') && CODECART_CLI))) {
            $modernizer = new \CodeCart\Core\DatabaseModernizer($this->registry);
            $result = array('database' => $modernizer->preflight());
        } elseif ($mode === 'styles_rebuild' && (PHP_SAPI === 'cli' || (defined('CODECART_CLI') && CODECART_CLI))) {
            $compiler = new \CodeCart\Core\StyleCompiler();
            $result = array('styles' => $compiler->rebuild());
        } elseif ($mode === 'db_migrate' && (PHP_SAPI === 'cli' || (defined('CODECART_CLI') && CODECART_CLI))) {
            if (empty($this->request->get['backup_confirmed'])) {
                $result = array('database' => array('changed' => array(), 'skipped' => array(), 'errors' => array(array('table' => '', 'error' => 'Verified database backup confirmation is required.'))));
            } else {
                $modernizer = new \CodeCart\Core\DatabaseModernizer($this->registry);
                $result = array('database' => $modernizer->migrate(!empty($this->request->get['large'])));
            }
        } elseif ($mode === 'all') {
            // The queue runs only through the registered core.queue.worker task.
            // A disabled queue worker must not be bypassed by cron:run.
            $result = array('scheduler' => $scheduler->runDue(20, $source), 'queue' => array('status' => 'scheduled_worker', 'processed' => 0, 'failed' => 0));
        } else {
            $this->response->setStatusCode(400);
            $result = array('error' => 'Unknown or unavailable Cron mode.');
        }

        $payload = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            $payload = '{"scheduler_one":{"success":false,"status":"error","message":"Could not encode scheduler response.","duration_ms":0}}';
        }
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput($payload);
        return $payload;
    }

    private function isAuthorized() {
        if (PHP_SAPI === 'cli' || (defined('CODECART_CLI') && CODECART_CLI)) { return true; }
        $expected = (string)$this->config->get('codecart_scheduler_key');
        $provided = isset($this->request->get['key']) && is_string($this->request->get['key']) ? $this->request->get['key'] : '';
        return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
    }
}
