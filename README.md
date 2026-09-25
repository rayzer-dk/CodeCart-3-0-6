# CodeCart 3.0.6

Private development repository for CodeCart 3.0.6.

Current source baseline: CodeCart PRO 3.0.6.0 Build 1.7.5.

## Release model

- PHP runtime target: 8.1–8.5
- OpenCart/ocStore compatibility layer retained
- Composer dependencies are installed into `upload/system/storage/vendor`
- Frontend assets are shipped as source + verified `.min.css/.min.js` files
- Node.js/Vite is intentionally not required by the current architecture
- Production releases are built by GitHub Actions and must pass release checks before packaging

## Repository policy

Do not commit real `config.php`, `admin/config.php`, credentials, API keys, runtime cache, logs, sessions or generated store data.
