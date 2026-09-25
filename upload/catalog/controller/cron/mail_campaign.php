<?php
class ControllerCronMailCampaign extends Controller {
    public function index($args = array()) {
        $limit = isset($args['limit']) ? max(1, min(100, (int)$args['limit'])) : 50;
        // Recover rows claimed by a worker that terminated before completion.
        $this->db->query("UPDATE `" . DB_PREFIX . "mail_campaign_queue` SET status='waiting', date_modified=NOW(), last_error=IF(last_error='', 'recovered_stale_processing', last_error) WHERE status='processing' AND date_modified < DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND attempts < 5");
        $this->db->query("UPDATE `" . DB_PREFIX . "mail_campaign_queue` SET status='failed', date_modified=NOW(), last_error=IF(last_error='', 'stale_processing_attempt_limit', last_error) WHERE status='processing' AND date_modified < DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND attempts >= 5");
        $q = $this->db->query("SELECT q.*, c.store_id, c.subject, c.message FROM `" . DB_PREFIX . "mail_campaign_queue` q INNER JOIN `" . DB_PREFIX . "mail_campaign` c ON (c.campaign_id=q.campaign_id) LEFT JOIN `" . DB_PREFIX . "mail_suppression` ms ON (LOWER(ms.email)=LOWER(q.email)) WHERE q.status='waiting' AND c.status IN ('queued','sending') ORDER BY q.mail_queue_id ASC LIMIT " . $limit);
        $sent=0; $failed=0; $suppressed=0; $campaigns=array();
        foreach ($q->rows as $row) {
            $campaign_id=(int)$row['campaign_id']; $campaigns[$campaign_id]=true;
            $supp = $this->db->query("SELECT email FROM `" . DB_PREFIX . "mail_suppression` WHERE email='" . $this->db->escape(utf8_strtolower((string)$row['email'])) . "' LIMIT 1");
            if ($supp->num_rows) {
                $this->db->query("UPDATE `" . DB_PREFIX . "mail_campaign_queue` SET status='suppressed', date_modified=NOW() WHERE mail_queue_id='" . (int)$row['mail_queue_id'] . "' AND status='waiting'");
                $suppressed++; continue;
            }
            $claimed = $this->db->query("SELECT mail_queue_id FROM `" . DB_PREFIX . "mail_campaign_queue` WHERE mail_queue_id='" . (int)$row['mail_queue_id'] . "' AND status='waiting' FOR UPDATE");
            if (!$claimed->num_rows) { continue; }
            $token = trim((string)$row['unsubscribe_token']);
            if ($token === '') { $token = bin2hex(random_bytes(32)); }
            $this->db->query("UPDATE `" . DB_PREFIX . "mail_campaign_queue` SET status='processing', attempts=attempts+1, unsubscribe_token='" . $this->db->escape($token) . "', date_modified=NOW() WHERE mail_queue_id='" . (int)$row['mail_queue_id'] . "' AND status='waiting'");
            if (!$this->db->countAffected()) { continue; }
            $this->db->query("UPDATE `" . DB_PREFIX . "mail_campaign` SET status='sending', date_started=COALESCE(date_started,NOW()), date_modified=NOW() WHERE campaign_id='" . $campaign_id . "'");

            $sq=$this->db->query("SELECT `key`,`value` FROM `".DB_PREFIX."setting` WHERE store_id='".(int)$row['store_id']."' AND `key` IN ('config_url','config_ssl','config_name','config_email','config_mail_engine','config_mail_parameter','config_mail_smtp_hostname','config_mail_smtp_username','config_mail_smtp_password','config_mail_smtp_port','config_mail_smtp_timeout')");
            $sc=array(); foreach($sq->rows as $sr){$sc[$sr['key']]=$sr['value'];}
            $base=!empty($sc['config_ssl'])?rtrim((string)$sc['config_ssl'],'/'):(!empty($sc['config_url'])?rtrim((string)$sc['config_url'],'/'):(defined('HTTP_CATALOG')?rtrim(HTTP_CATALOG,'/'):''));
            $store_name=isset($sc['config_name'])?(string)$sc['config_name']:(string)$this->config->get('config_name');
            $unsubscribe=$base.'/index.php?route=account/newsletter/unsubscribe&token='.rawurlencode($token);
            $plain=array('{{ customer_name }}'=>(string)$row['name'],'{{ store_name }}'=>$store_name,'{{ store_url }}'=>$base.'/','{{ unsubscribe_url }}'=>$unsubscribe);
            $html=array(); foreach($plain as $k=>$v){$html[$k]=htmlspecialchars($v,ENT_QUOTES,'UTF-8');}
            $subject=str_replace(array("\r","\n"),' ',strtr((string)$row['subject'],$plain));
            $body=strtr((string)$row['message'],$html);
            $message='<html><head><meta charset="UTF-8"><title>'.htmlspecialchars($subject,ENT_QUOTES,'UTF-8').'</title></head><body>'.$body.'</body></html>';
            $mail=new Mail(isset($sc['config_mail_engine'])?$sc['config_mail_engine']:$this->config->get('config_mail_engine'));
            $mail->parameter=isset($sc['config_mail_parameter'])?$sc['config_mail_parameter']:$this->config->get('config_mail_parameter');
            $mail->smtp_hostname=isset($sc['config_mail_smtp_hostname'])?$sc['config_mail_smtp_hostname']:$this->config->get('config_mail_smtp_hostname');
            $mail->smtp_username=isset($sc['config_mail_smtp_username'])?$sc['config_mail_smtp_username']:$this->config->get('config_mail_smtp_username');
            $mail->smtp_password=html_entity_decode(isset($sc['config_mail_smtp_password'])?$sc['config_mail_smtp_password']:$this->config->get('config_mail_smtp_password'),ENT_QUOTES,'UTF-8');
            $mail->smtp_port=isset($sc['config_mail_smtp_port'])?$sc['config_mail_smtp_port']:$this->config->get('config_mail_smtp_port');
            $mail->smtp_timeout=isset($sc['config_mail_smtp_timeout'])?$sc['config_mail_smtp_timeout']:$this->config->get('config_mail_smtp_timeout');
            $mail->setTo((string)$row['email']); $mail->setFrom(isset($sc['config_email'])?$sc['config_email']:$this->config->get('config_email')); $mail->setSender(html_entity_decode($store_name,ENT_QUOTES,'UTF-8')); $mail->setSubject($subject); $mail->setHtml($message); $mail->setText(trim(strip_tags(str_replace(array('<br>','<br/>','<br />'),"\n",$body)))."\n\n".$unsubscribe);
            try { $ok=\CodeCart\Core\MailDelivery::send($this->registry,$mail,'mail-campaign:'.(int)$row['mail_queue_id']); $err=''; }
            catch (\Throwable $e) { $ok=false; $err=substr($e->getMessage(),0,250); }
            if($ok){$this->db->query("UPDATE `".DB_PREFIX."mail_campaign_queue` SET status='sent', date_sent=NOW(), last_error='', date_modified=NOW() WHERE mail_queue_id='".(int)$row['mail_queue_id']."'");$sent++;}
            else{$attempt=(int)$row['attempts']+1;$status=$attempt>=5?'failed':'waiting';$this->db->query("UPDATE `".DB_PREFIX."mail_campaign_queue` SET status='".$status."', last_error='".$this->db->escape($err!==''?$err:'mail_send_failed')."', date_modified=NOW() WHERE mail_queue_id='".(int)$row['mail_queue_id']."'");$failed++;}
        }
        foreach(array_keys($campaigns) as $campaign_id){
            $counts=$this->db->query("SELECT status,COUNT(*) total FROM `".DB_PREFIX."mail_campaign_queue` WHERE campaign_id='".(int)$campaign_id."' GROUP BY status");$map=array();foreach($counts->rows as $r){$map[$r['status']]=(int)$r['total'];}
            $waiting=isset($map['waiting'])?$map['waiting']:0;$processing=isset($map['processing'])?$map['processing']:0;$s=isset($map['sent'])?$map['sent']:0;$f=isset($map['failed'])?$map['failed']:0;$sp=isset($map['suppressed'])?$map['suppressed']:0;
            $status=($waiting+$processing)>0?'sending':'completed';
            $this->db->query("UPDATE `".DB_PREFIX."mail_campaign` SET status='".$status."', sent='".$s."', failed='".$f."', suppressed='".$sp."', date_finished=".($status==='completed'?'NOW()':'date_finished').", date_modified=NOW() WHERE campaign_id='".(int)$campaign_id."'");
        }
        return array('success'=>true,'message'=>'Mail campaigns: sent '.$sent.', failed '.$failed.', suppressed '.$suppressed,'sent'=>$sent,'failed'=>$failed,'suppressed'=>$suppressed);
    }
}
