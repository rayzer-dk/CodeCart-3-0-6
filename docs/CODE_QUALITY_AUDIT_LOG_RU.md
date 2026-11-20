# Журнал независимого аудита исходников

## 2026-11-20 — аудит main

Проверка проводилась по реальным исходникам и новым запускам CI, не только по прежним отчётам.

Область: PHP-контроллеры и модели admin/catalog, библиотеки CodeCart Core, JS, CSS, Twig и комментарии об обходных исправлениях. Учитываются динамические точки входа OpenCart, OCMOD, Events, темы и внешние расширения.

Изменения: добавлены tools/quality/audit_cross_references.py и .github/workflows/source-quality-deep.yml; порог .jscpd.json уменьшен со 100% до 15%. В CatalogTransfer.php убрано неиспользуемое присваивание результата SimpleXlsx::streamSheet(). В DatabaseModernizer.php устранены два избыточных условия после предварительной проверки.

Подтверждённый запуск нового аудита: https://github.com/rayzer-dk/CodeCart-3-0-6/actions/runs/44705796141 — SUCCESS (все три задания). Сканировано 1507 исходных файлов. Выявлено 3708 кандидатов CSS, 31 кандидат JS и 15 комментариев об обходных решениях: это сигналы для проверки, а не доказанный мёртвый код. jscpd зафиксировал 1254 повторяющихся блока, 26.7% при пороге 15%; шаг рекомендательный, не скрывает превышение порога.

Ограничение: PHPStan для Core и дополнительных расширений рекомендательный, поскольку динамические вызовы OpenCart могут давать ложные срабатывания. Анализ CSS не выполняет рендеринг и не разрешает динамическую генерацию классов. Дубли с одинаковым назначением нельзя автоматически удалять без проверки API и тестов.

Результат: дополнительные проверки подключены на push, pull_request и workflow_dispatch; автоматическое удаление неподтверждённых кандидатов запрещено. Новые изменения требуют независимых PHP, OCMOD/Events, JS и браузерных регрессионных тестов.

## 2026-11-20 — точечное исправление подтверждённых избыточных условий

Работа ведётся только в rayzer-dk/CodeCart-3-0-6/main. В остальных репозиториях изменений в рамках этого этапа нет.

Независимая проверка непосредственных исходников и предшествующих инвариантов привела к следующим правкам:

- CatalogQuality.php: удалена проверка непустого массива SEO-запросов после гарантированного выхода при пустом списке найденных товаров. Убрана недостижимая ветка процента качества при нулевом числе проверок, поскольку проверка принадлежности магазину выполняется для каждого товара.
- AdminCategoryTreeEditor.php: убрана повторная проверка пустоты массива старых путей; array_search() уже гарантирует отказ при его пустоте. Проверки pivot и последнего узла сохранены.
- FontAwesomeManager.php: убрана заведомо истинная проверка extraIcons после добавления обязательной иконки file-pdf-o. Сохранены проверки манифеста и существования файлов, блок распрямлён.
- SchemaRegistry.php: используется созданный при чтении information_schema.STATISTICS список columns без лишних ?? и array_values; список инициализируется и заполняется при построении каждого индекса.
- CatalogTransfer.php: ранее убрано неиспользуемое присваивание при запуске потокового XLSX-парсера.
- DatabaseModernizer.php: ранее убраны недостижимые условные блоки после проверки потребности миграции; защитные проверки DDL сохранены.

Ничего не удалялось автоматически по одному лишь сигналу статического анализатора: внешние OCMOD/Events и динамические вызовы всё ещё требуют трассировки и функциональной регрессии. Проверки SQL, валидация файлов и безопасность оформления заказа не ослаблялись.

Контрольные ссылки: https://github.com/rayzer-dk/CodeCart-3-0-6/actions/workflows/dead-code-audit.yml и https://github.com/rayzer-dk/CodeCart-3-0-6/actions/workflows/source-quality-deep.yml. Автоматические CI-запуски для нового коммита были инициированы; успешность финальной матрицы проверяется отдельно, pending нельзя обозначать как PASS.

## 2026-11-20 — дополнительная очистка Core

По результатам чтения реализаций и проверки инвариантов выполнены точечные изменения:

- Queue.php: isset(owner) заменяет повторную проверку !== null, поскольку isset уже возвращает false для null; обработка ошибок и консервативная блокировка при неопределённом состоянии сохранены.
- MailAudit.php: убрано дублирующее условие !== null после isset(store_id). Значения 0 и строковый 0 сохраняются, резервный config_store_id и SQL-экранирование не изменены.
- EmailLogo.php: после успешного getimagesize() и проверки размеров MIME используется напрямую в обеих точках. Ветви неверного файла и fallback сохранены.

