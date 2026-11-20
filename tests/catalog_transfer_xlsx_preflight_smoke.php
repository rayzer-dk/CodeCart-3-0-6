<?php
// Regression: XLSX preflight must retain streamSheet() metadata to check the source columns.
require_once dirname(__DIR__) . '/upload/system/library/codecart/src/SimpleXlsx.php';

use CodeCart\Core\SimpleXlsx;

if (!SimpleXlsx::writeAvailable() || !SimpleXlsx::readAvailable()) {
    fwrite(STDERR, "FAIL: ZIP and SimpleXML are required for the XLSX preflight test.\n");
    exit(1);
}

$source = file_get_contents(dirname(__DIR__) . '/upload/system/library/codecart/src/CatalogTransfer.php');
if ($source === false || strpos($source, '$info = SimpleXlsx::streamSheet($xlsx,$sourceLogical,') === false) {
    fwrite(STDERR, "FAIL: CatalogTransfer no longer captures XLSX source column metadata.\n");
    exit(1);
}

$base = tempnam(sys_get_temp_dir(), 'ccp_xlsx_');
if ($base === false) {
    fwrite(STDERR, "FAIL: Could not allocate temporary XLSX path.\n");
    exit(1);
}
@unlink($base);
$file = $base . '.xlsx';

try {
    $columns = array('product_id', 'name', 'price');
    SimpleXlsx::write($file, array(
        'product' => array('columns' => $columns, 'rows' => array(
            array('product_id' => 123, 'name' => 'Sample product', 'price' => 12.5)
        ))
    ));
    $seen = array();
    $info = SimpleXlsx::streamSheet($file, 'product', function ($row) use (&$seen) {
        $seen[] = $row;
    });
    if (!isset($info['columns'], $info['rows'])
        || $info['columns'] !== $columns
        || (int)$info['rows'] !== 1
        || count($seen) !== 1
        || (int)$seen[0]['product_id'] !== 123
        || (string)$seen[0]['name'] !== 'Sample product') {
        throw new RuntimeException('XLSX stream metadata or parsed row differs from the expected values.');
    }
    echo "PASS: XLSX preflight retains column metadata and streams rows.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "FAIL: " . $exception->getMessage() . "\n");
    exit(1);
} finally {
    @unlink($file);
}
