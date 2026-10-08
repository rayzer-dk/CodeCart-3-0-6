# CodeCart PRO 3.0.6.0 — Build 2.0.4

CodeCart PRO is an e-commerce platform built on OpenCart/ocStore 3.x, with CodeCart Theme, compatibility adapters and optional lost URL monitoring.

[Українська](README.uk.md) · [Documentation](documentation/README.md) · [CodeCart PRO community](https://t.me/+tUZNEgY3aUk4MGIy)

**Create and verify a complete backup of website files and the database before installation or update.** Use the production release ZIP with bundled Composer dependencies. GitHub's source “Download ZIP” is not a ready installation package: its dependencies must first be installed with Composer.

Upload only the contents of `upload/` to the web root. Keep documentation, tools and repository metadata outside the public website.

- New store: [Installation](documentation/INSTALL.md).
- Existing store: [Upgrade and repair](documentation/UPGRADE.md); preserve configuration, merchant images and persistent storage.
- Hosting: PHP 8.1–8.3 is the recommended compatibility target for OpenCart/ocStore extensions. Core also supports PHP 8.4/8.5 and has CI checks on these versions; third-party modules and ionCube themes have their own requirements. See [nginx](documentation/NGINX.md) and [UniShop2](documentation/UNISHOP2_COMPATIBILITY.md).

A clean installation registers and activates CodeCart Theme only; bundled `default` files provide a compatibility fallback. An upgrade preserves the active theme of every existing store and adds CodeCart Theme as an option.

Canonical repository: [CodeCartPro/CodeCartPro-3.0.6.0](https://github.com/CodeCartPro/CodeCartPro-3.0.6.0). Work uses `main`; see [GitHub publishing](documentation/GITHUB.md). Source dependencies are pinned in `composer.lock` and installed into `upload/system/storage/vendor` during production packaging. Node.js/Vite is not required for installation.

Never publish real configuration files, credentials, API/license keys, runtime storage, sessions or customer data.

Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [codecartpro.com](https://codecartpro.com) · [Join the community](https://t.me/+tUZNEgY3aUk4MGIy)

[Storefront and administrator demo](documentation/DEMO.md)
