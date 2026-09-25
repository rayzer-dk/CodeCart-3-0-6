<?php
// * @source See SOURCE.txt for source and other copyright.
// * @license GNU General Public License version 3; see LICENSE.txt

class ControllerCommonLanguage extends Controller {
    public function index() {
        $this->load->language('common/language');

        $secure = function_exists('codecart_is_https') ? codecart_is_https() : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');
        $data['action'] = $this->url->link('common/language/language', '', $secure);
        $data['code'] = isset($this->session->data['language']) ? (string)$this->session->data['language'] : (string)$this->config->get('config_language');

        $this->load->model('localisation/language');
        $results = $this->model_localisation_language->getLanguages();
        $data['languages'] = array();

        foreach ($results as $result) {
            if (!empty($result['status'])) {
                $data['languages'][] = array('name' => $result['name'], 'code' => $result['code'], 'href' => '');
                if ((int)$result['language_id'] === (int)$this->config->get('config_language_id')) {
                    $data['code'] = $result['code'];
                }
            }
        }

        if (!isset($this->request->get['route'])) {
            $route = 'common/home';
            $url = '';
        } else {
            $url_data = $this->request->get;
            unset($url_data['_route_']);
            $route = isset($url_data['route']) ? (string)$url_data['route'] : 'common/home';
            unset($url_data['route']);
            $url = $url_data ? '&' . urldecode(http_build_query($url_data, '', '&')) : '';
        }

        if ($this->config->get('config_seo_url') || $this->config->get('codecart_language_prefix_enabled')) {
            $data['redirect'] = base64_encode(json_encode(array('route' => $route, 'url' => $url, 'protocol' => $secure)));
        } else {
            $data['redirect'] = $this->url->link($route, $url, $secure);
        }

        // In native prefix mode render real crawlable language links. This avoids
        // a POST -> redirect chain and lets /en/... itself select the language.
        // Legacy/no-prefix stores keep the standard POST selector as fallback.
        if ($this->config->get('codecart_language_prefix_enabled')) {
            $saved_language_id = (int)$this->config->get('config_language_id');
            $saved_prefix = (string)$this->config->get('codecart_language_prefix_current');

            foreach ($data['languages'] as &$language) {
                $language_code = (string)$language['code'];
                if (!isset($results[$language_code]) || empty($results[$language_code]['status'])) {
                    continue;
                }

                $this->config->set('config_language_id', (int)$results[$language_code]['language_id']);
                $this->config->set('codecart_language_prefix_current', isset($results[$language_code]['url_prefix']) ? strtolower(trim((string)$results[$language_code]['url_prefix'])) : '');

                if ($route === 'common/home') {
                    $base = $this->getCatalogBaseUrl($secure);
                    $prefix = (string)$this->config->get('codecart_language_prefix_current');
                    $language['href'] = $base . ($prefix !== '' ? rawurlencode($prefix) . '/' : '');
                } else {
                    $language['href'] = $this->url->link($route, $url, $secure);
                }
            }
            unset($language);

            $this->config->set('config_language_id', $saved_language_id);
            $this->config->set('codecart_language_prefix_current', $saved_prefix);
        }

        return $this->load->view('common/language', $data);
    }

    public function language() {
        $this->load->model('localisation/language');

        if ($this->config->get('config_seo_url') || $this->config->get('codecart_language_prefix_enabled')) {
            $this->seoLanguage();
            return;
        }

        if (isset($this->request->post['code'])) {
            $this->session->data['language'] = (string)$this->request->post['code'];
        }

        if (isset($this->request->post['redirect']) && codecart_is_safe_redirect($this->request->post['redirect'], array($this->config->get('config_url'), $this->config->get('config_ssl')))) {
            $this->response->redirect($this->request->post['redirect']);
        } else {
            $this->response->redirect($this->url->link('common/home'));
        }
    }

    private function seoLanguage() {
        $secure = function_exists('codecart_is_https') ? codecart_is_https() : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');
        $languages = $this->model_localisation_language->getLanguages();
        $selected = isset($this->request->post['code']) ? trim((string)$this->request->post['code']) : '';

        if ($selected === '' || !isset($languages[$selected]) || empty($languages[$selected]['status'])) {
            $selected = isset($this->session->data['language']) && isset($languages[$this->session->data['language']]) ? (string)$this->session->data['language'] : (string)$this->config->get('config_language');
        }

        if (isset($languages[$selected])) {
            $this->session->data['language'] = $selected;
            $this->config->set('config_language_id', (int)$languages[$selected]['language_id']);
            $prefix = isset($languages[$selected]['url_prefix']) ? strtolower(trim((string)$languages[$selected]['url_prefix'])) : '';
            $this->config->set('codecart_language_prefix_current', $prefix);
            $this->config->set('codecart_language_prefix_explicit', $this->config->get('codecart_language_prefix_enabled') ? 1 : 0);
            setcookie('language', $selected, time() + 2592000, '/', '', $secure, true);
        }

        $route = 'common/home';
        $url = '';
        if (isset($this->request->post['redirect'])) {
            $decoded = base64_decode((string)$this->request->post['redirect'], true);
            $redirect_data = $decoded !== false ? json_decode($decoded, true) : null;
            if (is_array($redirect_data) && isset($redirect_data['route']) && is_string($redirect_data['route']) && preg_match('/^[a-zA-Z0-9_\\/.-]+$/', $redirect_data['route'])) {
                $route = $redirect_data['route'];
                $url = isset($redirect_data['url']) && is_string($redirect_data['url']) ? $redirect_data['url'] : '';
            }
        }

        // Home is special: there is no SEO keyword to rewrite. Build the selected
        // language root directly so stale common/home seo_url rows cannot win.
        if ($route === 'common/home' && isset($languages[$selected])) {
            $base = $this->getCatalogBaseUrl($secure);
            $prefix = isset($languages[$selected]['url_prefix']) ? strtolower(trim((string)$languages[$selected]['url_prefix'])) : '';
            $this->response->redirect($base . ($prefix !== '' ? rawurlencode($prefix) . '/' : ''));
            return;
        }

        $this->response->redirect($this->url->link($route, $url, $secure));
    }

    private function getCatalogBaseUrl($secure) {
        $candidates = array(
            (string)($secure ? $this->config->get('config_ssl') : $this->config->get('config_url')),
            (string)$this->config->get('config_ssl'),
            (string)$this->config->get('config_url')
        );
        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            $parts = $candidate !== '' ? parse_url($candidate) : false;
            if (is_array($parts) && !empty($parts['host'])) {
                return rtrim($candidate, '/') . '/';
            }
        }

        $host = isset($this->request->server['HTTP_HOST']) ? preg_replace('/[^A-Za-z0-9.:-]/', '', (string)$this->request->server['HTTP_HOST']) : '';
        if ($host === '') {
            return '/';
        }
        $script = isset($this->request->server['SCRIPT_NAME']) ? str_replace('\\', '/', (string)$this->request->server['SCRIPT_NAME']) : '/index.php';
        $dir = rtrim(str_replace('\\', '/', dirname($script)), '/.');
        return ($secure ? 'https://' : 'http://') . $host . ($dir !== '' ? $dir . '/' : '/');
    }
}
