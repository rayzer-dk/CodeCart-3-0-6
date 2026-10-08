<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerStartupSeoUrl extends Controller {
	
	//seopro start
		private $seo_pro;
		public function __construct($registry) {
			parent::__construct($registry);	
			$this->seo_pro = new SeoPro($registry);
		}
	//seopro end
	
	public function index() {
		if ($this->config->get('codecart_lost_url_status')) {
			try {
				$target = (new \CodeCart\Core\LostUrlMonitor($this->registry))->redirect((array)$_SERVER);
				if ($target !== null) { $this->response->redirect($target, 301); return; }
			} catch (\Throwable $e) { $this->log->write('CodeCart lost URL redirect: ' . get_class($e)); }
		}

		// SEO aliases and language prefixes are independent features. The master
		// SEO URL switch must disable alias decoding/rewriting even when language
		// prefixes remain enabled.
		$seo_url_enabled = (bool)$this->config->get('config_seo_url');
		$seo_pro_enabled = $seo_url_enabled && (bool)$this->config->get('config_seo_pro');

		// Prefix-only mode still needs the URL rewrite hook so /en/index.php?...
		// can be generated without turning SEO aliases back on.
		if ($seo_url_enabled || $this->config->get('codecart_language_prefix_enabled')) {
			$this->url->addRewrite($this);
		}

		// If every active language has a folder, an unprefixed GET/HEAD is a
		// duplicate URL. Canonicalize it even when database SEO aliases are off.
		if ($this->config->get('codecart_language_prefix_missing_required') && $this->redirectMissingLanguagePrefix()) {
			return;
		}

		// Standard SEO URL mode uses one canonical policy too: content paths have no trailing slash.
		// Store root and language-prefix roots keep their slash. SeoPro retains its own configured policy.
		if ($seo_url_enabled && !$seo_pro_enabled && $this->redirectStandardTrailingSlash()) {
			return;
		}

	
		// Clean sitemap endpoints are system routes, not database SEO aliases.
		// Language prefixes have already been removed by startup/startup.php.
		if (isset($this->request->get['_route_'])) {
			$sitemap_route = trim((string)$this->request->get['_route_'], '/');

			// Some web-server/language-prefix combinations preserve the language folder
			// in _route_. Strip exactly one leading folder only for known system feed names.
			if (preg_match('#^[^/]+/((?:sitemap(?:\.xml|-.*\.xml)|google-merchant(?:-[^/]+)?\.xml))$#i', $sitemap_route, $system_route_match)) {
				$sitemap_route = $system_route_match[1];
			}

			if ($sitemap_route === 'sitemap.xml') {
				$this->request->get['route'] = 'extension/feed/google_sitemap';
				$this->request->get['_codecart_sitemap_clean'] = 1;
				unset($this->request->get['_route_']);
			} elseif ($sitemap_route === 'google-merchant.xml') {
				$this->request->get['route'] = 'extension/feed/google_base';
				unset($this->request->get['_route_']);
			} elseif (preg_match('/^google-merchant-([a-z0-9][a-z0-9-]{1,31})\.xml$/', $sitemap_route, $merchant_matches)) {
				$this->request->get['route'] = 'extension/feed/google_base';
				$this->request->get['lang'] = $merchant_matches[1];
				unset($this->request->get['_route_']);
			} elseif (preg_match('/^sitemap-(products|categories|manufacturers|information|blog-categories|blog-articles)-([a-z][a-z0-9-]{1,31})-([1-9][0-9]*)\.xml$/', $sitemap_route, $matches)) {
				$this->request->get['route'] = 'extension/feed/google_sitemap';
				$this->request->get['type'] = str_replace('-', '_', $matches[1]);
				$this->request->get['lang'] = $matches[2];
				$this->request->get['page'] = (int)$matches[3];
				$this->request->get['_codecart_sitemap_clean'] = 1;
				unset($this->request->get['_route_']);
			} elseif (preg_match('/^sitemap-(products|categories|manufacturers|information|blog-categories|blog-articles)-([1-9][0-9]*)\.xml$/', $sitemap_route, $matches)) {
				$this->request->get['route'] = 'extension/feed/google_sitemap';
				$this->request->get['type'] = str_replace('-', '_', $matches[1]);
				$this->request->get['page'] = (int)$matches[2];
				$this->request->get['_codecart_sitemap_clean'] = 1;
				unset($this->request->get['_route_']);
			}
		}

		// Decode catalog SEO aliases only while the master SEO URL switch is on.
		// In prefix-only mode startup/startup.php already consumes /<lang>/ and
		// optional index.php; any remaining pretty path is not a valid non-SEO URL.
		if ($seo_url_enabled && isset($this->request->get['_route_'])) {
			$parts = explode('/', $this->request->get['_route_']);
			
		//seopro prepare route
		if($seo_pro_enabled){		
			$parts = $this->seo_pro->prepareRoute($parts);
		}
		//seopro prepare route end

			// remove any empty arrays from trailing
			if (utf8_strlen(end($parts)) == 0) {
				array_pop($parts);
			}

			$seo_rows = array();
			$seo_keywords = array_values(array_unique(array_filter(array_map('strval', $parts), 'strlen')));
			if ($seo_keywords) {
				$quoted = array();
				foreach ($seo_keywords as $keyword) {
					$quoted[] = "'" . $this->db->escape($keyword) . "'";
				}
				$query = $this->db->query("SELECT seo_url_id, query, keyword FROM " . DB_PREFIX . "seo_url WHERE keyword IN (" . implode(',', $quoted) . ") AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY seo_url_id DESC");
				foreach ($query->rows as $row) {
					if (!isset($seo_rows[$row['keyword']])) {
						$seo_rows[$row['keyword']] = $row;
					}
				}
			}

			foreach ($parts as $part) {
				if (isset($seo_rows[$part])) {
					$url = explode('=', $seo_rows[$part]['query'], 2);

					if ($url[0] == 'product_id') {
						$this->request->get['product_id'] = $url[1];
					}

					if ($url[0] == 'category_id') {
						if (!isset($this->request->get['path'])) {
							$this->request->get['path'] = $url[1];
						} else {
							$this->request->get['path'] .= '_' . $url[1];
						}
					}

					if ($url[0] == 'manufacturer_id') {
						$this->request->get['manufacturer_id'] = $url[1];
					}

					if ($url[0] == 'information_id') {
						$this->request->get['information_id'] = $url[1];
					}

					if ($url[0] == 'article_id') {
						$this->request->get['article_id'] = $url[1];
					}

					if ($url[0] == 'blog_category_id') {
						if (!isset($this->request->get['blog_category_id'])) {
							$this->request->get['blog_category_id'] = $url[1];
						} else {
							$this->request->get['blog_category_id'] .= '_' . $url[1];
						}
					}

					if ($seo_rows[$part]['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id' && $url[0] != 'article_id' && $url[0] != 'blog_category_id') {
						$this->request->get['route'] = $seo_rows[$part]['query'];
					}
				} else {
					if(!$seo_pro_enabled){		
						$this->request->get['route'] = 'error/not_found';
					}

					break;
				}
			}

			if (!isset($this->request->get['route'])) {
				if (isset($this->request->get['product_id'])) {
					$this->request->get['route'] = 'product/product';
				} elseif (isset($this->request->get['path'])) {
					$this->request->get['route'] = 'product/category';
				} elseif (isset($this->request->get['manufacturer_id'])) {
					$this->request->get['route'] = 'product/manufacturer/info';
				} elseif (isset($this->request->get['information_id'])) {
					$this->request->get['route'] = 'information/information';
				} elseif (isset($this->request->get['article_id'])) {
					$this->request->get['route'] = 'blog/article';
				} elseif (isset($this->request->get['blog_category_id'])) {
					$this->request->get['route'] = 'blog/category';
				}
			}
		} elseif (!$seo_url_enabled && $this->config->get('codecart_language_prefix_enabled') && isset($this->request->get['_route_'])) {
			$this->request->get['route'] = 'error/not_found';
		}
		
		// SeoPro canonical validation is correct for catalog aliases, but clean
		// sitemap endpoints are system routes. Validating them as SEO aliases
		// turns valid child sitemap URLs into redirects/404s.
		if ($seo_pro_enabled && empty($this->request->get['_codecart_sitemap_clean'])) {
			$this->seo_pro->validate();
		}
		
	}


	private function redirectStandardTrailingSlash() {
		$method = isset($this->request->server['REQUEST_METHOD']) ? strtoupper((string)$this->request->server['REQUEST_METHOD']) : 'GET';
		if (!in_array($method, array('GET', 'HEAD'), true) || !empty($this->request->post)) { return false; }
		$requestUri = isset($this->request->server['REQUEST_URI']) ? (string)$this->request->server['REQUEST_URI'] : '';
		$uri = parse_url($requestUri);
		if (!is_array($uri) || empty($uri['path'])) { return false; }
		$path = preg_replace('#/+#', '/', (string)$uri['path']);
		if ($path === '/' || substr($path, -1) !== '/') { return false; }

		$isHttps = function_exists('codecart_is_https') ? codecart_is_https() : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');
		$base = (string)($isHttps ? $this->config->get('config_ssl') : $this->config->get('config_url'));
		$baseParts = parse_url($base);
		if (!is_array($baseParts) || empty($baseParts['host'])) { return false; }
		$basePath = isset($baseParts['path']) ? '/' . trim((string)$baseParts['path'], '/') : '/';
		if ($basePath !== '/') { $basePath .= '/'; }
		if ($path === $basePath) { return false; }

		// A language home such as /en/ is a root, not a content URL.
		$relative = $basePath === '/' ? trim($path, '/') : trim(substr($path, strlen($basePath)), '/');
		$prefix = strtolower(trim((string)$this->config->get('codecart_language_prefix_current')));
		if ($prefix !== '' && strtolower($relative) === $prefix) { return false; }

		$targetPath = rtrim($path, '/');
		$scheme = !empty($baseParts['scheme']) ? (string)$baseParts['scheme'] : ($isHttps ? 'https' : 'http');
		$target = $scheme . '://' . $baseParts['host'] . (!empty($baseParts['port']) ? ':' . (int)$baseParts['port'] : '') . $targetPath;
		if (isset($uri['query']) && $uri['query'] !== '') { $target .= '?' . $uri['query']; }
		$this->response->redirect($target, 301);
		return true;
	}

	private function redirectMissingLanguagePrefix() {
		$method = isset($this->request->server['REQUEST_METHOD']) ? strtoupper((string)$this->request->server['REQUEST_METHOD']) : 'GET';
		if (!in_array($method, array('GET', 'HEAD'), true) || !empty($this->request->post)) { return false; }
		$prefix = strtolower(trim((string)$this->config->get('codecart_language_prefix_current')));
		if ($prefix === '' || !preg_match('/^[a-z0-9][a-z0-9-]{0,31}$/', $prefix)) { return false; }
		$requestUri = isset($this->request->server['REQUEST_URI']) ? (string)$this->request->server['REQUEST_URI'] : '';
		if ($requestUri === '') { return false; }
		$isHttps = function_exists('codecart_is_https') ? codecart_is_https() : (!empty($this->request->server['HTTPS']) && strtolower((string)$this->request->server['HTTPS']) !== 'off');
		$base = (string)($isHttps ? $this->config->get('config_ssl') : $this->config->get('config_url'));
		$baseParts = parse_url($base);
		$uriParts = parse_url($requestUri);
		if (!is_array($baseParts) || empty($baseParts['host']) || !is_array($uriParts)) { return false; }
		$basePath = isset($baseParts['path']) ? '/' . trim((string)$baseParts['path'], '/') : '/';
		if ($basePath !== '/') { $basePath .= '/'; }
		$path = isset($uriParts['path']) ? (string)$uriParts['path'] : '/';
		if ($basePath !== '/' && strpos($path . '/', $basePath) !== 0) { return false; }
		$relative = $basePath === '/' ? ltrim($path, '/') : ltrim(substr($path, strlen($basePath)), '/');
		$targetPath = preg_replace('#/+#', '/', $basePath . rawurlencode($prefix) . '/' . $relative);
		$scheme = !empty($baseParts['scheme']) ? (string)$baseParts['scheme'] : ($isHttps ? 'https' : 'http');
		$target = $scheme . '://' . $baseParts['host'] . (!empty($baseParts['port']) ? ':' . (int)$baseParts['port'] : '') . $targetPath;
		if (isset($uriParts['query']) && $uriParts['query'] !== '') { $target .= '?' . $uriParts['query']; }
		$this->response->redirect($target, 301);
		return true;
	}

	public function rewrite($link) {
		$url_info = parse_url(str_replace('&amp;', '&', $link));
		$seo_url_enabled = (bool)$this->config->get('config_seo_url');
		$seo_pro_enabled = $seo_url_enabled && (bool)$this->config->get('config_seo_pro');

		if($seo_pro_enabled){		
		$url = null;
			} else {
		$url = '';
		}

		$data = array();

		parse_str(isset($url_info['query']) ? $url_info['query'] : '', $data);
		$route_for_prefix = isset($data['route']) ? (string)$data['route'] : '';

		// The storefront home page is a real root URL, not index.php?route=common/home.
		// Keep query parameters, but let the language-prefix layer select / or /<prefix>/.
		if ($route_for_prefix === 'common/home') {
			unset($data['route']);
			$query = '';
			if ($data) {
				$query = '?' . str_replace('&', '&amp;', http_build_query($data, '', '&'));
			}
			$home_path = str_replace('/index.php', '', $url_info['path']);
			$home = $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . rtrim($home_path, '/') . '/';
			return $this->applyLanguagePrefix($home . $query, $route_for_prefix);
		}

		// SEO URL disabled: preserve standard index.php?route=... links and only
		// add the selected language folder when native prefixes are enabled.
		if (!$seo_url_enabled) {
			return $this->applyLanguagePrefix($link, $route_for_prefix);
		}
		
		//seo_pro baseRewrite
		if($seo_pro_enabled){		
			list($url, $data, $postfix) =  $this->seo_pro->baseRewrite($data, (int)$this->config->get('config_language_id'));	
		}
		
		

		
		//seo_pro baseRewrite

		foreach ($data as $key => $value) {
			if (isset($data['route'])) {
				if (($data['route'] == 'product/product' && $key == 'product_id') || (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id') || ($data['route'] == 'blog/article' && $key == 'article_id') || ($data['route'] == 'blog/category' && $key == 'blog_category_id')) {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($key . '=' . (int)$value) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

					if ($query->num_rows && $query->row['keyword']) {
						$url .= '/' . $query->row['keyword'];

						unset($data[$key]);
					}
				} elseif ($key == 'path') {
					$categories = explode('_', $value);

					foreach ($categories as $category) {
						$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'category_id=" . (int)$category . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

						if ($query->num_rows && $query->row['keyword']) {
							$url .= '/' . $query->row['keyword'];
						} else {
							$url = '';

							break;
						}
					}

					unset($data[$key]);
				}
			}
		}

		//seo_pro add blank url
		unset($data['route']);

		$query = '';

		if ($data) {
			foreach ($data as $key => $value) {
				$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
			}

			if ($query) {
				$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
			}
		}
		
		if($seo_pro_enabled) {		
			$condition = ($url !== null);
		} else {
			$condition = $url;
		}

		if ($condition) {
			if($seo_pro_enabled){		
				if($this->config->get('config_page_postfix') && $postfix) {
					$url .= $this->config->get('config_page_postfix');
				} elseif($this->config->get('config_seopro_addslash') || !empty( $query)) {
					$url .= '/';
				} 
			}

			$rewritten = $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
			return $this->applyLanguagePrefix($rewritten, $route_for_prefix);
		} else {
			return $this->applyLanguagePrefix($link, $route_for_prefix);
		}
	}

	private function applyLanguagePrefix($url, $route) {
		if (!$this->config->get('codecart_language_prefix_enabled')) {
			return $url;
		}

		$route = (string)$route;
		$route_normalized = strtolower($route);
		if ($route_normalized === 'common/language/language' || strpos($route_normalized, 'extension/feed/') === 0 || strpos($route_normalized, 'api/') === 0) {
			return $url;
		}

		$prefix = strtolower(trim((string)$this->config->get('codecart_language_prefix_current')));
		if ($prefix === '' || !preg_match('/^[a-z0-9][a-z0-9-]{0,31}$/', $prefix)) {
			return $url;
		}

		$bases = array_filter(array_unique(array((string)$this->config->get('config_ssl'), (string)$this->config->get('config_url'))));
		usort($bases, function($a, $b) { return strlen($b) - strlen($a); });

		foreach ($bases as $base) {
			$base = rtrim($base, '/') . '/';
			if (strpos($url, $base) !== 0) {
				continue;
			}

			$rest = ltrim(substr($url, strlen($base)), '/');
			if ($rest === $prefix || strpos($rest, $prefix . '/') === 0) {
				return $url;
			}

			return $base . rawurlencode($prefix) . '/' . $rest;
		}

		return $url;
	}
}
