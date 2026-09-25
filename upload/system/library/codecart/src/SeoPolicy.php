<?php
namespace CodeCart\Core;

final class SeoPolicy {
    private $config;
    public function __construct($registry) { $this->config=$registry->get('config'); }

    public function category(int $categoryId, array $query): array {
        $filter=$this->normalizeFilter(isset($query['filter'])?(string)$query['filter']:'');
        $page=max(1,(int)($query['page']??1));
        $presentation=isset($query['sort']) || isset($query['order']) || isset($query['limit']);
        $mode=(string)$this->config->get('config_seo_filter_index_mode');
        if (!in_array($mode,array('noindex','allowlist','legacy'),true)) $mode='noindex';
        $filterIndexable=false;
        if ($filter!=='' && $mode==='allowlist') $filterIndexable=$this->allowlisted($categoryId,$filter);
        if ($mode==='legacy') return array('managed'=>false,'noindex'=>false,'canonical_filter'=>$filter,'canonical_page'=>$page);
        $noindex=false;
        if ($filter!=='' && !$filterIndexable) $noindex=true;
        if ($presentation && (int)$this->config->get('config_seo_presentation_noindex')!==0) $noindex=true;
        return array(
            'managed'=>true,
            'noindex'=>$noindex,
            'canonical_filter'=>($filterIndexable?$filter:''),
            'canonical_page'=>($filter!=='' && !$filterIndexable ? 1 : $page),
            'filter_indexable'=>$filterIndexable,
            'presentation'=>$presentation
        );
    }

    public function normalizeFilter(string $filter): string {
        $ids=array(); foreach(explode(',',$filter) as $id){$id=(int)$id;if($id>0)$ids[$id]=$id;}
        if(!$ids)return ''; sort($ids,SORT_NUMERIC); return implode(',',$ids);
    }

    public function trackingParams(): array {
        return array('gclid','dclid','fbclid','msclkid','yclid','_ga','mc_cid','mc_eid');
    }

    private function allowlisted(int $categoryId,string $filter): bool {
        if($categoryId<1||$filter==='')return false;
        $wanted=$categoryId.':'.$filter;
        $raw=(string)$this->config->get('config_seo_filter_allowlist');
        foreach(preg_split('/\\R/u',$raw) as $line){$line=trim((string)$line);if($line===''||strpos($line,':')===false)continue;list($cat,$ids)=array_map('trim',explode(':',$line,2));if((int)$cat!==$categoryId)continue;if($categoryId.':'.$this->normalizeFilter($ids)===$wanted)return true;}
        return false;
    }
}
