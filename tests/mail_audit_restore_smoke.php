<?php
/** Standalone regression: php tests/mail_audit_restore_smoke.php */
define('DB_PREFIX', 'oc_');

class FakeMailAuditDb {
    public $queries = array();
    public function query($sql) {
        $this->queries[] = $sql;
        return (object)array('num_rows' => 0, 'rows' => array(), 'row' => array('total' => 0));
    }
    public function escape($value) { return addslashes((string)$value); }
}
class Model { public $db; }
require dirname(__DIR__) . '/upload/system/library/codecart/src/MailAudit.php';
require dirname(__DIR__) . '/upload/admin/model/setting/mail_audit.php';

$db = new FakeMailAuditDb();
\CodeCart\Core\MailAudit::install($db);
if (strpos($db->queries[0], 'CREATE TABLE IF NOT EXISTS `oc_codecart_mail_audit`') === false
    || strpos($db->queries[0], 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci') === false) {
    throw new RuntimeException('Mail Audit missing safe schema creation');
}
foreach ($db->queries as $query) {
    if (preg_match('/\b(?:DELETE|DROP|TRUNCATE)\b/i', $query)) {
        throw new RuntimeException('Mail Audit restore must not delete existing data');
    }
}
$model = new ModelSettingMailAudit();
$model->db = $db;
$model->getRows(array('email'=>'demo@example.com', 'status'=>'accepted'), 1);
$lastQueries = array_slice($db->queries, -2);
if (strpos($lastQueries[0], 'FROM `oc_codecart_mail_audit` a WHERE (a.sender_email LIKE') === false
    || strpos($lastQueries[1], 'FROM `oc_codecart_mail_audit` a LEFT JOIN') === false
    || strpos($lastQueries[1], 'a.status =') === false) {
    throw new RuntimeException('Mail Audit list query aliases are incorrect');
}
echo "Mail Audit restore: PASS\n";
