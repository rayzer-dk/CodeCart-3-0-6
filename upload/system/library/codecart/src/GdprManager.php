<?php
namespace CodeCart\Core;

final class GdprManager {
    private $registry;
    private $db;
    private $request;
    private $log;

    public function __construct($registry) {
        $this->registry=$registry;
        $this->db=$registry->get('db');
        $this->request=$registry->get('request');
        $this->log=$registry->get('log');
    }

    public function createRequest(int $customerId,string $email,string $type): array {
        if ($customerId<1 || !filter_var($email,FILTER_VALIDATE_EMAIL) || !in_array($type,array('export','delete'),true)) throw new \InvalidArgumentException('Invalid GDPR request.');
        $raw=bin2hex(random_bytes(32));
        $hash=hash('sha256',$raw);
        $this->db->query("UPDATE `".DB_PREFIX."codecart_gdpr_request` SET status='superseded' WHERE customer_id='".(int)$customerId."' AND type='".$this->db->escape($type)."' AND status IN ('pending','confirmed')");
        $expire=date('Y-m-d H:i:s',time()+86400);
        $this->db->query("INSERT INTO `".DB_PREFIX."codecart_gdpr_request` SET customer_id='".(int)$customerId."', email='".$this->db->escape($email)."', type='".$this->db->escape($type)."', token='".$this->db->escape($hash)."', status='pending', date_added=NOW(), date_expire='".$this->db->escape($expire)."'");
        $id=(int)$this->db->getLastId();
        $this->audit($id,'customer',$customerId,'request_'.$type,'');
        return array('request_id'=>$id,'token'=>$raw,'type'=>$type);
    }

    public function confirm(string $rawToken): array {
        if (!preg_match('/^[a-f0-9]{64}$/',$rawToken)) return array();
        $hash=hash('sha256',$rawToken);
        $q=$this->db->query("SELECT * FROM `".DB_PREFIX."codecart_gdpr_request` WHERE token='".$this->db->escape($hash)."' AND status='pending' AND date_expire>NOW() LIMIT 1");
        if (!$q->num_rows) return array();
        $row=$q->row;
        $this->db->query("UPDATE `".DB_PREFIX."codecart_gdpr_request` SET status='confirmed', date_confirmed=NOW(), token='' WHERE request_id='".(int)$row['request_id']."'");
        $this->audit((int)$row['request_id'],'customer',(int)$row['customer_id'],'confirm_'.$row['type'],'');
        if ((string)$row['type']==='delete') {
            try { (new SystemNotification($this->registry))->notify('gdpr.delete.'.(int)$row['request_id'],'warning','Confirmed GDPR deletion request','Customer #'.(int)$row['customer_id'].' confirmed a deletion/anonymisation request. Review it before processing.','tool/codecart_core'); } catch (\Throwable $e) {}
        }
        $row['status']='confirmed';
        return $row;
    }


    public function getRequest(int $requestId): array {
        $q=$this->db->query("SELECT * FROM `".DB_PREFIX."codecart_gdpr_request` WHERE request_id='".(int)$requestId."' LIMIT 1");
        return $q->num_rows ? $q->row : array();
    }

    public function getConfirmedRequest(int $customerId,string $type): array {
        if (!in_array($type,array('export','delete'),true)) { return array(); }
        $q=$this->db->query("SELECT * FROM `".DB_PREFIX."codecart_gdpr_request` WHERE customer_id='".(int)$customerId."' AND type='".$this->db->escape($type)."' AND status='confirmed' ORDER BY request_id DESC LIMIT 1");
        return $q->num_rows ? $q->row : array();
    }

    public function cancelRequest(int $requestId): void {
        $this->db->query("UPDATE `".DB_PREFIX."codecart_gdpr_request` SET status='superseded', token='' WHERE request_id='".(int)$requestId."' AND status='pending'");
    }
    public function markExportProcessed(int $requestId): void {
        $this->db->query("UPDATE `".DB_PREFIX."codecart_gdpr_request` SET status='processed', date_processed=NOW() WHERE request_id='".(int)$requestId."' AND type='export' AND status='confirmed'");
    }

