# Build 2.0.2 — 2026-10-08

- Add opt-in lost-content 404 report with browser/session deduplication, separate bot counts, meaningful-request filters, store/language scope and persistent storage.
- Add manual slug-only 301 redirects, with live-source priority, target SEO checks and redirect pagination.
- Add selectable 30/60/90-day retention with safe/all cleanup modes; preserve all manual redirects.
- Recognize the verified UniShop2 article route information/uni_news_story.
- Register daily bounded cleanup in the shared scheduler when enabled; OFF stops all working paths and retains data.
- Correct web cron authentication: a false CODECART_CLI constant no longer grants CLI privileges.
- Apply the styled error page to missing native blog articles.

# Build 2.0.1 — 2026-10-08

- Added responsive 404 and maintenance pages to both bundled themes, including dark mode and translated navigation.
- Added a standalone, database/Twig-independent 500 fallback; public failures hide exception details and API/AJAX retain JSON responses.
- Missing products, categories, manufacturers and information pages use the same error layout with correct HTTP status and noindex.
- Error-page stylesheet loads only on error/maintenance pages; maintenance retains Retry-After.

# Build 2.0.0 — 2026-10-07

- Consolidate audit branch history and supplied Build 1.9.12 source in main.
- Update Twig to 3.30.0 and Symfony mbstring/php80 polyfills to 1.43.0; retain PHP 8.1 support.
- Abort authentication and clear session state when writing a rotated session fails.
- Restrict production packages and source-writing workflows to main; disable scheduled Dependabot version pull requests.
- Compatibility verification and limitations are recorded in documentation/VERIFICATION_2026-10-07.md.

# Build 1.9.12 — 2026-09-28

Исправлено
- Админка: исправлена цепочка входа после обновления/сброса пароля. Новая session ID теперь физически сохраняется до удаления старой сессии, поэтому redirect после успешного login не может вернуть пользователя на форму входа из-за ещё не записанной сессии.
- Пароли: OpenCart Request::clean() исторически HTML-экранирует все POST-строки. Проверка пароля теперь совместима и с прежним экранированным представлением, и с исходным значением; новые/изменённые пароли хешируются из восстановленного исходного текста. Это устраняет проблемы с паролями, содержащими &, <, > и кавычки, без блокировки старых хешей.
- Legacy SHA1/MD5 и современные password_hash() продолжают поддерживаться; после успешного legacy-входа выполняется безопасный rehash.
- Защита входа за reverse proxy/Cloudflare использует общий trusted-proxy resolver вместо REMOTE_ADDR, чтобы разные proxy-hop не создавали ложные блокировки после сброса пароля.
- UI: добавлена кнопка-глаз для показа/скрытия пароля на форме входа, сбросе пароля, профиле администратора, форме пользователя и форме покупателя в админке. Значение поля не изменяется.
- ru-ru: текст требования к новому паролю приведён к фактической серверной проверке 10–40 символов.

Проверено
- PHP 8.4 lint всех PHP-файлов release-пакета.
- Совместимость password_hash(), OpenCart legacy SHA1 и MD5.
- Пароли со спецсимволами &, <, >, двойными и одинарными кавычками.
- Session regenerate: новая сессия записывается до уничтожения старой.
- Twig syntax изменённых admin-шаблонов.

# Build 1.9.11 — 2026-09-28

Исправлено
- Delivery Auto: исправлен неверный endpoint отделений `GetWarehousesListByCity` → официальный `GetWarehousesList`.
- Delivery Auto: добавлен безопасный fallback между `www.delivery-auto.com` и `delivery-auto.com`; HTML/не-JSON ответ одного хоста больше не ломает синхронизацию, используется второй официальный хост.
- Delivery Auto: запросы справочника теперь явно требуют `application/json, text/json`; ошибки endpoint логируются без секретов и возвращаются как корректный JSON в админку.
- Укрпошта: справочник отделений приведён к актуальной документации 2026; идентификатором отделения используется `POSTOFFICE_ID`, закрытые (`LOCK_CODE != 0`) и закрытого типа (`IS_SECURITY = 1`) отделения не предлагаются покупателю.
- Meest: сверены функции `City` и `Branch`, фильтрация `CityUUID` поддерживается официальным API; текущая реализация сохранена.
- Нова пошта: транспорт `Address/getCities` и `AddressGeneral/getWarehouses` сверены с рабочим Nova Poshta PRO; просроченный ключ корректно возвращается как ошибка API без падения JSON-ответа.
- Проведён regression-check PHP syntax для изменённого carrier layer и повторная проверка структуры release-пакета.

# Build 1.9.10 — 2026-09-28

