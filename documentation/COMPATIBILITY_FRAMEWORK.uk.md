# Compatibility Framework — Build 2.0.4

[English](COMPATIBILITY_FRAMEWORK.md) · [Усі інструкції](README.uk.md) · [Спільнота](https://t.me/+tUZNEgY3aUk4MGIy)

## Призначення та архітектура

CodeCart відокремлює сучасне ядро від внутрішньої логіки сторонніх тем/розширень. Сумісність забезпечують стабільні runtime-контракти й ізольовані адаптери. Адаптер перетворює дані у формат старої теми OpenCart/ocStore без повернення N+1-запитів, застарілих бібліотек або старих controller.

Потік: controller ядра → контракт CompatibilityFramework → активні адаптери → результуючі дані → продовження controller/view.

Вбудовані адаптери містяться в `system/library/codecart/src/`; UniShop2 має ID `theme.unishop2`. Встановлювані адаптери постачаються як modern extensions у `system/extension/<code>/` без зміни файлів ядра. Їхній `manifest.json` декларує класи через `compatibility.adapters`.

Приклад manifest:

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
Клас має належати заявленому namespace розширення та реалізувати `CodeCart\Core\CompatibilityAdapterInterface`. Інші класи відхиляються. Приклад мінімального адаптера:

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
## Поточні стабільні контракти

| Контракт | Дані |
|---|---|
| `catalog.menu.data` | Вхідні/вихідні дані меню |
| `catalog.category_module.full_tree` | Boolean `value`; запит повного вже пакетно завантаженого дерева без SQL для кожної категорії |
| `catalog.category_page.subcategories` | `enabled`, `images`, `category_id` |
| `catalog.category_page.banner_in_category` | `enabled`, `page`, `category_id` |
| `catalog.product.option_image_size` | `width`, `height`, `product_id` |
| `catalog.product.option_value` | `value`, `raw_price`, `product_id` |
| `catalog.banner.item` | `item`, `width`, `height`, `banner_id` |

Контракти розширюються додаванням. Значення наявних полів не повинні змінюватися в лінії сумісності CodeCart 3.0.6.

## Безпека адаптерів

Адаптер неактивний, доки його `active()` не підтвердить застосування. Встановлені, але невикористовувані теми не повинні запускати логіку сумісності вітрини.

Виняток адаптера ізолюється та записується з `[CodeCart Compatibility]`; він не повинен зупиняти вітрину. Пакети сумісності не мають послаблювати SQL mode, підмінювати controller ядра, повертати старі бібліотеки або застосовувати OCMOD там, де доступний стабільний контракт.

Адаптери замість застарілих OCMOD search можуть реалізувати `satisfiesOcmod(string $code, string $file, string $search): bool`. Діагностика модифікаторів перевіряє це через CompatibilityFramework та може позначити відсутній старий search як забезпечений адаптером.

Якщо наявний контракт не покриває потрібну можливість, додайте до ядра один невеликий загальний контракт. Специфічна логіка теми залишається в адаптері.

## UniShop2

UniShop2 має вбудований адаптер із перевірками framework-контрактів; вони не підтверджують повний UI комерційної теми. Адаптер активний лише для `unishop2` із наявними налаштуваннями, якщо тему явно не вимкнено. Він покриває старі припущення OCMOD для меню, модуля/сторінки категорії, опцій товару та розмірів банерів.

Необов'язкові WebP та OG модифікатори UniShop2 не входять до адаптера: CodeCart має власну обробку зображень і OpenGraph. Дивіться [інструкцію UniShop2](UNISHOP2_COMPATIBILITY.uk.md).

Підтримка: [support@codecartpro.com](mailto:support@codecartpro.com) · [Спільнота CodeCart PRO](https://t.me/+tUZNEgY3aUk4MGIy)
