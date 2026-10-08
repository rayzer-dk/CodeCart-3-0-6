# UniShop2 compatibility — Build 2.0.4

[Українська](UNISHOP2_COMPATIBILITY.uk.md) · [All guides](README.md) · [Community](https://t.me/+tUZNEgY3aUk4MGIy)

**Back up the website/database before installing or updating a theme.** UniShop2 3.6.6.0 requires PHP 8.1–8.3 and a compatible ionCube Loader according to its own requirements. CodeCart core's PHP 8.4/8.5 support does not extend those theme requirements.

On a staging copy, follow the original theme instructions: install `unishop2_fix.ocmod.zip`, refresh Modifications, install `unishop2_v3.6.6.0.ocmod.zip`, install/enable/configure the theme, then refresh Modifications again. Review diagnostics and test product options, gallery, categories, menu, cart and checkout before production activation.

CodeCart provides installer path expansion, Twig loader behavior and a built-in adapter for legacy menu/category/product option/banner contracts. The adapter runs only when `config_theme=unishop2`, its settings exist and the theme is not disabled. Installation of unused theme files does not activate it. See the [developer guide](COMPATIBILITY_FRAMEWORK.md).

Do not install the optional `webp_img.ocmod.zip`: CodeCart has its own image pipeline. `fix_og.ocmod.zip` is unnecessary because CodeCart provides OpenGraph handling. `unishop2_tool.ocmod.zip` is optional demo/import tooling, not a production requirement. Default SQL connection mode follows OpenCart compatibility; strict mode is opt-in after extension testing.

Framework/OCMOD contracts and a focused missing-news HTTP scenario were checked. The latter used original controller/model files, a minimal empty news schema and CodeCart Theme. These checks do not certify the complete commercial UniShop2 UI or every module combination. Third-party theme source is not bundled with CodeCart.

Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [CodeCart PRO community](https://t.me/+tUZNEgY3aUk4MGIy)
