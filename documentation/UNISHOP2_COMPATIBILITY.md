CodeCart PRO 3.0.6.0 Build 1.8.3 — UniShop2 v3.6.6.0 compatibility

Supported target
- UniShop2 v3.6.6.0 on PHP 8.1–8.3 with a compatible ionCube Loader. UniShop2 itself does not declare PHP 8.4/8.5 support, even though CodeCart core supports PHP 8.1–8.5.

Installation order
1. Install `unishop2_fix.ocmod.zip`.
2. Refresh Modifications.
3. Install `unishop2_v3.6.6.0.ocmod.zip`.
4. Install/enable and configure UniShop2.
5. Refresh Modifications again.

CodeCart compatibility behavior
- Installer path expansion from `unishop2_fix` is already provided by CodeCart.
- Twig auto_reload + ArrayLoader/FilesystemLoader/ChainLoader behavior is already provided by CodeCart.
- CodeCart does not globally downgrade SQL mode to `NO_ENGINE_SUBSTITUTION`; global strict-mode removal from the UniShop fix is intentionally not applied by the compatibility layer.
- The 13 UniShop2 v3.6.6.0 catalog operations whose old OpenCart/ocStore anchors no longer exist are implemented by `CodeCart\Core\Unishop2Compatibility` and reported by OCMOD diagnostics as compatibility-satisfied.
- `webp_img.ocmod.zip` should not be installed on CodeCart because CodeCart has its own WebP/AVIF/image pipeline.
- `fix_og.ocmod.zip` is not required on CodeCart because CodeCart already implements OG image handling.
- `unishop2_tool.ocmod.zip` is optional demo/import tooling and is not required for production compatibility.

OFF means OFF
The UniShop2 adapter runs only when `config_theme=unishop2` and the theme is enabled. Merely having UniShop2 files installed does not change the catalog behavior.
