# CodeCart 3.0.6

Private development repository for CodeCart 3.0.6.

Current source baseline: CodeCart PRO 3.0.6.0 Build 1.8.4.

## Release model

- PHP runtime target: 8.1–8.5
- OpenCart/ocStore compatibility layer retained
- Composer dependencies are installed into `upload/system/storage/vendor`
- Frontend assets are shipped as source + verified `.min.css/.min.js` files
- Node.js/Vite is intentionally not required by the current architecture
- Production releases are built by GitHub Actions and must pass release checks before packaging

## Repository policy

Do not commit real `config.php`, `admin/config.php`, credentials, API keys, runtime cache, logs, sessions or generated store data.

## Source status

Source import status: complete for CodeCart PRO 3.0.6.0 Build 1.8.4. Production CI reconstructs vendor from composer.lock and validates the source on PHP 8.1–8.5 before packaging.

## Validated upgrade baselines

Validated upgrade paths include ocStore 3.0.4.1, OpenCart 3.0.5.1 and ocStore 3.0.5.0-Beta. OpenCart 3.0.5.1 and ocStore 3.0.5.0-Beta are tested on both MariaDB 10.11 and MySQL 8.4 with preservation and idempotency checks.


Optional bonus: `bonuses/Monobank_Payment_Modern_v1.1.0.ocmod.zip` demonstrates a Modern Extension payment integration and is not installed by default.


Theme policy (CodeCart 3.0.6.x):
- Fresh installation: CodeCart Theme is the only registered/active system theme. The bundled `default` theme files are compatibility fallback only.
- Upgrade from OpenCart/ocStore: the existing active theme is preserved unchanged. CodeCart Theme is added as an additional theme and is not enabled automatically. Existing Default/third-party themes are not removed or overwritten.
