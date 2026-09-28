<?php
class ControllerToolCodeCartCore extends Controller {
    public function index() {
        $this->ensureCodeCartCoreAutoload();
        $this->ensureCoreServices();
        $this->load->language('tool/codecart_core');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('view/stylesheet/codecart-core.css?v=3.0.6.0');
        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] === 'POST') {
            if (!$this->user->hasPermission('modify', 'tool/codecart_core')) {
                $this->session->data['error_warning'] = $this->language->get('error_permission');
            } else {
                try {
                    $this->processAction();
                } catch (\Throwable $e) {
                    $this->session->data['error_warning'] = $e->getMessage();
                }
            }
            $url = '';
            if (isset($this->request->post['return_tab']) && preg_match('/^[a-z0-9_-]{1,32}$/i', (string)$this->request->post['return_tab'])) {
                $returnTab = (string)$this->request->post['return_tab'];
                $url = '&tab=' . rawurlencode($returnTab);
                if ($returnTab === 'schema') { $url .= '&schema=1'; }
            }
            $this->response->redirect($this->url->link('tool/codecart_core', 'user_token=' . $this->session->data['user_token'] . $url, true));
            return;
        }

        $data = array();
        foreach (array(
            'heading_title','text_home','text_status','text_runtime','text_security','text_privacy','text_schema','text_extensions','text_icons','text_icon_search','text_icon_help','text_modern_social_icons','text_icon_core_package','text_icon_full_package','text_icon_core_package_help','text_icon_full_package_help','text_icon_full_count','text_icon_styles','text_icon_scanner','text_icon_scanner_help','text_icon_mode_requested','text_icon_mode_effective','text_icon_mode_setting','text_icon_mode_setting_help','text_icon_auto','text_icon_core','text_icon_full','text_icon_core_count','text_icon_files_scanned','text_icon_last_scan','text_icon_unknown','text_icon_location','text_icon_line','text_icon_kind','text_icon_resolution','text_icon_resolution_full','text_icon_resolution_unresolved','text_icon_unresolved','text_icon_no_unknown','text_icon_fallback_full','text_icon_core_active','text_icon_scan_error','text_icon_manual_build_help','button_icon_rescan','entry_icon_extras','help_icon_extras','button_icon_add_found','button_icon_save_extras','text_icon_all','text_icon_social','text_icon_commerce','text_icon_payment','text_icon_shipping','text_icon_contact','text_icon_interface','text_icon_security','text_icon_media','text_icon_development','text_icon_other','text_icon_copied','text_icon_usage','text_icon_loading','text_icon_load_error','text_about','text_about_overview','text_about_requirements','text_about_changes','text_about_comparison','text_about_comparison_note','text_about_future','text_about_fixed','text_about_improved','text_about_security','text_required','text_recommended','text_current','text_supported','text_not_supported','text_added','text_updated','text_removed_runtime','text_legacy','text_builtin','text_server_profile','text_server_requirements_help','text_platform','text_component','text_benefit','text_codecart_website','text_about_legacy_title','text_about_legacy_value','text_about_legacy_help','text_about_security_value','text_about_security_help','text_about_server_value','text_about_server_help','text_about_modern_title','text_about_modern_value','text_about_modern_help','text_enabled','text_disabled','text_ok','text_missing','text_optional','text_schema_not_scanned','text_no_issues','text_no_extensions','text_gdpr_requests','text_confirm_delete','text_device_fail_open','text_schema_readonly','text_extension_help','text_runtime_help','text_spam_help','text_gdpr_help','text_cookie_help','text_notification_help','text_current_user_devices','text_revoke_devices','text_refresh_registry','text_scan_schema','text_save','text_processed','text_pending','text_confirmed','text_superseded','text_export','text_delete','text_unknown','text_system_notifications','text_scheduler','text_retention_auto','text_cleanup_done','text_cleanup_confirm','text_gdpr_retention_help',
            'text_performance','text_performance_help','text_minify_diagnostics','text_minify_available','text_minify_stale','text_minify_active','entry_storefront_minify_css','entry_storefront_minify_js','entry_admin_minify_css','entry_admin_minify_js','help_storefront_minify_css','help_storefront_minify_js','help_admin_minify_css','help_admin_minify_js','text_safe_minify_note','button_save_performance','text_architecture','text_architecture_help','text_compat_detected','text_active_languages','text_service_layer_help','text_event_layer_help','text_asset_manager_help','text_api_v1','text_api_v1_help','text_service_layer','text_compat_layer','text_event_layer','text_asset_manager','text_search_adapter','text_native_fallback','text_opt_in','text_read_only','entry_api_v1_status','button_save_architecture',
            'entry_spam','entry_gdpr_retention','entry_admin_device','entry_customer_device','entry_login_protection','entry_login_max_attempts','entry_login_window','entry_security_audit_retention','entry_totp','entry_security_headers','entry_nosniff','entry_referrer_policy','entry_frame_options','entry_permissions_policy','entry_csp_report_only','entry_csp_policy','entry_hsts','entry_gdpr','entry_cookie','column_component','column_value','column_state','column_details','column_severity','column_type','column_table','column_column','column_message','column_request','column_customer','column_email','column_date','column_action','button_process_delete','button_cleanup_old','button_save','button_scan','button_refresh','button_revoke','button_cookie_settings','text_info','text_warning','text_error','text_external','text_core','text_schema_external_help','text_preflight_help','text_totp_enabled','text_totp_disabled','text_totp_setup','text_totp_secret','text_totp_uri','text_totp_code','text_totp_recovery','text_security_audit','text_login_protection_help','text_security_headers_help','text_csp_report_only_help','text_hsts_help','button_totp_prepare','button_totp_enable','button_totp_disable','button_copy','button_phpinfo','button_schema_safe_fix','text_schema_safe_fix_help','text_schema_backup_warning','text_schema_backup_confirm','text_schema_fixable','text_schema_manual','text_schema_external_ok','text_schema_fix_result','text_schema_fix_errors','column_meaning','column_next_action'
        ) as $key) { $data[$key] = $this->language->get($key); }

        $data['breadcrumbs'] = array(
            array('text'=>$this->language->get('text_home'),'href'=>$this->url->link('common/dashboard','user_token='.$this->session->data['user_token'],true)),
            array('text'=>$this->language->get('heading_title'),'href'=>$this->url->link('tool/codecart_core','user_token='.$this->session->data['user_token'],true))
        );
        $data['action'] = $this->url->link('tool/codecart_core','user_token='.$this->session->data['user_token'],true);
        $data['notification_link'] = $this->url->link('tool/notification','user_token='.$this->session->data['user_token'],true);
        $data['scheduler_link'] = $this->url->link('tool/scheduler','user_token='.$this->session->data['user_token'],true);
        $data['phpinfo_link'] = $this->url->link('tool/codecart_core/phpinfo','user_token='.$this->session->data['user_token'],true);
        $data['phpinfo_download_link'] = $this->url->link('tool/codecart_core/phpinfoDownload','user_token='.$this->session->data['user_token'],true);
        $data['button_phpinfo_download'] = $this->language->get('button_phpinfo_download');
        $data['cookie_settings_link'] = $this->url->link('setting/setting','user_token='.$this->session->data['user_token'].'&tab=option',true) . '#ccp-cookie-settings';
        $data['user_token'] = $this->session->data['user_token'];
        $data['active_tab'] = isset($this->request->get['tab']) && preg_match('/^[a-z0-9_-]+$/i',(string)$this->request->get['tab']) ? (string)$this->request->get['tab'] : 'runtime';

        $data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
        unset($this->session->data['success']);
        $data['error_warning'] = isset($this->session->data['error_warning']) ? $this->session->data['error_warning'] : '';
        unset($this->session->data['error_warning']);

        foreach (array('codecart_spam_service_status','codecart_admin_device_authorize_status','codecart_customer_device_authorize_status','codecart_gdpr_status','config_cookie_consent_status') as $key) {
            $data[$key] = (int)$this->config->get($key);
        }

        $data['codecart_storefront_minify_css_status'] = (int)$this->config->get('codecart_storefront_minify_css_status');
        $data['codecart_storefront_minify_js_status'] = (int)$this->config->get('codecart_storefront_minify_js_status');
        $data['codecart_admin_minify_css_status'] = (int)$this->config->get('codecart_admin_minify_css_status');
        $data['codecart_admin_minify_js_status'] = (int)$this->config->get('codecart_admin_minify_js_status');
        $data['minify_stats'] = $this->assetMinificationStats();

        $securityDefaults = array(
            'codecart_admin_login_protection_status'=>1,'codecart_admin_login_max_attempts'=>5,'codecart_admin_login_window_minutes'=>15,'codecart_security_audit_retention_days'=>90,'codecart_gdpr_retention_days'=>365,
            'codecart_security_headers_status'=>1,'codecart_security_nosniff_status'=>1,'codecart_security_referrer_policy'=>'strict-origin-when-cross-origin','codecart_security_frame_options_status'=>0,'codecart_security_frame_options'=>'SAMEORIGIN','codecart_security_permissions_policy_status'=>0,
            'codecart_security_permissions_policy'=>'camera=(), microphone=()','codecart_security_csp_report_only_status'=>0,'codecart_security_csp_report_only_policy'=>"default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:; font-src 'self' data: https:; connect-src 'self' https:; frame-src 'self' https:",'codecart_security_hsts_status'=>0,'codecart_security_hsts_max_age'=>31536000,'codecart_security_hsts_subdomains'=>0
        );
        foreach ($securityDefaults as $key=>$default) { $value=$this->config->get($key); $data[$key]=($value===null||$value==='')?$default:$value; }
        $data['totp_enabled'] = false;
        $data['totp_recovery_count'] = 0;
        $totp = null;
        try {
            $totp = new \CodeCart\Core\TotpManager($this->registry);
            $data['totp_enabled'] = $totp->isEnabled((int)$this->user->getId());
            $data['totp_recovery_count'] = $totp->remainingRecoveryCodes((int)$this->user->getId());
        } catch (\Throwable $e) {
            $data['totp_error'] = $e->getMessage();
        }
        $data['totp_pending_secret'] = isset($this->session->data['codecart_totp_pending_secret']) ? (string)$this->session->data['codecart_totp_pending_secret'] : '';
        $data['totp_pending_uri'] = ($data['totp_pending_secret'] !== '' && $totp) ? $totp->provisioningUri($data['totp_pending_secret'], $this->currentUsername()) : '';
        $data['totp_pending_label'] = ($data['totp_pending_secret'] !== '' && $totp) ? $totp->provisioningLabel($this->currentUsername()) : '';
        $data['totp_recovery_codes'] = isset($this->session->data['codecart_totp_recovery_codes']) && is_array($this->session->data['codecart_totp_recovery_codes']) ? $this->session->data['codecart_totp_recovery_codes'] : array();
        unset($this->session->data['codecart_totp_recovery_codes']);
        try { $data['security_audit'] = (new \CodeCart\Core\SecurityAudit($this->registry))->recent(50); } catch (\Throwable $e) { $data['security_audit'] = array(); $data['security_audit_error'] = $e->getMessage(); }

        try { $data['runtime'] = $this->localizeDiagnosticRows((new \CodeCart\Core\Preflight($this->registry))->rows()); } catch (\Throwable $e) { $data['runtime'] = array(array('component'=>'CodeCart Core','value'=>'partial','state'=>'warning','message'=>$e->getMessage())); }
        $data['db_legacy_tables'] = $this->legacyDatabaseTables();
        $data['db_modernize_url'] = str_replace('&amp;', '&', $this->url->link('tool/codecart_core/dbModernize', 'user_token=' . $this->session->data['user_token'], true));
        foreach (array('text_db_modernize','text_db_modernize_help','text_db_modernize_ok','text_db_modernize_running','text_db_modernize_done','text_db_modernize_large','button_db_modernize','column_engine','column_collation','column_size') as $dbKey) { $data[$dbKey] = $this->language->get($dbKey); }
        $data['schema_result'] = array();
        if (!empty($this->request->get['schema'])) {
            $data['schema_result'] = $this->localizeSchemaResult((new \CodeCart\Core\SchemaRegistry($this->registry))->diff(300));
        }

        try { $registry = new \CodeCart\Core\ModernExtensionRegistry($this->registry); $data['modern_extensions'] = $registry->all(); } catch (\Throwable $e) { $data['modern_extensions'] = array(); }
        try { $gdpr = new \CodeCart\Core\GdprManager($this->registry); $data['gdpr_requests'] = $gdpr->getRequests(100); } catch (\Throwable $e) { $data['gdpr_requests'] = array(); }
        try { $device = new \CodeCart\Core\DeviceAuthorization($this->registry); $data['trusted_admin_devices'] = $device->countTrusted('admin', (int)$this->user->getId()); } catch (\Throwable $e) { $data['trusted_admin_devices'] = 0; }

        $data['codecart_website'] = 'https://codecartpro.com/';
        $data['about_version'] = VERSION;
        $data['about_build'] = 'CodeCart PRO ' . VERSION . (defined('CODECART_CHANNEL') && CODECART_CHANNEL ? ' ' . CODECART_CHANNEL : '');
        $data['about_fixed_items'] = $this->language->get('about_fixed_items');
        $data['about_improved_items'] = $this->language->get('about_improved_items');
        $data['about_security_items'] = $this->language->get('about_security_items');
        $data['about_future_items'] = $this->language->get('about_future_items');
        foreach (array('about_fixed_items','about_improved_items','about_security_items','about_future_items') as $listKey) {
            if (!is_array($data[$listKey])) { $data[$listKey] = array(); }
        }
        $data['server_requirements'] = $this->localizeServerRequirements($this->serverRequirements());
        $data['comparison_rows'] = $this->localizeComparisonRows($this->comparisonRows());
        $data['icon_catalog'] = $this->iconCatalog();
        $data['icon_full_count'] = $this->iconFullCount();
        $data['icon_full_catalog'] = array();
        $data['icon_full_url'] = $this->url->link('tool/codecart_core/iconsFull', 'user_token=' . $this->session->data['user_token'], true);
        $data['icon_categories'] = array(
            'all' => $this->language->get('text_icon_all'),
            'social' => $this->language->get('text_icon_social'),
            'commerce' => $this->language->get('text_icon_commerce'),
            'payment' => $this->language->get('text_icon_payment'),
            'shipping' => $this->language->get('text_icon_shipping'),
            'contact' => $this->language->get('text_icon_contact'),
            'interface' => $this->language->get('text_icon_interface'),
            'security' => $this->language->get('text_icon_security'),
            'media' => $this->language->get('text_icon_media'),
            'development' => $this->language->get('text_icon_development'),
            'other' => $this->language->get('text_icon_other')
        );
        $data['modern_social_icons'] = array(
            array('class' => 'fa-brands fa-x-twitter', 'name' => 'X', 'category' => 'social'),
            array('class' => 'fa-brands fa-tiktok', 'name' => 'TikTok', 'category' => 'social'),
            array('class' => 'fa-brands fa-threads', 'name' => 'Threads', 'category' => 'social'),
            array('class' => 'fa-brands fa-viber', 'name' => 'Viber', 'category' => 'social'),
            array('class' => 'fa-brands fa-discord', 'name' => 'Discord', 'category' => 'social')
        );

        try {
            $activeTheme = strtolower(trim((string)$this->config->get('config_theme')));
            if ($activeTheme === '') { $activeTheme = 'default'; }
            $themeDirectory = trim((string)$this->config->get('theme_' . $activeTheme . '_directory'));
            if ($themeDirectory === '') { $themeDirectory = $activeTheme; }
            $fontAwesome = new \CodeCart\Core\FontAwesomeManager($themeDirectory, explode(',', (string)$this->config->get('codecart_fontawesome_extra_icons')));
            $data['icon_scan'] = $fontAwesome->scanCatalog();
            $data['icon_core_count'] = count($fontAwesome->getSupportedIcons());
            $requestedIconMode = strtolower((string)$this->config->get('theme_default_icon_mode'));
            if ($requestedIconMode === '' || $requestedIconMode === 'standard') { $requestedIconMode = $requestedIconMode === 'standard' ? 'full' : 'auto'; }
            if (!in_array($requestedIconMode, array('auto','core','full'), true)) { $requestedIconMode = 'auto'; }
            $data['icon_mode_requested'] = $requestedIconMode;
            $data['icon_mode_effective'] = $fontAwesome->resolveCatalogMode($requestedIconMode);
        } catch (\Throwable $e) {
            $data['icon_scan'] = array('format'=>2,'unknown'=>array('scanner'),'full_fallback'=>array(),'unresolved'=>array('scanner'),'occurrences'=>array(),'occurrences_truncated'=>false,'files'=>0,'scanned_at'=>time(),'scanner_error'=>$e->getMessage());
            $data['icon_core_count'] = 0;
            $data['icon_mode_requested'] = 'auto';
            $data['icon_mode_effective'] = 'full';
        }
        $data['icon_mode_options'] = array('auto'=>$this->language->get('text_icon_auto'),'core'=>$this->language->get('text_icon_core'),'full'=>$this->language->get('text_icon_full'));
        $data['icon_extra_names'] = (string)$this->config->get('codecart_fontawesome_extra_icons');
        $data['icon_scan_time'] = !empty($data['icon_scan']['scanned_at']) ? date('Y-m-d H:i:s', (int)$data['icon_scan']['scanned_at']) : '—';
        $data['icon_unknown_count'] = !empty($data['icon_scan']['unknown']) && is_array($data['icon_scan']['unknown']) ? count($data['icon_scan']['unknown']) : 0;
        $data['icon_unresolved_count'] = !empty($data['icon_scan']['unresolved']) && is_array($data['icon_scan']['unresolved']) ? count($data['icon_scan']['unresolved']) : 0;

        $data['codecart_api_v1_status'] = (int)$this->config->get('codecart_api_v1_status');
        $compatDiagnostics = array('main_category_source' => 'unavailable', 'active_languages' => 0);
        try {
            $compat = $this->registry->has('codecart_compat') ? $this->registry->get('codecart_compat') : new \CodeCart\Core\CompatibilityLayer($this->registry);
            if (!$this->registry->has('codecart_compat')) { $this->registry->set('codecart_compat', $compat); }
            $compatDiagnostics = $compat->diagnostics();
        } catch (\Throwable $e) {
            $compatDiagnostics['error'] = $e->getMessage();
        }
        $compatDetails = $this->language->get('text_compat_detected') . ': ' . $compatDiagnostics['main_category_source'] . '; ' . $this->language->get('text_active_languages') . ': ' . (int)$compatDiagnostics['active_languages'];
        $data['architecture'] = array(
            array('name'=>$this->language->get('text_service_layer'),'status'=>'active','details'=>$this->language->get('text_service_layer_help')),
            array('name'=>$this->language->get('text_compat_layer'),'status'=>'active','details'=>$compatDetails),
            array('name'=>$this->language->get('text_event_layer'),'status'=>'active','details'=>$this->language->get('text_event_layer_help')),
            array('name'=>$this->language->get('text_asset_manager'),'status'=>'active','details'=>$this->language->get('text_asset_manager_help')),
            array('name'=>$this->language->get('text_search_adapter'),'status'=>'active','details'=>$this->language->get('text_native_fallback')),
            array('name'=>$this->language->get('text_api_v1'),'status'=>$data['codecart_api_v1_status']?'enabled':'disabled','details'=>$this->language->get('text_read_only'))
        );

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('tool/codecart_core',$data));
    }

    private function ensureCodeCartCoreAutoload() {
        if (!class_exists('CodeCartPsr4')) {
            $loader = DIR_SYSTEM . 'library/codecart/psr4.php';
            if (is_file($loader)) { require_once($loader); }
        }
        if (class_exists('CodeCartPsr4') && is_dir(DIR_SYSTEM . 'library/codecart/src/')) {
            CodeCartPsr4::register('CodeCart\\Core\\', DIR_SYSTEM . 'library/codecart/src/');
        }
    }

    private function ensureCoreServices() {
        try {
            if (!$this->registry->has('codecart_compat') && class_exists('CodeCart\\Core\\CompatibilityLayer')) {
                $this->registry->set('codecart_compat', new \CodeCart\Core\CompatibilityLayer($this->registry));
            }
        } catch (\Throwable $e) {
            // The Core page must remain renderable on partially updated stores.
        }
    }

    private function processAction() {
        $action = isset($this->request->post['requested_action']) ? (string)$this->request->post['requested_action'] : (isset($this->request->post['core_action']) ? (string)$this->request->post['core_action'] : 'save');
        if ($action === 'save' || $action === 'save_security') {
            $keys = array('codecart_spam_service_status','codecart_admin_device_authorize_status','codecart_customer_device_authorize_status','codecart_admin_login_protection_status','codecart_security_headers_status','codecart_security_nosniff_status','codecart_security_frame_options_status','codecart_security_permissions_policy_status','codecart_security_csp_report_only_status','codecart_security_hsts_status','codecart_security_hsts_subdomains');
            foreach ($keys as $key) {
                $value = !empty($this->request->post[$key]) ? '1' : '0';
                $this->upsertSetting('codecart_core',$key,$value);
                $this->config->set($key,$value);
            }
            foreach (array('codecart_admin_login_max_attempts'=>array(3,20,5),'codecart_admin_login_window_minutes'=>array(5,240,15),'codecart_security_audit_retention_days'=>array(7,730,90),'codecart_security_hsts_max_age'=>array(300,63072000,31536000)) as $key=>$rule) { $value=isset($this->request->post[$key])?(int)$this->request->post[$key]:$rule[2]; $value=max($rule[0],min($rule[1],$value)); $this->upsertSetting('codecart_core',$key,(string)$value); $this->config->set($key,$value); }
            foreach (array('codecart_security_frame_options'=>array('DENY','SAMEORIGIN')) as $key=>$allowed) { $value=isset($this->request->post[$key])?strtoupper(trim((string)$this->request->post[$key])):$allowed[1]; if(!in_array($value,$allowed,true)){$value=$allowed[1];}$this->upsertSetting('codecart_core',$key,$value);$this->config->set($key,$value); }
            $policy=isset($this->request->post['codecart_security_permissions_policy'])?trim(str_replace(array("\r","\n","\0"),'',(string)$this->request->post['codecart_security_permissions_policy'])):'camera=(), microphone=()'; $policy=substr($policy,0,1000); $this->upsertSetting('codecart_core','codecart_security_permissions_policy',$policy);$this->config->set('codecart_security_permissions_policy',$policy);
            $referrer = isset($this->request->post['codecart_security_referrer_policy']) ? trim((string)$this->request->post['codecart_security_referrer_policy']) : 'strict-origin-when-cross-origin';
            $allowedReferrer = array('no-referrer','no-referrer-when-downgrade','origin','origin-when-cross-origin','same-origin','strict-origin','strict-origin-when-cross-origin');
            if (!in_array($referrer, $allowedReferrer, true)) { $referrer = 'strict-origin-when-cross-origin'; }
            $this->upsertSetting('codecart_core','codecart_security_referrer_policy',$referrer); $this->config->set('codecart_security_referrer_policy',$referrer);
            $csp = isset($this->request->post['codecart_security_csp_report_only_policy']) ? trim(str_replace(array("\r","\n","\0"), '', (string)$this->request->post['codecart_security_csp_report_only_policy'])) : "default-src 'self'";
            $csp = substr($csp, 0, 4000);
            $this->upsertSetting('codecart_core','codecart_security_csp_report_only_policy',$csp); $this->config->set('codecart_security_csp_report_only_policy',$csp);
            $this->session->data['success'] = $this->language->get('text_settings_saved');
            return;
        }
        if ($action === 'save_performance') {
            foreach (array('codecart_storefront_minify_css_status','codecart_storefront_minify_js_status','codecart_admin_minify_css_status','codecart_admin_minify_js_status') as $key) {
                $value = !empty($this->request->post[$key]) ? '1' : '0';
                $this->upsertSetting('codecart_core', $key, $value);
                $this->config->set($key, $value);
            }
            $this->session->data['success'] = $this->language->get('text_settings_saved');
            return;
        }

        if ($action === 'save_architecture') {
            $value = !empty($this->request->post['codecart_api_v1_status']) ? '1' : '0';
            $this->upsertSetting('codecart_core','codecart_api_v1_status',$value);
            $this->config->set('codecart_api_v1_status',$value);
            $this->session->data['success'] = $this->language->get('text_settings_saved');
            return;
        }
        if ($action === 'save_privacy') {
            $value = !empty($this->request->post['codecart_gdpr_status']) ? '1' : '0';
            $this->upsertSetting('codecart_core','codecart_gdpr_status',$value);
            $this->config->set('codecart_gdpr_status',$value);
            $days=isset($this->request->post['codecart_gdpr_retention_days'])?(int)$this->request->post['codecart_gdpr_retention_days']:365;
            $days=max(30,min(3650,$days));
            $this->upsertSetting('codecart_core','codecart_gdpr_retention_days',(string)$days);
            $this->config->set('codecart_gdpr_retention_days',$days);
            $this->session->data['success'] = $this->language->get('text_settings_saved');
            return;
        }
        if ($action === 'totp_prepare') {
            $totp = new \CodeCart\Core\TotpManager($this->registry);
            if ($totp->isEnabled((int)$this->user->getId())) { return; }
            $secret = $totp->createSecret();
            $this->session->data['codecart_totp_pending_secret'] = $secret;
            unset($this->session->data['codecart_totp_recovery_codes']);
            $this->session->data['success'] = $this->language->get('text_totp_setup');
            return;
        }
        if ($action === 'totp_enable') {
            $secret = isset($this->session->data['codecart_totp_pending_secret']) ? (string)$this->session->data['codecart_totp_pending_secret'] : '';
            if ($secret === '') { throw new \RuntimeException($this->language->get('error_totp_prepare')); }
            $code = isset($this->request->post['totp_code']) ? trim((string)$this->request->post['totp_code']) : '';
            $totp = new \CodeCart\Core\TotpManager($this->registry);
            $codes = $totp->enable((int)$this->user->getId(), $secret, $code);
            unset($this->session->data['codecart_totp_pending_secret']);
            $this->session->data['codecart_totp_recovery_codes'] = $codes;
            $this->session->data['codecart_totp_verified_user_id'] = (int)$this->user->getId();
            (new \CodeCart\Core\SecurityAudit($this->registry))->add('admin.totp.enable','success','info','admin',(int)$this->user->getId(),$this->currentUsername());
            $this->session->data['success'] = $this->language->get('text_totp_enabled');
            return;
        }
        if ($action === 'totp_disable') {
            $code = isset($this->request->post['totp_code']) ? trim((string)$this->request->post['totp_code']) : '';
            $totp = new \CodeCart\Core\TotpManager($this->registry);
            $totp->disable((int)$this->user->getId(), $code);
            unset($this->session->data['codecart_totp_pending_secret'], $this->session->data['codecart_totp_verified_user_id']);
            (new \CodeCart\Core\SecurityAudit($this->registry))->add('admin.totp.disable','success','warning','admin',(int)$this->user->getId(),$this->currentUsername());
            $this->session->data['success'] = $this->language->get('text_totp_disabled');
            return;
        }
        if ($action === 'icons_save_mode') {
            $mode = isset($this->request->post['theme_default_icon_mode']) ? strtolower(trim((string)$this->request->post['theme_default_icon_mode'])) : 'auto';
            if (!in_array($mode, array('auto','core','full'), true)) { $mode = 'auto'; }
            $this->upsertSetting('theme_default', 'theme_default_icon_mode', $mode);
            $this->config->set('theme_default_icon_mode', $mode);
            \CodeCart\Core\FontAwesomeManager::invalidateCache();
            $this->session->data['success'] = $this->language->get('text_icon_mode_saved');
            return;
        }
        if ($action === 'icons_save_extras' || $action === 'icons_add_found') {
            $activeTheme = strtolower(trim((string)$this->config->get('config_theme'))) ?: 'default';
            $themeDirectory = trim((string)$this->config->get('theme_' . $activeTheme . '_directory')) ?: $activeTheme;
            $saved = explode(',', (string)$this->config->get('codecart_fontawesome_extra_icons'));
            if ($action === 'icons_add_found') {
                $scan = (new \CodeCart\Core\FontAwesomeManager($themeDirectory, $saved))->scanCatalog(true);
                $names = array_merge($saved, $scan['full_fallback']);
            } else {
                $input = $this->request->post['icon_extra_names'] ?? '';
                if (!is_string($input) || strlen($input) > 20000) { throw new \RuntimeException($this->language->get('error_icon_extras')); }
                $names = preg_split('/[\s,;]+/', trim($input), -1, PREG_SPLIT_NO_EMPTY);
            }
            $file = DIR_SYSTEM . 'config/codecart_fontawesome_extras.json';
            $manifest = is_file($file) ? json_decode((string)file_get_contents($file), true) : null;
            if (!is_array($manifest) || empty($manifest['icons'])) { throw new \RuntimeException($this->language->get('error_icon_extras')); }
            $accepted = array();
            $skipped = 0;
            foreach ($names as $name) {
                $name = strtolower(trim((string)$name));
                if (strpos($name, 'fa-') === 0) { $name = substr($name, 3); }
                if ($name === '' || in_array($name, array('fa','fas','far','fab','solid','regular','brands'), true)) { continue; }
                if (isset($manifest['icons'][$name]) && count($accepted) < 500) { $accepted[$name] = $name; }
                else { $skipped++; }
            }
            // All binary fonts were compiled and shipped with the release.
            $manager = new \CodeCart\Core\FontAwesomeManager($themeDirectory, array_values($accepted));
            $value = implode(',', $manager->getExtraIcons());
            $this->upsertSetting('codecart_icons', 'codecart_fontawesome_extra_icons', $value);
            $this->config->set('codecart_fontawesome_extra_icons', $value);
            \CodeCart\Core\FontAwesomeManager::invalidateCache();
            $this->session->data['success'] = sprintf($this->language->get('text_icon_extras_saved'), count($manager->getExtraIcons()), $skipped);
            return;
        }
        if ($action === 'icons_rescan') {
            \CodeCart\Core\FontAwesomeManager::invalidateCache();
            $activeTheme = strtolower(trim((string)$this->config->get('config_theme')));
            if ($activeTheme === '') { $activeTheme = 'default'; }
            $themeDirectory = trim((string)$this->config->get('theme_' . $activeTheme . '_directory'));
            if ($themeDirectory === '') { $themeDirectory = $activeTheme; }
            $scan = (new \CodeCart\Core\FontAwesomeManager($themeDirectory, explode(',', (string)$this->config->get('codecart_fontawesome_extra_icons'))))->scanCatalog(true);
            $this->session->data['success'] = sprintf($this->language->get('text_icon_scan_complete'), (int)$scan['files'], count($scan['unknown']));
            return;
        }
        if ($action === 'refresh_extensions') {
            $items = (new \CodeCart\Core\ModernExtensionRegistry($this->registry))->refresh();
            $this->session->data['success'] = sprintf($this->language->get('text_extensions_refreshed'), count($items));
            return;
        }
        if ($action === 'revoke_admin_devices') {
            (new \CodeCart\Core\DeviceAuthorization($this->registry))->revokeAll('admin',(int)$this->user->getId());
            $this->session->data['success'] = $this->language->get('text_devices_revoked');
            return;
        }
        if ($action === 'cleanup_security_audit') {
            $days=(int)$this->config->get('codecart_security_audit_retention_days');
            $deleted=(new \CodeCart\Core\SecurityAudit($this->registry))->cleanup($days>0?$days:90);
            $this->session->data['success']=sprintf($this->language->get('text_cleanup_done'),$deleted);
            return;
        }
        if ($action === 'cleanup_gdpr') {
            $days=(int)$this->config->get('codecart_gdpr_retention_days');
            $deleted=(new \CodeCart\Core\GdprManager($this->registry))->cleanup($days>0?$days:365);
            $this->session->data['success']=sprintf($this->language->get('text_cleanup_done'),$deleted);
            return;
        }
        if ($action === 'gdpr_delete') {
            if (!(int)$this->config->get('codecart_gdpr_status')) { throw new \RuntimeException($this->language->get('error_gdpr_disabled')); }
            $requestId = isset($this->request->post['request_id']) ? (int)$this->request->post['request_id'] : 0;
            (new \CodeCart\Core\GdprManager($this->registry))->processDelete($requestId,(int)$this->user->getId());
            $this->session->data['success'] = $this->language->get('text_gdpr_processed');
            return;
        }
        if ($action === 'schema_safe_fix') {
            if (empty($this->request->post['schema_backup_confirm'])) {
                throw new \RuntimeException($this->language->get('text_schema_backup_warning'));
            }
            $repair = (new \CodeCart\Core\SchemaRegistry($this->registry))->repairSafe(300);
            $this->refreshDatabaseModernizationFlag();
            $this->session->data['success'] = sprintf($this->language->get('text_schema_fix_result'), (int)$repair['fixed'], (int)$repair['skipped']);
            if (!empty($repair['errors'])) {
                $this->session->data['error_warning'] = sprintf($this->language->get('text_schema_fix_errors'), (int)$repair['errors']);
            }
            return;
        }
        throw new \InvalidArgumentException($this->language->get('error_action'));
    }

    private function upsertSetting($code,$key,$value) {
        $q=$this->db->query("SELECT setting_id FROM `".DB_PREFIX."setting` WHERE store_id='0' AND `key`='".$this->db->escape($key)."' LIMIT 1");
        if ($q->num_rows) {
            $this->db->query("UPDATE `".DB_PREFIX."setting` SET code='".$this->db->escape($code)."', value='".$this->db->escape($value)."', serialized='0' WHERE setting_id='".(int)$q->row['setting_id']."'");
        } else {
            $this->db->query("INSERT INTO `".DB_PREFIX."setting` SET store_id='0', code='".$this->db->escape($code)."', `key`='".$this->db->escape($key)."', value='".$this->db->escape($value)."', serialized='0'");
        }
    }

    private function currentUsername() {
        try { $q=$this->db->query("SELECT username FROM `".DB_PREFIX."user` WHERE user_id='".(int)$this->user->getId()."' LIMIT 1"); return $q->num_rows?(string)$q->row['username']:''; } catch (\Throwable $e) { return ''; }
    }

    public function dbModernize() {
        $this->ensureCodeCartCoreAutoload();
        $this->load->language('tool/codecart_core');
        $json = array();

        $provided = isset($this->request->get['user_token']) ? (string)$this->request->get['user_token'] : '';
        $expected = isset($this->session->data['user_token']) ? (string)$this->session->data['user_token'] : '';

        if (!isset($this->request->server['REQUEST_METHOD']) || strtoupper((string)$this->request->server['REQUEST_METHOD']) !== 'POST') {
            $json['error'] = 'Invalid request method.';
        } elseif ($provided === '' || $expected === '' || !hash_equals($expected, $provided)) {
            $json['error'] = 'Invalid security token.';
        } elseif (!$this->user->hasPermission('modify', 'tool/codecart_core')) {
            $json['error'] = $this->language->get('error_permission');
        } elseif (empty($this->request->post['backup_confirm'])) {
            $json['error'] = $this->language->get('text_schema_backup_warning');
        }

        if (!$json) {
            try {
                @set_time_limit(300);
                // One table per request keeps every web request short on shared hosting.
                // Tables >= 256 MB are skipped here and left for php cli.php db:migrate --large.
                $result = (new \CodeCart\Core\DatabaseModernizer($this->registry))->migrate(false, 1);
                $json['changed'] = $result['changed'];
                $json['errors'] = $result['errors'];
                $json['skipped'] = count($result['skipped']);
                $json['remaining'] = count($this->legacyDatabaseTables());
                $json['done'] = empty($result['changed']);
            } catch (\Throwable $e) {
                $json['error'] = $e->getMessage();
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function legacyDatabaseTables() {
        try {
            $query = $this->db->query("SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, (DATA_LENGTH + INDEX_LENGTH) AS bytes FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND LEFT(TABLE_NAME, " . (int)strlen(DB_PREFIX) . ") = '" . $this->db->escape(DB_PREFIX) . "' AND (UPPER(ENGINE) <> 'INNODB' OR (TABLE_COLLATION IS NOT NULL AND TABLE_COLLATION NOT LIKE 'utf8mb4\\_%')) ORDER BY TABLE_NAME ASC");
        } catch (\Throwable $e) {
            return array();
        }
        $rows = array();
        foreach ($query->rows as $row) {
            $rows[] = array(
                'table' => (string)$row['TABLE_NAME'],
                'engine' => (string)$row['ENGINE'],
                'collation' => (string)$row['TABLE_COLLATION'],
                'size' => round(((int)$row['bytes']) / 1048576, 2) . ' MB',
                'large' => (int)$row['bytes'] >= 268435456
            );
        }
        return $rows;
    }

    private function refreshDatabaseModernizationFlag() {
        $required = $this->legacyDatabaseTables() ? 1 : 0;
        $this->upsertSetting('codecart_core', 'codecart_db_modernization_required', $required);
        $this->config->set('codecart_db_modernization_required', $required);
    }

    public function iconsFull() {
        if (!$this->user->hasPermission('access', 'tool/codecart_core')) {
            $this->response->setStatusCode(403);
            $this->response->addHeader('Content-Type: application/json; charset=utf-8');
            $this->response->setOutput(json_encode(array('error' => 'Forbidden')));
            return;
        }

        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Cache-Control: private, no-store, max-age=0');
        $this->response->setOutput(json_encode(
            array('icons' => $this->iconFullCatalog()),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
    }

    public function phpinfo() {
        $this->load->language('tool/codecart_core');
        if (!$this->user->hasPermission('access', 'tool/codecart_core')) {
            $this->response->setStatusCode(403);
            $this->response->setOutput('Forbidden');
            return;
        }
        ob_start();
        phpinfo();
        $data = array();
        $data['heading_title'] = $this->language->get('button_phpinfo');
        $data['phpinfo_download_link'] = $this->url->link('tool/codecart_core/phpinfoDownload', 'user_token=' . $this->session->data['user_token'], true);
        $data['phpinfo_download_text'] = $this->language->get('button_phpinfo_download');
        $data['button_close'] = $this->language->get('button_close');
        $data['phpinfo'] = (string)ob_get_clean();
        $data['phpinfo'] = preg_replace('@<style[^>]*?>.*?</style>@si', '', $data['phpinfo']);
        $this->response->setOutput($this->load->view('extension/dashboard/phpinfo', $data));
    }

    public function phpinfoDownload() {
        $this->load->language('tool/codecart_core');
        if (!$this->user->hasPermission('access', 'tool/codecart_core')) {
            $this->response->setStatusCode(403);
            $this->response->setOutput('Forbidden');
            return;
        }
        ob_start();
        phpinfo();
        $html = (string)ob_get_clean();
        $filename = 'phpinfo-' . date('Ymd-His') . '.html';
        $this->response->addHeader('Content-Type: text/html; charset=UTF-8');
        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '"');
        $this->response->addHeader('X-Content-Type-Options: nosniff');
        $this->response->setOutput($html);
    }

    private function serverRequirements() {
        $phpOk = version_compare(PHP_VERSION, '8.1.0', '>=') && version_compare(PHP_VERSION, '8.6.0', '<');
        $required = array(
            array('name'=>'PHP 8.1–8.5','value'=>PHP_VERSION . ' / ' . PHP_SAPI,'required'=>true,'ok'=>$phpOk),
            array('name'=>'mysqli / pdo_mysql','value'=>(extension_loaded('mysqli') ? 'mysqli ' : '') . (extension_loaded('pdo_mysql') ? 'pdo_mysql' : ''),'required'=>true,'ok'=>extension_loaded('mysqli') || extension_loaded('pdo_mysql')),
            array('name'=>'pdo_mysql','value'=>extension_loaded('pdo_mysql') ? 'loaded' : 'recommended','required'=>false,'ok'=>extension_loaded('pdo_mysql')),
            array('name'=>'GD','value'=>extension_loaded('gd') ? 'loaded' : 'missing','required'=>true,'ok'=>extension_loaded('gd')),
            array('name'=>'cURL','value'=>extension_loaded('curl') ? 'loaded' : 'missing','required'=>true,'ok'=>extension_loaded('curl')),
            array('name'=>'zlib','value'=>extension_loaded('zlib') ? 'loaded' : 'missing','required'=>true,'ok'=>extension_loaded('zlib')),
            array('name'=>'mbstring','value'=>extension_loaded('mbstring') ? 'loaded' : 'missing','required'=>true,'ok'=>extension_loaded('mbstring')),
            array('name'=>'DOM + XMLWriter','value'=>(extension_loaded('dom') && extension_loaded('xmlwriter')) ? 'loaded' : 'missing','required'=>true,'ok'=>extension_loaded('dom') && extension_loaded('xmlwriter')),
            array('name'=>'ZIP','value'=>extension_loaded('zip') ? 'loaded' : 'missing','required'=>true,'ok'=>extension_loaded('zip')),
            array('name'=>'SimpleXML','value'=>(extension_loaded('simplexml') && function_exists('simplexml_load_string')) ? 'loaded' : 'missing','required'=>true,'ok'=>extension_loaded('simplexml') && function_exists('simplexml_load_string')),
            array('name'=>'OpenSSL + fileinfo','value'=>(extension_loaded('openssl') && extension_loaded('fileinfo')) ? 'loaded' : 'missing','required'=>true,'ok'=>extension_loaded('openssl') && extension_loaded('fileinfo')),
            array('name'=>'GD WebP','value'=>function_exists('imagewebp') ? 'supported' : 'missing','required'=>true,'ok'=>function_exists('imagewebp')),
            array('name'=>'GD AVIF','value'=>(function_exists('imageavif') && function_exists('imagecreatefromavif')) ? 'supported' : 'optional','required'=>false,'ok'=>function_exists('imageavif') && function_exists('imagecreatefromavif')),
            array('name'=>'intl','value'=>extension_loaded('intl') ? 'loaded' : 'optional','required'=>false,'ok'=>extension_loaded('intl')),
            array('name'=>'OPcache','value'=>(function_exists('opcache_get_status') && @opcache_get_status(false)) ? 'enabled' : 'recommended','required'=>false,'ok'=>function_exists('opcache_get_status') && (bool)@opcache_get_status(false)),
            array('name'=>'Redis / APCu / Memcached','value'=>'optional cache backends','required'=>false,'ok'=>extension_loaded('redis') || extension_loaded('apcu') || extension_loaded('memcached')),
            array('name'=>'HTTPS','value'=>(!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off') ? 'active' : 'check server','required'=>true,'ok'=>!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off'),
            array('name'=>'file_uploads','value'=>filter_var(ini_get('file_uploads'), FILTER_VALIDATE_BOOLEAN) ? 'On' : 'Off','required'=>true,'ok'=>filter_var(ini_get('file_uploads'), FILTER_VALIDATE_BOOLEAN)),
            array('name'=>'session.auto_start','value'=>(string)ini_get('session.auto_start'),'required'=>true,'ok'=>!filter_var(ini_get('session.auto_start'), FILTER_VALIDATE_BOOLEAN)),
            array('name'=>'Storage / cache / logs','value'=>(is_writable(DIR_STORAGE) && is_writable(DIR_CACHE) && is_writable(DIR_LOGS)) ? 'writable' : 'check permissions','required'=>true,'ok'=>is_writable(DIR_STORAGE) && is_writable(DIR_CACHE) && is_writable(DIR_LOGS)),
            array('name'=>'OCMOD modification/','value'=>(defined('DIR_MODIFICATION') && is_writable(DIR_MODIFICATION)) ? 'writable' : 'check permissions','required'=>true,'ok'=>defined('DIR_MODIFICATION') && is_writable(DIR_MODIFICATION)),
            array('name'=>'image/','value'=>(defined('DIR_IMAGE') && is_writable(DIR_IMAGE)) ? 'writable' : 'check permissions','required'=>true,'ok'=>defined('DIR_IMAGE') && is_writable(DIR_IMAGE))
        );
        try {
            $version = $this->db->query('SELECT VERSION() AS version');
            array_splice($required, 1, 0, array(array('name'=>'MySQL / MariaDB','value'=>$version->num_rows ? (string)$version->row['version'] : 'connected','required'=>true,'ok'=>true)));
        } catch (\Throwable $e) {}
        return $required;
    }

    private function localizeDiagnosticRows(array $rows) {
        $valueMap = array(
            'loaded'=>'diag_loaded','missing'=>'diag_missing','supported'=>'diag_supported','not supported'=>'diag_not_supported','enabled'=>'diag_enabled','disabled'=>'diag_disabled','recommended'=>'diag_recommended','optional'=>'diag_optional','active'=>'diag_active','not active'=>'diag_not_active','writable'=>'diag_writable','inside document root'=>'diag_inside_root','outside document root'=>'diag_outside_root','unknown'=>'diag_unknown','never'=>'diag_never','not configured'=>'diag_not_configured','check failed'=>'diag_check_failed','On'=>'diag_enabled','Off'=>'diag_disabled'
        );
        $messageMap = array(
            'Supported: PHP 8.1–8.5 in the WEB/FPM profile.'=>'diag_msg_php','Required WEB/FPM extension.'=>'diag_msg_required_ext','Required image format.'=>'diag_msg_required_image','Recommended for native AVIF support.'=>'diag_msg_avif','Recommended for production.'=>'diag_msg_production','Current WEB/FPM value.'=>'diag_msg_current_web','Applied to customer/admin generic file uploads in addition to PHP limits.'=>'diag_msg_upload_limit','Compressed .ocmod.zip limit; extracted archives also have entry/path safety limits.'=>'diag_msg_extension_limit','Production should use Off with log_errors On.'=>'diag_msg_display_errors','Required for production checkout and secure cookies.'=>'diag_msg_https','Writable.'=>'diag_msg_writable','Directory must exist and be writable.'=>'diag_msg_not_writable','Prefer storage outside the public web root.'=>'diag_msg_storage','Core is running with a strict SQL mode.'=>'diag_msg_strict_ok','Compatibility SQL mode is active. Use strict SQL mode after the compatibility check reports no blockers.'=>'diag_msg_strict_warn','Use Database Modernizer only after a full backup.'=>'diag_msg_db_modernize','Schema version matches this build.'=>'diag_msg_schema_ok','Core schema version differs from this build. Run the migration check.'=>'diag_msg_schema_warn','Latest recorded Core migration.'=>'diag_msg_migration_latest','No CodeCart PRO Core migration records are available yet.'=>'diag_msg_migration_none','Review Scheduler / Queue.'=>'diag_msg_queue','Configuration check only; delivery still requires a send-test.'=>'diag_msg_smtp','Current OpenCart mail engine.'=>'diag_msg_mail','Low disk space can break cache, image generation, logs and updates.'=>'diag_msg_disk','OpenCart-compatible SQL mode (default for OpenCart/ocStore modules).'=>'diag_msg_sql_compat','Large XLSX imports use streaming XMLReader mode.'=>'diag_msg_xlsx_stream','Regular XLSX import remains available through SimpleXML, but very large workbooks can require significantly more memory.'=>'diag_msg_xlsx_simplexml'
        );
        foreach ($rows as &$row) {
            $value = isset($row['value']) ? (string)$row['value'] : '';
            if (isset($valueMap[$value])) { $row['value'] = $this->language->get($valueMap[$value]); }
            $message = isset($row['message']) ? (string)$row['message'] : '';
            if (isset($messageMap[$message])) { $row['message'] = $this->language->get($messageMap[$message]); }
            if (!empty($row['id'])) {
                $componentKey = 'diag_component_' . preg_replace('/[^a-z0-9]+/', '_', strtolower((string)$row['id']));
                $componentText = $this->language->get($componentKey);
                if ($componentText !== $componentKey) { $row['component'] = $componentText; }
            }
            if (strpos($value, 'legacy tables: ') === 0) { $row['value'] = 'InnoDB / utf8mb4 · ' . substr($value, 15); }
        }
        unset($row);
        return $rows;
    }

    private function localizeServerRequirements(array $rows) {
        $valueMap = array('loaded'=>'diag_loaded','missing'=>'diag_missing','supported'=>'diag_supported','optional'=>'diag_optional','enabled'=>'diag_enabled','recommended'=>'diag_recommended','active'=>'diag_active','check server'=>'diag_not_active','writable'=>'diag_writable','check permissions'=>'diag_missing','On'=>'diag_enabled','Off'=>'diag_disabled','optional cache backends'=>'diag_optional_cache_backends');
        $nameMap = array('Storage / cache / logs'=>'diag_storage_cache_logs');
        foreach ($rows as &$row) {
            $value = isset($row['value']) ? (string)$row['value'] : '';
            if (isset($valueMap[$value])) { $row['value'] = $this->language->get($valueMap[$value]); }
            if (isset($row['name'], $nameMap[$row['name']])) { $row['name'] = $this->language->get($nameMap[$row['name']]); }
        }
        unset($row);
        return $rows;
    }

    private function localizeComparisonRows(array $rows) {
        $map = array(
            'Content editor'=>'cmp_content_editor','Image formats'=>'cmp_image_formats','Cache backends'=>'cmp_cache_backends','Scheduler / Queue'=>'cmp_scheduler_queue',
            'System Notifications'=>'cmp_system_notifications','Central Spam Service'=>'cmp_spam_service','Built-in'=>'cmp_builtin','Built-in, opt-in on UPDATE'=>'cmp_builtin_optin',
            'TOTP + trusted device layer'=>'cmp_totp','Read-only diagnostics + migrations'=>'cmp_schema','PSR-4 / Services / Manifest / Extension Points'=>'cmp_modern',
            'contact / reviews / registration / forgotten / returns / GDPR'=>'cmp_spam_scope','form-specific'=>'cmp_form_specific','Core Dashboard'=>'cmp_core_dashboard',
            'not used by Core Dashboard; Legacy assets retained'=>'cmp_legacy_assets','CodeCart PRO Slider; Swiper 3 Legacy'=>'cmp_slider','3.7.1 Legacy Core'=>'cmp_jquery_legacy','3.4.1 Legacy Core'=>'cmp_bootstrap_legacy',
            '2.1.0 manual / CLI only'=>'cmp_scss_cli','2.1.0 runtime'=>'cmp_scss_runtime_oc','2.0.1 runtime'=>'cmp_scss_runtime_ocs','SeoPro + language prefixes + canonical/faceted policy'=>'cmp_seo',
            'JPEG/PNG/WebP/AVIF + safe fallback'=>'cmp_images','File / APCu / Memcached / Redis + fallback'=>'cmp_cache','File'=>'cmp_file','6.7.2 + AUTO/Core/Full + FA4 compatibility'=>'cmp_fa','Summernote 0.9.1 local'=>'cmp_summernote'
        );
        foreach ($rows as &$row) {
            foreach (array('component','opencart','ocstore','codecart') as $field) {
                if (isset($row[$field], $map[$row[$field]])) {
                    $text = $this->language->get($map[$row[$field]]);
                    if ($text !== $map[$row[$field]]) { $row[$field] = $text; }
                }
            }
        }
        unset($row);
        return $rows;
    }

    private function localizeSchemaResult(array $result) {
        if (!$result) { return $result; }
        $summaryMap = array('tables_expected'=>'schema_tables_expected','tables_present'=>'schema_tables_present','missing_tables'=>'schema_missing_tables','missing_columns'=>'schema_missing_columns','type_mismatches'=>'schema_type_mismatches','engine_mismatches'=>'schema_engine_mismatches','charset_mismatches'=>'schema_charset_mismatches','missing_indexes'=>'schema_missing_indexes','external_tables'=>'schema_external_tables','external_columns'=>'schema_external_columns','external_indexes'=>'schema_external_indexes','blocking_issues'=>'schema_blocking_issues','warnings'=>'schema_warnings','informational'=>'schema_informational');
        $localized = array();
        foreach (($result['summary'] ?? array()) as $key=>$value) { $localized[$this->language->get($summaryMap[$key] ?? $key)] = $value; }
        $result['summary'] = $localized;
        $typeMap = array('missing_table'=>'schema_type_missing_table','missing_column'=>'schema_type_missing_column','type'=>'schema_type_type','engine'=>'schema_type_engine','charset'=>'schema_type_charset','missing_index'=>'schema_type_missing_index','external_table'=>'schema_type_external_table','external_column'=>'schema_type_external_column','external_index'=>'schema_type_external_index');
        foreach (($result['issues'] ?? array()) as &$issue) {
            $rawType = isset($issue['type']) ? (string)$issue['type'] : '';
            $isExternal = isset($issue['source']) && $issue['source'] === 'external';
            $meaningKey = $isExternal ? 'schema_meaning_external' : 'schema_meaning_' . $rawType;
            $actionKey = $isExternal ? 'schema_action_external' : 'schema_action_' . $rawType;
            $issue['meaning'] = $this->language->get($meaningKey);
            $issue['next_action'] = $this->language->get($actionKey);
            if (isset($typeMap[$rawType])) { $issue['type'] = $this->language->get($typeMap[$rawType]); }
            $message = isset($issue['message']) ? (string)$issue['message'] : '';
            if ($message === 'Missing table') $issue['message'] = $this->language->get('schema_msg_missing_table');
            elseif ($message === 'Missing column') $issue['message'] = $this->language->get('schema_msg_missing_column');
            elseif ($message === 'Missing index') $issue['message'] = $this->language->get('schema_msg_missing_index');
            elseif ($message === 'Missing unique index') $issue['message'] = $this->language->get('schema_msg_missing_unique_index');
            elseif (strpos($message,'Additional table ') === 0) $issue['message'] = $this->language->get('schema_msg_external_table');
            elseif (strpos($message,'Additional column ') === 0) $issue['message'] = $this->language->get('schema_msg_external_column');
            elseif (strpos($message,'Additional index ') === 0) $issue['message'] = $this->language->get('schema_msg_external_index');
        }
        unset($issue);
        return $result;
    }

    private function comparisonRows() {
        return array(
            array('component'=>'PHP','opencart'=>'PHP 8.0–8.4','ocstore'=>'PHP 8.0–8.5','codecart'=>'PHP 8.1–8.5','status'=>'updated'),
            array('component'=>'Guzzle','opencart'=>'7.10.0','ocstore'=>'7.9.3','codecart'=>'7.15.5','status'=>'updated'),
            array('component'=>'Twig','opencart'=>'3.24.0','ocstore'=>'3.21.1','codecart'=>'3.28.0','status'=>'updated'),
            array('component'=>'Guzzle Promises','opencart'=>'2.3.0','ocstore'=>'2.2.0','codecart'=>'2.5.3','status'=>'updated'),
            array('component'=>'Guzzle PSR-7','opencart'=>'2.9.0','ocstore'=>'2.7.1','codecart'=>'2.13.1','status'=>'updated'),
            array('component'=>'League URI','opencart'=>'7.8.1','ocstore'=>'7.5.1','codecart'=>'7.8.1','status'=>'updated'),
            array('component'=>'League URI Interfaces','opencart'=>'7.8.1','ocstore'=>'7.5.0','codecart'=>'7.8.1','status'=>'updated'),
            array('component'=>'Symfony Deprecation Contracts','opencart'=>'3.6.0','ocstore'=>'3.6.0','codecart'=>'3.7.1','status'=>'updated'),
            array('component'=>'Symfony Polyfill Ctype','opencart'=>'1.37.0','ocstore'=>'1.32.0','codecart'=>'1.37.0','status'=>'updated'),
            array('component'=>'Symfony Polyfill PHP 8.0','opencart'=>'1.37.0','ocstore'=>'1.32.0','codecart'=>'1.37.0','status'=>'updated'),
            array('component'=>'scssphp Source Span','opencart'=>'1.1.0','ocstore'=>'1.0.0','codecart'=>'1.1.0','status'=>'updated'),
            array('component'=>'scssphp','opencart'=>'2.1.0 runtime','ocstore'=>'2.0.1 runtime','codecart'=>'2.1.0 manual / CLI only','status'=>'updated'),
            array('component'=>'Symfony Filesystem','opencart'=>'6.4.34','ocstore'=>'6.4.13','codecart'=>'6.4.45','status'=>'updated'),
            array('component'=>'Symfony Polyfill Mbstring','opencart'=>'1.37.0','ocstore'=>'1.32.0','codecart'=>'1.38.2','status'=>'updated'),
            array('component'=>'jQuery','opencart'=>'3.7.1','ocstore'=>'3.7.1','codecart'=>'3.7.1 Legacy Core','status'=>'legacy'),
            array('component'=>'Bootstrap','opencart'=>'3.4.1','ocstore'=>'3.4.1','codecart'=>'3.4.1 Legacy Core','status'=>'legacy'),
            array('component'=>'Font Awesome','opencart'=>'4.7.0','ocstore'=>'4.7.0','codecart'=>'6.7.2 + AUTO/Core/Full + FA4 compatibility','status'=>'updated'),
            array('component'=>'Content editor','opencart'=>'Summernote 0.8.18','ocstore'=>'Summernote 0.8.18','codecart'=>'Summernote 0.9.1 local','status'=>'updated'),
            array('component'=>'CodeMirror','opencart'=>'5.15.3','ocstore'=>'5.15.3','codecart'=>'5.65.21','status'=>'updated'),
            array('component'=>'Magnific Popup','opencart'=>'1.1.0','ocstore'=>'1.1.0','codecart'=>'1.2.0','status'=>'updated'),
            array('component'=>'PhotoSwipe','opencart'=>'—','ocstore'=>'—','codecart'=>'5.4.4','status'=>'added'),
            array('component'=>'HTML Purifier','opencart'=>'—','ocstore'=>'4.18.0','codecart'=>'4.19.0','status'=>'updated'),
            array('component'=>'Moment.js','opencart'=>'2.18.1','ocstore'=>'2.18.1','codecart'=>'2.30.1','status'=>'updated'),
            array('component'=>'DateTimePicker','opencart'=>'Bootstrap DateTimePicker 3.1.3.1','ocstore'=>'Bootstrap DateTimePicker 3.1.3.1','codecart'=>'CodeCart PRO Hybrid DateTimePicker 1.8.7','status'=>'updated'),
            array('component'=>'SortableJS','opencart'=>'—','ocstore'=>'1.10.2','codecart'=>'1.15.7','status'=>'updated'),
            array('component'=>'Swiper','opencart'=>'3.4.2','ocstore'=>'3.4.2','codecart'=>'CodeCart PRO Slider; Swiper 3 Legacy','status'=>'removed_runtime'),
            array('component'=>'Flot / jQVMap','opencart'=>'Core Dashboard','ocstore'=>'Core Dashboard','codecart'=>'not used by Core Dashboard; Legacy assets retained','status'=>'removed_runtime'),
            array('component'=>'SEO / clean URL','opencart'=>'SEO URL','ocstore'=>'SeoPro','codecart'=>'SeoPro + language prefixes + canonical/faceted policy','status'=>'updated'),
            array('component'=>'Image formats','opencart'=>'JPEG/PNG/WebP','ocstore'=>'JPEG/PNG/WebP','codecart'=>'JPEG/PNG/WebP/AVIF + safe fallback','status'=>'updated'),
            array('component'=>'Cache backends','opencart'=>'File','ocstore'=>'File','codecart'=>'File / APCu / Memcached / Redis + fallback','status'=>'added'),
            array('component'=>'Scheduler / Queue','opencart'=>'—','ocstore'=>'—','codecart'=>'Built-in','status'=>'added'),
            array('component'=>'System Notifications','opencart'=>'—','ocstore'=>'—','codecart'=>'Built-in','status'=>'added'),
            array('component'=>'GDPR / Cookie Consent','opencart'=>'—','ocstore'=>'—','codecart'=>'Built-in, opt-in on UPDATE','status'=>'added'),
            array('component'=>'MFA / Device Authorization','opencart'=>'—','ocstore'=>'—','codecart'=>'TOTP + trusted device layer','status'=>'added'),
            array('component'=>'Schema Registry / Diff','opencart'=>'—','ocstore'=>'—','codecart'=>'Read-only diagnostics + migrations','status'=>'added'),
            array('component'=>'Modern Extension Layer','opencart'=>'—','ocstore'=>'—','codecart'=>'PSR-4 / Services / Manifest / Extension Points','status'=>'added'),
            array('component'=>'Central Spam Service','opencart'=>'form-specific','ocstore'=>'form-specific','codecart'=>'contact / reviews / registration / forgotten / returns / GDPR','status'=>'added')
        );
    }

    private function runtimeInfo() {
        $required = array('mysqli/pdo_mysql'=>(extension_loaded('mysqli') || (extension_loaded('pdo') && extension_loaded('pdo_mysql'))),'gd'=>extension_loaded('gd'),'curl'=>extension_loaded('curl'),'mbstring'=>extension_loaded('mbstring'),'dom'=>extension_loaded('dom'),'xmlwriter'=>extension_loaded('xmlwriter'),'zip'=>extension_loaded('zip'),'simplexml'=>extension_loaded('simplexml') && function_exists('simplexml_load_string'),'openssl'=>extension_loaded('openssl'),'fileinfo'=>extension_loaded('fileinfo'));
        $optional = array('intl'=>extension_loaded('intl'),'apcu'=>extension_loaded('apcu'),'redis'=>extension_loaded('redis'),'memcached'=>extension_loaded('memcached'));
        $rows = array(
            array('component'=>'Version','value'=>'CodeCart PRO '.VERSION.(defined('CODECART_CHANNEL') && CODECART_CHANNEL ? ' '.CODECART_CHANNEL : ''),'state'=>'ok'),
            array('component'=>'PHP','value'=>PHP_VERSION.' / '.PHP_SAPI,'state'=>version_compare(PHP_VERSION,'8.1.0','>=') && version_compare(PHP_VERSION,'8.6.0','<')?'ok':'missing'),
            array('component'=>'DB','value'=>defined('DB_DRIVER')?DB_DRIVER:'','state'=>'ok')
        );
        try { $v=$this->db->query('SELECT VERSION() AS version'); if ($v->num_rows) $rows[]=array('component'=>'MySQL/MariaDB','value'=>(string)$v->row['version'],'state'=>'ok'); } catch (\Throwable $e) {}
        try { $cache=$this->registry->get('cache'); if (is_object($cache) && method_exists($cache,'getActiveEngine')) { $requested=method_exists($cache,'getRequestedEngine')?(string)$cache->getRequestedEngine():(string)$cache->getActiveEngine(); $active=(string)$cache->getActiveEngine(); $fallback=method_exists($cache,'getFallbackReason')?(string)$cache->getFallbackReason():''; $rows[]=array('component'=>'Cache backend','value'=>strtoupper($requested).($active!==$requested?' → '.strtoupper($active):''),'state'=>$active!==$requested?'warning':'ok','message'=>$fallback); } } catch (\Throwable $e) {}
        foreach ($required as $name=>$ok) $rows[]=array('component'=>$name,'value'=>$ok?'loaded':'missing','state'=>$ok?'ok':'missing');
        foreach ($optional as $name=>$ok) $rows[]=array('component'=>$name,'value'=>$ok?'loaded':'not loaded','state'=>$ok?'ok':'optional');
        if (extension_loaded('gd')) {
            $rows[]=array('component'=>'GD WebP','value'=>function_exists('imagewebp')?'supported':'missing','state'=>function_exists('imagewebp')?'ok':'missing');
            $rows[]=array('component'=>'GD AVIF','value'=>(function_exists('imageavif')&&function_exists('imagecreatefromavif'))?'supported':'not supported','state'=>(function_exists('imageavif')&&function_exists('imagecreatefromavif'))?'ok':'optional');
        }
        return $rows;
    }
    private function iconCatalog() {
        $file = DIR_SYSTEM . 'config/codecart_fontawesome_core_manifest.json';
        if (!is_file($file) || !is_readable($file)) { return array(); }
        $payload = json_decode((string)file_get_contents($file), true);
        if (!is_array($payload) || empty($payload['core_icons']) || !is_array($payload['core_icons'])) { return array(); }

        $styles = isset($payload['core_icon_styles']) && is_array($payload['core_icon_styles']) ? $payload['core_icon_styles'] : array();
        $previewNames = isset($payload['core_icon_preview_names']) && is_array($payload['core_icon_preview_names']) ? $payload['core_icon_preview_names'] : array();

        $icons = array();
        foreach ($payload['core_icons'] as $icon) {
            $icon = strtolower(trim((string)$icon));
            if ($icon !== '' && preg_match('/^[a-z0-9-]+$/', $icon)) { $icons[$icon] = true; }
        }
        // Saved extra icons are part of the effective minimal package. The picker
        // must show them immediately after saving instead of searching only the
        // immutable core manifest.
        $extraFile = DIR_SYSTEM . 'config/codecart_fontawesome_extras.json';
        $extraPayload = is_file($extraFile) && is_readable($extraFile) ? json_decode((string)file_get_contents($extraFile), true) : null;
        $savedExtras = preg_split('/[\s,;]+/', trim((string)$this->config->get('codecart_fontawesome_extra_icons')), -1, PREG_SPLIT_NO_EMPTY);
        $extraRows = array();
        if (is_array($extraPayload) && !empty($extraPayload['icons']) && is_array($extraPayload['icons'])) {
            foreach ($savedExtras as $extraName) {
                $extraName = strtolower(trim((string)$extraName));
                if (strpos($extraName, 'fa-') === 0) { $extraName = substr($extraName, 3); }
                if ($extraName === '' || !preg_match('/^[a-z0-9-]+$/', $extraName) || !isset($extraPayload['icons'][$extraName]) || isset($icons[$extraName])) { continue; }
                $row = $extraPayload['icons'][$extraName];
                $style = isset($row['default']) && in_array($row['default'], array('solid','regular','brands'), true) ? $row['default'] : 'solid';
                $extraRows[$extraName] = array('style'=>$style,'preview_name'=>$extraName);
                $icons[$extraName] = true;
            }
        }

        $icons = array_keys($icons);
        sort($icons, SORT_STRING);

        $definitions = $this->iconCategoryDefinitions();
        $lookup = array();
        foreach ($definitions as $category => $names) {
            foreach ($names as $name) { if (!isset($lookup[$name])) { $lookup[$name] = $category; } }
        }

        $rows = array();
        foreach ($icons as $icon) {
            $category = isset($lookup[$icon]) ? $lookup[$icon] : 'other';
            if (isset($extraRows[$icon])) {
                $style = $extraRows[$icon]['style'];
                $previewName = $extraRows[$icon]['preview_name'];
            } else {
                $style = isset($styles[$icon]) ? (string)$styles[$icon] : (($category === 'social') ? 'brands' : 'solid');
                if (!in_array($style, array('solid', 'regular', 'brands'), true)) { $style = 'solid'; }
                $previewName = isset($previewNames[$icon]) && preg_match('/^[a-z0-9-]+$/', (string)$previewNames[$icon]) ? (string)$previewNames[$icon] : $icon;
            }
            $previewClass = 'fa-' . $style . ' fa-' . $previewName;
            $rows[] = array(
                'name' => $icon,
                'class' => 'fa-' . $icon,
                'preview_name' => $previewName,
                'preview_class' => $previewClass,
                'category' => $category,
                'style' => $style
            );
        }
        return $rows;
    }

    private function iconFullCount() {
        $file = DIR_SYSTEM . 'config/codecart_fontawesome_full_catalog.json';
        if (!is_file($file)) { return 0; }
        $payload = json_decode((string)file_get_contents($file), true);
        return is_array($payload) && !empty($payload['icons']) && is_array($payload['icons']) ? count($payload['icons']) : 0;
    }

    private function iconFullCatalog() {
        $file = DIR_SYSTEM . 'config/codecart_fontawesome_full_catalog.json';
        if (!is_file($file)) { return array(); }
        $payload = json_decode((string)file_get_contents($file), true);
        if (!is_array($payload) || empty($payload['icons']) || !is_array($payload['icons'])) { return array(); }
        $result = array();
        foreach ($payload['icons'] as $row) {
            if (!is_array($row)) { continue; }
            $name = isset($row['name']) ? strtolower(trim((string)$row['name'])) : '';
            if ($name === '' || !preg_match('/^[a-z0-9-]+$/', $name)) { continue; }
            $styles = isset($row['styles']) && is_array($row['styles']) ? array_values(array_intersect($row['styles'], array('solid','regular','brands'))) : array();
            if (!$styles) { continue; }
            $style = isset($row['primary_style']) && in_array($row['primary_style'], $styles, true) ? (string)$row['primary_style'] : (in_array('brands', $styles, true) ? 'brands' : (in_array('solid', $styles, true) ? 'solid' : 'regular'));
            $search = array($name, isset($row['label']) ? (string)$row['label'] : $name);
            if (!empty($row['search']) && is_array($row['search'])) { $search = array_merge($search, $row['search']); }
            $result[] = array(
                'name' => $name,
                'label' => isset($row['label']) ? (string)$row['label'] : $name,
                'styles' => $styles,
                'style' => $style,
                'style_filter' => implode(' ', $styles),
                'class' => 'fa-' . $style . ' fa-' . $name,
                'search' => implode(' ', array_unique(array_map('strval', $search)))
            );
        }
        return $result;
    }

    private function iconCategoryDefinitions() {
        return array(
            'social' => array('x-twitter','tiktok','threads','viber','discord','facebook','facebook-f','facebook-square','twitter','twitter-square','instagram','youtube','youtube-play','youtube-square','linkedin','linkedin-square','pinterest','pinterest-p','pinterest-square','reddit','reddit-alien','reddit-square','whatsapp','telegram','vk','odnoklassniki','odnoklassniki-square','tumblr','tumblr-square','github','github-alt','github-square','gitlab','google-plus','google-plus-square','google-plus-official','flickr','dribbble','behance','behance-square','skype','snapchat','snapchat-ghost','snapchat-square','vimeo','vimeo-square','twitch'),
            'commerce' => array('shopping-cart','cart-plus','shopping-bag','shopping-basket','gift','tag','tags','barcode','qrcode','money','percent','calculator','balance-scale','archive','cube','cubes','ticket','certificate'),
            'payment' => array('credit-card','credit-card-alt','paypal','cc-paypal','cc-visa','cc-mastercard','cc-amex','cc-discover','cc-jcb','cc-diners-club','google-wallet','bank','university','money'),
            'shipping' => array('truck','plane','road','map-marker','map-pin','map-signs','map','map-o','location-arrow','compass','ship','bicycle','motorcycle','car','taxi','cube','cubes'),
            'contact' => array('phone','phone-square','mobile','envelope','envelope-o','envelope-open','envelope-open-o','comment','comment-o','comments','comments-o','address-book','address-book-o','address-card','address-card-o','user','users','user-circle','user-circle-o','whatsapp','telegram'),
            'interface' => array('home','search','bars','ellipsis-h','ellipsis-v','cog','cogs','check','check-circle','check-square','times','times-circle','plus','plus-circle','minus','minus-circle','edit','pencil','trash','trash-o','save','floppy-o','refresh','filter','sort','sort-asc','sort-desc','eye','eye-slash','bell','bell-o','calendar','calendar-o','calendar-check-o','clock-o','list','th','th-large','th-list','toggle-on','toggle-off','sliders','adjust','sun-o','moon-o','arrows','arrows-h','arrows-v','chevron-left','chevron-right','chevron-up','chevron-down'),
            'security' => array('shield','lock','unlock','unlock-alt','key','user-secret','exclamation-triangle','warning','exclamation-circle','ban','certificate','id-badge','id-card','id-card-o','fingerprint'),
            'media' => array('image','photo','picture-o','camera','camera-retro','video-camera','play','play-circle','play-circle-o','pause','pause-circle','stop','stop-circle','volume-up','volume-down','volume-off','microphone','microphone-slash','music','headphones','film'),
            'development' => array('code','code-fork','terminal','database','server','bug','sitemap','git','git-square','github','github-alt','gitlab','file-code-o','plug','cloud','cloud-upload','cloud-download','cubes')
        );
    }


    private function assetMinificationStats(): array {
        $stats = array('available' => 0, 'stale' => 0, 'active' => 0, 'catalog_available' => 0, 'admin_available' => 0);
        $scopes = array(
            array('root' => defined('DIR_CATALOG') ? rtrim(DIR_CATALOG, '/\\') . '/view' : '', 'enabled_css' => (bool)$this->config->get('codecart_storefront_minify_css_status'), 'enabled_js' => (bool)$this->config->get('codecart_storefront_minify_js_status'), 'key' => 'catalog_available'),
            array('root' => defined('DIR_APPLICATION') ? rtrim(DIR_APPLICATION, '/\\') . '/view' : '', 'enabled_css' => (bool)$this->config->get('codecart_admin_minify_css_status'), 'enabled_js' => (bool)$this->config->get('codecart_admin_minify_js_status'), 'key' => 'admin_available')
        );

        foreach ($scopes as $scope) {
            if (!$scope['root'] || !is_dir($scope['root'])) { continue; }
            try {
                $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($scope['root'], \FilesystemIterator::SKIP_DOTS));
                foreach ($it as $file) {
                    if (!$file->isFile()) { continue; }
                    $path = $file->getPathname();
                    if (!preg_match('/\.(css|js)$/i', $path, $m) || preg_match('/\.min\.(css|js)$/i', $path)) { continue; }
                    $ext = strtolower($m[1]);
                    $candidate = preg_replace('/\.' . $ext . '$/i', '.min.' . $ext, $path);
                    if (!$candidate || !is_file($candidate)) { continue; }
                    if (@filemtime($candidate) < @filemtime($path)) { $stats['stale']++; continue; }
                    $stats['available']++;
                    $stats[$scope['key']]++;
                    if (($ext === 'css' && $scope['enabled_css']) || ($ext === 'js' && $scope['enabled_js'])) { $stats['active']++; }
                }
            } catch (\Throwable $e) {
                // Diagnostics are informational only; never break the admin page.
            }
        }
        return $stats;
    }

}
