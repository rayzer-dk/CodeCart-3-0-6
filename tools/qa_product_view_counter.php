<?php
declare(strict_types=1);

// Runs against an explicitly supplied disposable local installation.
if ($argc !== 4 || $argv[1] !== '--disposable-config') {
    fwrite(STDERR, "Usage: php tools/qa_product_view_counter.php --disposable-config /staging/upload/config.php http://127.0.0.1:port/\n");
    exit(2);
}
$base = rtrim($argv[3], '/') . '/';
if (!in_array(parse_url($base, PHP_URL_HOST), array('127.0.0.1', 'localhost'), true)) {
    throw new RuntimeException('This mutation fixture requires a local disposable installation.');
}
require $argv[2];
if (!in_array(DB_HOSTNAME, array('127.0.0.1', 'localhost'), true) || !preg_match('/^[a-zA-Z0-9_]+$/D', DB_PREFIX)) {
    throw new RuntimeException('This fixture requires a local database and a valid table prefix.');
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
$db->set_charset('utf8mb4');
$product = $db->query("SELECT p.product_id,p.status,p.date_available,p.viewed FROM " . DB_PREFIX . "product p INNER JOIN " . DB_PREFIX . "product_to_store p2s ON p2s.product_id=p.product_id WHERE p.status=1 AND p.date_available<=NOW() AND p2s.store_id=0 ORDER BY p.product_id LIMIT 1")->fetch_assoc();
if (!$product) { throw new RuntimeException('Disposable installation needs one available product.'); }
$id = (int)$product['product_id'];
$category = $db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id=" . $id . " ORDER BY category_id LIMIT 1")->fetch_assoc();
$cookie = tempnam(sys_get_temp_dir(), 'cc-view-qa-');
$checks = 0;
$failures = array();
function viewed(): int {
    global $db, $id;
    return (int)$db->query('SELECT viewed FROM ' . DB_PREFIX . 'product WHERE product_id=' . $id)->fetch_row()[0];
}
function requestPage(string $route, array $query = array(), ?array $post = null): int {
    global $base, $cookie;
    $curl = curl_init($base . 'index.php?' . http_build_query(array_merge(array('route' => $route), $query)));
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5, CURLOPT_TIMEOUT => 20, CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie));
    if ($post !== null) { curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($body === false || $status >= 500 || preg_match('/Fatal error|Parse error|Uncaught (?:Error|Exception)/i', $body)) {
        throw new RuntimeException('Storefront request failed: ' . $route . ' HTTP ' . $status . ' ' . $error);
    }
    return $status;
}
function checkRequest(string $name, string $route, array $query, int $increment, ?array $post = null, ?int $expectedStatus = null): void {
    global $checks, $failures;
    $before = viewed();
    $status = requestPage($route, $query, $post);
    $after = viewed();
    if ($after - $before !== $increment || ($expectedStatus !== null && $status !== $expectedStatus)) {
        $failures[] = $name;
        echo 'FAIL ', $name, ': viewed delta=', $after - $before, ', expected=', $increment, ', HTTP=', $status, PHP_EOL;
    } else { $checks++; echo 'PASS ', $name, PHP_EOL; }
}
try {
    checkRequest('successful product opening', 'product/product', array('product_id' => $id), 1, null, 200);
    checkRequest('repeat successful opening', 'product/product', array('product_id' => $id), 1, null, 200);
    checkRequest('missing product', 'product/product', array('product_id' => 2147483000), 0, null, 404);
    checkRequest('rejected category path', 'product/product', array('product_id' => $id, 'path' => 2147483000), 0, null, 404);
    checkRequest('rejected manufacturer', 'product/product', array('product_id' => $id, 'manufacturer_id' => 2147483000), 0, null, 404);
    foreach (array('disabled' => 'status=0', 'future' => "date_available='2099-01-01'") as $name => $assignment) {
        $db->query('UPDATE ' . DB_PREFIX . 'product SET ' . $assignment . ' WHERE product_id=' . $id);
        try { checkRequest($name . ' product', 'product/product', array('product_id' => $id), 0, null, 404); }
        finally {
            $db->query('UPDATE ' . DB_PREFIX . 'product SET status=' . (int)$product['status'] . ",date_available='" . $db->real_escape_string($product['date_available']) . "' WHERE product_id=" . $id);
        }
    }
    $db->query('DELETE FROM ' . DB_PREFIX . 'product_to_store WHERE product_id=' . $id . ' AND store_id=0');
    try { checkRequest('product outside current store', 'product/product', array('product_id' => $id), 0, null, 404); }
    finally { $db->query('INSERT INTO ' . DB_PREFIX . 'product_to_store SET product_id=' . $id . ',store_id=0'); }
    checkRequest('home and popular modules do not count as a view', 'common/home', array(), 0, null, 200);
    checkRequest('search does not count as a view', 'product/search', array('search' => 'a'), 0, null, 200);
    if ($category) { checkRequest('category does not count as a view', 'product/category', array('path' => (int)$category['category_id']), 0, null, 200); }
    checkRequest('review AJAX does not count as a view', 'product/product/review', array('product_id' => $id), 0, null, 200);
    checkRequest('price preview does not count as a view', 'product/product/pricePreview', array(), 0, array('product_id' => $id, 'quantity' => 1), 200);
    checkRequest('recurring AJAX does not count as a view', 'product/product/getRecurringDescription', array(), 0, array('product_id' => $id, 'recurring_id' => 0, 'quantity' => 1), 200);
} finally {
    $db->query('UPDATE ' . DB_PREFIX . 'product SET viewed=' . (int)$product['viewed'] . ' WHERE product_id=' . $id);
    unlink($cookie);
}
echo 'RESULT ', $checks, ' scenarios passed, ', count($failures), ' failed; actual local HTTP and MySQL.', PHP_EOL;
exit($failures ? 1 : 0);