    public function getCustomerRequests(int $customerId): array {
        $q=$this->db->query("SELECT request_id,type,status,date_added,date_confirmed,date_processed,date_expire FROM `".DB_PREFIX."codecart_gdpr_request` WHERE customer_id='".(int)$customerId."' ORDER BY request_id DESC LIMIT 20");
        return $q->rows;
    }

    public function getRequests(int $limit=100): array {
        $limit=max(1,min(500,$limit));
        $q=$this->db->query("SELECT request_id,customer_id,email,type,status,date_added,date_confirmed,date_processed,date_expire FROM `".DB_PREFIX."codecart_gdpr_request` ORDER BY request_id DESC LIMIT ".(int)$limit);
        return $q->rows;
    }

    public function cleanup(int $days=365): int {
        $days=max(30,min(3650,$days));
        if (!$this->tableExists('codecart_gdpr_request')) return 0;
        $eligible=$this->db->query("SELECT request_id FROM `".DB_PREFIX."codecart_gdpr_request` WHERE status IN ('processed','superseded') AND COALESCE(date_processed,date_confirmed,date_added) < DATE_SUB(NOW(), INTERVAL ".(int)$days." DAY) LIMIT 2000");
        if (!$eligible->num_rows) return 0;
        $ids=array(); foreach($eligible->rows as $row){$ids[]=(int)$row['request_id'];}
        $ids=array_values(array_filter($ids)); if(!$ids) return 0;
        $idList=implode(',',$ids);
        if ($this->tableExists('codecart_gdpr_audit')) { $this->db->query("DELETE FROM `".DB_PREFIX."codecart_gdpr_audit` WHERE request_id IN (".$idList.")"); }
        $this->db->query("DELETE FROM `".DB_PREFIX."codecart_gdpr_request` WHERE request_id IN (".$idList.") AND status IN ('processed','superseded')");
        return (int)$this->db->countAffected();
    }

    public function exportCustomer(int $customerId): array {
        $out=array('generated_at'=>date(DATE_ATOM),'customer'=>array(),'addresses'=>array(),'orders'=>array(),'order_products'=>array(),'order_options'=>array(),'returns'=>array(),'rewards'=>array(),'transactions'=>array(),'wishlist'=>array(),'ips'=>array(),'activities'=>array(),'history'=>array());
        $customer=$this->fetchRows('customer','customer_id',$customerId);
        if ($customer) {
            unset($customer[0]['password'],$customer[0]['salt'],$customer[0]['token'],$customer[0]['code']);
            $out['customer']=$customer[0];
        }
        $out['addresses']=$this->fetchRows('address','customer_id',$customerId);
        $out['orders']=$this->fetchRows('order','customer_id',$customerId);
        $out['returns']=$this->fetchRows('return','customer_id',$customerId);
        $out['rewards']=$this->fetchRows('customer_reward','customer_id',$customerId);
        $out['transactions']=$this->fetchRows('customer_transaction','customer_id',$customerId);
        $out['wishlist']=$this->fetchRows('customer_wishlist','customer_id',$customerId);
        $out['ips']=$this->fetchRows('customer_ip','customer_id',$customerId);
        $out['activities']=$this->fetchRows('customer_activity','customer_id',$customerId);
        $out['history']=$this->fetchRows('customer_history','customer_id',$customerId);
        if ($out['orders'] && $this->tableExists('order_product')) {
            $ids=array_map(function($row){return (int)$row['order_id'];},$out['orders']);
            $idList=implode(',',array_filter($ids));
            if ($idList!=='') {
                $out['order_products']=$this->db->query("SELECT * FROM `".DB_PREFIX."order_product` WHERE order_id IN (".$idList.") ORDER BY order_product_id ASC")->rows;
                if ($this->tableExists('order_option')) $out['order_options']=$this->db->query("SELECT * FROM `".DB_PREFIX."order_option` WHERE order_id IN (".$idList.") ORDER BY order_option_id ASC")->rows;
            }
        }
        return $out;
    }

