<?php

$_['entry_noindex_status'] = 'Управление noindex';
$_['help_noindex_status'] = 'Рекомендуется включить. Система применяет noindex к объектам, где параметр «Индексация» отключён, а также к техническим SEO-дублям по активным правилам.';

$_['entry_internal_linking'] = 'Внутренняя перелинковка';
$_['help_internal_linking'] = 'Показывает связанные ссылки из существующих связей категорий, производителей, товаров и блога. Текст описаний автоматически не изменяется.';

$_['entry_cache_engine'] = 'Механизм кеша';
$_['help_cache_engine'] = 'File работает везде. APCu, Memcached и Redis доступны только если соответствующее PHP-расширение и сервис активны в WEB/FPM.';
$_['help_cache_engine_note'] = 'Это не сжатие. Для одного PHP-сервера рекомендуется APCu; для общего кеша между несколькими процессами/серверами — Redis. Если выбранный backend недоступен, ядро безопасно переходит на File Cache. Глобальная очистка APCu/Redis/Memcached в CodeCart PRO выполняется через generation namespace за O(1).';

// CodeCart PRO configurable checkout fields
$_['entry_checkout_fields'] = 'Необязательные поля заказа';
$_['help_checkout_fields'] = 'Выберите второстепенные поля, которые показываются в стандартном оформлении заказа.';
$_['help_checkout_fields_note'] = 'Критичные поля доставки оставлены включёнными для совместимости. Отключённые поля скрываются в гостевом оформлении, регистрации и адресах и сохраняются пустыми. После изменения проверьте модули доставки, оплаты, CRM и ERP.';
$_['entry_checkout_field_lastname'] = 'Фамилия';
$_['help_checkout_field_lastname'] = 'Отключайте только если доставка, оплата и CRM не требуют фамилию.';
$_['entry_checkout_field_telephone'] = 'Телефон';
$_['help_checkout_field_telephone'] = 'Отключайте только если доставка, оплата и CRM не требуют номер телефона.';
$_['entry_checkout_field_company'] = 'Компания';
$_['help_checkout_field_company'] = 'Необязательное поле компании или организации.';
$_['entry_checkout_field_address_2'] = 'Адрес 2';
$_['help_checkout_field_address_2'] = 'Необязательная вторая строка адреса.';
$_['entry_checkout_field_postcode'] = 'Почтовый индекс';
$_['help_checkout_field_postcode'] = 'Отключайте только если используемые способы доставки не требуют индекс.';

$_['entry_email_logo'] = 'Логотип в письмах';
$_['help_email_logo'] = 'Рекомендовано: PNG или JPEG, 600×160 px (2× для Retina). В письме логотип автоматически адаптируется максимум до 300×80 px и подготавливается в безопасном для почтовых клиентов формате. WebP можно использовать как исходный файл — повторно загружать его не требуется.';
$_['entry_apple_touch_icon'] = 'Иконка Apple Touch';
$_['help_apple_touch_icon'] = 'Необязательная иконка для домашнего экрана Apple. Рекомендуется PNG 180×180.';
$_['entry_catalog_fallback_image'] = 'Резервное изображение каталога';
$_['help_catalog_fallback_image'] = 'Необязательное изображение для отсутствующих файлов каталога. Оставьте пустым для стандартного поведения.';

$_['entry_storefront_buy_button_color'] = 'Цвет кнопки «Купить»';
$_['entry_storefront_buy_button_hover'] = 'Цвет «Купить» при наведении';
$_['entry_storefront_buy_button_text_color'] = 'Цвет текста кнопки «Купить»';

$_['help_storefront_buy_button_color'] = 'Отдельный цвет фона главной кнопки «Купить» на страницах товаров.';
$_['help_storefront_buy_button_hover'] = 'Цвет кнопки «Купить» при наведении, фокусе и после успешного добавления в корзину.';
$_['help_storefront_buy_button_text_color'] = 'Цвет текста и иконки главной кнопки «Купить».';
$_['entry_social_preview_image'] = 'Изображение для социального превью';
$_['help_social_preview_image'] = 'Необязательное стандартное изображение Open Graph/Twitter, если у текущей страницы нет собственного. Рекомендуется 1200×630 JPG/PNG/WebP.';

$_['text_cache_extension_unavailable'] = 'PHP-расширение недоступно';

$_['entry_storefront_sale_price_color'] = 'Цвет акционной цены';
$_['entry_storefront_cart_button_color'] = 'Цвет кнопки товаров / корзины';
$_['entry_storefront_cart_button_hover'] = 'Цвет кнопки товаров при наведении';
$_['entry_storefront_cart_button_text_color'] = 'Цвет текста кнопки товаров';
$_['help_storefront_sale_price_color'] = 'Цвет акционной цены в стандартных карточках и на странице товара.';
$_['help_storefront_cart_button_color'] = 'Фон большой кнопки товаров / корзины в шапке стандартной темы.';
$_['help_storefront_cart_button_hover'] = 'Цвет этой кнопки при наведении и фокусе.';
$_['help_storefront_cart_button_text_color'] = 'Цвет текста и иконки этой кнопки.';
$_['entry_compression'] = 'PHP GZIP: уровень сжатия';
$_['help_compression'] = 'Уровень 0–9. HTTPS не является методом сжатия. Если nginx, Apache, Cloudflare или CDN уже возвращает Content-Encoding gzip, br или zstd, оставьте PHP GZIP = 0, чтобы не тратить CPU повторно.';

