CodeCart PRO 3.0.6.0 Build 1.8.4

Create and verify a full files/database backup. Upload only the contents of upload/ to the web root. Open /install/ and follow Installer 2.0. PHP 8.1–8.5 is supported.

For an existing store use UPDATE, not a clean install and not install/opencart.sql. Existing config.php, admin/config.php and persistent storage data are preserved by the update flow. After installation or update, refresh Modifications; optional automatic OCMOD refresh can be enabled later in Extensions > Installer.

Branding: system interfaces use CodeCart PRO. On a clean install the bundled CodeCart PRO logo/favicon are initial defaults; once the merchant configures the store logo/favicon, those store assets are preserved by UPDATE and the configured favicon is also used in administration.

### CodeCart Theme
On a clean installation, CodeCart Theme is installed and selected as the storefront theme by default. The legacy `default` theme remains installed as a compatibility fallback.


## Optional environment overrides
For container/CI deployments, runtime DB connection values may override config.php with CODECART_DB_DRIVER, CODECART_DB_HOSTNAME, CODECART_DB_USERNAME, CODECART_DB_PASSWORD, CODECART_DB_DATABASE and CODECART_DB_PORT. Empty variables are ignored. DB_PREFIX remains in config.php for OpenCart 3 compatibility.
