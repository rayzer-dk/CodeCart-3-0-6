<?php
/**
 * Offline QA for notification UI URLs in OpenCart's HTML-escaped Url::link() environment.
 * No database connection, user credentials, or live requests are required.
 */
$root = dirname(__DIR__);
require_once $root . '/upload/system/storage/vendor/autoload.php';
require_once $root . '/upload/system/library/url.php';

function ccNoticeAssert($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

$controller = file_get_contents($root . '/upload/admin/controller/tool/notification.php');
ccNoticeAssert(substr_count($controller, 'html_entity_decode(') >= 4, 'OpenCart URL encoding must be normalized before Twig escaping');
$url = new Url('https://example.com/admin/', 'https://example.com/admin/');
$token = 'TestSessionToken12345';
$make = function($route) use ($url, $token) {
    return html_entity_decode($url->link($route, 'user_token=' . $token, true), ENT_QUOTES, 'UTF-8');
};
$counts = array('all'=>6, 'unread'=>2, 'read'=>3, 'resolved'=>1, 'dismissed'=>4);
$data = array(
    'header'=>'', 'column_left'=>'', 'footer'=>'', 'can_modify'=>true,
    'heading_title'=>'Notifications', 'text_list'=>'Notifications',
    'text_read_all'=>'Read all', 'text_empty'=>'Empty', 'counts'=>$counts,
    'selected_status'=>'all', 'notifications'=>array(), 'pages'=>3, 'page'=>1,
    'user_token'=>$token, 'notification_list_url'=>$make('tool/notification'),
    'notification_urls'=>array('read'=>$make('tool/notification/read'), 'readAll'=>$make('tool/notification/readAll'), 'delete'=>$make('tool/notification/delete'), 'clearRead'=>$make('tool/notification/clearRead')),
    'text_status_all'=>'All', 'text_status_unread'=>'New', 'text_status_read'=>'Read',
    'text_status_resolved'=>'Resolved', 'text_status_dismissed'=>'Archive',
    'text_archive_help'=>'History', 'text_level_critical'=>'Critical',
    'text_level_error'=>'Error', 'text_level_warning'=>'Warning', 'text_level_info'=>'Info',
    'text_delete'=>'Hide', 'text_read'=>'Read', 'text_clear_read'=>'Clear',
    'text_confirm_delete'=>'Confirm', 'text_confirm_clear'=>'Confirm', 'text_page'=>'Page',
    'text_previous'=>'Prev', 'text_next'=>'Next', 'error_action'=>'Error',
    'error_network'=>'Connection failed', 'error_session'=>'Sign in again',
    'breadcrumbs'=>array(array('href'=>$make('common/dashboard'), 'text'=>'Dashboard'))
);
$twig = new \Twig\Environment(
    new \Twig\Loader\FilesystemLoader($root . '/upload/admin/view/template'),
    array('autoescape'=>false, 'cache'=>false)
);
$html = $twig->render('tool/notification.twig', $data);
ccNoticeAssert(strpos($html,'&amp;amp;') === false, 'Double-escaped query separator in href');
ccNoticeAssert(strpos($html,'amp;user_token') === false || strpos($html,'&amp;user_token') !== false, 'Invalid query encoding');
ccNoticeAssert(strpos($html, 'status=dismissed') !== false, 'Archive filter missing');
ccNoticeAssert(strpos($html, 'page=2') !== false, 'Pagination missing');
ccNoticeAssert(strpos($html, '\\u0026') !== false, 'JavaScript-escaped query separator missing');
$script = explode('</script>', explode('<script>', $html, 2)[1], 2)[0];
ccNoticeAssert(strpos($script, '&amp;') === false, 'HTML entities must not be included in AJAX URL');
$data['selected_status'] = 'dismissed';
$data['pages'] = 1;
$data['notifications'] = array(array('severity'=>'warning','status'=>'dismissed','title'=>'Archived','message'=>'Notice','date_modified'=>'2026-10-10','route'=>'','notification_id'=>11));
$archived = $twig->render('tool/notification.twig',$data);
ccNoticeAssert(strpos($archived,'ccp-notification-card-actions') === false,'Archived notification must not offer Delete again');
echo "Notification HTML, navigation URL, JS URL, archive and pagination QA passed.\n";