// CodeCart PRO digital checkout profile
$_['entry_digital_checkout'] = 'Цифровые товары';
$_['entry_digital_checkout_status'] = 'Упрощённое оформление цифровых товаров';
$_['help_digital_checkout'] = 'Отдельный профиль оформления для корзины с загружаемыми товарами, которым не требуется доставка.';
$_['help_digital_checkout_note'] = 'По умолчанию выключено. Если включено и в корзине только цифровые загрузки (Необходима доставка = Нет), показываются только выбранные ниже поля. Страна и регион остаются обязательными для совместимости с налогами и оплатой. Для цифровых товаров требуется аккаунт, а файлы становятся доступны только после перехода заказа в статус Завершено.';
$_['entry_checkout_field_address_1'] = 'Адрес 1';
$_['entry_checkout_field_city'] = 'Город';

// CodeCart PRO RC63: быстрое оформление и гибкие поля
$_['entry_quick_checkout_status'] = 'Быстрое оформление';
$_['help_quick_checkout_status'] = 'Объединяет корзину и гостевое оформление на одной компактной странице. Legacy checkout остаётся fallback для несовместимых сценариев.';
$_['entry_checkout_email_fallback'] = 'Служебный E-Mail';
$_['help_checkout_email_fallback'] = 'Подставляется в заказ, если E-Mail скрыт или необязателен и покупатель его не указал. На этот адрес письмо покупателя не отправляется.';
$_['text_checkout_required'] = 'Обязательное';
$_['text_checkout_optional'] = 'Необязательное';
$_['text_checkout_hidden'] = 'Скрытое';
$_['text_checkout_custom_labels'] = 'Название поля';
$_['column_checkout_field'] = 'Поле';
$_['column_checkout_mode'] = 'Показ / обязательность';
$_['column_checkout_label'] = 'Подпись на витрине';
$_['entry_checkout_field_firstname'] = 'Имя';
$_['entry_checkout_field_email'] = 'E-Mail';
$_['entry_checkout_field_country'] = 'Страна';
$_['entry_checkout_field_zone'] = 'Регион / Область';

$_['entry_cookie_icon'] = 'Иконка управления cookies';
$_['entry_cookie_custom_icon'] = 'Своя иконка cookies';
$_['help_cookie_icon'] = 'Выберите встроенную иконку для кнопки повторного открытия баннера cookies или используйте своё изображение.';
$_['help_cookie_custom_icon'] = 'Используется, когда выше выбран вариант «Своя иконка». Предпочтительно PNG/WebP/SVG с прозрачным фоном.';
$_['text_cookie_icon_shield_cookie_check'] = 'Щит с cookie и галочкой';
$_['text_cookie_icon_shield_lock_check'] = 'Щит с замком и галочкой';
$_['text_cookie_icon_lock_circle_check'] = 'Круглый замок с галочкой';
$_['text_cookie_icon_hand_shield_check'] = 'Защита в ладони';
$_['text_cookie_icon_shield_lock'] = 'Щит с замком';
$_['text_cookie_icon_cookie_orbit'] = 'Печенье с орбитой';
$_['text_cookie_icon_cookie_document'] = 'Документ cookies';
$_['text_cookie_icon_custom'] = 'Своя иконка';

$_['entry_tax_display'] = 'Отображение цены и НДС';
$_['help_tax_display'] = 'Меняет только отображение цены в каталоге. Корзина, оформление, налоги и расчет заказа не изменяются.';
$_['text_tax_display_native'] = 'Штатное поведение OpenCart (рекомендуется для совместимости)';
$_['text_tax_display_none'] = 'Только основная цена OpenCart без дополнительной строки';
$_['text_tax_display_gross_net'] = 'Цена с НДС + ниже цена без НДС';
$_['text_tax_display_gross_tax'] = 'Цена с НДС + ниже только сумма НДС';
$_['text_tax_display_net_gross'] = 'Цена без НДС + ниже цена с НДС';
$_['entry_currency_trim_zeros'] = 'Скрывать ,00 в ценах';
$_['help_currency_trim_zeros'] = 'Если включено, цены с нулевой дробной частью показываются без нулей (100 вместо 100,00). Значения вроде 100,50 сохраняют копейки. Расчеты и точность в БД не меняются.';

// CodeCart PRO RC86: separate admin submenu appearance.
$_['entry_admin_submenu_color'] = 'Цвет подменю';
$_['help_admin_submenu_color'] = 'Фон вложенных пунктов бокового меню. Позволяет отдельно настроить контраст подменю.';

$_['entry_map_url']                   = 'Карта проезда';

$_['help_map_url']                    = 'Укажите HTTPS-адрес встраиваемой карты (например Google Maps с output=embed). Сохраняется только URL; HTML-код iframe не требуется.';

$_['text_email_logo_current_label'] = 'Сейчас:';
$_['text_email_logo_current'] = '%s, %s×%s px, %s KB. Для письма автоматически будет подготовлена компактная версия.';
$_['text_email_logo_current_none'] = 'логотип не выбран — используется обычный логотип магазина.';
$_['entry_image_avif'] = 'Предпочитать AVIF на витрине';
$_['help_image_avif'] = 'Если включено и PHP GD умеет кодировать AVIF, совместимые браузеры сначала получают AVIF. Без AVIF система переходит на WebP, затем на исходный формат. AVIF опционален, так как первое кодирование требует больше CPU.';
$_['entry_image_avif_quality'] = 'Качество AVIF';
$_['help_image_avif_quality'] = 'Качество от 45 до 90. Значение 72 — сбалансированное для фотографий товаров.';
$_['text_image_avif_supported'] = 'Кодирование AVIF доступно. Порядок: AVIF → WebP → оригинал.';
$_['text_image_avif_unsupported'] = 'В активном PHP GD нет кодировщика AVIF. Безопасно работает fallback WebP/оригинал.';
$_['text_image_avif_quality_hint'] = 'Рекомендуется: 72. AVIF генерируется в кеше, оригиналы не изменяются.';
