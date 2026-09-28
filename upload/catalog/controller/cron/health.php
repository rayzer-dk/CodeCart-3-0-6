<?php
class ControllerCronHealth extends Controller {
    public function index($args=array()) {
        $notifications=new \CodeCart\Core\SystemNotification($this->registry);
        $preflight=new \CodeCart\Core\Preflight($this->registry);
        $active=array();
        foreach($preflight->rows() as $row){
            $code='health.'.preg_replace('/[^a-z0-9_.-]+/i','.',(string)$row['id']);
            // This task is itself the cron heartbeat: while it runs, its own date_last is
            // not written yet, so "never" here would always be a false first-run warning.
            if((string)$row['id']==='cron.health'){$notifications->resolve($code);continue;}
            if(in_array($row['state'],array('error','warning'),true)){$active[$code]=true;$severity=$row['state']==='error'?'critical':'warning';$notifications->notify($code,$severity,'System check: '.$row['component'],trim((string)$row['value'])."\n".trim((string)($row['message']??'')),'tool/codecart_core');}
            else{$notifications->resolve($code);}
        }
        return true;
    }
}
