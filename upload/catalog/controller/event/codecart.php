<?php
class ControllerEventCodeCart extends Controller {
	public function injectStructuredData(&$route, &$args, &$output) {
		if (!$this->config->get('config_codecart_structured_data_status') || !is_string($output) || stripos($output, '</head>') === false) {
			return;
		}

		$is_https = codecart_is_https((array)$this->request->server);
		$base = $is_https ? (string)$this->config->get('config_ssl') : (string)$this->config->get('config_url');
		$base = rtrim($base, '/') . '/';
		$organization_id = $base . '#organization';
		$website_id = $base . '#website';

		$organization = array(
			'@type' => 'OnlineStore',
			'@id' => $organization_id,
			'name' => (string)$this->config->get('config_name'),
			'url' => $base
		);

		$logo = (string)$this->config->get('config_logo');
		if ($logo !== '' && is_file(DIR_IMAGE . $logo)) {
			$organization['logo'] = $base . 'image/' . ltrim(str_replace('\\', '/', $logo), '/');
		}

        $telephone = trim((string)$this->config->get('config_telephone'));
        $email = trim((string)$this->config->get('config_email'));
        $address = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string)$this->config->get('config_address'), ENT_QUOTES, 'UTF-8'))));
        if ($telephone !== '') {
            $organization['telephone'] = $telephone;
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $organization['email'] = $email;
        }
        if ($address !== '') {
            $organization['address'] = $address;
        }
        if ($telephone !== '' || filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $organization['contactPoint'] = array('@type' => 'ContactPoint', 'contactType' => 'customer service');
            if ($telephone !== '') { $organization['contactPoint']['telephone'] = $telephone; }
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) { $organization['contactPoint']['email'] = $email; }
        }

        require_once(DIR_SYSTEM . 'library/codecart/merchant_schema.php');
        $identity = CodeCartMerchantSchema::identity($this->config);
        if ($identity) {
            $organization = array_merge($organization, $identity);
        }
        $shipping_page = (int)$this->config->get('config_codecart_schema_shipping_info_id');
        $shipping_url = $shipping_page > 0
            ? html_entity_decode($this->url->link('information/information', 'information_id=' . $shipping_page), ENT_QUOTES, 'UTF-8') : '';
        $shipping = CodeCartMerchantSchema::shipping($this->config, $base, $shipping_url);
        if ($shipping !== null) {
            $organization['hasShippingService'] = $shipping;
        }
        $return_page = (int)$this->config->get('config_codecart_schema_return_info_id');
        // Default store can reuse its existing return-terms page. Additional
        // stores must select their own page to avoid a cross-store 404 link.
        if ($return_page < 1 && (int)$this->config->get('config_store_id') === 0) {
            $return_page = (int)$this->config->get('config_return_id');
        }
        $return_url = $return_page > 0
            ? html_entity_decode($this->url->link('information/information', 'information_id=' . $return_page), ENT_QUOTES, 'UTF-8') : '';
        $returns = CodeCartMerchantSchema::returns($this->config, $return_url);
        if ($returns !== null) {
            $organization['hasMerchantReturnPolicy'] = $returns;
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

		$external_nodes = $this->getExistingSchemaNodes($output);
		if ($external_nodes) {
			$graph = array_values(array_filter($graph, function($node) use ($external_nodes) {
				return !$this->isDuplicateSchemaNode($node, $external_nodes);
			}));
		}
		if (!$graph) { return; }

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

    /**
     * Suppress identical nodes already present in the rendered header, including
     * JSON-LD produced by compatible themes or third-party extensions.
     * Other plugins may inject JSON-LD later in the body; the global switch
     * remains available for those cases.
     */
    private function getExistingSchemaNodes($html) {
        $nodes = array();
        if (!preg_match_all('~<script\b[^>]*\btype\s*=\s*(["\'])application/ld\+json\1[^>]*>(.*?)</script>~is', $html, $matches)) {
            return $nodes;
        }
        foreach ($matches[2] as $json) {
            $decoded = json_decode(trim($json), true);
            if (!is_array($decoded)) {
                continue;
            }
            $items = isset($decoded['@graph']) && is_array($decoded['@graph'])
                ? $decoded['@graph'] : (isset($decoded['@type']) ? array($decoded) : $decoded);
            foreach ((array)$items as $node) {
                if (is_array($node) && isset($node['@type'])) {
                    $nodes[] = $node;
                }
            }
        }
        return $nodes;
    }

    private function isDuplicateSchemaNode(array $candidate, array $existing) {
        $types = array_map('strtolower', (array)(isset($candidate['@type']) ? $candidate['@type'] : array()));
        $a = trim((string)(isset($candidate['@id']) ? $candidate['@id'] : ''));
        $url = trim((string)(isset($candidate['url']) ? $candidate['url'] : ''));
        foreach ($existing as $node) {
            $otherTypes = array_map('strtolower', (array)$node['@type']);
            $sameType = (bool)array_intersect($types, $otherTypes);
            if (!$sameType && (in_array('onlinestore', $types, true) || in_array('organization', $types, true))) {
                $sameType = (bool)array_intersect(array('organization', 'onlinestore', 'onlinebusiness'), $otherTypes);
            }
            if (!$sameType && (bool)array_intersect(array('article','blogposting','newsarticle'), $types)) {
                $sameType = (bool)array_intersect(array('article','blogposting','newsarticle'), $otherTypes);
            }
            if (!$sameType) {
                continue;
            }
            $b = trim((string)(isset($node['@id']) ? $node['@id'] : ''));
            $otherUrl = trim((string)(isset($node['url']) ? $node['url'] : ''));
            $sameId = $a !== '' && $b !== '' && $a === $b;
            // Do not suppress an existing node with a different @id:
            // other graph nodes may still refer to our stable #organization identifier.
            $sameUrl = $url !== '' && $otherUrl !== ''
                && rtrim($url, '/') === rtrim($otherUrl, '/')
                && ($a === '' || $b === '' || $a === $b);
            if ($sameId || $sameUrl) {
                return true;
            }
        }
        return false;
    }

}

