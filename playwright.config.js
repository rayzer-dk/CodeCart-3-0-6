const { defineConfig, devices } = require('@playwright/test');

const baseURL = process.env.E2E_BASE_URL || 'http://127.0.0.1:8080';
const address = new URL(baseURL);
const isLocal = ['localhost', '127.0.0.1', '[::1]'].includes(address.hostname);
if (!isLocal && process.env.E2E_ALLOW_REMOTE !== '1') {
  throw new Error('Browser tests require a local QA instance unless E2E_ALLOW_REMOTE=1 is explicitly set.');
}

module.exports = defineConfig({
  testDir: './tests/E2E',
  testMatch: '**/*.spec.js',
  timeout: 30000,
  expect: { timeout: 10000 },
  fullyParallel: false,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [
    ['list'],
    ['html', { open: 'never', outputFolder: 'playwright-report' }],
    ['junit', { outputFile: 'test-results/results.xml' }],
  ],
  outputDir: 'test-results/artifacts',
  use: {
    baseURL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    actionTimeout: 12000,
    navigationTimeout: 20000,
  },
  projects: [
    { name: 'chromium-desktop', use: { ...devices['Desktop Chrome'] } },
    { name: 'chromium-mobile', use: { ...devices['Pixel 7'] } },
    { name: 'firefox-desktop', use: { ...devices['Desktop Firefox'] } },
    { name: 'webkit-mobile', use: { ...devices['iPhone 13'] } },
  ],
});