Это упрощения проверенного поведения, а не подавление анализаторов. Динамические связи OCMOD, Events и checkout не изменялись.

## 2026-11-20 — PWA app icon and release manifest regression

Scope: only CodeCart-3-0-6/main. Dedicated `config_codecart_pwa_icon` setting added to admin with native image picker and uk-ua/ru-ru/en-gb labels. On POST the image is checked against local DIR_IMAGE, a maximum of 8 MiB and 2048 px, and PNG/WebP format. The storefront PWA icon endpoint prefers the dedicated image, then config_icon, then its existing neutral shopping-bag rendering. Existing HTTPS and PWA OFF gates remain intact. No changes to Workbox caches, checkout or user data.

Regression coverage extended in tests/pwa_install_smoke.php: dedicated icon wins, missing icon falls back to favicon, traversal outside DIR_IMAGE cannot be used. Previous tests verify PWA OFF/HTTPS and 180/192/512 dimensions. New image-source cases execute only when PHP GD is available.

Audit result: First-party code quality deep audit https://github.com/rayzer-dk/CodeCart-3-0-6/actions/runs/44714945771 — success; PHPStan blocking admin/catalog passes. Duplicate-code inventory and CSS/JS reference candidates remain advisory, not automatically removed.

The earlier Production Release Check on SHA 4760d540 failed exclusively because five changed Core files had stale integrity hashes. The automatic Refresh Integrity Manifest workflow subsequently committed updated SHA-256 values, most recently commit a76ba32fed15 after the PWA changes. This journal commit triggers a fresh release check against the updated manifest. Do not regard still-queued runs as passed.

Open audit items: manual classification of substantial duplicated legacy MVC blocks, dynamic OCMOD/Events references and UI selectors; PHPStan advisory Core/bonus findings; complete storefront testing of PWA settings, image scaling and installation on representative devices. No unconditional production-readiness claim is made.

## 2026-11-20 — разбор 27 PHPStan замечаний по Core (ветка main)

Исходная выборка: 27 предупреждений Core в рекомендательном проходе PHPStan. Диагностика изучалась вместе с исходниками, обязательные проверки admin/catalog не отключались.

Изменения:
- CatalogTransfer.php: типизированы накопители ошибок и ID; повторяющиеся заполнение имён ошибок и формирование списка ошибок сведены к общим методам; централизована проверка отклонённых товаров и таблиц без PRIMARY KEY; удалены только логически недостижимые проверки null, размера, MIME и пустого array_chunk. Проверка PRIMARY KEY, запрет опасного импорта, безопасность ZIP и фильтрация файлов сохранены.
- CompatibilityFramework.php: необязательная возможность satisfiesOcmod вызывается только через проверенный callable, базовый интерфейс сторонних адаптеров не изменён.
- DashboardHealth.php: после curl_getinfo проверяется результат, гарантированные поля читаются без лишних значений по умолчанию. Промежуточная синтаксическая ошибка обнаружена blocking admin PHPStan и немедленно исправлена; этот ошибочный запуск не считался успешным.
- FreeCheckoutGuard.php: тип массива расчёта заказа уточнён с учётом динамической записи внешними total-моделями; сравнение всех корректировок и защита бесплатного заказа сохранены.
- DatabaseModernizer.php: исключена избыточная третья ветка при учёте неподдерживаемых DB engines: unsupported всегда подразумевает needsEngine. Отчёт о неподдерживаемых движках и запреты миграции сохранены.
- FileIntegrity.php и ModernExtensionRegistry.php: параметр registry в конструкторе сознательно сохранён для ABI/OpenCart/OCMOD совместимости. Два точно ограниченных по файлам и сообщению исключения описаны в phpstan.codecart.extra.dead-code.neon.dist; общий поиск неиспользуемых аргументов не отключался.

Хронология числа замечаний PHPStan Core: 27 → 15 → 4 → 0 (включая две явно задокументированные совместимые сигнатуры). Подтверждённый результат: https://github.com/rayzer-dk/CodeCart-3-0-6/actions/runs/44721846253 — SUCCESS, в журнале extra нет сообщений по upload/system/library/codecart/src. В bundled monobank_payment остаются четыре рекомендательных предупреждения о динамических OpenCart models/properties. Они не входят в 27 замечаний Core и не объявляются устранёнными.

Архив CodeCart-production предыдущей проверенной версии: https://github.com/rayzer-dk/CodeCart-3-0-6/actions/runs/44716013035 . Для сборки после новых изменений следует использовать соответствующий новый успешный Production Release Check; до завершения его нельзя считать готовым.

Дублирующиеся MVC-блоки и неподтверждённые CSS/JS-кандидаты остаются отдельной очередью ручного аудита; статическая проверка не заменяет интеграционное тестирование с OCMOD/Events и сторонними темами.
