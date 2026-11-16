<?php
class ControllerStartupCodeCartAiReady extends Controller {
    public function index(): void {
        if (!$this->config->get('codecart_webmcp_status') || (int)$this->config->get('config_store_id') !== 0) {
            return;
        }

        $route = $this->request->get['route'] ?? 'common/home';
        if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'GET' || !in_array($route, array(
            'common/home', 'product/category', 'product/product', 'product/search',
            'product/manufacturer', 'product/manufacturer/info', 'product/special'
        ), true)) {
            return;
        }

        $secure = codecart_is_https((array)$this->request->server);
        $token = (string)$this->config->get('codecart_webmcp_origin_trial');
        if ($secure && preg_match('~\A[A-Za-z0-9+/_-]{1,2046}={0,2}\z~', $token)) {
            $this->response->addHeader('Origin-Trial: ' . $token);
        }

        $endpoint = str_replace('&amp;', '&', $this->url->link('extension/module/codecart_ai_ready', '', $secure));
        $this->document->addScript(
            'catalog/view/javascript/codecart/webmcp.js?endpoint=' . rawurlencode($endpoint) . '&v=' . CODECART_PACKAGE_BUILD,
            'footer'
        );
    }
}
