<?php
class ControllerInstallStep4 extends Controller {
    public function index() {
        $this->load->language('install/step_4');
        $this->document->setTitle($this->language->get('heading_title'));
        foreach (array('heading_title','text_step_4','text_ready','text_ready_description','text_catalog','text_admin','text_version','text_database','text_storage','text_admin_login','text_admin_login_help','error_warning') as $key) {
            $data[$key] = $this->language->get($key);
        }
        $data['version'] = defined('VERSION') ? VERSION : '3.0.6.0';
        $data['php_version'] = PHP_VERSION;
        $data['database_summary'] = $this->databaseSummary();
        $data['storage_summary'] = $this->storageSummary();
        $data['admin_username'] = isset($this->session->data['codecart_install_admin_username']) ? (string)$this->session->data['codecart_install_admin_username'] : '';
        unset($this->session->data['codecart_install_admin_username']);
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $this->response->setOutput($this->load->view('install/step_4', $data));
        $this->finalizePublicStorage();
    }

    private function databaseSummary() {
        $db = $this->registry->get('db');
        if (!$db) { return $this->language->get('text_database_ready'); }
        try {
            $query = $db->query("SELECT VERSION() AS version");
            $row = $query->row;
            $version = isset($row['version']) ? preg_replace('/[^A-Za-z0-9._+\-]/', '', (string)$row['version']) : '';
            return 'InnoDB · utf8mb4' . ($version !== '' ? ' · ' . $version : '');
        } catch (\Throwable $e) {
            $this->log->write('Installer completion database summary: ' . $e->getMessage());
            return $this->language->get('text_database_ready');
        }
    }

    private function storageSummary() {
        $public = rtrim(str_replace('\\', '/', DIR_SYSTEM . 'storage/'), '/') . '/';
        $active = rtrim(str_replace('\\', '/', DIR_STORAGE), '/') . '/';
        return $active !== $public ? $this->language->get('text_storage_protected') : $this->language->get('text_storage_review');
    }

    private function finalizePublicStorage() {
        $public = rtrim(str_replace('\\', '/', DIR_SYSTEM . 'storage/'), '/') . '/';
        $active = rtrim(str_replace('\\', '/', DIR_STORAGE), '/') . '/';
        if ($active === $public || !is_dir($active) || !is_file($active . 'vendor/autoload.php')) { return; }
        if (is_dir($public)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($public, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $item) {
                if ($item->isLink() || $item->isFile()) { @unlink($item->getPathname()); }
                elseif ($item->isDir()) { @rmdir($item->getPathname()); }
            }
            @rmdir($public);
        }
        @mkdir($public, 0750, true);
        @file_put_contents($public . '.htaccess', "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n", LOCK_EX);
        @file_put_contents($public . 'web.config', '<?xml version="1.0" encoding="UTF-8"?><configuration><system.webServer><security><authorization><remove users="*" roles="" verbs=""/><add accessType="Deny" users="*"/></authorization></security></system.webServer></configuration>', LOCK_EX);
        @file_put_contents($public . 'index.html', '', LOCK_EX);
    }
}
