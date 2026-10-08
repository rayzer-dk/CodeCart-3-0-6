# CodeCart Compatibility Framework — Build 2.0.4

[Українська](COMPATIBILITY_FRAMEWORK.uk.md) · [All guides](README.md) · [Community](https://t.me/+tUZNEgY3aUk4MGIy)

## Purpose

CodeCart keeps the modern Core independent from third-party theme and extension internals. Compatibility is provided through stable runtime contracts and isolated adapters. An adapter may translate CodeCart data into the structure expected by a legacy OpenCart/ocStore theme without restoring old N+1 queries, obsolete libraries or old controller implementations.

## Runtime architecture

Core controller -> CompatibilityFramework contract -> active adapters -> resulting payload -> view/controller continuation.

Built-in adapters live in `system/library/codecart/src/`. UniShop2 is the first built-in adapter and is identified as `theme.unishop2`.

Installable adapters are delivered as CodeCart modern extensions under `system/extension/<code>/`. They do not patch Core files. Their `manifest.json` declares the adapter classes in the `compatibility.adapters` capability.

Example manifest fragment:

```json
{
  "manifest_version": 1,
  "code": "vendor_theme_compat",
  "name": "Vendor Theme Compatibility",
  "version": "1.0.0",
  "namespace": "Vendor\\ThemeCompat",
  "compatibility": {
    "adapters": [
      "Vendor\\ThemeCompat\\ThemeAdapter"
    ]
  }
}
```

The class must be inside the extension's declared namespace and implement `CodeCart\\Core\\CompatibilityAdapterInterface`. Classes outside the owning namespace and classes that do not implement the interface are rejected.

Minimal adapter shape:

```php
<?php
namespace Vendor\ThemeCompat;

use CodeCart\Core\CompatibilityAdapterInterface;

final class ThemeAdapter implements CompatibilityAdapterInterface {
    private $registry;

    public function __construct($registry) {
        $this->registry = $registry;
    }

    public function id(): string {
        return 'theme.vendor';
    }

    public function priority(): int {
        return 200;
    }

    public function active(): bool {
        return (string)$this->registry->get('config')->get('config_theme') === 'vendor_theme';
    }

    public function adapt(string $contract, array $payload): array {
        if ($contract === 'catalog.menu.data') {
            // Translate only the fields required by the theme.
        }
        return $payload;
    }
}
```

## Current stable contracts

`catalog.menu.data`
Receives and returns the menu view data.

`catalog.category_module.full_tree`
Payload contains boolean `value`. An adapter may request the complete already-batched category tree without reintroducing per-category SQL queries.

`catalog.category_page.subcategories`
Payload contains `enabled`, `images` and `category_id`.

`catalog.category_page.banner_in_category`
Payload contains `enabled`, `page` and `category_id`.

`catalog.product.option_image_size`
Payload contains `width`, `height` and `product_id`.

`catalog.product.option_value`
Payload contains `value`, `raw_price` and `product_id`.

`catalog.banner.item`
Payload contains `item`, `width`, `height` and `banner_id`.

Contracts are additive. Existing field meanings must not be changed within the CodeCart 3.0.6 compatibility line.

## Safety rules

Adapters are inactive unless their own `active()` detection succeeds. Installed but unused themes must not execute storefront compatibility logic.

An adapter exception is isolated and logged as `[CodeCart Compatibility]`; it must not make the storefront unavailable.

Compatibility packages must not weaken SQL mode, overwrite Core controllers, restore obsolete libraries or use OCMOD when a stable Compatibility Framework contract is available.

Adapters that replace obsolete OCMOD search points may optionally expose `satisfiesOcmod(string $code, string $file, string $search): bool`. The Modification diagnostics screen queries this through CompatibilityFramework, so a missing legacy search can be reported as satisfied by an adapter instead of as a false incompatibility warning.

When a missing capability cannot be represented by an existing contract, add one small generic contract to Core rather than a theme-specific `if` statement. Theme-specific behavior stays in the adapter.

## UniShop2

UniShop2 has a built-in adapter with framework contract checks. These checks do not certify the complete commercial theme storefront. Its adapter is activated only when the active theme is `unishop2`, its settings exist and it is not explicitly disabled.

The adapter covers the CodeCart equivalents of UniShop2's legacy OpenCart/ocStore OCMOD assumptions for menu, category module, category page, product option values/image sizes and banner item dimensions.

The optional UniShop WebP and OG compatibility modifications are not part of this adapter because CodeCart already provides its own image/WebP/AVIF and OpenGraph implementations.


Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [CodeCart PRO community](https://t.me/+tUZNEgY3aUk4MGIy)
