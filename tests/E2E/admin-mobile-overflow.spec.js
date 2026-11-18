const { test, expect } = require('@playwright/test');

test('mobile header keeps extra module actions reachable and restores them on desktop', async ({ page }) => {
  const username = process.env.E2E_ADMIN_USERNAME;
  const password = process.env.E2E_ADMIN_PASSWORD;
  if (!username || !password) { throw new Error('The isolated QA login is required.'); }

  await page.setViewportSize({ width: 360, height: 740 });
  await page.goto('/admin/');
  await page.locator('#input-username').fill(username);
  await page.locator('#input-password').fill(password);
  await Promise.all([
    page.waitForURL(/user_token=[a-zA-Z0-9]+/, { timeout: 20000 }),
    page.locator('form button[type="submit"]').click()
  ]);
  const nav = page.locator('#header .navbar-right');
  await expect(nav).toBeVisible();

  await page.evaluate(() => {
    const nav = document.querySelector('#header .navbar-right');
    window.codecartExtraClickCount = 0;
    for (let i = 0; i < 9; i++) {
      const item = document.createElement('li');
      item.className = 'qa-added-action';
      item.id = 'qa-header-extra-' + i;
      const link = document.createElement('a');
      link.href = '#';
      link.title = 'Extra module ' + i;
      const icon = document.createElement('i');
      icon.className = 'fa fa-cube';
      link.appendChild(icon);
      link.addEventListener('click', (event) => {
        event.preventDefault();
        window.codecartExtraClickCount++;
      });
      item.appendChild(link);
      nav.appendChild(item);
    }
  });

  await expect(page.locator('#ccp-header-more-toggle')).toBeVisible();
  await page.locator('#ccp-header-more-toggle').click();
  const extra = page.locator('#ccp-header-overflow-panel .qa-added-action').first();
  await expect(extra).toBeVisible();
  await extra.locator('a').click();
  await expect.poll(() => page.evaluate(() => window.codecartExtraClickCount)).toBeGreaterThan(0);
  await expect(page.locator('#ccp-header-more-toggle')).toHaveAttribute('aria-expanded','true');

  await page.setViewportSize({ width: 1500, height: 850 });
  await expect(page.locator('#ccp-header-more-toggle')).toBeHidden();
  await expect(nav.locator('.qa-added-action')).toHaveCount(9);
  await expect(page.locator('#ccp-header-overflow-panel .qa-added-action')).toHaveCount(0);
  await nav.locator('#qa-header-extra-0 > a').click();
  await expect.poll(() => page.evaluate(() => window.codecartExtraClickCount)).toBeGreaterThan(1);
});
