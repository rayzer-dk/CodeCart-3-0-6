<?php
declare(strict_types=1);

// This fixture creates and removes its own users and harmless OCMOD rows.
if ($argc !== 4 || $argv[1] !== '--disposable-config') {
    fwrite(STDERR, "Usage: php tools/qa_modification_bulk.php --disposable-config /staging/upload/admin/config.php http://127.0.0.1:port/admin/\n");
    exit(2);
}
$base = rtrim($argv[3], '/') . '/';
if (!in_array(parse_url($base, PHP_URL_HOST), array('127.0.0.1', 'localhost'), true)) {
    throw new RuntimeException('A disposable local installation is required.');
}
require $argv[2];
if (!in_array(DB_HOSTNAME, array('127.0.0.1', 'localhost'), true) || !preg_match('/^[a-zA-Z0-9_]+$/D', DB_PREFIX)) {
    throw new RuntimeException('A local database and valid table prefix are required.');
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
$db->set_charset('utf8mb4');
$suffix = bin2hex(random_bytes(5));
$username = 'qamod_' . $suffix;
$password = bin2hex(random_bytes(16));
$cookie = tempnam(sys_get_temp_dir(), 'cc-mod-qa-');
$ids = array();
$userId = $groupId = 0;
$token = '';
$checks = 0;
$failures = array();
$statePath = rtrim(DIR_STORAGE, '/\\') . '/codecart/ocmod-state.json';
$originalState = is_file($statePath) ? file_get_contents($statePath) : null;

function requestAdmin(string $route, ?array $post = null, ?string $requestToken = null, array $query = array()): array {
    global $base, $cookie, $token;
    $query = array_merge(array('route' => $route, 'user_token' => $requestToken ?? $token), $query);
    $curl = curl_init($base . 'index.php?' . http_build_query($query));
    curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5, CURLOPT_TIMEOUT => 20, CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie));
    if ($post !== null) { curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $body = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
    $error = curl_error($curl);
    curl_close($curl);
    if ($body === false || $status >= 500 || preg_match('/Fatal error|Parse error|Uncaught (?:Error|Exception)/i', $body)) {
        throw new RuntimeException('Admin request failed: ' . $route . ' HTTP ' . $status . ' ' . $error);
    }
    return array('body' => $body, 'status' => $status, 'url' => $url);
}
function expectBulk(string $name, bool $passed): void {
    global $checks, $failures;
    if ($passed) { $checks++; echo 'PASS ', $name, PHP_EOL; }
    else { $failures[] = $name; echo 'FAIL ', $name, PHP_EOL; }
}
function statuses(): array {
    global $db, $ids;
    $rows = $db->query('SELECT status FROM ' . DB_PREFIX . 'modification WHERE modification_id IN (' . implode(',', $ids) . ') ORDER BY modification_id')->fetch_all(MYSQLI_NUM);
    return array_map(static function (array $row): int { return (int)$row[0]; }, $rows);
}
function fixturePermissions(bool $access, bool $modify): void {
    global $db, $groupId;
    $permissions = json_encode(array('access' => $access ? array('marketplace/modification') : array(),
        'modify' => $modify ? array('marketplace/modification') : array()));
    $db->query("UPDATE " . DB_PREFIX . "user_group SET permission='" . $db->real_escape_string($permissions) . "' WHERE user_group_id=" . $groupId);
}
try {
    $db->query("INSERT INTO " . DB_PREFIX . "user_group SET name='Bulk QA " . $suffix . "',permission='{}'");
    $groupId = (int)$db->insert_id;
    fixturePermissions(true, true);
    $db->query("INSERT INTO " . DB_PREFIX . "user SET user_group_id=" . $groupId . ",username='" . $username . "',password='" . password_hash($password, PASSWORD_BCRYPT) . "',salt='',firstname='Bulk',lastname='QA',email='qa@example.test',image='',code='',ip='',status=1,date_added=NOW()");
    $userId = (int)$db->insert_id;
    foreach (array(0, 0, 1) as $index => $status) {
        $db->query("INSERT INTO " . DB_PREFIX . "modification SET extension_install_id=0,name='AAA Bulk QA " . $suffix . '-' . $index . "',code='bulk_qa_" . $suffix . '_' . $index . "',author='QA',version='1.0.0',link='',xml='<modification/>',status=" . $status . ',date_added=NOW()');
        $ids[] = (int)$db->insert_id;
    }
    $login = requestAdmin('common/login', array('username' => $username, 'password' => $password));
    parse_str((string)parse_url($login['url'], PHP_URL_QUERY), $loginQuery);
    $token = (string)($loginQuery['user_token'] ?? '');
    if ($token === '') { throw new RuntimeException('Fixture admin login failed.'); }

    $page = requestAdmin('marketplace/modification');
    expectBulk('list provides both selected-action buttons', str_contains($page['body'], 'id="button-bulk-enable"') && str_contains($page['body'], 'id="button-bulk-disable"'));
    requestAdmin('marketplace/modification/bulkEnable', array('selected' => array($ids[0], $ids[1], $ids[0])), null, array('sort' => 'name', 'order' => 'DESC', 'page' => 1));
    expectBulk('POST enables selected unique IDs and leaves unselected row', statuses() === array(1, 1, 1));
    $state = is_file($statePath) ? json_decode((string)file_get_contents($statePath), true) : array();
    expectBulk('bulk uses existing OCMOD dirty signal', ($state['status'] ?? '') === 'dirty' && ($state['reason'] ?? '') === 'modification_enabled');
    $response = requestAdmin('marketplace/modification/bulkDisable', array('selected' => array($ids[0], $ids[1])), null, array('sort' => 'name', 'order' => 'DESC', 'page' => 1));
    expectBulk('POST disables selected rows and leaves unselected enabled', statuses() === array(0, 0, 1));
    parse_str((string)parse_url($response['url'], PHP_URL_QUERY), $redirectQuery);
    expectBulk('redirect retains sort order and page', ($redirectQuery['route'] ?? '') === 'marketplace/modification' && ($redirectQuery['sort'] ?? '') === 'name' && ($redirectQuery['order'] ?? '') === 'DESC' && ($redirectQuery['page'] ?? '') === '1');

    foreach (array(
        'GET cannot change selected rows' => array(null, null, array('selected' => array($ids[0]))),
        'missing token cannot change rows' => array(array('selected' => array($ids[0])), '', array()),
        'invalid token cannot change rows' => array(array('selected' => array($ids[0])), 'invalid', array()),
        'empty selection is rejected' => array(array(), null, array()),
        'scalar selection is rejected' => array(array('selected' => (string)$ids[0]), null, array()),
        'noninteger and nested IDs cannot select a row' => array(array('selected' => array((string)$ids[0] . 'x', array($ids[0]), 0, -1, '1e2')), null, array()),
        'nonexistent IDs cannot select a row' => array(array('selected' => array(2147483000)), null, array()),
        'invalid ID prevents changing valid IDs in the same selection' => array(array('selected' => array($ids[0], 2147483000)), null, array())
    ) as $name => $input) {
        $response = requestAdmin('marketplace/modification/bulkEnable', $input[0], $input[1], $input[2]);
        expectBulk($name, statuses() === array(0, 0, 1));
        if ($name === 'GET cannot change selected rows') {
            expectBulk('GET returns method-not-allowed', $response['status'] === 405);
        }
        if ($name === 'empty selection is rejected') {
            expectBulk('empty selection shows an error', (bool)preg_match('/class="alert alert-danger/', $response['body']));
        }
    }
    fixturePermissions(true, false);
    requestAdmin('marketplace/modification/bulkEnable', array('selected' => array($ids[0])));
    expectBulk('modify permission required', statuses() === array(0, 0, 1));
    fixturePermissions(false, true);
    requestAdmin('marketplace/modification/bulkEnable', array('selected' => array($ids[0])));
    expectBulk('access permission required', statuses() === array(0, 0, 1));
    fixturePermissions(true, true);
    requestAdmin('marketplace/modification/enable', null, null, array('modification_id' => $ids[0]));
    expectBulk('existing single enable action remains compatible', statuses() === array(1, 0, 1));
    requestAdmin('marketplace/modification/disable', null, null, array('modification_id' => $ids[0]));
    expectBulk('existing single disable action remains compatible', statuses() === array(0, 0, 1));
} finally {
    if ($ids) { $db->query('DELETE FROM ' . DB_PREFIX . 'modification WHERE modification_id IN (' . implode(',', $ids) . ')'); }
    if ($userId) { $db->query('DELETE FROM ' . DB_PREFIX . 'user WHERE user_id=' . $userId); }
    if ($groupId) { $db->query('DELETE FROM ' . DB_PREFIX . 'user_group WHERE user_group_id=' . $groupId); }
    if ($originalState !== null) { file_put_contents($statePath, $originalState, LOCK_EX); }
    elseif (is_file($statePath)) { unlink($statePath); }
    unlink($cookie);
}
echo 'RESULT ', $checks, ' scenarios passed, ', count($failures), ' failed; actual local HTTP and MySQL.', PHP_EOL;
exit($failures ? 1 : 0);