    public function processDelete(int $requestId,int $adminId): void {
        $q=$this->db->query("SELECT * FROM `".DB_PREFIX."codecart_gdpr_request` WHERE request_id='".(int)$requestId."' AND type='delete' AND status='confirmed' LIMIT 1");
        if (!$q->num_rows) throw new \RuntimeException('Confirmed GDPR deletion request not found.');
        $customerId=(int)$q->row['customer_id'];
        $customer=$this->fetchRows('customer','customer_id',$customerId);
        if (!$customer) throw new \RuntimeException('Customer not found.');
        $originalEmail=(string)$customer[0]['email'];
        $anonymous='deleted-'.$customerId.'-'.substr(hash('sha256',$originalEmail.'|'.$customerId),0,12).'@invalid.local';
        $sets=array();
        $values=array(
            'firstname'=>'Deleted','lastname'=>'Customer','email'=>$anonymous,'telephone'=>'','fax'=>'','newsletter'=>'0','address_id'=>'0','custom_field'=>'','token'=>'','code'=>'','status'=>'0','safe'=>'0'
        );
        foreach ($values as $column=>$value) if ($this->columnExists('customer',$column)) $sets[]="`".$column."`='".$this->db->escape((string)$value)."'";
        if ($this->columnExists('customer','password')) $sets[]="`password`='".$this->db->escape(password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT))."'";
        if ($sets) $this->db->query("UPDATE `".DB_PREFIX."customer` SET ".implode(', ',$sets)." WHERE customer_id='".$customerId."'");

        foreach (array('address','customer_ip','customer_wishlist','customer_search','customer_activity','customer_online','customer_history','codecart_customer_authorize') as $table) {
            if ($this->tableExists($table) && $this->columnExists($table,'customer_id')) {
                $this->db->query("DELETE FROM `".DB_PREFIX.$table."` WHERE customer_id='".$customerId."'");
            }
        }
        if ($this->tableExists('customer_login') && $this->columnExists('customer_login','email')) {
            $this->db->query("DELETE FROM `".DB_PREFIX."customer_login` WHERE email='".$this->db->escape($originalEmail)."'");
        }
        $this->db->query("UPDATE `".DB_PREFIX."codecart_gdpr_request` SET email='".$this->db->escape($anonymous)."', status='processed', date_processed=NOW() WHERE request_id='".(int)$requestId."'");
        $this->audit($requestId,'admin',$adminId,'anonymize_customer','Orders and accounting records retained.');
        try { (new SystemNotification($this->registry))->resolve('gdpr.delete.'.(int)$requestId); } catch (\Throwable $e) {}
    }

    private function fetchRows(string $table,string $column,int $id): array {
        if (!$this->tableExists($table) || !$this->columnExists($table,$column)) return array();
        return $this->db->query("SELECT * FROM `".DB_PREFIX.$table."` WHERE `".$column."`='".(int)$id."'")->rows;
    }
    private function audit(int $requestId,string $actorType,int $actorId,string $action,string $details): void {
        if (!$this->tableExists('codecart_gdpr_audit')) return;
        $ip=function_exists('codecart_client_ip')?(string)codecart_client_ip((array)$this->request->server):(isset($this->request->server['REMOTE_ADDR'])?(string)$this->request->server['REMOTE_ADDR']:'');
        $this->db->query("INSERT INTO `".DB_PREFIX."codecart_gdpr_audit` SET request_id='".(int)$requestId."', actor_type='".$this->db->escape($actorType)."', actor_id='".(int)$actorId."', action='".$this->db->escape(substr($action,0,64))."', ip='".$this->db->escape($ip)."', details='".$this->db->escape(substr($details,0,1000))."', date_added=NOW()");
    }
    private function tableExists(string $table): bool {
        $q=$this->db->query("SHOW TABLES LIKE '".$this->db->escape(DB_PREFIX.$table)."'"); return (bool)$q->num_rows;
    }
    private function columnExists(string $table,string $column): bool {
        if (!$this->tableExists($table)) return false;
        $q=$this->db->query("SHOW COLUMNS FROM `".DB_PREFIX.$table."` LIKE '".$this->db->escape($column)."'"); return (bool)$q->num_rows;
    }
}
