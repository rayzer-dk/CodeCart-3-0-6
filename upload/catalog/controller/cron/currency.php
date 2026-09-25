<?php
class ControllerCronCurrency extends Controller {
    public function index($args = array()) {
        if (!$this->config->get('config_currency_auto')) {
            return array('success' => true, 'message' => 'Currency automatic refresh is disabled.');
        }

        $engine = strtolower(trim((string)$this->config->get('config_currency_engine')));
        if ($engine === '' || !preg_match('/^[a-z0-9_]+$/', $engine)) {
            return array('success' => true, 'message' => 'Currency provider is not configured.');
        }

        if (!$this->config->get('currency_' . $engine . '_status')) {
            return array('success' => true, 'message' => 'Currency provider ' . $engine . ' is disabled.');
        }

        $file = DIR_APPLICATION . 'model/extension/currency/' . $engine . '.php';
        if (!is_file($file)) {
            $message = 'Currency model not found: ' . $engine;
            $this->log->write('Currency scheduler: ' . $message);
            return array('success' => false, 'message' => $message);
        }

        $this->load->model('extension/currency/' . $engine);
        $property = 'model_extension_currency_' . $engine;
        $model = $this->registry->get($property);

        if (!is_object($model)) {
            $message = 'Currency model could not be loaded: ' . $engine;
            $this->log->write('Currency scheduler: ' . $message);
            return array('success' => false, 'message' => $message);
        }

        try {
            // Prefer a structured result. OpenCart's Proxy creates method callbacks
            // independently, so model-property state must not be used to transport
            // an error message between refresh() and getLastError().
            if (isset($model->refreshResult)) {
                $result = $model->refreshResult();
            } else {
                $ok = $model->refresh() !== false;
                $result = array('success' => $ok, 'message' => $ok ? 'OK' : 'Currency refresh failed: ' . $engine);
            }
        } catch (\Throwable $e) {
            $message = 'Currency refresh exception (' . $engine . '): ' . $e->getMessage();
            $this->log->write('Currency scheduler: ' . $message);
            return array('success' => false, 'message' => $message);
        }

        if (!is_array($result)) {
            $result = array('success' => (bool)$result, 'message' => (bool)$result ? 'OK' : 'Currency refresh failed: ' . $engine);
        }
        $success = !empty($result['success']);
        $message = trim((string)($result['message'] ?? ''));
        if ($message === '') {
            $message = $success ? 'OK' : 'Currency refresh failed: ' . $engine;
        }

        if (!$success) {
            // The provider itself logs detailed transport/parse/DB failures. Do not
            // write a second generic line unless it returned no meaningful detail.
            if (stripos($message, 'currency refresh') === false) {
                $this->log->write('Currency scheduler (' . $engine . '): ' . $message);
            }
            return array('success' => false, 'message' => $message);
        }

        return array('success' => true, 'message' => $message);
    }
}
