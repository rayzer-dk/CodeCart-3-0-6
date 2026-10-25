# Isolated CodeCart browser tests

The suite targets a separately installed QA store. Never run cart or login tests against a production database.

Use Node.js 22+, PHP 8.4 and an isolated MySQL/MariaDB database with the standard CodeCart CLI installer. Start the site at http://127.0.0.1:8080/.

Install with `npm install --no-package-lock` and `npx playwright install chromium firefox webkit`.
Set `E2E_PRODUCT_ID` to an in-stock product without mandatory options and set `E2E_ADMIN_USERNAME` and `E2E_ADMIN_PASSWORD` to the QA account.
Run `npm run test:e2e` (four browser profiles), or `npm run test:e2e:chromium`.

`playwright.config.js` is the source of configuration; `playwright.config.ts` forwards to it for TypeScript tooling. The CI workflow prepares a disposable database and starts PHP automatically. Reports and failure traces are saved as GitHub Actions artifacts. External targets are rejected unless explicitly permitted via `E2E_ALLOW_REMOTE=1`.
