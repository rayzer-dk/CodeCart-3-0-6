<?php
class ControllerCronStockNotify extends Controller {
    public function index($args = array()) {
        $limit = isset($args['limit']) ? max(1,min(100,(int)$args['limit'])) : 50;
        // Recover rows claimed by a worker that terminated before completion.
        $this->db->query("UPDATE `" . DB_PREFIX . "stock_notify` SET status='waiting', date_modified=NOW() WHERE status='processing' AND date_modified < DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND attempts < 5");
        $this->db->query("UPDATE `" . DB_PREFIX . "stock_notify` SET status='failed', date_modified=NOW() WHERE status='processing' AND date_modified < DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND attempts >= 5");
        $q=$this->db->query("SELECT sn.*, p.image, p.price, pd.name, l.code AS language_code FROM `".DB_PREFIX."stock_notify` sn JOIN `".DB_PREFIX."product` p ON p.product_id=sn.product_id JOIN `".DB_PREFIX."product_description` pd ON pd.product_id=sn.product_id AND pd.language_id=sn.language_id LEFT JOIN `".DB_PREFIX."language` l ON l.language_id=sn.language_id WHERE sn.status='waiting' AND p.status='1' ORDER BY sn.stock_notify_id ASC LIMIT ".(int)$limit);
        $sent=0;$failed=0; $this->db->query("DELETE FROM `".DB_PREFIX."stock_notify` WHERE status IN ('sent','failed') AND date_modified < DATE_SUB(NOW(), INTERVAL 180 DAY)");
        foreach($q->rows as $row){
            $eligible=(int)$row['quantity']>0;
            $optionIds=array();
            if(!empty($row['option_data'])){$decoded=json_decode((string)$row['option_data'],true);if(is_array($decoded)){$optionIds=array_values(array_unique(array_filter(array_map('intval',$decoded))));}}
            if($eligible && $optionIds){$oq=$this->db->query("SELECT COUNT(*) AS total, SUM(CASE WHEN subtract='0' OR quantity>0 THEN 1 ELSE 0 END) AS ready FROM `".DB_PREFIX."product_option_value` WHERE product_option_value_id IN (".implode(',',$optionIds).")");$eligible=$oq->num_rows && (int)$oq->row['total']===count($optionIds) && (int)$oq->row['ready']===count($optionIds);}
            if(!$eligible){continue;}
            $this->db->query("UPDATE `".DB_PREFIX."stock_notify` SET status='processing', attempts=attempts+1, date_modified=NOW() WHERE stock_notify_id='".(int)$row['stock_notify_id']."' AND status='waiting'");
            if(!$this->db->countAffected()){continue;}
            $sq=$this->db->query("SELECT `key`,`value` FROM `".DB_PREFIX."setting` WHERE store_id='".(int)$row['store_id']."' AND `key` IN ('config_url','config_ssl','config_name','config_email','config_mail_engine','config_mail_parameter','config_mail_smtp_hostname','config_mail_smtp_username','config_mail_smtp_password','config_mail_smtp_port','config_mail_smtp_timeout')");
            $sc=array(); foreach($sq->rows as $sr){$sc[$sr['key']]=$sr['value'];}
            $base=!empty($sc['config_ssl'])?rtrim((string)$sc['config_ssl'],'/'):(!empty($sc['config_url'])?rtrim((string)$sc['config_url'],'/'):(defined('HTTP_SERVER')?rtrim(HTTP_SERVER,'/'):rtrim(HTTP_CATALOG,'/')));
            $url=$base.'/index.php?route=product/product&product_id='.(int)$row['product_id'];
            $lc=strtolower((string)$row['language_code']);
            if(strpos($lc,'uk')===0){
                $notifyLanguage = new Language((string)$row['language_code']);
                $notifyLanguage->load('cron/stock_notify');
                $subjectPrefix = $notifyLanguage->get('text_subject_prefix');
                $lead = $notifyLanguage->get('text_lead');
                $button = $notifyLanguage->get('text_button');
            } elseif(strpos($lc,'en')===0){$subjectPrefix='Back in stock: ';$lead='The product is available to order again.';$button='View product';}
            else{$subjectPrefix='Товар снова в наличии: ';$lead='Товар снова доступен для заказа.';$button='Перейти к товару';}
            $subject=$subjectPrefix.html_entity_decode((string)$row['name'],ENT_QUOTES,'UTF-8');
            $image=''; if(!empty($row['image'])){$image=$base.'/image/'.ltrim((string)$row['image'],'/');}
            $html='<p>'.htmlspecialchars($lead,ENT_QUOTES,'UTF-8').'</p><h2>'.htmlspecialchars((string)$row['name'],ENT_QUOTES,'UTF-8').'</h2>'.(!empty($row['option_label'])?'<p><strong>'.htmlspecialchars((string)$row['option_label'],ENT_QUOTES,'UTF-8').'</strong></p>':'').($image?'<p><img src="'.htmlspecialchars($image,ENT_QUOTES,'UTF-8').'" alt="" style="max-width:240px;height:auto"></p>':'').'<p><a href="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($button,ENT_QUOTES,'UTF-8').'</a></p>';
            $mail=new Mail(isset($sc['config_mail_engine'])?$sc['config_mail_engine']:$this->config->get('config_mail_engine')); $mail->parameter=isset($sc['config_mail_parameter'])?$sc['config_mail_parameter']:$this->config->get('config_mail_parameter'); $mail->smtp_hostname=isset($sc['config_mail_smtp_hostname'])?$sc['config_mail_smtp_hostname']:$this->config->get('config_mail_smtp_hostname'); $mail->smtp_username=isset($sc['config_mail_smtp_username'])?$sc['config_mail_smtp_username']:$this->config->get('config_mail_smtp_username'); $mail->smtp_password=html_entity_decode(isset($sc['config_mail_smtp_password'])?$sc['config_mail_smtp_password']:$this->config->get('config_mail_smtp_password'),ENT_QUOTES,'UTF-8'); $mail->smtp_port=isset($sc['config_mail_smtp_port'])?$sc['config_mail_smtp_port']:$this->config->get('config_mail_smtp_port'); $mail->smtp_timeout=isset($sc['config_mail_smtp_timeout'])?$sc['config_mail_smtp_timeout']:$this->config->get('config_mail_smtp_timeout'); $mail->setTo($row['email']); $mail->setFrom(isset($sc['config_email'])?$sc['config_email']:$this->config->get('config_email')); $mail->setSender(html_entity_decode(isset($sc['config_name'])?$sc['config_name']:$this->config->get('config_name'),ENT_QUOTES,'UTF-8')); $mail->setSubject($subject); $mail->setHtml($html); $mail->setText(strip_tags(str_replace(array('<br>','<br/>','<br />'),"\n",$html)));
            try { $ok=\CodeCart\Core\MailDelivery::send($this->registry,$mail,'stock-notify:'.(int)$row['stock_notify_id']); } catch (\Throwable $e) { $ok=false; if ($this->log) { $this->log->write('Stock notify #' . (int)$row['stock_notify_id'] . ': ' . $e->getMessage()); } } if($ok){$this->db->query("UPDATE `".DB_PREFIX."stock_notify` SET status='sent', date_sent=NOW(), date_modified=NOW() WHERE stock_notify_id='".(int)$row['stock_notify_id']."'");$sent++;}else{$this->db->query("UPDATE `".DB_PREFIX."stock_notify` SET status=IF(attempts>=5,'failed','waiting'), date_modified=NOW() WHERE stock_notify_id='".(int)$row['stock_notify_id']."'");$failed++;}
        }
        return array('success'=>true,'message'=>'Stock notifications: sent '.$sent.', failed '.$failed,'sent'=>$sent,'failed'=>$failed);
    }
}
