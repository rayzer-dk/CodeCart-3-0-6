<?php
class ControllerUpgradeUpgrade extends Controller {
    public function index() {
        $this->load->language('upgrade/upgrade');
        $this->document->setTitle($this->language->get('heading_title'));

        // An installed store must never expose UPDATE/Repair or its preflight
        // diagnostics to anonymous visitors: /install/ is uploaded again with every
        // update and is often left on the server. Require store administrator
        // credentials before showing or running anything.
        if (!$this->isAuthorized()) {
            return $this->renderLogin();
        }

        foreach (array(
            'heading_title','text_upgrade','text_server','text_steps','text_error','text_clear','text_admin','text_user','text_setting','text_store','text_backup',
            'text_preflight','text_preflight_help','text_component','text_current','text_status','text_ok','text_warning','text_blocker','text_backup_confirm',
            'text_preflight_ready','text_preflight_blocked','text_repair_mode','text_repair_help','error_ajax','entry_progress','button_continue'
        ) as $key) {
            $data[$key] = $this->language->get($key);
        }

        $data['store'] = HTTP_OPENCART;
        $files = $this->getMigrationFiles();
        $data['total'] = count($files);

        $preflight = $this->preflight();
        $data['preflight'] = $preflight['rows'];
        $data['preflight_blockers'] = $preflight['blockers'];
        $data['preflight_warnings'] = $preflight['warnings'];

        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');
        $data['column_left'] = $this->load->controller('common/column_left');
        $this->response->setOutput($this->load->view('upgrade/upgrade', $data));
    }

    public function login() {
        $this->load->language('upgrade/upgrade');

        if (!isset($this->request->server['REQUEST_METHOD']) || strtoupper((string)$this->request->server['REQUEST_METHOD']) !== 'POST') {
            $this->response->redirect($this->url->link('upgrade/upgrade'));
            return;
        }

        $expected = isset($this->session->data['codecart_upgrade_login_token']) ? (string)$this->session->data['codecart_upgrade_login_token'] : '';
        $provided = isset($this->request->post['login_token']) ? (string)$this->request->post['login_token'] : '';
        $attempts = isset($this->session->data['codecart_upgrade_login_attempts']) ? (int)$this->session->data['codecart_upgrade_login_attempts'] : 0;
        $blockedUntil = isset($this->session->data['codecart_upgrade_login_blocked']) ? (int)$this->session->data['codecart_upgrade_login_blocked'] : 0;

        if ($blockedUntil > time()) {
            $this->session->data['codecart_upgrade_login_error'] = $this->language->get('error_login_attempts');
        } elseif ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            $this->session->data['codecart_upgrade_login_error'] = $this->language->get('error_login');
        } else {
            $username = isset($this->request->post['username']) ? trim((string)$this->request->post['username']) : '';
            $password = isset($this->request->post['password']) ? (string)$this->request->post['password'] : '';
            $userId = $this->verifyAdministrator($username, $password);

            if ($userId > 0) {
                if (function_exists('session_regenerate_id') && session_status() === PHP_SESSION_ACTIVE) {
                    @session_regenerate_id(true);
                }
                $this->session->data['codecart_upgrade_user_id'] = $userId;
                $this->session->data['codecart_upgrade_authorized_at'] = time();
                unset($this->session->data['codecart_upgrade_login_attempts'], $this->session->data['codecart_upgrade_login_error'], $this->session->data['codecart_upgrade_login_blocked']);
            } else {
                usleep(random_int(300000, 800000));
                $attempts++;
                $this->session->data['codecart_upgrade_login_attempts'] = $attempts;
                if ($attempts >= 5) {
                    $this->session->data['codecart_upgrade_login_blocked'] = time() + 900;
                    $this->session->data['codecart_upgrade_login_attempts'] = 0;
                }
                $this->session->data['codecart_upgrade_login_error'] = $this->language->get('error_login');
                if ($this->log) {
                    $this->log->write('Installer UPDATE: failed administrator verification from ' . (isset($this->request->server['REMOTE_ADDR']) ? (string)$this->request->server['REMOTE_ADDR'] : 'unknown') . '.');
                }
            }
        }

