<?php
namespace CodeCart\Core;

final class DeviceAuthorization {
    private $registry;
    private $db;
    private $config;
    private $session;
    private $request;
    private $storageReady = array();

    public function __construct($registry) {
        $this->registry=$registry;
        $this->db=$registry->get('db');
        $this->config=$registry->get('config');
        $this->session=$registry->get('session');
        $this->request=$registry->get('request');
    }

    public function enabled(string $type): bool {
        return (bool)(int)$this->config->get($type === 'admin' ? 'codecart_admin_device_authorize_status' : 'codecart_customer_device_authorize_status');
    }

    public function isStorageReady(string $type): bool {
        if (array_key_exists($type, $this->storageReady)) { return $this->storageReady[$type]; }
        list($table,) = $this->target($type);
        try {
            $q = $this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . $table) . "'");
            $this->storageReady[$type] = (bool)$q->num_rows;
        } catch (\Throwable $e) {
            $this->storageReady[$type] = false;
        }
        return $this->storageReady[$type];
    }

    public function isTrusted(string $type,int $entityId): bool {
        if (!$this->enabled($type) || $entityId < 1) return true;
        if (!$this->isStorageReady($type)) return true;
        $cookie=$this->cookieName($type);
        $raw=isset($this->request->cookie[$cookie]) ? trim((string)$this->request->cookie[$cookie]) : '';
        if (!preg_match('/^[a-f0-9]{64}$/',$raw)) return false;
        $token=hash('sha256',$raw);
        list($table,$column)=$this->target($type);
        $q=$this->db->query("SELECT authorize_id FROM `".DB_PREFIX.$table."` WHERE `".$column."`='".(int)$entityId."' AND token='".$this->db->escape($token)."' AND status='trusted' AND date_expire>NOW() LIMIT 1");
        return (bool)$q->num_rows;
    }

    public function hasPendingChallenge(string $type,int $entityId): bool {
        if (!$this->isStorageReady($type)) return false;
        $key=$this->sessionKey($type);
        if (empty($this->session->data[$key]['id']) || empty($this->session->data[$key]['token'])) return false;
        list($table,$column)=$this->target($type);
        $id=(int)$this->session->data[$key]['id'];
        $token=hash('sha256',(string)$this->session->data[$key]['token']);
        $q=$this->db->query("SELECT authorize_id FROM `".DB_PREFIX.$table."` WHERE authorize_id='".$id."' AND `".$column."`='".(int)$entityId."' AND token='".$this->db->escape($token)."' AND status='pending' AND attempts<5 AND date_expire>NOW() LIMIT 1");
        return (bool)$q->num_rows;
    }

    public function startChallenge(string $type,int $entityId): array {
        if ($entityId < 1) throw new \InvalidArgumentException('Invalid account.');
        if (!$this->isStorageReady($type)) throw new \RuntimeException('Device authorization storage is not ready. Run Installer UPDATE.');
        list($table,$column)=$this->target($type);
        $this->db->query("UPDATE `".DB_PREFIX.$table."` SET status='superseded' WHERE `".$column."`='".(int)$entityId."' AND status='pending'");
        $raw=bin2hex(random_bytes(32));
        $code=(string)random_int(100000,999999);
        $tokenHash=hash('sha256',$raw);
        $codeHash=hash_hmac('sha256',$code,$raw);
        $ip=$this->clientIp();
        $ua=substr(isset($this->request->server['HTTP_USER_AGENT'])?(string)$this->request->server['HTTP_USER_AGENT']:'',0,512);
        $expire=date('Y-m-d H:i:s',time()+600);
        $this->db->query("INSERT INTO `".DB_PREFIX.$table."` SET `".$column."`='".(int)$entityId."', token='".$this->db->escape($tokenHash)."', code_hash='".$this->db->escape($codeHash)."', ip='".$this->db->escape($ip)."', user_agent='".$this->db->escape($ua)."', attempts='0', status='pending', date_added=NOW(), date_expire='".$this->db->escape($expire)."'");
        $id=(int)$this->db->getLastId();
        $this->session->data[$this->sessionKey($type)]=array('id'=>$id,'token'=>$raw,'entity_id'=>$entityId);
        return array('authorize_id'=>$id,'code'=>$code,'expires_in'=>600);
    }

    public function verify(string $type,int $entityId,string $code): array {
        if (!$this->isStorageReady($type)) return array('success'=>false,'message'=>'Challenge unavailable.');
        $key=$this->sessionKey($type);
        if (!$this->hasPendingChallenge($type,$entityId)) return array('success'=>false,'message'=>'Challenge expired.');
        $id=(int)$this->session->data[$key]['id'];
        $raw=(string)$this->session->data[$key]['token'];
        list($table,$column)=$this->target($type);
        $q=$this->db->query("SELECT * FROM `".DB_PREFIX.$table."` WHERE authorize_id='".$id."' AND `".$column."`='".(int)$entityId."' LIMIT 1");
        if (!$q->num_rows) return array('success'=>false,'message'=>'Challenge not found.');
        $expected=hash_hmac('sha256',trim($code),$raw);
        if (!hash_equals((string)$q->row['code_hash'],$expected)) {
            $attempts=(int)$q->row['attempts']+1;
            $status=$attempts>=5?'blocked':'pending';
            $this->db->query("UPDATE `".DB_PREFIX.$table."` SET attempts='".$attempts."', status='".$status."' WHERE authorize_id='".$id."'");
            if ($status==='blocked') unset($this->session->data[$key]);
            return array('success'=>false,'message'=>$status==='blocked'?'Too many invalid codes.':'Invalid code.');
        }
        $this->db->query("UPDATE `".DB_PREFIX.$table."` SET status='verified', date_used=NOW() WHERE authorize_id='".$id."'");
        $deviceRaw=bin2hex(random_bytes(32));
        $deviceHash=hash('sha256',$deviceRaw);
        $ip=$this->clientIp();
        $ua=substr(isset($this->request->server['HTTP_USER_AGENT'])?(string)$this->request->server['HTTP_USER_AGENT']:'',0,512);
        $expire=date('Y-m-d H:i:s',time()+15552000); // 180 days
        $this->db->query("INSERT INTO `".DB_PREFIX.$table."` SET `".$column."`='".(int)$entityId."', token='".$this->db->escape($deviceHash)."', code_hash='', ip='".$this->db->escape($ip)."', user_agent='".$this->db->escape($ua)."', attempts='0', status='trusted', date_added=NOW(), date_expire='".$this->db->escape($expire)."', date_used=NOW()");
        $this->setCookie($this->cookieName($type),$deviceRaw,time()+15552000);
        unset($this->session->data[$key]);
        return array('success'=>true,'message'=>'OK');
    }


    public function countTrusted(string $type, int $entityId): int {
        if ($entityId < 1 || !$this->isStorageReady($type)) { return 0; }
        list($table,$column)=$this->target($type);
        try {
            $q=$this->db->query("SELECT COUNT(*) AS total FROM `".DB_PREFIX.$table."` WHERE `".$column."`='".(int)$entityId."' AND status='trusted' AND date_expire>NOW()");
            return isset($q->row['total']) ? (int)$q->row['total'] : 0;
        } catch (\Throwable $e) { return 0; }
    }

    public function cancelPending(string $type, int $entityId): void {
        if ($this->isStorageReady($type)) {
            list($table,$column)=$this->target($type);
            try { $this->db->query("UPDATE `".DB_PREFIX.$table."` SET status='superseded' WHERE `".$column."`='".(int)$entityId."' AND status='pending'"); } catch (\Throwable $e) {}
        }
        unset($this->session->data[$this->sessionKey($type)]);
    }
    public function revokeAll(string $type,int $entityId): void {
        if ($this->isStorageReady($type)) {
            list($table,$column)=$this->target($type);
            $this->db->query("DELETE FROM `".DB_PREFIX.$table."` WHERE `".$column."`='".(int)$entityId."'");
        }
        $this->setCookie($this->cookieName($type),'',time()-3600);
        unset($this->session->data[$this->sessionKey($type)]);
    }

    private function target(string $type): array {
        if ($type==='admin') return array('codecart_user_authorize','user_id');
        if ($type==='customer') return array('codecart_customer_authorize','customer_id');
        throw new \InvalidArgumentException('Invalid authorization type.');
    }
    private function cookieName(string $type): string { return $type==='admin'?'CCPADMINDEVICE':'CCPCUSTOMERDEVICE'; }
    private function sessionKey(string $type): string { return 'codecart_'.$type.'_device_challenge'; }
    private function clientIp(): string {
        if (function_exists('codecart_client_ip')) return (string)codecart_client_ip((array)$this->request->server);
        return isset($this->request->server['REMOTE_ADDR'])?(string)$this->request->server['REMOTE_ADDR']:'';
    }
    private function setCookie(string $name,string $value,int $expires): void {
        $secure=function_exists('codecart_is_https')?(bool)codecart_is_https():!empty($this->request->server['HTTPS']);
        setcookie($name,$value,array('expires'=>$expires,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax'));
        $this->request->cookie[$name]=$value;
    }
}
