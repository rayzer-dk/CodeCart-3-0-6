<?php
class ControllerToolCatalogTransfer extends Controller {
    public function export() {
        $this->load->language('tool/backup');

        if ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $this->sendExportError($this->language->get('error_catalog_export'), 405);
            return;
        }

        if (!$this->user->hasPermission('modify', 'tool/backup')) {
            $this->sendExportError($this->language->get('error_permission'), 403);
            return;
        }

        if (!isset($this->request->post['catalog_export_format']) || (string)$this->request->post['catalog_export_format'] !== 'xlsx') {
            $this->sendExportError($this->language->get('error_catalog_export'), 400);
            return;
        }

        $entities = isset($this->request->post['catalog_entities']) && is_array($this->request->post['catalog_entities'])
            ? array_values(array_intersect(array('products', 'categories', 'manufacturers', 'options', 'attributes', 'filters'), $this->request->post['catalog_entities']))
            : array();
        $portable = !empty($this->request->post['portable']);

        if (!$entities) {
            $this->sendExportError($this->language->get('error_catalog_entities'), 400);
            return;
        }

        $tmp = tempnam(DIR_UPLOAD, 'ccpexport_');
        if (!$tmp) {
            $this->sendExportError($this->language->get('error_catalog_export'), 500);
            return;
        }

        $filename = 'codecart_catalog_' . date('Y-m-d_H-i-s') . ($portable ? '.zip' : '.xlsx');
        $path = $tmp . ($portable ? '.zip' : '.xlsx');
        @unlink($tmp);
        $display_errors = ini_get('display_errors');
        @ini_set('display_errors', '0');

        try {
            $transfer = new \CodeCart\Core\CatalogTransfer($this->registry);
            $transfer->export($entities, $path, $portable);

            if (!is_file($path) || (int)filesize($path) < 32) {
                throw new \RuntimeException($this->language->get('error_catalog_export'));
            }

            if (!$portable) {
                $check = new \ZipArchive();
                $opened = $check->open($path);
                if ($opened !== true || $check->locateName('xl/workbook.xml') === false || $check->locateName('[Content_Types].xml') === false) {
                    if ($opened === true) {
                        $check->close();
                    }
                    throw new \RuntimeException('Generated XLSX archive is incomplete.');
                }
                $check->close();
            }

            while (ob_get_level() > 0) { @ob_end_clean(); }
            $this->response->addHeader('Pragma: public');
            $this->response->addHeader('Expires: 0');
            $this->response->addHeader('Cache-Control: no-store, no-cache, must-revalidate');
            $this->response->addHeader('Content-Type: ' . ($portable ? 'application/zip' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
            $this->response->addHeader('X-CodeCart-Export: ' . ($portable ? 'catalog-zip' : 'catalog-xlsx'));
            $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
            $this->response->addHeader('Content-Length: ' . (int)filesize($path));
            $this->response->addHeader('X-Content-Type-Options: nosniff');
            $this->response->setFile($path, true);
            $path = ''; // Response owns cleanup after streaming.
        } catch (\Throwable $e) {
            $this->log->write('CodeCart PRO catalog transfer export failed: ' . $e->getMessage());
            $this->sendExportError($e->getMessage(), 500);
        } finally {
            if ($display_errors !== false) {
                @ini_set('display_errors', (string)$display_errors);
            }
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public function preview() {
        $this->load->language('tool/backup');
        $json = array();

        if (!$this->user->hasPermission('modify', 'tool/backup')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $json['error'] = $this->language->get('error_catalog_file');
        } elseif (!isset($this->request->files['catalog_import']) || !is_array($this->request->files['catalog_import'])) {
            $json['error'] = $this->language->get('error_catalog_file');
        } else {
            try {
                $transfer = new \CodeCart\Core\CatalogTransfer($this->registry);
                $json['preview'] = $transfer->prepareUpload($this->request->files['catalog_import']);
            } catch (\Throwable $e) {
                $json['error'] = $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json; charset=UTF-8');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function import() {
        $this->load->language('tool/backup');
        $json = array();

        if (!$this->user->hasPermission('modify', 'tool/backup')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif ($this->request->server['REQUEST_METHOD'] !== 'POST') {
            $json['error'] = $this->language->get('error_catalog_file');
        } else {
            $token = isset($this->request->post['token']) ? (string)$this->request->post['token'] : '';
            $overwrite = !empty($this->request->post['overwrite_images']);
            try {
                $transfer = new \CodeCart\Core\CatalogTransfer($this->registry);
                $json['result'] = $transfer->importToken($token, $overwrite);
                $json['success'] = $this->language->get('text_catalog_import_success');
                $this->cache->delete('*');
            } catch (\Throwable $e) {
                $json['error'] = $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json; charset=UTF-8');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function sendExportError($message, $status = 400) {
        $message = trim((string)$message);
        if ($message === '') {
            $message = 'Catalog export failed.';
        }
        $status = (int)$status;
        $status_text = $status === 403 ? 'Forbidden' : ($status === 405 ? 'Method Not Allowed' : ($status >= 500 ? 'Internal Server Error' : 'Bad Request'));
        $this->response->setStatusCode($status);
        $this->response->addHeader('Content-Type: application/json; charset=UTF-8');
        $this->response->addHeader('Cache-Control: no-store');
        $this->response->addHeader('X-CodeCart-Export: error');
        $this->response->setOutput(json_encode(array('error' => $message), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
