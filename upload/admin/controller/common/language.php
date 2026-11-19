<?php
/**
 * Language selection is an authenticated POST action. User permissions and storefront
 * settings are not modified; only the current administrator's preference is saved.
 */
class ControllerCommonLanguage extends Controller {
    public function index() {
        $token = isset($this->request->get['user_token']) && is_string($this->request->get['user_token']) ? $this->request->get['user_token'] : '';
        $sessionToken = isset($this->session->data['user_token']) ? (string)$this->session->data['user_token'] : '';

        if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST'
            || !$this->user->isLogged()
            || $this->registry->get('codecart_mfa_pending')
            || $token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            $this->response->addHeader('HTTP/1.1 403 Forbidden');
            $this->response->setOutput('Forbidden');
            return;
        }

        $code = isset($this->request->post['language']) && is_string($this->request->post['language'])
            ? strtolower(trim($this->request->post['language'])) : '';
        try {
            $available = \CodeCart\Core\AdminLanguagePreference::available($this->db);
            if (!isset($available[$code])) {
                throw new \InvalidArgumentException('Language is not available.');
            }
            \CodeCart\Core\AdminLanguagePreference::save($this->db, (int)$this->user->getId(), $code);
            if (in_array($code, array('uk-ua', 'ru-ru', 'en-gb'), true)) {
                $this->session->data['codecart_admin_error_language'] = $code;
                setcookie('codecart_admin_error_language', $code, time() + 2592000, '/', '', codecart_is_https(), true);
            }
        } catch (\Throwable $e) {
            $this->log->write('Admin language selection failed: ' . get_class($e));
            // A missing database migration must not masquerade as a successful save.
            $this->response->addHeader('HTTP/1.1 503 Service Unavailable');
            $this->response->setOutput('Language preference could not be saved.');
            return;
        }

        $fallback = html_entity_decode($this->url->link('common/dashboard', 'user_token=' . $sessionToken, true), ENT_QUOTES, 'UTF-8');
        $redirect = isset($this->request->post['redirect']) && is_string($this->request->post['redirect'])
            ? $this->request->post['redirect'] : '';
        $params = array();
        if ($redirect !== '' && codecart_is_safe_redirect($redirect, array(HTTP_SERVER, HTTPS_SERVER))) {
            $query = parse_url($redirect, PHP_URL_QUERY);
            if (is_string($query)) {
                parse_str($query, $params);
            }
        }
        if (!isset($params['user_token']) || !is_string($params['user_token'])
            || !hash_equals($sessionToken, $params['user_token'])) {
            $redirect = $fallback;
        }
        $this->response->redirect($redirect);
    }
}
