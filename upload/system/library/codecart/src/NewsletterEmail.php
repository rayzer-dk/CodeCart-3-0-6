<?php
namespace CodeCart\Core;

/**
 * Branded, responsive transactional-style shell for permissioned marketing campaigns.
 * Inner message HTML remains editable. Server-side preview and queued emails use
 * the same render function, so what merchants see is what recipients receive.
 */
final class NewsletterEmail {
    /** Shared UTF-8 translation source for previews and queued deliveries. */
    private static function translations() {
        static $translations = null;
        if ($translations === null) {
            $path = dirname(__DIR__, 3) . '/config/codecart_newsletter_text.json';
            $raw = @file_get_contents($path);
            $decoded = $raw !== false ? json_decode($raw, true) : null;
            if (!is_array($decoded) || !isset($decoded['en']['presets'], $decoded['en']['labels'])) {
                throw new \RuntimeException('Newsletter translation file unavailable.');
            }
            $translations = $decoded;
        }
        return $translations;
    }

    private static function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Accepts only #rrggbb so a stored colour can never carry CSS or markup. */
    public static function color($value, $default) {
        $value = strtolower(trim((string)$value));
        return preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : $default;
    }

    /** Black or white text, whichever reads better on the given background. */
    private static function textOn($hex) {
        $r = hexdec(substr($hex, 1, 2)); $g = hexdec(substr($hex, 3, 2)); $b = hexdec(substr($hex, 5, 2));
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 160 ? '#172b4d' : '#ffffff';
    }

    public static function presets($locale) {
        $lang = strtolower(substr((string)$locale, 0, 2));
        $translations = self::translations();
        if (!isset($translations[$lang])) { $lang = 'en'; }
        $strings = $translations[$lang]['presets'];
        $result = array();
        foreach ($strings as $id => $parts) {
            $message = '<h2 style="margin:0 0 18px;font:700 25px/1.3 Arial,sans-serif;color:#172b4d;">'
                . self::h($parts[2]) . '</h2><p style="margin:0 0 16px;line-height:1.7;">'
                . self::h($parts[3]) . '</p><p style="margin:0 0 16px;line-height:1.7;">'
                . self::h($parts[4]) . '</p><p style="margin:0;line-height:1.7;color:#52647c;">'
                . self::h($parts[5]) . '</p>';
            $templateId = 'builtin_' . $id;
            $result[$templateId] = array(
                'id' => $templateId, 'name' => $parts[0], 'subject' => $parts[1],
                'message' => $message, 'html_encoding' => 'raw', 'built_in' => true
            );
        }
        return $result;
    }

    /**
     * The configured logo is a relative path under image/. Reject absolute URLs
     * and traversal; URL-build each path component separately.
     */
    public static function logoUrl($baseUrl, $relativePath) {
        $baseUrl = rtrim((string)$baseUrl, '/');
        $relativePath = str_replace('\\', '/', trim((string)$relativePath));
        if (!preg_match('~^https?://[^/]+~i', $baseUrl)
            || $relativePath === '' || $relativePath[0] === '/'
            || strpos($relativePath, '..') !== false || strpos($relativePath, ':') !== false) {
            return '';
        }
        $parts = explode('/', $relativePath);
        foreach ($parts as $part) {
            if ($part === '' || !preg_match('/^[\pL\pN_ .()\-]+$/u', $part)) { return ''; }
        }
        return $baseUrl . '/image/' . implode('/', array_map('rawurlencode', $parts));
    }

