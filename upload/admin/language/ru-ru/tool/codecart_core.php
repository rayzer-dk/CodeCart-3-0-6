<?php
// CodeCart PRO ru-ru language pack. Missing keys fall back to en-gb.
$_['heading_title'] = 'Ядро / Совместимость';
$_['text_home'] = 'Главная';
$_['text_runtime'] = 'Среда';
$_['text_security'] = 'Безопасность';
$_['text_privacy'] = 'Конфиденциальность';
$_['text_schema'] = 'Схема БД';
$_['text_extensions'] = 'Современные расширения';
$_['text_enabled'] = 'Включено';
$_['text_disabled'] = 'Выключено';
$_['text_ok'] = 'OK';
$_['text_missing'] = 'Отсутствует';
$_['text_optional'] = 'Необязательно';
$_['text_schema_not_scanned'] = 'Проверка не запускалась. Она только читает INFORMATION_SCHEMA и ничего не изменяет.';
$_['text_no_issues'] = 'Проблем не найдено';
$_['text_no_extensions'] = 'Современные расширения не зарегистрированы';
$_['text_gdpr_requests'] = 'Запросы GDPR';
$_['text_confirm_delete'] = 'Анонимизировать данные этого покупателя? Заказы и бухгалтерские записи будут сохранены.';
$_['text_device_fail_open'] = 'После включения неизвестное устройство подтверждается кодом из e-mail. Если код невозможно отправить из-за ошибки почты, текущая сессия не блокируется, а администратор получает критическое уведомление.';
$_['text_schema_readonly'] = 'Schema Registry — только диагностика. Дополнительные таблицы и поля сторонних модулей не считаются ошибкой. Автоматический ALTER не выполняется.';
$_['text_extension_help'] = 'Новый изолированный API-слой для современных расширений рядом с Legacy OpenCart 3. Это не бинарная совместимость с OpenCart 4.1: модули OpenCart 4.x требуют отдельной адаптации или bridge.';
$_['text_runtime_help'] = 'Проверяется текущий WEB/FPM runtime этой админки. CLI нужно проверять отдельно.';
$_['text_spam_help'] = 'Единый rate-limit сервис публичных форм, совместимый со старым FormGuard.';
$_['text_gdpr_help'] = 'Добавляет запросы на экспорт и удаление данных. Удаление выполняется контролируемой анонимизацией; заказы автоматически не удаляются.';
$_['text_cookie_help'] = 'Управляет согласием на необязательные cookies и подключением аналитических или маркетинговых интеграций.';
$_['text_notification_help'] = 'Только важные системные события, без рекламы.';
$_['text_current_user_devices'] = 'Доверенные устройства текущего администратора';
$_['text_revoke_devices'] = 'Отозвать доверенные устройства';
$_['text_refresh_registry'] = 'Обновить реестр';
$_['text_scan_schema'] = 'Проверить схему';
$_['text_save'] = 'Сохранить';
$_['text_processed'] = 'Обработано';
$_['text_pending'] = 'Ожидает';
$_['text_confirmed'] = 'Подтверждено';
$_['text_superseded'] = 'Заменено';
$_['text_export'] = 'Экспорт';
$_['text_delete'] = 'Удаление';
$_['text_unknown'] = 'Неизвестно';
$_['text_system_notifications'] = 'Системные уведомления';
$_['text_scheduler'] = 'Cron / Планировщик';
$_['text_settings_saved'] = 'Настройки сохранены.';
$_['text_extensions_refreshed'] = 'Реестр Modern Extensions обновлён: %s.';
$_['text_devices_revoked'] = 'Доверенные устройства отозваны.';
$_['text_gdpr_processed'] = 'Запрос GDPR обработан; учётная запись анонимизирована.';
$_['entry_spam'] = 'Центральная защита от спама';
$_['entry_admin_device'] = 'Защита входа администратора';
$_['entry_customer_device'] = 'Защита входа покупателя';
$_['entry_gdpr'] = 'Управление GDPR';
$_['entry_cookie'] = 'Согласие на cookies';
$_['column_component'] = 'Компонент';
$_['column_value'] = 'Значение';
$_['column_state'] = 'Состояние';
$_['column_type'] = 'Тип';
$_['column_table'] = 'Таблица';
$_['column_column'] = 'Поле';
$_['column_message'] = 'Сообщение';
$_['column_request'] = 'Запрос';
$_['column_customer'] = 'Покупатель';
$_['column_email'] = 'E-mail';
$_['column_date'] = 'Дата';
$_['column_action'] = 'Действие';
$_['button_process_delete'] = 'Анонимизировать';
$_['button_save'] = 'Сохранить';
$_['button_scan'] = 'Проверить';
$_['button_refresh'] = 'Обновить';
$_['button_revoke'] = 'Отозвать';
$_['error_permission'] = 'Недостаточно прав.';
$_['error_gdpr_disabled'] = 'GDPR Manager выключен.';
$_['error_action'] = 'Неизвестное действие.';
$_['button_cookie_settings'] = 'Настроить cookies';
$_['column_details'] = 'Пояснение';
$_['column_severity'] = 'Уровень';
$_['text_info'] = 'Информация';
$_['text_warning'] = 'Внимание';
$_['text_error'] = 'Ошибка';
$_['text_external'] = 'Стороннее/пользовательское';
$_['text_core'] = 'Ядро';
$_['text_schema_external_help'] = 'Дополнительные таблицы, колонки и индексы сторонних модулей показываются только информационно и никогда не удаляются автоматически.';
$_['text_preflight_help'] = 'Проверка выполняется в реальном WEB/FPM-профиле этого домена. Публичный phpinfo.php для постоянной диагностики не нужен.';
$_['entry_login_protection'] = 'Защита входа администратора';
$_['entry_login_max_attempts'] = 'Неудачные попытки на логин/IP';
$_['entry_login_window'] = 'Окно попыток, минут';
$_['entry_security_audit_retention'] = 'Хранение аудита, дней';
$_['entry_totp'] = 'MFA через приложение-аутентификатор (TOTP)';
$_['entry_security_headers'] = 'Заголовки безопасности';
$_['entry_frame_options'] = 'Защита от iframe';
$_['entry_permissions_policy'] = 'Permissions-Policy';
$_['entry_hsts'] = 'HSTS';
$_['text_totp_enabled'] = 'TOTP включён';
$_['text_totp_disabled'] = 'TOTP выключен';
$_['text_security_audit'] = 'Аудит безопасности';
$_['text_login_protection_help'] = 'Ограничивает неудачные входы администратора по паре логин + IP и отдельно по IP-источнику. Другой IP не может глобально заблокировать учётную запись администратора.';
$_['text_security_headers_help'] = 'Безопасные HTTP-заголовки управляются ядром. CSP сначала только Report-Only; HSTS отправляется только через HTTPS.';
$_['text_hsts_help'] = 'Включайте только когда весь домен и нужные поддомены постоянно работают через HTTPS.';
$_['entry_nosniff'] = 'Защита от MIME sniffing';
$_['entry_referrer_policy'] = 'Referrer-Policy';
$_['text_about'] = 'О системе';
$_['text_about_requirements'] = 'Требования сервера';
$_['text_required'] = 'Обязательно';
$_['text_recommended'] = 'Рекомендуется';
$_['text_not_supported'] = 'Не поддерживается';
$_['text_server_requirements_help'] = 'Показаны требования именно к профилю PHP, который обслуживает сайт. CLI для Cron проверяется отдельно.';
$_['text_component'] = 'Компонент';
$_['text_codecart_website'] = 'Сайт CodeCart PRO';
$_['text_icons'] = 'Иконки';
$_['text_icon_search'] = 'Поиск по названию иконки';
$_['text_icon_help'] = 'Каталог Font Awesome 6.7.2 + FA4 compatibility и CodeCart PRO aliases. Нажмите на иконку, чтобы скопировать её CSS-класс.';
$_['text_modern_social_icons'] = 'Современные социальные иконки';
$_['diag_loaded'] = 'загружено';
$_['diag_missing'] = 'отсутствует';
$_['diag_supported'] = 'поддерживается';
$_['diag_not_supported'] = 'не поддерживается';
$_['diag_enabled'] = 'включено';
$_['diag_disabled'] = 'выключено';
$_['diag_recommended'] = 'рекомендуется';
$_['diag_optional'] = 'необязательно';
$_['diag_active'] = 'активно';
$_['diag_not_active'] = 'неактивно';
$_['diag_writable'] = 'доступно для записи';
$_['diag_inside_root'] = 'внутри публичного каталога';
$_['diag_outside_root'] = 'вне публичного каталога';
$_['diag_unknown'] = 'неизвестно';
$_['diag_never'] = 'никогда';
$_['diag_not_configured'] = 'не настроено';
$_['diag_check_failed'] = 'ошибка проверки';
$_['diag_msg_php'] = 'Поддерживается PHP 8.1–8.5 в WEB/FPM-профиле.';
$_['diag_msg_required_ext'] = 'Обязательное PHP-расширение для WEB/FPM.';
$_['diag_msg_required_image'] = 'Обязательный формат изображений.';
$_['diag_msg_avif'] = 'Рекомендуется для нативной поддержки AVIF.';
$_['diag_msg_production'] = 'Рекомендуется для рабочего магазина.';
$_['diag_msg_current_web'] = 'Текущее значение WEB/FPM.';
$_['diag_msg_upload_limit'] = 'Лимит обычных загрузок покупателя и администратора дополнительно к PHP-лимитам.';
$_['diag_msg_extension_limit'] = 'Лимит сжатого .ocmod.zip; для распакованного архива также проверяются количество файлов, размер и пути.';
$_['diag_msg_display_errors'] = 'На рабочем магазине display_errors должен быть Off, а log_errors — On.';
$_['diag_msg_https'] = 'Необходимо для безопасного checkout и cookies.';
$_['diag_msg_writable'] = 'Каталог доступен для записи.';
$_['diag_msg_not_writable'] = 'Каталог должен существовать и быть доступен для записи.';
$_['diag_msg_storage'] = 'Лучше размещать storage вне публичного web root.';
$_['diag_msg_strict_ok'] = 'Ядро работает со строгим SQL-режимом.';
$_['diag_msg_strict_warn'] = 'Включён совместимый SQL-режим. Строгий режим стоит включать после проверки совместимости.';
$_['diag_msg_db_modernize'] = 'Изменяйте engine/charset только после полной резервной копии.';
$_['diag_msg_schema_ok'] = 'Версия схемы соответствует этой сборке.';
$_['diag_msg_schema_warn'] = 'Версия схемы отличается от сборки. Запустите проверку миграций.';
$_['diag_msg_migration_latest'] = 'Последняя записанная миграция ядра.';
$_['diag_msg_migration_none'] = 'История миграций ядра ещё не создана.';
$_['diag_msg_queue'] = 'Проверьте Cron / Планировщик и Queue.';
$_['diag_msg_smtp'] = 'Проверены только настройки; доставку нужно подтвердить тестовым письмом.';
$_['diag_msg_mail'] = 'Текущий почтовый механизм OpenCart.';
$_['diag_msg_disk'] = 'Недостаток места может остановить кеш, генерацию изображений, логи и обновления.';
$_['schema_tables_expected'] = 'Ожидается таблиц';
$_['schema_tables_present'] = 'Найдено таблиц';
$_['schema_missing_tables'] = 'Отсутствуют таблицы';
$_['schema_missing_columns'] = 'Отсутствуют поля';
$_['schema_type_mismatches'] = 'Несоответствия типов';
$_['schema_engine_mismatches'] = 'Несоответствия engine';
$_['schema_charset_mismatches'] = 'Несоответствия кодировки';
$_['schema_missing_indexes'] = 'Отсутствуют индексы';
$_['schema_external_tables'] = 'Дополнительные таблицы';
$_['schema_external_columns'] = 'Дополнительные поля';
$_['schema_external_indexes'] = 'Дополнительные индексы';
$_['schema_blocking_issues'] = 'Критические несоответствия';
$_['schema_warnings'] = 'Предупреждения';
$_['schema_informational'] = 'Информационные записи';
$_['schema_type_missing_table'] = 'Отсутствует таблица';
$_['schema_type_missing_column'] = 'Отсутствует поле';
$_['schema_type_type'] = 'Тип поля';
$_['schema_type_engine'] = 'Engine таблицы';
$_['schema_type_charset'] = 'Кодировка';
$_['schema_type_missing_index'] = 'Отсутствует индекс';
$_['schema_type_external_table'] = 'Дополнительная таблица';
$_['schema_type_external_column'] = 'Дополнительное поле';
$_['schema_type_external_index'] = 'Дополнительный индекс';
$_['schema_msg_missing_table'] = 'Таблица отсутствует';
$_['schema_msg_missing_column'] = 'Поле отсутствует';
$_['schema_msg_missing_index'] = 'Индекс отсутствует';
$_['schema_msg_missing_unique_index'] = 'Уникальный индекс отсутствует';
$_['schema_msg_external_table'] = 'Дополнительная таблица стороннего модуля или пользователя; только информация';
$_['schema_msg_external_column'] = 'Дополнительное поле стороннего модуля или пользователя; только информация';
$_['schema_msg_external_index'] = 'Дополнительный индекс стороннего модуля или пользователя; только информация';
$_['button_schema_safe_fix'] = 'Исправить безопасные проблемы';
$_['text_schema_safe_fix_help'] = 'В таблице теперь указано, что означает каждая проблема и что делать. Кнопка исправляет только engine/кодировку и отсутствующие неуникальные индексы. Отсутствующие таблицы, поля, изменение типов и уникальные индексы оставлены ручными, чтобы не повредить данные и модули.';
$_['text_schema_backup_warning'] = 'Перед любым исправлением схемы сделайте полный backup базы данных и файлов сайта. ALTER TABLE на больших таблицах может временно блокировать запись, поэтому выполняйте его при низкой нагрузке.';
$_['text_schema_backup_confirm'] = 'Я сделал полный backup базы данных и файлов сайта.';
$_['text_schema_fixable'] = 'Можно исправить';
$_['text_schema_manual'] = 'Вручную';
$_['text_schema_external_ok'] = 'Норма';
$_['text_schema_fix_result'] = 'Исправление схемы завершено: исправлено %d, оставлено для ручной проверки %d.';
$_['text_schema_fix_errors'] = 'При исправлении схемы возникло ошибок: %d. Проверьте журнал и оставшиеся строки.';
$_['column_meaning'] = 'Что это значит';
$_['column_next_action'] = 'Что делать';
$_['schema_meaning_missing_table'] = 'Таблица, обязательная для этой сборки CodeCart, отсутствует.';
$_['schema_action_missing_table'] = 'Не создавайте её вслепую. Сначала запустите миграции CodeCart; если проблема останется, сравните с чистой схемой и восстанавливайте только после backup.';
$_['schema_meaning_missing_column'] = 'В существующей таблице отсутствует обязательное поле.';
$_['schema_action_missing_column'] = 'Запустите миграции CodeCart. Если поле не появилось, проверьте миграцию ядра или модуля, которому оно принадлежит, до ручного ALTER.';
$_['schema_meaning_type'] = 'Поле существует, но его SQL-тип несовместим с эталонной схемой.';
$_['schema_action_type'] = 'Сначала проверьте реальные данные и зависимые модули. Автоматически тип не меняется, чтобы не потерять данные.';
$_['schema_meaning_engine'] = 'Engine таблицы отличается от эталона CodeCart.';
$_['schema_action_engine'] = 'После полного backup безопасное исправление может конвертировать таблицу в ожидаемый engine. Большая таблица может временно блокироваться.';
$_['schema_meaning_charset'] = 'Кодировка или collation таблицы отличается от эталона CodeCart.';
$_['schema_action_charset'] = 'После полного backup безопасное исправление может конвертировать таблицу. Перед этим проверьте старые текстовые данные.';
$_['schema_meaning_missing_index'] = 'Отсутствует эталонный индекс; запросы могут работать медленнее или отсутствует требуемая гарантия данных.';
$_['schema_action_missing_index'] = 'Безопасное исправление добавляет только неуникальные индексы. Уникальные исправляются вручную после проверки дублей.';
$_['schema_meaning_external'] = 'Это дополнительная таблица, поле или индекс стороннего модуля/доработки. Ошибкой CodeCart PRO это не является.';
$_['schema_action_external'] = 'Ничего делать не нужно. CodeCart PRO не удаляет и не изменяет сторонние объекты схемы.';
$_['text_icon_all'] = 'Все';
$_['text_icon_social'] = 'Соцсети';
$_['text_icon_commerce'] = 'Магазин';
$_['text_icon_payment'] = 'Оплата';
$_['text_icon_shipping'] = 'Доставка';
$_['text_icon_contact'] = 'Контакты';
$_['text_icon_interface'] = 'Интерфейс';
$_['text_icon_security'] = 'Безопасность';
$_['text_icon_media'] = 'Медиа';
$_['text_icon_development'] = 'Разработка';
$_['text_icon_other'] = 'Другие';
$_['text_icon_copied'] = 'Скопировано';
$_['text_icon_usage'] = 'Скопированный класс можно использовать в Twig/HTML, например <i class="fa-solid fa-cart-shopping"></i>. Для брендов используется стандартный синтаксис Font Awesome 6, например <i class="fa-brands fa-x-twitter"></i>.';
$_['entry_gdpr_retention'] = 'Хранение истории GDPR, дней';
$_['text_gdpr_retention_help'] = 'Автоматически удаляются только старые обработанные или отменённые запросы. Активные pending/confirmed не удаляются.';
$_['text_retention_auto'] = 'Автоматическая очистка, дней';
$_['button_cleanup_old'] = 'Очистить старые';
$_['text_cleanup_done'] = 'Удалено старых записей: %d';
$_['text_cleanup_confirm'] = 'Очистить записи старше установленного срока хранения?';
$_['help_login_max_attempts'] = 'Сколько неудачных попыток разрешить до временной блокировки. Рекомендуется 5–7; меньше может мешать администратору, больше ослабляет защиту.';
$_['help_login_window'] = 'Период, в котором считаются неудачные попытки. Рекомендуется 15 минут.';
$_['help_totp'] = 'Дополнительный одноразовый код из Authenticator. Рекомендуется для всех администраторов с доступом к настройкам, заказам или модулям.';
$_['help_customer_device'] = 'Проверка нового устройства покупателя через e-mail. Включайте, если почта магазина стабильно доставляет коды; для гостевого checkout не требуется.';
$_['help_nosniff'] = 'Запрещает браузеру угадывать MIME-тип файла. Рекомендуется оставить включённым.';
$_['help_referrer_policy'] = 'Определяет, сколько адреса страницы браузер передаёт внешним сайтам. strict-origin-when-cross-origin — рекомендуемый баланс приватности и совместимости.';
$_['help_frame_options'] = 'Защищает админку/витрину от встраивания в iframe. SAMEORIGIN рекомендуется; DENY строже и может мешать легальным интеграциям.';
$_['help_permissions_policy'] = 'Ограничивает браузерные API, например камеру, микрофон и геолокацию. Не меняйте без проверки интеграций.';
$_['help_gdpr'] = 'Управляет запросами на экспорт/анонимизацию персональных данных. Заказы и бухгалтерские записи автоматически не удаляются.';
$_['help_gdpr_retention'] = 'Сколько дней хранить завершённые/отменённые GDPR-запросы. Рекомендуется 365; активные запросы не очищаются.';
$_['help_cookie'] = 'Статус Cookie Consent и переход к его отдельным настройкам. Необязательные cookies должны запускаться только после согласия.';
$_['text_icon_scanner'] = 'Сканер совместимости иконок';
$_['text_icon_scanner_help'] = 'Проверяет реальные файлы витрины и скомпилированный catalog OCMOD. Для каждой иконки вне Core показывает CSS-класс, файл и строку. AUTO в таком случае безопасно переключается на Full Compatibility.';
$_['text_icon_mode_requested'] = 'Настроенный режим';
$_['text_icon_mode_setting'] = 'Режим пакета иконок';
$_['text_icon_mode_setting_help'] = 'Выберите пакет иконок для витрины. AUTO использует Core и переходит на Full только при необходимости.';
$_['text_icon_auto'] = 'AUTO — Core с автоматическим переходом на Full';
$_['text_icon_core'] = 'Core — облегчённый набор CodeCart PRO';
$_['text_icon_full'] = 'Full Compatibility — Font Awesome Free + совместимость с FA4';
$_['text_icon_mode_saved'] = 'Режим пакета иконок сохранён.';
$_['text_icon_mode_effective'] = 'Фактический режим';
$_['text_icon_core_count'] = 'Иконок в Core';
$_['text_icon_files_scanned'] = 'Проверено файлов';
$_['text_icon_last_scan'] = 'Последняя проверка';
$_['text_icon_unknown'] = 'Вне Core';
$_['text_icon_location'] = 'Файл';
$_['text_icon_line'] = 'Строка';
$_['text_icon_kind'] = 'Тип';
$_['text_icon_resolution'] = 'Решение';
$_['text_icon_resolution_full'] = 'Есть в Full Free';
$_['text_icon_resolution_unresolved'] = 'Иконки нет в Font Awesome Free 6.7.2. Возможна опечатка или PRO-only иконка; Full Compatibility ее не отрисует.';
$_['text_icon_unresolved'] = 'Недоступно в Free';
$_['text_icon_no_unknown'] = 'Все найденные иконки поддерживаются минимальным Core-пакетом.';
$_['text_icon_fallback_full'] = 'Обнаружены иконки вне Core: в режиме AUTO витрина использует Full Compatibility, поэтому иконки не пропадают.';
$_['text_icon_core_active'] = 'Core-пакета достаточно для текущей витрины.';
$_['text_icon_scan_error'] = 'Сканер не смог завершить проверку. AUTO переключается на Full Compatibility.';
$_['text_icon_manual_build_help'] = 'Ручные добавления в Core задаются при релизной сборке в system/config/codecart_fontawesome_core_manifest.json. Добавьте имя без fa- в manual_extra_icons. Ошибочные, неизвестные и не входящие в Free имена пропускаются с предупреждением и не останавливают всю сборку. На production шрифты не перекомпилируются.';
$_['button_icon_rescan'] = 'Проверить иконки';
$_['text_icon_scan_complete'] = 'Проверка иконок завершена: файлов %d, иконок вне Core %d.';
$_['text_icon_core_package'] = 'Core / Минимальный';
$_['text_icon_full_package'] = 'Полный Free';
$_['text_icon_core_package_help'] = 'Core — лёгкий минимальный набор, который реально загружается на витрине в режиме Core/AUTO. AUTO переключается на Full, если установленному расширению нужна иконка вне Core.';
$_['text_icon_full_package_help'] = 'Полный каталог Font Awesome Free 6.7.2. Просмотр полного каталога не заставляет витрину загружать весь пакет — это справочник и копирование классов.';
$_['text_icon_full_count'] = 'Иконок в полном Free';
$_['text_icon_styles'] = 'Стили';
$_['entry_icon_extras'] = 'Дополнительные иконки Core';
$_['help_icon_extras'] = 'Скопируйте классы иконок из полного Free ниже и вставьте через пробел или запятую. Сохраните набор или добавьте все доступные иконки, найденные сканером. Загружаются только необходимые небольшие файлы шрифтов. Неизвестные названия пропускаются. Чтобы убрать иконку из набора, удалите её название и сохраните.';
$_['button_icon_add_found'] = 'Добавить найденные иконки';
$_['button_icon_save_extras'] = 'Сохранить набор иконок';
$_['text_icon_extras_saved'] = 'Дополнительных иконок сохранено: %d. Неизвестных названий пропущено: %d. Встроенные иконки Core уже доступны.';
$_['error_icon_extras'] = 'Не удалось сохранить набор иконок. Проверьте ввод и наличие всех файлов сборки.';
$_['button_phpinfo'] = 'PHP Info';
$_['button_phpinfo_download'] = 'Скачать PHP Info';
$_['text_icon_loading'] = 'Загрузка полного каталога иконок…';
$_['text_icon_load_error'] = 'Не удалось загрузить полный каталог иконок.';
$_['text_architecture'] = 'Архитектура';
$_['text_architecture_help'] = 'Современные сервисы добавляются поверх ядра. Стандартные MVC-L, маршруты, Events и OCMOD OpenCart/ocStore не заменяются.';
$_['text_compat_detected'] = 'Определена схема главной категории';
$_['text_active_languages'] = 'активных языков';
$_['text_service_layer_help'] = 'Только дополнительный сервисный слой; стандартные Registry, Loader и MVC-L остаются основным путём совместимости.';
$_['text_event_layer_help'] = 'Опциональные обработчики изолированы: ошибка одного нового хука не ломает остальные обработчики и стандартные Events OpenCart.';
$_['text_asset_manager_help'] = 'Простые CSS/JS сохраняют стандартный мост addScript/addStyle для старых тем; расширенные ресурсы используются только явно.';
$_['text_api_v1'] = 'REST API v1';
$_['text_api_v1_help'] = 'Опциональный API каталога только для чтения. По умолчанию выключен. Включайте только для Headless, интеграций или внешних сервисов.';
$_['text_service_layer'] = 'Сервисный слой';
$_['text_compat_layer'] = 'Слой совместимости';
$_['text_event_layer'] = 'Событийный слой';
$_['text_asset_manager'] = 'Менеджер ресурсов';
$_['text_search_adapter'] = 'Адаптер поиска';
$_['text_native_fallback'] = 'Стандартный поиск каталога всегда остаётся резервным; внешние поисковые движки необязательны.';
$_['text_opt_in'] = 'По выбору';
$_['text_read_only'] = 'Только чтение каталога; стандартный API OpenCart и checkout не изменяются.';
$_['entry_api_v1_status'] = 'REST API v1';
$_['button_save_architecture'] = 'Сохранить настройки архитектуры';
$_['text_performance'] = 'Производительность';
$_['text_performance_help'] = 'Безопасные настройки оптимизации CSS и JavaScript для витрины и админки.';
$_['entry_storefront_minify_css'] = 'Использовать минифицированные CSS витрины, если доступны';
$_['entry_storefront_minify_js'] = 'Использовать минифицированный JavaScript витрины, если доступен';
$_['entry_admin_minify_css'] = 'Использовать минифицированные CSS админки, если доступны';
$_['entry_admin_minify_js'] = 'Использовать минифицированный JavaScript админки, если доступен';
$_['help_storefront_minify_css'] = 'Использовать локальный *.min.css только когда рядом с исходным CSS существует актуальная минифицированная версия. Исходный файл не изменяется.';
$_['help_storefront_minify_js'] = 'Использовать локальный *.min.js только когда рядом с исходным JavaScript существует актуальная минифицированная версия. Порядок скриптов не меняется.';
$_['help_admin_minify_css'] = 'Использовать локальный *.min.css в админке только когда рядом с исходным CSS существует актуальная минифицированная версия. Исходный файл не изменяется.';
$_['help_admin_minify_js'] = 'Использовать локальный *.min.js в админке только когда рядом с исходным JavaScript существует актуальная минифицированная версия. Порядок скриптов не меняется.';
$_['text_safe_minify_note'] = 'Безопасный режим: система не склеивает файлы и не выполняет рискованную runtime-минификацию стороннего кода. Если проверенной .min-версии нет, автоматически используется исходный файл.';
$_['button_save_performance'] = 'Сохранить производительность';
$_['text_minify_diagnostics'] = 'Диагностика min-ресурсов';
$_['text_minify_available'] = 'Доступно актуальных min-файлов';
$_['text_minify_stale'] = 'Устаревших min-файлов';
$_['text_minify_active'] = 'Активно по текущим настройкам';
$_['text_db_modernize'] = 'Модернизация таблиц БД (InnoDB + utf8mb4)';
$_['text_db_modernize_help'] = 'Конвертирует все таблицы магазина, включая таблицы сторонних модулей, в InnoDB/utf8mb4 по одной таблице за запрос — подходит для хостинга без SSH. Таблицы от 256 MB пропускаются: для них используйте php cli.php db:migrate --backup-confirmed --large.';
$_['text_db_modernize_ok'] = 'Все таблицы уже используют InnoDB и utf8mb4.';
$_['text_db_modernize_running'] = 'Сконвертировано таблиц: %s, осталось: %s…';
$_['text_db_modernize_done'] = 'Готово. Сконвертировано таблиц: %s, осталось: %s.';
$_['text_db_modernize_large'] = 'большая — только CLI';
$_['button_db_modernize'] = 'Модернизировать таблицы';
$_['column_engine'] = 'Движок';
$_['column_collation'] = 'Кодировка';
$_['column_size'] = 'Размер';

