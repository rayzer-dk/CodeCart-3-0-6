<?php
class ControllerInstallStep2 extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('install/step_2');

        $environmentReady = $this->validate();
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $environmentReady) {
            $this->response->redirect($this->url->link('install/step_3'));
        }

        $this->document->setTitle($this->language->get('heading_title'));

        foreach (array(
            'heading_title','text_step_2','text_install_php','text_install_extension','text_install_file','text_install_directory',
            'text_setting','text_current','text_required','text_extension','text_file','text_directory','text_status','text_on','text_off',
            'text_missing','text_writable','text_unwritable','text_version','text_file_upload','text_session','text_extension_write','text_extension_write_help','text_optional','button_continue','button_back'
        ) as $key) {
            $data[$key] = $this->language->get($key);
        }

        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        $data['error_warnings'] = isset($this->error['warnings']) && is_array($this->error['warnings']) ? $this->error['warnings'] : array();
        $data['action'] = $this->url->link('install/step_2');
        $data['can_continue'] = $environmentReady;

        $data['catalog_config'] = DIR_OPENCART . 'config.php';
        $data['admin_config'] = DIR_OPENCART . 'admin/config.php';
        $data['image'] = DIR_OPENCART . 'image';
        $data['image_cache'] = DIR_OPENCART . 'image/cache';
        $data['image_catalog'] = DIR_OPENCART . 'image/catalog';
        $data['cache'] = DIR_SYSTEM . 'storage/cache';
        $data['logs'] = DIR_SYSTEM . 'storage/logs';
        $data['download'] = DIR_SYSTEM . 'storage/download';
        $data['upload'] = DIR_SYSTEM . 'storage/upload';
        $data['modification'] = DIR_SYSTEM . 'storage/modification';
        $data['session_dir'] = DIR_SYSTEM . 'storage/session';
        $data['secure_storage_parent'] = dirname(rtrim(DIR_OPENCART, '/\\'));

        $data['error_catalog_config'] = $this->pathError($data['catalog_config'], true);
        $data['error_admin_config'] = $this->pathError($data['admin_config'], true);
        $data['error_image'] = $this->pathError($data['image']);
        $data['error_image_cache'] = $this->pathError($data['image_cache']);
        $data['error_image_catalog'] = $this->pathError($data['image_catalog']);
        $data['error_cache'] = $this->pathError($data['cache']);
        $data['error_logs'] = $this->pathError($data['logs']);
        $data['error_download'] = $this->pathError($data['download']);
        $data['error_upload'] = $this->pathError($data['upload']);
        $data['error_modification'] = $this->pathError($data['modification']);
        $data['error_session_dir'] = $this->pathError($data['session_dir']);
        $data['error_secure_storage_parent'] = $this->pathError($data['secure_storage_parent']);

        // Extension Installer capability is advisory rather than a clean-install
        // blocker: hardened deployments may intentionally keep application code
        // read-only and deploy extensions through CI/SFTP. If web installation is
        // expected, these targets must be writable by the PHP/web-server user.
        $data['extension_write_paths'] = array(
            DIR_OPENCART . 'admin' => is_writable(DIR_OPENCART . 'admin'),
            DIR_OPENCART . 'catalog' => is_writable(DIR_OPENCART . 'catalog'),
            DIR_OPENCART . 'system' => is_writable(DIR_OPENCART . 'system'),
            DIR_OPENCART . 'image' => is_writable(DIR_OPENCART . 'image')
        );
        $data['extension_install_writable'] = !in_array(false, $data['extension_write_paths'], true);

        $data['php_version'] = PHP_VERSION;
        $data['php_version_supported'] = version_compare(PHP_VERSION, '8.1.0', '>=') && version_compare(PHP_VERSION, '8.6.0', '<');
        $data['file_uploads'] = (bool)ini_get('file_uploads');
        $data['session_auto_start'] = (bool)ini_get('session.auto_start');
        $data['db'] = extension_loaded('mysqli') || extension_loaded('pdo_mysql');
        $data['gd'] = extension_loaded('gd');
        $data['curl'] = extension_loaded('curl');
        $data['openssl'] = extension_loaded('openssl') && function_exists('openssl_encrypt');
        $data['tls12'] = $data['openssl'] && $data['curl'] && defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') && defined('CURLOPT_SSLVERSION') && defined('CURL_SSLVERSION_TLSv1_2');
        $data['zlib'] = extension_loaded('zlib');
        $data['zip'] = extension_loaded('zip');
        $data['simplexml'] = extension_loaded('simplexml') && function_exists('simplexml_load_string');
        $data['iconv'] = function_exists('iconv');
        $data['mbstring'] = extension_loaded('mbstring');
        $data['dom'] = extension_loaded('dom');
        $data['hash'] = extension_loaded('hash');
        $data['xmlwriter'] = extension_loaded('xmlwriter');
        $data['json'] = extension_loaded('json');
        $data['fileinfo'] = extension_loaded('fileinfo');

        $data['back'] = $this->url->link('install/step_1');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $this->response->setOutput($this->load->view('install/step_2', $data));
    }

    private function pathError($path, $file = false) {
        if ($file) {
            return $this->isWritableFileTarget($path) ? '' : $this->language->get('error_unwritable');
        }
        if (!is_dir($path)) {
            return $this->language->get('error_missing');
        }
        return is_writable($path) ? '' : $this->language->get('error_unwritable');
    }


    private function isWritableFileTarget($path) {
        if (is_file($path)) { return is_writable($path); }
        $directory = dirname($path);
        return is_dir($directory) && is_writable($directory);
    }

    private function validate() {
        $warnings = array();
        $checks = array(
            array(!(version_compare(PHP_VERSION, '8.1.0', '>=') && version_compare(PHP_VERSION, '8.6.0', '<')), 'error_version'),
            array(!ini_get('file_uploads'), 'error_file_upload'),
            array((bool)ini_get('session.auto_start'), 'error_session'),
            array(!(extension_loaded('mysqli') || extension_loaded('pdo_mysql')), 'error_db'),
            array(!extension_loaded('gd'), 'error_gd'),
            array(!extension_loaded('curl'), 'error_curl'),
            array(!(extension_loaded('openssl') && function_exists('openssl_encrypt')), 'error_openssl'),
            array(!(extension_loaded('openssl') && extension_loaded('curl') && defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') && defined('CURLOPT_SSLVERSION') && defined('CURL_SSLVERSION_TLSv1_2')), 'error_tls12'),
            array(!extension_loaded('zlib'), 'error_zlib'),
            array(!extension_loaded('zip'), 'error_zip'),
            array(!(extension_loaded('simplexml') && function_exists('simplexml_load_string')), 'error_simplexml'),
            array(!extension_loaded('mbstring'), 'error_mbstring'),
            array(!extension_loaded('fileinfo'), 'error_fileinfo'),
            array(!function_exists('iconv'), 'error_iconv'),
            array(!extension_loaded('dom'), 'error_dom'),
            array(!extension_loaded('hash'), 'error_hash'),
            array(!extension_loaded('xmlwriter'), 'error_xmlwriter'),
            array(!extension_loaded('json'), 'error_json')
        );
        foreach ($checks as $check) {
            if ($check[0]) { $warnings[] = $this->language->get($check[1]); }
        }

        foreach (array(
            DIR_OPENCART . 'config.php' => 'error_catalog_writable',
            DIR_OPENCART . 'admin/config.php' => 'error_admin_writable'
        ) as $path => $key) {
            if (!$this->isWritableFileTarget($path)) { $warnings[] = $this->language->get($key); }
        }

        $paths = array(
            DIR_OPENCART . 'image' => 'error_image',
            DIR_OPENCART . 'image/cache' => 'error_image_cache',
            DIR_OPENCART . 'image/catalog' => 'error_image_catalog',
            DIR_SYSTEM . 'storage/cache' => 'error_cache',
            DIR_SYSTEM . 'storage/logs' => 'error_log',
            DIR_SYSTEM . 'storage/download' => 'error_download',
            DIR_SYSTEM . 'storage/upload' => 'error_upload',
            DIR_SYSTEM . 'storage/modification' => 'error_modification',
            DIR_SYSTEM . 'storage/session' => 'error_session_dir',
            dirname(rtrim(DIR_OPENCART, '/\\')) => 'error_secure_storage_parent'
        );
        foreach ($paths as $path => $key) {
            if (!is_dir($path) || !is_writable($path)) { $warnings[] = $this->language->get($key); }
        }

        $warnings = array_values(array_unique(array_filter($warnings, 'strlen')));
        if ($warnings) {
            $this->error['warnings'] = $warnings;
            $this->error['warning'] = implode(' ', $warnings);
        }
        return !$this->error;
    }
}