    public static function render($subject, $messageBody, array $variables, array $brand = array()) {
        $locale = strtolower(substr(isset($brand['locale']) ? (string)$brand['locale'] : 'en', 0, 2));
        $translations = self::translations();
        $words = isset($translations[$locale]['labels']) ? $translations[$locale]['labels'] : $translations['en']['labels'];
        $storeName = isset($brand['name']) ? (string)$brand['name'] : (isset($variables['store_name']) ? (string)$variables['store_name'] : '');
        $storeUrl = isset($brand['url']) ? (string)$brand['url'] : (isset($variables['store_url']) ? (string)$variables['store_url'] : '');
        $logoUrl = isset($brand['logo']) ? (string)$brand['logo'] : '';
        $email = isset($brand['email']) ? (string)$brand['email'] : '';
        $unsubscribe = isset($variables['unsubscribe_url']) ? (string)$variables['unsubscribe_url'] : '';
        if (!filter_var($storeUrl, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($storeUrl, PHP_URL_SCHEME)), array('http','https'), true)) { $storeUrl = ''; }
        if (!filter_var($logoUrl, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($logoUrl, PHP_URL_SCHEME)), array('http','https'), true)) { $logoUrl = ''; }
        if (!filter_var($unsubscribe, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($unsubscribe, PHP_URL_SCHEME)), array('http','https'), true)) { $unsubscribe = ''; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $email = ''; }

        $replacements = array();
        foreach (array('customer_name','store_name','store_url','unsubscribe_url') as $key) {
            $value = isset($variables[$key]) ? (string)$variables[$key] : '';
            $replacements['{{ ' . $key . ' }}'] = self::h($value);
        }
        $body = (string)$messageBody;
        if (strpos($body, '<') === false) {
            $body = nl2br(self::h($body));
        }
        $body = strtr($body, $replacements);

        $nameSafe = self::h($storeName);
        $logo = $logoUrl !== '' ? '<img src="' . self::h($logoUrl) . '" alt="' . $nameSafe . '" width="160" style="display:block;max-width:160px;max-height:56px;height:auto;border:0;" />' : '';
        $headerColor = self::color(isset($brand['header_color']) ? $brand['header_color'] : '', '#102d56');
        $buttonColor = self::color(isset($brand['button_color']) ? $brand['button_color'] : '', '#0969da');
        $headerText = self::textOn($headerColor);
        $buttonText = self::textOn($buttonColor);
        $header = $logo !== '' ? $logo . '<div style="margin-top:9px;color:' . $headerText . ';font-size:15px;">' . $nameSafe . '</div>' : '<div style="font-size:25px;line-height:1.2;font-weight:700;color:' . $headerText . ';">' . $nameSafe . '</div>';
        $cta = $storeUrl !== '' ? '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-top:26px;"><tr><td bgcolor="' . $buttonColor . '" style="border-radius:7px;"><a href="' . self::h($storeUrl) . '" style="display:inline-block;padding:13px 22px;border-radius:7px;font-size:15px;font-weight:700;color:' . $buttonText . ';text-decoration:none;">' . self::h($words['visit']) . '</a></td></tr></table>' : '';
        $signature = '<p style="margin:28px 0 0;color:#53657d;font-size:14px;line-height:1.5;">' . self::h($words['goodbye']) . '<br /><strong style="color:#172b4d;">' . $nameSafe . '</strong></p>';
        $site = $storeUrl !== '' ? '<a style="color:#5b7290;text-decoration:underline;" href="' . self::h($storeUrl) . '">' . self::h((string)parse_url($storeUrl, PHP_URL_HOST)) . '</a>' : '';
        $mail = $email !== '' ? '<a href="mailto:' . self::h($email) . '" style="color:#5b7290;text-decoration:underline;">' . self::h($email) . '</a>' : '';
        $contact = implode(' &nbsp;·&nbsp; ', array_filter(array($site, $mail)));
        $optout = $unsubscribe !== '' ? '<p style="margin:16px 0 0;"><a href="' . self::h($unsubscribe) . '" style="color:#1464bd;text-decoration:underline;">' . self::h($words['optout']) . '</a></p>' : '';
        return '<!DOCTYPE html><html lang="' . self::h($locale) . '"><head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width,initial-scale=1" />'
            . '<title>' . self::h($subject) . '</title><style>@media only screen and (max-width:620px){.cc-mail-shell{width:100%!important;max-width:100%!important}.cc-mail-content{padding:25px 22px!important}.cc-mail-brand{padding:24px 22px!important}.cc-mail-footer{padding:22px!important}}</style></head>'
            . '<body style="margin:0;padding:0;background-color:#f0f4f9;color:#24364d;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#f0f4f9" style="width:100%;background:#f0f4f9;"><tr><td align="center" style="padding:30px 12px;">'
            . '<table role="presentation" class="cc-mail-shell" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;border-collapse:separate;border-spacing:0;background:#fff;border:1px solid #e1e9f1;border-radius:12px;overflow:hidden;">'
            . '<tr><td class="cc-mail-brand" bgcolor="' . $headerColor . '" style="background:' . $headerColor . ';padding:28px 36px;">' . $header . '</td></tr>'
            . '<tr><td class="cc-mail-content" style="padding:34px 38px 30px;font-size:16px;line-height:1.7;color:#24364d;">' . $body . $cta . $signature . '</td></tr>'
            . '<tr><td class="cc-mail-footer" bgcolor="#f7f9fc" style="padding:24px 38px;background:#f7f9fc;border-top:1px solid #e4eaf3;text-align:center;color:#65768a;font-size:12px;line-height:1.7;">'
            . '<p style="margin:0 0 8px;">' . self::h($words['reason']) . ' <strong>' . $nameSafe . '</strong>.</p>'
            . $contact . $optout . '<p style="margin:13px 0 0;">&copy; ' . date('Y') . ' ' . $nameSafe . '</p></td></tr></table>'
            . '</td></tr></table></body></html>';
    }
}
