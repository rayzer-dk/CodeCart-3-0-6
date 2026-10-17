# PHPStan for CodeCart/OpenCart (independent storefront/admin scans)

The proposed generic `scanFiles: [system/startup.php, phpstan-stubs.php]`
solution is **not** safe for CodeCart. The real `Controller`,
`Model` and `Registry` classes already exist; `DB` wraps the DB
driver, so `$this->db` must be typed as `DB`, not `DB\\MySQLi|DB`.
`Registry` already has service annotations and both base classes
have `@mixin Registry`.

The loader turns `catalog/product` into the runtime property
`model_catalog_product` backed by a `Proxy`. We generate
**typed property annotations only from existing model files and real
class declarations**, rather than ignoring all undefined properties.
In particular an undefined `getProductsCustomFilter()` method must
remain visible to PHPStan if the model class is correctly discovered.
A missing model or an unknown route is not quietly fabricated.

Storefront and admin must be analysed separately, because both use
same-named model classes with different definitions.

To run from repository root on PHP 8.4:
```sh
composer install --working-dir=tools/quality
php tools/quality/generate_phpstan_models.php catalog
php tools/quality/generate_phpstan_models.php admin
tools/quality/vendor/bin/phpstan analyse -c phpstan.codecart.catalog.neon.dist --memory-limit=2G
tools/quality/vendor/bin/phpstan analyse -c phpstan.codecart.admin.neon.dist --memory-limit=2G
```

The initially selected level 5 is advisory for the legacy codebase;
it is not an assertion that the full project already passes at level 5.
Do not add `ignoreErrors` for all Controller/Model properties.
Legacy issues should be triaged individually; a reviewed,
narrowly scoped baseline is permissible only when justified.

The generated stubs are analysis-only: they are never loaded by PHP
at runtime and live in the ignored `build/` directory. They must not
be published with the CMS release. Run CI negative-method fixtures to
verify that unknown calls are actually detected, not hidden.