        unset($this->session->data['codecart_upgrade_login_token']);
        $this->response->redirect($this->url->link('upgrade/upgrade'));
    }

    public function next() {
        $this->load->language('upgrade/upgrade');
        $json = array();

        if (!$this->isAuthorized()) {
            $json['error'] = $this->language->get('error_login_required');
            return $this->json($json);
        }
        $step = isset($this->request->get['step']) ? (int)$this->request->get['step'] : 1;
        if ($step < 1) { $step = 1; }
        $repair = !empty($this->request->get['repair']);
        if ($repair && !defined('CODECART_FORCE_REPAIR')) { define('CODECART_FORCE_REPAIR', true); }

        $preflight = $this->preflight();
        if ($preflight['blockers'] > 0) {
            $json['error'] = $this->language->get('error_preflight_blocked');
            return $this->json($json);
        }

        if ($step === 1 && !empty($this->request->get['backup'])) {
            $this->session->data['codecart_upgrade_backup_confirmed_at'] = time();
        }
        $confirmedAt = isset($this->session->data['codecart_upgrade_backup_confirmed_at']) ? (int)$this->session->data['codecart_upgrade_backup_confirmed_at'] : 0;
        if ($confirmedAt <= 0 || (time() - $confirmedAt) > 1800) {
            $json['error'] = $this->language->get('error_backup_required');
            return $this->json($json);
        }

        $files = $this->getMigrationFiles();

        if (isset($files[$step - 1])) {
            try {
                $migration = basename($files[$step - 1], '.php');
                if (!preg_match('/^[0-9A-Za-z_.-]+$/', $migration)) {
                    throw new \RuntimeException('Invalid migration filename.');
                }
                $this->load->model('upgrade/' . $migration);
                $property = 'model_upgrade_' . str_replace('.', '', $migration);
                $model = $this->registry->get($property);
                if (!is_object($model)) {
                    throw new \RuntimeException('Upgrade migration handler is unavailable.');
                }
                // Loader exposes model methods through Proxy::__call(), therefore
                // method_exists($proxy, 'upgrade') is intentionally not used here.
                $model->upgrade();
                $json['success'] = sprintf($this->language->get('text_progress'), $migration, $step, count($files));
                $json['next'] = str_replace('&amp;', '&', $this->url->link('upgrade/upgrade/next', 'step=' . ($step + 1) . ($repair ? '&repair=1' : '')));
            } catch (\Throwable $exception) {
                $this->log->write('Upgrade step ' . $step . ' failed: ' . $exception->getMessage());
                $json['error'] = $this->language->get('error_upgrade_step');
            }
        } else {
            unset($this->session->data['codecart_upgrade_backup_confirmed_at']);
            $json['success'] = $this->language->get('text_success');
        }
        $this->json($json);
    }

    private function getMigrationFiles() {
        $files = glob(DIR_APPLICATION . 'model/upgrade/*.php');
        $files = is_array($files) ? $files : array();

        // UPDATE is an overlay operation, so legacy OpenCart/ocStore installer
        // migrations can remain on disk from the source shop. Re-running those
        // historical migrations against an already-current 3.x database is unsafe
        // (for example ocStore 3.0.4.1 still ships migration 1010 for url_alias,
        // while url_alias has already been removed). CodeCart 3.0.6 has a
        // self-contained compatibility migration starting at 3052; only CodeCart
        // package migrations in that namespace are eligible here.
        $files = array_values(array_filter($files, function($file) {
            $migration = basename($file, '.php');

            return ctype_digit($migration) && (int)$migration >= 3052;
        }));

        natsort($files);

        return array_values($files);
    }

    private function preflight() {
        $rows = array();
        $blockers = 0;
        $warnings = 0;
        $add = function($component, $current, $state, $message) use (&$rows, &$blockers, &$warnings) {
            $state = in_array($state, array('ok','warning','blocker'), true) ? $state : 'warning';
            if ($state === 'blocker') { $blockers++; }
            if ($state === 'warning') { $warnings++; }
            $rows[] = array('component'=>$component,'current'=>$current,'state'=>$state,'message'=>$message);
        };

        $phpOk = version_compare(PHP_VERSION, '8.1.0', '>=') && version_compare(PHP_VERSION, '8.6.0', '<');
        $add('PHP', PHP_VERSION, $phpOk ? 'ok' : 'blocker', $this->language->get('preflight_php'));

        // Third-party compatibility notices are informative only. CodeCart itself supports PHP 8.1–8.5;
        // the administrator may intentionally run a legacy theme/payment on a lower PHP version.
        $uniDetected = is_file(DIR_OPENCART . 'admin/controller/extension/module/uni_settings.php') || is_dir(DIR_OPENCART . 'catalog/view/theme/unishop2');
        if ($uniDetected && version_compare(PHP_VERSION, '8.4.0', '>=')) {
            $add('UniShop2', 'detected / PHP ' . PHP_VERSION, 'warning', 'Installed UniShop2 releases may require PHP 8.3 and ionCube. This warning does not block the CodeCart update.');
        }
        if ((bool)$this->config->get('module_pp_braintree_button_status') && version_compare(PHP_VERSION, '8.4.0', '>=')) {
            $add('Legacy Braintree Button', 'enabled', 'warning', 'Legacy Braintree files are preserved. Verify this payment integration on the selected PHP version.');
        }
        if ((bool)$this->config->get('payment_divido_status') || (bool)$this->config->get('module_divido_calculator_status')) {
            $add('Legacy Divido', 'enabled', 'warning', 'Divido is not part of CodeCart Core. Active legacy files are preserved and must be verified separately.');
        }

        $extensions = array(
            'mysqli / pdo_mysql' => extension_loaded('mysqli') || extension_loaded('pdo_mysql'),
            'gd' => extension_loaded('gd'), 'curl' => extension_loaded('curl'), 'openssl' => extension_loaded('openssl'),
            'zlib' => extension_loaded('zlib'), 'zip' => extension_loaded('zip'), 'simplexml' => extension_loaded('simplexml') && function_exists('simplexml_load_string'), 'mbstring' => extension_loaded('mbstring'),
            'fileinfo' => extension_loaded('fileinfo'), 'dom' => extension_loaded('dom'), 'xmlwriter' => extension_loaded('xmlwriter')
        );
        foreach ($extensions as $name => $ok) {
            $add($name, $ok ? $this->language->get('text_ok') : $this->language->get('text_blocker'), $ok ? 'ok' : 'blocker', $this->language->get('preflight_extension'));
        }

        $tls12Ok = extension_loaded('openssl') && extension_loaded('curl') && defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') && defined('CURLOPT_SSLVERSION') && defined('CURL_SSLVERSION_TLSv1_2');
        $curlVersion = extension_loaded('curl') ? curl_version() : array();
        $tlsCurrent = $tls12Ok ? 'TLS >= 1.2; libcurl ' . (string)($curlVersion['version'] ?? 'unknown') . '; OpenSSL ' . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'unknown') : 'TLS 1.2 policy unavailable';
        $add('TLS policy', $tlsCurrent, $tls12Ok ? 'ok' : 'blocker', $tls12Ok ? $this->language->get('preflight_tls_ready') : $this->language->get('preflight_tls_blocked'));

        foreach (array(DIR_OPENCART . 'config.php', DIR_OPENCART . 'admin/config.php') as $file) {
            // UPDATE reads existing configuration but deliberately does not rewrite it.
            // A securely read-only config.php must not be treated as an upgrade failure.
            $ok = is_file($file) && is_readable($file);
            $state = $ok ? (is_writable($file) ? 'readable / writable' : 'readable / protected') : 'missing / unreadable';
            $add(basename(dirname($file)) === 'admin' ? 'admin/config.php' : 'config.php', $state, $ok ? 'ok' : 'blocker', $this->language->get('preflight_config'));
        }

        // Replacing application Core files is the normal update path and must never
        // become a blocker by itself. Real blockers are limited to conditions that
        // can make the migration unsafe (unsupported runtime, unreadable config,
        // missing required schema, unavailable storage, etc.).
        $add(
            $this->language->get('preflight_files_component'),
            $this->language->get('preflight_files_current'),
            'ok',
            $this->language->get('preflight_files_help')
        );

        $db = $this->registry->get('db');
        if (!is_object($db)) {
            $add('Database', 'not connected', 'blocker', $this->language->get('preflight_db_connection'));
        } else {
            try {
                $version = $db->query('SELECT VERSION() AS version');
                $dbVersion = $version->num_rows ? (string)$version->row['version'] : 'unknown';
                $add('MySQL / MariaDB', $dbVersion, 'ok', $this->language->get('preflight_db_connection'));

                $charset = $db->query("SHOW CHARACTER SET WHERE Charset = 'utf8mb4'");
                $add('utf8mb4', $charset->num_rows ? 'supported' : 'missing', $charset->num_rows ? 'ok' : 'blocker', $this->language->get('preflight_utf8mb4'));

                $innodb = false;
                $engines = $db->query('SHOW ENGINES');
                foreach ($engines->rows as $row) {
                    $engine = isset($row['Engine']) ? (string)$row['Engine'] : (isset($row['engine']) ? (string)$row['engine'] : '');
                    $support = isset($row['Support']) ? strtoupper((string)$row['Support']) : (isset($row['support']) ? strtoupper((string)$row['support']) : '');
                    if (strcasecmp($engine, 'InnoDB') === 0 && in_array($support, array('YES','DEFAULT'), true)) { $innodb = true; break; }
                }
                $add('InnoDB', $innodb ? 'supported' : 'missing', $innodb ? 'ok' : 'blocker', $this->language->get('preflight_innodb'));

                $requiredTables = array('setting','user','user_group','product','category','order','language','seo_url','event','extension','modification');
                $missing = array();
                foreach ($requiredTables as $table) {
                    $q = $db->query("SHOW TABLES LIKE '" . $db->escape(DB_PREFIX . $table) . "'");
                    if (!$q->num_rows) { $missing[] = DB_PREFIX . $table; }
                }
                $add('OpenCart 3.x schema', $missing ? implode(', ', $missing) : 'compatible base schema found', $missing ? 'blocker' : 'ok', $this->language->get('preflight_schema'));

                if (!$missing) {
                    $requiredColumns = array(
                        'setting' => array('setting_id','store_id','code','key','value','serialized'),
                        'user' => array('user_id','user_group_id','username','password','email','ip','status'),
                        'user_group' => array('user_group_id','permission'),
                        'product' => array('product_id','model','quantity','price','status','date_added','date_modified'),
                        'category' => array('category_id','parent_id','status'),
                        'order' => array('order_id','store_id','customer_id','total','order_status_id','date_added','date_modified'),
                        'language' => array('language_id','name','code','locale','image','directory','sort_order','status'),
                        'seo_url' => array('seo_url_id','store_id','language_id','query','keyword'),
                        'event' => array('event_id','code','trigger','action','status','sort_order'),
                        'extension' => array('extension_id','type','code'),
                        'modification' => array('modification_id','extension_install_id','name','code','xml','status')
                    );
                    $missingColumns = array();
                    foreach ($requiredColumns as $table => $columns) {
                        $q = $db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "`");
                        $have = array();
                        foreach ($q->rows as $row) { if (isset($row['Field'])) { $have[(string)$row['Field']] = true; } }
                        foreach ($columns as $column) { if (!isset($have[$column])) { $missingColumns[] = DB_PREFIX . $table . '.' . $column; } }
                    }
                    $profileOk = !$missingColumns;
                    $add($this->language->get('preflight_source_component'), $profileOk ? $this->language->get('preflight_source_range') : implode(', ', array_slice($missingColumns, 0, 12)), $profileOk ? 'ok' : 'blocker', $profileOk ? $this->language->get('preflight_source_help') : $this->language->get('preflight_source_blocked'));

                    // OCMOD overlap is not automatically a conflict, but active modifications
                    // that target the same product/theme files changed by CodeCart PRO deserve an
                    // explicit staging check. Report only; never disable or rewrite third-party
                    // modifications here. The one explicitly retired Extra_email modification
                    // is handled later by its dedicated, backed-up migration contract.
                    $ocmodTargets = array(
                        'admin/model/catalog/product.php',
                        'admin/controller/catalog/product.php',
                        'admin/view/template/catalog/product_form.twig',
                        'admin/controller/extension/theme/default.php',
                        'admin/view/template/extension/theme/default.twig',
                        'catalog/model/catalog/product.php',
                        'catalog/controller/product/product.php',
                        'catalog/view/theme/default/template/product/product.twig',
                        'catalog/controller/common/header.php',
                        'catalog/view/theme/default/template/common/header.twig'
                    );
                    $ocmodMatches = array();
                    $activeMods = $db->query("SELECT modification_id,name,code,xml FROM `" . DB_PREFIX . "modification` WHERE status='1' ORDER BY modification_id ASC");
                    foreach ($activeMods->rows as $modification) {
                        $xml = strtolower((string)$modification['xml']);
                        foreach ($ocmodTargets as $target) {
                            if (strpos($xml, strtolower($target)) !== false) {
                                $label = trim((string)$modification['name']);
                                if ($label === '') { $label = trim((string)$modification['code']); }
                                if ($label === '') { $label = '#' . (int)$modification['modification_id']; }
                                $ocmodMatches[$label] = $label;
                                break;
                            }
                        }
                    }
                    if ($ocmodMatches) {
                        $visibleMods = array_slice(array_values($ocmodMatches), 0, 6);
                        $currentMods = implode(', ', $visibleMods);
                        if (count($ocmodMatches) > count($visibleMods)) { $currentMods .= ' +' . (count($ocmodMatches) - count($visibleMods)); }
                        $add($this->language->get('preflight_ocmod_component'), $currentMods, 'warning', $this->language->get('preflight_ocmod_warning'));
                    } else {
                        $add($this->language->get('preflight_ocmod_component'), $this->language->get('preflight_ocmod_none'), 'ok', $this->language->get('preflight_ocmod_help'));
                    }

                    $tableStatus = $db->query("SHOW TABLE STATUS LIKE '" . $db->escape(DB_PREFIX) . "%'");
                    $legacyEngine = 0;
                    $legacyCharset = 0;
                    $totalPrefixed = 0;
                    foreach ($tableStatus->rows as $row) {
                        $totalPrefixed++;
                        $engine = isset($row['Engine']) ? strtolower((string)$row['Engine']) : '';
                        $collation = isset($row['Collation']) ? strtolower((string)$row['Collation']) : '';
                        if ($engine !== '' && $engine !== 'innodb') { $legacyEngine++; }
                        if ($collation !== '' && strpos($collation, 'utf8mb4_') !== 0) { $legacyCharset++; }
                    }
                    $state = ($legacyEngine || $legacyCharset) ? 'warning' : 'ok';
                    $current = sprintf('%d tables; legacy engine: %d; non-utf8mb4: %d', $totalPrefixed, $legacyEngine, $legacyCharset);
                    $add('Existing database format', $current, $state, $this->language->get('preflight_existing_db_format'));

                    // Order/stock consistency requires transactions. Small legacy
                    // commerce tables are converted during the confirmed upgrade step;
                    // large or unsupported tables are a preflight blocker so the
                    // administrator can modernize them explicitly before downtime.
                    $criticalNames = array('product','product_option_value','order','order_product','order_option','order_total','order_history','customer','customer_reward','coupon_history','voucher_history');
                    $criticalLegacy = 0;
                    $criticalLarge = array();
                    $criticalUnsupported = array();
                    foreach ($criticalNames as $criticalName) {
                        $criticalTable = DB_PREFIX . $criticalName;
                        $meta = $db->query("SELECT ENGINE, DATA_LENGTH, INDEX_LENGTH FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $db->escape($criticalTable) . "' LIMIT 1");
                        if (!$meta->num_rows) { continue; }
                        $criticalEngine = strtoupper((string)$meta->row['ENGINE']);
                        if ($criticalEngine === 'INNODB') { continue; }
                        $criticalLegacy++;
                        if (!in_array($criticalEngine, array('MYISAM','ARIA'), true)) { $criticalUnsupported[] = $criticalTable . ' (' . $criticalEngine . ')'; continue; }
                        $criticalBytes = (int)$meta->row['DATA_LENGTH'] + (int)$meta->row['INDEX_LENGTH'];
                        if ($criticalBytes >= 268435456) { $criticalLarge[] = $criticalTable; }
                    }
                    $criticalBlocked = !empty($criticalLarge) || !empty($criticalUnsupported);
                    if ($criticalBlocked) {
                        $criticalCurrent = implode(', ', array_merge($criticalUnsupported, $criticalLarge));
                    } elseif ($criticalLegacy) {
                        $criticalCurrent = sprintf($this->language->get('preflight_transactional_convertible'), $criticalLegacy);
                    } else {
                        $criticalCurrent = $this->language->get('preflight_transactional_ready');
                    }
                    $add($this->language->get('preflight_transactional_component'), $criticalCurrent, $criticalBlocked ? 'blocker' : ($criticalLegacy ? 'warning' : 'ok'), $criticalBlocked ? $this->language->get('preflight_transactional_blocked') : $this->language->get('preflight_transactional_help'));
                }
            } catch (\Throwable $e) {
                $this->log->write('Upgrade preflight database check failed: ' . $e->getMessage());
                $add('Database', 'check failed', 'blocker', $this->language->get('preflight_db_connection'));
            }
        }

        $publicVendor = rtrim(str_replace('\\', '/', DIR_SYSTEM . 'storage/vendor/'), '/') . '/';
        $activeVendor = defined('DIR_STORAGE') ? rtrim(str_replace('\\', '/', DIR_STORAGE . 'vendor/'), '/') . '/' : $publicVendor;
        if ($activeVendor !== $publicVendor && is_file($activeVendor . 'autoload.php') && is_file($publicVendor . 'autoload.php')) {
            $foreignPackages = \CodeCart\Core\VendorCompatibility::foreignPackages($activeVendor, $publicVendor);
            if ($foreignPackages) {
                $visible = array_slice($foreignPackages, 0, 8);
                $current = implode(', ', $visible) . (count($foreignPackages) > count($visible) ? ' +' . (count($foreignPackages) - count($visible)) : '');
                $add($this->language->get('preflight_vendor_component'), $current, 'warning', $this->language->get('preflight_vendor_replace'));
            } else {
                $add($this->language->get('preflight_vendor_component'), $this->language->get('preflight_vendor_ready'), 'ok', $this->language->get('preflight_vendor_help'));
            }
        }

        $storageOk = defined('DIR_STORAGE') && is_dir(DIR_STORAGE) && is_writable(DIR_STORAGE);
        $add('Storage', defined('DIR_STORAGE') ? (string)DIR_STORAGE : 'not defined', $storageOk ? 'ok' : 'blocker', $this->language->get('preflight_storage'));
        foreach (array('cache' => DIR_CACHE, 'logs' => DIR_LOGS) as $name => $path) {
            $ok = is_dir($path) && is_writable($path);
            $add('Storage ' . $name, (string)$path, $ok ? 'ok' : 'blocker', $this->language->get('preflight_storage'));
        }

        $free = @disk_free_space(DIR_OPENCART);
        if (is_numeric($free) && $free >= 0) {
            $mb = (int)floor($free / 1048576);
            $add('Disk space', $mb . ' MB free', $mb < 256 ? 'warning' : 'ok', $this->language->get('preflight_disk'));
        }

        return array('rows'=>$rows, 'blockers'=>$blockers, 'warnings'=>$warnings);
    }

    private function isAuthorized() {
        $userId = isset($this->session->data['codecart_upgrade_user_id']) ? (int)$this->session->data['codecart_upgrade_user_id'] : 0;
        $at = isset($this->session->data['codecart_upgrade_authorized_at']) ? (int)$this->session->data['codecart_upgrade_authorized_at'] : 0;
        if ($userId <= 0 || $at <= 0 || (time() - $at) > 7200) {
            return false;
        }
        $this->session->data['codecart_upgrade_authorized_at'] = time();
        return true;
    }

    private function verifyAdministrator($username, $password) {
        if ($username === '' || $password === '' || !$this->registry->has('db') || !defined('DB_PREFIX')) {
            return 0;
        }
        try {
            $query = $this->db->query("SELECT u.user_id, u.password, u.salt, ug.permission FROM `" . DB_PREFIX . "user` u LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (ug.user_group_id = u.user_group_id) WHERE u.username = '" . $this->db->escape($username) . "' AND u.status = '1' LIMIT 1");
        } catch (\Throwable $e) {
            return 0;
        }
        if (!$query->num_rows || !codecart_password_verify($password, (string)$query->row['password'], isset($query->row['salt']) ? (string)$query->row['salt'] : '')) {
            return 0;
        }
        // Only a store administrator who may manage extensions/users can update Core.
        $permission = json_decode((string)$query->row['permission'], true);
        $modify = is_array($permission) && isset($permission['modify']) && is_array($permission['modify']) ? $permission['modify'] : array();
        if (!array_intersect(array('user/user_permission', 'marketplace/installer', 'tool/codecart_core'), $modify)) {
            return 0;
        }
        return (int)$query->row['user_id'];
    }

    private function renderLogin() {
        foreach (array('heading_title', 'text_upgrade', 'text_login_required', 'entry_username', 'entry_password', 'button_login') as $key) {
            $data[$key] = $this->language->get($key);
        }
        $data['error_login'] = isset($this->session->data['codecart_upgrade_login_error']) ? (string)$this->session->data['codecart_upgrade_login_error'] : '';
        unset($this->session->data['codecart_upgrade_login_error']);
        $this->session->data['codecart_upgrade_login_token'] = bin2hex(random_bytes(16));
        $data['login_token'] = $this->session->data['codecart_upgrade_login_token'];
        $data['action'] = $this->url->link('upgrade/upgrade/login');
        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');
        $data['column_left'] = $this->load->controller('common/column_left');
        $this->response->setOutput($this->load->view('upgrade/login', $data));
    }

    private function json(array $json) {
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