// Localized diagnostics and comparison table
$_['diag_component_runtime_php'] = 'PHP';
$_['diag_component_ext_xmlreader'] = 'XMLReader';
$_['diag_component_runtime_opcache'] = 'OPcache';
$_['diag_component_upload_store_limit'] = 'Лимит загрузки файлов магазина';
$_['diag_component_upload_extension_limit'] = 'Лимит установщика расширений';
$_['diag_component_security_display_errors'] = 'display_errors';
$_['diag_component_http_https'] = 'HTTPS';
$_['diag_component_path_storage_public'] = 'Доступность storage из веба';
$_['diag_component_db_version'] = 'MySQL/MariaDB';
$_['diag_component_db_strict_sql'] = 'Строгий режим SQL';
$_['diag_component_db_modern'] = 'Движок / кодировка БД';
$_['diag_component_db_schema_version'] = 'Версия схемы Core';
$_['diag_component_db_migration_history'] = 'История миграций';
$_['diag_component_db_connection'] = 'База данных';
$_['diag_component_cron_health'] = 'Пульс cron';
$_['diag_component_queue_failed'] = 'Ошибки очереди';
$_['diag_component_mail_smtp'] = 'SMTP';
$_['diag_component_mail_engine'] = 'Почтовый движок';
$_['diag_component_disk_free'] = 'Свободное место на диске';
$_['diag_msg_xlsx_stream'] = 'Большие XLSX импортируются в потоковом режиме XMLReader.';
$_['diag_msg_xlsx_simplexml'] = 'Обычный импорт XLSX работает через SimpleXML, но очень большие книги могут потребовать значительно больше памяти.';
$_['diag_optional_cache_backends'] = 'необязательные бэкенды кэша';
$_['diag_storage_cache_logs'] = 'Storage / кэш / журналы';
$_['diag_msg_sql_compat'] = 'Совместимый с OpenCart режим SQL (стандарт для модулей OpenCart/ocStore). Строгий режим включается настройкой codecart_db_strict_mode = 1.';

