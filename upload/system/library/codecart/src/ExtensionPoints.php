<?php
namespace CodeCart\Core;
final class ExtensionPoints {
    private $handlers=array();
    private $diagnostics=array();
    public function register(string $point,string $owner,callable $handler,int $priority=100): void {
        if(!preg_match('/^[a-z][a-z0-9_.-]{2,96}$/',$point))throw new \InvalidArgumentException('Invalid extension point.');
        $owner=trim($owner); if($owner==='')throw new \InvalidArgumentException('Extension owner is required.');
        $this->handlers[$point][]=array('owner'=>$owner,'priority'=>$priority,'handler'=>$handler);
    }
    private function sorted(string $point): array {
        $items=$this->handlers[$point]??array();
        usort($items,function($a,$b){return $a['priority']===$b['priority']?strcmp($a['owner'],$b['owner']):($a['priority']<=>$b['priority']);});
        return $items;
    }
    public function dispatch(string $point,array $payload=array()): array {
        foreach($this->sorted($point) as $item){
            $started=microtime(true);
            try{$result=($item['handler'])($payload);if(is_array($result))$payload=$result;$this->diagnostics[]=array('point'=>$point,'owner'=>$item['owner'],'status'=>'ok','ms'=>(int)round((microtime(true)-$started)*1000));}
            catch(\Throwable $e){$this->diagnostics[]=array('point'=>$point,'owner'=>$item['owner'],'status'=>'error','message'=>$e->getMessage(),'ms'=>(int)round((microtime(true)-$started)*1000));throw $e;}
        }
        return $payload;
    }
    public function dispatchSafe(string $point,array $payload=array(),?callable $onError=null): array {
        foreach($this->sorted($point) as $item){
            $started=microtime(true);
            try{
                $result=($item['handler'])($payload);
                if(is_array($result))$payload=$result;
                $this->diagnostics[]=array('point'=>$point,'owner'=>$item['owner'],'status'=>'ok','ms'=>(int)round((microtime(true)-$started)*1000));
            }catch(\Throwable $e){
                $this->diagnostics[]=array('point'=>$point,'owner'=>$item['owner'],'status'=>'error','message'=>$e->getMessage(),'ms'=>(int)round((microtime(true)-$started)*1000));
                if($onError){try{$onError($e,$point,$item['owner']);}catch(\Throwable $ignored){/* Diagnostics must not turn a safe extension point into a fatal error. */}}
            }
        }
        return $payload;
    }
    public function owners(string $point): array {return array_values(array_unique(array_map(function($i){return $i['owner'];},$this->handlers[$point]??array())));}
    public function points(): array {return array_keys($this->handlers);}
    public function diagnostics(): array {return $this->diagnostics;}
}
