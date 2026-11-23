<?php
class ControllerToolLog extends Controller {
    private $tailLimit = 1048576;

    public function index() {
        $this->load->language('tool/log');
        $this->document->setTitle($this->language->get('heading_title'));

        $data['error_warning'] = isset($this->session->data['error']) ? $this->session->data['error'] : '';
        unset($this->session->data['error']);
        $data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
        unset($this->session->data['success']);

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
        $data['breadcrumbs'][] = array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('tool/log', 'user_token=' . $this->session->data['user_token'], true));
        $data['clear'] = $this->url->link('tool/log/clear', 'user_token=' . $this->session->data['user_token'], true);
        $data['download'] = $this->url->link('tool/log/download', 'user_token=' . $this->session->data['user_token'], true);
        $data['button_copy'] = $this->language->get('button_copy');
        $data['unique_mode'] = isset($this->request->get['unique']) && (string)$this->request->get['unique'] === '1';
        $data['unique_url'] = $this->url->link('tool/log', 'user_token=' . $this->session->data['user_token'] . '&unique=1', true);
        $data['normal_url'] = $this->url->link('tool/log', 'user_token=' . $this->session->data['user_token'], true);
        $data['unique_rows'] = array();
        $data['unique_total'] = 0;

        $file = $this->getErrorLogFile();
        $data['log'] = '';
        $data['log_size'] = '0 B';
        $data['log_name'] = basename($file);
        $data['tail_notice'] = '';
        if (is_file($file) && is_readable($file)) {
            $size = (int)filesize($file);
            $data['log_size'] = $this->formatBytes($size);
            if ($size > 0) {
                $data['log'] = \CodeCart\Core\LogInsights::tail($file);
                if ($size > $this->tailLimit) { $data['tail_notice'] = sprintf($this->language->get('text_tail_notice'), $this->formatBytes($this->tailLimit)); }
            }
        }

        if ($data['unique_mode'] && $data['log'] !== '') {
            $data['unique_rows'] = \CodeCart\Core\LogInsights::group($data['log']);
            $data['unique_total'] = count($data['unique_rows']);
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('tool/log', $data));
    }

    public function download() {
        $this->load->language('tool/log');
        $file = $this->getErrorLogFile();
        if (is_file($file) && is_readable($file)) {
            $this->response->addHeader('Pragma: public');
            $this->response->addHeader('Expires: 0');
            $this->response->addHeader('Content-Description: File Transfer');
            $this->response->addHeader('Content-Type: text/plain; charset=utf-8');
            $this->response->addHeader('Content-Disposition: attachment; filename="error_' . date('Y-m-d_H-i-s') . '.log"');
            $this->response->setOutput((string)file_get_contents($file));
            return;
        }
        $this->session->data['error'] = $this->language->get('error_file_missing');
        $this->response->redirect($this->url->link('tool/log', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function clear() {
        $this->load->language('tool/log');
        if (!$this->user->hasPermission('modify', 'tool/log')) {
            $this->session->data['error'] = $this->language->get('error_permission');
        } else {
            $file = $this->getErrorLogFile();
            $handle = @fopen($file, 'wb');
            if ($handle) {
                fclose($handle);
                \CodeCart\Core\LogInsights::invalidate($this->config);
                $this->session->data['success'] = $this->language->get('text_success');
            } else {
                $this->session->data['error'] = $this->language->get('error_file_missing');
            }
        }
        $this->response->redirect($this->url->link('tool/log', 'user_token=' . $this->session->data['user_token'], true));
    }

    private function getErrorLogFile() {
        return \CodeCart\Core\LogInsights::path($this->config);
    }

    private function formatBytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $value = (float)$bytes; $i = 0;
        while ($value >= 1024 && $i < count($units) - 1) { $value /= 1024; $i++; }
        return round($value, $i ? 2 : 0) . ' ' . $units[$i];
    }
}
