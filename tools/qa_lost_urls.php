<?php
$root = dirname(__DIR__);
$file = $root . '/upload/system/library/codecart/src/LostUrlMonitor.php';
if (!is_file($file)) { fwrite(STDERR, "FAIL: lost URL monitor is missing\n"); exit(1); }
require $file;
function lostAssert($condition, $message) { if (!$condition) { fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL); exit(1); } }
$server = array('REQUEST_METHOD' => 'GET', 'HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome/120 Safari/537.36', 'REQUEST_URI' => '/ua/old-tractor?utm_source=mail&token=secret');
$item = \CodeCart\Core\LostUrlMonitor::candidate($server, array(), 'https://shop.test/');
lostAssert($item && $item['url'] === '/ua/old-tractor' && $item['slug'] === 'old-tractor' && $item['bot'] === false, 'clean meaningful SEO URL without tracking or secrets');
foreach (array('/.env','/wp-login.php','/administrator/index.php','/assets/app.js','/image/missing.jpg','/api/test','/abc%00def','/old%3Cscript%3E','/a','/robots.txt','/favicon.ico','/../old-product') as $uri) {
    $s = $server; $s['REQUEST_URI'] = $uri;
    lostAssert(\CodeCart\Core\LostUrlMonitor::candidate($s, array(), 'https://shop.test/') === null, 'reject technical/scanner URI ' . $uri);
}
foreach (array('POST','HEAD') as $method) { $s=$server; $s['REQUEST_METHOD']=$method; lostAssert(\CodeCart\Core\LostUrlMonitor::candidate($s,array(),'https://shop.test/')===null,'ignore non-page method'); }
$s=$server;$s['HTTP_X_REQUESTED_WITH']='XMLHttpRequest';lostAssert(\CodeCart\Core\LostUrlMonitor::candidate($s,array(),'https://shop.test/')===null,'ignore AJAX');
$s=$server;$s['HTTP_USER_AGENT']='Googlebot/2.1';lostAssert(\CodeCart\Core\LostUrlMonitor::candidate($s,array(),'https://shop.test/')['bot']===true,'classify declared bot without claiming verification');
$s=$server;$s['REQUEST_URI']='/index.php?route=information/uni_news&news_id=18&user_token=secret';
$item=\CodeCart\Core\LostUrlMonitor::candidate($s,array(),'https://shop.test/');
lostAssert($item && $item['kind']==='article' && strpos($item['url'],'news_id=18')!==false && strpos($item['url'],'secret')===false,'UniShop news URL without secrets');
$s=$server;$s['REQUEST_URI']='/index.php?route=information/uni_news_story&news_id=18&news_path=1_2';
$item=\CodeCart\Core\LostUrlMonitor::candidate($s,array(),'https://shop.test/');
lostAssert($item && $item['kind']==='article','actual UniShop2 3.6.6.0 article route');
foreach (array(30,60,90) as $days) { lostAssert(\CodeCart\Core\LostUrlMonitor::retentionDays($days)===$days,'supported retention'); }
foreach (array(0,365,-1,array(30)) as $days) { lostAssert(\CodeCart\Core\LostUrlMonitor::retentionDays($days)===60,'safe bounded retention fallback'); }
foreach (array('/da/index.php','/da/') as $path) {
    $s=$server;$s['REQUEST_URI']=$path.'?route=blog/article&article_id=18';
    $item=\CodeCart\Core\LostUrlMonitor::candidate($s,array(),'https://shop.test/','da');
    lostAssert($item && $item['kind']==='article' && strpos($item['url'],$path.'?')===0,'prefixed query URL keeps original path');
}
$s=$server;$s['HTTP_REFERER']='https://shop.test/category?token=secret#private';$item=\CodeCart\Core\LostUrlMonitor::candidate($s,array(),'https://shop.test/');
lostAssert($item['referer']==='https://shop.test/category' && $item['referred']===true,'safe internal navigation evidence');
$s=$server;$s['HTTP_REFERER']='https://random-spam.test/path';lostAssert(\CodeCart\Core\LostUrlMonitor::candidate($s,array(),'https://shop.test/')['referred']===false,'untrusted referrer alone must not promote spam');
class LostConfig { function get($key){return 0;} }
class LostRegistry { function get($key){ if($key==='config')return new LostConfig();throw new RuntimeException('OFF accessed service '.$key); } }
$monitor=new \CodeCart\Core\LostUrlMonitor(new LostRegistry());
$monitor->record($server,array());lostAssert($monitor->redirect($server)===null,'OFF redirect is inactive');
$monitor->prune();
require $root . '/upload/system/library/response.php';
$response=new Response();lostAssert($response->getStatusCode()===200,'default response status');
$response->addHeader('HTTP/1.1 404 Not Found');lostAssert($response->getStatusCode()===404,'legacy UniShop/OpenCart status header');
$response->addHeader('HTTP/1.1 200 OK');lostAssert($response->getStatusCode()===200,'latest effective status header');
echo "PASS: lost URL noise filters, UniShop contract, privacy and zero-work OFF\n";