Критичные
- Чистая установка 1.9.9 падала с MySQL 1064 (и в web-, и в CLI-установщике): SQL делился по строкам, оканчивающимся на `;`, а многострочные описания статей содержат `&gt;` в конце строки. Добавлен `CodeCart\Core\SqlScript` — разбор с учётом кавычек/комментариев, используется обоими установщиками.
- Сохранение в админке под строгим SQL-режимом теряло данные (опции товаров, описания производителей/статей, зоны геозон) — MySQL 1364 после DELETE. Сессия БД снова в OpenCart-совместимом режиме; строгий режим — только явным `codecart_db_strict_mode = 1`.

Формы
- Заголовок/тексты формы хранились HTML-экранированными: в списке блоков товара показывалось `&amp;`/`&quot;`, описание на витрине выводило буквальные `<b>`. Теперь хранится обычный текст и очищенный HTML; миграция исправляет уже сохранённые формы.
- Иконка FA6 (`fa-solid fa-…`), выбранная для кнопки формы в блоке товара/категории, молча отбрасывалась при сохранении.
- Блок «Форма» без выбранной формы больше не сохраняется пустым.
- Спиннер отправки был виден постоянно (перебивался стилями Font Awesome из footer).
- Поля заявки приходили в письмо HTML-экранированными; значения `select` с `&`/кавычками не проходили проверку.
- После сохранения формы и настроек перевозчика не показывалось сообщение об успехе (оно «всплывало» позже на другой странице).
- Поля ввода формы в админке экранируются (кавычки в заголовке ломали атрибут).

Перевозчики (Нова Пошта и др.)
- Проверено end-to-end: синхронизация 11–20 тыс. городов при memory_limit 128M, неверный ключ, поиск города и отделения в checkout, запись в заказ.
- Списки отделений кэшируются (повторные запросы при выборе/проверке/подтверждении больше не идут в API).
- Ошибки справочника на витрине пишутся в журнал (раньше покупатель видел «недоступно», а причина терялась).
- Экранирование `%`/`_` в поиске города.

Телефоны
- Поля телефона запрещали `+`, хотя подсказки предлагают `+380…`; при апгрейде покупатель с `+380…` не мог сохранить профиль. Разрешён один ведущий `+` (JS и серверная проверка во всех формах).

Данные и старые ошибки OpenCart/ocStore
- Демо-модуль «Головна — вступ» (html.36) имел невалидный JSON и неверную структуру — блок никогда не выводился. Исправлено в SQL и миграцией.
- Добавление языка не копировало описания производителей, блога, доп. вкладок товаров, форм и контентных блоков.
- Сохранение валюты обрезало пробел в символе (` ₴` → `₴`, цены «120.00₴»).
- Путь категории «Офісні планшети» указывал на чужую ветку; миграция выравнивает `category_path` по `parent_id` только для несогласованных категорий.
- Связи демо-статей сделаны двусторонними (иначе первое сохранение статьи удаляло связи у других статей).
- CLI-установщик ставил пустой аватар администратора (web — `catalog/profile-pic.webp`).
- Письмо администратору о заказе выводило ключ `text_quantity`.

Админка и языки
- Меню ru-ru было на английском (73 пункта); переведены также Google Analytics и прочие строки.
- uk-ua: «Статті» → «Інформаційні сторінки», «Настроювані поля» → «Додаткові поля», «Одиниці виміру» → «Одиниці довжини», «Валюти», «Статуси замовлень», «Теми сертифікатів»; исправлены смешанные латиница/кириллица (`cтатей`, `Партнерcкий`).
- Системные уведомления (OPcache, диск, cron, расширения, каталоги) локализованы; убраны ложные предупреждения: «нет истории миграций» на чистой установке, «cron never» при первом запуске, «мало места» при десятках ГБ свободных.
- Пресеты форм/блоков для en/ru больше не дают JS SyntaxError.
- Гейт языков проверяет и `$language->load()`, не считает данные формы языковыми ключами.

Сборка
- Устаревшие `stylesheet.min.css` пересобраны (1.9.9 из-за этого отдавал несжатый CSS).
- `codecart_presentation_schema_version` = 32; проверка схемы в модели форм — `>= 31` (без лишнего SHOW COLUMNS).

# Build 1.9.9 — 2026-09-27

- Fixed form saving on partially upgraded databases: admin write path now idempotently adds the four button-style columns before INSERT/UPDATE instead of throwing MySQL 1054.
- Fixed carrier city-sync AJAX authentication: JavaScript endpoint now receives a raw query separator instead of HTML `&amp;`, so `user_token` is transmitted correctly and the endpoint returns JSON rather than an HTML response with HTTP 200.
- Restored the bundled CodeCart admin profile image as the visual default when an administrator has no custom image; clean installs now store the same default image explicitly.
- Corrected fresh-install `codecart_presentation_schema_version` to 31 so clean databases match the current schema immediately.

# CodeCart PRO 3.0.6.0 Build 1.9.8

