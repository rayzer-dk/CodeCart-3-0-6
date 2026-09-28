<?php
namespace CodeCart\Core;

final class SystemNotification {
    private $registry;
    private $db;
    private $tableReady = null;

    public function __construct($registry) { $this->registry=$registry; $this->db = $registry->get('db'); }

    public function notify(string $code, string $severity, string $title, string $message, string $route = ''): void {
        $code = strtolower(trim($code));
        if ($code === '' || !preg_match('/^[a-z0-9_.:-]{2,160}$/', $code)) { return; }
        $severity = in_array($severity, array('info','warning','error','critical'), true) ? $severity : 'warning';
        $title = substr(trim($title), 0, 190);
        $message = substr(trim($message), 0, 4000);
        $route = substr(trim($route), 0, 255);
        if ($title === '' || $message === '' || !$this->tableExists()) { return; }

        // Atomic upsert avoids the SELECT-then-INSERT race when two requests publish
        // the same notification code at the same time. The UNIQUE(code) key is the
        // concurrency guard. Status is evaluated before the mutable fields are
        // overwritten, so a changed/resolved notice becomes unread again while an
        // unchanged read notice stays read.
        $this->db->query("INSERT INTO `" . DB_PREFIX . "codecart_notification` SET " .
            "code='" . $this->db->escape($code) . "', " .
            "severity='" . $this->db->escape($severity) . "', " .
            "title='" . $this->db->escape($title) . "', " .
            "message='" . $this->db->escape($message) . "', " .
            "route='" . $this->db->escape($route) . "', " .
            "status='unread', date_added=NOW(), date_modified=NOW() " .
            "ON DUPLICATE KEY UPDATE " .
            "status=IF(severity<>VALUES(severity) OR title<>VALUES(title) OR message<>VALUES(message) OR route<>VALUES(route) OR status='resolved','unread',status), " .
            "severity=VALUES(severity), title=VALUES(title), message=VALUES(message), route=VALUES(route), date_modified=NOW()");
    }

    public function resolve(string $code): void {
        if (!$this->tableExists()) { return; }
        $this->db->query("UPDATE `" . DB_PREFIX . "codecart_notification` SET status='resolved', date_modified=NOW() WHERE code='" . $this->db->escape(strtolower(trim($code))) . "' AND status!='resolved'");
    }

