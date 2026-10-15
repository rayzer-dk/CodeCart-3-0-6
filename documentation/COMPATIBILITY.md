# Compatibility
[Українська](COMPATIBILITY.uk.md) · [All guides](README.md) · [Community](https://t.me/+tUZNEgY3aUk4MGIy)

CodeCart preserves OpenCart/ocStore 3.x MVC-L routes, Events and OCMOD integration. Use PHP 8.1–8.3 as the compatibility target for existing extensions. Core also supports PHP 8.4/8.5 and has CI checks on these versions; individual modules, commercial themes and ionCube packages have their own requirements.

Upgrade runtime coverage exists for ocStore 3.0.4.1, OpenCart 3.0.5.1 and ocStore 3.0.5.0-Beta. Test the exact store on staging before deployment; framework tests and migration coverage do not certify all extensions, live payment APIs or third-party storefront UI. Back up website files and database before changing the system.

Clean installation activates CodeCart Theme only. Upgrade preserves every existing store's active theme, configuration, merchant images and persistent storage. Review Modifications diagnostics after any update.

- [UniShop2 requirements and coverage](UNISHOP2_COMPATIBILITY.md)
- [Compatibility Framework for developers](COMPATIBILITY_FRAMEWORK.md)
- [Upgrade and repair](UPGRADE.md)
- [nginx hosting configuration](NGINX.md)

Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [CodeCart PRO community](https://t.me/+tUZNEgY3aUk4MGIy)
