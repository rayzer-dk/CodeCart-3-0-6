<?php
namespace Cache;

class Memcached {
    private $expire; private $memcached; private $generation=1;
    public function __construct($expire){
        if(!extension_loaded('memcached')||!class_exists('\\Memcached',false)){throw new \RuntimeException('Memcached cache selected, but the PHP Memcached extension is not loaded.');}
        $this->expire=max(1,(int)$expire);
        $host=defined('CACHE_MEMCACHED_HOSTNAME')?CACHE_MEMCACHED_HOSTNAME:(defined('CACHE_HOSTNAME')?CACHE_HOSTNAME:'127.0.0.1');
        $port=defined('CACHE_MEMCACHED_PORT')?(int)CACHE_MEMCACHED_PORT:(defined('CACHE_PORT')?(int)CACHE_PORT:11211);
        $this->memcached=new \Memcached();$this->memcached->setOption(\Memcached::OPT_CONNECT_TIMEOUT,1500);$this->memcached->setOption(\Memcached::OPT_RETRY_TIMEOUT,1);$this->memcached->addServer((string)$host,$port);
        $versions=$this->memcached->getVersion();if(!is_array($versions)||!$versions||!array_filter($versions)){throw new \RuntimeException('Memcached cache is unavailable.');}
        $meta=CACHE_PREFIX.'__ccp_generation';$gen=$this->memcached->get($meta);
        if($gen===false){$this->memcached->add($meta,1,0);$gen=$this->memcached->get($meta);}
        $this->generation=max(1,(int)$gen);
    }
    private function physical($key){return CACHE_PREFIX.'g'.$this->generation.':'.(string)$key;}
    public function get($key){return $this->memcached->get($this->physical($key));}
    public function set($key,$value){return $this->memcached->set($this->physical($key),$value,$this->expire);}
    public function delete($key){
        $key=(string)$key;
        if($key==='*'){
            $meta=CACHE_PREFIX.'__ccp_generation';$gen=$this->memcached->increment($meta,1,2,0);
            if($gen===false){$this->memcached->set($meta,2,0);$gen=2;}
            $this->generation=max(1,(int)$gen);return true;
        }
        $clean=preg_replace('/[^A-Z0-9\._-]/i','',$key);$prefix=$this->physical($clean);
        $keys=$this->memcached->getAllKeys();
        if(is_array($keys)){foreach($keys as $cacheKey){if(strpos((string)$cacheKey,$prefix)===0){$this->memcached->delete($cacheKey);}}return true;}
        return $this->memcached->delete($this->physical($clean));
    }
}
