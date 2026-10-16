# Границы и зависимости

Baseline import владеет только файлами ZIP; existing developer tooling/.github остаются. Tools зависят от текущих шаблонов, контроллеров, языков и установленных Composer runtime. OCMOD проверять через тот же алгоритм поиска/замены что admin/controller/marketplace/modification.php; модели и JS-обработчики сортировки не менять без доказательств. CSS comments не меняют декларации. История/интеграция/лицензии сторонних библиотек сохраняются.

Источник новых проблем: AUDIT.md предыдущего аудита плюс пользовательский текст OCMOD. Новые claims должны иметь наблюдаемые доказательства. Изменения source → integrity manifest → embedded documentation ZIP → package → reopen ZIP/CRC/hash. Прямой push main разрешен пользователем. Публикация GitHub release/действия на production не запрошены.
