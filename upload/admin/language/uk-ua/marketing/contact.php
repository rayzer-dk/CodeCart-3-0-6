<?php

// Heading
$_['heading_title']        = 'Поштова розсилка';

// Text
$_['text_success']         = 'Ваше повідомлення відправлене';
$_['text_sent']            = 'Ваше повідомлення відправлене %s з %s отримувачів';
$_['text_list']            = 'Список розсилки';
$_['text_default']         = 'За замовчуванням';
$_['text_newsletter']      = 'Підписчики на новини';
$_['text_customer_all']    = 'Все покупці';
$_['text_customer_group']  = 'Група покупців';
$_['text_customer']        = 'Покупці';
$_['text_affiliate_all']   = 'Все партнери';
$_['text_affiliate']       = 'Партнери';
$_['text_product']         = 'Товари';

// Entry
$_['entry_store']          = 'Від';
$_['entry_to']             = 'Кому';
$_['entry_customer_group'] = 'Група покупців';
$_['entry_customer']       = 'Покупець';
$_['entry_affiliate']      = 'Партнер';
$_['entry_product']        = 'Товари';
$_['entry_subject']        = 'Тема';
$_['entry_message']        = 'Повідомлення';

// Help
$_['help_customer']       = '(Автодоповнення)';
$_['help_affiliate']      = '(Автодоповнення)';
$_['help_product']        = 'Надіслати покупцям, які вже замовляли товари зі списку. (Автодоповнення)';

// Error
$_['error_permission']     = 'У вас немає прав для відправлення повідомлень';
$_['error_subject']        = 'Необхідно вказати тему повідомлення';
$_['error_message']        = 'Необхідно додати текст повідомлення';
$_['error_email'] = 'Не знайдено одержувачів з коректною адресою E-Mail!';

// Шаблони листів
$_['text_template_new']        = 'Новий шаблон';
$_['text_template_saved']      = 'Шаблон листа збережено.';
$_['text_template_deleted']    = 'Шаблон листа видалено.';
$_['text_template_preview']    = 'Попередній перегляд листа';
$_['text_preview_customer']    = 'Іван Петренко';
$_['text_preview_store']       = 'Ваш магазин';
$_['entry_template']           = 'Шаблон листа';
$_['entry_template_name']      = 'Назва шаблону';
$_['button_template_load']     = 'Завантажити шаблон';
$_['button_template_save']     = 'Зберегти шаблон';
$_['button_template_preview']  = 'Попередній перегляд';
$_['button_template_delete']   = 'Видалити шаблон';
$_['help_template_variables']  = 'Доступні змінні: {{ customer_name }}, {{ store_name }}, {{ store_url }}, {{ unsubscribe_url }}.';
$_['error_template_name']      = 'Вкажіть назву шаблону не довшу за 100 символів.';
$_['error_template_not_found'] = 'Шаблон листа не знайдено.';

$_['text_campaign_queued'] = 'Кампанію №%s поставлено в чергу для %s одержувачів. Надсилання продовжиться у фоні.';
$_['text_campaign_history'] = 'Останні розсилки';
$_['column_campaign'] = 'Кампанія';
$_['column_audience'] = 'Одержувачі';
$_['column_progress'] = 'Прогрес';
$_['column_status'] = 'Статус';
$_['column_date_added'] = 'Створено';
$_['text_campaign_queued_status'] = 'У черзі';
$_['text_campaign_sending'] = 'Надсилається';
$_['text_campaign_completed'] = 'Завершено';
$_['text_campaign_empty'] = 'Немає одержувачів';

// Local HTML import
$_['help_template_open'] = 'Відкрити завантажує вибраний збережений шаблон. Виберіть Новий шаблон, щоб відкрити HTML-файл із ПК (UTF-8, до 1 МБ). Скрипти та небезпечна розмітка видаляються. Перевірте вміст перед збереженням.';
$_['text_template_imported'] = 'HTML завантажено в редактор. Перевірте його та збережіть шаблон, коли він буде готовий.';
$_['error_template_file'] = 'Виберіть HTML-файл (.html або .htm).';
$_['error_template_size'] = 'HTML-файл має бути непорожнім і не більшим за 1 МБ.';
$_['error_template_encoding'] = 'HTML-файл має використовувати кодування UTF-8.';
$_['error_template_import'] = 'Не вдалося завантажити HTML-файл. Перевірте права доступу та повторіть спробу.';
$_['error_template_html'] = 'Не вдалося безпечно обробити HTML шаблону. Перевірте розмітку та повторіть спробу.';
