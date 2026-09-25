<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Cache class
*/
class Cache {
	private $adaptor;
    private $requested = 'file';
    private $active = 'file';
    private $fallback_reason = '';
	
	/**
	 * Constructor
	 *
	 * @param	string	$adaptor	The type of storage for the cache.
	 * @param	int		$expire		Optional parameters
	 *
 	*/
	public function __construct($adaptor, $expire = 3600) {
        $requested = strtolower(trim((string)$adaptor));
        if ($requested === 'apc') { $requested = 'apcu'; }
        if (!in_array($requested, array('file','apcu','memcached','redis','mem'), true)) { $requested = 'file'; }
        $this->requested = $requested;
        $className = $requested === 'apcu' ? 'APCu' : ucfirst($requested);
        $class = 'Cache\\' . $className;
        try {
            if (!class_exists($class)) { throw new \RuntimeException('Cache adaptor is unavailable: ' . $requested); }
            $this->adaptor = new $class($expire);
            $this->active = $requested;
        } catch (\Throwable $e) {
            if ($requested === 'file') { throw $e; }
            $this->fallback_reason = $e->getMessage();
            $this->adaptor = new \Cache\File($expire);
            $this->active = 'file';
        }
	}
	
    /**
     * Gets a cache by key name.
     *
     * @param	string $key	The cache key name
     *
     * @return	string
     */
	public function get($key) {
		return $this->adaptor->get($key);
	}
	
    /**
     * 
     *
     * @param	string	$key	The cache key
	 * @param	string	$value	The cache value
	 * 
	 * @return	string
     */
	public function set($key, $value, $tags = array()) {
		if ($tags && method_exists($this->adaptor, 'setWithTags')) {
			return $this->adaptor->setWithTags($key, $value, (array)$tags);
		}
		return $this->adaptor->set($key, $value);
	}

	public function deleteTag($tag, $fallbackPrefix = '') {
		if (method_exists($this->adaptor, 'deleteTag')) {
			return $this->adaptor->deleteTag((string)$tag);
		}
		if ($fallbackPrefix !== '') {
			return $this->adaptor->delete((string)$fallbackPrefix);
		}
		return false;
	}
   
    /**
     * 
     *
     * @param	string	$key	The cache key
     */
	public function delete($key) {
		return $this->adaptor->delete($key);
	}
    public function getRequestedEngine() { return $this->requested; }
    public function getActiveEngine() { return $this->active; }
    public function getFallbackReason() { return $this->fallback_reason; }

}
