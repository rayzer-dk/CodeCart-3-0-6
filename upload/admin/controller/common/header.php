<?php
// * @source See SOURCE.txt for source and other copyright.
// * @license GNU General Public License version 3; see LICENSE.txt

class ControllerCommonHeader extends Controller {
    public function index() {
        $data['title'] = $this->document->getTitle();
        $data['base'] = $this->request->server['HTTPS'] ? HTTPS_SERVER : HTTP_SERVER;
        $server = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

        $data['has_store_favicon'] = false;
        $store_icon = trim((string)$this->config->get('config_icon'));
        if ($store_icon !== '' && is_file(DIR_IMAGE . $store_icon)) {
            $icon_path = str_replace('\\', '/', $store_icon);
            $icon_version = (int)@filemtime(DIR_IMAGE . $store_icon);
            $icon_url = $server . 'image/' . str_replace(' ', '%20', $icon_path);
            if ($icon_version > 0) {
                $icon_url .= (strpos($icon_url, '?') === false ? '?' : '&') . 'v=' . $icon_version;
            }
            $this->document->addLink($icon_url, 'icon');
            $data['has_store_favicon'] = true;
        }
        $data['package_build'] = defined('CODECART_PACKAGE_BUILD') ? (string)CODECART_PACKAGE_BUILD : '2.0.8';
        $data['current_route'] = isset($this->request->get['route']) ? (string)$this->request->get['route'] : '';

        $data['description'] = $this->document->getDescription();
        $data['keywords'] = $this->document->getKeywords();
        $data['links'] = $this->document->getLinks();
        $data['styles'] = $this->document->getStyles();
        $data['scripts'] = $this->document->getScripts();
        $data['modern_assets'] = method_exists($this->document, 'getAssets') ? $this->document->getAssets('header') : array();
        try {
            $assetOptimizer = new \CodeCart\Core\AdminAssetOptimizer($this->config);
            $data['styles'] = $assetOptimizer->styles($data['styles']);
            $data['scripts'] = $assetOptimizer->scripts($data['scripts']);
            $data['modern_assets'] = $assetOptimizer->assets($data['modern_assets']);
        } catch (\Throwable $e) {
            // Optimization is optional: admin must always fall back to original assets.
        }
        $data['lang'] = $this->language->get('code');
        $data['direction'] = $this->language->get('direction');
        $data['codecart_homepage'] = 'https://codecartpro.com/';
        $data['codecart_forum'] = \CodeCart\Core\Community::TELEGRAM;
        $data['codecart_documentation'] = rtrim($data['base'], '/') . '/view/documentation/CodeCart_PRO_Documentation.zip';

        $admin_colors = array(
            'admin_accent_color' => array('config_admin_accent_color', '#0b6fd3'),
            'admin_sidebar_color' => array('config_admin_sidebar_color', '#1f2937'),
            'admin_submenu_color' => array('config_admin_submenu_color', '#293141'),
            'admin_surface_color' => array('config_admin_surface_color', '#f5f7fa')
        );

        foreach ($admin_colors as $data_key => $color_setting) {
            $color = (string)$this->config->get($color_setting[0]);
            $data[$data_key] = preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : $color_setting[1];
        }

        $this->load->language('common/header');

        $data['text_logged'] = sprintf($this->language->get('text_logged'), $this->user->getUserName());
        $data['text_admin_theme'] = $this->language->get('text_admin_theme');
        $data['text_admin_theme_toggle'] = $this->language->get('text_admin_theme_toggle');
        $data['codecart_ui_i18n'] = array();
        if (strpos(strtolower((string)$data['lang']), 'uk') === 0) {
            foreach (array('ui_select_date','ui_delete','ui_close','ui_add','ui_remove','ui_edit','ui_view','ui_refresh','ui_upload','ui_download','ui_search','ui_copy','ui_filter','ui_save','ui_previous','ui_next','ui_collapse','ui_expand','ui_back','ui_continue','ui_select_image','ui_open','ui_action','ui_anchor_insert','ui_anchor_name','ui_toc_build','ui_toc_need_headings','ui_toc_title','ui_link_type','ui_link_help','ui_anchor_on_page','ui_anchor_choose','ui_anchor_help','ui_video_help') as $ui_key) {
                $data['codecart_ui_i18n'][$ui_key] = $this->language->get($ui_key);
            }
        }
        $data['text_refresh_modifications'] = $this->language->get('text_refresh_modifications');
        $data['ocmod_state'] = 'unknown';
        $data['ocmod_state_title'] = $data['text_refresh_modifications'];

        // Safe defaults keep the header renderable even when an optional post-login
        // component is temporarily unavailable during an existing-store UPDATE.
        $data['notification_count'] = 0;
        $data['system_notifications'] = array();
        $data['notifications_href'] = '';
        $data['firstname'] = '';
        $data['lastname'] = '';
        $data['username'] = '';
        $data['user_group'] = '';
        $data['image'] = '';
        $data['stores'] = array();
        $data['search'] = '';

        if (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
            $data['logged'] = '';
            $data['home'] = $this->url->link('common/login', '', true);
        } else {
            $data['logged'] = true;
            $data['home'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
            $data['logout'] = $this->url->link('common/logout', 'user_token=' . $this->session->data['user_token'], true);
            $data['profile'] = $this->url->link('common/profile', 'user_token=' . $this->session->data['user_token'], true);
            $data['modification_refresh'] = $this->url->link('marketplace/modification/refresh', 'user_token=' . $this->session->data['user_token'], true);
            try {
                $ocmodState = \CodeCart\Core\OcmodState::get();
                $status = isset($ocmodState['status']) ? (string)$ocmodState['status'] : 'unknown';
                if (in_array($status, array('clean','dirty','issues','unknown'), true)) {
                    $data['ocmod_state'] = $status;
                }
                if ($status === 'dirty') {
                    $data['ocmod_state_title'] = $this->language->get('text_refresh_modifications_pending');
                } elseif ($status === 'issues') {
                    $data['ocmod_state_title'] = $this->language->get('text_refresh_modifications_issues');
                } elseif ($status === 'clean') {
                    $data['ocmod_state_title'] = $this->language->get('text_refresh_modifications_clean');
                }
            } catch (\Throwable $e) {
                // Header remains available if advisory OCMOD state cannot be read.
            }
            $data['new_category'] = $this->url->link('catalog/category/add', 'user_token=' . $this->session->data['user_token'], true);
            $data['new_customer'] = $this->url->link('user/user/add', 'user_token=' . $this->session->data['user_token'], true);
            $data['new_download'] = $this->url->link('catalog/download/add', 'user_token=' . $this->session->data['user_token'], true);
            $data['new_manufacturer'] = $this->url->link('catalog/manufacturer/add', 'user_token=' . $this->session->data['user_token'], true);
            $data['new_product'] = $this->url->link('catalog/product/add', 'user_token=' . $this->session->data['user_token'], true);
            $data['notifications_href'] = $this->url->link('tool/notification', 'user_token=' . $this->session->data['user_token'], true);

            try {
                $notificationService = new \CodeCart\Core\SystemNotification($this->registry);
                $data['notification_count'] = $notificationService->unreadCount();
                $data['system_notifications'] = $notificationService->present($notificationService->latest(5));
            } catch (\Throwable $e) {
                $this->logHeaderFailure('notifications', $e);
            }

            try {
                $this->load->model('user/user');
                $this->load->model('tool/image');
                $user_info = $this->model_user_user->getUser($this->user->getId());

                if ($user_info) {
                    $data['firstname'] = isset($user_info['firstname']) ? $user_info['firstname'] : '';
                    $data['lastname'] = isset($user_info['lastname']) ? $user_info['lastname'] : '';
                    $data['username'] = isset($user_info['username']) ? $user_info['username'] : '';
                    $data['user_group'] = isset($user_info['user_group']) ? $user_info['user_group'] : '';
                    $image = isset($user_info['image']) ? (string)$user_info['image'] : '';

                    if ($image !== '' && is_file(DIR_IMAGE . $image)) {
                        $data['image'] = $this->model_tool_image->resize($image, 45, 45);
                    } elseif (is_file(DIR_IMAGE . 'catalog/profile-pic.webp')) {
                        $data['image'] = $this->model_tool_image->resize('catalog/profile-pic.webp', 45, 45);
                    }
                }
            } catch (\Throwable $e) {
                $this->logHeaderFailure('profile', $e);
            }

            try {
                $data['stores'][] = array('name' => $this->config->get('config_name'), 'href' => HTTP_CATALOG);
                $this->load->model('setting/store');
                $results = $this->model_setting_store->getStores();
                foreach ($results as $result) {
                    $data['stores'][] = array('name' => $result['name'], 'href' => $result['url']);
                }
            } catch (\Throwable $e) {
                $this->logHeaderFailure('stores', $e);
            }
        }

        try {
            $data['search'] = $this->load->controller('search/search');
        } catch (\Throwable $e) {
            $this->logHeaderFailure('search', $e);
            $data['search'] = '';
        }

        return $this->load->view('common/header', $data);
    }

    private function logHeaderFailure($component, \Throwable $e) {
        if ($this->registry->has('log')) {
            $this->log->write('Admin header ' . $component . ' failure: ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        }
    }
}
