<?php
class ControllerCronHealth extends Controller {
    public function index($args=array()) {
        $notifications=new \CodeCart\Core\SystemNotification($this->registry);
        $preflight=new \CodeCart\Core\Preflight($this->registry);
        $active=array();
        foreach($preflight->rows() as $row){
            $code='health.'.preg_replace('/[^a-z0-9_.-]+/i','.',(string)$row['id']);
            if(in_array($row['state'],array('error','warning'),true)){$active[$code]=true;$severity=$row['state']==='error'?'critical':'warning';$notifications->notify($code,$severity,'System check: '.$row['component'],trim((string)$row['value'])."\n".trim((string)($row['message']??'')),'tool/codecart_core');}
            else{$notifications->resolve($code);}
        }
        return true;
    }
}