## Build 1.9.8

### Fixed
- Исправлен выбор иконки кнопки формы: каталог теперь загружается лениво из статического локального файла с fallback на JSON endpoint и базовый набор, поэтому сбой маршрута больше не блокирует выбор.
- Исправлен обработчик выбора: клик по плитке гарантированно записывает класс иконки, обновляет preview и закрывает окно.

### UI
- Настройки иконки, цвета фона, цвета текста и hover-фона объединены в одну компактную строку.
- Поле с текстовым названием класса иконки скрыто; отображается только выбранная иконка.
- Picker сделан компактнее: иконки отображаются плитками без подписей, поиск сохранён; полное название доступно только как tooltip.

- Fixed form storefront fatal error after file-only update: presentation schema bumped to 31 and form reads tolerate the short migration window without querying missing style columns.
- Removed the redundant quick-add created-form toolbar from product/category content blocks. Existing forms remain selectable inside the standard Form block.
- Replaced the fixed button-icon dropdown with a lazy searchable modal picker backed by the full bundled Font Awesome Free catalogue; form icons now support safe FA6 classes as well as legacy FA4 aliases.

## Fixed
- Исправлен приоритет контентных блоков: category «Не показывать» отключает только наследование, но не явные блоки товара.
- Исправлены Warning `Undefined array key store_id` в настройках темы: используется нормализованный `$store_id`.
- В товаре и категории добавлен прямой выбор уже созданной формы/информационного блока без предварительного создания пустого блока.

## Added
- Глобальные настройки кнопки формы: иконка, фон, цвет текста и hover-цвет.
- Локальное переопределение оформления кнопки в конкретном блоке товара/категории.
- Индивидуальный переключатель показа для каждого локального контентного блока.
- Глобальное отключение формы/информационного блока через его Status продолжает скрывать его во всех местах использования.


Fixed:
- Fixed category purchase-block inheritance semantics: category mode “Do not show” is now a hard stop for product purchase-area blocks in that category path, including product-specific blocks and embedded Form / CTA blocks.
- “Inherit” continues to walk to the nearest configured parent category; product-level “Do not show” continues to suppress all blocks for that individual product.

Performance:
- Corrected reusable-form CSS placement: forms.css is now actually registered in the footer (the previous changelog claimed this, but the code still registered it in the header).
- Product-page related-products native-modules.css now loads in the footer together with its JS because that block is below the primary product content.
- Kept jQuery, Bootstrap JS and common.js in the compatibility-safe critical path for now; moving/defering them globally without extension-level regression tests could break OpenCart 3.x modules with inline scripts.

# CodeCart PRO 3.0.6.0 Build 1.9.4

Fixed:
- Fixed CodeCart Theme editor warnings when store_id is absent; store 0 is now the explicit safe default.
- Fixed theme count/selection on fresh CodeCart installs: legacy Default remains a filesystem fallback but is not counted or offered as a second user theme.
- Fixed CodeCart Theme preview: uses the real compact preview.webp and is bounded in Settings instead of expanding a missing-image placeholder.
- CodeCart Theme directory is fixed to codecart; the legacy default directory remains a compatibility fallback and is no longer exposed as a CodeCart Theme target.
- Fixed admin dark-mode profile fallback contrast.
- Removed hidden automatic product-question form injection. Forms now appear only when explicitly placed through catalog purchase blocks or a Form module/layout.
- Fixed catalog purchase-block settings links to open CodeCart Theme rather than the legacy Default controller.
- Clarified product/category inheritance and added a direct Manage Forms action.

Performance:
- Moved optional reusable-form CSS/JS to the footer.
- Moved PhotoSwipe CSS/JS to the footer on product and blog article pages; the existing DOM-ready initializers still execute after the library is loaded and no longer block the initial head render.

Fixed:
- Fixed SeoPro routing for the primary language without a URL prefix when the requested SEO path has a trailing slash.
- SeoPro now ignores empty route segments, preventing valid primary-language product/category/blog URLs from becoming false 404 pages.
- Category Wall corner action now points diagonally into the bottom-right corner.
- Category Wall corner and icon scale proportionally with card width, capped at the original 38 px corner size.


Theme installation policy:
- Clean CodeCart installation registers and activates only CodeCart Theme.
- The physical `catalog/view/theme/default` tree remains only as a compatibility fallback for legacy modules/OCMOD and is not presented as a second system theme on a clean installation.
- OpenCart/ocStore UPDATE preserves the currently active theme and every previously installed theme. CodeCart Theme is added as an additional selectable theme and is never activated automatically.
- Switching an upgraded store to CodeCart Theme later no longer hides or unregisters the original OpenCart/ocStore Default Theme.
- Added persistent `codecart_install_origin` metadata so theme presentation is based on installation origin instead of the currently active theme.

