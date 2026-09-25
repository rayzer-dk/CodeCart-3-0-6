<?php
namespace Cache;

class Redis {
    private $expire;
    private $cache;
    private $generation = 1;

    public function __construct($expire) {
        if (!extension_loaded('redis') || !class_exists('\\Redis', false)) { throw new \RuntimeException('Redis cache selected, but the PHP Redis extension is not loaded.'); }
        $this->expire=max(1,(int)$expire);
        $host=defined('CACHE_REDIS_HOSTNAME')?CACHE_REDIS_HOSTNAME:(defined('CACHE_HOSTNAME')?CACHE_HOSTNAME:'127.0.0.1');
        $port=defined('CACHE_REDIS_PORT')?(int)CACHE_REDIS_PORT:(defined('CACHE_PORT')?(int)CACHE_PORT:6379);
        $timeout=defined('CACHE_REDIS_TIMEOUT')?(float)CACHE_REDIS_TIMEOUT:1.5;
        $password=defined('CACHE_REDIS_PASSWORD')?CACHE_REDIS_PASSWORD:(defined('CACHE_PASSWORD')?CACHE_PASSWORD:'');
        $this->cache=new \Redis();
        try {
            if(!$this->cache->pconnect((string)$host,$port,$timeout)){throw new \RuntimeException('Redis connection failed.');}
            if($password!==''&&!$this->cache->auth((string)$password)){throw new \RuntimeException('Redis authentication failed.');}
            $meta=CACHE_PREFIX.'__ccp_generation';
            $gen=$this->cache->get($meta);
            if($gen===false){$this->cache->set($meta,1);$gen=1;}
            $this->generation=max(1,(int)$gen);
        } catch(\Throwable $e){throw new \RuntimeException('Redis cache is unavailable.',0,$e);}
    }
    private function physical($key){return CACHE_PREFIX.'g'.$this->generation.':'.(string)$key;}
    public function get($key){$data=$this->cache->get($this->physical($key));if($data===false)return false;$value=json_decode($data,true);return json_last_error()===JSON_ERROR_NONE?$value:false;}
    public function set($key,$value){$data=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($data===false)return false;return(bool)$this->cache->setex($this->physical($key),$this->expire,$data);}
    private function tagKey($tag){return CACHE_PREFIX.'g'.$this->generation.':__tag:'.hash('sha256',(string)$tag);}
    public function setWithTags($key,$value,array $tags){
        $physical=$this->physical($key);
        $data=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if($data===false||!$this->cache->setex($physical,$this->expire,$data))return false;
        foreach(array_unique(array_filter(array_map('strval',$tags),'strlen')) as $tag){
            $tagKey=$this->tagKey($tag);
            $this->cache->sAdd($tagKey,$physical);
            $this->cache->expire($tagKey,$this->expire);
        }
        return true;
    }
    public function deleteTag($tag){
        $tagKey=$this->tagKey((string)$tag);
        $keys=$this->cache->sMembers($tagKey);
        if(is_array($keys)&&$keys){$this->cache->del($keys);}
        $this->cache->del($tagKey);
        return true;
    }
    public function delete($key){
        $key=(string)$key;
        if($key==='*'){
            $gen=(int)$this->cache->incr(CACHE_PREFIX.'__ccp_generation');
            if($gen<1){$gen=1;$this->cache->set(CACHE_PREFIX.'__ccp_generation',$gen);}
            $this->generation=$gen;
            return true;
        }
        $clean=preg_replace('/[^A-Z0-9\._-]/i','',$key);
        $prefix=$this->physical($clean);$iterator=null;
        do{$keys=$this->cache->scan($iterator,$prefix.'*',500);if(is_array($keys)&&$keys){$this->cache->del($keys);}}while($iterator!==0&&$iterator!==null);
        return true;
    }
}
