<?php
$_['heading_title'] = 'Дані / Резервні копії';
$_['text_data_center'] = 'Центр даних';
$_['text_success'] = 'Відновлення бази даних завершено успішно.';
$_['text_select_all'] = 'Обрати все';
$_['text_unselect_all'] = 'Зняти вибір';
$_['text_catalog_title'] = 'Імпорт / Експорт каталогу';
$_['text_catalog_help'] = 'Безпечне масове перенесення та редагування каталогу. XLSX використовує логічні назви таблиць без префікса БД і при імпорті звіряється з реальною схемою OpenCart/ocStore.';
$_['text_catalog_export_format'] = 'Без прапорця «Переносимий пакет» завантажується XLSX. З прапорцем — ZIP, що містить catalog.xlsx та наявні файли image/catalog.';
$_['text_catalog_vs_sql'] = 'Це експорт каталогу для перенесення/редагування: XLSX або ZIP з catalog.xlsx. SQL нижче — окрема резервна копія всієї бази даних. XML для Google Merchant генерується окремим модулем фіда для кожної активної мови.';
$_['text_catalog_download_target'] = 'Файл каталогу: codecart_catalog_*.xlsx (або ZIP з catalog.xlsx).';
$_['text_catalog_import'] = 'Імпорт каталогу';
$_['text_catalog_import_help'] = 'Спочатку файл лише перевіряється. Запис у БД починається тільки після підтвердження. Перед імпортом автоматично створюється SQL-знімок таблиць, які будуть змінені. ID не перенумеровуються: це безпечно для перенесення/відновлення спорідненого каталогу, але перед об’єднанням двох незалежних магазинів потрібно перевірити збіги ID.';
$_['text_xlsx_unavailable'] = 'Імпорт/експорт XLSX недоступний: у WEB/FPM PHP мають бути активні розширення ZIP і SimpleXML.';
$_['text_xlsx_export_unavailable'] = 'Експорт XLSX недоступний: у WEB/FPM PHP має бути активне розширення ZIP.';
$_['text_xlsx_import_unavailable'] = 'Імпорт XLSX недоступний: у WEB/FPM PHP мають бути активні розширення ZIP і SimpleXML.';
$_['text_no_file'] = 'Файл не вибрано';
$_['text_preview'] = 'Попередня перевірка';
$_['text_processing'] = 'Обробка…';
$_['text_catalog_confirm'] = 'Імпорт оновить або додасть записи каталогу з такими самими ID. Автоматичний SQL-знімок буде створено перед записом. Продовжити?';
$_['text_catalog_import_success'] = 'Імпорт каталогу завершено.';
$_['text_catalog_result'] = 'Оновлено/додано записів: %s. Резервна копія: %b';
$_['text_sql_backup_title'] = 'Резервна копія бази даних';
$_['text_sql_backup_help'] = 'SQL-копія призначена для аварійного відновлення магазину. Для масового редагування товарів використовуйте вкладку каталогу.';
$_['text_restore_warning'] = 'Відновлення SQL змінює дані магазину. Перед операцією переконайтеся, що маєте актуальну повну копію сайту та бази даних.';
$_['text_restore_confirm'] = 'Відновлення SQL може замінити поточні дані вибраних таблиць. Продовжити?';

$_['tab_catalog'] = 'Каталог XLSX / ZIP';
$_['tab_backup'] = 'База даних SQL';
$_['tab_restore'] = 'Відновлення БД';

$_['entry_export'] = 'Таблиці';
$_['entry_progress'] = 'Прогрес';
$_['entry_portable'] = 'Пакет із зображеннями';
$_['help_portable'] = 'ZIP містить catalog.xlsx та наявні файли з image/catalog. Звичайний XLSX містить лише шляхи до зображень.';
$_['entry_overwrite_images'] = 'Замінювати наявні зображення файлами з пакета';

$_['entity_products'] = 'Товари';
$_['entity_categories'] = 'Категорії';
$_['entity_manufacturers'] = 'Виробники';
$_['entity_options'] = 'Опції';
$_['entity_attributes'] = 'Характеристики';
$_['entity_filters'] = 'Фільтри';
$_['help_products'] = 'Опис, категорії, фото, опції, характеристики, акції, знижки, магазини та зв’язки';
$_['help_categories'] = 'Дерево, описи, магазини, макети, фільтри та SEO';
$_['help_manufacturers'] = 'Виробники, магазини та макети';
$_['help_options'] = 'Опції та всі їх значення';
$_['help_attributes'] = 'Групи характеристик і характеристики';
$_['help_filters'] = 'Групи фільтрів і значення';
$_['icon_products'] = 'fa-cube';
$_['icon_categories'] = 'fa-folder-open';
$_['icon_manufacturers'] = 'fa-tags';
$_['icon_options'] = 'fa-list-alt';
$_['icon_attributes'] = 'fa-sliders';
$_['icon_filters'] = 'fa-filter';

$_['button_export'] = 'Завантажити резервну копію БД (.sql)';
$_['button_import'] = 'Відновити з SQL';
$_['button_catalog_export'] = 'Експортувати каталог у XLSX';
$_['button_catalog_select'] = 'Вибрати XLSX або ZIP';
$_['button_catalog_import'] = 'Імпортувати після перевірки';

$_['column_table'] = 'Таблиця';
$_['column_rows'] = 'Рядків';
$_['column_columns'] = 'Сумісних полів';
$_['column_status'] = 'Стан';

$_['error_permission'] = 'У вас недостатньо прав для зміни даних магазину.';
$_['error_export'] = 'Оберіть хоча б одну таблицю для резервної копії.';
$_['error_file'] = 'Файл не знайдено або не вдалося прочитати.';
$_['error_filesize'] = 'Розмір SQL-резервної копії перевищує 128 МБ.';
$_['error_filetype'] = 'Дозволено лише коректний SQL-файл резервної копії OpenCart.';
$_['error_catalog_entities'] = 'Оберіть хоча б один розділ каталогу.';
$_['error_catalog_file'] = 'Виберіть коректний файл каталогу.';
$_['error_catalog_export'] = 'Не вдалося створити файл експорту каталогу.';
$_['error_restore_table'] = 'SQL-файл містить таблицю, яка не належить поточному магазину. Відновлення зупинено.';

$_['text_progress_preparing'] = 'Підготовка файлу…';
$_['text_progress_uploading'] = 'Завантаження файлу…';
$_['text_progress_checking'] = 'Перевірка структури…';
$_['text_progress_importing'] = 'Запис даних у каталог…';
$_['text_progress_downloading'] = 'Завантаження готового файлу…';
$_['text_progress_done'] = 'Готово';
$_['text_relation_preserve'] = 'Безпечний режим: імпорт не видаляє наявні додаткові категорії, опції, характеристики чи інші зв’язки товару, якщо відповідного рядка немає у файлі. Для основної категорії виконується сумісне зіставлення OpenCart/ocStore.';

$_['text_sql_sensitive_warning'] = 'SQL-копія може містити хеші паролів, API-ключі, налаштування пошти та інші конфіденційні дані. Зберігайте файл як секрет. Тимчасові таблиці session та api_session за замовчуванням не вибрані.';

$_['text_catalog_xml_help'] = 'XML не дублює перенос каталогу: для Google Merchant та зовнішніх інтеграцій використовуйте окремі XML-фіди для кожної активної мови.';
$_['button_xml_feeds'] = 'Відкрити XML-фіди';

$_['error_restore_query'] = 'Помилка SQL під час відновлення таблиці';