# CodeCart PRO 3.0.6.0 Build 1.9.1

Mail reliability and mobile layout
- Local store images in HTML email are automatically embedded as CID inline images, so logos, product images and voucher images do not depend on Gmail/Outlook fetching files from the storefront.
- Email inline assets are normalized to persistent DIR_STORAGE/codecart/email-assets/ files and remain compatible with the persistent mail queue.
- Mail and SMTP transports now emit correct inline image MIME parts; queued delivery restores CID assets before sending.
- Order customer email no longer uses a five-column product table. Each product is rendered as a mobile-safe card with model, quantity, price and total rows.
- Admin order alert uses the same narrow-screen-safe product structure.
- Address blocks stack on small screens and all order tables are constrained to the message width with long values allowed to wrap.
- Reviewed all bundled HTML mail templates in CodeCart and Default fallback themes; no fixed table wider than the 600px mail container remains.

# CodeCart 3.0.6.0 Build 1.9.0

Fixed:
- SeoPro resolution for the primary unprefixed language now falls back to the configured default language on cache/route misses instead of producing a false 404; language-aware cache access is hardened against legacy flat cache data.
- CodeCart Theme is the only visible system theme on a native CodeCart installation; legacy Default Theme files remain only as a compatibility fallback. The theme list now shows the CodeCart Theme preview, status and edit action in one row.
- The bundled CodeCart system article was expanded with a detailed OpenCart/ocStore/CodeCart comparison table and made the newest demo article so it appears in Latest Articles.
- Category Wall corner keeps its original 38x38 geometry and the single fa-angle-down icon is positioned fully inside the triangular clip, preventing the glyph from being visually cut.
- Admin header light-theme navigation color is darker for better contrast.
- Catalog and blog sort/limit/view controls now use one fixed 34px geometry across light/dark themes.

Changed:
- Bundled demo presentation schema updated so existing CodeCart demo installs receive the article, theme-list and presentation fixes without affecting merchant stores.

# CodeCart 3.0.6.0 Build 1.8.9

Admin search:
- Reworked all five global admin search scopes: Catalog, Customers, Orders, Content & modules, and Settings.
- Settings search now indexes CodeCart Core / Compatibility tabs and important system/admin pages such as system notifications, scheduler/queue, backups, uploads, developer settings, layouts, SEO URL, users, localization and logs.
- Settings fields are searchable by visible labels in Ukrainian, Russian and English, by internal key and by section.
- Content & modules search now finds localized module titles and configured module instance names instead of searching only extension codes.
- Search examples with prefixes such as `module:`, `setting:`, `key:`, `product:` and their Ukrainian/Russian equivalents now work as real filters.
- Catalog/customer/order search accepts substring matches and additionally searches SKU/product identifiers, customer/order telephone and invoice data.
- Search scope is remembered between admin pages and two-character queries such as `AI` are supported.
- Unified result cards now show a clear title, context/path and useful secondary metadata without raw clutter.

# CodeCart 3.0.6.0 Build 1.8.8

Changed:
- The compact direct-subcategory block is now a standalone `Subcategories` module that can be assigned through Design → Layouts. No instance is created or assigned by default, so Category Wall is not duplicated.
- Category Wall corner remains 38×38 px and now uses one `fa-solid fa-angle-down` icon without rotation.

# CodeCart 3.0.6.0 Build 1.8.7

Fixed:
- Restored Category Wall corner size to 38x38 and enlarged only the Font Awesome double-angle icon.
- Removed the duplicate built-in subcategory grid from category.twig; Category Wall remains the single category-navigation block in the configured layout.
- Removed development/QA-only files from the final production package while retaining installation documentation, legal/provenance metadata and the optional Monobank bonus.

# CodeCart 3.0.6.0 Build 1.8.6

Fixed:
- Added FA4 compatibility aliases fa-file-text-o and fa-money to the lightweight Core icon package so AUTO does not fall back to Full for the checkout payment template.
- Left-aligned the icon package mode form in Core / Compatibility.
- Simplified the About-system comparison PHP row to PHP ranges only and renamed the CodeCart comparison column to CodeCart PRO 3.0.6.x.

# CodeCart 3.0.6 Build 1.8.5

Storefront / SEO:
- Enabled standard SEO URLs by default for clean installations; SeoPro remains a separate optional advanced URL mode.
- Added native SEO URL encode/decode support for blog articles and blog categories, so article_id/blog_category_id aliases work even when SeoPro is off.
- Enabled native subcategory images on category pages and added safe image dimensions/fallbacks.
- Removed the bundled demo Category Wall from Category layout because the native category page already renders subcategories; this removes duplicate child-category blocks.
- Improved empty-category spacing between Continue and content-bottom/form modules.
- Enlarged Category Wall corner navigation and switched it to Font Awesome 6 double-angle-right.
- Rewrote the bundled CodeCart system article to explain architecture, commerce reliability, compatibility, SEO and key differences.

