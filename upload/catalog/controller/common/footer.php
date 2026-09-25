<?php
class ControllerCommonFooter extends Controller {
	public function index() {
		$this->load->language('common/footer');

		$this->load->model('catalog/information');

		$data['informations'] = array();

		foreach ($this->model_catalog_information->getInformations() as $result) {
			if ($result['bottom']) {
				$data['informations'][] = array(
					'title' => $result['title'],
					'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
				);
			}
		}

		$data['contact'] = $this->url->link('information/contact');
		$data['return'] = $this->url->link('account/return/add', '', true);
		$data['sitemap'] = $this->url->link('information/sitemap');
		$data['tracking'] = $this->url->link('account/tracking', '', true);
		$data['manufacturer'] = $this->url->link('product/manufacturer');
		$data['voucher'] = $this->url->link('account/voucher', '', true);
		$data['affiliate'] = $this->url->link('affiliate/login', '', true);
		$data['special'] = $this->url->link('product/special');
		$data['account'] = $this->url->link('account/account', '', true);
		$data['order'] = $this->url->link('account/order', '', true);
		$data['wishlist'] = $this->url->link('account/wishlist', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);

		$data['telephone'] = trim((string)$this->config->get('config_telephone'));
		$data['email'] = trim((string)$this->config->get('config_email'));
		$data['address'] = nl2br(htmlspecialchars(trim((string)$this->config->get('config_address')), ENT_QUOTES, 'UTF-8'));

		$data['powered'] = sprintf($this->language->get('text_powered'), $this->config->get('config_name'), date('Y', time()));

		// Whos Online
		if ($this->config->get('config_customer_online')) {
			$this->load->model('tool/online');

			if (isset($this->request->server['REMOTE_ADDR'])) {
				$ip = $this->request->server['REMOTE_ADDR'];
			} else {
				$ip = '';
			}

			if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
				$is_https = function_exists('codecart_is_https') ? codecart_is_https() : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');
				$host = isset($this->request->server['HTTP_HOST']) ? (string)$this->request->server['HTTP_HOST'] : '';
				$request_uri = isset($this->request->server['REQUEST_URI']) ? (string)$this->request->server['REQUEST_URI'] : '/';
				$url = ($is_https ? 'https://' : 'http://') . $host . $request_uri;
			} else {
				$url = '';
			}

			if (isset($this->request->server['HTTP_REFERER'])) {
				$referer = $this->request->server['HTTP_REFERER'];
			} else {
				$referer = '';
			}

			$user_agent = isset($this->request->server['HTTP_USER_AGENT']) ? (string)$this->request->server['HTTP_USER_AGENT'] : '';
			$session_id = method_exists($this->session, 'getId') ? (string)$this->session->getId() : (session_id() ?: '');
			$visitor_key = hash('sha256', $session_id !== '' ? $session_id : ($ip . '|' . $user_agent));
			$this->model_tool_online->addOnline($visitor_key, $ip, $this->customer->getId(), $url, $referer, $user_agent);
		}

		$data['scripts'] = $this->document->getScripts('footer');
		$data['styles'] = $this->document->getStyles('footer');
		$data['modern_assets'] = method_exists($this->document, 'getAssets') ? $this->document->getAssets('footer') : array();
		try {
			$assetOptimizer = new \CodeCart\Core\StorefrontAssetOptimizer($this->config);
			$data['styles'] = $assetOptimizer->styles($data['styles']);
			$data['scripts'] = $assetOptimizer->scripts($data['scripts']);
			$data['modern_assets'] = $assetOptimizer->assets($data['modern_assets']);
		} catch (\Throwable $e) {
			// Optimization is optional: storefront must always fall back to original assets.
		}
		
		$output = $this->load->view('common/footer', $data);
		if ((int)$this->config->get('config_cookie_consent_status') && is_string($output) && stripos($output, 'id="ccp-consent"') === false) {
			$consent = $this->load->controller('common/cookie_consent');
			if (is_string($consent) && $consent !== '') {
				if (stripos($output, '</body>') !== false) {
					$output = preg_replace('/<\/body>/i', $consent . "\n</body>", $output, 1);
				} else {
					$output .= "\n" . $consent;
				}
			}
		}
		return $output;
	}
}