    public function unreadCount(): int {
        if (!$this->tableExists()) { return 0; }
        $q=$this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "codecart_notification` WHERE status='unread'");
        return isset($q->row['total']) ? (int)$q->row['total'] : 0;
    }

    public function latest(int $limit=5): array {
        if (!$this->tableExists()) { return array(); }
        $limit=max(1,min(50,$limit));
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "codecart_notification` WHERE status!='resolved' ORDER BY FIELD(severity,'critical','error','warning','info'), notification_id DESC LIMIT " . (int)$limit)->rows;
    }

    public function all(int $limit=100): array {
        if (!$this->tableExists()) { return array(); }
        $limit=max(1,min(500,$limit));
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "codecart_notification` ORDER BY (status='unread') DESC, FIELD(severity,'critical','error','warning','info'), notification_id DESC LIMIT " . (int)$limit)->rows;
    }


    public function present(array $rows): array {
        $language=$this->registry->has('language')?$this->registry->get('language'):null;
        if ($language && $this->registry->has('load')) {
            $loader=$this->registry->get('load');
            if (is_object($loader) && method_exists($loader,'language')) {
                // Header notifications are rendered outside tool/notification, so load
                // the canonical notification translations before presenting DB rows.
                $loader->language('tool/notification');
            }
        }
        foreach($rows as &$row){
            $code=isset($row['code'])?(string)$row['code']:'';
            if($language && strpos($code,'scheduler.')===0){
                $task=substr($code,strlen('scheduler.'));
                $row['title']=sprintf($language->get('text_notice_cron_failed'),$task);
                if(trim((string)$row['message'])==='Handler returned false') {
                    $row['message']=$language->get('text_notice_handler_false');
                } elseif (stripos((string)$row['message'], 'URL rejected: No host part in the URL') !== false) {
                    $row['message']=$language->get('text_notice_invalid_catalog_url');
                }
            } elseif($language && strpos($code,'health.')===0){
                $healthKey = substr($code, 7);
                $titleKey = 'text_health_' . str_replace('.', '_', $healthKey);
                $messageKey = $titleKey . '_message';
                $localizedTitle = $language->get($titleKey);
                $localizedMessage = $language->get($messageKey);
                // Families with a variable component (PHP extensions, writable paths)
                // share one phrase: "PHP extension: %s", "Directory: %s".
                $group = strpos($healthKey, '.') !== false ? substr($healthKey, 0, strpos($healthKey, '.')) : '';
                if ($localizedTitle === $titleKey && in_array($group, array('ext', 'path'), true) && $language->get('text_health_group_' . $group) !== 'text_health_group_' . $group) {
                    $localizedTitle = sprintf($language->get('text_health_group_' . $group), substr($healthKey, strlen($group) + 1));
                    $groupMessage = $language->get('text_health_group_' . $group . '_message');
                    if ($groupMessage !== 'text_health_group_' . $group . '_message') { $localizedMessage = $groupMessage; $messageKey = ''; }
                }
                if ($localizedTitle !== $titleKey) { $row['title'] = $localizedTitle; }
                elseif (strpos((string)$row['title'],'System check: ')===0) { $row['title']=sprintf($language->get('text_notice_system_check'),substr((string)$row['title'],14)); }
                if ($localizedMessage !== $messageKey && $localizedMessage !== '') {
                    $value=$this->healthValue((string)$healthKey,(string)$row['message'],$language);
                    $row['message']=$value!==''?$value.' — '.$localizedMessage:$localizedMessage;
                }
            }
        }
        unset($row);
        return $rows;
    }

    private function healthValue(string $healthKey,string $message,$language): string {
        $message=trim($message);
        if ($message==='') { return ''; }

        // New health notices store the diagnostic value on its own first line.
        if (strpos($message,"\n")!==false) {
            $parts=explode("\n",$message,2);
            return $this->translateHealthValue($healthKey,trim($parts[0]),$language);
        }

        // Backward-compatible extraction for notices written by older builds.
        if ($healthKey==='db.schema_version' && preg_match('/^((?:\d+\.){2,3}\d+|not recorded)\b/i',$message,$match)) {
            return $this->translateHealthValue($healthKey,$match[1],$language);
        }
        if ($healthKey==='db.strict_sql') {
            if (stripos($message,'not active ')===0) { return $this->translateHealthValue($healthKey,'not active',$language); }
            if (stripos($message,'active ')===0) { return $this->translateHealthValue($healthKey,'active',$language); }
        }
        if ($healthKey==='path.storage_public') {
            if (stripos($message,'inside document root ')===0) { return $this->translateHealthValue($healthKey,'inside document root',$language); }
            if (stripos($message,'outside document root ')===0) { return $this->translateHealthValue($healthKey,'outside document root',$language); }
        }
        if ($healthKey==='security.display_errors') {
            if (preg_match('/^(On|Off)\b/i',$message,$match)) { return $this->translateHealthValue($healthKey,$match[1],$language); }
        }
        if ($healthKey==='http.https') {
            if (stripos($message,'not detected')===0) { return $this->translateHealthValue($healthKey,'not detected',$language); }
            if (stripos($message,'active')===0) { return $this->translateHealthValue($healthKey,'active',$language); }
        }
        if ($healthKey==='db.migration_history' && stripos($message,'no history ')===0) {
            return $this->translateHealthValue($healthKey,'no history',$language);
        }
        return '';
    }

    private function translateHealthValue(string $healthKey,string $value,$language): string {
        $map=array(
            'active'=>'text_health_value_active',
            'not active'=>'text_health_value_not_active',
            'inside document root'=>'text_health_value_inside_webroot',
            'outside document root'=>'text_health_value_outside_webroot',
            'on'=>'text_health_value_on',
            'off'=>'text_health_value_off',
            'not recorded'=>'text_health_value_not_recorded',
            'no history'=>'text_health_value_no_history',
            'not detected'=>'text_health_value_not_detected',
            'enabled'=>'text_health_value_enabled',
            'disabled'=>'text_health_value_disabled',
            'loaded'=>'text_health_value_loaded',
            'missing'=>'text_health_value_missing',
            'never'=>'text_health_value_never',
            'not configured'=>'text_health_value_not_configured',
            'check failed'=>'text_health_value_check_failed'
        );
        $normalized=strtolower(trim($value));
        if (isset($map[$normalized])) {
            $translated=$language->get($map[$normalized]);
            if ($translated!==$map[$normalized]) { return $translated; }
        }
        return trim($value);
    }
    public function markRead(int $id): void {
        if ($id>0 && $this->tableExists()) $this->db->query("UPDATE `" . DB_PREFIX . "codecart_notification` SET status='read', date_modified=NOW() WHERE notification_id='" . (int)$id . "' AND status='unread'");
    }

    public function markAllRead(): void {
        if ($this->tableExists()) $this->db->query("UPDATE `" . DB_PREFIX . "codecart_notification` SET status='read', date_modified=NOW() WHERE status='unread'");
    }


    public function delete(int $id): void {
        if ($id>0 && $this->tableExists()) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_notification` WHERE notification_id='" . (int)$id . "'");
        }
    }

    public function deleteReadAndResolved(): void {
        if ($this->tableExists()) {
            $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_notification` WHERE status IN ('read','resolved')");
        }
    }

    public function cleanup(int $days = 30): int {
        $days = max(1, min(3650, $days));
        if (!$this->tableExists()) { return 0; }
        $this->db->query("DELETE FROM `" . DB_PREFIX . "codecart_notification` WHERE status IN ('read','resolved') AND date_modified < DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)");
        return method_exists($this->db, 'countAffected') ? (int)$this->db->countAffected() : 0;
    }

    private function tableExists(): bool {
        if ($this->tableReady !== null) { return $this->tableReady; }
        try {
            $q=$this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . "codecart_notification") . "'");
            $this->tableReady=(bool)$q->num_rows;
        } catch (\Throwable $e) { $this->tableReady=false; }
        return $this->tableReady;
    }
}
