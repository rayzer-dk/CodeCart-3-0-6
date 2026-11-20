# Dead-code audit

GitHub Actions workflow: `.github/workflows/dead-code-audit.yml`.

The established PHPStan checks remain unchanged. This separate audit runs on pushes, pull requests, and manual dispatch. Its fixture step is **blocking**: it must detect a known unused local variable, unused private property, and unused private method. Catalog and admin dead-code inventories are now **blocking** after reaching zero diagnostics. Core and bundled bonus modules remain **advisory** pending review of dynamic framework and third-party API findings. Full logs are saved as a GitHub Actions artifact.

Analysis uses PHPStan 2.3+ bleeding-edge rules and the existing OpenCart model stub generator. It covers first-party catalog/admin controllers and models, not third-party vendor files. It also inherits PHPStan's existing unreachable-code and type checks.

Locally, install the isolated tools and the runtime dependencies, then generate the model stubs:

```sh
composer install --working-dir=tools/quality
composer install --no-dev
php tools/quality/generate_phpstan_models.php catalog
php tools/quality/generate_phpstan_models.php admin
tools/quality/vendor/bin/phpstan analyse -c phpstan.codecart.catalog.dead-code.neon.dist --no-progress
tools/quality/vendor/bin/phpstan analyse -c phpstan.codecart.admin.dead-code.neon.dist --no-progress
```

OpenCart/ocStore routes, OCMOD patches, Events, dynamic model loading and template variables cannot all be resolved from ordinary PHP static analysis. Do not delete a reported member without checking its use in dynamic dispatch, extensions and themes. PHPStan does not analyze Twig undefined context keys here: PhpStorm's local Twig/OpenCart inspections can assist, but they are not GitHub CI validation. Do not treat advisory findings as proof that code is unused.
