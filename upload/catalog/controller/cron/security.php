<?php
class ControllerCronSecurity extends Controller {
    public function index($args = array()) {
        $result = array('login_attempts'=>0,'security_audit'=>0,'idempotency'=>0,'authorizations'=>0,'gdpr'=>0,'gdpr_retention'=>0,'queue_done'=>0,'notifications'=>0);
        try { $result['login_attempts']=(new \CodeCart\Core\LoginProtection($this->registry))->cleanup(7); } catch (\Throwable $e) { if($this->log){$this->log->write('Security cleanup login attempts: '.$e->getMessage());} }
        try { $days=(int)$this->config->get('codecart_security_audit_retention_days'); $result['security_audit']=(new \CodeCart\Core\SecurityAudit($this->registry))->cleanup($days>0?$days:90); } catch (\Throwable $e) { if($this->log){$this->log->write('Security cleanup audit: '.$e->getMessage());} }
        try { $days=(int)$this->config->get('codecart_gdpr_retention_days'); $result['gdpr_retention']=(new \CodeCart\Core\GdprManager($this->registry))->cleanup($days>0?$days:365); } catch (\Throwable $e) { if($this->log){$this->log->write('GDPR retention cleanup: '.$e->getMessage());} }
        try { $result['queue_done']=(new \CodeCart\Core\Queue($this->registry))->purgeCompleted(24); } catch (\Throwable $e) { if($this->log){$this->log->write('Queue retention cleanup: '.$e->getMessage());} }
        try { $result['notifications']=(new \CodeCart\Core\SystemNotification($this->registry))->cleanup(30); } catch (\Throwable $e) { if($this->log){$this->log->write('Notification retention cleanup: '.$e->getMessage());} }
        foreach(array('codecart_idempotency'=>"date_expire < NOW()",'codecart_user_authorize'=>"date_expire < NOW() AND status <> 'trusted'",'codecart_customer_authorize'=>"date_expire < NOW() AND status <> 'trusted'",'codecart_gdpr_request'=>"date_expire < NOW() AND status = 'pending'") as $table=>$where){
            try{$q=$this->db->query("SHOW TABLES LIKE '".$this->db->escape(DB_PREFIX.$table)."'");if($q->num_rows){$this->db->query("DELETE FROM `".DB_PREFIX.$table."` WHERE ".$where);$count=(int)$this->db->countAffected();if(strpos($table,'authorize')!==false){$result['authorizations']+=$count;}elseif($table==='codecart_gdpr_request'){$result['gdpr']+=$count;}else{$result['idempotency']+=$count;}}}catch(\Throwable $e){if($this->log){$this->log->write('Security cleanup '.$table.': '.$e->getMessage());}}
        }
        return $result;
    }
}