UI:
- Package build remains an internal release marker but is no longer displayed in Installer 2.0 or OCMOD compatibility UI.

# CodeCart 3.0.6 Build 1.8.4

Security:
- Installer 2.0 UPDATE on an already installed store now requires store administrator credentials (a user with modify permission for users, extensions or Core). Previously any visitor could open /install/, read preflight diagnostics and run UPDATE/"Repair current build", which invalidated the OCMOD build marker and disabled all modifications (UniShop2 storefront lost its CSS/JS) until an administrator refreshed Modifications.
- Administration always sends X-Frame-Options: SAMEORIGIN and CSP frame-ancestors 'self' (opt-out: codecart_security_admin_frame_protection=0). Storefront framing settings are unchanged.
- Blog category/article enable/disable and PayPal recurring enable/disable now require modify permission (PayPal also POST + user_token).

Fixed:
- Product pages opened from a parent category returned 404 when the product belongs only to a subcategory, although category listings include subcategory products. checkProductCategory() now accepts descendants of the path via category_path.
- Product card rows (latest/bestseller/popular/special/featured modules, wishlist, recently viewed, related, CMS/blog products) again expose the full historical getProduct() contract (ean, isbn, mpn, date_available, manufacturer, weight, ...). UniShop2 and third-party OCMOD code no longer raise warnings and the "New" sticker works again.
- ru-ru admin and storefront language packs were partial overlays: an overlay update replaced complete ocStore ru-ru files and Russian stores fell back to English (cart, checkout, account, product page, admin). Complete ru-ru packs are shipped now; new CodeCart strings are translated.
- uk-ua: checkout step numbers are printf placeholders again (guest checkout showed "Step 6" where step 4 was expected); password-reset mail showed a literal "%s"; return activity lost its link; untranslated PayPal Smart Button strings.
- The default/codecart themes served the unminified stylesheet in the normal production state (developer_theme=1 is OpenCart's default) and the shipped codecart stylesheet.min.css was stale. The minified file is now built from the source with a SHA-256 marker and served unless a merchant edited stylesheet.css.
- robots.txt advertised /sitemap.xml on a clean install while the sitemap feed was not enabled (404 for crawlers). Web and CLI installers now enable the Google Sitemap feed.
- Text logo is an H1 only on the home page (no duplicate H1 on product/category pages); compact text logo on mobile.

Performance:
- Category/manufacturer/search listing query no longer evaluates rating/discount/special correlated subqueries for every matching row unless the active sort uses them (20k products: category page 0.55 s -> 0.25 s; manufacturer 0.44 s -> 0.16 s). Third-party modified SQL is left untouched.
- Theme Editor overrides and design/translation overrides are resolved with one query per request instead of one query per template/language file (home page 100 -> 57 SQL queries).
- Traffic heartbeat no longer runs scheduled work (currency providers, mail queue) inside the visitor request on mod_php/CGI: it uses fastcgi_finish_request/litespeed_finish_request or a detached loopback cron request, with automatic fallback when loopback is blocked.

Improved:
- Core / Compatibility > Database Schema: "Modernize tables" converts remaining MyISAM/non-utf8mb4 tables (including third-party tables not covered by Schema Registry) one table per request, for hosting without SSH. The modernization flag is refreshed after safe schema repair.
- Release gates: language pack completeness/placeholder gate, minified stylesheet freshness gate; heavy runtime/upgrade CI gates now run on source changes and the 3.0.5 baseline gate tests the current source instead of a pinned 1.7.8 artifact.

Upgrade note: after uploading 1.8.4 and running UPDATE (administrator sign-in required), refresh Extensions > Modifications.

# CodeCart 3.0.6.0 Build 1.8.3

Fixed:
- Fixed malformed inline JavaScript in checkout shipping carrier city results, reproduced by real Chromium/Playwright E2E.
- Aligned UniShop2 release gate with the Compatibility Framework: OCMOD satisfaction is an optional adapter capability rather than a mandatory base-interface method.
- Fixed Monobank webhook QA SQL quoting so signed webhook and stale-event idempotency are exercised on live MariaDB.
- Proxy no longer exposes absolute server paths to visitors when an unavailable method is called; details go to the server log.
- Google Login secret is no longer rendered back into admin HTML; blank input preserves the stored secret.
- API v1 search has a per-IP rate limit.
- Legacy Braintree cleanup deletes only known stock controller/template files by SHA-256 and never removes an active integration.
- Active legacy Divido is preserved during update and reported as a compatibility warning instead of being removed.
- UniShop2/Braintree/Divido compatibility notices never block PHP 8.4/8.5 update; CodeCart Core remains PHP 8.1–8.5.

Changed:
- Bonus Monobank Payment Modern updated to v1.1.0 with one-invoice-per-order locking, currency_value-aware amount calculation, unique order id and safe secret handling.

Runtime QA:
- Live ocStore 3.0.4.1 -> CodeCart upgrade on MariaDB passed data preservation, Installer 2.0 migration, repeat-upgrade idempotency, storefront/admin login and error-log gates.
- Live commerce QA passed on MySQL 8.4 and MariaDB 10.11, including stock=1 concurrent checkout, Chromium guest checkout, signed Monobank webhook/stale-event idempotency and parallel HTTP load smoke.
- Build 1.8.3 integrity manifest was regenerated from the final main source tree.

# CodeCart PRO 3.0.6.0 Build 1.8.2

## Added — Modern Extension bonus
- Added `bonuses/Monobank_Payment_Modern_v1.1.0.ocmod.zip` as an optional demonstration extension.
- The payment method appears in Extensions -> Payments through thin OpenCart bridge files while API/webhook/idempotency logic is loaded from `system/extension/monobank_payment` through Modern Extension Registry.
- The bonus is not installed or enabled automatically.


## Compatibility Framework

- Replaced direct UniShop2 calls in catalog controllers with a generic Compatibility Framework.
- Added `CompatibilityAdapterInterface` and isolated runtime adapter execution with safe failure logging.
- UniShop2 is now the first built-in theme adapter instead of a Core-specific special case.
- Added installable compatibility adapters through modern extension `manifest.json` capability `compatibility.adapters`.
- External adapters are restricted to their extension namespace and must implement the official interface.
- OCMOD diagnostics now query the generic Compatibility Framework; adapters can optionally declare legacy OCMOD searches they supersede.
- Added stable contracts for menu, category module/page, product options and banner data.
- Preserved OFF-means-OFF: an installed but inactive theme does not execute its adapter.
- Preserved CodeCart batched category loading and modern image pipeline; no old ocStore/OpenCart N+1 implementation was restored.
- Added developer documentation in `documentation/COMPATIBILITY_FRAMEWORK.md`.
- Fixed Modern Extension Registry namespace normalization that could trim a trailing `t`, and fixed global `CodeCartPsr4` registration so newly installed adapter namespaces can be registered correctly.


UniShop2 compatibility
- Added an isolated `CodeCart\Core\Unishop2Compatibility` adapter for UniShop2 v3.6.6.0. The adapter activates only when UniShop2 is the active theme and does not affect the default CodeCart storefront.
- Restored the functional equivalents of 13 UniShop2 OCMOD contracts that no longer match the modern CodeCart catalog core: mega-menu (4), category module (3), category page (3), product options (2) and banner (1).
- Preserved CodeCart batched category loading instead of reintroducing UniShop/OpenCart N+1 `getCategories()` loops or legacy category cache behavior.
- UniShop menu data now receives icon/banner metadata, second-level images, third-level children/limits and landing-link compatibility from the existing CodeCart category tree.
- UniShop category pages receive subcategory visibility/images and `uni_banner_in_category` support without replacing CodeCart category routing/SEO logic.
- UniShop product options receive compatible ended/maximum/image-size fields while preserving CodeCart option-image switching and modern image pipeline.
- Standard banner items expose UniShop `width`/`height` aliases while retaining CodeCart slider effects and third-party Swiper fallback.
- OCMOD diagnostics recognize the 13 superseded UniShop2 search operations as satisfied by the CodeCart compatibility layer instead of reporting false compatibility warnings.
- UniShop2 fix Installer/Twig operations already implemented by CodeCart are likewise recognized as compatibility-satisfied; the legacy global SQL-mode relaxation is intentionally not claimed as equivalent.

# CodeCart PRO 3.0.6.0 Build 1.7.7

Upgrade compatibility
- Fixed OpenCart 3.0.5.1 and ocStore 3.0.5.0-Beta upgrades on MySQL 8.x when legacy product.date_available still uses DEFAULT '0000-00-00'. The migration preserves existing row values, replaces only the invalid default with 1970-01-01 in a temporary compatible session and restores the original SQL mode after the InnoDB conversion.
- Fixed legacy MyISAM/utf8mb4 upload.code index creation on MySQL where a full VARCHAR(255) key exceeds the 1000-byte MyISAM key limit. CodeCart now uses a compatible 191-character prefix index.
- Exact source fingerprints were verified against the supplied OpenCart 3.0.5.1 and ocStore 3.0.5.0-Beta archives before upgrade testing.
- Full upgrade QA passed for OpenCart 3.0.5.1 and ocStore 3.0.5.0-Beta on both MariaDB 10.11 and MySQL 8.4.
- Upgrade QA verified preservation of products, categories, SEO URLs, store settings, active theme, customers, orders, OCMOD records and Events. ocStore-specific meta_h1 and blog data were also preserved.
- Re-running Installer 2.0 UPDATE passed idempotency checks and storefront/admin login remained functional after upgrade.

# CodeCart PRO 3.0.6.0 Build 1.7.6

Upgrade reliability
- Installer 2.0 ignores stale legacy OpenCart/ocStore migration files left on disk by overlay updates and executes only CodeCart 3.0.6 migrations (3052+).
- Fixes the reproduced ocStore 3.0.4.1 upgrade failure where legacy migration 1010 was re-run and queried the already-removed url_alias table.
- Clean-install behavior is unchanged; UPDATE continues to preserve config, active theme, catalog and SEO data.

Runtime QA
- The production artifact passed clean-install runtime QA on MariaDB 10.11 and MySQL 8.4, including storefront/admin HTTP smoke, Redis tagged cache, persistent mail queue, SMTP delivery and Scheduler.
- Deep commerce release QA passed on MariaDB 10.11 and MySQL 8.4: full guest checkout, COD confirmation, duplicate payment-confirm protection, stock decrement/restore, one-use coupon enforcement, voucher accounting, order cancellation reversal and repeat-cancel idempotency.
- PayPal capture/refund/void/reauthorize/tracker mutation endpoints are gated by POST, user_token/hash_equals and modify permission before provider access. Provider-side refund/capture remains an external sandbox/live integration test requiring PayPal credentials.
- Build 1.7.6 passed the PHP 8.1, 8.2, 8.3, 8.4 and 8.5 release matrix and the ocStore 3.0.4.1 upgrade runtime pipeline.

# CodeCart PRO 3.0.6.0 Build 1.7.5

Localization / QA
- Removed confirmed hardcoded Ukrainian UI strings from first-party PHP/Twig/JavaScript paths and routed them through `uk-ua` language files.
- Storefront/admin generic icon-button tooltips now receive Ukrainian labels from language files via a small JSON i18n bridge; JavaScript no longer embeds Ukrainian UI copies.
- Form Builder and Purchase Blocks Ukrainian presets now come from PHP language files; JavaScript remains language-neutral for Ukrainian.
- Stock-notification Ukrainian subject/body/button strings moved to `catalog/language/uk-ua/cron/stock_notify.php`.
- Google Login secret/copy tooltips and admin report-mail subject/body moved to Ukrainian language files.
- Carrier-directory Ukrainian fallback branch label is supplied by the loaded shipping language file instead of the system library.
- Product page no longer identifies the bundled question form by a translated Ukrainian module name; it resolves the `codecart_form` module from the stable module code and form field schema.
- Added `tools/check_uk_locale.php` quality gate to detect new first-party hardcoded Ukrainian UI text outside language files while excluding demo seed content, transliteration dictionaries and third-party locales.
- Synchronized package/docs current build marker to 1.7.5; README latest-build line corrected from the stale 1.6.9 value.

# CodeCart PRO 3.0.6.0 Build 1.7.4

## Transactional mail queue
- System transactional mail is queued in the persistent CodeCart queue instead of waiting for SMTP inside checkout/account requests.
- Queue payloads contain message content and store_id only; SMTP credentials are resolved at worker execution time and are never persisted in queue arguments.
- Added `core.queue.worker` Scheduler task and `cron/mail_delivery` worker with retry/stale recovery inherited from the shared Queue service.
- Background campaign/stock-notify workers keep synchronous transport semantics so their own sent/failed state remains accurate.
- Attachment-bearing messages remain synchronous to avoid losing temporary attachment files.
- Queue infrastructure failure falls back to synchronous delivery so transactional mail is not silently lost.

# CodeCart PRO 3.0.6.0 Build 1.7.3

Security
- Added explicit POST + modify permission + user_token validation to PayPal capture, reauthorize, void, refund and tracking mutation endpoints.
- Completed checkout attribute-context escaping in CodeCart and default themes for dynamic form values while preserving compatibility with global Twig autoescape=false.
- SecurityAudit now uses the trusted-proxy-aware CodeCart client IP resolver.
- Replaced the remaining PayPal internal mt_rand token helper with random_int.

Commerce
- Made editOrder reversal and replacement atomic: processed-order stock, totals and affiliate reversal now occur inside the same transaction as the edited order write.

Performance
- Removed the standard catalog getProducts()/getProductSpecials N+1 hydration path. Product pages of IDs are now hydrated in one batch query while preserving the historical full result contract.

Reliability
- Added stale-processing recovery for persistent mail campaign and stock notification workers. Rows interrupted for more than 15 minutes are safely retried or failed after the retry limit.
- Stock notification worker now catches transport exceptions without aborting the whole batch.
- Preflight explicitly reports XMLReader availability and warns when very large XLSX imports will fall back to the higher-memory SimpleXML path.

# CodeCart PRO 3.0.6.0 Build 1.7.2

## Build 1.7.2

### Performance / scalability
- Cart::getProducts() now batch-loads cart products, selected option metadata/values, active discounts, specials, rewards, downloads and recurring profiles instead of issuing SQL per cart row/option. Request-local memoization from 1.7.0 is preserved.
- HTML sitemap builds the product-category tree from one getAllCategories() query instead of recursive category SQL calls. Blog category access already uses the model's in-request tree cache.
- Catalog Transfer adds XMLReader-based bounded-memory XLSX sheet streaming. Preflight and import process workbook rows incrementally when XMLReader is available; the legacy SimpleXML reader remains a compatibility fallback.

### Marketing / mail
- Back-in-stock subscriptions now support stock-tracked product option values (for example size/color): unavailable values remain selectable for notification, the subscription stores the selected stock values, and Scheduler sends only when the product and selected values are available again.
- Mass mailing is now persistent: clicking Send creates a campaign and durable recipient queue instead of requiring the admin browser tab to stay open through recursive AJAX batches.
- Scheduler task core.mail.campaign processes queued mail in bounded batches with retry, per-store SMTP settings, progress counters and recent campaign history in Marketing → Mail.
- Personalized 256-bit unsubscribe tokens are generated per queued recipient. Unsubscribe creates a persistent suppression entry and disables customer.newsletter when a customer account is known. Suppressed addresses are excluded even from the explicit “All customers” marketing audience.

### Database
- Presentation schema 26 adds mail_campaign, mail_campaign_queue and mail_suppression tables and extends stock_notify with option-specific subscription metadata.

### QA scope
- Modified PHP files were syntax-checked on PHP 8.4.23. XMLReader/Zip/SimpleXML runtime streaming still requires a server with the normal php-xml/php-zip extensions; this container lacks those extensions, so the new streaming path is static-reviewed here and remains part of the release runtime matrix.

---

# CodeCart PRO 3.0.6.0 Build 1.7.1

## Build 1.7.1

### Fixed / completed from independent audit
- Online visitors now use a hashed session visitor key instead of IP as the primary identity. Multiple users behind the same NAT/IP no longer overwrite each other; IP remains indexed for filtering. Presentation schema 24 migrates existing rows safely.
- Category Wall loads all visible child categories in one query instead of one query per parent card.
- Product page options load all option values in one query instead of one query per option group.
- Stock-notification administration now has filters, sortable columns, configurable page size, checkboxes, bulk delete/retry, statuses and standard CodeCart table styling.
- Catalog Transfer download now uses Response::setFile() and never loads the completed XLSX/ZIP into PHP memory.
- Catalog Transfer XLSX export writes worksheet XML to temporary files row-by-row and reads database rows in 1000-row batches; it no longer builds whole worksheet XML strings or table rowsets in PHP memory.
- Catalog Transfer pre-import SQL snapshot now reads source tables in 500-row batches instead of SELECT * into one PHP result set.

### Scope note
- XLSX import parsing itself still uses the compatibility SimpleXML reader and therefore remains size-limited by PHP memory for very large workbooks. Export and pre-import snapshot are now bounded-memory; streaming import is tracked separately because it requires an independent parser/refactor.

---

---

# CodeCart PRO 3.0.6.0 Build 1.7.0

## Build 1.7.0

### Fixed
- Google Merchant Feed: initialized the per-generation product type cache before use, preventing PHP 8 TypeError during feed generation.
- Marketing mail: selected store SMTP engine/host/user/password/port/timeout are now used for multistore sends instead of the current admin store SMTP settings.
- Gift vouchers: storefront/API-generated codes now use cryptographically secure random_bytes(); voucher/order_voucher code columns support 32 characters; fresh installs enforce UNIQUE voucher codes and UPDATE adds the unique index when existing data has no duplicates.
- PayPal and upload security tokens: replaced mt_rand()/uniqid()-based token generation with random_bytes()/random_int().
- Cart hot path: request-local getProducts() memoization avoids repeating the full product/options/discount/download query chain multiple times during the same checkout request; mutations invalidate the cache.

### Database
- Presentation schema 23: widens voucher and order_voucher code fields and adds a unique voucher-code index when safe.

### QA note
- This build fixes the highest-priority blockers identified by the independent Build 1.6.8/1.6.9 audit. Remaining audit items are tracked separately; this entry does not claim full production-ready status.

---

---

## Legacy 1.6.x history

The previous changelog snapshot had several older 1.6.x sections accidentally relabeled as Build 1.6.9 during a bulk version update. Those labels were not reliable release provenance, so they are no longer presented as exact per-build history. The underlying functionality remains documented in source comments and later verified 1.7.x entries. Future releases preserve historical headings instead of globally replacing version strings.