<?php
class ControllerEventCodeCart extends Controller {
	public function injectStructuredData(&$route, &$args, &$output) {
		if (!$this->config->get('config_codecart_structured_data_status') || !is_string($output) || stripos($output, '</head>') === false) {
			return;
		}

		$is_https = function_exists('codecart_is_https') ? codecart_is_https((array)$this->request->server) : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');
		$base = $is_https ? (string)$this->config->get('config_ssl') : (string)$this->config->get('config_url');
		$base = rtrim($base, '/') . '/';
		$organization_id = $base . '#organization';
		$website_id = $base . '#website';

		$organization = array(
			'@type' => 'Organization',
			'@id' => $organization_id,
			'name' => (string)$this->config->get('config_name'),
			'url' => $base
		);

		$logo = (string)$this->config->get('config_logo');
		if ($logo !== '' && is_file(DIR_IMAGE . $logo)) {
			$organization['logo'] = $base . 'image/' . ltrim(str_replace('\\', '/', $logo), '/');
		}

		$graph = array(
			$organization,
			array(
				'@type' => 'WebSite',
				'@id' => $website_id,
				'url' => $base,
				'name' => (string)$this->config->get('config_name'),
				'publisher' => array('@id' => $organization_id),
				'inLanguage' => (string)$this->language->get('code')
			)
		);

		foreach ((array)$this->document->getStructuredData() as $structured_data) {
			if (is_array($structured_data) && $structured_data) {
				$graph[] = $structured_data;
			}
		}

		$payload = array(
			'@context' => 'https://schema.org',
			'@graph' => $graph
		);

		$json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		if ($json === false) {
			return;
		}

		$script = '<script type="application/ld+json">' . $json . '</script>';
		$output = preg_replace('/<\\/head>/i', $script . "\n</head>", $output, 1);
	}
    public function injectCookieConsent(&$route, &$args, &$output) {
        if (!is_string($output) || stripos($output, 'id="ccp-consent"') !== false || !(int)$this->config->get('config_cookie_consent_status')) {
            return;
        }
        $consent = $this->load->controller('common/cookie_consent');
        if (is_string($consent) && $consent !== '') {
            if (stripos($output, '</body>') !== false) {
                $output = preg_replace('/<\/body>/i', $consent . "\n</body>", $output, 1);
            } else {
                $output .= "\n" . $consent;
            }
        }
    }
}