$_['text_icon_packages'] = 'Пакеты Font Awesome';

$_['error_invalid_request_method'] = 'Недопустимый метод запроса.';
$_['error_invalid_security_token'] = 'Недействительный токен безопасности.';

$_['text_extension_code'] = 'Код';
$_['text_extension_name'] = 'Название';
$_['text_extension_version'] = 'Версия';
$_['text_extension_namespace'] = 'Пространство имен';
$_['text_icon_style_solid'] = 'Сплошные';
$_['text_icon_style_regular'] = 'Контурные';
$_['text_icon_style_brands'] = 'Бренды';
$_['text_totp_own'] = 'Ваша учётная запись';
$_['button_totp_manage'] = 'Управление';
$_['text_totp_policy'] = 'Обязательно для групп';
$_['help_totp_policy'] = 'Участники выбранных групп обязаны настроить двухфакторную проверку при следующем входе и не смогут её отключить. Общие демо-группы и группы только для просмотра не отмечайте.';
$_['text_totp_group_stats'] = 'защищено %d из %d активных пользователей';
$_['entry_csp_report_only'] = 'Content Security Policy';
$_['entry_csp_policy'] = 'Политика CSP';
$_['text_csp_report_only_help'] = 'Отправляет политику ниже. В режиме «Только отчёты» браузер лишь сообщает о нарушениях и ничего не блокирует.';
$_['help_csp_policy'] = 'Директивы Content-Security-Policy через точку с запятой. Одна и та же политика используется в обоих режимах.';
$_['entry_csp_mode'] = 'Режим CSP';
$_['text_csp_mode_report'] = 'Только отчёты (Content-Security-Policy-Report-Only)';
$_['text_csp_mode_enforce'] = 'Блокировать (Content-Security-Policy)';
$_['help_csp_mode'] = 'В режиме «Блокировать» браузер запрещает всё, что не разрешено политикой. Темы и модули OpenCart 3 используют inline-скрипты и внешние ресурсы, поэтому включайте блокировку только после проверки витрины, оформления заказа, оплат и админки в режиме «Только отчёты» без нарушений.';
$_['text_about_changes'] = 'Отличия от OpenCart/ocStore';
$_['text_about_build'] = 'Сборка';
$_['text_about_schema'] = 'Схема ядра';
$_['text_about_revision'] = 'Ревизия оформления';
$_['text_about_upstream'] = 'Основа';
$_['text_about_upgrade_required'] = 'Нужен Installer UPDATE, в базе';
$_['text_about_db'] = 'База данных';
$_['text_about_driver'] = 'Драйвер БД';
$_['text_about_components'] = 'Компоненты и библиотеки';
$_['text_about_components_note'] = 'Версии ocStore и OpenCart взяты из их релизных архивов (composer.lock и поставляемые файлы). «—» означает, что компонента нет.';
$_['text_about_advantages'] = 'Преимущества';
$_['text_about_level_updated'] = 'Обновлено';
$_['text_about_level_added'] = 'Добавлено';
$_['text_about_level_same'] = 'Без изменений';
$_['text_about_group_added'] = 'Добавлено';
$_['text_about_group_security'] = 'Безопасность';
$_['text_about_group_updated'] = 'Обновлено';
$_['text_about_group_fixed'] = 'Исправлено';
$_['text_about_group_removed'] = 'Удалено';
$_['about_component_php'] = 'PHP';
$_['about_component_gallery'] = 'Галерея товара';
$_['about_component_datepicker'] = 'Выбор даты на витрине';
$_['about_component_password'] = 'Хеширование паролей админки';
$_['about_component_smtp_tls'] = 'TLS для SMTP / HTTPS';
$_['about_v_datepicker_native'] = 'Встроенный (без moment.js)';
$_['about_v_datepicker_bootstrap'] = 'bootstrap-datetimepicker + moment.js';
$_['about_v_password_sha1'] = 'SHA-1 с солью';
$_['about_v_tls_strict'] = 'TLS 1.2–1.3, проверка сертификата';
$_['about_v_tls_starttls'] = 'STARTTLS, без минимальной версии';
$_['about_advantages'] = array(
    'Работает на PHP 8.1–8.5. ocStore 3.0.3.7 заявляет PHP 5.4+, OpenCart 3.0.4.1 — PHP 7.3+.',
    'Библиотеки новее, чем в любой из основ: Twig 3.30 против 2.14 / 3.8 / 3.24, Guzzle 7.15 против 5.3 / 6.5 / 7.10.',
    'Пароли администраторов хешируются password_hash() вместо SHA-1 с солью, двухфакторный вход встроен. Ни в одной из трёх основ этого нет.',
    'Существующие расширения OpenCart 3 продолжают работать: алиасы классов Twig 2, jQuery Migrate, классы Font Awesome 4, OCMOD и события.',
    'Cron, очередь, уведомления, состояние магазина, проверка целостности и модернизация БД входят в ядро, а не в отдельные модули.'
);
$_['about_added_items'] = array(
    'Двухфакторный вход (TOTP) для администраторов с резервными кодами; можно сделать обязательным для группы пользователей.',
    'Подтверждение новых устройств кодом из письма для администраторов и покупателей.',
    'Планировщик, очередь задач, серверный Cron и команды CLI (cron:run, queue:work, db:migrate, styles:rebuild).',
    'Системные уведомления и панель состояния магазина с проверкой файлов по манифесту релиза.',
    'Брошенные корзины с напоминаниями по согласию, включая контакты гостей.',
    'Импорт/экспорт каталога (XLSX), публичные документы для товаров, связи товаров.',
    'SEO: языковые префиксы URL, SeoPro, hreflang и правила canonical, монитор потерянных URL.',
    'Журнал почты, DNS-проверка SPF/DKIM/DMARC, уведомления о поступлении товара.',
    'Запросы GDPR, согласие на cookie, единая антиспам-защита публичных форм, капча Cloudflare Turnstile.',
    'REST API v1 только для чтения (выключен по умолчанию), кеш Redis/APCu/Memcached, изображения WebP/AVIF.'
);
$_['about_updated_items'] = array(
    'PHP 8.1–8.5; Twig 3.30, Guzzle 7.15, scssphp 2.1.',
    'jQuery 3.7.1 везде, jQuery Migrate 3.4.1 для старых модулей.',
    'Font Awesome 6.7.2 Free; классы Font Awesome 4 и font-family FontAwesome работают через встроенный слой совместимости.',
    'Summernote 0.9.1, HTML Purifier 4.19.0, PhotoSwipe 5.4.4 как альтернативная галерея.',
    'Поля дат на витрине используют встроенный выбор даты; moment.js и bootstrap-datetimepicker оставлены только для сторонних модулей.',
    'Новые установки используют utf8mb4 и InnoDB; модернизатор БД переводит старые таблицы после резервной копии.',
    'Installer UPDATE обновляет существующие магазины версионированными миграциями с проверкой схемы.'
);
$_['about_fixed_items'] = array(
    'Включены исправления ядра OpenCart 3.0.5.1: кодирование пароля администратора, GLOB_BRACE на musl, авторизация Redis, сессия капчи.',
    'Расширения: имена классов Twig 2 связаны с Twig 3, триггеры событий совпадают по префиксу, принимается DB_DRIVER mpdo из ocStore.',
    'MySQL 8: таблицы CodeCart используют utf8mb4_unicode_ci, ошибки сопоставления 1267 нет.',
    'SeoPro отдаёт 404 для неизвестных сегментов пути и не перенаправляет cron.php и платёжные callback.',
    'Запросы с нестроковым route больше не заканчиваются HTTP 500.'
);
$_['about_security_items'] = array(
    'Пароли администраторов хешируются password_hash() и перехешируются при входе; старые хеши SHA-1 по-прежнему принимаются.',
    'Защита входа с лимитами попыток по аккаунту и источнику, журнал безопасности.',
    'CSRF-токены в формах подтверждения устройства и GDPR; лимит API по реальному IP клиента.',
    'Заголовки безопасности: nosniff, Referrer-Policy, Permissions-Policy, HSTS, CSP в режиме отчётов или блокировки; админку нельзя встроить во фрейм чужого сайта.',
    'SMTP и встроенные HTTP-интеграции (PayPal, курсы валют, Google, Turnstile) требуют TLS 1.2+ с проверкой сертификата.'
);
$_['about_removed_items'] = array(
    'Файлы шрифтов Font Awesome 4 (используются веб-шрифты Font Awesome 6, классы FA4 сопоставлены) и библиотека jQuery 2.1.1 (заглушка в 1 КБ со старым именем файла подключает jQuery 3.7.1 с jQuery Migrate 3.4.1 для тем, которые на него ссылаются).',
    'Устаревший php.ini в корне сайта (удалите его на существующих сайтах).',
    'Дублирующий шаг обновления 3054 и старые действия импорта каталога в «Инструменты → Резервное копирование».'
);
$_['about_component_twig'] = 'Twig';
$_['about_component_scssphp'] = 'scssphp';
$_['about_component_guzzle'] = 'Guzzle';
$_['about_component_jquery'] = 'jQuery';
$_['about_component_jquery_migrate'] = 'jQuery Migrate';
$_['about_component_bootstrap'] = 'Bootstrap';
$_['about_component_fontawesome'] = 'Font Awesome';
$_['about_component_swiper'] = 'Swiper';
$_['about_component_summernote'] = 'Summernote';
$_['about_component_htmlpurifier'] = 'HTML Purifier';
