<?php
namespace CodeCart\Core;

/**
 * Branded, responsive transactional-style shell for permissioned marketing campaigns.
 * Inner message HTML remains editable. Server-side preview and queued emails use
 * the same render function, so what merchants see is what recipients receive.
 */
final class NewsletterEmail {
    private static function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function presets($locale) {
        $lang = strtolower(substr((string)$locale, 0, 2));
        $strings = array(
            'uk' => array(
                'news' => array('Новини магазину', 'Новини та корисні оновлення від {{ store_name }}', 'Останні новини', 'Вітаємо, {{ customer_name }}!', 'Ділимося важливими новинами, добірками та корисною інформацією нашого магазину.', 'Завітайте до нас, щоб дізнатися більше.'),
                'offer' => array('Спеціальна пропозиція', 'Спеціальна пропозиція від {{ store_name }}', 'Пропозиція для вас', 'Вітаємо, {{ customer_name }}!', 'Ми підготували для вас добірку цікавих пропозицій. Оберіть те, що підходить саме вам.', 'Актуальні товари й умови дивіться в магазині.'),
                'new' => array('Нові надходження', 'Новинки в {{ store_name }}', 'Щойно з’явилося', 'Вітаємо, {{ customer_name }}!', 'Оновили асортимент! Перегляньте нові товари та знайдіть рішення для своїх завдань.', 'Відкрийте каталог за посиланням нижче.'),
                'info' => array('Інформаційне повідомлення', 'Важлива інформація від {{ store_name }}', 'Важливе повідомлення', 'Вітаємо, {{ customer_name }}!', 'Хочемо повідомити вам важливу інформацію щодо роботи нашого магазину.', 'Деталі завжди доступні на нашому сайті.')
            ),
            'ru' => array(
                'news' => array('Новости магазина', 'Новости и полезные обновления от {{ store_name }}', 'Последние новости', 'Здравствуйте, {{ customer_name }}!', 'Делимся новостями, подборками и полезной информацией нашего магазина.', 'Загляните к нам, чтобы узнать больше.'),
                'offer' => array('Специальное предложение', 'Специальное предложение от {{ store_name }}', 'Предложение для вас', 'Здравствуйте, {{ customer_name }}!', 'Мы подготовили подборку интересных предложений. Выберите то, что подходит именно вам.', 'Актуальные товары и условия смотрите в магазине.'),
                'new' => array('Новые поступления', 'Новинки в {{ store_name }}', 'Только что появилось', 'Здравствуйте, {{ customer_name }}!', 'Обновили ассортимент! Посмотрите новые товары и найдите решения для своих задач.', 'Откройте каталог по ссылке ниже.'),
                'info' => array('Информационное сообщение', 'Важная информация от {{ store_name }}', 'Важное сообщение', 'Здравствуйте, {{ customer_name }}!', 'Хотим сообщить важную информацию о работе нашего магазина.', 'Подробности всегда доступны на нашем сайте.')
            ),
            'en' => array(
                'news' => array('Store news', 'News and helpful updates from {{ store_name }}', 'Latest updates', 'Hello {{ customer_name }}!', 'Here are the latest updates, ideas and useful information from our store.', 'Visit us to learn more.'),
                'offer' => array('Special offer', 'A special offer from {{ store_name }}', 'Selected for you', 'Hello {{ customer_name }}!', 'We have put together a selection of offers worth exploring.', 'See current products and details in our store.'),
                'new' => array('New arrivals', 'What is new at {{ store_name }}', 'Fresh arrivals', 'Hello {{ customer_name }}!', 'Discover our latest additions and find solutions that fit your needs.', 'Explore the catalog below.'),
                'info' => array('Store announcement', 'Important information from {{ store_name }}', 'Important update', 'Hello {{ customer_name }}!', 'We would like to share important information about our store.', 'Find the latest details on our website.')
            )
        );
        if (!isset($strings[$lang])) { $lang = 'en'; }
        $result = array();
        foreach ($strings[$lang] as $id => $parts) {
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
        $labels = array(
            'uk' => array('visit'=>'Відвідати магазин','goodbye'=>'З повагою, команда','optout'=>'Відмовитися від розсилки','reason'=>'Ви отримали цей лист від магазину'),
            'ru' => array('visit'=>'Перейти в магазин','goodbye'=>'С уважением, команда','optout'=>'Отписаться от рассылки','reason'=>'Вы получили это письмо от магазина'),
            'en' => array('visit'=>'Visit our store','goodbye'=>'Kind regards, the team at','optout'=>'Unsubscribe from these emails','reason'=>'You received this email from')
        );
        $words = isset($labels[$locale]) ? $labels[$locale] : $labels['en'];
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
        $header = $logo !== '' ? $logo . '<div style="margin-top:9px;color:#e1edff;font-size:15px;">' . $nameSafe . '</div>' : '<div style="font-size:25px;line-height:1.2;font-weight:700;color:#fff;">' . $nameSafe . '</div>';
        $cta = $storeUrl !== '' ? '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-top:26px;"><tr><td bgcolor="#0969da" style="border-radius:7px;"><a href="' . self::h($storeUrl) . '" style="display:inline-block;padding:13px 22px;border-radius:7px;font-size:15px;font-weight:700;color:#fff;text-decoration:none;">' . self::h($words['visit']) . '</a></td></tr></table>' : '';
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
            . '<tr><td class="cc-mail-brand" bgcolor="#102d56" style="background:#102d56;padding:28px 36px;">' . $header . '</td></tr>'
            . '<tr><td class="cc-mail-content" style="padding:34px 38px 30px;font-size:16px;line-height:1.7;color:#24364d;">' . $body . $cta . $signature . '</td></tr>'
            . '<tr><td class="cc-mail-footer" bgcolor="#f7f9fc" style="padding:24px 38px;background:#f7f9fc;border-top:1px solid #e4eaf3;text-align:center;color:#65768a;font-size:12px;line-height:1.7;">'
            . '<p style="margin:0 0 8px;">' . self::h($words['reason']) . ' <strong>' . $nameSafe . '</strong>.</p>'
            . $contact . $optout . '<p style="margin:13px 0 0;">&copy; ' . date('Y') . ' ' . $nameSafe . '</p></td></tr></table>'
            . '</td></tr></table></body></html>';
    }
}
