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
// Transactional order alerts must include thumbnails in both themes, without per-item image SQL.
$mailController = file_get_contents(dirname(__DIR__) . '/upload/catalog/controller/mail/order.php');
if (strpos($mailController, 'SELECT product_id, image FROM') === false ||
    strpos($mailController, "'image'    => \$alert_thumb") === false ||
    strpos($mailController, "'href'     => \$alert_href") === false) {
    throw new RuntimeException('Order alert thumbnails must be resolved in one batch');
}
foreach (array('default', 'codecart') as $theme) {
    $template = file_get_contents(dirname(__DIR__) . '/upload/catalog/view/theme/' . $theme . '/template/mail/order_alert_html.twig');
    if (strpos($template, 'product.image') === false ||
        strpos($template, 'width="56" height="56"') === false) {
        throw new RuntimeException($theme . ' order alert lacks compact product images');
    }
}
// Customer email must not repeat identical billing/delivery addresses or expose IP details.
if (strpos($mailController, "\$data['same_address'] = \$same_address;") === false) {
    throw new RuntimeException('Order email lacks duplicate-address comparison');
}
foreach (array('default', 'codecart') as $theme) {
    $customerTemplate = file_get_contents(dirname(__DIR__) . '/upload/catalog/view/theme/' . $theme . '/template/mail/order_add.twig');
    if (strpos($customerTemplate, 'same_address or not payment_address or not shipping_address') === false ||
        strpos($customerTemplate, '{{ ip }}') !== false ||
        strpos($customerTemplate, 'product.image') === false) {
        throw new RuntimeException($theme . ' customer order mail must use one address, avoid IP and show product photos');
    }
}
// Regression: prepared email logos must not be centered inside a fixed
// 600x160 white rectangle. Both white-background and transparent logos
// should produce tight, correctly proportioned image assets.
if (function_exists('imagecreatetruecolor') && function_exists('imagepng')) {
    require dirname(__DIR__) . '/upload/system/library/codecart/src/EmailLogo.php';
    $mailLogoDir = sys_get_temp_dir() . '/codecart-logo-' . bin2hex(random_bytes(5)) . '/';
    if (!mkdir($mailLogoDir, 0700, true) && !is_dir($mailLogoDir)) {
        throw new RuntimeException('Cannot create email logo smoke temporary folder');
    }
    define('DIR_IMAGE', $mailLogoDir);

    $whiteImage = imagecreatetruecolor(320, 100);
    imagefill($whiteImage, 0, 0, imagecolorallocate($whiteImage, 255, 255, 255));
    imagefilledrectangle($whiteImage, 110, 30, 210, 70, imagecolorallocate($whiteImage, 11, 102, 202));
    imagepng($whiteImage, $mailLogoDir . 'white.png');
    imagedestroy($whiteImage);

    $logo = \CodeCart\Core\EmailLogo::prepare('white.png', 'https://example.test');
    $logoFile = $mailLogoDir . str_replace('https://example.test/image/', '', $logo['url']);
    $size = @getimagesize($logoFile);
    if (!$size || $size[0] > 180 || $size[1] > 75 ||
        $logo['width'] > 300 || $logo['height'] > 80 ||
        ($logo['width'] < 290 && $logo['height'] < 78) ||
        abs(($logo['width'] / $logo['height']) - ($size[0] / $size[1])) > 0.05) {
        throw new RuntimeException('Email logo must crop whitespace then fit its original aspect ratio in the slot');
    }

    $transparentImage = imagecreatetruecolor(320, 100);
    imagealphablending($transparentImage, false);
    imagesavealpha($transparentImage, true);
    imagefill($transparentImage, 0, 0, imagecolorallocatealpha($transparentImage, 255, 255, 255, 127));
    imagefilledrectangle($transparentImage, 110, 30, 150, 70, imagecolorallocatealpha($transparentImage, 13, 18, 25, 0));
    imagefilledrectangle($transparentImage, 151, 30, 210, 70, imagecolorallocatealpha($transparentImage, 11, 102, 202, 0));
    imagepng($transparentImage, $mailLogoDir . 'transparent.png');
    imagedestroy($transparentImage);

    $transparent = \CodeCart\Core\EmailLogo::prepare('transparent.png', 'https://example.test');
    $transparentFile = $mailLogoDir . str_replace('https://example.test/image/', '', $transparent['url']);
    $size = @getimagesize($transparentFile);
    if (!$size || $size[0] > 180 || $size[1] > 75) {
        throw new RuntimeException('Transparent email logo retains excessive whitespace');
    }
    // Pixels outside the artwork must remain transparent on both PNG variants.
    if (empty($transparent['dark_url'])) {
        throw new RuntimeException('Missing adaptive dark-mode email logo');
    }
    $darkFile = $mailLogoDir . str_replace('https://example.test/image/', '', $transparent['dark_url']);
    $lightPng = @imagecreatefrompng($transparentFile);
    $darkPng = @imagecreatefrompng($darkFile);
    if (!$lightPng || !$darkPng) {
        throw new RuntimeException('Email logo variants are not readable PNG images');
    }
    $edgeLight = imagecolorat($lightPng, 0, 0);
    $edgeDark = imagecolorat($darkPng, 0, 0);
    if ((($edgeLight >> 24) & 127) < 120 || (($edgeDark >> 24) & 127) < 120) {
        throw new RuntimeException('Email logo conversion painted an opaque backing over transparency');
    }
    $blackOriginal = imagecolorat($lightPng, 20, 20);
    $blackDark = imagecolorat($darkPng, 20, 20);
    $blueDark = imagecolorat($darkPng, 90, 20);
    if ((($blackOriginal >> 16) & 255) > 80 ||
        (($blackDark >> 16) & 255) < 220 ||
        (($blueDark >> 16) & 255) > 50) {
        throw new RuntimeException('Dark-mode logo must brighten dark text and preserve brand blue');
    }
    imagedestroy($lightPng);
    imagedestroy($darkPng);
    foreach (array('default', 'codecart') as $theme) {
        foreach (array('affiliate_alert_html.twig', 'affiliate_html.twig', 'forgotten_html.twig',
            'order_add.twig', 'order_alert_html.twig', 'order_edit_html.twig',
            'register_alert_html.twig', 'register_html.twig', 'transaction_html.twig') as $mailFile) {
            $mailHtml = file_get_contents(dirname(__DIR__) . '/upload/catalog/view/theme/' . $theme . '/template/mail/' . $mailFile);
            if (strpos($mailHtml, 'ccp-logo-frame') === false ||
                strpos($mailHtml, 'logo_dark') === false) {
                throw new RuntimeException($theme . '/' . $mailFile . ' lacks a bounded adaptive logo');
            }
        }
        $orderTemplate = file_get_contents(dirname(__DIR__) . '/upload/catalog/view/theme/' . $theme . '/template/mail/order_add.twig');
        if (strpos($orderTemplate, 'ccp-logo-frame') === false ||
            strpos($orderTemplate, 'logo_dark') === false ||
            strpos($orderTemplate, 'prefers-color-scheme:dark') === false) {
            throw new RuntimeException('Order emails must keep the logo slot and theme-aware variant');
        }
    }
    @unlink($logoFile);
    @unlink($transparentFile);
    @unlink($darkFile);
    @unlink($mailLogoDir . str_replace('https://example.test/image/', '', $logo['dark_url'] ?? ''));
    @unlink($mailLogoDir . 'white.png');
    @unlink($mailLogoDir . 'transparent.png');
    @rmdir($mailLogoDir . 'cache/email');
    @rmdir($mailLogoDir . 'cache');
    @rmdir($mailLogoDir);
}

echo "Mail Audit restore: PASS\n";
