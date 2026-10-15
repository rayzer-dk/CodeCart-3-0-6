# CodeCart 3.0.6

**CodeCart** is a free, open-source e-commerce content management system for creating and managing online stores. It builds on the familiar OpenCart/ocStore 3.x architecture while combining a modern responsive storefront, a comprehensive administration panel, multilingual SEO routing and a foundation for developing independent extensions.

The platform is intended for store owners, merchants, agencies and developers who need control of their shop and source code without being tied to a hosted service. The project is developed by **CodeCart PRO** and distributed under GPL-3.0; third-party components retain their respective licenses.

[Українська](README.uk.md) · [Documentation](documentation/README.md) · [Demo](documentation/DEMO.md) · [Community](https://t.me/+tUZNEgY3aUk4MGIy)

## Storefront and shopping experience

- **CodeCart Theme:** a responsive storefront with configurable colors, typography, layout, product cards, navigation and light/dark display modes. A bundled `default` theme provides an OpenCart compatibility fallback.
- **Product catalog:** categories, manufacturers, product variants and options, images, attributes, filters, featured products, related products, special offers and customer reviews.
- **Ready-to-use content blocks:** category and manufacturer walls, responsive grids and carousels, featured/latest/popular products, banners, articles and information pages. Carousel navigation works on desktop and mobile.
- **Shopping and checkout:** shopping cart, customer accounts, guest checkout, order processing, addresses, delivery and payment methods, discounts, coupons, vouchers and transactional notifications through the standard OpenCart extension interfaces.
- **Store presentation:** configurable header, menus, product information, contact details, storefront widgets and optional additional product tabs.

## Multilingual SEO and content

- Manage multiple store languages, including Ukrainian, English and Russian in the included language catalogs; available languages are configurable in administration.
- Built-in language URL folders with configurable prefixes, multilingual SEO keywords, canonical URLs and hreflang output. An extra LangDir extension is not required when using native CodeCart language folders.
- SeoPro-compatible URL routing, category paths, configurable trailing slashes and normal not-found handling.
- SEO URLs and metadata for products, categories, manufacturers, articles and information pages; XML sitemap and other feed modules are provided through the standard extension mechanism.
- Optional missing-URL monitoring, manual redirects and cleanup through the built-in scheduler. Monitoring is off until activated.

## Product files and digital downloads

- Attach publicly downloadable manuals, specifications, certificates and other documents to products using the product's **Links** tab. A document can be associated with more than one product.
- Manage public documents independently, with localized titles, controlled file delivery and download statistics. Supported formats include PDF, common Office formats, image files, TXT and ZIP, subject to upload limits.
- Keep **paid digital downloads** separate from public documents: customer access to paid files continues to follow the completed-order authorization rules.

## Administration, operations and extensibility

- Manage inventory, pricing, orders, customers, promotions, content, downloads, currencies, languages, stores and user permissions from the administration panel.
- Built-in reporting, diagnostics and database/upgrade utilities; maintenance features help identify configuration and compatibility issues.
- Shared task scheduler and CLI worker entry points for supported background jobs and maintenance. Actual cron execution requires one-time server configuration.
- OpenCart/ocStore 3.x–style MVC-L, Twig, Events and OCMOD architecture, preserving established extension workflows. Third-party extension and theme compatibility must be checked individually.
- MySQL/MariaDB storage, configurable cache backends and bundled Composer dependencies in production packages. Runtime storage, security-sensitive configuration and uploaded private data must be protected on deployment.

## System requirements and compatibility

CodeCart uses PHP and MySQL/MariaDB, with a modern web server such as Apache or nginx. **PHP 8.1–8.3 is recommended for broad third-party OpenCart/ocStore extension compatibility.** Core CI and compatibility checks cover PHP 8.4/8.5, but not every commercial module or encoded third-party theme. See [platform requirements](documentation/INSTALL.md), [compatibility](documentation/COMPATIBILITY.md) and [UniShop2 notes](documentation/UNISHOP2_COMPATIBILITY.md).

A clean installation enables CodeCart Theme. Updating an existing store preserves the installed stores' selected theme and settings; test custom themes, modifications and integrations on staging before a production upgrade.

## Installation and updates

**Back up and verify the website files and database before any installation or update.**

For new installations, download the **full production release ZIP with Composer dependencies**, extract it and upload only the **contents of `upload/`** to the web root. Follow the [installation instructions](documentation/INSTALL.md). A repository source ZIP is **not** an install-ready package; its dependencies must first be installed.

For existing shops, follow the [upgrade and recovery guide](documentation/UPGRADE.md). Preserve the site's `config.php`, `admin/config.php`, custom images, persistent storage, database and installed modifications. Never import a fresh-install database into an existing store.

Developers working in browser-based GitHub Codespaces can follow the [source update guide](documentation/GITHUB.md). Full production packages are published as release attachments, while this repository tracks the source.

## Documentation and support

[Documentation index](documentation/README.md) · [Installation](documentation/INSTALL.md) · [Upgrade](documentation/UPGRADE.md) · [Hosting](documentation/NGINX.md) · [Compatibility](documentation/COMPATIBILITY.md) · [Demo](documentation/DEMO.md)

Website: [codecartpro.com](https://codecartpro.com) · Support: [support@codecartpro.com](mailto:support@codecartpro.com) · Community: [CodeCart PRO](https://t.me/+tUZNEgY3aUk4MGIy) · [GPL-3.0 License](LICENSE)

**Security:** Do not commit live credentials, API/license keys, production configuration, sessions, logs, runtime storage or customer data. Use a reviewed production release with pinned dependencies for real deployments.
