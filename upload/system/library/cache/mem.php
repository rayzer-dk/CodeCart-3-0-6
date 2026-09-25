<?php
namespace Cache;
class Mem {
	private $expire;
	private $memcache;
	
	const CACHEDUMP_LIMIT = 9999;

	public function __construct($expire) {
		if (!extension_loaded('memcache') || !class_exists('\\Memcache', false)) {
			throw new \RuntimeException('Memcache cache selected, but the PHP Memcache extension is not loaded.');
		}

		$this->expire = max(1, (int)$expire);
		$host = defined('CACHE_MEMCACHE_HOSTNAME') ? CACHE_MEMCACHE_HOSTNAME : (defined('CACHE_HOSTNAME') ? CACHE_HOSTNAME : '127.0.0.1');
		$port = defined('CACHE_MEMCACHE_PORT') ? (int)CACHE_MEMCACHE_PORT : (defined('CACHE_PORT') ? (int)CACHE_PORT : 11211);

		$this->memcache = new \Memcache();
		if (!$this->memcache->pconnect((string)$host, $port, 2)) {
			throw new \RuntimeException('Memcache cache is unavailable.');
		}
	}

	public function get($key) {
		return $this->memcache->get(CACHE_PREFIX . $key);
	}

	public function set($key, $value) {
		return $this->memcache->set(CACHE_PREFIX . $key, $value, MEMCACHE_COMPRESSED, $this->expire);
	}

	public function delete($key) {
		$clean = preg_replace('/[^A-Z0-9\._-]/i', '', (string)$key);
		$prefix = CACHE_PREFIX . ($key === '*' ? '' : $clean);
		$deleted = false;

		// Memcache has no native prefix delete. Enumerate slab keys and remove only
		// keys belonging to this installation; never flush a shared Memcache server.
		$items = $this->memcache->getExtendedStats('items');
		if (is_array($items)) {
			foreach ($items as $server => $stats) {
				if (!is_array($stats) || empty($stats['items']) || !is_array($stats['items'])) {
					continue;
				}

				foreach (array_keys($stats['items']) as $slab_id) {
					$dump = $this->memcache->getExtendedStats('cachedump', (int)$slab_id, self::CACHEDUMP_LIMIT);
					if (!is_array($dump)) {
						continue;
					}

					foreach ($dump as $entries) {
						if (!is_array($entries)) {
							continue;
						}

						foreach (array_keys($entries) as $cache_key) {
							if (strpos((string)$cache_key, $prefix) === 0) {
								$this->memcache->delete((string)$cache_key);
								$deleted = true;
							}
						}
					}
				}
			}
		}

		// Cachedump may be disabled by the server. Exact-key invalidation must
		// still work, while wildcard/prefix invalidation fails safely rather than
		// flushing unrelated applications.
		if (!$deleted && $key !== '*') {
			return $this->memcache->delete(CACHE_PREFIX . $clean);
		}

		return $deleted;
	}
}
