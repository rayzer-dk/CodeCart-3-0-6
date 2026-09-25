<?php
namespace CodeCart\Core;

final class AssetManager {
    private $document;
    public function __construct($registry) { $this->document=$registry->get('document'); }
    public function register(string $id,array $asset): void {
        if(!method_exists($this->document,'addAsset')){ $this->registerLegacy($asset); return; }
        $bridge = !isset($asset['legacy_bridge']) || $asset['legacy_bridge'] !== false;
        $advanced = !empty($asset['defer']) || !empty($asset['async']) || !empty($asset['module']) || !empty($asset['preload']) || !empty($asset['dependencies']);
        if ($bridge && !$advanced) {
            $asset['legacy_bridge'] = true;
            $this->document->addAsset($id,$asset);
            $this->registerLegacy($asset);
            return;
        }
        $asset['legacy_bridge'] = false;
        $this->document->addAsset($id,$asset);
    }
    private function registerLegacy(array $asset): void {
        $type=isset($asset['type'])?strtolower((string)$asset['type']):'';
        $position=(isset($asset['position'])&&$asset['position']==='footer')?'footer':'header';
        $version=isset($asset['version'])?preg_replace('/[^A-Za-z0-9._-]/','',(string)$asset['version']):'';
        if($type==='script' && !empty($asset['src'])){$url=(string)$asset['src'];if($version!==''){$url.=(strpos($url,'?')===false?'?':'&').'v='.rawurlencode($version);}$this->document->addScript($url,$position);}
        if($type==='style' && !empty($asset['href'])){$url=(string)$asset['href'];if($version!==''){$url.=(strpos($url,'?')===false?'?':'&').'v='.rawurlencode($version);}$this->document->addStyle($url,'stylesheet',isset($asset['media'])?(string)$asset['media']:'screen',$position);}
    }
    public function script(string $id,string $src,array $options=array()): void {$options['type']='script';$options['src']=$src;$this->register($id,$options);}
    public function style(string $id,string $href,array $options=array()): void {$options['type']='style';$options['href']=$href;$this->register($id,$options);}
}
