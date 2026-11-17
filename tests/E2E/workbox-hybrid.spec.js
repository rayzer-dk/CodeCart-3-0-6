const { test, expect } = require('@playwright/test');

test('a new CodeCart store leaves Workbox fully off by default', async ({ page }) => {
  const response = await page.goto('/', { waitUntil: 'load' });
  expect(response?.ok()).toBe(true);
  await expect(page.locator('html')).toHaveAttribute('data-ccp-workbox', '0');
  await expect(page.locator('script[src*="codecart/workbox/register.js"]')).toHaveCount(0);
  await expect(page.locator('link[rel="manifest"]')).toHaveCount(0);
  const manifest = await page.request.get('/index.php?route=common/pwa/manifest');
  expect(manifest.status()).toBe(404);
  const workboxWorkers = await page.evaluate(async () => {
    if (!('serviceWorker' in navigator)) return [];
    const registrations = await navigator.serviceWorker.getRegistrations();
    return registrations.filter((registration) =>
      [registration.active, registration.waiting, registration.installing]
        .some((worker) => worker && new URL(worker.scriptURL).pathname.endsWith('/codecart-sw.js')));
  });
  expect(workboxWorkers).toHaveLength(0);
});
