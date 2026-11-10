<?php
/** Standalone regression test: php tests/installer_utf8mb4_and_icon_smoke.php */
require dirname(__DIR__) . '/upload/system/library/codecart/src/DatabaseDefaults.php';

class CodeCartFakeInstallerDb {
    public $queries = array();
    private $objects;
    private $schema;
    private $charset;
    private $collation;
    private $fail;

    public function __construct(array $objects, string $schema, string $charset, bool $fail = false, string $collation = '') {
        $this->objects = $objects;
        $this->schema = $schema;
        $this->charset = $charset;
        $this->collation = $collation !== '' ? $collation : ($charset === 'utf8mb4' ? 'utf8mb4_unicode_ci' : 'utf8mb3_unicode_ci');
        $this->fail = $fail;
    }

    public function query(string $sql) {
        $this->queries[] = $sql;
        if ($sql === 'SHOW FULL TABLES') {
            return (object)array('rows' => $this->objects);
        }
        if (strpos($sql, 'information_schema.SCHEMATA') !== false) {
            return (object)array('row' => array('schema_name' => $this->schema, 'db_charset' => $this->charset, 'db_collation' => $this->collation));
        }
        if ($this->fail) {
            throw new RuntimeException('SQL privilege denied');
        }
        return (object)array('rows' => array());
    }
}

$cases = array(
    array(array(array('table' => 'third_party')), 'shop', 'utf8mb3', false, 'existing_objects', false),
    array(array(), 'shop', 'utf8mb4', false, 'already_utf8mb4', false),
    array(array(), 'shop', 'utf8mb3', false, 'updated', true),
    array(array(), 'shop', 'utf8mb3', true, 'unavailable', true),
    array(array(), 'shop', 'utf8mb4', false, 'updated', true, 'utf8mb4_general_ci'),
    array(array(), 'shared-tenant', 'utf8mb3', false, 'unsupported_name', false),
);

foreach ($cases as $case) {
    $db = new CodeCartFakeInstallerDb($case[0], $case[1], $case[2], $case[3], $case[6] ?? '');
    $status = \CodeCart\Core\DatabaseDefaults::ensureUtf8mb4ForNewInstallation($db);
    if ($status !== $case[4]) {
        throw new RuntimeException('Incorrect charset installer status: ' . $status);
    }
    $alters = array_filter($db->queries, function ($sql) {
        return strpos($sql, 'ALTER DATABASE') === 0;
    });
    if ((count($alters) > 0) !== $case[5]) {
        throw new RuntimeException('Unsafe or missing database ALTER.');
    }
}

define('DIR_SYSTEM', dirname(__DIR__) . '/upload/system/');
require DIR_SYSTEM . 'library/codecart/src/FontAwesomeManager.php';
$font = new \CodeCart\Core\FontAwesomeManager('codecart');
if (!$font->isSupported('fa-file-pdf-o') || strpos($font->extraCss(), '.fa.fa-file-pdf-o') === false) {
    throw new RuntimeException('Built-in PDF icon is not available in lightweight Core mode.');
}
echo "Installer charset and PDF icon: PASS\n";
